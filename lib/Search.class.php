<?php
class Search
{
	const SEARCH_IP = '192.168.10.206';
	const SEARCH_PORT = '7800';
	const SEARCH_PAGE = 'cha/search.jsp';
	const UPDATE_PAGE = 'cha/resultXml.jsp';



	function SearchQuery($query_array, $start=0, $rows=20){
		$query_array['start'] = $start;
		$query_array['rows'] = $rows;
		$query = $this->buildURL( $query_array );

		$log = 'http://'.self::SEARCH_IP.':'.self::SEARCH_PORT.'/'.self::SEARCH_PAGE.'?'.$query;

		$this->_log($log);

		$return_text = $this->SearchRequestSoket(self::SEARCH_IP, self::SEARCH_PAGE.'?'.$query, '', self::SEARCH_PORT);

		$this->_log($return_text);
		$return_text = $this->returnSubStr($return_text);

		return $return_xml = $this->checkXMLSyntax($return_text);

		return 0;
	}

	function SearchUpdateForWN($xml){

		$this->_log($xml);
		$send_string = http_build_query(array(
			'buffer' => $xml
		));
		$return_text = $this->SearchRequestSoket(self::SEARCH_IP, self::UPDATE_PAGE, $send_string, self::SEARCH_PORT);
		$this->_log($return_text);
		$return_text = $this->returnSubStr($return_text);
		return $return_xml = $this->checkXMLSyntax($return_text);
		return 0;

	}

	function delete($ud_content_id, $content_id){

		$table = MetaDataClass::getTableInfo('usr', $ud_content_id);
		$table_name = strtolower($table[ud_content_code]);

		$cxml = new SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<delete />");
		$doc = $cxml->addChild('doc');
		$field = $doc->addChild('field', $table_name );
		$field->addAttribute('name', 'COLLECTION');

		$doc = $cxml->addChild('doc');
		$field = $doc->addChild('field', $content_id );
		$field->addAttribute('name', 'DOCID');

		$xml = $cxml->asXML();

		$return =  $this->SearchUpdateForWN($xml);
		if( $return->status == 0){
			return true;
		}else{
			throw new Exception($return->message , 101);
		}
	}

	function update($type, $ud_content_id, $content_id){
		global $db;
		$_select = array();
		$_from = array();
		$_where = array();
		$_order = array();

		array_push($_select , " c.* " );
		array_push($_from , " view_bc_content c " );
		array_push($_where , " c.content_id = '$content_id' " );

		$table = MetaDataClass::getTableInfo('usr', $ud_content_id);
		$table_name = strtolower($table[ud_content_code]);

		//로우 메타데이터에서 컬럼 메타데이터로 변경 2014-06-19 이성용
		$renQuery = MetaDataClass::createMetaQuery('usr' , $ud_content_id , array(
			'select' => $_select,
			'from' => $_from,
			'where' => $_where,
			'order' => $_order
		) );
		$_select = $renQuery[select];
		$_from = $renQuery[from];
		$_where = $renQuery[where];

		array_push($_select , " FUNC_PROXY_URL(c.bs_content_id,c.content_id) proxy_url  " );
		//array_push($_select ," case c.IS_DELETED when 'N' then 'U' when 'Y' then 'D' end as \"USE_YN\" ");
		//array_push($_select ," c.LAST_MODIFIED_DATE as  MODIFY_DATE ");

		$select = " select ".join(' , ', $_select);
		$from = " from ".join(' , ', $_from);
		if (!empty($_where)){
			$where = " where ".join(' and ', $_where);
		}
		$query = $select.$from.$where;
		$metadata = $db->queryRow($query);

		if($metadata['is_deleted'] == 'Y'){
			return true;
		}

		if(empty($metadata)) throw new Exception("empty content", 106);

		$cxml = new SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<$type />");
		$doc = $cxml->addChild('doc');
		$field = $doc->addChild('field', $table_name );
		$field->addAttribute('name', 'COLLECTION');

		$doc = $cxml->addChild('doc');
		$field = $doc->addChild('field', $content_id );
		$field->addAttribute('name', 'CONTENT_ID');

		foreach($metadata as $key => $val)
		{
			if($key == 'content_id') continue;
			//값
			$field = $doc->addChild('field', $val );
			//필드명
			$field->addAttribute('name', strtoupper($key) );
		}
		$xml = $cxml->asXML();

		$return =  $this->SearchUpdateForWN($xml);
		if( $return->status == 0){
			return true;
		}else{
			throw new Exception($return->message , 101);
		}

	}
	function buildURL($param){
		$url =  http_build_query($param) ;

		return $url;
	}

