<?php
session_start();

require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

$pgm_id = $_POST['pgm_id'];
$is_check = $_POST['checked'];

//$db->exec("
           //MERGE INTO SYNC_PROGRAM
                //USING DUAL
                   //ON (PGM_ID = '$pgm_id')
    //WHEN MATCHED THEN UPDATE SET IS_CHECK = '$is_check'
//WHEN NOT MATCHED THEN INSERT (PGM_ID, IS_CHECK) VALUES ('$pgm_id', '$is_check')"
//);

$v_cnt = $db->queryOne("
           SELECT	COUNT(*)
		   FROM		SYNC_PROGRAM
           WHERE	PGM_ID = '$pgm_id'
		   "
);

if ($v_cnt > 0){
	$db->exec("
		   UPDATE	SYNC_PROGRAM
		   SET		IS_CHECK = '$is_check'
		   WHERE	PGM_ID = '$pgm_id'
		   "
	);
}else{
	$db->exec("
			INSERT INTO SYNC_PROGRAM
				(
				PGM_ID
				,IS_CHECK
				)
			VALUES
				('$pgm_id', '$is_check')
		   "
	);
}


?>
