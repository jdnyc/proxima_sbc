<?php
if(!defined('DS'))
	define('DS', DIRECTORY_SEPARATOR);
require_once(dirname(__DIR__) . DS . 'lib'. DS .'Schedule.class.php');

class InterfaceClass
{
	public $type;
	public $response;

	function __construct()
	{
		$this->response = $response;
		$this->type = $type;
	}

	static function client($url, $function , $param ){
		$error_code = 501;
		$client = new nusoap_client($url, true);
		$client->xml_encoding = "UTF-8";
		$client->soap_defencoding = "UTF-8";
		$client->decode_utf8 = false;
		$filename = 'http_raw_post_data_client'.date('Y-m-d').'.log';
		if ( $err = $client->getError() ) {
			throw new Exception('soap_error: '.print_r($err), $error_code );
		}
		InterfaceClass::_LogFile($filename, 'Client call', 'url:'.$url.', function:'.$function.', param:'.print_r($param, true));
		$result = $client->call($function, $param);
		if ($client->fault) {
			InterfaceClass::_LogFile($filename, 'Client fault', 'soap_error: '.$result, $error_code);
			throw new Exception('soap_error: '.$result, $error_code );
		}
		if ( $err = $client->getError() ) {
			InterfaceClass::_LogFile($filename, 'Client error','soap_error: '.$err.'['.$result.']', $error_code);
			throw new Exception('soap_error: '.$err.'['.$result.']', $error_code );
		}
		InterfaceClass::_LogFile($filename, 'Client result', $result);
		return $result;
	}

	static function _LogFile($filename,$name,$contents){
		$root = $_SERVER['DOCUMENT_ROOT'].'/log/';
		if(empty($filename)){
			$filename = 'http_raw_post_data_'.date('Y-m-d').'.log';
		}
		@file_put_contents($root.$filename, "\n".$_SERVER['REMOTE_ADDR']."\t".date('Y-m-d H:i:s')."]\t".$name." : \n".print_r($contents, true)."\n", FILE_APPEND);
	}

	static function checkSyntax($receive)
	{
		if($rtn = json_decode($receive ,true ) ){
			$type = 'JSON';
		}else{

			libxml_use_internal_errors(true);
			$rtn = simplexml_load_string($receive);
			if (!$rtn) {
				foreach(libxml_get_errors() as $error)
				{
					$err_msg .= $error->message . "\n";
				}
				$type = 'null';
			}else{
				$type = 'XML';
			}
		}

		return array(
			'type' => $type,
			'msg' => $err_msg,
			'data' => $rtn
		);
	}

	function DefualtResponse($type){
		$this->type = $type;
		$success = 'true';
		$status = 0;
		$message = 'OK';

		$response_json = array(
			'success' => $success,
			'status' => $status,
			'message' => $message
		);
		$response_xml = new SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<Response />");
		if($type == 'JSON'){

			$this->response = $response_json;
			return $response_json;
		}else{

			$response_xml->addChild('message', $message);
			$response_xml->addChild('status', $status);
			$response_xml->addChild('success', $success);

			$this->response = $response_xml;
			return $response_xml;
		}
	}

	function getResponse(){
		return $this->response;
	}

	function setResponse($response){
		$this->response = $response;
		return true;
	}

	function ReturnResponse($type =null, $response = null){
		if( empty($type) ) $type = $this->type;
		if( empty($response) ) $response = $this->response;

		if($type == 'JSON'){
			return json_encode($response);
		}else{
			return $response->asXML();
		};
	}

	static function insertContent($metaValues, $content_id, $category_id, $bs_content_id, $ud_content_id,$title, $user_id , $status = -3 , $expire_date = '')
	{
		global $db;

		$category_full_path	= getCategoryFullPath($category_id);

		$cur_time 			= date('YmdHis');
		//$expired_date = '9999-12-31';
		$expired_date = $expired_date ? $expired_date :'9999-12-31';

		if( empty($title) ) $title = 'Temp';
		$Temp_Title = $db->escape($title);
		$query = "insert into bc_content(CATEGORY_ID, CATEGORY_FULL_PATH, BS_CONTENT_ID, UD_CONTENT_ID, CONTENT_ID, TITLE, REG_USER_ID, CREATED_DATE, STATUS, EXPIRED_DATE) values('$category_id', '$category_full_path', '$bs_content_id', '$ud_content_id', '$content_id', '$Temp_Title', '$user_id', '$cur_time', '$status', '$expired_date')";
		$r = $db->exec($query);

		$action = 'regist';
		$description = 'regist';
		insertLog($action, $user_id, $content_id, $description);

		self::insertUDSystem($content_id);
		return $content_id;
	}

	// CG는 등록시 regist_target값에 따라 상암/광화문 구분을 함
	static function insertCGContent($metaValues, $content_id, $category_id, $bs_content_id, $ud_content_id,$title, $user_id , $status = -3 , $expire_date = '')
	{
		global $db;

		$category_full_path	= getCategoryFullPath($category_id);

		$cur_time 			= date('YmdHis');
		//$expired_date = '9999-12-31';
		$expired_date = $expired_date ? $expired_date :'9999-12-31';

		if( empty($title) ) $title = 'Temp';
		$Temp_Title = $db->escape($title);
		$query = "insert into bc_content(CATEGORY_ID, CATEGORY_FULL_PATH, BS_CONTENT_ID, UD_CONTENT_ID, CONTENT_ID, TITLE, REG_USER_ID, CREATED_DATE, STATUS, EXPIRED_DATE) values('$category_id', '$category_full_path', '$bs_content_id', '$ud_content_id', '$content_id', '$Temp_Title', '$user_id', '$cur_time', '$status', '$expired_date')";
		$r = $db->exec($query);

		$action = 'regist';
		$description = 'regist';
		insertLog($action, $user_id, $content_id, $description);

		return $content_id;
	}

	// 그룹 대표(폴더) 콘텐츠 등록
	static function insertGroupContent($metaValues, $content_id, $category_id, $bs_content_id, $ud_content_id,$title, $user_id , $status = -3, $count, $expired_date)
	{
		global $db;

		$category_full_path	= getCategoryFullPath($category_id);
		$cur_time 			= date('YmdHis');
		//$expired_date = '9999-12-31';
		$expired_date = $expired_date ? $expired_date :'9999-12-31';
		$is_group = 'C';

		if( empty($title) ) $title = 'Temp';

		$Temp_Title = $db->escape($title);
		$query = "insert into bc_content(CATEGORY_ID, CATEGORY_FULL_PATH, BS_CONTENT_ID, UD_CONTENT_ID, CONTENT_ID, TITLE, REG_USER_ID, CREATED_DATE, STATUS, EXPIRED_DATE, IS_GROUP, GROUP_COUNT)
					values('$category_id', '$category_full_path', '$bs_content_id', '$ud_content_id', '$content_id', '$Temp_Title', '$user_id', '$cur_time', '$status', '$expired_date', 'G', '$count')";
		$r = $db->exec($query);

		$action = 'regist';
		$description = 'regist represent of group';
		insertLog($action, $user_id, $content_id, $description);

		self::insertUDSystem($content_id);
		return $content_id;
	}

