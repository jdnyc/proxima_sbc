Ext.ContentActions = {
  publishSns: function () {
    var sm = Ext.getCmp('tab_warp').getActiveTab().get(0).getSelectionModel();
    var record = sm.getSelected();
    // 사용금지 여부
    var usePrhibtAt = record.get('usr_meta').use_prhibt_at;
    if (usePrhibtAt == 'Y') {
      return Ext.Msg.alert('알림', '사용금지 된 콘텐츠 입니다. 아카이브팀에게 문의 바랍니다');
    }

    // 엠바고 해제 일시
    var embgRelisDt = record.get('usr_meta').embg_relis_dt;
    var embgAt = record.get('usr_meta').embg_at;

    if (!Ext.isEmpty(embgRelisDt) && embgAt == 'Y') {
      var embargoTimeStamp = YmdHisToDate(embgRelisDt).getTime();
      var nowTimeStamp = new Date().getTime();
      if (embargoTimeStamp > nowTimeStamp) {
        return Ext.Msg.alert('알림', '엠바고 해제일시가 지난 후 사용해주세요.');
      }
    }

    // Ext.data.Record[]
    var selectedContents = getSelectedContents();
    if (Array.isArray(selectedContents) && selectedContents.length > 1) {
      Ext.Msg.alert('알림', 'SNS게시는 일괄작업 할 수 없습니다.');
      return;
    }

    if (Ext.isEmpty(selectedContents) || selectedContents.length <= 0) {
      Ext.Msg.alert('알림', '게시할 콘텐츠를 선택해 주세요.');
      return;
    }

    var selectedContent = selectedContents[0];

    var components = [
      {
        name: 'Custom.SnsPublishWindow',
        isControl: true
      }
    ];
    Ext.Loader.load(getComponentUrls(components), function () {
      var snsPublishWindow = new Custom.SnsPublishWindow({
        contentId: selectedContent.get('content_id')
      });
      snsPublishWindow.show();
    });
  },
  archiveRequest: function () {
    Ext.Ajax.timeout = 300000; // 300 seconds

    var sm = Ext.getCmp('tab_warp').getActiveTab().get(0).getSelectionModel();

    var _check = sm.getSelections();
    var _ori_del_flag = 'N';
    var notAllowed = 'N';
    Ext.each(_check, function (r, i, a) {
      if (r.get('ori_status') == '1' || r.get('ori_size') <= 0) {
        _ori_del_flag = 'Y';
      }

      if (!Ext.isEmpty(r.json.ori_ext)) {
        var fileExt = r.json.ori_ext.toLowerCase();
      } else {
        var fileExt = '';
      }

      //mxf, mov 가 아니면 중단
      if (fileExt != 'mxf' && fileExt != 'mov') {
        notAllowed = 'Y';
      }
    });

    if (_ori_del_flag == 'Y') {
      Ext.Msg.alert(_text('MN00023'), _text('MSG02163'));
      return;
    }

    if (notAllowed == 'Y') {
      Ext.Msg.alert('알림', '아카이브 할 수 없는 콘텐츠 입니다.');
      return;
    }

    var archived_list = [];
    var _validates = [];
    var limit = 20;
    var ud_content_id = _check[0].get('ud_content_id');
    var genre_full_path = _check[0].json.genre_full_path;
    //console.log('_check:::', _check);
    //console.log('genre_full_path:::', genre_full_path);
    var str_test1 = /^\/0/;
    var str_test2 = /^\/-1/;

    if (_check.length > limit) {
      Ext.Msg.alert(
        '확인',
        '아카이브 요청은 한번에 ' + limit + '개까지만 가능합니다.'
      );
      return;
    }

    var stop = false;
    _check.some(function (record) {
      //console.log('record-', record);
      // if ( str_test1.test(record.get('category_full_path'))
      // 		&& record.get('ud_content_id') != '1'
      // 		&& record.get('ud_content_id') != '2'
      // 		&& record.get('ud_content_id') != '3'
      // 		&& record.get('ud_content_id') != '4'
      // 		&& record.get('ud_content_id') != '5'
      // 		&& record.get('ud_content_id') != '6') {
      // 	Ext.Msg.alert('확인', '제작프로그램은  촬영원본, 편집영상, 마스터, 클린, 오디오, 이미지, 문서, CG이미지만 아카이브 가능합니다.' + record.get('ud_content_id'));

      // 	stop = true;
      // } else if ( str_test2.test(record.get('category_full_path'))
      // 		&& record.get('ud_content_id') != '3'
      // 		&& record.get('ud_content_id') != '2') {
      // 	Ext.Msg.alert('확인', '토픽은 편집본과 완성본만 아카이브 가능합니다.');

      // 	stop = true;
      // } else if (record.get('archive_status')
      // 		&& record.get('archive_status') == 1) {
      // 	Ext.Msg.alert('확인', '이미 아카이브 요청된 콘텐츠 입니다.<br /><br />' + record.get('title') + '<br />');

      // 	stop = true;
      // } else if (record.get('archive_status')
      // 		&& record.get('archive_status') == 4) {
      // 	Ext.Msg.alert('확인', '아카이브된 콘텐츠 입니다.<br /><br />' + record.get('title') + '<br />');

      // 	stop = true;
      // } else if (record.get('ud_content_id') === '3' && record.json.qc_error_yn == "Y") {
      // 	Ext.Msg.alert('확인', '마스터본은 QC 작업이 완료되지 않은 파일은 아카이브 할 수 없습니다.<br /><br />' + record.get('title') + '<br />');

      // 	stop = true;
      // } else if (record.get('bs_content_id') != '57057' && Ext.isEmpty(record.get('proxy_path'))){
      // 	Ext.Msg.alert('확인', '저해상도 생성 전 파일은 아카이브 할 수 없습니다.<br /><br />' + record.get('title') + '<br />');

      // 	stop = true;
      // } else if (record.get('bs_content_id') != '57057' && Ext.isEmpty(record.get('thumb'))){
      // 	Ext.Msg.alert('확인', '썸네일 생성 전 파일은 아카이브 할 수 없습니다.<br /><br />' + record.get('title') + '<br />');

      // 	stop = true;
      // }

      _validates.push(record.data);

      return stop;
    });

    // if (stop) {
    // 	return;
    // }

    var mask = new Ext.LoadMask(Ext.getBody());
    mask.show();

    Ext.Ajax.request({
      url: '/store/validate_for_archive.php',
      params: {
        ud_content_id: ud_content_id,
        content_list: Ext.encode(_validates)
      },
      callback: function (self, success, response) {
        mask.hide();

        //NPS에서 DIVA연계를 할 경우에 대한 처리로직 추가. skc 2019.09.19

        //var v_archive_req_url = '/store/archive/request.php'; //DAS에 아카이브 요청자료 생성
        var v_archive_req_url = '/store/archive/request_archive.php'; //NPS에 아카이브 요청자료 생성
        var asterisk = '<span style="color: #f20606;" >*</span>';
        var result = Ext.decode(response.responseText);
        if (result.success) {
          var req_win = new Ext.Window({
            title: '아카이브 요청',
            width: 350,
            height: 290,
            modal: true,
            layout: 'fit',

            items: [
              {
                id: 'archive_request_form',
                xtype: 'form',
                padding: 5,
                defaults: {
                  labelSeparator: '',
                  anchor: '98%'
                },
                items: [
                  {
                    height: 150,
                    xtype: 'textarea',
                    name: 'comment',
                    allowBlank: false,
                    fieldLabel: asterisk + '요청사유'
                  }
                ]
              }
            ],
            buttonAlign: 'center',
            buttons: [
              {
                text: '요청',
                scale: 'medium',
                handler: function (btn, e) {
                  var sm = Ext.getCmp('tab_warp')
                    .getActiveTab()
                    .get(0)
                    .getSelectionModel();
                  var records = sm.getSelections();

                  if (sm.hasSelection()) {
                    var rs = [];

                    var form_data = Ext.getCmp('archive_request_form')
                      .getForm()
                      .getValues();

                      if(form_data.comment.trim() == '') {
                        Ext.Msg.alert(
                          '확인',
                          '요청사유를 입력해주세요.'
                        );
                        return;
                      }

                    // if (form_data.ud_content_id == undefined) {
                    //   Ext.Msg.alert("확인", "콘텐츠 유형을 선택하세요");
                    //   return;
                    // }

                    // var gtn = Ext.getCmp("arc_req_g_category")
                    //   .treePanel.getSelectionModel()
                    //   .getSelectedNode();

                    // if (Ext.isEmpty(gtn.attributes.id)) {
                    //   Ext.Msg.alert("확인", _text("MSG02543")); //최하위 장르를 선택해 주세요
                    //   return;
                    // }

                    Ext.each(records, function (r) {
                      rs.push(r.data);
                    });

                    req_win.getEl().mask('아카이브 요청 중입니다...');
                    Ext.Ajax.request({
                      url: v_archive_req_url,
                      timeout: 120000,
                      params: {
                        ud_content_id: form_data.ud_content_id,
                        req_comment: form_data.comment,
                        //genre: gtn.attributes.id,
                        contents: Ext.encode(rs)
                      },
                      callback: function (self, success, response) {
                        req_win.getEl().unmask();
                        if (success) {
                          try {
                            var result = Ext.decode(response.responseText);
                            if (result.success) {
                              Ext.getCmp('tab_warp')
                                .getActiveTab()
                                .get(0)
                                .getStore()
                                .reload();
                              req_win.close();

                              Ext.Msg.alert(
                                '확인',
                                '아카이브 요청 되었습니다.'
                              );
                            } else {
                              Ext.Msg.alert('확인', result.msg);
                            }
                          } catch (e) {
                            Ext.Msg.alert(e.name, e.message);
                          }
                        } else {
                          Ext.Msg.alert(
                            '서버 오류',
                            response.statusText + '(' + response.status + ')'
                          );
                        }
                      }
                    });
                  }
                }
              },
              {
                text: '취소',
                scale: 'medium',
                handler: function (btn, e) {
                  req_win.close();
                }
              }
            ]
          }).show();
        } else {
          Ext.Msg.alert('확인', result.msg);
        }
      }
    });
  },
  restoreRequest: function () {
    var sm = Ext.getCmp('tab_warp').getActiveTab().get(0).getSelectionModel();
    if (!sm.hasSelection()) {
      Ext.Msg.alert('알림', '콘텐츠를 선택 해 주세요');
      return;
    }
    var asterisk = '<span style="color: #f20606;" >*</span>';
    var rs = [];

    var win = new Ext.Window({
      title: '리스토어 요청',
      width: 400,
      height: 290,
      modal: true,
      border: true,
      frame: true,
      layout: 'fit',
      items: [
        {
          xtype: 'form',
          id: 'restore_request_form',
          padding: 5,
          defaults: {
            labelSeparator: '',
            anchor: '98%'
          },
          items: [
            {
              xtype: 'textfield',
              hidden: true,
              id: 'restore_request_user_id',
              value: ''
            },
            {
              hidden: true,
              xtype: 'datefield',
              name: 'restore_expire_date',
              fieldLabel: '사용기간',
              editable: false,
              format: 'Y-m-d',
              allformats: 'Ymd|ymd|Y-m-d|y-m-d|Y/m/d|Y/m/d',
              listeners: {
                render: function (self, date) {
                  var d = new Date();
                  var dd = d.add(Date.DAY, 180);
                  var max_d = d.add(Date.DAY, 365);
                  self.setMinValue(d.format('Y-m-d'));
                  self.setMaxValue(max_d.format('Y-m-d'));
                  self.setValue(dd);
                },
                select: function (self, date) { }
              }
            },
            {
              height: 150,
              xtype: 'textarea',
              fieldLabel: asterisk + '요청사유',
              name: 'restore_request_comnt',
              allowBlank: false,
            }
          ]
        }
      ],
      buttonAlign: 'center',
      buttons: [
        {
          text: '요청',
          scale: 'medium',
          handler: function (b, e) {
            var form = win.items.get(0);
            var form_data = Ext.getCmp('restore_request_form')
              .getForm()
              .getValues();
            //var v_target_ud_content_id = form_data.target_ud_content_id;
            // var restore_pgm_id = form
            //   .getForm()
            //   .findField("restore_program")
            //   .getValue();
            // var restore_pgm_nm = form
            //   .getForm()
            //   .findField("restore_program")
            //   .getRawValue();
            // var restore_epsd_id = form
            //   .getForm()
            //   .findField("restore_epsd")
            //   .getValue();
            // var restore_epsd_nm = form
            //   .getForm()
            //   .findField("restore_epsd")
            //   .getRawValue();
            var restore_request_comnt = form
              .getForm()
              .findField('restore_request_comnt')
              .getValue();
            var restore_expire_date =
              form
                .getForm()
                .findField('restore_expire_date')
                .getValue()
                .format('Ymd') + '000000';

            // if (Ext.isEmpty(v_target_ud_content_id)) {
            //   Ext.Msg.alert("확인", "콘텐츠 유형을 선택하세요");
            //   return;
            // }

            // if (restore_pgm_id.indexOf("_notallowed") != -1) {
            //   Ext.Msg.alert(
            //     "알림",
            //     "해당 프로그램은 스토리지 사용량이 80%를 초과하여 리스토어 할 수 없습니다"
            //   );
            //   return;
            // }

            if (restore_request_comnt.length > 1000) {
              Ext.Msg.alert(
                '알림',
                '요청사유는 1000자 이하로 입력하시기 바랍니다.'
              );
              return;
            } else if(restore_request_comnt.trim() == '') {
              Ext.Msg.alert(
                '알림',
                '요청사유를 입력해주시기 바랍니다.'
              );
              return;
            }

            var sm = Ext.getCmp('tab_warp')
              .getActiveTab()
              .get(0)
              .getSelectionModel();

            var rs = [];
            var records = sm.getSelections();
            Ext.each(records, function (r, i, a) {
              rs.push(r.get('content_id'));
            });

            var mask = new Ext.LoadMask(win.getEl(), {
              msg: '리스토어 요청중입니다...'
            });
            mask.show();

            Ext.Ajax.request({
              // url: "/store/archive/request_restore.php",
              url: '/api/v1/archive/request-restore',
              params: {
                content_ids: Ext.encode(rs),
                // target_ud_content_id: v_target_ud_content_id,
                // restore_pgm_id: restore_pgm_id,
                // restore_pgm_nm: restore_pgm_nm,
                // restore_epsd_id: restore_epsd_id,
                // restore_epsd_nm: restore_epsd_nm,
                restore_request_comnt: restore_request_comnt,
                restore_expire_date: restore_expire_date
              },
              callback: function (opt, success, response) {
                mask.hide();
                var res = Ext.decode(response.responseText);

                // if (success) {
                //   var res = Ext.decode(response.responseText);
                //   Ext.MessageBox.minWidth = 300;
                //   if (!res.success) {
                //     Ext.Msg.alert("알림", res.msg);
                //   } else {
                //     Ext.Msg.alert("알림", "리스토어 작업이 요청되었습니다.");
                //   }

                //   win.close();
                // } else {
                //   Ext.Msg.alert(
                //     "확인",
                //     "리스토어 작업 요청 실패(" + response.statusText + ")"
                //   );
                // }
                if (success) {
                  // Ext.MessageBox.minWidth = 300;
                  var res = Ext.decode(response.responseText);

                  // Ext.each(res.data, function(r) {
                  //   if (r.req_status === '2') {
                  //     Ext.Ajax.request({
                  //       url:
                  //         '/pages/menu/archive_management/action_archive_request.php',
                  //       params: {
                  //         action: 'approve',
                  //         'req_no[]': r.req_no
                  //       },
                  //       callback: function(opt, success, res) {
                  //         if (success) {
                  //           var msg = Ext.decode(res.responseText);
                  //           if (msg.success) {
                  //           } else {
                  //             Ext.Msg.alert('오류', msg.msg);
                  //           }
                  //         } else {
                  //           Ext.Msg.alert('서버 오류', res.statusText);
                  //         }
                  //       }
                  //     });
                  //   }
                  // });
                  Ext.Msg.alert('알림', '리스토어 작업이 요청되었습니다.');
                  win.close();
                } else {
                  var res = Ext.decode(response.responseText);
                  win.close();
                  Ext.Msg.alert('알림', res.msg);
                }
              }
            });
          }
        },
        {
          text: '취소',
          scale: 'medium',
          handler: function (b, e) {
            win.close();
          }
        }
      ],
      listeners: {
        afterrender: function (self) {
          /*
					var form = win.items.get(0);
					var pgm_id_field = form.getForm().findField('pgm_id');
					var pgm_nm_field = form.getForm().findField('pgm_nm');
					var epsd_id_field = form.getForm().findField('epsd_id');
					var epsd_no_field = form.getForm().findField('epsd_no');

					pgm_id_field.setValue(sel_pgm_id);
					pgm_nm_field.setValue(sel_pgm_nm);
					epsd_id_field.setValue(sel_epsd_id);
					epsd_no_field.setValue(sel_epsd_no);
					*/
        }
      }
    });
    win.show();
  },
  archiveDeleteRequest: function () {
    var sm = Ext.getCmp('tab_warp').getActiveTab().get(0).getSelectionModel();
    if (!sm.hasSelection()) {
      Ext.Msg.alert('알림', '콘텐츠를 선택 해 주세요');
      return;
    }

    var rs = [];

    var win = new Ext.Window({
      title: '아카이브 삭제 요청',
      width: 400,
      height: 290,
      modal: true,
      border: true,
      frame: true,
      layout: 'fit',
      items: [
        {
          xtype: 'form',
          id: 'restore_request_form',
          padding: 5,
          defaults: {
            labelSeparator: '',
            anchor: '98%'
          },
          items: [
            {
              xtype: 'textfield',
              hidden: true,
              id: 'restore_request_user_id',
              value: ''
            },
            {
              hidden: true,
              xtype: 'datefield',
              name: 'restore_expire_date',
              fieldLabel: '사용기간',
              editable: false,
              format: 'Y-m-d',
              allformats: 'Ymd|ymd|Y-m-d|y-m-d|Y/m/d|Y/m/d',
              listeners: {
                render: function (self, date) {
                  var d = new Date();
                  var dd = d.add(Date.DAY, 180);
                  var max_d = d.add(Date.DAY, 365);
                  self.setMinValue(d.format('Y-m-d'));
                  self.setMaxValue(max_d.format('Y-m-d'));
                  self.setValue(dd);
                },
                select: function (self, date) { }
              }
            },
            {
              height: 150,
              xtype: 'textarea',
              fieldLabel: '요청사유',
              name: 'restore_request_comnt'
            }
          ]
        }
      ],
      buttonAlign: 'center',
      buttons: [
        {
          text: '요청',
          scale: 'medium',
          handler: function (b, e) {
            var form = win.items.get(0);
            var form_data = Ext.getCmp('restore_request_form')
              .getForm()
              .getValues();

            var restore_request_comnt = form
              .getForm()
              .findField('restore_request_comnt')
              .getValue();
            var restore_expire_date =
              form
                .getForm()
                .findField('restore_expire_date')
                .getValue()
                .format('Ymd') + '000000';

            // if (Ext.isEmpty(v_target_ud_content_id)) {
            //   Ext.Msg.alert("확인", "콘텐츠 유형을 선택하세요");
            //   return;
            // }

            // if (restore_pgm_id.indexOf("_notallowed") != -1) {
            //   Ext.Msg.alert(
            //     "알림",
            //     "해당 프로그램은 스토리지 사용량이 80%를 초과하여 리스토어 할 수 없습니다"
            //   );
            //   return;
            // }

            if (restore_request_comnt.length > 1000) {
              Ext.Msg.alert(
                '알림',
                '요청사유는 1000자 이하로 입력하시기 바랍니다.'
              );
              return;
            }

            var sm = Ext.getCmp('tab_warp')
              .getActiveTab()
              .get(0)
              .getSelectionModel();

            var rs = [];
            var records = sm.getSelections();
            Ext.each(records, function (r, i, a) {
              rs.push(r.get('content_id'));
            });

            // var mask = new Ext.LoadMask(win.getEl(), {
            //   msg: "리스토어 요청중입니다..."
            // });
            // mask.show();

            // Ext.Ajax.request({
            //   url: "/store/archive/request_restore.php",
            //   params: {
            //     content_ids: Ext.encode(rs),
            //     // target_ud_content_id: v_target_ud_content_id,
            //     // restore_pgm_id: restore_pgm_id,
            //     // restore_pgm_nm: restore_pgm_nm,
            //     // restore_epsd_id: restore_epsd_id,
            //     // restore_epsd_nm: restore_epsd_nm,
            //     restore_request_comnt: restore_request_comnt,
            //     restore_expire_date: restore_expire_date
            //   },
            //   callback: function(opt, success, response) {
            //     mask.hide();

            //     if (success) {
            //       var res = Ext.decode(response.responseText);
            //       Ext.MessageBox.minWidth = 300;
            //       if (!res.success) {
            //         Ext.Msg.alert("알림", res.msg);
            //       } else {
            //         Ext.Msg.alert("알림", "리스토어 작업이 요청되었습니다.");
            //       }

            //       win.close();
            //     } else {
            //       Ext.Msg.alert(
            //         "확인",
            //         "리스토어 작업 요청 실패(" + response.statusText + ")"
            //       );
            //     }
            //   }
            // });
            Ext.Ajax.request({
              url: '/api/v1/archive/request-delete',
              params: {
                content_ids: Ext.encode(rs),
                // target_ud_content_id: v_target_ud_content_id,
                // restore_pgm_id: restore_pgm_id,
                // restore_pgm_nm: restore_pgm_nm,
                // restore_epsd_id: restore_epsd_id,
                // restore_epsd_nm: restore_epsd_nm,
                restore_request_comnt: restore_request_comnt,
                restore_expire_date: restore_expire_date
              },
              callback: function (opt, success, response) {
                if (success) {
                  // Ext.MessageBox.minWidth = 300;
                  var res = Ext.decode(response.responseText);
                  Ext.Msg.alert('알림', '아카이브 삭제 요청되었습니다.');

                  win.close();
                } else {
                  var res = Ext.decode(response.responseText);
                  win.close();
                  Ext.Msg.alert('실패', res.msg);
                }
              }
            });
          }
        },
        {
          text: '취소',
          scale: 'medium',
          handler: function (b, e) {
            win.close();
          }
        }
      ],
      listeners: {
        afterrender: function (self) {
          /*
					var form = win.items.get(0);
					var pgm_id_field = form.getForm().findField('pgm_id');
					var pgm_nm_field = form.getForm().findField('pgm_nm');
					var epsd_id_field = form.getForm().findField('epsd_id');
					var epsd_no_field = form.getForm().findField('epsd_no');

					pgm_id_field.setValue(sel_pgm_id);
					pgm_nm_field.setValue(sel_pgm_nm);
					epsd_id_field.setValue(sel_epsd_id);
					epsd_no_field.setValue(sel_epsd_no);
					*/
        }
      }
    });
    win.show();
  },
  extendedUse: function () {
    var _this = this;
    var dateForm = new Ext.form.FormPanel({
      padding: 5,
      defaults: {
        anchor: '95%'
      },
      border: false,
      items: [
        {
          xtype: 'datefield',
          fieldLabel: '만료 일자',
          name: 'expired_date',
          altFormats: 'Y-m-d|Ymd|YmdHis',
          format: 'Y-m-d',
          listeners: {
            select: function (self, date) {
              var newDate = new Date();
              if (newDate > date) {
                /**
                 * 이전 날짜보다 작은 값을 선택 했을 시
                 * // 이전날짜 선택시 null 값 입력
                 */
                self.setValue(null);
                Ext.Msg.alert('알림', '이전 날짜를 선택할 수 없습니다.');
              }
            }
          }
        }
      ],
      listeners: {
        afterrender: function (self) {
          var sm = Ext.getCmp('tab_warp')
            .getActiveTab()
            .get(0)
            .getSelectionModel();
          var record = sm.getSelected();
          // 만료 일자
          var expiredDate = record.get('expired_date');
          dateForm.getForm().findField('expired_date').setValue(expiredDate);
        }
      },
      buttons: [
        {
          text: '확인',
          scale: 'medium',
          handler: function (self) {
            var sm = Ext.getCmp('tab_warp')
              .getActiveTab()
              .get(0)
              .getSelectionModel();
            var record = sm.getSelected();
            var contentId = record.get('content_id');
            var getForm = dateForm.getForm();

            getForm.submit({
              method: 'PUT',
              url: '/api/v1/contents/' + contentId + '/expiredDate',
              success: function (form, action) {
                // Ext.Msg.alert('알림', '만료일자가 변경되었습니다.');
                var res = Ext.decode(action.response.responseText);
                Ext.Msg.show({
                  title: '알림',
                  msg: res.msg,
                  buttons: Ext.Msg.OK,
                  fn: function (btnId) {
                    if (btnId == 'ok') {
                      record.store.reload();
                      win.close();
                    }
                  }
                });
              },
              failure: function (form, action) {
                var res = Ext.decode(action.response.responseText);
                if (!res.success) {
                  Ext.Msg.alert('알림', res.msg);
                  win.close();
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
        }
      ]
    });
    var win = new Ext.Window({
      title: '삭제기간 연장',
      width: 400,
      autoHeight: true,
      modal: true,
      border: false,
      items: dateForm
    });
    return win.show();
  },
  prohibitedUse: function () {
    var sm = Ext.getCmp('tab_warp').getActiveTab().get(0).getSelectionModel();
    var record = sm.getSelected();
    var selections = sm.getSelections();
    // 사용여부 Y or N
    var prohibitedUseCheck = record.get('usr_meta').use_prhibt_at;

    var noSame = false;
    Ext.each(selections, function (r) {
      if (prohibitedUseCheck != r.get('usr_meta').use_prhibt_at) {
        noSame = true;
      }
    });
    if (noSame) {
      Ext.Msg.alert(_text('MN00023'), '사용금지 상태가 동일한 콘텐츠를 선택해주세요.');
    }

    function usePrhibtAt(useValue, usePrhibtSetResn) {
      var sm = Ext.getCmp('tab_warp').getActiveTab().get(0).getSelectionModel();
      var record = sm.getSelected();
      var sels = sm.getSelections();


      if (sels.length > 1) {

        var transactionIds = [];
        var waitMsg = Ext.Msg.wait('일괄 처리중...');
        //건별 호출 
        Ext.each(sels, function (sel) {

          var contentId = sel.get('content_id');

          var transactionId = Ext.Ajax.request({
            method: 'PUT',
            url: '/api/v1/contents/' + contentId + '/prohibited-use',
            params: {
              use_prhibt_at: useValue,
              use_prhibt_set_resn: usePrhibtSetResn
            },
            callback: function (opt, success, response) {
            }
          });
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
            Ext.Msg.alert('알림', '상태가 변경되었습니다.');
            Ext.getCmp('tab_warp')
              .getActiveTab()
              .get(0)
              .getStore()
              .reload();
            win.close();
          }
        }), 1000);

      } else {

        var contentId = record.get('content_id');
        var waitMsg = Ext.Msg.wait('처리 중입니다.', '처리중...');
        return Ext.Ajax.request({
          method: 'PUT',
          url: '/api/v1/contents/' + contentId + '/prohibited-use',
          params: {
            use_prhibt_at: useValue,
            use_prhibt_set_resn: usePrhibtSetResn
          },
          callback: function (opt, success, response) {
            waitMsg.hide();
            var res = Ext.decode(response.responseText);
            Ext.Msg.show({
              title: '알림',
              msg: res.msg,
              buttons: Ext.Msg.OK,
              fn: function (btnId) {
                if (btnId == 'ok') {
                  Ext.getCmp('tab_warp')
                    .getActiveTab()
                    .get(0)
                    .getStore()
                    .reload();
                  win.close();
                }
              }
            });
          }
        });
      }
    }
    var prohibitedUseReason = new Ext.form.TextArea({
      name: 'use_prhibt_set_resn',
      allowBlank: false,
      region: 'center'
    });
    var enableButton = new Ext.Button({
      text: '사용금지',
      // flex: 1,
      // height: 50,
      scale: 'medium',
      handler: function (self) {
        Ext.Msg.show({
          title: '알림',
          msg: '해당 미디어를 사용금지 설정 하시겠습니까?',
          buttons: Ext.Msg.OKCANCEL,
          fn: function (btnId) {
            if (btnId == 'ok') {
              var usePrhibtSetResn = prohibitedUseReason.getValue();
              if (prohibitedUseReason.isValid()) {
                usePrhibtAt('Y', usePrhibtSetResn);
              } else {
                Ext.Msg.alert('알림', '사유를 입력해주세요.');
              }
            }
          }
        });
      }
    });
    var disableButton = new Ext.Button({
      text: '사용금지 해제',
      // flex: 1,
      // height: 50,
      scale: 'medium',
      handler: function (self) {
        Ext.Msg.show({
          title: '알림',
          msg: '해당 미디어를 사용금지 해제 하시겠습니까?',
          buttons: Ext.Msg.OKCANCEL,
          fn: function (btnId) {
            if (btnId == 'ok') {
              var usePrhibtSetResn = prohibitedUseReason.getValue();
              usePrhibtAt('N', usePrhibtSetResn);
            }
          }
        });
      }
    });
    var label = new Ext.Container({
      autoHeight: true,
      margins: '5 5 5 5',
      html: '<sapn style="font-size:15px">사유</sapn>'
    });
    var field = new Ext.Container({
      layout: 'fit',
      items: [prohibitedUseReason]
    });
    var win = new Ext.Window({
      title: '사용금지 설정',
      width: 400,
      autoHeight: true,
      modal: true,
      border: false,
      // items: [
      //   new Ext.Container({
      //     layout: {
      //       type: 'hbox'
      //     },
      //     // items:
      //   })
      // ],
      buttons: [enableButton, disableButton],
      listeners: {
        afterrender: function (self) {
          if (
            typeof prohibitedUseCheck == 'undefined' ||
            prohibitedUseCheck == null ||
            prohibitedUseCheck == ''
          ) {
            win.items.insert(0, label);
            win.items.insert(1, field);
            disableButton.disable();
          } else {
            switch (prohibitedUseCheck) {
              case 'Y':
                enableButton.disable();
                win.items.insert(0, label);
                win.items.insert(1, field);
                break;
              case 'N':
                disableButton.disable();
                win.items.insert(0, label);
                win.items.insert(1, field);
                break;
            }
          }
        }
      }
    });
    return win.show();
  },
  loudnessViewer: function () {
    var sm = Ext.getCmp('tab_warp').getActiveTab().get(0).getSelectionModel();
    var record = sm.getSelected();
    var content_id = record.get('content_id');

    if (Ext.isEmpty(content_id)) {
      Ext.Msg.alert(_text('MN00023'), '대상 콘텐츠를 선택해주세요');
      return;
    }

    this.load = new Ext.LoadMask(Ext.getBody(), { msg: _text('MSG00143') });
    this.load.show();
    var that = this;
    if (!Ext.Ajax.isLoading(self.isOpen)) {
      this.isOpen = Ext.Ajax.request({
        url: '/javascript/ext.ux/Ariel.Nps.Loudness.php',
        params: {
          content_id: content_id
        },
        callback: function (self, success, response) {
          if (success) {
            that.load.hide();
            try {
              var r = Ext.decode(response.responseText);

              if (r !== undefined && !r.success) {
                if (r.code == 'session') {
                } else {
                  Ext.Msg.show({
                    title: '경고',
                    msg: r.msg,
                    icon: Ext.Msg.WARNING,
                    buttons: Ext.Msg.OK
                  });
                }
              }
            } catch (e) {
              //alert(response.responseText)
              //Ext.Msg.alert(e['name'], e['message'] );
            }
          } else {
            //>>Ext.Msg.alert('서버 오류', response.statusText+'('+response.status+')');
            Ext.Msg.alert(
              _text('MN00022'),
              response.statusText + '(' + response.status + ')'
            );
          }
        }
      });
    }
  },
  deleteFile: function (type) {
    if (Ext.isEmpty(type)) {
      type = 'delete';
    }
    var activeTab = Ext.getCmp('tab_warp').getActiveTab();
    var sm = activeTab.get(0).getSelectionModel();
    var selections = sm.getSelections();
    var isSameRegister = true;
    var form = new Ext.form.FormPanel({
      xtype: 'form',
      border: false,
      frame: true,
      padding: 5,
      labelWidth: 70,
      cls: 'change_background_panel',
      defaults: {
        anchor: '95%'
      },
      items: [
        {
          xtype: 'textarea',
          height: 50,
          fieldLabel: _text('MN00128'),
          allowBlank: false,
          blankText: _text('MSG02015'),
          msgTarget: 'under',
          name: 'delete_reason'
        }
      ]
    });
    var win = new Ext.Window({
      layout: 'fit',
      title: _text('MN00128'),
      modal: true,
      width: 500,
      height: 150,
      buttonAlign: 'center',
      items: form,
      buttons: [
        {
          text:
            '<span style="position:relative;top:1px;"><i class="fa fa-trash" style="font-size:13px;"></i></span>&nbsp;' +
            _text('MN00034'),
          scale: 'medium',
          handler: function (btn, e) {
            var deleteReason = form.getForm().findField('delete_reason');
            var isValid = deleteReason.isValid();
            // var isValid = Ext.getCmp('delete_reason').isValid();
            if (!isValid) {
              Ext.Msg.show({
                icon: Ext.Msg.INFO,
                title: _text('MN00024'), //확인
                msg: _text('MSG02015'),
                buttons: Ext.Msg.OK
              });
              return;
            }

            var sm = Ext.getCmp('tab_warp')
              .getActiveTab()
              .get(0)
              .getSelectionModel();
            var tm = deleteReason.getValue();

            var rs = [];
            var _rs = sm.getSelections();
            Ext.each(_rs, function (r, i, a) {
              rs.push({
                content_id: r.get('content_id'),
                delete_his: tm,
                reg_user_id: r.data.reg_user_id
              });
            });

            Ext.Msg.show({
              icon: Ext.Msg.QUESTION,
              title: _text('MN00024'),
              msg: _text('MSG00145'),
              buttons: Ext.Msg.OKCANCEL,
              fn: function (btnId, text, opts) {
                if (btnId == 'cancel') return;

                var ownerCt = Ext.getCmp('tab_warp').getActiveTab().get(0);
                ownerCt.sendAction(type, rs, ownerCt);
                win.destroy();
              }
            });
          }
        },
        {
          text:
            '<span style="position:relative;top:1px;"><i class="fa fa-close style="font-size:13px;"></i></span>&nbsp;' +
            _text('MN00004'),
          scale: 'medium',
          handler: function (btn, e) {
            win.destroy();
          }
        }
      ]
    });
    win.show();
  },
  downloadExcel: function () {
    var sm = Ext.getCmp('tab_warp').getActiveTab().get(0).getSelectionModel()
      .grid.id;
    var column_record = [];
    Ext.each(Ext.getCmp(sm).getColumnModel().columns, function (r) {
      column_record.push({
        header: r.header,
        id: r.id,
        dataIndex: r.dataIndex,
        width: r.width
      });
    });
    var data_record = [];

    Ext.each(Ext.getCmp(sm).getStore().data.items, function (r) {
      data_record.push(r.data);
    });

    function submitExcelExportForm(action) {
      // Ext.each(row, function(r) {
      //             var result = r.created_date;
      //             var year = result.getFullYear();
      //             var month = result.getMonth()+1;
      //             month = month >= 10 ? month : '0'+month;
      //             var day = result.getDate();
      //             day = day >=10 ? day: '0'+ day;
      //             r.created_date = year+"-"+month+"-"+day;
      //         });

      var isFrame = document.getElementsByName('_excelDownload');
      if (isFrame.length > 0) {
        var form = document.getElementsByName('_excelDownloadForm')[0];

        var col_data = document.createElement('input');
        col_data.setAttribute('name', 'column_record');
        col_data.setAttribute('value', Ext.encode(column_record));
        form.appendChild(col_data);

        var row_data = document.createElement('input');
        row_data.setAttribute('name', 'data_record');
        row_data.setAttribute('value', Ext.encode(data_record));
        form.appendChild(row_data);
        form.submit();
      } else {
        var doc = document,
          frame = doc.createElement('iframe');
        frame.style.display = 'none';
        doc.body.appendChild(frame);

        form = doc.createElement('form');
        form.method = 'post';
        form.name = '_excelDownloadForm';
        form.target = '_excelDownload';
        form.action = action;

        var col_data = document.createElement('input');
        col_data.setAttribute('name', 'column_record');
        col_data.setAttribute('value', Ext.encode(column_record));
        form.appendChild(col_data);

        var row_data = document.createElement('input');
        row_data.setAttribute('name', 'data_record');
        row_data.setAttribute('value', Ext.encode(data_record));
        form.appendChild(row_data);

        frame.appendChild(form);
        form.submit();
      }
      return true;
    }
    Ext.Msg.show({
      title: 'excel 다운로드',
      buttons: Ext.MessageBox.OKCANCEL,
      msg: '엑셀 파일을 다운로드 하시겠습니까?',
      fn: function (btn, text, opts) {
        if (btn == 'ok') {
          submitExcelExportForm('/custom/ktv-nps/store/excel_download.php');
        }
      }
    });
  },
  noticeSns: function () {
    return Ext.Msg.alert('알림', '준비중인 기능입니다.');
  },
  ConversionVideo: function (type) {
    var sm = Ext.getCmp('tab_warp').getActiveTab().get(0).getSelectionModel();
    var record = sm.getSelected();
    var contentId = record.get('content_id');
    var mediaType = 'proxy' + type;
    var channel = 'create_proxy_' + type;

    if (Ext.isEmpty(contentId)) {
      Ext.Msg.alert(_text('MN00023'), '대상 콘텐츠를 선택해주세요');
      return;
    }

    if (record.json.ori_status != 0) {
      Ext.Msg.alert(
        _text('MN00023'),
        '원본이 없습니다. 리스토어 후에 요청해주세요.'
      );
      return;
    }
    Ext.Msg.show({
      title: '알림',
      msg: ' 서비스 영상 생성 요청하시겠습니까?',
      buttons: Ext.Msg.OKCANCEL,
      fn: function (btnId) {
        if (btnId == 'ok') {
          Ext.Ajax.request({
            method: 'POST',
            url: '/api/v1/tasks/create-proxy-media',
            params: {
              content_id: contentId,
              channel: channel,
              media_type: mediaType
            },
            callback: function (opt, success, response) {
              var res = Ext.decode(response.responseText);
              if (res.success) {
                var msg = '작업요청 되었습니다';
              } else {
                if (res.msg) {
                  var msg = res.msg;
                } else {
                  var msg = '작업요청 실패하였습니다';
                }
              }

              Ext.Msg.show({
                title: '알림',
                msg: msg,
                buttons: Ext.Msg.OK,
                fn: function (btnId) {
                  if (btnId == 'ok') {
                  }
                }
              });
            }
          });
        }
      }
    });
    //return Ext.Msg.alert('알림', '준비중인 기능입니다.');
  },
  download: function () {
    var sm = Ext.getCmp('tab_warp').getActiveTab().get(0).getSelectionModel();
    var record = sm.getSelected();
    // 사용금지 여부
    // var usePrhibtAt = record.get('usr_meta').use_prhibt_at;
    // if(usePrhibtAt == 'Y'){
    //   return Ext.Msg.alert('알림','사용금지 된 콘텐츠 입니다.');
    // }

    // 엠바고 해제 일시
    // var embgRelisDt = record.get('usr_meta').embg_relis_dt;
    // var embgAt = record.get('usr_meta').embg_at;

    // if(!Ext.isEmpty(embgRelisDt) && (embgAt == 'Y')){
    //   var embargoTimeStamp = YmdHisToDate(embgRelisDt).getTime();
    //   var nowTimeStamp = new Date().getTime();
    //   if(embargoTimeStamp > nowTimeStamp){
    //     return Ext.Msg.alert('알림','엠바고 해제일시가 지난 후 다운로드 해주세요.');
    //   }
    // }

    var contentId = record.get('content_id');

    var mediaList = new Custom.MediaListGrid({
      contentId: contentId,
      height: 150
    });

    var udContentId = record.get('ud_content_id');

    var win = new Ext.Window({
      title: '다운로드',
      width: 500,
      autoHeight: true,
      modal: true,
      border: false,
      layout: 'fit',
      items: mediaList,
      buttons: [
        {
          text: '다운로드',
          scale: 'medium',
          handler: function (self) {
            var sm = mediaList.getSelectionModel();
            if (sm.hasSelection()) {
              var selectMediaType = sm.getSelected().get('media_type');
              if (selectMediaType == 'proxy15m1080' || selectMediaType == 'proxy15m1080logo') {
                mediaList.isContentDownloadGrant(udContentId, userId);
              } else {
                mediaList.downloadHandler();
              }
            } else {
              Ext.Msg.alert('알림', '다운로드 할 목록을 선택해 주세요.');
            }
          }
        },
        {
          text: '닫기',
          scale: 'medium',
          handler: function (self) {
            win.close();
          }
        }
      ]
    });
    return win.show();
  },
  loudnessMeasure: function () {
    var sm = Ext.getCmp('tab_warp').getActiveTab().get(0).getSelectionModel();
    var record = sm.getSelected();
    var contentId = record.get('content_id');

    if (Ext.isEmpty(contentId)) {
      Ext.Msg.alert(_text('MN00023'), '대상 콘텐츠를 선택해주세요');
      return;
    }
    Ext.Msg.show({
      title: '알림',
      msg: '라우드니스 측정작업 요청하시겠습니까?',
      buttons: Ext.Msg.OKCANCEL,
      fn: function (btnId) {
        if (btnId == 'ok') {
          Ext.Ajax.request({
            method: 'PUT',
            url: '/api/v1/contents/' + contentId + '/loudness-measure',
            params: {},
            callback: function (opt, success, response) {
              var res = Ext.decode(response.responseText);
              if (res.success) {
                var msg = '작업요청 되었습니다';
              } else {
                var msg = '작업요청 실패하였습니다';
              }

              Ext.Msg.show({
                title: '알림',
                msg: msg,
                buttons: Ext.Msg.OK,
                fn: function (btnId) {
                  if (btnId == 'ok') {
                  }
                }
              });
            }
          });
        }
      }
    });
  },
  adminMenu: function (channel) {
    var sm = Ext.getCmp('tab_warp').getActiveTab().get(0).getSelectionModel();
    var record = sm.getSelected();
    var contentId = record.get('content_id');

    if (Ext.isEmpty(contentId)) {
      Ext.Msg.alert(_text('MN00023'), '대상 콘텐츠를 선택해주세요');
      return;
    }

    var params = {
      channel: channel,
      content_id: contentId
    };
    Ext.Msg.show({
      title: '알림',
      msg: '작업 요청하시겠습니까?',
      buttons: Ext.Msg.OKCANCEL,
      fn: function (btnId) {
        if (btnId == 'ok') {
          Ext.Ajax.request({
            method: 'POST',
            url: '/api/v1/tasks/workflow',
            params: params,
            callback: function (opt, success, response) {
              var res = Ext.decode(response.responseText);
              if (res.success) {
                var msg = '작업요청 되었습니다';
              } else {
                if (res.msg) {
                  var msg = res.msg;
                } else {
                  var msg = '작업요청 실패하였습니다';
                }
              }

              Ext.Msg.show({
                title: '알림',
                msg: msg,
                buttons: Ext.Msg.OK,
                fn: function (btnId) {
                  if (btnId == 'ok') {
                  }
                }
              });
            }
          });
        }
      }
    });
  },
  sendToMplayout: function (type) {
    var sm = Ext.getCmp('tab_warp').getActiveTab().get(0).getSelectionModel();
    var record = sm.getSelected();
    var contentId = record.get('content_id');
    var channel = 'send_to_mplayout_' + type;
    var mediaType = 'proxy' + type;
    if (Ext.isEmpty(contentId)) {
      Ext.Msg.alert(_text('MN00023'), '대상 콘텐츠를 선택해주세요');
      return;
    }

    Ext.Msg.show({
      title: '알림',
      msg: ' 전송 요청하시겠습니까?',
      buttons: Ext.Msg.OKCANCEL,
      fn: function (btnId) {
        if (btnId == 'ok') {
          Ext.Ajax.request({
            method: 'POST',
            url: '/api/v1/tasks/send-to-media',
            params: {
              content_id: contentId,
              channel: channel,
              media_type: mediaType
            },
            callback: function (opt, success, response) {
              var res = Ext.decode(response.responseText);
              if (res.success) {
                var msg = '작업요청 되었습니다';
              } else {
                if (res.msg) {
                  var msg = res.msg;
                } else {
                  var msg = '작업요청 실패하였습니다';
                }
              }

              Ext.Msg.show({
                title: '알림',
                msg: msg,
                buttons: Ext.Msg.OK,
                fn: function (btnId) {
                  if (btnId == 'ok') {
                  }
                }
              });
            }
          });
        }
      }
    });
  },
  updateContentType: function () {
    var radioGroupId = Ext.id();
    var win = new Ext.Window({
      title: '콘텐츠 유형 변경',
      width: 250,
      autoHeight: true,
      layout: 'fit',
      modal: true,
      items: [{
        layout: 'hbox',
        autoHeight: true,
        margins: '10 10 10 10',
        items: [{
          flex: 1,
          border: false
        }, {
          xtype: 'radiogroup',
          columns: 1,
          vertical: true,
          flex: 1,
          id: radioGroupId,
          labelAlign: 'center',
          items: [
            { boxLabel: '원본', name: 'cntnts_ty_change', inputValue: 1, checked: true },
            { boxLabel: '클립본', name: 'cntnts_ty_change', inputValue: 7 },
            { boxLabel: '뉴스편집본', name: 'cntnts_ty_change', inputValue: 9 },
            { boxLabel: '마스터본', name: 'cntnts_ty_change', inputValue: 3 },
            { boxLabel: '클린본', name: 'cntnts_ty_change', inputValue: 2 }
          ]
        }, {
          flex: 1,
          border: false
        }]
      }],
      buttonAlign: 'center',
      buttons: [{
        text: '변경',
        scale: 'medium',
        handler: function (self) {

          var activeTab = Ext.getCmp('tab_warp').getActiveTab();
          var sm = activeTab.get(0).getSelectionModel();
          var record = sm.getSelected();
          var contentId = record.get('content_id');
          var udContentId = record.get('ud_content_id');
          var radioGroup = Ext.getCmp(radioGroupId);
          var selectedRadio = radioGroup.getValue();
          var radioValue = selectedRadio.inputValue;
          var radioName = selectedRadio.boxLabel

          if (radioValue == udContentId) {
            return Ext.Msg.alert('알림', '변경할 유형을 선택해주세요.');
          }

          Ext.Msg.show({
            title: '알림',
            msg: '[' + radioName + ']으로 변경하시겠습니까?',
            buttons: Ext.Msg.OKCANCEL,
            fn: function (btnId) {
              if (btnId == 'ok') {
                var loadMask = new Ext.LoadMask(Ext.getBody(), {
                  msg: '변경중입니다..'
                });
                loadMask.show();
                Ext.Ajax.request({
                  url: '/api/v1/contents/' + contentId + '/type',
                  method: 'PUT',
                  params: {
                    ud_content_id: radioValue
                  },
                  callback: function (opt, success, response) {
                    var res = Ext.decode(response.responseText);
                    if (success) {
                      if (res.success) {
                        Ext.defer(function () {
                          Ext.getCmp('tab_warp')
                            .getActiveTab()
                            .get(0)
                            .getStore()
                            .reload();
                          Ext.Msg.alert('알림', '[' + radioName + ']으로 변경되었습니다.');
                          loadMask.hide();
                          win.destroy();
                        }, 1000);
                      } else {
                        loadMask.hide();
                      }
                    } else {
                      loadMask.hide();
                      Ext.Msg.alert(
                        '서버 오류',
                        response.statusText + '(' + response.status + ')'
                      );
                    }
                  }
                });
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
    }).show();
  }
};

Ext.ContentEvents = {
  contentHidden: function (menuBox, self, record) {
    self.permission_code = ['archive', 'admin'];
    if (!Ext.ContentEvents.checkPermission(self)) {
      menuBox.remove(self);
      return;
    }
    var isHidden = record.get('is_hidden');
    var contentId = record.get('content_id');
    var changeHiddenValue = 0;
    var text = '숨김 요청';
    switch (isHidden) {
      case 'Y':
        self.setText('콘텐츠 숨김 해제');
        changeHiddenValue = 0;
        text = '숨김 해제요청';
        break;
      case 'N':
        self.setText('콘텐츠 숨김');
        changeHiddenValue = 1;
        text = '숨김 요청';
        break;
    }

    self.on('click', function (self) {
      Ext.Msg.show({
        title: '알림',
        msg: '콘텐츠를 ' + text + ' 하시겠습니까?',
        buttons: Ext.Msg.OKCANCEL,
        fn: function (btnId) {
          if (btnId == 'ok') {
            var loadMask = new Ext.LoadMask(Ext.getBody(), {
              msg: text + ' 중입니다..'
            });
            loadMask.show();
            Ext.Ajax.request({
              url: '/api/v1/contents/' + contentId + '/hidden',
              method: 'PUT',
              params: {
                is_hidden: changeHiddenValue
              },
              callback: function (opt, success, response) {
                var res = Ext.decode(response.responseText);
                if (res.success) {
                  Ext.defer(function () {
                    Ext.getCmp('tab_warp')
                      .getActiveTab()
                      .get(0)
                      .getStore()
                      .reload();
                    Ext.Msg.alert('알림', text + '처리되었습니다.');
                    loadMask.hide();
                  }, 1000);
                } else {
                  loadMask.hide();
                }
              }
            });
          }
        }
      });
    });
  },
  checkPermission: function (self) {
    var isPermission = false;
    if (!Ext.isEmpty(self)) {
      var permissionArray = self.permission_code;
    }

    if (!Ext.isEmpty(permissionArray)) {
      var permissions = self.permissions;
      Ext.each(permissionArray, function (code) {
        if (permissions.indexOf(code) != -1) {
          isPermission = true;
          return isPermission;
        }
      });

      return isPermission;
    }
    return isPermission;
  }
};
