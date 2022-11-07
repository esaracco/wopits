<?php

namespace Wopits;

class DbCache {
  // This method parse raw MySQL creation tables script to extract fields
  // descriptions.
  //
  // During the deployment, this method will be deleted and replaced by another
  // one that will return cached data instead of parsing file.
//<WPTPROD-remove>
  public static function getDBDescription():array {
    static $ret = [];

    $table = '';
    foreach (file(__DIR__.'/../db/mysql/wopits-create_tables.sql') as $line) {
      if (substr($line, 0, 2) === '--') continue;

      // Extract TABLE NAME
      if (preg_match('/\s*CREATE\sTABLE\s([a-z_]+)/', $line, $m)) {
        $table = $m[1];
      // Extract FIELD TYPE
      } elseif (
          preg_match('/\s+`?([a-z_]+)`?\s+([A-Z]+)(\s*\([^\)]+\))?(\s+UNSIGNED)?(\s+NOT\s+NULL)?/', $line, $m)) {
        @list(,$field, $type, $data, $unsigned, $notNull) = $m;

        if (isset($data)) {
          $data = str_replace(['(', ')', "'", ' '], '', $data);
          if (strpos($data, ',') !== false) {
            $data = explode(',', $data);
          }
        }

        if (!isset($ret[$table])) {
          $ret[$table] = [];
        }

        $isEnum = is_array($data);

        if ($isEnum) {
          $tmp = [];
          for ($i = 0, $iLen = count($data); $i < $iLen; $i++) {
            $tmp[$data[$i]] = true;
          }
          $data = $tmp;
        }

        $ret[$table][$field] = [
          'type' => strtolower($type),
          'unsigned' => isset($unsigned),
          'nullable' => !isset($notNull),
          'length' => (empty($data) || $isEnum) ? 0 : $data,
          'values' => $isEnum ? $data : null,
        ];
      }
    }

    return $ret ?? null;
  }
//</WPTPROD-remove>

  public static function getFieldLength(string $table, string $field):?int {
    return self::getDBDescription()[$table][$field]['length'];
  }
}
