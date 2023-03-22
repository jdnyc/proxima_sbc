<?php

use Api\Services\MediaService;
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/timecode.class.php');



$content_id = $_REQUEST['content_id'];
$user_id = $_SESSION['user']['user_id'];
$mode = $_REQUEST['mode'];
$request_id = $_REQUEST['request_id'];
$is_watch_meta = $_REQUEST['is_watch_meta'];//watchfolder meta confirm button
$targetGrid = $_REQUEST['targetGrid'];

if(empty($targetGrid)) { 
	$targetGrid = 'false';
} else {
	$targetGrid = 'true';
}

$content = $db->queryRow("select c.ud_content_id, c.title, c.bs_content_id, c.is_group, m.ud_content_title as meta_type_name, c.reg_user_id from bc_content c, bc_ud_content m where c.content_id={$content_id} and c.ud_content_id=m.ud_content_id");
$ud_content_id = $content['ud_content_id'];
$multi_list = $db->queryRow("select * from bc_usr_meta_field where usr_meta_field_type='listview' and ud_content_id='$ud_content_id' and usr_meta_field_title='TC정보' ");
$is_group = $content['is_group'];
$bs_content_id = $content['bs_content_id'];
$frame_rate = $db->queryOne("select sys_frame_rate from bc_sysmeta_movie where sys_content_id = '".$content_id."'");
$frame_rate = floatval($frame_rate);

// tag config useing option
$useTagConfig = false;

// 격자이미지 관련 테스트

$thumb_grid_path = $db->queryOne("
					SELECT	PATH
					FROM	BC_MEDIA
					WHERE	CONTENT_ID = $content_id
					AND		MEDIA_TYPE = 'thumb_grid'
                ");
if ($thumb_grid_path) {
    $ud_lowres_storage = $db->queryOne("
						SELECT	S.PATH_FOR_UNIX
						FROM	BC_STORAGE S, BC_UD_CONTENT_STORAGE US
						WHERE	S.STORAGE_ID = US.STORAGE_ID
						AND		US.US_TYPE = 'lowres'
						AND		US.UD_CONTENT_ID = $ud_content_id
					");

    $thumb_grid_file = LOCAL_LOWRES_ROOT.'/'.$thumb_grid_path;

    $thumbnails = getGridThumbnails($thumb_grid_file, $ud_lowres_storage);
    $show_thumb_grid = 'true';
    if($thumbnails === 'false') {
        $show_thumb_grid = 'false';
        $thumbnails = '';
    }

}else{
	$show_thumb_grid = 'false';
	$thumbnails = '';
}
if (!$frame_rate){
	$frame_rate = FRAMERATE;
}
$columns = '';
$usr_meta_field_id = '';
//$streamer_addr = STREAMER_ADDR.'/vod';
$is_sound = false;
$start_sec = 0;

//서비스용 경로 조회
$mediaService = new MediaService($app->getContainer());
$video_js_path = $mediaService->getMediaProxyPath($content_id);
$originalMedia = $mediaService->getMediaOriginal($content_id);

$originalExt = null;
if( !empty($originalMedia->path)  ){
    if( $originalMedia->stataus != 1 ){
        $pathArray = explode('.', $originalMedia->path);
        $originalExt = array_pop($pathArray);
        $originalExt = strtoupper($originalExt);
    }
}

// $stream_file = $db->queryOne("SELECT PATH FROM BC_MEDIA WHERE CONTENT_ID={$content_id} AND MEDIA_TYPE='".STREAM_FILE."' AND FILESIZE > '0' ORDER BY MEDIA_ID");
// $stream_path = $stream_file;
// $stream_path = str_replace('#', '%23', $stream_path);
// $stream_path = str_replace('\'', '\\\'', $stream_path);
// $video_js_path = LOCAL_LOWRES_ROOT.'/'.$stream_path;
//$video_js_path = LOCAL_LOWRES_ROOT.'/test2.mp4';

$html_text = '<video id="player3" class="vjs-skin-twitchy video-js vjs-big-play-centered" preload="auto" autoplay controls style="width:100%;height:100%;" data-setup=\\\'{ "inactivityTimeout": 0, "playbackRates": [0.5, 0.8,1, 1.5, 2, 3, 4, 8], "frame_rate":'.$frame_rate.', "update_fps":30 }\\\'><source src="'.$video_js_path.'" type="video/mp4"></video>';

// $flashVars = '"mp3:'.$stream_file.'"';
// $switch = 'false';
// if ( $content['bs_content_id'] == MOVIE || $content['bs_content_id'] == SEQUENCE)
// {
// 	$thumb_file = $db->queryOne("SELECT PATH FROM BC_MEDIA WHERE CONTENT_ID={$content_id} AND MEDIA_TYPE='thumb'");
// 	if (empty($thumb_file))
// 	{
// 		$thumb_file = 'img/process.jpg';
// 	}

// 	$thumb_file = addslashes($thumb_file);
// //	$flashVars = str_replace('mp3:', 'mp4:', $flashVars).'", image: "'.WEB_PATH.'/'.$thumb_file.'"';
// 	$flashVars = str_replace('mp3:', 'mp4:', $flashVars);
// 	$switch = 'true';
// }

//PFR 가능 여부 판단
//mxf, mov만 가능하고
//mxf일경우엔 mpeg2video, 8채널오디오인 경우만 가능. mpeg2video (4:2:2)
//2016-01-19
//원본 존재 : agent agent pfr,
//원본 삭제 && flashnet 사용 : flashnet pfr
if ( $content['bs_content_id'] == MOVIE ) {
	$pfr_err_msg = '';
	$audio_channel = '';
	$video_codec = '';
	$ori_media = $db->queryRow("select * from bc_media where content_id='".$content_id."' and media_type='original'");
	$path_array = explode('.', $ori_media['path']);
	$ori_ext = array_pop($path_array);
	if(strtoupper($ori_ext) == 'MOV') {
	} else if (strtoupper($ori_ext) == 'MXF') {
		$arr_sys_meta = $db->queryRow("select * from bc_sysmeta_movie where sys_content_id='".$content_id."'");
		if( strstr($arr_sys_meta['sys_video_codec'], 'mpeg2video') ) {
			//MXF는 이 경우에만 가능
		} else {
			//$pfr_err_msg = "MXF영상은 mpeg2video인 경우에 가능합니다.";
			$pfr_err_msg = _text('MSG02514');
		}
	} else {
		//$pfr_err_msg = "MXF, MOV 영상만 구간추출이 가능합니다.";
		$pfr_err_msg = _text('MSG02513');
	}

	$media_status = trim($ori_media['status']);
	$media_delete_date = trim($ori_media['delete_date']);
	$media_filesize = trim($ori_media['filesize']);

	//원본 존재 여부
	if( ($media_status == 0) && empty($media_delete_date) &&  ($media_filesize > 0)){
		$flag_ori = 'Y';
	} else {
		$flag_ori = 'N';
	}
}

///////멀티리스트 추가/수정 창 구현 함수 by 이성용 2011-03-03
function getListViewForm($columns, $usr_meta_field_id)
{
	$asciiA = 65;
	$columns = explode(';', $columns);
	$columnCount = count($columns);

	$check_datanm = false;

	foreach ($columns as $v)
	{
		if($v=='순번' )
		{
			$result[] = "{ readOnly: true , fieldLabel: '$v', anchor:'90%' , name: 'column".chr($asciiA++)."'}";
		}
		else if( $v=='내용'|| $v== '비고' )
		{
			$result[] = "{ xtype:'textarea', fieldLabel: '$v', anchor:'90%'  , name: 'column".chr($asciiA++)."'}";
		}
		else if($v == '길이' )
		{
			$result[] = "{ fieldLabel: '$v', anchor:'90%'  , name: 'column".chr($asciiA++)."'}";

		}
		else if( $v == '카테고리' )
		{
			if($usr_meta_field_id == '11879136')
			{
				$root_category = findCategoryRoot(CLEAN); //메타테이블아이디를 루트로 설정 by 이성용
				if( $root_category )
				{
					$root_category_id = $root_category['id'];
					$root_category_text = $root_category['title'];
					$category_path = substr(getCategoryPath('3936212').'/'.'3936212', 11);
				//	$category_path ='3936425/3936571';

				}

				if(empty($category_path)) $category_path = '0';


			$result[] = 	"{
					xtype: 'treecombo',
					id: 'tc_category',
					fieldLabel: '카테고리',
					treeWidth: '300',
					anchor:'90%',
					name: 'column".chr($asciiA++)."',
					autoScroll: true,
					pathSeparator: ' -> ',
					rootVisible: false,
					value: '$category_path',
					loader: new Ext.tree.TreeLoader({
						url: '/store/get_categories.php',
						baseParams: {
							action: 'get-folders',
							path: '$category_path'
						},
						listeners: {
							load: function(self, node, response){

								var path = self.baseParams.path;

								if(!Ext.isEmpty(path) && path != '0'){
									path = path.split('/');

									var id = path.shift();
									self.baseParams.path = path.join('/');

									var n = node.findChild('id', id);
									if(!Ext.isEmpty(n))
									{
										if(n && n.isExpandable()){
											n.expand();
										}else{
											n.select();
											Ext.getCmp('tc_category').setValue(n.id);
										}
									}
								}else{
									node.select();
									Ext.getCmp('tc_category').setValue(node.id);
								}
							}
						}
					}),
					root: new Ext.tree.AsyncTreeNode({
						id: $root_category_id,
					//	text: '$root_category_text',
						expanded: true
					})
				}";
			}
		}
		else if($v=='방송일자' || $v =='촬영일자')
		{
			$result[] = "{xtype:'datefield', fieldLabel: '$v',format: 'Y-m-d',
												altFormats: 'Y-m-d H:i:s|Y-m-d|Ymd|YmdHis',
												editable: true, anchor:'90%'  , name: 'column".chr($asciiA++)."'}";
		}
		else if($v=='자료명')
		{
			$check_datanm = true;
			$datanm_field = "{ hidden: true, anchor:'90%'  , name: 'column".chr($asciiA++)."'}";
		}
		else if( $v == '계절' )
		{
			$result[] = "{
							xtype:'combo',
							fieldLabel: '$v',
							mode: 'local',
							triggerAction: 'all',
							typeAhead: true,
							editable: true,
							anchor:'90%' ,
							store:  [
								['봄','봄'],
								['여름','여름'],
								['가을','가을'],
								['겨울','겨울']
							],
							name: 'column".chr($asciiA++)."'
						}";
		}
		else if( $v == '언어' )
		{
			$result[] = "{
							xtype:'combo',
							fieldLabel: '$v',
							mode: 'local',
							triggerAction: 'all',
							typeAhead: true,
							editable: true,
							anchor:'90%' ,
							store:  [
								['독일어','독일어'],
								['라틴어','라틴어'],
								['러시아어','러시아어'],
								['스페인어','스페인어'],
								['슬라브어','슬라브어'],
								['영어','영어'],
								['이태리어','이태리어'],
								['일본어','일본어'],
								['중국어','중국어'],
								['프랑스어','프랑스어'],
								['한국어','한국어'],
								['헝가리어','헝가리어'],
								['기타','기타']
							],
							name: 'column".chr($asciiA++)."'
						}";
		}
		else if( $v == '촬영구분' )
		{
			$result[] = "{
							xtype:'combo',
							fieldLabel: '$v',
							mode: 'local',
							triggerAction: 'all',
							typeAhead: true,
							editable: true,
							anchor:'90%' ,
							store:  [
								['국내','국내'],
								['해외','해외']
							],
							name: 'column".chr($asciiA++)."'
						}";
		}
		else if( $v == '소재구분' )
		{
			$result[] = "{
							xtype:'combo',
							fieldLabel: '$v',
							mode: 'local',
							triggerAction: 'all',
							typeAhead: true,
							editable: true,
							anchor:'90%' ,
							store:  [
								['1:1 편집본','1:1 편집본'],
								['2:1 편집본','2:1 편집본'],
								['완성편집본','완성편집본'],
								['VCR 편집본','VCR 편집본'],
								['방송본','방송본'],
								['스튜디오촬영본','스튜디오촬영본'],
								['촬영본','촬영본'],
								['6MM 촬영본','6MM 촬영본'],
								['COPY본','COPY본'],
								['코너자료','코너자료'],
								['인서트모음','인서트모음'],
								['FILLER','FILLER']
							],
							name: 'column".chr($asciiA++)."'
						}";
		}
		else if( $v == '촬영기법' )
		{
			$result[] = "{
							xtype:'combo',
							fieldLabel: '$v',
							mode: 'local',
							triggerAction: 'all',
							typeAhead: true,
							editable: true,
							anchor:'90%' ,
							store:  [
								['일반','일반'],
								['항공','항공'],
								['특수','특수'],
								['수중','수중']
							],
							name: 'column".chr($asciiA++)."'
						}";
		}
		else
		{
			$result[] = "{ fieldLabel: '$v', anchor:'90%'  , name: 'column".chr($asciiA++)."'}";
		}
	}
	if( $check_datanm )
	{
		$result[]  =	$datanm_field ;
	}
	$result[] = "{anchor:'90%'  , name: 'meta_value_id', hidden: true }";

	return array(
		'columnHeight' => ($columnCount * 45 + 20),
		'columns' => join(",\n", $result)
	);
}

