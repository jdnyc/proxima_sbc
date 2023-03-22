<?php
//putenv("ORACLE_HOME=/usr/oracle/app/product/11.2.0/dbhome_1");
//putenv("LD_LIBRARY_PATH=/usr/oracle/app/product/11.2.0/dbhome_1/lib");
//putenv("TNS_ADMIN=/usr/oracle/app/product/11.2.0/dbhome_1/network/admin");
//putenv("ORACLE_SID=DB01");
//putenv("NLS_LANG=KOREAN_KOREA.AL32UTF8");

class Database
{
	var $transaction = false;

	var $cid = null; // connector id
	var $sid = null; // statement id
	var $limit = null;
	var $offset = null;
	var $last_query;

	var $user= null;
	var $password= null;
	var $connection= null;
	var $charset= null;

	function __construct($user, $password, $connection, $charset = 'AL32UTF8')
	{
		$this->user = $user;
		$this->password = $password;
		$this->connection = $connection;
		$this->charset = $charset;

	//		$this->cid = oci_connect($user, $password, $connection);
	//		$this->cid = oci_connect($user, $password, $connection, $charset);
	//		if (!$this->cid)
	//		{
	//			$e = oci_error();
	//		}
	}

	function escape($str)
	{
		return str_replace("'", "''", $str);
	}

	function setTransaction($bool = false) {
		$this->transaction = $bool;
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

	function checkError($result)
	{
		if ($result === false && ($e = oci_error()) !== false)
		{
			$this->_error_log($e['message'].'('.$e['sqltext'].')');
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

		$row = oci_fetch_array($this->sid, OCI_BOTH);
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
		while ($row = oci_fetch_array($this->sid, OCI_BOTH)) {
			array_push($rows, $this->toLower($row));
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

		if(!$this->cid)
		{
			$this->cid = oci_connect($this->user, $this->password, $this->connection, $this->charset);
		}

		//echo $query."\n";
		$this->_log($query);

		$this->sid = oci_parse($this->cid, $query);
		if (!$this->sid) {
			$err = oci_error($this->cid);
			$this->_error_log($err['message'].'('.$err['sqltext'].')');
			throw new Exception($err['message'].'('.$err['sqltext'].')');
		}

		if ($this->transaction) {
			$result = @oci_execute($this->sid, OCI_NO_AUTO_COMMIT);
		}
		else {
			$result = @oci_execute($this->sid);
		}

		if (!$result) {
			$err = oci_error($this->sid);
			$this->_error_log($err['message'].'('.$err['sqltext'].')');
			throw new Exception($err['message'].'('.$err['sqltext'].')');
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

		if(!$this->cid)
		{
			$this->cid = oci_connect($this->user, $this->password, $this->connection, $this->charset);
		}

		//echo $query."\n";
		$this->_log($query);

		$this->sid = oci_parse($this->cid, $query);
		if (!$this->sid) {
			$err = oci_error($this->cid);
			throw new Exception($err['message'].'('.$err['sqltext'].')');
		}
		//$this->descriptor = $this->new_descriptor($this->cid, OCI_D_CLOB);
		oci_bind_by_name($this->sid,$place_holder,$var,$length  );
		if ($this->transaction) {
			$result = @oci_execute($this->sid, OCI_NO_AUTO_COMMIT);
		}
		else {
			$result = @oci_execute($this->sid);
		}

		if (!$result) {
			$err = oci_error($this->sid);
			throw new Exception($err['message'].'('.$err['sqltext'].')');
		}

		//OCIFreeDescriptor($this->descriptor);

		return true;

	}

	function xml_exec($query,$place_holder,$var,$length=null)
	{		
		$this->last_query = $query;
		if(!$this->cid)
		{
			$this->cid = oci_connect($this->user, $this->password, $this->connection, $this->charset);
		}

		//echo $query."\n";
		$this->_log($query);

		$this->sid = oci_parse($this->cid, $query);
		if (!$this->sid) {
			$err = oci_error($this->cid);
			throw new Exception($err['message'].'('.$err['sqltext'].')');
		}

		$lob = oci_new_descriptor($this->cid, OCI_D_LOB);
		oci_bind_by_name($this->sid, $place_holder, $lob, $length, OCI_B_CLOB);
		$lob->writeTemporary($var);

		if ($this->transaction) {
			$result = @oci_execute($this->sid, OCI_NO_AUTO_COMMIT);
		}
		else {
			$result = @oci_execute($this->sid);
		}

		if (!$result) {
			$err = oci_error($this->sid);
			throw new Exception($err['message'].'('.$err['sqltext'].')');
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
		$ncols = oci_num_fields($this->sid);
		for ($i = 0; $i < $ncols; $i++)
		{
			$field_name = oci_field_name($this->sid, $i+1);

			//$return[strtolower(oci_field_name($this->sid, $i+1))] = $row[$i];
			if (oci_field_type($this->sid, $field_name) == 'CLOB') {
				if( is_null($row[$i]) )
				{
					$return[strtolower($field_name)] = '';
				}
				else
				{
					$return[strtolower($field_name)] = $row[$i]->load();
				}
			}
			else {
				$return[strtolower($field_name)] = trim($row[$i]);
			}
		}

		return $return;
	}

	function _log($query)
	{
		//file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/database/database_'.date('Ymd_H').'h.log', date('Y-m-d H:i:s')."[".$_SERVER['REMOTE_ADDR']."]\t".iconv('utf-8', 'euc-kr', $query)."\n\n", FILE_APPEND);
	}

	function _error_log($text)
	{
		@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/database/database_'.date('Ymd').'.log', date('Y-m-d H:i:s')."[".$_SERVER['REMOTE_ADDR']."]\t".$text."\n", FILE_APPEND);
	}

	function close(){
		if($this->sid) oci_free_statement($this->sid);
		if($thi->cid) oci_close($this->cid);
	}

}
?>