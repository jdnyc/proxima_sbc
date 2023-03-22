<?php
require "./inc/testCheck.php";

if($site_test){
	echo rand(0,5000);
}
else
{
	include "../store/common_search_result.php";
}
// UTF-8 한글 체크
?>