?>
(function(){
	Ext.ns('Ariel');

	var that = this;
	var lastSeekPos = 0;
    var content_id = <?=$content_id?>;
    var ud_content_id = <?=$ud_content_id?>;
	var frame_rate = <?=$frame_rate?>;
    var setTime;//구간반복용

	<?php
	// custom functions
	if(defined('CUSTOM_ROOT')) {
		if(class_exists('\ProximaCustom\core\DetailPanelCustom')) {
			\ProximaCustom\core\DetailPanelCustom::renderCustomFunctions();
		}	
	}
    ?>
    
    // 비디오 플레이어에서 설정한 mark in/out 값을 조회
    function getMarkInOut() {
        var markers = videojs('player3').markers.getMarkers();
        var setInSec, setInTC, setOutSec, setOutTC;
        for (var i = 0; i < markers.length; i++) {
            if (markers[i].mark_type == 'MARK_IN'){
                setInSec = markers[i].time;
                setInTC = secFrameToTimecode(setInSec,frame_rate)
            } else if (markers[i].mark_type == 'MARK_OUT'){
                setOutSec = markers[i].time;
                setOutTC = secFrameToTimecode(setOutSec,frame_rate)
            }
        }

        var duration = setOutSec - setInSec + Math.floor(1/frame_rate);
        var durationTC = secFrameToTimecode(duration, frame_rate);
        var mark = {
            markIn: setInSec,
            markOut: setOutSec,
            markInTC: setInTC,
            markOutTC: setOutTC,
            duration: duration,
            durationTC: durationTC
        }
        
        // console.log(mark);
        return mark;
    }

    //In/Out리스트에서 선택한 항목을 삭제
    function deleteItem()
	{
		Ext.Msg.show({
			title: '확인',
			msg: '선택하신 항목을 삭제하시겠습니까?',
			icon: Ext.Msg.QUESTION,
			buttons: Ext.Msg.OKCANCEL,
			fn: function(btnId){
				if (btnId == 'ok')
				{
					Ext.getCmp('user_pfr_list').store.remove(Ext.getCmp('user_pfr_list').getSelectionModel().getSelections());
				}
			}
		})
    }
    
    function loop_play(videojs_player, set_in_time, set_out_time) {
        var current_time = 0;
        setTime = setInterval(function(){
            current_time = videojs_player.currentTime();
            if(current_time > set_out_time) {
                videojs_player.currentTime(set_in_time);
            }
            if(videojs_player.paused()) {
                end_loop_play();
            }
        }, 100);
    }
    function end_loop_play() {
        clearInterval(setTime);
    }

	function SeekHtmlPlayer(sec) {
		var cur_time = player3.currentTime();

		player3.currentTime(cur_time + sec);
	}

	function SeekFlowPlayer(pos)
	{
		if(pos > 0)
		{
			//pos초 앞으로
			var curPos = parseInt($f('player2').getTime());
			var toPos = curPos + pos;


			if(lastSeekPos == toPos)
			{
				toPos = toPos + 1;
			}
			var clip = $f('player2').getClip();



			if( toPos > clip.duration )
			{
				toPos = clip.duration;
			}

			lastSeekPos = toPos;
			$f('player2').seek(toPos);
		}
		else
		{
			//pos초 뒤로
			var curPos = parseInt($f('player2').getTime());
			var toPos = curPos + pos;
			if(lastSeekPos == toPos)
			{
				toPos = toPos - 1;
			}
			if( toPos < 0 )
			{
				toPos = 0;
			}
			lastSeekPos = toPos;
			$f('player2').seek(toPos);
		}
	}

	var EditCutDropZoneOverrides = {
		onContainerOver : function(ddSrc, evtObj, ddData) {
			var destGrid  = this.grid;
			var tgtEl    = evtObj.getTarget();
			var tgtIndex = destGrid.getView().findRowIndex(tgtEl);
			this.clearDDStyles();

			// is this a row?
			if (typeof tgtIndex === 'number') {
				var tgtRow       = destGrid.getView().getRow(tgtIndex);
				var tgtRowEl     = Ext.get(tgtRow);
				var tgtRowHeight = tgtRowEl.getHeight();
				var tgtRowTop    = tgtRowEl.getY();
				var tgtRowCtr    = tgtRowTop + Math.floor(tgtRowHeight / 2);
				var mouseY       = evtObj.getXY()[1];
				// below
				if (mouseY >= tgtRowCtr) {
					this.point = 'below';
					tgtIndex ++;
					tgtRowEl.addClass('gridRowInsertBottomLine');
					tgtRowEl.removeClass('gridRowInsertTopLine');
				}
				// above
				else if (mouseY < tgtRowCtr) {
					this.point = 'above';
					tgtRowEl.addClass('gridRowInsertTopLine');
					tgtRowEl.removeClass('gridRowInsertBottomLine');
				}
				this.overRow = tgtRowEl;
			}
			else {
				tgtIndex = destGrid.store.getCount();
			}
			this.tgtIndex = tgtIndex;

			destGrid.body.addClass('gridBodyNotifyOver');

			return this.dropAllowed;
		},
		notifyOut : function() {
			this.clearDDStyles();
		},
		clearDDStyles : function() {
			this.grid.body.removeClass('gridBodyNotifyOver');
			if (this.overRow) {
				this.overRow.removeClass('gridRowInsertBottomLine');
				this.overRow.removeClass('gridRowInsertTopLine');
			}
		},
		onContainerDrop : function(ddSrc, evtObj, ddData){
			var grid        = this.grid;
			var srcGrid     = ddSrc.view.grid;
			var destStore   = grid.store;
			var tgtIndex	= this.tgtIndex;
			var records     = ddData.selections;
			var table		= this.table;
			var id_field	= this.id_field;

			this.clearDDStyles();

			var srcGridStore = srcGrid.store;
			Ext.each(records, srcGridStore.remove, srcGridStore);

			if (tgtIndex > destStore.getCount()) {
				tgtIndex = destStore.getCount();
			}
			destStore.insert(tgtIndex, records);

			var idx = 1;
			var p = new Array();
			//업데이트 time
			var newRecord = new Array();
			srcGridStore.each(function(r){
				var index = r.store.indexOfId( r.id );

				if(index == 0){
					var startsec = 0;
					var start = '00:00:00:00';
					var end = r.get('duration');
					var endsec = r.get('durationsec');

				}else{
					var bfRecord = r.store.getAt( index - 1 );
					var start = bfRecord.get('endtc');
					var startsec = bfRecord.get('endsec');
					var endsec = bfRecord.get('endsec') + r.get('durationsec');
					var end =   secFrameToTimecode(endsec, frame_rate);
				}

				r.set('start_frame', start);
				r.set('end_frame', end);
				r.set('startsec', startsec);
				r.set('endsec', endsec);

				r.commit();
			});
			var datas = [];
			var datalist = grid.getStore().getRange();
			Ext.each(datalist,function(r){
				datas.push(r.data);
			});

			Ext.getCmp('player_warp').Save(grid, content_id, 'shot_list', 'add', datas , true ,true );
			return true;
		}
	};
	
	Ariel.DetailWindow = Ext.extend(Ext.Window, {
		id: 'winDetail',
		//title: _text('MN00137')+' [<?=addslashes($content['title'])?>]',
		//등록대기와 dcart에서 사용하기 위해 주석처리 by 이성용 2011-1-20
		//'<?=$content['meta_type_name']?> 상세보기 [<?=addslashes($content['title'])?>]',
		editing: <?=$editing ? 'true,' : 'false,'?>
		//top: 50,
		//height: 700,
		//minWidth:  900,
		//width:  1050,
		//minHeight: 500,
		modal: true,
		layout: 'fit',
		width: Ext.getBody().getViewSize().width*0.9,
		height: Ext.getBody().getViewSize().height*0.9,
		//maximizable: true,
		//maximized: true,
		draggable : false,//prevent move
		targetGrid: <?=$targetGrid?>,
		listeners: {
			render: function(self){
				// Ext.getCmp('grid_thumb_slider').hide();
				// Ext.getCmp('grid_summary_slider').hide();
				self.mask.applyStyles({
					"opacity": "0.5",
					"background-color": "#000000"
				});
				//self.setSize(1150,680);
				//self.setPosition('150','100');

				//var pos = self.getPosition();
				//if(pos[0]<0)
				//{
				//	self.setPosition(0,pos[1]);
				//}
				//else if(pos[1]<0)
				//{
				//	self.setPosition(pos[0],0);
				//}
				var width_side = Ext.getBody().getViewSize().width*0.9/2-8;
				if(width_side > 620) {
					Ext.getCmp('left_side_panel').setWidth(width_side);
				}
			},
			move: function(self, x, y){//창이 윈도우 포지션을 벗어났을때 0으로 셋팅
				var pos = self.getPosition();
				if(pos[0]<0)
				{
					self.setPosition(0,pos[1]);
				}
				else if(pos[1]<0)
				{
					self.setPosition(pos[0],0);
				}
			},

			close: function(self){
				//CANPN 20160225 reload store of ActiveTab
				if('<?=$_REQUEST['win_type']?>' == 'zodiac'){

				}else{
					Ext.getCmp('tab_warp').getActiveTab().get(0).getStore().reload();
					// Ext.getCmp('grid_thumb_slider').show();
					// Ext.getCmp('grid_summary_slider').show();
					if(self.targetGrid) {
						var grid = Ext.getCmp('tab_warp').getActiveTab().get(0);
						//grid.getView().focusRow(grid.getSelectionModel().getSelected());
					}
                }
                end_loop_play();
				videojs('player3').dispose();
			},
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
	        }

		},

		initComponent: function(config){
			Ext.apply(this, config || {});
			var canvas = document.createElement('canvas');

			var group_child_store = new Ext.data.JsonStore({
				url: '/store/group/get_child_list.php',
				autoLoad: false,
				root: 'data',
				fields: [
					'content_id', 'title', 'bs_content_id', 'thumb', 'sys_ori_filename', 'ori_path'
				],
				baseParams: {
					content_id: <?=$content_id?>,
					bs_content_id: <?=$bs_content_id?>
				},
				listeners: {
					load: function(self, records, opt) {
						groupChildloadDD(this);
					}
				}
			});

			function groupChildThumb(v, m, r) {
				var content_id = r.get('content_id');
				var bs_content_id = r.get('bs_content_id');
				//v = "/img/incoming.jpg";
				if(bs_content_id == 515) {
					var img = '<img id="thumb-group-child-' + content_id + '" onload="resizeImg(this, {w:45, h:30})" src="/img/audio_thumb.png" />';
				} else {
					var img = '<img id="thumb-group-child-' + content_id + '" draggable="true" onload="resizeImg(this, {w:45, h:30})" src="/data/' + v + '" />';
				}

				return img;
			}

			function groupChildloadDD(self) {
				var child_grid = Ext.getCmp('group_child_list');
				child_grid.getStore().each(function(r){
					var content_id = r.get('content_id');
					var thumb_child = document.getElementById('thumb-group-child-' + content_id);

					if(!Ext.isEmpty(thumb_child)) {
						thumb_child.addEventListener("dragstart", function(evt) {
							if(Ext.isChrome ) {
								var content_selected = Ext.getCmp('tab_warp').getActiveTab().get(0).getSelectionModel().getSelected();
								var win_highres_path = content_selected.get('highres_path');

								if(Ext.isMac) {
									win_highres_path = content_selected.get('highres_mac_path');
								}

								var child_sm = child_grid.getSelectionModel();
								if(child_sm.hasSelection()) {
									var records = child_sm.getSelections();
									var rs = [];

									Ext.each(records, function(r) {
										var highres_path = r.json.ori_path;

										var t_path = '"' + win_highres_path+'/'+highres_path+ '"';

										rs.push(t_path);
									});


									var media_child_list = rs.join(',');
									
									if(!Ext.isMac) {
										media_child_list = media_child_list.replace(/\//gi, '\\\\');
									}
									path = 'application/gmsdd:{"medias" : [' + media_child_list + ']}';
								}
			
								//Ext.getCmp('group_child_list').getSelectionModel().selectFirstRow();
								//Ext.getCmp('group_child_list').fireEvent('rowclick', Ext.getCmp('group_child_list'), 0)
							}
							evt.dataTransfer.setData("DownloadURL", path);
						}, false);
					}
				});
				return true;
			}

			function fn_selected_marker(mark_id) {
				var markers = videojs('player3').markers.getMarkers();

				for (var i = 0; i < markers.length; i++) {
					if (markers[i].mark_type == 'MARK'){
						$('.markers_'+markers[i].mark_id).children().removeClass('fa-lg');
						$('.markers_'+markers[i].mark_id).children().css('margin-top','-12px');
						if (markers[i].mark_id == mark_id){
							$('.markers_'+mark_id).children().addClass('fa-lg');
							$('.markers_'+mark_id).children().css('margin-top','-14px');
						}
					} else if (markers[i].mark_type == 'MARK_SHOT_LIST_IN' || markers[i].mark_type == 'MARK_SHOT_LIST_OUT'){
						$('.markers_'+markers[i].mark_id).children().css('margin-top','-12px');
						$('.markers_'+markers[i].mark_id).children().css('font-size','10px');
						if (markers[i].mark_id == mark_id){
							$('.markers_'+mark_id).children().css('margin-top','-18px');
							$('.markers_'+mark_id).children().css('font-size','14px');
						}
					}
				}
			}

			var fn_make_segment = function(b, e){
				var player3 = videojs(document.getElementById('player3'), {}, function(){
				});
                var _s = player3.currentTime();
                var frame_rate = 29.97;
                var frames = Math.floor(_s*frame_rate);
               
				Ext.Ajax.request({
					url: '/store/catalog/create_thumb_workflow.php',
					params: {
                        sec : _s,
                        frames: frames,
						content_id: <?=$content_id?>
					},
					callback: function(opts, success, response) {
						if(success) {
							//var images_view = Ext.getCmp('images-view');
							//if(images_view){images_view.store.reload();}
							var res = Ext.decode(response.responseText);
							if(!res.success) {
								Ext.Msg.alert( _text('MN00023'), res.msg);//알림
							}else{
                                Ext.Msg.alert( _text('MN00023'), res.msg);//알림
                            }
						}
					}
                });
                return true;

                var ctx = canvas.getContext('2d');
				var _s = player3.currentTime();
				ctx.drawImage(document.getElementById('player3_html5_api'), 0, 0, canvas.width, canvas.height);
				//var dataURI = canvas.toDataURL('image/jpeg'); // can also use 'image/png'
				var dataURL = canvas.toDataURL("image/png").replace("image/png", "image/octet-stream");
                //window.location.href=dataURI;

				Ext.Ajax.request({
					url: '/store/catalog/upload_thumbnail_image.php',
					params: {
						sec : _s,
						content_id: <?=$content_id?>,
						imgBase64: dataURL
					},
					callback: function(opts, success, response) {
						if(success) {
							var images_view = Ext.getCmp('images-view');
							if(images_view){images_view.store.reload();}
							var res = Ext.decode(response.responseText);
							if(!res.success) {
								Ext.Msg.alert( _text('MN00023'), res.msg);//알림
							}
						}
					}
				});
			};

			var fn_make_partical_file = function(b, e){
				//$f('player2').pause();
				player3.pause();

				if('<?=$_REQUEST['win_type']?>' == 'zodiac') {
					Ext.Msg.alert( _text('MN00023'), '검색화면에서만 가능한 기능입니다.');
					return;
				}

				if('<?=$pfr_err_msg?>' != '') {
					Ext.Msg.alert( _text('MN00023'), '<?=$pfr_err_msg?>');//알림
					return;
				}
				var setInSec, setOutSec, setInTC, setOutTC;
				var markers = videojs('player3').markers.getMarkers();
				for (var i = 0; i < markers.length; i++) {
					if (markers[i].mark_type == 'MARK_IN'){
						setInSec = markers[i].time;
						setInTC = secFrameToTimecode(setInSec,frame_rate)
					} else if (markers[i].mark_type == 'MARK_OUT'){
						setOutSec = markers[i].time;
						setOutTC = secFrameToTimecode(setOutSec,frame_rate)
					}
				}

				var txt = checkInOut(setInSec, setOutSec);
				if ( ! Ext.isEmpty(txt)) {
					Ext.Msg.alert(_text('MN00023'), txt);
					return;
				}

				//	Ext.Msg.alert(_text('MN00023'), '구현중입니다.');
				//	return;

				var sm = Ext.getCmp('tab_warp').getActiveTab().get(0).getSelectionModel();
				var title = sm.getSelected().get('title');
				<?php
					if($is_group == 'G') {
						echo "var sel = Ext.getCmp('group_child_list').getSelectionModel().getSelected();

						var child_content_id = sel.get('content_id');";
					} else {
						echo "var child_content_id = ".$content_id.";";
					}
				?>

				//원본파일이 있을 경우
				if ('<?=$flag_ori?>' == 'Y' ){
					new Ext.Window({
						layout: 'fit',
						title: _text('MN02140'),
						height: 120,
						width: 600,
						modal: true,
						buttonAlign: 'center',
						items: [{
							xtype: 'form',
							//frame: true,
							padding: 5,
							border: false,
							labelWidth: 60,
							cls: 'change_background_panel',
							items: [{
								xtype: 'textfield',
								anchor: '100%',
								fieldLabel: _text('MN00249'),//'제목'
								name: 'title',
								value: title
							}]

						}],

						buttons: [{
							text: '<span style="position:relative;top:1px;"><i class="fa fa-check" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00046'),//'확인'
							scale: 'medium',
							handler: function(btn) {
								var win = btn.ownerCt.ownerCt;
								var form = win.get(0).getForm();

								var wait_msg = Ext.Msg.wait( _text('MSG02036'), _text('MN00066'));//('등록중입니다.', '요청');
								Ext.Ajax.request({
									url: '/store/create_new_content.php',
									params: {
										content_id: child_content_id,
										title: form.getValues().title,
										//vr_meta: Ext.encode(values),
										start: setInSec,
										end: setOutSec
									},
									callback: function(opts, success, response){
										wait_msg.hide();
										if (success) {
											try {
												var r = Ext.decode(response.responseText);
												if (r.success) {
													win.close();
													Ext.Msg.show({
														title: _text('MN00003'),//'확인'
														msg: _text('MSG02037')+'</br>'+_text('MSG00190'),//'등록되었습니다.<br />창을 닫으시겠습니까?'
														icon: Ext.Msg.QUESTION,
														buttons: Ext.Msg.OKCANCEL,
														fn: function(btnId){
															if (btnId == 'ok') {
																Ext.getCmp('winDetail').close();
															}
														}
													});
												} else {
													Ext.Msg.alert( _text('MN00003'), r.msg);//'확인'
												}
											} catch(e) {
												Ext.Msg.alert( _text('MN01039'), response.responseText);//'오류'
											}
										} else {
											Ext.Msg.alert( _text('MN01098'), response.statusText);//'서버 오류'
										}
									}
								});
							}
						},{
							text: '<span style="position:relative;top:1px;"><i class="fa fa-close" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00004'),//'취소'
							scale: 'medium',
							handler: function(btn) {
								btn.ownerCt.ownerCt.close();
							}
						}]
					}).show();
					return;
				} else if ( '<?=$flag_ori?>' == 'N' ){
					if( '<?=ARCHIVE_USE_YN?>' == 'N' ){
						Ext.Msg.alert(_text('MN00023'), _text('MSG00031'));//원본 파일이 없습니다
					}else{
						if( '<?=$arr_sys_code['interwork_flashnet']['use_yn']?>' == 'Y' ){
							var win = new Ext.Window({
								layout:'fit',
								title: _text('MN02423'),
								modal: true,
								width:500,
								height:190,
								buttonAlign: 'center',
								items:[{
									id:'reason_request_inform',
									xtype:'form',
									border: false,
									frame: true,
									padding: 5,
									labelWidth: 70,
									cls: 'change_background_panel',
									defaults: {
										anchor: '95%'
									},
									items: [{
											xtype: 'textfield',
											id:'pfr_new_title',
											//anchor: '100%',
											allowBlank: false,
											fieldLabel: _text('MN00249'),//'제목'
											name: 'title',
											value: title
										},{
										id:'request_reason',
										xtype: 'textarea',
										height: 50,
										fieldLabel:_text('MN02423'),
										allowBlank: false,
										blankText: '<?=_text('MSG02187')?>',
										msgTarget: 'under'
									}]
								}],
								buttons:[{
									text : '<span style="position:relative;top:1px;"><i class="fa fa-paper-plane-o" style="font-size:13px;"></i></span>&nbsp;'+_text('MN02197'),
									scale: 'medium',
									handler: function(btn,e){
										var isValid = Ext.getCmp('request_reason').isValid();
										var isValid_new_title = Ext.getCmp('pfr_new_title').isValid();
										if ( ! isValid || !isValid_new_title) {
											Ext.Msg.show({
												icon: Ext.Msg.INFO,
												title: _text('MN00024'),//확인
												msg: '<?=_text('MSG02183')?>',
												buttons: Ext.Msg.OK
											});
											return;
										}

										var request_reason = Ext.getCmp('request_reason').getValue();
										var pfr_new_title = Ext.getCmp('pfr_new_title').getValue();
										
										var request_reason = Ext.getCmp('request_reason').getValue();
										var pfr_new_title = Ext.getCmp('pfr_new_title').getValue();

										var wait_msg = Ext.Msg.wait( _text('MSG02036'), _text('MN00066'));
										var rs = [];
										rs.push(<?=$content_id?>);

										Ext.Ajax.request({
											<?php if( $arr_sys_code['interwork_archive_confirm']['use_yn'] == 'Y'){ ?>
												url: '/store/archive/insert_archive_request.php',
												params: {
													case_management: 'request',
											<?php }else{?>
												url: '/store/archive/insert_archive_request_not_confirm.php',
												params: {	
											<?php }?>
												job_type: 'pfr',
												new_title: pfr_new_title,
												comment: request_reason,
												contents: Ext.encode(rs),
												ud_content_id : '<?=$ud_content_id?>',
												start: setInSec,
												end: setOutSec
											},
											callback: function(opt, success, res) {
												wait_msg.hide();
												var r = Ext.decode(res.responseText);
												if(!r.success) {
													Ext.Msg.alert(_text('MN00023'),r.msg);
												} else {
													//Ext.Msg.alert(_text('MN00023'), _text('MN01021') + ' ' + _text('MSG01009'));
												}
											}
										});
										win.destroy();
									}
								},{
									text : '<span style="position:relative;top:1px;"><i class="fa fa-close" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00004'),
									scale: 'medium',
									handler: function(btn,e){
										win.destroy();
									}
								}]
							});
							win.show();
						}else if( '<?=$arr_sys_code['interwork_oda_ods_l']['use_yn']?>' == 'Y'){
							var win = new Ext.Window({
								layout:'fit',
								title: _text('MN02423'),
								modal: true,
								width:500,
								height:190,
								buttonAlign: 'center',
								items:[{
									id:'reason_request_inform',
									xtype:'form',
									border: false,
									frame: true,
									padding: 5,
									labelWidth: 70,
									cls: 'change_background_panel',
									defaults: {
										anchor: '95%'
									},
									items: [{
											xtype: 'textfield',
											id:'pfr_new_title',
											//anchor: '100%',
											allowBlank: false,
											fieldLabel: _text('MN00249'),//'제목'
											name: 'title',
											value: title
										},{
										id:'request_reason',
										xtype: 'textarea',
										height: 50,
										fieldLabel:_text('MN02423'),
										allowBlank: false,
										blankText: '<?=_text('MSG02187')?>',
										msgTarget: 'under'
									}]
								}],
								buttons:[{
									text : '<span style="position:relative;top:1px;"><i class="fa fa-paper-plane-o" style="font-size:13px;"></i></span>&nbsp;'+_text('MN02197'),
									scale: 'medium',
									handler: function(btn,e){
										var isValid = Ext.getCmp('request_reason').isValid();
										var isValid_new_title = Ext.getCmp('pfr_new_title').isValid();
										if ( ! isValid || !isValid_new_title) {
											Ext.Msg.show({
												icon: Ext.Msg.INFO,
												title: _text('MN00024'),//확인
												msg: '<?=_text('MSG02183')?>',
												buttons: Ext.Msg.OK
											});
											return;
										}

										var request_reason = Ext.getCmp('request_reason').getValue();
										var pfr_new_title = Ext.getCmp('pfr_new_title').getValue();
										
										var request_reason = Ext.getCmp('request_reason').getValue();
										var pfr_new_title = Ext.getCmp('pfr_new_title').getValue();

										var wait_msg = Ext.Msg.wait( _text('MSG02036'), _text('MN00066'));
										var rs = [];
										rs.push(<?=$content_id?>);

										Ext.Ajax.request({
											<?php if( $arr_sys_code['interwork_archive_confirm']['use_yn'] == 'Y'){ ?>
												url: '/store/archive/insert_archive_request.php',
												params: {
													case_management: 'request',
											<?php }else{?>
												url: '/store/archive/insert_archive_request_not_confirm.php',
												params: {	
											<?php }?>
												job_type: 'pfr',
												new_title: pfr_new_title,
												comment: request_reason,
												contents: Ext.encode(rs),
												ud_content_id : '<?=$ud_content_id?>',
												start: setInSec,
												end: setOutSec
											},
											callback: function(opt, success, res) {
												wait_msg.hide();
												var r = Ext.decode(res.responseText);
												if(!r.success) {
													Ext.Msg.alert(_text('MN00023'),r.msg);
												} else {
													//Ext.Msg.alert(_text('MN00023'), _text('MN01021') + ' ' + _text('MSG01009'));
												}
											}
										});
										win.destroy();
									}
								},{
									text : '<span style="position:relative;top:1px;"><i class="fa fa-close" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00004'),
									scale: 'medium',
									handler: function(btn,e){
										win.destroy();
									}
								}]
							});
							win.show();
							
						} else if('<?=$arr_sys_code['interwork_oda_ods_d']['use_yn']?>' == 'Y'){
							var win = new Ext.Window({
								layout:'fit',
								title: _text('MN02423'),
								modal: true,
								width:500,
								height:190,
								buttonAlign: 'center',
								items:[{
									id:'reason_request_inform',
									xtype:'form',
									border: false,
									frame: true,
									padding: 5,
									labelWidth: 70,
									cls: 'change_background_panel',
									defaults: {
										anchor: '95%'
									},
									items: [{
											xtype: 'textfield',
											id:'pfr_new_title',
											//anchor: '100%',
											allowBlank: false,
											fieldLabel: _text('MN00249'),//'제목'
											name: 'title',
											value: title
										},{
										id:'request_reason',
										xtype: 'textarea',
										height: 50,
										fieldLabel:_text('MN02423'),
										allowBlank: false,
										blankText: '<?=_text('MSG02187')?>',
										msgTarget: 'under'
									}]
								}],
								buttons:[{
									text : '<span style="position:relative;top:1px;"><i class="fa fa-paper-plane-o" style="font-size:13px;"></i></span>&nbsp;'+_text('MN02197'),
									scale: 'medium',
									handler: function(btn,e){
										var isValid = Ext.getCmp('request_reason').isValid();
										var isValid_new_title = Ext.getCmp('pfr_new_title').isValid();
										if ( ! isValid || !isValid_new_title) {
											Ext.Msg.show({
												icon: Ext.Msg.INFO,
												title: _text('MN00024'),//확인
												msg: '<?=_text('MSG02183')?>',
												buttons: Ext.Msg.OK
											});
											return;
										}

										var request_reason = Ext.getCmp('request_reason').getValue();
										var pfr_new_title = Ext.getCmp('pfr_new_title').getValue();


										var wait_msg = Ext.Msg.wait( _text('MSG02036'), _text('MN00066'));
										var rs = [];
										rs.push(child_content_id);

										Ext.Ajax.request({
											<?php if( $arr_sys_code['interwork_archive_confirm']['use_yn'] == 'Y'){ ?>
												url: '/store/archive/insert_archive_request.php',
												params: {
													case_management: 'request',
											<?php }else{?>
												url: '/store/archive/insert_archive_request_not_confirm.php',
												params: {	
											<?php }?>
												job_type: 'pfr',
												new_title: pfr_new_title,
												comment: request_reason,
												contents: Ext.encode(rs),
												ud_content_id : '<?=$ud_content_id?>',
												start: setInSec,
												end: setOutSec
											},
											callback: function(opt, success, res) {
												wait_msg.hide();
												var r = Ext.decode(res.responseText);
												if(!r.success) {
													Ext.Msg.alert(_text('MN00023'),r.msg);
												} else {
													//Ext.Msg.alert(_text('MN00023'), _text('MN01021') + ' ' + _text('MSG01009'));
												}
											}
										});

										win.destroy();
									}
								},{
									text : '<span style="position:relative;top:1px;"><i class="fa fa-close" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00004'),
									scale: 'medium',
									handler: function(btn,e){
										win.destroy();
									}
								}]
							});
							win.show();
						}
					}
				}
			};

            var make_pfr_file_by_shot_list = function(self, index, e){

var grid = Ext.getCmp('timeline-grid');
var datas = [];
<?php
    // if( $originalExt != 'MXF' ){
    //     echo "Ext.Msg.alert('알림', '방송용(XDCAM MXF) 포맷이 아닙니다.');return;";
    // }else 
    if( $flag_ori !='Y' ){
        echo "Ext.Msg.alert('알림', '원본이 중앙스토리지에 없습니다. 리스토어 요청해주세요.');return;";
    }
?>

var sm = Ext.getCmp('timeline-grid').getSelectionModel();
if( !sm.hasSelection() ){
    Ext.Msg.show({
        title: '알림'
        ,msg: '생성할 목록을 선택해주세요.'
        ,buttons: Ext.Msg.OK
    });
    return;
}

var sels = sm.getSelections();

Ext.each(sels, function(r){
    datas.push(r.json);
});


for(var i=0; i<datas.length ;i++)
{
    if( datas[i].durationsec < 10 ){
        Ext.Msg.alert('알림', '10초 이상 클립만 생성 가능합니다.');
        return ;
    }
}

new Ext.Window({
    layout: 'fit',
    title: _text('MN02140'),
    height: 250,
    width: 400,
    modal: true,
    buttonAlign: 'center',
    items: [{
        xtype: 'form',
        //frame: true,
        padding: 5,
        border: false,
        labelWidth: 60,
        cls: 'change_background_panel',
        items: [{
            xtype:'combo',
            width: 150,
            name:'ud_content_id',
            editable: false,
            fieldLabel: '콘텐츠 유형',
            displayField:'ud_content_title',
            valueField: 'ud_content_id',
            typeAhead: true,
            beforeValue: '',
            triggerAction: 'all',
            lazyRender:true,
            store: new Ext.data.JsonStore({
                url: '/interface/mam_ingest/get_meta_json.php',
                root: 'data',
                autoLoad: true,
                baseParams: {
                    kind : 'ud_content',
                    bs_content_id: 506
                },
                fields: [
                    'ud_content_title',
                    'ud_content_id',
                    'allowed_extension'
                ]
            }),
            listeners:{
                afterrender: function(self){
                    self.getStore().load({
                        callback:function(r,o,s){
                            if( s && r[0] ){
                                //기본 클립본
                                self.setValue(7);
                            }
                        }
                    });
                }
            }
        },{
            xtype: 'textfield',
            anchor: '100%',
            fieldLabel: _text('MN00249'),//'제목'
            name: 'title',
            value: ''
        }]
    }],

    buttons: [{
        text: '<span style="position:relative;top:1px;"><i class="fa fa-check" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00046'),//'확인'
        scale: 'medium',
        handler: function(btn) {
            var win = btn.ownerCt.ownerCt;
            var form = win.get(0).getForm();

            var contentId = '<?=$content_id?>';
            var params = {
                content_id: contentId,
                title: form.getValues().title,
                ud_content_id: form.findField('ud_content_id').getValue(),
                in_out_list : Ext.encode(datas)
            };

            var requestMethod = 'POST';
            var requestUrl = '/api/v1/contents/'+contentId+'/clip';                                    

            var waitMsg = Ext.Msg.wait('처리 중입니다.', '처리중...');

            Ext.Ajax.request({
                //timeout: 180000,
                method: requestMethod,
                url: requestUrl,
                params: params,
                callback: function (opt, suc, res) {
                    waitMsg.hide();
                    var r = Ext.decode(res.responseText);
                    if (suc) {
                        if (r.success) {
                            btn.ownerCt.ownerCt.close();
                            Ext.Msg.show({
                                title: _text('MN00003'),
                                msg: _text('MSG02037'),
                                //icon: Ext.Msg.QUESTION,
                                buttons: Ext.Msg.OK,
                                fn: function(btnId){
                                    if (btnId == 'ok') {
                                        
                                    }
                                }
                            });
                        }
                        else {
                            Ext.Msg.alert('저장', r.msg);
                        }
                    } else {
                        Ext.Msg.alert('오류', r.msg);
                    }
                }
            });
        }
    },{
        text: '<span style="position:relative;top:1px;"><i class="fa fa-close" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00004'),//'취소'
        scale: 'medium',
        handler: function(btn) {
            btn.ownerCt.ownerCt.close();
        }
    }]
}).show();

return;

}

			var group_list_panel = {
				xtype: 'grid',
				id: 'group_child_list',
				cls: 'proxima_grid_header proxima_customize_grid_for_group',
				border: false,
				flex: 1,
				title: _text('MN01001'),
				//frame: true,
				margins : '0 0 0 0',
				enableDragDrop: false,
				ddGroup: 'ContentDD',
				stripeRows: true,
				dragZone: null,
				enableColumnMove: false,
				store: group_child_store,
				viewConfig: {
					forceFit: true
				},
				colModel: new Ext.grid.ColumnModel({
					columns: [
						//new Ext.grid.RowNumberer({width: 30}),
						{header: '', dataIndex: 'thumb', width: 30, renderer: groupChildThumb, align: 'center'},
						{header: '<center>'+_text('MN00370')+'</center>', dataIndex: 'sys_ori_filename'}//원본파일명
					]
				}),
				sm: new Ext.grid.RowSelectionModel({
					singleSelect: false,
					listeners: {
						selectionchange: function(self) {
							if (Ext.isAir) {
								airFunRemoveFilePath('all');

								if (self.getSelections()) {
									Ext.each(self.getSelections(), function(record){
										var root_path ='//Volumes/onlmg/highres/';
										var file = root_path +'/'+ record.get('ori_path');

										airFunAddFilePath(file);
									});
								}
							} // END Ext.isAir
						},
						rowselect: function(selModel, rowIndex, e) {
							var self = selModel.grid;
							var record = selModel.getSelected();
							var content_id = record.get('content_id');
							var bs_content_id = record.get('bs_content_id');

							// 스트리밍 영상 변경
							//var new_stream = 'mp4:' + record.json.proxy_path + '?tm=0';
							//if(bs_content_id == 515) {
							//	new_stream = 'mp3:' + record.json.proxy_path + '?tm=0';
							//}

							player3.src("/data/"+record.json.proxy_path);

							//$f('player2').stop();
							//$f('player2').load({ url: new_stream }).seek(1);

							//$f('player2').setClip({ url: new_stream }).seek(1);
							//$f('player2').startBuffering();
							//$f('player2').load().seek(1);

							// 미디어 정보내의 시스템 메타를 업데이트
							Ext.Ajax.request({
								url: '/store/group/get_sysmeta.php',
								params: {
									content_id: content_id,
									bs_content_id: bs_content_id
								},
								callback: function(opts, success, response) {
									if(success) {
										try {
											var r = Ext.decode(response.responseText);
											if(r.success === false) {
												Ext.Msg.alert( _text('MN00022'), r.msg);
											} else {
												var media_info = Ext.getCmp('detail_panel').find('name', 'media_info_tab');
												if(!Ext.isEmpty(media_info[0].items.get(0))) {
													media_info[0].items.get(0).getForm().loadRecord(r);
												}
											}
										} catch(e) {
											Ext.Msg.alert(e['name'], e['message']);
										}
									}else{
										Ext.Msg.alert( _text('MN00022'), opts.url+'<br />'+response.statusText+'('+response.status+')');
									}
								}
							});
							// 미디어 정보내의 미디어파일리스트는 store를 load
							var media_list = Ext.getCmp('media_list');
							//2015-11-19 수정
							if(media_list){
								media_list.getStore().load({
									params: {
										content_id: content_id
									}
								});
							}

							// 카탈로그 이미지 처리 (렌더되었을 경우는 store를 load / 랜더 전일 경우 listeners를 수정)
							var catalog = Ext.getCmp('detail_panel').find('name', 'catalog_info_tab');
							if(!Ext.isEmpty(catalog)) {
								if( catalog[0].items.items.length == 0 ) {
									catalog[0].purgeListeners();
									catalog[0].on('afterrender', function(self) {
										Ext.Ajax.request({
											url: '/store/get_catalog.php',
											params: {
												content_id: content_id
											},
											callback: function(opts, success, response){
												if (success) {
													try {
														var r = Ext.decode(response.responseText);
														if (r.success === false) {
															Ext.Msg.alert( _text('MN00022'), r.msg);
														} else {
															self.add(r)
															self.doLayout();
														}
													} catch(e) {
														Ext.Msg.alert(e['name'], e['message']);
													}
												} else {
													Ext.Msg.alert( _text('MN00022'), opts.url+'<br />'+response.statusText+'('+response.status+')');
												}
											}
										})
									});
								} else {
									catalog[0].items.items[0].getStore().load({
										params: {
											content_id : content_id
										}
									});
								}
							}
							// QC탭도 store를 load(단 있을 경우에만)
							var qc_list = Ext.getCmp('detail_panel').find('name', 'qc_info_tab');
							if(!Ext.isEmpty(qc_list)) {
								// QC 목록
								Ext.getCmp('qc_grid').getStore().load({
									params: {
										content_id: content_id
									}
								});
								// QC 검토의견
								Ext.Ajax.request({
									url: '/store/media_quality_store.php',
									params: {
										content_id: content_id,
										action: 'get_cmt'
									},
									callback: function(opts, success, response){
										if (success) {
											try {
												var r = Ext.decode(response.responseText);
												if (r.success) {
													Ext.getCmp('qc_review_cmt').setValue(r.comment);
												} else {
													Ext.Msg.alert('확인', r.msg);
												}
											} catch(e) {
												Ext.Msg.alert('오류', response.responseText);
											}
										} else {
											Ext.Msg.alert('서버 통신 오류', response.statusText);
										}
									}
								});
							}
						}
					}
				}),
				listeners: {
					render: function(grid) {
						grid.getStore().on('load', function() {
							grid.getSelectionModel().selectRow(0);
						});
						grid.getStore().load();
					}
				}
			};

			var rough_cut_store = new Ext.data.JsonStore({
				url:'/store/roughcut.php',
				baseParams: {
					content_id: <?=$content_id?>,
					type: 'shot_list',
					action : 'list',
					is_group: '<?=$is_group?>'
				},
				root: 'data',
				autoLoad : false,
				fields: [
					{name : 'content_id'},
					{name : 'list_id'},
					{name : 'in_frame'},
					{name : 'out_frame'},
					{name : 'insec'},
					{name : 'outsec'},
					{name : 'start_frame'},
					{name : 'end_frame'},
					{name : 'startsec'},
					{name : 'endsec'},
					{name : 'durationsec'},
					{name : 'duration'},
					{name : 'path'},
					{name : 'title'},
					{name : 'comments'}
				]
				,
				listeners: {
					beforeload: function(self, opts){
						opts.params = opts.params || {};
					}
				}
			});

			var rough_cut_detail_popup = function(self, index, e){
				var sel = Ext.getCmp('timeline-grid').getSelectionModel().getSelected();
				var rough_cut_store = Ext.getCmp('timeline-grid').getStore();
				if (rough_cut_store.getCount() > 0){
					var rought_cut_detail_win =  new Ext.Window({
						title: _text('MN02313'),
						cls: 'change_background_panel remove_border_toolbar',
						width: 500,
						modal: true,
						height: 380,
						miniwin: true,
						//resizable: false,
						layout: 'fit',
						buttonAlign: 'center',
						tbar: [{
							xtype:'button',
							cls : 'proxima_btn_customize',
							text: '<span style="position:relative;" title="'+_text('MN02375')+'"><i class="fa fa-arrow-left" style="font-size:13px;color:white;"></i></span>',//back
							scale: 'medium',
							handler: function(b, e){
								var i = 0;
								var j = 0;
								rough_cut_store.each(function(rec) {
									if (rec.get('list_id') == Ext.getCmp('rough_cut_form').getForm().getValues().list_id) {
										if(i == 0){
											j = rough_cut_store.getCount()-1;
										} else {
											j = i-1;
										}
									}
									i++;
								});
								var record = rough_cut_store.getAt(j);
								Ext.getCmp('rough_cut_form').getForm().loadRecord(record);
								Ext.getCmp('shot_list_color_input').getEl().setStyle('background', record.get('color'));
								var sm = Ext.getCmp('timeline-grid').getSelectionModel();
								sm.selectRow(j);
							}
						},{
							xtype:'button',
							cls : 'proxima_btn_customize',
							text: '<span style="position:relative;" title="'+_text('MN02376')+'"><i class="fa fa-arrow-right" style="font-size:13px;color:white;"></i></span>',//next
							scale: 'medium',
							handler: function(b, e){
								var i = 0;
								var j = 0;
								rough_cut_store.each(function(rec) {
									if (rec.get('list_id') == Ext.getCmp('rough_cut_form').getForm().getValues().list_id) {
										if(i == rough_cut_store.getCount()-1){
											j = 0;
										} else {
											j = i+1;
										}
									}
									i++;
								});
								var record = rough_cut_store.getAt(j);
								Ext.getCmp('rough_cut_form').getForm().loadRecord(record);
								Ext.getCmp('shot_list_color_input').getEl().setStyle('background', record.get('color'));
								var sm = Ext.getCmp('timeline-grid').getSelectionModel();
								sm.selectRow(j);
							}
						}],
						buttons: [
						{
							text : '<span style="position:relative;top:1px;"><i class="fa fa-arrow-left" style="font-size:13px;"></i></span>&nbsp;'+_text('MN02375'),
							scale: 'medium',
							hidden: true,
							handler: function(b, e){
								var i = 0;
								var j = 0;
								rough_cut_store.each(function(rec) {
									if (rec.get('list_id') == Ext.getCmp('rough_cut_form').getForm().getValues().list_id) {
										if(i == 0){
											j = rough_cut_store.getCount()-1;
										} else {
											j = i-1;
										}
									}
									i++;
								});
								var record = rough_cut_store.getAt(j);
								Ext.getCmp('rough_cut_form').getForm().loadRecord(record);
								Ext.getCmp('shot_list_color_input').getEl().setStyle('background', record.get('color'));
								var sm = Ext.getCmp('timeline-grid').getSelectionModel();
								sm.selectRow(j);
							}
						},{
							text : _text('MN02376') + ' <span style="position:relative;top:1px;"><i class="fa fa-arrow-right" style="font-size:13px;"></i></span>&nbsp;',
							scale: 'medium',
							hidden: true,
							handler: function(b, e){
								var i = 0;
								var j = 0;
								rough_cut_store.each(function(rec) {
									if (rec.get('list_id') == Ext.getCmp('rough_cut_form').getForm().getValues().list_id) {
										if(i == rough_cut_store.getCount()-1){
											j = 0;
										} else {
											j = i+1;
										}
									}
									i++;
								});
								var record = rough_cut_store.getAt(j);
								Ext.getCmp('rough_cut_form').getForm().loadRecord(record);
								Ext.getCmp('shot_list_color_input').getEl().setStyle('background', record.get('color'));
								var sm = Ext.getCmp('timeline-grid').getSelectionModel();
								sm.selectRow(j);
							}
						},{
							text : '<span style="position:relative;top:1px;"><i class="fa fa-edit" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00043'),
							scale: 'medium',
							handler: function(b, e){
								var title = Ext.getCmp('rough_cut_form').getForm().getValues().title;
								var color = Ext.getCmp('rough_cut_form').getForm().getValues().color;
								var comments = Ext.getCmp('rough_cut_comments_input').getValue();
								var list_id = Ext.getCmp('rough_cut_form').getForm().getValues().list_id;
                                
								var in_tc = Ext.getCmp('rough_cut_form').getForm().getValues().in_frame_tc;
								var out_tc = Ext.getCmp('rough_cut_form').getForm().getValues().out_frame_tc;
								var setInSec = timecodeToSecFrame(in_tc,frame_rate);
								var setOutSec = timecodeToSecFrame(out_tc,frame_rate);
								setInFrame = Math.floor(setInSec*frame_rate);
								setOutFrame = Math.floor(setOutSec*frame_rate);
                    
								dursec =  setOutSec - setInSec;
								duration = setOutFrame - setInFrame;
								var grid = Ext.getCmp('timeline-grid');
								var lastrecord = grid.getStore().getAt( grid.getStore().getCount() -1 ) ;
								var start_frame = lastrecord.get('end_frame');
								var starttcsec = lastrecord.get('endsec');
								var endtcsec = starttcsec + dursec;

								var end_frame = Math.floor(endtcsec*frame_rate);

								if(Ext.isNumber(endtcsec)) {
									var end_frame = Math.floor(endtcsec*frame_rate);
								} else {
									var end_frame = 0;
								}
								if(setInFrame > setOutFrame){
									return Ext.Msg.alert('알림', _text('MSG00194'));
								}
								Ext.MessageBox.confirm(_text ('MN00024'),_text ('MSG02169'), function(btn){
									if(btn === 'yes'){
										var datas = [];
										sel.data.start_frame = start_frame;
										sel.data.end_frame = end_frame;
										sel.data.in_frame = setInFrame;
										sel.data.out_frame = setOutFrame;
										sel.data.duration = duration;
										sel.data.list_id = list_id;
                                        
										datas.push(sel.data);
										Ext.Ajax.request({
											url: '/store/roughcut.php',
											params: {
												action: "edit",
												datas: Ext.encode(datas),
												type:'shot_list',
												title: title,
												comments: comments,
												content_id: content_id,
												color: color,
												list_id: list_id
											},
											callback: function(opt, success, response){
												//create_new_mark.close();
												rough_cut_store.load();
											}
										});
									}
								});
							}
						},{
							text : '<span style="position:relative;top:1px;"><i class="fa fa-trash" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00034'),
							scale: 'medium',
							handler: function(b, e){
								var datas = [];
								var grid = Ext.getCmp('timeline-grid');
								Ext.Msg.show({
									title : _text('MN00023'),
									msg : _text('MN00034')+' : '+_text('MSG02039'),
									buttons : Ext.Msg.OKCANCEL,
									fn : function(btn){
										if( btn == 'ok' ){
											datas.push(sel.data);
											Ext.getCmp('player_warp').Save(grid, content_id, 'shot_list', 'del', datas, true,false );
										}
									}
								});
							}
						},{
							text : '<span style="position:relative;top:1px;"><i class="fa fa-close" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00031'),
							scale: 'medium',
							handler: function(b, e){
								rought_cut_detail_win.close();
							}
						}],
						items:[{
							xtype: 'form',
							id: 'rough_cut_form',
							frame: true,
							items: [{
								xtype: 'textfield',
								//allowBlank: false,
								anchor:'100%',
								fieldLabel: _text('MN02438'),
								id: 'rough_cut_title_input',
								name: 'title'
							},{
								xtype: 'compositefield',
								fieldLabel: _text('MN00149'),
								name: 'timecode',
								anchor:'100%',
								items:[{
									xtype:'textfield',
									name : 'in_frame_tc',
									regex: /(0[0-9]|1[0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9]):([0-5][0-9])/,
									plugins: [new Ext.ux.InputTextMask('99:99:99:99')]
								},{
									xtype:'textfield',
									name : 'out_frame_tc',
									regex: /(0[0-9]|1[0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9]):([0-5][0-9])/,
									plugins: [new Ext.ux.InputTextMask('99:99:99:99')]
								}]
							},{
								xtype: 'textfield',
								fieldLabel: _text('MN00149'),
								readOnly: true,
								hidden: true,
								style: 'border: none;',
								name: 'in_out_range',
								anchor:'100%'
							},{
								xtype: 'textarea',
								//allowBlank: false,
								anchor:'100%',
								height: 150,
								fieldLabel: _text('MN01036'),
								id: 'rough_cut_comments_input',
								name: 'comments'
							},{
								xtype: 'hidden',
								name: 'list_id'
							},{
								xtype :'colorfield',
								anchor:'100%',
								name: 'color',
								fieldLabel: _text('MN02445'),
								//value: '#0000FF',
								id: 'shot_list_color_input',
								//msgTarget: 'qtip',
								fallback: true,
								//colors: colors_arr
							}]
						}]
					});
					var index = rough_cut_store.indexOf(sel);
					var record = rough_cut_store.getAt(index);
                    console.log(record);
					Ext.getCmp('rough_cut_form').getForm().loadRecord(record);
					rought_cut_detail_win.show();
				}
			};

			var make_pfr_file_by_shot_list = function(self, index, e){
				console.log('make_pfr_file_by_shot_list');

				var sm = Ext.getCmp('timeline-grid').getSelectionModel();
				if(sm.hasSelection()){
					var selected = sm.getSelected();
					var pfr_file = {};

					pfr_file.title = selected.get('title');
					pfr_file.content_id = selected.get('content_id');
					pfr_file.insec = selected.get('insec');
					pfr_file.outsec = selected.get('outsec');
					if(pfr_file.title == '' || pfr_file.title == null){
						Ext.Msg.alert( _text('MN01098'), _text('MSG00090'));
					}else{
						//xxxxxxxx
						if ('<?=$flag_ori?>' == 'Y' ){
							//make by Arile
							var wait_msg = Ext.Msg.wait( _text('MSG02036'), _text('MN00066'));
							Ext.Ajax.request({
								url: '/store/create_new_content.php',
								params: {
									content_id: pfr_file.content_id,
									title: pfr_file.title,
									start: pfr_file.insec,
									end: pfr_file.outsec
								},
								callback: function(opts, success, response){
									wait_msg.hide();
									if (success) {
										/*try {
											var r = Ext.decode(response.responseText);
											if (r.success) {
												win.close();
												Ext.Msg.show({
													title: _text('MN00003'),
													msg: _text('MSG02037')+'</br>'+_text('MSG00190'),
													icon: Ext.Msg.QUESTION,
													buttons: Ext.Msg.OKCANCEL,
													fn: function(btnId){
														if (btnId == 'ok') {
															Ext.getCmp('winDetail').close();
														}
													}
												});
											} else {
												Ext.Msg.alert( _text('MN00003'), r.msg);
											}
										} catch(e) {
											Ext.Msg.alert( _text('MN01039'), response.responseText);
										}*/
									} else {
										Ext.Msg.alert( _text('MN01098'), response.statusText);
									}
								}
							});
		
						} else if ( '<?=$flag_ori?>' == 'N' ){
							// make by Archive Agent
							if( '<?=ARCHIVE_USE_YN?>' == 'N' ){
								Ext.Msg.alert(_text('MN00023'), _text('MSG00031'));
							}else{
								if( '<?=$arr_sys_code['interwork_flashnet']['use_yn']?>' == 'Y' ){
									// not implement yet
									return;

								}else if( '<?=$arr_sys_code['interwork_oda_ods_l']['use_yn']?>' == 'Y'){
									// not implement yet
									return;

								} else if('<?=$arr_sys_code['interwork_oda_ods_d']['use_yn']?>' == 'Y'){
									var wait_msg = Ext.Msg.wait( _text('MSG02036'), _text('MN00066'));
									var rs = [];
									rs.push(pfr_file.content_id);

									var win = new Ext.Window({
										layout:'fit',
										title: _text('MN02423'),
										modal: true,
										width:500,
										height:190,
										buttonAlign: 'center',
										items:[{
											id:'reason_request_inform',
											xtype:'form',
											border: false,
											frame: true,
											padding: 5,
											labelWidth: 70,
											cls: 'change_background_panel',
											defaults: {
												anchor: '95%'
											},
											items: [{
													xtype: 'textfield',
													id:'pfr_new_title',
													//anchor: '100%',
													allowBlank: false,
													fieldLabel: _text('MN00249'),//'제목'
													name: 'title',
													value: pfr_file.title
												},{
												id:'request_reason',
												xtype: 'textarea',
												height: 50,
												fieldLabel:_text('MN02423'),
												allowBlank: false,
												blankText: '<?=_text('MSG02187')?>',
												msgTarget: 'under'
											}]
										}],
										buttons:[{
											text : '<span style="position:relative;top:1px;"><i class="fa fa-paper-plane-o" style="font-size:13px;"></i></span>&nbsp;'+_text('MN02197'),
											scale: 'medium',
											handler: function(btn,e){
												var isValid = Ext.getCmp('request_reason').isValid();
												var isValid_new_title = Ext.getCmp('pfr_new_title').isValid();
												if ( ! isValid || !isValid_new_title) {
													Ext.Msg.show({
														icon: Ext.Msg.INFO,
														title: _text('MN00024'),//확인
														msg: '<?=_text('MSG02183')?>',
														buttons: Ext.Msg.OK
													});
													return;
												}

												var request_reason = Ext.getCmp('request_reason').getValue();
												var pfr_new_title = Ext.getCmp('pfr_new_title').getValue();


												var wait_msg = Ext.Msg.wait( _text('MSG02036'), _text('MN00066'));

												Ext.Ajax.request({
													<?php if( $arr_sys_code['interwork_archive_confirm']['use_yn'] == 'Y'){ ?>
														url: '/store/archive/insert_archive_request.php',
														params: {
															case_management: 'request',
													<?php }else{?>
														url: '/store/archive/insert_archive_request_not_confirm.php',
														params: {	
													<?php }?>
														job_type: 'pfr',
														new_title: pfr_new_title,
														comment: request_reason,
														contents: Ext.encode(rs),
														ud_content_id : '<?=$ud_content_id?>',
														start: pfr_file.insec,
														end: pfr_file.outsec
													},
													callback: function(opt, success, res) {
														wait_msg.hide();
														var r = Ext.decode(res.responseText);
														if(!r.success) {
															Ext.Msg.alert(_text('MN00023'),r.msg);
														} else {
														}
													}
												});

												win.destroy();
											}
										},{
											text : '<span style="position:relative;top:1px;"><i class="fa fa-close" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00004'),
											scale: 'medium',
											handler: function(btn,e){
												win.destroy();
											}
										}]
									});
									win.show();
								}
							}
						}	
					}
				}else{
					Ext.Msg.alert( _text('MN01098'), _text('MSG01005'));
				}

			};

			// 샷 리스트
			var rough_cut_panel = {
				title: _text('MN02313'),
				xtype: 'grid',
				cls: 'proxima_customize_grid_for_group proxima_grid_header',
				stripeRows: true,
				flex: 1,
				enableDragDrop: false,
				//ddGroup: 'EditCutGridDD',
				id: 'timeline-grid',
				enableColumnMove: false,
				isDirty: false,
				getMarkInOut: function(){
					var grid = Ext.getCmp('timeline-grid');
                    
					var markers = videojs('player3').markers.getMarkers();
					var setInSec, setInTC, setOutSec, setOutTC, isIn = false, isOut = false;
					for (var i = 0; i < markers.length; i++) {
						if (markers[i].mark_type == 'MARK_IN'){
							setInSec = markers[i].time;
							isIn = true;
						} else if (markers[i].mark_type == 'MARK_OUT'){
							setOutSec = markers[i].time;
							isOut = true;
						}
					}
					var chackIn = isIn;
					var chackOut = isOut;
					if(!isIn && !isOut){
						setInSec = 0;
						setOutSec = 0;
					}
					if(isIn && !isOut){
						return Ext.Msg.alert('알림', _text('MSG00192'));
					}else if(!isIn && isOut){
						return Ext.Msg.alert('알림', _text('MSG00191'));
					}else if(setInSec > setOutSec){
						return Ext.Msg.alert('알림', _text('MSG00194'));
					}
					this.insertTimeLine( grid, setInSec, setOutSec ,false);
					this.isDirty = true;
				
					
				},
				insertTimeLine : function(grid, setInSec, setOutSec , is_edit){
					//Ext.getCmp('player_warp').stop();
                    
					var setInFrame, setOutFrame, txt ,dursec, duration, content_id;

					setInFrame = Math.floor(setInSec*frame_rate);
					setOutFrame = Math.floor(setOutSec*frame_rate);
                    
					dursec =  setOutSec - setInSec;
					duration = setOutFrame - setInFrame;
					
					<?php
						if($is_group == 'G') {
							echo "var group_list = Ext.getCmp('group_child_list').getSelectionModel().getSelected();
									content_id = group_list.get('content_id'); ";
						}
					?>

					if(grid.getStore().getCount() > 0) {
						var lastrecord = grid.getStore().getAt( grid.getStore().getCount() -1 ) ;
						var start_frame = lastrecord.get('end_frame');
						var starttcsec = lastrecord.get('endsec');
						var endtcsec = starttcsec + dursec;

						if(Ext.isNumber(endtcsec)) {
							var end_frame = Math.floor(endtcsec*frame_rate);
						} else {
							var end_frame = 0;
						}

					} else {
						var starttcsec = 0;
						var start_frame = 0;
						var endtcsec = dursec;
						var end_frame = duration;
					}

					var record = new Ext.data.Record({
						'content_id' : content_id,
						'list_id' : '',
						'start_frame' : start_frame,
						'startsec' : starttcsec,
						'end_frame' : end_frame,
						'endsec' : endtcsec,
						'in_frame' : setInFrame,
						'insec' : setInSec,
						'out_frame' : setOutFrame,
						'outsec' : setOutSec,
						'duration' : duration,
						'durationsec' : dursec,
						'note' : ''
					});
					grid.getStore().add(record);

					if( is_edit ){
						grid.startEditing(grid.getStore().getCount()-1, 6);
					}
				},
				tbar: [
				<?php if($arr_sys_code['download_xml']['use_yn'] == 'Y'){ ?>
				' ',{
                    width: 110,
                    scale: 'small',
                    xtype: 'aw-button',
                    text : 'EDL 다운로드',
                    iFontSize :11,
                    iColor : 'black',
                    iCls : 'fa fa-download',
                    cls : 'proxima_btn_customize proxima_btn_customize_new'
				},
				<?php } ?>
				{
					icon: '/led-icons/accept.png',
					hidden : true,
					text: '저장',
					handler: function(b, e){
						var grid = b.ownerCt.ownerCt;
						var datas = [];
						var datalist = grid.getStore().getRange();
						Ext.each(datalist,function(r){
							datas.push(r.data);
						});

						var save_type = 'add';
						<?php
							if($is_group == 'G') {
								echo "save_type = 'group-add';";
							}
						?>

						Ext.getCmp('player_warp').Save(grid, content_id, 'shot_list', save_type, datas , true ,true );
					}
				},'->',{
					icon: '/led-icons/page_2.png',
					hidden : true,
					text: _text('MN02289'),//'EDL생성',
					handler: function(b, e){
						//var grid = b.ownerCt.ownerCt;
						var grid = Ext.getCmp('timeline-grid');
						var datas = [];
						var export_type = 'export';
						<?php
							if($is_group == 'G') {
								echo "export_type = 'group-export';";
							}
						?>
						Ext.Msg.show({
							title : _text('MN00023'),
							msg : _text('MN02289')+' : '+_text('MSG02039'),
							buttons : Ext.Msg.OKCANCEL,
							fn : function(btn){
								if( btn == 'ok' ){
									Ext.getCmp('player_warp').Save(grid, content_id, 'shot_list', export_type, datas , false,true );
								}
							}
						});
					}
				},{
					scale: 'small',
                    xtype: 'aw-button',                  
                    iFontSize : 11,
                    iColor : 'black',
                    iCls : 'fa fa-plus',
                    width: 130,
                    cls : 'proxima_btn_customize proxima_btn_customize_new',
					text: '샷리스트 추가',
					handler: function(){
                        var marker = rough_cut_panel.getMarkInOut();
				
						
						var grid = Ext.getCmp('timeline-grid');
						var datas = [];
						var datalist = grid.getStore().getRange();
						Ext.each(datalist,function(r){
							datas.push(r.data);
						});
						Ext.getCmp('player_warp').Save(grid, content_id, 'shot_list', 'add', datas , true ,true );
			}
				},{
					hidden: <?php
						 if(checkAllowUdContentGrant( $_SESSION['user']['user_id'], $ud_content_id, GRANT_CREATE) ){ 
						 		echo 'false';
						 	}else{
						 		echo 'true';
						 	}
					?>,
					scale: 'small',
                    xtype: 'aw-button',                  
                    iFontSize : 11,
                    iColor : 'black',
                    iCls : 'fa fa-upload',
                    width: 130,
                    cls : 'proxima_btn_customize proxima_btn_customize_new',
					text: '클립 콘텐츠 생성',
				
					handler: function(b, e){

						var grid = Ext.getCmp('timeline-grid');
                        var datas = [];
                        <?php
                            // if( $originalExt != 'MXF' ){
                            //     echo "Ext.Msg.alert('알림', '방송용(XDCAM MXF) 포맷이 아닙니다.');return;";
                            // }else 
                            if( empty($originalExt) ){
                                echo "Ext.Msg.alert('알림', '원본이 중앙스토리지에 없습니다. 리스토어 요청해주세요.');return;";
                            }
                        ?>
                        
                        var sm = Ext.getCmp('timeline-grid').getSelectionModel();
                        if( !sm.hasSelection() ){
                            Ext.Msg.show({
                                title: '알림'
                                ,msg: '생성할 목록을 선택해주세요.'
                                ,buttons: Ext.Msg.OK
                            });
                            return;
                        }

                        var sels = sm.getSelections();

                        Ext.each(sels, function(r){
                            datas.push(r.json);
                        });

                        
                        for(var i=0; i<datas.length ;i++)
                        {
                            if( datas[i].durationsec < 10 ){
                                Ext.Msg.alert('알림', '10초 이상 클립만 생성 가능합니다.');
                                return ;
                            }
                        }

                        new Ext.Window({
                            layout: 'fit',
                            title: _text('MN02140'),
                            height: 250,
                            width: 400,
                            modal: true,
                            buttonAlign: 'center',
                            items: [{
                                xtype: 'form',
                                //frame: true,
                                padding: 5,
                                border: false,
                                labelWidth: 60,
                                cls: 'change_background_panel',
                                items: [{
                                    xtype:'combo',
                                    width: 150,
                                    name:'ud_content_id',
                                    editable: false,
                                    fieldLabel: '콘텐츠 유형',
                                    displayField:'ud_content_title',
                                    valueField: 'ud_content_id',
                                    typeAhead: true,
                                    beforeValue: '',
                                    triggerAction: 'all',
                                    lazyRender:true,
                                    store: new Ext.data.JsonStore({
                                        url: '/interface/mam_ingest/get_meta_json.php',
                                        root: 'data',
                                        autoLoad: true,
                                        baseParams: {
                                            kind : 'ud_content',
                                            bs_content_id: 506
                                        },
                                        fields: [
                                            'ud_content_title',
                                            'ud_content_id',
                                            'allowed_extension'
                                        ]
                                    }),
                                    listeners:{
                                        afterrender: function(self){
                                            self.getStore().load({
                                                callback:function(r,o,s){
                                                    if( s && r[0] ){
                                                        //기본 클립본
                                                        self.setValue(7);
                                                    }
                                                }
                                            });
                                        }
                                    }
                                },{
                                    xtype: 'textfield',
                                    anchor: '100%',
                                    fieldLabel: _text('MN00249'),//'제목'
                                    name: 'title',
                                    value: ''
                                }]
                            }],

                            buttons: [{
                                text: '<span style="position:relative;top:1px;"><i class="fa fa-check" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00046'),//'확인'
                                scale: 'medium',
                                handler: function(btn) {
                                    var win = btn.ownerCt.ownerCt;
                                    var form = win.get(0).getForm();

                                    var contentId = '<?=$content_id?>';
                                    var params = {
                                        content_id: contentId,
                                        title: form.getValues().title,
                                        ud_content_id: form.findField('ud_content_id').getValue(),
                                        in_out_list : Ext.encode(datas)
                                    };
 
                                    var requestMethod = 'POST';
                                    var requestUrl = '/api/v1/contents/'+contentId+'/clip';                                    

                                    var waitMsg = Ext.Msg.wait('처리 중입니다.', '처리중...');

                                    Ext.Ajax.request({
                                        //timeout: 180000,
                                        method: requestMethod,
                                        url: requestUrl,
                                        params: params,
                                        callback: function (opt, suc, res) {
                                            waitMsg.hide();
                                            var r = Ext.decode(res.responseText);
                                            if (suc) {
                                                if (r.success) {
													btn.ownerCt.ownerCt.close();
                                                    Ext.Msg.show({
                                                        title: _text('MN00003'),
                                                        msg: _text('MSG02037'),
                                                        //icon: Ext.Msg.QUESTION,
                                                        buttons: Ext.Msg.OK,
                                                        fn: function(btnId){
                                                            if (btnId == 'ok') {
																
                                                            }
                                                        }
                                                    });
                                                }
                                                else {
                                                    Ext.Msg.alert('저장', r.msg);
                                                }
                                            } else {
                                                Ext.Msg.alert('오류', r.msg);
                                            }
                                        }
                                    });
                                }
                            },{
                                text: '<span style="position:relative;top:1px;"><i class="fa fa-close" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00004'),//'취소'
                                scale: 'medium',
                                handler: function(btn) {
                                    btn.ownerCt.ownerCt.close();
                                }
                            }]
                        }).show();

                        return;

					}
				}],			
				store: new Ext.data.JsonStore({
					url:'/store/roughcut.php',
					baseParams: {
						content_id: <?=$content_id?>,
						type: 'shot_list',
						action : 'list',
						is_group: '<?=$is_group?>'
					},
					root: 'data',
					autoLoad : false,
					fields: [
						{name : 'content_id'},
						{name : 'list_id'},
						{name : 'in_frame'},
						{name : 'out_frame'},
						{name : 'insec'},
						{name : 'outsec'},
						{name : 'start_frame'},
						{name : 'end_frame'},
						{name : 'startsec'},
						{name : 'endsec'},
						{name : 'durationsec'},
						{name : 'duration'},
						{name : 'path'},
						{name : 'title'},
						{name : 'comments'},
						{name : 'color', convert: function(v, record) {
								if(record.color == null){
									return '#FF0000';
								}else {
									return record.color;
								}
							}
						},
						{ name : 'in_out_range', convert: function(v, record) {
								return frameToTimecode(record.in_frame,frame_rate) + ' ~ ' + frameToTimecode(record.out_frame, frame_rate) ;
							}
						}
						,
						{ name : 'in_frame_tc', convert: function(v, record) {
								return frameToTimecode(record.in_frame,frame_rate);
							}
						}
						,
						{ name : 'out_frame_tc', convert: function(v, record) {
								return frameToTimecode(record.out_frame,frame_rate);
							}
						}
						,
						{ name : 'duration_tc', convert: function(v, record) {
								return frameToTimecode(record.duration,frame_rate);
							}
						}
					]
					,
					listeners: {
						beforeload: function(self, opts){
							opts.params = opts.params || {};
						},
						load: function(store, records, opts){
							var videojs_player = videojs('player3');
							var data_array = [];
							<?php
								echo " var child_content_id = content_id;";
								if($is_group == 'G') {
									echo "var group_list = Ext.getCmp('group_child_list').getSelectionModel().getSelected();
										if(group_list){
											child_content_id = group_list.get('content_id');
										}";
								}
							?>
							Ext.each(records, function(record){
								if(record.get('content_id') == child_content_id) {
									var data_temp = {
										time: Math.ceil(record.get('in_frame')/frame_rate * 100000)/100000,
										time_code: record.get('intc'),
										text: record.get('title'),
										comments: record.get('comments'),
										mark_id: record.get('list_id'),
										mark_type: 'MARK_SHOT_LIST_IN',
										color: record.get('color')
									}
									data_array.push(data_temp);
									data_temp = {
										time: Math.ceil(record.get('out_frame')/frame_rate * 100000)/100000,
										time_code: record.get('outtc'),
										text: record.get('title'),
										comments: record.get('comments'),
										mark_id: record.get('list_id'),
										mark_type: 'MARK_SHOT_LIST_OUT',
										color: record.get('color')
									}
									data_array.push(data_temp);
								}
							})
							videojs_player.markers.reset(data_array);
						}
					}
				}),
				colModel: new Ext.grid.ColumnModel({
					defaultSortable: false,
					columns: [
						new Ext.grid.RowNumberer(),
						{header: _text('MN02445'), dataIndex: 'color', hidden: true},
						{header: _text('MN02296'), dataIndex: 'start_frame', hidden: true},
						{header: _text('MN02297'), dataIndex: 'end_frame', hidden: true},
						{header: _text('MN02316'), dataIndex: 'in_frame', hidden: true},
						{header: _text('MN02317'), dataIndex: 'out_frame', hidden: true},
						{header: _text('MN02318'), dataIndex: 'duration', hidden: true},
						{header: _text('MN02316'), dataIndex: 'in_frame_tc', width:35, align: 'center'},
						{header: _text('MN02317'), dataIndex: 'out_frame_tc', width:35, align: 'center'},
						{header: _text('MN02318'), dataIndex: 'duration_tc', width:35, align: 'center'},
						{header: _text('MN00249'), dataIndex: 'title'},
						{header: _text('MN02439'), dataIndex: 'comments',
							renderer: function(value, metaData, record){
								metaData.attr = 'title="' + record.get('comments') + '"';
								return value;
							}
						},
						{
							header: _text('MN02446'),sortable:false, width:35, align: 'center',
							renderer: function(value, metaData, record, rowIndex, colIndex, store) {
									return '<i class="fa fa-circle" style="margin-left:5px;color:'+record.get('color')+';"></i>  ';
							}
						}
					]
				}),
				sm: new Ext.grid.RowSelectionModel({
					//singleSelect:true
				}),
				listeners: {
					rowcontextmenu: function(self, rowIdx, e){
						e.stopEvent();
						var sm = self.getSelectionModel();
                        var r = sm.getSelections();
                        if( Ext.isEmpty(r) ){
                            sm.selectRow(rowIdx);
						    var r = sm.getSelected();
                        }
						//Ext.getCmp('player_warp').pause();
						//clearInterval(Ariel.RoughCutWindow.tid );

						var menu_f = new Ext.menu.Menu({
							items: [{
								text: _text('MN00034'),//'삭제',							
								handler: function(btn, e){
									var grid = self;

									Ext.Msg.show({
										title : _text('MN00023'),
										msg : _text('MN00034')+' : '+_text('MSG02039'),
										buttons : Ext.Msg.OKCANCEL,
										fn : function(btn){
											if( btn == 'ok' ){
												//grid.getStore().remove(r);
												//grid.isDirty = true;
												var datas = [];
                                                if(!Ext.isEmpty(r)){
                                                    for(var i=0;i<r.length;i++){
                                                        datas.push(r[i].data);
                                                    }
                                                }
												Ext.getCmp('player_warp').Save(grid, content_id, 'shot_list', 'del', datas, true,false );
											}
										}
									});
								}
							},{
								text: _text('MN00043'),
								handler: rough_cut_detail_popup
							},{
								text: _text('MN02140'),
								<?php if(!checkAllowUdContentGrant($user_id, $ud_content_id, GRANT_PFR) || $arr_sys_code['pfr_use_yn']['use_yn'] != 'Y'){?>
								hidden: true,	
								<?php }?>
								handler: function(b, e){

                                    var grid = Ext.getCmp('timeline-grid');
                                    var datas = [];
                                    <?php
                                        // if( $originalExt != 'MXF' ){
                                        //     echo "Ext.Msg.alert('알림', '방송용(XDCAM MXF) 포맷이 아닙니다.');return;";
                                        // }else 
                                        if( empty($originalExt) ){
                                            echo "Ext.Msg.alert('알림', '원본이 중앙스토리지에 없습니다. 리스토어 요청해주세요.');return;";
                                        }
                                    ?>

                                    var sm = Ext.getCmp('timeline-grid').getSelectionModel();
                                    if( !sm.hasSelection() ){
                                        Ext.Msg.show({
                                            title: '알림'
                                            ,msg: '생성할 목록을 선택해주세요.'
                                            ,buttons: Ext.Msg.OK
                                        });
                                        return;
                                    }

                                    var sels = sm.getSelections();

                                    Ext.each(sels, function(r){
                                        datas.push(r.json);
                                    });


                                    for(var i=0; i<datas.length ;i++)
                                    {
                                        if( datas[i].durationsec < 10 ){
                                            Ext.Msg.alert('알림', '10초 이상 클립만 생성 가능합니다.');
                                            return ;
                                        }
                                    }

                                    new Ext.Window({
                                        layout: 'fit',
                                        title: _text('MN02140'),
                                        height: 250,
                                        width: 400,
                                        modal: true,
                                        buttonAlign: 'center',
                                        items: [{
                                            xtype: 'form',
                                            //frame: true,
                                            padding: 5,
                                            border: false,
                                            labelWidth: 60,
                                            cls: 'change_background_panel',
                                            items: [{
                                                xtype:'combo',
                                                width: 150,
                                                name:'ud_content_id',
                                                editable: false,
                                                fieldLabel: '콘텐츠 유형',
                                                displayField:'ud_content_title',
                                                valueField: 'ud_content_id',
                                                typeAhead: true,
                                                beforeValue: '',
                                                triggerAction: 'all',
                                                lazyRender:true,
                                                store: new Ext.data.JsonStore({
                                                    url: '/interface/mam_ingest/get_meta_json.php',
                                                    root: 'data',
                                                    autoLoad: true,
                                                    baseParams: {
                                                        kind : 'ud_content',
                                                        bs_content_id: 506
                                                    },
                                                    fields: [
                                                        'ud_content_title',
                                                        'ud_content_id',
                                                        'allowed_extension'
                                                    ]
                                                }),
                                                listeners:{
                                                    afterrender: function(self){
                                                        self.getStore().load({
                                                            callback:function(r,o,s){
                                                                if( s && r[0] ){
                                                                    //기본 클립본
                                                                    self.setValue(7);
                                                                }
                                                            }
                                                        });
                                                    }
                                                }
                                            },{
                                                xtype: 'textfield',
                                                anchor: '100%',
                                                fieldLabel: _text('MN00249'),//'제목'
                                                name: 'title',
                                                value: ''
                                            }]
                                        }],

                                        buttons: [{
                                            text: '<span style="position:relative;top:1px;"><i class="fa fa-check" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00046'),//'확인'
                                            scale: 'medium',
                                            handler: function(btn) {
                                                var win = btn.ownerCt.ownerCt;
                                                var form = win.get(0).getForm();

                                                var contentId = '<?=$content_id?>';
                                                var params = {
                                                    content_id: contentId,
                                                    title: form.getValues().title,
                                                    ud_content_id: form.findField('ud_content_id').getValue(),
                                                    in_out_list : Ext.encode(datas)
                                                };

                                                var requestMethod = 'POST';
                                                var requestUrl = '/api/v1/contents/'+contentId+'/clip';                                    

                                                var waitMsg = Ext.Msg.wait('처리 중입니다.', '처리중...');

                                                Ext.Ajax.request({
                                                    //timeout: 180000,
                                                    method: requestMethod,
                                                    url: requestUrl,
                                                    params: params,
                                                    callback: function (opt, suc, res) {
                                                        waitMsg.hide();
                                                        var r = Ext.decode(res.responseText);
                                                        if (suc) {
                                                            if (r.success) {
                                                                btn.ownerCt.ownerCt.close();
                                                                Ext.Msg.show({
                                                                    title: _text('MN00003'),
                                                                    msg: _text('MSG02037'),
                                                                    //icon: Ext.Msg.QUESTION,
                                                                    buttons: Ext.Msg.OK,
                                                                    fn: function(btnId){
                                                                        if (btnId == 'ok') {
                                                                            
                                                                        }
                                                                    }
                                                                });
                                                            }
                                                            else {
                                                                Ext.Msg.alert('저장', r.msg);
                                                            }
                                                        } else {
                                                            Ext.Msg.alert('오류', r.msg);
                                                        }
                                                    }
                                                });
                                            }
                                        },{
                                            text: '<span style="position:relative;top:1px;"><i class="fa fa-close" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00004'),//'취소'
                                            scale: 'medium',
                                            handler: function(btn) {
                                                btn.ownerCt.ownerCt.close();
                                            }
                                        }]
                                    }).show();

                                    return;

                                    }
							}],
							listeners: {
								render: function(self){
								}
							}
						});
						menu_f.showAt(e.getXY());
					},

					rowclick : function(self, index, e){
						var sel = self.getSelectionModel().getSelected();
						var player3 = videojs(document.getElementById('player3'), {}, function(){
						});
						<?php
							echo " var child_content_id = content_id;";
							if($is_group == 'G') {
								echo "var group_list = Ext.getCmp('group_child_list').getSelectionModel().getSelected();
									if(group_list){
										child_content_id = group_list.get('content_id');
									}";
							}
						?>
						if (child_content_id == sel.get('content_id')){
							player3.currentTime(sel.get('insec'));
							fn_selected_marker(sel.get('list_id'));
						} else {
							var group_child_list = Ext.getCmp('group_child_list');
							var group_list_array = group_child_list.getStore().getRange();
							for(var i=0;i< group_list_array.length;i++)
							{
								if (sel.get('content_id') == group_list_array[i].get('content_id')){
									group_child_list.getSelectionModel().selectRow(i);
									group_child_list.fireEvent('rowselect', group_child_list);
									var TabShotListToCheck = Ext.getCmp('media_content_panel_warp').findById('timeline-grid');
									if(TabShotListToCheck){
											player3.currentTime(sel.get('insec'));
											fn_selected_marker(sel.get('list_id'));
									}
								}
							}
						}
						//Ariel.RoughCutTimeLineRun(self);

					},
					rowdblclick : rough_cut_detail_popup,
					viewready: function(self){
						if( Ext.isChrome ){
							var editGridDroptgtCfg = Ext.apply({}, EditCutDropZoneOverrides, {
								startsec : 'startsec',
								start_frame : 'start_frame',
								endsec : 'endsec',
								end_frame : 'end_frame',
								insec : 'insec',
								in_frame : 'in_frame',
								outsec : 'outsec',
								out_frame : 'out_frame',
								duration : 'duration',
								durationsec : 'durationsec',
								list_id : 'list_id',
								ddGroup: 'EditCutGridDD',
								grid : self
							});
							new Ext.dd.DropZone(self.getEl(), editGridDroptgtCfg);
						}
					},
					afterrender: function(self){
						if('<?=$_REQUEST['win_type']?>' == 'zodiac') return;
						//예) application/{확장자}:{파일명}:file:///{풀파일경로}
						var grid = Ext.getCmp('tab_warp').getActiveTab().get(0);
						var sm = grid.getSelectionModel();
						var r = sm.getSelected();
						var ori_ext= 'xml';
						var ori_path= r.json.proxy_path;

						//empty path return
						if(!ori_path) return;

						var ori_path_array = ori_path.split('/');

						ori_path_array.pop();
						ori_path_array.pop();

						var ori_path = ori_path_array.join('/');

						var filename = r.json.content_id;
						var highres_path= r.json.lowres_root;
						var path = highres_path + '/' + ori_path+'/' + filename +'.'+ ori_ext;

						if(Ext.isMac){
							var highres_path = r.json.lowres_mac_path;
							var path = highres_path + '/' + ori_path+'/' + filename +'.'+ ori_ext;
						}else{
							var highres_path= r.json.lowres_root;
							var path = highres_path + '/' + ori_path+'/' + filename +'.'+ ori_ext;
							path = path.replace(/\//gi, '\\\\');
						}


						var edl_path = 'application/gmsdd:{"medias":["'+path+'"]}';

						if(Ext.isPremiere){
							var edl_path = path;
							var thumb_img = document.getElementById('edl');
							if( !Ext.isEmpty(thumb_img) ){
								//객체가 있을경우 URL 셋팅 - 섬네일용
								thumb_img.addEventListener("dragstart",function(evt){
								//alert(edl_path);
									premiere_evt(evt,edl_path,1);
								},false);
							}
						}

						//데이터뷰에서 각 이미지에 설정해놓은 ID로 객체를 찾는다
						else if(Ext.isChrome){
							var thumb_img = document.getElementById('edl');
							if( !Ext.isEmpty(thumb_img) ){
								//객체가 있을경우 URL 셋팅 - 섬네일용
								thumb_img.addEventListener("dragstart",function(evt){
									evt.dataTransfer.setData("DownloadURL",edl_path);
								},false);
							}
						}

						var timeline_grid_drop = document.getElementById('timeline-grid');
						timeline_grid_drop.addEventListener('dragover', function (e) {
							e.stopPropagation();
							if (e.preventDefault) {

							e.preventDefault();
							}
						}, false);

						timeline_grid_drop.addEventListener('drop', function (e) {
							e.stopPropagation();
							e.preventDefault();
							var start_frame,end_frame;
							if(Ext.isIE){
								start_frame = e.dataTransfer.valueOf("start_frame");
								end_frame = e.dataTransfer.valueOf("end_frame");
							} else {
								start_frame = e.dataTransfer.getData("start_frame");
								end_frame = e.dataTransfer.getData("end_frame");
							}
							var start_sec = start_frame /frame_rate;
							var end_sec = end_frame /frame_rate;

							var save_type = 'add';
							<?php
								if($is_group == 'G') {
									echo "save_type = 'group-add';";
								}
							?>
							var grid = Ext.getCmp('timeline-grid');
							if(grid.id =='timeline-grid' ){
								Ext.getCmp('player_warp').insertTimeLine( grid, start_sec, end_sec ,false);
							}else if( grid.id =='preview-edit-grid' ){
								Ext.getCmp('player_warp').insertTimeLine( grid, start_sec, end_sec , true );
							}
							grid.isDirty = true;
							var datas = [];
							var datalist = grid.getStore().getRange();
							Ext.each(datalist,function(r){
								datas.push(r.data);
							});
							Ext.getCmp('player_warp').Save(grid, content_id, 'shot_list', save_type, datas , true ,true );
						}, false);
					}
				},
				viewConfig:{
					emptyText : _text('MSG00148'),
					forceFit : true,
					getRowClass: function(record, index, rowParams, store) {
						return 'multiline_row_line';
					}
				}
			};

			// 마커 상세
			var fn_marker_detail_show = function () {
				var store_mark = Ext.getCmp('marker-grid').getStore();
				var videojs_player = videojs('player3');
				var sel = Ext.getCmp('marker-grid').getSelectionModel().getSelected();
				videojs_player.pause();
				var current_time = videojs_player.currentTime();
				var current_time_text = secFrameToTimecode(current_time, frame_rate);
				if (store_mark.getCount() > 0){
					var create_new_mark =  new Ext.Window({
						title: _text('MN02437'),
						cls: 'change_background_panel remove_border_toolbar',
						width: 500,
						modal: true,
						height: 380,
						miniwin: true,
						//resizable: false,
						layout: 'fit',
						buttonAlign: 'center',
						tbar: [{
							xtype:'button',
							cls : 'proxima_btn_customize',
							text: '<span style="position:relative;" title="'+_text('MN02375')+'"><i class="fa fa-arrow-left" style="font-size:13px;color:white;"></i></span>',//back
							scale: 'medium',
							handler: function(b, e){
								var i = 0;
								var j = 0;
								store_mark.each(function(rec) {
									if (rec.get('mark_id') == Ext.getCmp('mark_form').getForm().getValues().mark_id) {
										if(i == 0){
											j = store_mark.getCount()-1;
										} else {
											j = i-1;
										}
									}
									i++;
								});
								var record = store_mark.getAt(j);
								Ext.getCmp('mark_form').getForm().loadRecord(record);
								Ext.getCmp('mark_color_input').getEl().setStyle('background', record.get('color'));
								var sm = Ext.getCmp('marker-grid').getSelectionModel();
								sm.selectRow(j);
							}
						},{
							xtype:'button',
							cls : 'proxima_btn_customize',
							text: '<span style="position:relative;" title="'+_text('MN02376')+'"><i class="fa fa-arrow-right" style="font-size:13px;color:white;"></i></span>',//next
							scale: 'medium',
							handler: function(b, e){
								var i = 0;
								var j = 0;
								store_mark.each(function(rec) {
									if (rec.get('mark_id') == Ext.getCmp('mark_form').getForm().getValues().mark_id) {
										if(i == store_mark.getCount()-1){
											j = 0;
										} else {
											j = i+1;
										}
									}
									i++;
								});
								var record = store_mark.getAt(j);
								Ext.getCmp('mark_form').getForm().loadRecord(record);
								Ext.getCmp('mark_color_input').getEl().setStyle('background', record.get('color'));
								var sm = Ext.getCmp('marker-grid').getSelectionModel();
								sm.selectRow(j);
							}
						}],
						buttons: [
						{
							text : '<span style="position:relative;top:1px;"><i class="fa fa-arrow-left" style="font-size:13px;"></i></span>&nbsp;'+_text('MN02375'),
							scale: 'medium',
							hidden: true,
							handler: function(b, e){
								var i = 0;
								var j = 0;
								store_mark.each(function(rec) {
									if (rec.get('mark_id') == Ext.getCmp('mark_form').getForm().getValues().mark_id) {
										if(i == 0){
											j = store_mark.getCount()-1;
										} else {
											j = i-1;
										}
									}
									i++;
								});
								var record = store_mark.getAt(j);
								Ext.getCmp('mark_form').getForm().loadRecord(record);
								Ext.getCmp('mark_color_input').getEl().setStyle('background', record.get('color'));
								var sm = Ext.getCmp('marker-grid').getSelectionModel();
								sm.selectRow(j);
							}
						},{
							text : _text('MN02376') + ' <span style="position:relative;top:1px;"><i class="fa fa-arrow-right" style="font-size:13px;"></i></span>&nbsp;',
							scale: 'medium',
							hidden: true,
							handler: function(b, e){
								var i = 0;
								var j = 0;
								store_mark.each(function(rec) {
									if (rec.get('mark_id') == Ext.getCmp('mark_form').getForm().getValues().mark_id) {
										if(i == store_mark.getCount()-1){
											j = 0;
										} else {
											j = i+1;
										}
									}
									i++;
								});
								var record = store_mark.getAt(j);
								Ext.getCmp('mark_form').getForm().loadRecord(record);
								Ext.getCmp('mark_color_input').getEl().setStyle('background', record.get('color'));
								var sm = Ext.getCmp('marker-grid').getSelectionModel();
								sm.selectRow(j);
							}
						},{
							text : '<span style="position:relative;top:1px;"><i class="fa fa-edit" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00043'),
							scale: 'medium',
							handler: function(b, e){
								var mark_title_input = Ext.getCmp('mark_title_input').getValue();
								var mark_comments_input = Ext.getCmp('mark_comments_input').getValue();
								var mark_id = Ext.getCmp('mark_form').getForm().getValues().mark_id;
								var color = Ext.getCmp('mark_form').getForm().getValues().color;
								Ext.MessageBox.confirm(_text ('MN00024'),_text ('MSG02168'), function(btn){
									if(btn === 'yes'){
										Ext.Ajax.request({
											url: '/store/video_mark/mark_action.php',
											params: {
												action: "edit",
												title: mark_title_input,
												comments: mark_comments_input,
												mark_id : mark_id,
												content_id: content_id,
												color: color
											},
											callback: function(opt, success, response){
												//create_new_mark.close();
												store_mark.load({
													params: {
														content_id : content_id,
														action: 'get'
													}
												});
											}
										});
									}
								});
							}
						},{
							text : '<span style="position:relative;top:1px;"><i class="fa fa-trash" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00034'),
							scale: 'medium',
							handler: function(b, e){
								var mark_title_input = Ext.getCmp('mark_title_input').getValue();
								var mark_comments_input = Ext.getCmp('mark_comments_input').getValue();
								var mark_id = Ext.getCmp('mark_form').getForm().getValues().mark_id;

								Ext.Ajax.request({
									url: '/store/video_mark/mark_action.php',
									params: {
										action: "del",
										mark_id : mark_id,
										content_id: content_id
									},
									callback: function(opt, success, response){
										//create_new_mark.close();
										var i = 0;
										var j = 0;
										var temp = 0;
										store_mark.each(function(rec) {
											if (rec.get('mark_id') == Ext.getCmp('mark_form').getForm().getValues().mark_id) {
												if(i == store_mark.getCount()-1){
													j = 0;
												} else {
													j = i+1;
												}
												temp = i;
											}
											i++;
										});
										var record = store_mark.getAt(j);
										store_mark.removeAt(temp);
										if (store_mark.getCount() > 0){
											Ext.getCmp('mark_form').getForm().loadRecord(record);
										} else {
											create_new_mark.close();
											store_mark.load({
												params: {
													content_id : content_id,
													action: 'get'
												}
											});
										}
									}
								});
							}
						},{
							text : '<span style="position:relative;top:1px;"><i class="fa fa-close" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00031'),
							scale: 'medium',
							handler: function(b, e){
								create_new_mark.close();
								document.getElementById("player3").focus();
								store_mark.load({
									params: {
										content_id : content_id,
										action: 'get'
									}
								});
							}
						}],
						items:[{
							xtype: 'form',
							id: 'mark_form',
							frame: true,
							items: [{
								xtype: 'textfield',
								//allowBlank: false,
								anchor:'100%',
								fieldLabel: _text('MN02438'),
								id: 'mark_title_input',
								name: 'title'
							},{
								xtype: 'hidden',
								//allowBlank: false,
								anchor:'100%',
								labelStyle: 'width: 200px;',
								fieldLabel: _text('MN02173') + ': ' + current_time_text + '      '+ _text('MN02120') ,
								value: '00:00:00:00',
								invalidText : '00:00:00:00 ~ 23:59:59:99',
								regex: /(0[0-9]|1[0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9]):([0-9][0-9])/,
								plugins: [new Ext.ux.InputTextMask('99:99:99:99')],
								id: 'duration_input'
							},{
								xtype: 'textfield',
								fieldLabel: _text('MN02173'),
								readOnly: true,
								style: 'border: none;',
								name: 'start_frame_time_code',
								anchor:'100%'
							},{
								xtype: 'textarea',
								//allowBlank: false,
								anchor:'100%',
								height: 150,
								fieldLabel: _text('MN01036'),
								id: 'mark_comments_input',
								name: 'comments'
							},{
								xtype: 'hidden',
								name: 'mark_id'
							},{
								xtype :'colorfield',
								anchor:'100%',
								name: 'color',
								fieldLabel: _text('MN02440'),
								//value: '#0000FF',
								id: 'mark_color_input',
								//msgTarget: 'qtip',
								fallback: true,
								//colors: colors_arr
							}]
						}]
					}); // end create new mark window
					var index = store_mark.indexOf(sel);
					var record = store_mark.getAt(index);
					Ext.getCmp('mark_form').getForm().loadRecord(record);
					create_new_mark.show();
				}
			};

			var mark_type_list = [];
			mark_type_list.push('MARK');
			mark_type_list.push('MARK_IN');
			mark_type_list.push('MARK_OUT');

			// 마커
			var marker_panel = {
				title: _text('MN02437'),
				xtype: 'grid',
				cls: 'proxima_customize_grid_for_group proxima_grid_header',
				stripeRows: true,
				flex: 1,
				enableDragDrop: false,
				ddGroup: 'EditCutGridDD',
				id: 'marker-grid',
				enableColumnMove: false,
				/*
				tbar: [
				<?php if($arr_sys_code['download_xml']['use_yn'] == 'Y'){ ?>
				_text('MN00050')+':',{
					xtype: 'component',
					width: 50,
					html: '<img align="center" id="marker_edl" draggable="true" src="/led-icons/download_bicon.png" width="25px" title="<?=_text('MSG02029')?>"  />'
				}
				<?php } ?>
				],
				*/
				store: new Ext.data.JsonStore({
					url: '/store/video_mark/mark_action.php',
					root: 'data',
					autoLoad : false,
					baseParams: {
						content_id: <?=$content_id?>,
						action : 'get',
						mark_type_list: Ext.encode(mark_type_list),
						is_group: '<?=$is_group?>'
					},
					fields:[
						{ name : 'content_id' },
						{ name : 'mark_id' },
						{ name : 'mark_type' },
						{ name : 'start_frame' },
						{ name : 'end_frame' },
						{ name : 'title' },
						{ name : 'comments' },
						{	name : 'color', convert: function(v, record) {
								if(record.color == null){
									return '#FF0000';
								}else {
									return record.color;
								}
							}
						},
						{ name : 'status' },
						{ name : 'start_frame_time_code', convert: function(v, record) {
							record.start_frame_time_code = secFrameToTimecode(record.start_frame/frame_rate, frame_rate);
							return record.start_frame_time_code;
							}
						}
					],
					listeners: {
						beforeload: function(self, opts){
							opts.params = opts.params || {};
						},
						load: function(store, records, opts){
							var videojs_player = videojs('player3');
							var data_array = [];
							<?php
								echo " var child_content_id = content_id;";
								if($is_group == 'G') {
									echo "var group_list = Ext.getCmp('group_child_list').getSelectionModel().getSelected();
										if(group_list){
											child_content_id = group_list.get('content_id');
										}";
								}
							?>
							Ext.each(records, function(record){
								if (record.get('content_id') == child_content_id){
									var data_temp = {
										time: record.get('start_frame')/frame_rate,
										time_code: secFrameToTimecode(record.get('start_frame')/frame_rate,frame_rate),
										text: record.get('title'),
										comments: record.get('comments'),
										mark_id: record.get('mark_id'),
										mark_type: record.get('mark_type'),
										color: record.get('color')
									}
									data_array.push(data_temp);
								}
								if (record.get('mark_type') == 'MARK_IN' || record.get('mark_type') == 'MARK_OUT'){
									store.remove( record );
								}
							});
                            if(!Ext.isEmpty(videojs_player.markers)) {
                                videojs_player.markers.reset(data_array);
                            }
						}
					}
				}),
				colModel: new Ext.grid.ColumnModel({
					defaultSortable: false,
					columns: [
						new Ext.grid.RowNumberer(),
						{header: _text('MN02296'), dataIndex: 'mark_id', hidden: true},
						{header: _text('MN02297'), dataIndex: 'mark_type', hidden: true},
						{header: _text('MN00405'), dataIndex: 'start_frame_time_code', width:35, align: 'center'},
						{header: _text('MN00249'), dataIndex: 'title'},
						{header: _text('MN02439'), dataIndex: 'comments',
							renderer: function(value, metaData, record){
								metaData.attr = 'title="' + record.get('comments') + '"';
								return value;
							}
						},
						{
						header: _text('MN02446'),sortable:false, width:35, align: 'center',
						renderer: function(value, metaData, record, rowIndex, colIndex, store) {
								return '<i class="fa fa-circle" style="margin-left:5px;color:'+record.get('color')+';"></i>  ';
							}
						}
					]
				}),
				sm: new Ext.grid.RowSelectionModel({
					singleSelect:true
				}),
				listeners: {
					rowcontextmenu: function(self, rowIdx, e){
						e.stopEvent();
						var sm = self.getSelectionModel();
						sm.selectRow(rowIdx);
						var r = sm.getSelected();

						var menu_f = new Ext.menu.Menu({
							items: [{
								text: _text('MN00034'),//'삭제',
								icon: '/led-icons/delete.png',
								handler: function(btn, e){
									Ext.Ajax.request({
										url: '/store/video_mark/mark_action.php',
										params: {
											action: "del",
											mark_id : r.json.mark_id,
											content_id: content_id
										},
										callback: function(opt, success, response){
											Ext.getCmp('marker-grid').getStore().reload();
										}
									});
								}
							},{
								text: _text('MN00043'),
								icon: '/led-icons/page_white_edit.png',
								handler: fn_marker_detail_show
							}],
							listeners: {
								render: function(self){
								}
							}
						});
						menu_f.showAt(e.getXY());
					},
					rowclick : function(self, index, e){
						var sel = self.getSelectionModel().getSelected();
						var player3 = videojs(document.getElementById('player3'), {}, function(){
						});
						<?php
							echo " var child_content_id = content_id;";
							if($is_group == 'G') {
								echo "var group_list = Ext.getCmp('group_child_list').getSelectionModel().getSelected();
									if(group_list){
										var child_content_id = group_list.get('content_id');
									}";
							}
						?>
						if (child_content_id == sel.get('content_id')){
							player3.currentTime(sel.get('start_frame')/frame_rate);
							fn_selected_marker(sel.get('mark_id'));
						} else {
							var group_child_list = Ext.getCmp('group_child_list');
							var group_list_array = group_child_list.getStore().getRange();
							for(var i=0;i< group_list_array.length;i++)
							{
								if (sel.get('content_id') == group_list_array[i].get('content_id')){
									group_child_list.getSelectionModel().selectRow(i);
									group_child_list.fireEvent('rowselect', group_child_list);
									var TabMarkerToCheck = Ext.getCmp('media_content_panel_warp').findById('marker-grid');
									if(TabMarkerToCheck){
										player3.currentTime(sel.get('start_frame')/frame_rate);
										fn_selected_marker(sel.get('mark_id'));
									}
								}
							}
						}
					},
					rowdblclick : fn_marker_detail_show,
					afterrender: function(self){
						if('<?=$_REQUEST['win_type']?>' == 'zodiac') return;
						//예) application/{확장자}:{파일명}:file:///{풀파일경로}
						var grid = Ext.getCmp('tab_warp').getActiveTab().get(0);
						var sm = grid.getSelectionModel();
						var r = sm.getSelected();
						var ori_ext= 'xml';
						var ori_path= r.json.proxy_path;

						//empty path return
						if(!ori_path) return;

						var ori_path_array = ori_path.split('/');

						ori_path_array.pop();
						ori_path_array.pop();

						var ori_path = ori_path_array.join('/');

						var filename = r.json.content_id;
						var highres_path= r.json.lowres_root;
						var path = highres_path + '/' + ori_path+'/' + filename +'_marker.'+ ori_ext;
						if(Ext.isMac){
							var highres_path = r.json.lowres_mac_path;
							var path = highres_path + '/' + ori_path+'/' + filename +'_marker.'+ ori_ext;
						}else{
							var highres_path= r.json.lowres_root;
							var path = highres_path + '/' + ori_path+'/' + filename +'_marker.'+ ori_ext;
							path = path.replace(/\//gi, '\\\\');
						}


						var edl_path = 'application/gmsdd:{"medias":["'+path+'"]}';

						var premiere_mark_edl_path = path;

						if(Ext.isPremiere){
							var edl_path = path;
							var thumb_img = document.getElementById('marker_edl');
							if( !Ext.isEmpty(thumb_img) ){
								//객체가 있을경우 URL 셋팅 - 섬네일용
								thumb_img.addEventListener("dragstart",function(evt){
								//alert(edl_path);
									premiere_evt(evt,premiere_mark_edl_path,1);
								},false);
							}
						}

						//데이터뷰에서 각 이미지에 설정해놓은 ID로 객체를 찾는다
						else if(Ext.isChrome){
							var thumb_img = document.getElementById('marker_edl');
							if( !Ext.isEmpty(thumb_img) ){
								//객체가 있을경우 URL 셋팅 - 섬네일용
								thumb_img.addEventListener("dragstart",function(evt){
									evt.dataTransfer.setData("DownloadURL",edl_path);
								},false);
							}
						}

						var marker_grid_drop = document.getElementById('marker-grid');
						marker_grid_drop.addEventListener('dragover', function (e) {
							e.stopPropagation();
							if (e.preventDefault) {

							e.preventDefault();
							}
						}, false);

						marker_grid_drop.addEventListener('drop', function (e) {
							e.stopPropagation();
							e.preventDefault();
							var start_frame,end_frame;
							if(Ext.isIE){
								start_frame = e.dataTransfer.valueOf("start_frame");
								end_frame = e.dataTransfer.valueOf("end_frame");
							} else {
								start_frame = e.dataTransfer.getData("start_frame");
								end_frame = e.dataTransfer.getData("end_frame");
							}
							var videojs_player = videojs(document.getElementById('player3'), {}, function(){});
							var current_time = Math.ceil(start_frame/frame_rate * 100000)/100000;
							videojs_player.markers.add([{
								time: current_time,
								text: '',
								time_code: secFrameToTimecode(current_time, frame_rate),
								mark_type: 'MARK',
							}]);
							<?php
								echo " var child_content_id = content_id;";
								if($is_group == 'G') {
									echo "var group_list = Ext.getCmp('group_child_list').getSelectionModel().getSelected();
										if(group_list){
											child_content_id = group_list.get('content_id');
										}";
								}
							?>
							Ext.Ajax.request({
								url : '/store/video_mark/mark_action.php',
								params : {
									mark_type: 'MARK',
									content_id :child_content_id,
									parent_content_id : content_id,
									action: 'add',
									start_frame: Math.floor(current_time*frame_rate),
									end_frame: Math.floor(current_time*frame_rate),
								},
								callback : function(opts, success, response){
									if (success){
										try{
											var r = Ext.decode(response.responseText);
											if(r.success){
												Ext.getCmp('marker-grid').getStore().reload();
											}else{
												Ext.Msg.alert(_text('MN00022'), r.msg);
											}
										}catch(e){
											Ext.Msg.alert(_text('MN00022'), e+'<br />'+response.responseText);
										}
									}else{
										Ext.Msg.alert(_text('MN00022'), response.statusText);
									}
								}
							});
						}, false);
					},
				},
				viewConfig:{
					emptyText : _text('MSG00148'),
					forceFit : true,
					getRowClass: function(record, index, rowParams, store) {
						return 'multiline_row_line';
					}
				}
			};

			// 리스트뷰 패널
			<?php
			if($columns != '')
			{
			?>
			var listview_panel = {
				xtype: 'panel',
				id: 'tc_panel',
				frame: true,
				//hidden: true,
				layout: 'fit',
				items: {
					xtype: 'form',
					padding: 5,
					frame: true,
					autoScroll: true,
					defaultType: 'textfield',

					items: [
						<?=$columns['columns']?>
					],

					listeners: {
						afterrender: function(self){
							//self.get(0).focus(false, 250);
						}
					}
				},
				buttonAlign: 'left',
				buttons: [{
					hidden: true,
					text: '구간 추출 생성',
					handler: function(b, e){
						//$f('player2').pause();

						Ext.Msg.show({
							title: '확인',
							msg: '구간 추출 클립을 생성 하시겠습니까?',
							icon: Ext.Msg.QUESTION,
							buttons: Ext.Msg.OKCANCEL,
							fn: function(btnId){
								if ( btnId == 'ok')
								{

									var setInSec, setOutSec;
									var markers = videojs('player3').markers.getMarkers();
									for (var i = 0; i < markers.length; i++) {
										if (markers[i].mark_type == 'MARK_IN'){
											setInSec = markers[i].time;
										} else if (markers[i].mark_type == 'MARK_OUT'){
											setOutSec = markers[i].time;
										}
									}

									var form = Ext.getCmp('tc_panel').get(0).getForm();
									var values = form.getFieldValues();

									if( Ext.isEmpty( values ) )
									{
										Ext.Msg.alert('정보', '생성될 정보가 없습니다.');
										return;
									}

									var txt = checkInOut(setInSec, setOutSec);
									if ( !Ext.isEmpty(txt) )
									{
										Ext.Msg.alert('정보', txt);
										return;
									}

									var duration = secFrameToTimecode((setOutSec - setInSec), frame_rate);

									var wait_msg = Ext.Msg.wait('등록중입니다.', '요청');
									Ext.Ajax.request({
										url: '/store/create_pfr.php',
										params: {
											content_id: <?=$content_id?>,
											//vr_meta: Ext.encode(values),
											start: setInSec,
											end: setOutSec
										},
										callback: function(opts, success, response){
											wait_msg.hide();
											if (success)
											{
												try
												{
													var r = Ext.decode(response.responseText);
													if (r.success)
													{

														Ext.Msg.show({
															title: '확인',
															msg: '구간 추출 클립이 등록되었습니다.<br />창을 닫으시겠습니까?',
															icon: Ext.Msg.QUESTION,
															buttons: Ext.Msg.OKCANCEL,
															fn: function(btnId){
																if (btnId == 'ok')
																{
																	Ext.getCmp('winDetail').close();
																}
															}
														});
													}
													else
													{
														Ext.Msg.alert('확인', r.msg);
													}
												}
												catch(e)
												{
													Ext.Msg.alert('오류', response.responseText);
												}
											}
											else
											{
												Ext.Msg.alert('서버 통신 오류', response.statusText);
											}
										}
									});
								}
							}
						});
					}
				},{
					xtype: 'tbfill'
				},{
					<?php
					if ( ! checkAllowUdContentGrant($user_id, $ud_content_id, GRANT_EDIT)) {
						//echo "hidden: true,";
					}
					?>
					hidden: true,
					text: _text('MN00003'),
					icon: '/led-icons/application_edit.png',
					handler: function(b, e){

						var parent = b.ownerCt.ownerCt;
						var msg = 'Do it?';
						if( parent.title == _text('MN00033') )//add //!!추가
						{
							msg = _text('MSG00174');
						}
						else //edit
						{
							msg = _text('MSG00175');
						}

						Ext.Msg.show({
							title: _text('MN00003'),
							msg: msg,
							icon: Ext.Msg.QUESTION,
							buttons: Ext.Msg.OKCANCEL,
							fn: function(btnId){
								if ( btnId == 'ok')
								{
									var form = parent.get(0).getForm();
									var values = form.getFieldValues();

									var list = Ext.getCmp('list<?=$usr_meta_field_id?>');
									var list_store = list.store;

									if( parent.title == _text('MN00033') )
									{
										var start_tc_sec = timecodeToSecFrame( values.columnB , frame_rate) ;
										var end_tc_sec = timecodeToSecFrame( values.columnC , frame_rate) ;
									}
									else
									{
										var start_tc_sec = timecodeToSecFrame( values.columnB , frame_rate);
										var end_tc_sec = timecodeToSecFrame( values.columnC , frame_rate);
									}

									values.columnB = secFrameToTimecode( start_tc_sec, frame_rate );
									values.columnC = secFrameToTimecode( end_tc_sec, frame_rate );

									var new_record = new list_store.recordType( values );
									if ( parent.title == _text('MN00033') )
									{
										list_store.add( new_record );
									}
									else
									{
										var old_record = list.getSelectedRecords()[0];
										var idx = list_store.indexOf( old_record );

										list_store.remove( old_record );
										list_store.insert( idx, new_record );
									}
									var outer = list.ownerCt;
									outer.submit(outer, parent, parent.title);
								}
							}
						});
					}
				},{
					hidden: true,
					//!!text: '취소',
					text: _text('MN00004'),
					icon: '/led-icons/cancel.png',
					handler: function(b, e){

						b.ownerCt.ownerCt.setVisible(false);
					}
				}],

				listeners: {
					hide: function(self){
						self.get(0).getForm().reset();
						if ( ! Ext.isEmpty(Ext.getCmp('tc_category'))) {
							Ext.getCmp('tc_category').setRawValue();
						}
					}
				}
			};
			<?php
			}
			?>
			
			// 영상 플레이어 하단에 위치한 패널(샷리스트와 마커)
			var media_content_panel = {
				region: 'center',
				xtype: 'tabpanel',
				id: 'media_content_panel_warp',
				title: _text('MN00164'),
				enableTabScroll:true,
				defaults: {
					layout: 'fit',
					autoScroll: true
				},
				activeTab: 0,
				flex: 1,
				border: false,
				split: true,
				cls:'proxima_tabpanel_customize proxima_media_tabpanel',
				width: 520,
				items : [
					<?php
						if($arr_sys_code['mark_list_use_yn']['use_yn'] == 'Y'){
							$marker_yn = "marker_panel";
						} else {
							$marker_yn = " ";
						}
						if( $arr_sys_code['shot_list_yn']['use_yn'] == 'Y' ){
							if ($is_group == 'G' && $bs_content_id != SEQUENCE) {
								echo "group_list_panel,rough_cut_panel,".$marker_yn;
							} else if ($_REQUEST['mode'] == 'review') {
								echo "review_panel,".$marker_yn;
							} else {
								//echo "listview_panel";
								echo "rough_cut_panel,".$marker_yn;
							}
						} else {
							if ($is_group == 'G' && $bs_content_id != SEQUENCE) {
								echo "group_list_panel,".$marker_yn;
							} else if ($_REQUEST['mode'] == 'review') {
								echo "review_panel,".$marker_yn;
							} else {
								echo $marker_yn;
							}
						}	
						
						// if(defined('CUSTOM_ROOT') && class_exists('\ProximaCustom\login\Login')) {
						// 	$login = new \ProximaCustom\login\Login();
						// }

						// custom tab
						if(defined('CUSTOM_ROOT') && class_exists('\ProximaCustom\core\DetailPanelCustom')) {
							\ProximaCustom\core\DetailPanelCustom::renderInOutList($user_id, $ud_content_id);
						}
					?>
					
				],
				listeners: {
					render: function(self){
					},
					beforetabchange: function(self, n, c){
					},
					tabchange: function(self, p) {
						if (p.id == 'timeline-grid'){
							Ext.getCmp('timeline-grid').getStore().reload();
						} else if (p.id == 'marker-grid'){
							Ext.getCmp('marker-grid').getStore().reload();
						}
					}
				},
			}

			var review_panel = {
				title: '심 의',
				margins : '5 5 5 5',
				xtype: 'form',
				items: [
				{
					xtype: 'hidden',
					name: 'k_content_id',
					value: '<?=$content_id?>'
				},{
					xtype: 'hidden',
					name: 'k_request_type',
					value: 'review'
				},{
					anchor: '90%',
					fieldLabel: '심의 결과',
					xtype : 'combo',
					typeAhead: true,
					triggerAction: 'all',
					mode : 'local',
					editable : false,
					value: 'accept',
					valueField: 'value',
					displayField: 'name',
					name: 'review_status',
					hiddenName: 'review_status',
					store: new Ext.data.SimpleStore({
						fields: [
							'value','name'
						],
						data: [
							['accept', '승인'],
							['refuse', '반려'],
							['progressing', '진행중']
						]
					})
				},{
					anchor: '90% 40%',
					fieldLabel: '지적사항 및<br /> 조치내용',
					name: 'review_point_out',
					emptyText: '',
					xtype: 'textarea'
				},{
					anchor: '90% 40%',
					fieldLabel: '심의 의견',
					name: 'review_comments',
					emptyText: '',
					xtype: 'textarea'
				}
				],

				listeners: {
					render: function(self){
						Ext.Ajax.request({
							url: '/store/nps_work/get_review_detail_list.php',
							params: {
								type: 'detail',
								content_id: <?=$content_id?>
							},
							callback: function(opts, success, response){
								if (success)
								{
									try
									{
										var r = Ext.decode(response.responseText);
										if (r.success)
										{

											var data = new Ext.data.Record(r.data);

											self.getForm().loadRecord(data);
											//if( r.data.review_status == 'accept' || r.data.review_status == 'refuse'){
											//	self.buttons[1].setDisabled(true);
											//}
										}
										else
										{
											Ext.Msg.alert('확인', r.msg);
										}
									}
									catch(e)
									{
										Ext.Msg.alert('오류', response.responseText);
									}
								}
								else
								{
									Ext.Msg.alert('서버 통신 오류', response.statusText);
								}
							}
						});
					}
				},
				buttonAlign: 'center',
				buttons: [{

					scale: 'medium',
					text: '저해상도 다운로드',
					icon: '/led-icons/download_sicon.jpg',
					handler: function(b, e){
						var content_id = this.ownerCt.ownerCt.getForm().findField('k_content_id').getValue();

						var rs = [];

						rs.push(content_id);

						Ext.Ajax.request({
							url: '/store/download_use_air.php',
							params: {
								flag : 'media',
								media_type : 'proxy',
								content_id_list : Ext.encode(rs)
							},
							callback: function(opts, success, response){
								var air_badge = response.responseText;
								new Ext.Window({
									title: '다운로더',
									modal: true,
									layout: 'fit',
									width: 200,
									height: 110,
									padding: 10,

									items: {
										html: '<div align="center"><h3>다운로더를 실행합니다.<br>' + air_badge + '</div>'
									}
								}).show();
							}
						});
					}
				},{
					text: '저장',
					scale: 'medium',
					icon: '/led-icons/accept.png',
					handler: function(b, e){
						var form = this.ownerCt.ownerCt.getForm();
						var values = form.getValues();

						form.submit({
							clientValidation: true,
							url: '/store/nps_work/review_update.php',
							//params: values,
							success: function(form, action) {
								Ext.Msg.show({
									title: '확인',
									msg: action.result.msg+'<br />'+'창을 닫으시겠습니까?',
									icon: Ext.Msg.QUESTION,
									buttons: Ext.Msg.OKCANCEL,
									fn: function(btnId){
										if ( btnId == 'ok')
										{
											Ext.getCmp('review_list_grid').getStore().reload();
											Ext.getCmp('winDetail').close();
										}
									}
								});

							},
							failure: function(form, action) {
								switch (action.failureType) {
									case Ext.form.Action.CLIENT_INVALID:
										Ext.Msg.alert('실패', 'Form fields may not be submitted with invalid values');
										break;
									case Ext.form.Action.CONNECT_FAILURE:
										Ext.Msg.alert('실패', 'Ajax communication failed');
										break;
									case Ext.form.Action.SERVER_INVALID:
									   Ext.Msg.alert('실패', action.result.msg);
								}
							}
						});

					}
				},{
					//!!text: '취소',
					scale: 'medium',
					text: _text('MN00004'),
					icon: '/led-icons/cancel.png',
					handler: function(b, e){
						//b.ownerCt.ownerCt.setVisible(false);
					}
				}]
            };
            
			Ext.QuickTips.init();
			this.items = {
				border: false,
				layout: 'border',

				items: [
				// 플레이어
				{
					layout: 'border',
					region: 'center',
					border: false,
					width: 620,
					minWidth: 620,//Due to player button
					items: [					
					{
						region: 'center',
						id: 'player_warp',
						bodyStyle: 'background-color:black;',
						width: 900,
						height: '50%',
						minHeight: 300,
						html: '<?=$html_text?>',
						//html: '<video id="player3" class="vjs-skin-twitchy video-js vjs-big-play-centered" preload="auto" autoplay controls style="width:100%;height:100%;" data-setup=\'{ "inactivityTimeout": 0, "playbackRates": [0.5, 1, 1.5, 2, 3, 4, 8], "frame_rate":<?=$frame_rate?> }\'><source src="<?=$video_js_path?>" type="video/mp4"></video>',
						listeners: {
							afterrender: function(self){
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

                                    var fn_seekLeft1 = function () {
                                        SeekHtmlPlayer(-1);
                                    }
                                    var fn_seekRight1 = function () {
                                        SeekHtmlPlayer(1);
                                    }
                                    var fn_seekLeft2 = function () {
                                        SeekHtmlPlayer(-10);
                                    }
                                    var fn_seekRight2 = function () {
                                        SeekHtmlPlayer(10);
                                    }

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

									var fn_loop = function () {
                                        var set_in_time = '';
                                        var set_out_time = '';
                                        var current_time = 0;
                                        var i = 1;
                                        var markers = videojs_player.markers.getMarkers();
                                        for (var i = 0; i < markers.length; i++) {
											if (markers[i].mark_type == 'MARK_IN'){
												set_in_time = markers[i].time;
                                            }
                                            if (markers[i].mark_type == 'MARK_OUT'){
												set_out_time = markers[i].time;
											}
                                        }

                                        if(Ext.isEmpty(set_in_time)) {
                                            Ext.Msg.alert(_text('MN00023'), _text('MSG00191'));//In 값이 비어 있습니다
                                            return;
                                        }
                                        if(Ext.isEmpty(set_out_time)) {
                                            Ext.Msg.alert(_text('MN00023'), _text('MSG00192'));//Out 값이 비어 있습니다
                                            return;
                                        }
                                        if(set_in_time == set_out_time) {
                                            Ext.Msg.alert(_text('MN00023'), _text('MSG00193'));//In 과 Out 값이 동일합니다
                                            return;
                                        }
                                        if(set_in_time > set_out_time) {
                                            Ext.Msg.alert(_text('MN00023'), _text('MSG00194'));//Out 보다 In 값이 더 큽니다
                                            return;
                                        }
                                        
                                        videojs_player.currentTime(set_in_time);
                                        current_time = videojs_player.currentTime();
                                        loop_play(videojs_player, set_in_time, set_out_time);
									};
									var loop_btn = document.createElement('div');
									loop_btn.id = 'loop_btn';
									loop_btn.className = 'vjs-control-custom';
									var loop_text = document.createElement('span');
									loop_text.className = 'fa fa-lg fa-repeat';
									loop_btn.appendChild(loop_text);
									loop_btn.title = _text('MN02431');
									loop_btn.onclick = fn_loop;

									// 마커 추가 기능
									var fn_mark = function () {
										if (timer) clearTimeout(timer);
										timer = setTimeout(function() {
											var current_time = videojs_player.currentTime();
											videojs_player.markers.add([{
												time: current_time,
												text: '',
												time_code: secFrameToTimecode(current_time, frame_rate),
												mark_type: 'MARK',
											}]);
											var TabToCheck = Ext.getCmp('media_content_panel_warp').findById('marker-grid');
											if(TabToCheck){
												Ext.getCmp('media_content_panel_warp').setActiveTab('marker-grid');
											}
											<?php
												echo " var child_content_id = content_id;";
												if($is_group == 'G') {
													echo "var group_list = Ext.getCmp('group_child_list').getSelectionModel().getSelected();
														if(group_list){
															child_content_id = group_list.get('content_id');
														}";
												}
											?>
											Ext.Ajax.request({
												url : '/store/video_mark/mark_action.php',
												params : {
													mark_type: 'MARK',
													content_id :child_content_id,
													parent_content_id : content_id,
													action: 'add',
													start_frame: Math.floor(current_time*frame_rate),
													end_frame: Math.floor(current_time*frame_rate),
												},
												callback : function(opts, success, response){
													if (success){
														try{
															var r = Ext.decode(response.responseText);
															if(r.success){
																Ext.getCmp('marker-grid').getStore().reload();
															}else{
																Ext.Msg.alert(_text('MN00022'), r.msg);
															}
														}catch(e){
															Ext.Msg.alert(_text('MN00022'), e+'<br />'+response.responseText);
														}
													}else{
														Ext.Msg.alert(_text('MN00022'), response.statusText);
													}
												}
											}); 
										}, 250); 
									};

									var mark_btn = document.createElement('div');
									mark_btn.id = 'mark_btn';
									mark_btn.className = 'vjs-control-custom';
									var mark_text = document.createElement('span');
									mark_text.className = 'fa fa-lg icon-rotate-225 fa-tag';
									mark_text.style = 'margin-top: 8px;';
									mark_btn.appendChild(mark_text);
									mark_btn.title = _text('MN02433');
									//mark_btn.ondblclick = fn_mark_popup_show;
									//mark_btn.oncontextmenu = fn_mark_menu;
									mark_btn.onclick = fn_mark;

									// 마커 삭제
									var fn_clean_mark = function () {
										Ext.MessageBox.confirm(_text ('MN00034'),_text ('MSG02167'), function(btn){
											if(btn === 'yes'){
												<?php
													echo " var child_content_id = content_id;";
													if($is_group == 'G') {
														echo "var group_list = Ext.getCmp('group_child_list').getSelectionModel().getSelected();
															if(group_list){
																child_content_id = group_list.get('content_id');
															}";
													}
												?>
												Ext.Ajax.request({
													url : '/store/video_mark/mark_action.php',
													params : {
														content_id : child_content_id,
														action: 'del'
													},
													callback : function(opts, success, response){
														if (success){
															try{
																var r = Ext.decode(response.responseText);
																if(r.success){
																	videojs_player.markers.removeAll();
																	Ext.getCmp('marker-grid').getStore().reload();
																}else{
																	Ext.Msg.alert(_text('MN00022'), r.msg);
																}
															}catch(e){
																Ext.Msg.alert(_text('MN00022'), e+'<br />'+response.responseText);
															}
														}else{
															Ext.Msg.alert(_text('MN00022'), response.statusText);
														}
													}
												});
											}
										});
									};
									var clear_mark_btn = document.createElement('div');
									clear_mark_btn.id = 'clear_mark_btn';
									clear_mark_btn.className = 'vjs-control-custom vjs-control-space';
									var clear_mark_text = document.createElement('span');
									clear_mark_text.className = 'fa fa-lg fa-trash-o';
									clear_mark_btn.appendChild(clear_mark_text);
									clear_mark_btn.title = _text('MN02432');
									clear_mark_btn.onclick = fn_clean_mark;

									// Mark In
									var fn_sec_in = function () {
										var markers = videojs_player.markers.getMarkers();
										var current_time = videojs_player.currentTime();
										for (var i = 0; i < markers.length; i++) {
											if (markers[i].mark_type == 'MARK_IN'){
												videojs_player.markers.remove([i]);
											}
										}
										videojs_player.markers.add([{
											time: videojs_player.currentTime(),
											text: _text('MN02315'),
											time_code: secFrameToTimecode(videojs_player.currentTime(), frame_rate),
											class: "mark-sec-in",
											mark_type: 'MARK_IN'
										}]);
										var TabToCheck = Ext.getCmp('media_content_panel_warp').findById('timeline-grid');
										if(TabToCheck){
											Ext.getCmp('media_content_panel_warp').setActiveTab('timeline-grid');
										}
										/* Ext.Ajax.request({
											url : '/store/video_mark/mark_action.php',
											params : {
												mark_type: 'MARK_IN',
												content_id : content_id,
												action: 'add',
												start_frame: Math.floor(current_time*frame_rate),
												end_frame: Math.floor(current_time*frame_rate),
											},
											callback : function(opts, success, response){
												if (success){
													try{
														var r = Ext.decode(response.responseText);
														if(r.success){
														}else{
															Ext.Msg.alert(_text('MN00022'), r.msg);
														}
													}catch(e){
														Ext.Msg.alert(_text('MN00022'), e+'<br />'+response.responseText);
													}
												}else{
													Ext.Msg.alert(_text('MN00022'), response.statusText);
												}
											}
										}); */
									};
									var set_in_btn = document.createElement('div');
									set_in_btn.id = 'set_in_btn';
									set_in_btn.className = ' vjs-control-custom';
									var set_in_text = document.createElement('p');
									set_in_text.innerHTML = '{';
									set_in_btn.appendChild(set_in_text);
									set_in_btn.title = _text('MN02435');
									set_in_btn.onclick = fn_sec_in;

									// Mark Out
									var fn_sec_out = function () {
										var markers = videojs_player.markers.getMarkers();
										var current_time = videojs_player.currentTime();
										for (var i = 0; i < markers.length; i++) {
											if (markers[i].mark_type == 'MARK_OUT'){
												videojs_player.markers.remove([i]);
											}
										}
										videojs_player.markers.add([{
											time: videojs_player.currentTime(),
											text: _text('MN02314'),
											time_code: secFrameToTimecode(videojs_player.currentTime(), frame_rate),
											class: "mark-sec-out",
											mark_type: 'MARK_OUT'
										}]);
										var TabToCheck = Ext.getCmp('media_content_panel_warp').findById('timeline-grid');
										if(TabToCheck){
											Ext.getCmp('media_content_panel_warp').setActiveTab('timeline-grid');
										}
										/* Ext.Ajax.request({
											url : '/store/video_mark/mark_action.php',
											params : {
												mark_type: 'MARK_OUT',
												content_id : content_id,
												action: 'add',
												start_frame: Math.floor(current_time*frame_rate),
												end_frame: Math.floor(current_time*frame_rate),
											},
											callback : function(opts, success, response){
												if (success){
													try{
														var r = Ext.decode(response.responseText);
														if(r.success){
														}else{
															Ext.Msg.alert(_text('MN00022'), r.msg);
														}
													}catch(e){
														Ext.Msg.alert(_text('MN00022'), e+'<br />'+response.responseText);
													}
												}else{
													Ext.Msg.alert(_text('MN00022'), response.statusText);
												}
											}
										}); */

									};
									var set_out_btn = document.createElement('div');
									set_out_btn.id = 'set_out_btn';
									set_out_btn.className = 'vjs-control-custom';
									var set_out_text = document.createElement('p');
									set_out_text.innerHTML = '}';
									set_out_btn.appendChild(set_out_text);
									set_out_btn.title = _text('MN02436');
									set_out_btn.onclick = fn_sec_out;

									// 이동
									var fn_goto_win_show = function () {
										var current_time = videojs_player.currentTime();
										var current_time_text = secFrameToTimecode(current_time, frame_rate);
										var goto_win =  new Ext.Window({
											title: _text('MN02141'),
											cls: 'change_background_panel',
											width: 300,
											modal: true,
											height: 120,
											miniwin: true,
											//resizable: false,
											layout: 'fit',
											buttonAlign: 'center',
											buttons: [
											{
												text : '<span style="position:relative;top:1px;"><i class="fa fa-sign-out" style="font-size:13px;"></i></span>&nbsp;'+_text('MN02141'),
												scale: 'medium',
												handler: function(b, e){
													videojs_player.pause();
													videojs_player.currentTime(Math.ceil(timecodeToSecFrame(Ext.getCmp('go_to_input').getValue(),frame_rate) * 100000)/100000);
													goto_win.close();
													document.getElementById("player3").focus();
												}
											},{
												text : '<span style="position:relative;top:1px;"><i class="fa fa-close" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00004'),
												scale: 'medium',
												handler: function(b, e){
													goto_win.close();
													document.getElementById("player3").focus();
												}
											}],
											items:[{
												xtype: 'form',
												frame: true,
												items: [
												{
													xtype: 'textfield',
													anchor:'100%',
													fieldLabel: _text('MN02173'),
													value: current_time_text,
													invalidText : '00:00:00:00 ~ 23:59:59:99',
													regex: /(0[0-9]|1[0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9]):([0-9][0-9])/,
													plugins: [new Ext.ux.InputTextMask('99:99:99:99')],
													id: 'go_to_input'
												}]
											}]
										}); // end create new mark window
										goto_win.show();
                                    };
                                    
                                    var fn_add_shot_list = function(){														
                                        var save_type = 'add';
                                        Ext.getCmp('tc_toolbar').setAddTC();
                                        var grid = Ext.getCmp('timeline-grid');
                                        var datas = [];
                                        var datalist = grid.getStore().getRange();
                                        Ext.each(datalist,function(r){
                                            datas.push(r.data);
                                        });
                                        Ext.getCmp('player_warp').Save(grid, content_id, 'shot_list', save_type, datas , true ,true );
                                    };

                                    var add_shot_list_btn = document.createElement('div');
									add_shot_list_btn.id = 'add_shot_list_btn';
									add_shot_list_btn.className = 'vjs-control-custom';
									var add_shot_list_text = document.createElement('span');
									add_shot_list_text.className = 'fa fa-lg fa-plus-square-o';
									add_shot_list_btn.appendChild(add_shot_list_text);
									add_shot_list_btn.title = _text('MN02442');
                                    add_shot_list_btn.onclick = fn_add_shot_list;

                                    var make_segment_btn = document.createElement('div');
									make_segment_btn.id = 'make_segment_btn';
									make_segment_btn.className = 'vjs-control-custom';
									var make_segment_text = document.createElement('span');
									make_segment_text.className = 'fa fa-lg fa-external-link';
									make_segment_btn.appendChild(make_segment_text);
									make_segment_btn.title = '이미지 생성';
                                    make_segment_btn.onclick = fn_make_segment;
                                    

                                    //사용안함
									var menu_btn = document.createElement('div');
									menu_btn.id = 'mark_btn';
									menu_btn.className = 'vjs-control-custom';
									var menu_text = document.createElement('span');
									menu_text.className = 'fa fa-lg fa-ellipsis-h';
									menu_btn.appendChild(menu_text);
									menu_btn.title = _text('MN02434');
									menu_btn.onclick = function (event) {
										//fn_action_icon_show_context_menu(<?=$content_id?>,event);
										var menu_f = new Ext.menu.Menu({
											cls: 'hideMenuIconSpace',
											items: [
												<?php
												if( $arr_sys_code['shot_list_yn']['use_yn'] == 'Y' ){
													if($is_group == 'G') {
														$save_type = " save_type = 'group-add'; ";
													}
													echo "
														{
															text: '<i class=\"fa fa-plus-square-o\" style=\"color: black;\"></i>'+_text('MN02442'),
															handler: function(btn, e){	
																var save_type = 'add';
																".$save_type."

																Ext.getCmp('tc_toolbar').setAddTC();
																var grid = Ext.getCmp('timeline-grid');
																var datas = [];
																var datalist = grid.getStore().getRange();
																Ext.each(datalist,function(r){
																	datas.push(r.data);
																});
																Ext.getCmp('player_warp').Save(grid, content_id, 'shot_list', save_type, datas , true ,true );
																//Ext.getCmp('player_warp').Save(grid, content_id, 'shot_list', export_type, datas , false,true );
															}
														},
													";
												}
												?>
											{
												text: '<i class=\"fa fa-external-link\" style=\"color: black;\"></i>'+_text('MN02443'),
												hidden: true,
												handler: fn_make_segment
											},{
												text: '<i class=\"fa fa-external-link-square \" style=\"color: black;\"></i>'+_text('MN02444'),
												handler: fn_make_partical_file,
												<?php if(!checkAllowUdContentGrant($user_id, $ud_content_id, GRANT_PFR) || $arr_sys_code['pfr_use_yn']['use_yn'] != 'Y'){?>
												hidden: true,	
												<?php }?>
											},{
												text: '<i class=\"fa fa-sign-out\" style=\"color: black;\"></i>'+_text('MN02447'),
												handler: fn_goto_win_show,
											}
											
											// Menu custom
											<?php
											if(defined('CUSTOM_ROOT') && class_exists('\ProximaCustom\core\DetailPanelCustom')) {
												\ProximaCustom\core\DetailPanelCustom::renderVideoPlayerMenuItems($user_id, $ud_content_id);
											}
											?>

											],
											listeners: {
												render: function(self){
												}
											}
										});
										var xyEvent = [event.clientX, event.clientY];
										menu_f.showAt(xyEvent);
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
                                    controlBar.appendChild(add_shot_list_btn);
                                    
                                    //이미지 추출 기능 추가
                                    controlBar.appendChild(make_segment_btn);

									<?php
										if($arr_sys_code['shot_list_yn']['use_yn'] == 'Y'){
											if($arr_sys_code['mark_list_use_yn']['use_yn'] == 'Y'){
												echo "
													controlBar.insertBefore(set_out_btn,add_shot_list_btn);
													controlBar.insertBefore(set_in_btn,set_out_btn);
													controlBar.insertBefore(mark_btn,set_in_btn);
													controlBar.insertBefore(clear_mark_btn,mark_btn);
													controlBar.insertBefore(loop_btn,clear_mark_btn);
												";
											} else {
												echo "
													controlBar.insertBefore(set_out_btn,add_shot_list_btn);
													controlBar.insertBefore(set_in_btn,set_out_btn);
													controlBar.insertBefore(loop_btn,set_in_btn);
												";
											}
										} else {
											if($arr_sys_code['mark_list_use_yn']['use_yn'] == 'Y'){
												echo "
													controlBar.insertBefore(mark_btn,add_shot_list_btn);
													controlBar.insertBefore(clear_mark_btn,mark_btn);
													controlBar.insertBefore(loop_btn,clear_mark_btn);
												";
											} else {
												echo "
													controlBar.insertBefore(loop_btn,add_shot_list_btn);
												";
											}
										}
									?>
									//controlBar.insertBefore(fast_forward_btn,loop_btn);
									controlBar.appendChild(frame_three_back_btn);
									controlBar.appendChild(frame_three_next_btn);
									controlBar.insertBefore(frame_next_btn,loop_btn);
									controlBar.insertBefore(play_btn,frame_next_btn);
									controlBar.insertBefore(frame_back_btn,play_btn);
									controlBar.insertBefore(frame_three_back_btn,frame_back_btn);
									controlBar.insertBefore(frame_three_next_btn, loop_btn);
									//controlBar.insertBefore(review_btn,frame_back_btn);

									videojs_player.hotkeys({
										volumeStep: 0.1,
										seekStep: 1/frame_rate,
                                        enableFullscreen: false,
                                        fullscreenKey: function(event, player) {
                                            // override fullscreen to trigger when pressing the F key or Ctrl+Enter
                                            return ((event.ctrlKey && event.which === 13));
                                        },
										customKeys: {
											loopKey: {
												key: function(e) {
													// Toggle something with L Key
													return (e.which === 76);
												},
												handler: fn_loop
											},
											<?php
												if($arr_sys_code['mark_list_use_yn']['use_yn'] == 'Y'){
													echo "
													markKey: {
														key: function(e) {
															// Mark with P Key
															return (e.which === 80);
														},
														handler: fn_mark
													},
													cleanMarkKey: {
														key: function(e) {
															// Clean Mark with D Key
															return (e.which === 68);
														},
														handler: fn_clean_mark
													},
													";
												}
											?>
                                            seekLeft1: {
												key: function(e) {
													// seekLeft1 with B Key
                                                    return (e.which === 66);
												},
												handler: fn_seekLeft1
											},
                                            seekRight1: {
												key: function(e) {
													// seekRight1 with N Key
                                                    return (e.which === 78);
												},
												handler: fn_seekRight1
											},
                                            seekLeft2: {
												key: function(e) {
													// seekLeft2 with R Key
                                                    return (e.which === 82);
												},
												handler: fn_seekLeft2
											}, 
                                            seekRight2: {
                                                key: function(e) {
													// seekRight2 with F Key
                                                    return (e.which === 70);
												},
												handler: fn_seekRight2
											},
											setInKey: {
												key: function(e) {
													// Set In with I Key
													return (e.which === 73);
												},
												handler: fn_sec_in
											},
											setOutKey: {
												key: function(e) {
													// Set On with O Key
													return (e.which === 79);
												},
												handler: fn_sec_out
											},
											<?php if(checkAllowUdContentGrant($user_id, $ud_content_id, GRANT_PFR) && $arr_sys_code['pfr_use_yn']['use_yn'] == 'Y'){ ?>
											makePartical: {
												//key: function(e) {
												//	// Set On with N Key
												//	return (e.which === 78);
												//},
												//handler: fn_make_partical_file
											},
											<?php }?>
											makeSegment: {
												key: function(e) {
													// Set On with S Key
													return (e.which === 83);
												},
												handler: fn_make_segment
											},

											goto: {
												key: function(e) {
													// Go to with G Key
													return (e.which === 71);
												},
												handler: fn_goto_win_show
											},
											<?php
												if( $arr_sys_code['shot_list_yn']['use_yn'] == 'Y' ){
													if($is_group == 'G') {
														$save_type = " save_type = 'group-add'; ";
													}
													echo "
														addToList: {
															key: function(e) {
																// Set On with A Key
																return (e.which === 65);
															},
															handler: function(btn, e){	
																var save_type = 'add';
																".$save_type."

																Ext.getCmp('tc_toolbar').setAddTC();
																var grid = Ext.getCmp('timeline-grid');
																var datas = [];
																var datalist = grid.getStore().getRange();
																Ext.each(datalist,function(r){
																	datas.push(r.data);
																});
																Ext.getCmp('player_warp').Save(grid, content_id, 'shot_list', save_type, datas , true ,true );
																//Ext.getCmp('player_warp').Save(grid, content_id, 'shot_list', export_type, datas , false,true );
															}
														},
													";
												}
											?>
										}
									});

									videojs_player.markers({
										markerStyle: {
											'width':'3px',
											'background-color': 'red'
										},
										markers: [
										]
									});
									videojs_player.markers.removeAll();

									// 마커 표시
									var store_mark = new Ext.data.JsonStore({
										url: '/store/video_mark/mark_action.php',
										root: 'data',
										fields:[
											{ name : 'content_id' },
											{ name : 'mark_id' },
											{ name : 'mark_type' },
											{ name : 'start_frame' },
											{ name : 'end_frame' },
											{ name : 'title' },
											{ name : 'comments' },
											{ name : 'color' },
											{ name : 'status' },
											{ name : 'start_frame_time_code', convert: function(v, record) {
												record.start_frame_time_code = secFrameToTimecode(record.start_frame/frame_rate, frame_rate);
												return record.start_frame_time_code;
											}}
										],
										listeners: {
											load: function(store, records, opts){
												var videojs_player = videojs('player3');
												videojs_player.markers.removeAll();
												var data_array = [];
												Ext.each(records, function(record){
													var data_temp = {
														time: record.get('start_frame')/frame_rate,
														time_code: secFrameToTimecode(record.get('start_frame')/frame_rate,frame_rate),
														text: record.get('title'),
														comments: record.get('comments'),
														mark_id: record.get('mark_id'),
														mark_type: record.get('mark_type')
													}
													data_array.push(data_temp);
												})
												videojs_player.markers.reset(data_array);
											}
										}
									});
									videojs_player.on('loadedmetadata', function(){
										var active_tab_id = Ext.getCmp('media_content_panel_warp').getActiveTab().getId();
										if (active_tab_id == 'timeline-grid'){
											Ext.getCmp('timeline-grid').getStore().reload();
										} else if (active_tab_id == 'marker-grid'){
											Ext.getCmp('marker-grid').getStore().reload();
										}
										this.bigPlayButton.hide();
									});
									videojs_player.on('pause', function() {
										this.bigPlayButton.show();
										videojs_player.on('play', function() {
											this.bigPlayButton.hide();
										});
									});
								});

								var player3 = document.getElementById("player3");
								if (player3.addEventListener) {
									player3.addEventListener('contextmenu', function(e) {
										e.preventDefault();
									}, false);

									player3.addEventListener('loadedmetadata', function() {
										var w, h, ratio, ori_ratio;
										ori_ratio = player3.videoWidth / player3.videoHeight;
										ratio = 320/180;
										//w = videojs.videoWidth;
										if (ratio <= ori_ratio){
											w = 320;
											h = parseInt(w / ori_ratio, 10);
										} else {
											h = 180;
											w = parseInt(h*ori_ratio);
										}
										canvas.width = w;
										canvas.height = h;
									}, false);
								} else {
									player3.attachEvent('oncontextmenu', function() {
										window.event.returnValue = false;
									});
								}
							}
						},
						secFrameToTimecode : function(sec){
							var time = secFrameToTimecode (sec, frame_rate);
							return time;
						},
						insertTimeLine : function(grid, setInSec, setOutSec , is_edit){
							//Ext.getCmp('player_warp').stop();

							var setInFrame, setOutFrame, txt ,dursec, duration, content_id;

							setInFrame = Math.floor(setInSec*frame_rate);
							setOutFrame = Math.floor(setOutSec*frame_rate);
							txt = checkInOut(setInSec, setOutSec);

							if ( !Ext.isEmpty(txt) ) {
								Ext.Msg.alert(_text('MN00023'), txt);
								return;
							}
							dursec =  setOutSec - setInSec;
							duration = setOutFrame - setInFrame;

							<?php
								if($is_group == 'G') {
									echo "var group_list = Ext.getCmp('group_child_list').getSelectionModel().getSelected();
											content_id = group_list.get('content_id'); ";
								}
							?>

							if(grid.getStore().getCount() > 0) {
								var lastrecord = grid.getStore().getAt( grid.getStore().getCount() -1 ) ;
								var start_frame = lastrecord.get('end_frame');
								var starttcsec = lastrecord.get('endsec');
								var endtcsec = starttcsec + dursec;

								if(Ext.isNumber(endtcsec)) {
									var end_frame = Math.floor(endtcsec*frame_rate);
								} else {
									var end_frame = 0;
								}

							} else {
								var starttcsec = 0;
								var start_frame = 0;
								var endtcsec = dursec;
								var end_frame = duration;
							}

							var record = new Ext.data.Record({
								'content_id' : content_id,
								'list_id' : '',
								'start_frame' : start_frame,
								'startsec' : starttcsec,
								'end_frame' : end_frame,
								'endsec' : endtcsec,
								'in_frame' : setInFrame,
								'insec' : setInSec,
								'out_frame' : setOutFrame,
								'outsec' : setOutSec,
								'duration' : duration,
								'durationsec' : dursec,
								'note' : ''
							});
							grid.getStore().add(record);

							if( is_edit ){
								grid.startEditing(grid.getStore().getCount()-1, 6);
							}
						},
						// 샷리스트 저장
						Save : function(grid, content_id, type, action, datas ,isLoad ,isMsg ){
                            var waitMsg = Ext.Msg.wait('처리 중입니다.', '처리중...');
							Ext.Ajax.request({
								url : '/store/roughcut.php',
								params : {
									type: type,
									content_id : content_id,
									action: action,
									datas: Ext.encode(datas)
								},
								callback : function(opts, success, response){
                                    waitMsg.hide();
									if (success){
										try{
											var r = Ext.decode(response.responseText);
											if(r.success){
												//if(isMsg) Ext.Msg.alert('알림', r.msg);

												if(isLoad){
													grid.getStore().load();
													grid.isDirty = false;
												}
											}else{
												Ext.Msg.alert(_text('MN00022'), r.msg);
											}
										}catch(e){
											Ext.Msg.alert(_text('MN00022'), e+'<br />'+response.responseText);
										}
									}else{
										Ext.Msg.alert(_text('MN00022'), response.statusText);
									}
								}
							});
						}
					},{
						region: 'south',
						//width: '50%',
						height:320,
						split: true,
						layout: {
							type: 'vbox',
							align: 'stretch'
						},
						items: [
						// 예전에 사용하던 툴바(플래시 플레이어 사용 시 필요)
						{
							hidden: true,
							xtype: 'toolbar',
							<?php
								if($is_sound || !checkAllowUdContentGrant($user_id, $ud_content_id, GRANT_CREATE)) {
									echo "hidden: true,";
								}
							?>
							id: 'tc_toolbar',
							setAddTC : function(){
								var grid = Ext.getCmp('timeline-grid');
								var setInSec, setOutSec;
								var markers = videojs('player3').markers.getMarkers();
								for (var i = 0; i < markers.length; i++) {
									if (markers[i].mark_type == 'MARK_IN'){
										setInSec = markers[i].time;
									} else if (markers[i].mark_type == 'MARK_OUT'){
										setOutSec = markers[i].time;
									}
								}
								if(grid.id =='timeline-grid' ){
									Ext.getCmp('player_warp').insertTimeLine( grid, setInSec, setOutSec ,false);
								}else if( grid.id =='preview-edit-grid' ){
									Ext.getCmp('player_warp').insertTimeLine( grid, setInSec, setOutSec , true );
								}
								grid.isDirty = true;
							},
							items: [{
								xtype: 'label',
								text: _text('MN00149')
								//!!text: '구간 입력'
							},'-',{
								cls: 'proxima_button_customize',
								width: 30,
								text: '<span style="position:relative;" title="'+_text('MN02315')+'"><p style="font-size:13px;color:white;">{</p></span>',
								//text: '<i class=\"fa fa-thumb-tack fa-fw fa-lg icon-rotate-315\"></i>',
								//tooltip:_text('MN02315'),
								handler: function(b, e){
									//var _s = $f('player2').getTime() + <?=$start_sec?>;
									var _s = player3.currentTime();
									var time_code = secFrameToTimecode(_s, frame_rate);

									Ext.getCmp('secIn').setValue(time_code);
								}
							},{
								xtype: 'textfield',
								id: 'secIn',
								width: 75,
								value: '00:00:00:00',
								invalidText : '00:00:00:00 ~ 23:59:59:99',
								regex: /(0[0-9]|1[0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9]):([0-9][0-9])/,
								plugins: [new Ext.ux.InputTextMask('99:99:99:99')]
							},'-', {
								xtype: 'textfield',
								id: 'secOut',
								width: 75,
								value: '00:00:00:00',
								invalidText : '00:00:00:00 ~ 23:59:59:99',
								regex: /(0[0-9]|1[0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9]):([0-9][0-9])/,
								plugins: [new Ext.ux.InputTextMask('99:99:99:99')]
							},{
								cls: 'proxima_button_customize',
								width: 30,
								text: '<span style="position:relative;" title="'+_text('MN02314')+'"><p  style="font-size:13px;color:white;">}</p></span>',
								//text: '<i class=\"fa fa-thumb-tack fa-fw fa-lg icon-rotate-45\"></i>',
								//tooltip:_text('MN02314'),
								handler: function(b, e){
									//var _s = $f('player2').getTime() + <?=$start_sec?>;
									var _s = player3.currentTime();
									var time_code = secFrameToTimecode(_s, frame_rate);

									Ext.getCmp('secOut').setValue(time_code);
								}
							},'-', {
								cls: 'proxima_button_customize',
								width: 30,
								text: '<span style="position:relative;" title="'+_text('MN02141')+'"><i class="fa fa-sign-out fa-fw fa-lg" style="font-size:13px;color:white;"></i></span>',
								//text: '<i class=\"fa fa-sign-out fa-fw fa-lg\"></i>',
								id: 'btnGoto',
								//tooltip:_text('MN02141'),
								handler: function(){
									var _seek = timecodeToSecFrame(Ext.getCmp('goto').getValue(), frame_rate);
									if (Ext.isEmpty(_seek)) {
										Ext.getCmp('goto').setValue(0);
										_seek = 0;
									}
									//$f('player2').seek(_seek);
									player3.currentTime(_seek);

								}
							}, {
								id: 'goto',
								xtype: 'textfield',
								enableKeyEvents: true,
								value: '00:00:00:00',
								invalidText : '00:00:00:00 ~ 23:59:59:99',
								regex: /(0[0-9]|1[0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9]):([0-9][0-9])/,
								plugins: [new Ext.ux.InputTextMask('99:99:99:99')],
								width: 75,
								listeners: {
									specialKey: function(self, e){
										if (e.getKey() == e.ENTER) {
											Ext.getCmp('btnGoto').handler();
										}
									}
								}
							}, '-', {
								hidden: true,
								icon: '/led-icons/sort_date.png',
								text: _text('MN00391'),
								handler: function(b, e){
									$f('player2').pause();

									var setInSec, setOutSec;
									var markers = videojs('player3').markers.getMarkers();
									for (var i = 0; i < markers.length; i++) {
										if (markers[i].mark_type == 'MARK_IN'){
											setInSec = markers[i].time;
										} else if (markers[i].mark_type == 'MARK_OUT'){
											setOutSec = markers[i].time;
										}
									}

									var txt = checkInOut(setInSec, setOutSec);
									if ( ! Ext.isEmpty(txt)) {
										Ext.Msg.alert(_text('MN00023'), txt);
										return;
									}

									Ext.Msg.show({
										title: '확인',
										msg: '구간 추출 클립을 생성 하시겠습니까?',
										icon: Ext.Msg.QUESTION,
										buttons: Ext.Msg.OKCANCEL,
										fn: function(btnId) {
											if (btnId == 'ok') {
												var wait_msg = Ext.Msg.wait('등록중입니다.', '요청');
												Ext.Ajax.request({
													url: '/store/create_pfr.php',
													params: {
														content_id: <?=$content_id?>,
														//vr_meta: Ext.encode(values),
														start: setInSec,
														end: setOutSec
													},
													callback: function(opts, success, response){
														wait_msg.hide();
														if (success) {
															try {
																var r = Ext.decode(response.responseText);
																if (r.success) {

																	Ext.Msg.show({
																		title: '확인',
																		msg: '구간 추출 클립이 등록되었습니다.<br />창을 닫으시겠습니까?',
																		icon: Ext.Msg.QUESTION,
																		buttons: Ext.Msg.OKCANCEL,
																		fn: function(btnId){
																			if (btnId == 'ok') {
																				Ext.getCmp('winDetail').close();
																			}
																		}
																	});
																} else {
																	Ext.Msg.alert('확인', r.msg);
																}
															} catch(e) {
																Ext.Msg.alert('오류', response.responseText);
															}
														} else {
															Ext.Msg.alert('서버 통신 오류', response.statusText);
														}
													}
												});
											}
										}
									});
								 }
							}, {
								cls: 'proxima_button_customize',
								width: 30,
								text: '<span style="position:relative;top:1px;" title="'+_text('MN02140')+'"><i class="fa fa-file-video-o" style="font-size:13px;color:white;"></i></span>',
								//text: '<span style="position:relative;top:1px;"><i class="fa fa-external-link" style="font-size:13px;"></i></span>&nbsp;'+_text('MN02140'),//'신규 콘텐츠 생성'
								//icon: '/led-icons/doc_convert.png',
								handler: fn_make_partical_file
							}
							<?php
							if( $arr_sys_code['shot_list_yn']['use_yn'] == 'Y' ){
								if($is_group == 'G') {
									$save_type = " save_type = 'group-add'; ";
								}
								echo "			
										,'-',{
											cls: 'proxima_button_customize',
											width: 30,
											text: '<span style=\"position:relative;top:1px;\" title=\"'+_text('MN02289')+'\"><i class=\"fa fa-external-link fa-rotate-180\" style=\"font-size:13px;color:white;\"></i></span>',
											//text: '<span style=\"position:relative;top:1px;\"><i class=\"fa fa-external-link fa-rotate-180\" style=\"font-size:13px;\"></i></span>&nbsp;'+_text('MN02289'),//EDL 생성
											handler: function(b, e){
												var save_type = 'add';
												".$save_type."

												Ext.getCmp('tc_toolbar').setAddTC();
												var grid = Ext.getCmp('timeline-grid');
												var datas = [];
												var datalist = grid.getStore().getRange();
												Ext.each(datalist,function(r){
													datas.push(r.data);
												});
												Ext.getCmp('player_warp').Save(grid, content_id, 'shot_list', save_type, datas , true ,true );
												//Ext.getCmp('player_warp').Save(grid, content_id, 'shot_list', export_type, datas , false,true );
											}
										}
									";
							}
							?>
							
							,'-',{
								cls: 'proxima_button_customize',
								width: 30,
								text: '<span style="position:relative;top:1px;" title="'+_text('MN02330')+'"><i class="fa fa-external-link" style="font-size:13px;color:white;"></i></span>',
								//text: '<span style="position:relative;top:1px;"><i class="fa fa-external-link" style="font-size:13px;"></i></span>&nbsp;'+_text('MN02330'),
								handler: fn_make_segment
							},{
								hidden: true,
								icon: '/led-icons/add.png',
								//!!text: '입력',
								text: _text('MN00036'),
								handler: function(b, e){

									$f('player2').pause();

									var setInSec, setOutSec;
									var markers = videojs('player3').markers.getMarkers();
									for (var i = 0; i < markers.length; i++) {
										if (markers[i].mark_type == 'MARK_IN'){
											setInSec = markers[i].time;
										} else if (markers[i].mark_type == 'MARK_OUT'){
											setOutSec = markers[i].time;
										}
									}

									var txt = checkInOut(setInSec, setOutSec);
									if ( !Ext.isEmpty(txt) )
									{
										Ext.Msg.alert(_text('MN00023'), txt);
										return;
									}

									var duration = secFrameToTimecode((setOutSec - setInSec), frame_rate);

									b.ownerCt.ownerCt.get(1).get(0).get(0).getForm().items.get(1).setValue(setInTC);
									b.ownerCt.ownerCt.get(1).get(0).get(0).getForm().items.get(2).setValue(setOutTC);
									b.ownerCt.ownerCt.get(1).get(0).get(0).getForm().items.get(3).setValue(duration);

								}
							}, {
								hidden: true,
								icon: '/led-icons/bin_closed.png',
								//!!text: '삭제',
								text: _text('MN00034'),
								handler: function(b, e){
								}
							}]
						}, {
							xtype: 'toolbar',
                            hidden: true,
                            items: [ 
                            _text('MN01050'),//'탐색'
								'-', {
								//icon: '/led-icons/control_rewind.png',
								//text: '10초 뒤로',
								//text: _text('MN01052'),
								//cls: 'proxima_button_customize',
								width: 30,
								//text: '<span style="position:relative;top:1px;" title="'+_text('MN01052')+'"><i class="fa fa-fast-backward" style="font-size:13px;color:white;"></i></span>',
                                text: '<i class="fa fa-fast-backward" style="font-size:13px;color:black;"></i>&nbsp'+'10초 뒤로( R )',
								handler: function(){
									//SeekFlowPlayer(-10);
									SeekHtmlPlayer(-10);
								}
							},'-',{
								//icon: '/led-icons/control_start.png',
								//text: '1초 뒤로',
								//text: _text('MN01051'),
								//cls: 'proxima_button_customize',
								width: 30,
								//text: '<span style="position:relative;top:1px;" title="'+_text('MN01051')+'"><i class="fa fa-step-backward" style="font-size:13px;color:white;"></i></span>',
                                text: '<i class="fa fa-step-backward" style="font-size:13px;color:black;"></i>&nbsp'+'1초 뒤로( B )',
								handler: function(){
									//SeekFlowPlayer(-1);
									SeekHtmlPlayer(-1);
								}
							},'-',{
								//iconAlign: 'right',
								//icon: '/led-icons/control_end.png',
								//text: '1초 앞으로',
								//text: _text('MN01053'),
								//cls: 'proxima_button_customize',
								width: 30,
								//text: '<span style="position:relative;top:1px;" title="'+_text('MN01053')+'"><i class="fa fa-step-forward" style="font-size:13px;color:white;"></i></span>',
                                text: '<i class="fa fa-step-forward" style="font-size:13px;color:black;"></i>&nbsp'+'1초 앞으로( N )',
								handler: function(){
									//SeekFlowPlayer(1);
									SeekHtmlPlayer(1);
								}
							},'-',{
								//iconAlign: 'right',
								//icon: '/led-icons/control_fastforward.png',
								//text: '10초 앞으로',
								//text: _text('MN01054'),
								//cls: 'proxima_button_customize',
								width: 30,
								//text: '<span style="position:relative;top:1px;" title="'+_text('MN01054')+'"><i class="fa fa-fast-forward" style="font-size:13px;color:white;"></i></span>',
                                text: '<i class="fa fa-fast-forward" style="font-size:13px;color:black;"></i>&nbsp'+'10초 앞으로( F )',
								handler: function(){
									//SeekFlowPlayer(10);
									SeekHtmlPlayer(10);
								}
							}
                            ]
						},
							<?php
								echo "media_content_panel";							
							?>
						]
					}]
				},{
					region: 'east',
					xtype: 'panel',
					layout: 'border',
					id: 'left_side_panel',
					bodyStyle: 'border: none;',
					width: 520,
					split: true,
					items: [
					<?php if($useTagConfig): ?>
					// 태그 목록
					{						
						region: 'south',
						xtype: 'form',						
						height: 35,
					    id: 'tag_list_in_content',
						hidden: true,
					    bodyStyle: 'background: #eaeaea;padding-top:3px;',
					    items: [],
					    listeners :{
					    	render: function(){
					    		var tag_list_in_content_form = Ext.getCmp('tag_list_in_content');

						      	Ext.Ajax.request({
						                url: '/store/tag/tag_action.php',
						                params: {
						                  action: 'get_tag_list_of_content',
						                  content_id: <?=$content_id?>
						                },
						                callback: function(opt, success, response){
						                    if(success) {
						                        var result = Ext.decode(response.responseText);
						                        var result_data = result.data;
						                        
						                        tag_list_in_content_form.add({
						                           	xtype : 'button',
						                           	cls: 'proxima_button_customize',
													width: 30,
						                           text: '<span style=\"position:relative;top:1px;\" title=\"'+_text('MN02240')+'\"><i class=\"fa fa-eraser\" style=\"font-size:13px;color:white;\"></i></span>',
						                           style: {
						                              float: 'left',
						                              marginRight: '2px'
						                           },
						                           listeners: {
						                              render: function(c){
						                                 c.getEl().on('click', function(){
						                                    var content_id_array_2 = [];
	  														content_id_array_2.push({
	  																content_id: <?=$content_id ?>
	  															});

	  														Ext.Ajax.request({
	  															url: '/store/tag/tag_action.php',
	  															params: {
	  																content_id: Ext.encode(content_id_array_2),
	  																action: "clear_tag_for_content"
	  															},
	  															callback: function(opts, success, response) {
	  																Ext.getCmp('tag_list_in_content').reset_list_of_tag_form();
	  															}
	  														});
						                                 }, c);
						                              }
						                           }

						                     	});

						                     	tag_list_in_content_form.add({
						                           	xtype : 'button',
						                           	cls: 'proxima_button_customize',
													width: 30,
						                           	text: '<span style=\"position:relative;top:1px;\" title=\"'+_text('MN02239')+'\"><i class=\"fa fa-cog\" style=\"font-size:13px;color:white;\"></i></span>',
						                           style: {
						                              float: 'left',
						                              marginRight: '5px'
						                           },
						                           listeners: {
						                              render: function(c){
						                                 c.getEl().on('click', function(){
						                                    tag_management_windown('detail_content');
						                                 }, c);
						                              }
						                           }

						                     	});

					                        	for(i = 0; i < result_data.length; i++){
					                           		if(i<10){
					                           			if(result_data[i].is_checked == '1'){
					                           				tag_list_in_content_form.add({
						                                 		xtype: 'label',
							                     				html: '<div title=\"'+result_data[i].tag_category_title+'\" style=\"position: relative;float:left;height:1px;width:18px;padding-right:5px;\"><i class=\"fa fa-circle\" style=\"position: absolute;font-size:15px;margin-top:5px;color:'+result_data[i].tag_category_color+';border: 2px solid '+result_data[i].tag_category_color+';\"></i></div>'
						                              		});

					                           			}else{
					                           				tag_list_in_content_form.add({
						                                 		xtype: 'label',
							                     				html: '<div tag_id_data =\"'+result_data[i].tag_category_id+'\" style=\"position: relative;float:left;height:1px;width:18px\"><i class=\"fa fa-circle\" style=\"position: absolute;font-size:15px;margin-right: 5px;margin-top:7px;color:'+result_data[i].tag_category_color+';padding-right: 4px;\" title=\"'+result_data[i].tag_category_title+'\"></i><i class=\"fa fa-check\" style=\"position: absolute;font-size:16px;margin-top:1px; display:none;\"></i></div>',
							                     				listeners: {
							                        				render: function(c){
							                           					var tag_category_id = c.getEl().dom.children[0].getAttribute('tag_id_data');
							                           					c.getEl().on('click', function(){
							                           						var content_id_array_2 = [];
							                           						content_id_array_2.push({
				  																	content_id: <?=$content_id ?>
			  																});
			  																change_tag_content('change_tag_content', content_id_array_2, tag_category_id,'no_reload_data');
							                           					}, c);
							                        				}
							                     				}
						                              		});
					                           			}

						                           }else{
														if(result_data[i].is_checked == '1'){
					                           				tag_list_in_content_form.add({
						                                 		xtype: 'label',
							                     				html: '<div title=\"'+result_data[i].tag_category_title+'\" style=\"position: relative;float:left;height:1px;width:18px;padding-right:5px;\"><i class=\"fa fa-circle\" style=\"position: absolute;font-size:15px;margin-top:2px;color:'+result_data[i].tag_category_color+';border: 2px solid '+result_data[i].tag_category_color+';\"></i></div>'
						                              		});

					                           			}
						                           }
						                        }
						                        if(result_data.length > 10){
						                           tag_list_in_content_form.add({
						                              	xtype : 'button',
						                              	cls: 'proxima_button_customize',
														width: 30,
						                              	text: '<span style=\"position:relative;top:1px;\" title=\"'+_text('MN02288')+'\"><i class=\"fa fa-ellipsis-h\" style=\"font-size:13px;color:white;\"></i></span>',
						                              	style: {
					                                		float: 'left',
						                                	marginRight: '5px'
						                              	},
						                              	listeners: {
						                                 	render: function(c){
							                                    c.getEl().on('click', function(event){
							                                       tag_list_windown(<?=$content_id ?>);
							                                    }, c);
							                                 }
							                              }
						                           });
						                        }

						                        tag_list_in_content_form.doLayout();
						                    }
						                }
						            });
					    	}
					    },
					    reset_list_of_tag_form: function(){
				    		Ext.getCmp('tag_list_in_content').removeAll();
				    		var tag_list_in_content_form = Ext.getCmp('tag_list_in_content');
					      	Ext.Ajax.request({
					                url: '/store/tag/tag_action.php',
					                params: {
					                  action: 'get_tag_list_of_content',
					                  content_id: <?=$content_id?>
					                },
					                callback: function(opt, success, response){
					                    if(success) {
					                        var result = Ext.decode(response.responseText);
					                        var result_data = result.data;
					                        tag_list_in_content_form.add({
					                           	xtype : 'button',
					                           	cls: 'proxima_button_customize',
												width: 30,
					                           text: '<span style=\"position:relative;top:1px;\" title=\"'+_text('MN02240')+'\"><i class=\"fa fa-eraser\" style=\"font-size:13px;color:white;\"></i></span>',
					                           style: {
					                              float: 'left',
					                              marginRight: '2px'
					                           },
					                           listeners: {
					                              render: function(c){
					                                 c.getEl().on('click', function(){
					                                    var content_id_array_2 = [];
  														content_id_array_2.push({
  																content_id: <?=$content_id ?>
  															});

  														Ext.Ajax.request({
  															url: '/store/tag/tag_action.php',
  															params: {
  																content_id: Ext.encode(content_id_array_2),
  																action: "clear_tag_for_content"
  															},
  															callback: function(opts, success, response) {
  																Ext.getCmp('tag_list_in_content').reset_list_of_tag_form();
  															}
  														});
					                                 }, c);
					                              }
					                           }

					                     	});

					                     	tag_list_in_content_form.add({
					                           	xtype : 'button',
					                           	cls: 'proxima_button_customize',
												width: 30,
					                           text: '<span style=\"position:relative;top:1px;\" title=\"'+_text('MN02239')+'\"><i class=\"fa fa-cog\" style=\"font-size:13px;color:white;\"></i></span>',
					                           style: {
					                              float: 'left',
					                              marginRight: '5px'
					                           },
					                           listeners: {
					                              render: function(c){
					                                 c.getEl().on('click', function(){
					                                    tag_management_windown('detail_content');
					                                 }, c);
					                              }
					                           }

					                     	});
				                        	for(i = 0; i < result_data.length; i++){
				                           		if(i<10){
				                           			if(result_data[i].is_checked == '1'){
				                           				tag_list_in_content_form.add({
					                                 		xtype: 'label',
						                     				html: '<div title=\"'+result_data[i].tag_category_title+'\" style=\"position: relative;float:left;height:1px;width:18px;padding-right:5px;\"><i class=\"fa fa-circle\" style=\"position: absolute;font-size:15px;margin-top:5px;color:'+result_data[i].tag_category_color+';border: 2px solid '+result_data[i].tag_category_color+';\"></i></div>'
					                              		});

				                           			}else{
				                           				tag_list_in_content_form.add({
					                                 		xtype: 'label',
						                     				html: '<div tag_id_data =\"'+result_data[i].tag_category_id+'\" style=\"position: relative;float:left;height:1px;width:18px\"><i class=\"fa fa-circle\" style=\"position: absolute;font-size:15px;margin-right: 5px;margin-top:7px;color:'+result_data[i].tag_category_color+';padding-right: 4px;\" title=\"'+result_data[i].tag_category_title+'\"></i><i class=\"fa fa-check\" style=\"position: absolute;font-size:16px;margin-top:1px; display:none;\"></i></div>',
						                     				listeners: {
						                        				render: function(c){
						                           					var tag_category_id = c.getEl().dom.children[0].getAttribute('tag_id_data');
						                           					c.getEl().on('click', function(){
						                           						var content_id_array_2 = [];
						                           						content_id_array_2.push({
			  																	content_id: <?=$content_id ?>
		  																});
		  																change_tag_content('change_tag_content', content_id_array_2, tag_category_id,'no_reload_data');
						                           					}, c);
						                        				}
						                     				}
					                              		});
				                           			}

					                           }else{
													if(result_data[i].is_checked == '1'){
				                           				tag_list_in_content_form.add({
					                                 		xtype: 'label',
						                     				html: '<div title=\"'+result_data[i].tag_category_title+'\" style=\"position: relative;float:left;height:1px;width:18px;padding-right:5px;\"><i class=\"fa fa-circle\" style=\"position: absolute;font-size:15px;margin-top:2px;color:'+result_data[i].tag_category_color+';border: 2px solid '+result_data[i].tag_category_color+';\"></i></div>'
					                              		});

				                           			}
					                           }
					                        }
					                        if(result_data.length > 10){
					                           tag_list_in_content_form.add({
					                              	xtype : 'button',
					                              	cls: 'proxima_button_customize',
													width: 30,
					                              	text: '<span style=\"position:relative;top:1px;\" title=\"'+_text('MN02288')+'\"><i class=\"fa fa-ellipsis-h\" style=\"font-size:13px;color:white;\"></i></span>',
					                              style: {
					                                 float: 'left',
					                                 marginRight: '5px'
					                              },
					                              listeners: {
					                                 render: function(c){
					                                    c.getEl().on('click', function(event){
					                                       tag_list_windown(<?=$content_id ?>);
					                                    }, c);
					                                 }
					                              }
					                           });
					                        }

					                        tag_list_in_content_form.doLayout();
					                    }
					                }
					            });
					    }
					},
					<?php endif; ?>
					// 메타데이터
					{
						region: 'center',
						xtype: 'tabpanel',
						id: 'detail_panel',
						cls: 'proxima_tabpanel_customize proxima_media_tabpanel',
						//!!title: '메타데이터',
						title: _text('MN00164'),
						enableTabScroll:true,
						border: false,
						split: true,
						width: 520,
						checkModified: function(self, n, c){
							var _isDirty = false;

							if (c && c.xtype == 'form' && c.getForm().isDirty()) {
								var items = c.getForm().items;
								items.each(function(i){
									if (i.xtype != 'treecombo' && i.isDirty()) {
										_isDirty = true;
										return false;
									}
								});
							}

							if (_isDirty) {
								Ext.Msg.show({
									animEl: self.el,
									//!!title: '확인',
									//!!msg: '수정된 내용이 있습니다. 적용 하시겠습니까?',
									title: _text('MN00003'),
									msg: _text('MSG00173'),
									icons: Ext.Msg.QUESTION,
									buttons: Ext.Msg.OKCANCEL,
									fn: function(btnId){
										if (btnId == 'ok')
										{
											c.getFooterToolbar().items.each(function(b){
												if (b.text == _text('MN00043'))
												{
													b.fireEvent('click', b, true);
												}
											});
										}
										else
										{
											c.getForm().reset();
											if ( n )
											{
												self.setActiveTab(n);
											}
										}
									}
								});

								return false;
							}

							return true;
						},
						<?php
						$activeTab = 0;
						if (in_array(REVIEW_GROUP, $_SESSION['user']['groups']))
						{
							$activeTab = 2;
						}					

						?>

						listeners: {
							render: function(self){
								var myMask = new Ext.LoadMask(Ext.getBody(), {msg:_text('MSG00143')});
								myMask.show();
								Ext.Ajax.request({
									url: '/store/get_detail_metadata.php',
									params: {
										mode: '<?=$mode?>',
										content_id: <?=$content_id?>,
										is_watch_meta: '<?=$is_watch_meta?>'
										<?php
											if ( ! is_null($request_id)) {
												echo ',request_id: '.$request_id;
											}
										?>

									},
									callback: function(opts, success, response){
										myMask.hide();
										if (success) {
											try {
												var r = Ext.decode(response.responseText);

												// 사용자화
												Ext.each(r[0].items, function (i, idx) {
													// 날짜를 선택하면 자동으로 요일표시
													if (i.fieldLabel == '방송일자') {
														Ext.apply(i, {
															listeners: {
																select: function (self, dt) {
																	var weekday = self.ownerCt.find('fieldLabel', '방송요일');
																	if (weekday.length == 1) {
																		weekday[0].setValue(dt.format('l')+'요일');
																	}
																}
															}
														});
													}
												}); // 사용자화 끝

												self.add(r);
												self.doLayout();
												self.activate(0);
											}
											catch(e) {
												Ext.Msg.alert(e['name'], e['message']);
											}
										}
										else {
											Ext.Msg.alert(_text('MN00022'), opts.url+'<br />'+response.statusText+'('+response.status+')');
										}
									}
								});
							},
							beforetabchange: function(self, n, c){
								var tc_toolbar = Ext.getCmp('tc_toolbar');
								if (n.title == 'TC정보') {
									// tc list 추가
									tc_toolbar.get(13).setVisible(true);
								} else {
									<?php
									//checkAllowUdContentGrant($user_id, $ud_content_id, $grant , $category_id = null)
									if (checkAllowUdContentGrant($user_id, $ud_content_id,GRANT_CREATE)) {
										// echo 'tc_toolbar.get(11).setVisible(true);';
									}

									if (checkAllowUdContentGrant($user_id, $ud_content_id, GRANT_CREATE)) {
										echo 'tc_toolbar.get(12).setVisible(true);';
									}

									if (checkAllowUdContentGrant($user_id, $ud_content_id, GRANT_EDIT)) {
										echo 'tc_toolbar.get(14).setVisible(true);';
									}
									?>

									//tc_toolbar.get(13).setVisible(false);
								}

								/*
								if( !Ext.isEmpty( self.getActiveTab() ) )//탭 변경시 tc_목록입력창 하이드
								{
									if( self.getActiveTab().getId() != 'user_metadata_4002610' )
									{
										Ext.getCmp('tc_panel').setVisible(false);
									}
								}


								return self.checkModified(self, n, c);
								*/
							}
						}
					}
					]
				}]
			};

			Ariel.DetailWindow.superclass.initComponent.call(this);
		}
	});


	new Ariel.DetailWindow().show();

	var player3 = videojs(document.getElementById('player3'), {}, function(){
	});

	if('<?=$show_thumb_grid?>' == 'true') {
		player3.thumbnails({<?=$thumbnails?>});
	}

	/*
	$f("player2", {src: "/flash/flowplayer/flowplayer-3.2.16-dev.swf", wmode: 'opaque'}, {
		clip: {
			autoPlay: false,
			autoBuffering: <?=$switch?>,
			scaling: 'fit',
			bufferLength: 0,
			bufferTime: 0,
			provider: 'hddn'
		},
		plugins: {
			hddn: {
				url: '/flash/flowplayer/flowplayer.rtmp-3.2.12-dev.swf',
				netConnectionUrl: '<?=$streamer_addr?>'
			}
		},
		onKeypress: function(clip){
		}
	});
	*/
})()