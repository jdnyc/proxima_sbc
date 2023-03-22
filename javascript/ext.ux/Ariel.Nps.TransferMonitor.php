<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lang.php');

?>
Ext.ns('Ariel.Nps');
Ariel.Nps.TransferMonitor = Ext.extend(Ext.TabPanel, {
	border: false,


	initComponent: function(config){
		Ext.apply(this, config || {});
		var that = this;

		var store = new Ext.data.JsonStore({
			url:'/store/nps_work/request_master.php',
			root: 'data',
			totalProperty: 'total',
			baseParams : {
				type: 'list'
			},
			fields: [
				{name: 'usr_program'},
				{name: 'usr_progid'},
				{name: 'usr_materialid'},
				{name: 'usr_turn'},
				{name: 'usr_subprog'},
				{name: 'usr_brod_date'},
				{name: 'usr_content'},
				{name: 'usr_mednm'},
				{name: 'usr_producer'},
				{name: 'usr_grade'},
				{name: 'material_id'},
				{name: 'program_id'},
				{name: 'status'},
				{name: 'status_nm'},
				{name: 'req_comment'},
				{name: 'mas_comment'},
				{name: 'is_appr'},
				{name: 'pd_nm'},
				{name: 'created_date',type:'date',dateFormat:'YmdHis'},
				{name: 'content_id'},
				{name: 'progress'},
				{name: 'request_id'},
				{name: 'send_user_id'},
				{name: 'send_user_nm'},
				{name: 'interface_id'},
				{name: 'send_phone'},
				{name: 'ch_qc_status'},
				{name: 'ch_qc_status_nm'},
				{name: 'ch_fs_status'},
				{name: 'ch_fs_status_nm'},
				{name: 'qc_qtip'},
				{name: 'tm_qtip'}
			],
			listeners: {
				beforeload: function( self, opts ){
					opts.params = opts.params || {};
					var values = Ext.getCmp('transfer-monitor-form').getSearchVal(Ext.getCmp('transfer-monitor-form'));
					var task_status = new Array();
					for (x in values) {
						if(x == 'status'){
							task_status.push( values[x] );
						}
					}
					opts.params.status = task_status;
					opts.params.create_date_start = values.create_date_start;
					opts.params.create_date_end = values.create_date_end;
					opts.params.program_category = Ext.getCmp('transfer-monitor-form').getForm().findField('program_category').getValue();
				}
			}
		});

		var scrollstore = new Ext.data.JsonStore({
			url:'/store/nps_work/request_master.php',
			root: 'data',
			totalProperty: 'total',
			baseParams : {
				type: 'list',
				sub_type : 'scroll'
			},
			fields: [
				{name: 'turn'},
				{name: 'program'},
				{name: 'program_id'},
				{name: 'status'},
				{name: 'status_nm'},
				{name: 'req_comment'},
				{name: 'mas_comment'},
				{name: 'created_date',type:'date',dateFormat:'YmdHis'},
				{name: 'request_id'},
				{name: 'accept_user_id'},
				{name: 'accept_user_nm'},
				{name: 'send_user_id'},
				{name: 'send_user_nm'},
				{name: 'send_phone'},
				{name: 'scroll_bottom'},
				{name: 'scroll_top'},
				{name: 'brodymd_start',type:'date',dateFormat:'Ymd'},
				{name: 'brodymd_end',type:'date',dateFormat:'Ymd'}
			],
			listeners: {
				beforeload: function( self, opts ){
					opts.params = opts.params || {};
					var values = Ext.getCmp('scroll-monitor-form').getSearchVal(Ext.getCmp('scroll-monitor-form'));
					var task_status = new Array();
					for (x in values) {
						if(x == 'status'){
							task_status.push( values[x] );
						}
					}
					opts.params.status = task_status;
					opts.params.create_date_start = values.create_date_start;
					opts.params.create_date_end = values.create_date_end;
					opts.params.program_category = Ext.getCmp('scroll-monitor-form').getForm().findField('program_category').getValue();
				}
			}
		});

		var captionstore = new Ext.data.JsonStore({
			url:'/store/nps_work/request_caption.php',
			root: 'data',
			totalProperty: 'total',
			baseParams : {
				type: 'list'
			},
			fields: [
				{name: 'turn'},
				{name: 'program'},
				{name: 'progid'},
				{name: 'comments'},
				{name: 'request_date',type:'date',dateFormat:'YmdHis'},
				{name: 'request_id'},
				{name: 'accept_user_id'},
				{name: 'accept_user_nm'},
				{name: 'send_user_id'},
				{name: 'send_user_nm'},
				{name: 'send_phone'}
			],
			listeners: {
				beforeload: function( self, opts ){
					opts.params = opts.params || {};

				}
			}
		});

		function request_accept_win(record){

			if( record.get('status') == '0' ){
				var hiddenbutton = false;
			}else{
				var hiddenbutton = true;
			}
			new Ext.Window({
				title: '주조 전송 의뢰 승인',
				width: 300,
				height: 300,
				layout: 'fit',
				modal: true,

				items: [{
					xtype: 'form',
					padding: 5,
					labelWidth: 50,
					labelAlign: 'right',
					url:'/store/nps_work/request_master.php',
					baseParams: {
						type : 'update'
					},
					defaults: {
						xtype:'textfield',
						readOnly: true,
						width:'90%'
					},
					items: [{
						xtype: 'hidden',
						name: 'request_id',
						value: record.get('request_id')
					},{
						fieldLabel: '프로그램',
						value: record.get('usr_program')
					},{
						fieldLabel: '회차',
						value: record.get('usr_turn')
					},{
						fieldLabel: '소재 ID',
						value: record.get('usr_materialid')
					},{
						fieldLabel: '소재명',
						value: record.get('usr_subprog')
					},{
						name: 'mas_comment',
						xtype: 'textarea',
						readOnly: false,
						maxLength : 1000,
						allowBlank : false,
						fieldLabel: '주조 의견',
						value: record.get('mas_comment')
					},{
						fieldLabel: '담당자',
						value: record.get('usr_producer')
					}]
				}],
				buttonAlign: 'center',
				buttons: [{
					text: '반려',
					hidden: hiddenbutton,
					scale: 'medium',
					handler: function(b,e){
						var win = b.ownerCt.ownerCt;
						var form = b.ownerCt.ownerCt.get(0);
						if( form.getForm().isValid() ){
							form.getForm().submit({
								waitMsg : '처리 중...',
								params: {
									status : -5
								},
								success: function(form, action) {
								   Ext.Msg.alert('성공', action.result.msg);
								   win.close();
								   Ext.getCmp('scroll-monitor-grid').getStore().reload();
								},
								failure: function(form, action) {
									Ext.Msg.alert('실패', action.result.msg);
								}
							});

						}
					}
				},{
					text: '승인',
					scale: 'medium',
					hidden: hiddenbutton,
					handler: function(b,e){
						var win = b.ownerCt.ownerCt;
						var form = b.ownerCt.ownerCt.get(0);
						if( form.getForm().isValid() ){
							form.getForm().submit({
								waitMsg : '처리 중...',
								params: {
									status : 2
								},
								success: function(form, action) {
								   Ext.Msg.alert('성공', action.result.msg);
								    win.close();
									Ext.getCmp('transfer-monitor-grid').getStore().reload();
								},
								failure: function(form, action) {
									Ext.Msg.alert('실패', action.result.msg);
								}
							});

						}
					}
				}]
			}).show();
		}

		function request_scroll_request_win(record){

			var title = '스크롤 의뢰';
			if( Ext.isEmpty(record) ){
				//등록
				var isEdit = false;
				var isADD = false;
				var	isReady = true;
				var	isMas = true;
				var send_user_nm = '<?=$_SESSION['user']['KOR_NM']?>';
				var send_user_id = '<?=$_SESSION['user']['user_id']?>';

				var program_id	= '';
				var turn		='';
				var brodymd_start = '';
				var brodymd_end =  '';
				var req_comment =  '';
				var mas_comment =  '';
				var scroll_top =  '';
				var scroll_bottom =  '';
				var request_id = '';

				var accept_user_nm = '';
				var accept_user_id = '';

			}else{
				var isEdit = true;
				var request_id =  record.get('request_id');
				var send_user_nm = record.get('send_user_nm');
				var send_user_id = record.get('send_user_id');
				var program_id	= record.get('program_id');
				var turn		= record.get('turn');
				var brodymd_start = record.get('brodymd_start');
				var brodymd_end =  record.get('brodymd_end');
				var req_comment =  record.get('req_comment');
				var mas_comment =  record.get('mas_comment');
				var scroll_top =  record.get('scroll_top');
				var scroll_bottom =  record.get('scroll_bottom');



				if( record.get('status') == 0){
					//등록상태
					//주조확인 내용 /승인/반려버튼 활성화
					var isADD = true;
					var	isReady = false;
					var	isMas = false;
					var	MasEdit = false;
					var accept_user_nm = '<?=$_SESSION['user']['KOR_NM']?>';
					var accept_user_id = '<?=$_SESSION['user']['user_id']?>';
				}else {
					//승인이나 반려상태
					var isADD = true;
					var isReady = true;
					var accept_user_nm = record.get('accept_user_nm');
					var accept_user_id = record.get('accept_user_id');
				}
			}

		new Ext.Window({
			title: title,
			width: 400,
			height: 500,
			modal: true,
			layout: 'fit',
			items: [{
				xtype: 'form',
				padding: 5,
				labelWidth: 50,
				labelAlign: 'right',
				url:'/store/nps_work/request_master.php',
				baseParams: {
					type : 'request',
					sub_type : 'scroll'
				},
				defaults: {
					xtype:'textfield',
					width:'90%',
					readOnly: isEdit
				},
				items: [{
					xtype:'hidden',
					name : 'request_id',
					value : request_id
				},{
					xtype:'hidden',
					name : 'program_id',
					value : program_id
				},{
					xtype: 'combo',
					width: 295,
					//mode: 'local',
					triggerAction: 'all',
					fieldLabel: '프로그램',
					name : 'program',
					editable : false,
					allowBlank : false,
					displayField : 'category_title',
					valueField : 'path',
					hiddenValue: 'path',
					store : new Ext.data.JsonStore({
						url:'/store/nps_work/request_master.php',
						autoLoad: true,
						baseParams: {
							type : 'program',
							sub_type : 'scroll',
							notall : 'true'
						},
						root: 'data',
						idProperty: 'path',
						fields: [
							{ name:'category_title' },
							{ name:'path' }
						]
					}),
					listeners: {
						select: function(combo, record, index ){
							combo.ownerCt.getForm().findField('program_id').setValue(record.data.path);
						},
						afterrender : function(self){
							if(!Ext.isEmpty(record)){
								self.setRawValue(record.get('program'));
								self.setValue(record.get('program_id'));
							}
						}
					}
				},{
					fieldLabel: '회차',
					name :'turn',
					allowBlank : false,
					value : turn
				},{
					xtype: 'compositefield',
					fieldLabel: '방송기간',
					width: 295,

					items: [{
						xtype:'datefield',
						format: 'Y-m-d',
						altFormats : 'YmdHis|Y-m-d|Ymd',
						name: 'brodymd_start',
						value : brodymd_start,
						allowBlank : false,
						flex: 1
					},{
						xtype: 'displayfield',
						width: 10,
						value: '~'
					},{
						xtype: 'datefield',
						name: 'brodymd_end',
						altFormats : 'YmdHis|Y-m-d|Ymd',
						value : brodymd_end,
						format: 'Y-m-d',
						allowBlank : false,
						flex: 1
					}]
				},{
					xtype: 'textarea',
					fieldLabel: '송출내용',
					allowBlank : false,
					value : req_comment,
					maxLength : 1000,
					name:'req_comment'
				},{
					xtype: 'textarea',
					fieldLabel: '상단',
					allowBlank : false,
					value : scroll_top,
					maxLength : 1000,
					name: 'scroll_top'
				},{
					xtype: 'textarea',
					fieldLabel: '하단',
					allowBlank : false,
					maxLength : 1000,
					value : scroll_bottom,
					name: 'scroll_bottom'
				},{
					fieldLabel: '의뢰자',
					readOnly: true,
					value: send_user_nm
				},{
					hidden: true,
					value: send_user_id,
					name: 'send_user_id'
				},{
					hidden: isMas,
					fieldLabel: '승인자',
					readOnly: true,
					value: accept_user_nm
				},{
					hidden: true,
					value: accept_user_id,
					name: 'accept_user_id'
				},{
					hidden: isMas,
					allowBlank : isMas,
					readOnly: MasEdit,
					xtype: 'textarea',
					fieldLabel: '주조확인',
					value : mas_comment,
					maxLength : 1000,
					name:'mas_comment'
				}]
			}],
			buttonAlign: 'center',
			buttons: [{
				text: '의뢰',
				hidden: isADD,
				scale: 'medium',
				handler: function(b,e){
					var win = b.ownerCt.ownerCt;
					var form = b.ownerCt.ownerCt.get(0);
					if( form.getForm().isValid() ){
						form.getForm().submit({
							waitMsg : '처리 중...',
							params: {

							},
							success: function(form, action) {
							   Ext.Msg.alert('성공', action.result.msg);
								win.close();
								Ext.getCmp('scroll-monitor-grid').getStore().reload();
							},
							failure: function(form, action) {
								Ext.Msg.alert('실패', action.result.msg);
							}
						});

					}
				}
			},{
				text: '승인',
				hidden: isReady,
				scale: 'medium',
				handler: function(b,e){
					var win = b.ownerCt.ownerCt;
					var form = b.ownerCt.ownerCt.get(0);
					if( form.getForm().isValid() ){
						form.getForm().submit({
							waitMsg : '처리 중...',
							params: {
								type: 'update',
								status: 2
							},
							success: function(form, action) {
							   Ext.Msg.alert('성공', action.result.msg);
								win.close();
								Ext.getCmp('scroll-monitor-grid').getStore().reload();
							},
							failure: function(form, action) {
								Ext.Msg.alert('실패', action.result.msg);
							}
						});

					}
				}
			},{
				text: '반려',
				hidden: isReady,
				scale: 'medium',
				handler: function(b,e){
					var win = b.ownerCt.ownerCt;
					var form = b.ownerCt.ownerCt.get(0);
					if( form.getForm().isValid() ){
						form.getForm().submit({
							waitMsg : '처리 중...',
							params: {
								type: 'update',
								status: -5
							},
							success: function(form, action) {
							   Ext.Msg.alert('성공', action.result.msg);
								win.close();
								Ext.getCmp('scroll-monitor-grid').getStore().reload();
							},
							failure: function(form, action) {
								Ext.Msg.alert('실패', action.result.msg);
							}
						});

					}
				}
			},{
				text: '삭제',
				hidden: true
			}]
		}).show();
		}


		this.items = [{
			xtype: 'panel',
			title: '부조 모니터링',
			layout: 'border',
			items:[{
				xtype: 'form',
				region: 'north',
				id: 'transfer-monitor-form',
				height: 80,
				frame: true,
				defaults: {
				},
				getSearchVal : function(self){
					return self.getForm().getValues();
				},
				items: [{
					xtype: 'compositefield',
					fieldLabel: '프로그램',
					items: [{
						xtype: 'combo',
						//mode: 'local',
						triggerAction: 'all',
						width: 375,
						name : 'program_category',
						editable: false,
						displayField : 'category_title',
						valueField : 'path',
						hiddenValue: 'path',
						value : '전체',
						store : new Ext.data.JsonStore({
							url:'/store/nps_work/request_master.php',
							autoLoad: true,
							baseParams: {
								type : 'program'
							},
							root: 'data',
							idProperty: 'path',
							fields: [
								{ name:'category_title' },
								{ name:'path' }
							]
						})
					},{
						xtype: 'displayfield',
						flex: 1,
					},{
						xtype: 'checkboxgroup',
						columns: 4,
						width: 300,
						defaults:{
							checked: true
						},
						items: [{
							xtype: 'checkbox',
							boxLabel: '전체',
							listeners : {
								check: function( self, checked ){
									var checklist = self.ownerCt.ownerCt.find('group','toggle');
									Ext.each(checklist, function(r){
										r.setValue(checked);
									});
								}
							}
						},{
							xtype: 'checkbox',
							boxLabel: '전송완료',
							group: 'toggle',
							inputValue : 'complete',
							name: 'status'
						},{
							xtype: 'checkbox',
							boxLabel: '전송중',
							group: 'toggle',
							inputValue : 'processing',
							name: 'status'
						},{
							xtype: 'checkbox',
							boxLabel: '실패',
							group: 'toggle',
							inputValue : 'error',
							name: 'status'
						}]
					}]
				},{
					xtype: 'compositefield',
					fieldLabel: '전송 기간',
					items: [{
						width: 150,
						xtype: 'datefield',
						format: 'Y-m-d',
						name: 'create_date_start',
						listeners: {
							render: function(self){
								var d = new Date();
								self.setMaxValue(d.format('Y-m-d'));
								self.setValue(d.add(Date.MONTH, -1).format('Y-m-d'));
							}
						}
					},{
						xtype: 'displayfield',
						width: 10,
						value: '~'
					},{

						width: 150,
						xtype: 'datefield',
						format: 'Y-m-d',
						name: 'create_date_end',
						value: new Date().format('Y-m-d')
					},{
						width: 50,
						xtype: 'button',
						text: '검색',
						handler: function(b, e){
							Ext.getCmp('transfer-monitor-grid').getStore().reload();
						}
					}]
				}]
			},{
				xtype: 'grid',
				region: 'center',
				frame: true,
				id: 'transfer-monitor-grid',
				loadMask: true,
				store: store,
				viewConfig:{
					//forceFit: true
				},
				listeners: {
					render: function(self){
					},
					rowdblclick: function(self, rowIndex, e){
						var sm = self.getSelectionModel().getSelected();
						request_accept_win( sm);
					},
					afterrender: function(self){

					}
				},
				colModel: new Ext.grid.ColumnModel({
					defaultSortable: false,
					columns: [
						new Ext.grid.RowNumberer(),
						{header: "전송일시", dataIndex: 'created_date' , width: 120, renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s') },
						{header: "소재 ID", dataIndex: 'usr_materialid'  , width: 120},
						{header: "소재명", dataIndex: 'usr_subprog' , width: 150},
						{header: "담당 PD", dataIndex: 'usr_producer' , width: 50, hidden: true},
						{header: "의뢰자", dataIndex: 'send_user_nm' , width: 50, hidden: true},
						{header: "의뢰내용", dataIndex: 'req_comment', hidden: true},
						{header: "전송상태", dataIndex: 'ch_fs_status_nm' , renderer: function(value, metaData, record, rowIndex, colIndex, store){
							metaData.attr = 'ext:qtip="'+record.get('tm_qtip')+'"  ';
							return value;
						}},
						{header: "QC (에러)", dataIndex: 'ch_qc_status_nm' , hidden: true, renderer: function(value, metaData, record, rowIndex, colIndex, store){
							metaData.attr = 'ext:qtip="'+record.get('qc_qtip')+'" ext:qwidth=300 ';
							return value;
						}},
						{header: "주조 의견", dataIndex: 'mas_comment' , hidden: true, renderer: function(value, metaData, record, rowIndex, colIndex, store){
							if(record.get('status') != '0'){
								value = record.get('status_nm')+'-'+value;
							}
							return value;
						} }
					]
				}),
				sm: new Ext.grid.RowSelectionModel({
					singleSelect:true
				}),
				bbar: {
					xtype: 'paging',
					pageSize: 20,
					displayInfo: true,
					store: store
				}
			}]
/*		},{
			xtype: 'panel',
			title: '스크롤 의뢰',
			hidden: true,
			layout: 'border',
			items:[{
				xtype: 'form',
				region: 'north',
				id: 'scroll-monitor-form',
				height: 80,
				frame: true,
				defaults: {
				},
				getSearchVal : function(self){
					return self.getForm().getValues();
				},
				items: [{
					xtype: 'compositefield',
					fieldLabel: '프로그램',
					items: [{
						xtype: 'combo',
						//mode: 'local',
						triggerAction: 'all',
						width: 375,
						name : 'program_category',
						editable: false,
						displayField : 'category_title',
						valueField : 'path',
						hiddenValue: 'path',
						value : '전체',
						store : new Ext.data.JsonStore({
							url:'/store/nps_work/request_master.php',
							autoLoad: true,
							baseParams: {
								type : 'program',
								sub_type : 'scroll'
							},
							root: 'data',
							idProperty: 'path',
							fields: [
								{ name:'category_title' },
								{ name:'path' }
							]
						})
					},{
						xtype: 'displayfield',
						flex: 1
					},{
						xtype: 'checkboxgroup',
						columns: 4,
						width: 300,
						defaults:{
							checked: true
						},
						items: [{
							xtype: 'checkbox',
							boxLabel: '전체',
							listeners : {
								check: function( self, checked ){
									var checklist = self.ownerCt.ownerCt.find('group','toggle');
									Ext.each(checklist, function(r){
										r.setValue(checked);
									});
								}
							}
						},{
							xtype: 'checkbox',
							boxLabel: '승인완료',
							group: 'toggle',
							inputValue : '2',
							name: 'status'
						},{
							xtype: 'checkbox',
							boxLabel: '의뢰중',
							group: 'toggle',
							inputValue : '0',
							name: 'status'
						},{
							xtype: 'checkbox',
							boxLabel: '반려',
							group: 'toggle',
							inputValue : '-5',
							name: 'status'
						}]
					}]
				},{
					xtype: 'compositefield',
					fieldLabel: '의뢰 기간',
					items: [{
						width: 150,
						xtype: 'datefield',
						format: 'Y-m-d',
						name: 'create_date_start',
						listeners: {
							render: function(self){
								var d = new Date();
								self.setMaxValue(d.format('Y-m-d'));
								self.setValue(d.add(Date.MONTH, -1).format('Y-m-d'));
							}
						}
					},{
						xtype: 'displayfield',
						width: 10,
						value: '~'
					},{

						width: 150,
						xtype: 'datefield',
						format: 'Y-m-d',
						name: 'create_date_end',
						value: new Date().format('Y-m-d')
					},{
						width: 50,
						xtype: 'button',
						text: '검색',
						handler: function(b, e){
							Ext.getCmp('scroll-monitor-grid').getStore().reload();
						}
					},{
						xtype: 'displayfield',
						flex: 1
					},{
						width: 100,
						xtype: 'button',
						text: '등록',
						handler: function(b, e){
							request_scroll_request_win();
						}
					},{
						xtype: 'displayfield',
						width: 30
					}]
				}]
			},{
				xtype: 'grid',
				region: 'center',
				frame: true,
				id: 'scroll-monitor-grid',
				loadMask: true,
				store: scrollstore,
				viewConfig:{
					//forceFit: true
				},
				listeners: {
					render: function(self){
					},
					rowdblclick: function(self, rowIndex, e){
						var sm = self.getSelectionModel().getSelected();
						request_scroll_request_win( sm);
					},
					afterrender: function(self){

					}
				},
				colModel: new Ext.grid.ColumnModel({
					defaultSortable: false,
					columns: [
						new Ext.grid.RowNumberer(),
						{header: "의뢰일시", dataIndex: 'created_date' , width: 120, renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s') },
						{header: "프로그램 ID", dataIndex: 'program_id'  , width: 120},
						{header: "프로그램명", dataIndex: 'program' , width: 300},
						{header: "회차", dataIndex: 'turn' , width: 50},
						//{header: "의뢰자", dataIndex: 'send_user_nm' , width: 50},
						{header: "의뢰내용", dataIndex: 'req_comment' , width: 150},
						{header: "주조확인", dataIndex: 'mas_comment', width: 150 , renderer: function(value, metaData, record, rowIndex, colIndex, store){
							if(record.get('status') != '0'){
								value = record.get('status_nm')+'-'+value;
							}
							return value;
						} }
					]
				}),
				sm: new Ext.grid.RowSelectionModel({
					singleSelect:true
				}),
				bbar: {
					xtype: 'paging',
					pageSize: 20,
					displayInfo: true,
					store: scrollstore
				}
			}]
		},{
			xtype: 'panel',
			title: '자막 의뢰',
			hidden: true,
			layout: 'border',
			items:[{
					xtype: 'hidden'
				},{
				xtype: 'grid',
				region: 'center',
				frame: true,
				id: 'caption-monitor-grid',
				loadMask: true,
				store: captionstore,
				viewConfig:{
					//forceFit: true
				},
				listeners: {
					render: function(self){
					},
					rowdblclick: function(self, rowIndex, e){
						var sm = self.getSelectionModel().getSelected();
					},
					afterrender: function(self){
					}
				},
				colModel: new Ext.grid.ColumnModel({
					defaultSortable: false,
					columns: [
						new Ext.grid.RowNumberer(),
						{header: "의뢰일시", dataIndex: 'request_date' , width: 120, renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s') },
						{header: "프로그램 ID", dataIndex: 'progid'  , width: 120},
						{header: "프로그램명", dataIndex: 'program' , width: 300},
						{header: "회차", dataIndex: 'turn' , width: 50},
						{header: "의뢰자", dataIndex: 'send_user_nm' , width: 50},
						{header: "의뢰내용", dataIndex: 'comments' , width: 150, renderer: function(value, metaData, record, rowIndex, colIndex, store){
							metaData.attr = 'ext:qtip="'+value+'"  ';
							return value;
						}}
					]
				}),
				sm: new Ext.grid.RowSelectionModel({
					singleSelect:true
				}),
				bbar: {
					xtype: 'paging',
					pageSize: 20,
					displayInfo: true,
					store: captionstore
				}
			}]
*/		}];

		this.listeners = {
			activate: function( p ){
				p.setActiveTab(0);
			},
			tabchange: function( self, tab ){
				tab.get(1).getStore().load();
			}
		};

		Ariel.Nps.TransferMonitor.superclass.initComponent.call(this);
	}
});