<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lang.php');
?>

(function(){

	var myPageSize = 50;
	var store = new Ext.data.JsonStore({
		url: '/store/statistics/user/user_logoff.php',
		root: 'user_off',
		totalProperty: 'total',
		idProperty: 'user_id',
		sortInfo:{
			field: 'group_name',
			desc: 'desc'
		},
		fields: [
			'group_name',
			'user_id',
			'user_name',
			{name: 'date', type: 'date', dateFormat: 'YmdHis'},
			{name: 'last_login', type: 'date', dateFormat: 'YmdHis'}
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
	});

	return {
		border: false,
		loadMask: true,
		layout: 'fit',

		//>> tbar: ['기간 : ',{ MN00150
		tbar: [_text('MN00150')+' : ',{ 
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
		//>> '부터',
		_text('MN00183'),
		{
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
			//>> text: '조회',
			text: _text('MN00037'),
			handler: function(btn, e){
				Ext.getCmp('logoff_users').store.reload();
			}
		}],

		xtype: 'grid',
		id: 'logoff_users',
		border: false,
		loadMask: true,

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
				//>>{header: '그룹 이름', dataIndex: 'group_name', width: 200}, MN00117
				//>>{header: '사용자 아이디', dataIndex: 'user_id', width: 200}, MN00195
				//>>{header: '사용자 이름', dataIndex: 'user_name', width: 200}, MN00196
				//>>{header: '가입일자', dataIndex: 'date', renderer: Ext.util.Format.dateRenderer('Y-m-d'), width: 150}, MN00100
				//>>{header: '최종 로그인일자', dataIndex: 'last_login', renderer: Ext.util.Format.dateRenderer('Y-m-d'), width: 150} MN00104
				{header: _text('MN00117'), dataIndex: 'group_name', width: 200},
				{header: _text('MN00195'), dataIndex: 'user_id', width: 200},
				{header: _text('MN00196'), dataIndex: 'user_name', width: 200},
				{header: _text('MN00100'), dataIndex: 'date', renderer: Ext.util.Format.dateRenderer('Y-m-d'), width: 150},
				{header: _text('MN00104'), dataIndex: 'last_login', renderer: Ext.util.Format.dateRenderer('Y-m-d'), width: 150}
			]
		}),
		view: new Ext.ux.grid.BufferView({
			rowHeight: 18,
			scrollDelay: false,
			//>>emptyText: '결과값이 없습니다.'MSG00148
			emptyText: _text('MSG00148')
		}),

		bbar: new Ext.PagingToolbar({
			store: store,
			pageSize: myPageSize
		})

	}
})()