(function () {
    Ext.ns('Ariel.DashBoard');

    Ariel.DashBoard.Personalization = Ext.extend(Ext.Panel, {
        title: '<span class="user_span"><span class="icon_title"><i class=""></i></span><span class="main_title_header">' + '개인설정' + '</span></span>',
        cls: 'grid_title_customize proxima_customize',

        listeners: {
            afterrender: function (self) {
                Ext.Ajax.request({
                    url: '/store/get_myInfo.php',
                    success: function (response, opts) {
                        try {
                            var r = Ext.decode(response.responseText);
                            self.myInfo = r.data;

                            // 브라우저 설정 폼
                            var browserForm = self.browserConfig.get(0).getForm();
                            var topMenuModeRadioGroup = browserForm.findField('top_menu_mode');
                            var actionIconSlide = browserForm.findField('action_icon_slide');

                            // setvalue
                            browserForm.setValues(self.myInfo);

                            // 탑 매뉴 checked
                            (self.myInfo.user_top_menu == 'B') ?
                                topMenuModeRadioGroup.onSetValue(topMenuModeRadioGroup.items.items[0], true) :
                                topMenuModeRadioGroup.onSetValue(topMenuModeRadioGroup.items.items[1], true);
                            // 아이콘 리스트 checked
                            (self.myInfo.action_icon_slide_yn == 'Y') ?
                                actionIconSlide.onSetValue(actionIconSlide.items.items[0], true) :
                                actionIconSlide.onSetValue(actionIconSlide.items.items[1], true);

                            var infoChangeForm = self.userInfoChangeForm.get(0).getForm();
                            infoChangeForm.setValues(self.myInfo);


                        } catch (e) {
                            Ext.Msg.alert(_text('MN00022'), e);
                        }
                    }
                });

            }
        },
        initComponent: function () {

            this._initialize();
            Ariel.DashBoard.Personalization.superclass.initComponent.call(this);
        },
        _initialize: function () {
            var _this = this;

            this.browserConfig = this._browserConfig();
            var passwordChangeForm = this._passwordChangeForm();
            this.userInfoChangeForm = this._userInfoChangeForm();

            var tabPanel = new Ext.TabPanel({
                border: false,
                activeTab: 0,
                items: [{
                    title: '브라우저 설정',
                    items: [
                        _this.browserConfig,
                        {
                            xtype: 'a-iconbutton',
                            scale: 'large',
                            text: '저장',
                            handler: function (self) {
                                _this._onBrowserConfig(_this.myInfo, _this.browserConfig);
                            }
                        }
                    ]
                }, {
                    title: '비밀번호 변경',
                    items: [
                        passwordChangeForm,
                        {
                            xtype: 'a-iconbutton',
                            scale: 'large',
                            text: '적용',
                            handler: function (self) {
                                _this._onChangePassword(_this.myInfo, passwordChangeForm);
                            }
                        }
                    ]

                },{
                    title: '개인정보 변경',
                    items: [
                        _this.userInfoChangeForm,
                        {
                            xtype: 'a-iconbutton',
                            scale: 'large',
                            text: '적용',
                            handler: function (self) {
                                _this._onChangeUserInfo(_this.myInfo, _this.userInfoChangeForm);
                            }
                        }
                    ]

                }]
            });

            this.items = tabPanel;

        },
        /**
         * 브라우저 설정 변경 폼
         */
        _browserConfig: function () {
            var _this = this;

            return new Ext.form.FieldSet({
                items: new Ext.form.FormPanel({
                    width: 350,
                    border: false,
                    items: [
                        new Ext.form.ComboBox({
                            fieldLabel: _text('MN02513'),
                            name: 'first_page',
                            hiddenName: 'first_page',
                            typeAhead: true,
                            triggerAction: 'all',
                            lazyRender: true,
                            mode: 'local',
                            store: new Ext.data.ArrayStore({
                                // fields: [
                                //     'code_itm_code',
                                //     'code_itm_nm'
                                // ],
                                fields: ['name', 'value'],
                                data: [[_text('MN00096'), 'media'], [_text('MN00311'), 'home']]
                            }),
                            // valueField: 'code_itm_code',
                            // displayField: 'code_itm_nm'
                            hiddenValue: 'value',
                            displayField: 'name',
                            valueField: 'value'

                        }),
                        new Ext.form.RadioGroup({
                            fieldLabel: '메뉴 형태',
                            fieldLabel: _text('MN02319'),
                            name: 'top_menu_mode',
                            allowBlank: false,
                            items: [
                                { boxLabel: _text('MN02320'), name: 'top_menu_mode', inputValue: 'B', checked: true },
                                { boxLabel: _text('MN02321'), name: 'top_menu_mode', inputValue: 'S' }
                            ]
                        }),
                        new Ext.form.RadioGroup({
                            hidden: true,
                            fieldLabel: _text('MN02379'),
                            name: 'action_icon_slide',
                            allowBlank: false,
                            items: [
                                { boxLabel: _text('MN00001'), name: 'action_icon_slide', inputValue: 'Y', checked: true },
                                { boxLabel: _text('MN00002'), name: 'action_icon_slide', inputValue: 'N' }
                            ]
                        })
                    ]
                })
            });
        },
        /**
         * 비밀번호 변경 폼
         */
        _passwordChangeForm: function () {
            var asterisk = '<span style="color: #f20606;" >*</span>';
            return new Ext.form.FieldSet({
                items: new Ext.form.FormPanel({
                    border: false,
                    items: [{
                        xtype: 'textfield',
                        fieldLabel: asterisk + '비밀번호',
                        name: 'password_0',
                        inputType: 'password'
                    }, {
                        xtype: 'textfield',
                        fieldLabel: asterisk + '새 비밀번호',
                        name: 'password_1',
                        inputType: 'password'
                    }, {
                        xtype: 'textfield',
                        fieldLabel: asterisk + '비밀번호 확인',
                        name: 'password_2',
                        inputType: 'password'
                    }]
                })
            });
        },
        /**
         * 새 비밀번호와 비밀번호 확인을 비교해서 일치할때 check 리턴 아니면 re 리턴
         * @param string user_id 
         * @param string value1 
         * @param string value2 
         * @param string check_pw 
         */
        _checkPassword: function (user_id, value1, value2, check_pw) {
            if (Ext.isEmpty(value1)) {
                //Ext.Msg.alert('확인','비밀번호를 입력해 주세요.');
                Ext.Msg.alert(_text('MN00024'), _text('MSG00095'));
                return 're';
            } else if (Ext.isEmpty(value2)) {
                //Ext.Msg.alert('확인','비밀번호 확인을 입력해 주세요.');
                Ext.Msg.alert(_text('MN00024'), _text('MSG00096'));
                return 're';
            } else if (value1 != value2) {
                //Ext.Msg.alert('확인','비밀번호 확인을 다시 입력해 주세요.');
                Ext.Msg.alert(_text('MN00024'), _text('MSG00097'));
                return 're';
            } else if (check_pw == 'Y' && user_id != 'admin') {
                if (value1.length < 9) {
                    //Ext.Msg.alert('확인','비밀번호를 9자리 이상으로 입력해 주세요.');
                    Ext.Msg.alert(_text('MN00024'), _text('MSG02034'));
                    return 're';
                } else if (fn_pwCheck(value1) == 're') {
                    Ext.Msg.alert('확인', '비밀번호로 사용할 수 없는 특수문자가 포함되어 있습니다.');
                    return 're';
                } else if (!fn_pwCheck(value1)) {
                    //Ext.Msg.alert('확인','비밀번호는 영문, 숫자, 특수문자를 각각 1개 이상 포함하여야 합니다.');
                    Ext.Msg.alert(_text('MN00024'), _text('MSG02035'));
                    return 're';
                }
                return 'check';
            } else {
                return 'check';
            }
        },
        /**
         * 개인정보 변경 폼
         */
         _userInfoChangeForm: function () {
            var _this = this;
            var asterisk = '<span style="color: #f20606;" >*</span>';
            return new Ext.form.FieldSet({
                items: new Ext.form.FormPanel({
                    border: false,
                    items: [{
                        xtype: 'textfield',
                        fieldLabel: asterisk + 'HP',
                        name: 'phone',
                        regex: /^\d{3}-\d{4}-\d{4}$/,
                        regexText: 'xxx-xxx-xxxx 형식으로 입력해주세요', 
                    },{
                        xtype: 'textfield',
                        fieldLabel: '이메일',
                        name : 'email',
                        regex:/^[0-9a-zA-Z]([-_.]?[0-9a-zA-Z])*@[0-9a-zA-Z]([-_.]?[0-9a-zA-Z])*.[a-zA-Z]{2,3}$/i,
                        regexText: '이메일 형식으로 입력해주세요.'
                    }]
                })
            });
         },
        /**
         *  비밀번호 변경
         * @param object myInfo 
         * @param object passwordChangeForm 
         */
        _onChangePassword: function (myInfo, passwordChangeForm) {
            var user_id = myInfo.user_id;

            var changePasswordForm = passwordChangeForm.get(0).getForm();
            var currentPassword = changePasswordForm.findField('password_0').getValue();
            var newPassword = changePasswordForm.findField('password_1').getValue();
            var newPasswordCheck = changePasswordForm.findField('password_2').getValue();

            var waitPopup = Ext.Msg.wait('비밀번호를 변경중입니다.', '변경중...');
            var check_pw = this._checkPassword(myInfo.user_id, newPassword, newPasswordCheck, myInfo.check_pw);

            if (check_pw == 'check') {
                Ext.Ajax.request({
                    method: 'POST',
                    url: Ariel.DashBoard.Url.changePassword,
                    params: {
                        // userId: user_id,
                        current_password: currentPassword,
                        new_password: newPassword
                    },
                    callback: function (opts, success, response) {
                        var r = Ext.decode(response.responseText);
                        waitPopup.hide();
                        try {
                            if (r.success) {

                                Ext.Msg.show({
                                    icon: Ext.Msg.QUESTION,
                                    //>>title: '확인',
                                    title: _text('MN00024'),
                                    //>> msg: '사용자 정보가 변경되었습니다. 다시 로그인 해 주시기 바랍니다.'+'</br>'+' 님 로그아웃 하시겠습니까?',
                                    msg: _text('MSG02054') + ' ' + _text('MSG01038') + '</br>' + myInfo.user_nm + '(' + myInfo.user_id + '), 님 ' + _text('MSG00002'),
                                    buttons: Ext.Msg.OKCANCEL,
                                    fn: function (btnId, text, opts) {
                                        if (btnId == 'cancel') return;

                                        Ext.Ajax.request({
                                            url: '/store/logout.php',
                                            callback: function (opts, success, response) {
                                                if (success) {
                                                    try {
                                                        var r = Ext.decode(response.responseText);

                                                        if (r.success) {
                                                            window.location = '/';
                                                        } else {
                                                            //>>Ext.Msg.alert('오류', r.msg);
                                                            Ext.Msg.alert(_text('MN00022'), r.msg);
                                                        }
                                                    }
                                                    catch (e) {
                                                        //>>Ext.Msg.alert('오류', e+'<br />'+response.responseText);
                                                        Ext.Msg.alert(_text('MN00022'), e + '<br />' + response.responseText);
                                                    }
                                                } else {
                                                    //>>Ext.Msg.alert('오류', response.statusText);
                                                    Ext.Msg.alert(_text('MN00022'), response.statusText);
                                                }
                                            }
                                        })
                                    }
                                });
                            } else {
                                if (r.msg) {
                                    Ext.Msg.show({
                                        title: _text('MN00024'),//'확인'
                                        msg: r.msg,
                                        buttons: Ext.Msg.OK
                                    });
                                }
                            }
                        } catch (e) {
                            Ext.Msg.alert(e['name'], e['message']);
                        }
                    }
                })
            }

        },
        /**
         * 브라우저 설정 변경
         * @param object myInfo 
         * @param object browserConfig 
         */
        _onBrowserConfig: function (myInfo, browserConfig) {
            var user_id = myInfo.user_id;

            var browserConfigForm = browserConfig.get(0).getForm();
            var first_page = browserConfigForm.findField('first_page').getValue();
            var top_menu_mode = browserConfigForm.findField('top_menu_mode').getValue().inputValue;
            var action_icon_slide = browserConfigForm.findField('action_icon_slide').getValue().inputValue;

            Ext.Ajax.request({
                method: 'POST',
                url: Ariel.DashBoard.Url.option,
                params: {
                    userId: user_id,
                    first_page: first_page,
                    top_menu_mode: top_menu_mode,
                    action_icon_slide_yn: action_icon_slide,
                },
                callback: function (opts, success, response) {
                    var r = Ext.decode(response.responseText);
                    try {
                        if (r.success) {
                            Ext.Msg.show({
                                icon: Ext.Msg.QUESTION,
                                //>>title: '확인',
                                title: _text('MN00024'),
                                //>> msg: '사용자 정보가 변경되었습니다. 다시 로그인 해 주시기 바랍니다.'+'</br>'+' 님 로그아웃 하시겠습니까?',
                                msg: _text('MSG02054') + ' ' + '페이지를 새로고침 해 주시기 바랍니다.' + '</br>' + myInfo.user_nm + '(' + myInfo.user_id + '), 님 ' + '새로고침 하시겠습니까?',
                                buttons: Ext.Msg.OKCANCEL,
                                fn: function (btnId, text, opts) {
                                    if (btnId == 'cancel') return;
                                    location.reload(true);
                                }
                            });
                        }
                    } catch (e) {
                        Ext.Msg.alert(e['name'], e['message']);
                    }
                }
            });
        },
        /**
         * 개인정보 변경
         * @param object myInfo 
         * @param object userInfoChangeForm 
         */
        _onChangeUserInfo: function (myInfo, userInfoChangeForm) {
            var userInfoForm = userInfoChangeForm.get(0).getForm();
            var phoneField = userInfoForm.findField('phone');
            var phoneValue = phoneField.getValue();
            var emailField = userInfoForm.findField('email');
            var emailValue = emailField.getValue();

            if(Ext.isEmpty(phoneValue.trim()) || !phoneField.isValid()) {
                Ext.Msg.show({
                    icon: Ext.Msg.QUESTION,
                    //>>title: '확인',
                    title: _text('MN00024'),
                    msg: '핸드폰 번호를 입력해 주시기 바랍니다.',
                    buttons: Ext.Msg.OK,
                });
                return;
            } else if(!emailField.isValid()) {
                Ext.Msg.show({
                    icon: Ext.Msg.QUESTION,
                    //>>title: '확인',
                    title: _text('MN00024'),
                    msg: '이메일을 입력해 주시기 바랍니다.',
                    buttons: Ext.Msg.OK,
                });
                return;
            }else {
                Ext.Ajax.request({
                    method: 'POST',
                    url: Ariel.DashBoard.Url.userInfoChange,
                    params: {
                        phone: phoneValue,
                        email: emailValue,
                    },
                    callback: function (opts, success, response) {
                        var r = Ext.decode(response.responseText);
                        try {
                            if (r.success) {
                                Ext.Msg.show({
                                    icon: Ext.Msg.QUESTION,
                                    //>>title: '확인',
                                    title: _text('MN00024'),
                                    msg: '개인정보 변경이 완료되었습니다.',
                                    buttons: Ext.Msg.OK,
                                });
                            }
                        } catch (e) {
                            Ext.Msg.alert(e['name'], e['message']);
                        }
                    }
                });
            }
        }
    });

    // return new Ariel.DashBoard.Personalization();
})()