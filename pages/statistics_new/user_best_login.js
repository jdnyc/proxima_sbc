{
	border: false,
	loadMask: true,
	layout: 'fit',

	tbar: [_text('MN00150'),{//!!기간
		xtype: 'datefield',
		id: 'start_date',
		editable: false,
		format: 'Y-m-d',
		listeners: {
			render: function(self){
				var d = new Date();

				self.setMaxValue(d.format('Y-m-d'));
				self.setValue(d.add(Date.MONTH, -1).format('Y-m-d'));
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
			Ext.getCmp('send_form').store.reload();
		}
	}],

	items:{
		id: 'send_form',
		xtype: 'grid',
		border: false,
		loadMask: true,

		store: new Ext.data.JsonStore({
			autoLoad: true,
			url: '/store/statistics/content/best_login.php',
			root: 'data',
			sortInfo: {
				field: 'rank',
				desc: 'desc'
			},
			fields: [
				'rank',
				'user',
				'name',
				'count'
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
		}),
		cm: new Ext.grid.ColumnModel({
			defaults:{
				sortable: true,
				menuDisabled: false
			},
			columns: [
				/*!!
				{header: '순 위', dataIndex: 'rank', width: 50},
				{header: '사용자 아이디', dataIndex: 'user', width: 200},
				{header: '사용자 이름', dataIndex: 'name', width: 200},
				{header: '로그인수', dataIndex: 'count', width: 150}
				*/
				{header: _text('MN00204'), dataIndex: 'rank', width: 50},
				{header: _text('MN00195'), dataIndex: 'user', width: 200},
				{header: _text('MN00189'), dataIndex: 'name', width: 200},
				{header: _text('MN00122'), dataIndex: 'count', width: 150}				
			]
		})
	}
}
