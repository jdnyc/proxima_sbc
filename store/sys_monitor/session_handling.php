<?php 
$value = $_REQUEST['value'];
$_SESSION['temp'] = $value;
echo $_SESSION['temp'];
?>