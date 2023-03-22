<?php
/*
	2012. 07. 17 이승수
	viewWorkFlow.php에서 modal: false로 하기 위해 새로만듬
	모든 id에 content_id가 붙어서 고유값을 갖는다.

	이 window는 EBS DMC에서 주조로 전송할 시 쓰이는 창. 전송작업만 보여주게 된다.(최근작업 하나)
*/
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lang.php');

//print_r($_SESSION);
$records = $_POST['records'];
$arr_content_id = json_decode($records, true);


?>

(function(records){
	var store = new Ext.data.JsonStore({
		url: '/store/workFlowStore.php?content_id='+records,
		root: 'data',
		fields: [
			'id',
			'type',
			'status',
			'source',
			'target',
			'progress',
			'task_user_id',
			'destination',
			{name: 'creation_datetime', type: 'date', dateFormat: 'YmdHis'},
			{name: 'start_datetime', type: 'date', dateFormat: 'YmdHis'},
			{name: 'complete_datetime', type: 'date', dateFormat: 'YmdHis'}
		],
		sortInfo: {
			field: 'creation_datetime',
			direction: 'DESC'
		}
	});
	store.load();

	var win = new Ext.Window({
		id: 'taskDetail'+records,
		//>>title: '콘텐츠 작업흐름 정보 - '+records ,
		title: _text('MN00277')+ ' - ' +records ,
		//modal: false,
		collapsible: true,
		maximizable: true,
		width: 850,
		height: 600,
		layout:	'border',
		border: false,
		listeners: {
			beforeclose: function(self){
				Ext.getCmp('taskList'+records).stopAutoReload();
			}
		},
		items: [{
			id: 'taskList'+records,
			xtype: 'grid',
			loadMask: true,
			enableDD: false,
			store: store,
			height: 300,
			region: 'center',
			plain: true,
			intervalID: null,
			selModel: new Ext.grid.RowSelectionModel({
				singleSelect: true,
				listeners: {
					rowselect: function(self){
						Ext.getCmp('tasklog'+records).getStore().load();
					},
					rowdeselect: function(self){
						Ext.getCmp('tasklog'+records).getStore().removeAll();
					}
				}
			}),
			runAutoReload: function(thisRef){
				this.stopAutoReload();

				this.intervalID = setInterval(function (e) {
					if (thisRef) {
						store.reload();
					}
				}, 5000);
			},

			stopAutoReload: function(){
				if (this.intervalID) {
					clearInterval(this.intervalID);
				}
			},
			listeners: {
				afterrender: function(self){
					self.runAutoReload(self);
					//console.log('afterrender');
				}
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
					{header: '작업ID', dataIndex: 'id', width: 60},
					{header: '작업유형', dataIndex: 'type', hidden: true},
					new Ext.ux.ProgressColumn({
							header: _text('MN00261'),
							width: 105,
							dataIndex: 'progress',
							//divisor: 'price',
							align: 'center',
							renderer: function(value, meta, record, rowIndex, colIndex, store, pct) {
								return Ext.util.Format.number(pct, "0%");
							}
					}),
					{header: '상태', dataIndex: 'status', renderer: renderStatus, width: 50},
					{header: _text('MN00220'), dataIndex: 'source', width: 250, align: 'center'},
					{header: _text('MN00242'), dataIndex: 'target', width: 130, align: 'center'},
					{header: '서버', dataIndex: 'destination', width: 60, align: 'center'},
					{header: _text('MN00102'), dataIndex: 'creation_datetime', width: 140, renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s')},
					{header: _text('MN00233'), dataIndex: 'start_datetime', width: 140, renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s')},
					{header: _text('MN00234'), dataIndex: 'complete_datetime', width: 140, renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s')}
				]
			}),
			tbar: [{
				//>>text: '새로고침',
				text: _text('MN00139'),
				icon: '/led-icons/arrow_refresh.png',
				handler: function(){
					Ext.getCmp('taskList'+records).getStore().reload();
				},
				scope: this
			}],

			viewConfig: {
				//>>emptyText: '결과값이 없습니다.',
				emptyText: _text('MSG00148'),
				listeners: {
						refresh: function(self) {
						Ext.getCmp('tasklog'+records).getStore().removeAll();
					}
				}
			}
		},
		{
			id: 'tasklog'+records,
			//>>title:	"로그",
			title:	_text('MN00048'),
			xtype:	'grid',
			region: 'south',
			split: true,
			collapsible: true,
			height: 300,
			loadMask: true,
			//autoExpandColumn: 'description',
			store:	new	Ext.data.JsonStore({
				id:	'log_store'+records,
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
						var	sel	= Ext.getCmp('taskDetail'+records).get(0).getSelectionModel().getSelected();
						self.baseParams.task_id	= sel.get('id');
					}
				}
			}),
			columns: [
				{header: 'ID', dataIndex: 'task_log_id',	width: 55},
				{header: '생성일', dataIndex: 'creation_date', renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s'), width: 120, align: 'center'},
				//{header: '내용', dataIndex:	'description', id: 'description'}
				{header: '내용', dataIndex:	'description', width: 300}
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
				text: _text('MN00139'),
				icon: '/led-icons/arrow_refresh.png',
				handler: function(){
					Ext.getCmp('tasklog'+records).getStore().reload();
				}
			}],
			buttons: [{
				//>>text: '닫기',
				text: _text('MN00031'),
				handler: function(b, e){
					b.ownerCt.ownerCt.ownerCt.close();
				}
			}]
		}
		]
	});
	win.show();


function renderStatus(value){
	switch(value){
		case 'complete':
			//>>value = '성 공';
			value = _text('MN00011');
		break;

		case 'down_queue':
		case 'watchFolder':
		case 'queue':
			//>>value = '대 기';
			value = _text('MN00039');
		break;

		case 'error':
			//>>value = '실 패';
			value = _text('MN00012');
		break;

		case 'processing':
			//>>value = '처리중';
			value = _text('MN00262');
		break;

		case 'cancel':
			//>>value = '취소 대기중';
			value = _text('MN00004');
		break;

		case 'canceling':
			//>>value = '취소 중';
			value = _text('MN00004');
		break;

		case 'canceled':
			//>>value = '취소됨';
			value = _text('MN00004');
		break;

		case 'retry':
			//>>value = '재시작';
			value = _text('MN00006');
		break;

		case 'regiWait':
			//>>value = '등록 대기중';
			value = _text('MN00360');
		break;
	}
	return value;
}

function renderTypeName(value){
	switch(value){
		case '10':
			//>>value = '카탈로깅';
			value = _text('MN00270');
		break;

		case '20':
			//>>value = '트랜스코딩';
			value = _text('MN00298');
		break;

		case '60':
			//>>value = '인제스트전송';
			value = _text('MN00226');
		break;

		case '11':
			value = '저해상도 영상캡쳐';
		break;

		case '80':
			value = '전송';
		break;

		case '15':
			value = 'QC';
		break;

		case 'archive':
			value = 'Archive';
		break;

		case 'restore':
			value = 'Restore';
		break;

		case 'pfr_restore':
			value = 'PFR Restore';
		break;
	}
	return value;
}

})(<?=$records?>)