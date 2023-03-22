Ext.ns('Ariel.glossary');
Ariel.glossary.SelectFieldWindow = Ext.extend(Ext.Window, {
    // property
    selectDomain: null, //textfield 
    modal: true,



    // constructor: function(config) {
    //     Ariel.glossary.SelectDomainWindow.superclass.constructor.call(this,config);
    // },

    initComponent: function () {

        this._initialize();

        Ariel.glossary.SelectDomainWindow.superclass.initComponent.call(this);
    },

    _initialize: function () {
        var _this = this;
        this.storeUrl = '/api/v1/data-dic-fields';
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

                    // var Data = {
                    //     field_id: clickData.id,
                    //     column_nm: clickData.field_nm.replace(/ /gi, ""),
                    //     column_eng_nm: clickData.field_eng_nm,
                    //     domn_nm: clickData.domain.domn_nm,
                    //     data_ty: clickData.domain.data_ty,
                    //     data_lt: clickData.domain.data_lt,
                    // };

                    // _this.inputForm.getForm().setValues(Data);
                    // _this._textSetValue(_this.checkField, clickData);
                    _this._setValueFieldData(clickData, _this);


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
            title: '필드 찾기',
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
                totalProperty: 'total',
                root: 'data',
                fields: [
                    { name: 'id', type: 'int' },
                    'field_nm',
                    'field_eng_nm',

                ]
            }),
            cm: new Ext.grid.ColumnModel({
                columns: [
                    new Ext.grid.RowNumberer({
                        header: '순번',
                        width: 60,
                    }),
                    // { header: 'ID', dataIndex: 'id' },
                    { header: '필드명', dataIndex: 'field_nm' },
                    { header: '영문명', dataIndex: 'field_eng_nm' },


                ]
            }),
            viewConfig: {
                emptyText: '목록이 없습니다.'
            },
            listeners: {
                afterrender: function (self) {
                    self.store.load({
                        params: {
                            keyword: _this.selectField
                        }
                    });
                },
                rowdblclick: function (self, rowIndex, e) {
                    var sm = self.getSelectionModel();
                    var searchData = sm.getSelected().json;
                    Ext.Msg.show({
                        title: '알림',
                        msg: searchData.field_nm + '을 선택하시겠습니까?',
                        buttons: Ext.Msg.OKCANCEL,
                        fn: function (btn, text, opts) {
                            if (btn == 'ok') {
                                if (searchData.domain == null) {
                                    Ext.Msg.alert('알림', '도메인이 추가되지 않은 필드입니다.');
                                } else {
                                    // var Data = {
                                    //     field_id: searchData.id,
                                    //     column_nm: searchData.field_nm,
                                    //     column_eng_nm: searchData.field_eng_nm,
                                    //     domn_nm: searchData.domain.domn_nm,
                                    //     data_ty: searchData.domain.data_ty,
                                    //     data_lt: searchData.domain.data_lt,
                                    // };
                                    // _this.inputForm.getForm().setValues(Data);

                                    // _this._textSetValue(_this.checkField, searchData);
                                    _this._setValueFieldData(searchData, _this);

                                    self.ownerCt.close();
                                };
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
    /**
     * 필드 조회시 간략하게 텍스트로 만들어 보여줌
     * @param object textField 
     * @param object record 
     */
    _textSetValue: function (textField, record) {

        var data_lt = Ext.isEmpty(record.domain.data_lt) ? '' : '(' + record.domain.data_lt + ')';

        return textField.setValue(record.field_nm + ' / ' +
            record.field_eng_nm + ' / ' +
            record.domain.data_ty + data_lt);
    },
    _setValueFieldData: function (data, _this) {
        var filedData = {
            field_id: data.id,
            column_nm: data.field_nm.replace(/ /gi, ""),
            column_eng_nm: data.field_eng_nm,
            domn_nm: data.domain.domn_nm,
            data_ty: data.domain.data_ty,
            data_lt: data.domain.data_lt,
        };

        _this.inputForm.getForm().setValues(filedData);

        _this._textSetValue(_this.checkField, data);
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
