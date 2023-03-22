<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
fn_checkAuthPermission($_SESSION);

$search_f = $_POST['search_f'];
$search_v = $_POST['search_v'];
$search_sdate = $_POST['search_sdate'];
$search_edate = $_POST['search_edate'];

try {
	$query = "
		SELECT  STORAGE_ID, TYPE, NAME, PATH, DESCRIPTION
		FROM    BC_STORAGE
		WHERE	TYPE IN('SAN','NAS') --and storage_id = '127'
		ORDER BY STORAGE_ID ASC";

	$data = $db->queryAll($query);

	$datas = array();
	foreach($data as $d)
	{
		$row = array();
		$row = $d;
		$disk_total = @disk_total_space($d[path]);
		$disk_free = @disk_free_space($d[path]);
		$row[usage] = formatByte($disk_total-$disk_free);
		$row[usable] = formatByte($disk_free);
		$row[quota] = formatByte($disk_total);
		array_push($datas, $row);
	}

	if($_POST[is_excel] == 1)
	{
		$columns = json_decode($_POST[columns], true);
		$array = array();
		foreach($datas as $d)
		{
			$rows = array();
			foreach($columns as $col)
			{
				if( strstr($col[0], 'date')  )
				{
					if(empty($d[$col[0]]))
					{
						$value = '';
					}
					else
					{
						$value = substr($d[$col[0]],0,4).'-'.substr($d[$col[0]],4,2).'-'.substr($d[$col[0]],6,2);
					}
				}
				else
				{
					$value = $d[$col[0]];
				}

				$rows[$col[1]] = $value;
			}
			array_push($array, $rows);
		}

		echo createExcelFile(_text('MSG02151'),$array);
	}
	else
	{
		echo json_encode(array(
			'success' => true,
			'total' => $total,
			'data' => $datas,
			'query'=>$query
		));
	}

}
catch(Exception $e){
	$msg = $e->getMessage();
	if($e->getCode() == ERROR_QUERY){
		$msg = $msg.'( '.$db->last_query.' )';
	}

	die(json_encode(array(
		'success' => false,
		'msg' => $msg
	)));
}

//function formatByte($b, $p=null) {
    //$units = array("B","KB","MB","GB","TB","PB","EB","ZB","YB");
    //$c=0;

	//if(empty($b) || $b < 1){
		//return '';
	//}
    //else if(!$p && $p !== 0) {
        //foreach($units as $k => $u) {
            //if(($b / pow(1024,$k)) >= 1) {
                //$r["bytes"] = $b / pow(1024,$k);
                //$r["units"] = $u;
                //$c++;
				//$r_k = $b;
            //}
        //}
        //return number_format($r["bytes"],2) . " " . $r["units"];
    //} else {
        //return number_format($b / pow(1024,$p), 2) . " " . $units[$p];
    //}

//}
?>