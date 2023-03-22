<?php 
require_once("ui_functions.php");
require_once($_SERVER['DOCUMENT_ROOT']."/lib/config.php");
//$system_id = $_POST['id'];
//$system_id = $_GET['id'];
$system_id = $_REQUEST['id'];
/* output 구조. json으로 encode해서 넘겨주게 됨
  
  $output = array(
  				array(
  					drive => $rows['hdd_name'].' ('.$rows['drive_letter'].')',
  					used => round($rows['used_size']/1024/1024/1024),
  					total => round($rows['total_size']/1024/1024/1024),
  					percent => $rows['available_percentage']
		        ),
		        array(
  					drive => $rows['hdd_name'].' ('.$rows['drive_letter'].')',
  					used => round($rows['used_size']/1024/1024/1024),
  					total => round($rows['total_size']/1024/1024/1024),
  					percent => $rows['available_percentage']
		        ),
		        ...
		    );
*/



//현재 실행중인 작업목록 테이블
$query =  'select system_info_id, drive_letter, hdd_name, total_size, used_size, available_percentage, get_date
			from bc_system_hdd_used bshu
  				join bc_system_info_hdd bsih
    				on bshu.system_info_hdd_id = bsih.id
			where bshu.get_date > (select max(get_date)-'.(TIMECHECK/10 - 1).' from bc_system_hdd_used)
  				and system_info_id='.$system_id;

$stmt = $db->queryAll($query);

$output = array();

foreach($stmt as $rows)
{
	$delayed = intval(strtotime(date('YmdHis')) - strtotime($rows['get_date']));		
	if($delayed < TIMECHECK)
	{
		$data = array(
					drive => $rows['hdd_name'].' ('.$rows['drive_letter'].':)',
  					total => byteSizeCalc($rows['total_size']),
  					remain => byteSizeCalc($rows['total_size']-$rows['used_size']),
  					percent => $rows['available_percentage'].' %'
			    );
		array_push($output, $data);
	}	    
}

$json = array(
	'success' => true,
	'details' => $output
);

echo json_encode($json);

function byteSizeCalc($size)
{	//byte단위 정보가 들어가야함
	if($size > pow(2,40))
	{
		return round($size / pow(2,40), 2).' TB';
	}
	else if($size > pow(2, 30))
	{
		return round($size / pow(2,30), 2).' GB';
	}
	else if($size > pow(2, 20))
	{
		return round($size / pow(2,20), 2).' MB';
	}
	else if($size > pow(2, 10))
	{
		return round($size / pow(2,10), 2).' KB';
	}
	else
	{
		return $size.' Byte';
	}
}
//echo '<font size=200>'.$id.'</font>';
?>