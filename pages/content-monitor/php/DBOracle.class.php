<?php
class Database
{
	var $transaction = false;

	var $cid = null; // connector id
	var $sid = null; // statement id
	var $limit = null;
	var $offset = null;
	var $last_query;

	function __construct($user, $password, $connection, $charset = 'AL32UTF8') {
//		$this->cid = oci_connect($user, $password, $connection);
		$this->cid = oci_connect($user, $password, $connection, $charset);
		if (!$this->cid) {
			$e = oci_error();

			throw new Exception($e['message']);
		}
	}

	function escape($str) {
		return str_replace("'", "''", $str);
	}

	function setTransaction($bool = false) {
		$this->transaction = $bool;
	}

	function setLimit($limit, $offset = null) {
		if (!is_numeric($limit) || $limit < 0) {

			throw new Exception('limit 은 숫자이거나 0 보다 커야 여야만 합니다.');
		}

		if (!is_null($offset)) {
			if (!is_numeric($offset) || $limit < 0) {

				throw new Exception('offset 은 은 숫자이거나 0 보다 커야 여야만 합니다.');
			}
		}

		$this->limit = $limit;
		$this->offset = $offset+1;
	}

	function checkError($result) {
		if ($result === false && ($e = oci_error()) !== false) {

			throw new Exception($e['message']);
		}
		else if ($result === false) {

			return null;
		}

		return $result;
	}

	function queryOne($query) {
		$this->exec($query);
		oci_fetch($this->sid);
		$result = oci_result($this->sid, 1);

		return $this->checkError($result);
	}

	function queryRow($query) {
		$this->exec($query);

		$row = oci_fetch_array($this->sid, OCI_BOTH);
		if (empty($row)) {

			return array();
		}

		return $this->toLower($row);
	}

	function queryAll($query) {
		if (!empty($this->limit)) {
			$this->limit += $this->offset-1;
			$query = 'SELECT * FROM (SELECT a.*, ROWNUM mdb2rn FROM ('.$query.') a WHERE ROWNUM <= '.$this->limit.') WHERE mdb2rn >= '.$this->offset;
		}
		$this->limit = null;
		$this->offset = null;

		$this->exec($query);

		$rows = array();
		while ($row = oci_fetch_array($this->sid, OCI_BOTH)) {
			array_push($rows, $this->toLower($row));
		}

		return $rows;
	}

	function getColumnNames() {
		$ncols = oci_num_fields($this->sid);
		for ($i=1; $i<=$ncols; $i++) {
			$cols[strtolower(oci_field_name($this->sid, $i))] = $i;
		}

		return $cols;
	}

	function exec($query) {
		$this->last_query = $query;

		$this->_log($query);

		$this->sid = oci_parse($this->cid, $query);
		if (!$this->sid) {
			$err = oci_error($this->cid);

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

			throw new Exception($err['message'].'('.$err['sqltext'].')');
		}

		return true;
	}

	function parse($stmt) {
		$result = oci_parse($this->cid, $stmt);

		return $this->checkError($result);
	}

	function execute($sid, $mode = OCI_COMMIT_ON_SUCCESS) {
		oci_execute($sid, $mode);
	}

	function commit() {
		oci_commit($this->cid);
	}

	function rollback() {
		oci_rollback($this->cid);
	}

	function bind($sid, $bind_name, $var, $maxlen = -1, $type = SQLT_CHR) {
		$result = oci_bind_by_name($sid, $bind_name, $var, $maxlen, $type);

		return $this->checkError($result);
	}

	function new_descriptor($type) {
		$result = oci_new_descriptor($this->cid, $type);

		return $this->checkError($result);
	}

	function toLower($row) {
		$ncols = oci_num_fields($this->sid);
		for ($i = 0; $i < $ncols; $i++) {
			$return[strtolower(oci_field_name($this->sid, $i+1))] = $row[$i];
		}

		return $return;
	}

	function _log($query) {
	}

	function close(){
		if($this->sid) oci_free_statement($this->sid);
		if($thi->cid) oci_close($this->cid);
	}
}