<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lang.php');

?>
Ext.ns('Ariel.Nps');
Ariel.Nps.ScrollRequest = Ext.extend(Ext.Panel, {
	border: false,
	layout: 'fit',

	initComponent: function(config){
		Ext.apply(this, config || {});
		var that = this;

		var store = new Ext.data.JsonStore({
			url:'/store/notice/get_list.php',
			root: 'data',
			totalProperty: 'total',
			baseParams : {
				type: 'list'
			},
			fields: [
				{name: 'notice_id'},
				{name : 'notice_title'},
				{name : 'notice_content'},
				{name : 'from_user_id'},
				{name : 'notice_type'},
				{name : 'member_group_id'},
				{name : 'depnm'},
				{name : 'mname'},
				{name : 'depcd'},
				{name:'created_date',type:'date',dateFormat:'YmdHis'}
			]
		});
               
                var req_win = new Ext.Window({
                    title: '스크롤 의뢰',
                    width: 300,
                    height: 300,
                    modal: true,
                    closeAction: 'hide',
                    layout: 'fit',
                    
                    items: [{
                        xtype: 'form',
                        padding: 5,
                        labelWidth: 50,
                        labelAlign: 'right',

                        defaults: {
                            xtype:'textfield',
                            width:'90%'
                        },
                        items: [{
                                xtype: 'combo',
                                fieldLabel: '프로그램',
                                //id: 'prog_combo',
                                width: 197,
                                mode: 'local',
                                triggerAction: 'all',
                                editable: false,
                                displayField: 'd',
                                valueField: 'v',
                                emptyText: '프로그램을 선택하세요',
                                store: new Ext.data.ArrayStore({
                                        fields: [
                                                'd', 'v'
                                        ],
                                        data: [
                                                ['메타데이터 보완 요청','refuse_meta'],
                                                ['인코딩 재작업 요청','refuse_encoding']
                                        ]
                                })
                        },{
                            fieldLabel: '회차'
                        },{
                            xtype: 'compositefield',
                            fieldLabel: '방송기간',
                            layout: 'hbox',
                            items: [{
                                xtype:'datefield',
                                format: 'Y-m-d',
                                width: 90
                            },{
                                xtype: 'displayfield',
                                value: '~'
                            },{
                                xtype: 'datefield',
                                format: 'Y-m-d',
                                width: 90
                            }]
                        },{
                            xtype: 'textarea',
                            fieldLabel: '송출내용'
                        },{
                            fieldLabel: '상단'
                        },{
                            fieldLabel: '하단'
                        },{
                            fieldLabel: '의뢰자',
                            readOnly: true,
                        }]
                    }],
                    buttonAlign: 'center',
                    buttons: [{
                        text: '의뢰'
                    },{
                        text: '삭제',
                        hidden: true
                    }]
                });
                
                var appr_win = new Ext.Window({
                    title: '스크롤 의뢰 승인',
                    width: 300,
                    height: 350,
                    modal: true,
                    closeAction: 'hide',
                    layout: 'fit',
                    
                    items: [{
                        xtype: 'form',
                        padding: 5,
                        labelWidth: 50,
                        labelAlign: 'right',

                        defaults: {
                            xtype:'textfield',
                            readOnly: true,
                            width:'90%'
                        },
                        items: [{
                            xtype: 'hidden',
                            name: 'prog_id'
                        },{
                            fieldLabel: '프로그램'
                        },{
                            fieldLabel: '회차'
                        },{
                            xtype: 'compositefield',
                            fieldLabel: '방송기간',
                            layout: 'hbox',
                            items: [{
                                xtype:'datefield',
                                format: 'Y-m-d',
                                width: 90
                            },{
                                xtype: 'displayfield',
                                value: '~'
                            },{
                                xtype: 'datefield',
                                format: 'Y-m-d',
                                width: 90
                            }]
                        },{
                            xtype: 'textarea',
                            fieldLabel: '송출내용'
                        },{
                            fieldLabel: '상단'
                        },{
                            fieldLabel: '하단'
                        },{
                            fieldLabel: '의뢰자'
                        },{
                            fieldLabel: '승인자'
                        }]
                    }],
                    buttonAlign: 'center',
                    buttons: [{
                        text: '승인'
                    },{
                        text: '반려'
                    }]
                });

		this.items = {
			xtype: 'editorgrid',
			frame: true,
			title: '전송 모니터링',
			id: 'scroll_request_grid',
			loadMask: true,
			store: store,
			viewConfig:{
				forceFit: true
			},
			listeners: {
				render: function(self){
					self.store.load();
				},
				cellclick: function(self, rowIndex, columnIndex, e) {
                                        if(columnIndex == 1) {
                                            req_win.show();
                                        } else {
                                            appr_win.show();
                                        }
                                }
			},
			colModel: new Ext.grid.ColumnModel({
				defaultSortable: false,
				columns: [
					{header: "의뢰일시", dataIndex: '' , width: 50, renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s') },
					{header: "프로그램 ID", dataIndex: 'notice_title' },
					{header: "프로그램명", dataIndex: 'notice_content' },
                                        {header: "회차", dataIndex: 'notice_content' },
                                        {header: "의뢰내용", dataIndex: 'notice_content' },
                                        {header: "주조확인", dataIndex: 'notice_content' }
				]
			}),
			sm: new Ext.grid.RowSelectionModel({
				singleSelect:true
			}),
			tbar: [{
                                xtype: 'displayfield',
                                value: '프로그램'
                        },{
                                xtype: 'combo',
                                id: 'req_prog_combo',
                                //width: 80,
                                mode: 'local',
                                triggerAction: 'all',
                                editable: false,
                                displayField: 'd',
                                valueField: 'v',
                                emptyText: '반려목록을 선택하세요',
                                store: new Ext.data.ArrayStore({
                                        fields: [
                                                'd', 'v'
                                        ],
                                        data: [
                                                ['메타데이터 보완 요청','refuse_meta'],
                                                ['인코딩 재작업 요청','refuse_encoding']
                                        ]
                                })
                        },' ',{
                                xtype: 'displayfield',
                                value: '전송기간'
                        },{
                                xtype: 'datefield',
                                id: 'rq_s_date'
                        },{
                                xtype: 'displayfield',
                                value: '~'
                        },{
                                xtype: 'datefield',
                                id: 'rq_e_date'
                        },{
                                xtype: 'button',
                                text: '검색'
                        },'->',{
                                xtype: 'checkbox',
                                boxLabel: '전체'
                        },'-',{
                                xtype: 'checkbox',
                                boxLabel: '전송완료'
                        },'-',{
                                xtype: 'checkbox',
                                boxLabel: '전송중'
                        },'-',{
                                xtype: 'checkbox',
                                boxLabel: '실패'
                        }],
                        bbar: {
				xtype: 'paging',
				pageSize: 20,
				displayInfo: true,
				store: store
			}
		};

		Ariel.Nps.ScrollRequest.superclass.initComponent.call(this);
	}
});