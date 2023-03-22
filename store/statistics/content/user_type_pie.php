{
	border: false,
	layout: 'fit',

	tbar: [_text('MN00294'),{ //!!'통계 종류:'
		xtype: 'combo',
		id: 'statistics_user_type',
		width: 100,
		triggerAction: 'all',
		editable: false,
		mode: 'local',
		displayField: 'd',
		valueField: 'v',
		value: 'reg',
		store: new Ext.data.ArrayStore({
			fields: [
				'v', 'd'
			],
			data: [
				//!!['reg', '등록'],
				//!!['read', '조회'],
				//!!['download', '다운로드']
				['reg', _text('MN00038')],
				['read', _text('MN00047')],
				['download', _text('MN00050')]
			]
		})
	},
	'-',_text('MN00150')//!!기간
	,{
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
			Ext.getCmp('statistics_user_type_chart').store.reload();
		}
	}],

	items: {
		xtype: 'piechart',
		id: 'statistics_user_type_chart',
		dataField: 'count',
		categoryField: 'name',
		listeners: {
			render: function(self){
				self.store.load();
			}
		},
		store: new Ext.data.JsonStore({
			url: '/store/get_statistics_user_type.php',
			root: 'data',
			fields:[
				'name',
				'count'
			],
			listeners: {
				beforeload: function(self, opts){
					opts.params = opts.params || {};

					Ext.apply(opts.params, {
						type: Ext.getCmp('statistics_user_type').getValue(),
						start_date: Ext.getCmp('start_date').getValue().format('Ymd000000'),
						end_date: Ext.getCmp('end_date').getValue().format('Ymd240000')
					});
				}
			}
		}),
		extraStyle: {
			legend: {
				display: 'bottom',
				padding: 5,
				font: {
					family: 'Tahoma',
					size: 13
				}
			}
		}
	}
}