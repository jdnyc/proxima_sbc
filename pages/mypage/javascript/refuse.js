var mapping_action = function(value){
	if( value == 'refuse_meta' )
	{
		return 'refuse_meta';
	}
	else if( value == 'refuse_encoding' )
	{
		return 'refuse_encoding';
	}
};

var mapping_status = function(value){
	if( value == '0' )
	{
		//!! return '등록대기';
		return _text('MN00039');
	}
	else if( value == '2' )
	{
		//!! return '승인';
		return _text('MN00206');
	}
	else if( value == '-5' )
	{
		//!! return '반려';
		return _text('MN00174');
	}
};

var myPageSize_refuse = 5;

var refuse_store = new Ext.data.JsonStore({
//	autoLoad: true,
	url: '/pages/mypage/php/refuse_store.php',
	root: 'data',
	totalProperty: 'total',
	baseParams: {
		start:0,
		limit: myPageSize_refuse
	},
	fields: [
		{name: 'title'},
		{name: 'status'},
		{name: 'user_id'},
		{name: 'target_user_id'},
		{name: 'action'},
		{name: 'description', type: 'string'},
		{name: 'created_time', type: 'date', dateFormat: 'YmdHis'},
		{name: 'content_id'}
	]
});

var refuse_grid = new Ext.grid.GridPanel({//등록한 영상 그리드
	id: 'refuse_grid',
	//!!title:'반려 목록',
	title:_text('MN00247'),	
	loadMask: true,
	store: refuse_store,
	columns: [
		new Ext.grid.RowNumberer(),
		/*
		{header: '콘텐츠 제목', dataIndex: 'title', align:'center',sortable:'true'},
		{header: '상 태', dataIndex: 'status', align:'center', renderer: mapping_status},
		{header: '반려 목록', dataIndex: 'action', align:'center', renderer: mapping_action},
		{header: '반려 사유', dataIndex: 'description', align:'center'},
		{header: '요청자', dataIndex: 'user_id', align:'center'},
		{header: '대상자', dataIndex: 'target_user_id', align:'center'},
		{header: '반려 날짜', dataIndex: 'created_time', align:'center', renderer: Ext.util.Format.dateRenderer('Y-m-d'),sortable:'true'}
		*/
		{header: _text('MN00278'), dataIndex: 'title', align:'center',sortable:'true'},
		{header: _text('MN00138'), dataIndex: 'status', align:'center', renderer: mapping_status},
		{header: '반려 목록', dataIndex: 'action', align:'center', renderer: mapping_action},
		{header: '반려 사유', dataIndex: 'description', align:'center'},
		{header: '요청자', dataIndex: 'user_id', align:'center'},
		{header: '대상자', dataIndex: 'target_user_id', align:'center'},
		{header: '반려 날짜', dataIndex: 'created_date', align:'center', renderer: Ext.util.Format.dateRenderer('Y-m-d'),sortable:'true'}
		

	],
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
		rowdblclick: function(self, idx, e){
			var record = self.getSelectionModel().getSelected();

			var id= record.get('content_id');
			var type = 'mypage';

			Ext.Ajax.request({
				url: '/javascript/ext.ux/Ariel.DetailWindow.php',
				params: {
					content_id: id,
					type: type
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

		}
	},
	bbar: new Ext.PagingToolbar({
		store: refuse_store,
		pageSize: myPageSize_refuse
	})
});
