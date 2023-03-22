<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/db.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

$category_id = $_POST['category_id'];
$ud_content_id = $_POST['ud_content_id'];

try
{
	
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
	$v_result = $db->queryAll($category_query);

	$arrayCategories = array();
		foreach ($v_result as $row){
			$arrayCategories[$row['category_id']] = array("PARENT_ID" => $row['parent_id'], "CATEGORY_TITLE" =>$row['category_title'], "CATEGORY_ID" =>$row['category_id']);
		}

	$result = createTree($category_id, $arrayCategories, 0);
	//$result_data['categories'] = createTree($array, 0);
	//file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/test_'.date('Ymd').'.log', date("Y-m-d H:i:s\t")." ::xxxxxx: mov_view_json -> category_id 00000000:::"."\r\n".$category_id."\r\n\n", FILE_APPEND);
	//file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/test_'.date('Ymd').'.log', date("Y-m-d H:i:s\t")." ::xxxxxx: mov_view_json -> result :::"."\r\n".print_r($result,true)."\r\n\n", FILE_APPEND);

	echo json_encode(array(
		'success'=>'true',
		'data'=>$result,
	));
}
catch (Exception $e)
{
	echo '{"success":false, "msg": "'.$db->last_query.'"}';
}
function createTree($category_id, $array, $currentParent, $currLevel = 0, $prevLevel = -1) {
		global $result_create_tree;
		foreach ($array as $categoryId => $category) {

			if ($currentParent == $category['PARENT_ID']) {
				
				if ($currLevel > $prevLevel) $result_create_tree .= ' <ul id="categories" class="dtree_categories"> ';
		
				if ($currLevel == $prevLevel) $result_create_tree .= ' </li> ';
		
				//echo '<li> <label for="subfolder2">'.$category['CATEGORY_TITLE'].'</label> <input type="checkbox" id="subfolder2"/>';
				//file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/test_'.date('Ymd').'.log', date("Y-m-d H:i:s\t")." ::xxxxxx: mov_view_json -> category_id 1111111111:::"."\r\n".$category_id."\r\n\n", FILE_APPEND);
				//file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/test_'.date('Ymd').'.log', date("Y-m-d H:i:s\t")." ::xxxxxx: mov_view_json -> category ['CATEGORY_ID'] :::"."\r\n".$category ['CATEGORY_ID']."\r\n\n", FILE_APPEND);
				if ($category ['CATEGORY_ID'] == $category_id){
					$result_create_tree .= ' <li class="current_category"><a class="text_tree">' . $category ['CATEGORY_TITLE'] . '<p id="user_category_id_'.$category ['CATEGORY_ID'].'" class="text_category_id display_none">' . $category ['CATEGORY_ID'].'</p>';
					$result_create_tree .= '<p id="user_category_id_'.$category ['CATEGORY_TITLE'].'" class="text_category_title display_none">' . $category ['CATEGORY_TITLE'].'</p>';
					$category_full_path	= getCategoryFullPath($category ['CATEGORY_ID']);
					$result_create_tree .= '<p  class="text_category_full_path display_none">' . $category_full_path .'</p>';
					$result_create_tree .= '</a> ';
				} else {
					$result_create_tree .= ' <li><a class="text_tree">' . $category ['CATEGORY_TITLE'] . '<p id="user_category_id_'.$category ['CATEGORY_ID'].'" class="text_category_id display_none">' . $category ['CATEGORY_ID'].'</p>';
					$result_create_tree .= '<p id="user_category_id_'.$category ['CATEGORY_TITLE'].'" class="text_category_title display_none">' . $category ['CATEGORY_TITLE'].'</p>';
					$category_full_path	= getCategoryFullPath($category ['CATEGORY_ID']);
					$result_create_tree .= '<p  class="text_category_full_path display_none">' . $category_full_path .'</p>';
					$result_create_tree .= '</a> ';
				}

				if ($currLevel > $prevLevel) { $prevLevel = $currLevel; }
		
				$currLevel++;
		
				createTree ($category_id, $array, $categoryId, $currLevel, $prevLevel);
		
				$currLevel--;
			}
		
		}
		
		if ($currLevel == $prevLevel) $result_create_tree .= ' </li> </ul> '; //echo " </li> </ul> ";
		return $result_create_tree;
	}
?>