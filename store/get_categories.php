<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
fn_checkAuthPermission($_SESSION);
$request_type = $_REQUEST['type'];


if (is_null($request_type)) {
    $user_id           = $_REQUEST['user_id'];
    $node           = $_REQUEST['node'];
	$path			= $_REQUEST['path'];
    $ud_content_id  = $_REQUEST['ud_content_id'];
	$ud_content_tab = $_REQUEST['ud_content_tab'];
	$useWholeCategory = $_REQUEST['useWholeCategory'];
    $groups_array   = getGroups($_SESSION['user']['user_id']);

	$category_grant = accessGroupGrant($groups_array);

	$is_mapping_category = false;

	if(!empty($ud_content_id) && $_REQUEST['node'] == 0) {
		$category_id = $db->queryOne("
							SELECT	C.CATEGORY_ID
							FROM	BC_CATEGORY C, BC_CATEGORY_MAPPING CM
							WHERE	CM.UD_CONTENT_ID = '$ud_content_id'
							AND		CM.CATEGORY_ID = C.CATEGORY_ID AND C.PARENT_ID != '-1'
						");

		if($category_id !== null) {
			$node = $category_id;
			$is_mapping_category = true;
		}
	}


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

	$user_id = empty($_SESSION['user']['user_id'])? $user_id : $_SESSION['user']['user_id'];

	if (!empty($ud_content_id) && $_REQUEST['node'] == 0 && $is_mapping_category) {
		$query = "select * from bc_category where category_id = ".$node." order by show_order";
	} else {
		$query = "select * from bc_category where parent_id = ".$node." order by show_order";
    }
    
    if( $node == 200 ){
        // $hasAdmin =  \Api\Models\User::where('user_id',$user_id )->first()->hasAdminGroup();      
        // if($hasAdmin){
           
            $query = "SELECT c.* FROM BC_CATEGORY c JOIN FOLDER_MNG f ON c.CATEGORY_ID=f.CATEGORY_ID WHERE c.PARENT_ID=".$node." and f.DELETED_AT IS NULL and f.USING_YN='Y' ORDER BY c.CATEGORY_TITLE";
        // }else{
        //     $query = "SELECT c.* FROM BC_CATEGORY c 
        //     JOIN FOLDER_MNG f ON (c.CATEGORY_ID=f.CATEGORY_ID)
        //     JOIN  ( SELECT * FROM FOLDER_MNG_USER UNION SELECT * FROM FOLDER_MNG_OWNER_USER ) u ON (f.id=u.folder_id)
        //     WHERE c.PARENT_ID=".$node."
        //     and f.DELETED_AT IS NULL 
        //     and f.USING_YN='Y' 
        //     AND u.user_id='$user_id'
        //     ORDER BY c.CATEGORY_ID";
        // }
    }
	
	$categories = $mdb->queryAll($query);

    foreach ($categories as $category) {
        if (($_REQUEST['mode'] != 'all')
                && $_REQUEST['mode'] != 'cg'
                && $category['category_id'] == '5393760') {
            continue;
        }

        if (($_REQUEST['mode'] != 'all')
                && $_REQUEST['mode'] != 'cg'
                && $category['parent_id'] != '0'
                && $category['code'] == 'last' ) {
            continue;
        }

        //관리자가 아니고 제작그룹일땐 오직 해당 프로그램만..
        $node_category_id = $category['category_id'];
		$node_grant_array = set_category_access_grant($node_category_id, $category_grant, $category_grant_array, $ud_content_id );
        $expanded  = false;

        if ($node_grant_array['read'] == 0) {
            $node_grant_array['hidden'] = 1;
        }

		if ($category['status'] != 'accept' && $category['status'] != '') {
			$node_grant_array['hidden'] = true;
		}
		if($node_grant_array['read'] == 1) {
			$chk_category = $db->queryOne("select count(category_id) from bc_category where parent_id = '0' and category_id = '$node_category_id'");
			if($chk_category != 0 ) {
				$expanded = true;
			} else {
				$expanded = false;
			}
		}

        if (isset($_POST['path'])) {
			if((boolean)$category['no_children']){
					$data[] = array(
					'id' => $category['category_id'],
					'code' => $category['code'],
					'text' => $category['category_title'],
					'read' => $node_grant_array['read'],
					'add' => $node_grant_array['add'],
					'edit' => $node_grant_array['edit'],
					'del' => $node_grant_array['del'],
					'hidden' => $node_grant_array['hidden'],
					'leaf' => (boolean)$category['no_children'],
					'qtip' => $category['category_title'],
					'expanded' => $expanded
				);
			}else{
				$data[] = array(
					'id' => $category['category_id'],
					'code' => $category['code'],
					'text' => $category['category_title'],
					'read' => $node_grant_array['read'],
					'add' => $node_grant_array['add'],
					'edit' => $node_grant_array['edit'],
					'del' => $node_grant_array['del'],
					'hidden' => $node_grant_array['hidden'],
					'leaf' => (boolean)$category['no_children'],
					'qtip' => $category['category_title'],
					'expanded' => $expanded
				);
			}
		} else {
			if((boolean)$category['no_children']){
				$data[] = array(
                    'id' => $category['category_id'],
                    'code' => $category['code'],
                    'text' => $category['category_title'],
                    'read' => $node_grant_array['read'],
                    'add' => $node_grant_array['add'],
                    'edit' => $node_grant_array['edit'],
                    'del' => $node_grant_array['del'],
                    'hidden' => $node_grant_array['hidden'],
					'leaf' => (boolean)$category['no_children'],
					'qtip' => $category['category_title'],
					'expanded' => $expanded
                );
			}else{
				$data[] = array(
					'id' => $category['category_id'],
					'code' => $category['code'],
					'text' => $category['category_title'],
					'read' => $node_grant_array['read'],
					'add' => $node_grant_array['add'],
					'edit' => $node_grant_array['edit'],
					'del' => $node_grant_array['del'],
					'hidden' => $node_grant_array['hidden'],
					'leaf' => (boolean)$category['no_children'],
					'qtip' => $category['category_title'],
					'expanded' => $expanded
				);
			}
        }
    }
} else if($_REQUEST['type'] == 'main' ) {
    $node  = $_REQUEST['node'];
    $user_id = $_SESSION['user']['user_id'];

    if( !is_numeric($node) || $node == '0' )
    {
        if( $_SESSION['user']['is_admin'] == 'Y' )
        {
            $lists = $db->queryAll("
						SELECT	C.*
						FROM	BC_CATEGORY C,
								PATH_MAPPING PM
						WHERE	C.CATEGORY_ID=PM.CATEGORY_ID
						AND		C.PARENT_ID = '$node'
						ORDER BY C.CATEGORY_TITLE ASC
					");
		} else {
			$lists = $db->queryAll("
						SELECT	C.*
						FROM	BC_CATEGORY C,
								PATH_MAPPING PM,
								USER_MAPPING UM
						WHERE	C.CATEGORY_ID = PM.CATEGORY_ID
						AND		UM.CATEGORY_ID = C.CATEGORY_ID
						AND		C.PARENT_ID = '$node'
						AND		UM.USER_ID = '$user_id'
						ORDER BY C.CATEGORY_TITLE ASC
					");

		}

		foreach ($lists as $list) {
			$data[] = array(
				'id' => $list['category_id'],
				'type' => '',
				'icon' => '/led-icons/folder.gif',
				'text' => $list['category_title'],
				'leaf' => (boolean)$list['no_children']
			);
		}
	} else {
		$lists = $db->queryAll("
					SELECT	C.*
					FROM	BC_CATEGORY C
								LEFT JOIN	SUBPROG_MAPPING SM
								ON		C.CATEGORY_ID = SM.CATEGORY_ID
					WHERE	C.PARENT_ID = $node
					ORDER BY	C.SHOW_ORDER ASC
				");

		foreach ($lists as $list) {
			$data[] = array(
				'id' => $list['category_id'],
				'type' => '',
				'icon' => '/led-icons/folder.gif',
				'text' => $list['category_title'],
				'leaf' => (boolean)$list['no_children']
			);
		}
	}
} else if( $_REQUEST['type'] == 'last' ) {
	$user_id = $_SESSION['user']['user_id'];

    $node = $_REQUEST['node'];
    $ud_content_id  = $_REQUEST['ud_content_id'];

    if( $_SESSION['user']['is_admin'] == 'Y' )
    {
        $categories = $db->queryAll("
		SELECT	C.*
		FROM	BC_CATEGORY C
		WHERE	C.PARENT_ID = $node
		AND		CODE = 'LAST'
		ORDER BY C.CATEGORY_TITLE ASC
        ");
    }
    else if( !is_numeric($node) || $node == '0' )
    {
        $categories = $db->queryAll("
						SELECT	C.*
						FROM	BC_CATEGORY C,
								PATH_MAPPING PM,
								USER_MAPPING UM
						WHERE	C.CATEGORY_ID = PM.CATEGORY_ID
						AND		UM.CATEGORY_ID = C.CATEGORY_ID
						AND		C.PARENT_ID = $node
						AND		CODE = 'LAST'
						AND		UM.USER_ID = '$user_id'
						ORDER BY C.CATEGORY_TITLE ASC
        ");
    }
    else
    {
        $categories = $db->queryAll("
						SELECT	C.*
						FROM	BC_CATEGORY C
						WHERE	C.PARENT_ID = $node
						AND		CODE = 'LAST'
						ORDER BY C.CATEGORY_TITLE ASC
        ");
    }

	foreach ($categories as $category) {
		if($_REQUEST['mode'] != 'cg' && $category['category_id'] == '4801860' ) {
			continue;
		}

		if( $category['no_children'] ) {

            $data[] = array(
                'id' => $category['category_id'],
                'code' => $category['code'],
                'text' => $category['category_title'],
                'icon' => '/led-icons/folder.gif',
                'read' => 1,
                'add' => 0,
                'edit' => 0,
                'del' => 0,
                'hidden' => 0,
                'leaf' => (boolean)$category['no_children']
            );
		} else {
            $data[] = array(
                'id' => $category['category_id'],
                'code' => $category['code'],
                'text' => $category['category_title'],
                'read' => 1,
                'add' => 0,
                'edit' => 0,
                'del' => 0,
                'hidden' => 0,
                'leaf' => (boolean)$category['no_children']
            );
        }
    }
} else if( $_REQUEST['type'] == 'review' ) {
	$lists = $db->queryAll("
				SELECT	M.*
				FROM	BC_MEMBER M,
						BC_MEMBER_GROUP MG,
						BC_MEMBER_GROUP_MEMBER MGM
				WHERE	M.MEMBER_ID = MGM.MEMBER_ID
				AND		MG.MEMBER_GROUP_ID = MGM.MEMBER_GROUP_ID
				AND		MG.MEMBER_GROUP_ID = '".REVIEW_GROUP."'
				ORDER BY M.MEMBER_ID ASC
    ");

	foreach ($lists as $list) {
        $data[] = array(
            'id' => $list['user_id'],
            'icon' => '/led-icons/user.png',
            'text' => $list['user_nm'].'('.$list['dept_nm'].')',
            'leaf' => true
        );
    }
} else if ($_REQUEST['type'] == 'product') {
    $node  = $_REQUEST['node'];
    $user_id = $_SESSION['user']['user_id'];
    //$user_id = '20190';

    $logger->addInfo($node);

    if ( ! is_numeric($node) || $node == '0') {
        $categories = $db->queryAll("
						SELECT	C.*
						FROM	BC_CATEGORY C, PATH_MAPPING P
						WHERE	C.CATEGORY_ID = P.CATEGORY_ID
						AND		C.PARENT_ID = $node
						ORDER BY C.CATEGORY_TITLE
					");
        foreach ($categories as $list) {
            $data[] = array(
                'id' => $list['category_id'],
                'type' => '',
                'text' => $list['category_title'],
                'leaf' => false
            );
        }
    } else {
        $lists = $db->queryAll("
					SELECT	M.*
					FROM	BC_MEMBER M,
							USER_MAPPING UM
					WHERE	M.USER_ID=UM.USER_ID
					AND		UM.CATEGORY_ID = $node
					ORDER BY M.MEMBER_ID ASC
        ");

        foreach ($lists as $list) {
            if ($list['extra_vars']== 'temp') {

                $image = '/led-icons/status_online.png';

                $is_temp = true;
                $name = '[임시]'.$list['user_nm'];
                $data[] = array(
                    'id' => $node.'-'.$list['user_id'],
                    'icon' => $image,
                    'is_temp' => $is_temp,
                    'text' => $name,
                    'leaf' => true
                );
            } else {
                $image = '/led-icons/user.png';
                $is_temp = false;

                $data[] = array(
                    'id' => $node.'-'.$list['user_id'],
                    'icon' => $image,
                    'is_temp' => $is_temp,
                    'text' => $list['user_nm'].'('.$list['dept_nm'].')',
                    'leaf' => true
                );
            }
        }
    }
} else if ($_REQUEST['type'] == 'ud_regist') {
    $mode = $_REQUEST['mode'];
    $node = 0;
    $user_id = $_SESSION['user']['user_id'];

	if( $_SESSION['user']['is_admin'] == 'Y' ) {

                $query = "
				SELECT	C.*,
						P.QUOTA,
						P.USAGE
				FROM	BC_CATEGORY C,
						PATH_MAPPING P
				WHERE	C.CATEGORY_ID = P.CATEGORY_ID
				AND		C.PARENT_ID = $node
				ORDER BY C.CATEGORY_TITLE
                    ";

		if(!empty($mode) && $mode == 'audio') {
                    $node = 5393760;
		    $query = "
		    	SELECT *
			FROM BC_CATEGORY
			WHERE PARENT_ID = $node
			ORDER BY CATEGORY_TITLE
		    ";
        }

        $lists = $db->queryAll($query);
	} else {
            $query = "
				SELECT	C.*,
						P.QUOTA,
						P.USAGE
				FROM	BC_CATEGORY C,
						PATH_MAPPING P,
						USER_MAPPING UM
				WHERE	C.CATEGORY_ID = P.CATEGORY_ID
				AND		UM.CATEGORY_ID = C.CATEGORY_ID
				AND		C.PARENT_ID = $node
				AND		UM.USER_ID = $user_id
				ORDER BY C.CATEGORY_TITLE
        ";

            if(!empty($mode) && $mode == 'audio') {
                $node = 5393760;
	    	$query = "
			SELECT *
			FROM BC_CATEGORY
			WHERE PARENT_ID = $node
			ORDER BY CATEGORY_TITLE
		";
            }
        $lists = $db->queryAll($query);
    }

	foreach ($lists as $list) {
        if(!is_null($list['usage']) && !is_null($list['quota'])) {
        $c_title = $list['category_title'].' - ('.$list['usage'].'/'.$list['quota'].')';
        } else {
        $c_title = $list['category_title'];
        }

        $data[] = array(
            'id' => $list['category_id'],
            'icon' => '/led-icons/folder.gif',
            'text' => $c_title,
            'leaf' => true
        );
    }
} else if ($_REQUEST['type'] == 'wholeCategory') {
	/**/
	$node           = $_REQUEST['node'];
	$path			= $_REQUEST['path'];
    $ud_content_id  = $_REQUEST['ud_content_id'];
	$ud_content_tab = $_REQUEST['ud_content_tab'];
    $groups_array   = getGroups($_SESSION['user']['user_id']);

	$category_grant = accessGroupGrant($groups_array);
	
	$is_mapping_category = false;

    //노드별 권한 추가 2001-11-03 by 이성용
	//부모노드에 권한이 존재하면 부모노드 권한 승계 / 파라미터가 없다면 권한 X

	$category_grant_array = array(
		'read' => 1,
		'add' => 0,
		'edit' => 0,
		'del' => 0,
		'hidden' => 0
	);

	if($node != 0) {
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
	}

	$query = "select * from bc_category where parent_id = ".$node." order by show_order";
	if(defined('CUSTOM_ROOT') && class_exists('\ProximaCustom\core\CategoryCustom')) {
		$query = \ProximaCustom\core\CategoryCustom::buildSelectCategoryQuery($node);
	} 

	$categories = $mdb->queryAll($query);	


	if(defined('CUSTOM_ROOT') && class_exists('\ProximaCustom\core\CategoryCustom')) {
		$categories = \ProximaCustom\core\CategoryCustom::getVisibleCategories($categories, $node);
	} 

    foreach ($categories as $category) {       
		$node_category_id = $category['category_id'];
		//$map_ud_content = $db->queryOne("SELECT UD_CONTENT_ID FROM BC_CATEGORY_MAPPING WHERE CATEGORY_ID = $node_category_id");
		//if(is_set($map_ud_content)) $ud_content_id = $map_ud_content;
		$node_grant_array = set_category_access_grant($node_category_id, $category_grant, $category_grant_array, $ud_content_id );
		$expanded  = false;
		
        if ($node_grant_array['read'] == 0) {
            $node_grant_array['hidden'] = 1;
        }

		if ($category['status'] != 'accept' && $category['status'] != '') {
			$node_grant_array['hidden'] = true;
		}
		if($node_grant_array['read'] == 1) {
			$chk_category = $db->queryOne("
								select	category_id
								from	bc_category
								where	parent_id = '0'
								and		category_id = '$node_category_id'
							");

			if(count($chk_category) > 0 ) {
				$expanded = true;
			} else {
				$expanded = false;
			}
		}

		$trg_ud_contents = $db->queryAll("select ud_content_id from bc_category_mapping where category_id = $node_category_id order by ud_content_id");
		if(count($trg_ud_contents) > 1) {
			$tmp_trg_ud_contents = array();
			foreach($trg_ud_contents as $tmp_ud_content) {
				array_push($tmp_trg_ud_contents, $tmp_ud_content['ud_content_id']);
			}
			$trg_ud_content = implode(',', $tmp_trg_ud_contents);
		} else {
			$trg_ud_content = $trg_ud_contents[0]['ud_content_id'];
		}

		if(empty($trg_ud_content) && !empty($_REQUEST['trg_ud_content'])) {
			$trg_ud_content = $_REQUEST['trg_ud_content'];
		}

        if (isset($_POST['path'])) {
			if((boolean)$category['no_children']){
					$data[] = array(
					'id' => $category['category_id'],
					'code' => $category['code'],
					'text' => $category['category_title'],
					'read' => $node_grant_array['read'],
					'add' => $node_grant_array['add'],
					'edit' => $node_grant_array['edit'],
					'del' => $node_grant_array['del'],
					'hidden' => $node_grant_array['hidden'],
					'leaf' => (boolean)$category['no_children'],
					'qtip' => $category['category_title'],
					'expanded' => $expanded,
					'trg_ud_content' => $trg_ud_content
				);
			}else{
				$data[] = array(
					'id' => $category['category_id'],
					'code' => $category['code'],
					'text' => $category['category_title'],
					'read' => $node_grant_array['read'],
					'add' => $node_grant_array['add'],
					'edit' => $node_grant_array['edit'],
					'del' => $node_grant_array['del'],
					'hidden' => $node_grant_array['hidden'],
					'leaf' => (boolean)$category['no_children'],
					'qtip' => $category['category_title'],
					'expanded' => $expanded,
					'trg_ud_content' => $trg_ud_content
				);
			}
		} else {
			if((boolean)$category['no_children']){
				$data[] = array(
                    'id' => $category['category_id'],
                    'code' => $category['code'],
                    'text' => $category['category_title'],
                    'read' => $node_grant_array['read'],
                    'add' => $node_grant_array['add'],
                    'edit' => $node_grant_array['edit'],
                    'del' => $node_grant_array['del'],
                    'hidden' => $node_grant_array['hidden'],
					'leaf' => (boolean)$category['no_children'],
					'qtip' => $category['category_title'],
					'expanded' => $expanded,
					'trg_ud_content' => $trg_ud_content
                );
			}else{
				$data[] = array(
					'id' => $category['category_id'],
					'code' => $category['code'],
					'text' => $category['category_title'],
					'read' => $node_grant_array['read'],
					'add' => $node_grant_array['add'],
					'edit' => $node_grant_array['edit'],
					'del' => $node_grant_array['del'],
					'hidden' => $node_grant_array['hidden'],
					'leaf' => (boolean)$category['no_children'],
					'qtip' => $category['category_title'],
					'expanded' => $expanded,
					'trg_ud_content' => $trg_ud_content
				);
			}
        }
    }
}

echo json_encode($data);
?>