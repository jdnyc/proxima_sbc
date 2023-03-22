(function () {
    Ext.ns('Ariel.glossary');

    Ariel.glossary.CodeListGrid = Ext.extend(Ext.Panel, {
        clickCodeSetId: 0,
        pageSize: 50,
        // property
        permission_code: 'data_dic.code',
        title: '<span class="user_span"><span class="icon_title"><i class=""></i></span><span class="main_title_header">' + '코드' + '</span></span>',
        loadMask: true,

        stripeRows: true,
        frame: false,
        autoWidth: true,
        viewConfig: {
            emptyText: '목록이 없습니다.',
            forceFit: true,
            border: false,
        },
        cls: 'grid_title_customize proxima_customize',
        autoHight: true,
        layout: {
            type: 'Hbox',
            align: 'stretch'
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
                _this._showCodeGrid(),
                _this._showValueGrid()
            ];


            Ariel.glossary.CodeListGrid.superclass.initComponent.call(this);
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
                var codeSelect = _this.get(0);
                var codeItemSelect = _this.get(1);
                // codeSet
                // if (this._getPermission(permissions, 'add')) {
                if (true) {
                    codeSelect.getTopToolbar().insertButton(0, {
                        xtype: 'a-iconbutton',
                        text: '추가',
                        handler: function (self) {
                            var sm = codeSelect.getSelectionModel();

                            new Ariel.glossary.inputFormWindow({
                                title: '추가',
                                type: 'add',
                                action: 'codeSet',
                                getRecord: null,
                                method: 'POST',
                                apiUrl: Ariel.glossary.UrlSet.codeSet,
                                onAfterSave: function () {
                                    codeSelect.store.reload();
                                }
                            }).show();

                        }
                    });
                }

                // if (this._getPermission(permissions, 'edit')) {
                if (true) {
                    codeSelect.getTopToolbar().insertButton(1, { xtype: 'tbspacer' });

                    codeSelect.getTopToolbar().insertButton(2, {
                        xtype: 'a-iconbutton',
                        text: '수정',
                        handler: function (self) {
                            var sm = codeSelect.getSelectionModel();

                            if (sm.hasSelection()) {

                                var getRecord = sm.getSelected();
                                new Ariel.glossary.inputFormWindow({
                                    title: '수정',
                                    type: 'edit',
                                    getRecord: getRecord,
                                    action: 'codeSet',
                                    method: 'PUT',
                                    apiUrl: Ariel.glossary.UrlSet.codeSetIdParam(getRecord.id),
                                    onAfterSave: function () {
                                        codeSelect.store.reload();
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
                    codeSelect.getTopToolbar().insertButton(3, { xtype: 'tbspacer' });

                    codeSelect.getTopToolbar().insertButton(4, {
                        xtype: 'a-iconbutton',
                        text: '삭제',
                        handler: function (self) {
                            var sm = codeSelect.getSelectionModel();
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
                                                url: Ariel.glossary.UrlSet.codeSetIdParam(getRecord.id),
                                                callback: function (opts, success, resp) {
                                                    if (success) {
                                                        try {
                                                            var r = Ext.decode(resp.responseText);

                                                            if (r.success == false) {
                                                                Ext.Msg.alert(r.code, r.msg);
                                                            } else {
                                                                codeSelect.store.reload();
                                                                codeItemSelect.store.reload();
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


                //codeItem
                // if (this._getPermission(permissions, 'add')) {
                if (true) {
                    codeItemSelect.getTopToolbar().insertButton(0, {
                        xtype: 'a-iconbutton',
                        text: '추가',
                        handler: function (self) {
                            var code_set_id = codeSelect.getSelectionModel().getSelected().id;
                            var sm = codeItemSelect.getSelectionModel();

                            if (sm.hasSelection()) {
                                var getRecord = sm.getSelected();

                                new Ariel.glossary.inputFormWindow({
                                    title: '추가',
                                    type: 'add',
                                    action: 'codeItem',
                                    method: 'POST',
                                    getRecord: getRecord,
                                    apiUrl: Ariel.glossary.UrlSet.codeItem,
                                    code_set_id: code_set_id,
                                    onAfterSave: function () {
                                        codeItemSelect.store.proxy.setUrl(Ariel.glossary.UrlSet.codeSetIdParamCodeItems(code_set_id));
                                        codeItemSelect.store.reload();
                                    }
                                }).show();
                            } else {
                                new Ariel.glossary.inputFormWindow({
                                    title: '추가',
                                    type: 'add',
                                    action: 'codeItem',
                                    method: 'POST',
                                    getRecord: null,
                                    apiUrl: Ariel.glossary.UrlSet.codeItem,
                                    code_set_id: code_set_id,
                                    onAfterSave: function () {
                                        codeItemSelect.store.proxy.setUrl(Ariel.glossary.UrlSet.codeSetIdParamCodeItems(code_set_id));
                                        codeItemSelect.store.reload();
                                    }
                                }).show();
                            }

                        }
                    });
                }

                // if (this._getPermission(permissions, 'edit')) {
                if (true) {
                    codeItemSelect.getTopToolbar().insertButton(1, { xtype: 'tbspacer' });

                    codeItemSelect.getTopToolbar().insertButton(2, {
                        xtype: 'a-iconbutton',
                        text: '수정',
                        handler: function (self) {

                            var sm = codeItemSelect.getSelectionModel();

                            if (sm.hasSelection()) {
                                var getRecord = sm.getSelected();
                                var code_set_id = codeSelect.getSelectionModel().getSelected().id;
                                new Ariel.glossary.inputFormWindow({
                                    title: '수정',
                                    type: 'edit',
                                    getRecord: getRecord,
                                    action: 'codeItem',
                                    method: 'PUT',
                                    apiUrl: Ariel.glossary.UrlSet.codeItemIdParam(getRecord.id),
                                    onAfterSave: function () {
                                        codeItemSelect.store.proxy.setUrl(Ariel.glossary.UrlSet.codeSetIdParamCodeItems(code_set_id));
                                        codeItemSelect.store.reload();
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
                    codeItemSelect.getTopToolbar().insertButton(3, { xtype: 'tbspacer' });

                    codeItemSelect.getTopToolbar().insertButton(4, {
                        xtype: 'a-iconbutton',
                        text: '삭제',
                        handler: function (self) {
                            var sm = codeItemSelect.getSelectionModel();
                            if (sm.hasSelection()) {
                                var getRecord = sm.getSelected();
                                var code_set_id = codeSelect.getSelectionModel().getSelected().id;
                                Ext.Msg.show({
                                    title: '알림',
                                    msg: '삭제하시겠습니까?',
                                    buttons: Ext.Msg.OKCANCEL,
                                    fn: function (btnId, text, opts) {
                                        if (btnId == 'ok') {
                                            Ext.Ajax.request({
                                                method: 'DELETE',
                                                url: Ariel.glossary.UrlSet.codeItemIdParam(getRecord.id),
                                                callback: function (opts, success, resp) {
                                                    if (success) {
                                                        try {
                                                            var r = Ext.decode(resp.responseText);

                                                            if (r.success == false) {
                                                                Ext.Msg.alert(r.code, r.msg);
                                                            } else {
                                                                codeItemSelect.store.proxy.setUrl(Ariel.glossary.UrlSet.codeSetIdParamCodeItems(code_set_id));
                                                                codeItemSelect.getStore().reload();
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
                /**
                 * sort up 버튼
                 */
                if (true) {
                    codeItemSelect.getTopToolbar().insertButton(5, { xtype: 'tbfill' });
                    codeItemSelect.getTopToolbar().insertButton(6, {
                        xtype: 'button',
                        cls: 'proxima_button_customize',
                        text: "<i class='fa fa-angle-up fa-2x' style=\"font-size:16px;color:white;\" title='" + _text('MN02230') + "'></i>",
                        width: 30,
                        handler: function (self) {
                            var sm = codeItemSelect.getSelectionModel();

                            if (sm.hasSelection()) {
                                var getRecord = sm.getSelected();
                                var codeItems = codeItemSelect.getStore().data.items;
                                var selectedIndex = codeItems.indexOf(getRecord);
                                var changeCodeItems = []
                                var idx = 0;
                                var idx2 = 1;
                                while (idx < 2) {
                                    changeCodeItems.push({
                                        'id': codeItems[selectedIndex - idx].id,
                                        'code_set_id': codeItems[selectedIndex - idx].json.code_set_id,
                                        'sort_ordr': codeItems[selectedIndex - idx2].json.sort_ordr,
                                    });
                                    idx++;
                                    idx2--;
                                }
                                Ext.each(changeCodeItems, function (r, i, e) {
                                    Ext.Ajax.request({
                                        method: 'PUT',
                                        url: Ariel.glossary.UrlSet.codeItemIdParam(r.id),
                                        params: {
                                            code_set_id: r.code_set_id,
                                            sort_ordr: r.sort_ordr
                                        },
                                        callback: function (opts, success, resp) {
                                            if (success) {
                                                try {
                                                    codeItemSelect.store.proxy.setUrl(Ariel.glossary.UrlSet.codeSetIdParamCodeItems(r.code_set_id));
                                                    codeItemSelect.getStore().reload();
                                                } catch (e) {
                                                    Ext.Msg.alert(e['name'], e['message']);
                                                }
                                            } else {
                                                Ext.Msg.alert('status: ' + resp.status, resp.statusText);
                                            }
                                        }
                                    });
                                });
                            } else {
                                Ext.Msg.alert('알림', '목록을 선택해주세요.');
                            }

                        }
                    });

                }
                /**
                 * sort down 버튼
                 */
                if (true) {
                    codeItemSelect.getTopToolbar().insertButton(7, { xtype: 'tbspacer' });
                    codeItemSelect.getTopToolbar().insertButton(8, {
                        xtype: 'button',
                        cls: 'proxima_button_customize',
                        text: "<i class='fa fa-angle-down fa-2x' style=\"font-size:16px;color:white;\" title='" + _text('MN02231') + "'></i>",
                        width: 30,
                        handler: function (self) {
                            var sm = codeItemSelect.getSelectionModel();

                            if (sm.hasSelection()) {
                                var getRecord = sm.getSelected();
                                var codeItems = codeItemSelect.getStore().data.items;
                                var selectedIndex = codeItems.indexOf(getRecord);
                                var changeCodeItems = []

                                var idx = 0;
                                var idx2 = 1;
                                while (idx < 2) {
                                    changeCodeItems.push({
                                        'id': codeItems[selectedIndex + idx].id,
                                        'code_set_id': codeItems[selectedIndex + idx].json.code_set_id,
                                        'sort_ordr': codeItems[selectedIndex + idx2].json.sort_ordr,
                                    });
                                    idx++;
                                    idx2--;
                                }
                                Ext.each(changeCodeItems, function (r, i, e) {
                                    Ext.Ajax.request({
                                        method: 'PUT',
                                        url: Ariel.glossary.UrlSet.codeItemIdParam(r.id),
                                        params: {
                                            code_set_id: r.code_set_id,
                                            sort_ordr: r.sort_ordr
                                        },
                                        callback: function (opts, success, resp) {
                                            if (success) {
                                                try {
                                                    codeItemSelect.store.proxy.setUrl(Ariel.glossary.UrlSet.codeSetIdParamCodeItems(r.code_set_id));
                                                    codeItemSelect.getStore().reload();
                                                } catch (e) {
                                                    Ext.Msg.alert(e['name'], e['message']);
                                                }
                                            } else {
                                                Ext.Msg.alert('status: ' + resp.status, resp.statusText);
                                            }
                                        }
                                    });
                                });
                            } else {
                                Ext.Msg.alert('알림', '목록을 선택해주세요.');
                            }

                        }
                    });

                }
            } catch (e) {
                Ext.Msg.alert(e['name'], e['message']);
            }

            codeSelect.getTopToolbar().doLayout();
            codeItemSelect.getTopToolbar().doLayout();
            codeItemSelect.getTopToolbar().onDisable();
        },
        // 코드 그리드 함수
        _showCodeGrid: function () {
            var _this = this;
            // var apiUrl = '/api/v1/data-dic-code-sets';
            var store = new Ext.data.JsonStore({
                restful: true,

                remoteSort: true,
                proxy: new Ext.data.HttpProxy({
                    method: 'GET',
                    url: Ariel.glossary.UrlSet.codeSet,
                    type: 'rest',
                }),
                totalProperty: 'total',
                root: 'data',
                fields: [
                    { name: 'id', type: 'int' },
                    'code_set_nm',
                    'code_set_code',
                    'code_set_cl',
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
                    { header: 'ID', dataIndex: 'id', sortable: true },
                    { header: '코드명', dataIndex: 'code_set_nm', sortable: true, align: 'left' },
                    { header: '코드영문명', dataIndex: 'code_set_code', sortable: true, align: 'left' },
                    { header: '코드 분류', dataIndex: 'code_set_cl', sortable: true, align: 'left', hidden: true },
                    { header: '설명', dataIndex: 'dc', align: 'left' },
                ]
            });
            var searchField = new Ariel.glossary.searchField({
                store: store,
                pageSize: _this.pageSize
            });
            var tbar = [
                '->', ' ',
                searchField
            ];
            var bbar = new Ext.PagingToolbar({
                pageSize: _this.pageSize,
                store: store,
            });

            var codeGrid = new Ext.grid.GridPanel({
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
                flex: 1,
                store: store,
                cm: cm,
                tbar: tbar,
                bbar: bbar,
                listeners: {
                    afterrender: function (self) {
                        self.store.load({
                            params: {
                                limit: _this.pageSize
                            }
                        });
                    },
                    rowclick: function (self, rowIndex, e) {
                        try {
                            if (self.selModel.hasSelection()) {
                                var code_set_id = self.getSelectionModel().getSelected().id;
                                var code_set_cl = self.getSelectionModel().getSelected().data.code_set_cl;

                                _this.clickCodeSetId = code_set_id;
                                // _this.items.get(1).store.proxy.setUrl(Ariel.glossary.UrlSet.codeSetIdParamCodeItems(code_set_id));
                                _this.items.get(1).store.proxy.setUrl(Ariel.glossary.UrlSet.codeItemsByCodeSetId(code_set_id));

                                _this.items.get(1).store.load({
                                    params: {
                                        code_set_id: code_set_id,
                                    }
                                });

                                _this.items.get(1).topToolbar.onEnable();
                                _this.items.get(1).bottomToolbar.onEnable();
                            } else {
                                Ext.Msg.alert('알림', '목록을 선택해주세요.');
                            }
                        } catch (e) {
                            Ext.Msg.alert(e['name'], e['message']);
                        }
                    },
                    rowdblclick: function (self, rowIndex, e) {
                        //수정창
                        var codeSelect = _this.get(0);
                        var sm = codeSelect.getSelectionModel();

                        var getRecord = sm.getSelected();
                        new Ariel.glossary.inputFormWindow({
                            title: '수정',
                            type: 'edit',
                            getRecord: getRecord,
                            action: 'codeSet',
                            method: 'PUT',
                            apiUrl: Ariel.glossary.UrlSet.codeSetIdParam(getRecord.id),
                            onAfterSave: function () {
                                codeSelect.store.reload();
                            }
                        }).show();
                    }
                },
            });
            return codeGrid;

        },
        // 유효값 그리드 함수
        _showValueGrid: function () {
            var _this = this;
            // var apiUrl = '/api/v1/data-dic-code-items';
            var store = new Ext.data.JsonStore({
                // autoDestroy: true,
                remoteSort: true,
                restful: true,
                baseParams: {
                    sorters: ['depth', 'parnts_id', 'sort_ordr'],
                    dir: 'ASC',
                    limit: 1000
                },
                proxy: new Ext.data.HttpProxy({
                    method: 'GET',
                    // url: Ariel.glossary.UrlSet.codeSetIdParamCodeItems(0),
                    url: Ariel.glossary.UrlSet.codeItemsByCodeSetId(0),
                    type: 'rest'
                }),
                totalProperty: 'total',
                root: 'data',
                fields: [
                    { name: 'id', type: 'int' },
                    'code_itm_nm',
                    'code_itm_code',
                    'use_yn',
                    { name: 'dp', type: 'int' },
                    'parnts_id',
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
                    { header: 'ID', dataIndex: 'id' },
                    { header: '유효값', dataIndex: 'code_itm_code', align: 'left' },
                    {
                        header: '유효값명', dataIndex: 'code_itm_nm', renderer: function (value, metaData, record, rowIndex, colIndex, store) {
                            if (record.get('dp') > 1) {
                                for (var i = 1; i < record.get('dp'); i++) {
                                    value = '&nbsp&nbsp' + value;
                                }
                                value = '┖' + value;
                            }
                            return value;
                        },
                        align: 'left'
                    },
                    { header: '사용여부', dataIndex: 'use_yn' },
                    { header: '설명', dataIndex: 'dc', align: 'left' }
                ]
            });
            var valueGrid = new Ext.grid.GridPanel({
                loadMask: true,
                title: '<span class="user_span"><span class="icon_title"><i class=""></i></span><span class="main_title_header">' + '유효값' + '</span></span>',
                store: store,
                viewConfig: {
                    emptyText: '목록이 없습니다.',
                    forceFit: true,
                    border: false,
                },
                cm: cm,
                tbar: [

                ],
                // 필요없을듯
                bbar: new Ext.PagingToolbar({
                    // pageSize: _this.pageSize,
                    pageSize: 1000,
                    store: store
                }),
                listeners: {
                    afterrender: function (self) {


                        self.bottomToolbar.onDisable();

                    },
                    rowdblclick: function (self, rowIndex, e) {
                        //수정창
                        var codeSelect = _this.get(0);
                        var codeItemSelect = _this.get(1);
                        var sm = codeItemSelect.getSelectionModel();


                        var getRecord = sm.getSelected();
                        var code_set_id = codeSelect.getSelectionModel().getSelected().id;
                        new Ariel.glossary.inputFormWindow({
                            title: '수정',
                            type: 'edit',
                            getRecord: getRecord,
                            action: 'codeItem',
                            method: 'PUT',
                            apiUrl: Ariel.glossary.UrlSet.codeItemIdParam(getRecord.id),
                            onAfterSave: function () {
                                codeItemSelect.store.proxy.setUrl(Ariel.glossary.UrlSet.codeSetIdParamCodeItems(code_set_id));
                                codeItemSelect.store.reload();
                            }
                        }).show();
                    }

                }
            });

            return valueGrid;
        }
    })
    return new Ariel.glossary.CodeListGrid();
})()