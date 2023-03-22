<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
/*
 * Subject : ODA_D Get Request List.
 * Created date : 2016.10.14
 * Created by : CanPN
 **/
	function SoapGetRestoreList(){
		global $arr_sys_code;
		global $db;
		$result;
		@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/ODA_ODS_D_SoapGetRestoreList'.date('Ym').'.log', date('Y-m-d H:i:s').'$SoapGetRestoreList start'."\n", FILE_APPEND);
		try{

			$query = "	SELECT	TAPE_ID
								,CONTENT_ID
								,CREATED_DATETIME
								,TASK_ID
								,START_FRAME
								,END_FRAME
								,REQUEST_TYPE
								,ORI_CONTENT_ID
						FROM	bc_archive_request
						WHERE	REQUEST_SYSTEM = 'ODS_D'
						AND		(REQUEST_TYPE = 'RESTORE' OR REQUEST_TYPE = 'PFR')
						AND		STATUS = 'APPROVE'
					";

			$restore_data = $db->queryAll($query);

			if(is_array($restore_data) && !empty($restore_data)){
				$string_xml = '<?xml version="1.0"?>';
				$string_xml .= '<RestoreList>';

				foreach ($restore_data as $restore) {
					$string_xml .= '<Content>';
					$string_xml .= '<CartridgeID>'.$restore['tape_id'].'</CartridgeID>';
					$string_xml .= '<CreatedDatetime>'.$restore['created_datetime'].'</CreatedDatetime>';
					if($restore['request_type'] == 'PFR'){
						$query = "	SELECT PATH
									FROM BC_MEDIA
									WHERE CONTENT_ID = ".$restore['ori_content_id']."
									AND MEDIA_TYPE = 'original'
								";
						$ori_content_path = $db->queryOne($query);

						$ori_ext = array_pop(explode('.', $ori_content_path));
						$frame_rate = getFrameRate($restore['content_id']);
						$mark_in	= round($restore['start_frame'] * $frame_rate);
						$mark_out		= round($restore['end_frame'] * $frame_rate);
						$pfr_storage_path = $arr_sys_code['interwork_oda_ods_d']['ref4'];

						$string_xml .= '<ContentId>'.$restore['ori_content_id'].'</ContentId>';
						$string_xml .= '<RestoreMode>P</RestoreMode>';
						$string_xml .= '<TaskID>'.$restore['task_id'].'</TaskID>';
						$string_xml .= '<MarkIn>'.$mark_in.'</MarkIn>';
						$string_xml .= '<MarkOut>'.$mark_out.'</MarkOut>';
						$string_xml .= '<TargetPath>'.$pfr_storage_path.'\\'.$restore['content_id'].'.'.$ori_ext.'</TargetPath>';

					}else if($restore['request_type'] == 'RESTORE'){
						$string_xml .= '<ContentId>'.$restore['content_id'].'</ContentId>';
						$string_xml .= '<RestoreMode>C</RestoreMode>';
						$string_xml .= '<TaskID>'.$restore['task_id'].'</TaskID>';
						$string_xml .= '<MarkIn></MarkIn>';
						$string_xml .= '<MarkOut></MarkOut>';
						$string_xml .= '<TargetPath></TargetPath>';
					}
					
					
					$string_xml .= '</Content>';
				}
				$string_xml .= '</RestoreList>';
				
				$result = array(
		    			'code' => '0',
		    			'msg' => $string_xml
		    	);
				@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/ODA_ODS_D_SoapGetRestoreList'.date('Ym').'.log', date('Y-m-d H:i:s').'Return::::'.print_r($result, true).")\n", FILE_APPEND);
			}else{
				//empty restore list
				$result = array(
		    			'code' => '1',
		    			'msg' => 'Just not found data Empty Restore Request List'
		    	);
		    	@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/ODA_ODS_D_SoapGetRestoreList'.date('Ym').'.log', date('Y-m-d H:i:s').'Return::::'.print_r($result, true).")\n", FILE_APPEND);
			}
			

		}catch(Exception $e){
			$msg = $e->getMessage();
	    	switch($e->getCode()){
	    		case ERROR_QUERY:
	    			$msg .= '( '.$db->last_query.' )';
	    			break;
	    	}
	    	@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/ODA_ODS_D_SoapGetRestoreList'.date('Ym').'.log', date('Y-m-d H:i:s').'$msg::::'.$msg.")\n", FILE_APPEND);
			return array(
		    			'code' => '1',
		    			'msg' => $msg
		    	);
		}
		return $result;


}	
?>