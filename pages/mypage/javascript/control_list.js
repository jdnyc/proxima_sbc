var myPageSize_control = 5;

var control_store = new Ext.data.JsonStore({
//	autoLoad: true,
	url: '/pages/mypage/php/control_store.php',
	root: 'data',
	totalProperty: 'total',
	baseParams: {
		start: 0,
		limit: myPageSize_control
	},
	fields: [
		{name: 'content_id'},
		{name: 'title'},
		{name: 'ud_content_id'},
		{name: 'ud_content_title'},
		{name: 'created_date', type: 'date', dateFormat: 'YmdHis'},
		{name: 'status'},
		{name: 'reg_user_id'}
	],
	listeners: {
		load: function(self){
		}
	}
});
var control_grid = new Ext.grid.GridPanel({
	id:'control_grid',
	title: '통제 리스트',	
	store: control_store,
	loadMask: true,
	defaults : {
		sortable: true,
		align: 'left'
	},
	sm: new Ext.grid.RowSelectionModel({
		singleSelect: true
	}),
	columns: [
		new Ext.grid.RowNumberer(),	
		{ header: '제목', dataIndex: 'title' },
		{ header: '콘텐츠 구분', dataIndex: 'ud_content_title' },
		{ header: '등록일자', dataIndex: 'created_date',  renderer: Ext.util.Format.dateRenderer('Y-m-d') }	
	],
	viewConfig: {
		forceFit: true,
		emptyText: _text('MSG00148')
		//!!emptyText: '검색 결과가 없습니다.'
	},
	listeners: {
		rowdblclick: function(self, rowIndex, e){

			var sm = self.getSelectionModel().getSelected();

			//console.log(sm.get('status'));

			if (sm.get('status') == -2)
			{
				Ext.Msg.show({
					title: '경고'
					,msg: _text('MSG00217')
					,icon: Ext.Msg.WARNING
					,buttons: Ext.Msg.OK
				});

				return;
			}								

			var content_id = sm.get('content_id');

			//>>self.load = new Ext.LoadMask(Ext.getBody(), {msg: '상세 정보를 불러오는 중입니다...'});
			self.load = new Ext.LoadMask(Ext.getBody(), {msg: _text('MSG00143')});
			self.load.show();
			var that = self;

			if ( !Ext.Ajax.isLoading(self.isOpen) )
			{
				self.isOpen = Ext.Ajax.request({
					url: '/javascript/ext.ux/Ariel.DetailWindow.php',
					params: {
						content_id: content_id
					},
					callback: function(self, success, response){
						if (success)
						{
							//that.load.hide();
							try
							{
								
								if (sm.get('status') == -1)
								{								
									Ext.Msg.show({
										title: '경고'
										,msg: _text('MSG00216')
										,icon: Ext.Msg.WARNING
										,buttons: Ext.Msg.OK
										,fn: function(btnId, txt, opt){
											var r = Ext.decode(response.responseText);
										}
									});
								}
								else
								{
									var r = Ext.decode(response.responseText);
								}

								if ( r !== undefined && !r.success)
								{
									Ext.Msg.show({
										title: '경고'
										,msg: r.msg
										,icon: Ext.Msg.WARNING
										,buttons: Ext.Msg.OK
									});
								}
							}
							catch (e)
							{
								//alert(response.responseText)
								//Ext.Msg.alert(e['name'], e['message'] );
							}
						}
						else
						{
							//>>Ext.Msg.alert('서버 오류', response.statusText+'('+response.status+')');
							Ext.Msg.alert(_text('MN00022'), response.statusText+'('+response.status+')');
						}
					}
				});
			} else {
                that.load.hide();
            }
		}
	
	},
	bbar: new Ext.PagingToolbar({
		store: control_store,
		pageSize: myPageSize_control
	})
});
