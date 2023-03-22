<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

$receive_xml = file_get_contents('php://input');

file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/sys_monitor'.date('Ymd').'.html', date("Y-m-d H:i:s\t").$receive_xml."\n\n", FILE_APPEND);

$response = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>'.chr(10).'<response />');

if ( empty($receive_xml) )
{
	$error = $response->addChild('result');
	$error->addAttribute('success', 'false');
	$error->addAttribute('msg', '요청 값이 없습니다.');
	$returntime = $error->addChild('returntime', get_interval_time());
	$datetime = $error->addChild('datetime', date('YmdHis'));
	die($response->asXML());
}

$xml = reConvertSpecialChar(reConvertSpecialChar($receive_xml));

libxml_use_internal_errors(true);
$xml = simplexml_load_string($receive_xml);



if ( !$xml )
{
	foreach ( libxml_get_errors() as $error )
	{
		$err_msg .= $error->message . "\t";
	}
	$result = $response->addChild('result');
	$result->addAttribute('success', 'false');
	$result->addChild('msg', 'xml 파싱 에러: '.$err_msg);
	$result->addChild('returntime', get_interval_time());
	$result->addChild('datetime', date('YmdHis'));

	die($response->asXML());
}

try
{

	$cur_time = date('YmdHis');
	$request_ip = (string)$xml->status->ip;
	$os = (string)$xml->status->os;
	$host_name = (string)$xml->status->host_name;
	$com_name = (string)$xml->status->computer_name;

	////////////////////////////////
	//승수: cpu사용량이 음수일때 0으로 처리
	////////////////////////////////
	$cpu_use = (string)$xml->status->cpu;
	if($cpu_use < 0)
	{
		$cpu_use = 0;
	}

	$cpu_count = $xml->status->cpu['count'];
	$memory_use = (string)$xml->status->memory;
	//////////////////////////////////////////////////////////////////////////
	//추가내용_승수: DB의 BC_SYSTEM_INFO테이블에 총 메모리량 정보 필드 (MEMORY_SIZE) 추가.
	//				그 부분에 저장할 변수 $memory_size
	//////////////////////////////////////////////////////////////////////////
	$memory_size = (int)$xml->status->memory['total'];

	$hdd_info = $xml->status->hdd;
	$process_info = $xml->status->processes;
	$get_date = (string)$xml->status->datetime;


//print_r($hdd_info);
//exit;

	//////////////////////////////////////
	//추가내용_승수: ip값이 정상적인 값이 아닐 경우
	//////////////////////////////////////
	if(!preg_match('/^[0-9]{1,3}.[0-9]{1,3}.[0-9]{1,3}.[0-9]{1,3}$/', $request_ip))
	{
		$error = $response->addChild('result');
		$error->addAttribute('success', 'false');
		$error->addAttribute('msg', 'IP주소 형식이 다릅니다.');
		$returntime = $error->addChild('returntime', get_interval_time());
		$datetime = $error->addChild('datetime', date('YmdHis'));
		die($response->asXML());
	}



	$check_ip = $db->queryOne("select id from bc_system_info where ip_add = '$request_ip'");

	//ip를 체크하여 디비에 없을시 시스템을 추가하고 있을시는 프로세스사용내역등을 추가하고 시스템인포 테이블값을 업데이트한다..
	if(!empty($check_ip)) // 등록된 아이피 일 경우 값들을 업데이트.
	{

		/////////////////////
		//승수: memory_size추가
		$update_bc_system_info = "update bc_system_info set os = '$os', host_name = '$host_name', com_name = '$com_name', cpu_count= '$cpu_count', memory_size= '$memory_size' where id = '$check_ip'";
		$r = $db->exec($update_bc_system_info);
		//echo $update_bc_system_info."\n\r";

		//system_info_hdd 데이터 업데이트 및 하드 사용률 인서트

		foreach($hdd_info as $hdd)
		{

			foreach($hdd as $hd)
			{

				$free_size = $hd;
				$drive = $hd['drive'];
				$hdd_name = $hd['name'];
				$total = $hd['total'];
				$used = $hd['used'];

				$system_hdd_used_id = getNextSystemHddUsedId();
				$system_info_hdd_id = $db->queryOne("select id from bc_system_info_hdd where system_info_id = '$check_ip' and drive_letter = '$drive'");
				//없을경우 새로운 하드 정보를 추가 해야함..

				if(empty($system_info_hdd_id))
				{
					$system_info_hdd_id = getNextSystemInfoHddId();
					$insert_bc_info_hdd = "insert into bc_system_info_hdd (id, drive_letter, hdd_name, total_size, system_info_id)
											values
												('$system_info_hdd_id', '$drive', '$hdd_name', '$total', '$check_ip')";
					$r = $db->exec($insert_bc_info_hdd);
					if($r){
						logMsg('하드정보 추가됨', __LINE__);
					}
				}
		//echo $db->last_query."\n\r";

				$update_bc_system_info_hdd = "update bc_system_info_hdd set hdd_name = '$hdd_name', total_size = '$total' where drive_letter = '$drive' and system_info_id = '$check_ip'";
				$r = $db->exec($update_bc_system_info_hdd);
		//echo $update_bc_system_info_hdd."\n\r";

				$insert_bc_hdd_used = "insert into bc_system_hdd_used (id, used_size, available_percentage, get_date, system_info_hdd_id)
												values
													('$system_hdd_used_id', '$used', '$free_size', '$cur_time', '$system_info_hdd_id')";
				$r = $db->exec($insert_bc_hdd_used);
		//echo $insert_bc_hdd_used."\n\r";
				$cur_drive .= "'".$drive."', ";
			}
		}

		$cur_drives = rtrim(trim($cur_drive), ',');
//echo $cur_drives;
		//업데이트/인서트후 변경된 드라이브정보는 삭제.
		// 자식 테이블 부터 삭제. --22일 삭제가 안되고 있슴.->완료함
		$hdd_ids = $db->queryAll("select id from bc_system_info_hdd where system_info_id = '$check_ip' and drive_letter not in (".$cur_drives.") ");
//echo $db->last_query;

		foreach($hdd_ids as $hdd_id)
		{
			$ids .= "'".$hdd_id['id']."',";
		}

		$ids = rtrim($ids, ',');

		if(!empty($ids))
		{

			$del_used_drive = "delete from bc_system_hdd_used where system_info_hdd_id in (".$ids.")";
			$r = $db->exec($del_used_drive);

			$del_drive = "delete from bc_system_info_hdd where system_info_id = '$check_ip' and id in (".$ids.")";
			$r = $db->exec($del_drive);
		}
		//echo $del_drive."\n\r";

		 // process 정보 인서트
		foreach($process_info as $processes)
		{
			foreach($processes as $process)
			{
				$system_info_process_id = getNextSystemProcessId();
				$p_name = $process['name'];
				$pid = $process['pid'];

				$insert_sys_process = "insert into bc_system_info_process (id, process_name, pid, get_date, system_info_id)
												values
													('$system_info_process_id', '$p_name', '$pid', '$cur_time', '$check_ip')";
				$r = $db->exec($insert_sys_process);
		//echo $insert_sys_process."\n\r";
			}
		}


		$system_info_process_id = getNextSystemProcessId();
		//프로세스 사용량 정보 인서트
		$insert_process_used = "insert into bc_system_process_used (id, cpu_used, memory_used, get_date, system_info_id)
											values
												('$system_info_process_id', '$cpu_use', '$memory_use', '$cur_time', '$check_ip')";
		$r = $db->exec($insert_process_used);
		//echo $insert_process_used."\n\r";
	}
	else
	{

		$system_info_id = getNextSystemInfoId();

		///////////////////////
		//승수 : memory_size 추가
		///////////////////////
		$insert_bc_system_info = "insert into bc_system_info (id, os, ip_add, host_name, com_name, cpu_count, memory_size)
											values
												('$system_info_id', '$os', '$request_ip', '$host_name', '$com_name', '$cpu_count', '$memory_size')";
		//echo $insert_bc_system_info."\n\r";
		$r = $db->exec($insert_bc_system_info);
		//echo $db->last_query."\n\r";

		//시스템 하드별 정보 및 사용량 추가
		foreach($hdd_info as $hdd)
		{
			foreach($hdd as $hd)
			{
				$system_info_hdd_id = getNextSystemInfoHddId();
				$system_hdd_used_id = getNextSystemHddUsedId();

				$free_size = $hd;
				$drive = $hd['drive'];
				$hdd_name = $hd['name'];
				$total = $hd['total'];
				$used = $hd['used'];

				$insert_bc_hdd_info = "insert into bc_system_info_hdd (id, drive_letter, hdd_name, total_size, system_info_id)
											values
												('$system_info_hdd_id', '$drive', '$hdd_name', '$total', '$system_info_id')";
				$r = $db->exec($insert_bc_hdd_info);

				$insert_bc_hdd_used = "insert into bc_system_hdd_used (id, used_size, available_percentage, get_date, system_info_hdd_id)
												values
													('$system_hdd_used_id', '$used', '$free_size', '$cur_time', '$system_info_hdd_id')";
				$r = $db->exec($insert_bc_hdd_used);
		//echo $insert_bc_hdd_info."\n\r";
		//echo $insert_bc_hdd_used."\n\r";
			}
		}

		 // process 정보 인서트
		foreach($process_info as $processes)
		{
			foreach($processes as $process)
			{
				$system_info_process_id = getNextSystemProcessId();
				$p_name = $process['name'];
				$pid = $process['pid'];

				$insert_sys_process = "insert into bc_system_info_process (id, process_name, pid, get_date, system_info_id)
												values
													('$system_info_process_id', '$p_name', '$pid', '$cur_time', '$system_info_id')";
				$r = $db->exec($insert_sys_process);
		//echo $insert_sys_process."\n\r";
			}
		}

		//프로세스 사용량 정보 인서트
		$system_info_process_id = getNextSystemProcessId();
		//echo '???id: ' . $system_info_id . ', cpu_used: ' . $cpu_use . ', ';
		$insert_process_used = "insert into bc_system_process_used (id, cpu_used, memory_used, get_date, system_info_id)
											values
												('$system_info_process_id', '$cpu_use', '$memory_use', '$cur_time', '$system_info_id')";
		$r = $db->exec($insert_process_used);
	}

//exit;
	$regist = $response->addChild('result');
		$regist->addAttribute('success', 'true');
		$regist->addAttribute('msg', '');
		$returntime = $regist->addChild('returntime',get_interval_time());
		$datetime = $regist->addChild('datetime', date('YmdHis'));
		
		die($response->asXML());

}
catch ( Exception $e )
{
	$msg = $e->getMessage();
	switch ( $e->getCode() )
	{
		case ERROR_QUERY:
			$msg .= $db->last_query;
		break;
	}

	$error = $response->addChild('result');
	$error->addAttribute('success', 'false');
	$error->addAttribute('msg', $msg);
	$returntime = $error->addChild('returntime', get_interval_time());
	$datetime = $error->addChild('datetime', date('YmdHis'));
	die($response->asXML());

}

