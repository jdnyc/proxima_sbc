<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lib.php');


$types = $mdb->queryAll("select content_type_id as type, name from content_type");
?>
  
[{
	height: 400,
	loadMask: true,
	border: false,
	title: '콘텐츠 타입별 등록 현황',
	items: {
		store: new Ext.data.JsonStore({
			fields:['name', 'count'],
			data: [
			<?
				foreach($types as $type){
					$regist = $mdb->queryOne("select count(content_id) from content where content_type_id = '{$type['type']}'");
			?>
				{name:'<?=$type['name']?>', count: '<?=$regist?>'},
			<?}?>
			]
		}),
		xtype: 'piechart',
		dataField: 'count',
		categoryField: 'name',
		
		extraStyle:
		{
			legend:
			{
				display: 'bottom',
				padding: 5,
				font:
				{
					family: 'Tahoma',
					size: 13
				}
			}
		}
	}
},{
	height: 400,
	loadMask: true,
	border: false,
	title: '콘텐츠 타입별 조회 현황',
	items: {
		store: new Ext.data.JsonStore({
			fields:['name', 'count'],
			data: [
			<?
				foreach($types as $type){
					$read = $mdb->queryOne("select count(L.id) from log L, content C where link_table_id = C.content_id and C.content_type_id = '{$type['type']}' and L.action = 'read'");
					$down = $mdb->queryOne("select count(L.id) from log L, content C wheRe link_table_id = C.content_id and C.content_type_id = '{$type['type']}' and L.action = 'download'");
			?>
				{name:'<?=$type['name']?>', count: '<?=$read?>'},
			<?}?>
			]
		}),
		xtype: 'piechart',
		dataField: 'count',
		categoryField: 'name',
		
		extraStyle:
		{
			legend:
			{
				display: 'bottom',
				padding: 5,
				font:
				{
					family: 'Tahoma',
					size: 13
				}
			}
		}
	}
},{
	height: 400,
	loadMask: true,
	border: false,
	title: '콘텐츠 타입별 다운로드 현황',
	items: {
		store: new Ext.data.JsonStore({
			fields:['name', 'count'],
			data: [
			<?
				foreach($types as $type){
					$down = $mdb->queryOne("select count(L.id) from log L, content C wheRe link_table_id = C.content_id and C.content_type_id = '{$type['type']}' and L.action = 'download'");
			?>
				{name:'<?=$type['name']?>', count: '<?=$down?>'},
			<?}?>
			]
		}),
		xtype: 'piechart',
		dataField: 'count',
		categoryField: 'name',
		
		extraStyle:
		{
			legend:
			{
				display: 'bottom',
				padding: 5,
				font:
				{
					family: 'Tahoma',
					size: 13
				}
			}
		}
	}
}]

