(function () {
  Ext.ns('Ariel.DashBoard');

  Ariel.DashBoard.Request = Ext.extend(Ext.grid.GridPanel, {
    // private

    // dashboard : true / menu: false
    statusButtonShow: false,

    hideCnt: false,
    comboValue: 'All',


    title: '<span class="user_span"><span class="icon_title"><i class=""></i></span><span class="main_title_header">' + '의뢰' + '</span></span>',
    cls: 'grid_title_customize proxima_customize',
    loadMask: true,
    stripeRows: true,
    frame: false,
    viewConfig: {
      emptyText: '목록이 없습니다.',
      border: false,
      getRowClass: function (record, rowIndex, rp, ds) {
        if (!this.grid.statusButtonShow) {
          return 'custom-grid-row';
        };
      },
    },

    initComponent: function () {

      this._initialize();

      Ariel.DashBoard.Request.superclass.initComponent.call(this);
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
      // if (this._getPermission(permissions)) {
      if (true) {
        _this.getTopToolbar().addButton({
          hidden: _this.statusButtonShow,
          xtype: 'a-iconbutton',
          text: '의뢰 추가',
          handler: function (self) {
            // 나중에 패널이 바뀌어도 타입만 다른곳에서 찾아서 변경하면 됨
            var type = _this.requestTypeCombo.getValue();
            switch (type) {
              case 'video':
                var typeSe = {
                  ord_meta_cd: type,
                  dept_cd: 7
                };
                break;
              case 'graphic':
                var typeSe = {
                  ord_meta_cd: type,
                  dept_cd: 6
                };
                break;
            };
            _this._requestDetailWindow(null, 'add');
          }
        });
      };
      // if (this._getPermission(permissions)) {
      if (true) {
        _this.getTopToolbar().addButton({
          hidden: _this.statusButtonShow,
          xtype: 'a-iconbutton',
          text: '수정',
          handler: function (self) {
            var sm = _this.getSelectionModel();
            if (sm.hasSelection()) {
              var ordId = sm.getSelected().get('ord_id');
              _this._requestDetailWindow(ordId, 'edit');
            } else {
              Ext.Msg.alert('알림', '수정할 목록을 선택해주세요.');
            }
          }
        });
      };
      // if (this._getPermission(permissions)) {
      if (false) {
        _this.getTopToolbar().addButton({
          hidden: _this.statusButtonShow,
          xtype: 'a-iconbutton',
          text: '배정',
          handler: function (self) {
            var sm = _this.getSelectionModel();
            if (sm.hasSelection()) {
              var ordId = sm.getSelected().get('ord_id');
              _this._userSelectWindow(ordId, _this.store);
            } else {
              Ext.Msg.alert('알림', '배정할 목록을 선택해주세요.');
            }

          }
        });
      };

      if (this._getPermission(permissions)) {
        _this.getTopToolbar().addButton({
          hidden: _this.statusButtonShow,
          xtype: 'a-iconbutton',
          text: '의뢰 진행',
          handler: function (self) {
            var sm = _this.getSelectionModel();
            if (sm.hasSelection()) {
              // 선택한 의뢰 아이디
              var ordId = sm.getSelected().get('ord_id');
              var status = sm.getSelected().get('ord_status');
              var changeStatus = 'working';
              if (!(status == changeStatus)) {
                _this._updateStatus(ordId, changeStatus);
              } else {
                Ext.Msg.alert('알림', '이미 변경된 상태입니다.');
              }
            } else {
              Ext.Msg.alert('알림', '의뢰 진행할 목록을 선택해주세요.');
            }
          }
        });
      };

      if (this._getPermission(permissions)) {
        _this.getTopToolbar().addButton({
          hidden: _this.statusButtonShow,
          xtype: 'a-iconbutton',
          text: '의뢰 완료',
          handler: function (self) {
            var sm = _this.getSelectionModel();

            if (sm.hasSelection()) {

              // 선택한 의뢰 아이디
              var ordId = sm.getSelected().get('ord_id');
              var status = sm.getSelected().get('ord_status');
              var changeStatus = 'complete';
              if (!(status == changeStatus)) {
                _this._updateStatus(ordId, changeStatus);
              } else {
                Ext.Msg.alert('알림', '이미 변경된 상태입니다.');
              }

            } else {
              Ext.Msg.alert('알림', '의뢰 진행할 목록을 선택해주세요.');
            }
          }
        });
      };
      if (this._getPermission(permissions)) {
        _this.getTopToolbar().addButton({
          hidden: _this.statusButtonShow,
          xtype: 'a-iconbutton',
          text: '의뢰 취소',
          handler: function (self) {
            var sm = _this.getSelectionModel();
            if (sm.hasSelection()) {
              var ordId = sm.getSelected().get('ord_id');
              var status = sm.getSelected().get('ord_status');
              if ((status === 'ready') || (status === 'working')) {
                Ext.Msg.show({
                  title: '알림',
                  msg: '의뢰를 취소 하시겠습니까?',
                  buttons: Ext.Msg.OK,
                  fn: function (btnId) {
                    if (btnId == 'ok') {
                      Ext.Ajax.request({
                        method: 'POST',
                        url: '/api/v1/request/' + ordId + '/update-status-cancel',
                        callback: function (options, success, response) {
                          if (success) {
                            try {
                              Ext.Msg.alert('알림', '의뢰가 취소 되었습니다.');
                              _this.getStore().reload();
                            } catch (e) {
                              Ext.Msg.alert(e.name, e.message);
                            }
                          } else {
                            Ext.Msg.alert(_text('MN01098'), response.statusText);
                          }
                        }
                      });
                    }
                  }
                });
              } else {
                Ext.Msg.alert('알림', '요청 또는 진행중인 상태에만 취소 할 수 있습니다.');
              }
            }

          }
        });
      }

      if (this._getPermission(permissions)) {
        _this.getTopToolbar().addButton({
          hidden: _this.statusButtonShow,
          xtype: 'a-iconbutton',
          text: '의뢰 삭제',
          handler: function (btnId) {

            var sm = _this.getSelectionModel();


            if (sm.hasSelection()) {
              var ordId = sm.getSelected().get('ord_id');
              var status = sm.getSelected().get('ord_status');
              if (status == 'complete') {

                return Ext.Msg.alert('알림', '완료 상태가 아닌 목록에서 요청해주세요.');
              } else {
                Ext.Msg.show({
                  title: '알림',
                  msg: '의뢰를 삭제 하시겠습니까?',
                  buttons: Ext.Msg.OKCANCEL,
                  fn: function (btnId) {
                    if (btnId == 'ok') {
                      Ext.Ajax.request({
                        method: 'POST',
                        params: {
                          'status': status
                        },
                        url: '/api/v1/request/' + ordId + '/delete',
                        callback: function (options, success, response) {
                          var r = Ext.decode(response.responseText, true);
                          if (r.success) {
                            try {
                              Ext.Msg.alert('알림', '의뢰가 삭제 되었습니다.');
                              _this.getStore().reload();
                            } catch (e) {
                              Ext.Msg.alert(e.name, e.message);
                            }
                          } else {
                            Ext.Msg.alert(_text('MN00023'), r.msg);
                          }
                        }
                      });
                    }

                  }
                });
              }

            } else {
              Ext.Msg.alert('알림', '의뢰 삭제할 목록을 선택해주세요.');
            }

          }
        });
      }

      _this.getTopToolbar().doLayout();
    },
    _initialize: function () {
      /**
       * 1.그리드패널 만들기
       * 2.콤보박스로 스토어 바꾸어서 영상편집의뢰 랑 그래픽의뢰 리스트 조회 하기
       * 3.라디오그룹(전체,요청,진행중,완료) 클릭시 요청상태에 따라 스토어 리로드
       */
      var _this = this;


      // search fields
      this.requestTypeCombo = _this._inCodeComboBox('REQEST_TY_SE');
      this.graphicReqestTyCombo = _this._graphicReqestTyCombo();
      this.requestStatusRadioGroup = new Ext.form.RadioGroup({
        name: 'ord_status',
        width: 260,
        columns: [.19, .19, .24, .19, .19],
        items: [
          { boxLabel: '전체', name: 'ord_status', inputValue: 'all', checked: true },
          { boxLabel: '요청', name: 'ord_status', inputValue: 'ready' },
          { boxLabel: '진행중', name: 'ord_status', inputValue: 'working' },
          { boxLabel: '취소', name: 'ord_status', inputValue: 'cancel' },
          { boxLabel: '완료', name: 'ord_status', inputValue: 'complete' }
        ],
        listeners: {
          change: function (self, checked) {
            _this._searchStoreLoad();
          }
        }
      });
      this.myRequestCheckBox = new Ext.form.Checkbox({
        boxLabel: '내 의뢰',
        // checked: true,
        listeners: {
          check: function (self, checked) {
            _this._searchStoreLoad();
          },
          afterrender: function (self) {
            if (_this.statusButtonShow) {
              self.setValue(true);
            } else {
              self.setValue(false);
            }
          }
        }
      });

      this.store = new Ext.data.JsonStore({
        remoteSort: true,
        restful: true,
        proxy: new Ext.data.HttpProxy({
          method: 'GET',
          // url: '/api/v1/request',
          url: Ariel.DashBoard.Url.request,
          type: 'rest'
        }),
        remoteSort: true,
        totalProperty: 'total',
        root: 'data',
        baseParams: {
          type: 'video'
        },
        fields: [
          'ord_meta_cd',
          'ord_id',
          'artcl_titl',
          'artcl_id',
          'ch_div_cd',
          'ord_ctt',
          'title',
          'inputr_id',
          'ord_work_id',
          { name: 'input_dtm', type: 'date' },
          { name: 'ord_status', type: 'date' },
          { name: 'completed_dtm', type: 'date' },
          // 'completed_dtm',
          'expt_ord_end_dtm',
          'requeest_st_code',
          'work_user',
          'inputr',
          'ord_status',
          'graphic_reqest_ty',
          'graphic_reqest_ty_ln',


          // { name: 'input_dtm', type: 'date' },
        ]
      });

      var defaultCellStyle = 'font-size:15px;';
      this.cm = new Ext.grid.ColumnModel({
        defaults: {
          align: 'center',
          menuDisabled: true,
          sortable: false,
          renderer: function (v) {
            return _this._cellStyle(defaultCellStyle, v);
          }
        },
        columns: [
          {
            width: 30,
            renderer: function (v, p, record, rowIndex) {
              return _this._cellStyle(defaultCellStyle, rowIndex + 1);
            }
          },
          {
            header: '유형', dataIndex: 'ord_meta_cd', align: 'left', renderer: function (value, metaData, record, rowIndex, colIndex, store) {
              if (!(value == null)) {
                switch (value) {
                  case 'video':
                    return _this._cellStyle(defaultCellStyle, '영상편집의뢰');
                  case 'graphic':
                    return _this._cellStyle(defaultCellStyle, '그래픽의뢰');
                }
              }
            },
            width: 100,
            hidden: _this.hideCnt


          },
          {
            header: '의뢰유형', id: 'graphic_reqest_ty', dataIndex: 'graphic_reqest_ty_ln', width: 70, align: 'left',
            renderer: function (v) {
              if (isValue(v)) {
                return _this._cellStyle(defaultCellStyle, v.code_itm_nm);
              }
            }
          },
          { header: '제목', dataIndex: 'title', width: 220, align: 'left' },
          { header: '내용', dataIndex: 'ord_ctt', width: 300, align: 'left' },
          { header: '의뢰자', dataIndex: 'inputr_id', align: 'left', hidden: true },
          { header: '담당자', dataIndex: 'ord_work_id', align: 'left', hidden: true },
          {
            header: '의뢰자', dataIndex: 'inputr', align: 'left', renderer: function (value, metaData, record, rowIndex, colIndex, store) {
              if (!(value == null)) {
                return _this._cellStyle(defaultCellStyle, value.user_id + '(' + value.user_nm + ')');
              }
            },
            width: 160
          },
          {
            header: '담당자', dataIndex: 'work_user', align: 'left', renderer: function (value, metaData, record, rowIndex, colIndex, store) {
              if (!(value == null)) {
                return _this._cellStyle(defaultCellStyle, value.user_id + '(' + value.user_nm + ')');
              }
            },
            width: 160
          },
          {
            header: '요청일시', dataIndex: 'input_dtm', renderer: function (value, metaData, record, rowIndex, colIndex, store) {
              if (!(value == null)) {
                return _this._cellStyle(defaultCellStyle, _this._dateToStr(value));
              }
            },
            width: 180
          },
          {
            header: '완료일시', dataIndex: 'completed_dtm', renderer: function (value, metaData, record, rowIndex, colIndex, store) {

              if (!(value == null)) {
                return _this._cellStyle(defaultCellStyle, _this._dateToStr(value));
              }
            },
            width: 180
          },
          {
            header: '의뢰상태', dataIndex: 'requeest_st_code', renderer: function (value, metaData, record, rowIndex, colIndex, store) {
              if (!(value == null)) {
                return _this._cellStyle(defaultCellStyle, value.code_itm_nm);
              }
            },
            width: 80
          }

        ],
      });
      this.startDateField = new Ext.form.DateField({
        name: 'start_date',
        hidden: !_this.hideCnt,
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
        hidden: !_this.hideCnt,
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
        text: '<span style="position:relative;" title="' + _text('MN00139') + '"><i class="fa fa-refresh" style="font-size:13px;color:white;"></i></span>',
        cls: 'proxima_button_customize',
        width: 30,
        handler: function (self) {
          _this.store.reload();
        },
        scope: this
      },
      this.requestTypeCombo,
      this.graphicReqestTyCombo,
      {
        xtype: 'spacer',
        width: 10
      },
      {
        hidden: !_this.hideCnt,
        xtype: 'tbtext',
        text: '요청일시',
      },
        ' ',
      _this.startDateField,
      {
        hidden: !_this.hideCnt,
        html: '~'
      },
      _this.endDateField,
      {
        hidden: !_this.hideCnt,
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
        hidden: !_this.hideCnt,
        xtype: 'tbseparator'
      },
      {
        hidden: !_this.hideCnt,
        xtype: 'tbtext',
        text: '의뢰상태 : ',
      },
        ' ',
      this.requestStatusRadioGroup,
      this.myRequestCheckBox,
        '->'
      ];

      this.bbar = new Ext.PagingToolbar({
        pageSize: 30,
        store: _this.store
      });

      this.listeners = {
        afterrender: function (self) {
          var graphicReqestTyIndex = self.getColumnModel().getIndexById('graphic_reqest_ty');

          if (_this.comboValue === 'graphic') {
            self.getColumnModel().setHidden(graphicReqestTyIndex, false);
          } else {
            self.getColumnModel().setHidden(graphicReqestTyIndex, true);
          };

        },
        rowdblclick: function (self, rowIndex, e) {
          var sm = _this.getSelectionModel();

          var getRecord = sm.getSelected();
          var ordId = getRecord.get('ord_id');
          _this._requestDetailWindow(ordId, 'edit');
        }
      };


    },
    /**
     * 컬럼 데이터 타입 포멧
     * @param timeStamp value 
     */
    _columnValueDateFormat: function (value) {
      return value.replace(
        /^(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})$/,
        "$1-$2-$3 $4:$5:$6");
    },
    /**
     * 코드 아이템 목록이 들어있는 콤보박스
     * @param String code 
     */
    _inCodeComboBox: function (code) {
      var _this = this;
      var combo = new Ext.form.ComboBox({
        hidden: _this.hideCnt,
        allowBlank: false,
        editable: false,
        width: 100,
        name: 'request_ty_se',
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
              // Ariel.Nps.DashBoard
              store.data.insert(0, {
                data: {
                  code_itm_code: 'All',
                  code_itm_nm: '전체'
                }
              });
              /**
              * 처음 로딩되었을때 콤보박스에 들어갈 값
              */
              // var firstType = r[0].get('code_itm_code')
              // combo.setValue(firstType);
              combo.setValue(_this.comboValue);
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
          afterrender: function (self) {
            self.getStore().load({
              params: {
                is_code: 1
              }
            })
          },
          select: function (self, record, idx) {
            // var selectedComboValue = record.get('code_itm_code');
            _this._searchStoreLoad();
          }
        }
      });
      return combo;
    },
    /**
     * 1.콤보박스의 유형을 본다.
     * 2.진행상태 라디오 그룹의 체크 값을 본다.
     * 3.내 심의 체크박스의 체크상태를 본다
     * 
     * 콤보박스는 무저건 하나 선택되어야 하고 
     * 라디오는 전체일때 
     * 내 심의가 체크상태가 아닐떄만 조건
     * @param String type 
     */
    _searchStoreLoad: function () {
      // var typeValue = this.requestTypeCombo.getValue();
      var statusValue = this.requestStatusRadioGroup.getValue().inputValue;
      var myRequest = this.myRequestCheckBox.getValue();
      var startDate = this.startDateField.getValue();
      var endDate = this.endDateField.getValue();
      var typeValue = this.comboValue;
      var graphicReqestTy = this.graphicReqestTyCombo.getValue();
      // var search = {
      //     'type': typeValue,
      //     'status': statusValue,
      //     'myReqest': myReqest
      // };
      // Ext.encode(search);
      this.getStore().load({
        params: {
          type: typeValue,
          status: statusValue,
          my_request: myRequest,
          start_date: startDate,
          end_date: endDate,
          graphic_reqest_ty: graphicReqestTy
        }
      })
    },
    /**
     * 의뢰상태 변경 
     * @param String ordId 선택된 목록 아이디
     * @param String changeStatus 바꿀 상태
     */
    _updateStatus: function (ordId, changeStatus) {
      var _this = this;
      var updateStatusAjax = Ext.Ajax.request({
        url: Ariel.DashBoard.Url.requestStatusUpdate(ordId),
        params: {
          changeStatus: changeStatus
        },
        callback: function (options, success, response) {
          if (success) {
            try {
              Ext.Msg.alert('알림', '진행상태가 변경되었습니다.');
              _this.getStore().reload();
            } catch (e) {
              Ext.Msg.alert(e.name, e.message);
            }
          } else {
            Ext.Msg.alert(_text('MN01098'), response.statusText);
          }
        }
      });
      return updateStatusAjax;
    },
    /**
     * 담당자 유저 검색 윈도우창
     */
    _userSelectWindow: function (ordId, store) {
      var components = [
        '/custom/ktv-nps/javascript/ext.ux/Custom.UserSelectWindow.js',
        '/custom/ktv-nps/javascript/ext.ux/components/Custom.UserListGrid.js',
        '/custom/ktv-nps/javascript/api/Custom.Store.js',
        '/javascript/common.js'
      ];

      Ext.Loader.load(components, function (r) {
        var win = new Custom.UserSelectWindow({
          singleSelect: true,
          listeners: {
            ok: function () {
              var selectedUser = this._selected.get('user_id');
              // var selectedUser = this._selected.get('user_nm');
              Ext.Ajax.request({
                url: Ariel.DashBoard.Url.requestUpdateCharger(ordId),
                params: {
                  updateCharger: selectedUser
                },
                callback: function (options, success, response) {
                  Ext.Msg.show({
                    title: '알림',
                    msg: '선택하시겠습니까?',
                    buttons: Ext.Msg.OKCANCEL,
                    fn: function (btnId, text, opts) {
                      if (btnId == 'ok') {
                        if (success) {
                          try {
                            Ext.Msg.alert('알림', '담당자가 배정되었습니다.');
                            store.reload();
                            win.close();
                          } catch (e) {
                            Ext.Msg.alert(e.name, e.message);
                          }
                        } else {
                          Ext.Msg.alert(_text('MN01098'), response.statusText);
                        }
                      }
                    }
                  });
                }
              });

              // var UserSelectWindow = this;
              // var selectedUser = UserSelectWindow._selected.data;
              // Ext.Msg.show({
              //     title: "알림",
              //     msg: "선택하시겠습니까?",
              //     buttons: Ext.Msg.OKCANCEL,
              //     fn: function (btnId, text, opts) {
              //         if (btnId == "ok") {
              //             form.setValues(selectedUser);
              //             UserSelectWindow.close();
              //         }
              //     }
              // });
            }
          }
        }).show();
      });
    },
    _addFormWindow: function (typeSe, store) {
      /**
        *  제목필드,내용필드,확인/취소 버튼이 있고
        *  확인버튼 누를때 아작스 호출 후 제목, 내용, 분류된 그룹 아이디 넘기기
        * 
        * 1.  그래픽편집 의뢰 인지 영상편집 의뢰인지 분류
        * 2-1 그래픽편집의뢰 에서 추가 할때에는 cg팀장 / member_group_id = 7
        * 2-2 영상편집의뢰 에서 추가 할때에는 디편실편집의뢰 / member_group_id = 6
        * 3. api에서 부서코드 dept_cd 필드에 위에 그룹 아이디를 넣으면 된다
        * 4. api에서 세션정보에서 유저아이디로 의뢰자 필드 inputr_id 에 넣으면 된다
        * 5.ord_meta_cd 가 비디오 인지 그래픽 인지 분류해서 넣기
        */
      var _this = this;
      var form = new Ext.form.FormPanel({
        defaultType: 'textfield',
        padding: 5,
        defaults: {
          anchor: '95%'
        },

        items: [{
          fieldLabel: '제목',
          allowBlank: false,
          name: 'title'
        }, {
          xtype: 'textarea',
          fieldLabel: '내용',
          height: 200,
          name: 'ord_ctt'
        }]
      });

      var win = new Ext.Window({
        title: '의뢰 추가',
        width: 1000,
        modal: true,
        items: form,
        buttons: [{
          text: '추가',
          scale: 'medium',
          handler: function (self) {
            var getForm = form.getForm();
            if (!getForm.isValid()) {
              Ext.Msg.show({
                title: '알림',
                msg: '입력되지 않은 값이 있습니다.',
                buttons: Ext.Msg.OK,
              });
              return;
            }
            var formValue = getForm.getValues();
            Ext.Ajax.request({
              url: Ariel.DashBoard.Url.request,
              method: 'POST',
              params: {
                requestData: Ext.encode(formValue),
                typeSe: Ext.encode(typeSe)
              },
              callback: function (opts, success, resp) {
                if (success) {
                  try {
                    store.reload();
                    win.close();
                    Ext.Msg.alert('알림', '요청되었습니다.');
                  } catch (e) {
                    Ext.Msg.alert(e['name'], e['message']);
                  }
                } else {
                  Ext.Msg.alert('status: ' + resp.status, resp.statusText);
                }
              }
            });
          }
        },
        {
          text: '취소',
          scale: 'medium',
          handler: function (self) {
            win.close();
          }
        }]
      });
      return win.show();
    },
    /**
     * timeStamp 값 포멧
     * @param timeStamp format 
     */
    _dateToStr: function (format) {

      var year = format.getFullYear();

      var month = format.getMonth() + 1;

      if (month < 10) month = '0' + month;

      var date = format.getDate();

      if (date < 10) date = '0' + date;

      var hour = format.getHours();

      if (hour < 10) hour = '0' + hour;

      var min = format.getMinutes();

      if (min < 10) min = '0' + min;

      var sec = format.getSeconds();

      if (sec < 10) sec = '0' + sec;

      return year + "-" + month + "-" + date + " " + hour + ":" + min + ":" + sec;

    },
    _requestDetailWindow: function (ordId, type) {
      var _this = this;


      var sm = _this.getSelectionModel();
      if (sm.hasSelection()) {
        var record = sm.getSelected();
        var ordTy = record.get('ord_meta_cd');
      } else {
        var ordTy = _this.requestTypeCombo.getValue();
      }

      var requestGrid = this;
      switch (ordTy) {
        case 'video':
          var typeSe = {
            ord_meta_cd: ordTy,
            dept_cd: 7
          };
          break;
        case 'graphic':
          var typeSe = {
            ord_meta_cd: ordTy,
            dept_cd: 6
          };
          break;
      };
      var components = [
        '/lib/extjs/examples/ux/BufferView.js',
        '/custom/ktv-nps/js/custom/Custom.RequestDetailWindow.js'
      ];
      var components = Ariel.versioning.numberingScripts(components);
      Ext.Loader.load(components, function (r) {
        Ext.Ajax.request({
          // url: Ariel.DashBoard.Url.requestId('201604081747OR00008'),
          url: Ariel.DashBoard.Url.requestId(ordId),
          callback: function (options, success, response) {
            if (success) {
              try {
                var res = Ext.decode(response.responseText);

                if (type == 'edit') {
                  new Custom.RequestDetailWindow({
                    action: 'show_detail',
                    label_width: 50,
                    data: res.data,
                    type: type,
                    typeSe: typeSe,
                    requestGrid: requestGrid
                  }).show();
                } else if (type == 'add') {
                  new Custom.RequestDetailWindow({
                    // height: 450,
                    action: 'show_detail',
                    label_width: 50,
                    type: type,
                    typeSe: typeSe,
                    requestGrid: requestGrid
                  }).show();
                }

              } catch (e) {
                Ext.Msg.alert(e.name, e.message);
              }
            } else {
              Ext.Msg.alert(_text('MN01098'), response.statusText);
            }
          }
        });
      });


    },
    _gridFile: function () {
      var _this = this;
      if (_this.data.artcl_id == null) {
        var uploadCheck = false;
      } else {
        var uploadCheck = true;
      }
      var store_file = new Ext.data.JsonStore({
        url: '/store/request_zodiac/request_list.php',
        root: 'data',
        totalProperty: 'total',
        autoLoad: true,
        fields: [
          { name: 'ord_id' },
          { name: 'file_path' },
          { name: 'file_name' }
        ],
        listeners: {
          beforeload: function (self, opts) {
            opts.params = opts.params || {};

            Ext.apply(opts.params, {
              action: 'list_file',
              ord_id: _this.data.ord_id
            });
          },
          load: function (store, records, opts) {
            //myMask.hide();
          }
        }
      });
      var gridFile = new Ext.grid.GridPanel({
        title: _text('MN01045'),//'첨부파일'
        loadMask: true,
        cls: 'proxima_customize',
        enableDD: false,
        store: store_file,
        height: 160,
        border: false,
        autoScroll: true,
        frame: false,
        flex: 4,
        plain: true,
        selModel: new Ext.grid.RowSelectionModel({
          singleSelect: false,
          listeners: {
            rowselect: function (self) {
            },
            rowdeselect: function (self) {
            }
          }
        }),
        view: new Ext.ux.grid.BufferView({
          scrollDelay: false,
          forceFit: true,
          emptyText: _text('MSG00148')//결과 값이 없습니다
        }),
        listeners: {
        },
        tbar: ['->', {
          hidden: uploadCheck,
          cls: 'proxima_button_customize',
          width: 30,
          text: '<span style="position:relative;top:1px;" title="' + _text('MN00399') + '"><i class="fa fa-upload" style="font-size:13px;color:white;"></i></span>',
          handler: function (self) {
            _this._uploadWindow(gridFile);
          }
        }, {
            xtype: 'button',
            cls: 'proxima_button_customize',
            width: 30,
            text: '<span style="position:relative;top:1px;" title="' + _text('MN00050') + '"><i class="fa fa-download" style="font-size:13px;color:white;"></i></span>',//'다운로드'
            handler: function (btn, e) {
              if (btn.ownerCt.ownerCt.selModel.getSelections().length < 1) {
                Ext.Msg.alert(_text('MN00023'), _text('MSG02001'));
                return;
              } else {
                Ext.Msg.show({
                  title: _text('MN00023'),//알림
                  msg: _text('MSG02030'),//다운로드 하시겠습니까?
                  buttons: Ext.Msg.OKCANCEL,
                  fn: function (btn) {
                    if (btn == 'ok') {
                      // _this._clickBtn(gridFile.getSelectionModel().getSelections());
                      // _this._downLoad(gridFile.getSelectionModel().getSelections());
                      _this._downLoadBtn(gridFile.getSelectionModel().getSelections());

                    }
                  }
                });
              }
            }
          }],
        cm: new Ext.grid.ColumnModel({
          defaults: {
            sortable: true,
            align: 'center'
          },
          columns: [
            new Ext.grid.RowNumberer(),
            { header: 'ID', dataIndex: 'ord_id', width: 60, hidden: true },
            { header: '파일 이름', dataIndex: 'file_name', width: 60 }
          ]
        })
      });
      return gridFile;
    },
    _downLoadBtn: function (files) {
      var _this = this;

      Ext.each(files, function (file) {
        var filePath = file.get('file_path');
        var fileName = file.get('file_name');
        var ordId = _this.data.ord_id;
        // var url = Ariel.DashBoard.Url.downloadPath(filePath, fileName) + '&type=attach&ord_id = '.ordId;
        // var url = Ariel.DashBoard.Url.downloadPath(filePath, fileName, 'attach', ordId);
        var url = Ariel.DashBoard.Url.downloadPath(filePath, fileName, 'attach');
        var aTag = document.getElementById('downloadImg');

        if (aTag) {
          document.body.removeChild(aTag);
        }

        aTag = document.createElement('a');

        aTag.setAttribute('href', url);
        aTag.setAttribute('download', '');
        aTag.setAttribute('style', 'display:none;');
        aTag.setAttribute('id', 'downloadImg');
        aTag.innerHTML = 'downloadImg';

        document.body.appendChild(aTag);
        aTag.click();
      });

    },
    _cellStyle: function (styleString, v) {
      if (this.statusButtonShow) {
        return v;
      } else {
        return '<span style="' + styleString + '">' + v + '</span>'
      }
    },
    _graphicReqestTyCombo: function () {
      var _this = this;
      var graphicReqestTyHide = true;
      if ((!_this.statusButtonShow) && (_this.comboValue == 'graphic')) {
        graphicReqestTyHide = false;
      };

      var combo = new Ext.form.ComboBox({
        hidden: graphicReqestTyHide,
        allowBlank: false,
        editable: false,
        width: 80,
        name: 'graphic_request_ty',
        mode: "local",
        displayField: 'code_itm_nm',
        valueField: 'code_itm_code',
        hiddenValue: 'code_itm_code',
        typeAhead: true,
        triggerAction: 'all',
        lazyRender: true,
        value: 'All',
        store: new Ext.data.JsonStore({
          restful: true,
          proxy: new Ext.data.HttpProxy({
            method: "GET",
            url: '/api/v1/open/data-dic-code-sets/' + 'GRAPHIC_REQEST_TY' + '/code-items',
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
              var comboRecord = Ext.data.Record.create([
                { name: "code_itm_code" },
                { name: "code_itm_nm" }
              ]);
              var allComboMenu = {
                code_itm_code: "All",
                code_itm_nm: "전체"
              };
              var addComboMenu = new comboRecord(allComboMenu);
              store.insert(0, addComboMenu);
              var firstValueCheck = self.valueField;

              var firstValue;
              if (isValue(firstValueCheck)) {
                firstValue = store.data.items[0].data[firstValueCheck];
              } else {
                firstValue = 'All';
              }

              combo.setValue(firstValue);
            }
          }
        }),
        listeners: {
          beforerender: function (self) {
          },
          afterrender: function (self) {

            self.getStore().load({
              params: {
                is_code: 1
              }
            });

          },
          select: function (self, record, idx) {
            _this._searchStoreLoad();
          }
        }
      });
      return combo;
    }

  });
  // return new Ariel.DashBoard.Request();
})()