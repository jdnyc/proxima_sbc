<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lang.php');
?>


(function(){

	var myPageSize = 50;
	var store = new Ext.data.JsonStore({
		url: '/store/statistics/user/user_group.php',
		root: 'user_group',
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
		]
	});

	return {
		

		xtype: 'grid',
		id: 'user_list',
		border: false,
		loadMask: true,
		
		store: store,
		listeners: {
			viewready: function(self){
				self.store.load({
					params: {
						start: 0,
						limit: myPageSize
					}
				})
			}
		},
		cm: new Ext.grid.ColumnModel({
			defaults:{
				sortable: true		
			},
			columns: [
				//>>{header: '그룹 이름', dataIndex: 'group_name', width: 200},
				//>>{header: '사용자 아이디', dataIndex: 'user_id', width: 200},
				//>>{header: '사용자 이름', dataIndex: 'user_name', width: 200},
				//>>{header: '가입일자', dataIndex: 'date', renderer: Ext.util.Format.dateRenderer('Y-m-d'), width: 130}
				
				{header: '<?=_text('MN00117')?>', dataIndex: 'group_name', width: 200},
				{header: '<?=_text('MN00195')?>', dataIndex: 'user_id', width: 200},
				{header: '<?=_text('MN00196')?>', dataIndex: 'user_name', width: 200},
				{header: '<?=_text('MN00100')?>', dataIndex: 'date', renderer: Ext.util.Format.dateRenderer('Y-m-d'), width: 130}
			]
		}),
		view: new Ext.ux.grid.BufferView({
			rowHeight: 18,
			scrollDelay: false
		}),

		bbar: new Ext.PagingToolbar({
			store: store,
			pageSize: myPageSize			
		})

	}
})()