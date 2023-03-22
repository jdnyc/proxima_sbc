<?php

class MetaDataClass
{
	public $type;
	public $response;

	const SYS_CODE = 'sys';//self::SYS_CODE
	const SYS_VALUE = 'bc_sysmeta';//self::SYS_VALUE
	const SYS_FIELD_CODE = 'sys_meta_field_code';//self::SYS_FIELD_CODE

	const USR_CODE = 'usr';//self::USR_CODE
	const USR_VALUE = 'bc_usrmeta';//self::USR_VALUE
	const USR_FIELD_CODE = 'usr_meta_field_code';//self::USR_FIELD_CODE

	function __construct()
	{
	}

	static function getVar($type ,$type2){
		if($type == 'usr'){
			if($type2 == 'table'){
				return self::USR_VALUE;
			}else if($type2 == 'field'){
				return self::USR_CODE;
			}
		}else if($type == 'sys'){
			if($type2 == 'table'){
				return self::SYS_VALUE;
			}else if($type2 == 'field'){
				return self::SYS_CODE;
			}
		}
		else{
			return false;
		}
	}

	//시스템 메타데이터 정보 , 사용자 메타데이터 정보
	static function getMetaFieldInfo ($type , $id ){
		///동적 필드 정보 얻기
		global $db;
		global $sys_meta_field_info;
		global $usr_meta_field_info;

		if($type == self::SYS_CODE){
			if( is_null($sys_meta_field_info) ){

				$sys_field_info = $db->queryAll("SELECT * FROM bc_sys_meta_field ORDER BY bs_content_id, show_order");

				foreach($sys_field_info as $sysInfo ){
					if( !is_array($sys_meta_field_info[$sysInfo['bs_content_id']]) ){
						$sys_meta_field_info[$sysInfo['bs_content_id']] = array();
					}

					array_push($sys_meta_field_info[$sysInfo['bs_content_id']] , $sysInfo );
				}

				$GLOBALS['sys_meta_field_info'] = $sys_meta_field_info;
				return $GLOBALS['sys_meta_field_info'][$id];
			}else{
				return $sys_meta_field_info[$id];
			}
		}else if($type == self::USR_CODE){			
			if( is_null($usr_meta_field_info) ){
				$usr_field_info = $db->queryAll("SELECT * FROM bc_usr_meta_field ORDER BY ud_content_id, show_order");

				foreach($usr_field_info as $usrInfo ){
					if( !isset($usr_meta_field_info[$usrInfo['ud_content_id']]) ){
						$usr_meta_field_info[$usrInfo['ud_content_id']] = array();
					}

					array_push($usr_meta_field_info[$usrInfo['ud_content_id']] , $usrInfo );
				}

				$GLOBALS['usr_meta_field_info'] = $usr_meta_field_info;
				return $GLOBALS['usr_meta_field_info'][$id];
			}else{
				return $usr_meta_field_info[$id];
			}
		}

		return false;
	}

	//content_id 로 value 배열 얻기
	static function getValueInfo($type, $id , $content_id ){
		global $db;
		//$tableInfo =  getTableInfo($type, $id);
		$tableName = self::getTableName($type, $id);
		if(empty($tableName)){
			return false;
		}
		if( $type == self::USR_CODE ){
			$prefix = self::USR_CODE.'_content_id';
			return	$db->queryRow("SELECT * FROM $tableName WHERE $prefix=$content_id");
		}else if( $type == self::SYS_CODE ){
			$prefix = self::SYS_CODE.'_content_id';
			return	$db->queryRow("SELECT * FROM $tableName WHERE $prefix=$content_id");
		}else{
			return false;
		}

	}

	//content_id 로 id => value 배열 얻기
	static function getFieldValueInfo($type, $id, $content_id){
		global $db;
		$fieldInfo = self::getMetaFieldInfo($type, $id);
		$valueInfo = self::getValueInfo($type, $id, $content_id);

		if($type == self::USR_CODE){
			$code_field = self::USR_FIELD_CODE;
		}else if($type == self::SYS_CODE){
			$code_field = self::SYS_FIELD_CODE;
		}else{
			return false;
		}
		foreach($fieldInfo as $key => $field){
			$fieldInfo[$key]['value'] ='';
			if( $type == self::USR_CODE ){
				$fieldname = strtolower($field[$code_field]);
			}else if( $type == self::SYS_CODE ){
				$fieldname = strtolower(self::SYS_CODE.'_'.$field[$code_field]);
			}else{
				return false;
			}

			if(!empty($valueInfo[ $fieldname ])){
				$fieldInfo[$key]['value'] = $valueInfo[ $fieldname ];
			}
		}
		return $fieldInfo;
	}

