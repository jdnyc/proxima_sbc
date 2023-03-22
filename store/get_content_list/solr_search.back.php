<?php

if(!defined('DS'))
	define('DS', DIRECTORY_SEPARATOR);

$rootDir = dirname(dirname(__DIR__));	
require_once($rootDir . DS .'lib' . DS . 'config.php');
require_once($rootDir . DS .'lib' . DS . 'functions.php');
require_once($rootDir . DS .'searchengine' . DS . 'solr' . DS . 'searcher.class.php');

use Proxima\core\Session;
use Proxima\models\content\Category;
Session::init();

try {

	// _t  자연어
	// _s  like~ 나 equal 검색
	$search_q = $_POST['search_q'];
	$category_path = $_REQUEST['filter_value'];
	$start = (empty($_POST['start']))? 0 : $_POST['start'];
	$limit = (empty($_POST['limit']))? 20 : $_POST['limit'];

	$sdate = $_REQUEST['sdate'];
	$edate = $_REQUEST['edate'];

	$instt = $_POST['instt'];

	if(!is_null($_POST['search_tbar'])){
        $searchTbar = json_decode($_POST['search_tbar']);
	}

	$sort = (empty($_POST['sort']))? 'created_date' : $_POST['sort'];
	$dir = (empty($_POST['dir']))? 'DESC' : $_POST['dir'];

	$ud_content_id = $_REQUEST['ud_content_id'];

	/*싱글만 지원되므로 아래와 같이 처리 2017.12.22 Alex */
	$tag_category_id = $_REQUEST['tag_category_id'];

	$filters = json_decode($_REQUEST['filters'], true);

	$search_p_arr = array();
	$search_p_ud_arr = array();
	$searchkey_p_arr = array();

	$ud_total_list = array();
	
	$ud_contents = $db->queryAll("SELECT UD_CONTENT_ID FROM BC_UD_CONTENT WHERE UD_CONTENT_ID != $ud_content_id ORDER BY SHOW_ORDER ASC");
    
	foreach($ud_contents as $ud_content){
		$ud_total_list[$ud_content['ud_content_id']] = 0;
	}

	/**
	 * 검색엔진으로 통합검색시 카테고리 명도 검색이 되도록 수정
	 * 검색시 검색어 searchkey에 걸리거나 카테고리 타이틀에 걸리거나 검색되도록 수정 - 2018.03.07 Alex
	 */
	$search_ctgr_nm = array();

	if(!empty($search_q)){
		$search_q = solr_encode($search_q);
		if(strpos($search_q, ' ') >= 0) {
			$search_q_arr = explode(" ", $search_q);
			$searchkey_q = array();
			foreach($search_q_arr as $key){
				array_push($searchkey_q, "searchkey:*".$key."*");
				$search_ctgr_nm[] = "category_title:*".$key."*";
			}

			$searchkey_p_arr[] = join($searchkey_q, ' AND ');
		} else {
			$searchkey_p_arr[] ='searchkey:'.strpos($search_q, ' ')? '"'.$search_q.'"' : '*'.$search_q.'*';
			$search_ctgr_nm[] = "category_title:*".$key."*";
		}

		$search_key = '(('.join($searchkey_p_arr, ' AND ').') OR ('.join($search_ctgr_nm, ' AND ').'))';
	}

	if(!empty($search_key)) {
		$search_p_arr[] = $search_key;
	}

	/*CJ오쇼핑의 경우 전체 상태값에 대한 항목 추가  
	if($arr_sys_code['use_menu_accept']['use_yn'] == 'Y') {
		if(is_numeric($_REQUEST[content_status])){
			$search_p_arr[] = '(status:'.$_REQUEST[content_status].' )';
		}else{
			if($_SESSION['user']['is_admin'] == 'Y'){
				$search_p_arr[] = '(status:2 OR status:0 OR status:"-3" )';
			}else{
				$search_p_arr[] = '(status:2 )';
			}
		}
	}else{
		$search_p_arr[] = '(status:2 OR status:0 OR status:"-3" )';
	}
	*/
    
	/* 기본검색은 -3(콘텐츠 입수 이전 상태)은 제외하고 검색 2017.12.21 Alex */	
	// 필터 에 따라 조건을 넣도록 변경 khk
	if(!empty($filters)) {
		
		if(($filters['content_status'] !== null) && ($filters['content_status'] !== 'All')) {
			$search_p_arr[] = "status: {$filters['content_status']}";			
		}
		if(($filters['content_review_status'] !== null) && ($filters['content_review_status'] !== 'All')) {
			$search_p_arr[] = "review_status: {$filters['content_review_status']}";			
		}
		if(($filters['content_archive_status'] !== null) && ($filters['content_archive_status'] !== 'All')) {
			if($filters['content_archive_status'] === 'Y'){
                    $search_p_arr[] = " archive_status_s:[\"1\" TO \"3\"]";
			}else{
				$search_p_arr[] = " !archive_status_s:[\"1\" TO \"3\"]";
			}
			// $search_p_arr[] = "archv_sttus: {$filters['content_archive_status']}";			
		}

		// 년도별 카테고리 검색 조건
		if (($filters['category_start_date'] !== null) && ($filters['category_end_date'] !== null)) {
			// 생성날짜
			//$search_p_arr[] = 'create_date:['.$filters['category_start_date'].' TO '.$filters['category_end_date'].']';
			// 제작일자
			$search_p_arr[] = 'prod_de_t:['.$filters['category_start_date'].' TO '.$filters['category_end_date'].']';
		}

		if(($filters['created_date'] !== null) && ($filters['created_date'] !== 'All')) {
			$today = date('Ymd');
			
			if(strlen($filters['created_date']) === 14){
				$fromDate = $filters['created_date'];
				$search_p_arr[] = "create_date:[{$fromDate} TO {$today}240000]";
			}else{
				$fromDate = date('Ymd', strtotime("-{$filters['created_date']} day"));
				$search_p_arr[] = "create_date:[{$fromDate}000000 TO {$today}240000]";
			};
		}

		if(($filters['brdcst_stle_se'] !== null) && ($filters['brdcst_stle_se'] !== 'All') && ($filters['brdcst_stle_se'] !== '전체')){
			$search_p_arr[] = "brdcst_stle_se_t:\"{$filters['brdcst_stle_se']}\"";	
		}
        
		if(($filters['matr_knd'] !== null) && ($filters['matr_knd'] !== 'All') && ($filters['matr_knd'] !== '전체')){
			$search_p_arr[] = "matr_knd_t:\"{$filters['matr_knd']}\"";	
		}
	
		if(is_null($instt) || ($instt == "")){
			if($filters['category_id'] !== null) {
				$category_path = Category::getPath($filters['category_id']);		
			}
		}else{
			// 부처영상 클릭검색일때
			$category_path = null;
			$search_p_arr[] = "regist_instt_s:\"{$instt}\"";
		}
		
		if(defined('CUSTOM_ROOT') && class_exists('\ProximaCustom\core\SolrSearchCustom')) {			
			$emptyResponse = ['success' => true,
						'total' => 0,
						'q' => $search_q,
						'data' => $data,
						'ud_content_id' => $ud_content_id,
						'results' => array(),
						'search'=>'solr zero',
						'ud_total_list' => $ud_total_list];
			$searchConditions = \ProximaCustom\core\SolrSearchCustom::getFilterSearchCondition($filters, $emptyResponse);
			if(!empty($searchConditions)) {
				$search_p_arr[] = '('.implode(' OR ', $searchConditions).')';
			}
		}

	} 
    
	/* Tag ID가 있으면 해당 태그에 해당되는 콘텐츠 조건 추가 2017.12.22 Alex */
	if(!empty($tag_category_id)) {
		$tag_contents = $db->queryAll("
						SELECT	CONTENT_ID
						FROM	BC_TAG
						WHERE	TAG_CATEGORY_ID = $tag_category_id
					");
		
		if(count($tag_contents) > 0) {
			$tag_search_contents = array();
			foreach($tag_contents as $content) {
				$tag_content_id = $content['content_id'];
				array_push($tag_search_contents, 'content_id:'.$tag_content_id);
			}

			$search_p_arr[] = '('.implode(' OR ', $tag_search_contents).')';
		} else {
			$ud_total_list[$ud_content_id] = 0;
			die(json_encode(array(
				'success' => true,
				'total' => 0,
				'q' => $search_q,
				'data' => $data,
				'results' => array(),
				'search'=>'solr zero',
				'ud_total_list' =>$ud_total_list
			)));
		}
	}

	//허용된 사용자 이외에는 히든된 콘텐츠는 검색안함 2017.12.21 Alex
	if($_SESSION['user']['allow_hiddenSearch'] == 'N') {
		$search_p_arr[] = 'is_hidden:N';
	}

	$search_p_arr[] = ' is_deleted:N';
	$search_p_arr[] = ' !is_group:C';
	if(!empty($category_path) && $category_path != '/0/') {
		$search_category_path = str_replace('/', '\/', $category_path).'*';
		$search_p_ud_arr[] = 'category_full_path:'.$search_category_path;
	}
	if($ud_content_id != 'all') $search_p_ud_arr[] = 'meta_type:'.$ud_content_id;

	/*사용자화 for SMC*/
	if( $arr_sys_code['smc_yn']['use_yn']=='Y'){
		if($_SESSION['user']['is_admin'] != 'Y'){
			$search_p_arr[] = 'usr_isopen_t:Y';
		}
	}

	if(!empty($sdate) && !empty($edate))
	{
		$sdate = date('Ymd', strtotime($sdate)).'000000';
		$edate = date('Ymd', strtotime($edate)).'235959';
		
		$search_p_arr[] = 'create_date:['.$sdate.' TO '.$edate.']';
	}

	if (!is_null($searchTbar) && !is_null($searchTbar->start_date) && !is_null($searchTbar->end_date) && ($searchTbar->start_date != "All")) {
		$search_p_arr[] = 'create_date:['.$searchTbar->start_date.' TO '.$searchTbar->end_date.']';
	}
	
	$search_q_common = join($search_p_arr, ' AND ');
	$search_p_ud = join($search_p_ud_arr, ' AND ');
    
	$search_q = $search_q_common.' AND '.$search_p_ud;
    
	// -3인 콘텐츠 검색 안되도록
	$search_q .= ' AND !status:\-3';

	$sort_str = 'create_date+DESC';
	if(!empty($sort) && !empty($dir)) {
		if($sort == 'created_date'){
			$sort = 'create_date';
			$sort_str = $sort.'+'.$dir;
		}
		else if($sort == 'title'){
			$sort_str = $sort.'+'.$dir;
		}
		else if($sort == 'content_id'){
			$sort = 'content_id_int';
			$sort_str = $sort.'+'.$dir;
		}
		else if($sort == 'category_title' || $sort == 'category'){
			$sort = 'category_title';
			$sort_str = $sort.'+'.$dir;
		}
		else {
			$sort_str = $sort.'_t+'.$dir;
		}
	}
    
    $engine = $engine = app()->getContainer()['searcher'];
	$data = $engine->search(urlencode($search_q), $start, $limit, $sort_str);
	$data = json_decode($data);
	$total = $data->response->numFound;
    
	$map_categories = getCategoryMapInfo();
	$mapping_category = json_encode($map_categories);
 
	if($total > 0){
		$content_list = mapping_meta($data->response->docs);
		$contents = $content_list;
		$contents = fetchMetadata($content_list);		
		if( $ud_content_id != 'all' ){
			$ud_total_q = array();
			$ud_data = array();

			foreach($ud_contents as $ud_content){
				$ud_search_q = $search_q_common.' AND meta_type:'.$ud_content['ud_content_id'].' AND !status:\-3';
          
				/**
				 * Root Category ID가 같을 경우에는 category_full_path도 포함해서 검색하도록 수정
				 * 2018.01.23 Alex
				 */
				if($map_categories[$ud_content_id]['category_id'] == $map_categories[$ud_content['ud_content_id']]['category_id'] && !empty($search_category_path)) {
					$ud_search_q = $ud_search_q.' AND category_full_path:'.$search_category_path;
				}

				$ud_data = $engine->search(urlencode($ud_search_q), 0, 1);
				$ud_data = json_decode($ud_data,true);
				$ud_total_list[$ud_content['ud_content_id']] = $ud_data['response']['numFound'];
				$ud_total_q[$ud_content['ud_content_id']] = $ud_search_q;
			}

		}
 
		$ud_total_list[$ud_content_id] = $total;

		echo json_encode(array(
			'success' => true,
			'total' => $total,
			'q' => $search_q,
			'total_q' => $ud_search_q,
			'data' => $data,
			'results' => $contents,
			'search'=>'solr',
			'ud_total_list' =>$ud_total_list
		));
	}
	else{
		if( $ud_content_id != 'all' ){
			$ud_total_q = array();
			$ud_data = array();

			foreach($ud_contents as $ud_content){
				$ud_search_q = $search_q_common.' AND meta_type:'.$ud_content['ud_content_id'].' AND !status:\-3';
				/**
				 * Root Category ID가 같을 경우에는 category_full_path도 포함해서 검색하도록 수정
				 * 2018.01.23 Alex
				 */
  
				if($map_categories[$ud_content_id]['category_id'] == $map_categories[$ud_content['ud_content_id']]['category_id'] && !empty($search_category_path)) {
					$ud_search_q .= ' AND category_full_path:'.$search_category_path;
				}

				$ud_data = $engine->search(urlencode($ud_search_q), 0, 1);
				$ud_data = json_decode($ud_data,true);
				$ud_total_list[$ud_content['ud_content_id']] = $ud_data['response']['numFound'];
				$ud_total_q[$ud_content['ud_content_id']] = $ud_search_q;
			}
		}
 
		$ud_total_list[$ud_content_id] = $total;
		
		die(json_encode(array(
			'success' => true,
			'total' => 0,
			'q' => $search_q,
			'total_q' => $ud_search_q,
			'data' => $data,
			'results' => array(),
			'search'=>'solr zero',
			'ud_total_list' =>$ud_total_list
		)));
	}
}
catch(Exception $e){
	die(json_encode(array(
		'success' => false,				
		'msg'=>$e->getMessage()
	)));
}

function mapping_meta($datas){
	global $db;
	$ud_content_id = 46;

	$result = array();
	if(!empty($datas)){

		$i = 0;
		foreach($datas as $data){
			$content = $db->queryRow("
				select	c.*, ud.ud_content_title
				from	bc_content c
						left outer join bc_ud_content ud on ud.ud_content_id = c.ud_content_id
				where	content_id =".$data->content_id
			);
			$usrMetaContent = $db->queryRow("
				SELECT USE_PRHIBT_AT, EMBG_RELIS_DT 
				FROM BC_USRMETA_CONTENT 
				WHERE USR_CONTENT_ID =".$data->content_id
			);
            
			foreach($content as $k => $v){
				$result[$i][$k] = $v;
				$result[$i]['usr_meta'] = $usrMetaContent;
			}
	
			foreach($data as $k => $v){
				if(substr($k, -2, 2) == '_t' || substr($k, -2, 2) == '_i'){
					$k = substr($k, 0, strlen($k)-2);
				}
				$result[$i][$k] = $v;
				if($k == 'create_date') $result[$i]['created_date'] = $v;
			}
			$i++;
		}
	}


	return $result;
}

function ordutf8($string, &$offset) {
	$code = ord(substr($string, $offset,1)); 
	if ($code >= 128) {		//otherwise 0xxxxxxx
		if ($code < 224) $bytesnumber = 2;				//110xxxxx
		else if ($code < 240) $bytesnumber = 3;		//1110xxxx
		else if ($code < 248) $bytesnumber = 4;	//11110xxx
		$codetemp = $code - 192 - ($bytesnumber > 2 ? 32 : 0) - ($bytesnumber > 3 ? 16 : 0);
		for ($i = 2; $i <= $bytesnumber; $i++) {
			$offset ++;
			$code2 = ord(substr($string, $offset, 1)) - 128;		//10xxxxxx
			$codetemp = $codetemp*64 + $code2;
		}
		$code = $codetemp;
	}
	$offset += 1;
	if ($offset >= strlen($string)) $offset = -1;
	return $code;
}

function solr_encode($string) {
		//escape필요한 특수문자 : + - && || ! ( ) { } [ ] ^ " ~ * ? : \ /
		$illegal = array(
			"+","-","&&","||","!",
			"(",")","{","}","[",
			"]","^",'"',"~","*",
			"?",":","/",";");
	
		//2017-11-01 이승수. 특수문자 검색 안됨.
		//검색 되는 항목은 현재 +, -, &&, / 4가지. 나머지는 검색어에서 제외
	
		$replace = array(
			"\+","\-","\&\&"," "," ",
			" "," "," "," "," ",
			" "," "," "," "," ",
			" "," ","\/"," ");//검색되는 4개만 역슬래쉬 넣음 
	
		//역슬래쉬 먼저 변경. 배열비교방식에서 변경하면 두번 변경되므로
		//$string = str_replace("\\", "\\\\", $string);//역슬래쉬 검색 될 경우 처리
		$string = str_replace(".", " ", $string);//닷(.)을 공백으로 치환
		$string = str_replace("\\", " ", $string);//역슬래쉬도 검색 안되므로 제외
		$string = str_replace($illegal, $replace, $string);
	
		return $string;
	}
?>