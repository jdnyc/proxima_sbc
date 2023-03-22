<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

try {
    $cuesheet_id = $_REQUEST['cuesheet_id'];
    $cuesheet_type = $_REQUEST['cuesheet_type'];

    if ($cuesheet_type == 'M') {
        $query = "SELECT    B.TITLE,
                            A.CONTENT_ID,
                            A.CONTROL,
                            A.CUESHEET_CONTENT_ID,
                            A.CUESHEET_ID,
                            A.SHOW_ORDER,
                            CASE C.STATUS
                                WHEN 'complete' THEN '성공'
                                WHEN 'queue' THEN '대기'
                                WHEN 'processing' THEN '진행중'
                                WHEN 'error' THEN FUNC_PARSE_TASK_LOG(C.TASK_ID)
                                ELSE COALESCE(C.STATUS, '대기')
                            END STATUS,
                             C.PROGRESS,
                             C.COMPLETE_DATETIME
                        FROM   (
                           SELECT   *
                           FROM   BC_CUESHEET_CONTENT
                           WHERE   CUESHEET_ID='$cuesheet_id'
                           ) A
                           LEFT OUTER JOIN
                           BC_CONTENT B
                           ON(A.CONTENT_ID=B.CONTENT_ID)
                           LEFT OUTER JOIN
                           BC_TASK C
                           ON(A.TASK_ID=C.TASK_ID)
                        ORDER BY A.SHOW_ORDER";
    } else {
        $query = "SELECT A.*,
                        B.SYS_DURATION DURATION,
                        FUNC_PARSE_TASK_LOG(C.TASK_ID)
                 FROM   (
                           SELECT   *
                           FROM   BC_CUESHEET_CONTENT
                           WHERE   CUESHEET_ID='$cuesheet_id'
                           ) A
                           LEFT OUTER JOIN
                           BC_SYSMETA_SOUND B
                           ON(A.CONTENT_ID=B.SYS_CONTENT_ID)
                           LEFT OUTER JOIN
                           BC_TASK C
                           ON(A.TASK_ID=C.TASK_ID)
                        ORDER BY A.SHOW_ORDER";
    }

    $data = $db->queryAll($query);

    echo json_encode(array(
        'success' => true,
        'data' => $data
    ));
} catch (Exception $e) {
    echo json_encode(array(
        'success' => false,
        'msg' => $e->getMessage(),
        'query' => $db->last_query
    ));
}
?>