	// 그룹 대표(폴더) 콘텐츠 등록
	static function insertCGGroupContent($metaValues, $content_id, $category_id, $bs_content_id, $ud_content_id,$title, $user_id , $status = -3, $count, $expired_date)
	{
		global $db;

		$category_full_path	= getCategoryFullPath($category_id);
		$cur_time 			= date('YmdHis');
		//$expired_date = '9999-12-31';
		$expired_date = $expired_date ? $expired_date :'9999-12-31';
		$is_group = 'C';

		if( empty($title) ) $title = 'Temp';

		$Temp_Title = $db->escape($title);
		$query = "insert into bc_content(CATEGORY_ID, CATEGORY_FULL_PATH, BS_CONTENT_ID, UD_CONTENT_ID, CONTENT_ID, TITLE, REG_USER_ID, CREATED_DATE, STATUS, EXPIRED_DATE, IS_GROUP, GROUP_COUNT)
					values('$category_id', '$category_full_path', '$bs_content_id', '$ud_content_id', '$content_id', '$Temp_Title', '$user_id', '$cur_time', '$status', '$expired_date', 'G', '$count')";
		$r = $db->exec($query);

		$action = 'regist';
		$description = 'regist represent of group';
		insertLog($action, $user_id, $content_id, $description);

		return $content_id;
	}

	// 그룹내 콘텐츠 등록
	static function insertGroupChildContent($metaValues, $content_id, $category_id, $bs_content_id, $ud_content_id,$title, $user_id , $status = -3, $parent_id, $index, $expired_date )
	{
		global $db;
		$category_full_path	= getCategoryFullPath($category_id);
		$cur_time 			= date('YmdHis');
		//$expired_date = '9999-12-31';
		$expired_date = $expired_date ? $expired_date :'9999-12-31';
		$is_group = 'C';

		if( empty($title) ) $title = 'Temp';

		$Temp_Title = $db->escape($title);
		$query = "insert into bc_content(CATEGORY_ID, CATEGORY_FULL_PATH, BS_CONTENT_ID, UD_CONTENT_ID, CONTENT_ID, TITLE, REG_USER_ID, CREATED_DATE, STATUS, EXPIRED_DATE, IS_GROUP, PARENT_CONTENT_ID, GROUP_COUNT)
					values('$category_id', '$category_full_path', '$bs_content_id', '$ud_content_id', '$content_id', '$Temp_Title', '$user_id', '$cur_time', '$status', '$expired_date', 'C', '$parent_id', '$index')";
		$r = $db->exec($query);

		$action = 'regist';
		$description = 'regist child content of group';
		insertLog($action, $user_id, $content_id, $description);

		self::insertUDSystem($content_id);
		return $content_id;
	}

	static function insertReadyContent($content_id, $title, $user_id )
	{
		global $db;
		$status= 0;
		$created_date = date('YmdHis');
		$query = "insert into BC_READY_CONTENT(CONTENT_ID,TITLE,CREATED_DATE,STATUS,USER_ID) values('$content_id','$title','$created_date','$status', '$user_id')";
		$r = $db->exec($query);
		self::insertUDSystem($content_id);
	}

	static function insertUDSystem($content_id)
	{
		return true;
	}

	static function insertFILE_ID($content_id, $ud_content_id)
	{
		global $db;
		$table_name = MetaDataClass::getTableName('usr', $ud_content_id );
		//등록될 필드명
		$field_name='USR_MATERIALID';

		$checkField = $db->queryOne("SELECT count(*) FROM ALL_TAB_COLUMNS WHERE TABLE_NAME='$table_name' and column_name = '$field_name'");

		$field_id = buildFileID();

		if( empty($checkField) ){
			return false;
		}
		$checkdata = $db->queryRow("select * from $table_name where usr_content_id='$content_id'");

		if( empty($checkdata) ){
			$query = "insert into $table_name (USR_CONTENT_ID, $field_name ) values('$content_id', '".$field_id."')";
			$r = $db->exec($query);
		}else{
			$query = "update $table_name set $field_name='".$field_id."' where usr_content_id='$content_id'";
			$r = $db->exec($query);
		}
		return $field_id;
	}

	static function insertFILE_ID_KeepID($content_id, $ud_content_id, $usr_materialid)
	{
		global $db;
		$table_name = MetaDataClass::getTableName('usr', $ud_content_id );
		//등록될 필드명
		$field_name='USR_MATERIALID';

		$checkField = $db->queryOne("SELECT count(*) FROM ALL_TAB_COLUMNS WHERE TABLE_NAME='$table_name' and column_name = '$field_name'");

		$field_id = $usr_materialid;//buildFileID();

		if( empty($checkField) ){
			return false;
		}
		$checkdata = $db->queryRow("select * from $table_name where usr_content_id='$content_id'");

		if( empty($checkdata) ){
			$query = "insert into $table_name (USR_CONTENT_ID, $field_name ) values('$content_id', '".$field_id."')";
			$r = $db->exec($query);
		}else{
			$query = "update $table_name set $field_name='".$field_id."' where usr_content_id='$content_id'";
			$r = $db->exec($query);
		}
		return $field_id;
	}

