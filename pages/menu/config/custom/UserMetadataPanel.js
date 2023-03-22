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
			this.fieldGrid = this.buildField();

			this.items = [
				this.tableGrid,
				this.fieldGrid
			];

			Ariel.config.custom.MetadataPanel.superclass.initComponent.call(this);
		},

		buildTable: function(){
			return new Ext.grid.GridPanel({
				id: 'bc_ud_content',
				title: _text('MN00198'),
				region: 'center',
				loadMask: true,
				enableDragDrop: true,
				ddGroup: 'tableGridDD',
				store: new Ext.data.JsonStore({
					url: '/pages/menu/config/custom/user_metadata/php/get.php',
					root: 'data',
					idPropery: 'ud_content_id',
					fields: [
						'bs_content_id',
						'bs_content_title',
						'ud_content_id',
						'show_order',
						'ud_content_title',
						'allowed_extension',
						'description',
						'use_common_category',
						'expired_date'
					],
					listeners: {
						exception: function(self, type, action, options, response, arg){
							if(type == 'response') {
								if(response.status == '200') {
									Ext.Msg.alert(_text('MN00022'), response.responseText);
								}else{
									Ext.Msg.alert(_text('MN00022'), response.status);
								}
							}else{
								Ext.Msg.alert(_text('MN00022'), type);
							}
						}
					}
				}),
				colModel: new Ext.grid.ColumnModel({
					defaults: {
						align: 'center'
					},
					columns: [
						{header: _text('MN00273'), dataIndex: 'ud_content_title'}
						,{header: '시스템콘텐츠 명', dataIndex: 'bs_content_title'}
						,{header: _text('MN00309'), dataIndex: 'allowed_extension'}
						,{header: _text('MN00049'), dataIndex: 'description'}
						//,{header: _text('MN00403'), dataIndex: 'expired_date'}
					]
				}),
				listeners: {
					viewready: function(self){
						self.store.load({
							params: {
								action: 'bc_ud_content'
							}
						});
						var upGridDroptgtCfg = Ext.apply({}, dropZoneOverrides, {
							table: 'bc_ud_content',
							id_field: 'ud_content_id',
							ddGroup: 'tableGridDD',
							grid : Ext.getCmp('bc_ud_content')
						});
						new Ext.dd.DropZone(Ext.getCmp('bc_ud_content').getEl(), upGridDroptgtCfg);
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
					emptyText: 'no data'
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
						var hasSelection = Ext.getCmp('bc_ud_content').getSelectionModel().hasSelection();
						if(hasSelection) {
							this.buildEditTableWin(e);
						}else{
							Ext.Msg.alert(_text('MN00022'), _text('MSG00169'));
						}
					},
					scope: this
				},{
					text: _text('MN00034'),
					scale: 'medium',
					handler: function(btn, e) {
						var hasSelection = Ext.getCmp('bc_ud_content').getSelectionModel().hasSelection();
						if(hasSelection) {
							this.buildDeleteTable(e);
						}else{
							Ext.Msg.alert(_text('MN00022'), _text('MSG00170'));
						}
					},
					scope: this
				}]
			});
		},
		
		buildAddTable: function(e){
			var win = new Ext.Window({
				id: 'add_table_win',
				layout: 'fit',
				title: _text('MN00148'),
				width: 400,
				height: 300,
				padding: 10,
				modal: true,
				items: {
					id: 'add_table_form',
					xtype: 'form',
					baseCls: 'x-plain',
					defaultType: 'textfield',
					defaults: {
						anchor: '100%'
					},
					items: [{
						name: 'ud_content_title',
						fieldLabel: _text('MN00273'),
						msgTarget: 'under',
						allowBlank: false
					},{
						xtype: 'combo',
						store: new Ext.data.JsonStore({
							url: '/pages/menu/config/custom/user_metadata/php/get.php',
							baseParams: {
								action: 'content_type_list'
							},
							root: 'data',
							idProperty: 'bs_content_id',
							fields: [
								{name: 'bs_content_title', type: 'string'},
								{name: 'bs_content_id', type: 'int'}
							]
						}),
						allowBlank: false,
						hiddenName: 'bs_content_id',
						valueField: 'bs_content_id',
						displayField: 'bs_content_title',
						fieldLabel: '시스템콘텐츠 명',
						typeAhead: true,
						triggerAction: 'all',
						forceSelection: true,
						editable: false,
						emptyText: _text('MSG00111')
					},{
						name: 'allowed_extension',
						fieldLabel: _text('MN00309')
					},/*{
						xtype: 'radiogroup',
						fieldLabel: _text('MN00395'),
						items: [
							{boxLabel: _text('MN00393'), name: 'use_common_category', inputValue: 'Y'},
							{boxLabel: _text('MN00394'), name: 'use_common_category', inputValue: 'N'}
						]
					},*/{
						xtype: 'combo',
						fieldLabel: _text('MN00403'),
						typeAhead: true,
						triggerAction: 'all',
						editable: false,
						name: 'expired_date',
						mode: 'local',
						emptyText: _text('MSG00215'),
						allowBlank: false,
						valueField: 'interval',
						hiddenValue: 'interval',
						hiddenName: 'expired_date',
						displayField: 'display',
						store: new Ext.data.ArrayStore({
							fields: [
								'interval', 'display'
							],
							data: [
								[-1, '영구'],
								['P1M', '1개월'],
								['P3M', '3개월'],
								['P6M', '6개월'],
								['P1Y',   '1년'],
								['P3Y',   '3년'],
								['P5Y',   '5년'],
								['P10Y', '10년'],
								['P10Y', '20년']
							]
						})
					},{
						xtype: 'textarea',
						name: 'description',
						fieldLabel: _text('MN00049')
					}]
				},
				buttons: [{
					text: _text('MN00033'),
					handler: function(btn, e) {
						
						btn.disable();
						Ext.getCmp('add_table_form').getForm().submit({
							url: '/pages/menu/config/custom/user_metadata/php/add.php',
							params: {
								action: 'add_table'
							},
							success: function(form, action) {
								try {
									var result = Ext.decode(action.response.responseText, true);
									if(result.success) {
										Ext.getCmp('add_table_win').close();
										Ext.getCmp('bc_ud_content').store.reload();
										Ext.getCmp('table_combo').store.reload();
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
									buttons: Ext.Msg.OK,
									fn: function(){
										btn.enable();
									}
								});
							}
						});
					}
				},{
					text: _text('MN00004'),
					handler: function() {
						this.ownerCt.ownerCt.close();
					}
				}]
			}).show(e.getTarget());
		},

		buildEditTableWin: function(e) {
			
			var _submit = function(){
				var ud_content_id = Ext.getCmp('bc_ud_content').getSelectionModel().getSelected().get('ud_content_id');
				Ext.getCmp('edit_table_form').getForm().submit({
					url: '/pages/menu/config/custom/user_metadata/php/edit.php',
					params: {
						action: 'edit_table'
					},
					success: function(form, action){
						try {
							var result = Ext.decode(action.response.responseText, true);
							if (result.success) {
								// 정상
								Ext.getCmp('edit_table_win').close();
								Ext.getCmp('bc_ud_content').store.reload();
								Ext.getCmp('table_combo').store.reload();
							}
							else 
							{
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
				id: 'edit_table_win',
				layout: 'fit',
				title: _text('MN00147'),
				width: 400,
				height: 300,
				padding: 10,
				modal: true,
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
						name: 'ud_content_id'
					},{
						xtype: 'hidden',
						name: 'show_order'
					},{
						name: 'ud_content_title',
						fieldLabel: _text('MN00273'),
						msgTarget: 'under',
						allowBlank: false,
						listeners: {
							render: function(self){
								self.focus(true, 500);							
							}
						}
					},{
						xtype: 'combo',
						id: 'content_type_list',				
						store: new Ext.data.JsonStore({
							url: '/pages/menu/config/custom/user_metadata/php/get.php',
							baseParams: {
								action: 'content_type_list'
							},
							root: 'data',
							idProperty: 'bs_content_id',
							fields: [
								{name: 'bs_content_title', type: 'string'},
								{name: 'bs_content_id', type: 'int'}
							],
							listeners: {
								load: function(self){
									
									var rec = Ext.getCmp('bc_ud_content').getSelectionModel().getSelected();
									Ext.getCmp('content_type_list').setValue(rec.get('bs_content_id'));
								}
							}
						}),
						autoShow: true,
						lazyInit: false,
						//mode: 'local',
						hiddenName: 'bs_content_id',
						valueField: 'bs_content_id',
						displayField: 'bs_content_title',
						fieldLabel: '시스템콘텐츠 명',
						typeAhead: true,
						triggerAction: 'all',
						forceSelection: true,
						editable: false,
						emptyText: _text('MSG00111')
					},{
						name: 'allowed_extension',
						fieldLabel: _text('MN00309')
					},/*						
						xtype: 'radiogroup',
						fieldLabel: _text('MN00395'),
						name: 'use_common_category',
						items: [
							{boxLabel: _text('MN00393'), name: 'use_common_category', inputValue: 'Y'},
							{boxLabel: _text('MN00394'), name: 'use_common_category', inputValue: 'N'}
						]
					},*/{
						xtype: 'combo',
						fieldLabel: _text('MN00403'),
						typeAhead: true,
						triggerAction: 'all',
						editable: false,
						name: 'expired_date',
						mode: 'local',
						emptyText: _text('MSG00215'),
						allowBlank: false,
						valueField: 'interval',
						hiddenValue: 'interval',
						hiddenName: 'expired_date',
						displayField: 'display',
						store: new Ext.data.ArrayStore({
							fields: [
								'interval', 'display'
							],
							data: [
								[-1, '영구'],
								['P1M', '1개월'],
								['P3M', '3개월'],
								['P6M', '6개월'],
								['P1Y',   '1년'],
								['P3Y',   '3년'],
								['P5Y',   '5년'],
								['P10Y', '10년'],
								['P10Y', '20년']
							]
						})
					},{
						xtype: 'textarea',
						name: 'description',
						fieldLabel: _text('MN00049')
					}],
					keys: [{
						key: 13,
						handler: function(){
							_submit();
						}
					}],
					listeners: {
						afterrender: function(self) {
							var sm = Ext.getCmp('bc_ud_content').getSelectionModel();
							var rec = sm.getSelected();
							self.getForm().loadRecord(rec);

							//console.log(rec.get('use_common_category'));
							
//							if (rec.get('use_common_category') == 'N')
//							{
//								self.getForm().setValues({
//									use_common_category: 'N'
//								});
//							}
//							else
//							{
//								self.getForm().setValues({
//									use_common_category: 'Y'
//								});
//							}
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
			var rec = Ext.getCmp('bc_ud_content').getSelectionModel().getSelected();

			// 삭제 확인 창
			Ext.Msg.show({
				animEl: e.getTarget(),
				//>>title: '삭제 확인',
				title: _text('MN00024'),
				
				icon: Ext.Msg.INFO,
				//>>msg: '선택하신 "' + rec.get('ud_content_title') + '" 콘텐츠들 을 지우시겠습니까?',
				msg: "'" + rec.get('ud_content_title') + '" ' +  _text('MSG00172'),

				buttons: Ext.Msg.OKCANCEL,
				fn: function(btnID, text, opt) {
					if(btnID == 'ok') {
						Ext.Ajax.request({
							url: '/pages/menu/config/custom/user_metadata/php/del.php',
							params: {
								action: 'delete_table',
								ud_content_id: rec.get('ud_content_id')
							},
							callback: function(opts, success, response) {
								try {
									var r = Ext.decode(response.responseText, true);
									if(r.success) {
										Ext.getCmp('bc_ud_content').store.reload();
										var c = Ext.getCmp('table_combo');
										var id = c.getValue();
										if (id == rec.get('bs_content_id')) {
											c.getStore().reload();
											c.reset();
											Ext.getCmp('bc_usr_meta_field').getStore().removeAll();
										}
								 }else{
										//>>Ext.Msg.alert('오류', r.msg);
									 	Ext.Msg.alert(_text('MN00022') , r.msg);
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

		buildField: function(){
			function _renderType(v){
				switch (v) {
					case 'textfield':
						//>>return '한줄 입력';
						return _text('MN00350');
					break;
					
					case 'textarea':
						//>>return '여러줄 입력';
						return _text('MN00351');
					break;
				
					case 'combo':
						//>>return '콤보박스';
						return _text('MN00358');
					break;
				
					case 'checkbox':
						//>>return '체크박스';
						return _text('MN00353');
					break;
				
					case 'datefield':
						//>>return '날짜';
						return _text('MN00354');
					break;
				
					case 'numberfield':
						//>>return '숫자';
						return _text('MN00355');
					break;

					// 2010-11-08 추가 (컨테이너 추가 by CONOZ)
					case 'container':
						//>>return '컨테이너';
						return _text('MN00272');
					break;
				
					case 'listview':
						//>>return '테이블(표)';
						return _text('MN00352');
					break;
				
				}
			}

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
								Ext.getCmp('field_win').close();
								Ext.getCmp('bc_usr_meta_field').store.reload();
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

			function _buildForm(btn){
				return {
					id: 'field_form',
					xtype: 'form',
					baseCls: 'x-plain',
					defaultType: 'textfield',
					defaults: {
						anchor: '100%'
					},
					items: [{
						xtype: 'hidden',
						name: 'ud_content_id'
					},{
						xtype: 'hidden',
						name: 'usr_meta_field_id'
					},{
						// 2010-11-08 추가 (컨테이너 추가 by CONOZ)
						name: 'container_id',
						id: 'container_combo',
						xtype: 'combo',
						store: new Ext.data.JsonStore({
							autoLoad: true,
							url: '/pages/menu/config/custom/user_metadata/php/get.php',
							baseParams: {
								action: 'container_list',
								ud_content_id: Ext.getCmp('table_combo').getValue()
							},
							root: 'data',
							idProperty: 'container_id',
							fields: [
								'container_id',
								'usr_meta_field_title'
							]
						}),
						hiddenName: 'container_id',
						valueField: 'container_id',							
						displayField: 'usr_meta_field_title',
						triggerAction: 'all',
						editable: false,
						fieldLabel: _text('MN00272'),
						allowBlank: true
					},{
						name: 'usr_meta_field_title',
						fieldLabel: _text('MN00308'),
						msgTarget: 'under',
						allowBlank: false,
						listeners: {
							render: function(self){
								self.focus(true, 500);
							}
						}
					},{
						xtype: 'combo',
						store: new Ext.data.ArrayStore({
							id: 0,
							fields: [ 'usr_meta_field_type', 'displayText' ],
							data: [
							    /*
								['textfield', '한줄 입력'],
								['textarea', '여러줄 입력'],
								['combo', '리스트'],
								['checkbox', '체크박스'],
								['datefield', '날짜'],
								['numberfield', '숫자'],
								['listview', '멀티리스트'],
								// 2010-11-08 추가 (컨테이너 추가 by CONOZ)
								['container', '컨테이너']
								*/
							    ['textfield',	_text('MN00350')],
								['textarea',	_text('MN00351')],
								['combo',		_text('MN00358')],
								['checkbox',	_text('MN00353')],
								['datefield',	_text('MN00354')],
								['timefield',	_text('MN00405')],
								['datetimefield',	_text('MN00406')],
								['numberfield',	_text('MN00355')],
								['listview',	_text('MN00352')],
								['container',	_text('MN00272')]
							]
						}),
						mode: 'local',
						hiddenName: 'usr_meta_field_type',
						displayField: 'displayText',
						valueField: 'usr_meta_field_type',
						value: 'textfield',
						triggerAction: 'all',
						editable: false,
						name: 'usr_meta_field_type',
						fieldLabel: _text('MN00228'),
						allowBlank: false
					},{
						xtype: 'checkbox',
						name: 'is_required',
						fieldLabel: _text('MN00305'),
						inputValue: 1,
						checked: true
					},{
						xtype: 'checkbox',
						name: 'is_editable',
						fieldLabel: _text('MN00054'),
						inputValue: 1,
						checked: true
					},{
						xtype: 'checkbox',
						name: 'is_show',
						fieldLabel: _text('MN00040'),
						inputValue: 1,
						checked: true
					},{
						xtype: 'checkbox',
						name: 'is_search_reg',
						fieldLabel: _text('MN00114'),
						inputValue: 1,
						checked: true
					},{
						xtype: 'textarea',
						name: 'default_value',
						fieldLabel: _text('MN00155')
					}],
					keys: [{
						key: 13,
						handler: function(){
							_submit(btn.url);
						}
					}]
				}
			}

			function _buildWindow(btn){
				return new Ext.Window({
					id: 'field_win',
					layout: 'fit',
					title: _text('MN00164') + ' ' + btn.getText(),
					width: 400,
					height: 300,
					padding: 10,
					modal: true,
					items: _buildForm(btn),
					buttons: [{
						url: btn.url,
						text: btn.getText(),
						handler: function(btn, e){
							_submit(btn.url)
						}
					},{
						//>>text: '취소',
						text: _text('MN00004'),
						handler: function(){
							this.ownerCt.ownerCt.close();
						}
					}]
				});
			}

			function _load_form(btn, e){
				var win = _buildWindow(btn);
				//>>if (btn.getText() == '추가') {
				if (btn.getText() == _text('MN00033')) {
					var ud_content_id = Ext.getCmp('table_combo').getValue();
					if (!ud_content_id) {
						Ext.Msg.alert(_text('MN00022'), _text('MSG00111'));
						return;
					}
					win.show(btn.getId(), function(){
						this.get(0).getForm().setValues({
							ud_content_id: ud_content_id
						})
					});
				} else {
					var sm = Ext.getCmp('bc_usr_meta_field').getSelectionModel();
					if (!sm.hasSelection()) {
						Ext.Msg.alert(_text('MN00022'), _text('MSG00084'));
						return;
					}
					win.show(btn.getId(), function(){
						this.get(0).getForm().loadRecord(sm.getSelected());
					});
				}
					
			}

			function _delete_record(btn, e){
				var sm = Ext.getCmp('bc_usr_meta_field').getSelectionModel();
				if(!sm.hasSelection()) {
					Ext.Msg.alert(_text('MN00022'), _text('MSG00082'));
					return;
				}
				var recs = sm.getSelections();

				var msg = '';
				if(recs.length > 1){
					//>>msg = recs.length + ' 개의  선택된 메타데이터들 을 삭제 하시겠습니까?';
					msg = recs.length + _text('MSG00171');
				}else{
					//>>msg = '선택하신 "' + recs[0].get('usr_meta_field_title') + '"  메타데이터를 삭제 하시겠습니까?';
					msg = '"' + recs[0].get('usr_meta_field_title') + '" ' + _text('MSG00172');
				}

				var usr_meta_field_id_list = '';
				Ext.each(recs, function(item, index, allItems){
					usr_meta_field_id_list += item.get('usr_meta_field_id') + ',';
				})

				Ext.Msg.show({
					animEl: btn.getId(),
					title: _text('MN00024'),
					icon: Ext.Msg.INFO,
					msg: msg,
					buttons: Ext.Msg.OKCANCEL,
					fn: function(btnID, text, opt) {
						if(btnID == 'ok') {
							Ext.Ajax.request({
								url: '/pages/menu/config/custom/user_metadata/php/del.php',
								params: {
									action: 'field',
									usr_meta_field_id_list: usr_meta_field_id_list
								},
								callback: function(opts, success, response) {
									try {
										var r = Ext.decode(response.responseText, true);
										if (r.success) {
											Ext.getCmp('bc_usr_meta_field').getStore().reload();
										} else {
											Ext.Msg.alert(_text('MN00022'), r.msg);
										}
									} catch(e) {
										alert(_text('MN00022'), e.message + '(responseText: ' + response.responseText + ')');
									}
								}
							})
						}
					}
				})
			}

			return new Ext.grid.GridPanel({
				title: _text('MN00164'),
				id: 'bc_usr_meta_field',
				region: 'south',
				height: 400,
				loadMask: true,
				enableDragDrop: true,
				ddGroup: 'fieldGridDD',
				store: new Ext.data.JsonStore({
					url: '/pages/menu/config/custom/user_metadata/php/get.php',
					root: 'data',
					idProperty: 'usr_meta_field_id',
					// 2010-11-08 container_id, container_name 추가 (컨테이너 추가 by CONOZ)
					fields: [
						'ud_content_id',
						'usr_meta_field_id',
						'show_order',
						'usr_meta_field_title',
						'container_id',
						'container_name',
						'usr_meta_field_type',
						'is_required',
						'is_editable',
						'default_value',
						'is_show',
						'is_search_reg'
					]
				}),
				colModel: new Ext.grid.ColumnModel({
					defaults: {
						align: 'center'
					},
					// 2010-11-08 container_name 추가 (컨테이너 추가 by CONOZ)
					columns: [
						{header: _text('MN00308'),		dataIndex: 'usr_meta_field_title'},
						{header: _text('MN00272'),	dataIndex: 'container_name'},
						{header: _text('MN00228'),		dataIndex: 'usr_meta_field_type',			renderer: _renderType},
						{header: _text('MN00305'),  dataIndex: 'is_required',	renderer: _renderCheck},
						{header: _text('MN00054'),	dataIndex: 'is_editable',		renderer: _renderCheck},
						{header: _text('MN00040'),		dataIndex: 'is_show',		renderer: _renderCheck},
						{header: _text('MN00114'), dataIndex: 'is_search_reg',	renderer: _renderCheck},
						{header: _text('MN00155'), dataIndex: 'default_value'}
					]
				}),
				viewConfig: {
					emptyText: _text('MSG00148'),
					forceFit: true,
					getRowClass: function(record, idx, rp, ds){
						if ( record.get('usr_meta_field_type') == 'container' )
						{
							return 'user-custom-container';
							//console.log( rp );
						}
					}					
				},
				listeners: {
					viewready: function(self) {
						var downGridDroptgtCfg = Ext.apply({}, dropZoneOverrides, {
							table: 'bc_usr_meta_field',
							id_field: 'usr_meta_field_id',
							ddGroup: 'fieldGridDD',
							grid : Ext.getCmp('bc_usr_meta_field')
						});
						new Ext.dd.DropZone(Ext.getCmp('bc_usr_meta_field').getEl(), downGridDroptgtCfg);
					},
					rowdblclick: function(self, rowIndex, e) {
						_load_form({text: _text('MN00043'), url: 'edit.php', getText: function(){return _text('MN00043')}, getId: function(){return e.getTarget()}}, null);
					}
				},
				tbar: [_text('MN00275'), '-', {
					id: 'table_combo',
					xtype: 'combo',
					store: new Ext.data.JsonStore({
						url: '/pages/menu/config/custom/user_metadata/php/get.php',
						baseParams: {
							action: 'table_list'
						},
						root: 'data',
						idProperty: 'ud_content_id',
						fields: [
							'ud_content_title',
							'ud_content_id'
						]
					}),
					listeners: {
						select: function(self, rec, idx){
							Ext.getCmp('bc_usr_meta_field').store.load({
								params: {
									action: 'table_field',
									ud_content_id: rec.get('ud_content_id')
								}
							});
						}
					},
					displayField: 'ud_content_title',
					valueField: 'ud_content_id',
					triggerAction: 'all',
					forceSelection: true,
					minChars: 1,
					editable: false,
					emptyText: _text('MSG00111')
				},{
					//>>text: '새로고침',
					text: _text('MN00139'),
					handler: function(btn, e){
						Ext.getCmp('bc_usr_meta_field').store.reload();
					}
				}],

				buttonAlign: 'center',
				fbar: [{
					url: 'add.php',
					text: _text('MN00033'),
					scale: 'medium',
					handler: _load_form
				},{
					url: 'edit.php',
					text: _text('MN00043'),
					scale: 'medium',
					handler: _load_form
				},{
					text: _text('MN00034'),
					scale: 'medium',
					handler: _delete_record
				}]
			});
		}
	});

	return new Ariel.config.custom.MetadataPanel();
})()