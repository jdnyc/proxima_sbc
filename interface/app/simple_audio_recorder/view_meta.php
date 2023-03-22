<?php

require_once($_SERVER['DOCUMENT_ROOT']."/lib/config.php");
require_once($_SERVER['DOCUMENT_ROOT']."/lib/functions.php");
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lang.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/MetaData.class.php');

$file = $_GET['file'];

$content_id = $_GET['content_id'];
if( $content_id){

}else{
    $query = "SELECT CONTENT_ID FROM BC_MEDIA WHERE MEDIA_TYPE = 'original' AND PATH LIKE '%$file%'";

    $content_id = $db->queryOne($query);
    $content_info = $db->queryRow("SELECT * FROM BC_CONTENT WHERE CONTENT_ID = '$content_id'");
    if ($content_info['is_group'] == 'C') {
        $content_id = $content_info['parent_content_id'];
    }

    if (empty($content_id)) {
        echo "프로그램 정보가 없습니다";
        return true;
    }
}

?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=8" />
<meta http-equiv="X-UA-Compatible" content="IE=9" />
<meta name="viewport" content="initial-scale=1, maximum-scale=1,user-scalable=no"/>
<title>상세정보</title>
	<link rel="SHORTCUT ICON" href="/Ariel.ico" />
	<link rel="stylesheet" type="text/css" href="/lib/extjs/resources/css/ext-all.css" />
	<link rel="stylesheet" type="text/css" href="/css/custom-xtheme-access.css" />
	<link rel="stylesheet" type="text/css" href="/lib/extjs/examples/ux/css/ProgressColumn.css" />
	<link rel="stylesheet" type="text/css" href="/javascript/timepicker/Ext.ux.Spinner/resources/css/Spinner.css" />
	<link rel="stylesheet" type="text/css" href="/javascript/timepicker/Ext.ux.TimePicker/resources/css/TimePicker.css" />

	<link rel="stylesheet" type="text/css" href="/css/font-awesome.min.css">

	<link rel="stylesheet" type="text/css" href="/css/style.css" />
	<script type="text/javascript" src="/javascript/script.js"></script>


	<script type="text/javascript">
		var global_detail;

		function MM_swapImgRestore() { //v3.0
			var i, x, a = document.MM_sr;
			for (i = 0; a && i < a.length && (x = a[i]) && x.oSrc; i++) x.src = x.oSrc;
		}

		function MM_preloadImages() { //v3.0
			var d = document;
			if (d.images) {
				if (!d.MM_p) d.MM_p = new Array();
				var i, j = d.MM_p.length,
					a = MM_preloadImages.arguments;
				for (i = 0; i < a.length; i++)
					if (a[i].indexOf("#") != 0) {
						d.MM_p[j] = new Image;
						d.MM_p[j++].src = a[i];
					}
			}
		}

		function MM_findObj(n, d) { //v4.01
			var p, i, x;
			if (!d) d = document;
			if ((p = n.indexOf("?")) > 0 && parent.frames.length) {
				d = parent.frames[n.substring(p + 1)].document;
				n = n.substring(0, p);
			}
			if (!(x = d[n]) && d.all) x = d.all[n];
			for (i = 0; !x && i < d.forms.length; i++) x = d.forms[i][n];
			for (i = 0; !x && d.layers && i < d.layers.length; i++) x = MM_findObj(n, d.layers[i].document);
			if (!x && d.getElementById) x = d.getElementById(n);
			return x;
		}

		function MM_swapImage() { //v3.0
			var i, j = 0,
				x, a = MM_swapImage.arguments;
			document.MM_sr = new Array;
			for (i = 0; i < (a.length - 2); i += 3)
				if ((x = MM_findObj(a[i])) != null) {
					document.MM_sr[j++] = x;
					if (!x.oSrc) x.oSrc = x.src;
					x.src = a[i + 2];
				}
		}
	</script>
	<!--현이롤오버관련끝-->

	<link rel="stylesheet" type="text/css" href="/css/login.css" />

	<style type="text/css">
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
	<script type="text/javascript" src="/javascript/ext.ux/dd.js"></script>
	<script type="text/javascript" src="/javascript/ext.ux/Ext.ux.grid.PageSizer.js"></script>
	<script type="text/javascript" src="/lib/extjs/src/locale/ext-lang-ko.js"></script>

	<script type="text/javascript" src="/javascript/jquery-1.9.1.min.js"></script>

    <!--Custom Menu Pages -->
	<?php

	if (defined('CUSTOM_ROOT') && class_exists('\ProximaCustom\core\ScriptManager')) {
		$scripts = \ProximaCustom\core\ScriptManager::getCustomScripts(false, ['Ariel.Nps.DashBoard', 'Ariel.Das.ArcManage', 'Ariel.task.Monitor']);
		foreach ($scripts as $script) {
			echo $script;
		}
	}

	?>
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
			handleMouseDown: function(g, rowIndex, e) {
				if ((g.enableDragDrop || g.enableDrag) && e.getTarget().className == 'x-grid3-row-checker') {
					return;
				} else {
					Ext.grid.CheckboxSelectionModel.superclass.handleMouseDown.apply(this, arguments);
				}
			}
		});
		Ext.override(Ext.PagingToolbar, {
			doLoad: function(start) {
				var o = {},
					pn = this.getParams();
				o[pn.start] = start;
				o[pn.limit] = this.pageSize;
				if (this.fireEvent('beforechange', this, o) !== false) {
					var options = Ext.apply({}, this.store.lastOptions);
					options.params = Ext.applyIf(o, options.params);
					this.store.load(options);
				}
			}
		});
		Ext.override(Ext.Window, {
			onPosition: function(x, y) {
				if (x < 0) this.setPosition(0, y);
				if (y < 0) this.setPosition(x, 0);
			}
		})

		function resizeImg(self, size) {
			if (!Ext.isIE) {}
			//	self.display = none;
			if (size) {
				self.width = size.w;
				self.height = size.h;
			} else {
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
				xtype: 'tabpanel',
				width: '100%',
				//defaults:{autoHeight: true},
				activeTab: 0,
				frame:true,
				items: [],
				listeners:{
					render: function(self){
						Ext.Ajax.request({
							url: '/store/get_detail_viewMeta.php',
							params: {
								content_id: <?=$content_id?>
							},
							callback: function(opts, success, response){
								if (success) {
									try {
										var r = Ext.decode(response.responseText);

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
					}
				}
			}]
			
		});
	});

	
	</script>

	</body>
</html>