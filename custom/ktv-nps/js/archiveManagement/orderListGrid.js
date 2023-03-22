(function () {
    Ext.ns("Ariel.archiveManagement");

    Ariel.archiveManagement.orderListGrid = Ext.extend(Ext.grid.GridPanel, {
        // property
        start_date: new Date().add(Date.DAY, -30),
        end_date: new Date(),
        pageSize: 30,

        title: '<span class="user_span"><span class="icon_title"><i class=""></i></span><span class="main_title_header">' + "주문관리" + "</span></span>",
        loadMask: true,
        stripeRows: true,
        frame: false,
        autoWidth: true,
        // layout: 'fit',
        viewConfig: {
            emptyText: _text('MSG00148'),//'결과 값이 없습니다.',
            // forceFit: true,
            border: false
        },
        cls: "grid_title_customize proxima_customize",
        listeners: {
            afterrender: function (self) {
                var start_date = self
                    .getTopToolbar()
                    .find("name", "start_date")[0]
                    .getValue();
                var end_date = self
                    .getTopToolbar()
                    .find("name", "end_date")[0]
                    .getValue();
                self.getStore().load({
                    params: {
                        start_date: start_date.format("Ymd"),
                        end_date: end_date.format("Ymd"),
                        limit: self.pageSize
                    }
                });
            },
            rowdblclick: function (self, rowIndex, e) {
                //  행을 클릭했을떄 수정버튼을 누른것과 같은 동일한 이벤트
                var _this = this;
                var sm = _this.getSelectionModel();
                var selectRecord = sm.getSelected();
                _this._updateFormWindowShow(_this, selectRecord);
            }
        },
        initComponent: function () {
            this._initialize();

            Ariel.archiveManagement.orderListGrid.superclass.initComponent.call(this);
        },

        _initialize: function () {
            var _this = this;
            this.purposeCodeStore = new Ext.data.JsonStore({
                restful: true,
                proxy: new Ext.data.HttpProxy({
                    restful: true,
                    api: {
                        read: {
                            url: Ariel.glossary.UrlSet.codeSetIdParamCodes([
                                "*OR002",
                                "*OR003"
                            ]),
                            method: "POST"
                        }
                    },
                    url: Ariel.glossary.UrlSet.codeSetIdParamCodes(["*OR002", "*OR003"]),
                    type: "rest"
                }),
                root: "data",
                fields: [
                    { name: "items" },
                    { name: "type" },
                    { name: "id", mapping: "id" }
                ]
            });

            this.store = new Ext.data.JsonStore({
                remoteSort: true,
                restful: true,
                idProperty: 'order_num',
                proxy: new Ext.data.HttpProxy({
                    method: "GET",
                    url: Ariel.archiveManagement.UrlSet.order,
                    type: "rest"
                }),
                remoteSort: true,
                totalProperty: "total",
                root: "data",
                fields: [
                    "bank_deposit",
                    // 'bank_nm',
                    // 'bank_num',
                    "cancel_date",
                    "card_num",
                    "copy_date",
                    "delivery",
                    "delivery_amt",
                    "delivery_date",
                    "cancel_date",
                    "memo",
                    "memo1",
                    { name: "order_date", type: "date" },
                    "order_num",
                    "purpose",
                    "receipt_amt",
                    // { name: 'receipt_date', type: 'date' },
                    "receipt_date",
                    "repay_date",
                    "status",
                    "usepo",
                    "order_customer",
                    "orderBD",
                    "content",
                    "orderItems"
                ]
            });
            this.cm = new Ext.grid.ColumnModel({
                defaults: {
                    align: "center",
                    menuDisabled: true,
                    sortable: false
                },
                columns: [
                    {
                        header: "진행상태",
                        dataIndex: "orderBD",
                        sortable: true,
                        renderer: function (
                            value,
                            metaData,
                            record,
                            rowIndex,
                            colIndex,
                            store
                        ) {
                            if (value == null) {
                                return null;
                            } else {
                                return value.code_itm_nm;
                            }
                        }
                    },
                    {
                        header: "주문번호",
                        dataIndex: "order_num",
                        sortable: true,
                        width: 130
                    },
                    {
                        header: "이름",
                        dataIndex: "order_customer",
                        sortable: true,
                        renderer: function (
                            value,
                            metaData,
                            record,
                            rowIndex,
                            colIndex,
                            store
                        ) {
                            if (value == null) {
                                return null;
                            } else {
                                return value.cust_nm;
                            }
                        },
                        align: "left"
                    },
                    {
                        header: "전화번호",
                        dataIndex: "order_customer",
                        sortable: true,
                        renderer: function (
                            value,
                            metaData,
                            record,
                            rowIndex,
                            colIndex,
                            store
                        ) {
                            if (value == null) {
                                return null;
                            } else {
                                return value.phone;
                            }
                        }
                    },
                    {
                        header: "신청일자",
                        dataIndex: "order_date",
                        sortable: true,
                        renderer: Ext.util.Format.dateRenderer("Y-m-d")
                    },
                    {
                        header: "총액(원)",
                        dataIndex: "receipt_amt",
                        sortable: true,
                        align: "right",
                        renderer: function (
                            value,
                            metaData,
                            record,
                            rowIndex,
                            colIndex,
                            store
                        ) {
                            if (value == null) {
                                return null;
                            } else {
                                return _this._numberWithCommas(value) + "원";
                            }
                        }
                    },
                    {
                        header: "은행명",
                        dataIndex: "order_customer",
                        sortable: true,
                        renderer: function (
                            value,
                            metaData,
                            record,
                            rowIndex,
                            colIndex,
                            store
                        ) {
                            if (value == null) {
                                return null;
                            } else {
                                return value.bank_nm;
                            }
                        }
                    },
                    {
                        header: "은행계좌",
                        dataIndex: "order_customer",
                        sortable: true,
                        align: "left",
                        renderer: function (
                            value,
                            metaData,
                            record,
                            rowIndex,
                            colIndex,
                            store
                        ) {
                            if (value == null) {
                                return null;
                            } else {
                                return value.bank_num;
                            }
                        }
                    },
                    {
                        header: "은행번호",
                        dataIndex: "bank_num",
                        sortable: true,
                        align: "left",
                        hidden: true
                    },

                    {
                        header: "입금자",
                        dataIndex: "order_customer",
                        sortable: true,
                        align: "left",
                        renderer: function (
                            value,
                            metaData,
                            record,
                            rowIndex,
                            colIndex,
                            store
                        ) {
                            if (value == null) {
                                return null;
                            } else {
                                return value.bank_deposit;
                            }
                        }
                    },
                    {
                        header: "입금일",
                        dataIndex: "receipt_date",
                        sortable: true,
                        renderer: function (
                            value,
                            metaData,
                            record,
                            rowIndex,
                            colIndex,
                            store
                        ) {
                            // if (!(value == null)) {
                            //     return _this._columnValueDateFormat(value);
                            // }
                            if (!(value == null)) {
                                return value;
                            }
                        }
                    },
                    {
                        header: "복사일",
                        dataIndex: "copy_date",
                        sortable: true,
                        renderer: function (
                            value,
                            metaData,
                            record,
                            rowIndex,
                            colIndex,
                            store
                        ) {
                            if (!(value == null)) {
                                return _this._columnValueDateFormat(value);
                            }
                        }
                    },

                    {
                        header: "카트번호",
                        dataIndex: "card_num",
                        sortable: true,
                        align: "left",
                        hidden: true
                    },
                    {
                        header: "복사일자",
                        dataIndex: "copy_date",
                        sortable: true,
                        align: "left",
                        hidden: true
                    },
                    {
                        header: "배송방법",
                        dataIndex: "delivery",
                        sortable: true,
                        align: "left",
                        hidden: true
                    },
                    {
                        header: "배송료",
                        dataIndex: "delivery_amt",
                        sortable: true,
                        align: "left",
                        hidden: true
                    },

                    {
                        header: "배송일",
                        dataIndex: "delivery_date",
                        sortable: true,
                        renderer: function (
                            value,
                            metaData,
                            record,
                            rowIndex,
                            colIndex,
                            store
                        ) {
                            if (!(value == null)) {
                                return _this._columnValueDateFormat(value);
                            }
                        }
                    },

                    {
                        header: "주문일자",
                        dataIndex: "order_date",
                        sortable: true,
                        align: "left",
                        hidden: true
                    },
                    {
                        header: "사용주체",
                        dataIndex: "purpose",
                        sortable: true,
                        align: "left",
                        hidden: true
                    },

                    {
                        header: "취소일",
                        dataIndex: "cancel_date",
                        sortable: true,
                        renderer: function (
                            value,
                            metaData,
                            record,
                            rowIndex,
                            colIndex,
                            store
                        ) {
                            if (!(value == null)) {
                                return _this._columnValueDateFormat(value);
                            }
                        }
                    },
                    {
                        header: "환불일자",
                        dataIndex: "repay_date",
                        sortable: true,
                        align: "left",
                        hidden: true
                    },
                    {
                        header: "주문상태",
                        dataIndex: "status",
                        sortable: true,
                        align: "left",
                        hidden: true
                    },
                    {
                        header: "사용목적",
                        dataIndex: "usepo",
                        sortable: true,
                        align: "left",
                        hidden: true
                    }
                ]
            });
            this.tbar = [
                "신청일",
                " ",
                {
                    xtype: "datefield",
                    editable: false,
                    format: "Y-m-d",
                    name: "start_date",
                    value: _this.start_date,
                    width: 100,
                    listeners: {
                        select: function (self, date) {
                            var end_date = _this
                                .getTopToolbar()
                                .find("name", "end_date")[0]
                                .getValue();
                            _this.getStore().reload({
                                params: {
                                    start_date: date.format("Ymd"),
                                    end_date: end_date.format("Ymd")
                                }
                            });
                        }
                    }
                },
                " ",
                "~",
                " ",
                {
                    xtype: "datefield",
                    editable: false,
                    format: "Y-m-d",
                    name: "end_date",
                    width: 100,
                    value: _this.end_date,
                    listeners: {
                        select: function (self, date) {
                            var start_date = _this
                                .getTopToolbar()
                                .find("name", "start_date")[0]
                                .getValue();
                            _this.getStore().reload({
                                params: {
                                    start_date: start_date.format("Ymd"),
                                    end_date: date.format("Ymd")
                                }
                            });
                        }
                    }
                },
                " ",
                "주문번호",
                " ",
                {
                    xtype: "numberfield",
                    name: "search_order_num"
                },
                " ",
                "이름",
                " ",
                {
                    xtype: "textfield",
                    name: "search_cust_nm"
                },
                " ",
                "진행상태",
                _this._inCodeComboBox("status", "false", "*OR001"),
                " ",
                {
                    xtype: "radiogroup",
                    name: "receipt",
                    width: 180,
                    items: [
                        {
                            boxLabel: "전체",
                            name: "receipt",
                            inputValue: "1",
                            checked: true
                        },
                        { boxLabel: "입금전", name: "receipt", inputValue: "2" },
                        { boxLabel: "입금후", name: "receipt", inputValue: "3" }
                    ]
                },
                " ",
                {
                    xtype: "a-iconbutton",
                    text: "검색",
                    handler: function (self) {
                        var getTb = _this.getTopToolbar();
                        var start_date = getTb.find("name", "start_date")[0].getValue();
                        var end_date = getTb.find("name", "end_date")[0].getValue();
                        var search_order_num = getTb
                            .find("name", "search_order_num")[0]
                            .getValue();
                        var search_cust_nm = getTb
                            .find("name", "search_cust_nm")[0]
                            .getValue();
                        var status = getTb.find("name", "status")[0].getValue();
                        var receipt = getTb
                            .find("name", "receipt")[0]
                            .getValue()
                            .getGroupValue();

                        _this.getStore().reload({
                            params: {
                                start_date: start_date.format("Ymd"),
                                end_date: end_date.format("Ymd"),
                                search_order_num: search_order_num,
                                search_cust_nm: search_cust_nm,
                                status: status,
                                receipt: receipt
                            }
                        });
                    }
                },
                " ",
                {
                    xtype: "a-iconbutton",
                    text: "추가",
                    handler: function (self) {
                        if (_this.radioData == null) {
                            _this.purposeCodeStore.load({
                                callback: function (record, opts, success) {
                                    _this.radioData = record;
                                    if (success) {
                                        var win = new Ariel.archiveManagement.inputFormWindow({
                                            action: "add",
                                            text: '추가',
                                            url: Ariel.archiveManagement.UrlSet.order,
                                            method: "POST",
                                            radioData: _this.radioData,
                                            onAfterSave: function () {
                                                _this.store.reload();

                                            }
                                        });
                                        _this._priceDataRequeest(win);
                                    }
                                }
                            });
                        } else {
                            var win = new Ariel.archiveManagement.inputFormWindow({
                                action: "add",
                                text: '추가',
                                url: Ariel.archiveManagement.UrlSet.order,
                                method: "POST",
                                radioData: _this.radioData,
                                onAfterSave: function () {
                                    _this.store.reload();
                                }
                            });
                            _this._priceDataRequeest(win);
                        }
                    }
                },
                {
                    xtype: "a-iconbutton",
                    text: "수정",
                    handler: function (self) {
                        var sm = _this.getSelectionModel();
                        if (sm.hasSelection()) {
                            var selectRecord = sm.getSelected();

                            _this._updateFormWindowShow(_this, selectRecord);
                        } else {
                            Ext.Msg.alert("알림", "수정하실 목록을 선택해주세요.");
                        }
                    }
                },
                {
                    xtype: "a-iconbutton",
                    text: "삭제",
                    handler: function (self) {
                        var sm = _this.getSelectionModel();

                        if (sm.hasSelection()) {
                            var getRecord = sm.getSelected().data;

                            Ext.Msg.show({
                                title: "알림",
                                msg: "삭제하시겠습니까?",
                                buttons: Ext.Msg.OKCANCEL,
                                fn: function (btnId, text, opts) {
                                    if (btnId == "ok") {
                                        Ext.Ajax.request({
                                            method: "DELETE",
                                            url: Ariel.archiveManagement.UrlSet.orderOrderNumParam(
                                                getRecord.order_num
                                            ),
                                            callback: function (opts, success, resp) {
                                                if (success) {
                                                    try {
                                                        _this.store.reload();
                                                    } catch (e) {
                                                        Ext.Msg.alert(e["name"], e["message"]);
                                                    }
                                                } else {
                                                    Ext.Msg.alert(
                                                        "status: " + resp.status,
                                                        resp.statusText
                                                    );
                                                }
                                            }
                                        });
                                    }
                                }
                            });
                        } else {
                            Ext.Msg.alert("알림", "삭제하실 목록을 선택해주세요.");
                        }
                    }
                }
            ];
            this.bbar = new Ext.PagingToolbar({
                pageSize: this.pageSize,
                store: this.store
            });
        },
        /**
         * 콤보박스 목록을 코드셋에서 가져온 코드아이템 목록으로
         * @param string name 콤보박스 네임
         * @param string fieldName 콤보박스 필드 네임
         * @param string code 코드 셋 코드로 코드 아이템 조회
         */
        _inCodeComboBox: function (name, fieldName, code) {
            var _this = this;

            var combo = new Ext.form.ComboBox({
                allowBlank: false,
                name: name,
                hiddenName: name,
                editable: false,
                mode: "local",
                fieldLabel: fieldName,
                displayField: "code_itm_nm",
                valueField: "code_itm_code",
                hiddenValue: "code_itm_code",
                typeAhead: true,

                // beforeValue: '',
                triggerAction: "all",
                lazyRender: true,
                store: new Ext.data.JsonStore({
                    restful: true,
                    proxy: new Ext.data.HttpProxy({
                        method: "GET",
                        url: Ariel.glossary.UrlSet.codeSetIdParamCodeItems(code),
                        type: "rest"
                    }),
                    root: "data",
                    fields: [
                        { name: "code_itm_code", mapping: "code_itm_code" },
                        { name: "code_itm_nm", mapping: "code_itm_nm" },
                        { name: "id", mapping: "id" }
                    ],
                    listeners: {
                        load: function (store, r, option) {
                            var comboRecord = Ext.data.Record.create([
                                { name: "code_itm_code" },
                                { name: "code_itm_nm" }
                            ]);
                            var allComboMenu = {
                                code_itm_code: "0",
                                code_itm_nm: "전체"
                            };
                            var addComboMenu = new comboRecord(allComboMenu);
                            store.insert(0, addComboMenu);

                            var firstValue = store.data.items[0].data.code_itm_code;

                            combo.setValue(firstValue);
                        },
                        exception: function (self, type, action, opts, response, args) {
                            try {
                                var r = Ext.decode(response.responseText, true);

                                if (!r.success) {
                                    Ext.Msg.alert(_text("MN00023"), r.msg);
                                }
                            } catch (e) {
                                Ext.Msg.alert(_text("MN00023"), r.msg);
                            }
                        }
                    }
                }),
                listeners: {
                    afterrender: function (self) {
                        self.getStore().load({
                            params: {
                                is_code: 1
                            }
                        });
                    },
                    select: function (self, record, idx) {
                        self.setValue(record.get("code_itm_code"));
                    }
                }
            });
            return combo;
        },
        _columnValueDateFormat(value) {
            var date = new Date(
                value / 10000,
                (value % 10000) / 100 - 1,
                value % 100
            );
            return Ext.util.Format.date(date, "Y-m-d");
        },
        /**
         * 수정버튼을 눌렀을때 윈도우 창
         * @param {} _this
         * @param {*} selectRecord
         */
        _updateFormWindowShow: function (_this, orderRecord) {
            if (_this.radioData == null) {
                _this.purposeCodeStore.load({
                    callback: function (record, opts, success) {
                        _this.radioData = record;
                        if (success) {
                            var win = new Ariel.archiveManagement.inputFormWindow({
                                action: "edit",
                                text: '수정',
                                method: "PUT",
                                url: Ariel.archiveManagement.UrlSet.orderOrderNumParam(
                                    orderRecord.data.order_num
                                ),
                                orderRecord: orderRecord,
                                radioData: _this.radioData,
                                onAfterSave: function () {
                                    _this.store.reload();
                                }
                            });
                            _this._priceDataRequeest(win);
                        }
                    }
                });
            } else {
                var win = new Ariel.archiveManagement.inputFormWindow({
                    action: "edit",
                    text: '수정',
                    method: "PUT",
                    url: Ariel.archiveManagement.UrlSet.orderOrderNumParam(orderRecord.data.order_num),
                    // selectRecord: selectRecord,
                    orderRecord: orderRecord,
                    radioData: _this.radioData,
                    onAfterSave: function () {
                        _this.store.reload();
                    }
                });
                _this._priceDataRequeest(win);
            }
        },
        _numberWithCommas(x) {
            return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        },
        /**
         * 주문관리 윈도우 에서 가격을 조정할  가격관리 데이터
         */
        _priceDataRequeest: function (win) {
            return Ext.Ajax.request({
                method: "GET",
                url: Ariel.archiveManagement.UrlSet.price,
                callback: function (opts, success, resp) {

                    priceData = Ext.decode(resp.responseText).data;
                    win.priceData = priceData;

                    win.show();
                }
            });
        }
    });
    return new Ariel.archiveManagement.orderListGrid();
})()
