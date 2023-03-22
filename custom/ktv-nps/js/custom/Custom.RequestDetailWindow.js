Ext.ns("Custom");
Custom.RequestDetailWindow = Ext.extend(Ext.Window, {
  action: null,
  label_width: null,
  // 레코드 데이터
  data: null,
  // add or edit
  type: null,
  // 의뢰 그리드
  requestGrid: null,

  // uploadFormId: Ext.id(),
  // uploadFieldId: Ext.id(),
  uploadFormId: Ext.id(),


  id: 'requestDetail',
  modal: true,
  width: Ext.getBody().getViewSize().width * (90 / 100),
  height: Ext.getBody().getViewSize().height * (90 / 100),
  autoScroll: true,
  layout: 'fit',
  border: false,
  buttonAlign: 'center',
  listeners: {
    close: function () {
      this.requestGrid.getStore().reload();
      // get_request_count_info();
    }
  },
  initComponent: function () {
    //불필요 기능 삭제
    //this._getRequestCountInfo();
    this._initialize();
    Custom.RequestDetailWindow.superclass.initComponent.call(this);
  },
  _initialize: function () {


    var _this = this;

    switch (_this.type) {
      case 'add':
        var url = Ariel.DashBoard.Url.request;
        var method = 'POST';
        var msg = '추가 하시겠습니까?';
        var text = '추가';
        break;
      case 'edit':
        var url = Ariel.DashBoard.Url.requestId(_this.data.ord_id);
        var method = 'POST';
        var msg = '수정 하시겠습니까?';
        var text = '수정';
        break;
    }



    this.title = this.typeSe.ord_meta_cd + ' ' + _text('MN02101') + ' ' + _text('MN02091');//' 편집 의뢰'
    this.items = this._hBoxPanel();
    this.buttons = [{
      text: '<span style="position:relative;top:1px;"><i class="fa fa-edit" style="font-size:13px;"></i></span>&nbsp;' + text,//update
      scale: 'medium',
      handler: function (self, e) {
        var formRequestData = Ext.getCmp('form_request').getForm().getValues();
        var formUserData = Ext.getCmp('form_user').getForm().getValues();

        var typeSe = _this.typeSe;
        Ext.Msg.show({
          title: _text('MN00023'),//'알림'
          msg: msg,
          buttons: Ext.Msg.OKCANCEL,
          fn: function (btnId) {
            if (btnId == 'ok') {
              Ext.Ajax.request({
                url: url,
                method: method,
                params: {
                  request_data: Ext.encode(formRequestData),
                  user_data: Ext.encode(formUserData),
                  typeSe: Ext.encode(typeSe)
                },
                callback: function (opts, success, resp) {
                  if (success) {
                    try {
                      var res = Ext.decode(resp.responseText);

                      // 폼 여러개 저장해놨다가 첨부파일 한번에 업로드 한다.
                      if (_this.type == 'add') {
                        var gridUploadFile = Ext.getCmp('grid_upload_file');
                        var ordId = res.data.ord_id;

                        _this._ajaxUpload(gridUploadFile, ordId);
                      }

                      _this.destroy();
                      _this.requestGrid.getStore().reload();

                      Ext.Msg.alert('알림', text + ' 되었습니다.');
                    } catch (e) {
                      Ext.Msg.alert(e['name'], e['message']);
                    }
                  } else {
                    Ext.Msg.alert('status: ' + resp.status, resp.statusText);
                  }
                }
              })
            }
          }
        });
      }
    }, {
      text: '<span style="position:relative;top:1px;"><i class="fa fa-close" style="font-size:13px;"></i></span>&nbsp;' + _text('MN00004'),//cancel
      scale: 'medium',
      handler: function (self, e) {
        _this.requestGrid.getStore().reload();
        // get_request_count_info();
        _this.destroy();
      }

    }];
  },
  _hBoxPanel: function () {
    var _this = this;
    var items = [];

    if (_this.type == 'add') {
      items = [_this._panelRight()];
    } else if (_this.type == 'edit') {
      items = [_this._panelLeft(), _this._panelRight()];
    };

    var hBoxPanel = new Ext.Panel({
      layout: 'hbox',
      // margins: '100 0 0 0',
      layoutConfig: {
        padding: '5',
        align: 'stretch'
      },
      frame: false,
      border: false,
      items: items
    });
    return hBoxPanel;
  },

  /**
   * PanelLeft
   */
  _panelLeft: function () {
    var _this = this;
    var panelLeft = new Ext.Panel({
      layout: 'vbox',
      cls: 'change_background_panel',
      title: _text('MN02099'),//'news'
      flex: 1,
      layoutConfig: {
        padding: '5',
        align: 'stretch'
      },
      frame: false,
      border: true,
      // height: this.height - 60,
      items: [
        _this._formNews(),
        _this._gridEdl(),
        _this._gridContent()
      ]
    });
    return panelLeft;
  },
  _formNews: function () {
    var _this = this;

    var formNews = new Ext.FormPanel({
      labelSeparator: '',
      id: 'form_article',
      flex: 2,
      // height: 330,
      width: '100%',
      style: {
        background: 'white',
        fontSize: '20px'
      },
      frame: false,
      border: false,
      labelWidth: _this.label_width,
      padding: 10,
      defaults: {
        anchor: '99%'
      },
      layout: {
        type: 'hbox',
        align: 'stretch'  // Child items are stretched to full width
      },
      items: [{
        layout: 'vbox',
        width: 50,
        items: [{
          xtype: 'label',
          margins: '5 0 0 0',
          text: '*' + _text('MN00249'),//제목,
        }, {
          xtype: 'label',
          margins: '10 0 0 0',
          text: '*' + _text('MN02103'),//기사내용
        }]
      }, {
        flex: 1,
        layout: 'vbox',
        layout: {
          type: 'vbox',
          align: 'stretch'  // Child items are stretched to full width
        },
        margins: '0 0 0 5',
        items: [{
          xtype: 'textfield',
          name: 'title',
          id: 'article_title',
          style: 'font-size: 18px;',
          fieldLabel: '*' + _text('MN00249'),//제목
          value: '',
          readOnly: true
        }, {
          xtype: 'textarea',
          fieldLabel: '*' + _text('MN02103'),//기사내용
          style: 'font-size: 18px; line-height: 1.5em;',
          name: 'detail',
          flex: 1,
          value: '',
          listeners: {
            afterrender: function (self, e) {
              if (_this.type == 'edit') {
                var params = {
                  action: _this.action,
                  artcl_id: _this.data.artcl_id,
                  rd_seq: _this.data.rd_deq
                };
              } else {
                var params = {
                  action: _this.action
                };
              }
              Ext.Ajax.request({
                url: '/store/request_zodiac/get_article.php',
                // params: {
                // action: _this.action,
                // action : '<?=$type?>',
                // artcl_id: _this.data.artcl_id,
                // rd_seq: '<?=$rd_seq?>'
                // },
                params: params,
                callback: function (opt, success, response) {
                  try {
                    var r = Ext.decode(response.responseText);
                    if (r.success) {
                      self.setValue(r.data.artcl_ctt);
                      Ext.getCmp('article_title').setValue(r.data.artcl_titl);
                    } else {
                      Ext.Msg.alert(_text('MN00023'), r.msg);
                    }
                  } catch (e) {
                    //console.log(e);
                  }
                }
              });

            }
          },
          readOnly: true
        }, {
          xtype: 'textfield',
          name: 'ord_id',
          value: _this.data.ord_id,
          hidden: true
        }]
      }]
    });
    return formNews;
  },
  _gridEdl: function () {
    var _this = this;
    var storeEdl = new Ext.data.JsonStore({
      url: '/store/request_zodiac/request_list.php',
      root: 'data',
      totalProperty: 'total',
      autoLoad: true,
      fields: [
        { name: 'ord_id' },
        { name: 'mark_in' },
        { name: 'mark_out' },
        { name: 'edl_titl' },
        { name: 'video_id' },
        { name: 'content_id' },
        { name: 'ord_no' }
      ],
      listeners: {
        beforeload: function (self, opts) {
          opts.params = opts.params || {};

          Ext.apply(opts.params, {
            action: 'list_edl',
            ord_id: _this.data.ord_id
          });
        },
        load: function (store, records, opts) {
          //myMask.hide();
        }
      }
    });
    var gridEdl = new Ext.grid.GridPanel({
      title: 'EDL',
      loadMask: true,
      cls: 'proxima_customize',
      enableDD: false,
      store: storeEdl,
      // height: 160,
      border: false,
      autoScroll: true,
      flex: 1,
      plain: true,
      hidden: true,
      tbar: ['Drag:', {
        xtype: 'component',
        width: 50,
        html: '<img align="center" id="edl" src="/led-icons/download_bicon.png" width="17px" title = "' + _text('MSG02029') + '"/>',//EDL Import시 드래그해주세요.
        listeners: {
          afterrender: function (self) {
            var ori_ext = 'xml';
            var filename = _this.data.ord_id;
            var root_path = 'X:/storage/lowres';
            var path = 'application/' + ori_ext + ':file:///' + root_path + '/EDL/' + filename + '.' + ori_ext;

            //데이터뷰에서 각 이미지에 설정해놓은 ID로 객체를 찾는다
            if (Ext.isChrome) {
              var thumb_img = document.getElementById('edl');
              if (!Ext.isEmpty(thumb_img)) {
                //객체가 있을경우 URL 셋팅 - 섬네일용
                thumb_img.addEventListener("dragstart", function (evt) {
                  evt.dataTransfer.setData("DownloadURL", path);
                }, false);
              }
            }
          }
        }
      }, '->', {

          style: {
            marginLeft: '5px'
          },
          cls: 'proxima_button_customize',
          width: 30,
          text: '<span style="position:relative;top:1px;" title="' + _text('MN00050') + '"><i class="fa fa-download" style="font-size:13px;color:white;"></i></span>',//'다운로드'
          handler: function (btn, e) {
            var getStore = btn.ownerCt.ownerCt.getStore();

            if (getStore.getCount() < 1) {
              Ext.Msg.alert(_text('MN00023'), _text('MSG02028'));
              return;
            } else {
              var ord_id = _this.data.ord_id;
              window.open('/store/request_zodiac/getEDL.php?ord_id=' + ord_id);
            }
          }
        }],
      selModel: new Ext.grid.RowSelectionModel({
        singleSelect: true,
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
        rowdblclick: function (self, rowIndex, e) {
          _this._showContent(self);
        }
      },
      cm: new Ext.grid.ColumnModel({
        defaults: {
          sortable: true,
          align: 'center'
        },
        columns: [
          new Ext.grid.RowNumberer(),
          { header: _text('MN00249'), dataIndex: 'edl_titl', width: 120 },//'제목'
          { header: 'Script ID', dataIndex: 'ord_id', width: 60, hidden: true },
          { header: _text('MN02105'), dataIndex: 'mark_in', width: 60 },//'시작'
          { header: _text('MN02104'), dataIndex: 'mark_out', width: 60 },//'종료'
          { header: _text('MN02087') + 'ID', dataIndex: 'video_id', width: 60, hidden: true }//'비디오ID'
        ]
      })
    });
    return gridEdl;
  },
  _showContent: function (self) {
    var sm = self.getSelectionModel().getSelected();

    var content_id = sm.get('content_id');

    //>>self.load = new Ext.LoadMask(Ext.getBody(), {msg: '상세 정보를 불러오는 중입니다...'});
    self.load = new Ext.LoadMask(Ext.getBody(), { msg: _text('MSG00143') });
    self.load.show();
    var that = self;

    if (!Ext.Ajax.isLoading(self.isOpen)) {
      self.isOpen = Ext.Ajax.request({
        url: '/javascript/ext.ux/Ariel.DetailWindow.php',
        params: {
          content_id: content_id,
          record: Ext.encode(sm.json)
        },
        callback: function (self, success, response) {
          if (success) {
            that.load.hide();
            try {
              var r = Ext.decode(response.responseText);

              if (r !== undefined && !r.success) {
                Ext.Msg.show({
                  title: _text('MN00021')//'경고'
                  , msg: r.msg
                  , icon: Ext.Msg.WARNING
                  , buttons: Ext.Msg.OK
                });
              }
            }
            catch (e) {
              //alert(response.responseText)
              //Ext.Msg.alert(e['name'], e['message'] );
            }
          }
          else {
            //>>Ext.Msg.alert('서버 오류', response.statusText+'('+response.status+')');
            Ext.Msg.alert(_text('MN00022'), response.statusText + '(' + response.status + ')');
          }
        }
      });
    } else {
      that.load.hide();
    }
  },
  _gridContent: function () {
    var _this = this;
    var storeVideo = new Ext.data.JsonStore({
      url: '/store/request_zodiac/request_list.php',
      root: 'data',
      totalProperty: 'total',
      autoLoad: true,
      fields: [
        { name: 'ord_id' },
        { name: 'content_id' },
        { name: 'title' }
      ],
      listeners: {
        beforeload: function (self, opts) {
          opts.params = opts.params || {};

          Ext.apply(opts.params, {
            action: 'list_video',
            ord_id: _this.data.ord_id,
            type: _this.typeSe.ord_meta_cd
          });
        },
        load: function (store, records, opts) {
          //myMask.hide();
        }
      }
    });
    var gridContent = new Ext.grid.GridPanel({
      title: _this.typeSe.ord_meta_cd,
      hidden: true,
      loadMask: true,
      cls: 'proxima_customize',
      enableDD: false,
      border: false,
      frame: false,
      store: storeVideo,
      autoScroll: true,
      // height: 160,
      flex: 1,
      plain: true,
      selModel: new Ext.grid.RowSelectionModel({
        singleSelect: true,
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
        rowdblclick: function (self, rowIndex, e) {
          showContent(self);
        }
      },
      cm: new Ext.grid.ColumnModel({
        defaults: {
          sortable: true,
          align: 'center'
        },
        columns: [
          new Ext.grid.RowNumberer(),
          { header: 'ID', dataIndex: 'ord_id', width: 60, hidden: true },
          //{header: '_this.typeSe.ord_meta_cd', dataIndex: 'content_id', width: 60},
          { header: _text('MN00249'), dataIndex: 'title', width: 60 }//'제목'
        ]
      })
    });
    return gridContent;
  },
  /**
   * PanelRight
   */
  _panelRight: function () {
    var _this = this;
    var items = [];

    if (_this.type == 'add') {
      items = [
        _this._formEditor(),
        _this._formRequest(),
        _this._gridUploadFile()
      ];

    } else if (_this.type == 'edit') {
      items = [this._formEditor(), _this._formRequest(), _this._gridFile()];
    };

    var panelRight = new Ext.Panel({
      layout: 'vbox',
      cls: 'change_background_panel',
      title: _text('MN00095'),//'의뢰'
      flex: 1,
      layoutConfig: {
        padding: '5',
        align: 'stretch'
      },
      frame: false,
      border: true,
      items: items
    });
    return panelRight;
  },
  _formEditor: function () {
    var _this = this;
    if (_this.type == 'add') {
      var hideCheck = false;
    } else {
      var hideCheck = true;
    }
    var formEditor = new Ext.FormPanel({
      id: 'form_user',
      frame: false,
      width: '100%',
      style: {
        paddingTop: '5px',
        background: 'white'
      },
      height: 30,
      border: false,
      labelWidth: 1,
      defaults: {
        labelStyle: 'text-align:center;',
        anchor: '100%'
      },
      items: [{
        xtype: 'compositefield',
        style: {
          background: 'white'
        },
        items: [{
          xtype: 'displayfield',
          width: _this.label_width,
          value: '*' + _text('MN02102')//편집자
        }, {
          xtype: 'textfield',
          id: 'editor_name',
          style: 'font-size: 18px;',
          readOnly: true,
          name: 'user_nm',
          // value: _this.data.work_user.user_nm,
          width: 115
        }, {
          xtype: 'textfield',
          disabled: true,
          style: 'font-size: 18px;',
          name: 'user_id',
          id: 'editor_id',
          // value: _this.data.work_user.user_id,
          width: 110
        }, {
          xtype: 'button',
          cls: 'proxima_button_customize',
          id: 'request_editing_request_search_user_btn',
          width: 30,
          height: 30,
          style: {
            top: '-4'
          },
          text: '<span style="position:relative;top:1px;" title="' + _text('MN00190') + '"><i class="fa fa-search" style="font-size:13px;color:white;"></i></span>',//'사용자 조회'
          //width : 80,
          handler: function (btn, e) {
            _this._searchUser();
          }
        }, {
          xtype: 'displayfield',
          width: 5
        }, {
          xtype: 'button',
          hidden: true,
          text: '<span style="position:relative;top:1px;"><i class="fa fa-check" style="font-size:13px;"></i></span>&nbsp;' + _text('MN02101'),//'편집자 저장'
          width: 80,
          handler: function (btn, e) {
            if (Ext.isEmpty(Ext.getCmp('editor_id').getValue())) {
              Ext.Msg.alert(_text('MN00023'), _text('MSG02027'));
            }
            // else if (Ext.isEmpty('<?=$_SESSION['user']['user_id']?>')) {

            //     Ext.Msg.alert(_text('MN00023'), _text('MSG01001'));
            // } 
            else {
              setUser(Ext.getCmp('editor_id').getValue());
            }
          }
        }, {
          xtype: 'displayfield',
          value: '*' + '그래픽의뢰유형',
          listeners: {
            afterrender: function (self) {
              if (_this.typeSe.ord_meta_cd !== "graphic") {
                self.hide();
              };
            }
          }
        }, {
          xtype: 'combo',
          name: 'graphic_reqest_ty',
          hiddenName: 'graphic_reqest_ty',
          editable: false,
          mode: "local",
          displayField: "code_itm_nm",
          valueField: "code_itm_code",
          hiddenValue: "code_itm_code",
          typeAhead: true,
          width: 100,
          triggerAction: "all",
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
                formEditor.getForm().setValues(_this.data);
                if (Ext.isEmpty(formEditor.getForm().findField('graphic_reqest_ty').getValue())) {
                  formEditor.getForm().findField('graphic_reqest_ty').setValue(r[0].get('code_itm_code'));
                };
              }
            }
          }),
          listeners: {
            afterrender: function (self) {
              if (_this.type === 'edit') {
                self.disable();
                self.setReadOnly(true);
              }
              if (_this.typeSe.ord_meta_cd !== "graphic") {
                self.hide();
                self.disable();
              };
              self.getStore().load({
                params: {
                  is_code: 1
                }
              });
            }
          }
        }]
      }],
      listeners: {
        afterrender: function (self) {

          if (_this.type == 'edit') {
            formEditor.getForm().setValues(_this.data.work_user);

          }
        }
      }
    });
    return formEditor;
  },
  _formRequest: function () {
    var _this = this;
    var formRequest = new Ext.FormPanel({
      labelSeparator: '',
      id: 'form_request',
      // width: '100%',
      style: {
        paddingTop: '8px',
        background: 'white'
      },
      frame: false,
      flex: 2,
      border: false,
      labelWidth: _this.label_width,
      layout: {
        type: 'hbox',
        align: 'stretch'  // Child items are stretched to full width
      },
      padding: 5,
      defaults: {
        anchor: '99%'
      },
      items: [{
        layout: 'vbox',
        width: 50,
        items: [{
          xtype: 'label',
          margins: '5 0 0 0',
          text: '*' + _text('MN00249'),//제목,
        }, {
          xtype: 'label',
          margins: '10 0 0 0',
          text: '*' + _text('MN02093'),//업무의뢰내역,
        }]
      }, {
        flex: 1,
        layout: 'vbox',
        layout: {
          type: 'vbox',
          align: 'stretch'  // Child items are stretched to full width
        },
        margins: '0 0 0 5',
        items: [{
          xtype: 'textfield',
          name: 'title',
          style: 'font-size: 18px;',
          fieldLabel: '*' + _text('MN00249'),//제목
          // value: _this.data.title
        }, {
          xtype: 'textarea',
          style: 'font-size: 18px; line-height: 1.5em;',
          fieldLabel: '*' + _text('MN02093'),//업무의뢰내역
          name: 'ord_ctt',
          flex: 1,
        }, {
          xtype: 'textfield',
          name: 'ord_id',
          // value: _this.data.ord_id,
          hidden: true
        }]
      }],
      listeners: {
        afterrender: function (self) {
          if (_this.type == 'edit') {
            formRequest.getForm().setValues(_this.data);
          }
        }
      }
    });
    return formRequest;
  },
  _gridFile: function () {
    var _this = this;

    // if (_this.data.artcl_id == null) {
    //     var uploadCheck = false;
    // } else {
    //     var uploadCheck = true;
    // }
    if (_this.type == 'add') {
      var store_file = new Ext.data.ArrayStore({
        fields: [
          // { name: 'ord_id' },
          // { name: 'file_path' },
          // { name: 'file_name' },
          // { name: 'fileAttachFakePath', type: 'file' }
          { name: 'formItem' }
        ]
      });
    } else if (_this.type == 'edit') {
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
    }

    var gridFile = new Ext.grid.GridPanel({
      title: _text('MN01045'),//'첨부파일'
      loadMask: true,
      cls: 'proxima_customize',
      enableDD: false,
      id: 'grid_file',
      store: store_file,

      border: false,
      autoScroll: true,
      frame: false,
      flex: 1,
      plain: true,
      uploadForm: [],
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
        // hidden: uploadCheck,
        hidden: false,
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
        }, {
          xtype: 'button',
          cls: 'proxima_button_customize',
          width: 30,
          text: '<span style="position:relative;top:1px;" title="' + '첨부파일 삭제' + '"><i class="fa fa-ban" style="font-size:13px;color:white;"></i></span>',//'첨부파일 삭제'
          handler: function (btn, e) {
            var sm = gridFile.getSelectionModel();



            Ext.Msg.show({
              title: '알림',
              msg: '첨부파일을 삭제하시겠습니까?',
              buttons: Ext.Msg.OKCANCEL,
              fn: function (btnId) {
                if (btnId == 'ok') {
                  if (sm.hasSelection()) {
                    var rec = sm.getSelected();
                    var id = rec.id;
                    Ext.Ajax.request({
                      method: 'delete',
                      url: '/api/v1/attach/' + id,
                      callback: function (opts, success, response) {
                        var res = Ext.decode(response.responseText);
                        if (res.success) {
                          Ext.Msg.alert('알림', '첨부파일이 삭제 되었습니다.');
                          gridFile.getStore().reload();
                        } else {
                          Ext.Msg.alert('알림', res.msg);
                        };
                      }
                    })
                  } else {
                    Ext.Msg.alert('알림', '삭제하실 목록을 선택해주세요.');
                  }
                }
              }
            });
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
          { header: '파일 이름', dataIndex: 'file_name', width: 60 },
          { header: '', dataIndex: 'fileAttachFakePath', width: 100 }
        ]
      })
    });
    return gridFile;
  },
  /**
   * 추가 창에서 첨부파일 추가시 이 그리드에다가 파일 이름들을 넣어 보여줌
   */
  _gridUploadFile: function () {
    var _this = this;


    var store_file = new Ext.data.ArrayStore({
      fields: [
        { name: 'ord_id' },
        { name: 'id' },
        { name: 'file_path' },
        { name: 'file_name' },
        { name: 'fileAttachFakePath', type: 'file' }
      ]
    });


    var gridFile = new Ext.grid.GridPanel({
      title: _text('MN01045'),//'첨부파일'
      loadMask: true,
      cls: 'proxima_customize',
      enableDD: false,
      id: 'grid_upload_file',
      store: store_file,

      border: false,
      autoScroll: true,
      frame: false,
      flex: 1,
      plain: true,
      uploadForm: [],
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
        // hidden: uploadCheck,
        hidden: false,
        cls: 'proxima_button_customize',
        width: 30,
        text: '<span style="position:relative;top:1px;" title="' + _text('MN00399') + '"><i class="fa fa-upload" style="font-size:13px;color:white;"></i></span>',
        handler: function (self) {
          _this._uploadWindowAdd(gridFile);
        }
      }, {
          xtype: 'button',
          cls: 'proxima_button_customize',
          width: 30,
          text: '<span style="position:relative;top:1px;" title="' + '첨부파일 삭제' + '"><i class="fa fa-ban" style="font-size:13px;color:white;"></i></span>',//'첨부파일 삭제'
          handler: function () {
            var sm = gridFile.getSelectionModel();
            var rec = sm.getSelected();
            if (sm.hasSelection()) {
              Ext.Msg.show({
                title: '알림',
                msg: '첨부파일을 목록에서 삭제하시겠습니까?',
                buttons: Ext.Msg.OKCANCEL,
                fn: function (btnId) {
                  if (btnId == 'ok') {
                    gridFile.getStore().remove(rec);
                    Ext.Msg.alert('알림', '목록에서 삭제되었습니다.');
                  }
                }
              });
            } else {
              Ext.Msg.alert('알림', '삭제할 목록을 선택해주세요.');
            }
          }
        }],
      cm: new Ext.grid.ColumnModel({
        defaults: {
          sortable: true,
          align: 'center'
        },
        columns: [
          new Ext.grid.RowNumberer({
            hidden: true
          }),
          { header: 'ID', dataIndex: 'ord_id', width: 60, hidden: true },
          { header: '파일 이름', dataIndex: 'file_name', width: 60 },
        ]
      })
    });
    return gridFile;
  },
  /**
   * viewDetailRequest -> function get_request_count_info()
   * 리스트 목록
   */
  _getRequestCountInfo: function () {
    return Ext.Ajax.request({
      url: '/store/request_zodiac/request_list.php',
      params: {
        action: 'get_total_new_count'
      },
      callback: function (opt, success, response) {
        try {
          var r = Ext.decode(response.responseText, true);
          if (r.success) {
            var total_new_request_count = r.total_new_request_count;
            var total_new_video_count = r.total_new_video_count;
            var total_new_graphic_count = r.total_new_graphic_count;

          } else {
            Ext.Msg.alert(_text('MN00022'), r.msg);
          }
        } catch (e) {
          alert(_text('MN01098'), e.message + '(responseText: ' + response.responseText + ')');
        }
      }
    })
  },
  /**
   * 유저검색 윈도우
   */
  _searchUser: function () {
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
            var user = this._selected.data;
            Ext.getCmp('form_user').getForm().setValues(user);
            win.close();
          }
        }
      }).show();
    });
  },
  /**
   * 수정버튼에 있는 다운로드 사용시 
   */
  _downLoadBtn: function (files) {
    var _this = this;

    Ext.each(files, function (file) {
      var filePath = file.get('file_path');
      var fileName = file.get('file_name');
      var ordId = _this.data.ord_id;
      var type = 'attach';

      // console.log('file', file);

      // Ext.Ajax.request({
      //     url: 'custom/ktv-nps/download/download.php',
      //     params: {
      //         file_path: filePath,
      //         file_name: fileName,
      //         ord_id: ordId,
      //         type: 'attach'
      //     }
      // });
      // Ext.getBody().mask('DownLoading....', 'x-mask-loading');
      Ext.Ajax.request({
        method: 'GET',
        url: '/api/v1/attach/' + ordId,
        callback: function (opts, success, response) {
          var res = Ext.decode(response.responseText);


          //  console.log(res.data.le);

          if (!Ext.isEmpty(res.data)) {
            for (var i = 0; i < res.data.length; i++) {
              if (file.id == res.data[i].id) {
                //  console.log('일치');

                //if (!Ext.isEmpty(res.data[i].storage_id)) {
                var url = 'custom/ktv-nps/download/download.php' + '?path=' + filePath + '&name=' + fileName + '&type=' + type + '&fileid=' + res.data[i].id;

                // 윈도우 새창을 열어 다운로드 하는 방식
                // window.open(url);
                // 새창 안띄우게 하고 다운로드 하려는 방식 조디악 파일 다운 안됨
                // new Ext.Window({
                //     layout: 'fit',
                //     autoEl: {
                //         tag: "iframe",
                //         src: url
                //     }
                // }).show();
                // iframe 다른 방식

                Ext.getBody().createChild({
                  tag: 'iframe',
                  cls: 'x-hidden',
                  onload: 'var t = Ext.get(this); t.remove.defer(1000, t);',
                  src: url
                });
                //}
              }
            }
          }


          // if (!Ext.isEmpty(res.data.storage_id)) {
          //     var url = 'custom/ktv-nps/download/download.php' + '?path=' + filePath + '&name=' + fileName + '&type=' + type + '&fileid=' + res.data.id;
          //     window.open(url);
          // } else {
          //     /**
          //      * cps에서 첨부한 파일 수정해야함!
          //      */

          //     var filePath = res.data.file_path;
          //     var fileName = res.data.file_name;

          //     var aTag = document.getElementById('downloadImg');

          //     if (aTag) {
          //         document.body.removeChild(aTag);
          //     }

          //     aTag = document.createElement('a');
          //     aTag.setAttribute('href', filePath);
          //     aTag.setAttribute('download', fileName);
          //     aTag.setAttribute('style', 'display:none;');
          //     aTag.setAttribute('id', 'downloadImg');
          //     aTag.innerHTML = 'downloadImg';

          //     document.body.appendChild(aTag);

          //     aTag.click();

          // };
        }
      })

      // var url = Ariel.DashBoard.Url.downloadPath(filePath, fileName, 'attach', ordId);



      // var url = Ariel.DashBoard.Url.downloadPath(filePath, fileName) + '&type=attach&ord_id = '.ordId;

      // var url = Ariel.DashBoard.Url.downloadPath(filePath, fileName, 'attach');
      // var aTag = document.getElementById('downloadImg');

      // if (aTag) {
      //     document.body.removeChild(aTag);
      // }

      // aTag = document.createElement('a');

      // aTag.setAttribute('href', url);
      // aTag.setAttribute('download', '');
      // aTag.setAttribute('style', 'display:none;');
      // aTag.setAttribute('id', 'downloadImg');
      // aTag.innerHTML = 'downloadImg';

      // document.body.appendChild(aTag);
      // aTag.click();
    });

  },
  _uploadWindow: function (gridFile) {
    var _this = this;
    //확장자 체크
    var extension_arr = ['ZIP', 'HWP', 'DOC', 'DOCX', 'XML', 'PPTX', 'PPT', 'XLS', 'XLSX', 'PDF', 'JPG', 'JPEG', 'PNG', 'MP3', 'WAV', 'AI', 'PSD', 'EPS', 'TXT'];
    var extensionStr = extension_arr.join(',');
    var win = new Ext.Window({
      title: _text('MN00399'), //'업로드',
      width: 605,
      top: 50,
      height: 165,
      modal: true,
      layout: 'fit',
      items: [{
        xtype: 'form',
        fileUpload: true,
        border: false,
        frame: true,
        id: 'fileAttachuploadForm',
        defaults: {
          labelSeparator: '',
          labelWidth: 30,
          anchor: '95%',
          style: {
            'padding-top': '5px'
          }
        },
        items: [{
          xtype: 'fileuploadfield',
          hidden: true,
          id: 'fileAttachUpload',
          name: 'FileAttach',
          listeners: {
            fileselected: function (self, value) {
              Ext.getCmp('fileAttachFakePath').setValue(value);
            }
          }
        }, {
          xtype: 'compositefield',
          fieldLabel: _text('MN01045'), //'첨부 파일',
          items: [{
            xtype: 'textfield',
            id: 'fileAttachFakePath',
            allowBlank: false,
            readOnly: true,
            flex: 1
          }, {
            xtype: 'button',
            text: _text('MN02176'), //'파일선택',
            listeners: {
              click: function (btn, e) {
                $('#' + Ext.getCmp('fileAttachUpload').getFileInputId()).click();
              }
            }
          }]
        }, {
          xtype: 'fieldset',
          title: '허용확장자',
          collapsible: false,
          border: true,
          items: [{
            xtype: 'displayfield',
            hideLabel: true,
            value: extensionStr
          }]
        }],
        buttonsAlign: 'left',
        buttons: [{
          text: '<span style="position:relative;top:1px;"><i class="fa fa-check" style="font-size:13px;"></i></span>&nbsp;' + _text('MN00046'), //'저장'
          scale: 'small',
          handler: function (b, e) {

            var regist_form = Ext.getCmp('fileAttachuploadForm').getForm();
            if (!regist_form.isValid()) {
              Ext.Msg.alert(_text('MN00023'), _text('MSG01006')); //알림, 첨부파일을 선택 해 주시기 바랍니다.
              return;
            }
            // //확장자 체크
            // var extension_arr = ['ZIP', 'HWP', 'DOC', 'DOCX', 'XML', 'PPTX', 'PPT', 'XLS', 'XLSX', 'PDF', 'JPG', 'JPEG', 'PNG', 'MP3', 'WAV', 'AI', 'PSD', 'EPS', 'TXT'];
            var upload_file = Ext.getCmp('fileAttachUpload').getValue();
            var filename_arr = upload_file.split('.');
            var extension_index = filename_arr.length - 1;
            var file_extension = filename_arr[extension_index].toUpperCase();
            if (extension_arr.indexOf(file_extension) === -1) {
              Ext.Msg.alert(_text('MN00023'), _text('MN00309') + ' : ' + extension_arr.join(', ')); //알림, 허용 확장자 :
              return;
            }

            var filePath = regist_form.getValues().fileAttachFakePath;
            // window.open('/test/testp.php?ord_id=' + _this.data.ord_id + '&file_path=' + filePath);

            regist_form.submit({
              url: '/custom/ktv-nps/download/upload.php',
              params: {
                ord_id: _this.data.ord_id
              },
              success: function (form, action) {

                var r = Ext.decode(action.response.responseText);

                if (r.result == 'false') {
                  Ext.Msg.alert(_text('MN00023'), r.msg);
                  return;
                }
                //Ext.Msg.alert( _text('MN00023'), '등록에 성공하였습니다.');
                gridFile.getStore().reload();
                win.close();
              },
              failure: function (form, action) {

                var r = Ext.decode(action.response.responseText);
                Ext.Msg.alert(_text('MN00023'), r.msg);
              }
            });
          }
        }, {
          text: '<span style="position:relative;top:1px;"><i class="fa fa-close" style="font-size:13px;"></i></span>&nbsp;' + _text('MN00031'), //'닫기'
          scale: 'small',
          handler: function (b, e) {
            win.close();
          }
        }]
      }]
    });
    return win.show();
  },
  /**
   * 추가시 첨부파일 추가 할때 첨부파일을 선택하게 해주는 윈도우
   * 그 폼을 this에 저장한 후 this에서 꺼내어 루프 돌려서 첨부파일 업로드함
   */
  _uploadWindowAdd: function (gridFile) {
    var _this = this;
    //확장자 체크
    var extension_arr = ['ZIP', 'HWP', 'DOC', 'DOCX', 'XML', 'PPTX', 'PPT', 'XLS', 'XLSX', 'PDF', 'JPG', 'JPEG', 'PNG', 'MP3', 'WAV', 'AI', 'PSD', 'EPS', 'TXT'];
    // 확장자 스트링
    var extensionStr = extension_arr.join(',');
    var fileAttachUpload = Ext.id();
    var uploadField = new Ext.ux.form.FileUploadField({

      xtype: 'fileuploadfield',
      hidden: true,
      id: fileAttachUpload,
      name: 'FileAttach',
      listeners: {
        fileselected: function (self, value) {
          Ext.getCmp('fileAttachFakePath').setValue(value);
        }
      }
    });
    var win = new Ext.Window({
      title: _text('MN00399'), //'업로드',
      width: 605,
      top: 50,
      height: 165,
      modal: true,
      layout: 'fit',
      items: [{
        xtype: 'form',
        fileUpload: true,
        border: false,
        frame: true,
        id: 'fileAttachuploadForm',
        defaults: {
          labelSeparator: '',
          labelWidth: 30,
          anchor: '95%',
          style: {
            'padding-top': '5px'
          }
        },
        items: [uploadField, {
          xtype: 'compositefield',
          fieldLabel: _text('MN01045'), //'첨부 파일',
          items: [{
            xtype: 'textfield',
            id: 'fileAttachFakePath',
            name: 'file_name',
            allowBlank: false,
            readOnly: true,
            flex: 1
          }, {
            xtype: 'button',
            text: _text('MN02176'), //'파일선택',
            listeners: {
              click: function (btn, e) {
                $('#' + Ext.getCmp(fileAttachUpload).getFileInputId()).click();
              }
            }
          }]
        }, {
            xtype: 'fieldset',
            title: '허용확장자',
            collapsible: false,
            border: true,
            items: [{
              xtype: 'displayfield',
              hideLabel: true,
              value: extensionStr
            }]
          }],
        buttonsAlign: 'left',
        buttons: [{
          text: '<span style="position:relative;top:1px;"><i class="fa fa-check" style="font-size:13px;"></i></span>&nbsp;' + _text('MN00046'), //'저장'
          scale: 'small',
          handler: function (b, e) {

            var regist_form = Ext.getCmp('fileAttachuploadForm').getForm();
            if (!regist_form.isValid()) {
              Ext.Msg.alert(_text('MN00023'), _text('MSG01006')); //알림, 첨부파일을 선택 해 주시기 바랍니다.
              return;
            }
            // //확장자 체크
            // var extension_arr = ['ZIP', 'HWP', 'DOC', 'DOCX', 'XML', 'PPTX', 'PPT', 'XLS', 'XLSX', 'PDF', 'JPG', 'JPEG', 'PNG', 'MP3', 'WAV', 'AI', 'PSD', 'EPS', 'TXT'];
            //          // 확장자 스트링
            //          var extensionStr = extension_arr.join(',');
            var upload_file = Ext.getCmp(fileAttachUpload).getValue();
            var filename_arr = upload_file.split('.');
            var extension_index = filename_arr.length - 1;
            var file_extension = filename_arr[extension_index].toUpperCase();
            if (extension_arr.indexOf(file_extension) === -1) {
              Ext.Msg.alert(_text('MN00023'), _text('MN00309') + ' : ' + extension_arr.join(', ')); //알림, 허용 확장자 :
              return;
            }

            var filePath = regist_form.getValues().fileAttachFakePath;
            // window.open('/test/testp.php?ord_id=' + _this.data.ord_id + '&file_path=' + filePath);
            var form = new Ext.form.FormPanel({
              fileUpload: true,
              border: false,
              frame: true,
              defaults: {
                labelSeparator: '',
                labelWidth: 30,
                anchor: '95%',
                style: {
                  'padding-top': '5px'
                }
              },
              items: [uploadField]
            });

            var formData = regist_form.getValues();

            var programRecord = Ext.data.Record.create([
              { name: 'file_name' }
            ]);

            var oriFileName = formData.file_name.split('\\');
            var fileName = oriFileName[oriFileName.length - 1];
            var fileRecord = new Object();
            fileRecord.file_name = fileName;


            var fileRecord = new programRecord(fileRecord);

            var store = gridFile.getStore();
            store.add(fileRecord);
            gridFile.uploadForm.push(form);
            win.close();
          }
        }, {
          text: '<span style="position:relative;top:1px;"><i class="fa fa-close" style="font-size:13px;"></i></span>&nbsp;' + _text('MN00031'), //'닫기'
          scale: 'small',
          handler: function (b, e) {
            win.close();
          }
        }]
      }]
    });
    win.show();
  },
  /**
   * 추가상태일때 파일들을 그리에 모아 놓을 그리드 _gridUploadFile
   * 파일들 오더 아이디
   */
  _ajaxUpload: function (gridUploadFile, ordId) {
    Ext.each(gridUploadFile.uploadForm, function (r, i) {
      var win = new Ext.Window({
        hidden: true,
        items: r
      }).show();
      win.hide();
      Ext.Ajax.request({
        method: 'POST',
        form: r.getForm().getEl().dom,
        isUpload: true,
        params: {
          ord_id: ordId,
          index: i
        },
        url: '/custom/ktv-nps/download/upload.php',
        headers: { 'Content-Type': 'multipart/form-data; charset=UTF-8' },
        callback: function (opt, success, response) {
          try {
            var r = Ext.decode(response.responseText, true);
            if (r.success) {
              win.close();
            } else {
              Ext.Msg.alert(_text('MN00022'), r.msg);
            }
          } catch (e) {
            alert(_text('MN01098'), e.message + '(responseText: ' + response.responseText + ')');
          }
        }
      });
    });
  }


});