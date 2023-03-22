Ext.ns('Ariel.menu', 'Ariel.menu.analytics');

Ariel.menu.analytics.Register = Ext.extend(Ext.grid.GridPanel, {
	title: '등록 통계',
	loadMask: true,
	viewConfig: {
		emptyText: '데이터가 없습니다.',
		forceFit: true
	},
	listeners: {
		viewready: function(self){

			var start_date = new Date().add(Date.DAY, -30);
			var end_date = new Date();

			Ext.getCmp('register_start_date').setValue(start_date);
			Ext.getCmp('register_end_date').setValue(end_date);

			self.store.load({
				params: {
					action: 'analytics_register',
					category: self.getTopToolbar().get(1).getValue(),
					media_type: self.getTopToolbar().get(3).getValue(),
					start_date: start_date.format('Ymd'),
					end_date: end_date.format('Ymd')
				}
			});
		}
	},

	initComponent: function(config){
		Ext.apply(this, config || {});

		this.store = new Ext.data.JsonStore({
			url: 'get2.php',
			root: 'data',
			fields: [
				'user_id',
				'register_count'
			]
		});

		this.colModel = new Ext.grid.ColumnModel({
			columns: [
				{header: '사용자 아이디', dataIndex: 'user_id'},
				{header: '등록 수', dataIndex: 'register_count'}
			]
		});

		this.tbar = this.buildTopToolbar();

		Ariel.menu.analytics.Register.superclass.initComponent.call(this);
	},

	buildTopToolbar: function(){
		return ['카테고리: ', new Ext.ux.ComboTree,
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
			lazyInit: true,
			editable: false,
			triggerAction: 'all',
			displayField: 'name',
			valueField: 'name',
			value: '전체',
			hiddenValue: '0'
		},'&nbsp;&nbsp;시작일: ',{
			id: 'register_start_date',
			xtype: 'datefield',
			editable: false,
			format: 'Y-m-d',
			listeners: {
				select: function(self, dt){
					Ext.getCmp('register_end_date').setMinValue(dt);
				}
			}
		},'&nbsp;&nbsp;종료일: ', {
			id: 'register_end_date',
			xtype: 'datefield',
			editable: false,
			format: 'Y-m-d',
			listeners: {
				select: function(self, dt){
					Ext.getCmp('register_start_date').setMaxValue(dt);
				}
			}
		},'-',{
			text: '검색',
			handler: function(btn, e){
				this.store.load({
					params: {
						action: 'analytics_register',
						category: this.getTopToolbar().get(1).hiddenValue,
						media_type: this.getTopToolbar().get(3).getValue(),
						start_date: Ext.getCmp('register_start_date').getValue().format('Ymd'),
						end_date: Ext.getCmp('register_end_date').getValue().format('Ymd')
					}
				});
			},
			scope: this
		},{
			text: '엑셀로 저장',
			handler: function(btn, e){
						var category = this.getTopToolbar().get(1).hiddenValue;
						var media_type = this.getTopToolbar().get(3).getValue();
						var start_date = Ext.getCmp('register_start_date').getValue().format('Ymd');
						var end_date = Ext.getCmp('register_end_date').getValue().format('Ymd');

				window.open('export.php?type=excel&action=register&start_date=' + start_date + '&end_date=' + end_date + '&category=' + category + '&media_type=' + media_type);
			},
			scope: this
		}]
	}
});

