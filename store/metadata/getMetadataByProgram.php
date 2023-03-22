<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/MetaData.class.php');

if ( empty($_SESSION['user']['user_id']) || $_SESSION['user']['user_id'] == 'temp' ) {
	throw new Exception('재 로그인이 필요합니다');
	exit;
}

$user_id = $_SESSION['user']['user_id'];
$content_id = $_POST['content_id'];

$ud_content_id = $db->queryOne("SELECT UD_CONTENT_ID FROM BC_CONTENT WHERE CONTENT_ID = '$content_id'");

try
{
	// 분류코드쪽 데이터
	$query = "
		SELECT	C.CONTENT_ID
				,C.CATEGORY_ID
				,(SELECT  SYS_CONNECT_BY_PATH(CATEGORY_ID, '/')
				FROM  BC_CATEGORY
				WHERE CATEGORY_ID = C.CATEGORY_ID
				START WITH parent_ID = 0
				CONNECT BY PRIOR CATEGORY_ID = PARENT_ID) AS CATEGORY_PATH
					,M1.C_ID AS k_type1_1--K_TYPE1_1
					,M2.C_ID AS K_TYPE1_2
					,M3.C_ID AS K_TYPE2_1
					,M4.C_ID AS K_TYPE2_2
					,M5.C_ID AS K_TYPE3_1
					,M6.C_ID AS K_TYPE3_2
					,M7.C_ID AS K_TYPE3_3
					,M8.C_ID AS K_TYPE4_1
					,M9.C_ID AS K_TYPE5_1
					,M10.C_ID AS K_TYPE6_1
					,M11.C_ID AS K_TYPE7_1
		FROM		BC_CONTENT C
					LEFT OUTER JOIN (
						SELECT	CID, C_ID
						FROM		TB_CLASSIFICATIONMASTER
						WHERE	C_ID IN (
												SELECT	C_ID
												FROM		TB_CLASSIFICATION
												WHERE	C_PID = (SELECT C_PID FROM TB_CLASSIFICATIONNAME WHERE C_ID = 2)
												START WITH C_PID = (SELECT C_PID FROM TB_CLASSIFICATIONNAME WHERE C_ID = 2)
												CONNECT BY PRIOR C_ID = C_PID
												)
					) M1 ON(M1.CID = C.CONTENT_ID)
					LEFT OUTER JOIN (
						SELECT	CID, C_ID
						FROM		TB_CLASSIFICATIONMASTER
						WHERE	C_ID IN (
												SELECT	C_ID
												FROM		TB_CLASSIFICATION
												WHERE	C_PID != (SELECT C_PID FROM TB_CLASSIFICATIONNAME WHERE C_ID = 2)
												START WITH C_PID = (SELECT C_PID FROM TB_CLASSIFICATIONNAME WHERE C_ID = 2)
												CONNECT BY PRIOR C_ID = C_PID
												)
					) M2 ON(M2.CID = C.CONTENT_ID)
					LEFT OUTER JOIN (
						SELECT	CID, C_ID
						FROM		TB_CLASSIFICATIONMASTER
						WHERE	C_ID IN (
												SELECT	C_ID
												FROM		TB_CLASSIFICATION
												WHERE	C_PID = (SELECT C_PID FROM TB_CLASSIFICATIONNAME WHERE C_ID = 6)
												START WITH C_PID = (SELECT C_PID FROM TB_CLASSIFICATIONNAME WHERE C_ID = 6)
												CONNECT BY PRIOR C_ID = C_PID
												)
					) M3 ON(M3.CID = C.CONTENT_ID)
					LEFT OUTER JOIN (
						SELECT	CID, C_ID
						FROM		TB_CLASSIFICATIONMASTER
						WHERE	C_ID IN (
												SELECT	C_ID
												FROM		TB_CLASSIFICATION
												WHERE	C_PID = (SELECT C_PID FROM TB_CLASSIFICATIONNAME WHERE C_ID = 112)
												START WITH C_PID = (SELECT C_PID FROM TB_CLASSIFICATIONNAME WHERE C_ID = 112)
												CONNECT BY PRIOR C_ID = C_PID
												)
					) M4 ON(M4.CID = C.CONTENT_ID)
					LEFT OUTER JOIN (
						SELECT	CID, C_ID
						FROM		TB_CLASSIFICATIONMASTER
						WHERE	C_ID IN (
												SELECT	C_ID
												FROM		TB_CLASSIFICATION
												WHERE	C_PID = (SELECT C_PID FROM TB_CLASSIFICATIONNAME WHERE C_ID = 189)
												START WITH C_PID = (SELECT C_PID FROM TB_CLASSIFICATIONNAME WHERE C_ID = 189)
												CONNECT BY PRIOR C_ID = C_PID
												)
					) M5 ON(M5.CID = C.CONTENT_ID)
					LEFT OUTER JOIN (
						SELECT	CID, C_ID
						FROM		TB_CLASSIFICATIONMASTER
						WHERE	C_ID IN (
												SELECT	C_ID
												FROM		TB_CLASSIFICATION
												WHERE	C_PID != (SELECT C_PID FROM TB_CLASSIFICATIONNAME WHERE C_ID = 189)
												AND		LEVEL = 2
												START WITH C_PID = (SELECT C_PID FROM TB_CLASSIFICATIONNAME WHERE C_ID = 189)
												CONNECT BY PRIOR C_ID = C_PID
												)
					) M6 ON(M6.CID = C.CONTENT_ID)
					LEFT OUTER JOIN (
						SELECT	CID, C_ID
						FROM		TB_CLASSIFICATIONMASTER
						WHERE	C_ID IN (
												SELECT	C_ID
												FROM		TB_CLASSIFICATION
												WHERE	C_PID != (SELECT C_PID FROM TB_CLASSIFICATIONNAME WHERE C_ID = 189)
												AND		LEVEL = 3
												START WITH C_PID = (SELECT C_PID FROM TB_CLASSIFICATIONNAME WHERE C_ID = 189)
												CONNECT BY PRIOR C_ID = C_PID
												)
					) M7 ON(M7.CID = C.CONTENT_ID)
					LEFT OUTER JOIN (
						SELECT	CID, C_ID
						FROM		TB_CLASSIFICATIONMASTER
						WHERE	C_ID IN (
												SELECT	C_ID
												FROM		TB_CLASSIFICATION
												WHERE	C_PID = (SELECT C_PID FROM TB_CLASSIFICATIONNAME WHERE C_ID = 365)
												START WITH C_PID = (SELECT C_PID FROM TB_CLASSIFICATIONNAME WHERE C_ID = 365)
												CONNECT BY PRIOR C_ID = C_PID
												)
					) M8 ON(M8.CID = C.CONTENT_ID)
					LEFT OUTER JOIN (
						SELECT	CID, C_ID
						FROM		TB_CLASSIFICATIONMASTER
						WHERE	C_ID IN (
												SELECT	C_ID
												FROM		TB_CLASSIFICATION
												WHERE	C_PID = (SELECT C_PID FROM TB_CLASSIFICATIONNAME WHERE C_ID = 376)
												START WITH C_PID = (SELECT C_PID FROM TB_CLASSIFICATIONNAME WHERE C_ID = 376)
												CONNECT BY PRIOR C_ID = C_PID
												)
					) M9 ON(M9.CID = C.CONTENT_ID)
					LEFT OUTER JOIN (
						SELECT	CID, C_ID
						FROM		TB_CLASSIFICATIONMASTER
						WHERE	C_ID IN (
												SELECT	C_ID
												FROM		TB_CLASSIFICATION
												WHERE	C_PID = (SELECT C_PID FROM TB_CLASSIFICATIONNAME WHERE C_ID = 381)
												START WITH C_PID = (SELECT C_PID FROM TB_CLASSIFICATIONNAME WHERE C_ID = 381)
												CONNECT BY PRIOR C_ID = C_PID
												)
					) M10 ON(M10.CID = C.CONTENT_ID)
					LEFT OUTER JOIN (
						SELECT	CID, C_ID
						FROM		TB_CLASSIFICATIONMASTER
						WHERE	C_ID IN (
												SELECT	C_ID
												FROM		TB_CLASSIFICATION
												WHERE	C_PID = (SELECT C_PID FROM TB_CLASSIFICATIONNAME WHERE C_ID = 391)
												START WITH C_PID = (SELECT C_PID FROM TB_CLASSIFICATIONNAME WHERE C_ID = 391)
												CONNECT BY PRIOR C_ID = C_PID
												)
					) M11 ON(M11.CID = C.CONTENT_ID)
		WHERE	C.CONTENT_ID = ".$content_id."
		";
	$data_content = $db->queryRow($query);
	// 사용자 메타 정보를 가져오기 위해 UD_CONTENT_ID로 테이블 명을 조회
	$meta_fields = MetaDataClass::getFieldValueInfo('usr', $ud_content_id, $content_id);
	$data_meta = array();
	foreach($meta_fields as $field) {
		$data_meta[$field['usr_meta_field_id']] = $field['value'];
	}

	echo json_encode(array(
			'success' => true,
			'data' => $data_content,
			'meta_data' => $data_meta
	));

} catch (Exception $e) {

	echo json_encode(array(
			'success' => false,
			'msg' => $e->getMessage()
	));
}
?>
