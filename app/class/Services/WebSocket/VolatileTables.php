<?php

namespace Wopits\Services\WebSocket;

require_once (__DIR__.'/../../../config.php');

use Swoole\Table as SwooleTable;
use Wopits\DbCache;

class VolatileTables
{
  private $_tables = [];
  
  public function __construct ()
  {
    // clients
    //
    // -> key: fd
    // -> settings (JSON encoded): user settings
    // -> openedChats (string comma separator): wallId of walls with chat
    //
    $this->_addTable ('clients', WPT_SWOOLE_TABLE_SIZE, [
      'sessionId' => [SwooleTable::TYPE_INT],
      'id' => [SwooleTable::TYPE_INT],
      'ip' => [SwooleTable::TYPE_STRING, 45],
      'username' => [SwooleTable::TYPE_STRING,
                     DbCache::getFieldLength('users', 'username')],
      'slocale' => [SwooleTable::TYPE_STRING, 2],
      'settings' => [SwooleTable::TYPE_STRING,
                     DbCache::getFieldLength('users', 'settings')],
      'openedChats' => [SwooleTable::TYPE_STRING, 255],
      'final' => [SwooleTable::TYPE_INT]
    ]);

    // internals
    //
    // -> key: fd
    //
    $this->_addTable ('internals', WPT_SWOOLE_TABLE_SIZE, []);

    // usersUnique
    //
    // -> key: userId
    // -> v (string comma separator): fd associated to the userId
    //
    $this->_addTable ('usersUnique', WPT_SWOOLE_TABLE_SIZE,
      ['v' => [SwooleTable::TYPE_STRING, 255]]);

    // chatUsers
    //
    // -> key: wallId
    // -> v (JSON encoded): [
    //     fd => [id => userId, name => username],
    //     ..
    //   ]
    //
    $this->_addTable ('chatUsers', WPT_SWOOLE_TABLE_SIZE,
      ['v' => [SwooleTable::TYPE_STRING, 2000]]);

    // openedWalls
    $this->_addTable ('openedWalls', WPT_SWOOLE_TABLE_SIZE*4,
      ['v' => [SwooleTable::TYPE_STRING, 2000]]);

    // activeWalls
    //
    // -> key: wallId
    // -> v (JSON encoded): [
    //   fd => userId,
    //   ...
    // ]
    //
    $this->_addTable ('activeWalls', WPT_SWOOLE_TABLE_SIZE,
      ['v' => [SwooleTable::TYPE_STRING, 2000]]);

    // activeWallsUnique
    //
    // -> v (JSON encoded): [
    //   userId => fd,
    //   ...
    // ]
    //
    $this->_addTable ('activeWallsUnique', WPT_SWOOLE_TABLE_SIZE,
      ['v' => [SwooleTable::TYPE_STRING, 2000]]);
  }

  private function _addTable (string $name, int $length, array $columns):void
  {
    $table = new SwooleTable ($length);

    //$table->columns = $columns;
    foreach ($columns as $field => $props)
      $table->column ($field, $props[0], $props[1]??null);

    $table->create ();

    $this->_tables[$name] = $table;
  }

  public function __get (string $name):SwooleTable
  {
    return $this->_tables[$name];
  }

  public function tGet (string $table, string $key):object
  {
    return (object)$this->$table->get ($key);
  }

  public function tSet (string $table, string $key, object $value):void
  {
    $this->$table->set ($key, (array)$value);
  }

  public function jGet (string $table, string $key):object
  {
    return empty ( ($v = ($this->$table->get($key))['v']) ) ?
             (object)[] : json_decode ($v);
  }

  public function jSet (string $table, string $key, object $value):void
  {
    if ($this->isEmpty ($value))
      $this->$table->del ($key);
    else
      $this->$table->set ($key, ['v' => json_encode ($value)]);
  }

  public function isEmpty (object $value):bool
  {
    return empty ((array)$value);
  }

  public function lSet (string $table, string $key, array $value):void
  {
    if (empty ($value))
      $this->$table->del ($key);
    else
      $this->$table->set ($key, ['v' => implode (',', $value)]);
  }

  public function lGet (string $table, string $key):array
  {
    return empty ( ($v = ($this->$table->get($key))['v']) ) ?
             [] : explode (',', $v);
  }

  public function lAdd (string $table, string $key, int $newValue):void
  {
    $v = $this->lGet ($table, $key);

    if (!in_array ($newValue, $v))
    {
      $v[] = $newValue;
      $this->lSet ($table, $key, $v);
    }
  }

  public function lDel (string $table, string $key, int $value):void
  {
    $v = $this->lGet ($table, $key);

    array_splice ($v, array_search ($value, $v), 1);

    $this->lSet ($table, $key, $v);
  }
}
