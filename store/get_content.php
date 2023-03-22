<?php
////////////////////////////////////////////////////
//
//	2010.10.03 수정
//	문서 이미지 경로 null일시 기본이미지 대체
//	해결: imgs/doc.png 경로값 추가.
//	작성자 : 박정근, 김성민
////////////////////////////////////////////////////
require_once($_SERVER['DOCUMENT_ROOT'].'/store/get_content_list/libs/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lib.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/searchengine/solr/searcher.class.php');
// require_once($_SERVER['DOCUMENT_ROOT'].'/lib/Search.class.php');
// require_once($_SERVER['DOCUMENT_ROOT'].'/lib/FirePHPCore/FirePHP.class.php');
fn_checkAuthPermission($_SESSION);
// $fb = FirePHP::getInstance(true);
define('PATH', $_SERVER['DOCUMENT_ROOT'].'/store/get_content_list');

set_time_limit(180);

$searchengine_usable = ($arr_sys_code['interwork_gmsearch']['use_yn'] == 'Y')? true : false;

$is_admin = $_SESSION['user']['is_admin'];
$user_id = $_SESSION['user']['user_id'];

$content_type	= $_POST['content_type'];
$ud_content_id	= $_POST['ud_content_id'];
if( !empty($_POST['meta_table_id']) ) $ud_content_id	= $_POST['meta_table_id'];

$order_field	= $_POST['sort'];
$order_dir		= $_POST['dir'];
$start			= $_POST['start'];
$limit			= $_POST['limit'];
if(empty($start)){
	$start = 0;
}

$filter_type	= $_POST['filter_type'];
$filter_value	= $_POST['filter_value'];

$mode			= $_POST['mode'];
$action			= $_POST['action'];

$list_type 		= $_POST['list_type'];

$search_q 		= $_POST['search_q'];

//$bs_content_id = $db->queryOne("select bs_content_id from bc_ud_content where ud_content_id='$ud_content_id'");

//$ud_content_id	= 4;
//$order_field	= 'content_id';
//$order_dir		= 'desc';
//$start			= 0;
//$limit			= 20;
/**
 * 검색엔진이 사용 가능하면 다 무시하고 검색엔진으로 동작한다.
 */
if( $searchengine_usable ){
	$target_page = '/solr_search.php';
}else if ($filter_type == 'date')
{
	$target_page = '/filter_date.php';				//등록일자 검색
}
else if ($action == 'favorite') {
       $target_page = '/favorite.php';
}
else if ($action == 'a_search')
{
	$target_page = '/common_search.php';
// 	$target_page = '/wisenut_search.php';					//상세검색
//	$target_page = '/a_search.php';
}
else if ($action == 'program')
{
	$target_page = '/program.php';					//프로그램별
}
else if ($mode == 'advance')
{
	$target_page = '/advanceSearch.php';			//
}
else if ($list_type == 'common_search')
{
	$target_page = '/common_search.php';
}
else if (empty($list_type))
{
	$target_page = '/common_search.php';//'/common.php';
}
else
{
	$target_page = '/'.$list_type.'.php';
}

// echo $target_page;

include_once(PATH.$target_page);
?>