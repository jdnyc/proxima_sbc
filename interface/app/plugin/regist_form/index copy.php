<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT']."/lib/config.php");
require_once($_SERVER['DOCUMENT_ROOT']."/lib/functions.php");

$flag = $_REQUEST['flag'];


if (empty($_SESSION['user']['user_id']) || $_SESSION['user']['user_id'] == 'temp') {
    if ($_REQUEST['direct']) {
        $user = $mdb->queryRow("select * from bc_member where user_id='".$_REQUEST['user_id']."'");
        if (empty($user['user_id'])) {
            echo "<script type=\"text/javascript\">window.location=\"/index.php?flag=".$flag."\"</script>";
            exit;
        } else {
            $groups = getGroups($user['user_id']);
            $_REQUEST['lang'] = $user['lang'] ;
            $_SESSION['user'] = array(
                'user_id' => trim($user['user_id']),
                'is_admin' => trim($user['is_admin']),
                'KOR_NM' => $user['user_nm'],
                'user_email' => $user['email'],
                'phone' =>  $user['phone'],
                'groups' => $groups,
                'lang' => $user['lang']
            );
        }
    } else {
        echo "<script type=\"text/javascript\">window.location=\"/index.php?flag=".$flag."\"</script>";
        exit;
    }
}

//For NLE Exporter, default REQUEST set
$userId = !empty($_REQUEST['user_id']) ? $_REQUEST['user_id'] : $_SESSION['user']['user_id'];
$lang = !empty($_REQUEST['lang']) ? $_REQUEST['lang'] : $_SESSION['user']['lang'];

require_once($_SERVER['DOCUMENT_ROOT'].'/store/metadata/buildMediaListTab.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/store/metadata/buildSystemMeta.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lang.php');

// 저장 전 작업에 대한 로직 문자열을 얻어온다.
$beforeSaveJsLogic = '';
if (defined('CUSTOM_ROOT') && class_exists('\ProximaCustom\core\MetadataManager')) {
    $beforeSaveJsLogic = \ProximaCustom\core\MetadataManager::getBeforeSaveJsLogic();
}


