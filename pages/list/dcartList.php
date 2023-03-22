<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lang.php');

?>
//////sound 그리드//////


var dCart_size = 35;

var dCart_store = new Ext.data.JsonStore({
		url: '/store/get_dcart.php',
		root: 'data',
		totalProperty: 'total',
		fields:
		[
			{name: 'title'},
			{name: 'contentsType'},
			{name: 'created_time', type: 'date', dateFormat: 'YmdHis'},
			{name: 'status'},
			{name: 'content_id'},
			{name: 'content_type_id'},
			{name: 'meta_table_id'},
			{name: 'name'}
			<?php
			$fields = $db->queryAll("select * from bc_usr_meta_field where ud_content_id='4023846' and usr_meta_field_type!='container'");
			foreach ($fields as $field)
			{
				if($field['usr_meta_field_type'] == 'datefield')
				{
					$tmp[] = "{name: '".$field['usr_meta_field_title']."'}";
				}
				else
				{
					$tmp[] = "{name: '".$field['usr_meta_field_title']."'}";
				}
			}

			if ($tmp)
			{
				echo ', '.join(', ', $tmp);
			}
			?>
		]
});


var ost_store = new Ext.data.JsonStore({
		url: '/store/get_dcart.php',
		root: 'data',
		totalProperty: 'total',
		fields:
		[
			{name: 'title'},
			{name: 'contentsType'},
			{name: 'created_time', type: 'date', dateFormat: 'YmdHis'},
			{name: 'status'},
			{name: 'content_id'},
			{name: 'content_type_id'},
			{name: 'meta_table_id'},
			{name: 'name'}
			<?php
			$fields_ost = $db->queryAll("select * from bc_usr_meta_field where ud_content_id='81769' and usr_meta_field_type!='container'");
			foreach ($fields_ost as $field)
			{
				if($field['usr_meta_field_type'] == 'datefield')
				{
					$tmp_ost[] = "{name: '".$field['usr_meta_field_title']."'}";
				}
				else
				{
					$tmp_ost[] = "{name: '".$field['usr_meta_field_title']."'}";
				}
			}

			if ($tmp_ost)
			{
				echo ', '.join(', ', $tmp_ost);
			}
			?>
		]
});

