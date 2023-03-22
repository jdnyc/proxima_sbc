(function () {
    Ext.ns('Ariel.glossary');

    Ariel.glossary.TableListGrid = Ext.extend(Ext.Panel, {
        tableId:0,
        pageSize: 50,

        // property
        permission_code: 'data_dic.table',
        // permission_code: 'data_dic.word',
        title: '<span class="user_span"><span class="icon_title"><i class=""></i></span><span class="main_title_header">' + '테이블' + '</span></span>',
        loadMask: true,
        stripeRows: true,
        frame: false,


        viewConfig: {
            emptyText: '목록이 없습니다.',
            forceFit: true,
            border: false,
        },
        cls: 'grid_title_customize proxima_customize',

        layout: {
            type: 'Hbox',
            align: 'stretch'
            // align: 'stretchmax'
        },
        defaults: {
            flex: 1
        },

        initComponent: function () {
            var _this = this;
            var components = [
                '/custom/ktv-nps/js/glossary/inputFormWindow.js'
            ];
            Ext.Loader.load(components);

            _this.items = [
                _this._showTableGrid(),
                _this._showColumnGrid()
            ];


            Ariel.glossary.TableListGrid.superclass.initComponent.call(_this);
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
            try {
                var _this = this;
                var tableSelect = _this.get(0);
                var columnSelect = _this.get(1);
                //table toolbar
                // if (this._getPermission(permissions, 'add')) {

                if (true) {
                    tableSelect.getTopToolbar().insertButton(0, {
                        xtype: 'a-iconbutton',
                        text: '추가',
                        handler: function (self) {
                            new Ariel.glossary.inputFormWindow({
                                title: '추가',
                                type: 'add',
                                action: 'table',
                                method: 'POST',
                                status: 'ACCEPT',
                                apiUrl: Ariel.glossary.UrlSet.table,
                                onAfterSave: function () {
                                    tableSelect.store.reload();

                                }
                            }).show();
                        }
                    });
                    // } else {
                } if (false) {
                    tableSelect.getTopToolbar().insertButton(0, {
                        xtype: 'a-iconbutton',
                        text: '추가요청',
                        handler: function (self) {
                            new Ariel.glossary.inputFormWindow({
                                title: '추가',
                                type: 'add',
                                action: 'table',
                                method: 'POST',
                                status: 'REQUEST',
                                apiUrl: Ariel.glossary.UrlSet.table,
                                onAfterSave: function () {
                                    tableSelect.store.reload();
                                }
                            }).show();
                        }
                    });
                }
                // if (this._getPermission(permissions, 'edit')) {
                if (true) {
                    tableSelect.getTopToolbar().insertButton(1, { xtype: 'tbspacer' });

                    tableSelect.getTopToolbar().insertButton(2, {
                        xtype: 'a-iconbutton',
                        text: '수정',
                        handler: function (self) {
                            var sm = tableSelect.getSelectionModel();

                            if (sm.hasSelection()) {
                                var getRecord = sm.getSelected();
                                new Ariel.glossary.inputFormWindow({
                                    title: '수정',
                                    type: 'edit',
                                    getRecord: getRecord,
                                    method: 'PUT',
                                    action: 'table',
                                    apiUrl: Ariel.glossary.UrlSet.tableIdParam(getRecord.id),
                                    onAfterSave: function () {
                                        tableSelect.store.reload();
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
                    tableSelect.getTopToolbar().insertButton(3, { xtype: 'tbspacer' });

                    tableSelect.getTopToolbar().insertButton(4, {
                        xtype: 'a-iconbutton',
                        text: '삭제',
                        handler: function (self) {
                            var sm = tableSelect.getSelectionModel();

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
                                                url: Ariel.glossary.UrlSet.tableIdParam(getRecord.id),
                                                callback: function (opts, success, resp) {
                                                    if (success) {
                                                        try {
                                                            tableSelect.store.reload();
                                                            columnSelect.store.reload();
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
                if (true) {
                    tableSelect.getTopToolbar().insertButton(5, { xtype: 'tbspacer' });
                    tableSelect.getTopToolbar().insertButton(6, {
                        hidden: true,
                        xtype: 'a-iconbutton',
                        text: '승인',
                        handler: function (self) {
                            var sm = tableSelect.getSelectionModel();
                            if (sm.hasSelection()) {
                                var getRecord = sm.getSelected();
                                Ext.Msg.show({
                                    title: '알림',
                                    msg: '승인 하시겠습니까?',
                                    buttons: Ext.Msg.OKCANCEL,
                                    fn: function (btnId, text, opts) {

                                        if (btnId == 'ok') {
                                            Ariel.glossary.ChangeStatus.changeStatus(
                                                'ACCEPT', Ariel.glossary.UrlSet.tableIdParam(getRecord.id), tableSelect.getStore()
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
                if (true) {
                    tableSelect.getTopToolbar().insertButton(7, { xtype: 'tbspacer' });
                    tableSelect.getTopToolbar().insertButton(8, {
                        hidden: true,
                        xtype: 'a-iconbutton',
                        text: '반려',
                        handler: function (self) {
                            var sm = tableSelect.getSelectionModel();
                            if (sm.hasSelection()) {
                                var getRecord = sm.getSelected();

                                Ext.Msg.show({
                                    title: '알림',
                                    msg: '반려 하시겠습니까?',
                                    buttons: Ext.Msg.OKCANCEL,
                                    fn: function (btnId, text, opts) {
                                        if (btnId == 'ok') {
                                            Ariel.glossary.ChangeStatus.changeStatus(
                                                'REFUSE', Ariel.glossary.UrlSet.tableIdParam(getRecord.id), tableSelect.getStore()
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


                // column toolbar
                // if (this._getPermission(permissions, 'add')) {
                if (true) {
                    columnSelect.getTopToolbar().insertButton(0, {
                        xtype: 'a-iconbutton',
                        text: '추가',
                        handler: function (self) {
                            var table_id = _this.items.get(0).getSelectionModel().getSelected().id;
                            new Ariel.glossary.inputFormWindow({
                                title: '추가',
                                type: 'add',
                                action: 'column',
                                method: 'POST',
                                status: 'ACCEPT',
                                apiUrl: Ariel.glossary.UrlSet.column,
                                table_id: table_id,
                                onAfterSave: function () {
                                    columnSelect.store.proxy.setUrl(Ariel.glossary.UrlSet.tableIdParamColumns(table_id));
                                    columnSelect.store.reload();
                                }
                            }).show();
                        }
                    });
                    // } else {
                } if (false) {
                    columnSelect.getTopToolbar().insertButton(0, {
                        xtype: 'a-iconbutton',
                        text: '추가요청',
                        handler: function (self) {
                            new Ariel.glossary.inputFormWindow({
                                title: '추가',
                                type: 'add',
                                action: 'table',
                                method: 'POST',
                                status: 'REQUEST',
                                apiUrl: Ariel.glossary.UrlSet.column,
                                onAfterSave: function () {
                                    columnSelect.store.reload();
                                }
                            }).show();
                        }
                    });
                }
                // if (this._getPermission(permissions, 'edit')) {
                if (true) {
                    columnSelect.getTopToolbar().insertButton(1, { xtype: 'tbspacer' });

                    columnSelect.getTopToolbar().insertButton(2, {
                        xtype: 'a-iconbutton',
                        text: '수정',
                        handler: function (self) {

                            var sm = _this.items.get(1).getSelectionModel();
                            var table_id = _this.items.get(0).getSelectionModel().getSelected().id;
                            if (sm.hasSelection()) {
                                var getRecord = sm.getSelected();
                                new Ariel.glossary.inputFormWindow({
                                    title: '수정',
                                    type: 'edit',
                                    getRecord: getRecord,
                                    action: 'column',
                                    method: 'PUT',
                                    apiUrl: Ariel.glossary.UrlSet.columnIdParam(getRecord.id),
                                    onAfterSave: function () {
                                        columnSelect.store.proxy.setUrl(Ariel.glossary.UrlSet.tableIdParamColumns(table_id));

                                        columnSelect.store.reload();
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
                    columnSelect.getTopToolbar().insertButton(3, { xtype: 'tbspacer' });

                    columnSelect.getTopToolbar().insertButton(4, {
                        xtype: 'a-iconbutton',
                        text: '삭제',
                        handler: function (self) {
                            var sm = _this.items.get(1).getSelectionModel();
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
                                                url: Ariel.glossary.UrlSet.columnIdParam(getRecord.id),
                                                callback: function (opts, success, resp) {
                                                    if (success) {
                                                        try {
                                                            var r = Ext.decode(resp.responseText);

                                                            if (r.success == false) {
                                                                Ext.Msg.alert(r.code, r.msg);
                                                            } else {
                                                                var table_id = tableSelect.getSelectionModel().getSelected().id;
                                                                columnSelect.store.proxy.setUrl(Ariel.glossary.UrlSet.tableIdParamColumns(table_id));
                                                                columnSelect.store.load({
                                                                    params: {
                                                                        limit: _this.pageSize,
                                                                    }
                                                                });
                                                            }
                                                        }
                                                        catch (e) {
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
                    columnSelect.getTopToolbar().insertButton(5, { xtype: 'tbspacer' });
                    columnSelect.getTopToolbar().insertButton(6, {
                        hidden: true,
                        xtype: 'a-iconbutton',
                        text: '승인',
                        hidden: true,
                        handler: function (self) {
                            var sm = columnSelect.getSelectionModel();
                            if (sm.hasSelection()) {
                                var getRecord = sm.getSelected();
                                Ext.Msg.show({
                                    title: '알림',
                                    msg: '승인 하시겠습니까?',
                                    buttons: Ext.Msg.OKCANCEL,
                                    fn: function (btnId, text, opts) {

                                        if (btnId == 'ok') {
                                            Ariel.glossary.ChangeStatus.changeStatus(
                                                'ACCEPT', Ariel.glossary.UrlSet.columnIdParam(getRecord.id), columnSelect.getStore()
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
                    columnSelect.getTopToolbar().insertButton(7, { xtype: 'tbspacer' });
                    columnSelect.getTopToolbar().insertButton(8, {
                        hidden: true,
                        xtype: 'a-iconbutton',
                        text: '반려',
                        handler: function (self) {
                            var sm = columnSelect.getSelectionModel();
                            if (sm.hasSelection()) {
                                var getRecord = sm.getSelected();

                                Ext.Msg.show({
                                    title: '알림',
                                    msg: '반려 하시겠습니까?',
                                    buttons: Ext.Msg.OKCANCEL,
                                    fn: function (btnId, text, opts) {
                                        if (btnId == 'ok') {
                                            Ariel.glossary.ChangeStatus.changeStatus(
                                                'REFUSE', Ariel.glossary.UrlSet.columnIdParam(getRecord.id), columnSelect.getStore()
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




            tableSelect.getTopToolbar().doLayout();
            columnSelect.getTopToolbar().doLayout();
            columnSelect.getTopToolbar().onDisable();

        },

        // 테이블 그리드 함수
        _showTableGrid: function () {
            var _this = this;


            var store = new Ext.data.JsonStore({
                restful: true,
                remoteSort: true,
                proxy: new Ext.data.HttpProxy({
                    method: 'GET',
                    url: Ariel.glossary.UrlSet.table,
                    type: 'rest'
                }),
                totalProperty: 'total',
                root: 'data',
                fields: [
                    { name: 'id', type: 'int' },
                    'system',
                    'table_section',
                    'table_nm',
                    'table_eng_nm',
                    'table_se_nm',
                    'dc'
                ]
            });
            var cm = new Ext.grid.ColumnModel({
                defaults: {
                    align: 'center',
                    menuDisabled: true,
                    sortable: false
                },
                columns: [
                    { header: 'ID', dataIndex: 'id' },//, width:70
                    {
                        header: '시스템', dataIndex: 'system', renderer: function (value, metaData, record, rowIndex, colIndex, store) {
                            if (Ext.isEmpty(value)) {
                                return null;
                            } else {
                                return value.code_itm_nm;
                            }
                        },
                        align: 'left'
                    },
                    { header: '테이블명', dataIndex: 'table_nm', align: 'left' },
                    { header: '테이블ID', dataIndex: 'table_eng_nm', sortable: true, align: 'left' },
                    {
                        header: '속성', dataIndex: 'table_section', renderer: function (value, metaData, record, rowIndex, colIndex, store) {
                            if (Ext.isEmpty(value)) {
                                return null;
                            } else {
                                return value.code_itm_nm;
                            }
                        },
                        align: 'left'
                    },

                    { header: '설명', dataIndex: 'dc', align: 'left' },
                ]
            });
            var searchField = new Ariel.glossary.searchField({
                store: store,
                pageSize: _this.pageSize,
            });

            var tableGrid = new Ext.grid.GridPanel({

                loadMask: true,
                stripeRows: true,
                frame: false,


                viewConfig: {
                    emptyText: '목록이 없습니다.',
                    forceFit: true,
                    border: false,
                },
                cls: 'grid_title_customize proxima_customize',
                flex: 1,
                store: store,
                cm: cm,
                tbar: [
                    '->', ' ',
                    searchField
                ],
                bbar: new Ext.PagingToolbar({
                    pageSize: _this.pageSize,
                    store: store
                }),
                listeners: {
                    afterrender: function (self) {
                        self.store.load({
                            params: {
                                limit: _this.pageSize
                            }
                        });
                    },
                    rowclick: function (self, rowIndex, e) {

                        if (self.selModel.hasSelection()) {
                            try {
                                var table_id = self.getSelectionModel().getSelected().id;

                                _this.tableId = table_id;
                                // _this.get(1).getBottomToolbar().bindStore(_this.codeItemStore(table_id));
                             
                                _this.get(1).store.proxy.setUrl('/api/v1/data-dic-tables/' + table_id + '/columns');
                                _this.get(1).store.load({
                                    params: {
                                        table_id:table_id,
                                        limit: _this.pageSize,
                                    }
                                });
                                _this.get(1).topToolbar.onEnable();
                                _this.get(1).bottomToolbar.onEnable();
                            } catch (e) {
                                Ext.Msg.alert(e['name'], e['message']);
                            }
                        } else {
                            Ext.Msg.alert('알림', '목록을 선택해주세요.');
                        }
                    },
                    rowdblclick: function (self, rowIndex, e) {
                        //수정창
                        var tableSelect = _this.get(0);
                        var sm = tableSelect.getSelectionModel();
                        var getRecord = sm.getSelected();
                        new Ariel.glossary.inputFormWindow({
                            title: '수정',
                            type: 'edit',
                            getRecord: getRecord,
                            method: 'PUT',
                            action: 'table',
                            apiUrl: Ariel.glossary.UrlSet.tableIdParam(getRecord.id),
                            onAfterSave: function () {
                                tableSelect.store.reload();
                            }
                        }).show();
                    }
                }
            });


            return tableGrid;

        },
        // 유효값 그리드 함수
        _showColumnGrid: function () {
            var _this = this;
            
            var store = new Ext.data.JsonStore({
                restful: true,
                remoteSort: true,
                proxy: new Ext.data.HttpProxy({
                    method: 'GET',
                    url: Ariel.glossary.UrlSet.tableIdParamColumns(_this.tableId),
                    type: 'rest'
                }),
                totalProperty: 'total',
                // idProperty: 'id',
                root: 'data',
                fields: [
                    { name: 'id', type: 'int' },
                    'std_yn',
                    'column_nm',
                    'column_eng_nm',
                    'domain',
                    'pk_yn',
                    'nn_yn',
                    'dc'
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
                    { header: '표준여부', dataIndex: 'std_yn', sortable: true },
                    { header: '컬럼명', dataIndex: 'column_nm', sortable: true, align: 'left' },
                    { header: '컬럼ID', dataIndex: 'column_eng_nm', sortable: true, align: 'left' },
                    {
                        header: '도메인명', dataIndex: 'domain', renderer: function (value, metaData, record, rowIndex, colIndex, store) {
                            if (value == null) {
                                return null;
                            } else {
                                return value.domn_nm;
                            }
                        },
                        align: 'left'
                    },
                    {
                        header: '데이터타입', dataIndex: 'domain',
                        renderer: function (value, metaData, record, rowIndex, colIndex, store) {

                            if (value == null) {
                                if (Ext.isEmpty(record.json.data_ty)) {
                                    return null;
                                } else {
                                    if (Ext.isEmpty(record.json.data_lt)) {
                                        return record.json.data_ty;
                                    } else {
                                        return record.json.data_ty + '(' + record.json.data_lt + ')';
                                    }
                                };
                            } else {
                                if (Ext.isEmpty(value.data_lt)) {
                                    return value.data_ty;
                                } else {
                                    return value.data_ty + '(' + value.data_lt + ')';
                                }
                            }
                        },
                        align: 'left'
                    },
                    { header: 'PK 여부', dataIndex: 'pk_yn' },
                    { header: 'NotNull', dataIndex: 'nn_yn' },
                    { header: '설명', dataIndex: 'dc', align: 'left' }
                ]
            });
            var valueGrid = new Ext.grid.GridPanel({
                title: '<span class="user_span"><span class="icon_title"><i class=""></i></span><span class="main_title_header">' + '컬럼 정보' + '</span></span>',
                loadMask: true,
                viewConfig: {
                    emptyText: '목록이 없습니다.',
                    forceFit: true,
                    border: false,
                },
                store: store,
                cm: cm,
                tbar: [],
                bbar: new Ext.PagingToolbar({
                    pageSize: _this.pageSize,
                    store: store,
                }),
                listeners: {
                    afterrender: function (self) {
                        self.bottomToolbar.onDisable();
                    },
                    rowdblclick: function (self, rowIndex, e) {
                        //수정창


                        var sm = _this.items.get(1).getSelectionModel();

                        var table_id = _this.items.get(0).getSelectionModel().getSelected().id;

                        var getRecord = sm.getSelected();
                        new Ariel.glossary.inputFormWindow({
                            title: '수정',
                            type: 'edit',
                            getRecord: getRecord,
                            action: 'column',
                            method: 'PUT',
                            apiUrl: Ariel.glossary.UrlSet.columnIdParam(getRecord.id),
                            onAfterSave: function () {
                                columnSelect.store.proxy.setUrl(Ariel.glossary.UrlSet.tableIdParamColumns(table_id));

                                columnSelect.store.reload();
                            }
                        }).show();
                    }
                }
            });
            return valueGrid;
        }
    });

    return new Ariel.glossary.TableListGrid();

})()

