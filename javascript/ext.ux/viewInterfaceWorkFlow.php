<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lang.php');

//print_r($_SESSION);
$workflow_id = $_REQUEST['workflow_id'];
$content_id = $_REQUEST['content_id'];
$root_task = $_REQUEST['root_task'];

$auto_height = $_POST['screen_height']*0.7;
$auto_width = $_POST['screen_width']*0.8;

?>

(function(){
	var store = new Ext.data.JsonStore({
		url: '/store/workFlowInterfaceStore.php',
		baseParams: {
			workflow_id: <?=$workflow_id?>,
			content_id: +<?=$content_id?>,
			root_task: '<?=$root_task?>'
		},
		root: 'data',
		fields: [
			'task_id',
			'job_name',
			'type',
			'type_nm',
			'status',
			'status_nm',
			'job_name',
            'progress',
            'source',
            'target',
			{name: 'creation_datetime', type: 'date', dateFormat: 'YmdHis'},
			{name: 'start_datetime', type: 'date', dateFormat: 'YmdHis'},
			{name: 'complete_datetime', type: 'date', dateFormat: 'YmdHis'}
		]
	});
	store.load();

	var win_workflow = new Ext.Window({
		id: 'taskInterfaceDetail',
		//title: '작업흐름 정보',
		title: _text('MN00277'),
		height: <?=$auto_height?>,
		minHeight : 600,
		width: <?=$auto_width?>,
		minWidth : 600,
		draggable : false,//prevent move
		maximizable : true,
		modal: true,
		border: false,
		autoScroll : true,
		layout: {
			type:'vbox',
			padding:'5',
			align:'stretch'
		},
		items: [{
			id: 'taskInterfaceList',
			cls: 'proxima_customize proxima_customize_progress',
			stripeRows: true,
			xtype: 'grid',
			loadMask: true,
			enableDD: false,
			store: store,
			//height: 300,
			flex : 1,
			//region: 'center',
			plain: true,

			selModel: new Ext.grid.RowSelectionModel({
				singleSelect: true,
				listeners: {
					rowselect: function(self){
                                                Ext.getCmp('taskInterfacelog').getStore().load();
					},
					rowdeselect: function(self){
						Ext.getCmp('taskInterfacelog').getStore().removeAll();
					}
				}
			}),
			listeners: {
				//rowdblclick: function(self){
				//	self.ownerCt.doSubmit(self.ownerCt);
				//}
			},
			cm: new Ext.grid.ColumnModel({
				defaults: {
					sortable: true,
					align: 'center'
				},
				columns: [
					//>>{header: '작업 ID',dataIndex: 'id', width: 60},
					//>>{header: '작업 유형', dataIndex: 'type_nm',  width: 90, hidden: true},
					//>>{header: '단위 작업명',	dataIndex: 'job_name},
					//>>{header: '작업상태',dataIndex: 'status', renderer: renderStatus},
					//>>{header: '등록일',	dataIndex: 'creation_datetime', renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s'), width: 120},
					//>>{header: '작업시작',dataIndex: 'start_datetime', renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s')},
					//>>{header: '작업종료',dataIndex: 'complete_datetime', renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s')}
					//>>{header: '저장경로', dataIndex: 'target', align: 'left', width: 400}
					{header: _text('MN00235'), dataIndex: 'task_id',hidden: true,  width: 60},
					{header: '작업 유형', dataIndex: 'type_nm',  width: 90, hidden: true},
					{header: _text('MN02138'), dataIndex: 'job_name', align: 'left',  width: 180},
                    new Ext.ux.ProgressColumn({
						header: _text('MN00261'),
						width: 95,
						dataIndex: 'progress',
						align: 'center',
						renderer: function(value, meta, record, rowIndex, colIndex, store, pct) {
							return Ext.util.Format.number(pct, "0%");
						}
					}),
					{header: _text('MN00138'), dataIndex: 'status', width: 80},
					{header: _text('MN00102'), dataIndex: 'creation_datetime', renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s'), width: 130},
					{header: _text('MN00233'), dataIndex: 'start_datetime', renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s'), width: 130},
					{header: _text('MN00234'), dataIndex: 'complete_datetime', renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s'), width: 130},
					{header: _text('MN00242'), dataIndex: 'target', align: 'left', width: 400}
				]
			}),
			tbar: [{
				//>>text: '새로고침',
				cls: 'proxima_button_customize',
				width: 30,
				text: '<span style="position:relative;top:1px;" title="'+_text('MN00139')+'"><i class="fa fa-refresh" style="font-size:13px;color:white;"></i></span>',
				//icon: '/led-icons/arrow_refresh.png',
				handler: function(){
					Ext.getCmp('taskInterfaceList').getStore().reload();
				},
				scope: this
			},{
				xtype: 'combo',
                                hidden: true,
				//id: 'search_f',
				width: 100,
				triggerAction: 'all',
				editable: false,
				mode: 'local',
				store: [
					['content_id', _text('MN00287')]
				],
				value: 'content_id'

			},{
				hidden: true,
				allowBlank: false,
				xtype: 'textfield',
				id: 'search_conid',
				listeners: {
					specialKey: function(self, e){
						var w = self.ownerCt.ownerCt;
						if (e.getKey() == e.ENTER && self.isValid())
						{
							e.stopEvent();
							w.doSearch(w.getTopToolbar(), this.store);
						}
					}
				}
			},' ' ,{
				xtype: 'button',
                                hidden: true,
				//>>text: '조회',
				text: _text('MN00047'),
				text: '<?=_text('MN00037')?>',
				handler: function(b, e){
					var w = b.ownerCt.ownerCt;
					w.doSearch(w.getTopToolbar(), this.store);
				}
			}],

			viewConfig: {
				forceFit: true,
				//>>emptyText: '결과값이 없습니다.',
				emptyText: '<?=_text('MSG00148')?>',
				listeners: {
						refresh: function(self) {
						Ext.getCmp('taskInterfacelog').getStore().removeAll();
					}
				}
			},

			doSearch: function(tbar, store){
				//var combo_value = tbar.get(0).getValue();
				var params = {};
				//console.log(tbar.get(2).getValue());
				params.search_field = tbar.get(2).getValue();
				params.search_value = tbar.get(3).getValue();


				if(Ext.isEmpty(params.search_value)){
					//>>Ext.Msg.alert('정보', '검색어를 입력해주세요.');
					Ext.Msg.alert(_text('MN00023'), _text('MSG00007'));
				}else{
					Ext.getCmp('taskInterfaceList').getStore().load({
						params: params
					});
				}
			}
		},{
			id: 'taskInterfacelog',
			//>>title:	"로그",
			title:	_text('MN00048'),
			xtype:	'grid',
			cls: 'proxima_customize',
			stripeRows: true,
			//region: 'south',
			split: true,
			collapsible: true,
			buttonAlign: 'center',
			//height: 300,
			flex : 1,
			loadMask: true,
			autoExpandColumn: 'description',
			store:	new	Ext.data.JsonStore({
				id:	'interface_log_store',
				url: '/store/get_task_log.php',
				totalProperty: 'total',
				idProperty:	'id',
				root: 'data',
				fields:	[
					{name: 'task_log_id'},
					{name: 'task_id'},
					{name: 'description'},
					{name: 'creation_date',	type: 'date', dateFormat: 'YmdHis'}
				],
				listeners: {
					beforeload:	function(self, opts){
						var	sel	= Ext.getCmp('taskInterfaceDetail').get(0).getSelectionModel().getSelected();
						self.baseParams.task_id	= sel.get('task_id');
					}
				}
			}),
			columns: [
				{header: 'ID', dataIndex: 'task_log_id',	width: 45},
				//>>{header: '생성일', dataIndex: 'creation_date', renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s'), width: 120, align: 'center'},
				//>>{header: '내용', dataIndex: 'description', id: 'description'}
				{header: _text('MN00107'), dataIndex: 'creation_date', renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s'), width: 120, align: 'center'},
				{header: _text('MN00156'), dataIndex:	'description', id: 'description'}
			],
			selModel: new Ext.grid.RowSelectionModel({
				singleSelect: true
			}),
			viewConfig: {
				//>>emptyText: '기록된 작업 내용이 없습니다.'
				emptyText: _text('MSG00166')
			},
			tbar: [{
				//text: '새로고침',
				cls: 'proxima_button_customize',
				width: 30,
				text: '<span style="position:relative;top:1px;" title="'+_text('MN00139')+'"><i class="fa fa-refresh" style="font-size:13px;color:white;"></i></span>',
				//icon: '/led-icons/arrow_refresh.png',
				handler: function(){
					Ext.getCmp('tasklog').getStore().reload();
				}
			}],
			buttons: [{
				text : '<span style="position:relative;top:1px;"><i class="fa fa-close" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00031'),
				scale: 'medium',
				handler: function(b, e){
					b.ownerCt.ownerCt.ownerCt.close();
				}
			}]
        }],
		listeners: {
			resize : function( self, width, height ){
			},
			afterrender : function(self){
				self.setWidth(<?=$auto_width?>);
				self.setHeight(<?=$auto_height?>);
			}
		}
	});
	//win.show();
	return win_workflow;

})()