<?php
//putenv("ORACLE_HOME=/usr/oracle/app/product/11.2.0/dbhome_1");
//putenv("LD_LIBRARY_PATH=/usr/oracle/app/product/11.2.0/dbhome_1/lib");
//putenv("TNS_ADMIN=/usr/oracle/app/product/11.2.0/dbhome_1/network/admin");
//putenv("ORACLE_SID=DB01");
//putenv("NLS_LANG=KOREAN_KOREA.AL32UTF8");

class Database
{
	public $transaction = false;

	public $cid = null; // connector id
	public $sid = null; // statement id
	public $limit = null;
	public $offset = null;
    public $last_query = "";

    public $clob = null;//CLOB 필드 로드 여부
    public $newclob = null;
    
	private $user= null;
	private $password= null;
	private $connection= null;
	private $charset= null;

	//table field info array
	public $table_field_type = array();


	function __construct($user, $password, $connection, $charset = 'AL32UTF8')
	{
//		$this->cid = oci_connect($user, $password, $connection);
        if( !function_exists('oci_pconnect') ) throw new Exception('not found oci_pconnect');

        $this->user = $user;
		$this->password = $password;
		$this->connection = $connection;
		$this->charset = $charset;

		$this->limit = null;
		$this->offset = null;
    }
    
    function __destruct()
    {
        $this->close();
    }

	function escape($str)
	{
		return str_replace("'", "''", $str);
	}

	function setTransaction($bool = false) {
		$this->transaction = $bool;
  }
  
  function setLoadNEWCLOB($bool = false)
	{
		//CLOB 필드 로드 여부
		$this->clob = $bool;
  }
  
  function db_connect(){
    if(!$this->cid)
    {
        $this->cid = @oci_pconnect($this->user, $this->password, $this->connection, $this->charset);
            
        if(!$this->cid)
        {
            $this->_logError('connect error');
            throw new Exception('db connect error');
        }
    }
    return $this->cid;
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
		if ($result === false && ($e = oci_error()) !== false)
		{
			throw new Exception($e['message']);
		}
		else if ($result === false)
		{
			return null;
		}

		return $result;
	}

	function queryOne($query)
	{
		$this->exec($query);
		oci_fetch($this->sid);
		$result = oci_result($this->sid, 1);

		return $this->checkError($result);
	}

	function queryRow($query)
	{
		$this->exec($query);

		$row = oci_fetch_array($this->sid, OCI_BOTH + OCI_RETURN_NULLS );
		if (empty($row)) {
			return array();
		}

		return $this->toLower($row);
	}

	function queryAll($query) {
		if (!empty($this->limit)) {
			$this->limit += $this->offset;
			$query = 'SELECT * FROM (SELECT a.*, ROWNUM mdb2rn FROM ('.$query.') a WHERE ROWNUM <= '.$this->limit.') WHERE mdb2rn > '.$this->offset;
		}
		$this->exec($query);

		$this->limit = null;
		$this->offset = null;

		$rows = array();
		while ($row = oci_fetch_array($this->sid, OCI_BOTH + OCI_RETURN_NULLS )) {
			array_push($rows, $this->toLower($row));
		}

		return $rows;
  }
  
  
	function TypeCheck($stmt)
	{
		$ncols = oci_num_fields($stmt);

		for ($i = 1; $i <= $ncols; $i++) {
			$field_type = strtolower(oci_field_type($stmt, $i));
			$field_name = strtolower(oci_field_name($stmt, $i));
			$cols[$field_type][] = $field_name;
		}
		return $cols;
	}

	function getColumnNames()
	{
		$ncols = oci_num_fields($this->sid);
		for ($i=1; $i<=$ncols; $i++)
		{
			$cols[strtolower(oci_field_name($this->sid, $i))] = $i;
		}

		return $cols;
	}

	function exec($query)
	{        
        $this->last_query = $query;
        
        if(empty($query)) return;
        //echo $query."\n";
        
        $this->_log($query);
        
        $this->db_connect();

		$this->sid = oci_parse($this->cid, $query);
		if (!$this->sid) {
            $err = oci_error($this->cid);
            $this->_logError($err['message'].'('.$err['sqltext'].')');
			throw new Exception('DB Error');
		}

		if ($this->transaction) {
			$result = @oci_execute($this->sid, OCI_NO_AUTO_COMMIT);
		}
		else {
			$result = @oci_execute($this->sid);
		}

		if (!$result) {
			$err = oci_error($this->sid);
			$this->_logError($err['message'].'('.$err['sqltext'].')');
			throw new Exception('DB result Error');
		}

		return true;
	}

