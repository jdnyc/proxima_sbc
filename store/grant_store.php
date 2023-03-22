<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
fn_checkAuthPermission($_SESSION);
$data = array();

$user_id = $_SESSION['user']['user_id'];

try
{
	/*{name: 'ud_content_id' },
		{name: 'member_group_id'},
		{name: 'category_id' },
		{name: 'group_grant'}*/

		$grant_type =$_POST['grant_type'];
		$query = "
				 SELECT	BCG.UD_CONTENT_ID,
						BCG.MEMBER_GROUP_ID,
						BCG.CATEGORY_ID,
						BCG.GROUP_GRANT,
						BCG.CATEGORY_FULL_PATH,
						BUC.UD_CONTENT_TITLE,
						BC.CATEGORY_TITLE,
						BMG.MEMBER_GROUP_NAME
				FROM	BC_GRANT BCG,
						BC_UD_CONTENT BUC,
						BC_CATEGORY BC,
						BC_MEMBER_GROUP BMG
				WHERE	BCG.UD_CONTENT_ID=BUC.UD_CONTENT_ID
				AND		BCG.CATEGORY_ID=BC.CATEGORY_ID
				AND		BCG.MEMBER_GROUP_ID=BMG.MEMBER_GROUP_ID
				AND		BCG.GRANT_TYPE='$grant_type'
		";

		$rows = $db->queryAll($query);
		
 		$grant_query = "
 				SELECT	BC.ID, BC.CODE,
 						(CASE WHEN BCM.LANG = 'ko' THEN COALESCE(BC.NAME, BC.ENAME) ELSE COALESCE(BC.ENAME, BC.NAME) END) AS NAME,
 						BC.CODE_TYPE_ID, BC.SORT, BC.HIDDEN, BC.ENAME, BC.REF1, BCT.NAME TYPE_NAME
				FROM	BC_CODE BC
						LEFT OUTER JOIN BC_CODE_TYPE BCT ON(BCT.ID = BC.CODE_TYPE_ID)
						LEFT OUTER JOIN BC_MEMBER BCM ON(BCM.USER_ID = '$user_id')
				WHERE	BCT.CODE='$grant_type' AND BC.USE_YN='Y'
				ORDER BY BC.ID
 		";
// 		$grant_query = "
// 					select bc.*,
// 							 bct.name type_name
// 					  from bc_code bc,
// 							 bc_code_type bct
// 					 where bct.id = bc.code_type_id
// 						and bct.code='$grant_type'
// 				 order by bc.id
//	  		";
		$grant_list = $db->queryAll($grant_query);

		foreach ($rows as $row) {
			$_grant_text = array();
			
			//비트 연산으로 권한 코드의 텍스트 병합
			foreach ($grant_list as $grant) {
				if (((int)$row['group_grant']) & ((int)$grant['code'])) {
					$_grant_text [] = $grant['name'];
				}
			}
			
			$grant_text = join(' / ', $_grant_text);
			$row['grant_text'] = $grant_text;
			
			array_push($data, array(
				$row['ud_content_title'],
				$row['member_group_name'],
				$row['category_title'],
				$row['group_grant'],
				$row['ud_content_id'],
				$row['member_group_id'],
				$grant_text
			));
		}

	echo json_encode($data);

} catch (Exception $e) {
	echo _text('MN00022').': '.$e->getMessage();
}
?>