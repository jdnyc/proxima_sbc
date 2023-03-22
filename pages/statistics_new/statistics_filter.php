<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

function filter_group_user($is_admin, $user_id){
	global $db;
	if(empty($is_admin))
	{
		$is_admin =$_SESSION['user']['is_admin'];
	}
	if(empty($user_id))
	{
		$user_id =$_SESSION['user']['user_id'];
	}
	if ($is_admin == 'Y'){
		$groups_parent = 0;
	} else {
		$groups_parent_arr = $db->queryAll("SELECT  N.MEMBER_GROUP_ID
											FROM BC_MEMBER_GROUP_MEMBER N
											WHERE N.MEMBER_ID = (	
																	SELECT	MEMBER_ID
																	FROM		BC_MEMBER
																	WHERE	USER_ID =  '".$user_id."'
																)
											");
		$groups_parent_list = array();
		foreach($groups_parent_arr as $group) {
			array_push($groups_parent_list , $group['member_group_id']);
		}
		$groups_parent = join(' ,' , $groups_parent_list);
	}
	if( DB_TYPE == 'oracle' ){
		$where = 	"USER_ID IN (
					SELECT 	USER_ID
					FROM 	BC_MEMBER					
					UNION ALL
					SELECT  M.USER_ID
					FROM 	BC_MEMBER_GROUP_MEMBER N
					INNER JOIN BC_MEMBER M ON N.MEMBER_ID=M.MEMBER_ID
					WHERE 	N.MEMBER_GROUP_ID in ( 	SELECT 	A.MEMBER_GROUP_ID
													FROM 	BC_MEMBER_GROUP A
													WHERE 	MEMBER_GROUP_ID IN (   	SELECT 	MEMBER_GROUP_ID
																					FROM 	BC_MEMBER_GROUP
																					MINUS
																					SELECT 	MEMBER_GROUP_ID
																					FROM 	BC_MEMBER_GROUP
																					WHERE 	MEMBER_GROUP_ID in ($groups_parent)
																				)
													START WITH MEMBER_GROUP_ID in ($groups_parent)
													CONNECT BY PRIOR MEMBER_GROUP_ID = PARENT_GROUP_ID) 
				)";
	} else {
		$where = "USER_ID IN (
					SELECT 	USER_ID
					FROM 	BC_MEMBER
					UNION ALL
					SELECT  M.USER_ID
					FROM 	BC_MEMBER_GROUP_MEMBER N
					INNER JOIN BC_MEMBER M ON N.MEMBER_ID=M.MEMBER_ID
					WHERE 	N.MEMBER_GROUP_ID in ( 	
													WITH RECURSIVE q AS (
														SELECT	ARRAY[po.MEMBER_GROUP_ID] AS HIERARCHY
																,po.MEMBER_GROUP_ID
																,1 AS LEVEL
														FROM	BC_MEMBER_GROUP po
														WHERE	po.MEMBER_GROUP_ID in ($groups_parent)
														UNION ALL
														SELECT	q.HIERARCHY || po.MEMBER_GROUP_ID
																,po.MEMBER_GROUP_ID
																,q.level + 1 AS LEVEL
														FROM	BC_MEMBER_GROUP po
																JOIN q ON q.MEMBER_GROUP_ID = po.PARENT_GROUP_ID
													)

													SELECT 	MEMBER_GROUP_ID
													FROM 	q
													WHERE 	MEMBER_GROUP_ID IN (   	SELECT 	MEMBER_GROUP_ID
																					FROM 	BC_MEMBER_GROUP
																					EXCEPT
																					SELECT 	MEMBER_GROUP_ID
																					FROM 	BC_MEMBER_GROUP
																					WHERE 	MEMBER_GROUP_ID in ($groups_parent)
																				)
												) 
				)";
		/* //where에 user_id가 있어서 다른사용자 통계가 조회되지 않음.
		$where = "USER_ID IN (
					SELECT 	USER_ID
					FROM 	BC_MEMBER
					WHERE 	USER_ID= '".$user_id."'
					UNION ALL
					SELECT  M.USER_ID
					FROM 	BC_MEMBER_GROUP_MEMBER N
					INNER JOIN BC_MEMBER M ON N.MEMBER_ID=M.MEMBER_ID
					WHERE 	N.MEMBER_GROUP_ID in ( 	
													WITH RECURSIVE q AS (
														SELECT	ARRAY[po.MEMBER_GROUP_ID] AS HIERARCHY
																,po.MEMBER_GROUP_ID
																,1 AS LEVEL
														FROM	BC_MEMBER_GROUP po
														WHERE	po.MEMBER_GROUP_ID in ($groups_parent)
														UNION ALL
														SELECT	q.HIERARCHY || po.MEMBER_GROUP_ID
																,po.MEMBER_GROUP_ID
																,q.level + 1 AS LEVEL
														FROM	BC_MEMBER_GROUP po
																JOIN q ON q.MEMBER_GROUP_ID = po.PARENT_GROUP_ID
													)

													SELECT 	MEMBER_GROUP_ID
													FROM 	q
													WHERE 	MEMBER_GROUP_ID IN (   	SELECT 	MEMBER_GROUP_ID
																					FROM 	BC_MEMBER_GROUP
																					EXCEPT
																					SELECT 	MEMBER_GROUP_ID
																					FROM 	BC_MEMBER_GROUP
																					WHERE 	MEMBER_GROUP_ID in ($groups_parent)
																				)
												) 
				)";
		*/
	}
	return $where;
}
?>