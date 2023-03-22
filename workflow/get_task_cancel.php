<?PHP
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
/*
	<Request>
		<GetTask action="cancel" id="1111" channel="192.168.1.1" />
	</Request>

	<Result Action="cancel">
		<TaskID>4646</TaskID>
		<Info>DoCancel</Info>
	 </Result>

	 -- 연동
	 update_task_status.php
	 <Request>
		<Result Action="canceled">
			<TaskID>4280</TaskID>
		</Result>
	</Request>
*/
$is_debug = 1;
if ( false )
{
	define('TASK_TABLE', 'task_test');
	define('TASK_LOG_TABLE', 'task_log_test');
}
else
{
	define('TASK_TABLE', 'bc_task');
	define('TASK_LOG_TABLE', 'bc_task_log');
}
$response = new SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\"?> \n<Response />");


try
{
	$xml = file_get_contents('php://input');
	//_debug(basename(__FILE__), $xml);
	if (empty($xml)) throw new Exception('요청값이 없습니다.');

	$xml = new SimpleXMLElement($xml);
	$task_action = $xml->GetTask['Action'];
	$task_id	 = $xml->GetTask['TaskID'];
	$remote_ip	 = $_SERVER['REMOTE_ADDR'];

	//$db->exec("LOCK TABLES ".TASK_TABLE." WRITE");

	$assign_task = $db->queryRow("select * from ".TASK_TABLE." where task_id=$task_id and status='$task_action'");
	if (empty($assign_task)) throw new Exception("TaskID: $task_id, $task_action 작업이 없습니다.", TASK_NOHAVEITEM);



	//$rtn = $db->exec(sprintf("update ".TASK_TABLE." set status='canceling', assign_ip='%s' where task_id=%d and status='%s'", $remote_ip, $task_id, $task_action));


	//$db->exec('UNLOCK TABLES');

	//update 에 대한 영향값은 0이다.
	//if (empty($rtn)) throw new TaskException(sprintf('cancel 상태로 업데이트에 실패하였습니다.[task_id: %d]', $task_id), $task_id);

	$result = $response->addChild('Result');
	$result->addAttribute('Action', 'cancel');
	$result->addChild('TaskID', $task_id);
	$result->addChild('Info', 'DoCancel');

	//$result->addChild('TaskID', $assign_task['task_id']);
	//$result->addChild('Info', 'DoCancel');

	echo $response->asXML();
	//_debug(basename(__FILE__), $response->asXML());
}
catch (TaskException $e)
{
	$task_id					= $e->getTaskID();
	$task_log_msg				= $e->getMessage();
	$task_log_creation_datetime = date('YmdHis');

//	$db->exec("update ".TASK_TABLE." set status='error' where task_id=".$task_id);
//	_debug(basename(__FILE__), $db->last_query);

//	$db->exec("insert into ".TASK_LOG_TABLE." (task_id, description, creatied_date) values ($task_id, '$task_log_msg', '$task_log_creation_datetime')");
//	_debug(basename(__FILE__), $db->last_query);

//	_debug(basename(__FILE__), $task_log_msg);
}
catch (Exception $e)
{
	//$db->exec('UNLOCK TABLES');

	if ($e->getCode() == TASK_NOHAVEITEM)
	{
		$result = $response->addChild('Result');
			$result->addAttribute('Action', 'cancel');

		$result->addChild('TaskID', $task_id);
		$result->addChild('Info', 'GoAhead');
		$rtn = $response->asXML();
		echo $rtn;
	}
	else
	{
		$result = $response->addChild('Result');
			$result->addAttribute('success', 'false');
			$result->addAttribute('msg', $e->getLine().':'.$e->getMessage());

		$rtn = $response->asXML();
		echo $rtn;
	}

	if (!strstr($rtn, '취소 작업이 없습니다.'))
	{
		_debug(basename(__FILE__), $rtn.' '.$db->last_query);
	}
}

class TaskException extends Exception
{
	private $task_id;

	function __construct($msg, $task_id)
	{
		$this->task_id = $task_id;

		parent::__construct($msg);
	}

	function getTaskId()
	{
		return $this->task_id;
	}

}


?>
