<?php

namespace Proxima\models\system;

use \Proxima\core\ModelBase;

/**
 * Log model class
 */
class Log extends ModelBase
{   
    private static $tableName = 'bc_log';

    /**
     * finds the log by log id
     *
     * @param mixed $id log id
     * @return Content Content object
     */
    public static function find($id)
    {
        global $db;
        $tableName = self::$tableName;
        $query = "SELECT * FROM {$tableName} WHERE log_id = '{$id}'";
        $row = $db->queryRow($query);
        return new Log($row);
    }

    /**
     * search logs
     *
     * @param array $condition array of condition. action(required), fromDate(required), toDate(required), sendUserIds(optional)
     * @param array $pagination array of start pos and limit for pagination ['start' => 0, 'limit' => 100]     
     * @return array array of Log list and totalCount ['totalCount' => 100, 'data' => array of Log object]
     */
    public static function search($condition, $pagination)
    {
        global $db;
        $tableName = self::$tableName;

        $where = " action = '{$condition['action']}' 
                AND created_date >= '{$condition['fromDate']}' AND created_date <= '{$condition['toDate']}'";

        if(!empty($condition['sendUserIds'])) {
            $sendUserIdsString = implode("', '", $condition['sendUserIds']);
            $where .= " AND user_id in ('{$sendUserIdsString}')";
        }

        $countQuery = "SELECT count(*) FROM {$tableName} WHERE {$where} ";        

        $totalCount = $db->queryOne($countQuery);

        $query = "SELECT * FROM {$tableName} WHERE {$where} ORDER BY log_id DESC";

        if(!empty($pagination)) {
            $db->setLimit($pagination['limit'], $pagination['start']);
        }
        
        $rows = $db->queryAll($query);

        $logs = [];
        foreach($rows as $row) {
            $logs[] = new Log($row);
        }
        return [
                'totalCount' => $totalCount,
                'data' => $logs
            ];
    }

    /**
     * search logs
     *
     * @param array $condition array of condition. action(required), fromDate(required), toDate(required), sendUserIds(optional)
     * @param array $pagination array of start pos and limit for pagination ['start' => 0, 'limit' => 100]     
     * @return array array of Log list and totalCount ['totalCount' => 100, 'data' => array of Log object]
     */
    public static function searchMultiAction($condition, $pagination)
    {
        global $db;
        $tableName = self::$tableName;

        $actions = $condition['action'];

        if(is_array($actions)) {
            foreach($actions as $v) {
                $action[] = $v;
            }

            $where = " action in (".join(',', $action).")";
         } else {
            $where = " action = '".$actions."'";
         }

        $where .= " AND created_date >= '{$condition['fromDate']}' AND created_date <= '{$condition['toDate']}'";

        if(!empty($condition['userIds'])) {
            $userIdsString = implode("', '", $condition['userIds']);
            $where .= " AND user_id in ('{$userIdsString}')";
        }

        if(is_numeric($condition['ud_content_id'])) {
            $where .= " AND ud_content_id = ".$condition['ud_content_id'];
        }

        $countQuery = "SELECT count(*) FROM {$tableName} WHERE {$where} ";

        $totalCount = $db->queryOne($countQuery);

        $query = "SELECT * FROM {$tableName} WHERE {$where} ORDER BY log_id DESC";

        if(!empty($condition['ud_content_id'])) {
            $query = "SELECT a.*, (SELECT ud_content_title FROM BC_UD_CONTENT WHERE ud_content_id = a.ud_content_id) as ud_content_title,
                                    (SELECT title FROM BC_CONTENT WHERE content_id = a.content_id) as title,
                                    (SELECT user_nm FROM BC_MEMBER where user_id = a.user_id) as user_nm
                    FROM (".$query.") a ORDER BY log_id DESC";
        }

        if(!empty($pagination)) {
            $db->setLimit($pagination['limit'], $pagination['start']);
        }
        
        $rows = $db->queryAll($query);

        $logs = [];
        foreach($rows as $row) {
            $logs[] = new Log($row);
        }

        
        return [
                'totalCount' => $totalCount,
                'data' => $logs
            ];
    }
}