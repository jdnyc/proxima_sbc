<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/timecode.class.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/FcpXML.class.php');
session_start();

$list_user_id = $_SESSION['user']['user_id'];
$content_id = $_POST['content_id'];
$is_group = $_POST['is_group'];
$type = $_POST['type'];
$action = $_POST['action'];
$color = $_POST['color'];
$datas = $_POST['datas'];
$title = $_POST['title'];
$comments = $_POST['comments'];
$frame_rate = getFrameRate($content_id);

try
{
	if( empty($list_user_id) || $list_user_id == 'temp' ) throw new Exception("Login 해주세요");
	if( empty($content_id) || empty($type) || empty($action)  ) throw new Exception("Param Error");
	$query_colors = "select ref1 from bc_code where code_type_id  in (select id from bc_code_type ct where ct.code = 'MARK_COLOR_DEFAULT') and use_yn = 'Y'  order by code";
	$color_default = $db->queryAll($query_colors);
	$total_shotlist_query = "		SELECT 	COUNT(*)
								FROM 	BC_SHOT_LIST
								WHERE 	CONTENT_ID = '".$content_id."'
								AND 	type='".$type."'";
	$total_shotlist = $db->queryOne($total_shotlist_query);
	$color_index = $total_shotlist % sizeof($color_default);
	$color_value = $color_default[$color_index]['ref1'];
	$success = true;

	switch($action)
	{
		case 'list':
			if($is_group != 'G') {
				$datas = $db->queryAll("select * from BC_SHOT_LIST where content_id='$content_id' and type='$type' order by start_frame,list_id ");
	
				$paths = $db->queryAll("select TRUNC(start_frame / 29.97) starttc ,path from BC_SCENE where media_id in (select media_id from bc_media where  content_id= '$content_id' ) order by start_frame desc ");
				$pathMap = array();
				foreach($paths as $path)
				{
					$pathMap[$path['starttc']] = $path['path'];
				}
				$pathMap_r = array_reverse($pathMap);
	
				if( !empty($datas) ){
					foreach($datas as $key => $data)
					{
                        if(empty($data['start_frame'])){
                            $datas[$key]['startsec'] = 0;
                        }else{
                            $datas[$key]['startsec'] = ceil($data['start_frame']/$frame_rate * 100000)/100000;
                        }
                        if(empty($data['end_frame'])){
                            $datas[$key]['endsec'] = 0;
                        }else{
                            $datas[$key]['endsec'] = ceil($data['end_frame']/$frame_rate * 100000)/100000;
                        }
                        if(empty($data['in_frame'])){
                            $datas[$key]['insec'] = 0;
                        }else{
                            $datas[$key]['insec'] = ceil($data['in_frame']/$frame_rate * 100000)/100000;
                        }
                        if(empty($data['out_frame'])){
                            $datas[$key]['outsec'] = 0;
                        }else{
                            $datas[$key]['outsec'] = ceil($data['out_frame']/$frame_rate * 100000)/100000;
                        }
                        if(empty($data['duration'])){
                            $datas[$key]['durationsec'] = 0;
                        }else{
                            $datas[$key]['durationsec'] = ceil($data['duration']/$frame_rate * 100000)/100000;
                        }
	
						foreach($pathMap as $pkey => $ppath)
						{
							if( $datas[$key]['outsec'] <= $pkey && $pkey >= $datas[$key]['insec'] ){
								$datas[$key]['path'] = $ppath;
							}
						}
	
						foreach($pathMap as $pkey => $ppath)
						{
							if( empty($datas[$key]['path']) &&  $pkey <= $datas[$key]['insec'] ){
								$datas[$key]['path'] = $ppath;
							}
						}
					}
				}
			} else if ($is_group) {
				$datas = $db->queryAll("select * from BC_SHOT_LIST where parent_content_id='$content_id' and type='$type' order by start_frame,list_id ");
				
				if( !empty($datas) ){
					foreach($datas as $key => $data)
					{
						$paths = $db->queryAll("select TRUNC(start_frame / 29.97) starttc ,path from BC_SCENE where media_id in (select media_id from bc_media where  content_id= '{$data['content_id']}' ) order by start_frame desc ");
						$pathMap = array();
						foreach($paths as $path)
						{
							$pathMap[$path['starttc']] = $path['path'];
						}
						$pathMap_r = array_reverse($pathMap);
						$datas[$key]['startsec'] = ceil($data['start_frame']/$frame_rate * 100000)/100000;
						$datas[$key]['endsec'] = ceil($data['end_frame']/$frame_rate * 100000)/100000;
						$datas[$key]['insec'] = ceil($data['in_frame']/$frame_rate * 100000)/100000;
						$datas[$key]['outsec'] = ceil($data['out_frame']/$frame_rate * 100000)/100000;
						$datas[$key]['durationsec'] = ceil($data['duration']/$frame_rate * 100000)/100000;
				
						foreach($pathMap as $pkey => $ppath)
						{
							if( $datas[$key]['outsec'] <= $pkey && $pkey >= $datas[$key]['insec'] ){
								$datas[$key]['path'] = $ppath;
							}
						}
				
						foreach($pathMap as $pkey => $ppath)
						{
							if( empty($datas[$key]['path']) &&  $pkey <= $datas[$key]['insec'] ){
								$datas[$key]['path'] = $ppath;
							}
						}
					}
				}
			}
		break;

		case 'add':
			$lists = json_decode($datas,true);
			if(!empty($lists)){
				foreach($lists as $list)
				{
					$list['start_frame'] = trim($list['start_frame']);
					$list['end_frame'] = trim($list['end_frame']);
					$list['in_frame'] = trim($list['in_frame']);
					$list['out_frame'] = trim($list['out_frame']);
					$list['duration'] = trim($list['duration']);
					if(empty($list['list_id'])){
						$insert_data = array(
							'list_id'		=>	getSequence('SEQ_RP_LIST_ID'),
							'content_id'	=>	$content_id,
							'type'			=>	$type,
							'start_frame'	=>	$list['start_frame'],
							'end_frame'		=>	$list['end_frame'],
							'in_frame'		=>	$list['in_frame'],
							'out_frame'		=>	$list['out_frame'],
							'duration'		=>	$list['duration'],
							'color'			=>	'#'.$color_value,
							'list_user_id'	=>	$list_user_id
						);
						$query = $db->insert('BC_SHOT_LIST', $insert_data);
						//$list_id = getSequence('SEQ_RP_LIST_ID');
						//$update_q = "insert into BC_SHOT_LIST (LIST_ID,CONTENT_ID,TYPE,STARTTC,ENDTC,INTC,OUTTC,DURATION,note ,list_user_id) values($list_id,$content_id,'$type','$list[starttc]','$list[endtc]','$list[intc]','$list[outtc]','$list[duration]','$list[note]','$list_user_id')";
					}else{
                        $updateData = array(									
							'start_frame'	=>	$list['start_frame'],
							'end_frame'		=>	$list['end_frame'],
							'in_frame'		=>	$list['in_frame'],
							'out_frame'		=>	$list['out_frame'],
							'duration'		=>	$list['duration']
						);
                        $r = $db->update('BC_SHOT_LIST', $updateData, 'list_id='.$list['list_id'] );
						//$update_q = "update BC_SHOT_LIST set START_FRAME='$list[start_frame]',END_FRAME='$list[end_frame]',IN_FRAME='$list[in_frame]',OUT_FRAME='$list[out_frame]',DURATION='$list[duration]' where list_id='$list[list_id]' ";
						//$r = $db->exec($update_q);
					}
				}
				//$result = export($type, $content_id, $is_group);
				$success = true;
                $msg = '저장되었습니다';
                
				
				//searchUpdate($content_id);
				//'저장되었습니다';
			}
		break;
		
		case 'group-add':
			// 저장시에는 기존에 저장된 값을 찾아서 삭제하고 신규로 등록
			$lists = json_decode($datas,true);
			$is_exist = $db->queryOne("select count(*) from BC_SHOT_LIST where parent_content_id='$content_id' and type='$type'");
			
			if($is_exist > 0) {
				$db->exec("delete from BC_SHOT_LIST where parent_content_id = '$content_id'");
			}
			
			if(!empty($lists)){
				foreach($lists as $list) {
					if(empty($list['list_id'])){
						$color = '#'.$color_value;
					} else {
						$color = $list['color'];
					}
					$list['content_id'] = trim($list['content_id']);
					$list['start_frame'] = trim($list['start_frame']);
					$list['end_frame'] = trim($list['end_frame']);
					$list['in_frame'] = trim($list['in_frame']);
					$list['out_frame'] = trim($list['out_frame']);
					$list['duration'] = trim($list['duration']);
					$list_id = getSequence('SEQ_RP_LIST_ID');
					$update_q = "insert into BC_SHOT_LIST (LIST_ID,CONTENT_ID,TYPE,START_FRAME,END_FRAME,IN_FRAME,OUT_FRAME,DURATION ,list_user_id,PARENT_CONTENT_ID, COLOR, COMMENTS, TITLE) 
										values('$list_id','$list[content_id]','$type','$list[start_frame]','$list[end_frame]','$list[in_frame]','$list[out_frame]','$list[duration]','$list_user_id', '$content_id', '$color','$list[comments]','$list[title]')";
					$r = $db->exec($update_q);
				}
				//$result = export($type, $content_id, $is_group);
				$success = $result['success'];
                $msg = $result ['msg'];
                $success = true;
                $msg = '저장되었습니다';
				//searchUpdate($content_id);
				//$msg = "저장되었습니다";
			}
		break;

		case 'export':
			$datas = $db->queryAll("select * from BC_SHOT_LIST where content_id='$content_id' and type='$type' order by start_frame,list_id ");

			$content_info = $db->queryRow("
				SELECT	M.STORAGE_ID, M.STATUS, C.CONTENT_ID,C.UD_CONTENT_ID, C.TITLE,M.PATH, S.*
				FROM		BC_MEDIA M,
							VIEW_BC_CONTENT C
								LEFT JOIN	BC_SYSMETA_MOVIE S
								ON				S.SYS_CONTENT_ID = C.CONTENT_ID
				WHERE	C.CONTENT_ID = ".$content_id."
				AND		M.CONTENT_ID = C.CONTENT_ID
				AND		M.MEDIA_TYPE = 'proxy'
				AND		COALESCE(M.STATUS , '0') = '0'
			");
			$ud_content_id = $content_info['ud_content_id'];

			$us_type = 'lowres';
			$us_type_file = 'highres';
			

			$storage_info_file = $db->queryRow("
				SELECT	M.PATH as file_path,  S.sys_video_rt, st.path as storage_path
				FROM	BC_MEDIA M, VIEW_UD_STORAGE ST,
						VIEW_BC_CONTENT C
							LEFT JOIN	BC_SYSMETA_MOVIE S
							ON				S.SYS_CONTENT_ID = C.CONTENT_ID
				WHERE	C.CONTENT_ID = ".$content_id."
				AND		ST.UD_CONTENT_ID = C.UD_CONTENT_ID
				AND		ST.US_TYPE = '".$us_type_file."'
				AND		M.CONTENT_ID = C.CONTENT_ID
				AND		M.MEDIA_TYPE = 'original'
				AND		COALESCE(M.STATUS , '0') = '0'
			");

			$storage_info = $db->queryRow("
				SELECT	* 
				FROM	VIEW_UD_STORAGE
				WHERE	ud_content_id = ".$ud_content_id." 
				and		us_type='".$us_type."'
			");
			//$start_timecode =  $db->queryOne("SELECT SYS_START_TIMECODE FROM BC_SYSMETA_MOVIE WHERE sys_content_id = '$content_id'");

			if($type == 'preview'){
				$xml_title = $content_info['title'].'_'.$type;
			}else{
				$xml_title = $content_info['title'];
			}

			$fcp = new FcpXML();
			$fcp->setTitle( $xml_title );
			$fcp->setAudioChannel(8);
			$fcp->setResolution(1920, 1080);
			if( empty($start_timecode) ){
				$start_timecode = '00:00:00;00';
			}

			$file_path = $storage_info_file['storage_path'].'/'.$storage_info_file['file_path']; 
			$duration = $storage_info_file['sys_video_rt'];
			foreach($datas as $data)
			{
				$start_frame = timecode::getConvFrameToTimecode($data['start_frame'],$frame_rate);
				$end_frame = timecode::getConvFrameToTimecode($data['end_frame'],$frame_rate);
				$in_frame =timecode::getConvFrameToTimecode( $data['in_frame'],$frame_rate);
				$out_frame = timecode::getConvFrameToTimecode($data['out_frame'],$frame_rate);
				//($filepath, $start_tc, $duration , $in , $out, $start, $end )
				$fcp->addTLInfo($file_path, $frame_rate, $start_timecode,$duration, $in_frame ,$out_frame, $start_frame, $end_frame );
			}
			$xml = $fcp->createFcpXML();

			$path_array = explode('/', $content_info['path'] );
			array_pop($path_array);
			array_pop($path_array);

			//확장자 뺀 파일명
			$target_path = join('/',$path_array);

			if($type == 'preview'){
				$target_path = $target_path.'_'.$type;
			}
			$target_path = $target_path.'/'.$content_info['content_id'].'.'.'xml';

			$down_path = $storage_info['path'].'/'.$target_path;
			//$down_path_array = explode('/', $down_path );
			//if( $down_path_array[0] == 'Z:'){
				//$down_path_array[0] = 'D:/Storage';
			//}
			//$down_path = join('/', $down_path_array);

			//echo $xml->asXML();

			$fcp->_LogFile('',$down_path, $xml->asXML() );
			$result = $fcp->_PrintFile( $down_path , $xml->asXML() );


			if($result){
				$filesize_xml = filesize($down_path);
				$check_media = $db->queryAll("
					SELECT	*
					FROM	BC_MEDIA
					WHERE	CONTENT_ID = ".$content_info['content_id']."
					AND		MEDIA_TYPE = 'shot_list'
				");

				if( count($check_media) > 0 ){
					$query_update = "
						UPDATE	BC_MEDIA	SET
							FILESIZE = ".$filesize_xml."
						WHERE	CONTENT_ID = ".$content_info['content_id']."
						AND		MEDIA_TYPE = 'shot_list'
					";
					$db->exec($query_update);
				}else{
					$db->insert('BC_MEDIA', array(
						'CONTENT_ID' => $content_info['content_id'],
						'STORAGE_ID' =>	$content_info['storage_id'],
						'CREATED_DATE' => date('YmdHis'),
						'FILESIZE'	=>	$filesize_xml,
						'PATH' => $target_path,
						'MEDIA_TYPE' => 'shot_list',
						'STATUS' => $content_info['status'],
						'REG_TYPE' => 'shot_list',
						'EXPIRED_DATE' => '99981231000000'
					));
				}
				$msg = 'success';
			}else{
				$msg = _text('MSG00167');
			}

		break;
		
		case 'group-export':
			// 기존값은 지우고 신규로 등록
			$datas = $db->queryAll("select * from BC_SHOT_LIST where parent_content_id='$content_id' and type='$type' order by start_frame,list_id ");

			$content_info = $db->queryRow("
				SELECT	C.CONTENT_ID,C.UD_CONTENT_ID, C.TITLE,M.PATH, S.*
				FROM		BC_MEDIA M,
							VIEW_BC_CONTENT C
								LEFT JOIN	BC_SYSMETA_MOVIE S
								ON				S.SYS_CONTENT_ID = C.CONTENT_ID
				WHERE	C.CONTENT_ID = ".$content_id."
				AND		M.CONTENT_ID = C.CONTENT_ID
				AND		M.MEDIA_TYPE = 'proxy'
				AND		COALESCE(M.STATUS , '0') = '0'
			");

			//$content_info = $db->queryRow("select c.content_id,c.ud_content_id, c.title, c.ud_system_code,m.path,s.* from view_bc_content c,bc_media m,bc_sysmeta_movie s where c.content_id='$content_id' and c.content_id=m.content_id and c.content_id=s.sys_content_id(+) and m.media_type='original' and NVL(m.status,0)=0");
			$ud_content_id = $content_info['ud_content_id'];

			$us_type = 'lowres';
			
		
			$storage_info = $db->queryRow("SELECT * FROM VIEW_UD_STORAGE WHERE ud_content_id = $ud_content_id and us_type='$us_type'");
			//$start_timecode =  $db->queryOne("SELECT SYS_START_TIMECODE FROM BC_SYSMETA_MOVIE WHERE sys_content_id = '$content_id'");
		
			if($type == 'preview'){
				$xml_title = $content_info['title'].'_'.$type;
			}else{
				$xml_title = $content_info['title'];
			}
		
			$fcp = new FcpXML();
			$fcp->setTitle( $xml_title );
			$fcp->setAudioChannel(8);
			$fcp->setResolution(1920, 1080);
			if( empty($start_timecode) ){
				$start_timecode = '00:00:00;00';
			}
		
			foreach($datas as $data) {
				$child_content_id = $data['content_id'];
				$child_content_info = $db->queryRow("select c.content_id,c.ud_content_id, c.title, m.path,s.* from view_bc_content c,bc_media m,bc_sysmeta_movie s where c.content_id='$child_content_id' and c.content_id=m.content_id and c.content_id=s.sys_content_id(+) and m.media_type='original' and NVL(m.status,0)=0");
				 
				$file_path = $storage_info['path'].'/'.$child_content_info['path'];
				$duration = $child_content_info['sys_video_rt'];
				
				$start_frame = timecode::getConvFrameToTimecode($data['start_frame'],$frame_rate);
				$end_frame = timecode::getConvFrameToTimecode($data['end_frame'],$frame_rate);
				$in_frame =timecode::getConvFrameToTimecode( $data['in_frame'],$frame_rate);
				$out_frame = timecode::getConvFrameToTimecode($data['out_frame'],$frame_rate);
				//($filepath, $start_tc, $duration , $in , $out, $start, $end )
				$fcp->addTLInfo($file_path, $frame_rate, $start_timecode,$duration, $in_frame ,$out_frame, $start_frame, $end_frame );
			}
			$xml = $fcp->createFcpGroupXML();

			$path_array = explode('/', $content_info['path'] );
			array_pop($path_array);
			array_pop($path_array);

			//확장자 뺀 파일명
			$target_path = join('/',$path_array);
		
			$target_path = $target_path.'/'.$content_info['content_id'].'.'.'xml';
			$down_path = $storage_info['path'].'/'.$target_path;
			//$down_path_array = explode('/', $down_path );
			//if( $down_path_array[0] == 'Z:'){
				//$down_path_array[0] = 'D:/Storage';
			//}
			//$down_path = join('/', $down_path_array);
			//echo $xml->asXML();
			$fcp->_LogFile('',$down_path, $xml->asXML() );
			$fcp->_PrintFile( $down_path , $xml->asXML() );
		
			$msg = "EDL이 생성되었습니다";
			break;

		case 'del':
			$lists = json_decode($datas,true);
			if(!empty($lists)){
				foreach($lists as $list)
				{
					$update_q = "delete from BC_SHOT_LIST where list_id='$list[list_id]' ";
					$r = $db->exec($update_q);
				}
				//$result = export($type, $content_id, $is_group);

				$frame_rate = getFrameRate($content_id);
				if($is_group != 'G') {
					$datas = $db->queryAll("select * from BC_SHOT_LIST where content_id='$content_id' and type='$type' order by start_frame,list_id ");
		
					if( !empty($datas) ){
						$temp_duration = 0;
						foreach($datas as $key => $data)
						{
							$start_frame = $temp_duration;
							$duration = empty($data['duration']) ? 0:$data['duration'];
							$end_frame = ($duration+ $temp_duration);
							$temp_duration += $duration;
							$update_q = "update BC_SHOT_LIST set START_FRAME='$start_frame',END_FRAME='$end_frame' where list_id='$data[list_id]' ";
							$r = $db->exec($update_q);
						}
					}
				} else if ($is_group) {
					$datas = $db->queryAll("select * from BC_SHOT_LIST where parent_content_id='$content_id' and type='$type'  order by start_frame,list_id ");
					
					if( !empty($datas) ){
						$temp_duration = 0;
						foreach($datas as $key => $data)
						{
							$start_frame = $temp_duration;
							$duration = $data['duration'];
							$end_frame = ($duration+ $temp_duration);
							$temp_duration = $duration;
							$update_q = "update BC_SHOT_LIST set START_FRAME='$start_frame',END_FRAME='$end_frame' where list_id='$data[list_id]' ";
							$r = $db->exec($update_q);
						}
					}
				}
				$success = $result['success'];
                $msg = $result ['msg'];
                $success = true;
                $msg = '저장되었습니다';

				//searchUpdate($content_id);
			}
		break;

		case 'edit':
			$lists = json_decode($datas,true);
			foreach($lists as $list){
                
				$fieldKey = array();
				$fieldValue = array();
				array_push($fieldKey, 'title' );
				array_push($fieldValue, $title);
				array_push($fieldKey, 'comments' );
				array_push($fieldValue, $comments);
				array_push($fieldKey, 'list_user_id' );
				array_push($fieldValue, $list_user_id);
				array_push($fieldKey, 'color' );
				array_push($fieldValue, $color);

				array_push($fieldKey, 'start_frame' );
				array_push($fieldValue,$list['start_frame']);
				array_push($fieldKey, 'end_frame' );
				array_push($fieldValue, $list['end_frame']);
				array_push($fieldKey, 'in_frame' );
				array_push($fieldValue, $list['in_frame']);
				array_push($fieldKey, 'out_frame' );
				array_push($fieldValue, $list['out_frame']);
				array_push($fieldKey, 'duration' );
				array_push($fieldValue, $list['duration']);
				$query = $db->UpdateQuery('BC_SHOT_LIST' , $fieldKey , $fieldValue, "LIST_ID='$list[list_id]'" );
				$r = $db->exec($query);
			}
			//$result = export($type, $content_id, $is_group);
            $success = true;
            $msg = '저장되었습니다';

		break;

		default:
			throw new Exception("Type Error");
		break;

	}

	echo json_encode(array(
		'success' => $success,
		'msg' => $msg,
		'data' => $datas,
		'query' => $query
	));
}
catch (Exception $e)
{
	echo json_encode(array(
		'success' => false,
		'msg' => $e->getMessage()
	));
}

function download(){

	$filepath = './hello_world.txt';
	$filesize = filesize($filepath);
	$path_parts = pathinfo($filepath);
	$filename = $path_parts['basename'];
	$extension = $path_parts['extension'];

	header("Pragma: public");
	header("Expires: 0");
	header("Content-Type: application/octet-stream");
	header("Content-Disposition: attachment; filename=\"$filename\"");
	header("Content-Transfer-Encoding: binary");
	header("Content-Length: $filesize");

	ob_clean();
	flush();
	readfile($filepath);
}

function export($type, $content_id, $is_group){
	global $db;
	if($is_group == ''){
		$content = $db->queryRow("select c.ud_content_id, c.title, c.bs_content_id, c.is_group, m.ud_content_title as meta_type_name, c.reg_user_id from bc_content c, bc_ud_content m where c.content_id={$content_id} and c.ud_content_id=m.ud_content_id");
		$is_group = $content['is_group'];
	}
	$frame_rate = getFrameRate($content_id);
	if($is_group != 'G') {
		$datas = $db->queryAll("
			select * 
			from BC_SHOT_LIST 
			where content_id=".$content_id." 
			and type='".$type."' 
			order by start_frame,list_id 
		");
	} else if ($is_group){
		$datas = $db->queryAll("
			select * 
			from BC_SHOT_LIST 
			where parent_content_id=".$content_id." 
			and type='".$type."' 
			order by start_frame,list_id ");
	}

	$content_info = $db->queryRow("
		SELECT	M.STORAGE_ID, M.STATUS, C.CONTENT_ID,C.UD_CONTENT_ID, C.TITLE,M.PATH, S.*
		FROM		BC_MEDIA M,
					VIEW_BC_CONTENT C
						LEFT JOIN	BC_SYSMETA_MOVIE S
						ON				S.SYS_CONTENT_ID = C.CONTENT_ID
		WHERE	C.CONTENT_ID = ".$content_id."
		AND		M.CONTENT_ID = C.CONTENT_ID
		AND		M.MEDIA_TYPE = 'proxy'
		AND		COALESCE(M.STATUS , '0') = '0'
	");
	$ud_content_id = $content_info['ud_content_id'];

	$us_type = 'lowres';
	$us_type_file = 'highres';
	

	$storage_info_file = $db->queryRow("
		SELECT	M.PATH as file_path,  S.sys_video_rt, st.path as storage_path
		FROM	BC_MEDIA M, VIEW_UD_STORAGE ST,
				VIEW_BC_CONTENT C
					LEFT JOIN	BC_SYSMETA_MOVIE S
					ON				S.SYS_CONTENT_ID = C.CONTENT_ID
		WHERE	C.CONTENT_ID = ".$content_id."
		AND		ST.UD_CONTENT_ID = C.UD_CONTENT_ID
		AND		ST.US_TYPE = '".$us_type_file."'
		AND		M.CONTENT_ID = C.CONTENT_ID
		AND		M.MEDIA_TYPE = 'original'
		AND		COALESCE(M.STATUS , '0') = '0'
	");

	$storage_info = $db->queryRow("
		SELECT	* 
		FROM	VIEW_UD_STORAGE
		WHERE	ud_content_id = ".$ud_content_id." 
		and		us_type='".$us_type."'
	");
	//$start_timecode =  $db->queryOne("SELECT SYS_START_TIMECODE FROM BC_SYSMETA_MOVIE WHERE sys_content_id = '$content_id'");

	if($type == 'preview'){
		$xml_title = $content_info['title'].'_'.$type;
	}else{
		$xml_title = $content_info['title'];
	}

	$fcp = new FcpXML();
	$fcp->setTitle( $xml_title );
	$fcp->setAudioChannel(8);
	$fcp->setResolution(1920, 1080);
	if( empty($start_timecode) ){
		$start_timecode = '00:00:00;00';
	}

	$file_path = $storage_info_file['storage_path'].'/'.$storage_info_file['file_path']; 
	$duration = $storage_info_file['sys_video_rt'];
	if($is_group != 'G') {
		foreach($datas as $data)
		{
			$start_frame = timecode::getConvFrameToTimecode($data['start_frame'],$frame_rate);
			$end_frame = timecode::getConvFrameToTimecode($data['end_frame'],$frame_rate);
			$in_frame =timecode::getConvFrameToTimecode( $data['in_frame'],$frame_rate);
			$out_frame = timecode::getConvFrameToTimecode($data['out_frame'],$frame_rate);
			//($filepath, $start_tc, $duration , $in , $out, $start, $end )
			$fcp->addTLInfo($file_path, $frame_rate, $start_timecode,$duration, $in_frame ,$out_frame, $start_frame, $end_frame );
		}
		$xml = $fcp->createFcpXML();
	} else if ($is_group){
		foreach($datas as $data)
		{
			$start_frame = timecode::getConvFrameToTimecode($data['start_frame'],$frame_rate);
			$end_frame = timecode::getConvFrameToTimecode($data['end_frame'],$frame_rate);
			$in_frame =timecode::getConvFrameToTimecode( $data['in_frame'],$frame_rate);
			$out_frame = timecode::getConvFrameToTimecode($data['out_frame'],$frame_rate);
			//($filepath, $start_tc, $duration , $in , $out, $start, $end )
			$storage_info_child_file = $db->queryRow("
				SELECT	M.PATH as file_path,  S.sys_video_rt, st.path as storage_path
				FROM	BC_MEDIA M, VIEW_UD_STORAGE ST,
						VIEW_BC_CONTENT C
							LEFT JOIN	BC_SYSMETA_MOVIE S
							ON				S.SYS_CONTENT_ID = C.CONTENT_ID
				WHERE	C.CONTENT_ID = ".$data['content_id']."
				AND		ST.UD_CONTENT_ID = C.UD_CONTENT_ID
				AND		ST.US_TYPE = '".$us_type_file."'
				AND		M.CONTENT_ID = C.CONTENT_ID
				AND		M.MEDIA_TYPE = 'original'
				AND		COALESCE(M.STATUS , '0') = '0'
			");
			$file_path_child = $storage_info_child_file['storage_path'].'/'.$storage_info_child_file['file_path']; 
			$duration_child = $storage_info_child_file['sys_video_rt'];
			$fcp->addGroupTLInfo($file_path_child, $data['content_id'], $frame_rate, $start_timecode, $duration_child , $in_frame ,$out_frame, $start_frame, $end_frame );
		}
		$xml = $fcp->createFcpGroupXML();
	}
	

	$path_array = explode('/', $content_info['path'] );
	array_pop($path_array);
	array_pop($path_array);

	//확장자 뺀 파일명
	$target_path = join('/',$path_array);

	if($type == 'preview'){
		$target_path = $target_path.'_'.$type;
	}

	$target_path = $target_path.'/'.$content_info['content_id'].'.'.'xml';
    $target_path_edius = join('/',$path_array);
    $target_path_edius = $target_path_edius.'/'.$content_info['content_id'].'.'.'xml';
    $target_path_edius_csv = join('/',$path_array);
	$target_path_edius_csv = $target_path_edius_csv.'/'.$content_info['content_id'].'.'.'csv';

	if(SERVER_TYPE == 'windows'){
		//for window path
		$down_path = $storage_info['path'].'/'.$target_path;
		$down_path_edius = $storage_info['path'].'/'.$target_path_edius;
		$down_path_edius_csv = $storage_info['path'].'/'.$target_path_edius_csv;
	} else {
		//for linux path
		$down_path = $storage_info['path_for_unix'].'/'.$target_path;
		$down_path_edius = $storage_info['path_for_unix'].'/'.$target_path_edius;
		$down_path_edius_csv = $storage_info['path_for_unix'].'/'.$target_path_edius_csv;
    }
    //임시 테스트
	$down_path  =$_SERVER['DOCUMENT_ROOT'].'/log/'.$content_info['content_id'].'.'.'xml';
	$fcp->_LogFile('',$down_path, $xml->asXML() );
	$result = $fcp->_PrintFile( $down_path , $xml->asXML() );


	if($result ){
		$filesize_xml = filesize($down_path);
		$check_media = $db->queryAll("
			SELECT	*
			FROM	BC_MEDIA
			WHERE	CONTENT_ID = ".$content_info['content_id']."
			AND		MEDIA_TYPE = 'shot_list'
		");

		if( count($check_media) > 0 ){
			$query_update = "
				UPDATE	BC_MEDIA	SET
					FILESIZE = ".$filesize_xml."
				WHERE	CONTENT_ID = ".$content_info['content_id']."
				AND		MEDIA_TYPE = 'shot_list'
			";
			$db->exec($query_update);
		}else{
			$db->insert('BC_MEDIA', array(
				'CONTENT_ID' => $content_info['content_id'],
				'STORAGE_ID' =>	$content_info['storage_id'],
				'CREATED_DATE' => date('YmdHis'),
				'FILESIZE'	=>	$filesize_xml,
				'PATH' => $target_path,
				'MEDIA_TYPE' => 'shot_list',
				'STATUS' => $content_info['status'],
				'REG_TYPE' => 'shot_list',
				'EXPIRED_DATE' => '99981231000000'
			));
		}

		$msg = 'success';
		$success = true;
	}else{
		$msg = _text('MSG00167');
		$success = false;
	}

	$result =  array(
		'success' => $success,
		'msg' => $msg
	);

	return $result;
}
?>