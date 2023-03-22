<?php
function getCombo($default_value){
	list($default, $data) = explode('(default)', $default_value);

	$data = explode(';', $data);
	$data = "'".join("', '", $data)."'";

	return array(
		'default' => $default,
		'data' => $data	
	);
} 
?>