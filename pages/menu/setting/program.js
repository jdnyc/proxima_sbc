(function() {

    var mask,
        main,
        prefix_url = 'pages/menu/setting',
        prefix_id = Ext.id(),
        text_insert_empty_episode = '선택하신 프로그램에 회차가 존재하지 않습니다. <br />프로그램만 추가하시겠습니까?',
        text_insert_episode_has_not_selection = '회차를 선택하지 않으셨습니다. <br />전체 회차를 추가 또는 업데이트 하시겠습니까?',
        text_insert_episode_has_selection = '선택하신 회차를 추가 하시겠습니까?',
        text_delete_deny_root = '최상의는 삭제할 수 없습니다.',
        text_delete_all = '선택하신 제작 프로그램의 전체 회차까지 삭제하시겠습니까?',
        text_delete_episode = '선택하신 제작 프로그램의 회차를 삭제하시겠습니까?';

    // BIS 프로그램 조회
    var Program = Ext.extend(Ext.grid.GridPanel, {
        id: prefix_id + '.bis.program',
        isClearSelections: true,

        initComponent: function(config) {
            var _this = this,
                config = config || {};


            Ext.apply(this, config, {
                loadMask: true,
                // tbar: [{
                //     xtype: 'tbtext',
                //     text: '프로그램명'
                // }, {
                //     xtype: 'textfield'
                // }, {
                //     text: '검색'
                // }],
                cm: new Ext.grid.ColumnModel({
                    defaults: {
                        menuDisabled: true
                    },
                    columns: [{
                        header: '프로그램명',
                        dataIndex: 'pgm_nm'
                    }]
                }),
                sm: new Ext.grid.RowSelectionModel({
                    singleSelect: true
                }),
                viewConfig: {
                    forceFit: true,
                    emptyText: '데이터가 없습니다.'
                },
                store: new Ext.data.JsonStore({
                    autoLoad: false,
                    url: '/store/bis/get_program_list.php',
                    root: 'data',
                    totalProperty: 'total',
                    sortInfo: {
                        field: 'pgm_nm',
                        direction: 'ASC'
                    },
                    fields: [
                        'pgm_id',
                        'pgm_nm',
                        'info_grd',
                        'main_role',
                        'pgm_info',
                        'status'
                    ]
                })
            });

            Program.superclass.initComponent.call(this);
        },

        afterRender: function() {
            Program.superclass.afterRender.call(this);

            this.selModel.on('rowselect', this.onSelectionChange, this);
        },

        onSelectionChange: function(selModel, rowIndex, record) {
            if (this.isClearSelections) {
                this.clearSelections();
            }
            this.reloadEpisode(record);
            this.fillForm(record);

            this.isClearSelections = true;
        },

        clearSelections: function() {
            Ext.getCmp(prefix_id + '.program').selModel.clearSelections();
        },

        fillForm: function(record) {
            Ext.getCmp(prefix_id + '.category.info.form').getForm().loadRecord(record);
        },

        reloadEpisode: function(record) {
            Ext.getCmp(prefix_id + '.bis.episode').getStore().load({
                params: {
                    pgm_id: record.get('pgm_id')
                }
            });
        }
    });

    main = new Ext.Panel({
        layout: {
            type: 'hbox',
            align: 'stretch'
        },
        items: [{
            flex: 1,
            id: prefix_id + '.program',
            xtype: 'treegrid',
            columns: [{
                header: '제작 프로그램',
                dataIndex: 'title',
                width: 300
            }, {
                header: '저장 경로',
                dataIndex: 'path',
                width: 200
            }],
            dataUrl: prefix_url + '/get_program.php',
            listeners: {
                afterrender: function(self) {
                    self.selModel.on('selectionchange', self.onSelectionChange);
                }
            },

            onSelectionChange: function(self, newNode, oldNew) {
                var node;

                if (newNode) {
                    var cmp = Ext.getCmp(prefix_id + '.bis.program'),
                        rowIndex;

                    rowIndex = cmp.getStore().find('pgm_id', newNode.attributes.path);
                    if (rowIndex) {
                        cmp.isClearSelections = false;
                        cmp.selModel.selectRow(rowIndex);
                        cmp.getView().focusRow(rowIndex);
                    }
                }
            }
        }, {
            layout: {
                type: 'vbox',
                align: 'center',
                pack: 'center'
            },
            width: 50,
            items: [{
                xtype: 'button',
                width: '80%',
                height: '45%',
                margins: '0 0 30 0',
                text: '<span style="font-weight: bold;font-size: 28px;">&lt;</span>',
                handler: function() {
                    var sm = Ext.getCmp(prefix_id + '.bis.program').selModel,
                        episodeGrid = Ext.getCmp(prefix_id + '.bis.episode'),
                        hasProgram,
                        data,
                        text;

                    if (sm.hasSelection()) {
                        mask =  new Ext.LoadMask(main.getEl());

                        data = sm.getSelected().data;
                        data.episode = [];

                        // 회차 선택 유무 처리
                        if (episodeGrid.getStore().getCount() === 0) {
                            text = text_insert_empty_episode;
                        } else if ( ! episodeGrid.selModel.hasSelection()) {
                            text = text_insert_episode_has_not_selection;
                            Ext.each(episodeGrid.getStore().getRange(), function(record) {
                                data.episode.push(record.data);
                            });
                        } else {
                            text = text_insert_episode_has_selection;
                            Ext.each(episodeGrid.selModel.getSelections(), function(record) {
                                data.episode.push(record.data);
                            });
                        }

                        Ext.Msg.show({
                            title: '확인',
                            msg: text,
                            buttons: Ext.Msg.OKCANCEL,
                            fn: function(btnId) {
                                if (btnId === 'ok') {
                                    mask.show();
                                    Ext.Ajax.request({
                                        url: prefix_url + '/save.php',
                                        params: Ext.encode(data),
                                        callback: function(self) {
                                            mask.hide();

                                            var cmp = Ext.getCmp(prefix_id + '.program');
                                            var node = cmp.getRootNode().findChild('path', data.pgm_id);
                                            if (node) {
                                                cmp.getLoader().load(node, function(node) {
                                                    node.expand();
                                                });
                                            } else {
                                                cmp.getLoader().load(cmp.getRootNode());
                                            }
                                        }
                                    });
                                }
                            }
                        });
                    }
                }
            }, {
                xtype: 'button',
                width: '80%',
                height: '45%',
                text: '<span style="font-weight: bold;font-size: 28px;">&gt;</span>',
                handler: function() {
                    var sm = Ext.getCmp(prefix_id + '.program').selModel,
                        node,
                        text;

                    node = sm.getSelectedNode();
                    if (node) {
                        mask =  new Ext.LoadMask(main.getEl());

                        // 선택 유무 처리
                        if (node.getDepth() === 0) {
                            Ext.Msg.alert('확인', text_delete_deny_root);
                            return;
                        } else if (node.getDepth() === 1) {
                            text = text_delete_all;
                        } else if (node.getDepth() === 2) {
                            text = text_delete_episode;
                        } else {
                            return;
                        }

                        Ext.Msg.show({
                            title: '확인',
                            msg: text,
                            buttons: Ext.Msg.OKCANCEL,
                            fn: function(btnId) {
                                if (btnId === 'ok') {
                                    mask.show();
                                    Ext.Ajax.request({
                                        url: prefix_url + '/delete.php',
                                        params: Ext.encode(node.attributes),
                                        callback: function(self) {
                                            mask.hide();
                                            node.remove();
                                        }
                                    });
                                }
                            }
                        });
                    }
                }
            }]
        }, {
            width: 400,
            border: false,
            layout: {
                type: 'vbox',
                align: 'stretch'
            },
            items: [{
                flex: 1,
                layout: {
                    type: 'vbox',
                    align: 'stretch'
                },
                items: [{
                    id: prefix_id + '.category.info.form',
                    xtype: 'form',
                    frame: true,
                    defaults: {
                        xtype: 'textfield',
                        anchor: '100%'
                    },
                    items: [{
                        name: 'pgm_nm',
                        fieldLabel: '프로그램명',
                        readOnly: false
                    }, {
                        name: 'pgm_id',
                        fieldLabel: '저장 경로',
                        readOnly: false
                    }]
                }, new Program({
                    flex: 1
                })]
            }, {
                flex: 1,
                id: prefix_id + '.bis.episode',
                title: '회차',
                xtype: 'grid',
                loadMask: true,
                cm: new Ext.grid.ColumnModel({
                    defaults: {
                        menuDisabled: true
                    },
                    columns: [{
                        header: '회차',
                        dataIndex: 'epsd_no',
                        width: 30
                    }, {
                        header: '부제명',
                        dataIndex: 'epsd_nm'
                    }, {
                        header: '방송일자',
                        dataIndex: 'brd_run',
                        width: 70
                    }]
                }),
                viewConfig: {
                    forceFit: true,
                    emptyText: '데이터가 없습니다.'
                },
                store: new Ext.data.JsonStore({
                    url: '/store/bis/get_episode_list.php',
                    root: 'data',
                    totalProperty: 'total',
                    sortInfo: {
                        field: 'epsd_no',
                        direction: 'ASC'
                    },
                    fields: [
                    'pgm_id',   // 프로그램ID
                    'pgm_nm',   // 프로그램
                    'house_no',   // 소재ID
                    'epsd_nm',   // 부제 (일단 회차명을 부제로)
                    {name: 'epsd_no', type: 'int'},   // 회차
                    {name: 'brd_ymd', type:'date', dateFormat:'Ymd'},    // 방송일자
                    'delib_grd_code',   // 등급분류
                    'pgm_info',   // 내용
                    'main_role'   // 담당PD
                    ]
                }),
                // tbar: [{
                //     xtype: 'tbtext',
                //     text: '부제명'
                // }, {
                //     xtype: 'textfield'
                // }, {
                //     text: '검색'
                // }]
            }]
        }]
    });

    return main;
})()