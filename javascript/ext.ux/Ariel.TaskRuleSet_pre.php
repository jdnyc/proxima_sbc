<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
session_start();
fn_checkAuthPermission($_SESSION);
?>
(function(){
	Ext.ns('Ariel.config.custom');

	Ariel.config.custom.MetadataPanel = Ext.extend(Ext.Panel, {
		layout: 'border',
		border: false,
		defaults: {
			split: true,
			border:false
		},

		initComponent: function(config){
			Ext.apply(this, config || {});

			this.tableGrid = this.buildTable();
			this.fieldGrid = this.buildModule();

			this.items = [
				this.tableGrid,
				this.fieldGrid
			];

			Ariel.config.custom.MetadataPanel.superclass.initComponent.call(this);
		},

		//작업 설정
		buildTable : function(){
			return new Ext.grid.GridPanel({
				xtype:	'grid',
				id: 'task_rule_list',
				//cls: 'proxima_customize',
				//>>title: '작업 설정',
				//title: _text('MN00380'),
				title: '<span class="user_span"><span class="icon_title"><i class=""></i></span><span class="main_title_header">'+_text('MN00380')+'</span></span>',
				cls: 'grid_title_customize proxima_customize',
				stripeRows: true,
				region: 'center',
				loadMask: true,
				split: true,
				border : false,
				selModel: new Ext.grid.RowSelectionModel({
					singleSelect: true
				}),
				store: new Ext.data.JsonStore({
					url: '/pages/menu/config/workflow/workflow_list.php',
					root: 'data',
					idPropery: 'task_workflow_id',
					fields: [
						{name: 'task_rule_id', type: 'int'},
						{name: 'type', type: 'int'},
						'job_name',
						'type_name',
						'parameter',
						'src_path',
						'tar_path',
						's_name',
						't_name',
						'source_path',
						'target_path',
						'task_type_id',
						'source_opt', //김형기 추가
						'target_opt', //김형기 추가
						'type_and_name' //김형기 추가
					],

					sortInfo: {
						field: 'type',
						direction: 'asc'
					},
					listeners: {
						exception: function(self, type, action, options, response, arg){
							if(type == 'response') {
								if(response.status == '200') {
									//>>Ext.Msg.alert(lang.errorLabel, response.responseText);
									Ext.Msg.alert(_text('MN00022'), response.responseText);
								}else{
									//>>Ext.Msg.alert(lang.errorLabel, response.status);
									Ext.Msg.alert(_text('MN00022'), response.status);
								}
							}else{
								//>>Ext.Msg.alert(lang.errorLabel, type);
								Ext.Msg.alert(_text('MN00022'), type);
							}
						}
					}
				}),

				cm: new Ext.grid.ColumnModel({
					defaults: {
						sortable: true
					},
					columns: [
						//>>{header: '작업ID', dataIndex: 'task_rule_id', width: 40, align: 'center'},
						//>>{header: '작업 명', dataIndex: 'job_name', width: 85},
						//>>{header: '파라미터', dataIndex: 'parameter', width: 220},
						//>>{header: '소스 경로', dataIndex: 'src_path', width: 200},
						//>>{header: '타겟 경로', dataIndex: 'tar_path', width: 200}
						//>>{header: '소스 옵션', dataIndex: 'source_opt', width: 300},
						//>>{header: '타겟 옵션', dataIndex: 'target_opt', width: 300},
						//>>{header: '작업 유형' , dataIndex: 'type_name' , width:160},
						//>>{header: '작업 코드' , dataIndex: 'type' , width:70 ,align: 'center'}

						{header: _text('MN00367'), dataIndex: 'job_name', width: 220},
						{header: _text('MN00235'), dataIndex: 'task_rule_id', width: 50, align: 'center', hidden: true},
						{header: _text('MN00299'), dataIndex: 'parameter', width: 300},
						{header: _text('MN00343'), dataIndex: 'src_path', width: 130},
						{header: _text('MN00344'), dataIndex: 'tar_path', width: 130},
						{header: _text('MN02054'), dataIndex: 'source_opt', width: 300},
						{header: _text('MN02055'), dataIndex: 'target_opt', width: 300},
						{header: _text('MN02056'), dataIndex: 'type_name' , width:160},
						{header: _text('MN02057'), dataIndex: 'type' , width:70 ,align: 'center'}
					]
				}),

				listeners: {
					viewready: function(self){
						self.store.load({
							params: {
								action: 'task_rule_list'
							}
						});
					},
					rowdblclick: {
						fn: function(self, rowIndex, e){
							var sm = Ext.getCmp('task_rule_list').getSelectionModel();
							var sel = sm.getSelected();
							var hasSelection = sm.hasSelection();
							this.buildEditTableWin(e,sm,sel);
						},
						scope: this
					}
				},
				viewConfig: {
					//forceFit: true,
					//>>emptyText: '작업을 추가해 주세요.'
                    scrollToTop: Ext.emptyFn,
					emptyText: _text('MSG00196')
				},

				buttonAlign: 'center',
				fbar: [{
					text : '<span style="position:relative;top:1px;"><i class="fa fa-plus" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00033'),
					scale: 'medium',
					handler: function(btn, e) {
						this.buildAddTable(e);
					},
					scope: this
				},{
					text : '<span style="position:relative;top:1px;"><i class="fa fa-edit" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00043'),
					scale: 'medium',
					handler: function(btn, e) {
						var sm = Ext.getCmp('task_rule_list').getSelectionModel();
						var sel = sm.getSelected();
						var hasSelection = sm.hasSelection();
						if(hasSelection) {

							this.buildEditTableWin(e,sm,sel);

						}else{
							//>>Ext.Msg.alert(lang.errorLabel, lang.editTableAlertEmptyText);
							Ext.Msg.alert(_text('MN00022'), _text('MSG00169'));
						}
					},
					scope: this
				},{
					text : '<span style="position:relative;top:1px;"><i class="fa fa-trash" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00034'),
					scale: 'medium',
					handler: function(btn, e) {
						var hasSelection = Ext.getCmp('task_rule_list').getSelectionModel().hasSelection();
						if(hasSelection) {
							this.buildDeleteTable(e);
						}else{
							//>>Ext.Msg.alert(lang.errorLabel, '삭제하실 작업을 선택해 주세요.');
							Ext.Msg.alert(_text('MN00022'), _text('MSG00022'));
						}
					},
					scope: this
				}]
			});
		},

		buildAddTable: function(e){  //추가 버튼 기능실행

			var win = new Ext.Window({
				id: 'add_taskRule_win',
				cls: 'change_background_panel',
				layout: 'fit',
				//>>title: '작업 추가',
				title: _text('MN00070'),
				width: 450,
				height: 300,
				padding: 10,
				modal: true,
				buttonAlign: 'center',
				items: {
					id: 'add_table_form',
					xtype: 'form',
					baseCls: 'x-plain',
					defaultType: 'textfield',
					defaults: {
						anchor: '100%'
					},
					items: [{
						name: 'user_task_name',
						//>>fieldLabel: '작업 명칭',
						fieldLabel: _text('MN00367'),
						msgTarget: 'under',
						allowBlank: false
					},
					/* //2011.12.14 김형기 제거
					{
						id: 'm_info_idx',
						xtype: 'combo',

						//>>fieldLabel: '작업 유형',
						 fieldLabel:  _text('MN00402'),
						store: 	new Ext.data.JsonStore({
							url: '/pages/menu/config/workflow/workflow_list.php',
							baseParams: {
								action: 'module_list'
							},
							root: 'data',
							idProperty: 'module_info_id',
							fields: [
								{name: 'name', type: 'string'},
								{name: 'module_info_id', type: 'int'},
								{name: 'module_id', type: 'int'}]
						}),
						hiddenName: 'module_info_id',
						name: 'm_info_id',
						valueField: 'module_info_id',
						displayField: 'name',
						typeAhead: true,
						triggerAction: 'all',
						forceSelection: true,
						editable: false,
						//>>emptyText: '작업 타입을 선택하세요.'
						emptyText: _text('MSG00197'),
						listeners: {
						    select: function (cmb, record, index) {
						      Ext.getCmp('m_task_type_list').show();
						      Ext.getCmp('m_task_type_list').reset();
						      Ext.getCmp('m_task_type_list').getStore().setBaseParam('module_info_id',Ext.getCmp('m_info_idx').getValue());
						    }
						}
					},
					*/
					{
						id: 'm_task_type_list',
						xtype: 'combo',
						//fieldLabel: '작업 유형',
						fieldLabel: _text('MN02056'),
						store: 	new Ext.data.JsonStore({
							url: '/pages/menu/config/workflow/workflow_list.php',
							autoDestroy: true,
							baseParams: {
								action: 'get_available_task_list'
							},
							root: 'data',
							idProperty: 'type',
							fields: [
								{name: 'name'},
								{name: 'type'},
								{name: 'task_type_id'},
								{name: 'type_and_name'}
							]
						}),
						hiddenName: 'type_and_name',
						valueField: 'task_type_id',
						tpl: '<tpl for="."><div class="x-combo-list-item" ><font color=red><b>[{type}]</b></font> {name}</div></tpl>',
						displayField: 'type_and_name',
						typeAhead: true,
						triggerAction: 'all',
						forceSelection: true,
						editable: false,
						//>>emptyText: '작업 타입을 선택하세요.'
						emptyText: _text('MSG00197'),
						listeners: {
						        beforequery: function(qe){
						            delete qe.combo.lastQuery;
						        }
						    }
					},{
						id: 'm_task_type_parameter',
						xtype: 'textfield',
						name: 'parameter',
						//>>fieldLabel: '파라미터',
						fieldLabel: _text('MN00299'),
						msgTarget: 'under',
						allowBlank: false
					},{
						xtype: 'combo',
						//>>fieldLabel: '소스 경로',
						fieldLabel: _text('MN00343'),
						store: new Ext.data.JsonStore({
							url: '/pages/menu/config/workflow/workflow_list.php',
							baseParams: {
								action: 'storage_list'
							},
							root: 'data',
							idProperty: 'storage_id',
							fields: [
								{name: 'name', type: 'string'},
								{name: 'storage_id', type: 'int'},
								{name: 'path', type: 'string'}
							]
						}),
						hiddenName: 'src_storage_id',
						valueField: 'storage_id',
						displayField: 'name',
						//수정일 : 2011.12.09
						//작성자 : 김형기
						//내용 : tpl구문 추가 하여 스토리지 이름과 경로가 동시에 나오도록 변경
						tpl: '<tpl for="."><div class="x-combo-list-item" >{name} [{path}]</div></tpl>',
						typeAhead: true,
						triggerAction: 'all',
						forceSelection: true,
						editable: false,
						//>>emptyText: '소스 스토리지를 선택하세요'
						emptyText: _text('MSG00198')
					},{
						xtype: 'combo',
						//>>fieldLabel: '타겟 경로',
						fieldLabel: _text('MN00344'),
						store: new Ext.data.JsonStore({
							url: '/pages/menu/config/workflow/workflow_list.php',
							baseParams: {
								action: 'storage_list'
							},
							root: 'data',
							idProperty: 'storage_id',
							fields: [
								{name: 'name', type: 'string'},
								{name: 'storage_id', type: 'int'},
								{name: 'path', type: 'string'}
							]
						}),
						hiddenName: 'trg_storage_id',
						valueField: 'storage_id',
						displayField: 'name',
						//수정일 : 2011.12.09
						//작성자 : 김형기
						//내용 : tpl구문 추가 하여 스토리지 이름과 경로가 동시에 나오도록 변경
						tpl: '<tpl for="."><div class="x-combo-list-item" >{name} [{path}]</div></tpl>',
						typeAhead: true,
						triggerAction: 'all',
						forceSelection: true,
						editable: false,
						//>>emptyText: '타겟 스토리지를 선택하세요'
						emptyText: _text('MSG00199')
					},{	//2011.12.15 김형기 추가
						xtype: 'textfield',
						name: 'source_opt',
						//fieldLabel: '소스 옵션',
						fieldLabel: _text('MN02054'),
						msgTarget: 'under',
						allowBlank: false
					},{
						xtype: 'textfield',
						name: 'target_opt',
						//fieldLabel: '타겟 옵션',
						fieldLabel: _text('MN02055'),
						msgTarget: 'under',
						allowBlank: false
					}]
				},
				buttons: [{
					text : '<span style="position:relative;top:1px;"><i class="fa fa-plus" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00033'),
					scale: 'medium',
					handler: function(btn, e) {

						Ext.getCmp('add_table_form').getForm().submit({
							url: '/pages/menu/config/workflow/edit_workflow.php',
							params: {
								action: 'add_rule'
							},
							success: function(form, action) {
								try {
									var result = Ext.decode(action.response.responseText, true);
									if(result.success) {
										Ext.getCmp('add_taskRule_win').close();
										Ext.getCmp('task_rule_list').store.reload();
									}else{
										Ext.Msg.show({
											//>>title: lang.errorLabel,
											title: _text('MN00022'),
											icon: Ext.Msg.ERROR,
											msg: result.msg,
											buttons: Ext.Msg.OK
										})
									}
								}catch(e){
									Ext.Msg.show({
										//>>title: lang.errorLabel,
										title: _text('MN00022'),
										icon: Ext.Msg.ERROR,
										msg: e.message,
										buttons: Ext.Msg.OK
									})
								}
							},
							failure: function(form, action) {
								Ext.Msg.show({
									icon: Ext.Msg.ERROR,
									title: lang.errorLabel,
									msg: action.result.msg,
									buttons: Ext.Msg.OK
								});
							}
						});
					}
				},{
					text : '<span style="position:relative;top:1px;"><i class="fa fa-close" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00004'),
					scale: 'medium',
					handler: function() {
						this.ownerCt.ownerCt.close();
					}
				}]
			}).show(e.getTarget());
		},

		buildEditTableWin: function(e,sm,sel) { //수정버튼 기능 실행

			var _submit = function(){
				Ext.getCmp('edit_table_form').getForm().submit({
					url: '/pages/menu/config/workflow/edit_workflow.php',
					params: {
						action: 'edit_rule'
					},
					success: function(form, action){
						try {
							var result = Ext.decode(action.response.responseText, true);
							if (result.success) {
								// 정상
								Ext.getCmp('edit_rule_win').close();
								Ext.getCmp('task_rule_list').store.reload();
							}
							else {
								// 쿼리 에러
								Ext.Msg.show({
									//>>title: lang.errorLabel,
									title: _text('MN00022'),
									icon: Ext.Msg.ERROR,
									msg: result.msg,
									buttons: Ext.Msg.OK
								})
							}
						}
						catch (e) {
							// 문법 에러
							Ext.Msg.show({
								//>>title: lang.errorLabel,
								title: _text('MN00022'),
								icon: Ext.Msg.ERROR,
								msg: e.message,
								buttons: Ext.Msg.OK
							})
						}
					},
					failure: function(form, action){
						// 파일이 없을 경우 가 다분함
						Ext.Msg.show({
							icon: Ext.Msg.ERROR,
							//>>title: lang.errorLabel,
							title: _text('MN00022'),
							msg: action.result.msg,
							buttons: Ext.Msg.OK
						});
					}
				});
			}

			var win = new Ext.Window({
				id: 'edit_rule_win',
				cls: 'change_background_panel',
				layout: 'fit',
				//>>title: '작업 수정',
				title: _text('MN00383'),
				width: 450,
				height: 300,
				padding: 10,
				modal: true,
				buttonAlign: 'center',
				items: {
					id: 'edit_table_form',
					xtype: 'form',
					baseCls: 'x-plain',
					defaultType: 'textfield',
					defaults: {
						anchor: '100%'
					},
					items: [{
						xtype: 'hidden',
						name: 'task_rule_id'
					},{
						name: 'job_name',
						//>>fieldLabel: '작업 명칭',
						fieldLabel: _text('MN00367'),
						msgTarget: 'under',
						allowBlank: false
					},
					/* //2011.12.14 김형기 제거
					{
						id:'m_info_idx',
						xtype: 'combo',
						//>>fieldLabel: '작업 유형',
						fieldLabel: _text('MN00382'),
						store: new Ext.data.JsonStore({
								autoLoad: true,
								url: '/pages/menu/config/workflow/workflow_list.php',
								baseParams: {
									action: 'module_list'
								},
								root: 'data',
								idProperty: 'module_info_id',
								fields: [
									{name: 'name', type: 'string'},
									{name: 'module_info_id', type: 'int'},
									{name: 'module_id', type: 'int'}
									]
							}),
						mode: 'remote',
						hiddenName: 'm_name',
						hiddenValue: sel.get('module_info_id'),
						valueField: 'module_info_id',
						displayField: 'name',
						typeAhead: true,
						triggerAction: 'all',
						forceSelection: true,
						editable: false,
   					    fieldLabel:  _text('MN00402'),

						//>>emptyText: '작업 타입을 선택하세요.'
						emptyText: _text('MSG00197'),
						listeners: {
						    select: function (cmb, record, index) {
						      Ext.getCmp('m_task_type_list').show();
						      Ext.getCmp('m_task_type_list').reset();
						      Ext.getCmp('m_task_type_list').getStore().setBaseParam('module_info_id',Ext.getCmp('m_info_idx').getValue());
						    }
						}
					},
					*/
					{
						id: 'm_task_type_list',
						xtype: 'combo',
						//fieldLabel: '작업 유형',
						fieldLabel: _text('MN02056'),
						store: 	new Ext.data.JsonStore({
							url: '/pages/menu/config/workflow/workflow_list.php',
							autoDestroy: true,
							baseParams: {
								action: 'get_available_task_list'
							},
							root: 'data',
							idProperty: 'type',
							fields: [
								{name: 'name'},
								{name: 'type'},
								{name: 'task_type_id'},
								{name: 'type_and_name'}//타입과 모듈 동시에 나오는 필드 추가
							]
						}),
						hiddenName: 'type_and_name',
						hiddenValue: sel.get('task_type_id'),
						valueField: 'task_type_id',
						tpl: '<tpl for="."><div class="x-combo-list-item" ><font color=red><b>[{type}]</b></font> {name}</div></tpl>',
						displayField: 'type_and_name',
						typeAhead: true,
						triggerAction: 'all',
						forceSelection: true,
						editable: false,
						//>>emptyText: '작업 타입을 선택하세요.'
						emptyText: _text('MSG00197'),
						listeners: {
						        beforequery: function(qe){
						            delete qe.combo.lastQuery;
						        }
						    }
					},{
						name: 'parameter',
						//>>fieldLabel: '파라미터',
						fieldLabel: _text('MN00299'),
						msgTarget: 'under',
						allowBlank: false
					},{
						xtype: 'combo',
						//>>fieldLabel: '소스 경로',
						fieldLabel: _text('MN00343'),
						store: new Ext.data.JsonStore({
							url: '/pages/menu/config/workflow/workflow_list.php',
							baseParams: {
								action: 'storage_list'
							},
							root: 'data',
							idProperty: 'storage_id',
							fields: [
								{name: 'name', type: 'string'},
								{name: 'storage_id', type: 'int'},
								{name: 'path', type: 'string'}
							]
						}),
						hiddenName: 's_name',
						hiddenValue: sel.get('source_path'),
						valueField: 'storage_id',
						displayField: 'name',
						//수정일 : 2011.12.09
						//작성자 : 김형기
						//내용 : tpl구문 추가 하여 스토리지 이름과 경로가 동시에 나오도록 변경
						tpl: '<tpl for="."><div class="x-combo-list-item" >{name} [{path}]</div></tpl>',
						typeAhead: true,
						triggerAction: 'all',
						forceSelection: true,
						editable: false,
						//>>emptyText: '소스 스토리지를 선택하세요'
						emptyText: _text('MSG00198')
					},{
						xtype: 'combo',
						//>>fieldLabel: '타겟 경로',
						fieldLabel: _text('MN00344'),
						store: new Ext.data.JsonStore({
							url: '/pages/menu/config/workflow/workflow_list.php',
							baseParams: {
								action: 'storage_list'
							},
							root: 'data',
							idProperty: 'storage_id',
							fields: [
								{name: 'name', type: 'string'},
								{name: 'storage_id', type: 'int'},
								{name: 'path', type: 'string'}
							]
						}),
						hiddenName: 't_name',
						hiddenValue: sel.get('target_path'),
						valueField: 'storage_id',
						displayField: 'name',
						//수정일 : 2011.12.09
						//작성자 : 김형기
						//내용 : tpl구문 추가 하여 스토리지 이름과 경로가 동시에 나오도록 변경
						tpl: '<tpl for="."><div class="x-combo-list-item" >{name} [{path}]</div></tpl>',
						typeAhead: true,
						triggerAction: 'all',
						forceSelection: true,
						editable: false,
						//>>emptyText: '타겟 스토리지를 선택하세요'
						emptyText: _text('MSG00199')
					},{
						xtype: 'textfield',
						name: 'source_opt',
						//fieldLabel: '소스 옵션',
						fieldLabel: _text('MN02054'),
						msgTarget: 'under'
						//,allowBlank: false
					},{
						xtype: 'textfield',
						name: 'target_opt',
						//fieldLabel: '타겟 옵션',
						fieldLabel: _text('MN02055'),
						msgTarget: 'under'
						//,allowBlank: false
					}],
					keys: [{
						key: 13,
						handler: function(){
							_submit();
						}
					}],
					listeners: {
						afterrender: function(self) {
							var sm = Ext.getCmp('task_rule_list').getSelectionModel();
							var rec = sm.getSelected();
							self.getForm().loadRecord(rec);
						}
					}
				},
				buttons: [{
					text : '<span style="position:relative;top:1px;"><i class="fa fa-edit" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00043'),
					scale: 'medium',
					handler: function(btn, e) {
						_submit();
					}
				},{
					text : '<span style="position:relative;top:1px;"><i class="fa fa-close" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00004'),
					scale: 'medium',
					handler: function(btn, e) {
						this.ownerCt.ownerCt.close();
					}
				}]
			}).show(e.getTarget());
		},

		buildDeleteTable: function(e) {
			var rec = Ext.getCmp('task_rule_list').getSelectionModel().getSelected();

			// 삭제 확인 창
			Ext.Msg.show({
				animEl: e.getTarget(),
				//title: '확인',
				title: _text('MN00024'),
				icon: Ext.Msg.INFO,
				//msg: '선택하신 "' + rec.get('job_name') + '" 작업 을 삭제 하시겠습니까?',
				msg: '"' + rec.get('job_name') + '"' + _text('MSG00172'),
				buttons: Ext.Msg.OKCANCEL,
				fn: function(btnID, text, opt) {
					if(btnID == 'ok') {
						Ext.Ajax.request({
							url: '/pages/menu/config/workflow/edit_workflow.php',
							params: {
								action: 'delete_rule',
								task_rule_id: rec.get('task_rule_id')
							},
							callback: function(opts, success, response) {
								try {
									var r = Ext.decode(response.responseText, true);
									if(r.success) {
										Ext.Msg.alert(_text('MN00023'), r.msg);
										Ext.getCmp('task_rule_list').store.reload();
									}else{
										Ext.Msg.alert(_text('MN00022'), r.msg);
									}
								}catch(e) {
									alert(e.message + '(responseText: ' + response.responseText + ')');
								}
							}
						})
					}
				}
			})
		},

		//모듈 목록 + 모듈 별 사용 작업 목록
		buildModule: function(){

			//모듈 별 사용 작업 목록
			var center =  new Ext.grid.GridPanel({
				//title : '모듈 별 사용 작업 목록',
				//title : _text('MN02059'),
				id: 'task_type_list',
				title: '<span class="user_span"><span class="icon_title"><i class=""></i></span><span class="main_title_header">'+_text('MN02059')+'</span></span>',
				cls: 'grid_title_customize proxima_customize',
				stripeRows: true,
				enableHdMenu: true,
				xtype:	'grid',
				split: true,
				height: 350,
				loadMask: true,
				border : false,
				store:	new	Ext.data.JsonStore({
					id:	'task_type_store',
					url: '/pages/menu/config/workflow/workflow_list.php',
					totalProperty: 'total',
					idProperty:	'id',
					root: 'data',
					selModel: new Ext.grid.RowSelectionModel({
						singleSelect: true
					}),
					fields:	[
						{name: 'module_info_id'},
						{name: 'job_name'},
						{name: 'name'},
						{name: 'type'}
					],
					listeners: {
						beforeload:	function(self, opts){
							var sel = Ext.getCmp('modueInfo').getSelectionModel().getSelected();
							if(sel){
									self.baseParams.module_info_id	= sel.get('module_info_id');
									self.baseParams.action = 'get_available_task_list_by_module';
									}

						},
						exception: function(self, type, action, options, response, arg){
							if(type == 'response') {
								if(response.status == '200') {
									//>>Ext.Msg.alert(lang.errorLabel, response.responseText);
									Ext.Msg.alert(_text('MN00022'), response.responseText);
								}else{
									//>>Ext.Msg.alert(lang.errorLabel, response.status);
									Ext.Msg.alert(_text('MN00022'), response.status);
								}
							}else{
								//>>Ext.Msg.alert(lang.errorLabel, type);
								Ext.Msg.alert(_text('MN00022'), type);
							}
						}
					}
				}),
				columns: [
					//>>{header: '작업 명칭', dataIndex: 'job_name', width: 250, align:'left'},
					//>>{header: '작업 유형', dataIndex: 'name', width: 180,align:'left'},
					//>>{header: '작업 코드', dataIndex: 'type', width: 70, align:'center'}
					{header: _text('MN00367'), dataIndex: 'job_name', width: 250, align:'left'},
					{header: _text('MN02056'), dataIndex: 'name', width: 180,align:'left'},
					{header: _text('MN02056'), dataIndex: 'type', width: 70, align:'center'}
				],

				listeners: {

					rowdblclick: {
						fn: function(self, rowIndex, e){
						var sm = Ext.getCmp('modueInfo').getSelectionModel();
							this.request({
								module_info_id: sm.getSelected().get('module_info_id'),
								module_id: sm.getSelected().get('module_id')
							});

						},
						scope: this
					}
				},

				viewConfig: {
					//>>emptyText: '모듈을 추가해 주세요.'
					emptyText: _text('MSG00200')
				}
			});

			//모듈 목록
			var left =  new Ext.grid.GridPanel({
				//title : '모듈 목록',
				//title : _text('MN02060'),
				id: 'modueInfo',
				title: '<span class="user_span"><span class="icon_title"><i class=""></i></span><span class="main_title_header">'+_text('MN02060')+'</span></span>',
				cls: 'grid_title_customize proxima_customize',
				stripeRows: true,
				enableHdMenu: true,
				xtype:	'grid',
				height: 350,
				border : false,
				loadMask: true,
				//autoExpandColumn: 'description',
				store:	new	Ext.data.JsonStore({
					id:	'rull_store',
					url: '/pages/menu/config/workflow/workflow_list.php',
					totalProperty: 'total',
					idProperty:	'id',
					root: 'data',
					selModel: new Ext.grid.RowSelectionModel({
						singleSelect: true
					}),
					fields:	[
						{name: 'module_info_id'},
						{name: 'name'},
						{name: 'active'},
						{name: 'module_name'},
						{name: 'main_ip'},
						{name: 'sub_ip'},
						{name: 'description'},
						{name: 'allow_storage'},
						{name: 'module_code'},
						{name: 'module_id'}
					],
					listeners: {
						exception: function(self, type, action, options, response, arg){
							if(type == 'response') {
								if(response.status == '200') {
									//>>Ext.Msg.alert(lang.errorLabel, response.responseText);
									Ext.Msg.alert(_text('MN00022'), response.responseText);
								}else{
									//>>Ext.Msg.alert(lang.errorLabel, response.status);
									Ext.Msg.alert(_text('MN00022'), response.status);
								}
							}else{
								//>>Ext.Msg.alert(lang.errorLabel, type);
								Ext.Msg.alert(_text('MN00022'), type);
							}
						}
					}
				}),
				columns: [
					//>>{header: '모듈ID', dataIndex: 'module_id', width: 45, align:'center'},
					//>>{header: '모듈 명', dataIndex: 'name', width: 110},
					//>>{header: '활성화', dataIndex:	'active', width: 45, align:'center'},
					//>>{header: '메인 IP', dataIndex:	'main_ip', width: 120, align:'center'},
					//>>{header: '보조 IP', dataIndex:	'sub_ip', width: 120, align:'center'},
					//>>{header: '설 명', dataIndex:	'description', width: 180}
					//>>{header: '스토리지', dataIndex: 'allow_storage', width: 180}

					{header: _text('MN00385'), dataIndex: 'module_info_id', width: 45, align:'center'},
					{header: _text('MN00346'), dataIndex: 'name', width: 110},
					{header: _text('MN00347'), dataIndex:	'active', width: 45, align:'center'},
					{header: _text('MN00348'), dataIndex:	'main_ip', width: 120, align:'center'},
					{header: _text('MN00349'), dataIndex:	'sub_ip', width: 120, align:'center'},
					{header: _text('MN00049'), dataIndex:	'description', width: 170},
					{header: _text('MN00381'), dataIndex: 'allow_storage', width: 180}
				],
				selModel: new Ext.grid.RowSelectionModel({
						singleSelect: true,
						listeners: {
							rowselect: function(self){
								Ext.getCmp('task_type_list').getStore().load();
							},
							rowdeselect: function(self){
							Ext.getCmp('task_type_list').getStore().removeAll();
							}
						}
					}),

				listeners: {
					viewready: function(self){
						self.store.load({
							params: {
								action: 'get_bc_module_info'
							}
						});
					},
					rowdblclick: {
						fn: function(self, rowIndex, e){
						var sm = Ext.getCmp('modueInfo').getSelectionModel();
						this.request({
						module_info_id: sm.getSelected().get('module_info_id'),
						module_id: sm.getSelected().get('module_id')
						});


						},
						scope: this
					}
				},

				viewConfig: {
					//>>emptyText: '모듈을 추가해 주세요.'
					emptyText: _text('MSG00200')
				},

				buttonAlign: 'center',

				fbar: [{
					text : '<span style="position:relative;top:1px;"><i class="fa fa-plus" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00033'),
					scale: 'medium',
					handler: function(btn, e) {
						this.buildAddModuleWin(e);
					},
					scope: this
				},{
					text : '<span style="position:relative;top:1px;"><i class="fa fa-edit" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00043'),
					scale: 'medium',
					handler: function(btn, e) {
						var sm = Ext.getCmp('modueInfo').getSelectionModel();
						//var hasSelection = Ext.getCmp('modueInfo').getSelectionModel().hasSelection();

						if(sm.hasSelection()) {
							//this.buildEditWorkflowRuleWin(e);
							this.request({
								module_info_id: sm.getSelected().get('module_info_id'),
								module_id: sm.getSelected().get('module_id')
								});
						}else{
							//>>Ext.Msg.alert(lang.errorLabel, '수정하실 모듈을 선택해 주세요.');
							Ext.Msg.alert(_text('MN00022'), _text('MSG00084'));
						}
					},
					scope: this
				},{
					text : '<span style="position:relative;top:1px;"><i class="fa fa-trash" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00034'),
					scale: 'medium',
					handler: function(btn, e) {
						var hasSelection = Ext.getCmp('modueInfo').getSelectionModel().hasSelection();
						if(hasSelection) {
							this.buildDeleteRuleWin(e);
						}else{
							//>>Ext.Msg.alert(lang.errorLabel, lang.deleteTableAlertEmptyText);
							Ext.Msg.alert(_text('MN00022'), _text('MSG00170'));
						}
					},
					scope: this
				}]

			});

			var main_panel = new Ext.Panel({
				region: 'south',
				layout: "fit",
				height:350,
				border : false,
				defaults: {
					 bodyStyle:'padding:0px'
					,border: false
					//,padding : 10
					,cellCls : 'verticalAlignTop'
				},
				items: [
					new Ext.Panel({
						layout: "border",
						margins: '0 0 0 0',
						border : false,
						items: [{
							region: 'west',
							//minWidth: 300,
							width: 820,
							autoScroll: false,
							split: true,
							layout: 'fit',
							border : false,
							margins: '0 0 0 0',
							items: [
								left
							]

						}, {
							region: 'center',
							flex: 4,
							split: true,
							autoScroll: true,
							width: '300',
							minWidth: 300,
							layout: 'fit',
							border : false,
							margins: '0 0 0 0',
							items:[
								center
							]

						}]
					})
				]
			});
			return main_panel;
		},

		buildAddModuleWin: function(e){  //모듈 추가 버튼 기능실행

			var south_form = new Ext.form.FormPanel({
					//title: '접근 가능한 스토리지',
					title: _text('MN02061'),
					cls: 'change_background_panel',
					id: 'add_storage_form',
					autoScroll: true,
					border: false,
					frame: false,
					padding : 5,
					items: {
							xtype: 'checkboxgroup',
							id: 'allow_storage',
							hideLabel: true,
							fieldLabel: ' ',
							name: 'group',
							columns: 2,

							items: [
							<?php
							$checkboxs = array();
							$groups = $db->queryAll("select storage_id, name from bc_storage");
							foreach($groups as $group){

								array_push($checkboxs, "{boxLabel: '{$group['name']}', name: 's_".$group['storage_id']."', inputValue: '{$group['storage_id']}'}");
								$checked = '';
							}
							echo implode(", \n", $checkboxs);
							?>
							]
						}
			});


			var center_form = new Ext.form.FormPanel({
					id: 'add_available_task_form',
					//title: '사용할 작업',
					title: _text('MN02062'),
					cls: 'change_background_panel',
					width: 450,
					autoScroll: true,
					border: false,
					frame: false,
					padding: 5,
					items: [{
							xtype: 'checkboxgroup',
							hideLabel: true,
							id: 'available_task',
							name: 'available_task_group',
							columns: 1,
							items: [
							<?php
							$checkboxs = array();
							//$groups = $db->queryAll("select task_type_id, type, name from bc_task_type"); //2011.12.14 김형기 제거
							$groups = $db->queryAll("select tr.job_name,tr.task_rule_id, tt.name type_name, tt.type
													from bc_task_rule tr left outer join bc_task_type tt on tr.task_type_id = tt.task_type_id");

							foreach($groups as $group){

								//array_push($checkboxs, "{boxLabel: '[{$group['type']}] {$group['name']}', name: 's_".$group['task_type_id']."', inputValue: '{$group['task_type_id']}'}"); //2011.12.14 김형기 제거
								array_push($checkboxs, "{boxLabel: '{$group['job_name']} [{$group['type_name']}({$group['type']})]', name: 's_".$group['task_rule_id']."', inputValue: '{$group['task_rule_id']}'}");
								$checked = '';
							}
							echo implode(", \n", $checkboxs);
							?>
							]
					}]

			});

			var left_form = new Ext.form.FormPanel({
					//title: '기본 정보',
					title: _text('MN00154'),
					id: 'add_module_form',
					cls: 'change_background_panel',
					border: false,
					frame: false,
					padding : 5,
					defaultType: 'textfield',
					defaults: {
						anchor: '100%'
					},
					items: [{
						name: 'module_name',
						//>>fieldLabel: '모듈 명칭',
						fieldLabel: _text('MN00346'),
						allowBlank: false,
						//>>emptyText: '모듈 명을 입력해 주세요'
						emptyText: _text('MSG00203'),
						defaultValue :''
					},{
						xtype: 'checkbox',
						name: 'activity',
						checked: true,
						//>>fieldLabel: '활성화'
						fieldLabel: _text('MN00347')
					},{
						name: 'main_ip',
						//fieldLabel: '메인 IP',
						fieldLabel: _text('MN00348'),
						allowBlank: false,
						//>>emptyText: '메인 IP주소를 입력해 주세요.'
						emptyText: _text('MSG00205')
					},{
						name: 'sub_ip',
						//>>fieldLabel: '보조 IP',
						fieldLabel: _text('MN00349'),
						//>>emptyText: '보조 IP주소를 입력해 주세요.'
						emptyText: 	_text('MSG00206')
					},{
						xtype: 'textarea',
						name: 'description',
						//>>fieldLabel: '설 명',
						fieldLabel: _text('MN00049'),
						//>>emptyText: '모듈에 대한 설명을 입력해 주세요.'
						emptyText: _text('MSG00204')
					}]
			});

			var win = new Ext.Window({
				id: 'add_module_win',
				layout: 'fit',
				//>>title: '모듈 추가',
				title: _text('MN00386'),
				border : false,
				width: 750,
				height: 500,
				modal: true,
				buttonAlign: 'center',
				items:[
						new Ext.Panel({
					    layout: 'border',
						border: false,
					    id : 'add_module_panel',
				        items: [{
							region: 'west',
							width: 350,
							layout: 'border',
							margins: '0 5 0 0',
							items: [
								    {
										region: 'center',
										width: 350,
										height:200,
										layout: 'fit',
										border : false,
										items: [
											left_form
											]
										},{
										region: 'south',
										flex: 0,
										autoScroll: true,
										width: 350,
										height:200,
										layout: 'fit',
										border: false,
										items: [
												south_form
											]

									}
							]

						}, {
						region: 'center',
						flex: 2,
						autoScroll: true,
						width: 400,
						layout: 'fit',
						items:[
							center_form
						]

					}]
				})]
				,
				buttons: [{
					text : '<span style="position:relative;top:1px;"><i class="fa fa-plus" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00033'),
					scale: 'medium',
					handler: function(btn, e) {
					Ext.getCmp('add_module_form').items.each(function(f) {
					        if (f.el.getValue() == f.emptyText) {
					            f.el.dom.value = '';
					        }
    				});
    				var dbParams = Ext.encode(Ext.getCmp('add_module_form').getForm().getValues());
					var dbParams2 = Ext.encode(Ext.getCmp('add_available_task_form').getForm().getValues());
					var dbParams3 = Ext.encode(Ext.getCmp('add_storage_form').getForm().getValues());
					Ext.Ajax.request({
		    			    method: 'post',
							url: '/pages/menu/config/workflow/edit_workflow.php',
							params: {
								action: 'add_module',
								data : dbParams,
								task :  dbParams2,
								storage :  dbParams3
							},

							success: function ( result, request ) {
								try {
									var result = Ext.decode(result.responseText, true);
									if(result.success) {
										Ext.getCmp('add_module_win').close();
										Ext.getCmp('modueInfo').store.reload();
									}else{
										Ext.Msg.show({
											//>>title: lang.errorLabel,
											title: _text('MN00022'),
											icon: Ext.Msg.ERROR,
											msg: result.msg,
											buttons: Ext.Msg.OK
										})
									}
								}catch(e){
									Ext.Msg.show({
										//>>title: lang.errorLabel,
										title: _text('MN00022'),
										icon: Ext.Msg.ERROR,
										msg: e.message,
										buttons: Ext.Msg.OK
									})
								}
							},
							failure: function(result,request) {
								Ext.Msg.show({
									icon: Ext.Msg.ERROR,
									//>>title: lang.errorLabel,
									title: _text('MN00022'),
									msg: result.msg,
									buttons: Ext.Msg.OK
								});
							}
						});
					}
				},{
					text : '<span style="position:relative;top:1px;"><i class="fa fa-close" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00004'),
					scale: 'medium',
					handler: function() {
						this.ownerCt.ownerCt.close();
					}
				}]
			}).show(e.getTarget());
		},

		buildEditWorkflowRuleWin: function(e,sm,sel) { //모듈 수정버튼 기능 실행

			var _submit = function(){
				Ext.getCmp('edit_module_form').items.each(function(f) {
					        if (f.el.getValue() == f.emptyText) {
					            f.el.dom.value = '';
					        }
    				});
    			var dbParams = Ext.encode(Ext.getCmp('edit_module_form').getForm().getValues());
				var dbParams2 = Ext.encode(Ext.getCmp('edit_available_task_form').getForm().getValues());
				var dbParams3 = Ext.encode(Ext.getCmp('edit_storage_form').getForm().getValues());
				Ext.Ajax.request({
		    			    method: 'post',
							url: '/pages/menu/config/workflow/edit_workflow.php',
							params: {
								action: 'add_module',
								data : dbParams,
								task :  dbParams2,
								storage :  dbParams3
							},

							success: function ( result, request ) {
								try {
									var result = Ext.decode(result.responseText, true);
									if(result.success) {
										Ext.getCmp('add_module_win').close();
										Ext.getCmp('modueInfo').store.reload();
									}else{
										Ext.Msg.show({
											//>>title: lang.errorLabel,
											title: _text('MN00022'),
											icon: Ext.Msg.ERROR,
											msg: result.msg,
											buttons: Ext.Msg.OK
										})
									}
								}catch(e){
									Ext.Msg.show({
										//>>title: lang.errorLabel,
										title: _text('MN00022'),
										icon: Ext.Msg.ERROR,
										msg: e.message,
										buttons: Ext.Msg.OK
									})
								}
							},
							failure: function(result,request) {
								Ext.Msg.show({
									icon: Ext.Msg.ERROR,
									//>>title: lang.errorLabel,
									title: _text('MN00022'),
									msg: result.msg,
									buttons: Ext.Msg.OK
								});
							}
						});
			}

			var south_form = new Ext.form.FormPanel({
					//title: ' 접근 가능한 스토리지 ',
					title: _text('MN01087'),
					id: 'edit_storage_form',
					autoScroll: true,
					border: false,
					frame: true,
					items: {
							xtype: 'checkboxgroup',
							id: 'allow_storage',
							hideLabel: true,
							fieldLabel: ' ',
							name: 'group',
							columns: 2,

							items: [
							<?php
							$checkboxs = array();
							$groups = $db->queryAll("select storage_id, name from bc_storage");
							foreach($groups as $group){

								array_push($checkboxs, "{boxLabel: '{$group['name']}', name: 's_".$group['storage_id']."', inputValue: '{$group['storage_id']}'}");
								$checked = '';
							}
							echo implode(", \n", $checkboxs);
							?>
							]
						}
			});


			var center_form = new Ext.form.FormPanel({
					id: 'edit_available_task_form',
					//title: '사용할 작업',
					title: _text('MN01088'),
					width: 450,
					autoScroll: true,
					border: false,
					frame: true,
					items: [{
							xtype: 'checkboxgroup',
							hideLabel: true,
							id: 'available_task',
							name: 'available_task_group',
							columns: 1,
							items: [
							<?php
							$checkboxs = array();
							//2011.12.14 김형기 수정
							/*
							$groups = $db->queryAll("select task_type_id, type, name from bc_task_type");
							foreach($groups as $group){

								array_push($checkboxs, "{boxLabel: '[{$group['type']}] {$group['name']}', name: 's_".$group['task_type_id']."', inputValue: '{$group['task_type_id']}'}");
								$checked = '';
							}
							*/
							//$groups = $db->queryAll("select task_type_id, type, name from bc_task_type"); //2011.12.14 김형기 제거
							$groups = $db->queryAll("select tr.job_name, tt.name type_name, tt.type
													from bc_task_rule tr left outer join bc_task_type tt on tr.task_type_id = tt.task_type_id");

							foreach($groups as $group){

								//array_push($checkboxs, "{boxLabel: '[{$group['type']}] {$group['name']}', name: 's_".$group['task_type_id']."', inputValue: '{$group['task_type_id']}'}"); //2011.12.14 김형기 제거
								array_push($checkboxs, "{boxLabel: '{$group['job_name']} [{$group['type_name']}({$group['type']})]', name: 's_".$group['task_rule_id']."', inputValue: '{$group['task_rule_id']}'}");
								$checked = '';
							}
							echo implode(", \n", $checkboxs);
							?>
							]
					}]

			});

			var left_form = new Ext.form.FormPanel({
					//title: '기본 정보',
					title: _text('MN00154'),
					cls: 'change_background_panel',
					id: 'edit_module_form',
					border: false,
					frame: true,
					defaultType: 'textfield',
					defaults: {
						anchor: '100%'
					},
					items: [{
						name: 'module_name',
						//>>fieldLabel: '모듈 명칭',
						fieldLabel: _text('MN00346'),
						allowBlank: false,
						//>>emptyText: '모듈 명을 입력해 주세요'
						emptyText: _text('MSG00203'),
						defaultValue :''
					},{
						xtype: 'checkbox',
						name: 'activity',
						checked: true,
						//>>fieldLabel: '활성화'
						fieldLabel: _text('MN00347')
					},{
						name: 'main_ip',
						//fieldLabel: '메인 IP',
						fieldLabel: _text('MN00348'),
						allowBlank: false,
						//>>emptyText: '메인 IP주소를 입력해 주세요.'
						emptyText: _text('MSG00205')
					},{
						name: 'sub_ip',
						//>>fieldLabel: '보조 IP',
						fieldLabel: _text('MN00349'),
						//>>emptyText: '보조 IP주소를 입력해 주세요.'
						emptyText: 	_text('MSG00206')
					},{
						xtype: 'textarea',
						name: 'description',
						//>>fieldLabel: '설 명',
						fieldLabel: _text('MN00049'),
						//>>emptyText: '모듈에 대한 설명을 입력해 주세요.'
						emptyText: _text('MSG00204')
					}]
			});

			var win = new Ext.Window({
				id: 'edit_module_win',
				layout: 'fit',
				//>>title: '모듈 추가',
				title: _text('MN00386'),
				width: 750,
				height: 500,
				padding: 0,
				modal: true,
				items:[
						new Ext.Panel({
					    layout: 'fit',
					    border: false,
					    id : 'add_module_panel',
				        items: [{
							region: 'west',
							width: 350,
							layout: 'border',
							margins: '0 0 0 0',
							items: [
								    {
										region: 'center',
										width: 350,
										height:200,
										layout: 'fit',
										border: false,
										cls: 'change_background_panel',
										items: [
											left_form
											]
										}, {
										region: 'south',
										flex: 0,
										autoScroll: true,
										width: 350,
										height:200,
										layout: 'fit',
										items: [
												south_form
											]

									}
							]

						}, {
						region: 'center',
						flex: 2,
						border: false,
						autoScroll: true,
						width: 400,
						layout: 'fit',
						items:[
							center_form
						]

					}]
				})]
				,
				buttons: [{
					//>>text: lang.userAddTableAddButton,
					text: _text('MN00033'),
					scale: 'large',
					handler: function(btn, e) {
						_submit();

					}
				},{
					//>>text: lang.userAddTableCancelButton,
					scale: 'large',
					text: _text('MN00004'),
					handler: function() {
						this.ownerCt.ownerCt.close();
					}
				}]
			}).show(e.getTarget());
		},

		buildDeleteRuleWin: function(e) {
			var rec = Ext.getCmp('modueInfo').getSelectionModel().getSelected();

			// 삭제 확인 창
			Ext.Msg.show({
				animEl: e.getTarget(),
				//>>title: '삭제 확인',
				title: _text('MN00024'),
				icon: Ext.Msg.INFO,
				//>>msg: '"' + rec.get('name') + '"  을 삭제 하시겠습니까?',
				msg: '"' + rec.get('name') + '"' + _text('MSG00172'),
				buttons: Ext.Msg.OKCANCEL,
				fn: function(btnID, text, opt) {
					if(btnID == 'ok') {
						Ext.Ajax.request({
							url: '/pages/menu/config/workflow/edit_workflow.php',
							params: {
								action: 'del_module',
								module_info_id: rec.get('module_info_id')
							},
							callback: function(opts, success, response) {
								try {
									var r = Ext.decode(response.responseText, true);
									if(r.success) {
										//Ext.Msg.alert(_text('MN00022'), r.msg);
										Ext.getCmp('modueInfo').store.reload();
										Ext.getCmp('task_type_list').store.reload();
									}else{
										Ext.Msg.alert(_text('MN00022'), r.msg);
									}
								}catch(e) {
									alert(e.message + '(responseText: ' + response.responseText + ')');
								}
							}
						})
					}
				}
			})
		},

		request: function(p){

			Ext.Ajax.request({
				url: '/pages/menu/config/workflow/edit_module.php',
				params: {
					module_info_id: p.module_info_id,
					module_id : p.module_id
				},
				callback: function(self, success, response){
					try {
						var r = Ext.decode(response.responseText);
						r.show();


					}
					catch(e){
						Ext.Msg.alert('error', e);
					}
				}
			})
		}
	});

	return new Ariel.config.custom.MetadataPanel();
})()