<?php

namespace Proxima\models\system;

use \Proxima\core\ModelBase;
use Proxima\models\system\Storage;

/**
 * UserContentStorage model class
 */
class UserContentStorage extends ModelBase
{   
    public static function find($userContentId, $type)
    {
        global $db;
        $query = "SELECT * FROM bc_ud_content_storage WHERE ud_content_id = '{$userContentId}' AND us_type = '{$type}'";
        $row = $db->queryRow($query);
        return new UserContentStorage($row);
    }

    public static function all($where = null)
    {
        global $db;
        $query = "SELECT * FROM bc_ud_content_storage";
        if(!empty($where)) {
            $query .= " WHERE {$where}";
        }
        $rows = $db->queryAll($query);

        $data = [];
        foreach($rows as $row) {
            $data[] = new UserContentStorage($row);
        }
        return $data;
    }

    public function storage()
    {        
        $storageId = $this->get('storage_id');
        return Storage::find($storageId);
    }
}