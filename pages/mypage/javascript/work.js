var myPageSize_work =5;
var work_store = new Ext.data.JsonStore({//작업 내역 스토어
	url: '/pages/mypage/php/work_store.php',
	root: 'data',
	totalProperty: 'total',
	fields: [
		{name: 'title'},
		{name: 'original'},
		{name: 'transcoding'},
		{name: 'catalog'},
		{name: 'nearline'},
		{name: 'archive'},
		{name: 'is_deleted'},
		{name: 'ud_content_title'},
		{name: 'created_date',type:'date',dateFormat: 'YmdHis'},
		{name: 'status'},
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

var work_contents_grid = new Ext.grid.GridPanel({//작업내역 그리드
	//!!title:'전체 작업내역',
	title:_text('MN00247'),
	id:'work_grid',
	loadMask:true,
	store: work_store,
	defaults : {
		sortable: true,
		align: 'left'
	},
	viewConfig: {
		forceFit: true,
		getRowClass : function (r, rowIndex, rp, ds) {

			if (r.get('status') == '2')
			{
				return 'complete';
			}

		},
		emptyText: _text('MSG00148')
		//!!emptyText: '검색 결과가 없습니다.'
	},
	listeners: {
/*		viewready: function(self){
			self.store.load({
				params: {
					start: 0,
					limit: myPageSize_work,
					start_date: Ext.getCmp('start_date').getValue().format('Ymd000000'),
					end_date: Ext.getCmp('end_date').getValue().format('Ymd240000'),
				}

			})
		}
		*/
		/*,
		rowdblclick: function(self, idx, e){
			var record = self.getSelectionModel().getSelected();
			var id= record.get('content_id');

			Ext.Ajax.request({
				url: '/javascript/ext.ux/Ariel.DetailWindow.php',
				params: {
					content_id: id
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
		}*/
	},
	columns: [
		new Ext.grid.RowNumberer(),
		/*!!
		{ header: '콘텐츠 제목', dataIndex: 'title' ,width:400 },
		{ header: '콘텐츠 유형', dataIndex: 'meta_table_id',width:80 , renderer: mappingMetaTable },
		{ header: '전송', dataIndex: 'original', width: 60 , renderer: mappingTaskStatus },
		{ header: '트랜스코딩', dataIndex: 'transcoding', width: 60 , renderer: mappingTaskStatus },
		{ header: '카달로깅', dataIndex: 'catalog', width: 60, renderer: mappingTaskStatus },
		{ header: '니어라인', dataIndex: 'nearline', width: 60 , renderer: mappingTaskStatus },
		{ header: '아카이브', dataIndex: 'archive', width: 60 , renderer: mappingTaskStatus },
		{ header: '상 태', dataIndex: 'status',width: 60 , renderer: mappingStatus },
		{ header: '등록일자', dataIndex: 'created_time',width: 100, renderer: Ext.util.Format.dateRenderer('Y-m-d') }
		*/
		{ header: _text('MN00278'), dataIndex: 'title' ,width:400 },
		{ header: '콘텐츠 구분', dataIndex: 'ud_content_title',width:80  , renderer: renderUdContent },
		{ header: _text('MN00243'), dataIndex: 'original', width: 60 , renderer: mappingTaskStatus },
		{ header: _text('MN00298'), dataIndex: 'transcoding', width: 60 , renderer: mappingTaskStatus },
		{ header: _text('MN00270'), dataIndex: 'catalog', width: 60, renderer: mappingTaskStatus },
		//{ header: _text('MN00057'), dataIndex: 'nearline', width: 60 , renderer: mappingTaskStatus },
		{ header: _text('MN00056'), dataIndex: 'archive', width: 60 , renderer: mappingTaskStatus },
		{ header: _text('MN00138'), dataIndex: 'status',width: 60 , renderer: mappingStatus },
		{ header: _text('MN00102'), dataIndex: 'created_date',width: 100, renderer: Ext.util.Format.dateRenderer('Y-m-d') }
	],

	tbar: [_text('MN00150'),{//!!기간
		xtype: 'datefield',
		id: 'start_date',
		editable: false,
		format: 'Y-m-d',
		listeners: {
			render: function(self){
				self.setMaxValue(new Date().format('Y-m-d'));
				self.setValue(new Date().add(Date.DAY, -6).format('Y-m-d'));
			}
		}
	},
	_text('MN00183')//!!'부터'
,{
	xtype: 'datefield',
	id: 'end_date',
	editable: false,
	format: 'Y-m-d',
	listeners: {
		render: function(self){
				self.setMaxValue(new Date().format('Y-m-d'));
				self.setValue(new Date().format('Y-m-d'));
			}
		}
	},'-',{
		icon: '/led-icons/find.png',
		//text: '조회',
		text: _text('MN00059'),
		handler: function(btn, e){
			Ext.getCmp('work_grid').store.reload();
		}
	}],
	bbar: new Ext.PagingToolbar({
		store: work_store,
		pageSize: myPageSize_work
	})
});


function mappingMetaTable(value)
{
	return value;	
}


function renderUdContent(value){
	if(Ext.isEmpty(value))
	{
		return '미정의';
	}
	else
	{
		return value;
	}
}

function mappingStatus(value)
{
	if( value == '2' )
	{
		//!! return '승인';
		return _text('MN00206');
	}
	else if( value == '0' )
	{
		//!! return '등록대기';
		return _text('MN00039');
	}
	else if( value < 0 && value > -5 )
	{
		//!!lsy return '전송중';
		return _text('MN00206');
	}
	else if( value == '-5' )
	{
		//!! return '반려';
		return _text('MN00174');
	}
	else if( value == '-6' )
	{
		//!!lsy return '재승인요청';
		return _text('MN00039');
	}
}

function mappingTaskStatus(value)
{
	if( value == 'complete' )
	{
		//!! return '완료';
		return _text('MSG00083');
	}
	else if( value == 'processing' )
	{
		//!! return '진행중';
		return _text('MN00262');
	}
	else if( value == 'error' )
	{
		//!! return '오류';
		return _text('MN00022');
	}
	else if(  value == 'queue'  )
	{
		//!!return '할당중';
		return _text('MN00039');
	}

}