<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lang.php');

?>
Ext.ns('Ariel.Nps');

function actionCallback(opts, success, response){
	var o = {}, store, record;

	if(true != success){
		Ext.Msg.show({
			//>>title: '실패',
			title: _text('MN00012'),
			msg: '',
			buttons: Ext.Msg.OK,
			closable: false
		});
		return;
	}

	try {
		result = Ext.decode(response.responseText);
		if (result && true == result.success) {
			switch (opts.params.action) {
				case 'create-folder':
					var n = opts.node;
					n.setId(result.id);
					delete n.attributes.isNew;
				break;
			}
		} else {
			//>>Ext.Msg.alert('오류', result.msg);

			switch (opts.params.action) {
				case 'create-folder':
					var n = opts.node;

					 n.remove(true);
				break;
			}
			Ext.Msg.alert(_text('MN00022'), result.msg);
		}
	} catch(e) {
		Ext.Msg.show({
			//>>title: '오류',
			title: _text('MN00022'),
			msg: e,
			buttons: Ext.Msg.OK,
			closable: false
		})
	}
}


Ariel.Nps.CList = Ext.extend(Ext.Panel, {
	layout: 'fit',
	autoScroll: true,
	initComponent: function(config){
		Ext.apply(this, config || {});

		var that = this;

		this.items = {
			xtype: 'grid',
			ddGroup: 'ContentDD',
			border: false,
			hideHeaders : true,
			autoScroll: true,
			enableDrag: false,
			enableDrop: true,
			enableDragDrop: false,

			reload: function( args ){
				this.store.reload({
					params: args
				});
			},
			store: new Ext.data.JsonStore({
				url: '/store/nps_work/temp.php',
				idProperty: 'content_id',
				root: 'data',
				totalProperty: 'total',
				autoLoad: true,
				listeners: {
					exception: function(self, type, action, opts, response, arg){
						if (type == 'response')
						{
							try
							{
								var r = Ext.decode(response.responseText);
								if (!r.success)
								{
									Ext.Msg.show({
										title: '경고'
										,msg: r.msg
										,icon: Ext.Msg.WARNING
										,buttons: Ext.Msg.OK
									});
								}
							}
							catch (e)
							{
								Ext.Msg.show({
									title: '경고'
									,msg: response.responseText
									,icon: Ext.Msg.WARNING
									,buttons: Ext.Msg.OK
								});
							}
						}
						else
						{
							Ext.Msg.show({
								title: '경고'
								,msg: '통신 오류'
								,icon: Ext.Msg.ERROR
								,buttons: Ext.Msg.OK
							});
						}
					}
				},
				baseParams: {
					action: 'listing',
					start: 0,
					limit: 20
				},
				fields: [
					{name: 'category_title'},
					{name: 'content_id'},
					{name: 'status'},
					{name: 'created_date', type:'date', dateFormat: 'YmdHis'},
					{name: 'thumb'},
					{name: 'highres_web_root'},
					{name: 'lowres_web_root'},
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
					{name: 'icons'},
					{name: 'ori_path'},
					{name: 'fields'}
				]
			}),
			viewConfig: {
				 forceFit: true,
				 emptyText: '콘텐츠를 드래그 앤 드랍 하세요'
			},
			sm: new Ext.grid.RowSelectionModel({
				listeners: {
					selectionchange: function(self, index, record){
					},
					rowselect : function(  self,  rowIndex,  record ) {

						var panel = self.grid.ownerCt.ownerCt.ownerCt.ownerCt.get(1);
						var work_request = self.grid.ownerCt.ownerCt.ownerCt.ownerCt.ownerCt;

						var type = work_request.get(0).get(0).get(0).getValue();


						work_request.getFormItems(record.get('content_id'),type, panel );
					}
				}
			}),
			cm: new Ext.grid.ColumnModel({
				defaults: {
					sortable: false
				},
				columns: [
					{header: '제목', dataIndex: 'title'}
				]
			}),
			listeners: {
				beforerender: {
					fn: function(self){

					}
				},
				afterrender: {
					fn: function(self){
						var dd = new Ext.dd.DropTarget(self.el, {
							ddGroup:'ContentDD',
							notifyDrop: function(dd, e, node){

								var grid = that.get(0);

								var panel = grid.ownerCt.ownerCt.ownerCt.ownerCt.get(1);
								var work_request = grid.ownerCt.ownerCt.ownerCt.ownerCt.ownerCt;
								var type = work_request.get(0).get(0).get(0).getValue();

								if(type == 'review'){
									var allow_review = false;
									Ext.each(node.selections, function(r){

										//심의 의뢰이면서 방송마스터가 아니라면 x
										if( r.get('ud_content_id') != '4000346' ){
											allow_review = true;
										}
									});
									if(allow_review){
										Ext.Msg.alert( _text('MN00023'), '심의 의뢰는 방송마스터만 가능합니다.');
										return false;
									}
								}

								var records = [];

								Ext.each(node.selections, function(r){
									records.push(r.data);

									if( Ext.isEmpty( self.getStore().getById(r.id ) ) )
									{
										self.getStore().add(  r );
									}
								});

								work_request.getFormItems(node.selections[0].get('content_id'),type, panel );

								return true;
							}
						});
					},
					scope: this
				},
				rowcontextmenu: function(self, rowIdx, e){
					e.stopEvent();

					return ;

					var ownerCt = self;

					var sm = self.getSelectionModel();
					if (!sm.isSelected(rowIdx)) {
						sm.selectRow(rowIdx);
					}
					var rs = [];
					var _rs = sm.getSelections();
					Ext.each(_rs, function(r, i, a){
						rs.push({
							content_id: r.get('content_id')
						});
					});

					var menu_f = new Ext.menu.Menu({
						items: [],
						listeners: {
							render: function(self){
							var content_id = sm.getSelected().get('content_id');

							}
						}
					});
					menu_f.showAt(e.getXY());
				}
			}
		};

		Ariel.Nps.CList.superclass.initComponent.call(this);
	}
});


