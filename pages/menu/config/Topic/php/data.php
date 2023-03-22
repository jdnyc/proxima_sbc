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
			c.show_order sort
		from
			BC_CATEGORY c,
			BC_UD_CONTENT_TAB uct
		where c.category_id=uct.category_id
		  and uct.name='topic'
		order by c.show_order";

	$result_list = $db->queryAll($query);

	$no = 1;
	$node_list = array();
	$node = array();
	$child = array();

	$excel_array = array();

	foreach ($result_list as $item)
	{
		$node = array();
		$child_list = array();
		$child_no = 1;
		$leaf = true; //기본 리프노드

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

		foreach ($item as $key => $value)
		{
			if($key == 'cnt')
			{
				$node[$key] = $value.' 명';
			}
			else
			{
				$node[$key] = $value;
			}
		}
		
		$child_list[] = makeChildNode($node['id']);

		if(!$leaf)
		{
			$node['children'] = $child_list;
		}
		else
		{
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

function makeChildNode($id)
{
	global $db;
}
?>