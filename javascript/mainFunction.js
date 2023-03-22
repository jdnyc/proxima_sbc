// 이미지 로드 실패 시 처리(cjos-vms 커스터마이징)
function fallbackImg(self) {
  // console.log('image fall back!!!', self);
  self.setAttribute('src', '/css/images/no-image.jpg');
}

function _ame_archive(content_id) {
  Ext.Ajax.request({
    url: '/plugin/ame/get_ame_task_id.php',
    //params: params,
    callback: function (self, success, response) {
      if (success) {
        try {
          var r = Ext.decode(response.responseText);
          if (r.success == 'true' || r.success == true) {
            var task_id = r.task_id;

            if (task_id) {
              _ame_premiere_set_seq(content_id, task_id);
            } else {
              alert('작업 ID를 얻어오지 못하였습니다.');
            }
          }
        } catch (e) {
          alert('_ame_archive 작업 실패');
        }
      } else {
      }
    }
  });
}

function _ame_premiere_set_seq(content_id, task_id) {
  //alert('_ame_premiere_set_seq CALL !!!');
  //var url = "http://10.153.135.78:8002";
  var url = 'http://10.61.67.27:8002';
  var params = {};
  params.content_id = content_id;
  params.task_id = task_id;
  alert(params.content_id);
  alert(params.task_id);
  var content_tab = Ext.getCmp('tab_warp');
  var active_tab = content_tab.getActiveTab();
  var content_grid = active_tab.get(0);
  var sel = content_grid.getSelectionModel().getSelected();

  var ori_path = sel.get('premiere_media_path');
  var lowres_root = sel.get('lowres_root');

  ori_path = ori_path.replace(/\\/gi, '/');

  if (ori_path.indexOf(lowres_root) < 0) {
    ori_path = lowres_root + '/' + ori_path;
  }

  params.sequence = ori_path;

  url = url + '/?' + task_id + '&' + content_id + '&' + ori_path;

  $.ajax({
    url: url,
    success: function (data) {
      $('#time').append(data);
    }
  });

  return;

  Ext.Ajax.request({
    url: url,
    method: 'get',
    //params: params,
    callback: function (self, success, response) {
      alert(success);
      if (success) {
        try {
          var r = Ext.decode(response.responseText);

          if (r.success == 'true' || r.success == true) {
            alert('작업이 등록되었습니다.');
          }
        } catch (e) {
          alert(' _ame_premiere_set_seq 작업 실패');
        }
      } else {
      }
    }
  });
}

function resizeImgs(self, url, size) {
  var check, width, height;
  var imgObj = new Image();

  if (size && size.w) {
    width = size.w;
  } else {
    width = 150;
  }

  if (size && size.h) {
    height = size.h;
  } else {
    height = 84;
  }

  imgObj.src = url;

  if (imgObj.width == 0 || imgObj.height == 0) check = 0;

  if (imgObj.width / width < 1) {
    self.width = imgObj.width;
  } else {
    self.width = width;
  }

  if (imgObj.height / height < 1) {
    self.height = imgObj.height;
  } else {
    self.height = height;
  }
}

function resizeImg(self, size) {
  if (!Ext.isIE) {
  }
  //  self.display = none;
  if (size) {
    self.width = size.w;
    self.height = size.h;
  } else {
    //self.width = 150;
    //self.height = 83;
  }

  //  self.display = block;
}

function errorImg(self) {
  //self.src = '/img/incoming.jpg';
  if (!Ext.isIE) {
  }
}

function time() {
  return Math.floor(new Date().getTime() / 1000);
}

function show_url(url) {
  if (Ext.isEmpty(url)) {
    return;
  }
  window.open(url);
}

function show_sgl_log(content_id) {
  var win = new Ext.Window({
    title: _text('MN01099'), //SGL log
    width: 500,
    modal: true,
    //height: 150,
    height: 600,
    miniwin: true,
    resizable: false,
    layout: 'vbox',
    buttonAlign: 'center',
    buttons: [
      {
        text:
          '<span style="position:relative;top:1px;"><i class="fa fa-close" style="font-size:13px;"></i></span>&nbsp;' +
          _text('MN00031'),
        scale: 'medium',
        handler: function (b, e) {
          win.close();
        }
      }
    ],
    items: [
      {
        xtype: 'grid',
        autoScroll: true,
        cls: 'proxima_customize',
        stripeRows: true,
        //border: false,
        //height: 120,
        height: 100,
        store: new Ext.data.ArrayStore({
          fields: ['volume', 'volume_group', 'status', 'archived_date']
        }),
        viewConfig: {
          loadMask: true,
          forceFit: true
        },
        columns: [
          { header: _text('MN02213'), dataIndex: 'volume', sortable: 'false' }, //Volume Name
          {
            header: _text('MN02214'),
            dataIndex: 'volume_group',
            sortable: 'false'
          }, //Volume Group
          {
            header: _text('MN02215'),
            dataIndex: 'status',
            sortable: 'false',
            width: 70
          }, //Status
          {
            header: _text('MN02216'),
            dataIndex: 'archived_date',
            sortable: 'false',
            width: 120
          } //ArchiveDate
        ],
        sm: new Ext.grid.RowSelectionModel({})
      },
      {
        layout: 'fit',
        title: _text('MN00048'), //log
        flex: 1,
        html: '&nbsp',
        padding: 5,
        width: '100%',
        autoScroll: true,
        listeners: {
          render: function (self) {
            win.refresh_data(win);
          }
        }
      }
    ],
    refresh_data: function (self) {
      self.el.mask();
      Ext.Ajax.request({
        url: '/store/get_sgl_log_data.php',
        params: {
          content_id: content_id,
          mode: 'archive'
        },
        callback: function (opt, success, response) {
          self.el.unmask();
          var res = Ext.decode(response.responseText);
          if (res.success) {
            self.items.get(1).update(res.msg);
            var grid = self.items.get(0);
            Ext.each(res.volume, function (i) {
              grid.store.loadData([i], true);
            });
          }
        }
      });
    }
  });
  win.show();
}

function show_loudness_log2(content_id) {
  var loudness_list_store = new Ext.data.JsonStore({
    url: '/store/loudness/get_loudness_list.php',
    baseParams: {
      content_id: content_id
    },
    autoLoad: true,
    root: 'data',
    fields: [
      'loudness_id',
      'jobUid',
      'state',
      'task_id',
      'req_user_id',
      'req_user_nm',
      { name: 'req_datetime', type: 'date', dateFormat: 'YmdHis' },
      'req_type'
    ]
  });

  var loudness_detail_store = new Ext.data.JsonStore({
    url: '/store/loudness/get_loudness_detail_list.php',
    root: 'data',
    fields: [
      'loudness_log_id',
      'log',
      { name: 'creation_date', type: 'date', dateFormat: 'YmdHis' },
      'req_type'
    ]
  });

  function render_state(v) {
    switch (v) {
      case '1':
        v = _text('MN01039');
        break;
      case '2':
        v = _text('MN00011');
        break;
      case '3':
        v = _text('MN01049');
        break;
      case '13':
        v = _text('MN00262');
        break;
      default:
        v = _text('MN02177');
        break;
    }

    return v;
  }

  function render_type(v) {
    switch (v) {
      case 'M':
        v = _text('MN02243');
        break;
      case 'C':
        v = _text('MN02244');
        break;
    }

    return v;
  }

  var loudness_win = new Ext.Window({
    title: _text('MN02245'), //Loudness Log
    width: 500,
    modal: true,
    height: 600,
    miniwin: true,
    resizable: false,
    layout: 'vbox',
    buttons: [
      {
        text: _text('MN00031'), //닫기
        scale: 'medium',
        handler: function (b, e) {
          loudness_win.close();
        }
      }
    ],
    items: [
      {
        xtype: 'grid',
        autoScroll: true,
        flex: 1,
        store: loudness_list_store,
        viewConfig: {
          loadMask: true,
          forceFit: true
        },
        columns: [
          {
            header: _text('MN02247'),
            dataIndex: 'loudness_id',
            sortable: false,
            hidden: true
          }, //Loudness ID
          {
            header: _text('MN02248'),
            dataIndex: 'jobUid',
            sortable: false,
            hidden: true
          }, //JobUid
          {
            header: _text('MN02246'),
            dataIndex: 'req_datetime',
            sortable: false,
            width: 100,
            renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s'),
            align: 'center'
          }, //req_datetime
          {
            header: _text('MN00222'),
            dataIndex: 'req_type',
            sortable: false,
            renderer: render_type,
            align: 'center'
          }, //req_type
          {
            header: _text('MN00138'),
            dataIndex: 'state',
            sortable: false,
            width: 70,
            renderer: render_state,
            align: 'center'
          }, //Status
          {
            header: _text('MN00218'),
            dataIndex: 'req_user_nm',
            sortable: false,
            width: 70,
            align: 'center'
          } //req_user_nm
        ],
        sm: new Ext.grid.RowSelectionModel({
          singleSelect: true
        }),
        listeners: {
          rowclick: function (self, rowIndex, record) {
            var rowRecord = self.getSelectionModel().getSelected();
            loudness_detail_store.load({
              params: {
                loudness_id: rowRecord.get('loudness_id')
              }
            });
          }
        }
      },
      {
        xtype: 'grid',
        autoScroll: true,
        flex: 2,
        store: loudness_detail_store,
        viewConfig: {
          loadMask: true,
          forceFit: true
        },
        columns: [
          {
            header: _text('MN00108'),
            dataIndex: 'creation_date',
            sortable: false,
            width: 100,
            renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s'),
            align: 'center'
          }, //creation_date
          {
            header: _text('MN02249'),
            dataIndex: 'loudness_id_log',
            sortable: false,
            hidden: true
          }, //Loudness Log ID
          { header: _text('MN00048'), dataIndex: 'log', sortable: false } //log
        ],
        sm: new Ext.grid.RowSelectionModel({
          singleSelect: true
        }),
        listeners: {
          rowdblclick: function (self, rowIndex, e) {
            var rowRecord = self.getSelectionModel().getSelected();

            var log_win = new Ext.Window({
              title: _text('MN02245'), //Loudness Log
              width: 500,
              modal: true,
              height: 600,
              miniwin: true,
              resizable: false,
              layout: 'fit',
              buttons: [
                {
                  text: _text('MN00031'), //닫기
                  scale: 'medium',
                  handler: function (b, e) {
                    log_win.close();
                  }
                }
              ],
              items: [
                {
                  xtype: 'textarea',
                  value: rowRecord.get('log')
                }
              ]
            });

            log_win.show();
          }
        }
      }
    ]
  });
  loudness_win.show();
}

function show_loudness_log(content_id) {
  var loudness_win = new Ext.Window({
    title: _text('MN02245'), //Loudness Log
    width: 700,
    modal: true,
    height: 600,
    miniwin: true,
    //resizable: false,
    buttonAlign: 'center',
    layout: 'fit',
    buttons: [
      {
        //text: _text('MN00031'),//닫기
        text:
          '<span style="position:relative;top:1px;"><i class="fa fa-close" style="font-size:13px;"></i></span>&nbsp;' +
          _text('MN00031'),
        scale: 'medium',
        handler: function (b, e) {
          loudness_win.close();
        }
      }
    ],
    items: [
      new Ariel.LoudnessLog({
        //1 : search
        content_id: content_id
      })
    ]
  });

  loudness_win.show();
}

function show_qc_log(content_id) {
  var qc_win = new Ext.Window({
    title: _text('MN02344'), //Quality Check 로그
    width: 700,
    modal: true,
    height: 600,
    miniwin: true,
    //resizable: false,
    layout: 'fit',
    buttonAlign: 'center',
    buttons: [
      {
        text:
          '<span style="position:relative;top:1px;"><i class="fa fa-close" style="font-size:13px;"></i></span>&nbsp;' +
          _text('MN00031'),
        scale: 'medium',
        handler: function (b, e) {
          qc_win.close();
        }
      }
    ],
    items: [
      new Ariel.QualityCheckLog({
        //1 : search
        content_id: content_id
      })
    ]
  });

  qc_win.show();
}

