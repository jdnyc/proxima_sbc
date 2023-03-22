<?php
session_start();
require_once '../lib/config.php';
require_once '../lib/functions.php';

$year = explode('/', $_POST['date']);

$result = array();

$status = 0;
$is_deleted = 'N';
$ud_contents = join( ',', $CG_LIST);
$_where = array();

array_push($_where , " status > $status " );
array_push($_where , " is_deleted = '$is_deleted' " );
array_push($_where , " ud_content_id in ( $ud_contents  ) " );

$where = ' where '.join(' and ', $_where);
if ( count($year) == 2 )
{
	if($_POST['mode'] == 'file')
	{//파일생성일자 용
		$years = $db->queryAll("select substr(file_created_date, 1, 4) as year from view_content $where and file_created_date is not null group by substr(file_created_date, 1, 4) order by substr(file_created_date, 1, 4) desc ");
	}
	else
	{
		$years = $db->queryAll("select substr(created_date, 1, 4) as year from bc_content $where group by substr(created_date, 1, 4) order by substr(created_date, 1, 4) desc ");
	}	

	for ($i=0; $i<count($years); $i++)
	{
		if ( preg_match('/[0-9]{4}/', $years[$i]['year']) ) 
		{
			array_push($result, array(
									'text' => $years[$i]['year']
			));
		}
	}
}
else
{
	if($_POST['mode'] == 'file')
	{//파일생성일자 용
		$months = $db->queryAll("select substr(file_created_date, 5, 2) as month from view_content $where  and file_created_date is not null and  file_created_date like '".$year[2]."%' group by substr(file_created_date, 5, 2)  order by substr(file_created_date, 5, 2) desc ");
	}
	else
	{
		$months = $db->queryAll("select substr(created_date, 5, 2) as month from bc_content $where  and  created_date like '".$year[2]."%' group by substr(created_date, 5, 2)  order by substr(created_date, 5, 2) desc ");
	}

	for ($i=0; $i<count($months); $i++)
	{
		if ( preg_match('/[0-1][0-9]/', $months[$i]['month']) )
		{
			array_push($result, array(
				'text' => $months[$i]['month'],
				'leaf' => true
			));
		}
	}

}

echo json_encode($result);

?>