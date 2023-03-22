<?php
try{
	$target_dir = "Z:/Temp/";
	$target_file = $target_dir . basename($_FILES["FileAttach"]["name"]);
	$tmp_name = $_FILES["FileAttach"]["tmp_name"];
	$size = $_FILES["FileAttach"]["size"];
	$fileType = pathinfo($target_file,PATHINFO_EXTENSION);


	if($size>0 && ($fileType == 'xlsx' || $fileType == 'xls')) {
	    $return = move_uploaded_file($tmp_name, $target_file);
	    if($return){
	    	echo json_encode( array(
				'success' => true,
				'result' => $target_file
			));
	    }else{
	    	echo json_encode( array(
				'success' => false,
				'result' => 'Can not upload file'
			));
	    }
	} else {
	    echo json_encode( array(
			'success' => false,
			'result' => 'Not allowed file'
		));
	}
}
catch(Exception $e){
	die(json_encode(array(
		'success' => false,
		'result' => 'failure',
		'msg' => $e->getMessage()
	)));
}
?>