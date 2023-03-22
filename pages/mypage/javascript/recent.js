

var myPageSize_recent = 5;

var recent_store = new Ext.data.JsonStore({//최근작업 스토어
//	autoLoad: true,
	url: '/pages/mypage/php/recent_store.php',
	root: 'data',
	totalProperty: 'total',
	baseParams: {
		start: 0,
		limit: myPageSize_recent
	},
	fields: [
		{name: 'title'},
		{name: 'ud_content_id'},
			{name: 'ud_content_title'},
		{name: 'status' },
		{name: 'is_deleted' },
		{name:'created_date',type:'date',dateFormat: 'YmdHis'},
		{name: 'content_id'}
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

var recent_contents_grid = new Ext.grid.GridPanel({//최근 작업 그리드
	//!!title:'최근 등록작업',
	title:_text('MN00267'),
	loadMask: true,
	id: 'recent_grid',
	store: recent_store,
	defaults : {
		sortable: true,
		align: 'left'
	},
	columns: [
		new Ext.grid.RowNumberer(),
		/*!!	
		{ header: '콘텐츠 제목', dataIndex: 'title' ,width: 600},
		{ header:'콘텐츠 유형',	 dataIndex: 'meta_table_id', width: 200 , renderer: mappingMetaTable },
		{ header: '상 태', dataIndex: 'status', width: 200 , renderer: mappingStatus },
		{ header: '등록일자', dataIndex: 'created_time', width: 200 , renderer: Ext.util.Format.dateRenderer('Y-m-d') }
		*/
		{ header: _text('MN00278'), dataIndex: 'title' ,width: 600},
		{ header: '콘텐츠 구분',	 dataIndex: 'ud_content_title', width: 200 ,
			renderer: renderUdContent },
		{ header:  _text('MN00138'), dataIndex: 'status', width: 200 , renderer: mappingStatus },
		{ header: _text('MN00102'), dataIndex: 'created_date', width: 200 , renderer: Ext.util.Format.dateRenderer('Y-m-d') }
		
	],
	viewConfig: {
		forceFit: true,
		//!!emptyText: '검색 결과가 없습니다.'
		emptyText: _text('MSG00148')
	},
	bbar: new Ext.PagingToolbar({
		store: recent_store,
		pageSize: myPageSize_recent
	}),
	listeners: {
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
						Ext.Msg.alert(_text('MN00022'), response.statusText+'('+response.status+')');
					}
				}
			});
		}
	}

});


function mappingMetaTable(value)
{
	return value;	
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
		return '대기';
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