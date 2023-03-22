<?php 
require_once("ui_functions.php");
require_once($_SERVER['DOCUMENT_ROOT']."/lib/config.php");
//이 시간동안 새로운 정보가 안들어오면 연결이 끊어진것으로 판단
define('TIMECHECK', 10);


	
//모든 정보를 가져오기
$query1 = 'select id from bc_system_info';
$stmt1 = $db->queryAll($query1);

/* output 구조. json으로 encode해서 넘겨주게 됨
  $output = array(array(
 						 id => $server_id 
  						 url => $cpu_used, $memory _used 이용한 조건식
						 name => $server_name 
				  ),
			  	  array( 
			  			 id => $server_id
  						 url => $cpu_used, $memory _used 이용한 조건식
						 name => $server_name 
				  ),
					...
			)		
*/
  
$output = array();

foreach($stmt1 as $rows1)
{
	$server_id = $rows1['id'];
	
	//서버 id에 따른 서버이름
	$query2 = 'select com_name from bc_system_info where id='.$server_id;
	$stmt2 = $db->queryAll($query2);
	foreach($stmt2 as $rows2)
	{
		$server_name = $rows2['com_name'];
	}
	
	
	//서버 id에 따른 cpu, memory 사용량 정보. 최근 하나만
	$query3 = ' select cpu_used, memory_used, get_date 
				from bc_system_process_used
				where system_info_id='.$server_id.' order by get_date desc';
	$stmt3 = $db->queryRow($query3);
	$cpu_used = $stmt3['cpu_used'];
	
	
	$memory_used = $stmt3['memory_used'];
	//echo 'cpu: '.$cpu_used . ', memory: '.$memory_used.', ';
	
	//사용량 정도에 따라 보통, 경고, 위험 으로 나눠서 그림 표시
	if($cpu_used > 85 || $memory_used > 85)
	{
		$changing_url = '/store/sys_monitor/images/server_orange.png';
		if($cpu_used > 90 || $memory_used > 90)
		{
			$changing_url = '/store/sys_monitor/images/server_red.png';
		}
	}
	else
	{
		$changing_url = '/store/sys_monitor/images/server_green.png';
	}

//연결이 끊어졌는지 확인하는 과정
	$delayed = intval(strtotime(date('YmdHis')) - strtotime($stmt3['get_date']));		
	if($delayed > TIMECHECK)
	{
		//끊어진 상태는 회색으로
		$changing_url = '/store/sys_monitor/images/server_gray.png';
	}
	
	
	$subArray = array(
					'id' => $server_id,
	  				'url' => $changing_url,
					'name' => $server_name
					);
	
	array_push($output, $subArray);
	
}
$json = array(
	'success' => true,
	'images' => $output
);

echo json_encode($json);	

?>