<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
session_start();
?>
(function(){

	var delete_list_size = 100;

	var delete_store = new Ext.data.JsonStore({
		url:'/store/get_delete_list.php',
		root: 'data',
		totalProperty: 'total',
		fields: [
			{name: 'content_id'},
			{name : 'user_id'},
			{name : 'user_nm'},
			{name : 'title'},
			{name : 'category_title'},
			{name : 'category_path'},
			{name : 'bs_content_title'},
			{name : 'ud_content_title'},
			{name : 'ud_content_id'},
			{name:'created_date',type:'date',dateFormat:'YmdHis'},
			{name: 'reason'},
			{name: 'id'}
		],
		listeners: {
			beforeload: function(self, opts){
				opts.params = opts.params || {};

				Ext.apply(opts.params, {
					start_date: Ext.getCmp('start_date').getValue().format('Ymd000000'),
					end_date: Ext.getCmp('end_date').getValue().format('Ymd240000')
				});
			}
		}
	});



	return {
		border: false,
		loadMask: true,
		frame:true,
		width:800,
		tbar: [	'삭제요청일자 : ',{
			xtype: 'datefield',
			id: 'start_date',
			editable: true,
			width: 120,
			format: 'Y-m-d',
			listeners: {
				render: function(self){
					var d = new Date();
					self.setMaxValue(d.format('Y-m-d'));
					self.setValue(d.add(Date.MONTH, -12).format('Y-m-d'));
				}
			}
		},
		_text('MN00183')
		,{
			xtype: 'datefield',
			id: 'end_date',
			width: 120,
			editable: true,
			format: 'Y-m-d',
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
			text: _text('MN00059'),
			handler: function(btn, e){

				Ext.getCmp('delete_list').getStore().load({
					params:{
						start:0,
						limit:delete_list_size
					}

				});
			}
		},'-',{
			icon: '/led-icons/arrow_redo.png',
			text: '복원',
			handler: function(b, e){
				var sm = Ext.getCmp('delete_list').getSelectionModel();

				if( !sm.hasSelection() ) return;

				//Ext.Msg.alert( _text('MN00023'), '작업중입니다.');
				//return;
				var content_ids = [];
				Ext.each(sm.getSelections(),function(r){
					content_ids.push({
						content_id : r.get('content_id'),
						ud_content_id : r.get('ud_content_id')
					});
				});

				Ext.Ajax.request({
					url: '/store/delete_recovery.php',
					params: {
						content_ids : Ext.encode(content_ids)
					},
					callback: function(opts, success, response){
						if (success){
							try{
								var result = Ext.decode(response.responseText);
								if (result.success){
									Ext.getCmp('delete_list').getStore().reload();
								}
								Ext.Msg.alert( _text('MN00023'), result.msg);
							}catch (e){
								Ext.Msg.show({
									title: '오류',
									msg: e['message'],
									icon: Ext.Msg.ERROR,
									buttons: Ext.Msg.OK
								});
							}
						}else{

						}
					}
				});

			}
		}

		,{
			hidden: true,
			text: '삭제',
			handler: function(){
				var check = Ext.getCmp('delete_list').getSelectionModel().hasSelection();
				if(check)
				{
					var content_id = Ext.getCmp('delete_list').getSelectionModel().getSelected().json['content_id'];
					Ext.Msg.show({
						title: '삭제',
						msg: '삭제하시겠습니까?',
						buttons: {
							yes: true,
							no: true
						},
						fn: function(btn){
							if(btn == 'yes')
							{
								Ext.Ajax.request({
									url: '/store/delete_deleteList.php',
									params: {
										content_id : content_id
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
														title: '오류',
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
								delete_store.reload();
							}
						}
					});
				}
				else
				{
					Ext.Msg.alert('삭제', '삭제할 항목을 선택 해 주세요.');
				}
			}
		}],
		xtype: 'grid',
		id: 'delete_list',
		loadMask: true,
		columnWidth: 1,
		store: delete_store,

		listeners: {
			viewready: function(self){
				self.store.load({
					params: {
						start: 0,
						limit: delete_list_size,
						start_date: Ext.getCmp('start_date').getValue().format('Ymd000000'),
						end_date: Ext.getCmp('end_date').getValue().format('Ymd240000')
					}
				})
			},
			rowcontextmenu: function(self, rowIndex, e){
				e.stopEvent();
				var menu = new Ext.menu.Menu({
					items : [],
					listeners: {
						itemclick: function(){
							var check = Ext.getCmp('delete_list').getSelectionModel().hasSelection();
							if(check){
								var content_id = Ext.getCmp('delete_list').getSelectionModel().getSelected().json['content_id'];
								Ext.Msg.show({
									title: '삭제',
									msg: '삭제하시겠습니까?',
									buttons: {
										yes: true,
										no: true
									},
									fn: function(btn){
										if(btn == 'yes'){
											Ext.Ajax.request({
												url: '/store/delete_deleteList.php',
												params: {
													content_id : content_id
												},
												callback: function(opts, success, response){
													if (success){
														try{
															var result = Ext.decode(response.responseText);
															if (!result.success){
																Ext.Msg.show({
																	title: '오류',
																	msg: result.msg,
																	icon: Ext.Msg.ERROR,
																	buttons: Ext.Msg.OK
																});
															}
														}catch (e){
															Ext.Msg.show({
																title: '오류',
																msg: e['message'],
																icon: Ext.Msg.ERROR,
																buttons: Ext.Msg.OK
															});
														}
													}else{

													}
												}
											});
											delete_store.reload();
										}
									}
								});
							}else{
								Ext.Msg.alert('삭제', '삭제할 항목을 선택 해 주세요.');
							}
						}

					}
				});

				menu.showAt(e.getXY());
			}
		},
		sm: new Ext.grid.RowSelectionModel({
					multiSelect : true
		}),
		cm: new Ext.grid.ColumnModel({
			defaults:{
				sortable: true
			},

			columns: [
				new Ext.grid.RowNumberer(),
				{header: 'content_id', dataIndex: 'content_id',id : 'content_id', hidden:true},
				//{header: '콘텐츠 타입', dataIndex: 'bs_content_title',align:'center',sortable:'true',width:100},
				{header: '콘텐츠 유형', dataIndex: 'ud_content_title',align:'center',sortable:'true',width:100},
				{header: '카테고리', dataIndex: 'category_title',align:'center',sortable:'true',width:100},
				{header: '콘텐츠 제목', dataIndex: 'title', align:'center',sortable:'true',width:200},//content_id
				{header: '요청자 사원번호', dataIndex: 'user_id',align:'center',sortable:'true',width:100},//user_id
				{header: '요청자', dataIndex: 'user_nm',align:'center',sortable:'true',width:100},
				{header: '요청일자', dataIndex: 'created_date', align:'center', renderer: Ext.util.Format.dateRenderer('Y-m-d'),sortable:'true',width:100},
				{header: '삭제 요청 사유', dataIndex: 'reason', align:'center',sortable:'true',width:300}
			]
		}),

		view: new Ext.ux.grid.BufferView({
			rowHeight: 18,
			scrollDelay: false
		}),
		bbar: new Ext.PagingToolbar({
			store: delete_store,
			pageSize: delete_list_size,
			items:[{
				xtype:'tbtext',
				pageX:'100',
				pageY:'100'
			}]
		})

	}
})()