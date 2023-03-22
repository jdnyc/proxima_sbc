<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/workflow/lib/task_manager.php');//2011.12.17 Adding Task Manager Class
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/timecode.class.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/interface.class.php');
session_start();
try{

    // $arr_param_info[0]['target_path'] = $enc_nas_dir.'/KEY_IMAGE_'.$enc_time.'.jpg';
	// 				$arr_param_info[0]['parameter'] = '"6" "'.$key_info_one['start_frame'].'" "JPG" "1280" "720"';
	// 				//"6" "600" "JPG" "1280" "720"
    //                 $task->insert_task_query($content_id, 'enc_key_image_low', 1, $user_id, $arr_param_info);
                    
    $user_id = $_SESSION['user']['user_id'];
	$content_id = $_POST['content_id'];
	$imageData = $_POST['imgBase64'];
    $sec = $_POST['sec'];
    $frames = $_POST['frames'];
    $frame_rate = getFrameRate($content_id);

	$content_info = $db->queryRow("select bs_content_id, ud_content_id from bc_content where content_id=".$content_id);
	$bs_content_id = $content_info['bs_content_id'];
	$ud_content_id = $content_info['ud_content_id'];

	$all = $db->queryAll("select * from bc_media m, bc_scene s where m.content_id={$_POST['content_id']} and m.media_id=s.media_id order by s.start_frame");
	$media = $db->queryRow("select media_id, path from bc_media where content_id=$content_id and media_type='original'");
	$mediaProxy = $db->queryRow("select * from bc_media where content_id=$content_id and media_type='proxy'");
    $media_id = $mediaProxy['media_id'];
	$scene_id = getNextSequence();
    $show_order = ' ';
    
    $beforePath = $all[0]['path'];
    if( !empty($beforePath) && strstr($beforePath , 'Catalog' ) ){
        $beforePathList = explode('/', $beforePath);

        $bfFilename = array_pop($beforePathList);

        $bfFolder = array_pop($beforePathList);

        $midPath = join('/', $beforePathList).'/'.'Thumbnail';
    }else{
        throw new Exception('이전 자료는 생성할 수 없습니다.관리자에게 문의하세요.');
    }

	$path = $midPath.'/'.''.$frames.'.jpg';
	$start_frame = $frames;
	$title = 'Title'.$scene_id;	
	//Get storage info from DB
	$storage_info = $db->queryRow("
		SELECT	* 
		FROM	VIEW_UD_STORAGE
		WHERE	ud_content_id = ".$ud_content_id." 
		and		us_type='lowres'
	");
	//for window path
	//$down_path = $storage_info[path];
	//for linux path
	$down_path = $storage_info['path_for_unix'];
	$count = $db->queryOne("select count(*) from bc_media m, bc_scene s where m.content_id=$content_id and m.media_id=s.media_id and s.START_FRAME=$start_frame");
	if ($count == 0){
		$filesize = 10;

		$insert_data_arr = array(
			'SCENE_ID' => $scene_id,
			'MEDIA_ID' => $media_id,
			'PATH' => $path,
			'START_FRAME' => $start_frame,
			'FILESIZE' => $filesize,
            'TITLE' => $title,
            'scene_type' => 'S',
            'show_order' => $start_frame
		);
        $db->insert('BC_SCENE',$insert_data_arr);
        
        $paramInfo = [];
        $paramInfo[0]['target_path'] = $midPath;
        $paramInfo[0]['parameter'] = '"5" "'.$frames.'" "JPG" "640" "360"';
        //"6" "600" "JPG" "1280" "720"
        //$option=[];
        //$option['change_target_path'] = $path;
       
        $task = new TaskManager($db);
        $task->start_task_workflow($content_id, 'create_catalog_thumb', $user_id, $paramInfo);
		
	} else {
		throw new Exception('중복된 위지 이미지입니다.');
	}
    
    echo json_encode( array(
        'success' => true,
        'msg' => '이미지 추출 요청 되었습니다.<br /> 잠시 후에 확인 해주세요.',
        'result' => $_POST
    ));
	
}
catch(Exception $e){
	die(json_encode(array(
		'success' => false,
		'result' => $insert_data_arr,
		'msg' => $e->getMessage()
	)));
}
	
?>