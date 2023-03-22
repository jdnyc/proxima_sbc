<?php
/*
	DBPostgresql.class.php

	POSTGRESQL 위한 CLASS

*/

class Database
{
	public $transaction = false;

	public $cid = null; // connector id
	public $sid = null; // statement id
	public $limit = null;
	public $offset = null;
	public $last_query = "";

	var $clob = null;//CLOB 필드 로드 여부
	var $newclob = null;

	//table field info array
	public $table_field_type = array();

	function __construct($user, $password, $connection, $charset = 'AL32UTF8')
	{
		$this->cid = pg_connect($connection);
		if (!$this->cid)
		{
			$err_msg = "Connection fail!";
			throw new Exception($err_msg);
		}

		$this->limit = null;
		$this->offset = null;
	}

	function escape($str)
	{
		//return pg_escape_literal($str);
		return str_replace("'", "''", $str);
	}

	function setTransaction($bool = false) {
		$this->transaction = $bool;
		//odbc_autocommit($cid,$bool);
	}

	function setLoadNEWCLOB($bool = false)
	{
		//CLOB 필드 로드 여부
		$this->newclob = $bool;
	}

	function setLimit($limit, $offset = null)
	{
		$this->limit = $limit;
		$this->offset = $offset;

		if (!is_numeric($this->limit) || $this->limit < 0) {
			throw new Exception('limit 은 숫자이거나 0 보다 커야 여야만 합니다.');
		}
		if (!is_null($this->offset)) {
			if (!is_numeric($this->offset) || $this->limit < 0) {
				throw new Exception('offset 은 은 숫자이거나 0 보다 커야 여야만 합니다.');
			}
		}
	}

	function InsertQuery($table ,$insert_field_array, $insert_value_array) {
        foreach ($insert_field_array as $k => $v) {
            $insert_field_array[$k] = $this->escape(trim($v));
        }
		$query = "insert into $table (" . join(' , ', $insert_field_array) . ") values ('" . join("', '", $insert_value_array) . "')";

		return $query;
	}

//	function insert($table ,$data) {
//
//        foreach ($data as $key => $val) {
//			if( $val != '' ){
//				$fields[] = $key;
//				$values[] = $this->escape($val);
//			}
//        }
//		$query = "INSERT INTO $table (" . join(', ', $fields) . ") VALUES ('" . join("', '", $values) . "')";
//
//		$this->exec($query);
//	}

	function UpdateQuery($table, $insert_field_array, $insert_value_array, $where) {
		$updateq_array = array();        

        foreach ($insert_field_array as $key => $field) {
            $insert_value_array[$key] = $this->escape(trim($insert_value_array[$key]));
			array_push($updateq_array, $field . "='" . $insert_value_array[$key] . "'");
		}

        if ( ! empty($updateq_array)) {
            $query = "update $table set " . join(', ', $updateq_array) . " where " . $where;
        }

		return $query;
	}

	function checkError($result)
	{
		/*
		if ($result === false && ($e = odbc_error()) !== false)
		{
			$err_msg = odbc_errormsg($this->cid);
			//echo "odbc_error ??".print_r(odbc_error());
			throw new Exception($err_msg);
		}
		else if ($result === false)
		{
			//echo "null error";
			return null;
		}
*/
		if($result === false)
		{
			$result = pg_last_error($this->cid);
		}

		return $result;
	}

	function queryOne($query)
	{
		$result = $this->exec($query);

		if($result === false)
		{
			return $this->checkError($result);
		}

		//need @ for pg_fetch_row
		$fetch_result = @pg_fetch_row($result,0);
		//print_r($fetch_result);

	    if(!empty($fetch_result))
		{
			$result = pg_result($result, 0);
		}
		else
		{
			return null;
		}


		//echo $result;

		/*
		if(odbc_fetch_row($result,0) === true)
		{
			echo("asdfasdf");
			$result = odbc_result($result, 1);
		}
		else
		{
			$result = null;
		}

		echo $result;
		*/
		return $this->checkError($result);
	}

