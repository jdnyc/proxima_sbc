<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
?>
/*
#등록대기 = 0 (default)
#등록요청 = 1
#심의비대상(등록완료) = 2
#심의대기중 = 3
#승인 = 4
#반려 = 5
#조건부승인 = 6
*/

grideachcontentlist = {
	xtype: 'portal',
	layout: 'fit',
	id: 'content_list',
	border: false,

	items: [{
		border: false,
		layout: 'fit',
		//columnWidth: '1',

		items: [
		<?PHP
		$content_type_list = $db->queryAll("select * from bc_bs_content where bs_content_id = 506 order by show_order");
		while ($content_type = current($content_type_list))
		{
			//if($content_type['content_type_id'] == '515')continue;
		?>
			{
			id: 'content-type-<?=$content_type['bs_content_id']?>',
			content_type: <?=$content_type['bs_content_id']?>,
			xtype: 'contentgrid',
			title: '<?=$content_type['bs_content_title']?>',
			iconCls: 'icon-<?=$content_type['bs_content_id']?>',
			store: new Ext.data.JsonStore({
				url: '/store/get_content.php',
				idProperty: 'content_id',
				totalProperty: 'total',
				root: 'results',

				fields: [
					{name: 'category'},
					{name: 'content_id'},
					{name: 'is_hidden'},
					{name: 'created_time', type:'date', dateFormat: 'YmdHis'},
					{name: 'thumb'},
					{name: 'streaming'},
					{name: 'table_name'},
					{name: 'meta_table_id', type: 'int'},
					{name: 'status', type: 'int'},
					{name: 'content_type'},
					{name: 'content_type_id'},
					{name: 'title'},
					{name: 'summary'},
					<?php
					$tmp = array();
					$fields = array();
					$meta_field_list = $db->queryAll("select * 
														from bc_ud_content t, bc_usr_meta_field f 
														where t.bs_content_id={$content_type['bs_content_id']} 
														and t.ud_content_id=f.ud_content_id 
														order by f.show_order");

					foreach ($meta_field_list as $meta_field)
					{
						if (in_array($meta_field['usr_meta_field_title'], $tmp)) continue;

						array_push($tmp, $meta_field['usr_meta_field_title']);

						$field = '';
						if ($meta_field['usr_meta_field_type'] == 'datefield')
						{
							//echo $meta_field['name'].' '.$db->queryOne("select v.value from meta_field f, meta_value v where f.name='{$meta_field['name']}' and f.meta_field_id=v.meta_field_id")."\n";

							$field = "{name: '" . str_replace(' ', '_', $meta_field['usr_meta_field_title']) ."', type:'date', dateFormat: 'Y-m-d H:i:s'}\n";
						}
						else
						{
							$field = "{name: '" . str_replace(' ', '_', $meta_field['usr_meta_field_title']) . "'}\n";
						}

						array_push($fields, $field);
					}
					echo implode(', ', $fields);
					?>
				],
				remoteSort: true,
				sortInfo: {
					field: 'content_id',
					direction: 'desc'
				},
				baseParams: {
					task: 'listing',
					content_type: <?=$content_type['bs_content_id']?>,
					start: 0,
					limit: 20
				},
				listeners: {
					load: function(self){
						self.each(function(record){
							//console.log(record);
						});
					}
				}
			}),
			sm: new Ext.grid.CheckboxSelectionModel({
				listeners: {
					rowselect: function(self, idx, records){
						//airFunRemoveFilePath('all');
						//if (self.getCount() > 0)
						//{
						//	Ext.each(self.getSelections(), function(i){
						//		alert(i.get('file'));
						//		airFunAddFilePath(i.get('file'));
						//	});
						//}
					}
				}
			}),
			cm: new Ext.grid.ColumnModel({
				defaults: {
					sortable: true
				},
				columns: [
					new Ext.grid.CheckboxSelectionModel(),
					<?php
					$exists_list = array();
					$fields = array();
					foreach ($meta_field_list as $meta_field)
					{
						if (in_array($meta_field['usr_meta_field_title'], $exists_list)) continue;

						array_push($exists_list, $meta_field['usr_meta_field_title']);
						if($meta_field['usr_meta_field_type'] == 'datefield')
						{
							array_push($fields, "{header: '" . $meta_field['usr_meta_field_title'] . "', altFormats: 'Y-m-d|Y-m-d H:i:s', dataIndex: '" . str_replace(' ', '_', $meta_field['usr_meta_field_title']) ."', renderer: Ext.util.Format.dateRenderer('Y-m-d')}\n");
						}
						else
						{
							$width = '';
							if ($meta_field['usr_meta_field_title'] == '제목')
							{
								$width = ', width: 150';
							}
							array_push($fields, "{header: '" . $meta_field['usr_meta_field_title'] . "', dataIndex: '" . str_replace(' ', '_', $meta_field['usr_meta_field_title']) . "' $width}\n");
						}
					}
					echo implode(', ', $fields);
					?>
					,{header: '카테고리', dataIndex: 'category', sortable: false}
				]
			})
		}
		<?php
			if (next($content_type_list))
			{
				echo ',';
			}
			else
			{

			}
		}
		?>
		]
	}]
};
