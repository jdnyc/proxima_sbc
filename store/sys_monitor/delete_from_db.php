<?php 
require_once($_SERVER['DOCUMENT_ROOT']."/lib/config.php");
require_once("ui_functions.php");

$system_id = $_POST['id'];
//$system_id = $_GET['id'];



//현재 실행중인 작업목록 테이블
$query1 =  'delete from (select * 
						from bc_system_hdd_used bshu,
							 (select bsih.id
							  from bc_system_info_hdd bsih
							  where bsih.system_info_id = 143) bsih
						where bshu.system_info_hdd_id = bsih.id);
			delete from bc_system_info_process where system_info_id in ('.$system_id.');
			delete from bc_system_process_used where system_info_id='.$system_id;
$stmt1 = $db->queryAll($query1);

?>