//////등록대기 그리드//////
//
//
//var registwait_size = 30;
//
//var registWait_store = new Ext.data.JsonStore({
//		url: '/store/regist_wait_list.php',
//		root: 'data',
//		totalProperty: 'total',
//		remoteSort: true,
//		sortInfo: {
//			field: 'created_time',
//			direction: 'DESC'
//		},
//		fields:
//		[
//			{name: 'title'},
//			{name: 'meta_table_type'},
//			{name: 'created_time', type: 'date', dateFormat: 'YmdHis'},
//			{name: 'status'},
//			{name: 'user_id'},
//			{name: 'source'},
//			{name: 'id'},
//			{name: 'content_type_id'},
//			{name: 'meta_table_id'},
//			{name: 'modified'}
//		],
//		listeners: {
//			load: function(self){
//			},
//			beforeload: function(self, opts){
//				self.baseParams = {
//					limit: registwait_size,
//					start: 0
//				}
//			}
//		}
//});
//registWait_store.load({params:{start:0, limit:registwait_size}});
//
//var waitlist = new Ext.grid.GridPanel({
//
//	title:'등록대기 리스트',
//	flex: 1,
//	height:515,
//	frame: true,
//	loadMask: true,
//	store: registWait_store,
//	columnWidth: 1,
//
//	/*
//	2011-02-07 박정근
//	탭 별로 검색 가능하도록 변경
//	*/
//	reload: function( args ){
//		this.store.reload({
//			params: args
//		})
//	},
//
//	sm: new Ext.grid.RowSelectionModel({
//			//singleSelect: true
//	}),
//	columns: [
//		 new Ext.grid.RowNumberer(),
//		{header: '제목', dataIndex: 'title', align:'center',sortable:'true'},
//		{header: '콘텐츠 유형', dataIndex: 'meta_table_type', align:'center',sortable:'true'},
//		{header: '요청자', dataIndex: 'user_id', align:'center',sortable:'true'},
//		{header: '출 처', dataIndex: 'source', align:'center',sortable:'true'},
//		{header: '등록 상태', dataIndex: 'status', align:'center',sortable:'true'},
//		{header: '등록 일시', dataIndex: 'created_time', align:'center', renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s'),sortable:'true'},
//		{header: '수정날짜', dataIndex: 'modified', hidden:true },
//		{header: 'id', dataIndex: 'id', hidden:true }
//	],
//	view: new Ext.ux.grid.BufferView({
//		rowHeight: 20,
//		scrollDelay: false,
//		getRowClass : function (r, rowIndex, rp, ds) {
//			if (!Ext.isEmpty(r.get('modified')))
//			{
//				return 'wait_list_modified';
//			}
//		},
//		forceFit: true,
//		emptyText: '검색 결과가 없습니다.'
//	}),
//	tbar:{
//		xtype:'toolbar',
//		items:[{
//				text: '새로고침',
//				icon: '/led-icons/arrow_refresh.png',
//				handler: function(){
//					Ext.getCmp('search_combo').reset();
//					Ext.getCmp('search_text').reset();
//					waitlist.getStore().load({
//						params: {
//							start: 0,
//							limit: registwait_size
//						}
//					});
//				}
//			},'-','기간 선택 : ',
//			{
//				xtype: 'datefield',
//				id: 'start_date_t',
//				editable: false,
//				format: 'Y-m-d',
//				listeners: {
//					render: function(self){
//						var d = new Date();
//						self.setMaxValue(d.format('Y-m-d'));
//						self.setValue(d.add(Date.DAY, -6).format('Y-m-d'));
//					}
//				}
//			},
//			'부터',
//			{
//				xtype: 'datefield',
//				id: 'end_date_t',
//				editable: false,
//				format: 'Y-m-d',
//				listeners: {
//					render: function(self){
//						var d = new Date();
//						self.setMaxValue(d.format('Y-m-d'));
//						self.setValue(d.format('Y-m-d'));
//					}
//				}
//			},'-',{
//				icon: '/led-icons/find.png',
//				text: '기간조회',
//				handler: function(btn, e){
//					var start_date = Ext.getCmp('start_date_t').getValue().format('Ymd000000');
//					var end_date = Ext.getCmp('end_date_t').getValue().format('Ymd240000');
//
//					waitlist.getStore().load({
//						params:	{
//							start_date: Ext.getCmp('start_date_t').getValue().format('Ymd000000'),
//							end_date: Ext.getCmp('end_date_t').getValue().format('Ymd240000'),
//							start: 0,
//							limit: registwait_size
//						}
//					});
//				}
//			},'-',/*{
//				icon: '/led-icons/find.png',
//				text: '전체보기',
//				handler: function(btn, e){
//					registWait_store.reload({
//						params:	{
//							start: 0,
//							limit: registwait_size
//						}
//					});
//				}
//			},'-',{
//				xtype:'tbtext',
//				align:'center',
//				text: '콘텐츠에 대한 다중 선택이 가능합니다. (우클릭: 등록완료, 더블클릭:상세정보)'
//			}*/
//			'검색: ',
//			{
//				xtype: 'combo',
//				id: 'search_combo',
//				//width: 65,
//				mode: 'local',
//				triggerAction: 'all',
//				editable: false,
//				displayField: 'd',
//				valueField: 'v',
//				value: '4012943',
//				emptyText: '검색명을 선택하세요',
//				store: new Ext.data.ArrayStore({
//					fields: [
//						'd', 'v'
//					],
//					data: [
//						['Tape No','4012943'],['프로그램명','81787'],['부제','81786']
//					]
//				})
//			},
//			{
//				xtype: 'textfield',
//				id: 'search_text',
//				emptyText: '검색어를 입력하세요.'
//				,
//				listeners:{
//					specialKey: function(self, e){
//						if( e.getKey() == e.ENTER )
//						{
//							e.stopEvent();
//							action = 'search';
//
//							var search_combo = Ext.getCmp('search_combo').getValue();
//							var search_text = Ext.getCmp('search_text').getValue();
//							if( !Ext.isEmpty( search_combo ) )
//							{
//								if( !Ext.isEmpty( search_text ) )
//								{
//									waitlist.getStore().load({
//										params: {
//											action: action,
//											search_combo: search_combo,
//											search_text: search_text,
//											start: 0,
//											limit: registwait_size
//										}
//									});
//								}
//								else
//								{
//									Ext.Msg.alert( _text('MN00023'),'검색어을 입력하세요.');
//								}
//							}
//							else
//							{
//								Ext.Msg.alert( _text('MN00023'),'검색명을 선택하세요.');
//							}
//						}
//					}
//				}
//
//			},'-',
//			{
//				text: '검색',
//				id: 'search_button',
//				icon: '/led-icons/find.png',
//				handler: function(){
//					action = 'search';
//
//					var search_combo = Ext.getCmp('search_combo').getValue();
//					var search_text = Ext.getCmp('search_text').getValue();
//					if( !Ext.isEmpty( search_combo ) )
//					{
//						if( !Ext.isEmpty( search_text ) )
//						{
//							waitlist.getStore().load({
//								params: {
//									action: action,
//									search_combo: search_combo,
//									search_text: search_text,
//									start: 0,
//									limit: registwait_size
//								}
//							});
//						}
//						else
//						{
//							Ext.Msg.alert( _text('MN00023'),'검색어을 입력하세요.');
//						}
//					}
//					else
//					{
//						Ext.Msg.alert( _text('MN00023'),'검색명을 선택하세요.');
//					}
//				}
//			}
//		]
//	},
//	contextmenu: new Ext.menu.Menu({
//
//		items:[{
//			<?php
//			if ( $_SESSION['user'] &&
//				!in_array(ADMIN_GROUP, $_SESSION['user']['groups'])
//					&& !in_array(CHANNEL_GROUP, $_SESSION['user']['groups'])
//					&& $_SESSION['user']['is_admin'] != 'Y'
//
//			) {
//				echo "hidden: true,";
//			}
//			?>
//			id: 'regist_id',
//			text: '승인하기',
//			icon: '/led-icons/application_get.png'
//		},{
//			<?php
//			if ( $_SESSION['user'] &&
//				!in_array(ADMIN_GROUP, $_SESSION['user']['groups'])
//					&& !in_array(CHANNEL_GROUP, $_SESSION['user']['groups'])
//					&& $_SESSION['user']['is_admin'] != 'Y'
//
//			) {
//				echo "hidden: true,";
//			}
//			?>
//			id: 'approval',
//			text: '일괄 승인',
//			icon: '/led-icons/page_2_copy.png'
//		},{
//			id: 'update',
//			text: '종합정보 일괄 업데이트',
//			icon: '/led-icons/page_2_copy.png'
//		},{
//			<?php
//			if ( $_SESSION['user'] &&
//				!in_array(ADMIN_GROUP, $_SESSION['user']['groups'])
//					&& !in_array(CHANNEL_GROUP, $_SESSION['user']['groups'])
//					&& $_SESSION['user']['is_admin'] != 'Y'
//
//			) {
//				echo "hidden: true,";
//			}
//			?>
//			id: 'delete',
//			icon: '/led-icons/delete.png',
//			text: '삭제'
//		}],
//		listeners:{
//
//			itemclick:function(item){
//
//				if(item.id =='regist_id')
//				{
//					Ext.Msg.show({
//
//						title: '등록 확인',
//						msg: '등록을 완료하시겠습니까?',
//						minWidth: 100,
//						modal: true,
//						icon: Ext.MessageBox.QUESTION,
//						buttons: Ext.Msg.OKCANCEL,
//						fn: function(btnId){
//							//if(btnId=='cancel') return;
//							if(btnId=='ok'){
//								var sel = waitlist.getSelectionModel();
//								//var id = sel.getSelected().get('id');
//								var get_sel =sel.getSelections();
//								for(var i=0;i<get_sel.length;i++)
//								{
//									var id = get_sel[i].get('id');
//
//									//2011.01.06 김성민 :: DMC파일일경우 status값만 바꾸기위해 구분값 추가.
//									var user_id = get_sel[i].get('user_id');
//
//									Ext.Ajax.request({
//										url: '/store/wait_regist_update.php',
//										params:{
//											id: id,
//											channel: user_id
//										},
//
//										callback: function(options,success,response){
//											if(success){
//												Ext.Msg.alert('등록','등록완료 성공');
//												registWait_store.reload();
//											}
//											else{
//												Ext.Msg.alert('등록','등록완료 실패',response.statusText);
//											}
//										}
//									});
//								}
//							}
//
//							if(btnId=='cancel'){
//								Ext.Msg.alert('등록','등록이 취소되었습니다.');
//							}
//
//						}
//					});
//				}
//				else if(item.id =='delete')
//				{
//					var sel = waitlist.getSelectionModel();
//					var rs=[];
//					var records = sel.getSelections();
//					Ext.each(records, function(r, i, a){
//						rs.push({
//							content_id: r.get('id')
//						});
//					});
//					Ext.Msg.show({
//						icon: Ext.Msg.QUESTION,
//						title: '확인',
//						msg: '삭제하시겠습니까?',
//						buttons: Ext.Msg.OKCANCEL,
//						fn: function(btnId, text, opts){
//							if(btnId == 'cancel') return;
//
//							var w = Ext.Msg.wait('삭제 요청중...');
//							var action = 'delete';
//							Ext.Ajax.request({
//								url: '/store/delete_contents.php',
//								params: {
//									action: action,
//									content_id: Ext.encode(rs)
//								},
//								callback: function(opts, success, response){
//									w.hide();
//									if(success){
//										try{
//											var r = Ext.decode(response.responseText);
//											if(!r.success){
//												Ext.Msg.alert( _text('MN00023'), '삭제 권한이 없습니다.');
//												return;
//											}
//											waitlist.store.reload();
//										}
//										catch (e)
//										{
//											Ext.Msg.alert(e['name'], e['message']);
//										}
//									}else{
//										Ext.Msg.alert('오류', response.statusText);
//									}
//								}
//							})
//						}
//					});
//				}
//				else if(item.id =='approval')
//				{
//					var sel = waitlist.getSelectionModel();
//					var rs = [],
//						_rs = sel.getSelections();
//					Ext.each(_rs, function(r, i, a){
//						rs.push({
//							content_id: r.get('id')
//						});
//					});
//					requestBatchAccept(rs);
//				}
//				else if(item.id =='update')
//				{
//					var sel = waitlist.getSelectionModel();
//					var rs=[];
//					var records = sel.getSelections();
//					Ext.each(records, function(r, i, a){
//						rs.push({
//							content_id: r.get('id')
//						});
//					});
//					Ext.Msg.show({
//						title: '확인',
//						msg: '업데이트 하시겠습니까?',
//						minWidth: 100,
//						modal: true,
//						icon: Ext.MessageBox.QUESTION,
//						buttons: Ext.Msg.OKCANCEL,
//						fn: function(btnId){
//							if(btnId=='cancel')
//							{
//								return;
//							}
//							else
//							{
//								Ext.Ajax.request({
//									url: '/store/information_all_update.php',
//									params:{
//										content_id: Ext.encode(rs)
//									},
//									callback: function(options,success,response){
//										if(success)
//										{
//											Ext.Msg.show({
//												title: '확인',
//												msg: '업데이트 완료',
//												icon: Ext.Msg.QUESTION,
//												buttons: Ext.Msg.OKCANCEL,
//												fn: function(btnId){
//													if (btnId == 'ok')
//													{
//														//Ext.getCmp('winDetail').close();
//													}
//												}
//											});
//										}
//										else
//										{
//											Ext.Msg.alert('확인','업데이트 실패',response.statusText);
//										}
//									}
//								});
//							}
//						}
//					});
//				}
//			}
//		}
//	}),
//
//	listeners:{
//		afterrender: function(self){
//			registWait_store.load({params:{start: 0, limit: registwait_size}});
//		},
//		rowcontextmenu: function(self,rowIndex,e){
//
//			var cell = self.getSelectionModel();
//
//			if (!cell.isSelected(rowIndex))
//			{
//				cell.selectRow(rowIndex);
//			}
//
//			e.stopEvent();
//			self.contextmenu.showAt(e.getXY());
//		},
//		rowdblclick: function(self, idx, e){
//			var record = self.getSelectionModel().getSelected();
//			var id= record.get('id');
//			var content_type_id = record.get('content_type_id');
//			var meta_table_id = record.get('meta_table_id');
//			var type = 'wait';
//			Ext.Ajax.request({
//				url: '/javascript/ext.ux/Ariel.DetailWindow.php',
//				params: {
//					content_id: id,
//					type: type
//				},
//				callback: function(self, success, response){
//					if (success)
//					{
//						try
//						{
//							Ext.decode(response.responseText);
//						}
//						catch (e)
//						{
//							Ext.Msg.alert(e['name'], e['message'] );
//						}
//					}
//					else
//					{
//						Ext.Msg.alert('서버 오류', response.statusText+'('+response.status+')');
//					}
//				}
//			});
//
//		}
//	},
//	viewConfig: {
//		forceFit: true
//	},
//	bbar : new Ext.PagingToolbar({
//		store: registWait_store,
//		pageSize: registwait_size,
//		items: [{
//			xtype: 'tbseparator',
//			width: 40
//		},{
//			xtype: 'displayfield',
//			value: '범례: 콘텐츠의 메타데이터 수정시 연노랑색으로 표시 됩니다.'
//		}]
//
//	})
//});
