<?php 
require_once("ui_functions.php");
require_once($_SERVER['DOCUMENT_ROOT']."/lib/config.php");
require_once($_SERVER['DOCUMENT_ROOT']."/lib/functions.php");
//$system_id = $_POST['id'];
//$system_id = $_GET['id'];
$system_id = $_REQUEST['id'];
$tab_num = $_REQUEST['tab'];
/* output 구조. json으로 encode해서 넘겨주게 됨
  == tab 1 cpu, memory 사용량 높은것 == 
  $output = array(
  				array(
  					time => 
  					cpu =>
  					memory =>
		        ),
		        ...
		    );
  == tab 2 프로세스 시작/종료 시간 == 
  $output = array(
  				array(
  					name => 
  					pid =>
  					start =>
  					end =>
		        ),
		        ...
		    );
*/

if($tab_num == 1)
{
	$query1 =  'select cpu_used, memory_used, get_date
	from bc_system_process_used
	where system_info_id = '.$system_id.'
	  and (cpu_used > 70 or memory_used > 78)';
		
	$stmt1 = $db->queryAll($query1);
	
	$output = array();
	
	foreach($stmt1 as $rows1)
	{				
		$data = array(
					time => date("Y/m/d h:i:s", strtotime($rows1['get_date'])),
  					cpu => $rows1['cpu_used'],
  					memory => $rows1['memory_used']  					
			    );
		array_push($output, $data);		
	}
	
	echo createExcelFile('asdf', $output);	
	
}
else if($tab_num == 2)
{
	$query2 = 'select distinct process_name from bc_system_info_process where system_info_id ='.$system_id;
		
	$stmt2 = $db->queryAll($query2);
	
	$output = array();
	
	foreach($stmt2 as $rows2)
	{			
		$query3 = "select pid, max(get_date), min(get_date)
	from bc_system_info_process 
	where process_name = '".$rows2['process_name']."'
	  and system_info_id = ".$system_id."
	  group by pid
	  order by min(get_date) asc";
		$stmt3 = $db->queryAll($query3);
		foreach($stmt3 as $rows3)
		{
			if(strtotime(date('YmdHis')) - strtotime($rows3['max(get_date)']) < 100)
			{
				
				$data = array(
							name => $rows2['process_name'],
			  				pid => $rows3['pid'],
			  				start => date("Y/m/d h:i:s", strtotime($rows3['min(get_date)'])),
			  				end => '-'
					    );		
			}
			else
			{		
				$data = array(
							name => $rows2['process_name'],
		  					pid => $rows3['pid'],
		  					start => date("Y/m/d h:i:s", strtotime($rows3['min(get_date)'])),
		  					end => date("Y/m/d h:i:s", strtotime($rows3['max(get_date)']))
					    );			
			}
			array_push($output, $data);
		}		
	}
	
	echo createExcelFile('asdf', $output);
}
else{
	echo "tab_num 오류";
}
?>