(function () {
	var myPageSize = 50;
	var store = new Ext.data.JsonStore({
		//		autoLoad: true,
		url: '/store/statistics/content/delete_ranking.php',
		root: 'del_rank',
		sortInfo: {
			field: 'rank',
			desc: 'desc'
		},
		fields: [
			'rank',
			'type',
			'title',
			'user',
			'description',
			{ name: 'date', type: 'date', dateFormat: 'YmdHis' }
		]
	});



	return {

		tbar: [_text('MN00150'), {//!!기간
			xtype: 'datefield',
			id: 'del_start_date',
			width: 100,
			editable: false,
			format: 'Y-m-d',
			listeners: {
				render: function (self) {
					var d = new Date();

					self.setMaxValue(d.format('Y-m-d'));
					self.setValue(d.add(Date.DAY, -7).format('Y-m-d'));
				}
			}
		},
		_text('MN00183')//!!'부터'
			, {
			xtype: 'datefield',
			id: 'del_end_date',
			width: 100,
			editable: false,
			format: 'Y-m-d',
			listeners: {
				render: function (self) {
					var d = new Date();

					self.setMaxValue(d.format('Y-m-d'));
					self.setValue(d.format('Y-m-d'));
				}
			}
		}, {
			//icon: '/led-icons/find.png',
			//text: '조회',
			//text: _text('MN00059'),
			cls: 'proxima_button_customize',
			width: 30,
			text: '<span style="position:relative;top:1px;" title="' + _text('MN00059') + '"><i class="fa fa-search" style="font-size:13px;color:white;"></i></span>',
			handler: function (btn, e) {
				Ext.getCmp('delete_info').store.reload({
					params: {
						start: 0,
						limit: myPageSize,
						start_date: Ext.getCmp('del_start_date').getValue().format('Ymd000000'),
						end_date: Ext.getCmp('del_end_date').getValue().format('Ymd240000')
					}
				});
			}
		}, {
			xtype: 'button',
			//text : '엑셀출력',
			//icon : '/led-icons/doc_excel_table.png',
			cls: 'proxima_button_customize',
			width: 30,
			text: '<span style="position:relative;top:1px;" title="' + _text('MN00212') + '"><i class="fa fa-file-excel-o" style="font-size:13px;color:white;"></i></span>',
			handler: function (self, e) {
				var search_value1, search_value2, grid, search_type;

				search_type = 'date';
				search_value1 = Ext.getCmp('del_start_date').getValue().format('Ymd000000');
				search_value2 = Ext.getCmp('del_end_date').getValue().format('Ymd240000');

				grid = Ext.getCmp('delete_info');
				if (grid.store.totalLength == 0) {
					Ext.Msg.alert(_text('MN00023'), _text('MSG02051'));//'출력하실 내용이 없습니다.'
					return;
				}
				else {
					excelData('date', '/store/statistics/content/delete_ranking.php', grid.colModel, '', search_value1, search_value2);
				}
			}
		}],

		xtype: 'grid',
		id: 'delete_info',
		title: '<span class="user_span"><span class="icon_title"><i class=""></i></span><span class="main_title_header">' + _text('MN00318') + '</span></span>',
		cls: 'grid_title_customize proxima_customize',
		stripeRows: true,
		border: false,
		loadMask: true,
		height: 200,
		store: store,
		listeners: {
			viewready: function (self) {
				self.store.load({
					params: {
						start: 0,
						limit: myPageSize,
						start_date: Ext.getCmp('del_start_date').getValue().format('Ymd000000'),
						end_date: Ext.getCmp('del_end_date').getValue().format('Ymd240000')
					}
				})
			},
			afterrender: function (self) {
				var tbar = self.getTopToolbar();
				var endDateIndex = tbar.items.items.indexOf(Ext.getCmp('del_end_date'));
				var radioDay = new Custom.RadioDay({
					dateFieldConfig: {
						startDateField: Ext.getCmp('del_start_date'),
						endDateField: Ext.getCmp('del_end_date')
					},
					checkDay: 'one'
				})
				tbar.insert(endDateIndex + 1, radioDay);
				tbar.doLayout();
			}
		},
		cm: new Ext.grid.ColumnModel({
			defaults: {
				sortable: true
			},
			columns: [
				/*!!
				{header: '순위', dataIndex: 'rank', width: 50},
				{header: '타입', dataIndex: 'type', width: 100},
				{header: '항목', dataIndex: 'title', width: 350},
				{header: '사용자 이름', dataIndex: 'user', width: 150},
				{header: '삭제사유', dataIndex: 'description', width: 350},
				{header: '삭제일자', dataIndex: 'date', renderer: Ext.util.Format.dateRenderer('Y-m-d'), width: 150}
				*/
				{ header: _text('MN00204'), dataIndex: 'rank', width: 60 },
				{ header: _text('MN00291'), dataIndex: 'type', width: 100 },
				{ header: _text('MN00307'), dataIndex: 'title', width: 350 },
				{ header: _text('MN00189'), dataIndex: 'user', width: 150 },
				{ header: _text('MN00128'), dataIndex: 'description', width: 350 },
				{ header: _text('MN00105'), dataIndex: 'date', renderer: Ext.util.Format.dateRenderer('Y-m-d'), width: 150 }

			]
		}),
		view: new Ext.ux.grid.BufferView({
			rowHeight: 18,
			scrollDelay: false,
			forceFit: true,
			emptyText: _text('MSG00148')
		}),

		bbar: new Ext.PagingToolbar({
			store: store,
			pageSize: myPageSize
		})
	}
})()