function change_tag_content(
  action,
  av_content_id_array,
  tag_category_id,
  reload_data
) {
  Ext.Ajax.request({
    url: '/store/tag/tag_action.php',
    params: {
      action: action,
      content_id: Ext.encode(av_content_id_array),
      tag_category_id: tag_category_id
    },
    callback: function (opts, success, response) {
      try {
        var r = Ext.decode(response.responseText, true);
        if (r.success) {
          if (reload_data !== 'no_reload_data') {
            Ext.getCmp('tab_warp').getActiveTab().get(0).getStore().reload();
          } else {
            Ext.getCmp('tag_list_in_content').reset_list_of_tag_form();
          }
        } else {
          Ext.Msg.alert(_text('MN00022'), r.msg);
        }
      } catch (e) {
        Ext.Msg.alert(
          _text('MN01098'),
          e.message + '(responseText: ' + response.responseText + ')'
        );
      }
    }
  });
}

function moveSelectedRow(grid, direction, mode, start_index) {
  var v_start_index = 0;
  if (typeof start_index === 'undefined') {
    v_start_index = 0;
  } else {
    v_start_index = start_index;
  }
  var record = grid.getSelectionModel().getSelected();
  if (!record) {
    Ext.Msg.alert(_text('MN00022'), 'Select tag to move');
  }
  var index;

  if (mode == 'up' || mode == 'down') {
    index = grid.getStore().indexOf(record);
    if (direction < 0) {
      index--;
      if (index < v_start_index) {
        return;
      }
    } else {
      index++;
      if (index >= grid.getStore().getCount()) {
        return;
      }
    }
  } else if (mode == 'top') {
    index = v_start_index;
  } else if (mode == 'bottom') {
    index = grid.getStore().getCount() - 1;
  }
  grid.getStore().remove(record);
  grid.getStore().insert(index, record);
  grid.getSelectionModel().selectRow(index, true);
}

function tag_management_windown(event, mode_load) {
  var tagPanel = Ext.getCmp('west_menu_item_media_tags');

  // 설정버튼을 누르면 이때까지는 collapsed는 false이다.
  // 그래서 일단 저장한 후 나중에 확인해서
  var tagPanelBeforeCollapsed = tagPanel.collapsed;

  var tagMgtWin = new Ext.Window({
    title: _text('MN02560'),
    id: 'list_of_tag',
    cls: 'newWindowStyle',
    width: 500,
    modal: true,
    height: 400,
    miniwin: true,
    resizable: false,
    changed: false,
    layout: 'vbox',
    updateTag: function (taglistGrid) {
      var tagMgtWin = this;
      var sm = taglistGrid.getSelectionModel().getSelected();
      var edit_tag = new Ext.Window({
        width: 300,
        height: 150,
        modal: true,
        miniwin: true,
        resizable: false,
        title: _text('MN02566'),
        cls: 'change_background_panel',
        layout: 'fit',
        buttonAlign: 'center',
        buttons: [
          {
            text:
              '<span style="position:relative;top:1px;"><i class="fa fa-edit" style="font-size:13px;"></i></span>&nbsp;' +
              _text('MN00043'), //edit
            scale: 'medium',
            handler: function (b, e) {
              var tag_title_input_edit = Ext.getCmp(
                'tag_title_input_edit2'
              ).getValue();
              var tag_color_input_edit = Ext.getCmp(
                'tag_color_input_edit2'
              ).getValue();

              Ext.Ajax.request({
                url: '/store/tag/tag_action.php',
                params: {
                  action: 'update_tag',
                  tag_category_id: sm.get('tag_category_id'),
                  tag_category_title: tag_title_input_edit,
                  tag_category_color: tag_color_input_edit
                },
                callback: function (opt, success, response) {
                  edit_tag.close();

                  taglistGrid.getStore().reload();
                  Ext.getCmp('tag_search').getStore().reload();

                  tagMgtWin.changed = true;

                  Ext.getCmp('tag_menu_list_data').menuReset();
                  // Ext.getCmp('tag_filters').tag_filter_reset();
                  if (mode_load !== 'detail_content') {
                    Ext.getCmp('tab_warp').getActiveTab().get(0).reload();
                  } else {
                    Ext.getCmp('tag_list_in_content').reset_list_of_tag_form();
                  }
                }
              });
            }
          },
          {
            //text: _text('MN00031'),
            text:
              '<span style="position:relative;top:1px;"><i class="fa fa-close" style="font-size:13px;"></i></span>&nbsp;' +
              _text('MN00004'),
            scale: 'medium',
            handler: function (b, e) {
              edit_tag.close();
            }
          }
        ],
        items: [
          {
            xtype: 'form',
            frame: true,
            items: [
              {
                xtype: 'textfield',
                allowBlank: false,
                width: 150,
                fieldLabel: _text('MN02561'),
                id: 'tag_title_input_edit2',
                value: sm.get('tag_category_title')
              },
              {
                xtype: 'colorfield',
                width: 150,
                fieldLabel: _text('MN02562'),
                id: 'tag_color_input_edit2',
                value: sm.get('tag_category_color'),
                //msgTarget: 'qtip',
                fallback: true
              }
            ]
          }
        ]
      });
      edit_tag.show();
    },
    items: [
      {
        xtype: 'toolbar',
        margins: '0 0 0 335px',
        height: 35,
        items: [
          {
            xtype: 'button',
            cls: 'proxima_btn_customize proxima_btn_customize_new',
            text:
              '<span style="position:relative;top:1px;" title="' +
              _text('MN00139') +
              '"><i class="fa fa-refresh" style="font-size:13px;"></i></span>',
            width: 30,
            handler: function (b, e) {
              Ext.getCmp('save_order_button').disable();
              Ext.getCmp('listing_tag').getStore().reload();
              Ext.getCmp('tag_search').getStore().reload();
            }
          },
          {
            xtype: 'button',
            cls: 'proxima_btn_customize proxima_btn_customize_new',
            text:
              '<i class="fa fa-angle-double-up" title="' +
              _text('MN02229') +
              '" style="font-size:13px;"></i>',
            width: 30,
            handler: function (b, e) {
              var grid = Ext.getCmp('listing_tag');
              moveSelectedRow(grid, 0, 'top');
              Ext.getCmp('save_order_button').enable();
            }
          },
          {
            xtype: 'button',
            cls: 'proxima_btn_customize proxima_btn_customize_new',
            text:
              '<i class="fa fa-angle-up" title="' +
              _text('MN02230') +
              '" style="font-size:13px;"></i>',
            width: 30,
            handler: function (b, e) {
              var grid = Ext.getCmp('listing_tag');
              moveSelectedRow(grid, -1, 'up');
              Ext.getCmp('save_order_button').enable();
            }
          },
          {
            xtype: 'button',
            cls: 'proxima_btn_customize proxima_btn_customize_new',
            text:
              '<i class="fa fa-angle-down" title="' +
              _text('MN02231') +
              '" style="font-size:13px;"></i>',
            width: 30,
            handler: function (b, e) {
              var grid = Ext.getCmp('listing_tag');
              moveSelectedRow(grid, +1, 'down');
              Ext.getCmp('save_order_button').enable();
            }
          },
          {
            xtype: 'button',
            cls: 'proxima_btn_customize proxima_btn_customize_new',
            text:
              '<i class="fa fa-angle-double-down" title="' +
              _text('MN02232') +
              '" style="font-size:13px;"></i>',
            width: 30,
            handler: function (b, e) {
              var grid = Ext.getCmp('listing_tag');
              moveSelectedRow(grid, 0, 'bottom');
              Ext.getCmp('save_order_button').enable();
            }
          }
        ]
      },
      {
        xtype: 'grid',
        cls: 'proxima_customize proxima_new_grid_style',
        stripeRows: true,
        autoScroll: true,
        height: 300,
        id: 'listing_tag',
        store: new Ext.data.JsonStore({
          url: '/store/tag/tag_action.php',
          root: 'data',
          baseParams: {
            action: 'listing'
          },
          fields: [
            'tag_category_id',
            'tag_category_title',
            'tag_category_color',
            'show_order'
          ]
        }),
        viewConfig: {
          loadMask: true,
          forceFit: true
        },
        columns: [
          new Ext.grid.RowNumberer(),
          {
            header: 'No',
            dataIndex: 'show_order',
            sortable: false,
            width: 10,
            hidden: true
          },
          {
            header: '',
            dataIndex: 'tag_category_id',
            sortable: 'false',
            hidden: true
          },
          {
            header: _text('MN02561'),
            dataIndex: 'tag_category_title',
            sortable: false,
            renderer: function (
              value,
              metaData,
              record,
              rowIndex,
              colIndex,
              store
            ) {
              //return '<span style=\"font-weight:bold ;color:'+record.data.tag_category_color+';\">'+value+'</span>';
              return value;
            }
          },
          {
            header: _text('MN02446'),
            dataIndex: 'tag_category_color',
            sortable: false,
            width: 20,
            renderer: function (
              value,
              metaData,
              record,
              rowIndex,
              colIndex,
              store
            ) {
              return (
                '<i class="fa fa-circle" style="margin-left:5px;color:' +
                value +
                ';"></i>'
              );
            }
          }
          //,{ header: 'show_order', dataIndex: 'show_order', sortable:true}
        ],
        sm: new Ext.grid.RowSelectionModel({}),
        listeners: {
          afterrender: function (self) {
            self.getStore().load();
          },
          rowdblclick: function (self, rowIndex, e) {
            tagMgtWin.updateTag(self);
          }
        }
      }
    ],
    buttonAlign: 'center',
    fbar: [
      {
        text:
          '<span style="position:relative;top:1px;"><i class="fa fa-plus" style="font-size:13px;"></i></span>&nbsp;' +
          _text('MN00033'), // add
        scale: 'medium',
        handler: function (b, e) {
          var create_new_tag = new Ext.Window({
            title: _text('MN02563'),
            cls: 'change_background_panel',
            width: 300,
            modal: true,
            height: 150,
            miniwin: true,
            resizable: false,
            layout: 'fit',
            buttonAlign: 'center',
            buttons: [
              {
                text:
                  '<span style="position:relative;top:1px;"><i class="fa fa-plus" style="font-size:13px;"></i></span>&nbsp;' +
                  _text('MN00033'),
                scale: 'medium',
                handler: function (b, e) {
                  var tag_title_input = Ext.getCmp(
                    'tag_title_input'
                  ).getValue();
                  var tag_color_input = Ext.getCmp(
                    'tag_color_input'
                  ).getValue();

                  Ext.Ajax.request({
                    url: '/store/tag/tag_action.php',
                    params: {
                      action: 'add_tag',
                      tag_category_title: tag_title_input,
                      tag_category_color: tag_color_input
                    },
                    callback: function (opt, success, response) {
                      create_new_tag.close();

                      Ext.getCmp('listing_tag').getStore().reload();
                      Ext.getCmp('tag_search').getStore().reload();

                      tagMgtWin.changed = true;

                      Ext.getCmp('tag_menu_list_data').menuReset();
                      // Ext.getCmp('tag_filters').tag_filter_reset();
                      if (mode_load !== 'detail_content') {
                      } else {
                        Ext.getCmp(
                          'tag_list_in_content'
                        ).reset_list_of_tag_form();
                      }
                    }
                  });
                }
              },
              {
                text:
                  '<span style="position:relative;top:1px;"><i class="fa fa-close" style="font-size:13px;"></i></span>&nbsp;' +
                  _text('MN00004'),
                scale: 'medium',
                handler: function (b, e) {
                  create_new_tag.close();
                }
              }
            ],
            items: [
              {
                xtype: 'form',
                frame: true,
                items: [
                  {
                    xtype: 'textfield',
                    allowBlank: false,
                    width: 150,
                    fieldLabel: _text('MN02561'),
                    id: 'tag_title_input'
                  },
                  {
                    xtype: 'colorfield',
                    width: 150,
                    fieldLabel: _text('MN02562'),
                    id: 'tag_color_input',
                    value: '#FF0000',
                    //msgTarget: 'qtip',
                    fallback: true
                  }
                ]
              }
            ]
          }); // end create new tag window
          create_new_tag.show();
        }
      },
      {
        action: 'update_tag',
        text:
          '<span style="position:relative;top:1px;"><i class="fa fa-edit" style="font-size:13px;"></i></span>&nbsp;' +
          _text('MN00043'), //edit
        scale: 'medium',
        handler: function (b, e) {
          var taglistGrid = Ext.getCmp('listing_tag');
          var hasSelection = taglistGrid.getSelectionModel().hasSelection();

          if (hasSelection) {
            tagMgtWin.updateTag(taglistGrid);
          } else {
            Ext.Msg.alert(_text('MN00022'), _text('MSG02536'));
          }
        }
      },
      {
        text:
          '<span style="position:relative;top:1px;"><i class="fa fa-trash" style="font-size:13px;"></i></span>&nbsp;' +
          _text('MN00034'), //delete
        scale: 'medium',
        handler: function (b, e) {
          var hasSelection = Ext.getCmp('listing_tag')
            .getSelectionModel()
            .hasSelection();
          if (hasSelection) {
            Ext.MessageBox.confirm(
              _text('MN00034'),
              _text('MSG02535'),
              function (btn) {
                if (btn === 'yes') {
                  var action = 'delete_tag';
                  var sm = Ext.getCmp('listing_tag')
                    .getSelectionModel()
                    .getSelected();
                  var tag_category_id = sm.data.tag_category_id;

                  Ext.Ajax.request({
                    url: '/store/tag/tag_action.php',
                    params: {
                      action: action,
                      tag_category_id: tag_category_id
                    },
                    callback: function (opt, success, response) {
                      Ext.getCmp('listing_tag').getStore().reload();
                      Ext.getCmp('tag_search').getStore().reload();

                      tagMgtWin.changed = true;

                      Ext.getCmp('tag_menu_list_data').menuReset();
                      // Ext.getCmp('tag_filters').tag_filter_reset();
                      if (mode_load !== 'detail_content') {
                        Ext.getCmp('tab_warp').getActiveTab().get(0).reload();
                      } else {
                        Ext.getCmp(
                          'tag_list_in_content'
                        ).reset_list_of_tag_form();
                      }
                    }
                  });
                }
              }
            );
          } else {
            Ext.Msg.alert(_text('MN00022'), _text('MSG02537'));
          }
        }
      },
      {
        text:
          '<span style="position:relative;top:1px;"><i class="fa fa-edit" style="font-size:13px;"></i></span>&nbsp;' +
          _text('MN02228'),
        id: 'save_order_button',
        disabled: true,
        scale: 'medium',
        handler: function (b, e) {
          var grid = Ext.getCmp('listing_tag').getStore();
          var tag_id_order = [];
          grid.each(function (record) {
            tag_id_order.push(record.data.tag_category_id);
          });
          Ext.Ajax.request({
            url: '/store/tag/tag_action.php',
            params: {
              action: 'update_order_tag',
              tag_id_order: Ext.encode(tag_id_order)
            },
            callback: function (opt, success, response) {
              Ext.getCmp('listing_tag').getStore().reload();
              Ext.getCmp('tag_search').getStore().reload();

              tagMgtWin.changed = true;

              Ext.getCmp('tag_menu_list_data').menuReset();
              // Ext.getCmp('tag_filters').tag_filter_reset();
              Ext.getCmp('save_order_button').disable();
              if (mode_load !== 'detail_content') {
              } else {
                Ext.getCmp('tag_list_in_content').reset_list_of_tag_form();
              }
            }
          });
        }
      },
      {
        text:
          '<span style="position:relative;top:1px;"><i class="fa fa-close" style="font-size:13px;"></i></span>&nbsp;' +
          _text('MN00031'),
        scale: 'medium',
        handler: function (b, e) {
          if (tagMgtWin.changed) {
            Ext.getCmp('tag_search').getStore().reload();
          }

          if (!tagPanelBeforeCollapsed) {
            tagPanel.expand(false);
          }

          tagMgtWin.close();
        }
      }
    ]
  });
  tagMgtWin.show();
}

