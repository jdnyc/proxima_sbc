

var myPageSize_recent = 5;

var recent_store = new Ext.data.JsonStore({//최근작업 스토어
	url: '/pages/mypage/php/recent_store.php',
	root: 'data',	
	totalProperty: 'total',
	fields: [
		{name: 'title'},
		{name: 'meta_table_id'},
		{name: 'original'},
		{name: 'transcoding'},
		{name: 'catalog'},
		{name: 'status'},
		{name:'created_date',type:'date',dateFormat: 'YmdHis'}
	],
	listeners: {
		load: function(self){
		},
		beforeload: function(self, opts){
			self.baseParams = {
				limit: myPageSize_recent,
				start: 0
			}
		}
	}
});

var recent_contents_grid = new Ext.grid.GridPanel({//최근 작업 그리드
	title:'최근등록작업',
	id: 'recent_grid',
	store: recent_store,
	frame: true,
	loadMask: true,
	boxMinHeight: 200,
	columns: [
		new Ext.grid.RowNumberer(),
		{header: '콘텐츠 제목', dataIndex: 'title',width:80,sortable:'true'},
		{ header: '콘텐츠 유형', dataIndex: 'meta_table_id',width:60 , renderer: mappingMetaTable },
		{ header: '전송', dataIndex: 'original', width: 40 , renderer: mappingTaskStatus },
		{ header: '트랜스코딩', dataIndex: 'transcoding', width: 40 , renderer: mappingTaskStatus },
		{ header: '카달로깅', dataIndex: 'catalog', width: 40, renderer: mappingTaskStatus },
		{header: '상 태', dataIndex: 'status',width: 25,sortable:'true',renderer: mappingStatus },
		{header: '등록일자', dataIndex: 'created_date',width: 60, renderer: Ext.util.Format.dateRenderer('Y-m-d'),sortable:'true'}
	],
	viewConfig: {
		forceFit: true,
		emptyText: '검색 결과가 없습니다.'
	},
	bbar: new Ext.PagingToolbar({
		store: recent_store,
		pageSize: myPageSize_recent
	})
});
//
//var recent_contents_grid = new Ext.grid.GridPanel({//최근 작업 그리드
//	title:'최근 등록작업',
//	loadMask: true,
//	id: 'recent_grid',
//	store: recent_store,
//	defaults : {
//		sortable: true,
//		align: 'left'
//	},
//	columns: [
//		new Ext.grid.RowNumberer(),
//		{ header: '콘텐츠 제목', dataIndex: 'title' ,width: 600},
//		{ header:'콘텐츠 유형',	 dataIndex: 'meta_table_id', width: 200 , renderer: mappingMetaTable },
//		{ header: '상 태', dataIndex: 'status', width: 200 , renderer: mappingStatus },
//		{ header: '등록일자', dataIndex: 'created_time', width: 200 , renderer: Ext.util.Format.dateRenderer('Y-m-d') }
//	],
//	viewConfig: {
//		forceFit: true,
//		emptyText: '검색 결과가 없습니다.'
//	},
//	bbar: new Ext.PagingToolbar({
//		store: recent_store,
//		pageSize: myPageSize_recent
//	}),
//	listeners: {
//		rowdblclick: function(self, idx, e){
//
//			var record = self.getSelectionModel().getSelected();
//			var id= record.get('content_id');
//			Ext.ariel.detailLoadMask.show();
//			Ext.Ajax.request({
//				url: '/ext.ux/Ariel.DetailWindow.php',
//				params: {
//					content_id: id
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
//		}
//	}
//
//});

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

function mappingStatus(value)
{
	if( value == '2' )
	{
		return '등록';
	}
	else if( value == '1' )
	{
		return '등록';
	}
	else if( value == '-2' )
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