	//컨테이너 별 value 얻기
	static function getFieldValueforContaierInfo($type , $id, $container_id, $content_id ){
		global $db;
		$newValueInfo = array();
		$fieldValueInfo = self::getFieldValueInfo($type , $id, $content_id);
		foreach($fieldValueInfo as $field)
		{
			if ( $field['container_id'] == $container_id && $field['depth'] == 1 ) {
				array_push($newValueInfo , $field);
			}
		}
		return $newValueInfo ;
	}

	//테이블 정보 얻기
	static function getTableInfo($type, $id){
		///동적 테이블 정보 얻기
		global $db;
		global $bs_content_info;
		global $ud_content_info;

		if($type == self::SYS_CODE){
			if( is_null($bs_content_info) ){

				$bs_info = $db->queryAll("select * from BC_BS_CONTENT order by bs_content_id, show_order");

				foreach($bs_info as $bsInfo ){
					if( !is_array($bs_content_info[$bsInfo['bs_content_id']]) ){
						$bs_content_info[$bsInfo['bs_content_id']] = array();
					}
					$bs_content_info[$bsInfo['bs_content_id']] = $bsInfo ;
				}

				$GLOBALS['bs_content_info'] = $bs_content_info;
				return $GLOBALS['bs_content_info'][$id];
			}else{
				return $bs_content_info[$id];
			}
		}else if($type == self::USR_CODE){
			if( is_null($ud_content_info) ){
				$ud_info = $db->queryAll("select * from BC_UD_CONTENT order by ud_content_id, show_order");

				foreach($ud_info as $usrInfo ){
					if( !is_array($ud_content_info[$usrInfo['ud_content_id']]) ){
						$ud_content_info[$usrInfo['ud_content_id']] = array();
					}

					$ud_content_info[$usrInfo['ud_content_id']]= $usrInfo ;
				}

				$GLOBALS['ud_content_info'] = $ud_content_info;
				return $GLOBALS['ud_content_info'][$id];
			}else{
				return $ud_content_info[$id];
			}
		}

		return false;

	}

	//필드명 얻기
	static function getFieldName($type, $name){
		if( $type == self::USR_CODE ){
            $prefix = self::USR_CODE;
            return $name;
		}else if( $type == self::SYS_CODE ){
			$prefix = self::SYS_CODE;
		}else{
			return false;
		}
		return $prefix.'_'.$name;
	}

	//테이블명 얻기
	static function getTableName($type, $id){
		///동적 테이블 명 얻기
		if($type == self::SYS_CODE){
			$default = self::SYS_VALUE;
			$tableInfo = self::getTableInfo($type, $id);
			if( !empty($tableInfo['bs_content_code']) ){
				return strtolower($default.'_'.$tableInfo['bs_content_code']);
			}
		}else if($type == self::USR_CODE){
			$default = self::USR_VALUE;

			$tableInfo = self::getTableInfo($type, $id);

			if( !empty($tableInfo['ud_content_code']) ){
				return strtolower($default.'_'.$tableInfo['ud_content_code']);
			}
		}

		return false;
	}

	//메타밸류 데이터로우가 있는지 여부
	static function isNewMeta($type, $id, $content_id){
		//콘텐츠에 기본 필드정보가 있는지 확인
		global $db;
		$table = self::getTableName($type, $id);
		if($type == self::USR_CODE){
			$isVal = $db->queryOne("SELECT count(*) FROM $table WHERE usr_content_id=$content_id");
		}else if($type == self::SYS_CODE){
			$isVal = $db->queryOne("SELECT count(*) from $table WHERE sys_content_id=$content_id");
		}else{
		}

		if($isVal > 0){
			//업데이트
			return false;
		}else{
			//추가
			return true;
		}
	}

