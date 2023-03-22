<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

$user_id = $_SESSION['user']['user_id'];

try
{

	$query = "
	select
		c.category_id category_id,
		c.category_title title,
		p.path ,
		p.quota,
		p.usage ,
		c.show_order sort,
		count(u.user_id) cnt
	from
		BC_CATEGORY c,
		PATH_MAPPING p,
		user_mapping u
	where
		p.category_id=c.category_id
	and u.category_id(+)=c.category_id
	group by c.category_id, c.category_title, p.path,p.quota,		p.usage , c.show_order order by c.show_order";

	$result_list = $db->queryAll($query);

	$user_list = $db->queryAll("
	select
		c.category_id category_id,
		m.user_nm title,
		u.user_id,
		m.user_nm name,
		m.dept_nm,
		m.breake,
		m.dep_tel_num
	from
		BC_CATEGORY c,
		PATH_MAPPING p,
		user_mapping u,
		bc_member m
	where
		p.category_id = c.category_id
	and c.category_id = u.category_id(+)
	and m.user_id=u.user_id");

	$no = 1;
	$node_list = array();
	$node = array();
	$child = array();

	$excel_array = array();

	foreach ($result_list as $item) {
		$node = array();
		$child_list = array();
		$child_no = 1;
		$leaf = true;

		$node['no'] = (string)$no;
		$node['icon'] = '/led-icons/folder.gif';
		$node['expanded'] = true;

		$node['id'] = $item['category_id'];

		$excel_array [] = array(
			'카테고리' => $item['title'],
			'폴더명' =>  $item['path'],
			'사용자' => '',
			'사번' => '',
			'부서명' => '',
			'사용자 정보' =>  $item['cnt'].' 명'
		);

		foreach ($item as $key => $value) {
			if($key == 'cnt') {
				$node[$key] = $value.' 명';
			} else {
				$node[$key] = $value;
			}
		}

		foreach ($user_list as $user) {

			//사용자 정보 추가
			if ($user['category_id'] == $item['category_id']) {

				//사용자 노드가 있다면 false
				$leaf = false;
				//사용자 노드 초기화
				$child = array();

				$child['id'] = $user['category_id'].'-'.$user['user_id'];

				//로우 넘버
				$child['no'] = (string)$child_no;
				//아이콘
				$child['icon'] = '/led-icons/user.png';

				$excel_array [] = array(
					'카테고리' => '',
					'폴더명' =>  '',
					'사용자' => $user['name'],
					'사번' => $user['user_id'],
					'부서명' => $user['dept_nm'],
					'사용자 정보' => ''
				);

				foreach ($user as $userKey => $userValue) {
					$child[$userKey] = $userValue;
				}

				$child['leaf'] = true;
				$child_no++;
				$child_list [] = $child;
			}
		}

		if ( !$leaf) {
			$node['children'] = $child_list;
		} else {
			$node['children'] = array();
		}

		$node['leaf'] = false;
		$node_list [] = $node;

		$no++;
	}

	if($_GET['is_excel'])
	{
		echo createExcelFile('제작프로그램관리', $excel_array);
	}
	else
	{
		echo json_encode($node_list);
	}
}
catch (Exception $e)
{
	echo '오류 : '.$e->getMessage();
}

function escape($v)
{
	$v = str_replace("'", "\'", $v);
	$v = str_replace("\r", '', $v);
	$v = str_replace("\n", '\\n', $v);

	return $v;
}

function breake_map($value)
{
	switch($value)
	{
		case 'C':
			return '<span style="color: blue">재직</span>';
		break;

		case 'T':
			return '<span style="color: red">퇴사</span>';
		break;
	}
}

function breake_map_e($value)
{
	switch($value)
	{
		case 'C':
			return '재직';
		break;

		case 'T':
			return '퇴사';
		break;
	}
}
?>