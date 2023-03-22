(function () {
    Ext.ns('Ariel.DashBoard');
    Ariel.DashBoard.Storage = Ext.extend(Ext.grid.GridPanel, {
        buttonShow: false,
        _home: false,

        permission_code: 'charge.pd',
        title: '<span class="user_span"><span class="icon_title"><i class=""></i></span><span class="main_title_header">' + '스크래치 폴더' + '</span></span>',
        cls: 'grid_title_customize proxima_customize',
        loadMask: true,
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
            var that = this;
            var getUserMapping = Ext.getCmp(this.userMappingId);
            var tbar = this.getTopToolbar();

            var userMappingButtonInsertIndex = tbar.items.indexOf(getUserMapping);
            if (this._getPermission(permissions)) {
                tbar.insertButton(userMappingButtonInsertIndex, that.mapping_user(that, null));
            };

            tbar.doLayout();
        },
        createWindow: function (action, list, target) {

            var that = this;

            var ownerCdValue = that.ownerCdValue;
            var chmodValue = that.chmodValue;
            var prefixValue = that.prefixValue;
            if (action == 'edit') {
                var title = '폴더 수정';
            } else {
                var title = '폴더 추가';
            }

            //if( Ext.isEmpty(Ext.getCmp(target.windowId))) {
            var add_win = new Ext.Window({
                layout: 'fit',
                id: target.windowId,
                code: action,
                title: title,
                width: 450,
                height: 480,
                //closeAction: 'hide',
                modal: true,
                resizable: true,
                plian: true,
                items: [{
                    xtype: 'form',
                    border: false,
                    frame: true,
                    width: '100%',
                    flex: 1.3,
                    defaults: {
                        anchor: '95%'
                    },
                    items: [{
                        xtype: 'hidden',
                        name: 'id'
                    }, {
                        xtype: 'hidden',
                        name: 'parent_id',
                        value: 4
                    }, {
                        xtype: 'hidden',
                        name: 'step',
                        value: 2
                    }, {
                        name: 'folder_path_nm',
                        xtype: 'textfield',
                        //readOnly: true,
                        allowBlank: false,
                        emptyText: '폴더명 입력해주세요',
                        fieldLabel: '폴더명 입력'
                    }, {
                        name: 'folder_path',
                        xtype: 'textfield',
                        allowBlank: false,
                        regex: /^[A-Za-z0-9\_+]*$/,
                        emptyText: '폴더영문명 입력해주세요',
                        fieldLabel: '폴더영문명 입력',
                        enableKeyEvents: true,
                        listeners: {
                            keydown: function (e, t, o) {
                                this.fireEvent('upperSetValue');
                            },
                            change: function (e, t, o) {
                                this.fireEvent('upperSetValue');
                            },
                            upperSetValue: function (e, t, o) {
                                this.upperSetValue();
                            }
                        }, upperSetValue: function () {
                            var newValue = this.getValue().toLowerCase();
                            this.setValue(newValue);
                            this.ownerCt.getForm().findField('group_cd').fireEvent('customSelect', newValue);
                        }
                    }, {
                        hidden: true,
                        fieldLabel: '권한',
                        name: 'chmod',
                        xtype: 'textfield',
                        value: chmodValue,
                        readOnly: true
                    }, {
                        hidden: true,
                        fieldLabel: '소유자',
                        name: 'owner_cd',
                        xtype: 'textfield',
                        readOnly: true,
                        value: ownerCdValue
                    }, {
                        fieldLabel: '소유그룹',
                        name: 'group_cd',
                        xtype: 'textfield',
                        readOnly: true,
                        listeners: {
                            customSelect: function (value) {
                                this.setValue(prefixValue + '_' + value);
                            }
                        }
                    }, {
                        layout: 'hbox',
                        fieldLabel: '쿼터',
                        xtype: 'compositefield',
                        items: [{
                            flex: 1,
                            name: 'quota',
                            xtype: 'numberfield',
                            maxValue: 1000,
                            allowBlank: false
                        }, {
                            xtype: 'combo',
                            name: 'quota_unit',
                            typeAhead: true,
                            width: 100,
                            triggerAction: 'all',
                            mode: 'local',
                            editable: false,
                            value: 'T',
                            store: new Ext.data.SimpleStore({
                                fields: [
                                    'typeId',
                                    'displayType'
                                ],
                                data: [
                                    ['T', 'TB'],
                                    ['G', 'GB'],
                                    ['M', 'MB']
                                ]
                            }),
                            valueField: 'typeId',
                            displayField: 'displayType'
                        }]
                    },
                    {
                        layout: 'hbox',
                        fieldLabel: '유예기간',
                        xtype: 'compositefield',
                        items: [{
                            flex: 1,
                            name: 'grace_period',
                            xtype: 'numberfield',
                            maxValue: 1000,
                            value: 1,
                            allowBlank: false
                        }, {
                            xtype: 'combo',
                            name: 'grace_period_unit',
                            typeAhead: true,
                            width: 100,
                            triggerAction: 'all',
                            mode: 'local',
                            editable: false,
                            value: 'm',
                            store: new Ext.data.SimpleStore({
                                fields: [
                                    'typeId',
                                    'displayType'
                                ],
                                data: [
                                    ['m', 'Minutes'],
                                    ['h', 'Hours'],
                                    ['d', 'Days'],
                                    ['w', 'Weeks'],
                                    ['y', 'Years']
                                ]
                            }),
                            valueField: 'typeId',
                            displayField: 'displayType'
                        }]
                    },
                    {
                        name: 'expired_date',
                        fieldLabel: '사용 종료일',
                        xtype: 'datefield',
                        altFormats: 'Y-m-d|Ymd|YmdHis',
                        editable: true,
                        format: 'Y-m-d',
                        listeners: {
                            render: function (self) {
                                //self.setValue(new Date().format('Y-m-d'));
                            }
                        }
                    }, {
                        name: 'dc',
                        fieldLabel: '부여 사유',
                        xtype: 'textarea'
                    }, {
                        name: 'ntcn_yn',
                        fieldLabel: '알림 설정',
                        xtype: 'checkbox',
                        inputValue: 'Y',
                        inputValueFalse: 'N',
                        listeners: {
                            check: function (self, checked) {
                            }
                        }
                    }],
                    customGetValues: function () {
                        //날짜필드 체크박스 값 보정
                        var form = this;
                        var values = form.getValues();
                        //var fieldValues = form.getFieldValues();
                        var formList = form.items.items;

                        for (var i = 0; i < formList.length; i++) {
                            if (formList[i].xtype == 'checkbox') {

                                if (formList[i].getValue() === false) {
                                    if (formList[i].inputValueFalse != undefined) {
                                        values[formList[i].name] = formList[i].inputValueFalse;
                                    }
                                }
                            } else if (formList[i].xtype == 'datefield' && !Ext.isEmpty(formList[i].getValue())) {
                                values[formList[i].name] = formList[i].getValue().format("YmdHis");
                            }
                        }
                        return values;
                    }
                }],
                buttons: [{
                    xtype: 'aw-button',
                    iCls: 'fa fa-check',
                    scale: 'medium',
                    text: '저장',
                    handler: function () {


                        var form = Ext.getCmp(that.windowId).get(0).getForm();

                        if (!form.isValid()) {
                            Ext.Msg.alert('알림', '올바른 값을 입력해주세요');
                            return false;
                        }

                        var formValues = form.customGetValues();

                        var itemId = formValues.id;
                        if (!Ext.isEmpty(itemId)) {
                            var requestMethod = 'PUT';
                            var requestUrl = '/api/v1/folder-mngs/' + itemId;
                        } else {
                            var requestMethod = 'POST';
                            var requestUrl = '/api/v1/folder-mngs';
                        }

                        var waitMsg = Ext.Msg.wait('처리 중입니다.', '처리중...');

                        Ext.Ajax.request({
                            timeout: 180000,
                            method: requestMethod,
                            url: requestUrl,
                            params: formValues,
                            callback: function (opt, suc, res) {
                                waitMsg.hide();
                                var r = Ext.decode(res.responseText);
                                if (suc) {
                                    if (r.success) {
                                        Ext.getCmp(that.windowId).hide();
                                        Ext.getCmp(that.gridId).getStore().reload();
                                        Ext.getCmp(that.windowId).close();
                                    }
                                    else {
                                        Ext.Msg.alert('저장', r.msg);
                                    }
                                } else {
                                    Ext.Msg.alert('오류', r.msg);
                                }
                            }
                        });

                    }
                }, {
                    xtype: 'aw-button',
                    iCls: 'fa fa-times',
                    scale: 'medium',
                    text: '닫기',
                    handler: function (self) {
                        self.ownerCt.ownerCt.close();
                    }
                }],
                listeners: {
                    afterrender: function (self) {
                        if (action == 'edit') {
                            if (!Ext.isEmpty(list)) {
                                var form = self.get(0).getForm();
                                var record = new Ext.data.Record(list);
                                form.loadRecord(record);
                            }

                            form.findField('folder_path').setReadOnly(true);
                        } else if (action == 'add') {
                        }
                    }
                }
            });

            return add_win;
            //}
        },
        initComponent: function (config) {
            Ext.apply(this, config || {});

            var that = this;

            this.isLoading = false;
            this.prefixValue = 'group';

            this.ownerCdValue = 'admin';
            this.chmodValue = '775';

            this.parentId = 4;

            this.windowId = Ext.id();

            this.gridId = Ext.id();
            this.id = that.gridId;

            this.userMappingId = Ext.id();

            this.add_category_node = function (target, xtype) {
                if (xtype == null) {
                    xtype = 'aw-button';
                }
                return {
                    hidden: that.buttonShow,
                    xtype: xtype,
                    iCls: 'fa fa-plus',
                    text: '폴더 추가',
                    cmd: 'add-category-node',
                    //text: '폴더 추가',
                    handler: function () {
                        var win = target.createWindow('add', '', target);
                        win.show();
                    }
                };
            };

            this.edit_category_node = function (target, xtype) {
                if (xtype == null) {
                    xtype = 'aw-button';
                }
                return {
                    hidden: that.buttonShow,
                    xtype: xtype,
                    iCls: 'fa fa-edit',
                    cmd: 'edit-category-node',
                    text: '폴더 수정',
                    handler: function () {
                        var sel = target.getSelectionModel().getSelected();
                        if (Ext.isEmpty(sel)) {
                            Ext.Msg.alert('알림', '목록을 선택하여 주세요');
                            return;
                        } else {

                            var win = target.createWindow('edit', sel.data, target);
                            //Ext.getCmp(target.windowId).setSize(500,400);
                            win.show();
                        }
                    }
                };
            };

            this.delete_category_node = function (target, xtype) {
                if (xtype == null) {
                    xtype = 'aw-button';
                }
                return {
                    hidden: that.buttonShow,
                    xtype: xtype,
                    cmd: 'delete-category-node',
                    iCls: 'fa fa-ban',
                    text: '폴더 삭제',
                    handler: function () {

                        var sel = target.getSelectionModel().getSelected();
                        if (Ext.isEmpty(sel)) {
                            Ext.Msg.alert('알림', '목록을 선택하여 주세요');
                            return;
                        } else {

                            var id = sel.get('id');

                            var requestMethod = 'DELETE';
                            var requestUrl = '/api/v1/folder-mngs/{id}';

                            requestUrl = requestUrl.replace('{id}', id);

                            Ext.Msg.show({
                                title: '삭제',
                                msg: '폴더를 삭제 하시겠습니까?',
                                buttons: Ext.Msg.YESNO,
                                animEl: 'elId',
                                icon: Ext.MessageBox.QUESTION,
                                fn: function (btnId, text, opts) {
                                    if (btnId == 'no') return;
                                    var waitMsg = Ext.Msg.wait('처리 중입니다.', '처리중...');

                                    Ext.Ajax.request({
                                        method: requestMethod,
                                        url: requestUrl,
                                        callback: function (opt, suc, res) {
                                            waitMsg.hide();
                                            if (suc) {
                                                var r = Ext.decode(res.responseText);
                                                if (r.success) {
                                                    // that.getStore().reload();
                                                    that.getStore().reload();
                                                    Ext.Msg.alert('알림', '삭제되었습니다.');
                                                }
                                                else {
                                                    Ext.Msg.alert('알림', r.msg);
                                                }
                                            }
                                            else {
                                                Ext.Msg.alert('오류', res.responseText);
                                            }
                                        }
                                    });

                                }
                            });
                        }
                    }
                };
            };
            this.mapping_user = function (target, xtype) {
                if (xtype == null) {
                    xtype = 'aw-button';
                }
                return {
                    // hidden: that.buttonShow,
                    hidden: that._home,
                    xtype: xtype,
                    iCls: 'fa fa-user-plus',
                    cmd: 'delete-category-node',
                    text: '사용자 매핑',
                    handler: function () {
                        var sel = target.getSelectionModel().getSelected();

                        if ((Ext.isEmpty(sel))) {
                            Ext.Msg.alert('알림', '매핑할 프로그램을 선택하여 주세요');
                            return;
                        }

                        var ownerArray = [];
                        var owners = sel.get('owners');

                        Ext.each(owners, function (r) {
                            ownerArray.push(r.user_id);
                        });
                        Ext.Ajax.request({
                            method: 'GET',
                            url: '/api/v1/users-admin-check',
                            params: {
                                owners: Ext.encode(ownerArray)
                            },
                            callback: function (opts, success, res) {
                                var res = Ext.decode(res.responseText);
                                if (!res.data) {
                                    Ext.Msg.alert('알림', '담당PD 또는 관리자만 맵핑 해주세요.');
                                } else {
                                    var folderPath = sel.get('folder_path');
                                    var folderId = sel.get('id');
                                    var folderPathNM = sel.get('folder_path_nm');

                                    new Ariel.System.UserMapWindow({
                                        folderPath: folderPath,
                                        folderPathNM: folderPathNM,
                                        folderId: folderId,
                                        saveUrl: null,
                                        listeners: {
                                        }
                                    }).show();
                                }
                            }
                        });


                    }
                };
            };


            this.set_notice_quota = function (target, xtype) {
                if (xtype == null) {
                    xtype = 'aw-button';
                }
                return {
                    hidden: that.buttonShow,
                    xtype: xtype,
                    text: '쿼터 알림 설정',
                    handler: function () {
                        Ext.Ajax.request({
                            url: '/pages/menu/config/Program/get_notice_quota_form.php',
                            callback: function (self, success, response) {
                                try {
                                    var r = Ext.decode(response.responseText);
                                    r.show();
                                }
                                catch (e) {
                                    Ext.Msg.alert(_text('MN00022'), e);
                                }
                            }
                        });
                    }
                };
            };

            this.refresh = function (that) {
                that.getStore().reload();
            }

            this.refresh_button = function (target, xtype) {
                if (xtype == null) {
                    xtype = 'aw-button';
                }
                return {
                    xtype: xtype,
                    iCls: 'fa fa-refresh',
                    text: '새로고침',
                    handler: function (btn, e) {
                        that.getStore().reload();
                    }
                }
            }

            this.tbarSearchTypeId = Ext.id();
            this.tbarSearchValueId = Ext.id();


            this.doSearch = function () {

                var paramKey = Ext.getCmp(that.tbarSearchTypeId).getValue();
                var paramVal = Ext.getCmp(that.tbarSearchValueId).getValue();
                var params = [];
                params[paramKey] = paramVal;

                params['parent_id'] = 4;
                params['my_list'] = 1;

                that.getStore().load({
                    params: params
                });
            }
            this.tbar = [{
                xtype: 'combo',
                id: this.tbarSearchTypeId,
                itemId: this.tbarSearchTypeId,
                typeAhead: true,
                triggerAction: 'all',
                mode: 'local',
                width: 80,
                editable: false,
                hidden: false,
                store: new Ext.data.SimpleStore({
                    fields: ['id', 'name'],
                    data: [
                        ['folder_path_nm', '폴더명'],
                        ['folder_path', '폴더영문명']
                    ]
                }),
                valueField: 'id',
                displayField: 'name',
                value: 'folder_path_nm'
            }, {
                xtype: 'displayfield',
                width: 5
            }, {
                xtype: 'textfield',
                id: this.tbarSearchValueId,
                listeners: {
                    specialKey: function (self, e) {
                        if (e.getKey() == e.ENTER && self.isValid()) {
                            e.stopEvent();
                            that.doSearch();
                        }
                    }
                }
            }, {
                xtype: 'aw-button',
                iCls: 'fa fa-search',
                text: '검색',
                handler: function () {
                    that.doSearch();
                }
            },
                '-',
            that.refresh_button(that, null),
            { hidden: that.buttonShow, xtype: 'tbseparator' },
            { xtype: 'tbspacer', width: 50 },
            that.add_category_node(that, null),
            { hidden: that.buttonShow, xtype: 'tbseparator' },
            that.edit_category_node(that, null),
            { hidden: that.buttonShow, xtype: 'tbseparator' },
            that.delete_category_node(that, null),
            { hidden: that.buttonShow, xtype: 'tbseparator' },
            { xtype: 'tbspacer', width: 50 },
            { xtype: 'hidden', id: that.userMappingId },
            { hidden: that.buttonShow, xtype: 'tbseparator' },
            {
                hidden: true,
                xtype: 'aw-button',
                iCls: 'fa fa-file-excel-o',
                text: '엑셀로 저장',
                handler: function (b, e) {
                    var grid = that;
                    excelDataProgram('', '/pages/menu/config/Program/php/data.php', grid.colModel, 'grid_category', Ext.getCmp(this.tbarSearchTypeId).getValue(), Ext.getCmp(this.tbarSearchValueId).getValue());
                }
            }, '->', {
                xtype: 'displayfield',
                value: '<b></b>'
            }, {
                hidden: that.buttonShow,
                xtype: 'aw-button',
                iCls: 'fa fa-refresh',
                text: '동기화',
                handler: function (b, e) {
                    var w = Ext.Msg.wait('처리 중');

                    Ext.Ajax.request({
                        url: '/api/v1/folder-mngs-sync',
                        timeout: 600000,
                        callback: function (opt, suc, res) {
                            w.hide();
                            that.refresh(that);

                        }
                    });
                }
            }
            ];

            this.colModel = new Ext.grid.ColumnModel({
                defaults: {
                    align: 'center',
                    menuDisabled: true,
                    sortable: false
                },
                columns: [
                    { header: '폴더명', dataIndex: 'folder_path_nm', width: 150, hidden: true },
                    { header: '폴더', dataIndex: 'folder_path_nm', width: 150, sortable: true },
                    { header: '폴더영문명', dataIndex: 'folder_path', width: 120, hidden: true },
                    { header: '그룹', dataIndex: 'group_cd', width: 100, sortable: true },
                    {
                        header: '쿼터',
                        dataIndex: 'quota',
                        sortable: true,
                        width: 80,
                        renderer: function (value, metaData, record, rowIndex, colIndex, store) {
                            return value + ' ' + record.get('quota_unit');
                        },
                    },
                    //{header: '쿼터 상태', dataIndex: 'status', sortable: false , width: 80 },	
                    {
                        header: '사용량', dataIndex: 'cursize', width: 100, sortable: true, renderer: function (value, meta, record, rowIndex, colIndex, store, pct) {
                            if (isValue(value)) {
                                return value;
                            } else {
                                return '0.0G';
                            }
                        }
                    },
                    new Ext.ux.ProgressColumn({
                        header: '사용률',
                        width: 150,
                        dataIndex: 'cursize_num',
                        align: 'center',
                        sortable: true,
                        _fraction: null,
                        getFraction: function (value, meta, record, rowIndex, colIndex, store) {
                            var curSize = record.get('cursize_num');
                            var maxSize = record.get('softlimit_num');
                            var curValue = 0;
                            if (curSize == 0 || maxSize == 0) {
                            } else {
                                curValue = (curSize / maxSize) * 100;
                                curValue = Math.round(curValue);
                            }
                            var fraction = 0;
                            if (record) {
                                if (this.dividend) {
                                    fraction = record.get(this.dividend) / curValue;
                                }
                                else if (this.divisor) {
                                    fraction = curValue / record.get(this.divisor);
                                } else {
                                    fraction = curValue / 100;
                                }
                                if (fraction < 0) {
                                    fraction = 0;
                                }
                            }
                            if (!isValue(value)) {
                                fraction = 0;
                            }
                            this.fraction = fraction;

                            return fraction;

                        },
                        // tpl: new Ext.XTemplate(
                        //     '<tpl if="align == \'left\'">',
                        //     '<div class="ux-progress-cell-inner ux-progress-cell-inner-{align} ux-progress-cell-storage-background">',
                        //     '<div>{value}</div>',
                        //     '</div>',
                        //     '<div class="ux-progress-cell-inner ux-progress-cell-inner-{align} ux-progress-cell-foreground {cls}" style="width:{pct}%" ext:qtip="{qtip}">',
                        //     '<div ext:qtip="{qtip}">{value}</div>',
                        //     '</div>',
                        //     '</tpl>',
                        //     '<tpl if="align != \'left\'">',
                        //     '<div class="ux-progress-cell-inner ux-progress-cell-inner-{align} ux-progress-cell-foreground {cls}" ext:qtip="{qtip}">',
                        //     '<div ext:qtip="{qtip}">{value}</div>',
                        //     '</div>',
                        //     '<div class="ux-progress-cell-inner ux-progress-cell-inner-{align} ux-progress-cell-storage-background" style="left:{pct}%">',
                        //     '<div style="left:-{pct}%">{value}</div>',
                        //     '</div>',
                        //     '</tpl>'
                        // ),
                        // getBarClass: function (fraction) {
                        //     if (fraction >= 1) {
                        //         return 'over';
                        //     }
                        //     return (fraction > 0.9) ? 'usage2' : 'usage';
                        // },
                        renderer: function (value, meta, record, rowIndex, colIndex, store, pct) {
                            return Ext.util.Format.number(this.fraction * 100, "0%");
                        },
                        sortable: true
                    }),
                    {
                        header: '유예기간', dataIndex: 'grace_period', sortable: false, width: 120, renderer: function (value, metaData, record, rowIndex, colIndex, store) {
                            return value + ' ' + record.get('grace_period_unit');
                        },
                        hidden: true
                    },

                    //{header: 'Hard Limit', dataIndex: 'hardlimit', sortable: false , width: 80 },	
                    //{header: 'Soft Limit', dataIndex: 'quota', sortable: false , width: 80 },					
                    { header: '사용자 정보', dataIndex: 'user_names', sortable: false, width: 70, hidden: true },
                    {
                        header: '알림설정 여부', dataIndex: 'ntcn_yn', sortable: false, width: 90, align: 'center', renderer: function (value) {
                            if (value == 'Y') {
                                return 'ON';
                            } else {
                                return 'OFF';
                            }
                        },
                        hidden: true
                    },
                    { header: '사용종료일', dataIndex: 'expired_date', width: 100, renderer: Ext.util.Format.dateRenderer('Y-m-d'), align: 'center', hidden: true },
                    { header: '등록일시', dataIndex: 'updated_at', width: 130, renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s'), align: 'center', hidden: true },
                    { header: '수정일시', dataIndex: 'created_at', width: 130, renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s'), align: 'center', hidden: true },
                    { header: '부여 사유', dataIndex: 'dc', width: 300, hidden: true },
                    {
                        header: '담당자', dataIndex: 'owner_info', width: 150, renderer: function (v) {
                            if (!Ext.isEmpty(v)) {
                                v = v[0];
                                return v.user_nm + '(' + v.user_id + ')';
                            }
                        }
                    }
                ]
            });
            this.selModel = new Ext.grid.RowSelectionModel({
                singcleSelect: true
            });
            this.store = new Ext.data.JsonStore({
                restful: true,
                remoteSort: true,
                proxy: new Ext.data.HttpProxy({
                    method: 'GET',
                    url: '/api/v1/folder-mngs',
                    type: 'rest'
                }),
                sortInfo: {
                    field: 'id',
                    direction: 'DESC'
                },
                idProperty: 'id',
                root: 'data',
                fields: [
                    { name: 'id', type: 'int' },

                    { name: 'fsname' },
                    { name: 'softlimit' },
                    { name: 'hardlimit' },
                    { name: 'cursize' },
                    { name: 'softlimit_num' },
                    { name: 'hardlimit_num' },
                    { name: 'cursize_num', type: 'int' },
                    { name: 'status' },

                    'folder_path',
                    'folder_path_nm',
                    'group_cd',
                    { name: 'quota', type: 'int' },
                    'chmod',
                    'owner_cd',
                    'quota_unit',
                    'grace_period',
                    'grace_period_unit',
                    'dc',
                    'ntcn_yn',
                    'owners',
                    { name: 'created_at', type: 'date' },
                    { name: 'updated_at', type: 'date' },
                    { name: 'expired_date', type: 'date', dateFormat: 'YmdHis' },
                    'owner_info'
                ],
                listeners: {
                    beforeload: function (self, opts) {
                    },
                    load: function (self, records, opts) {
                    }
                }
            });
            this.bbar = {
                xtype: 'paging',
                pageSize: 30,
                displayInfo: true,
                store: this.store
            };
            this.contextMenu = new Ext.menu.Menu({
                items: [
                    that.add_category_node(that, 'menuitem'),
                    that.edit_category_node(that, 'menuitem'),
                    that.delete_category_node(that, 'menuitem'),
                    '-',
                    that.mapping_user(that, 'menuitem')
                ]
            });
            this.listeners = {
                viewready: function (self) {
                    that.doSearch();
                },
                rowcontextmenu: function (self, rowIndex, e) {
                    e.stopEvent();
                    self.getSelectionModel().selectRow(rowIndex);

                    var c = self.contextMenu;
                    c.showAt(e.getXY());
                },
                rowdblclick: function (self, rowIndex, e) {
                    // e.stopEvent();
                    // var sel = self.getSelectionModel().getSelected();

                    // if (Ext.isEmpty(sel)) {
                    //     Ext.Msg.alert('알림', '목록을 선택하여 주세요');
                    //     return;
                    // }
                    // var ownerArray = [];
                    // var owners = sel.get('owners');

                    // Ext.each(owners, function (r) {
                    //     ownerArray.push(r.user_id);
                    // });
                    // return Ext.Ajax.request({
                    //     method: 'GET',
                    //     url: '/api/v1/users-admin-check',
                    //     params: {
                    //         owners: Ext.encode(ownerArray)
                    //     },
                    //     callback: function (opts, success, res) {
                    //         var res = Ext.decode(res.responseText);
                    //         if (!res.data) {
                    //             Ext.Msg.alert('알림', '권한이 없습니다.');
                    //         } else {
                    //             var win = that.createWindow('edit', sel.data, self);
                    //             win.show();
                    //         }
                    //     }
                    // });

                    //Ext.getCmp(that.windowId).setSize(400,250);

                }
            };
            Ariel.DashBoard.Storage.superclass.initComponent.call(this);
        }
    });
    // return new Ariel.DashBoard.Storage();
})()