Ariel.Nps.CWorkList = Ext.extend(Ext.Panel, {
	layout: 'fit',
	autoScroll: true,
	initComponent: function(config){
		Ext.apply(this, config || {});

		var that = this;

		this.items = {
			xtype: 'grid',
			border: false,
			hideHeaders : true,
			autoScroll: true,
			enableDrag: false,
			enableDrop: true,
			enableDragDrop: false,
			store: new Ext.data.JsonStore({
				url: '/store/nps_work/preview_user_list.php',
				autoLoad: true,
				idProperty: 'user_id',
				root: 'data',
				fields: [
					{name: 'user_id'},
					{name: 'user_nm'}
				]
			}),
			viewConfig: {
				 forceFit: true,
				 emptyText: '작업자를 드래그 앤 드랍 하세요'
			},
			sm: new Ext.grid.RowSelectionModel({
				listeners: {
					selectionchange: function(self, index, record){
					},
					rowselect : function(  self,  rowIndex,  record ) {
					}
				}
			}),
			cm: new Ext.grid.ColumnModel({
				defaults: {
					sortable: false
				},
				columns: [
					{ header: '작업자', dataIndex: 'user_nm' }
				]
			}),
			listeners: {
				afterrender: {
					fn: function(self){
						var dd = new Ext.dd.DropTarget(self.el, {
							ddGroup:'WorkerDD',
							notifyDrop: function(dd, e, node){

								var records = [];
								var typefield = self.ownerCt.ownerCt.ownerCt.items.get(0);

								if( Ext.isEmpty( typefield.getValue() ) )
								{
									Ext.Msg.alert( _text('MN00023'), '작업 형식을 선택해주세요.');
									return true;
								}

								if( typefield.getValue() == 'preview' )
								{
									if( ( node.node.leaf == true ) &&  ( node.node.ownerTree.title == '제작사용자' ) )
									{

									}
									else
									{
										Ext.Msg.alert( _text('MN00023'), '제작사용자만 할당 할 수 있습니다.');
										return true;
									}
								}
								else if( typefield.getValue() == 'review' )
								{
									if(  ( node.node.leaf == true ) &&  ( node.node.ownerTree.title == '심의 의뢰' )  )
									{

									}
									else
									{
										Ext.Msg.alert( _text('MN00023'), '심의자만 할당 할 수 있습니다.');
										return true;
									}
								}
								else
								{
									Ext.Msg.alert( _text('MN00023'), '알수없는 정보입니다');
									return true;
								}

								if( node.node.leaf == true )
								{
									var nodeid=node.node.id;
									var nodetext=node.node.text;

									var record = new Ext.data.Record({ user_id: nodeid, user_nm: nodetext });
									self.getStore().removeAll();
									self.getStore().add(record);
								}


								return true;
							}
						});
					},
					scope: this
				}
			}
		};

		Ariel.Nps.CWorkList.superclass.initComponent.call(this);
	}
});

