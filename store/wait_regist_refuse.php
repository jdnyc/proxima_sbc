<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

$content_id = $_POST['id'];
$user_id = $_SESSION['user']['user_id'];
$created_time =date('YmdHis');
$action = 'refuse'; //로그에 들어감
$action_list =  $db->escape($_POST['refuse_list']);//반려 목록 refuse_meta, refuse_encoding
$description = $db->escape($_POST['description']);//반려 세부내용

$rejuse = CONTENT_STATUS_REFUSE; //-5으로 정의 2011-01-25 by 이성용

try
{
	//작성자: 박정근
	//작성일: 2011-03-10
	//내용: 일괄 작업 적용
	$content_list = explode(',', $_POST['id']);
	foreach ($content_list as $content_id)
	{
		$nps_check = $db->queryRow("select nps_content_id, user_id from content where content_id='$content_id'");

		if( !empty($nps_check['nps_content_id']) ) //nps 에서 넘어왔는지 체크
		{
			$target_user_id = $nps_check['user_id'];
		}
		else
		{
			//////////반려 리스트 등록/////////

			if($action_list == 'refuse_meta') //최초 메타데이터 작업자 찾기
			{
				$meta_user = $db->queryRow("select user_id from log where action='edit' and link_table_id='$content_id' order by id asc");

				if( empty($meta_user) )
				{
					throw new Exception('최초 메타데이터 작업자가 존재하지 않습니다. ', -1);
				}
				else
				{
					$target_user_id = $meta_user['user_id'];
				}
			}
			else if($action_list == 'refuse_encoding') //인코딩 작업자 찾기
			{
				$query  = "select mv.value from content c, meta_field mf, meta_value mv where c.content_id='$content_id' and c.content_id=mv.content_id and mf.meta_field_id=mv.meta_field_id and mf.name like '%인코딩 작업자%' ";
				$encoding_user = $db->queryOne($query);
				if( empty($encoding_user) )
				{
					throw new Exception('인코딩 작업자가 존재하지 않습니다. ', -1);
				}
				else
				{
					$target_user = $db->queryRow("select user_id from member where name like '%$encoding_user%' ");
					if( empty($target_user) )
					{
						throw new Exception('알수 없는 작업자 입니다.', -1);
					}
					else
					{
						$target_user_id = $target_user['user_id'];
					}
				}
			}
		}

		$refuse_id = getNextSequence();

		$refuse_regist = $db->exec("insert into refuse_list (ID, ACTION, USER_ID, TARGET_USER_ID, CONTENT_ID, CREATED_TIME, DESCRIPTION) values ('$refuse_id','$action_list','$user_id', '$target_user_id' ,'$content_id','$created_time','$description')");

		////////////콘텐츠 status , CONTENT_STATUS_REFUSE= -5 으로 변경
		$regist_update_query = $db->exec("update content set status= '$rejuse' where content_id='$content_id'");

		//////////////반려 로그 남김////////////

		$log_id = getNextSequence();
		$des = $db->escape($action_list.':'.$description);

		//로그에 콘텐츠타입,메타타입 추가 by 이성용 2011-03-25
		$content_data = $db->queryRow("select * from content where content_id='$content_id' ");

		$content_type_id=$content_data['content_type_id'];
		$meta_table_id=$content_data['meta_table_id'];

		$log_regist = $db->exec("insert into log (id, action, user_id, link_table, link_table_meta, link_table_id, created_time, description) values ('$log_id','$action','$user_id','$content_type_id' ,'$meta_table_id' ,'$content_id','$created_time','$des')");
	}




	$data = array(
		'success'	=> true
	);

	echo json_encode($data);
}
catch (Exception $e)
{
	echo '오류 : '.$e->getMessage().' - '.$db->last_query;
}
?>