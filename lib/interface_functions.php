<?php

function interfaceQuery($query,$order,$limit, $start){
	//타겟 주소
	$host = DMC_MAM_SERVER_IP;
	$page = '/interface/link_cms/getDMCInfo.php';
	$port = DMC_MAM_SERVER_PORT;
	$str = json_encode(array(
		'type' => 'online_tape',
		'query' => $query,
		'start' => $start,
		'limit' => $limit,
		'order' => $order
	));

	$rtn = Post_XML_Soket($host, $page, $str, $port);
	$rtn = nl2br($rtn);
	$rtn = explode('<br />', $rtn);
	foreach($rtn as $list)
	{
		$list = trim($list);
		if( json_decode($list ,true) ){
			if( is_numeric($list) ) continue;

			$rtn = json_decode($list, true);
			if( !is_array($rtn) || !$rtn['success'] ){
				return array();
			}

			return $rtn['data'];
		}
	}

	return array();
}
?>