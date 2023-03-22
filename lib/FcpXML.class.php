<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/lib/timecode.class.php';

class FcpXML
{
	public $masterclipid;//마스터클립ID
	public $audioChannel;//오디오채널
	public $start_tc;//시퀀스 시작타임코드
	public $start_tc_frame;//시퀀스 시작타임코드 프레임
	public $total_duration_frame = 0;//총RT 프레임
	public $timeline_info = array(); //타임라인정보
	public $marker_info = array();
	public $in_out_info = array();
	public $group_timeline_info = array(); //그룹타임라인정보

	public $width;
	public $height;

	function __construct()
	{
		/*기본값 셋팅*/
		$this->title = 'Sequence';
		$this->audioChannel = 4;
		//$this->masterclipid = $this->title.'file1';
		$this->start_tc = '00:00:00;00';
		$this->start_tc_frame = 0;
		$this->width = 1920;
		$this->height = 1080;
		//($filepath, $start_tc, $duration , $in , $out, $start, $end )
	}

	function setResolution($width, $height){//시퀀스명
		$this->width = $width;
		$this->height = $height;
		return true;
	}

	function setTitle($title){//시퀀스명
		$this->title = $title;
		return true;
	}

	function setAudioChannel ($channel){//오디오채널변경
		$this->audioChannel = $channel;
		return true;
	}

	function TimecodeToFrame($timecode, $frame_rate){//타임코드to프레임 변환

		$TCArray = explode(':', trim($timecode) );

		$hour = $TCArray[0];
		$min = $TCArray[1];
		$sec = $TCArray[2];
		$frame = $TCArray[3];

		if( !is_numeric($hour) ) return false;
		if( !is_numeric($min) ) return false;

		/* if( strstr($sec, ';') ){
			//frame 29.97
			$sec_array = explode(';', $sec);
			$sec = $sec_array[0];
			$frame = $sec_array[1];

		}else if( strstr($sec, '.') ){
			// 1/100
			$sec_array = explode('.', $sec);
			$sec = $sec_array[0];
			$frame = (int)($sec_array[1] / 3 );

		}else if( is_numeric($sec) ){
			$frame = 0;
		} */

		$total_sec = ( $hour * 3600 ) + ( $min * 60 ) + $sec ;

		$total_frame = round( ( ( $total_sec * $frame_rate ) + $frame ) );
		return $total_frame;
	}

	function addTLInfo($filepath, $frame_rate, $start_tc, $duration , $in , $out, $start, $end ,$data = null ){//타임라인정보 입력
		$start_tc_frame = $this->TimecodeToFrame($start_tc, $frame_rate);
		$duration_frame = $this->TimecodeToFrame($duration, $frame_rate);

		$this->total_duration_frame = $this->total_duration_frame + $duration_frame;
		$fileinfo = $this->getFileInfo($filepath);

		if( !empty($data) ){
			$start_frame = $data[start_frame];
			$end_frame = $data[end_frame];
			$in_frame = $data[in_frame];
			$out_frame = $data[out_frame];
		}else{
			$in_frame = $this->TimecodeToFrame($in, $frame_rate);
			$out_frame = $this->TimecodeToFrame($out, $frame_rate);
			$start_frame = $this->TimecodeToFrame($start, $frame_rate);
			$end_frame = $this->TimecodeToFrame($end, $frame_rate);
		}
		array_push($this->timeline_info,array(
			'start_tc'			=> $start_tc,
			'start_tc_frame'	=> $start_tc_frame,
			'filepath'			=> $filepath,
			'duration'			=> $duration,
			'duration_frame'	=> $duration_frame,
			'in'				=> $in ,
			'in_frame'			=> $in_frame ,
			'out'				=> $out ,
			'out_frame'			=> $out_frame,
			'start'				=> $start,
			'start_frame'		=> $start_frame,
			'end'				=> $end,
			'end_frame'			=> $end_frame,
			'fullpath'			=> $fileinfo['fullpath'],
			'filename'			=> $fileinfo['filename'],
			'fileext'			=> $fileinfo['fileext'],
			'file_id'			=> $fileinfo['file_id'],
			'is_new'			=> $fileinfo['is_new']
		));
	}

	function addMarkInfo($filepath, $duration, $comment, $name , $in , $out){//타임라인정보 입력
		//$fileinfo = $this->getFileInfo($filepath);
		//$this->total_duration_frame = $this->total_duration_frame + $duration;
		array_push($this->marker_info,array(
			'comment'		=> $comment,
			'name'			=> $name,
			'in'			=> $in,
			'out'			=> $out
		));
	}

	function addGroupMarkInfo($filepath, $content_id, $duration, $comment, $name , $in , $out){//타임라인정보 입력
		//$fileinfo = $this->getFileInfo($filepath);
		//$this->total_duration_frame = $this->total_duration_frame + $duration;
		array_push($this->marker_info,array(
			'content_id'	=> $content_id,
			'comment'		=> $comment,
			'name'			=> $name,
			'in'			=> $in,
			'out'			=> $out
		));
	}