	static function getFieldNametoIdMap($type , $ud_content_id)
	{
		$field_info = self::getMetaFieldInfo ($type , $ud_content_id );

		$id_to_name_map = array();

		foreach($field_info as $field){
			if($type == self::USR_CODE){
				if( $field['usr_meta_field_type'] == 'container' ) continue;
				$id_to_name_map[$field['usr_meta_field_code']] = $field['usr_meta_field_id'];
			}else if($type == self::SYS_CODE){
				if( $field['field_input_type'] == 'container' ) continue;
				$id_to_name_map[self::SYS_CODE.'_'.$field['sys_meta_field_code']] = $field['sys_meta_field_id'];
			}
		}

		//	$array = {
		//		1111 => fieldname1,
		//		2222 => fieldname2,
		//	};

		return $id_to_name_map;
	}

	static function getFieldNametoKorNameMap($type , $ud_content_id)
	{
		$field_info = self::getMetaFieldInfo ($type , $ud_content_id );

		$id_to_name_map = array();

		foreach($field_info as $field){
			if($type == self::USR_CODE){
				if( $field['usr_meta_field_type'] == 'container' ) continue;
				$id_to_name_map[$field['usr_meta_field_code']] = $field['usr_meta_field_title'];
			}else if($type == self::SYS_CODE){
				if( $field['field_input_type'] == 'container' ) continue;
				$id_to_name_map[self::SYS_CODE.'_'.$field['sys_meta_field_code']] = $field['sys_meta_field_title'];
			}
		}

		//	$array = {
		//		1111 => fieldname1,
		//		2222 => fieldname2,
		//	};

		return $id_to_name_map;
	}

	static function getFieldIdtoNameMap($type , $ud_content_id)
	{
		$field_info = self::getMetaFieldInfo ($type , $ud_content_id );

		$id_to_name_map = array();

		foreach($field_info as $field){
			if($type == self::USR_CODE){
				if( $field['usr_meta_field_type'] == 'container' ) continue;
				$id_to_name_map[$field['usr_meta_field_id']] = $field['usr_meta_field_code'];
			}else if($type == self::SYS_CODE){
				if( $field['field_input_type'] == 'container' ) continue;
				$id_to_name_map[$field['sys_meta_field_id']] = self::SYS_CODE.'_'.$field['sys_meta_field_code'];
			}
		}

		//	$array = {
		//		1111 => fieldname1,
		//		2222 => fieldname2,
		//	};

		return $id_to_name_map;
	}

	//기본값 렌더러 ex> datafield
	/**
	 * POST로 넘어온 데이터에서 실제 메타필드에 해당하는 값만 key/value 배열로 리턴한다.
	 *
	 * @param [type] $type
	 * @param [type] $ud_content_id
	 * @param [type] $values_array
	 * @return void
	 */
	static function getDefValueRender($type , $ud_content_id , $postData ){
		//$values_array id => val
		
		// POST로 넘어온 데이터에서 k_content_id 같은거 제외하고 실제 필드만 추출
		if($type == self::SYS_CODE) {
			$values_array = self::getMetaValues( $postData );
		} else {
			$values_array = self::getUserMetaValuesFromPost( $postData );
        }

        $field_info = self::getMetaFieldInfo ($type , $ud_content_id );

        if($type == self::USR_CODE){
			foreach($field_info as $field){
                if(isset($values_array[strtolower($field['usr_meta_field_code'])])){
                    $target_value = $values_array[strtolower($field['usr_meta_field_code'])];
                }
				if( !empty( $target_value ) ){
					if( $field['usr_meta_field_type'] == 'datefield' ){
                        //날짜입력일경우 변경
						if( strtotime($target_value) ){
                            if($field['data_length'] == 8 ){
                                $target_value = date("Ymd", strtotime($target_value));
                            }else{
                                $target_value = date("YmdHis", strtotime($target_value));
                            }
							$values_array[strtolower($field['usr_meta_field_code'])] = $target_value;
						}
					}
				} else {
                    if (isset($values_array[$field['usr_meta_field_code']])) {
                        $target_value = $values_array[$field['usr_meta_field_code']];
                    }                    
                    if( !empty( $target_value ) ){
                        if( $field['usr_meta_field_type'] == 'datefield'){
                            //날짜입력일경우 변경
                            if( strtotime($target_value) ){
                                if($field['data_length'] == 8 ){
                                    $target_value = date("Ymd", strtotime($target_value));
                                }else{
                                    $target_value = date("YmdHis", strtotime($target_value));
                                }
                                $values_array[$field['usr_meta_field_code']] = $target_value;
                            }
                        }
                    }
                }
			}
		}else if($type == self::SYS_CODE){
			foreach($field_info as $field){
			}
		}
		return $values_array;

	}

