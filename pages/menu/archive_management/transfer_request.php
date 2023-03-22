<?php

/////////////////////////////
// CHA 광화문/상암동 전송요청 관리
// 2014 07 31	이승수
/////////////////////////////

session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

$arr_info_msg = getStoragePolicyInfo();
$info = $arr_info_msg['info1_2'];
$info2 = $arr_info_msg['info2_2'];

$user_id = $_SESSION['user']['user_id'];

?>		  

(function(){            
     
	var total_list = 0;
	var delete_inform_size = 50;
	
	//var selModel = new Ext.grid.CheckboxSelectionModel({
	var selModel = new Ext.grid.RowSelectionModel({   
		singleSelect : false
	});
    
	var transfer_request_store = new Ext.data.JsonStore({
		url:'/pages/menu/archive_management/get_transfer_request.php',
		root: 'data',
		totalProperty : 'total_list',		
		idProperty: 'content_id',
		fields: [
			'req_no',
			{name: 'req_time',type:'date',dateFormat:'YmdHis'},
			'req_type',
			'req_status',
			'content_id',
			'user_nm',
			'mtrl_id',
			'mtrl_nm',
			'pgm_id',
			'pgm_nm',
			'title',
			'ud_content_title',
			'ud_content_id',
			'nps_content_id',
			'req_comment',
			'user_info',
			'task_status',
			'progress'
		],
		 
		listeners: {
			beforeload: function(self, opts){
				opts.params = opts.params || {};

				Ext.apply(opts.params, {
					arc_start_date: Ext.getCmp('transfer_request_arc_start_date').getValue().format('Ymd000000'),
					arc_end_date: Ext.getCmp('transfer_request_arc_end_date').getValue().format('Ymd240000'),
					req_type: Ext.getCmp('transfer_request_ud_combo').getValue(),
					mtrl_id : Ext.getCmp('transfer_request_mtrl_id').getValue()
				});				
						
			},
			load: function(self, opts){				
				total_list = self.getTotalCount();	
				var tooltext = "( 검색된 미디어 수 : <font color=blue><b>"+total_list +"</b></font> )";
				Ext.getCmp('transfer_request_toolbartext').setText(tooltext);
				
				var storage_info = self.reader.jsonData.info;
				Ext.getCmp('transfer_request_storage_info').setValue(storage_info);
				var storage_info2 = self.reader.jsonData.info2;
				Ext.getCmp('transfer_cache_storage_info').setValue(storage_info2);
			}
			
			//load: sortChanges
		}
	});

	var tbar1 = new Ext.Toolbar({
		dock: 'top',
        items: [' 구분',{
			xtype:'combo',
			id:'transfer_request_ud_combo',
			mode:'local',
			width:80,
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
					['NPS등록','nps_reg'],
					['ICMS등록','cis_reg']
				]
			}),
			listeners:{
				select:{
					fn:function(self,record,index){
						transfer_request_grid.getStore().reload();
					}
				}	
			}
		},'-','의뢰일시',
		{
			xtype: 'datefield',
			id: 'transfer_request_arc_start_date',
			editable: true,
			width: 105,
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
			id: 'transfer_request_arc_end_date',
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
		},'-','검색어',{
			xtype: 'textfield',
			id: 'transfer_request_mtrl_id',
			listeners: {
				specialKey: function(self, e){
					if (e.getKey() == e.ENTER) {
						e.stopEvent();
						transfer_request_grid.getStore().reload({params:{start: 0}});
					}
				}
			}
		},'-',{
			icon: '/led-icons/find.png',
			//>>text: '조회',
			text: '<?=_text('MN00047')?>',
			handler: function(btn, e){
				transfer_request_grid.getStore().reload({params:{start: 0}});
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
				var sel = transfer_request_grid.getSelectionModel().getSelections();
				var sel_id = new Array();
				var is_break = false;
				var break_msg = '';
				if(sel.length>0)
				{					
					for(var i=0;i<sel.length;i++)
					{
//						if( !Ext.isEmpty(sel[i].get('flag')) )
//						{
//							break_msg = '승인대기인 항목만 선택 해 주시기 바랍니다.';
//							is_break = true;
//						}
						sel_id.push(sel[i].get('req_no'));
					}
					
					if(false)//is_break)
					{
						Ext.Msg.alert('오류', break_msg);
						return;
					}
					else
					{
						Ext.Msg.show({
							title: '확인',
							msg : '선택하신 요청목록을 승인 하시겠습니까?',
							buttons : Ext.Msg.YESNO,
							fn : function(button){
								if(button == 'yes')
								{
									Ext.Ajax.request({
										url : '/pages/menu/archive_management/action_transfer_request.php',
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
													transfer_request_grid.getStore().reload();
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
			icon: '/led-icons/cancel.png',
			text: '반려',
			tooltip: '요청된 항목을 반려합니다.',
			handler : function(btn, e){
				var sel = transfer_request_grid.getSelectionModel().getSelections();
				var sel_id = new Array();
				var is_break = false;
				var break_msg = '';
				if(sel.length>0)
				{					
					for(var i=0;i<sel.length;i++)
					{
						sel_id.push(sel[i].get('req_no'));
					}
					
					if(false)//is_break)
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
										url : '/pages/menu/archive_management/action_transfer_request.php',
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
													transfer_request_grid.getStore().reload();
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
		}]
	});

	var transfer_request_grid = new Ext.grid.EditorGridPanel({
		border: false,
		loadMask: true,
		frame: true,
		id: 'transfer_request_grid_id',
		width:800,
		tbar: new Ext.Container({
			height: 27,
			layout: 'anchor',
			xtype: 'container',
			defaults: {
				anchor: '100%',
				height: 27
			},
			items: [
				tbar1
			]
		}),
		//xtype: 'editorgrid',
		clicksToEdit: 1,
		loadMask: true,
		columnWidth: 1,
		store: transfer_request_store,
		disableSelection: true,
		
		listeners: {
			viewready: function(self){
				self.store.load({
					params: {						
						start: 0,
						limit: delete_inform_size,
						arc_start_date: Ext.getCmp('transfer_request_arc_start_date').getValue().format('Ymd000000'),
						arc_end_date: Ext.getCmp('transfer_request_arc_end_date').getValue().format('Ymd240000')
					}					
				});
				self.add(tbar2);
			},
			rowdblclick: function(self, rowIndex, e){				
				var sm = self.getSelectionModel().getSelected();							

				var content_id = sm.get('nps_content_id');
				var req_type = sm.get('req_type');
				var mtrl_id = sm.get('mtrl_id');
				var req_comment = sm.get('req_comment');
				var url = '';

				//상세보기는 NPS자료, CMS자료, DAS자료로 구분된다.
				if(req_type == 'cis_reg') {		//NPS 자료
					url = 'http://<?=SERVER_IP_NPS?>/detailview.php?direct=true&user_id=<?=$user_id?>&content_id='+content_id;
					var IFrame = new Ext.Panel({
						layout: 'fit',
						html: '<iframe frameborder=0 width="100%" height="100%" src="'+url+'"></iframe>'
					});

					var win = new Ext.Window({
						width: '95%',		
						top: 50,
						height: 550,
						minHeight: 500,
						minWidth: 800,
						modal: true,
						layout: 'fit',
						maximizable: true,
						items:[
							IFrame
						],
						listeners: {
							render: function(self){
								self.mask.applyStyles({
									"opacity": "0.8",
									"background-color": "#FFFFCC"
								});

								var pos = self.getPosition();
								if(pos[0]<0)
								{
									self.setPosition(0,pos[1]);
								}
								else if(pos[1]<0)
								{
									self.setPosition(pos[0],0);
								}

							},
							move: function(self, x, y){//창이 윈도우 포지션을 벗어났을때 0으로 셋팅
								var pos = self.getPosition();
								if(pos[0]<0)
								{
									self.setPosition(0,pos[1]);
								}
								else if(pos[1]<0)
								{
									self.setPosition(pos[0],0);
								}
							},

							close: function(self){

								/*
								var p = Ext.getCmp('detail_panel').checkModified();
								if (p == false)
								{
									return false;
								}
								*/
							}
						}
					});
					win.show();
					//Ext.Msg.alert('요청사유', req_comment);
				} else {		//CMS 자료
					var sm = self.getSelectionModel().getSelected();
				
					//>>self.load = new Ext.LoadMask(Ext.getBody(), {msg: '상세 정보를 불러오는 중입니다...'});
					self.load = new Ext.LoadMask(Ext.getBody(), {msg: _text('MSG00143')});
					self.load.show();
					var that = self;

					if ( !Ext.Ajax.isLoading(self.isOpen) )
					{
						var url = '/javascript/ext.ux/Detailpanel/media_show.php';			

						Ext.Ajax.request({
							url: url,
							params: {
								mtrl_id: mtrl_id
							},
							callback: function(self, success, response){
								if (success) {
									try
									{
										var r = Ext.decode(response.responseText);
										if ( r !== undefined && !r.success)	{
											Ext.Msg.show({
												title: '경고'
												,msg: r.msg
												,icon: Ext.Msg.WARNING
												,buttons: Ext.Msg.OK
											});
										}
										//Ext.Msg.alert('요청사유', req_comment);
									}
									catch (e)
									{
										
									}
								}
								else
								{
									//>>Ext.Msg.alert('서버 오류', response.statusText+'('+response.status+')');
									Ext.Msg.alert(_text('MN00022'), response.statusText+'('+response.status+')');
								}
							}
						});
					} 
					//url = '/pages/menu/archive_management/transfer_request_detail_win.php?mtrl_id='+mtrl_id;
				}				
			}			
		},
		
		sm : selModel,
		
		cm: new Ext.grid.ColumnModel({
			defaults:{
				sortable: false
			},

			columns: [	
				new Ext.grid.RowNumberer(),
				
				{header: '의뢰상태',dataIndex:'req_status',align:'center',width:70,
					renderer: mapReqStatus,hidden:true},
				{header: '의뢰구분',dataIndex:'req_type',align:'center',width:70,
					renderer: mapReqType},
				{header: '전송상태',dataIndex:'task_status',align:'center',width:70,
					renderer: mapTaskstatus},
				{header: '의뢰일시', dataIndex:'req_time',align:'center',menuDisabled: true,width:150,
					renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s')},
				{header: '의뢰자',dataIndex:'user_info',align:'center',menuDisabled: true,width:150},
				{header: '소재구분',dataIndex:'ud_content_title',align:'center',width:70,menuDisabled: true},
				{header: '소재ID',dataIndex:'mtrl_id',align:'center',width:140,menuDisabled: true},
				{header: '<center>소재명</center>',dataIndex:'title',align:'left',width:250,menuDisabled: true},
				{header: '프로그램ID',dataIndex:'pgm_id',align:'center',width:100,menuDisabled: true},
				{header: '<center>프로그램명</center>',dataIndex:'pgm_nm',align:'left',width:250,menuDisabled: true},
				{header: '요청사유',dataIndex:'req_comment',align:'center',width:250,menuDisabled: true}
			]
		}),

		view: new Ext.ux.grid.BufferView({
			rowHeight: 20,
			scrollDelay: false,
			emptyText: '결과 값이 없습니다.'
		}),
		
		bbar: new Ext.PagingToolbar({
			store: transfer_request_store,
			pageSize: delete_inform_size,
			items:[{
				id: 'transfer_request_toolbartext',
				xtype:'tbtext',
				pageX:'100',
				pageY:'100',				
				text : "리스트 수 : "+total_list
			},'->',{
				xtype: 'displayfield',
				id: 'transfer_request_storage_info',
				value: '<?=$info?>'
			},{
				xtype: 'displayfield',
				width: 20
			},{
				xtype: 'displayfield',
				id: 'transfer_cache_storage_info',
				value: '<?=$info2?>'
			}]
		})

	});
	return transfer_request_grid;

	function mapTaskstatus(value)
	{
		if(value == 'complete')
		{
			return '<font color=blue>완료</font>';
		}
		else if(value =='error')
		{
			return '<font color=red>에러</font>';
		}
		else if(value =='progress' || value == 'processing')
		{
			return '<font color=red>진행중</font>';
		}
		else 
		{	
		
			if(!isNaN(parseInt(value)))
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

	function mapReqType(value, p, r){
		if(value == 'cis_reg') {
			return 'iCMS등록';
		} else if(value == 'nps_reg') {
			return 'NPS등록';
		} else {
			return value;
		}
	}

	function mapReqStatus(value, p, r){
		if(value == '1') {
			return '승인대기';
		} else if(value == '2') {
			return '반려';
		} else if(value == '3') {
			return '승인';
		} else if(value == '4') {
			return '작업완료';
		} else if(value == '5') {
			return '작업오류';
		} else {
			return value;
		}
	}
})()