	function addInOutInfo($in , $out){//타임라인정보 입력
		//$fileinfo = $this->getFileInfo($filepath);
		//$this->total_duration_frame = $this->total_duration_frame + $duration;
		array_push($this->in_out_info,array(
			'in'			=> $in,
			'out'			=> $out
		));
	}

	function addGroupTLInfo($filepath, $content_id, $frame_rate, $start_tc, $duration , $in , $out, $start, $end ){//타임라인정보 입력
		$start_tc_frame = $this->TimecodeToFrame($start_tc, $frame_rate);
		$duration_frame = $this->TimecodeToFrame($duration, $frame_rate);

		$this->total_duration_frame = $this->total_duration_frame + $duration_frame;
		$fileinfo = $this->getFileInfo($filepath);

		$in_frame = $this->TimecodeToFrame($in, $frame_rate);
		$out_frame = $this->TimecodeToFrame($out, $frame_rate);
		$start_frame = $this->TimecodeToFrame($start, $frame_rate);
		$end_frame = $this->TimecodeToFrame($end, $frame_rate);
		array_push($this->timeline_info,array(
		'content_id'		=> $content_id,
		'start_tc'			=> $start_tc,
		'start_tc_frame'	=> $start_tc_frame,
		'filepath'			=> $filepath,
		'duration'			=> $duration,
		'duration_frame'	=> $duration_frame,
		'in'				=> $in ,
		'in_frame'			=> $in_frame ,
		'out'				=> $out ,
		'out_frame'			=> $out_frame,
		'start'				=> $start,
		'start_frame'		=> $start_frame,
		'end'				=> $end,
		'end_frame'			=> $end_frame,
		'fullpath'			=> $fileinfo['fullpath'],
		'filename'			=> $fileinfo['filename'],
		'fileext'			=> $fileinfo['fileext'],
		'file_id'			=> $fileinfo['file_id'],
		'is_new'			=> $fileinfo['is_new']
		));
	}

	function getFileInfo($path){//파일정보 생성
		$is_new = true;
		$fullpath = $path;
		$pathArray = explode('/', $path);
		$file = array_pop($pathArray);

		$fileArray = explode('.', $file);
		$ext = array_pop($fileArray);
		$filepath = join('/', $pathArray);
		$filename = join('.', $fileArray);
		$fileext = $ext;

		$cnt = count($this->timeline_info) + 1;

		$file_id = $filename.'file'.$cnt;

		foreach($this->timeline_info as $info)
		{
			if( $info['fullpath'] == $fullpath ){
				$is_new = false;
				$file_id=$info['file_id'];
			}
		}
		return array(
			'fullpath' => $fullpath,
			'filepath' => $filepath,
			'filename' => $filename,
			'fileext' => $fileext,
			'file_id' => $file_id,
			'is_new' => $is_new
		);
	}

	function addAudioTrack($xml, $clipindex, $track_num , $tl_infos ){//오디오트랙매핑

		$track = $xml->addChild('track');

		foreach($tl_infos as $key => $tl_info )
		{
			$clipindex = $key + 1;
			$clipitem = $track->addChild('clipitem');
			$clipitem->addAttribute('id', $this->title.$clipindex.$track_num );
			$clipitem->addChild('name',''.$this->title.'');
			$clipitem->addChild('duration',$tl_info['total_duration_frame']);
			$clipitem = $this->addRate($clipitem);
			$clipitem->addChild('in', $tl_info['in_frame']);
			$clipitem->addChild('out',$tl_info['out_frame']);
			$clipitem->addChild('start', $tl_info['start_frame']);
			$clipitem->addChild('end', $tl_info['end_frame']);
			$clipitem->addChild('masterclipid', $this->masterclipid );
				$logginginfo = $clipitem->addChild('logginginfo');
				$logginginfo->addChild('good', 'FALSE');

				$file = $clipitem->addChild('file');
					$file->addAttribute('id', $tl_info['file_id'] );
				$filter = $clipitem->addChild('filter');
					$effect = $filter->addChild('effect');
					$effect->addChild('name', 'Audio Levels');
					$effect->addChild('effectid', 'audiolevels');
					$effect->addChild('effectcategory', 'audiolevels');
					$effect->addChild('effecttype', 'audiolevels');
					$effect->addChild('mediatype', 'audio');
						$parameter = $effect->addChild('parameter');
						$parameter->addChild('name', 'Level');
						$parameter->addChild('parameterid', 'Level');
						$parameter->addChild('valuemin', '0');
						$parameter->addChild('valuemax', '3.98109');
						$parameter->addChild('value', '1');

				$filter = $clipitem->addChild('filter');
					$effect = $filter->addChild('effect');
					$effect->addChild('name', 'Audio Pan');
					$effect->addChild('effectid', 'audiopan');
					$effect->addChild('effectcategory', 'audiopan');
					$effect->addChild('effecttype', 'audiopan');
					$effect->addChild('mediatype', 'audio');
						$parameter = $effect->addChild('parameter');
						$parameter->addChild('name', 'Pan');
						$parameter->addChild('parameterid', 'pan');
						$parameter->addChild('valuemin', '-1');
						$parameter->addChild('valuemax', '1');
						$parameter->addChild('value', '0');

				$sourcetrack = $clipitem->addChild('sourcetrack');
				$sourcetrack->addChild('mediatype','audio');
				$sourcetrack->addChild('trackindex', $track_num );

				$link = $clipitem->addChild('link');
				$link->addChild('linkclipref',''.$this->title.'v'.$clipindex);
				$link->addChild('mediatype','video');
				$link->addChild('trackindex','1');
				$link->addChild('clipindex',$clipindex);

				for($channelnum = 1 ; $channelnum <= $this->audioChannel ;$channelnum++ )
				{
					$link = $clipitem->addChild('link');
					$link->addChild('linkclipref',''.$this->title.$clipindex.$channelnum);
					$link->addChild('mediatype','audio');
					$link->addChild('trackindex',$channelnum);
					$link->addChild('clipindex',$clipindex);
				}

		}

		$track->addChild('enabled', 'TRUE');
		$track->addChild('locked', 'FALSE');
		$track->addChild('outputchannelindex', $track_num );

		return $xml;

	}