function tag_filter_windown() {
  var win = new Ext.Window({
    title: _text('MN02559'),
    id: 'list_of_tag_filter',
    width: 300,
    modal: true,
    height: 400,
    miniwin: true,
    resizable: false,
    layout: 'fit',
    items: [
      {
        xtype: 'grid',
        cls: 'proxima_customize',
        stripeRows: true,
        autoScroll: true,
        height: 300,
        id: 'listing_tag_filter',
        store: new Ext.data.JsonStore({
          url: '/store/tag/tag_action.php',
          root: 'data',
          baseParams: {
            action: 'listing'
          },
          fields: [
            'tag_category_id',
            'tag_category_title',
            'tag_category_color',
            'show_order'
          ]
        }),
        viewConfig: {
          loadMask: true,
          forceFit: true
        },
        columns: [
          //new Ext.grid.RowNumberer(),
          //{ header: 'No', dataIndex: 'show_order', sortable:false, width: 10,},
          {
            //header: _text('MN02561'),
            dataIndex: 'tag_category_title',
            sortable: false,
            renderer: function (
              value,
              metaData,
              record,
              rowIndex,
              colIndex,
              store
            ) {
              return (
                '<i class="fa fa-circle" style="margin-left:5px;color:' +
                record.data.tag_category_color +
                ';"></i>  ' +
                value
              );
              //return value;
            }
          },
          { dataIndex: 'tag_category_id', sortable: 'false', hidden: true }
          /*
                              ,{
                                  header: '',
                                  dataIndex: 'tag_category_color',
                                  sortable:false,
                                  width: 10,
                                  renderer: function(value, metaData, record, rowIndex, colIndex, store) {
                                      return '<i class="fa fa-circle" style=\"margin-left:5px;color:'+value+';\"></i>';
                                  }
                              }*/
          //,{ header: 'show_order', dataIndex: 'show_order', sortable:true}
        ],
        sm: new Ext.grid.RowSelectionModel({}),
        listeners: {
          afterrender: function (self) {
            self.getStore().load();
          },
          rowclick: function (self, rowIndex, e) {
            var sm = self.getSelectionModel().getSelected();
            var content_tab = Ext.getCmp('tab_warp');
            var active_tab = content_tab.getActiveTab();
            var params = content_tab.mediaBeforeParam;
            params.tag_category_id = sm.data.tag_category_id;
            active_tab.get(0).reload(params);

            win.close();
          }
        }
      }
    ],
    buttonAlign: 'center',
    fbar: [
      /*
                  {
                      text: _text('MN02239'),
                      scale: 'medium',
                      handler: function(b, e){
                          tag_management_windown();
                      }
                  },*/
      {
        text:
          '<span style="position:relative;top:1px;"><i class="fa fa-close" style="font-size:13px;"></i></span>&nbsp;' +
          _text('MN00031'),
        scale: 'medium',
        handler: function (b, e) {
          win.close();
        }
      }
    ]
  });
  win.show();
}

function tag_list_windown(av_content_id) {
  var win = new Ext.Window({
    title: _text('MN02564'),
    id: 'list_of_tag_filter',
    width: 300,
    modal: true,
    height: 400,
    miniwin: true,
    resizable: false,
    layout: 'fit',
    items: [
      {
        xtype: 'grid',
        cls: 'proxima_customize',
        stripeRows: true,
        autoScroll: true,
        height: 300,
        id: 'listing_tag',
        store: new Ext.data.JsonStore({
          url: '/store/tag/tag_action.php',
          root: 'data',
          baseParams: {
            action: 'listing'
          },
          fields: [
            'tag_category_id',
            'tag_category_title',
            'tag_category_color',
            'show_order'
          ]
        }),
        viewConfig: {
          loadMask: true,
          forceFit: true
        },
        columns: [
          {
            dataIndex: 'tag_category_id',
            sortable: 'false',
            hidden: true
          },
          {
            dataIndex: 'tag_category_title',
            sortable: false,
            renderer: function (
              value,
              metaData,
              record,
              rowIndex,
              colIndex,
              store
            ) {
              return (
                '<i class="fa fa-circle" style="margin-left:5px;color:' +
                record.data.tag_category_color +
                ';"></i>  ' +
                value
              );
            }
          }
        ],
        sm: new Ext.grid.RowSelectionModel({}),
        listeners: {
          afterrender: function (self) {
            self.getStore().load();
          },
          rowclick: function (self, rowIndex, e) {
            var sm = self.getSelectionModel().getSelected();
            var tag_category_id = sm.data.tag_category_id;
            var content_id_array_2 = [];
            content_id_array_2.push({
              content_id: av_content_id
            });
            change_tag_content(
              'change_tag_content',
              content_id_array_2,
              tag_category_id,
              'no_reload_data'
            );
            win.close();
          }
        }
      }
    ],
    buttonAlign: 'center',
    fbar: [
      {
        text: _text('MN00031'),
        scale: 'medium',
        handler: function (b, e) {
          win.close();
        }
      }
    ]
  });
  win.show();
}

function show_harris_storage() { }

function backHome() {
  location.href = 'http://' + location.host + '/index.php';
}

function sessionChecker() {
  if (Ext.Ajax.isLoading()) return true;
  if (is_long_time_ajax_working == 'Y') return;
  session_checker = Ext.Ajax.request({
    url: '/store/session_check.php',
    params: {
      session_expire: session_expire_time,
      session_user_id: session_user_id,
      session_super_admin: session_super_admin,
      session_prevent_duplicate_login: session_prevent_duplicate_login
    },
    callback: function (opts, success, response) {
      var result = Ext.decode(response.responseText);
      if (result.has_session === false) {
        clearInterval(session_checker_id);
        if (result.check_duplication == true) {
          Ext.Msg.alert(
            _text('MN00024'),
            _text('MSG02511') + '<br />' + _text('MSG02512'),
            backHome
          );
        } else {
          Ext.Msg.alert(
            _text('MN00024'),
            _text('MSG02070') + '<br />' + _text('MSG02071'),
            backHome
          );
        }
      } else if (result.has_session === true) {
        session_expire_time = result.session_expire;
      }
    },
    failure: function (response, opts) {
      clearInterval(session_checker_id);
      Ext.Msg.alert(_text('MN00024'), _text('MSG02529'), backHome);
    }
  });
}

//var App = new Ext.App({});

function fn_checkLogout(callbackFunction, av_obj) {
  Ext.Ajax.request({
    url: '/lib/session_check.php',
    async: false,
    callback: function (opts, success, response) {
      if (success) {
        try {
          if (response.responseText != 'true') {
            fn_msgLogout(av_obj);
          } else {
            if (!Ext.isEmpty(callbackFunction)) {
              callbackFunction();
            }
          }
        } catch (e) {
          fn_msgLogout(av_obj);
        }
      } else {
        fn_msgLogout(av_obj);
      }
    }
  });
}

function fn_Logout(av_obj) {
  Ext.Ajax.request({
    url: '/store/logout.php',
    callback: function (opts, success, response) {
      if (success) {
        try {
          var r = Ext.decode(response.responseText);
          if (r.success) {
            //넘어온 객체가 있고 객체의 부모가 있는 경우 부모를
            if (!Ext.isEmpty(av_obj) && !Ext.isEmpty(av_obj.opener)) {
              if (Ext.isAdobeAgent) {
                av_obj.opener.location = '/?agent=' + Ext.isAdobeAgent;
              } else {
                av_obj.opener.location = '/';
              }

              if (!Ext.isEmpty(av_obj.self)) {
                window.close();
              }
            } else {
              if (Ext.isAdobeAgent) {
                window.location = '/?agent=' + Ext.isAdobeAgent;
              } else {
                window.location = '/';
              }
            }
          } else {
            //>>Ext.Msg.alert('오류', r.msg);
            Ext.Msg.alert(_text('MN00022'), r.msg);
          }
        } catch (e) {
          //>>Ext.Msg.alert('오류', e+'<br />'+response.responseText);
          Ext.Msg.alert(_text('MN00022'), e + '<br />' + response.responseText);
        }
      } else {
        //>>Ext.Msg.alert('오류', response.statusText);
        Ext.Msg.alert(_text('MN00022'), response.statusText);
      }
    }
  });
}