function _p($m)
{
	echo iconv('utf-8', 'euc-kr', $m) . "\n";

}


function getNextSystemInfoId()
{
	return getSequence('SEQ_BC_SYSTEM_INFO_ID');
	//global $db;

	//return $db->queryOne('select SEQ_BC_SYSTEM_INFO_ID.nextval from dual');
}

function getNextSystemInfoHddId()
{
	return getSequence('SEQ_BC_SYSTEM_INFO_HDD_ID');
	//global $db;

	//return $db->queryOne('select SEQ_BC_SYSTEM_INFO_HDD_ID.nextval from dual');
}

function getNextSystemHddUsedId()
{
	return getSequence('SEQ_BC_SYSTEM_HDD_USED_ID');
	//global $db;

	//return $db->queryOne('select SEQ_BC_SYSTEM_HDD_USED_ID.nextval from dual');
}

function getNextSystemProcessId()
{
	return getSequence('SEQ_BC_SYSTEM_INFO_PROCESS_ID');
	//global $db;

	//return $db->queryOne('select SEQ_BC_SYSTEM_INFO_PROCESS_ID.nextval from dual');
}

function logMsg($msg, $line)
{
	file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/Log'.date('Ymd').'.html', "[" . date("Y-m-d H:i:s\t"). "] -  " . $msg . "[ Line : " . $line . "] \n<br>"  , FILE_APPEND);
}

function get_interval_time(){
	global $db;
	return $db->queryOne("SELECT value FROM BC_HTML_CONFIG WHERE type='monitor_interval'");
}
?>