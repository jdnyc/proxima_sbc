<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
fn_checkAuthPermission($_SESSION);

try {
    $user_id = $_SESSION['user']['user_id'];
    $is_admin = $_SESSION['user']['is_admin'];
    
    $topAxisSub = json_decode($_POST['top_axis_sub']);
    $query ="SELECT
                (SELECT UD_CONTENT_TITLE FROM bc_ud_content WHERE ud_content_id=c.UD_CONTENT_ID ) AS UD_CONTENT_TITLE,
                substr(m.CREATED_DATE,0,8) AS CREATED_DATE,
                count(c.content_id) cnt,
                ROUND(sum(m.filesize) / 1024 / 1024 / 1024 ,2 ) AS filesize_gb,
                ROUND(sum(m.filesize) / 1024 / 1024 / 1024 /1024,2 ) AS filesize_tb
            FROM
                bc_content c
                JOIN (SELECT filesize, content_id, media_id,CREATED_DATE FROM bc_media WHERE media_type='archive' AND filesize > 0 ) m
                ON (c.content_id=m.content_id)
            WHERE
                c.IS_DELETED='N'
                AND c.status >= 0
                AND m.CREATED_DATE between 20180805000000 AND 20180811240000
                GROUP BY UD_CONTENT_ID, substr(m.CREATED_DATE,0,8) ORDER BY substr(m.CREATED_DATE,0,8), UD_CONTENT_ID";

    $statisticses = $db->queryAll($query);
    $statisticsCount = 0;
    $data = array();

    foreach($statisticses as $key => $statistics){
        $statistics['top_axis_sub'] = $topAxisSub[0];
        $data[$statisticsCount] = $statistics;
        // $statistics['top_axis_sub'] = $topAxisSub[1];
        // $data[$statisticsCount];
        // $data[$statisticsCount] = $statistics;
        // $statisticsCount++;
    };

    echo json_encode(array(
        'success' => true,
        'data' => $data
        ));

} catch (Exception $e) {
    echo json_encode(array(
    'success' => false,
    'message' => $e->getMessage()
    ));
}