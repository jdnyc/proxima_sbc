<?php

require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lang.php');
fn_checkAuthPermission($_SESSION);
?>

(function(){
	var total_list = 0;
	var v_page_size = 20;

	var selModel = new Ext.grid.CheckboxSelectionModel({
	     singleSelect : false,
	     checkOnly : true
	});

	var list_store = new Ext.data.JsonStore({
		url:'/store/content_archive_info.php',
		root: 'data',
		totalProperty : 'total_list',
		fields: [
			{name: 'request_id'},
			{name: 'content_id'},
			{name: 'media_id'},
			{name: 'request_system'},
			{name: 'request_type'},
			{name: 'if_key1'},
			{name: 'if_key2'},
			{name: 'if_key33'},
			{name: 'progress'},
			{name: 'status'},
			{name: 'request_user_id'},
			{name: 'task_id'},
			{name: 'comments'},
			{name: 'nps_content_id'},
			{name: 'created_datetime',type:'date',dateFormat:'YmdHis'},
			{name: 'approve_datetime',type:'date',dateFormat:'Y-m-d H:i:s'},
			{name: 'approve_reason'},
			{name: 'reject_datetime',type:'date',dateFormat:'Y-m-d H:i:s'},
			{name: 'reject_reason'},
			{name: 'failed_datetime',type:'date',dateFormat:'YmdHis'},
			{name: 'failed_reason'},
			{name: 'complete_datetime',type:'date',dateFormat:'YmdHis'},
			{name: 'tape_id'},
			{name: 'ori_content_id'},
			{name: 'approve_user_id'},
			{name: 'bs_content_id'},
			{name: 'category_full_path'},
			{name: 'title'},
			{name: 'media_type'},
			{name: 'file_size'},
			{name: 'path'},
			{name: 'ud_content_title'},
			{name: 'bs_content_title'},
			{name: 'category'},
			{name: 'start_frame'},
			{name: 'end_frame'},
			{name : 'reason', 
					convert: function(v, record) {
                        if(record.status == 'REJECT'){
                           return record.reject_reason;
                        }else {
                           return record.approve_reason;
                        }
                     }
            },
            {name : 'title_case', 
					convert: function(v, record) {
                        if(record.request_type == 'PFR'){
                           return record.if_key2;
                        }else {
                           return record.title;
                        }
                     }
            },
            {name : 'start_frame_code', 
					convert: function(v, record) {
                        if(record.request_type == 'PFR'){
                           return record.start_frame;
                        }
                     }
            },
            {name : 'end_frame_code', 
					convert: function(v, record) {
                        if(record.request_type == 'PFR'){
                           return record.end_frame;
                        }
                     }
            },
            {name: 'date_time',type:'date',dateFormat:'YmdHis'},
		],
		listeners: {
			beforeload: function(self, opts){
				//opts.params = opts.params || {};

				//Ext.apply(opts.params, {
					//start_date: Ext.getCmp('archive_start_date').getValue().format('Ymd000000'),
					//end_date: Ext.getCmp('archive_end_date').getValue().format('Ymd240000')
				//});

			},
			load: function(self, opts){
				total_list = self.getTotalCount();
				var tooltext = "(  "+_text('MN02165')+" : <font color=blue><b>"+total_list +"</b></font> )";
				Ext.getCmp('toolbartext').setText(tooltext);
			}
		}
	});

	function note_qtip(value, metaData, record, rowIndex, colIndex, store)
	{
		if( value != '' && value != null)
		{
			metaData.attr = 'ext:qtip="'+value+'"';
		}
		return Ext.util.Format.htmlEncode(value);
	}


	return {
		border: false,
		cls: 'proxima_customize',
		loadMask: true,
		frame:false,
		width:'100%',
		//>>tbar: [' 아카이브 상태 ',{
		tbar: [ _text('MN01108')+' : ',{
			xtype:'combo',
			id:'archive_type_combo',
			mode:'local',
			width:120,
			triggerAction:'all',
			editable:false,
			displayField:'d',
			valueField:'v',
			value : 'all',
			//>>emptyText:'검색',
			emptyText:'<?=_text('MN00037')?>',
			store: new Ext.data.ArrayStore({
				fields:[
					'd','v'
				],
				data:[
					[ _text('MN00244'),'all'],//전체
					<?php
					//if( $arr_sys_code['interwork_flashnet']['use_yn'] == 'Y' ){
					if($arr_sys_code['interwork_archive_confirm']['use_yn'] == 'Y'){
						echo("[_text('MN02418'),'REQUEST'],");//'아카이브 요청'
						echo("[_text('MN02415'),'REJECT'],");//'아카이브 반려'
					}
					?>
					[ _text('MN02413'),'APPROVE'],//'아카이브 승인'
					[ _text('MN02414'),'PROCESSING'],//'아카이브 진행중'
					[ _text('MN02416'),'COMPLETE'],//'아카이브 완료'
					[ _text('MN02417'),'FAILED']//'아카이브 실패'
				]
			}),
			listeners:{
				select:{
					fn:function(self,record,index){
						var search_val = Ext.getCmp('archive_type_combo').getValue();
							Ext.getCmp('archive_inform_id').getStore().load({
							params: {
								start: 0,
								limit: v_page_size,
								date_mode : Ext.getCmp('archive_date_combo').getValue(),
								start_date: Ext.getCmp('archive_start_date').getValue().format('Ymd000000'),
								end_date: Ext.getCmp('archive_end_date').getValue().format('Ymd240000'),
								action : search_val
								}
							});
					}
				}
			}
		},'-', _text('MN00354'),//'날짜'
		{
			xtype:'combo',
			id:'archive_date_combo',
			mode:'local',
			width:140,
			triggerAction:'all',
			editable:false,
			displayField:'d',
			valueField:'v',
			value : '',
			//>>emptyText:'검색',
			emptyText:_text('MSG00026'),//선택해주세요.
			store: new Ext.data.ArrayStore({
				fields:[
					'd','v'
				],
				data:[
					[ _text('MN00244'),'disable'],//전체
					[ _text('MN02412'),'created_datetime'],//'아카이브 요청일자'
					[ _text('MN02419'),'approve_datetime']//'아카이브 승인일자'
				]
			}),
			listeners:{
				select:{
					fn:function(self,record,index){
						var search_val = Ext.getCmp('archive_date_combo').getValue();
						
						if(search_val == 'disable')
						{
							Ext.getCmp('archive_start_date').disable();
							Ext.getCmp('archive_end_date').disable();

						}else{
								Ext.getCmp('archive_start_date').enable();
								Ext.getCmp('archive_end_date').enable();
								if(search_val == 'created_datetime' || search_val == 'approve_datetime')
								{
									var d = new Date();
									Ext.getCmp('archive_start_date').setMaxValue(d.format('Y-m-d'));
									Ext.getCmp('archive_end_date').setMaxValue(d.format('Y-m-d'));
								}
								else
								{
									Ext.getCmp('archive_start_date').setMaxValue('');
									Ext.getCmp('archive_end_date').setMaxValue('');
								}
						}
					}
				}
			}
		}
		,'-',{
			xtype: 'datefield',
			id: 'archive_start_date',
			width:100,
			disabled : true,
			editable: true,
			format: 'Y-m-d',
			listeners: {
				render: function(self){
					var d = new Date();
					self.setValue(d.add(Date.MONTH, -12).format('Y-m-d'));
				}
			}
		},
		//>>'부터'
		'<?=_text('MN00183')?>'
		,{
			xtype: 'datefield',
			id: 'archive_end_date',
			width:100,
			editable: true,
			format: 'Y-m-d',
			disabled : true,
			listeners: {
				render: function(self){
					var d = new Date();
					self.setValue(d.format('Y-m-d'));
				}
			}
		},'-',{
			cls: 'proxima_button_customize',
			width: 30,
			text: '<span style="position:relative;top:1px;" title="'+_text('MN00059')+'"><i class="fa fa-search" style="font-size:13px;color:white;"></i></span>',
			handler: function(btn, e){
				var search_val = Ext.getCmp('archive_type_combo').getValue();
				//>>if((search_val=='전체보기')||Ext.isEmpty(search_val))
					Ext.getCmp('archive_inform_id').getStore().load({
						params: {
								start: 0,
								limit: v_page_size,
								date_mode : Ext.getCmp('archive_date_combo').getValue(),
								start_date: Ext.getCmp('archive_start_date').getValue().format('Ymd000000'),
								end_date: Ext.getCmp('archive_end_date').getValue().format('Ymd240000'),
								action : search_val
						}

					});

			}
		}
		<?php if($arr_sys_code['interwork_archive_confirm']['use_yn'] == 'Y'){?>
		,{
			//icon: '/led-icons/application_edit.png',
			cls: 'proxima_button_customize',
			width: 30,
			text: '<span style="position:relative;top:1px;" title="'+_text('MN02420')+'"><i class="fa fa-check" style="font-size:13px;color:white;"></i></span>',
			handler : function(btn, e){
				var sel = Ext.getCmp('archive_inform_id').getSelectionModel().getSelections();
				if(sel.length > 0){
					var request_check_flag = 'Y';
					Ext.each(sel, function(r){
						if( r.get('status') != 'REQUEST' ){
							request_check_flag = 'N';
						}
					});

					if(request_check_flag == 'N') {
						//Only not requested contents cannot confirm.
						Ext.Msg.alert(_text('MN00023'), _text('MSG02119'));
						return;
					}else{
						var win = new Ext.Window({
							layout:'fit',
							title: _text('MN02476'),
							modal: true,
							width:500,
							height:170,
							buttonAlign: 'center',
							items:[{
								id:'reason_approve_inform',
								xtype:'form',
								border: false,
								frame: true,
								padding: 5,
								labelWidth: 70,
								cls: 'change_background_panel',
								defaults: {
									anchor: '95%'
								},
								items: [{
									id:'approve_reason',
									xtype: 'textarea',
									height: 50,
									fieldLabel:_text('MN02476'),
									allowBlank: false,
									blankText: '<?=_text('MSG02183')?>',
									msgTarget: 'under'
								}]
							}],
							buttons:[{
								text : '<span style="position:relative;top:1px;"><i class="fa fa-paper-plane-o" style="font-size:13px;"></i></span>&nbsp;'+_text('MN02197'),
								scale: 'medium',
								handler: function(btn,e){
									var isValid = Ext.getCmp('approve_reason').isValid();
									if ( ! isValid) {
										Ext.Msg.show({
											icon: Ext.Msg.INFO,
											title: _text('MN00024'),//확인
											msg: '<?=_text('MSG02183')?>',
											buttons: Ext.Msg.OK
										});
										return;
									}

									var approve_reason = Ext.getCmp('approve_reason').getValue();

									var sel = Ext.getCmp('archive_inform_id').getSelectionModel().getSelections();
									var sel_request_id = new Array();
									var sel_content_id = new Array();
									var del_info = new Array();
									var request_check_flag = 'Y';
									if(sel.length > 0)
									{
										Ext.each(sel, function(r){
											if( r.get('status') != 'REQUEST' ){
												request_check_flag = 'N';
											}else{
												sel_request_id.push(r.get('request_id'));
												var request_type = r.get('request_type');
												if(request_type == 'PFR'){
													sel_content_id.push(r.get('ori_content_id'));
												}else{
													sel_content_id.push(r.get('content_id'));
												}
												
											}
										});

										if(request_check_flag == 'N') {
											//Only not requested contents cannot confirm.
											Ext.Msg.alert(_text('MN00023'), _text('MSG02119'));
											return;
										}
										
										Ext.Msg.show({
					 						title: _text('MN00021'),
					 						msg : _text('MSG02165'),
					 						buttons : Ext.Msg.YESNO,
					 						fn : function(button){
					 							if(button == 'yes')
					 							{
					 								Ext.Ajax.request({
														url : '/store/archive/insert_archive_request.php',
														params : {
															request_ids : Ext.encode(sel_request_id),
															content_ids : Ext.encode(sel_content_id),
															case_management : 'approve',
															approve_reason: approve_reason
														},
														callback : function(opt, success, res){
															if(success)
															{
																 var msg = Ext.decode(res.responseText);
																 if(msg.success)
																 {
																 	//Ext.Msg.alert(' 완 료',msg.msg);
																 	Ext.getCmp('archive_inform_id').getStore().reload();
																 }
																 else {
																	Ext.Msg.alert( _text('MN01039'), msg.msg);//'오류'
																 }
															}
															else
															{
																Ext.Msg.alert( _text('MN01098'), res.statusText);//'서버 오류'
															}
														}
													})
					 							}
					 						}
					 					});

									}else {
										Ext.Msg.alert( _text('MN01039'), _text('MSG01005'));
									}

									win.destroy();
								}
							},{
								text : '<span style="position:relative;top:1px;"><i class="fa fa-close style="font-size:13px;"></i></span>&nbsp;'+_text('MN00004'),
								scale: 'medium',
								handler: function(btn,e){
									win.destroy();
								}
							}]
						});
						win.show();
					}
				}else{
					Ext.Msg.alert( _text('MN01039'), _text('MSG01005'));
				}
			}
		},{
			cls: 'proxima_button_customize',
			width: 30,
			text: '<span style="position:relative;top:1px;" title="'+_text('MN02422')+'"><i class="fa fa-ban" style="font-size:13px;color:white;"></i></span>',
			handler : function(btn, e){
				var sel = Ext.getCmp('archive_inform_id').getSelectionModel().getSelections();
				if(sel.length > 0){
					var request_check_flag = 'Y';
					Ext.each(sel, function(r){
						if( r.get('status') != 'REQUEST' ){
							request_check_flag = 'N';
						}
					});

					if(request_check_flag == 'N') {
						//Only not requested contents cannot confirm.
						Ext.Msg.alert(_text('MN00023'), _text('MSG02119'));
						return;
					}else{
						var win = new Ext.Window({
							layout:'fit',
							title: _text('MN00178'),
							modal: true,
							width:500,
							height:170,
							buttonAlign: 'center',
							items:[{
								id:'reason_reject_inform',
								xtype:'form',
								border: false,
								frame: true,
								padding: 5,
								labelWidth: 70,
								cls: 'change_background_panel',
								defaults: {
									anchor: '95%'
								},
								items: [{
									id:'reject_reason',
									xtype: 'textarea',
									height: 50,
									fieldLabel:_text('MN00178'),
									allowBlank: false,
									blankText: '<?=_text('MSG02184')?>',
									msgTarget: 'under'
								}]
							}],
							buttons:[{
								text : '<span style="position:relative;top:1px;"><i class="fa fa-paper-plane-o" style="font-size:13px;"></i></span>&nbsp;'+_text('MN02197'),
								scale: 'medium',
								handler: function(btn,e){
									var isValid = Ext.getCmp('reject_reason').isValid();
									if ( ! isValid) {
										Ext.Msg.show({
											icon: Ext.Msg.INFO,
											title: _text('MN00024'),//확인
											msg: '<?=_text('MSG02183')?>',
											buttons: Ext.Msg.OK
										});
										return;
									}

									var reject_reason = Ext.getCmp('reject_reason').getValue();

									var sel = Ext.getCmp('archive_inform_id').getSelectionModel().getSelections();
									var sel_request_id = new Array();
									var sel_content_id = new Array();
									var del_info = new Array();
									var request_check_flag = 'Y';
									if(sel.length > 0)
									{
										Ext.each(sel, function(r){
											if( r.get('status') != 'REQUEST' ){
												request_check_flag = 'N';
											}else{
												sel_request_id.push(r.get('request_id'));
											}
										});

										if(request_check_flag == 'N') {
											//Only not requested contents cannot confirm.
											Ext.Msg.alert(_text('MN00023'), _text('MSG02119'));
											return;
										}
										
										Ext.Msg.show({
					 						title: _text('MN00021'),
					 						msg : _text('MSG02186'),
					 						buttons : Ext.Msg.YESNO,
					 						fn : function(button){
					 							if(button == 'yes')
					 							{
					 								Ext.Ajax.request({
														url : '/store/archive/insert_archive_request.php',
														params : {
															request_ids : Ext.encode(sel_request_id),
															case_management : 'reject',
															reject_reason: reject_reason
														},
														callback : function(opt, success, res){
															if(success)
															{
																 var msg = Ext.decode(res.responseText);
																 if(msg.success)
																 {
																 	//Ext.Msg.alert(' 완 료',msg.msg);
																 	Ext.getCmp('archive_inform_id').getStore().reload();
																 }
																 else {
																	Ext.Msg.alert( _text('MN01039'), msg.msg);//'오류'
																 }
															}
															else
															{
																Ext.Msg.alert( _text('MN01098'), res.statusText);//'서버 오류'
															}
														}
													})
					 							}
					 						}
					 					});

									}else {
										Ext.Msg.alert( _text('MN01039'), _text('MSG01005'));
									}

									win.destroy();
								}
							},{
								text : '<span style="position:relative;top:1px;"><i class="fa fa-close style="font-size:13px;"></i></span>&nbsp;'+_text('MN00004'),
								scale: 'medium',
								handler: function(btn,e){
									win.destroy();
								}
							}]
						});
						win.show();
					}
				}else{
					Ext.Msg.alert( _text('MN01039'), _text('MSG01005'));
				}
			}
		}
		<?php } ?>
		,{
			//icon:'/led-icons/arrow_refresh.png',
			//text: '초기화',
			cls: 'proxima_button_customize',
			width: 30,
			text: '<span style="position:relative;top:1px;" title="'+_text('MN02096')+'"><i class="fa fa-refresh" style="font-size:13px;color:white;"></i></span>',
			handler : function(btn, e){
				var sm = Ext.getCmp('archive_inform_id').getSelectionModel();
				sm.clearSelections();
				Ext.getCmp('archive_inform_id').getStore().reload();
			}
		}],
		xtype: 'grid',
		layout: 'fit',
		id: 'archive_inform_id',
		title: '<span class="user_span"><span class="icon_title"><i class=""></i></span><span class="main_title_header">'+_text('MN02411')+'</span></span>',
		cls: 'grid_title_customize proxima_customize',
		stripeRows: true,
		border: false,
		loadMask: true,
		columnWidth: 1,
		store: list_store,
		disableSelection: true,
		listeners: {
			viewready: function(self){
				self.store.load({
					params: {
						start: 0,
						limit: v_page_size,
						start_date: Ext.getCmp('archive_start_date').getValue().format('Ymd000000'),
						end_date: Ext.getCmp('archive_end_date').getValue().format('Ymd240000')
					}

				})
			},
			rowdblclick: {
				fn: function(self, rowIndex, e){
					var index =0;
					var sm = self.getSelectionModel();
					var sel = Ext.getCmp('archive_inform_id').getSelectionModel().getSelections();
					if(sel.length>0)
					{
						for(var i=0;i<sel.length;i++)
						{
							index = Ext.getCmp('archive_inform_id').getStore().indexOf(sel[i]);
							sm.selectRow(index,true);
						}
					}
					if(sm.isSelected(rowIndex))
					{
						sm.deselectRow(rowIndex);
					}
					else  sm.selectRow(rowIndex,true);
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
				{header: _text('MN00138'),dataIndex:'status',align:'center',sortable:'true',width:80},
				{header: 'request_id', dataIndex: 'request_id',hidden:true},
				{header: 'content_id', dataIndex: 'content_id',hidden:true},
				{header: _text('MN02084'),dataIndex:'request_type',align:'center',sortable:'true',width:60},
				{header: _text('MN00300'),dataIndex:'media_type',align:'center',sortable:'true',width:80, hidden : true},
				{header: _text('MN00279'),dataIndex:'bs_content_title',align:'center',sortable:'true',width:100},
				{header: _text('MN00197'), dataIndex: 'ud_content_title', align:'center',sortable:'true',width:120},
				{header: _text('MN00387'), dataIndex: 'category', align:'left',sortable:'true',width:120},
				{header: _text('MN00249'), dataIndex:'title_case',align:'left',sortable:'true',width:200},
				{header: _text('MN00301'), dataIndex:'file_size',align:'center',sortable:'true',width:80},
				{header: _text('MN00172'), dataIndex: 'path',align:'left',sortable:'true',width:120},
				{header: _text('MN00218'), dataIndex:'request_user_id',align:'center',sortable:'true',width:80}	,
				{header: _text('MN02423'), dataIndex: 'comments',align:'left',sortable:'true',width:160, renderer : function(value, metadata) {
					if(!Ext.isEmpty(value))
					{
						metadata.attr = 'ext:qtip="' + value + '"';
					}
					return value;
				}},
				{header: _text('MN02412'), dataIndex:'created_datetime',align:'center',sortable:'true',width:170 ,renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s'),sortable:'true',width:150},
				{header: _text('MN00156'), dataIndex: 'reason',align:'left',sortable:'true',width:160, renderer : function(value, metadata) {
					if(!Ext.isEmpty(value))
					{
						metadata.attr = 'ext:qtip="' + value + '"';
					}
					return value;
				}},
				{header: _text('MN02514'), dataIndex:'start_frame_code',align:'center',sortable:'true',width:150},
				{header: _text('MN02515'), dataIndex:'end_frame_code',align:'center',sortable:'true',width:150},
				{header: _text('MN02137'), dataIndex:'date_time',align:'center',sortable:'true',width:170 ,renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s'),sortable:'true',width:150},
			]
		}),

		view: new Ext.ux.grid.BufferView({
			rowHeight: 20,
			//forceFit: true,
			scrollDelay: false,
			emptyText : _text('MSG00148')//'결과 값이 없습니다.'
		}),

		bbar: new Ext.PagingToolbar({
			store: list_store,
			pageSize: v_page_size,
			items:[{
				id : 'toolbartext',
				xtype:'tbtext',
				pageX:'100',
				pageY:'100',
				text : _text('MN02165')+" : "+total_list
			}]
		})

	}
})()