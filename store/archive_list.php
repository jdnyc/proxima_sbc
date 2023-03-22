<?php
/**
 * Created by PhpStorm.
 * User: cerori
 * Date: 2015-04-01
 * Time: 오후 4:46
 */
session_start();

require_once "../lib/config.php";

$start = empty($_POST['start']) ? 1 : $_POST['start'];
$limit = empty($_POST['limit']) ? 10 : $_POST['limit'];
$user_id = $_SESSION['user']['user_id'];

try {
	if( DB_TYPE == 'oracle' ){
		$query_rownum = 'AND ROWNUM=1';
	}else{
		$query_rownum = 'OFFSET 1 LIMIT 1';
	}
    $query = "
                SELECT
                        COUNT(*) OVER() ROW_COUNT,
                        A.TITLE, B.COMMENTS, B.REJECT_COMMENTS, B.CATEGORY_FULL_PATH,
                        CASE B.STATUS
                            WHEN 4 THEN '<span style=\"color: red\" ext:qtip=\"' || reject_comments || '\">반려</span>'
                            ELSE
                                CASE (SELECT STATUS FROM BC_TASK WHERE SRC_CONTENT_ID=B.CONTENT_ID AND DESTINATION=UPPER(B.REQUEST_TYPE) ".$query_rownum.")
                                    WHEN 'complete' THEN '<span style=\"color: green\">완료</span>'
                                    WHEN 'processing' THEN '<span style=\"color: darkkhaki\">작업중</span>'
                                    WHEN 'error' THEN '<span style=\"color: red\">실패</span>'
                                    WHEN 'queue' THEN '대기'
                                    ELSE '대기'
                                END
                        END TASK_STATUS,
                        /*
                        CASE B.STATUS
                              WHEN 1 THEN '대기'
                              WHEN 2 THEN '<span style=\"color: green\">작업증</span>'
                              WHEN 3 THEN '<span style=\"color: blue\">완료</span>'
                              WHEN 4 THEN '<span style=\"color: red\" ext:qtip=\"' || reject_comments || '\">반려</span>'
                        END STATUS,
                        */
                        CASE B.REQUEST_TYPE
                              WHEN 'archive' THEN '<span style=\"color: blue\">아카이브</span>'
                              WHEN 'restore' THEN '<span style=\"color: green\">리스토어</span>'
                              ELSE B.REQUEST_TYPE
                        END REQUEST_TYPE,
                        TO_CHAR(B.CREATED, 'YYYYMMDDHH24MISS') CREATED
                  FROM BC_CONTENT A, ARCHIVE_REQUEST B
                  WHERE A.CONTENT_ID=B.CONTENT_ID";
    if (isset($user_id)) {
        $query .= " AND B.REQUEST_USER_ID = '$user_id'";
    }
    $query .= " ORDER BY B.REQUEST_ID DESC";

    $db->setLimit($limit, $start);
} catch (Exception $e) {
    echo $e->getMessage();
}
$total = 0;
$data = $db->queryAll($query);
if ( ! empty($data)) {
    $total = $data[0]['ROW_COUNT'];
}

echo json_encode(array(
    'success' => true,
    'data' => $data,
    'total' => $total
));
