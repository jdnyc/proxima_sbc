<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lang.php');
fn_checkAuthPermission($_SESSION);

//header("Content-type: application/json; charset=UTF-8");
// $user_id = trim($_REQUEST['user_id']);
$user_id = $_SESSION['user']['user_id'];
//$password = md5(trim($_REQUEST['password']));

try
{

	$user = $mdb->queryRow("select email, phone, user_nm, job_position, dept_nm, lang from bc_member where user_id='$user_id' ");
	if($user_id == 'admin') {
	    $user_product_group = $mdb->queryAll("select ca.category_title from bc_category ca, path_mapping p where ca.category_id = p.category_id order by ca.category_title");
	} else {
	    $user_product_group = $mdb->queryAll("select bc.category_title from bc_category bc, user_mapping um where bc.category_id = um.category_id and um.user_id = '$user_id' order by bc.category_title");
	}
        $product_group = null;
        foreach ($user_product_group as $group) {
            if(!empty($product_group)) {
                $product_group = $product_group.'<p>'.$group['category_title'].'</p>';
            } else {
                $product_group = '<p>'.$group['category_title'].'</p>';
            }
        }

        $user['programs'] = $product_group;
       
        $user_top_menu_query = "	SELECT	TOP_MENU_MODE
												FROM		BC_MEMBER_OPTION
													WHERE	MEMBER_ID = (
														SELECT	MEMBER_ID
											  			FROM		BC_MEMBER
											  			WHERE	USER_ID =  '".$user_id."'
										  			)";
        
        $user_top_menu = $mdb->queryOne($user_top_menu_query);
        $user['user_top_menu'] = $user_top_menu;
        
        $action_icon_slide_query = "	SELECT	ACTION_ICON_SLIDE_YN
												FROM		BC_MEMBER_OPTION
													WHERE	MEMBER_ID = (
														SELECT	MEMBER_ID
											  			FROM		BC_MEMBER
											  			WHERE	USER_ID =  '".$user_id."'
										  			)";
        
        $action_icon_slide = $mdb->queryOne($action_icon_slide_query);
        $user['action_icon_slide_yn'] = $action_icon_slide;

        $first_page_query = "	SELECT	FIRST_PAGE
								FROM		BC_MEMBER_OPTION
								WHERE	MEMBER_ID = (
									SELECT	MEMBER_ID
						  			FROM		BC_MEMBER
						  			WHERE	USER_ID =  '".$user_id."'
					  			)";
        
        $first_page = $mdb->queryOne($first_page_query);
        $user['first_page'] = trim($first_page);
		$user['user_id'] = $_SESSION['user']['user_id'];
		$user['check_pw'] = $arr_sys_code['check_password_yn']['use_yn'];
		// $user['user_kor_nm'] = $_SESSION['user']['KOR_NM'];
//	$result_email = $mdb->queryOne("select email from bc_member where user_id=$user_id");

		echo json_encode(array(
			'success' => true,
			'data' => $user,
		));

}
catch (Exception $e)
{
	echo json_encode(array(
		'success' => false,
		'msg' => $e->getMessage()
	));
}

?>
