(function () {
  Ext.ns('Custom');
  Custom.UserRequest = Ext.extend(Ext.grid.GridPanel, {
    // 홈화면 
    _home: false,
    _isAdmin: true,
    permission_code: 'member_request',


    title: '<span class="user_span"><span class="icon_title"><i class=""></i></span><span class="main_title_header">' + '사용자 신청 관리' + '</span></span>',
    cls: 'grid_title_customize proxima_customize',
    loadMask: true,
    stripeRows: true,
    frame: false,
    viewConfig: {
      emptyText: '목록이 없습니다.',
      border: false
    },
    initComponent: function () {
      this._initialize();
      Custom.UserRequest.superclass.initComponent.call(this);
    },
    _getPermission: function (permissions, current) {
      var rtn = false;
      Ext.each(permissions, function (permission) {
        if (permission == '*') {
          rtn = true;
        } else if (permission == current) {
          rtn = true;
        }
      });
      return rtn;
    },
    _initializeByPermission: function (permissions) {
      var _this = this;
      var permissionsCheck = this._getPermission(permissions);
      var topToolbar = _this.getTopToolbar();

      // 툴바 홈화면 매뉴 / 시스템 매뉴 에 따라 버튼 여백 다르게
      switch (_this._home) {
        case true:
          topToolbar.add(new Ext.Toolbar.Fill({}));
          break;
        case false:
          topToolbar.add(new Ext.Toolbar.Separator({}));
          break;
      }


      if (permissionsCheck) {
        topToolbar.addButton({
          xtype: 'a-iconbutton',
          text: '수정',
          hidden: _this._home,
          handler: function (self) {
            var sm = _this.getSelectionModel();

            if (sm.hasSelection()) {
              _this._signUpWindow();
            } else {
              Ext.Msg.alert('알림', '수정할 목록을 선택해 주세요.');
            }
          }
        });
      };

      if (permissionsCheck) {
        topToolbar.addButton({
          xtype: 'a-iconbutton',
          text: '승인',
          hidden: !_this._home,
          handler: function (self) {
            var sm = _this.getSelectionModel();

            if (sm.hasSelection()) {
              var id = sm.getSelected().get('id');
              var nowStatus = sm.getSelected().get('pd_status');
              // 요청상태가 아니면 상태변경 할 수 없다.
              if (!(nowStatus == 'request')) {
                return Ext.Msg.alert('알림', '요청상태가 아닙니다.');
              }
              _this._changeStatus(id, 'approval', false);
            } else {
              Ext.Msg.alert('알림', '승인할 목록을 선택해 주세요.');
            }
          }
        });
      };

      if (permissionsCheck) {
        topToolbar.addButton({
          xtype: 'a-iconbutton',
          text: '승인',
          hidden: _this._home,
          handler: function (self) {
            _this._changeStatusHandler('approval');

          }
        });
      };

      if (permissionsCheck) {
        topToolbar.addButton({
          xtype: 'a-iconbutton',
          text: '반려',
          hidden: !_this._home,
          handler: function (self) {
            var sm = _this.getSelectionModel();

            if (sm.hasSelection()) {
              var id = sm.getSelected().get('id');
              var nowStatus = sm.getSelected().get('pd_status');
              // 요청상태가 아니면 상태변경 할 수 없다.
              if (!(nowStatus == 'request')) {
                return Ext.Msg.alert('알림', '요청상태가 아닙니다.');
              }
              _this._changeStatus(id, 'reject', false);
            } else {
              Ext.Msg.alert('알림', '승인할 목록을 선택해 주세요.');
            }
          }
        });
      };

      if (permissionsCheck) {
        topToolbar.addButton({
          xtype: 'a-iconbutton',
          text: '반려',
          hidden: _this._home,
          handler: function (self) {
            _this._changeStatusHandler('reject');
          }
        });
      };

      topToolbar.doLayout();
    },
    _initialize: function () {
      var _this = this;

      this.statusRadioGroup = new Ext.form.RadioGroup({
        name: 'status',
        width: 250,
        items: [
          { boxLabel: '전체', name: 'status', inputValue: 'all', checked: true },
          { boxLabel: '요청', name: 'status', inputValue: 'request' },
          { boxLabel: '등록완료', name: 'status', inputValue: 'approval' },
          { boxLabel: '반려', name: 'status', inputValue: 'reject' }
        ],
        listeners: {
          change: function (self, checked) {
            _this._searchStoreLoad();
          }
        }
      });
      this.pdStatusRadioGroup = new Ext.form.RadioGroup({
        name: 'pd_status',
        width: 250,
        items: [
          { boxLabel: '전체', name: 'pd_status', inputValue: 'all', checked: true },
          { boxLabel: '요청', name: 'pd_status', inputValue: 'request' },
          { boxLabel: '승인', name: 'pd_status', inputValue: 'approval' },
          { boxLabel: '반려', name: 'pd_status', inputValue: 'reject' }
        ],
        listeners: {
          change: function (self, checked) {
            _this._searchStoreLoad();
          }
        }
      });

      this.statusCombo = this._inCodeComboBox('MEMBER_REQUEST_STTUS');
      this.pdStatusCombo = this._inCodeComboBox('MEMBER_REQUEST_STTUS');
      // this.statusCombo = new Ext.form.ComboBox({
      //   xtype: 'combo',
      //   itemId: 'status',
      //   mode: 'local',
      //   store: [
      //     ['all', '전체상태'],
      //     ['request', '요청'],
      //     ['approval', '승인'],
      //     ['reject', '반려']
      //   ],
      //   value: 'all',
      //   width: 90,
      //   triggerAction: 'all',
      //   typeAhead: true,
      //   editable: false
      // });
      // this.pdStatusCombo = new Ext.form.ComboBox({
      //   xtype: 'combo',
      //   itemId: 'pd_status',
      //   mode: 'local',
      //   store: [
      //     ['all', '전체상태'],
      //     ['request', '요청'],
      //     ['approval', '승인'],
      //     ['reject', '반려']
      //   ],
      //   value: 'all',
      //   width: 90,
      //   triggerAction: 'all',
      //   typeAhead: true,
      //   editable: false
      // });



      this.store = new Ext.data.JsonStore({
        remoteSort: true,
        restful: true,
        proxy: new Ext.data.HttpProxy({
          method: 'GET',
          url: '/api/v1/users/request-list',
          type: 'rest'
        }),
        totalProperty: 'total',
        root: 'data',
        fields: [
          'id',
          'dept',
          'user_id',
          'instt',
          'mnfct_se',
          'progrm_nm',
          'charger_id',
          'password',
          'phone',
          { name: 'regist_dt', type: 'date' },
          { name: 'updt_dt', type: 'date' },
          { name: 'delete_dt', type: 'date' },
          { name: 'compt_dt', type: 'date' },
          'lxtn_no',
          'use_purps',
          'status',
          'user_nm',
          'progrm_id',
          'charger',
          'programs',
          'pd_status',
          'dept_info'
        ]
      });

      this.cm = new Ext.grid.ColumnModel({
        defaults: {
          align: 'center',
          menuDisabled: true,
          sortable: false
        },
        columns: [
          {
            header: '순번',
            renderer: function (v, p, record, rowIndex) {
              return rowIndex + 1;
            },
            width: 40
          },
          { header: 'ID', dataIndex: 'id', hidden: true },
          { header: '신청자 ID', dataIndex: 'user_id', align: 'left', width: 70 },
          { header: '패스워드', dataIndex: 'password', hidden: true },
          { header: '신청자 이름', dataIndex: 'user_nm', width: 75 },
          { header: '연락처', dataIndex: 'phone' },
          // { header: '부서', dataIndex: 'dept' },
          {
            header: '부서', dataIndex: 'dept_info', renderer: function (value) {
              if (!(value == null)) {
                return value.code_itm_nm;
              }
            }
          },
          { header: '내선번호', dataIndex: 'lxtn_no' },
          { header: '담당자 ID', dataIndex: 'charger_id', align: 'left' },
          { header: '사용목적', dataIndex: 'use_purps', width: 200, align: 'left' },
          { header: '제작구분', dataIndex: 'mnfct_se', width: 70 },
          {
            header: '프로그램 명', dataIndex: 'programs', width: 150, align: 'left',
            renderer: function (value, metaData, record, rowIndex, colIndex, store) {
              var programTitle = '';
              if (!(value == null)) {
                var valueCount = value.length;
                if (!(valueCount == 0)) {
                  Ext.each(value, function (r, i) {
                    if (!(i == valueCount - 1)) {
                      programTitle = programTitle + r.folder_path_nm + ' , ';
                    } else {
                      programTitle = programTitle + r.folder_path_nm;
                    }
                  });
                  return programTitle;
                }
              }
            }
          },
          { header: '프로그램 ID', dataIndex: 'progrm_id', hidden: true },
          { header: '패스워드', dataIndex: 'password', hidden: true },
          {
            header: '담당상태', dataIndex: 'pd_status',
            renderer: function (value, metaData, record, rowIndex, colIndex, store) {
              if (!(value == null)) {
                switch (value) {
                  case 'request':
                    return '요청';
                  case 'approval':
                    return '승인';
                  case 'reject':
                    return '반려';
                }
              }
            },
            width: 70
          },
          {
            header: '등록상태', dataIndex: 'status', renderer: function (value, metaData, record, rowIndex, colIndex, store) {
              if (!(value == null)) {
                switch (value) {
                  case 'request':
                    return '요청';
                  case 'approval':
                    return '등록완료';
                  case 'reject':
                    return '반려';
                }
              }
            },
            width: 70
          },
          { header: '신청일자', dataIndex: 'regist_dt', renderer: Ext.util.Format.dateRenderer('Y-m-d'), width: 80 },
          { header: '승인일자', dataIndex: 'compt_dt', renderer: Ext.util.Format.dateRenderer('Y-m-d'), width: 80 },
          { header: '수정일자', dataIndex: 'updt_dt', renderer: Ext.util.Format.dateRenderer('Y-m-d'), width: 80 },
          { header: '삭제일자', dataIndex: 'delete_dt', renderer: Ext.util.Format.dateRenderer('Y-m-d'), width: 80 }
        ]
      });
      this.startDateField = new Ext.form.DateField({
        name: 'start_date',
        editable: false,
        width: 105,
        format: 'Y-m-d',
        altFormats: 'Ymd|ymd|Y-m-d|y-m-d|Y/m/d|Y/m/d',
        listeners: {
          render: function (self) {
            var d = new Date();

            self.setMaxValue(d.format('Y-m-d'));
            self.setValue(d.add(Date.DAY, -7).format('Y-m-d'));
          },
          select: function () {
            _this._searchStoreLoad();
          }
        }
      });
      this.endDateField = new Ext.form.DateField({
        xtype: 'datefield',
        name: 'end_date',
        editable: false,
        width: 105,
        format: 'Y-m-d',
        altFormats: 'Ymd|ymd|Y-m-d|y-m-d|Y/m/d|Y/m/d',
        listeners: {
          render: function (self) {
            var d = new Date();

            self.setMaxValue(d.format('Y-m-d'));
            self.setValue(d.format('Y-m-d'));
          },
          select: function (self, date) {
            var startDateFieldValue = _this.startDateField.getValue();
            if (startDateFieldValue > date) {
              /**
               * 이전 날짜보다 작은 값을 선택 했을 시
               * // 이전날짜 선택시 null 값 입력
               */
              self.setValue(new Date());
              return Ext.Msg.alert('알림', '시작날짜보다 이전날짜를 선택할 수 없습니다.');;
            };
            _this._searchStoreLoad();
          }
        }
      });

      this.tbar = [{
        //>>text: '새로고침',
        cls: 'proxima_button_customize',
        width: 30,
        text: '<span style="position:relative;" title="' + _text('MN00139') + '"><i class="fa fa-refresh" style="font-size:13px;color:white;"></i></span>',
        handler: function (self) {
          _this._reloadStore();
        },
        scope: this
      }, {
        xtype: 'spacer',
        width: 10
      },
      {
        text: '요청일시',
      },
        ' ',
      _this.startDateField,
      {
        html: '~'
      },
      _this.endDateField,
      {
        xtype: 'radioday',
        width: 100,
        columns: [.50, .50],
        dateFieldConfig: {
          startDateField: _this.startDateField,
          endDateField: _this.endDateField
        },
        basicFieldHidden: {
          one: true,
        },
        checkDay: 'one',
        listeners: {
          change: function () {
            _this._searchStoreLoad();
          }
        }
      },
      {
        xtype: 'tbseparator'
      }, {
        xtype: 'label',
        text: '담당상태:',
        style: {
          'margin-left': '10px',
          'margin-right': '10px',
        }
      },
      this.pdStatusCombo,
        ' ',
      {
        xtype: 'label',
        text: '등록상태:',
        style: {
          'margin-left': '10px',
          'margin-right': '10px',
        }
      },
      this.statusCombo,
        // this.statusRadioGroup,
        // this.pdStatusRadioGroup,
        ' '];
      this.bbar = new Ext.PagingToolbar({
        displayInfo: true,
        store: _this.store
      });

      this.listeners = {
        afterrender: function (self) {
          // var _this = this;
          // self.getStore().load({
          //   params: {
          //     is_admin: _this._isAdmin
          //   }
          // });
          // _this._searchStoreLoad();
        },
        rowdblclick: function (self, idx, e) {
          // 홈화면에선 수정 못함
          if (self._home) return false;
          _this._signUpWindow();
        }
      };
    },
    /**
     * 그리드 패널 스토어 리로드
     */
    _reloadStore: function () {
      this.store.reload();
    },
    _searchStoreLoad: function () {
      var _this = this;
      // 라디오
      // var status = this.statusRadioGroup.getValue().inputValue;
      // var pdStatus = this.pdStatusRadioGroup.getValue().inputValue;
      // 콤보
      var status = this.statusCombo.getValue();
      var pdStatus = this.pdStatusCombo.getValue();
      if (Ext.isEmpty(status))
        status = 'all';

      if (Ext.isEmpty(pdStatus))
        pdStatus = 'all';


      var startDate = this.startDateField.getValue();
      var endDate = this.endDateField.getValue();
      this.store.load({
        params: {
          is_admin: _this._isAdmin,
          status: status,
          pd_status: pdStatus,
          start_date: startDate,
          end_date: endDate
        }
      });
    },
    /**
     * 아작스 호출 Member Table 에 status 값 변경
     * @param integer id 
     * @param String changeStatus 
     * @param bool isAdminStatus true:관리자 승인상태, false:PD승인상태
     */
    _changeStatus: function (id, changeStatus, isAdminStatus) {
      var _this = this;
      var params = null;
      if (isAdminStatus) {
        params = {
          change_status: changeStatus
        };
      } else {
        params = {
          change_pd_status: changeStatus
        };
      }

      return Ext.Ajax.request({
        method: 'PUT',
        url: '/api/v1/users/change-status/' + id,
        params: params,
        callback: function (opt, success, respnse) {
          if (success) {
            try {
              _this._reloadStore();
              Ext.Msg.alert('알림', '상태가 변경되었습니다.');
            } catch (e) {
              Ext.Msg.alert(e.name, e.message);
            }
          } else {
            Ext.Msg.alert(_text('MN01098'), response.statusText);//'서버 오류'
          }

        }
      });
    },
    /**
     * 상태 변경 핸들링
     * @param String status 
     */
    _changeStatusHandler: function (status) {
      var _this = this;

      switch (status) {
        case 'approval':
          var changeStatus = 'approval';
          var msg = '승인할 목록을 선택해 주세요.';
          break;
        case 'reject':
          var changeStatus = 'reject';
          var msg = '반려할 목록을 선택해 주세요.';
          break;
        default:
          return Ext.Msg.alert('알림', '없는 상태 에러');
      }

      var sm = _this.getSelectionModel();

      if (sm.hasSelection()) {
        var id = sm.getSelected().get('id');
        var nowStatus = sm.getSelected().get('status');
        var nowPDStatus = sm.getSelected().get('pd_status');

        if (status === 'approval') {
          if (!(nowPDStatus == 'approval')) {
            return Ext.Msg.alert('알림', '담당상태가 승인상태가 아닙니다.');
          }
        }

        // 요청상태가 아니면 상태변경 할 수 없다.
        if (!(nowStatus == 'request')) {
          return Ext.Msg.alert('알림', '요청상태가 아닙니다.');
        }

        _this._changeStatus(id, changeStatus, true);



      } else {
        Ext.Msg.alert('알림', msg);
      }
    },
    /**
     * 사용자 Id 신청 윈도우 폼(수정용)
     * check true, record 
     * record 값을 폼에 넣는다.
     */
    _signUpWindow: function () {
      var _this = this;
      var record = this.getSelectionModel().getSelected();

      var components = [
        '/custom/ktv-nps/javascript/ext.ux/Custom.SignUpWindow.js'
      ];
      Ext.Loader.load(components, function (r) {
        new Custom.SignUpWindow({
          rowRecord: record,
          check: true,
          width: 450,
          _reloadStore: function () {
            _this._reloadStore();
          }
        }).show();
      });
    },
    /**
    * 코드 아이템 목록이 들어있는 콤보박스
    * @param String code 
    */
    _inCodeComboBox: function (code, name) {
      var _this = this;

      var combo = new Ext.form.ComboBox({

        allowBlank: false,
        editable: false,
        width: 100,
        name: 'status',
        mode: "local",
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
            // url: Ariel.glossary.UrlSet.codeSetIdParamCodeItems(code),
            url: '/api/v1/data-dic-code-sets' + '/' + code + '/code-items',
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
              // Ariel.Nps.DashBoard
              store.data.insert(0, {
                data: {
                  code_itm_code: 'all',
                  code_itm_nm: '전체'
                }
              });
              /**
              * 처음 로딩되었을때 콤보박스에 들어갈 값
              */
              // var firstType = r[0].get('code_itm_code')
              // combo.setValue(firstType);
              combo.setValue('all');
              // combo.setValue(_this.comboValue);
              // _this._searchStoreLoad();
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
          select: function (self, record, idx) {
            // var selectedComboValue = record.get('code_itm_code');
            _this._searchStoreLoad();
          },
          afterrender: function (self) {
            self.getStore().load({
              params: {
                is_code: 1
              }
            })
          }
        }
      });
      return combo;
    },
  });

  return new Custom.UserRequest();
})()