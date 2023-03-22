<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lang.php');

$ord_id = $_REQUEST['ord_id'];

$title_ = addslashes($_REQUEST['title']);
$title = str_replace("\r", '', str_replace("\n", '\\n', $title_));
$ord_ctt_b = $_REQUEST['ord_ctt'];
$ord_work_id = $_REQUEST['ord_work_id'];
$rd_id = $_REQUEST['rd_id'];
$rd_seq = $_REQUEST['rd_seq'];

if(empty($artcl_id) && !empty($rd_id) ){
	$type = 'show_detail_rundown';
	$artcl_id = $_REQUEST['rd_id'];
}else{
	$type = 'show_detail';
	$artcl_id = $_REQUEST['artcl_id'];
}

$ord_work_name = $db->queryOne("SELECT USER_NM FROM BC_MEMBER WHERE USER_ID = '".$ord_work_id."' ");
$value = addslashes($ord_ctt_b);
$ord_ctt = str_replace("\r", '', str_replace("\n", '\\n', $value));
$ord_meta_cd = $_REQUEST['ord_type'];
if($ord_meta_cd == _text('MN02088')){//'그래픽'
	$check_hidden = 'hidden : true,';
	$check_hidden_g = '';
	$height = 560;
}else{
	$check_hidden = '';
	$check_hidden_g = 'hidden : true,';
	$height = 580;
}

if( strstr($ord_id, 'POR' )){
	$hidden_article_info = 'hidden : true,';
	$height = 230;
}

?>

