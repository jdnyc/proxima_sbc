<?php

/////////////////////////////
// CHA 아카이브 요청 관리
// 2014 07 31	이승수
/////////////////////////////

session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

//$arr_info_msg = getStoragePolicyInfo();
$arr_info_msg = array(
	'info1_2' => 'info1',
	'info2_2' => 'info2'
);

$info = $arr_info_msg['info1_2'];
$info2 = $arr_info_msg['info2_2'];

$diva_info_msg = getDivaStorageInfo();

?>		  

(function(){			
	
	var total_list = 0;
	var delete_inform_size = 100;
	
	function show_confirm_win(rs_req_no)
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
					value : '<center><p style="font-weight:bold;height:30px;line-height:30px;">선택하신 요청목록을 승인 하시겠습니까?</p></center>'
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
						is_click : false,
						handler: function(b, e){								
									confirm_win.el.mask();
								
									Ext.Ajax.request({
										url: '/pages/menu/archive_management/action_archive_request.php',
										params: {
											action: 'accept',
											'req_no[]': rs_req_no,
											 auth_comment: Ext.getCmp('confirm_auth_comment').getValue()
										},
										callback : function(opt, success, res){
											confirm_win.el.unmask();
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
											archive_request_grid.getStore().reload();
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

	//자동 새로고침에 사용하는 변수

	
	var archive_request_store = new Ext.data.JsonStore({
		url:'/pages/menu/archive_management/get_archive_request.php',
		root: 'data',
		totalProperty : 'total_list',		
		idProperty: 'req_no',
		baseParams: {
			 start : 0,
			 limit : 100
		},
		fields: [
			'req_no',
			{name: 'req_time',type:'date',dateFormat:'YmdHis'},
			'arc_type',
			'status',
			'content_id',
			'user_nm',
			'mtrl_id',
			'meta_mtrl_nm',
			'meta_pgm_id',
			'meta_pgm_nm',
			'ud_content_title',
			'ud_content_id',
			'req_comment',
			'ud_system',
			'target_ud_system',
			'title',
			'pgm_id',
			'pgm_nm',
			'mgmt_id',
			'new_mtrl_id',
			'req_user_info',
			'arc_user_info',
			{name: 'arc_time',type:'date',dateFormat:'YmdHis'},			
			'arc_comment',
			'src_device_id',
			{name: 'done_time',type:'date',dateFormat:'YmdHis'}
		],
		 
		listeners: {
			beforeload: function(self, opts){
				opts.params = opts.params || {};

				Ext.apply(opts.params, {
					arc_start_date: Ext.getCmp('archive_request_arc_start_date').getValue().format('Ymd000000'),
					arc_end_date: Ext.getCmp('archive_request_arc_end_date').getValue().format('Ymd240000'),
					arc_type: Ext.getCmp('archive_request_ud_combo').getValue(),
					arc_status: Ext.getCmp('archive_request_status_combo').getValue(),
					mtrl_id : Ext.getCmp('archive_request_mtrl_id').getValue(),
					search_field: Ext.getCmp('archive_request_search_field').getValue()
				});
						
			},
			load: function(self, opts){				
				total_list = self.getTotalCount();	
				var tooltext = "( 검색된 미디어 수 : <font color=blue><b>"+total_list +"</b></font> )";
				Ext.getCmp('archive_request_toolbartext').setText(tooltext);
				
				var storage_info = self.reader.jsonData.info;
				Ext.getCmp('archive_request_storage_info').setValue(storage_info);
				var storage_info2 = self.reader.jsonData.info2;
				Ext.getCmp('cache_storage_info').setValue(storage_info2);
			}
			
			//load: sortChanges
		}
	});

	var tbar1 = new Ext.Toolbar({
		dock: 'top',
		items: [' 의뢰구분',{
			xtype:'combo',
			id:'archive_request_ud_combo',
			mode:'local',
			width:90,
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
					['아카이브','archive'],
					['리스토어','restore'],
					['Partial 리스토어','pfr_restore']
				]
			}),
			listeners:{
				select:{
					fn:function(self,record,index){
						archive_request_grid.getStore().reload();
					}
				}	
			}
		},'-',' 의뢰상태',{
			xtype:'combo',
			id:'archive_request_status_combo',
			mode:'local',
			width:90,
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
					['대기','<?=CHA_REQ?>'],
					['반려','<?=CHA_REJT?>'],
					['승인완료','<?=CHA_ACPT?>'],
					['작업완료(R)','<?=CHA_FIN_NL?>'],
					['작업중(R)','<?=CHA_PRG_NL?>'],
					['작업실패(R)','<?=CHA_ERR_NL?>'],
					['작업완료(T)','<?=CHA_FIN?>'],
					['작업중(T)','<?=CHA_PRG?>'],
					['작업실패(T)','<?=CHA_ERR?>']
				]
			}),
			listeners:{
				select:{
					fn:function(self,record,index){
						archive_request_grid.getStore().reload();
					}
				}	
			}
		},
		'-','의뢰일시',
		{
			xtype: 'datefield',
			id: 'archive_request_arc_start_date',
			editable: true,
			width: 105,
			format: 'Y-m-d',
			altFormats: 'Ymd|ymd|Y-m-d|y-m-d|Y/m/d|Y/m/d',
			listeners: {
				render: function(self){
					var d = new Date();

					self.setMaxValue(d.format('Y-m-d'));
					self.setValue(d.add(Date.DAY, -3).format('Y-m-d'));
				}
			}
		},'~' 
		,{
			xtype: 'datefield',
			id: 'archive_request_arc_end_date',
			editable: true,
			width: 105,
			format: 'Y-m-d',
			altFormats: 'Ymd|ymd|Y-m-d|y-m-d|Y/m/d|Y/m/d',
			listeners: {
				render: function(self){
					var d = new Date();

					self.setMaxValue(d.format('Y-m-d'));
					self.setValue(d.format('Y-m-d'));
				}
			}
		},'-','프로그램',{
			xtype: 'treecombo',
			id: 'archive_request_pgm_category',
			//fieldLabel: '프로그램',
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
				url: '/store/cha_get_category_program.php',
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
			id:'archive_request_search_field',
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
					['의뢰자','2'],
					['승인자','3']
				]
			}),
			listeners:{
				select:{
					fn:function(self,record,index){
						//archive_request_grid.getStore().reload();
					}
				}	
			}
		},{
			xtype: 'textfield',
			id: 'archive_request_mtrl_id',
			listeners: {
				specialKey: function(self, e){
					if (e.getKey() == e.ENTER) {
						e.stopEvent();
						//var params = Ext.getCmp('archive_request_search_field').getValue();
						archive_request_grid.getStore().reload({params:{start: 0}});
					}
				}
			}
		},'-',{
			icon: '/led-icons/find.png',
			//>>text: '조회',
			text: '<?=_text('MN00047')?>',
			handler: function(btn, e){
				//var params = Ext.getCmp('archive_request_search_field').getValue();
				archive_request_grid.getStore().reload({params:{start: 0}});
			}
		}]
	});



	
	var tbar2 = new Ext.Toolbar({
		dock: 'top',
		items: [{
			icon: '/led-icons/accept.png',
			text: '승인',
			tooltip: '요청된 항목을 승인합니다.',
			handler : function(btn, e){
				var sel = archive_request_grid.getSelectionModel().getSelections();
				var is_break = false;
				var break_msg = '';
				var is_break_add = false;
				var is_break_add_no = '';
				var rs_req_no = [];

				var is_break_add_no_arr = [];
				
				//console.log(sel);
				var archive_request_arr = [];
				var archive_request_content_id_arr = [];
				var archive_request_mtrl_id_arr = [];
				var archive_request_map_arr = {};
				var is_check_archive_metadata = false;
				var dup_request_arr = [];

				for(var i=0;i<sel.length;i++)
				{
					if(sel[i].get('arc_type') == 'archive' && sel[i].get('status') == <?=CHA_REQ?>)
					{
						archive_request_content_id_arr.push(sel[i].get('content_id'));
						archive_request_mtrl_id_arr.push(sel[i].get('mtrl_id'));
						archive_request_map_arr[sel[i].get('content_id')] = sel[i].get('mtrl_id');
						is_check_archive_metadata = true;						
					}
				}			
				
				if(sel.length>0)
				{					
					for(var i=0;i<sel.length;i++)
					{	
						if( sel[i].get('status') != <?=CHA_REQ?>
						 && sel[i].get('status') != <?=CHA_ERR?>)
						{
							break_msg = '요청중인 항목을 선택 해 주시기 바랍니다.';
							is_break = true;
						}

						if( Ext.isEmpty(sel[i].get('ud_system')) ) {
							//ud_system이 없는것 승인시엔, 지정해줘야함. 한 건씩 동작
							is_break_add = true;
							is_break_add_no = sel[i].get('req_no');
							is_break_add_no_arr.push(is_break_add_no); // 출처가 없는 req_no 배열
						}
						else 
						{
							rs_req_no.push(sel[i].get('req_no')); // 출처가 있는 req_no 배열
						}

						if(sel[i].get('arc_type') == 'archive' && sel[i].get('status') == <?=CHA_REQ?>)
						{
							archive_request_arr.push(sel[i].get('content_id'));
							
							// 동일한 부분
							if(sel[i].get('src_device_id') == 'PVIDEO')
							{
								var temp_mtrl_id = sel[i].get('mtrl_id');
								for(var c=0;c<dup_request_arr.length;c++)
								{
									if(dup_request_arr[c] == temp_mtrl_id)
									{
										break_msg = '동일한 소재ID로 PDS -> TAPE ARCHIVE 작업이 존재합니다.<br>확인해 주시기 바랍니다.';
										is_break = true;
									}
								}								
								dup_request_arr.push(temp_mtrl_id);
							}

						}
						
						
					}

					if(is_break) {
						Ext.Msg.alert('오류', break_msg);
						return;
					}


					if(is_check_archive_metadata)
					{	
						Ext.Ajax.request({
							url: '/store/is_check_require_metadata.php',
							params: {
								action: 'accept',
								arr_content_id: Ext.encode(archive_request_content_id_arr) 
							},
							callback : function(opt, success, res){
								
								 var result = Ext.decode(res.responseText);	
								 var sel_invaild_content_ids = [];
								 if(result.count>0)
								 {
									var content_ids = Ext.decode(result.content_ids);

									var alert_msg = "";
									
									for(var i=0;i<content_ids.length;i++)
									{	
										var map_content_id = archive_request_map_arr[content_ids[i]];										
										sel_invaild_content_ids.push(map_content_id);
										alert_msg += "<br><font color=red>"+map_content_id+"</font>";
										for(var s=0;s<sel.length;s++)
										{
											if(sel[s].get('content_id') == content_ids[i])
											{
												break;
											}
										}
									}

									

									
									Ext.Msg.confirm('알림',"아카이브 필수 메타항목이 없는 요청이 있습니다.<br>소재ID: "+alert_msg+"<br> 메타를 수정하시겠습니까? <br>(여러소재 일경우 첫소재에 대한 메타창이 열립니다.)", function(btn){
									   if(btn === 'yes')
									   {	
											for(var s=s;s<sel.length;s++)
											{
												archive_request_grid.getSelectionModel().selectNext(); 
											}
											archive_request_grid.fireEvent('rowdblclick', archive_request_grid);
											return;
									   }
									   else{												
												if(is_break_add) {
													var win = new Ext.Window({
														title: '소재출처 선택',
														width: 250,
														//height: 130,
														modal: true,
														border: true,
														frame: true,
														padding: '3px',
														buttonAlign:'center',
														items:[{
															xtype: 'combo',
															store: new Ext.data.ArrayStore({
																id: 0,
																fields: [ 'type', 'displayText' ],
																data: [
																	['2', '광화문 NDS'],
																	['3', '광화문 PDS']
																]
															}),
															mode: 'local',
															hiddenName: 'type',
															displayField: 'displayText',
															valueField: 'type',
															value: '3',
															triggerAction: 'all',
															editable: false,
															name: 'type',
															fieldLabel: '입력형식',
															allowBlank: false
														},
														{
															xtype: 'displayfield',
															value : '<p style="height:20px;line-height:20px;">승인내용</p>'
														},
														{
															xtype:'textarea',
															layout: 'fit',
															width: 230,
															id:'auth_comment'
														}
														
														],
														buttons: [{
															text:'수정',
															scale: 'medium',
															icon: '/led-icons/application_edit.png',
															handler: function(b, e){
																var combo = win.get(0);
																var ud_system = combo.getValue();
																win.el.mask();
																Ext.Ajax.request({
																	url: '/pages/menu/archive_management/action_archive_request.php',
																	params: {
																		action: 'accept',
																		'no_system_req_no[]': is_break_add_no_arr,
																		is_empty_ud: 'Y',
																		ud_system: ud_system,
																		'req_no[]' : rs_req_no,
																		auth_comment : Ext.getCmp('auth_comment').getValue()
																	},
																	callback : function(opt, success, res){
																		win.el.unmask();
																		
																		 var msg = Ext.decode(res.responseText);							
																		 if(msg.success)
																		 {
																			Ext.Msg.alert(' 완 료',msg.msg);							
																			
																		 }
																		 else {
																			Ext.Msg.alert(' 오 류 ',msg.msg);
																		 }
																		
																		archive_request_grid.getStore().reload();
																		win.close();
																	}
																});
															}
														},{
															text: '취소',
															scale: 'medium',
															icon: '/led-icons/cross.png',
															handler: function(b, e){
																win.close();
															}
														}]
													});
													win.show();
												} else {
													
													show_confirm_win(rs_req_no);
													return;
													Ext.Msg.show({
														title: '확인',
														msg : '선택하신 요청목록을 승인 하시겠습니까?',
														buttons : Ext.Msg.YESNO,
														fn : function(button){
															if(button == 'yes')
															{									
															
															}
														}
													});
												}
										  }
									 });

									//Ext.Msg.alert("알림","필수 메타항목이 빠져있는 요청이 있습니다.<br>소재ID: "+alert_msg);
								 }
								 else 
								 {
									if(is_break_add) {
											var win = new Ext.Window({
												title: '소재출처 선택',
												width: 250,
												//height: 130,
												modal: true,
												border: true,
												frame: true,
												padding: '3px',
												buttonAlign:'center',
												items:[{
													xtype: 'combo',
													store: new Ext.data.ArrayStore({
														id: 0,
														fields: [ 'type', 'displayText' ],
														data: [
															['2', '광화문 NDS'],
															['3', '광화문 PDS']
														]
													}),
													mode: 'local',
													hiddenName: 'type',
													displayField: 'displayText',
													valueField: 'type',
													value: '3',
													triggerAction: 'all',
													editable: false,
													name: 'type',
													fieldLabel: '입력형식',
													allowBlank: false
												},{
													xtype: 'displayfield',
													value : '<p style="height:20px;line-height:20px;">승인내용</p>'
												},
												{
													xtype:'textarea',
													layout: 'fit',
													width: 230,
													id:'auth_comment'
												}],
												buttons: [{
													text:'수정',
													scale: 'medium',
													icon: '/led-icons/application_edit.png',
													handler: function(b, e){
														var combo = win.get(0);
														var ud_system = combo.getValue();
														win.el.mask();
														Ext.Ajax.request({
															url: '/pages/menu/archive_management/action_archive_request.php',
															params: {
																action: 'accept',
																'no_system_req_no[]': is_break_add_no_arr,
																is_empty_ud: 'Y',
																ud_system: ud_system,
																'req_no[]' : rs_req_no,
																auth_comment : Ext.getCmp('auth_comment').getValue()
															},
															callback : function(opt, success, res){
																win.el.unmask();
																
																 var msg = Ext.decode(res.responseText);							
																 if(msg.success)
																 {
																	Ext.Msg.alert(' 완 료',msg.msg);							
																	
																 }
																 else {
																	Ext.Msg.alert(' 오 류 ',msg.msg);
																 }
																
																archive_request_grid.getStore().reload();
																win.close();
															}
														});
													}
												},{
													text: '취소',
													scale: 'medium',
													icon: '/led-icons/cross.png',
													handler: function(b, e){
														win.close();
													}
												}]
											});
											win.show();
										} else {
											show_confirm_win(rs_req_no);
											return;
											Ext.Msg.show({
												title: '확인',
												msg : '선택하신 요청목록을 승인 하시겠습니까?',
												buttons : Ext.Msg.YESNO,
												fn : function(button){
													if(button == 'yes')
													{									
														Ext.Ajax.request({
															url: '/pages/menu/archive_management/action_archive_request.php',
															params: {
																action: 'accept',
																'req_no[]': rs_req_no
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
																archive_request_grid.getStore().reload();
															}
														});
													}
												}
											});
										}
								 }
							}
						});

					}
					else 
					{
						if(is_break_add) {
							var win = new Ext.Window({
								title: '소재출처 선택',
								width: 250,
								//height: 130,
								modal: true,
								border: true,
								frame: true,
								padding: '3px',
								buttonAlign:'center',
								items:[{
									xtype: 'combo',
									store: new Ext.data.ArrayStore({
										id: 0,
										fields: [ 'type', 'displayText' ],
										data: [
											['2', '광화문 NDS'],
											['3', '광화문 PDS']
										]
									}),
									mode: 'local',
									hiddenName: 'type',
									displayField: 'displayText',
									valueField: 'type',
									value: '3',
									triggerAction: 'all',
									editable: false,
									name: 'type',
									fieldLabel: '입력형식',
									allowBlank: false
								},{
									xtype: 'displayfield',
									value : '<p style="height:20px;line-height:20px;">승인내용</p>'
								},
								{
									xtype:'textarea',
									layout: 'fit',
									width: 230,
									id:'auth_comment'
								}],
								buttons: [{
									text:'수정',
									scale: 'medium',
									icon: '/led-icons/application_edit.png',
									handler: function(b, e){
										var combo = win.get(0);
										var ud_system = combo.getValue();
										win.el.mask();
										Ext.Ajax.request({
											url: '/pages/menu/archive_management/action_archive_request.php',
											params: {
												action: 'accept',
												'no_system_req_no[]': is_break_add_no_arr,
												is_empty_ud: 'Y',
												ud_system: ud_system,
												'req_no[]' : rs_req_no,
												auth_comment : Ext.getCmp('auth_comment').getValue()
											},
											callback : function(opt, success, res){
												win.el.unmask();
												
												 var msg = Ext.decode(res.responseText);							
												 if(msg.success)
												 {
													Ext.Msg.alert(' 완 료',msg.msg);							
													
												 }
												 else {
													Ext.Msg.alert(' 오 류 ',msg.msg);
												 }
												
												archive_request_grid.getStore().reload();
												win.close();
											}
										});
									}
								},{
									text: '취소',
									scale: 'medium',
									icon: '/led-icons/cross.png',
									handler: function(b, e){
										win.close();
									}
								}]
							});
							win.show();
						} else {
							show_confirm_win(rs_req_no);
							return;
							Ext.Msg.show({
								title: '확인',
								msg : '선택하신 요청목록을 승인 하시겠습니까?',
								buttons : Ext.Msg.YESNO,
								fn : function(button){
									if(button == 'yes')
									{									
										Ext.Ajax.request({
											url: '/pages/menu/archive_management/action_archive_request.php',
											params: {
												action: 'accept',
												'req_no[]': rs_req_no
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
												archive_request_grid.getStore().reload();
											}
										});
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
			icon: '/led-icons/cancel.png',
			text: '반려',
			tooltip: '요청된 항목을 반려합니다.',
			handler : function(btn, e){
				var sel = archive_request_grid.getSelectionModel().getSelections();
				var rs_req_no = [];
				var is_break = false;
				var break_msg = '';
				if(sel.length>0)
				{					
					for(var i=0;i<sel.length;i++)
					{
						if( sel[i].get('status') != <?=CHA_REQ?>  )
						{
							break_msg = '요청중인 항목만 선택 해 주시기 바랍니다.';
							is_break = true;
						}
						
						rs_req_no.push(sel[i].get('req_no'));
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
							msg : '선택하신 요청목록을 반려 하시겠습니까?',
							buttons : Ext.Msg.YESNO,
							fn : function(button){
								if(button == 'yes')
								{
									Ext.Ajax.request({
										url: '/pages/menu/archive_management/action_archive_request.php',
										params: {
											action: 'decline',
											'req_no[]': rs_req_no
										},
										callback : function(opt, success, res){
											if(success)
											{
												 var msg = Ext.decode(res.responseText);							
												 if(msg.success)
												 {
													Ext.Msg.alert(' 완 료',msg.msg);							
													archive_request_grid.getStore().reload();
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
									});
								}
							}
						});
					}
				}
				else {
					Ext.Msg.alert('오류','선택 된 아이템이 없습니다.');
				}
			}		
		},{
			icon: '/led-icons/application_put.png',
			text: '승인(아카이브 출처 수정)',
			tooltip: '아카이브시 잘못된 출처를 수정할 수 있습니다.',
			hidden:true,
			handler : function(btn, e){
				var sel = archive_request_grid.getSelectionModel().getSelections();
				var is_break = false;
				var break_msg = '';
				var is_break_add = false;
				var is_break_add_no = '';
				var archive_request_content_id_arr = [];
				var rs_req_no = [];
				var archive_request_map_arr = [];
				var is_break_add_no_arr = [];

				if(sel.length == 1)
				{					
					for(var i=0;i<sel.length;i++)
					{	
						if(sel[i].get('arc_type') != 'archive')
						{
							break_msg = '아카이브 항목을 선택 해 주시기 바랍니다.';
							is_break = true;
						}
						else if( sel[i].get('status') != <?=CHA_REQ?> && sel[i].get('status') != <?=CHA_ERR?>)
						{
							break_msg = '요청중인 항목을 선택 해 주시기 바랍니다.';
							is_break = true;
						}
						else 
						{
							is_break_add_no = sel[i].get('req_no');
							archive_request_content_id_arr.push(sel[i].get('content_id'));
							archive_request_map_arr[sel[i].get('content_id')] = sel[i].get('mtrl_id');
							//is_break_add_no = sel[i].get('req_no');
							is_break_add_no_arr.push(is_break_add_no); // 출처가 없는 req_no 배열
						}						
					}


										
					if(is_break) {
						Ext.Msg.alert('오류', break_msg);
						return;
					} else {

						Ext.Ajax.request({
							url: '/store/is_check_require_metadata.php',
							params: {
								action: 'accept',
								arr_content_id: Ext.encode(archive_request_content_id_arr) 
							},
							callback : function(opt, success, res){
								
								 var result = Ext.decode(res.responseText);	
								 var sel_invaild_content_ids = [];
								 if(result.count>0)
								 {	
									
									var content_ids = Ext.decode(result.content_ids);
									var alert_msg = "";
									
									for(var i=0;i<content_ids.length;i++)
									{											
										var map_content_id = archive_request_map_arr[content_ids[i]];										
										sel_invaild_content_ids.push(map_content_id);
										alert_msg += "<br><font color=red>"+map_content_id+"</font>";
										for(var s=0;s<sel.length;s++)
										{
											if(sel[s].get('content_id') == content_ids[i])
											{												
												break;
											}
										}
									}
									
									Ext.Msg.confirm('알림',"아카이브 필수 메타항목이 없는 요청이 있습니다.<br>소재ID: "+alert_msg+"<br> 메타를 수정하시겠습니까? <br>(여러소재 일경우 첫소재에 대한 메타창이 열립니다.)", function(btn){
									   if(btn === 'yes')
									   {	
											for(var s=s;s<sel.length;s++)
											{
												archive_request_grid.getSelectionModel().selectNext(); 
											}
											archive_request_grid.fireEvent('rowdblclick', archive_request_grid);
											return;
									   }
									   else{												
												var win = new Ext.Window({
													title: '소재출처 선택',
													width: 250,
													//height: 130,
													modal: true,
													border: true,
													frame: true,
													padding: '3px',
													items:[{
														xtype: 'combo',
														store: new Ext.data.ArrayStore({
															id: 0,
															fields: [ 'type', 'displayText' ],
															data: [
																['2', '광화문 NDS'],
																['3', '광화문 PDS']
															]
														}),
														mode: 'local',
														hiddenName: 'type',
														displayField: 'displayText',
														valueField: 'type',
														value: '3',
														triggerAction: 'all',
														editable: false,
														name: 'type',
														fieldLabel: '입력형식',
														allowBlank: false
													},{
														xtype: 'displayfield',
														value : '<p style="height:20px;line-height:20px;">승인내용</p>'
													},
													{
														xtype:'textarea',
														layout: 'fit',
														width: 230,
														id:'auth_comment'
													}],
													buttons: [{
														text:'수정',
														scale: 'medium',
														icon: '/led-icons/application_edit.png',
														handler: function(b, e){
															var combo = win.get(0);
															var ud_system = combo.getValue();
															win.el.mask();
															Ext.Ajax.request({
																url: '/pages/menu/archive_management/action_archive_request.php',
																params: {
																	action: 'accept',
																	'no_system_req_no[]': is_break_add_no_arr,
																	is_empty_ud: 'Y',
																	ud_system: ud_system,
																	'req_no[]' : rs_req_no,
																	auth_comment : Ext.getCmp('auth_comment').getValue()
																},
																callback : function(opt, success, res){
																	win.el.unmask();
																	 var msg = Ext.decode(res.responseText);							
																	 if(msg.success)
																	 {												
																		Ext.Msg.alert(' 완 료',msg.msg);		
																	 }
																	 else {
																		Ext.Msg.alert(' 오 류 ',msg.msg);
																	 }
																	
																	archive_request_grid.getStore().reload();
																	win.close();
																}
															});
														}
													},{
														text: '취소',
														scale: 'medium',
														icon: '/led-icons/cross.png',
														handler: function(b, e){
															win.close();
														}
													}]
												});
												win.show();
										  }
									 });

									//Ext.Msg.alert("알림","필수 메타항목이 빠져있는 요청이 있습니다.<br>소재ID: "+alert_msg);
								 }
								 else 
								 {
									var win = new Ext.Window({
											title: '소재출처 선택',
											width: 250,
											//height: 130,
											modal: true,
											border: true,
											frame: true,
											padding: '3px',
											items:[{
												xtype: 'combo',
												store: new Ext.data.ArrayStore({
													id: 0,
													fields: [ 'type', 'displayText' ],
													data: [
														['2', '광화문 NDS'],
														['3', '광화문 PDS']
													]
												}),
												mode: 'local',
												hiddenName: 'type',
												displayField: 'displayText',
												valueField: 'type',
												value: '3',
												triggerAction: 'all',
												editable: false,
												name: 'type',
												fieldLabel: '입력형식',
												allowBlank: false
											},{
												xtype: 'displayfield',
												value : '<p style="height:20px;line-height:20px;">승인내용</p>'
											},
											{
												xtype:'textarea',
												layout: 'fit',
												width: 230,
												id:'auth_comment'
											}],
											buttons: [{
												text:'수정',
												scale: 'medium',
												icon: '/led-icons/application_edit.png',
												handler: function(b, e){
													var combo = win.get(0);
													var ud_system = combo.getValue();
													win.el.mask();
													Ext.Ajax.request({
														url: '/pages/menu/archive_management/action_archive_request.php',
														params: {
															action: 'accept',
															'no_system_req_no[]': is_break_add_no_arr,
															is_empty_ud: 'Y',
															ud_system: ud_system,
															'req_no[]' : rs_req_no,
															auth_comment : Ext.getCmp('auth_comment').getValue()
														},
														callback : function(opt, success, res){
															win.el.unmask();
															 var msg = Ext.decode(res.responseText);							
															 if(msg.success)
															 {												
																Ext.Msg.alert(' 완 료',msg.msg);		
															 }
															 else {
																Ext.Msg.alert(' 오 류 ',msg.msg);
															 }
															
															archive_request_grid.getStore().reload();
															win.close();
														}
													});
												}
											},{
												text: '취소',
												scale: 'medium',
												icon: '/led-icons/cross.png',
												handler: function(b, e){
													win.close();
												}
											}]
										});
										win.show();	
										
								 }
							}
						});
						
					}
				}
				else {
					Ext.Msg.alert('오류','한 건을 선택 해 주시기 바랍니다.');
				}
			}
		},{
			icon: '/led-icons/application_edit.png',
			text: '리스토어 전송처 수정',
			hidden:true,
			tooltip: '리스토어시 잘못된 전송처를 수정할 수 있습니다.',
			handler : function(btn, e){
				var sel = archive_request_grid.getSelectionModel().getSelections();
				var break_msg = '';
				var is_break = false;
				var rs = [];
				var ud_system = '';
				if( sel.length>0 ) {
					for(var i=0;i<sel.length;i++)
					{
						if( sel[i].get('arc_type') != 'restore' )
						{
							break_msg = '리스토어 항목만 선택 해 주시기 바랍니다.';
							is_break = true;
						}
						
						if( sel[i].get('status') != <?=CHA_REQ?>  )
						{
							break_msg = '요청중인 항목만 선택 해 주시기 바랍니다.';
							is_break = true;
						}

						rs.push(sel[i].get('req_no'));
						ud_system = sel[i].get('ud_system');
					}

					if(is_break)
					{
						Ext.Msg.alert('오류', break_msg);
						return;
					} else {
						var win = new Ext.Window({
							title: '전송처 선택',
							width: 250,
							height: 130,
							modal: true,
							border: true,
							frame: true,
							padding: '3px',
							items:[{
								xtype: 'combo',
								store: new Ext.data.ArrayStore({
									id: 0,
									fields: [ 'type', 'displayText' ],
									data: [
										['0', '상암동 NPS'],
										['1', '광화문 NPS'],
										['2', '광화문 NDS'],
										['3', '광화문 PDS'],
										['4', '광화문 archiveR']
									]
								}),
								mode: 'local',
								hiddenName: 'type',
								displayField: 'displayText',
								valueField: 'type',
								value: ud_system,
								triggerAction: 'all',
								editable: false,
								name: 'type',
								fieldLabel: '입력형식',
								allowBlank: false
							}],
							buttons: [{
								text:'수정',
								scale: 'medium',
								icon: '/led-icons/application_edit.png',
								handler: function(b, e){
									var combo = win.get(0);
									var ud_system = combo.getValue();
									
									Ext.Ajax.request({
										url: '/gateway/diva/request_restore.php',
										params: {
											'req_no[]': rs,
											target_ud_system: ud_system,
											mode: 'update'
										},
										callback: function(opt, success, response){
											var res = Ext.decode(response.responseText);
											if(!res.success)
											{
												Ext.Msg.alert('알림', res.msg);
											}
											else
											{
												Ext.Msg.alert('알림', '리스토어 전송처가 수정되었습니다.');
												archive_request_grid.getStore().reload();
												win.close();
											}							
										}
									});
								}
							},{
								text: '취소',
								scale: 'medium',
								icon: '/led-icons/cross.png',
								handler: function(b, e){
									win.close();
								}
							}]
						});
						win.show();
					}
				} else {
					Ext.Msg.alert('오류','선택 된 아이템이 없습니다.');
				}
			}
		},'-',{
			text: '자동 새로고침 실행중',
			scale: 'small',
			pressed: true,
			id: 'un_pin_archive_request',
			icon: '/led-icons/accept.png',
			handler: function(b, e){
				clearInterval(setTime);
				Ext.getCmp('un_pin_archive_request').hide();						
				Ext.getCmp('pin_archive_request').show();
			}
		},{
			text: '자동 새로고침 중지됨',
			scale: 'small',
			id: 'pin_archive_request',
			hidden: true,
			icon: '/led-icons/cross.png',
			listeners: {
				afterrender: function(self){
					if(setTime == null)
					{
						setTime = setInterval(function(){							
							Ext.getCmp('archive_request_grid_id').getStore().reload();
						}, auto_refresh_time);
					}
				}
			},
			handler: function(b, e){
				setTime = setInterval(function(){								
					Ext.getCmp('archive_request_grid_id').getStore().reload();
				}, auto_refresh_time);
				Ext.getCmp('pin_archive_request').hide();						
				Ext.getCmp('un_pin_archive_request').show();
			}
		}		
		]
	});

	var archive_request_grid = new Ext.grid.EditorGridPanel({
		border: false,
		loadMask: true,
		frame:true,
		id: 'archive_request_grid_id',
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
		//xtype: 'editorgrid',
		clicksToEdit: 1,
		loadMask: true,
		columnWidth: 1,
		store: archive_request_store,
		disableSelection: true,
		listeners: {
			viewready: function(self){
				self.store.load({
					params: {						
						start: 0,
						limit: delete_inform_size,
						arc_start_date: Ext.getCmp('archive_request_arc_start_date').getValue().format('Ymd000000'),
						arc_end_date: Ext.getCmp('archive_request_arc_end_date').getValue().format('Ymd240000')
					}					
				});
				//self.add(tbar2);
			},
			rowcontextmenu: function(self, rowIdx, e){
					e.stopEvent();

					var ownerCt = self;

					var sm = self.getSelectionModel();
					if (!sm.isSelected(rowIdx)) {
						sm.selectRow(rowIdx);
					}

					var sel_data	  = sm.getSelected();
					var req_no		= sel_data.get('req_no');
					var src_device_id = sel_data.get('src_device_id');
					var arc_type	  = sel_data.get('arc_type');
					var status		= sel_data.get('status');
					var ud_system	 = sel_data.get('ud_system');
					var mgmt_id	   = sel_data.get('mgmt_id');
					var mtrl_id	   = sel_data.get('mtrl_id');
					var content_id	= sel_data.get('content_id');

					var target_ud_system = sel_data.get('target_ud_system');


					var menu = new Ext.menu.Menu({
							   items: [
								{
									icon: '/led-icons/application_form.png',									
									text:'작업흐름보기',
									handler: function(btn, e){
										Ext.Ajax.request({
												url: '/javascript/ext.ux/viewWorkFlowCha.php',
												params: {
													arc_type: arc_type,
													req_no : req_no,
													content_id : content_id,
													target_ud_system : target_ud_system
												},
												callback: function(options, success, response){
													if (success)
													{
														try
														{
															Ext.decode(response.responseText);
														}
														catch (e)
														{
															Ext.Msg.alert(e['name'], e['message']);
														}
													}
													else
													{
														//>>Ext.Msg.alert('서버 오류', response.statusText);
														Ext.Msg.alert(_text('MN00022'), response.statusText);
													}
												}
										});
									}
								}
							   ]
				});
				menu.showAt(e.getXY());				


					//console.log(sel_data);
			},
			
			rowdblclick: function(self, rowIndex, e){
				var sm = self.getSelectionModel().getSelected();							

				var content_id = sm.get('content_id');	
				var req_comment = sm.get('req_comment');
				var mtrl_id = sm.get('mtrl_id');
				var mgmt_id = sm.get('mgmt_id');
				var is_block = ""; 
				if(!mgmt_id)
				{
					is_block = "ok";
				}
				

				var that = self;

				if ( !Ext.Ajax.isLoading(self.isOpen) )
				{
				
					//>>self.load = new Ext.LoadMask(Ext.getBody(), {msg: '상세 정보를 불러오는 중입니다...'});
					self.load = new Ext.LoadMask(Ext.getBody(), {msg: _text('MSG00143')});
					self.load.show();
					Ext.Ajax.request({
						url: '/interface/archive_interface/get_syncdata_one.php',
						params: {
							content_id: content_id,
							is_block : is_block
						},
						callback: function(self, success, response) {
							self.isOpen = Ext.Ajax.request({
								url: '/javascript/ext.ux/Ariel.DetailWindow.php',
								params: {
									content_id: content_id,
									record: Ext.encode(sm.json),
									page_from: 'ArchiveRequest'
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
											if( !Ext.isEmpty(req_comment) ) {
												//Ext.Msg.alert('요청사유', req_comment);
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
				} //endif
			}			
		},
	
		sm : selModel,
		
		cm: new Ext.grid.ColumnModel({
			defaults:{
				sortable: false
			},

			columns: [	
				new Ext.grid.RowNumberer(),
				selModel,
				{header: '의뢰구분',dataIndex:'arc_type',align:'center',width:90,
					renderer: mapReqType},
				{header: '의뢰상태',dataIndex:'status',align:'center',width:70,
					renderer: mapStatus,menuDisabled: true},
				{header: '소재ID',dataIndex:'mtrl_id',align:'center',sortable: true,menuDisabled: true,width:120,
					editor: new Ext.form.TextField({
						allowBlank: true,
						readOnly: true
				})},
				{header: '<center>소재명</center>',dataIndex:'title',align:'left',menuDisabled: true,width:250},
				{header: '의뢰자',dataIndex:'req_user_info',align:'center',menuDisabled: true,width:110},
				{header: '의뢰일시', dataIndex:'req_time',align:'center',sortable: true,menuDisabled: true,width:140,
					renderer: showDate},
				{header: '승인자',dataIndex:'arc_user_info',align:'center',menuDisabled: true,width:110,renderer: showEmpty},
				{header: '승인일시',dataIndex:'arc_time',align:'center',menuDisabled: true,width:140,renderer: showDate},
				{header: '소재출처',dataIndex:'src_device_id',align:'center',menuDisabled: true,width:90,
					renderer: mapdeviceSystem},
				{header: '<center>요청사유</center>',dataIndex:'req_comment',align:'left',menuDisabled: true,width:250,
					renderer: function (value, metadata, record, rowIdx, colIdx, store)
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
				
				{header: '<center>승인내용</center>',dataIndex:'arc_comment',align:'left',menuDisabled: true,width:250,renderer: function (value, metadata, record, rowIdx, colIdx, store)
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
				{header: '리스토어 전송처',dataIndex:'target_ud_system',align:'center',menuDisabled: true,width:90,
					renderer: mapUdSystem},
				{header: '완료일시',dataIndex:'done_time',align:'center',menuDisabled: true,width:140,renderer: showDate},
				
				{header: '파샬복원소재ID',dataIndex:'new_mtrl_id',align:'center',menuDisabled: true,width:120,
					editor: new Ext.form.TextField({
						allowBlank: true,
						readOnly: true
				}),renderer: showEmpty},
				
				{header: '프로그램ID',dataIndex:'pgm_id',align:'center',menuDisabled: true,width:100,
					renderer: showEmpty},
				{header: '프로그램명',dataIndex:'pgm_nm',align:'center',menuDisabled: true,width:250,
					renderer: showEmpty},
			
				{header: '의뢰번호',dataIndex:'mgmt_id',align:'center',menuDisabled: true,width:150,
					renderer: showEmpty,editor: new Ext.form.TextField({
						allowBlank: true,
						readOnly: true
				})}
				
			]
		}),

		view: new Ext.ux.grid.BufferView({
			rowHeight: 20,
			scrollDelay: false,
			emptyText: '결과 값이 없습니다.'
		}),
	
		bbar: new Ext.PagingToolbar({
			store: archive_request_store,
			pageSize: delete_inform_size,
			items:[{
				id: 'archive_request_toolbartext',
				xtype:'tbtext',
				pageX:'100',
				pageY:'100',				
				text : "리스트 수 : "+total_list
			},'->',{
				xtype: 'displayfield',
				id: 'archive_request_storage_info',
				value: '<?=$info?>'
			},{
				xtype: 'displayfield',
				width: 20
			},{
				xtype: 'displayfield',
				id: 'cache_storage_info',
				value: '<?=$info2?>'
			},{
				xtype: 'displayfield',
				width: 20
			},{
				xtype: 'displayfield',
				value: '<?=$diva_info_msg?>'
			}			
			]
		})
	});

	return archive_request_grid;

	function showEmpty(value){
		if(Ext.isEmpty(value)) value = '-';
		return value;
	}

	function showDate(value){
		if(Ext.isEmpty(value)) value = '-';
		else value = Ext.util.Format.date(value,'Y-m-d H:i:s');
		return value;
	}

	function mapUdSystem(value){
		switch(value) {
			case '0':
				value = '상암동 NPS';
			break;
			case '1':
				value = '광화문 NPS';
			break;
			case '2':
				value = '광화문 NDS';
			break;
			case '3':
				value = '광화문 PDS';
			break;
			case '4':
				value = '광화문 archiveR';
			break;
			default:
				value = '-';
		}
		return value;
	}


	function mapdeviceSystem(value){
		switch(value) {
			case '0':
				value = '상암동 NPS';
			break;
			case '1':
				value = '광화문 NPS';
			break;
			case 'ARCHIVE01':
				value = '광화문 archiveR';
			break;
			case 'TAPEPVIDEO':
				
			
			case 'TAPENVIDEO':
				
			
			case 'TAPEVIDEO':
				value = '상암동 archiveT';
			break;
			case 'NVIDEO':
				value = '광화문 NDS';
			break;
			case 'PVIDEO':
				value = '광화문 PDS';
			break;
			case '4':
				value = '광화문 archiveR';
			break;
			default:
				value = '-';
		}
		return value;
	}

	function mapStatus(value){
		switch(value) {			
			case '<?=CHA_REQ?>':
				value = '대기';
			break;
			case '<?=CHA_REJT?>':
				value = '<font color=crimson><b>반려</b></font>';
			break;
			case '<?=CHA_ACPT?>':
				value = '<font color=royalblue><b>승인</b></font>';
			break;
			case '<?=CHA_FIN_NL?>':
				value = '<font color=forestgreen><b>작업완료(R)</b></font>';
			break;
			case '<?=CHA_PRG_NL?>':
				value = '<font color=darkkhaki><b>작업중(R)</b></font>';
			break;
			case '<?=CHA_ERR_NL?>':
				value = '<font color=crimson><b>작업실패(R)</b></font>';
			break;
			case '<?=CHA_FIN?>':
				value = '<font color=forestgreen><b>작업완료(T)</b></font>';
			break;
			case '<?=CHA_PRG?>':
				value = '<font color=darkkhaki><b>작업중(T)</b></font>';
			break;
			case '<?=CHA_ERR?>':
				value = '<font color=crimson><b>작업실패(T)</b></font>';
			break;
			case '<?=CHA_REG_ERR?>':
				value = '<font color=crimson><b>등록실패(T)</b></font>';
			break;
			case '':
				value = '-';
			break;
			
			default:
				if(!isNaN(value))
				{
					value = value+"%";
				}
				else 
				{
					value = '-';
				}
		}
		return value;
	}

	function mapReqType(value){
		value = value.toLowerCase();
		switch(value) {
			case 'archive':
				value = '<font color=blue>아카이브</font>';
			break;
			case 'restore':
				value = '<font color=green>리스토어</font>';
			break;
			case 'pfr_restore':
				value = '<font color=olivedrab>Partial 리스토어</font>';
			break;
			case 'delete':
				value = '<font color=red>아카이브 삭제</font>';
			break;
			default:
				value = '-';
		}
		return value;
	}
	
})()