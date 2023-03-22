<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/db.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

$user_id = $_SESSION['user']['user_id'];
$is_admin = $_SESSION['user']['is_admin'];
$action = $_POST['action'];
$type = $_POST['type'];
$notice_id = $_POST['notice_id'];

try
{
	if( $type == 'insert' ){
		$notice['notice_type'] = 'all';
		$notice['from_user_id']= $user_id;
		$notice_start = date("Y-m-d");
		$notice_end = date("Y-m-d", strtotime(date("Y-m-d"). "+7day"));
		$notice_reg =  date("Y-m-d");
	}else{

		$description = '';
		insertLogNotice('read_notice', $user_id, $notice_id, $description);

		$query = "
			SELECT	N.*, F.FILE_NAME, F.FILE_PATH
			FROM		BC_NOTICE N
							LEFT JOIN	BC_NOTICE_FILES F
							ON				N.NOTICE_ID = F.NOTICE_ID
			WHERE	N.NOTICE_ID = ".$notice_id."
		";
		$db->setLoadNEWCLOB(true);
		$notice = $db->queryRow($query);
		if( $type == 'edit' ){
			if( $notice['notice_type'] == 'all' ){
			}else{
				$query_to = "
					SELECT	M.MEMBER_ID as to_id, M.USER_NM as to_name, M.USER_ID AS USER_ID,
								(SELECT 'u' FROM (SELECT USER_ID FROM BC_MEMBER WHERE USER_ID = '".$user_id."') A) AS TYPE_NOTICE
					FROM		BC_NOTICE_RECIPIENTS N, BC_MEMBER M
					WHERE	N.MEMBER_ID = M.MEMBER_ID
					AND		N.NOTICE_ID = ".$notice_id."
					UNION ALL
					SELECT	N.MEMBER_GROUP_ID as to_id, M.MEMBER_GROUP_NAME as to_name, M.MEMBER_GROUP_NAME AS USER_ID,
								(SELECT 'g' FROM (SELECT USER_ID FROM BC_MEMBER WHERE USER_ID = '".$user_id."') A) AS TYPE_NOTICE
					FROM		BC_NOTICE_RECIPIENTS N, BC_MEMBER_GROUP M
					WHERE	N.MEMBER_GROUP_ID = M.MEMBER_GROUP_ID
					AND		N.NOTICE_ID = ".$notice_id."
				";
				$notice_to = $db->queryAll($query_to);
				$to_ids = array();
				$to_nm_ids = array();
				$to_g_ids = array();
				$to_g_nm_ids = array();
				foreach( $notice_to as $to_id ){
					if($to_id['type_notice'] == 'u'){
						array_push($to_ids, $to_id['to_id']);
						array_push($to_nm_ids, $to_id['to_name']."[".$to_id['user_id']."]" );
					}else if($to_id['type_notice'] == 'g'){
						array_push($to_g_ids, $to_id['to_id']);
						array_push($to_g_nm_ids, $to_id['to_name'] );
					}
				}

				if( count($to_g_nm_ids) > 0){
					$groups = join(',', $to_g_nm_ids)."\\r";
				}

				$notice['to_user_ids'] = join(',', $to_ids);
				///$notice['to_user_names'] = join(',', array_merge($to_nm_ids,$to_g_nm_ids));
				$notice['to_user_names'] = $groups.join(',', $to_nm_ids);
				$notice['to_group_ids'] = join(',', $to_g_ids);
			}
			if ($is_admin == 'Y'){
				$readonly = 0;
			} else {
				$readonly = 1;
			}
		}

		$notice_start = empty($notice['notice_start']) ? '' : date("Y-m-d", strtotime($notice['notice_start']));
		$notice_end = empty($notice['notice_end']) ? '' : date("Y-m-d", strtotime($notice['notice_end']));
		$notice_reg = empty($notice['created_date']) ? '' : date("Y-m-d", strtotime($notice['created_date']));
		$contents =  addslashes($notice['notice_content_c']);
	}
	echo json_encode(array(
		'success'=>'true',
		'query'=>$query,
		'data'=>$notice,
		'readonly' => $readonly
	));
}
catch (Exception $e)
{
	echo '{"success":false, "msg": "'.$db->last_query.'"}';
}
?>