<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/mssql_connection.php');
try
{
	$start = 0;

	$limit = 10;

	$medcd = $_POST['medcd'];

	$where = '';

	if(!empty($medcd) && ( $medcd != 'all' ) )
	{
		$where = " and MedCd = '$medcd' ";
	}

	$query = "
	SELECT
		DISTINCT FormBaseYmd
	FROM
		tbbf002
	WHERE
		(BrodGu = '001')
	".$where;

	$order = " ORDER BY FormBaseYmd DESC ";

//	$total =  $db_ms->queryOne("select count(*) from ( $query ) cnt");

//	$db_ms->setLimit($limit , $start);

	$list = $db_ms->queryAll($query.$order);

	if(PEAR::isError($list)) throw new Exception($list->getMessage());

	foreach($list as $key => $data)
	{
		if( strtotime( $data['formbaseymd'] ) )
		{
			$list[$key]['name'] = date('Y-m-d', strtotime( $data['formbaseymd'] ) );
		}
		else
		{
			$list[$key]['name'] = $data['formbaseymd'];
		}
	}

	array_unshift($list , array(
		'name'=>'전체',
		'formbaseymd'=>'all'
	));


	echo json_encode(array(
		'success' => true,
		'data' => $list,
		'total'=> $total
	));
}
catch (Exception $e)
{
	echo '오류 : '.$e->getMessage().$db_ms->lasy_query;
}


?>