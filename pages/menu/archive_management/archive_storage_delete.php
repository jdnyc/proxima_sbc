<?php

/////////////////////////////
// 아카이브 삭제 관리 보여주는 페이지 
// 2011.12.15 
// by 허광회
/////////////////////////////

session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

// $arr_info_msg = getStoragePolicyInfo();
// $info = $arr_info_msg['info1_2'];
// $info2 = $arr_info_msg['info2_2'];

?>		  

(function(){

	function showEmpty(value){
		if(Ext.isEmpty(value)) value = '-';
		return value;
	}

	function showDate(value){
		if(Ext.isEmpty(value)) value = '-';
		else value = Ext.util.Format.date(value,'Y-m-d H:i:s');
		return value;
	}

	var sortChanges = function(grid,self){
                    var v = Ext.getCmp('delete_inform_id').getView();
                    store = Ext.getCmp('delete_inform_id').getStore();                  
                    store.each(function(r){
                        if(r.get('flag') == '삭제 완료')
                        {
                            v.fly(v.getRow(store.indexOf(r))).addClass('status-delete-complete');
                            v.fly(v.getRow(store.indexOf(r))).addClassOnSelect('status-delete-complete');
                            v.fly(v.getRow(store.indexOf(r))).addClassOnOver('status-delete-complete-over');                           
                        }  
                                                  
                        else if(r.get('flag') == '삭제승인')
                        {
                            v.fly(v.getRow(store.indexOf(r))).addClass('status-delete-approve');
                            v.fly(v.getRow(store.indexOf(r))).addClassOnClick('status-delete-complete');
                           v.fly(v.getRow(store.indexOf(r))).addClassOnOver('status-delete-approve-over');                           
                        }
                        
                        else if(r.get('flag') == '기한만료')
                        {
                            v.fly(v.getRow(store.indexOf(r))).addClass('status-delete-limit');
                            v.fly(v.getRow(store.indexOf(r))).addClassOnClick('status-delete-complete');
                           v.fly(v.getRow(store.indexOf(r))).addClassOnOver('status-delete-limit-over');                          
                        }
                        else if(r.get('flag') == '사용자 요청')
                        {
                            v.fly(v.getRow(store.indexOf(r))).addClass('status-delete-request');
                            v.fly(v.getRow(store.indexOf(r))).addClassOnClick('status-delete-complete');
                            v.fly(v.getRow(store.indexOf(r))).addClassOnOver('status-delete-request-over');                          
                        }
                    });
                }
                
     
	var total_list = 0;
	var delete_inform_size = 100;
	
	var selModel = new Ext.grid.CheckboxSelectionModel({             
	     singleSelect : false,
	     checkOnly : false
	     
	    
	 });
    
	var delete_store = new Ext.data.JsonStore({
		url:'/pages/menu/archive_management/get_archive_storage_delete.php',
		root: 'data',
		totalProperty : 'total_list',		
		idProperty: 'content_id',
		fields: [
			{name: 'content_id'},
			{name: 'ud_content_id'},
			{name: 'contentType'},
			{name: 'category'},
			{name: 'title'},
			{name: 'asset_id'},
			{name: 'mtrl_id'},
			{name: 'created_date',type:'date',dateFormat:'YmdHis'},
			{name: 'arc_time',type:'date',dateFormat:'YmdHis'},
			{name: 'del_exp_date'},
//			{name: 'delete_date', type: 'date',dateFormat: 'YmdHis'},
			{name: 'reg_user_id'},
			{name: 'expired_date',type: 'date',dateFormat: 'YmdHis'},
			{name: 'delete_result'},
			{name: 'file_size'},
			{name: 'flag'},
			'path',
			'media_type',
			'deleted_date',
			'flag_nm',
			'request_id',
			{name: 'request_date',type: 'date',dateFormat: 'YmdHis'},
			{name: 'auth_date',type: 'date',dateFormat: 'YmdHis'},
			{name: 'request_user_info'},
			{name: 'request_comment'},
			{name: 'auth_user_info'},
			{name: 'auth_comment'}
		],
		 
		listeners: {
			beforeload: function(self, opts){
				var search_val = Ext.getCmp('delete_combo').getValue();

				opts.params = opts.params || {};

				Ext.apply(opts.params, {
					start_date: Ext.getCmp('start_date').getValue().format('Ymd000000'),
					end_date: Ext.getCmp('end_date').getValue().format('Ymd240000'),
					arc_start_date: Ext.getCmp('r_arc_start_date').getValue().format('Ymd000000'),
					arc_end_date: Ext.getCmp('r_arc_end_date').getValue().format('Ymd240000'),
					ud_content: Ext.getCmp('ud_combo').getValue(),
					action : search_val,
					search_feild : Ext.getCmp('delete_archive_r_search_field').getValue(),
					mtrl_id : Ext.getCmp('mtrl_id').getValue()
				});				
						
			},
			load: function(self, opts){				
				total_list = self.getTotalCount();	
				var tooltext = "( 검색된 미디어 수 : <font color=blue><b>"+total_list +"</b></font> )";
				Ext.getCmp('toolbartext').setText(tooltext);
				
				var storage_info = self.reader.jsonData.info;
				Ext.getCmp('storage_info').setValue(storage_info);
				var storage_info2 = self.reader.jsonData.info2;
				Ext.getCmp('storage_info2').setValue(storage_info2);
			}
			
			//load: sortChanges
		}
	});

	var tbar1 = new Ext.Toolbar({
		dock: 'top',
        items: [' 구분 : ',{
			xtype:'combo',
			id:'delete_combo',
			mode:'local',
			width: 80,
			triggerAction:'all',
			editable:false,
			displayField:'d',
			valueField:'v',
			value : '전체보기',
			store: new Ext.data.ArrayStore({
				fields:[
					'd','v'	
				],
				data:[
					['전체보기','all'],					
					['삭제 대기',''],
					['삭제 요청','<?=DEL_MEDIA_REQUEST_FLAG?>'],	
					['삭제 승인','<?=DEL_MEDIA_ADMIN_APPROVE_FLAG?>'],
					['삭제 완료','<?=DEL_MEDIA_COMPLETE_FLAG?>'],
					['삭제 실패','<?=DEL_MEDIA_ERROR_FLAG?>']
				]
			}),
			listeners:{
				select:{
					fn:function(self,record,index){
						Ext.getCmp('delete_inform_id').getStore().reload();
					}
				}	
			}
		},'-',' 저장경로 : ',{
			xtype:'combo',
			id:'ud_combo',
			mode:'local',
			width:60,
			triggerAction:'all',
			editable:false,
			displayField:'d',
			valueField:'v',
			value : '전체',
			store: new Ext.data.ArrayStore({
				fields:[
					'd','v'	
				],
				data:[					
					['전체','all'],
					['NDS','<?=UD_NDS?>'],
					['PDS','<?=UD_PDS?>']
				]
			}),
			listeners:{
				select:{
					fn:function(self,record,index){
						Ext.getCmp('delete_inform_id').getStore().reload();
					}
				}	
			}
		},'-','Tape 완료일자',
		{
			xtype: 'datefield',
			id: 'start_date',
			//disabled : true,
			editable: true,
			width: 90,
			format: 'Y-m-d',
			altFormats: 'Ymd|ymd|Y-m-d|y-m-d|Y/m/d|Y/m/d',
			listeners: {
				render: function(self){
					var d = new Date();

					self.setMaxValue(d.format('Y-m-d'));
					self.setValue(d.add(Date.MONTH, -12).format('Y-m-d'));
				}
			}
		},'~' 
		,{
			xtype: 'datefield',
			id: 'end_date',
			editable: true,
			width: 90,
			format: 'Y-m-d',
			altFormats: 'Ymd|ymd|Y-m-d|y-m-d|Y/m/d|Y/m/d',
			//disabled : true,
			listeners: {
				render: function(self){
					var d = new Date();

					self.setMaxValue(d.format('Y-m-d'));
					self.setValue(d.format('Y-m-d'));
				}
			}
		},'-','승인일자',
		{
			xtype: 'datefield',
			id: 'r_arc_start_date',
			editable: true,
			width: 90,
			format: 'Y-m-d',
			altFormats: 'Ymd|ymd|Y-m-d|y-m-d|Y/m/d|Y/m/d',
			listeners: {
				render: function(self){
					var d = new Date();

					self.setMaxValue(d.format('Y-m-d'));
					self.setValue(d.add(Date.MONTH, -12).format('Y-m-d'));
				}
			}
		},'~' 
		,{
			xtype: 'datefield',
			id: 'r_arc_end_date',
			editable: true,
			width: 90,
			format: 'Y-m-d',
			altFormats: 'Ymd|ymd|Y-m-d|y-m-d|Y/m/d|Y/m/d',
			listeners: {
				render: function(self){
					var d = new Date();

					self.setMaxValue(d.format('Y-m-d'));
					self.setValue(d.format('Y-m-d'));
				}
			}
		},'-',{
			xtype:'combo',
			id:'delete_archive_r_search_field',
			mode:'local',
			width:70,
			triggerAction:'all',
			editable:false,
			displayField:'d',
			valueField:'v',
			value : '1',
			store: new Ext.data.ArrayStore({
				fields:[
					'd','v'	
				],
				data:[					
					['검색어','1'],
					['요청자','2'],
					['승인자','3']
				]
			}),
			listeners:{
				select:{
					fn:function(self,record,index){
						//return_grid.getStore().reload();
					}
				}	
			}
		},{
			xtype: 'textfield',
			id: 'mtrl_id',
			listeners: {
				specialKey: function(self, e){
					if (e.getKey() == e.ENTER) {
						e.stopEvent();
						Ext.getCmp('delete_inform_id').getStore().reload({params:{start: 0}});
					}
				}
			}
		},'-',{
			icon: '/led-icons/find.png',
			//>>text: '조회',
			text: '<?=_text('MN00047')?>',
			handler: function(btn, e){
				Ext.getCmp('delete_inform_id').getStore().reload({params:{start: 0}});
			}
		}]
	});
	
	var tbar2 = new Ext.Toolbar({
		dock: 'top',
        items: [{
			icon: '/led-icons/cross.png',
			text: '항목 삭제',
			tooltip: 'Tape화 된 자료에 한해, Archive스토리지에서 삭제를 합니다.',
			handler : function(btn, e){
				var sel = Ext.getCmp('delete_inform_id').getSelectionModel().getSelections();
				var sel_id = new Array();
				var is_break = false;
				var break_msg = '';

				var request_delete_ids_arr = [];
				var is_request_auth_flag = false;
				if(sel.length>0)
				{					
					
					
					for(var i=0;i<sel.length;i++)
					{
						if( !Ext.isEmpty(sel[i].get('flag')) )
						{
							if( sel[i].get('flag') != 'DME' && sel[i].get('flag') != 'DMR')
							{
								break_msg = '삭제대기, 실패인 항목만 선택 해 주시기 바랍니다.';
								//alert(break_msg);
								is_break = true;	
							}
						}

						if(sel[i].get('flag') =='DMR')
						{							
							request_delete_ids_arr.push(sel[i].get('content_id'));
							is_request_auth_flag = true;
						}
						else 
						{
							sel_id.push(sel[i].get('content_id'));
						}
						
					}
					
					if(is_break)
					{
						Ext.Msg.alert('오류', break_msg);
						return;
					}
					else
					{
						if(is_request_auth_flag)
						{	

							var win = new Ext.Window({
								title: '삭제승인 - 내용',
								width: 360,
								height: 180,
								modal: true,
								border: true,
								frame: true,
								layout: 'fit',
								items:[{
											xtype: 'textarea',
											padding: '0px',
											layout: 'fit',
											id:'remove_auth_comment',
											allowBlank:false,					
											listeners:
											{
												afterrender: function(e)
												{					
													Ext.getCmp('remove_auth_comment').focus(true,200);
												}
											}
										}],
								buttons: [{
											text:'승인',
											scale: 'medium',
											icon: '/led-icons/delete.png',
											handler: function(b, e){

												var remove_auth_comment = Ext.getCmp('remove_auth_comment').getValue();
												if(Ext.isEmpty(remove_auth_comment))
												{
													Ext.Msg.alert("알림","삭제승인 내용을 입력해주세요",function(e)
													{
														Ext.getCmp('remove_auth_comment').focus(true,200);
													});

													return;						
												}
												else 
												{
													Ext.Ajax.request({
														url : '/pages/menu/archive_management/action.php',
														params : {
															ids : Ext.encode(sel_id),
															action : 'archive_storage_delete',
															auth_comment : remove_auth_comment,
															request_ids : Ext.encode(request_delete_ids_arr)
														},
														callback : function(opt, success, res){
															if(success)
															{
																 var msg = Ext.decode(res.responseText);							
																 if(msg.success)
																 {
																	win.close();
																	Ext.Msg.alert(' 완 료 ',msg.msg);	
																	
																	Ext.getCmp('delete_inform_id').getStore().reload();
																	
																 }
																 else {
																	Ext.Msg.alert(' 오 류 ',msg.msg);
																 }
															}
															else 
															{
																 Ext.Msg.alert('서버 오류', res.statusText);
															}
														}
													}) 			
												}
											}
										  },
										  {
												text: '닫기',
												scale: 'medium',				
												handler: function(b, e){
													win.close();
												}
										   }
								]
							}).show();
						}
						else 
						{
							Ext.Msg.show({
								title: '확인',
								msg : '선택하신 목록을 삭제 하시겠습니까?',
								buttons : Ext.Msg.YESNO,
								fn : function(button){
									if(button == 'yes')
									{
										Ext.Ajax.request({
											url : '/pages/menu/archive_management/action.php',
											params : {
												ids : Ext.encode(sel_id),
												action : 'archive_storage_delete'							
											},
											callback : function(opt, success, res){
												if(success)
												{
													 var msg = Ext.decode(res.responseText);							
													 if(msg.success)
													 {
														Ext.Msg.alert(' 완 료',msg.msg);							
														Ext.getCmp('delete_inform_id').getStore().reload();
													 }
													 else {
														Ext.Msg.alert(' 오 류 ',msg.msg);
													 }
												}
												else 
												{
													 Ext.Msg.alert('서버 오류', res.statusText);
												}
											}
										}) 								
									}
								}
							
							}); 
						}
					}		
				}
				else {
					Ext.Msg.alert('오류','선택 된 아이템이 없습니다.');
				}				
			}		
		},'-',{
			icon: '/led-icons/arrow_refresh.png',
			text: '삭제 재시도',
			tooltip: '오류난 작업을 재시도 합니다.<br />(실제 파일이 없는 경우는 삭제완료로 판단하여 성공으로 처리합니다.)<br />',
			handler : function(btn, e){
				var sel = Ext.getCmp('delete_inform_id').getSelectionModel().getSelections();
				var sel_id = new Array();
				var is_break = false;
				var break_msg = '';
				if(sel.length>0)
				{					
					for(var i=0;i<sel.length;i++)
					{
						//console.log(sel[i]);
						//console.log(sel[i].get('content_id'));
						if( !Ext.isEmpty(sel[i].get('flag')) )
						{
							if( sel[i].get('flag') != 'DMR' &&  sel[i].get('flag') != 'DMA' )
							{
								break_msg = '삭제 요청 / 삭제 승인 자료만 선택 해 주시기 바랍니다.';
								is_break = true;	
							}							
						}
						sel_id.push(sel[i].get('content_id'));
					}
					
					if(is_break)
					{
						Ext.Msg.alert('오류', break_msg);
						return;
					}
					else
					{
						Ext.Msg.show({
							title: '확인',
							msg : '오류난 작업을 재시도 합니다.<br />(실제 파일이 없는 경우는 삭제완료로 판단하여 성공으로 처리합니다.)<br />',
							buttons : Ext.Msg.YESNO,
							fn : function(button){
								if(button == 'yes')
								{
									Ext.Ajax.request({
										url : '/pages/menu/archive_management/action.php',
										params : {
											ids : Ext.encode(sel_id),
											action : 'archive_storage_delete_retry'							
										},
										callback : function(opt, success, res){
											if(success)
											{
												 var msg = Ext.decode(res.responseText);							
												 if(msg.success)
												 {
													Ext.Msg.alert(' 완 료',msg.msg);							
													Ext.getCmp('delete_inform_id').getStore().reload();
												 }
												 else {
													Ext.Msg.alert(' 오 류 ',msg.msg);
												 }
											}
											else 
											{
												 Ext.Msg.alert('서버 오류', res.statusText);
											}
										}
									}) 								
								}
							}
						
						}); 
					}		
				}
				else {
					Ext.Msg.alert('오류','선택 된 아이템이 없습니다.');
				}				
			}		
		},'-',{
			icon:'/led-icons/database.png',
			text: '스토리지 정보',
			tooltip: '스토리지 보관기간에 대한 정책을 변경할 수 있습니다.',
			handler : function(btn, e){		
				Ext.Ajax.request({
					url: '/pages/menu/archive_management/archive_storage_info.php',
					params: {},
					callback: function(opt, success, response){
						var res = Ext.decode(response.responseText);
					}
				});
			}
		}]
	});

	return {
		border: false,
		loadMask: true,
		frame:true,
		width:800,
		tbar: new Ext.Container({
			height: 54,
			layout: 'anchor',
			xtype: 'container',
			defaults: {
				anchor: '100%',
				height: 27
			},
			items: [
				tbar1,
				tbar2
			]
		}),
		xtype: 'editorgrid',
		clicksToEdit: 1,
		id: 'delete_inform_id',
		loadMask: true,
		columnWidth: 1,
		store: delete_store,
		disableSelection: true,
		listeners: {
			viewready: function(self){
				self.store.load({
					params: {						
						start: 0,
						limit: delete_inform_size,
						start_date: Ext.getCmp('start_date').getValue().format('Ymd000000'),
						end_date: Ext.getCmp('end_date').getValue().format('Ymd240000'),
						arc_start_date: Ext.getCmp('r_arc_start_date').getValue().format('Ymd000000'),
						arc_end_date: Ext.getCmp('r_arc_end_date').getValue().format('Ymd240000')
					}
					
				});
				//self.add(tbar2);
			},
			cellclick : function(self, rowIndex, columnIndex, e ){
				var record    = self.getStore().getAt(rowIndex);
				var fieldName = self.getColumnModel().getDataIndex(columnIndex); // Get field name
				var data      = record.get(fieldName);
				var request_id   = record.get('request_id');
				
				if(fieldName == 'request_comment' || fieldName == 'auth_comment')
				{
					var action = 'request_modify';
					var win_title = '요청사유';
					if(fieldName == 'auth_comment')
					{
						action = 'auth_modify';
						win_title = '승인내용';
					}
					var win = new Ext.Window({
						width : 300,
						height : 160,
						layout: 'fit',
						modal : true,
						title :win_title,
						frame : true,
						items:[{
							xtype: 'textarea',
							padding: '0px',
							layout: 'fit',
							id:'request_comment',
							allowBlank:false,					
							listeners:
							{
								afterrender: function(e)
								{					
									if(!Ext.isEmpty(data))
									{
										Ext.getCmp('request_comment').setValue(data);
									}
									Ext.getCmp('request_comment').focus(true,200);
								}
							}
							}],
							buttons: [{
										text:'수정',
										scale: 'medium',
										icon: '/led-icons/delete.png',
										handler: function(b, e){
											var del_request_comment = Ext.getCmp('request_comment').getValue();
											if(Ext.isEmpty(del_request_comment))
											{
												Ext.Msg.alert("알림","삭제요청 사유를 입력해주세요",function(e)
												{
													Ext.getCmp('request_comment').focus(true,200);
												});															
											}
											else 
											{
												Ext.Ajax.request({
													url : '/store/update_request_comnt.php',
													params : {
														request_id : request_id,
														action : action,
														request_comment : del_request_comment
													},
													callback : function(opt, success, res){
														if(success)
														{
															 var msg = Ext.decode(res.responseText);							
															 if(msg.success)
															 {
																win.close();
																//Ext.Msg.alert(' 완 료',msg.msg);							
																Ext.getCmp('delete_inform_id').getStore().reload();
															 }
															 else {
																Ext.Msg.alert(' 오 류 ',msg.msg);
															 }
														}
														else 
														{
															 Ext.Msg.alert('서버 오류', res.statusText);
														}
													}
												}) 		
											}
										}
									},
									{
										text:'닫기',
										scale: 'medium',										
										handler: function(b, e){
											win.close();
										}
									}
							]
								
						}).show();
				}
				
			},
			rowdblclick2: {
						fn: function(self, rowIndex, e){						
							var index =0;	
							var sm = self.getSelectionModel();			
							var sel = Ext.getCmp('delete_inform_id').getSelectionModel().getSelections();
							if(sel.length>0)
							{	
								for(var i=0;i<sel.length;i++)
								{ 																	
									index = Ext.getCmp('delete_inform_id').getStore().indexOf(sel[i]);
									sm.selectRow(index,true);										
								}
							}
							if(sm.isSelected(rowIndex))
							{
								sm.deselectRow(rowIndex);	
							}
							else  sm.selectRow(rowIndex,true);
						}
					},
			rowdblclick: {
				fn: function(self, rowIndex, e){						
					var sm = self.getSelectionModel().getSelected();							

					var content_id = sm.get('content_id');	
					var req_comment = sm.get('req_comment');
					var mtrl_id = sm.get('mtrl_id');				

					var that = self;;
					if(!Ext.Ajax.isLoading(self.isOpen))
					{
						self.load = new Ext.LoadMask(Ext.getBody(), {msg: _text('MSG00143')});
						self.load.show();
						Ext.Ajax.request({
						url: '/interface/archive_interface/get_syncdata_one.php',
						params: {
							content_id: content_id
						},
						callback: function(self, success, response) {
							self.isOpen = Ext.Ajax.request({
								url: '/javascript/ext.ux/Ariel.DetailWindow.php',
								params: {
									content_id: content_id,
									record: Ext.encode(sm.json)
								},
								callback: function(self, success, response){
									if (success)
									{
										that.load.hide();
										try
										{
											var r = Ext.decode(response.responseText);
											if ( r !== undefined && !r.success)
											{
												Ext.Msg.show({
													title: '경고'
													,msg: r.msg
													,icon: Ext.Msg.WARNING
													,buttons: Ext.Msg.OK
												});
											}
										}
										catch (e)
										{
											//alert(response.responseText)
											//Ext.Msg.alert(e['name'], e['message'] );
										}
									}
									else
									{
										//>>Ext.Msg.alert('서버 오류', response.statusText+'('+response.status+')');
										Ext.Msg.alert(_text('MN00022'), response.statusText+'('+response.status+')');
									}
								}
							});

							Ext.Ajax.request({
									url: '/interface/archive_interface/get_keyframe_one.php',
									params: {
										content_id: content_id
									},
									callback: function(self, success, response){
										
									}
								});
							}
						});
					}
				}
			}
			//sortchange : sortChanges
			
		},
	
		sm : selModel,
		
		cm: new Ext.grid.ColumnModel({
			defaults:{
				sortable: true
			},

			columns: [				
				new Ext.grid.RowNumberer(),
				selModel,
				{header: 'content_id', dataIndex: 'content_id',hidden:true},				
				{header: '구분',dataIndex:'flag_nm',align:'center',sortable:'true',width:80},
				{header: '구분code',dataIndex:'flag',align:'center',sortable:'true',width:120,hidden: true},
				//{header: '파일종류',dataIndex:'media_type',align:'center',sortable:'true',width:100},
				{header: '저장경로',dataIndex:'contentType',align:'center',sortable:'true',width:100},
				//{header: '카테고리위치', dataIndex: 'category', align:'left',sortable:'true',width:200},
				{header: 'Asset ID', dataIndex:'asset_id',align:'center',sortable:'true',width:110,editor: new Ext.form.TextField({
							allowBlank: true,
							readOnly: true
						})},
				{header: 'Material ID', dataIndex:'mtrl_id',align:'center',sortable:'true',width:100,editor: new Ext.form.TextField({
							allowBlank: true,
							readOnly: true
						})},
				{header: '제목', dataIndex:'title',align:'left',sortable:'true',width:300},
				//{header: '파일크기', dataIndex:'file_size',align:'center',sortable:'true',width:80},
				//{header: '파일경로', dataIndex: 'path',align:'left',sortable:'true',width:120},
				//{header: '등록자', dataIndex:'reg_user_id',align:'center',sortable:'true',width:100}	,
				{header: '승인일자', dataIndex:'arc_time',align:'center',sortable:'true',width:120 ,renderer: Ext.util.Format.dateRenderer('Y-m-d'),sortable:'true',width:100},
				{header: 'Tape완료일자', dataIndex:'created_date',align:'center',sortable:'true',width:120 ,renderer: Ext.util.Format.dateRenderer('Y-m-d'),sortable:'true',width:100},
				{header: 'Archive삭제예정일', dataIndex:'del_exp_date',align:'center',sortable:'true',width:70},
				{header: '요청자', dataIndex:'request_user_info',align:'center',sortable:'true',width:70,renderer: showEmpty},
				{header: '요청일시', dataIndex:'request_date',align:'center',sortable:'true',width:70,renderer: showDate ,sortable:'true',width:100},
				{header: '요청사유', dataIndex:'request_comment',align:'center',sortable:'true',width:120,renderer: function (value, metadata, record, rowIdx, colIdx, store)
				{											
					value = Ext.util.Format.htmlEncode(value);
					
					if(value)
					{
						metadata.attr = 'ext:qtip=\"<div class=memo_style><font size=3>'+value+'</font></div>\"';
						 //metadata.attr = 'ext:qtip="' + value + '"';
						
					}
					else value = '-';
					return value;
				}},
				{header: '승인자', dataIndex:'auth_user_info',align:'center',sortable:'true',width:70,renderer: showEmpty},
				{header: '승인일시', dataIndex:'auth_date',align:'center',sortable:'true',width:70,renderer: showDate,sortable:'true',width:100},
				{header: '승인내용', dataIndex:'auth_comment',align:'center',sortable:'true',width:120,renderer: function (value, metadata, record, rowIdx, colIdx, store)
				{											
					value = Ext.util.Format.htmlEncode(value);
					
					if(value)
					{
						metadata.attr = 'ext:qtip=\"<div class=memo_style><font size=3>'+value+'</font></div>\"';
						 //metadata.attr = 'ext:qtip="' + value + '"';
						
					}
					else value = '-';
					return value;
				}}
				//{header: '만료기한', dataIndex:'auth_date',align:'center',sortable:'true',width:120 ,renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s'),sortable:'true',width:100}
			]
		}),

		view: new Ext.ux.grid.BufferView({
			rowHeight: 20,
			scrollDelay: false,
			emptyText: '결과 값이 없습니다.'
		}),
	
		bbar: new Ext.PagingToolbar({
			store: delete_store,
			pageSize: delete_inform_size,
			items:[{
				id : 'toolbartext',
				xtype:'tbtext',
				pageX:'100',
				pageY:'100',				
				text : "리스트 수 : "+total_list
			},'->',{
				xtype: 'displayfield',
				id: 'storage_info',
				value: '<?=$info?>'
			},{
				xtype: 'displayfield',
				width: 20
			},{
				xtype: 'displayfield',
				id: 'storage_info2',
				value: '<?=$info2?>'
			}]
		})

	}
})()
