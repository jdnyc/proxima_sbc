<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
fn_checkAuthPermission($_SESSION);

$post_category_id = $_POST['node'];
$page_mode = $_POST['page_mode'];
$root_category_id = 0;
$user_id = $_SESSION['user']['user_id'];
$is_admin = $_SESSION['user']['is_admin'];
$node_list = array();

if (is_null($post_category_id)
		|| strstr($post_category_id, 'xnode') ) {
	$post_category_id = $root_category_id;
}



$today = date_create(date('Ymd'));

try
{
	if( DB_TYPE == 'oracle' ){
		$query = "
			SELECT	A.CATEGORY_ID, A.PARENT_ID, A.CATEGORY_TITLE, A.DEPTH, COALESCE(B.CONTENTS_CNT, 0) AS CONTENTS_CNT, B.ORIGINAL_SIZE, B.PROXY_SIZE, B.LAST_REGIST_DATE
			FROM	(
					  SELECT  C.CATEGORY_ID, C.PARENT_ID, C.CATEGORY_TITLE, C.SHOW_ORDER, C.NO_CHILDREN, LEVEL AS DEPTH
					  FROM    BC_CATEGORY C
					  WHERE   PARENT_ID = '".$post_category_id."'
					  START WITH CATEGORY_ID = 0
					  CONNECT BY PRIOR CATEGORY_ID = PARENT_ID
					  ORDER siblings BY C.SHOW_ORDER
					) A
					LEFT OUTER JOIN (
					  SELECT  C.CATEGORY_ID
						  , COUNT(C.CONTENT_ID)AS CONTENTS_CNT
						  , SUM(ORIGINAL_M.FILESIZE) AS ORIGINAL_SIZE
						  , SUM(PROXY_M.FILESIZE) AS PROXY_SIZE
						  , MAX(C.CREATED_DATE) AS LAST_REGIST_DATE
					  FROM    view_bc_content C
						  LEFT OUTER JOIN (
							SELECT CONTENT_ID, MEDIA_ID, FILESIZE
							FROM BC_MEDIA
							WHERE MEDIA_TYPE = 'original'
						  ) ORIGINAL_M ON (ORIGINAL_M.CONTENT_ID = C.CONTENT_ID )
						  LEFT OUTER JOIN (
							SELECT CONTENT_ID, MEDIA_ID, FILESIZE
							FROM BC_MEDIA
							WHERE MEDIA_TYPE = 'proxy'
						  ) PROXY_M ON (PROXY_M.CONTENT_ID = C.CONTENT_ID)
					  WHERE   C.STATUS = 2
					  AND     C.IS_DELETED = 'N'
					  GROUP BY C.CATEGORY_ID
					) B ON (A.CATEGORY_ID = B.CATEGORY_ID)
		";
	}else{
		$query = "
			SELECT	A.CATEGORY_ID, A.PARENT_ID, A.CATEGORY_TITLE, A.DEPTH, COALESCE(B.CONTENTS_CNT, 0) AS CONTENTS_CNT, B.ORIGINAL_SIZE, B.PROXY_SIZE, B.LAST_REGIST_DATE
			FROM	(
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
							WHERE	po.CATEGORY_ID = 0
							AND		po.IS_DELETED = '0'
							UNION ALL
							SELECT	q.HIERARCHY || po.CATEGORY_ID
									,po.CATEGORY_ID
									,po.CATEGORY_TITLE
									,po.IS_DELETED
									,po.NO_CHILDREN
									,po.SHOW_ORDER
									,po.PARENT_ID
									,q.level + 1 AS level
							FROM	BC_CATEGORY po
									JOIN q ON q.CATEGORY_ID = po.PARENT_ID
							WHERE	po.IS_DELETED = '0'
						
						)
						SELECT  CATEGORY_ID, PARENT_ID, CATEGORY_TITLE, SHOW_ORDER, NO_CHILDREN, LEVEL AS DEPTH
						FROM    q
						WHERE   PARENT_ID = '".$post_category_id."'
						ORDER BY HIERARCHY
					) A
					LEFT OUTER JOIN (
					  SELECT  C.CATEGORY_ID
						  , COUNT(C.CONTENT_ID)AS CONTENTS_CNT
						  , SUM(ORIGINAL_M.FILESIZE) AS ORIGINAL_SIZE
						  , SUM(PROXY_M.FILESIZE) AS PROXY_SIZE
						  , MAX(C.CREATED_DATE) AS LAST_REGIST_DATE
					  FROM    view_bc_content C
						  LEFT OUTER JOIN (
							SELECT CONTENT_ID, MEDIA_ID, FILESIZE
							FROM BC_MEDIA
							WHERE MEDIA_TYPE = 'original'
						  ) ORIGINAL_M ON (ORIGINAL_M.CONTENT_ID = C.CONTENT_ID )
						  LEFT OUTER JOIN (
							SELECT CONTENT_ID, MEDIA_ID, FILESIZE
							FROM BC_MEDIA
							WHERE MEDIA_TYPE = 'proxy'
						  ) PROXY_M ON (PROXY_M.CONTENT_ID = C.CONTENT_ID)
					  WHERE   C.STATUS = '2'
					  AND     C.IS_DELETED = 'N'
					  GROUP BY C.CATEGORY_ID
					) B ON (A.CATEGORY_ID = B.CATEGORY_ID)
		";
	}
	$order = " order by A.show_order asc, A.category_title asc ";

	$category_list = $db->queryAll($query.$order);
	$data = array();
	$list = array();
	$result = array();

	$no = 1;
	foreach($category_list as $row)
	{
		$node = array();
		$child_no = 1;
		$leaf = true;
		$hidden = false;
		$category_id = $row['category_id'];
		$category_title = $row['category_title'];
		$status = $row['status'];
		$no_children = $row['no_children'];
		$expired_date = $row['expired_date'];
		$broad_date = $row['broad_date'];
		$contents = $row['contents'];
		$req_user_id = $row['req_user_id'];

		$row['hidden'] = $hidden;
		$row['id'] = $category_id;
		$row['title'] = $category_title;
		$row['text'] = $category_title;
		$row['expired_date'] = $expired_date;
		$row['remain_expired_date'] = $remain_expired_date.$expired_date_postfix;
		$row['broad_date'] = $broad_date;
		$row['contents'] = $contents;
		$row['category_path'] = $category_path;
		$row['catPathTitle'] = $catPathTitle;
		$row['tr_category'] = $tr_category;
		$row['tr_category_nm'] = $tr_category_nm;
		$row['editable'] = true;
		$row['status'] = $status;
		$row['no'] = $no;
		$row['icon'] = '/led-icons/folder.gif';
		$row['req_user_id'] = $req_user_id;
		$row['original_total_size'] = formatByte($row['original_size']);
		$row['proxy_total_size'] = formatByte($row['proxy_size']);
		if(!empty($row['last_regist_date'])) {
			$row['last_regist_date'] = date('Y-m-d H:i:s', strtotime($row['last_regist_date']));
		}

		array_push($result, $row);
		$no++;
	}

