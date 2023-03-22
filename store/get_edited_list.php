<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lib.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lang.php');

$limit = $_POST['limit']+1;
$start = $_POST['start'];

if(empty($limit)){
    $limit = 21;
}

$mappingDeleteFlag = array(
	0 =>		"<font color=green>".'존재하는 파일'."</font>",
	1 =>		"<font color=red>".'삭제된 파일'."</font>"
);
$total = $db->queryOne("select count(*) from bc_log l, bc_member m
						where l.user_id = m.user_id
						  and action='edit'
						  and content_id=".$_REQUEST['content_id']);
						  
$db->setLimit($limit, $start);
$medias = $db->queryAll("select m.user_nm, l.created_date, l.description, l.log_id
						from bc_log l, bc_member m
						where l.user_id = m.user_id
						  and (action='edit' or action='original_update_renew')
						  and content_id=".$_REQUEST['content_id']."order by l.created_date DESC");

for ($i=0; $i<count($medias); $i++) {
	$description = _text("MN02198");

	if($medias[$i]['description'] == "edit_content_type"){
		// 콘텐츠 유형 변경일 경우 수정 이력 설명에 콘텐츠 유형 변경으로..
        $description = '콘텐츠 유형 변경';
	}

	$medias[$i]['user_nm'] = $medias[$i]['user_nm'];
	$medias[$i]['date'] = $medias[$i]['created_date'];
    $medias[$i]['description'] = $description;
	$medias[$i]['log_id'] = $medias[$i]['log_id'];
}

echo json_encode(array(
	'success' => true,
	'data' => $medias,
	'total' => $total
));
?>