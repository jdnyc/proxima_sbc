<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/store/get_content_list/libs/functions.php');

$limit = $_POST['limit'];
$start = $_POST['start'];


$v = explode('/', $_POST['value']);
$meta_table_id = $_POST['meta_table_id'];

//print_r($v);

if ($meta_table_id == CLEAN)
{
	$field_title = '81851';
	$field_sub_title = '81853';
	$field_broadcast_date = '81854';
}
else if ($meta_table_id == PRE_PRODUCE) 
{
	$field_title = '81787';
	$field_sub_title = '81786';
	$field_broadcast_date = '4002618';
}

$cnt = count($v);
if ( $cnt == 1)
{
	//$arr_cho = array("ㄱ", "ㄱ", "ㄴ", "ㄷ", "ㄷ", "ㄹ", "ㅁ","ㅂ", "ㅂ", "ㅅ", "ㅅ", "ㅇ", "ㅈ", "ㅈ","ㅊ", "ㅋ", "ㅌ", "ㅍ", "ㅎ");
	$mapping = array(
		'ㄱ' => array('가', '나'),
		'ㄴ' => array('나', '다'),
		'ㄷ' => array('다', '라'),
		'ㄹ' => array('라', '마'),
		'ㅁ' => array('마', '바'),
		'ㅂ' => array('바', '사'),
		'ㅅ' => array('사', '이'),
		'ㅇ' => array('이', '지'),
		'ㅈ' => array('지', '치'),
		'ㅊ' => array('치', '키'),
		'ㅋ' => array('키', '티'),
		'ㅌ' => array('티', '피'),
		'ㅍ' => array('피', '히'),
		'ㅎ' => array('히', '가')
	);
	if ( $mapping[$v[0]] )
	{
		$q = "select 
				distinct v.content_id 
			from 
				content c,
				meta_value v
			where 
				c.is_deleted=0 
			and 
				c.status=2 
			and 
				c.content_id=v.content_id
			and
				v.meta_field_id=$field_title
			and 
				v.value is not null
			and
				v.value  > '".$mapping[$v[0]][0]."' and v.value < '".$mapping[$v[0]][1]."'";
	}
	else if ($v[0] == '특집')
	{
		$q = "select 
				distinct v.content_id 
			from 
				content c,
				meta_value v
			where 
				c.is_deleted=0 
			and 
				c.status=2 
			and 
				c.content_id=v.content_id
			and
				v.meta_field_id=$field_title
			and 
				v.value is not null
			and
				(v.value like '".$v[0]."%' or v.value like '<".$v[0]."%')";
	}
	else
	{
		$q = "select 
				distinct v.content_id 
			from 
				content c,
				meta_value v
			where 
				c.is_deleted=0 
			and 
				c.status=2 
			and 
				c.content_id=v.content_id
			and
				v.meta_field_id=$field_title
			and 
				v.value is not null
			and
				v.value  > '".$v[0]."' and v.value < '".chr(ord($v[0])+1)."'";
	}
}
else if ( $cnt == 2 )
{
	$q = "select 
				distinct v.content_id 
			from 
				content c, meta_value v 
			where 
				c.is_deleted=0 
			and 
				c.status=2 
			and 
				c.content_id=v.content_id 
			and 
				v.meta_field_id=$field_title
			and 
				v.value is not null
			and
				v.value = '".$db->escape($v[1])."'";
	//$q = "select distinct content_id from meta_value where meta_table_id=$meta_table_id and value = '".$v[2]."'";
}
else if ( $cnt == 3 ) 
{
	$q = "select 
				distinct c.content_id
			from 
				content c,
			    (select distinct content_id from meta_value where meta_field_id=$field_title and value is not null and value ='".$db->escape($v[1])."' ) t1,
				(select distinct content_id from meta_value where meta_field_id=$field_broadcast_date and value is not null and substr(value, 1, 4) ='".$v[2]."' ) t2
			where 
				c.is_deleted=0 
			and 
				c.status=2
			and 
				c.content_id=t1.content_id
			and
				c.content_id=t2.content_id";
}
else if ( $cnt == 4 ) 
{
	$q = "select 
				distinct c.content_id
			from 
				content c,
			    (select distinct content_id from meta_value where meta_field_id=$field_title and value is not null and value ='".$db->escape($v[1])."' ) t1,
				(select distinct content_id from meta_value where meta_field_id=$field_broadcast_date and value is not null and substr(value, 1, 6) ='".$v[2].$v[3]."' ) t2
			where 
				c.is_deleted=0 
			and 
				c.status=2
			and 
				c.content_id=t1.content_id
			and
				t1.content_id=t2.content_id";
}
else if ( $cnt == 5 ) 
{
	$q = "select 
				distinct c.content_id
			from 
				content c,
			    (select distinct content_id from meta_value where meta_field_id=$field_title and value is not null and value ='".$db->escape($v[1])."' ) t1,
				(select distinct content_id from meta_value where meta_field_id=$field_broadcast_date and value is not null and substr(value, 1, 6) ='".$v[2].$v[3]."' ) t2,
				(select distinct content_id from meta_value where meta_field_id=$field_sub_title and value is not null and value ='".$db->escape($v[4])."' ) t3
			where 
				c.is_deleted=0 
			and 
				c.status=2
			and 
				c.content_id=t1.content_id
			and
				c.content_id=t2.content_id
			and
				c.content_id=t3.content_id";
}
else
{
	print($v);
}

//echo $q;
$total = $db->queryOne("select count(*) from ($q) t");

if ($total == 0)
{
	die(json_encode(array(
		'success' => true,
		'total' => $total,
		'results' => array()
	)));
}
$db->setLimit($limit, $start);
$data = $db->queryAll($q);

foreach ( $data as $i )
{
	$contents[] = $i['content_id'];
}

$contents = implode(',', $contents);
$content_list_query = "select ".
						"c.category_id, c.content_id, c.title, c.created_time, c.meta_table_id, c.content_type_id, c.is_hidden, ct.name as content_type, mt.name as table_name ".
					"from ".
						"content c, content_type ct, meta_table mt ".
					"where ".
						"c.content_id in ($contents) ".
						"and c.meta_table_id=mt.meta_table_id ".
						"and c.content_type_id=ct.content_type_id ";

$content_list = $db->queryAll($content_list_query);

$contents = fetchMetadata($content_list);
die(json_encode(array(
	'success' => true,
	'total' => $total,
	'results' => $contents
)));
//print_r($data);
?>