	function queryRow($query)
	{
		$result = $this->exec($query);
		if($result === false)
		{
			return $this->checkError($result);
		}

		$this->sid = $result;
		$fetch_result = @pg_fetch_array($result,0);




		if(empty($fetch_result))
		{
			return array();
		}
		$this->newclob = null;

		return $this->toLower($fetch_result);
	}

	function queryAll($query) {
		if (!empty($this->limit)) {
			$query = 'SELECT alias_sub_q.* FROM ('.$query.') alias_sub_q limit '.$this->limit.' offset '.$this->offset;
			//$this->limit += $this->offset;
			//$query = 'SELECT * FROM (SELECT a.*, ROWNUM mdb2rn FROM ('.$query.') a WHERE ROWNUM <= '.$this->limit.') WHERE mdb2rn > '.$this->offset;
		}

		$result = $this->exec($query);
		$this->sid = $result;

		$this->limit = null;
		$this->offset = null;

		$rows = array();
		if($result)
			{
				while ($row = @pg_fetch_array($this->sid)) {
					array_push($rows, $this->toLower($row));
				}
			}

		return $rows;
	}

	function getColumnNames()
	{
		$ncols = pg_num_fields($this->sid);
		for ($i=1; $i<=$ncols; $i++)
		{
			$cols[strtolower(pg_field_name($this->sid, $i))] = $i;
		}

		return $cols;
	}

	function exec($query)
	{
		$this->_log($query);
		//insert update convert query 2016-02-15
		//$query = $this->customQuery($query);

		$this->last_query = $query;



		//echo $query."\n";
		//$query = iconv("UTF-8","EUC-KR",$query);
		//echo $query."\n";
		//$this->_log($query);

		if ($this->transaction) {
			$result = @pg_query($this->cid,$query);
		}
		else {
			//pg_query($this->cid,"begin");
			$result = @pg_query($this->cid,$query);
			//odbc_commit($this->cid);
		}

		if (!$result) {

			$err = pg_last_error($this->cid);
			$this->_logError(print_r($err,true));
			throw new Exception($err);
			//throw new Exception($err['message'].'('.$err['sqltext'].')');
			return false;
		}
		$this->sid = $result;

		return $result;
	}

	function affectedRows() {
		$rtn = pg_affected_rows($this->sid);
		$this->_log('pg_affected_rows:'.$rtn);
		return $rtn;
	}

	//2012-01-27 추가 by허광회
	// 아직 ODBC 에 맞게 수정 하지 않음...

	function clob_exec($query,$place_holder,$var,$length=null)
	{

		$query = str_replace($place_holder, "'".$var."'", $query);




		$this->exec($query);


		return $query;

	}

	function parse($stmt)
	{
		$result = oci_parse($this->cid, $stmt);
		return $this->checkError($result);
	}

	function execute($sid, $mode = OCI_COMMIT_ON_SUCCESS)
	{
		odbc_execute($sid, $mode);
	}

	function commit()
	{
		odbc_commit($this->cid);
	}

	function rollback()
	{
		odbc_rollback($this->cid);
	}

	function bind($sid, $bind_name, $var, $maxlen = -1, $type = SQLT_CHR)
	{
		$result = oci_bind_by_name($sid, $bind_name, $var, $maxlen, $type);
		return $this->checkError($result);
	}

	function new_descriptor($type)
	{
		$result = oci_new_descriptor($this->cid, $type);
		return $this->checkError($result);
	}

	function toLower($row)
	{
		$ncols = pg_num_fields($this->sid);

		for ($i = 0; $i < $ncols; $i++)
		{
			//$return[strtolower(pg_field_name($this->sid, $i+1))] = iconv("EUC-KR","UTF-8",$row[pg_field_name($this->sid, $i+1)]);
			$return[strtolower(pg_field_name($this->sid, $i))] = $row[pg_field_name($this->sid, $i)];
			//$return[strtolower(odbc_field_name($this->sid, $i+1))] = $row[odbc_field_name($this->sid, $i+1)];
		}

		return $return;
	}

