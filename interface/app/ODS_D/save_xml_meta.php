<?php
/*

 **/
function fn_save_meta($v_content_id,$v_type_job)
{
	global $db;
	//content duration 
	$v_duration = '';
	
	// Storage Infomation
	$query = "
		SELECT	MAX(C.PATH) AS HIGHRES_PATH
				,MAX(D.PATH) AS LOWRES_PATH
				,MAX(REF3) AS XML_PATH
		FROM	(
				SELECT	CASE WHEN US_TYPE = 'highres' THEN STORAGE_ID ELSE NULL END AS STORAGE1
						,CASE WHEN US_TYPE = 'lowres' THEN STORAGE_ID ELSE NULL END AS STORAGE2
				FROM	BC_UD_CONTENT_STORAGE
				WHERE	UD_CONTENT_ID = (SELECT	UD_CONTENT_ID FROM BC_CONTENT WHERE CONTENT_ID = ".$v_content_id.")
				) A
				LEFT OUTER JOIN BC_SYS_CODE B ON(B.CODE = 'INTERWORK_ODA_ODS_D')
				LEFT OUTER JOIN BC_STORAGE C ON(C.STORAGE_ID = A.STORAGE1)
				LEFT OUTER JOIN BC_STORAGE D ON(D.STORAGE_ID = A.STORAGE2)
		WHERE	B.USE_YN = 'Y'
	";
	
	$v_storage = $db->queryRow($query);
	
	$v_HIGHRES_PATH = $v_storage['highres_path'];
	$v_LOWRES_PATH = $v_storage['lowres_path'];
	// $v_XML_PATH = $v_storage['xml_path'];
	$v_XML_PATH = 'Y:\Storage\lowres\archive_xml';
	
	file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/log/xml_oda_d77u' . date('Ymd') . '.log', $_SERVER['REMOTE_ADDR'] . "\t[" . date('Y-m-d H:i:s') . '] v_type_job  ::: ' .$v_type_job. "\r\n", FILE_APPEND);
	file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/log/xml_oda_d77u' . date('Ymd') . '.log', $_SERVER['REMOTE_ADDR'] . "\t[" . date('Y-m-d H:i:s') . '] 222 $v_storage[highres_path] ::: ' .$v_storage['highres_path']. "\r\n", FILE_APPEND);
	file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/log/xml_oda_d77u' . date('Ymd') . '.log', $_SERVER['REMOTE_ADDR'] . "\t[" . date('Y-m-d H:i:s') . '] 222 $v_storage[lowres_path] ::: ' .$v_storage['lowres_path']. "\r\n", FILE_APPEND);
	file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/log/xml_oda_d77u' . date('Ymd') . '.log', $_SERVER['REMOTE_ADDR'] . "\t[" . date('Y-m-d H:i:s') . '] 222 $v_storage[xml_path] ::: ' .$v_storage['xml_path']. "\r\n", FILE_APPEND);
	
	// HEADER INFO
	$query = "
		SELECT	A.TITLE
				,SUBSTR(a.CREATED_DATE, 1, 8) AS CREATED_DATE
				,TO_CHAR(TO_DATE(a.CREATED_DATE, 'YYYYMMDDHH24MISS'), 'HH24:MI:SS') AS CREATED_TIME
				,A.UD_CONTENT_ID AS UD_CONTENT_ID
				,A.BS_CONTENT_ID AS BS_CONTENT_ID
				,SUBSTR(A.CATEGORY_FULL_PATH, 4,1) AS CATEGORY_TYPE
				,(	SELECT	SUM(M.FILESIZE)
					FROM	BC_MEDIA M
					WHERE	M.CONTENT_ID = A.CONTENT_ID
					AND		M.MEDIA_TYPE ='original'
				) AS FILE_SIZE
				,(	SELECT	C.CATEGORY_TITLE
					FROM	BC_CATEGORY C
					WHERE	C.CATEGORY_ID = A.CATEGORY_ID
				) AS CATEGORY_TITLE
		FROM	BC_CONTENT A
		WHERE	A.CONTENT_ID = ".$v_content_id."
	";
	
	$v_content = $db->queryRow($query);

	// Files1
	//changed for postgresql
	//SUBSTR(A.PATH, INSTR(A.PATH, '/', -1) + 1) AS FILE_NAME
	$query = "
		SELECT	A.PATH AS FILE_PATH
				,A.FILESIZE
				,A.MEDIA_TYPE
		FROM	BC_MEDIA A
		WHERE	A.CONTENT_ID = ".$v_content_id."
		AND		A.MEDIA_TYPE = 'original'
	";
	file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/log/xml_oda_d77u' . date('Ymd') . '.log', $_SERVER['REMOTE_ADDR'] . "\t[" . date('Y-m-d H:i:s') . '] 777 $query ::: ' .$query."\r\n", FILE_APPEND);
	
	$v_file = $db->queryAll($query);

	if($v_type_job=='save_xml_meta'){
		$SaveMetadata = new SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\"?> \n<SaveMetadata />");
		$SaveMetadata->addChild('ContentID', $v_content_id);
		if(!empty($v_content['created_date'])) {
			$created_date = date('Y-m-d', strtotime($v_content['created_date']));
		}
		$SaveMetadata->addChild('CreatedDate', $created_date);
		$SaveMetadata->addChild('CreatedTime', $v_content['created_time']);
		$SaveMetadata->addChild('FileTotalSize', $v_content['file_size']);
		$v_ud_content_id = $v_content['ud_content_id'];
		$v_bs_content_id = $v_content['bs_content_id'];
		$v_category_type = $v_content['category_type'];

		file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/log/xml_oda_d77u' . date('Ymd') . '.log', $_SERVER['REMOTE_ADDR'] . "\t[" . date('Y-m-d H:i:s') . '] 444 $query3 ::: ' .print_r($v_content, true). "\r\n", FILE_APPEND);
		file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/log/xml_oda_d77u' . date('Ymd') . '.log', $_SERVER['REMOTE_ADDR'] . "\t[" . date('Y-m-d H:i:s') . '] 444 $query3 ::: ' .$v_content['created_time']. "\r\n", FILE_APPEND);
		file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/log/xml_oda_d77u' . date('Ymd') . '.log', $_SERVER['REMOTE_ADDR'] . "\t[" . date('Y-m-d H:i:s') . '] 444 $query3 ::: ' .$v_content['created_date']. "\r\n", FILE_APPEND);

		//User metadata
		$v_usr_meta_all = MetaDataClass::getFieldValueInfo('usr', $v_ud_content_id, $v_content_id);
		
		//Media Information
		$v_sys_meta_all = MetaDataClass::getFieldValueInfo('sys', $v_bs_content_id, $v_content_id);
		
		$MetaData = $SaveMetadata->addChild('MetaData');
		
		$Attribute = $MetaData->addChild('Attribute', $v_content['title']);
		$Attribute->addAttribute('Name', 'Title');
		$Attribute = $MetaData->addChild('Attribute', $v_content['category_title']);
		$Attribute->addAttribute('Name', 'Category');
		file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/log/xml_oda_d77u' . date('Ymd') . '.log', $_SERVER['REMOTE_ADDR'] . "\t[" . date('Y-m-d H:i:s') . '] 444 $query5 ::: ' .print_r($Attribute, true). "\r\n", FILE_APPEND);
		foreach($v_usr_meta_all as $v_meta) {
			$Attribute = $MetaData->addChild('Attribute', $v_meta['value']);
			$Attribute->addAttribute('Name', $v_meta['usr_meta_field_title']);
		}
		
		$MetaData = $SaveMetadata->addChild('MediaInfo');
		foreach($v_sys_meta_all as $v_meta) {
			$Attribute = $MetaData->addChild('Attribute', $v_meta['value']);
			$Attribute->addAttribute('Name', $v_meta['sys_meta_field_title']);
			
			if($v_meta['sys_meta_field_title'] == 'Duration'){
				$v_duration = $v_meta['value'];
			}
		}
		file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/log/xml_oda_d77u' . date('Ymd') . '.log', $_SERVER['REMOTE_ADDR'] . "\t[" . date('Y-m-d H:i:s') . '] 777 $query ::: ' .print_r($v_file, true)."\r\n", FILE_APPEND);
		$Files = $SaveMetadata->addChild('Files');
		foreach($v_file as $file_row) {
			$filepath = $file_row['file_path'];
			$filepath = str_replace('\\', '/', $filepath);
			$filepath = trim($filepath, '/');
			$filepath_array = explode('/', $filepath);
			$filename = array_pop($filepath_array);

			$v_FileData = $Files->addChild('FileData');
			$v_FileData->addChild('RootPath', $v_HIGHRES_PATH);
			$v_FileData->addChild('FilePath', $file_row['file_path']);
			$v_FileData->addChild('FileName', $filename);
			$v_FileData->addChild('FileSize', $file_row['filesize']);
			$v_FileData->addChild('FileAttr', $file_row['media_type']);
		
			if($file_row['media_type'] == 'original' || $file_row['media_type'] == 'proxy'){
				$v_FileData->addChild('Duration', $v_duration);
			}
		}

		if($v_content['title']==null||$v_content['title']==''){
			file_put_contents($v_XML_PATH.'/'.$v_content_id.'.XML', $SaveMetadata->asXML());
		}
		else{
			file_put_contents($v_XML_PATH.'/'.$v_content['title'].'.XML', $SaveMetadata->asXML());
		}
	}
	else if($v_type_job=='save_text_meta'){
			//User metadata
		
		$v_ud_content_id = $v_content['ud_content_id'];
		$v_bs_content_id = $v_content['bs_content_id'];
		$v_category_type = $v_content['category_type'];
		// //User metadata
		$v_usr_meta_all = MetaDataClass::getFieldValueInfo('usr', $v_ud_content_id, $v_content_id);
		
		//Media Information
		$v_sys_meta_all = MetaDataClass::getFieldValueInfo('sys', $v_bs_content_id, $v_content_id);

		$string_txt.= '[ContentInfo] '."\r\n";
		$string_txt.= 'Title = '.$v_content['title']."\r\n";
		$string_txt.= 'Category = '.$v_content['category_title']."\r\n";
		$string_txt.= '[Metadata] '."\r\n";
		$string_txt.= "\r\n";
		foreach($v_usr_meta_all as $v_meta) {
			$string_txt.= $v_meta['usr_meta_field_title'].' = '.$v_meta['value']."\r\n";
			
		}
		$string_txt.= "\r\n";
		$string_txt.= '[MediaInfo] '."\r\n";
		foreach($v_sys_meta_all as $v_meta) {
			$string_txt.= $v_meta['sys_meta_field_title'].' = '.$v_meta['value']."\r\n";
			// if($v_meta['sys_meta_field_title'] == 'Duration'){
			// 	$string_txt.= 'Duration = '.$v_meta['value']."\n";
			// 	// $v_duration = $v_meta['value'];
			// }
		}
		$string_txt.= "\r\n";
		$string_txt.= '[Original File] '."\r\n";
		foreach($v_file as $file_row) {
			$filepath = $file_row['file_path'];
			$filepath = str_replace('\\', '/', $filepath);
			$filepath = trim($filepath, '/');
			$filepath_array = explode('/', $filepath);
			$filename = array_pop($filepath_array);
			$string_txt.= 'RootPath = '.$v_HIGHRES_PATH."\r\n";
			$string_txt.= 'FilePath = '.$file_row['file_path']."\r\n";
			$string_txt.= 'FileName = '.$filename."\r\n";
			$string_txt.= 'FileSize = '.$file_row['filesize']."\r\n";
			$string_txt.= 'FileAttr = '.$file_row['media_type']."\r\n";			
			if($file_row['media_type'] == 'original' || $file_row['media_type'] == 'proxy'){
				// $v_FileData->addChild('Duration', $v_duration);
				$string_txt.= 'Duration = '.$v_duration."\r\n";
			}
		}
		if($v_content['title']==null||$v_content['title']==''){
			
			file_put_contents($v_XML_PATH.'/'.$v_content_id.'.txt',$string_txt."\r\n");
		}
		else{
			file_put_contents($v_XML_PATH.'/'.$v_content['title'].'.txt', $string_txt."\r\n");
			// file_put_contents($v_XML_PATH.'/'.$v_content['title'].'.txt', $string_xml_1);
		}
	}
	else if($v_type_job=='save_json_meta'){
		// $arr_data = array();
		
		$v_ud_content_id = $v_content['ud_content_id'];
		$v_bs_content_id = $v_content['bs_content_id'];
		$v_category_type = $v_content['category_type'];
		// //User metadata
		$v_usr_meta_all = MetaDataClass::getFieldValueInfo('usr', $v_ud_content_id, $v_content_id);
		
		//Media Information
		$v_sys_meta_all = MetaDataClass::getFieldValueInfo('sys', $v_bs_content_id, $v_content_id);
	
		$string_txt.= "{";
		
		$string_txt.= '"'.'ContentInfo'.'" : {';
		// $string_txt.= '"'.'Title :'.'"'.$v_content['title'];
		$string_txt.= '"'.'Title'.'" : '.'"'.$v_content['title'].'",';
		$string_txt.= '"'.'Category'.'" : '.'"'.$v_content['category_title'].'"';
		$string_txt.= "},";
		$string_txt.= '"'.'Metadata'.'" : {';
		foreach($v_usr_meta_all as $v_meta) {
			//$string_txt.= $v_meta['usr_meta_field_title'].' = '.$v_meta['value'];
			if(next($v_usr_meta_all)){
				$string_txt.= '"'.$v_meta['usr_meta_field_title'].'" : '.'"'.$v_meta['value'].'",';
			}else{
				$string_txt.= '"'.$v_meta['usr_meta_field_title'].'" : '.'"'.$v_meta['value'].'"';
			}
			
			
		}
		$string_txt.= "},";
		$string_txt.= '"'.'MediaInfo'.'" : {';
		foreach($v_sys_meta_all as $v_meta) {
			//$string_txt.= $v_meta['sys_meta_field_title'].' = '.$v_meta['value'];
			if(next($v_sys_meta_all)){
				$string_txt.= '"'.$v_meta['sys_meta_field_title'].'" : '.'"'.$v_meta['value'].'",';
			}else{
				$string_txt.= '"'.$v_meta['sys_meta_field_title'].'" : '.'"'.$v_meta['value'].'"';
			}

			// if($v_meta['sys_meta_field_title'] == 'Duration'){
			// 	$string_txt.= 'Duration = '.$v_meta['value']."\n";
			// 	// $v_duration = $v_meta['value'];
			// }
		}
		$string_txt.= "},";
		$string_txt.= '"'.'Original File'.'" : {';
		foreach($v_file as $file_row) {
			$filepath = $file_row['file_path'];
			$filepath = str_replace('\\', '/', $filepath);
			$filepath = trim($filepath, '/');
			$filepath_array = explode('/', $filepath);
			$filename = array_pop($filepath_array);
			$string_txt.= '"'.'RootPath'.'" : '.'"'.$v_HIGHRES_PATH.'",';
			$string_txt.= '"'.'FilePath'.'" : '.'"'.$file_row['file_path'].'",';
			$string_txt.= '"'.'FileName'.'" : '.'"'.$filename.'",';
			$string_txt.= '"'.'FileSize'.'" : '.'"'.$file_row['FileSize'].'",';
			$string_txt.= '"'.'FileAttr'.'" : '.'"'.$file_row['media_type'].'",';
		
			if($file_row['media_type'] == 'original' || $file_row['media_type'] == 'proxy'){
				// $v_FileData->addChild('Duration', $v_duration);
				$string_txt.= '"'.'Duration'.'" : '.'"'.$v_duration.'"';
				//$string_txt.= 'Duration = '.$v_duration;
			}
		}
		$string_txt.= "}";
		$string_txt.= "}";
		file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/log/xml_oda_d77u' . date('Ymd') . '.log', $_SERVER['REMOTE_ADDR'] . "\t[" . date('Y-m-d H:i:s') . '] review_reviewer END :::  ' .print_r($string_txt, true). "\r\n", FILE_APPEND);
		$review_reviewer = array_push($arr_data, $string_txt);
		file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/log/xml_oda_d77u' . date('Ymd') . '.log', $_SERVER['REMOTE_ADDR'] . "\t[" . date('Y-m-d H:i:s') . '] review_reviewer END :::  ' .print_r($review_reviewer, true). "\r\n", FILE_APPEND);
	
		if($v_content['title']==null||$v_content['title']==''){
			
			file_put_contents($v_XML_PATH.'/'.$v_content_id.'.json',$string_txt."\n");
		}
		else{
			file_put_contents($v_XML_PATH.'/'.$v_content['title'].'.json', $string_txt."\n");
			// file_put_contents($v_XML_PATH.'/'.$v_content['title'].'.txt', $string_xml_1);
		}
	}
	
	
	
	
	//vendor\danielstjules\stringy\src
	// file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/log/xml_oda_d77u' . date('Ymd') . '.log', $_SERVER['REMOTE_ADDR'] . "\t[" . date('Y-m-d H:i:s') . '] 10 END :::  ' .$v_content['title']. "\r\n", FILE_APPEND);
	// file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/log/xml_oda_d77u' . date('Ymd') . '.log', $_SERVER['REMOTE_ADDR'] . "\t[" . date('Y-m-d H:i:s') . '] 11 END ::: ' .$v_XML_PATH.'/'.$v_content_id.'.XML'. "\r\n", FILE_APPEND);
	// file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/log/xml_oda_d77u' . date('Ymd') . '.log', $_SERVER['REMOTE_ADDR'] . "\t[" . date('Y-m-d H:i:s') . '] 12 END ::: ' .$SaveMetadata->asXML(). "\r\n", FILE_APPEND);
}
?>