(function () {

  var total_list = 0;
  var request_page_size = 50;

  // var archiveStatus = {
  //          request :			1,
  //          approve	:		2,
  //          reject	:		3
  //     };
  function _makeFavoriteTextCheckBox(text, name, textareaId) {
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
  }
  function show_confirm_win(rs_req_no) {
    var confirm_win = new Ext.Window({
      title: '확인',
      width: 250,
      modal: true,
      border: true,
      frame: true,
      padding: '3px',
      buttonAlign: 'center',
      items: [{
        xtype: 'displayfield',
        value: '<center><p style="font-weight:bold;height:30px;line-height:30px;">선택하신 요청목록을 승인 하시겠습니까?</p></center>'
      }, {
        xtype: 'displayfield',
        value: '<p style="height:20px;line-height:20px;">승인내용</p>'
      }, {
        xtype: 'textarea',
        layout: 'fit',
        width: 230,
        id: 'confirm_auth_comment'
      }],
      buttons: [{
        text: '예',
        scale: 'medium',
        icon: '/led-icons/accept.png',
        is_click: false,
        handler: function (b, e) {
          confirm_win.el.mask();

          Ext.Ajax.request({
            url: '/pages/menu/archive_management/action_archive_request.php',
            params: {
              action: 'accept',
              'req_no[]': rs_req_no,
              auth_comment: Ext.getCmp('confirm_auth_comment').getValue()
            },
            callback: function (opt, success, res) {
              confirm_win.el.unmask();
              if (success) {
                var msg = Ext.decode(res.responseText);
                if (msg.success) {
                  Ext.Msg.alert(' 완 료', msg.msg);
                } else {
                  Ext.Msg.alert(' 오 류 ', msg.msg);
                }
              } else {
                Ext.Msg.alert('서버 오류', res.statusText);
              }
              confirm_win.close();
              request_manage_grid.getStore().reload();
            }
          });
        }
      }, {
        text: '아니오',
        scale: 'medium',
        icon: '/led-icons/cross.png',
        handler: function (b, e) {
          confirm_win.close();
        }
      }
      ]
    }).show();
  }

  function deleteFileForm(customWindow) {
    var is_break = false;
    var records = request_manage_grid.getSelectionModel().getSelections();
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
    if (records.length > 0) {
      for (var i = 0; i < records.length; i++) {
        if ((records[i].get('req_status') != 3) || (records[i].get('req_type') != 'archive')) {
          break_msg = '아카이브 반려중인 항목만 선택해주세요';
          is_break = true;
          break;
        }
      }
      if (is_break) {
        return Ext.Msg.alert('오류', break_msg);
      } else {
        var win = new Ext.Window({
          layout: 'fit',
          title: _text('MN00128'),
          modal: true,
          width: Ext.getBody().getViewSize().width * 0.4,
          height: Ext.getBody().getViewSize().height * 0.3,
          buttonAlign: 'center',
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
                  _makeFavoriteTextCheckBox('메타데이터 보완이 필요합니다.', 'security', textareaId),
                  _makeFavoriteTextCheckBox('영상수정이 필요합니다.', 'modify', textareaId),
                  _makeFavoriteTextCheckBox('중복등록.', 'overlap', textareaId),
                  _makeFavoriteTextCheckBox('보존 가치 없음.', 'noValue', textareaId),
                  _makeFavoriteTextCheckBox('영상 오류.', 'error', textareaId),
                ]
              }),
              form
            ]
          }),
          border: false,
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
              // 파라미터 콘텐츠 아이디, delete_his:tm, reg_user_id
              Ext.Msg.show({
                icon: Ext.Msg.QUESTION,
                title: _text('MN00024'),
                msg: _text('MSG00145'),
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
                  })

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
              });
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
      }
    }

  }

  function requestApprove(customWindow) {
    var sel = request_manage_grid.getSelectionModel().getSelections();
    var is_break = false;
    var break_msg = 0;
    var rs_req_no = [];
    if (sel.length > 0) {
      for (var i = 0; i < sel.length; i++) {
        if (sel[i].get('req_status') != 1) {
          break_msg = '요청중인 항목만 선택해주세요';
          is_break = true;
          break;
        }

        rs_req_no.push(sel[i].get('req_no'));
      }

      if (is_break) {
        Ext.Msg.alert('오류', break_msg);
        return;
      } else {
        Ext.Msg.show({
          title: '알림',
          msg: '승인하시겠습니까?',
          buttons: Ext.Msg.OKCANCEL,
          fn: function (btnId, text, opts) {
            if (btnId == 'ok') {
              Ext.Ajax.request({
                url: '/pages/menu/archive_management/action_archive_request.php',
                params: {
                  action: 'approve',
                  // appr_comment: Ext.getCmp('reqest_appr_comment').getValue(),
                  'req_no[]': rs_req_no
                },
                callback: function (opt, success, res) {
                  if (success) {
                    var msg = Ext.decode(res.responseText);
                    if (msg.success) {
                      Ext.Msg.alert('완료', msg.msg);
                      Ext.getCmp('request_manage_grid_id').getStore().reload();
                      if (!Ext.isEmpty(customWindow)) {
                        customWindow.close();
                      }
                      req_win.close();
                    } else {
                      Ext.Msg.alert('오류', msg.msg);
                    }
                  } else {
                    Ext.Msg.alert('서버 오류', res.statusText);
                  }
                  request_manage_grid.getStore().reload();
                }
              });
            }
          }
        });

        // var req_win = new Ext.Window({
        // 	title: '승인',
        // 	width: 350,
        // 	height: 160,
        // 	modal: true,
        // 	layout: 'fit',
        // 	items: [{
        // 		xtype: 'form',
        // 		padding: 5,
        // 		labelWidth: 50,
        // 		labelAlign: 'right',
        // 		defaults: {
        // 			anchor: '98%'
        // 		},
        // 		items: [{
        // 			xtype: 'textarea',
        // 			id: 'reqest_appr_comment',
        // 			name: 'comment',
        // 			allowBlank: 'false',
        // 			height: 80,
        // 			fieldLabel: '승인내용'
        // 		}]
        // 	}],
        // 	buttonAlign: 'center',
        // 	buttons: [{
        // 		text: '승인',
        // 		handler: function (btn, e) {

        // 			Ext.Ajax.request({
        // 				url: '/pages/menu/archive_management/action_archive_request.php',
        // 				params: {
        // 					action: 'approve',
        // 					appr_comment: Ext.getCmp('reqest_appr_comment').getValue(),
        // 					'req_no[]': rs_req_no
        // 				},
        // 				callback: function (opt, success, res) {
        // 					if (success) {
        // 						var msg = Ext.decode(res.responseText);
        // 						if (msg.success) {
        // 							Ext.Msg.alert('완료', msg.msg);
        // 							Ext.getCmp('request_manage_grid_id').getStore().reload();
        // 							if (!Ext.isEmpty(customWindow)) {
        // 								customWindow.close();
        // 							}
        // 							req_win.close();
        // 						} else {
        // 							Ext.Msg.alert('오류', msg.msg);
        // 						}
        // 					} else {
        // 						Ext.Msg.alert('서버 오류', res.statusText);
        // 					}
        // 					request_manage_grid.getStore().reload();
        // 				}
        // 			});
        // 		}
        // 	}, {
        // 		text: '취소',
        // 		handler: function (btn, e) {
        // 			req_win.close();
        // 		}
        // 	}]
        // }).show();
      }
    }
  }

  function requestReject(customWindow) {
    var form = new Ext.form.FormPanel({
      xtype: 'form',
      padding: 10,
      border: false,
      labelAlign: 'right',
      labelWidth: 50,
      flex: 1,
      layout: 'fit',
      defaults: {
        anchor: '98%'
      },
      items: [{
        xtype: 'textarea',
        id: 'reqest_reject_comment',
        name: 'comment',
        allowBlank: 'false',
        height: 80,
        fieldLabel: '반려내용',
        favoriteCheckedText: []
      }]
    });
    var sel = request_manage_grid.getSelectionModel().getSelections();
    var is_break = false;
    var break_msg = 0;
    var rs_req_no = [];
    if (sel.length > 0) {
      for (var i = 0; i < sel.length; i++) {
        if (sel[i].get('req_status') != 1) {
          break_msg = '요청중인 항목만 선택해주세요';
          is_break = true;
          break;
        }

        rs_req_no.push(sel[i].get('req_no'));
      }

      if (is_break) {
        Ext.Msg.alert('오류', break_msg);
        return;
      } else {
        var req_win = new Ext.Window({
          title: '반려',
          width: Ext.getBody().getViewSize().width * 0.4,
          height: Ext.getBody().getViewSize().height * 0.3,
          modal: true,
          layout: 'fit',
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
                  _makeFavoriteTextCheckBox('메타데이터 보완이 필요합니다.', 'security', 'reqest_reject_comment'),
                  _makeFavoriteTextCheckBox('영상수정이 필요합니다.', 'modify', 'reqest_reject_comment'),
                  _makeFavoriteTextCheckBox('중복등록.', 'overlap', 'reqest_reject_comment'),
                  _makeFavoriteTextCheckBox('보존 가치 없음.', 'noValue', 'reqest_reject_comment'),
                  _makeFavoriteTextCheckBox('영상 오류.', 'error', 'reqest_reject_comment'),
                ]
              }),
              form
            ]
          }),
          buttonAlign: 'center',
          buttons: [{
            text: '반려',
            handler: function (btn, e) {
              Ext.Ajax.request({
                url: '/pages/menu/archive_management/action_archive_request.php',
                params: {
                  action: 'reject',
                  appr_comment: Ext.getCmp('reqest_reject_comment').getValue(),
                  'req_no[]': rs_req_no
                },
                callback: function (opt, success, res) {
                  if (success) {
                    var msg = Ext.decode(res.responseText);
                    if (msg.success) {
                      Ext.Msg.alert('완료', msg.msg);
                      Ext.getCmp('request_manage_grid_id').getStore().reload();
                      if (!Ext.isEmpty(customWindow)) {
                        customWindow.close();
                      }
                      req_win.close();
                    } else {
                      Ext.Msg.alert('오류', msg.msg);
                    }
                  } else {
                    Ext.Msg.alert('서버 오류', res.statusText);
                  }
                  request_manage_grid.getStore().reload();
                }
              });
            }
          }, {
            text: '취소',
            handler: function (btn, e) {
              req_win.close();
            }
          }]
        }).show();
      }
    }
  }

  function deleteFile(customWindow) {
    var sel = request_manage_grid.getSelectionModel().getSelections();
    var is_break = false;
    var break_msg = 0;
    var rs_req_no = [];
    if (sel.length > 0) {
      for (var i = 0; i < sel.length; i++) {
        if ((sel[i].get('req_status') == 2)) {
          break_msg = '요청 또는 반려 항목만 선택해주세요';
          is_break = true;
          break;
        }
        rs_req_no.push(sel[i].get('req_no'));
      }
      if (is_break) {
        Ext.Msg.alert('오류', break_msg);
        return;
      } else {
        Ext.Ajax.request({
          url: '/pages/menu/archive_management/action_archive_request.php',
          params: {
            action: 'reject',
            appr_comment: '파일삭제',
            'req_no[]': rs_req_no
          },
          callback: function (opt, success, res) {
            if (success) {
              var msg = Ext.decode(res.responseText);
              if (msg.success) {
                // 반려 상태로 바뀐후 그리드 리로드
                Ext.getCmp('request_manage_grid_id').getStore().reload();
                // 파일 삭제 로직
                deleteFileForm(sel);
                if (!Ext.isEmpty(customWindow)) {
                  customWindow.close();
                }
              } else {
                Ext.Msg.alert('오류', msg.msg);
              }
            } else {
              Ext.Msg.alert('서버 오류', res.statusText);
            }
            request_manage_grid.getStore().reload();
          }
        });
      }
    }
  }
  var selModel = new Ext.grid.CheckboxSelectionModel({
    singleSelect: false,
    checkOnly: false
  });

  //자동 새로고침에 사용하는 변수

  var request_manage_store = new Ext.data.JsonStore({
    url: '/pages/menu/archive_management/get_request_data.php',
    root: 'data',
    totalProperty: 'total_list',
    idProperty: 'req_no',
    baseParams: {
      start: 0,
      limit: 50
    },
    sortInfo: {
      field: 'req_time',
      direction: 'ASC' // or 'DESC' (case sensitive for local sorting)
    },
    remoteSort: true,
    fields: [
      'req_no', { name: 'req_time', type: 'date', dateFormat: 'YmdHis' },
      'req_type', 'req_status',
      'req_user_id', 'req_user_nm',
      'das_content_id', 'nps_content_id',
      'req_comment', 'appr_user_id',
      'pgm_id', 'pgm_nm',
      'trg_category_id', 'trg_category_title',
      'appr_user_nm', { name: 'appr_time', type: 'date', dateFormat: 'YmdHis' },
      'appr_comment', 'title',
      'ud_content_title', 'ud_content_id',
      'status', 'task_id',
      'qualitycheck', 'required_input',
      'ud_content_title',
      'content_id',
      'reg_user_id', 'archive_id', 'path', 'filesize'
    ],
    listeners: {
      beforeload: function (self, opts) {
        opts.params = opts.params || {};

        Ext.apply(opts.params, {
          req_start_date: Ext.getCmp('request_start_date').getValue().format('Ymd000000'),
          req_end_date: Ext.getCmp('request_end_date').getValue().format('Ymd240000'),
          req_type: Ext.getCmp('request_type_combo').getValue(),
          req_status: Ext.getCmp('request_status_combo').getValue(),
          search_field: Ext.getCmp('request_search_field').getValue(),
          search_value: Ext.getCmp('request_search_value').getValue(),
          task_status: Ext.getCmp('request_task_status_combo').getValue()
        });
      },
      load: function (self, opts) {
        total_list = self.getTotalCount();
        var tooltext = "요청 : <font color=blue><b>" + total_list + "</b></font> 건";
        Ext.getCmp('request_toolbartext').setText(tooltext);
      }
    }
  });



  var tbar1 = new Ext.Toolbar({
    dock: 'top',
    items: [' 요청구분', {
      xtype: 'combo',
      id: 'request_type_combo',
      hiddenName: 'request_type_combo',
      mode: 'local',
      width: 90,
      triggerAction: 'all',
      editable: false,
      displayField: 'd',
      valueField: 'v',
      value: 'all',
      store: new Ext.data.ArrayStore({
        fields: [
          'd', 'v'
        ],
        data: [
          ['전체', 'all'],
          ['아카이브', 'archive'],
          ['리스토어', 'restore'],
          // ['Partial 리스토어', 'pfr_restore']
          ['아카이브 삭제', 'delete']
        ]
      }),
      listeners: {
        select: {
          fn: function (self, record, index) {
            request_manage_grid.getStore().reload();
          }
        }
      }
    }, '-', ' 요청상태', {
        xtype: 'combo',
        id: 'request_status_combo',
        hiddenName: 'request_status_combo',
        mode: 'local',
        width: 90,
        triggerAction: 'all',
        editable: false,
        displayField: 'd',
        valueField: 'v',
        value: '1',
        store: new Ext.data.ArrayStore({
          fields: [
            'd', 'v'
          ],
          data: [
            ['전체', 'all'],
            ['요청', '1'],
            ['승인', '2'],
            ['반려', '3']
          ]
        }),
        listeners: {
          select: {
            fn: function (self, record, index) {
              request_manage_grid.getStore().reload();
            }
          }
        }
      }, '-', ' 작업상태', {
        id: 'request_task_status_combo',
        xtype: 'combo',
        mode: 'local',
        width: 70,
        triggerAction: 'all',
        editable: false,
        displayField: 'd',
        valueField: 'v',
        name: 'task_status',
        value: 'all',
        store: new Ext.data.ArrayStore({
          fields: [
            'd', 'v'
          ],
          data: [
            ['전체', 'all'],
            ['완료', 'complete'],
            ['에러', 'error'],
            ['처리중', 'processing']
          ]
        }),
        listeners: {
          select: {
            fn: function (self, record, index) {
              //request_manage_grid.getStore().reload();
            }
          }
        }
      }, '-', '요청일시', {
        xtype: 'datefield',
        id: 'request_start_date',
        editable: false,
        width: 105,
        format: 'Y-m-d',
        altFormats: 'Ymd|ymd|Y-m-d|y-m-d|Y/m/d|Y/m/d',
        listeners: {
          render: function (self) {
            var d = new Date();

            self.setMaxValue(d.format('Y-m-d'));
            self.setValue(d.add(Date.DAY, -3).format('Y-m-d'));
          }
        }
      }, '~', {
        xtype: 'datefield',
        id: 'request_end_date',
        editable: false,
        width: 105,
        format: 'Y-m-d',
        altFormats: 'Ymd|ymd|Y-m-d|y-m-d|Y/m/d|Y/m/d',
        listeners: {
          render: function (self) {
            var d = new Date();

            self.setMaxValue(d.format('Y-m-d'));
            self.setValue(d.format('Y-m-d'));
          }
        }
      }, '-', {
        xtype: 'combo',
        id: 'request_search_field',
        mode: 'local',
        width: 70,
        triggerAction: 'all',
        editable: false,
        displayField: 'd',
        valueField: 'v',
        value: 'keyword',
        store: new Ext.data.ArrayStore({
          fields: [
            'd', 'v'
          ],
          data: [
            ['제목', 'keyword'],
            ['요청자', 'req_user'],
            ['승인자', 'appr_user'],
            ['요청사유', 'req_comment']
            // ['NPS ID', 'nps_content_id']
          ]
        }),
        listeners: {
          select: {
            fn: function (self, record, index) {
              //request_manage_grid.getStore().reload();
            }
          }
        }
      }, {
        xtype: 'textfield',
        id: 'request_search_value',
        listeners: {
          specialKey: function (self, e) {
            if (e.getKey() == e.ENTER) {
              e.stopEvent();

              request_manage_grid.getStore().reload({ params: { start: 0 } });
            }
          }
        }
      }, '-', {
        xtype: 'aw-button',
        iCls: 'fa fa-search',
        text: '조회',
        tooltip: '조회',
        handler: function (btn, e) {
          //var params = Ext.getCmp('archive_request_search_field').getValue();
          request_manage_grid.getStore().reload({ params: { start: 0 } });
        }
      }, '-', {
        xtype: 'aw-button',
        iCls: 'fa fa-search',
        text: '이벤트 조회',
        tooltip: '이벤트 조회',
        handler: function (btn, e) {
          var sel = request_manage_grid.getSelectionModel().getSelected();
          var grid = btn.ownerCt.ownerCt;
          if (sel) {
            var taskId = sel.get('task_id');
            if (taskId) {
              grid._showEventWinShow(taskId);
            } else {
              Ext.Msg.alert('알림', '작업정보가 없습니다');
            }
          } else {

          }

        }
      }, '->', {
        xtype: 'aw-button',
        iCls: 'fa fa-check',
        // icon: '/led-icons/accept.png',
        text: '승인',
        tooltip: '요청된 항목을 승인합니다',
        handler: function (btn, e) {
          var sel = request_manage_grid.getSelectionModel().getSelections();
          var is_break = false;
          var break_msg = 0;
          var rs_req_no = [];
          if (sel.length > 0) {
            for (var i = 0; i < sel.length; i++) {
              if (sel[i].get('req_status') != 1) {
                break_msg = '요청중인 항목만 선택해주세요';
                is_break = true;
                break;
              }

              rs_req_no.push(sel[i].get('req_no'));
            }

            if (is_break) {
              Ext.Msg.alert('오류', break_msg);
              return;
            } else {
              Ext.Msg.show({
                title: '알림',
                msg: '승인하시겠습니까?',
                buttons: Ext.Msg.OKCANCEL,
                fn: function (btnId, text, opts) {
                  if (btnId == 'ok') {
                    Ext.Ajax.request({
                      url: '/pages/menu/archive_management/action_archive_request.php',
                      params: {
                        action: 'approve',
                        // appr_comment: Ext.getCmp('reqest_appr_comment').getValue(),
                        'req_no[]': rs_req_no
                      },
                      callback: function (opt, success, res) {
                        if (success) {
                          var msg = Ext.decode(res.responseText);
                          if (msg.success) {
                            Ext.Msg.alert('완료', msg.msg);
                            Ext.getCmp('request_manage_grid_id').getStore().reload();
                            req_win.close();
                          } else {
                            Ext.Msg.alert('오류', msg.msg);
                          }
                        } else {
                          Ext.Msg.alert('서버 오류', res.statusText);
                        }
                        request_manage_grid.getStore().reload();
                      }
                    });
                  }
                }
              });

              // var req_win = new Ext.Window({
              // 	title: '승인',
              // 	width: 350,
              // 	height: 160,
              // 	modal: true,
              // 	layout: 'fit',
              // 	items: [{
              // 		xtype: 'form',
              // 		padding: 5,
              // 		labelWidth: 50,
              // 		labelAlign: 'right',
              // 		defaults: {
              // 			anchor: '98%'
              // 		},
              // 		items: [{
              // 			xtype: 'textarea',
              // 			id: 'reqest_appr_comment',
              // 			name: 'comment',
              // 			allowBlank: 'false',
              // 			height: 80,
              // 			fieldLabel: '승인내용'
              // 		}]
              // 	}],
              // 	buttonAlign: 'center',
              // 	buttons: [{
              // 		text: '승인',
              // 		handler: function (btn, e) {

              // 			Ext.Ajax.request({
              // 				url: '/pages/menu/archive_management/action_archive_request.php',
              // 				params: {
              // 					action: 'approve',
              // 					appr_comment: Ext.getCmp('reqest_appr_comment').getValue(),
              // 					'req_no[]': rs_req_no
              // 				},
              // 				callback: function (opt, success, res) {
              // 					if (success) {
              // 						var msg = Ext.decode(res.responseText);
              // 						if (msg.success) {
              // 							Ext.Msg.alert('완료', msg.msg);
              // 							Ext.getCmp('request_manage_grid_id').getStore().reload();
              // 							req_win.close();
              // 						} else {
              // 							Ext.Msg.alert('오류', msg.msg);
              // 						}
              // 					} else {
              // 						Ext.Msg.alert('서버 오류', res.statusText);
              // 					}
              // 					request_manage_grid.getStore().reload();
              // 				}
              // 			});
              // 		}
              // 	}, {
              // 		text: '취소',
              // 		handler: function (btn, e) {
              // 			req_win.close();
              // 		}
              // 	}]
              // }).show();
            }
          }
        }
      }, ' ', {
        xtype: 'aw-button',
        iCls: 'fa fa-times',
        text: '반려',
        tooltip: '요청된 항목을 반려합니다.',
        handler: function (btn, e) {
          var form = new Ext.form.FormPanel({
            xtype: 'form',
            padding: 10,
            border: false,
            labelAlign: 'right',
            labelWidth: 50,
            flex: 1,
            layout: 'fit',
            defaults: {
              anchor: '98%'
            },
            items: [{
              xtype: 'textarea',
              id: 'reqest_reject_comment',
              name: 'comment',
              allowBlank: 'false',
              // fieldLabel: '반려내용',
              favoriteCheckedText: []
            }]
          });
          var sel = request_manage_grid.getSelectionModel().getSelections();
          var is_break = false;
          var break_msg = 0;
          var rs_req_no = [];
          if (sel.length > 0) {
            for (var i = 0; i < sel.length; i++) {
              if (sel[i].get('req_status') != 1) {
                break_msg = '요청중인 항목만 선택해주세요';
                is_break = true;
                break;
              }

              rs_req_no.push(sel[i].get('req_no'));
            }

            if (is_break) {
              Ext.Msg.alert('오류', break_msg);
              return;
            } else {
              var req_win = new Ext.Window({
                title: '반려',
                width: Ext.getBody().getViewSize().width * 0.4,
                height: Ext.getBody().getViewSize().height * 0.3,
                modal: true,
                layout: 'fit',
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
                        _makeFavoriteTextCheckBox('메타데이터 보완이 필요합니다.', 'security', 'reqest_reject_comment'),
                        _makeFavoriteTextCheckBox('영상수정이 필요합니다.', 'modify', 'reqest_reject_comment'),
                        _makeFavoriteTextCheckBox('중복등록.', 'overlap', 'reqest_reject_comment'),
                        _makeFavoriteTextCheckBox('보존 가치 없음.', 'noValue', 'reqest_reject_comment'),
                        _makeFavoriteTextCheckBox('영상 오류.', 'error', 'reqest_reject_comment'),
                      ]
                    }),
                    form
                  ]
                }),
                buttonAlign: 'center',
                buttons: [{
                  text: '반려',
                  handler: function (btn, e) {
                    Ext.Ajax.request({
                      url: '/pages/menu/archive_management/action_archive_request.php',
                      params: {
                        action: 'reject',
                        appr_comment: Ext.getCmp('reqest_reject_comment').getValue(),
                        'req_no[]': rs_req_no
                      },
                      callback: function (opt, success, res) {
                        if (success) {
                          var msg = Ext.decode(res.responseText);
                          if (msg.success) {
                            Ext.Msg.alert('완료', msg.msg);
                            Ext.getCmp('request_manage_grid_id').getStore().reload();
                            req_win.close();
                          } else {
                            Ext.Msg.alert('오류', msg.msg);
                          }
                        } else {
                          Ext.Msg.alert('서버 오류', res.statusText);
                        }
                        request_manage_grid.getStore().reload();
                      }
                    });
                  }
                }, {
                  text: '취소',
                  handler: function (btn, e) {
                    req_win.close();
                  }
                }]
              }).show();
            }
          }
        }
      }, {
        xtype: 'aw-button',
        iCls: 'fa fa-ban',
        text: '파일삭제',
        tooltip: '파일을 삭제합니다.',
        handler: function (btn) {
          deleteFileForm();
        }
      }, {
        hidden: true,
        icon: '/led-icons/page_white_excel.png',
        text: 'Excel',
        handler: function (btn, e) {
          window.location = '/pages/menu/archive_management/get_request_data.php?is_excel=Y' +
            '&req_start_date=' + Ext.getCmp('request_start_date').getValue().format('Ymd000000') +
            '&req_end_date=' + Ext.getCmp('request_end_date').getValue().format('Ymd240000') +
            '&req_type=' + Ext.getCmp('request_type_combo').getValue() +
            '&req_status=' + Ext.getCmp('request_status_combo').getValue() +
            '&search_field=' + Ext.getCmp('request_search_field').getValue() +
            '&search_value=' + Ext.getCmp('request_search_value').getValue();

          Ext.Msg.alert('알림', '엑셀파일 다운로드가 곧 시작됩니다.');
        }
      }]
  });


  var tbar2 = new Ext.Toolbar({
    dock: 'top',
    hidden: true,
    items: [{
      icon: '/led-icons/accept.png',
      text: '승인',
      tooltip: '요청된 항목을 승인합니다',
      handler: function (btn, e) {
        var sel = request_manage_grid.getSelectionModel().getSelections();
        var is_break = false;
        var break_msg = 0;
        var rs_req_no = [];
        if (sel.length > 0) {
          for (var i = 0; i < sel.length; i++) {
            if (sel[i].get('req_status') != 1) {
              break_msg = '요청중인 항목만 선택해주세요';
              is_break = true;
              break;
            }

            rs_req_no.push(sel[i].get('req_no'));
          }

          if (is_break) {
            Ext.Msg.alert('오류', break_msg);
            return;
          } else {
            var req_win = new Ext.Window({
              title: '승인',
              width: 350,
              height: 160,
              modal: true,
              layout: 'fit',
              items: [{
                xtype: 'form',
                padding: 5,
                labelWidth: 50,
                labelAlign: 'right',
                defaults: {
                  anchor: '98%'
                },
                items: [{
                  xtype: 'textarea',
                  id: 'reqest_appr_comment',
                  name: 'comment',
                  allowBlank: 'false',
                  height: 80,
                  fieldLabel: '승인내용'
                }]
              }],
              buttonAlign: 'center',
              buttons: [{
                text: '승인',
                handler: function (btn, e) {

                  Ext.Ajax.request({
                    url: '/pages/menu/archive_management/action_archive_request.php',
                    params: {
                      action: 'approve',
                      // appr_comment: Ext.getCmp('reqest_appr_comment').getValue(),
                      'req_no[]': rs_req_no
                    },
                    callback: function (opt, success, res) {
                      if (success) {
                        var msg = Ext.decode(res.responseText);
                        if (msg.success) {
                          Ext.Msg.alert('완료', msg.msg);
                          Ext.getCmp('request_manage_grid_id').getStore().reload();
                          req_win.close();
                        } else {
                          Ext.Msg.alert('오류', msg.msg);
                        }
                      } else {
                        Ext.Msg.alert('서버 오류', res.statusText);
                      }
                      request_manage_grid.getStore().reload();
                    }
                  });
                }
              }, {
                text: '취소',
                handler: function (btn, e) {
                  req_win.close();
                }
              }]
            }).show();
          }
        }
      }
    }, '-', {
      icon: '/led-icons/cancel.png',
      text: '반려',
      tooltip: '요청된 항목을 반려합니다.',
      handler: function (btn, e) {
        var sel = request_manage_grid.getSelectionModel().getSelections();
        var is_break = false;
        var break_msg = 0;
        var rs_req_no = [];
        if (sel.length > 0) {
          for (var i = 0; i < sel.length; i++) {
            if (sel[i].get('req_status') != 1) {
              break_msg = '요청중인 항목만 선택해주세요';
              is_break = true;
              break;
            }

            rs_req_no.push(sel[i].get('req_no'));
          }

          if (is_break) {
            Ext.Msg.alert('오류', break_msg);
            return;
          } else {
            var req_win = new Ext.Window({
              title: '반려',
              width: 350,
              height: 160,
              modal: true,
              layout: 'fit',
              items: [{
                xtype: 'form',
                padding: 5,
                labelAlign: 'right',
                labelWidth: 50,
                defaults: {
                  anchor: '98%'
                },
                items: [{
                  xtype: 'textarea',
                  id: 'reqest_reject_comment',
                  name: 'comment',
                  allowBlank: 'false',
                  height: 80,
                  fieldLabel: '반려내용'
                }]
              }],
              buttonAlign: 'center',
              buttons: [{
                text: '반려',
                handler: function (btn, e) {
                  Ext.Ajax.request({
                    url: '/pages/menu/archive_management/action_archive_request.php',
                    params: {
                      action: 'reject',
                      appr_comment: Ext.getCmp('reqest_reject_comment').getValue(),
                      'req_no[]': rs_req_no
                    },
                    callback: function (opt, success, res) {
                      if (success) {
                        var msg = Ext.decode(res.responseText);
                        if (msg.success) {
                          Ext.Msg.alert('완료', msg.msg);
                          Ext.getCmp('request_manage_grid_id').getStore().reload();
                          req_win.close();
                        } else {
                          Ext.Msg.alert('오류', msg.msg);
                        }
                      } else {
                        Ext.Msg.alert('서버 오류', res.statusText);
                      }
                      request_manage_grid.getStore().reload();
                    }
                  });
                }
              }, {
                text: '취소',
                handler: function (btn, e) {
                  req_win.close();
                }
              }]
            }).show();
          }
        }
      }
    }
      // ,'->',{
      // 	text: '자동 새로고침 실행중',
      // 	scale: 'small',
      // 	//pressed: true,
      // 	id: 'un_pin_request',
      // 	icon: '/led-icons/accept.png',
      // 	handler: function(b, e){
      // 		clearInterval(setTime);
      // 		Ext.getCmp('un_pin_request').hide();
      // 		Ext.getCmp('pin_request').show();
      // 	}
      // },{
      // 	text: '자동 새로고침 중지됨',
      // 	scale: 'small',
      // 	id: 'pin_request',
      // 	hidden: true,
      // 	icon: '/led-icons/cross.png',
      // 	listeners: {
      // 		afterrender: function(self){
      // 			if(setTime == null) {
      // 				setTime = setInterval(function() {
      // 					Ext.getCmp('request_manage_grid_id').getStore().reload();
      // 				}, auto_refresh_time);
      // 			}
      // 		}
      // 	},
      // 	handler: function(b, e) {
      // 		setTime = setInterval(function() {
      // 			Ext.getCmp('request_manage_grid_id').getStore().reload();
      // 		}, auto_refresh_time);
      // 		Ext.getCmp('pin_request').hide();
      // 		Ext.getCmp('un_pin_request').show();
      // 	}
      // }		
    ]
  });

  var request_manage_grid = new Ext.grid.EditorGridPanel({
    border: false,
    loadMask: true,
    frame: true,
    id: 'request_manage_grid_id',
    width: 800,
    // tbar: new Ext.Container({
    // 	height: 54,
    // 	layout: 'anchor',
    // 	xtype: 'container',
    // 	defaults: {
    // 		anchor: '100%',
    // 		height: 27
    // 	},
    // 	items: [
    // 		tbar1,
    // 		tbar2
    // 	]
    // }),
    tbar: tbar1,
    //xtype: 'editorgrid',
    clicksToEdit: 1,
    loadMask: true,
    columnWidth: 1,
    store: request_manage_store,
    disableSelection: true,
    listeners: {
      viewready: function (self) {
        self.store.load({
          params: {
            start: 0,
            limit: request_page_size,
            req_start_date: Ext.getCmp('request_start_date').getValue().format('Ymd000000'),
            req_end_date: Ext.getCmp('request_end_date').getValue().format('Ymd240000')
          }
        });
        //self.add(tbar2);
      },
      rowcontextmenu: function (self, rowIdx, e) {
        return;
        e.stopEvent();

        var ownerCt = self;

        var sm = self.getSelectionModel();
        if (!sm.isSelected(rowIdx)) {
          sm.selectRow(rowIdx);
        }

        var sel_data = sm.getSelected();
        var req_no = sel_data.get('req_no');
        var src_device_id = sel_data.get('src_device_id');
        var arc_type = sel_data.get('arc_type');
        var status = sel_data.get('status');
        var ud_system = sel_data.get('ud_system');
        var mgmt_id = sel_data.get('mgmt_id');
        var mtrl_id = sel_data.get('mtrl_id');
        var content_id = sel_data.get('content_id');
        var task_id = sel_data.get('task_id');

        var target_ud_system = sel_data.get('target_ud_system');

        var menu = new Ext.menu.Menu({
          items: [{
            icon: '/led-icons/application_form.png',
            text: '작업흐름보기',
            handler: function (btn, e) {
              if (Ext.isEmpty(task_id)) {
                Ext.Msg.alert('알림', '승인되지 않은 요청입니다');
                return;
              }

              Ext.Ajax.request({
                url: '/javascript/ext.ux/viewWorkFlowRequest.php',
                params: {
                  task_id: task_id
                },
                callback: function (options, success, response) {
                  if (success) {
                    try {
                      Ext.decode(response.responseText);
                    } catch (e) {
                      Ext.Msg.alert(e['name'], e['message']);
                    }
                  } else {
                    //>>Ext.Msg.alert('서버 오류', response.statusText);
                    Ext.Msg.alert(_text('MN00022'), response.statusText);
                  }
                }
              });
            }
          }]
        });
        menu.showAt(e.getXY());
      },

      rowdblclick: function (self, rowIndex, e) {
        var sm = self.getSelectionModel().getSelected();

        var content_id = sm.get('das_content_id');
        var req_comment = sm.get('req_comment');
        var mtrl_id = sm.get('mtrl_id');
        var mgmt_id = sm.get('mgmt_id');
        var is_block = "";
        var hasQualityTab = sm.get('qualitycheck');

        if (!mgmt_id) {
          is_block = "ok";
        }

        var that = self;

        self.load = new Ext.LoadMask(Ext.getBody(), { msg: _text('MSG00143') });
        self.load.show();
        var components = [
          '/custom/ktv-nps/javascript/ext.ux/Custom.ParentContentGrid.js',
        ];
        Ext.Loader.load(components, function (r) {
          that.load.hide();
          new Custom.ContentDetailWindow({
            content_id: content_id,
            isPlayer: true,
            isMetaForm: true,
            playerMode: 'read',
            listeners: {
              afterrender: function (self) {
                self.buttons[0].hide();
                Ext.each(self.buttons, function (r) {
                  r.hide();
                });

                self.addButton({
                  xtype: 'aw-button',
                  text: '요청승인',
                  handler: function (btn) {
                    requestApprove(self);
                  }
                });
                self.addButton({
                  xtype: 'aw-button',
                  text: '요청반려',
                  handler: function (btn) {
                    requestReject(self);
                  }
                });
                self.addButton({
                  xtype: 'aw-button',
                  text: '파일삭제',
                  handler: function (btn) {
                    deleteFileForm(self)
                  }
                });
                self.addButton({
                  xtype: 'aw-button',
                  text: '닫기',
                  handler: function (btn) {
                    self.close();
                  }
                });
              }
            }
          }).show();
        });
        // Ext.Ajax.request({
        // 	url: '/javascript/ext.ux/Ariel.DetailWindow.php',
        // 	params: {
        // 		content_id: content_id,
        // 		record: Ext.encode(sm.json),
        // 		page_from: 'ArchiveRequest',
        // 		hasQualityTab: hasQualityTab
        // 	},
        // 	callback: function(self, success, response){
        // 		if (success) {
        // 			that.load.hide();
        // 			try {

        // 				var r = Ext.decode(response.responseText);

        // 				if ( r !== undefined && !r.success) {
        // 					Ext.Msg.show({
        // 						title: '경고'
        // 						,msg: r.msg
        // 						,icon: Ext.Msg.WARNING
        // 						,buttons: Ext.Msg.OK
        // 					});
        // 				}
        // 				if( !Ext.isEmpty(req_comment) ) {
        // 					//Ext.Msg.alert('요청사유', req_comment);
        // 				}
        // 			} catch (e) {
        // 				//alert(response.responseText)
        // 				//Ext.Msg.alert(e['name'], e['message'] );
        // 			}
        // 		} else {
        // 			//>>Ext.Msg.alert('서버 오류', response.statusText+'('+response.status+')');
        // 			Ext.Msg.alert(_text('MN00022'), response.statusText+'('+response.status+')');
        // 		}
        // 	}
        // });
      }
    },

    sm: selModel,
    cm: new Ext.grid.ColumnModel({
      defaults: {
        sortable: false
      },

      columns: [
        new Ext.grid.RowNumberer(),
        selModel,
        { header: '요청No', dataIndex: 'req_no', hidden: true },
        { header: '작업ID', dataIndex: 'task_id', hidden: true },
        { header: '요청구분', dataIndex: 'req_type', align: 'center', width: 90, renderer: mapReqType },
        { header: '콘텐츠ID', dataIndex: 'nps_content_id', align: 'center', width: 70, hidden: true },
        { header: '요청상태', dataIndex: 'req_status', align: 'center', width: 70, renderer: mapStatus, menuDisabled: true },
        { header: '작업상태', dataIndex: 'status', align: 'center', width: 70, renderer: mapTaskStatus, menuDisabled: true },
        { header: '유형', dataIndex: 'ud_content_title', align: 'center', width: 70 },
        //{header: '필수데이터', dataIndex: 'required_input', align: 'center', width: 70, renderer: mapRequired, menuDisabled: true},
        { header: 'QC', dataIndex: 'qualitycheck', align: 'center', width: 60, hidden: true, renderer: mapQualityCheck, menuDisabled: true },
        { header: '<center>제목</center>', dataIndex: 'title', align: 'left', menuDisabled: true, width: 250 },
        { header: '<center>미디어ID</center>', dataIndex: 'archive_id', align: 'center', menuDisabled: true, width: 150 },
        { header: '용량', dataIndex: 'filesize', align: 'center', menuDisabled: true, width: 140 },
        { header: '미디어 경로', dataIndex: 'path', align: 'center', menuDisabled: true, width: 400 },

        { header: '요청자', dataIndex: 'req_user_id', align: 'center', menuDisabled: true, hidden: true, },
        { header: '요청자', dataIndex: 'req_user_nm', align: 'center', menuDisabled: true, width: 110 },
        { header: '요청일시', dataIndex: 'req_time', align: 'center', sortable: true, menuDisabled: true, width: 140, renderer: showDate },
        { header: '<center>요청사유</center>', dataIndex: 'req_comment', align: 'left', menuDisabled: true, width: 250 },
        { header: '승인자', dataIndex: 'appr_user_id', align: 'center', menuDisabled: true, width: 110, hidden: true },
        { header: '승인자', dataIndex: 'appr_user_nm', align: 'center', menuDisabled: true, width: 110, renderer: showEmpty },
        { header: '승인일시', dataIndex: 'appr_time', align: 'center', menuDisabled: true, width: 140, renderer: showDate },
        { header: '<center>승인(반려)내용</center>', dataIndex: 'appr_comment', align: 'left', menuDisabled: true, width: 250 }
      ]
    }),

    view: new Ext.ux.grid.BufferView({
      rowHeight: 20,
      scrollDelay: false,
      emptyText: '결과 값이 없습니다.'
    }),

    bbar: new Ext.PagingToolbar({
      store: request_manage_store,
      pageSize: request_page_size,
      items: ['->', {
        id: 'request_toolbartext',
        xtype: 'tbtext',
        pageX: '100',
        pageY: '100',
        text: "요청 : " + total_list + " 건"
      }]
    }),
    _showEventWin: null,
    _showEventWinShow: function (taskId) {
      if (this._showEventWin != null) {
        this._showEventWin._load(taskId);
        this._showEventWin.show();
      } else {
        this._showEventWin = new Custom.Archive.EventWindow({
        });
        this._showEventWin._load(taskId);
        this._showEventWin.show();
      }
    }
  });

  return request_manage_grid;

  function showEmpty(value) {
    if (Ext.isEmpty(value)) value = '-';
    return value;
  }

  function showDate(value) {
    if (Ext.isEmpty(value)) value = '-';
    else value = Ext.util.Format.date(value, 'Y-m-d H:i:s');
    return value;
  }

  function mapStatus(value) {
    switch (value) {
      case '1':
        value = '요청';
        break;
      case '3':
        value = '<font color=crimson><b>반려</b></font>';
        break;
      case '2':
        value = '<font color=royalblue><b>승인</b></font>';
        break;
      case '':
        value = '-';
        break;
    }
    return value;
  }

  function mapTaskStatus(value) {
    switch (value) {
      case 'queue':
      case 'assigning':
        value = '대기';
        break;
      case 'error':
        value = '<font color=crimson><b>실패</b></font>';
        break;
      case 'complete':
        value = '<font color=royalblue><b>완료</b></font>';
        break;
      case 'processing':
        value = '<font color=limegreen><b>진행중</b></font>';
        break;
      case 'scheduled':
        value = '<font color=limegreen><b>예약됨</b></font>';
        break;
      case 'cancel':
      case 'canceling':
      case 'canceled':
        value = '<font color=royalblue><b>취소</b></font>';
        break;
      default:
        value = '-';
        break;
    }
    return value;
  }

  function mapRequired(value) {
    switch (value) {
      case 'N':
        value = '<font color=royalblue><b>완료</b></font>';
        break;
      case 'Y':
        value = '<font color=crimson><b>미입력</b></font>';
        break;
      default:
        value = '-';
    }

    return value;
  }

  function mapQualityCheck(value) {
    switch (value) {
      case 'complete':
        value = '<font color=royalblue><b>완료</b></font>';
        break;
      case 'error':
        value = '<font color=crimson><b>오류</b></font>';
        break;
      default:
        value = '-';
    }

    return value;
  }

  function mapReqType(value) {
    value = value.toLowerCase();
    switch (value) {
      case 'archive':
        value = '<font color=blue>아카이브</font>';
        break;
      case 'restore':
        value = '<font color=green>리스토어</font>';
        break;
      case 'pfr_restore':
        value = '<font color=olivedrab>Partial 리스토어</font>';
        break;
      case 'delete':
        value = '<font color=red>아카이브 삭제</font>';
        break;
      default:
        value = '-';
    }
    return value;
  }

})()