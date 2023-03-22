<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
try{
	// Update BC_CONTENT_STATUS
	$query_update = "
					UPDATE BC_CONTENT_STATUS
					SET ARCHIVE_STATUS = 'N',
						ARCHIVE_DATE = NULL,
						RESTORE_DATE = NULL
					";
	$db->exec($query_update);

	//Delete BC_ARCHIVE_REQUEST
	$query_bc_archive_request = "DELETE FROM BC_ARCHIVE_REQUEST";

	$db->exec($query_bc_archive_request);

	echo array(
				'success' => true,
				'msg' => 'Reset data successfully'
				);	
}
catch(Exception $e){
	$msg = $e->getMessage();
	switch($e->getCode()){
		case ERROR_QUERY:
			$msg .= '( '.$db->last_query.' )';
		break;
	}

	echo array(
			'success' => false,
			'msg' => $msg
	);
}
?>