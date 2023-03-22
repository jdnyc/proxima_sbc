<?php
header("Pragma","public");
header("Expires","0");
header("Cache-Control","must-revalidate, post-check=0, pre-check=0");
header("Content-Type","application/force-download");
header("Content-Type","application/vnd.ms-excel");
header("Content-Disposition","attachment;filename=aaaa.xls");
 
echo $_POST['ex'];
?>