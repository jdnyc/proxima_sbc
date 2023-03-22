<?php
session_start();
require_once '../../lib/config.php';

$start = empty($_POST['start']) ? 0 : $_POST['start'];
$limit = empty($_POST['limit']) ? 10 : $_POST['limit'];
$state = $_POST['state'];
$user_id = $_SESSION['user']['user_id'];

$query = "SELECT COUNT(*) OVER() ROW_COUNT,
                 B.ID, B.CONTENT_ID, B.COMMENTS,
                  TO_CHAR(B.CREATED, 'YYYYMMDDHH24MISS') CREATED,
                  A.TITLE,
                  B.REQUESTER,
                  FUNC_GET_USER_NAME(B.REQUESTER) REQUESTER_NAME,
                  B.ACCEPTER,
                  FUNC_GET_USER_NAME(B.ACCEPTER) ACCEPTER_NAME,
                  CASE B.STATE
                  WHEN " . GRANT_REVIEW_ACCEPT . " THEN '완료'
                  WHEN " . GRANT_REVIEW_REJECT . " THEN '반려'
                  WHEN " . GRANT_REVIEW_REQUEST . " THEN '대기' END STATE
            FROM BC_CONTENT A, REVIEW B
           WHERE B.ID IN (SELECT MAX(ID) FROM REVIEW GROUP BY CONTENT_ID)
             AND A.CONTENT_ID=B.CONTENT_ID";
if ($state == 'result') {
  $query .= " AND B.REQUESTER = '$user_id'";
} else {
  $query .= " --AND A.STATE = '" . GRANT_REVIEW_REQUEST . "'";
}
$query .= " ORDER BY B.ID DESC";
$db->setLimit($limit, $start);

$data = $db->queryAll($query);

$total = 0;
if ( ! empty($data)) {
  $total = $data[0]['row_count'];
}

echo json_encode(array(
    'success' => true,
    'data' => $data,
    'total' => $total
));
