(function(){
	Ext.ns('Ariel.config.custom');

	Ariel.config.custom.MetadataPanel = Ext.extend(Ext.Panel, {
		layout: 'border',
		title: '<span class="user_span"><span class="icon_title"><i class=""></i></span><span class="main_title_header">'+_text('MN02040')+'</span></span>',
		cls: 'grid_title_customize proxima_customize',
		border: false,
		defaults: {
			//split: true
		},

		initComponent: function(config){
			Ext.apply(this, config || {});

			this.tableGrid = this.buildTable();

			this.items = [
				this.tableGrid
			];

			Ariel.config.custom.MetadataPanel.superclass.initComponent.call(this);
		},

		buildTable: function(){
			return new Ext.grid.GridPanel({
				xtype:	'grid',
				stripeRows: true,
				border: false,
				id: 'modulelist',
				//!!title: '스토리지 설정',
				//title: _text('MN00329'),
				region: 'center',
				loadMask: true,
				//split: true,
				//collapsible: true,
				selModel: new Ext.grid.RowSelectionModel({
					singleSelect: true
				}),
				store: new Ext.data.JsonStore({
					url: '/pages/menu/config/workflow/workflow_list.php',
					root: 'data',
					idPropery: 'task_type_id',
					fields: [
						{name: 'task_type_id', type: 'int'},
						{name: 'type', type: 'int'},
						'name'
					],
					sortInfo: {
						field: 'type',
						direction: 'asc'
					},
					listeners: {
						exception: function(self, type, action, options, response, arg){
							if(type == 'response') {
								if(response.status == '200') {
									Ext.Msg.alert(_text('MN00023'), response.responseText);
								}else{
									Ext.Msg.alert(_text('MN00023'), response.status);
								}
							}else{
								Ext.Msg.alert(_text('MN00023'), type);
							}
						}
					}
				}),

				cm: new Ext.grid.ColumnModel({
					defaults: {
						sortable: true,
						menuDisabled: true
					},
					columns: [
						{header: _text('MN01026'), dataIndex: 'type', width: 100 ,align: 'center'},
						{header: 'ID', dataIndex: 'task_type_id', width: 30, align: 'center', hidden: true},
						{header: _text('MN00236'), dataIndex: 'name', width: 350}
					]
				}),

				listeners: {
					viewready: function(self){
						self.store.load({
							params: {
								action: 'task_type_list'
							}
						});
					},
					rowdblclick: {
						fn: function(self, rowIndex, e){
							this.buildEditTableWin(e);
						},
						scope: this
					}
				},
				viewConfig: {
					forceFit: true,
					emptyText: _text('MSG00020')
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
						var hasSelection = Ext.getCmp('modulelist').getSelectionModel().hasSelection();
						if(hasSelection) {

							this.buildEditTableWin(e);

						}else{
							Ext.Msg.alert(_text('MN00023'), _text('MSG00084'));
						}
					},
					scope: this
				},{
					text : '<span style="position:relative;top:1px;"><i class="fa fa-trash" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00034'),
					scale: 'medium',
					handler: function(btn, e) {
						var hasSelection = Ext.getCmp('modulelist').getSelectionModel().hasSelection();
						if(hasSelection) {
							this.buildDeleteTable(e);
						}else{
							//Ext.Msg.alert(lang.errorLabel, '삭제하실 스토리지를 선택해 주세요.');
							Ext.Msg.alert(_text('MN00023'), _text('MSG00022'));

						}
					},
					scope: this
				}]
			});
		},
		buildAddTable: function(e){  //추가 버튼 기능실행
			var win = new Ext.Window({
				id: 'add_bc_module_win',
				cls: 'change_background_panel',
				layout: 'fit',
				title: _text('MN02056')+' '+ _text('MN00033'),//'작업 유형 추가'
				width: 400,
				height: 150,
				padding: 10,
				modal: true,
				buttonAlign: 'center',
				items: {
					id: 'add_bc_module_form',
					xtype: 'form',
					baseCls: 'x-plain',
					defaultType: 'textfield',
					defaults: {
						anchor: '100%'
					},
					items: [{
						xtype : 'numberfield',
						name: 'task_type',
						//fieldLabel: '작업 유형'
						fieldLabel: _text('MN02056')
							//타입 값
					},{
						name: 'task_name',
						//fieldLabel: '작업명'
						fieldLabel: _text('MN00236')
							//타입명
					}]
				},
				buttons: [{
					text : '<span style="position:relative;top:1px;"><i class="fa fa-plus" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00033'),
					scale: 'medium',
					handler: function(btn, e) {

						Ext.getCmp('add_bc_module_form').getForm().submit({
							url: '/pages/menu/config/workflow/edit_workflow.php',
							params: {
								action: 'add_task_type'
							},
							success: function(form, action) {
								try {
									var result = Ext.decode(action.response.responseText, true);
									if(result.success) {
										Ext.getCmp('add_bc_module_win').close();
										Ext.getCmp('modulelist').store.reload();
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

		buildEditTableWin: function(e) { //수정버튼 기능 실행

			var _submit = function(){
				Ext.getCmp('edit_bc_module_form').getForm().submit({
					url: '/pages/menu/config/workflow/edit_workflow.php',
					params: {
						action: 'edit_task_type'
					},
					success: function(form, action){
						try {
							var result = Ext.decode(action.response.responseText, true);
							if (result.success) {
								// 정상
								Ext.getCmp('edit_bc_module_win').close();
								Ext.getCmp('modulelist').store.reload();
							}
							else {
								// 쿼리 에러
								Ext.Msg.show({
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
							title: _text('MN00022'),
							msg: action.result.msg,
							buttons: Ext.Msg.OK
						});
					}
				});
			}

			var win = new Ext.Window({
				id: 'edit_bc_module_win',
				cls: 'change_background_panel',
				layout: 'fit',
				//!!title: '모듈타입 수정',
				title: _text('MN02056')+' ' + _text('MN00043'),//'작업 유형'
				width: 400,
				height: 150,
				padding: 10,
				modal: true,
				buttonAlign: 'center',
				items: {
					id: 'edit_bc_module_form',
					xtype: 'form',
					baseCls: 'x-plain',
					defaultType: 'textfield',
					defaults: {
						anchor: '100%'
					},
					items: [{
						name: 'task_type_id',
						hidden: true
					},{
						xtype : 'numberfield',
						name: 'type',
						style: 'color: gray',
						//fieldLabel: '작업 유형',
						fieldLabel: _text('MN02056'),
						msgTarget: 'under',
						readOnly: true,
						allowBlank: false
					},{
						name: 'name',
						//fieldLabel: '작업명'
						fieldLabel: _text('MN00236')

					}],
					keys: [{
						key: 13,
						handler: function(){
							_submit();
						}
					}],
					listeners: {
						afterrender: function(self) {
							var sm = Ext.getCmp('modulelist').getSelectionModel();
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
			var rec = Ext.getCmp('modulelist').getSelectionModel().getSelected();

			// 삭제 확인 창
			Ext.Msg.show({
				animEl: e.getTarget(),
				title: _text('MN00003'),
				icon: Ext.Msg.INFO,
				msg: _text('MN02056') +' '+ rec.get('name') +' : '+ _text('MSG00140'),//'작업 유형'
				buttons: Ext.Msg.OKCANCEL,
				fn: function(btnID, text, opt) {
					if(btnID == 'ok') {
						Ext.Ajax.request({
							url: '/pages/menu/config/workflow/edit_workflow.php',
							params: {
								action: 'delete_task_type',
								task_type_id : rec.get('task_type_id')
							},
							callback: function(opts, success, response) {
								try {
									var r = Ext.decode(response.responseText, true);
									if(r.success) {
										//Ext.Msg.alert(_text('MN00023'), r.msg);
										Ext.getCmp('modulelist').store.reload();
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