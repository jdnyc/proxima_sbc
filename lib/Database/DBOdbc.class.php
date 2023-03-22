<?php
/*
	DBOdbc.class.php

*/

class Database
{
	var $transaction = false;

	var $cid = null; // connector id
	var $sid = null; // statement id
	var $limit = null;
	var $offset = null;
	var $last_query = "";

	function __construct($user, $password, $connection, $charset = 'AL32UTF8')
	{

		$this->cid = odbc_connect($connection ,$user, $password);
		if (!$this->cid)
		{
			$e = odbc_error();
		}
		else
		{
			//odbc_exec($this->cid, "SET NAMES 'utf-8'");
            //odbc_exec($this->cid, "SET client_encoding='utf-8'");
		}
	}

	function escape($str)
	{
		return str_replace("'", "''", $str);
	}

	function setTransaction($bool = false) {
		$this->transaction = $bool;
		odbc_autocommit($cid,$bool);
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
        $query = "select NOW() from BC_MEMBER WHERE USER_ID = 'admin'";
        
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

		return $result;
	}

	function queryOne($query)
	{
		$result = $this->exec($query);
		if(!$result)
		{
			//echo $query;exit;
		}
		$fetch_result = odbc_fetch_row($result,1);

	    if($fetch_result === true)
		{
			$result = odbc_result($result, 1);
			$result = iconv("EUC-KR","UTF-8",$result);
		}
		else
		{
			return  null;
		}

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
		$this->sid = $result;

		$row = odbc_fetch_array($result);
		//$row = iconv("EUC-KR","UTF-8",$row);

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

		$result = $this->exec($query);
		$this->sid = $result;

		$this->limit = null;
		$this->offset = null;

		$rows = array();
		if($result)
		{
			while ($row = odbc_fetch_array($this->sid)) {
				array_push($rows, $this->toLower($row));
			}
		}
		return $rows;
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

		//echo $query."\n";
		$query = iconv("UTF-8","EUC-KR",$query);
		//echo $query."\n";
		$this->_log($query);

		if ($this->transaction) {

			$result = @odbc_exec($this->cid,$query);
		}
		else {
			$result = @odbc_exec($this->cid,$query);
			odbc_commit($this->cid);
		}

		if (!$result) {
			$err = odbc_error($this->cid);
			//return throw new Exception($err['message'].'('.$err['sqltext'].')');
		}

		return $result;
	}

	function affectedRows() {
		return odbc_num_rows($this->sid);
	}

	//2012-01-27 추가 by허광회
	// 아직 ODBC 에 맞게 수정 하지 않음...

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
			return $this->checkError($result);
		}

		//OCIFreeDescriptor($this->descriptor);

		return true;

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
		$ncols = odbc_num_fields($this->sid);
		for ($i = 0; $i < $ncols; $i++)
		{
			$return[strtolower(odbc_field_name($this->sid, $i+1))] = iconv("EUC-KR","UTF-8",$row[odbc_field_name($this->sid, $i+1)]);
			//$return[strtolower(odbc_field_name($this->sid, $i+1))] = $row[odbc_field_name($this->sid, $i+1)];
		}

		return $return;
	}

	function _log($query)
	{
	//	file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/database/database_'.date('Ymd_H').'h.log', date('Y-m-d H:i:s')."[".$_SERVER['REMOTE_ADDR']."]\t".iconv('utf-8', 'euc-kr', $query)."\n\n", FILE_APPEND);
	}

	function close(){
		if($this->sid) odbc_free_result($this->sid);
		if($thi->cid) odbc_close($this->cid);
	}

}