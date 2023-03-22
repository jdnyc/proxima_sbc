Ext.ns('Ariel.Panel.InfoReport');
(function () {
  function showDetail(selected) {
    var detail_id, action;
    if (Ext.getCmp('tab_list').activeTab.id == 'tab_q') {
      action = 'show_detail_rundown';
      detail_id = 'rd_id';
    } else {
      action = 'show_detail';
      detail_id = 'artcl_id';
    }

    Ext.Ajax.request({
      url: '/store/request_zodiac/get_article.php',
      params: {
        artcl_id: selected.get(detail_id),
        rd_seq: selected.get('rd_seq'),
        action: action
      },
      callback: function (options, success, response) {
        if (success) {
          //var d = new Date();
          //console.log('return get_article'+d.getTime());
          try {
            r = Ext.decode(response.responseText);
            if (r.success) {
              Ext.Ajax.request({
                url: '/javascript/withZodiac/viewDetailArticle.php',
                params: {
                  artcl_id: selected.get(detail_id),
                  data: Ext.encode(r)
                },
                callback: function (options, success, response) {
                  if (success) {
                    try {
                      Ext.decode(response.responseText);
                      //var d = new Date();
                      //console.log('return viewDetailArticle'+d.getTime());
                    } catch (e) {
                      Ext.Msg.alert(e.name, e.message);
                    }
                  } else {
                    Ext.Msg.alert(_text('MN02008'), response.statusText); //'서버 오류'
                  }
                }
              });
            }
          } catch (e) {
            Ext.Msg.alert(e.name, e.message);
          }
        } else {
          Ext.Msg.alert(_text('MN02008'), response.statusText); //'서버 오류'
        }
      }
    });
  }

  function showContent(self) {
    var sm = self.getSelectionModel().getSelected();

    var content_id = sm.get('media_id');

    //>>self.load = new Ext.LoadMask(Ext.getBody(), {msg: '상세 정보를 불러오는 중입니다...'});
    self.load = new Ext.LoadMask(Ext.getBody(), { msg: _text('MSG00143') });
    self.load.show();
    var that = self;

    if (!Ext.Ajax.isLoading(self.isOpen)) {
      self.isOpen = Ext.Ajax.request({
        url: '/javascript/ext.ux/Ariel.DetailWindow.php',
        params: {
          content_id: content_id,
          win_type: 'zodiac',
          record: Ext.encode(sm.json)
        },
        callback: function (self, success, response) {
          if (success) {
            that.load.hide();
            try {
              var r = Ext.decode(response.responseText);

              if (r !== undefined && !r.success) {
                Ext.Msg.show({
                  //title: '경고'
                  title: _text('MN00021'),
                  msg: r.msg,
                  icon: Ext.Msg.WARNING,
                  buttons: Ext.Msg.OK
                });
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
    } else {
      that.load.hide();
    }
  }

  function unMatching(type) {
    var grid_article, grid_matching, action, request_id;
    if (type == 'grid_detail') {
      action = 'unmatching_article';
      grid_article = 'grid_article';
      grid_matching = 'grid_detail';
      request_id = 'artcl_id';
    } else {
      action = 'unmatching_rundown';
      grid_article = 'grid_article_q';
      grid_matching = 'grid_detail_q';
      request_id = 'rd_id';
    }
    if (
      Ext.isEmpty(
        Ext.getCmp(grid_article)
          .getSelectionModel()
          .getSelected()
      ) ||
      Ext.isEmpty(
        Ext.getCmp(grid_matching)
          .getSelectionModel()
          .getSelected()
      )
    ) {
      //Ext.Msg.alert('알림','먼저 대상을 선택 해 주시기 바랍니다.');
      Ext.Msg.alert(_text('MN00023'), _text('MSG01005'));
    } else {
      var sel_article = Ext.getCmp(grid_article)
        .getSelectionModel()
        .getSelected();
      var sel_matching = Ext.getCmp(grid_matching)
        .getSelectionModel()
        .getSelected();

      Ext.Msg.show({
        title: _text('MN00024'), //확인
        msg: _text('MN02118') + ' : ' + _text('MSG02039'), //매칭 해제 : 이 작업을 진행하시겠습니까?
        buttons: Ext.Msg.OKCANCEL,
        fn: function (btn) {
          if (btn == 'ok') {
            Ext.Ajax.request({
              url: '/store/request_zodiac/get_article.php',
              params: {
                action: action,
                artcl_id: sel_article.get(request_id),
                rd_seq: sel_article.get('rd_seq'),
                content_id: sel_matching.get('media_id'),
                plyout_id: sel_matching.get('playout_id'),
                type_content: Ext.getCmp('tab_content_list').activeTab.id
              },
              callback: function (opt, success, response) {
                try {
                  var r = Ext.decode(response.responseText);
                  if (r.success) {
                    Ext.getCmp(grid_matching)
                      .getStore()
                      .reload();
                  }
                } catch (e) {
                  //console.log(e);
                }
              }
            });
          }
        }
      });
    }
  }

  function transmissionContent(type) {
    var content_ids = new Array();
    var contents = Ext.getCmp('tab_content_list')
      .activeTab.get(0)
      .get(1)
      .getSelectionModel()
      .getSelections();
    Ext.each(contents, function (r) {
      content_ids.push(r.get('content_id'));
    });
    if (content_ids.length < 1) {
      //Ext.Msg.alert('알림','먼저 대상을 선택 해 주시기 바랍니다.');
      Ext.Msg.alert(_text('MN00023'), _text('MSG01005'));
    } else {
      Ext.Ajax.request({
        url: '/store/request_zodiac/transmission_content.php',
        params: {
          content_ids: Ext.encode(content_ids)
        },
        callback: function (opt, success, response) {
          try {
            var r = Ext.decode(response.responseText);
            if (r.success) {
              Ext.Msg.alert(_test('MN00023'), r.msg); //'알림'
            }
          } catch (e) {
            //console.log(e);
          }
        }
      });
    }
  }

  Ariel.Panel.InfoReport = Ext.extend(Ext.grid.GridPanel, {
    cls: 'proxima_customize',
    initComponent: function (config) {
      var _this = this;

      if (this.gridtype == 'listArticle') {
        var store_article = new Ext.data.JsonStore({
          url: '/store/request_zodiac/get_article.php',
          root: 'data',
          //autoLoad : true,
          //currentRow : 0,
          fields: [
            'artcl_id',
            'apprv_div_nm',
            'artcl_div_nm',
            'artcl_titl',
            'prd_div_nm',
            'inputr_nm',
            'video_count',
            'grphc_count',
            'issu_nm',
            'artcl_fld_nm',
            'artcl_frm_cd',
            'artcl_frm_nm',
            'rptr_nm',
            'rd_seq',
            'rd_id',
            'brdc_pgm_nm'
          ],
          listeners: {
            load: function (store, records, opts) { }
          }
        });
        Ext.apply(
          this,
          {
            defaults: {
              border: false,
              margins: '10 30 10 10'
            },
            border: false,
            frame: false,
            //title: '일반기사목록',
            //flex: 1,
            loadMask: true,
            store: store_article,
            viewConfig: {
              forceFit: true,
              getRowClass: function(record, index, rowParams) {
                var videoCount = parseInt(record.get('video_count'));
                // 영상 매핑이 안되어 있으면 색깔 표시
                if(videoCount > 0) {
                  return;
                }
                return 'mapping__listcontent-row';
              },
              //>>emptyText: '결과값이 없습니다.'
              emptyText: _text('MSG00148')
            },
            selModel: new Ext.grid.RowSelectionModel({
              singleSelect: true,
              listeners: {
                rowselect: function (self) {
                  var detail_id, action, request_id;
                  if (Ext.getCmp('tab_list').activeTab.id == 'tab_q') {
                    detail_id = 'grid_detail_q';
                    action = 'list_rundown_maching';
                    request_id = 'rd_id';
                  } else {
                    detail_id = 'grid_detail';
                    action = 'list_detail';
                    request_id = 'artcl_id';
                  }
                  var grid_selection = self.getSelected();
                  Ext.getCmp(detail_id)
                    .getStore()
                    .load({
                      params: {
                        action: action,
                        artcl_id: grid_selection.get(request_id),
                        rd_seq: grid_selection.get('rd_seq'),
                        type_content: Ext.getCmp('tab_content_list').activeTab
                          .id
                      }
                    });
                },
                rowdeselect: function (self) { }
              }
            }),
            listeners: {
              afterrender: function (self) {
                var search_form, action;
                if (self.id == 'grid_article') {
                  action = 'list_article';
                  //search_form = Ext.encode(Ext.getCmp('search_form').getForm().getValues());
                  var search_form = new Object();
                  search_form.apprv_div_cd = Ext.getCmp(
                    'apprv_div_cd'
                  ).getValue();
                  search_form.artcl_frm_cd = Ext.getCmp(
                    'artcl_frm_cd'
                  ).getValue();
                  if(!Ext.isEmpty(Ext.getCmp('start_date'))){
                    search_form.start_date = Ext.getCmp('start_date')
                    .getValue()
                    .format('Y-m-d')
                    .trim();
                  }else{
                    search_form.start_date = Ext.getCmp('start_date_infoReport')
                    .getValue()
                    .format('Y-m-d')
                    .trim();
                  }
                  
                  if(!Ext.isEmpty(Ext.getCmp('end_date'))){
                    search_form.end_date = Ext.getCmp('end_date')
                    .getValue()
                    .format('Y-m-d')
                    .trim();
                  }else{
                    search_form.end_date = Ext.getCmp('end_date_infoReport')
                    .getValue()
                    .format('Y-m-d')
                    .trim();
                  }

                  search_form.artcl_titl = Ext.getCmp('artcl_titl').getValue();

                  self.store.load({
                    params: {
                      action: action,
                      search: Ext.encode(search_form)
                    }
                  });
                }
              },
              viewready: function (self) { },
              rowdblclick: function (self, row_index, e) {
                Ext.getBody().mask(' ');
                var grid_selection = self.getSelectionModel().getSelected();

                if (Ext.isEmpty(grid_selection)) {
                  //Ext.Msg.alert('알림','기사 정보가 없습니다.');
                  Ext.Msg.alert(_text('MN00023'), _text('MSG02032'));
                } else {
                  //var d = new Date();
                  //console.log('rowdblclick'+d.getTime());
                  showDetail(grid_selection);
                }
              }
            },
            colModel: new Ext.grid.ColumnModel({
              defaults: {
                //menuDisabled: true
              },
              columns: [
                new Ext.grid.RowNumberer(),
                {
                  header: 'id',
                  dataIndex: 'artcl_id',
                  width: 20,
                  hidden: true,
                  align: 'center'
                },
                //'상태'
                {
                  header: _text('MN00138'),
                  dataIndex: 'apprv_div_nm',
                  width: 20,
                  align: 'center'
                },
                //'이슈'
                {
                  header: '<center>' + _text('MN02112') + '</center>',
                  width: 20,
                  dataIndex: 'issu_nm'
                },
                //'대분류'
                {
                  header: _text('MN02113'),
                  dataIndex: 'artcl_fld_nm',
                  width: 20,
                  align: 'center'
                },
                //'제목'
                {
                  header: '<center>' + _text('MN00249') + '</center>',
                  dataIndex: 'artcl_titl'
                },
                //'프로그램명'
                {
                  header: '<center>' + '프로그램명' + '</center>',
                  dataIndex: 'brdc_pgm_nm',
                  width: 70
                },
                //'형식'
                {
                  header: _text('MN00310'),
                  dataIndex: 'artcl_frm_nm',
                  width: 20,
                  align: 'center'
                },
                //'기자'
                {
                  header: _text('MN02114'),
                  dataIndex: 'rptr_nm',
                  align: 'center',
                  width: 20,
                  align: 'center'
                },
                //'영상수'
                {
                  header: _text('MN02115'),
                  dataIndex: 'video_count',
                  align: 'center',
                  width: 20
                },
                //'그래픽수'
                {
                  header: _text('MN02116'),
                  dataIndex: 'grphc_count',
                  align: 'center',
                  width: 20
                }
              ]
            }),
            //sm: new Ext.grid.RowSelectionModel({
            //singleSelect: true
            //}),
            bbar: {
              xtype: 'paging',
              pageSize: 20,
              displayInfo: true,
              store: store_article
            }
          },
          config || {}
        );
      } else if (this.gridtype == 'listImage') {
        var store_detail = new Ext.data.JsonStore({
          url: '/store/request_zodiac/get_article.php',
          root: 'data',
          //autoLoad : true,
          fields: [
            'artcl_id',
            'ord',
            'media_nm',
            'part',
            'snd_st',
            'playout_yn',
            'playout_id',
            'media_id',
            { name: 'playout_time', type: 'date', dateFormat: 'His' }
          ],
          listeners: {
            load: function (store, records, opts) {
              //myMask.hide();
            }
          }
        });

        Ext.apply(
          this,
          {
            defaults: {
              border: false,
              margins: '10 30 10 10'
            },
            frame: false,
            border: false,
            //title: '비디오 매칭 항목',
            title: _text('MN02087') + _text('MN02107'),
            //title: '<span class="user_span"><span class="icon_title"><i class=""></i></span><span class="main_title_header">'+_text('MN02087') + _text('MN02107')+'</span></span>',
            //cls: 'grid_title_customize proxima_customize',
            //flex: 1,
            loadMask: true,
            enableDragDrop: true,
            ddGroup: 'newsGridDD',
            store: store_detail,
            viewConfig: {
              forceFit: true,
              emptyText: _text('MSG00148') //결과 값이 없습니다
            },
            tbar: [
              {
                xtype: 'button',
                //width : 60,
                //icon: '/led-icons/accept.png',
                //text : '매칭',
                //text : '<span style="position:relative;top:1px;"><i class="fa fa-link" style="font-size:13px;"></i></span>&nbsp;'+_text('MN02117'),
                cls: 'proxima_button_customize',
                width: 60,
                text:
                  '<span style="position:relative;top:1px;" title="' +
                  _text('MN02117') +
                  '"><i class="fa fa-link" style="font-size:13px;color:white;"> 매핑</i></span>',
                handler: function (self, e) {
                  var action, grid_id, grid_detail, request_id, change_field;
                  if (Ext.getCmp('tab_list').activeTab.id == 'tab_q') {
                    action = 'matching_q';
                    grid_id = 'grid_article_q';
                    grid_detail = 'grid_detail_q';
                    request_id = 'rd_id';
                    change_field = '';
                  } else {
                    action = 'matching';
                    grid_id = 'grid_article';
                    grid_detail = 'grid_detail';
                    request_id = 'artcl_id';
                  }
                  var grid_content = Ext.getCmp('tab_content_list')
                    .activeTab.get(0)
                    .get(1)
                    .getSelectionModel();
                  if (
                    Ext.isEmpty(
                      Ext.getCmp(grid_id)
                        .getSelectionModel()
                        .getSelected()
                    )
                  ) {
                    //Ext.Msg.alert('알림','먼저 대상을 선택 해 주시기 바랍니다.');
                    Ext.Msg.alert('test', 'tests');
                    return;
                  } else {
                    Ext.Msg.show({
                      title: _text('MN00024'), //확인
                      //msg : '<?=_text('MN02117')?> : <?=_text('MSG02039')?>',//매칭 : 이 작업을 진행하시겠습니까?
                      msg: _text('MN02117') + ' : ' + _text('MSG02039'), //
                      buttons: Ext.Msg.OKCANCEL,
                      fn: function (btn) {
                        if (btn == 'ok') {
                          Ext.each(grid_content.getSelections(), function (r) {
                            var duration;
                            var sys_video_rt = r.get('sys_video_rt');
                            if (!Ext.isEmpty(sys_video_rt)) {
                              duration = sys_video_rt.substr(0, 8);
                            } else {
                              duration = '';
                            }
                            var sel = Ext.getCmp(grid_id)
                              .getSelectionModel()
                              .getSelected();

                            Ext.Ajax.request({
                              url: '/store/request_zodiac/get_article.php',
                              params: {
                                action: action,
                                content_id: r.get('content_id'),
                                duration: duration,
                                title: r.get('title'),
                                type_content: r.get('bs_content_id'),
                                artcl_id: sel.get(request_id),
                                rd_seq: sel.get('rd_seq')
                              },
                              callback: function (opt, success, response) {
                                try {
                                  var r = Ext.decode(response.responseText); //console.log(r);
                                  if (r.success) {
                                    //Ext.Msg.alert('알림','매칭 되었습니다.');
                                    Ext.getCmp(grid_detail)
                                      .getStore()
                                      .reload();
                                    //Ext.getCmp(grid_id).getStore().reload();
                                    sel.set(
                                      'grphc_count',
                                      r.data_count.grphc_count
                                    );
                                    sel.set(
                                      'video_count',
                                      r.data_count.video_count
                                    );
                                    Ext.getCmp(grid_id).store.commitChanges();
                                  } else {
                                    //Ext.Msg.alert('알림',r.msg);
                                    Ext.Msg.alert(_text('MN00023'), r.msg);
                                  }
                                } catch (e) {
                                  //console.log(e);
                                }
                              }
                            });
                          });
                        }
                      }
                    });
                  }
                }
              },
              {
                xtype: 'spacer',
                width: 50
              },
              {
                xtype: 'button',
                //icon: '/led-icons/delete.png',
                //width : 80,
                //text : '매칭 해제',
                //text :  '<span style="position:relative;top:1px;"><i class="fa fa-unlink" style="font-size:13px;"></i></span>&nbsp;'+_text('MN02118'),
                cls: 'proxima_button_customize',
                width: 90,
                text:
                  '<span style="position:relative;top:1px;" title="' +
                  _text('MN02118') +
                  '"><i class="fa fa-unlink" style="font-size:13px;color:white;"> 매핑해제</i></span>',
                handler: function (btn, e) {
                  unMatching(btn.ownerCt.ownerCt.id);
                }
              },
              {
                xtype: 'spacer',
                width: 50
              },
              {
                xtype: 'button',
                cls: 'proxima_button_customize',
                width: 90,
                text:
                  '<span style="position:relative;top:1px;" title="' +
                  _text('MN02517') +
                  '"><i class="fa fa-list" style="font-size:13px;color:white;"> 순서저장</i></span>',
                handler: function (self, e) {
                  //putUpdateContentsOrder
                  var type_report = Ext.getCmp('tab_list').activeTab.tab_type;
                  var grid_id, grid_detail;

                  var detail_store = new Array();

                  if (type_report == '001') {
                    grid_id = 'grid_article';
                    grid_detail = 'grid_detail';
                  } else {
                    grid_id = 'grid_article_q';
                    grid_detail = 'grid_detail_q';
                  }
                  var sel = Ext.getCmp(grid_id)
                    .getSelectionModel()
                    .getSelected();
                  Ext.each(
                    Ext.getCmp(grid_detail).getStore().data.items,
                    function (r) {
                      var o_list_detail = new Object();
                      o_list_detail.object_id = r.get('media_id');
                      o_list_detail.playout_id = r.get('playout_id');
                      detail_store.push(o_list_detail);
                    }
                  );

                  if (detail_store.length < 1) {
                    Ext.Msg.alert('알림', '매핑된 목록이 없습니다.');
                  } else {
                    Ext.Ajax.request({
                      url: '/store/request_zodiac/get_article.php',
                      params: {
                        action: 'update_order',
                        type: Ext.getCmp('tab_list').activeTab.tab_type,
                        media_cd: Ext.getCmp('tab_content_list').activeTab
                          .content_type,
                        artcl_id: sel.get('artcl_id'),
                        rd_id: sel.get('rd_id'),
                        rd_seq: sel.get('rd_seq'),
                        store: Ext.encode(detail_store)
                      },
                      callback: function (opt, success, response) {
                        try {
                          var r = Ext.decode(response.responseText);
                          if (r.success) {
                            //Ext.Msg.alert('알림','매칭 되었습니다.');
                            Ext.getCmp(grid_detail)
                              .getStore()
                              .reload();
                            //Ext.getCmp(grid_id).getStore().reload();
                            sel.set('grphc_count', r.data_count.grphc_count);
                            sel.set('video_count', r.data_count.video_count);
                            Ext.getCmp(grid_id).store.commitChanges();
                          } else {
                            //Ext.Msg.alert('알림',r.msg);
                            Ext.Msg.alert(_text('MN00023'), r.msg);
                          }
                        } catch (e) {
                          //console.log(e);
                        }
                      }
                    });
                  }
                }
              },
              {
                xtype: 'button',
                hidden: true,
                text: 'test transmission',
                handler: function (btn, e) {
                  transmissionContent(btn.ownerCt.ownerCt.id);
                }
              }
            ],
            listeners: {
              viewready: function (self) {
                var downGridDroptgtNews = Ext.apply({}, newsDropZoneOverrides, {
                  table: self.id,
                  id_field: 'content_id',
                  ddGroup: 'newsGridDD',
                  grid: Ext.getCmp(self.id)
                });
                new Ext.dd.DropZone(
                  Ext.getCmp(self.id).getEl(),
                  downGridDroptgtNews
                );
              },
              afterrender: function (self) { },
              rowcontextmenu: function (self, row_index, e) {
                e.stopEvent();

                self.getSelectionModel().selectRow(row_index);

                var rowRecord = self.getSelectionModel().getSelected();
                var workflow_id = rowRecord.get('workflow_id');
                var content_id = rowRecord.get('content_id');

                var menu = new Ext.menu.Menu({
                  items: [
                    {
                      text: '우클릭메뉴',
                      hidden: true,
                      icon: '/led-icons/chart_organisation.png',
                      handler: function (btn, e) {
                        //함수
                        menu.hide();
                      }
                    }
                  ]
                });
                menu.showAt(e.getXY());
              },

              rowdblclick: function (self, row_index, e) {
                showContent(self);
              }
            },
            colModel: new Ext.grid.ColumnModel({
              defaults: {
                //menuDisabled: true
              },
              columns: [
                new Ext.grid.RowNumberer(),
                {
                  header: 'id',
                  dataIndex: 'artcl_id',
                  width: 20,
                  hidden: true
                },
                //'순서'
                //{header: _text('MN02119'), dataIndex: 'ord', width: 10, align : 'center'},
                //'제목'
                {
                  header: '<center>' + _text('MN00249') + '</center>',
                  dataIndex: 'media_nm'
                },
                //'종류'
                {
                  header: _text('MN00255'),
                  dataIndex: 'part',
                  width: 10,
                  align: 'center'
                },
                //'길이'
                {
                  header: _text('MN02120'),
                  dataIndex: 'playout_time',
                  renderer: Ext.util.Format.dateRenderer('H:i:s'),
                  width: 10,
                  align: 'center'
                },
                //'전송'
                {
                  header: _text('MN00243'),
                  dataIndex: 'playout_yn',
                  width: 10,
                  align: 'center'
                },
                //'송출아이디'
                {
                  header: _text('MN02121'),
                  dataIndex: 'playout_id',
                  align: 'center',
                  width: 20,
                  align: 'center'
                }
              ]
            }),
            sm: new Ext.grid.RowSelectionModel({
              singleSelect: true
            }),
            bbar: {
              xtype: 'paging',
              pageSize: 5,
              displayInfo: true,
              store: store_detail
            }
          },
          config || {}
        );
      } else {
        //프로그램 grid store
        var store_program = new Ext.data.JsonStore({
          url: '/store/request_zodiac/get_article.php',
          root: 'data',
          //autoLoad : true,
          fields: ['rd_id', 'brdc_dt', 'brdc_start_clk', 'brdc_pgm_nm'],
          listeners: {
            load: function (store, records, opts) { }
          }
        });

        Ext.apply(
          this,
          {
            defaults: {
              border: false,
              margins: '10 30 10 10'
            },
            frame: true,
            //title: 프로그램목록',
            //flex: 1,
            loadMask: true,
            store: store_program,
            viewConfig: {
              forceFit: true,
              emptyText: _text('MSG00148') //결과 값이 없습니다
            },
            frame: false,
            border: false,
            selModel: new Ext.grid.RowSelectionModel({
              singleSelect: true,
              listeners: {
                rowselect: function (self) {
                  Ext.getCmp('grid_article_q')
                    .getStore()
                    .load({
                      params: {
                        action: 'list_rundown',
                        search: self.getSelected().get('rd_id')
                      }
                    });

                  Ext.getCmp('grid_detail_q').store.removeAll();
                },
                rowdeselect: function (self) { }
              }
            }),
            listeners: {
              afterrender: function (self) {
                //var search_text = Ext.encode(Ext.getCmp('search_form_q').getForm().getValues());
                var search_text = new Object();
                search_text.broad_ymd = Ext.getCmp('broad_ymd')
                  .getValue()
                  .format('Y-m-d')
                  .trim();
                search_text.pgm_nm = Ext.getCmp('pgm_nm').getValue();
                self.store.load({
                  params: {
                    action: 'list_program',
                    search: Ext.encode(search_text)
                  }
                });
              },
              viewready: function (self) { },
              //rowclick: function (self, row_index, e) {
              //Ext.getCmp('grid_article_q').getStore().load({
              //params : {
              //action : 'list_rundown',
              //search : self.getSelectionModel().getSelected().get('rd_id')
              //}
              //});
              //},
              rowdblclick: function (self, row_index, e) { }
            },
            colModel: new Ext.grid.ColumnModel({
              defaults: {
                //menuDisabled: true
              },
              columns: [
                new Ext.grid.RowNumberer(),
                { header: 'id', dataIndex: 'rd_id', width: 120, hidden: true },
                //'프로그램명'
                {
                  header: '<center>' + _text('MN00303') + '</center>',
                  dataIndex: 'brdc_pgm_nm'
                },
                //'방송일자'
                {
                  header: _text('MN00180'),
                  dataIndex: 'brdc_dt',
                  width: 20,
                  algin: 'center',
                  hidden: true
                },
                //'방송시각'
                {
                  header: _text('MN02123'),
                  dataIndex: 'brdc_start_clk',
                  width: 20,
                  algin: 'center',
                  renderer: function (v) {
                    if (!Ext.isEmpty(v) && v.length > 4) {
                      return v.substr(0, 2) + ':' + v.substr(3, 2);
                    } else {
                      return v;
                    }
                  }
                },
                //'기사수'
                { header: _text('MN02124'), dataIndex: 'rd_id', hidden: true }
              ]
            }),

            bbar: {
              xtype: 'paging',
              pageSize: 20,
              displayInfo: true,
              store: store_program
            }
          },
          config || {}
        );
      }

      Ariel.Panel.InfoReport.superclass.initComponent.call(this);

      this.on('render', this._init);
    },
    _init: function () { }
  });

  Ext.reg('tab_article', Ariel.Panel.InfoReport);
})();