var dcartList = new Ext.grid.GridPanel({
	id:'radio',
	title:'R.방송프로그램',
	frame: true,
	store: dCart_store,
	loadMask: true,
	sm: new Ext.grid.RowSelectionModel({
			singleSelect: true
	}),
	columns: [
		new Ext.grid.RowNumberer(),
		<?php
		$header_list = $db->queryAll("select f.usr_meta_field_type, f.usr_meta_field_title, f.usr_meta_field_id
										from bc_ud_content t, bc_usr_meta_field f
										where t.ud_content_id='4023846'
										and f.usr_meta_field_type != 'container'
										and t.ud_content_id=f.ud_content_id
										order by f.show_order");
		$fields = array();
		foreach ($header_list as $header)
		{
			if($meta_field['usr_meta_field_type'] == 'datefield')
			{
				array_push($fields, "{header: '".$header['usr_meta_field_title']."', dataIndex: '".$header['usr_meta_field_title']."'}\n");
			}
			else
			{
				if( $header['name'] =='프로그램명' )
				{
					array_push($fields, "{header: '".$header['usr_meta_field_title']."', dataIndex: '".$header['usr_meta_field_title']."', width: 200 }\n");
				}
				else if( $header['name'] =='부제' || $header['usr_meta_field_title'] =='주요내용' || $header['usr_meta_field_title'] == '비고' )
				{
					array_push($fields, "{header: '".$header['usr_meta_field_title']."', dataIndex: '".$header['usr_meta_field_title']."', width: 300}\n");
				}
				else
				{
					array_push($fields, "{header: '".$header['usr_meta_field_title']."', dataIndex: '".$header['usr_meta_field_title']."'}\n");
				}

			}
		}
		echo implode(', ', $fields);
		?>
	],

	listeners:{

		afterrender: function(self){
			dCart_store.load({
				params:{
					start: 0,
					limit: dCart_size,
					meta_table_id: 4023846,
					register: 'radio'
				}
			});
		},
		rowcontextmenu: function(self,rowIndex,e){
			/*
			var cell = self.getSelectionModel();

			if (!cell.isSelected(rowIndex))
			{
				cell.selectRow(rowIndex);
			}

			e.stopEvent();
			self.contextmenu.showAt(e.getXY());
			*/
		},
			rowdblclick: function(self, idx, e){
				var record = self.getSelectionModel().getSelected();
				var id= record.get('content_id');
				var content_type_id = record.get('content_type_id');
				var meta_table_id = record.get('meta_table_id');
				var type = 'dcart';
				Ext.Ajax.request({
					url: '/javascript/ext.ux/Ariel.DetailWindow.php',
					params: {
						content_id: id,
						type: type
					},
					callback: function(self, success, response){
						if (success)
						{
							try
							{
								Ext.decode(response.responseText);
							}
							catch (e)
							{
								Ext.Msg.alert(e['name'], e['message'] );
							}
						}
						else
						{
							Ext.Msg.alert('서버 오류', response.statusText+'('+response.status+')');
						}
					}
				});

			}
	},
	viewConfig: {
		//>>emptyText: '등록된 자료가 없습니다.'
		emptyText: '<?=_text('MSG00142')?>'
	}
	,bbar : new Ext.PagingToolbar({
		store: dCart_store,
		pageSize: dCart_size
	})

});

var RecordList = new Ext.grid.GridPanel({
	id:'ost',
	title:'음반',
	frame: true,
	store: ost_store,
	loadMask: true,
	sm: new Ext.grid.RowSelectionModel({
			singleSelect: true
	}),
	columns: [
		new Ext.grid.RowNumberer(),
		<?php
		$header_list = $db->queryAll("select f.usr_meta_field_type, f.usr_meta_field_title, f.usr_meta_field_id
											from bc_ud_content t, bc_usr_meta_field f
											where t.ud_content_id='81769'
											and f.depth=1
											and t.ud_content_id=f.ud_content_id
											order by f.show_order");
		$fields_ost = array();
		foreach ($header_list as $header)
		{
			if($meta_field['usr_meta_field_type'] == 'datefield')
			{
				array_push($fields_ost, "{header: '".$header['usr_meta_field_title']."', dataIndex: '".$header['usr_meta_field_title']."' }\n");
			}
			else
			{
				if( $header['usr_meta_field_title'] =='프로그램명' )
				{
					array_push($fields_ost, "{header: '".$header['usr_meta_field_title']."', dataIndex: '".$header['usr_meta_field_title']."', width: 200 }\n");
				}
				else if( $header['usr_meta_field_title'] =='부제' || $header['usr_meta_field_title'] =='주요내용' || $header['usr_meta_field_title'] == '비고' )
				{
					array_push($fields_ost, "{header: '".$header['usr_meta_field_title']."', dataIndex: '".$header['usr_meta_field_title']."', width: 300}\n");
				}
				else
				{
					array_push($fields_ost, "{header: '".$header['usr_meta_field_title']."', dataIndex: '".$header['usr_meta_field_title']."'}\n");
				}
			}
		}
		echo implode(', ', $fields_ost);
		?>
	],

	listeners:{
		afterrender: function(self){
			ost_store.load({
				params:{
					start: 0,
					limit: dCart_size,
					meta_table_id: '81769',
					register: 'ost'
				}
			});
		},
		rowcontextmenu: function(self,rowIndex,e){
		/*
			var cell = self.getSelectionModel();

			if (!cell.isSelected(rowIndex))
			{
				cell.selectRow(rowIndex);
			}

			e.stopEvent();
			self.contextmenu.showAt(e.getXY());
			*/
		},
			rowdblclick: function(self, idx, e){
				var record = self.getSelectionModel().getSelected();
				var id= record.get('content_id');
				var content_type_id = record.get('content_type_id');
				var meta_table_id = record.get('meta_table_id');
				var type = 'dcart';
				Ext.Ajax.request({
					url: '/javascript/ext.ux/Ariel.DetailWindow.php',
					params: {
						content_id: id,
						//content_type_id: content_type_id,
						//meta_table_id: meta_table_id,
						type: type
					},
					callback: function(self, success, response){
						if (success)
						{
							try
							{
								Ext.decode(response.responseText);
							}
							catch (e)
							{
								Ext.Msg.alert(e['name'], e['message'] );
							}
						}
						else
						{
							Ext.Msg.alert('서버 오류', response.statusText+'('+response.status+')');
						}
					}
				});

			}
	},
	viewConfig: {
		//>>emptyText: '등록된 자료가 없습니다.'
		emptyText: '<?=_text('MSG00142')?>'
	}
	,bbar : new Ext.PagingToolbar({
		store: ost_store,
		pageSize: dCart_size
	})

});