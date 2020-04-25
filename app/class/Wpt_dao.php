<?php

  class Wpt_dao extends PDO
  {
    function __construct ()
    {
      if (!getenv('DEPLOY'))//PROD-remove
        parent::__construct (
          WPT_DSN, WPT_DB_USER, WPT_DB_PASSWORD, [
//            PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone = '+06:00'",
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
          ]);
    }

    // Dummy access to the DB, to preserve persistent connexion
    public function ping ()
    {
      $this->query ("SELECT 1");
    }

    protected function setTimezone ($U = null)
    {
      $User = $U ?? new Wpt_user ();

      $this->query ("SET time_zone='".$User->getTimezone()."'"); 
    }

    protected function executeQuery ($sql, $data, $where = null)
    {
      preg_match ('/^\s*(INSERT|UPDATE)\s*/', $sql, $m);

      if ($m[1] == 'INSERT')
      {
        $fields = '`'.implode ("`,`", array_keys($data)).'`';
        $sql .=
          " ($fields) VALUES (".
          preg_replace ('/(`([a-z_]+)`)/', ':$2', $fields).')';
      }
      elseif ($m[1] == 'UPDATE')
      {
        $fields = '';
        foreach ($data as $k => $v)
          $fields .= "`$k` = :$k,";
        $sql .= ' SET '.substr ($fields, 0, -1).' WHERE ';

        foreach ($where as $k => $v)
          $sql .= " `$k` = :$k AND ";
        $sql = substr ($sql, 0, -4);

        $data = array_merge ($data, $where);
      }

      $stmt = $this->prepare ($sql);
      $stmt->execute ($data);

      return $stmt->rowCount ();
    }
  }
