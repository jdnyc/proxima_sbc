<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
?>

(function(){

	Ext.ns('Ariel.config');

	function renderBreake(v){
		switch (v)
		{
			case 'C':
				return '<span style="color: blue">재직</span>';
			break;

			case 'T':
				return '<span style="color: red">퇴사</span>';
			break;
		}
	}

	function date_format(v)//날짜 변환함수
	{
		if(!Ext.isEmpty(v))
		{
			var year = v.substr(0,4);
			var mon = v.substr(4,2);
			var day = v.substr(6,2);
			var val = year+"-"+mon+"-"+day;
			return val;
		}
		else
		{
			return v;
		}


	}

	function getprogWin(action , list  , target){

		if (!Ext.isEmpty(list))
		{
			var category_id = list.category_id ;
			var category_name = list.category_name ;
			var folder_name = list.folder_name ;
			var quota = list.quota ;
		}
		else
		{
			var category_id = '' ;
			var category_name = '' ;
			var folder_name = '' ;
			var quota = '' ;
		}


		if( Ext.isEmpty(Ext.getCmp('add_category_win')))
		{
			var add_win = new Ext.Window({
				layout: 'vbox',
				id: 'add_category_win',
				code: action,
				title: '프로그램 검색',
				width: 600,
				height: 600,
//				closeAction: 'hide',
				modal: true,
				resizable: true,
				plian: true,
				items:[{
					id:'add_category',
					xtype:'form',
					border: false,
					frame: true,
                                        width: '100%',
                                        flex: 1,
					defaults: {
						anchor:'100%'
					},
					items:[{
						id: 'category',
						xtype: 'textfield',
                        readOnly: true,
						emptyText: '카테고리명을 입력해주세요',
						fieldLabel: '카테고리명 입력',
						value: category_name,
						anchor: '95%'
					},{
						id: 'folder_name',
						xtype: 'textfield',
                        readOnly: true,
						emptyText: '폴더명을 입력해주세요',
						fieldLabel: '폴더명 입력',
						value: folder_name,
						anchor: '95%'
					},{
						id: 'quota',
						xtype: 'numberfield',
						hidden: true,
						emptyText: 'QUOTA를 입력해주세요',
						fieldLabel: 'QUOTA(GB)',
						value: quota,
						anchor: '95%'
					},{
						hiddden: true,
						xtype: 'hidden',
						id: 'category_id',
						value: category_id
					}]
				},new Ariel.Nps.BISProgram({metaTab: 'add_category', flex:2})],
				buttons:[{
					icon:'/led-icons/accept.png',
					text:'저장',
					handler:function(){
						var category_name=Ext.getCmp('category').getValue();
						var folder=Ext.getCmp('folder_name').getValue();
						var quota=Ext.getCmp('quota').getValue();

						var formValues = Ext.getCmp('add_category').getForm().getValues();


						var category_id = Ext.getCmp('category_id').getValue();

						if(Ext.isEmpty( category_id ) )
						{
							var action = 'add';
						}
						else
						{
							var action = 'edit';
						}

						var params = [];

						if( category_name && folder ){

							Ext.Ajax.request({
								url:'/pages/menu/config/Program/category_save.php',
								params:{
									category_name: category_name,
									folder: folder,
									category_id: category_id,
									quota : quota,
									action: action,
									params: Ext.encode(params)
								},
								callback:function(opt,suc,res)
								{
									var r = Ext.decode(res.responseText);
									if(suc)
									{
										if(r.success){
											Ext.getCmp('add_category_win').hide();
											Ext.Msg.alert('저장','저장 성공');
											target.refresh(target);
										}
										else{
											Ext.Msg.alert('저장','저장 실패');
										}
									}
									else
									{
										Ext.Msg.alert('오류',r.msg);
									}
								}
							})
						}else{
							Ext.Msg.alert( _text('MN00023'),'정보가 부족합니다');
						}
					}

				},{
					icon:'/led-icons/cancel.png',
					text:'닫기',
					handler:function(){
						Ext.getCmp('add_category_win').hide();
					}
				}]
			});
		}

		if ( action == 'edit' )
		{
			Ext.getCmp('add_category_win').code = action;

			Ext.getCmp('category').setValue(category_name);
			Ext.getCmp('folder_name').setValue(folder_name);
			Ext.getCmp('quota').setValue(quota);
			Ext.getCmp('folder_name').setReadOnly(true);
			Ext.getCmp('category_id').setValue(category_id);
			Ext.getCmp('bis_program_list').setVisible(false);
		}
		else if( action == 'add' )
		{
			Ext.getCmp('add_category_win').code = action;
			Ext.getCmp('category').setValue('');
			Ext.getCmp('folder_name').setValue('');
			//Ext.getCmp('folder_name').setReadOnly(false);
			Ext.getCmp('quota').setValue('');
			Ext.getCmp('category_id').setValue('');
			Ext.getCmp('bis_program_list').setVisible(true);
			Ext.getCmp('bis_program_list').getStore().load();
		}


	}

	Ariel.config.Product_Program_MNG_Tree = Ext.extend(Ext.Panel, {
		layout: 'fit',
		defaults:{
			margins:'5 5 5 5',
			padding: 5
		},
		initComponent: function(config){
			Ext.apply(this, config || {});

			var that = this;

			this.add_subcategory_node = function(target){
				return {
					cmd: 'add-subcategory-node',
					text: '부제 카테고리 생성',
					hidden: true,
					icon: '/led-icons/control_wheel.png',
					handler: function(){
						var sel = target.treegrid.getSelectionModel().getSelectedNodes();

						if( (Ext.isEmpty(sel)) ||   ( sel[0].getDepth() != 1 ) )
						{
							Ext.Msg.alert( _text('MN00023'),'해당 카테고리를 선택하여 주세요');
							return;
						}

						Ext.Ajax.request({
							url:'/pages/menu/config/Program/update_subprog.php',
							params:{
								category_id: sel[0].id,
								type : 'each'
							},
							callback: function(opts, suc, res){
								if(suc)
								{
									try
									{
										var r = Ext.decode(res.responseText);
										if (!r.success)
										{
											Ext.Msg.alert('확인', r.msg);
										}
										else
										{
											Ext.Msg.alert('확인', r.msg);
											target.refresh(target);
										}
									}
									catch (e)
									{
										Ext.Msg.alert('오류', res.responseText);
									}
								}
							}
						});
					}
				};
			};


			this.add_category_node = function(target){
				return {
					cmd: 'add-category-node',
					text: '프로그램 추가',
					icon: '/led-icons/folder_add.png',
					handler: function(){

						getprogWin('add' , '', target);
						Ext.getCmp('add_category_win').show();
					}
				};
			};

			this.add_user_node = function(target){
				return {
					cmd: 'add-user-node',
					text: '사용자 추가',
					icon: '/coquette/png/16x16/add_user.png',
					handler: function(){
						var sel = target.treegrid.getSelectionModel().getSelectedNodes();

						if( (Ext.isEmpty(sel)) ||   ( sel[0].getDepth() != 1 ) )
						{
							Ext.Msg.alert( _text('MN00023'),'해당 프로그램을 선택하여 주세요');
							return;
						}

						if(!Ext.isEmpty(cat_win))
						{
							cat_win.destroy();
						}

						var title = sel[0].attributes.title;
						var category_id = [];
						var path = [];

						Ext.each(sel, function(i){
							path.push(i.attributes.path);
						});
						Ext.each(sel, function(i){
							category_id.push(i.attributes.category_id);
						});
						//var path = sel[0].attributes.path;
						//var category_id = sel[0].attributes.category_id;

						var cat_win = new Ext.Window({
							layout:'fit',
							title:'사용자 추가',
							width:500,
							height:500,

							modal:true,
							resizable:true,
							plian:true,
							items:[{
								xtype:'form',
								border:false,
								frame:true,
								labelWidth:100,
								defaults:{
									anchor:'100%'
								},
								items:[{
									xtype:'textfield',
									disabled:true,
									value: category_id,
									fieldLabel:'선택된 프로그램',
									anchor:'95%'
								},{
									xtype:'textfield',
									disabled:true,
									fieldLabel:'선택된 폴더',
									value: path,
									anchor:'95%'
								},
								{
									xtype: 'listview',
									fieldLabel:'추가할 사용자',
									anchor:'95%',
									id: 'user_listview',
									columnSort: false,
									reserveScrollOffset: true,
									autoScroll: true,
									emptyText: '등록된 데이터가 없습니다.',
									multiSelect: true,
									store: new Ext.data.ArrayStore({
										fields: [
											{ name: 'user_id' },
											{ name : 'user_nm' },
											{ name: 'dept_nm' }
										],
										listeners: {
											load: function(self){
												//console.log(self);
											}
										}

									}),
									columns: [
										{ header : '사번', dataIndex: 'user_id' },
										{ header : '성명', dataIndex: 'user_nm' },
										{ header : '부서', dataIndex: 'dept_nm' }
									],
									listeners: {
									}
								}]
							}],
							buttons:[{
								icon: '/led-icons/magnifier_zoom_in.png',
								text: '사용자 검색',
								handler:function(){

								var user_store = new Ext.data.JsonStore({
									url: '/pages/menu/config/user/php/get.php',
									id: 'user_search_store',
									remoteSort: true,
									sortInfo: {
										field: 'user_id',
										direction: 'ASC'
									},
									idProperty: 'member_id',
									totalProperty: 'total',
									root: 'data',
									fields: [
										'member_id',
										'user_id',
										'user_nm',
										'group',
										'occu_kind',
										'job_position',
										'job_duty',
										'dep_tel_num',
										'breake',
										'dept_nm',
										{name: 'created_time', type: 'date', dateFormat: 'YmdHis'},
										{name: 'last_login', type: 'date', dateFormat: 'YmdHis'},
										{name: 'hired_date', type: 'date', dateFormat: 'YmdHis'},
										{name: 'retire_date', type: 'date', dateFormat: 'YmdHis'}
									],
									listeners: {
										exception: function(self, type, action, opts, response, args){
											try {
												var r = Ext.decode(response.responseText);
												if(!r.success) {
													Ext.Msg.alert('정보', r.msg);
												}
											}
											catch(e) {
												Ext.Msg.alert('<?=_text('MN00022')?>', e);
											}
										}
									}
								});

									new Ext.Window({
										title: '사용자 검색',
										modal:true,
										width: 400,
										height: 400,
										layout: 'fit',

										items: [{
											xtype: 'form',
											layout: 'fit',
											border:false,

											buttons:[{
												id: 'btnAdd',
												icon:'/led-icons/add.png',
												//disabled: true,
												text: '리스트에 추가',
												handler: function(self){
													var records = self.ownerCt.ownerCt.get(0).getSelectionModel().getSelections();
													var store = Ext.getCmp('user_listview').getStore().getRange();

													var rs = [];

													Ext.each(records, function(i){
														rs.push(i.get('user_id'));
													});

													Ext.Ajax.request({
														url:'/pages/menu/config/Program/user_check.php',
														params:{
															user_id:Ext.encode(rs)
														},
														callback:function(option,success,response)
														{
															var r = Ext.decode(response.responseText);
															if(r.success)
															{
																var check = true;
																Ext.each(store, function(si){
																	Ext.each(records, function(ri){
																		if( si.get('user_id') == ri.get('user_id') )
																		{
																			check = false;
																			Ext.Msg.alert( _text('MN00023'), '중복되는 유저가 있습니다.');
																		}
																	});
																});

																if (check)
																{
																	self.ownerCt.ownerCt.get(0).getStore().remove( records );
																	Ext.getCmp('user_listview').getStore().add(records);
																}

															}
															else
															{

																Ext.Msg.alert('오류', r.msg);
																return;
															}
														}
													});
												}
											},{
												icon:'/led-icons/cancel.png',
												text: '닫기',
												handler: function(self){
													self.ownerCt.ownerCt.ownerCt.destroy();
												}
											}],
											items: [
												new Ext.grid.GridPanel({
													id: 'user_list',
													border: false,
													store: user_store,
													loadMask: true,
													listeners: {
														rowclick: function(self, idx, e){
															//if (self.getSelectionModel().getSelected().get('breake') == 'C')
															//{
															//	Ext.getCmp('btnAdd').enable();
															//}
															//else
															//{
															//	Ext.getCmp('btnAdd').disable();
															//}
														},
														viewready: function(self){
															self.getStore().load({
																params: {
																	start: 0,
																	limit: 20
																}
															});
														}
													},
													colModel: new Ext.grid.ColumnModel({
														defaults: {
															sortable: true
														},
														columns: [
															new Ext.grid.RowNumberer(),
															{header: 'number', dataIndex:'member_id',hidden:'true'},
															{header: '사번', dataIndex: 'user_id',	align:'center' },
															{header: '성명',   dataIndex: 'user_nm',		align:'center'},
															{header: '부서',   dataIndex: 'dept_nm',	align:'center'},
															{header: '재직구분',   dataIndex: 'breake',	align:'center', renderer: renderBreake}
														]
													}),
													viewConfig: {
														forceFit: true
													},
													tbar: [{
														xtype: 'combo',
														id: 'search_f',
														width: 70,
														triggerAction: 'all',
														editable: false,
														mode: 'local',
														store: [
															//['s_created_time', '등록일자'],
															['s_user_id', '사번'],
															['s_name', '성명'],
															['s_dept_nm','부서']
														],
														value: 's_user_id',
														listeners: {
															select: function(self, r, i){
																//if (i == 0)
																//{
																//	self.ownerCt.get(2).setVisible(true);
																//	self.ownerCt.get(3).setVisible(false);
																//}
																//else
																//{
																	self.ownerCt.get(3).setVisible(true);
																	self.ownerCt.get(2).setVisible(false);
																//}
															}
														}
													},' ',{
														hidden: true,
														xtype: 'datefield',
														id: 'search_v1',
														format: 'Y-m-d',
														listeners: {
															render: function(self){
																self.setValue(new Date());
															}
														}
													},{
														allowBlank: false,
														xtype: 'textfield',
														id: 'search_v2',
														listeners: {
															specialKey: function(self, e){
																var w = self.ownerCt.ownerCt;
																if (e.getKey() == e.ENTER && self.isValid())
																{
																	e.stopEvent();
																	w.doSearch(w.getTopToolbar(), Ext.getCmp('user_search_store'));
																}
															}
														}
													},' ',{
														icon:'/led-icons/magnifier_zoom_in.png',
														xtype: 'button',
														text: '조회',
														handler: function(b, e){
															var w = b.ownerCt.ownerCt;
															w.doSearch(w.getTopToolbar(), Ext.getCmp('user_search_store'));
														}
													},'-',{
														icon: '/led-icons/arrow_refresh.png',
														text: '새로고침',
														handler: function(btn, e){
															Ext.getCmp('user_list').getStore().load({
																	params:{
																		start:0,
																		limit:20
																	}
															});
														}
													}],
													bbar: new Ext.PagingToolbar({
														store: user_store,
														pageSize: 20
													}),
													doSearch: function(tbar, store){
														var combo_value = tbar.get(0).getValue(),
															params = {};
															params.start = 0;
															params.limit = 20;

														if (combo_value == 's_created_time')
														{
															params.search_field = combo_value;
															params.search_value = tbar.get(2).getValue().format('Y-m-d');
														}
														else
														{
															params.search_field = combo_value;
															params.search_value = tbar.get(3).getValue();
														}
														if(Ext.isEmpty(params.search_field) || Ext.isEmpty(params.search_value)){
															Ext.Msg.alert('정보', '검색어를 입력해주세요.');
														}else{
															Ext.getCmp('user_list').getStore().load({
																params: params
															});
														}
													}
												})]
											}]
									}).show();
								}
							},{
								icon:'/led-icons/accept.png',
								text:'저장',
								handler:function(){
									var records = Ext.getCmp('user_listview').getStore().getRange();

									var rs = [];

									Ext.each(records, function(i){
										rs.push(i.get('user_id'));
									});

									if( !Ext.isEmpty(records) )
									{
										Ext.Ajax.request({
											url:'/pages/menu/config/Program/user_save.php',
											params:{
												user_id: Ext.encode(rs),
												category_id: Ext.encode(category_id),
												action:'add'
											},
											callback:function(option,success,response)
											{
												var r = Ext.decode(response.responseText);
												if(r.success){

													Ext.Msg.alert('저장','저장되었습니다.');

													that.refresh(that);
												}
												else{
													Ext.Msg.alert('오류', r.msg);
												}
											}
										})
									}
									else
									{
										Ext.Msg.alert( _text('MN00023'),'사용자의 사번이 입력되지 않았습니다.');
									}

									cat_win.destroy();
								}

							},{
								icon:'/led-icons/cancel.png',
								text:'닫기',
								handler:function(){
									cat_win.destroy();
								}
							}]
						});

						cat_win.show();

					}
				};
			};

			this.edit_category_node = function(target){
				return {
					cmd: 'edit-category-node',
					text: '프로그램 수정',
					icon: '/led-icons/folder_edit.png',
					handler: function(){

						var sel = target.treegrid.getSelectionModel().getSelectedNodes();

						if( Ext.isEmpty(sel) || ( sel[0].getDepth() != 1 ) )
						{
							Ext.Msg.alert( _text('MN00023'),'프로그램을 선택하여 주세요');
							return;
						}
						else
						{
							var list = {};
							list.category_id = sel[0].attributes.category_id;
							list.category_name =  sel[0].attributes.title;
							list.folder_name =  sel[0].attributes.path;
							list.quota = sel[0].attributes.quota;

							getprogWin('edit' , list , target );
                                                        Ext.getCmp('add_category_win').setSize(600,350);
							Ext.getCmp('add_category_win').show();
						}
					}
				};
			};

			this.delete_user_node = function(target){
				return {
					cmd: 'delete-user-node',
					text: '사용자 삭제',
					icon: '/coquette/png/16x16/delete_user.png',
					handler: function(){
						var sel = target.treegrid.getSelectionModel().getSelectedNodes();

						if( (Ext.isEmpty(sel)) ||   ( sel[0].getDepth() != 2 ) )
						{
							Ext.Msg.alert( _text('MN00023'),'사용자를 선택하여 주세요');
							return;
						}

						var temp = new Array();

						Ext.each(sel, function(r){
							var temp_user = r.attributes.user_id;
							var temp_category = r.attributes.category_id;
							temp.push({user_id:temp_user, category_id:temp_category });
						});

						var user_id = sel[0].attributes.user_id;
						var category_id =  sel[0].attributes.category_id;

						Ext.Ajax.request({
							url:'/pages/menu/config/Program/user_save.php',
							params:{
								category_id: category_id,
								user_id: user_id,
								user_ids: Ext.encode(temp),
								action: 'del'
							},
							callback: function(opts, suc, res){
								if(suc)
								{
									try
									{
										var r = Ext.decode(res.responseText);
										if (!r.success)
										{
											Ext.Msg.alert('확인', r.msg);
										}
										else
										{
											Ext.Msg.alert('확인','삭제 성공');
											target.refresh(target);
										}
									}
									catch (e)
									{
										Ext.Msg.alert('오류', res.responseText);
									}
								}
							}
						});
					}
				};
			};

			this.set_notice_quota = function(target){
				return {
					text: '쿼터 알림 설정',
					icon: '/led-icons/drive_rename.png',
					handler: function(){
						Ext.Ajax.request({
							url: '/pages/menu/config/Program/get_notice_quota_form.php',
							callback: function(self, success, response){
								try {
									var r = Ext.decode(response.responseText);
									r.show();
								}
								catch(e){
									Ext.Msg.alert(_text('MN00022'), e);
								}
							}
						});
					}
				};
			};

			this.delete_category_node = function(target){
				return {
					cmd: 'delete-category-node',
					text: '프로그램 삭제',
					icon: '/led-icons/folder_delete.png',
					handler: function(){

						var sel = target.treegrid.getSelectionModel().getSelectedNodes();
						if( Ext.isEmpty(sel) || ( sel[0].getDepth() != 1 ) )
						{
							Ext.Msg.alert( _text('MN00023'),'프로그램을 선택하여 주세요');
							return;
						}
						else
						{
							Ext.Msg.show({
								title:'프로그램 삭제',
								msg: '프로그램을 삭제 하시겠습니까?',
								buttons: Ext.Msg.YESNO,
								animEl: 'elId',
								icon: Ext.MessageBox.QUESTION,
								fn: function(btnId, text, opts)
								{
									if(btnId == 'no') return;

									var category_id = sel[0].attributes.category_id;
									Ext.Ajax.request({
										url:'/pages/menu/config/Program/category_save.php',
										params:{
											category_id: category_id,
											action: 'del'
										},
										callback: function(opt,suc,res){
											if(suc){
												var r = Ext.decode(res.responseText);
												if(r.success)
												{
													Ext.Msg.alert('삭제','삭제 성공');

													target.refresh(target);

												}
												else
												{
													Ext.Msg.alert('삭제','삭제 실패');
												}
											}
											else{
												Ext.Msg.alert('오류',res.responseText);
											}
										}
									});

								}
							});

						}

					}
				};
			};

			this.refresh = function(that){
				that.treegrid.getLoader().on("beforeload", function(treeLoader, node){
				});


				that.treegrid.getLoader().load( that.treegrid.getRootNode() );
			}

			this.refresh_button = function(that){

				return {
					text: '새로고침',
					icon: '/led-icons/arrow_refresh.png',
					handler: function(btn, e){

						that.refresh(that);
					}
				}
			}


			this.treegrid = new Ext.ux.tree.TreeGrid({
				id: 'program_category_grid',
				enableDD: false,
				//columnResize : true,

				selModel: new Ext.tree.MultiSelectionModel({
				}),
				tbar: [
				that.refresh_button(that),
				'-',
					{
						xtype: 'tbspacer',
						width: 10
					},
					that.add_category_node(that),
					'-',
					that.edit_category_node(that),
					'-',
					that.delete_category_node(that),
					'-',
					that.add_user_node(that),
					'-',
					that.delete_user_node(that),
//					'-',
//					that.set_notice_quota(that),

				'->','-',{
					text: '모두 열기',
					icon: '/led-icons/folder-open.gif',
					handler: function(){
						Ext.getCmp('program_category_grid').expandAll(true);
					}
				},'-',{
					text: '모두 접기',
					icon: '/led-icons/folder.gif',
					handler: function(){
						Ext.getCmp('program_category_grid').collapseAll(false);
					}
				},
				'-',' ',
				{
					hidden: true,
					text: '엑셀다운로드',
					icon: '/led-icons/doc_excel_table.png',
					handler: function(){
						window.location="/pages/menu/config/Program/php/data.php?is_excel=true";
					}
				},' ',
				'-',
				{
					xtype: 'textfield',
					id: 'tree_search_field',
					listeners: {
					specialKey: function(self, e){
						if (e.getKey() == e.ENTER && self.isValid())
						{
							e.stopEvent();
							var root = that.treegrid.getRootNode();
							var value = Ext.getCmp('tree_search_field').getValue();
							if( Ext.isEmpty(value) ) return;

							that.treegrid.collapseAll(false);

							Ext.Ajax.request({
								url:'/pages/menu/config/Program/php/search_user.php',
								params:{
									name : value
								},
								callback: function(opt,suc,res){
									if(suc){
										var r = Ext.decode(res.responseText);
										if(r.success)
										{

											var list = r.msg;
											root.eachChild(function(node){

												for(var i=0; i < list.length ; i++ )
												{
													 if( node.findChild( 'category_id' , list[i].category_id ) )
													 {
														node.expand();
													 }
												}
											});
										}
										else
										{
											Ext.Msg.alert('오류',r.msg);
										}
									}
									else{
										Ext.Msg.alert('오류',res.responseText);
									}
								}
							});
						}
					}
				}
				},
				{
					text: '검색',
					icon: '/led-icons/find.png',
					handler: function(){
						var root = that.treegrid.getRootNode();
						var value = Ext.getCmp('tree_search_field').getValue();

						if( Ext.isEmpty(value) ) return;

						that.treegrid.collapseAll(true);

						Ext.Ajax.request({
							url:'/pages/menu/config/Program/php/search_user.php',
							params:{
								name : value
							},
							callback: function(opt,suc,res){
								if(suc){
									var r = Ext.decode(res.responseText);
									if(r.success)
									{

										var list = r.msg;
										root.eachChild(function(node){

											for(var i=0; i < list.length ; i++ )
											{
												 if( node.findChild( 'category_id' , list[i].category_id ) )
												 {
													node.expand();
												 }
											}
										});
									}
									else
									{
										Ext.Msg.alert('오류',r.msg);
									}
								}
								else{
									Ext.Msg.alert('오류',res.responseText);
								}
							}
						});
					}
				}
				],
				columns:[
					//{ header: "No", dataIndex: 'no' , width: 70 ,sortType: 'asInt' },
					{ header: "카테고리", dataIndex: 'title' , width: 150 },

					{ header: "폴더명", dataIndex: 'path' , width: 120 },
					//{ header: "스토리지 그룹", dataIndex: 'storage_group_name' , width: 80},
					//{ header: "사용자", dataIndex: 'name' , width: 80 ,sortable: false },
					//{ header: "QUOTA(GB)", dataIndex: 'quota',sortable: false , width: 200 },
					//{ header: "사용률(GB)", dataIndex: 'usage',sortable: false , width: 200 },
					{ header: "사번", dataIndex: 'user_id' , width: 80 ,sortable: false },
					//{ header: "부서명", dataIndex: 'dept_nm' ,sortable: false, width: 100 },
					//{ header: "재직여부", dataIndex: 'breake',sortable: false , width: 100  },
					//{ header: "부서 전화번호", dataIndex: 'dep_tel_num',sortable: false, width: 100 },
					{ header: "카테고리ID", dataIndex: 'id', sortable: false, hidden: true, width: 100 },
					{header: '사용자 정보', dataIndex: 'cnt', sortable: false, width: 60}
				],
				loader: new Ext.tree.TreeLoader({
					baseParams: {
					},
					listeners: {
						load: function( self,  node, response ){
							Ext.getCmp('program_category_grid').collapseAll(true);
						}
					},
					dataUrl: '/pages/menu/config/Program/php/data.php'
				}),
				contextMenu: new Ext.menu.Menu({
					items: [
						that.add_category_node(that),
						that.edit_category_node(that),
						that.delete_category_node(that),
						that.add_subcategory_node(that),
						'-',
						that.add_user_node(that),
						that.delete_user_node(that)
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
				listeners: {
					contextmenu: function(node, e) {
						//node.select();
						if( Ext.isEmpty(node.ownerTree.getSelectionModel().getSelectedNodes()) )
						{
							node.select();
						}

						var c = node.getOwnerTree().contextMenu;
						c.contextNode = node;
						c.showAt(e.getXY());
					}
					,afterrender: function(self){
					}
				}
			});

			this.items = [
				this.treegrid
			];

			Ariel.config.Product_Program_MNG_Tree.superclass.initComponent.call(this);
		}
	});


	return {
		xtype: 'panel',
		layout: 'fit',
		items:[
			new Ariel.config.Product_Program_MNG_Tree({
				//title: '제작프로그램 관리'
			})
		]
	}

})()