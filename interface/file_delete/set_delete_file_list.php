<?php
//////////////////////////
//  xml 요청리스트를 받아
//  삭제 / 폐기  관련 업데이트
//
// 수정일자
//   2011. 12 . 09  by허광회
//////////////////////////

require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/db.php');

//file_put_contents(LOG_PATH.'/set_list_'.date('Ymd').'.log', date("Y-m-d H:i:s\t")."ccccc \r\n".print_r($_POST, true)."\r\n", FILE_APPEND);

/* config 파일에 정의되어있음

//Content 관련 삭제 상태
define('CONTENT_STATUS_REG_READY',		0); //등록 대기
define('CONTENT_STATUS_REVIEW_READY',	1); //심의 대기
define('CONTENT_STATUS_REG_COMPLETE',	2); //등록 완료 : 기술 심의, 내용 심의 대상에 따라 둘 다 대상이면 둘 다 완료 되어야 등록 완료로 변경된다.
define('CONTENT_STATUS_DELETE_REQUEST',	3); //삭제 요청 :  사용자 요청으로 상태값이 변경된다.
define('CONTENT_STATUS_DELETE_EXPIRE',  4); //삭제 요청 :  기한만료가 되면 상태값이 변경된다.
define('CONTENT_STATUS_DELETE_APPROVE', 5); //삭제 승인 : 콘텐츠의 기한만료나 사용자 요청으로 삭제 요청을 승락하면 이값으로 변경된다.
define('CONTENT_STATUS_DELETE_COMPLETE',6); //삭제 완료 : 콘텐츠의 관리자의 승인으로 삭제 완료된 상태값

//Media 관련 삭제 상태
define('DEL_MEDIA_COMPLETE_FLAG','DMC'); //미디어 파일 삭제 완료 상태
define('DEL_MEDIA_ERROR_FLAG','DME'); // 미디어 파일 에러 상태
define('DEL_MEDIA_REQUEST_FLAG','DMR'); //미디어 파일 사용자 요청 상태
define('DEL_MEDIA_DATE_EXPIRE_FLAG','DME'); //미디어 파일 만료 상태
define('DEL_MEDIA_CONTENT_REQUEST_FLAG','DCR'); // 콘텐츠의 삭제 요청으로인한 미디어 삭제요청상태
define('DEL_MEDIA_CONTENT_EXPIRE_FLAG','DCE'); // 콘텐츠의 기한만료로 인한 미디어 삭제요청상태

//다음날 삭제 처리할 파일 FLAG형태
define('DEL_MEDIA_AUTO_APPROVE_FLAG','DAA'); // 콘텐츠 자동승인 상태
define('DEL_MEDIA_ADMIN_APPROVE_FLAG','DMA'); //미디어 파일 관라자 승인 상태
*/

