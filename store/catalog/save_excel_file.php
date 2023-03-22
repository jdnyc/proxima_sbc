<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

$mode = $_REQUEST['mode'];
$content_id = $_REQUEST['content_id'];
$scene_type_list = json_decode($_REQUEST['scene_type_list']);

try{
	/*$_where = array();
	foreach($scene_type_list as $scene_type_item){
			$scene_type = $scene_type_item->scene_type;
			array_push($_where , " s.scene_type='{$scene_type}' ");
		}
	$where = join(' or ' , $_where);
	$result = $db->queryAll("select * from bc_media m, bc_scene s where m.content_id={$content_id} and m.media_id=s.media_id and ({$where}) order by s.start_frame");

	$sub_story_board = $db->queryAll("	SELECT 	s.STORY_BOARD_ID,
												s.TITLE,
												s.CONTENT,
												s.PEOPLES,
												s.COMMENTS,
												m.CONTENT_ID,
												s.MEDIA_ID 
										FROM 	BC_MEDIA m, 
												BC_STORY_BOARD s 
										WHERE 	m.CONTENT_ID={$content_id} 
										AND 	m.MEDIA_ID=s.MEDIA_ID 
										AND 	({$where})
										AND 	IS_DELETED = 'N' 
										ORDER BY s.START_FRAME ASC, s.END_FRAME ASC, s.TITLE ASC
									");
	*/



	$frame_rate = getFrameRate($content_id);

	$content_info = $db->queryRow("select bs_content_id, ud_content_id from bc_content where content_id=".$content_id);
	$bs_content_id = $content_info[bs_content_id];
	$ud_content_id = $content_info[ud_content_id];

	if( in_array( $ud_content_id, $CG_LIST  ) ){
		$alias = '/data/CGLowres/';
	}else{
		$alias = '/data/';
	}

	$_where = array();
	foreach($scene_type_list as $scene_type_item){
			$scene_type = $scene_type_item->scene_type;
			if (trim($scene_type) != ''){
				array_push($_where , " s.scene_type='{$scene_type}' ");
			}
		}
	$where = join(' or ' , $_where);

	$all = $db->queryAll("select * from bc_media m, bc_scene s where m.content_id={$content_id} and m.media_id=s.media_id and ({$where}) order by s.start_frame");

	$data = array();
	if ( !empty($all) )
	{
		$lastcnt = count($all);	
		foreach ( $all as $key => $scene )
		{
			if( ($key + 1) == $lastcnt ){//마지막씬
				$end_frame = $duration_frame;
			}else{
				$end_frame = $all[$key+1]['start_frame'];
			}

			$time_code = frameToTimeCode($scene['start_frame'],$content_id);
			$end_tc = frameToTimeCode($scene['end_frame'],$content_id);
			$current_sec = (int)($scene['start_frame']/$frame_rate);
			$end_sec = (int)($end_frame/$frame_rate);
			if($scene['path'] == $poster_path['path']){
				$is_poster = 1;
			} else {
				$is_poster = 0;
			}

			if($scene['scene_type'] == 'L'){
				$loudness_info = $db->queryRow("	
												SELECT * FROM tb_loudness L, tb_loudness_measurement_log LM
												WHERE L.LOUDNESS_ID = LM.LOUDNESS_ID
												AND L.CONTENT_ID = '".$content_id."'
												AND LM.FRAME_NUM = '".$scene['start_frame']."'
											");
				
			}
			if($scene['scene_type'] == 'Q'){
				$qc_info = $db->queryRow("	
												SELECT * FROM BC_MEDIA_QUALITY Q
												WHERE Q.MEDIA_ID IN (SELECT MEDIA_ID FROM BC_MEDIA WHERE CONTENT_ID='$content_id')
												AND Q.START_TC = ".$scene['start_frame']."
												AND Q.QUALITY_TYPE = 'No audio samples'
											");
				$end_tc = frameToTimeCode($qc_info['end_tc'],$content_id);
			}

			array_push($data, array(
								'scene_id' 		=> $scene['scene_id'],
								'media_id' 		=> $scene['media_id'],
								'url'	 		=> $alias.$scene['path'],
								'is_poster'	 	=> $is_poster,
								'sort'			=> $scene['show_order'],
								'scene_type' 	=> $scene['scene_type'],
								'timecode'		=> $time_code,
								'end_tc'		=> $end_tc,
								'comments'		=> nl2br(str_replace('"', '', $scene['comments'])),
								'current_frame'	=> $scene['start_frame'],
								'current_sec'	=> $current_sec,
								'end_sec'		=> $end_sec,
								'end_frame' 	=> $end_frame,
								'truepeak'		=> $loudness_info['truepeak'],
								'momentary'		=> $loudness_info['momentary'],
								'loudnessrange'	=> $loudness_info['loudnessrange'],
								'integrate'		=> $loudness_info['integrate'],
								'shortterm'		=> $loudness_info['shortterm'], ));
		}

		$sub_story_board = $db->queryAll("	SELECT 	m.CONTENT_ID,
													m.MEDIA_ID,
													s.STORY_BOARD_ID,
													s.START_FRAME,
													s.END_FRAME,
													s.TITLE,
													s.CONTENT,
													s.PEOPLES,
													s.COMMENTS,
													s.XML_PATH
											FROM 	BC_MEDIA m, 
													BC_STORY_BOARD s 
											WHERE 	m.CONTENT_ID={$content_id} 
											AND 	m.MEDIA_ID=s.MEDIA_ID 
											AND 	IS_DELETED = 'N' 
											ORDER BY s.START_FRAME ASC, s.END_FRAME ASC, s.TITLE ASC
										");

		$count_all_story_board = $db->queryOne("SELECT 	count(*)
											FROM 	BC_MEDIA m, 
													BC_STORY_BOARD s 
											WHERE 	m.CONTENT_ID={$content_id} 
											AND 	m.MEDIA_ID=s.MEDIA_ID 
											AND 	IS_DELETED = 'N' ");
		if ( !empty($sub_story_board) ){
			foreach ( $sub_story_board as $index => $story_board ){
				$time_code_start_sec = frameToTimeCode($story_board['start_frame'],$content_id);
				$time_code_end_sec = frameToTimeCode($story_board['end_frame'],$content_id);
				array_push($data, array(
											'content_id' 			=> $story_board['content_id'],
											'media_id' 				=> $story_board['media_id'],
											'story_board_id'		=> $story_board['story_board_id'],
											'title'					=> $story_board['title'],
											'content'				=> $story_board['content'],
											'peoples'				=> $story_board['peoples'],
											'xml_path' 				=> $story_board['xml_path'],
											'start_frame' 			=> $story_board['start_frame'],
											'end_frame' 			=> $story_board['end_frame'],
											'comments'				=> nl2br(str_replace('"', '', $story_board['comments'])),
											'time_code_start_sec'	=> $time_code_start_sec,
											'time_code_end_sec'		=> $time_code_end_sec
											));
				foreach ( $all as $key => $scene )
				{	
					if ($scene['start_frame'] >=  $story_board['start_frame'] && $scene['start_frame'] <=  $story_board['end_frame']){
						if( ($key + 1) == $lastcnt ){//마지막씬
							$end_frame = $duration_frame;
						}else{
							$end_frame = $all[$key+1]['start_frame'];
						}

						$time_code = frameToTimeCode($scene['start_frame'],$content_id);
						$current_sec = (int)($scene['start_frame']/$frame_rate);
						$end_sec = (int)($end_frame/$frame_rate);

						array_push($data, array(
											'scene_id' 	=> $scene['scene_id'],
											'media_id' 	=> $scene['media_id'],
											'url'	 	=> $alias.$scene['path'],
											'sort'		=> $scene['show_order'],
											'scene_type' 	=> $scene['scene_type'],
											'timecode'	=> $time_code,
											'comments'	=> nl2br(str_replace('"', '', $scene['comments'])),
											'current_frame'	=> $scene['start_frame'],
											'current_sec'	=> $current_sec,
											'end_sec'	=> $end_sec,
											'end_frame' => $end_frame,
											'xml_path' => $story_board['xml_path'],
											'is_sub_story_board' => '1' ));
					}
				}
			}
		}
	}




	if( $mode == 'excel' ){
		//filePutContent('SB_Test_download____','SB result', $data);
		echo createExcelFileForStoryBoard('Story_Board', $data);
	}else{
		echo json_encode(array(
				'success' => true,
				'data' => $data
		));
	}
}catch (Exception $e){
	echo json_encode(array(
		'success' => false,
		'msg' => $e->getMessage()
	));
}
?>