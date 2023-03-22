<?php
require_once($_SERVER['DOCUMENT_ROOT']."/lib/config.php");
file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/edius_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] channel ===> '.$_REQUEST['channel']."\r\n", FILE_APPEND);

$query = "select t1.register, t4.path
            from bc_task_workflow t1, bc_task_workflow_rule t2, bc_task_rule t3, bc_storage t4
            where t4.storage_id = t3.source_path
                and t3.task_rule_id = t2.task_rule_id
                and t2.job_priority = 1
                and t2.task_workflow_id = t1.task_workflow_id";

$path = $db -> queryAll($query);

$path_array = array();

foreach($path as $info) {
    $index = $info['register'];
    $new_path = str_replace("/","\\",$info['path']);
    $path_array[$index] = $new_path;
}

echo '{"success":"true", "data": '.json_encode($path_array)."}";

?>
