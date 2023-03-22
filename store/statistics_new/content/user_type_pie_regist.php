<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lib.php');


$types = $mdb->queryAll("select meta_table_id as type, name from meta_table where meta_table_id != ".VOD_CLIP."");
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
					$regist = $mdb->queryOne("select count(meta_table_id) from content where meta_table_id = '{$type['type']}'");
					$del_count = $mdb->queryOne("select count(id) from log where link_table_meta = '{$type['type']}'");
					$t[] = "{name: '".$type['name']."', count: '".($regist-$del_count)."'}";
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

