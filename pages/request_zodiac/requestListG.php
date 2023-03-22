<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lang.php');

$type_content = $_REQUEST['type_content'];

?>

(function(records){
	function searchUser(){
		var search = new Ext.Window({
			//>>title: '사용자 검색',MN00190
			title: '<?=_text('MN00190')?>',
			modal:true,
			width: 400,
			height: 400,
			layout: 'fit',
			items: [{
				xtype: 'form',
				layout: 'fit',
				border:false,
				buttons:[
				{
					//icon: '/led-icons/accept.png',
					text: '<span style="position:relative;top:1px;"><i class="fa fa-check" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00046'),//'저장'
					handler:function(){
						var sel = Ext.getCmp('user_list').getSelectionModel().getSelected();
						viewUser(sel, search);
					}
				},{
					//icon:'/led-icons/cancel.png',
					text: '<span style="position:relative;top:1px;"><i class="fa fa-close" style="font-size:13px;"></i></span>&nbsp;'+ _text('MN00031'),//'닫기'
					handler: function(self){
						self.ownerCt.ownerCt.ownerCt.destroy();
					}
				}],
				items: [
					new Ext.grid.GridPanel({
						id: 'user_list',
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
								//>>{header: 'number', dataIndex:'member_id',hidden:'true'},
								//>>{header: '사번', dataIndex: 'user_id',	align:'center' },
								//>>{header: '성명',   dataIndex: 'name',		align:'center'},
								//>>{header: '부서',   dataIndex: 'dept_nm',	align:'center'}

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
							width: 70,
							triggerAction: 'all',
							editable: false,
							mode: 'local',
							store: [
								//>>['s_created_time', '등록일자'],
								//>>['s_user_id', '아이디'],
								//>>['s_name', '이름'],
								//>>['s_dept_nm','부서']

								['s_created_time', '<?=_text('MN00102')?>'],
								['s_user_id', '<?=_text('MN00195')?>'],
								['user_nm', '<?=_text('MN00223')?>'],
								['s_dept_nm','<?=_text('MN00181')?>']
							],
							value: 's_created_time',
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
							xtype: 'datefield',
							id: 'search_v1',
							format: 'Y-m-d',
							listeners: {
								render: function(self){
									self.setValue(new Date());
								}
							}
						},{
							hidden: true,
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
							//icon:'/led-icons/magnifier_zoom_in.png',
							xtype: 'button',
							text: '<span style="position:relative;top:1px;"><i class="fa fa-search" style="font-size:13px;"></i></span>&nbsp;'+'<?=_text('MN00037')?>',
							text: '<?=_text('MN00037')?>',
							handler: function(b, e){
								var w = b.ownerCt.ownerCt;
								w.doSearch(w.getTopToolbar(), user_search_store);
							}
						},'-',{
							//icon: '/led-icons/arrow_refresh.png',
							//>>text: '새로고침',MN00229
							text: '<span style="position:relative;top:1px;"><i class="fa fa-refresh" style="font-size:13px;"></i></span>&nbsp;'+'<?=_text('MN00139')?>',
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
								//>>Ext.Msg.alert('정보', '검색어를 입력해주세요.');
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
							//Ext.Msg.alert('알림','저장 되었습니다.');
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
						//>>Ext.Msg.alert('정보', r.msg);
						Ext.Msg.alert('<?=_text('MN00023')?>', r.msg);
					}
				}
				catch(e) {
					//>>Ext.Msg.alert('디코드 오류', e);
					Ext.Msg.alert('<?=_text('MN00022')?>', e);
				}
			}
		}
	});

	var winCreateRequest = new Ext.Window({
		id: 'requestCreate',
		title: '<?=$type_content?>'+ _text('MN02091'),//' 편집 의뢰'
		modal: true,
		//width: '60%',
		autoScroll: true,
		height: 250,
		width: 600,
		layout:	'fit',
		border: false,
		items: [{
			xtype: 'form',
			//hidden : true,
			id : 'form_create_request',
			frame: true,
			flex : 1,
			border: false,
			buttonAlign : 'center',
			labelWidth: 80,
			labelSeparator: '',
			defaults: {
				labelStyle: 'text-align:left;',
				anchor: '95%'
			},
			autoScroll: true,
			items:[{
				xtype: 'compositefield',
				fieldLabel :'*'+_text('MN02102'),//편집자
				items:[{
					xtype: 'textfield',
					id : 'editor_name',
					readOnly : true,
					value : '',
					width : 100
				},{
					xtype: 'textfield',
					readOnly : true,
					id : 'editor_id',
					value : '',
					width : 70
				},{
					xtype: 'button',
					text : '<span style="position:relative;top:1px;"><i class="fa fa-search" style="font-size:13px;"></i></span>&nbsp;'+'<?=_text('MN00190')?>',//'사용자 조회'
					width : 50,
					handler: function(btn, e){
						searchUser();
					}
				},{
					xtype : 'displayfield',
					width : 5
				},{
					xtype: 'button',
					text : '<span style="position:relative;top:1px;"><i class="fa fa-check" style="font-size:13px;"></i></span>&nbsp;'+_text('MN02101'),//'편집자 저장'
					hidden : true,
					width : 50,
					handler: function(btn, e){
						if(Ext.isEmpty(Ext.getCmp('editor_id').getValue())){
							//Ext.Msg.alert('알림','편집자를 선택하세요.');
							Ext.Msg.alert( _text('MN00023'), _text('MSG01003'));//알림, 먼저 사용자를 선택해주세요.
						}else if(Ext.isEmpty('<?=$_SESSION['user']['user_id']?>')){
							Ext.Msg.alert( _text('MN00023'), _text('MSG01001'));//알림, 로그인 해 주시기 바랍니다.
						}else{
							setUser(Ext.getCmp('editor_id').getValue());
						}
					}
				}]
			},{
				xtype: 'textfield',
				name : 'title',
				fieldLabel : '*'+_text('MN00249'),//제목
				value : ''
			},{
				xtype : 'textarea',
				fieldLabel : '*'+_text('MN02093'),//업무의뢰내역
				name : 'detail',
				value : '',
				listeners: {
					beforerender : function(self, e){
						self.setValue('');
					},
					afterrender:function(self){
						self.getEl().setStyle({
							//fontSize:'22px'
						})
					}
				}
			},{
				xtype : 'textfield',
				name : 'ord_id',
				value : '',
				hidden : true
			}],
			buttons:[]
		}],
		buttonAlign : 'center',
		buttons : [{
			text : '<span style="position:relative;top:1px;"><i class="fa fa-check" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00046'),//'저장'
			handler : function(self, e){
				Ext.Msg.show({
					title : _text('MN00023'),//'알림'
					msg : _text('MN00046')+' : '+_text('MSG02039'),//'저장 : 이 작업을 진행하시겠습니까?',
					buttons: Ext.Msg.OKCANCEL,
					fn: function(btnID){
						if (btnID == 'ok') {
							Ext.Ajax.request({
								url: '/store/request_zodiac/request_list.php',
								params: {
									action : 'create_request',
									values : Ext.encode(Ext.getCmp('form_create_request').getForm().getValues()),
									type_content : '<?=$type_content?>'
								},
								callback: function(opt, success, response){
									var r = Ext.decode(response.responseText);
									if(r.success){
										Ext.getCmp('request_list').getStore().reload();
										winCreateRequest.destroy();
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
			text : '<span style="position:relative;top:1px;"><i class="fa fa-close" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00031'),//'닫기'
			handler : function(self, e){
				Ext.getCmp('request_list').getStore().reload();
				winCreateRequest.destroy();
			}
		}]
	});
	winCreateRequest.show();

})()