	function affectedRows() {
		return oci_num_rows($this->sid);
	}

	//2012-01-27 추가 by허광회
	function clob_exec($query,$place_holder,$var,$length=null)
	{
		// 사용법
		// query 작성시  $place_holder에  :변수명  / $var에 실제들어갈 값(4000바이트?) /  $length 길이 -1은 최대값
		// ex> 쿼리 : insert into $table_name( 컬럼명 ) values ( .....  , :test , .....)
		//     $db->clob_exec($query,:test,&$_POST[test],4000)
		$this->last_query = $query;

		//echo $query."\n";
		$this->_log($query);

		$this->sid = oci_parse($this->cid, $query);
		if (!$this->sid) {
			$err = oci_error($this->cid);
			throw new Exception($err['message'].'('.$err['sqltext'].')');
		}
		//$this->descriptor = OCINewDescriptor($this->cid,OCI_D_LOB);
		oci_bind_by_name($this->sid,$place_holder,$var,$length);
		if ($this->transaction) {
			$result = @oci_execute($this->sid, OCI_NO_AUTO_COMMIT);
		}
		else {
			$result = @oci_execute($this->sid);
		}

		if (!$result) {
            $err = oci_error($this->sid);
            $this->_error_log($err['message'].'('.$err['sqltext'].')');
			throw new Exception('DB result Error');
		}

		//OCIFreeDescriptor($this->descriptor);

		return true;

  }
  
  
  /**
   * 프로시저 실행 function
   *
   * @param [type] $query "BEGIN SP_MODIFY_META( :P_PARAM , :P_RESULT ); END;"
   * @param [type] $params   $params = array(
   *   array( 
   *     'field_type'  => 'clob',
   *     'key'         => ':P_PARAM',
   *     'val'         => $request_json ,
   *     'param_type'  => '',
   *     'length' => -1
   *   ), 
   *   array( 
   *     'field_type'  => 'vchar2',
   *     'key'         => ':P_RESULT',
   *     'param_type'  => 'out',
   *     'length' => 4000
   *   )    
   * )
   * @return $params
   */
	function clob_param_exec($query,$params)
	{
		
    $this->last_query = $query;
 
    $this->_exec_log('query:'.$query);
    $this->_exec_log('params:'.print_r($params,true));

    $this->db_connect();
    
    $stmt = $this->parse($query);

    $clob_descriptor_array = array();

    if( !empty($params) ){
      foreach($params as $p_key => $param){
        if( $param['field_type'] == 'clob' ){
          $clob_descriptor_array[$p_key] = $this->new_descriptor(OCI_D_LOB); 
          $this->bind($stmt,$param['key'], $clob_descriptor_array[$p_key],$param['length'], SQLT_CLOB); 
          $clob_descriptor_array[$p_key]->WriteTemporary($param['val']);    
        }else{
          if( $param['param_type'] == 'out' ){
            $this->bind($stmt, $param['key'], $params[$p_key]['result'], $param['length']);
          }else{
            $this->bind($stmt, $param['key'], $param['val'], $param['length']);
          }
        }
      }
    }
    $this->execute($stmt);
 

    if( !empty($clob_descriptor_array) ){
      foreach($clob_descriptor_array as $clob_descriptor){
        $clob_descriptor->close();
        $clob_descriptor->free();
      }
    }   
    if($stmt){
      oci_free_statement($stmt);
    }
    
    $this->_exec_log('params:'.print_r($params,true));
		return $params;
	}

	function parse($stmt)
	{
		$result = oci_parse($this->cid, $stmt);
		return $this->checkError($result);
	}

	function execute($sid, $mode = OCI_COMMIT_ON_SUCCESS)
	{
		oci_execute($sid, $mode);
	}

	function commit()
	{
		oci_commit($this->cid);
	}

