<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lib.php');


$types = $mdb->queryAll("select content_type_id as type, name from content_type");
?>
{
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
					$regist = $mdb->queryOne("select count(content_id) from content where content_type_id = '{$type['type']}'");
					$del_count = $mdb->queryOne("select count(id) from log where link_table = '{$type['type']}'");
					$t[] = "{name: '".$type['name']."', count: '".($regist-$del_count)."'}";
				}
				echo implode(', ', $t);
				?>
			]
		}),
		xtype: 'piechart',
		dataField: 'count',
		categoryField: 'name',
		
		extraStyle:	{
			legend:	{
				display: 'bottom',
				padding: 5,
				font: {
					family: 'Tahoma',
					size: 13
				}
			}
		}
	}
}