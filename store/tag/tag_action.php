<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

use Proxima\core\Session;

Session::init();
/*try
{
*/

	$user_id = Session::get('user')['user_id'];
	//$user_id = 'admin';
	$action = $_POST['action'];
	$cur_datetime = date('YmdHis');
	//$content_id = $_POST['content_id'];
	$content_ids = json_decode($_POST['content_id']);
	$tag_category_id = $_POST['tag_category_id'];
	
	/*
	if(is_null($user_id) || $user_id == 'temp')
	{
		throw new Exception ('재 로그인이 필요합니다');
	}
	*/

	switch($_POST['action'])
	{	
		
		case 'listing':
			$query = "SELECT * FROM BC_TAG_CATEGORY WHERE USER_ID = '$user_id' AND IS_DELETE= 'N' ORDER BY SHOW_ORDER ASC";
			$tag_content_list = $db->queryAll($query);
			$data = $tag_content_list;
		break;
		
		
		case 'get_tag_list_of_content':
			$content_id = $_POST['content_id'];
         	$query = "   SELECT  A.*
                          ,CASE
                           WHEN B.TAG_CATEGORY_ID = A.TAG_CATEGORY_ID THEN
                              1
                           ELSE
                              0
                        END AS IS_CHECKED
                  FROM    BC_TAG_CATEGORY A
                  LEFT OUTER JOIN BC_TAG B ON A.USER_ID = B.USER_ID AND '$content_id' = B.CONTENT_ID
                  WHERE   A.USER_ID = '$user_id'
                  AND     A.IS_DELETE= 'N'
                  ORDER BY A.SHOW_ORDER ASC
         ";
         $tag_content_list = $db->queryAll($query);
         $data = $tag_content_list;
      	break;
      	/*
		case 'get_list_tag_for_content':
			$query = "SELECT  A.*
					        ,CASE
					         WHEN B.TAG_CATEGORY_ID = A.TAG_CATEGORY_ID THEN
					            1
					         ELSE
					            0
					      END AS IS_CHECKED
					FROM    BC_TAG_CATEGORY A
					LEFT OUTER JOIN BC_TAG B ON A.USER_ID = B.USER_ID AND $content_id = B.CONTENT_ID
					WHERE   A.USER_ID = '$user_id'
					AND     A.IS_DELETE= 'N'
					AND ROWNUM <= 15
					ORDER BY A.SHOW_ORDER ASC";
			
			$tag_content_list = $db->queryAll($query);
			$data = $tag_content_list;
			break;
		*/	
		case 'change_tag_content':
			foreach ($content_ids as $content_id){
				
				$query = "SELECT * FROM BC_TAG WHERE CONTENT_ID = $content_id->content_id AND USER_ID = '$user_id'";
				$tag_content_list = $db->queryAll($query);
				
				if(count($tag_content_list)>0){
					//update value
					$query = "UPDATE BC_TAG SET TAG_CATEGORY_ID = $tag_category_id WHERE CONTENT_ID = $content_id->content_id AND USER_ID = '$user_id'";
					$db->exec($query);
					$data = 'update';
				}else{
					// insert new
					$query = "INSERT INTO BC_TAG (USER_ID, CONTENT_ID, TAG_CATEGORY_ID, SHOW_ORDER, CONTENT_TYPE)  values ('$user_id', '$content_id->content_id', '$tag_category_id','$cur_datetime' ,'M')";
					$db->exec($query);
					$data = 'insert new';
				}
			}
		break;
		
		case 'clear_tag_for_content':
			foreach ($content_ids as $content_id){
				$query = "DELETE FROM BC_TAG WHERE USER_ID = '$user_id' AND CONTENT_ID = $content_id->content_id";
				$db->exec($query);
				$data = "clear tag for content";
			}
			break;
			
		case 'delete_tag':
			$query = "UPDATE BC_TAG_CATEGORY SET IS_DELETE = 'Y' WHERE TAG_CATEGORY_ID = $tag_category_id AND USER_ID = '$user_id'";	
			$db->exec($query);
			$query = "DELETE FROM BC_TAG WHERE USER_ID = '$user_id' AND TAG_CATEGORY_ID = $tag_category_id";
			$db->exec($query);
			$data = "delete tag";
		break;

		case 'add_tag':
			$tag_category_title = $_POST['tag_category_title'];
			$tag_category_color = $_POST['tag_category_color'];
			$tag_id = getSequence('SEQ_BC_TAG_ID');
			$query = "
					INSERT INTO 
						BC_TAG_CATEGORY (
							TAG_CATEGORY_ID,
							TAG_CATEGORY_TITLE,
							USER_ID, 
							TAG_CATEGORY_COLOR, 
							IS_DELETE,
							SHOW_ORDER
							)  
					VALUES (
							'$tag_id',
							'$tag_category_title',
							'$user_id',
							'$tag_category_color',
							'N',
							(
								SELECT coalesce(max(SHOW_ORDER), 0)+1
									FROM BC_TAG_CATEGORY
									WHERE USER_ID = '$user_id'
									AND IS_DELETE = 'N'
							)
						)";
			$db->exec($query);
			$data = "add new tag";
		break;
		
		case 'update_tag':
			$tag_category_title = $_POST['tag_category_title'];
			$tag_category_color = $_POST['tag_category_color'];
			$query = "UPDATE BC_TAG_CATEGORY SET TAG_CATEGORY_TITLE = '$tag_category_title', TAG_CATEGORY_COLOR = '$tag_category_color' WHERE TAG_CATEGORY_ID = $tag_category_id AND USER_ID = '$user_id'";
			$db->exec($query);
			$data = "update tag";
		break;
		
		case 'update_order_tag':
			$tag_id_order = json_decode($_POST['tag_id_order']);
			//print_r($tag_id_order);
			for($i = 0 ; $i < count($tag_id_order); $i++){
				$order = $i +1;
				$query = "
						UPDATE BC_TAG_CATEGORY 
							SET SHOW_ORDER = '$order' 
						WHERE TAG_CATEGORY_ID = $tag_id_order[$i] 
						AND USER_ID = '$user_id'
						";
				$db->exec($query);
			}
			break;
		default:
			throw new Exception ('알수 없는 action 입니다');
		break;
	}


	echo json_encode(array(
		'success' => true,
		'data' => $data,
		'action' => $action
	));

/*}
catch (Exception $e)
{
	$msg = $e->getMessage();
	echo json_encode(array(
		'success' => false,
		'msg' => $msg
	));
}*/

?>