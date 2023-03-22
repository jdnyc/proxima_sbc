<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lib.php');


$types = $mdb->queryAll("select content_type_id as type, name from content_type");
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
					$read = $mdb->queryOne("select count(L.id) from log L, content C where L.link_table_id = C.content_id and C.content_type_id = '{$type['type']}' and L.action = 'read'");
					$t[] = "{name: '".$type['name']."', count: '".$read."'}";
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



