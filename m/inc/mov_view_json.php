<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/db.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

$content_id = $_GET['content_id'];
if(!$content_id) $content_id = $_POST['content_id'];

// $content_id = '23482340';
$meta_table_id = $_POST['meta_table_id'];
$ud_content_id = $meta_table_id;
$bs_content_id = $_POST['bs_content_id'];
$user_id = $_SESSION['user']['user_id'];
$is_admin = $_SESSION['user']['is_admin'];


try
{
	$action = 'read';
	$description = '';
	insertLog($action, $user_id, $content_id, $description);
	$content = $db->queryRow("select * from view_bc_content where content_id='$content_id'");
	$content_user_id = $content['reg_user_id'];
	$ud_content_id = $content['ud_content_id'];
	$containerList = $db->queryAll("select * from bc_usr_meta_field where ud_content_id='{$ud_content_id}' and container_id is not null and depth=0 order by show_order");
	$container_array = array();

//file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/test_'.date('Ymd').'.log', date("Y-m-d H:i:s\t")." ::xxxxxx: mov_view_json -> content :::"."\r\n".print_r($content, true)."\r\n\n", FILE_APPEND);	
//file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/test_'.date('Ymd').'.log', date("Y-m-d H:i:s\t")." ::xxxxxx: mov_view_json -> containerList :::"."\r\n".print_r($containerList, true)."\r\n\n", FILE_APPEND);	
	foreach ($containerList as $container_key => $container) {

		// 컨테이너 아이디
		$container_id_tmp = $container['container_id'];

		// 컨테이너 명
		$container_title = addslashes($container['usr_meta_field_title']);

		//$rsFields = content_meta_value_list( $content_id, $content['ud_content_id'] , $container_id_tmp );
		$rsFields =  MetaDataClass::getFieldValueforContaierInfo('usr' , $ud_content_id, $container_id_tmp, $content_id);
		
		foreach ($rsFields as $f) {
			if($f['type'] == 'listview')
			{
				$listview = getMetaMultiXML($content_id);
			}
			if ($f['default_value']){
				$f['default_value']	= str_replace('(default)', '', $f['default_value']);
			}
			array_push($container_array, $f);
		}
		//file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/test_'.date('Ymd').'.log', date("Y-m-d H:i:s\t")." ::xxxxxx: mov_view_json -> container_array :::"."\r\n".print_r($container_array, true)."\r\n\n", FILE_APPEND);	

	}
	$detail_title_value = $content[title];
	$result_data = array();
	$result_data['basic'] = $container_array;
	
	$stream_file = $db->queryOne("
									select 	path 
									from 	bc_media 
									where 	content_id = '".$content_id."' 
											and media_type='proxy' 
									order by media_id");

	//content_id, user_id, comments, seq, datetime
 	$query_comment = "
 			SELECT	b.CONTENT_ID
 					,a.*
 			FROM	BC_COMMENTS a
 					LEFT OUTER JOIN BC_CONTENT b ON(b.CONTENT_ID = a.CONTENT_ID)
 			WHERE	b.CONTENT_ID	= ".$content_id."
 			AND 	DELETE_YN = '0'
 			ORDER BY b.CONTENT_ID, a.SEQ ASC";
 	//file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/test_'.date('Ymd').'.log', date("Y-m-d H:i:s\t")." ::xxxxxx: mov_view_json -> query_comment :::"."\r\n".$query_comment."\r\n\n", FILE_APPEND);		
	$arr_output = $db->queryAll($query_comment);
	$comment_array = array();
	$query_seq = "
					SELECT 		SEQ 
					FROM 		BC_COMMENTS
					WHERE 		USER_ID = '$user_id'
					AND 		CONTENT_ID = '$content_id'
					AND 		DELETE_YN = '0'
					ORDER BY 	SEQ DESC      
				";
	$user_last_seq_comment = $db->queryRow($query_seq);

	foreach($arr_output as $out)
	{
		//file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/test_'.date('Ymd').'.log', date("Y-m-d H:i:s\t")." ::xxxxxx: mov_view_json -> out :::"."\r\n".print_r($out, true)."\r\n\n", FILE_APPEND);
		//file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/test_'.date('Ymd').'.log', date("Y-m-d H:i:s\t")." ::xxxxxx: mov_view_json -> out['content_id'] :::"."\r\n".$out['content_id']."\r\n\n", FILE_APPEND);
		$con_info = $db->queryRow("select * from bc_content where content_id='".$out['content_id']."'");
		$out['show_info'] = "[".date('Y-m-d H:i:s', strtotime($out['datetime']))."] ".$out['user_nm'].": ";
		$out['version'] = $con_info['version'];
		$out['datetime_format'] = date('Y-m-d H:i:s', strtotime($out['datetime']));
		if (($out['user_id'] == $user_id && $out['seq'] == $user_last_seq_comment['seq']) || $is_admin == 'Y'){
			$out['is_lasted'] = 1;
		} else {
			$out['is_lasted'] = 0;
		}
		array_push($comment_array, $out);
	}
	$comment_yn		= $db->queryOne("
										SELECT	COALESCE((
												SELECT	USE_YN
												FROM	BC_SYS_CODE A
												WHERE	A.TYPE_ID = 1
												AND		A.CODE='COMMENT_YN'
												), 'N') AS USE_YN
										FROM	BC_MEMBER
										WHERE	USER_ID = '".$user_id."'
									");
	if ($comment_yn == 'Y'){
		$result_data['comment'] = $comment_array;
	} else {
		$result_data['comment'] = 'N';
	}

// 	file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/test_'.date('Ymd').'.log', date("Y-m-d H:i:s\t")." ::xxxxxx: mov_view_json -> comment_array :::"."\r\n".print_r($comment_array, true)."\r\n\n", FILE_APPEND);

	$arr_output = MetaDataClass::getFieldValueInfo('sys', $bs_content_id , $content_id);
/*	$query_media_info = "
							select 	* 
							from 	bc_sys_meta_field f, 
									bc_sys_meta_value v 
							where 	v.content_id=".$content_id." 
									and v.sys_meta_field_id=f.sys_meta_field_id 
							order by f.show_order, f.sys_meta_field_title";
	$arr_output = $db->queryAll($query_media_info);
*/
	$result_data['mediaInfo'] = $arr_output;

	$category_query = " ";
	
	if( DB_TYPE == 'oracle' ){
		$category_query = "
			SELECT	CATEGORY_ID,
					CATEGORY_TITLE,
					IS_DELETED,
					NO_CHILDREN,
					PARENT_ID,
					SHOW_ORDER
			FROM	BC_CATEGORY
			WHERE	CATEGORY_ID != 0
			START WITH CATEGORY_ID = (
					SELECT C.CATEGORY_ID
					FROM	BC_CATEGORY C, BC_CATEGORY_MAPPING CM 
					WHERE	CM.UD_CONTENT_ID = '$ud_content_id'
					AND		CM.CATEGORY_ID = C.CATEGORY_ID AND C.PARENT_ID!=-1
				)
			CONNECT BY PRIOR CATEGORY_ID = PARENT_ID
			ORDER SIBLINGS BY SHOW_ORDER ASC, CATEGORY_TITLE ASC
			";
	} else {
		$category_query = "
			WITH RECURSIVE q AS (
				SELECT	ARRAY[po.CATEGORY_ID] AS HIERARCHY
						,po.CATEGORY_ID
						,po.CATEGORY_TITLE
						,po.IS_DELETED
						,po.NO_CHILDREN
						,po.SHOW_ORDER
						,po.PARENT_ID
						,1 AS LEVEL
				FROM	BC_CATEGORY po
				WHERE	po.CATEGORY_ID = '$ud_content_id'
				AND		po.IS_DELETED = 0
				UNION ALL
				SELECT	q.HIERARCHY || po.CATEGORY_ID
						,po.CATEGORY_ID
						,po.CATEGORY_TITLE
						,po.IS_DELETED
						,po.NO_CHILDREN
						,po.SHOW_ORDER
						,po.PARENT_ID
						,q.level + 1 AS LEVEL
				FROM	BC_CATEGORY po
						JOIN q ON q.CATEGORY_ID = po.PARENT_ID
				WHERE	po.IS_DELETED = 0
			)
			SELECT	CATEGORY_ID,
					CATEGORY_TITLE,
					IS_DELETED,
					NO_CHILDREN,
					PARENT_ID,
					SHOW_ORDER
			FROM	q
			WHERE 	CATEGORY_ID != 0
			ORDER BY HIERARCHY
			";
	}
	$categories = $db->queryAll($category_query);
	$result_data['categories'] = $categories;

	$category_title_query = "
			SELECT	CATEGORY_TITLE
			FROM	BC_CATEGORY
			WHERE	CATEGORY_ID = '".$content[category_id]."'
			";
	$category_title = $db->queryRow($category_title_query);

	$get_log_edit_metadata_query = "
									SELECT 		USER_ID
												,CONTENT_ID
												,BS_CONTENT_ID
												,UD_CONTENT_ID 
												,CREATED_DATE
												,DESCRIPTION
									FROM 		BC_LOG
									WHERE 		ACTION = 'edit'
									AND 		CONTENT_ID = '".$content_id."'
									ORDER BY 	LOG_ID DESC
									";
	$get_log_edit_metadata_query = "
									SELECT 		m.USER_NM
												,l.CREATED_DATE
												,l.DESCRIPTION
												,l.LOG_ID
									FROM 		BC_LOG l
												,BC_MEMBER m
									WHERE 		l.USER_ID = m.USER_ID
									AND 		(ACTION = 'edit' OR ACTION = 'original_update_renew')
									AND 		CONTENT_ID = '".$content_id."'
									ORDER BY 	l.CREATED_DATE DESC
									";
	$history_edit_metadata		= $db->queryOne("
										SELECT	COALESCE((
												SELECT	USE_YN
												FROM	BC_SYS_CODE A
												WHERE	A.TYPE_ID = 1
												AND		A.CODE='HISTORY_EDIT_METADATA'
												), 'N') AS USE_YN
										FROM	BC_MEMBER
										WHERE	USER_ID = '".$user_id."'
									");
	if ($history_edit_metadata == 'Y'){
		$get_log_edit_metadata = $db->queryAll($get_log_edit_metadata_query);
		$result_data['history_edit_metadata'] = $get_log_edit_metadata;
	} else {
		$result_data['history_edit_metadata'] = 'N';
	}

	$buttonEdit = 0;
	if (($user_id && checkAllowUdContentGrant($user_id, $ud_content_id, GRANT_EDIT)) || 
		($user_id && checkAllowUdContentGrant($user_id, $ud_content_id, GRANT_EDIT_MY_CONTENT))) {
		$buttonEdit = 1;
	}
	
	//file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/test_'.date('Ymd').'.log', date("Y-m-d H:i:s\t")." ::xxxxxx: mov_view_json -> get_log_edit_metadata :::"."\r\n".print_r($get_log_edit_metadata, true)."\r\n\n", FILE_APPEND);

// 	file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/test_'.date('Ymd').'.log', date("Y-m-d H:i:s\t")." ::xxxxxx: mov_view_json -> query_field :::"."\r\n".print_r($r, true)."\r\n\n", FILE_APPEND);
	//12강 물과 함께 시원한 여름 저작권 테스트
	echo json_encode(array(
		'success'=>'true',
		'query'=>$query,
		'data'=>$result_data,
		'stream_file'=>$stream_file,
		'listview'=>$listview,
		'content_id'=>$content_id,
		'meta_table_id'=>$result_data[basic][0][ud_content_id],
		'ud_content_id'=>$result_data[basic][0][ud_content_id],
		'title'=>$detail_title_value,
		'category_id'=>$content[category_id],
		'category_full_path'=>$content[category_full_path],
		'bs_content_id'=>$content[bs_content_id],
		'category_title'=>$category_title[category_title],
		'buttonEdit' =>$buttonEdit,
		'is_group' =>$content[is_group],
		'parent_content_id' =>$content[parent_content_id]
	));
}
catch (Exception $e)
{
	echo '{"success":false,"error": "'.$e.'", "msg": "'.$db->last_query.'"}';
}
?>