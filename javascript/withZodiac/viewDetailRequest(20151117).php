<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lang.php');

//print_r($_SESSION);
$records = $_POST['records'];
$workflow_id = $_REQUEST['workflow_id'];
$content_id = $_REQUEST['content_id'];
?>

(function(records){
	var store_article = new Ext.data.JsonStore({
		url: '/store/request_zodiac/request_list.php',
		totalProperty: 'total',
		idProperty: 'request_id',
		autoLoad : true,
		root: 'data',
		fields: [
			{ name : 'request_id' },
			{ name : 'dept_code' },
			{ name : 'article_id' },
			{ name : 'request_title' },
			{ name : 'request_content' },
			{ name : 'send_sms' },
			{ name : 'status' },
			{ name : 'return_comment' },
			{ name : 'update_user' },
			{ name : 'update_date', type:'date', dateFormat:'YmdHis' },
			{ name : 'create_user' },
			{ name : 'create_user_name' },
			{ name : 'create_date' , type:'date', dateFormat:'YmdHis' },
			{ name : 'comments' }
		],
		listeners: {
			beforeload: function(self, opts){
				opts.params = opts.params || {};

				Ext.apply(opts.params, {
					action : 'list'
				});
			}
		}
	});

	var win = new Ext.Window({
		id: 'requestDetail',
		//>>title: '콘텐츠 작업흐름 정보 - '+records ,
		title: '영상 편집 의뢰',
		modal: true,
		width: '80%',
		height: 600,
		minWidth: 900,
		layout:	'vbox',
		border: false,

		items: [{
			xtype: 'form',
			frame: true,
			flex : 1,
			height: 50,
			border: false,
			buttonAlign : 'center',
			labelWidth: 1,
			defaults: {
				labelStyle: 'text-align:center;',
				anchor: '95%'
			},
			autoScroll: true,
			items:[{
				xtype: 'compositefield',
				items:[{
					xtype: 'displayfield',
					flex:0.8,
					value: '*업무부서'
				},{
					xtype:'combo',
					flex:2,
					displayField:'name',
					valueField: 'value',
					typeAhead: true,
					triggerAction: 'all',
					lazyRender:true,
					mode: 'local',
					value: 'all',
					store: new Ext.data.ArrayStore({
							id: 0,
							fields: [
									'name',
									'value'
							],
							data: [['전체', 'all'], ['720px 이하', 'low'], ['720 ~ 1920', 'center'], ['1920px 이상', 'high']]
					})
				},{
					xtype: 'displayfield',
					flex:0.5,
					value: '&nbsp;'
				},{
					xtype: 'displayfield',
					flex:0.8,
					value: '*업무팀'
				},{
					xtype: 'combo',
					flex:2,
					triggerAction: 'all',
					editable: false,
					mode: 'local',
					store: new Ext.data.JsonStore({
						autoLoad: true,
						url: '/store/request_zodiac/request_list.php',
						baseParams: {
							action : 'dept'
						},
						root: 'data',
						idProperty: 'module_info_id',
						fields: [
							{name: 'name', type: 'string'},
							{name: 'value', type: 'int'}
						]
					}),
					mode: 'remote',
					hiddenName: 'name',
					hiddenValue: 'value',
					valueField: 'value',
					displayField: 'name',
					typeAhead: true,
					triggerAction: 'all',
					forceSelection: true,
					editable: false,
					value : '전체',
					listeners: {
						select: function (cmb, record, index) {
						}
					}
				},{
					xtype: 'displayfield',
					flex:5,
					value: '&nbsp;'
				}]
			}],
			buttons:[]
		},{
			title : '기사목록',
			xtype: 'grid',
			loadMask: true,
			enableDD: false,
			store: store_article,
			height: 100,
			flex: 3,
			plain: true,
			selModel: new Ext.grid.RowSelectionModel({
				singleSelect: true,
				listeners: {
					rowselect: function(self){
					},
					rowdeselect: function(self){
					}
				}
			}),
			view: new Ext.ux.grid.BufferView({
				scrollDelay: false,
				forceFit : true,
				emptyText: '데이터가 없습니다.'
			}),
			listeners: {
			},
			cm: new Ext.grid.ColumnModel({
				defaults: {
					sortable: true,
					align: 'center'
				},
				columns: [
					{header: 'ID', dataIndex: 'request_id', width: 60, hidden: true},
					{header: '구분', dataIndex: 'request_id', width: 60},
					{header: '업무 의뢰일', dataIndex: 'create_date', align: 'center', renderer: Ext.util.Format.dateRenderer('Y-m-d'), width: 120},
					{header: '업무부서', dataIndex: 'dept_code', width: 120},
					{header: '업무자', dataIndex: 'create_user_name', width: 60},
					{header: '업무상태', dataIndex: 'status', width: 60},
					{header: '제목', dataIndex: 'request_title', width: 120},
					{header: '요청자', dataIndex: 'create_user_name', width: 60},
					{header: '업무 의뢰 내역', dataIndex: 'request_content', width: 80},
					{header: '업무자 코멘트', dataIndex: 'comments', width: 60}
				]
			})
		},{
			xtype: 'form',
			frame: true,
			flex : 1,
			height: 120,
			border: false,
			buttonAlign : 'center',
			labelWidth: 100,
			padding: 5,
			defaults: {
				anchor: '99%'
			},
			autoScroll: true,
			items:[{
				xtype: 'textfield',
				fieldLabel : '*제목'
			},{
				xtype : 'textarea',
				fieldLabel : '*업무의뢰내역'
			}],
			buttons:[]
		},{
			title : '비디오',
			xtype: 'editorgrid',
			loadMask: true,
			enableDD: false,
			store: store_article,
			clicksToEdit: true,
			height: 150,
			flex: 3,
			plain: true,
			tbar: [{
				text : '추가',
				icon : '/led-icons/add.png',
				handler: function(b,e){
					var grid = b.ownerCt.ownerCt;
					var Record = grid.getStore().recordType;
					var r = new Record({
						'type':'',
						'requestymd':'',
						'dept':'',
						'user':''
					});
					r.markDirty();
					grid.stopEditing();
					grid.getStore().insert(grid.getStore().getCount(), r);
				}
			},{
				text : '삭제',
				icon : '/led-icons/cross.png',
				handler: function(b,e){
					var grid = b.ownerCt.ownerCt;
					//var sel = grid.getSelectionModel().getSelected();
					var sel = grid.getSelectionModel().getSelections();
					var Record = grid.getStore().recordType;
					if(Ext.isEmpty(sel)) {
						Ext.Msg.alert('알림','목록을 선택하세요.');
						return;
					}else{
						var requestIds = new Array();
						Ext.each(sel,function(r){
							if(!Ext.isEmpty(r.get('request_id'))){
								requestIds.push(r.get('request_id'));
							}
						});

						Ext.Ajax.request({
							url: '/store/request_zodiac/edit_request.php',
							params: {
								grid_name : 'video',
								action : 'delete',
								values : Ext.encode(requestIds)
							},
							callback: function(opts, success, response){
								if (success){
									try{
										var r = Ext.decode(response.responseText);
										if (r.success){
											grid.getStore().reload();
										}
										else{
											Ext.Msg.alert('알림', r.msg);
										}
									}
									catch(e){
										Ext.Msg.alert(e['title'], e['message']);
									}
								}else{
									Ext.Msg.alert('서버통신오류', response.statusText);
								}
							}
						});
						grid.getStore().reload();
					}
				}
			},'->',{
				text : '저장',
				icon : '/led-icons/accept.png',
				handler: function(b,e){
					var grid = b.ownerCt.ownerCt;
					var newVal = new Array();
					Ext.each(grid.getStore().getRange(),function(r){
						if( !Ext.isEmpty(r.data.request_id) && !Ext.isEmpty(r.data.create_date)  && !Ext.isEmpty(r.data.dept_code) && !Ext.isEmpty(r.data.create_user_name )){
							newVal.push({
								type : r.data.request_id,
								requestymd : r.data.create_date.format('Y-m-d'),
								dept : r.data.dept_code,
								user : r.data.create_user_name
							});

							Ext.Ajax.request({
								url: '/store/request_zodiac/edit_request.php',
								params: {
									grid_name : 'video',
									action : 'save',
									values : Ext.encode(newVal)
								},
								callback: function(opts, success, response){
									if (success){
										try{
											var r = Ext.decode(response.responseText);
											if (r.success){
												grid.getStore().reload();
											}
											else{
												Ext.Msg.alert('알림', r.msg);
											}
										}
										catch(e){
											Ext.Msg.alert(e['title'], e['message']);
										}
									}else{
										Ext.Msg.alert('서버통신오류', response.statusText);
									}
								}
							});

							grid.getStore().commitChanges();
						}else{
							Ext.Msg.alert('알림','데이터를 입력하세요.');
							return;
						}
					});
				}
			},{
				text : '취소',
				icon : '/led-icons/arrow_undo.png',
				handler: function(b,e){
					var grid = b.ownerCt.ownerCt;
					grid.getStore().reload() ;
				}
			}],
			selModel: new Ext.grid.RowSelectionModel({
				singleSelect: false,
				listeners: {
					rowselect: function(self){
					},
					rowdeselect: function(self){
					}
				}
			}),
			view: new Ext.ux.grid.BufferView({
				scrollDelay: false,
				forceFit : true,
				emptyText: '데이터가 없습니다.'
			}),
			listeners: {
				render: function(self){
					self.store.load({
						params: {
							content_id: '".$content_id."',
							usr_meta_field_id : '$meta_field_id',
							type: 'json'
						}
					});
				}
			},
			columns: [
				{header: '구분', dataIndex: 'request_id',width:70, editor: {
				xtype: 'combo',
				name: 'type',
				mode: 'local',
				displayField: 'name',
				valueField: 'name',
				triggerAction: 'all',
				editable: false,
				allowBlank: false,
				store: new Ext.data.JsonStore({
					fields : ['value', 'name'],
					data   : [
						{value: '1', name: '1'},
						{value: '2', name: '2'},
						{value: '3', name: '3'},
						{value: '4', name: '4'}						]
				})}},
				{header: '의뢰일', dataIndex: 'create_date',width:82, editor: {
					xtype: 'datefield',
					name: 'requestymd',
					editable: true,
					format: 'Y-m-d',
					allowBlank: false,
					altFormats: 'Y-m-d'
				}, renderer: Ext.util.Format.dateRenderer('Y-m-d') },
				{header: '업무부서', dataIndex: 'dept_code',width:75, editor: {
					xtype: 'textfield',
					name: 'dept',
					allowBlank: false,
					editable: true
				}},
				{header: '업무자', dataIndex: 'create_user_name',width:60, editor: {
					xtype: 'textfield',
					name: 'user',
					allowBlank: false,
					editable: true
				}}
			]
		},{
			title : 'EDL',
			xtype: 'grid',
			loadMask: true,
			enableDD: false,
			store: store_article,
			height: 100,
			flex: 3,
			plain: true,

			selModel: new Ext.grid.RowSelectionModel({
				singleSelect: true,
				listeners: {
					rowselect: function(self){
					},
					rowdeselect: function(self){
					}
				}
			}),
			view: new Ext.ux.grid.BufferView({
				scrollDelay: false,
				forceFit : true,
				emptyText: '데이터가 없습니다.'
			}),
			listeners: {
			},
			cm: new Ext.grid.ColumnModel({
				defaults: {
					sortable: true,
					align: 'center'
				},
				columns: [
					{header: 'ID', dataIndex: 'request_id', width: 60, hidden: true},
					{header: '구분', dataIndex: 'request_id', width: 60},
					{header: '업무 의뢰일', dataIndex: 'create_date', align: 'center', renderer: Ext.util.Format.dateRenderer('Y-m-d'), width: 120},
					{header: '업무부서', dataIndex: 'dept_code', width: 120},
					{header: '업무자', dataIndex: 'create_user_name', width: 60},
					{header: '업무상태', dataIndex: 'status', width: 60},
					{header: '제목', dataIndex: 'request_title', width: 120},
					{header: '요청자', dataIndex: 'create_user_name', width: 60},
					{header: '업무 의뢰 내역', dataIndex: 'request_content', width: 80},
					{header: '업무자 코멘트', dataIndex: 'comments', width: 60}
				]
			})
		},{
			title : '첨부파일',
			xtype: 'grid',
			loadMask: true,
			enableDD: false,
			store: store_article,
			height: 100,
			flex: 3,
			plain: true,

			selModel: new Ext.grid.RowSelectionModel({
				singleSelect: false,
				listeners: {
					rowselect: function(self){
					},
					rowdeselect: function(self){
					}
				}
			}),
			view: new Ext.ux.grid.BufferView({
				scrollDelay: false,
				forceFit : true,
				emptyText: '데이터가 없습니다.'
			}),
			listeners: {
			},
			tbar : [{
				xtype : 'button',
				text : '다운로드',
				handler: function(btn, e){
					if(btn.ownerCt.ownerCt.selModel.getSelections().length < 1 ){
						Ext.Msg.alert('알림','다운로드할 목록을 선택해주세요.');
						return;
					}else{
						Ext.Msg.alert('알림','선택한 파일을 다운로드 하시겠습니까?');
					}
				}
			}],
			cm: new Ext.grid.ColumnModel({
				defaults: {
					sortable: true,
					align: 'center'
				},
				columns: [
					{header: 'ID', dataIndex: 'request_id', width: 60, hidden: true},
					{header: '구분', dataIndex: 'request_id', width: 60},
					{header: '업무 의뢰일', dataIndex: 'create_date', align: 'center', renderer: Ext.util.Format.dateRenderer('Y-m-d'), width: 120},
					{header: '업무부서', dataIndex: 'dept_code', width: 120},
					{header: '업무자', dataIndex: 'create_user_name', width: 60},
					{header: '업무상태', dataIndex: 'status', width: 60},
					{header: '제목', dataIndex: 'request_title', width: 120},
					{header: '요청자', dataIndex: 'create_user_name', width: 60},
					{header: '업무 의뢰 내역', dataIndex: 'request_content', width: 80},
					{header: '업무자 코멘트', dataIndex: 'comments', width: 60}
				]
			})
		}]
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

                case '11':
			value = '썸네일';
		break;

                case '15':
			value = 'Quality Checker(QC)';
		break;

		case '20':
			//>>value = '트랜스코딩';
			value = _text('MN00298');
		break;

                case '29':
			//>>value = '트랜스코딩';
			value = _text('MN00298');
		break;

		case '60':
			//>>value = '인제스트전송';
			value = _text('MN00226');
		break;

                case '69':
			value = '전송(FS)';
		break;

                case '70':
			value = '트랜스코딩(Audio)';
		break;

                case '80':
			value = 'FTP 전송';
		break;

                case '89':
			value = 'FTP 전송(Client)';
		break;

		case '130':
			value = '미디어정보';
		break;
	}
	return value;
}

})(<?=$records?>)