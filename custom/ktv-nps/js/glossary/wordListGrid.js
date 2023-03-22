(function () {
    Ext.ns('Ariel.glossary');

    Ariel.glossary.WordListGrid = Ext.extend(Ext.grid.GridPanel, {
        // property
        // title: '표준용어',
        permission_code: 'data_dic.word',
        title: '<span class="user_span"><span class="icon_title"><i class=""></i></span><span class="main_title_header">' + '표준용어' + '</span></span>',
        loadMask: true,
        stripeRows: true,
        frame: false,
        autoWidth: true,
        layout: 'fit',
        viewConfig: {
            emptyText: '목록이 없습니다.',
            forceFit: true,
            border: false
        },
        cls: 'grid_title_customize proxima_customize',
        pageSize: 50,
        listeners: {
            afterrender: function (self) {
                self.getStore().load({
                    params: {
                        limit: this.pageSize
                    }
                });
            },
            rowdblclick: function (self, rowIndex, e) {
                var _this = this;
                var sm = _this.getSelectionModel();
                var getRecord = sm.getSelected();
                new Ariel.glossary.inputFormWindow({
                    title: '수정',
                    type: 'edit',
                    action: 'word',
                    method: 'PUT',
                    getRecord: getRecord,
                    apiUrl: Ariel.glossary.UrlSet.wordIdParam(getRecord.id),
                    onAfterSave: function () {
                        _this.store.reload();
                    }
                }).show();
            }
        },
        initComponent: function () {

            var _this = this;
            this._initialize();
            // var components = [
            //     '/custom/ktv-nps/js/glossary/searchField.js',
            //     '/custom/ktv-nps/js/glossary/inputFormWindow.js',
            //     '/custom/ktv-nps/js/glossary/domainSelectWindow.js',
            //     '/custom/ktv-nps/js/glossary/fieldSelectWindow.js',
            //     '/custom/ktv-nps/js/glossary/codeSelectWindow.js'
            // ];
            // console.log('bf load');
            // Ext.Loader.load(components, function (r) {
            //     console.log('load',r);
            // });
            // console.log('af load');
            Ariel.glossary.WordListGrid.superclass.initComponent.call(this);
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
            var tbarIdx = 0;
            if (this._getPermission(permissions, 'add')) {
                _this.getTopToolbar().insertButton(tbarIdx, {
                    xtype: 'a-iconbutton',
                    text: '추가',
                    handler: function (self) {
                        new Ariel.glossary.inputFormWindow({
                            title: '추가',
                            type: 'add',
                            action: 'word',
                            method: 'POST',
                            status: 'ACCEPT',
                            apiUrl: Ariel.glossary.UrlSet.word,
                            onAfterSave: function () {
                                _this.store.reload();

                            }
                        }).show();
                    }
                });
                tbarIdx++;
            } else {
                _this.getTopToolbar().insertButton(tbarIdx, {
                    xtype: 'a-iconbutton',
                    text: '추가요청',
                    handler: function (self) {
                        new Ariel.glossary.inputFormWindow({
                            title: '추가',
                            type: 'add',
                            action: 'word',
                            method: 'POST',
                            status: 'REQUEST',
                            apiUrl: Ariel.glossary.UrlSet.word,
                            onAfterSave: function () {
                                _this.store.reload();
                            }
                        }).show();
                    }
                });
                tbarIdx++;
            }

            if (this._getPermission(permissions, 'edit')) {
                _this.getTopToolbar().insertButton(tbarIdx, { xtype: 'tbspacer' });
                tbarIdx++;
                _this.getTopToolbar().insertButton(tbarIdx, {
                    xtype: 'a-iconbutton',
                    text: '수정',
                    handler: function (self) {
                        var sm = _this.getSelectionModel();

                        if (sm.hasSelection()) {
                            var getRecord = sm.getSelected();
                            new Ariel.glossary.inputFormWindow({
                                title: '수정',
                                type: 'edit',
                                action: 'word',
                                method: 'PUT',
                                getRecord: getRecord,
                                apiUrl: Ariel.glossary.UrlSet.wordIdParam(getRecord.id),
                                onAfterSave: function () {
                                    _this.store.reload();
                                }
                            }).show();
                        } else {
                            Ext.Msg.alert('알림', '수정하실 목록을 선택해주세요.');
                        }
                    }
                });
                tbarIdx++;

            }

            if (this._getPermission(permissions, 'del')) {
                _this.getTopToolbar().insertButton(tbarIdx, { xtype: 'tbspacer' });
                tbarIdx++;
                _this.getTopToolbar().insertButton(tbarIdx, {
                    xtype: 'a-iconbutton',
                    text: '삭제',
                    handler: function (self) {
                        var sm = _this.getSelectionModel();

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
                                            url: Ariel.glossary.UrlSet.wordIdParam(getRecord.id),
                                            callback: function (opts, success, resp) {
                                                if (success) {
                                                    try {
                                                        _this.store.reload();
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
                tbarIdx++;
            }

            if (this._getPermission(permissions, 'accept')) {
                _this.getTopToolbar().insertButton(tbarIdx, { xtype: 'tbspacer' });
                tbarIdx++;
                _this.getTopToolbar().insertButton(tbarIdx, {
                    xtype: 'a-iconbutton',
                    text: '승인',
                    handler: function (self) {
                        var sm = _this.getSelectionModel();
                        if (sm.hasSelection()) {
                            var getRecord = sm.getSelected();
                            Ext.Msg.show({
                                title: '알림',
                                msg: '승인 하시겠습니까?',
                                buttons: Ext.Msg.OKCANCEL,
                                fn: function (btnId, text, opts) {

                                    if (btnId == 'ok') {
                                        Ariel.glossary.ChangeStatus.changeStatus(
                                            'ACCEPT', Ariel.glossary.UrlSet.wordIdParam(getRecord.id), _this.getStore()
                                        );
                                    }
                                }
                            })
                        } else {
                            Ext.Msg.alert('알림', '승인하실 목록을 선택해주세요.');
                        }
                    }
                });
                tbarIdx++;
            }
            if (this._getPermission(permissions, 'refuse')) {
                _this.getTopToolbar().insertButton(tbarIdx, { xtype: 'tbspacer' });
                tbarIdx++;
                _this.getTopToolbar().insertButton(tbarIdx, {
                    xtype: 'a-iconbutton',
                    text: '반려',
                    handler: function (self) {
                        var sm = _this.getSelectionModel();
                        if (sm.hasSelection()) {
                            var getRecord = sm.getSelected();

                            Ext.Msg.show({
                                title: '알림',
                                msg: '반려 하시겠습니까?',
                                buttons: Ext.Msg.OKCANCEL,
                                fn: function (btnId, text, opts) {
                                    if (btnId == 'ok') {
                                        Ariel.glossary.ChangeStatus.changeStatus(
                                            'REFUSE', Ariel.glossary.UrlSet.wordIdParam(getRecord.id), _this.getStore()
                                        );
                                    }
                                }
                            });

                        } else {
                            Ext.Msg.alert('알림', '승인하실 목록을 선택해주세요.');
                        }
                    }
                });
                tbarIdx++;
            }


            // if (this._getPermission(permissions, 'request')) {
            //     _this.getTopToolbar().insertButton(9, { xtype: 'tbspacer' });
            //     _this.getTopToolbar().insertButton(10, {
            //         xtype: 'a-iconbutton',
            //         text: 'Excel 업로드',
            //         handler: function (self) {

            //             new Ext.Window({
            //                 title : 'Excel 업로드',
            //                 width: 500,
            //                 height: 500,
            //                 items: [{
            //                     xtype:'form',
            //                     items: [{
            //                         xtype: 'fileuploadfield',
            //                         buttonOnly: true,
            //                         buttonText: '',
            //                         name: 'FileUpload',
            //                         multiple: false,
            //                         setFiles : function(files) {
            //                             //files, uploaded by add button OR dragged files info.
            //                             var names = [];
            //                             if (files) {                                               
            //                             }
            //                             var newFiles = Array.from(files);

            //                             this.setValue(values);
            //                         },
            //                         listeners: {
            //                             fileselected: function(self, value,event){

            //                                 // self.setFiles(files);
            //                             },
            //                             afterrender: function(self){

            //                             }
            //                         }
            //                     }],
            //                     buttons: [
            //                         {
            //                             text:'업로드'
            //                         }
            //                     ]
            //                 }]
            //             }).show();
            //         }
            //     });
            // }

            if (this._getPermission(permissions, 'request')) {
                _this.getTopToolbar().insertButton(tbarIdx, { xtype: 'tbspacer' });
                tbarIdx++;
                _this.getTopToolbar().insertButton(tbarIdx, {
                    xtype: 'a-iconbutton',
                    text: 'Excel 다운로드',
                    handler: function (self) {

                        Ariel.helper.postDownload('/api/v1/data-dic-words/export', null);
                    }
                });
                tbarIdx++;
            }

            _this.getTopToolbar().doLayout();

        },
        _initialize: function () {
            var _this = this;

            this.apiUrl = Ariel.glossary.UrlSet.word;

            this.store = new Ext.data.JsonStore({
                remoteSort: true,
                restful: true,
                proxy: new Ext.data.HttpProxy({
                    method: 'GET',
                    url: Ariel.glossary.UrlSet.word,
                    type: 'rest'
                }),
                remoteSort: true,
                totalProperty: 'total',
                root: 'data',
                fields: [
                    { name: 'id', type: 'int' },
                    { name: 'no', type: 'int' },
                    'word_se',
                    'word_se_nm',
                    'word_nm',
                    'word_eng_nm',
                    'domain',
                    'word_eng_abrv_nm',
                    'word_st_code',
                    { name: 'regist_dt', type: 'date' },
                    { name: 'updt_dt', type: 'date' },
                    'delete_yn'
                ]
            });

            this.searchField = new Ariel.glossary.searchField({
                store: this.store,
                pageSize: this.pageSize,
                width: 320
            });

            this.cm = new Ext.grid.ColumnModel({
                defaults: {
                    align: 'center',
                    menuDisabled: true,
                    sortable: false
                },
                columns: [
                    columnRowIndex(50),
                    { header: 'ID', dataIndex: 'id' },
                    { header: 'NO', dataIndex: 'no', sortable: true },
                    {
                        header: '구분', dataIndex: 'word_se_nm', renderer: function (value, metaData, record, rowIndex, colIndex, store) {
                            if (value == null) {
                                return null;
                            } else {
                                return value.code_itm_nm;
                            }
                        }
                    },
                    { header: '용어명', dataIndex: 'word_nm', sortable: true, align: 'left' },
                    { header: '영문약어명', dataIndex: 'word_eng_abrv_nm', sortable: true, align: 'left' },
                    { header: '영문정식명', dataIndex: 'word_eng_nm', sortable: true, align: 'left' },
                    {
                        header: '도메인명', dataIndex: 'domain', renderer: function (value, metaData, record, rowIndex, colIndex, store) {
                            if (value == null) {
                                return null;
                            } else {
                                return value.domn_nm;
                            }
                        },
                        sortable: true,
                        align: 'left'
                    },
                    {
                        header: '상태', dataIndex: 'word_st_code', renderer: function (value, metaData, record, rowIndex, colIndex, store) {
                            if (value == null) {
                                return null;
                            } else {
                                return value.code_itm_nm;
                            }
                        },
                    },
                    { header: '생성일시', dataIndex: 'regist_dt', renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s'), hidden: true },
                    { header: '수정일시', dataIndex: 'updt_dt', renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s'), hidden: true },
                ]
            });

            this.bbar = new Ext.PagingToolbar({
                pageSize: this.pageSize,
                store: this.store,
            });

            //기본 둘바 버튼
            this.tbar = ['->', ' ',
                _this.searchField, ' '
            ];
        }
    });
    return new Ariel.glossary.WordListGrid();
})()