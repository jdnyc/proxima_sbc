/**
 * Created by cerori on 2015-04-01.
 */
Ext.ns('Ariel.Panel.Monitor');
(function () {

    function showTaskDetail(workflow_id, content_id, root_task) {

        Ext.Ajax.request({
            url: '/javascript/ext.ux/viewInterfaceWorkFlow.php',
            params: {
                workflow_id: workflow_id,
                content_id: content_id,
                root_task: root_task,
                screen_width: window.innerWidth,
                screen_height: window.innerHeight
            },
            callback: function (options, success, response) {
                if (success) {
                    try {
                        var r = Ext.decode(response.responseText);
                        r.show();
                    } catch (e) {
                        Ext.Msg.alert(e.name, e.message);
                    }
                } else {
                    Ext.Msg.alert(_text('MN01098'), response.statusText);//'서버 오류'
                }
            }
        });
    }

    Ariel.Panel.Monitor = Ext.extend(Ext.grid.GridPanel, {

        initComponent: function (config) {
            var _this = this;
            var store = new Ext.data.JsonStore({
                url: '/store/get_personal_task.php',
                root: 'data',
                fields: [
                    'workflow_id', 'workflow_name', 'content_title', 'content_id',
                    'count_complete', 'count_error', 'count_processing', 'count_queue', 'total', 'total_progress',
                    'user_id', 'user_name', 'status', 'root_task',
                    {
                        name: 'register', convert: function (v, record) {
                            //console.log(record);
                            return record.user_id + '(' + record.user_name + ')';
                        }
                    },
                    { name: 'start_datetime', type: 'date', dateFormat: 'YmdHis' },
                    { name: 'complete_datetime', type: 'date', dateFormat: 'YmdHis' },
                    { name: 'creation_datetime', type: 'date', dateFormat: 'YmdHis' }
                ]
            });


            Ext.apply(this, {
                defaults: {
                    border: false,
                    margins: '10 10 10 10'
                },
                frame: false,
                //title: _text('MN02128'),//'작업 내역'
                title: '<span class="user_span"><span class="icon_title"><i class="fa fa-list"></i></span><span class="main_title_header">' + _text('MN02128') + '</span></span>',
                id: 'task_grid',
                cls: 'proxima_customize',
                //flex: 1,
                loadMask: true,
                store: store,
                viewConfig: {
                    forceFit: true,
                    emptyText: _text('MSG00148')//결과 값이 없습니다
                },
                listeners: {
                    viewready: function (self) {
                        _this.onSearch();
                    },

                    rowcontextmenu: function (self, row_index, e) {
                        e.stopEvent();

                        self.getSelectionModel().selectRow(row_index);

                        var rowRecord = self.getSelectionModel().getSelected();
                        var workflow_id = rowRecord.get('workflow_id');
                        var content_id = rowRecord.get('content_id');
                        var root_task = rowRecord.get('root_task');


                        var menu = new Ext.menu.Menu({
                            items: [{
                                text: '<span style="position:relative;top:1px;"><i class="fa fa-list" style="font-size:13px;"></i></span>&nbsp;' + _text('MN00241'),//'작업흐름보기'
                                //icon: '/led-icons/chart_organisation.png',
                                handler: function (btn, e) {
                                    showTaskDetail(workflow_id, content_id, root_task);
                                    menu.hide();
                                }
                            }, {
                                hidden: true,
                                text: '<span style="position:relative;top:1px;"><i class="fa fa-check-square-o" style="font-size:13px;"></i></span>&nbsp;' + _text('MN02129'),//'완료 처리'
                                icon: '/led-icons/chart_organisation.png',
                                handler: function (btn, e) {

                                    // console.log(workflow_id, content_id);

                                    // manualTaskComplete(workflow_id, content_id);
                                    menu.hide();
                                }
                            }]
                        });
                        menu.showAt(e.getXY());
                    },

                    rowdblclick: function (self, row_index, e) {
                        var rowRecord = self.getSelectionModel().getSelected();
                        var workflow_id = rowRecord.get('workflow_id');
                        var content_id = rowRecord.get('content_id');
                        var root_task = rowRecord.get('root_task');

                        showTaskDetail(workflow_id, content_id, root_task);
                    }
                },
                colModel: new Ext.grid.ColumnModel({
                    defaults: {
                        //menuDisabled: true
                    },
                    columns: [
                        //'작업 유형'
                        {
                            header: _text('MN01026'),
                            dataIndex: 'interface_work_type_nm',
                            align: 'center',
                            width: 80,
                            hidden: true
                        },
                        //'작업유형 명'
                        { header: _text('MN01028'), dataIndex: 'workflow_name', width: 120 },
                        // '제목'
                        { header: _text('MN00249'), dataIndex: 'content_title', width: 250 },
                        //'등록자'
                        { header: _text('MN00120'), dataIndex: 'register', width: 70 },
                        //'상태'
                        { header: _text('MN00138'), dataIndex: 'status', width: 70 },
                        //'완료 건/총 건'
                        {
                            header: _text('MN02130'),
                            dataIndex: 'count_complete',
                            align: 'center',
                            width: 80,
                            renderer: function (value, meta, record, rowIndex, colIndex, store, pct) {
                                return value + ' / ' + record.get('total');
                            }
                        },
                        //'진행작업 명'
                        { header: _text('MN01028'), dataIndex: 'task_job_name', width: 200, hidden: true },
                        //'진행률(%)'
                        new Ext.ux.ProgressColumn({
                            header: _text('MN00261'),
                            width: 90,
                            dataIndex: 'total_progress',
                            align: 'center',
                            renderer: function (value, meta, record, rowIndex, colIndex, store, pct) {
                                return Ext.util.Format.number(pct, "0%");
                            }
                        }),
                        //'작업상태'
                        { header: _text('MN00237'), dataIndex: 'task_status_nm', align: 'center', width: 80, hidden: true },
                        //'작업생성일'
                        {
                            header: _text('MN01023'),
                            dataIndex: 'creation_datetime',
                            align: 'center',
                            renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s'),
                            width: 120
                        }
                    ]
                }),
                sm: new Ext.grid.RowSelectionModel({
                    singleSelect: true
                }),
                tbar: [{
                    hidden: true,
                    //icon: '/led-icons/arrow_refresh.png',
                    text: '<span style="position:relative;top:1px;"><i class="fa fa-refresh" style="font-size:13px;"></i></span>&nbsp;' + _text('MN00390'),//'새로고침'
                    handler: function (btn, e) {
                        Ext.getCmp('task_grid').getStore().reload();
                    }
                }, {
                    hidden: true,
                    xtype: 'combo',
                    id: 'task_type',
                    typeAhead: true,
                    triggerAction: 'all',
                    mode: 'local',
                    width: 120,
                    editable: false,
                    value: 'all',
                    emptyText: '작업구분',
                    store: new Ext.data.SimpleStore({
                        fields: [
                            'type_id',
                            'type_nm'
                        ],
                        data: [
                            ['all', '전체'],
                            ['regist', '등록'],
                            ['transfer', '전송']
                        ]
                    }),
                    valueField: 'type_id',
                    displayField: 'type_nm',
                    listeners: {
                        select: function () {
                        }
                    }
                }, {
                    hidden: true,
                    xtype: 'tbspacer',
                    width: '20'
                }, {
                    hidden: true,
                    xtype: 'combo',
                    id: 'task_status',
                    typeAhead: true,
                    triggerAction: 'all',
                    mode: 'local',
                    width: 120,
                    editable: false,
                    value: 'all',
                    emptyText: '상태구분',
                    store: new Ext.data.SimpleStore({
                        fields: [
                            'status_id',
                            'status_nm'
                        ],
                        data: [['all', '전체'],

                        ['pending', '작업대기중'],
                        ['queue', '작업대기'],
                        ['assigning', '작업할당중'],
                        ['processing', '작업중'],
                        ['complete', '작업완료'],
                        ['cancel', '작업취소'],
                        ['canceled', '작업취소'],
                        ['error', '실패']
                        ]
                    }),
                    valueField: 'status_id',
                    displayField: 'status_nm'
                }, {
                    hidden: true,
                    xtype: 'tbspacer',
                    width: '20'
                }, {
                    xtype: 'combo',
                    id: 'task_filter_combo',
                    itemId: 'filter',
                    mode: 'local',
                    store: [
                        [1, _text('MN02131')],//'전체 자료'
                        [2, _text('MN02132')]//'내 자료'
                    ],
                    value: 2,
                    width: 90,
                    triggerAction: 'all',
                    typeAhead: true,
                    editable: false,
                    listeners: {
                        select: function () {
                            _this.onSearch();
                        }
                    }
                }, {
                    xtype: 'tbspacer',
                    width: 10
                }, {
                    hidden: true,
                    xtype: 'combo',
                    id: 'task_search_key',
                    typeAhead: true,
                    triggerAction: 'all',
                    mode: 'local',
                    width: 80,
                    editable: false,
                    value: 'content_title',
                    store: [
                        ['content_title', '제목']//,
                        //['filename', '파일명']
                    ]
                }, ' ', {
                    xtype: 'datefield',
                    id: 'task_grid_search_st_dt',
                    format: 'Y-m-d',
                    width: 90,
                    value: new Date().add(Date.DAY, -5).format('Y-m-d')
                }, _text('MN00183'),//' 부터 ' 
                {
                    id: 'task_grid_search_en_dt',
                    xtype: 'datefield',
                    format: 'Y-m-d',
                    width: 90,
                    value: new Date()
                }, '-', {
                    xtype: 'displayfield',
                    value: _text('MN00249'),//'제목'
                    hidden: true
                }, {
                    xtype: 'tbspacer',
                    width: 5,
                    hidden: true
                }, {
                    id: 'task_search_value',
                    xtype: 'textfield',
                    width: 300,
                    emptyText: _text('MN00249'),
                    listeners: {
                        specialkey: function (field, e) {
                            if (e.getKey() == e.ENTER) {
                                _this.onSearch.call(_this);
                            }
                        }
                    }
                }, {
                    xtype: 'button',
                    //icon: '/led-icons/find.png',
                    id: 'task_grid_btn_search',
                    text: '<span style="position:relative;" title="' + _text('MN00037') + '"><i class="fa fa-search" style="font-size:13px;color:white;"></i></span>',//'조회'
                    handler: _this.onSearch,
                    scope: _this
                }, '->', {
                    xtype: 'checkbox',
                    //boxLabel: '자동 새로고침',
                    boxLabel: _text('MN00229'),
                    checked: false,
                    listeners: {
                        check: function (self, checked) {
                            var TaskListTabPanel = self.ownerCt.ownerCt;
                            if (checked) {
                                TaskListTabPanel.runAutoReload(TaskListTabPanel);
                            }
                            else {
                                TaskListTabPanel.stopAutoReload();
                            }
                        },
                        render: function (self) {
                            var TaskListTabPanel = self.ownerCt.ownerCt;
                            TaskListTabPanel.stopAutoReload();
                        }
                    }
                }],
                runAutoReload: function (thisRef) {
                    this.stopAutoReload();

                    this.intervalID = setInterval(function (e) {
                        if (thisRef) {
                            thisRef.getStore().reload();
                        }
                    }, 5000);
                },

                stopAutoReload: function () {
                    if (this.intervalID) {
                        clearInterval(this.intervalID);
                    }
                },
                bbar: {
                    xtype: 'paging',
                    pageSize: 15,
                    displayInfo: true,
                    store: store
                }
            }, config || {});


            Ariel.Panel.Monitor.superclass.initComponent.call(this);
        },

        onSearch: function () {

            var key = Ext.getCmp('task_search_key').getValue();
            var value = Ext.getCmp('task_search_value').getValue();
            var filter = this.getTopToolbar().getComponent('filter').getValue();
            var stdt = Ext.getCmp('task_grid_search_st_dt').getValue().format('Ymd');
            var endt = Ext.getCmp('task_grid_search_en_dt').getValue().format('Ymd');

            this.getStore().load({
                params: {
                    key: key,
                    value: value,
                    filter: filter,
                    stdt: stdt,
                    endt: endt
                }
            });
        }
    });

    Ext.reg('mainmonitor', Ariel.Panel.Monitor);
})();