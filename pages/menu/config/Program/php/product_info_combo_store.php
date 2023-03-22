<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

require_once($_SERVER['DOCUMENT_ROOT'].'/mssql_connection.php');
try
{
	$start = is_null($_POST['start']) ? 0 : $_POST['start'];
	$limit = is_null($_POST['limit']) ? 15 : $_POST['limit'];

	$where_array = array();

	if(!empty($_POST['prognm']))
	{
		$prognm = trim($_POST['prognm']);
		array_push($where_array, " prognm like '%$prognm%' ");
	}

	if( !empty($_POST['medcd']) && ( $_POST['medcd'] != 'all' )  )
	{
		array_push($where_array, " medcd = '{$_POST['medcd']}' ");
	}

	if( !empty($_POST['formbaseymd']) && ( $_POST['formbaseymd'] != 'all' ) && ( $_POST['formbaseymd'] != '전체' ))
	{
		array_push($where_array, " formbaseymd = '{$_POST['formbaseymd']}' ");
	}

	if(!empty($where_array))
	{

		$where = ' and '.join(' and ',$where_array);
	}

	$query = "
	select
		*
	from
		tbbf002
	where
		brodgu='001'
		 ".$where;

//	echo $query;

//	$query = "select * from tbbf001  ".$where ;
	$sort = $_POST['sort'];
	$dir = $_POST['dir'];
	$order = " order by $sort $dir";

	$total =  $db_ms->queryOne("select count(*) from ( $query ) cnt");

	$db_ms->setLimit($limit , $start);

	$list = $db_ms->queryAll($query.$order);

	$codes = getCodeInfo("MEDCD");

	foreach($list as $key => $val)
	{
		foreach($codes as $code)
		{
			if($code['code'] == $val['medcd'])
			{
				$list[$key]['mednm'] = $code['name'];
			}
		}
	}

	echo json_encode(array(
		'success' => true,
		'data' => $list,
		'total'=> $total
	));
}
catch (Exception $e)
{
	echo '오류 : '.$e->getMessage();
}


?>