<?php 
require_once("ui_functions.php");
require_once($_SERVER['DOCUMENT_ROOT']."/lib/config.php");
if($system_id = $_POST['id'])
{	
	
	
	/* output 구조. json으로 encode해서 넘겨주게 됨
	  
	  $output = array(
	  				array(
	  					key => $type,
	  					value => $contents
			        ),
			        array(
	  					key => $type,
	  					value => $contents
			        ),
			        ...
			    );
	*/
	//ip에 맞는 id 구하기

	
	//테이블 정보
	$query1 =  'select ip_add, host_name, com_name, os, memory_size 
				from bc_system_info
				where id='.$system_id;
	$stmt1 = $db->queryAll($query1);
	
	$query2 = 'select get_date
			  from bc_system_process_used
			  where system_info_id='.$system_id.'
					order by get_date desc';
	$stmt2 = $db->queryOne($query2);
	
	$delayed = strtotime(date('YmdHis')) - strtotime($stmt2);
		
	if($delayed < TIMECHECK)
	{
		$status = '연결 양호';
	}
	else if($delayed > (TIMECHECK * 3))
	{	//3번 체크해도 연결에 문제가 있을 경우
		$status = '연결 불량';
	}
	else
	{
		$status = '연결중..';
	}	    
	
	$output = array();
	foreach($stmt1 as $rows1)
	{	
		$output = array(
					array(
	  					key => '서버상태',
	  					value => $status
			        ),
	  				array(
	  					key => '아이피 주소',
	  					value => $rows1['ip_add']
			        ),
			        array(
	  					key => '호스트 이름',
	  					value => $rows1['host_name']
			        ),
			        array(
	  					key => '서버 이름',
	  					value => $rows1['com_name']
			        ),
			        array(
	  					key => '운영체제',
	  					value => $rows1['os']
			        ),
			        array(
	  					key => '메모리',
	  					value => (round($rows1['memory_size'], -3)/1000).' GB ('.$rows1['memory_size'].' MB)'
			        )
			        
			    );
	}
	
	$json = array(
		'success' => true,
		'details' => $output
	);
	
	echo json_encode($json);
}
else
{	//아무것도 선택 안했을 시 출력되는 부분
	$output = array(
		array(
  			key => '서버상태',
  			value => '연결대기'
        ),
  		array(
  			key => '아이피 주소',
  			value => ''
        ),
        array(
  			key => '호스트 이름',
  			value => ''
        ),
        array(
  			key => '서버 이름',
  			value => ''
        ),
        array(
  			key => '운영체제',
  			value => ''
        ),
        array(
  			key => '메모리',
  			value => ''
        )
    );
	
	$json = array(
		'success' => true,
		'details' => $output
	);
	
	echo json_encode($json);
}
//echo '<font size=200>'.$id.'</font>';
?>