Ariel.Nps.WorkRequest = Ext.extend(Ext.Window, {
	width: 800,
	layout: 'fit',
	height: 600,
	listeners: {
		hide: function(self){

			self.reset();
		}
	},
	initComponent: function(config){

		that = this;

		this.reset = function(){

			var eastpanel = this.get(0).get(1);

			var user = Ext.getCmp('worker');
			var request_contents = Ext.getCmp('request_contents');

			user.get(0).get(0).getStore().removeAll();
			request_contents.get(0).get(0).getStore().removeAll();
			eastpanel.removeAll();
		};

		this.items = [{
			layout: 'border',
			xtype: 'form',
			frame: true,
			buttonAlign : 'center',
			defaults : {
				border : false,
				autoScroll: true,
				frame: true,
				split: true,

				bodyStyle: 'margin-top: 10px;margin-right: 10px;margin-bottom: 10px;margin-left: 10px;'
			},
			items: [
			{
				region : 'center',
				xtype : 'panel',
				width : '45%',
				layout:'form',
				defaults : {
					anchor : '95%',
					padding : 5,
					labelSeparator : ''
				},
				items: [{
					xtype: 'combo',
					fieldLabel: '의뢰 형식',
					name: 'k_request_type',
					hiddenName: 'k_request_type',
					typeAhead: true,
					editable: false,
					triggerAction: 'all',
					lazyRender: true,
					mode: 'local',
					value: 'preview',
					store: new Ext.data.ArrayStore({
						fields: [
							'value',
							'displayText'
						],
						data: [['review', '심의'], ['preview', '프리뷰노트']]
					}),
					valueField: 'value',
					displayField: 'displayText',
					listeners: {
						select : function (self, record){

							that.reset();

							if( self.getValue() == 'preview' )
							{//프리뷰 통보 x
								Ext.getCmp('k_send_result').setVisible(false);
							}
							else if( self.getValue() == 'review' )
							{
								that.getMemberValue(that);
								Ext.getCmp('k_send_result').setVisible(true);
							}
						}
					}
				},{
					xtype: 'panel',
					ddGroup: 'ContentDD',
					fieldLabel: '작업자',
					id : 'worker',
					layout: 'fit',
					items: [
						new Ariel.Nps.CWorkList({
							height: 50,
							autoScroll: false
						})
					]
				},{
					xtype: 'panel',
					fieldLabel: '의뢰 목록',
					id : 'request_contents',
					layout: 'fit',
					items: [
						new Ariel.Nps.CList({
							height: 80,
							autoScroll: true
						})
					]
				},{
					xtype: 'fieldset',
					id: 'k_send_result',
					hidden: true,
					name: 'k_send_result',
					title: '작업진행에 대한 통보 허용',
					checkboxToggle: true,
					height: 140,
					defaults : {
						anchor : '95%',
						padding: 5,
						labelWidth: 30,
						labelSeparator: ''
					},
					items: [{
						xtype: 'displayfield',
						hideLabel: true,
						value: '*통보 허용 체크시 진행결과가 <br />아래의 연락처와 주소로 통보됩니다'
					},{
						xtype : 'textfield',
						name : 'k_email',
						vtype: 'email',
						fieldLabel : '이메일'
					},{
						xtype : 'textfield',
						name : 'k_phone',
						//emptyText: 'xxx-xxxx-xxxx',
                       // maskRe: /[\d\-]/,
                        //regex: /^\d{3}-\d{4}-\d{4}$/,
                       // regexText: 'xxx-xxx-xxxx 형식으로 입력해주세요',
						fieldLabel : '전화번호'
					}]
				}]
			},{
				region: 'east',
				xtype : 'panel',
				width: '55%',
				layout: 'form',
				defaults : {
					anchor : '95%',
					labelSeparator: ''
				}
			}],
			buttons: [{
				text: '등록',
				//scale: 'medium',
				handler: function(e){

					var params = this.ownerCt.ownerCt.getForm().getValues();

					var worker_grid = Ext.getCmp('worker').get(0).get(0).getStore();
					var contents = Ext.getCmp('request_contents').get(0).get(0).getStore();

					if(worker_grid.getCount() == 0)
					{
						Ext.Msg.alert(_text('MN00024'), '작업자를 할당 해주세요');
						return;
					}
					else if(contents.getCount() == 0)
					{
						Ext.Msg.alert(_text('MN00024'), '의뢰 목록을 넣어주세요');
						return;
					}

					if( !this.ownerCt.ownerCt.getForm().isValid() ){
						Ext.Msg.alert(_text('MN00024'), '필수값을 넣어주세요');
						return;
					}

					var content_list_array = new Array();
					var content_list_store = Ext.getCmp('request_contents').get(0).get(0).getStore();

					content_list_store.each(function(r){

						content_list_array.push( r.json );
					});

					params.k_content_list = Ext.encode(content_list_array);

					params.k_work_user_id = Ext.getCmp('worker').get(0).get(0).getStore().getAt(0).get('user_id');


					Ext.Msg.show({
						title: _text('MN00024'),
						msg: '제목을 제외한 메타데이터를 일괄 저장 후 작업의뢰가 요청됩니다.<br />진행하시겠습니까? ',
						buttons: Ext.Msg.OKCANCEL,
						fn: function(btnID){
							if (btnID == 'ok') {
								that.submit(params);
							}
						}
					});

					//that.submit();

				}
			},{
				text: '취소',
				//scale: 'medium',
				handler: function(e){
					this.ownerCt.ownerCt.ownerCt.hide();
					that.reset();
				}
			}]
		}];

		Ariel.Nps.WorkRequest.superclass.initComponent.call(this);
	},
	getFormItems: function( id ,type, panel ){
		Ext.Ajax.request({
			url: '/modules/work/form.php',
			params: {
				content_id: id,
				type: type
			},
			callback: function(opts, success, response){
				if ( success )
				{
					try
					{
						var r = Ext.decode(response.responseText);

						panel.removeAll();
						panel.add(r);
						panel.doLayout();
					}
					catch(e)
					{
						Ext.Msg.alert(e['name'], e['message']);
					}
				}
				else
				{
					Ext.Msg.alert(_text('MN00022'), response.statusText);
				}
			}
		});
	},
	submit: function (p){
		Ext.Ajax.request({
			url: '/store/nps_work/request.php',
			params: p,
			callback: function(opts, success, response){
				if ( success )
				{
					try
					{
						var r = Ext.decode(response.responseText);

						if(r.success)
						{

							Ext.getCmp('tab_warp').getActiveTab().get(0).getStore().reload();

							that.hide();
						}

						Ext.Msg.alert( _text('MN00023'), r.msg);

					}
					catch(e)
					{
						Ext.Msg.alert(e['name'], e['message']);
					}
				}
				else
				{
					Ext.Msg.alert(_text('MN00022'), response.statusText);
				}
			}
		});
	},
	getMemberValue: function(self){

		Ext.Ajax.request({
			url: '/store/nps_work/get_member_value.php',
			callback: function(opts, success, response){
				if ( success )
				{
					try
					{
						var r = Ext.decode(response.responseText);
						if(r.success)
						{
							var email= r.email;
							var phone= r.phone;

							var values = {
								k_email: email,
								k_phone: phone
							};

							var form = self.get(0).getForm();
							form.findField('k_email').setValue(email);
							form.findField('k_phone').setValue(phone);
						}
						else
						{
							Ext.Msg.alert(e['name'], r.msg);
						}

					}
					catch(e)
					{
						Ext.Msg.alert(e['name'], e['message']);
					}
				}
				else
				{
					Ext.Msg.alert(_text('MN00022'), response.statusText);
				}
			}
		});
	}

});