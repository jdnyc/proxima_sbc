<?php 
require_once($_SERVER['DOCUMENT_ROOT']."/lib/config.php");
require_once("ui_functions.php");

$system_id = $_POST['id'];
//$system_id = $_GET['id'];

/* output 구조. json으로 encode해서 넘겨주게 됨
  
  $output = array(
  				array(
  					xfield => '',
  					cpu => $cpu,
  					memory => $memory
		        ),
		        array(
		        	xfield => '',
  					cpu => $type,
  					memory => $contents
		        ),
		        ...
		    );				
*/


//차트에 나타낼 최근 cpu와 memory사용량
$query1 =  'select cpu_used, memory_used, rownum, get_date 
			from (select cpu_used, memory_used, get_date 
				  from bc_system_process_used
				  where system_info_id='.$system_id.' 
					 order by get_date desc)
			where rownum between 1 and 1';
$query2 =  'select cpu_used, memory_used, rownum, get_date 
			from (select cpu_used, memory_used, get_date 
				  from bc_system_process_used
				  where system_info_id='.$system_id.' 
					 order by get_date desc)
			where rownum between 1 and 30
				order by get_date asc';
$query3 =  'select memory_size
			from bc_system_info
			where id='.$system_id;		
$stmt1 = $db->queryRow($query1);
$stmt2 = $db->queryAll($query2);
$stmt3 = $db->queryOne($query3);

$cpu = array();
$memory = array();
$interval = array();

$output = array();
$delayed = intval(strtotime(date('YmdHis')) - strtotime($stmt1['get_date']));		

if($delayed < TIMECHECK)
{
	foreach($stmt2 as $rows2)
	{					
		$sub = array(
					xfield => $rows2['rownum'],
  					cpu => $rows2['cpu_used'],
  					memory => $rows2['memory_used'],
  					date => dateToTime($rows2['get_date']),   					
  					memory_mb => round(intval($stmt3)*intval($rows2['memory_used'])/100, 1)
  				);
  		array_push($output, $sub);
	}	    
}

$json = array(
	'success' => true,
	'details' => $output
);

echo json_encode($json);

function dateToTime($date)
{
	$H = substr($date, 8, 2);
	$m = substr($date, 10,2);
	$s = substr($date, 12,2);
	return "$H:$m:$s";
}
//echo '<font size=200>'.$id.'</font>';
?>