	function addVideoTrack($xml, $clipindex, $tl_info ){//비디오트랙매핑

		$clipitem = $xml->addChild('clipitem');
		$clipitem->addAttribute('id',$this->title.'v'.$clipindex );

		$clipitem->addChild('name',$this->title.'');
		$clipitem->addChild('duration', $tl_info['duration_frame'] );
		$clipitem = $this->addRate($clipitem);
		$clipitem->addChild('in', $tl_info['in_frame']);
		$clipitem->addChild('out', $tl_info['out_frame'] );
		$clipitem->addChild('start',$tl_info['start_frame']);
		$clipitem->addChild('end', $tl_info['end_frame']);
		$clipitem->addChild('pixelaspectratio','NTSC-601');
		$clipitem->addChild('anamorphic','FALSE');
		$clipitem->addChild('alphatype','none');
		//$clipitem->addChild('masterclipid', $this->masterclipid );
		$logginginfo = $clipitem->addChild('logginginfo');
		$logginginfo->addChild('good', 'TRUE');

		//test
		$logginginfo->addChild('lognote', 'lognote');
		$logginginfo->addChild('description', 'description');
		$logginginfo->addChild('scene', 'scene');
//		$labels =  $clipitem->addChild('labels' );
//		$labels->addChild('label' ,'label');
//		$labels->addChild('label2' ,'label2');

		//if($tl_info['is_new']){
			$clipitem = $this->addNewFile( $clipitem, $tl_info );
		//}else{
		//	$clipitem = $this->addFile( $clipitem, $tl_info );
		//}
//		$clipitem->addChild('comment', 'commentcommentcommentcommentcomment');
//
//		$marker = $clipitem->addChild('marker');
//		$marker->addChild('name', 'marker1');
//		$marker->addChild('in', $tl_info['in_frame']);
//		$marker->addChild('out', $tl_info['out_frame']);
//		$marker->addChild('comment', 'marker1');
//
//		$comments = $clipitem->addChild('comments');
//		$comments->addChild('mastercomment1', 'mastercomment1');
//		$comments->addChild('mastercomment2', 'mastercomment2');
//		$comments->addChild('mastercomment3', 'mastercomment2');
//		$comments->addChild('mastercomment4', 'mastercomment2');
//		$comments->addChild('clipcommenta', 'clipcommenta');
//		$comments->addChild('clipcommentb', 'clipcommentb');

		$sourcetrack = $clipitem->addChild('sourcetrack');
		$sourcetrack->addChild('mediatype', 'video');

		$link = $clipitem->addChild('link');
		$link->addChild('linkclipref', ''.$this->title.'v'.$clipindex);
		$link->addChild('mediatype', 'video');
		$link->addChild('trackindex', '1');
		$link->addChild('clipindex', $clipindex);

		for($channelnum = 1 ; $channelnum <= $this->audioChannel ;$channelnum++ )
		{
			$link = $clipitem->addChild('link');
			$link->addChild('linkclipref',''.$this->title.$clipindex.$channelnum);
			$link->addChild('mediatype','audio');
			$link->addChild('trackindex',$channelnum);
			$link->addChild('clipindex',$clipindex);
		}

		$clipitem->addChild('fielddominance' , 'upper');


		return $xml;
	}

