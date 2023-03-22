<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lib.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
$meta_table_id = $_POST['panel_id'];
$container_id = $db->queryOne("select container_id from meta_field where meta_table_id='$meta_table_id'and type='container' order by sort");

$showColumnHeader = array(
	'바코드',
	'테이프형태',
	'콘텐츠ID',
	'저장물ID',
	'파일ID',
	'프로그램명',
	'부제',
	'방송일자',
	'방송시작시각',
	'방송종료시각'
);
	$body = "
	{
		xtype:'treegrid',
		id: 'ingest_list',
		//title: '인제스트 요청 리스트',
		//autoHeight: true,
		//columnResize : false,
		//enableSort : false,
       // containerScroll : true,
		//reserveScrollOffset : false,
		//enableHdMenu : true,
        enableDD: false,
		selModel: new Ext.tree.MultiSelectionModel({
		}),
        columns:[
			";

		$meta_fields = $db->queryAll("select name, meta_field_id from meta_field where type !='container' and container_id='$container_id' order by sort");
		$items = "{header: 'NO.',				dataIndex: 'no', width: 60, sortType: 'asInt', align:'center'}";
		foreach($meta_fields as $meta_field)
		{
			if($meta_field['name']=='Tape NO')
			{
				$items .= ",{header:'".$meta_field['name']."', dataIndex: '".$meta_field['meta_field_id']."', width:80, align:'center'}";
				$items .= ",{header:'재생길이', dataIndex: '507', width:80, align:'center'}";
			}
		}

////////////////tc정보 //////////////////
		$tc_default = "select DEFAULT_VALUE from meta_field where name like '%TC정보%' and depth =1 and meta_table_id='$meta_table_id'";
		$tc_default_value = $mdb->queryOne($tc_default);

		$default = getListViewColumns($tc_default_value); //멀티리스트 컬럼 얻기
		foreach($default as $k => $v)
		{
			$asciiA = 65;
			$items.=",{header: '".$v."', dataIndex: 'column".chr($asciiA+$k)."', width: 80, align:'center'}";
		}

/////////////////////////////////////
		$items.=",{header: '상태',			dataIndex: 'status', width: 80, align:'center'}";
		$items.=",{header: 'TITLE',			dataIndex: 'title', width: 80, align:'center', tooltip:'test'}";
		foreach($meta_fields as $meta_field)
		{
			$items .= ",{header:'".$meta_field['name']."', dataIndex: '".$meta_field['meta_field_id']."', width:80, align:'center'}";
		}
		$body.=$items;
		$body.="
		],
		loader: new Ext.tree.TreeLoader({
			baseParams: {
				start_date: Ext.getCmp('start_date').getValue().format('Ymd000000'),
				end_date: Ext.getCmp('end_date').getValue().format('Ymd240000'),
				meta_table_id: $meta_table_id,
				container_id: $container_id,
				start: 0,
				limit: Ariel.limit
			},
			dataUrl: '/store/statistics/ingest/ingest_meta_statistic_data.php',
			listeners: {
				load: function(self){
					Ext.Ajax.request({
						url: '/store/statistics/ingest/getTotal.php',
						params: {
							start_date: self.baseParams.start_date,
							end_date: self.baseParams.end_date,
							meta_table_id: self.baseParams.meta_table_id
						},
						callback: function(opts, success, response){

							Ariel.myMask.hide();

							var r = Ext.decode(response.responseText);
							var cur = self.baseParams.start + self.baseParams.limit;

							Ariel.total_page = Math.floor((r.total/Ariel.limit));

							if(Ariel.total_page <1)
							{
								Ariel.total_page=1;
							}

							if ( self.baseParams.start == 0 )
							{
								ingest_panel.getBottomToolbar().get(0).disable();
								ingest_panel.getBottomToolbar().get(1).disable();
							}
							else
							{
								ingest_panel.getBottomToolbar().get(0).enable();
								ingest_panel.getBottomToolbar().get(1).enable();
							}

							if ( r.total < cur )
							{
								ingest_panel.getBottomToolbar().get(5).disable();
								ingest_panel.getBottomToolbar().get(6).disable();
							}
							else
							{
								ingest_panel.getBottomToolbar().get(5).enable();
								ingest_panel.getBottomToolbar().get(6).enable();
							}

							if(Ariel.cur_page>1)
							{
								ingest_panel.getBottomToolbar().get(0).enable();
								ingest_panel.getBottomToolbar().get(1).enable();
							}
							else
							{
								ingest_panel.getBottomToolbar().get(0).disable();
								ingest_panel.getBottomToolbar().get(1).disable();
							}

							if(Ariel.cur_page == Ariel.total_page)
							{
								ingest_panel.getBottomToolbar().get(5).disable();
								ingest_panel.getBottomToolbar().get(6).disable();
							}


							// 현재 페이지 표시
							Ext.getCmp('start_page').setValue(Ariel.cur_page);
							ingest_panel.getBottomToolbar().get(4).setText(Ariel.total_page);
						}
					});
				},
				loadException: function(self, node, response){
					Ext.Msg.alert('확인', response.responseText);
				}
			}
		}),
		listeners: {
			afterrender: function(self){
				var sort = new Ext.tree.TreeSorter(self, {
					//folderSort: true,
					dir: 'desc',
					sortType: function(node){
						return parseInt(node.id, 10);
					}
				});
			}
		}

    }
	";
echo $body;

function getListViewColumns($columns)
{
	$asciiA = 65;
	$columns = explode(';', $columns);
	foreach ($columns as $v)
	{
		$result[] = $v;
	}
	return $result;
}
?>