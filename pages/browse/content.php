<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
session_start();

require_once($_SERVER['DOCUMENT_ROOT'].'/out.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lang.php');
$user_id = $_SESSION['user']['user_id'];

fn_checkAuthPermission($_SESSION);
////////////////////////////////////////////////////
//
//  2010.10.03 수정
//  한화면 보기시 meta_table_id값이 정의되지 않아 기능수행 안됨.
//  해결: store의 필드안에 meta_table_id 추가. line: 42.
//  작성자 : 박정근, 김성민
////////////////////////////////////////////////////

$ud_content_id = $_POST['ud_content_id'];
$bs_content_id = $_POST['bs_content_id'];

$user_option = $db->queryRow("
    SELECT  top_menu_mode, slide_thumbnail_size, first_page, slide_summary_size, show_content_subcat_yn, content_column_order
    FROM        bc_member_option
    WHERE   member_id = (
        SELECT  member_id
        FROM        bc_member
        WHERE   user_id =  '".$user_id."'
    )
");

/** 2019.12.07 hkkim KTV는 이 기능을 무조건 사용하지 않으므로 주석처리하고 기본값을 넣음 */
$notice_new_content_count_yn = 'N';
// $notice_new_content_count_yn = $db->queryOne("
// 		SELECT	COALESCE((
// 			SELECT	USE_YN
// 			FROM	BC_SYS_CODE A
// 			WHERE	A.TYPE_ID = 1
// 			AND		A.CODE='NOTICE_NEW_CONTENT_COUNT'
// 		), 'N') AS USE_YN
// 		FROM	BC_MEMBER
// 		WHERE	USER_ID = '".$_SESSION ['user'] ['user_id']."'
// 	");

/** 2019.12.07 hkkim 변수는 선언되어 있으나 본문에서 사용하지 않으므로 주석처리 */
// $check_loudness = $db->queryOne("
// 	SELECT	COALESCE((
// 				SELECT	USE_YN
// 				FROM	BC_SYS_CODE A
// 				WHERE	A.TYPE_ID = 1
// 				AND		A.CODE = 'INTERWORK_LOUDNESS'), 'N') AS USE_YN
// 	FROM	(
// 			SELECT	USER_ID
// 			FROM	BC_MEMBER
// 			WHERE	USER_ID = '".$_SESSION['user']['user_id']."') DUAL
// ");
$action_icon_slide_yn = $db->queryOne("
	SELECT	ACTION_ICON_SLIDE_YN
	FROM		BC_MEMBER_OPTION
	WHERE	MEMBER_ID = (
		SELECT	MEMBER_ID
		FROM		BC_MEMBER
		WHERE	USER_ID =  '".$_SESSION['user']['user_id']."'
	)
");

$show_workflow_text = '';
if($user_id && checkAllowUdContentGrant($user_id, $ud_content_id, GRANT_SHOW_WORKFLOW)) {
    $show_workflow_text = "'<i class=\"fa fa-tasks\" title=\""._text('MN00241')."\" onclick=\"fn_action_icon_show_workflow({content_id})\" style=\"font-size: 15px;\"></i>',";
}

?>
{
	xtype: 'contentgrid',
	border: false,
	id: 'ud_content_id'+<?=$ud_content_id?>,
	ud_content_id: <?=$ud_content_id?>,
	bs_content_id: <?=$bs_content_id?>,
	cls: 'proxima_contentgrid_media proxima_grid_header',
	//loadMask: true,

	/*
	2011-02-07 박정근
	탭 별로 검색 가능하도록 변경
	*/
	reload: function(args) {
		Ext.getCmp('main_card_search').getEl().mask(_text('MSG02125'),"x-mask-loading");
		var content_tab = Ext.getCmp('tab_warp');
		var active_tab = content_tab.getActiveTab();
		var content_grid = active_tab.get(0);
		if (Ext.isEmpty(args)) {
			var args = {};
		}        

		// 툴바의 조건값 추가 2012-11-22 이성용
		if ( !Ext.isEmpty(content_grid) && ! Ext.isEmpty(content_grid.getTopToolbar())){
			if ( ! Ext.isEmpty( content_grid.getTopToolbar().get(0))) {
				args.archive_status = content_grid.getTopToolbar().get(0).getValue();
			}

			if ( ! Ext.isEmpty( content_grid.getTopToolbar().get(2))) {
				args.media_status = content_grid.getTopToolbar().get(2).getValue();
			}

			if ( ! Ext.isEmpty( content_grid.getTopToolbar().get(4))) {
				args.category_type = content_grid.getTopToolbar().get(4).getValue();
			}
		}

		if ( !Ext.isEmpty(content_grid) && ! Ext.isEmpty(content_grid.getBottomToolbar())
				&& ! Ext.isEmpty(content_grid.getBottomToolbar().pageSize)) {

			pagesize = content_grid.getBottomToolbar().pageSize;
			args.limit = content_grid.getBottomToolbar().pageSize;
		} else {
			pagesize = 35;
		}

		var value = Ext.get('search_input').getValue();

		if (Ext.isEmpty(value) || (args.filter_type == 'category')) {
			content_tab.items.each(function(item){
			   //item.setTitle(item.initialConfig.title+'('+')');
			});
		}

		this.store.reload({
			limit: pagesize,
			params: args
		});

		this.loadDDfile(this);

	},
		//2015-12-30 현재 상태 표시
	init_tools : function(target)
	{
		var self = this;
		var bbar = self.getBottomToolbar();
		if(Ext.isPhotoshop){
				//var photoshop_upload_btn_ico = this.getTool('ps_plugin_upload_icon');
				//photoshop_upload_btn_ico.dom.style.display = 'block';
				bbar.getComponent('ps_plugin_upload_icon').show();
		}
		
		
		var temp;
		var arr_view_tool = [];
		temp = bbar.getComponent('thumb_view_tool');
		arr_view_tool.push(temp);

		temp = bbar.getComponent('summary_view_tool');
		arr_view_tool.push(temp);

		temp = bbar.getComponent('list_view_tool');
		arr_view_tool.push(temp);
		
		var first_mode = self.mode;
		if(first_mode == 'thumb'){
			first_mode = 'thumb_view_tool';
		}else if(first_mode == 'mix'){
			first_mode = 'summary_view_tool';
		}else if(first_mode == 'list'){
			first_mode = 'list_view_tool';
		}

		arr_view_tool.forEach(function(tool) {
			if(!Ext.isEmpty(tool)){
				tool.removeClass('proxima_btn_control_tool_active');
			}
			if(tool.id == first_mode){
				tool.addClass('proxima_btn_control_tool_active');
			}
		});
        
		switch(self.mode){
			case 'thumb':
                bbar.getComponent('column_sort_order').hide();
            break;
			case 'mix':
                bbar.getComponent('column_sort_order').hide();
            break;
			case 'list':
				//bbar.getComponent('column_sort_order').hide();
				bbar.getComponent('column_sort_order').show();
			break;
		}
	},

	template: {
		//리스트+섬네일보기 Summary view
		list: new Ext.XTemplate(
			'<div class="{[this.getContentStatusCss(values.status)]}">',
				'<div class="x-grid3-cell-inner" unselectable="on" style></div>',
				'{icons_grid}',
				'<table class="x-grid3-row-table">',
					'<tbody>',

						 '<tr>',
							'<td class="x-grid3-col x-grid3-cell ux-explorerview-icon" style="/*vertical-align:middle*/" align="center">',
							'<tpl if="bs_content_id == <?=MOVIE?>  || bs_content_id == <?=SEQUENCE?>">',
								'<tpl if="is_group ==\'G\'">',
									'<tpl if="Ext.isEmpty(thumb_group_path)">',
										'<img id ="thumb-list-{content_id}"  onload="resizeImg(this, {w:80, h:60})" ext:qtip="{qtip}"  src="/img/incoming_proxy.png" alt="No IMAGE">',
									'</tpl>',
									'<tpl if="thumb == \'incoming.jpg\'"><img id ="thumb-list-{content_id}"  onload="resizeImg(this, {w:80, h:60})" ext:qtip="{qtip}"  src="/img/incoming_proxy.png" alt="No IMAGE"></tpl>',
									'<tpl if="!Ext.isEmpty(thumb_group_path) && thumb_group_path != \'incoming.jpg\'">',
										'<img id ="thumb-list-{content_id}" onload="resizeImg(this, {w:80, h:60})" onerror="fallbackImg(this)" ext:qtip="{qtip}" src="{thumb_web_root}/{thumb_group_path}"',
										'<tpl if="this.gtWidth(thumb_group_path, thumb_web_root) === 1"> width="80" </tpl>',
										'<tpl if="this.gtWidth(thumb_group_path, thumb_web_root) === -1"> height="60" </tpl>',
										'>',
									'</tpl>',
								'</tpl>',
								'<tpl if="is_group ==\'I\'">',
									'<tpl if="Ext.isEmpty(thumb)">',
										'<img id ="thumb-list-{content_id}" onload="resizeImg(this, {w:80, h:60})" ext:qtip="{qtip}" onerror="fallbackImg(this)"  src="/img/incoming_proxy.png" alt="No IMAGE">',
									'</tpl>',
									'<tpl if="thumb == \'incoming.jpg\'"><img id ="thumb-list-{content_id}"  onload="resizeImg(this, {w:80, h:60})" ext:qtip="{qtip}"  src="/img/incoming_proxy.png" alt="No IMAGE"></tpl>',
									'<tpl if="!Ext.isEmpty(thumb) && thumb != \'incoming.jpg\'">',
										'<img id ="thumb-list-{content_id}" onload="resizeImg(this, {w:80, h:60})" onerror="fallbackImg(this)" ext:qtip="{qtip}" src="{thumb_web_root}/{thumb}"',
										'<tpl if="this.gtWidth(thumb, thumb_web_root) === 1"> width="80" </tpl>',
										'<tpl if="this.gtWidth(thumb, thumb_web_root) === -1"> height="60" </tpl>',
										'>',
									'</tpl>',
								'</tpl>',
							'</tpl>',
							'<tpl if="bs_content_id == <?=SOUND?>">',
								'<tpl if="Ext.isEmpty(thumb) || thumb === \'/img/audio_thumb.png\'"><img id ="thumb-list-{content_id}"  onload="resizeImg(this, {w:60, h:60})" ext:qtip="{qtip}"  src="/img/audio_thumb.png" alt="No IMAGE"></tpl>',
								'<tpl if="thumb == \'/img/incoming_proxy.png\'"><img id ="thumb-list-{content_id}"  onload="resizeImg(this, {w:60, h:60})" ext:qtip="{qtip}"  src="/img/incoming_proxy.png" alt="No IMAGE"></tpl>',
								'<tpl if="!Ext.isEmpty(thumb) && thumb != \'/img/incoming_proxy.png\'">',
								'<img onload="resizeImg(this, {w:60, h:60})" ext:qtip="{qtip}" src="{thumb_web_root}/{thumb}"',
								'<tpl if="this.gtWidth(thumb, thumb_web_root) === 1"> width="60" </tpl>',
								'<tpl if="this.gtWidth(thumb, thumb_web_root) === -1"> height="60" </tpl>',
								'>',
								'</tpl>',
							'</tpl>',
							'<tpl if="bs_content_id == <?=IMAGE?>">',
								'<tpl if="Ext.isEmpty(thumb) || thumb === \'/img/incoming_proxy.png\'"><img id ="thumb-list-{content_id}"  onload="resizeImg(this, {w:60, h:60})" ext:qtip="{qtip}"  src="/img/incoming_proxy.png" alt="No IMAGE"></tpl>',
								'<tpl if="thumb == \'/img/incoming_proxy.png\'"><img id ="thumb-list-{content_id}"  onload="resizeImg(this, {w:60, h:60})" ext:qtip="{qtip}"  src="/img/incoming_proxy.png" alt="No IMAGE"></tpl>',
								'<tpl if="!Ext.isEmpty(thumb) && thumb != \'/img/incoming_proxy.png\'">',
								'<tpl if="is_group ==\'G\'">',
								'<div   ext:qtip="{qtip}"  draggable="true"  id ="thumb-{content_id}"  style="height:60px;width:80px;background-image:url(\'{thumb_web_root}/{thumb_group_path}\');background-position:center center;background-repeat:no-repeat;background-size:contain;text-align:center;" ></div',
								'</tpl>',
								'<tpl if="is_group ==\'I\'">',
								'<div   ext:qtip="{qtip}"  draggable="true"  id ="thumb-{content_id}"  style="height:60px;width:80px;background-image:url(\'{thumb_web_root}/{thumb}\');background-position:center center;background-repeat:no-repeat;background-size:contain;text-align:center;" ></div',
								'</tpl>',
								'<tpl if="this.gtWidth(thumb, thumb_web_root) === 1"> width="60" </tpl>',
								'<tpl if="this.gtWidth(thumb, thumb_web_root) === -1"> height="60" </tpl>',
								'>',
								'</tpl>',
							'</tpl>',
							'<tpl if="bs_content_id == <?=DOCUMENT?>">',
							'<tpl if="!Ext.isEmpty(thumb) && thumb != \'incoming.jpg\'">',
							'<img id ="thumb-list-{content_id}" onload="resizeImg(this, {w:70, h:50})" ext:qtip="{qtip}" src="{thumb_web_root}/{thumb}"',
							'<tpl if="this.gtWidth(thumb, thumb_web_root) === 1"> width="70" </tpl>',
							'<tpl if="this.gtWidth(thumb, thumb_web_root) === -1"> height="50" </tpl>',
							'>',
							'</tpl>',
							'<tpl if="Ext.isEmpty(thumb) || thumb === \'/img/doc_thumb.png\'">',
								'<img id ="thumb-list-{content_id}"  onload="resizeImg(this, {w:70, h:50})" ext:qtip="{qtip}"  src="/img/doc_thumb.png" alt="No IMAGE">',
								'</tpl>',
							'</tpl>',
							'</td>',
							'<td class="x-grid3-col x-grid3-cell">',
								'<div class="x-grid3-cell-inner" unselectable="on" >{summary_field}</div>',
							'</td>',
						'</tr>',
					'</tbody>',
				'</table>',
			'</div>',
			{

			//  function return이 1인 경우 가로가 비율에 비하여 크며
			//  return이 -1인 경우 세로가 비율에 비하여 크며
			//  return이 0인 경우 이미지 가로,세로 중 하나 이상이 0인 잘못된 데이터

				gtWidth: function(url, lowres_web_root){
					var imgObj = new Image();
					imgObj.src = lowres_web_root+'/' + url;

					if (imgObj.width == 0 || imgObj.height == 0) return 0;
					if ( (imgObj.width/Ariel.list_width) > (imgObj.height/Ariel.list_height) )
					{
						return 1;
					}
					else
					{
						return -1;
					}
				},
				mode_template: 'summary',
				getContentStatusCss: function(status) {
					var css = 'x-grid3-row ux-explorerview-detailed-icon-row content-status-' + status;					
					return css;
				}
			}
		),
		//Thumbnail view
		tile: new Ext.XTemplate(			
			'<div mode="thumb" class="{[this.getContentStatusCss(values.status)]}" style="{[this.getContentStyle(values)]}">',
				'<table class="x-grid3-row-table">',
					'<tbody>',
						'<div class="where_icons">', // content icons div box
							'<tpl if="icons">{icons}</tpl>', // content icons store/get_content_list/libs/
							'<tpl if="!icons"><tr height="16px"></tr></tpl>',
						'</div>',
						'<tr colspan="2" align="center" height="<?=$thumb_height - 30?>">',
						'<td colspan="2" class="x-grid3-col x-grid3-cell ux-explorerview-icon" style=" vertical-align:middle" align="center">',
							'<tpl if="bs_content_id == <?=MOVIE?> || bs_content_id == <?=SEQUENCE?> ">',
								'<div class="image_container" id="image_{content_id}">',
									'<tpl if="is_group ==\'G\'">',
										'<div class="thumb_img_box">',
											'<tpl if="!Ext.isEmpty(premiere_media_path) && Ext.isEmpty(thumb_group_path) ">', //premiere project media type check
												'<img class="thumb_img" id ="thumb-{content_id}" onload="resizeImg(this)" src="/css/images/sequence.jpeg" alt="Premiere Sequence File">',
											'</tpl>',
											'<tpl if="Ext.isEmpty(premiere_media_path) && Ext.isEmpty(thumb_group_path)">',
												'<img class="thumb_img" id ="thumb-{content_id}" onload="resizeImg(this)" src="/img/incoming_proxy.png" alt="No IMAGE">',
												'<div class="play-container" onclick="fn_show_player_for_play_icon({content_id},event);" >',
													'<div class="outer-circle">',
													  '<div class="inner-circle">',
														  '<div class="triangle">',
														  '</div>',
													  '</div>',
													'</div>',
												'</div>',
											'</tpl>',
											'<tpl if="!Ext.isEmpty(premiere_media_path) && !Ext.isEmpty(thumb_group_path)">',
												'<tpl if="is_working==0">',
													'<img class="thumb_img" id ="thumb-{content_id}" align="center" style=" vertical-align:middle"  onload="resizeImg(this)"  src="{thumb_web_root}/{thumb_group_path}">',
													'<div class="play-container" onclick="fn_show_player_for_play_icon({content_id},event);">',
														'<div class="outer-circle">',
														  '<div class="inner-circle">',
															  '<div class="triangle">',
															  '</div>',
														  '</div>',
														'</div>',
													'</div>',
												'</tpl>',
												'<tpl if="is_working==1">',
													'<img class="thumb_img" id ="thumb-{content_id}" onload="resizeImg(this)" src="/img/incoming_proxy.png" alt="No IMAGE">',
													'<div class="play-container" onclick="fn_show_player_for_play_icon({content_id},event);">',
														'<div class="outer-circle">',
														  '<div class="inner-circle">',
															  '<div class="triangle">',
															  '</div>',
														  '</div>',
														'</div>',
													'</div>',
												'</tpl>',
											'</tpl>',
											'<tpl if="Ext.isEmpty(premiere_media_path) && !Ext.isEmpty(thumb_group_path)">',
												'<tpl if="is_working==0">',
													'<img class="thumb_img" id ="thumb-{content_id}" align="center" style=" vertical-align:middle"  onload="resizeImg(this)"  src="{thumb_web_root}/{thumb_group_path}">',
													'<div class="play-container" onclick="fn_show_player_for_play_icon({content_id},event);">',
														'<div class="outer-circle">',
														  '<div class="inner-circle">',
															  '<div class="triangle">',
															  '</div>',
														  '</div>',
														'</div>',
													'</div>',
												'</tpl>',
												'<tpl if="is_working==1">',
													'<img class="thumb_img" id ="thumb-{content_id}" onload="resizeImg(this)" src="/img/incoming_proxy.png" alt="No IMAGE">',
													'<div class="play-container" onclick="fn_show_player_for_play_icon({content_id},event);">',
														'<div class="outer-circle">',
														  '<div class="inner-circle">',
															  '<div class="triangle">',
															  '</div>',
														  '</div>',
														'</div>',
													'</div>',
												'</tpl>',
											'</tpl>',
										'</div>',
									'</tpl>',
									'<tpl if="is_group ==\'I\'">',
										'<div class="thumb_img_box">',
											'<tpl if="!Ext.isEmpty(premiere_media_path) && Ext.isEmpty(thumb) ">', //premiere project media type check
												'<img class="thumb_img" id ="thumb-{content_id}" onload="resizeImg(this)" src="/css/images/sequence.jpeg" alt="Premiere Sequence File">',
											'</tpl>',
											'<tpl if="Ext.isEmpty(premiere_media_path) && Ext.isEmpty(thumb)">',
												'<img class="thumb_img" id ="thumb-{content_id}" onerror="fallbackImg(this)" onload="resizeImg(this)" src="/img/incoming_proxy.png" alt="No IMAGE">',
												'<div class="play-container" onclick="fn_show_player_for_play_icon({content_id},event);" >',
													'<div class="outer-circle">',
													  '<div class="inner-circle">',
														  '<div class="triangle">',
														  '</div>',
													  '</div>',
													'</div>',
												'</div>',
											'</tpl>',
											'<tpl if="!Ext.isEmpty(premiere_media_path) && !Ext.isEmpty(thumb)">',
												'<tpl if="is_working==0">',
													'<img class="thumb_img" id ="thumb-{content_id}" align="center" style=" vertical-align:middle" onerror="fallbackImg(this)"  onload="resizeImg(this)"  src="{thumb_web_root}/{thumb}">',
													'<div class="play-container" onclick="fn_show_player_for_play_icon({content_id},event);">',
														'<div class="outer-circle">',
														  '<div class="inner-circle">',
															  '<div class="triangle">',
															  '</div>',
														  '</div>',
														'</div>',
													'</div>',
												'</tpl>',
												'<tpl if="is_working==1">',
													'<img class="thumb_img" id ="thumb-{content_id}" onerror="fallbackImg(this)" onload="resizeImg(this)" src="/img/incoming_proxy.png" alt="No IMAGE">',
													'<div class="play-container" onclick="fn_show_player_for_play_icon({content_id},event);">',
														'<div class="outer-circle">',
														  '<div class="inner-circle">',
															  '<div class="triangle">',
															  '</div>',
														  '</div>',
														'</div>',
													'</div>',
												'</tpl>',
											'</tpl>',
											'<tpl if="Ext.isEmpty(premiere_media_path) && !Ext.isEmpty(thumb)">',
												'<tpl if="is_working==0">',
													'<img class="thumb_img" id ="thumb-{content_id}" align="center" style=" vertical-align:middle" onerror="fallbackImg(this)"  onload="resizeImg(this)"  src="{thumb_web_root}/{thumb}">',
													'<div class="play-container" onclick="fn_show_player_for_play_icon({content_id},event);">',
														'<div class="outer-circle">',
														  '<div class="inner-circle">',
															  '<div class="triangle">',
															  '</div>',
														  '</div>',
														'</div>',
													'</div>',
												'</tpl>',
												'<tpl if="is_working==1">',
													'<img class="thumb_img" id ="thumb-{content_id}" onerror="fallbackImg(this)" onload="resizeImg(this)" src="/img/incoming_proxy.png" alt="No IMAGE">',
													'<div class="play-container" onclick="fn_show_player_for_play_icon({content_id},event);">',
														'<div class="outer-circle">',
														  '<div class="inner-circle">',
															  '<div class="triangle">',
															  '</div>',
														  '</div>',
														'</div>',
													'</div>',
												'</tpl>',
											'</tpl>',
										'</div>',
									'</tpl>',
									'<tpl if="!Ext.isEmpty(sys_video_rt)">',
										<?php if($action_icon_slide_yn == 'Y'){?>
										'<div class="template_duration">',
										<?php }else{?>
										'<div class="template_duration_no_slide">',
										<?php }?>
											'{sys_video_rt}',
										'</div>',
									'</tpl>',
									<?php if($action_icon_slide_yn == 'Y'){?>
									'<div class="hide_icons">',
									<?php }else{ ?>
									'<div class="hide_icons_no_slide">',
									<?php }?>
										'<i class="fa fa-align-justify" title="'+_text('MN02410')+'" onclick="fn_action_icon_show_context_menu({content_id},event)" style="font-size: 15px;"></i>',
										'<i class="fa-stack" title="'+_text('MN02342')+'" onclick="fn_action_icon_show_detail_popup({content_id})">',
										   '<i class="fa fa-square-o" style="position:absolute;left: -2px;bottom: 5px;font-size: 14px;"></i>',
										   '<i class="fa fa-external-link-square" style="position:absolute;right: -1px;top: 5%;font-size: 11px;"></i>',
										'</i>',
										<?=$show_workflow_text?>
										'<i class="fa fa-picture-o" title="'+_text('MN02343')+'" ext:qtip="{qtip}" ext:qwidth=<?=CONFIG_QTIP_WIDTH?> style="font-size: 15px;"></i>',
										'&nbsp',
									'</div>',
								'</div>',
							'</tpl>',
							'<tpl if="bs_content_id == <?=SOUND?>">',
								'<div class="image_container" id="image_{content_id}">',
									'<div class="thumb_img_box">',
										'<tpl if="!Ext.isEmpty(thumb) && thumb != \'/img/audio_thumb.png\'">',
											'<img class="thumb_img" id ="thumb-{content_id}" align="center" style=" vertical-align:middle"  onload="resizeImgs(this)" ext:qtip="{qtip}" ext:qwidth=<?=CONFIG_QTIP_WIDTH_DOCUMENT?> src="{thumb_web_root}/{thumb}">',
										'</tpl>',
										'<tpl if="Ext.isEmpty(thumb) || thumb === \'/img/audio_thumb.png\'">',
											'<img class="thumb_img" id ="thumb-{content_id}" onload="resizeImg(this)" ext:qtip="{qtip}" ext:qwidth=<?=CONFIG_QTIP_WIDTH_DOCUMENT?>  src="/img/audio_thumb.png" alt="No IMAGE">',
										'</tpl>',
									'</div>',
									'<tpl if="!Ext.isEmpty(sys_video_rt)">',
										<?php if($action_icon_slide_yn == 'Y'){?>
										'<div class="template_duration">',
										<?php }else{?>
										'<div class="template_duration_no_slide">',
										<?php }?>
											'{sys_video_rt}',
										'</div>',
									'</tpl>',
									<?php if($action_icon_slide_yn == 'Y'){?>
									'<div class="hide_icons">',
									<?php }else{ ?>
									'<div class="hide_icons_no_slide">',
									<?php }?>
										'<i class="fa fa-align-justify" title="'+_text('MN02410')+'" onclick="fn_action_icon_show_context_menu({content_id},event)" style="font-size: 15px;"></i>',
										'<i class="fa-stack" title="'+_text('MN02342')+'" onclick="fn_action_icon_show_detail_popup({content_id})">',
										   '<i class="fa fa-square-o" style="position:absolute;left: -2px;bottom: 5px;font-size: 14px;"></i>',
										   '<i class="fa fa-external-link-square" style="position:absolute;right: -1px;top: 5%;font-size: 11px;"></i>',
										'</i>',
										<?=$show_workflow_text?>
										'&nbsp',
									'</div>',
								'</div>',
							'</tpl>',
							'<tpl if="bs_content_id == <?=DOCUMENT?>">',
								'<div class="image_container" id="image_{content_id}">',
									'<div class="thumb_img_box">',
										'<tpl if="!Ext.isEmpty(thumb) && thumb != \'/img/doc_thumb.png\'">',
											'<img class="thumb_img" id ="thumb-{content_id}" align="center" style=" vertical-align:middle"  onload="resizeImgs(this)" ext:qtip="{qtip}" ext:qwidth=<?=CONFIG_QTIP_WIDTH_DOCUMENT?> src="{thumb_web_root}/{thumb}">',
										'</tpl>',
										'<tpl if="Ext.isEmpty(thumb) || thumb === \'/img/doc_thumb.png\'">',
											'<img class="thumb_img" id ="thumb-{content_id}" onload="resizeImg(this)" ext:qtip="{qtip}" ext:qwidth=<?=CONFIG_QTIP_WIDTH_DOCUMENT?>  src="/img/doc_thumb.png" alt="No IMAGE">',
										'</tpl>',
									'</div>',
									'<tpl if="!Ext.isEmpty(sys_video_rt)">',
										<?php if($action_icon_slide_yn == 'Y'){?>
										'<div class="template_duration">',
										<?php }else{?>
										'<div class="template_duration_no_slide">',
										<?php }?>
											'{sys_video_rt}',
										'</div>',
									'</tpl>',
									<?php if($action_icon_slide_yn == 'Y'){?>
									'<div class="hide_icons">',
									<?php }else{ ?>
									'<div class="hide_icons_no_slide">',
									<?php }?>
										'<i class="fa fa-align-justify" title="'+_text('MN02410')+'" onclick="fn_action_icon_show_context_menu({content_id},event)" style="font-size: 15px;"></i>',
										'<i class="fa-stack" title="'+_text('MN02342')+'" onclick="fn_action_icon_show_detail_popup({content_id})">',
										   '<i class="fa fa-square-o" style="position:absolute;left: -2px;bottom: 5px;font-size: 14px;"></i>',
										   '<i class="fa fa-external-link-square" style="position:absolute;right: -1px;top: 5%;font-size: 11px;"></i>',
										'</i>',
										<?=$show_workflow_text?>
										'&nbsp',
									'</div>',
								'</div>',
							'</tpl>',
							'<tpl if="bs_content_id == <?=IMAGE?>">',
								'<div class="image_container" id="image_{content_id}">',
									'<div class="thumb_img_box">',
										'<tpl if="!Ext.isEmpty(thumb) && thumb != \'/img/incoming_proxy.png\'">',
											'<tpl if="is_group ==\'G\'">',
											'<img class="thumb_img" id ="thumb-{content_id}" align="center"  ext:qtip="{qtip}"  ext:qwidth="410" src="{thumb_web_root}/{thumb_group_path}">',
											'</tpl>',
											'<tpl if="is_group ==\'I\'">',
											'<img class="thumb_img" id ="thumb-{content_id}" align="center"  ext:qtip="{qtip}" ext:qwidth="410"   src="{thumb_web_root}/{thumb}">',
											'</tpl>',
										'</tpl>',
										'<tpl if="Ext.isEmpty(thumb) || thumb === \'/img/incoming_proxy.png\'">',
											'<img onload="resizeImg(this)" src="/img/incoming_proxy.png" alt="No IMAGE">',
										'</tpl>',
									'</div>',
									//'<tpl if="!Ext.isEmpty(sys_video_rt)">',
									//	<?php if($action_icon_slide_yn == 'Y'){?>
									//	'<div class="template_duration">',
									//	<?php }else{?>
									//	'<div class="template_duration_no_slide">',
									//	<?php }?>
									//		'{sys_video_rt}',
									//	'</div>',
									//'</tpl>',
									<?php if($action_icon_slide_yn == 'Y'){?>
									'<div class="hide_icons">',
									<?php }else{ ?>
									'<div class="hide_icons_no_slide">',
									<?php }?>
										'<i class="fa fa-align-justify" title="'+_text('MN02410')+'" onclick="fn_action_icon_show_context_menu({content_id},event)" style="font-size: 15px;"></i>',
										'<i class="fa-stack" title="'+_text('MN02342')+'" onclick="fn_action_icon_show_detail_popup({content_id})">',
										   '<i class="fa fa-square-o" style="position:absolute;left: -2px;bottom: 5px;font-size: 14px;"></i>',
										   '<i class="fa fa-external-link-square" style="position:absolute;right: -1px;top: 5%;font-size: 11px;"></i>',
										'</i>',
										<?=$show_workflow_text?>
										'&nbsp',
									'</div>',
								'</div>',
							'</tpl>',


						'</td>',
						'</tr>',
						'<div class="x-grid3-cell-inner" unselectable="on" ><tr colspan="2" >',
							'<td colspan="2" align="center" class="x-grid3-col x-grid3-cell"  ><div class="x-grid3-cell-inner" unselectable="on" >',

							'<tpl if=" ud_content_id == 4000305 ">',
								'{thumb_field}<br />{ori_size} / {sysf57055}',
							'</tpl>',

							'<tpl if=" ud_content_id == 4000308 ">',
								'{thumb_field}<br />{ori_size}',
							'</tpl>',

							'<tpl if=" ud_content_id == 4000306  ">',
								'{thumb_field}<br />{ori_size} / {sysf507}',
							'</tpl>',
							'<tpl if=" ud_content_id == 4000325 ">',
								'{thumb_field}<br />{ori_size}',
							'</tpl>',
							'<tpl if=" ud_content_id != 4000305 && ud_content_id != 4000308 && ud_content_id != 4000306 && ud_content_id != 4000325">',
								'<font class="content_title" title="{register_type_text}" >{thumb_field}</font>',
							'</tpl>',
							'</div></td></tr>',
							'<tr colspan="2" ><td colspan="2" align="center" class="x-grid3-col x-grid3-cell" ><div class="x-grid3-cell-inner" unselectable="on" >',
								'<tpl if=" ud_content_id != 4000406">',
									'<font title="{register_type_text}" >{usr_materialid}</font>',
								'</tpl>',
						'</div></td></tr>',
					'</tbody>',
				'</table>',
			'</div>',

			{
				gtWidth: function(url, lowres_web_root){
					var imgObj = new Image();
					imgObj.src = lowres_web_root+'/' + url;

					if (imgObj.width == 0 || imgObj.height == 0) return 0;
					if ( (imgObj.width/Ariel.thumb_width) > (imgObj.height/Ariel.thumb_height) )
					{
						return 1;
					}
					else
					{
						return -1;
					}
				},
				mode_template: 'thumb',
				// 썸네일 뷰 일때 css 처리하는 매서드
				getContentStatusCss: function(status) {
					var css = 'x-grid3-row ux-explorerview-large-icon-row content-status-' + status;																	
					return css;
				},
				getContentStyle: function(values){
					var usrMeta = values.usr_meta;
					
					if(usrMeta){
						var usePrhibtAt = usrMeta.use_prhibt_at;
						if(usePrhibtAt == 'Y'){
                            return 'box-shadow: inset 0px 0px 0px 1px red';
						}
					}
                	
				}
			}
		)
	},

	listeners: {
		beforerender: function(self) {		
			// 리스트 뷰일 때 콘텐츠 상태로 row에 대한 css 적용
			this.viewConfig.getRowClass = function(record, rowIndex, rp, store) {
				var css = [];
				css.push('content-status-' + record.get('status'));				
				return css.join(' ');
			}
			
			// ContentStatus js 동적 로드
			Ext.Loader.load(['/javascript/content/ContentStatus.js'], function() {
				var contentStatusManager = new ContentStatusManager();
				// 콘텐츠 상태를 로드해서 css를 미리 생성
				contentStatusManager.load();
			}, self);	
		},
			//2015-12-30 현재 상태 표시
		afterrender : function(self) {
			$('#ud_content_id'+<?=$ud_content_id?>).find('div.x-panel-bwrap').children('.x-panel-body.x-panel-body-noheader.x-panel-body-noborder').wrap( "<div class='cls_grid_content'></div>" );
			var ori_height = $('#ud_content_id'+<?=$ud_content_id?>).closest('.x-panel-body.x-panel-body-noheader.x-panel-body-noborder').height();
            var height = (ori_height - 36)+"px";
            
            //아래 기능 없어도 창 조절시 그리드가 틀어지지 않음. 아래 기능 푼다면 상세검색 버튼 누를때 그리드 하단 UI가 틀어짐
			//$(".cls_grid_content").attr({style: "height:" + height});
			$(window).resize(function () { 
				setTimeout(function(){ 
					var win_height = $('#ud_content_id'+<?=$ud_content_id?>).closest('.x-panel-body.x-panel-body-noheader.x-panel-body-noborder').height();
					var win_change_height = (win_height - 36)+"px";
					//$(".cls_grid_content").attr({style: "height:" + win_change_height});
				},200);
			});
			this.init_tools(self);
		},
		rowcontextmenu: function(self, rowIdx, e){
			e.stopEvent();

			var ownerCt = self;

			var sm = self.getSelectionModel();
			if ( ! sm.isSelected(rowIdx)) {
				sm.selectRow(rowIdx);
			}
			var rs = [];
			var _rs = sm.getSelections();
			var archived_checked = 0;
			var restore_checked = 0;
			var process_checked = 0;

			var approval_check = 0;
			var un_approval_check = 0;

			//var is_approve_current = sm.getSelected().data['approval_yn'];
			Ext.each(_rs, function(r, i, a){
				if(r.data['archive_yn'] =='Y'){
					archived_checked = 1;
				} else if(r.data['archive_yn'] =='N'){
					restore_checked = 1;
				} else if(r.data['archive_yn'] =='P'){
					process_checked = 1;
				}

				if(r.data['approval_yn'] =='Y'){
					approval_check = 1;
				}else{
					un_approval_check = 1;
				}

				rs.push({
					content_id: r.get('content_id')
				});
			});
			
			if( !Ext.isEmpty(Ariel.menu_context) ) {

				var restore_menu_item = Ext.getCmp('restore_menu_item');
				var archive_menu_item = Ext.getCmp('archive_menu_item');
				var approval_content_menu_item = Ext.getCmp('approval_content_menu_item');
				var un_approval_content_menu_item = Ext.getCmp('un_approval_content_menu_item');
				var is_archive_current = sm.getSelected().data['archive_yn'];
				var ud_content_id      = sm.getSelected().data['ud_content_id'];

				/*
					premiere 관련 contenxt 메뉴 추가 2016-08-24 by hkh
				*/
				var premiere_media_path = sm.getSelected().data['premiere_media_path'];
				var ori_path = sm.getSelected().data['ori_path'];
				
                <?php
                    if($arr_sys_code['premiere_plugin_use_yn']['use_yn'] == 'Y'){
                        echo "
                            if(ud_content_id == '2' && Ext.isEmpty(ori_path) && !Ext.isEmpty(premiere_media_path)){
                                Ext.getCmp('create_ame_archive').setVisible(true);
                            }else {
                                Ext.getCmp('create_ame_archive').setVisible(false);
                            }
                            ";
                    }
                ?>

				if(Ext.isPremiere){

					var premiere_media_path = sm.getSelected().data['premiere_media_path'];

					//alert(premiere_media_path);
					if(Ext.isEmpty(premiere_media_path)){
						Ext.getCmp('import_premiere_sequence_menu_item').setVisible(false);
					}else {
						Ext.getCmp('import_premiere_sequence_menu_item').setVisible(true);
					}

					var ori_path = sm.getSelected().data['ori_path'];

					if(Ext.isEmpty(ori_path)){
						Ext.getCmp('create_a_premiere_sequence_menu_item').setVisible(false);
					}else {
						Ext.getCmp('create_a_premiere_sequence_menu_item').setVisible(true);
					}					

					/*
						AME 아카이브 시  해당 탭 과 원본 파일 없고 / 시퀀스 파일만 있을 경우 
						메뉴가 노출되도록 변경
					*/

				} else {
					//Ext.getCmp('import_premiere_sequence_menu_item').setVisible(false);
					//Ext.getCmp('create_a_premiere_sequence_menu_item').setVisible(false);
				}


			if(Ext.isPhotoshop){

				var ori_path = sm.getSelected().data['ori_path'];

				if(Ext.isEmpty(ori_path)){
					Ext.getCmp('import_image_item').setVisible(false);
				}else {
					Ext.getCmp('import_image_item').setVisible(true);
				}

			}else {
				//Ext.getCmp('import_image_item').setVisible(false);
			}
			
				if(!Ext.isEmpty(approval_content_menu_item) && !Ext.isEmpty(un_approval_content_menu_item)) {
					if(approval_check == 1 && un_approval_check == 0){
						// un_approval_check
						approval_content_menu_item.setVisible(false);
						un_approval_content_menu_item.setVisible(true);
					}else if(approval_check == 0 && un_approval_check == 1){
						// approval_check
						un_approval_content_menu_item.setVisible(false);
						approval_content_menu_item.setVisible(true);
					} else {
						un_approval_content_menu_item.setVisible(false);
						approval_content_menu_item.setVisible(false);
					}
				}else{
					/*Ariel.menu_context.showAt(e.getXY());
					return;*/
				}

				if(!Ext.isEmpty(archive_menu_item) && !Ext.isEmpty(restore_menu_item)) {
					if(archived_checked == 1 && restore_checked == 0 && process_checked == 0){
						// restore
						archive_menu_item.setVisible(false);
						restore_menu_item.setVisible(true);
						Ariel.menu_context.showAt(e.getXY());
						return;
					}else if(archived_checked == 0 && restore_checked == 1 && process_checked == 0){
						// archive
						restore_menu_item.setVisible(false);
						archive_menu_item.setVisible(true);
						Ariel.menu_context.showAt(e.getXY());
						return;
					} else {
						restore_menu_item.setVisible(false);
						archive_menu_item.setVisible(false);
						Ariel.menu_context.showAt(e.getXY());
						return;
					}
				}else{
                    showContextMenuLists();
					Ariel.menu_context.showAt(e.getXY());
					return;
				}
			}
		}
	},

	loadDDfile: function(self){
		//섬네일이미지에 원본파일 경로로 변경하는 함수  by 이성용
		 if( Ext.isChrome ){
			//크롬일때
			self.getStore().each(function(r){
				//스토어의 각 콘텐츠 별로 경로 매핑

				var content_id = r.get('content_id');
				//원본경로 정보 찾기				

				//데이터뷰에서 각 이미지에 설정해놓은 ID로 객체를 찾는다
				var thumb_img = document.getElementById('thumb-'+content_id);
				var thumb_list_img= document.getElementById('thumb-list-'+content_id);
				var list_img= document.getElementById('list-'+content_id);
						
                if( !Ext.isEmpty(thumb_img)){
                    thumb_img.addEventListener("dragstart",function(evt){
                        Ariel.ddEvent(evt, r );
                    },false);
                }
                if( !Ext.isEmpty(thumb_list_img) ){
                    thumb_list_img.addEventListener("dragstart",function(evt){
                        Ariel.ddEvent(evt, r );
                    },false);
                }
                if( !Ext.isEmpty(list_img) ){
                    list_img.addEventListener("dragstart",function(evt){
                        Ariel.ddEvent(evt, r );
                    },false);
                }
				
			});
		} else {
			//console.log(self.ownerCt.title);
		}

		return true;
	},

	store: new Ext.data.JsonStore({
		url: '/store/get_content.php',
		idProperty: 'content_id',
		totalProperty: 'total',
		root: 'results',
		remoteSort: true,
		sortInfo: {
			field: 'created_date',
			direction: 'desc'
		},
		listeners: {
			exception: function(self, type, action, opts, response, arg){                
                console.log('Content store load exception: ', response);
				if (type == 'response') {
					try {
						var r = Ext.decode(response.responseText);
						if (!r.success) {
							Ext.Msg.show({
								title: '경고',
								msg: r.msg,
								icon: Ext.Msg.WARNING,
								buttons: Ext.Msg.OK
							});
						}
					} catch (e) {
						Ext.Msg.show({
							title: '경고',
							msg: response.responseText,
							icon: Ext.Msg.WARNING,
							buttons: Ext.Msg.OK
						});
					}
				} else {
					Ext.Msg.show({
						title: '경고',
						msg: '통신 오류',
						icon: Ext.Msg.ERROR,
						buttons: Ext.Msg.OK
					});
				}
				// 로딩마스크 제거
				Ext.getCmp('main_card_search').getEl().unmask();
			},
			beforeload: function(self, opt){				
				var beforeParam = Ext.getCmp('tab_warp').mediaBeforeParam || {};
				//관심콘텐츠용 검색은 이전값 유지 안하도록
				if(beforeParam.action == 'favorite') beforeParam.action = '';
				var value = Ext.getCmp('search_input').getValue();
				var objectArray = [beforeParam, opt.params];

				self.baseParams = mergeObjectProperty(objectArray);

				self.baseParams.search_q = value;

				if( Ariel.check_flashnet == 'Y' ){
					var archive_combo = Ext.getCmp('archive_search_combo').getValue();
					self.baseParams.archive_combo =  archive_combo;
				}


				//스토어 로드시 선택된 트리노드 항상 반영하도록. 트리가 처음 생성될 땐 값이 없으므로 뺌
				var now_tree = Ext.getCmp('tree-tab').getActiveTab();
				var node = now_tree.getSelectionModel().getSelectedNode();
				if( !Ext.isEmpty(node) ) {
				   // self.baseParams.filter_value = node.getPath();
				}
			},
			load : function(self, records, opt ){  
				
				Ext.Ajax.request({           
                    url: '/store/user/user_option/get_slider_value.php',
                    success: function (response, options) {
                    	var data =  Ext.decode(response.responseText);
                    	var slide_thumbnail_size = data[0].slide_thumbnail_size;
                    	var number = parseInt(slide_thumbnail_size);
                    	Ext.getCmp('grid_thumb_slider').setValue(number);
                    }
                });
				if(!Ext.isEmpty(self.reader.jsonData.ud_total_list))
				{
					var ud_total_list = self.reader.jsonData.ud_total_list;
					var tab_warp = 'tab_warp';
					var content_tab = self.reader.jsonData.ud_content_id;
					var ud_new_total_list = self.reader.jsonData.ud_new_total_list;
					var new_content_count_all_tab = self.reader.jsonData.total_new_content_count;

					if(content_tab == 4000406) {
						tab_warp = 'tab_warp_audio';
					}

					Ext.getCmp(tab_warp).items.each(function(item){
							var ud_content_id = item.ud_content_id;
							var ud_total = ud_total_list[ud_content_id];

							var new_cnt;
							if(!Ext.isEmpty(ud_new_total_list)){
								if(Ext.isEmpty(ud_new_total_list[ud_content_id])){
									new_cnt = 0;
								}else{
									new_cnt = ud_new_total_list[ud_content_id]['new_cnt'];
								}
							}
							if(Ext.isEmpty(ud_total)) ud_total = 0;
							if(Ext.isEmpty(new_cnt)) new_cnt = 0;

							var formatedContentCount = Ext.util.Format.number(ud_total, '0,000');
							var notice_new_content_count_yn = '<?= $notice_new_content_count_yn?>';
							if(notice_new_content_count_yn == 'Y'){
								if(new_cnt >0){
									item.setTitle(item.initialConfig.title +'&nbsp&nbsp <font size=2px color="#FFFFFF" class="new_count_icon"><b>&nbsp'+new_cnt+'&nbsp</b></font>');
								} else{
									item.setTitle(item.initialConfig.title +'<font size=2px color="#FFFFFF"></font>');
								}
								if(new_content_count_all_tab >0){
									Ext.fly('total_new_content_all_tab').dom.innerHTML = '<font size=2px color="#FFFFFF" class="new_count_icon"><b>&nbsp'+new_content_count_all_tab+'&nbsp</b></font>';
								}else{
									Ext.fly('total_new_content_all_tab').dom.innerHTML = '';
								}
							}else{							

								item.setTitle(item.initialConfig.title +'('+ formatedContentCount+ ')' +'<font size=2px color="#FFFFFF"></font>');
								Ext.fly('total_new_content_all_tab').dom.innerHTML = '';
							}
					});
				}else{
					 Ext.getCmp('tab_warp').items.each(function(item){
					 	//item.setTitle(item.initialConfig.title +'(0)');
						 item.setTitle(item.initialConfig.title);
					 });
				}

				var view = Ext.getCmp('tab_warp').getActiveTab().get(0);
				if(self.reader.jsonData.ud_content_id == 4000406) {
					view = Ext.getCmp('tab_warp_audio').getActiveTab().get(0);
				}

				if(!Ext.isEmpty(view)) {
					var contentGridSM = view.getSelectionModel();
					var selectedRowCount = contentGridSM.getCount();
					var selectedCountField = view.getBottomToolbar( ).find('itemId', 'selectedCount')[0];

					selectedCountField.setValue(selectedRowCount + '&nbsp' + _text('MN01996'));//MN01996 개 선택됨
				}
				
				view.loadDDfile(view);
				Ext.getCmp('tab_warp').mediaBeforeParam = opt.params;
				Ext.getCmp('grid_summary_slider').hide();
				Ext.getCmp('grid_thumb_slider').show();
				Ext.getCmp('grid_thumb_slider').change_image_size();

				var grid_content_view = Ext.getCmp('ud_content_id'+<?=$ud_content_id?>).getView();
				if(typeof grid_content_view.rowTemplate === 'undefined' || grid_content_view.rowTemplate === null){
					Ext.getCmp('grid_thumb_slider').hide();
					Ext.getCmp('grid_summary_slider').hide();
				}else{
					if(grid_content_view.rowTemplate.mode_template == 'thumb'){
						Ext.getCmp('grid_summary_slider').hide();
						Ext.getCmp('grid_thumb_slider').show();
						Ext.getCmp('grid_thumb_slider').change_image_size();
					}else if (grid_content_view.rowTemplate.mode_template === 'summary') {
						view.getView().changeTemplate(view.template.list);
						Ext.getCmp('form_panel_thumb_slider').hide();
						Ext.getCmp('grid_thumb_slider').hide();
						Ext.getCmp('form_panel_summary_slider').show();
						Ext.getCmp('grid_summary_slider').show();
						//Ext.getCmp('grid_summary_slider').setValue(100);
						Ext.getCmp('grid_summary_slider').change_image_size();
					} else {
						Ext.getCmp('grid_thumb_slider').hide();
						Ext.getCmp('grid_summary_slider').hide();
					}
				}

				Ext.getCmp('main_card_search').getEl().unmask();

				var isFirst = true;
				$("div.cls_slider td.x-toolbar-cell").each(function(index, item){
					var child = $(item).find("div.x-slider");
					var id = $(child).attr("id");
					if(id == "grid_thumb_slider" && !$(item).hasClass("x-hide-display")){
						if(!isFirst){
							$(this).addClass("x-hide-display");
							var last_child = $("div.cls_slider").find("tr.x-toolbar-right-row").last();
							var cls_last_child = last_child.find("td.x-toolbar-cell").first().removeClass("x-hide-display");
						}
						isFirst = false;
					}
					if(id == "grid_summary_slider" && !$(item).hasClass("x-hide-display")){
						if(!isFirst){
							$(this).addClass("x-hide-display");
						}
						isFirst = false;
					}
				});
				var active_tab_id = Ext.getCmp('tab_warp').getActiveTab().id;
                var last_child = $("div#"+active_tab_id).find("div.cls_slider").find("tr.x-toolbar-right-row").last();
                
                var thum_view = last_child.find("td.x-toolbar-cell:eq(3)").find("table.x-btn.proxima_btn_customize ");
                var summary_view = last_child.find("td.x-toolbar-cell:eq(4)").find("table.x-btn.proxima_btn_customize ");
                var list_view = last_child.find("td.x-toolbar-cell:eq(5)").find("table.x-btn.proxima_btn_customize ");
                if(!$(thum_view).hasClass("cls_action") && !$(summary_view).hasClass("cls_action") && !$(list_view).hasClass("cls_action")){
                	last_child.find("td.x-toolbar-cell").first().removeClass("x-hide-display");
                	last_child.find("td.x-toolbar-cell:eq(1)").addClass("x-hide-display");
                } else if ($(thum_view).hasClass("cls_action")) {
				    last_child.find("td.x-toolbar-cell").first().removeClass("x-hide-display");
                	last_child.find("td.x-toolbar-cell:eq(1)").addClass("x-hide-display");
				} else if ($(summary_view).hasClass("cls_action")) {
				    last_child.find("td.x-toolbar-cell").first().addClass("x-hide-display");
                	last_child.find("td.x-toolbar-cell:eq(1)").removeClass("x-hide-display");
				} else if ($(list_view).hasClass("cls_action")){
				    last_child.find("td.x-toolbar-cell").first().addClass("x-hide-display");
                	last_child.find("td.x-toolbar-cell:eq(1)").addClass("x-hide-display");
				}
				
			}
		},

		baseParams: {
			ud_content_id: <?=$ud_content_id?>,
			task: 'listing',
			start: 0,
			limit: 35
		},

		fields: [
			{name: 'category_title'},
			{name: 'approval_yn'},
            {name: 'category_id'},
            {name: 'category_full_path'},
			{name: 'content_id'},
			{name: 'status'},
			{name: 'content_status_nm'},
			{name: 'archive_yn'},
			{name: 'reg_user_id'},
			{name: 'user_nm'},
			{name: 'created_date', type:'date', dateFormat: 'YmdHis'},
			{name: 'thumb'},
			{name: 'highres_web_root'},
            {name: 'lowres_web_root'},
            {name: 'thumb_web_root'},
			{name: 'proxy_path'},
			{name: 'ud_content_title'},
			{name: 'bs_content_title'},
			{name: 'bs_content_id'},
			{name: 'ud_content_id'},
			{name: 'title'},
			{name: 'thumb_field'},
			{name: 'summary_field'},
			{name: 'summary'},
			{name: 'qtip'},
			{name: 'children'},
			{name: 'is_group'},
			{name: 'is_cr'},
			{name: 'is_working'},
			{name: 'is_cg_download_denined'},
			{name: 'icons'},
			{name: 'ori_path'},
			{name: 'ori_status'},
			{name: 'review'},
			{name: 'icons_grid'},
			{name: 'icons_download'},
			{name: 'proxy_width'},
			{name: 'ori_size'},
			{name: 'register_type'},
			{name: 'register_type_text'},
			{name: 'checked_qc', type: 'boolean'},
			{name: 'sys_display_size'},
			{name: 'sys_audio_codec'},
			{name: 'sys_video_codec'},
			{name: 'sys_video_rt'},
			{name: 'sys_frame_rate'},
			{name: 'premiere_media_path'}, //시퀀스 관련 파일
			{name: 'seq_id'}, // 시퀀스 ID
			{name: 'highres_path'}, // 고해상도 위치
			{name: 'lowres_root'},
			{name: 'proxy_group_path'},
			{name: 'thumb_group_path'},
			{name: 'highres_mac_path'}, //Hires Mac Path
			{name: 'content_status_color'},
			{name: 'content_status_icon'},
			{name: 'expired_date'},
			{name: 'usr_meta'},
			{name: 'reg_user_nm'},
			{name: 'is_hidden'},
			<?php
			// 2010-11-22 컨테이너일 경우 출력 x and f.type != 'container'
			//보여지는 항목 조정 2011-2-21 by 이성용
			$meta_field_list = $db->queryAll("
									SELECT	*
									FROM	BC_UD_CONTENT T, BC_USR_META_FIELD F
									WHERE	T.UD_CONTENT_ID = F.UD_CONTENT_ID
									AND		F.UD_CONTENT_ID = $ud_content_id
									AND		F.SUMMARY_FIELD_CD IN (4,5,6,7)
									AND		F.USR_META_FIELD_TYPE != 'container'
									ORDER BY F.SHOW_ORDER
							");
            
			if ($meta_field_list) {
				$tmp = array();
				$fields = array();

				foreach($meta_field_list as $meta_field) {
					$meta_field['usr_meta_field_code'] = strtolower('USR_'.$meta_field['usr_meta_field_code']);
					if (in_array($meta_field['usr_meta_field_code'], $tmp)) continue;

					array_push($tmp, $meta_field['usr_meta_field_code']);
					if ($meta_field['usr_meta_field_type'] == 'datefield') {
                        if(strstr($meta_field['usr_meta_field_code'],'_DE')){
                            array_push($fields, "{name: '" . str_replace(' ', '_', $meta_field['usr_meta_field_code']) ."', type:'date', dateFormat: 'Ymd'}\n");					
                        }else{
                            array_push($fields, "{name: '" . str_replace(' ', '_', $meta_field['usr_meta_field_code']) ."', type:'date', dateFormat: 'Ymdhis'}\n");					
                        }
					}
					else {
						array_push($fields, "{name: '" . str_replace(' ', '_', $meta_field['usr_meta_field_code']) . "'}\n");
					}
				}
				echo ', '.implode(', ', $fields);
			}

			if ($meta_field_list) {
				$tmp = array();
				$fields = array();

				foreach($meta_field_list as $meta_field) {
					$meta_field['usr_meta_field_code'] = strtolower($meta_field['usr_meta_field_code']);
					if (in_array($meta_field['usr_meta_field_code'], $tmp)) continue;

					array_push($tmp, $meta_field['usr_meta_field_code']);
					if ($meta_field['usr_meta_field_type'] == 'datefield') {
                        if(strstr($meta_field['usr_meta_field_code'],'_de')){
                            array_push($fields, "{name: '" . str_replace(' ', '_', $meta_field['usr_meta_field_code']) ."', type:'date', dateFormat: 'Ymd'}\n");					
                        }else{
                            array_push($fields, "{name: '" . str_replace(' ', '_', $meta_field['usr_meta_field_code']) ."', type:'date', dateFormat: 'Ymdhis'}\n");					
                        }
					}
					else {
						array_push($fields, "{name: '" . str_replace(' ', '_', $meta_field['usr_meta_field_code']) . "'}\n");
					}
				}
				echo ', '.implode(', ', $fields);
			}
			
			$sys_meta_field_list = $db->queryAll("
									SELECT	*
									FROM	BC_BS_CONTENT T, BC_SYS_META_FIELD F
									WHERE	T.BS_CONTENT_ID = F.BS_CONTENT_ID
									AND		F.BS_CONTENT_ID = $bs_content_id
									ORDER BY F.SHOW_ORDER
								");
			if ($sys_meta_field_list) {
				$tmp = array();
				$sys_fields = array();

				foreach($sys_meta_field_list as $meta_field) {
					$meta_field['sys_meta_field_code'] = strtolower('sys_'.$meta_field['sys_meta_field_code']);
					if (in_array($meta_field['sys_meta_field_code'], $tmp)) continue;

					array_push($tmp, $meta_field['sys_meta_field_code']);
					if ($meta_field['sys_meta_field_type'] == 'datefield') {
						array_push($sys_fields, "{name: '" . str_replace(' ', '_', $meta_field['sys_meta_field_code']) ."', type:'date', dateFormat: 'Ymdhis'}\n");
					}
					else {
						array_push($sys_fields, "{name: '" . str_replace(' ', '_', $meta_field['sys_meta_field_code']) . "'}\n");
					}
				}
				echo ', '.implode(', ', $sys_fields);
			}
			?>
		]
	}),	

	sm: new Ext.grid.CheckboxSelectionModel({
		listeners: {
			selectionchange: function(self, index, record){
				// console.log(self.getSelections());
				if (Ext.isAir) {
					airFunRemoveFilePath('all');

					if (self.getSelections()) {
						Ext.each(self.getSelections(), function(record){
							var root_path ='//Volumes/onlmg/highres/';
							var file = root_path +'/'+ record.get('ori_path');

							airFunAddFilePath(file);
							if (record.get('is_group') == 'G') {
								Ext.each(record.get('children'), function(item) {
									file = root_path + '/' + item['path'];
									airFunAddFilePath(file);
								});
							}
						});
					}
				} // END Ext.isAir
			}
		}
	}),

	cm: new Ext.grid.ColumnModel({
		defaults: {
			sortable: true
		},

		columns: [
			<?php
			if(defined('CUSTOM_ROOT') && class_exists('\ProximaCustom\core\MetadataManager')) {
				$columns = [];
				$cjoFields = \ProximaCustom\core\MetadataManager::makeMetadataShowOrder([], $meta_field_list, $sys_meta_field_list);
				$basicColumns = \ProximaCustom\core\MetadataManager::metadataColumns();
				$customBasicColumns = \ProximaCustom\core\MetadataManager::customMetadataColumns();
                
				$columns = array_merge($columns, $basicColumns);
				if ( !in_array($ud_content_id, $CG_LIST ) ) {
					$columns = array_merge($columns,$customBasicColumns);
				}
				$columns = array_merge($columns,$cjoFields);
				
				$contentColumnOrder = json_decode($user_option['content_column_order'],true);
				
				if(is_null($contentColumnOrder[$ud_content_id])){
					echo implode(', ',$columns);
				}else{
					// 저장한 컬럼 순서가 있을 때
					$storedColumn = [];
					$orderColumns = explode(',', $contentColumnOrder[$ud_content_id]);
					foreach($orderColumns as $orderColumn){
						$storedColumn[] = $columns[$orderColumn];
					};
					echo implode(', ',$storedColumn);
				};
			} else {
				if ($meta_field_list) {
					$tmp = array();
					$fields = array();
					foreach($meta_field_list as $meta_field){
						$meta_field['usr_meta_field_code'] = strtolower('USR_'.$meta_field['usr_meta_field_code']);
						if(in_array($meta_field['usr_meta_field_code'], $tmp)) continue;

						array_push($tmp, $meta_field['usr_meta_field_code']);

						if($meta_field['usr_meta_field_type'] == 'datefield') {
							array_push($fields, "{header: '" . $meta_field['usr_meta_field_title'] . "', dataIndex: '". str_replace(' ', '_', $meta_field['usr_meta_field_code']) ."', renderer: Ext.util.Format.dateRenderer('Y-m-d'), menuDisabled: true,renderer: function(value, metaData, record, rowIndex, colIndex, store) {metaData.css += 'column_content_data';return Ext.util.Format.date(value, 'Y-m-d');}}\n");
						} else {
							array_push($fields, "{header: '" . $meta_field['usr_meta_field_title'] . "', dataIndex: '" . str_replace(' ', '_', $meta_field['usr_meta_field_code']) . "', menuDisabled: true,renderer: function(value, metaData, record, rowIndex, colIndex, store) {metaData.css += 'column_content_data';return value;}}\n");
						}
					}
					echo ', '.implode(', ', $fields);
				}
				if ($sys_meta_field_list) {
					$tmp = array();
					$sys_fields = array();
					foreach($sys_meta_field_list as $meta_field){
						$meta_field['sys_meta_field_code'] = strtolower('SYS_'.$meta_field['sys_meta_field_code']);
						if(in_array($meta_field['sys_meta_field_code'], $tmp)) continue;

						array_push($tmp, $meta_field['sys_meta_field_code']);

						if($meta_field['sys_meta_field_type'] == 'datefield') {
							array_push($sys_fields, "{header: '" . $meta_field['sys_meta_field_title'] . "', dataIndex: '". str_replace(' ', '_', $meta_field['sys_meta_field_code']) ."', renderer: Ext.util.Format.dateRenderer('Y-m-d'), menuDisabled: true,renderer: function(value, metaData, record, rowIndex, colIndex, store) {metaData.css += 'column_content_data';return Ext.util.Format.date(value, 'Y-m-d');}}\n");
						} else {
							array_push($sys_fields, "{header: '" . $meta_field['sys_meta_field_title'] . "', dataIndex: '" . str_replace(' ', '_', $meta_field['sys_meta_field_code']) . "', menuDisabled: true,renderer: function(value, metaData, record, rowIndex, colIndex, store) {metaData.css += 'column_content_data';return value;}}\n");
						}
					}
					echo ', '.implode(', ', $sys_fields);
				}
			}	
			?>
		],
		listeners:{
			columnmoved:function(cm,oldIndex,newIndex){
				var sortOrder = '';
				var totalCount = cm.columns.length-1;
                Ext.each(cm.columns,function(r, i, e){
					if(totalCount == i){
						sortOrder = sortOrder + r.id;
					}else{
						sortOrder = sortOrder + r.id + ',';
					}
				});

				Ext.Ajax.request({
					method:'POST',
					url:'/api/v1/member-option-column-save',
					params:{
						sort_order:sortOrder,
						ud_content_id: <?=$ud_content_id?>
					},
					callback:function(option,success,response){
						var r = Ext.decode(response.responseText);
						if(!r.success){
							Ext.Msg.alert('알림', '컬럼순서 저장에 실패했습니다.');
						}
					}
				});
			}
		}
	})
}
