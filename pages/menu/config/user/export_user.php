<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/PHPExcel.php');
$objPHPExcel = new PHPExcel();

$objPHPExcel->setActiveSheetIndex(0)
->setCellValue('A1', _text("MN00195"))
->setCellValue('B1', _text("MN00196"))
->setCellValue('C1', _text("MN00181"))
->setCellValue('D1', _text("MN02127"))
->setCellValue('E1', _text("MN02208"))
->setCellValue('F1', _text("MN00111"));

$objPHPExcel->setActiveSheetIndex(0)->getColumnDimension('A')->setWidth(20);
$objPHPExcel->setActiveSheetIndex(0)->getColumnDimension('B')->setWidth(20);
$objPHPExcel->setActiveSheetIndex(0)->getColumnDimension('C')->setWidth(20);
$objPHPExcel->setActiveSheetIndex(0)->getColumnDimension('D')->setWidth(20);
$objPHPExcel->setActiveSheetIndex(0)->getColumnDimension('E')->setWidth(15);
$objPHPExcel->setActiveSheetIndex(0)->getColumnDimension('F')->setWidth(30);


$rows = $db->queryAll("	SELECT  M.USER_ID,
						        M.USER_NM,
						        M.DEPT_NM,
						        M.EMAIL,
						        M.PHONE,
								(
								SELECT 
									LISTAGG(G.MEMBER_GROUP_NAME,',') WITHIN GROUP(ORDER BY G.MEMBER_GROUP_ID)
								FROM BC_MEMBER_GROUP_MEMBER GM,
										BC_MEMBER_GROUP G
								WHERE MEMBER_ID = M.MEMBER_ID AND GM.MEMBER_GROUP_ID = G.MEMBER_GROUP_ID
								) AS GROUPS
						FROM BC_MEMBER M WHERE m.DEL_YN='N'
						ORDER BY USER_NM ASC 
");

$i = 2;
foreach ($rows as $row){
	$objPHPExcel->setActiveSheetIndex(0)
	->setCellValue('A'.$i, $row['user_id'])
	->setCellValue('B'.$i, $row['user_nm'])
	->setCellValue('C'.$i, $row['dept_nm'])
	->setCellValue('D'.$i, $row['email'])
	->setCellValue('E'.$i, $row['phone'])
	->setCellValue('F'.$i, $row['groups']);
	$i++;
}

$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
$full_path = 'user_export.xlsx';

header('Content-type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="'.$full_path.'"');
$objWriter->save('php://output');
?>