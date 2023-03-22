(function(){
/****************************************************************
 * 11-11-15, 이승수 
 * 1. 시스템관리 -> 코드관리 페이지
 * 2. 구성
 * 		코드 추가: add.php,
 * 		코드 삭제: del.php,
 * 		코드 수정: edit.php,
 * 		코드추가시 콤보박스: get_comgbo.php
 * 		코드 목록 그리드에 출력: get.php
 * 		
 ****************************************************************/
	var main_store = new Ext.data.JsonStore({
        url: '/pages/menu/config/code/get.php',
        root: 'data',
        totalProperty: 'total',
        fields: ['code_type', 'code_type_name', 'code_id', 'code', 'code_name', 'ename']
    });
    main_store.load();    
	
	function editCode(){
		var code_id = Ext.getCmp('main_grid').getSelectionModel().getSelected().json['code_id'];
		var code_type_name = Ext.getCmp('main_grid').getSelectionModel().getSelected().json['code_type_name'];
		var code = Ext.getCmp('main_grid').getSelectionModel().getSelected().json['code'];
		var code_name = Ext.getCmp('main_grid').getSelectionModel().getSelected().json['code_name'];
		var code_ename = Ext.getCmp('main_grid').getSelectionModel().getSelected().json['ename'];
		new Ext.Window({
			id: 'code_edit_win',
			//title: '코드 수정',
			title: _text('MN02033'),
			width: 300,
			height: 200,
			modal: true,
			layout: 'fit',
			items: {
				id: 'code_edit_form',
				xtype: 'form',
				url: '/pages/menu/config/code/edit.php',
				frame: true,
				items:[{
					xtype: 'hidden',
					value: code_id,
					name: 'code_id'
				},{
					xtype: 'combo',
					width: 162,
					name: 'code_type_combo',
					//fieldLabel: '코드유형 명',
					fieldLabel: _text('MN02026'),
					emptyText: code_type_name,
					mode: 'local',
					triggerAction: 'all',
					disabled: true,
					editable: false,			
					displayField: 'code_type_name',
					store: new Ext.data.JsonStore({
						url: '/pages/menu/config/code/get_combo.php',
						root: 'data',
						totalProperty: 'total',
						fields: ['code_type_id', 'code_type_name']
					}),
					listeners: {
						render: function(){
							this.store.reload();
						}
					}
				},{
					xtype: 'textfield',
					width: 162,
					name: 'code',
					disabled: true,
					emptyText: code,
					//fieldLabel: '코드'
					fieldLabel: _text('MN02030')
				},{
					xtype: 'textfield',
					width: 162,
					name: 'code_name',
					emptyText: code_name,
					//fieldLabel: '코드 명'
					fieldLabel: _text('MN02032')
				},{
					xtype: 'textfield',
					width: 162,
					name: 'code_ename',
					emptyText: code_ename,
					//fieldLabel: '코드 영문명'
					fieldLabel: _text('MN02191')
				}],
				buttons: [{
					//text: '수정',
					text: _text('MN00043'),
					handler: function(){
						Ext.getCmp('code_edit_form').getForm().submit({
							success: function(form, action){
								if(action.result.success)
								{
									//Ext.Msg.alert( _text('MN02033'), action.result.msg);//'코드 수정'
								}
								else
								{
									Ext.Msg.alert( _text('MN01039'), action.result.errormsg);//오류
								}
								main_store.reload();
								Ext.getCmp('code_edit_win').close();
							}
							,failure: function(form, action) {				
								Ext.Msg.alert( _text('MN01039'), action.result.errormsg);//오류
								Ext.getCmp('code_edit_win').close();										
							}
						});								
					}
				},{
					//text: '취소',
					text: _text('MN00004'),
					handler: function(){
						Ext.getCmp('code_edit_win').close();
					}
				}]
			}
		}).show();
	}
    
    return {
    	xtype: 'panel',
    	layout: 'fit',    	
    	items: [
		    {
		    	xtype: 'grid',
				id: 'main_grid',
		        store: main_store,		        
		        autoWidth: true,
		        autoScroll: true,
		        viewConfig: {
					forceFit: true
				},
				sm: new Ext.grid.RowSelectionModel({
					singleSelect: true
				}),
				tbar:[{
						xtype: 'displayfield',
						//value: '코드유형'
						value: _text('MN02024')+'&nbsp;( '
				},{
					//text: '추가',
					text: '<span style="position:relative;top:1px;"><i class="fa fa-plus-circle" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00033'),
					handler: function(b, e){
						new Ext.Window({
							id: 'code_type_add_win',
							//title: '코드유형 추가',
							title: _text('MN02025'),
							width: 300,
							height: 130,
							modal: true,
							layout: 'fit',
							items: {
								id: 'code_type_add_form',
								xtype: 'form',
								url: '/pages/menu/config/code/add.php',
								frame: true,
								items:[{
									xtype: 'textfield',
									width: 162,
									name: 'code',
									//fieldLabel: '코드유형'
									fieldLabel: _text('MN02024')
								},{
									xtype: 'textfield',
									width: 162,
									name: 'code_name',
									//fieldLabel: '코드유형 명'
									fieldLabel: _text('MN02026')
								}],
								buttons: [{
									//text: '추가',
									text: _text('MN00033'),
									handler: function(){
										Ext.getCmp('code_type_add_form').getForm().submit({
											success: function(form, action){										
												if(action.result.success)
												{
													//Ext.Msg.alert( _text('MN02025'), action.result.msg);//'코드유형 추가'
												}
												else
												{
													Ext.Msg.alert( _text('MN01039'), action.result.errormsg);//오류
												}
												main_store.reload();
												Ext.getCmp('code_type_add_win').close();
											},
											failure: function(form, action) {
												Ext.Msg.alert( _text('MN01039'), action.result.errormsg);//오류
												Ext.getCmp('code_type_add_win').close();
											}
										});								
									}
								},{
									//text: '취소',
									text: _text('MN00004'),
									handler: function(){
										Ext.getCmp('code_type_add_win').close();
									}
								}]
							}
						}).show();
					}
				},{
					xtype: 'displayfield',
					value: '| '
				},{
					//text: '수정',
					text: '<span style="position:relative;top:1px;"><i class="fa fa-edit" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00043'),
					handler: function(){
						new Ext.Window({
							id: 'code_type_edit_win',
							//title: '코드유형 수정',
							title: _text('MN02027'),
							width: 300,
							height: 165,
							modal: true,
							layout: 'fit',
							items: {
								id: 'code_type_edit_form',
								xtype: 'form',
								url: '/pages/menu/config/code/edit.php',
								frame: true,
								items:[{
									xtype: 'combo',
									width: 162,
									name: 'code_type_combo',
									//fieldLabel: '코드유형 명',
									fieldLabel: _text('MN02026'),
									mode: 'local',
									triggerAction: 'all',
									editable: false,			
									displayField: 'code_type_name',
									store: new Ext.data.JsonStore({
										url: '/pages/menu/config/code/get_combo.php',
										root: 'data',
										totalProperty: 'total',
										fields: ['code_type_id', 'code_type_name']
									}),
									listeners: {
										render: function(){
											this.store.reload();
										}
									}
								},{
									xtype: 'textfield',
									width: 162,
									name: 'code_type_name',
									//fieldLabel: '수정할 값'
									fieldLabel: _text('MN02028')
								}],
								buttons: [{
									//text: '수정',
									text: _text('MN00043'),
									handler: function(){
										Ext.getCmp('code_type_edit_form').getForm().submit({
											success: function(form, action){
												if(action.result.success)
												{
													//Ext.Msg.alert( _text('MN02027'), action.result.msg);//'코드유형 수정'
												}
												else
												{
													Ext.Msg.alert( _text('MN01039'), action.result.errormsg);//오류
												}
												main_store.reload();
												Ext.getCmp('code_type_edit_win').close();
											}
											,failure: function(form, action) {										
												Ext.Msg.alert( _text('MN01039'), action.result.errormsg);//오류
												Ext.getCmp('code_type_edit_win').close();										
											}
										});								
									}
								},{
									//text: '취소',
									text: _text('MN00004'),
									handler: function(){
										Ext.getCmp('code_type_edit_win').close();
									}
								}]
							}
						}).show();
					}
				},{
					xtype: 'displayfield',
					value: '| '
				},{
					//text: '삭제',
					text: '<span style="position:relative;top:1px;"><i class="fa fa-minus-circle" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00034'),
					handler: function(){
						new Ext.Window({
							id: 'code_type_del_win',
							//title: '코드유형 삭제',
							title: _text('MN02029'),
							width: 300,
							height: 165,
							modal: true,
							layout: 'fit',
							items: {
								id: 'code_type_del_form',
								xtype: 'form',
								url: '/pages/menu/config/code/del.php',
								frame: true,
								items:[{
									xtype: 'combo',
									width: 162,
									name: 'code_type_combo',
									id: 'code_type_combo_id',
									//fieldLabel: '코드유형 명',
									fieldLabel: _text('MN02026'),
									mode: 'local',
									editable: false,
									triggerAction: 'all',
									displayField: 'code_type_name',
									store: new Ext.data.JsonStore({
										url: '/pages/menu/config/code/get_combo.php',
										root: 'data',
										totalProperty: 'total',
										fields: ['code_type_id', 'code_type_name']
									}),
									listeners: {
										render: function(){
											this.store.reload();
										}
									}
								}],
								buttons: [{
									//text: '삭제',
									text: _text('MN00034'),
									handler: function(){
										Ext.getCmp('code_type_del_form').getForm().submit({
											success: function(form, action){
												if(action.result.success)
												{
													var combo_value = Ext.getCmp('code_type_combo_id').getValue();
													var response_msg = action.result.msg;
													//Ext.Msg.alert('코드유형 삭제', action.result.msg);
													Ext.Msg.show({
														//title: '코드유형 삭제',
														title: _text('MN02029'),
														msg: response_msg,
														buttons: {
															yes: true,
															no: true
														},
														fn: function(btn){
															if(btn == 'yes')
															{
																Ext.Ajax.request({
																	url: '/pages/menu/config/code/del.php',
																	params: {
																		code_type_combo: combo_value,
																		del_continue: true
																	},
																	callback: function(opts, success, response){
																		if (success)
																		{
																			try
																			{
																				var result = Ext.decode(response.responseText);
																				if (!result.success)
																				{
																					Ext.Msg.show({
																						title:  _text('MN01039'),//'오류'
																						msg: result.msg,
																						icon: Ext.Msg.ERROR,
																						buttons: Ext.Msg.OK
																					});
																				}
																			}
																			catch (e)
																			{
																					Ext.Msg.show({
																						title:  _text('MN01039'),//'오류'
																						msg: e['message'],
																						icon: Ext.Msg.ERROR,
																						buttons: Ext.Msg.OK
																					});
																			}
																		}
																		else
																		{
																			
																		}									
																	}
																});
																main_store.reload();
															}						
														}
													});	
												}
												else
												{
													Ext.Msg.alert( _text('MN01039'), action.result.errormsg);//'오류'
												}
												main_store.reload();
												Ext.getCmp('code_type_del_win').close();
											}
											,failure: function(form, action) {										
												Ext.Msg.alert( _text('MN01039'), action.result.errormsg);//'오류'
												Ext.getCmp('code_type_del_win').close();										
											}
										});								
									}
								},{
									//text: '취소',
									text: _text('MN00004'),
									handler: function(){
										Ext.getCmp('code_type_del_win').close();
									}
								}]
							}
						}).show();
						
						
						/*
						
						Ext.Msg.show({
							title: '코드유형 삭제',
							msg: '정말로 삭제하시겠습니까?',
							buttons: {
								yes: true,
								no: true
							},
							fn: function(btn){
								if(btn == 'yes')
								{							
									Ext.Ajax.request({
										url: '/pages/menu/config/code/del.php',
										params: {
											code_id: code_id
										},
										callback: function(opts, success, response){
											if (success)
											{
												try
												{
													var result = Ext.decode(response.responseText);
													if (!result.success)
													{
														Ext.Msg.show({
															title: '오류',
															msg: result.msg,
															icon: Ext.Msg.ERROR,
															buttons: Ext.Msg.OK
														});
													}
												}
												catch (e)
												{
														Ext.Msg.show({
															title: '디코드 오류',
															msg: e['message'],
															icon: Ext.Msg.ERROR,
															buttons: Ext.Msg.OK
														});
												}
											}
											else
											{
												
											}									
										}
									});
									main_store.reload();
								}						
							}
						});						
						*/
					}
				},{
					xtype: 'displayfield',
					value: ' )'
				},{
					xtype: 'displayfield',
					width: 5
				},'-',{
					xtype: 'displayfield',
					width: 5
				},{
					xtype: 'displayfield',
					//value: '코드 ( '
					value: _text('MN02030')+' ( '
				},{
					//text: '추가',
					text: '<span style="position:relative;top:1px;"><i class="fa fa-plus-circle" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00033'),
					handler: function(b, e){
						new Ext.Window({
							id: 'code_add_win',
							//title: '코드 추가',
							title: _text('MN02031'),
							width: 300,
							height: 155,
							modal: true,
							layout: 'fit',
							items: {
								id: 'code_add_form',
								xtype: 'form',
								url: '/pages/menu/config/code/add.php',
								frame: true,
								items:[{
									xtype: 'combo',
									width: 162,
									name: 'code_type_combo',
									//fieldLabel: '코드유형 명',
									fieldLabel: _text('MN02026'),
									mode: 'local',
									editable: false,
									triggerAction: 'all',
									displayField: 'code_type_name',
									store: new Ext.data.JsonStore({
										url: '/pages/menu/config/code/get_combo.php',
										root: 'data',
								        totalProperty: 'total',
								        fields: ['code_type_id', 'code_type_name']
									}),
									listeners: {
										render: function(){
											this.store.reload();
										}
									}
								},{
									xtype: 'textfield',
									width: 162,
									name: 'code',
									//fieldLabel: '코드'
									fieldLabel: _text('MN02030')
								},{
									xtype: 'textfield',
									width: 162,
									name: 'code_name',
									//fieldLabel: '코드 명'
									fieldLabel: _text('MN02032')
								}],
								buttons: [{
									//text: '추가',
									text: _text('MN00033'),
									handler: function(){
										Ext.getCmp('code_add_form').getForm().submit({
											success: function(form, action){
												if(action.result.success)
												{
													//Ext.Msg.alert( _text('MN02031'), action.result.msg);//'코드 추가'
												}
												else
												{
													Ext.Msg.alert( _text('MN01039'), action.result.errormsg);//오류
												}
												main_store.reload();
												Ext.getCmp('code_add_win').close();
											}
											,failure: function(form, action) {										
												Ext.Msg.alert( _text('MN01039'), action.result.errormsg);//오류
												Ext.getCmp('code_add_win').close();										
											}
										});								
									}
								},{
									//text: '취소',
									text: _text('MN00004'),
									handler: function(){
										Ext.getCmp('code_add_win').close();
									}
								}]
							}
						}).show();
					}
				},{
					xtype: 'displayfield',
					value: '| '
				},{
					//text: '수정',
					text: '<span style="position:relative;top:1px;"><i class="fa fa-edit" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00043'),
					handler: function(){
						var check = Ext.getCmp('main_grid').getSelectionModel().hasSelection();
						if(check)
						{
							editCode();
							//var code_id = Ext.getCmp('main_grid').getSelectionModel().getSelected().json['code_id'];
							//var code_type_name = Ext.getCmp('main_grid').getSelectionModel().getSelected().json['code_type_name'];
							//var code = Ext.getCmp('main_grid').getSelectionModel().getSelected().json['code'];
							//var code_name = Ext.getCmp('main_grid').getSelectionModel().getSelected().json['code_name'];
							//new Ext.Window({
								//id: 'code_edit_win',
								////title: '코드 수정',
								//title: _text('MN02033'),
								//width: 300,
								//height: 165,
								//modal: true,
								//layout: 'fit',
								//items: {
									//id: 'code_edit_form',
									//xtype: 'form',
									//url: '/pages/menu/config/code/edit.php',
									//frame: true,
									//items:[{
										//xtype: 'hidden',
										//value: code_id,
										//name: 'code_id'
									//},{
										//xtype: 'combo',
										//width: 162,
										//name: 'code_type_combo',
										////fieldLabel: '코드유형 명',
										//fieldLabel: _text('MN02026'),
										//emptyText: code_type_name,
										//mode: 'local',
										//triggerAction: 'all',
										//disabled: true,
										//editable: false,			
										//displayField: 'code_type_name',
										//store: new Ext.data.JsonStore({
											//url: '/pages/menu/config/code/get_combo.php',
											//root: 'data',
									        //totalProperty: 'total',
									        //fields: ['code_type_id', 'code_type_name']
										//}),
										//listeners: {
											//render: function(){
												//this.store.reload();
											//}
										//}
									//},{
										//xtype: 'textfield',
										//width: 162,
										//name: 'code',
										//disabled: true,
										//emptyText: code,
										////fieldLabel: '코드'
										//fieldLabel: _text('MN02030')
									//},{
										//xtype: 'textfield',
										//width: 162,
										//name: 'code_name',
										//emptyText: code_name,
										////fieldLabel: '코드 명'
										//fieldLabel: _text('MN02032')
									//}],
									//buttons: [{
										////text: '수정',
										//text: _text('MN00043'),
										//handler: function(){
											//Ext.getCmp('code_edit_form').getForm().submit({
												//success: function(form, action){
													//if(action.result.success)
													//{
														////Ext.Msg.alert( _text('MN02033'), action.result.msg);//'코드 수정'
													//}
													//else
													//{
														//Ext.Msg.alert( _text('MN01039'), action.result.errormsg);//오류
													//}
													//main_store.reload();
													//Ext.getCmp('code_edit_win').close();
												//}
												//,failure: function(form, action) {				
													//Ext.Msg.alert( _text('MN01039'), action.result.errormsg);//오류
													//Ext.getCmp('code_edit_win').close();										
												//}
											//});								
										//}
									//},{
										////text: '취소',
										//text: _text('MN00004'),
										//handler: function(){
											//Ext.getCmp('code_edit_win').close();
										//}
									//}]
								//}
							//}).show();
						}
						else
						{
							Ext.Msg.alert( _text('MN00043'), _text('MSG01005'));//'수정', 먼저 대상을 선택 해 주시기 바랍니다.
						}
					}
				},{
					xtype: 'displayfield',
					value: '| '
				},{
					//text: '삭제',
					text: '<span style="position:relative;top:1px;"><i class="fa fa-minus-circle" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00034'),
					handler: function(){
						var check = Ext.getCmp('main_grid').getSelectionModel().hasSelection();
						if(check)
						{
							var code_id = Ext.getCmp('main_grid').getSelectionModel().getSelected().json['code_id'];
							Ext.Msg.show({
								//title: '삭제',
								text: _text('MN00034'),
								//msg: '정말로 삭제하시겠습니까?',
								msg: _text('MSG02039'),
								buttons: {
									yes: true,
									no: true
								},
								fn: function(btn){
									if(btn == 'yes')
									{							
										Ext.Ajax.request({
											url: '/pages/menu/config/code/del.php',
											params: {
												code_id: code_id
											},
											callback: function(opts, success, response){
												if (success)
												{
													try
													{
														var result = Ext.decode(response.responseText);
														if (!result.success)
														{
															Ext.Msg.show({
																//title: '오류',
																title:  _text('MN01039'),//'오류'
																msg: result.msg,
																icon: Ext.Msg.ERROR,
																buttons: Ext.Msg.OK
															});
														}
													}
													catch (e)
													{
															Ext.Msg.show({
																//title: '디코드 오류',
																title:  _text('MN01039'),//'오류'
																msg: e['message'],
																icon: Ext.Msg.ERROR,
																buttons: Ext.Msg.OK
															});
													}
												}
												else
												{
													
												}									
											}
										});
										main_store.reload();
									}						
								}
							});
						}
						else
						{			
							Ext.Msg.alert( _text('MN00034'), _text('MSG01005'));//'삭제', 먼저 대상을 선택 해 주시기 바랍니다.
						}
						
					}
				},{
					xtype: 'displayfield',
					value: ' )'
				},{
					xtype: 'displayfield',
					width: 5
				},'-',{
					xtype: 'displayfield',
					width: 5
				},{
					//text: '엑셀로 저장',
					text: '<span style="position:relative;top:1px;"><i class="fa fa-file-excel-o" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00212'),
					handler: function(){
						var search_word = Ext.getCmp('search_word').getValue();
						window.location = '/pages/menu/config/code/get.php?mode=excel&search_word='+search_word;
					}
				},{
					xtype: 'displayfield',
					width: 5
				},'-',{
					xtype: 'displayfield',
					width: 5
				},{
					//text: '새로고침',
					text: '<span style="position:relative;top:1px;"><i class="fa fa-refresh" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00390'),
					handler: function(b, e){
						main_store.reload();
					}
				},'->',{
					xtype: 'textfield',
					id: 'search_word',
					listeners:{
						specialkey: function(field, e){
							var search_word = Ext.getCmp('search_word').getValue();
							main_store.reload({
								params:{
									search_word: search_word
								}
							});
						}
					}
				},{
					//text: '검색',
					text: '<span style="position:relative;top:1px;"><i class="fa fa-search" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00037'),
					handler: function(b, e){
						var search_word = Ext.getCmp('search_word').getValue();
						main_store.reload({
							params:{
								search_word: search_word
							}
						});
						//console.log(Ext.getCmp('main_grid').getStore());
						//Ext.getCmp('main_grid').addStroe(search_store);
					}
				}],
				listeners: {
					rowdblclick: function(self, index, e){
						var check = Ext.getCmp('main_grid').getSelectionModel().hasSelection();
						if(check)
						{
							editCode();
							//var code_id = Ext.getCmp('main_grid').getSelectionModel().getSelected().json['code_id'];
							//var code_type_name = Ext.getCmp('main_grid').getSelectionModel().getSelected().json['code_type_name'];
							//var code = Ext.getCmp('main_grid').getSelectionModel().getSelected().json['code'];
							//var code_name = Ext.getCmp('main_grid').getSelectionModel().getSelected().json['code_name'];
							//new Ext.Window({
								//id: 'code_edit_win',
								////title: '코드 수정',
								//title: _text('MN02033'),
								//width: 300,
								//height: 200,
								//modal: true,
								//layout: 'fit',
								//items: {
									//id: 'code_edit_form',
									//xtype: 'form',
									//url: '/pages/menu/config/code/edit.php',
									//frame: true,
									//items:[{
										//xtype: 'hidden',
										//value: code_id,
										//name: 'code_id'
									//},{
										//xtype: 'combo',
										//width: 162,
										//name: 'code_type_combo',
										////fieldLabel: '코드유형 명',
										//fieldLabel: _text('MN02026'),
										//emptyText: code_type_name,
										//mode: 'local',
										//triggerAction: 'all',
										//disabled: true,
										//editable: false,			
										//displayField: 'code_type_name',
										//store: new Ext.data.JsonStore({
											//url: '/pages/menu/config/code/get_combo.php',
											//root: 'data',
									        //totalProperty: 'total',
									        //fields: ['code_type_id', 'code_type_name']
										//}),
										//listeners: {
											//render: function(){
												//this.store.reload();
											//}
										//}
									//},{
										//xtype: 'textfield',
										//width: 162,
										//name: 'code',
										//disabled: true,
										//emptyText: code,
										////fieldLabel: '코드'
										//fieldLabel: _text('MN02030')
									//},{
										//xtype: 'textfield',
										//width: 162,
										//name: 'code_name',
										//emptyText: code_name,
										////fieldLabel: '코드 명'
										//fieldLabel: _text('MN02032')
									//},{
										//xtype: 'textfield',
										//width: 162,
										//name: 'code_ename',
										//emptyText: code_name,
										////fieldLabel: '코드 영문명'
										//fieldLabel: _text('MN02032')
									//}],
									//buttons: [{
										////text: '수정',
										//text: _text('MN00043'),
										//handler: function(){
											//Ext.getCmp('code_edit_form').getForm().submit({
												//success: function(form, action){
													//if(action.result.success)
													//{
														//Ext.Msg.alert( _text('MN02033'), action.result.msg);//'코드 수정'
													//}
													//else
													//{
														//Ext.Msg.alert( _text('MN01039'), action.result.errormsg);//오류
													//}
													//main_store.reload();
													//Ext.getCmp('code_edit_win').close();
												//}
												//,failure: function(form, action) {		
													//Ext.Msg.alert( _text('MN01039'), action.result.errormsg);//오류
													//Ext.getCmp('code_edit_win').close();										
												//}
											//});								
										//}
									//},{
										////text: '취소',
										//text: _text('MN00004'),
										//handler: function(){
											//Ext.getCmp('code_edit_win').close();
										//}
									//}]
								//}
							//}).show();
						}
						else
						{
							Ext.Msg.alert( _text('MN00043'), _text('MSG01005'));//'수정', 먼저 대상을 선택 해 주시기 바랍니다.
						}
					}
				},
		        colModel: new Ext.grid.ColumnModel({
			        defaultSortable: true,
					defaults: {
				        sortable: true,
				        menuDisabled: true
				        //width: 100
				    },
			        columns: [
			        	new Ext.grid.RowNumberer(),
			            {
			                //header   : '코드유형', 
							header   : _text('MN02024'),
			                sortable : true, 
			                dataIndex: 'code_type'
			            },{
			                //header   : '코드유형 명', 
							header   : _text('MN02026'),
			                sortable : true, 
			                dataIndex: 'code_type_name'
			            },{
			                //header   : '코드', 
							header   : _text('MN02030'),
			                sortable : true, 
			                dataIndex: 'code'
			            },{
			                //header   : '코드 명', 
							header   : _text('MN02032'),
			                sortable : true, 
			                dataIndex: 'code_name'
			            },{
			                //header   : '코드 명', 
							header   : _text('MN02032'),
			                sortable : true, 
			                dataIndex: 'ename'
			            }]
		        })
		    }
    	]
    };
})()