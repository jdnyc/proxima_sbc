(function(){
	var myPageSize = 50;
	var store = new Ext.data.JsonStore({
	//	autoLoad: true,
		url: '/store/statistics/content/ranking_read_cg.php',
		root: 'read_rank',
		totalProperty: 'total',
		sortInfo:{
			field: 'inx',
			desc: 'desc'
		},
		fields: [
			'inx',
			'ud_content_title',
			'title',
			'user_nm',
			{name: 'log_date', type: 'date', dateFormat: 'YmdHis'}
		]
	});
//console.log(store);
	return {

		tbar: [_text('MN00150'),{//!!기간
			xtype: 'datefield',
			id: 'start_date',
			editable: false,
			format: 'Y-m-d',
			listeners: {
				render: function(self){
					var d = new Date();

					self.setMaxValue(d.format('Y-m-d'));
					self.setValue(d.add(Date.DAY, -7).format('Y-m-d'));
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
					var d = new Date();

					self.setMaxValue(d.format('Y-m-d'));
					self.setValue(d.format('Y-m-d'));
				}
			}
		},'-',{
			icon: '/led-icons/find.png',
			//text: '조회',
			text: _text('MN00059'),
			handler: function(btn, e){
				Ext.getCmp('read_info').store.reload({
					params: {
						start: 0,
						limit: myPageSize,
						start_date: Ext.getCmp('start_date').getValue().format('Ymd000000'),
						end_date: Ext.getCmp('end_date').getValue().format('Ymd240000')
					}
				});
			}
		}],

		xtype: 'grid',
		id: 'read_info',
		border: false,
		loadMask: true,
		height: 200,
		store: store,
		listeners: {
			viewready: function(self){
				self.store.load({
					params: {
						start: 0,
						limit: myPageSize,
						start_date: Ext.getCmp('start_date').getValue().format('Ymd000000'),
						end_date: Ext.getCmp('end_date').getValue().format('Ymd240000')
					}
				})
			}
		},
		cm: new Ext.grid.ColumnModel({
			defaults:{
				sortable: true
			},
			columns: [
					/*!!
				{header: '순위', dataIndex: 'rank', width: 50},
				{header: '타입', dataIndex: 'type', width: 100},
				{header: '항목', dataIndex: 'title', width: 350},
				{header: '사용자 이름', dataIndex: 'user', width: 150},
				{header: '조회일자', dataIndex: 'date', renderer: Ext.util.Format.dateRenderer('Y-m-d'), width: 150}
				*/
				{header: _text('MN00204'), dataIndex: 'inx', width: 50},
				{header: _text('MN00291'), dataIndex: 'ud_content_title', width: 100},
				{header: _text('MN00307'), dataIndex: 'title', width: 350},
				{header: _text('MN00120'), dataIndex: 'user_nm', width: 150},
				{header: _text('MN00253'), dataIndex: 'log_date', renderer: Ext.util.Format.dateRenderer('Y-m-d'), width: 150, align:'center'}
			

			]
		}),
		view: new Ext.ux.grid.BufferView({
			rowHeight: 18,
			scrollDelay: false,
			forceFit : true
		}),

		bbar: new Ext.PagingToolbar({
			store: store,
			pageSize: myPageSize
		})
	}
})()