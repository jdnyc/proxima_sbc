<?php
class Solr
{
	const SOLR_URL = 'http://192.168.252.21:8983/';

	function solrQuery($q, $start=0, $rows=20, $sort = null, $highlighting = null)
	{
		if(empty($sort)) $sort = 'create_date+desc';
		$sort = "&sort=".$sort;
		
		if(!empty($highlighting)){
			$highlighting = "&hl=on&hl.fl=*&hl.simple.post=</span>&hl.simple.pre=<span>";
		}

		if($q != '')
		{
			$query = "?q=".trim($q)."&start=".$start."&rows=".$rows."&indent=on&wt=json".$sort.$highlighting;
			$result = $this->request("", "solr/collection1/select".$query);
		}
		return $result;
	}

	function solrQuery_bak($q, $start=0, $rows=20, $qf = null, $sort = null)
	{
		if(empty($sort)) $sort = 'create_date+desc';

		if(!empty($qf))
		{
			$qf = "&defType=dismax&qf=".$qf;
		}
		//$query = "?q=".trim(urlencode($q)).
		$query = "?q=".trim($q).
			"&version=3&start=$start&rows=$rows&indent=true&sort=$sort&wt=json";

		if($q != '')
			$result = $this->request("", "solr/collection1/select".$query);

		return $result;
	}

	function solrDelete($id)
	{
		$xml =  new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><delete />');

		if ( is_array($id) ){
			foreach ( $id as $i ){
				$field = $xml->addChild('id', $id);
			}
		}else{
			$field = $xml->addChild('id', $id);
		}

		$str = $xml->asXML();
		if($str)
			return $success = $this->request($str, 'solr/collection1/update');
		return 0;
	}

	function solrUpdate($group_name, $storage_type, $content_id, $content, $usr_meta_value, $array_data)
	{
		$add =  new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><add />');

		$doc = $add->addChild('doc');

		$field = $doc->addChild('field', $group_name);
		$field->addAttribute('name', 'group');

		$field = $doc->addChild('field', trim($content['category_full_path']));
		$field->addAttribute('name', 'category_full_path');

		$field = $doc->addChild('field', trim($content['bs_content_id']));
		$field->addAttribute('name', 'content_type');

		$field = $doc->addChild('field', trim($content['ud_content_id']));
		$field->addAttribute('name', 'meta_type');

		$field = $doc->addChild('field', trim($content_id));
		$field->addAttribute('name', 'content_id');
		$field = $doc->addChild('field', trim($content_id));
		$field->addAttribute('name', 'content_id_int');

		$field = $doc->addChild('field', trim($content['status']));
		$field->addAttribute('name', 'status');

		$field = $doc->addChild('field', $this->esc(trim($content['title'])));
		$field->addAttribute('name', 'title');

		$field = $doc->addChild('field', $content['created_date']);
		$field->addAttribute('name', 'create_date');

		$field = $doc->addChild('field', $content['is_deleted']);
		$field->addAttribute('name', 'is_deleted');

		$field = $doc->addChild('field', $content['is_group']);
		$field->addAttribute('name', 'is_group');
		
		if($content['is_hidden'] == '1')
		{
			$hiddden_val = 'Y';
		}else
		{
			$hiddden_val = 'N';
		}
		$field = $doc->addChild('field', $hiddden_val);
		$field->addAttribute('name', 'is_hidden');

		$field = $doc->addChild('field', $this->esc($content['reg_user_id']));
		$field->addAttribute('name', 'reg_user_id');

		$field = $doc->addChild('field', $this->esc($content['reg_user_nm']));
		$field->addAttribute('name', 'reg_user_nm');

		$field = $doc->addChild('field', $content['category_id']);
		$field->addAttribute('name', 'category_id');

		$field = $doc->addChild('field', $this->esc($content['category_title']));
		$field->addAttribute('name', 'category_title');

		$field = $doc->addChild('field', $this->esc($content['content_status_nm']));
        $field->addAttribute('name', 'content_status_nm');

		if(!empty($usr_meta_value)){
			foreach ($usr_meta_value as $k=>$v)
			{
				$field = $doc->addChild('field', $this->esc(trim($v)) );
				$field->addAttribute('name', $k.'_t');

				$field2 = $doc->addChild('field', $this->esc(trim($v)) );
				$field2->addAttribute('name', $k.'_s');
			}
		}

		if(!empty($array_data)) {
			foreach($array_data as $k => $v) {
				$field = $doc->addChild('field', $this->esc(trim($v)) );
				$field->addAttribute('name', $k.'_a');
			}
		}

		$str = $add->asXML();
		if($str){
			$success = $this->request($str, "solr/collection1/update");
			return $success;
		}
		return 0;
	}

	function solrCommit()
	{
		return $this->request("<commit/>", "solr/collection1/update");
	}

	function request($reqData, $type)
	{
		@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/'.basename(__FILE__).date('Ymd').'.log',"reqData: \r".$reqData."\n"."type:\r".$type."\n", FILE_APPEND);

		if($type == "solr/collection1/update"){
			$header[] = "Content-type: text/xml; charset=UTF-8";
		}
		else if($type == "solr/gmSearch"){
			$header[] = "Content-type: text/xml; charset=UTF-8";
		}
		else{
			$header[] = "";//"Content-type: text/xml; charset=UTF-8";
		}

		$session = curl_init();
		curl_setopt($session, CURLOPT_HEADER,         false);
		curl_setopt($session, CURLOPT_HTTPHEADER,     $header);
		curl_setopt($session, CURLOPT_URL,            self::SOLR_URL.$type);
		curl_setopt($session, CURLOPT_POSTFIELDS,     $reqData);
		curl_setopt($session, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($session, CURLOPT_POST,           1);

		$response = curl_exec($session);
		@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/'.basename(__FILE__).date('Ymd').'.log',"response: \r".$response."\n", FILE_APPEND);
		if ($response === false)
		{
			throw new Exception('검색쿼리 전송 오류: '.curl_error($session));
		}

		curl_close($session);

//		return '<int name="status">0</int>';

		return $response;
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
}

?>