//	//루트노드 자신도 보이도록
//	if ($post_category_id == $root_category_id) {
//		$root = $db->queryRow("select * from bc_category where category_id='".$post_category_id."'");
//		$result = array(
//			'id' => $root['category_id'],
//			'category_id' => $root['category_id'],
//			'category_path' => '/'.$root['category_id'],
//			'title' => $root['category_title'],
//			'text' => $root['category_title'],
//			'catPathTitle' => $root['category_title'],
//			'expanded' => true,
//			'no' => 0,
//			'icon' => '/led-icons/folder.gif',
//			'leaf' => false,
//			'children' => $result,
//			'contents_cnt' => '',
//			'original_total_size' => '',
//			'proxy_total_size' => '',
//			'last_regist_date' => ''
//		);
//	}


	if($_POST['is_excel'] == 1)
	{
		$columns = json_decode($_POST['columns'], true);
		$array = array();
		foreach($result as $d)
		{
			$row = array();
			foreach($columns as $col)
			{
				$row[$col[1]] = $d[$col[0]];
			}
			array_push($array, $row);
		}

		echo createExcelFile(_text('MSG02143'),$array);
	}
	else
	{
		echo json_encode($result);
	}


}
catch (Exception $e)
{
	echo '오류 : '.$e->getMessage();
}

function getChildrenNode($row){

	if(!empty($row['children'])){
		$children = array();
		$children_no = 1;

		foreach($row['children'] as $children_row){
			$children_row['no'] = (string)$children_no;

			if(!empty($children_row['children'])){
				$children_row['children'] = getChildrenNode($children_row);
			}

			$children[] = $children_row;
			$children_no++;
		}

		return $children;
	}
	else{
		return array();
	}

}

function getRootParentID($id){
	global $db;

	$parent_id = $db->queryOne("select parent_id from bc_category where category_id=".$id);
	if($parent_id == '0') return $id;
	else return getRootParentID($parent_id);
}

//function getCategoryFullPath($id)
//{
	//global $db;

	//$parent_id = $db->queryOne("select parent_id from bc_category where category_id=".$id);
	//if ($parent_id !== 0 && !is_null($parent_id))
	//{
		//$self_id = getCategoryFullPath($parent_id);
	//}

	//return $self_id.'/'.$id;
//}

//function formatByte($b, $p=null) {
    //$units = array("B","KB","MB","GB","TB","PB","EB","ZB","YB");
    //$c=0;

	//if(empty($b) || $b < 1){
		//return '';
	//}
    //else if(!$p && $p !== 0) {
        //foreach($units as $k => $u) {
            //if(($b / pow(1024,$k)) >= 1) {
                //$r["bytes"] = $b / pow(1024,$k);
                //$r["units"] = $u;
                //$c++;
				//$r_k = $b;
            //}
        //}
        //return number_format($r["bytes"],2) . " " . $r["units"];
    //} else {
        //return number_format($b / pow(1024,$p), 2) . " " . $units[$p];
    //}

//}
?>