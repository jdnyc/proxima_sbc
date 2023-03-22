Ext.ns('Ariel.glossary');
Ariel.glossary.inputFormWindow = Ext.extend(Ext.Window, {

    modal: true,
    width: 500,
    autoHeight: true,
    termRecord: null, //수정할 용어 데이터(Record)
    onAfterSave: null,
    buttonText: null,
    action: null, //카테고리 종류
    // layout: 'fit',
    // constructor: function(config) {

    //     Ariel.glossary.WordListGrid.superclass.constructor.call(this,config);
    // },
    listeners: {
        render: function (self) {

            if (self.type == 'edit') {
                var domainRecord = self.getRecord.data.domain;

                this.inputForm.getForm().setValues(domainRecord);
                this.inputForm.getForm().setValues(self.getRecord.json);

            }

            if (self.action == 'word') {
                delete self.buttons[0].handler;

                self.buttons[0].handler = function (self1) {

                    if (!self.inputForm.getForm().isValid()) {
                        Ext.Msg.show({
                            title: '알림',
                            msg: '입력되지 않은 값이 있습니다.',
                            buttons: Ext.Msg.OK,
                        });
                        return;
                    }
                    if (self.type == 'edit') {
                        var msg = '수정하시겠습니까? ';
                        self._checkSubmit(msg);
                    } else {
                        var wordName = self.inputForm.getForm().findField('word_nm').getValue();
                        Ext.Ajax.request({
                            method: 'POST',
                            url: Ariel.glossary.UrlSet.wordNameParamSearch(),
                            params: {
                                keyword: wordName
                            },
                            callback: function (opts, success, resp) {
                                if (success) {
                                    try {
                                        var wordCheck = Ext.decode(resp.responseText);

                                        if ((Array.isArray(wordCheck.data))) {
                                            var msg = '같은 이름의 용어가 있습니다.' + '<br />' + '그래도 추가하시겠습니까?';
                                            self._checkSubmit(msg);
                                        } else if (!Ext.isEmpty(wordCheck.msg)) {
                                            var msg = '추가하시겠습니까? ';
                                            self._checkSubmit(msg);
                                        }
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
            } else if (self.action == 'field') {
                delete self.buttons[0].handler;

                self.buttons[0].handler = function (self1) {

                    if (!self.inputForm.getForm().isValid()) {
                        Ext.Msg.show({
                            title: '알림',
                            msg: '입력되지 않은 값이 있습니다.',
                            buttons: Ext.Msg.OK,
                        });
                        return;
                    }

                    if (self.type == 'edit') {
                        var msg = '수정하시겠습니까? ';
                        self._checkSubmit(msg);
                    } else {
                        var wordName = self.inputForm.getForm().findField('field_nm').getValue();
                        Ext.Ajax.request({
                            method: 'POST',
                            url: Ariel.glossary.UrlSet.fieldNameParamSearch(),
                            params: {
                                keyword: wordName
                            },
                            callback: function (opts, success, resp) {
                                if (success) {
                                    try {
                                        var wordCheck = Ext.decode(resp.responseText);

                                        if ((Array.isArray(wordCheck.data)) && !Ext.isEmpty(wordCheck.data)) {
                                            var msg = '같은 이름이 있습니다.' + '<br />' + '그래도 추가하시겠습니까?';
                                            self._checkSubmit(msg);
                                        } else {
                                            var msg = '추가하시겠습니까? ';
                                            self._checkSubmit(msg);
                                        }
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
            }

        }
    },

    initComponent: function () {

        this._initialize();
        Ariel.glossary.inputFormWindow.superclass.initComponent.call(this);

    },
    _initialize: function () {
        var _this = this;
        var inputForm = null;

        this._gridAction(this);
        this.items = this.inputForm;


        this.buttonAlign = 'center';
        this.buttons = [{
            text: this.title,
            scale: 'medium',
            handler: function (self) {
                if (!_this.inputForm.getForm().isValid()) {
                    Ext.Msg.show({
                        title: '알림',
                        msg: '입력되지 않은 값이 있습니다.',
                        buttons: Ext.Msg.OK,
                    });
                    return;
                }
                _this.inputForm.getForm().submit({
                    method: _this.method,
                    url: _this.apiUrl,
                    success: function (form, action) {

                        Ext.Msg.show({
                            title: '알림',
                            msg: _this.title + ' 되었습니다.',
                            buttons: Ext.Msg.OK,
                            fn: function (btnId, text, opts) {
                                if (btnId == 'ok') {

                                    _this.onAfterSave();
                                    _this.close();
                                };
                            }
                        });
                    },
                    failure: function (form, action) {
                        // console.log(action);
                        // var error = Ext.decode(action.response.responseText);
                        // Ext.Msg.alert(error.code, error.msg);
                    }
                });

            }
        }, {
            text: '취소',
            scale: 'medium',
            handler: function (self) {
                _this.close();
            }
        }];
        // 여기다가 하면 왜 안되지?
        // this.listeners = {

        //     render: function (self) {

        //         if (self.type == 'edit') {

        //             // this.inputForm.getForm().setValues(self.getRecord.data.domain);??
        //             this.inputForm.getForm().setValues(self.getRecord.json);
        //         }
        //     }
        // };

    },
    /**
     * 같은 용어 체크 후 서브밋
     */
    _checkSubmit: function (msg) {
        var _this = this;
        return Ext.Msg.show({
            title: '알림',
            msg: msg,
            buttons: Ext.Msg.OKCANCEL,
            fn: function (btnId) {
                if (btnId == 'ok') {
                    _this.inputForm.getForm().submit({
                        method: _this.method,
                        url: _this.apiUrl,
                        success: function (form, action) {
                            _this.onAfterSave();
                            _this.close();
                        },
                        failure: function (form, action) {
                            // console.log(action);
                            var error = Ext.decode(action.response.responseText);
                            Ext.Msg.alert(error.code, error.msg);
                        }
                    });

                }
            }
        });
    },
    /**
     * 도메인을 찾는 검색 결과를 텍스트 필드로 셋 벨류 해준다 
     * @param {*} resp result
     * @param {*} type domn_nm field_nm code_set_nm
     */
    _searchDomainControll: function (resp, type) {
        var _this = this;

        var inputForm = this.items.get(0)
        var total = Ext.decode(resp.responseText).total;

        if (total == 0) {
            inputForm.find('name', type)[0].setValue('');
            return Ext.Msg.show({
                title: '알림',
                msg: '검색된 항목이 없습니다.',
                buttons: Ext.Msg.OK,
            });
        }
        if (total == 1) {
            var searchData = Ext.decode(resp.responseText).data[0];
            var data_lt = Ext.isEmpty(searchData.data_lt) ? '' : '(' + searchData.data_lt + ')';
            var domainData = {
                domn_id: searchData.id,
                domn_nm: searchData.domn_nm
            }
            inputForm.getForm().setValues(domainData);


            return inputForm.find('name', type)[0].setValue(searchData.domn_nm + ' / '
                + searchData.domn_eng_nm + ' / '
                + searchData.data_ty + data_lt
            );
        }
        if (total > 1) {
            return new Ariel.glossary.SelectDomainWindow({
                selectDomain: inputForm.find('name', 'searchfield')[0].get(0).items.get(0).getValue(),
                checkDomain: inputForm.find('name', type)[0],
                inputForm: inputForm
            }).show();
        }



    },
    /**
     * 도메인,필드,코드를 찾는 검색 결과를 텍스트 필드로 셋 벨류 해준다 
     * @param {*} resp result
     * @param {*} type domn_nm field_nm code_set_nm
     */
    _searchFieldControll: function (resp, type) {

        var inputForm = this.items.get(0)

        var total = Ext.decode(resp.responseText).total;

        if (total == 0) {
            inputForm.find('name', type)[0].setValue('');
            return Ext.Msg.show({
                title: '알림',
                msg: '검색된 항목이 없습니다.',
                buttons: Ext.Msg.OK,
            });
        }
        if (total == 1) {
            var searchData = Ext.decode(resp.responseText).data[0];

            var Data = {
                field_id: searchData.id,
                column_nm: searchData.field_nm.replace(/ /gi, ""),
                column_eng_nm: searchData.field_eng_nm,
                domn_nm: searchData.domain.domn_nm,
                data_ty: searchData.domain.data_ty,
                data_lt: searchData.domain.data_lt,
            };

            inputForm.getForm().setValues(Data);



            return inputForm.find('name', type)[0].setValue(searchData.field_nm + ' / '
                + searchData.field_eng_nm + ' / '
                + searchData.domain.domn_nm);
        }
        if (total > 1) {
            return new Ariel.glossary.SelectFieldWindow({
                selectField: inputForm.find('name', 'searchfield')[0].get(0).items.get(0).getValue(),
                checkField: inputForm.find('name', type)[0],
                inputForm: inputForm
            }).show();

        }


    },
    /**
     * 코드를 찾는 검색 결과를 텍스트 필드로 셋 벨류 해준다 
     * @param {*} resp result
     * @param {*} type domn_nm field_nm code_set_nm
     */
    _searchCodeControll: function (resp, type) {
        var _this = this;

        var inputForm = this.items.get(0)
        var total = Ext.decode(resp.responseText).total;

        if (total == 0) {
            inputForm.find('name', type)[0].setValue('');
            return Ext.Msg.show({
                title: '알림',
                msg: '검색된 항목이 없습니다.',
                buttons: Ext.Msg.OK,
            });
        }
        if (total == 1) {
            var searchData = Ext.decode(resp.responseText).data[0];
            var data_lt = Ext.isEmpty(searchData.data_lt) ? '' : '(' + searchData.data_lt + ')';
            var codeData = {
                code_set_id: searchData.id,
                // domn_nm: searchData.code_set_nm,
                // domn_eng_nm: searchData.code_set_code,
            }
            inputForm.getForm().setValues(codeData);


            return inputForm.find('name', type)[0].setValue(searchData.code_set_nm + ' / '
                + searchData.code_set_code);
        }
        if (total > 1) {
            return new Ariel.glossary.SelectCodeWindow({
                selectCode: inputForm.find('name', 'searchfield')[0].get(0).items.get(0).getValue(),
                checkCode: inputForm.find('name', type)[0],
                inputForm: inputForm
            }).show();
        }



    },
    /**
     * FormPanel 파라미터로 구분
     * @param object self 
     */
    _gridAction: function (self) {
        switch (self.action) {
            case 'word':
                self.inputForm = this._inputFormWord();
                break;
            case 'codeSet':
                self.inputForm = this._inputFormCodeSet();
                break;
            case 'codeItem':
                self.inputForm = this._inputFormCodeItem();
                break;
            case 'table':
                self.inputForm = this._inputFormTable();
                break;
            case 'column':
                self.inputForm = this._inputFormColumn();
                break;
            case 'domain':
                self.inputForm = this._inputFormDomain();
                break;
            case 'field':
                self.inputForm = this._inputFormField();
                break;

        }
    },
    /**
     * 도메인 필드 코드 (조회,초기화) 창 정의
     * @param string type 
     */
    _searchField: function (type) {
        var _this = this;
        if (!(_this.getRecord == null)) {
            if (!(type == 'code')) {
                var r = _this.getRecord.data.domain;
            } else {
                var r = _this.getRecord.data;
            }
        }

        if (type == 'domain') {
            var title = '도메인';
            var name = 'domn_nm';


        } else if (type == 'field') {
            var title = '필드';
            var name = 'field_nm';

        } else if (type == 'code') {
            var title = '코드';
            var name = 'code_set_nm';

        }

        return new Ext.form.FieldSet({
            xtype: 'fieldset',
            title: title,
            layout: 'fit',
            autoHeight: true,
            name: 'searchfield',
            items: [
                {
                    xtype: 'compositefield',
                    name: 'searchfield',
                    autoHeight: true,
                    listeners: {
                        specialkey: function (field, e) {
                            if (e.getKey() == e.ENTER) {
                                var searchText = field.items.get(0).getValue();
                                _this._requestText(type, searchText);
                            }
                        }
                    },
                    items: [{
                        xtype: 'textfield',
                        // emptyText: '이름',
                        name: 'searchFieldName',
                        submitValue: false,
                        flex: 4
                    }, {
                        text: '조회',
                        xtype: 'button',
                        flex: 1,
                        handler: function (self) {
                            var searchText = self.ownerCt.get(0).getValue();
                            _this._requestText(type, searchText);
                        }
                    }, {
                        text: '초기화',
                        xtype: 'button',
                        flex: 1,
                        handler: function (self) {
                            var inputForm = _this.inputForm.getForm();
                            var displayField = inputForm.findField(name);
                            var searchField = inputForm.findField('searchFieldName');
                            var fieldArr = [
                                'domn_nm', 'domn_eng_nm',
                                'field_nm', 'field_eng_nm',
                                'column_eng_nm', 'column_nm',
                                'code_set_id', 'code_set_id'
                            ];
                            searchField.setValue(null);
                            displayField.setValue(displayField.originalValue);
                            Ext.each(fieldArr, function (r, idx, e) {
                                if (!(_this.inputForm.getForm().findField(r) == null)) {

                                    _this.inputForm.getForm().findField(r).setValue(null);
                                }
                            });


                        }
                    }]
                }, {
                    xtype: 'displayfield',
                    name: name
                }
            ],
            listeners: {
                afterrender: function (self) {

                    var searchField = self.ownerCt.getForm().findField('searchFieldName');
                    var displayField = self.ownerCt.getForm().findField(name);
                    if (_this.type == 'edit') {
                        if (type == 'code') {
                            Ext.Ajax.request({
                                url: Ariel.glossary.UrlSet.codeSetIdParam(_this.getRecord.json.code_set_id),
                                method: 'GET',
                                callback: function (opts, success, resp) {
                                    if (success) {

                                        try {

                                            var codeSetData = Ext.decode(resp.responseText).data;

                                            if (codeSetData == null) {

                                            } else {
                                                searchField.setValue(codeSetData.code_set_nm);
                                                displayField.setValue(codeSetData.code_set_code + ' / '
                                                    + codeSetData.code_set_nm)
                                            }

                                        } catch (e) {
                                            //     Ext.Msg.alert(e['name'], e['message']);
                                        }
                                    } else {
                                        Ext.Msg.alert('status: ' + resp.status, resp.statusText);
                                    }
                                }
                            });
                        } else {
                            if (r == null) {
                                return null;
                            } else {

                                searchField.setValue(r.domn_nm);
                                if (r.data_lt == null) {
                                    displayField.setValue(r.domn_nm + ' / '
                                        + r.domn_eng_nm + ' / '
                                        + r.data_ty);

                                } else {
                                    displayField.setValue(r.domn_nm + ' / '
                                        + r.domn_eng_nm + ' / '
                                        + r.data_ty + '( ' + r.data_lt + ' )');

                                }
                            }
                        }
                    }
                },

            }
        });
    },
    /**
     * 추가,수정 창에서 도메인,필드,코드 검색시 url경로 지정
     * @param string type  (도메인,필드,코드)
     * @param string searchText 검색된 텍스트 필드의 값
     */
    _requestText: function (type, searchText) {
        var _this = this;
        if (type == 'domain') {
            var url = Ariel.glossary.UrlSet.domain;
        };
        if (type == 'field') {
            var url = Ariel.glossary.UrlSet.field;
        };
        if (type == 'code') {
            var url = Ariel.glossary.UrlSet.codeSet;
        };
        Ext.Ajax.request({
            url: url,
            method: 'GET',
            params: {
                keyword: searchText,
            },
            callback: function (opts, success, resp) {
                if (success) {
                    try {
                        var r = Ext.decode(resp.responseText);
                        if (r.success == false) {
                            Ext.Msg.alert(r.code, r.msg);
                        } else {
                            if (searchText == '') {
                                Ext.Msg.show({
                                    title: '알림',
                                    msg: '검색할 내용을 입력해 주세요.',
                                    buttons: Ext.Msg.OK,
                                });
                            } else {
                                if (type == 'domain') {
                                    _this._searchDomainControll(resp, 'domn_nm');
                                }
                                if (type == 'field') {
                                    _this._searchFieldControll(resp, 'field_nm');
                                }
                                if (type == 'code') {
                                    _this._searchCodeControll(resp, 'code_set_nm');
                                }
                            }
                        }
                    }
                    catch (e) {
                        //     Ext.Msg.alert(e['name'], e['message']);
                    }
                } else {
                    Ext.Msg.alert('status: ' + resp.status, resp.statusText);
                }
            }
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
                    url: Ariel.glossary.UrlSet.codeSetIdParamCodeItems(code),
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
                        var firstValue = r[0].data.code_itm_code;
                        var form = _this.inputForm.getForm();
                        var self = form.findField(name);
                        if (!(_this.type == 'edit')) {

                            self.setValue(firstValue);

                            if (_this.action == 'domain') {
                                if (!(firstValue == 'CODE')) {
                                    form.findField('searchfield').disable();
                                }
                            }
                        } else {
                            var setComboValue = [];

                            Ext.each(Object.keys(_this.getRecord.data), function (r1, idx1, e) {
                                var objectKey = _this.getRecord.data[r1];

                                Ext.each(objectKey, function (r2, idx2, e) {

                                    if (typeof r2 == 'object') {

                                        if (r2.hasOwnProperty('code_itm_nm')) {

                                            if (self.value === r2.code_itm_code) {
                                                setComboValue[self.name] = r2.code_itm_code;
                                            }
                                        }
                                    }
                                });
                            });

                            if (Array.isArray(setComboValue)) {
                                self.setValue(setComboValue[self.name]);
                            }

                            /**
                             * 수정할거 코드셋에 코드 아이템 넣어줘야함
                             */
                            if (self.name = "code_set_cl") {
                                self.setValue(self.originalValue);
                            };




                            if (_this.action == 'domain') {
                                if (!(firstValue == 'CODE')) {
                                    form.findField('searchfield').disable();
                                } else {
                                    form.findField('searchfield').enable();
                                }
                            }


                        }
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
                        },
                        // callback: function (r, o, success) {
                        //     if (success) {
                        //         try {

                        //             var firstValue = r[0].data.code_itm_code;
                        //             var form = _this.inputForm.getForm();

                        //             if (!(_this.type == 'edit')) {

                        //                 self.setValue(firstValue);

                        //                 if (_this.action == 'domain') {
                        //                     if (!(firstValue == 'CODE')) {
                        //                         form.findField('searchfield').disable();
                        //                     }
                        //                 }
                        //             } else {
                        //                 var setComboValue = [];

                        //                 Ext.each(Object.keys(_this.getRecord.data), function (r1, idx1, e) {
                        //                     var objectKey = _this.getRecord.data[r1];

                        //                     Ext.each(objectKey, function (r2, idx2, e) {

                        //                         if (typeof r2 == 'object') {

                        //                             if (r2.hasOwnProperty('code_itm_nm')) {

                        //                                 if (self.value === r2.code_itm_code) {
                        //                                     setComboValue[self.name] = r2.code_itm_code;
                        //                                 }
                        //                             }
                        //                         }
                        //                     });
                        //                 });

                        //                 if (Array.isArray(setComboValue)) {
                        //                     self.setValue(setComboValue[self.name]);
                        //                 }

                        //                 /**
                        //                  * 수정할거 코드셋에 코드 아이템 넣어줘야함
                        //                  */
                        //                 if (self.name = "code_set_cl") {
                        //                     self.setValue(self.originalValue);
                        //                 };




                        //                 if (_this.action == 'domain') {
                        //                     if (!(firstValue == 'CODE')) {
                        //                         form.findField('searchfield').disable();
                        //                     } else {
                        //                         form.findField('searchfield').enable();
                        //                     }
                        //                 }


                        //             }
                        //         } catch (e) {
                        //             // //     Ext.Msg.alert(e['name'], e['message']);
                        //         }
                        //     } else {
                        //         // Ext.Msg.alert('status: ' + resp.status, resp.statusText);
                        //     }
                        // }
                    });
                },
                select: function (self, record, idx) {
                    var form = _this.inputForm.getForm();
                    if (record.get('code_itm_code') == 'CODE') {
                        form.findField('searchfield').enable();
                    };

                    self.setValue(record.get('code_itm_code'));


                }
            }
        });


        return combo;
    },
    /**
     * db데이터 타입 콤보박스 ()
     * @param this _this 
     * @param bol hide 
     */
    _dataTypeComboBoxField: function (_this, hide) {
        var dataType = new Ext.form.ComboBox({
            width: 130,
            fieldLabel: '데이터 타입',
            hidden: hide,
            name: 'data_ty',
            triggerAction: 'all',
            editable: false,
            mode: 'local',
            store: [
                ['VARCHAR2', 'VARCHAR2'],
                ['NUMBER', 'NUMBER'],
                ['CHAR', 'CHAR'],
                ['CLOB', 'CLOB'],
                ['DATE', 'DATE'],
                ['DOUBLE', 'DOUBLE'],
                ['FLOAT', 'FLOAT']
            ],
            value: 'VARCHAR2',
            listeners: {
                afterrender: function (self) {
                    var form = _this.inputForm.getForm();
                    if (self.value == 'NUMBER' || self.value == 'DOUBLE' || self.value == 'FLOAT') {
                        form.findField('data_lt').disable();
                    } else {

                        form.findField('data_dcmlpoint').disable();
                    }
                },
                select: function (self, r, i) {
                    var form = _this.inputForm.getForm();
                    var data_lt = form.findField('data_lt');
                    var data_dcmlpoint = form.findField('data_dcmlpoint');
                    switch (self.value) {
                        case 'NUMBER':
                        case 'DOUBLE':
                        case 'FLOAT':
                            data_lt.setValue(null);
                            return {
                                data_lt: data_lt.disable(),
                                data_dcmlpoint: data_dcmlpoint.enable()
                            };
                        case 'CHAR':
                        case 'VARCHAR2':
                        case 'CLOB':
                            data_dcmlpoint.setValue(null);
                            return {
                                data_lt: data_lt.enable(),
                                data_dcmlpoint: data_dcmlpoint.disable()
                            };

                        case 'DATE':
                            data_dcmlpoint.setValue(null);
                            return {
                                data_lt: data_lt.disable(),
                                data_dcmlpoint: data_dcmlpoint.disable()
                            };
                    }
                }
            }
        });
        return dataType;
    },
    /**
     * 소분류 콤보박스 스토어 로드 함수
     * @param object sclasl 소분류 필드
     * @param int mlsfcId 중분류의 선택된 값 record ID
     */
    _inSclaslComboBoxLoad: function (_this, sclasl, mlsfcId) {
        sclasl.getStore().on("load", function (store, r) {

            if (_this.type == 'edit') {
                var domainRecord = _this.getRecord.data;
                if (!(domainRecord.domain_sclas == null)) {
                    sclasl.setValue(domainRecord.domain_sclas.code_itm_code);
                }
            } else {
                if (!(r.length == 0)) {
                    sclasl.setValue(r[0].data.code_itm_code);
                }
            }

            if (!(_this.type == 'edit')) {
                // 수정상태가 아닐때
            } else {
                var setComboValue = [];
                Ext.each(Object.keys(_this.getRecord.data), function (r1, idx1, e) {
                    var objectKey = _this.getRecord.data[r1];
                    Ext.each(objectKey, function (r2, idx2, e) {
                        if (typeof r2 == 'object') {
                            if (r2.hasOwnProperty('code_itm_nm')) {
                                if (self.value === r2.code_itm_code) {
                                    setComboValue[self.name] = r2.code_itm_nm;
                                }
                            }
                        }
                    });
                });

                // if (Array.isArray(setComboValue)) {

                //     sclasl.setValue(setComboValue[sclasl.name]);
                // }

            }
            if (!(r.length == 0)) {
                sclasl.enable();
            } else {
                sclasl.disable();
                sclasl.setValue(null);
            }
        });

        sclasl.getStore().on("exception", function (self, type, action, opts, response, args) {
            try {
                var r = Ext.decode(response.responseText, true);

                if (!r.success) {
                    Ext.Msg.alert(_text('MN00023'), r.msg);
                }
            }
            catch (e) {
                Ext.Msg.alert(_text('MN00023'), r.msg);
            }

        });
        return sclasl.getStore().load({
            params: {
                is_code: 1,
                parnts_id: mlsfcId
            }
            // callback: function (r, o, success) {
            //     if (success) {
            //         try {

            //             if (_this.type == 'edit') {
            //                 var domainRecord = _this.getRecord.data;
            //                 if (!(domainRecord.domain_sclas == null)) {
            //                     sclasl.setValue(domainRecord.domain_sclas.code_itm_code);
            //                 }
            //             } else {
            //                 if (!(r.length == 0)) {
            //                     sclasl.setValue(r[0].data.code_itm_code);
            //                 }
            //             }

            //             if (!(_this.type == 'edit')) {
            //                 // 수정상태가 아닐때
            //             } else {
            //                 var setComboValue = [];
            //                 Ext.each(Object.keys(_this.getRecord.data), function (r1, idx1, e) {
            //                     var objectKey = _this.getRecord.data[r1];
            //                     Ext.each(objectKey, function (r2, idx2, e) {
            //                         if (typeof r2 == 'object') {
            //                             if (r2.hasOwnProperty('code_itm_nm')) {
            //                                 if (self.value === r2.code_itm_code) {
            //                                     setComboValue[self.name] = r2.code_itm_nm;
            //                                 }
            //                             }
            //                         }
            //                     });
            //                 });

            //                 // if (Array.isArray(setComboValue)) {

            //                 //     sclasl.setValue(setComboValue[sclasl.name]);
            //                 // }

            //             }
            //             if (!(r.length == 0)) {
            //                 sclasl.enable();
            //             } else {
            //                 sclasl.disable();
            //             }
            //         } catch (e) {
            //             Ext.Msg.alert(e['name'], e['message']);
            //         }
            //     } else {

            //     }
            // }
        });
    },
    /**
     * 상태값 넣어주는 함수 초기값 REQUEST
     * @param object form  formField 객체
     */
    _addStatusCode: function (form) {
        var _this = this;
        var status = new Ext.data.JsonStore({
            restful: true,
            proxy: new Ext.data.HttpProxy({
                method: 'GET',
                // url: '/api/v1/data-dic-code-sets/DD_STATUS/code-items',
                url: Ariel.glossary.UrlSet.codeSetIdParamCodeItems('DD_STATUS'),
                type: 'rest',
            }),
            root: 'data',
            fields: [
                { name: 'code_itm_code', mapping: 'code_itm_code' },
                { name: 'code_itm_nm', mapping: 'code_itm_nm' }
            ],
            listeners: {
                load: function (store, r, option) {
                    if (!(_this.status == undefined)) {
                        Ext.each(r, function (r, idx, e) {
                            if (r.data.code_itm_code == _this.status) {
                                sttusValue = r.data.code_itm_code;
                            };
                        })

                        return form.getForm().findField('sttus_code').setValue(sttusValue);
                    }
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
        });
        status.load({
            params: {
                is_code: 1
            },
            //     callback: function (r, o, success) {

            //         if (success) {
            //             try {
            //                 if (!(_this.status == undefined)) {
            //                     Ext.each(r, function (r, idx, e) {
            //                         if (r.data.code_itm_code == _this.status) {
            //                             sttusValue = r.data.code_itm_code;
            //                         };
            //                     })

            //                     return form.getForm().findField('sttus_code').setValue(sttusValue);
            //                 }
            //             } catch (e) {
            //                 ////     Ext.Msg.alert(e['name'], e['message']);
            //             }
            //         } else {
            //             // Ext.Msg.alert('status: ' + resp.status, resp.statusText);
            //         }
            //     }
        });
    },
    /**
     * FormField 정의 
     */
    _inputFormWord: function () {
        var _this = this;
        var inputForm = new Ext.form.FormPanel({
            defaultType: 'textfield',
            padding: 5,
            defaults: {
                anchor: '95%'
            },
            items: [{
                hidden: true,
                fieldLabel: '도메인ID',
                name: 'domn_id'
            },
            _this._inCodeComboBox('word_se', '시스템', 'DD_WORD_SE'),
            {
                // WORD_NM
                name: 'word_nm',
                allowBlank: false,
                fieldLabel: '용어명',
                emptyText: '용어명',
            }, {
                // WORD_ENG_ABRV_NM
                xtype: 'textfield',
                allowBlank: false,
                fieldLabel: '영문약어명',
                name: 'word_eng_abrv_nm',
                emptyText: '영문약어명'
            }, {
                // WORD_ENG_NM
                xtype: 'textfield',
                allowBlank: false,
                fieldLabel: '영문정식명',
                name: 'word_eng_nm',
                emptyText: '영문정식명',
            },
            _this._searchField('domain'),
            {
                hidden: true,
                allowBlank: false,
                xtype: 'textfield',
                fieldLabel: '상태',
                name: 'sttus_code'
            }, {
                // SPECIFICATION
                xtype: 'textarea',
                fieldLabel: '설명',
                name: 'dc',
                emptyText: '설명'
            }],
            listeners: {
                afterrender: function (self) {
                    _this._addStatusCode(self);
                }
            }
        });
        return inputForm;
    },
    _inputFormCodeItem: function () {
        var _this = this;
        var inputForm = new Ext.form.FormPanel({
            defaultType: 'textfield',
            padding: 5,
            defaults: {
                anchor: '95%'
            },
            items: [{
                hidden: true,
                xtype: 'numberfield',
                fieldLabel: 'code_set_id',
                name: 'code_set_id',
                value: _this.code_set_id

            }, {
                fieldLabel: '유효값',
                allowBlank: false,
                name: 'code_itm_code'
            }, {
                fieldLabel: '유효값명',
                allowBlank: false,
                name: 'code_itm_nm'
            }, {
                xtype: 'radiogroup',
                fieldLabel: '사용 여부',
                name: 'use_yn',
                allowBlank: false,
                items: [
                    { boxLabel: 'Y', name: 'use_yn', inputValue: 'Y', checked: true },
                    { boxLabel: 'N', name: 'use_yn', inputValue: 'N' }
                ]
            }, {
                hidden: true,
                xtype: 'numberfield',
                fieldLabel: 'dp',
                name: 'dp',
                value: 1
            }, {
                hidden: true,
                fieldLabel: 'codePath',
                name: 'code_path',
                value: '/0'
            }, {
                xtype: 'textarea',
                fieldLabel: '설명',
                name: 'dc'
            }
            ],
            listeners: {
                afterrender: function (self) {

                    var parentComboBox = new Ext.form.ComboBox({
                        allowBlank: false,
                        name: 'parnts_id',
                        hiddenName: 'parnts_id',
                        editable: false,
                        mode: "local",
                        fieldLabel: '부모코드',
                        displayField: 'code_itm_nm',
                        valueField: 'id',
                        hiddenValue: 'id',
                        typeAhead: true,
                        // beforeValue: '',
                        triggerAction: 'all',
                        lazyRender: true,
                        store: new Ext.data.JsonStore({
                            restful: true,
                            proxy: new Ext.data.HttpProxy({
                                method: 'GET',
                                url: Ariel.glossary.UrlSet.codeSetIdParamCodeItems('DD_DOMN_CL'),
                                type: 'rest'
                            }),
                            root: 'data',
                            fields: [
                                { name: 'code_itm_code', mapping: 'code_itm_code' },
                                { name: 'code_itm_nm', mapping: 'code_itm_nm' },
                                { name: 'id', mapping: 'id' },
                                { name: 'dp', mapping: 'dp' },
                                { name: 'code_path', mapping: 'code_path' }
                            ],
                            listeners: {
                                load: function (store, r, option) {
                                    var self = _this.inputForm.getForm().findField('parnts_id');

                                    if (!(_this.type == 'edit')) {
                                        /**
                                         * 코드 분류는 초기값 없음
                                         */
                                        self.setValue(null);
                                    } else {
                                        var setComboValue = [];
                                        Ext.each(Object.keys(_this.getRecord.data), function (r1, idx1, e) {
                                            var objectKey = _this.getRecord.data[r1];
                                            Ext.each(objectKey, function (r2, idx2, e) {
                                                if (typeof r2 == 'object') {
                                                    if (r2.hasOwnProperty('code_itm_nm')) {
                                                        if (self.value === r2.code_itm_code) {
                                                            setComboValue[self.name] = r2.code_itm_code;
                                                        }
                                                    }
                                                }
                                            });
                                        });

                                        if (Array.isArray(setComboValue)) {
                                            self.setValue(setComboValue[self.name]);
                                        }
                                    }
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
                                    },
                                    // callback: function (r, o, success) {
                                    //     if (success) {
                                    //         try {
                                    //             var firstValue = r[0].data.code_itm_code;
                                    //             var form = _this.inputForm.getForm();
                                    //             if (!(_this.type == 'edit')) {
                                    //                 /**
                                    //                  * 코드 분류는 초기값 없음
                                    //                  */
                                    //                 self.setValue(null);
                                    //             } else {
                                    //                 var setComboValue = [];
                                    //                 Ext.each(Object.keys(_this.getRecord.data), function (r1, idx1, e) {
                                    //                     var objectKey = _this.getRecord.data[r1];
                                    //                     Ext.each(objectKey, function (r2, idx2, e) {
                                    //                         if (typeof r2 == 'object') {
                                    //                             if (r2.hasOwnProperty('code_itm_nm')) {
                                    //                                 if (self.value === r2.code_itm_code) {
                                    //                                     setComboValue[self.name] = r2.code_itm_code;
                                    //                                 }
                                    //                             }
                                    //                         }
                                    //                     });
                                    //                 });

                                    //                 if (Array.isArray(setComboValue)) {
                                    //                     self.setValue(setComboValue[self.name]);
                                    //                 }
                                    //             }
                                    //         } catch (e) {
                                    //             // //     Ext.Msg.alert(e['name'], e['message']);
                                    //         }
                                    //     } else {
                                    //         // Ext.Msg.alert('status: ' + resp.status, resp.statusText);
                                    //     }
                                    // }
                                });
                            },
                            select: function (self, record, idx) {
                                var r = record.data;
                                var setData = {
                                    id: r.id,
                                    dp: Number(r.dp) + 1,
                                    code_path: r.code_path
                                };
                                _this.inputForm.getForm().setValues(setData);
                            }
                        }
                    });
                    var parentDisplay = new Ext.form.DisplayField({
                        fieldLabel: '부모코드',
                        listeners: {
                            afterrender: function (self) {
                                if (_this.getRecord) {
                                    self.setValue(_this.getRecord.data.code_itm_nm);
                                }

                            }
                        }
                    });
                    var parntIdField = new Ext.form.TextField({
                        fieldLabel: '부모코드',
                        name: 'parnts_id',
                        hidden: true,
                        listeners: {
                            afterrender: function (self) {
                                var r = _this.getRecord.json;
                                if (_this.getRecord == null) {
                                    self.setValue(0);
                                } else {

                                    var setData = {
                                        id: r.id,
                                        dp: Number(r.dp) + 1,
                                        code_path: r.code_path,
                                        parnts_id: r.id
                                    };

                                    _this.inputForm.getForm().setValues(setData);
                                };
                            }
                        }
                    });
                    var parentRadioGroup = {
                        xtype: 'radiogroup',
                        fieldLabel: '최상위 여부',
                        name: 'root',
                        forceSelection: true,
                        allowBlank: false,
                        items: [{
                            boxLabel: 'Y',
                            checked: true,
                            name: 'root',
                            inputValue: 'Y',
                            listeners: {
                                check: function (self, checked) {
                                    if (checked) {
                                        inputForm.getForm().findField('parnts_id').disable();
                                        inputForm.getForm().findField('dp').setValue(1);
                                        inputForm.getForm().findField('code_path').setValue('/0');
                                        parentComboBox.setValue(null);
                                    } else {
                                        inputForm.getForm().findField('parnts_id').enable();
                                    }
                                }
                            }
                        },
                        { boxLabel: 'N', name: 'root', inputValue: 'N', }
                        ],
                        listeners: {
                            afterrender: function (self) {
                                if (self.items.get(0).checked) {
                                    inputForm.getForm().findField('parnts_id').disable();
                                }

                            }
                        }
                    };

                    if ((_this.type == "edit")) {

                        Ext.Ajax.request({
                            method: 'GET',
                            url: Ariel.glossary.UrlSet.codeItemIdParam(_this.getRecord.id),
                            callback: function (opts, success, resp) {
                                if (success) {
                                    try {
                                        var codeItemRecord = Ext.decode(resp.responseText).data;
                                        if (!(codeItemRecord.parnts_id == null)) {
                                            if (codeItemRecord.parnts_id == 0) {
                                                parentRadioGroup.items[0].checked = true;
                                                parentRadioGroup.items[1].checked = false;
                                            } else {
                                                parentRadioGroup.items[0].checked = false;
                                                parentRadioGroup.items[1].checked = true;
                                            };

                                            var codeSetCl = Ext.decode(resp.responseText).data.code_set_cl;

                                            // if (codeSetCl == 'HIERARCHY' && !(_this.getRecord == null)) {

                                            //     self.insert(0, parentRadioGroup);
                                            //     self.insert(1, parentComboBox);

                                            //     self.doLayout();
                                            // }
                                            Ext.Ajax.request({
                                                method: 'GET',
                                                url: Ariel.glossary.UrlSet.codeItemIdParam(codeItemRecord.parnts_id),
                                                callback: function (opts, success, resp) {
                                                    var parentId = Ext.decode(resp.responseText).data.id;
                                                    parentComboBox.setValue(parentId);
                                                }
                                            })
                                        };

                                    } catch (e) {
                                        Ext.Msg.alert(e['name'], e['message']);
                                    }
                                } else {
                                    Ext.Msg.alert('status: ' + resp.status, resp.statusText);
                                }

                            }

                        })

                    } else {
                        Ext.Ajax.request({
                            url: Ariel.glossary.UrlSet.codeSetIdParam(_this.code_set_id),
                            method: 'GET',
                            callback: function (opts, success, resp) {
                                if (success) {
                                    try {

                                        var codeSetCl = Ext.decode(resp.responseText).data.code_set_cl;
                                        // self.insert(0, parentRadioGroup);
                                        if (codeSetCl == 'HIERARCHY' && !(_this.getRecord == null)) {


                                            self.insert(0, parentDisplay);
                                            // self.insert(1, parentComboBox);
                                            self.insert(1, parntIdField);

                                            self.doLayout();
                                        }
                                    }
                                    catch (e) {
                                        Ext.Msg.alert(e['name'], e['message']);
                                    }
                                } else {
                                    Ext.Msg.alert('status: ' + resp.status, resp.statusText);
                                }
                            }
                        });
                    };

                }
            }
        });
        return inputForm;
    },
    _inputFormCodeSet: function () {
        var _this = this;
        var inputForm = new Ext.form.FormPanel({
            defaultType: 'textfield',
            padding: 5,
            defaults: {
                anchor: '95%'
            },
            items: [
                _this._inCodeComboBox('code_set_cl', '분류 정보', 'DD_CDSET_CL'),
                {
                    fieldLabel: '코드명',
                    allowBlank: false,
                    name: 'code_set_nm'
                }, {
                    fieldLabel: '코드영문명',
                    allowBlank: false,
                    name: 'code_set_code'
                }, {
                    xtype: 'textarea',
                    fieldLabel: '설명',
                    name: 'dc'
                }]
        });
        return inputForm;
    },
    _inputFormTable: function () {
        var _this = this;
        var inputForm = new Ext.form.FormPanel({
            defaultType: 'textfield',
            padding: 5,
            defaults: {
                anchor: '95%'
            },
            items: [
                _this._inCodeComboBox('sys_code', '시스템', 'DD_SYSTEM'),
                {
                    fieldLabel: '테이블명',
                    allowBlank: false,
                    name: 'table_nm'
                }, {
                    fieldLabel: '테이블영문명',
                    allowBlank: false,
                    name: 'table_eng_nm'
                },
                _this._inCodeComboBox('table_se', '속성', 'DD_TABLE_SE'),
                {
                    hidden: true,
                    fieldLabel: '상태 코드',
                    allowBlank: false,
                    name: 'sttus_code'
                }, {
                    fieldLabel: '설명',
                    name: 'dc'
                }
            ],
            listeners: {
                afterrender: function (self) {
                    _this._addStatusCode(self);
                }
            }
        });
        return inputForm;
    },
    _inputFormColumn: function () {
        var _this = this;
        var inputForm = new Ext.form.FormPanel({
            defaultType: 'textfield',
            padding: 5,
            defaults: {
                anchor: '95%'
            },
            items: [
                {
                    xtype: 'radiogroup',
                    name: 'std_yn',
                    items: [{
                        boxLabel: '표준정보',
                        name: 'std_yn',
                        inputValue: 'Y',
                        checked: true,
                        listeners: {
                            check(self, checked) {
                                /**
                                 * getForm() ->한뒤에 findField로 바꿔서 배열 없애기
                                 */
                                var form = _this.inputForm;
                                if (checked) {
                                    form.getForm().findField('column_nm').setReadOnly(true);
                                    form.getForm().findField('column_eng_nm').setReadOnly(true);

                                    form.getForm().findField('domn_nm').show();
                                    form.getForm().findField('data_ty').hide(true);
                                    form.getForm().findField('data_lt').hide(true);
                                    _this.syncSize();

                                }
                            }
                        }
                    }, {
                        boxLabel: '비표준정보',
                        name: 'std_yn',
                        inputValue: 'N',
                        listeners: {
                            check(self, checked) {
                                var form = _this.inputForm;
                                if (checked) {
                                    // form.find('name', 'column_nm')[0].setReadOnly(false);
                                    // form.find('name', 'column_eng_nm')[0].setReadOnly(false);

                                    // form.find('name', 'domn_nm')[0].hide(true);
                                    // form.find('name', 'data_ty')[0].show();
                                    // form.find('name', 'data_lt')[0].show();
                                    form.getForm().findField('column_nm').setReadOnly(false);
                                    form.getForm().findField('column_eng_nm').setReadOnly(false);

                                    form.getForm().findField('domn_nm').hide(true);
                                    form.getForm().findField('data_ty').show();
                                    form.getForm().findField('data_lt').show();

                                    _this.syncSize();
                                }
                            }
                        }
                    }],
                },
                _this._searchField('field'),
                {
                    fieldLabel: '컬럼명',
                    readOnly: true,
                    allowBlank: false,
                    name: 'column_nm',
                }, {

                    fieldLabel: '컬럼ID',
                    readOnly: true,
                    allowBlank: false,
                    name: 'column_eng_nm'
                }, {
                    fieldLabel: '도메인명',
                    readOnly: true,
                    name: 'domn_nm'
                }, {
                    hidden: true,
                    xtype: 'numberfield',
                    fieldLabel: 'table_id',
                    name: 'table_id',
                    value: _this.table_id
                }, {
                    hidden: true,
                    xtype: 'numberfield',
                    fieldLabel: 'field_id',
                    name: 'field_id'
                }, _this._dataTypeComboBoxField(_this, true),
                {
                    xtype: 'numberfield',
                    hidden: true,
                    fieldLabel: '데이터 길이',
                    name: 'data_lt'
                }, {
                    hidden: true,
                    fieldLabel: 'data_dcmlpoint',
                    name: 'data_dcmlpoint',
                    value: 'Y'
                }, {
                    xtype: 'radiogroup',
                    fieldLabel: 'PK 여부',
                    name: 'pk_yn',
                    allowBlank: false,
                    items: [
                        { boxLabel: 'Y', name: 'pk_yn', inputValue: 'Y' },
                        { boxLabel: 'N', name: 'pk_yn', inputValue: 'N', checked: true }
                    ],
                }, {
                    xtype: 'radiogroup',
                    fieldLabel: 'Not Null',
                    name: 'nn_yn',
                    allowBlank: false,
                    items: [
                        { boxLabel: 'Y', name: 'nn_yn', inputValue: 'Y' },
                        { boxLabel: 'N', name: 'nn_yn', inputValue: 'N', checked: true }
                    ],
                }, {
                    hidden: true,
                    xtype: 'numberfield',
                    fieldLabel: '순서',
                    name: 'ordr',
                    value: '1'
                }, {
                    hidden: true,
                    fieldLabel: 'sttus_code',
                    name: 'sttus_code',
                }, {
                    xtype: 'textarea',
                    fieldLabel: '설명',
                    name: 'dc'
                }
            ],
            listeners: {
                afterrender: function (self) {
                    _this._addStatusCode(self);
                }
            }

        });

        return inputForm;
    },
    _inputFormDomain: function () {
        var _this = this;
        var inputForm = new Ext.form.FormPanel({
            defaultType: 'textfield',
            padding: 5,
            defaults: {
                anchor: '95%'
            },
            items: [
                _this._searchField('code'),
                {
                    xtype: 'combo',
                    allowBlank: false,
                    name: 'domn_mlsfc',
                    hiddenName: 'domn_mlsfc',
                    editable: false,
                    mode: "local",
                    fieldLabel: '중분류',
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
                            url: Ariel.glossary.UrlSet.codeSetIdParamCodeItems('DD_DOMN_CL'),
                            type: 'rest'
                        }),
                        root: 'data',
                        fields: [
                            { name: 'code_itm_code', mapping: 'code_itm_code' },
                            { name: 'code_itm_nm', mapping: 'code_itm_nm' }
                        ],
                        listeners: {
                            load: function (store, r, option) {
                                var self = _this.inputForm.getForm().findField('domn_mlsfc');
                                if (!(_this.type == 'edit')) {
                                    /**
                                     * 추가 버튼시 처음으로 들어갈 값 설정 안함
                                     */
                                    // self.setValue(r[0].data.code_itm_code);

                                } else {

                                    var setComboValue = [];
                                    Ext.each(Object.keys(_this.getRecord.data), function (r1, idx1, e) {
                                        var objectKey = _this.getRecord.data[r1];
                                        Ext.each(objectKey, function (r2, idx2, e) {
                                            if (typeof r2 == 'object') {
                                                if (r2.hasOwnProperty('code_itm_nm')) {
                                                    if (self.value === r2.code_itm_code) {
                                                        //수정시 소분류 로드
                                                        var sclasl = _this.inputForm.getForm().findField('domn_sclas');
                                                        _this._inSclaslComboBoxLoad(_this, sclasl, r2.id);

                                                        // 수정시 선택했던 값 
                                                        setComboValue[self.name] = r2.code_itm_nm;

                                                    }
                                                }
                                            }
                                        });
                                    });
                                    if (Array.isArray(setComboValue)) {

                                        self.setValue(setComboValue[self.name]);
                                    }

                                }
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
                                    is_code: 1,
                                    dp: 1
                                },
                                // callback: function (r, o, success) {

                                //     if (success) {
                                //         try {
                                //             if (!(_this.type == 'edit')) {
                                //                 /**
                                //                  * 추가 버튼시 처음으로 들어갈 값 설정 안함
                                //                  */
                                //                 // self.setValue(r[0].data.code_itm_code);

                                //             } else {

                                //                 var setComboValue = [];
                                //                 Ext.each(Object.keys(_this.getRecord.data), function (r1, idx1, e) {
                                //                     var objectKey = _this.getRecord.data[r1];
                                //                     Ext.each(objectKey, function (r2, idx2, e) {
                                //                         if (typeof r2 == 'object') {
                                //                             if (r2.hasOwnProperty('code_itm_nm')) {
                                //                                 if (self.value === r2.code_itm_code) {
                                //                                     //수정시 소분류 로드
                                //                                     var sclasl = _this.inputForm.getForm().findField('domn_sclas');
                                //                                     _this._inSclaslComboBoxLoad(_this, sclasl, r2.id);

                                //                                     // 수정시 선택했던 값 
                                //                                     setComboValue[self.name] = r2.code_itm_nm;

                                //                                 }
                                //                             }
                                //                         }
                                //                     });
                                //                 });
                                //                 if (Array.isArray(setComboValue)) {

                                //                     self.setValue(setComboValue[self.name]);
                                //                 }

                                //             }
                                //         } catch (e) {
                                //             ////     Ext.Msg.alert(e['name'], e['message']);
                                //         }
                                //     } else {
                                //         // ////     Ext.Msg.alert(e['name'], e['message']);
                                //         // Ext.Msg.alert('status: ' + resp.status, resp.statusText);
                                //     }
                                // }
                            });


                        },
                        select: function (self, record, idx) {

                            var sclasl = _this.inputForm.getForm().findField('domn_sclas');

                            sclasl.clearValue();

                            _this._inSclaslComboBoxLoad(_this, sclasl, record.id);


                            self.setValue(record.get('code_itm_code'));
                        }
                    }
                },
                // 소분류
                {
                    xtype: 'combo',
                    name: 'domn_sclas',
                    hiddenName: 'domn_sclas',
                    editable: false,
                    mode: "local",
                    fieldLabel: '소분류',
                    displayField: 'code_itm_nm',
                    valueField: 'code_itm_code',
                    hiddenValue: 'code_itm_code',
                    typeAhead: true,
                    triggerAction: 'all',
                    lazyRender: true,
                    store: new Ext.data.JsonStore({
                        restful: true,
                        proxy: new Ext.data.HttpProxy({
                            method: 'GET',
                            url: Ariel.glossary.UrlSet.codeSetIdParamCodeItems('DD_DOMN_CL'),
                            type: 'rest'
                        }),
                        root: 'data',
                        fields: [
                            { name: 'code_itm_code', mapping: 'code_itm_code' },
                            { name: 'code_itm_nm', mapping: 'code_itm_nm' }
                        ]
                    }),
                    listeners: {
                        afterrender: function (self) {
                            // if (_this.type == 'edit') {
                            //     var mlsfc = _this.inputForm.getForm().findField('domn_mlsfc');
                            //     console.log(mlsfc.getValue());
                            // }
                            if (Ext.isEmpty(self.getRawValue())) {
                                self.disable();
                            }
                        },
                        select: function (self, record, idx) {
                            self.setValue(record.get('code_itm_code'));
                        }
                    }
                },
                _this._inCodeComboBox('domn_ty', '유형', 'DD_DOMN_TY'),
                {
                    fieldLabel: '도메인명',
                    allowBlank: false,
                    allowBlank: false,
                    name: 'domn_nm'
                }, {
                    fieldLabel: '도메인영문명',
                    allowBlank: false,
                    allowBlank: false,
                    name: 'domn_eng_nm'
                },
                _this._dataTypeComboBoxField(_this, false),
                {
                    fieldLabel: '데이터길이',
                    name: 'data_lt'
                }, {
                    fieldLabel: '데이터소수점',
                    name: 'data_dcmlpoint'
                }, {
                    hidden: true,
                    fieldLabel: 'sttus_code',
                    name: 'sttus_code'
                }, {
                    xtype: 'textarea',
                    fieldLabel: '설명',
                    name: 'dc'
                }, {
                    hidden: true,
                    xtype: 'numberfield',
                    fieldLabel: '코드 셋 아이디',
                    name: 'code_set_id'
                }
            ],
            listeners: {
                afterrender: function (self) {


                    _this._addStatusCode(self);

                    // form.findField('searchfield').disable();
                    // _this.inputForm.find('name', 'domn_nm')[0].setReadOnly(true);
                    // _this.inputForm.find('name', 'domn_eng_nm')[0].setReadOnly(true);
                }
            }
        });
        return inputForm;
    },
    _inputFormField: function () {
        var _this = this;
        var inputForm = new Ext.form.FormPanel({
            defaultType: 'textfield',
            padding: 5,
            defaults: {
                anchor: '95%'
            },
            items: [{
                hidden: true,
                fieldLabel: '도메인ID',
                name: 'domn_id'
            }, {
                fieldLabel: '필드명',
                name: 'field_nm',
                allowBlank: false,
                listeners: {
                    specialkey: function (field, e) {
                        var _this = this;
                        if (e.getKey() == e.ENTER) {
                            // 공백 key
                            var keyword = field.getValue();
                            if (Ext.isEmpty(keyword)) {
                                return false;
                            }
                            inputForm.remove(inputForm.find('name', 'listView')[0]);
                            inputForm.getForm().findField('field_eng_nm').reset();

                            var listView = new Ext.list.ListView({
                                height: 120,
                                name: 'listView',
                                simpleSelect: true,
                                singleSelect: true,
                                store: {
                                    xtype: 'arraystore',
                                    fields: [
                                        'word'
                                    ]
                                },
                                columns: [
                                    { header: '조회 결과', dataIndex: 'word' }
                                ],
                                listeners: {
                                    click: function (self, index, node) {
                                        var clickRecords = self.getSelectedRecords();
                                        var selWord = clickRecords[0].get('word');

                                        inputForm.getForm().findField('field_eng_nm').setValue(selWord);
                                    }
                                }
                            });

                            Ext.Ajax.request({
                                //method: 'GET',
                                url: '/api/v1/data-dic-words/search',
                                params: {
                                    keyword: keyword
                                },
                                callback: function (opts, success, response) {
                                    if (success) {
                                        try {
                                            var result = Ext.decode(response.responseText);
                                            if (result.success) {
                                                var wordsList = result.data;

                                                var wordCnt = wordsList.length;

                                                //var wordsList = [['1111'],['2222'],['3333']];                       

                                                if (wordCnt == 1) {
                                                    inputForm.getForm().findField('field_eng_nm').setValue(wordsList[0]);
                                                } else {
                                                    var dataList = [];
                                                    for (var i = 0; i < wordsList.length; i++) {
                                                        dataList.push([wordsList[i]]);
                                                    }
                                                    listView.getStore().loadData(dataList);
                                                    inputForm.insert(3, listView);
                                                    inputForm.doLayout();
                                                }

                                            } else {
                                                return Ext.Msg.show({
                                                    title: '알림',
                                                    msg: result.msg,
                                                    buttons: {
                                                        ok: "확인"
                                                        //, yes: "용어추가" 
                                                    },
                                                    fn: function (btnId, text, opts) {
                                                        if (btnId == 'yes') {
                                                            new Ariel.glossary.inputFormWindow({
                                                                title: '추가',
                                                                type: 'add',
                                                                action: 'word',
                                                                method: 'POST',
                                                                apiUrl: '/api/v1/data-dic-words',
                                                                onAfterSave: function () {
                                                                    //추가
                                                                }
                                                            }).show();
                                                        }
                                                    }
                                                });
                                            }
                                        }
                                        catch (e) {
                                            ////     Ext.Msg.alert(e['name'], e['message']);
                                        }
                                    } else {
                                        Ext.Msg.alert(_text('MN01098'), response.statusText + '(' + response.status + ')');
                                    }
                                }
                            });

                        }
                    }
                },
            }, {
                fieldLabel: '필드ID',
                allowBlank: false,
                name: 'field_eng_nm'
            },
            _this._searchField('domain'),
            {
                hidden: true,
                fieldLabel: '상태 코드',
                name: 'sttus_code'
            }, {
                xtype: 'textarea',
                fieldLabel: '설명',
                name: 'dc'
            }],
            listeners: {
                afterrender: function (self) {

                    _this._addStatusCode(self);

                    _this.inputForm.find('name', 'field_eng_nm')[0].setReadOnly(true);

                }
            }
        });
        return inputForm;
    },



















    /**
     * 리스트 뷰 예시
     */
    // _listView: function (inputForm, textValue, full) {
    //     var store = new Ext.data.JsonStore({
    //         restful: true,

    //         proxy: new Ext.data.HttpProxy({
    //             method: 'GET',
    //             url: '/api/v1/data-dic-equals',
    //             type: 'rest',
    //             headers: { 'X-DEV-AUTH': 'admin' }
    //         }),
    //         totalProperty: 'total',
    //         root: 'data[0]',
    //         fields: [
    //             { name: 'id', type: 'float' },
    //             'word_se',
    //             'word_nm',
    //             'word_eng_nm',
    //             'domain',
    //             'word_eng_abrv_nm',

    //             'delete_yn'
    //         ],

    //     });


    //     var listView = new Ext.list.ListView({
    //         name: 'listView',
    //         store: store,
    //         columns: [
    //             { header: 'ID', dataIndex: 'id' },
    //             { header: '구분', dataIndex: 'word_se' },
    //             { header: '용어명', dataIndex: 'word_nm' },
    //             { header: '영문약어명', dataIndex: 'word_eng_abrv_nm' },
    //             { header: '영문정식명', dataIndex: 'word_eng_nm' },
    //             {
    //                 header: '도메인명', dataIndex: 'domain', renderer: function (value, metaData, record, rowIndex, colIndex, store) {
    //                     if (value == null) {
    //                         return null;
    //                     } else {
    //                         return value.domn_nm;
    //                     }
    //                 }
    //             },

    //         ],
    //         listeners: {
    //             afterrender: function (self) {
    //                 // items.find('name', 'listView').
    //                 listView.getStore().load({
    //                     params: {
    //                         keyword: textValue
    //                     }
    //                 });

    //             },
    //             click(self, index, node, e) {
    //                 full = full + self.getStore().data.items[index].data.word_eng_abrv_nm;

    //             }
    //         }
    //     });


    //     inputForm.remove(inputForm.find('name', 'listView')[0]);

    //     inputForm.insert(3, listView);
    //     inputForm.doLayout();
    //     return full;
    // }
});