try{

	$receive_xml = urldecode(file_get_contents('php://input'));
	//$receive_xml = file_get_contents('test.xml');

	file_put_contents(LOG_PATH.'/set_delete_file_list_'.date('Ymd').'.log', date("Y-m-d H:i:s\t")."ccccc \r\n".$receive_xml."\r\n", FILE_APPEND);

	if (empty($receive_xml) )
	{
		throw new Exception('요청값이 없습니다.');
	}

//	libxml_use_internal_errors(true);
	$xml = simplexml_load_string($receive_xml);

	if (!$xml)
	{
		throw new Exception(libxml_get_last_error()->message);
	}

	$request = $xml->files;
	foreach($request as $delete_list)
	{
		file_put_contents(LOG_PATH.'/set_delete_file_list_'.date('Ymd').'.log', date("Y-m-d H:i:s\t")."ccccc \r\n".print_r($item, true)."\r\n", FILE_APPEND);
		foreach($delete_list as $item)
		{
			$msg_type       = $item['msg'];
			$msg   	        = $item['hmsg'];
			$media_type	    = strtoupper((string)$item['media_type']);
			$media_id		= $item['media_id'];
			$flag			= $item['flag'];
			$status			= $item['status'];
			$cur_date = date('YmdHis');

			//if(strcmp(trim($status),"true"))
			if( ( $status == "true" && $media_type != 'CATALOG') || ($status == "false" && in_array($msg_type, array(2,3)) && $media_type != 'CATALOG') )
			{
				$media_flag = trim(DEL_MEDIA_COMPLETE_FLAG);

				//삭제완료로 바꿈

				//폴더 삭제시 콘텐츠의 상태값 업데이트
				$query = "
				update	bc_media
				set		delete_status = '".$media_flag."', status = 1
				where	media_id = ".$media_id."
				";

				$query_update_content_status = "
					UPDATE	BC_CONTENT SET
								STATUS = '".CONTENT_STATUS_DELETE_COMPLETE."',
								IS_DELETED = 'Y'
					WHERE	CONTENT_ID = (SELECT CONTENT_ID FROM BC_MEDIA WHERE MEDIA_ID = '".$media_id."')
				";



				file_put_contents(LOG_PATH.'/set_delete_file_list_'.date('Ymd').'.log', date("Y-m-d H:i:s\t")." - query \r\n".$query."\r\n", FILE_APPEND);
				file_put_contents(LOG_PATH.'/set_delete_file_list_query_'.date('Ymd').'.log', date("Y-m-d H:i:s\t")." - query \r\n".$query_update_content_status."\r\n", FILE_APPEND);

				if(!$db->exec($query))
				{
					$error_msg = "\n [Error >> Dete ". date("Y-m-d H:i:s\t").'media_id :'.$media_id. 'msg_type : Query Error  msg : '.$msg.'] \n';
					file_put_contents(LOG_PATH.'/set_delete_file_list_'.date('Ymd').'.log', date("Y-m-d H:i:s\t")." - True_Error \r\n".$receive_xml."\r\n", FILE_APPEND);
				}

				if(!$db->exec($query_update_content_status))
				{
					$error_msg = "\n [Error >> Dete ". date("Y-m-d H:i:s\t").'bc_content update error media_id :'.$media_id. 'msg_type : Query Error  msg : '.$msg.'] \n';
					file_put_contents(LOG_PATH.'/set_delete_file_list_'.date('Ymd').'.log', date("Y-m-d H:i:s\t")." - True_Error \r\n".$receive_xml."\r\n", FILE_APPEND);
				}
			}else{//DEL_MEDIA_ERROR_FLAG

				if($media_type != 'CATALOG')
				{
					file_put_contents(LOG_PATH.'/set_delete_file_list_else_'.date('Ymd').'.log', date("Y-m-d H:i:s\t")." - True_Error \r\n".$receive_xml."\r\n", FILE_APPEND);
				}

				//$media_flag = trim(DEL_MEDIA_ERROR_FLAG);

				////삭제완료로 바꿈

				////폴더 삭제시 콘텐츠의 상태값 업데이트
				//$query = "
				//update	bc_media
				//set		delete_status = '".$media_flag."', status = 1
				//where	media_id = ".$media_id."
				//";

				//$query_update_content_status = "
					//UPDATE	SET	BC_CONTENT
								//STATUS = '".CONTENT_STATUS_DELETE_COMPLETE."',
								//IS_DELETED = 'Y'
					//WHERE	CONTENT_ID = (SELECT CONTENT_ID FROM BC_MEDIA WHERE MEDIA_ID = '".$media_id."')
				//";



				//file_put_contents(LOG_PATH.'/set_delete_file_list_'.date('Ymd').'.log', date("Y-m-d H:i:s\t")." - query \r\n".$query."\r\n", FILE_APPEND);

				//if(!$db->exec($query))
				//{
					//$error_msg = "\n [Error >> Dete ". date("Y-m-d H:i:s\t").'media_id :'.$media_id. 'msg_type : Query Error  msg : '.$msg.'] \n';
					//file_put_contents(LOG_PATH.'/set_delete_file_list_'.date('Ymd').'.log', date("Y-m-d H:i:s\t")." - True_Error \r\n".$receive_xml."\r\n", FILE_APPEND);
				//}

				//if(!$db->exec($query_update_content_status))
				//{
					//$error_msg = "\n [Error >> Dete ". date("Y-m-d H:i:s\t").'bc_content update error media_id :'.$media_id. 'msg_type : Query Error  msg : '.$msg.'] \n';
					//file_put_contents(LOG_PATH.'/set_delete_file_list_'.date('Ymd').'.log', date("Y-m-d H:i:s\t")." - True_Error \r\n".$receive_xml."\r\n", FILE_APPEND);
				//}
			}

		}
	}

	$response = new SimpleXMLElement("<response><result /></response>");
	$response->result->addAttribute('success', 'true');
	echo $response->asXML();

}catch (Exception $e)
{
	$response = new SimpleXMLElement("<response><result /></response>");
	$response->result->addAttribute('success', 'false');
	$response->result->addAttribute('message', $e->getMessage());
	$response->result->addAttribute('query', $db->last_query);

	echo $response->asXML();
}
?>