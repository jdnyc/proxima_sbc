<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

$ud_content_id = $_POST['ud_content_id'];
$content_list = json_decode($_POST['content_list'], true);

try {
	$usr_meta_fields = $db->queryAll("
							SELECT	*
							FROM	BC_USR_META_FIELD
							WHERE	UD_CONTENT_ID = $ud_content_id
							AND		IS_REQUIRED = '1'
							AND		USR_META_FIELD_TYPE != 'container'
						");
	
	$validate_fields = array();
	$titles = array();
	$validated = true;
	
	foreach($usr_meta_fields as $field){
		array_push($validate_fields, $field['usr_meta_field_code']);
	}
	
	foreach ($content_list as $content) {
        $content_id = $content['content_id'];
        
		$tbRequestService = new \Api\Services\TbRequestService($app->getContainer());
		
		$contentStatusCheck = $tbRequestService->contentStatusCheck($content_id);
		if(!empty($contentStatusCheck))
			throw new Exception($contentStatusCheck);

		$archiveRequestCheck = $tbRequestService->archiveRequestCheck($content_id);
		if(!empty($archiveRequestCheck))
            throw new Exception($archiveRequestCheck);
        
        $archiveService = new \Api\Services\ArchiveService($app->getContainer());
        if( !empty($archiveService->isArchived($content_id)) ){           
                throw new Exception('중복 요청입니다.');            
        }
		// 아카이브 여부 확인하는 쿼리 추가
        //STATUS - 1: 승인대기(요청) / 2: 승인 / 3: 반려 / 4: 완료 / 5: 실패
		
		
		    
        // $isExist =   $db->queryOne("SELECT count(*) FROM TB_REQUEST WHERE REQ_TYPE='archive' and REQ_STATUS!=3 AND NPS_CONTENT_ID='$content_id' ");
        // if( $isExist > 0){
        //     throw new Exception('중복 요청입니다.');
        // }

		// $isArchive = $db->queryOne("
		// 				SELECT	STATUS
		// 				FROM	TB_ARCHIVE_REQUEST
		// 				WHERE	STATUS IN ('1','2')
		// 				AND		REQ_TYPE = 'archive'
		// 				AND		NPS_CONTENT_ID = $content_id
		// 			");
	
		// if(!empty($isArchive)) {
		// 	$validated = false;
		// 	$validate_type = 'archive';
		// 	array_push($titles, $content['title']);
		// 	break;
		// }
        
		//메타정보 확인
		$tablename = MetaDataClass::getTableName('usr',$ud_content_id);
		$usr_metas = $db->queryRow("
						SELECT	*
						FROM	".$tablename."
						WHERE	USR_CONTENT_ID = $content_id
					");
        
		foreach($validate_fields as $validate_field){
			$var = $usr_metas[strtolower($validate_field)];
			if(empty($var) && $var !== '0') {
				// $validated = false;
				array_push($titles, $content['title']);
				break;
			}
		}
	}
	
	if(!$validated) {
		switch($validate_type) {
			case 'archive':
				//1: 승인대기(요청) / 2: 승인 / 3: 반려 / 4: 완료 / 5: 실패
				if($isArchive == '1'){
					$msg = '해당 소재는 이미 아카이브 요청 된 소재입니다.<br />'.join('<br />', $titles);
				}else if($isArchive == '2'){
					$msg = '해당 소재는 이미 아카이브가 진행중인 소재입니다.<br />'.join('<br />', $titles);
				}else if($isArchive == '4'){
					$msg = '해당 소재는 이미 아카이브가 완료된 소재입니다.<br />'.join('<br />', $titles);
				}
			break;
			case 'restore':
				$msg = '해당 소재는 리스토어된 소재 입니다. 재 아카이브 금지.<br />'.join('<br />', $titles);
			break;
			default :
				$msg = '입력되지 않은 항목이 있습니다.';
		}
	} else {
		$msg = "성공";
	}
	
	echo json_encode(array(
		'success' => $validated,
		'msg' => $msg
	));
} catch(Exception $e) {
	die(json_encode(array(
			'success' => false,
			'msg' => $e->getMessage()
	)));
}

function getCategoryFullPath($content_id) {
	global $db;

	return $db->queryOne("select category_full_path from bc_content where content_id = " . $content_id);
}
