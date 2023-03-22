(function(){
	Ext.ns('Ariel.config.custom');

	Ariel.config.custom.ContentMetadataPanel = Ext.extend(Ext.Panel, {
		layout: 'border',
		cls: 'proxima_customize',
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

			Ariel.config.custom.ContentMetadataPanel.superclass.initComponent.call(this);
		},

		buildTable: function(){
			return new Ext.grid.GridPanel({
				id: 'content_table',
				//>>title: '콘텐츠 종류',
				//title: _text('MN00208'),//'시스템콘텐츠',
				title: '<span class="user_span"><span class="icon_title"><i class=""></i></span><span class="main_title_header">'+_text('MN00208')+'</span></span>',
				cls: 'grid_title_customize',
				stripeRows: true,
				border: false,
				region: 'center',
				loadMask: true,
				store: new Ext.data.JsonStore({
					url: '/pages/menu/config/custom/content_metadata/php/get.php',
					root: 'data',
					idPropery: 'bs_content_id',
					fields: [
						'bs_content_id',
						'bs_content_title',
						'bs_content_code',
						'show_order',
						'allowed_extension',
						'description'										
					],
					listeners: {
						exception: function(self, type, action, options, response, arg){
							if(type == 'response') {
								if(response.status == '200') {
									try {
										var r = Ext.decode(response.responseText, true);
										//>>Ext.Msg.alert('오류', r.msg);									
										Ext.Msg.alert(_text('MN00023'), r.msg);									
									} catch(e) {
										//>>Ext.Msg.alert('오류', response.responseText);_text('MN00022')
										Ext.Msg.alert(_text('MN00023'), response.responseText);										
									}
								}else{
									//>>Ext.Msg.alert('오류', response.status);
									Ext.Msg.alert(_text('MN00023'), response.status);
								}
							}else{
								//>>Ext.Msg.alert('오류', type);_text('MN00022')
								Ext.Msg.alert(_text('MN00023'), type);
							}
						}
					}
				}),
				colModel: new Ext.grid.ColumnModel({
					defaults: {
						align: 'center'
					},
					columns: [
						//>>{header: '콘텐츠 종류', dataIndex: 'bs_content_title'},_text('MN00279')
						{header: _text('MN02269'), dataIndex: 'bs_content_title'},	//시스템 콘텐츠명 Title
						{header: _text('MN02153'), dataIndex: 'bs_content_code'},	//테이블명 DB Table Name
						//!!	{header: '설명', dataIndex: 'description'}
						{header: _text('MN00049'), dataIndex: 'description'}
					]
				}),
				listeners: {
					viewready: function(self){
						self.store.load({
							params: {
								action: 'content_type_list'
							}
						});
					},
					rowdblclick: {
						fn: function(self, rowIndex, e){
							this.buildEditTableWin();
						},
						scope: this
					},
					rowclick: function (self, rowIndex, e){
						
						var sm = Ext.getCmp('content_table').getSelectionModel();
						var sel = sm.getSelected();
						var bs_content_id = sel.get('bs_content_id');

						Ext.getCmp('content_field').store.load({
							params: {
								action: 'content_field_list',
								content_type_id:bs_content_id
							}
						});
					}
				},
				viewConfig: {
					forceFit: true,
					emptyText: 'no data'
				},

				buttonAlign: 'center',
				fbar: [{
					text : '<span style="position:relative;top:1px;"><i class="fa fa-plus" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00033'),
					scale: 'medium',
					handler: function() {
						this.buildAddTable();
					},
					scope: this
				},{
					text : '<span style="position:relative;top:1px;"><i class="fa fa-edit" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00043'),
					scale: 'medium',
					handler: function() {
						var hasSelection = Ext.getCmp('content_table').getSelectionModel().hasSelection();
						if(hasSelection) {
							this.buildEditTableWin();
						}else{
							//>>Ext.Msg.alert('오류', '수정하실 행을 선택해주세요');
							Ext.Msg.alert(_text('MN00023'), _text('MSG00112'));
						}
					},
					scope: this
				},{
					text : '<span style="position:relative;top:1px;"><i class="fa fa-trash" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00034'),
					scale: 'medium',
					handler: function() {
						var hasSelection = Ext.getCmp('content_table').getSelectionModel().hasSelection();
						if(hasSelection) {
							this.buildDeleteTable();
						}else{
							//>>Ext.Msg.alert('오류', '삭제하실 행을 선택해주세요');
							Ext.Msg.alert(_text('MN00023'), _text('MSG00113'));
						}
					},
					scope: this

				}]
			});
		},

		buildAddTable: function(){
			var win = new Ext.Window({
				id: 'add_table_win',
				cls: 'change_background_panel',
				layout: 'fit',
				//title: '시스템 콘텐츠 추가',
				title : _text('MN02219'),
				width: 400,
				height: 210,
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
						name: 'name',
						fieldLabel: _text('MN02269'),
						msgTarget: 'under',
						allowBlank: false
					},{
						name: 'bs_content_code',
						fieldLabel: _text('MN02153'),
						msgTarget: 'under',
						allowBlank: false
					},{
						xtype: 'textarea',
						height: 50,
						name: 'description',
						fieldLabel: _text('MN00049')
					}]
				},
				buttons: [{
					text : '<span style="position:relative;top:1px;"><i class="fa fa-plus" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00033'),
					scale: 'medium',
					handler: function() {
						Ext.getCmp('add_table_form').getForm().submit({
							url: '/pages/menu/config/custom/content_metadata/php/add.php',
							params: {
								action: 'add_table'
							},
							success: function(form, action) {
								try {
									var result = Ext.decode(action.response.responseText, true);
									if(result.success) {
										Ext.getCmp('add_table_win').close();
										Ext.getCmp('content_table').store.reload();
									}else{
										Ext.Msg.show({
											title: _text('MN00023'),
											icon: Ext.Msg.ERROR,
											msg: result.msg,
											buttons: Ext.Msg.OK
										})
									}
								}catch(e){
									Ext.Msg.show({
										//>>title: '오류',
										title: _text('MN00023'),
										icon: Ext.Msg.ERROR,
										msg: e.message,
										buttons: Ext.Msg.OK
									})
								}
							},
							failure: function(form, action) {
								Ext.Msg.show({
									icon: Ext.Msg.ERROR,
									//>>title: '오류',
									title: _text('MN00023'),
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
			}).show();
		},

		buildEditTableWin: function() {
			
			var _submit = function(){		
				var meta_table_id = Ext.getCmp('content_table').getSelectionModel().getSelected().get('meta_table_id');
				Ext.getCmp('edit_table_form').getForm().submit({
					url: '/pages/menu/config/custom/content_metadata/php/edit.php',
					params: {
						action: 'edit_table'
					},
					success: function(form, action){
						try {
							var result = Ext.decode(action.response.responseText, true);
							if (result.success) {
								// 정상
								Ext.getCmp('edit_table_win').close();
								Ext.getCmp('content_table').store.reload();
							}
							else {
								// 쿼리 에러
								Ext.Msg.show({
									title: _text('MN00023'),
									icon: Ext.Msg.ERROR,
									msg: result.msg,
									buttons: Ext.Msg.OK
								})
							}
						} 
						catch (e) 
						{
							// 문법 에러
							Ext.Msg.show({
								//>>title: '오류',
								title: _text('MN00023'),
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
							//>>title: '오류',
							title: _text('MN00023'),
							msg: action.result.msg,
							buttons: Ext.Msg.OK
						});
					}
				});
			}

			var win = new Ext.Window({
				id: 'edit_table_win',
				cls: 'change_background_panel',
				layout: 'fit',
				//title: '시스템 콘텐츠 수정',
				title: _text('MN02220'),
				width: 400,
				height: 210,
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
						name: 'bs_content_id'
					},{
						xtype: 'hidden',
						name: 'show_order'
					},{
						name: 'bs_content_title',
						//>>fieldLabel: '콘텐츠 종류',
						fieldLabel: _text('MN02269'),//'시스템 콘텐츠 명',
						msgTarget: 'under',
						allowBlank: false,
						listeners: {
							render: function(self){
								self.focus(true, 500);
							}
						}
					},{
						name: 'bs_content_code',
						//>>fieldLabel: '콘텐츠 종류',
						fieldLabel: _text('MN02153'),//'테이블명',
						msgTarget: 'under',
						allowBlank: false,
						listeners: {
							render: function(self){								
							}
						}
					},{
						xtype: 'textarea',
						name: 'description',
						height : 50,
						//!!fieldLabel: '설명'
						fieldLabel: _text('MN00049')
					}],
					listeners: {
						afterrender: function(self) {
							var sm = Ext.getCmp('content_table').getSelectionModel();
							var rec = sm.getSelected();
							self.getForm().loadRecord(rec);
						}
					}
				},
				keys: [{
					key: 13,
					handler: function(){
						_submit();
					}
				}],
				buttons: [{
					text : '<span style="position:relative;top:1px;"><i class="fa fa-edit" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00043'),
					scale: 'medium',
					handler: function() {
						_submit();
					}
				},{
					text : '<span style="position:relative;top:1px;"><i class="fa fa-close" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00004'),
					scale: 'medium',
					handler: function() {
						this.ownerCt.ownerCt.close();
					}
				}]
			}).show();
		},
		buildDeleteTable: function() {
			var rec = Ext.getCmp('content_table').getSelectionModel().getSelected();
			if (!rec) {
				//!!Ext.Msg.alert('정보', '삭제하실 콘텐츠를 선택하여주세요.');
				Ext.Msg.alert(_text('MN00023'), _text('MSG00110'));
			}

			// 삭제 확인 창
			Ext.Msg.show({
				//!!title: '삭제 확인',
				title: _text('MN00024'),
				icon: Ext.Msg.INFO,
				//>> msg: rec.get('bs_content_title') + _text('MN00279')+' '+_text('MSG00140'),
				msg: '"' + rec.get('bs_content_title') + '" '+_text('MSG00172'),
				buttons: Ext.Msg.OKCANCEL,
				fn: function(btnID, text, opt) {
					if(btnID == 'ok') {
						Ext.Ajax.request({
							url: '/pages/menu/config/custom/content_metadata/php/del.php',
							params: {
								action: 'delete_table',
								bs_content_id: rec.get('bs_content_id')
							},
							callback: function(opts, success, response) {
								try {
									var r = Ext.decode(response.responseText, true);
									if(r.success) {
										Ext.getCmp('content_table').store.reload();
										var sm = Ext.getCmp('content_table').getSelectionModel();
										var sel = sm.getSelected();
										var bs_content_id = sel.get('bs_content_id');
										if (id == bs_content_id) {
											Ext.getCmp('content_field').getStore().removeAll();
										}
								 }else{
										//>>Ext.Msg.alert('오류', r.msg);
									 	Ext.Msg.alert(_text('MN00023'), r.msg);
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
						return _text('MN00350');
					break;
					
					case 'textarea':
						return _text('MN00351');
					break;
				
					case 'combo':
						return _text('MN00358');
					break;
				
					case 'checkbox':
						return _text('MN00353');
					break;
				
					case 'datefield':
						return _text('MN00354');
					break;
				
					case 'numberfield':
						return _text('MN00355');
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
					case null:
						return '<span style="color: #CCCCCC">off</span>';
					break;			
				}
			}

			function _submit(url) {
				Ext.getCmp('content_field_form').getForm().submit({
					url: '/pages/menu/config/custom/content_metadata/php/' + url,
					params: {
						action: 'field'
					},
					success: function(form, action) {
						try {
							var result = Ext.decode(action.response.responseText, true);
							if (result.success) {
								Ext.getCmp('content_field_win').close();
								Ext.getCmp('content_field').store.reload();
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
								//>>title: '오류',
								title: _text('MN00023'),
								icon: Ext.Msg.ERROR,
								msg: e.message,
								buttons: Ext.Msg.OK
							})
						}
					},
					failure: function(form, action) {
						Ext.Msg.show({
							icon: Ext.Msg.ERROR,
							//>>title: '오류',
							title: _text('MN00023'),
							msg: action.result.msg,
							buttons: Ext.Msg.OK
						});
					}
				});
			}

			function _buildForm(btn){
				
				return {
					id: 'content_field_form',
					xtype: 'form',
					baseCls: 'x-plain',
					defaultType: 'textfield',
					defaults: {
						anchor: '100%'
					},
					items: [{
						xtype: 'hidden',
						name: 'bs_content_id'
					},{
						xtype: 'hidden',
						name: 'sys_meta_field_id'
					},{
						name: 'sys_meta_field_title',
						//!!fieldLabel: '메타데이터 명',
						fieldLabel: _text('MN00169'),
						msgTarget: 'under',
						allowBlank: false,
						//2015-12-16 메타데이터 항목 명 10자 이내로 수정
						autoCreate: {tag: 'input', type: 'text', autocomplete: 'off', maxlength: '30'},
						listeners: {
							render: function(self){														
								self.focus(false, 700);
							}
						}
					},{
						name: 'sys_meta_field_code',
						//!!fieldLabel: '메타데이터 명',
						fieldLabel: _text('MN02154'),//'필드명',
						msgTarget: 'under',
						allowBlank: false,
						autoCreate: {tag: 'input', type: 'text', autocomplete: 'off', maxlength: '20'},
						listeners: {
							render: function(self){	
							}
						}
					},{
						xtype: 'combo',
						store: new Ext.data.ArrayStore({
							id: 0,
							fields: [ 'type', 'displayText' ],
							data: [
								['textfield',_text('MN00350')],								
								['textarea', _text('MN00351')],
								['combo',_text('MN00358')],
								['checkbox', _text('MN00353')],
								['datefield', _text('MN00354')],
								['numberfield',_text('MN00355')]
							]
						}),
						mode: 'local',
						hiddenName: 'type',
						displayField: 'displayText',
						valueField: 'type',
						value: 'textfield',
						triggerAction: 'all',
						editable: false,
						name: 'type',
						//!!fieldLabel: '입력형식',
						fieldLabel: _text('MN00228'),
						allowBlank: false
					},{
						xtype: 'checkbox',
						name: 'is_visible',
						//!!	fieldLabel: '보기',
						fieldLabel: _text('MN00040'),
						inputValue: '1',
						checked: true
					},{
						xtype: 'textarea',
						name: 'default_value',
						//!!fieldLabel: '기본값'
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
				var btn_text = '';
				if(btn.mode == _text('MN00033')){
					//add
					btn_text = '<span style="position:relative;top:1px;"><i class="fa fa-plus" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00033');
				}else{
					btn_text = '<span style="position:relative;top:1px;"><i class="fa fa-edit" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00043');
				}
				return new Ext.Window({
					id: 'content_field_win',
					cls: 'change_background_panel',
					layout: 'fit',
					title: _text('MN00164')+' '+btn.mode,
					width: 400,
					height: 300,
					padding: 10,
					modal: true,
					buttonAlign: 'center',
					items: _buildForm(btn),
					buttons: [{
						url: btn.url,
						text: btn_text,
						scale: 'medium',
						handler: function(btn, e){
							_submit(btn.url)
						}
					},{
						text: '<span style="position:relative;top:1px;"><i class="fa fa-close" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00004'),
						scale: 'medium',
						handler: function(){
							this.ownerCt.ownerCt.close();
						}
					}]
				});
			}

			function _load_form(btn, e){
				var win = _buildWindow(btn);
				if (btn.mode == _text('MN00033')) {
					var sm = Ext.getCmp('content_table').getSelectionModel();
					if (!sm.hasSelection()) {
						Ext.Msg.alert(_text('MN00023'), _text('MSG00111'));
						return;
					}else{
						var sel = sm.getSelected();
						var content_type_id = sel.get('bs_content_id');
						if (!content_type_id) {
							Ext.Msg.alert(_text('MN00023'), _text('MSG00111'));
							return;
						}
						win.show(btn.getId(), function(){
							this.get(0).getForm().setValues({
								bs_content_id: content_type_id
							})
						});
					}
				} else {
					var sm = Ext.getCmp('content_field').getSelectionModel();
					if (!sm.hasSelection()) {
						Ext.Msg.alert(_text('MN00023'), _text('MSG00112'));
						return;
					}
					win.show(btn.getId(), function(){
						this.get(0).getForm().loadRecord(sm.getSelected());
					});
				}
					
			}

			function _delete_record(btn, e){

				var msg = '';

				var sm = Ext.getCmp('content_field').getSelectionModel();
				if(!sm.hasSelection()) {
					Ext.Msg.alert(_text('MN00023'), _text('MSG00113'));
					return;
				}

				var recs = sm.getSelections();
				if(recs.length > 1){
					//!!msg = recs.length + ' 개의 선택된 메타데이터를 삭제 하시겠습니까?';
					msg = recs.length + ' '+_text('MSG00171');
				}else{
					//!!msg = recs[0].get('sys_meta_field_title') + '"  메타데이터를 삭제 하시겠습니까?';
					msg = recs[0].get('sys_meta_field_title') + ' '+_text('MSG00140');
				}

				var sys_meta_field_id_list = '';
				Ext.each(recs, function(item, index, allItems){
					sys_meta_field_id_list += item.get('sys_meta_field_id') + ',';
				})

				Ext.Msg.show({
					animEl: btn.getId(),
					title: _text('MN00003'),
					icon: Ext.Msg.INFO,
					msg: msg,
					buttons: Ext.Msg.OKCANCEL,
					fn: function(btnID, text, opt) {
						if(btnID == 'ok') {
							Ext.Ajax.request({
								url: '/pages/menu/config/custom/content_metadata/php/del.php',
								params: {
									action: 'delete_field',
									sys_meta_field_id_list: sys_meta_field_id_list
								},
								callback: function(opts, success, response) {
									try {
										var r = Ext.decode(response.responseText, true);
										if (r.success) {
											Ext.getCmp('content_field').getStore().reload();
										} else {
											//>>Ext.Msg.alert('오류', r.msg);
											Ext.Msg.alert(_text('MN00024'), r.msg);
										}
									} catch(e) {
										//>>alert('디코드 오류', e.message + '(responseText: ' + response.responseText + ')');
										alert(_text('MN00023'), e.message + '(responseText: ' + response.responseText + ')');
									}
								}
							})
						}
					}
				})
			}

			return new Ext.grid.GridPanel({
				//>>title: '메타데이터',
				//title: _text('MN00164'),
				title: '<span class="user_span"><span class="icon_title"><i class=""></i></span><span class="main_title_header">'+_text('MN00164')+'</span></span>',
				cls: 'grid_title_customize',
				stripeRows: true,
				border: false,
				id: 'content_field',
				region: 'south',
				height: 400,
				loadMask: true,
				enableDragDrop: true,
				ddGroup: 'fieldGridDD',
				store: new Ext.data.JsonStore({
					url: '/pages/menu/config/custom/content_metadata/php/get.php',
					root: 'data',
					idProperty: 'sys_meta_field_id',
					fields: [
						'bs_content_id',
						'sys_meta_field_code',
						'sys_meta_field_id',
						'show_order',
						'sys_meta_field_title',
						'field_input_type',
						'default_value',
						'is_visible'
					]
				}),
				colModel: new Ext.grid.ColumnModel({
					defaults: {
						align: 'center'
					},
					columns: [
					    /*
						{header: '메타데이터명', dataIndex: 'sys_meta_field_title'},
						{header: '형식', dataIndex: 'field_input_type', renderer: _renderType},
						{header: '보기', dataIndex: 'is_visible', renderer: _renderCheck},
						{header: '기본값', dataIndex: 'default_value'}
						*/
						{header: _text('MN00169'), dataIndex: 'sys_meta_field_title'},
						{header: _text('MN02154'), dataIndex: 'sys_meta_field_code'},//'필드명'
						{header: _text('MN00310'), dataIndex: 'field_input_type', renderer: _renderType},
						{header: _text('MN00040'), dataIndex: 'is_visible', renderer: _renderCheck},
						{header: _text('MN00155'), dataIndex: 'default_value'}
					]
				}),
				keys: [{
					key: 46,
					handler: function(){
						_delete_record(function(){
							return {
								getId: function(){
									return null;								
								}
							}
						}(), null);
					},
					scope: this
				}],
				viewConfig: {
					//>>emptyText: '검색된 데이터가 없습니다.',
					emptyText: _text('MSG00148'),
					forceFit: true
				},
				listeners: {
					rowdblclick: function(self, rowIndex, e) {
						//>>_load_form({text: '수정', url: 'edit.php', getText: function(){return '수정'}, getId: function(){return e.getTarget()}}, null);
						_load_form({text: _text('MN00043'), url: 'edit.php', mode: _text('MN00043'), getId: function(){return e.getTarget()}}, null);
						
						//>>_load_form({text: _text('MN00043'), url: 'edit.php', getText: function(){return _text('MN00043'),}, getId: function(){return e.getTarget()}}, null);
					}
				},
				//>>tbar: ['콘텐츠 종류', '-', {
				/*
				tbar: [{
					id: 'content_type_list',
					xtype: 'combo',
					store: new Ext.data.JsonStore({
						url: '/pages/menu/config/custom/content_metadata/php/get.php',
						baseParams: {
							action: 'content_type_list'
						},
						root: 'data',
						idProperty: 'bs_content_title',
						fields: [
							'bs_content_title',
							'bs_content_id'
						]
					}),
					listeners: {
						select: function(self, rec, idx){
							Ext.getCmp('content_field').store.load({
								params: {
									action: 'content_field_list',
									content_type_id: rec.get('bs_content_id')
								}
							});
						}
					},
					displayField: 'bs_content_title',
					valueField: 'bs_content_id',
					//>>fieldLabel: '콘텐츠 종류',
					fieldLabel: _text('MN00279'),
					triggerAction: 'all',
					forceSelection: true,
					editable: false,
					//>>emptyText: '콘텐츠 종류를 선택하여주세요'
					emptyText: _text('MSG00111')
				}],
				*/
				buttonAlign: 'center',
				fbar: [{
					url: 'add.php',
					text : '<span style="position:relative;top:1px;"><i class="fa fa-plus" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00033'),
					mode: _text('MN00033'),
					scale: 'medium',
					handler: _load_form
				},{
					url: 'edit.php',
					text : '<span style="position:relative;top:1px;"><i class="fa fa-edit" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00043'),
					mode: _text('MN00043'),
					scale: 'medium',
					handler: _load_form
				},{
					text : '<span style="position:relative;top:1px;"><i class="fa fa-trash" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00034'),
					scale: 'medium',
					handler: _delete_record
				}]
			});
		}
	});

	return new Ariel.config.custom.ContentMetadataPanel();
})()