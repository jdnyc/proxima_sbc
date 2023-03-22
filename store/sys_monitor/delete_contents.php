<?php 
$system_id = $_REQUEST['id'];
require_once($_SERVER['DOCUMENT_ROOT']."/lib/config.php");
require_once('ui_functions.php');

if($system_id)
{
	$query = 'select get_date
			  from BC_SYSTEM_INFO_PROCESS
			  where system_info_id='.$system_id.'
			  	order by get_date desc';
	$stmt = $db->queryOne($query);

	$delayed = strtotime(date('YmdHis')) - strtotime($stmt);		
	if($delayed > TIMECHECK)
	{
		//성공시 11111 출력
		echo $db->exec("delete from (select * from bc_system_hdd_used bshu,
									 (select bsih.id from bc_system_info_hdd bsih where bsih.system_info_id=".$system_id.") bsih
						where bshu.system_info_hdd_id=bsih.id)");
		echo $db->exec("delete from bc_system_info_hdd where system_info_id=".$system_id);
		echo $db->exec("delete from bc_system_info_process where system_info_id=".$system_id);
		echo $db->exec("delete from bc_system_process_used where system_info_id=".$system_id);
		echo $db->exec("delete from bc_system_info where id=".$system_id);
	}
	else if($delayed >= 0 && $delayed <= TIMECHECK)
	{
		echo 3;
	}
	else
	{
		echo 0;
	}
}
else
{
	echo 2;
}
?>