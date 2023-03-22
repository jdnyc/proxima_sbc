(function () {
    Ext.ns('Ariel.DashBoard');
    Ariel.DashBoard.AuthorityMandate = Ext.extend(Ext.grid.GridPanel, {
        title: '<span class="user_span"><span class="icon_title"><i class=""></i></span><span class="main_title_header">' + '리스토어 권한 승계' + '</span></span>',
        cls: 'grid_title_customize proxima_customize',
        loadMask: true,
        stripeRows: true,
        frame: false,
        viewConfig: {
            emptyText: '목록이 없습니다.',
            border: false
        },
        height: 500,
        initComponent: function () {

            this._initialize();

            Ariel.DashBoard.AuthorityMandate.superclass.initComponent.call(this);
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
            var permissionsCheck = this._getPermission(permissions);
            var topToolbar = _this.getTopToolbar();

            if (permissionsCheck) {
                topToolbar.addButton({
                    xtype: 'a-iconbutton',
                    text: '권한위임',
                    handler: function (self) {
                        var url = Ariel.DashBoard.Url.authorityMandate;
                        var msg = '권한 승계가 완료되었습니다.';
                        _this._inputFormWindow('추가', 'POST', url, msg, null);
                    }
                });
            };

            if (permissionsCheck) {
                topToolbar.addButton({
                    xtype: 'a-iconbutton',
                    text: '만료일자 변경',
                    handler: function (self) {
                        var sm = _this.getSelectionModel();

                        if (sm.hasSelection()) {
                            var getRecord = sm.getSelected();
                            var url = Ariel.DashBoard.Url.authorityMandateUpdate(getRecord.get('id'))
                            var msg = '수정되었습니다.';

                            _this._inputFormWindow('수정', 'POST', url, msg, getRecord);
                        } else {
                            Ext.Msg.alert('알림', '수정할 목록을 선택해주세요.');
                        }
                    }
                });
            };

            if (permissionsCheck) {
                topToolbar.addButton({
                    xtype: 'a-iconbutton',
                    text: '권한위임 취소',
                    handler: function (self) {
                        var sm = _this.getSelectionModel();

                        if (sm.hasSelection()) {
                            var getRecord = sm.getSelected();
                            var getRecordId = getRecord.get('id');
                            Ext.Msg.show({
                                title: '알림',
                                msg: '취소 하시겠습니까?',
                                buttons: Ext.Msg.OKCANCEL,
                                fn: function (btnId) {
                                    if (btnId == 'ok') {
                                        Ext.Ajax.request({
                                            method: 'DELETE',
                                            // url: Ariel.DashBoard.Url.authorityMandate(getRecordId)
                                            url: '/api/v1/authority-mandate/' + getRecordId,
                                            callback: function (opts, success, resp) {
                                                if (success) {
                                                    try {
                                                        _this.store.reload();
                                                    } catch (e) {
                                                        Ext.Msg.alert(e['name'], e['message']);
                                                    }
                                                } else {
                                                    var res = Ext.decode(resp.responseText);
                                                    Ext.Msg.alert('알림', res.msg);
                                                }
                                            }
                                        })
                                    }
                                }
                            })
                        } else {
                            Ext.Msg.alert('알림', '권한위임 취소할 목록을 선택해주세요.');
                        }
                    }
                });
            };

            topToolbar.doLayout();
        },
        _initialize: function () {
            /**
             * AUTHORITY_MANDATE,AuthorityMandate
             * 1.컬럼에는 MANDATARY(수임자) , 받은 권한, 시작 일자,만료 일자,삭제 여부가 있다.
             * 2. tbar 버튼에는 추가, 수정, 삭제 버튼이 있고
             * 3. 추가 시에 추가 윈도우 창이 show 되고 그 안에 유저 검색, 부여할 권한, 시작 일자, 만료일자, 삭제 여부가 있다.
             */
            var _this = this;

            // 권한 승계 그리드 패널의 스토어
            this.store = new Ext.data.JsonStore({
                remoteSort: true,
                restful: true,
                proxy: new Ext.data.HttpProxy({
                    method: 'GET',
                    // url: Ariel.DashBoard.Url.authorityMandate,
                    url: Ariel.DashBoard.Url.authorityMandate,
                    type: 'rest'
                }),
                remoteSort: true,
                totalProperty: 'total',
                root: 'data',
                fields: [
                    'id',
                    'mandatary',
                    'mandator',
                    { name: 'regist_dt', type: 'date' },
                    { name: 'end_dt', type: 'date' },
                    'updt_dt',
                    // 'end_dt',
                    'delete_dt',
                    'authority',
                    'mandatary_info',
                    'mandator_info'
                ]
            });
            this.bbar = {
                xtype: 'paging',
                pageSize: 30,
                displayInfo: true,
                store: this.store
            };
            // 권한 승계 그리드 컬럼 모델
            this.cm = new Ext.grid.ColumnModel({
                defaults: {
                    align: 'center',
                    menuDisabled: true,
                    sortable: false
                },
                columns: [
                    {
                        header: '수임자', dataIndex: 'mandatary_info', renderer: function (value, metaData, record, rowIndex, colIndex, store) {
                            if (!(value == null)) {
                                return value.user_id + '(' + value.user_nm + ')';
                            };
                        },
                        width: 170
                    },
                    { header: '수임자', dataIndex: 'mandatary', width: 150, hidden: true },
                    {
                        header: '위임자', dataIndex: 'mandator_info', width: 150, renderer: function (value, metaData, record, rowIndex, colIndex, store) {
                            if (!(value == null)) {
                                return value.user_id + '(' + value.user_nm + ')';
                            };
                        },
                        width: 170
                    },
                    { header: '위임자', dataIndex: 'mandator', width: 150, hidden: true },
                    { header: '권한', dataIndex: 'authority', hidden: true },
                    { header: '시작 일자', dataIndex: 'regist_dt', renderer: Ext.util.Format.dateRenderer('Y-m-d'), width: 150 },
                    { header: '수정 일자', dataIndex: 'updt_dt', hidden: true },
                    {
                        header: '만료 일자', dataIndex: 'end_dt', width: 150,
                        renderer: function (value, metaData, record) {

                            function dateDiff(_date1, _date2) {
                                var diffDate_1 = _date1 instanceof Date ? _date1 : new Date(_date1);
                                var diffDate_2 = _date2 instanceof Date ? _date2 : new Date(_date2);

                                diffDate_1 = new Date(diffDate_1.getFullYear(), diffDate_1.getMonth() + 1, diffDate_1.getDate());
                                diffDate_2 = new Date(diffDate_2.getFullYear(), diffDate_2.getMonth() + 1, diffDate_2.getDate());

                                var diff = Math.abs(diffDate_2.getTime() - diffDate_1.getTime());

                                diff = Math.ceil(diff / (1000 * 3600 * 24));

                                return diff;
                            };
                            function diffBool(_date1) {
                                var endDate = _date1;
                                var nowDate = new Date();
                                if (nowDate >= endDate) {
                                    return true;
                                } else {
                                    return false;
                                }
                            }
                            if (!Ext.isEmpty(value)) {
                                var endDt = value.format('Y-m-d');
                                var dateDiff = dateDiff(endDt, new Date());
                                var diffBool = diffBool(value);

                                if (!diffBool) {
                                    if (dateDiff <= 30) {
                                        return '<span style="color:red;">' + endDt + ' (D-' + dateDiff + ')</span>';
                                    } else {
                                        return endDt;
                                    }
                                } else {
                                    return endDt;
                                }
                            }
                        }
                    }
                ]
            });


            this.startDateField = new Ext.form.DateField({
                name: 'start_date',
                editable: false,
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
                        _this._searchStoreLoad();
                    }
                }
            });
            this.endDateField = new Ext.form.DateField({
                xtype: 'datefield',
                name: 'end_date',
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
                            return Ext.Msg.alert('알림', '시작날짜보다 이전날짜를 선택할 수 없습니다.');
                        };
                        _this._searchStoreLoad();
                    }
                }
            });

            // 권한 승계 그리드 탑 툴바 추가 수정 삭제 버튼
            this.tbar = [{
                //>>text: '새로고침',
                cls: 'proxima_button_customize',
                width: 30,
                text: '<span style="position:relative;" title="' + _text('MN00139') + '"><i class="fa fa-refresh" style="font-size:13px;color:white;"></i></span>',
                handler: function (self) {
                    _this.getStore().reload();
                },
                scope: this
            },
            {
                xtype: 'spacer',
                width: 10
            },
            {
                text: '요청일시',
            },
                ' ',
            _this.startDateField,
            {
                html: '~'
            },
            _this.endDateField,
            {
                xtype: 'radioday',
                dateFieldConfig: {
                    startDateField: _this.startDateField,
                    endDateField: _this.endDateField
                },
                addRadio: {
                    allRadio: true
                },
                width: 230,
                checkDay: 'all',
                columns: [.24, .24, .27, .25],
                listeners: {
                    change: function (self, checked) {
                        if (checked.value == 'all') {
                            _this.getStore().load();
                        } else {
                            _this._searchStoreLoad();
                        };
                    },
                    // beforerender: function (self) {
                    //     self._addAllRadio();
                    // }

                }
            },
                '->'
            ];
            // 권한 승계 그리드 패널의 리스너
            this.listeners = {
                afterrender: function (self) {
                    // self.store.load();
                },
                rowdblclick: function (self, rowIndex, e) {
                    var sm = _this.getSelectionModel();

                    var getRecord = sm.getSelected();
                    var url = Ariel.DashBoard.Url.authorityMandateUpdate(getRecord.get('id'))
                    var msg = '수정되었습니다.';

                    _this._inputFormWindow('수정', 'POST', url, msg, getRecord);

                }
            }
        },
        /**
         * 
         * @param {string} title 윈도우창 타이틀 (추가,수정)
         * @param {string} method submit method ('POST','DELETE')
         * @param {string} url submit url
         * @param {string} msg submit 후 알림 창 메세지
         * @param {object} editRecord 수정할때 선택된 목록의 그리드 레코드
         */
        _inputFormWindow: function (title, method, url, msg, editRecord) {
            var _this = this;
            var win = new Ext.Window({
                title: title,
                width: 400,
                autoHeight: true,
                modal: true,
                items: new Ext.form.FormPanel({
                    padding: 5,
                    defaults: {
                        anchor: '95%'
                    },
                    border: false,
                    items: [{
                        xtype: 'compositefield',
                        fieldLabel: '수임자',
                        name: 'userSearch',
                        items: [{
                            xtype: 'textfield',
                            name: 'mandatary',
                            allowBlank: false,
                            readOnly: true,
                            flex: 2
                        }, {
                            xtype: 'button',
                            text: '사용자 검색',
                            flex: 1,
                            handler: function (btn) {
                                var form = win.get(0).getForm();
                                _this._userSelectWindow(form);
                            }
                        }]
                    }, {
                        xtype: 'displayfield',
                        name: 'userInfo',
                        listeners: {
                            afterrender: function (self) {
                                self.hide();
                            }
                        }
                    }, {
                        xtype: 'datefield',
                        allowBlank: false,
                        editable: false,
                        fieldLabel: '만료 일자',
                        // readOnly: true,
                        altFormats: "Y-m-d|Ymd|YmdHis",
                        format: "Y-m-d",
                        name: 'end_dt',
                        listeners: {
                            select: function (self, date) {
                                var newDate = new Date();
                                if (newDate > date) {
                                    /**
                                     * 이전 날짜보다 작은 값을 선택 했을 시
                                     */

                                    // 이전날짜 선택시 null 값 입력
                                    self.setValue(null);
                                    Ext.Msg.alert('알림', '이전 날짜를 선택할 수 없습니다.');
                                }
                            }
                        }
                    }],
                    listeners: {
                        afterrender: function (self) {
                            // 수정버튼을 클릭했을때..
                            if (!(editRecord == null)) {
                                self.getForm().findField('userSearch').hide();
                                self.getForm().setValues(editRecord.data);
                            }
                        }
                    }
                }),
                buttons: [
                    {
                        text: '확인',
                        scale: "medium",
                        handler: function (self) {
                            var getForm = win.get(0).getForm();
                            switch (title) {
                                case '추가':
                                    var record = null;
                                    break;
                                case '수정':
                                    var record = Ext.encode(editRecord.data);
                                    break;
                            }
                            getForm.submit({
                                // method: 'POST',
                                method: method,
                                url: url,
                                params: {
                                    record: record
                                },
                                success: function (form, action) {
                                    Ext.Msg.show({
                                        title: '알림',
                                        msg: msg,
                                        buttons: Ext.Msg.OK,
                                        fn: function (btnId) {
                                            if (btnId == 'ok') {
                                                _this.store.reload();
                                                win.close();
                                            }
                                        }
                                    });
                                },
                                failure: function (form, action) {
                                    var res = Ext.decode(action.response.responseText);
                                    if (!res.success) {
                                        Ext.Msg.alert('알림', res.msg);
                                        win.close();
                                    }
                                }

                            })
                        }
                    }, {
                        text: '취소',
                        scale: "medium",
                        handler: function (self) {
                            win.close();
                        }
                    }
                ]

            });
            return win.show();
        },
        /**
         * 유저 검색창
         * @param {Object} form _inputFormWindow()->form
         */
        _userSelectWindow: function (form) {
            var _this = this;
            var components = [
                '/custom/ktv-nps/javascript/ext.ux/Custom.UserSelectWindow.js',
                '/custom/ktv-nps/javascript/ext.ux/components/Custom.UserListGrid.js',
                '/custom/ktv-nps/javascript/api/Custom.Store.js',
                '/javascript/common.js'
            ];
            Ext.Loader.load(components, function (r) {
                var win = new Custom.UserSelectWindow({
                    singleSelect: true,
                    listeners: {
                        ok: function () {
                            // 선택된 유저 레코드
                            var selected = this._selected

                            // 유저 아이디
                            var selectedUser = selected.get('user_id');


                            // 폼 필드
                            var mandatorField = form.findField('mandatary');
                            var userInfoField = form.findField('userInfo');

                            // display text
                            var displayText = _this._searchDisplayText(selected);

                            // 필드에 선택된 값 입력
                            mandatorField.setValue(selectedUser);
                            userInfoField.setValue(displayText);

                            userInfoField.show();
                            win.close();
                        }
                    }
                }).show();
            });
        },
        /**
         * 검색후에 디스플레이 필드로 추가 정보를 보여주기 위한 텍스트
         * @param Record selected
         * @return string
         */
        _searchDisplayText: function (selected) {
            // 유저 아이디
            var selectedUser = selected.get('user_id');
            // 유저 이름
            var selectedUserNm = selected.get('user_nm');
            // 부서명
            var selectedUserDept = selected.get('dept_nm');


            if (selectedUserDept == null || selectedUserDept == "" || typeof selectedUserDept == "undefined") {
                // 부서명이 없을 때
                var displayText = selectedUser + '(' + selectedUserNm + ')';
            } else {
                // 부서명이 있을 때
                var displayText = selectedUser + '(' + selectedUserNm + ')' + ' / ' + selectedUserDept;
            }
            return displayText;

        },
        _searchStoreLoad: function () {
            var startDate = this.startDateField.getValue();
            var endDate = this.endDateField.getValue();
            this.getStore().load({
                params: {
                    start_date: startDate,
                    end_date: endDate
                }
            });
        }
    });
    return new Ariel.DashBoard.AuthorityMandate();
})()