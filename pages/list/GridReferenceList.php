<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
?>
gridreferencelist = {
	xtype: 'contentgrid',
	id: 'reference_list',
	meta_table_id: '81768',
	title: '참조영상',

	/*
	2011-02-07 박정근
	탭 별로 검색 가능하도록 변경
	*/
	reload: function( args ){
		this.store.reload({
			params: args
		})
	},

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
			meta_table_id: 81768,
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
			{name: 'meta_table_id', type: 'int'},
			{name: 'title'},
			{name: 'summary'}
			<?php
				//보여지는 항목 조정 2011-2-21 by 이성용
				$meta_field_list = $db->queryAll("select * 
													from bc_ud_content t, bc_usr_meta_field f 
													where t.ud_content_id='81768' 
													and t.ud_content_id = f.ud_content_id 
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
		columns: [
			//new Ext.grid.CheckboxSelectionModel(),
			new Ext.grid.RowNumberer(),
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
				echo implode(', ', $fields).', ';
			}
			?>
			{header: '카테고리', dataIndex: 'category'}
		]
	})
};
