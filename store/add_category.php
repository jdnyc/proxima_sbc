<?php
header('Content-Type: application/json');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/workflow/lib/task_manager.php');

try {
	$action = $_REQUEST['action'];
	$success = true;
	$message = '';

	$action_array = explode('-', $action);
	$mode = array_pop($action_array);

	if( $mode == 'cg' )
	{
		$action = join('-' , $action_array);
	}

	$parent_id_array = explode( '/', $_REQUEST['parent_id'] );
	$parent_id		=	array_pop( $parent_id_array );

	$id_array		=	 explode( '/', $_REQUEST['id'] );
	$id				=	array_pop($id_array );

	$newParent_id_array	= 	explode( '/', $_REQUEST['newParent_id'] );
	$newParent_id	= 	array_pop( $newParent_id_array );

	$oldParent_id_array	= explode( '/', $_REQUEST['oldParent_id'] );
	$oldParent_id	= 	array_pop( $oldParent_id_array );

	//$db->setTransaction(true);

	switch ($action) {
		case 'create-folder':
			//throw new Exception('작업중입니다');
			$parent_id = $parent_id;

			$str_info = $db->queryRow(" select * from path_mapping where category_id='$parent_id' ");

			//if( $str_info['storage_group'] == 2){
			//	 throw new Exception('AD 그룹에 대한 작업은 기능 구현중입니다.');
			//}
			$title_post = trim($_REQUEST['title']);
			$title = preg_replace("/[#\&\+%@=\/\\\:;,\.'\"\^`~|\!\?\*$#<>\[\]\{\}]/i", "",  $title_post);

			if( empty($title) )  throw new Exception('잘못된 이름입니다.');

			$is_exists = $db->queryOne("select count(*) from bc_category where parent_id=".$parent_id." and upper(category_title)='".$db->escape($title)."'");
			if ($is_exists > 0) throw new Exception('동일한 이름이 존재합니다.');

			$seq =  getSequence('SEQ_BC_CATEGORY_ID');
			$result = $mdb->exec("update bc_category set no_children='0' where category_id=". $parent_id);
			$order_max=$db->queryOne("select max(show_order) from bc_category where parent_id=".$parent_id);

			$result = $mdb->exec(sprintf("insert into bc_category (category_id, parent_id, category_title, no_children, show_order) values (%d, %d, '%s', '1', %d)", $seq, $parent_id, $db->escape($title), $order_max));

			$order_max= $order_max+1;
			$db->exec(sprintf("update bc_category set show_order='%d' where category_id='1'",$order_max));

			if( $mode != 'cg' )
			{
				//OD연동 폴더 생성
				//$ingest_path = $params['ingest_path'];//'/Volumes/NPS_Main/Ingest/space'
				//$master_path = $params['master_path'];
				//$folder = $params['folder'];

				if( $str_info['storage_group'] == 1){

					$path_info_row = $db->queryRow(" select * from path_mapping where category_id='$parent_id' ");

					$path_info = $path_info_row['path'];

					$ingest_path = '/Volumes/NPS_Main/Ingest/'.$path_info;
					$master_path = '/Volumes/NPS_Main/Master/'.$path_info;

					if( !empty($path_info_row['ud_storage_group_id']) ){
						$isChangeInfo = $db->queryAll("select * from bc_ud_storage_group_map where storage_group_id='{$path_info_row['ud_storage_group_id']}' " );

						if( !empty($isChangeInfo) ){
							$str_map_info = array();
							foreach($isChangeInfo as $info)
							{
								$str_map_info [$info['source_storage_id']] = $info['ud_storage_id'];
							}

							if( $str_map_info[40]&& $str_map_info[56] ){
								$ingest_path_root = $db->queryOne("select path from BC_STORAGE where storage_id='$str_map_info[40]' ");
								$master_path_root = $db->queryOne("select path from BC_STORAGE where storage_id='$str_map_info[56]' ");
								$ingest_path = $ingest_path_root.'/'.$path_info;
								$master_path = $master_path_root.'/'.$path_info;
							}
						}
					}

					$params = array(
						'ingest_path' => $ingest_path,
						'master_path' => $master_path,
						'folder' => $title,
						'category_id' => $seq
					);

					$task_id = nps_od_linkage( 'create_folder' , 'admin' , $params );
					if(empty($task_id)) throw new Exception('작업오류 입니다.');

					$r = $db->exec("insert into CATEGORY_TASK_INFO (CATEGORY_ID,TASK_ID) values ( '$seq','$task_id' ) ");
				}
			}

            sort_category($parent_id);//CJO, 카테고리 생성/수정/삭제시 이름으로 정렬
		break;

		case 'rename-folder':
			$id = $id ;
			$new_name = $db->escape($_REQUEST['newName']);

			$is_exists = $db->queryOne("select count(*) from bc_category where parent_id=".$parent_id." and upper(category_title)='".$new_name."' and category_id != '$id' ");
			if ($is_exists > 0) throw new Exception('동일한 이름이 존재합니다.');

            $result = $mdb->exec(sprintf("update bc_category set category_title='%s' where category_id=%d", $new_name, $id));
			sort_category($parent_id);//CJO, 카테고리 생성/수정/삭제시 이름으로 정렬
			
			// 2018.02.12 khk 해당 카테고리에 속한 콘텐츠를 조회하여 검색엔진 업데이트
			$contents = \Proxima\models\content\Content::getContentsByCategoryId($id);
			foreach($contents as $content) {
				searchUpdate($content->get('content_id'));
			}
		break;

		case 'delete-folder':
			$id = $id;
			$parent_id = $parent_id;

			$hasChildNodes = $mdb->queryOne("select count(*) from bc_category where parent_id=$id");

			if($hasChildNodes > 1) throw new Exception('하위 카테리고리 부터 삭제하여주세요.');

            //$isExistContents = $db->queryOne("select  count(*) from view_bc_content c where c.category_id=$id and c.status='2' and  c.is_deleted='N' ");
            $isExistContents = $db->queryOne("select  count(*) from view_bc_content c where c.category_id=$id and c.is_deleted='N' ");
			if($isExistContents > 0 ){
				throw new Exception(_text('MSG02110'));//선택된 카테고리 내에 콘텐츠가 존재합니다.
				//throw new Exception('미디어가 존재합니다. 이관 또는 삭제 후 재시도해 주세요');
			}

			$result = $mdb->exec(sprintf("delete from bc_category where category_id=%d", $id));

			$parentHasChildNodes = $mdb->queryOne("select count(*) from bc_category where parent_id=$parent_id");

			if($parentHasChildNodes == 0) {
				$result = $mdb->exec("update bc_category set no_children=1 where category_id=$parent_id");
			}
            sort_category($parent_id);//CJO, 카테고리 생성/수정/삭제시 이름으로 정렬
		break;

		case 'delete-root-folder':
			$id = $id;
			if( DB_TYPE == 'oracle' ){
				$result = $mdb->exec(sprintf("	DELETE  BC_CATEGORY 
												WHERE   CATEGORY_ID IN (SELECT  CATEGORY_ID
																		FROM    BC_CATEGORY
																		START WITH CATEGORY_ID = %d
																		CONNECT BY PRIOR CATEGORY_ID = PARENT_ID)", $id));
			} else {
				$result = $mdb->exec(sprintf("	DELETE
												FROM	BC_CATEGORY
												WHERE	CATEGORY_ID IN (
																WITH RECURSIVE q AS (
																	SELECT	ARRAY[po.CATEGORY_ID] AS HIERARCHY
																			,po.CATEGORY_ID
																			,po.CATEGORY_TITLE
																			,po.PARENT_ID
																			,1 AS LEVEL
																	FROM	BC_CATEGORY po
																	WHERE	po.IS_DELETED = 0
																	AND		po.CATEGORY_ID = %d
																	UNION ALL
																	SELECT	q.HIERARCHY || po.CATEGORY_ID
																			,po.CATEGORY_ID
																			,po.CATEGORY_TITLE
																			,po.PARENT_ID
																			,q.level + 1 AS LEVEL
																	FROM	BC_CATEGORY po
																			JOIN q ON q.CATEGORY_ID = po.PARENT_ID
																	WHERE	po.IS_DELETED = 0
																)
																SELECT	CATEGORY_ID
																FROM	q
																WHERE	CATEGORY_ID > 0
														)", $id));
				
			}
		break;

		case 'sort-folder':
			$parent_id = 	$parent_id;
			$order =	$_REQUEST['order'];

			if( $order_array = json_decode($order, true) )
			{
				foreach($order_array as $sort => $id)
				{
					$result = $mdb->exec("update bc_category set show_order='$sort' where category_id='$id'");

				}
			}

			break;

		case 'move-folder':
			//printr ($_SESSION['user']['groups']);exit;
			$user_id = $_SESSION['user']['user_id'];
			if( empty($user_id) || $user_id=='temp') throw new Exception('로그인이 필요합니다.');

			$groups = getGroups($user_id);

			if($_SESSION['user']['is_admin'] != 'Y' &&  !in_array(CG_ADMIN_GROUP, $groups) )
			{
				 throw new Exception('권한이 없습니다.');
			}

			$target_id = $id;
			$newParent_id = 	$newParent_id;
			$oldParent_id = 	$oldParent_id;
			$order =	$_REQUEST['order'];

			$new_path = $_REQUEST['new_path'].'/'.$target_id;


			$category_info = $db->queryRow("select * from bc_category where category_id='$target_id'");
			$p_category_info = $db->queryOne("select count(*) from bc_category where parent_id='$target_id'");

			if( $newParent_id == $oldParent_id )
			{//부모들이 같다면 단순 정렬
				if( $order_array = json_decode($order, true) )
				{
					foreach($order_array as $sort => $id)
					{
						$result = $mdb->exec("update bc_category set show_order='$sort' where category_id='$id'");
					}
				}
			}
			else //이동
			{

				if( $p_category_info == 0 ) //마지막 노드일때
				{
					//대상 노드의 부모노드 변경
					$result = $mdb->exec("update bc_category set parent_id='$newParent_id' where category_id='$target_id'");

					//대상 노드에 속해있던 기존 콘텐츠의 경로 변경
					$result = $mdb->exec("update bc_content set category_full_path='$new_path' where category_id='$target_id'");

					$result = $mdb->exec("update bc_category_grant set category_full_path='$new_path' where category_id='$target_id'");


					$oldParent_child_count = $db->queryOne("select count(*) from bc_category where parent_id='$oldParent_id'");
					if($oldParent_child_count == 0)
					{
						$result = $mdb->exec("update bc_category set no_children='1' where category_id='$oldParent_id'");
					}

					//새부모노드의 자식노드를 정렬
					if( $order_array = json_decode($order, true) )
					{
						foreach($order_array as $sort => $id)
						{
							$result = $mdb->exec("update bc_category set show_order='$sort' where category_id='$id'");
						}
					}
				}
				else//자식노드가 있을때 우선 막음
				{
					throw new Exception('마지막 카테고리만 이동가능합니다');
				}
			}
			$order_max=$db->queryOne("select max(show_order)+1 from bc_category where parent_id=".$oldParent_id);
			//db->exec("update bc_category set show_order='".$order_max."',parent_id='4801860'  where category_id='1'");

		break;

		case 'hasChildContent'://해당 카테고리에 포함된 콘텐츠 정보
			$count = $db->queryOne("select count(*) from view_content where category_id='$id' and is_deleted='Y' and status > 0 ");
			die(json_encode( array(
				'success' => $success,
				'msg' => $message,
				'count' => $count
			)));
		break;

		case 'change-category':

			$id = $id;
			$category_full_path = $_REQUEST['new_path'];

			$str_info = $db->queryRow("select * from path_mapping where category_id='$id' ");



			$task_infos = $db->queryAll("select t.* from category_task_info cti, bc_task t where cti.task_id=t.task_id and cti.category_id='$id' ");


			if( !empty($task_infos) )
			{
				$job_count = 0;
				foreach($task_infos as $task_info)
				{
					//폴더 생성 작업이 있는것
					if( ( $task_info['job_priority'] == 2 ) && ( $task_info['status'] == 'complete' ) )
					{
						$job_count++;
					}
					else if( $task_info['status'] == 'error' )
					{
						throw new Exception('폴더 생성에 실패했습니다.');
					}
				}

				//인제스트 마스터가 모두 작업완료될때까지 2012-09-20 이성용
				if( $job_count != 2 )
				{
					throw new Exception('폴더 생성중입니다.');
				}
			}


			if( $contents_array = json_decode($_REQUEST['contents'], true) )
			{
				foreach( $contents_array as $content_id)
				{

					//파일이동 채널명
					$channel = 'move_content';


					///DB view_content parent_id 추가 DB수정 필요
					$content		= $db->queryRow("select * from view_content where content_id='$content_id'");

					if( empty($content) ) throw new Exception('삭제된 콘텐츠 입니다.');

					$is_original = $db->queryRow("select m.* from view_content c, bc_media m where c.content_id=m.content_id and m.media_type='original' and ( m.status is null or m.status= 0 ) and c.content_id='$content_id'   ");

					$parent_id = $content['parent_id'];

					//기존 카테고리 패스 배열
					$old_category_array = explode('/', trim($content['category_full_path'], '/') );

					if( $content['category_id'] != '0' )//프로그램 폴더
					{//루트 카테고리가 아닌 1 depth 카테고리 일때

						$new_category_array = explode('/', trim($category_full_path, '/') );

						if( ( count($old_category_array) > 1 ) && ( count($new_category_array) > 1 ) )
						{
							//기존,신규 카테고리패스 정보가 프로그램이하 일때

							//같은 프로그램인지 체크
							$new_root_category = array_shift($new_category_array);
							$new_program_category =  array_shift($new_category_array);

							$old_root_category = array_shift($old_category_array);
							$old_program_category =  array_shift($old_category_array);

							if( $new_program_category != $old_program_category )
							{//다른 프로그램사이의 이동이라면 권한변경 프로세스 필요
								///첫폴더 패스까지 이동후 권한 변경 => 이후 패스
								$channel = 'move_content_grant';
							}
						}
					}

					if( check_workingMedia($content_id ) ){
						throw new Exception('파일에 대한 작업이 진행중입니다.완료후에 이동가능합니다.');
					}


					//콘텐츠 유형별 채널분리
					$channel = getUDChannel( $content['ud_content_id'], $channel );

					$before_info = $db->queryRow("select * from view_content c, bc_category ca where c.category_id=ca.category_id");
					$result = $mdb->exec("update bc_content set category_id='$id', category_full_path='$category_full_path' where content_id='$content_id'");

					$r = $db->exec("update nps_work_list set category_id='$id' where content_id='$content_id'");

					Insert_Work_List_for_ingest($content_id);

					//원본이 있을때만 이동 2012-11-05 이성용
					if( !empty($is_original) ){
						$task_mgr = new TaskManager($db);
						$task_mgr->start_task_workflow($content_id, $channel, $_SESSION['user']['user_id'] );
					}
				}
			}

		break;

		case 'golast':

			$task_infos = $db->queryAll("select t.* from category_task_info cti, bc_task t where cti.task_id=t.task_id and cti.category_id='$id' and cti.type is null ");

			if( !empty($task_infos) )
			{
				$job_count = 0;
				foreach($task_infos as $task_info)
				{
					//폴더 생성 작업이 있는것
					if( ( $task_info['job_priority'] == 2 ) && ( $task_info['status'] == 'complete' ) )
					{
						$job_count++;
					}
					else if( $task_info['status'] == 'error' )
					{
						throw new Exception('폴더 생성에 실패했습니다.');
					}
				}

				//인제스트 마스터가 모두 작업완료될때까지 2012-09-20 이성용
				if( $job_count != 2 )
				{
					throw new Exception('폴더 생성중입니다.');
				}
			}

			$r = $db->exec("update bc_category  set code='last' where category_id='$parent_id'");
			$r = $db->exec("update bc_category  set code='last' where category_id='$id'");

			$lists = $db->queryAll("select c.* from view_content c, bc_media m where c.category_id='$id' and c.content_id=m.content_id and m.media_type='original' and ( m.status is null or m.status= 0 )  ");

			if( !empty($lists) )
			{
				foreach($lists as $list)
				{
					$content_id = $list['content_id'];
					$ud_content_id = $list['ud_content_id'];
					$category_id = $list['category_id'];

					$query= "update bc_media set status = '1' where media_type='original' and content_id = '$content_id'" ;
					$rtn = $db->exec($query);

					$channel = 'delete';
					$channel = getUDChannel( $ud_content_id, $channel );
					$insert_task = new TaskManager($db);
					$task_id = $insert_task->start_task_workflow($content_id, $channel, $_SESSION['user']['user_id'] );

					if( !empty($category_id) )
					{
						$r = $db->exec("insert into CATEGORY_TASK_INFO (CATEGORY_ID,TASK_ID, TYPE ) values ( '$category_id','$task_id','delete' ) ");
					}
				}
			}
			else
			{

				$parent = $db->queryRow("select path,storage_group,ud_storage_group_id from path_mapping where category_id='$parent_id'");
				$parent_path = $parent['path'];
				$storage_group = $parent['storage_group'];
				$ud_storage_group_id = $parent['ud_storage_group_id'];

				if( $storage_group == 1){
					//od일때
					$category_title = $db->queryOne("select category_title  from bc_category where category_id='$id'");

					if(empty($category_title) ||  empty($parent_path) )  throw new Exception('경로를 알 수 없습니다.');

					$delete_foler_path = $parent_path.'/'.$category_title;

					$job_infos =  $db->queryAll("select r.* , tt.type from bc_task_rule r , bc_task_type tt  where r.task_type_id=tt.task_type_id and ( r.task_rule_id='86' or r.task_rule_id='87') ");

					foreach($job_infos as $job_info)
					{
						$next_task_id = getSequence('TASK_SEQ');

						$source = $delete_foler_path;
						$target = $delete_foler_path;
						$insert_q = " insert into bc_task ( MEDIA_ID,TASK_ID,TYPE,SOURCE,TARGET,PARAMETER,STATUS,PRIORITY,CREATION_DATETIME,DESTINATION,TASK_WORKFLOW_ID,JOB_PRIORITY,TASK_RULE_ID,ROOT_TASK,TASK_USER_ID ) values ('0','$next_task_id', '{$job_info['type']}', '$source', '$target', '{$job_info['parameter']}','queue', '300', '".date("YmdHis")."', 'delete', '14', '2', '{$job_info['task_rule_id']}', '$next_task_id', '$user_id') ";
						$r =  $db->exec($insert_q);

						if( !empty($ud_storage_group_id) ){
							$isChangeInfo = $db->queryAll("select * from bc_ud_storage_group_map where storage_group_id='$ud_storage_group_id' " );

							if( !empty($isChangeInfo) ){
								$str_map_info = array();
								foreach($isChangeInfo as $info)
								{
									$str_map_info [$info['source_storage_id']] = $info['ud_storage_id'];
								}

								if( $str_map_info[$job_info['source_path']]&& $str_map_info[$job_info['target_path']] ){
									$src_storage_id = $str_map_info[$job_info['source_path']];
									$trg_storage_id = $str_map_info[$job_info['target_path']];
									$insert_q = "insert into BC_TASK_STORAGE
  (TASK_ID,SRC_STORAGE_ID,TRG_STORAGE_ID )
  values
  ('$next_task_id', '$src_storage_id' , '$trg_storage_id' )";
									$r = $db->exec($insert_q);
								}
							}
						}


					}
				}else if($storage_group == 2){
					//ad일때
					$category_title = $db->queryOne("select category_title  from bc_category where category_id='$id'");

					if(empty($category_title) ||  empty($parent_path) )  throw new Exception('경로를 알 수 없습니다.');

					$delete_foler_path = $parent_path.'/'.$category_title;

					$job_infos =  $db->queryAll("select r.* , tt.type from bc_task_rule r , bc_task_type tt  where r.task_type_id=tt.task_type_id and ( r.task_rule_id='115' or r.task_rule_id='116') ");

					foreach($job_infos as $job_info)
					{
						if( $job_info['task_rule_id'] == '116'){
							$source = $parent_path.'/'.'Ingest'.'/'.$category_title;
							$target = $parent_path.'/'.'Ingest'.'/'.$category_title;
						}else{
							$source = $parent_path.'/'.'Master'.'/'.$category_title;
							$target = $parent_path.'/'.'Master'.'/'.$category_title;
						}
						$next_task_id = getSequence('TASK_SEQ');

						$insert_q = " insert into bc_task ( MEDIA_ID,TASK_ID,TYPE,SOURCE,TARGET,PARAMETER,STATUS,PRIORITY,CREATION_DATETIME,DESTINATION,TASK_WORKFLOW_ID,JOB_PRIORITY,TASK_RULE_ID,ROOT_TASK,TASK_USER_ID ) values ('0','$next_task_id', '{$job_info['type']}', '$source', '$target', '{$job_info['parameter']}','queue', '300', '".date("YmdHis")."', 'delete', '14', '2', '{$job_info['task_rule_id']}', '$next_task_id', '$user_id') ";
						$r =  $db->exec($insert_q);
					}
				}
			}

			$message = '제작 종료 성공';
		break;

	}

	//$db->commit();

	die(json_encode( array(
		'success' => $success,
		'msg' => $message,
		'title' => $title,
		'id' => $seq,
		'q' => $lists
	)));

} catch(Exception $e) {
	if($db->transaction) $db->rollback();
	die(json_encode( array(
		'success' => false,
		'msg' => $e->getMessage()
	)));
}

function Insert_Work_List_for_ingest($content_id)
{
	global $db;

	$type = 'ingest' ;

	$content		= $db->queryRow("select * from view_content where content_id='$content_id'");
	$created_date	= $content['created_date'];
	$from_user_id	= $_SESSION['user']['user_id'];
	$to_user_id		= $_SESSION['user']['user_id'];
	$nps_work_list_id	= getSequence('SEQ_NPS_WORK_LIST_ID');
	$work_type			= $type;//	review / preview 작업타입

	$work_title				= $db->escape($content['title']);

	$category = $db->queryRow("select ca.* from bc_content c, bc_category ca where ca.category_id=c.category_id and c.content_id='$content_id'");

	if( $category['parent_id'] == '0' )
	{
		$member_group_id = $db->queryOne("select member_group_id from path_mapping where category_id='$category_id'");
		$category_id=$category['category_id'];
	}
	else
	{
		$member_group_id = $db->queryOne("select member_group_id from path_mapping where category_id='{$category['parent_id']}'");
		$category_id=$category['category_id'];
	}

	$status = 'complete';
	$is_send_to = '0';

	$r = $db->exec("insert into NPS_WORK_LIST(NPS_WORK_LIST_ID, FROM_USER_ID, CONTENT_ID, WORK_TYPE, STATUS, CREATED_DATE, WORK_TITLE, TO_USER_ID , IS_SEND_TO , category_id , member_group_id ) values ('$nps_work_list_id', '$from_user_id', '$content_id', '$work_type', '$status', '$created_date', '$work_title', '$to_user_id' , '$is_send_to','$category_id' , '$member_group_id' ) ");

	$msg = $work_title.' 작업 요청 성공';

	return $msg;
}
?>