	static function updateCategoryforTURN($content_id, $turn){
		global $db;
		//회차 카테고리 생성 2014-12-11 이성용

		//코드에서 앞 문자만 제거
		//$turn_ren = substr($turn, 1);
		if($turn){
			$turn_num = (int)$turn;
		}
		$content = $db->queryRow("select * from view_bc_content where content_id='$content_id'");

		if( $content['parent_id'] == 0 ){
			$sub_category_id = $db->queryOne("select category_id from bc_category where parent_id='$content[category_id]' and CATEGORY_TITLE='$turn_num' ");
			if( empty($sub_category_id) ){
				//신규 카테고리 생성 후 콘텐츠 카테고리 업데이트
				$seq =  getSequence('SEQ_BC_CATEGORY_ID');
				$r = $db->exec("insert into bc_category (category_id, parent_id, category_title, no_children, show_order) values
				($seq, $content[category_id], '$turn_num', '1', $turn_num )");
				$sub_category_id = $seq;

				$child_check = $db->queryRow("select * from bc_category where category_id='$content[category_id]'");
				if($child_check['no_children'] == 1){
					$r = $db->exec("update bc_category set NO_CHILDREN=0 where category_id='$content[category_id]'");
				}
			}

			$category_full_path	= getCategoryFullPath($sub_category_id);
			$r = $db->exec("update bc_content set category_id='$sub_category_id', CATEGORY_FULL_PATH='$category_full_path' where content_id='$content_id'");
		} else {
			$parent_category_id = $content[parent_id];
			$sub_category_id = $db->queryOne("select category_id from bc_category where parent_id='$parent_category_id' and CATEGORY_TITLE='$turn_num' ");
			if( empty($sub_category_id) ){
				//신규 카테고리 생성 후 콘텐츠 카테고리 업데이트
				$seq =  getSequence('SEQ_BC_CATEGORY_ID');
				$r = $db->exec("insert into bc_category (category_id, parent_id, category_title, no_children, show_order) values
						($seq, $parent_category_id, '$turn_num', '1', $turn_num )");
				$sub_category_id = $seq;

				$child_check = $db->queryRow("select * from bc_category where category_id='$parent_category_id'");
				if($child_check['no_children'] == 1){
					$r = $db->exec("update bc_category set NO_CHILDREN=0 where category_id='$parent_category_id'");
				}
			}
			$category_full_path	= getCategoryFullPath($sub_category_id);
			$r = $db->exec("update bc_content set category_id='$sub_category_id', CATEGORY_FULL_PATH='$category_full_path' where content_id='$content_id'");
		}

		return true;
	}

	static function getUD_SYSTEM($content_id){
		global $db;
		return $db->queryOne("select UD_SYSTEM_CODE from BC_UD_SYSTEM where content_id='$content_id'");
	}

	static function insertBaseContentValue($content_id, $content_type_id )
	{
		global $db;
		return true;
	}

	static function insertMedia($content_id, $channel, $ud_content_id)
	{
		global $db;

		$row = $db->queryRow("select * from view_bc_content where content_id='$content_id'");
		$UD_PATH = self::getCategoryDirPath($row, $row['category_id'], true );
		$metarow = MetaDataClass::getValueInfo('usr', $ud_content_id, $content_id );
		$usr_materialid = $metarow[usr_materialid];
		$fullpath = $UD_PATH."/clip/".$usr_materialid;
		$created_datetime = date('YmdHis');
		$expired_date = self::check_media_expire_date($ud_content_id, 'original', $created_datetime);
		$query = "insert into bc_media (content_id, media_type, storage_id, path, reg_type, created_date, expired_date)
					values ({$content_id}, 'original', 0, '{$fullpath}', '{$channel}', '{$created_datetime}', '{$expired_date}')";
		$db->exec($query);
	}

	static function insertCGMedia($content_id, $channel, $ud_content_id, $file, $type = null, $child_file = null)
	{
		global $db;

		$row = $db->queryRow("select * from view_bc_content where content_id='$content_id'");
		$UD_PATH = self::getCategoryDirPath($row, $row['category_id'], true );
		//$metarow = MetaDataClass::getValueInfo('usr', $ud_content_id, $content_id );
		//$usr_materialid = $metarow[usr_materialid];
		//$fullpath = $UD_PATH."/clip/".$usr_materialid;
		$fullpath = $UD_PATH."/clip/".$file;
		if($type == 'child') {
			$fullpath = $fullpath."/".$child_file;
		}
		$created_datetime = date('YmdHis');
		$expired_date = self::check_media_expire_date($ud_content_id, 'original', $created_datetime);
		$query = "insert into bc_media (content_id, media_type, storage_id, path, reg_type, created_date, expired_date)
		values ({$content_id}, 'original', 0, '{$fullpath}', '{$channel}', '{$created_datetime}', '{$expired_date}')";
		$db->exec($query);
		//전송후 상태값을 승인상태로 업데이트
		$update_status = $db->exec("
							UPDATE BC_CONTENT
							SET STATUS = '2'
							WHERE CONTENT_ID = '$content_id'
						");
	}

	// 등록시 원본 파일명을 sysmeta 테이블에 등록
	static function insertSysMetaValus($metaValues, $content_id, $meta_table_id) {
		global $db;

		$fieldKey = array();
		$fieldValue = array();
		//필드 목록 배열
		$metaFieldInfo = MetaDataClass::getMetaFieldInfo ('sys' , $meta_table_id );
		//필드의 id => name
		$fieldNameMap = MetaDataClass::getFieldIdtoNameMap('sys' , $meta_table_id );
		//테이블 명
		$tablename = MetaDataClass::getTableName('sys', $meta_table_id );
		//기본 데이터유형 변환
		$metaValues = MetaDataClass::getDefValueRender('sys' , $meta_table_id , $metaValues);

		foreach($fieldNameMap as $sys_meta_field_id => $name )
		{
			$value = $metaValues[$sys_meta_field_id];
			$value = $db->escape($value);
			if(!is_null($metaValues[$sys_meta_field_id])) {
				array_push($fieldKey, $name );
				array_push($fieldValue, $value );
			}
		}

		if( MetaDataClass::isNewMeta('sys' ,$meta_table_id , $content_id) ){
			//신규 등록
			array_push($fieldKey, 'sys_content_id' );
			array_push($fieldValue, $content_id );
			$query = $db->InsertQuery($tablename ,$fieldKey, $fieldValue);

		}else{
			//업데이트
			$query = $db->UpdateQuery($tablename ,$fieldKey, $fieldValue, "sys_content_id='$content_id'" );
		}
		InterfaceClass::_LogFile('','query',$query);
		$r = $db->exec($query);

		return true;

	}


	static function  insertMetaValues($metaValues, $content_id, $meta_table_id ,$update = null )
	{
		global $db;

		$is_turn_update = false;
		@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/json_register_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] return666 ===> '.print_r($metaValues, true)."\r\n", FILE_APPEND);
		$fieldKey = array();
		$fieldValue = array();
		//필드 목록 배열
		$metaFieldInfo = MetaDataClass::getMetaFieldInfo ('usr' , $meta_table_id );
		//필드의 id => name
		$fieldNameMap = MetaDataClass::getFieldIdtoNameMap('usr' , $meta_table_id );
		//테이블 명
		$tablename = MetaDataClass::getTableName('usr', $meta_table_id );
		//기본 데이터유형 변환
		$metaValues = MetaDataClass::getDefValueRender('usr' , $meta_table_id , $metaValues);

		foreach($fieldNameMap as $usr_meta_field_id => $name )
		{
			$value = $metaValues[$usr_meta_field_id];
			$value = $db->escape($value);
			if(!is_null($metaValues[$usr_meta_field_id])) {
				array_push($fieldKey, $name );
				//array_push($fieldValue, "'".$value."'" );
				array_push($fieldValue, $value);
			}
			if( $name == 'USR_TURN' ){
				if( !empty($value) ){
					$is_turn_update = $value;
				}
			}
		}
		@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/json_register_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] return777 ===> '.print_r($metaValues, true)."\r\n", FILE_APPEND);

		if( MetaDataClass::isNewMeta('usr' ,$meta_table_id , $content_id) ){
			//신규 등록
			array_push($fieldKey, 'usr_content_id' );
			array_push($fieldValue, $content_id );
			$query = $db->InsertQuery($tablename ,$fieldKey, $fieldValue);

		}else{
			//업데이트
			$query = $db->UpdateQuery($tablename ,$fieldKey, $fieldValue, "usr_content_id='$content_id'" );
		}
		InterfaceClass::_LogFile('','query',$query);
		$r = $db->exec($query);

		return true;
	}

	static function  insertHrdkCodeMetaValues($metaValues, $content_id)
	{
		global $db;

		$fieldKey = array();
		$fieldValue = array();
		// HRDK 용으로 분류값 입력하도록 수정
		foreach($metaValues as $k_type => $k_type_value )
		{

			if(strpos($k_type, 'k_type') !== false && !empty($k_type_value)) {
				$query = "INSERT INTO TB_CLASSIFICATIONMASTER VALUES('$content_id', '$k_type_value')";
				$r = $db->exec($query);
				InterfaceClass::_LogFile('','query',$query);
			}
		}

		return true;
	}

	static function  insertMetaValuesKeepID($metaValues, $content_id, $meta_table_id ,$update = null, $usr_materialid = null )
	{
		global $db;

		$is_turn_update = false;

		$fieldKey = array();
		$fieldValue = array();
		//필드 목록 배열
		$metaFieldInfo = MetaDataClass::getMetaFieldInfo ('usr' , $meta_table_id );
		//필드의 id => name
		$fieldNameMap = MetaDataClass::getFieldIdtoNameMap('usr' , $meta_table_id );
		//테이블 명
		$tablename = MetaDataClass::getTableName('usr', $meta_table_id );
		//기본 데이터유형 변환
		$metaValues = MetaDataClass::getDefValueRender('usr' , $meta_table_id , $metaValues);

		foreach($fieldNameMap as $usr_meta_field_id => $name )
		{
			$value = $metaValues[$usr_meta_field_id];
			$value = $db->escape($value);
			if(!is_null($metaValues[$usr_meta_field_id])) {
				array_push($fieldKey, $name );
				array_push($fieldValue, $value);
			}
			if( $name == 'USR_TURN' ){
				if( !empty($value) ){
					$is_turn_update = $value;
				}
			}
		}

		if( MetaDataClass::isNewMeta('usr' ,$meta_table_id , $content_id) ){
			//신규 등록
			array_push($fieldKey, 'usr_content_id' );
			array_push($fieldValue, $content_id );
			$query = $db->InsertQuery($tablename ,$fieldKey, $fieldValue);

		}else{
			//업데이트
			$query = $db->UpdateQuery($tablename ,$fieldKey, $fieldValue, "usr_content_id='$content_id'" );
		}
		InterfaceClass::_LogFile('','query',$query);
		$r = $db->exec($query);

		//DAS에서 넘어온 소재, 리스토어로 넘어올 때는 소재ID 그대로(M이든 P이든)
		if($usr_materialid == '') {
			$file_id = self::insertFILE_ID($content_id, $meta_table_id);
		} else {
			$file_id = self::insertFILE_ID_KeepID($content_id, $meta_table_id, $usr_materialid);
		}

		//임시 제목일경우 파일아이디로 업데이트 2014-12-11 이성용
		if( !empty($file_id) ){
			$is_temp = $db->queryOne("select content_id from bc_content where content_id='$content_id' and title='Temp' ");
			if( !empty($is_temp) ){
				$r = $db->exec("update bc_content set title='$file_id' where content_id='$content_id'");
			}
		}

		if( !empty($is_turn_update) ){
			self::updateCategoryforTURN($content_id, $is_turn_update);
		}

		return true;
	}

	static function  updateWorkMetaValues( $metaValues, $content_id, $meta_table_id )
	{
		global $db;

		$is_turn_update = false;

		$fieldKey = array();
		$fieldValue = array();
		//필드 목록 배열
		$metaFieldInfo = MetaDataClass::getMetaFieldInfo ('usr' , $meta_table_id );
		//필드의 id => name
		$fieldNameMap = MetaDataClass::getFieldIdtoNameMap('usr' , $meta_table_id );
		//테이블 명
		$tablename = MetaDataClass::getTableName('usr', $meta_table_id );
		//기본 데이터유형 변환
		$metaValues = MetaDataClass::getDefValueRender('usr' , $meta_table_id , $metaValues);

		foreach($fieldNameMap as $usr_meta_field_id => $name )
		{
			$value = $metaValues[$usr_meta_field_id];
			$value = $db->escape($value);
			if(!is_null($metaValues[$usr_meta_field_id])) {
				array_push($fieldKey, $name );
				array_push($fieldValue, $value );
			}
		}
			//업데이트
			$query = $db->UpdateQuery($tablename ,$fieldKey, $fieldValue, "usr_content_id='$content_id'" );

		InterfaceClass::_LogFile('','query',$query);
		$r = $db->exec($query);

		return true;
	}

	static function getMetaValues( $metadatas )
	{
		$metaValues = array();
		foreach($metadatas as $metadata)
		{
			foreach($metadata as $key => $value)
			{
				if( is_numeric($key) ){
					$metaValues[$key] = $value;
				}
			}
		}
		return $metaValues;
	}

	static function getMetaNameValues( $metadatas )
	{
		$metaValues = array();
		foreach($metadatas as $metadata)
		{
			if(!is_array($metadata)) continue;
			foreach($metadata as $key => $value)
			{
				if( strstr(strtolower($key) , 'usr_') ){
					$metaValues[strtoupper($key)] = $value;
				}
			}
		}
		return $metaValues;
	}

	static function findUsrMetaValue($metadatas, $usr_meta_field_id)
	{
		foreach($metadatas as $meta)
		{
			foreach($meta as $meta_field => $meta_value)
			{
				if($meta_field == $usr_meta_field_id)
				{
					return $meta_value;
				}
			}
		}
		return '';
	}

	static function insertContentCodeInfo($metaValues, $content_id,  $is_update = null )
	{
		global $db;
		$medcd = $metaValues[0]['k_medcd'];
		$brodymd = $metaValues[0]['k_brodymd'];
		$formbaseymd = $metaValues[0]['k_formbaseymd'];;
		$progcd = $metaValues[0]['k_progcd'];
		$subprogcd = $metaValues[0]['k_subprogcd'];

		$datagrade = $metavalues[0]['k_datagrade'];
		$storterm = $metavalues[0]['k_storterm'];

		if(!$is_update){		//신규일때

			//등록시 전송처 코드 입력 2013-02-15 이성용
			$register_type = 'E';//편집 전송 코드

			$r = $db->exec ("insert into CONTENT_CODE_INFO	(CONTENT_ID,MEDCD,PROGCD,SUBPROGCD,BRODYMD,FORMBASEYMD,DATAGRADE,STORTERM , REGISTER_TYPE ) values ('$content_id', '$medcd','$progcd','$subprogcd','$brodymd','$formbaseymd','$datagrade','$storterm' , '$register_type' )");
		}else{
			//업데이트
			$r = $db->exec ("update CONTENT_CODE_INFO set medcd='$medcd',brodymd='$brodymd' where content_id='$content_id' ");
		}

		return true;
	}

	static function checkXMLSyntax($receive_xml)
	{
		libxml_use_internal_errors(true);
		$rtn = simplexml_load_string($receive_xml);
		if (!$rtn) {
			foreach(libxml_get_errors() as $error)
			{
				$err_msg .= $error->message . "\n";
			}
			throw new Exception('xml 파싱 에러: '.$err_msg);
		}

		return $rtn;
	}

        static function insertCueSheet($cuesheet_id, $cuesheet_title, $broad_date, $cuesheet_type, $user_id, $subcontrol_room, $create_system, $prog_id)
	{
		global $db;

		$cur_time 			= date('YmdHis');

		if( empty($cuesheet_title) ) $cuesheet_title = 'Temp';
		$Temp_Title = $db->escape($cuesheet_title);
		$query = "insert into bc_cuesheet (CUESHEET_ID, CUESHEET_TITLE, BROAD_DATE, CREATED_DATE, USER_ID, TYPE, SUBCONTROL_ROOM, CREATE_SYSTEM, PROG_ID)
                            values('$cuesheet_id', '$Temp_Title', '$broad_date', '$cur_time', '$user_id', '$cuesheet_type', '$subcontrol_room', '$create_system', '$prog_id')";
		$r = $db->exec($query);

		$action = 'regist';
		$description = 'regist cuesheet';
		insertLog($action, $user_id, $cuesheet_id, $description);

		return $cuesheet_id;
	}

        static function editCueSheet($cuesheet_id, $cuesheet_title, $broad_date, $user_id, $subcontrol_room, $prog_id)
	{
		global $db;

                $_update = array();

                if(!empty($cuesheet_title)) {
                    array_push($_update , " cuesheet_title = '$cuesheet_title' ");
                }
                if(!empty($subcontrol_room)) {
                    array_push($_update , " subcontrol_room = '$subcontrol_room' ");
                }
                if(!empty($broad_date)) {
                    array_push($_update , " broad_date = '$broad_date' ");
                }
                if(!empty($prog_id)) {
                    array_push($_update , " prog_id = '$prog_id' ");
                }

		$query = "update bc_cuesheet set ".join(' , ', $_update)." where cuesheet_id = '$cuesheet_id'";
		$r = $db->exec($query);

		$action = 'update';
		$description = 'update cuesheet';
		insertLog($action, $user_id, $cuesheet_id, $description);

		return true;
	}

                static function deleteCueSheet($cuesheet_id, $user_id)
	{
		global $db;

                // 큐시트에 등록된 컨텐츠 아이템이 있을 경우 전체 삭제
                $hasItem = $db->queryOne("select count(*) from bc_cuesheet_content where cuesheet_id = '$cuesheet_id'");
                if($hasItem > 0) {
                    $del_query = "delete bc_cuesheet_content where cuesheet_id = '$cuesheet_id'";
                    $db->exec($del_query);
                }

                // 해당 큐시트 삭제
		$query = "delete bc_cuesheet where cuesheet_id = '$cuesheet_id'";
		$r = $db->exec($query);

		$action = 'delete';
		$description = 'delete cuesheet';
		insertLog($action, $user_id, $cuesheet_id, $description);

		return true;
	}

        static function insertCueSheetItems($cuesheet_items, $cuesheet_id, $user_id, $update = null)
	{
		global $db;

                $isVal = $db->queryOne("select count(*) from bc_cuesheet_content where cuesheet_id = '$cuesheet_id'");

                if($isVal > 0 ) {   // 업데이트

                } else {    // 신규등록
                    $show_order = 0;

                    foreach($cuesheet_items as $item) {
                        $cuesheet_content_id = getSequence('SEQ_BC_CUESHEET_CONTENT_ID');
                        $title = $db->escape($item['title']);
                        $content_id = $item['content_id'];

                        $query = "insert into bc_cuesheet_content (CUESHEET_ID, SHOW_ORDER, TITLE, CONTENT_ID, CUESHEET_CONTENT_ID, TASK_ID) values('$cuesheet_id', '$show_order', '$title', '$content_id', '$cuesheet_content_id', '')";
                        $r = $db->exec($query);

                        $action = 'regist';
                        $description = 'regist cuesheet_content';
                        insertLog($action, $user_id, $cuesheet_id, $description);

                        $show_order = $show_order + 1;
                    }
                }

		return true;
	}

        static function insertAudioCueSheetItems($cuesheet_items, $cuesheet_id, $user_id, $req_action)
	{
		global $db;

                if($req_action == 'add') { // 신규등록
					$index = 0;
                    foreach($cuesheet_items as $item) {
                        $cuesheet_content_id = getSequence('SEQ_BC_CUESHEET_CONTENT_ID');

                        $content_id = $item['content_id'];
                        $index = $index + 1;
						$control = $item['control'];

                        $content_info = $db->queryRow("select * from bc_content where content_id = '$content_id'");
                        $title = $content_info['title'];

                        $query = "insert into bc_cuesheet_content (CUESHEET_ID, SHOW_ORDER, TITLE, CONTENT_ID, CUESHEET_CONTENT_ID, CONTROL)
                                        values('$cuesheet_id', '$index', '$title', '$content_id', '$cuesheet_content_id', '$control')";
                        $r = $db->exec($query);

                    }

		    $action = 'regist';
		    $description = 'regist cuesheet_content';
		    insertLog($action, $user_id, $cuesheet_id, $description);

                } else {    // 기존 큐시트 수정 및 삭제
                    // 오디오는 전송기능이 없기때문에 기존 데이터를 모두 날리고 새로 입력
                    $query = "delete bc_cuesheet_content where cuesheet_id = '$cuesheet_id'";
                    $db->exec($query);
					$index = 0;
                    foreach($cuesheet_items as $item) {
                        $cuesheet_content_id = getSequence('SEQ_BC_CUESHEET_CONTENT_ID');

                        $content_id = $item['content_id'];
                        $index = $index + 1;
						$control = $item['control'];

                        $content_info = $db->queryRow("select * from bc_content where content_id = '$content_id'");
                        $title = $content_info['title'];

                        $query = "insert into bc_cuesheet_content (CUESHEET_ID, SHOW_ORDER, TITLE, CONTENT_ID, CUESHEET_CONTENT_ID, CONTROL)
                                        values('$cuesheet_id', '$index', '$title', '$content_id', '$cuesheet_content_id', '$control')";
                        $r = $db->exec($query);

                    }

		    $action = 'edit';
		    $description = 'edit cuesheet_content';
		    insertLog($action, $user_id, $cuesheet_id, $description);

                }

		return true;
	}

	// 인제스트 인터페이스
	static function insertIngestSchedule($schedule_id, $title, $ingest_system_ip, $channel, $schedule_type, $ingest_day, $ingest_date, $start_time, $duration, $is_use, $ud_content_id, $user_id, $prog_id, $router_no)
	{
		global $db, $logger;

		//$schedule_id =  getSequence('im_schedule_seq');
		$title = $db->escape($title);

		if(is_null($schedule_type))  throw new Exception('작업타입 오류');

		if($schedule_type == 2) //주간 일때
		{
			$date_time = $ingest_day;
		}
		else
		{
			$date_time = str_replace('-','',$ingest_date);
		}


		if( empty($start_time) )  throw new Exception('작업시작시간정보 오류');
		if( !strtotime($start_time) )  throw new Exception('작업시작시간정보 오류');

		$start_time =  Date( 'His', strtotime(trim($start_time)));

		if( empty($duration) )  throw new Exception('재생길이정보 오류');
		if( !strtotime($duration) )  throw new Exception('재생길이정보 오류');

		$duration = Date( 'His', strtotime(trim($duration)));

		$dh = substr($duration , 0, 2);
		$di = substr($duration , 2, 2);
		$ds = substr($duration , 4, 2);

		$duration = ( $dh * 3600 ) + ( $di * 60 ) + $ds;

		$create_time = date("YmdHis");
		$status = 0;
		$bs_content_id = 506;

		$category_id = $db->queryOne("select category_id from path_mapping where upper(path) = upper('$prog_id')");
		if(is_null($category_id ) || $category_id=='0')
		{
			$category_id = $db->queryOne("select category_id from BC_CATEGORY_MAPPING where ud_content_id='$ud_content_id'");
		}

		if(is_null($user_id) || $user_id=='temp')
		{
			 throw new Exception('유저정보가 없습니다. 로그인이 필요합니다.');
		}

		$schedule_list = $db->queryAll("select * from ingestmanager_schedule where INGEST_SYSTEM_IP='$ingest_system_ip' and CHANNEL='$channel' and is_use='1'");

		if( InterfaceClass::duplicateCheck($schedule_list, $schedule_type, $date_time, $start_time, $duration ) )
		{
			throw new Exception('시간정보가 중복되는 스케줄이 존재합니다.');
		}

		//$logger->info('$schedule_type : ' . $schedule_type);
		//$logger->info('$data', func_get_args());

		// $schedule_id, $title, $ingest_system_ip, $channel, $schedule_type, $ingest_day, $ingest_date, $start_time, $duration, $is_use, $ud_content_id, $user_id, $prog_id, $router_no

		$schedule = new Schedule();
		switch ($schedule_type) {

			// 지정일 한번
			case 0:
				$schedule->specifyDay($ingest_date, $start_time);
				break;

			// 매일 반복
			case 1:
				$schedule->daily($start_time);
				break;

			// 주 반복
			case 2:
				$schedule->weekly($ingest_day, $start_time);
				break;

			// 기간 지정
			case 3:
				$schedule->term($data['start_date'], $data['end_date'], $start_time);
				break;
		}

		$cronExpression = $schedule->getCronExpression();


		$r = $db->exec("insert into ingestmanager_schedule
					(SCHEDULE_ID,INGEST_SYSTEM_IP,CHANNEL,SCHEDULE_TYPE,DATE_TIME,START_TIME,DURATION,CREATE_TIME,STATUS,BS_CONTENT_ID,UD_CONTENT_ID,TITLE,USER_ID,IS_USE, CATEGORY_ID, ROUTER_NO, cron )
				values ('$schedule_id','$ingest_system_ip','$channel','$schedule_type','$date_time','$start_time','$duration','$create_time','$status','$bs_content_id','$ud_content_id','$title','$user_id', '$is_use', '$category_id', '$router_no', '$cronExpression')");


		return true;
	}

	static function editIngestSchedule($schedule_id, $title, $ingest_system_ip, $channel, $schedule_type, $ingest_day, $ingest_date, $start_time, $duration, $is_use, $ud_content_id, $prog_id, $router_no)
	{
		global $db;


			$title = $db->escape($title);

			if(is_null($schedule_type))  throw new Exception('작업타입 오류');

			if($schedule_type == 2) //주간 일때
			{
				$date_time = $ingest_day;
			}
			else
			{
				$date_time = str_replace('-','', $ingest_date);
			}


			if( empty($start_time) )  throw new Exception('작업시작시간정보 오류');
			if( !strtotime($start_time) )  throw new Exception('작업시작시간정보 오류');

			$start_time =  Date( 'His', strtotime(trim($start_time)));

			if( empty($duration) )  throw new Exception('재생길이정보 오류');
			if( !strtotime($duration) )  throw new Exception('재생길이정보 오류');

			$duration = Date( 'His', strtotime(trim($duration)));

			$dh = substr($duration , 0, 2);
			$di = substr($duration , 2, 2);
			$ds = substr($duration , 4, 2);

			$duration = ( $dh * 3600 ) + ( $di * 60 ) + $ds;

			$old_info = $db->queryRow("select * from ingestmanager_schedule where schedule_id='$schedule_id'");

			$schedule_list = $db->queryAll("select * from ingestmanager_schedule where INGEST_SYSTEM_IP='$ingest_system_ip' and CHANNEL='$channel' and is_use='1' and schedule_id!='$schedule_id'");

			if(InterfaceClass::duplicateCheck($schedule_list, $type, $date, $time, $duration))
			{
				throw new Exception('시간정보가 중복되는 스케줄이 존재합니다.');
			}

			$schedule = new Schedule();
			switch ($schedule_type) {

				// 지정일 한번
				case 0:
					$schedule->specifyDay($ingest_date, $start_time);
					break;

				// 매일 반복
				case 1:
					$schedule->daily($start_time);
					break;

				// 주 반복
				case 2:
					$schedule->weekly($ingest_day, $start_time);
					break;

				// 기간 지정
				case 3:
					$schedule->term($data['start_date'], $data['end_date'], $data['start_time']);
					break;
			}

			$cronExpression = $schedule->getCronExpression();

			$category_id = $db->queryOne("select category_id from path_mapping where upper(path) = upper('$prog_id')");
			if(!is_null($category_id))
			{
				$category_id = $category_id;
			}

			$r = $db->exec("update ingestmanager_schedule set title='$title', ingest_system_ip='$ingest_system_ip', channel='$channel', schedule_type='$schedule_type', date_time='$date_time',  start_time='$start_time', duration='$duration', is_use='$is_use', ud_content_id='$ud_content_id', category_id='$category_id', router_no='$router_no', cron='cronExpression' where schedule_id='$schedule_id'");

			$msg = '수정 작업 성공';
		return true;
	}

	static function delIngestSchedule($schedule_id)
	{
		global $db;

		$db->exec("delete from ingestmanager_schedule where schedule_id='$schedule_id'");

		$msg = '수정 작업 성공';

		return true;
	}

	static function insertIngestScheduleMeta($action, $schedule_id, $usr_program, $usr_prog_id, $usr_turn, $usr_turn_code, $usr_subprog, $usr_content, $usr_mednm, $usr_producer, $usr_grade, $usr_keyword)
	{
		global $db;
		$_values = array();
		if(!empty($usr_program)) {
		    array_push($_values, "usr_program = '".$usr_program."'" );
		}
		if(!empty($usr_prog_id)) {
		    array_push($_values, "usr_prog_id = '".$usr_prog_id."'" );
		}
		if(!empty($usr_turn)) {
		    array_push($_values, "usr_turn = '".$usr_turn."'" );
		}
		if(!empty($usr_turn_code)) {
		    array_push($_values, "usr_turn_code = '".$usr_turn_code."'" );
		}
		if(!empty($usr_subprog)) {
		    array_push($_values, "usr_subprog = '".$usr_subprog."'" );
		}
		if(!empty($usr_content)) {
		    array_push($_values, "usr_content = '".$usr_content."'" );
		}
		if(!empty($usr_mednm)) {
		    array_push($_values, "usr_mednm = '".$usr_mednm."'" );
		}
		if(!empty($usr_producer)) {
		    array_push($_values, "usr_producer = '".$usr_producer."'" );
		}
		if(!empty($usr_grade)) {
		    array_push($_values, "usr_grade = '".$usr_grade."'" );
		}
		if(!empty($usr_keyword)) {
		    array_push($_values, "usr_keyword = '".$usr_keyword."'" );
		}

		if($action == 'add') {
		    $query = "insert into ingestmanager_schedule_meta (schedule_id, usr_program, usr_prog_id, usr_turn, usr_turn_code, usr_subprog, usr_content, usr_mednm, usr_producer, usr_grade, usr_keyword)
				values ('$schedule_id', '$usr_program', '$usr_prog_id', '$usr_turn', '$usr_turn_code', '$usr_subprog', '$usr_content', '$usr_mednm', '$usr_producer', '$usr_grade', '$usr_keyword')" ;
		} else if($action == 'edit') {
		    $query = "update ingestmanager_schedule_meta set ".join(' , ', $_values)." where schedule_id = '$schedule_id'";
		} else if($action == 'del') {
		    $query = "delete ingestmanager_schedule_meta where schedule_id = '$schedule_id'";
		}

		$db->exec($query);

		return true;
	}

	// 스케쥴 중복 체크
	static function duplicateCheck($schedule_list, $type , $date , $time , $duration){

	    foreach($schedule_list as $schedule)
	    {
		    if($type == 0)//일회성 .. 시간만 체크 하면 됨
		    {
			    $targetWeek = date("W", strtotime( $date.$time ) );

			    if( $schedule['schedule_type'] == 2 ) //주간 반복 스케줄들
			    {
				    if( date("W") == $schedule['date_time'] )//같은 요일일떄
				    {
					    if( strtotime( $time ) == strtotime( $schedule['start_time'] ) )
					    {//시작시각이 같으면 안됨
						    return true;
					    }
					    else if( strtotime( $time ) <  strtotime( $schedule['start_time'] ) )
					    {
						    if( ( strtotime( $time ) +  $duration ) >=  strtotime( $schedule['start_time'] ) )
						    {
							    return true;
						    }
					    }
					    else if( strtotime( $time ) > strtotime( $schedule['start_time'] ) )
					    {
						    if( strtotime( $time ) <= ( strtotime( $schedule['start_time'] ) + $schedule['duration'] ) )
						    {
							    return true;
						    }
					    }
				    }
				    else if( ( date("W") == 0 || date("W") == 6 ) && ( $schedule['date_time'] == 8 )  ) //주말일때
				    {
					    if( strtotime( $time ) == strtotime( $schedule['start_time'] ) )
					    {//시작시각이 같으면 안됨
						    return true;
					    }
					    else if( strtotime( $time ) <  strtotime( $schedule['start_time'] ) )
					    {
						    if( ( strtotime( $time ) +  $duration ) >=  strtotime( $schedule['start_time'] ) )
						    {
							    return true;
						    }
					    }
					    else if( strtotime( $time ) > strtotime( $schedule['start_time'] ) )
					    {
						    if( strtotime( $time ) <= ( strtotime( $schedule['start_time'] ) + $schedule['duration'] ) )
						    {
							    return true;
						    }
					    }
				    }
				    else if( ( date("W") == 1 || date("W") == 2 || date("W") == 3 || date("W") == 4 || date("W") == 5 )  && ( $schedule['date_time'] == 7 ) ) //평일일때
				    {
					    if( strtotime( $time ) == strtotime( $schedule['start_time'] ) )
					    {//시작시각이 같으면 안됨
						    return true;
					    }
					    else if( strtotime( $time ) <  strtotime( $schedule['start_time'] ) )
					    {
						    if( ( strtotime( $time ) +  $duration ) >=  strtotime( $schedule['start_time'] ) )
						    {
							    return true;
						    }
					    }
					    else if( strtotime( $time ) > strtotime( $schedule['start_time'] ) )
					    {
						    if( strtotime( $time ) <= ( strtotime( $schedule['start_time'] ) + $schedule['duration'] ) )
						    {
							    return true;
						    }
					    }
				    }
			    }
			    else if( $schedule['schedule_type'] == 0 )
			    {
				    if( $schedule['date_time'] == $date ) //동일한 날짜
				    {
					    if( strtotime( $time ) == strtotime( $schedule['start_time'] ) )
					    {//시작시각이 같으면 안됨
						    return true;
					    }
					    else if( strtotime( $time ) <  strtotime( $schedule['start_time'] ) )
					    {
						    if( ( strtotime( $time ) +  $duration ) >=  strtotime( $schedule['start_time'] ) )
						    {
							    return true;
						    }
					    }
					    else if( strtotime( $time ) > strtotime( $schedule['start_time'] ) )
					    {
						    if( strtotime( $time ) <= ( strtotime( $schedule['start_time'] ) + $schedule['duration'] ) )
						    {
							    return true;
						    }
					    }
				    }
			    }
			    else if( $schedule['schedule_type'] == 1 )
			    {
				    if( strtotime( $time ) == strtotime( $schedule['start_time'] ) )
				    {//시작시각이 같으면 안됨
					    return true;
				    }
				    else if( strtotime( $time ) <  strtotime( $schedule['start_time'] ) )
				    {
					    if( ( strtotime( $time ) +  $duration ) >=  strtotime( $schedule['start_time'] ) )
					    {
						    return true;
					    }
				    }
				    else if( strtotime( $time ) > strtotime( $schedule['start_time'] ) )
				    {
					    if( strtotime( $time ) <= ( strtotime( $schedule['start_time'] ) + $schedule['duration'] ) )
					    {
						    return true;
					    }
				    }
			    }
		    }
		    else if($type == 1) //매일
		    {
			    if( strtotime( $time ) == strtotime( $schedule['start_time'] ) )
			    {//시작시각이 같으면 안됨
				    return true;
			    }
			    else if( strtotime( $time ) <  strtotime( $schedule['start_time'] ) )
			    {
				    if( ( strtotime( $time ) +  $duration ) >=  strtotime( $schedule['start_time'] ) )
				    {
					    return true;
				    }
			    }
			    else if( strtotime( $time ) > strtotime( $schedule['start_time'] ) )
			    {
				    if( strtotime( $time ) <= ( strtotime( $schedule['start_time'] ) + $schedule['duration'] ) )
				    {
					    return true;
				    }
			    }
		    }
		    else if($type == 2) //주간반복
		    {

			    if($schedule['schedule_type'] == 2) //주간 반복 스케줄들
			    {
				    if($date == $schedule['date_time'])//같은 요일일때
				    {
					    if( strtotime( $time ) == strtotime( $schedule['start_time'] ) )
					    {//시작시각이 같으면 안됨
						    return true;
					    }
					    else if( strtotime( $time ) <  strtotime( $schedule['start_time'] ) )
					    {
						    if( ( strtotime( $time ) +  $duration ) >=  strtotime( $schedule['start_time'] ) )
						    {
							    return true;
						    }
					    }
					    else if( strtotime( $time ) > strtotime( $schedule['start_time'] ) )
					    {
						    if( strtotime( $time ) <= ( strtotime( $schedule['start_time'] ) + $schedule['duration'] ) )
						    {
							    return true;
						    }
					    }
				    }
				    else if( ( $date == 0 || $date == 6 ) && ( $schedule['date_time'] == 8 ) )//주말일때
				    {
					    if( strtotime( $time ) == strtotime( $schedule['start_time'] ) )
					    {//시작시각이 같으면 안됨
						    return true;
					    }
					    else if( strtotime( $time ) <  strtotime( $schedule['start_time'] ) )
					    {
						    if( ( strtotime( $time ) +  $duration ) >=  strtotime( $schedule['start_time'] ) )
						    {
							    return true;
						    }
					    }
					    else if( strtotime( $time ) > strtotime( $schedule['start_time'] ) )
					    {
						    if( strtotime( $time ) <= ( strtotime( $schedule['start_time'] ) + $schedule['duration'] ) )
						    {
							    return true;
						    }
					    }
				    }
				    else if( ( $date == 1 || $date == 2 || $date == 3 || $date == 4 || $date == 5 )  && ( $schedule['date_time'] == 7 ) )//평일일때
				    {
					    if( strtotime( $time ) == strtotime( $schedule['start_time'] ) )
					    {//시작시각이 같으면 안됨
						    return true;
					    }
					    else if( strtotime( $time ) <  strtotime( $schedule['start_time'] ) )
					    {
						    if( ( strtotime( $time ) +  $duration ) >=  strtotime( $schedule['start_time'] ) )
						    {
							    return true;
						    }
					    }
					    else if( strtotime( $time ) > strtotime( $schedule['start_time'] ) )
					    {
						    if( strtotime( $time ) <= ( strtotime( $schedule['start_time'] ) + $schedule['duration'] ) )
						    {
							    return true;
						    }
					    }
				    }
				    else if( $date == 7 && ( $schedule['date_time'] == 1 || $schedule['date_time'] == 2  || $schedule['date_time'] == 3  || $schedule['date_time'] == 4  || $schedule['date_time'] == 5 ))
				    {
					    if( strtotime( $time ) == strtotime( $schedule['start_time'] ) )
					    {//시작시각이 같으면 안됨
						    return true;
					    }
					    else if( strtotime( $time ) <  strtotime( $schedule['start_time'] ) )
					    {
						    if( ( strtotime( $time ) +  $duration ) >=  strtotime( $schedule['start_time'] ) )
						    {
							    return true;
						    }
					    }
					    else if( strtotime( $time ) > strtotime( $schedule['start_time'] ) )
					    {
						    if( strtotime( $time ) <= ( strtotime( $schedule['start_time'] ) + $schedule['duration'] ) )
						    {
							    return true;
						    }
					    }
				    }
				    else if( $date == 8 && ( $schedule['date_time'] == 0 || $schedule['date_time'] == 6 ) )
				    {
					    if( strtotime( $time ) == strtotime( $schedule['start_time'] ) )
					    {//시작시각이 같으면 안됨
						    return true;
					    }
					    else if( strtotime( $time ) <  strtotime( $schedule['start_time'] ) )
					    {
						    if( ( strtotime( $time ) +  $duration ) >=  strtotime( $schedule['start_time'] ) )
						    {
							    return true;
						    }
					    }
					    else if( strtotime( $time ) > strtotime( $schedule['start_time'] ) )
					    {
						    if( strtotime( $time ) <= ( strtotime( $schedule['start_time'] ) + $schedule['duration'] ) )
						    {
							    return true;
						    }
					    }
				    }
			    }
			    else if( $schedule['schedule_type'] == 1 )
			    {
				    if( strtotime( $time ) == strtotime( $schedule['start_time'] ) )
				    {//시작시각이 같으면 안됨
					    return true;
				    }
				    else if( strtotime( $time ) <  strtotime( $schedule['start_time'] ) )
				    {
					    if( ( strtotime( $time ) +  $duration ) >=  strtotime( $schedule['start_time'] ) )
					    {
						    return true;
					    }
				    }
				    else if( strtotime( $time ) > strtotime( $schedule['start_time'] ) )
				    {
					    if( strtotime( $time ) <= ( strtotime( $schedule['start_time'] ) + $schedule['duration'] ) )
					    {
						    return true;
					    }
				    }
			    }
			    else if( $schedule['schedule_type'] == 0 )
			    {
				    if( strtotime( $time ) == strtotime( $schedule['start_time'] ) )
				    {//시작시각이 같으면 안됨
					    return true;
				    }
				    else if( strtotime( $time ) <  strtotime( $schedule['start_time'] ) )
				    {
					    if( ( strtotime( $time ) +  $duration ) >=  strtotime( $schedule['start_time'] ) )
					    {
						    return true;
					    }
				    }
				    else if( strtotime( $time ) > strtotime( $schedule['start_time'] ) )
				    {
					    if( strtotime( $time ) <= ( strtotime( $schedule['start_time'] ) + $schedule['duration'] ) )
					    {
						    return true;
					    }
				    }
			    }
		    }
	    }

	    return false;
    }

	function getIngestMetaFields($ud_content_id) {
		global $db;

		$ingest_container = $db->queryOne("select usr_meta_field_id from bc_usr_meta_field where usr_meta_field_title = '작업정보' and usr_meta_field_type = 'container' and ud_content_id = '$ud_content_id' order by show_order");
		$ingest_fields = $db->queryAll("select * from bc_usr_meta_field where ud_content_id = '$ud_content_id' and container_id = '$ingest_container' and usr_meta_field_type != 'textarea' ");

		return $ingest_fields;
	}

	static function getCategoryDirPath( $content , $category_id , $is_prog = false ) {
		global $db;
		$returnPath = '';

		$category = $db->queryRow("select * from bc_category where category_id='$category_id'");

		$is_AD_storage_group_path = false;

		//스토리지그룹이 AD일때 프로그램패스 / ingest / 부제 이런식 2013-02-05 이성용
		//define('UD_INGEST', 4000282 );//사용자 정의 - 인제스트
		//define('UD_DASDOWN', 4000284 );//사용자 정의 - DAS다운로드
		//define('UD_FINALEDIT', 4000345 );//사용자 정의 - 편집마스터
		//define('UD_FINALBROD', 4000346 );//사용자 정의 - 방송마스터

		$parent_id = $category['parent_id'];

		if( $parent_id == '0' ) //제작프로그램
		{
			$info =  $db->queryRow("select c.*, pm.path from path_mapping pm, bc_category c where pm.category_id=c.category_id and c.category_id='$category_id'");

			if( empty($info) ) return 'tmp';

			$returnPath = $info['path'];

		}
		else//부제일때?
		{
			$sub_path = $category['category_title'];//부제패스 명명

			$info =  $db->queryRow("select c.*, pm.path from path_mapping pm, bc_category c where pm.category_id=c.category_id and c.category_id='$parent_id'");

			$prog_path = $info['path'];

			//부제카테고리여도 프로그램패스를 얻어야할때
			if($is_prog){


				//프로그램패스
				$returnPath = $prog_path;

			}else{

				//$subinfo =  $this->db->queryRow("select c.*, sb.subprogcd, sb.progcd from subprog_mapping sb, bc_category c where pm.category_id=c.category_id and c.category_id='$category_id'");

				if( empty($info) ) return '';

				//프로그램패스
				$returnPath = $prog_path;

				if($is_AD_storage_group_path){
					$returnPath = $returnPath.'/'.$is_AD_storage_group_path.'/'.$sub_path;
				}else{
					$returnPath = $returnPath.'/'.$sub_path;
				}
			}
		}

		if(!empty($content['ud_content_code'])){
			$returnPath = $returnPath.'/'.$content['ud_content_code'];
		}

		return $returnPath;
	}

	static function check_media_expire_date($ud_content_id, $file_type, $created_datetime)
	{
		global $db;
		//ud_content_id 사용시
		$query = "select * from bc_ud_content_delete_info where ud_content_id = $ud_content_id and code_type = 'FLDLNM'";
		$data = $db->queryAll($query);

		foreach($data as $d => $da)
		{
			$type = $da[type_code];
			$code = $da[date_code];

			if(strstr($file_type,$type))
			{
				return check_limit_date($code,$created_datetime);
			}
		}

		return "99981231000000";
	}
}
?>