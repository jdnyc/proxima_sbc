<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

?>
(function(){
	var myPageSize = 10;
	var store = new Ext.data.JsonStore({
		url: '/store/ingest_content_type.php',
		root: 'data',
		fields: [
			'content_type_id',
			'meta_table_id',
			'name'
		]
	});
	store.load();

	var win = new Ext.Window({
		title: '콘텐츠 유형 선택',
		modal: true,
		width: 350,
		height: 180,
		layout: 'fit',

		items: [{
			xtype: 'grid',
			loadMask: true,
			enableDD: false,
			store: store,

			listeners: {
				rowdblclick: function(self){
					self.ownerCt.doSubmit(self.ownerCt);
				}
			},
			cm: new Ext.grid.ColumnModel({
				defaults: {
					sortable: true
				},
				columns: [
					{header: '콘텐츠 유형',	dataIndex: 'name',align: 'center',sortable: 'true'}
				]
			}),
			buttons: [{
				text: '선택',
				handler: function(b, e){
					b.ownerCt.ownerCt.ownerCt.doSubmit(b.ownerCt.ownerCt.ownerCt);
				}
			},{
				text: '닫기',
				handler: function(b, e){
					b.ownerCt.ownerCt.ownerCt.close();
				}
			}],
			viewConfig: {
				forceFit: true
			}
		}],
		doSubmit: function(w){
			var sm = w.get(0).getSelectionModel();
			if (!sm.hasSelection())
			{
				Ext.Msg.alert('확인', '선택해주세요.');
				return;
			}

			var sel = sm.getSelected();
			var content_type_id = sel.get('content_type_id');
			var meta_table_id = sel.get('meta_table_id');
			///////////새창띄우기////////////////
		//	var new_w = window.open('/javascript/ext.ux/new.php?content_id=['+records+']&content_type_id='+content_type_id+'&meta_table_id='+meta_table_id,'newwindow');
			//new_w.focus();

			//var content_id = records[0];

			Ext.Ajax.request({
				url: '/javascript/ext.ux/ingest_list_panel.php',
				params: {
					content_type_id: content_type_id,
					meta_table_id: meta_table_id
				},
				callback: function(self, success, response){
					if (success)
					{
						try
						{
							Ext.decode(response.responseText);
						}
						catch (e)
						{
							Ext.Msg.alert(e['name'], e['message'] );
						}
					}
					else
					{
						Ext.Msg.alert('서버 오류', response.statusText+'('+response.status+')');
					}
				}
			});
			w.close();
		}

	});
	win.show();


})()