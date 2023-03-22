<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

//토픽 리스트
$post_category_id = $_POST['node'];
$page_mode = $_POST['page_mode'];
$topic_root_category_id = -1;
$user_id = $_SESSION['user']['user_id'];
$is_admin = $_SESSION['user']['is_admin'];
$node_list = array();

if (empty($post_category_id) 
		|| strstr($post_category_id, 'xnode') !== false) {
    $post_category_id = $topic_root_category_id;
}

$today = date_create(date('Ymd'));

$query = "select c.category_title, c.no_children, ct.*
	from bc_category c,
		bc_category_topic ct
	where c.is_deleted = 0
	  and c.category_id=ct.category_id
		and c.parent_id = '$post_category_id'
	order by c.show_order asc";
$category_infos = $db->queryAll($query);

$no = 1;

foreach ($category_infos as $info) {
	$node = array();
	$child_no = 1;
	$leaf = true;
    $hidden = false;
    $category_id = $info['category_id'];
    $category_title = $info['category_title'];
	$status = $info['status'];
	$no_children = $info['no_children'];
	$expired_date = $info['expired_date'];
	$broad_date = $info['broad_date'];
	$contents = $info['contents'];
	$req_user_id = $info['req_user_id'];

	//토픽 우클릭 메뉴 기본권한
	$topic_add = 0;
	$topic_edit = 0;
	$topic_del = 0;

    if ( ! empty($category_id)) {
        $category_path = getCategoryFullPath($category_id);
        $root_category_id = $topic_root_category_id;
        $arr_full_path = explode("/".$root_category_id."/", $category_path);
        if (count($arr_full_path) > 1) {
            $mapping_category_path = $root_category_id.'/'.$arr_full_path[1];
        } else {
            $mapping_category_path = $root_category_id.'/'.$arr_full_path[0];
        }
        $catPathTitle = getCategoryPathTitle($mapping_category_path, '>');
    } else {
        $category_path = '';
        $catPathTitle = '';
    }

	$node['exp_date'] = '-';
	if (empty($expired_date)) {
		//$node['exp_date'] = '-';
	} else {
		//$node['exp_date'] = substr($expired_date,0,4).'-'.substr($expired_date,4,2).'-'.substr($expired_date,6,2);
	}

	$dc_expired_date = date_create($expired_date);
	$diff_date = date_diff($today, $dc_expired_date);
	$remain_expired_date = $diff_date->format('%R%a');

	if ($remain_expired_date > 365) {
		$remain_expired_date = $diff_date->format('%y');
        $expired_date_postfix = '년';
	} else if ($remain_expired_date < -365) {
        $remain_expired_date = $diff_date->format('%R%y');
        $expired_date_postfix = '년';
    } else if ($remain_expired_date < -0) {
        $remain_expired_date = $diff_date->format('%R%a');
        $expired_date_postfix = '일';
    } else {
        $remain_expired_date = $diff_date->format('%a');
        $expired_date_postfix = '일';
    }

	$expired_date_info = new DateTime($expired_date);
	$expired_date = $expired_date_info->format('Y-m-d');
	if ($broad_date != '') {
		$broad_date_info = new DateTime($broad_date);
		$broad_date = $broad_date_info->format('Y-m-d');
	} else {
		$broad_date = '-';
	}

	if ($contents == '') $contents = '-';

	//토픽 권한 판단
	$topic_add = 1;
	if ($user_id == $req_user_id || $is_admin == 'Y') {
		$topic_del = 1;
		$topic_edit = 1;
	} else {
		$topic_del = 1;
		$topic_edit = 1;
	}
	
	//일단 1뎁스까지 보이도록
	//$node['expanded'] = false;
	
	switch($status) {
		case 'accept':
			$status = '<font color=blue>승인</font>';
		break;
		case 'decline':
			$status = '<font color=red>반려</font>';
			$hidden = true;
		break;
		default:
			$status = '-';
			$hidden = true;
//			//사용자 자신이 신청한 토픽은 보이도록. NLE에선 자기것이라도 승인 아니면 안보이게
//			if( $user_id == $req_user_id && $page_mode != 'nle') {
//				$hidden = false;
//			} else {
//				$hidden = true;
//			}			
	}

	if ($remain_expired_date < 0) {
//		$hidden = true;
	}

	if ($page_mode == 'manage') {
		$hidden = false;
	}
	
	$node['hidden'] = $hidden;
	$node['id'] = $category_id;
	$node['category_id'] = $category_id;
    $node['title'] = $category_title;
	$node['text'] = $category_title;
	$node['expired_date'] = $expired_date;
	$node['remain_expired_date'] = $remain_expired_date.$expired_date_postfix;
	$node['broad_date'] = $broad_date;
	$node['contents'] = $contents;
	$node['category_path'] = $category_path;
    $node['catPathTitle'] = $catPathTitle;
    $node['tr_category'] = $tr_category;
    $node['tr_category_nm'] = $tr_category_nm; 
	$node['editable'] = true;
	$node['status'] = $status;
	$node['no'] = $no;
	$node['icon'] = '/led-icons/folder.gif';
	$node['req_user_id'] = $req_user_id;
	$node['topic_add'] = $topic_add;
	$node['topic_edit'] = $topic_edit;
	$node['topic_del'] = $topic_del;

	array_push($node_list, $node);

	$no++;
}

//루트노드 자신도 보이도록
if ($post_category_id == $topic_root_category_id) {
	$root = $db->queryRow("select * from bc_category where category_id='".$post_category_id."'");
	$node_list = array(array(
		'id' => $root['category_id'],
		'category_id' => $root['category_id'],
		'category_path' => '/'.$root['category_id'],
		'title' => $root['category_title'],
		'text' => $root['category_title'],
		'catPathTitle' => $root['category_title'],
		'expanded' => true,
		'no' => 0,
		'icon' => '/led-icons/folder.gif',
		'leaf' => false,
		'children' => $node_list,
		'tr_category' => '',
		'tr_category_nm' => '',
		'topic_add' => 1,
		'topic_edit' => 0,
		'topic_del' => 0
	));
}

echo json_encode($node_list);

?>