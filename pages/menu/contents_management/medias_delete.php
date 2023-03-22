<?php

/////////////////////////////
// 콘텐츠 삭제 관리 보여주는 페이지
// 2011.12.15
// by 허광회
/////////////////////////////

require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lang.php');


	$del_complete_code = 'DC'; // 삭제 완료
	$del_error_code = 'DO'; // 삭제 에러
	$del_admin_approve_code = 'DA';//관리자가 삭제 수락
	$del_request_code = 'DR'; // 삭제 요청
	$del_limit_code = 'DL'; //기한만료

	//config

	$admin_auth_user_del_list = true;

//$total = $db->queryOne("select count(*) from bc_content c");
//$del_suc = $db->queryOne("select  count(*) from bc_content c where  c.IS_DELETED='N' ");
$total = $db->queryOne("select count(*) from bc_media");
$del_suc = $db->queryOne("select  count(*) from bc_media bm where bm.flag ='del_complete_code'");

?>

(function(){

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
	var delete_inform_size = 20;

	var selModel = new Ext.grid.CheckboxSelectionModel({
	     singleSelect : false,
	     checkOnly : true


	 });

	var delete_store = new Ext.data.JsonStore({
		url:'/store/contents_deleted_info.php',
		root: 'data',
		totalProperty : 'total_list',
		idProperty: 'media_id',
		fields: [
			{name: 'contentd_id'},
			{name: 'ud_content_id'},
			{name: 'contentType'},
			{name: 'category'},
			{name: 'title'},
			{name: 'created_time',type:'date',dateFormat:'YmdHis'},
//			{name: 'delete_date', type: 'date',dateFormat: 'YmdHis'},
			{name: 'reg_user_id'},
			{name: 'expired_date',type: 'date',dateFormat: 'YmdHis'},
			{name: 'delete_result'},
			{name: 'file_size'},
			{name: 'flag'},
			'media_id',
			'path',
			'media_type',
			'deleted_date'
		],

		listeners: {
			beforeload: function(self, opts){
				opts.params = opts.params || {};

				Ext.apply(opts.params, {
					start_date: Ext.getCmp('start_date').getValue().format('Ymd000000'),
					end_date: Ext.getCmp('end_date').getValue().format('Ymd240000')
				});

			},
			load: function(self, opts){
				total_list = self.getTotalCount();
				var tooltext = "( 총 미디어 수: <b><?=$total?></b> | 검색된 미디어 수 : <font color=blue><b>"+total_list +"</b></font> )";
				Ext.getCmp('toolbartext').setText(tooltext);
			}

			//load: sortChanges
		}
	});

	return {
		border: false,
		loadMask: true,
		frame:true,
		width:800,
		//>>tbar: [' 삭제여부: ',{
		tbar: [' 구분 : ',{
			xtype:'combo',
			id:'delete_combo',
			mode:'local',
			width:80,
			triggerAction:'all',
			editable:false,
			displayField:'d',
			valueField:'v',
			value : '전체보기',
			//>>emptyText:'검색',
			emptyText:'<?=_text('MN00037')?>',
			store: new Ext.data.ArrayStore({
				fields:[
					'd','v'
				],
				data:[
				//>>['전체보기','all'],
				//>>['삭제성공','delete_success'],
				//>>['삭제실패','delete_failure']
					['<?=_text('MN00008')?>','all'],
					['사용자요청','delete_request'],
					['기한만료','delete_limit'],
					['삭제완료','delete_success'],
				//>>['<?=_text('MN00129')?>','delete_success'],
					['삭제실패','delete_failure'],
				//>>['<?=_text('MN00130')?>','delete_failure'],
					['삭제승인','delete_approve']
				]
			}),
			listeners:{
				select:{
					fn:function(self,record,index){
						var search_val = Ext.getCmp('delete_combo').getValue();
							Ext.getCmp('delete_inform_id').getStore().load({
							params: {
								start: 0,
								limit: delete_inform_size,
								date_mode : Ext.getCmp('delete_date_combo').getValue(),
								start_date: Ext.getCmp('start_date').getValue().format('Ymd000000'),
								end_date: Ext.getCmp('end_date').getValue().format('Ymd240000'),
								action : search_val
								}
							});
					}
				}
			}
		//>>},'-','삭제된 날짜 : '{

		},'-','날짜검색',
		{
			xtype:'combo',
			id:'delete_date_combo',
			mode:'local',
			width:80,
			triggerAction:'all',
			editable:false,
			displayField:'d',
			valueField:'v',
			value : '',
			//>>emptyText:'검색',
			emptyText:'선택',
			store: new Ext.data.ArrayStore({
				fields:[
					'd','v'
				],
				data:[
					['선택안함','disable'],
					['등록일자','created_date'],
					['만료일자','expired_date'],
					['구분관련일자','deleted_date']
				]
			}),
			listeners:{
				select:{
					fn:function(self,record,index){
						var search_val = Ext.getCmp('delete_date_combo').getValue();
						if(search_val == 'disable')
						{
							Ext.getCmp('start_date').disable();
							Ext.getCmp('end_date').disable();

						}else{
								Ext.getCmp('start_date').enable();
								Ext.getCmp('end_date').enable();
						}
					}
				}
			}
		}
		,'-',{
			xtype: 'datefield',
			id: 'start_date',
			disabled : true,
			editable: true,
			format: 'Y-m-d',
			listeners: {
				render: function(self){
					var d = new Date();

					self.setMaxValue(d.format('Y-m-d'));
					self.setValue(d.add(Date.MONTH, -12).format('Y-m-d'));
				}
			}
		},
		//>>'부터'
		'<?=_text('MN00183')?>'
		,{
			xtype: 'datefield',
			id: 'end_date',
			editable: true,
			format: 'Y-m-d',
			disabled : true,
			listeners: {
				render: function(self){
					var d = new Date();

					self.setMaxValue(d.format('Y-m-d'));
					self.setValue(d.format('Y-m-d'));
				}
			}
		},'-',{
			icon: '/led-icons/find.png',
			//>>text: '조회',
			text: '<?=_text('MN00059')?>',
			handler: function(btn, e){
				var search_val = Ext.getCmp('delete_combo').getValue();
				//>>if((search_val=='전체보기')||Ext.isEmpty(search_val))
					Ext.getCmp('delete_inform_id').getStore().load({
						params: {
								start: 0,
								limit: delete_inform_size,
								date_mode : Ext.getCmp('delete_date_combo').getValue(),
								start_date: Ext.getCmp('start_date').getValue().format('Ymd000000'),
								end_date: Ext.getCmp('end_date').getValue().format('Ymd240000'),
								action : search_val
						}

					});

			}
		},'-',{
			icon: '/led-icons/application_get.png',
			text: '기한연장',
			handler : function(btn,e){
				var sel = Ext.getCmp('delete_inform_id').getSelectionModel().getSelections();
				var sel_id = new Array();
				if(sel.length>0)
				{
					for(var i=0;i<sel.length;i++)
					{
						//flag 값에 대해 처리
						if(sel[i].get('flag') == '삭제완료')
						{
							Ext.Msg.alert('오류','삭제완료 목록은 연장하실 수 없습니다.');
							return;
						}else if(sel[i].get('flag') == '사용자 요청')
						{
							Ext.Msg.alert('오류','사용자요청 목록은 연장하실 수 없습니다.');
							return;
						}
						sel_id.push(sel[i].get('media_id'));
					}

					Ext.Ajax.request({
						url: '/pages/menu/contents_management/contents_extend.php',
						params : {
							ids : Ext.encode(sel_id)
						},
						callback :function(opt, success, resp){
							if(success)
							{
								 Ext.decode(resp.responseText);
							}
							else
							{
								 Ext.Msg.alert( _text('MN01098'), resp.statusText);//'서버 오류'
							}
						}
					});
				}
				else {
					Ext.Msg.alert('오류', '선택 된 아이템이 없습니다.');
				}

			}
		},'-',{
			icon: '/led-icons/application_edit.png',
			text: '삭제승인',
			handler : function(btn, e){
				var sel = Ext.getCmp('delete_inform_id').getSelectionModel().getSelections();
				var sel_id = new Array();
				if(sel.length>0)
				{
					for(var i=0;i<sel.length;i++)
					{
						if(sel[i].get('flag') == '삭제완료')
						{
							Ext.Msg.alert('오류','삭제완료 목록은 연장하실 수 없습니다.');
							return;

						}else if(sel[i].get('flag') == '삭제승인')
						{
							Ext.Msg.alert('오류','벌써 승인하였습니다.');
							return;
						}
						<?
						if(!$admin_auth_user_del_list)
							echo("
								else if(sel[i].get('flag') == '사용자 요청')
								{
									Ext.Msg.alert('오류','사용자 요청에 대한 삭제승인권한이 설정되지 않았습니다');
									return;
								}
								")

						?>
						sel_id.push(sel[i].get('media_id'));
					}

					Ext.Msg.show({
 						title: ' 경 고 ',
 						msg : '다음 선택하신 목록을 승인 하시겠습니까?',
 						buttons : Ext.Msg.YESNO,
 						fn : function(button){
 							if(button == 'yes')
 							{
 									Ext.Ajax.request({
									url : '/pages/menu/contents_management/content_delete_action.php',
									params : {
										ids : Ext.encode(sel_id),
										action : 'approve',
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
 							}else {



 							}
 						}

 					});

				}
				else {
					Ext.Msg.alert('오류','선택 된 아이템이 없습니다.');
				}
			}
		},'-',{
			icon:'/led-icons/application_delete.png',
			text: '삭제취소',
			handler : function(btn, e){
				var sel = Ext.getCmp('delete_inform_id').getSelectionModel().getSelections();
				var sel_id = new Array();
				var now = new Date();
				var sel_expire_id = new Array();
				if(sel.length>0)
				{
					for(var i=0;i<sel.length;i++)
					{
						if(sel[i].get('flag') == '삭제완료')
						{
							Ext.Msg.alert('오류','삭제완료 목록이 있습니다. ');
							return;

						}else if(sel[i].get('flag') == '사용자 요청')
						{
							Ext.Msg.alert('오류','사용자 요청 목록이 있습니다.');
							return;
						}else if(sel[i].get('flag') == '기한만료')
						{
							Ext.Msg.alert('오류','기한만료 목록이 있습니다.');
							return;
						}else if(sel[i].get('flag') == '삭제실패')
						{
							Ext.Msg.alert('오류','삭제실패 목록이 있습니다.');
							return;
						}

						if(sel[i].get('expired_date')<now)
						{
							sel_expire_id.push(sel[i].get('media_id'));
						}else  sel_id.push(sel[i].get('media_id'));
					}

					Ext.Msg.show({
 						title: ' 경 고 ',
 						msg : '다음 선택하신 목록을 삭제승인 취소 하시겠습니까? <br>만료기한이 지난 목록은 만료기한으로 처리됩니다.',
 						buttons : Ext.Msg.YESNO,
 						fn : function(button){
 							if(button == 'yes')
 							{
 									Ext.Ajax.request({
									url : '/pages/menu/contents_management/content_delete_action.php',
									params : {
										ids : Ext.encode(sel_id),
										action : 'cancel',
										ids_expire : Ext.encode(sel_expire_id)
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
 							}else {



 							}
 						}

 					});

				}
				else {
					Ext.Msg.alert('오류','선택 된 아이템이 없습니다.');
				}
			}
		},'-',{
			icon:'/led-icons/arrow_refresh.png',
			text: '선택 초기화',
			handler : function(btn, e){
				var sm = Ext.getCmp('delete_inform_id').getSelectionModel();
				sm.clearSelections();
			}
		}],
		xtype: 'grid',
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
						end_date: Ext.getCmp('end_date').getValue().format('Ymd240000')
					}

				})
			},
			rowdblclick: {
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
				{header: '구분',dataIndex:'flag',align:'center',sortable:'true',width:100},
				{header: '파일종류',dataIndex:'media_type',align:'center',sortable:'true',width:100},
				{header: '콘텐츠 종류',dataIndex:'contentType',align:'center',sortable:'true',width:100},
				{header: '콘텐츠 구분', dataIndex: 'ud_content_id', align:'center',sortable:'true',width:100},
				{header: '카테고리위치', dataIndex: 'category', align:'left',sortable:'true',width:200},
				{header: '제목', dataIndex:'title',align:'center',sortable:'true',width:250},
				{header: '파일크기', dataIndex:'file_size',align:'center',sortable:'true',width:80},
				{header: '파일경로', dataIndex: 'path',align:'left',sortable:'true',width:120},
				{header: '등록자', dataIndex:'reg_user_id',align:'center',sortable:'true',width:100}	,
				{header: '등록일자', dataIndex:'created_time',align:'center',sortable:'true',width:120 ,renderer: Ext.util.Format.dateRenderer('Y-m-d'),sortable:'true',width:100},
				{header: '만료기한', dataIndex:'expired_date',align:'center',sortable:'true',width:120 ,renderer: Ext.util.Format.dateRenderer('Y-m-d'),sortable:'true',width:100},
				{header: '구분관련일자', dataIndex: 'deleted_date', width: 80,align:'center',
							renderer: function(value, metaData, record, rowIndex, colIndex, store) {
								if(value)
								{
									return value.substring(0,4)+'-'+value.substring(4,6)+'-'+value.substring(6,8);
									}
								else return ;
						   }

				}
			]
		}),

		view: new Ext.ux.grid.BufferView({
			rowHeight: 20,
			scrollDelay: false
		}),

		bbar: new Ext.PagingToolbar({
			store: delete_store,
			pageSize: delete_inform_size,
			items:[{
				id : 'toolbartext',
				xtype:'tbtext',
				pageX:'100',
				pageY:'100',
				//>>text: '(총 미디어 수: <?=$total?>  |  삭제된 미디어 수: <?="<font color=red>".$del_suc."</font>"?>  |  존재하는 미디어의 수: <?="<font color=blue>".($total-$del_suc)."</font>"?>)'
				text : "리스트 수 : "+total_list
			}]
		})

	}
})()