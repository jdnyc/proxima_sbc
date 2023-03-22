<?php
require_once($_SERVER['DOCUMENT_ROOT']."/lib/config.php");
require_once($_SERVER['DOCUMENT_ROOT']."/lib/functions.php");

//For NLE Exporter, default REQUEST set
$lang = 'ko';

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
    <script type="text/javascript" src="/lib/extjs/src/locale/ext-lang-<?=$lang?>.js"></script>

    <script type="text/javascript" src="/javascript/jquery-1.9.1.min.js"></script>


    <!--Custom Menu Pages -->
    <?php

    if (defined('CUSTOM_ROOT') && class_exists('\ProximaCustom\core\ScriptManager')) {
        $scripts = \ProximaCustom\core\ScriptManager::getCustomScripts(false);
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
			items:[{
				region: 'center',
				xtype: 'panel',
				layout: 'fit',			
				style : {					
				},
                id: 'regist_form_tab',
                //autoScroll: true,
				//defaults:{autoHeight: true},
				frame:true,				
				items: [{
					xtype: 'tabpanel',
					id: 'regist_form_tabpanel',
					activeTab: 0,				
					frame:true,
                    isFirst: true,
                    defaults : {
                        labelSeparator : '',
                        anchor:  '95%'
                    },
					listeners:{
						afterrender:function(self) {
							
						}
					},
                    items:[],
					loadFormMetaData: function(self, params ){
                        var myMask = new Ext.LoadMask(Ext.getBody(), {msg:"로딩중입니다..."});
                        myMask.show();
			               
						params = params || {};									
						params.lang = '<?=$lang?>';

						Ext.Ajax.request({
							url: 'get_metadata.php',
							params: params,
							callback: function(opts, success, response){
                                myMask.hide();
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
						
						params = params || {};
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
                    },                    
                    getCustomForm: function(num){
                        return this.get(num).getForm();
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
            
            if( i ==0 && Ext.getCmp('regist_form_tab').ownerCt.get('bbar_form') ){
                var addValues = Ext.getCmp('regist_form_tab').ownerCt.get('bbar_form').getForm().getValues();
                p.use_prhibt_at = addValues.use_prhibt_at;
                p.k_codec       = addValues.k_codec;
                p.k_send_to_main       = addValues.k_send_to_main;
                p.k_send_to_sub       = addValues.k_send_to_sub;
                p.k_qc_confirm       = addValues.k_qc_confirm;
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