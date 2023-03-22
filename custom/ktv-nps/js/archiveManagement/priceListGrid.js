(function () {
    Ext.ns('Ariel.archiveManagement');
    Ariel.archiveManagement.priceListGrid = Ext.extend(Ext.grid.EditorGridPanel, {
        title: '<span class="user_span"><span class="icon_title"><i class=""></i></span><span class="main_title_header">' + '가격관리' + '</span></span>',
        loadMask: true,
        stripeRows: true,
        frame: false,
        autoWidth: true,
        // layout: 'fit',
        viewConfig: {
            emptyText: '목록이 없습니다.',
            // forceFit: true,
            border: false
        },
        cls: 'grid_title_customize proxima_customize',
        listeners: {
            afterrender: function (self) {
                self.getStore().load({

                });
            }
        },
        initComponent: function () {

            this._initialize();
            Ariel.archiveManagement.priceListGrid.superclass.initComponent.call(this);
        },
        _initialize: function () {
            var _this = this;
            var sm = new Ext.grid.CheckboxSelectionModel({
                singleSelect: false
            });
            this.store = new Ext.data.JsonStore({
                remoteSort: true,
                restful: true,
                proxy: new Ext.data.HttpProxy({
                    method: 'GET',
                    url: Ariel.archiveManagement.UrlSet.price,
                    type: 'rest'
                }),
                remoteSort: true,
                totalProperty: 'total',
                root: 'data',
                fields: [
                    'idx',
                    'method',
                    'prolength',
                    'price',
                    'won_price',
                    'tape_price',
                    'id',
                    'regist_user_id',
                    'updt_user_id',
                    'regist_dt',
                    'updt_dt',
                    'delete_dt'
                ]
            });
            this.sm = sm;

            this.cm = new Ext.grid.ColumnModel({
                defaults: {
                    align: 'center',
                    menuDisabled: true,
                    sortable: false
                },
                columns: [
                    sm,
                    {
                        header: '순번',
                        renderer: function (v, p, record, rowIndex) {
                            return rowIndex + 1;
                        }
                    },
                    { header: '순번', dataIndex: 'idx', sortable: true, width: 100, hidden: true },
                    {
                        header: '규격', dataIndex: 'method', sortable: true, editor: {
                            xtype: 'combo',
                            allowBlank: false,
                            editable: false,
                            mode: "local",
                            displayField: 'code_itm_nm',
                            valueField: 'code_itm_code',
                            hiddenValue: 'code_itm_code',
                            typeAhead: true,

                            // beforeValue: '',
                            triggerAction: 'all',
                            lazyRender: true,
                            store: new Ext.data.JsonStore({
                                restful: true,
                                proxy: new Ext.data.HttpProxy({
                                    method: 'GET',
                                    url: Ariel.glossary.UrlSet.codeSetIdParamCodeItems('OR_PRICE'),
                                    type: 'rest'
                                }),
                                root: 'data',
                                fields: [
                                    { name: 'code_itm_code', mapping: 'code_itm_code' },
                                    { name: 'code_itm_nm', mapping: 'code_itm_nm' },
                                    { name: 'id', mapping: 'id' }
                                ],
                                listeners: {
                                    load: function (store, r, option) {

                                    },
                                    exception: function (self, type, action, opts, response, args) {
                                        try {
                                            var r = Ext.decode(response.responseText, true);

                                            if (!r.success) {
                                                Ext.Msg.alert(_text('MN00023'), r.msg);
                                            }
                                        }
                                        catch (e) {
                                            Ext.Msg.alert(_text('MN00023'), r.msg);
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
                                    // var form = _this.inputForm.getForm();
                                    // if (record.get('code_itm_code') == 'CODE') {
                                    //     form.findField('searchfield').enable();
                                    // };

                                    self.setValue(record.get('code_itm_code'));


                                }
                            }
                        }, width: 130
                    },
                    { header: '시간(분)', dataIndex: 'prolength', sortable: true, editor: { xtype: 'numberfield' }, width: 130 },
                    {
                        header: '가격(원)', dataIndex: 'price', sortable: true, align: 'right', editor: { xtype: 'numberfield' }, width: 130, renderer: function (value, metaData, record, rowIndex, colIndex, store) {
                            if (value == null) {
                                return null;
                            } else {
                                return _this._numberWithCommas(value) + '원';
                            }
                        },
                    },
                    {
                        header: '원본가격(원)', dataIndex: 'won_price', sortable: true, align: 'right', editor: { xtype: 'numberfield' }, width: 130, renderer: function (value, metaData, record, rowIndex, colIndex, store) {
                            if (value == null) {
                                return null;
                            } else {
                                return _this._numberWithCommas(value) + '원';
                            }
                        }
                    },
                    // { header: '', dataIndex: 'tape_price', sortable: true },
                    // { header: '', dataIndex: 'id', sortable: true, align: 'left' },
                    // { header: '', dataIndex: 'regist_user_id', sortable: true, align: 'left', hidden: true },

                    // { header: '', dataIndex: 'updt_user_id', sortable: true, align: 'right' },
                    // { header: '', dataIndex: 'regist_dt', sortable: true },
                    // { header: '', dataIndex: 'updt_dt', sortable: true, align: 'left' },
                    // { header: '', dataIndex: 'delete_dt', sortable: true, align: 'left', hidden: true },
                ]
            });
            this.tbar = [
                {
                    xtype: 'a-iconbutton',
                    text: '새로고침',
                    handler: function (self) {
                        _this.getStore().reload();
                    }
                }, {
                    xtype: 'a-iconbutton',
                    text: '추가',
                    handler: function (self) {
                        var gridStore = _this.getStore();
                        var maxIdxArr = [];
                        Ext.each(gridStore.data.items, function (r, i, e) {
                            maxIdxArr[i] = r.data.idx;
                        });
                        var maxIdx = Math.max.apply(null, maxIdxArr) + 1;

                        var orderSalesPriceRecord = Ext.data.Record.create([
                            { name: "idx" },
                            { name: 'method' },
                            { name: 'prolength' },
                            { name: "price" },
                            { name: 'won_price' },
                            { name: 'tape_price' },
                            { name: "id" },
                            { name: 'regist_user_id' },
                            { name: 'updt_user_id' },
                            { name: 'regist_dt' },
                            { name: "updt_dt" },
                            { name: 'delete_dt' }
                        ]);

                        var newRecord = {
                            "idx": null,
                            "method": null,
                            "prolength": null,
                            "price": null,
                            "won_price": null,
                            "tape_price": null,
                            "id": null,
                            "regist_user_id": null,
                            "updt_user_id": null,
                            "regist_dt": null,
                            "updt_dt": null,
                            "delete_dt": null
                        };

                        var addRecord = new orderSalesPriceRecord(newRecord);
                        _this.getStore().add(addRecord);

                    }
                }, {
                    xtype: 'a-iconbutton',
                    text: '저장',
                    handler: function (self) {
                        var gridStore = _this.getStore();
                        var createOrUpdateRecord = [];
                        Ext.each(gridStore.data.items, function (r, i, e) {
                            createOrUpdateRecord[i] = r.data;
                        });



                        Ext.Ajax.request({
                            method: 'PUT',
                            url: Ariel.archiveManagement.UrlSet.price,
                            params: {
                                record: Ext.encode(createOrUpdateRecord)
                            },
                            callback: function (opts, success, resp) {
                                if (success) {
                                    try {
                                        _this.getStore().reload();
                                        Ext.Msg.alert('알림', '저장되었습니다.');
                                    } catch (e) {
                                        Ext.Msg.alert(e['name'], e['message']);
                                    }
                                } else {
                                    Ext.Msg.alert('status: ' + resp.status, resp.statusText);
                                }
                            }
                        })

                    }
                }, {
                    xtype: 'a-iconbutton',
                    text: '삭제',
                    handler: function (self) {
                        if (sm.hasSelection()) {
                            var checkIdx = sm.getSelections()[0].data.idx;

                            Ext.Msg.show({
                                title: '알림',
                                msg: '삭제하시겠습니까?',
                                buttons: Ext.Msg.OKCANCEL,
                                fn: function (btnId, text, opts) {
                                    if (btnId == 'ok') {
                                        Ext.Ajax.request({
                                            method: 'DELETE',
                                            // url: '/api/v1/content-orderSalesPrice/' + checkIdx,
                                            url: Ariel.archiveManagement.UrlSet.getOrderSalesPriceByIdx(checkIdx),
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
                                        })
                                    }
                                }
                            })
                        } else {
                            Ext.Msg.alert('알림', '삭제하실 목록을 선택해주세요.');
                        };


                    }
                }
            ]

        },
        _numberWithCommas(x) {
            return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        }
    });
    return new Ariel.archiveManagement.priceListGrid();
})()