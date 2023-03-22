Ext.ns('Ariel.glossary');
Ariel.glossary.SelectCodeWindow = Ext.extend(Ext.Window, {
    // property
    selectCode: null, //textfield 
    modal: true,
    // autoHeight: true,


    // constructor: function(config) {
    //     Ariel.glossary.SelectCodeWindow.superclass.constructor.call(this,config);
    // },

    initComponent: function () {

        this._initialize();

        Ariel.glossary.SelectCodeWindow.superclass.initComponent.call(this);
    },

    _initialize: function () {

        var _this = this;
        this.storeUrl = '/api/v1/data-dic-code-sets';
        this.pageSize = 25;
        this.items = this._showSelectGridWindow();
        this.buttonAlign = 'center';
        this.buttons = [{
            text: '선택',
            scale: 'medium',
            handler: function (self) {

                var sm = _this.smClick;
                if (sm == null) {
                    Ext.Msg.show({
                        title: '알림',
                        msg: '목록을 선택해 주세요.',
                        buttons: Ext.Msg.OK,
                    });
                } else if (sm.getSelected()) {
                    var clickData = sm.getSelected().json;

                    var Data = {
                        code_set_id: clickData.id,
                    }
                    _this.inputForm.getForm().setValues(Data);
                    _this._textSetValue(_this.checkCode, clickData);
                    _this.close();

                }
            }
        }, {
            text: '취소',
            scale: 'medium',
            handler: function (self) {
                _this.close();
            }
        }];
    },
    _showSelectGridWindow: function () {
        var _this = this;
        var selectGrid = new Ext.grid.GridPanel({
            title: '도메인 찾기',
            loadMask: true,
            stripeRows: true,
            frame: true,
            height: 200,
            store: new Ext.data.JsonStore({
                restful: true,
                proxy: new Ext.data.HttpProxy({
                    method: 'GET',
                    url: this.storeUrl,
                    type: 'rest',
                }),
                root: 'data',
                fields: [
                    { name: 'id', type: 'float' },
                    'code_set_nm',
                    'code_set_code',
                ]
            }),
            cm: new Ext.grid.ColumnModel({
                columns: [
                    new Ext.grid.RowNumberer({
                        header: '순번',
                        width: 60,
                    }),
                    // { header: 'ID', dataIndex: 'id' },
                    { header: 'id', dataIndex: 'id' },
                    { header: '코드명', dataIndex: 'code_set_nm' },
                    { header: '코드영문명', dataIndex: 'code_set_code' },
                ]
            }),
            viewConfig: {
                emptyText: '목록이 없습니다.'
            },
            listeners: {
                afterrender: function (self) {

                    self.store.load({
                        params: {
                            keyword: _this.selectCode
                        }
                    });
                },
                rowdblclick: function (self, rowIndex, e) {

                    var sm = self.getSelectionModel();
                    var searchData = sm.getSelected().json;
                    Ext.Msg.show({
                        title: '알림',
                        msg: searchData.code_set_nm + '을 선택하시겠습니까?',
                        buttons: Ext.Msg.OKCANCEL,
                        fn: function (btn, text, opts) {
                            if (btn == 'ok') {
                                var Data = {
                                    code_set_id: searchData.id,
                                    // domn_nm: searchData.code_set_nm,
                                    // domn_eng_nm: searchData.code_set_code,

                                }
                                _this.inputForm.getForm().setValues(Data);
                                _this._textSetValue(_this.checkCode, searchData);
                                self.ownerCt.close();
                            }
                        }
                    });
                },
                rowclick: function (self, idx, e) {
                    _this.smClick = self.getSelectionModel();
                }
            }
        });
        return selectGrid;
    },
    _textSetValue: function (textField, record) {
        var data_lt = Ext.isEmpty(record.data_lt) ? '' : '(' + record.data_lt + ')';

        return textField.setValue(record.code_set_nm + ' / ' +
            record.code_set_code);
    }
    // _getSearchList: function (text) {
    //     var col = this.items.get(0).getColumnModel().columns;
    //     var colArr = new Array();

    //     Ext.each(col, function (i, idx, e) {
    //         colArr[idx - 1] = i.dataIndex;
    //     });

    //     var total = colArr.length - 1;
    //     var fullText = '';
    //     Ext.each(colArr, function (i, idx, e) {
    //         if (idx < total) {
    //             fullText = fullText + i + " like '%" + text + "%' OR ";
    //         }
    //         if (idx == total) {
    //             fullText = fullText + i + " like '%" + text + "%'";
    //         }
    //     })

    //     return fullText;
    // }
});
