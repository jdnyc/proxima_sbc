<?php 
require_once("ui_functions.php");
require_once($_SERVER['DOCUMENT_ROOT']."/lib/config.php");
$system_id = $_POST['id'];
//$first_process = $_POST['first'];
//$system_id = $_GET['id'];

/* output 구조. json으로 encode해서 넘겨주게 됨
  
  $output = array(
  				array(
  					task => $task,
  					date => $date
		        ),
		        array(
  					task => $task,
  					date => $date
		        ),
		        ...
		    );
*/




//현재 실행중인 작업목록 테이블
$query3 =  'select process_name, pid, get_date 
			from bc_system_info_process
			where system_info_id='.$system_id.' 
  				and get_date =  (select max(get_date)
  								from bc_system_info_process
                  				where system_info_id='.$system_id.')
				order by get_date desc';	

$stmt3 = $db->queryAll($query3);

$output = array();

foreach($stmt3 as $rows3)
{	
	$delayed = intval(strtotime(date('YmdHis')) - strtotime($rows3['get_date']));		
	if($delayed < TIMECHECK)
	{
		$subquery = 'select min(get_date) from bc_system_info_process bsip
					 where bsip.pid = '.$rows3['pid'].'
  						and bsip.system_info_id='.$system_id;
		$substmt = $db->queryOne($subquery);
		$during = secToDate(strtotime($rows3['get_date']) - strtotime($substmt));
		
		$data = array(
						num => '',
	  					task => $rows3['process_name'],
	  					pid => $rows3['pid'],
	  					//date => $rows3['get_date']
						date => date("Y/m/d h:i:s", strtotime($rows3['get_date'])),
						during => $during
			    );
		array_push($output, $data);
	}	    
}
//주요 파일이 가장 위에 오도록 하기 위해
$output = sort_data($output);

//순번 매기는 부분. sort_data 후에 매겨준다
for($i=0; $i < count($output); $i++)
{
	$output[$i]['num'] = $i+1;
}


$json = array(
	'success' => true,
	'details' => $output
);

echo json_encode($json);


function sort_data($output)
{
	$array = array();
	foreach($output as $out1)
	{
		if($out1['task'] == "PCMS.exe")//$first_process
		{
			array_unshift($array, $out1);
			continue;
		}
		array_push($array, $out1);	
	}
	return $array;
}

function secToDate($sec)
{
	//$num/60/60/24/
	//	      분   시   일
	$d = floor($sec/(60*60*24));
	$h = floor(($sec%(60*60*24))/(60*60));
	$i = floor((($sec%(60*60*24))%(60*60))/(60));
	$s = (($sec%(60*60*24))%(60*60))%(60);
	return $d.'일 '.$h.'시간 '.$i.'분 '.$s.'초';
}
//echo '<font size=200>'.$id.'</font>';
?>