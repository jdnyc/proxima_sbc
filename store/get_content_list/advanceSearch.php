<?php
/*
require_once($_SERVER['DOCUMENT_ROOT'].'/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/libs/functions.php');
*/

$globalSubCount = 0;
$globalChar = 97;

$condition = json_decode($_POST['condition']);

$where_content_require = array();
$where_require = array();
$where = array();

foreach ($condition->require as $v)
{
	if ($v->field_name == 'meta_table_id')
	{
		array_push($where_content_require, "c.".$v->field_name."='".$v->value."'");
	}
	else
	{
		array_push($where_require, $v->field_name."='".$v->value."'");
	}
}
$where_content_require = join(' and ', $where_content_require);
$where_require = join(' and ', $where_require);

// date
foreach ($condition->startEnd as $v)
{
	array_push($where, buildQueryDateTermStartEnd($v->field_id, $v->start, $v->end));
}
foreach ($condition->start as $v)
{
	array_push($where, buildQueryDateTermStart($v->field_id, $v->start));
}
foreach ($condition->end as $v)
{
	array_push($where, buildQueryDateTermEnd($v->field_id, $v->end));
}

// equal
foreach ($condition->equal as $v)
{
	$globalSubCount++;
	array_push($where, "(select content_id from meta_value where meta_field_id=$v->field_id and value='{$v->value}') ".chr($globalChar++));
}

// like
foreach ($condition->like as $v)
{
	if (preg_match('/^c_/', $v->field_id))
	{
		$c_title = " c.title like '%".$v->value."%' ";
		continue;
	}

	$globalSubCount++;
	array_push($where, "(select content_id from meta_value where meta_field_id=$v->field_id and value like '%{$v->value}%') ".chr($globalChar++));
}


if (!empty($where))
{
	$charA = 97; // 소문자 아스키 a 코드
	$select_query = "select a.content_id from ".join(", \n", $where).' where 1=1 ';

	$w = '';
	for ($i=0; $i<$globalSubCount-1; $i++)
	{
		$where_query .= ' and '.chr($charA).'.content_id='.chr($charA+1).'.content_id ';
		
		$charA++;
	}

	$query = $select_query.' '.$where_query;
}

if ( empty($where) && empty($c_title) )
{
	die(json_encode(array(
		'success' => false,
		'msg' => '검색어가 입력되지 않았습니다.'
	)));
}


$q = "select ".
			"c.category_id, c.content_id, c.title, c.meta_table_id, c.created_time, c.content_type_id, c.is_hidden, ct.name as content_type, mt.name as table_name ".
		"from ".
			"content c, content_type ct, meta_table mt ".
		"where 1=1 ";
			if ( !empty($where_content_require) )
			{
				$q .= " and ".$where_content_require;
			}
			if ( !empty($query) )
			{
				$q .= " and c.content_id in (".$query.") ";
			}			
			if ( !empty($c_title) )
			{
				$q .= ' and '.$c_title;
			}
	$q .=	"and c.meta_table_id=mt.meta_table_id ".
			"and c.content_type_id=ct.content_type_id ".
			"and c.is_deleted=0 ".
			"and c.user_id!='watchfolder' ";
$total = $db->queryOne("select count(*) from (".$q.") a");

$db->setLimit($limit, $start);
$content_list = $db->queryAll($q);

$contents = fetchMetadata($content_list);
die(json_encode(array(
	'success' => true,
	'total' => $total,
	'results' => $contents
)));


// functions 
function buildQueryDateTermStartEnd($field_id, $start, $end)
{
	global $where_require, $globalSubCount, $globalChar;
	
	$globalSubCount++;
/*
	return "(select content_id from meta_value where meta_field_id=$field_id ".
				" and date_format(value, '%Y-%m-%d') >= date_format('$start', '%Y-%m-%d') ".
				" and date_format(value, '%Y-%m-%d') <= date_format('$end', '%Y-%m-%d')) ".chr($globalChar++);	
*/
	// 오라클용
	return "(select content_id from meta_value where meta_field_id=$field_id ".
				" and value is not null ".
				" and to_date(value, 'YYYYMMDDHH24MISS') >= to_date('$start', 'YYYY-MM-DD') ".
				" and to_date(value, 'YYYYMMDDHH24MISS') <= to_date('$end', 'YYYY-MM-DD')) ".chr($globalChar++);	
}

function buildQueryDateTermStart($field_id, $start)
{
	global $where_require, $globalSubCount, $globalChar;
	
	$globalSubCount++;
	return "(select content_id from meta_value where meta_field_id=$field_id ".
				" and value is not null ".
				" and to_date(value, 'YYYYMMDDHH24MISS') >= to_date('$start', 'YYYY-MM-DD')) ".chr($globalChar++);
}

function buildQueryDateTermEnd($field_id, $end)
{
	global $where_require, $globalSubCount, $globalChar;
	
	$globalSubCount++;
	return "(select content_id from meta_value where meta_field_id=$field_id ".
				" and value is not null ".
				" and to_date(value, 'YYYYMMDDHH24MISS') <= to_date('$end', 'YYYY-MM-DD')) ".chr($globalChar++);	
}


?>