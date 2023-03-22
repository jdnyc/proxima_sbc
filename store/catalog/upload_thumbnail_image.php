<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/timecode.class.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/MetaData.class.php');
try{
	$content_id = $_POST['content_id'];
	$imageData = $_POST['imgBase64'];
	$sec = $_POST['sec'];
	$frame_rate = getFrameRate($content_id);

	$content_info = $db->queryRow("select bs_content_id, ud_content_id from bc_content where content_id=".$content_id);
	$bs_content_id = $content_info['bs_content_id'];
	$ud_content_id = $content_info['ud_content_id'];

	if( in_array( $ud_content_id, $CG_LIST  ) ){
		$alias = '/data/CGLowres/';
	}else{
		$alias = '/data/';
	}

	$all = $db->queryAll("select * from bc_media m, bc_scene s where m.content_id={$_POST['content_id']} and m.media_id=s.media_id order by s.start_frame");
	$media = $db->queryRow("select media_id, path from bc_media where content_id=$content_id and media_type='original'");
	$media_id = $db->queryOne("select media_id from bc_media where content_id=$content_id and media_type='proxy'");

	$scene_id = getNextSequence();
	$show_order = ' ';
	//$path = $scene_list['thumbnail'];
	//$where = join(' and ' , $_where);
	$make_target = explode('/', $media[path]);
	$i = count($make_target)-1;
	$filename = $make_target[$i];
	$content_path = substr($media[path], 0, strlen($media[path])- strlen($filename));

	$path = $content_path.$content_id.'/Catalog/'.$scene_id.'.jpg';
	$start_frame = round($sec*$frame_rate);
	$title = 'Title'.$scene_id;

	// Remove the headers (data:,) part.
	// A real application should use them according to needs such as to check image type
	$filteredData=substr($imageData, strpos($imageData, ",")+1);

	// Need to decode before saving since the data we received is already base64 encoded
	$unencodedData=base64_decode($filteredData);

	//echo "unencodedData".$unencodedData;

	// Save file. This example uses a hard coded filename for testing,
	// but a real application can specify filename in POST variable

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
	$down_path = $storage_info[path_for_unix];
	$count = $db->queryOne("select count(*) from bc_media m, bc_scene s where m.content_id=$content_id and m.media_id=s.media_id and s.START_FRAME=$start_frame");
	if ($count == 0){
		$fp = fopen( $down_path.'/'.$path, 'wb' );
		fwrite( $fp, $unencodedData);
		fclose( $fp );

		$filesize = filesize($down_path.'/'.$path);
		//pgsql에서 동작하도록 변경
//		$query = "insert into BC_SCENE (SCENE_ID,MEDIA_ID,PATH,START_FRAME,FILESIZE,TITLE ) values ($scene_id,$media_id,'$path',$start_frame,$filesize,'$title')";
//		$r = $db->exec($query);
		$insert_data_arr = array(
			'SCENE_ID' => $scene_id,
			'MEDIA_ID' => $media_id,
			'PATH' => $path,
			'START_FRAME' => $start_frame,
			'FILESIZE' => $filesize,
			'TITLE' => $title
		);
		$db->insert('BC_SCENE',$insert_data_arr);
		echo json_encode( array(
			'success' => true,
			'result' => 'success'
		));
	} else {
		echo json_encode( array(
			'success' => false,
			'result' => 'success',
			'msg' => 'Same file is exists'
		));
	}

	
}
catch(Exception $e){
	die(json_encode(array(
		'success' => false,
		'result' => 'failure',
		'msg' => $e->getMessage()
	)));
}
	
?>