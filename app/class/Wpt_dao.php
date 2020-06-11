<?php

  class Wpt_dao extends PDO
  {
    protected $isMySQL;

    function __construct ()
    {
      if (!getenv('DEPLOY'))//PROD-remove
        parent::__construct (
          WPT_DSN, WPT_DB_USER, WPT_DB_PASSWORD, [
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
          ]);

      if (!getenv('DEPLOY'))//PROD-remove
        $this->isMySQL =
          ($this->getAttribute(PDO::ATTR_DRIVER_NAME) == 'mysql');
    }

    // Dummy access to the DB, to preserve persistent connexion
    public function ping ()
    {
      $this->query ("SELECT 1");
    }

    protected function getFieldQuote ()
    {
      return ($this->isMySQL) ? '`' : '"';
    }

    protected function setTimezone ($U = null)
    {
      $User = $U ?? new Wpt_user ();

      $this->exec (($this->isMySQL) ?
        // MySQL
        "SET time_zone='".$User->getTimezone()."'":
        // PostgreSQL
        "SET timezone TO '".$User->getTimezone()."'");
    }

    protected function executeQuery ($sql, $data, $where = null)
    {
      $q = $this->getFieldQuote ();

      // INSERT
      if (strpos ($sql, 'INSERT') !== false)
      {
        $fields = $q.implode("$q,$q", array_keys($data)).$q;
        $sql .=
          " ($fields) VALUES (".
          preg_replace ("/$q([a-z_]+)$q/", ':$1', $fields).')';
      }
      // UPDATE
      elseif (strpos ($sql, 'UPDATE') !== false)
      {
        $fields = '';
        foreach ($data as $k => $v)
          $fields .= "$q$k$q = :$k,";
        $sql .= ' SET '.substr ($fields, 0, -1).' WHERE ';

        foreach ($where as $k => $v)
          $sql .= "$q$k$q = :$k AND ";
        $sql = substr ($sql, 0, -4);

        $data = array_merge ($data, $where);
      }

      $stmt = $this->prepare ($sql);
      $stmt->execute ($data);

      return $stmt->rowCount ();
    }
  }