	function getTableFieldTypeList($table_name)
	{
		if( empty($this->table_field_type[strtolower($table_name)])){
			$check_field_query = $this->queryRow("select * from ".$table_name);
			$ncols = pg_num_fields($this->sid);
			for ($i = 0; $i < $ncols; $i++){
				$return[strtolower(pg_field_name($this->sid, $i))] =  pg_field_type($this->sid, $i) ;
			}
			$this->table_field_type[strtolower($table_name)] = $return;
			return $return;
		}else{
			return $this->table_field_type[strtolower($table_name)];
		}

		$ncols = pg_num_fields($this->sid);
		for ($i = 0; $i < $ncols; $i++)
		{
			$return[strtolower(pg_field_name($this->sid, $i))] =  pg_field_type($this->sid, $i) ;
		}
		return $return;
	}

	function _log($query)
	{
		//@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/database_'.date('Ymd_H').'h.log', "\n".date('Y-m-d H:i:s').'- '.microtime(true)." [".$_SERVER['REMOTE_ADDR']."] ".$query."\r\n", FILE_APPEND);
	}

	function _logError($query)
	{
		@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/database_Error_'.date('Ymd_H').'h.log', "\n".date('Y-m-d H:i:s').'- '.microtime(true)." [".$_SERVER['REMOTE_ADDR']."] ".$query."\r\n", FILE_APPEND);
	}

	function close(){
		if($this->sid) odbc_free_result($this->sid);
		if($thi->cid) odbc_close($this->cid);
	}

