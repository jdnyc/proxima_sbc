<?php

use Api\Services\MediaService;
session_start();
require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/functions.php');

$content_id = $_REQUEST['content_id'];

$start_sec = 0;
$content_info = $db->queryRow("
					SELECT	TITLE, UD_CONTENT_ID
					FROM	BC_CONTENT
					WHERE	CONTENT_ID = $content_id
				");
$title = $content_info['title'];
$ud_content_id = $content_info['ud_content_id'];
$mediaInfo = $db->queryRow("
					SELECT	PATH,(select VIRTUAL_PATH from bc_storage where storage_id=m.storage_id ) mid_path
					FROM	BC_MEDIA m
					WHERE	CONTENT_ID = $content_id
					AND		MEDIA_TYPE = '".STREAM_FILE."'
                ");
$stream_file = $mediaInfo['path'];
$stream_path = $stream_file;
$stream_file = addslashes($stream_file).'?tm='.$start_sec;
$flashVars = '"mp4:'.$stream_file.'"';
$switch = 'true';
$stream_path = str_replace('#', '%23', $stream_path);
$stream_path = str_replace('\'', '\\\'', $stream_path);

if( !empty($mediaInfo['mid_path']) ){
    $video_js_path = $mediaInfo['mid_path'].'/'.$stream_path;
}else{
    $video_js_path = LOCAL_LOWRES_ROOT.'/'.$stream_path;
}
//서비스용 경로 조회
$mediaService = new MediaService($app->getContainer());
$video_js_path = $mediaService->getMediaProxyPath($content_id);

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

?>
(function(){
	var player_win = new Ext.Window({
		id: 'cuesheet_player_win',
		border: false,
		layout: 'fit',
		split: true,
		title: '<?=addslashes($title)?>',
		width: 640,
		height: 480,
		modal: true,
		items: [{
			width: 800,
			height: '50%',
			minWidth: 480,
			minHeight: 300,
			//html: '<a href=<?=$flashVars?> id="player3"></a>',
			html: '<video id="player3" class="vjs-skin-twitchy video-js vjs-big-play-centered vjs-preview-customize" preload="auto" autoplay controls style="width:100%;height:100%;" data-setup=\'{ "inactivityTimeout": 0, "playbackRates": [0.5, 0.8, 1, 1.5, 2, 3, 4, 8] , "update_fps":30}\'><source src="<?=$video_js_path?>" type="video/mp4"></video>',
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

						var frame_three_back_btn = document.createElement('div');
						frame_three_back_btn.id = 'frame_three_back_btn';
						frame_three_back_btn.className = 'vjs-control-custom';
						var frame_three_back_text = document.createElement('span');
						frame_three_back_text.className = 'fa fa-lg fa-backward';
						frame_three_back_btn.appendChild(frame_three_back_text);
						frame_three_back_btn.title = '3초 뒤로';
						frame_three_back_btn.onclick = function() {
							var cur_time = videojs_player.currentTime();
							videojs_player.currentTime(cur_time - 90 /frame_rate);
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

						var frame_three_next_btn = document.createElement('div');
						frame_three_next_btn.id = 'frame_three_next_btn';
						frame_three_next_btn.className = 'vjs-control-custom';
						var frame_three_next_text = document.createElement('span');
						frame_three_next_text.className = 'fa fa-lg fa-forward';
						frame_three_next_btn.appendChild(frame_three_next_text);
						frame_three_next_btn.title = '3초 앞으로';
						frame_three_next_btn.onclick = function() {
							var cur_time = videojs_player.currentTime();
							videojs_player.currentTime(cur_time + 90 /frame_rate);
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
						controlBar.appendChild(frame_three_back_btn);
						controlBar.appendChild(frame_three_next_btn);
						controlBar.insertBefore(play_btn,frame_next_btn);
						controlBar.insertBefore(frame_back_btn,play_btn);
						controlBar.insertBefore(frame_three_back_btn,frame_back_btn);
						controlBar.insertBefore(frame_next_btn, frame_three_next_btn);
						
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
		}],
		listeners: {
			show : function(win) {
				document.onkeyup = function(evt) {
				    evt = evt || window.event;
				    if (evt.keyCode == 27) {
						if(!Ext.isEmpty(player3)) {
							if(!player3.isFullscreen()) {
								win.close();		
							}
						} else {
							win.close();
						}
				    }
				};
	        },
			close: function(){				
				videojs('player3').dispose();

				var grid = Ext.getCmp('tab_warp').getActiveTab().get(0);
				if(!Ext.isEmpty(Ext.getCmp('tab_warp'))) {
					grid.restoreScroll();
					//2018.02.13 khk 포커스를 하면 스크롤이 흔들린다. 이걸 해결하기 전에는 안될듯...
					//grid.getView().focusRow(grid.getSelectionModel().getSelected());											
				}
			}
		}
	});		
	return player_win;			
})()