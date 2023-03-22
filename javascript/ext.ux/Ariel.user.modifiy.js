Ext.ns('Ariel.user.modifiy');
(function () {

    Ariel.user.modifiy = Ext.extend(Ext.Window, {

        initComponent: function (config) {
            var _this = this;

            if (_this.action == 'add') {
                _this.title = _text('MN00126');
                _this.height = 590;
            }
            if (_this.action == 'edit') {
                _this.title = _text('MN00193');
                _this.height = 530;
            }

            Ext.apply(this, config || {});

            if (_this.type == 'passwordChange') {

                _this.items = {
                    cls: 'change_background_panel',
                    xtype: 'form',
                    border: false,
                    // id: 'change_password_form',
                    url: '/store/change_password.php',
                    frame: true,
                    border: false,
                    defaultType: 'textfield',
                    padding: 5,
                    defaults: {
                        anchor: '100%'
                    },
                    items:
                        [{
                            xtype: 'hidden',
                            id: 'user_id',
                            name: 'user_id',
                            value: _this.contents.data.user_id
                        }, {
                            inputType: 'password',
                            name: 'user_password',
                            allowBlank: false,
                            msgTarget: 'under',
                            fieldLabel: _text('MN00185')//'비밀번호'
                        }, {
                            inputType: 'password',
                            name: 'user_password_valid',
                            allowBlank: false,
                            msgTarget: 'under',
                            fieldLabel: _text('MN00187')//'비밀번호 확인'
                        }, {
                            xtype: 'combo',
                            fieldLabel: _text('MN02189'),//'언어 선택'
                            hidden: true,
                            hiddenName: 'lang',
                            hiddenValue: 'value',
                            displayField: 'name',
                            valueField: 'value',
                            typeAhead: true,
                            triggerAction: 'all',
                            lazyRender: true,
                            mode: 'local',
                            value: 'all',
                            editable: false,
                            store: new Ext.data.ArrayStore({
                                fields: ['name', 'value'],
                                data: [['한국어', 'ko'], ['English', 'en']]
                            })
                        }],
                    buttons: [{
                        text: '<span style="position:relative;top:1px;"><i class="fa fa-edit" style="font-size:13px;"></i></span>&nbsp;' + _text('MN00043'),//'저장'
                        scale: 'medium',
                        handler: function () {
                            if (_this.type == 'passwordChange') {

                                var form = _this.items.items[0].getForm();
                                var user = form.getValues();

                                var password_1 = user.user_password;
                                var password_2 = user.user_password_valid;
                                var lang = user.lang;
                                _this.changeInfo('edit', user.user_id, password_1, password_2, '', '', _this, '', '', lang);
                            }
                        }
                    }, {
                        text: '<span style="position:relative;top:1px;"><i class="fa fa-close" style="font-size:13px;"></i></span>&nbsp;' + _text('MN00004'),//'취소'
                        scale: 'medium',
                        handler: function () {
                            _this.close();
                        }
                    }]
                }
            }
            if (_this.type == 'update') {
                Ext.Ajax.request({
                    url: '/store/get_myInfo.php',
                    success: function (response, opts) {
                        try {
                            _this.r = Ext.decode(response.responseText);

                            setValue = _this.items.items[0].getForm().items;

                            // setValue.items[0].setValue(_this.r.data.user_nm+' ('+_this.r.data.user_id+')');
                            // setValue.items[1].setValue(_this.r.data.ori_password);
                            // setValue.items[4].setValue(_this.r.data.email);
                            // setValue.items[5].setValue(_this.r.data.phone);
                            // setValue.items[6].setValue(_this.r.data.lang);
                            // setValue.items[7].setValue(_this.r.data.first_page);
                            var setV = [
                                { user: _this.r.data.user_nm + ' (' + _this.r.data.user_id + ')' },
                                { password_0: _this.r.data.ori_password },
                                { e_mail: _this.r.data.email },
                                { phone: _this.r.data.phone },
                                { lang: _this.r.data.lang },
                                { first_page: _this.r.data.first_page }

                            ];
                            Ext.each(setV, function (el, index, items) {
                                _this.items.items[0].getForm().setValues(el);
                            });

                            (_this.r.data.user_top_menu == 'S') ?
                                setValue.items[8].onSetValue(setValue.items[8].items.items[1], true) :
                                setValue.items[8].onSetValue(setValue.items[8].items.items[0], true);

                            (_this.r.data.action_icon_slide_yn == 'Y') ?
                                setValue.items[9].onSetValue(setValue.items[9].items.items[0], true) :
                                setValue.items[9].onSetValue(setValue.items[9].items.items[1], true);
                        } catch (e) {
                            Ext.Msg.alert(_text('MN00022'), e);
                        }
                    }
                });

                _this.items = [{
                    xtype: 'form',
                    cls: 'change_background_panel',
                    frame: false,
                    width: 300,
                    padding: 10,
                    border: false,
                    region: 'center',
                    buttonAlign: 'center',
                    defaults: {
                        width: 200
                    },
                    items: [{
                        xtype: 'displayfield',
                        fieldLabel: _text('MN00189'),//'사용자'
                        name: 'user'

                    }, {
                        hidden: passwordFieldHidden,
                        xtype: 'textfield',
                        fieldLabel: _text('MN01100'),//'현재 비밀번호' 
                        inputType: 'password',
                        allowBlank: false,
                        id: 'password_0',
                        name: 'password_0'

                    }, {
                        hidden: passwordFieldHidden,
                        xtype: 'textfield',
                        fieldLabel: _text('MN01101'),//'신규 비밀번호'
                        inputType: 'password',
                        allowBlank: false,
                        id: 'password_1'

                    }, {
                        hidden: passwordFieldHidden,
                        xtype: 'textfield',
                        fieldLabel: _text('MN00187'),//'비밀번호 확인'
                        inputType: 'password',
                        allowBlank: false,
                        id: 'password_2'
                    }, {
                        hidden: emailFieldHidden,
                        xtype: 'textfield',
                        fieldLabel: _text('MN02127'),// '이메일'
                        allowBlank: false,
                        id: 'e_mail',
                        name: ' e_mail',
                        vtype: 'email'
                    }, {
                        hidden: phoneFieldHidden,
                        xtype: 'textfield',
                        fieldLabel: _text('MN00333'),//'전화번호'
                        vtype: 'phone',
                        maxLength: 13,
                        id: 'phone',
                        name: 'phone'
                    }, {
                        hidden: languageFieldHidden,
                        xtype: 'combo',
                        fieldLabel: _text('MN02189'),//'언어 선택'
                        id: 'lang',
                        name: 'lang',
                        hiddenName: 'lang',
                        hiddenValue: 'value',
                        displayField: 'name',
                        valueField: 'value',
                        typeAhead: true,
                        triggerAction: 'all',
                        lazyRender: true,
                        mode: 'local',
                        value: 'all',
                        editable: false,
                        store: new Ext.data.ArrayStore({
                            fields: ['name', 'value'],
                            //data: [['한국어', 'ko'], ['English', 'en'], ['日本語', 'ja']]
                            data: [['한국어', 'ko'], ['English', 'en']]
                        })
                    }, {
                        xtype: 'combo',
                        fieldLabel: _text('MN02513'),// 첫 페이지 선택(홈 또는 미디어 검색)
                        id: 'first_page',
                        name: 'first_page',
                        hiddenName: 'first_page',
                        hiddenValue: 'value',
                        displayField: 'name',
                        valueField: 'value',
                        typeAhead: true,
                        triggerAction: 'all',
                        lazyRender: true,
                        mode: 'local',
                        editable: false,
                        store: new Ext.data.ArrayStore({
                            fields: ['name', 'value'],
                            data: [[_text('MN00311'), 'home'], [_text('MN00096'), 'media'],]
                        }),
                    }, {
                        xtype: 'radiogroup',
                        fieldLabel: _text('MN02319'),
                        name: 'top_menu_mode',
                        id: 'top_menu_mode_id',
                        allowBlank: false,
                        items: [
                            { boxLabel: _text('MN02320'), id: 'top_menu_mode_b', name: 'top_menu_mode', inputValue: 'B' },
                            { boxLabel: _text('MN02321'), id: 'top_menu_mode_s', name: 'top_menu_mode', inputValue: 'S' }
                        ],
                    }, {
                        xtype: 'radiogroup',
                        hidden: true,
                        fieldLabel: _text('MN02379'),
                        name: 'action_icon_slide',
                        id: 'action_icon_slide',
                        allowBlank: false,
                        items: [
                            { boxLabel: _text('MN00001'), id: 'action_icon_slide_yes', name: 'action_icon_slide', inputValue: 'Y' },
                            { boxLabel: _text('MN00002'), id: 'action_icon_slide_no', name: 'action_icon_slide', inputValue: 'N' }
                        ],
                    }],
                    buttons: [{
                        text: '<span style="position:relative;top:1px;"><i class="fa fa-edit" style="font-size:13px;"></i></span>&nbsp;' + _text('MN00043'),//'저장'
                        scale: 'medium',
                        handler: function (btnId, text, opts) {
                            _this.onAccept(_this);
                        }
                    }, {
                        text: '<span style="position:relative;top:1px;"><i class="fa fa-close" style="font-size:13px;"></i></span>&nbsp;' + _text('MN00004'),//'취소'
                        //text: _text('MN00004'),
                        scale: 'medium',
                        handler: function () {
                            _this.close();
                        }
                    }]
                }]
            }
            if (_this.type == 'addEdit') {

                _this.items = [{
                    xtype: 'form',
                    border: false,
                    frame: true,
                    padding: '5px',
                    defaultType: 'textfield',
                    flex: 1,
                    autoScroll: true,
                    layoutConfig: {
                        autoScroll: true
                    },
                    defaults: {
                        msgTarget: 'under',
                        autoScroll: true,
                        anchor: '95%'
                    },
                    style: {
                        background: 'white'
                    },
                    id: 'userInfo',
                    items: [{
                        xtype: 'hidden',
                        name: 'member_id',
                        // value: '<?=$user['member_id']?>'
                    }, {
                        allowBlank: false,
                        //>>fieldLabel: '아이디',MN00195
                        fieldLabel: _text('MN00195'),
                        name: 'user_id',
                        maskRe: /[a-zA-Z0-9_]/,
                        vtype: 'alphanum'
                    }, {
                        allowBlank: false,
                        inputType: 'password',
                        //>>fieldLabel: '비밀번호',
                        fieldLabel: _text('MN00185'),
                        name: 'password'
                    }, {
                        allowBlank: false,
                        inputType: 'password',
                        //>>fieldLabel: '비밀번호 확인',
                        fieldLabel: _text('MN00187'),
                        name: 'password_valid'
                    }, {
                        allowBlank: false,
                        //>>fieldLabel: '이 름',
                        fieldLabel: _text('MN00196'),
                        name: 'name'
                    }, {
                        //>>fieldLabel: '부 서',
                        fieldLabel: _text('MN00181'),
                        name: 'dept_nm'
                    }, {
                        //>>fieldLabel: '직 위',
                        fieldLabel: _text('MN00260'),
                        hidden: true,
                        name: 'job_position'
                    }, {
                        //>>fieldLabel: '이메일',
                        fieldLabel: _text('MN02127'),
                        name: 'email'
                    }, {
                        //>>fieldLabel: '전화번호',
                        fieldLabel: _text('MN00333'),
                        name: 'phone',
                        xtype: 'compositefield',
                        items: [{
                            xtype: 'combo',
                            triggerAction: 'all',
                            editable: false,
                            store: ['010', '011', '016', '017', '018', '019'],
                            //xtype : 'textfield',
                            width: 100,
                            name: 'phone1'
                        }, {
                            xtype: 'displayfield',
                            width: 7,
                            value: '-'
                        }, {
                            xtype: 'textfield',
                            width: 100,
                            maxLength: 4,
                            name: 'phone2',
                        }, {
                            xtype: 'displayfield',
                            width: 7,
                            value: '-'
                        }, {
                            xtype: 'textfield',
                            width: 100,
                            maxLength: 4,
                            name: 'phone3'
                        }]
                    }, {
                        xtype: 'combo',
                        fieldLabel: _text('MN02189'),//'언어 선택'
                        hiddenName: 'lang',
                        hiddenValue: 'value',
                        displayField: 'name',
                        valueField: 'value',
                        typeAhead: true,
                        triggerAction: 'all',
                        lazyRender: true,
                        mode: 'local',
                        value: _this.data.user.lang,
                        editable: false,
                        store: new Ext.data.ArrayStore({
                            fields: ['name', 'value'],
                            //data: [['한국어', 'ko'], ['English', 'en'], ['日本語', 'ja']]
                            data: [['한국어', 'ko'], ['English', 'en']]
                        })
                    }, {
                        xtype: 'radiogroup',
                        fieldLabel: _text('MN02319'),
                        name: 'top_menu_mode_user',
                        id: 'top_menu_mode_id_user',
                        allowBlank: false,
                        items: [
                            { boxLabel: _text('MN02320'), id: 'top_menu_mode_b_user', name: 'top_menu_mode_user', inputValue: 'B' },
                            { boxLabel: _text('MN02321'), id: 'top_menu_mode_s_user', name: 'top_menu_mode_user', inputValue: 'S' }
                        ],
                        listeners: {
                            render: function (self) {
                                if (_this.action == 'edit') {
                                    if (_this.data.user.top_menu_mode == 'S') {
                                        self.onSetValue('top_menu_mode_s_user', true);
                                    } else {
                                        self.onSetValue('top_menu_mode_b_user', true);
                                    }
                                }
                            }

                        }
                    }, {
                        xtype: 'radiogroup',
                        hidden: true,
                        fieldLabel: _text('MN02379'),
                        name: 'action_icon_slide_user',
                        id: 'action_icon_slide_user',
                        allowBlank: false,
                        items: [
                            { boxLabel: _text('MN00001'), id: 'action_icon_slide_yes_user', name: 'action_icon_slide', inputValue: 'Y' },
                            { boxLabel: _text('MN00002'), id: 'action_icon_slide_no_user', name: 'action_icon_slide', inputValue: 'N' }
                        ],
                        listeners: {
                            render: function (self) {
                                if (_this.action == 'add') {
                                    self.onSetValue('action_icon_slide_yes_user', true);
                                } else {
                                    if (_this.data.user.action_icon_slide_yes_user) {
                                        self.onSetValue('action_icon_slide_yes_user', true);
                                    } else {
                                        self.onSetValue('action_icon_slide_no_user', true);
                                    }
                                }
                            }

                        }
                    }
                        // ,{
                        // //>>fieldLabel: '유효일시',
                        // fieldLabel: _text('MN00334'),
                        // xtype: 'datefield',
                        // name: 'expired_date',
                        // format: 'Y-m-d',
                        // listeners: {
                        //     render: function(self){
                        //         self.setValue(new Date().add(Date.YEAR, 5).format('Y-m-d'));
                        //     }
                        // }
                        // }
                    ]
                }, {
                    xtype: 'fieldset',
                    id: 'user_group_info',
                    //title: '그룹',
                    title: _text('MN00111'),
                    style: {
                        background: 'white'
                    },
                    //layout: 'fit',
                    //bodyStyle: 'background-color:white;',
                    autoScroll: true,
                    flex: 1,
                    labelWidth: 30,
                    height: 200,


                    items: {
                        xtype: 'checkboxgroup',
                        id: 'grant_list',
                        name: 'group',
                        columns: 3,
                        listeners: {

                            beforerender: function (self) {
                                var s = [];
                                var checked = false;

                                Ext.each(_this.data.groups, function (el, index, items) {
                                    var gName = items[index].member_group_name;
                                    if (_this.action == 'add') {
                                        (items[index].is_default == 'Y') ? checked = true : checked = false;
                                    }
                                    if (_this.action == 'edit') {
                                        (_this.data.groups_member.indexOf(el.member_group_name) == -1) ? checked = false : checked = true;
                                    }

                                    s[index] = { boxLabel: gName, name: gName.replace(' ', '_'), inputValue: items[index].member_group_id, checked: checked };
                                });
                                Ext.getCmp('grant_list').items = s;
                            }
                        }
                    }
                }, {
                    buttonAlign: 'center',
                    buttons: [{
                        // text : '<span style="position:relative;top:1px;"><i class="fa fa-plus" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00033'),
                        text: '<span style="position:relative;top:1px;"><i class="fa fa-plus" style="font-size:13px;"></i></span>&nbsp;' + _this.data.button_text,
                        scale: 'medium',
                        handler: function (self, e) {
                            var _groups = Ext.getCmp('grant_list').getValue();
                            var groups = [];
                            if (_groups.length == 0) {
                                //>>Ext.Msg.alert('정보', '그룹을 선택하여 주세요');MN00023, MSG00098
                                Ext.Msg.alert(_text('MN00023'), _text('MSG00098'));
                                return;
                            }
                            Ext.each(_groups, function (i) {
                                groups.push(i.getRawValue());
                            });

                            var sm = Ext.getCmp('userInfo');
                            var user_form = sm.getForm();

                            if (!user_form.isValid()) return Ext.Msg.alert('알림', '올바른 값을 입력해주세요.');

                            if (_this.action == 'add') {
                                var add_user_id = sm.get(1).getValue();

                                var password_1 = sm.get(2).getValue();
                                var password_2 = sm.get(3).getValue();
                                var email = sm.get(7).getValue();

                                if (!Ext.isEmpty(user_form.findField('phone1'))) {
                                    phone_number = user_form.findField('phone1').getValue() + '-' + user_form.findField('phone2').getValue() + '-' + user_form.findField('phone3').getValue();
                                }

                                var lang = sm.get(9).getValue();
                                var dept = sm.get(6).getValue();

                                if (Ext.getCmp('top_menu_mode_id_user').getValue() == null) {
                                    // Ext.Msg.alert(_text('MN00023'),_text('MSG01058'));
                                    Ext.Msg.alert(_text('MN00023'), '메뉴 형태를 ' + _text('MSG00026'));
                                    return;
                                }

                                var user_top_menu_mode = Ext.getCmp('top_menu_mode_id_user').getValue().inputValue;
                                var action_icon_slide_yn = Ext.getCmp('action_icon_slide_user').getValue().inputValue;
                                _this.changeInfo('add', add_user_id, password_1, password_2, email, phone_number, Ext.getCmp('window_manager_member'), sm, groups, lang, user_top_menu_mode, action_icon_slide_yn);
                            }

                            if (_this.action == 'edit') {
                                _this._editUser(groups, _this.data.user.member_id);
                            }

                            if (Ext.getCmp('user_list')) {
                                Ext.getCmp('user_list').getStore().reload();
                            }

                        }
                    }, {
                        text: '<span style="position:relative;top:1px;"><i class="fa fa-plus" style="font-size:13px;"></i></span>&nbsp;' + _text('MN00004'),
                        scale: 'medium',
                        handler: function () {
                            Ext.getCmp('window_manager_member').close();
                        }

                    }]
                }
                ]

            }

            Ariel.user.modifiy.superclass.initComponent.call(this);
        },
        changeInfo: function (action, add_user_id, value1, value2, email, phone, changeWindow, infos, groups, lang, top_menu_mode, action_icon_slide_yn) {
            var url, dept_nm, dept, groups, user_name, user_groups, msg_r, check_pw;
            if (action == 'add') {
                url = '/store/user/user_oracle.php';
                msg_r = _text('MSG01024');//추가 되었습니다.
            }
            else {
                url = '/store/change_password.php';
                msg_r = _text('MSG02033');//변경 되었습니다.
            }

            if (infos) {
                dept_nm = infos.get(5).getValue();
                dept = infos.get(6).getValue();
                user_groups = groups.join(',');
                user_name = infos.get(4).getValue();
            }


            var waitPopup = Ext.Msg.wait('처리중 입니다.', '처리중...');

            check_pw = this.checkPassword(add_user_id, value1, value2);
            if (check_pw == 'check') {
                Ext.Ajax.request({
                    url: url,
                    params: {
                        action: action,
                        user_id: add_user_id,
                        name: user_name,
                        user_password: value1,
                        password_1: value1,
                        password_2: value2,
                        email: email,
                        phone: phone,
                        groups: user_groups,
                        job_position: dept,
                        dept_nm: dept_nm,
                        lang: lang,
                        top_menu_mode: top_menu_mode,
                        action_icon_slide_yn: action_icon_slide_yn
                    },
                    callback: function (options, success, response) {
                        waitPopup.hide();
                        if (success) {
                            try {
                                var r = Ext.decode(response.responseText);
                                if (r.success) {
                                    if (Ext.getCmp('show_user_info')) {
                                        Ext.getCmp('show_user_info').getForm().setValues({
                                            info_user_phone: phone,
                                            info_user_email: email
                                        });
                                        if (r.msg) {
                                            Ext.Msg.show({
                                                title: _text('MN00024'),//'확인'
                                                msg: r.msg,
                                                buttons: Ext.Msg.OK
                                            });
                                        }
                                    }
                                    changeWindow.close();
                                }
                                else {
                                    if (r.msg) {
                                        Ext.Msg.show({
                                            title: _text('MN00024'),//'확인'
                                            msg: r.msg,
                                            buttons: Ext.Msg.OK
                                        });
                                    }
                                }
                            }
                            catch (e) {
                                Ext.Msg.alert(e['name'], e['message']);
                            }
                        }
                        else {
                            Ext.Msg.alert(_text('MN00024'), response.statusText);//'확인'
                        }
                    }
                });
            }
        },
        onAccept: function (_this) {
            getValue = _this.items.items[0].getForm().items;
            var user_id = _this.r.data.user_id;
            var user_nm = getValue.items[0].getValue();
            var password_0 = getValue.items[1].getValue();
            var password_1 = getValue.items[2].getValue();
            var password_2 = getValue.items[3].getValue();
            var email = getValue.items[4].getValue();
            var phone = getValue.items[5].getValue();
            var first_page = getValue.items[7].getValue();
            var user_top_menu_mode = getValue.items[8].getValue().inputValue;
            var action_icon_slide_yn = getValue.items[9].getValue().inputValue;
            var check_password = _this.r.data.check_pw;


            var check_pw = _this.checkPassword(user_id, password_1, password_2, check_password);

            if (check_pw != 'check') {
                //Ext.Msg.alert( _text('MN00023'), _text('MSG00091'));
                return;
            }

            if (!passwordFieldHidden) {


                if (Ext.isEmpty(password_0) || Ext.isEmpty(password_1) || Ext.isEmpty(password_2)) {
                    //Ext.Msg.alert( _text('MN00023'),'비밀번호를 입력하세요.');
                    Ext.Msg.alert(_text('MN00023'), _text('MSG00095'));
                    return;
                }
                if (password_1 != password_2) {
                    // Ext.Msg.alert( _text('MN00023'),'비밀번호가 서로 맞지 않습니다.');
                    Ext.Msg.alert(_text('MN00023'), _text('MSG00091'));
                    return;
                }
            }

            Ext.Msg.show({
                title: _text('MN00024'),//확인
                msg: _text('MSG02118'),//저장하시겠습니까?,
                buttons: Ext.Msg.OKCANCEL,
                fn: function (btn) {
                    if (btn == 'ok') {
                        Ext.Ajax.request({
                            url: '/store/change_info.php',
                            params: {
                                user_id: user_id,
                                password_0: password_0,
                                password_1: password_1,
                                password_2: password_2,
                                email: email,
                                phone: phone,
                                lang: Ext.getCmp('lang').getValue(),
                                user_menu_mode: user_top_menu_mode,
                                action_icon_slide: action_icon_slide_yn,
                                first_page: first_page
                            },
                            callback: function (opts, success, response) {
                                if (success) {
                                    try {
                                        var r = Ext.decode(response.responseText);

                                        if (r.success) {
                                            // Ext.Msg.alert( _text('MN00023'), r.msg);
                                            //Ext.Msg.alert( _text('MN00023'), r.msg);
                                            Ext.getCmp('show_user_info').getForm().setValues({
                                                info_user_phone: phone,
                                                info_user_email: email
                                            });
                                            this.close();
                                            Ext.Msg.show({
                                                icon: Ext.Msg.QUESTION,
                                                //>>title: '확인',
                                                title: _text('MN00024'),
                                                //>> msg: '사용자 정보가 변경되었습니다. 다시 로그인 해 주시기 바랍니다.'+'</br>'+' 님 로그아웃 하시겠습니까?',
                                                msg: _text('MSG02054') + ' ' + _text('MSG01038') + '</br>' + user_nm + '(' + user_id + '), 님 ' + _text('MSG00002'),
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
                                            Ext.Msg.alert(_text('MN00022'), r.msg);
                                        }
                                    } catch (e) {
                                        Ext.Msg.alert(_text('MN00022'), e + '<br />' + response.responseText);
                                    }
                                } else {
                                    Ext.Msg.alert(_text('MN00022'), response.statusText);
                                }
                            }
                        });
                    }
                }
            })
        },
        checkPassword: function (add_user_id, value1, value2, check_pw) {
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
            } else if (check_pw == 'Y' && add_user_id != 'admin') {
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
        _editUser: function (groups, member_id) {
            var sm = Ext.getCmp('userInfo');
            var user_form = sm.getForm();
            var user_top_menu_mode = Ext.getCmp('top_menu_mode_id_user').getValue().inputValue;
            var action_icon_slide_yn = Ext.getCmp('action_icon_slide_user').getValue().inputValue;
            var phone_number = '';
            if (!Ext.isEmpty(user_form.findField('phone1'))) {
                phone_number = user_form.findField('phone1').getValue() + '-' + user_form.findField('phone2').getValue() + '-' + user_form.findField('phone3').getValue();
            }

            var userId = user_form.findField('user_id').getValue();
            var params = {
                user_real_name: user_form.findField('name').getValue(),
                dept_nm: user_form.findField('dept_nm').getValue(),
                job_position: user_form.findField('job_position').getValue(),
                groups: groups.join(','),
                //expired_date: sm.get(5).getValue().format('YmdHis'),
                email: user_form.findField('email').getValue(),
                //phone: sm.get(6).getValue(),
                hp_no: phone_number,
                lang: user_form.findField('lang').getValue(),
                user_top_menu_mode: user_top_menu_mode,
                action_icon_slide_yn: action_icon_slide_yn
            };
            Ext.Ajax.request({
                method: 'PUT',
                url: '/api/v1/users/' + userId,
                params: params,
                callback: function (opt, success, respnse) {
                    if (success) {
                        try {
                            var r = Ext.decode(respnse.responseText);
                            if (r.success) {
                                Ext.getCmp('user_list').getStore().reload();
                                Ext.getCmp('window_manager_member').close();
                            } else {
                                //>>Ext.Msg.alert('등록 오류', r.msg);
                                Ext.Msg.alert(_text('MN00022'), r.msg);
                            }
                        } catch (e) {
                            Ext.Msg.alert(e.name, e.message);
                        }
                    } else {
                        Ext.Msg.alert(_text('MN01098'), response.statusText);//'서버 오류'
                    }

                }
            });



            // Ext.Ajax.request({
            //     url: '/store/user/user_oracle.php',
            //     params: {
            //         action: 'edit_1',
            //         // member_id: empty($_POST['member_id']) ? '0' : $_POST['member_id'],
            //         member_id: member_id,
            //         userId: user_form.findField('user_id').getValue(),
            //         pw: password,
            //         name: user_form.findField('name').getValue(),
            //         dept_nm: user_form.findField('dept_nm').getValue(),
            //         job_position: user_form.findField('job_position').getValue(),
            //         groups: groups.join(','),
            //         //expired_date: sm.get(5).getValue().format('YmdHis'),
            //         email: user_form.findField('email').getValue(),
            //         //phone: sm.get(6).getValue(),
            //         phone: phone_number,
            //         lang: user_form.findField('lang').getValue(),
            //         user_top_menu_mode: user_top_menu_mode,
            //         action_icon_slide_yn: action_icon_slide_yn
            //     },
            //     callback: function (opts, success, resp) {
            //         //w.hide();
            //         if (success) {
            //             try {
            //                 var r = Ext.decode(resp.responseText);
            //                 if (r.success) {
            //                     Ext.getCmp('user_list').getStore().reload();
            //                     Ext.getCmp('window_manager_member').close();
            //                 } else {
            //                     //>>Ext.Msg.alert('등록 오류', r.msg);
            //                     Ext.Msg.alert(_text('MN00022'), r.msg);
            //                 }
            //             } catch (e) {
            //                 //>>Ext.Msg.alert('디코드 오류', e);
            //                 Ext.Msg.alert(_text('MN00022'), e);//에러
            //             }
            //         } else {
            //             //>>Ext.Msg.alert('서버 오류', resp.statusText);
            //             Ext.Msg.alert(_text('MN00022'), resp.statusText);
            //         }
            //     }
            // });
        }


    });

})();