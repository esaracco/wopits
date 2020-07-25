<?php
  require_once (__DIR__.'/Wpt_dbCache.php');

  class Wpt_dao extends PDO
  {
    protected $isMySQL;

    function __construct ()
    {
      if (!getenv('DEPLOY'))//WPTPROD-remove
        parent::__construct (
          WPT_DSN, WPT_DB_USER, WPT_DB_PASSWORD, [
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
          ]);

      if (!getenv('DEPLOY'))//WPTPROD-remove
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

    // Very basic DB fields validator.
    protected function checkDBValue ($table, $field, &$value)
    {
      $f = Wpt_dbCache::getDBDescription()[$table][$field];

      if (is_null ($value))
      {
        if ($f['nullable'])
          return;
        else
          throw new Exception ("DB field $table::$field is not nullable");
      }

      switch ($f['type'])
      {
        case 'int':
        case 'tinyint':
        case 'smallint':

          // Here we convert non integer values.
          if (!preg_match ('/^\d+$/', $value))
          {
            $fix = intval ($value);

            error_log ("Bad DB field integer value `$fix` for $table::$field");

            $value = $fix;
          }
          break;

        case 'char':
        case 'varchar':
        case 'text':

          $maxLength = $f['length'];

          // Here we cut long strings.
          if ($maxLength && strlen ($value) > $f['length'])
          {
            $fix = substr ($value, 0, $maxLength);

            error_log (
              "Bad DB field length (".strlen ($value).
              " instead of $maxLength) `".
              preg_replace("/(\n|\r)/", '', substr ($fix, 0, 100)).
                "(...)` for $table::$field");

            $value = $fix;
          }
          break;

        case 'enum':

          if (!isset ($f['values'][$value]))
            throw new Exception (
              "Bad DB field value `$value` for $table::$field");
          break;

        //<WPTPROD-remove>
        default:
          throw new Exception (
            "Unknown DB field type `{$f['type']}` for $table::$field");
        //</WPTPROD-remove>
      }

      $badValue = false;
      switch ($field)
      {
        // `settings` must be a JSON string.
        case 'settings':
          $badValue = !@json_decode($value)->locale;
          break;

        // `email` must be a simple valid email.
        case 'email':
          $badValue = !filter_var ($value, FILTER_VALIDATE_EMAIL);
          break;
      }

      if ($badValue)
        throw new Exception ("Bad DB field value `$value` for $table::$field");
    }

    protected function getDuplicateQueryPart ($fields)
    {
      $q = $this->getFieldQuote ();

      return ($this->isMySQL) ?
        // MySQL
        ' ON DUPLICATE KEY UPDATE ' :
        // PostgreSQL
        " ON CONFLICT ($q".implode("$q,$q",$fields)."$q) DO UPDATE SET ";
    }

    protected function executeQuery ($sql, $data, $where = null)
    {
      $q = $this->getFieldQuote ();

      preg_match ('/(INSERT\s+INTO|UPDATE)\s+([a-z_]+)$/', $sql, $m);
      $table = $m[2];

      // Check values. Bad values are silently converted when possible.
      foreach ($data as $_field => $_value)
        $this->checkDBValue ($table, $_field, $data[$_field]);

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