function fn_msgLogout(av_obj) {
  Ext.Msg.show({
    title: _text('MN00024'),
    msg: '재 로그인이 필요합니다',
    buttons: Ext.Msg.OK,
    fn: function (btnID) {
      Ext.Ajax.request({
        url: '/store/logout.php',
        callback: function (opts, success, response) {
          if (success) {
            try {
              var r = Ext.decode(response.responseText);
              if (r.success) {
                //넘어온 객체가 있고 객체의 부모가 있는 경우 부모를
                if (!Ext.isEmpty(av_obj) && !Ext.isEmpty(av_obj.opener)) {
                  if (Ext.Ext.isAdobeAgent) {
                    av_obj.opener.location = '/?agent=' + Ext.isAdobeAgent;
                  } else {
                    av_obj.opener.location = '/';
                  }

                  if (!Ext.isEmpty(av_obj.self)) {
                    window.close();
                  }
                } else {
                  if (Ext.Ext.isAdobeAgent) {
                    window.location = '/?agent=' + Ext.isAdobeAgent;
                  } else {
                    window.location = '/';
                  }
                }
              } else {
                //>>Ext.Msg.alert('오류', r.msg);
                Ext.Msg.alert(_text('MN00022'), r.msg);
              }
            } catch (e) {
              //>>Ext.Msg.alert('오류', e+'<br />'+response.responseText);
              Ext.Msg.alert(
                _text('MN00022'),
                e + '<br />' + response.responseText
              );
            }
          } else {
            //>>Ext.Msg.alert('오류', response.statusText);
            Ext.Msg.alert(_text('MN00022'), response.statusText);
          }
        }
      });
    }
  });
}

function contentTooolbarTemplete(that) {
  var id = document.getElementById(that);
}

function goCueSheet() {
  var current_tab = Ext.getCmp('nps_center').getLayout().activeItem.id;

  if (current_tab == 'nps_media_main') {
    if (!Ext.isEmpty(cuesheetSearchWin)) {
      cuesheetSearchWin.hide();
    }
    var cuesheet = Ext.getCmp('media_cuesheet');
    if (cuesheet.collapsed) {
      Ext.getCmp('media_cuesheet').collapsible = true;
      Ext.getCmp('media_cuesheet').expand();
    } else {
      Ext.getCmp('media_cuesheet').collapse();
      Ext.getCmp('media_cuesheet').collapsible = false;
    }
  } else if (current_tab == 'nps_audio_main') {
    if (!Ext.isEmpty(cuesheetSearchWin)) {
      cuesheetSearchWin.hide();
    }
    var cuesheet = Ext.getCmp('audio_cuesheet');
    if (cuesheet.collapsed) {
      Ext.getCmp('audio_cuesheet').collapsible = true;
      Ext.getCmp('audio_cuesheet').expand();
    } else {
      Ext.getCmp('audio_cuesheet').collapse();
      Ext.getCmp('audio_cuesheet').collapsible = false;
    }
  } else {
    return;
  }
}

//메뉴 변경 함수 통합 2016-03-09 이성용
function afterMenuChange(itemPosition) {
  if (!itemPosition) {
    itemPosition = 0;
  }
  Ext.getCmp('nps_center').getLayout().setActiveItem(itemPosition);
  Ext.getCmp('nps_center').doLayout();
}

// 메뉴 이동시 기존에 상세검색 window 가 떠있으면 hide 처리함
function closeAdvancedSearchWin() {
  if (Ariel.advancedSearch) {
    Ariel.advancedSearch.hide();
  } else if (Ariel.advancedAudioSearch) {
    Ariel.advancedAudioSearch.hide();
  } else if (Ariel.advancedCGSearch) {
    Ariel.advancedCGSearch.hide();
  }
}

// make zip file from urls using jszip
function deferredAddZip(url, filename, zip) {
  var deferred = $.Deferred();
  JSZipUtils.getBinaryContent(url, function (err, data) {
    if (err) {
      deferred.reject(err);
    } else {
      zip.file(filename, data, { binary: true });
      deferred.resolve(data);
    }
  });
  return deferred;
}

function doSimpleSearch(type) {
  // 통합검색일시 상세검색 초기화
  var is_research = null,
    value = '',
    content_tab = '',
    now_tree = '',
    active_tab,
    nodePath = '/0',
    search_field = '',
    mode,
    node,
    instt = null,
    telecineTySe = null,
    filters = '';

  search_field = Ext.getCmp('search_input');
  value = Ext.getCmp('search_input').getValue();
  content_tab = Ext.getCmp('tab_warp');
  //now_tree = Ext.getCmp('menu-tree');
  now_tree = Ext.getCmp('tree-tab').getActiveTab();
  yearly_tree = Ext.getCmp('yearly-tree');
  is_research = Ext.getCmp('research_media').getValue();

  var customSearchGrid = Ext.getCmp('nps_media__custom_search_grid');
  customSearchGrid.getSelectionModel().clearSelections();
  customSearchGrid.rowSelectedIndex = -1;
  var tagSearchGrid = Ext.getCmp('tag_search');
  tagSearchGrid.getSelectionModel().clearSelections();
  tagSearchGrid.rowSelectedIndex = -1;

  var searchToolbarBox = Ext.getCmp('tbarContainer');

  // 바텀 툴바 검색창
  var searchBottomToolbar = Ext.getCmp('nps_media_search_bottom_toolbar');
  // var dateCombo = searchBottomToolbar.find('name', 'dateCombo')[0].getValue();

  // var statusCombo = searchBottomToolbar
  //     .find('name', 'statusCombo')[0]
  //     .getValue();
  // var reviewStatusCombo = searchBottomToolbar
  //     .find('name', 'reviewStatusCombo')[0]
  //     .getValue();
  // var archiveStatusCombo = searchBottomToolbar
  //     .find('name', 'archiveStatusCombo')[0]
  //     .getValue();
  // var broadcastForm = searchBottomToolbar
  //     .find('name', 'broadcastForm')[0]
  //     .getValue();
  // var materialKind = searchBottomToolbar
  //     .find('name', 'materialKind')[0]
  //     .getValue();

  var filters = {
    // content_status: statusCombo,
    // content_review_status: reviewStatusCombo,
    // content_archive_status: archiveStatusCombo,
    // created_date: dateCombo,
    // brdcst_stle_se: broadcastForm,
    // matr_knd: materialKind
  };

  if (is_research) {
    search_field.search_array.push(value);
  } else {
    search_field.search_array = [];
  }

  active_tab = content_tab.getActiveTab();
  node = now_tree.getSelectionModel().getSelectedNode();

  if (!Ext.isEmpty(node)) {
    if (node.attributes.type != 'yearly') {
      nodePath = node.getPath();

      if (node.attributes.copy) {
        var originalCategoryId = node.attributes.original_category_id;
        nodePath = now_tree.getNodeById(originalCategoryId).getPath();
      }

      var isFirst = now_tree.customFirstNode.isLoaded;
      if (isFirst == false) {
        nodePath = now_tree.customFirstNode.path;
        now_tree.customFirstNodeSetLoad();
      }


    } else {
      filters.category_start_date = node.attributes.startDate;
      filters.category_end_date = node.attributes.endDate;
    }
  } else {
    node = now_tree.root.firstChild;

  }

  if (!Ext.isEmpty(node) && !Ext.isEmpty(node.parentNode)) {
    if (node.parentNode.id == '203') {
      instt = node.attributes.code;
    }
    // console.log(node);
    if (!Ext.isEmpty(node.parentNode.parentNode)) {
      if (node.parentNode.parentNode.id == '203') {
        instt = node.attributes.code;
      }
    }
  }

  if (!Ext.isEmpty(node) && !Ext.isEmpty(node.parentNode)) {
    if (node.parentNode.id == '205') {
      telecineTySe = node.attributes.code;
    }
    // console.log(node);
    if (!Ext.isEmpty(node) && !Ext.isEmpty(node.parentNode.parentNode)) {
      if (node.parentNode.parentNode.id == '205') {
        telecineTySe = node.attributes.code;
      }
    }
  }

  if (now_tree.title == '지난 제작프로그램') mode = 'last';

  //검색시 정렬값 초기화
  active_tab.get(0).getStore().sortInfo = {
    field: 'created_date',
    direction: 'desc'
  };

  // 추가 상세검색 필드 들 filter 객체에 추가
  var searchTbar2 = searchToolbarBox.getComponent('toolbar2');
  if (!Ext.isEmpty(searchTbar2)) {
    var tbar2Fields = searchTbar2._getValueFields();
    Ext.each(tbar2Fields, function (field) {
      filters[field.name || field.itemId] = field.getValue();
    });

    var searchTbar3 = searchToolbarBox.getComponent('toolbar3');
    var tbar3Fields = searchTbar3._getValueFields();
    Ext.each(tbar3Fields, function (field) {
      filters[field.name || field.itemId] = field.getValue();
    });

    var searchTbar4 = searchToolbarBox.getComponent('toolbar4');
    var tbar4Fields = searchTbar4._getValueFields();
    Ext.each(tbar4Fields, function (field) {
      filters[field.name || field.itemId] = field.getValue();
    });
  } else {
    filters = null;
  }

  active_tab.get(0).reload({
    meta_table_id: active_tab.ud_content_id,
    list_type: 'common_search',
    filter_value: nodePath,
    mode: mode,
    instt: instt,
    telecine_ty_se: telecineTySe,
    search_q: value,
    search_array: Ext.encode(search_field.search_array),
    filters: Ext.encode(filters),
    tag_category_id: '',
    start: 0
    // ,
    // search_tbar: Ext.encode(search_tbar)
  });

  if (now_tree.title == '지난 제작프로그램') mode = 'last';

  content_tab.items.each(function (item) {
    //item.setTitle(item.initialConfig.title + '(' + ')');
  });
}

var isOpenedAdvSearchPanel = false;
function openAdvancePanel() {
  var s_win = Ext.getCmp('a-search-win');
  s_win.toggleCollapse(); // 2012-06-23 광회 추가 collapse mini button 과 상세검색 버튼 연동 문제.
  if (isOpenedAdvSearchPanel) {
    isOpenedAdvSearchPanel = false;
    s_win.collapse();
  } else {
    isOpenedAdvSearchPanel = true;
    s_win.expand();
    //Ext.get('search_input').dom.value = '';
  }
}
function fn_pwCheck(p) {
  chk1 = /^[a-z\d\{\}\[\]\/?.,;:|\)*~`!^\-_+&lt;&gt;@\#$%&amp;\\\=\(\'\"]{8,12}$/i; //영문자 숫자 특문자 이외의 문자가 있는지 확인
  chk2 = /[a-z]/i; //적어도 한개의 영문자 확인
  chk3 = /\d/; //적어도 한개의 숫자 확인
  chk4 = /[\{\}\[\]\/?.,;:|\)*~`!^\-_+&lt;&gt;@\#$%&amp;\\\=\(\'\"]/; //적어도 한개의 특문자 확인
  if (chk1.test(p)) {
    if (chk2.test(p) && chk3.test(p) && chk4.test(p)) {
      return true;
    } else {
      return false;
    }
  } else {
    return 're';
  }

  //return chk1.test(p) && chk2.test(p) && chk3.test(p) && chk4.test(p);
}

function excelData(
  search_type,
  url,
  grid_column,
  search_text,
  search_value1,
  search_value2
) {
  var columns = new Array();
  var coloumn_length, columnInfo;

  if (!Ext.isEmpty(grid_column)) {
    if (search_type == 'program') {
      coloumn_length = grid_column.length;
      columnInfo = grid_column;
    } else if (search_type == 'form') {
      search_text = Ext.encode(search_text);
      search_value1 = Ext.encode(search_value1);
      coloumn_length = grid_column.getColumnCount();
      columnInfo = grid_column.columns;
    } else {
      coloumn_length = grid_column.getColumnCount();
      columnInfo = grid_column.columns;
    }

    for (var i = 0; i < coloumn_length; i++) {
      if (columnInfo[i].dataIndex == 'content_id') continue;
      if (columnInfo[i].hidden == true) {
        var hidden_text = 'hidden';
      } else {
        var hidden_text = 'show';
        var column_data = new Array();
        column_data[0] = columnInfo[i].dataIndex;
        column_data[1] = columnInfo[i].header;
        column_data[2] = columnInfo[i].width;
        column_data[3] = columnInfo[i].align;
        column_data[4] = hidden_text; //grid_column.columns[i].hidden
        columns[i] = column_data;
      }
    }
  }

  var form = document.createElement('form');
  form.setAttribute('method', 'post'); // POST
  form.setAttribute('target', '_blank'); // 새창

  form.setAttribute('action', url);

  //조회값 종류
  var types = document.createElement('input');
  types.setAttribute('name', 'search_type');
  types.setAttribute('value', search_type);
  form.appendChild(types);

  //조회 필드
  var search_menu = document.createElement('input');
  search_menu.setAttribute('name', 'search_f');
  search_menu.setAttribute('value', search_text);
  form.appendChild(search_menu);

  //컬럼
  var grid_column = document.createElement('input');
  grid_column.setAttribute('name', 'columns');
  grid_column.setAttribute('value', Ext.encode(columns));
  form.appendChild(grid_column);

  //조회 값
  var search_values1 = document.createElement('input');
  search_values1.setAttribute('name', 'search_v');
  search_values1.setAttribute('value', search_value1);
  form.appendChild(search_values1);

  var search_values1 = document.createElement('input');
  search_values1.setAttribute('name', 'search_sdate');
  search_values1.setAttribute('value', search_value1);
  form.appendChild(search_values1);

  var search_values2 = document.createElement('input');
  search_values2.setAttribute('name', 'search_edate');
  search_values2.setAttribute('value', search_value2);
  form.appendChild(search_values2);

  //excel 구분값
  var is_excel = document.createElement('input');
  is_excel.setAttribute('name', 'is_excel');
  is_excel.setAttribute('value', 1);
  form.appendChild(is_excel);

  document.body.appendChild(form);
  form.submit();

  return;
}

