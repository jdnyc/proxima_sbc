(function () {
    Ext.ns('Ariel.glossary');

    Ariel.glossary.CodeListGrid = Ext.extend(Ext.Panel, {
        pageSize: 50,

        // property
        permission_code: 'data_dic.domain',
        title: '<span class="user_span"><span class="icon_title"><i class=""></i></span><span class="main_title_header">' + '도메인' + '</span></span>',
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

            this.items = [
                _this._showDomainGrid(),
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
                var domainSelect = _this.get(0);
                // if (this._getPermission(permissions, 'add')) {
                if (true) {
                    domainSelect.getTopToolbar().insertButton(0, {
                        xtype: 'a-iconbutton',
                        text: '추가',
                        handler: function (self) {
                            new Ariel.glossary.inputFormWindow({
                                title: '추가',
                                type: 'add',
                                action: 'domain',
                                apiUrl: Ariel.glossary.UrlSet.domain,
                                method: 'POST',
                                status: 'ACCEPT',
                                onAfterSave: function () {
                                    domainSelect.store.reload();
                                }
                            }).show();
                        }
                    });
                    // } else {
                } if (false) {
                    domainSelect.getTopToolbar().insertButton(0, {
                        xtype: 'a-iconbutton',
                        text: '추가요청',
                        handler: function (self) {
                            new Ariel.glossary.inputFormWindow({
                                title: '추가',
                                type: 'add',
                                action: 'domain',
                                apiUrl: Ariel.glossary.UrlSet.domain,
                                status: 'REQUEST',
                                method: 'POST',
                                onAfterSave: function () {
                                    domainSelect.store.reload();
                                }
                            }).show();
                        }
                    });
                }
                // if (this._getPermission(permissions, 'edit')) {
                if (true) {
                    domainSelect.getTopToolbar().insertButton(1, { xtype: 'tbspacer' });

                    domainSelect.getTopToolbar().insertButton(2, {
                        xtype: 'a-iconbutton',
                        text: '수정',
                        handler: function (self) {
                            var sm = domainSelect.getSelectionModel();

                            if (sm.hasSelection()) {

                                var getRecord = sm.getSelected();
                                new Ariel.glossary.inputFormWindow({
                                    title: '수정',
                                    type: 'edit',
                                    getRecord: getRecord,
                                    action: 'domain',
                                    method: 'PUT',
                                    apiUrl: Ariel.glossary.UrlSet.domainIdParam(getRecord.id),
                                    onAfterSave: function () {
                                        domainSelect.store.reload();
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
                    domainSelect.getTopToolbar().insertButton(3, { xtype: 'tbspacer' });

                    domainSelect.getTopToolbar().insertButton(4, {
                        xtype: 'a-iconbutton',
                        text: '삭제',
                        handler: function (self) {
                            var sm = domainSelect.getSelectionModel();
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
                                                url: Ariel.glossary.UrlSet.domainIdParam(getRecord.id),
                                                callback: function (opts, success, resp) {
                                                    if (success) {
                                                        try {
                                                            var r = Ext.decode(resp.responseText);

                                                            if (r.success == false) {
                                                                Ext.Msg.alert(r.code, r.msg);
                                                            } else {
                                                                domainSelect.store.reload();
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
                    domainSelect.getTopToolbar().insertButton(5, { xtype: 'tbspacer' });
                    domainSelect.getTopToolbar().insertButton(6, {
                        hidden: true,
                        xtype: 'a-iconbutton',
                        text: '승인',
                        handler: function (self) {
                            var sm = domainSelect.getSelectionModel();
                            if (sm.hasSelection()) {
                                var getRecord = sm.getSelected();
                                Ext.Msg.show({
                                    title: '알림',
                                    msg: '승인 하시겠습니까?',
                                    buttons: Ext.Msg.OKCANCEL,
                                    fn: function (btnId, text, opts) {

                                        if (btnId == 'ok') {
                                            Ariel.glossary.ChangeStatus.changeStatus(
                                                'ACCEPT', Ariel.glossary.UrlSet.domainIdParam(getRecord.id), domainSelect.getStore()
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
                    domainSelect.getTopToolbar().insertButton(7, { xtype: 'tbspacer' });
                    domainSelect.getTopToolbar().insertButton(8, {
                        hidden: true,
                        xtype: 'a-iconbutton',
                        text: '반려',
                        handler: function (self) {
                            var sm = domainSelect.getSelectionModel();
                            if (sm.hasSelection()) {
                                var getRecord = sm.getSelected();

                                Ext.Msg.show({
                                    title: '알림',
                                    msg: '반려 하시겠습니까?',
                                    buttons: Ext.Msg.OKCANCEL,
                                    fn: function (btnId, text, opts) {
                                        if (btnId == 'ok') {
                                            Ariel.glossary.ChangeStatus.changeStatus(
                                                'REFUSE', Ariel.glossary.UrlSet.domainIdParam(getRecord.id), domainSelect.getStore()
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


            domainSelect.getTopToolbar().doLayout();
        },
        // 도메인 그리드 함수
        _showDomainGrid: function () {
            var _this = this;

            var store = new Ext.data.JsonStore({
                // autoDestroy: true,
                restful: true,
                remoteSort: true,
                proxy: new Ext.data.HttpProxy({
                    method: 'GET',
                    url: Ariel.glossary.UrlSet.domain,
                    type: 'rest'
                }),
                totalProperty: 'total',
                // idProperty: 'id',
                root: 'data',
                fields: [
                    { name: 'id', type: 'int' },
                    'domn_nm',
                    'domain_mlsfc',
                    'domain_sclas',
                    'data_ty',
                    'data_lt',
                    'domn_eng_nm',
                    'domain_type',
                    'data_lt'
                ]
            });
            var cm = new Ext.grid.ColumnModel({
                defaults: {
                    align: 'center',
                    menuDisabled: true,
                    sortable: false
                },
                columns: [
                    { header: 'ID', dataIndex: 'id', sortable: true },//, width:70
                    {
                        header: '중분류', dataIndex: 'domain_mlsfc', renderer: function (value, metaData, record, rowIndex, colIndex, store) {
                            if (value == null) {
                                return null;
                            } else {
                                return value.code_itm_nm;
                            }
                        },
                        sortable: true,
                        align: 'left'
                    },
                    {
                        header: '소분류', dataIndex: 'domain_sclas', renderer: function (value, metaData, record, rowIndex, colIndex, store) {
                            if (value == null) {
                                return null;
                            } else {
                                return value.code_itm_nm;
                            }
                        },
                        sortable: true,
                        align: 'left'
                    },
                    {
                        header: '유형', dataIndex: 'domain_type', renderer: function (value, metaData, record, rowIndex, colIndex, store) {
                            if (value == null) {
                                return null;
                            } else {
                                return value.code_itm_nm;
                            }
                        },
                        align: 'left'
                    },
                    { header: '도메인명', dataIndex: 'domn_nm', sortable: true, align: 'left' },
                    { header: '도메인영문명', dataIndex: 'domn_eng_nm', sortable: true, align: 'left' },
                    { header: '데이터타입', dataIndex: 'data_ty', align: 'left' },
                    { header: '길이', dataIndex: 'data_lt', align: 'left' }
                ]
            });
            var searchField = new Ariel.glossary.searchField({
                store: store,
                // width:320,

                pageSize: _this.pageSize,
            });

            var domainGrid = new Ext.grid.GridPanel({
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
                        try {
                            if (self.selModel.hasSelection()) {

                                var domn_id = self.getSelectionModel().getSelected().id;
                                _this.items.get(1).store.proxy.setUrl(Ariel.glossary.UrlSet.domainIdParamCodeItems(domn_id));
                                _this.items.get(1).store.load({
                                    params: {
                                        domn_id:domn_id,
                                        limit: _this.pageSize
                                    }
                                });
                            } else {
                                Ext.Msg.alert('알림', '목록을 선택해주세요.');
                            }
                        } catch (e) {
                            Ext.Msg.alert(e['name'], e['message']);
                        }
                    },
                    rowdblclick: function (self, rowIndex, e) {
                        //수정창
                        var domainSelect = _this.get(0);
                        var sm = domainSelect.getSelectionModel();
                        var getRecord = sm.getSelected();
                        new Ariel.glossary.inputFormWindow({
                            title: '수정',
                            type: 'edit',
                            getRecord: getRecord,
                            action: 'domain',
                            method: 'PUT',
                            apiUrl: Ariel.glossary.UrlSet.domainIdParam(getRecord.id),
                            onAfterSave: function () {
                                domainSelect.store.reload();
                            }
                        }).show();
                    }
                },

            });


            return domainGrid;

        },
        // 유효값 그리드 함수
        _showValueGrid: function () {
            var _this = this;

            var store = new Ext.data.JsonStore({
                restful: true,
                remoteSort: true,
                proxy: new Ext.data.HttpProxy({
                    method: 'GET',
                    // url: '/api/v1/data-dic-codeSet/' + 0 + '/codeItems',
                    url: Ariel.glossary.UrlSet.domainIdParamCodeItems(0),
                    type: 'rest'
                }),
                totalProperty: 'total',
                root: 'data',
                fields: [
                    { name: 'id', type: 'int' },
                    'code',
                    'code_itm_code',
                    'code_itm_nm',
                    'use_yn',
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
                    { header: '코드명', dataIndex: 'code', align: 'left' },
                    { header: '유효값', dataIndex: 'code_itm_code', align: 'left' },
                    { header: '유효값명', dataIndex: 'code_itm_nm', align: 'left' },
                    { header: '사용여부', dataIndex: 'use_yn' },
                    { header: '설명', dataIndex: 'dc', align: 'left' }
                ]
            });
            var valueGrid = new Ext.grid.GridPanel({
                title: '<span class="user_span"><span class="icon_title"><i class=""></i></span><span class="main_title_header">' + '유효값' + '</span></span>',
                loadMask: true,
                viewConfig: {
                    emptyText: '목록이 없습니다.',
                    forceFit: true,
                    border: false,
                },
                store: store,
                cm: cm,
                bbar: new Ext.PagingToolbar({
                    pageSize: _this.pageSize,
                    store: store
                })
            });
            return valueGrid;
        }
    })
    return new Ariel.glossary.CodeListGrid();
})()