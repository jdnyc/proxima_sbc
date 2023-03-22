<?PHP
/*
 *  UD CONTENT 유형별로 아카이브 및 리스토어에 대한 조회가 가능하도록 작업관리 페이지를 이용
 * 아카이브 모니터링 페이지 신규 작성
 * 2013.05.30
 * 임찬모
 */
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

$arrStatus = array(
	'처리중' => 'processing',
        '대기중' => 'queue',
	'지난작업 - 전체' => 'all',
	'지난작업 - 성공' => 'complete',
	'지난작업 - 실패' => 'error'
);

$limit = !empty($_POST['limit']) ? $_POST['limit'] : 20;
$start = !empty($_POST['start']) ? $_POST['start'] : 0;
$task_status = $_POST['task_status'];
$ud_content_id = $_POST['ud_content_id'];

if ($task_status == "''")
{
	echo '{"success":"true", "total": 0, "data": []}';
	exit;
}

if ( strstr($task_status, "''") )
{
	$status_query = " ";
}
else
{
	$status_query = " and t.status in ($task_status) ";
}

if( $task_status == 'all' )
{
	$status_query = "";
}
//작업유형이 리랩퍼(31), 아카이브(110), 아카이브삭제(150), 리스토어(160)인 경우만 보여줌
$type_field = " and t.type in  (31, 110, 150, 160) ";

if($ud_content_id == 'all')
{
    $ud_content_query = "";
}
else
{
    $ud_content_query = " and c.ud_content_id = '$ud_content_id'";
}

	$db->setLimit($limit,$start);

	$query = "SELECT T.*, M.FILESIZE, C.CONTENT_ID, C.TITLE, C.REG_USER_ID, C.UD_CONTENT_ID
				FROM 
					BC_TASK T, BC_CONTENT C, BC_MEDIA M
				WHERE 
					T.MEDIA_ID(+)=M.MEDIA_ID
				AND 
					M.CONTENT_ID(+)=C.CONTENT_ID
				$status_query
                                $type_field
                                $ud_content_query
				ORDER BY T.TASK_ID DESC";


	$tasks = $db->queryAll($query);

    $total = $db->queryOne("select count(*) from ($query) cnt ");

$result = '{"success":"true", "total": '.$total.', "data": '.json_encode($tasks)."}";

echo $result;
?>