function delete_sub_story_board(story_board_id) {
  Ext.MessageBox.confirm(_text('MN00034'), _text('MSG02115'), function (btn) {
    if (btn === 'yes') {
      var action = 'delete_sub_story_board';
      Ext.Ajax.request({
        url: '/store/catalog/edit.php',
        params: {
          action: action,
          story_board_id: story_board_id
        },
        callback: function (opt, success, response) {
          var images_view = Ext.getCmp('images-view');
          images_view.store.reload();
        }
      });
    }
  });
}

function edit_sub_story_board(element) {
  var sb_content = element.getAttribute('sb_content');
  var sb_title = element.getAttribute('sb_title');
  var sb_id = element.getAttribute('sb_id');
  new Ext.Window({
    layout: 'fit',
    height: 200,
    width: 600,
    modal: true,
    title: _text('MN02300'),
    buttonAlign: 'center',
    items: [
      {
        xtype: 'form',
        cls: 'change_background_panel',
        padding: 5,
        labelWidth: 50,
        border: false,
        items: [
          {
            xtype: 'textfield',
            anchor: '100%',
            fieldLabel: _text('MN00249'), //'제목'
            name: 'title',
            value: sb_title
          },
          {
            xtype: 'textarea',
            anchor: '100%',
            fieldLabel: _text('MN02311'), //'제목'
            name: 'content',
            value: sb_content
          },
          {
            xtype: 'textfield',
            anchor: '100%',
            hidden: true,
            fieldLabel: _text('MN02312'), //'제목'
            name: 'peoples'
            //value: peoples
          }
        ]
      }
    ],

    buttons: [
      {
        text:
          '<span style="position:relative;top:1px;"><i class="fa fa-edit" style="font-size:13px;"></i></span>&nbsp;' +
          _text('MN00043'),
        scale: 'medium',
        handler: function (btn) {
          var win = btn.ownerCt.ownerCt;
          var form = win.get(0).getForm();
          if (form.getValues().title.trim() == '') {
            Ext.Msg.alert(_text('MN01039'), _text('MSG00090'));
            return;
          }
          var wait_msg = Ext.Msg.wait(_text('MSG02036'), _text('MN00066')); //('등록중입니다.', '요청');
          Ext.Ajax.request({
            url: '/store/catalog/edit.php',
            params: {
              action: 'edit_story_board_title',
              story_board_id: sb_id,
              title: form.getValues().title,
              content: form.getValues().content
              //peoples: form.getValues().peoples,
            },
            callback: function (opts, success, response) {
              wait_msg.hide();
              if (success) {
                try {
                  var r = Ext.decode(response.responseText);
                  if (r.success) {
                    win.close();
                    var images_view = Ext.getCmp('images-view');
                    images_view.store.reload();
                  } else {
                    Ext.Msg.alert(_text('MN00003'), r.msg); //'확인'
                  }
                } catch (e) {
                  Ext.Msg.alert(_text('MN01039'), response.responseText); //'오류'
                }
              } else {
                Ext.Msg.alert(_text('MN01098'), response.statusText); //'서버 오류'
              }
            }
          });
        }
      },
      {
        text:
          '<span style="position:relative;top:1px;"><i class="fa fa-close" style="font-size:13px;"></i></span>&nbsp;' +
          _text('MN00004'),
        scale: 'medium',
        handler: function (btn) {
          btn.ownerCt.ownerCt.close();
        }
      }
    ]
  }).show();
}

function fn_contain_elements(list, value) {
  for (var i = 0; i < list.length; ++i) {
    if (list[i] === value) return true;
  }

  return false;
}

