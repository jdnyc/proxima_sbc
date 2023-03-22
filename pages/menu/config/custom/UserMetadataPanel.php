<?php
// 2017.11.22 기준 사용되는 파일
require_once($_SERVER['DOCUMENT_ROOT']."/lib/config.php");
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

fn_checkAuthPermission($_SESSION);


$query= "select * from bc_code where code_type_id  in (select id from bc_code_type ct where ct.code = 'FLDLNM')";
$all = $db->queryAll($query);
foreach($all as $v)
{
	$id = '\'del_'.$v['code'].'_field\'';
	$combo_name =  'del_'.$v['code'].'_date';
	$checkboxName = '\'del_'.$v['code'].'_checkbox\' ';
	$title =  $v['name']." 삭제 기한";
	$file_del_str.= "
			,{
				//유동성있게 변하게 해야함
				id : $id,
				xtype:'fieldset',
				title: '$title',
				checkboxToggle: true,
				checkboxName: $checkboxName,
				autowidth:true,
					items : [{
								 xtype : 'compositefield'
								 ,msgTarget: 'side'
								// ,fieldLabel: ' 파일 삭제 기한 '
								 ,fieldLabel: _text('MN01007')
								 ,layout: {
									align: 'middle'
									,pack: 'center'
									,type: 'hbox'
								  }
								 ,items : [
										{
										name : '$combo_name',
										xtype: 'combo',
										width: 100,
										autoWidth: true,
										store: new Ext.data.JsonStore({
											url: '/pages/menu/config/custom/user_metadata/php/get.php',
											baseParams: {
												action: 'file_del_date_list'
											},
											root: 'data',
											idProperty: 'id',
											fields: [
												{name: 'code', type: 'string'},
												{name: 'name', type: 'string'}
											]
										}),
										hiddenName: '$combo_name',
										valueField: 'code',
										displayField: 'name',
										fieldLabel: '삭제 예정일',
										typeAhead: true,
										triggerAction: 'all',
										forceSelection: true,
										editable: false
										}
									]
								}
							]
				}
		";

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
			this.fieldGrid = this.buildField();
			this.ContainerGrid = this.buildContainer();

			this.items = [
				this.tableGrid,
				this.fieldGrid,
				this.ContainerGrid
			];

			Ariel.config.custom.MetadataPanel.superclass.initComponent.call(this);
		},

		buildTable: function(){
			return new Ext.grid.GridPanel({
				id: 'bc_ud_content',
				//$$ MN00198 사용자 정의 콘텐츠 구성
				//title: _text('MN00198'),
				title: '<span class="user_span"><span class="icon_title"><i class=""></i></span><span class="main_title_header">'+_text('MN00198')+'</span></span>',
				cls: 'grid_title_customize proxima_customize',
				border: false,
				region: 'north',
				loadMask: true,
				height: 300,
				enableDragDrop: true,
				ddGroup: 'tableGridDD',
				stripeRows: true,
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
						'storage',
						//'use_common_category',
						'ori_content_idx',
						'content_expire_date',
						'e_date',
						'contents_expire_date',
						'se_date',
						'show_contents_expire_date',
						'ud_content_code',
						'category',
						'category_name'
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
						{header: _text('MN00273'), dataIndex: 'ud_content_title', width: 80},//콘텐츠 명
						{header: _text('MN00279'), dataIndex: 'bs_content_title', width: 60},//콘텐츠 종류
						{header: _text('MN00309'), dataIndex: 'allowed_extension', width: 200},//허용 확장자
						//{header: '카테고리 사용', dataIndex: 'use_common_category', width: 60},//
						{header: _text('MN01005'), dataIndex: 'show_contents_expire_date', width: 80},//콘텐츠 별 사용기간
						{header: _text('MN02153') , width: 80 , dataIndex: 'ud_content_code'},//테이블명
						{header: _text('MN00049'), dataIndex: 'description', width: 300},//설명,
						{header: _text('MN01013'), dataIndex: 'category_name'},
						{header: _text('MN01013'), dataindex: 'category', hidden: 'true'}
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
							var sm = Ext.getCmp('bc_ud_content').getSelectionModel();
							var sel = sm.getSelected();
							this.buildEditTableWin(e,sel);
						},
						scope: this
					},
					rowclick: function (self, rowIndex, e){
						var sm = Ext.getCmp('bc_ud_content').getSelectionModel();
						var sel = sm.getSelected();
						var ud_content_id = sel.get('ud_content_id');

						Ext.getCmp('bc_usr_meta_field_container').store.load({
							params: {
								action: 'container_list',
								ud_content_id: ud_content_id
							},
							callback : function(records, operation, success) {
								if (records.length != 0){
									Ext.getCmp('bc_usr_meta_field').store.load({
										params: {
											action: 'table_field',
											ud_content_id: ud_content_id,
											container_id: records[0].get('container_id')
										}
									});
									var sm = Ext.getCmp('bc_usr_meta_field_container').getSelectionModel();
									sm.selectFirstRow();
									Ext.getCmp('btn_delete_metadata_container').disable();
									Ext.getCmp('btn_change_order_container_metadata_top').disable();
									Ext.getCmp('btn_change_order_container_metadata_up').disable();
									Ext.getCmp('btn_change_order_container_metadata_down').disable();
									Ext.getCmp('btn_change_order_container_metadata_bottom').disable();
								} else {
									Ext.getCmp('bc_usr_meta_field').store.removeAll();
								}
							}
						});
						//console.log(Ext.getCmp('bc_usr_meta_field_container').store.getAt(0));
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
					handler: function(btn, e) {
						this.buildAddTable(e);
					},
					scope: this
				},{
					text : '<span style="position:relative;top:1px;"><i class="fa fa-edit" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00043'),
					scale: 'medium',
					handler: function(btn, e) {
						var hasSelection = Ext.getCmp('bc_ud_content').getSelectionModel().hasSelection();
						var sm = Ext.getCmp('bc_ud_content').getSelectionModel();
						var sel = sm.getSelected();
						if(hasSelection) {

		   						 Ext.Ajax.request({
		   							 url: '/pages/menu/config/custom/user_metadata/php/edit_window.php',
		   							 params : {
		   							 	ud_content_id: sel.get('ud_content_id')
		   							 },
		   							 callback: function(opts, success, resp){
		   								 if (success)
		   								 {
		   									 Ext.decode(resp.responseText);
		   								 }
		   								 else
		   								 {
											Ext.Msg.alert( _text('MN01098'), resp.statusText);//'서버 오류'
		   								 }
		   							 }
		   						 });

							//this.buildEditTableWin(e,sel);
						}else{
							Ext.Msg.alert(_text('MN00022'), _text('MSG00169'));
						}
					},
					scope: this
				},{
					text : '<span style="position:relative;top:1px;"><i class="fa fa-trash" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00034'),
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
				},{
					text : '<span style="position:relative;top:1px;"><i class="fa fa-cog" style="font-size:13px;"></i></span>&nbsp;'+_text('MN02221'),
					scale: 'medium',
					handler: function(btn, e) {
						this.buildManageCategory(e);
					},
					scope: this
				}]
			});
		},

		buildAddTable: function(e){
			var win = new Ext.Window({
				id: 'add_table_win',
				border: false,
				layout: 'border',
				split: true,
				title: _text('MN00148'),
				width: 800,
				height: 440,
				modal: true,
				buttonAlign: 'center',
				items: [{
					id: 'add_table_form',
					cls: 'change_background_panel',
					xtype: 'form',
					//title: '콘텐츠 주요사항 설정',
					//title: _text('MN01006'),
					region: 'center',
					defaultType: 'textfield',
					padding: 10,
					width:500,
					defaults: {
						anchor: '100%',
						labelSeparator: ''
					},
					labelWidth : 170,
					items: [{
						name: 'ud_content_title',
						fieldLabel: _text('MN00273'),//콘텐츠 명
						msgTarget: 'under',
						//2015-12-16 콘텐츠 명 20자 이내로 수정
						autoCreate: {tag: 'input', type: 'text', autocomplete: 'off', maxlength: '20'},
						allowBlank: false
					},{
						name: 'ud_content_code',
						fieldLabel: _text('MN02153'),//'테이블명'
						msgTarget: 'under',
						regex: /^[A-Za-z0-9+]*$/,
						regexText: _text('MSG02074'),//Only number, alphabet can allow here.
						autoCreate: {tag: 'input', type: 'text', size: 4, autocomplete: 'off', maxlength: 16},
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
						fieldLabel: _text('MN00279'),
						typeAhead: true,
						triggerAction: 'all',
						forceSelection: true,
						editable: false,
						emptyText: _text('MSG00111')
					},{
						name: 'allowed_extension',
						fieldLabel: _text('MN00309')
					}
//					,
//					{
//						xtype: 'radiogroup',
//						fieldLabel: _text('MN00395'),
//						items: [
//							{boxLabel: _text('MN00393'), name: 'use_common_category', inputValue: 'Y'},
//							{boxLabel: _text('MN00394'), name: 'use_common_category', inputValue: 'N'}
//						]
//					}

					,{
						xtype: 'textarea',
						name: 'description',
						fieldLabel: _text('MN00049')
					},{
						name : 'content_expire_date',
						xtype: 'combo',
						store: new Ext.data.JsonStore({
							url: '/pages/menu/config/custom/user_metadata/php/get.php',
							baseParams: {
								action: 'content_del_date_list'
							},
							root: 'data',
							idProperty: 'id',
							fields: [
								{name: 'code', type: 'string'},
								{name: 'name', type: 'string'}
							]
						}),
						allowBlank: false,
						hiddenName: 'content_expire_date',
						valueField: 'code',
						displayField: 'name',
						fieldLabel: _text('MN02013'),//'만료 기한'
						typeAhead: true,
						triggerAction: 'all',
						forceSelection: true,
						editable: false
					},{
						fieldLabel : _text('MN02014'),//고해상도 스토리지
						id: 'add_highres_storage',
						hiddenName : 'highres',
						valueField: 'storage_id',
						displayField: 'name',
						xtype: 'combo',
						store: new Ext.data.JsonStore({
							url: '/pages/menu/config/custom/user_metadata/php/get.php',
							baseParams: {
								action: 'ud_storage_list'
							},
							autoLoad: true,
							root: 'data',
							idProperty: 'storage_id',
							fields: [
								{name: 'storage_id'},
								{name: 'name'}
							],
							listeners: {
								load: function(self){
								}
							}
						}),
						allowBlank: false,
						typeAhead: true,
						triggerAction: 'all',
						forceSelection: true,
						editable: false
					},{
						fieldLabel : _text('MN02015'),//저해상도 스토리지
						id: 'add_lowres_storage',
						hiddenName : 'lowres',
						valueField: 'storage_id',
						displayField: 'name',
						xtype: 'combo',
						store: new Ext.data.JsonStore({
							url: '/pages/menu/config/custom/user_metadata/php/get.php',
							baseParams: {
								action: 'ud_storage_list'
							},
							autoLoad: true,
							root: 'data',
							idProperty: 'storage_id',
							fields: [
								{name: 'storage_id'},
								{name: 'name'}
							],
							listeners: {
								load: function(self){
								}
							}
						}),
						allowBlank: false,
						typeAhead: true,
						triggerAction: 'all',
						forceSelection: true,
						editable: false

					},{
						fieldLabel : _text('MN02016'),//업로드 스토리지
						id: 'add_upload_storage',
						hiddenName : 'upload',
						valueField: 'storage_id',
						displayField: 'name',
						xtype: 'combo',
						store: new Ext.data.JsonStore({
							url: '/pages/menu/config/custom/user_metadata/php/get.php',
							baseParams: {
								action: 'ud_storage_list'
							},
							autoLoad: true,
							root: 'data',
							idProperty: 'storage_id',
							fields: [
								{name: 'storage_id'},
								{name: 'name'}
							],
							listeners: {
								load: function(self){
								}
							}
						}),
						allowBlank: false,
						typeAhead: true,
						triggerAction: 'all',
						forceSelection: true,
						editable: false
					},{
						fieldLabel : _text('MN01013'),//기본 카테고리
						id: 'add_category',
						hiddenName : 'category',
						valueField: 'category_id',
						displayField: 'category_title',
						xtype: 'combo',
						store: new Ext.data.JsonStore({
							url: '/pages/menu/config/custom/user_metadata/php/get.php',
							baseParams: {
								action: 'ud_category_list'
							},
							autoLoad: true,
							root: 'data',
							idProperty: 'category_id',
							fields: [
								{name: 'category_id'},
								{name: 'category_title'}
							],
							listeners: {
								load: function(self){
								}
							}
						}),
						allowBlank: false,
						typeAhead: true,
						triggerAction: 'all',
						forceSelection: true,
						editable: false
					}]
				},{
					region: 'east',
					id: 'add_table_form2',
					hidden : true,
					width : 1,
					title: '콘텐츠 폐기/삭제 기한  및 파일기한 설정',
					xtype: 'form',
					defaultType: 'textfield',
					padding : 10,
					autoScroll : true,
					items: [{
							xtype:'fieldset',
							title: '콘텐츠 삭제 기한',
							collapsible: false,
							items : [{

								 xtype : 'compositefield'
								 ,msgTarget: 'side'
								 ,fieldLabel: '콘텐츠 삭제 기한'
								 ,layout: {
									align: 'middle'
									,pack: 'center'
									,type: 'hbox'
								  }
								 ,items : [
										{
										name : 'contents_expire_date',
										width: 100,
										autoWidth: true,
										xtype: 'combo',
										store: new Ext.data.JsonStore({
											url: '/pages/menu/config/custom/user_metadata/php/get.php',
											baseParams: {
												action: 'contents_del_date_list'
											},
											root: 'data',
											idProperty: 'id',
											fields: [
												{name: 'code', type: 'string'},
												{name: 'name', type: 'string'}
											]
										}),

										hiddenName: 'contents_expire_date',
										valueField: 'code',
										displayField: 'name',
										fieldLabel: '삭제예정일',
										typeAhead: true,
										triggerAction: 'all',
										forceSelection: true,
										editable: false
									}
									]
								}
								]

							}

							<?=$file_del_str?>
						]
				}
				],
				buttons: [{
					text : '<span style="position:relative;top:1px;"><i class="fa fa-plus" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00033'),
					scale: 'medium',
					handler: function(btn, e) {

						//btn.disable();
						var form2_params = Ext.encode(Ext.getCmp('add_table_form2').getForm().getValues());
						Ext.getCmp('add_table_form').getForm().submit({
							url: '/pages/menu/config/custom/user_metadata/php/add.php',
							params: {
								action: 'add_table',
								expire : form2_params
							},
							success: function(form, action) {
								try {
									var result = Ext.decode(action.response.responseText, true);
									if(result.success) {
										Ext.getCmp('add_table_win').close();
										Ext.getCmp('bc_ud_content').store.reload();
										//Ext.getCmp('table_combo').store.reload();
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

		buildEditTableWin: function(e,sel) {
				 Ext.Ajax.request({
		   							 url: '/pages/menu/config/custom/user_metadata/php/edit_window.php',
		   							 params : {
		   							 	ud_content_id: sel.get('ud_content_id'),
		   							 	rec : sel
		   							 },
		   							 callback: function(opts, success, resp){
		   								 if (success)
		   								 {
		   									 Ext.decode(resp.responseText);
		   								 }
		   								 else
		   								 {
											Ext.Msg.alert( _text('MN01098'), resp.statusText);//'서버 오류'
		   								 }
		   							 }
		   						 });
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
										var sm = Ext.getCmp('bc_ud_content').getSelectionModel();
										var sel = sm.getSelected();
										var ud_content_id = sel.get('ud_content_id');
										Ext.getCmp('bc_ud_content').store.reload();
										if (id == rec.get('bs_content_id')) {
											c.getStore().reload();
											c.reset();
											Ext.getCmp('bc_usr_meta_field').getStore().removeAll();
										}
										Ext.getCmp('bc_usr_meta_field_container').store.removeAll();
										Ext.getCmp('bc_usr_meta_field').store.removeAll();
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

		buildManageCategory : function(e) {
			var win = new Ext.Window({
				title: _text('MN02221'),
				id: 'manage_category_win',
				width: 500,
				modal: true,
				height: 400,
				miniwin: true,
				resizable: false,
				layout: 'vbox',
				items: [
				{
					xtype: 'grid',
					cls: 'proxima_customize',
					stripeRows: true,
					autoScroll: true,
					height: 350,
					id: 'listing_category',
					store: new Ext.data.JsonStore({
						url: '/pages/menu/config/custom/user_metadata/php/get.php',
						root: 'data',
						baseParams: {
							action: 'manage_category_list'
						},
						fields: ['category_id','use_yn','ud_content_title','category_title']
					}),
					viewConfig: {
						loadMask: true,
						forceFit: true
					},
					columns: [
						new Ext.grid.RowNumberer(),
						{ 	header: _text('MN02223'),
							dataIndex: 'category_title',
							sortable:'false',
							xtype: 'gridcolumn',
							flex: 1,
							editor: {
								xtype: 'textfield'
							}
						}
						,{ header: _text('MN02227'), dataIndex: 'use_yn', sortable:'false' ,width:30 ,editor: { xtype: 'textarea', height: 100 }, flex: 1 }
						,{ header: _text('MN02226'), dataIndex: 'ud_content_title', sortable:'false'}

					],
					sm: new Ext.grid.RowSelectionModel({
					}),
					listeners: {
						afterrender: function(self){
							self.getStore().load();
						},
						rowdblclick: function(self, rowIndex, e){
							var sm = self.getSelectionModel().getSelected();
							var edit_category = new Ext.Window({
								width: 300,
								height: 120,
								modal: true,
								miniwin: true,
								resizable: false,
								title: _text('MN02222'),
								cls: 'change_background_panel',
								layout: 'fit',
								buttonAlign: 'center',
								buttons: [{
									text : '<span style="position:relative;top:1px;"><i class="fa fa-edit" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00043'),
									scale: 'medium',
									handler: function(b, e){
										var category_title_input_edit = Ext.getCmp('category_title_input_edit').getValue();
										var old_category_title = Ext.getCmp('old_category_title').getValue();

										Ext.Ajax.request({
											url: '/store/add_category.php',
											params: {
												action: "rename-folder",
												id: sm.get('category_id'),
												parent_id: 0,
												newName: category_title_input_edit,
												oldName: old_category_title
											},
											callback: function(opt, success, response){
												edit_category.close();
												self.getStore().reload();
											}
										});

									}
								},{
									text : '<span style="position:relative;top:1px;"><i class="fa fa-close" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00004'),
									scale: 'medium',
									handler: function(b, e){
										edit_category.close();
									}
								}],
								items: [{
									xtype: 'form',
									frame: true,
									items: [{
										xtype: 'textfield',
										allowBlank: false,
										fieldLabel: _text('MN02223'),
										id: 'category_title_input_edit',
										value: sm.get('category_title')
									},{
										xtype: 'hidden',
										allowBlank: false,
										fieldLabel: 'old category title',
										id: 'old_category_title',
										value: sm.get('category_title')
									}]
								}]
							});
							edit_category.show();
						},
						rowclick: function (self, rowIndex, e, colIndex, record){
						// disable add and edit function for default metadata field
							var sm = self.getSelectionModel().getSelected();
							var use_yn = sm.get('use_yn');
							if (use_yn == 'Y'){
								Ext.getCmp('btn_management_delete_category').disable();
							} else{
								Ext.getCmp('btn_management_delete_category').enable();
							}
						}
					}
				}
			],
			buttonAlign: 'center',
			fbar: [{
				text : '<span style="position:relative;top:1px;"><i class="fa fa-plus" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00033'), //add
				scale: 'medium',
				handler: function(b, e){
					var create_new_category =  new Ext.Window({
						title: _text('MN02225'),
						cls: 'change_background_panel',
						width: 300,
						modal: true,
						height: 120,
						miniwin: true,
						resizable: false,
						buttonAlign: 'center',
						layout: {
							type:'vbox',
							padding:'5',
							align:'stretch',
							pack : 'center'
						},
						buttons: [{
							text : '<span style="position:relative;top:1px;"><i class="fa fa-plus" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00033'),
							scale: 'medium',
							handler: function(b, e){
								var category_title_input = Ext.getCmp('category_title_input').getValue();

								Ext.Ajax.request({
									url: '/store/add_category.php',
									params: {
										action: "create-folder",
										parent_id: 0,
										title: category_title_input
									},
									callback: function(opt, success, response){
										create_new_category.close();
										Ext.getCmp('listing_category').getStore().reload();
									}
								});
							}
						},{
							text : '<span style="position:relative;top:1px;"><i class="fa fa-close" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00004'),
							scale: 'medium',
							handler: function(b, e){
								create_new_category.close();
							}
						}],
						items:[{
							xtype: 'form',
							frame : false,
							border : false,
							defaults: {
								anchor: '100%'
							},
							bodyStyle:{"background-color":"#f0f0f0"},
							items: [{
								xtype: 'textfield',
								allowBlank: false,
								fieldLabel: _text('MN02223'),
								id: 'category_title_input'
							}]
						}]
					}); // end create new tag window
					create_new_category.show();
				}
			},{
				action: 'rename-folder',
				text : '<span style="position:relative;top:1px;"><i class="fa fa-edit" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00043'), //edit
				scale: 'medium',
				handler: function(b, e){
					var hasSelection = Ext.getCmp('listing_category').getSelectionModel().hasSelection();

					if(hasSelection){
						var sm = Ext.getCmp('listing_category').getSelectionModel().getSelected();
						var edit_category = new Ext.Window({
							cls: 'change_background_panel',
							width: 300,
							height: 120,
							modal: true,
							miniwin: true,
							resizable: false,
							title: _text('MN02222'),
							buttonAlign: 'center',
							layout: {
								type:'vbox',
								padding:'5',
								align:'stretch',
								pack : 'center'
							},
							buttons: [{
								text : '<span style="position:relative;top:1px;"><i class="fa fa-edit" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00043'),
								scale: 'medium',
								handler: function(b, e){
									var category_title_input_edit = Ext.getCmp('category_title_input_edit2').getValue();
									var old_category_title = Ext.getCmp('old_category_title2').getValue();

									Ext.Ajax.request({
										url: '/store/add_category.php',
										params: {
											action: "rename-folder",
											action: "rename-folder",
											id: sm.get('category_id'),
											parent_id: 0,
											newName: category_title_input_edit,
											oldName: old_category_title
										},
										callback: function(opt, success, response){
											edit_category.close();
											Ext.getCmp('listing_category').getStore().reload();
										}
									});

								}
							},{
								text : '<span style="position:relative;top:1px;"><i class="fa fa-close" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00004'),
								scale: 'medium',
								handler: function(b, e){
									edit_category.close();
								}
							}],
							items: [{
								xtype: 'form',
								frame : false,
								border : false,
								defaults: {
									anchor: '100%'
								},
								bodyStyle:{"background-color":"#f0f0f0"},
								items: [{
									xtype: 'textfield',
									allowBlank: false,
									fieldLabel: _text('MN02223'),
									id: 'category_title_input_edit2',
									value: sm.get('category_title')
								},{
									xtype: 'hidden',
									allowBlank: false,
									fieldLabel: 'old category title',
									id: 'old_category_title2',
									value: sm.get('category_title')
								}]
							}]
						});
						edit_category.show();
					}else{
						Ext.Msg.alert(_text('MN00022'), _text('MSG02075'));
					}
				}
			},{
				scale: 'medium',
				text : '<span style="position:relative;top:1px;"><i class="fa fa-trash" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00034'), //delete
				id: 'btn_management_delete_category',
				handler: function(b, e){
					var hasSelection = Ext.getCmp('listing_category').getSelectionModel().hasSelection();
					if(hasSelection){
						var sm = Ext.getCmp('listing_category').getSelectionModel().getSelected();
						var category_title = sm.data.category_title;
						var use_yn = sm.data.use_yn;
						if(use_yn == 'N'){
							Ext.MessageBox.confirm(_text('MN01032'), _text('MSG00140')+' '+category_title+' ?', function(btn){
								if(btn === 'yes'){
									var category_id = sm.data.category_id;

									Ext.Ajax.request({
										url: '/store/add_category.php',
										params: {
											action: 'delete-root-folder',
											id: category_id,
											parent_id: 0
										},
										callback: function(opt, success, response){
											Ext.getCmp('listing_category').getStore().reload();
										}
									});
								}
							});
						} else {
							Ext.Msg.alert(_text('MN00022'), _text('MN00130'));
						}
					}else{
						Ext.Msg.alert(_text('MN00022'), _text('MSG02076'));
					}
				}
			},{
				text : '<span style="position:relative;top:1px;"><i class="fa fa-close" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00031'),
				scale: 'medium',
				handler: function(b, e){
					win.close();
				}
			}]

			});
			win.show();
		},

		buildContainer: function(){
			function _load_form_add_container(b, e) {
				if (b.mode == _text('MN00033')) {
					//var ud_content_id = Ext.getCmp('table_combo').getValue();
					var sm = Ext.getCmp('bc_ud_content').getSelectionModel();
					var sel = sm.getSelected();
					var ud_content_id = sel.get('ud_content_id');
						if (!ud_content_id) {
							//Select user defined content type.
							Ext.Msg.alert(_text('MN00022'), _text('MSG00111'));
							return;
						}
					var create_new_container =  new Ext.Window({
						title: _text('MN02305'),
						cls: 'change_background_panel',
						width: 300,
						modal: true,
						height: 120,
						miniwin: true,
						resizable: false,
						buttonAlign: 'center',
						layout: {
							type:'vbox',
							padding:'5',
							align:'stretch',
							pack : 'center'
						},
						buttons: [{
							text : '<span style="position:relative;top:1px;"><i class="fa fa-plus" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00033'),
							scale: 'medium',
							handler: function(b, e){
								var container_title_input = Ext.getCmp('container_title_input').getValue();
								Ext.Ajax.request({
									url: '/pages/menu/config/custom/user_metadata/php/add.php',
									params: {
										action: 'field',
										ud_content_id: ud_content_id,
										usr_meta_field_title: container_title_input,
										usr_meta_field_type: 'container',
										is_show: 1,
										is_required: 1,
										is_editable: 1,
										is_search_reg: 1
									},
									callback: function(opt, success, response){
										try {
											var r = Ext.decode(response.responseText, true);
											if (r.success) {
												create_new_container.close();
												Ext.getCmp('bc_usr_meta_field_container').getStore().reload();
											} else {
												Ext.Msg.alert(_text('MN00022'), r.msg);
											}
										} catch(e) {
											alert(_text('MN00022'), e.message + '(responseText: ' + response.responseText + ')');
										}
									}
								});
							}
						},{
							text : '<span style="position:relative;top:1px;"><i class="fa fa-close" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00004'),
							scale: 'medium',
							handler: function(b, e){
								create_new_container.close();
							}
						}],
						items:[{
							xtype: 'form',
							frame: false,
							border : false,
							bodyStyle:{"background-color":"#f0f0f0"},
							defaults: {
								anchor: '100%'
							},
							items: [{
								xtype: 'textfield',
								allowBlank: false,
								autoCreate: {tag: 'input', type: 'text', autocomplete: 'off', maxlength: '100'},
								fieldLabel: _text('MN02307'),
								id: 'container_title_input'
							}]
						}]
					}); // end create new container window
					create_new_container.show();
				} else {
					var hasSelection = Ext.getCmp('bc_usr_meta_field_container').getSelectionModel().hasSelection();
					var sm = Ext.getCmp('bc_usr_meta_field_container').getSelectionModel().getSelected();
					var content_sm = Ext.getCmp('bc_ud_content').getSelectionModel();
					var sel = content_sm.getSelected();
					var ud_content_id = sel.get('ud_content_id');
					if (!hasSelection) {
						Ext.Msg.alert(_text('MN00022'), _text('MSG00084'));
						return;
					}
					var create_edit_container =  new Ext.Window({
						title: _text('MN02306'),
						cls: 'change_background_panel',
						width: 300,
						modal: true,
						height: 120,
						miniwin: true,
						resizable: false,
						buttonAlign: 'center',
						layout: {
							type:'vbox',
							padding:'5',
							align:'stretch',
							pack : 'center'
						},
						buttons: [{
							text : '<span style="position:relative;top:1px;"><i class="fa fa-edit" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00043'),
							scale: 'medium',
							handler: function(b, e){
								var container_title_input = Ext.getCmp('container_title_input_edit').getValue();
								var container_meta_field_id = Ext.getCmp('container_meta_field_id').getValue();
								Ext.Ajax.request({
									url: '/pages/menu/config/custom/user_metadata/php/edit.php',
									params: {
										action: 'field',
										ud_content_id: ud_content_id,
										usr_meta_field_title: container_title_input,
										usr_meta_field_type: 'container',
										usr_meta_field_id : container_meta_field_id,
										is_show: 1,
										is_required: 1,
										is_editable: 1,
										is_search_reg: 1
									},
									callback: function(opt, success, response){
										try {
											var r = Ext.decode(response.responseText, true);
											if (r.success) {
												create_edit_container.close();
												Ext.getCmp('bc_usr_meta_field_container').getStore().reload();
											} else {
												Ext.Msg.alert(_text('MN00022'), r.msg);
											}
										} catch(e) {
											alert(_text('MN00022'), e.message + '(responseText: ' + response.responseText + ')');
										}
									}
								});
							}
						},{
							text : '<span style="position:relative;top:1px;"><i class="fa fa-close" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00004'),
							scale: 'medium',
							handler: function(b, e){
								create_edit_container.close();
							}
						}],
						items:[{
								xtype: 'form',
								frame: false,
								border : false,
								bodyStyle:{"background-color":"#f0f0f0"},
								defaults: {
									anchor: '100%'
								},
								items: [{
									xtype: 'textfield',
									allowBlank: false,
									fieldLabel: _text('MN02307'),
									id: 'container_title_input_edit',
									value: sm.get('usr_meta_field_title')
								},{
									xtype: 'hidden',
									allowBlank: false,
									fieldLabel: 'old category title',
									id: 'old_category_title2',
									value: sm.get('usr_meta_field_title')
								},{
									xtype: 'hidden',
									allowBlank: false,
									fieldLabel: 'container_meta_field_id',
									id: 'container_meta_field_id',
									value: sm.get('container_id')
								}]
							}]
					}); // end create new container window
					create_edit_container.show();
				}
			}

			function _delete_container(btn, e){
				var sm = Ext.getCmp('bc_usr_meta_field_container').getSelectionModel();
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

				var content_sm = Ext.getCmp('bc_ud_content').getSelectionModel();
				var sel = content_sm.getSelected();
				var ud_content_id = sel.get('ud_content_id');

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
							//var container_meta_field_id = Ext.getCmp('container_meta_field_id').getValue();
							var sm = Ext.getCmp('bc_usr_meta_field_container').getSelectionModel();
							var recs = sm.getSelections();
							var container_meta_field_id = recs[0].get('container_id');
							Ext.Ajax.request({
								url: '/pages/menu/config/custom/user_metadata/php/del.php',
								params: {
									action: 'delete_container_field',
									ud_content_id: ud_content_id,
									usr_meta_field_id_list : container_meta_field_id
								},
								callback: function(opts, success, response) {
									try {
										var r = Ext.decode(response.responseText, true);
										if (r.success) {
											Ext.getCmp('bc_usr_meta_field_container').getStore().reload();
											Ext.getCmp('bc_usr_meta_field').getStore().removeAll();
											//Ext.getCmp('bc_usr_meta_field').getStore().sync();
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

			function _save_order(grid){
				var srcGrid	 	= grid;
				var destStore   = grid.store;
				var table		= 'bc_usr_meta_field';
				var id_field	= 'usr_meta_field_id';

				var srcGridStore = srcGrid.store;
				var idx = 1;
				var p = new Array();
				srcGridStore.each(function(r){
					var is_checked = r.get('is_default'); // check for default items in user metadata
					if (is_checked != 1){
						p.push({
							table: table,
							id_field: id_field,
							id_value: r.get(id_field),
							sort: idx++
						});
					}
				});

				Ext.Msg.show({
					title: _text('MN00024'),
					icon: Ext.Msg.INFO,
					msg: _text('MSG02077'),
					buttons: Ext.Msg.OKCANCEL,
					fn: function(btnID, text, opt) {
						if(btnID == 'ok') {
							Ext.Ajax.request({
								url: '/pages/menu/config/custom/user_metadata/php/edit.php',
								params: {
									action: 'sort_field',
									records: Ext.encode(p)
								},
								callback: function(opts, success, response){
									try {
										var r = Ext.decode(response.responseText, true);
										if (r.success) {
											Ext.getCmp('bc_usr_meta_field_container').getStore().reload();
										} else {
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

			function _auto_save_order(grid){
				var srcGrid	 	= grid;
				var destStore   = grid.store;
				var table		= 'bc_usr_meta_field';
				var id_field	= 'usr_meta_field_id';

				var srcGridStore = srcGrid.store;
				var idx = 1;
				var p = new Array();
				srcGridStore.each(function(r){
					//var is_checked = r.get('is_default'); // check for default items in user metadata
					p.push({
						table: table,
						id_field: id_field,
						id_value: r.get(id_field),
						sort: idx++
					});
				});

				Ext.Ajax.request({
					url: '/pages/menu/config/custom/user_metadata/php/edit.php',
					params: {
						action: 'sort_field',
						records: Ext.encode(p)
					},
					callback: function(opts, success, response){
						try {
							var r = Ext.decode(response.responseText, true);
							if (r.success) {
								//Ext.getCmp('bc_usr_meta_field_container').getStore().reload();
							} else {
								Ext.Msg.alert(_text('MN00022'), r.msg);
							}
						}catch(e) {
							alert(e.message + '(responseText: ' + response.responseText + ')');
						}
					}
				})
			}

			return new Ext.grid.GridPanel({
				id: 'bc_usr_meta_field_container',
				//cls: 'proxima_customize',
				//$$ MN00198 사용자 정의 콘텐츠 구성
				//title: _text('MN02233'),
				title: '<span class="user_span"><span class="icon_title"><i class=""></i></span><span class="main_title_header">'+_text('MN02233')+'</span></span>',
				cls: 'grid_title_customize proxima_customize',
				stripeRows: true,
				border: false,
				region: 'west',
				loadMask: true,
				width: 350,
				enableDragDrop: true,
				ddGroup: 'tableGridDD',
				store: new Ext.data.JsonStore({
					url: '/pages/menu/config/custom/user_metadata/php/get.php',
					root: 'data',
					idProperty: 'usr_meta_field_id1',
					// 2010-11-08 container_id, container_name 추가 (컨테이너 추가 by CONOZ)
					fields: [
						'usr_meta_field_title',
						'container_id',
						'usr_meta_field_id',
						'is_default'
					]
				}),
				colModel: new Ext.grid.ColumnModel({
					defaults: {
						align: 'center'
					},
					// 2010-11-08 container_name 추가 (컨테이너 추가 by CONOZ)
					columns: [
						{header: _text('MN00272'),		dataIndex: 'usr_meta_field_title'}
					]
				}),
				viewConfig: {
					emptyText: _text('MSG00148'),
					forceFit: true,
					getRowClass: function(record, idx, rp, ds){
						if ( record.get('usr_meta_field_type') == 'container' )
						{
							//return 'user-custom-container';
							//console.log( rp );
						}
						if (record.get('is_default') == 1 ){
							return 'disabled-row';
						}else {
							return 'dragable-dropable';
						}
					}
				},
				listeners: {
					viewready: function(self) {
						var downGridDroptgtCfg = Ext.apply({}, dropZoneOverridesShowOrder, {
							table: 'bc_usr_meta_field',
							id_field: 'container_id',
							ddGroup: 'tableGridDD',
							grid : Ext.getCmp('bc_usr_meta_field_container')
						});
						new Ext.dd.DropZone(Ext.getCmp('bc_usr_meta_field_container').getEl(), downGridDroptgtCfg);
					},
					rowclick: function (self, rowIndex, e, colIndex, record){
						var store = self.getStore();
						var container_id = store.getAt(rowIndex).get('container_id');
						//var ud_content_id = Ext.getCmp('table_combo').getValue();
						var sm = Ext.getCmp('bc_ud_content').getSelectionModel();
						var sel = sm.getSelected();
						var ud_content_id = sel.get('ud_content_id');
						Ext.getCmp('bc_usr_meta_field').store.load({
							params: {
								action: 'table_field',
								ud_content_id: ud_content_id, // need change to container id
								container_id: container_id
							}
						});

						var is_default_value = store.getAt(rowIndex).get('is_default');
						if (is_default_value == 1){
							Ext.getCmp('btn_delete_metadata_container').disable();
							Ext.getCmp('btn_change_order_container_metadata_top').disable();
							Ext.getCmp('btn_change_order_container_metadata_up').disable();
							Ext.getCmp('btn_change_order_container_metadata_down').disable();
							Ext.getCmp('btn_change_order_container_metadata_bottom').disable();
						}  else{
							Ext.getCmp('btn_delete_metadata_container').enable();
							Ext.getCmp('btn_change_order_container_metadata_top').enable();
							Ext.getCmp('btn_change_order_container_metadata_up').enable();
							Ext.getCmp('btn_change_order_container_metadata_down').enable();
							Ext.getCmp('btn_change_order_container_metadata_bottom').enable();
						}
					}
				},
				/*
				tbar: [_text('MN00275'), {
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
							Ext.getCmp('bc_usr_meta_field_container').store.load({
								params: {
									action: 'container_list',
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
				}],
				*/
				tbar: [{
					text: '<span style="position:relative;" title="'+_text('MN00139')+'"><i class="fa fa-refresh" style="font-size:11px;color:white;"></i></span>',
					//text: _text('MN00139'),
					cls: 'proxima_button_customize',
					width: 30,
					handler: function(btn, e){
						Ext.getCmp('bc_usr_meta_field_container').store.reload();
					}
				},{
				   	xtype: 'button',
				   	cls: 'proxima_button_customize',
					text: "<i class='fa fa-angle-double-up fa-2x' style=\"font-size:16px;color:white;\" title='"+_text('MN02229')+"'></i>",
					id: "btn_change_order_container_metadata_top",
					width: 30,
					handler: function(b, e){
						var grid = Ext.getCmp('bc_usr_meta_field_container');
						var start_index = 0;
						grid.store.each(function(r){
							var is_default = r.get('is_default');
							if (is_default == 1){
								start_index ++;
							}
						});
						moveSelectedRow(grid, 0, 'top', start_index);
						//Ext.getCmp('save_order_button').enable();
						_auto_save_order(grid);
					}
				},{
					xtype: 'button',
					cls: 'proxima_button_customize',
					text: "<i class='fa fa-angle-up fa-2x' style=\"font-size:16px;color:white;\" title='"+_text('MN02230')+"'></i>",
					id: "btn_change_order_container_metadata_up",
					width: 30,
					handler: function(b, e){
						var grid = Ext.getCmp('bc_usr_meta_field_container');
						var start_index = 0;
						grid.store.each(function(r){
							var is_default = r.get('is_default');
							if (is_default == 1){
								start_index ++;
							}
						});
						moveSelectedRow(grid, -1, 'up', start_index);
						//Ext.getCmp('save_order_button').enable();
						_auto_save_order(grid);
					}

				},{
					xtype: 'button',
					cls: 'proxima_button_customize',
					text: "<i class='fa fa-angle-down fa-2x' style=\"font-size:16px;color:white;\" title='"+_text('MN02231')+"'></i>",
					id: "btn_change_order_container_metadata_down",
					width: 30,
					handler: function(b, e){
						var grid = Ext.getCmp('bc_usr_meta_field_container');
						var start_index = 0;
						grid.store.each(function(r){
							var is_default = r.get('is_default');
							if (is_default == 1){
								start_index ++;
							}
						});
						moveSelectedRow(grid, +1, 'down', start_index);
						//Ext.getCmp('save_order_button').enable();
						_auto_save_order(grid);
					}

				},{
					xtype: 'button',
					cls: 'proxima_button_customize',
					text: "<i class='fa fa-angle-double-down fa-2x' style=\"font-size:16px;color:white;\" title='"+_text('MN02232')+"'></i>",
					id: "btn_change_order_container_metadata_bottom",
					width: 30,
					handler: function(b, e){
						var grid = Ext.getCmp('bc_usr_meta_field_container');
						var start_index = 0;
						grid.store.each(function(r){
							var is_default = r.get('is_default');
							if (is_default == 1){
								start_index ++;
							}
						});
						moveSelectedRow(grid, 0, 'bottom', start_index);
						//Ext.getCmp('save_order_button').enable();
						_auto_save_order(grid);
					}

				}],

				buttonAlign: 'center',
				fbar: [{
					url: 'add.php',
					//text: _text('MN00033'),
					text : '<span style="position:relative;top:1px;"><i class="fa fa-plus" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00033'),
					mode: _text('MN00033'),
					scale: 'medium',
					id: 'btn_add_metadata_container',
					handler: _load_form_add_container
				},{
					url: 'edit.php',
					//text: _text('MN00043'),//수정
					text : '<span style="position:relative;top:1px;"><i class="fa fa-edit" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00043'),
					mode: _text('MN00043'),
					scale: 'medium',
					id: 'btn_edit_metadata_container',
					handler: _load_form_add_container
				},{
					text: _text('MN02228'),
					//id : 'save_order_button1',
					//disabled: true,
					hidden: true,
					scale: 'medium',
					handler: function(b, e){
						var grid		= Ext.getCmp('bc_usr_meta_field_container');
						_save_order(grid);
					}
				},{
					text : '<span style="position:relative;top:1px;"><i class="fa fa-trash" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00034'),
					scale: 'medium',
					id: 'btn_delete_metadata_container',
					handler: _delete_container
				}]
			});
		},

		buildField: function(){
			function _renderType(v){
				switch (v) {
					case 'textfield':
						//>>return '한줄 입력';
						return _text('MN00350');

					case 'textarea':
						//>>return '여러줄 입력';
						return _text('MN00351');

					case 'combo':
						//>>return '콤보박스';
						return _text('MN00358');

					case 'checkbox':
						//>>return '체크박스';
						return _text('MN00353');

					case 'datefield':
						//>>return '날짜';
						return _text('MN00354');

					case 'numberfield':
						//>>return '숫자';
						return _text('MN00355');

					// 2010-11-08 추가 (컨테이너 추가 by CONOZ)
					case 'container':
						//>>return '컨테이너';
						return _text('MN00272');

					case 'listview':
						//>>return '테이블(표)';
						return _text('MN00352');

					case 'compositefield':
						return '합성필드';


					<?php 
					if(defined('CUSTOM_ROOT') && class_exists('\ProximaCustom\core\MetadataFieldManager')) {
						$metaFieldsCase = \ProximaCustom\core\MetadataFieldManager::getCustomMetadataFieldNameByTypeCase();
						echo $metaFieldsCase;									
					} 
					?>
				}
				return '';
			}


			function _renderSummaryCd(v) {
				switch (v) {

					case '1':
						return _text('MN00060');//'미리 보기'
					break;

					case '2':
						return _text('MN00062');//'요약보기';
					break;

					case '3':
						return _text('MN00060')+' / '+_text('MN00062');//'미리 보기 / 요약보기';
					break;

					case '4':
						return _text('MN00061');//'리스트 보기';
					break;

					case '5':
						return _text('MN00060')+' / '+_text('MN00061');//'미리 보기 / 리스트 보기';
					break;

					case '6':
						return _text('MN00062')+' / '+_text('MN00061');//'요약보기 / 리스트 보기';
					break;

					case '7':
						return _text('MN00060')+' / '+_text('MN00062')+' / '+_text('MN00061');//'미리 보기 / 요약보기 / 리스트 보기';
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

			function in_array(search, array)
			{
				for (i = 0; i < array.length; i++)
				{
					if(array[i] ==search )
					{
						return true;
					}
				}
				return false;
			}

			function submit_meta(url){
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

			function _submit(url) {
				var form_values = Ext.getCmp('field_form').getForm().getValues();
				if( form_values.usr_meta_field_type == 'combo' ){
					var d_value = form_values.default_value;
					var array_values = d_value.split("(default)");

					if( Ext.isEmpty(d_value) || d_value.indexOf('(default)') == -1 || ( d_value.indexOf('(default)') != 0 && ( array_values.length < 2 || !in_array(array_values.shift(), array_values[0].split(';')) ) )){//빈값 || (default) 부재 || ( 기본값이 빈값 아님 &&  (기본값만 입력됨 || 값에 없는 기본값 입력) )
						Ext.Msg.alert( _text('MN00023'), _text('MSG02026') );//알림, 기본값을 확인하세요.
						return;
					}else{
						//if( d_value.indexOf('(default)') == 0 )//기본값 부재
						submit_meta(url);
					}
				}else{
					submit_meta(url);
				}
			}

			// 메타데이터 입력/수정 폼
			function _buildForm(btn){
				var sm = Ext.getCmp('bc_ud_content').getSelectionModel();
				var sel = sm.getSelected();
				var ud_content_id = sel.get('ud_content_id');
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
						xtype: 'hidden',
						allowBlank: false,
						name: 'container_id',
						id: 'container_combo',
						value: Ext.getCmp('bc_usr_meta_field_container').getSelectionModel().getSelected().get('container_id')
					},{
						name: 'usr_meta_field_title',
						fieldLabel: _text('MN00308'),
						msgTarget: 'under',
						allowBlank: false,
						//2015-12-16 메타데이터 항목 명 10자 이내로 수정
						autoCreate: {tag: 'input', type: 'text', autocomplete: 'off', maxlength: '100'},
						listeners: {
							render: function(self){
								self.focus(true, 500);
							}
						}
					},{
						name: 'usr_meta_field_code',
						//fieldLabel: '필드명',
						fieldLabel: _text('MN02154'),
						msgTarget: 'under',
						allowBlank: false,
						regex: /^[A-Za-z0-9_+]*$/,
						regexText: _text('MSG02074'),//Only number, alphabet can allow here.
						autoCreate: {tag: 'input', type: 'text', autocomplete: 'off', maxlength: '20'},
						listeners: {
							render: function(self){
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
								['numberfield',	_text('MN00355')]
								<?php 
								if(defined('CUSTOM_ROOT') && class_exists('\ProximaCustom\core\MetadataFieldManager')) {
									$metaFields = \ProximaCustom\core\MetadataFieldManager::getCustomMetadataFields();
									echo $metaFields;									
								} 
								?>
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
						id: 'usr_meta_field_type',
						fieldLabel: _text('MN00228'),
						allowBlank: false,

						listeners: {
							change: function( self, newValue, oldValue ){
							},
							afterrender: function(combo){
								//var codefield = combo.ownerCt.getForm().findField('usr_meta_field_type');
								//var xxx = combo.store.getAt(0).data.usr_meta_field_type;
								//alert(combo.getValue());
								//alert(codefield.getValue());
								//alert(xxx);
								//alert(combo.getState());
							},
							select: function( combo, record, index ){
								var container_id = combo.ownerCt.getForm().findField('container_id');
								var codefield = combo.ownerCt.getForm().findField('usr_meta_field_code');
								var default_value = combo.ownerCt.getForm().findField('default_value');
								var summary_field_cd = combo.ownerCt.getForm().findField('summary_field_cd');
								var text_guide = combo.ownerCt.getForm().findField('text_guide');
								var is_required = combo.ownerCt.getForm().findField('is_required');
								var is_editable = combo.ownerCt.getForm().findField('is_editable');
								var is_show = combo.ownerCt.getForm().findField('is_show');
								var is_search_reg = combo.ownerCt.getForm().findField('is_search_reg');
								if(combo.getValue() == 'container'){
									//codefield.setValue(' ');
									default_value.setValue('');
									//codefield.setDisabled(true);
									codefield.hide();
									container_id.reset();
									container_id.hide();
									default_value.hide();
									summary_field_cd.items.each( function(k){
												k.setValue(false);
											});
									summary_field_cd.hide();
									text_guide.hide();
									is_required.setValue(false);
									is_required.hide();
									is_editable.setValue(false);
									is_editable.hide();
									is_show.setValue(false);
									is_show.hide();
									is_search_reg.setValue(false);
									is_search_reg.hide();
								}else{
									//codefield.setValue(' ');
									//codefield.setDisabled(false);
									codefield.show();
									container_id.show();
									default_value.show();
									summary_field_cd.show();
									text_guide.show();
									is_required.show();
									is_editable.show();
									is_show.show();
									is_search_reg.show();
									
									var comboValue = combo.getValue();
									var number_of_line = Ext.getCmp('number_of_line');
									if(comboValue == 'textfield' || comboValue == 'textarea'){
										number_of_line.item_type = true;
										if(number_of_line.summary_view){
											number_of_line.setVisible(true);
										}
									}else{
										number_of_line.item_type = false;
										number_of_line.setVisible(false);
									}
								}
							}
						}
					},{
						xtype: 'checkbox',
						name: 'is_required',
						fieldLabel: _text('MN00305'),
						inputValue: 1,
						checked: true
					},{
						xtype: 'checkbox',
						name: 'is_editable',
						fieldLabel: _text('MN00053'), //2011.11.08 김형기 MN00054(제거)->MN00053(수정허용) 변경
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
						xtype: 'checkbox',
						name: 'is_social',
						fieldLabel: _text('MN02308'),
						inputValue: 1,
						checked: false,
						hidden:  <?php echo $arr_sys_code['interwork_sns']['use_yn'] == 'Y'?'false':'true'?>
					},{
						xtype: 'checkboxgroup',
						name: 'summary_field_cd',
						//fieldLabel: '보기모드 지정',
						fieldLabel: _text('MN02022'),
						autoScroll: true,
						columns: 1,
						border: true,
						items: [
						<?php
						//$metass = $db->queryAll("select c.code, c.name from bc_code c, bc_code_type ct where ct.id=c.code_type_id and ct.code='main_summary_field' ");//main_summary_field/main_summary_field_eng

                        $metas = getCodeInfoLang($_SESSION['user']['lang'],'main_summary_field');//'CONTENT_STATUS
                        
                        $mode_items = array();
                        foreach($metas as $meta) {
                            if($meta['code'] == 1) continue;//미리(썸네일)보기 제외
                            $mode_items[] = "{boxLabel: '{$meta['name']}', name: 'summary_field_cd-{$meta['code']}' }";
                        }
                        echo implode(',', $mode_items);

						// while ($meta = current($metas))
						// {
                            
						// 	if(key($metas) == 0)
						// 	{
						// 		//'전체 선택'
						// 		echo "{boxLabel: _text('MN02023'), name: 'summary_field_cd-0'
						// 			,listeners: {
						// 				check: function( self, checked  ){
						// 					self.ownerCt.items.each( function(k){
						// 						k.setValue( checked  );
						// 					});
						// 				}
						// 			}
						// 		},\n";
						// 	}
						// 	if($meta['name'] != _text('MN00060')){
						// 		if($meta['code'] == 2){
						// 			echo " {
						// 						xtype: 'container',
						// 						layout: 'column',
						// 						columns: 2,
						// 						items:[{
						// 							xtype: 'checkbox',
						// 							width: 100,
						// 							boxLabel: '{$meta['name']}',
						// 							name: 'summary_field_cd-{$meta['code']}',
						// 							listeners: {
						// 								check: function( self, checked  ){
						// 									var number_of_line = Ext.getCmp('number_of_line');
						// 									var usr_meta_field_type = Ext.getCmp('usr_meta_field_type').getValue();
															
						// 									if(usr_meta_field_type == 'textfield' || usr_meta_field_type == 'textarea'){
						// 										number_of_line.item_type = true;	
						// 									}else{
						// 										number_of_line.item_type = false;
						// 									}
						// 									if(checked){
						// 										number_of_line.summary_view = true;
						// 										if(number_of_line.item_type){
						// 											number_of_line.setVisible(true);
						// 										}
						// 									}else{
						// 										number_of_line.summary_view = false;
						// 										number_of_line.setVisible(false);
						// 									}
						// 								}
						// 							}
						// 						},{
						// 							xtype: 'combo',
						// 							width: 80,
						// 							name: 'num_line',
						// 							id: 'number_of_line',
						// 							summary_view: false,
						// 							item_type: true,
						// 							hidden: true,
						// 							store: new Ext.data.ArrayStore({
						// 								fields: [ 'num_line', 'num_line_txt' ],
						// 								data: [
						// 									[1,	1],
						// 									[2,	2],
						// 									[3,	3]
						// 								]
						// 							}),
						// 							mode: 'local',
						// 							triggerAction: 'all',
						// 							hiddenName: 'num_line',
						// 							displayField: 'num_line_txt',
						// 							valueField: 'num_line',
						// 							value: 1,
						// 							editable: false
						// 						}]
						// 				   }\n";
						// 		}else{
						// 			echo "{boxLabel: '{$meta['name']}', name: 'summary_field_cd-{$meta['code']}' }\n";
						// 		}
								

						// 	}else{
						// 		echo "{boxLabel: '{$meta['name']}', hidden:true, name: 'summary_field_cd-{$meta['code']}' }\n";								
						// 	}	
						// 	if (next($metas))
						// 	{
						// 		echo ',';
						// 	}
						// }
						?>
						]
					},{
						xtype: 'textarea',
						name: 'default_value',
						height: 40,
						autoScroll: true,
						maxLength: 1000,
						fieldLabel: _text('MN00155')
					},{
						xtype : 'displayfield',
						name: 'text_guide',
						//value : 'ex)기본값(default)첫번째;두번째;세번째'
						value : _text('MN02155')
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
					btn_text = '<span style="position:relative;top:1px;"><i class="fa fa-plus" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00033');
				}else{
					btn_text = '<span style="position:relative;top:1px;"><i class="fa fa-edit" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00043');
				}

				return new Ext.Window({
					id: 'field_win',
					cls: 'change_background_panel',
					layout: 'fit',
					title: _text('MN00164') + ' ' + btn.mode,
					width: 400,
					height: 530,
					padding: 10,
					modal: true,
					resizable: false,
					items: _buildForm(btn),
					buttonAlign: 'center',
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
					//var ud_content_id = Ext.getCmp('table_combo').getValue();
					var sm = Ext.getCmp('bc_ud_content').getSelectionModel();
					var sel = sm.getSelected();
					var ud_content_id = sel.get('ud_content_id');
					if (!ud_content_id) {
						//Select user defined content type.
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

						var form = this.get(0).getForm();

						if( !Ext.isEmpty(this.get(0).getForm().findField('summary_field_cd')) )
						{
							var summary = this.get(0).getForm().findField('summary_field_cd');
							summary.items.each( function(i){
								var nameArray = i.getName().split("-");

								if(nameArray[1] != '0' && parseInt(nameArray[1]) & parseInt( sm.getSelected().get('summary_field_cd') ) )
								{
									summary.setValue( i.getName(), true  );
								}
							});
						}
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

				var ud_content_id = recs[0].get('ud_content_id');

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
									ud_content_id: ud_content_id,
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

			function _save_order(grid){
				var srcGrid	 	= grid;
				var destStore   = grid.store;
				var table		= 'bc_usr_meta_field';
				var id_field	= 'usr_meta_field_id';

				var srcGridStore = srcGrid.store;
				var idx = 1;
				var p = new Array();
				srcGridStore.each(function(r){
					var is_checked = r.get('is_default'); // check for default items in user metadata
					if (is_checked != 1){
						p.push({
							table: table,
							id_field: id_field,
							id_value: r.get(id_field),
							sort: idx++
						});
					}
				});

				Ext.Msg.show({
					title: _text('MN00024'),
					icon: Ext.Msg.INFO,
					msg: _text('MSG02077'),
					buttons: Ext.Msg.OKCANCEL,
					fn: function(btnID, text, opt) {
						if(btnID == 'ok') {
							Ext.Ajax.request({
								url: '/pages/menu/config/custom/user_metadata/php/edit.php',
								params: {
									action: 'sort_field',
									records: Ext.encode(p)
								},
								callback: function(opts, success, response){
									try {
										var r = Ext.decode(response.responseText, true);
										if (r.success) {
											Ext.getCmp('bc_usr_meta_field').getStore().reload();
										} else {
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

			function _auto_save_order(grid){
				var srcGrid	 	= grid;
				var destStore   = grid.store;
				var table		= 'bc_usr_meta_field';
				var id_field	= 'usr_meta_field_id';

				var srcGridStore = srcGrid.store;
				var idx = 1;
				var p = new Array();
				srcGridStore.each(function(r){
					var is_checked = r.get('is_default'); // check for default items in user metadata
					if (is_checked != 1){
						p.push({
							table: table,
							id_field: id_field,
							id_value: r.get(id_field),
							sort: idx++
						});
					}
				});

				Ext.Ajax.request({
					url: '/pages/menu/config/custom/user_metadata/php/edit.php',
					params: {
						action: 'sort_field',
						records: Ext.encode(p)
					},
					callback: function(opts, success, response){
						try {
							var r = Ext.decode(response.responseText, true);
							if (r.success) {
								//Ext.getCmp('bc_usr_meta_field').getStore().reload();
							} else {
								Ext.Msg.alert(_text('MN00022'), r.msg);
							}
						}catch(e) {
							alert(e.message + '(responseText: ' + response.responseText + ')');
						}
					}
				})
			}

			return new Ext.grid.GridPanel({
				//title: _text('MN00164'),
				id: 'bc_usr_meta_field',
				title: '<span class="user_span"><span class="icon_title"><i class=""></i></span><span class="main_title_header">'+_text('MN00164')+'</span></span>',
				cls: 'grid_title_customize proxima_customize',
				stripeRows: true,
				border: false,
				region: 'center',
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
						'is_search_reg',
						'is_social',
						'num_line',
						'summary_field_cd',
						'usr_meta_field_code',
						'is_default'
					]
				}),
				colModel: new Ext.grid.ColumnModel({
					defaults: {
						align: 'center'
					},
					// 2010-11-08 container_name 추가 (컨테이너 추가 by CONOZ)
					columns: [
						{header: _text('MN00308'),	dataIndex: 'usr_meta_field_title'},
						{header: _text('MN02154'),	dataIndex: 'usr_meta_field_code'},//필드명
						{header: _text('MN00272'),	dataIndex: 'container_name'},
						{header: _text('MN00228'),	dataIndex: 'usr_meta_field_type',			renderer: _renderType},
						{header: _text('MN00305'),  dataIndex: 'is_required',	renderer: _renderCheck},
						{header: _text('MN00053'),	dataIndex: 'is_editable',		renderer: _renderCheck}, //2011.11.08 김형기 MN00054(제거)->MN00053(수정허용) 변경
						{header: _text('MN00040'),	dataIndex: 'is_show',		renderer: _renderCheck},
						{header: _text('MN00114'), 	dataIndex: 'is_search_reg' ,	renderer: _renderCheck},
						{header: _text('MN02308'), 	dataIndex: 'is_social' ,	renderer: _renderCheck},
						{header: _text('MN02022'), 	dataIndex: 'summary_field_cd' ,	renderer: _renderSummaryCd },//보기모드 지정
						{header: _text('MN00155'), 	dataIndex: 'default_value'},
						{header: 'is_default', 		dataIndex: 'is_default', hidden: true, enableColumnHide: false ,hideable : false,sortable : false, draggable : false}
					]
				}),
				viewConfig: {
					emptyText: _text('MSG00148'),
					forceFit: true,
					getRowClass: function(record, idx, rp, ds){
						if ( record.get('usr_meta_field_type') == 'container' )
						{
							//return 'user-custom-container';
							//console.log( rp );
						}
						if (record.get('is_default') == 1 ){
							return 'disabled-row';
						} else {
							return 'dragable-dropable';
						}
					}
				},
				listeners: {

					viewready: function(self) {
						var downGridDroptgtCfg = Ext.apply({}, dropZoneOverridesShowOrder, {
							table: 'bc_usr_meta_field',
							id_field: 'usr_meta_field_id',
							ddGroup: 'fieldGridDD',
							grid : Ext.getCmp('bc_usr_meta_field')
						});
						new Ext.dd.DropZone(Ext.getCmp('bc_usr_meta_field').getEl(), downGridDroptgtCfg);
					},
					rowdblclick: function(self, rowIndex, e) {
						// disable view function for default metadata field
						var store = self.getStore();
						var is_default_value = store.getAt(rowIndex).get('is_default');
						if (is_default_value != 1){
							_load_form({text: _text('MN00043'), url: 'edit.php', mode: _text('MN00043'), getId: function(){return e.getTarget()}}, null);
						}
					},
					rowclick: function (self, rowIndex, e, colIndex, record){
					// disable add and edit function for default metadata field
						var store = self.getStore();
						var is_default_value = store.getAt(rowIndex).get('is_default');
						var usr_meta_field_type = store.getAt(rowIndex).get('usr_meta_field_type');
						if (is_default_value == 1){
							Ext.getCmp('btn_edit_metadata_field').disable();
							Ext.getCmp('btn_delete_metadata_field').disable();
							Ext.getCmp('btn_change_order_top').disable();
							Ext.getCmp('btn_change_order_up').disable();
							Ext.getCmp('btn_change_order_down').disable();
							Ext.getCmp('btn_change_order_bottom').disable();
						}  else{
							Ext.getCmp('btn_edit_metadata_field').enable();
							Ext.getCmp('btn_delete_metadata_field').enable();
							Ext.getCmp('btn_change_order_top').enable();
							Ext.getCmp('btn_change_order_up').enable();
							Ext.getCmp('btn_change_order_down').enable();
							Ext.getCmp('btn_change_order_bottom').enable();
						}
					}
				},
				tbar: [{
					text: '<span style="position:relative;" title="'+_text('MN00139')+'"><i class="fa fa-refresh" style=\"font-size:11px;color:white;\"></i></span>',
					//text: _text('MN00139'),
					width: 30,
					cls: 'proxima_button_customize',
					handler: function(btn, e){
						Ext.getCmp('bc_usr_meta_field').store.reload();
					}
				},{
				   	xtype: 'button',
				   	cls: 'proxima_button_customize',
					text: "<i class='fa fa-angle-double-up fa-2x' style=\"font-size:16px;color:white;\" title='"+_text('MN02229')+"'></i>",
					width: 30,
					id: 'btn_change_order_top',
					handler: function(b, e){
						var grid = Ext.getCmp('bc_usr_meta_field');
						var start_index = 0;
						grid.store.each(function(r){
							var is_default = r.get('is_default');
							if (is_default == 1){
								start_index ++;
							}
						});
						moveSelectedRow(grid, 0, 'top', start_index);
						Ext.getCmp('save_order_button').enable();
						_auto_save_order(grid);
					}
				},{
					xtype: 'button',
					cls: 'proxima_button_customize',
					text: "<i class='fa fa-angle-up fa-2x' style=\"font-size:16px;color:white;\" title='"+_text('MN02230')+"'></i>",
					width: 30,
					id: 'btn_change_order_up',
					handler: function(b, e){
						var grid = Ext.getCmp('bc_usr_meta_field');
						var start_index = 0;
							grid.store.each(function(r){
								var is_default = r.get('is_default');
								if (is_default == 1){
									start_index ++;
								}
						});
						moveSelectedRow(grid, -1, 'up', start_index);
						Ext.getCmp('save_order_button').enable();
						_auto_save_order(grid);
					}

				},{
					xtype: 'button',
					cls: 'proxima_button_customize',
					text: "<i class='fa fa-angle-down fa-2x' style=\"font-size:16px;color:white;\" title='"+_text('MN02231')+"'></i>",
					width: 30,
					id: 'btn_change_order_down',
					handler: function(b, e){
						var grid = Ext.getCmp('bc_usr_meta_field');
						var start_index = 0;
						grid.store.each(function(r){
							var is_default = r.get('is_default');
							if (is_default == 1){
								start_index ++;
							}
						});
						moveSelectedRow(grid, +1, 'down', start_index);
						Ext.getCmp('save_order_button').enable();
						_auto_save_order(grid);
					}

				},{
					xtype: 'button',
					cls: 'proxima_button_customize',
					text: "<i class='fa fa-angle-double-down fa-2x' style=\"font-size:16px;color:white;\" title='"+_text('MN02232')+"'></i>",
					width: 30,
					id: 'btn_change_order_bottom',
					handler: function(b, e){
						var grid = Ext.getCmp('bc_usr_meta_field');
						var start_index = 0;
						grid.store.each(function(r){
							var is_default = r.get('is_default');
							if (is_default == 1){
								start_index ++;
							}
						});
						moveSelectedRow(grid, 0, 'bottom', start_index);
						Ext.getCmp('save_order_button').enable();
						_auto_save_order(grid);
					}

				}],

				buttonAlign: 'center',
				fbar: [{
					url: 'add.php',
					text : '<span style="position:relative;top:1px;"><i class="fa fa-plus" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00033'),
					mode: _text('MN00033'),
					scale: 'medium',
					id: 'btn_add_metadata_field',
					handler: _load_form
				},{
					url: 'edit.php',
					text : '<span style="position:relative;top:1px;"><i class="fa fa-edit" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00043'),
					scale: 'medium',
					mode: _text('MN00043'),
					id: 'btn_edit_metadata_field',
					handler: _load_form
				},{
					text: _text('MN02228'),
					id : 'save_order_button',
					//disabled: true,
					hidden: true,
					scale: 'medium',
					handler: function(b, e){
						var grid		= Ext.getCmp('bc_usr_meta_field');
						_save_order(grid);
					}
				},{
					text : '<span style="position:relative;top:1px;"><i class="fa fa-trash" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00034'),
					scale: 'medium',
					id: 'btn_delete_metadata_field',
					handler: _delete_record
				}]
			});
		}
	});

	return new Ariel.config.custom.MetadataPanel();
})()