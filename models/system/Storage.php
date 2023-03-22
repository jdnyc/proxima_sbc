<?php

namespace Proxima\models\system;

use \Proxima\core\ModelBase;

/**
 * Storage model class
 */
class Storage extends ModelBase
{   
    public static function find($storageId)
    {
        global $db;
        $query = "SELECT * FROM bc_storage WHERE storage_id = '{$storageId}'";
        $row = $db->queryRow($query);
        return new Storage($row);
    }

    public static function all($where = null)
    {
        global $db;
        $query = "SELECT * FROM bc_storage";
        if(!empty($where)) {
            $query .= " WHERE {$where}";
        }
        $rows = $db->queryAll($query);

        $data = [];
        foreach($rows as $row) {
            $data[] = new Storage($row);
        }
        return $data;
    }

    public function getPath()
    {
        $path = '';
        if (stripos(PHP_OS, 'linux') === 0) {
            $path = $this->get('path_for_unix');
        } else {
            $path = $this->get('path');
        }
        return $path;
    }

}