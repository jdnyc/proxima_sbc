Ext.ns('Ariel.menu', 'Ariel.menu.analytics');

Ariel.menu.analytics.Login = Ext.extend(Ext.grid.GridPanel, {
	title: '사용자 로그인 통계',
	loadMask: true,
	stripeRows: true,
	viewConfig: {
		forceFit: true
	},
	listeners: {
		viewready: function(self){
			var start_date = new Date().add(Date.DAY, -30);
			var end_date = new Date();

			Ext.getCmp('login_start_date').setValue(start_date);
			Ext.getCmp('login_end_date').setValue(end_date);

			self.store.load({
				params: {
					action: 'analytics_login_count',
					start_date: Ext.getCmp('login_start_date').getValue().format('Ymd'),
					end_date: Ext.getCmp('login_end_date').getValue().format('Ymd')
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
				'user_name',
				'user_id',
				'login_count'
			],
			listeners: {
				exception: function(self, type, action, response, opts){
					if(type == 'remote'){
						Ext.Msg.alert('오류', response);
					}else{
						Ext.Msg.alert('오류', '페이지를 찾을수 없습니다.');
					}
				}
			}
		});

		this.colModel = new Ext.grid.ColumnModel({
			columns: [
				{header: '이름', dataIndex: 'user_name'},
				{header: '아이디', dataIndex: 'user_id'},
				{header: '로그인 횟수', dataIndex: 'login_count'}
			]
		});

		this.tbar = this.buildTopToolbar();

		Ariel.menu.analytics.Login.superclass.initComponent.call(this);
	},

	buildTopToolbar: function(){
		return ['&nbsp;&nbsp;시작일: ',{
			id: 'login_start_date',
			xtype: 'datefield',
			editable: false,
			format: 'Y-m-d',
			listeners: {
				select: function(self, dt){
					Ext.getCmp('login_end_date').setMinValue(dt);
				}
			}
		},'&nbsp;&nbsp;종료일: ', {
			id: 'login_end_date',
			xtype: 'datefield',
			editable: false,
			format: 'Y-m-d',
			listeners: {
				select: function(self, dt){
					Ext.getCmp('login_start_date').setMaxValue(dt);
				}
			}
		},'-',{
			text: '검색',
			handler: function(btn, e){

				this.store.load({
					params: {
						action: 'analytics_login_count',
						start_date: Ext.getCmp('login_start_date').getValue().format('Ymd'),
						end_date: Ext.getCmp('login_end_date').getValue().format('Ymd')
					}
				});
			},
			scope: this
		}]
	}
});

