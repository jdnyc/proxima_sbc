<?PHP
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
session_start();
fn_checkAuthPermission($_SESSION);
$task_id = $_POST['task_id']; 

if ( !empty($task_id) )
{
	//$db->setLimit(20,0);
	$logs = $db->queryAll("select * from bc_task_log where task_id = $task_id order by task_log_id desc");

	$result = '{"success":"true", "total": '.count($logs).', "data": '.json_encode($logs)."}";

	echo $result;
}

?>