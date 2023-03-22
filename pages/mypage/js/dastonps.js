	var myPageSize_dastonps = 5;
	var date = new Date();



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


	var dastonps_store = new Ext.data.JsonStore({//dastonps 스토어
		url: '/pages/mypage/php/dastonps_store.php',
		root: 'data',
		totalProperty: 'total',
		fields: [
			{name: 'title'},
			{name: 'archive'},
			{name: 'restore'},
			{name: 'original'},
			{name: 'transcoding'},
			{name: 'catalog'},
			{name: 'is_deleted'},
			{name: 'meta_table_id'},
			{name: 'created_date',type:'date',dateFormat: 'YmdHis'},
			{name: 'status'},
			{name: 'meta_table_name'},
			{name: 'content_id'}
		],
		listeners: {
			beforeload: function(self, opts){
				opts.params = opts.params || {};

				Ext.apply(opts.params, {
					start_date: Ext.getCmp('dastonps_start_date').getValue().format('Ymd000000'),
					end_date: Ext.getCmp('dastonps_end_date').getValue().format('Ymd240000')
				});
			}
		}
	});


	var dastonps_grid = new Ext.grid.GridPanel({//dastonps 그리드
		title:'DAS에서 NPS로 전송한 콘텐츠',
		id: 'dastonps',
		frame: true,
		border: false,
		loadMask: true,
		store: dastonps_store,
		columns: [
			new Ext.grid.RowNumberer(),
			{ header: '콘텐츠 제목', dataIndex: 'title' ,width:400 },
			{ header: '콘텐츠 유형', dataIndex: 'meta_table_name',width:80 },
			//{ header: '리스토어', dataIndex: 'restore', width: 60 , renderer: mappingTaskStatus },
			{ header: '전송', dataIndex: 'original', width: 60 , renderer: mappingTaskStatus },
			{ header: '트랜스코딩', dataIndex: 'transcoding', width: 60 , renderer: mappingTaskStatus },
			{ header: '카달로깅', dataIndex: 'catalog', width: 60, renderer: mappingTaskStatus },
			{ header: '상 태', dataIndex: 'status',width: 60, renderer: mappingStatus },
			{ header: '등록일자', dataIndex: 'created_date',width: 100, renderer: Ext.util.Format.dateRenderer('Y-m-d')}
		],

		tbar: ['기간 : ',{
			xtype: 'datefield',
			id: 'dastonps_start_date',
			editable: false,
			format: 'Y-m-d',
			listeners: {
				render: function(self){
					self.setMaxValue(date.format('Y-m-d'));
					self.setValue(date.add(Date.MONTH, -1).format('Y-m-d'));
				}
			}
		},
		'부터',
		{
			xtype: 'datefield',
			id: 'dastonps_end_date',
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
				Ext.getCmp('dastonps').store.reload();
			}
		}],
		view:  new Ext.ux.grid.BufferView({
			rowHeight: 20,
			scrollDelay: false,
			forceFit: true,
			getRowClass : function (r, rowIndex, rp, ds) {

				if ( r.get('status') > 0 )
				{
					return 'complete';//승인
				}
			},
			emptyText: '검색 결과가 없습니다.'
		}),
		listeners: {
			rowdblclick: function(self, idx, e){
				var record = self.getSelectionModel().getSelected();

				Ext.Ajax.request({
					url: '/javascript/ext.ux/Ariel.DetailWindow.php',
					params: {
						content_id: record.get('content_id'),
						type: 'mypage'
					},
					callback: function(self, success, response){
						if (success)
						{
							try
							{
								Ext.decode(response.responseText);
							}
							catch (e)
							{
								Ext.Msg.alert(e['name'], e['message'] );
							}
						}
						else
						{
							Ext.Msg.alert('서버 오류', response.statusText+'('+response.status+')');
						}
					}
				});

			},
			viewready: function(self){
//				self.store.load({
//					params: {
//						start: 0,
//						limit: myPageSize_dastonps,
//						start_date: Ext.getCmp('dastonps_start_date').getValue().format('Ymd000000'),
//						end_date: Ext.getCmp('dastonps_end_date').getValue().format('Ymd240000')
//					}
//				});
			}
			/*,
			rowcontextmenu: function(self,rowIndex,e){
				var cell = self.getSelectionModel();
				if (!cell.isSelected(rowIndex))
				{
					cell.selectRow(rowIndex);
				}
				e.stopEvent();
				self.contextmenu.showAt(e.getXY());
			}*/
		},
		bbar: new Ext.PagingToolbar({
			store: dastonps_store,
			pageSize: myPageSize_dastonps
		})
		/*,contextmenu: new Ext.menu.Menu({
			items:[{
				icon: '/led-icons/delete.png',
				text: '삭제'
			}],
			listeners:{
				itemclick:function(item){
					if(item.text =='삭제')
					{
						doDelete( Ext.getCmp('dastonps') );
					}
				}
			}
		})*/
	});