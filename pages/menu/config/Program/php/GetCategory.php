<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'] . '/config.php');

try
{
	$strQuery = "
	select
		c.id category_id,
		c.title,
		p.path ,
		u.user_id,
		m.name,
		m.dept_nm,
		m.breake,
		m.dep_tel_num
	from
		categories c,
		path_mapping p,
		user_mapping u,
		member m
	where
		p.category_id = c.id
	and c.id = u.category_id(+)
	and m.user_id=u.user_id
	";

//	$counts = $db->queryAll("select  um.category_id,  m.breake,  count(um.user_id) count from  user_mapping um, member m where um.user_id=m.user_id group by um.category_id, m.breake");


	$rows = $db->queryAll($strQuery);
	if(PEAR::isError($rows)) throw new Exception($rows->getMessage(), ERROR_QUERY);

//	foreach($DBValue as $key => $v)
//	{
//		foreach($counts as $value)
//		{
//			if (($v['category_id']) == ($value['category_id']))
//			{
//				if(($value['breake']) == 'C')
//				{//재직일때
//					$DBValue[$key]['cn'] = $value['count'];
//				}
//				else
//				{//재직이아닐때
//					$DBValue[$key]['tn'] = $DBValue[$key]['tn'] +  $value['count'];
//				}
//
//			}
//		}
//	}
//
//	echo json_encode(array(
//	//	'success' => true,
//		'total' => $total,
//		'data' => $DBValue
//	));
	
	$data = array();
	
	foreach($rows as $row)
	{
		array_push($data, array(
			$row['category_id'],
			$row['title'],
			$row['path'],			
			$row['user_id'],
			$row['name'],
			$row['dept_nm'],
			breake_map($row['breake']),
			$row['dep_tel_num']
		));
	}

	echo json_encode($data);

}
catch(Exception $e)
{
	$msg = $e->getMessage();

	switch($e->getCode())
	{
		case ERROR_QUERY:
			$msg = $msg.'( '.$db->last_query.' )';
		break;
	}

	die(json_encode(array(
		'success' => false,
		'msg' => $msg
	)));
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
?>