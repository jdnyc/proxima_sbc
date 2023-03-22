<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
?>
gridlastbroadcastlist = {
	xtype: 'contentgrid',
	id: 'last_broadcast_list',
	title: '라디오방송프로그램',

	store: new Ext.data.JsonStore({
		url: '/store/getLastBroadcastList.php',
		root: 'data',
		fields: [
			{name: 'category'},
			{name: 'content_id'},
			{name: 'is_hidden'},
			{name: 'created_time', type:'date', dateFormat: 'YmdHis'},
			{name: 'thumb'},
			{name: 'streaming'},
			{name: 'table_name'},
			{name: 'content_type', mapping: 'name'},
			{name: 'content_type_id'},
			{name: 'title'},
			{name: 'summary'}
			<?php
				$meta_field_list = $db->queryAll("select * from bc_ud_content t, bc_usr_meta_field f where t.ud_content_id = f.ud_content_id order by f.show_order");
				if($meta_field_list){
					$tmp = array();
					$fields = array();
					foreach($meta_field_list as $meta_field){
						if(in_array($meta_field['usr_meta_field_title'], $tmp)) continue;

						array_push($tmp, $meta_field['usr_meta_field_title']);
						if($meta_field['usr_meta_field_type'] == 'datefield'){
							array_push($fields, "{name: '" . str_replace(' ', '_', $meta_field['usr_meta_field_title']) ."', type:'date', dateFormat: 'Y-m-d H:i:s'}\n");
							//print_r($fields);
						}else{
							array_push($fields, "{name: '" . str_replace(' ', '_', $meta_field['usr_meta_field_title']) . "'}\n");
						}
					}
					echo ', '.implode(', ', $fields);
				}
			?>
		]
	}),

	columns: [
		{header: '제목', dataIndex: 'title', width: 130}
		<?php
		if($meta_field_list){
			$tmp = array();
			$fields = array();
			foreach($meta_field_list as $meta_field){
				if(in_array($meta_field['usr_meta_field_title'], $tmp)) continue;

				array_push($tmp, $meta_field['usr_meta_field_title']);
				if($meta_field['usr_meta_field_type'] == 'datefield'){
					array_push($fields, "{header: '" . $meta_field['usr_meta_field_title'] . "', dataIndex: '". str_replace(' ', '_', $meta_field['usr_meta_field_title']) ."', renderer: Ext.util.Format.dateRenderer('Y-m-d')}\n");
				}else{
					array_push($fields, "{header: '" . $meta_field['usr_meta_field_title'] . "', dataIndex: '" . str_replace(' ', '_', $meta_field['usr_meta_field_title']) . "'}\n");
				}
			}
			echo ', '.implode(', ', $fields);
		}
		?>
		,{header: '카테고리', dataIndex: 'category'}
	]
}
