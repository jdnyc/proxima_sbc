<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
////////////////////////////////////////////////////
//
//	2010.10.03 수정
//	한화면 보기시 meta_table_id값이 정의되지 않아 기능수행 안됨.
//	해결: store의 필드안에 meta_table_id 추가. line: 42.
//	작성자 : 박정근, 김성민
////////////////////////////////////////////////////
?>
gridcontentlist2 = {
	title: '편집본',
	xtype: 'contentgrid',
	border: false,

	store: new Ext.data.JsonStore({
		url: '/store/get_content.php',
		idProperty: 'content_id',
		totalProperty: 'total',
		root: 'results',
		remoteSort: true,
		sortInfo: {
			field: 'content_id',
			direction: 'desc'
		},
		baseParams: {
			task: 'listing',
			start: 0,
			limit: 20
		},

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
			{name: 'status', type: 'int'},
			{name: 'meta_table_id'},
			{name: 'title'},
			{name: 'summary'}
			<?php
				// 2010-11-22 컨테이너일 경우 출력 x and f.type != 'container'
				$meta_field_list = $db->queryAll("select * 
													from bc_ud_content t, bc_usr_meta_field f 
													where t.ud_content_id = f.ud_content_id 
													and f.usr_meta_field_type != 'container' 
													order by f.show_order");
				if($meta_field_list){
					$tmp = array();
					$fields = array();
					foreach($meta_field_list as $meta_field){
						if(in_array($meta_field['usr_meta_field_title'], $tmp)) continue;

						array_push($tmp, $meta_field['usr_meta_field_title']);
						if($meta_field['usr_meta_field_type'] == 'datefield'){
							array_push($fields, "{name: '" . str_replace(' ', '_', $meta_field['usr_meta_field_title']) ."'}\n");
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

	sm: new Ext.grid.CheckboxSelectionModel(),
	cm: new Ext.grid.ColumnModel({
		defaults: {
			sortable: false
		},
		columns: [
			new Ext.grid.CheckboxSelectionModel()
			<?php
			if($meta_field_list){
				$tmp = array();
				$fields = array();
				foreach($meta_field_list as $meta_field){
					if(in_array($meta_field['usr_meta_field_title'], $tmp)) continue;

					array_push($tmp, $meta_field['usr_meta_field_title']);
					if($meta_field['usr_meta_field_type'] == 'datefield'){
						array_push($fields, "{header: '" . $meta_field['usr_meta_field_title'] . "', dataIndex: '". str_replace(' ', '_', $meta_field['usr_meta_field_title']) ."'}\n");
					}else{
						if( $meta_field['usr_meta_field_title'] =='프로그램명' )
						{
							array_push($fields, "{header: '" . $meta_field['usr_meta_field_title'] . "', dataIndex: '" . str_replace(' ', '_', $meta_field['usr_meta_field_title']) . "' , width: 200 }\n");
						}
						else if( $meta_field['usr_meta_field_title'] =='부제' || $meta_field['usr_meta_field_title'] =='주요내용' || $meta_field['usr_meta_field_title'] == '비고' )
						{
							array_push($fields, "{header: '" . $meta_field['usr_meta_field_title'] . "', dataIndex: '" . str_replace(' ', '_', $meta_field['usr_meta_field_title']) . "' , width: 300 }\n");
						}
						else
						{
							array_push($fields, "{header: '" . $meta_field['usr_meta_field_title'] . "', dataIndex: '" . str_replace(' ', '_', $meta_field['usr_meta_field_title']) . "'}\n");
						}
					}
				}
				echo ', '.implode(', ', $fields);
			}
			?>
			,{header: '카테고리', dataIndex: 'category'}
		]
	})
};
