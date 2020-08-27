<?php

namespace Wopits;

require_once (__DIR__.'/../prepend.php');

class DbCache
{
  // This method parse raw MySQL creation tables script to extract fields
  // descriptions.
  //
  // During the deployment, this method will be deleted replaced by another
  // one that will return cached data instead of parsing file.
  //<WPTPROD-remove>
  public static function getDBDescription ()
  {
    static $ret = [];

    $table = '';
    foreach (file (__DIR__.'/../db/mysql/wopits-create_tables.sql') as $line)
    {
      if (substr ($line, 0, 2) == '--') continue;
      if (preg_match ('/\s*CREATE\sTABLE\s([a-z_]+)/', $line, $m))
        $table = $m[1];
      elseif (
        preg_match ("/\s+`?([a-z_]+)`?\s+([A-Z]+)(\s*\([^\)]+\))?(\s+UNSIGNED)?(\s+NOT\s+NULL)?/", $line, $m))
      {
        @list (,$field, $type, $data, $unsigned, $notNull) = $m;

        if (isset ($data))
        {
          $data = str_replace (['(', ')', "'", ' '], '', $data);
          if (strpos ($data, ',') !== false)
            $data = explode (',', $data);
        }

        if (!isset ($ret[$table]))
          $ret[$table] = [];

        $isEnum = is_array ($data);

        if ($isEnum)
        {
          $tmp = [];
          for ($i = 0, $iLen = count ($data); $i < $iLen; $i++)
            $tmp[$data[$i]] = 1;
          $data = $tmp;
        }

        $ret[$table][$field] = [
          'type' => strtolower ($type),
          'unsigned' => isset ($unsigned),
          'nullable' => !isset ($notNull),
          'length' => (empty($data) || $isEnum) ? 0 : $data,
          'values' => $isEnum ? $data : null
        ];
      }
    }

    return $ret;
  }
  //</WPTPROD-remove>

  public static function getFieldLength ($table, $field)
  {
    $ret = self::getDBDescription()[$table][$field]['length'];

    return ($ret) ? $ret : null;
  }
}
