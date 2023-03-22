(function () {
    Ext.ns('Ariel.glossary');

    Ariel.glossary.FieldListGrid = Ext.extend(Ext.Container, {
        pageSize: 25,

        // property
        permission_code: 'data_dic.field',
        loadMask: true,
        stripeRows: true,
        frame: true,
        cls: 'grid_title_customize proxima_customize',
        layout: {
            type: 'vbox',
            align: 'stretch'
        },
        defaults: {
            flex: 1
        },


        initComponent: function () {
            var _this = this;

            // var components = [
            //     '/custom/ktv-nps/js/glossary/inputFormWindow.js'
            // ];
            // Ext.Loader.load(components);
            var fieldTabPanel = _this._showFieldTabPanel();
            this.items = [
                _this._showFieldGrid(),

                fieldTabPanel

            ];

            Ariel.glossary.FieldListGrid.superclass.initComponent.call(this);
        },
        _getPermission: function (permissions, current) {
            var rtn = false;
            Ext.each(permissions, function (permission) {
                if (permission == '*') {
                    rtn = true;
                } else if (permission == current) {
                    rtn = true;
                }
            });
            return rtn;
        },
        _initializeByPermission: function (permissions) {
            var _this = this;
            try {
                var filedSelect = _this.get(0);
                // if (this._getPermission(permissions, 'add')) {
                if (true) {
                    filedSelect.getTopToolbar().insertButton(0, {
                        xtype: 'a-iconbutton',
                        text: '추가',
                        handler: function (self) {
                            new Ariel.glossary.inputFormWindow({
                                title: '추가',
                                type: 'add',
                                method: 'POST',
                                action: 'field',
                                status: 'ACCEPT',
                                apiUrl: Ariel.glossary.UrlSet.field,
                                onAfterSave: function () {
                                    filedSelect.store.reload();

                                }
                            }).show();
                        }
                    });
                    // } else {
                } if (false) {
                    filedSelect.getTopToolbar().insertButton(0, {
                        xtype: 'a-iconbutton',
                        text: '추가요청',
                        handler: function (self) {
                            new Ariel.glossary.inputFormWindow({
                                title: '추가',
                                type: 'add',
                                method: 'POST',
                                action: 'field',
                                status: 'REQUEST',
                                apiUrl: Ariel.glossary.UrlSet.field,
                                onAfterSave: function () {
                                    filedSelect.store.reload();
                                }
                            }).show();
                        }
                    });
                }
                // if (this._getPermission(permissions, 'edit')) {
                if (true) {
                    filedSelect.getTopToolbar().insertButton(1, { xtype: 'tbspacer' });

                    filedSelect.getTopToolbar().insertButton(2, {
                        xtype: 'a-iconbutton',
                        text: '수정',
                        handler: function (self) {
                            var sm = filedSelect.getSelectionModel();

                            if (sm.hasSelection()) {
                                var getRecord = sm.getSelected();

                                new Ariel.glossary.inputFormWindow({
                                    title: '수정',
                                    type: 'edit',
                                    getRecord: getRecord,
                                    method: 'PUT',
                                    action: 'field',
                                    apiUrl: Ariel.glossary.UrlSet.fieldIdParam(getRecord.id),
                                    onAfterSave: function () {
                                        filedSelect.store.reload();
                                    }
                                }).show();
                            } else {
                                Ext.Msg.alert('알림', '수정하실 목록을 선택해주세요.');
                            }
                        }
                    });

                }

                // if (this._getPermission(permissions, 'del')) {
                if (true) {
                    filedSelect.getTopToolbar().insertButton(3, { xtype: 'tbspacer' });

                    filedSelect.getTopToolbar().insertButton(4, {
                        xtype: 'a-iconbutton',
                        text: '삭제',
                        handler: function (self) {
                            var sm = filedSelect.getSelectionModel();

                            if (sm.hasSelection()) {
                                var getRecord = sm.getSelected();

                                Ext.Msg.show({
                                    title: '알림',
                                    msg: '삭제하시겠습니까?',
                                    buttons: Ext.Msg.OKCANCEL,
                                    fn: function (btnId, text, opts) {
                                        if (btnId == 'ok') {
                                            Ext.Ajax.request({
                                                method: 'DELETE',
                                                url: Ariel.glossary.UrlSet.fieldIdParam(getRecord.id),
                                                callback: function (opts, success, resp) {
                                                    if (success) {
                                                        try {
                                                            filedSelect.store.reload();
                                                        } catch (e) {
                                                            Ext.Msg.alert(e['name'], e['message']);
                                                        }
                                                    } else {
                                                        Ext.Msg.alert('status: ' + resp.status, resp.statusText);
                                                    }
                                                }
                                            });
                                        }
                                    }
                                })
                            } else {
                                Ext.Msg.alert('알림', '삭제하실 목록을 선택해주세요.');
                            }
                        }
                    });
                }

                // if (this._getPermission(permissions, 'accept')) {
                if (false) {
                    filedSelect.getTopToolbar().insertButton(5, { xtype: 'tbspacer' });
                    filedSelect.getTopToolbar().insertButton(6, {
                        xtype: 'a-iconbutton',
                        text: '승인',
                        handler: function (self) {
                            var sm = filedSelect.getSelectionModel();
                            if (sm.hasSelection()) {
                                var getRecord = sm.getSelected();
                                Ext.Msg.show({
                                    title: '알림',
                                    msg: '승인 하시겠습니까?',
                                    buttons: Ext.Msg.OKCANCEL,
                                    fn: function (btnId, text, opts) {

                                        if (btnId == 'ok') {
                                            Ariel.glossary.ChangeStatus.changeStatus(
                                                'ACCEPT', Ariel.glossary.UrlSet.fieldIdParam(getRecord.id), filedSelect.getStore()
                                            );
                                        }
                                    }
                                })
                            } else {
                                Ext.Msg.alert('알림', '승인하실 목록을 선택해주세요.');
                            }
                        }
                    });
                }
                // if (this._getPermission(permissions, 'refuse')) {
                if (false) {
                    filedSelect.getTopToolbar().insertButton(7, { xtype: 'tbspacer' });
                    filedSelect.getTopToolbar().insertButton(8, {
                        xtype: 'a-iconbutton',
                        text: '반려',
                        handler: function (self) {
                            var sm = filedSelect.getSelectionModel();
                            if (sm.hasSelection()) {
                                var getRecord = sm.getSelected();

                                Ext.Msg.show({
                                    title: '알림',
                                    msg: '반려 하시겠습니까?',
                                    buttons: Ext.Msg.OKCANCEL,
                                    fn: function (btnId, text, opts) {
                                        if (btnId == 'ok') {
                                            Ariel.glossary.ChangeStatus.changeStatus(
                                                'REFUSE', Ariel.glossary.UrlSet.fieldIdParam(getRecord.id), filedSelect.getStore()
                                            );
                                        }
                                    }
                                });

                            } else {
                                Ext.Msg.alert('알림', '반려하실 목록을 선택해주세요.');
                            }
                        }
                    });
                }
            } catch (e) {
                Ext.Msg.alert(e['name'], e['message']);
            }
            filedSelect.getTopToolbar().doLayout();
        },

        // 코드 그리드 함수
        _showFieldGrid: function () {
            var _this = this;


            // var storeUrl = '/pages/menu/glossary/store/field_store.php';
            var store = new Ext.data.JsonStore({
                // autoDestroy: true,
                remoteSort: true,
                restful: true,
                proxy: new Ext.data.HttpProxy({
                    method: 'GET',
                    url: Ariel.glossary.UrlSet.field,
                    type: 'rest'
                }),
                totalProperty: 'total',
                // idProperty: 'id',
                root: 'data',
                fields: [
                    { name: 'id', type: 'int' },
                    'field_nm',
                    'field_eng_nm',
                    'domain',
                    'dc',
                    { name: 'regist_dt', type: 'date' },
                    { name: 'updt_dt', type: 'date' }
                ]
            });
            var cm = new Ext.grid.ColumnModel({
                defaults: {
                    align: 'center',
                    menuDisabled: true,
                    sortable: false
                },
                columns: [
                    // new Ext.grid.RowNumberer({
                    //     header: '순번',
                    //     width: 60,
                    // }),
                    { header: 'ID', dataIndex: 'id', sortable: true },//, width:70
                    { header: '필드명', dataIndex: 'field_nm', sortable: true, align: 'left' },
                    { header: '필드ID', dataIndex: 'field_eng_nm', sortable: true, align: 'left' },
                    {
                        header: '도메인명', dataIndex: 'domain', dataIndex: 'domain',
                        renderer: function (value, metaData, record, rowIndex, colIndex, store) {
                            if (value == null) {
                                return null;
                            } else {
                                return value.domn_nm;
                            }
                        },
                        sortable: true,
                        align: 'left'
                    }, {
                        header: '데이터타입', dataIndex: 'domain',
                        renderer: function (value, metaData, record, rowIndex, colIndex, store) {
                            if (Ext.isEmpty(value)) {
                                return null;
                            } else {
                                if (Ext.isEmpty(value.data_lt)) {
                                    return value.data_ty;
                                } else {
                                    return value.data_ty + '(' + value.data_lt + ')';
                                }
                            }
                        }
                    },
                    { header: '설명', dataIndex: 'dc', align: 'left' },
                    { header: '생성일시', dataIndex: 'regist_dt', renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s') },
                    { header: '수정일시', dataIndex: 'updt_dt', renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s') },

                ]
            });
            var searchField = new Ariel.glossary.searchField({
                store: store,
                pageSize: _this.pageSize,
            });

            var fieldGrid = new Ext.grid.GridPanel({
                title: '<span class="user_span"><span class="icon_title"><i class=""></i></span><span class="main_title_header">' + '필드 관리' + '</span></span>',
                loadMask: true,
                stripeRows: true,
                frame: false,
                autoWidth: true,
                layout: 'fit',
                viewConfig: {
                    emptyText: '목록이 없습니다.',
                    forceFit: true,
                    border: false,
                },
                cls: 'grid_title_customize proxima_customize',
                store: store,
                cm: cm,
                tbar: ['->', ' ',
                    searchField
                ],
                listeners: {
                    afterrender: function (self) {
                        self.store.load({
                            params: {
                                limit: _this.pageSize
                            }
                        });
                    },
                    rowclick: function (self, rowIndex, e) {

                        var tabPanel = _this.items.get(1);
                        if (self.selModel.hasSelection()) {
                            try {
                                /**
                                 * 버튼 있었을떄 비활성화 하려고 해놓은거 같은데.,..
                                 */
                                // console.log(_this.items.get(1).items.get(0).items.items);
                                // if (_this.btnAction == 0) {
                                //     Ext.each(_this.items.get(1).items.get(0).items.items, function (i, idx, o) {
                                //         console.log(i);
                                //         i.enable();
                                //     });
                                // }
                                var fieldId = self.getSelectionModel().getSelected().id;
                                clickTab = tabPanel.activeTab.itemId;
                                if (clickTab == 'info') {
                                    var params = 'Y'
                                } else {
                                    var params = 'N'
                                }
                                tabPanel.getItem(clickTab).get(0).store.proxy.setUrl('/api/v1/data-dic-fields/' + fieldId + '/columns');
                                tabPanel.getItem(clickTab).get(0).store.load({
                                    params: {
                                        field_id:fieldId,
                                        limit: _this.pageSize,
                                        std_yn: params
                                    }
                                });
                            } catch (e) {
                                Ext.Msg.alert(e['name'], e['message']);
                            }
                        } else {
                            Ext.Msg.alert('알림', '목록을 선택해주세요.');
                        }
                    },
                    rowdblclick: function (self, rowIndex, e) {
                        //수정창
                        var filedSelect = _this.get(0);
                        var sm = filedSelect.getSelectionModel();


                        var getRecord = sm.getSelected();

                        new Ariel.glossary.inputFormWindow({
                            title: '수정',
                            type: 'edit',
                            getRecord: getRecord,
                            method: 'PUT',
                            action: 'field',
                            apiUrl: Ariel.glossary.UrlSet.fieldIdParam(getRecord.id),
                            onAfterSave: function () {
                                filedSelect.store.reload();
                            }
                        }).show();
                    }
                },
                bbar: new Ext.PagingToolbar({
                    pageSize: _this.pageSize,
                    store: store
                })
            });
            return fieldGrid;
        },
        _showFieldTabPanel: function () {
            var _this = this;

            var fieldPanel = new Ext.TabPanel({
                frame: false,
                enableTabScroll: true,
                border: false,

                activeTab: 0,

                items: [{
                    id: 'infoGridTest',
                    xtype: 'container',
                    title: '참조 정보',
                    layout: 'fit',
                    itemId: 'info',
                    stdYn: 'Y',
                    items: _this._tabClickInfoGrid()
                }, {
                    xtype: 'container',
                    title: '비표준 정보',
                    itemId: 'nonInfo',
                    stdYn: 'N',
                    layout: 'fit',
                    items: _this._tabClickNonInfoGrid()
                }],
                listeners: {
                    tabchange: function (self, tab) {
                        var sm = _this.items.get(0).getSelectionModel();
                        if (sm.hasSelection()) {
                            try {
                                var fieldId = sm.getSelected().id;

                                tab.get(0).store.proxy.setUrl('/api/v1/data-dic-fields/' + fieldId + '/columns');
                                tab.get(0).store.load({
                                    params: {
                                        limit: _this.pageSize,
                                        std_yn: tab.stdYn
                                    }
                                });
                            } catch (e) {
                                Ext.Msg.alert(e['name'], e['message']);
                            }
                        }

                    }
                }
            });
            return fieldPanel;
        },
        // 참조 정보
        _tabClickInfoGrid: function () {
            var _this = this;
            var store = new Ext.data.JsonStore({
                // autoDestroy: true,
                restful: true,
                proxy: new Ext.data.HttpProxy({
                    method: 'GET',
                    url: '/api/v1/data-dic-fields/0/columns',
                    type: 'rest'
                }),
                totalProperty: 'total',
                // idProperty: 'id',
                root: 'data',
                fields: [
                    { name: 'id', type: 'int' },
                    'column_nm',
                    'column_eng_nm',
                    'table',
                    { name: 'regist_dt', type: 'date' },
                    { name: 'updt_dt', type: 'date' }

                ]
            });
            var cm = new Ext.grid.ColumnModel({
                defaults: {
                    align: 'center',
                    menuDisabled: true,
                    sortable: false
                },
                columns: [
                    // new Ext.grid.RowNumberer({
                    //     header: '순번',
                    //     width: 60,
                    // }),
                    { header: 'ID', dataIndex: 'id' },
                    {
                        header: '테이블명', dataIndex: 'table', renderer: function (value, metaData, record, rowIndex, colIndex, store) {
                            if (value == null) {
                                return null;
                            } else {
                                return value.table_nm;
                            }
                        },
                        align: 'left'
                    },
                    {
                        header: '테이블ID', dataIndex: 'table', renderer: function (value, metaData, record, rowIndex, colIndex, store) {
                            if (value == null) {
                                return null;
                            } else {
                                return value.table_eng_nm;
                            }
                        },
                        align: 'left'
                    },
                    { header: '컬럼명', dataIndex: 'column_nm', align: 'left' },
                    { header: '컬럼ID', dataIndex: 'column_eng_nm', align: 'left' },
                    { header: '생성일시', dataIndex: 'regist_dt', renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s') },
                    { header: '수정일시', dataIndex: 'updt_dt', renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s') }
                ]

            });
            var searchField = new Ariel.glossary.searchField({
                store: store,
                // width:320,
                pageSize: _this.pageSize,
            });
            var nonInfoGrid = new Ext.grid.GridPanel({
                id: 'infoGrid',
                loadMask: true,
                stripeRows: true,
                frame: false,
                viewConfig: {
                    emptyText: '목록이 없습니다.',
                    border: false,
                },
                cls: 'grid_title_customize proxima_customize',
                store: store,
                cm: cm,
                tbar: [
                    '->',
                    searchField
                ],
                bbar: new Ext.PagingToolbar({
                    pageSize: _this.pageSize,
                    store: store,
                })
            });
            return nonInfoGrid;
        },
        // 비표준 정보
        _tabClickNonInfoGrid: function () {
            var _this = this;

            var store = new Ext.data.JsonStore({
                // autoDestroy: true,
                restful: true,
                proxy: new Ext.data.HttpProxy({
                    method: 'GET',
                    url: '/api/v1/data-dic-fields/0/columns?std_yn=N',
                    type: 'rest'
                }),
                totalProperty: 'total',
                // idProperty: 'id',
                root: 'data',
                fields: [
                    { name: 'id', type: 'int' },
                    'column_nm',
                    'column_eng_nm',
                    'table',
                    { name: 'regist_dt', type: 'date' },
                    { name: 'updt_dt', type: 'date' }

                ]
            });
            var cm = new Ext.grid.ColumnModel({
                defaults: {
                    align: 'center',
                    menuDisabled: true,
                    sortable: false
                },
                columns: [
                    // new Ext.grid.RowNumberer({
                    //     header: '순번',
                    //     width: 60,
                    // }),
                    { header: 'ID', dataIndex: 'id' },
                    {
                        header: '테이블명', dataIndex: 'table', renderer: function (value, metaData, record, rowIndex, colIndex, store) {
                            if (value == null) {
                                return null;
                            } else {
                                return value.table_nm;
                            }
                        },
                        align: 'left'
                    },
                    {
                        header: '테이블ID', dataIndex: 'table', renderer: function (value, metaData, record, rowIndex, colIndex, store) {
                            if (value == null) {
                                return null;
                            } else {
                                return value.table_eng_nm;
                            }
                        },
                        align: 'left'
                    },
                    { header: '컬럼명', dataIndex: 'column_nm', align: 'left' },
                    { header: '컬럼ID', dataIndex: 'column_eng_nm', align: 'left' },
                    { header: '생성일시', dataIndex: 'regist_dt', renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s') },
                    { header: '수정일시', dataIndex: 'updt_dt', renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s') }
                ]

            });
            var searchField = new Ariel.glossary.searchField({
                store: store,
                // width:320,
                pageSize: _this.pageSize,
            });
            var infoGrid = new Ext.grid.GridPanel({
                loadMask: true,
                stripeRows: true,
                frame: false,
                viewConfig: {
                    emptyText: '목록이 없습니다.',
                    border: false,
                },
                cls: 'grid_title_customize proxima_customize',
                store: store,
                cm: cm,
                tbar: [
                    '->',
                    searchField
                ],
                bbar: new Ext.PagingToolbar({
                    pageSize: _this.pageSize,
                    store: store,
                }),
                listeners: {
                    afterrender: function (self) {
                        self.store.load({
                            params: {
                                limit: _this.pageSize
                            }
                        });
                    }
                }
            });
            return infoGrid;
        }
    })

    return new Ariel.glossary.FieldListGrid();
})()