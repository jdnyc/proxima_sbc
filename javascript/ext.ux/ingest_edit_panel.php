<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');


$content_id = $_POST['id'];
$content = $db->queryRow("select * from ingest where id='$content_id'");
//$content_type_id = $content['content_type_id'];
//$meta_table_id = $content['meta_table_id'];

?>
(function(){
	/////////////////time code 입력/////////////////
			var tc_list_store = new Ext.data.JsonStore({
				url: '/pages/menu/config/ingest/tc_list_store.php',
				root: 'data',
				fields: ['tc_in','tc_out','id'],
				listeners: {
					load: function(self){
					},
					beforeload: function(self, opts){
						self.baseParams = {
							id:<?=$content_id?>
						}
					}
				}
			});
			tc_list_store.load();
			////////////////time code store////////////////

	var now = new Date();
	var nowdate= now.format('Y-m-d');

	var win = new Ext.Window({
		id: 'ingest_list_win',
		title: '인제스트 수정 창',
		width: '1000',
		top: 50,
		height: 650,
		modal: true,
		layout: 'fit',
		resizable: false,
		maximizable: false,
		listeners: {
			render: function(self){
				//console.log(self);
				//self.clearAnchor();
			},
			destroy: function(self){
				//delete that;
			},
			move: function(self, x, y){//창이 윈도우 포지션을 벗어났을때 0으로 셋팅
				var pos = self.getPosition();
				if(pos[0]<0)
				{
					self.setPosition(0,pos[1]);
				}
				else if(pos[1]<0)
				{
					self.setPosition(pos[0],0);
				}
			}
		},
		items : [{
			border: false,
			layout: 'border',
			items: [{
					region: 'center',
					width: '40%',
					split: false,
					id: 'tc_list_v',
					fieldLabel: 'Time Code List',
					items: [{
						xtype: 'grid',
						id: 'ingest_tc_list',
						title: '타임 코드',
						border: false,
						frame: true,
						height: 585,
						columnResize: false,
						columnSort: false,
						sm: new Ext.grid.RowSelectionModel({
						}),
						store: tc_list_store,
						columns: [
							{header: 'IN', dataIndex: 'tc_in'},
							{header: 'OUT', dataIndex: 'tc_out'}
							,{header: 'ID', dataIndex:'id', hidden: true}
						],
						viewConfig: {
							forceFit: true
						}
					},{
						xtype: 'container',
						layout: 'column',
						defaults: {
							height:'30'
						},
						items: [{
							xtype: 'button',
							columnWidth: .5,
							text: '추가',
							handler: function(b, e){
								buildFormTimeCode();
							}
						},{
							xtype: 'button',
							columnWidth: .5,
							text: '삭제',
							handler: function(b, e){//타임코드 멀티셀렉 삭제
								var del = Ext.getCmp('ingest_tc_list').getStore();
								var sel_array =Ext.getCmp('ingest_tc_list').getSelectionModel().getSelections();
								for(var i=0;i< sel_array.length;i++)
								{
									del.remove(sel_array[i]);
								}
							}
						}]

					}]
				},{
				region: 'east',
				xtype: 'panel',
				id: 'detail_panel',
				title: '메타데이터',
				border: true,
				split: false,
				width: '60%',
				autoScroll: true,

				listeners: {
					afterrender: function(self){
						Ext.Ajax.request({
							url: '/store/get_ingest_metadata.php',
							params: {
								content_id : <?=$content_id?>
							},
							callback: function(opts, success, response){
								if(success)
								{
									try
									{
										var r = Ext.decode(response.responseText);

										self.add(r)
										self.doLayout();
									}
									catch(e)
									{
										Ext.Msg.alert(e['name'], e['message']);
									}
								}
								else
								{
									Ext.Msg.alert('오류', opts.url+'<br />'+response.statusText+'('+response.status+')');
								}
							}
						})
					}
				}
			}]
		}]
	});
	win.show();

})()