	function customQuery($query){
		global $db;

		$new_query = array();
		$type = strtoupper( substr(trim($query), 0, 6) );
		switch($type){
			case 'INSERT':

				$is_convert_null = true;
				list($insert_header, $values) = explode("values", $query, 2);
				list($insert_header_table, $field_names) = explode("(", $insert_header, 2);
				list($insert_header_prifix, $table_name) = explode("into", strtolower($insert_header_table), 2);

				if( !empty($insert_header_prifix) && !empty($table_name) && !empty($values) && !empty($field_names) ){
				}else{
					list($insert_header, $values) = explode("VALUES", $query, 2);
					list($insert_header_table, $field_names) = explode("(", $insert_header, 2);
					list($insert_header_prifix, $table_name) = explode("into", strtolower($insert_header_table), 2);
				}

				$field_names		= ltrim(trim($field_names) , "(");
				$field_names		= rtrim(trim($field_names) , ")");
				$field_names_array  = explode( ",", $field_names);

				$values				= ltrim( trim($values) , "(");
				$values				= rtrim( trim($values) , ")");
				$values_array		= explode( ",", $values);

				if(count($field_names_array) > 0 &&  count($values_array) > 0 && ( count($field_names_array) == count($values_array) ) ){
					$type_list = $this->getTableFieldTypeList($table_name);
					foreach($values_array as $key => $val)
					{
						$val = trim($val);
						$field_name = $field_names_array[$key];
						$field_type = $type_list[$field_name];
						if( $field_type == 'float8' ){
							//숫자 ''제거
							$val = trim($val , "'");
							if($is_convert_null){//공백처리여부
								if(is_null($val)){
									$val = 'null';
								}
							}
							$values_array[$key] = $val;
						}else{
							//문자 '' 없으면 추가
							if( substr($val, 0,1) != "'" ){
								$val = "'".$val."'";
							}
							if($is_convert_null){//공백처리여부
								if($val == "''"){
									$val = 'null';
								}
							}
							$values_array[$key] = $val;
						}
					}
					$remake_query = "INSERT INTO ".$table_name." (".join(",", $field_names_array).") "."VALUES"." (".join(",", $values_array).")";
					$this->_log('CUSTOM: '.$remake_query);
					return $remake_query;
				}else{
					return $query;
				}

			break;
			case 'UPDATE':
				list($prifix, $table_name, $field_where) = preg_split('/update|set/i', $query, 3);

				$is_convert_null = true;
				$field_where_array = preg_split("/[\\n|\\r|\\t| ]+WHERE+[\\n|\\r|\\t| ]/i", $field_where, -1); //explode("where", $field_where, 2);

				if(count($field_where_array) == 2){
					$type_list = $this->getTableFieldTypeList($table_name);

					$field_value = $field_where_array[0];
					$where_value = $field_where_array[1];
					$field_value_array = $this->string2KeyedArray($field_value,",","=");
					//print_r($field_value_array);
					$where_value_array = preg_split("/[\\n|\\r|\\t| ]+[AND]+[\\n|\\r|\\t| ]/i", $where_value, -1); //explode("where", $field_where, 2);

					$newWhereValueArray = array();
					foreach($where_value_array as $key => $where_value)
					{
						list($field_name,$where_val) = explode("=", $where_value );

						$field_type = $type_list[trim($field_name)];
						if($field_type == 'float8'){//숫자
							$where_val = trim(trim($where_val),"'");
							if($is_convert_null){//공백처리여부
								if(is_null($where_val)){
									$where_val = 'null';
								}
							}
							array_push($newWhereValueArray, $field_name."=".$where_val);
						}else{//문자
							if($is_convert_null){//공백처리여부
								if($where_value == "''"){
									$where_value = 'null';
								}
							}
							array_push($newWhereValueArray, $where_value);
						}
					}
					$where_part = join(" AND ",$newWhereValueArray);

					$newUpdateFieldArray = array();
					foreach($field_value_array as $field_name => $field_val)
					{
						$field_type = $type_list[trim($field_name)];
						if($field_type == 'float8'){//숫자
							$field_val = trim(trim($field_val),"'");
							if($is_convert_null){//공백처리여부
								if(is_null($field_val)){
									$field_val = 'null';
								}
							}
							array_push($newUpdateFieldArray, $field_name."=".$field_val);
						}else{//문자
							if($is_convert_null){//공백처리여부
								if($field_val == "''"){
									$field_val = 'null';
								}
							}
							array_push($newUpdateFieldArray, $field_name."=".$field_val);
						}
					}
					$field_part = join(" , ",$newUpdateFieldArray);

					$remake_query = "UPDATE ".$table_name." SET ".$field_part." WHERE ".$where_part;
					$this->_log('CUSTOM: '.$remake_query);
					return $remake_query;
				}else{
					//예외처리
				}

			break;

			case 'SELECT':
			break;
			case 'DELETE':
			break;
		}

		return $query;
	}

	function string2KeyedArray($string, $delimiter = ',', $kv = '=') {
		if ($a = explode($delimiter, $string)) {
			foreach ($a as $s) {
				if ($s) {
					if ($pos = strpos($s, $kv)) {
						$ka[trim(substr($s, 0, $pos))] = trim(substr($s, $pos + strlen($kv)));
						$pre_field_name = trim(substr($s, 0, $pos));
					} else {
						//if text have comma, attach here.
						$ka[$pre_field_name] = $ka[$pre_field_name].','.$s;
					}
				}
			}
			return $ka;
		}
	}

