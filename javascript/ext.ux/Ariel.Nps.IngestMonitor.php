<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lang.php');

?>
Ext.ns('Ariel.Nps');

Ariel.Nps.IngestMonitor = Ext.extend(Ext.Window, {
	width: 1000,
	layout: 'fit',
	height: 600,
	listeners: {
		hide: function(self){
			self.reset();
		}
	},
	initComponent: function(config){

		Ext.apply(this, config || {});
		var that = this;

		var store = new Ext.data.JsonStore({
			url:'/pages/menu/config/workflow/workflow_list.php',
			root: 'data',
			totalProperty: 'total',
			baseParams : {
				action: 'storage_list'
			},
			fields: [
				{name: 'storage_id'},
				{name : 'name'},
				{name : 'type'},
				{name : 'description'},
				{name : 'authority'},
				{name : 'describe'},
				{name : 'group_name'},
				{name : 'mac_address'},
				{name : 'path'},
                                {name: 'login_id'},
                                {name: 'login_pw'},
                                {name: 'path_for_mac'},
                                {name: 'virtual_path'}
			]
		});
                
                var test_store = new Ext.data.SimpleStore({
                                fields: [
                                        {name: 'ingest_id'},
                                        {name : 'file_name'},
                                        {name : 'duration'},
                                        {name : 'start_tc'},
                                        {name : 'ingest_server'},
                                        {name : 'worker'},
                                        {name : 'created_date', type:'date', dateFormat: 'YmdHis'},
                                        {name : 'status'}
                                ],
                                data: [
                                        ['1','먹거리X파일 1회','00:50:00','01:00:00', '인제스트 1번','홍길동', '20140524152626','작업중'],
                                        ['2','먹거리X파일 2회','00:53:00','01:30:00', '인제스트 3번','장보고', '20140522163045','완료'],
                                        ['3','먹거리X파일 3회','00:45:00','00:00:00', '인제스트 2번','김수한무', '20140520113345','중지']
                                ]
                        });

		this.items = {
			xtype: 'grid',
			frame: true,
			id: 'ingest_monitor_grid',
			loadMask: true,
			store: test_store,
			viewConfig:{
				forceFit: true
			},
			listeners: {
				render: function(self){
				//	self.store.load();
				}
			},
			colModel: new Ext.grid.ColumnModel({
				defaultSortable: false,
				columns: [
					{header: "전송ID", dataIndex: 'ingest_id', width: 20},
					{header: "파일명", dataIndex: 'file_name' },
					{header: "Duration", dataIndex: 'duration'},
					{header: "Start TC", dataIndex: 'start_tc'},
                                        {header: "인제스트 장비", dataIndex: 'ingest_server'},
                                        {header: "작업자", dataIndex:'worker'},
                                        {header: "등록일자", dataIndex:'created_date', width: 200, renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s') },
                                        {header: "상태", dataIndex:'status'}
				]
			}),
			sm: new Ext.grid.RowSelectionModel({
				singleSelect:true
			}),
                        tbar: ['기간',{
                                xtype : 'datefield',
                                id : 'ingest_s_date',
                                value: new Date(),
                                format: 'Y-m-d',
                                width : 120                                
                        },' ~ ',{
                                xtype : 'datefield',
                                id : 'ingest_e_date',
                                value: new Date(),
                                format: 'Y-m-d',
                                width : 120
                        },{
                                xtype: 'tbspacer',
				width: '20'
                        },{
                                xtype : 'combo',
                                id : 'ingest_search_type',
                                typeAhead: true,
                                triggerAction: 'all',
                                mode : 'local',
                                width : 120,
                                editable : false,
                                hidden : false,
                                value: 'all',
                                emptyText : '검색조건',
                                store: new Ext.data.SimpleStore({
                                        fields: [
                                                'status_id',
                                                'status_nm'
                                        ],
                                        data: [['all', '전체'],
                                                ['complete', '완료'],
                                                ['processing', '작업중'],
                                                ['error', '중지']
                                              ]
                                }),
                                valueField: 'status_id',
                                displayField: 'status_nm'
                        },{
                                xtype: 'tbspacer',
				width: '10'
                        },{
                                xtype: 'textfield',
                                id: 'ingest_search_value',
                                emptyText: '검색어'
                        },{
                                xtype: 'button',
                                text: '조회'
                        }],
/*			bbar: {
				xtype: 'paging',
				pageSize: 20,
				displayInfo: true,
				store: store
			}
*/		};

		Ariel.Nps.IngestMonitor.superclass.initComponent.call(this);
	}

});


Ariel.Nps.TransferMonitor = Ext.extend(Ext.Window, {
	width: 800,
	layout: 'fit',
	height: 600,
	listeners: {
		hide: function(self){
			self.reset();
		}
	},
	initComponent: function(config){

		Ext.apply(this, config || {});
		var that = this;

		var store = new Ext.data.JsonStore({
			url:'/pages/menu/config/workflow/workflow_list.php',
			root: 'data',
			totalProperty: 'total',
			baseParams : {
				action: 'storage_list'
			},
			fields: [
				{name: 'storage_id'},
				{name : 'name'},
				{name : 'type'},
				{name : 'description'},
				{name : 'authority'},
				{name : 'describe'},
				{name : 'group_name'},
				{name : 'mac_address'},
				{name : 'path'},
                                {name: 'login_id'},
                                {name: 'login_pw'},
                                {name: 'path_for_mac'},
                                {name: 'virtual_path'}
			]
		});
                
                var test_store = new Ext.data.SimpleStore({
                                fields: [
                                        {name: 'transfer_id'},
                                        {name : 'transfer_type'},
                                        {name : 'transfer_name'},
                                        {name : 'file_name'},
                                        {name : 'target'},
                                        {name : 'target_type'},
                                        {name : 'worker'},
                                        {name : 'created_date', type:'date', dateFormat: 'YmdHis'},
                                        {name : 'status'}
                                ],
                                data: [
                                        ['1','아카이브','아카이브 등록','먹거리 X파일 1회', '상암동 NPS', '아카이브','홍길동', '20140524152626','작업중'],
                                        ['2','NLE','NLE 등록','먹거리 X파일 2회', 'NLE', '상암동 NPS','장보고', '20140522163045','완료'],
                                        ['3','아카이브','아카이브 등록','먹거리 X파일 3회', '상암동 NPS', '아카이브','김수한무', '20140520113345','중지']
                                ]
                        });

		this.items = {
			xtype: 'grid',
			frame: true,
			id: 'transfer_monitor_grid',
			loadMask: true,
			store: test_store,
			viewConfig:{
				forceFit: true
			},
			listeners: {
				render: function(self){
				//	self.store.load();
				}
			},
			colModel: new Ext.grid.ColumnModel({
				defaultSortable: false,
				columns: [
					{header: "전송ID", dataIndex: 'transfer_id'},
					{header: "전송 구분", dataIndex: 'transfer_type' },
					{header: "전송 작업명", dataIndex: 'transfer_name'},
					{header: "콘텐츠 명", dataIndex: 'file_name'},
                                        {header: "전송처", dataIndex: 'target'},
                                        {header: "전송 대상", dataIndex:'target_type'},
                                        {header: "작업자", dataIndex:'worker'},
                                        {header: "등록일자", dataIndex:'created_date', width: 80, renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s') },
                                        {header: "상태", dataIndex:'status'}
				]
			}),
			sm: new Ext.grid.RowSelectionModel({
				singleSelect:true
			}),
                        tbar: ['기간',{
                                xtype : 'datefield',
                                id : 'transfer_s_date',
                                value: new Date(),
                                format: 'Y-m-d',
                                width : 120                                
                        },' ~ ',{
                                xtype : 'datefield',
                                id : 'transfer_e_date',
                                value: new Date(),
                                format: 'Y-m-d',
                                width : 120
                        },{
                                xtype: 'tbspacer',
				width: '20'
                        },{
                                xtype : 'combo',
                                id : 'transfer_search_type',
                                typeAhead: true,
                                triggerAction: 'all',
                                mode : 'local',
                                width : 120,
                                editable : false,
                                hidden : false,
                                value: 'all',
                                emptyText : '검색조건',
                                store: new Ext.data.SimpleStore({
                                        fields: [
                                                'status_id',
                                                'status_nm'
                                        ],
                                        data: [['all', '전체'],
                                                ['complete', '완료'],
                                                ['processing', '작업중'],
                                                ['error', '중지']
                                              ]
                                }),
                                valueField: 'status_id',
                                displayField: 'status_nm'
                        },{
                                xtype: 'tbspacer',
				width: '10'
                        },{
                                xtype: 'textfield',
                                id: 'trnasfer_search_value',
                                emptyText: '검색어'
                        },{
                                xtype: 'button',
                                text: '조회'
                        }],
/*			bbar: {
				xtype: 'paging',
				pageSize: 20,
				displayInfo: true,
				store: store
			}
*/		};

		Ariel.Nps.TransferMonitor.superclass.initComponent.call(this);
	}

});