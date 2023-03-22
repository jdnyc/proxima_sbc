<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/searchengine/solr/searcher.class.php');

$search_q = $_REQUEST['search_key'];

$searchengine_usable = ($arr_sys_code['interwork_gmsearch']['use_yn'] == 'Y')? true : false;

if ($searchengine_usable){
	if(strpos($search_q, ' ') >= 0){
		//공백 포함
		$search_q_arr = explode(" ", $search_q);
		$searchkey_q = array();
		foreach($search_q_arr as $key){
			array_push($searchkey_q, "title:*".$key."*");
		}

		$search_p_arr[] = join($searchkey_q, ' OR ');
	}
	else {
		$search_key = strpos($search_q, ' ')? '"'.$search_q.'"' : '*'.$search_q.'*';
		$search_p_arr[] ='title:'.$search_key;
	}
	$search_p = '('.join($search_p_arr, ' OR ').')';
	$search_p .= 'AND !status:\-3';
	$search_p .= 'AND group:"proxima"';
    die();
	$engine = new Searcher($db);
	$data = $engine->search(urlencode($search_p), 0, 10, "");

	echo $data;
}
else {
	echo null;
}

?>