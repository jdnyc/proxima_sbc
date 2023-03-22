<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

$user_id = $_SESSION['user']['user_id'];

try
{
	//$result = get_children(0, 0);
	//echo json_encode($result);
	//return;

	$query = "
		SELECT  A.CATEGORY_ID, A.PARENT_ID, A.CATEGORY_TITLE, A.DEPTH, B.CONTENTS_CNT, B.ORIGINAL_SIZE, B.PROXY_SIZE, B.LAST_REGIST_DATE
		FROM    (
				  SELECT  C.CATEGORY_ID, C.PARENT_ID, C.CATEGORY_TITLE, C.SHOW_ORDER, C.NO_CHILDREN, LEVEL AS DEPTH
				  FROM    BC_CATEGORY C
				  WHERE   CATEGORY_ID > 0
				  --AND     PARENT_ID = 0
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
				  FROM    VIEW_CONTENT C
					  LEFT OUTER JOIN (SELECT CONTENT_ID, MEDIA_ID, FILESIZE FROM BC_MEDIA WHERE MEDIA_TYPE = 'original') ORIGINAL_M ON (ORIGINAL_M.CONTENT_ID = C.CONTENT_ID )
					  LEFT OUTER JOIN (SELECT CONTENT_ID, MEDIA_ID, FILESIZE FROM BC_MEDIA WHERE MEDIA_TYPE = 'proxy') PROXY_M ON (PROXY_M.CONTENT_ID = C.CONTENT_ID)
				  WHERE   C.STATUS = 2
				  AND     C.IS_DELETED = 'N'
				  GROUP BY C.CATEGORY_ID        
				) B ON (A.CATEGORY_ID = B.CATEGORY_ID)
	";

	$category_list = $db->queryAll($query);
	$data = array();
	$list = array();

	foreach($category_list as $row)
	{		
		if(!empty($row['category_id']))
		{
			$fullCategory = getCategoryFullPath($row['category_id']);
			$category_ids = explode('/', $fullCategory);
			foreach($category_ids as $category_id)
			{
				if(empty($category_id) || $category_id == '0') continue;
				if(empty($data[$category_id])){
					$data[$category_id]['contents_cnt'] = 0;
					$data[$category_id]['original_total_size'] = 0;
					$data[$category_id]['proxy_total_size'] = 0;
					$data[$category_id]['last_regist_date'] = '';
				}
				$data[$category_id]['contents_cnt'] += empty($row['contents_cnt'])? 0 : $row['contents_cnt'];
				$data[$category_id]['original_total_size'] += empty($row['original_size'])? 0 : $row['original_size'];
				$data[$category_id]['proxy_total_size'] += empty($row['proxy_size'])? 0 : $row['proxy_size'];
				$data[$category_id]['last_regist_date'] = ($data[$category_id]['last_regist_date'] < $row['last_regist_date'])? $row['last_regist_date'] : $data[$category_id]['last_regist_date'];
			}
		}
	}

	foreach($category_list as $row)
	{
/*
		$fullCategory = getCategoryFullPath($row['category_id']);
		$category_ids = explode('/', $fullCategory);
		$category_ids = array_filter($category_ids);
		if(count($category_ids) > 1){
			$category_ids_str = implode('/', $category_ids);
			$category_ids = explode('/', $category_ids_str);

			////////////////////////////////

		}
		else{
			$list[$row['category_id']] = $node;
		}
*/
		if($row['depth'] == 2){
			$list[$row['category_id']]['icon'] = '/led-icons/folder.gif';
			$list[$row['category_id']]['expanded'] = true;
			$list[$row['category_id']]['title'] = $row['category_title'];
			$list[$row['category_id']]['category_id'] = $row['category_id'];
			$list[$row['category_id']]['contents_cnt'] = (string)$data[$row['category_id']]['contents_cnt'];
			$list[$row['category_id']]['original_total_size'] = (string)formatByte($data[$row['category_id']]['original_total_size']);
			$list[$row['category_id']]['proxy_total_size'] = (string)formatByte($data[$row['category_id']]['proxy_total_size']);
			$list[$row['category_id']]['last_regist_date'] = (empty($data[$row['category_id']]['last_regist_date']))? '' : date('Y-m-d', strtotime($data[$row['category_id']]['last_regist_date']));
			$list[$row['category_id']]['leaf'] = true;
		}
		else if($row['depth'] == 3){
			if(empty($list[$row['parent_id']])) $list[$row['parent_id']] = array();
			$list[$row['parent_id']]['children'][$row['category_id']]['icon'] = '/led-icons/folder.gif';
			$list[$row['parent_id']]['children'][$row['category_id']]['expanded'] = true;
			$list[$row['parent_id']]['children'][$row['category_id']]['title'] = $row['category_title'];
			$list[$row['parent_id']]['children'][$row['category_id']]['category_id'] = $row['category_id'];
			$list[$row['parent_id']]['children'][$row['category_id']]['contents_cnt'] = (string)$data[$row['category_id']]['contents_cnt'];
			$list[$row['parent_id']]['children'][$row['category_id']]['original_total_size'] = (string)formatByte($data[$row['category_id']]['original_total_size']);
			$list[$row['parent_id']]['children'][$row['category_id']]['proxy_total_size'] = (string)formatByte($data[$row['category_id']]['proxy_total_size']);
			$list[$row['parent_id']]['children'][$row['category_id']]['last_regist_date'] = (empty($data[$row['category_id']]['last_regist_date']))? '' : date('Y-m-d', strtotime($data[$row['category_id']]['last_regist_date']));
			$list[$row['parent_id']]['children'][$row['category_id']]['leaf'] = true;
		}
		else if($row['depth'] == 4){
			$list[getRootParentID($row['parent_id'])]['children'][$row['parent_id']]['children'][$row['category_id']] = $node;
			$list[getRootParentID($row['parent_id'])]['children'][$row['parent_id']]['children'][$row['category_id']]['icon'] = '/led-icons/folder.gif';
			$list[getRootParentID($row['parent_id'])]['children'][$row['parent_id']]['children'][$row['category_id']]['expanded'] = true;
			$list[getRootParentID($row['parent_id'])]['children'][$row['parent_id']]['children'][$row['category_id']]['title'] = $row['category_title'];
			$list[getRootParentID($row['parent_id'])]['children'][$row['parent_id']]['children'][$row['category_id']]['category_id'] = $row['category_id'];
			$list[getRootParentID($row['parent_id'])]['children'][$row['parent_id']]['children'][$row['category_id']]['contents_cnt'] = (string)$data[$row['category_id']]['contents_cnt'];
			$list[getRootParentID($row['parent_id'])]['children'][$row['parent_id']]['children'][$row['category_id']]['original_total_size'] = (string)formatByte($data[$row['category_id']]['original_total_size']);
			$list[getRootParentID($row['parent_id'])]['children'][$row['parent_id']]['children'][$row['category_id']]['proxy_total_size'] = (string)formatByte($data[$row['category_id']]['proxy_total_size']);
			$list[getRootParentID($row['parent_id'])]['children'][$row['parent_id']]['children'][$row['category_id']]['last_regist_date'] = (empty($data[$row['category_id']]['last_regist_date']))? '' : date('Y-m-d', strtotime($data[$row['category_id']]['last_regist_date']));
			$list[getRootParentID($row['parent_id'])]['children'][$row['parent_id']]['children'][$row['category_id']]['leaf'] = true;
		}

	}

	$result = array();
	$no = 1;
	foreach($list as $row){		
		$row['leaf'] = true;
		$children = getChildrenNode($row);
		$row['children'] = $children;
		if(!empty($children)) $row['leaf'] = false;

		$row['no'] = (string)$no;
		$result[] = $row;
		$no++;
	}

	echo json_encode($result);
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

function getCategoryFullPath($id)
{
	global $db;

	$parent_id = $db->queryOne("select parent_id from bc_category where category_id=".$id);
	if ($parent_id != -1 && $parent_id !== 0 && !is_null($parent_id))
	{
		$self_id = getCategoryFullPath($parent_id);
	}

	return $self_id.'/'.$id;
}

function formatByte($b, $p=null) {
    $units = array("B","KB","MB","GB","TB","PB","EB","ZB","YB");
    $c=0;

	if(empty($b) || $b < 1){
		return '';
	}
    else if(!$p && $p !== 0) {
        foreach($units as $k => $u) {
            if(($b / pow(1024,$k)) >= 1) {
                $r["bytes"] = $b / pow(1024,$k);
                $r["units"] = $u;
                $c++;
				$r_k = $b;
            }
        }
        return number_format($r["bytes"],2) . " " . $r["units"];
    } else {
        return number_format($b / pow(1024,$p), 2) . " " . $units[$p];
    }

}
?>