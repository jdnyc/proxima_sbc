<?php
/****************************************************************
 * 11-11-16, 승수.
 * 상황은 4가지로 구별해서.  1. 전체 리스트, 	2. 전체 리스트 엑셀출력
 *						3. 검색 결과		4. 검색결과 엑셀출력
 * 각각에 따라 처리


 2016-01-20
 쿼리 수정
 ****************************************************************/
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

$mode = $_REQUEST['mode'];//mode에 excel값이 들어올 경우 엑셀로 출력하는 양식
$search_word = $_REQUEST['search_word'];//검색했을 시

try{
	$_where = "";
	if( $search_word ){
		$_where = "
			AND ( UPPER(CODE) LIKE UPPER('%".$search_word."%')  OR  UPPER(CODE_NM) LIKE UPPER('%".$search_word."%')  OR  UPPER(MEMO) LIKE UPPER('%".$search_word."%') )
		";
	}
	$order = "ORDER BY SORT ASC, ID ASC";
	$query = "
		SELECT	ID, CODE, CODE_NM, CODE_NM_ENGLISH, SORT, USE_YN, MEMO, REF1, REF2, REF3, REF4, REF5
		FROM	BC_SYS_CODE
		WHERE	TYPE_ID = 1
		".$_where."
	";
	$total = $db->queryOne("
		SELECT COUNT(*)
		FROM		(".$query.") CNT
	");

	$result = $db->queryAll($query.$order);

	if( $mode == 'excel' ){
		echo createExcelFile('code_list', $result);
	}else{
		echo json_encode(array(
				'success' => true,
				'total' => $total,
				'data' => $result
		));
	}
}catch (Exception $e){
	echo json_encode(array(
		'success' => false,
		'msg' => $e->getMessage()
	));
}
?>