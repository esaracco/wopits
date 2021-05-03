<?php

namespace Wopits;

require_once (__DIR__.'/../config.php');

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

  function __construct (array $args = null, object $ws = null)
  {
    $this->_dbDescription = DbCache::getDBDescription ();

    // Set context from WebSocket server
    if ($ws)
    {
      $this->ws = $ws;
      $this->userId = $ws->id;
      $this->sessionId = $ws->sessionId;

      Helper::changeLocale ($ws->slocale);
    }
    else
      $this->userId = $args['userId']??$_SESSION['userId']??null;

    $this->slocale = $this->ws->slocale??$_SESSION['slocale']??'en';
    $this->wallId = $args['wallId']??null;
    $this->data = $args['data']??null;

    parent::__construct (
      WPT_DSN, WPT_DB_USER, WPT_DB_PASSWORD, [
        \PDO::ATTR_PERSISTENT => true,
        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        \PDO::ATTR_EMULATE_PREPARES => false,
        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
      ]);
  }

  // Dummy access to the DB, to preserve persistent connection
  public function ping ():void
  {
    $this->query ('SELECT 1');
  }

  public function getUploadedFileInfos (?object $data):array
  {
    if (!is_object ($data) ||
        !$data->size ||
        !preg_match ('#data:[^;]*;base64,(.*)#', $data->content, $content))
      return [null, null, _("Empty file or bad file format.")];

    // File extension is not mandatory
    preg_match ('#\.([a-z0-9]+)$#i', $data->name, $ext);

    return [$ext[1]??null, $content[1], null];
  }

  protected function sendWsClient (array $msg):void
  {
    $client = new Services\WebSocket\Client ('127.0.0.1', WPT_WS_PORT);

    $client->connect ();

    $client->send (json_encode ($msg));
  }

  // Very basic DB fields validator.
  protected function checkDBValue (string $table, string $field, &$value):void
  {
    $f = $this->_dbDescription[$table][$field]??null;

    //<WPTPROD-remove>
    if (is_null ($f))
      throw new \Exception ("Unknown DB field $table::$field");
    //</WPTPROD-remove>

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
        if (!preg_match ('/^\-?\d+$/', $value))
        {
          error_log ("Bad DB field integer value `$value` for $table::$field");

          $value = intval ($value);
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
      // `email` must be a simple valid email.
      case 'email':
        $badValue = !filter_var ($value, FILTER_VALIDATE_EMAIL);
        break;
    }

    if ($badValue)
      throw new \Exception ("Bad DB field value `$value` for $table::$field");
  }

  protected function getDuplicateQueryPart (array $fields):string
  {
    return ($this->getAttribute(\PDO::ATTR_DRIVER_NAME) == 'mysql') ?
      // MySQL
      ' ON DUPLICATE KEY UPDATE ' :
      // PostgreSQL
      ' ON CONFLICT ('.implode(',',$fields).') DO UPDATE SET ';
  }

  public function executeQuery (string $sql, array $data,
                                array $where = null):int
  {
    if (!preg_match ('/(INSERT INTO|UPDATE) ([a-z_]+)$/', $sql, $m))
      throw new \Exception ("Not a valid SQL exec request (`$sql`)");

    list (, $action, $table) = $m;

    // Check values. Bad values are silently converted when possible.
    foreach ($data as $_field => $_value)
      $this->checkDBValue ($table, $_field, $data[$_field]);

    switch ($action)
    {
      // INSERT
      case 'INSERT INTO':

        $fields = implode (',', array_keys($data));
        $sql .= " ($fields) VALUES (".
                preg_replace ('/([a-z_]+)/', ':$1', $fields).')';
        break;

      // UPDATE
      case 'UPDATE':

        $fields = '';
        foreach ($data as $k => $v)
          $fields .= "$k = :$k,";
        $sql .= ' SET '.substr($fields, 0, -1).' WHERE ';

        foreach ($where as $k => $v)
          $sql .= "$k = :$k AND ";
        $sql = substr ($sql, 0, -4);

        $data = array_merge ($data, $where);
        break;
    }

    $stmt = $this->prepare ($sql);
    $stmt->execute ($data);

    return $stmt->rowCount ();
  }

  protected function getUserDir (string $type = null):string
  {
    return ($type == 'web') ?
      WPT_DATA_WPATH."/users/{$this->userId}" :
      Helper::getSecureSystemName ("/users/{$this->userId}");
  }

  protected function getWallDir (string $type = null, int $wallId = null):string
  {
    if (!$wallId)
      $wallId = $this->wallId;

    return ($type == 'web') ?
      WPT_DATA_WPATH."/walls/{$wallId}" :
      Helper::getSecureSystemName ("/walls/{$wallId}");
  }
}
