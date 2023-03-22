<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lib.php');

$types = $mdb->queryAll("select bs_content_id as type, bs_content_title as name from bc_bs_content");
$time = date('YmdHis');

try
{
    $array = array();
	foreach($types as $type){
        $row = array();
		$id=$type['type'];
		$name=$type['name'];

		$regist = $mdb->queryOne("select count(*) from bc_content where bs_content_id='$id' and is_deleted='N'");

        $row[_text('MN00276')] = $name;//콘텐츠 유형
        $row[_text('MN00284')] = $regist;//콘텐츠 타입별 등록 횟수
        array_push($array, $row);
	}

    //MSG02152 콘텐츠타입별등록현황
    echo createExcelFile(_text('MSG02152'),$array);
}
catch (Exception $e)
{
	echo '쿼리 오류: '.$e->getMessage();
}
?>