<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lib.php');
$mappingDeleteFlag = array(
	0 =>		"<font color=green>".'존재하는 파일'."</font>",
	1 =>		"<font color=red>".'삭제된 파일'."</font>"
);

$allow_type = array(
	'60',
	'20',
	'80'
);

if( DB_TYPE == 'oracle' ){
	$minus = "MINUS";
}else{
	$minus = "EXCEPT";
}
$medias = $db->queryAll("
	select	c.content_id, c.ud_content_id, c.bs_content_id
			,m.delete_date del,m.media_id, m.storage_id
			,m.media_type, m.path, m.filesize
            ,m.created_date
            ,m.status
            ,m.memo
			,m.attach_type
	from	bc_content c, bc_media m
	where	c.content_id=m.content_id
	and		m.content_id='{$_REQUEST['content_id']}'
	--and		(m.status != '1' or m.status is NULL)
	and		m.media_type in (	select	media_type
								from	bc_media
								where	content_id='{$_REQUEST['content_id']}'
								".$minus."
								select	media_type
								from	bc_media
								where	content_id='{$_REQUEST['content_id']}'
								and		media_type ='raw'
								)
	order by m.created_date
");

//$medias = $db->queryAll("select t.status as task_status, t.status as task_progress, c.content_id, c.ud_content_id, c.bs_content_id,c.is_deleted del,m.media_id, m.storage_id, m.media_type, m.path, m.filesize, m.created_date ".
//							" from bc_content c, bc_media m, bc_task t" .
//							" where ".
//							" m.content_id=" . $_REQUEST['content_id'] .
//							" and m.content_id=c.content_id
//							and m.media_id=t.media_id(+)
//							and t.type(+) != '10'
//							and (m.status != 1 or m.status is NULL)
//							order by m.created_date");

//$medias = $db->queryAll('select * from media where content_id='.$_REQUEST['content_id']);
for ($i=0; $i<count($medias); $i++) {
	$media_type = 	$medias[$i]['media_type'];
	
	// 미디어 파일 유형 코드
	$media_type_code_id = $db->queryOne("select id from dd_code_set where code_set_code = 'MEDIA_FILE_TY_CODE'");
	$media_type_code = $db->queryOne("select code_itm_nm from dd_code_item where code_set_id = '{$media_type_code_id}' AND code_itm_code = '{$media_type}'");
 
	// 첨부파일 유형
	if($media_type === 'Attach') {
		$attachType = $medias[$i]['attach_type'];
		if(!empty($attachType)) {
			$attachTypeName = $db->queryOne("SELECT	dci.code_itm_nm
											FROM	DD_CODE_ITEM dci 
											WHERE 	dci.CODE_SET_ID IN (
														SELECT 	ID 
														FROM	DD_CODE_SET dcs 
														WHERE	code_set_code = 'ATCHMNFL_TY'
											)
											AND		code_itm_code = '{$attachType}'
											");
	
			if(!empty($attachTypeName)) {
				$medias[$i]['attach_type'] = $attachTypeName;
			}
		}
	}
	$medias[$i]['media_type_name'] = $media_type_code;

	// 미디어 확장자 
	$extension =explode('.' , $medias[$i]['path']);
    $eventn = $extension[count($extension)-1];
	$medias[$i]['extension'] =  $eventn;
    
	//t.status as task_status, t.status as task_progress,
	// $task_lists = $db->queryAll("select * from bc_task t where t.media_id='{$medias[$i]['media_id']}' order by t.task_id asc");
	// foreach ($task_lists as $list) {
	// 	if ($media_type == 'original') {

	// 		// 150316 임시 나스 스토리지 숨겨지기전까지 임시
	// 		//$medias[$i]['path'] = basename($medias[$i]['path']);
	// 		if ($list['type'] == 60 ||  $list['type'] == 80) {
	// 			$medias[$i]['task_status'] = $list['status'];
	// 			$medias[$i]['task_progress'] = $list['progress'];
	// 		}
	// 	} else if ($media_type == 'proxy') {
	// 		if ($list['type'] == 20) {
	// 			$medias[$i]['task_status'] = $list['status'];
	// 			$medias[$i]['task_progress'] = $list['progress'];
	// 		}
	// 	} else if ($media_type == 'thumb') {
	// 		if ($list['type'] == 11) {
	// 			$medias[$i]['task_status'] = $list['status'];
	// 			$medias[$i]['task_progress'] = $list['progress'];
	// 		}
	// 	}
	// }

    $medias[$i]['filesize'] = formatBytes($medias[$i]['filesize']);
    if( $medias[$i]['status'] == 1 ){
        if(  empty($medias[$i]['del'] )){
            //삭제된거지만 날짜 없는경우 임시
            $medias[$i]['del'] =  date('Y-m-d H:i:s', strtotime("+14 days",strtotime($medias[$i]['created_date'])));
        }
    }
    if(!empty(trim($medias[$i]['del']))) {
        $medias[$i]['del'] = date('Y-m-d H:i:s', strtotime($medias[$i]['del']));
    }
}


$mediaTypes = ['original','thumb','proxy2m1080','proxy2m1080logo','proxy15m1080logo','proxy','proxy360'];
$sortedMedias = [];

foreach($mediaTypes as $mediaType){
	foreach($medias as $key => $media){
        if($media['media_type'] === $mediaType){
			$sortedMedias[] = $media;
            unset($medias[$key]);
			break;
		}
	}
}

$medias = array_merge($sortedMedias,$medias);
// dd(array_merge($sortedMedias, $medias));

// $medias = array_unique(array_merge($sortedMedias,$medias),SORT_REGULAR);



echo json_encode(array(
	'success' => true,
	'data' => $medias
));
?>