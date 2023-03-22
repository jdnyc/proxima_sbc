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

			this.items = [
				this.tableGrid
			];

			Ariel.config.custom.MetadataPanel.superclass.initComponent.call(this);
		},

		buildTable: function(){
			return new Ext.grid.GridPanel({
				xtype:	'grid',
				id: 'storageList',
				//cls: 'proxima_customize',
				title: '<span class="user_span"><span class="icon_title"><i class=""></i></span><span class="main_title_header">'+_text('MN00381')+'</span></span>',
				cls: 'grid_title_customize proxima_customize',
				stripeRows: true,
				border: false,
				//!!title: '스토리지 설정',
				//title: _text('MN00329'),
				region: 'center',
				loadMask: true,
				split: true,
				collapsible: false,
				selModel: new Ext.grid.RowSelectionModel({
					singleSelect: true
				}),
				store: new Ext.data.JsonStore({
					url: '/pages/menu/config/workflow/workflow_list.php',
					root: 'data',
					idPropery: 'storage_id',
					fields: [
						'storage_id',
						'path',
						'name',
						'type',
						'login_id',
						'login_pw',
						'description',
						'write_limit',
						'read_limit',
						'path_for_win',
						'path_for_mac',
						'path_for_unix',
                        'virtual_path',
                        'limit_session'
					],
					sortInfo: {
						field: 'name',
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
						{header: _text('MN00377'), dataIndex: 'name', width: 200},
						{header: _text('MN00210'), dataIndex: 'storage_id', width: 40, align: 'center', hidden: true},
						{header: _text('MN00376'), dataIndex: 'path', width: 250},
						{header: _text('MN02523'), dataIndex: 'path_for_win', width: 200},
						{header: _text('MN02520'), dataIndex: 'path_for_mac', width: 250},
						{header: _text('MN02521'), dataIndex: 'path_for_unix', width: 250},
						{header: _text('MN02522'), dataIndex: 'virtual_path', width: 200},
						{header: _text('MN00291'), dataIndex: 'type', width: 40},
						{header: _text('MN00378'), dataIndex: 'login_id', width: 110, hidden: true},
                        {header: _text('MN00379'), dataIndex: 'login_pw', width: 110, hidden: true},
                        {header: _text('MN02000'), dataIndex: 'limit_session', width: 80, renderer: function(value){
							if( value == '0' ){
								value = _text('MN02329');
							}
							return value;
						}},
						// {header: _text('MN02328'), dataIndex: 'write_limit', width: 80, renderer: function(value){
						// 	if( value == '-1' ){
						// 		value = _text('MN02329');
						// 	}
						// 	return value;
						// }},
						// {header: _text('MN02327'), dataIndex: 'read_limit', width: 80, renderer: function(value){
						// 	if( value == '-1' ){
						// 		value = _text('MN02329');
						// 	}
						// 	return value;
						// }},
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
					emptyText:_text('MSG00148'),//'결과 값이 없습니다.',
					forceFit:true
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
						var hasSelection = Ext.getCmp('storageList').getSelectionModel().hasSelection();
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
				cls: 'change_background_panel',
				layout: 'fit',
				//!!title: '스토리지 추가',
				title: _text('MN00381')+' '+ _text('MN00033'),
				width: 400,
				height: 480,
				padding: 10,
				modal: true,
				buttonAlign: 'center',
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
						name: 'storage_path_win',
						fieldLabel: _text('MN02523')
					},{
						name: 'storage_path_mac',
						fieldLabel: _text('MN02520')
					},{
						name: 'storage_path_unix',
						fieldLabel: _text('MN02521')
					},{
						name: 'storage_path_virtual',
						fieldLabel: _text('MN02522')
					},{
						xtype: 'combo',
						name: 's_type',
						store: [
							['storage_type1', 'SAN'],
							['storage_type2', 'NAS'],
							['storage_type3', 'FTP'],
							['storage_type4', 'SFTP'],
							['storage_type5', 'HTTP']

						],
						allowBlank: false,
						value : 'storage_type1',
						//!!fieldLabel: '타 입',
						fieldLabel: _text('MN00291'),
						typeAhead: true,
						triggerAction: 'all',
						forceSelection: true,
						editable: false,
						listeners: {
							select: function(self, record, index){
								var write_limit = self.ownerCt.getForm().findField('write_limit');
								var read_limit = self.ownerCt.getForm().findField('read_limit');
								var limit_session = self.ownerCt.getForm().findField('limit_session');
                                    
                                if( record.get('field2') == 'FTP' ){
                                    //FTP 만 읽기/쓰기 제한 가능
                                    if( !Ext.isEmpty(limit_session) ){
                                        limit_session.setDisabled( false );
                                    }
                                }else{
                                    if( !Ext.isEmpty(limit_session) ){
                                        limit_session.setDisabled( true );
                                    }
                                }
							}
						}
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
						xtype:'numberfield',
						name: 'write_limit',
						hidden: true,
						fieldLabel: _text('MN02328'),//'쓰기 제한',
						allowBlank: true
					},{
						xtype:'numberfield',
						name: 'read_limit',
						hidden: true,
						fieldLabel: _text('MN02327'),//'읽기 제한',
						allowBlank: true
					},{
						xtype:'numberfield',
                        name: 'limit_session',
                        disabled: true,
						fieldLabel: _text('MN02000'),//'접속 제한',
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
					text : '<span style="position:relative;top:1px;"><i class="fa fa-plus" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00033'),
					scale: 'medium',
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
				cls: 'change_background_panel',
				layout: 'fit',
				//!!title: '스토리지 수정',
				title: _text('MN00381')+' ' + _text('MN00043'),
				width: 400,
				height: 480,
				padding: 10,
				modal: true,
				buttonAlign: 'center',
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
						name: 'path_for_win',
						fieldLabel: _text('MN02523')
					},{
						name: 'path_for_mac',
						fieldLabel: _text('MN02520')
					},{
						name: 'path_for_unix',
						fieldLabel: _text('MN02521')
					},{
						name: 'virtual_path',
						fieldLabel: _text('MN02522')
					},{
						xtype: 'combo',
						name: 'type',
						store: [
							['storage_type1', 'SAN'],
							['storage_type2', 'NAS'],
							['storage_type3', 'FTP'],
							['storage_type4', 'SFTP'],
							['storage_type5', 'HTTP']

						],
						allowBlank: false,
						value : 'storage_type1',
						//!!fieldLabel: '타 입',
						fieldLabel: _text('MN00291'),
						typeAhead: true,
						triggerAction: 'all',
						forceSelection: true,
						editable: false,
						listeners: {
							select: function(self, record, index){
								var write_limit = self.ownerCt.getForm().findField('write_limit');
								var read_limit = self.ownerCt.getForm().findField('read_limit');
								var limit_session = self.ownerCt.getForm().findField('limit_session');
                                    
                                if( record.get('field2') == 'FTP' ){
                                    //FTP 만 읽기/쓰기 제한 가능
                                    if( !Ext.isEmpty(limit_session) ){
                                        limit_session.setDisabled( false );
                                    }
                                }else{
                                    if( !Ext.isEmpty(limit_session) ){
                                        limit_session.setDisabled( true );
                                    }
                                }
							}
						}
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
						xtype:'numberfield',
						name: 'write_limit',
						hidden: true,
						fieldLabel: _text('MN02328'),//'쓰기 제한',
						allowBlank: true
					},{
						xtype:'numberfield',
						name: 'read_limit',
						hidden: true,
						fieldLabel: _text('MN02327'),//'읽기 제한',
						allowBlank: true
					},{
						xtype:'numberfield',
                        name: 'limit_session',
                        disabled: true,
						fieldLabel: _text('MN02000'),//'접속 제한',
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
							
							var write_limit = self.getForm().findField('write_limit');
                            var read_limit = self.getForm().findField('read_limit');
                            var limit_session = self.getForm().findField('limit_session');
								
							if( rec.get('type') == 'FTP' ){
								//FTP 만 읽기/쓰기 제한 가능
								if( !Ext.isEmpty(limit_session) ){
									limit_session.setDisabled( false );
								}
							}else{
								if( !Ext.isEmpty(limit_session) ){
									limit_session.setDisabled( true );
								}
							}
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
										//Ext.Msg.alert(_text('MN00023'), r.msg);
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