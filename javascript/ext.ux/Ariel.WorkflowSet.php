<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
session_start();
fn_checkAuthPermission($_SESSION);

$workflow_type = $_GET['workflow_type'];
if($_SESSION['user']['super_admin']  == 'Y') {
	//$super_admin_hidden = ' hidden: false, ';
	$is_super_admin = true;
} else {
	//$super_admin_hidden = ' hidden: true, ';
	$is_super_admin = true;
}
?>
(function(){

	Ext.ns('Ariel.config.custom');

	Ariel.config.custom.MetadataPanel = Ext.extend(Ext.Panel, {
		layout: 'border',
		border: false,
		defaults: {
			split: true
		},

		initComponent: function(config){
			Ext.apply(this, config || {});

			this.tableGrid = this.buildTable();
			this.fieldGrid = this.buildField_tree();

		//	this.fieldGrid = this.buildField();

			this.items = [
				this.tableGrid,
				this.fieldGrid
			];

			Ariel.config.custom.MetadataPanel.superclass.initComponent.call(this);
		},

		buildTable: function(){
			var panel_title;
			if('<?=$workflow_type?>' == 'p'){
				panel_title = _text('MN01070');
			}else if('<?=$workflow_type?>' == 'i'){
				panel_title = _text('MN00327');
			}else if('<?=$workflow_type?>' == 'c'){
				panel_title = _text('MN01069');
			}else if('<?=$workflow_type?>' == 's'){
				panel_title = _text('MN02322');
			}

			return new Ext.grid.GridPanel({
				id: 'task_workflow<?=$workflow_type?>',
				//>>title: '작업흐름 설정',
				// title: _text('MN00327'),
				//cls: 'proxima_customize',
				title: '<span class="user_span"><span class="icon_title"><i class=""></i></span><span class="main_title_header">'+panel_title+'</span></span>',
				cls: 'grid_title_customize proxima_customize',
				stripeRows: true,
				border: false,
				region: 'center',
				loadMask: true,
				//enableDragDrop: true,
				ddGroup: 'tableGridDD',
				selModel: new Ext.grid.RowSelectionModel({
					singleSelect: true,
					listeners: {
						rowselect: function(self){
							Ext.getCmp('workflowRule<?=$workflow_type?>').getLoader().load( Ext.getCmp('workflowRule<?=$workflow_type?>').getRootNode() );
							 //Ext.getCmp('workflowRule<?=$workflow_type?>').getStore().load();
						},
						rowdeselect: function(self){
							//Ext.getCmp('workflowRule<?=$workflow_type?>').getStore().removeAll();
						}
					}
				}),
				store: new Ext.data.JsonStore({
					url: '/pages/menu/config/workflow/workflow_list_pre.php',
					root: 'data',
					idPropery: 'task_workflow_id',
					fields: [
						'task_workflow_id',
						'user_task_name',
						'register',
						'description',
						'activity',
						'content_status_nm',
						'content_status',
						'type',
						'preset_type',
						'icon_url',
						'bs_content_id',
						'bs_content_title',
						'creator'
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
				colModel: new Ext.grid.ColumnModel({
					defaults: {
						sortable: true
					},
					columns: [
						
						//>>{header: '작업흐름 명', dataIndex: 'user_task_name'},
						//>>{header: '등록 채널', dataIndex: 'register'},
						//>>{header: '활성화', dataIndex: 'activity'},
						//>>{header: '설 명', dataIndex: 'description'}
						{header: _text('MN01112'), dataIndex: 'user_task_name', width: 200},
						{header: 'No', dataIndex: 'task_workflow_id', width: 15, hidden: true},
						<?php
						if ($workflow_type == 'p' || $workflow_type == 'i' || $workflow_type == 'c') {
						?>
						{header: _text('MN02309'), dataIndex: 'creator', width: 100},
						<?php
						}
						?>
						<?php
						if ($workflow_type == 'p' || $workflow_type == 'i' || $workflow_type == 's') {
						?>
						{header: _text('MN01071'), dataIndex: 'register', width: 100},
						<?php
						}
						?>
						{header: _text('MN00279'), dataIndex: 'bs_content_title', width: 70, align: 'center'},
						{header: _text('MN00347'), dataIndex: 'activity', width: 50, align: 'center', renderer: function (data){
							if (data == '1') {
								return '<span style="color: blue">on</span>';
							} else {
								return '<span style="color: red">off</span>';
							}
						}},
						<?php
						if ($workflow_type == 'p') {
						?>
						{header: _text('MN01114'), dataIndex: 'preset_type', width: 80},
						<?php
						}
						?>
						{header: _text('MN00049'), dataIndex: 'description', width: 300},
						{header: _text('MSG01014'), dataIndex: 'content_status_nm', width: 80, hidden: true}
					]
				}),
				listeners: {
					viewready: function(self){
						self.store.load({
							params: {
								action: 'task_workflow',
								workflow_type: '<?=$workflow_type?>'
							}
						});
						var upGridDroptgtCfg = Ext.apply({}, dropZoneOverrides, {
							table: 'task_workflow',
							id_field: 'task_workflow_id',
							ddGroup: 'tableGridDD',
							grid : Ext.getCmp('task_workflow<?=$workflow_type?>')
						});
						new Ext.dd.DropZone(Ext.getCmp('task_workflow<?=$workflow_type?>').getEl(), upGridDroptgtCfg);
					},
					rowdblclick: {
						<?php
						if ($is_super_admin) {
						?>
						fn: function(self, rowIndex, e) {
							var sel = self.getSelectionModel().getSelected();
							<?php if ($workflow_type == 'p') { ?>
								var title_edit_window = _text('MN00043')+' '+_text('MN01070');
								this.buildEditTableWin(e , sel, title_edit_window);
							<?php }else if($workflow_type == 'i'){ ?>
								var title_edit_window = _text('MN00043')+' '+_text('MN00327');
								this.buildEditTableWin(e , sel, title_edit_window);
							<?php }else if($workflow_type == 's'){ ?>
								var title_edit_window = _text('MN00043')+' '+_text('MN02273');
								this.buildEditTableWin(e , sel, title_edit_window);
							<?php }else{ ?>
								var title_edit_window = _text('MN00043')+' '+_text('MN01069');
								this.buildEditTableWin(e , sel, title_edit_window);
							<?php }?>
						},
						scope: this
						<?php
						}
						?>
					}
				},
				viewConfig: {
					listeners: {
						refresh: function(self) {
							//Ext.getCmp('workflowRule<?=$workflow_type?>').getStore().removeAll();
						}
					},

					forceFit: true,
					emptyText: 'no data'
				},

				buttonAlign: 'center',
				fbar: [{
					text : '<span style="position:relative;top:1px;"><i class="fa fa-plus" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00033'),
					scale: 'medium',
					<?=$super_admin_hidden?>
					handler: function(btn, e) {
						<?php if ($workflow_type == 'p') { ?>
							var title_add_window = _text('MN00033')+' '+_text('MN01070');
							this.buildAddTable(e,title_add_window);
						<?php }else if($workflow_type == 'i'){ ?>
							var title_add_window = _text('MN00033')+' '+_text('MN00327');
							this.buildAddTable(e,title_add_window);
						<?php }else if($workflow_type == 's'){ ?>
							var title_add_window = _text('MN00033')+' '+_text('MN02273');
							this.buildAddTable(e,title_add_window);
						<?php }else{ ?>
							var title_add_window = _text('MN00033')+' '+_text('MN01069');
							this.buildAddTable(e,title_add_window);
						<?php }?>
					},
					scope: this
				},{
					text : '<span style="position:relative;top:1px;"><i class="fa fa-edit" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00043'),
					scale: 'medium',
					<?=$super_admin_hidden?>
					handler: function(btn, e) {
						var hasSelection = Ext.getCmp('task_workflow<?=$workflow_type?>').getSelectionModel().hasSelection();
						if(hasSelection) {
							var sel = Ext.getCmp('task_workflow<?=$workflow_type?>').getSelectionModel().getSelected();
							<?php if ($workflow_type == 'p') { ?>
								var title_edit_window = _text('MN00043')+' '+_text('MN01070');
								this.buildEditTableWin(e , sel, title_edit_window);
							<?php }else if($workflow_type == 'i'){ ?>
								var title_edit_window = _text('MN00043')+' '+_text('MN00327');
								this.buildEditTableWin(e , sel, title_edit_window);
							<?php }else if($workflow_type == 's'){ ?>
								var title_edit_window = _text('MN00043')+' '+_text('MN02273');
								this.buildEditTableWin(e , sel, title_edit_window);
							<?php }else{ ?>
								var title_edit_window = _text('MN00043')+' '+_text('MN01069');
								this.buildEditTableWin(e , sel, title_edit_window);
							<?php }?>
						}else{
							//>>Ext.Msg.alert(lang.errorLabel, '수정할 항목을 선택해주세요');
							Ext.Msg.alert(_text('MN00022'), _text('MSG00084'));
						}
					},
					scope: this
				},{
					text : '<span style="position:relative;top:1px;"><i class="fa fa-trash" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00034'),
					scale: 'medium',
					<?=$super_admin_hidden?>
					handler: function(btn, e) {
						var hasSelection = Ext.getCmp('task_workflow<?=$workflow_type?>').getSelectionModel().hasSelection();
						if(hasSelection) {
							this.buildDeleteTable(e);
						}else{
							//>>Ext.Msg.alert(lang.errorLabel, '삭제하실 작업흐름을 선택해 주세요.');
							Ext.Msg.alert(_text('MN00022'), _text('MSG00022'));
						}
					},
					scope: this
				}]
			});
		},

		buildAddTable: function(e,title_win){  //추가 버튼 기능실행
			var win = new Ext.Window({
				layout: 'fit',
				cls: 'change_background_panel',
				//>>title: '작업흐름 추가',
				title: title_win,
				width: 400,
				height: 300,
				padding: 10,
				modal: true,
				buttonAlign: 'center',
				items: {
					xtype: 'form',
					baseCls: 'x-plain',
					defaultType: 'textfield',
					defaults: {
						anchor: '100%'
					},
					items: [{
						xtype: 'hidden',
						name: 'task_workflow_id'
					},{
						xtype: 'hidden',
						name: 'workflow_type',
						value: '<?=$workflow_type?>'
					},{
						name: 'user_task_name',
						//>>fieldLabel: '작업흐름 명',
						fieldLabel: _text('MN01112'),
						msgTarget: 'under',
						allowBlank: false
					},
					<?php
					if ($workflow_type == 'p') {
					?>
					{
						name: 'bs_content_id',
						xtype: 'combo',
						store: new Ext.data.JsonStore({
							url: '/pages/menu/config/custom/content_metadata/php/get.php',
							baseParams: {
								action: 'content_type_list_workflow'
							},
							root: 'data',
							idProperty: 'bs_content_id',
							fields: [
								'bs_content_title',
								'bs_content_id'
							]
						}),
						typeAhead: true,
						hiddenName: 'bs_content_id',
						hiddenValue: 'bs_content_id',
						displayField: 'bs_content_title',
						valueField: 'bs_content_id',
						hiddenValue: 'bs_content_id',
						//>>fieldLabel: '콘텐츠 종류',
						fieldLabel: _text('MN00279'),
						triggerAction: 'all',
						forceSelection: true,
						editable: false
						//,emptyText: _text('MSG00111')
					},
					<?php
					}
					?>
					<?php
					if ($workflow_type == 'c') {
					?>
					{
						//hidden: true,
						xtype: 'combo',
						fieldLabel: 'Icon url',
						name: 'icon_url',
						emptyText: _text('MSG01033'),
						store: new Ext.data.JsonStore({
							url: '/pages/menu/config/workflow/workflow_list_pre.php',
							baseParams: {
								action: 'get_icon',
							},
							autoLoad: true,
							root: 'data',
							fields: [
								{name: 'code_name'},
								{name: 'code'},
								{name: 'code_id'}
							]
						}),
						hiddenName: 'icon_url',
						hiddenValue: 'code',
						valueField: 'code_id',
						displayField: 'code_name',
						allowBlank: true,
						editable: false,
						typeAhead: true,
						triggerAction: 'all',
						tpl : '<tpl for="."><div class ="x-combo-list-item"><img src="/css/icons/{code}" align="left">&nbsp;&nbsp;&nbsp;{code_name}</div></tpl>'
					},
					<?php
					}
					if ($workflow_type != 'p') {
					?>
					{
						xtype: 'combo',
						fieldLabel: 'Task',
						name: 'workflow_preset',
						store: new Ext.data.JsonStore({
							url: '/pages/menu/config/workflow/workflow_list_pre.php',
							baseParams: {
								<?php
								if($workflow_type == 'i') {
								?>
								action: 'task_workflow_type',
								workflow_type: 'p',
								preset_type: 'ingest'
								<?php
								} else if($workflow_type == 'c') {
								?>
								action: 'task_workflow_type',
								workflow_type: 'p',
								preset_type: 'outgest'
								<?php
								}else if($workflow_type == 's') {
								?>
								action: 'task_workflow_type',
								workflow_type: 'p',
								preset_type: 'system'
								<?php
								}
								?>
							},
							autoLoad: true,
							root: 'data',
							fields: [
								{name: 'user_task_name'},
								{name: 'register'},
								{name: 'task_workflow_id'}
							]
						}),
						hiddenName: 'workflow_preset',
						valueField: 'task_workflow_id',
						displayField: 'user_task_name',
						allowBlank: true,
						editable: false,
						typeAhead: true,
						triggerAction: 'all',
						listeners: {
							select: function(self) {
								var record;

								record = self.getStore().getAt(self.getStore().find('task_workflow_id', self.getValue()));

								self.ownerCt.getForm().findField('register').setValue(record.get('register'));
								self.ownerCt.getForm().findField('task_workflow_id').setValue(record.get('task_workflow_id'));
							}
						}
					},
					<?php
					}
					?>
					{
					<?php
					if ($workflow_type != 'p') {
					?>
						xtype: 'hidden',
					<?php
					}
					?>
						name: 'register',
						//>>fieldLabel: '등록 채널',
						fieldLabel: _text('MN01071'),
						allowBlank: false
					},{
						xtype: 'textarea',
						name: 'description',
						//>>fieldLabel: '설  명'
						fieldLabel: _text('MN00049')
					},{
						xtype: 'checkbox',

						name: 'activity',
						status: 'active',
						//>>fieldLabel: '활성화'
						fieldLabel: _text('MN00347')
					}
					<?php
					if ($workflow_type == 'p') {
					?>
					,{

						xtype: 'radiogroup',
						fieldLabel: _text('MN01114'),
						name: 'preset_type',
						allowBlank: false,
						items: [
							{boxLabel: _text('MN01116'), name: 'preset_type', inputValue: 'ingest'},
							{boxLabel: _text('MN01115'), name: 'preset_type', inputValue: 'outgest'},
							{boxLabel: _text('MN02323'), name: 'preset_type', inputValue: 'system'}
						]

					}
					<?php
					}
					?>
					],
					listeners: {
						afterrender: function(self) {
							<?php
							if ($workflow_type == 'p') {
							?>
							self.getForm().findField('bs_content_id').getStore().load({
								callback: function(s , r){
									self.getForm().findField('bs_content_id').setValue(0);
							}});
							<?php
							}
							?>
						}
					}
				},
				buttons: [{
					text : '<span style="position:relative;top:1px;"><i class="fa fa-plus" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00033'),
					scale: 'medium',
					handler: function(btn, e) {
						//btn.disable();
						win.get(0).getForm().submit({
							url: '/pages/menu/config/workflow/edit_workflow_pre.php',
							params: {
								action: 'add_workflow'
							},
							success: function(form, action) {
								try {
									var result = Ext.decode(action.response.responseText, true);
									if(result.success) {
										win.close();
										Ext.getCmp('task_workflow<?=$workflow_type?>').store.reload();
									}else{
										Ext.Msg.show({
											title: _text('MN00022'),
											icon: Ext.Msg.ERROR,
											msg: result.msg,
											buttons: Ext.Msg.OK
										})
									}
								}catch(e){
									Ext.Msg.show({
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
									//>>title: lang.errorLabel,
									title: _text('MN00022'),
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

		buildEditTableWin: function(e, sel,title_win) { //수정버튼 기능 실행
			var _submit = function(){
				win.get(0).getForm().submit({
					url: '/pages/menu/config/workflow/edit_workflow_pre.php',
					params: {
						action: 'edit_workflow'
					},
					success: function(form, action){
						try {
							var result = Ext.decode(action.response.responseText, true);
							if (result.success) {
								// 정상
								win.close();
								Ext.getCmp('task_workflow<?=$workflow_type?>').store.reload();
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
				layout: 'fit',
				cls: 'change_background_panel',
				//>>title: '작업흐름 수정',
				title: title_win,
				width: 400,
				height: 300,
				padding: 10,
				modal: true,
				buttonAlign: 'center',
				items: {
					xtype: 'form',
					baseCls: 'x-plain',
					defaultType: 'textfield',
					defaults: {
						anchor: '100%'
					},
					items: [{
						xtype: 'hidden',
						name: 'task_workflow_id'
					},{
						name: 'user_task_name',
						//>>fieldLabel: '작업흐름 명',
						fieldLabel: _text('MN01112'),
						msgTarget: 'under',
						allowBlank: false,
						listeners: {
							render: function(self){
								self.focus(true, 500);
							}
						}
					}
					<?php
					if ($workflow_type == 'c') {
					?>
					,{
						//hidden: true,
						xtype: 'combo',
						fieldLabel: 'Icon url',
						name: 'icon_url',
						emptyText: _text('MSG01033'),
						store: new Ext.data.JsonStore({
							url: '/pages/menu/config/workflow/workflow_list_pre.php',
							baseParams: {
								action: 'get_icon',
							},
							autoLoad: true,
							root: 'data',
							fields: [
								{name: 'code_name'},
								{name: 'code'},
								{name: 'code_id'}
							]
						}),
						hiddenName: 'icon_url',
						hiddenValue: 'code',
						valueField: 'code_id',
						displayField: 'code_name',
						allowBlank: true,
						editable: false,
						typeAhead: true,
						triggerAction: 'all',
						tpl : '<tpl for="."><div class ="x-combo-list-item"><img src="/css/icons/{code}" align="left">&nbsp;&nbsp;&nbsp;{code_name}</div></tpl>'
					}
					<?php
					}
					?>
					<?php
					if ($workflow_type == 'p') {
					?>
					,{
						name: 'bs_content_id',
						xtype: 'combo',
						store: new Ext.data.JsonStore({
							url: '/pages/menu/config/custom/content_metadata/php/get.php',
							baseParams: {
								action: 'content_type_list_workflow'
							},
							root: 'data',
							idProperty: 'bs_content_id',
							fields: [
								'bs_content_title',
								'bs_content_id'
							]
						}),
						typeAhead: true,
						hiddenName: 'bs_content_id',
						hiddenValue: sel.get('bs_content_id'),
						displayField: 'bs_content_title',
						valueField: 'bs_content_id',
						//>>fieldLabel: '콘텐츠 종류',
						fieldLabel: _text('MN00279'),
						triggerAction: 'all',
						forceSelection: true,
						editable: false
						//,emptyText: _text('MSG00111')
					}
					<?php
					}
					?>
					,{
					<?php
					if ($workflow_type != 'p') {
					?>
						readOnly: true,
						style: 'color: gray',
					<?php
					}
					?>
						name: 'register',
						//>>fieldLabel: '등록 채널',
						fieldLabel: _text('MN01071'),
						allowBlank: false
					}
					,{
						xtype: 'textarea',
						name: 'description',
						//>>fieldLabel: '설 명'
						fieldLabel: _text('MN00049')
					}
					,{
						xtype: 'checkbox',
						name: 'activity',
						//>>fieldLabel: '활성화'
						fieldLabel: _text('MN00347')
					}
					<?php
					if ($workflow_type == 'p') {
					?>
					,{

						xtype: 'radiogroup',
						fieldLabel: _text('MN01114'),
						name: 'preset_type',
						allowBlank: false,
						items: [
							{boxLabel: _text('MN01116'), name: 'preset_type', inputValue: 'ingest'},
							{boxLabel: _text('MN01115'), name: 'preset_type', inputValue: 'outgest'},
							{boxLabel: _text('MN02323'), name: 'preset_type', inputValue: 'system'}
						]

					}
					<?php
					}
					?>
					],
					keys: [{
						key: 13,
						handler: function(){
							_submit();
						}
					}],
					listeners: {
						afterrender: function(self) {
							var sm = Ext.getCmp('task_workflow<?=$workflow_type?>').getSelectionModel();
							var rec = sm.getSelected();
							self.getForm().loadRecord(rec);

							<?php
							if ($workflow_type == 'p') {
							?>
							self.getForm().findField('bs_content_id').getStore().load({
								callback: function(s , r){
								self.getForm().loadRecord(rec);
							}});
							<?php
							}
							?>

							<?php
							if ($workflow_type == 'c') {
							?>
							self.getForm().findField('icon_url').getStore().load({
								callback: function(s , r){
								self.getForm().loadRecord(rec);
							}});
							<?php
							}
							?>
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
			var rec = Ext.getCmp('task_workflow<?=$workflow_type?>').getSelectionModel().getSelected();

			// 삭제 확인 창
			Ext.Msg.show({
				animEl: e.getTarget(),
				//>>title: '확인',
				title: _text('MN00024'),
				icon: Ext.Msg.INFO,
				//>>msg: rec.get('user_task_name') + '" 작업흐름 을 삭제 하시겠습니까?',
				msg: '"' + rec.get('user_task_name') + '"' + _text('MSG00172'),
				buttons: Ext.Msg.OKCANCEL,
				fn: function(btnID, text, opt) {
					if(btnID == 'ok') {
						Ext.Ajax.request({
							url: '/pages/menu/config/workflow/edit_workflow_pre.php',
							params: {
								action: 'delete_workflow',
								task_workflow_id: rec.get('task_workflow_id')
							},
							callback: function(opts, success, response) {
								try {
									var r = Ext.decode(response.responseText, true);
									if(r.success) {
										Ext.Msg.alert(_text('MN00024'), r.msg);
										Ext.getCmp('task_workflow<?=$workflow_type?>').store.reload();

										 Ext.getCmp('workflowRule<?=$workflow_type?>').getRootNode().removeAll();
										//Ext.getCmp('workflowRule<?=$workflow_type?>').getLoader().removeAll();//load( Ext.getCmp('workflowRule<?=$workflow_type?>').getRootNode() );
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

		buildField_tree: function(){
			function _renderCheck(v) {
				switch (v) {
					case 1:
					case '1':
						return '<span style="color: blue">on</span>';
					break;

					case 0:
					case '0':
					case '':
					case null:
						return '<span style="color: #CCCCCC">off</span>';
					break;
				}
			}

			function _submit(url) {
				Ext.getCmp('field_form').getForm().submit({
					url: '/pages/menu/config/custom/user_metadata/php/' + url,
					params: {
						action: 'field'
					},
					success: function(form, action) {
						try {
							var result = Ext.decode(action.response.responseText, true);
							if (result.success) {
								Ext.getCmp('field_win<?=$workflow_type?>').close();
								Ext.getCmp('meta_field').store.reload();
							} else {
								Ext.Msg.show({
									//>>title: lang.errorLabel,
									title: _text('MN00022'),
									icon: Ext.Msg.ERROR,
									msg: result.msg,
									buttons: Ext.Msg.OK
								})
							}
						} catch (e) {
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
							//>>title: lang.errorLabel,
							title: _text('MN00022'),
							msg: action.result.msg,
							buttons: Ext.Msg.OK
						});
					}
				});
			}

			function _buildWindow(btn){
				return new Ext.Window({
					id: 'field_win<?=$workflow_type?>',
					layout: 'fit',
					//title: lang.userFieldWindowTitle + ' ' + btn.getText(),
					title: _text('MN00164') + ' ' + btn.getText(),
					width: 400,
					height: 300,
					padding: 10,
					modal: true,
					items: {},
					buttons: [{
						url: btn.url,
						text: btn.getText(),
						handler: function(btn, e){
							_submit(btn.url)
						}
					},{
						text: _text('MN00004'),//'취소',
						handler: function(){
							this.ownerCt.ownerCt.close();
						}
					}]
				});
			}

			function _workflow_rule_edit(action, params){

				Ext.Ajax.request({
					url:'/pages/menu/config/workflow/edit_workflow_pre.php',
					params: {
						action : action,
						params: params
					},
					callback: function(opt,suc,res){
						if(suc){
							var r = Ext.decode(res.responseText);
							if(r.success) {

								Ext.getCmp('workflowRule<?=$workflow_type?>').getLoader().load( Ext.getCmp('workflowRule<?=$workflow_type?>').getRootNode() );

								return true;
							}
							else {
								Ext.Msg.alert(_text('MN01039'),r.msg);//'오류'
							}
						}
						else{
							return false;
						}
					}
				});
			}

			return  new Ext.ux.tree.TreeGrid({
				id: 'workflowRule<?=$workflow_type?>',
				//>>title:	"작업흐름 상세",
				//title:	_text('MN00365'),
				title: '<span class="user_span"><span class="icon_title"><i class=""></i></span><span class="main_title_header">'+_text('MN00365')+'</span></span>',
				cls: 'grid_title_customize proxima_customize_gridtree',
				border: false,
				enableDD: true,
				region: 'south',

				split: true,
				//collapsible: true,
				ddGroup: 'workflowRuleDD',
				allowContainerDrop: true,
				height: 350,
				loadMask: true,
				loader: new Ext.tree.TreeLoader({
					autoLoad: true,
					baseParams: {
					},
					listeners: {
						load: function( self,  node, response ){
							var tab_id = 'workflowRule<?=$workflow_type?>';
							var workflow_rule_tree_grid_element = document.getElementById(tab_id).getElementsByClassName('x-treegrid-col');
							for(var i = 0; i<workflow_rule_tree_grid_element.length; i++){
								var node_i = workflow_rule_tree_grid_element[i];
								if(node_i.childNodes.length == 4){
									node_i.className = 'grid_tree_remove_border x-treegrid-col';
								}
							}
						},
						beforeload: function ( self,  node, response ) {

							if( !Ext.isEmpty( Ext.getCmp('task_workflow<?=$workflow_type?>').getSelectionModel().getSelected() ) ) {
								self.baseParams.task_workflow_id = Ext.getCmp('task_workflow<?=$workflow_type?>').getSelectionModel().getSelected().get('task_workflow_id');
							}
						}
					},
					dataUrl: '/pages/menu/config/workflow/workflow_rule_tree_pre.php'
				}),

				columns: [
					{header: _text('MN00366'), dataIndex: 'job_no', width: 120, align:'center'},
					{header: _text('MN00367'), dataIndex: 'job_name', width: 200},
					{header: _text('MN00299'), dataIndex: 'parameter', width: 300, hidden: true},
					{header: _text('MN00343'), dataIndex: 'src_path', width: 250},
					{header: _text('MN00344'), dataIndex: 'tar_path', width: 250},
					{header: _text('MSG01014'), dataIndex:	'content_status_nm', width: 100},
					{header: 'task_rule_id', dataIndex:	'task_rule_id', width: 250 , hidden: true },
					{header: 'task_rule_parent_id', dataIndex:	'task_rule_parent_id', width: 250 , hidden: true }
				],

				listeners: {
					dblclick: function(  node, e ){
							//console.log(node.getSelectionModel());

					},
					click: function ( node, e ){

						Ext.getCmp('workflowRule<?=$workflow_type?>').getFooterToolbar().items.each(function(r){
							if( node.id == '0'  && ( r.text == _text('MN00034') || r.text == _text('MN00043') ) )
							{
								r.setDisabled(true);
							}
							else
							{
								r.setDisabled(false);
							}
						});
					},
					viewready: function(self){

					},
					contextmenu: function(node, e) {
						node.select();
						var c = node.getOwnerTree().contextMenu;
						c.contextNode = node;

						c.items.each(function(r){
							if( node.id == '0'  && ( r.text == _text('MN00034') || r.text == _text('MN00043') ) )
							{
								r.setVisible(false);
							}
							else
							{
								r.setVisible(true);
							}
						});

						c.showAt(e.getXY());
					},
					append: function( tree, parent, node, index ){

					},
					beforemovenode: function( tree, node, oldParent, newParent, index ){

						var params = [];

						params.push( {
							'type' : 'node',
							'job_priority' : node.getDepth() - 1  ,
							'task_rule_id' : node.attributes.task_rule_id,
							'task_rule_parent_id' :	node.attributes.task_rule_parent_id	,
							'task_workflow_id' :	node.attributes.task_workflow_id,
							'workflow_rule_id' :	node.attributes.workflow_rule_id
						});

						params.push( {
							'type' : 'newParent',
							'job_priority' : newParent.getDepth() - 1 ,
							'task_rule_id' : newParent.attributes.task_rule_id,
							'task_rule_parent_id' :	newParent.attributes.task_rule_parent_id,
							'task_workflow_id' :	newParent.attributes.task_workflow_id,
							'workflow_rule_id' :	newParent.attributes.workflow_rule_id
						});

						 _workflow_rule_edit( 'edit_workflow_rule_sort', Ext.encode(params) );

						//return false;
					}
				},
				contextMenu: new Ext.menu.Menu({
					items: [
						{
							//>>text: lang.addLabel,
							text: _text('MN00033'),
							scale: 'medium',
							handler: function(btn, e) {
								var hasSelection = Ext.getCmp('task_workflow<?=$workflow_type?>').getSelectionModel().hasSelection();
								if(hasSelection) {
									this.buildAddWorkflowRuleWin(e);
								}else{
									//>>Ext.Msg.alert(lang.errorLabel, '작업흐름을 선택해 주세요');
									Ext.Msg.alert(_text('MN00022'), _text('MSG00176'));
								}
							},
							scope: this
						},{
							//>>text: lang.editLabel,
							text: _text('MN00043'),
							scale: 'medium',
							handler: function(btn, e) {
								var hasSelection = Ext.getCmp('task_workflow<?=$workflow_type?>').getSelectionModel().hasSelection();
								if(hasSelection) {
									this.buildEditWorkflowRuleWin(e);
								}else{
									//>>Ext.Msg.alert(lang.errorLabel, lang.editTableAlertEmptyText);
									Ext.Msg.alert(_text('MN00022'), _text('MSG00169'));
								}
							},
							scope: this
						},{
							//>>text: lang.deleteLabel,
							text: _text('MN00034'),
							scale: 'medium',
							handler: function(btn, e) {
								var hasSelection = Ext.getCmp('task_workflow<?=$workflow_type?>').getSelectionModel().hasSelection();
								if(hasSelection) {
									this.buildDeleteRuleWin(e);
								}else{
									//>>Ext.Msg.alert(lang.errorLabel, lang.deleteTableAlertEmptyText);
									Ext.Msg.alert(_text('MN00022'), _text('MSG00170'));
								}
							},
							scope: this
						}
					],
					listeners: {
						itemclick: {
							fn: function(item, e){
								var r = item.parentMenu.contextNode.getOwnerTree();
								switch (item.cmd) {
								}
							},
							scope: this
						}
					}
				}),
				viewConfig: {
					//>>emptyText: '작업흐름을 선택해 주세요.'
					emptyText: _text('MSG00176')
				},

				buttonAlign: 'center',
				buttons: [{
					text : '<span style="position:relative;top:1px;"><i class="fa fa-plus" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00033'),
					scale: 'medium',
					<?=$super_admin_hidden?>
					handler: function(btn, e) {
						var hasSelection = Ext.getCmp('task_workflow<?=$workflow_type?>').getSelectionModel().hasSelection();
						if(hasSelection) {
							this.buildAddWorkflowRuleWin(e);
						}else{
							//>>Ext.Msg.alert(lang.errorLabel, '작업흐름을 선택해 주세요');
							Ext.Msg.alert(_text('MN00022'), _text('MSG00176'));
						}
					},
					scope: this
				},{
					text : '<span style="position:relative;top:1px;"><i class="fa fa-edit" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00043'),
					scale: 'medium',
					handler: function(btn, e) {
						var hasSelection = Ext.getCmp('task_workflow<?=$workflow_type?>').getSelectionModel().hasSelection();
						if (hasSelection) {
							this.buildEditWorkflowRuleWin(e);
						} else {
							Ext.Msg.alert(_text('MN00022'), _text('MSG00169'));
						}
					},
					scope: this
				},{
					text : '<span style="position:relative;top:1px;"><i class="fa fa-trash" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00034'),
					scale: 'medium',
					<?=$super_admin_hidden?>
					handler: function(btn, e) {
						var hasSelection = Ext.getCmp('task_workflow<?=$workflow_type?>').getSelectionModel().hasSelection();
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
		},

		buildAddWorkflowRuleWin: function(e){  //작업 상세 추가 버튼 기능실행
			var win = new Ext.Window({
				layout: 'fit',
				cls: 'change_background_panel',
				//>>title: '작업 추가',
				title: _text('MN00070'),
				width: 400,
				height: 150,
				padding: 10,
				modal: true,
				buttonAlign: 'center',
				items: {
					xtype: 'form',
					baseCls: 'x-plain',
					defaultType: 'textfield',
					defaults: {
						anchor: '100%'
					},
					items: [{
						xtype: 'combo',
						//>>fieldLabel: '작업 명',
						fieldLabel: _text('MN00345'),
						name: 'user_task_name',
						store: new Ext.data.JsonStore({
							url: '/pages/menu/config/workflow/workflow_list_pre.php',
							baseParams: {
								action: 'task_rule'
							},
							root: 'data',
							idProperty: 'task_rule_id',
							fields: [
								{name: 'job_name', type: 'string'},
								{name: 'task_rule_id', type: 'int'},
								{name: 'source_path', type: 'string'},
								{name: 'target_path', type: 'string'}
							]
						}),
						allowBlank: false,
						hiddenName: 'task_rule_id',
						valueField: 'task_rule_id',
						displayField: 'job_name',
						typeAhead: true,
						triggerAction: 'all',
						forceSelection: true,
						editable: false,
						//>>emptyText: '추가할 작업을 선택하세요'
						emptyText: _text('MSG00186'),
						listeners: {
							select: function(self, record, index) {
								var s_name = record.get('source_path');
								var t_name = record.get('target_path');

								self.ownerCt.getForm().findField('s_name').setValue(s_name);
								self.ownerCt.getForm().findField('t_name').setValue(t_name);
							}
						}
					},{
						xtype: 'combo',
						fieldLabel: _text('MSG01014'),
						name: 'content_status',
						store: new Ext.data.JsonStore({
							url: '/pages/menu/config/workflow/workflow_list_pre.php',
							baseParams: {
								action: 'content_status_type_list'
							},
							autoLoad: true,
							root: 'data',
							idProperty: 'code',
							fields: [
								{name: 'name'},
								{name: 'code'}
							]
						}),
						allowBlank: true,
						hiddenName: 'content_status',
						valueField: 'code',
						displayField: 'name',
						typeAhead: true,
						triggerAction: 'all',
						forceSelection: true,
						editable: false
					},{
						xtype: 'textfield',
						name: 's_name',
						hidden: true
					},{
						xtype: 'textfield',
						name: 't_name',
						hidden: true
					}]
				},
				buttons: [{
					text : '<span style="position:relative;top:1px;"><i class="fa fa-plus" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00033'),
					scale: 'medium',
					handler: function(btn, e) {
						//btn.disable();
						var add_task_id = Ext.getCmp('task_workflow<?=$workflow_type?>').getSelectionModel().getSelected().get('task_workflow_id');

						if( !Ext.isEmpty( Ext.getCmp('workflowRule<?=$workflow_type?>').getSelectionModel().getSelectedNode() ) )
						{
							var task_rule_parent_id = Ext.getCmp('workflowRule<?=$workflow_type?>').getSelectionModel().getSelectedNode().attributes.task_rule_id;
							var job_priority =  parseInt( Ext.getCmp('workflowRule<?=$workflow_type?>').getSelectionModel().getSelectedNode().attributes.job_no ) + 1;
							var workflow_rule_parent_id = Ext.getCmp('workflowRule<?=$workflow_type?>').getSelectionModel().getSelectedNode().attributes.workflow_rule_id;
						}
						else
						{
							var task_rule_parent_id = 0;
							var job_priority = 1;
							var workflow_rule_parent_id = 0;
						}

						win.get(0).getForm().submit({

							url: '/pages/menu/config/workflow/edit_workflow_pre.php',
							params: {
								action: 'add_task_rule',
								task_rule_parent_id : task_rule_parent_id,
								workflow_rule_parent_id: workflow_rule_parent_id,
								job_priority : job_priority,
								task_workflow_id: add_task_id
							},
							success: function(form, action) {
								try {
									var result = Ext.decode(action.response.responseText, true);
									if(result.success) {
										win.close();
										Ext.getCmp('workflowRule<?=$workflow_type?>').getLoader().load( Ext.getCmp('workflowRule<?=$workflow_type?>').getRootNode() );

										//Ext.getCmp('workflowRule<?=$workflow_type?>').store.reload();
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
									//>>title: lang.errorLabel,
									title: _text('MN00022'),
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

		// 작업 상세 수정버튼 기능 실행
		buildEditWorkflowRuleWin: function(e) {

			var _submit = function() {
				win.get(0).getForm().submit({
					url: '/pages/menu/config/workflow/edit_workflow_pre.php',
					params: {
						action: 'edit_workflow_rule'
					},
					success: function(form, action){
						try {
							var result = Ext.decode(action.response.responseText, true);
							if (result.success) {
								win.close();
								Ext.getCmp('workflowRule<?=$workflow_type?>').getLoader().load( Ext.getCmp('workflowRule<?=$workflow_type?>').getRootNode() );
							} else {
								Ext.Msg.show({
									title: _text('MN00022'),
									icon: Ext.Msg.ERROR,
									msg: result.msg,
									buttons: Ext.Msg.OK
								})
							}
						} catch (e) {
							Ext.Msg.show({
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
				layout: 'fit',
				cls: 'change_background_panel',
				//title: '작업흐름 상세 수정',
				title: _text('MN00369'),
				width: 400,
				height: 180,
				padding: 10,
				modal: true,
				buttonAlign: 'center',
				items: {
					xtype: 'form',
					baseCls: 'x-plain',
					defaultType: 'textfield',
					defaults: {
						anchor: '100%'
					},
					items: [{
						xtype: 'hidden',
						name: 'workflow_rule_id'
					}, {
						hidden: true,
						xtype: 'combo',
						fieldLabel: _text('MN00345'),
						name: 'job_name',
						store: new Ext.data.JsonStore({
							url: '/pages/menu/config/workflow/workflow_list_pre.php',
							baseParams: {
								action: 'task_rule'
							},
							root: 'data',
							idProperty: 'task_rule_id',
							fields: [
								{name: 'job_name', type: 'string'},
								{name: 'task_rule_id', type: 'int'}
							]
						}),
						allowBlank: false,
						hiddenName: 'task_rule_id',
						valueField: 'task_rule_id',
						displayField: 'job_name',
						typeAhead: true,
						triggerAction: 'all',
						forceSelection: true,
						editable: false
					}, {
						xtype: 'combo',
						fieldLabel: 'Source Storage',
						name: 'source_storage',
						store: new Ext.data.JsonStore({
							url: '/store/get_storage.php',
							root: 'data',
							fields: [
								'description',
								'login_id',
								'login_pw',
								'name',
								'path',
								'path_for_mac',
								'storage_id',
								'type',
								'virtual_path',
								'display_name'
							]
						}),
						hiddenName: 'source_storage_id',
						allowBlank: false,
						valueField: 'storage_id',
						displayField: 'display_name',
						typeAhead: true,
						triggerAction: 'all',
						editable: false
					}, {
						xtype: 'combo',
						fieldLabel: 'Target Storage',
						name: 'target_storage',
						store: new Ext.data.JsonStore({
							url: '/store/get_storage.php',
							root: 'data',
							fields: [
								'description',
								'login_id',
								'login_pw',
								'name',
								'path',
								'path_for_mac',
								'storage_id',
								'type',
								'virtual_path',
								'display_name'
							]
						}),
						hiddenName: 'target_storage_id',
						allowBlank: false,
						valueField: 'storage_id',
						displayField: 'display_name',
						typeAhead: true,
						triggerAction: 'all',
						editable: false
					}, {
						xtype: 'combo',
						fieldLabel: _text('MSG01014'),
						name: 'content_status',
						store: new Ext.data.JsonStore({
							url: '/pages/menu/config/workflow/workflow_list_pre.php',
							baseParams: {
								action: 'content_status_type_list'
							},
							autoLoad: true,
							root: 'data',
							idProperty: 'code',
							fields: [
								{name: 'name'},
								{name: 'code'}
							],
							listeners: {
							}
						}),
						allowBlank: true,
						hiddenName: 'content_status',
						valueField: 'code',
						displayField: 'name',
						typeAhead: true,
						triggerAction: 'all',
						forceSelection: true,
						editable: false
					}],

					keys: [{
						key: 13,
						handler: function(){
							_submit();
						}
					}],
					listeners: {
						afterrender: function(self) {

							var sm = Ext.getCmp('workflowRule<?=$workflow_type?>').getSelectionModel();
							var rec = sm.getSelectedNode().attributes;
							var data = new Ext.data.Record(rec);

							self.getForm().loadRecord(data);
							self.getForm().findField('content_status').getStore().load({
								callback: function(s, r) {
									self.getForm().findField('content_status').setValue(rec.content_status);
								}
							});
							self.getForm().findField('source_storage_id').getStore().load({
								callback: function(s, r) {
									self.getForm().findField('source_storage_id').setValue(rec.source_storage_id);
								}
							});
							self.getForm().findField('target_storage_id').getStore().load({
								callback: function(s, r) {
									self.getForm().findField('target_storage_id').setValue(rec.target_storage_id);
								}
							});
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

		buildDeleteRuleWin: function(e) {

			var sm = Ext.getCmp('workflowRule<?=$workflow_type?>').getSelectionModel();
			var rec = sm.getSelectedNode().attributes;

			// 삭제 확인 창
			Ext.Msg.show({
				animEl: e.getTarget(),
				title: _text('MN00024'),//'삭제 확인',
				icon: Ext.Msg.INFO,
				//>>msg: '선택하신 "' + rec.get('job_name') + '" 작업 을 삭제 하시겠습니까?',
				msg: '"' + rec.job_name + '"' + _text('MSG00172'),
				buttons: Ext.Msg.OKCANCEL,
				fn: function(btnID, text, opt) {
					if(btnID == 'ok') {
						Ext.Ajax.request({
							url: '/pages/menu/config/workflow/edit_workflow_pre.php',
							params: {
								action: 'del_task_rule',
								workflow_rule_id: rec.workflow_rule_id
							},
							callback: function(opts, success, response) {
								try {
									var r = Ext.decode(response.responseText, true);
									if(r.success) {
										Ext.getCmp('workflowRule<?=$workflow_type?>').getLoader().load( Ext.getCmp('workflowRule<?=$workflow_type?>').getRootNode() );
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
		}
	});

	return new Ariel.config.custom.MetadataPanel();
})()
