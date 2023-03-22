(function(){
	Ext.ns('Ariel.config.archive');

	Ariel.config.archive.ArchiveConfigPanel = Ext.extend(Ext.Panel, {
		layout: 'border',
		border: false,
		defaults: {
			split: true
		},

		initComponent: function(config){
			Ext.apply(this, config || {});

			this.tableGrid = this.buildTable();

			this.items = [
				this.tableGrid
			];

			Ariel.config.archive.ArchiveConfigPanel.superclass.initComponent.call(this);
		},

		buildTable: function(){
			return new Ext.tree.Panel({
				xtype:	'treecolumn',
				id: 'category_id',
				region: 'center',
				loadMask: true,
				split: true,
                                singleExpand: true,
				store: new Ext.data.JsonStore({
					url: '/pages/menu/config/archive/php/get_categories.php',
					root: 'data',
					fields: [
						'category_id',
						'category_title',
						'arc_method',
						'arc_period',
						'del_method',
						'del_period',
						'edit_date'
					],
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
				
				colModel: new Ext.grid.ColumnModel({
					defaults: {
						sortable: true
					},
					columns: [
						/*!!
						{header: 'ID', dataIndex: 'storage_id', width: 40, align: 'center'},
						{header: '스토리지 명', dataIndex: 'name', width: 120},
						{header: '경 로', dataIndex: 'path', width: 200},
						{header: '유 형', dataIndex: 'type', width: 40},
						{header: '접근 ID', dataIndex: 'login_id', width: 70},
						{header: '접근 PW', dataIndex: 'login_pw', width: 70},
						{header: '설 명', dataIndex: 'description', width: 220}
						*/

						new Ext.grid.RowNumberer(),
						{header: _text('MN00210'), dataIndex: 'storage_id', width: 40, align: 'center', hidden: true},
						{header: _text('MN00377'), dataIndex: 'name', width: 200},
						{header: _text('MN00376'), dataIndex: 'path', width: 300},
						{header: _text('MN00291'), dataIndex: 'type', width: 40},
						{header: _text('MN00378'), dataIndex: 'login_id', width: 70,},
						{header: _text('MN00379'), dataIndex: 'login_pw', width: 70},
						{header: _text('MN00049'), dataIndex: 'description', width: 220}
					]
				}),

				listeners: {
					viewready: function(self){
						self.store.load({
							params: {
								action: 'storage_list'
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
					//forceFit: true,
					//emptyText: '작업을 추가해 주세요.'
					emptyText: _text('MSG00020')
				},

				buttonAlign: 'center',
				fbar: [{
					text: _text('MN00033'),
					scale: 'medium',
					handler: function(btn, e) {
						this.buildAddTable(e);
					},
					scope: this
				},{
					text: _text('MN00043'),
					scale: 'medium',
					handler: function(btn, e) {
						var hasSelection = Ext.getCmp('storageList').getSelectionModel().hasSelection();
						if(hasSelection) {
							
							this.buildEditTableWin(e);
							
						}else{
							Ext.Msg.alert(_text('MN00023'), _text('MSG00084'));
						}
					},
					scope: this
				},{
					text: _text('MN00034'),
					scale: 'medium',
					handler: function(btn, e) {
						var hasSelection = Ext.getCmp('storageList').getSelectionModel().hasSelection();
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
				id: 'add_storage_win',
				layout: 'fit',
				//!!title: '스토리지 추가',
				title: _text('MN00381')+' '+ _text('MN00033'),
				width: 400,
				height: 300,
				padding: 10,
				modal: true,
				items: {
					id: 'add_storage_form',
					xtype: 'form',
					baseCls: 'x-plain',
					defaultType: 'textfield',
					defaults: {
						anchor: '100%'
					},
					items: [{
						name: 'storage_name',
						//!!fieldLabel: '스토리지 명',
						fieldLabel: _text('MN00377'),
						msgTarget: 'under',
						allowBlank: false
					},{
						name: 'storage_path',
						fieldLabel: _text('MN00376')
						//!!fieldLabel: '경 로'
					},{
						xtype: 'combo',
						name: 's_type',						
						store: [							
							['storage_type1', 'SAN'],
							['storage_type2', 'NAS'],
							['storage_type3', 'FTP'],
							['storage_type4', 'SSH']
						],
						allowBlank: false,
						value : 'storage_type1',
						//!!fieldLabel: '타 입',
						fieldLabel: _text('MN00291'),
						typeAhead: true,
						triggerAction: 'all',
						forceSelection: true,
						editable: false
					},{
						name: 's_id',
						//!!fieldLabel: '접근 아이디',
						fieldLabel: _text('MN00378'),
						allowBlank: true
					},{
						name: 's_pw',
						//!!fieldLabel: '패스워드',
						fieldLabel: _text('MN00379'),
						allowBlank: true
					},{
						xtype: 'textarea',
						name: 'description',
						//!!fieldLabel: '설 명',
						fieldLabel: _text('MN00049'),
						allowBlank: true
					}]
				},
				buttons: [{
					text: _text('MN00033'),
					handler: function(btn, e) {
						
						Ext.getCmp('add_storage_form').getForm().submit({
							url: '/pages/menu/config/workflow/edit_workflow.php',
							params: {
								action: 'add_storge'
							},
							success: function(form, action) {
								try {
									var result = Ext.decode(action.response.responseText, true);
									if(result.success) {
										Ext.getCmp('add_storage_win').close();
										Ext.getCmp('storageList').store.reload();
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
					text: lang.userAddTableCancelButton,
					handler: function() {
						this.ownerCt.ownerCt.close();
					}
				}]
			}).show(e.getTarget());
		},

		buildEditTableWin: function(e) { //수정버튼 기능 실행
			
			var _submit = function(){
				Ext.getCmp('edit_storage_form').getForm().submit({
					url: '/pages/menu/config/workflow/edit_workflow.php',
					params: {
						action: 'edit_storage'
					},
					success: function(form, action){
						try {
							var result = Ext.decode(action.response.responseText, true);
							if (result.success) {
								// 정상
								Ext.getCmp('edit_storage_win').close();
								Ext.getCmp('storageList').store.reload();
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
				id: 'edit_storage_win',
				layout: 'fit',
				//!!title: '스토리지 수정',
				title: _text('MN00381')+' ' + _text('MN00043'),
				width: 400,
				height: 300,
				padding: 10,
				modal: true,
				items: {
					id: 'edit_storage_form',
					xtype: 'form',
					baseCls: 'x-plain',
					defaultType: 'textfield',
					defaults: {
						anchor: '100%'
					},
					items: [{
						name: 'storage_id',
						hidden: true
					},{
						name: 'name',
						//!!fieldLabel: '스토리지 명',
						fieldLabel: _text('MN00377'),
						msgTarget: 'under',
						allowBlank: false
					},{
						name: 'path',
						//!!fieldLabel: '경 로'
						fieldLabel: _text('MN00376')
					},{
						xtype: 'combo',
						name: 'type',						
						store: [							
							['storage_type1', 'SAN'],
							['storage_type2', 'NAS'],
							['storage_type3', 'FTP'],
							['storage_type4', 'SSH']
						],
						allowBlank: false,
						value : 'storage_type1',
						//!!fieldLabel: '타 입',
						fieldLabel: _text('MN00291'),
						typeAhead: true,
						triggerAction: 'all',
						forceSelection: true,
						editable: false
					},{
						name: 'login_id',
						//!!fieldLabel: '접근 아이디',
						fieldLabel: _text('MN00378'),
						allowBlank: true
					},{
						name: 'login_pw',
						//!!fieldLabel: '패스워드',
						fieldLabel: _text('MN00379'),
						allowBlank: true
					},{
						xtype: 'textarea',
						name: 'description',
						//!!fieldLabel: '설 명',
						fieldLabel: _text('MN00049'),
						allowBlank: true
					}],
					keys: [{
						key: 13,
						handler: function(){
							_submit();
						}
					}],
					listeners: {
						afterrender: function(self) {
							var sm = Ext.getCmp('storageList').getSelectionModel();
							var rec = sm.getSelected();
							self.getForm().loadRecord(rec);
						}
					}
				},
				buttons: [{
					text: _text('MN00043'),
					handler: function(btn, e) {
						_submit();
					}
				},{
					text: _text('MN00004'),
					handler: function(btn, e) {
						this.ownerCt.ownerCt.close();
					}
				}]
			}).show(e.getTarget());
		},

		buildDeleteTable: function(e) {
			var rec = Ext.getCmp('storageList').getSelectionModel().getSelected();

			// 삭제 확인 창
			Ext.Msg.show({
				animEl: e.getTarget(),
				title: _text('MN00003'),
				icon: Ext.Msg.INFO,
				msg: _text('MN00381') +' '+ rec.get('name') +' '+ _text('MSG00140'),
				buttons: Ext.Msg.OKCANCEL,
				fn: function(btnID, text, opt) {
					if(btnID == 'ok') {
						Ext.Ajax.request({
							url: '/pages/menu/config/workflow/edit_workflow.php',
							params: {
								action: 'delete_storage',
								storage_id : rec.get('storage_id')
							},
							callback: function(opts, success, response) {
								try {
									var r = Ext.decode(response.responseText, true);
									if(r.success) {
										Ext.Msg.alert(_text('MN00023'), r.msg);
										Ext.getCmp('storageList').store.reload();
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