	function addNewFile( $xml, $tl_info ){//파일정보생성
		$file = $xml->addChild('file');
		$file->addAttribute('id', $tl_info['file_id']);
		$file->addChild('name' , $tl_info['filename'].'.'.$tl_info['fileext'] );
		$file->addChild('pathurl' , 'file://localhost/'.$tl_info['fullpath'] );
		$file = $this->addRate($file);
		$file->addChild('duration', $tl_info['total_duration_frame']);
		$timecode = $file->addChild('timecode');
		$timecode = $this->addRate($timecode);

		$timecode->addChild('string',$tl_info['start_tc']);
		$timecode->addChild('frame', $tl_info['start_tc_frame']);
		$timecode->addChild('displayformat','DF');
		$timecode->addChild('source','source');

		$file_media = $file->addChild('media');
		$video = $file_media->addChild('video');
		$video->addChild('duration', $tl_info['duration_frame'] );
		$samplecharacteristics = $video->addChild('samplecharacteristics');
		$samplecharacteristics->addChild('width', $this->width);
		$samplecharacteristics->addChild('height', $this->height);

		$audio = $file_media->addChild('audio');
		$samplecharacteristics = $audio->addChild('samplecharacteristics');
		$samplecharacteristics->addChild('samplerate', 48000);
		$samplecharacteristics->addChild('depth', 16);
//		$audio->addChild('channelcount', $this->audioChannel);

		return $xml;

	}

	function addFile($xml,$tl_info){//참조파일정보생성
		$file = $xml->addChild('file');
		$file->addAttribute('id', $tl_info['file_id']);
		return $xml;
	}

	function addVideoFormat($xml){//비디오포맷
		$format = $xml->addChild('format');
		$samplecharacteristics = $format->addChild('samplecharacteristics');
		$samplecharacteristics->addChild('width', $this->width);
		$samplecharacteristics->addChild('height', $this->height);
		$samplecharacteristics->addChild('anamorphic', 'FALSE');
		$samplecharacteristics->addChild('pixelaspectratio', 'Square');
		$samplecharacteristics->addChild('fielddominance', 'upper');
		$samplecharacteristics = $this->addRate($samplecharacteristics);
		$samplecharacteristics->addChild('colordepth', 24);

		$codec = $samplecharacteristics->addChild('codec');
		$codec->addChild('name', 'Apple XDCAM 422 1080i60 (50 Mb/s CBR)');

		$appspecificdata = $codec->addChild('appspecificdata');
		$appspecificdata->addChild('appname','Final Cut Pro');
		$appspecificdata->addChild('appmanufacturer','Apple Inc.');
		$appspecificdata->addChild('appversion','7.0');
		$data = $appspecificdata->addChild('data');
		$qtcodec = $data->addChild('qtcodec');
		$qtcodec->addChild('codecname', 'Apple XDCAM 422 1080i60 (50 Mb/s CBR)');
		$qtcodec->addChild('codectypename', 'XDCAM 422 1080i60 (50 Mb/s CBR)');
		$qtcodec->addChild('codectypecode', 'xd5b');
		$qtcodec->addChild('codecvendorcode', 'appl');
		$qtcodec->addChild('spatialquality', 1023);
		$qtcodec->addChild('temporalquality', 0);
		$qtcodec->addChild('keyframerate', 0);
		$qtcodec->addChild('datarate', 0);

		$appspecificdata = $format->addChild('appspecificdata');
		$appspecificdata->addChild('appname','Final Cut Pro');
		$appspecificdata->addChild('appmanufacturer','Apple Inc.');
		$appspecificdata->addChild('appversion','7.0');
		$data = $appspecificdata->addChild('data');
		$fcpimageprocessing = $data->addChild('fcpimageprocessing');
		$fcpimageprocessing->addChild('useyuv', 'TRUE');
		$fcpimageprocessing->addChild('usesuperwhite', 'FALSE');
		$fcpimageprocessing->addChild('rendermode','YUV8BPP');

		return $xml;

	}

	function addAudioFormat($xml){//오디오포맷
		$format = $xml->addChild('format');
		$samplecharacteristics =  $format->addChild('samplecharacteristics');
		$samplecharacteristics->addChild('depth', 24 );
		$samplecharacteristics->addChild('samplerate', 48000 );
		return $xml;
	}

	function addAudioFilter($xml){//오디오필터
		$filter = $xml->addChild('filter');
		$effect = $filter->addChild('effect');
		$effect->addChild('name', 'Audio Levels');
		$effect->addChild('effectid', 'audiolevels');
		$effect->addChild('effectcategory', 'audiolevels');
		$effect->addChild('effecttype', 'audiolevels');
		$effect->addChild('mediatype', 'audio');
		$parameter = $effect->addChild('parameter');
		$parameter->addChild('name', 'Level');
		$parameter->addChild('parameterid', 'Level');
		$parameter->addChild('valuemin', '0');
		$parameter->addChild('valuemax', '3.98109');
		$parameter->addChild('value', '1');

		return $xml;
	}

