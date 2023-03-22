<?php
session_start();

?>
(function(){

	Ext.ns('Ariel.config.custom');

	Ariel.config.custom.SNSPanel = Ext.extend(Ext.Panel, {
		layout: 'border',
		border: false,
		defaults: {
			split: true
		},

		initComponent: function(config){
			Ext.apply(this, config || {});

			this.ListTable = this.buildListTable();

			this.items = [
				this.ListTable
			];

			Ariel.config.custom.SNSPanel.superclass.initComponent.call(this);
		},
		buildListTable: function(){
			var list_store = new Ext.data.JsonStore({
				url: '/pages/sns/get_list.php',
				root: 'data',
				totalProperty: 'total',
				fields: [
					'content_id',
					'sns_seq_no',
					'social_type_nm',
					'title',
					'content',
					'web_url1',
					'status',
					'reg_user_nm',
					'created_date',
					'deleted_date'
				],
				listeners: {
					beforeload: function(self, opts){
						opts.params = opts.params || {};

						Ext.apply(opts.params, {
							s_date: Ext.getCmp('sns_start_date').getValue().format('Ymd000000'),
							e_date: Ext.getCmp('sns_end_date').getValue().format('Ymd240000'),
							keyword: Ext.getCmp('sns_keyword').getValue()
						});
					}
				}
			});

			return new Ext.grid.GridPanel({
				id: 'sns_list_table',
				title: '<span class="user_span"><span class="icon_title"><i class=""></i></span><span class="main_title_header">'+_text('MN02324')+'</span></span>',
				cls: 'grid_title_customize proxima_customize',
				stripeRows: true,
				border: false,
				//title: _text('MN02331'),//'SNS Transfer List',//ln
				flex: 2,
				region: 'center',
				loadMask: true,
				selModel: new Ext.grid.RowSelectionModel({
					singleSelect: false,
					listeners: {
						
					}
				}),
				store: list_store,
				tbar: [_text('MN00107'),':'//'Created Date:'
				,{
					xtype: 'datefield',
					id: 'sns_start_date',
					editable: false,
					format: 'Y-m-d',
					listeners: {
						render: function(self){
							var date = new Date();
							self.setValue(date.add(Date.DAY, -14).format('Y-m-d'));
						}
					}
				},'~',{
					xtype: 'datefield',
					id: 'sns_end_date',
					editable: false,
					format: 'Y-m-d',
					listeners: {
						render: function(self){
							var date = new Date();
							self.setValue(date.format('Y-m-d'));
						}
					}
				},' ',_text('MN02332'),':'//'Keyword:'
				,{
					xtype: 'textfield',
					id: 'sns_keyword'
				},{
					xtype: 'button',
					cls: 'proxima_button_customize',
					width: 30,
					//search
					text: '<span style="position:relative;top:1px;" title="'+_text('MN00059')+'"><i class="fa fa-search" style="font-size:13px;color:white;"></i></span>',
					handler: function(b, e){
						list_store.reload();
					}
				},'->',{
					xtype: 'button',
					cls: 'proxima_button_customize',
					width: 30,
					text: '<span style="position:relative;top:1px;" title="'+_text('MN02333')+'"><i class="fa fa-user" style="font-size:13px;color:white;"></i></span>',//'SNS Account Management',
					handler: function(b, e){
						var win = new Ext.Window({
							width: 700,
							height: 200,
							title: _text('MN02333'),//'SNS Account Management',//ln
							layout: 'fit',
							modal: true,
							items:[{
								xtype: 'grid',
								cls: 'proxima_customize',
								stripeRows: true,
								border: false,
								id: 'sns_account_table',
								height: 200,
								region: 'north',
								loadMask: true,
								selModel: new Ext.grid.RowSelectionModel({
									singleSelect: true,
									listeners: {
										
									}
								}),
								tbar: [{
									//Edit
									//text: '<span style="position:relative;top:1px;"><i class="fa fa-pencil-square-o" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00043'),
									cls: 'proxima_button_customize',
									width: 30,
									text: '<span style="position:relative;top:1px;" title="'+_text('MN00043')+'"><i class="fa fa-pencil-square-o" style="font-size:13px;color:white;"></i></span>',
									handler: function(b, e) {
										Ext.getCmp('sns_account_table').editAccount();
									}
								}],
								store: new Ext.data.JsonStore({
									url: '/pages/sns/get_account.php',
									root: 'data',
									fields: [
										'social_type',
										'social_type_id',
										'user_id',
										'password',
										'token',
										'use_yn'
									],
									listeners: {
										load: function(self){
											Ext.getCmp('sns_account_table').getSelectionModel().selectRow(0, true);
										}
									}
								}),
								colModel: new Ext.grid.ColumnModel({
									defaults: {
										sortable: true
									},
									columns: [
										new Ext.grid.RowNumberer(),
										//'Use Y/N'
//										{header: _text('MN02334'), dataIndex: 'use_yn', width: 50, align: 'center', renderer: function(v) {
//											switch (v) {
//												case 'Y':
//													return '<span style="color: blue">Y</span>';
//												break;
//
//												case 'N':
//													return '<span style="color: #CCCCCC">N</span>';
//												break;
//											}
//										}},
										//'Social Type'
										{header: _text('MN02335'), dataIndex: 'social_type', width: 80},
										{header: 'ID', dataIndex: 'user_id', width: 90},
										//{header: 'Password', dataIndex: 'password', width: 90},
										//'Token Key'
										{header: _text('MN02336'), dataIndex: 'token', width: 200}
									]
								}),
								listeners: {
									viewready: function(self){
										self.store.load();
									},
									rowdblclick: function(){
										Ext.getCmp('sns_account_table').editAccount();
									}
								},
								viewConfig: {
									listeners: {
										
									},

									forceFit: true
								},
								editAccount: function(){
									var grid = Ext.getCmp('sns_account_table');
									var sel_model = grid.getSelectionModel();
									if(sel_model.getCount() == 0) {
										Ext.Msg.alert(_text('MN00023'),_text('MSG00026'));
										return;
									}

									var sel_info = sel_model.getSelected();
									var token_disable = true;

									//2016-05-15 only facebook save token in web. Other, save token in tomcat.
									if(sel_info.get('social_type_id') == 'FACEBOOK') {
										token_disable = false;
									}

									var use_yn_check = false;
									if(sel_info.get('use_yn') == 'Y') {
										use_yn_check = true;
									}
									
									var win = new Ext.Window({
										title: _text('MN02378'),
										cls: 'change_background_panel',
										width: 400,
										height: 260,
										layout: 'fit',
										modal: true,
										buttonAlign: 'center',
										items: [{
											xtype: 'form',
											name: 'sns_form',
											url: '/pages/sns/edit_account.php',
											frame: true,
											padding: 10,
											defaults: {
												width: 250
											},
											items: [{
												xtype: 'checkbox',
												hidden: true,
												fieldLabel: _text('MN02334'),//'Use Y/N',
												name: 'use_yn',
												checked: use_yn_check
											},{
												xtype: 'textfield',
												fieldLabel: _text('MN02335'),//'Social Type',
												readOnly: true,
												name: 'social_type',
												value: sel_info.get('social_type')
											},{
												xtype: 'textfield',
												fieldLabel: 'Social Type ID',
												hidden: true,
												name: 'social_type_id',
												value: sel_info.get('social_type_id')
											},{
												xtype: 'textfield',
												fieldLabel: 'ID',
												name: 'user_id',
												value: sel_info.get('user_id')
											},{
												xtype: 'textfield',
												fieldLabel: 'Password',
												name: 'password',
												value: sel_info.get('password')
											},{
												xtype: 'textarea',
												fieldLabel: _text('MN02336'),//'Token Key',
												disabled: token_disable,
												name: 'token',
												value: sel_info.get('token')
											}]
										}],
										buttons: [{
											text : '<span style="position:relative;top:1px;"><i class="fa fa-edit" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00043'),
											scale: 'medium',
											handler: function(btn, e){
												Ext.Msg.show({
													title: _text('MN00024'),
													msg: _text('MN00043')+', '+_text('MSG01007'),
													buttons: Ext.Msg.YESNO,
													fn: function(btnID, text, opt){
														if (btnID == 'yes')
														{
															var form = win.items.get(0).getForm();
												
															Ext.Ajax.request({
																url: '/pages/sns/edit_account.php',
																params:{
																	social_type_id: form.findField('social_type_id').getValue(),
																	user_id: form.findField('user_id').getValue(),
																	token: form.findField('token').getValue(),
																	//use_yn: form.findField('use_yn').getValue(),
																	password: form.findField('password').getValue()
																},
																callback: function(opt, success, res) {
																	grid.getStore().reload();
																}
															});
															
															win.close();
														}
													}
												});
											}
										},{
											text : '<span style="position:relative;top:1px;"><i class="fa fa-close" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00004'),
											scale: 'medium',
											handler: function(btn, e){
												win.close();
											}
										}]
									});

									win.show();
								}
							}]
						});

						win.show();
					}
				}],
				colModel: new Ext.grid.ColumnModel({
					defaults: {
						sortable: false
					},
					columns: [
						{header: '', dataIndex: 'sns_seq_no', width: 50},
						{header: 'Content ID', dataIndex: 'content_id', width: 50, hidden: 'true'},
						//'Social Type'
						{header: _text('MN02335'), dataIndex: 'social_type_nm', width: 70},
						//'Title'
						{header: _text('MN00249'), dataIndex: 'title', width: 200},
						//'Content'
						{header: _text('MN00067'), dataIndex: 'content', width: 250},
						{header: 'URL', dataIndex: 'web_url1', width: 150},
						//'Status'
						{header: _text('MN00138'), dataIndex: 'status', width: 70},
						//'Creator'
						{header: _text('MN02309'), dataIndex: 'reg_user_nm', width: 100},
						//'Created Date'
						{header: _text('MN00107'), dataIndex: 'created_date', width: 130},
						//'Deleted Date'
						{header: _text('MN00105'), dataIndex: 'deleted_date', width: 130}
					]
				}),
				listeners: {
					viewready: function(self){
						self.store.load();
					},
					rowcontextmenu: function(self, rowIndex, e){

						e.stopEvent();

						var sm = self.getSelectionModel();
						if (!sm.isSelected(rowIndex)) {
							sm.selectRow(rowIndex);
						}

						var menu = new Ext.menu.Menu({
							items: [{
								//Delete
								text: '<span style="position:relative;top:1px;"><i class="fa fa-ban" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00034'),
								handler: function(b, e) {
									var grid = Ext.getCmp('sns_list_table');
									var sel_model = grid.getSelectionModel();
									if(sel_model.getCount() == 0) {
										Ext.Msg.alert(_text('MN00023'),_text('MSG00026'));
										return;
									}

									var rs = [];
									Ext.each(sel_model.getSelections(), function(r, i, a){
										rs.push(r.get('sns_seq_no'));
									});

									Ext.Msg.show({
										title: _text('MN00024'),
										msg: _text('MN00034')+', '+_text('MSG01007'),
										buttons: Ext.Msg.YESNO,
										fn: function(btnID, text, opt){
											if (btnID == 'yes')
											{
												Ext.Ajax.request({
													url: '/pages/sns/edit_list.php',
													params:{
														mode: 'delete',
														'sns_seq_no_arr[]': rs
													},
													callback: function(opt, success, res) {
														grid.getStore().reload();
													}
												});
											}
										}
									});
								}
							}]
						});
						
						menu.showAt(e.getXY());
					}
				},
				viewConfig: {
					listeners: {
						
					},

					forceFit: false
				},
				bbar: new Ext.PagingToolbar({
					pageSize: 50,
					displayInfo: true,
					store: list_store
				})
			});
		}
	});

	return new Ariel.config.custom.SNSPanel();
})()