?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=9" />
<meta name="viewport" content="initial-scale=1, maximum-scale=1,user-scalable=no"/>
    <title>등록 페이지</title>
	<link rel="SHORTCUT ICON" href="/Ariel.ico"/>
    <link rel="stylesheet" type="text/css" href="/lib/extjs/resources/css/ext-all.css" />
	<link rel="stylesheet" type="text/css" href="/css/xtheme-access.css" />
	<link rel="stylesheet" type="text/css" href="/lib/extjs/examples/ux/css/ProgressColumn.css" />
	<link rel="stylesheet" type="text/css" href="/javascript/timepicker/Ext.ux.Spinner/resources/css/Spinner.css" />
	<link rel="stylesheet" type="text/css" href="/javascript/timepicker/Ext.ux.TimePicker/resources/css/TimePicker.css" />

	<link rel="stylesheet" type="text/css" href="/css/font-awesome.min.css">

	<link rel="stylesheet" type="text/css" href="/css/style.css" />
	<script type="text/javascript" src="/javascript/script.js"></script>


    <script type="text/javascript">

	var global_detail;

	function MM_swapImgRestore() { //v3.0
	  var i,x,a=document.MM_sr; for(i=0;a&&i<a.length&&(x=a[i])&&x.oSrc;i++) x.src=x.oSrc;
	}
	function MM_preloadImages() { //v3.0
	  var d=document; if(d.images){ if(!d.MM_p) d.MM_p=new Array();
		var i,j=d.MM_p.length,a=MM_preloadImages.arguments; for(i=0; i<a.length; i++)
		if (a[i].indexOf("#")!=0){ d.MM_p[j]=new Image; d.MM_p[j++].src=a[i];}}
	}

	function MM_findObj(n, d) { //v4.01
	  var p,i,x;  if(!d) d=document; if((p=n.indexOf("?"))>0&&parent.frames.length) {
		d=parent.frames[n.substring(p+1)].document; n=n.substring(0,p);}
	  if(!(x=d[n])&&d.all) x=d.all[n]; for (i=0;!x&&i<d.forms.length;i++) x=d.forms[i][n];
	  for(i=0;!x&&d.layers&&i<d.layers.length;i++) x=MM_findObj(n,d.layers[i].document);
	  if(!x && d.getElementById) x=d.getElementById(n); return x;
	}

	function MM_swapImage() { //v3.0
	  var i,j=0,x,a=MM_swapImage.arguments; document.MM_sr=new Array; for(i=0;i<(a.length-2);i+=3)
	   if ((x=MM_findObj(a[i]))!=null){document.MM_sr[j++]=x; if(!x.oSrc) x.oSrc=x.src; x.src=a[i+2];}
	}

	</script>
	<!--현이롤오버관련끝-->

	<link rel="stylesheet" type="text/css" href="/css/login.css" />

    <style type="text/css">

	.app-msg .x-box-bl, .app-msg .x-box-br, .app-msg .x-box-tl, .app-msg .x-box-tr {
		background-image: url(/images/box-round-images/corners.gif);
	}

	.app-msg .x-box-bc, .app-msg .x-box-mc, .app-msg .x-box-tc {
		background-image: url(/images/box-round-images/tb.gif);
	}

	.app-msg .x-box-mc {
		color: darkorange;
		background-color: #c3daf9;
	}

	.app-msg .x-box-mc h3 {
		color: red;
	}

	.app-msg .x-box-ml {
		background-image: url(/images/box-round-images/l.gif);
	}

	.app-msg .x-box-mr {
		background-image: url(/images/box-round-images/r.gif);
	}

	.custom-nav-tab {
		background-color: #BDBDBD;
		padding: 0 0 0 4;
	}

	.tab-over-cls {
		background-color:red;
	}

	 .x-grid3-col-title{
		text-align: left;
	 }
	.x-grid3-td-title b {
		font-family:tahoma, verdana;
		display:block;
	}
	.x-grid3-td-title b i {
		font-weight:normal;
		font-style: normal;
		color:#000;
	}
	.x-grid3-td-title .x-grid3-cell-inner {
		white-space:normal;
	}
	.x-grid3-td-title a {
		color: #385F95;
		text-decoration:none;
	}
	.x-grid3-td-title a:hover {
		text-decoration:underline;
	}
	.details .x-btn-text {
		background-image: url(details.gif);
	}
	.x-resizable-pinned .x-resizable-handle-south{
		//11-11-16, 승수. 파일 없음. 	background:url(../../resources/images/default/sizer/s-handle-dark.gif);
		background-position: top;
	}
	.x-grid3-row-body p {
		margin:5px 5px 10px 5px !important;
	}
	.x-grid3-col-fileinfo{
		text-align: right;
	}

	.inner-body {
		margin: 0;
		padding: 0;
		background: #1b1b1b url(/images/web_bg_blue.jpg) top left repeat-x;
		font-family: Arial;
		font-size: 0.8em;
		width:100%;
	}

	.icon-506 {
		background-image:url(/led-icons/film.png) !important;
	}

	.icon-515 {
		background-image:url(/led-icons/music.png) !important;
	}

	.icon-57057 {
		background-image:url(/led-icons/book.png) !important;
	}

	.icon-518 {
		background-image:url(/led-icons/image_1.png) !important;
	}

	/* I'm not happy to have to include this hack but since we're using floating elements */
	/* this is needed, if anyone have a better solution, please keep me posted! */
	.x-grid3-body:after { content: "."; display: block; height: 0; font-size: 0; clear: both; visibility: hidden; }
	.x-grid3-body { display: inline-block; }
	/* Hides from IE Mac \*/
	* html .x-grid3-body { height: 1%; }
	.x-grid3-body { display: block; }
	/* End Hack */

	/*.x-grid3-row .ux-explorerview-large-icon-row x-grid3-row-first x-grid3-row-selected*/
	/*.x-grid3-row .ux-explorerview-large-icon-row .x-grid3-row-selected {*/

	.x-toolleft {
		float: left;
	}

	.x-tool-refresh20 {background-image: url(/css/h_img/win_refresh.png ) !important;repeat-x; width: 20px; height: 20px;margin: 0px 10px 0px 0px;}
	.x-tool-detail {background-image: url(/css/h_img/win_list.png) !important;repeat-x; width: 20px; height: 20px;margin: 0px 10px 0px 0px;}
	.x-tool-list {background-image: url(/css/h_img/win_look_list.png) !important;repeat-x;width: 20px; height: 20px;margin: 0px 10px 0px 0px;}
	.x-tool-tile {background-image: url(/css/h_img/win_look.png) !important;repeat-x;width: 20px; height: 20px;margin: 0px 10px 0px 0px;}


	.gridBodyNotifyOver {
        border-color: #00cc33 !important;
    }
    .gridRowInsertBottomLine {
        border-bottom:1px dashed #00cc33;
    }
    .gridRowInsertTopLine {
        border-top:1px dashed #00cc33;
    }

    #loading-mask{
        position:absolute;
        left:0;
        top:0;
        width:100%;
        height:100%;
        z-index:20000;
        background-color:white;
    }

	#loading{
    	border: 1px solid black;
        position:absolute;
        left:45%;
        top:40%;
        padding:2px;
        z-index:20001;
        height:auto;
    }
    #loading a {
        color:#225588;
    }
    #loading .loading-indicator{
        background:white;
        color:#444;
        font:bold 13px tahoma,arial,helvetica;
        padding:10px;
        margin:0;
        height:auto;
    }
    #loading-msg {
        font: normal 10px arial,tahoma,sans-serif;
    }

	#images-view .x-panel-body{
		background: white;
		font: 11px Arial, Helvetica, sans-serif;
	}
	#images-view .thumb{
		background: #dddddd;
		padding: 3px;
	}
	#images-view .thumb img{
		height: 60px;
		width: 80px;
	}
	#images-view .thumb-wrap{
		float: left;
		margin: 4px;
		margin-right: 0;
		padding: 5px;
	}
	.comments{
		background-color: blue;
	}
	#images-view .thumb-wrap span{
		display: block;
		overflow: hidden;
		text-align: center;
	}

	#images-view .x-view-over{
		border:1px solid #dddddd;
		background: #efefef url(../../resources/images/default/grid/row-over.gif) repeat-x left top;
		padding: 4px;
	}

	#images-view .x-view-selected{
		background: #eff5fb url(images/selected.gif) no-repeat right bottom;
		border:1px solid #99bbe8;
		padding: 4px;
	}
	#images-view .x-view-selected .thumb{
		background:transparent;
	}

	#images-view .loading-indicator {
		font-size:11px;
		background-image:url('../../resources/images/default/grid/loading.gif');
		background-repeat: no-repeat;
		background-position: left;
		padding-left:20px;
		margin:10px;
	}

	.mainnav-date {
		background-image: url(/led-icons/calendar_2.png) !important;
	}
	.mainnav-category {
		background-image: url(/led-icons/text_padding_bottom.png) !important;
	}

	.subnav-favorite {
		background-image: url(/led-icons/zicon.gif) !important;
	}
	.subnav-workflow {
		background-image: url(/led-icons/workicon.gif) !important;
	}

	.is-hidden-content {
		background-color: #DDA0DD;
	}

	.review-ready {
		background-color: red;
	}

	/*
	.content-status-reg-ready {
		background-color: #;
	}
	*/
	.content-status-reg-request {
		background-color: #FFD700;
	}
	/*
	.content-status-reg-complete {
		background-color: #;
	}
	*/
	.content-status-review-ready {
		background-color: #A9A9A9;
	}
	.content-status-review-complete {
		background-color: #778899;
	}
	.content-status-review-return {
		background-color: #E9967A;
	}
	.content-status-review-half {
		background-color: #DCDCDC;
	}

	.ct-override {
		background-color: red;
	}
	.wait_list_modified {
		background-color: #FFFFBB;
	}

	.x-list-body-inner dl {
	   border-bottom: 1px solid #DDDDDD;
	   border-right: 1px solid #DDDDDD;
	}

	/* progress */
	.x-grid3-td-progress-cell .x-grid3-cell-inner {
		font-weight: bold;
	}

	.x-grid3-td-progress-cell .high {
		background: transparent url(/lib/extjs/examples/ux/images/progress-bg-green.gif) 0 -33px;
	}

	.x-grid3-td-progress-cell .medium {
		/*background: transparent url(/lib/extjs/examples/ux/images/progress-bg-orange.gif) 0 -33px;*/
		background: transparent url(/lib/extjs/examples/ux/images/progress-bg-middle.gif) 0 -33px;
	}

	.x-grid3-td-progress-cell .low {
		/*background: transparent url(/lib/extjs/examples/ux/images/progress-bg-green.gif) 0 -33px;*/
		background: transparent url(/lib/extjs/examples/ux/images/progress-bg-low.gif) 0 -33px;
	}

	.x-grid3-td-progress-cell .ux-progress-cell-foreground {
		color: #fff;
	}

	.x-tab-strip-active2  span.x-tab-strip-text
	{
		color:white;
		font-weight: bold;
		font-size:15px;
	}

	.x-tab-strip-active2 .x-tab-top
	{

	}

	.readonly-class {
        background-color: #DADADA;
        background-image: none;
        border-color: #B5B8C8;
    }

    /* 검은배경일때 그리드 마우스 오버시 잘 보이도록 */
    .x-grid3-row-over {
        background-color: #6081A1 !important;
        background-image: none !important;
    }

	</style>