	function addAudioOutputs($xml){//오디오맵핑
		$outputs = $xml->addChild('outputs');

		for($channelnum = 1 ; $channelnum <= $this->audioChannel ;$channelnum++ )
		{
			$group = $outputs->addChild('group');
			$group->addChild('index', $channelnum);
			$group->addChild('numchannels', 1);
			$group->addChild('downmix', -3);
			$channel =  $group->addChild('channel');
			$channel->addChild('index', $channelnum);
		}

		return $xml;
	}

	function createFcpXML(){//XML생성
		$basexml = new SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\"?><!DOCTYPE xmeml><xmeml version=\"5\" />");

		//시퀀스 정보
		$seqxml = $basexml->addChild('sequence');
		$seqxml->addAttribute('id' , $this->title);

		$seqxml->addAttribute('MZ.Sequence.PreviewFrameSizeHeight' , $this->height);
		$seqxml->addAttribute('MZ.Sequence.PreviewFrameSizeWidth' , $this->width);
		$seqxml->addAttribute('MZ.Sequence.AudioTimeDisplayFormat' , "200");
		$seqxml->addAttribute('MZ.Sequence.PreviewRenderingClassID' , "1061109567");
		$seqxml->addAttribute('MZ.Sequence.PreviewRenderingPresetCode' , "2019833186");
		$seqxml->addAttribute('MZ.Sequence.PreviewRenderingPresetPath' , "EncoderPresets\\SequencePreview\\b778b3d6-ecf0-41ec-8c32-6d7d1db81bb8\\XDCAMHD 50 NTSC.epr");
		$seqxml->addAttribute('MZ.Sequence.PreviewUseMaxRenderQuality' , "false");
		$seqxml->addAttribute('MZ.Sequence.PreviewUseMaxBitDepth' , "false");
		$seqxml->addAttribute('MZ.Sequence.EditingModeGUID' , "b778b3d6-ecf0-41ec-8c32-6d7d1db81bb8");
		$seqxml->addAttribute('MZ.Sequence.VideoTimeDisplayFormat' , "102");
		$seqxml->addAttribute('explodedTracks' , "true");




		$seqxml->addChild('updatebehavior','add');
		$seqxml->addChild('name',$this->title);
		$seqxml->addChild('duration', $this->total_duration_frame);

		$seqxml = $this->addRate($seqxml);
		//시작타임코드정보
		$seqxml = $this->addTimeCode($seqxml);
		$seqxml->addChild('in', -1);
		$seqxml->addChild('out', -1);

		$media = $seqxml->addChild('media');
		$video = $media->addChild('video');

		//포맷정보 추가
		$video = $this->addVideoFormat($video);

		//video track 정보 추가
		$track = $video->addChild('track');
		foreach($this->timeline_info as $key => $timeline_info ){
			$track = $this->addVideoTrack($track, $key+1, $timeline_info );
		}
		$track->addChild('enabled','TRUE');
		$track->addChild('locked', 'FALSE');

		$audio = $media->addChild('audio');
		$audio->addChild('in', -1);
		$audio->addChild('out', -1 );
		for($channelnum = 1 ; $channelnum <= $this->audioChannel ;$channelnum++ ){
			$audio = $this->addAudioTrack($audio ,1, $channelnum , $this->timeline_info );
		}

		//addMarkInfo
		foreach($this->marker_info as $key => $mark){
			$marker = $seqxml->addChild('marker');
			foreach($mark as $mark_key => $mark_value){
				$marker->addChild($mark_key, $mark_value );
			}
		}

	//	$audio = $this->addAudioFormat($audio);
	//	$audio = $this->addAudioOutputs($audio);
	//	$audio = $this->addAudioFilter($audio);
		$seqxml->addChild('ismasterclip', 'FALSE');

//		$marker = $seqxml->addChild('marker');
//		$marker->addChild('name', 'marker1');
//		$marker->addChild('in', 330);
//		$marker->addChild('out', -1);
//		$color =  $marker->addChild('color');
//		$color->addChild('alpha', 0);
//		$color->addChild('red', 127);
//		$color->addChild('green', 0);
//		$color->addChild('blue', 255);
//		$marker->addChild('comment','112312312');
//		$marker = $seqxml->addChild('marker');
//		$marker->addChild('name', 'marker2');
//		$marker->addChild('in', 340);
//		$marker->addChild('out', -1);
//				$color =  $marker->addChild('color');
//		$color->addChild('alpha', 0);
//		$color->addChild('red', 127);
//		$color->addChild('green', 0);
//		$color->addChild('blue', 255);
//		$marker->addChild('comment','dfasdfsf');

