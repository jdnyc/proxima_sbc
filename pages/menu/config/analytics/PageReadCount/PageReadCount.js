Ext.ns('Ariel.menu', 'Ariel.menu.analytics');

Ariel.menu.analytics.PageReadCount = Ext.extend(Ext.grid.GridPanel, {
	title: '페이지 조회 통계',
	loadMask: true,
	stripeRows: true,
	viewConfig: {
		forceFit: true,
		emptyText: '데이타가 없습니다.'
	},
	listeners: {
		viewready: function (self) {

			var start_date = new Date().add(Date.DAY, -30);
			var end_date = new Date();

			Ext.getCmp('readed_count_start_date').setValue(start_date);
			Ext.getCmp('readed_count_end_date').setValue(end_date);

			self.store.load({
				params: {
					action: 'analytics_readed_count',
					category: self.getTopToolbar().get(1).getValue(),
					media_type: self.getTopToolbar().get(3).getValue(),
					start_date: start_date.format('Ymd'),
					end_date: end_date.format('Ymd')
				}
			});
		}
	},

	initComponent: function (config) {
		Ext.apply(this, config || {});

		this.store = new Ext.data.JsonStore({
			url: 'get2.php',
			root: 'data',
			fields: [
				{ name: 'category', mapping: 'categories_id' },
				'media_type',
				'title',
				'readed_count',
				{ name: 'registered', type: 'date', dateFormat: 'YmdHis' }
			],
			messageProperty: 'msg',
			listeners: {
				exception: function (self, type, action, opts, response, arg) {
					if (type == 'remote') {
						Ext.Msg.show({
							icon: Ext.Msg.ERROR,
							text: '오류',
							msg: response.msg
						});
					} else {
						Ext.Msg.alert('오류', '요청 페이지가 존재하지 않습니다.');
					}
				}
			}
		});

		this.colModel = new Ext.grid.ColumnModel({
			columns: [
				{ header: '미디어 종류', dataIndex: 'media_type' },
				{ header: '분류', dataIndex: 'category' },
				{ header: '제목', dataIndex: 'title' },
				{ header: '조회 수', dataIndex: 'readed_count' },
				{ header: '등록일', dataIndex: 'registered', renderer: Ext.util.Format.dateRenderer('Y-m-d') }
			]
		});

		this.tbar = this.buildTopToolbar();

		Ariel.menu.analytics.PageReadCount.superclass.initComponent.call(this);
	},

	buildTopToolbar: function () {
		return ['분류: ', new Ext.ux.ComboTree({ value: '/전체' }),
			'&nbsp;&nbsp;미디어 종류: ', {
				xtype: 'combo',
				store: new Ext.data.JsonStore({
					autoLoad: true,
					url: 'get.php',
					baseParams: {
						action: 'table_list'
					},
					root: 'data',
					fields: [
						'srl',
						'name'
					]
				}),
				lazyInit: false,
				editable: false,
				triggerAction: 'all',
				displayField: 'name',
				valueField: 'srl',
				value: -1
			}, '&nbsp;&nbsp;시작일: ', {
				id: 'readed_count_start_date',
				xtype: 'datefield',
				editable: false,
				format: 'Y-m-d',
				listeners: {
					select: function (self, dt) {
						Ext.getCmp('readed_count_end_date').setMinValue(dt);
					}
				}
			}, '&nbsp;&nbsp;종료일: ', {
				id: 'readed_count_end_date',
				xtype: 'datefield',
				editable: false,
				format: 'Y-m-d',
				listeners: {
					select: function (self, dt) {
						Ext.getCmp('readed_count_start_date').setMaxValue(dt);
					}
				}
			}, '-', {
				text: '검색',
				handler: function (btn, e) {
					this.store.load({
						params: {
							action: 'analytics_readed_count',
							category: this.getTopToolbar().get(1).hiddenValue,
							media_type: this.getTopToolbar().get(3).getValue(),
							start_date: Ext.getCmp('readed_count_start_date').getValue().format('Ymd'),
							end_date: Ext.getCmp('readed_count_end_date').getValue().format('Ymd')
						}
					});
				},
				scope: this
			}]
	}
});