	function insert($table ,$data, $exec='exec') {
		//2016-02-26 형변환에 맞춤.

		//require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
		//_debug("register_sequence","=============  : ISNERTT : ");
		$table_info = $this->queryAll("
			SELECT	TABLE_NAME
				   ,COLUMN_NAME
				   ,DATA_TYPE
				   ,CHARACTER_MAXIMUM_LENGTH
			FROM	INFORMATION_SCHEMA.COLUMNS
			WHERE	TABLE_NAME = '".strtolower($table)."'
		");
		$table_columns = array();
		foreach($table_info as $ti) {
			$table_columns[strtolower($ti['column_name'])] = $ti['data_type'];
		}
		//_debug("register_sequence","DDDDDDDDDDDDDDDDDDDDDDDDDDDDATATA");
		//_debug("register_sequence",print_r($data,true));
		////_debug("register_sequence",print_r($table_info,true));
		//_debug("register_sequence",print_r($table_columns,true));

        foreach ($data as $key => $val) {

        	$key = strtolower($key);
        	////_debug("register_sequence","valuess  :: ".strtolower($table_columns[$key]));
			if(in_array(strtolower($table_columns[$key]), array('smallint','integer','bigint','decimal variable','numeric variable','real','double precision','serial','bigserial','numeric'))) {

				if(is_numeric($val) || $val === 0){
					$val = $val;
				}else if(empty($val)){
					$val = 'null';
					//_debug("register_sequence"," value is null !!! :".$key);
				}

				
				//_debug("register_sequence","key  :: ".$key." value ::".$value);
				$num_fields[] = $key;
	            $num_values[] = $val;
			} else {
				if($val !== null) {
					$fields[] = $key;				
					$values[] = $this->escape($val);					
				}
			}
        }
		if(empty($num_fields)) {
			$query = "INSERT INTO $table (" . join(', ', $fields) . ") VALUES ('" . join("', '", $values) . "')";
		} else {
			if( $fields && count($fields) > 0 ){
				$query_fields_fields = join(', ', $fields).",";
				$query_values_fields = "'" . join("', '", $values) ."',";
			}else{
				$query_values_fields = "";
			}

			//_debug("register_sequence","NUMBER FIELD ==============================================");
			//_debug("register_sequence",print_r($num_fields,true));
			$query = "INSERT INTO $table (".$query_fields_fields.join(', ', $num_fields) . ") VALUES (".$query_values_fields.join(", ", $num_values).")";
		}


		//_debug("register_sequence",$query);

		//{
			//$query = "INSERT INTO $table (" . join(', ', $fields).",".join(', ', $num_fields) . ") VALUES ('" . join("', '", $values) ."',".join(", ", $num_values).")";
		//}
		

		if($exec == 'exec') {
			$this->exec($query);
		}

		return $query;
	}

	function update($table, $data, $where, $exec='exec') {
		//2016-02-26 형변환에 맞춤.
		$table_info = $this->queryAll("
			SELECT	TABLE_NAME
				   ,COLUMN_NAME
				   ,DATA_TYPE
				   ,CHARACTER_MAXIMUM_LENGTH
			FROM	INFORMATION_SCHEMA.COLUMNS
			WHERE	TABLE_NAME = '".strtolower($table)."'
		");
		$table_columns = array();
		foreach($table_info as $ti) {
			$table_columns[strtolower($ti['column_name'])] = $ti['data_type'];
		}

		foreach ($data as $key => $val) {
			$key = strtolower($key);
			if(in_array(strtolower($table_columns[$key]), array('smallint','integer','bigint','decimal variable','numeric variable','real','double precision','serial','bigserial','numeric'))) {

				if(is_numeric($val) || $val === 0){
					$val = $val;
				}else if(empty($val)){
					$val = 'null';
					//_debug("register_sequence"," value is null !!! :".$key);
				}

				//if(empty($val) && (int)$val !== 0) {
				//	$val = 'null';
				//}
				
				$num_fields[] = $key . " = " . $this->escape($val);
			} else {
				if($val === null) {
					$fields[] = $key . " = null";
				} else {
					$fields[] = $key . " = '" . $this->escape($val) ."'";					
				}
			}
		}

		if(empty($num_fields)) {
			$query = "UPDATE " . $table . " SET " . join(', ', $fields) . " WHERE " . $where;
		} else {
			$query = "UPDATE " . $table . " SET " . join(', ', $fields).",".join(', ', $num_fields) . " WHERE " . $where;
		}
@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/11testdatabase_'.date('Ymd_H').'h.log', date('Y-m-d H:i:s')."[".$_SERVER['REMOTE_ADDR']."]\t".$query."\n\n", FILE_APPEND);
		if($exec == 'exec') {
			$this->exec($query);
		}

		return $query;
	}

}