</head>

<body>
  	<script type="text/javascript" src="/lib/extjs/adapter/ext/ext-base.js"></script>
    <script type="text/javascript" src="/lib/extjs/ext-all.js"></script>
	<script type="text/javascript" src="/javascript/lang.php"></script>

	<script type="text/javascript" src="/javascript/js_config.php"></script>
	<script type="text/javascript" src="/javascript/functions.js"></script>

	<!-- <script type="text/javascript" src="/javascript/functions.js"></script> -->
	<script type="text/javascript" src="/javascript/Ext.ux.PopupWindow.js"></script>

	<!-- <script type="text/javascript" src="/javascript/ext.ux/Ariel.CartWindow.php"></script> -->
	<script type="text/javascript" src="/javascript/ext.ux/dd.js"></script>
	<script type="text/javascript" src="/javascript/ext.ux/Ext.ux.grid.PageSizer.js"></script>
	<!-- <script type="text/javascript" src="/javascript/ext.ux/categoryContextMenu.php"></script> -->

    <script type="text/javascript" src="/javascript/ext.ux/Ext.ux.TreeCombo.js"></script>
	<script type="text/javascript" src="/javascript/ext.ux/Ext.ariel.ContentList.php"></script>
	<script type="text/javascript" src="js/searchMaterial.js"></script>

    <script type="text/javascript" src="/lib/extjs/src/locale/ext-lang-<?=$lang?>.js"></script>


	<script type="text/javascript" src="/javascript/ext.ux/Ariel.Nps.WorkRequest.php"></script>
    <script type="text/javascript" src="/javascript/ext.ux/Ariel.Nps.BISEpisode.php"></script>
    <script type="text/javascript" src="/javascript/jquery-1.9.1.min.js"></script>

	<!-- ////////트리 그리드 선언///////  -->
	<script type="text/javascript" src="/lib/extjs/examples/ux/treegrid/TreeGridSorter.js"></script>
	<script type="text/javascript" src="/lib/extjs/examples/ux/treegrid/TreeGridColumnResizer.js"></script>
	<script type="text/javascript" src="/lib/extjs/examples/ux/treegrid/TreeGridNodeUI.js"></script>
	<script type="text/javascript" src="/lib/extjs/examples/ux/treegrid/TreeGridLoader.js"></script>
	<script type="text/javascript" src="/lib/extjs/examples/ux/treegrid/TreeGridColumns.js"></script>
	<script type="text/javascript" src="/lib/extjs/examples/ux/treegrid/TreeGrid.js"></script>


    <!--Custom Menu Pages -->
    <?php

    if (defined('CUSTOM_ROOT') && class_exists('\ProximaCustom\core\ScriptManager')) {
        $scripts = \ProximaCustom\core\ScriptManager::getCustomScripts(false);
        foreach ($scripts as $script) {
            echo $script;
        }
    }

    ?>
    
    <script type="text/javascript" src="/javascript/component/button/Ariel.IconButton.js"></script>
	<script type="text/javascript" src="/javascript/Ariel.override.js"></script>



	<script type="text/javascript">

	dt = new Date();
	var current_focus = null;
	var advanceSearchWin = null;

	// RootPath 변수
	var root_path = null;
	// 	Program별 Path 담는 변수
	var prog_path = null;

	Ext.override(Ext.grid.CheckboxSelectionModel, {
		handleMouseDown : function(g, rowIndex, e){
			if((g.enableDragDrop || g.enableDrag) && e.getTarget().className == 'x-grid3-row-checker')
			{
				return;
			}
			else
			{
				Ext.grid.CheckboxSelectionModel.superclass.handleMouseDown.apply(this, arguments);
			}
		}
	});
	Ext.override(Ext.PagingToolbar, {
		doLoad : function(start){
			var o = {}, pn = this.getParams();
			o[pn.start] = start;
			o[pn.limit] = this.pageSize;
			if(this.fireEvent('beforechange', this, o) !== false){
				var options = Ext.apply({}, this.store.lastOptions);
				options.params = Ext.applyIf(o, options.params);
				this.store.load(options);
			}
		}
	});
	Ext.override(Ext.Window, {
		onPosition: function(x, y){
			if(x < 0) this.setPosition(0, y);
			if(y < 0) this.setPosition(x, 0);
		}
	})

	function resizeImg(self, size){
		if (!Ext.isIE){
		}
	//	self.display = none;
		if (size){
			self.width = size.w;
			self.height = size.h;
		}else{
			self.width = 150;
			self.height = 84;
		}

	//	self.display = block;
	}



	Ext.chart.Chart.CHART_URL = '/lib/extjs/resources/charts.swf';
	Ext.BLANK_IMAGE_URL = '/lib/extjs/resources/images/default/s.gif';

	Ext.onReady(function(){

		Ext.QuickTips.init();

		var view = new Ext.Viewport({
			layout: 'border',
			defaults: {
				split: true,
				autoScroll: true
			},
			items:[{
				region: 'center',
				xtype: 'panel',
				layout: 'fit',
				width: '100%',
				style : {
					background : 'red'
				},
				id: 'regist_form_tab',
				defaults:{autoHeight: true},
				frame:true,
				tbar:[{
					xtype: 'displayfield',
					width: 15
				},{
					xtype: 'radiogroup',
					hideLabel: true,
					width: 180,
					columns: 3,
					hidden: true,
					beforeValue: 'program',
					items: [
						{boxLabel: '카테고리', name: 'is_use', inputValue:'program' , checked: true}
					],
					listeners:{
						change: function(self, checked){
							var checkedVal = checked.getRawValue();
							if (self.beforeValue == checkedVal) return;
							Ext.Msg.show({
								title: '알림',
								icon: Ext.Msg.INFO,
								msg: '입력하신 정보가 초기화 되며, 선택하신 유형으로 정보가 갱신됩니다.<br />진행하시겠습니까?',
								buttons: Ext.Msg.OKCANCEL,
								fn: function(btnID, text, opt) {
									if(btnID == 'ok') {
										var tab = self.ownerCt.ownerCt;
										tab.get(0).loadFormMetaData(tab.get(0));
										self.beforeValue = checkedVal;
									} else {
										self.setValue(self.beforeValue);
									}
								}
							});
						},
					}
				},'',{
					xtype: 'displayfield',
					width: 7
				},_text('MN00276'),{//MN00276 content type
					xtype:'combo',
					width: 100,
					id : 'content_type',
                    editable: false,
					displayField:'ud_content_title',
					valueField: 'ud_content_id',
					typeAhead: true,
					beforeValue: '',
					triggerAction: 'all',
					lazyRender:true,
					store: new Ext.data.JsonStore({
						url: '/interface/mam_ingest/get_meta_json.php',
						root: 'data',
						baseParams: {
							kind : 'ud_content',
							flag: '<?=$flag?>'
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
										//로드된 첫번째 항목 설정
										self.setValue(r[0].get('ud_content_id'));
										self.beforeValue = r[0].get('ud_content_id');

										var tab = self.ownerCt.ownerCt;
										tab.get(0).loadFormMetaData(tab.get(0));
									}
								}
							});
						},
						select: function(self, record, index ){
							var selVal = record.get('ud_content_id');
							if(self.beforeValue == selVal) return;

							Ext.Msg.show({
								title: _text('MN00023'),//Information
								icon: Ext.Msg.INFO,
								//Current metadata will be cleared. And will be refresh by selected content type metadata.
								//Will you proceed?
								msg: _text('MSG02066')+'<br />'+_text('MSG02067'),
								buttons: Ext.Msg.OKCANCEL,
								fn: function(btnID, text, opt) {
									if(btnID == 'ok') {
										var tab = self.ownerCt.ownerCt;
										tab.get(0).loadFormMetaData(tab.get(0));
										self.beforeValue = selVal;
									} else {
										self.setValue(self.beforeValue);
									}
								}
							});
						}
					}
				},{
					xtype: 'displayfield',
					width: 7
				},'-',{
					xtype: 'displayfield',
					width: 7
				},{
                    text: 'submit_meta()',
                    hidden: true,
                    handler: function(b, e) {
                        var sm = submit_meta();
                        console.log(sm);
                    }
                }],
				items: [{
					xtype: 'tabpanel',
					id: 'regist_form_tabpanel',
					activeTab: 0,
					defaults:{autoHeight: true},
					frame:true,
					isFirst: true,
					listeners:{
						afterrender:function(self) {
							
						}
					},
					items:[],
					loadFormMetaData: function(self, params ){
						var tbar = self.ownerCt.getTopToolbar();
						var ud_content_tab = tbar.items.get(1).getValue().getRawValue();
						var ud_content_id = tbar.items.get(5).getValue();
						params = params || {};
						params.ud_content_tab = ud_content_tab;
						params.ud_content_id = ud_content_id;
						params.user_id = '<?=$userId?>';
						params.lang = '<?=$lang?>';

						Ext.Ajax.request({
							url: 'get_metadata.php',
							params: params,
							callback: function(opts, success, response){
								if (success) {
									try {
										var r = Ext.decode(response.responseText);
										self.removeAll();
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
					put_meta_afterLoadFormMetaData: function(self, params, input_meta_string){
						var tbar = self.ownerCt.getTopToolbar();
						var ud_content_tab = tbar.items.get(1).getValue().getRawValue();
						var ud_content_id = tbar.items.get(5).getValue();
						params = params || {};
						params.ud_content_tab = ud_content_tab;
						params.ud_content_id = ud_content_id;
						params.user_id = '<?=$userId?>';
						params.lang = '<?=$lang?>';

						Ext.Ajax.request({
							url: 'get_metadata.php',
							params: params,
							callback: function(opts, success, response){
								if (success) {
									try {
										var r = Ext.decode(response.responseText);
										self.removeAll();
										self.add(r);
										self.doLayout();
										self.activate(0);

										put_meta2(input_meta_string);
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
					}
				}]
			}],
			listeners:{
				afterrender:function(self) {
				},
				render: function(self) {
					getRootPathArray();
					getMPathArray();
				}
			}
		});
    });

	function tc_new_window(meta_field_id)
	{
		new Ext.Window({
			title:'TC 정보 추가',
			modal:true,
			//id:'tc'+meta_field_id,
			width: 450,
			//maximized: true,
			height:400,
			frame:true,
			layout: 'fit',
			items:[{
						xtype: 'panel',
						id: 'tc_panel',
						frame: false,
						hidden: false,
						layout: 'fit',
						id:'tc'+meta_field_id,
						items: {
							xtype: 'form',
							padding: 0,
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
						buttons: [
									{
										text: '가상 클립 생성',
										hidden: true
									},
									{
										xtype: 'tbfill'
									},
									{
										text: '확인',
										icon: '/led-icons/application_edit.png',
										handler: function(b, e)
										{
											var parent = b.ownerCt.ownerCt;
											var form = parent.get(0).getForm();
											//필수입력값 체크

											if( !form.isValid() )
											{
												Ext.Msg.alert( _text('MN00023'), '필수 값을 입력해주세요');
												return;
											}

											var parent = b.ownerCt.ownerCt;
											Ext.Msg.show({
													title: '확인',
													msg: parent.title+' 하시겠습니까?',
													icon: Ext.Msg.QUESTION,
													width:200,
													buttons: Ext.Msg.OKCANCEL,
													fn: function(btnId)
													{
														if ( btnId == 'ok')
														{
															var form = parent.get(0).getForm();
															var values = form.getFieldValues();

															var list = Ext.getCmp('list<?=$meta_field_id?>');
															var list_store = list.store;

															if( parent.title == '추가' )
															{
																var start_tc_sec = timecodeToSec( values.columnB ) ;
																var end_tc_sec = timecodeToSec( values.columnC )  ;
															}
															else
															{
																var start_tc_sec = timecodeToSec( values.columnB );
																var end_tc_sec = timecodeToSec( values.columnC );
															}

															values.columnB = secToTimecode( start_tc_sec );
															values.columnC = secToTimecode( end_tc_sec );

															if (Ext.getCmp('tc_category'))
															{
																var tn = Ext.getCmp('tc_category').getFullPath();
																if(!Ext.isEmpty(tn))
																{
																//	var result = tn.getPath().replace(/ -> /gi, '/');
																	var root = Ext.getCmp('tc_category').getRootPath();
																	var result = root+'/'+tn;
																	values.columnE =result;
																}
																else
																{
																	values.columnE ='';
																}
															}

															var new_record = new list_store.recordType( values );
															if ( parent.title == '추가' )
															{
																list_store.add( new_record );
															}
															else
															{
																var old_record = list.getSelectionModel().getSelections()[0];
																var idx = list_store.indexOf( old_record );

																list_store.remove( old_record );
																list_store.insert( idx, new_record );
																list.getSelectionModel().selectRow(idx);

															}
															var outer = list.ownerCt;
															b.ownerCt.ownerCt.ownerCt.close();
														}
													}
												});

										}
									},
									{
										text: '취소',
										icon: '/led-icons/cancel.png',
										handler: function(b, e)
										{
											b.ownerCt.ownerCt.ownerCt.close();
										}
									}
								]

					}]
		}).show();
	}

	function checkExt(){
		var ud_content = Ext.getCmp('content_type');
		var ud_content_id = ud_content.getValue();
		var record = ud_content.findRecord(ud_content.valueField || ud_content.displayField, ud_content_id);

		return record.get('allowed_extension');
	}

	function isValid()
	{
		var metaTab = Ext.getCmp('regist_form_tabpanel');
		var length = metaTab.items.length;
		var curTab = metaTab.activeTab;

		//TC정보 그리드 스토어에 valid체크
		var tc_grid = Ext.getCmp('list<?=$meta_field_id?>');

		if( !Ext.isEmpty( tc_grid ) )
		{
			if( Ext.isEmpty( tc_grid.getStore().data.items ) )
			{
				return 'false';
			}
		}

		for(var i=0; i<length; ++i)
		{
			metaTab.setActiveTab(i);
			if( !metaTab.items.items[i].getForm().isValid() )
			{
				return 'false';
			}
		}

		metaTab.setActiveTab(curTab);

		return 'true';
	}

	function clearForm()
	{
		var metaTab = Ext.getCmp('regist_form_tabpanel');
		var length = metaTab.items.length;
		var curTab = metaTab.activeTab;

		for(var i=0; i<length; ++i)
		{
			metaTab.setActiveTab(i);
			metaTab.items.items[i].getForm().reset();
		}
		var tc_grid = Ext.getCmp('list<?=$meta_field_id?>');
		if( !Ext.isEmpty(tc_grid) ){
			tc_grid.getStore().removeAll();
		}
		metaTab.setActiveTab(curTab);
	}

	function getFormData() {
		var metaTab = Ext.getCmp('regist_form_tabpanel');
		var length = metaTab.items.length;
		var arrMeta = [];
        var curTab = metaTab.activeTab;
        
        //CJO, 저장전 커스텀 로직
		<?=$beforeSaveJsLogic?>

		for (var i = 0; i < length; ++i) {
			metaTab.setActiveTab(i);
            var p = metaTab.items.items[i].getForm().getValues();
            metaTab.items.items[i].getForm().items.each(function(i){
                if (i.xtype == 'checkbox' && !i.checked) {
                    i.el.dom.checked = true;
                    i.el.dom.value = '';
                }
                if(i.xtype == 'combo'){
                    var kval = i.id ;
                    p[i.name] = i.getValue();
                }
                if(i.xtype == 'c-tree-combo'){
                    var kval = i.id ;
                    p[i.name] = i.getValue();
                }
            });

			if (i == 0 && Ext.getCmp('category') != null) {
				var tn = Ext.getCmp('category').treePanel.getSelectionModel().getSelectedNode();
				p.c_category_id = tn.attributes.id;
			}

			arrMeta.push(p);
		}

		//TC정보 그리드 스토어의 xml 등록
		var tc_grid = Ext.getCmp('list<?=$meta_field_id?>');

		if ( ! Ext.isEmpty( tc_grid)) {

			var tmp = new Array();

			tc_grid.getStore().each(function(i){
				tmp.push(i.data);
			});
			arrMeta.push({ multi: tmp });
		}

		metaTab.setActiveTab(0);

		return arrMeta;
	}

	function loadFormData(data)
	{
		var metaTab = Ext.getCmp('regist_form_tabpanel');
		var retData = {
			success: true,
			msg: ''
		};

		if(data == null)
		{
			retData.success = false;
			retData.msg = 'data is null';
			return retData;
		}

		var rec = Ext.decode(data);

		try
		{
			var curTab = metaTab.activeTab;

			for(var i=0; i<rec.length; ++i)
			{
				var target_tab = metaTab.get('user_metadata_'+rec[i].k_meta_field_id);

				if(!Ext.isEmpty(target_tab)){

					if( Ext.isObject(rec[i]) && !Ext.isEmpty(rec[i]) && Ext.isEmpty(rec[i].multi) )	{
						var record = new Ext.data.Record( rec[i] );
						target_tab.getForm().loadRecord(record);
					}

					if(i == 0 && Ext.getCmp('category')){
						//카테고리 처리
						var categoryId = rec[i].c_category_id;
						if(categoryId != '0'){
							Ext.getCmp('category').setPath(rec[i].c_fullPath);
						}
					}

					if(rec[i].k_meta_field_id == '4002615'){

						var tc_grid = Ext.getCmp('list<?=$meta_field_id?>');
						tc_grid.getStore().load({
							params: {
								meta_field_id: '<?=$meta_field_id?>',
								content_id: rec[i].c_content_id
							}
						});
					}
				}
			}
			metaTab.setActiveTab(curTab);
		}
		catch (err)
		{
			retData.success = false;
			retData.msg = 'Fail to load form data\n' + err;
		}

		return retData;
	}

	function get_meta(){
		var metadata = [];

		var metadata = getFormData();

		var returnValue = {

			user_id: '<?=$userId?>',
			flag: '<?=$flag?>',
			metadata_type: 'id',
			metadata: metadata
		};
		var ret =  Ext.encode(returnValue);

		return ret;
	}

	function put_meta(data){
		var decodeData = Ext.decode(data);
		var metadata = decodeData.metadata;

		Ext.getCmp('content_type').setValue(metadata[0].k_ud_content_id);

		Ext.getCmp("regist_form_tabpanel").put_meta_afterLoadFormMetaData(Ext.getCmp("regist_form_tabpanel"), '', data);
		Ext.getCmp("regist_form_tabpanel").beforeValue = metadata[0].k_ud_content_id;
	}

	function put_meta2(data){
		data = Ext.decode(data);
		var metadata = data.metadata;

		var metaTab = Ext.getCmp('regist_form_tabpanel');
		var length = metaTab.items.length;
		var arrMeta = [];
		var curTab = metaTab.activeTab;

		var i=0;
		Ext.each(metadata, function(meta, index){
			metaTab.setActiveTab('user_metadata_'+meta.k_ud_content_id);

			var p = metaTab.items.items[i].getForm().setValues(meta);
			i++;
		});

		metaTab.setActiveTab(curTab.id);
	}

	function submit_meta(){
		var metadata = [];

		var metadata = getFormData();

		var returnValue = {

			user_id: '<?=$userId?>',
			flag: '<?=$flag?>',
			metadata_type: 'id',
			metadata: metadata
		};
		var ret =  Ext.encode(returnValue);

		return ret;
	}

	function submit_meta2(){
		var metadata = [];

		var metadata = getFormData();

		var returnValue = {

			result: 'false',
			msg: '필수 입력 데이터가 없습니다.',
			user_id: '<?=$userId?>',
			flag: '<?=$flag?>',
			metadata: metadata
		};
		var ret =  Ext.encode(returnValue);

		var return_sub = ret.substr(0,ret.length -1);

		return ret;
	}

	function submit_meta_soap(){
		var metadata = [];
		var metadata = getFormData();
		var returnValue = {
			user_id : '<?=$userId?>',
			flag : '<?=$flag?>',
			metadata_type : 'id',
			metadata : metadata
		};
		var ret =  Ext.encode(returnValue);


		return ret;
	}

	function getRootPath(){
		var category_id = null;
                var channel = 'edius';
				var mid_path = '';

		if (Ext.getCmp('category') != null)
		{
			var tn = Ext.getCmp('category').treePanel.getSelectionModel().getSelectedNode();
			category_id = tn.attributes.id;
		}

		if( Ext.isEmpty(category_id) || Ext.isEmpty(prog_path)|| Ext.isEmpty(root_path) ){
			return 'false';
		}

        // Edius로 넘길때는 역슬러쉬 두번(\\)으로 변경해줘야 됨
		//return root_path[channel] + "\\" + prog_path[category_id] + mid_path ;
		return "Z:\\Export";
	}

	function getRootPathArray(){
		Ext.Ajax.request({
			url: '/store/get_task_rootpath.php',
			callback: function(self, success, response){
				if (success){
					try {
						var r = Ext.decode(response.responseText);

						if (r.success) {
                            root_path = r.data;
						}
					} catch (e) {
					}
				}else{

				}
			}
		});
	}

	function getMPathArray(){
		Ext.Ajax.request({
			url: '/store/get_category_path.php',
			callback: function(self, success, response){
				if (success) {
					try {
						var r = Ext.decode(response.responseText);

						if(r.success){
                            prog_path = r.data;
						}
					} catch (e) {

					}
				} else {

				}
			}
		});
	}

	function submit(url, file) {
		var metadata = [];

		var metadata = Ext.encode(getFormData());
		var url = '/interface/plugin_register.php';

		Ext.Ajax.request({
			url: url,
			params: {
				filepath: file,
				user_id: '<?=$userId?>',
				metadata: metadata,
				flag: '<?=$flag?>'
			},
			callback: function(self, success, response) {

				if (success) {
					try {
						var r = Ext.decode(response.responseText);

						if(r.success){
							window.location ='success.php';
						}else{
							window.location ='fail.php';
						}
					} catch (e) {
						window.location ='fail.php';
					}
				} else {
					window.location ='fail.php';
				}
			}
		});

		return true;
	}

	function submit_evcr() {

		var metadata = [];

		var metadata = Ext.encode(getFormData());
		var url = '/interface/plugin_evcr_register.php';
		var result = null;

            var jqxhr = $.ajax({
                async: false,
                dataType: 'json',
                url: url,
      			data: {
	     			type: 'metadata',
		    		metadata: metadata
			    }
            }).done(function(jsonResponse){
                 result = jsonResponse.content_id;
            }).fail(function(e) {
                if (e.status === 200) {
                    result = e.responseText;
                } else {
                    result = e.statusText + "(" + e.status + ")";
                }
            });

		return result;
	}

	function fn_checkLogout(callbackFunction, av_obj){
		Ext.Ajax.request({
			url: '/lib/session_check.php',
			async: false,
			callback: function(opts, success, response){
				if(success){
					try{
						if(response.responseText != 'true'){
							fn_msgLogout(av_obj);
						}else{
							if(!Ext.isEmpty(callbackFunction)){
								callbackFunction();
							}
						}
					}
					catch(e){
						fn_msgLogout(av_obj);
					}
				}else{
					fn_msgLogout(av_obj);
				}
			}
		});
	}

	function fn_filter_type3_1(){
		var v_filter_value = Ext.getCmp('k_type3_1').getValue();

		Ext.getCmp('k_type3_2').setValue();
		Ext.getCmp('k_type3_3').setValue();

		Ext.getCmp('k_type3_2').store.clearFilter();
		Ext.getCmp('k_type3_3').store.clearFilter();

		Ext.getCmp('k_type3_2').store.filterBy(function (record) {
			if(!Ext.isEmpty(v_filter_value)){
				if (record.get('c_pid') == v_filter_value || Ext.isEmpty(record.get('c_pid'))) return true;
			}else{
				return false;
			}
		});
	}

	function fn_filter_type3_2(){
		var v_filter_value = Ext.getCmp('k_type3_2').getValue();

		Ext.getCmp('k_type3_3').setValue();

		Ext.getCmp('k_type3_3').store.clearFilter();

		Ext.getCmp('k_type3_3').store.filterBy(function (record) {
			if(!Ext.isEmpty(v_filter_value)){
				if (record.get('c_pid') == v_filter_value || Ext.isEmpty(record.get('c_pid'))) return true;
			}else{
				return false;
			}
		});
	}
    </script>



</body>
</html>