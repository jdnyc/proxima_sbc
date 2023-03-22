<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lang.php');

//print_r($_SESSION);
$records = $_POST['records'];

?>

(function(records){
	var store = new Ext.data.JsonStore({
		url: '/store/workFlowStore.php?content_id='+records,
		root: 'data',
		fields: [
			'id',
			'type',
			'status',
            'user_task_name',
			{name: 'creation_datetime', type: 'date', dateFormat: 'YmdHis'},
			{name: 'start_datetime', type: 'date', dateFormat: 'YmdHis'},
			{name: 'complete_datetime', type: 'date', dateFormat: 'YmdHis'}
		]
	});
	store.load();
	
	var win = new Ext.Window({
		id: 'taskDetail',
		//>>title: '콘텐츠 작업흐름 정보 - '+records ,
		title: _text('MN00277')+ ' - ' +records ,
		modal: true,
		width: 850,
		height: 600,
		layout:	'border',
		border: false,

		items: [{
			id: 'taskList',
			cls: 'proxima_customize',
			stripeRows: true,
			//border: false,
			xtype: 'grid',
			loadMask: true,
			enableDD: false,
			store: store,
			height: 300,
			region: 'center',
			plain: true,

			selModel: new Ext.grid.RowSelectionModel({
				singleSelect: true,
				listeners: {
					rowselect: function(self){
						Ext.getCmp('tasklog').getStore().load();
					},
					rowdeselect: function(self){
						Ext.getCmp('tasklog').getStore().removeAll();
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
					//>>{header: '작업코드',dataIndex: 'type', hidden: true, width: 40},
					//>>{header: '작업명',	dataIndex: 'type', renderer: renderTypeName},
					//>>{header: '작업상태',dataIndex: 'status', renderer: renderStatus},
					//>>{header: '등록일',	dataIndex: 'creation_datetime', renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s')},
					//>>{header: '작업시작',dataIndex: 'start_datetime', renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s')},
					//>>{header: '작업종료',dataIndex: 'complete_datetime', renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s')}
					{header: _text('MN00235'), dataIndex: 'id', width: 60},
                    {header: _text('MN01112'), dataIndex: 'user_task_name'},
					{header: _text('MN00069'), dataIndex: 'type', hidden: true, width: 40},
					{header: _text('MN00236'), dataIndex: 'type', renderer: renderTypeName},
					{header: _text('MN00138'), dataIndex: 'status', renderer: renderStatus},
					{header: _text('MN00102'), dataIndex: 'creation_datetime', renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s')},
					{header: _text('MN00233'), dataIndex: 'start_datetime', renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s')},
					{header: _text('MN00234'), dataIndex: 'complete_datetime', renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s')}
				]
			}),
			tbar: [{
				//>>text: '새로고침',
				//text: _text('MN00139'),
				//icon: '/led-icons/arrow_refresh.png',
				cls: 'proxima_button_customize',
				width: 30,
				text: '<span style="position:relative;top:1px;" title="'+_text('MN00139')+'"><i class="fa fa-refresh" style="font-size:13px;color:white;"></i></span>',
				handler: function(){
					Ext.getCmp('taskList').getStore().reload();
				},
				scope: this
			}
			,{
				hidden: true,
				xtype: 'combo',
				id: 'search_f',
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
			},' '
			,{
				hidden: true,
				xtype: 'button',
				//>>text: '조회',
				text: _text('MN00047'),
				text: '<?=_text('MN00037')?>',
				handler: function(b, e){
					var w = b.ownerCt.ownerCt;
					w.doSearch(w.getTopToolbar(), this.store);
				}
			},
			'작업명 : ',
			{
				xtype: 'combo',
				id:this.typeComboId,
				width: 130,
				triggerAction: 'all',
				editable: false,
				mode: 'local',
				valueField: 'value',
				displayField: 'name',
				store: new Ext.data.SimpleStore({
					fields: ['name', 'value'],
					data: [
						["전체","All"],
						['전송',"60"],
						["미디어정보 추출","130"],
						["영상 트랜스코딩","20"],
						['오디오 트랜스코딩',"70"],
						['영상 카탈로깅',"10"],
						['대표이미지 생성',"1"],
						['영상 QC',"15"],
						["라우드니스 측정","50"]
					]
				}),
				value:'All',
			},
			'상태 : ',
			{
				xtype: 'combo',
				id:this.typeComboId,
				width: 100,
				triggerAction: 'all',
				editable: false,
				mode: 'local',
				valueField: 'value',
				displayField: 'name',
				store: new Ext.data.SimpleStore({
					fields: ['name', 'value'],
					data: [
						["전체","All"],
						["성공","complete"],
						["처리중","processing"],
						["실패","error"],
						["무시","skip"],
						["등록 대기","regiWait"]
					]
				}),
				value:'All',
			},' ',{
				xtype: 'button',
				//>>text: '조회',
				text: _text('MN00047'),
				text: '<?=_text('MN00037')?>',
				handler: function(b, e){
					var w = b.ownerCt.ownerCt;
					w.newDoSearch(w.getTopToolbar(),this.store);
				}
			}],

			viewConfig: {
				forceFit: true,
				//>>emptyText: '결과값이 없습니다.',
				emptyText: '<?=_text('MSG00148')?>',
				listeners: {
						refresh: function(self) {
						Ext.getCmp('tasklog').getStore().removeAll();
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
					Ext.getCmp('taskList').getStore().load({
						params: params
					});

				}
			},
			newDoSearch: function(tbar,store){
      
				var params = {};
           
				var typeComboValue = tbar.get(6).getValue();
				if(!(typeComboValue == 'All')){
					params.search_type = typeComboValue;
				}
				var statusComboValue = tbar.get(8).getValue();
				if(!(statusComboValue == 'All')){
					params.search_status = statusComboValue;
				}
				
				<!-- console.log(tbar.get(6).getValue()); -->
				
				Ext.getCmp('taskList').getStore().load({
					params: params
				});

				
			}
		},
		{
			id: 'tasklog',
			//>>title:	"로그",
			title:	_text('MN00048'),
			xtype:	'grid',
			cls: 'proxima_customize',
			//border: false,
			region: 'south',
			split: true,
			collapsible: true,
			height: 300,
			loadMask: true,
			buttonAlign: 'center',
			autoExpandColumn: 'description',
			store:	new	Ext.data.JsonStore({
				id:	'log_store',
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
						//console.log(Ext.getCmp('taskDetail'));
						var	sel	= Ext.getCmp('taskDetail').get(0).getSelectionModel().getSelected();
						self.baseParams.task_id	= sel.get('id');
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
				emptyText: _text('MSG00166')
			},
			tbar: [{
				cls: 'proxima_button_customize',
				width: 30,
				text: '<span style="position:relative;top:1px;" title="'+_text('MN00139')+'"><i class="fa fa-refresh" style="font-size:13px;color:white;"></i></span>',
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

		case 'skip':
			value = '무시';
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