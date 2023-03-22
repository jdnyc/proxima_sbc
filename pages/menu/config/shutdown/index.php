(function(){


	var PageSize = 25;//페이지 제한

	var store = new Ext.data.JsonStore({//공지사항 스토어
		url: '/pages/menu/config/shutdown/store.php',
		root: 'data',
		autoLoad: true,
		baseParams: {
		},
		totalProperty: 'total',
		fields: [
			{name: 'program'},
			{name: 'category_title'},
			{name: 'category_id'},
			{name: 'from_user_id'},
			{name: 'to_user_id'},
			{name: 'status'},
			{name: 'from_user_nm'},
			{name: 'created_date', type: 'date', dateFormat: 'YmdHis'}
		],
		listeners: {
			beforeload: function(){
			}
		}
	});

	var grid = new Ext.grid.GridPanel({//////공지사항 그리드///////
		store: store,
		sm: new Ext.grid.RowSelectionModel({
			singleSelect: true
		}),
		columns: [
			new Ext.grid.RowNumberer(),
			{header: '제작프로그램', dataIndex: 'program'},
			{header: '폴더', dataIndex: 'category_title'},
			{header: '요청자', dataIndex: 'from_user_nm' },
			{header: '상태', dataIndex: 'status', renderer: function(value){

				switch(value)
				{
					case 'queue':
						return '대기';
					break;
					case 'accept':
						return '승인';
					break;
					case 'refuse':
						return '반려';
					break;
				}
				return value;
			}},
			{header: '요청일자', dataIndex: 'created_date',  renderer: Ext.util.Format.dateRenderer('Y-m-d') }
		],
		viewConfig: {
			forceFit: true
		},
		listeners: {
			rowdblclick: function(self, idx, e){
			}
		},
		bbar : {
			xtype: 'paging',
			pageSize: PageSize,
			displayInfo: true,
			store: store
		},
		tbar: [{
			text: '승인',
			icon:'/led-icons/accept.png',
			scale: 'medium',
			handler: function(e){
				var grid = e.ownerCt.ownerCt;
				var sel = grid.getSelectionModel().getSelections();

				var tmp = new Array();
				Ext.each(sel,function(r){
					tmp.push(r.json);
				});

				if(grid.getSelectionModel().getCount() < 1) return;

				Ext.Ajax.request({
					url: '/store/nps_work/shutdown.php',
					params: {
						action: 'accept',
						list : Ext.encode(tmp)
					},
					callback: function(self, success, response){
						try {
							var r = Ext.decode(response.responseText);

							grid.getStore().reload();

							Ext.Msg.alert( _text('MN00023'), r.msg );

						}
						catch(e){
							Ext.Msg.alert(_text('MN00022'), e);
						}
					}
				});
			}
		},'-',{
			text: '반려',
			icon:'/led-icons/cross.png',
			scale: 'medium',
			handler: function(e){
				var grid = e.ownerCt.ownerCt;
				var sel = grid.getSelectionModel().getSelections();

				var tmp = new Array();
				Ext.each(sel,function(r){
					tmp.push(r.json);
				});

				if(grid.getSelectionModel().getCount() < 1) return;

				Ext.Ajax.request({
					url: '/store/nps_work/shutdown.php',
					params: {
						action: 'refuse',
						list : Ext.encode(tmp)
					},
					callback: function(self, success, response){
						try {
							var r = Ext.decode(response.responseText);

							grid.getStore().reload();

							Ext.Msg.alert( _text('MN00023'), r.msg );

						}
						catch(e){
							Ext.Msg.alert(_text('MN00022'), e);
						}
					}
				});
			}
		}]
	});
	return {
		border: false,
		loadMask: true,
		layout: 'fit',
		items: [
			grid
		]
	};
})()