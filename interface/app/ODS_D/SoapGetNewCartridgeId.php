<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
/**
 * Created by PhpStorm.
 * User: cerori
 * Date: 2015-01-15
 */

function SoapGetNewCartridgeId() {
	global $server;
	global $db;
	global $arr_sys_code;
	
	@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/ODA_ODS_D_SoapGetNewCartridgeId'.date('Ym').'.log', date('Y-m-d H:i:s').'$SoapGetNewCartridgeId start'.")\n", FILE_APPEND);
	
	try{
		//changed for postgresql
		//'".$arr_sys_code['interwork_oda_ods_d']['ref5']."' || LPAD(COALESCE(SUBSTR(MAX(TAPE_ID), 5), 0) + 1, 6, '0') AS CARTRIDGE_ID
		$v_cartridge_id = $db->queryOne("
			SELECT	MAX(TAPE_ID) AS CARTRIDGE_ID
			FROM	BC_ARCHIVE_REQUEST
			WHERE	REQUEST_SYSTEM = 'ODS_D'
		");
		
		if($v_cartridge_id == ''){
			$v_cartridge_id = $arr_sys_code['interwork_oda_ods_d']['ref5'].'000001';
		}else{
			$v_cartridge_id = $arr_sys_code['interwork_oda_ods_d']['ref5'].str_pad(substr($v_cartridge_id, -6) + 1, 6, '0', STR_PAD_LEFT);
		}
		
		@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/ODA_ODS_D_SoapGetNewCartridgeId'.date('Ym').'.log', date('Y-m-d H:i:s').'$v_cartridge_id:::'.$v_cartridge_id."\n", FILE_APPEND);
	}
	catch(Exception $e){
		$msg = $e->getMessage();
		switch($e->getCode()){
			case ERROR_QUERY:
				$msg .= '( '.$db->last_query.' )';
			break;
		}
	
		@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/ODA_ODS_D_SoapGetNewCartridgeId'.date('Ym').'.log', date('Y-m-d H:i:s').'$msg:::'.$msg."\n", FILE_APPEND);
		
		return array(
				'code' => '1',
				'msg' => $msg
		);
	}
	
	return array(
		'code' => '0', 
		'msg' => $v_cartridge_id
	);
}