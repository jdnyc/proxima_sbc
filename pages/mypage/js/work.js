var myPageSize_work =5;
var work_store = new Ext.data.JsonStore({//작업 내역 스토어
	url: '/mypage/php/work_store.php',
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
		{name: 'meta_table_id'},
		{name: 'created_time',type:'date',dateFormat: 'YmdHis'},
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
	title:'전체 작업내역',
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
			if (r.get('status') == '-5')
			{
				return 'refuse';
			}

		},
		emptyText: '검색 결과가 없습니다.'
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
				url: '/ext.ux/Ariel.DetailWindow.php',
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
		{ header: '콘텐츠 제목', dataIndex: 'title' ,width:400 },
		{ header: '콘텐츠 유형', dataIndex: 'meta_table_id',width:80 , renderer: mappingMetaTable },
		{ header: '전송', dataIndex: 'original', width: 60 , renderer: mappingTaskStatus },
		{ header: '트랜스코딩', dataIndex: 'transcoding', width: 60 , renderer: mappingTaskStatus },
		{ header: '카달로깅', dataIndex: 'catalog', width: 60, renderer: mappingTaskStatus },
		{ header: '니어라인', dataIndex: 'nearline', width: 60 , renderer: mappingTaskStatus },
		{ header: '아카이브', dataIndex: 'archive', width: 60 , renderer: mappingTaskStatus },
		{ header: '상 태', dataIndex: 'status',width: 60 , renderer: mappingStatus },
		{ header: '등록일자', dataIndex: 'created_time',width: 100, renderer: Ext.util.Format.dateRenderer('Y-m-d') }

	],

	tbar: ['기간 : ',{
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
	'부터'
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
		text: '조회',
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
	if( value == '81722' )
	{
		return 'TV방송프로그램';
	}
	else if( value == '81767' )
	{
		return '소재영상';
	}
	else if( value == '81768' )
	{
		return '참조영상';
	}
	else if( value == '4023846' )
	{
		return 'R.방송프로그램';
	}
	else if( value == '81769' )
	{
		return '음반';
	}
}



function mappingStatus(value)
{
	if( value == '2' )
	{
		return '승인';
	}
	else if( value == '0' )
	{
		return '등록대기';
	}
	else if( value < 0 && value > -5 )
	{
		return '전송중';
	}
	else if( value == '-5' )
	{
		return '반려';
	}
	else if( value == '-6' )
	{
		return '재승인요청';
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
		return '할당중';
	}

}