function fn_action_icon_show_detail_popup(av_content_id) {
  var content_id = av_content_id;

  self.load = new Ext.LoadMask(Ext.getBody(), { msg: _text('MSG00143') });
  self.load.show();
  var that = self;

  if (!Ext.Ajax.isLoading(self.isOpen)) {
    var sm = Ext.getCmp('tab_warp').getActiveTab().get(0).getSelectionModel();
    var sel = sm.getSelected();

    self.isOpen = Ext.Ajax.request({
      url: '/javascript/ext.ux/Ariel.DetailWindow.php',
      params: {
        content_id: content_id,
        record: Ext.encode(sel.json)
      },
      callback: function (self, success, response) {
        if (success) {
          that.load.hide();
          try {
            var r = Ext.decode(response.responseText);

            if (r !== undefined && !r.success) {
              Ext.Msg.show({
                title: '경고',
                msg: r.msg,
                icon: Ext.Msg.WARNING,
                buttons: Ext.Msg.OK
              });
            }
          } catch (e) { }
        } else {
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

function fn_action_icon_show_workflow(av_content_id) {
  var rs = [];
  rs.push(av_content_id);

  Ext.Ajax.request({
    url: '/javascript/ext.ux/viewWorkFlow.php',
    params: {
      records: Ext.encode(rs)
    },
    callback: function (options, success, response) {
      if (success) {
        try {
          Ext.decode(response.responseText);
        } catch (e) {
          Ext.Msg.alert(e['name'], e['message']);
        }
      } else {
        Ext.Msg.alert(_text('MN00022'), response.statusText);
      }
    }
  });
}
function showContextMenuLists() {
  var menuItem = Ariel.menu_context;
  var sm = Ext.getCmp('tab_warp').getActiveTab().get(0).getSelectionModel();
  var rec = sm.getSelected();
  var tabUdContentId = Ext.getCmp('tab_warp').getActiveTab().ud_content_id;
  var restoreAt = rec.json.restore_at;
  var archiveStatus = rec.json.archive_status;
  //var oriFlag = rec.json.ori_flag;
  var oriStatus = rec.json.ori_status;
  var oriExt = rec.json.ori_ext;

  if (rec.json.category_id == 202) {
    var isHomepage = true;
  } else {
    var isHomepage = false;
  }

  if (rec.json.category_id == 204) {
    var isEhistory = true;
  } else {
    var isEhistory = false;
  }

  var isOnArchiveMedia = null;

  if (rec.json.archive_status == 2 || rec.json.archive_status == 3) {
    isOnArchiveMedia = true;
  }
  // if( !Ext.isEmpty(rec.json.medias)){
  //   for(var i=0; rec.json.medias.length;i++)
  //   {
  //     console.log(rec.json.medias[i]);
  //     // if( rec.json.medias[i]['media_type'] =='archive' && rec.json.medias[i]['status'] =='0' ){
  //     //   //아카이브 미디어가 있는 경우
  //     //   isOnArchiveMedia = true;
  //     // }
  //   }
  // }

  Ext.each(menuItem.items.items, function (r) {

    // ContentAction.php 매뉴 배열에 itemId 가 있는 요소만 false로 해놓고 보여주는 여부를 정한다.
    if (r != undefined && r.itemId != undefined && !Ext.isEmpty(r.itemId) && isValue(r.itemId)) {
      r.setVisible(false);
      r.fireEvent('showmenu', menuItem, r, rec);
    }
  });
  //Ariel.menu_context.getComponent('deleteFile').setVisible(true);
  if (Ariel.menu_context.getComponent('prohibitedUse')) {
    Ariel.menu_context.getComponent('prohibitedUse').setVisible(true);
  }

  if (Ariel.menu_context.getComponent('downloadExcel')) {
    Ariel.menu_context.getComponent('downloadExcel').setVisible(true);
  }

  if (Ariel.menu_context.getComponent('download')) {
    Ariel.menu_context.getComponent('download').setVisible(true);
  }

  if (oriStatus != 1) {
    //메인 스토리지에 파일이 있는 경우
    //변환
    //전송 가능
    switch (tabUdContentId) {
      case 1:
      case 2:
        if (Ariel.menu_context.getComponent('ConversionVideo')) {
          Ariel.menu_context.getComponent('ConversionVideo').setVisible(true);
        }
        if (Ariel.menu_context.getComponent('CustomConversionVideo')) {
          Ariel.menu_context
            .getComponent('CustomConversionVideo')
            .setVisible(true);
        }
        if (Ariel.menu_context.getComponent('sendToMplayout')) {
          Ariel.menu_context.getComponent('sendToMplayout').setVisible(true);
        }
        break;
      case 7:
        if (Ariel.menu_context.getComponent('ConversionVideo')) {
          Ariel.menu_context.getComponent('ConversionVideo').setVisible(true);
        }
        if (Ariel.menu_context.getComponent('CustomConversionVideo')) {
          Ariel.menu_context
            .getComponent('CustomConversionVideo')
            .setVisible(true);
        }
        // if (Ariel.menu_context.getComponent('publishSns')) {
        //   Ariel.menu_context.getComponent('publishSns').setVisible(true);
        // }
        if (Ariel.menu_context.getComponent('sendToMplayout')) {
          Ariel.menu_context.getComponent('sendToMplayout').setVisible(true);
        }
        break;
      case 3:
        if (Ariel.menu_context.getComponent('ConversionVideo')) {
          Ariel.menu_context.getComponent('ConversionVideo').setVisible(true);
        }
        if (Ariel.menu_context.getComponent('CustomConversionVideo')) {
          Ariel.menu_context
            .getComponent('CustomConversionVideo')
            .setVisible(true);
        }
        // if (Ariel.menu_context.getComponent('publishSns')) {
        //   Ariel.menu_context.getComponent('publishSns').setVisible(true);
        // }
        if (Ariel.menu_context.getComponent('transfer_to_maincontrol')) {
          Ariel.menu_context
            .getComponent('transfer_to_maincontrol')
            .setVisible(true);
        }
        if (Ariel.menu_context.getComponent('sendToMplayout')) {
          Ariel.menu_context.getComponent('sendToMplayout').setVisible(true);
        }
        break;
      case 9:
        if (Ariel.menu_context.getComponent('ConversionVideo')) {
          Ariel.menu_context.getComponent('ConversionVideo').setVisible(true);
        }
        if (Ariel.menu_context.getComponent('CustomConversionVideo')) {
          Ariel.menu_context
            .getComponent('CustomConversionVideo')
            .setVisible(true);
        }
        // if (Ariel.menu_context.getComponent('publishSns')) {
        //   Ariel.menu_context.getComponent('publishSns').setVisible(true);
        // }
        if (Ariel.menu_context.getComponent('transmission_zodiac')) {
          Ariel.menu_context
            .getComponent('transmission_zodiac')
            .setVisible(true);
        }
        if (Ariel.menu_context.getComponent('sendToMplayout')) {
          Ariel.menu_context.getComponent('sendToMplayout').setVisible(true);
        }
        break;
    }
  }

  //아카이브 미디어가 있는경우
  //전송 가능
  if (isOnArchiveMedia) {
    switch (tabUdContentId) {
      case 3:
        if (Ariel.menu_context.getComponent('transfer_to_maincontrol')) {
          Ariel.menu_context
            .getComponent('transfer_to_maincontrol')
            .setVisible(true);
        }
        break;
      case 9:
        if (Ariel.menu_context.getComponent('transmission_zodiac')) {
          Ariel.menu_context
            .getComponent('transmission_zodiac')
            .setVisible(true);
        }
        break;
    }
  }
  //if(archiveStatus === '1' || archiveStatus === '2' || archiveStatus === '3'){
  if (!Ext.isEmpty(archiveStatus)) {
    //아카이브
    switch (tabUdContentId) {
      case 1:
      case 2:
      case 3:
      case 7:
      case 9:
        if (Ariel.menu_context.getComponent('restoreRequest')) {
          Ariel.menu_context.getComponent('restoreRequest').setVisible(true);
        }
        if (Ariel.menu_context.getComponent('archiveDeleteRequest')) {
          Ariel.menu_context
            .getComponent('archiveDeleteRequest')
            .setVisible(true);
        }

        if (Ariel.menu_context.getComponent('deleteFile') != undefined) {
          Ariel.menu_context.getComponent('deleteFile').setVisible(false);
        } else {
          if (Ariel.menu_context.getComponent('deleteFileRequest')) {
            Ariel.menu_context
              .getComponent('deleteFileRequest')
              .setVisible(false);
          }
        }
        break;
    }
  } else {
    //아카이브 아닌경우
    switch (tabUdContentId) {
      case 1:
      case 2:
      case 3:
      case 7:
      case 9:
        if (oriExt == 'mxf' || oriExt == 'mov') {
          if (Ariel.menu_context.getComponent('archiveRequest')) {
            Ariel.menu_context.getComponent('archiveRequest').setVisible(true);
          }
        }
        break;
    }
    if (Ariel.menu_context.getComponent('deleteFile') != undefined) {
      Ariel.menu_context.getComponent('deleteFile').setVisible(true);
    } else {
      if (Ariel.menu_context.getComponent('deleteFileRequest')) {
        Ariel.menu_context.getComponent('deleteFileRequest').setVisible(true);
      }
    }
  }

  if (restoreAt == 1) {
    //리스토어한 영상인 경우 삭제 기간 연장 버튼 활성화
    if (Ariel.menu_context.getComponent('extendedUse')) {
      Ariel.menu_context.getComponent('extendedUse').setVisible(true);
    }
  }

  if (isHomepage || isEhistory) {
    // console.log(isHomepage);
    // console.log(isEhistory);
    if (Ariel.menu_context.getComponent('ConversionVideo')) {
      Ariel.menu_context.getComponent('ConversionVideo').setVisible(false);
    }

    if (Ariel.menu_context.getComponent('transfer_to_maincontrol')) {
      Ariel.menu_context
        .getComponent('transfer_to_maincontrol')
        .setVisible(false);
    }

    if (Ariel.menu_context.getComponent('transmission_zodiac')) {
      Ariel.menu_context.getComponent('transmission_zodiac').setVisible(false);
    }

    if (Ariel.menu_context.getComponent('archiveDeleteRequest')) {
      Ariel.menu_context.getComponent('archiveDeleteRequest').setVisible(false);
    }

    if (Ariel.menu_context.getComponent('deleteFile')) {
      Ariel.menu_context.getComponent('deleteFile').setVisible(false);
    }
    if (Ariel.menu_context.getComponent('deleteFileRequest')) {
      Ariel.menu_context.getComponent('deleteFileRequest').setVisible(false);
    }
  }

  // 콘텐츠 유형 
  switch (tabUdContentId) {
    case 1:
      if (Ariel.menu_context.getComponent('updateContentType')) {
        Ariel.menu_context.getComponent('updateContentType').setVisible(true);
      }
      break;
    case 2:
      if (Ariel.menu_context.getComponent('updateContentType')) {
        Ariel.menu_context.getComponent('updateContentType').setVisible(true);
      }
      break;
    case 3:
      if (Ariel.menu_context.getComponent('updateContentType')) {
        Ariel.menu_context.getComponent('updateContentType').setVisible(true);
      }
      break;
    case 4:
      break;
    case 5:
      break;
    case 7:
      if (Ariel.menu_context.getComponent('updateContentType')) {
        Ariel.menu_context.getComponent('updateContentType').setVisible(true);
      }
      break;
    case 8:
      break;
    case 9:
      if (Ariel.menu_context.getComponent('updateContentType')) {
        Ariel.menu_context.getComponent('updateContentType').setVisible(true);
      }
      break;
  }

  if (Ariel.menu_context.getComponent('contentHidden')) {
    Ariel.menu_context.getComponent('contentHidden').setVisible(true);
  }

  Ariel.menu_context.fireEvent('showContextMenuLists', Ariel.menu_context);
  return;
}

function fn_action_icon_show_context_menu(av_content_id, event) {
  var sm = Ext.getCmp('tab_warp').getActiveTab().get(0).getSelectionModel();
  var content_id_data = av_content_id;
  var list_content_data = Ext.getCmp('tab_warp')
    .getActiveTab()
    .get(0)
    .getStore();
  var list_content = list_content_data.data.items;

  if (sm.hasSelection()) {
    var selection = sm.getSelections();
    var content_id_array = [];
    Ext.each(selection, function (r, i, a) {
      content_id_array.push({
        content_id: r.get('content_id')
      });
    });

    var isExists = false;
    Ext.each(content_id_array, function (r, i, a) {
      if (r.content_id == content_id_data) {
        isExists = true;
      }
    });

    if (isExists == false) {
      for (i = 0; i < list_content.length; i++) {
        if (list_content[i].id == content_id_data) {
          sm.selectRow(i);
          break;
        }
      }
    }
  } else {
    for (i = 0; i < list_content.length; i++) {
      if (list_content[i].id == content_id_data) {
        sm.selectRow(i);
        break;
      }
    }
  }

  var selected_content_data = sm.getSelected().data;
  var archived_checked = 0;
  var restore_checked = 0;
  var process_checked = 0;
  var xyEvent = [event.clientX + 50, event.clientY - 80];

  if (selected_content_data['archive_yn'] == 'Y') {
    archived_checked = 1;
  } else if (selected_content_data['archive_yn'] == 'N') {
    restore_checked = 1;
  } else if (selected_content_data['archive_yn'] == 'P') {
    process_checked = 1;
  }

  if (!Ext.isEmpty(Ariel.menu_context)) {
    var restore_menu_item = Ext.getCmp('restore_menu_item');
    var archive_menu_item = Ext.getCmp('archive_menu_item');

    if (!Ext.isEmpty(archive_menu_item) && !Ext.isEmpty(restore_menu_item)) {
      if (
        archived_checked == 1 &&
        restore_checked == 0 &&
        process_checked == 0
      ) {
        // restore
        archive_menu_item.setVisible(false);
        restore_menu_item.setVisible(true);
        Ariel.menu_context.showAt(xyEvent);
        return;
      } else if (
        archived_checked == 0 &&
        restore_checked == 1 &&
        process_checked == 0
      ) {
        // archive
        restore_menu_item.setVisible(false);
        archive_menu_item.setVisible(true);
        Ariel.menu_context.showAt(xyEvent);
        return;
      } else {
        restore_menu_item.setVisible(false);
        archive_menu_item.setVisible(false);
        Ariel.menu_context.showAt(xyEvent);
        return;
      }
    } else {
      showContextMenuLists();
      Ariel.menu_context.showAt(xyEvent);
      return;
    }
  }
}

function fn_action_icon_show_loudness(av_content_id) {
  show_loudness_log(av_content_id);
}

function fn_action_icon_do_archive(av_content_id) {
  var send_win = new Ext.Window({
    title: _text('MN01057'),
    width: 300,
    height: 100,
    modal: true,
    layout: 'fit',
    resizable: false,
    items: [
      {
        xtype: 'form',
        id: 'req_archive_form',
        padding: 5,
        labelWidth: 100,
        labelAlign: 'right',
        labelSeparator: '',
        defaults: {
          xtype: 'textfield',
          width: '90%'
        },
        items: [
          {
            xtype: 'combo',
            readOnly: false,
            anchor: '95%',
            triggerAction: 'all',
            fieldLabel: _text('MN01057'),
            allowBlank: false,
            name: 'archive_group',
            editable: false,
            forceSelection: true,
            displayField: 'name',
            valueField: 'code',
            hiddenName: 'archive_group',
            store: new Ext.data.JsonStore({
              url: '/store/get_archive_group.php',
              autoLoad: true,
              root: 'data',
              fields: ['code', 'name']
            })
          }
        ]
      }
    ],
    buttonAlign: 'center',
    buttons: [
      {
        text: _text('MN00066'),
        handler: function (b, e) {
          var form_valid = Ext.getCmp('req_archive_form').getForm().isValid();
          if (!form_valid) {
            Ext.Msg.alert(_text('MN00023'), _text('MSG01017'));
            return;
          }
          var values = b.ownerCt.ownerCt.get(0).getForm().getValues();

          var rs = [];
          rs.push({
            content_id: av_content_id,
            archive_group: values.archive_group
          });

          b.ownerCt.ownerCt.close();

          //requestAction('archive', '아카이브 하시겠습니까?', rs);MN00056 MSG01007
          requestAction(
            'archive',
            _text('MN00056') + '. ' + _text('MSG01007'),
            rs
          );
        }
      },
      {
        text: _text('MN00031'),
        handler: function (b, e) {
          b.ownerCt.ownerCt.close();
        }
      }
    ]
  }).show();
}

function fn_action_icon_do_restore(av_content_id, type) {
  var sm = Ext.getCmp('tab_warp').getActiveTab().get(0).getSelectionModel();

  var rs = [];
  var _rs = sm.getSelections();
  Ext.each(_rs, function (r, i, a) {
    rs.push(r.get('content_id'));
  });

  var sel = sm.getSelected();
  var content_id = sel.get('content_id');

  if (type == 'restore') {
    requestAction(type, _text('MN01021') + '. ' + _text('MSG01007'), rs);
  } else {
    var self = Ext.getCmp('tab_warp').getActiveTab().get(0);
    self.load = new Ext.LoadMask(Ext.getBody(), { msg: _text('MSG00143') });
    self.load.show();
    var that = self;

    if (!Ext.Ajax.isLoading(self.isOpen)) {
      self.isOpen = Ext.Ajax.request({
        url: '/javascript/ext.ux/Ariel.DetailWindow.php',
        params: {
          content_id: content_id,
          record: Ext.encode(sel.json),
          page_mode: 'pfr'
        },
        callback: function (self, success, response) {
          if (success) {
            that.load.hide();
            try {
              if (sel.get('status') == -1) {
                Ext.Msg.show({
                  title: '경고',
                  msg: _text('MSG00216'),
                  icon: Ext.Msg.WARNING,
                  buttons: Ext.Msg.OK,
                  fn: function (btnId, txt, opt) {
                    var r = Ext.decode(response.responseText);
                  }
                });
              } else {
                var r = Ext.decode(response.responseText);
              }

              if (r !== undefined && !r.success) {
                Ext.Msg.show({
                  title: '경고',
                  msg: r.msg,
                  icon: Ext.Msg.WARNING,
                  buttons: Ext.Msg.OK
                });
              }
            } catch (e) { }
          } else {
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
}

function fn_action_icon_do_delete_hr_content(av_content_id) {
  var sm = Ext.getCmp('tab_warp').getActiveTab().get(0).getSelectionModel();
  var seletions = sm.getSelections();
  var isArchive;
  for (i = 0; i < seletions.length; i++) {
    if (seletions[i].data.archive_yn == 'N') {
      isArchive = false;
      break;
    }
  }

  if (isArchive == false) {
    Ext.Msg.alert(_text('MN00144'), _text('MSG02120'));
    return;
  }

  var win = new Ext.Window({
    layout: 'fit',
    title: _text('MN00128'),
    modal: true,
    width: 500,
    height: 150,
    buttonAlign: 'center',
    items: [
      {
        id: 'delete_inform',
        xtype: 'form',
        border: false,
        frame: true,
        padding: 5,
        labelWidth: 70,
        cls: 'change_background_panel',
        defaults: {
          anchor: '100%'
        },
        items: [
          {
            id: 'delete_reason',
            xtype: 'textarea',
            height: 50,
            fieldLabel: _text('MN00128'),
            allowBlank: false,
            blankText: _text('MSG01062'), //'삭제 사유를 적어주세요',
            msgTarget: 'under'
          }
        ]
      }
    ],
    buttons: [
      {
        text:
          '<span style="position:relative;top:1px;"><i class="fa fa-trash" style="font-size:13px;"></i></span>&nbsp;' +
          _text('MN00034'),
        scale: 'medium',
        handler: function (btn, e) {
          var isValid = Ext.getCmp('delete_reason').isValid();
          if (!isValid) {
            Ext.Msg.show({
              icon: Ext.Msg.INFO,
              title: _text('MN00024'),
              msg: _text('MSG01062'), //'삭제사유를 적어주세요.',
              buttons: Ext.Msg.OK
            });
            return;
          }

          var sm = Ext.getCmp('tab_warp')
            .getActiveTab()
            .get(0)
            .getSelectionModel();
          var tm = Ext.getCmp('delete_reason').getValue();

          var rs = [];
          var _rs = sm.getSelections();
          Ext.each(_rs, function (r, i, a) {
            rs.push({
              content_id: r.get('content_id'),
              delete_his: tm
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
              ownerCt.sendAction('delete_hr', rs, ownerCt);
              win.destroy();
            }
          });
        }
      },
      {
        text:
          '<span style="position:relative;top:1px;"><i class="fa fa-close" style="font-size:13px;"></i></span>&nbsp;' +
          _text('MN00004'),
        scale: 'medium',
        handler: function (btn, e) {
          win.destroy();
        }
      }
    ]
  });
  win.show();
}

function fn_action_icon_do_delete_content(av_content_id) {
  var win = new Ext.Window({
    layout: 'fit',
    title: _text('MN00128'),
    modal: true,
    width: 500,
    height: 150,
    buttonAlign: 'center',
    items: [
      {
        id: 'delete_inform',
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
            id: 'delete_reason',
            xtype: 'textarea',
            height: 50,
            fieldLabel: _text('MN00128'),
            allowBlank: false,
            blankText: _text('MSG02015'),
            msgTarget: 'under'
          }
        ]
      }
    ],
    buttons: [
      {
        text:
          '<span style="position:relative;top:1px;"><i class="fa fa-trash" style="font-size:13px;"></i></span>&nbsp;' +
          _text('MN00034'),
        scale: 'medium',
        handler: function (btn, e) {
          var isValid = Ext.getCmp('delete_reason').isValid();
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
          var tm = Ext.getCmp('delete_reason').getValue();

          var rs = [];
          var _rs = sm.getSelections();
          Ext.each(_rs, function (r, i, a) {
            rs.push({
              content_id: r.get('content_id'),
              delete_his: tm
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
              ownerCt.sendAction('delete', rs, ownerCt);
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
}
function fn_action_icon_do_fix_loudness() {
  Ext.Msg.show({
    title: _text('MN00024'),
    msg: _text('MSG02088'),
    modal: true,
    minWidth: 100,
    icon: Ext.MessageBox.QUESTION,
    buttons: Ext.Msg.OKCANCEL,
    fn: function (btnId) {
      if (btnId == 'cancel') return;

      var sm = Ext.getCmp('tab_warp').getActiveTab().get(0).getSelectionModel();
      var sel = sm.getSelected();
      var content_id = sel.get('content_id');

      var w = Ext.Msg.wait(_text('MSG02086'));

      Ext.Ajax.request({
        url: '/store/nps_work/request_loudness.php',
        params: {
          content_id: content_id,
          action: 'adjust'
        },
        callback: function (opt, success, response) {
          w.hide();
          if (success) {
            var res = Ext.decode(response.responseText);
            if (res.success) {
              Ext.Msg.alert(_text('MN00023'), res.msg);
            } else {
              Ext.Msg.alert(_text('MN01039'), res.msg);
            }
          } else {
            Ext.Msg.alert(_text('MN01039'), response.statusText);
          }
        }
      });
    }
  });
}

function fn_action_icon_do_check_loudness() {
  Ext.Msg.show({
    title: _text('MN00024'),
    msg: _text('MSG02094'),
    modal: true,
    minWidth: 100,
    icon: Ext.MessageBox.QUESTION,
    buttons: Ext.Msg.YESNOCANCEL,
    fn: function (btnId) {
      if (btnId == 'cancel') return;

      var sm = Ext.getCmp('tab_warp').getActiveTab().get(0).getSelectionModel();
      var sel = sm.getSelected();
      var content_id = sel.get('content_id');
      var is_correct = 'N';

      if (btnId == 'yes') {
        is_correct = 'Y';
      }

      var w = Ext.Msg.wait(_text('MSG02086'));

      Ext.Ajax.request({
        url: '/store/nps_work/request_loudness.php',
        params: {
          content_id: content_id,
          action: 'measure',
          is_correct: is_correct
        },
        callback: function (opt, success, response) {
          w.hide();
          if (success) {
            var res = Ext.decode(response.responseText);
            if (res.success) {
              Ext.Msg.alert(_text('MN00023'), res.msg);
            } else {
              Ext.Msg.alert(_text('MN01039'), res.msg);
            }
          } else {
            Ext.Msg.alert(_text('MN01039'), response.statusText);
          }
        }
      });
    }
  });
}

var player_windown_flag = false;
function fn_show_player_for_play_icon(av_content_id, event) {
  event.stopPropagation();

  var sm = Ext.getCmp('tab_warp').getActiveTab().get(0).getSelectionModel();
  var content_id_data = av_content_id;
  var list_content_data = Ext.getCmp('tab_warp')
    .getActiveTab()
    .get(0)
    .getStore();
  var list_content = list_content_data.data.items;

  if (sm.hasSelection()) {
    var selection = sm.getSelections();
    var content_id_array = [];
    Ext.each(selection, function (r, i, a) {
      content_id_array.push({
        content_id: r.get('content_id')
      });
    });

    var isExists = false;
    Ext.each(content_id_array, function (r, i, a) {
      if (r.content_id == content_id_data) {
        isExists = true;
      }
    });

    if (isExists == false) {
      for (i = 0; i < list_content.length; i++) {
        if (list_content[i].id == content_id_data) {
          sm.selectRow(i);
          break;
        }
      }
    }
  } else {
    for (i = 0; i < list_content.length; i++) {
      if (list_content[i].id == content_id_data) {
        sm.selectRow(i);
        break;
      }
    }
  }

  player_windown_popup = Ext.getCmp('cuesheet_player_win');
  if (player_windown_flag) {
    return;
  } else {
    player_windown_flag = true;
    Ext.Ajax.request({
      url: '/store/cuesheet/player_window.php',
      params: {
        content_id: av_content_id
      },
      callback: function (option, success, response) {
        var r = Ext.decode(response.responseText);

        if (success) {
          r.show();
          var player3 = videojs(
            document.getElementById('player3'),
            {},
            function () { }
          );
          /*
                              $f("player3", {src: "/flash/flowplayer/flowplayer.swf", wmode: 'opaque'}, {
                                  clip: {
                                      autoPlay: true,
                                      autoBuffering: true,
                                      scaling: 'fit',
                                      provider: 'rtmp'
                                  },
                                  plugins: {
                                      rtmp: {
                                          url: '/flash/flowplayer/flowplayer.rtmp.swf',
                                          netConnectionUrl: Ariel.streamer_addr
                                      }
                                  }
                              });
                              */
          player_windown_flag = false;
        } else {
          //Ext.Msg.alert('오류', '다시 시도 해 주시기 바랍니다.');
          Ext.Msg.alert(_text('MN01098'), _text('MSG02052'));
          player_windown_flag = false;
          return;
        }
      }
    });
  }
}

/*
    Category management Search page
*/
function fn_category_management_media() {
  var west_menu_item_media_category = Ext.getCmp(
    'west_menu_item_media_category'
  );
  var isCollapsed = west_menu_item_media_category.collapsed;
  if (isCollapsed) {
    west_menu_item_media_category.expand(false);
  } else {
    west_menu_item_media_category.collapse(false);
  }

  var win = new Ext.Window({
    title: _text('MN02221'),
    id: 'manage_category_media',
    width: 500,
    modal: true,
    height: 250,
    miniwin: true,
    resizable: false,
    layout: 'fit',
    items: [
      {
        id: 'menu-tree-media-management',
        layout: 'fit',
        border: false,
        xtype: 'navcategory',
        //ddGroup: 'ContentDD',
        //title: '프로그램',
        //title: _text('MN00387'),
        rootVisible: false,
        listeners: {
          render: function (self) {
            // new Ext.tree.TreeSorter(self, {
            //     property:
            // });
          }
        },
        reload_category_search: function () {
          if (!Ext.isEmpty(Ext.getCmp('nav_tab'))) {
            var tree, treeLoader, rootNode, activeTab;
            tree = Ext.getCmp('menu-tree');
            rootNode = tree.getRootNode();
            treeLoader = tree.getLoader();
            activeTab = Ext.getCmp('tab_warp').getActiveTab();
            treeLoader.baseParams.ud_content_id = activeTab.ud_content_id;
            rootNode.attributes.read = activeTab.c_read;
            rootNode.attributes.add = activeTab.c_add;
            rootNode.attributes.edit = activeTab.c_edit;
            rootNode.attributes.del = activeTab.c_del;
            rootNode.attributes.hidden = activeTab.c_hidden;

            if (!rootNode.attributes.read) {
              rootNode.disabled = true;
            } else {
              rootNode.disabled = false;
            }

            if (treeLoader.isLoading()) {
              treeLoader.abort();
            }
            treeLoader.load(rootNode);
          }
        },
        loader: new Ext.tree.TreeLoader({
          url: '/store/get_categories.php',
          listeners: {
            beforeload: function (treeLoader, node, callback) {
              if (!treeLoader.loaded && !treeLoader.baseParams.ud_content_id) {
                treeLoader.baseParams.ud_content_id = Ext.getCmp(
                  'tab_warp'
                ).getActiveTab().ud_content_id;
              }
              treeLoader.baseParams.action = 'get-folders';
              treeLoader.baseParams.read = node.attributes.read;
              //2015-11-19 카테고리 보임
              //treeLoader.baseParams.read = 1;
              treeLoader.baseParams.add = node.attributes.add;
              treeLoader.baseParams.edit = node.attributes.edit;
              treeLoader.baseParams.del = node.attributes.del;
              treeLoader.baseParams.hidden = node.attributes.hidden;
            },

            load: function (treeLoader, node, callback) {
              if (treeLoader.baseParams.ud_content_id) {
                treeLoader.loaded = true;
                delete treeLoader.baseParams.ud_content_id;
              }

              if (
                !Ext.getCmp('menu-tree-media-management')
                  .getRootNode()
                  .isExpanded()
              ) {
                Ext.getCmp('menu-tree-media-management').getRootNode().expand();
              }

              if (!node.attributes.read) {
                node.disable(true);
              }
            }
          }
        })
      }
    ],
    listeners: {
      close: function (self) {
        Ext.getCmp('menu-tree-media-management').reload_category_search();
      }
    },
    buttonAlign: 'center',
    fbar: [
      {
        text:
          '<span style="position:relative;top:1px;"><i class="fa fa-plus" style="font-size:13px;"></i></span>&nbsp;' +
          _text('MN00033'), //add
        scale: 'medium',
        handler: function (b, e) {
          var categoryManagementObj = Ext.getCmp('menu-tree-media-management');
          var categoryManagementSelectNode = categoryManagementObj
            .getSelectionModel()
            .getSelectedNode();
          if (categoryManagementSelectNode != null) {
            categoryManagementObj.invokeCreateFolder(
              categoryManagementSelectNode
            );
          } else {
            Ext.Msg.alert(_text('MN00023'), _text('MSG00026'));
            return;
          }
        }
      },
      {
        text:
          '<span style="position:relative;top:1px;"><i class="fa fa-edit" style="font-size:13px;"></i></span>&nbsp;' +
          _text('MN00043'), //edit
        scale: 'medium',
        handler: function (b, e) {
          var categoryManagementObj = Ext.getCmp('menu-tree-media-management');
          var categoryManagementSelectNode = categoryManagementObj
            .getSelectionModel()
            .getSelectedNode();
          if (categoryManagementSelectNode != null) {
            categoryManagementObj.editor.triggerEdit(
              categoryManagementSelectNode
            );
          } else {
            Ext.Msg.alert(_text('MN00023'), _text('MSG00026'));
            return;
          }
        }
      },
      {
        scale: 'medium',
        text:
          '<span style="position:relative;top:1px;"><i class="fa fa-trash" style="font-size:13px;"></i></span>&nbsp;' +
          _text('MN00034'), //delete
        handler: function (b, e) {
          var categoryManagementObj = Ext.getCmp('menu-tree-media-management');
          var categoryManagementSelectNode = categoryManagementObj
            .getSelectionModel()
            .getSelectedNode();
          if (categoryManagementSelectNode != null) {
            if (categoryManagementSelectNode.getDepth() < 2) {
              Ext.Msg.alert(_text('MN00023'), _text('MSG02510'));
              return;
            } else {
              categoryManagementObj.deleteFolder(categoryManagementSelectNode);
            }
          } else {
            Ext.Msg.alert(_text('MN00023'), _text('MSG00026'));
            return;
          }
        }
      },
      {
        text:
          '<span style="position:relative;top:1px;"><i class="fa fa-close" style="font-size:13px;"></i></span>&nbsp;' +
          _text('MN00031'),
        scale: 'medium',
        handler: function (b, e) {
          Ext.getCmp('menu-tree-media-management').reload_category_search();
          win.destroy();
        }
      }
    ]
  });
  win.show();
}

function fn_formatBytes(bytes, decimals) {
  if (bytes == 0) return '0 Bytes';
  var k = 1000,
    dm = decimals || 2,
    sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'],
    i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
}

// D&D web upload
function fn_make_droppable_area(element, callback) {
  var ud_content_tab_data;
  Ext.Ajax.request({
    url: '/interface/mam_ingest/get_meta_json.php',
    method: 'POST',
    params: {
      kind: 'ud_content',
      flag: 'webupload'
    },
    callback: function (opt, success, response) {
      if (success) {
        var result = Ext.decode(response.responseText);
        ud_content_tab_data = result.data;
      } else {
      }
    }
  });

  var content_tab = Ext.getCmp('tab_warp');

  var input = document.createElement('input');
  input.setAttribute('type', 'file');
  input.setAttribute('name', 'FileUpload');
  // input.setAttribute('multiple', true);
  input.style.display = 'none';

  input.addEventListener('change', triggerCallback);
  element.appendChild(input);

  var v_notify = $('#notify');
  var v_notify_msg = $('#notify-msg');
  var search_category = Ext.getCmp('menu-tree');

  element.addEventListener('dragover', function (e) {
    e.preventDefault();
    e.stopPropagation();
    element.classList.add('dragover');

    var current_ud_content_id = content_tab.getActiveTab().ud_content_id;
    var current_category_text;
    var selected_category = search_category
      .getSelectionModel()
      .getSelectedNode();
    if (selected_category) {
      current_category_text = selected_category.text;
    } else {
      var node = search_category.getNodeById(current_ud_content_id);
      current_category_text = node.text;
    }
    v_notify_msg.html(_text('MSG02520') + " ('" + current_category_text + "')");
    v_notify.css({ opacity: 1, display: 'inline-block' });
    element.style.border = '3px solid #337AB7';
  });

  element.addEventListener('dragleave', function (e) {
    e.preventDefault();
    e.stopPropagation();
    //element.classList.remove('dragover');
    element.style.border = '0px';
    v_notify.css({ opacity: 0, display: 'none' });
  });

  element.addEventListener('drop', function (e) {
    e.preventDefault();
    e.stopPropagation();
    //element.classList.remove('dragover');
    element.style.border = '0px';
    v_notify.css({ opacity: 0, display: 'none' });
    var current_ud_content_id = content_tab.getActiveTab().ud_content_id;
    var content_type_allow_data;
    ud_content_tab_data.map(function (val) {
      if (val.ud_content_id == current_ud_content_id) {
        content_type_allow_data = val.allowed_extension.toUpperCase();
      }
    });
    if (Ext.isEmpty(content_type_allow_data)) {
      //get_meta_json에서 업로드 권한 있는것만 나옴.
      Ext.Msg.alert(_text('MN00023'), _text('MSG01002')); //권한이 지정되지 않았습니다.
      return;
    }
    var extension_allow = content_type_allow_data.split(',');
    var files;
    if (e.dataTransfer) {
      files = e.dataTransfer.files;
      fn_web_upload_callback(files);
    } else if (e.target) {
      files = e.target.files;
    }

    filenames = [];
    for (var i = 0; i < files.length; i++) {
      filenames.push(files[i].name);
    }
    for (var i = 0; i < filenames.length; i++) {
      filenames[i];
      var filename_arr = filenames[i].split('.');
      var extension_index = filename_arr.length - 1;
      var file_extension = '.' + filename_arr[extension_index].toUpperCase();
      if (extension_allow.indexOf(file_extension) === -1) {
        Ext.Msg.alert(
          _text('MN00023'),
          _text('MN00309') + ' : ' + extension_allow.join(', ')
        );
        return;
      }
    }
  });

  function triggerCallback(e) {
    var files;
    if (e.dataTransfer) {
      files = e.dataTransfer.files;
    } else if (e.target) {
      files = e.target.files;
    }
    callback.call(null, files);
  }
}

/**
 * Web uploader function
 */
function fn_web_upload_callback(files) {
  var option = {
    user_id: '<?=$user_id?>',
    user_lang: '<?=$user_lang?>',
    is_drag: 'Y',
    files: files
  };
  proximaWebUploader(option, UPLOAD_URL);
}

function fn_filter_management() {
  var west_menu_item_media_filters = Ext.getCmp('west_menu_item_media_filters');
  var isCollapsed = west_menu_item_media_filters.collapsed;
  if (isCollapsed) {
    west_menu_item_media_filters.expand(false);
  } else {
    west_menu_item_media_filters.collapse(false);
  }

  var selModel = new Ext.grid.CheckboxSelectionModel({
    singleSelect: false,
    checkOnly: true
  });

  var win = new Ext.Window({
    title: _text('MN02549'),
    // id: 'list_of_tag',
    cls: 'newWindowStyle',
    width: 500,
    modal: true,
    height: 400,
    miniwin: true,
    resizable: false,
    layout: 'vbox',
    items: [
      {
        xtype: 'grid',
        cls: 'proxima_customize proxima_new_grid_style',
        stripeRows: true,
        //hideHeaders: true,
        autoScroll: true,
        height: 400,
        id: 'listing_filters_management',
        store: new Ext.data.JsonStore({
          url: '/store/user_filters/user_filter_action.php',
          root: 'data',
          baseParams: {
            action: 'listing'
          },
          fields: ['id', 'title', 'code', 'use_yn', 'user_id']
        }),
        viewConfig: {
          loadMask: true,
          forceFit: true
        },
        columns: [
          selModel,
          { header: _text('MN02535'), dataIndex: 'title', sortable: false },
          { header: 'Code', dataIndex: 'code', sortable: false, hidden: true }
        ],
        sm: selModel,
        listeners: {
          afterrender: function (self) {
            self.fn_setCheckedRowByStore(self);
          }
        },
        fn_setCheckedRowByStore: function (av_grid) {
          av_grid.getStore().load({
            scope: this,
            callback: function (records, operation, success) {
              if (success) {
                var sm = av_grid.getSelectionModel();
                Ext.each(records, function (record) {
                  if (record.data.use_yn == 'Y') {
                    var row = records.indexOf(record);
                    sm.selectRow(row, true);
                  }
                });
              } else {
              }
            }
          });
        }
      }
    ],
    buttonAlign: 'center',
    fbar: [
      {
        text:
          '<span style="position:relative;top:1px;"><i class="fa fa-edit" style="font-size:13px;"></i></span>&nbsp;' +
          _text('MN00046'),
        id: 'save_order_button',
        scale: 'medium',
        handler: function (b, e) {
          var listing_filters_management = Ext.getCmp(
            'listing_filters_management'
          );
          var filterStore = listing_filters_management.getStore();
          var arr_filter_id = [];

          Ext.each(filterStore.data.items, function (item) {
            arr_filter_id.push(item.id);
          });

          var selections = listing_filters_management
            .getSelectionModel()
            .getSelections();
          var arr_selection_id = [];

          Ext.each(selections, function (selection) {
            arr_selection_id.push(selection.data.id);
          });

          var arr_unselection_id = [];

          Ext.each(arr_filter_id, function (filter_id) {
            if (arr_selection_id.indexOf(filter_id) < 0) {
              arr_unselection_id.push(filter_id);
            }
          });

          Ext.Ajax.request({
            url: '/store/user_filters/user_filter_action.php',
            params: {
              action: 'update_useyn_filter',
              arr_filter_selection: Ext.encode(arr_selection_id),
              arr_filter_unselection: Ext.encode(arr_unselection_id)
            },
            success: function (conn, response, options, eOpts) {
              var west_menu_item_media_filters = Ext.getCmp(
                'west_menu_item_media_filters'
              );
              west_menu_item_media_filters.fn_setFiltersUI(
                west_menu_item_media_filters
              );
              win.close();
            }
          });
        }
      },
      {
        text:
          '<span style="position:relative;top:1px;"><i class="fa fa-close" style="font-size:13px;"></i></span>&nbsp;' +
          _text('MN00031'),
        scale: 'medium',
        handler: function (b, e) {
          win.close();
        }
      }
    ]
  });
  win.show();
}

function noticePopup(n_id) {
  Ext.Ajax.request({
    url: '/pages/menu/config/notice/win_notice.php',
    params: {
      action: 'main_view',
      type: 'view',
      notice_id: n_id,
      screen_width: window.innerWidth,
      screen_height: window.innerHeight
    },
    callback: function (self, success, response) {
      try {
        var r = Ext.decode(response.responseText);
        r.show();
      } catch (e) {
        //>>Ext.Msg.alert('오류', e);
        Ext.Msg.alert(_text('MN00022'), e);
      }
    }
  });
}

// 사용자 정보 수정
function showUserModifiyInformation() {
  new Ariel.user.modifiy({
    width: 350,
    height: 350,
    title: _text('MN00043'), //'사용자 정보변경'
    modal: true,
    border: true,
    layout: 'fit',
    type: 'update'
  }).show();
  // win.loadData(function(){
  //     win.show();
  // });
}

// 미디어정보 프로그램 별 | 연도별 탭 체인지
function change_media_active_tab(activeTab) {
  // var tabPanel = new Ariel.nav.MainPanel({
  //     useWholeCategory: false,
  //     changeActiveTab: activeTab
  // });
  // var panel = Ext.getCmp('west_menu_item_media_category');
  // // panel.removeAll();

  // panel.add(tabPanel);
  // panel.doLayout();
  // if (!(Ext.getCmp('nav_tab').changeActiveTab == null)) {
  //     self.setActiveTab(Ext.getCmp('nav_tab').changeActiveTab);
  //     self.doLayout();
  // };
  Ext.getCmp('tree-tab').setActiveTab(activeTab);
}
function jsonStoreByCode(code, self) {
  var jsonStore = new Ext.data.JsonStore({
    restful: true,
    proxy: new Ext.data.HttpProxy({
      method: 'GET',
      url: '/api/v1/open/data-dic-code-sets/' + code + '/code-items',
      type: 'rest'
    }),
    root: 'data',
    fields: [
      { name: 'code_itm_code', mapping: 'code_itm_code' },
      { name: 'code_itm_nm', mapping: 'code_itm_nm' },
      { name: 'id', mapping: 'id' }
    ],
    listeners: {
      load: function (store, r) {
        var comboRecord = Ext.data.Record.create([
          { name: 'code_itm_code' },
          { name: 'code_itm_nm' }
        ]);
        var allComboMenu = {
          code_itm_code: 'All',
          code_itm_nm: '전체'
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
      },
      exception: function (self, type, action, opts, response, args) {
        try {
          var r = Ext.decode(response.responseText, true);

          if (!r.success) {
            Ext.Msg.alert(_text('MN00023'), r.msg);
          }
        } catch (e) {
          Ext.Msg.alert(_text('MN00023'), r.msg);
        }
      }
    }
  });
  return jsonStore;
}

function YmdHisToDate(str) {
  var year = str.substring(0, 4);
  var month = str.substring(4, 6);
  var day = str.substring(6, 8);
  var hour = str.substring(8, 10);
  var minute = str.substring(10, 12);
  var second = str.substring(12, 14);
  var date = new Date(year, month - 1, day, hour, minute, second);
  return date;
}
