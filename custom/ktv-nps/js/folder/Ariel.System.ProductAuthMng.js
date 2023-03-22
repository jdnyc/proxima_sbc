(function () {

    Ext.ns('Ariel.System');

    Ariel.System.ProductAuthMng = Ext.extend(Ext.Panel, {
        layout: 'fit',
        autoScroll: true,
        border: false,
        setLoading: function (isLoading) {
            this.isLoading = isLoading;
            return true;
        },
        getLoading: function () {
            return this.isLoading;
        },
        createWindow: function (action, list, target) {

            var that = this;

            var ownerCdValue = that.ownerCdValue;
            var chmodValue = that.chmodValue;
            var prefixValue = that.prefixValue;
            var groupCd = that.groupCd;


            if (Ext.isEmpty(list)) {
                var title = '프로그램 추가';
            } else {
                var title = '프로그램 수정';
            }

            var formId = Ext.id();

            var bis_program = new Ariel.Nps.BISProgram({
                flex: 3,
                listeners: {
                    selectProgram: function (self, sel) {

                        var pgm_id = sel.data.pgm_id;
                        var pgm_nm = sel.data.pgm_nm;
                        var form = Ext.getCmp(formId).getForm();
                        //if (action == 'add') {
                        form.findField('pgm_id').setValue(pgm_id);
                        //}
                        form.findField('folder_path_nm').setValue(pgm_nm);
                    }
                }
            });


            var add_win = new Ext.Window({
                layout: 'fit',
                id: target.windowId,
                code: action,
                title: title,
                width: 700,
                height: 620,
                //closeAction: 'hide',
                modal: true,
                resizable: true,
                plian: true,
                items: [{
                    xtype: 'form',
                    id: formId,
                    border: false,
                    frame: true,
                    defaults: {
                        anchor: '95%'
                    },
                    items: [{
                        xtype: 'hidden',
                        name: 'id'
                    }, {
                        xtype: 'hidden',
                        name: 'step',
                        value: 3
                    }, {
                        //hidden: true,
                        xtype: 'radiogroup',
                        fieldLabel: '폴더 유형',
                        anchor: '95%',
                        columns: 2,
                        items: [{
                            boxLabel: '뉴스',
                            name: 'parent_id',
                            inputValue: '3'
                        }, {
                            boxLabel: '제작',
                            name: 'parent_id',
                            inputValue: '2',
                            checked: true
                        }]
                    }, {
                        name: 'folder_path_nm',
                        xtype: 'textfield',
                        //readOnly: true,
                        emptyText: '프로그램명을 입력해주세요',
                        fieldLabel: '프로그램명 입력'
                    }, {
                        name: 'pgm_id',
                        xtype: 'textfield',
                        fieldLabel: '프로그램ID',
                        readOnly: true
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
                            //this.setValue( newValue );
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
                        hidden: true,
                        fieldLabel: '소유그룹',
                        name: 'group_cd',
                        xtype: 'textfield',
                        readOnly: true,
                        value: groupCd,
                        listeners: {
                            customSelect: function (value) {
                                // if( !Ext.isEmpty(prefixValue) ){
                                //     this.setValue(prefixValue + '_'+ value);
                                // }else{
                                //     this.setValue(value);
                                // }
                            }
                        }
                    }, {
                        name: 'quota',
                        xtype: 'numberfield',
                        fieldLabel: 'QUOTA(GB)',
                        hidden: true,
                        readOnly: true
                    }, {
                        name: 'using_yn',
                        xtype: 'checkbox',
                        checked: true,
                        inputValue: 'Y',
                        inputValueFalse: 'N',
                        fieldLabel: '사용여부'
                    }, {
                        xtype: 'fieldset',
                        submitValue: false,
                        checkboxToggle: true,
                        checkboxName: 'bis-program',
                        title: '프로그램 조회',
                        height: 400,
                        layout: 'fit',
                        items: [
                            bis_program
                        ]
                    }
                    ],
                    customGetValues: function () {
                        //날짜필드 체크박스 값 보정
                        var form = this;
                        var values = form.getValues();
                        var fieldValues = form.getFieldValues();
                        //var fieldValues = form.getFieldValues();
                        var formList = form.items.items;
                        console.log(values);
                        console.log(fieldValues);
                        for (var i = 0; i < formList.length; i++) {

                            console.log(formList[i]);
                            console.log(formList[i].getValue());

                            if (formList[i].xtype == 'checkbox') {

                                if (formList[i].getValue() === false) {
                                    if (formList[i].inputValueFalse != undefined) {
                                        values[formList[i].name] = formList[i].inputValueFalse;
                                    }
                                }
                            } else if (formList[i].xtype == 'combo') {
                                values[formList[i].name] = formList[i].getValue();

                            } else if (formList[i].xtype == 'datefield' && !Ext.isEmpty(formList[i].getValue())) {
                                values[formList[i].name] = formList[i].getValue().format("YmdHis");
                            }
                        }

                        if (values['bis-program'] != 'on') {
                            delete values['pgm_id'];
                        }
                        delete values['bis-program'];

                        return values;
                    }
                }],
                buttons: [{
                    xtype: 'aw-button',
                    iCls: 'fa fa-check',
                    scale: 'medium',
                    text: '저장',
                    handler: function () {
                        var form = Ext.getCmp(target.windowId).get(0).getForm();

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
                                        Ext.getCmp(target.windowId).hide();
                                        Ext.getCmp(target.gridId).getStore().reload();
                                        Ext.getCmp(target.windowId).close();
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
                            if (form.findField('folder_path')) {
                                form.findField('folder_path').setReadOnly(true);
                            }
                        }
                    }
                }
            });
            return add_win;

        },
        initComponent: function (config) {
            Ext.apply(this, config || {});

            var that = this;
            var _this = this;

            this.isLoading = false;
            this.prefixValue = '';

            this.ownerCdValue = 'admin';
            this.chmodValue = '755';

            this.groupCd = 'cms_group';

            this.windowId = Ext.id();

            this.gridId = Ext.id();


            this.tbarSearchTypeId = Ext.id();
            this.tbarSearchValueId = Ext.id();
            this.tbarFolderTypeId = Ext.id();
            this.tbarDateTypeComboId = Ext.id();

            this.defaultFolderTypeValue = 2;

            this.add_category_node = function (target, xtype) {
                if (xtype == null) {
                    xtype = 'aw-button';
                }
                var rtn = {
                    xtype: xtype,
                    iCls: 'fa fa-plus',
                    text: '프로그램 추가',
                    cmd: 'add-category-node',
                    handler: function (self) {
                        var win = target.createWindow('add', '', target);
                        win.show();
                    }
                };
                return rtn;
            };

            this.edit_category_node = function (target, xtype) {
                if (xtype == null) {
                    xtype = 'aw-button';
                }
                return {
                    xtype: xtype,
                    iCls: 'fa fa-edit',
                    cmd: 'edit-category-node',
                    text: '프로그램 수정',
                    handler: function () {
                        var sel = target.get(0).getSelectionModel().getSelected();
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
                    xtype: xtype,
                    cmd: 'delete-category-node',
                    iCls: 'fa fa-ban',
                    text: '프로그램 삭제',
                    handler: function () {

                        var sel = target.get(0).getSelectionModel().getSelected();
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
                                msg: '프로그램을 삭제 하시겠습니까?',
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
                                                    Ext.getCmp(that.gridId).getStore().reload();
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
                    xtype: xtype,
                    iCls: 'fa fa-user-plus',
                    cmd: 'delete-category-node',
                    text: '사용자 매핑',
                    handler: function () {
                        var sel = target.get(0).getSelectionModel().getSelected();

                        if ((Ext.isEmpty(sel))) {
                            Ext.Msg.alert('알림', '매핑할 프로그램을 선택하여 주세요');
                            return;
                        }

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
                };
            };
            this.set_notice_quota = function (target, xtype) {
                if (xtype == null) {
                    xtype = 'aw-button';
                }
                return {
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
                that.doSearch();
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
                        Ext.getCmp(that.gridId).getStore().reload();
                    }
                }
            }
            this.pageSize = 30;
            this.doSearch = function () {
                var params = new Object();
                var paramKey = Ext.getCmp(that.tbarSearchTypeId).getValue();
                var paramVal = Ext.getCmp(that.tbarSearchValueId).getValue();

                var searchDateField = Ext.getCmp(that.tbarDateTypeComboId).getValue();
                if (searchDateField !== 'all') {
                    params['search_date_field'] = searchDateField;
                    params['start_date'] = that.startDateField.getValue();
                    params['end_date'] = endDateOf(that.endDateField.getValue());
                }

                //params['pgm_type'] = paramKey;
                //params['pgm_value'] = paramVal;
                params[paramKey] = paramVal;
                params['parent_id'] = Ext.getCmp(this.tbarFolderTypeId).getValue();
                params['using'] = this.grid.getTopToolbar().getComponent('using').getValue().value;
                params['limit'] = that.pageSize;
                //params['action'] = 'grid_category';
                Ext.getCmp(that.gridId).getStore().load({
                    params: params
                });
            }
            this.startDateField = new Ext.form.DateField({
                name: 'start_date',
                editable: false,
                hidden: true,
                width: 105,
                format: 'Y-m-d',
                altFormats: 'Ymd|ymd|Y-m-d|y-m-d|Y/m/d|Y/m/d',
                listeners: {
                    render: function (self) {
                        var d = new Date();

                        self.setMaxValue(d.format('Y-m-d'));
                        self.setValue(d.add(Date.DAY, -7).format('Y-m-d'));
                    },
                    select: function () {
                    }
                }
            });
            this.endDateField = new Ext.form.DateField({
                xtype: 'datefield',
                name: 'end_date',
                hidden: true,
                editable: false,
                width: 105,
                format: 'Y-m-d',
                altFormats: 'Ymd|ymd|Y-m-d|y-m-d|Y/m/d|Y/m/d',
                listeners: {
                    render: function (self) {
                        var d = new Date();

                        self.setMaxValue(d.format('Y-m-d'));
                        self.setValue(d.format('Y-m-d'));
                    },
                    select: function (self, date) {
                        var startDateFieldValue = _this.startDateField.getValue();
                        if (startDateFieldValue > date) {
                            /**
                             * 이전 날짜보다 작은 값을 선택 했을 시
                             * // 이전날짜 선택시 null 값 입력
                             */
                            self.setValue(new Date());
                            return Ext.Msg.alert('알림', '시작날짜보다 이전날짜를 선택할 수 없습니다.');;
                        };
                    }
                }
            });
            this.store = new Ext.data.JsonStore({
                url: '/api/v1/folder-mngs',
                restful: true,
                remoteSort: true,
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
                    { name: 'status' },
                    'folder_path',
                    'folder_path_nm',
                    'group_cd',
                    'quota',
                    'chmod',
                    'owner_cd',
                    'quota_unit',
                    'grace_period',
                    'grace_period_unit',
                    'step',
                    'parent_id',
                    'parent_nm',
                    'dc',
                    'pgm_id',
                    'using_yn',
                    'parent',
                    'owner_info',
                    { name: 'created_at', type: 'date' },
                    { name: 'updated_at', type: 'date' },
                    { name: 'expired_date', type: 'date', dateFormat: 'YmdHis' }
                ],
                listeners: {
                    beforeload: function (self, opts) {
                        // var search_type = Ext.getCmp('pgm_search_type').getValue();
                        // var search_value = Ext.getCmp('pgm_search_value').getValue();

                        // self.baseParams.action = 'grid_category';
                        // self.baseParams.pgm_type = search_type;
                        // self.baseParams.pgm_value = search_value;
                    },
                    load: function (self, records, opts) {
                    }
                }
            });

            var dateFieldShow = Ext.id();
            this.grid = new Ext.grid.GridPanel({
                id: that.gridId,
                loadMask: true,
                tbar: [{
                    xtype: 'displayfield',
                    value: '제작유형:'
                }, {
                    xtype: 'combo',
                    id: this.tbarFolderTypeId,
                    typeAhead: true,
                    triggerAction: 'all',
                    mode: 'local',
                    width: 80,
                    editable: false,
                    store: new Ext.data.SimpleStore({
                        fields: ['id', 'name'],
                        data: [
                            ['2', '제작'],
                            ['3', '뉴스']
                        ]
                    }),
                    valueField: 'id',
                    displayField: 'name',
                    value: this.defaultFolderTypeValue
                }, '-', {
                    xtype: 'displayfield',
                    value: '기간:'
                }, {
                    xtype: 'combo',
                    id: this.tbarDateTypeComboId,
                    mode: 'local',
                    triggerAction: 'all',
                    width: 80,
                    editable: false,
                    store: new Ext.data.SimpleStore({
                        fields: ['id', 'name'],
                        data: [
                            ['all', '전체'],
                            ['created_at', '등록일시'],
                            ['updated_at', '수정일시']
                        ]
                    }),
                    valueField: 'id',
                    displayField: 'name',
                    value: 'all',
                    listeners: {
                        afterrender: function (self) {
                            if (self.getValue() === 'all') {
                                that.startDateField.setVisible(false);
                                Ext.getCmp(dateFieldShow).setVisible(false);
                                that.endDateField.setVisible(false);
                            } else {
                                that.startDateField.setVisible(true);
                                Ext.getCmp(dateFieldShow).setVisible(true);
                                that.endDateField.setVisible(true);
                            }
                        },
                        select: function (self) {
                            if (self.getValue() === 'all') {
                                that.startDateField.setVisible(false);
                                Ext.getCmp(dateFieldShow).setVisible(false);
                                that.endDateField.setVisible(false);
                            } else {
                                that.startDateField.setVisible(true);
                                Ext.getCmp(dateFieldShow).setVisible(true);
                                that.endDateField.setVisible(true);
                            }
                        }
                    }
                },
                    ' ',
                this.startDateField,
                {
                    id: dateFieldShow,
                    hidden: true,
                    html: '~'
                },
                this.endDateField,
                    '-',
                {
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
                            ['folder_path_nm', '프로그램명'],
                            ['folder_path', '폴더영문명'],
                            ['user_query', '사용자']
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
                },
                { xtype: 'tbspacer', width: 20 },
                {
                    xtype: 'radiogroup',
                    itemId: 'using',
                    allowBlank: false,
                    width: 180,
                    items: [{
                        boxLabel: '전체',
                        name: 'using'
                    }, {
                        boxLabel: '사용',
                        name: 'using',
                        value: 'Y',
                        checked: true
                    }, {
                        boxLabel: '미사용',
                        name: 'using',
                        value: 'N'
                    }]
                }, {
                    xtype: 'aw-button',
                    iCls: 'fa fa-search',
                    text: '검색',
                    handler: function () {
                        that.doSearch();
                    }
                },
                    '-',
                that.refresh_button(that)
                    , '-',
                { xtype: 'tbspacer', width: 50 },
                that.add_category_node(that)
                    , '-',
                that.edit_category_node(that),
                    '-',
                that.delete_category_node(that),
                    '-',
                { xtype: 'tbspacer', width: 50 },
                that.mapping_user(that),
                //'-',
                {
                    hidden: true,
                    xtype: 'aw-button',
                    iCls: 'fa fa-file-excel-o',
                    text: '엑셀로 저장',
                    handler: function (b, e) {
                        var grid = Ext.getCmp(that.gridId);
                        excelDataProgram('', '/pages/menu/config/Program/php/data.php', grid.colModel, 'grid_category', Ext.getCmp(this.tbarSearchTypeId).getValue(), Ext.getCmp(this.tbarSearchValueId).getValue());
                    }
                }, '->', {
                    xtype: 'displayfield',
                    value: '<b></b>'
                }
                ],
                colModel: new Ext.grid.ColumnModel({
                    defaults: {
                        sortable: false,
                        menuDisabled: true,
                    },
                    columns: [
                        columnRowIndex(50),
                        {
                            header: '제작유형', dataIndex: 'parent', width: 80, align: 'center', sortable: true, renderer: function (value, record) {
                                return value.folder_path_nm;
                            }
                        },
                        { header: '프로그램명', dataIndex: 'folder_path_nm', sortable: true, width: 300 },
                        { header: '폴더명', dataIndex: 'folder_path', sortable: true, width: 100 },
                        //{header: "그룹여부", dataIndex: 'is_group', width: 150, hidden: true},
                        //{header: "QUOTA(GB)", dataIndex: 'quota',sortable: false , width: 80 },
                        //{header: "사용량(GB)", dataIndex: 'usage',sortable: false , width: 80 },
                        //{header: "카테고리ID", dataIndex: 'id', sortable: false, hidden: true, width: 100 },
                        //{header: '사용자 정보', dataIndex: 'cnt', sortable: false, width: 70, hidden: true},
                        //{header: '제작 시작일', dataIndex: 'start_date', width: 80, renderer: Ext.util.Format.dateRenderer('Y-m-d'), align : 'center'},
                        //{header: '제작 종료일', dataIndex: 'end_date', width: 80, renderer: Ext.util.Format.dateRenderer('Y-m-d'), align : 'center'},
                        { header: '프로그램ID', dataIndex: 'pgm_id', sortable: true, width: 150 },
                        { header: '담당자', dataIndex:'owner_info', align:'left',
                            renderer: function(value, metaData, record, rowIndex, colIndex, store){
                                if(!Ext.isEmpty(value)){
                                    if(!Ext.isEmpty(value.user_nm) && !Ext.isEmpty(value.user_id)){
                                        return value.user_nm+'('+value.user_id+')';
                                    }
                                };
                            }
                        },
                        //{header: '사용자 정보', dataIndex: 'users_nm', sortable: false, width: 300},
                        //{header: '홈페이지 프로그램 전송위치', dataIndex: 'home_pgm_path', sortable: false, width: 150},
                        //{header: '홈페이지 프로그램', dataIndex: 'home_pgmnm', sortable: false, width: 150, hidden: true},
                        //{header: '홈페이지 프로그램', dataIndex: 'home_pgmid', sortable: false, width: 80, hidden: true},
                        //{header: '홈페이지 카테고리', dataIndex: 'home_board_cate', sortable: false, hidden: true},
                        {
                            header: '사용여부', dataIndex: 'using_yn', sortable: true, width: 70, align: 'center', renderer: function (value) {

                                if (value == 'Y') {
                                    return '예';
                                } else {
                                    return '아니요';
                                }

                            }
                        },
                        { header: '등록일시', dataIndex: 'created_at', width: 130, sortable: true, renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s'), align: 'center' },
                        { header: '수정일시', dataIndex: 'updated_at', width: 130, sortable: true, renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s'), align: 'center' }
                    ]
                }),
                selModel: new Ext.grid.RowSelectionModel({
                    singcleSelect: true
                }),
                
                store: this.store,
                bbar: {
                    xtype: 'paging',
                    pageSize: this.pageSize,
                    displayInfo: true,
                    store: this.store
                },
                contextMenu: new Ext.menu.Menu({
                    items: [
                        that.add_category_node(that, 'menuitem'),
                        that.edit_category_node(that, 'menuitem'),
                        that.delete_category_node(that, 'menuitem'),
                        '-',
                        that.mapping_user(that, 'menuitem')
                    ]
                }),
                listeners: {
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
                        e.stopEvent();
                        var sel = self.getSelectionModel().getSelected();

                        if (Ext.isEmpty(sel)) {
                            Ext.Msg.alert('알림', '목록을 선택하여 주세요');
                            return;
                        }
                        var win = that.createWindow('edit', sel.data, self.ownerCt);
                        //Ext.getCmp(that.windowId).setSize(400,250);
                        win.show();
                    }
                }
            });

            this.items = [
                this.grid
            ];

            Ariel.System.ProductAuthMng.superclass.initComponent.call(this);
        }
    });


    return new Ariel.System.ProductAuthMng();

})()