(function(records){
	function searchUser(){
		var search = new Ext.Window({
			title: '<?=_text('MN00190')?>',
			modal:true,
			width: 400,
			height: 400,
			layout: 'fit',
			items: [{
				xtype: 'form',
				layout: 'fit',
				border:false,
				buttonAlign: 'center',
				buttons:[
				{
					text: '<span style="position:relative;top:1px;"><i class="fa fa-check" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00046'),//save
					scale: 'medium',
					handler:function(){
						var sel = Ext.getCmp('user_list').getSelectionModel().getSelected();
						Ext.getCmp('editor_id').setValue(sel.get('user_id'));
						Ext.getCmp('editor_name').setValue(sel.get('user_nm'));
						search.destroy();
					}
				},{
					text: '<span style="position:relative;top:1px;"><i class="fa fa-close" style="font-size:13px;"></i></span>&nbsp;'+ _text('MN00031'),//close
					scale: 'medium',
					handler: function(self){
						self.ownerCt.ownerCt.ownerCt.destroy();
					}
				}],
				items: [
					new Ext.grid.GridPanel({
						id: 'user_list',
						cls: 'proxima_customize',
						border: false,
						store: user_search_store,
						loadMask: true,
						listeners: {
							viewready: function(self){
								self.getStore().load({
									params: {
										start: 0,
										limit: 20
									}
								});
							},
							rowdblclick: function (self, row_index, e) {
								var sel = Ext.getCmp('user_list').getSelectionModel().getSelected();
								viewUser(sel, search);
							}
						},
						colModel: new Ext.grid.ColumnModel({
							defaults: {
								sortable: true
							},
							columns: [
								new Ext.grid.RowNumberer(),
								{header: 'number', dataIndex:'member_id',hidden:'true'},
								{header: '<?=_text('MN00195')?>', dataIndex: 'user_id',	align:'center' },
								{header: '<?=_text('MN00223')?>',   dataIndex: 'user_nm',		align:'center'},
								{header: '<?=_text('MN00181')?>',   dataIndex: 'dept_nm',	align:'center'}
							]
						}),
						viewConfig: {
							forceFit: true
						},
						sm: new Ext.grid.RowSelectionModel({
							singleSelect: true
						}),
						tbar: [{
							xtype: 'combo',
							id: 'search_f',
							width: 120,
							triggerAction: 'all',
							editable: false,
							mode: 'local',
							store: [
								['s_created_time', '<?=_text('MN00102')?>'],
								['s_user_id', '<?=_text('MN00195')?>'],
								['user_nm', '<?=_text('MN00223')?>'],
								['s_dept_nm','<?=_text('MN00181')?>']
							],
							value: 'user_nm',
							listeners: {
								select: function(self, r, i){
									if (i == 0)
									{
										self.ownerCt.get(2).setVisible(true);
										self.ownerCt.get(3).setVisible(false);
									}
									else
									{
										self.ownerCt.get(3).setVisible(true);
										self.ownerCt.get(2).setVisible(false);
									}
								}
							}
						},' ',{
							hidden: true,
							xtype: 'datefield',
							id: 'search_v1',
							format: 'Y-m-d',
							width: 100,
							listeners: {
								render: function(self){
									self.setValue(new Date());
								}
							}
						},{

							allowBlank: false,
							xtype: 'textfield',
							id: 'search_v2',
							listeners: {
								specialKey: function(self, e){
									var w = self.ownerCt.ownerCt;
									if (e.getKey() == e.ENTER && self.isValid())
									{
										e.stopEvent();
										w.doSearch(w.getTopToolbar(), user_search_store);
									}
								}
							}
						},' ',{
							xtype: 'button',
							cls: 'proxima_button_customize',
							width: 30,
							text: '<span style="position:relative;" title="'+_text('MN00037')+'"><i class="fa fa-search" style="font-size:13px;color:white;"></i></span>',
							handler: function(b, e){
								var w = b.ownerCt.ownerCt;
								w.doSearch(w.getTopToolbar(), user_search_store);
							}
						},{
							cls: 'proxima_button_customize',
							width: 30,
							text: '<span style="position:relative;" title="'+_text('MN00139')+'"><i class="fa fa-refresh" style="font-size:13px;color:white;"></i></span>',
							handler: function(btn, e){
								Ext.getCmp('user_list').getStore().load({
										params:{
											start:0,
											limit:20
										}
								});
							}
						}],
						bbar: new Ext.PagingToolbar({
							store: user_search_store,
							pageSize: 20
						}),
						doSearch: function(tbar, store){
							var combo_value = tbar.get(0).getValue(),
								params = {};
								params.start = 0;
								params.limit = 20;

							if (combo_value == 's_created_time')
							{
								params.search_field = combo_value;
								params.search_value = tbar.get(2).getValue().format('Y-m-d');
							}
							else
							{
								params.search_field = combo_value;
								params.search_value = tbar.get(3).getValue();
							}
							if(Ext.isEmpty(params.search_field) || Ext.isEmpty(params.search_value)){
								Ext.Msg.alert('<?=_text('MN00023')?>', '<?=_text('MSG00007')?>');
							}else{
								Ext.getCmp('user_list').getStore().load({
									params: params
								});
							}
						}
					})
				]}
			]
		})	.show();
	}

	function viewUser(sel, win){
		Ext.getCmp('editor_id').setValue(sel.get('user_id'));
		Ext.getCmp('editor_name').setValue(sel.get('user_nm'));
		win.destroy();
	}

	function setUser(worker_id){
		Ext.Ajax.request({
            url: '/store/request_zodiac/request_list.php',
            params: {
                ord_id : '<?=$_REQUEST['ord_id']?>',
				action : 'set_user',
				worker_id : worker_id,
				user_id  : '<?=$_SESSION['user']['user_id']?>'
            },
            callback: function (options, success, response) {
                if (success) {
                    try {
                        var r = Ext.decode(response.responseText);
						if(r.success){
							//Ext.Msg.alert('알림','저장이 완료되었습니다');
							//Ext.Msg.alert( _text('MN00023'), _text('MSG00058'));
							Ext.getCmp('request_list').getStore().reload();
						}
                    } catch (e) {
                        Ext.Msg.alert(e.name, e.message);
                    }
                } else {
                    Ext.Msg.alert( _text('MN01098'), response.statusText);//'서버 오류'
                }
            }
        });
	}

	function showContent(self){
		var sm = self.getSelectionModel().getSelected();

		var content_id = sm.get('content_id');

		//>>self.load = new Ext.LoadMask(Ext.getBody(), {msg: '상세 정보를 불러오는 중입니다...'});
		self.load = new Ext.LoadMask(Ext.getBody(), {msg: _text('MSG00143')});
		self.load.show();
		var that = self;

		if ( !Ext.Ajax.isLoading(self.isOpen) )
		{
			self.isOpen = Ext.Ajax.request({
				url: '/javascript/ext.ux/Ariel.DetailWindow.php',
				params: {
					content_id: content_id,
					record: Ext.encode(sm.json)
				},
				callback: function(self, success, response){
					if (success)
					{
						that.load.hide();
						try
						{
							var r = Ext.decode(response.responseText);

							if ( r !== undefined && !r.success)
							{
								Ext.Msg.show({
									title: _text('MN00021')//'경고'
									,msg: r.msg
									,icon: Ext.Msg.WARNING
									,buttons: Ext.Msg.OK
								});
							}
						}
						catch (e)
						{
							//alert(response.responseText)
							//Ext.Msg.alert(e['name'], e['message'] );
						}
					}
					else
					{
						//>>Ext.Msg.alert('서버 오류', response.statusText+'('+response.status+')');
						Ext.Msg.alert('<?=_text('MN00022')?>', response.statusText+'('+response.status+')');
					}
				}
			});
		} else {
            that.load.hide();
        }
	}

	var user_search_store = new Ext.data.JsonStore({
		url: '/pages/menu/config/user/php/get.php',
		remoteSort: true,
		sortInfo: {
			field: 'user_id',
			direction: 'ASC'
		},
		idProperty: 'member_id',
		root: 'data',
		fields: [
			'member_id',
			'user_id',
			'user_nm',
			'group',
			'occu_kind',
			'job_position',
			'job_duty',
			'dep_tel_num',
			'breake',
			'dept_nm',
			{name: 'created_time', type: 'date', dateFormat: 'YmdHis'},
			{name: 'last_login', type: 'date', dateFormat: 'YmdHis'},
			{name: 'hired_date', type: 'date', dateFormat: 'YmdHis'},
			{name: 'retire_date', type: 'date', dateFormat: 'YmdHis'}
		],
		listeners: {
			exception: function(self, type, action, opts, response, args){
				try {
					var r = Ext.decode(response.responseText);
					if(!r.success) {
						Ext.Msg.alert('<?=_text('MN00023')?>', r.msg);
					}
				}
				catch(e) {
					Ext.Msg.alert('<?=_text('MN00022')?>', e);
				}
			}
		}
	});

	var store_article = new Ext.data.JsonStore({
		url: '/store/request_zodiac/request_list.php',
		root: 'data',
		totalProperty: 'total',
		idProperty: 'ord_id',
		autoLoad : true,
		fields:[
			{ name : 'ord_id' },
			{ name : 'ord_ctt' },
			{ name : 'ord_div_cd' },
			{ name : 'inputr_id' },
			{ name : 'inputr_name' },
			{name: 'worker', convert: function(v, record) {
				if(Ext.isEmpty(record.inputr_name)){
					record.inputr_name = '-';
				}
				return record.inputr_id + '(' + record.inputr_name + ')';
			}},
			{name: 'register', convert: function(v, record) {
				if(Ext.isEmpty(record.ord_work_name)){
					return '';
				}else{
					return record.ord_work_id + '(' + record.ord_work_name + ')';
				}

			}},
			{ name : 'ord_meta_cd' },
			{ name : 'dept_cd' },
			{ name : 'dept_name' },
			{ name : 'ord_status' },
			{ name : 'title' },
			{ name : 'ch_div_cd' },
			{ name : 'ord_work_ctt' },
			{ name : 'artcl_id' },
			{ name : 'rd_id' },
			{ name : 'rd_seq' },
			{ name : 'updtr_id' },
			{ name : 'ord_work_id' },
			{ name : 'ord_work_name' },
			{ name : 'input_dtm', type:'date', dateFormat:'YmdHis' },
			{ name : 'updt_dtm', type:'date', dateFormat:'YmdHis' },
			{ name : 'expt_ord_end_dtm', type:'date', dateFormat:'YmdHis' }
		],
		listeners: {
			beforeload: function(self, opts){
				opts.params = opts.params || {};

				Ext.apply(opts.params, {
					action : 'list_article',
					ord_id : '<?=$ord_id?>'
				});
			},
			load: function(store, records, opts){
				//myMask.hide();
			}
		}
	});

	var store_edl = new Ext.data.JsonStore({
		url: '/store/request_zodiac/request_list.php',
		root: 'data',
		totalProperty: 'total',
		autoLoad : true,
		fields:[
			{ name : 'ord_id' },
			{ name : 'mark_in' },
			{ name : 'mark_out' },
			{ name : 'edl_titl' },
			{ name : 'video_id' },
			{ name : 'content_id' },
			{ name : 'ord_no' }
		],
		listeners: {
			beforeload: function(self, opts){
				opts.params = opts.params || {};

				Ext.apply(opts.params, {
					action : 'list_edl',
					ord_id : '<?=$ord_id?>'
				});
			},
			load: function(store, records, opts){
				//myMask.hide();
			}
		}
	});

	var store_video = new Ext.data.JsonStore({
		url: '/store/request_zodiac/request_list.php',
		root: 'data',
		totalProperty: 'total',
		autoLoad : true,
		fields:[
			{ name : 'ord_id' },
			{ name : 'content_id' },
			{ name : 'title' }
		],
		listeners: {
			beforeload: function(self, opts){
				opts.params = opts.params || {};

				Ext.apply(opts.params, {
					action : 'list_video',
					ord_id : '<?=$ord_id?>',
					type : '<?=$ord_meta_cd?>'
				});
			},
			load: function(store, records, opts){
				//myMask.hide();
			}
		}
	});

	var store_file = new Ext.data.JsonStore({
		url: '/store/request_zodiac/request_list.php',
		root: 'data',
		totalProperty: 'total',
		autoLoad : true,
		fields:[
			{ name : 'ord_id' }
		],
		listeners: {
			beforeload: function(self, opts){
				opts.params = opts.params || {};

				Ext.apply(opts.params, {
					action : 'list_file',
					ord_id : '<?=$ord_id?>'
				});
			},
			load: function(store, records, opts){
				//myMask.hide();
			}
		}
	});

	var label_width = 50;

	//form
	var form_editor = new Ext.FormPanel({
		//xtype: 'form',
		id : 'form_user',
		frame: false,
		width: '100%',
		style: {
			paddingTop: '5px',
			background : 'white'
		},
		flex : 1,
		height: 30,
		border: false,
		labelWidth: 1,
		defaults: {
			labelStyle: 'text-align:center;',
			anchor: '100%'
		},
		items:[{
			xtype: 'compositefield',
			style: {
				background : 'white'
			},
			items:[{
				xtype: 'displayfield',
				width : label_width,
				value: '*'+_text('MN02102')//편집자
			},{
				xtype: 'textfield',
				id : 'editor_name',
				readOnly : true,
				value : '<?=$ord_work_name?>',
				width : 115
			},{
				xtype: 'textfield',
				disabled : true,
				id : 'editor_id',
				value : '<?=$ord_work_id?>',
				width : 110
			},{
				xtype: 'button',
				cls: 'proxima_button_customize',
				id: 'request_editing_request_search_user_btn',
				width: 30,
				height: 30,
				style:{
					top: '-4'
				},
				text : '<span style="position:relative;top:1px;" title="'+_text('MN00190')+'"><i class="fa fa-search" style="font-size:13px;color:white;"></i></span>',//'사용자 조회'
				//width : 80,
				handler: function(btn, e){
					searchUser();
				}
			},{
				xtype : 'displayfield',
				width : 5
			},{
				xtype: 'button',
				hidden : true,
				text : '<span style="position:relative;top:1px;"><i class="fa fa-check" style="font-size:13px;"></i></span>&nbsp;'+_text('MN02101'),//'편집자 저장'
				width : 80,
				handler: function(btn, e){
					if(Ext.isEmpty(Ext.getCmp('editor_id').getValue())){
						Ext.Msg.alert( _text('MN00023'), _text('MSG02027'));
					}else if(Ext.isEmpty('<?=$_SESSION['user']['user_id']?>')){
						Ext.Msg.alert( _text('MN00023'), _text('MSG01001'));
					}else{
						setUser(Ext.getCmp('editor_id').getValue());
					}
				}
			}]
		}]
	});

	var form_news = new Ext.FormPanel({
		//xtype: 'form',
		<?=$hidden_article_info?>
		labelSeparator: '',
		//title : _text('MN02099'),//'기사'
		id : 'form_article',
		flex : 1,
		height : 330,
		width : '100%',
		style: {
			//paddingTop: '8px',
			background : 'white'
		},
		frame: false,
		border : false,
		labelWidth: label_width,
		padding: 10,
		defaults: {
			anchor: '99%'
		},
		//autoScroll: true,
		items:[{
			xtype: 'textfield',
			name : 'title',
			id : 'article_title',
			fieldLabel : '*'+_text('MN00249'),//제목
			value : '',
			readOnly : true
		},{
			xtype : 'textarea',
			fieldLabel : '*'+_text('MN02103'),//기사내용
			name : 'detail',
			height: 280,
			value : '',
			listeners: {
				afterrender : function(self, e){
					Ext.Ajax.request({
						url: '/store/request_zodiac/get_article.php',
						params: {
							action : '<?=$type?>',
							artcl_id : '<?=$artcl_id?>',
							rd_seq : '<?=$rd_seq?>'
						},
						callback: function(opt, success, response){
							try{
								var r = Ext.decode(response.responseText);
								if(r.success){
									self.setValue(r.data.artcl_ctt);
									Ext.getCmp('article_title').setValue(r.data.artcl_titl);
								}else{
									Ext.Msg.alert( _text('MN00023'),r.msg);
								}
							}catch (e){
								//console.log(e);
							}
						}
					});

				}
			},
			readOnly : true
		},{
			xtype : 'textfield',
			name : 'ord_id',
			value : '<?=$ord_id?>',
			hidden : true
		}]
	});

	var form_request = new Ext.FormPanel({
		//xtype: 'form',
		//title : _text('MN00095'),//'의뢰'
		labelSeparator: '',
		id : 'form_request',
		width: '100%',
		style: {
			paddingTop: '8px',
			background : 'white'
		},
		frame: false,
		flex : 1,
		height: 300,
		border: false,
		labelWidth: label_width,
		padding: 5,
		defaults: {
			anchor: '99%'
		},
		//autoScroll: true,
		items:[{
			xtype: 'textfield',
			name : 'title',
			fieldLabel : '*'+_text('MN00249'),//제목
			value : '<?=$title?>'
		},{
			xtype : 'textarea',
			fieldLabel : '*'+_text('MN02093'),//업무의뢰내역
			name : 'detail',
			height : 245,
			value : '',
			listeners: {
				beforerender : function(self, e){
					self.setValue('<?=$ord_ctt?>');
				}
			}
		},{
			xtype : 'textfield',
			name : 'ord_id',
			value : '<?=$ord_id?>',
			hidden : true
		}]
	});

	var grid_content = new Ext.grid.GridPanel({
		title : '<?=$ord_meta_cd?>',
		<?=$hidden_article_info?>
		<?=$check_hidden_g?>
		//xtype: 'grid',
		loadMask: true,
		cls: 'proxima_customize',
		enableDD: false,
		border : false,
		frame : false,
		store: store_video,
		autoScroll: true,
		height: 160,
		flex: 3,
		plain: true,

		selModel: new Ext.grid.RowSelectionModel({
			singleSelect: true,
			listeners: {
				rowselect: function(self){
				},
				rowdeselect: function(self){
				}
			}
		}),
		view: new Ext.ux.grid.BufferView({
			scrollDelay: false,
			forceFit : true,
			emptyText: _text('MSG00148')//결과 값이 없습니다
		}),
		listeners: {
			rowdblclick: function(self, rowIndex, e){
				showContent(self);
			}
		},
		cm: new Ext.grid.ColumnModel({
			defaults: {
				sortable: true,
				align: 'center'
			},
			columns: [
				new Ext.grid.RowNumberer(),
				{header: 'ID', dataIndex: 'ord_id', width: 60, hidden: true},
				//{header: '<?=$ord_meta_cd?>ID', dataIndex: 'content_id', width: 60},
				{header: _text('MN00249'), dataIndex: 'title', width: 60}//'제목'
			]
		})
	});

	var grid_edl = new Ext.grid.GridPanel({
		title : 'EDL',
		//xtype: 'grid',
		<?=$hidden_article_info?>
		<?=$check_hidden?>
		loadMask: true,
		cls: 'proxima_customize',
		enableDD: false,
		store: store_edl,
		height: 160,
		border : false,
		autoScroll: true,
		//frame : false,
		flex: 3,
		plain: true,
		tbar : ['Drag:',{
				xtype: 'component',
				width: 50,
				html: '<img align="center" id="edl" src="/led-icons/download_bicon.png" width="17px" title="<?=_text('MSG02029')?>" />',//EDL Import시 드래그해주세요.
				listeners: {
					afterrender: function(self){
						var ori_ext= 'xml';
						var filename = '<?=$ord_id?>';
						var root_path= '<?=ATTACH_ROOT?>';
						var path = 'application/'+ori_ext+':file:///' + root_path+'/EDL/' +filename +'.'+ ori_ext;

						//데이터뷰에서 각 이미지에 설정해놓은 ID로 객체를 찾는다
						if(Ext.isChrome){
							var thumb_img = document.getElementById('edl');
							if( !Ext.isEmpty(thumb_img) ){
								//객체가 있을경우 URL 셋팅 - 섬네일용
								thumb_img.addEventListener("dragstart",function(evt){
									evt.dataTransfer.setData("DownloadURL",path);
								},false);
							}
						}
					}
				}
			},'->',{

			style: {
				marginLeft: '5px'
			},
			cls: 'proxima_button_customize',
			width: 30,
			text : '<span style="position:relative;top:1px;" title="'+_text('MN00050')+'"><i class="fa fa-download" style="font-size:13px;color:white;"></i></span>',//'다운로드'
			handler : function(btn, e){
				var getStore = btn.ownerCt.ownerCt.getStore();

				if( getStore.getCount()  < 1 ){
					Ext.Msg.alert( _text('MN00023'), _text('MSG02028'));
					return;
				}else{
					var ord_id =  '<?=$ord_id?>';
					window.open('/store/request_zodiac/getEDL.php?ord_id='+ord_id);
				}
			}
		}],
		selModel: new Ext.grid.RowSelectionModel({
			singleSelect: true,
			listeners: {
				rowselect: function(self){
				},
				rowdeselect: function(self){
				}
			}
		}),
		view: new Ext.ux.grid.BufferView({
			scrollDelay: false,
			forceFit : true,
			emptyText: _text('MSG00148')//결과 값이 없습니다
		}),
		listeners: {
			rowdblclick: function(self, rowIndex, e){
				showContent(self);
			}
		},
		cm: new Ext.grid.ColumnModel({
			defaults: {
				sortable: true,
				align: 'center'
			},
			columns: [
				new Ext.grid.RowNumberer(),
				{header: _text('MN00249'), dataIndex: 'edl_titl', width: 120},//'제목'
				{header: 'Script ID', dataIndex: 'ord_id', width: 60, hidden: true},
				{header: _text('MN02105'), dataIndex: 'mark_in', width: 60},//'시작'
				{header: _text('MN02104'), dataIndex: 'mark_out', width: 60},//'종료'
				{header: _text('MN02087')+'ID', dataIndex: 'video_id', width: 60, hidden : true}//'비디오ID'
			]
		})
	});

	var grid_file = new Ext.grid.GridPanel({
		title : _text('MN01045'),//'첨부파일'
		<?=$hidden_article_info?>
		//xtype: 'grid',
		loadMask: true,
		cls: 'proxima_customize',
		enableDD: false,
		store: store_file,
		height: 160,
		border : false,
		autoScroll: true,
		frame : false,
		flex: 4,
		plain: true,

		selModel: new Ext.grid.RowSelectionModel({
			singleSelect: false,
			listeners: {
				rowselect: function(self){
				},
				rowdeselect: function(self){
				}
			}
		}),
		view: new Ext.ux.grid.BufferView({
			scrollDelay: false,
			forceFit : true,
			emptyText: _text('MSG00148')//결과 값이 없습니다
		}),
		listeners: {
		},
		tbar : ['->',{
			xtype : 'button',
			cls: 'proxima_button_customize',
			width: 30,
			text : '<span style="position:relative;top:1px;" title="'+_text('MN00050')+'"><i class="fa fa-download" style="font-size:13px;color:white;"></i></span>',//'다운로드'
			handler: function(btn, e){
				if(btn.ownerCt.ownerCt.selModel.getSelections().length < 1 ){
					Ext.Msg.alert( _text('MN00023'), _text('MSG02001'));
					return;
				}else{
					Ext.Msg.alert( _text('MN00023'), _text('MSG02030'));
				}
			}
		}],
		cm: new Ext.grid.ColumnModel({
			defaults: {
				sortable: true,
				align: 'center'
			},
			columns: [
				new Ext.grid.RowNumberer(),
				{header: 'ID', dataIndex: 'ord_id', width: 60}
			]
		})
	});

	var panel_left = new Ext.Panel({
		layout : 'vbox',
		cls: 'change_background_panel',
		title : _text('MN02099'),//'news'
		flex : 1,
		frame: false,
		border : true,
		height : <?=$height?>-60,
		items : [form_news, grid_edl,grid_content]
	});

	var panel_right = new Ext.Panel({
		layout : 'vbox',
		cls: 'change_background_panel',
		title : _text('MN00095'),//'의뢰'
		flex : 1,
		frame: false,
		border : true,
		height : <?=$height?>-60,

		items : [form_editor, form_request,grid_file]
	});

	var hbox_panel = new Ext.Panel({
		layout : 'hbox',
		margins : '100 0 0 0',
		frame: false,
		border : false,
		items : [panel_left, panel_right]
	});
function get_request_count_info(){
	 return Ext.Ajax.request({
            url: '/store/request_zodiac/request_list.php',
            params: {
                action: 'get_total_new_count'
            },
            
            callback: function(opts, success, response) {
              
                try {
                   var r = Ext.decode(response.responseText, true);
                   if (r.success) {
                	   var total_new_request_count = r.total_new_request_count;
                	   var total_new_video_count = r.total_new_video_count;
                	   var total_new_graphic_count = r.total_new_graphic_count;
                	   
                	   if(total_new_request_count > 0){
                	   		Ext.fly('total_new_request').dom.innerHTML = '<font size=2px color="#FFFFFF" class="new_count_icon"><b>&nbsp'+total_new_request_count+'&nbsp</b></font>';
                	   }else{
                	   		Ext.fly('total_new_request').dom.innerHTML = '';
                	   }
                	   
                	   if(total_new_video_count > 0){
                	   		Ext.fly('total_new_request_video').dom.innerHTML = '<font size=2px color="#FFFFFF" class="new_count_icon"><b>&nbsp'+total_new_video_count+'&nbsp</b></font>';
                	   }else{
                	   		Ext.fly('total_new_request_video').dom.innerHTML = '';
                	   }
                	   
                	   if(total_new_graphic_count > 0){
                	   		Ext.fly('total_new_request_graphic').dom.innerHTML = '<font size=2px color="#FFFFFF" class="new_count_icon"><b>&nbsp'+total_new_graphic_count+'&nbsp</b></font>';
                	   }else{
                	   		Ext.fly('total_new_request_graphic').dom.innerHTML = '';
                	   }
                	   
                   } else {
                      Ext.Msg.alert(_text('MN00022'), r.msg);
                   }
                   
                } catch(e) {
                   alert(_text('MN01098'), e.message + '(responseText: ' + response.responseText + ')');
                }
             }
        });
	}



	var win = new Ext.Window({
		id: 'requestDetail',
		title: '<?=$ord_meta_cd?>'+' '+ _text('MN02101')+' '+ _text('MN02091'),//' 편집 의뢰'
		modal: true,
		autoScroll: true,
		height: <?=$height?>,
		width: 800,
		layout:	'fit',
		border: false,
		items: [hbox_panel],
		buttonAlign : 'center',
		listeners: {
			close: function(){
				Ext.getCmp('request_list').getStore().reload();
				get_request_count_info();
			}
		},
		buttons : [{
			text : '<span style="position:relative;top:1px;"><i class="fa fa-edit" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00044'),//update
			scale: 'medium',
			handler : function(self, e){
				Ext.Msg.show({
					title : _text('MN00023'),//'알림'
					msg : _text('MSG00175'),//'수정 하시겠습니까?'
					buttons: Ext.Msg.OKCANCEL,
					fn: function(btnID){
						if (btnID == 'ok') {
							Ext.Ajax.request({
								url: '/store/request_zodiac/request_list.php',
								params: {
									action : 'update_request',
									values : Ext.encode(Ext.getCmp('form_request').getForm().getFieldValues()),
									editor : Ext.getCmp('editor_id').getValue()
								},
								callback: function(opt, success, response){
									var r = Ext.decode(response.responseText);
									if(r.success){
										win.destroy();
										Ext.getCmp('request_list').getStore().reload();
									}else{
										Ext.Msg.alert( _text('MN01098'), response.statusText);//'서버 오류'
									}
								}
							});
						}
					}
				});

			}
		},{
			text : '<span style="position:relative;top:1px;"><i class="fa fa-close" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00004'),//cancel
			scale: 'medium',
			handler : function(self, e){
				Ext.getCmp('request_list').getStore().reload();
				get_request_count_info();
				win.destroy();
			}
		}]
	});
	win.show();

})()