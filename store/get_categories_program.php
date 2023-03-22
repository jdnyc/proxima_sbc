<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

$request_type = $_REQUEST['type'];

$logger->addInfo('$_REQUEST', $_REQUEST);

$node			= $_REQUEST['node'];
$path			= $_REQUEST['path'];
$ud_content_id	= $_REQUEST['ud_content_id'];
$ud_content_tab	= $_REQUEST['ud_content_tab'];
$groups_array	= getGroups($_SESSION['user']['user_id']);

$category_grant = categoryGroupGrant($groups_array);

$logger->addInfo('$category_grant', $category_grant);

//노드별 권한 추가 2001-11-03 by 이성용
//부모노드에 권한이 존재하면 부모노드 권한 승계 / 파라미터가 없다면 권한 X
$read   = empty($_REQUEST['read'])      ? 0 : $_REQUEST['read'];
$add    = empty($_REQUEST['add'])       ? 0 : $_REQUEST['add'];
$edit   = empty($_REQUEST['edit'])      ? 0 : $_REQUEST['edit'];
$delete = empty($_REQUEST['del'])       ? 0 : $_REQUEST['del'];
$hidden = empty($_REQUEST['hidden'])    ? 0 : $_REQUEST['hidden'];

$category_grant_array = array(
		'read' => $read,
		'add' => $add,
		'edit' => $edit,
		'del' => $delete,
		'hidden' => $hidden
);

$user_id = $_SESSION['user']['user_id'];

if( DB_TYPE == 'oracle' ){
	$query = "
		SELECT	*
		FROM	BC_CATEGORY
		WHERE	CATEGORY_ID != 0
		START WITH CATEGORY_ID = 0
		CONNECT BY PRIOR CATEGORY_ID = PARENT_ID
		ORDER SIBLINGS BY SHOW_ORDER, CATEGORY_TITLE
	";
}else{
	$query = "
		WITH RECURSIVE q AS (
			SELECT	ARRAY[po.CATEGORY_ID] AS HIERARCHY
					,po.CATEGORY_ID
					,po.CATEGORY_TITLE
					,po.PARENT_ID
					,po.SHOW_ORDER
					,1 AS LEVEL
			FROM	BC_CATEGORY po
			WHERE	po.CATEGORY_ID = 0
			AND		po.IS_DELETED = 0
			UNION ALL
			SELECT	q.HIERARCHY || po.CATEGORY_ID
					,po.CATEGORY_ID
					,po.CATEGORY_TITLE
					,po.PARENT_ID
					,po.SHOW_ORDER
					,q.level + 1 AS LEVEL
			FROM	BC_CATEGORY po
					JOIN q ON q.CATEGORY_ID = po.PARENT_ID
			WHERE	po.IS_DELETED = 0
		)
		SELECT	CATEGORY_ID
				,CATEGORY_TITLE
				,PARENT_ID
				,SHOW_ORDER
		FROM	q
		WHERE 	CATEGORY_ID != 0
		--AND		PARENT_ID = 0
		ORDER BY SHOW_ORDER, CATEGORY_TITLE, HIERARCHY;
	";
}

//$query = "
	//SELECT	*
	//FROM	BC_CATEGORY
	//WHERE	CATEGORY_ID != 0
	//START WITH CATEGORY_ID = 0
	//CONNECT BY PRIOR CATEGORY_ID = PARENT_ID
	//ORDER SIBLINGS BY SHOW_ORDER, CATEGORY_TITLE
//";

$categories = $db->queryAll($query);
foreach ($categories as $category) {
	//관리자가 아니고 제작그룹일땐 오직 해당 프로그램만..
	$node_category_id = $category['category_id'];
	$node_grant_array = set_category_grant($node_category_id, $category_grant, $category_grant_array, $ud_content_id );
	$expanded  = false;

	if ($node_grant_array['read'] == 0) {
		$node_grant_array['hidden'] = 1;
	}

	if ($category['status'] != 'accept' && $category['status'] != '') {
		$node_grant_array['hidden'] = true;
	}


	if (isset($_POST['path'])) {
		if ($category['no_children']) {
			$data[] = array(
					'id' => $category['category_id'],
					'code' => $category['code'],
					'text' => $category['category_title'],
					'icon' => '/led-icons/folder.gif',
					'read' => $node_grant_array['read'],
					'add' => $node_grant_array['add'],
					'edit' => $node_grant_array['edit'],
					'del' => $node_grant_array['del'],
					'hidden' => $node_grant_array['hidden'],
					'leaf' => (boolean)$category['no_children']
			);
		} else {
			$data[] = array(
					'id' => $category['category_id'],
					'code' => $category['code'],
					'text' => $category['category_title'],
					//'singleClickExpand' => true,
					'read' => $node_grant_array['read'],
					'add' => $node_grant_array['add'],
					'edit' => $node_grant_array['edit'],
					'del' => $node_grant_array['del'],
					'hidden' => $node_grant_array['hidden'],
					'leaf' => (boolean)$category['no_children']
			);
		}
	} else {
		if ($category['no_children']) {
			$data[] = array(
					'id' => $category['category_id'],
					'code' => $category['code'],
					'text' => $category['category_title'],
					'icon' => '/led-icons/folder.gif',
					//'singleClickExpand' => true,
					'read' => $node_grant_array['read'],
					'add' => $node_grant_array['add'],
					'edit' => $node_grant_array['edit'],
					'del' => $node_grant_array['del'],
					'hidden' => $node_grant_array['hidden'],
					'leaf' => (boolean)$category['no_children']
					,'expanded' => $expanded
			);
		} else {
			$data[] = array(
					'id' => $category['category_id'],
					'code' => $category['code'],
					'text' => $category['category_title'],
					//'singleClickExpand' => true,
					'read' => $node_grant_array['read'],
					'add' => $node_grant_array['add'],
					'edit' => $node_grant_array['edit'],
					'del' => $node_grant_array['del'],
					'hidden' => $node_grant_array['hidden'],
					'leaf' => (boolean)$category['no_children']
			);
		}
	}
}
echo json_encode($data);
?>