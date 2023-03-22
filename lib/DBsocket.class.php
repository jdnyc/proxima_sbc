<?php
class DatabaseSoket
{
	var $host = null;//소켓 호스트
	var $page = null;//소켓 호출 페이지
	var $port = null;//소켓 포트
	var $str = null;//전송 내용
	var $type = null;//사용 유형
	var $query = null;
	var $order = null;

	var $limit = null;
	var $offset = null;
	var $last_query;


	function __construct($host, $page, $port, $type )
	{
		$this->host = $host;//DMC_MAM_SERVER_IP
		$this->page = $page;//'/interface/link_cms/getDMCInfo.php';
		$this->port = $port;//DMC_MAM_SERVER_PORT;
		$this->type = $type;//online_tape

		$this->str = array(
			'type' => $this->type,
			'query' => '',
			'start' => '',
			'limit' => '',
			'order' => ''
		);
	}


	function Post_XML_Soket_for_DB($host, $page, $string, $port='80')
	{
		$return = '';
		$fp = fsockopen($host, $port, $errno, $errstr, 30);
		if (!$fp) {
			return "$errstr ($errno)<br />\n";
		}else{
			$out = "POST /".$page." HTTP/1.1\r\n";
			$out .= "User-Agent: Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1; WOW64; Trident/4.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0)\r\n";
			$out .= "Content-type: application/x-www-form-urlencoded\r\n";
			$out .= "Content-length: ". strlen($string) ."\r\n";
			$out .= "Host: ".$host."\r\n";
			$out .= "Connection: Close\r\n\r\n";
			$out .= $string;
			fwrite($fp, $out);
			while (!feof($fp)) {
				$return .= fgets($fp, 128);
			}
			fclose($fp);
		}
		return $return;
	}

	function escape($str)
	{
		return str_replace("'", "''", $str);
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

	function queryOne($query)
	{
		$this->limit = 1;
		$this->offset = 0;
		$result = $this->exec2($query);

		if( !empty($result) ){

			foreach($result[0] as $key => $val )
			{
				return $val;
			}
		}
		return false;
	}

	function queryRow($query)
	{
		$this->limit = 1;
		$this->offset = 0;
		$result = $this->exec2($query);
		if (empty($result)) {
			return array();
		}
		return $result[0];
	}

	function queryAll($query) {
		$result = $this->exec2($query);

		$this->limit = null;
		$this->offset = null;

		return $result;
	}

	function exec($query)
	{
		$return = false;
		$this->last_query = $query;

		$this->str['query'] = $query;

		$this->str['limit'] =  $this->limit;
		$this->str['start'] =  $this->offset;
		$this->str['exec'] =  true;

		$this->_log($query);

		$rtn = $this->Post_XML_Soket_for_DB($this->host, $this->page, json_encode($this->str), $this->port);

		$this->_log($rtn);

		$rtn = nl2br($rtn);
		$rtn = explode('<br />', $rtn);
		foreach($rtn as $list)
		{
			$list = trim($list);
			if( json_decode($list ,true) ){
				if( is_numeric($list) ) continue;

				$rtn = json_decode($list, true);
				if( !is_array($rtn) || !$rtn['success'] ){
					throw new Exception('전송 오류');
				}

				$return = $rtn['data'];
			}
		}

		$this->limit = null;
		$this->offset = null;

		//$this->_log($query);


		return $return;
	}

	function exec2($query)
	{
		$return = false;
		$this->last_query = $query;

		$this->str['query'] = $query;

		$this->str['limit'] =  $this->limit;
		$this->str['start'] =  $this->offset;

		$this->_log($query);

		$rtn = $this->Post_XML_Soket_for_DB($this->host, $this->page, json_encode($this->str), $this->port);

		$this->_log($rtn);

		$rtn = nl2br($rtn);
		$rtn = explode('<br />', $rtn);
		foreach($rtn as $list)
		{
			$list = trim($list);
			if( json_decode($list ,true) ){
				if( is_numeric($list) ) continue;

				$rtn = json_decode($list, true);
				if( !is_array($rtn) || !$rtn['success'] ){
					throw new Exception('전송 오류');
				}

				$return = $rtn['data'];
			}
		}

		$this->limit = null;
		$this->offset = null;

		//$this->_log($query);


		return $return;
	}

	function _log($query)
	{
		@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/database/database_soket_'.date('Ymd-H').'.log', date('Y-m-d H:i:s')."[".$_SERVER['REMOTE_ADDR']."]\t".iconv('utf-8', 'euc-kr', $query)."\n", FILE_APPEND);
	}
}
?>