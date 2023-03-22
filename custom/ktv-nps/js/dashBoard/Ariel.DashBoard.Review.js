(function () {
  Ext.ns('Ariel.DashBoard');

  Ariel.DashBoard.Review = Ext.extend(Ext.grid.GridPanel, {
    // dashboard 에서는 버튼 숨기기
    statusButtonShow: false,
    // comboBox에 쓸 하이든
    hideCnt: false,
    // 반려 횟수 필드
    betweenRejectCountField: null,
    _changeStatusWindow: Ext.id(),
    comboValue: 'All',
    permission_code: 'review',
    title: '<span class="user_span"><span class="icon_title"><i class=""></i></span><span class="main_title_header">' + '등록/심의' + '</span></span>',
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
      Ariel.DashBoard.Review.superclass.initComponent.call(this);
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
    _changeStatusValidation: function (status) {
      switch (status) {
        case 'approval':
          return Ext.Msg.alert('알림', '이미 승인된 상태입니다.');
        case 'reject':
          return Ext.Msg.alert('알림', '반려된 상태입니다.');
        default:
          return Ext.Msg.alert('알림', '요청 상태가 아니거나 변경할 수 없는 상태 입니다.');
      }
    },
    _initializeByPermission: function (permissions) {
      var _this = this;

      switch (_this.comboValue) {
        case 'ingest':
          var type = '심의';
          break;
        case 'content':
          var type = '등록';
          break;
      }
      _this.getTopToolbarTwo().addFill();
      if (this._getPermission(permissions)) {
        // _this.getTopToolbar().addButton({
        _this.getTopToolbarTwo().addButton({
          hidden: _this.statusButtonShow,
          xtype: 'a-iconbutton',
          text: type + ' 승인',
          handler: function (self) {

            var sm = _this.getSelectionModel();
            var sels = sm.getSelections();

            if (sm.hasSelection()) {
              if (sels.length > 1) {
                Ext.Msg.show({
                  title: '알림',
                  msg: '상태를 변경하시겠습니까?',
                  buttons: Ext.Msg.OKCANCEL,
                  fn: function (btnId, text, opts) {
                    if (btnId == 'ok') {
                      var transactionIds = [];
                      var waitMsg = Ext.Msg.wait('일괄 처리중...');
                      //건별 호출 
                      Ext.each(sels, function (sel) {
                        // 선택한 의뢰 아이디
                        var ordId = sel.get('id');
                        var contentId = sel.get('content_id');
                        var changeStatus = 'approval';
                        var transactionId = _this._updateStatus(ordId, changeStatus, contentId, true, null, null, false);
                        transactionIds.push(transactionId);
                      });
                      var startDt = new Date().getTime();
                      var interId = setInterval((function () {
                        var endDt = new Date().getTime();
                        var runningTm = endDt - startDt / 1000;
                        var isComplete = true;
                        for (var i = 0; i < transactionIds.length; i++) {
                          if (Ext.Ajax.isLoading(transactionIds[i]) == true) {
                            isComplete = false;
                          }
                        }
                        if (isComplete || (runningTm > 30)) {
                          clearInterval(interId);
                          waitMsg.hide();
                          Ext.Msg.alert('알림', '진행상태가 변경되었습니다.');
                          _this.getStore().reload();
                        }
                      }), 1000);
                    }
                  }
                });
              } else {
                // 선택한 의뢰 아이디
                var ordId = sm.getSelected().get('id');
                var status = sm.getSelected().get('review_reqest_sttus');
                var contentId = sm.getSelected().get('content_id');
                var reviewUser = sm.getSelected().get('review_user_id');
                var changeStatus = 'approval';

                if (!(status == changeStatus) && ((status == 'request') || (status == 'reject'))) {
                  switch (_this.comboValue) {
                    case 'content':
                      Ext.Msg.show({
                        title: '알림',
                        msg: '상태를 변경하시겠습니까?',
                        buttons: Ext.Msg.OKCANCEL,
                        fn: function (btnId, text, opts) {
                          if (btnId == 'ok') {
                            // _this._updateRejectCn(ordId, changeStatus, contentId, reviewUser);
                            _this._updateStatus(ordId, changeStatus, contentId, true, null);
                          }
                        }
                      });
                      // 콘텐츠 등록 승인 버튼
                      break;
                    case 'ingest':
                      // 방송 심의 승인 버튼
                      Ext.Msg.show({
                        title: '알림',
                        msg: '상태를 변경하시겠습니까?',
                        buttons: Ext.Msg.OKCANCEL,
                        fn: function (btnId, text, opts) {
                          if (btnId == 'ok') {
                            _this._updateStatus(ordId, changeStatus, contentId, true, null);
                          }
                        }
                      });
                      break;
                  }
                } else {
                  // request:, approval , reject
                  _this._changeStatusValidation(status);
                }

              }
            } else {
              Ext.Msg.alert('알림', '의뢰 진행할 목록을 선택해주세요.');
            }
          }
        });
        if (_this.comboValue != 'ingest') {
          _this.getTopToolbarTwo().addButton({
            hidden: _this.statusButtonShow,
            xtype: 'a-iconbutton',
            text: '승인 취소',
            handler: function (self) {
              var sm = _this.getSelectionModel();
              if (sm.hasSelection()) {
                _this.cancelChangeStatus('approval');
              } else {
                Ext.Msg.alert('알림', '의뢰 진행할 목록을 선택해주세요.');
              }
            }
          })
        }
      }
      if (this._getPermission(permissions)) {
        // _this.getTopToolbar().addButton({
        _this.getTopToolbarTwo().addButton({
          hidden: _this.statusButtonShow,
          xtype: 'a-iconbutton',
          text: type + ' 반려',
          handler: function (self) {
            var sm = _this.getSelectionModel();
            if (sm.hasSelection()) {
              // 선택한 의뢰 아이디

              var ordId = sm.getSelected().get('id');
              var contentId = sm.getSelected().get('content_id');
              var status = sm.getSelected().get('review_reqest_sttus');
              var reviewUser = sm.getSelected().get('review_user_id');
              var changeStatus = 'reject';
              if (!(status == changeStatus) && !(status == 'approval')) {
                _this._updateRejectCn(ordId, changeStatus, contentId, reviewUser);
              } else {
                _this._changeStatusValidation(status);
              }
            } else {
              Ext.Msg.alert('알림', '의뢰 진행할 목록을 선택해주세요.');
            }
          }
        });
        if (_this.comboValue != 'ingest') {
          _this.getTopToolbarTwo().addButton({
            hidden: _this.statusButtonShow,
            xtype: 'a-iconbutton',
            text: '반려 취소',
            handler: function (self) {
              _this.cancelChangeStatus('reject');
            }
          });
        }

      }
      // 콘텐츠 등록일때
      if (_this.comboValue != 'ingest') {
        if (true) {
          // _this.getTopToolbar().addButton({
          _this.getTopToolbarTwo().addButton({
            hidden: _this.statusButtonShow,
            xtype: 'a-iconbutton',
            text: '재승인 요청',
            handler: function (self) {
              var sm = _this.getSelectionModel();
              if (sm.hasSelection()) {
                var status = sm.getSelected().get('review_reqest_sttus');
                var ordId = sm.getSelected().get('id');
                var contentId = sm.getSelected().get('content_id');
                var registUser = sm.getSelected().get('regist_user_id');

                var changeStatus = 'request';
                if (status == "reject") {
                  Ext.Msg.show({
                    title: '알림',
                    msg: '상태를 변경하시겠습니까?',
                    buttons: Ext.Msg.OKCANCEL,
                    fn: function (btnId, text, opts) {
                      if (btnId == 'ok') {
                        // _this._updateStatus(ordId, changeStatus, contentId, true, registUser);
                        _this._changeRequestStatus('re');
                      }
                    }
                  });
                } else {
                  // "reject" 반려가 아니면 
                  Ext.Msg.alert('알림', '반려 상태가 아닙니다.');
                }
              } else {
                Ext.Msg.alert('알림', '재승인 요청할 목록을 선택해주세요.');
              }
            }
          });
        }
      }
      // 콘텐츠 등록일때
      if (_this.comboValue != 'ingest') {
        if (this._getPermission(permissions)) {
          // _this.getTopToolbar().addButton({
          _this.getTopToolbarTwo().addButton({
            hidden: _this.statusButtonShow,
            xtype: 'a-iconbutton',
            text: '파일삭제',
            handler: function () {
              _this._deleteFile();
            }
          });
        }
      }

      _this.getTopToolbar().doLayout();
      _this.getTopToolbarTwo().doLayout();

      // 대쉬보드에서는 두번쨰 툴바 지우기
      if (_this.statusButtonShow) {
        _this.getTopToolbar().remove(_this.getTopToolbarTwo());
      }
    },
    _initialize: function () {
      var _this = this;
      _this.registUserPhoneColumnId = Ext.id();
      if (_this.comboValue == 'content') {
        var isContent = true;
      } else {
        var isContent = false;
      };

      this.reviewTypeCombo = _this._inCodeComboBox('REVIEW_TY_SE');
      this.reviewStatusCombo = _this._statusCodeComboBox('REVIEW_REQEST_STTUS');
      this.contentTypeCombo = new Ext.form.ComboBox({
        hidden: !_this.hideCnt,
        allowBlank: false,
        editable: false,
        width: 100,
        name: 'cntnts_ty',
        mode: "local",
        displayField: 'code_itm_nm',
        valueField: 'code_itm_code',
        hiddenValue: 'code_itm_code',
        typeAhead: true,
        triggerAction: 'all',
        lazyRender: true,
        value: 'All',
        listeners: {
          beforerender: function (self) {
            self.store = _this._contentJsonStoreByCode('CNTNTS_TY', self);
          },
          afterrender: function (self) {
            self.getStore().load({
              params: {
                is_code: 1
              }
            });
          },
          select: function (self, record, idx) {

          }
        }
      });
      this.reviewStatusRadioGroup = new Ext.form.RadioGroup({
        name: 'review_reqest_sttus',
        width: 250,
        items: [
          { boxLabel: '전체', name: 'review_reqest_sttus', inputValue: 'all', checked: true },
          { boxLabel: '요청', name: 'review_reqest_sttus', inputValue: 'request' },
          { boxLabel: '승인', name: 'review_reqest_sttus', inputValue: 'approval' },
          { boxLabel: '반려', name: 'review_reqest_sttus', inputValue: 'reject' }
        ],
        listeners: {
          change: function (self, checked) {

          }
        }
      });
      this.myReviewCheckBox = new Ext.form.Checkbox({
        boxLabel: '내 심의',
        checked: false,
        listeners: {
          check: function (self, checked) {
          }
        }
      });

      this.indvdlinfoCombo = new Ext.form.ComboBox({
        hidden: !_this.hideCnt,
        width: 80,
        name:'indvdlinfo_at',
        triggerAction: 'all',
        editable: false,
        mode: 'local',
        store: [
          ['all', '전체'],
          ['Y', '검출'],
          ['N', '미검출'],
         ],
         value: 'all',
         
      });
      


      this.searchCombo = new Ext.form.ComboBox({
        hidden: !_this.hideCnt,
        width: 90,
        triggerAction: 'all',
        editable: false,
        mode: 'local',
        store: [
          ['title', '제목'],
          ['media_id', '미디어ID'],
          ['regist_user_id', '등록자'],
          ['miryfc_video', '등록기관'],
          ['progrm_nm', '프로그램명'],
          ['tme_no', '회차번호'],
          ['prod_se_nm', '제작구분'],
          // ['brdcst_stle_se', '방송형태'],
          ['matr_knd', '소재종류']
        ],
        value: 'title',
        listeners: {
          afterrender: function (self) {
            if (self.getValue() === 'miryfc_video') {
              _this.miryfcVideoCombo.show();
              _this.brdcstStleSeCombo.hide();
              _this.matrKndCombo.hide();
              _this.searchText.hide();
              _this.searchText.maskRe = null;
            } else if (self.getValue() === 'brdcst_stle_se') {
              _this.miryfcVideoCombo.hide();
              _this.brdcstStleSeCombo.show();
              _this.matrKndCombo.hide();
              _this.searchText.hide();
              _this.searchText.maskRe = null;
            } else if (self.getValue() === 'matr_knd') {
              _this.miryfcVideoCombo.hide();
              _this.brdcstStleSeCombo.hide();
              _this.matrKndCombo.show();
              _this.searchText.hide();
              _this.searchText.maskRe = null;
            } else {
              _this.miryfcVideoCombo.hide();
              _this.brdcstStleSeCombo.hide();
              _this.matrKndCombo.hide();
              _this.searchText.show();

            };
          },
          select: function (self, record, index) {

            if (self.getValue() === 'miryfc_video') {
              _this.miryfcVideoCombo.show();
              _this.brdcstStleSeCombo.hide();
              _this.matrKndCombo.hide();
              _this.searchText.hide();
            } else if (self.getValue() === 'brdcst_stle_se') {
              _this.miryfcVideoCombo.hide();
              _this.brdcstStleSeCombo.show();
              _this.matrKndCombo.hide();
              _this.searchText.hide();
            } else if (self.getValue() === 'matr_knd') {
              _this.miryfcVideoCombo.hide();
              _this.brdcstStleSeCombo.hide();
              _this.matrKndCombo.show();
              _this.searchText.hide();
              _this.searchText.maskRe = null;
            } else {
              _this.miryfcVideoCombo.hide();
              _this.brdcstStleSeCombo.hide();
              _this.matrKndCombo.hide();
              _this.searchText.show();
            }
            _this.searchText.setValue('');
            _this.brdcstStleSeCombo.setValue('All');
            _this.miryfcVideoCombo.setValue('All');
            _this.matrKndCombo.setValue('All');

          }
        }
      });
      this.searchText = new Ext.form.TextField({
        width: 130,
        listeners: {
          specialkey: function (f, e) {
            if (e.getKey() === e.ENTER) {
              _this._searchStoreLoad();
            };
          },
          afterrender: function (self) {

            if (!_this.hideCnt) {
              self.hide();
            }
          },
          show: function (self) {

          }
        }
      });

      this.miryfcVideoCombo = new Ext.form.ComboBox({
        hidden: !_this.hideCnt,
        editable: false,
        width: 100,
        name: 'miryfc_video',
        mode: "local",
        displayField: 'code_itm_nm',
        valueField: 'code_itm_code',
        hiddenValue: 'code_itm_code',
        typeAhead: true,
        triggerAction: 'all',
        lazyRender: true,
        listeners: {
          beforerender: function (self) {
            self.store = jsonStoreByCode('INSTT', self);
            // self.store.events.load = true;
            // self.store.on('load', function (store, r) {
            //     var comboRecord = Ext.data.Record.create([
            //         { name: "code_itm_code" },
            //         { name: "code_itm_nm" }
            //     ]);
            //     var allComboMenu = {
            //         code_itm_code: "",
            //         code_itm_nm: " "
            //     };
            //     var addComboMenu = new comboRecord(allComboMenu);
            //     store.insert(0, addComboMenu);

            // });
          },
          afterrender: function (self) {
            self.getStore().load({
              params: {
                is_code: 1
              }
            });
          }
        }
      });
      this.brdcstStleSeCombo = new Ext.form.ComboBox({
        hidden: !_this.hideCnt,
        editable: false,
        width: 100,
        name: 'brdcst_stle_se',
        mode: "local",
        displayField: 'code_itm_nm',
        valueField: 'code_itm_code',
        hiddenValue: 'code_itm_code',
        typeAhead: true,
        triggerAction: 'all',
        lazyRender: true,
        listeners: {
          beforerender: function (self) {
            self.store = jsonStoreByCode('BRDCST_STLE_SE', self);
            // self.store.events.load = true;
            // self.store.on('load', function (store, r) {
            //     var comboRecord = Ext.data.Record.create([
            //         { name: "code_itm_code" },
            //         { name: "code_itm_nm" }
            //     ]);
            //     var allComboMenu = {
            //         code_itm_code: "",
            //         code_itm_nm: " "
            //     };
            //     var addComboMenu = new comboRecord(allComboMenu);
            //     store.insert(0, addComboMenu);

            // });
          },
          afterrender: function (self) {
            self.getStore().load({
              params: {
                is_code: 1
              }
            });
          }
        }
      });
      this.matrKndCombo = new Ext.form.ComboBox({
        hidden: !_this.hideCnt,
        editable: false,
        width: 100,
        name: 'matr_knd',
        mode: "local",
        displayField: 'code_itm_nm',
        valueField: 'code_itm_code',
        hiddenValue: 'code_itm_code',
        typeAhead: true,
        triggerAction: 'all',
        lazyRender: true,
        listeners: {
          beforerender: function (self) {
            self.store = jsonStoreByCode('MATR_KND', self);
          },
          afterrender: function (self) {
            self.getStore().load({
              params: {
                is_code: 1
              }
            });
          }
        }
      });
      this.store = new Ext.data.JsonStore({
        restful: true,
        proxy: new Ext.data.HttpProxy({
          method: 'GET',
          url: Ariel.DashBoard.Url.reviews,
          type: 'rest'
        }),
        sortInfo: {
          field: 'regist_dt',
          direction: 'ASC' // or 'DESC' (case sensitive for local sorting)
        },
        remoteSort: true,
        totalProperty: 'total',
        root: 'data',
        fields: [
          'id',
          'review_ty_se',
          'content_id',
          'title',
          'cn',
          'updt_user_id',
          'review_user_id',
          'regist_user_id',
          'regist_user_phone',
          { name: 'regist_dt', type: 'date' },
          { name: 'updt_dt', type: 'date' },
          { name: 'compt_dt', type: 'date' },
          { name: 'reject_dt', type: 'date' },
          'review_reqest_sttus',
          'review_st_code',
          'reject_cn',
          'registerer',
          'review_user_nm',
          'status_count',
          'usr_meta',
          'ud_content_title',
          'miryfc_video',
          'reject_count',
          'reject_count_r',
          'reg_user_id',
          'media_id',
          'progrm_nm',
          'brdcst_stle_se_nm',
          'prod_se_nm',
          'delete_count',
          'is_deleted',
          'matr_knd_nm',
          'sys_video_rt',
          'indvdlinfo_at',
          'Y',
          'N',
          'edit_count'
        ],
        listeners: {
          load: function (self, record, options) {
            var reader = self.reader;
            var jsonData = reader.jsonData;
            var cm = _this.getColumnModel();
            var hasIngestGroup = jsonData.groups.ingest_group;

            var regisUserPhoneColumnIndex = cm.getIndexById(_this.registUserPhoneColumnId);
            if (isContent) {
              cm.setHidden(regisUserPhoneColumnIndex, !hasIngestGroup);
            }
          }
        }
      });

      this.cm = new Ext.grid.ColumnModel({
        defaults: {
          align: 'center',
          menuDisabled: true,
          sortable: false
        },
        columns: [
          {
            width: 30,
            renderer: function (v, p, record, rowIndex) {
              return rowIndex + 1;
            }
          },
          // { header: '순번', dataIndex: 'id', width: 70 },
          {
            header: '유형', dataIndex: 'review_ty_se', align: 'left', renderer: function (value, metaData, record, rowIndex, colIndex, store) {
              if (!(value == null)) {
                switch (value) {
                  case 'ingest':
                    return '방송 심의'
                  case 'content':
                    return '콘텐츠등록'
                }
              }
            },
            width: 100,
            hidden: _this.hideCnt
          },
          { header: '콘텐츠 유형', dataIndex: 'ud_content_title', align: 'left', },
          { header: '제작구분', dataIndex: 'prod_se_nm', align: 'left' },
          // { header: '방송형태', dataIndex: 'brdcst_stle_se_nm', align: 'left' },
          { header: '소재종류', dataIndex: 'matr_knd_nm', align: 'left' },
          { header: '제목', dataIndex: 'title', width: 220, align: 'left', sortable: true },
          {
            header: '재생길이', dataIndex: 'sys_video_rt', align: 'center',
            renderer: function (v) {
              return v;
            }
          },
          {
            header: '등록자', dataIndex: 'registerer', align: 'left', renderer: function (value, metaData, record, rowIndex, colIndex, store) {
              if (value == null) {
                return null;
              } else {
                return value.user_nm;
              }
            }
          },
          {
            header: '전화번호', id: _this.registUserPhoneColumnId, dataIndex: 'regist_user_phone', hidden: true, align: 'center', renderer: function (value, metaData, record, rowIndex, colIndex, store) {
              if (value == null) {
                return null;
              } else {
                // // if (record.json.hasOwnProperty('has_ingest_group')) {
                // var cm = _this.getColumnModel();
                // if (cm.isHidden(colIndex) && record.json.has_ingest_group) {
                //     cm.setHidden(colIndex, false);
                // }
                // // }
                return value;
              }
            }
          },
          {
            header: '개인정보검출', dataIndex: 'indvdlinfo_at', align: 'center', renderer: function (value, metaData, record, rowIndex, colIndex, store) {
              if (value == null || value == 'N') {
                return null;
              } else if (value == 'Y') {
                return '검출';
              }
            }
          },
          {
            header: '심의자', dataIndex: 'review_user_nm', align: 'left', renderer: function (value, metaData, record, rowIndex, colIndex, store) {
              if (value == null) {
                return null;
              } else {
                return value.user_nm;
              }
            }
          },
          {
            header: '의뢰상태', dataIndex: 'review_st_code', renderer: function (value, metaData, record, rowIndex, colIndex, store) {
              if (value == null) {
                return null;
              } else {
                return value.code_itm_nm;
              }
            }
          },
          { header: '심의 의견', dataIndex: 'reject_cn', width: 300, align: 'left' },
          {
            header: '등록일시', dataIndex: 'regist_dt', sortable: true, renderer: function (value, metaData, record, rowIndex, colIndex, store) {
              if (value == null) {
                return null;
              } else {
                return _this._dateToStr(value);
              }
            },
            width: 150
          },
          {
            header: '승인일시', dataIndex: 'compt_dt', sortable: true, renderer: function (value, metaData, record, rowIndex, colIndex, store) {
              if (value == null) {
                return null;
              } else {
                return _this._dateToStr(value);
              }
            }, width: 150
          },
          { header: '미디어ID', dataIndex: 'media_id', width: 130, sortable: true, align: 'center', hidden: !isContent },
          { header: '등록기관', dataIndex: 'miryfc_video', align: 'left' },

          {
            header: '프로그램명', dataIndex: 'progrm_nm', width: 200, sortable: true, align: 'left'
          },
          {
            header: '회차번호', dataIndex: 'usr_meta', width: 65, align: 'left', renderer: function (value) {
              return value.tme_no;
            }
          },
          { header: '내용', dataIndex: 'cn', width: 300, align: 'left', hidden: isContent },
          {
            header: '반려횟수', dataIndex: 'reject_count_r', renderer: function (value, metaData, record, rowIndex, colIndex, store) {
              if (value == null) {
                return 0;
              } else {
                return value;
              }
            }
          }, {
            header: '수정횟수', dataIndex: 'edit_count', renderer: function (value, metaData, record, rowIndex, colIndex, store) {
              if (value == null) {
                return 0;
              } else {
                return value;
              }
            }
          },{
            header: '삭제남은일수', dataIndex: 'delete_count', renderer: function (value, metaData, record, rowIndex, colIndex, store) {
              return value;
            }
          }, {
            header: '삭제 여부', dataIndex: 'is_deleted', align: 'center', width: 65, hidden: true, renderer: function (value) {
              if (value == 'Y') {
                return '삭제됨';
              }
            }
          },

          {
            header: '반려일시', dataIndex: 'reject_dt', sortable: true, renderer: function (value, metaData, record, rowIndex, colIndex, store) {
              if (value == null) {
                return null;
              } else {
                return _this._dateToStr(value);
              }
            }, width: 150
          }
        ],
      });

      this.sm = new Ext.grid.RowSelectionModel({
        //  singleSelect: true
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
            // 처음 로드되지 않음

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
              return Ext.Msg.alert('알림', '시작날짜보다 이전날짜를 선택할 수 없습니다.');
            };
            // 처음 로드 되지 않음

          }
        }
      });
      this.dateCheck = new Ext.form.Checkbox({
        hidden: !_this.hideCnt,
        checked: true,
        listeners: {
          afterrender: function (self) {
            self.resize();
            var value = self.getValue();
            if (value == false) {
              _this.startDateField.disable();
              _this.endDateField.disable();
            }
          },
          check: function (self, checked) {
            if (checked) {
              _this.startDateField.enable();
              _this.endDateField.enable();
            } else {
              _this.startDateField.disable();
              _this.endDateField.disable();
            }
          }
        },
        resize: function () {
          this.el.setSize(this.getWidth(), this.getHeight());
        }
      });

      var toolbarOne = new Ext.Toolbar({
        itemId: 'toolbarOne',
        items: [
          {
            //>>text: '새로고침',
            cls: 'proxima_button_customize',
            width: 30,
            text: '<span style="position:relative;" title="' + _text('MN00139') + '"><i class="fa fa-refresh" style="font-size:13px;color:white;"></i></span>',
            handler: function (self) {
              _this.store.reload();
            },
            scope: this
          },
          _this.reviewTypeCombo,

          {
            // hidden: !_this.hideCnt,
            xtype: 'spacer',
            width: 10
          },
          // {
          //     hidden: !_this.hideCnt,
          //     text: '등록일시 : ',
          // },

          {
            xtype: 'label',
            hidden: !_this.hideCnt,
            text: '등록일시 :'
          },
          _this.dateCheck,
          // ' ',
          _this.startDateField,
          {
            hidden: !_this.hideCnt,
            html: '~'
          },
          _this.endDateField,
          {
            hidden: !_this.hideCnt,
            xtype: 'radioday',
            checkDay: 'month',
            dateFieldConfig: {
              startDateField: _this.startDateField,
              endDateField: _this.endDateField
            },
            listeners: {
              change: function (self, checked) {

              }
            }
          },
          {
            hidden: !_this.hideCnt,
            xtype: 'tbseparator'
          },
          _this.contentTypeCombo,
          {
            hidden: !_this.hideCnt,
            xtype: 'tbseparator'
          },
          _this.indvdlinfoCombo,
          _this.searchCombo,
          _this.searchText,
          _this.miryfcVideoCombo,
          _this.brdcstStleSeCombo,
          _this.matrKndCombo,
          {
            hidden: !_this.hideCnt,
            cls: 'proxima_button_customize',
            width: 30,
            text: '<span style="position:relative;" title="' + _text('MN00139') + '"><i class="fa fa-search" style="font-size:13px;color:white;"></i></span>',
            handler: function (self) {
              _this._searchStoreLoad();
            }
          }, {
            hidden: !_this.hideCnt,
            xtype: 'tbseparator'
          },

          '상태 : ',

          ' ',

          this.reviewStatusCombo,
          {
            xtype: 'spacer',
            width: 10
          },
          
          '개인정보 : ',

          ' ',
          this.indvdlinfoCombo,
          {
            xtype: 'spacer',
            width: 10
          },

          // this.reviewStatusRadioGroup,
          this.myReviewCheckBox,
          {
            xtype: 'spacer',
            width: 10
          },
          {
            hidden: _this.hideCnt,
            cls: 'proxima_button_customize',
            width: 30,
            text: '<span style="position:relative;" title="' + _text('MN00139') + '"><i class="fa fa-search" style="font-size:13px;color:white;"></i></span>',
            handler: function (self) {
              _this._searchStoreLoad();
            }
          },
          '->',
          {
            hidden: true,
            xtype: 'a-iconbutton',
            text: '배정',
            handler: function (self) {
              var sm = _this.getSelectionModel();
              if (sm.hasSelection()) {
                var ordId = sm.getSelected().get('id');
                _this._userSelectWindow(ordId, _this.store);
              } else {
                Ext.Msg.alert('알림', '배정할 목록을 선택해주세요.');
              }
            }
          }
        ]
      });
      var toolbarTwo = new Ext.Toolbar({
        itemId: 'toolbarTwo',
        items: [
        ]
      });
      this.tbar = new Ext.Container({
        border: false,
        items: [
          toolbarOne,
          toolbarTwo
        ]
      });
      // this.tbar = [{
      //     //>>text: '새로고침',
      //     cls: 'proxima_button_customize',
      //     width: 30,
      //     text: '<span style="position:relative;" title="' + _text('MN00139') + '"><i class="fa fa-refresh" style="font-size:13px;color:white;"></i></span>',
      //     handler: function (self) {
      //         _this.store.reload();
      //     },
      //     scope: this
      // },
      // _this.reviewTypeCombo,
      // {
      //     // hidden: !_this.hideCnt,
      //     xtype: 'spacer',
      //     width: 10
      // },
      // {
      //     hidden: !_this.hideCnt,
      //     text: '등록일시 : ',
      // },
      //     ' ',
      // _this.startDateField,
      // {
      //     hidden: !_this.hideCnt,
      //     html: '~'
      // },
      // _this.endDateField,
      // {
      //     hidden: !_this.hideCnt,
      //     xtype: 'radioday',
      //     dateFieldConfig: {
      //         startDateField: _this.startDateField,
      //         endDateField: _this.endDateField
      //     },
      //     listeners: {
      //         change: function (self, checked) {

      //         }
      //     }
      // },
      // {
      //     hidden: !_this.hideCnt,
      //     xtype: 'tbseparator'
      // },
      // _this.contentTypeCombo,
      // {
      //     hidden: !_this.hideCnt,
      //     xtype: 'tbseparator'
      // },
      // _this.searchCombo,
      // _this.searchText,
      // _this.miryfcVideoCombo,
      // _this.brdcstStleSeCombo,
      // _this.matrKndCombo,
      // {
      //     hidden: !_this.hideCnt,
      //     cls: 'proxima_button_customize',
      //     width: 30,
      //     text: '<span style="position:relative;" title="' + _text('MN00139') + '"><i class="fa fa-search" style="font-size:13px;color:white;"></i></span>',
      //     handler: function (self) {
      //         _this._searchStoreLoad();
      //     }
      // }, {
      //     hidden: !_this.hideCnt,
      //     xtype: 'tbseparator'
      // },

      //     '상태 : ',

      //     ' ',
      // this.reviewStatusCombo,
      // {
      //     xtype: 'spacer',
      //     width: 10
      // },
      // // this.reviewStatusRadioGroup,
      // this.myReviewCheckBox,
      // {
      //     xtype: 'spacer',
      //     width: 10
      // },
      // {
      //     hidden: _this.hideCnt,
      //     cls: 'proxima_button_customize',
      //     width: 30,
      //     text: '<span style="position:relative;" title="' + _text('MN00139') + '"><i class="fa fa-search" style="font-size:13px;color:white;"></i></span>',
      //     handler: function (self) {
      //         _this._searchStoreLoad();
      //     }
      // },
      //     '->',
      // {
      //     hidden: true,
      //     xtype: 'a-iconbutton',
      //     text: '배정',
      //     handler: function (self) {
      //         var sm = _this.getSelectionModel();
      //         if (sm.hasSelection()) {
      //             var ordId = sm.getSelected().get('id');
      //             _this._userSelectWindow(ordId, _this.store);
      //         } else {
      //             Ext.Msg.alert('알림', '배정할 목록을 선택해주세요.');
      //         }
      //     }
      // }];

      this.bbar = {
        xtype: 'paging',
        pageSize: 30,
        displayInfo: true,
        store: _this.store
      };
      this.listeners = {
        afterrender: function (self) {
          _this.reviewTypeCombo.setValue(_this.comboValue);
          _this._searchStoreLoad();

          // _this.getStore().on('load', function (self, record, options) {
          //     var cm = _this.getColumnModel();
          //     cm.getIndexById(_this.registUserPhoneColumnId);
          // })

        },
        rowdblclick: function (self, rowIndex, e) {
          var _this = this;
          var record = self.getSelectionModel().getSelected();
          var contentId = record.get('content_id');

          if (record.get('is_deleted') == 'Y') {
            return Ext.Msg.alert('알림', '이미 삭제된 콘텐츠입니다.');
          }

          var components = [
            '/custom/ktv-nps/javascript/ext.ux/Custom.ParentContentGrid.js',
          ];
          Ext.Loader.load(components, function (r) {
            new Custom.ContentDetailWindow({
              content_id: contentId,
              id: _this._changeStatusWindow,
              isPlayer: true,
              isMetaForm: true,
              playerMode: 'read',
              permission_code: 'review.content',
              permission: false,
              isEasyCerti: true,
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
              _initializeByPermission: function (permission) {

                var win = this;
                if (this._getPermission(permission)) {
                  this.addButton({
                    xtype: 'aw-button',
                    text: '파일삭제',
                    handler: function (btn) {
                      _this._deleteFile();
                    }
                  });
                } else {
                  var approvalBtn = Ext.getCmp('approvalBtn');
                  var rejectBtn = Ext.getCmp('rejectBtn');

                  approvalBtn.hide();
                  rejectBtn.hide();
                }

                this.addButton({
                  xtype: 'aw-button',
                  text: '닫기',
                  handler: function (btn) {
                    win.close();
                  }
                });
                this.doLayout();
              },
              listeners: {
                afterrender: function (self) {
                  var window = self;
                  Ext.each(self.buttons, function (r) {
                    r.hide();
                  });
                  var ordId = record.get('id');

                  var status = record.get('review_reqest_sttus');
                  var contentId = record.get('content_id');
                  var reviewUser = record.get('review_user_id');

                  if (status == 'request') {
                    self.addButton({
                      xtype: 'aw-button',
                      text: '등록승인',
                      id: 'approvalBtn',
                      handler: function (btn) {
                        var changeStatus = 'approval';
                        if (!(status == changeStatus) && (status == 'request')) {
                          Ext.Msg.show({
                            title: '알림',
                            msg: '상태를 변경하시겠습니까?',
                            buttons: Ext.Msg.OKCANCEL,
                            fn: function (btnId, text, opts) {
                              if (btnId == 'ok') {
                                // _this._updateRejectCn(ordId, changeStatus, contentId, reviewUser, window);
                                _this._updateStatus(ordId, changeStatus, contentId, true, null, window);
                              }
                            }
                          });

                        } else {
                          _this._changeStatusValidation(status);
                        }
                      }
                    });
                    self.addButton({
                      xtype: 'aw-button',
                      text: '등록반려',
                      id: 'rejectBtn',
                      handler: function (btn) {
                        var changeStatus = 'reject';
                        if (!(status == changeStatus) && !(status == 'approval')) {
                          _this._updateRejectCn(ordId, changeStatus, contentId, reviewUser, window);

                        } else {
                          _this._changeStatusValidation(status);
                        }
                      }
                    });
                  }
                }
              }
            }).show();
          });
        }
      };


      //대시보드 심의/등록일때 
      if (this.statusButtonShow) {
        this.myReviewCheckBox.setValue(true);
        this.reviewStatusCombo.setValue('all');
      } else {
        this.myReviewCheckBox.setValue(false);
        this.reviewStatusCombo.setValue('request');
      }
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
        value: 'All',
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

              //_this.listeners

              /**
               * 처음 로딩되었을때 콤보박스에 들어갈 값
               */
              store.data.insert(0, {
                data: {
                  code_itm_code: 'All',
                  code_itm_nm: '전체'
                }
              });

              combo.setValue(_this.comboValue);

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

          }
        }
      });
      return combo;
    },
    _searchStoreLoad: function () {
      var typeValue = this.reviewTypeCombo.getValue();
      // var statusValue = this.reviewStatusRadioGroup.getValue().inputValue;
      var statusValue = this.reviewStatusCombo.getValue();
      var myReview = this.myReviewCheckBox.getValue();

      // if (Ext.isEmpty(statusValue)){
      //   statusValue = 'request';
      // }

      var startDate = this.startDateField.getValue();
      var endDate = this.endDateField.getValue();
      var indvdlinfoCombo = this.indvdlinfoCombo.getValue();
      var searchCombo = this.searchCombo.getValue();
      var searchText = this.searchText.getValue();
      var contentTypeValue = this.contentTypeCombo.getValue();
      var miryfcVideoCombo = this.miryfcVideoCombo.getValue();
      var brdcstStleSeCombo = this.brdcstStleSeCombo.getValue()
      var matrKndCombo = this.matrKndCombo.getValue()

      if (Ext.isEmpty(miryfcVideoCombo)) {
        miryfcVideoCombo = 'All';
      };
      if (Ext.isEmpty(brdcstStleSeCombo)) {
        brdcstStleSeCombo = 'All';
      };

      if (Ext.isEmpty(matrKndCombo)) {
        matrKndCombo = 'All';
      };


      if (this.dateCheck.getValue() == false) {
        startDate = null;
        endDate = null;
      };

      var searchParams = {
        type: typeValue,
        status: statusValue,
        myReview: myReview,
        start_date: startDate,
        end_date: endDate,
        search_combo: searchCombo,
        indvdlinfo_combo : indvdlinfoCombo,
        search_text: searchText,
        content_type: contentTypeValue,
        miryfc_video: miryfcVideoCombo,
        brdcst_stle_se: brdcstStleSeCombo,
        matr_knd: matrKndCombo
      };

      // 반려횟수 검색조건에 추가
      var toolbarOne = this.getTopToolbarOne();
      var betweenRejectCountField = toolbarOne.getComponent('betweenRejectCountField');
      if (!Ext.isEmpty(betweenRejectCountField)) {
        var betWeenRejectCountValue = betweenRejectCountField.getValue();

        if (statusValue = 'reject') {
          // 반려상태인데 반려 횟수 값이 있다면 그 값을 넣고
          if (!Ext.isEmpty(betWeenRejectCountValue)) {
            if (!Ext.isEmpty(betWeenRejectCountValue.firstNumber)) {
              searchParams.firstRejectCount = betWeenRejectCountValue.firstNumber;
            } else {
              searchParams.firstRejectCount = 0;
            }
            if (!Ext.isEmpty(betWeenRejectCountValue.lastNumber)) {
              searchParams.lastRejectCount = betWeenRejectCountValue.lastNumber;
            } else {
              searchParams.lastRejectCount = 999;
            }
          }
        }
      };
      this.getStore().load({
        params: searchParams
      })
    },
    /**
     * 담당자 유저 검색 윈도우창
     */
    _userSelectWindow: function (id, store) {
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
                url: Ariel.DashBoard.Url.reviewsUpdateCharger(id),
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
            }
          }
        }).show();
      });
    },
    /**
     * 심의상태 변경 
     * @param String id 선택된 목록 아이디
     * @param String changeStatus 바꿀 상태
     * @param Stirng 등록자 유저 아이디
     */
    _updateStatus: function (id, changeStatus, contentId, msgShow, registUser, window, afterLoading = true) {
      var _this = this;

      var updateStatusAjax = Ext.Ajax.request({
        url: Ariel.DashBoard.Url.reviewsStatusUpdate(id),
        params: {
          change_status: changeStatus,
          regist_user: registUser,
          content_id: contentId
        },
        callback: function (options, success, response) {
          if (success) {
            try {
              if (msgShow) {

                if (afterLoading) {
                  Ext.Msg.alert('알림', '진행상태가 변경되었습니다.');
                  _this.getStore().reload();
                }

                if (!Ext.isEmpty(window)) {
                  window.close();
                };
              } else {
                if (!Ext.isEmpty(window)) {
                  window.close();
                };
              }

            } catch (e) {
              Ext.Msg.alert(e.name, e.message);
            }
          } else {
            var res = Ext.decode(response.responseText);
            var errorMsg = res.msg;

            Ext.Msg.alert('알림', errorMsg);
          }
        }
      });

      return updateStatusAjax;
    },
    _changeRequestStatus: function (type) {
      // 요쳥상태로 다시 돌리는 함수
      var sm = this.getSelectionModel();
      var _this = this;
      if (sm.hasSelection()) {
        var ordId = sm.getSelected().get('id');
        var registUser = sm.getSelected().get('regist_user_id');
        var contentId = sm.getSelected().get('content_id');

        var changeRequestStatusAjax = Ext.Ajax.request({
          url: Ariel.DashBoard.Url.reviewsStatusUpdate(ordId),
          params: {
            change_status: 'request',
            regist_user: registUser,
            type: type,
            content_id: contentId
          },
          callback: function (options, success, response) {
            var res = Ext.decode(response.responseText);
            if (res.success) {
              _this.getStore().reload();
              Ext.Msg.alert('알림', '진행상태가 변경되었습니다.');
            } else {
              return Ext.Msg.alert('알림', res.msg);
            }
          }
        });

        return changeRequestStatusAjax;
      } else {
        return Ext.Msg.alert('알림', '의뢰 진행할 목록을 선택해주세요.');
      }
    },
    /**
     * 
     * @param String id  오더 아이디 t
     * @param String changeStatus  변경할 상태 값
     * @param String contentId 콘텐츠 아이디
     * @param String reviewUser 심의자 아이디
     */
    _updateRejectCn: function (id, changeStatus, contentId, reviewUser, window) {
      var _this = this;
      var textareaId = Ext.id();

      var form = new Ext.form.FormPanel({
        padding: 10,
        border: false,
        defaults: {
          anchor: '100%'
        },
        flex: 1,
        layout: 'fit',
        items: [{
          xtype: 'textarea',
          id: textareaId,
          hideLabel: true,
          name: 'reject_cn',
          allowBlank: false,
          favoriteCheckedText: [],
          listeners: {
            afterrender: function (self) {

            }
          }
        }]
      });
      var win = new Ext.Window({
        title: '심의 의견',
        width: Ext.getBody().getViewSize().width * 0.4,
        height: Ext.getBody().getViewSize().height * 0.4,
        // width: 300,
        // height: 170,
        layout: 'fit',
        border: false,
        items: new Ext.Container({
          border: false,
          layout: {
            type: 'vbox',
            align: 'stretch'
          },
          items: [
            new Ext.Container({
              border: false,
              margins: '10 10 10 20',
              items: [
                _this._makeFavoriteTextCheckBox('메타데이터 보완이 필요합니다.', 'security', textareaId),
                _this._makeFavoriteTextCheckBox('영상수정이 필요합니다.', 'modify', textareaId),
                _this._makeFavoriteTextCheckBox('중복등록.', 'overlap', textareaId),
                _this._makeFavoriteTextCheckBox('보존 가치 없음.', 'noValue', textareaId),
                _this._makeFavoriteTextCheckBox('영상 오류.', 'error', textareaId),
                _this._makeFavoriteTextCheckBox('내용 수정 후 \'재 승인 요청\'  해주세요.', 'registerAgain', textareaId)
              ]
            }),
            form
          ]
        }),
        modal: true,
        buttons: [{
          xtype: "a-iconbutton",
          scale: 'medium',
          text: '확인',
          handler: function (self, e) {
            var rejectCn = form.getForm().getValues().reject_cn;
            if (rejectCn.trim() == '') {
              Ext.Msg.alert('알림', '반려 사유를 입력해 주시기 바랍니다.');
              return;
            }
            Ext.Ajax.request({
              url: Ariel.DashBoard.Url.rejectCnUpdate(id),
              params: {
                reject_cn: rejectCn,
                review_user: reviewUser,
                content_id: contentId,
                change_status: changeStatus
              },
              callback: function (options, success, response) {
                if (success) {
                  try {

                    _this._updateStatus(id, changeStatus, contentId, false, null, window);


                    switch (changeStatus) {
                      case 'approval':
                        Ext.Msg.alert('알림', '승인 되었습니다.');
                        break;
                      case 'reject':
                        Ext.Msg.alert('알림', '반려 되었습니다.');
                        break;
                      default:
                        Ext.Msg.alert('알림', '요청 상태가 아니거나 변경할 수 없는 상태 입니다.');
                        break;
                    };

                    _this.getStore().reload();


                    win.close();
                  } catch (e) {
                    Ext.Msg.alert(e.name, e.message);
                  }
                } else {
                  var res = Ext.decode(response.responseText);
                  var errorMsg = res.msg;

                  Ext.Msg.alert('알림', errorMsg);
                  win.close();
                }
              }
            });
          }
        }, {
          xtype: "a-iconbutton",
          scale: 'medium',
          text: '취소',
          handler: function (btn, e) {
            win.close();
          }
        }]
      });

      return win.show();
    },
    /**
        * 코드 아이템 목록이 들어있는 콤보박스
        * @param String code 
        */
    _statusCodeComboBox: function (code) {
      var _this = this;

      var combo = new Ext.form.ComboBox({

        allowBlank: false,
        editable: false,
        width: 60,
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
              // combo.setValue(_this.comboValue);
              combo.setValue(combo.getValue());


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

            var toolbarOne = _this.getTopToolbarOne();
            var insertIndex = toolbarOne.items.indexOf(self) + 1;
            if (!_this.statusButtonShow) {
              // if(record.get('code_itm_code') == 'reject'){
              if (self.getValue() == 'reject') {
                // 반려일때만 반려횟수 필드 추가
                // if(!Ext.isEmpty(_this.betweenRejectCountField)){
                // 반려를 또 눌렀을떄 옆에 또생겨서 처리해놓음
                // var betweenRejectCountFieldIndex = toolbarOne.items.indexOf(_this.betweenRejectCountField);
                var betweenRejectCountField = toolbarOne.getComponent('betweenRejectCountField');
                var betweenRejectCountFieldIndex = toolbarOne.items.indexOf(betweenRejectCountField);
                if (betweenRejectCountFieldIndex != -1) {
                  toolbarOne.remove(betweenRejectCountField);
                };

                // }
                _this.betweenRejectCountField = _this._makeBetWeenRejectCountField();
                toolbarOne.insert(insertIndex, _this.betweenRejectCountField);
                toolbarOne.doLayout();
              } else {
                var betweenRejectCountField = toolbarOne.getComponent('betweenRejectCountField');
                if (!Ext.isEmpty(betweenRejectCountField)) {
                  toolbarOne.remove(betweenRejectCountField);
                }
              }
            };
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
    _makeBetWeenRejectCountField: function () {
      var _this = this;
      var betweenRejectCountField = new Ext.form.CompositeField({
        itemId: 'betweenRejectCountField',
        width: 80,
        items: [
          {
            xtype: 'numberfield',
            width: 30,
            emptyText: "0",

            listeners: {
              change: function (self, newValue, oldValue) {
                betweenRejectCountField.value.firstNumber = newValue;
                // _this.betweenRejectCountField.firstNumber = newValue;
              }
            }

          },
          {
            xtype: 'displayfield',
            value: '~',
          },
          {
            xtype: 'numberfield',
            width: 30,
            emptyText: 999,
            listeners: {
              change: function (self, newValue, oldValue) {
                betweenRejectCountField.value.lastNumber = newValue;
                // _this.betweenRejectCountField.lastNumber = newValue;
              }
            }
          }
        ],
        value: {
          firstNumber: 0,
          lastNumber: 999
        }
      });
      return betweenRejectCountField;
    },
    _contentJsonStoreByCode: function (code, self) {
      var jsonStore = new Ext.data.JsonStore({
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
            /**
             * ud_content_id
             * 5:이미지 , code : "audio"
             * 4:오디오, code: "image"
             * 8:cg  code: "cg"
             * 목록에서 제외
             */
            var removeContentArray = ["audio", "image", "cg"];
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

            self.setValue(firstValue);

            var newRecord = [];
            var newRecordCheck = true;
            Ext.each(store.data.items, function (record) {
              newRecordCheck = true;
              Ext.each(removeContentArray, function (content) {
                if (record.get('code_itm_code') === content) {
                  newRecordCheck = false;
                };
              });
              // console.log(record)
              if (newRecordCheck) {
                newRecord.push(record);
              }
            });
            store.removeAll();
            store.add(newRecord);
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
      });
      return jsonStore;
    },
    /**
     * 파일삭제 
     * @param int contentId 
     */
    _deleteFileForm: function (records) {
      var textareaId = Ext.id();
      var _this = this;
      var form = new Ext.form.FormPanel({
        xtype: 'form',
        border: false,
        // frame: true,
        padding: 5,
        layout: 'fit',
        flex: 1,
        // labelWidth: 70,
        // cls: 'change_background_panel',
        defaults: {
          anchor: '100%'
        },
        items: [
          {
            xtype: 'textarea',
            id: textareaId,
            // fieldLabel: _text('MN00128'),
            allowBlank: false,
            // blankText: _text('MSG02015'),
            msgTarget: 'under',
            name: 'delete_reason',
            border: false,
            favoriteCheckedText: []
            // value: '삭제요청'
          }
        ]
      });
      var win = new Ext.Window({
        title: _text('MN00128'), // 삭제 사유
        modal: true,
        width: Ext.getBody().getViewSize().width * 0.4,
        height: Ext.getBody().getViewSize().height * 0.3,
        buttonAlign: 'center',
        layout: 'fit',
        border: false,
        items: new Ext.Container({
          border: false,
          layout: {
            type: 'vbox',
            align: 'stretch'
          },
          items: [
            new Ext.Container({
              border: false,
              margins: '10 10 10 20',
              items: [
                _this._makeFavoriteTextCheckBox('메타데이터 보완이 필요합니다.', 'security', textareaId),
                _this._makeFavoriteTextCheckBox('영상수정이 필요합니다.', 'modify', textareaId),
                _this._makeFavoriteTextCheckBox('중복등록.', 'overlap', textareaId),
                _this._makeFavoriteTextCheckBox('보존 가치 없음.', 'noValue', textareaId),
                _this._makeFavoriteTextCheckBox('영상 오류.', 'error', textareaId),
                _this._makeFavoriteTextCheckBox('내용 수정 후 \'재 승인 요청\'  해주세요.', 'registerAgain', textareaId)
              ]
            }),
            form
          ]
        }),
        buttons: [{
          text: '<span style="position:relative;top:1px;"><i class="fa fa-trash" style="font-size:13px;"></i></span>&nbsp;' + _text('MN00034'),
          scale: 'medium',
          handler: function (btn, e) {
            var deleteReason = form.getForm().findField('delete_reason');
            var tm = deleteReason.getValue();
            var isValid = deleteReason.isValid();
            if (!isValid) {
              Ext.Msg.show({
                icon: Ext.Msg.INFO,
                title: _text('MN00024'), //확인
                msg: _text('MSG02015'),
                buttons: Ext.Msg.OK
              });
              return;
            }
            Ext.Msg.show({
              icon: Ext.Msg.QUESTION,
              title: _text('MN00024'),
              msg: _text('MSG00145'), // 삭제 사유를 저장하고 콘텐츠를 삭제하시겠습니까?
              buttons: Ext.Msg.OKCANCEL,
              fn: function (btnId, text, opts) {
                if (btnId == 'cancel') return;

                var rs = [];
                Ext.each(records, function (r) {
                  rs.push({
                    content_id: r.get('content_id'),
                    delete_his: tm,
                    reg_user_id: r.get('reg_user_id')
                  });
                });
                var w = Ext.Msg.wait(_text('MSG00144'));
                Ext.Ajax.request({
                  url: '/store/delete_contents.php',
                  params: {
                    action: 'forceDelete',
                    content_id: Ext.encode(rs)
                  },
                  callback: function (opts, success, response) {
                    w.hide();
                    if (success) {
                      try {
                        var r = Ext.decode(response.responseText);
                        if (!r.success) {
                          //>>Ext.Msg.alert('알림', '삭제 권한이 없습니다.');
                          Ext.Msg.alert(_text('MN00023'), r.msg);
                          return;
                        }
                        Ext.Msg.alert(_text('MN00023'), r.msg);
                      } catch (e) {
                        Ext.Msg.alert(e['name'], e['message']);
                      }
                    } else {
                      //>>Ext.Msg.alert('오류', response.statusText);
                      Ext.Msg.alert(_text('MN00022'), response.statusText);
                    }
                  }
                })
                win.destroy();
              }
            })
          }
        }, {
          text: '<span style="position:relative;top:1px;"><i class="fa fa-close style="font-size:13px;"></i></span>&nbsp;' + _text('MN00004'),
          scale: 'medium',
          handler: function (btn, e) {
            win.destroy();
          }
        }]
      });
      return win.show();
    },
    _makeFavoriteTextCheckBox: function (text, name, textareaId) {
      var boxName = name;
      var checkBox = new Ext.form.Checkbox({
        boxLabel: text,
        inputValue: text,
        name: boxName,
        listeners: {
          check: function (self, checked) {
            var textArea = Ext.getCmp(textareaId);

            if (checked) {
              textArea.favoriteCheckedText.push(self.inputValue);
            } else {
              textArea.favoriteCheckedText.remove(self.inputValue);
            }

            var fullFavoriteText = '';
            Ext.each(textArea.favoriteCheckedText, function (r) {
              fullFavoriteText = fullFavoriteText + r + ' ';
            });

            textArea.setValue(null);
            textArea.setValue(fullFavoriteText);
          }
        }
      });
      return checkBox;
    },
    _deleteFile: function () {
      var _this = this;
      var sm = _this.getSelectionModel();
      var records = sm.getSelections();

      var isBreak = false;

      if (sm.hasSelection()) {

        Ext.each(records, function (r) {
          var contentId = r.get('content_id');
          /**
           * 아카이브가 없을때 true 리턴
           * 아카이브가 있을떄
           *  요청상태라면 반려로 바꾼후 true 리턴
           *  반려상태라면 true 리턴
           *  승인상태라면 false 리턴
           */
          if (r.get('review_reqest_sttus') != 'reject') {
            isBreak = true;
          };

        });
        if (isBreak) {
          var breakMng = '반려된 목록만 삭제요청 해주세요.';
          return Ext.Msg.alert(_text('MN00023'), breakMng);
        } else {
          _this._deleteFileForm(records);
        }
      } else {
        Ext.Msg.alert('알림', '파일삭제 할 목록을 선택하여 주세요.')
      }
    },
    /**
     * 변경된 상태 취소 하기
     */
    cancelChangeStatus: function (cancelStatus) {
      var _this = this;
      var text = '올바른';
      if (cancelStatus == 'approval') {
        text = '승인';
      }

      if (cancelStatus == 'reject') {
        text = '승인';
      }

      var sm = this.getSelectionModel();
      if (sm.hasSelection()) {
        // var ordId = sm.getSelected().get('id');
        // var status = sm.getSelected().get('review_reqest_sttus');
        // var contentId = sm.getSelected().get('content_id');
        // var reviewUser = sm.getSelected().get('review_user_id');
        var nowStatus = sm.getSelected().get('review_st_code').code_itm_code;
        var nowStatusNm = sm.getSelected().get('review_st_code').code_itm_nm;
        if (cancelStatus == nowStatus) {
          Ext.Msg.show({
            title: '알림',
            msg: '상태를 변경하시겠습니까?',
            buttons: Ext.Msg.OKCANCEL,
            fn: function (btnId, text, opts) {
              if (btnId == 'ok') {
                _this._changeRequestStatus('cancel');
              }
            }
          });
        } else {
          return Ext.Msg.alert('알림', text + ' 상태에서만 취소해주세요.');
        }
      } else {
        return Ext.Msg.alert('알림', '의뢰 진행할 목록을 선택해주세요.');
      }
    },
    getTopToolbarTwo: function () {
      return this.getTopToolbar().getComponent('toolbarTwo');
    },
    getTopToolbarOne: function () {
      return this.getTopToolbar().getComponent('toolbarOne');
    }
  });
  // return new Ariel.DashBoard.Review();
})()