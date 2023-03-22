<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lang.php');
fn_checkAuthPermission($_SESSION);

?>

(function(){
	var myMask = new Ext.LoadMask(Ext.getBody(), {msg:"loading..."});

	var store = new Ext.data.JsonStore({
		url: '/pages/statistics_new/storage_store.php',
		root: 'data',
		totalProperty: 'total',
		idProperty: 'user_id',
		fields:[
			{name: 'storage_id'},
			{name: 'type'},
			{name: 'name'},
			{name: 'path'},
			{name: 'description'},
			{name: 'usage'},
			{name: 'usable'},
			{name: 'quota'}
		],
		listeners: {
			beforeload: function(self, opts){
				opts.params = opts.params || {};

				Ext.apply(opts.params, {
				});
			},
			load: function(store, records, opts){
				myMask.hide();
			}
		}
	});

	return {
		title: '<span class="user_span"><span class="icon_title"><i class=""></i></span><span class="main_title_header">'+_text('MN01105')+'</span></span>',
		cls: 'grid_title_customize',
		border: false,
		loadMask: true,
		layout: 'card',
		activeItem: 0,
		autoScroll: true,

		tbar: [{
			//icon: '/led-icons/arrow_refresh.png',
			//text: "<?=_text('MN00390')?>",
			cls: 'proxima_button_customize',
			width: 30,
			text: '<span style="position:relative;top:1px;" title="'+_text('MN00390')+'"><i class="fa fa-refresh" style="font-size:13px;color:white;"></i></span>',
			handler: function(btn, e){
				Ext.getCmp('storage_statistic_list').getStore().load();
			}
		},{
			xtype : 'button',
			//text : '엑셀출력',
			//icon : '/led-icons/doc_excel_table.png',
			cls: 'proxima_button_customize',
			width: 30,
			text: '<span style="position:relative;top:1px;" title="'+_text('MN00212')+'"><i class="fa fa-file-excel-o" style="font-size:13px;color:white;"></i></span>',
			handler : function(self, e){
				var grid = Ext.getCmp('storage_statistic_list');
				if(grid.store.totalLength == 0 )
				{
					Ext.Msg.alert( _text('MN00023'), _text('MSG02051'));//'출력하실 내용이 없습니다.'
					return;
				}
				else
				{
					excelData('text', '/pages/statistics_new/storage_store.php', grid.colModel, '', '', '');
				}
			}
		}],

		items: [{
			xtype: 'grid',
			id: 'storage_statistic_list',
			cls: 'proxima_customize',
			stripeRows: true,
			border: false,
			loadMask: true,
			store: store,
			cm: new Ext.grid.ColumnModel({
				columns:[
					{header: _text('MN02350'), dataIndex: 'name', width: 250, align: 'left'},
					{header: _text('MN02351'), dataIndex: 'usage', width: 150, align: 'center'},
					{header: _text('MN02352'), dataIndex: 'usable', width: 150, align: 'center'},
					{header: _text('MN02353'), dataIndex: 'quota', width: 150, align: 'center'},
				]
			}),
			view: new Ext.ux.grid.BufferView({
				scrollDelay: false,
				forceFit:true
			}),
			listeners: {
				afterrender: function(self){
					self.getStore().load();
				}
			}
		}]

	}
})()