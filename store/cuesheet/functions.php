<?php

function createCuesheetXML($cuesheet_id, $user_id)
{
	global $db;
	$xml = new SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\"?> \n<CueMetaData />");

	// 큐시트 아이템
	//	CUEITEM TITLE="(3) 이개호 법안발의" FILEPATH="V:/default/3이개호법안발의_N20141103V25850" FILENAME="3이개호법안발의_N20141103V25850"/
	$cue_items = $db->queryAll("select * from bc_cuesheet_content where cuesheet_id = '$cuesheet_id' order by show_order");
	foreach ($cue_items as $item) {
		$cuesheet = $xml->addChild("CUEITEM");
		$content_id = $item['content_id'];
		$filename = get_cue_filename($content_id);
		$cuesheet->addAttribute('TITLE', $item['title']);
		$cuesheet->addAttribute('FILEPATH', 'V:/default/'.$filename);
		$cuesheet->addAttribute('FILENAME', $filename);
	}

	return $xml->asXML();
}

function get_cue_filename($content_id) {
	global $db;

	$original_path = $db->queryOne("select path from bc_media where media_type = 'original' and content_id = '$content_id'");

	$path_array = explode('/',$original_path);

	if(count($path_array) < 1) return false;

	//파일명
	$file = array_pop($path_array);

	//파일명과 확장자 분리
	$filename_array = explode('.', $file);
	$filename = $filename_array[0];

	return $filename;
}

function getOriginalFileExtension($content_id) {
	global $db;

	$original_path = $db->queryOne("select path from bc_media where media_type = 'original' and content_id = '$content_id'");

	return pathinfo($original_path, PATHINFO_EXTENSION);
}

?>