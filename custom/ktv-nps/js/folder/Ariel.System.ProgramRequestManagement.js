(function () {

  Ext.ns('Ariel.System');

  Ariel.System.ProgramRequestManagement = Ext.extend(Ext.grid.GridPanel, {

    ownerCdValue: 'admin',
    chmodValue: '755',
    groupCd: 'cms_group',
    windowId: Ext.id(),
    gridId: Ext.id(),

    tbarButtonHide: false,
    permission_code: 'charge.pd',
    loadMask: true,
    layout: 'fit',
    autoScroll: true,
    border: false,
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
      if (this._getPermission(permissions)) {
        this.getTopToolbar().addButton({
          xtype: 'aw-button',
          iCls: 'fa fa-plus',
          text: '신규',
          handler: function (self) {
            // _this._requestWindow().show();
            var win = _this.createWindow('add', '', _this);
            win.show();
          }
        });
      };


      if (this._getPermission(permissions)) {
        this.getTopToolbar().addButton({
          xtype: 'aw-button',
          iCls: 'fa fa-edit',
          text: '수정',
          handler: function (self) {
            var sel = _this.getSelectionModel().getSelected();
            if (Ext.isEmpty(sel)) {
              Ext.Msg.alert('알림', '목록을 선택하여 주세요');
              return;
            } else {
              var oriStatus = sel.get('status');
              if (oriStatus !== 'request') return Ext.Msg.alert('알림', '요청상태가 아닙니다.');

              var win = _this.createWindow('edit', sel.data, _this);
              win.show();
            }
          }
        });
      };

      if (this._getPermission(permissions)) {
        this.getTopToolbar().addButton({
          hidden: _this.tbarButtonHide,
          xtype: 'aw-button',
          iCls: 'fa fa-ban',
          text: '삭제',
          handler: function (self) {
            var sel = _this.getSelectionModel().getSelected();
            if (Ext.isEmpty(sel)) {
              Ext.Msg.alert('알림', '목록을 선택하여 주세요');
              return;
            } else {
              var id = sel.get('id');


              Ext.Msg.show({
                title: '삭제',
                msg: '프로그램을 삭제 하시겠습니까?',
                buttons: Ext.Msg.YESNO,
                animEl: 'elId',
                fn: function (btnId, text, opts) {
                  if (btnId == 'no') return;
                  var waitMsg = Ext.Msg.wait('처리 중입니다.', '처리중...');

                  Ext.Ajax.request({
                    method: 'DELETE',
                    url: '/api/v1/folder-mng-requests/' + id,
                    callback: function (opt, suc, res) {
                      waitMsg.hide();
                      if (suc) {
                        var r = Ext.decode(res.responseText);
                        if (r.success) {
                          _this.getStore().reload();
                          Ext.Msg.alert('알림', '삭제되었습니다.');
                        }
                        else {
                          Ext.Msg.alert('알림', r.msg);
                        }
                      }
                      else {
                        Ext.Msg.alert('오류', res.responseText);
                      }
                    }
                  });
                }
              });
            }
          }
        });
      };

      if (true) {
        this.getTopToolbar().addButton({
          hidden: _this.tbarButtonHide,
          xtype: 'aw-button',
          iCls: 'fa fa-check',
          text: '승인',
          handler: function (self) {
            var sel = _this.getSelectionModel().getSelected();
            if (Ext.isEmpty(sel)) {
              Ext.Msg.alert('알림', '목록을 선택하여 주세요');
              return;
            } else {
              _this._updateStatusAjax('approval');
            }
          }
        });
      };
      if (true) {
        this.getTopToolbar().addButton({
          hidden: _this.tbarButtonHide,
          xtype: 'aw-button',
          iCls: 'fa fa-times',
          text: '반려',
          handler: function (self) {
            var sel = _this.getSelectionModel().getSelected();
            if (Ext.isEmpty(sel)) {
              Ext.Msg.alert('알림', '목록을 선택하여 주세요');
              return;
            } else {
              _this._updateStatusAjax('reject');
            }
          }
        });
      }

      this.getTopToolbar().doLayout();

    },
    initComponent: function () {
      this._initialize();

      Ariel.System.ProgramRequestManagement.superclass.initComponent.call(this);
    },
    _initialize: function () {
      var _this = this;
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
      this.searchDateCombo = new Ext.form.ComboBox({
        xtype: 'combo',
        mode: 'local',
        triggerAction: 'all',
        width: 80,
        editable: false,
        store: new Ext.data.SimpleStore({
          fields: ['id', 'name'],
          data: [
            ['created_at', '등록일시'],
            ['updated_at', '수정일시']
          ]
        }),
        valueField: 'id',
        displayField: 'name',
        value: 'created_at',
        listeners: {
          select: function () {
            _this._searchStoreLoad();
          }
        }
      });
      this.store = new Ext.data.JsonStore({
        url: '/api/v1/folder-mng-requests',
        restful: true,
        remoteSort: true,
        sortInfo: {
          field: 'id',
          direction: 'DESC'
        },
        idProperty: 'id',
        root: 'data',
        fields: [
          { name: 'id', type: 'int' },
          'pgm_id',
          'reason',
          'regist_user_id',
          'approval_user_id',
          'folder_path_nm',
          'status',
          'dc',
          { name: 'created_at', type: 'date' },
          { name: 'updated_at', type: 'date' },
          'registerer',
          'approval'
        ]
      });
      this.cm = new Ext.grid.ColumnModel({
        defaults: {
          sortable: false,
          menuDisabled: true,
        },
        columns: [
          columnRowIndex(50),
          { header: '아이디', dataIndex: 'id', hidden: true },
          { header: '프로그램명', dataIndex: 'folder_path_nm', sortable: true, width: 300 },
          { header: '프로그램 아이디', dataIndex: 'pgm_id', width: 130, align: 'center' },
          { header: '사유', dataIndex: 'dc', width: 300 },
          {
            header: '상태', dataIndex: 'status', align: 'center', width: 60, renderer: function (value) {
              if (value == null) {
                return null;
              } else {
                switch (value) {
                  case 'request':
                    return '요청';
                  case 'reject':
                    return '반려';
                  case 'approval':
                    return '승인';
                  default:
                    return null;
                }
              }
            }
          },
          { header: '신청자', dataIndex: 'regist_user_id', hidden: true },
          {
            header: '신청자', dataIndex: 'registerer', renderer: function (v) {
              if (!Ext.isEmpty(v)) {
                return v.user_nm + '(' + v.user_id + ')';
              }
            }
          },
          { header: '승인자', dataIndex: 'approval_user_id', hidden: true },
          {
            header: '승인자', dataIndex: 'approval', renderer: function (v) {
              if (!Ext.isEmpty(v)) {
                return v.user_nm + '(' + v.user_id + ')';
              }
            }
          },
          { header: '등록일시', dataIndex: 'created_at', width: 150, sortable: true, renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s'), align: 'center' },
          { header: '수정일시', dataIndex: 'updated_at', width: 150, sortable: true, renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s'), align: 'center' }
        ]
      });
      this.tbar = [{
        xtype: 'aw-button',
        iCls: 'fa fa-refresh',
        text: '새로고침',
        handler: function (btn, e) {
          _this._gridReload();
        }
      }, {
        xtype: 'tbseparator'
      },
      _this.searchDateCombo,
        ' ',
      _this.startDateField,
      {
        html: '~'
      },
      _this.endDateField,
      {
        xtype: 'radioday',
        dateFieldConfig: {
          startDateField: _this.startDateField,
          endDateField: _this.endDateField
        },
        listeners: {
          change: function () {
            _this._searchStoreLoad();
          }
        }
      },
      {
        xtype: 'tbfill'
      }
      ];

    },
    createWindow: function (action, list, target) {

      var that = this;

      var ownerCdValue = that.ownerCdValue;
      var chmodValue = that.chmodValue;
      var prefixValue = that.prefixValue;
      var groupCd = that.groupCd;


      if (Ext.isEmpty(list)) {
        var title = '제작폴더 신청';
      } else {
        var title = '제작폴더 수정';
      }

      var formId = Ext.id();

      var bis_program = new Ariel.Nps.BISProgram({
        flex: 3,
        listeners: {
          selectProgram: function (self, sel) {

            var pgm_id = sel.data.pgm_id;
            var pgm_nm = sel.data.pgm_nm;

            var form = Ext.getCmp(formId).getForm();
            //if (action == 'add') {
            form.findField('pgm_id').setValue(pgm_id);
            //}
            form.findField('folder_path_nm').setValue(pgm_nm);

          }
        }
      });


      var add_win = new Ext.Window({
        layout: 'fit',
        id: target.windowId,
        code: action,
        title: title,
        width: 700,
        height: 620,
        //closeAction: 'hide',
        modal: true,
        resizable: true,
        plian: true,
        items: [{
          xtype: 'form',
          id: formId,
          border: false,
          frame: true,
          defaults: {
            anchor: '95%'
          },
          items: [{
            xtype: 'hidden',
            name: 'id'
          }, {
            xtype: 'hidden',
            name: 'step',
            value: 3
          }, {
            hidden: true,
            xtype: 'radiogroup',
            fieldLabel: '폴더 유형',
            anchor: '95%',
            columns: 2,
            items: [{
              boxLabel: '뉴스',
              name: 'parent_id',
              inputValue: '3'
            }, {
              boxLabel: '제작',
              name: 'parent_id',
              inputValue: '2',
              checked: true
            }]
          }, {
            name: 'folder_path_nm',
            xtype: 'textfield',
            //readOnly: true,
            emptyText: '프로그램명을 입력해주세요',
            fieldLabel: '프로그램명 입력'
          }, {
            name: 'pgm_id',
            xtype: 'textfield',
            fieldLabel: '프로그램ID',
            readOnly: true
          }, {
            hidden: true,
            disabled: true,
            name: 'folder_path',
            xtype: 'textfield',
            allowBlank: false,
            regex: /^[A-Za-z0-9\_+]*$/,
            emptyText: '폴더영문명 입력해주세요',
            fieldLabel: '폴더영문명 입력',
            enableKeyEvents: true,
            listeners: {
              keydown: function (e, t, o) {
                this.fireEvent('upperSetValue');
              },
              change: function (e, t, o) {
                this.fireEvent('upperSetValue');
              },
              upperSetValue: function (e, t, o) {
                this.upperSetValue();
              }
            }, upperSetValue: function () {
              var newValue = this.getValue().toLowerCase();
              //this.setValue( newValue );
              this.ownerCt.getForm().findField('group_cd').fireEvent('customSelect', newValue);
            }
          }, {
            hidden: true,
            fieldLabel: '권한',
            name: 'chmod',
            xtype: 'textfield',
            value: chmodValue,
            readOnly: true
          }, {
            hidden: true,
            fieldLabel: '소유자',
            name: 'owner_cd',
            xtype: 'textfield',
            readOnly: true,
            value: ownerCdValue
          }, {
            hidden: true,
            fieldLabel: '소유그룹',
            name: 'group_cd',
            xtype: 'textfield',
            readOnly: true,
            value: groupCd
          }, {
            name: 'quota',
            xtype: 'numberfield',
            fieldLabel: 'QUOTA(GB)',
            hidden: true,
            readOnly: true
          }, {
            name: 'using_yn',
            hidden: true,
            xtype: 'checkbox',
            checked: true,
            inputValue: 'Y',
            inputValueFalse: 'N',
            fieldLabel: '사용여부'
          }, {
            xtype: 'textarea',
            name: 'dc',
            allowBlank: false,
            fieldLabel: '<span style="color: #f20606;" >*</span>신청 사유'
          }, {
            xtype: 'fieldset',
            submitValue: false,
            checkboxToggle: true,
            checkboxName: 'bis-program',
            title: '프로그램 조회',
            height: 400,
            layout: 'fit',
            items: [
              bis_program
            ]
          }
          ],
          customGetValues: function () {
            var _this = this;
            //날짜필드 체크박스 값 보정
            var form = this;
            var values = form.getValues();
            //var fieldValues = form.getFieldValues();

            var formList = form.items.items;
            for (var i = 0; i < formList.length; i++) {
              if (formList[i].xtype == 'checkbox') {

                if (formList[i].getValue() === false) {
                  if (formList[i].inputValueFalse != undefined) {
                    values[formList[i].name] = formList[i].inputValueFalse;
                  }
                }
              } else if (formList[i].xtype == 'datefield' && !Ext.isEmpty(formList[i].getValue())) {
                values[formList[i].name] = formList[i].getValue().format("YmdHis");
              }
            }

            if (values['bis-program'] != 'on') {
              delete values['pgm_id'];
            }
            delete values['bis-program'];

            if (values['use_yn']) {
              switch (values['use_yn']) {
                case '사용':
                  values['use_yn'] = 'Y';
                  break;
                case '미사용':
                  values['use_yn'] = 'N';
                  break;
              }
            };

            if (values['dvs_yn']) {
              switch (values['dvs_yn']) {
                case '방영':
                  values['dvs_yn'] = 'N';
                  break;
                case '종방':
                  values['dvs_yn'] = 'Y';
                  break;
              }
            };

            return values;
          }
        }],
        buttons: [{
          xtype: 'aw-button',
          iCls: 'fa fa-check',
          scale: 'medium',
          text: '저장',
          handler: function () {
            var form = Ext.getCmp(target.windowId).get(0).getForm();
            
            var program_request_reason = Ext.getCmp(formId).getForm().findField('dc').getValue();
            if (!form.isValid() || program_request_reason.trim() === '') {
              Ext.Msg.alert('알림', '올바른 값을 입력해주세요');
              return false;
            }

            var formValues = form.customGetValues();

            var itemId = formValues.id;
            if (!Ext.isEmpty(itemId)) {
              var requestMethod = 'PUT';
              var requestUrl = '/api/v1/folder-mng-requests/' + itemId;
            } else {
              var requestMethod = 'POST';
              var requestUrl = '/api/v1/folder-mng-requests';
            }

            var waitMsg = Ext.Msg.wait('처리 중입니다.', '처리중...');

            Ext.Ajax.request({
              timeout: 180000,
              method: requestMethod,
              url: requestUrl,
              params: formValues,
              callback: function (opt, suc, res) {
                waitMsg.hide();
                var r = Ext.decode(res.responseText);
                if (suc) {
                  if (r.success) {

                    Ext.getCmp(target.windowId).hide();
                    that.getStore().reload();
                    Ext.getCmp(target.windowId).close();
                    console.log(r.msg);
                    if (!Ext.isEmpty(r.msg)) return Ext.Msg.alert('알림', r.msg);

                    return Ext.Msg.alert('알림', '등록완료 되었습니다.');
                  }
                  else {
                    Ext.Msg.alert('저장', r.msg);
                  }
                } else {
                  Ext.Msg.alert('오류', r.msg);
                }
              }
            });

          }
        }, {
          xtype: 'aw-button',
          iCls: 'fa fa-times',
          scale: 'medium',
          text: '닫기',
          handler: function (self) {
            self.ownerCt.ownerCt.close();
          }
        }],
        listeners: {
          afterrender: function (self) {
            if (action == 'edit') {
              if (!Ext.isEmpty(list)) {
                var form = self.get(0).getForm();
                var record = new Ext.data.Record(list);
                form.loadRecord(record);
              }
              if (form.findField('folder_path')) {
                form.findField('folder_path').setReadOnly(true);
              }
            }
          }
        }
      });
      return add_win;

    },
    _updateStatusAjax: function (updateStatus) {
      var _this = this;
      var sel = this.getSelectionModel().getSelected();
      var selectedId = sel.get('id');
      switch (updateStatus) {
        case 'approval':
          var statusText = '승인';
          break;
        case 'reject':
          var statusText = '반려';
          break;
      };
      Ext.Msg.show({
        title: '알림',
        msg: statusText + ' 하시겠습니까?',
        buttons: Ext.Msg.YESNO,
        fn: function (btnId) {
          if (btnId == 'no') return;
          var waitMsg = Ext.Msg.wait('처리 중입니다.', '처리중...');

          Ext.Ajax.request({
            timeout: 180000,
            method: 'PUT',
            url: '/api/v1/folder-mng-requests/' + selectedId + '/update-status',
            params: {
              status: updateStatus
            },
            callback: function (opt, suc, res) {
              waitMsg.hide();
              var r = Ext.decode(res.responseText);
              if (suc) {
                if (r.success) {
                  // 요청 상태값이 아닐때 msg 넣어주었다
                  if (!Ext.isEmpty(r.msg)) return Ext.Msg.alert('알림', r.msg);

                  _this._gridReload();
                  return Ext.Msg.alert('알림', statusText + ' 되었습니다.');
                }
                else {
                  Ext.Msg.alert('저장', r.msg);
                }
              } else {
                Ext.Msg.alert('오류', r.msg);
              }
            }
          });
        }
      });

    },
    _gridReload: function () {
      return this.getStore().reload();
    },
    _searchStoreLoad: function () {
      var startDate = this.startDateField.getValue();
      var endDate = this.endDateField.getValue();
      var searchDateCombo = this.searchDateCombo.getValue();
      return this.getStore().load({
        params: {
          search_date_type: searchDateCombo,
          start_date: startDate,
          end_date: endDateOf(endDate)
        }
      })
    }
  });


  return new Ariel.System.ProgramRequestManagement();
})()