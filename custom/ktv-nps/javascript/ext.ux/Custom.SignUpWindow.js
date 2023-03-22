Ext.ns("Custom");
Custom.SignUpWindow = Ext.extend(Ext.Window, {
  // 관리자 페이지일때
  rowRecord: null,
  check: false,
  agreeCheck: [],

  _reloadStore: function () {
    // 재사용한 곳의 스토어 리로드
    // null
  },
  _buttonId: Ext.id(),
  _refreshId: Ext.id(),
  _programListId: Ext.id(),
  _submitButtonId: Ext.id(),


  _agreeCheckId: Ext.id(),

  // 
  title: '사용자 ID신청',
  modal: true,
  layout: 'fit',
  border: false,
  draggable: true,
  autoScroll: true,
  // width: Ext.getBody().getViewSize().width * 0.3,
  // height: Ext.getBody().getViewSize().height * 0.55,
  width: 1020,
  // height: 660,
  autoHeight: true,
  initComponent: function () {
    this._initialize();

    Custom.SignUpWindow.superclass.initComponent.call(this);
  },
  _initialize: function () {
    var _this = this;
    var signUpForm = this._signUpForm();
    var agreeCheckContainer = this._agreeCheckContainer();

    switch (_this.check) {
      case true:
        var text = '수정';
        break;
      case false:
        var text = '신청';
        break;
    }
    var container = new Ext.Container({
      autoHeight: true,
      layout: 'hbox',
      items: [
        signUpForm
      ]
    });
    if (!this.check) {
      container.add(agreeCheckContainer);
    };
    this.items = container;
    this.buttonAlign = 'center';
    this.buttons = [{
      scale: 'medium',
      text: text,
      id: _this._submitButtonId,
      handler: function (self) {
        var form = signUpForm.getForm();
        var userId = form.findField('user_id').getValue();
        var password = form.findField('password').getValue();
        var passwordCheck = form.findField('password_check').getValue();
        var idCheck = form.findField('check').getValue();
        var fileListGrid = signUpForm.find('name', 'programList')[0];


        var fileList = [];
        if (!(fileListGrid.items.getCount() == 0)) {

          var fileListStore = fileListGrid.get(0);
          var storeData = fileListStore.getStore().data.items;

          Ext.each(storeData, function (r, i) {
            fileList[i] = r.data;
          });

        } else {
          fileList = null;
        };

        if (!(_this.check)) {
          var passwordCheck = _this._checkPassword(userId, password, passwordCheck);
        } else {
          _this._requestUserFormUpdate(form, fileList);
        }

        // valid check
        if (!form.isValid()) {
          Ext.Msg.alert('알림', '입력되지 않은 값이 있습니다.');
          return;
        }

        if (!idCheck) {
          Ext.Msg.alert('알림', '아이디 중복체크가 되지 않았습니다.');
          return;
        }

        if (!_this.check) {
          if (!_this._agreeAllCheck()) {
            Ext.Msg.alert('알림', '이용약관 동의가 모두 체크되지 않았습니다.');
            return;
          }
        }



        if (passwordCheck == 'check') {
          // 비밀번호 check 되었을때 submit
          if (!(_this.check)) {
            form.submit({
              method: 'POST',
              // url: '/api/v1/users/request',
              url: '/api/v1/open/users/request',
              params: {
                program_list: Ext.encode(fileList)
              },
              success: function (form, action) {
                Ext.Msg.show({
                  title: '알림',
                  msg: '신청 완료 되었습니다.',
                  buttons: Ext.Msg.OK,
                  fn: function (btnId) {
                    if (btnId == 'ok') {
                      _this.close();
                    }
                  }
                });
              },
              failure: function (form, action) {
                var error = Ext.decode(action.response.responseText);
                Ext.Msg.alert(error.code, error.msg);
              }
            })
          }
        };
      }
    }, {
      scale: 'medium',
      text: '취소',
      handler: function () {
        _this.close();
      }
    }];
  },
  /**
   * 사용자 신청 폼
   */
  _signUpForm: function () {

    var string = '상기와 같이 사용자ID를 신청합니다.</br>'
      + '<span style=" font-size:13px; color:red;">또한 내부 자료 누출 사고 발생 시</br>'
      + '모든 책임을 질 것을 동의합니다.</br > '
      + '※ 작업 후 불필요 파일은</br>'
      + '&emsp;&nbsp;즉시 삭제해주시기 바랍니다.</br>'
      + '&emsp;&nbsp;차후 불이익 발생 시 책임지지 않습니다.</span> ';


    // var string = '상기와 같이 사용자ID를 신청합니다.</br>'
    //   + '<span style=" font-size:13px; color:red;">또한 내부 자료 누출 사고 발생 시 모든 책임을 질 것을 동의합니다.</br > '
    //   + '※ 작업 후 불필요 파일은 즉시 삭제해주시기 바랍니다.</br>'
    //   + '&emsp;&nbsp;차후 불이익 발생 시 책임지지 않습니다.</span> ';

    var _this = this;
    var form = new Ext.form.FormPanel({
      border: false,
      autoHeight: true,
      margins: '5 0 5 5',
      defaultType: 'textfield',
      padding: 5,
      flex: 2,
      defaults: {
        asterisk: true,
        anchor: '95%',
        allowBlank: false
      },
      items: [{
        asterisk: false,
        xtype: 'combo',
        fieldLabel: '제작구분',
        displayField: 'value',
        valueField: 'name',
        mode: 'local',
        editable: false,
        triggerAction: 'all',
        value: 'news',
        name: 'mnfct_se',
        store: new Ext.data.ArrayStore({
          fields: ['name', 'value'],
          data: [
            ['news', '뉴스'],
            ['prod', '제작'],
          ]
        }),
        listeners: {
          select: function (self) {
            if ((self.getValue() == 'prod') || (self.getValue() == '뉴스')) {
              var form = _this.get(0).get(0).getForm();
              form.findField('charger_id').setValue(null);
              form.findField('charger_user_id').setValue(null);
              form.findField('charger_nm').setValue(null);


              var programSearchButton = Ext.getCmp(_this._buttonId);
              var refreshButton = Ext.getCmp(_this._refreshId);
              programSearchButton.enable();
              refreshButton.enable();
            } else {
              // _this._adminAjax();
              _this._disable(form);
            };
            _this.doLayout();
            _this.getEl().disableShadow();
          },
          afterrender: function (self) {
            if ((self.getValue() == 'prod') || (self.getValue() == '제작')) {
              var programSearchButton = Ext.getCmp(_this._buttonId);
              var refreshButton = Ext.getCmp(_this._refreshId);
              refreshButton.enable();
              programSearchButton.enable();
            } else {
              // _this._adminAjax();
              _this._disable(form);
            };
          }
        }
      },
      _this._inCodeComboBox("instt", "부처", "INSTT"),
      // {
      //   fieldLabel: '부서',
      //   name: 'dept'
      // },
      _this._inCodeComboBoxDp("dept", "부서", "DEPT", "c104176100"),
      {
        asterisk: false,
        xtype: 'compositefield',
        fieldLabel: '프로그램',
        name: 'programfield',
        items: [
          {
            hidden: true,
            xtype: 'textfield',
            name: 'progrm_nm',
            readOnly: true,
            flex: 3,
            disable: true
          }, {
            hidden: true,
            xtype: 'textfield',
            name: 'progrm_id',
            disable: true
          },
          {
            xtype: 'button',
            flex: 2,
            text: '프로그램 조회/추가',
            id: _this._buttonId,
            handler: function (self) {
              var components = [
                // '/custom/ktv-nps/javascript/ext.ux/components/Custom.BISProgramWindow.js',
                // '/custom/ktv-nps/javascript/ext.ux/components/Custom.ProgramListGrid.js'
                '/javascript/ext.ux/Ariel.Nps.OpenBISProgram.js'
              ];

              Ext.Loader.load(components, function (r) {


                var BISProgramGrid = _this._addProgramGrid(form);
                var BISProgram = _this._searchProgram(BISProgramGrid);
                var win = new Ext.Window({
                  width: 650,
                  height: 600,
                  layout: 'fit',
                  modal: true,
                  items: new Ext.Panel({
                    layout: 'vbox',
                    items: [
                      BISProgram,
                      BISProgramGrid
                    ]
                  }),
                  buttons: [{
                    scale: 'medium',
                    text: '등록',
                    handler: function () {
                      var programList = form.find('name', 'programList')[0];
                      var programStoreData = BISProgramGrid.getStore().data;
                      var isData = programStoreData.getCount();

                      if (!(isData == 0)) {
                        Ext.Msg.show({
                          title: '알림',
                          msg: '프로그램 목록을 추가하시겠습니까?',
                          buttons: Ext.Msg.OKCANCEL,
                          fn: function (btnId, text, opts) {
                            if (btnId == 'ok') {
                              // 리스트 뷰는 왜 안되지?
                              // var list = new Ext.list.ListView({
                              var list = _this._programListGrid();
                              var programRecord = Ext.data.Record.create([
                                { name: 'folder_path_nm' },
                                { name: 'folder_path' },
                                { name: 'pgm_id' }
                              ]);

                              Ext.each(programStoreData.items, function (r) {
                                var selectRecord = new programRecord(r.data);
                                list.getStore().add(selectRecord);
                              });

                              programList.removeAll();
                              programList.add(list);
                              programList.show();
                              form.doLayout();
                              _this.doLayout();
                              win.close();
                            }
                          }
                        });
                      } else {
                        Ext.Msg.alert('알림', '선택된 프로그램이 없습니다.');
                      }
                    }
                  }, {
                    scale: 'medium',
                    text: '취소',
                    handler: function () {
                      win.close();
                    }
                  }],
                  listeners: {
                    afterrender: function (self) {
                      _this._addOrDelTbarButton(BISProgram, BISProgramGrid);
                    }
                  }
                }).show();
              });
            }
          }, {
            xtype: 'button',
            id: _this._refreshId,
            flex: 2,
            text: '초기화',
            handler: function (self) {
              var programList = form.find('name', 'programList')[0];
              programList.removeAll();
              programList.hide();
              form.doLayout();
              _this.doLayout();
            }
          }]
      }, {
        xtype: 'compositefield',
        asterisk: true,
        fieldLabel: '담당자',
        items: [{
          xtype: 'textfield',
          allowBlank: false,
          name: 'charger_id',
          readOnly: true,
          flex: 2,
          hidden: true
        },{
          xtype: 'textfield',
          allowBlank: false,
          name: 'charger_user_id',
          readOnly: true,
          flex: 2
        }, {
          xtype: 'textfield',
          name: 'charger_nm',
          disabled: true,
          submitValue: false,
          flex: 2
        }, {
          xtype: 'button',
          flex: 1,
          text: '조회',
          handler: function (self) {
            _this._searchUser();
          }
        }]
      }, {
        xtype: 'fieldset',
        hidden: true,
        // title: '프로그램 리스트', // title, header, or checkboxToggle creates fieldset header
        id: _this._programListId,
        autoHeight: true,
        columnWidth: 0.5,
        name: 'programList',
        // checkboxToggle: true,
        // collapsed: true, // fieldset initially collapsed
        items: []
      }, {
        fieldLabel: '이름',
        name: 'user_nm'
      }, {
        xtype: 'compositefield',
        fieldLabel: '아이디',
        items: [{
          xtype: 'textfield',
          name: 'user_id',
          allowBlank: false,
          flex: 3
        }, {
          xtype: 'button',
          flex: 2,
          text: '중복체크',
          name: 'checkButton',
          handler: function (self) {
            // 공백이 있나 없나 체크 
            function checkSpace(str) {
              if (str.search(/\s/) != -1) {
                return true;
              } else {
                return false;
              }
            };


            var getForm = form.getForm();
            var userId = getForm.findField('user_id');
            var checkMemberId = getForm.findField('check');
            var button = self;

            if (checkSpace(userId.getValue())) {
              return Ext.Msg.alert('알림', '아이디에 공백이 들어갈 수 없습니다.');
            }

            if ((userId.getValue() == '')) {
              return Ext.Msg.alert('알림', '아이디를 입력해 주세요.');
            }

            Ext.Ajax.request({
              method: 'GET',
              // url: '/api/v1/users/exists',
              url: '/api/v1/open/users/exists',
              params: {
                user_id: userId.getValue()
              },
              // headers: {
              //   'Content-Type': 'application/json',
              //   'X-API-KEY': 'B+Hqhy*3GEuJJmk%',
              //   'X-API-USER': 'admin'
              // },
              callback: function (opts, success, resp) {
                if (success) {
                  try {
                    // existsMemberId 중복이면 true 아니면 false
                    var existsMemberId = Ext.decode(resp.responseText).data;

                    if (!existsMemberId) {
                      var msg = Ext.Msg.show({
                        title: '알림',
                        msg: '사용가능한 아이디 입니다. 사용하시겠습니까?',
                        buttons: Ext.Msg.OKCANCEL,
                        fn: function (btnId, text, opts) {
                          if (btnId == 'ok') {
                            userId.setReadOnly(true);
                            checkMemberId.setValue(true);
                            button.disable();
                          }
                        }
                      });
                    } else {
                      Ext.Msg.alert('알림', '이미 존재하는 아이디 입니다.');
                    }
                  } catch (e) {
                    Ext.Msg.alert(e['name'], e['message']);
                  }
                } else {
                  Ext.Msg.alert('status: ' + resp.status, resp.statusText);
                }
              }
            });


          },
          listeners: {
            afterrender: function (self) {
              if (_this.check) {
                self.disable();
              }
            }
          }
        }, {
          xtype: 'checkbox',
          name: 'check',
          readOnly: true,
          disabled: true,
          submitValue: false
        }]
      }, {
        fieldLabel: '비밀번호',
        name: 'password',
        inputType: 'password'
      }, {
        // 비밀번호 안내
        xtype: 'container',
        style: {
          marginLeft: '105px'
        },
        items: [{
          xtype: 'label',
          style: {
            color: 'red'
          },
          text: '비밀번호는 최소 9자 이상의 영문, 숫자로 입력해야 합니다.',
          listeners: {
            afterrender: function (self) {
              if (_this.check) {
                self.hide();
              }
            }
          }
        }]
      }, {
        fieldLabel: '비밀번호 확인',
        name: 'password_check',
        inputType: 'password'
      }, {
        fieldLabel: 'HP',
        name: 'phone'
      }, {
        asterisk: false,
        fieldLabel: '내선번호',
        allowBlank: true,
        name: 'lxtn_no'
      }, {
        xtype: 'textarea',
        fieldLabel: '사용목적',
        name: 'use_purps'
      }, {
        xtype: 'displayfield',
        style: {
          'font-size': '14px'
        },
        html: string
      }],
      listeners: {
        afterrender: function (self) {
          var getForm = self.getForm();
          // 제작구분 콤보필드가 뉴스이면 활성화 제작이면 비활성화
          var mnfctSe = getForm.findField('mnfct_se');
          // var progrm_nm = getForm.findField('progrm_nm');

          var password = getForm.findField('password');
          var passwordCheck = getForm.findField('password_check');



          var chargerNm = getForm.findField('charger_nm');

          var checkMemberId = getForm.findField('check');

          var userId = getForm.findField('user_id');

          // var dept = getForm.findField('dept');
          // var instt = getForm.findField('instt');

          if (mnfctSe.getValue() == 'prod') {
            // progrm_nm.disable();
          } else {
            // progrm_nm.enable();
          };


          // 관리자 페이지 일때
          if (_this.check) {
            password.hide();
            password.disable();


            passwordCheck.setValue(_this.rowRecord.get('password'));
            passwordCheck.hide();
            passwordCheck.disable();

            chargerNm.setValue(_this.rowRecord.get('charger').user_nm);

            checkMemberId.setValue(_this.check);

            userId.setReadOnly(true);
            getForm.setValues(_this.rowRecord.data);

            // 프로그램이 있을때
            var programList = form.find('name', 'programList')[0];
            var list = _this._programListGrid();

            var programData = _this.rowRecord.data.programs;

            if (programData.length != 0) {
              var programRecord = Ext.data.Record.create([
                { name: 'folder_path_nm' },
                { name: 'folder_path' },
                { name: 'pgm_id' }
              ]);

              Ext.each(programData, function (r) {

                var selectRecord = new programRecord(r);
                list.getStore().add(selectRecord);
              });


              programList.removeAll();
              programList.add(list);
              programList.show();
              form.doLayout();
              _this.doLayout();
            }
          }

          // '<i class="fa fa-asterisk" style="color: #f20606;" ></i>' + 
          Ext.each(self.items.items, function (r) {
            self.getForm().findField('instt').asterisk = false;

            if ((r.asterisk === true) && (typeof r.fieldLabel !== 'undefined') && (r.asterisk !== false)) {
              var oriLabel = r.fieldLabel;
              // var asterisk = '<i class="fa fa-asterisk" style="color: #f20606;" ></i>';
              var asterisk = '<span style="color: #f20606;" >*</span>';
              r.fieldLabel = asterisk + oriLabel;
            }
          });
        }
      }
    });
    return form;
  },
  /**
   * 약관 동의 콘테이너
   */
  _agreeCheckContainer: function () {
    var s = this;
    // &emsp;
    var circleIcon = s.HTML('', ["font-size: 10px"], 'fa fa-circle-o', false);
    var circleDotIcon = s.HTML('', ["font-size: 10px"], 'fa fa-dot-circle-o', false);

    var agreeText =
      s.HTML('개인정보의 수집 및 이용목적', ["font-size: 15px", "font-weight:bold"]) +
      '&emsp;' + circleIcon + s.HTML('방송제작 관련 섭외, 출연, 제작 등 방송 제작 업무 활용', ["font-size: 15px"]) +
      '&emsp;' + circleIcon + s.HTML('출연료 및 제작비 지급 정산', ["font-size: 15px"]) + "</br>" +
      s.HTML('개인정보 수집항목', ["font-size: 15px", "font-weight:bold"], 'fal fa-square') +
      '&emsp;' + circleIcon + s.HTML('<u>필수항목: 성명, 소속, 직위, 핸드폰, 이메일</u>', ["font-size: 17px", "font-weight:bold", "color:red"]) +
      // '&emsp;' + circleIcon + s.HTML('<u>선택항목: 은행명, 계좌번호</u>', ["font-size: 17px", "font-weight:bold", "color:red"]) + "</br>" +
      '</br>' +
      s.HTML('개인정보의 보유 및 이용기간', ["font-size: 15px", "font-weight:bold"], 'fal fa-square', true) +
      '&emsp;' + circleIcon + s.HTML('문화체육관광부 개인정보 보호지침 제20조에 따라', ["font-size: 15px"], null, true) +
      '&emsp;&emsp;' + s.HTML('<u>동의서 제출일로부터</u>', ["font-size: 17px", "font-weight:bold", "color:blue"], null, false) +
      s.HTML('<u> 3년</u>', ["font-size: 17px", "font-weight:bold", "color:red"], null, false) +
      s.HTML('으로 하며,', ["font-size: 15px"], null) +
      '&emsp;&emsp;' + s.HTML('재동의를 받지 않은 개인정보는', ["font-size: 15px"], null, true) +
      '&emsp;&emsp;' + s.HTML('문화체육관광부 개인정보 보호지침 제24조에 따라 파기', ["font-size: 15px", "font-weight:bold"], null, false) +
      s.HTML('한다', ["font-size: 15px"], null) + "</br>" +
      s.HTML('동의거부권 및 동의거부에 따른 불이익', ["font-size: 15px", "font-weight:bold"], 'fal fa-square') +
      '&emsp;' + circleIcon + s.HTML('<u>최소한의 정보만 수집하며 개인정보 수집을 거부할 권리가 있으며,</u>', ["font-size: 17px", "font-weight:bold", "color:blue"], null) +
      '&emsp;' + circleIcon + s.HTML('<u>이 경우 한국정책방송원의 통합인물정보 DB에 등록되지 않습니다</u>', ["font-size: 17px", "font-weight:bold", "color:blue"], null);
    var _this = this;
    var container = new Ext.Container({
      flex: 3,
      margins: '5 15 5 0',
      items: [{
        xtype: 'fieldset',
        // title: '개인정보의 수집 및 이용에 관한 안내',
        title: s.HTML('개인정보의 수집 및 이용에 관한 안내', ["font-size: 12px", "font-weight:bold"]),
        closable: true,
        items: [{
          border: false,
          html: agreeText,
          style: {
            'margin-bottom': '10px'
          }
        }, {
          xtype: 'container',
          border: false,
          id: _this._agreeCheckId,
          items: [
            _this._agreeOrDisagreeRadioGroup('&emsp;' + circleDotIcon + s.HTML('개인정보의 필수 항목 수집 이용 동의 여부 :', ["font-size: 15px", "font-weight:bold"], null, false), 1),
            // _this._agreeOrDisagreeRadioGroup('&emsp;' + circleDotIcon + s.HTML('개인정보의 선택 항목 수집 이용 동의 여부 :', ["font-size: 15px", "font-weight:bold"], null, false), 2)
          ]
        }]
      }]
    });
    return container;
  },
  /**
   * 담당자 조회 후 입력
   */
  _searchUser: function () {
    var _this = this;

    var components = [
      '/javascript/common.js',
      '/custom/ktv-nps/javascript/common.js',// 뺄거
      '/custom/ktv-nps/javascript/ext.ux/Custom.UserSelectWindow.js',
      '/custom/ktv-nps/javascript/ext.ux/components/Custom.UserListGrid.js',
      '/custom/ktv-nps/javascript/api/Custom.Store.js',

      '/javascript/component/button/Ariel.IconButton.js',
    ];

    Ext.Loader.load(components, function (r) {
      var win = new Custom.UserSelectWindow({
        singleSelect: true,
        userIdMasking: true,
        listeners: {
          ok: function () {
            var user = this._selected.data;
            var form = _this.get(0).get(0).getForm();
            var textUserName = this._userNameField.getValue();
            form.findField('charger_id').setValue(user.member_id);
            form.findField('charger_user_id').setValue(user.user_id);
            form.findField('charger_nm').setValue(textUserName);
            win.close();
          }
        }
      }).show();
    });
  },
  /**
   * 새 비밀번호와 비밀번호 확인을 비교해서 일치할때 check 리턴 아니면 re 리턴
   * @param string user_id 
   * @param string value1 
   * @param string value2 
   * @param string check_pw 
   */
  _checkPassword: function (user_id, value1, value2) {
    function fn_pwCheck(p) {
      chk1 = /^[a-z\d\{\}\[\]\/?.,;:|\)*~`!^\-_+&lt;&gt;@\#$%&amp;\\\=\(\'\"]{8,12}$/i;  //영문자 숫자 특문자 이외의 문자가 있는지 확인
      chk2 = /[a-z]/i;  //적어도 한개의 영문자 확인
      chk3 = /\d/;  //적어도 한개의 숫자 확인
      chk4 = /[\{\}\[\]\/?.,;:|\)*~`!^\-_+&lt;&gt;@\#$%&amp;\\\=\(\'\"]/; //적어도 한개의 특문자 확인
      if (chk1.test(p)) {
        if (chk2.test(p) && chk3.test(p) && chk4.test(p)) {
          return true;
        } else {
          return false;
        }
      }
      else {
        return 're';
      }
      //return chk1.test(p) && chk2.test(p) && chk3.test(p) && chk4.test(p);
    }
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
    } else if (user_id != 'admin') {
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
   * 폼 수정 submit 
   * @param this._signUpForm() form 
   * @param ARRAY filelist 파일리스트가 있다면 배열로 받는다.
   */
  _requestUserFormUpdate: function (form, fileList) {
    var _this = this;
    var id = _this.rowRecord.get('id');
    return form.submit({
      method: 'PUT',
      url: '/api/v1/users/request/' + id,
      params: {
        program_list: Ext.encode(fileList)
      },
      success: function (form, action) {

        if (action.result.data === null) {
          return Ext.Msg.alert('알림', action.result.msg);
        }
        Ext.Msg.show({
          title: '알림',
          msg: '수정 완료 되었습니다.',
          buttons: Ext.Msg.OK,
          fn: function (btnId) {
            if (btnId == 'ok') {
              _this.close();
              _this._reloadStore();
            }
          }
        });
      },
      failure: function (form, action) {
        var error = Ext.decode(action.response.responseText);
        Ext.Msg.alert(error.code, error.msg);
      }
    })
  },
  /**
   * 프로그램 목록을 조회하는 그리드
   */
  _searchProgram: function (grid) {
    return new Ariel.Nps.OpenBISProgram({
      flex: 2,
      height: 300,
      listeners: {
        selectionsProgram: function (self, sel) {
          var programRecord = Ext.data.Record.create([
            { name: 'folder_path_nm' },
            { name: 'folder_path' },
            { name: 'pgm_id' }
          ]);
          var store = grid.getStore();

          Ext.each(sel, function (r) {
            var selectRecord = new programRecord(r.data);
            store.add(selectRecord);
          });
        }
      }
    });
  },
  /**
   * 조회한 목록에서 프로그램을 등록하기전 추가한 프로그램 목록을 보여주는 그리드
   * @param {} form 
   */
  _addProgramGrid: function (form) {
    var _this = this;
    var grid = new Ext.grid.GridPanel({
      flex: 1,
      height: 300,
      viewConfig: {
        emptyText: '추가된 프로그램 정보가 없습니다',
        forceFit: true
      },
      store: new Ext.data.ArrayStore({
        fields: [
          { name: 'folder_path_nm' },
          { name: 'folder_path' },
          { name: 'pgm_id' }
        ]
      }),
      colModel: new Ext.grid.ColumnModel({
        columns: [
          {
            header: '순번',
            renderer: function (v, p, record, rowIndex) {
              return rowIndex + 1;
            },
            width: 70
          },
          { header: '프로그램명', dataIndex: 'folder_path_nm', sortable: true, width: 300 },
          { header: '폴더명', dataIndex: 'folder_path', sortable: true, width: 100 },
          // { header: '프로그램ID', dataIndex: 'pgm_id', sortable: true, width: 150 }
        ]
      }),
      tbar: [
        {
          hidden: true,
          xtype: 'aw-button',
          iCls: 'fa fa-minus',
          text: '삭제',
          handler: function (self) {
            var sm = grid.getSelectionModel();

            if (sm.hasSelection()) {
              var records = sm.getSelections();

              Ext.Msg.show({
                title: '알림',
                msg: '삭제하시겠습니까?',
                buttons: Ext.Msg.OKCANCEL,
                fn: function (btnId) {
                  if (btnId == 'ok') {
                    Ext.each(records, function (r) {
                      grid.getStore().remove(r);
                    });
                  }
                }
              })
            } else {

              Ext.MessageBox.minWidth = 230;
              Ext.Msg.alert('알림', '삭제하실 프로그램을 선택해주세요.');
            }
          }
        }],
      listeners: {
        afterrender: function (self) {
          var programList = form.find('name', 'programList')[0];
          var programListGrid = programList.get(0);
          var isProgramListGrid = programList.items.getCount();

          if (!(isProgramListGrid == 0)) {

            var programStoreData = programListGrid.getStore().data;
            var programRecord = Ext.data.Record.create([
              { name: 'folder_path_nm' },
              { name: 'folder_path' },
              { name: 'pgm_id' }
            ]);

            Ext.each(programStoreData.items, function (r) {
              var selectRecord = new programRecord(r.data);
              grid.getStore().add(selectRecord);
            });
          }
        }
      }
    });
    return grid;
  },
  /**
   * 추가한 목록을 보여주는 그리드의 툴바 버튼
   * @param {} searchListGrid 
   * @param {*} addListGrid 
   */
  _addOrDelTbarButton: function (searchListGrid, addListGrid) {
    addListGrid.topToolbar.addButton({
      xtype: 'aw-button',
      iCls: 'fa fa-arrow-down',
      // text: '추가',
      handler: function (btn) {

        function inArray(array, item) {
          for (var i = 0; i < array.length; i++) {
            if (array[i].data.id == item) {
              return false;
            }
          }
          return true;
        };

        var grid = searchListGrid;
        var sm = grid.getSelectionModel();
        if (sm.hasSelection()) {
          var addListGridStore = addListGrid.getStore();
          var records = sm.getSelections();

          // 프로그램 추가 목록 그리드의 데이터 수?
          if (addListGridStore.data.items.length == 0) {

            grid._fireSelectionsEvent(records);
          } else {
            var storeData = addListGridStore.data.items;
            // 등록후의 중복 체크
            var programOverlapCheck = [];
            Ext.each(records, function (record, i) {
              Ext.each(storeData, function (r) {
                // 왼쪽은 사용자 신청 / 오른쪽은 홈화면에서의 조건
                if ((record.get('id') == r.get('id')) || (record.get('id') == r.get('pgm_id'))) {
                  programOverlapCheck.push(record);
                }
              });
            });

            // 스토어의 레코드와 선택한 레코드중 중복된 값이 있으면 programOverlapCheck에 저장

            if (programOverlapCheck.length != 0) {
              Ext.each(programOverlapCheck, function (r) {
                records.remove(r);
              });
            }
            // /**
            // * 프로그램 중복이 있을떄
            // */
            // var setRecord = [];
            // // records 선택한 레코드
            // // 스토어의 저장되어있는 레코드
            // /**
            //  */
            // console.log(storeData);
            // console.log(records);
            // Ext.each(storeData, function (r, i) {
            //   // 같은 데이터가 있으면 false
            //   if (!inArray(records, r.data.id) {
            //     // setRecord.push(records[i]);
            //   };
            //   //   if (check) {
            //   //     records.push(r);
            //   //     tempArray.push(item);
            //   //   };
            // });




            grid._fireSelectionsEvent(records);
          };



          // resultArray;
          // console.log(resultArray);

          // 그리드 스토어에 선택한 레코드를 넣는건가?
          // grid._fireSelectionsEvent(records)





        } else {
          Ext.Msg.alert('알림', '추가하실 프로그램을 선택해주세요.');
        }
      }
    });
    addListGrid.topToolbar.addButton({
      xtype: 'aw-button',
      iCls: 'fa fa-arrow-up',
      // text: '삭제',
      handler: function (self) {
        var sm = addListGrid.getSelectionModel();

        if (sm.hasSelection()) {
          var records = sm.getSelections();

          Ext.Msg.show({
            title: '알림',
            msg: '삭제하시겠습니까?',
            buttons: Ext.Msg.OKCANCEL,
            fn: function (btnId) {
              if (btnId == 'ok') {
                Ext.each(records, function (r) {
                  addListGrid.getStore().remove(r);
                });
              }
            }
          })
        } else {
          Ext.Msg.alert('알림', '삭제하실 프로그램을 선택해주세요.');
        }
      }
    });

    var width = addListGrid.topToolbar.getWidth();

    var toolbarCenter = (width / 2) - 26;
    addListGrid.topToolbar.insert(0, {
      xtype: 'tbspacer',
      width: toolbarCenter
    });

  },
  _disable: function (form) {
    // console.log(form.find('name', 'programList'));
    var programList = Ext.getCmp(this._programListId);
    // var progrmfield = form.getForm().findField('programfield');

    var button = Ext.getCmp(this._buttonId);
    var refreshButton = Ext.getCmp(this._refreshId);
    // Ext.Array.each(progrmfield, function (item, index, length) {
    //   console.log(item);
    //   // item.setDisabled(true);
    // });
    // progrmfield.setDisabled(true);
    button.disable();
    refreshButton.disable();
    programList.removeAll();
    programList.hide();
    // form.doLayout();

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
      asterisk: false,
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
      // lazyRender: true,
      store: new Ext.data.JsonStore({
        restful: true,
        proxy: new Ext.data.HttpProxy({
          method: "GET",
          url: '/api/v1/open/data-dic-code-sets/' + code + '/code-items',
          type: "rest"
        }),
        root: "data",
        fields: [
          { name: "code_itm_code", mapping: "code_itm_code" },
          { name: "code_itm_nm", mapping: "code_itm_nm" },
          { name: "id", mapping: "id" }
        ],
        listeners: {
          load: function (store, r) {
            if (!Ext.isEmpty(combo.getValue())) {
              combo.setValue(combo.getValue());
            }
            /**
             * 부처 기본값 문화관광체육부 코드값 c104176
             */
            if(Ext.isEmpty(combo.getValue())){
              Ext.each(r, function (r) {
                if (r.get('code_itm_code') == 'c104176') {
                  combo.setValue(r.get('code_itm_nm'));
                };
              });
            };
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
  /**
  * 콤보박스 목록을 코드셋에서 가져온 코드아이템 목록으로(뎁스로 되어있는 코드)
  * @param string name 콤보박스 네임
  * @param string fieldName 콤보박스 필드 네임
  * @param string code 코드 셋 코드로 코드 아이템 조회
  */
  _inCodeComboBoxDp: function (name, fieldName, code, dpCode) {
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
          url: '/api/v1/open/data-dic-code-sets/' + code + '/code-items',
          type: "rest"
        }),
        root: "data",
        fields: [
          { name: "code_itm_code", mapping: "code_itm_code" },
          { name: "code_itm_nm", mapping: "code_itm_nm" },
          { name: "code_path", mapping: "code_path" },
          { name: "id", mapping: "id" }
        ],
        listeners: {
          load: function (store, r, option) {
            /**
             * 스토어를 비운다음 부모 노드를 코드로 찾아서
             * 코드의 자식 코드 아이템들만 모은다음 
             * 다시 스토어에 넣어준다.,
             */
            store.remove(r);

            var deleteIndexArray = [];
            Ext.each(r, function (code) {
              var codeId = code.get('code_itm_code');
              if (codeId == dpCode) {
                var parentCodePath = code.get('code_path');
                Ext.each(r, function (code2, i) {
                  var id = code2.get('id');
                  var checkCodePath = parentCodePath + '/' + id;
                  if (checkCodePath != code2.get('code_path')) {
                    deleteIndexArray.push(i);
                  }
                });
              };
            });

            Ext.each(deleteIndexArray, function (deleteArrayIndex) {
              r.remove(r[deleteArrayIndex]);
            });

            store.add(r);

            if (!Ext.isEmpty(combo.getValue())) {
              combo.setValue(combo.getValue());
            }

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
  /**
   * 동의 비동의 라디오그룹 틀
   */
  _agreeOrDisagreeRadioGroup: function (label, nameIndex) {
    var _this = this;

    // 기본 동의로 변경
    var container = new Ext.Container({
      border: false,
      layout: 'hbox',
      checked: true,
      items: [{
        xtype: 'label',
        // text: label,
        html: label,
        flex: 1
      }, {
        xtype: 'radiogroup',
        flex: 1,
        // name: 'agreeOrDisAgree',
        name: 'agreeOrDisAgree' + nameIndex,
        items: [{
          boxLabel: '동의',
          // name: 'agree',
          // name: 'agreeOrDisAgree',
          name: 'agreeOrDisAgree' + nameIndex,
          width: 30,
          value: 'agree',
          checked: true,
          listeners: {
            check: function (self, checked) {
              container.checked = true;
              // var agreeCheck = Ext.getCmp(_this._agreeCheckId).items.items;
              // agreeCheck
              // _this.agreeCheck[name] = true;
              _this.agreeCheck[self.name] = checked;

              // _this.checked = true;
            },
            beforerender: function (self) {
              _this.agreeCheck[self.name] = true;
            }
          }
        }, {
          boxLabel: '비동의',
          // name: 'disagree',
          // name: 'agreeOrDisAgree',
          name: 'agreeOrDisAgree' + nameIndex,
          width: 30,
          value: 'disagree',
          // checked: true,
          listeners: {
            check: function (self, checked) {
              container.checked = false;
              // _this.checked = true;
              // Ext.getCmp(_this.submitButtonId).agreeCheck = true;
              // _this.agreeCheck[name] = false;

            }
          }
        }
        ]
      }]
    });
    return container;
  },
  /**
   * 체크 동의 표시
   */
  _agreeAllCheck: function () {
    //   var _this = this;
    var _this = this;
    var agree1 = _this.agreeCheck['agreeOrDisAgree1'];
    if (agree1) {
      return true;
    }
    // var agree2 = _this.agreeCheck['agreeOrDisAgree2'];
    // if (agree1 && agree2) {
    //   return true;
    // };
    //   Ext.each(_this.agreeCheck, function (check) {
    //     console.log(check);
    //   });
    //   // var bool = true;
    //   // Ext.each(agreeCheck, function (r) {
    //   //   if (!r.checked) {
    //   //     bool = false;
    //   //   };
    //   // });
    //   // return bool;

    //   // console.log(Ext.getCmp(_this.submitButtonId));
  },
  _adminAjax: function () {
    var _this = this;

    var form = _this.get(0).get(0).getForm();
    if (form.charger == null) {
      Ext.Ajax.request({
        url: '/api/v1/open/user/admin',
        method: 'GET',
        params: {
          user_id: 'admin'
        },
        callback: function (opt, success, res) {
          var admin = Ext.decode(res.responseText);
          form.charger = admin;

          form.findField('charger_id').setValue(admin.data.user_id);
          form.findField('charger_nm').setValue(admin.data.user_nm);
        }
      });
    } else {
      console.log(form);
      form.findField('charger_id').setValue(form.charger.data.user_id);
      form.findField('charger_nm').setValue(form.charger.data.user_nm);
    }

  },
  /**
   * 
   * @param string text 텍스트
   * @param ARRAY style 스타일 배열
   * @param stirng icon 어썸폰트4 클래스 스트링
   * @param bool brCheck 개행
   */
  HTML: function (text, style, icon, brCheck) {

    if (typeof text == "undefined" || text == null) {
      return;
    };


    var iconClass = 'class = "' + icon + '"';

    if (brCheck == false) {
      var br = '';
    } else {
      var br = '</br>';
    };

    if (typeof style == "undefined" || style == null || style == "") {
      var basicHTML = '<span' + iconClass + '>' + text + '</span>' + br;
    } else {
      var styles = '';
      var count = style.length - 1;
      Ext.each(style, function (r, i) {
        // if (count == i) {
        //   styles = styles + r + ' ';
        // } else {
        //   styles = styles + r + '; ';
        // }
        styles = styles + r + '; ';
      });

      var basicHTML = '<span ' + iconClass + 'style="' + styles + '" > ' + text + '</span > ' + br;
    };
    return basicHTML;

  },
  /**
   * 프로그램 선택할때에 리스트 그리드
   */
  _programListGrid: function () {
    var grid = new Ext.grid.GridPanel({
      height: 100,
      store: new Ext.data.ArrayStore({
        fields: [
          { name: 'folder_path_nm' },
          { name: 'folder_path' },
          { name: 'pgm_id' }
        ]
      }),
      defaults: {
        menuDisabled: true,
        sortable: false
      },
      columns: [
        { header: '프로그램명', dataIndex: 'folder_path_nm', width: 300 },
        { header: '폴더명', dataIndex: 'folder_path', sortable: true, width: 100, hidden: true },
        // { header: '프로그램ID', dataIndex: 'pgm_id', sortable: true, width: 150, hidden: true }
      ]
    });
    return grid;
  },
  _getUniqueObjectArray: function (array, key) {
    var tempArray = [];
    var resultArray = [];
    for (var i = 0; i < array.length; i++) {
      var item = array[i];
      if (temArray.include(item[key])) {
        continue;
      } else {

      }
    }
  },
  _iconAsterisk: function () {
    return '<i class="fa fa-asterisk" style="color: #f20606;" ></i>';
  }
});