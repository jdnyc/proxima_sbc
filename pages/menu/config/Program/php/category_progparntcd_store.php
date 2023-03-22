<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

//require_once($_SERVER['DOCUMENT_ROOT'].'/mssql_connection.php');
try
{
	$start = is_null($_POST['start']) ? 0 : $_POST['start'];
	$limit = is_null($_POST['limit']) ? 15 : $_POST['limit'];

	$category_id = $_POST['category_id'];


	$query = "select cpm.* from CATEGORY_PROGCD_MAPPING cpm , BC_CATEGORY c, path_mapping p where cpm.category_id=c.category_id and p.category_id=c.category_id and cpm.category_id='$category_id'";

	$order = " order by cpm.PROGPARNTCD desc, cpm.prognm asc, cpm.MEDCD asc";

	$total =  $db->queryOne("select count(*) from ( $query ) cnt");

	//$db_ms->setLimit($limit , $start);

	$list = $db->queryAll($query.$order);


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