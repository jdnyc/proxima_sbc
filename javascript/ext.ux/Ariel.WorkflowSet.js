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
			return new Ext.grid.GridPanel({
				id: 'task_workflow',
				//>>title: '작업흐름 설정',
				title: _text('MN00327'),
				region: 'center',
				loadMask: true,
				//enableDragDrop: true,
				ddGroup: 'tableGridDD',
				selModel: new Ext.grid.RowSelectionModel({
					singleSelect: true,
					listeners: {
						rowselect: function(self){
							Ext.getCmp('workflowRule').getLoader().load( Ext.getCmp('workflowRule').getRootNode() );
							 //Ext.getCmp('workflowRule').getStore().load();
						},
						rowdeselect: function(self){
							//Ext.getCmp('workflowRule').getStore().removeAll();
						}
					}
				}),
				store: new Ext.data.JsonStore({
					url: '/pages/menu/config/workflow/workflow_list.php',
					root: 'data',
					idPropery: 'task_workflow_id',
					fields: [
						'task_workflow_id',
						'user_task_name',
						'register',
						'description',
						'activity',
						'content_status_nm',
						'content_status'
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
						{header: 'No', dataIndex: 'task_workflow_id', width: 15, hidden: true},
						//>>{header: '작업흐름 명', dataIndex: 'user_task_name'},
						//>>{header: '등록 채널', dataIndex: 'register'},
						//>>{header: '활성화', dataIndex: 'activity'},
						//>>{header: '설 명', dataIndex: 'description'}
						{header: _text('MN00361'), dataIndex: 'user_task_name', width: 200},
						{header: _text('MN00362'), dataIndex: 'register', width: 100},
						{header: _text('MN00347'), dataIndex: 'activity', width: 30 , renderer: function (data){
							if(data == '1')
							{
								return "<font color=blue>"+_text('MN02157');//활성
							}
							else
							{
								return '<font color=red>'+_text('MN02158');//비활성
							}
						}},
						{header: _text('MN00049'), dataIndex: 'description', width: 180},
						{header: _text('MN02051'), dataIndex: 'content_status_nm', width: 80}//완료후 콘텐츠 상태
					]
				}),
				listeners: {
					viewready: function(self){
						self.store.load({
							params: {
								action: 'task_workflow'
							}
						});
						var upGridDroptgtCfg = Ext.apply({}, dropZoneOverrides, {
							table: 'task_workflow',
							id_field: 'task_workflow_id',
							ddGroup: 'tableGridDD',
							grid : Ext.getCmp('task_workflow')
						});
						new Ext.dd.DropZone(Ext.getCmp('task_workflow').getEl(), upGridDroptgtCfg);
					},
					rowdblclick: {
						fn: function(self, rowIndex, e){

							this.buildEditTableWin(e, self.getSelectionModel().getSelected());
						},
						scope: this
					}
				},
				viewConfig: {
					listeners: {
						refresh: function(self) {
							//Ext.getCmp('workflowRule').getStore().removeAll();
						}
					},

					forceFit: true,
					emptyText: 'no data'
				},

				buttonAlign: 'center',
				fbar: [{
					//>>text: lang.addLabel,
					text: _text('MN00033'),
					scale: 'medium',
					handler: function(btn, e) {
						this.buildAddTable(e);
					},
					scope: this
				},{
					//>>text: lang.editLabel,
					text: _text('MN00043'),
					scale: 'medium',
					handler: function(btn, e) {
						var hasSelection = Ext.getCmp('task_workflow').getSelectionModel().hasSelection();
						if(hasSelection) {
							var sel = Ext.getCmp('task_workflow').getSelectionModel().getSelected();
							this.buildEditTableWin(e , sel);

						}else{
							//>>Ext.Msg.alert(lang.errorLabel, '수정할 항목을 선택해주세요');
							Ext.Msg.alert(_text('MN00022'), _text('MSG00084'));
						}
					},
					scope: this
				},{
					//>>text: lang.deleteLabel,
					text: _text('MN00034'),
					scale: 'medium',
					handler: function(btn, e) {
						var hasSelection = Ext.getCmp('task_workflow').getSelectionModel().hasSelection();
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

		buildAddTable: function(e){  //추가 버튼 기능실행
			var win = new Ext.Window({
				id: 'add_workflow_win',
				layout: 'fit',
				//>>title: '작업흐름 추가',
				title: _text('MN00363'),
				width: 400,
				height: 260,
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
						name: 'user_task_name',
						//>>fieldLabel: '작업흐름 명',
						fieldLabel: _text('MN00361'),
						msgTarget: 'under',
						allowBlank: false
					},{
						xtype: 'checkbox',
						name: 'activity',
						status: 'active',
						//>>fieldLabel: '활성화'
						fieldLabel: _text('MN00347')
					},{
						name: 'register',
						//>>fieldLabel: '등록 채널',
						fieldLabel: _text('MN00362'),
						allowBlank: false
					},{
						xtype: 'textarea',
						name: 'description',
						//>>fieldLabel: '설  명'
						fieldLabel: _text('MN00049')
					},{
						xtype: 'combo',
						//fieldLabel: '콘텐츠 상태',
						fieldLabel: _text('MN02053'),
						name: 'content_status',
						store: new Ext.data.JsonStore({
							url: '/pages/menu/config/workflow/workflow_list.php',
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
						editable: true
					}]
				},
				buttons: [{
					//>>text: lang.userAddTableAddButton,
					text: _text('MN00033'),
					handler: function(btn, e) {

						//btn.disable();
						Ext.getCmp('add_table_form').getForm().submit({
							url: '/pages/menu/config/workflow/edit_workflow.php',
							params: {
								action: 'add_workflow'
							},
							success: function(form, action) {
								try {
									var result = Ext.decode(action.response.responseText, true);
									if(result.success) {
										Ext.getCmp('add_workflow_win').close();
										Ext.getCmp('task_workflow').store.reload();
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
					//>>text: lang.userAddTableCancelButton,
					text: _text('MN00004'),
					handler: function() {
						this.ownerCt.ownerCt.close();
					}
				}]
			}).show(e.getTarget());
		},

		buildEditTableWin: function(e, sel) { //수정버튼 기능 실행

			var _submit = function(){
				Ext.getCmp('edit_table_form').getForm().submit({
					url: '/pages/menu/config/workflow/edit_workflow.php',
					params: {
						action: 'edit_workflow'
					},
					success: function(form, action){
						try {
							var result = Ext.decode(action.response.responseText, true);
							if (result.success) {
								// 정상
								Ext.getCmp('edit_table_win').close();
								Ext.getCmp('task_workflow').store.reload();
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
				id: 'edit_table_win',
				layout: 'fit',
				//>>title: '작업흐름 수정',
				title: _text('MN00364'),
				width: 400,
				height: 260,
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
						name: 'task_workflow_id'
					},{
						name: 'user_task_name',
						//>>fieldLabel: '작업흐름 명',
						fieldLabel: _text('MN00361'),
						msgTarget: 'under',
						allowBlank: false,
						listeners: {
							render: function(self){
								self.focus(true, 500);
							}
						}
					},{
						xtype: 'checkbox',
						name: 'activity',
						//>>fieldLabel: '활성화'
						fieldLabel: _text('MN00347')
					},{
						name: 'register',
						//>>fieldLabel: '등록 채널',
						fieldLabel: _text('MN00362'),
						allowBlank: false
					},{
						xtype: 'textarea',
						name: 'description',
						//>>fieldLabel: '설 명'
						fieldLabel: _text('MN00049')
					},{
						xtype: 'combo',
						//fieldLabel: '콘텐츠 상태',
						fieldLabel: _text('MN02053'),
						name: 'content_status',
						store: new Ext.data.JsonStore({
							url: '/pages/menu/config/workflow/workflow_list.php',
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
						value: sel.get('content_status'),
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
							var sm = Ext.getCmp('task_workflow').getSelectionModel();
							var rec = sm.getSelected();
							self.getForm().loadRecord(rec);

							self.getForm().findField('content_status').getStore().load({
								callback: function(s , r){
								self.getForm().loadRecord(rec);
							}});
						}
					}
				},
				buttons: [{
					//>>text: lang.userEditTableEditButtonText,
					text: _text('MN00043'),
					handler: function(btn, e) {
						_submit();
					}
				},{
					//>>text: lang.cancel,
					text: _text('MN00004'),
					handler: function(btn, e) {
						this.ownerCt.ownerCt.close();
					}
				}]
			}).show(e.getTarget());
			Ext.getCmp('edit_table_form').getForm().findField( 'content_status' ).setValue(sel.get('content_status'));
		},

		buildDeleteTable: function(e) {
			var rec = Ext.getCmp('task_workflow').getSelectionModel().getSelected();

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
							url: '/pages/menu/config/workflow/edit_workflow.php',
							params: {
								action: 'delete_workflow',
								task_workflow_id: rec.get('task_workflow_id')
							},
							callback: function(opts, success, response) {
								try {
									var r = Ext.decode(response.responseText, true);
									if(r.success) {
										//Ext.Msg.alert(_text('MN00024'), r.msg);
										Ext.getCmp('task_workflow').store.reload();

										 Ext.getCmp('workflowRule').getRootNode().removeAll();
										//Ext.getCmp('workflowRule').getLoader().removeAll();//load( Ext.getCmp('workflowRule').getRootNode() );
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
								Ext.getCmp('field_win').close();
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
					id: 'field_win',
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
						//text: '취소',
						text: _text('MN02073'),
						handler: function(){
							this.ownerCt.ownerCt.close();
						}
					}]
				});
			}

			function _workflow_rule_edit(action, params){

				Ext.Ajax.request({
					url:'/pages/menu/config/workflow/edit_workflow.php',
					params: {
						action : action,
						params: params
					},
					callback: function(opt,suc,res){
						if(suc){
							var r = Ext.decode(res.responseText);
							if(r.success) {

								Ext.getCmp('workflowRule').getLoader().load( Ext.getCmp('workflowRule').getRootNode() );

								return true;
							}
							else {
								Ext.Msg.alert( _text('MN01039'),r.msg);//'오류'
							}
						}
						else{
							return false;
						}
					}
				});
			}

			return  new Ext.ux.tree.TreeGrid({
				id: 'workflowRule',
				//>>title:	"작업흐름 상세",
				title:	_text('MN00365'),
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
						},
						beforeload: function ( self,  node, response ) {

							if( !Ext.isEmpty( Ext.getCmp('task_workflow').getSelectionModel().getSelected() ) )
							{
								self.baseParams.task_workflow_id = Ext.getCmp('task_workflow').getSelectionModel().getSelected().get('task_workflow_id');
							}
						}
					},
					dataUrl: '/pages/menu/config/workflow/workflow_rule_tree.php'
				}),
				columns: [
					{header: _text('MN00366'), dataIndex: 'job_no', width: 120, align:'center'},
					{header: _text('MN00367'), dataIndex: 'job_name', width: 100},
					{header: _text('MN00299'), dataIndex:	'parameter', width: 230},
					{header: _text('MN00343'), dataIndex:	'src_path', width: 250},
					{header: _text('MN00344'), dataIndex:	'tar_path', width: 250},
					{header: _text('MN02159'), dataIndex:	'storage_group', width: 100 },//'스토리지 그룹'
					{header: _text('MN02051'), dataIndex:	'content_status_nm', width: 100},//'완료후 콘텐츠 상태'
					{header: 'task_rule_id', dataIndex:	'task_rule_id', width: 250 , hidden: true },
					{header: 'workflow_rule_parent_id', dataIndex:	'workflow_rule_parent_id', width: 250 , hidden: true },
					{header: 'task_rule_parant_id', dataIndex:	'task_rule_parant_id', width: 250 , hidden: true }
				],

				listeners: {
					dblclick: function(  node, e ){
							//console.log(node.getSelectionModel());

					},
					click: function ( node, e ){

						Ext.getCmp('workflowRule').getFooterToolbar().items.each(function(r){
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
							'task_rule_parant_id' :	node.attributes.task_rule_parant_id	,
							'task_workflow_id' :	node.attributes.task_workflow_id,
							'workflow_rule_id' :	node.attributes.workflow_rule_id
						});

						params.push( {
							'type' : 'newParent',
							'job_priority' : newParent.getDepth() - 1 ,
							'task_rule_id' : newParent.attributes.task_rule_id,
							'task_rule_parant_id' :	newParent.attributes.task_rule_parant_id,
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
								var hasSelection = Ext.getCmp('task_workflow').getSelectionModel().hasSelection();
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
								var hasSelection = Ext.getCmp('task_workflow').getSelectionModel().hasSelection();
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
								var hasSelection = Ext.getCmp('task_workflow').getSelectionModel().hasSelection();
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

				fbar: [{
					//>>text: lang.addLabel,
					text: _text('MN00033'),
					scale: 'medium',
					handler: function(btn, e) {
						var hasSelection = Ext.getCmp('task_workflow').getSelectionModel().hasSelection();
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
						var hasSelection = Ext.getCmp('task_workflow').getSelectionModel().hasSelection();
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
						var hasSelection = Ext.getCmp('task_workflow').getSelectionModel().hasSelection();
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

	/*
		buildField: function(){
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
					id: 'field_win',
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
						text: '취소',
						handler: function(){
							this.ownerCt.ownerCt.close();
						}
					}]
				});
			}


			return new Ext.grid.GridPanel({
			id: 'workflowRule',
			//>>title:	"작업흐름 상세",
			title:	_text('MN00365'),
			xtype:	'grid',
			region: 'south',
			split: true,
			collapsible: true,
			height: 350,
			loadMask: true,
			//autoExpandColumn: 'description',
			store:	new	Ext.data.JsonStore({
				id:	'rull_store',
				url: '/pages/menu/config/workflow/workflow_rule.php',
				totalProperty: 'total',
				idProperty:	'id',
				root: 'data',
				fields:	[
					{name: 'job_no'},
					{name: 'job_name'},
					{name: 'parameter'},
					{name: 'src_path'},
					{name: 'tar_path'},
					{name: 'workflow_rule_id'},
					{name: 'task_workflow_id'},
					{name: 'task_rule_id'}
				],
				listeners: {
					beforeload:	function(self, opts){
						var sel = Ext.getCmp('task_workflow').getSelectionModel().getSelected();
						self.baseParams.task_workflow_id	= sel.get('task_workflow_id');
					}
				}
			}),
			columns: [
				//>>{header: '작업 순서', dataIndex: 'job_no', width: 75, align:'center'},
				//>>{header: '작업 명칭', dataIndex: 'job_name', width: 100},
				//>>{header: '파라미터', dataIndex:	'parameter', width: 230},
				//>>{header: '소스 경로', dataIndex:	'src_path', width: 250},
				//>>{header: '타겟 경로', dataIndex:	'tar_path', width: 250}

				{header: _text('MN00366'), dataIndex: 'job_no', width: 75, align:'center'},
				{header: _text('MN00367'), dataIndex: 'job_name', width: 100},
				{header: _text('MN00299'), dataIndex:	'parameter', width: 230},
				{header: _text('MN00343'), dataIndex:	'src_path', width: 250},
				{header: _text('MN00344'), dataIndex:	'tar_path', width: 250}
			],
			selModel: new Ext.grid.RowSelectionModel({
				singleSelect: true
			}),
			listeners: {
				rowdblclick: {
					fn: function(self, rowIndex, e){
						this.buildEditWorkflowRuleWin(e);
					},
					scope: this
				}
			},
			viewConfig: {
				//>>emptyText: '작업흐름을 선택해 주세요.'
				emptyText: _text('MSG00176')
			},

			buttonAlign: 'center',

			fbar: [{
				//>>text: lang.addLabel,
				text: _text('MN00033'),
				scale: 'medium',
				handler: function(btn, e) {
					var hasSelection = Ext.getCmp('task_workflow').getSelectionModel().hasSelection();
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
					var hasSelection = Ext.getCmp('task_workflow').getSelectionModel().hasSelection();
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
					var hasSelection = Ext.getCmp('task_workflow').getSelectionModel().hasSelection();
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

		*/

		buildAddWorkflowRuleWin: function(e){  //작업 상세 추가 버튼 기능실행
			var win = new Ext.Window({
				id: 'add_workflow_rule_win',
				layout: 'fit',
				//>>title: '작업 추가',
				title: _text('MN00070'),
				width: 400,
				height: 250,
				padding: 10,
				modal: true,
				items: {
					id: 'add_task_rule_form',
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
							url: '/pages/menu/config/workflow/workflow_list.php',
							baseParams: {
								action: 'task_rule'
							},
							root: 'data',
							idProperty: 'task_rule_id',
							fields: [
								{name: 'job_name', type: 'string'},
								{name: 'task_rule_id', type: 'int'},
								{name: 'source_path', type: 'int'},
								{name: 'target_path', type: 'int'}
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
							select : function( combo, record, index ){								
								var source_path = record.json.source_path;
								var target_path = record.json.target_path;
								combo.ownerCt.getForm().findField('s_name').setValue(source_path); 
								combo.ownerCt.getForm().findField('t_name').setValue(target_path); 
							}
						}
					},{					
						xtype: 'combo',
						//>>fieldLabel: '소스 경로',
						fieldLabel: _text('MN00343'),
						store: new Ext.data.JsonStore({
							url: '/pages/menu/config/workflow/workflow_list.php',
							baseParams: {
								action: 'storage_list'
							},
							autoLoad: true,
							root: 'data',
							idProperty: 'storage_id',
							fields: [
								{name: 'name', type: 'string'},
								{name: 'storage_id', type: 'int'},
								{name: 'path', type: 'string'}
							]
						}),
						hiddenName: 's_name',									
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
							autoLoad: true,
							root: 'data',
							idProperty: 'storage_id',
							fields: [
								{name: 'name', type: 'string'},
								{name: 'storage_id', type: 'int'},
								{name: 'path', type: 'string'}
							]
						}),
						hiddenName: 't_name',						
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
						xtype: 'combo',						
						//fieldLabel: '스토리지 그룹',
						fieldLabel: _text('MN02159'),
						name: 'storage_group',
						store: new Ext.data.JsonStore({
							url: '/pages/menu/config/workflow/workflow_list.php',
							baseParams: {
								action: 'storage_group'
							},
							root: 'data',
							idProperty: 'code',
							fields: [
								{name: 'name', type: 'string'},
								{name: 'code', type: 'int'}
							]
						}),
						allowBlank: true,
						hiddenName: 'storage_group',
						valueField: 'code',
						displayField: 'name',
						typeAhead: true,
						triggerAction: 'all',
						forceSelection: true,
						editable: false
					},{
						xtype: 'combo',
						//fieldLabel: '콘텐츠 상태',
						fieldLabel: _text('MN02053'),
						name: 'content_status',
						store: new Ext.data.JsonStore({
							url: '/pages/menu/config/workflow/workflow_list.php',
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
					}
					/*,{
						name: 'job_no',
						//>>fieldLabel: '작업 순서',
						fieldLabel: _text('MN00366'),
						allowBlank: false,
						//>>emptyText: '작업 실행 번호를 입력하세요'
						emptyText: _text('MSG00187')
					}*/]
				},
				buttons: [{
					//>>text: lang.userAddTableAddButton,
					text: _text('MN00033'),
					handler: function(btn, e) {
						//btn.disable();
						var add_task_id = Ext.getCmp('task_workflow').getSelectionModel().getSelected().get('task_workflow_id');

						if( !Ext.isEmpty( Ext.getCmp('workflowRule').getSelectionModel().getSelectedNode() ) )
						{
							var task_rule_parant_id = Ext.getCmp('workflowRule').getSelectionModel().getSelectedNode().attributes.task_rule_id;
							var job_priority =  parseInt( Ext.getCmp('workflowRule').getSelectionModel().getSelectedNode().attributes.job_no ) + 1;

							var workflow_rule_parent_id = Ext.getCmp('workflowRule').getSelectionModel().getSelectedNode().attributes.workflow_rule_id;
						}
						else
						{
							var task_rule_parant_id = 0;
							var workflow_rule_parent_id = 0;
							var job_priority = 1;
						}

						Ext.getCmp('add_task_rule_form').getForm().submit({

							url: '/pages/menu/config/workflow/edit_workflow.php',
							params: {
								action: 'add_task_rule',
								task_rule_parant_id : task_rule_parant_id,
								workflow_rule_parent_id : workflow_rule_parent_id,
								job_priority : job_priority,
								task_workflow_id: add_task_id
							},
							success: function(form, action) {
								try {
									var result = Ext.decode(action.response.responseText, true);
									if(result.success) {
										Ext.getCmp('add_workflow_rule_win').close();
										Ext.getCmp('workflowRule').getLoader().load( Ext.getCmp('workflowRule').getRootNode() );

										//Ext.getCmp('workflowRule').store.reload();
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
					//>>text: lang.userAddTableCancelButton,
					text: _text('MN00004'),
					handler: function() {
						this.ownerCt.ownerCt.close();
					}
				}]
			}).show(e.getTarget());
		},

		buildEditWorkflowRuleWin: function(e) { //작업 상세 수정버튼 기능 실행

			var _submit = function(){
				Ext.getCmp('edit_workflowRule_form').getForm().submit({
					url: '/pages/menu/config/workflow/edit_workflow.php',
					params: {
						action: 'edit_workflow_rule'
					},
					success: function(form, action){
						try {
							var result = Ext.decode(action.response.responseText, true);
							if (result.success) {
								// 정상
								Ext.getCmp('edit_table_win').close();
								Ext.getCmp('workflowRule').getLoader().load( Ext.getCmp('workflowRule').getRootNode() );

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
				id: 'edit_table_win',
				layout: 'fit',
				//title: '작업흐름 상세 수정',
				title: _text('MN00369'),
				width: 400,
				height: 250,
				padding: 10,
				modal: true,
				items: {
					id: 'edit_workflowRule_form',
					xtype: 'form',
					baseCls: 'x-plain',
					defaultType: 'textfield',
					defaults: {
						anchor: '100%'
					},
					items: [{
						xtype: 'hidden',
						name: 'workflow_rule_id'
					},{
						xtype: 'combo',
						//>>fieldLabel: '작업 명',
						fieldLabel: _text('MN00345'),
						name: 'job_name',
						readOnly : true,
						//hiddenName: 'task_rule_ids',
						store: new Ext.data.JsonStore({
							url: '/pages/menu/config/workflow/workflow_list.php',
							baseParams: {
								action: 'task_rule'
							},
							root: 'data',
							idProperty: 'task_rule_id',
							fields: [
								{name: 'job_name', type: 'string'},
								{name: 'task_rule_id', type: 'int'},
								{name: 'source_path', type: 'int'},
								{name: 'target_path', type: 'int'}
							]
						}),
						allowBlank: false,
						valueField: 'task_rule_id',
						displayField: 'job_name',
						typeAhead: true,
						triggerAction: 'all',
						forceSelection: true,
						editable: false,
						listeners: {
							select : function( combo, record, index ){								
								var source_path = record.json.source_path;
								var target_path = record.json.target_path;
								combo.ownerCt.getForm().findField('s_name').setValue(source_path); 
								combo.ownerCt.getForm().findField('t_name').setValue(target_path); 
							}
						}
						//emptyText: '수정할 작업을 선택하세요'
					},{					
						xtype: 'combo',
						//>>fieldLabel: '소스 경로',
						fieldLabel: _text('MN00343'),
						store: new Ext.data.JsonStore({
							url: '/pages/menu/config/workflow/workflow_list.php',
							baseParams: {
								action: 'storage_list'
							},
							autoLoad: true,
							root: 'data',
							idProperty: 'storage_id',
							fields: [
								{name: 'name', type: 'string'},
								{name: 'storage_id', type: 'int'},
								{name: 'path', type: 'string'}
							]
						}),
						hiddenName: 's_name',									
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
							autoLoad: true,
							root: 'data',
							idProperty: 'storage_id',
							fields: [
								{name: 'name', type: 'string'},
								{name: 'storage_id', type: 'int'},
								{name: 'path', type: 'string'}
							]
						}),
						hiddenName: 't_name',						
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
						xtype: 'combo',						
						//fieldLabel: '스토리지 그룹',
						fieldLabel: _text('MN02159'),
						name: 'storage_group',
						store: new Ext.data.JsonStore({
							url: '/pages/menu/config/workflow/workflow_list.php',
							baseParams: {
								action: 'storage_group'
							},
							autoLoad: true,
							root: 'data',
							idProperty: 'code',
							fields: [
								{name: 'name', type: 'string'},
								{name: 'code', type: 'int'}
							]
						}),
						allowBlank: true,
						hiddenName: 'storage_group',
						valueField: 'code',
						displayField: 'name',
						typeAhead: true,
						triggerAction: 'all',
						forceSelection: true,
						editable: false
					},{
						xtype: 'combo',
						//fieldLabel: '콘텐츠 상태',
						fieldLabel: _text('MN02053'),
						name: 'content_status',
						store: new Ext.data.JsonStore({
							url: '/pages/menu/config/workflow/workflow_list.php',
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
					}
					/*,{
						name: 'job_no',
						//>>fieldLabel: '작업 순서'
						fieldLabel: _text('MN00366')
					}*/],
					keys: [{
						key: 13,
						handler: function(){
							_submit();
						}
					}],
					listeners: {
						afterrender: function(self) {

							var sm = Ext.getCmp('workflowRule').getSelectionModel();
								
							var rec = sm.getSelectedNode().attributes;
							var data = new Ext.data.Record(rec);
						
							self.getForm().loadRecord(data);
							self.getForm().findField('content_status').getStore().load({
								callback: function(s , r){
								self.getForm().findField('content_status').setValue(rec.content_status);
							}});

							self.getForm().findField('storage_group').getStore().load({
								callback: function(s , r){
								self.getForm().findField('storage_group').setValue(rec.storage_group_id);
							}});
							self.getForm().findField('s_name').getStore().load({
								callback: function(s , r){
								self.getForm().findField('s_name').setValue(rec.source_path_id);
							}});
							self.getForm().findField('t_name').getStore().load({
								callback: function(s , r){
								self.getForm().findField('t_name').setValue(rec.target_path_id);
							}});
						}
					}
				},
				buttons: [{
					//>>text: lang.userEditTableEditButtonText,
					text: _text('MN00043'),
					handler: function(btn, e) {
						_submit();
					}
				},{
					//>>text: lang.cancel,
					text: _text('MN00004'),
					handler: function(btn, e) {
						this.ownerCt.ownerCt.close();
					}
				}]
			}).show(e.getTarget());
		},

		buildDeleteRuleWin: function(e) {

			var sm = Ext.getCmp('workflowRule').getSelectionModel();
			var rec = sm.getSelectedNode().attributes;

			// 삭제 확인 창
			Ext.Msg.show({
				animEl: e.getTarget(),
				//title: '확인',
				title: _text('MN00024'),
				icon: Ext.Msg.INFO,
				//>>msg: '선택하신 "' + rec.get('job_name') + '" 작업 을 삭제 하시겠습니까?',
				msg: '"' + rec.job_name + '"' + _text('MSG00172'),
				buttons: Ext.Msg.OKCANCEL,
				fn: function(btnID, text, opt) {
					if(btnID == 'ok') {
						Ext.Ajax.request({
							url: '/pages/menu/config/workflow/edit_workflow.php',
							params: {
								action: 'del_task_rule',
								workflow_rule_id: rec.workflow_rule_id
							},
							callback: function(opts, success, response) {
								try {
									var r = Ext.decode(response.responseText, true);
									if(r.success) {
										Ext.getCmp('workflowRule').getLoader().load( Ext.getCmp('workflowRule').getRootNode() );
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