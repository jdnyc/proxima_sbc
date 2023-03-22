<?php

/////////////////////////////
// Tape아카이브 삭제 관리 보여주는 페이지 
// 2011.12.15 
// by 허광회
/////////////////////////////

session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
$diva_info_msg = getDivaStorageInfo();
?>		  

(function(){
     
	var total_list = 0;
	var delete_inform_size = 100;

	function show_confirm_win(sel_id)
	{	
	
		var confirm_win = new Ext.Window({
				title: '확인',
				width: 250,															
				modal: true,
				border: true,
				frame: true,
				padding: '3px',	
				buttonAlign: 'center',
				items:
				[
					{
						xtype: 'displayfield',
						value : '<center><p style="font-weight:bold;height:30px;line-height:30px;">선택하신 목록을 승인 하시겠습니까??</p></center>'
					},
					{
						xtype: 'displayfield',
						value : '<p style="height:20px;line-height:20px;">승인내용</p>'
					},
					{
						xtype:'textarea',
						layout: 'fit',
						width: 230,
						id:'confirm_auth_comment'
					}
				
				],
				buttons:[{
							text:'예',
							scale: 'medium',
							icon: '/led-icons/accept.png',
							handler: function(b, e){
									Ext.Ajax.request({
											url : '/pages/menu/archive_management/action.php',
											params : {
												ids : Ext.encode(sel_id),
												action : 'accept',
												auth_comment: Ext.getCmp('confirm_auth_comment').getValue()
											},
											callback : function(opt, success, res){
												if(success)
												{
													 var msg = Ext.decode(res.responseText);							
													 if(msg.success)
													 {
														Ext.Msg.alert(' 완 료',msg.msg);		
													 }
													 else {
														Ext.Msg.alert(' 오 류 ',msg.msg);
													 }
												}
												else 
												{
													 Ext.Msg.alert('서버 오류', res.statusText);
												}

												confirm_win.close();
												archive_manage_grid.getStore().reload();
											}
										}); 								
							}
						 },
						 {
							text:'아니오',
							scale: 'medium',
							icon: '/led-icons/cross.png',
							handler: function(b, e){
								confirm_win.close();
							}
						 }
				
				]
		}).show();
	}
	
	var selModel = new Ext.grid.CheckboxSelectionModel({             
	     singleSelect : false,
	     checkOnly : false
	     
	    
	 });

	 function showEmpty(value){
		if(Ext.isEmpty(value)) value = '-';
		return value;
	}

	function showDate(value){
		if(Ext.isEmpty(value)) value = '-';
		else value = Ext.util.Format.date(value,'Y-m-d H:i:s');
		return value;
	}
    
	var delete_store = new Ext.data.JsonStore({
		url:'/pages/menu/archive_management/get.php',
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
			{name: 'path'},			
			{name: 'created_time',type:'date',dateFormat:'YmdHis'},
			{name: 'arc_time',type:'date',dateFormat:'YmdHis'},
//			{name: 'delete_date', type: 'date',dateFormat: 'YmdHis'},
			{name: 'reg_user_id'},
			{name: 'exp_date'},
			{name: 'expired_date',type: 'date',dateFormat: 'YmdHis'},
			{name: 'delete_result'},
			{name: 'file_size'},
			{name: 'flag'},
			'media_type',
			'deleted_date',
			'flag_nm',
			'ud_system',
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
				var search_val = Ext.getCmp('archive_manage_delete_combo').getValue();

				opts.params = opts.params || {};

				Ext.apply(opts.params, {
					start_date: Ext.getCmp('archive_manage_start_date').getValue().format('Ymd000000'),
					end_date: Ext.getCmp('archive_manage_end_date').getValue().format('Ymd240000'),
					genre_category: Ext.getCmp('archive_manage_genre_category').getValue(),
					ud_content: Ext.getCmp('archive_manage_ud_combo').getValue(),
					action : search_val,
					search_feild : Ext.getCmp('delete_archive_search_field').getValue(),
					mtrl_id: Ext.getCmp('archive_manage_mtrl_id').getValue()
				});				
						
			},
			load: function(self, opts){
				total_list = self.getTotalCount();	
				var tooltext = "( 검색된 미디어 수 : <font color=blue><b>"+total_list +"</b></font> )"; 			
				Ext.getCmp('archive_manage_toolbartext').setText(tooltext);					
			}
		}
	});

	var tbar1 = new Ext.Toolbar({
		dock: 'top',
		items: [' 구분 : ',{
			xtype:'combo',
			id:'archive_manage_delete_combo',
			mode:'local',
			width:80,
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
					['승인대기','<?=ARCHIVE_QUEUE?>'],
					['승인','<?=ARCHIVE_ACCEPT?>'],
					['성공','<?=ARCHIVE_COMPLETE?>'],
					['실패','<?=ARCHIVE_ERROR?>'],
					['삭제요청','<?=ARCHIVE_REQUEST_DELETE?>'],
					['삭제승인','<?=ARCHIVE_REQUEST_DELETE_ACCEPT?>'],
					['삭제','<?=ARCHIVE_DELETE?>']
				]
			}),
			listeners:{
				select:{
					fn:function(self,record,index){
						archive_manage_grid.getStore().reload();
					}
				}	
			}
		},'-',' 저장경로 : ',{
			xtype:'combo',
			id:'archive_manage_ud_combo',
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
						archive_manage_grid.getStore().reload();
					}
				}	
			}
		},'-','승인일자',
		{
			xtype: 'datefield',
			id: 'archive_manage_start_date',
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
			id: 'archive_manage_end_date',
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
		},'-','장르',{
			xtype: 'treecombo',
			id: 'archive_manage_genre_category',
			fieldLabel: '장르',
			treeWidth: '400',
			width: '90%',
			autoScroll: true,
			pathSeparator: '>',
			rootVisible: true,
			name: 'c_category_id',
			value: '',
			listeners: {
				render: function(self){										
					var path = '0/';
					if(!Ext.isEmpty(path)){
						path = path.split('/');											
						var catId = path[path.length-1];
						if(path.length <= 1)
						{
							self.setValue('');
							self.setRawValue('');
						}
						else
						{
							self.setValue(catId);												
							self.setRawValue('Archive MAM');
						}
					}										
				}
			},
			loader: new Ext.tree.TreeLoader({
				url: '/store/get_categories.php',
				baseParams: {
					action: 'get-folders',
					path: ''
				}
			}),
			root: new Ext.tree.AsyncTreeNode({
				id: 0,
				text: 'Archive MAM',
				expanded: true
			})
		},'-',{
			xtype:'combo',
			id:'delete_archive_search_field',
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
						//archive_manage_grid.getStore().reload();
					}
				}	
			}
		},{
			xtype: 'textfield',
			id: 'archive_manage_mtrl_id',
			listeners: {
				specialKey: function(self, e){
					if (e.getKey() == e.ENTER) {
						e.stopEvent();
						archive_manage_grid.getStore().reload({params:{start: 0}});
					}
				}
			}
		},'-',{
			icon: '/led-icons/find.png',
			//>>text: '조회',
			text: '<?=_text('MN00047')?>',
			handler: function(btn, e){
				archive_manage_grid.getStore().reload({params:{start: 0}});
			}
		}]
	});

	var tbar2 = new Ext.Toolbar({
		dock: 'top',
        items: [{
			icon: '/led-icons/accept.png',
			text: 'Tape화 승인',
			tooltip: '선택한 항목들을 Tape화 시킵니다.<br />',
			handler : function(btn, e){
				var sel = archive_manage_grid.getSelectionModel().getSelections();
				var sel_id = new Array();
				var is_break = false;
				var break_msg = '';
				
				if(sel.length>0)
				{		
					
					for(var i=0;i<sel.length;i++)
					{
						if( !Ext.isEmpty(sel[i].get('flag')) )
						{
							break_msg = '승인대기인 항목만 선택 해 주시기 바랍니다.';
							is_break = true;
						}
						sel_id.push(sel[i].data);
					}
					
					if(is_break)
					{
						Ext.Msg.alert('오류', break_msg);
						return;
					}
					else
					{
						show_confirm_win(sel_id);
						return;
						Ext.Msg.show({
							title: '확인',
							msg : '선택하신 목록을 승인 하시겠습니까?',
							buttons : Ext.Msg.YESNO,
							fn : function(button){
								if(button == 'yes')
								{
									Ext.Ajax.request({
										url : '/pages/menu/archive_management/action.php',
										params : {
											ids : Ext.encode(sel_id),
											action : 'accept'							
										},
										callback : function(opt, success, res){
											if(success)
											{
												 var msg = Ext.decode(res.responseText);							
												 if(msg.success)
												 {
													Ext.Msg.alert(' 완 료',msg.msg);							
													archive_manage_grid.getStore().reload();
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
			icon: '/led-icons/arrow_undo.png',
			text: '승인취소',
			tooltip: '선택한 항목들을 승인취소 합니다.',
			handler : function(btn, e){
				var sel = archive_manage_grid.getSelectionModel().getSelections();
				var sel_id = new Array();
				var is_break = false;
				var break_msg = '';
				if(sel.length>0)
				{					
					for(var i=0;i<sel.length;i++)
					{
						if( !Ext.isEmpty(sel[i].get('flag')) )
						{
							if(  sel[i].get('flag') != 'accept' )
							{
								break_msg = '승인, 승인대기인 항목만 선택 해 주시기 바랍니다.';
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
							msg : '선택하신 목록을 승인취소 하시겠습니까?',
							buttons : Ext.Msg.YESNO,
							fn : function(button){
								if(button == 'yes')
								{
									Ext.Ajax.request({
										url : '/pages/menu/archive_management/action.php',
										params : {
											ids : Ext.encode(sel_id),
											action : 'accept_cancel'							
										},
										callback : function(opt, success, res){
											if(success)
											{
												 var msg = Ext.decode(res.responseText);							
												 if(msg.success)
												 {
													Ext.Msg.alert(' 완 료',msg.msg);							
													archive_manage_grid.getStore().reload();
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
			icon:'/led-icons/cross.png',
			text: 'Tape삭제',
			tooltip: '<font color=red>※주의 : 실제 Tape에서 파일이 삭제됩니다.</font>',
			handler : function(btn, e){
				var sel = archive_manage_grid.getSelectionModel().getSelections();
				var sel_id = new Array();
				var now = new Date();
				var is_break = false;
				var break_msg = '';
				var is_request_auth_flag = false;
				var request_delete_ids_arr = [];				

				if(sel.length>0)
				{
					for(var i=0;i<sel.length;i++)
					{
						
						if(sel[i].get('flag') != 'complete' && sel[i].get('flag') != 'req_delete' )
						{
							break_msg = 'Tape아카이브된 대상만 선택 해 주시기 바랍니다.';
							is_break = true;
												
						}	
						
						if(sel[i].get('flag') =='req_delete')
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
															action : 'delete',
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
																	archive_manage_grid.getStore().reload();																	
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
								title: '알림',
								msg : '선택하신 목록을 Tape아카이브에서 삭제 하시겠습니까?',
								buttons : Ext.Msg.YESNO,
								fn : function(button){
									if(button == 'yes')
									{
										Ext.Ajax.request({
											url : '/pages/menu/archive_management/action.php',
											params : {
												ids : Ext.encode(sel_id),
												action : 'delete'				
											},
											callback : function(opt, success, res){
												if(success)
												{
													 var msg = Ext.decode(res.responseText);							
													 if(msg.success)
													 {
														Ext.Msg.alert(' 완 료',msg.msg);							
														archive_manage_grid.getStore().reload();
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
		},{
			hidden: true,
			icon:'/led-icons/arrow_refresh.png',
			text: '수동 상세메타 동기화',
			tooltip: 'Archive솔루션 내의 항목들의 메타데이터를 수동으로 동기화 합니다<br />'+
				'(기본적으로 하루에 한 번 자동 동기화가 이루어집니다.)',
			handler : function(btn, e){			
				Ext.Msg.show({
					title: '확인',
					msg: 'Archive솔루션 내의 항목들의 메타데이터를 수동으로 동기화 합니다<br />'+
						'(기본적으로 하루에 한 번 자동 동기화가 이루어집니다.)',
					icon: Ext.Msg.INFO,
					buttons: Ext.Msg.OKCANCEL,
					fn: function(btnId){
						if (btnId == 'ok')
						{
							archive_manage_grid.el.mask('동기화 요청 중 입니다...');
							Ext.Ajax.request({
								url: '/interface/archive_interface/archive_detail_daily.php',
								params: {},
								callback: function(opt, success, response)
								{
									archive_manage_grid.el.unmask();
									Ext.Msg.alert('알림', '동기화 작업이 시작되었습니다.');
								}
							});
						}
					}
				});
			}	
		},'-',
		{
			icon: '/led-icons/application_put.png',
			text: '콘텐츠 유형 변경',
			tooltip: '콘텐츠 유형을 NDS -> PDS / PDS->NDS 로 변경할 수 있습니다.',
			handler : function(btn,e){

				var sel = archive_manage_grid.getSelectionModel().getSelections();
				if(sel.length <1)
				{	
					Ext.Msg.alert("알림","선택한 것이 없습니다.");
					return;
				}

				
				Ext.Msg.confirm("알림","유형 수정할 경우 컨텐츠의 장르값이 초기화 됩니다.<br>유형을 변경하시겠습니까?",
								function(btn)
								{
									if(btn === 'yes')
									{
										var sel = archive_manage_grid.getSelectionModel().getSelections();
										var is_break = false;
										var break_msg = '';
										var is_break_add = false;
										var is_break_add_no = '';
										var archive_request_content_id_arr = [];
										var sel_id = [];
										var archive_request_map_arr = [];
										var is_break_add_no_arr = [];

										var change_req_no = [];
										
										if(sel.length == 1)
										{					
											for(var i=0;i<sel.length;i++)
											{	
												if( sel[i].get('flag') != "")
												{
													break_msg = '승인대기 항목을 선택 해 주시기 바랍니다.';
													is_break = true;
												}				
												else 
												{
													sel_id.push(sel[i].get('content_id'));
												}
											}

											if(is_break)
											{
												Ext.Msg.alert("알림",break_msg);
												return;
											}
											else 
											{
												//실제 변경해야할 소재..

												Ext.Ajax.request({
													url: '/pages/menu/archive_management/action.php',
													params: {
															ids : Ext.encode(sel_id),												
														  action: 'change_udsystem'							
														
													},
													callback : function(opt, success, res){
														
														 var msg = Ext.decode(res.responseText);							
														 if(msg.success)
														 {												
															Ext.Msg.alert(' 완 료',msg.msg);		
															archive_manage_grid.getStore().reload();
														 }
														 else {
															Ext.Msg.alert(' 오 류 ',msg.msg);
														 }
														
														archive_request_grid.getStore().reload();
														
													}
												});
											}
										}
										else 
										{
											Ext.Msg.alert("알림","선택된 항목이 없습니다.");
											return;
										}
									}
									else 
									{
									}
								});


				
				
			}		
		}
		,'-',{
			icon:'/led-icons/arrow_refresh.png',
			text: '수동 장르 동기화',
			tooltip: '장르 정보를 수동으로 동기화 합니다<br />'+
				'(장르 정보가 변경 시 사용자가 수동으로 돌려주어야 합니다.)',
			handler : function(btn, e){			
				Ext.Msg.show({
					title: '확인',
					msg: '장르 정보를 수동으로 동기화 합니다<br />'+
						'(장르 정보가 변경 시 사용자가 수동으로 돌려주어야 합니다.)',
					icon: Ext.Msg.INFO,
					buttons: Ext.Msg.OKCANCEL,
					fn: function(btnId){
						if (btnId == 'ok')
						{
							archive_manage_grid.el.mask('동기화 중 입니다...');
							Ext.Ajax.request({
								url: '/interface/app/client/common.php',
								params: {
									mode: 'GetArchiveGenre'
								},
								callback: function(opt, success, response)
								{
									archive_manage_grid.el.unmask();
									Ext.Msg.alert('알림', '동기화 되었습니다.');
								}
							});
						}
					}
				});
			}	
		}]
	});

	
	var archive_manage_grid = new Ext.grid.EditorGridPanel({
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
		loadMask: true,
		columnWidth: 1,
		store: delete_store,
		disableSelection: true,
		listeners: {
			viewready: function(self){
				self.store.load();				
			},
			cellclick : function(self, rowIndex, columnIndex, e ){
				var record    = self.getStore().getAt(rowIndex);
				var fieldName = self.getColumnModel().getDataIndex(columnIndex); // Get field name
				var data      = record.get(fieldName);
				var request_id   = record.get('request_id');
				
				if(fieldName == 'request_comment' || fieldName == 'auth_comment')
				{
					var action = 'request_modify';
					if(fieldName == 'auth_comment')
					{
						action = 'auth_modify';
					}
					var win = new Ext.Window({
						width : 300,
						height : 160,
						layout: 'fit',
						modal : true,
						title : '요청사유',
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
																archive_manage_grid.getStore().reload();
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
					var sel = archive_manage_grid.getSelectionModel().getSelections();
					if(sel.length>0)
					{	
						for(var i=0;i<sel.length;i++)
						{ 																	
							index = archive_manage_grid.getStore().indexOf(sel[i]);
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
				{header: '구분',dataIndex:'flag_nm',align:'center',sortable:'true',width:70},
				{header: '구분code',dataIndex:'flag',align:'center',sortable:'true',width:120,hidden: true},
				//{header: '파일종류',dataIndex:'media_type',align:'center',sortable:'true',width:100},
				{header: '콘텐츠유형',dataIndex:'contentType',align:'center',sortable:'true',width:100},
				//{header: '카테고리위치', dataIndex: 'category', align:'left',sortable:'true',width:200},				
				{header: 'Material ID', dataIndex:'mtrl_id',align:'center',sortable:'true',width:110,editor: new Ext.form.TextField({
							allowBlank: true,
							readOnly: true
						})},
				{header: '파일명', dataIndex:'path',align:'center',sortable:'true',width:150,editor: new Ext.form.TextField({
							allowBlank: true,
							readOnly: true
						})},
				{header: '제목', dataIndex:'title',align:'left',sortable:'true',width:350,renderer:showEmpty},
				//{header: '파일크기', dataIndex:'file_size',align:'center',sortable:'true',width:80},
				//{header: '파일경로', dataIndex: 'path',align:'left',sortable:'true',width:120},
				//{header: '등록자', dataIndex:'reg_user_id',align:'center',sortable:'true',width:100}	,
				{header: '승인일자', dataIndex:'arc_time',align:'center',sortable:'true',width:120 ,renderer: Ext.util.Format.dateRenderer('Y-m-d'),sortable:'true',width:100},
				{header: 'Tape화 예정일', dataIndex:'exp_date',align:'center',sortable:'true',width:100 ,renderer:showEmpty},
				{header: '요청자', dataIndex:'request_user_info',align:'center',sortable:'true',width:70,renderer:showEmpty},
				{header: '요청일시', dataIndex:'request_date',align:'center',sortable:'true',width:70,renderer: showDate,sortable:'true',width:100},
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
				{header: '승인자', dataIndex:'auth_user_info',align:'center',sortable:'true',width:70,renderer:showEmpty},
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
			]
		}),

		view: new Ext.ux.grid.BufferView({
			rowHeight: 20,
			scrollDelay: false,
			emptyText: '결과 값이 없습니다.'
		}),
		viewConfig : {
			enableTextSelection : true
		},
	
		bbar: new Ext.PagingToolbar({
			store: delete_store,
			pageSize: delete_inform_size,
			items:[{
				id: 'archive_manage_toolbartext',
				xtype:'tbtext',
				pageX:'100',
				pageY:'100',				
				text : "리스트 수 : "+total_list
			},'->',{
				xtype: 'displayfield',
				value: '<?=$diva_info_msg?>'
			}]
		})

	});

	return archive_manage_grid;
	
})()
