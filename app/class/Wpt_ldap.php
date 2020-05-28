<?php

class Wpt_ldap
{
  private $ldap;

  public function connect ()
  {
    $ret = null;

    if ( !($host = $this->checkHost (WPT_LDAP_HOST)) ||
         !($ret = $this->ldap = ldap_connect ($host)) )
      error_log (__METHOD__.':'.__LINE__.
        ':LDAP connection to '.($host?$host:WPT_LDAP_HOST).' failed!');
    else
      $ret = $this->bind (WPT_LDAP_BINDDN, WPT_LDAP_BINDPW);

    return $ret;
  }

  public function checkHost ()
  {
    $host = WPT_LDAP_HOST;
    $ret = null;

    if (preg_match ('#^(ldaps?)://([^:]+):?(\d+)?$#i', $host, $m))
    {
      @list (,$scheme,$host,$port) = $m;

      if (!$port)
        $port = (strtolower ($scheme) == 'ldap') ? 389 : 636;

      $c = fsockopen ($host, $port, $errno, $errstr, 5);
      if (is_resource ($c))
      {
        $ret = "$scheme://$host:$port";
        fclose ($c);
      }
    }

    return $ret;
  }

  public function bind ($dn, $pw)
  {
    if ( !($r = ldap_bind ($this->ldap, $dn, $pw)) )
      error_log (__METHOD__.':'.__LINE__.":LDAP bind for dn `$dn` failed!");

    return $r;
  }

  //TODO LDAP attributes mapping in site conf
  public function getUserData ($uid)
  {
    $ret = null;

    if ( !($s = ldap_search ($this->ldap,
             WPT_LDAP_BASEDN,
             // Replace user uid in template string with real user uid
             str_replace ('{uid}', $uid, WPT_LDAP_FILTER),
             ['dn', 'mail', 'cn'])) )
      error_log (__METHOD__.':'.__LINE__.":LDAP search for uid `$uid` failed!");
    else
    {
      if ( !($r = ldap_get_entries ($this->ldap, $s)) )
        error_log (
          __METHOD__.':'.__LINE__.":LDAP get entries for uid `$uid` failed!");
      elseif ($r['count'])
        $ret = [
          'dn' => $r[0]['dn'],
          'mail' => $r[0]['mail'][0],
          'cn' => $r[0]['cn'][0]
        ];
    }

    return $ret;
  }

  public function __destruct ()
  {
    if (is_resource ($this->ldap))
      ldap_close ($this->ldap);
  }
}
