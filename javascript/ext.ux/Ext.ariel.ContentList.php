<?php 
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
session_start();


require_once($_SERVER['DOCUMENT_ROOT'].'/out.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lang.php');
$user_id = $_SESSION['user']['user_id'];
$user_option = $db->queryRow("
    SELECT  top_menu_mode, slide_thumbnail_size, first_page, slide_summary_size, show_content_subcat_yn
    FROM        bc_member_option
    WHERE   member_id = (
        SELECT  member_id
        FROM        bc_member
        WHERE   user_id =  '".$user_id."' and del_yn='N'
    )
");

/*
2017-12-22 이승수, 제거한 아이콘
    <tr>
        <td>
            <span class="icon fa-stack " style="position:relative;">
                <i class="fa fa-square fa-stack-1x" style="font-size:17px;left:0px;"></i>
                <i class="fa fa-align-right fa-rotate-90 fa-stack-1x fa-inverse" style="position:relative;top:0px;left:0px;font-size:10px;color:#f38889;"></i>
            </span>
        </td>
        <td class="textCon" style=\ "padding: 0 0 0 5px;width:230px;\">'._text('MN02277').'</td>
        <td>
            <span class="icon fa-stack " style="position:relative;">
                <i class="fa fa-square fa-stack-1x" style="font-size:17px;left:0px;"></i>
                <i class="fa fa-align-right fa-rotate-90 fa-stack-1x fa-inverse" style="position:relative;top:0px;left:0px;font-size:10px;"></i>
            </span>
        </td>
        <td class="textCon" style=\ "padding: 0 0 0 5px;width:230px;\">'._text('MN02278').'</td>
    </tr>
    <tr>
        <td>
            <span class=\ "icon fa-stack \" style=\ "position:relative;\">
                <i class=\ "fa fa-square fa-stack-1x\" style=\ "font-size:17px;left:0px;\"></i>
                <i class=\ "fa fa-server fa-stack-1x fa-inverse\" style=\
                    "position:relative;top:0px;left:0px;font-size:10px;\"></i>
            </span>
        </td>
        <td class="textCon" style=\ "padding: 0 0 0 5px;width:230px;\">'._text('MN01056').'</td>
        <td>
            <span class="icon fa-stack " style="position:relative;">
                <i class="fa fa-square fa-stack-1x" style="font-size:17px;left:0px;"></i>
                <i class="fa fa-server fa-stack-1x fa-inverse" style="position:relative;top:0px;left:0px;font-size:10px;color:#91caf5;"></i>
            </span>
        </td>
        <td class="textCon" style=\ "padding: 0 0 0 5px;width:230px;\">'._text('MN02384').'</td>
    </tr>
    <tr>
        <td>
            <span class="icon fa-stack " style="position:relative;">
                <i class="fa fa-square fa-stack-1x" style="font-size:17px;left:0px;"></i>
                <i class="fa fa-facebook-square fa-stack-1x fa-inverse" style="position:relative;top:0px;left:0px;font-size:10px;"></i>
            </span>
        </td>
        <td class="textCon" style=\ "padding: 0 0 0 5px;width:230px;\">Facebook</td>
        <td>
            <span class="icon fa-stack " style="position:relative;">
                <i class="fa fa-square fa-stack-1x" style="font-size:17px;left:0px;"></i>
                <i class="fa fa-twitter fa-stack-1x fa-inverse" style="position:relative;top:0px;left:0px;font-size:10px;"></i>
            </span>
        </td>
        <td class="textCon" style=\ "padding: 0 0 0 5px;width:230px;\">Twitter</td>
    </tr>
    <tr>
        <td>
            <span class="icon fa-stack " style="position:relative;">
                <i class="fa fa-square fa-stack-1x" style="font-size:17px;left:0px;"></i>
                <i class="fa fa-twitter fa-stack-1x fa-inverse" style="position:relative;top:0px;left:0px;font-size:10px;"></i>
            </span>
        </td>
        <td class="textCon" style=\ "padding: 0 0 0 5px;width:230px;\">Youtube</td>
        <td>
            <span class="icon fa-stack " style="position:relative;">
                <i class="fa fa-square fa-stack-1x" style="font-size:17px;left:0px;"></i>
                <strong class="fa fa-text fa-stack-1x fa-inverse" style="top:0px;left:0px;font-size:8px;font-weight:bold;color:#8fc7f1;">QC</strong>
            </span>
        </td>
        <td class="textCon" style=\ "padding: 0 0 0 5px;width:230px;\">'._text('MN02294').'('._text('MN00262').')</td>
    </tr>
    <tr>
        <td>
            <span class="icon fa-stack " style="position:relative;">
                <i class="fa fa-square fa-stack-1x" style="font-size:17px;left:0px;"></i>
                <strong class="fa fa-text fa-stack-1x fa-inverse" style="top:0px;left:0px;font-size:8px;font-weight:bold;color:#f38889;">QC</strong>
            </span>
        </td>
        <td class="textCon" style=\ "padding: 0 0 0 5px;width:230px;\">'._text('MN02294').'('._text('MN02346').')</td>
        <td>
            <span class="icon fa-stack " style="position:relative;">
                <i class="fa fa-square fa-stack-1x" style="font-size:17px;left:0px;"></i>
                <strong class="fa fa-text fa-stack-1x fa-inverse" style="top:0px;left:0px;font-size:8px;font-weight:bold;">QC</strong>
            </span>
        </td>
        <td class="textCon" style=\ "padding: 0 0 0 5px;width:230px;\">'._text('MN02294').'('._text('MN02178').')</td>
    </tr>
*/
$icon_help_top = '<table cellpadding=\ "2\" cellspacing=\ "5\" border=\ "0\" style=\
"padding-top: 10px;padding-left: 10px;background-color:#333337;\">
<tbody>
	<tr>
	<td style=\ "position:relative;\">
		<span class="icon fa-stack " style="position:relative;">
			<i class=" fa fa-square fa-stack-1x" style="font-size:17px;left:0px;"></i>
			<strong class="fa fa-inverse fa-text fa-stack-1x" style="top:0px;left:0px;font-size:8px;font-weight:bold;">UHD</strong>
		</span>
	</td>
	<td class="textCon" style=\ "padding: 0 0 0 5px;width:230px;\">'.'해상도 3840x2160 UHD'.'</td>
	<td style=\ "position:relative;\">
		<span class="icon fa-stack " style="position:relative;">
			<i class="fa fa-square fa-stack-1x" style="font-size:17px;left:0px;"></i>
			<strong class="fa fa-inverse fa-text fa-stack-1x" style="top:0px;left:0px;font-size:8px;font-weight:bold;">MP4</strong>
		</span>
	</td>
	<td class="textCon" style=\ "padding: 0 0 0 5px;width:230px;\">'.'확장자 MP4'.'</td>
	</tr>
    <tr>
        <td style=\ "position:relative;\">
            <span class="icon fa-stack " style="position:relative;">
                <i class=" fa fa-square fa-stack-1x" style="font-size:17px;left:0px;"></i>
                <strong class="fa fa-inverse fa-text fa-stack-1x" style="top:0px;left:0px;font-size:8px;font-weight:bold;">HD</strong>
            </span>
        </td>
        <td class="textCon" style=\ "padding: 0 0 0 5px;width:230px;\">'.'1920x1080 XDCAM,DVCPRO HD'.'</td>
        <td style=\ "position:relative;\">
            <span class="icon fa-stack " style="position:relative;">
                <i class="fa fa-square fa-stack-1x" style="font-size:17px;left:0px;"></i>
                <strong class="fa fa-inverse fa-text fa-stack-1x" style="top:0px;left:0px;font-size:8px;font-weight:bold;">SD</strong>
            </span>
        </td>
        <td class="textCon" style=\ "padding: 0 0 0 5px;width:230px;\">'.'720x512 XDCAM,DVCPRO SD'.'</td>
    </tr>
    <tr>
	<td style=\ "position:relative;\">
		<span class="icon fa-stack " style="position:relative;">
			<i class=" fa fa-square fa-stack-1x" style="font-size:17px;left:0px;"></i>
			<strong class="fa fa-inverse fa-text fa-stack-1x" style="top:0px;left:0px;font-size:8px;font-weight:bold;">ETC</strong>
		</span>
	</td>
	<td class="textCon" style=\ "padding: 0 0 0 5px;width:230px;\">'.'그외 정의되지 않은 포맷'.'</td>
	<td style=\ "position:relative;\">

	</td>
	<td class="textCon" style=\ "padding: 0 0 0 5px;width:230px;\">'.'</td>
	</tr>
    <tr>
        <td style=\ "position:relative;\">
        <span class="icon fa-stack " style="position:relative;">
                <i class="fa fa-square fa-stack-1x" style="font-size:17px;left:0px;"></i>
                <strong class="fa fa-inverse fa-text fa-stack-1x" style="top:0px;left:0px;font-size:8px;font-weight:bold;">O</strong>
            </span>
        </td>
        <td class="textCon" style=\ "padding: 0 0 0 5px;width:230px;\">'.'온라인 영상'.'</td>
        <td style=\ "position:relative;\">
        <span class="icon fa-stack " style="position:relative;">
                <i class="fa fa-square fa-stack-1x" style="font-size:17px;left:0px;"></i>
                <strong class="fa fa-inverse fa-text fa-stack-1x" style="top:0px;left:0px;font-size:8px;font-weight:bold;">R</strong>
            </span>
        </td>
        <td class="textCon" style=\ "padding: 0 0 0 5px;width:230px;\">'.'리스토어 영상'.'</td>
    </tr>
    <tr>
    <td style=\ "position:relative;\">
    <span class="icon fa-stack " style="position:relative;">
        <i class="fa fa-square fa-stack-1x" style="font-size:17px;left:0px;"></i>
        <strong class="fa fa-inverse fa-text fa-stack-1x" style="top:0px;left:0px;font-size:8px;font-weight:bold;">A</strong>
    </span>
	</td>
	<td class="textCon" style=\ "padding: 0 0 0 5px;width:230px;\">'.'아카이브 영상'.'</td>
	<td>
	<span>
		<i class=\ "icon1 fa fa-circle-o\" style=\ "font-size:17px;\"></i>
	</span>
	</td>
	<td class="textCon" style=\ "padding: 0 0 0 5px;width:230px;\">'.'즐겨찾기 기능'.'</td>
    </tr>
    <tr>
		<td style=\ "position:relative;\">
			<span class="icon fa-stack " style="position:relative;color:green\">
				<i class=" fa fa-square fa-stack-1x" style="font-size:17px;left:0px;"></i>
				<strong class="fa fa-inverse fa-text fa-stack-1x" style="top:0px;left:0px;font-size:8px;font-weight:bold;">L</strong>
			</span>
		</td>
		<td class="textCon" style=\ "padding: 0 0 0 5px;width:230px;\">'._text('MN02242').' 정상'.'</td>
		<td style=\ "position:relative;\">
            <span class="icon fa-stack " style="position:relative;color:red\">
                <i class=" fa fa-square fa-stack-1x" style="font-size:17px;left:0px;"></i>
                <strong class="fa fa-inverse fa-text fa-stack-1x" style="top:0px;left:0px;font-size:8px;font-weight:bold;">L</strong>
            </span>
        </td>
        <td class="textCon" style=\ "padding: 0 0 0 5px;width:230px;\">'._text('MN02242').' 오류'.'</td>
	</tr>
	<tr>
        <td>
            <span class=\ "icon fa-stack\" style=\ "position:relative;color:green\">
                <i class=\ "fa fa-square fa-stack-1x\" style=\ "font-size:17px;\"></i>
                <i class=\ "fa fa fa-check fa-stack-1x fa-inverse\" style=\
                    "position:relative;top:0px;left:0px;font-size:10px;\"></i>
            </span>
        </td>
        <td class="textCon" style=\ "padding: 0 0 0 5px;width:230px;\">'.'등록 승인'.'</td>
        <td>
            <span class="icon fa-stack " style="position:relative;">
                <i class="fa fa-square fa-stack-1x" style="font-size:17px;color:red"></i>
                <i class="fa fa-ban fa-stack-1x fa-inverse" style="position:relative;top:0px;left:0px;font-size:10px;"></i>
            </span>
        </td>
        <td class="textCon" style=\ "padding: 0 0 0 5px;width:230px;\">'.'등록 반려'.'</td>
	</tr>
	<tr>
        <td style=\ "position:relative;\">
            <span class="icon fa-stack " style="position:relative;color:green">
                <i class=" fa fa-square fa-stack-1x" style="font-size:17px;left:0px;"></i>
                <strong class="fa fa-inverse fa-text fa-stack-1x" style="top:0px;left:0px;font-size:8px;font-weight:bold;">QC</strong>
            </span>
        </td>
        <td class="textCon" style=\ "padding: 0 0 0 5px;width:230px;\">'.'QC 정상'.'</td>
        <td style=\ "position:relative;\">
            <span class="icon fa-stack " style="position:relative;color:red">
                <i class="fa fa-square fa-stack-1x" style="font-size:17px;left:0px;"></i>
                <strong class="fa fa-inverse fa-text fa-stack-1x" style="top:0px;left:0px;font-size:8px;font-weight:bold;">QC</strong>
            </span>
        </td>
        <td class="textCon" style=\ "padding: 0 0 0 5px;width:230px;\">'._text('MN02290').'</td>
    </tr>
</tbody>
</table>';

$icon_help_bottom = '<table cellpadding=\"2\" cellspacing=\"5\" border=\"0\" style=\"padding-left: 10px;background-color:#333337;\">
    <tbody>
        <tr>
            <td>
                <span class="icon fa-stack" style="position:relative;">
                    <i class="fa fa-square fa-stack-1x" style="font-size:17px;left:0px;">
                        <i class="fa fa-square-o fa-stack-1x fa-inverse" style="position:absolute;top:2.5px;font-size: 10px;padding-left:0px;"></i>
                        <i class="fa fa-external-link-square fa-stack-1x fa-inverse" style="position:absolute;bottom:2px;left:2px;font-size: 8px;"></i>
                    </i>
                </span>
            </td>
            <td class="textCon" style=\"padding: 0 0 0 5px;width:230px;\">'._text('MN02342').'</td>
            <td>
                <span class="icon fa-stack" style="position:relative;">
                    <i class="fa fa-square fa-stack-1x" style="font-size:17px;left:0px;"></i>
                    <i class="fa fa-tasks fa-stack-1x fa-inverse" style="position:relative;top:0px;left:0px;font-size:10px;"></i>
                </span>
            </td>
            <td class="textCon" style=\"padding: 0 0 0 5px;width:230px;\">'._text('MN00241').'</td>
        </tr>
        <tr>
            <td>
                <span class="icon fa-stack" style="position:relative;">
                <i class="fa fa-square fa-stack-1x" style="font-size:17px;left:0px;"></i>
                <i class="fa fa-picture-o fa-stack-1x fa-inverse" style="position:relative;top:0px;left:0px;font-size:10px;"></i>
                </span>
            </td>
            <td class="textCon" style=\"padding: 0 0 0 5px;width:230px;\">'._text('MN02343').'</td>
            <td>
                <span class="icon fa-stack" style="position:relative;">
                    <i class="fa fa-square fa-stack-1x" style="font-size:17px;left:0px;"></i>
                    <i class="fa fa-align-justify fa-stack-1x fa-inverse" style="position:relative;top:0px;left:0px;font-size:10px;"></i>
                </span>
            </td>
            <td class="textCon" style=\"padding: 0 0 0 5px;width:230px;\">'._text('MN02410').'</td>
        </tr>
    </tbody></table>';
	$icon_help_top = str_replace("\r\n", "", $icon_help_top);
	$icon_help_top = str_replace("\n", "", $icon_help_top);
	$icon_help_bottom = str_replace("\r\n", "", $icon_help_bottom);
	$icon_help_bottom = str_replace("\n", "", $icon_help_bottom);

if(defined('CUSTOM_ROOT') && class_exists('\ProximaCustom\core\ContentListAction')) {
	\ProximaCustom\core\ContentListAction::renderCustomFunctions();				
}
?>
Ext.ns('Ariel');
Ext.ns('Ext.ariel');
Ext.QuickTips.init();
Ext.QuickTips.init=Ext.QuickTips.init.createSequence(function(autoRender) {
	Ext.QuickTips.getQuickTip().el.disableShadow();
});


//Ext.ToolTip.prototype.onTargetOver =
//    	Ext.ToolTip.prototype.onTargetOver.createInterceptor(function(e) {
//    		this.baseTarget = e.getTarget();
//    	});
//Ext.ToolTip.prototype.onMouseMove =
//    	Ext.ToolTip.prototype.onMouseMove.createInterceptor(function(e) {
//    		if (!e.within(this.baseTarget)) {
//    			this.onTargetOver(e);
//    			return false;
//    		}
//    	});
Ext.ariel.ContentList = Ext.extend(Ext.grid.GridPanel, {
	//loadMask: true,
//	collapsible: true,  //사용자의 요구에 따라 주석처리 2011-02-24 by 이성용
//	enableDragDrop: true,
	ddGroup: 'ContentDD',
	stripeRows: true,
	anchor : '97%',
	dragZone: null,
	//mode: Ext.util.Cookies.get('last_view_mode') ?? 'thumb' ,
	mode: 'thumb',
	// 2018.02.13 khk
	// 썸네일 모드일 때 현재 상태를 저장해 놓는 객체
	currentThumbModeInfo: {
		bodyWidthStyle: '',
		scrollTop: 0,
	},
	// 썸네일 모드일 때 스크롤위치를 복원시켜주는 함수
	restoreScroll: function() {
		//console.log('@@@selections(restoreScroll)!!!', this.getSelectionModel().selections.length);	
		//console.log('@@@scrollTop(restoreScroll)!!!', this.getView().scroller.dom.scrollTop);	
		//console.log('@@@scrollLeft(restoreScroll)!!!', this.getView().scroller.dom.scrollLeft);	
		//console.log('@@@currentThumbModeInfo.scrollTop(restoreScroll)!!!', this.currentThumbModeInfo.scrollTop);
		if( (this.mode == 'thumb' || this.mode == 'mix') && 
			this.currentThumbModeInfo.bodyWidthStyle == '100%' &&
			this.getView().scroller.dom.scrollTop != this.currentThumbModeInfo.scrollTop &&
			this.getSelectionModel().selections.length > 0 ) {
			//console.log('restoreScroll!!!');			
			this.getView().scroller.dom.scrollTop = this.currentThumbModeInfo.scrollTop;
		}
	},
    //cls : 'x-portlet',
	//plugins: new Ext.ux.grid.DragSelector(),
	template: {
		//리스트+섬네일보기
		list: new Ext.XTemplate(
			'<div class="x-grid3-row ux-explorerview-detailed-icon-row">',
				'<table class="x-grid3-row-table">',
					'<tbody>',
						'<tr>',
							'<td class="x-grid3-col x-grid3-cell ux-explorerview-icon" style="vertical-align:middle" align="center">',
							'<img src="/Ariel.WEB_PATH/{thumb}" ext:qtip="<div><img src=\'/Ariel.WEB_PATH/{thumb}\'><br/>{summary_field}</div>" ',
								'<tpl if="this.gtWidth(thumb) === 1"> width=Ariel.list_width </tpl>',
								'<tpl if="this.gtWidth(thumb) === -1"> height=Ariel.list_height </tpl>',
							'></td>',
							'<td class="x-grid3-col x-grid3-cell">',
								'<div class="x-grid3-cell-inner" unselectable="on" style=" ">{summary_field}</div>',
							'</td>',
						'</tr>',
					'</tbody>',
				'</table>',
			'</div>',
			{
			/*
				function return이 1인 경우 가로가 비율에 비하여 크며
				return이 -1인 경우 세로가 비율에 비하여 크며
				return이 0인 경우 이미지 가로,세로 중 하나 이상이 0인 잘못된 데이터
			*/
				gtWidth: function(url, w){
					var imgObj = new Image();
					imgObj.src = '/Ariel.WEB_PATH/' + url;

					if (imgObj.width == 0 || imgObj.height == 0) return 0;
					if ( (imgObj.width/Ariel.list_width) > (imgObj.height/Ariel.list_height) )
					{
						return 1;
					}
					else
					{
						return -1;
					}
				}
			}
		),
		//섬네일보기
		tile: new Ext.XTemplate(
			'<div class="x-grid3-row ux-explorerview-large-icon-row">',
				'<table class="x-grid3-row-table">',
					'<tbody>',
						'<tr height=Ariel.thumb_height>',
							'<td class="x-grid3-col x-grid3-cell ux-explorerview-icon" style=" vertical-align:middle" align="center">',
							'<img id="imagelist" src="/Ariel.WEB_PATH/{thumb}" ext:qtip="<img src=\'/Ariel.WEB_PATH/{thumb}\'><br/>제목:{title}</p>메타타입:{table_name}</p>카테고리:{category}</p>" style="border: 1px solid #000000"',
								'<tpl if="this.gtWidth(thumb) === 1"> width=Ariel.thumb_width </tpl>',
								'<tpl if="this.gtWidth(thumb) === -1"> height=Ariel.thumb_height </tpl>',
							'></td>',
						'</tr>',
						'<tr>',
							'<td class="x-grid3-col x-grid3-cell"><div class="x-grid3-cell-inner" unselectable="on">{thumb_field}</div></td></tr>',
					'</tbody>',
				'</table>',
			'</div>',
			{
				gtWidth: function(url){
					var imgObj = new Image();
					imgObj.src = '/Ariel.WEB_PATH/' + url;

					if (imgObj.width == 0 || imgObj.height == 0) return 0;
					if ( (imgObj.width/Ariel.thumb_width) > (imgObj.height/Ariel.thumb_height) )
					{
						return 1;
					}
					else
					{
						return -1;
					}
				}
			}
		)
	},
	toolTemplate: new Ext.XTemplate(
        '<tpl for=".">',
            '<div class="x-toolleft x-tool-{id}" >&#160;</div>',
        '</tpl>'
    ),
	listeners: {
		beforerender: function(p) {

		},
		render: function(p){

			new Ext.Resizable(p.getEl(), {
				handles: 's',
				minHeight: 150,
				pinned: true,
				resizeElement: function(){
					var box = this.proxy.getBox();
					p.updateBox(box);
					if(p.layout){
						p.doLayout();
					}

					return box;
				}
			});

			self.dragZone = new Ext.grid.GridDragZone(p, {
				ddGroup: 'ContentDD',
				getDragData: function(grid, ddel, rowIndex, selections){
					//alert(rowIndex);
				}
			});
		},
		afterrender: function (comp, e) {
			
			var el = Ext.getCmp('tab_warp').getActiveTab().get(0);
			el.on('click', function (event) {

				if(event.target.className === 'fa fa-circle test_tag_function' || event.target.className === 'fa fa-circle-o test_tag_function'){
					elem = event.target;
					var sm = Ext.getCmp('tab_warp').getActiveTab().get(0).getSelectionModel();
					var content_id_data = elem.getAttribute('text_data');
					var list_content_data = Ext.getCmp('tab_warp').getActiveTab().get(0).getStore();
					var list_content = list_content_data.data.items;

					if(sm.hasSelection()){
						var selection = sm.getSelections();
						var content_id_array = [];
						Ext.each(selection, function(r, i, a){
							content_id_array.push({
								content_id: r.get('content_id')
							});
						});

						var isExists = false;
						Ext.each(content_id_array, function(r, i, a){
							if(r.content_id == content_id_data){
								isExists = true;
							}
						});

						if(isExists == false){
							for(i = 0; i<list_content.length;i++){
								if(list_content[i].id == content_id_data){
									sm.selectRow(i);
									break;
								}
							}
						}

					}else{
						
						for(i = 0; i<list_content.length;i++){
							
							if(list_content[i].id == content_id_data){
								sm.selectRow(i);
								break;
							}
						}
					}
								
					if( !Ext.isEmpty(Ariel.tag_menu_list) ) {
						Ariel.tag_menu_list.showAt(event.getXY());
						return;
					}

				}
			});
        },
		rowclick: function(self, i, e){
			current_focus = self;
			/*
			*	전체 로우 선택시 deselect 가 안되는 문제 수정
			*	shift 또는 ctrl이 눌리지 않는 경우에는 선택한 로우를 셀렉트하여 나머지는 deselect 처리함 - 2018.1.9 Alex
			*/
			var sm = self.getSelectionModel();
			if (sm.hasSelection() && !e.shiftKey && !e.ctrlKey) {
				sm.selectRow(i);
			}

			/*
			* 현재화면에서 선택된 ROW개수를 PagingToolbar에 업데이트 하도록 수정 - 2018.01.11 Alex
			*/
			var selectedRowCount = sm.getCount();
			var selectedCountField = self.getBottomToolbar( ).find('itemId', 'selectedCount')[0];

            //MN01996 개 선택됨
			selectedCountField.setValue(selectedRowCount + _text('MN01996'));

		},
		rowdblclick: function(self, rowIndex, e){
			
			if(player_windown_flag)
			{
				return;
			}
			var sm = self.getSelectionModel().getSelected();

			var content_id = sm.get('content_id');

			<?php
			if(defined('CUSTOM_ROOT') && class_exists('\ProximaCustom\core\ContentListAction')) {
				echo 'beforeShowDetailPanelAction(sm);';				
			}
			?>

			var win_type; 

			if( this.id == 'contentgrid_westtab_video' || this.id == 'contentgrid_westtab_graphic' ){
				win_type = 'zodiac';
			}

      var params = {
                        content_id: content_id,
                        record: Ext.encode(sm.json),
                        win_type : win_type,
                        targetGrid: 'tab_warp'
                    };
      openDetailWindowWithParams(self, params);
		},
		keypress: function(e) {
			e.preventDefault(); 
			var hasSelection = Ext.getCmp('tab_warp').getActiveTab().get(0).getSelectionModel().hasSelection();
			if(hasSelection && e.getKey() == e.SPACE) {
				var selection = Ext.getCmp('tab_warp').getActiveTab().get(0).getSelectionModel().getSelected();
				Ext.Ajax.request({
					url:'/store/cuesheet/player_window.php',
					params:{
						content_id: selection.get('content_id')
					},
					callback:function(option,success,response) {
						var r = Ext.decode(response.responseText);
						if(success) {
							r.show();
							var player3 = videojs(document.getElementById('player3'), {}, function(){});
						} else {
							Ext.Msg.alert( _text('MN01098'), _text('MSG02052'));
							return;
						}
					}
				});
			}
		}
	},

	sendAction: function(action, records, self) {
        if (action == 'delete' || action == 'delete_hr' || action == 'forceDelete' ) {
			//>> var w = Ext.Msg.wait('삭제 요청중...');
			var w = Ext.Msg.wait(_text('MSG00144'));
			Ext.Ajax.request({
				url: '/store/delete_contents.php',
				params: {
					action: action,
					content_id: Ext.encode(records)
				},
				callback: function(opts, success, response){
					w.hide();
					if (success) {
						try {
							var r = Ext.decode(response.responseText);
							if ( ! r.success) {
								//>>Ext.Msg.alert('알림', '삭제 권한이 없습니다.');
								Ext.Msg.alert(_text('MN00023'), r.msg);
								return;
							}
							self.store.reload();
							// if (Ext.getCmp('topic-tree')) {
							// 	var root = Ext.getCmp('topic-tree').getRootNode();
							// 	Ext.getCmp('topic-tree').getLoader().load(root);
							// }
						} catch (e) {
							Ext.Msg.alert(e['name'], e['message']);
						}
					}else{
						//>>Ext.Msg.alert('오류', response.statusText);
						Ext.Msg.alert(_text('MN00022'), response.statusText);
					}
				}
			});
		} else if (action == 'delete_proxy') {
			//>> var w = Ext.Msg.wait('삭제 요청중...');
			var w = Ext.Msg.wait(_text('MSG00144'));

			Ext.Ajax.request({
				url: '/store/delete_contents.php',
				params: {
					action: action,
					content_id: Ext.encode(records)
				},
				callback: function(opts, success, response){
					w.hide();
					if(success){
						try{
							var r = Ext.decode(response.responseText);
							if(!r.success){
								//>>Ext.Msg.alert('알림', '삭제 권한이 없습니다.');
								Ext.Msg.alert(_text('MN00023'), r.msg);
								return;
							}else{
								//Ext.Msg.alert(_text('MN00023'), r.msg);
							}
							self.store.reload();
						}
						catch (e)
						{
							Ext.Msg.alert(e['name'], e['message']);
						}
					}else{
						//>>Ext.Msg.alert('오류', response.statusText);
						Ext.Msg.alert(_text('MN00022'), response.statusText);
					}
				}
			});
		}
	},

	initComponent: function(config){
		//var lastMode = Ext.util.Cookies.get('last_view_mode');
		var rowTemplateValue = this.template.tile;
		//if(lastMode === 'list') {
		//	rowTemplateValue = null;
		//} else if(lastMode === 'mix') {
		//	rowTemplateValue = this.template.list;
		//}
		this.bbar = this.buildPageToolBar();
		this.viewConfig = {
			// refresh 후 스크롤바 현재위치에 고정
            scrollToTop: Ext.emptyFn,
			rowTemplate: this.template.tile,
			//>>emptyText: '등록된 자료가 없습니다',
			emptyText: _text('MSG00142'),
			enableRowBody: true,
			forceFit: false,
			showPreview: false,			
			listeners: {
				//refresh전에 헤더를 없애고
				//refresh후에 바디 사이즈를 조절한다.
				beforerefresh: function(self){
					//console.log(this.grid.mode);
					var gridHead = this.grid.getGridEl().child('div[class=x-grid3-header]');
					var gridBody = this.grid.getGridEl().child('div[class=x-grid3-body]');
					// 2018.02.13 khk 썸네일 모드일 때 새로고침이 진행될 경우 스크롤 위치를 저장시켜놨다가 100%로
					// 스타일 변경 후 이동 시켜준다. 근데 검색 등 새로고침이 아닐때는 맨 위로 가도록 한다.
					this.grid.currentThumbModeInfo.bodyWidthStyle = gridBody.getStyle('width');
					this.grid.currentThumbModeInfo.scrollTop = this.grid.getView().scroller.dom.scrollTop;
					switch(this.grid.mode)
					{
						case 'thumb':
						case 'mix':
							gridHead.setStyle('display', 'none');							
							break;
						case 'list':
							gridHead.setStyle('display', 'block');																					
							break;
					}
				},
				refresh: function(self){
					//console.log(this.grid.mode);
					var gridBody = this.grid.getGridEl().child('div[class=x-grid3-body]');											
					switch(this.grid.mode)
					{
						case 'thumb':
						case 'mix':							
							gridBody.setStyle('width', '100%');									
							break;
						case 'list':									
							break;
					}
				}
			}
		};

		Ext.apply(this, config || {});

		Ext.ariel.ContentList.superclass.initComponent.call(this);

		this.store.on('exception', function(self, type, action, opts, response, args){
			if (type == 'response')
			{
				var result = Ext.decode(response.responseText);
				if (!result.success)
				{
					Ext.Msg.alert(_text('MN00024'), result.msg);
				}
			}
		});
		
		var contentListGrid = this;
		this.store.on('load', function(self, records, options){
			//console.log('@@@loaded!!!', contentListGrid);			
			//console.log('@@@selections!!!', contentListGrid.getSelectionModel().selections.length);	
			//console.log('@@@scrollTop!!!', contentListGrid.getView().scroller.dom.scrollTop);	
			//console.log('@@@scrollLeft!!!', contentListGrid.getView().scroller.dom.scrollLeft);	
			// 2018.02.13 khk refresh 후 선택된것이 있으면 가로 스크롤만 맨 앞으로 이동시키고			
			// 가로 스크롤은 리스트 모드일 경우만...
			if( contentListGrid.mode == 'list' &&
				contentListGrid.getView().scroller.dom.scrollLeft != 0) {
				contentListGrid.getView().scroller.dom.scrollLeft = 0;				
			}	
			// 선택된게 없으면 세로 스크롤도 맨 위로 이동시킴
			if(contentListGrid.getSelectionModel().selections.length == 0 &&
				contentListGrid.getView().scroller.dom.scrollTop != 0) {
				contentListGrid.getView().scroller.dom.scrollTop = 0;				
			} else {
				// 스크롤 위치 복원 시켜줌
				contentListGrid.restoreScroll();				
			}			
		});
	},

	buildPageToolBar: function(){
		var _this = this;
		return new Ext.PagingToolbar({
			pageSize: 35,
			cls:'cls_slider content_page_toolbar',
			store: this.store,
			buttonAlign: 'center',
			plugins: [
				new Ext.ux.grid.PageSizer({cls:'content_page_toolbar_number_per_page'})
			],
			listeners: {
				afterrender: function(self){
					self.remove(10);
					
					self.add({
						xtype: 'tbspacer',
						width: '20'
					});

					self.add({
						xtype:'displayfield',
						itemId: 'selectedCount',
						value: '0 '+_text('MN01996')//MN01996 개 선택됨
					});
					
					self.add('->');

					// self.add(thumbSlider);
                    self.add({                       
                        xtype: 'slider',
                        minValue: 0,
                        maxValue: 100,
                        value: 0,
                        id: 'grid_thumb_slider',
                        width:150,
                        increment: 1,
                        stateful: true,
                        listeners: {
                            change: function(e,nv,ov) {
                                var x = $(".x-grid3-row.ux-explorerview-large-icon-row");
                                //var ad_width = (280 -(100-nv));

                                var range_start = 150;
                                var range_end = 380;
                                var h_range_start = 85;
                                var h_range_end = 214;
                                var ad_width = (range_start +(range_end- range_start)*nv/100);
                                var ad_height = (h_range_start +(h_range_end- h_range_start)*nv/100);

                                for(var i=0;i<x.length;i++)
                                {
                                    $(x[i]).height("100%");
                                    $(x[i]).width(ad_width+'px');
                                }

                                var height = parseInt((ad_width/16)*10);

                                $(".thumb_img").css("max-width",(ad_width-7)+"px");
                                $(".thumb_img").css("max-height",ad_height+"px");
                                $(".thumb_img_box").css("width",ad_width+"px");
                                $(".thumb_img_box").css("height",ad_height+"px");


                            },
                            changecomplete: function( slider, newValue, thumb ){                               
                                Ext.Ajax.request({
                                    url: '/store/user/user_option/slide_thumb_size.php',
                                    params: {
                                        slide_thumb_value: newValue
                                    },
                                    callback: function(options, success, response) {
                                    }
                                });                              
                            },
                            render: function(self){
                                // self.setValue(<?=$user_option['slide_thumbnail_size']?>);
                            }
                        },
                        change_image_size: function(){
                            var x = $(".x-grid3-row.ux-explorerview-large-icon-row");
                            var nv = Ext.getCmp('grid_thumb_slider').getValue();
                            var range_start = 150;
                            var range_end = 380;
                            var h_range_start = 85;
                            var h_range_end = 214;
                            var ad_width = (range_start +(range_end- range_start)*nv/100);
                            var ad_height = (h_range_start +(h_range_end- h_range_start)*nv/100);

                            for(var i=0;i<x.length;i++)
                            {
                                $(x[i]).height("100%");
                                $(x[i]).width(ad_width+'px');
                            }

                            var height = parseInt((ad_width/16)*10);

                            $(".thumb_img").css("max-width",(ad_width-7)+"px");
                            $(".thumb_img").css("max-height",ad_height+"px");
                            $(".thumb_img_box").css("width",ad_width+"px");
                            $(".thumb_img_box").css("height",ad_height+"px");
                        }
                    });
					// self.add(summarySlider);
                    self.add({
                        xtype: 'slider',
                        minValue: 0,
                        maxValue: 100,
                        id: 'grid_summary_slider',
                        width:150,
                        increment: 1,
                        stateful: true,
                        listeners: {
                            change: function(e,nv,ov) {
                                var x = $(".x-grid3-row.ux-explorerview-detailed-icon-row");
                                //var ad_width = (280 -(100-nv));

                                var range_start = 312;
                                var range_end = 624;
                                
                                var h_range_start = 85;
                                var h_range_end = 214;
                                var ad_width = (range_start +(range_end- range_start)*nv/100);
                                var ad_height = (h_range_start +(h_range_end- h_range_start)*nv/100);

                                for(var i=0;i<x.length;i++){
                                    $(x[i]).height("100%");
                                    $(x[i]).width(ad_width+'px');
                                }

                                var height = parseInt((ad_width/16)*10);

                                $(".thumb_img").css("max-width",(ad_width-7)+"px");
                                $(".thumb_img").css("max-height",ad_height+"px");
                                $(".thumb_img_box").css("width",ad_width+"px");
                                $(".thumb_img_box").css("height",ad_height+"px");


                            },
                            changecomplete: function( slider, newValue, thumb ){
                                Ext.Ajax.request({
                                    url: '/store/user/user_option/slide_summary_size.php',
                                    params: {
                                        slide_summary_value: newValue
                                    },
                                    callback: function(options, success, response) {
                                    }
                                });
                            },
                            render: function(self){
                                //self.setValue(0);
                                self.setValue(<?=$user_option['slide_summary_size']?>);

                            }
                        },
                        change_image_size: function(){

                            var x = $(".x-grid3-row.ux-explorerview-detailed-icon-row");
                            var nv = Ext.getCmp('grid_summary_slider').getValue();
                            //var ad_width = (280 -(100-nv));
                            var range_start = 312;
                            var range_end = 624;

                            // var h_range_start = 85;
                            // var h_range_end = 214;
                            var ad_width = (range_start +(range_end- range_start)*nv/100);
                            // var ad_height = (h_range_start +(h_range_end- h_range_start)*nv/100);

                            for(var i=0;i<x.length;i++){
                                // $(x[i]).height("100%");
                                $(x[i]).width(ad_width+'px');
                            }

                            // var height = parseInt((ad_width/16)*10);

                            // $(".thumb_img").css("max-width",(ad_width-7)+"px");
                            // $(".thumb_img").css("max-height",ad_height+"px");
                            // $(".thumb_img_box").css("width",ad_width+"px");
                            // $(".thumb_img_box").css("height",ad_height+"px");
                        }
                    });

					//컬럼순번 저장 하기
					
					self.add({
						itemId:'column_sort_order',
						width: 30,
						cls: 'proxima_btn_customize proxima_btn_customize_new proxima_btn_control_tool',
						text: '<i class="fa fa-sort" aria-hidden="true" title="'+'컬럼 순서 저장하기'+'"></i>',
						handler: function(self){
							var cm = _this.getColumnModel();
							
							var totalCount = cm.columns.length-1;
							var sortOrder = '';
							Ext.each(cm.columns,function(r,i,e){
								if(totalCount == i){
									sortOrder = sortOrder + r.id;
								}else{
									sortOrder = sortOrder + r.id + ',';
								}
							});

							Ext.Msg.show({
								title:'알림',
								msg:'컬럼 정렬순서를 이대로 저장하시겠습니까?',
								buttons:Ext.Msg.OKCANCEL,
								fn:function(btnId, text, opts){
									if(btnId == 'ok'){
										Ext.Ajax.request({
											method:'POST',
											url:'/api/v1/member-option-column-save',
											params:{
												sort_order:sortOrder,
												ud_content_id:_this.ud_content_id
											},
											callback:function(option,success,response){
												var r = Ext.decode(response.responseText);
												if(r.success){
													Ext.Msg.alert('알림', '변경되었습니다.');
												}
											}
										});
									}
								}
							});
						}
					});
			
				
					self.add({
						xtype: 'tbspacer',
						width: '20'
					});


					self.add({
						width: 30,
						cls: 'proxima_btn_customize proxima_btn_customize_new proxima_btn_control_tool',
						text: '<i class="fa fa-repeat" aria-hidden="true" title="'+_text('MN00139')+'"></i>',
						//qtip: _text('MN00139'),
						handler: function(self){
							var p = Ext.getCmp('tab_warp').getActiveTab().get(0);
							Ext.getCmp('main_card_search').getEl().mask(_text('MSG02125'),"x-mask-loading");
							p.store.reload();
						}
					});
					self.add({
						itemId: 'thumb_view_tool',
						width: 30,
						cls: 'proxima_btn_customize proxima_btn_customize_new proxima_btn_control_tool',
						text: '<i class="fa fa-th-large" aria-hidden="true" title="'+_text('MN00060')+'"></i>',
						handler: function(self){
                            var active_tab_id = Ext.getCmp('tab_warp').getActiveTab().id;
                            var last_child = $("div#"+active_tab_id).find("div.cls_slider").find("tr.x-toolbar-right-row").last();
                            var cls_last_child = last_child.find("td.x-toolbar-cell").find("table.x-btn.proxima_btn_customize").removeClass("cls_action");
                            
                            // last_child.find("td.x-toolbar-cell.cls_action").removeClass("cls_action");
                            this.addClass("cls_action");
                            // $("table.x-btn.proxima_btn_customize.cls_action").parent().addClass("cls_action");

                            last_child.find("td.x-toolbar-cell").first().removeClass("x-hide-display");
                            last_child.find("td.x-toolbar-cell:eq(1)").addClass("x-hide-display");

							var p = Ext.getCmp('tab_warp').getActiveTab().get(0);
							p.mode = 'thumb';
							//Ext.util.Cookies.set('last_view_mode','thumb');
							p.getView().changeTemplate(p.template.tile);
							p.loadDDfile(p);

							p.init_tools(p);
							Ext.getCmp('form_panel_summary_slider').hide();
							Ext.getCmp('grid_summary_slider').hide();
							Ext.getCmp('grid_thumb_slider').show();
							Ext.getCmp('form_panel_thumb_slider').show();
							Ext.getCmp('grid_thumb_slider').change_image_size();
							
						}
					});
					// 요약 보기
					self.add({
						itemId: 'summary_view_tool',
						//qtip: _text('MN00062'),
						width: 30,
						cls: 'proxima_btn_customize proxima_btn_customize_new proxima_btn_control_tool',
						text: '<i class="fa fa-th-list" aria-hidden="true" title="'+_text('MN00062')+'"></i>',
						handler: function(self){
                            var active_tab_id = Ext.getCmp('tab_warp').getActiveTab().id;
                            var last_child = $("div#"+active_tab_id).find("div.cls_slider").find("tr.x-toolbar-right-row").last();
                            var cls_last_child = last_child.find("td.x-toolbar-cell").find("table.x-btn.proxima_btn_customize").removeClass("cls_action");
                            // last_child.find("td.x-toolbar-cell.cls_action").removeClass("cls_action");
                            this.addClass("cls_action");
                            // $("table.x-btn.proxima_btn_customize.cls_action").parent().addClass("cls_action");

                            last_child.find("td.x-toolbar-cell").first().addClass("x-hide-display");
                            last_child.find("td.x-toolbar-cell:eq(1)").removeClass("x-hide-display");

							var p = Ext.getCmp('tab_warp').getActiveTab().get(0);
							p.mode = 'mix';
							//Ext.util.Cookies.set('last_view_mode','mix');
							p.getView().changeTemplate(p.template.list);
							p.loadDDfile(p);

							p.init_tools(p);
							Ext.getCmp('form_panel_thumb_slider').hide();
							Ext.getCmp('grid_thumb_slider').hide();
							Ext.getCmp('form_panel_summary_slider').show();
							Ext.getCmp('grid_summary_slider').show();
                            Ext.Ajax.request({           
                                url: '/store/user/user_option/get_slider_value.php',
                                success: function (response, options) {
                                    var data =  Ext.decode(response.responseText);
                                    var slide_summary_size = data[0].slide_summary_size;
                                    var number = parseInt(slide_summary_size);
                                    Ext.getCmp('grid_summary_slider').setValue(number);
                                }
                            });
							Ext.getCmp('grid_summary_slider').change_image_size();
						}
					});
					// 리스트 보기
					self.add({
						itemId: 'list_view_tool',
						width: 30,
						cls: 'proxima_btn_customize proxima_btn_customize_new proxima_btn_control_tool',
						text: '<i class="fa fa-align-justify" aria-hidden="true" title="'+_text('MN00061')+'"></i>',
						handler: function(self){
                            var active_tab_id = Ext.getCmp('tab_warp').getActiveTab().id;
                            var last_child = $("div#"+active_tab_id).find("div.cls_slider").find("tr.x-toolbar-right-row").last();
                            var cls_last_child = last_child.find("td.x-toolbar-cell").find("table.x-btn.proxima_btn_customize").removeClass("cls_action");
                            
                            // last_child.find("td.x-toolbar-cell.cls_action").removeClass("cls_action");
                            this.addClass("cls_action");
                            // $("table.x-btn.proxima_btn_customize.cls_action").parent().addClass("cls_action");

                            last_child.find("td.x-toolbar-cell").first().addClass("x-hide-display");
                            last_child.find("td.x-toolbar-cell:eq(1)").addClass("x-hide-display");

							var p = Ext.getCmp('tab_warp').getActiveTab().get(0);
							p.mode = 'list';
							//Ext.util.Cookies.set('last_view_mode','list');
							p.getView().changeTemplate();
							p.loadDDfile(p);

							p.init_tools(p);
							Ext.getCmp('form_panel_thumb_slider').hide();
							Ext.getCmp('form_panel_summary_slider').hide();
							Ext.getCmp('grid_thumb_slider').hide();
							Ext.getCmp('grid_summary_slider').hide();
						}
					});

					self.add({
						//id: 'help-icon',
						width: 30,
						cls: 'proxima_btn_customize proxima_btn_customize_new proxima_btn_control_tool',
						text: '<i class="fa fa-question-circle" aria-hidden="true" title="'+_text('MN00009')+'"></i>',
						//qtip: _text('MN00009'),
						handler: function(e, toolEl, p, tc){

							var win = new Ext.Window({
									title: _text('MN00009'),
									cls: 'change_background_panel',
									id: 'help_icon_meaning',
									width: 515,
									modal: true,
									//height: 280,
									autoHeight:true,
									miniwin: true,
									resizable: false,
									//bodyStyle:'background: #F1F1F1;',
									layout: 'fit',
									items: [
									{
										xtype: 'label',
										html: '<?php echo $icon_help_top;?>'
									},
									{
										xtype: 'label',
										html: '<table cellpadding=\"2\" cellspacing=\"5\" border=\"0\" style=\"height:5px;width: 100%;background-color:#333337;\"></table>'
									},
									{
										xtype: 'label',
										html: '<?php echo $icon_help_bottom;?>'
									}
								]
							});
							win.show();
						}
					});

					self.add({
						itemId : 'ps_plugin_upload_icon',
						qtip: 'Photoshop Upload Button',
						hidden:true,
						handler: function(e, toolEl, p, tc){
							_photoshop_put_image();
						}
					});

					self.add({
						xtype: 'tbspacer',
						width: '20'
					});


					self.doLayout();
				},
				change: function(self, data ){

				}
			}
		})
	},
	reSortOrderColumn:function(){
		var _this = this;
		var cm = _this.getColumnModel();
        Ext.Ajax.request({
			method:'GET',
			url:'/api/v1/content-list-column-sort-order',
			callback:function(option, success, response){
				var r = Ext.decode(response.responseText);
                if(Ext.isEmpty(r.data)){
                    return false;
				};
                var sortOrder = r.data.sort_order;
				var sortOrderArray = sortOrder.split(","); 


				Ext.each(sortOrderArray,function(r,newIndex){
    				var oldIndex = cm.getIndexById(r);
                    cm.moveColumn(oldIndex, newIndex);
				});
			}
		})
	}
});

Ext.reg('contentgrid', Ext.ariel.ContentList);