	function rollback()
	{
		oci_rollback($this->cid);
  }
  
  
	function bind($sid, $bind_name, & $var, $maxlen = -1, $type = SQLT_CHR)
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
        $return = [];
        $ncols = oci_num_fields($this->sid);    

		//if( !$this->clob ){

            for ($i = 1; $i <= $ncols; $i++) {
                $columnName  = oci_field_name($this->sid, $i);
                $columnType  = oci_field_type($this->sid, $i);
                $val = $row[$columnName];

                if ($columnType == 'CLOB') {
                    if( is_null($val) )
					{
						$return[strtolower($columnName)] = '';
					}
					else
					{
						$return[strtolower($columnName)] = $val->load();
					}
                }else{
                    $return [strtolower($columnName)] = trim($val);
                }
            }
		// }else{
		// 	$field_type = $this->TypeCheck($this->sid);

		// 	for ($i = 0; $i < $ncols; $i++) {
        //         $field_name = oci_field_name($this->sid, $i+1);              
		// 		$return[strtolower($field_name)] = $row[$i];
		// 	}

		// 	if(array_key_exists('clob',$field_type) ){
		// 		foreach($field_type['clob'] as $name )
		// 		{
		// 			foreach($return as $key => $val )
		// 			{
		// 				if($name == $key){
		// 					$return[$key] = $val->load();
		// 				}
		// 			}
		// 		}
		// 	}
		// }

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
	    //	file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/database/database_'.date('Ymd_H').'h.log', date('Y-m-d H:i:s')."[".$_SERVER['REMOTE_ADDR']."]\t".iconv('utf-8', 'euc-kr', $query)."\n\n", FILE_APPEND);
  } 
  function _exec_log($query)
  {
  //	file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/database/database_'.date('Ymd_H').'h.log', date('Y-m-d H:i:s')."[".$_SERVER['REMOTE_ADDR']."]\t".iconv('utf-8', 'euc-kr', $query)."\n\n", FILE_APPEND);
}
  
	function _logError($query)
	{
		@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/database_Error_'.date('Ymd').'.log', "\n".date('Y-m-d H:i:s').'- '.microtime(true)." [".$_SERVER['REMOTE_ADDR']."] ".$query."\r\n", FILE_APPEND);
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
						//$ka[$pre_field_name] = $ka[$pre_field_name].','.$s;
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
      SELECT 
      TABLE_NAME
      ,COLUMN_NAME
      ,DATA_TYPE
      ,DATA_LENGTH AS CHARACTER_MAXIMUM_LENGTH 
      FROM COLS 
      WHERE TABLE_NAME = '".strtoupper($table)."'
		");
		$table_columns = array();
		foreach($table_info as $ti) {
			$table_columns[strtolower($ti['column_name'])] = $ti['data_type'];
		}

        foreach ($data as $key => $val) {

        $key = strtolower($key);
        	////_debug("register_sequence","valuess  :: ".strtolower($table_columns[$key]));
        if(in_array(strtolower($table_columns[$key]), array('number','smallint','integer','bigint','decimal variable','numeric variable','real','double precision','serial','bigserial','numeric'))) {

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
      SELECT 
      TABLE_NAME
      ,COLUMN_NAME
      ,DATA_TYPE
      ,DATA_LENGTH AS CHARACTER_MAXIMUM_LENGTH 
      FROM COLS 
      WHERE TABLE_NAME = '".strtoupper($table)."'
		");
		$table_columns = array();
		foreach($table_info as $ti) {
			$table_columns[strtolower($ti['column_name'])] = $ti['data_type'];
		}

		foreach ($data as $key => $val) {
			$key = strtolower($key);
			if(in_array(strtolower($table_columns[$key]), array('number','smallint','integer','bigint','decimal variable','numeric variable','real','double precision','serial','bigserial','numeric'))) {

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
		} else if( !empty($fields) && !empty($num_fields) ){
			$query = "UPDATE " . $table . " SET " . join(', ', $fields).",".join(', ', $num_fields) . " WHERE " . $where;
        }else{
            $query = "UPDATE " . $table . " SET " . join(', ', $num_fields) . " WHERE " . $where;
        }
    
    if($exec == 'exec') {
			$this->exec($query);
		}

		return $query;
  }
  
	function close(){
		if($this->sid) oci_free_statement($this->sid);
		if($this->cid) oci_close($this->cid);
	}
}