<?php
$cItemList = "
{
	xtype: 'grid',
	id: 'item_list',
	fieldLabel: '상품목록',
	loadMask: true,
	name: 'x_items_list',
	height: 200,
	frame: true,
	border: true,
	store: new Ext.data.JsonStore({
		autoLoad: true,
		url: '/store/getPgmItem.php',
		root: 'data',
		fields: [
			'content_id',
			'item_cd',
			'item_nm'
		],
		baseParams: {
			content_id: $content_id
		}
	}),
	cm: new Ext.grid.ColumnModel({
		defaults: {
			menuDisabled: true,
			sortable: false
		},
		columns: [
			{header: '코드', dataIndex: 'item_cd', width: 100, align: 'center'},
			{header: '상품명', dataIndex: 'item_nm', width: 500}
		]
	}),

	viewConfig: {
		emptyText: '등록된 상품이 없습니다.'
	},

	tbar: [{
		icon: '/led-icons/add.png',
		text: '추가',
		handler: function(b, e){
			Ext.Ajax.request({
				url: '/php/component/searchItem.js',
				callback: function(opts, success, resp){
					if (success) {
						Ext.decode(resp.responseText);
					} else {
						Ext.Msg.alert( _text('MN01098'), resp.statusText);//'서버 오류'
					}
				}
			});
		}
	},{
		icon: '/led-icons/delete.png',
		text: '삭제',
		handler: function(b, e){
			var g = Ext.getCmp('item_list');
			var sm = Ext.getCmp('item_list').getSelectionModel();
			var selectedList = sm.getSelections();

			g.store.remove(selectedList);
		}
	}]
}
";
?>