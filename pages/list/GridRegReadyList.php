<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

$thumb_width = $db->queryOne("select value from bc_html_config where type='thumb_width'");
$thumb_height = $db->queryOne("select value from bc_html_config where type='thumb_height'");

$list_width = $db->queryOne("select value from bc_html_config where type='list_width'");
$list_height = $db->queryOne("select value from bc_html_config where type='list_height'");

//소재영상
?>
gridregreadylist = {
	xtype: 'contentgrid',
	id: 'reg_ready_list',
	meta_table_id: '81767',
	title: '소재영상',

	/*
	2011-02-07 박정근
	탭 별로 검색 가능하도록 변경
	*/
	reload: function( args ){
		this.store.reload({
			params: args
		})
	},
	template: {
		//리스트+섬네일보기
		list: new Ext.XTemplate(
			'<div class="x-grid3-row ux-explorerview-detailed-icon-row ',
			'<tpl if="status == 5">content-status-review-return</tpl>',
			'">',
				'<table class="x-grid3-row-table">',
					'<tbody>',
						'<tr>',
							'<td class="x-grid3-col x-grid3-cell ux-explorerview-icon" style="vertical-align:middle" align="center">',
							'<img src="/<?=WEB_PATH?>/{thumb}" ext:qtip="{qtip}"',
								'<tpl if="this.gtWidth(thumb) === 1"> width="<?=$list_width?>" </tpl>',
								'<tpl if="this.gtWidth(thumb) === -1"> height="<?=$list_height?>" </tpl>',
							'></td>',
							'<td class="x-grid3-col x-grid3-cell">',
								'<div class="x-grid3-cell-inner" unselectable="on" style=" ">자료명:{datanm}</p>연출자:{prodper}</p>입수일자:{acquiymd}</div>',
							'</td>',
						'</tr>',
					'</tbody>',
				'</table>',
			'</div>',
			{
			/*
				function return이 1인 경우 가로가 비율에 비하여 크며
				return이 -1인 경우 세로가 비율에 비하여 크며
				return이 0인 경우 이미지 가로,세로 중 하나 이상이 0인 잘못된 데이터
			*/
				gtWidth: function(url, w){
					var imgObj = new Image();
					imgObj.src = '/<?=WEB_PATH?>/' + url;

					if (imgObj.width == 0 || imgObj.height == 0) return 0;
					if ( (imgObj.width/<?=$list_width?>) > (imgObj.height/<?=$list_height?>) )
					{
						return 1;
					}
					else
					{
						return -1;
					}
				}
			}
		),
		//섬네일보기
		tile: new Ext.XTemplate(
			'<div class="x-grid3-row ux-explorerview-large-icon-row ',
			'<tpl if="status == \'5\'">content-status-review-return</tpl>',
			'">',
				'<table class="x-grid3-row-table">',
					'<tbody>',
						'<tr height="<?=$thumb_height?>">',
							'<td class="x-grid3-col x-grid3-cell ux-explorerview-icon" style=" vertical-align:middle" align="center">',
							'<img src="/<?=WEB_PATH?>/{thumb}" ext:qtip="{qtip}"',
								'<tpl if="this.gtWidth(thumb) === 1"> width="<?=$thumb_width?>" </tpl>',
								'<tpl if="this.gtWidth(thumb) === -1"> height="<?=$thumb_height?>" </tpl>',
							'></td>',
						'</tr>',
						'<tr>',
							'<td class="x-grid3-col x-grid3-cell"><div class="x-grid3-cell-inner" unselectable="on">{datanm}</div></td></tr>',
					'</tbody>',
				'</table>',
			'</div>',
			{
				gtWidth: function(url){
					var imgObj = new Image();
					imgObj.src = '/<?=WEB_PATH?>/' + url;

					if (imgObj.width == 0 || imgObj.height == 0) return 0;
					if ( (imgObj.width/<?=$thumb_width?>) > (imgObj.height/<?=$thumb_height?>) )
					{
						return 1;
					}
					else
					{
						return -1;
					}
				}
			}
		)
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
			meta_table_id: 81767,
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
			{name: 'datanm'},
			{name: 'connote'},
			{name: 'prodper'},
			{name: 'acquiymd'},
			{name: 'summary'},
			{name: 'qtip'}
			<?php
				//보여지는 항목 조정 2011-2-21 by 이성용
				$meta_field_list = $db->queryAll("select * 
													from bc_ud_content t, bc_usr_meta_field f 
													where t.ud_content_id='81767' 
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
		//	new Ext.grid.CheckboxSelectionModel()
			new Ext.grid.RowNumberer()
			//,{header: 'Tape NO', dataIndex: 'Tape_NO' }
			,{header: '자료명', dataIndex: 'datanm', width: 300  }
		//	,{header: '수록내용', dataIndex: '수록내용', width: 300  }
			,{header: '연출자', dataIndex: '연출자' }
			,{header: '프로그램명', dataIndex: '프로그램명', width: 200 }
		//	,{header: '부제', dataIndex: '부제', width: 300 }
		//	,{header: '색인어', dataIndex: '색인어' }
		//	,{header: '소재구분', dataIndex: '소재구분' }
			,{header: '입수일자', dataIndex: '입수일자'}
		//	,{header: '촬영장소', dataIndex: '촬영장소' }
			,{header: '취급구분', dataIndex: '취급구분' }
			<?php //사용자 요구에 따라 필드목록과 순서 고정 2011-02-25 by 이성용
	/*		if($meta_field_list){
				$tmp = array();
				$fields = array();
				foreach($meta_field_list as $meta_field){
					if(in_array($meta_field['name'], $tmp)) continue;

					array_push($tmp, $meta_field['name']);
					if($meta_field['type'] == 'datefield'){
						array_push($fields, "{header: '" . $meta_field['name'] . "', dataIndex: '". str_replace(' ', '_', $meta_field['name']) ."', renderer: Ext.util.Format.dateRenderer('Y-m-d')}\n");
					}else{
						if( $meta_field['name'] =='프로그램명' || $meta_field['name'] =='자료명' )
						{
							array_push($fields, "{header: '" . $meta_field['name'] . "', dataIndex: '" . str_replace(' ', '_', $meta_field['name']) . "' , width: 200 }\n");
						}
						else if( $meta_field['name'] =='부제' || $meta_field['name'] =='내용' || $meta_field['name'] == '비고' )
						{
							array_push($fields, "{header: '" . $meta_field['name'] . "', dataIndex: '" . str_replace(' ', '_', $meta_field['name']) . "' , width: 300 }\n");
						}
						else
						{
							array_push($fields, "{header: '" . $meta_field['name'] . "', dataIndex: '" . str_replace(' ', '_', $meta_field['name']) . "'}\n");
						}
					}
				}
				echo ', '.implode(', ', $fields);

			}*/
			?>
		]
	})
};
