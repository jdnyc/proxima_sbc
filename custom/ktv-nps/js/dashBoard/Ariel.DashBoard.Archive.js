(function () {
    Ext.ns('Ariel.DashBoard');
    Ariel.DashBoard.Archive = Ext.extend(Ext.grid.EditorGridPanel, {




        title: '<span class="user_span"><span class="icon_title"><i class=""></i></span><span class="main_title_header">' + '아카이브/리스토어 요청관리' + '</span></span>',
        cls: 'grid_title_customize proxima_customize',
        initComponent: function (config) {
            var _this = this;
            var total_list = 0;
            var request_page_size = 50;
            var request_manage_grid = this;
            // var archiveStatus = {
            //          request :			1,
            //          approve	:		2,
            //          reject	:		3
            //     };


            function show_confirm_win(rs_req_no) {
                var confirm_win = new Ext.Window({
                    title: '확인',
                    width: 250,
                    modal: true,
                    border: true,
                    frame: true,
                    padding: '3px',
                    buttonAlign: 'center',
                    items: [{
                        xtype: 'displayfield',
                        value: '<center><p style="font-weight:bold;height:30px;line-height:30px;">선택하신 요청목록을 승인 하시겠습니까?</p></center>'
                    }, {
                        xtype: 'displayfield',
                        value: '<p style="height:20px;line-height:20px;">승인내용</p>'
                    }, {
                        xtype: 'textarea',
                        layout: 'fit',
                        width: 230,
                        id: 'confirm_auth_comment'
                    }],
                    buttons: [{
                        text: '예',
                        scale: 'medium',
                        icon: '/led-icons/accept.png',
                        is_click: false,
                        handler: function (b, e) {
                            confirm_win.el.mask();

                            Ext.Ajax.request({
                                url: '/pages/menu/archive_management/action_archive_request.php',
                                params: {
                                    action: 'accept',
                                    'req_no[]': rs_req_no,
                                    auth_comment: Ext.getCmp('confirm_auth_comment').getValue()
                                },
                                callback: function (opt, success, res) {
                                    confirm_win.el.unmask();
                                    if (success) {
                                        var msg = Ext.decode(res.responseText);
                                        if (msg.success) {
                                            Ext.Msg.alert(' 완 료', msg.msg);
                                        } else {
                                            Ext.Msg.alert(' 오 류 ', msg.msg);
                                        }
                                    } else {
                                        Ext.Msg.alert('서버 오류', res.statusText);
                                    }
                                    confirm_win.close();
                                    request_manage_grid.getStore().reload();
                                }
                            });
                        }
                    }, {
                        text: '아니오',
                        scale: 'medium',
                        icon: '/led-icons/cross.png',
                        handler: function (b, e) {
                            confirm_win.close();
                        }
                    }
                    ]
                }).show();
            }

            var selModel = new Ext.grid.CheckboxSelectionModel({
                singleSelect: false,
                checkOnly: false
            });

            //자동 새로고침에 사용하는 변수

            var request_manage_store = new Ext.data.JsonStore({
                url: '/pages/menu/archive_management/get_request_data.php',
                root: 'data',
                totalProperty: 'total_list',
                idProperty: 'req_no',
                baseParams: {
                    start: 0,
                    limit: 50
                },
                fields: [
                    'req_no', { name: 'req_time', type: 'date', dateFormat: 'YmdHis' },
                    'req_type', 'req_status',
                    'req_user_id', 'req_user_nm',
                    'das_content_id', 'nps_content_id',
                    'req_comment', 'appr_user_id',
                    'pgm_id', 'pgm_nm',
                    'trg_category_id', 'trg_category_title',
                    'appr_user_nm', { name: 'appr_time', type: 'date', dateFormat: 'YmdHis' },
                    'appr_comment', 'title',
                    'ud_content_title', 'ud_content_id',
                    'status', 'task_id',
                    'qualitycheck', 'required_input',
                    'ud_content_title'
                ],
                listeners: {
                    beforeload: function (self, opts) {
                        opts.params = opts.params || {};
                        // tbar1.find('name', 'request_type_combo')


                        Ext.apply(opts.params, {
                            req_start_date: _this.topToolbar.find('name', 'request_start_date')[0].getValue().format('Ymd000000'),
                            req_end_date: _this.topToolbar.find('name', 'request_end_date')[0].getValue().format('Ymd235959'),
                            req_type: _this.topToolbar.find('name', 'request_type_combo')[0].getValue(),
                            req_status: _this.topToolbar.find('name', 'request_status_combo')[0].getValue(),
                            search_field: _this.topToolbar.find('name', 'request_search_field')[0].getValue(),
                            search_value: _this.topToolbar.find('name', 'request_search_value')[0].getValue(),
                            req_user: true,
                        });
                    },
                    load: function (self, opts) {


                        total_list = self.getTotalCount();
                        var tooltext = "요청 : <font color=blue><b>" + total_list + "</b></font> 건";
                        _this.bottomToolbar.find('name', 'request_toolbartext')[0].setText(tooltext);
                    }
                }
            });
            var startDateField = new Ext.form.DateField({
                name: 'request_start_date',
                editable: false,
                width: 105,
                format: 'Y-m-d',
                altFormats: 'Ymd|ymd|Y-m-d|y-m-d|Y/m/d|Y/m/d',
                listeners: {
                    render: function (self) {
                        var d = new Date();

                        self.setMaxValue(d.format('Y-m-d'));
                        self.setValue(d.add(Date.DAY, -7).format('Y-m-d'));
                    }
                }
            });
            var endDateField = new Ext.form.DateField({
                xtype: 'datefield',
                name: 'request_end_date',
                editable: false,
                width: 105,
                format: 'Y-m-d',
                altFormats: 'Ymd|ymd|Y-m-d|y-m-d|Y/m/d|Y/m/d',
                listeners: {
                    render: function (self) {
                        var d = new Date();

                        self.setMaxValue(d.format('Y-m-d'));
                        self.setValue(d.format('Y-m-d'));
                    }
                }
            });
            var tbar1 = new Ext.Toolbar({
                dock: 'top',
                items: [' 요청구분', {
                    xtype: 'combo',
                    name: 'request_type_combo',
                    hiddenName: 'request_type_combo',
                    mode: 'local',
                    width: 90,
                    triggerAction: 'all',
                    editable: false,
                    displayField: 'd',
                    valueField: 'v',
                    value: 'all',
                    store: new Ext.data.ArrayStore({
                        fields: [
                            'd', 'v'
                        ],
                        data: [
                            ['전체', 'all'],
                            ['아카이브', 'archive'],
                            ['리스토어', 'restore'],
                            // ['Partial 리스토어', 'pfr_restore']
                            ['아카이브 삭제', 'delete']
                        ]
                    }),
                    listeners: {
                        select: {
                            fn: function (self, record, index) {
                                request_manage_grid.getStore().reload();
                            }
                        }
                    }
                }, '-', ' 요청상태', {
                        xtype: 'combo',
                        name: 'request_status_combo',
                        hiddenName: 'request_status_combo',
                        mode: 'local',
                        width: 90,
                        triggerAction: 'all',
                        editable: false,
                        displayField: 'd',
                        valueField: 'v',
                        value: 'all',
                        store: new Ext.data.ArrayStore({
                            fields: [
                                'd', 'v'
                            ],
                            data: [
                                ['전체', 'all'],
                                ['요청', '1'],
                                ['승인', '2'],
                                ['반려', '3']
                            ]
                        }),
                        listeners: {
                            select: {
                                fn: function (self, record, index) {
                                    request_manage_grid.getStore().reload();
                                }
                            }
                        }
                    }, '-', '요청일시',
                    startDateField,
                    '~',
                    endDateField, {
                        xtype: 'tbspacer',
                        width: 10
                    }, {
                        xtype: 'radioday',
                        // startDateField: startDateField,
                        // endDateField: endDateField,
                        dateFieldConfig: {
                            startDateField: startDateField,
                            endDateField: endDateField
                        },
                    }, '-', {
                        xtype: 'tbseparator',
                        hidden: true
                    }, {
                        xtype: 'combo',
                        hidden: true,
                        name: 'request_search_field',
                        mode: 'local',
                        width: 70,
                        triggerAction: 'all',
                        editable: false,
                        displayField: 'd',
                        valueField: 'v',
                        value: 'keyword',
                        store: new Ext.data.ArrayStore({
                            fields: [
                                'd', 'v'
                            ],
                            data: [
                                ['제목', 'keyword'],
                                ['요청자', 'req_user'],
                                ['승인자', 'appr_user']
                                // ,
                                // ['NPS ID', 'nps_content_id']
                            ]
                        }),
                        listeners: {
                            select: {
                                fn: function (self, record, index) {
                                    //request_manage_grid.getStore().reload();
                                }
                            }
                        }
                    }, {
                        xtype: 'textfield',
                        hidden: true,
                        name: 'request_search_value',
                        listeners: {
                            specialKey: function (self, e) {
                                if (e.getKey() == e.ENTER) {
                                    e.stopEvent();

                                    request_manage_grid.getStore().reload({ params: { start: 0 } });
                                }
                            }
                        }
                    }, {
                        xtype: 'tbseparator',
                        hidden: true
                    }, {
                        xtype: 'tbspacer',
                        width: 10
                    }, {
                        xtype: 'a-iconbutton',
                        // icon: '/led-icons/find.png',
                        //>>text: '조회',
                        text: '조회',
                        handler: function (btn, e) {
                            //var params = Ext.getCmp('archive_request_search_field').getValue();
                            request_manage_grid.getStore().reload({ params: { start: 0 } });
                        }
                    }, '->', {
                        xtype: 'a-iconbutton',
                        text: ' 재승인 요청',
                        handler: function (self) {
                            var sm = _this.getSelectionModel();
                            if (sm.hasSelection()) {
                                var record = sm.getSelected();
                                var status = record.get('req_status');
                                var reqNo = record.get('req_no');
                                /**
                                 * 요청 : 1, 반려: 3, 승인 : 2
                                 */
                                if (status !== '3') {
                                    return Ext.Msg.alert('알림', '반려상태일때 재승인을 요청해 주세요.');
                                };

                                Ext.Ajax.request({
                                    method: 'PUT',
                                    url: '/api/v1/archive/update-status/' + reqNo,
                                    params: {
                                        update_status: '1',
                                    },
                                    callback: function (opts, success, res) {
                                        _this.getStore().reload();
                                        Ext.Msg.alert('알림', '재승인 요청되었습니다.');
                                    }
                                });



                            } else {
                                Ext.Msg.alert('알림', '재승인 요청할 목록을 선택해주세요.');
                            }
                        }
                    }, {
                        xtype: 'a-iconbutton',
                        text: '요청 취소',
                        handler: function (self) {
                            var sm = _this.getSelectionModel();
                            if (sm.hasSelection()) {
                                var record = sm.getSelected();
                                var status = record.get('req_status');
                                var reqNo = record.get('req_no');
                                /**
                                 * 요청 : 1, 반려: 3, 승인 : 2
                                 */
                                if (status !== '1') {
                                    return Ext.Msg.alert('알림', '요청 상태일때만 취소 할 수 있습니다.');
                                };
                                Ext.Msg.show({
                                    title: '알림',
                                    msg: '취소 하시겠습니까?',
                                    buttons: Ext.Msg.OKCANCEL,
                                    fn: function (btnId, text, opts) {
                                        if (btnId == 'ok') {
                                            Ext.Ajax.request({
                                                method: 'DELETE',
                                                url: '/api/v1/archive/delete-request/' + reqNo,
                                                callback: function (opts, success, res) {
                                                    _this.getStore().reload();
                                                    Ext.Msg.alert('알림', '요청 취소되었습니다.');
                                                }
                                            });
                                        }
                                    }
                                });
                            } else {
                                Ext.Msg.alert('알림', '요청 취소할 목록을 선택해주세요.');
                            }
                        }
                    }, {
                        hidden: true,
                        icon: '/led-icons/page_white_excel.png',
                        text: 'Excel',
                        handler: function (btn, e) {
                            window.location = '/pages/menu/archive_management/get_request_data.php?is_excel=Y' +
                                '&req_start_date=' + _this.topToolbar.find('name', 'request_start_date')[0].getValue().format('Ymd000000') +
                                '&req_end_date=' + _this.topToolbar.find('name', 'request_end_date')[0].getValue().format('Ymd000000') +
                                '&req_type=' + _this.topToolbar.find('name', 'request_type_combo')[0].getValue() +
                                '&req_status=' + _this.topToolbar.find('name', 'request_status_combo')[0].getValue() +
                                '&search_field=' + _this.topToolbar.find('name', 'request_search_field')[0].getValue() +
                                '&search_value=' + _this.topToolbar.find('name', 'request_search_value')[0].getValue();

                            Ext.Msg.alert('알림', '엑셀파일 다운로드가 곧 시작됩니다.');
                        }
                    }]
            });




            Ext.apply(this, {
                border: false,
                loadMask: true,
                frame: true,
                width: 800,
                tbar: tbar1,
                clicksToEdit: 1,
                loadMask: true,
                columnWidth: 1,
                store: request_manage_store,
                disableSelection: true,
                listeners: {
                    viewready: function (self) {
                        self.store.load({
                            params: {
                                start: 0,
                                limit: request_page_size,
                                req_start_date: _this.topToolbar.find('name', 'request_start_date')[0].getValue().format('Ymd000000'),
                                req_end_date: _this.topToolbar.find('name', 'request_end_date')[0].getValue().format('Ymd000000')
                            }
                        });
                        //self.add(tbar2);
                    },
                    rowcontextmenu: function (self, rowIdx, e) {
                        e.stopEvent();

                        var ownerCt = self;

                        var sm = self.getSelectionModel();
                        if (!sm.isSelected(rowIdx)) {
                            sm.selectRow(rowIdx);
                        }

                        var sel_data = sm.getSelected();
                        var req_no = sel_data.get('req_no');
                        var src_device_id = sel_data.get('src_device_id');
                        var arc_type = sel_data.get('arc_type');
                        var status = sel_data.get('status');
                        var ud_system = sel_data.get('ud_system');
                        var mgmt_id = sel_data.get('mgmt_id');
                        var mtrl_id = sel_data.get('mtrl_id');
                        var content_id = sel_data.get('content_id');
                        var task_id = sel_data.get('task_id');

                        var target_ud_system = sel_data.get('target_ud_system');

                        var menu = new Ext.menu.Menu({
                            items: [{
                                icon: '/led-icons/application_form.png',
                                text: '작업흐름보기',
                                handler: function (btn, e) {
                                    if (Ext.isEmpty(task_id)) {
                                        Ext.Msg.alert('알림', '승인되지 않은 요청입니다');
                                        return;
                                    }

                                    Ext.Ajax.request({
                                        url: '/javascript/ext.ux/viewWorkFlowRequest.php',
                                        params: {
                                            task_id: task_id
                                        },
                                        callback: function (options, success, response) {
                                            if (success) {
                                                try {
                                                    Ext.decode(response.responseText);
                                                } catch (e) {
                                                    Ext.Msg.alert(e['name'], e['message']);
                                                }
                                            } else {
                                                //>>Ext.Msg.alert('서버 오류', response.statusText);
                                                Ext.Msg.alert(_text('MN00022'), response.statusText);
                                            }
                                        }
                                    });
                                }
                            }]
                        });
                        menu.showAt(e.getXY());
                    },

                    rowdblclick: function (self, rowIndex, e) {
                        var sm = self.getSelectionModel().getSelected();

                        var content_id = sm.get('das_content_id');
                        var req_comment = sm.get('req_comment');
                        var mtrl_id = sm.get('mtrl_id');
                        var mgmt_id = sm.get('mgmt_id');
                        var is_block = "";
                        var hasQualityTab = sm.get('qualitycheck');

                        if (!mgmt_id) {
                            is_block = "ok";
                        }

                        var that = self;

                        self.load = new Ext.LoadMask(Ext.getBody(), { msg: _text('MSG00143') });
                        self.load.show();
                        var components = [
                            '/custom/ktv-nps/javascript/ext.ux/Custom.ParentContentGrid.js',
                        ];
                        Ext.Loader.load(components, function (r) {
                            that.load.hide();
                            new Custom.ContentDetailWindow({
                                content_id: content_id,
                                isPlayer: true,
                                isMetaForm: true,
                                playerMode: 'read',
                                listeners: {
                                    afterrender: function (self) {
                                        self.buttons[0].hide();
                                    }
                                }
                            }).show();
                        });
                        // Ext.Ajax.request({
                        //     url: '/javascript/ext.ux/Ariel.DetailWindow.php',
                        //     params: {
                        //         content_id: content_id,
                        //         record: Ext.encode(sm.json),
                        //         page_from: 'ArchiveRequest',
                        //         hasQualityTab: hasQualityTab
                        //     },
                        //     callback: function (self, success, response) {

                        //         if (success) {
                        //             that.load.hide();
                        //             try {

                        //                 var r = Ext.decode(response.responseText);

                        //                 if (r !== undefined && !r.success) {
                        //                     Ext.Msg.show({
                        //                         title: '경고'
                        //                         , msg: r.msg
                        //                         , icon: Ext.Msg.WARNING
                        //                         , buttons: Ext.Msg.OK
                        //                     });
                        //                 }
                        //                 if (!Ext.isEmpty(req_comment)) {
                        //                     //Ext.Msg.alert('요청사유', req_comment);
                        //                 }
                        //             } catch (e) {
                        //                 //alert(response.responseText)
                        //                 //Ext.Msg.alert(e['name'], e['message'] );
                        //             }
                        //         } else {
                        //             //>>Ext.Msg.alert('서버 오류', response.statusText+'('+response.status+')');
                        //             Ext.Msg.alert(_text('MN00022'), response.statusText + '(' + response.status + ')');
                        //         }
                        //     }
                        // });
                    }
                },

                sm: selModel,
                cm: new Ext.grid.ColumnModel({
                    defaults: {
                        sortable: false
                    },

                    columns: [
                        new Ext.grid.RowNumberer(),
                        selModel,
                        { header: '요청No', dataIndex: 'req_no', hidden: true },
                        { header: '작업ID', dataIndex: 'task_id', hidden: true },
                        { header: '요청구분', dataIndex: 'req_type', align: 'center', width: 90, renderer: mapReqType },
                        { header: 'NPS ID', dataIndex: 'nps_content_id', align: 'center', width: 70, hidden: true },
                        { header: '요청상태', dataIndex: 'req_status', align: 'center', width: 70, renderer: mapStatus, menuDisabled: true },
                        { header: '작업상태', dataIndex: 'status', align: 'center', width: 70, renderer: mapTaskStatus, menuDisabled: true },
                        { header: '유형', dataIndex: 'ud_content_title', align: 'center', width: 70 },
                        { header: '필수데이터', dataIndex: 'required_input', align: 'center', width: 70, renderer: mapRequired, menuDisabled: true, hidden: true },
                        { header: 'QC', dataIndex: 'qualitycheck', align: 'center', width: 60, renderer: mapQualityCheck, menuDisabled: true, hidden: true },
                        { header: '<center>제목</center>', dataIndex: 'title', align: 'left', menuDisabled: true, width: 250 },
                        { header: '요청자', dataIndex: 'req_user_id', align: 'center', menuDisabled: true, hidden: true, },
                        { header: '요청자', dataIndex: 'req_user_nm', align: 'center', menuDisabled: true, width: 110 },
                        { header: '요청일시', dataIndex: 'req_time', align: 'center', sortable: true, menuDisabled: true, width: 140, renderer: showDate },
                        { header: '<center>요청사유</center>', dataIndex: 'req_comment', align: 'left', menuDisabled: true, width: 250 },
                        { header: '승인자', dataIndex: 'appr_user_id', align: 'center', menuDisabled: true, width: 110, hidden: true },
                        { header: '승인자', dataIndex: 'appr_user_nm', align: 'center', menuDisabled: true, width: 110, renderer: showEmpty },
                        { header: '승인일시', dataIndex: 'appr_time', align: 'center', menuDisabled: true, width: 140, renderer: showDate },
                        { header: '<center>승인내용</center>', dataIndex: 'appr_comment', align: 'left', menuDisabled: true, width: 250 }
                    ]
                }),

                view: new Ext.ux.grid.BufferView({
                    rowHeight: 20,
                    scrollDelay: false,
                    emptyText: '결과 값이 없습니다.'
                }),

                bbar: new Ext.PagingToolbar({
                    store: request_manage_store,
                    pageSize: request_page_size,
                    items: ['->', {
                        name: 'request_toolbartext',
                        xtype: 'tbtext',
                        pageX: '100',
                        pageY: '100',
                        text: "요청 : " + total_list + " 건"
                    }]
                })
            }, config || {});

            function showEmpty(value) {
                if (Ext.isEmpty(value)) value = '-';
                return value;
            }

            function showDate(value) {
                if (Ext.isEmpty(value)) value = '-';
                else value = Ext.util.Format.date(value, 'Y-m-d H:i:s');
                return value;
            }

            function mapStatus(value) {
                switch (value) {
                    case 1:
                        value = '요청';
                        break;
                    case 3:
                        value = '<font color=crimson><b>반려</b></font>';
                        break;
                    case 2:
                        value = '<font color=royalblue><b>승인</b></font>';
                        break;
                    case '1':
                        value = '요청';
                        break;
                    case '3':
                        value = '<font color=crimson><b>반려</b></font>';
                        break;
                    case '2':
                        value = '<font color=royalblue><b>승인</b></font>';
                        break;
                    case '':
                        value = '-';
                        break;
                }
                return value;
            }

            function mapTaskStatus(value) {
                switch (value) {
                    case 'queue':
                        value = '대기';
                        break;
                    case 'error':
                        value = '<font color=crimson><b>실패</b></font>';
                        break;
                    case 'complete':
                        value = '<font color=royalblue><b>완료</b></font>';
                        break;
                    case 'processing':
                        value = '<font color=limegreen><b>진행중</b></font>';
                        break;
                        case 'scheduled':
                          value = '<font color=limegreen><b>예약됨</b></font>';
                          break;
                    default:
                        value = '-';
                        break;
                }
                return value;
            }

            function mapRequired(value) {
                switch (value) {
                    case 'N':
                        value = '<font color=royalblue><b>완료</b></font>';
                        break;
                    case 'Y':
                        value = '<font color=crimson><b>미입력</b></font>';
                        break;
                    default:
                        value = '-';
                }

                return value;
            }

            function mapQualityCheck(value) {
                switch (value) {
                    case 'complete':
                        value = '<font color=royalblue><b>완료</b></font>';
                        break;
                    case 'error':
                        value = '<font color=crimson><b>오류</b></font>';
                        break;
                    default:
                        value = '-';
                }

                return value;
            }

            function mapReqType(value) {
                value = value.toLowerCase();
                switch (value) {
                    case 'archive':
                        value = '<font color=blue>아카이브</font>';
                        break;
                    case 'restore':
                        value = '<font color=green>리스토어</font>';
                        break;
                    case 'pfr_restore':
                        value = '<font color=olivedrab>Partial 리스토어</font>';
                        break;
                    case 'delete':
                        value = '<font color=red>아카이브 삭제</font>';
                        break;
                    default:
                        value = '-';
                }
                return value;
            }
            Ariel.DashBoard.Archive.superclass.initComponent.call(this);
        }
    });
    // return new Ariel.DashBoard.Archive();
})()