	function SearchParam($params){
		if(empty($params)) return false;

		$query_array = array();
		$type_array = array();
		$order_array = array();

		$p = json_decode($params);

		$typeMap = array(
			'AND' => '',
			'OR' => '|'
		);

		$ExtFieldMap = array(
			'USR_PROGID',
			'USR_MATERIALID',
			'USR_INGEST_SYSTEM'
		);

		$fieldIdtoNameMap = MetaDataClass::getFieldIdtoNameMap('usr' , $p->meta_table_id);
		foreach ($p->fields as $key => $field) {
			$field_name='';
			if ( is_numeric($field->meta_field_id)) {
				$field_name = $fieldIdtoNameMap[$field->meta_field_id];
				//<TITLE:contains:명랑> <TITLE:contains:방송>
				if( empty($field_name) ) continue;
				if ( $field->type == 'datefield' )	{
					array_push($type_array, $typeMap['AND'] );
					if(!empty($field->order_type)){
						array_push($order_array, $field_name.'_SORT'.'/'.$field->order_type );
					}

					if ( empty($field->s_dt) ) {
						array_push($query_array, '^F<'.$field_name.':lte:'.$field->e_dt.'>');
					}else if ( empty($field->e_dt) ) {
						array_push($query_array, '^F<'.$field_name.':gte:'.$field->s_dt.'>');
					}else {
						array_push($query_array, '^F<'.$field_name.':gte:'.$field->s_dt.'> <'.$field_name.':lte:'.$field->e_dt.'>');
					}
				}else{

					if(in_array($field_name,$ExtFieldMap)){
						$search_field_type = 'E';
					}else{
						$search_field_type = 'C';
					}

					array_push($query_array, '^'.$search_field_type.'<'.$field_name.':contains:'.$field->value.'>');
					array_push($type_array, $typeMap['AND'] );
					if(!empty($field->order_type)){
						array_push($order_array, $field_name.'_SORT'.'/'.$field->order_type );
					}
				}
			}
			else {
				if ($field->meta_field_id == 'created_date') {
					$field_name = strtoupper($field->meta_field_id);
					array_push($type_array, $typeMap['AND'] );

					if(!empty($field->order_type)){
						array_push($order_array, $field_name.'_SORT'.'/'.$field->order_type );
					}

					if ( empty($field->s_dt) ) {
						array_push($query_array, '^F<'.$field_name.':lte:'.$field->e_dt.'>');
						//$tables[] = '(select distinct usr_content_id as content_id from '.$tablename.' where '.$field_name.' <= \''.$f->e_dt.'\') t'.$i++;
					}else if ( empty($field->e_dt) ) {
						array_push($query_array, '^F<'.$field_name.':gte:'.$field->s_dt.'>');
						//$tables[] = '(select distinct usr_content_id as  content_id from '.$tablename.' where '.$field_name.' >= \''.$field->s_dt.'\') t'.$i++;
					}else {
						array_push($query_array, '^F<'.$field_name.':gte:'.$field->s_dt.'><'.$field_name.':lte:'.$field->e_dt.'>');
						//$tables[] = '(select distinct usr_content_id as  content_id from '.$tablename.' where '.$field_name.' >= \''.$field->s_dt.'\' and $field_name <= \''.$field->e_dt.'\') t'.$i++;
					}
				}else{
					$field_name = strtoupper($field->meta_field_id);
					array_push($query_array, '^C<'.$field_name.':contains:'.$field->value.'>');
					array_push($type_array, $typeMap['AND'] );
					if(!empty($field->order_type)){
						array_push($order_array, $field_name.'_SORT'.'/'.$field->order_type );
					}
				}
			}
		}

		$query_list = array();

		foreach($query_array as $key => $list )
		{
			array_push($query_list,$list);

			if( !empty($query_array[$key+1]) ){
				array_push($query_list,$type_array[$key]);
			}
		}
		return array(
			'query' => join('', $query_list),
			'order' => join(',',$order_array)
		);
	}

	function SearchRequestSoket($host, $page, $string, $port='80')
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

	function MapQtip ( $lists ,$nameMap ){
		$qtip_list = array();
		foreach($lists as $key => $value)
		{
			$name = $nameMap[$key];
			if(empty($name)) $name = $key;
			$value = (string)$value;

			if( !empty($name) && strstr($value, '<!HS>') && strstr($value, '<!HE>') ){
				$value = htmlspecialchars($value);
				$value = preg_replace('/&lt;!HS&gt;/i', "<span style='color: red'>", $value );
				$value = preg_replace('/&lt;!HE&gt;/i', "</span>", $value );

				$qtip_list[] = '<li><b>'.$name.'</b>: '.$value.'</li>';
			}
		}

		return '<ul>'.join('', $qtip_list).'</ul>';

	}

	function esc($str)
	{
		$str = str_replace('&', '&amp;',  $str);
		$str = str_replace('<', '&lt;',   $str);
		$str = str_replace('>', '&gt;',   $str);
		$str = str_replace('"', '&quot;', $str);
		$str = str_replace('\'', '&#39;', $str);

		return $str;
	}

	function returnSubStr($return_text){
		$return = substr( $return_text , strpos( $return_text, '<'));
		return $return;
	}

	function xmltojson($xml){
		$receive_json = json_encode($xml);
		$json = json_decode($receive_json,TRUE);
		return $json;
	}

	function checkXMLSyntax($receive_xml)
	{
		libxml_use_internal_errors(true);
		$rtn = simplexml_load_string($receive_xml, 'SimpleXMLElement', LIBXML_NOCDATA );
		if (!$rtn) {
			foreach(libxml_get_errors() as $error)
			{
				$err_msg .= $error->message . "\n";
			}
			throw new Exception('xml 파싱 에러: '.$err_msg);
		}

		return $rtn;
	}

	function _log($log){
		@file_put_contents(LOG_PATH.'/Search_'.date('Ymd').'.log', '['.date('Y-m-d H:i:s').'] '.$log."\n", FILE_APPEND);
	}
}

?>
