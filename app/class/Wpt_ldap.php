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
        ':ldap_connect '.($host?$host:WPT_LDAP_HOST).' failed!');
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
      error_log (__METHOD__.':'.__LINE__.":ldap_bind `$dn` failed!");

    return $r;
  }

  public function getUsers ($fromScript = false)
  {
    $ret = [];
    $filter = '(objectClass='.WPT_LDAP_OBJECTCLASS.')';

    if ( !($s = @ldap_search ($this->ldap,
                  WPT_LDAP_BASEDN, $filter, ['uid', 'mail', 'cn'])) )
    {
      $msg = __METHOD__.':'.__LINE__."ldap_search() `$filter` failed!";

      if ($fromScript)
        exit ("$msg\n");
      else
        error_log ($msg);
    }
    else
    {
      if ( !($r = ldap_get_entries ($this->ldap, $s)) )
      {
        $msg = __METHOD__.':'.__LINE__.":ldap_get_entries() `$filter` failed!";

        if ($fromScript)
          exit ("$msg\n");
        else
          error_log ($msg);
      }
      elseif ($r['count'])
      {
        foreach ($r as $item)
        {
          $uid = $item['uid'][0];
          $mail = $item['mail'][0]??null;
          $cn = $item['cn'][0]??'';

          if (!empty ($mail))
            $ret[] = ['uid' => $uid, 'mail' => $mail, 'cn' => $cn];
          elseif ($uid && $fromScript)
            echo "ERROR No email for LDAP user $uid\n";
        }
      }
    }

    return $ret;
  }

  public function getUserData ($uid)
  {
    $filter = '(&(objectClass='.WPT_LDAP_OBJECTCLASS.')'.
              '(uid:caseExactMatch:='.ldap_escape ($uid, '', LDAP_ESCAPE_FILTER).'))';
    $ret = null;

    if ( !($s = ldap_search ($this->ldap,
             WPT_LDAP_BASEDN, $filter, ['dn', 'mail', 'cn'])) )
    {
      error_log (__METHOD__.':'.__LINE__.":ldap_search() `$filter` failed!");
    }
    elseif ( !($r = ldap_get_entries ($this->ldap, $s)) )
    {
      error_log (__METHOD__.':'.__LINE__.
        ":ldap_get_entries() `$filter` failed!");
    }
    elseif ($r['count'])
    {
      $item = $r[0];
      $ret = [
        'dn' => $item['dn'],
        'mail' => $item['mail'][0]??null,
        'cn' => $item['cn'][0]??''
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
