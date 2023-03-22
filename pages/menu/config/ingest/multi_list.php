<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');


$ingest_id = $_POST['id'];
$ingest = $db->queryRow("select * from ingest where id='$ingest_id'");

$meta_table_id = $ingest['meta_table_id'];
$multi_list = $db->queryRow("select * from meta_field where type='listview' and meta_table_id='$meta_table_id' and name='TC정보' ");
if( !empty( $multi_list ) )
{
	$default_value = $multi_list['default_value'];
	$meta_field_id = $multi_list['meta_field_id'];

	$columns = getListViewForm($default_value, $meta_field_id);
}
else
{
	$columns ='';
	$meta_field_id = '';
}

function getListViewForm($columns)
{
	$asciiA = 65;
	$columns = explode(';', $columns);
	$columnCount = count($columns);

	foreach ($columns as $v)
	{
		if($v=='내용')
		{
			$result[] = "{xtype:'textarea', fieldLabel: '$v',width:250 , name: 'column".chr($asciiA++)."'}";
		}
		else if($v=='방송일자' || $v =='촬영일자')
		{
			$result[] = "{xtype:'datefield', fieldLabel: '$v',format: 'Y-m-d',
    											altFormats: 'Y-m-d H:i:s|Y-m-d|Ymd|YmdHis',
    											editable: false, width:250 , name: 'column".chr($asciiA++)."'}";
		}
		else
		{
			$result[] = "{fieldLabel: '$v',width:250 , name: 'column".chr($asciiA++)."'}";
		}
	}

	return array(
		'columnHeight' => ($columnCount * 45 + 20),
		'columns' => join(",\n", $result)
	);
}

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
							id:<?=$ingest_id?>
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
		title: '메타데이터 보기',
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
					width: '50%',
					id: 'tc_list_v',
					layout: 'vbox',
					layoutConfig: {
						align: 'stretch',
						pack: 'start'
					},
					items: [{
						xtype: 'grid',
						id: 'ingest_tc_list',
						title: '타임 코드',
						flex: 1,
						frame: false,
						columnResize: false,
						columnSort: false,
						sm: new Ext.grid.RowSelectionModel({
						}),
						store: tc_list_store,
						columns: [
							{header: 'IN', dataIndex: 'tc_in' },
							{header: 'OUT', dataIndex: 'tc_out' }
						],
						viewConfig: {
							forceFit: true
						}
					},{
							flex: 3,
							layout: 'fit',
							frame: true,
							border: false,

							items: [{
								xtype: 'panel',
								id: 'tc_panel',
								frame: true,
								hidden: true,
								layout: 'fit',
								items: {
									xtype: 'form',
									padding: 5,
									frame: true,
									autoScroll: true,
									defaultType: 'textfield',

									items: [
										<?=$columns['columns']?>
									],

									listeners: {
										afterrender: function(self){
											//self.get(0).focus(false, 250);
										}
									}
								},
								listeners: {
									hide: function(self){
										self.get(0).getForm().reset();
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
				width: '50%',
				autoScroll: true,
				listeners: {
					afterrender: function(self){
						Ext.Ajax.request({
							url: '/store/get_ingest_metadata.php',
							params: {
								content_id : <?=$ingest_id?>
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
