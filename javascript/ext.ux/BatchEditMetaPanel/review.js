(function () {
    Ext.ns('Ariel');

    Ariel.BatchEditMetaWindow = Ext.extend(Ext.Window, {
        // 선택한 콘텐츠 타이틀
        selectedTitle: null,
        // 선택한 콘텐츠 아이디
        selectedId: null,




        title: '심의 요청',
        width: Ext.getBody().getViewSize().width * 0.4,
        height: Ext.getBody().getViewSize().height * 0.3,
        layout: 'fit',
        initComponent: function () {

            this._initialize();
            Ariel.BatchEditMetaWindow.superclass.initComponent.call(this);
        },
        _initialize: function () {
            var _this = this;
            var inputForm = this._inputForm();
            this.items = inputForm;

        },
        /**
         * 제목,심의자,설명
         * 심의자 검색 기능
         */
        _inputForm: function () {
            var _this = this;
            var inputForm = new Ext.form.FormPanel({
                defaultType: 'textfield',
                padding: 10,
                defaults: {
                    anchor: '95%'
                },
                items: [{
                    fieldLabel: '제목',
                    name: 'title',
                    listeners: {
                        afterrender: function (self) {
                            var contentTitle = _this.selectedTitle;
                            if (!(contentTitle === null)) {
                                self.setValue(contentTitle);
                            }
                        }
                    }
                }, {
                    // xtype: 'number',
                    hidden: true,
                    name: 'content_id',
                    listeners: {
                        afterrender: function (self) {
                            var contentId = _this.selectedId;
                            if (!(contentId === null)) {
                                self.setValue(contentId);
                            }
                        }
                    }
                }, {
                    hidden: true,
                    name: 'review_ty_se',
                    value: 'ingest'
                }, {
                    xtype: 'compositefield',
                    fieldLabel: '심의자',
                    items: [{
                        xtype: 'textfield',
                        readOnly: true,
                        name: 'user_id',
                        felx: 1
                    }, {
                        xtype: 'textfield',
                        readOnly: true,
                        name: 'user_nm',
                        flex: 1
                    }, {
                        text: '검색',
                        xtype: 'button',
                        flex: 1,
                        handler: function (self) {
                            _this._userSelectWindow(inputForm);
                        }
                    }]
                }, {
                    xtype: 'textarea',
                    fieldLabel: '설명',
                    name: 'cn'
                }]
            });
            this.buttons = this._submitButton(inputForm);
            return inputForm;
        },
        /**
         * submit or cancel buttons
         * @param this._inputForm inputForm 
         */
        _submitButton: function (inputForm) {
            var _this = this;
            var form = inputForm.getForm();
            buttons = [{
                xtype: "a-iconbutton",
                text: '의뢰',
                handler: function (btn, e) {
                    if (!form.isValid()) {
                        Ext.Msg.show({
                            title: '알림',
                            msg: '입력되지 않은 값이 있습니다.',
                            buttons: Ext.Msg.OK,
                        });
                        return;
                    }

                    Ext.Ajax.request({
                        method: 'POST',
                        url: '/api/v1/dash-board-reviews',
                        // url:Ariel.DashBoard.Url.reviews,
                        params: {
                            reviewsData: Ext.encode(form.getValues())
                        },
                        callback: function (option, success, response) {

                            if (success) {
                                Ext.Msg.alert('알림', '심의 요청 되었습니다.');
                                _this.close();
                            } else {
                                Ext.Msg.alert(_text('MN00022'), response.statusText + '(' + response.status + ')');
                            }
                        }
                    })

                    // form.submit({
                    //     // method: 'POST',
                    //     url: '/api/v1/dash-board-reviews',
                    //     success: function (form, action) {
                    //         Ext.Msg.alert('성공');
                    //         // Ext.Msg.show({
                    //         //     title: '알림',
                    //         //     msg: _this.title + ' 되었습니다.',
                    //         //     buttons: Ext.Msg.OK,
                    //         //     fn: function (btnId, text, opts) {
                    //         //         if (btnId == 'ok') {

                    //         //             _this.onAfterSave();
                    //         //             _this.close();
                    //         //         };
                    //         //     }
                    //         // });
                    //     },
                    //     failure: function (form, action) {
                    //         // console.log(action);
                    //         // var error = Ext.decode(action.response.responseText);
                    //         Ext.Msg.alert('실패');
                    //     }
                    // })

                }
            }, {
                xtype: "a-iconbutton",
                text: "취소",
                handler: function (btn, e) {
                    _this.close();
                }
            }];
            return buttons;
        },
        /**
         * 심의자 유저 검색 윈도우창
         */
        _userSelectWindow: function (inputForm) {

            var form = inputForm.getForm();
            var components = [
                '/custom/ktv-nps/javascript/ext.ux/Custom.UserSelectWindow.js',
                '/custom/ktv-nps/javascript/ext.ux/components/Custom.UserListGrid.js',
                '/custom/ktv-nps/javascript/api/Custom.Store.js',
                '/javascript/common.js'
            ];

            Ext.Loader.load(components, function (r) {
                new Custom.UserSelectWindow({
                    singleSelect: true,
                    listeners: {
                        ok: function () {
                            var UserSelectWindow = this;
                            var selectedUser = UserSelectWindow._selected.data;
                            Ext.Msg.show({
                                title: "알림",
                                msg: "선택하시겠습니까?",
                                buttons: Ext.Msg.OKCANCEL,
                                fn: function (btnId, text, opts) {
                                    if (btnId == "ok") {
                                        form.setValues(selectedUser);
                                        UserSelectWindow.close();
                                    }
                                }
                            });


                        }
                    }
                }).show();
            });
        }
    });
    return new Ariel.BatchEditMetaWindow();
})()