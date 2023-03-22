<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lib.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
$meta_table_id = $_POST['panel_id'];

$broadcast = array(
	 array(
		'name'	=> 'Tape NO',
		'type'	=> 'string'
	 ),
	 array(
		'name'	=> '매체',
		'type'	=> 'string'
	 ),
	 array(
		'name'	=> '프로그램명',
		'type'	=> 'string',
		'width'	=> 200
	 ),
	 array(
		'name'	=> '부제',
		'type'	=> 'string',
		'width'	=> 300
	 ),
	 array(
		'name'	=> '방송일자',
		'type'	=> 'date'
	 ),
	 array(
		'name'	=> '연출자',
		'type'	=> 'string'
	 )
);

$tvinsert = array(
	 array(
		'name'	=> 'Tape NO',
		'type'	=> 'string'
	 ),
	 array(
		'name'	=> '자료명',
		'type'	=> 'string',
		'width'	=> 150
	 ),
	 array(
		'name'	=> '프로그램명',
		'type'	=> 'string',
		'width'	=> 200
	 ),
	 array(
		'name'	=> '부제',
		'type'	=> 'string',
		'width'	=> 300
	 ),
	 array(
		'name'	=> '취급구분',
		'type'	=> 'string'
	 ),
	 array(
		'name'	=> '연출자',
		'type'	=> 'string'
	 )
);

$reference = array(
	 array(
		'name'	=> 'Tape NO',
		'type'	=> 'string'
	 ),
	 array(
		'name'	=> '프로그램명',
		'type'	=> 'string',
		'width'	=> 200
	 ),
	 array(
		'name'	=> '부제',
		'type'	=> 'string',
		'width'	=> 300
	 ),
	 array(
		'name'	=> '방송일자',
		'type'	=> 'date'
	 ),
	 array(
		'name'	=> '연출자',
		'type'	=> 'string'
	 )
);

$fields = array();

array_push($fields, "{ name: 'created_time' , type: 'date', dateFormat: 'YmdHis' }");
array_push($fields, "{ name: 'meta_table_id' }");
array_push($fields, "{ name: 'user_id' }");
array_push($fields, "{ name: 'id'  }");
array_push($fields, "{ name: 'status' }");
array_push($fields, "{ name: 'tc_in' }");
array_push($fields, "{ name: 'tc_out' }");

$columns = array();

array_push($columns, " new Ext.grid.RowNumberer() ");
array_push($columns, " { header: '상태', dataIndex: 'status' , sortable:'true', renderer: mappingstatus} ");
//array_push($columns, " { header: 'TC_IN', dataIndex: 'tc_in' , sortable:'true' } ");
//array_push($columns, " { header: 'TC_OUT', dataIndex: 'tc_out' , sortable:'true' } ");

if( $meta_table_id == PRE_PRODUCE )
{
	$array_list = $broadcast;
}
else if( $meta_table_id == CLEAN )
{
	$array_list = $tvinsert;
}
else
{
	$array_list = $reference;
}


foreach($array_list as $list )
{
	if( $list['type'] == 'date' )
	{
		array_push($fields, "{ name: '".$list['name']."' , type: 'date', dateFormat: 'YmdHis' }");
		array_push($columns, "{ header: '".$list['name']."' , dataIndex: '".$list['name']."', sortable:'true' , renderer: Ext.util.Format.dateRenderer('Y-m-d') }");
	}
	else
	{
		if( !empty( $list['width'] ) )
		{
			array_push($fields, "{ name: '".$list['name']."'}");
			array_push($columns, "{ header: '".$list['name']."' , dataIndex: '".$list['name']."', sortable:'true' , width: ".$list['width']." }");
		}
		else
		{
			array_push($fields, "{ name: '".$list['name']."'}");
			array_push($columns, "{ header: '".$list['name']."' , dataIndex: '".$list['name']."', sortable:'true'  }");
		}
	}
}

array_push($columns, "{ header: '등록일자', dataIndex: 'created_time', sortable:'true', renderer: Ext.util.Format.dateRenderer('Y-m-d') }");



?>
(function(meta_table_id){
	return 	{
		xtype:'grid',
		id: 'ingest_list',
		loadMask: true,
		store: new Ext.data.JsonStore({
			url: '/pages/menu/config/ingest/ingest_store.php',
			id: 'ingest_store',
			totalProperty: 'total',
			root: 'data',
			autoLoad: true,
			sortInfo: {
				field: 'created_time',
				direction: 'DESC'
			},
			remoteSort: true,
			fields: [
				<?php
					echo $fields_list = join(', ' , $fields );
				?>
			],

			listeners: {
				beforeload: function(self, opts){
					opts.params = opts.params || {};

					Ext.apply(opts.params, {
						meta_table_id : meta_table_id,
						start_date: Ext.getCmp('start_date').getValue().format('Ymd000000'),
						end_date: Ext.getCmp('end_date').getValue().format('Ymd240000')
					});
				}
			}
		}),
		columns: [
			<?php
					echo $columns_list = join(', ' , $columns );
			?>
		],
		bbar: [
			new Ext.PagingToolbar({
				store: 'ingest_store',
				pageSize: Ariel.limit
			})
		],
		view: new Ext.ux.grid.BufferView({
			rowHeight: 20,
			scrollDelay: false,
			forceFit: true,
			getRowClass : function (r, rowIndex, rp, ds) {
				if( r.get('status') == '1' )
				{
					return 'complete'; //완료 상태
				}
				if( r.get('status') == '2' )
				{
					return 'progressing'; //진행 상태
				}
			},
			emptyText: '검색 결과가 없습니다.'
		}),
		listeners: {
			rowdblclick: function(self, idx, e){
				var record = self.getSelectionModel().getSelected();

				var ingest_id= record.get('id');

				self.viewtc(ingest_id);

			},
			rowcontextmenu: function(self,rowIndex,e){

				var cell = self.getSelectionModel();

				if (!cell.isSelected(rowIndex))
				{
					cell.selectRow(rowIndex);
				}

				e.stopEvent();
				self.contextmenu.showAt(e.getXY());
			}
		},
		contextmenu: new Ext.menu.Menu({
			items:[{
				hidden: true,
				text: '추가',
				icon: '/led-icons/folder_add.png'
			},{
				hidden: true,
				text: '수정',
				icon: '/led-icons/folder_edit.png'
			},{
				text: '삭제',
				icon: '/led-icons/folder_delete.png'
			}],
			listeners: {
				itemclick: function(item, e){

					if( item.text == '삭제' )
					{
						delete_list();
					}
				}
			}
		}),
		viewtc: function(id){
			Ext.Ajax.request({
				url: '/pages/menu/config/ingest/multi_list.php',
				params: {
					id: id
				},
				callback: function(options, success, response){
					if (success)
					{
						try
						{
							Ext.decode(response.responseText);
						}
						catch (e)
						{
							Ext.Msg.alert(e['name'], e['message']);
						}
					}
					else
					{
						Ext.Msg.alert( _text('MN01098'), response.statusText);//'서버 오류'
					}
				}
			});
		}
    }
})(<?=$meta_table_id?>)