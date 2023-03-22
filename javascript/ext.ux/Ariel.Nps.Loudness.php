<?php

use Api\Services\MediaService;
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lib.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

$content_id = $_REQUEST['content_id'];
$content = $db->queryRow("
	SELECT	*
	FROM	BC_CONTENT
	WHERE	CONTENT_ID = $content_id
");

$ud_content_id = $content['ud_content_id'];

/*Storage에 XML파일 추가*/
$loudness_root = $db->queryOne("
	SELECT	PATH
	FROM	BC_STORAGE
	WHERE	STORAGE_ID = 127
	AND		NAME = 'Loudness XML'
");
// $loudness_filename = $content_id.'.xml';
// $loudness_filename = iconv('utf-8','cp949',$loudness_filename);
// $xml_file = $loudness_root.'/'.$loudness_filename;
// @file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/'.basename(__FILE__).'_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] xml_file ===> '.$xml_file."\r\n", FILE_APPEND);
// $xml = simplexml_load_file($xml_file);

$datas = array();
$intergrates = array();
$momentarys = array();
$shortterms= array();
$truepeaks = array();
$loudnessranges = array();

//$logLength = count($xml->loudnesslogs->loudnesslog);
$idx = 1;
$duration = 0;
$maxTimeStamp = 0;
// @file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/'.basename(__FILE__).'_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] xml ===> '.print_r($xml, true)."\r\n", FILE_APPEND);
// foreach ($xml->loudnesslogs->loudnesslog as $item) {
// 	$tmp_data = array();
// 	$attr = $item[0]->attributes();
	
// 	$mediatime = (string)$attr['mediatime'];
// 	$intergrate = (string)$attr['i'];
// 	$momentary = (string)$attr['m'];
// 	$shortterm = (string)$attr['s'];
// 	$truepeak = (string)$attr['tp'];
// 	$loudnessrange = (string)$attr['lra'];

// 	/*Grid용 데이터*/
// 	$tmp_data['mediatime']	= $mediatime;
// 	$tmp_data['i']			= $intergrate;
// 	$tmp_data['m']			= $momentary;
// 	$tmp_data['s']			= $shortterm;
// 	$tmp_data['tp']			= $truepeak;
// 	$tmp_data['lra']		= $loudnessrange;

// 	array_push($datas, $tmp_data);

// 	/*Flot Chart용 데이터*/
// 	$tmp_timeArr = explode('.', $mediatime);
// 	$tmp_time = (strtotime($tmp_timeArr[0]) * 1000) + $tmp_timeArr[1];

// 	$tmp_intergrate = "['".$tmp_time."', '".$intergrate."']";
// 	$tmp_momentary = "['".$tmp_time."', '".$momentary."']";
// 	$tmp_shortterm = "['".$tmp_time."', '".$shortterm."']";
// 	$tmp_truepeak = "['".$tmp_time."', '".$truepeak."']";
// 	$tmp_loudnessrange = "['".$tmp_time."', '".$loudnessrange."']";

// 	array_push($intergrates, $tmp_intergrate);
// 	array_push($momentarys, $tmp_momentary);
// 	array_push($shortterms, $tmp_shortterm);
// 	array_push($truepeaks, $tmp_truepeak);
// 	array_push($loudnessranges, $tmp_loudnessrange);
	
// 	/*마지막 로그에서 Duration 정보 뽑아냄*/
// 	if($idx == $logLength) {
// 		$maxTimeStamp = $tmp_time;
// 		$tmpSecArr = explode(':', $tmp_timeArr[0]);
// 		$duration = (($tmpSecArr[0] * 3600) + ($tmpSecArr[1] * 60) + $tmpSecArr[2]) * 1000 + $tmp_timeArr[1];
// 	}

// 	$idx++;
// }

$start_sec = 0;


$todayTimeStamp = strtotime('00:00:00') * 1000;

$stream_file = $db->queryOne("
	SELECT	PATH
	FROM	BC_MEDIA
	WHERE	CONTENT_ID = $content_id
	AND		MEDIA_TYPE = '".STREAM_FILE."'
");
$stream_path = $stream_file;
$stream_file = addslashes($stream_file).'?tm='.$start_sec;
$stream_path = str_replace('#', '%23', $stream_path);
$stream_path = str_replace('\'', '\\\'', $stream_path);
$video_js_path = LOCAL_LOWRES_ROOT.'/'.$stream_path;
//서비스용 경로 조회
$mediaService = new MediaService($app->getContainer());
$video_js_path = $mediaService->getMediaProxyPath($content_id);

$flashVars = '"mp4:'.$stream_file.'"';
$switch = 'false';
$streamer_addr = STREAMER_ADDR;

//Grid Thumbnail
$thumb_grid_path = $db->queryOne("
					SELECT	PATH
					FROM	BC_MEDIA
					WHERE	CONTENT_ID = $content_id
					AND		MEDIA_TYPE = 'thumb_grid'
				");

$ud_lowres_storage = $db->queryOne("
						SELECT	S.PATH
						FROM	BC_STORAGE S, BC_UD_CONTENT_STORAGE US
						WHERE	S.STORAGE_ID = US.STORAGE_ID
						AND		US.US_TYPE = 'lowres'
						AND		US.UD_CONTENT_ID = $ud_content_id
					");

$show_thumb_grid = 'true';

$thumb_grid_file = "http://".SERVER_HOST.'/data/'.$thumb_grid_path;

$thumbnails = getGridThumbnails($thumb_grid_file, $ud_lowres_storage);

if($thumbnails === 'false') {
	$show_thumb_grid = 'false';
	$thumbnails = '';
}
$frame_rate = $db->queryOne("select sys_frame_rate from bc_sysmeta_movie where sys_content_id = '".$content_id."'");
$frame_rate = floatval($frame_rate);

$loudnessMasterInfo = $db->queryRow("
						SELECT	*
						FROM	TB_LOUDNESS
						WHERE	CONTENT_ID = $content_id
					");
/*Loudness 값에 따라 색상처리하는 부분 추가 - 2018.03.08 Alex*/
$loudness_range_code = $db->queryOne("
							SELECT	C.REF1
							FROM	BC_CODE C, BC_CODE_TYPE CT
							WHERE	C.CODE_TYPE_ID = CT.ID
							AND		CT.CODE = 'LOUDNESS_RANGE'
						");
$loudness_range = explode(',', $loudness_range_code);

if($loudnessMasterInfo['preset'] == 'ATSC A/85 (USA, Korea)') {
	if($loudnessMasterInfo['integrate'] >= $loudness_range[1] && $loudnessMasterInfo['integrate'] <= $loudness_range[0]) {
		$htmlColor = 'green';
	} else {
		$htmlColor = 'red';
	}
} else if($loudnessMasterInfo['preset'] == 'EBU R128 (Europe)') {
	if($loudnessMasterInfo['integrate'] >= $loudness_range[1] && $loudnessMasterInfo['integrate'] <= $loudness_range[0]) {
		$htmlColor = 'green';
	} else {
		$htmlColor = 'red';
	}
}
//@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/'.basename(__FILE__).'_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] intergrates ===> '.print_r($intergrates, true)."\r\n", FILE_APPEND);
//@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/'.basename(__FILE__).'_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] loudnessMasterInfo ===> '.print_r($loudnessMasterInfo, true)."\r\n", FILE_APPEND);
?>

(function(){
	Ext.ns('Ariel');

	var dataStore = new Ext.data.JsonStore({
		data:<?=json_encode($datas)?>,
		reader: new Ext.data.ArrayReader( { id: 'loudness_log_id' }),
		fields: [
			{name : 'mediatime'},
			{name : 'i'},
			{name : 'm'},
			{name : 's'},
			{name : 'tp'},
			{name : 'lra'}
		]
	});

	var g_flot = null;

	var datasets = {
		"intergate": {
			label: "Intergate",
			color: 'green',
			data: <?= '['.implode(',', $intergrates).']'?>
		},
		"momentary": {
			label: "Momentary",
			color: 'cyan',
			data: <?= '['.implode(',', $momentarys).']'?>
		},
		"shortterm": {
			label: "Shortterm",
			color: 'blue',
			data: <?= '['.implode(',', $shortterms).']'?>
		},
		"truepeak": {
			label: "Truepeak",
			color: 'yellow',
			data: <?= '['.implode(',', $truepeaks).']'?>
		}
	};




	Ariel.Nps.Loudness = Ext.extend(Ext.Window, {
		id: 'loudnessWin',
		title: _text('MN02245')+' [<?=addslashes($content['title'])?>]',
		//width: '95%',
		top: 50,
		//height: 700,
		minWidth:  1000,
		minHeight: 700,
		width: Ext.getBody().getViewSize().width*0.7,
		height: Ext.getBody().getViewSize().height*0.8,
		modal: true,
		layout: 'fit',
		maximizable: true,

		initComponent: function(config){
			Ext.apply(this, config || {});

			this.items = {
				border: false,
				layout: 'border',

				items: [{
					region: 'center',
					xype: 'compositefield',
					layout: 'hbox',
					layoutConfig: {
						pack:'center',
						align:'stretch'
					},
					flex: 1,
					items: [{
						width: '50%',
						//html: '<a href=<?=$flashVars?> id="player3"></a>',
						html: '<video id="player3" class="vjs-skin-twitchy video-js vjs-big-play-centered vjs-preview-customize" preload="auto" autoplay controls style="width:100%;height:100%;" data-setup=\'{ "inactivityTimeout": 0, "playbackRates": [0.5, 1, 1.5, 2, 3, 4, 8] , "update_fps":30}\'><source src="<?=$video_js_path?>" type="video/mp4"></video>',
						listeners: {
							afterrender: function(self){
								var frame_rate = <?=$frame_rate?>;
								videojs('player3').ready(function(){
									var timer;
									var playbackRates = JSON.parse(document.getElementById("player3").getAttribute('data-setup')).playbackRates;
									var videojs_player = this,
									controlBar;

									var review_btn = document.createElement('div');
									review_btn.id = 'review_btn';
									review_btn.className = 'vjs-control-custom';
									var review_text = document.createElement('span');
									review_text.className = 'fa fa-lg fa-backward';
									review_btn.appendChild(review_text);
									review_btn.title = _text('MN02425');
									review_btn.onclick = function () {
										
										var curent_rate = videojs_player.playbackRate();
										if (curent_rate > 1) {
											videojs_player.playbackRate(1);
										} else {
											for (var i = 0; i < playbackRates.length; i++) {
												if (playbackRates[i] == curent_rate && i != 0){
													videojs_player.playbackRate(playbackRates[i-1]);
												}
											}	
										}
									};

									var frame_back_btn = document.createElement('div');
									frame_back_btn.id = 'frame_back_btn';
									frame_back_btn.className = 'vjs-control-custom';
									var frame_back_text = document.createElement('span');
									frame_back_text.className = 'fa fa-lg fa-step-backward';
									frame_back_btn.appendChild(frame_back_text);
									frame_back_btn.title = _text('MN02426');
									frame_back_btn.onclick = function () {
										var cur_time = videojs_player.currentTime();
										videojs_player.currentTime(cur_time - 1/frame_rate);
									};

									var frame_next_btn = document.createElement('div');
									frame_next_btn.id = 'frame_next_btn';
									frame_next_btn.className = 'vjs-control-custom';
									var frame_next_text = document.createElement('span');
									frame_next_text.className = 'fa fa-lg fa-step-forward';
									frame_next_btn.appendChild(frame_next_text);
									frame_next_btn.title = _text('MN02429');
									frame_next_btn.onclick = function () {
										var cur_time = videojs_player.currentTime();
										videojs_player.currentTime(cur_time + 1/frame_rate);
									};

									var fast_forward_btn = document.createElement('div');
									fast_forward_btn.id = 'fast_forward_btn';
									fast_forward_btn.className = 'vjs-control-custom';
									var fast_forward_text = document.createElement('span');
									fast_forward_text.className = 'fa fa-lg fa-forward';
									fast_forward_btn.appendChild(fast_forward_text);
									fast_forward_btn.title = _text('MN02430');
									fast_forward_btn.onclick = function () {
										var curent_rate = videojs_player.playbackRate();
										if (curent_rate < 1) {
											videojs_player.playbackRate(1);
										} else {
											for (var i = 0; i < playbackRates.length; i++) {
												if (playbackRates[i] == curent_rate && i != playbackRates.length){
													videojs_player.playbackRate(playbackRates[i+1]);
												}
											}
										}
									};

									var space_div = document.createElement('div');
									space_div.className = 'vjs-control-space-custom';

									// Get control bar and insert before elements
									controlBar = document.getElementsByClassName('vjs-custom-control-spacer')[0];
									var remaining_time = document.getElementsByClassName('vjs-remaining-time')[0];
									remaining_time.style.display = "none";

									var insertBeforeNode = document.getElementsByClassName('vjs-volume-menu-button')[0];
									var play_btn = document.getElementsByClassName('vjs-play-control')[0];
									// Insert the icon div in proper location
									controlBar.appendChild(frame_next_btn);
									controlBar.insertBefore(play_btn,frame_next_btn);
									controlBar.insertBefore(frame_back_btn,play_btn);
									videojs_player.hotkeys({
										volumeStep: 0.1,
										seekStep: 1/frame_rate,
									});
									videojs_player.on('loadedmetadata', function(){
										this.bigPlayButton.hide();
									});
									videojs_player.on('pause', function() {
										this.bigPlayButton.show();
										videojs_player.one('play', function() {
											this.bigPlayButton.hide();
										});
									});
								});
								var videoplayer = document.getElementById("player3");
								var player3 = videojs(videoplayer, {}, function(){});

								if('<?=$show_thumb_grid?>' == 'true') {
									player3.thumbnails({<?=$thumbnails?>});
								}

								if (videoplayer.addEventListener) {
									videoplayer.addEventListener('contextmenu', function(e) {
										e.preventDefault();
									}, false);
								} else {
									videoplayer.attachEvent('oncontextmenu', function() {
										window.event.returnValue = false;
									});
								}
							}
						}
					},{
						xtype: 'grid',
						width: '49%',
						store: dataStore,
						stripeRows: true,
						loadmask: true,
						columns:[
							{header: "TIME", dataIndex: 'mediatime'},
							{header: "INTEGRATE", dataIndex: 'i'},
							{header: "MOMENTARY", dataIndex: 'm'},
							{header: "SHORTTERM", dataIndex: 's'},
							{header: "TRUEPEAK", dataIndex: 'tp'},
							{header: "LOUDNESSRANGE", dataIndex: 'lra'}
						],
						listeners: {
							rowclick: function(self, idx, e){
								var select = self.getSelectionModel().getSelected();
								if(!Ext.isEmpty(select)) {
									var tmp_time = select.get('mediatime').split('.');
									var timeCodeArr = tmp_time[0].split(':');
									var sec = parseInt(timeCodeArr[0]) * 3600 + parseInt(timeCodeArr[1]) * 60 + parseInt(timeCodeArr[2]);

									videojs('player3').currentTime(sec);
									
									g_flot.getOptions().grid.markings = [];

									var selectTimeStamp = <?=$todayTimeStamp?> + (sec*1000) + parseInt(tmp_time[1]);

									g_flot.getOptions().grid.markings.push(
												{xaxis: {from: selectTimeStamp, to: selectTimeStamp}, color: "#ff0000" }, 
												{yaxis: {from: -24, to: -24}, color: "#ff0000" });
									g_flot.setupGrid();
									g_flot.draw();
								}
							}
						}
					}]
				},{
					region: 'south',
					height: 350,
					html: '<div id="placeholder" style="float:left; width:80%; height:100%;"></div><div style="float:right; width:20%;"><div id="choices" style="height:100px"></div><div id="summaryloudness"></div></div>'
				}]
			};

			Ariel.Nps.Loudness.superclass.initComponent.call(this);
		}
	});
	//new Ariel.Nps.Loudness().show();
	
	new Ariel.Nps.Loudness().show(this, function(){
		var i = 10;
//		$.each(datasets, function(key, val) {
//			val.color = i;
//			++i;
//		});

		// insert checkboxes 
		var choiceContainer = $("#choices");
		$.each(datasets, function(key, val) {
			choiceContainer.append("<br/><input type='checkbox' name='" + key +"' checked='checked' id='id" + key + "'></input>" +
				"<div style='width:13px;height:10px;display:inline-block;margin-left:3px;background-color:" + val.color + ";'></div>" +
				"<label for='id" + key + "' style='margin-left:3px;'>"+ val.label + "</label>");
		});

		var summaryContainer = $("#summaryloudness");

		summaryContainer.append('<br/><table><tr><td style="width:130px;font-size:20px;font-weight:bold;color:<?=$htmlColor?>;">Integrate(LKFS)</td><td style="width:20px;"></td><td style="font-size:20px;font-weight:bold;color:<?=$htmlColor?>;"><?=round($loudnessMasterInfo['integrate'], 1)?></td></table><br/><br/><br/><br/>');

		summaryContainer.append('<table><tr><td style="width:130px;">Integrate(LKFS)</td><td><?=round($loudnessMasterInfo['integrate'], 1)?></td></tr><tr><td style="width:130px;">Max Momentary(LKFS)</td><td><?=round($loudnessMasterInfo['max_momentary'], 1)?></td></tr>'
					+ '<tr><td style="width:130px;">Max Short-term(LKFS)</td><td><?=round($loudnessMasterInfo['max_shortterm'], 1)?></td></tr><tr><td style="width:130px;">Max True Peak(dBTP)</td><td><?=round($loudnessMasterInfo['max_truepeak'], 1)?></td></tr>'
					+ '<tr><td style="width:130px;">Loudness Range(LU)</td><td><?=round($loudnessMasterInfo['loudnessrange'], 1)?></td></tr></table>');
		

		choiceContainer.find("input").click(plotAccordingToChoices);
		
		function plotAccordingToChoices() {

			var data = [];

			choiceContainer.find("input:checked").each(function () {
				var key = $(this).attr("name");
				if (key && datasets[key]) {
					data.push(datasets[key]);
				}
			});

			var plot = $.plot("#placeholder", data, {
				legend: {
				//	position: "nw"
					show: false
				},
				series: {
					lines: {
						show: true
					},
					shadowSize: 0
				},
				xaxis: {
					mode: "time",
					timezone: 'browser',
					timeformat: "%H:%M:%S",
					//panRange: 좌우 이동 범위 (타임스탬프이므로 현재날짜00시/현재날짜+Duration)
					panRange: [<?=$todayTimeStamp?>, <?=$maxTimeStamp?>],
					//zoomRange : 최대확대시 간격(10초) / 최대 축소시 간격(Duration)
					zoomRange: [10000, <?=$duration?>]
				},
				yaxis: {
					ticks: [0, -4, -8, -12, -16, -20, -24, -30, -40, -50],
					min: -60,
					max: 0,
					panRange: [-60, 0],
					zoomRange: [1, 60]
				},
				zoom: {
					interactive: true
				},
				pan: {
					interactive: true
				},
				crosshair: {
					mode: "x"
				},
				grid: {
					markings: [{
						yaxis: {
							from: -24 ,
							to: -24
						}, 
						color: "#ff0000"
					}],
					markingsLineWidth: 2.5
				}
			});

			g_flot = plot;

			var placeholder = $("#placeholder");

			placeholder.bind("plotpan", function (event, plot) {
				var axes = plot.getAxes();
				//console.log("plotpan");
			});

			placeholder.bind("plotzoom", function (event, plot) {
				var axes = plot.getAxes();
			});

			placeholder.bind("plotzoom", function (event, plot) {
				var axes = plot.getAxes();
			});

			placeholder.bind("plotclick", function (event, plot, item) {
				console.log("item", item);
			});

			$("<div class='button' style='right:20px;top:20px'>zoom out</div>")
				.appendTo(placeholder)
				.click(function (event) {
					//console.log('zoomout click');
					event.preventDefault();
					plot.zoomOut();
				});

			function addArrow(dir, right, top, offset) {
				$("<img class='button' src='img/arrow-" + dir + ".gif' style='right:" + right + "px;top:" + top + "px'>")
					.appendTo(placeholder)
					.click(function (e) {
						e.preventDefault();
						plot.pan(offset);
					});
			}

			addArrow("left", 55, 60, { left: -100 });
			addArrow("right", 25, 60, { left: 100 });
			addArrow("up", 40, 45, { top: -100 });
			addArrow("down", 40, 75, { top: 100 });
		}

		plotAccordingToChoices();
	});
})()