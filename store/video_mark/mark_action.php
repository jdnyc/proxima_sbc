<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/timecode.class.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/FcpXML.class.php');
fn_checkAuthPermission($_SESSION);

$content_id = $_POST['content_id'];
$parent_content_id = $_POST['parent_content_id'];
$type = $_POST['type'];
$action = $_POST['action'];
$start_frame = $_POST['start_frame'];
$end_frame = $_POST['end_frame'];
$title = $_POST['title'];
$comments = $_POST['comments'];
$color = $_POST['color'];
$mark_id = $_POST['mark_id'];
$mark_type = $_POST['mark_type'];
$mark_type_list = json_decode($_POST['mark_type_list']);
$user_id = $_SESSION['user']['user_id'];
$cur_datetime = date('YmdHis');
$frame_rate = getFrameRate($content_id);

$content = $db->queryRow("select c.ud_content_id, c.title, c.bs_content_id, c.is_group, m.ud_content_title as meta_type_name, c.reg_user_id from bc_content c, bc_ud_content m where c.content_id={$content_id} and c.ud_content_id=m.ud_content_id");
$is_group = $content['is_group'];

$_where = array();


try
{

	$success = true;
	$datas = array();
	$query_colors = "select ref1 from bc_code where code_type_id  in (select id from bc_code_type ct where ct.code = 'MARK_COLOR_DEFAULT') and use_yn = 'Y'  order by code";
	$color_default = $db->queryAll($query_colors);
	$total_marker_query = "		SELECT 	COUNT(*)
								FROM 	BC_MARK
								WHERE 	CONTENT_ID = '".$content_id."'
								AND 	STATUS = 'CREATE'";
	$total_marker = $db->queryOne($total_marker_query);
	$color_index = $total_marker % sizeof($color_default);
	$color_value = $color_default[$color_index]['ref1'];
	switch($action)
	{
		case 'add':
			$media_id = $db->queryOne("select media_id from bc_media where content_id = $content_id and media_type = 'original'");
			if ($mark_type == 'MARK_IN' || $mark_type == 'MARK_OUT'){
				$has_child = $mdb->queryOne("SELECT COUNT(*) FROM BC_MARK WHERE CONTENT_ID=$content_id AND MARK_TYPE = '$mark_type'");
				if( $has_child > 0 ){
					$fieldKey = array();
					$fieldValue = array();
					array_push($fieldKey, 'status' );
					array_push($fieldValue, 'DELETE');
					array_push($fieldKey, 'updated_datetime' );
					array_push($fieldValue, $cur_datetime);
					array_push($fieldKey, 'updated_user_id' );
					array_push($fieldValue, $user_id);
					$where = "CONTENT_ID=$content_id AND MARK_TYPE = '$mark_type'";
					$query = $db->UpdateQuery('BC_MARK' , $fieldKey , $fieldValue, $where );
					$r = $db->exec($query);
				}
			}
			$mark_id = getSequence('SEQ_BC_MARK_ID');
			$insert_data = array(
				'mark_id'					=>	$mark_id,
				'mark_type'					=>	$mark_type,
				'content_id'				=>	$content_id,
				'parent_content_id'			=>	$parent_content_id,
				'media_id'					=>	$media_id,
				'start_frame'				=>	$start_frame,
				'end_frame'					=>	$end_frame,
				'color'						=>	'#'.$color_value,
				'status'					=>	'CREATE',
				'created_datetime'			=>	$cur_datetime,
				'created_user_id'			=>	$user_id,
			);
			$query = $db->insert('BC_MARK', $insert_data);
			// $result = export('MARK', $content_id, $is_group);
			// $success = $result['success'];
			// $msg = $result ['msg'];
			$success = true;
			$msg = 'success';

			searchUpdate($content_id);
		break;

        case 'get':
            if ($mark_type_list != null){
                foreach($mark_type_list as $key => $mark_type_value){
                    array_push($_where , "M.mark_type='{$mark_type_value}' ");
                }
                $where_mark_type_list = join(' or ' , $_where);
                if ($where_mark_type_list != null){
                    $where_mark_type_list = 'and ('.$where_mark_type_list.')';
                }
            }

            $content_id_q = " M.CONTENT_ID = '".$content_id."' ";
			if ($is_group) {
                $content_id_q = " M.PARENT_CONTENT_ID = '".$content_id."' ";
            }
            
            $query = "
                SELECT 	B.user_nm as created_user_nm, M.*
                FROM 	BC_MARK M
                    left outer join bc_member B on M.created_user_id=b.user_id
                WHERE 	".$content_id_q."
                AND 	M.STATUS = 'CREATE'
                ".$where_mark_type_list."
                ORDER BY M.START_FRAME ASC
            ";
			$result = $db->queryAll($query);
			foreach ( $result as $key => $mark ){
				array_push($datas, array(
							'mark_id' 			=> $mark['mark_id'],
							'mark_type' 		=> $mark['mark_type'],
							'content_id' 		=> $mark['content_id'],
                            'media_id' 			=> $mark['media_id'],
                            'created_user_id' 	=> $mark['created_user_id'],
                            'created_user_nm' 	=> $mark['created_user_nm'],
							'start_frame' 		=> $mark['start_frame'],
							'end_frame' 		=> $mark['end_frame'],
							'title' 			=> $mark['title'],
							'comments' 			=> $mark['comments'],
							'color' 			=> $mark['color'],
							'status' 			=> $mark['status']
							 ));
			}
		break;

		case 'del':
			$fieldKey = array();
			$fieldValue = array();
			array_push($fieldKey, 'status' );
			array_push($fieldValue, 'DELETE');
			array_push($fieldKey, 'updated_datetime' );
			array_push($fieldValue, $cur_datetime);
			array_push($fieldKey, 'updated_user_id' );
			array_push($fieldValue, $user_id);
			if ($mark_id){
				$where = "MARK_ID = $mark_id";
			} else {
				$where = "CONTENT_ID=$content_id";
			}
			$query = $db->UpdateQuery('BC_MARK' , $fieldKey , $fieldValue, $where );
			$r = $db->exec($query);

			searchUpdate($content_id);
		break;

		case 'edit':
		
			$fieldKey = array();
			$fieldValue = array();
			array_push($fieldKey, 'title' );
			array_push($fieldValue, $title);
			array_push($fieldKey, 'comments' );
			array_push($fieldValue, $comments);
			array_push($fieldKey, 'updated_datetime' );
			array_push($fieldValue, $cur_datetime);
			array_push($fieldKey, 'updated_user_id' );
			array_push($fieldValue, $user_id);
			array_push($fieldKey, 'color' );
			array_push($fieldValue, $color);
			$query = $db->UpdateQuery('BC_MARK' , $fieldKey , $fieldValue, "MARK_ID = $mark_id" );
			$r = $db->exec($query);
			$result = export('MARK', $content_id, $is_group);
			$success = $result['success'];
			$msg = $result ['msg'];

			searchUpdate($content_id);
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

function createCsvMarkerList($fileName, $array) {
	$time = date('YmdHis');
	$file = fopen($fileName,"w");

	$headers = array('"# EDIUS Marker list"');
	$headers = str_replace('"', '', $headers);
	fputs($file, implode($headers, ',')."\n");
	$headers = array('"# Format Version 3"');
	$headers = str_replace('"', '', $headers);
	fputs($file, implode($headers, ',')."\n");
	$headers = array('"# No"', 'Anchor', 'Position', 'Duration', 'Comment');
	$headers = str_replace('"', '', $headers);
	fputs($file, implode($headers, ',')."\n");

	foreach ($array as $line){
		fputs($file, implode($line, ',')."\n");
	}
	fclose($file);
}

function export($type, $content_id, $is_group){
	global $db;
	$frame_rate = getFrameRate($content_id);
	if($is_group != 'G' && $is_group != 'C') {
		$parent_content_id = $content_id;
		
	} else if ($is_group){
		$parent_content_id = $db->queryOne("
			SELECT 	PARENT_CONTENT_ID
			FROM 	BC_MARK
			WHERE 	CONTENT_ID = ".$content_id);
	}

	$content_info = $db->queryRow("
		SELECT	M.STORAGE_ID, M.STATUS, C.CONTENT_ID,C.UD_CONTENT_ID, C.TITLE,M.PATH, S.*
		FROM		BC_MEDIA M,
					VIEW_BC_CONTENT C
						LEFT JOIN	BC_SYSMETA_MOVIE S
						ON				S.SYS_CONTENT_ID = C.CONTENT_ID
		WHERE	C.CONTENT_ID = ".$parent_content_id."
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
		WHERE	C.CONTENT_ID = ".$parent_content_id."
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

	$xml_title = $content_info['title'];
	
	$fcp = new FcpXML();
	$fcp->setTitle( $xml_title );
	$fcp->setAudioChannel(8);
	$fcp->setResolution(1920, 1080);

	$file_path = $storage_info_file['storage_path'].'/'.$storage_info_file['file_path']; 
	$duration = $storage_info_file['sys_video_rt'];
	if($is_group != 'G' && $is_group != 'C') {
		$datas = $db->queryAll("
			SELECT 	MARK_ID,
					MARK_TYPE,
					CONTENT_ID,
					MEDIA_ID,
					START_FRAME,
					END_FRAME,
					TITLE,
					REPLACE(COMMENTS, CHR(10), '&#13;') AS COMMENTS,
					COMMENTS as COMMENTS_MARKER,
					COLOR,
					STATUS
			FROM 	BC_MARK 
			WHERE 	CONTENT_ID=".$content_id." 
			AND 	MARK_TYPE='".$type."'
			AND 	STATUS = 'CREATE'
			order by START_FRAME,MARK_ID 
		");
		$data_csv_marker = array();
		$i= 1;
		foreach($datas as $data)
		{
			if ($data['start_frame'] == $data['end_frame']){
				$out = -1;
				$duration_marker = '';
			} else {
				$out = $data['end_frame'];
				$duration_marker = timecode::getConvFrameToTimecode($data['end_frame']- $data['start_frame'], $frame_rate);
			}
			$comment = $data['comments'];
			$name = $data['title'];
			$in = $data['start_frame'];
			//($filepath, $start_tc, $duration , $in , $out, $start, $end )
			$fcp->addMarkInfo($filepath, $duration, $comment, $name , $in , $out);
			array_push($data_csv_marker, array(
								'no'			=> $i,
								'anchor'		=> 'ON',
								'position' 		=> timecode::getConvFrameToTimecode($data['start_frame'], $frame_rate),
								'duration' 		=> $duration_marker,
								'comment'	 	=> $data['comments_marker']
								));
			$i++;
		}
		$start_timecode = '00:00:00;00';
		$fcp->addTLInfo($file_path, $frame_rate, $start_timecode,$duration, $start_timecode ,$duration, $start_timecode, $duration );
		$set_in_frame = $db->queryOne("
			SELECT 	START_FRAME
			FROM 	BC_MARK 
			WHERE 	CONTENT_ID=".$content_id." 
			AND 	MARK_TYPE='MARK_IN'
			AND 	STATUS = 'CREATE'
			order by START_FRAME,MARK_ID 
		");
		$set_out_frame = $db->queryOne("
			SELECT 	START_FRAME
			FROM 	BC_MARK 
			WHERE 	CONTENT_ID=".$content_id." 
			AND 	MARK_TYPE='MARK_OUT'
			AND 	STATUS = 'CREATE'
			order by START_FRAME,MARK_ID 
		");
		if ($set_in_frame == ''){
			$set_in_frame = -1;
		}
		if ($set_out_frame == ''){
			$set_out_frame = -1;
		}
		$fcp->addInOutInfo($set_in_frame , $set_out_frame);
		$xml = $fcp->createFcpXML_Marker();
		$xml_edius = $fcp->createFcpXML();
	} else if ($is_group){
		$start_timecode = '00:00:00;00';
		$groups_content = $db->queryAll("	SELECT	C.CONTENT_ID,
													C.BS_CONTENT_ID,
													C.TITLE
											FROM	BC_CONTENT C
											WHERE	(C.PARENT_CONTENT_ID = $parent_content_id OR C.CONTENT_ID = $parent_content_id)
											AND		C.STATUS = '2'
											ORDER  BY C.GROUP_COUNT");
		$start_sec = 0;
		$duration_frame = 0;
		$data_csv_marker = array();
		$i= 1;
		foreach ($groups_content as $item) {
			$storage_info_child_file = $db->queryRow("
				SELECT	M.PATH as file_path,  S.sys_video_rt, st.path as storage_path
				FROM	BC_MEDIA M, VIEW_UD_STORAGE ST,
						VIEW_BC_CONTENT C
							LEFT JOIN	BC_SYSMETA_MOVIE S
							ON				S.SYS_CONTENT_ID = C.CONTENT_ID
				WHERE	C.CONTENT_ID = ".$item['content_id']."
				AND		ST.UD_CONTENT_ID = C.UD_CONTENT_ID
				AND		ST.US_TYPE = '".$us_type_file."'
				AND		M.CONTENT_ID = C.CONTENT_ID
				AND		M.MEDIA_TYPE = 'original'
				AND		COALESCE(M.STATUS , '0') = '0'
			");
			$file_path_child = $storage_info_child_file['storage_path'].'/'.$storage_info_child_file['file_path']; 
			$duration_child = $storage_info_child_file['sys_video_rt'];
			$start_sec_temp = $start_sec;
			$start_sec += timecode::getConvSecFrame($duration_child,$frame_rate);
			$start_tc = timecode::getConvSecFrameToTimecode($start_sec_temp, $frame_rate);
			$end_tc = timecode::getConvSecFrameToTimecode($start_sec, $frame_rate);
			$fcp->addGroupTLInfo($file_path_child, $item['content_id'], $frame_rate, $start_timecode, $duration_child , $start_timecode ,$duration_child, $start_tc, $end_tc );

			$datas = $db->queryAll("
				SELECT 	MARK_ID,
						MARK_TYPE,
						CONTENT_ID,
						MEDIA_ID,
						START_FRAME,
						END_FRAME,
						TITLE,
						REPLACE(COMMENTS, CHR(10), '&#13;') AS COMMENTS,
						COMMENTS as COMMENTS_MARKER,
						COLOR,
						STATUS
				FROM 	BC_MARK 
				WHERE 	CONTENT_ID=".$item['content_id']." 
				AND 	MARK_TYPE='".$type."'
				AND 	STATUS = 'CREATE'
				order by START_FRAME,MARK_ID 
			");
			foreach($datas as $data)
			{
				if ($data['start_frame'] == $data['end_frame']){
					$out = -1;
					$duration_marker = '';
				} else {
					$out = $data['end_frame'] + $duration_frame;
					$duration_marker = timecode::getConvFrameToTimecode($data['end_frame']- $data['start_frame'], $frame_rate);
				}
				$comment = $data['comments'];
				$name = $data['title'];
				$in = $data['start_frame']  + $duration_frame;

				$fcp->addGroupMarkInfo($file_path_child, $item['content_id'], $duration_child, $comment, $name , $in , $out);
				array_push($data_csv_marker, array(
								'no'			=> $i,
								'anchor'		=> '"ON"',
								'position' 		=> '"'.timecode::getConvFrameToTimecode($in, $frame_rate).'"',
								'duration' 		=> $duration_marker.' ' ,
								'comment'	 	=> '"'.$data['comments_marker'].'"'
								));
				$i++;
			}
			$duration_frame += round(timecode::getConvSecFrame($duration_child,$frame_rate) * $frame_rate);
		}
		$set_in_frame = $db->queryOne("
			SELECT 	START_FRAME
			FROM 	BC_MARK 
			WHERE 	CONTENT_ID=".$content_id." 
			AND 	MARK_TYPE='MARK_IN'
			AND 	STATUS = 'CREATE'
			order by START_FRAME,MARK_ID 
		");
		$set_out_frame = $db->queryOne("
			SELECT 	START_FRAME
			FROM 	BC_MARK 
			WHERE 	CONTENT_ID=".$content_id." 
			AND 	MARK_TYPE='MARK_OUT'
			AND 	STATUS = 'CREATE'
			order by START_FRAME,MARK_ID 
		");
		if ($set_in_frame == ''){
			$set_in_frame = -1;
		}
		if ($set_out_frame == ''){
			$set_out_frame = -1;
		}
		$fcp->addInOutInfo($set_in_frame , $set_out_frame);
		$xml = $fcp->createFcpGroupXML_Marker();
		$xml_edius = $fcp->createFcpGroupXML();
	}

	$path_array = explode('/', $content_info['path'] );
	array_pop($path_array);
	array_pop($path_array);

	//확장자 뺀 파일명
	$target_path = join('/',$path_array);

    $target_path = $target_path.'/'.$content_info['content_id'].'_marker.'.'xml';
    $target_path_edius = join('/',$path_array);
    $target_path_edius = $target_path_edius.'/'.$content_info['content_id'].'_marker_edius.'.'xml';
    $target_path_edius_csv = join('/',$path_array);
	$target_path_edius_csv = $target_path_edius_csv.'/'.$content_info['content_id'].'_marker_edius.'.'csv';
    
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
	
	$fcp->_LogFile('',$down_path, $xml->asXML() );
	$result = $fcp->_PrintFile( $down_path , $xml->asXML() );
	
	$fcp->_LogFile('',$down_path_edius, $xml_edius->asXML() );
	$result_edius = $fcp->_PrintFile( $down_path_edius , $xml_edius->asXML() );

	if($result && $result_edius){
		$filesize_xml = filesize($down_path);
		$check_media = $db->queryAll("
			SELECT	*
			FROM	BC_MEDIA
			WHERE	CONTENT_ID = ".$content_info['content_id']."
			AND		MEDIA_TYPE = 'marker'
		");

		if( count($check_media) > 0 ){
			$query_update = "
				UPDATE	BC_MEDIA	SET
					FILESIZE = ".$filesize_xml."
				WHERE	CONTENT_ID = ".$content_info['content_id']."
				AND		MEDIA_TYPE = 'marker'
			";
			$db->exec($query_update);
		}else{
			// $db->insert('BC_MEDIA', array(
			// 	'CONTENT_ID' => $content_info['content_id'],
			// 	'STORAGE_ID' =>	$content_info['storage_id'],
			// 	'CREATED_DATE' => date('YmdHis'),
			// 	'FILESIZE'	=>	$filesize_xml,
			// 	'PATH' => $target_path,
			// 	'MEDIA_TYPE' => 'marker',
			// 	'STATUS' => $content_info['status'],
			// 	'REG_TYPE' => 'shot_list',
			// 	'EXPIRED_DATE' => '99981231000000'
			// ));
		}

		$filesize_xml_edius = filesize($down_path_edius);
		$check_media_edius = $db->queryAll("
			SELECT	*
			FROM	BC_MEDIA
			WHERE	CONTENT_ID = ".$content_info['content_id']."
			AND		MEDIA_TYPE = 'marker_edius'
		");

		if( count($check_media_edius) > 0 ){
			$query_update = "
				UPDATE	BC_MEDIA	SET
					FILESIZE = ".$filesize_xml_edius."
				WHERE	CONTENT_ID = ".$content_info['content_id']."
				AND		MEDIA_TYPE = 'marker_edius'
			";
			$db->exec($query_update);
		}else{
			// $db->insert('BC_MEDIA', array(
			// 	'CONTENT_ID' => $content_info['content_id'],
			// 	'STORAGE_ID' =>	$content_info['storage_id'],
			// 	'CREATED_DATE' => date('YmdHis'),
			// 	'FILESIZE'	=>	$filesize_xml_edius,
			// 	'PATH' => $target_path_edius,
			// 	'MEDIA_TYPE' => 'marker_edius',
			// 	'STATUS' => $content_info['status'],
			// 	'REG_TYPE' => 'shot_list',
			// 	'EXPIRED_DATE' => '99981231000000'
			// ));
		}

		createCsvMarkerList($down_path_edius_csv, $data_csv_marker);
		$filesize_csv_edius = filesize($down_path_edius_csv);
		$check_media_edius = $db->queryAll("
			SELECT	*
			FROM	BC_MEDIA
			WHERE	CONTENT_ID = ".$content_info['content_id']."
			AND		MEDIA_TYPE = 'marker_edius_csv'
		");

		if( count($check_media_edius) > 0 ){
			$query_update = "
				UPDATE	BC_MEDIA	SET
					FILESIZE = ".$filesize_csv_edius."
				WHERE	CONTENT_ID = ".$content_info['content_id']."
				AND		MEDIA_TYPE = 'marker_edius_csv'
			";
			$db->exec($query_update);
		}else{
			// $db->insert('BC_MEDIA', array(
			// 	'CONTENT_ID' => $content_info['content_id'],
			// 	'STORAGE_ID' =>	$content_info['storage_id'],
			// 	'CREATED_DATE' => date('YmdHis'),
			// 	'FILESIZE'	=>	$filesize_csv_edius,
			// 	'PATH' => $target_path_edius_csv,
			// 	'MEDIA_TYPE' => 'marker_edius_csv',
			// 	'STATUS' => $content_info['status'],
			// 	'REG_TYPE' => 'shot_list',
			// 	'EXPIRED_DATE' => '99981231000000'
			// ));
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