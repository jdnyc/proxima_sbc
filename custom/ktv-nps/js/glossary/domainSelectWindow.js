Ext.ns('Ariel.glossary');
Ariel.glossary.SelectDomainWindow = Ext.extend(Ext.Window, {
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
        this.storeUrl = '/api/v1/data-dic-domains';
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
                        domn_id: clickData.id,

                    }
                    _this.inputForm.getForm().setValues(Data);
                    _this._textSetValue(_this.checkDomain, clickData);
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
                    { name: 'id', type: 'int' },
                    'domn_nm',
                    'domn_eng_nm',
                    'domn_mlsfc',
                    'data_ty',
                    'data_lt',
                    'domn_ty'
                ],
            }),
            cm: new Ext.grid.ColumnModel({
                columns: [
                    new Ext.grid.RowNumberer({
                        header: '순번',
                        width: 60,
                    }),
                    // { header: 'ID', dataIndex: 'id' },
                    { header: '도메인명', dataIndex: 'domn_nm' },
                    { header: '영문명', dataIndex: 'domn_eng_nm' },
                    { header: '분류', dataIndex: 'domn_mlsfc' },
                    { header: '데이터타입', dataIndex: 'data_ty' },
                    { header: '길이', dataIndex: 'data_lt' },
                    { header: '유형', dataIndex: 'domn_ty' }

                ]
            }),
            viewConfig: {
                emptyText: '목록이 없습니다.'
            },
            listeners: {
                afterrender: function (self) {

                    self.store.load({
                        params: {
                            keyword: _this.selectDomain
                        }
                    });
                },
                rowdblclick: function (self, rowIndex, e) {

                    var sm = self.getSelectionModel();
                    var searchData = sm.getSelected().json;
                    Ext.Msg.show({
                        title: '알림',
                        msg: searchData.domn_nm + '을 선택하시겠습니까?',
                        buttons: Ext.Msg.OKCANCEL,
                        fn: function (btn, text, opts) {
                            if (btn == 'ok') {
                                var Data = {
                                    domn_id: searchData.id,

                                }
                                _this.inputForm.getForm().setValues(Data);
                                _this._textSetValue(_this.checkDomain, searchData);
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

        return textField.setValue(record.domn_nm + ' / ' +
            record.domn_eng_nm + ' / ' +
            record.data_ty + data_lt);
    },
    /**
     * where절 like구문 만들어 주는 함수 api로 대체되어서 지워도 될 듯
     */
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
