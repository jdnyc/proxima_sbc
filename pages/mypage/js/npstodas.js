var myPageSize_npstodas = 5;

	var date = new Date();
///////////////////// 함수///////////////////////////////
var msg = function(title, msg){
	Ext.Msg.show({
		title: title,
		msg: msg,
		minWidth: 100,
		modal: true,
		icon: Ext.Msg.INFO,
		buttons: Ext.Msg.OK
	});
};
var bbartext = {
	xtype: 'displayfield',
	value: '범례: 우클릭시 삭제버튼(NPS 콘텐츠만 삭제)  /  DAS로 전송된 콘텐츠가 아카이브 완료시 녹색으로 표시됩니다.'
};
	function doDelete(owner)
	{
		var sm = owner.getSelectionModel();
		var rs = [];
		var _rs = sm.getSelections();
		var ist_archive = true;
		Ext.each(_rs, function(r, i, a){

			if( r.get('das_status') != 2 )
			{
				ist_archive = true;
			}
		});

		if( ist_archive )
		{
			Ext.Msg.alert( _text('MN00023'), '승인된 콘텐츠만 삭제 가능합니다');
			return;
		}

		var delete_win = new Ext.Window({
			layout:'fit',
			title:'삭제 사유',
			modal: true,
			width:500,
			height:140,
			items:[{
				xtype:'form',
				border: false,
				frame: true,
				padding: 5,
				labelWidth: 50,
				defaults: {
					anchor: '100%'
				},
				items: [{
					xtype: 'textarea',
					height: 50
					,fieldLabel: '내용'
				}],
				buttons:[{
					text:'삭제',
					handler: function(btn,e){

						var sm = owner.getSelectionModel();
						var tm = this.ownerCt.ownerCt.get(0).getValue();

						if( Ext.isEmpty(tm) )
						{
							Ext.Msg.alert( _text('MN00023'), '삭제사유를 적어주세요');
							return;
						}

						var rs = [];
						var _rs = sm.getSelections();
						Ext.each(_rs, function(r, i, a){
							rs.push({
								content_id: r.get('content_id'),
								delete_his: tm
							});
						});

						Ext.Msg.show({
							icon: Ext.Msg.QUESTION,
							title: '확인',
							msg: '삭제사유를 저장하고 콘텐츠를 삭제하시겠습니까?',
							buttons: Ext.Msg.OKCANCEL,
							fn: function(btnId, text, opts){
								if(btnId == 'cancel') return;

								var w = Ext.Msg.wait('삭제 요청중...');
								var action = 'mypage_delete';

								Ext.Ajax.request({
									url: '/php/delete_contents.php',
									params: {
										action: action,
										content_id: Ext.encode(rs)
									},
									callback: function(opts, success, response){
										w.hide();
										if(success){
											try{
												var r = Ext.decode(response.responseText);
												if(!r.success){
													Ext.Msg.alert( _text('MN00023'), '삭제 권한이 없습니다.');
													return;
												}
												owner.store.reload();
											}
											catch (e)
											{
												Ext.Msg.alert(e['name'], e['message']);
											}
										}
										else
										{
											Ext.Msg.alert('오류', response.statusText);
										}
									}
								});

								delete_win.destroy();
							}
						});
					}
				},{
					text:'닫기',
					handler: function(btn,e){
						delete_win.destroy();
					}
				}]

			}]
		});
		delete_win.show();

	}

