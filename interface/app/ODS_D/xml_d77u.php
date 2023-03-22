<?php
/*
 * Subject : ODA L77U Inteface xml create.
 * param : $v_content_ids - target Content ID
 * Created date : 2016.06.27
 * Created by : g.c.Shin
 **/
function fn_create_xml_d77u($v_content_id)
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
	$v_XML_PATH = $v_storage['xml_path'];
	
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
		FROM	BC_CONTENT A
		WHERE	A.CONTENT_ID = ".$v_content_id."
	";
	
	$v_content = $db->queryRow($query);
	$ArchiveData = new SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\"?> \n<ArchiveData />");
	$ArchiveData->addChild('ContentID', $v_content_id);
	if(!empty($v_content['created_date'])) {
		$created_date = date('Y-m-d', strtotime($v_content['created_date']));
	}
	$ArchiveData->addChild('CreatedDate', $created_date);
	$ArchiveData->addChild('CreatedTime', $v_content['created_time']);
	$ArchiveData->addChild('FileTotalSize', $v_content['file_size']);
	$v_ud_content_id = $v_content['ud_content_id'];
	$v_bs_content_id = $v_content['bs_content_id'];
	$v_category_type = $v_content['category_type'];
	
	file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/log/xml_oda_d77u' . date('Ymd') . '.log', $_SERVER['REMOTE_ADDR'] . "\t[" . date('Y-m-d H:i:s') . '] 444 $query3 ::: ' .$v_content['CREATED_TIME']. "\r\n", FILE_APPEND);

	//User metadata
	$v_usr_meta_all = MetaDataClass::getFieldValueInfo('usr', $v_ud_content_id, $v_content_id);
	
	//Media Information
	$v_sys_meta_all = MetaDataClass::getFieldValueInfo('sys', $v_bs_content_id, $v_content_id);
	
	$MetaData = $ArchiveData->addChild('MetaData');
	
	$Attribute = $MetaData->addChild('Attribute', $v_content['title']);
	$Attribute->addAttribute('Name', 'title');
	foreach($v_usr_meta_all as $v_meta) {
		$Attribute = $MetaData->addChild('Attribute', $v_meta['value']);
		$Attribute->addAttribute('Name', $v_meta['usr_meta_field_title']);
	}
	
	$MetaData = $ArchiveData->addChild('MediaInfo');
	foreach($v_sys_meta_all as $v_meta) {
		$Attribute = $MetaData->addChild('Attribute', $v_meta['value']);
		$Attribute->addAttribute('Name', $v_meta['sys_meta_field_title']);
		
		if($v_meta['sys_meta_field_title'] == 'Duration'){
			$v_duration = $v_meta['value'];
		}
	}
	
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
	file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/log/xml_oda_d77u' . date('Ymd') . '.log', $_SERVER['REMOTE_ADDR'] . "\t[" . date('Y-m-d H:i:s') . '] 777 $query ::: ' .$query. "\r\n", FILE_APPEND);
	
	$v_file = $db->queryAll($query);
	
	$Files = $ArchiveData->addChild('Files');
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
	
	file_put_contents($v_XML_PATH.'/'.$v_content_id.'.XML', $ArchiveData->asXML());
	
	file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/log/xml_oda_d77u' . date('Ymd') . '.log', $_SERVER['REMOTE_ADDR'] . "\t[" . date('Y-m-d H:i:s') . '] 11 END ::: ' .$v_XML_PATH.'/'.$v_content_id.'.XML'. "\r\n", FILE_APPEND);
	file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/log/xml_oda_d77u' . date('Ymd') . '.log', $_SERVER['REMOTE_ADDR'] . "\t[" . date('Y-m-d H:i:s') . '] 12 END ::: ' .$ArchiveData->asXML(). "\r\n", FILE_APPEND);
}
?>