		return $basexml;
	}

	function createFcpXML_Marker(){//XML생성
		$basexml = new SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\"?><!DOCTYPE xmeml><xmeml version=\"5\" />");

		//시퀀스 정보
		$seqxml = $basexml->addChild('sequence');
		$seqxml->addAttribute('id' , $this->title);

		$seqxml->addAttribute('MZ.Sequence.PreviewFrameSizeHeight' , $this->height);
		$seqxml->addAttribute('MZ.Sequence.PreviewFrameSizeWidth' , $this->width);
		$seqxml->addAttribute('MZ.Sequence.AudioTimeDisplayFormat' , "200");
		$seqxml->addAttribute('MZ.Sequence.PreviewRenderingClassID' , "1061109567");
		$seqxml->addAttribute('MZ.Sequence.PreviewRenderingPresetCode' , "2019833186");
		$seqxml->addAttribute('MZ.Sequence.PreviewRenderingPresetPath' , "EncoderPresets\\SequencePreview\\b778b3d6-ecf0-41ec-8c32-6d7d1db81bb8\\XDCAMHD 50 NTSC.epr");
		$seqxml->addAttribute('MZ.Sequence.PreviewUseMaxRenderQuality' , "false");
		$seqxml->addAttribute('MZ.Sequence.PreviewUseMaxBitDepth' , "false");
		$seqxml->addAttribute('MZ.Sequence.EditingModeGUID' , "b778b3d6-ecf0-41ec-8c32-6d7d1db81bb8");
		$seqxml->addAttribute('MZ.Sequence.VideoTimeDisplayFormat' , "102");
		$seqxml->addAttribute('explodedTracks' , "true");

		//$seqxml->addChild('updatebehavior','add');
		$seqxml->addChild('name',$this->title);
		$seqxml->addChild('duration', $this->total_duration_frame);

		$seqxml = $this->addRate($seqxml);
		//시작타임코드정보
		$seqxml = $this->addTimeCode($seqxml);
		//in and out info
		foreach($this->in_out_info as $key => $in_out){
			foreach($in_out as $in_out_key => $in_out_value){
				$seqxml->addChild($in_out_key, $in_out_value );
			}
		}

		$media = $seqxml->addChild('media');
		$video = $media->addChild('video');

		//포맷정보 추가
		$video = $this->addVideoFormat($video);

		//video track 정보 추가
		$track = $video->addChild('track');
		foreach($this->timeline_info as $key => $timeline_info ){
			$track = $this->addVideoTrack($track, $key+1, $timeline_info );
		}
		$track->addChild('enabled','TRUE');
		$track->addChild('locked', 'FALSE');

		$audio = $media->addChild('audio');
		$audio->addChild('in', -1);
		$audio->addChild('out', -1 );
		for($channelnum = 1 ; $channelnum <= $this->audioChannel ;$channelnum++ ){
			$audio = $this->addAudioTrack($audio ,1, $channelnum , $this->timeline_info );
		}
		//addMarkInfo
		foreach($this->marker_info as $key => $mark){
			$marker = $seqxml->addChild('marker');
			foreach($mark as $mark_key => $mark_value){
				$marker->addChild($mark_key, $mark_value );
			}
		}

		//$seqxml->addChild('ismasterclip', 'FALSE');

		return $basexml;
	}

	function createFcpGroupXML(){//XML생성
		$basexml = new SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\"?><!DOCTYPE xmeml><xmeml version=\"5\" />");

		//시퀀스 정보
		$seqxml = $basexml->addChild('sequence');
		$seqxml->addAttribute('id' , $this->title);

		$seqxml->addAttribute('MZ.Sequence.PreviewFrameSizeHeight' , $this->height);
		$seqxml->addAttribute('MZ.Sequence.PreviewFrameSizeWidth' , $this->width);
		$seqxml->addAttribute('MZ.Sequence.AudioTimeDisplayFormat' , "200");
		$seqxml->addAttribute('MZ.Sequence.PreviewRenderingClassID' , "1061109567");
		$seqxml->addAttribute('MZ.Sequence.PreviewRenderingPresetCode' , "2019833186");
		$seqxml->addAttribute('MZ.Sequence.PreviewRenderingPresetPath' , "EncoderPresets\\SequencePreview\\b778b3d6-ecf0-41ec-8c32-6d7d1db81bb8\\XDCAMHD 50 NTSC.epr");
		$seqxml->addAttribute('MZ.Sequence.PreviewUseMaxRenderQuality' , "false");
		$seqxml->addAttribute('MZ.Sequence.PreviewUseMaxBitDepth' , "false");
		$seqxml->addAttribute('MZ.Sequence.EditingModeGUID' , "b778b3d6-ecf0-41ec-8c32-6d7d1db81bb8");
		$seqxml->addAttribute('MZ.Sequence.VideoTimeDisplayFormat' , "102");
		$seqxml->addAttribute('explodedTracks' , "true");

		//PREMERE SD -> HD 로 넣는거 임시 방편 


		$seqxml->addChild('updatebehavior','add');
		$seqxml->addChild('name',$this->title);
		$seqxml->addChild('duration', $this->total_duration_frame);

		$seqxml = $this->addRate($seqxml);
		//시작타임코드정보
		$seqxml = $this->addTimeCode($seqxml);
		$seqxml->addChild('in', -1);
		$seqxml->addChild('out', -1);

		$media = $seqxml->addChild('media');
		$video = $media->addChild('video');

		//포맷정보 추가
		$video = $this->addVideoFormat($video);

		//video track 정보 추가
		$track = $video->addChild('track');
		foreach($this->timeline_info as $key => $timeline_info ){
			$track = $this->addVideoTrack($track, $key+1, $timeline_info );
		}
		$track->addChild('enabled','TRUE');
		$track->addChild('locked', 'FALSE');

		$audio = $media->addChild('audio');
		$audio->addChild('in', -1);
		$audio->addChild('out', -1 );
		for($channelnum = 1 ; $channelnum <= $this->audioChannel ;$channelnum++ ){
			$audio = $this->addAudioTrack($audio ,1, $channelnum , $this->timeline_info );
		}

		$seqxml->addChild('ismasterclip', 'FALSE');

		return $basexml;
	}

	function createFcpGroupXML_Marker(){//XML생성
		$basexml = new SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\"?><!DOCTYPE xmeml><xmeml version=\"5\" />");

		//시퀀스 정보
		$seqxml = $basexml->addChild('sequence');
		$seqxml->addAttribute('id' , $this->title);

		$seqxml->addAttribute('MZ.Sequence.PreviewFrameSizeHeight' , $this->height);
		$seqxml->addAttribute('MZ.Sequence.PreviewFrameSizeWidth' , $this->width);
		$seqxml->addAttribute('MZ.Sequence.AudioTimeDisplayFormat' , "200");
		$seqxml->addAttribute('MZ.Sequence.PreviewRenderingClassID' , "1061109567");
		$seqxml->addAttribute('MZ.Sequence.PreviewRenderingPresetCode' , "2019833186");
		$seqxml->addAttribute('MZ.Sequence.PreviewRenderingPresetPath' , "EncoderPresets\\SequencePreview\\b778b3d6-ecf0-41ec-8c32-6d7d1db81bb8\\XDCAMHD 50 NTSC.epr");
		$seqxml->addAttribute('MZ.Sequence.PreviewUseMaxRenderQuality' , "false");
		$seqxml->addAttribute('MZ.Sequence.PreviewUseMaxBitDepth' , "false");
		$seqxml->addAttribute('MZ.Sequence.EditingModeGUID' , "b778b3d6-ecf0-41ec-8c32-6d7d1db81bb8");
		$seqxml->addAttribute('MZ.Sequence.VideoTimeDisplayFormat' , "102");
		$seqxml->addAttribute('explodedTracks' , "true");


		//$seqxml->addChild('updatebehavior','add');
		$seqxml->addChild('name',$this->title);
		$seqxml->addChild('duration', $this->total_duration_frame);

		$seqxml = $this->addRate($seqxml);
		//시작타임코드정보
		$seqxml = $this->addTimeCode($seqxml);
		//in and out info
		foreach($this->in_out_info as $key => $in_out){
			foreach($in_out as $in_out_key => $in_out_value){
				$seqxml->addChild($in_out_key, $in_out_value );
			}
		}

		$media = $seqxml->addChild('media');
		$video = $media->addChild('video');

		//포맷정보 추가
		$video = $this->addVideoFormat($video);

		//video track 정보 추가
		$track = $video->addChild('track');
		foreach($this->timeline_info as $key => $timeline_info ){
			$track = $this->addVideoTrack($track, $key+1, $timeline_info );
		}
		$track->addChild('enabled','TRUE');
		$track->addChild('locked', 'FALSE');

		$audio = $media->addChild('audio');
		$audio->addChild('in', -1);
		$audio->addChild('out', -1 );
		for($channelnum = 1 ; $channelnum <= $this->audioChannel ;$channelnum++ ){
			$audio = $this->addAudioTrack($audio ,1, $channelnum , $this->timeline_info );
		}
		//addMarkInfo
		foreach($this->marker_info as $key => $mark){
			$marker = $seqxml->addChild('marker');
			foreach($mark as $mark_key => $mark_value){
				$marker->addChild($mark_key, $mark_value );
			}
		}
		//$seqxml->addChild('ismasterclip', 'FALSE');

		return $basexml;
	}

	function addTimeCode($xml){
		$timecode = $xml->addChild('timecode');
		$timecode = $this->addRate($timecode);
		$timecode->addChild('string', $this->start_tc );
		$timecode->addChild('frame', $this->start_tc_frame );
		$timecode->addChild('source', 'source' );
		$timecode->addChild('displayformat', 'DF' );
		return $xml;
	}

	function addRate($target){
		$rate = $target->addChild('rate');
		$rate-> addChild('ntsc', 'TRUE');
		$rate-> addChild('timebase', '30');
		return $target ;
	}

	static function TB_ORD_EDL_parser( $ord_id ){
		global $db;
		$check_content_ids = array();
		$content_infos = array();
		$datas = $db->queryAll("select oe.*,o.title from TB_ORD_EDL oe, TB_ORD o where o.ord_id=oe.ord_id and o.ord_id='$ord_id' order by ord_no ");

		//의뢰 목록에서 비디오ID로 콘텐츠 정보 조회
		foreach($datas as $data)
		{
			$content_id = $data[video_id];
			if( in_array($content_id, $check_content_ids) ) continue;

			array_push($check_content_ids,$content_id);

			$content_info = $db->queryRow("select c.content_id,c.ud_content_id, c.title,m.path,s.* from view_bc_content c,bc_media m,bc_sysmeta_movie s where c.content_id='$content_id' and c.content_id=m.content_id and c.content_id=s.sys_content_id(+) and m.media_type='original' and COALESCE(m.status,'0')=0");
			$ud_content_id = $content_info[ud_content_id];
			$us_type = 'highres';
			$storage_info = $db->queryRow("SELECT * FROM VIEW_UD_STORAGE WHERE ud_content_id = '$ud_content_id' and us_type='$us_type'");
			$start_timecode = '00:00:00:00';

			$content_infos[ $content_id ] = array(
				'content_info' => $content_info,
				'storage_info' => $storage_info,
				'start_timecode' => $start_timecode
			);
		}

		foreach($datas as $key => $data)
		{
			$content_id = $data[video_id];
			$datas[$key][content_id]		= $content_id;
			$datas[$key][ud_content_id]		= $content_infos[$content_id][content_info][ud_content_id];
			$datas[$key][start_timecode]	= $content_infos[$content_id][start_timecode];

			$datas[$key][duration]			= $content_infos[$content_id][content_info][sys_video_rt];
			$datas[$key][path]				= $content_infos[$content_id][content_info][path];
			$datas[$key][root]				= $content_infos[$content_id][storage_info][path];
			$xml_title = $data[title];

			//list start end 추가
			if($key == 0){
				$time = new timecode( $data[mark_in], $data[mark_out] );
				$length =  $time->getLength();
				$datas[$key][starttc] = '00:00:00:00';
				$datas[$key][endtc] = timecode::getConvTimecode($length ).':00';
			}else{
				$time = new timecode( $data[mark_in], $data[mark_out] );
				$length =  $time->getLength();

				$bf_sec = timecode::getConvSec( $datas[$key-1][endtc] );
				$end_sec = $bf_sec + $length ;

				$datas[$key][starttc] = $datas[$key-1][endtc];
				$datas[$key][endtc] = timecode::getConvTimecode($end_sec).':00';
			}
		}

		$fcp = new FcpXML();
		$fcp->setTitle( $xml_title );
		$fcp->setAudioChannel(8);
		$fcp->setResolution(1920, 1080);

		foreach($datas as $data)
		{
			$file_path = rtrim($data[root],'/').'/'.ltrim($data[path], '/');
			$start_timecode = $data[start_timecode];
			if( empty($start_timecode) ){
				$start_timecode = '00:00:00;00';
			}
			$duration = $data[sys_video_rt];

			$starttc = $data[starttc];
			$endtc = $data[endtc];
			$intc = $data[mark_in];
			$outtc = $data[mark_out];
			//($filepath, $start_tc, $duration , $in , $out, $start, $end )
			$fcp->addTLInfo($file_path, $start_timecode,$duration, $intc ,$outtc, $starttc, $endtc );
		}

		$xml = $fcp->createFcpXML();

		$target_path = $ord_id.'.'.'xml';

		$return_xml = $xml->asXML();

		$domxml = new DOMDocument();
		$domxml->preserveWhiteSpace = false;
		$domxml->formatOutput = true;
		/* @var $xml SimpleXMLElement */
		$domxml->loadXML($return_xml);

		$return_xml = $dom->saveXML();

		$fcp->_LogFile('',$target_path, $return_xml );
		//$fcp->_PrintFile( $down_path , $xml->asXML() );

		$msg = "EDL이 생성되었습니다";
		return $return_xml;
	}

	static function getMediaInfo( $content_id ){
		global $db;

		return $db->queryRow("selecct * from bc_media where content_id='$content_id '");
	}

	static function _LogFile($filename,$name,$contents){
		$root = $_SERVER['DOCUMENT_ROOT'].'/log/';
		if(empty($filename)){
			$filename = 'FcpXML_Log_'.date('Y-m-d').'.log';
		}
		@file_put_contents($root.$filename, "\n".$_SERVER['REMOTE_ADDR']."\t".date('Y-m-d H:i:s')."]\t".$name." : \n".$contents."\n", FILE_APPEND);
	}

	static function _PrintFile($filename,$contents){
		//$root = $_SERVER['DOCUMENT_ROOT'].'/xml/';
		if(empty($filename)){
			$filename = 'FcpXML_'.date('Y-m-d').'.log';
		}
		$filename = iconv('utf-8','cp949',$filename);
		return @file_put_contents($root.$filename, $contents );
	}
}
?>