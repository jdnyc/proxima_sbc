<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lib.php');


$types = $mdb->queryAll("select meta_table_id as type, name from meta_table");
?>
  
[{
	height: 400,
	loadMask: true,
	border: false,
	items: {
		store: new Ext.data.JsonStore({
			fields:['name', 'count'],
			data: [
			<?php
				unset($t);
				foreach($types as $type){
					$down = $mdb->queryOne("select count(L.id) from log L, content C where link_table_id = C.content_id and C.meta_table_id = '{$type['type']}' and L.action = 'download'");
					$t[] = "{name: '".$type['name']."', count: '".$down."'}";
					}
					echo implode(', ', $t);
			?>
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

