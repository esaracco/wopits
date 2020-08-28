<?php

namespace Wopits;

require_once (__DIR__.'/../config.php');

use Wopits\DbCache;

class Base extends \PDO
{
  public $userId;
  public $wallId;
  public $data;
  public $wallName;
  public $sessionId;
  public $slocale;
  protected $ws;
  private $_dbDescription;

  function __construct ($args = null, $ws = null)
  {
    $this->_dbDescription = DbCache::getDBDescription ();

    if ($ws)
    {
      Common::changeLocale ($ws->slocale);

      $this->userId = $ws->id;
      $this->sessionId = $ws->sessionId;

      $this->ws = $ws;
    }
    else
      $this->userId = $args['userId']??$_SESSION['userId']??null;

    $this->slocale = $this->ws->slocale??$_SESSION['slocale']??'en';
    $this->wallId = $args['wallId']??null;
    $this->data = $args['data']??null;

    if (!getenv('DEPLOY'))//WPTPROD-remove
      parent::__construct (
        WPT_DSN, WPT_DB_USER, WPT_DB_PASSWORD, [
          \PDO::ATTR_PERSISTENT => true,
          \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
          \PDO::ATTR_EMULATE_PREPARES => false,
          \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
        ]);
  }

  // Dummy access to the DB, to preserve persistent connexion
  public function ping ()
  {
    $this->query ("SELECT 1");
  }

  public function getUploadedFileInfos ($data)
  {
    $ret = [];

    if (!is_object ($data) ||
        !$data->size ||
        !preg_match ('#\.([a-z0-9]+)$#i', $data->name, $m1) ||
        !preg_match ('#data:([^;]+);base64,(.*)#', $data->content, $m2))
    {
      $ret = [null, null, _("Empty file or bad file format")];
    }
    else
      $ret = [$m1[1], $m2[2], null];

    return $ret;
  }

  // Very basic DB fields validator.
  protected function checkDBValue ($table, $field, &$value)
  {
    $f = $this->_dbDescription[$table][$field];

    if (is_null ($value))
    {
      if ($f['nullable'])
        return;
      else
        throw new \Exception ("DB field $table::$field is not nullable");
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
        if ($maxLength && mb_strlen ($value) > $f['length'])
        {
          $fix = mb_substr ($value, 0, $maxLength);

          error_log (
            "Bad DB field length (".mb_strlen ($value).
            " instead of $maxLength) `".
            preg_replace("/(\n|\r)/", '', mb_substr ($fix, 0, 100)).
              "(...)` for $table::$field");

          $value = $fix;
        }
        break;

      case 'enum':

        if (!isset ($f['values'][$value]))
          throw new \Exception (
            "Bad DB field value `$value` for $table::$field");
        break;

      //<WPTPROD-remove>
      default:
        throw new \Exception (
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
      throw new \Exception ("Bad DB field value `$value` for $table::$field");
  }

  protected function getDuplicateQueryPart ($fields)
  {
    return ($this->getAttribute(\PDO::ATTR_DRIVER_NAME) == 'mysql') ?
      // MySQL
      ' ON DUPLICATE KEY UPDATE ' :
      // PostgreSQL
      " ON CONFLICT (".implode(',',$fields).") DO UPDATE SET ";
  }

  protected function executeQuery ($sql, $data, $where = null)
  {
    preg_match ('/(INSERT\s+INTO|UPDATE)\s+([a-z_]+)$/', $sql, $m);
    $table = $m[2];

    // Check values. Bad values are silently converted when possible.
    foreach ($data as $_field => $_value)
      $this->checkDBValue ($table, $_field, $data[$_field]);

    // INSERT
    if (strpos ($sql, 'INSERT') !== false)
    {
      $fields = implode (',', array_keys($data));
      $sql .= " ($fields) VALUES (".
              preg_replace ("/([a-z_]+)/", ':$1', $fields).')';
    }
    // UPDATE
    elseif (strpos ($sql, 'UPDATE') !== false)
    {
      $fields = '';
      foreach ($data as $k => $v)
        $fields .= "$k = :$k,";
      $sql .= ' SET '.substr($fields, 0, -1).' WHERE ';

      foreach ($where as $k => $v)
        $sql .= "$k = :$k AND ";
      $sql = substr ($sql, 0, -4);

      $data = array_merge ($data, $where);
    }

    $stmt = $this->prepare ($sql);
    $stmt->execute ($data);

    return $stmt->rowCount ();
  }

  protected function getUserDir ($type = null)
  {
    return ($type) ?
      WPT_DATA_WPATH."/users/{$this->userId}" :
      Common::getSecureSystemName ("/users/{$this->userId}");
  }

  protected function getWallDir ($type = null)
  {
    return ($type == 'web') ?
      WPT_DATA_WPATH."/walls/{$this->wallId}" :
      Common::getSecureSystemName ("/walls/{$this->wallId}");
  }
}
