<?php
/****************************************************************
 * 11-11-16, 승수.
 * 상황은 4가지로 구별해서.  1. 전체 리스트, 	2. 전체 리스트 엑셀출력
 *						3. 검색 결과		4. 검색결과 엑셀출력
 * 각각에 따라 처리


 2016-01-20
 쿼리 수정

 2016-04-01
 쿼리 수정
 ****************************************************************/
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

$mode = $_REQUEST['mode'];//mode에 excel값이 들어올 경우 엑셀로 출력하는 양식
$search_word = $_REQUEST['search_word'];//검색했을 시

try{
	$_where = "";
	if($_POST['type'] == 'code_type' ){
		if( $search_word ){
			$_where = "
				WHERE  CODE LIKE '%".$search_word."%'  OR  NAME LIKE '%".$search_word."%'
			";
		}
		$query = "
			SELECT	CODE, NAME, ID
			FROM		BC_CODE_TYPE
			".$_where."
		";
		$total = $db->queryOne("
			SELECT COUNT(*)
			FROM		(".$query.") CNT
		");
		$order = " ORDER BY ID ASC ";

		$result = $db->queryAll($query.$order);

	}else{
		$_where = array();
		array_push($_where, " WHERE	T.ID = C.CODE_TYPE_ID AND	C.HIDDEN = 0");
		if( $search_word ){
			array_push($_where, " ( T.CODE LIKE '%".$search_word."%'  OR  T.NAME LIKE '%".$search_word."%'  OR  C.CODE LIKE '%".$search_word."%'  OR  C.NAME LIKE '%".$search_word."%'   )");
		}
		if( $_POST['code_type_id'] != '' ){
			array_push($_where, " T. ID = ".$_POST['code_type_id']." ");
		}
		$order = "ORDER BY T.ID, C.CODE";
		$query = "
			SELECT	T.CODE AS CODE_TYPE, T.NAME AS CODE_TYPE_NAME,
						C.ID, C.CODE, C.NAME, C.ENAME, C.OTHER, C.REF1, C.REF2, C.REF3, C.REF4, C.REF5, C.USE_YN
			FROM		BC_CODE_TYPE T, BC_CODE C
			".join(' AND ', $_where)."
		";
		$total = $db->queryOne("
			SELECT COUNT(*)
			FROM		(".$query.") CNT
		");

		$result = $db->queryAll($query.$order);
	}

	if( $mode == 'excel' ){
		echo createExcelFile('code_list', $result);
	}else{
		echo json_encode(array(
				'success' => true,
				'total' => $total,
				'data' => $result,
				'query' => $query.$order
		));
	}
}catch (Exception $e){
	echo json_encode(array(
		'success' => false,
		'msg' => $e->getMessage()
	));
}
?>