	//사용자 정의 유형 추가시 테이블 추가
	static function addTableQuery($type , $id , $fieldname ){
		global $db,$db_type;

		$fieldname = strtolower($fieldname);
		$default_field = 'CONTENT_ID';

		if( $type == self::USR_CODE ){
			$prefix = self::USR_VALUE;
			$default_field = $default_field;
		}else if( $type == self::SYS_CODE ){
			$prefix = self::SYS_VALUE;
			$default_field = self::SYS_CODE.'_'.$default_field;
		}else{
			return false;
		}

		$newTable = $prefix.'_'.$fieldname;
		//기본 메타테이블 생성
		if( $db_type == 'oracle' ){
			$query = "CREATE TABLE $newTable (	$default_field NUMBER NOT NULL ENABLE ,
			CONSTRAINT ".$newTable."_PK PRIMARY KEY   (    $default_field   )  ENABLE )";
		}else{
			$query = "CREATE TABLE $newTable (	$default_field NUMERIC NOT NULL,
			CONSTRAINT ".$newTable."_PK PRIMARY KEY   (    $default_field   ))";
		}
		return $query;
	}

	static function alterTableQuery($type , $id , $fieldname ){
		global $db;
		$fieldname = strtolower($fieldname);
		$default_field = 'CONTENT_ID';

		$beforename = self::getTableName($type, $id);

		if( $type == self::USR_CODE ){
			$prefix = self::USR_VALUE;
			$default_field = $default_field;
		}else if( $type == self::SYS_CODE ){
			$prefix = self::SYS_VALUE;
			$default_field = self::SYS_CODE.'_'.$default_field;
		}else{
			return false;
		}

		$newTable = $prefix.'_'.$fieldname;

		$query = "alter TABLE $beforename rename to $newTable";

		return $query;
	}

	static function dropTableQuery($type , $id ){
		global $db;

		$tablename = self::getTableName($type, $id);

		$query = " drop table $tablename ";

		return $query;

	}

	static function checkFieldQuery($type , $id , $fieldname ){
		global $db_type;
		$fieldname = self::getFieldName($type, strtoupper($fieldname) );

		$tablename = self::getTableName($type, $id);
		
		if( $db_type == 'oracle' ){
			$query = "	SELECT 	COUNT(*)
						FROM 	user_tab_cols
						WHERE 	table_name = '$tablename'
						AND 	column_name = '$fieldname'
						";
		}else{
			$fieldname = strtolower($fieldname);
			$tablename = strtolower($tablename);
			$query = "	SELECT 	COUNT(*)
						FROM 	information_schema.columns
						WHERE 	table_name = '$tablename'
						AND 	column_name = '$fieldname'
						";
		}
		return $query;
	}

	static function addFieldQuery($type , $id , $fieldname ){

		$charsize = 4000;

		$fieldname = self::getFieldName($type, strtolower($fieldname) );

		$tablename = self::getTableName($type, $id);

		//$query = " ALTER TABLE $tablename ADD ( $fieldname VARCHAR2( $charsize ) ) ";
		$query = " ALTER TABLE $tablename ADD $fieldname CHARACTER varying( $charsize ) ";

		return $query;
	}

	static function alterFieldQuery($type , $id , $beforename, $aftername ){

		$beforename = self::getFieldName($type, strtolower($beforename) );
		$aftername = self::getFieldName($type, strtolower($aftername) );

		$tablename = self::getTableName($type, $id);

		$query = "ALTER TABLE $tablename RENAME COLUMN $beforename TO $aftername ";

		return $query;

	}

	static function delFieldQuery($type , $id , $fieldename ){

		$fieldename = self::getFieldName($type, strtoupper($fieldename) );

		$tablename = self::getTableName($type, $id);

		$query = "ALTER TABLE $tablename DROP COLUMN $fieldename ";

		return $query;

	}

	static function createMetaQuery($type , $id , $queryArray ){

		$return = array();

		$tablename = self::getTableName($type, $id);

		$_select	= $queryArray['select'];
		$_from		= $queryArray['from'];
		$_where		= $queryArray['where'];
		$_order		= $queryArray['order'];

		if($type == 'usr' ){
			array_push($_select , " um.* ");
			array_push($_from , " $tablename um " );
			//array_push($_where , " c.content_id=um.usr_content_id(+) " );
			array_push($_where , " c.content_id=um.usr_content_id " );
		}else if( $type == 'sys' ){
			array_push($_select , " sys.* ");
			array_push($_from , " $tablename sys " );
			//array_push($_where , " c.content_id=sys.sys_content_id(+) " );
			array_push($_where , " c.content_id=sys.sys_content_id " );
		}else{
			return false;
		}


		//array_push($_order , );

		$return['select'] = $_select;
		$return['from'] = $_from;
		$return['where'] = $_where;
		$return['order'] = $_order;

		return $return;
	}

	/**
	 *  메타데이터 값 추출
	 *
	 * @param array $postData
	 * @return array meta_field_code와 값이 key/value형태로 리턴됨
	 */
	static function getUserMetaValuesFromPost( $postData )
	{		
        $metaValues = array();
        $exceptKeys = array(
            'ext-comp-',
            'k_content_id',
            'k_ud_content_id',
            'k_category_id',
            'k_title',
            'c_category_id'
        );
		foreach($postData as $metadata)
		{
			if(is_array($metadata)){

				foreach($metadata as $key => $value)
				{
                    $fieldPrefix = substr($key, 0, 4);
                    $isExist = false;
                    foreach($exceptKeys as $exceptKey){
                        if( strstr( $key, $exceptKey) ) {
                            $isExist = true;
                        }
                    }

                    if ($isExist) {
                        continue;
                    }
					
					//if( $fieldPrefix == self::USR_CODE . '_' ){
						$metaValues[$key] = $value;
					//}
				}

			}else{
				foreach($postData as $key => $value)
				{
					// echo 'key : '.$key."\n";
					$fieldPrefix = substr($key, 0, 4);										
																				
                    $isExist = false;
                    foreach($exceptKeys as $exceptKey){
                        if( strstr( $key, $exceptKey) ) {
                            $isExist = true;
                        }
                    }
                    if ($isExist) {
                        continue;
                    }
					//if( $fieldPrefix == self::USR_CODE . '_' ){
						$metaValues[$key] = trim($value);
					//}
				}
			}
		}
		return $metaValues;
	}

	/**
	 *  시스템 메타데이터 값 추출
	 *
	 * @param array $postData
	 * @return array meta_field_code와 값이 key/value형태로 리턴됨
	 */

	static function getMetaValues( $metadatas )
	{
		$metaValues = array();
		foreach($metadatas as $metadata)
		{
			if(is_array($metadata)){

				foreach($metadata as $key => $value)
				{
					if( is_numeric($key) ){
						$metaValues[$key] = $value;
					}
				}

			}else{
				foreach($metadatas as $key => $value)
				{
					if( is_numeric($key) ){
						$metaValues[$key] = $value;
					}
				}
			}
		}
		return $metaValues;
	}

	static function insertSysMeta($metaValues, $meta_table_id , $content_id ){
		global $db;
		$table_type = self::SYS_CODE;
		$fieldKey = array();
		$fieldValue = array();
		//필드 목록 배열
		$metaFieldInfo = self::getMetaFieldInfo ($table_type , $meta_table_id );
		//필드의 id => name
		$fieldNameMap = self::getFieldIdtoNameMap($table_type , $meta_table_id );
		//테이블 명
		$tablename = self::getTableName($table_type, $meta_table_id );
		//기본 데이터유형 변환
		$metaValues = self::getDefValueRender($table_type , $meta_table_id , $metaValues);

		foreach($fieldNameMap as $usr_meta_field_id => $name )
		{
			$value = $metaValues[$usr_meta_field_id];
			$value = $db->escape($value);
			if(strtolower($name) != 'sys_ori_filename') {
				array_push($fieldKey, $name );
				//array_push($fieldValue, "'".$value."'" );
				//2015-11-19 수정
				array_push($fieldValue, $value );
			}
		}

		if( self::isNewMeta($table_type, $meta_table_id , $content_id) ){
			//신규 등록
			array_push($fieldKey, 'sys_content_id' );
			array_push($fieldValue, $content_id );
			$query = $db->InsertQuery($tablename ,$fieldKey, $fieldValue);


		}else{
			//업데이트
			$query = $db->UpdateQuery($tablename ,$fieldKey, $fieldValue, "sys_content_id=$content_id" );
		}
		self::_LogFile('','insertSysMeta',$query);

		$r = $db->exec($query);
		return true;
	}

	static function formatBytes($b, $p=null) {
		$units = array("B","KB","MB","GB","TB","PB","EB","ZB","YB");
		$c=0;
		if(!$p && $p !== 0) {
			foreach($units as $k => $u) {
				if(($b / pow(1024,$k)) >= 1) {
					$r['bytes'] = $b / pow(1024,$k);
					$r['units'] = $u;
					$c++;
				}
			}
			return number_format($r['bytes'],2) . " " . $r['units'];
		} else {
			return number_format($b / pow(1024,$p)) . " " . $units[$p];
		}

	}

	static function _LogFile($filename,$name,$contents){
		$root = $_SERVER['DOCUMENT_ROOT'].'/log/';
		if(empty($filename)){
			$filename = 'MetaDataClass_'.date('Y-m-d').'.log';
		}
		@file_put_contents($root.$filename, "\n".$_SERVER['REMOTE_ADDR']."\t".date('Y-m-d H:i:s')."]\t".$name." : \n".$contents."\n", FILE_APPEND);
	}
	//뷰 쿼리 재생성
	// CREATE OR REPLACE VIEW VIEW1_TEST1 AS SELECT * FROM bc_usr_meta_value_test1 WITH READ ONLY;

	//##기본값 변경
	//ALTER TABLE BC_USR_META_VALUE_1234
	//MODIFY (F2 DEFAULT 2222 );

	//##필드 타입변경
	//ALTER TABLE BC_USR_META_VALUE_1234
	//MODIFY (F1111 NUMBER );

	static function getChildContents($content_id) {
      global $db, $db_type;

	  if( $db_type == 'oracle' ){
		$query = "
			 select * from (
                     select (select path from bc_media where content_id = ".$content_id." and media_type = 'original') original_file,
                            (select path from bc_media where content_id = ".$content_id." and media_type = 'raw') raw_file
                        from dual
                  union
                     select (select path from bc_media where content_id=a.content_id and media_type = 'original' and rownum=1) original_file,
                            (select path from bc_media where content_id=a.content_id and media_type = 'raw' and rownum=1) raw_file
                        from bc_content a
                        where a.parent_content_id = ".$content_id."
               ) order by raw_file
		";
	  }else{
		$query = "
			select * from (
                     select (select path from bc_media where content_id = ".$content_id." and media_type = 'original') original_file,
                            (select path from bc_media where content_id = ".$content_id." and media_type = 'raw') raw_file
                  union
                     select (select path from bc_media where content_id=a.content_id and media_type = 'original' offset 0 limit 1) original_file,
                            (select path from bc_media where content_id=a.content_id and media_type = 'raw' offset 0 limit 1) raw_file
                        from bc_content a
                        where a.parent_content_id = ".$content_id."
               ) b order by raw_file
		";
	  }

      return $db->queryAll($query);
   }

   static function makeContentInfoXML($content_id) {
		global $db;
		$xml = new SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\"?> \n<Geminisoft></Geminisoft>");

		$xml->addChild('Content');

		return $xml->asXML();
   }

   static function registerUserId($content_id){
	global $db, $db_type;
    if ($db_type == 'oracle') {
		$query = "select * from bc_content where content_id = ".$content_id;
		$registerUserId = $db->queryAll($query)[0]['reg_user_id'];	
    }
	return $registerUserId;
   }

   static function isAdminByUser($user){
	global $db, $db_type;
    if ($db_type == 'oracle') {
		
    }
   }
}
