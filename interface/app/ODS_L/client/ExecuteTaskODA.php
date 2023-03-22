<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

function ExecuteTaskODA($data,$mode,$content_id)
{
	global $arr_sys_code;
	global $db;
	try{
		$v_reslt_arr =	array();
		$url = $arr_sys_code['interwork_oda_ods_l']['ref2'];
		$options = array( 
			'soap_version'=>SOAP_1_2,
			'exceptions'=>true, 
			'trace'=>1, 
			'cache_wsdl'=>WSDL_CACHE_BOTH
			);
		$client = new SoapClient($url,$options);

		if($mode == 'archive'){
			//make xml
			$string_xml = '<?xml version="1.0"?>';
			$string_xml .= '<request>';
			$string_xml .= '<objectID>'.$data['objectID'].'</objectID>';
			$string_xml .= '<objectCategory>'.$data['objectCategory'].'</objectCategory>';
			$string_xml .= '<filesPathRoot>'.$data['filesPathRoot'].'</filesPathRoot>';
			$string_xml .= '<filename>'.$data['filename'].'</filename>';
			$string_xml .= '<priority>'.$data['priority'].'</priority>';
			$string_xml .= '<title>'.$data['title'].'</title>';
			$string_xml .= '<comments>'.$data['comments'].'</comments>';
			$string_xml .= '<soapWSDL>http://'.$_SERVER['SERVER_ADDR'].':'.$_SERVER['SERVER_PORT'].'/interface/app/ODS_L/common.php?wsdl</soapWSDL>';
			$string_xml .= '<soapService>Common</soapService>';
			$string_xml .= '<soapPort>CommonPort</soapPort>';
			$string_xml .= '</request>';

			file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/ExecuteTaskODA'.date('Ymd').'.log', date('Y-m-d H:i:s')."\t".'SOAP archive:::'.$string_xml."\n", FILE_APPEND);

			$v_return = $client->Ariel_Archive($string_xml);

			file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/ExecuteTaskODA'.date('Ymd').'.log', date('Y-m-d H:i:s')."\t".'SOAP archive request:::'.print_r($client->__getLastRequest(), true)."\n", FILE_APPEND);

		}else if($mode == 'restore'){
			//make xml
			$string_xml = '<?xml version="1.0"?>';
			$string_xml .= '<request>';
			$string_xml .= '<objectID>'.$data['objectID'].'</objectID>';
			$string_xml .= '<objectCategory>'.$data['objectCategory'].'</objectCategory>';
			$string_xml .= '<filesPathRoot>'.$data['filesPathRoot'].'</filesPathRoot>';
			$string_xml .= '<filename>'.$data['filename'].'</filename>';
			$string_xml .= '<priority>'.$data['priority'].'</priority>';
			$string_xml .= '<soapWSDL>http://'.$_SERVER['SERVER_ADDR'].':'.$_SERVER['SERVER_PORT'].'/interface/app/ODS_L/common.php?wsdl</soapWSDL>';
			$string_xml .= '<soapService>Common</soapService>';
			$string_xml .= '<soapPort>CommonPort</soapPort>';
			$string_xml .= '</request>';

			file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/ExecuteTaskODA'.date('Ymd').'.log', date('Y-m-d H:i:s')."\t".'SOAP restore:::'.$string_xml."\n", FILE_APPEND);

			$v_return = $client->Ariel_Restore($string_xml);

			file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/ExecuteTaskODA'.date('Ymd').'.log', date('Y-m-d H:i:s')."\t".'SOAP restore request:::'.print_r($client->__getLastRequest(), true)."\n", FILE_APPEND);

		}else if($mode == 'pfr'){
			//make xml
			$string_xml = '<?xml version="1.0"?>';
			$string_xml .= '<request>';
			$string_xml .= '<objectID>'.$data['objectID'].'</objectID>';
			$string_xml .= '<objectCategory>'.$data['objectCategory'].'</objectCategory>';
			$string_xml .= '<filesPathRoot>'.$data['filesPathRoot'].'</filesPathRoot>';
			$string_xml .= '<priority>'.$data['priority'].'</priority>';
			$string_xml .= '<destFile>'.$data['destFile'].'</destFile>';
			$string_xml .= '<sourceFile>'.$data['sourceFile'].'</sourceFile>';
			$string_xml .= '<pfrMarkIn>'.$data['pfrMarkIn'].'</pfrMarkIn>';
			$string_xml .= '<pfrMarkOut>'.$data['pfrMarkOut'].'</pfrMarkOut>';
			$string_xml .= '<soapWSDL>http://'.$_SERVER['SERVER_ADDR'].':'.$_SERVER['SERVER_PORT'].'/interface/app/ODS_L/common.php?wsdl</soapWSDL>';
			$string_xml .= '<soapService>Common</soapService>';
			$string_xml .= '<soapPort>CommonPort</soapPort>';
			$string_xml .= '</request>';

			file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/ExecuteTaskODA'.date('Ymd').'.log', date('Y-m-d H:i:s')."\t".'SOAP pfr:::'.$string_xml."\n", FILE_APPEND);
			$v_return = $client->Ariel_PartialRestore($string_xml);
			
			file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/ExecuteTaskODA'.date('Ymd').'.log', date('Y-m-d H:i:s')."\t".'SOAP pfr request:::'.print_r($client->__getLastRequest(), true)."\n", FILE_APPEND);
		}
		
		$xml = simplexml_load_string($v_return);
		file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/ExecuteTaskODA'.date('Ymd').'.log', date('Y-m-d H:i:s')."\t".'Return XML:::'.$xml->asXML()."\n", FILE_APPEND);
		$v_return_data = (array) $xml;

		$v_reslt_arr['mode'] = $mode;
		
		if($v_return_data['status'] == 1000){
			//successfully
			$v_reslt_arr['status'] = '1';
			$v_reslt_arr['message'] = $v_return_data['msg'];

			$query = "
				UPDATE	BC_ARCHIVE_REQUEST
				SET		IF_KEY1='".$v_return_data['jobId']."'
				WHERE	TASK_ID=".$data['task_id']."
			";
			$db->exec($query);

		}else{
			//update status for BC_CONTENT_STATUS and BC_ARCHIVE_REQUEST

			$v_query = "
				UPDATE	BC_ARCHIVE_REQUEST
				SET		STATUS			= 'FAILED'
				WHERE	TASK_ID			=".$data['task_id']."
			";
			$db->exec($v_query);

			if($mode == 'archive'){
				$v_query = "
					UPDATE	BC_CONTENT_STATUS
					SET		ARCHIVE_STATUS	= 'E'
					WHERE	CONTENT_ID		= $content_id
				";
				$db->exec($v_query);
			}

			$v_reslt_arr['status'] = '0';
			$v_reslt_arr['message'] = $v_return_data['msg'];		
		}
		return $v_reslt_arr;
	} catch (SoapFault $fault) {
		// case SOAP fail
		$v_reslt_arr =	array();

		$v_query = "
				UPDATE	BC_ARCHIVE_REQUEST
				SET		STATUS			= 'FAILED'
				WHERE	TASK_ID			=".$data['task_id']."
			";
		$db->exec($v_query);
		if($mode == 'archive'){
				$v_query = "
					UPDATE	BC_CONTENT_STATUS
					SET		ARCHIVE_STATUS	= 'N'
					WHERE	CONTENT_ID		= $content_id
				";
				$db->exec($v_query);
			}

		$v_reslt_arr['status'] = '0';
		$v_reslt_arr['message'] = 'SOAP FAILED REQUEST';
		return $v_reslt_arr;
	}
}