var npstodas_store = new Ext.data.JsonStore({//작업 내역 스토어
	url: '/pages/mypage/php/npstodas_store.php',
	root: 'data',
	totalProperty: 'total',
	fields: [
		{name: 'title'},
		{name: 'original'},
		{name: 'archive'},
		{name: 'transcoding'},
		{name: 'catalog'},
		{name: 'das_is_deleted'},
		{name: 'ud_content_id'},
		{name: 'das_created_time',type:'date',dateFormat: 'YmdHis'},
		{name: 'das_status'},
		{name: 'das_pronm'},
		{name: 'das_datanm'},
		{name: 'das_title'},
		{name: 'das_meta_table_id'},
		{name: 'das_content_id'},
		{name: 'content_id'}
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

var npstodas_grid = new Ext.grid.GridPanel({//작업내역 그리드
	title:'NPS에서 DAS로 전송한 콘텐츠',
	id:'npstodas',
	frame: true,
	loadMask: true,
	border: false,
	store: npstodas_store,
	sm: new Ext.grid.RowSelectionModel({
	}),
	listeners: {
//		viewready: function(self){
//			self.store.load({
//				params: {
//					start: 0,
//					limit: myPageSize_npstodas,
//					start_date: Ext.getCmp('start_date').getValue().format('Ymd000000'),
//					end_date: Ext.getCmp('end_date').getValue().format('Ymd240000')
//				}
//			})
//		},
		rowcontextmenu: function(self,rowIndex,e){
			var cell = self.getSelectionModel();
			if (!cell.isSelected(rowIndex))
			{
				cell.selectRow(rowIndex);
			}
			e.stopEvent();
			self.contextmenu.showAt(e.getXY());
		}
	},
	columns: [
		new Ext.grid.RowNumberer(),
		{ header: 'NPS 제목', dataIndex: 'title' ,width:200 },
		{ header: 'NPS콘텐츠 유형', dataIndex: 'ud_content_id', width:90 , renderer: mappingMetaTable },
		{ header: 'DAS 자료명', dataIndex: 'das_datanm', width:200  },
		{ header: 'DAS 프로그램명', dataIndex: 'das_pronm', width:150  },
		{ header: 'DAS콘텐츠 유형', dataIndex: 'das_meta_table_id', width:90 , renderer: mappingMetaTable },
		{ header: '아카이브', dataIndex: 'archive', width: 60 , renderer: mappingTaskStatus },
		{ header: '전송', dataIndex: 'original', width: 60 , renderer: mappingTaskStatus },
		{ header: '트랜스코딩', dataIndex: 'transcoding', width: 60 , renderer: mappingTaskStatus },
		{ header: '카달로깅', dataIndex: 'catalog', width: 60, renderer: mappingTaskStatus },
		{ header: '상 태', dataIndex: 'das_status',width: 60, renderer: mappingStatusDas },
	//	{ header: '삭제여부', dataIndex: 'das_is_deleted',width: 50, renderer: mappingDeleted },
		{ header: '등록일자', dataIndex: 'das_created_time',width: 60, renderer: Ext.util.Format.dateRenderer('Y-m-d')}
	],
	tbar: ['기간 : ',{
		xtype: 'datefield',
		id: 'start_date',
		editable: false,
		format: 'Y-m-d',
		listeners: {
			render: function(self){
				self.setMaxValue(date.format('Y-m-d'));
				self.setValue(date.add(Date.MONTH, -1).format('Y-m-d'));
			}
		}
	},'부터',
	{
		xtype: 'datefield',
		id: 'end_date',
		editable: false,
		format: 'Y-m-d',
		listeners: {
			render: function(self){
				self.setMaxValue(date.format('Y-m-d'));
				self.setValue(date.format('Y-m-d'));
			}
		}
	},'-',{
		icon: '/led-icons/find.png',
		text: '조회',
		handler: function(btn, e){
			Ext.getCmp('npstodas').store.reload();
		}
	}],
	view:  new Ext.ux.grid.BufferView({
		rowHeight: 20,
		scrollDelay: false,
		forceFit: true,
		getRowClass : function (r, rowIndex, rp, ds) {

		/*	if( r.get('das_is_deleted') == '1' )
			{
				return 'delete'; //삭제 상태
			}
		*/

			if ( r.get('archive') == 'complete' )
			{
				return 'complete';//승인
			}
		},
		emptyText: '검색 결과가 없습니다.'
	}),
	bbar: [
		new Ext.PagingToolbar({
			store: npstodas_store,
			pageSize: myPageSize_npstodas
		}),
		bbartext
	],
	contextmenu: new Ext.menu.Menu({
		items:[{
			icon: '/led-icons/delete.png',
			text: '삭제'
		}],
		listeners:{
			itemclick:function(item){
				if(item.text =='삭제')
				{
					doDelete( Ext.getCmp('npstodas') );
				}
			}
		}
	})

});




	function mappingMetaTable(value)
	{
		if( value == '4000282' )
		{
			return '인제스트';
		}
		else if( value == '4000283' )
		{
			return '편집본';
		}
		else if(value == '81767' )
		{
			return '소재영상';
		}
		else
		{
			return value;
		}
	}

	function mappingStatus(value)
	{
		if( value > 0 )
		{
			return '등록';
		}
		else if( value < 0 && value > -5 )
		{
			return '등록중';
		}
	}
	function mappingMediaStatus(value)
	{
		if(value == '1')
		{
			return '삭제';
		}
		else
		{
			return '존재';
		}
	}


	function mappingStatusDas(value)
	{
		if( value == 2 )
		{
			return '승인';
		}
		else if( value == -1 || value == -2 )
		{
			return '등록중';
		}
		else if(value == 0)
		{
			return '등록대기';
		}
		else if(value == -5)
		{
			return '반려';
		}
		else if(value == -6)
		{
			return '재승인요청';
		}
		else
		{
			return value;
		}

	}

	function mappingTaskStatus(value)
	{
		if( value == 'complete' )
		{
			return '완료';
		}
		else if( value == 'progressing' )
		{
			return '진행중';
		}
		else if( value == 'error' )
		{
			return '오류';
		}
		else if(  value == 'queue'  )
		{
			return '대기';
		}
		else if( value == 'start' )
		{
			return '시작';
		}
		else if(value == 'non' )
		{
			return '불필요';
		}
		else
		{
			return '';
		}
	}

	function mappingDeleted(value)
	{
		if(value == '1')
		{
			return '삭제';
		}
		else
		{
			return '';
		}
	}