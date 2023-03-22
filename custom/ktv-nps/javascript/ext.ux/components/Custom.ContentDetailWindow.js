(function () {
  Ext.ns('Custom');

  // 콘텐츠 등록, 아카이브 목록 등에서 간단한 상세보기를 위한 창
  Custom.ContentDetailWindow = Ext.extend(Ext.Window, {
    // properties

    _selected: null, //선택한 콘텐츠 정보

    content_id: null, //선언시 자동 로드
    isPlayer: true, // 플레이어뷰 여부
    isMetaForm: false, // 메타데이터뷰 여부
    metaForm: null, //메타데이터 폼 뷰
    hideMetaFormButton: false, //메타데이터 폼 수정버튼
    player: null, //플레이어 뷰
    playerMode: 'read', //read/inout 플레이어 셋 인아웃 모드
    domainData: null, //player url
    isEasyCerti: null, // 개인정보 검출 뷰 여부

    id: Ext.id(),

    constructor: function (config) {
      this.addEvents('ok');
      this.addEvents('load');

      this.title = '콘텐츠 상세 조회';
      this.width = 990;
      this.height = 690;
      this.minWidth = 640;
      this.modal = true;
      this.layout = {
        type: 'border'
      };

      this.buttonAlign = 'center';

      //his._selected = null;

      //this.cls = 'dark-window';

      Ext.apply(this, {}, config || {});

      Custom.ContentDetailWindow.superclass.constructor.call(this);
    },

    initComponent: function (config) {
      this._initItems();

      Ext.apply(this, {}, config || {});
      Custom.ContentDetailWindow.superclass.initComponent.call(this);
    },

    _initItems: function () {
      var _this = this;

      _this.items = [];

      // _this.domainData = 'http://10.10.50.87/data/';
      _this.domainData = '/data'; //'http://10.10.50.128/data/';

      _this.listeners = {
        afterrender: function (self) {
          if (self.content_id != null) {
            self._loadContent(self.content_id);
            if (self.isEasyCerti) {
              _this._loadEasyCertiData(self.content_id);
            }
          }
        }
      };

      if (_this.isPlayer) {
        if (_this.isEasyCerti) {
          _this.player = {
            region: 'center',
            xtype: 'c-player',
            width: 640,
            height: 360,
            mode: _this.playerMode,
            listeners: {
              setinout: function (self, value) {}
            }
          };
  
          _this.leftItem = {
            layout: 'border',
            xtype: 'panel',
            region: 'center',
            border: true,
            split: true,
            items: [
              _this.player
            ]
          };

          var personalInfoDetectedItem = {
            region: 'south',
            // height:320,
            height:200,
            id: 'personalInfoDetectedItemId',
            xtype: 'form',
            cls: 'change_background_panel_detail_content background_panel_detail_content',
            autoScroll: true,
            url: '/store/content_edit1.php',
            title: '개인정보검출',
            padding: 5,
            labelWidth: 80,
            border: false,
            defaultType: 'textfield',
            defaults: {
              labelSeparator: '',
              anchor: '95%'
            },
            items: [
              {
                xtype: 'textfield',
                readOnly : true,
                allowBlank: false, 
                autoCreate: {
                  tag: 'input', type: 'text', autocomplete: 'off', maxlength: '150'
                },
                id: 'account_numbers_id',
                fieldLabel: '계좌번호',
                name: 'account_numbers'
              },
              {
                xtype: 'textfield',
                readOnly : true,
                allowBlank: false, 
                autoCreate: {
                  tag: 'input', type: 'text', autocomplete: 'off', maxlength: '150'
                },
                id: 'businessman_numbers_id',
                fieldLabel: '사업자등록번호',
                name: 'businessman_numbers'
              },
              {
                xtype: 'textfield',
                readOnly : true,
                allowBlank: false, 
                autoCreate: {
                  tag: 'input', type: 'text', autocomplete: 'off', maxlength: '150'
                },
                id: 'cellphone_numbers_id',
                fieldLabel: '휴대폰번호',
                name: 'cellphone_numbers'
              },
              {
                xtype: 'textfield',
                readOnly : true,
                allowBlank: false, 
                autoCreate: {
                  tag: 'input', type: 'text', autocomplete: 'off', maxlength: '150'
                },
                id: 'credit_cards_id',
                fieldLabel: '신용카드번호',
                name: 'credit_cards'
              },{
                xtype: 'textfield',
                readOnly : true,
                allowBlank: false, 
                autoCreate: {
                  tag: 'input', type: 'text', autocomplete: 'off', maxlength: '150'
                },
                id: 'driver_numbers_id',
                fieldLabel: '운전면허번호',
                name: 'driver_numbers'
              },
              {
                xtype: 'textfield',
                readOnly : true,
                allowBlank: false, 
                autoCreate: {
                  tag: 'input', type: 'text', autocomplete: 'off', maxlength: '150'
                },
                id: 'emails_id',
                fieldLabel: '이메일',
                name: 'emails'
              },{
                xtype: 'textfield',
                readOnly : true,
                allowBlank: false, 
                autoCreate: {
                  tag: 'input', type: 'text', autocomplete: 'off', maxlength: '150'
                },
                id: 'foreign_numbers_id',
                fieldLabel: '외국인등록번호',
                name: 'foreign_numbers'
              },
              {
                xtype: 'textfield',
                readOnly : true,
                allowBlank: false, 
                autoCreate: {
                  tag: 'input', type: 'text', autocomplete: 'off', maxlength: '150'
                },
                id: 'health_insurances_id',
                fieldLabel: '건강보험번호',
                name: 'health_insurances'
              },
              {
                xtype: 'textfield',
                readOnly : true,
                allowBlank: false, 
                autoCreate: {
                  tag: 'input', type: 'text', autocomplete: 'off', maxlength: '150'
                },
                id: 'passport_numbers_id',
                fieldLabel: '여권번호',
                name: 'passport_numbers'
              },{
                xtype: 'textfield',
                readOnly : true, 
                allowBlank: false, 
                autoCreate: {
                  tag: 'input', type: 'text', autocomplete: 'off', maxlength: '150'
                },
                id: 'prevent_words_id',
                fieldLabel: '금칙어',
                name: 'prevent_words'
              },
              {
                xtype: 'textfield',
                readOnly : true,
                allowBlank: false, 
                autoCreate: {
                  tag: 'input', type: 'text', autocomplete: 'off', maxlength: '150'
                },
                id: 'social_numbers_id',
                fieldLabel: '주민등록번호',
                name: 'social_numbers'
              },{
                xtype: 'textfield',
                readOnly : true,
                allowBlank: false, 
                autoCreate: {
                  tag: 'input', type: 'text', autocomplete: 'off', maxlength: '150'
                },
                id: 'telephone_numbers_id',
                fieldLabel: '전화번호',
                name: 'telephone_numbers'
              },
            ]
          }
          _this.leftItem.items.push(personalInfoDetectedItem);
          _this.items.push(_this.leftItem);
        } else {
          _this.player = {
            region: 'center',
            xtype: 'c-player',
            border: true,
            width: 640,
            height: 360,
            mode: _this.playerMode,
            listeners: {
              setinout: function (self, value) {}
            }
          };
          _this.items.push(_this.player);
        }
      }

      if (_this.isMetaForm) {
        _this.width = _this.width + 400;

        _this.metaForm = {
          xtype: 'tabpanel',
          collapsible: true,
          split: true,
          region: 'east',
          width: 650,
          listeners: {
            render: function (self) {},
            tabchange: function (self, tab) {
              // 탭별로 버튼이 있어서
              if (_this.hideMetaFormButton) {
                switch (tab.title) {
                  case '기본정보':
                    Ext.each(tab.get(0).buttons, function (r) {
                      r.hide();
                    });
                    break;
                  case '홈페이지':
                  case '포털':
                  case 'e영상역사관':
                    Ext.each(tab.buttons, function (r) {
                      r.hide();
                    });
                    break;
                }
              }

              // 업로드 버튼 추가
              switch (tab.title) {
                case '기본정보':
                  _this._makeUploadButton(tab.get(0));
                  break;
                case '홈페이지':
                case '포털':
                case 'e영상역사관':
                  _this._makeUploadButton(tab);
                  break;
              }
            }
          },
          _getValue: function () {
            return _this._getMetaForm().get(0).get(0).getForm().getValues();
          }
        };

        _this.items.push(_this.metaForm);
      }

      _this.buttons = [
        {
          xtype: 'aw-button',
          text: '선택',
          handler: function (b, e) {
            _this._fireOkEvent();
            //_this.close();
          }
        },
        {
          xtype: 'aw-button',
          text: '닫기',
          handler: function (b, e) {
            _this.close();
          }
        }
      ];
    },
    _selectRecord: function (select) {
      this._selected = select;
      return this._selected;
    },
    _getSelectedRecord: function () {
      return this._selected;
    },
    _fireOkEvent: function () {
      var inOut;
      if (this.isPlayer) {
        var inOut = this._getPlayer()._getValue();
      }
      var _selected = {
        player: inOut,
        content: this._getSelectedRecord()
      };

      this.fireEvent('ok', this, _selected);
      return true;
    },
    _fireLoadEvent: function (data) {
      if (this.isPlayer) {
        // var path = this._findProxyMediaPath(data);
        // if (path) {
        //   this._getPlayer()._setSrc(path);
        // }
      }
      this._selectRecord(data);
      this.fireEvent('load', this, data);
      return true;
    },
    /**
     * 윈도우 존재여부 체크
     */
    _isExistComp: function () {
      if (this.destroying || this.isDestroyed) {
        return false;
      }
      return true;
    },
    /**
     * 이지서티 검출 내역 로드
     */
    _loadEasyCertiData: function (contentId) {
      var _this = this;
      var apiUrl = '/api/v1/contents/easy-certi/' + contentId;
      Ext.Ajax.request({
        method: 'GET',
        url: apiUrl,
        callback: function (opts, success, response) {
          if (success) {
            try {
              var returnVal = Ext.decode(response.responseText);
              if (returnVal.success) {
                _this.easycertiData = returnVal.data;
                var datas = returnVal.data;
                if (datas) {
                  var dataKey = Object.keys(datas);
                  for (var i=0; i<dataKey.length; i++) {
                    if (dataKey[i] != 'id' && dataKey[i] != 'content_id') {
                      if (Ext.getCmp(dataKey[i] + '_id')) Ext.getCmp(dataKey[i] + '_id').setValue(datas[dataKey[i]]);
                    }
                  }
                }
              }
            } catch (e) {
              Ext.Msg.alert(e['name'], e['message']);
            }
          } else {
            Ext.Msg.alert('Error' + response.status, response.statusText);
          }
        }
      });

    },
    /**
     * 콘텐츠 ID 로 영상/메타 로드
     */
    _loadContent: function (contentId) {
      var _this = this;
      this.content_id = contentId;

      this._getContent(contentId);

      if (_this.isMetaForm) {
        var myMask = new Ext.LoadMask(Ext.getBody(), {
          msg: '불러오는 중입니다'
        });
        myMask.show();
        Ext.Ajax.request({
          url: '/store/get_detail_metadata.php',
          params: {
            mode: 'read',
            content_id: contentId,
            window_id: _this.id
          },
          callback: function (opts, success, response) {
            myMask.hide();
            if (success) {
              try {
                var r = Ext.decode(response.responseText);

                if (_this._isExistComp()) {
                  _this._getMetaForm().removeAll();

                  // 콘텐츠 이동버튼 숨김
                  r[6].relatedButton = true;

                  _this._getMetaForm().add(r);
                  _this._getMetaForm().doLayout();
                  _this._getMetaForm().activate(0);
                }
              } catch (e) {
                console.log(e);
                Ext.Msg.alert(e['name'], e['message']);
              }
            } else {
              Ext.Msg.alert(
                '알림',
                opts.url +
                  '<br />' +
                  response.statusText +
                  '(' +
                  response.status +
                  ')'
              );
            }
          }
        });
      }
    },
    _getContent: function (contentId) {
      var _this = this;

      if (_this.isPlayer) {
        var apiUrl = '/api/v1/contents/' + contentId + '/preview-url';
        Ext.Ajax.request({
          method: 'GET',
          url: apiUrl,
          callback: function (opts, success, response) {
            if (success) {
              try {
                var returnVal = Ext.decode(response.responseText);
                if (returnVal.success) {
                  var path = returnVal.data.srcUrl;
                  if (path && _this._isExistComp()) {
                    _this._getPlayer()._setSrc(path);
                  }
                }
              } catch (e) {
                Ext.Msg.alert(e['name'], e['message']);
              }
            } else {
              Ext.Msg.alert('Error' + response.status, response.statusText);
            }
          }
        });
      }

      var apiUrl = '/api/v1/contents/' + contentId;
      Ext.Ajax.request({
        method: 'GET',
        url: apiUrl,
        callback: function (opts, success, response) {
          if (success) {
            try {
              var returnVal = Ext.decode(response.responseText);
              if (returnVal.success) {
                if (_this._isExistComp()) {
                  _this._fireLoadEvent(returnVal.data);
                }
              }
            } catch (e) {
              Ext.Msg.alert(e['name'], e['message']);
            }
          } else {
            Ext.Msg.alert('Error' + response.status, response.statusText);
          }
        }
      });
    },
    _findProxyMediaPath: function (data) {
      var medias = data.medias;
      var proxyPath;
      var prefix = this.domainData;
      for (var i = 0; i < medias.length; i++) {
        if (medias[i].media_type == 'proxy') {
          if (
            !Ext.isEmpty(medias[i].storage) &&
            !Ext.isEmpty(medias[i].storage.virtual_path)
          ) {
            prefix = medias[i].storage.virtual_path;
          } else {
            prefix = '/data';
          }
          proxyPath = prefix + '/' + medias[i].path;
        }
      }
      return proxyPath;
    },
    _getMetaForm: function () {
      return this.get(1);
    },
    _getPlayer: function () {
      var _this = this;
      if(_this.isEasyCerti) {
        return this.get(0).get(0);
      } else {
        return this.get(0);
      }
    },
    _mediaListStore: function () {
      var _this = this;
      var store = new Ext.data.JsonStore({
        url: '/store/get_media.php',
        root: 'data',
        fields: ['media_type']
      });

      return store;
    },
    _makeUploadButton: function (tab) {
      var _this = this;
      var mediaListStore = _this._mediaListStore();

      mediaListStore.load({
        params: {
          content_id: _this.content_id
        }
      });
      mediaListStore.on('load', function (self, records, opts) {
        var mediaTypeCount = 0;
        var buttonItemId = 'attachUploadButton';

        records.find(function (record) {
          if (record.get('media_type') == 'Attach') {
            mediaTypeCount++;
          }
        });
        var fileAttachUpload = Ext.id();
        var fileAttachFakePath = Ext.id();
        var fileAttachUploadFormId = Ext.id();

        var text = '첨부파일 업로드(' + mediaTypeCount + ')';
        var fileAttachUploadForm = new Ext.form.FormPanel({
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
          items: [
            {
              xtype: 'fileuploadfield',
              hidden: true,
              id: fileAttachUpload,
              name: 'FileAttach',
              listeners: {
                fileselected: function (self, value) {
                  Ext.getCmp(fileAttachFakePath).setValue(value);
                }
              }
            },
            {
              xtype: 'compositefield',
              fieldLabel: _text('MN01045'), //'첨부 파일',
              items: [
                {
                  xtype: 'textfield',
                  id: fileAttachFakePath,
                  allowBlank: false,
                  readOnly: true,
                  flex: 1
                },
                {
                  xtype: 'button',
                  text: _text('MN02176'), //'파일선택',
                  listeners: {
                    click: function (btn, e) {
                      $(
                        '#' + Ext.getCmp(fileAttachUpload).getFileInputId()
                      ).click();
                    }
                  }
                }
              ]
            }
          ],
          buttonsAlign: 'left',
          buttons: [
            {
              text:
                '<span style="position:relative;top:1px;"><i class="fa fa-check" style="font-size:13px;"></i></span>&nbsp;' +
                _text('MN00046'), //'저장'
              scale: 'small',
              handler: function (b, e) {
                var regist_form = fileAttachUploadForm.getForm();
                if (!regist_form.isValid()) {
                  Ext.Msg.alert(_text('MN00023'), _text('MSG01006')); //알림, 첨부파일을 선택 해 주시기 바랍니다.
                  return;
                }
                //확장자 체크
                var extension_arr = [
                  'ZIP',
                  'HWP',
                  'DOC',
                  'DOCX',
                  'XML',
                  'PPTX',
                  'PPT',
                  'XLS',
                  'XLSX',
                  'PDF',
                  'JPG',
                  'JPEG',
                  'PNG',
                  'MP3',
                  'WAV',
                  'TXT'
                ];
                var upload_file = Ext.getCmp(fileAttachUpload).getValue();
                var filename_arr = upload_file.split('.');
                var extension_index = filename_arr.length - 1;
                var file_extension = filename_arr[
                  extension_index
                ].toUpperCase();
                if (extension_arr.indexOf(file_extension) === -1) {
                  Ext.Msg.alert(
                    _text('MN00023'),
                    _text('MN00309') + ' : ' + extension_arr.join(', ')
                  ); //알림, 허용 확장자 :
                  return;
                }

                regist_form.submit({
                  url: '/custom/ktv-nps/download/upload_attach.php',
                  params: {
                    content_id: _this.content_id,
                    ud_content_id: _this._selected.ud_content_id
                  },
                  success: function (form, action) {
                    var r = Ext.decode(action.response.responseText);

                    if (r.result == 'false') {
                      Ext.Msg.alert(_text('MN00023'), r.msg);
                      return;
                    }
                    Ext.getCmp(uploadWindowId).close();
                  },
                  failure: function (form, action) {
                    var r = Ext.decode(action.response.responseText);
                    Ext.Msg.alert(_text('MN00023'), r.msg);
                  }
                });
              }
            },
            {
              text:
                '<span style="position:relative;top:1px;"><i class="fa fa-close" style="font-size:13px;"></i></span>&nbsp;' +
                _text('MN00031'), //'닫기'
              scale: 'small',
              handler: function (b, e) {
                Ext.getCmp(uploadWindowId).close();
              }
            }
          ]
        });
        var button = new Ext.Button({
          text: text,
          scale: 'medium',
          itemId: buttonItemId,
          handler: function (btn, e) {
            //확장자 체크
            var extension_arr = [
              'ZIP',
              'HWP',
              'DOC',
              'DOCX',
              'XML',
              'PPTX',
              'PPT',
              'XLS',
              'XLSX',
              'PDF',
              'JPG',
              'JPEG',
              'PNG',
              'MP3',
              'WAV',
              'TXT'
            ];
            var extensionStr = extension_arr.join(',');
            var win = new Ext.Window({
              title: '업로드',
              width: 550,
              top: 50,
              height: 165,
              modal: true,
              layout: 'fit',
              items: new Ext.form.FormPanel({
                id: fileAttachUploadFormId,
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
                items: [
                  {
                    xtype: 'fileuploadfield',
                    hidden: true,
                    id: fileAttachUpload,
                    name: 'FileAttach',
                    listeners: {
                      fileselected: function (self, value) {
                        Ext.getCmp(fileAttachFakePath).setValue(value);
                      }
                    }
                  },
                  {
                    xtype: 'compositefield',
                    fieldLabel: _text('MN01045'), //'첨부 파일',
                    items: [
                      {
                        xtype: 'textfield',
                        id: fileAttachFakePath,
                        allowBlank: false,
                        readOnly: true,
                        flex: 1
                      },
                      {
                        xtype: 'button',
                        text: _text('MN02176'), //'파일선택',
                        listeners: {
                          click: function (btn, e) {
                            $(
                              '#' +
                                Ext.getCmp(fileAttachUpload).getFileInputId()
                            ).click();
                          }
                        }
                      }
                    ]
                  },
                  {
                    xtype:'fieldset',
                    title:'허용확장자',
                    collapsible:false,
                    border:true,
                    items:[{
                        xtype:'displayfield',
                        hideLabel:true,
                        value:extensionStr
                    }]
                }
                ],
                buttonsAlign: 'left',
                buttons: [
                  {
                    text:
                      '<span style="position:relative;top:1px;"><i class="fa fa-check" style="font-size:13px;"></i></span>&nbsp;' +
                      _text('MN00046'), //'저장'
                    scale: 'small',
                    handler: function (b, e) {
                      var regist_form = Ext.getCmp(
                        fileAttachUploadFormId
                      ).getForm();
                      if (!regist_form.isValid()) {
                        Ext.Msg.alert(_text('MN00023'), _text('MSG01006')); //알림, 첨부파일을 선택 해 주시기 바랍니다.
                        return;
                      }
                      // //확장자 체크
                      // var extension_arr = [
                      //   'ZIP',
                      //   'HWP',
                      //   'DOC',
                      //   'DOCX',
                      //   'XML',
                      //   'PPTX',
                      //   'PPT',
                      //   'XLS',
                      //   'XLSX',
                      //   'PDF',
                      //   'JPG',
                      //   'JPEG',
                      //   'PNG',
                      //   'MP3',
                      //   'WAV',
                      //   'TXT'
                      // ];
                      var upload_file = Ext.getCmp(fileAttachUpload).getValue();
                      var filename_arr = upload_file.split('.');
                      var extension_index = filename_arr.length - 1;
                      var file_extension = filename_arr[
                        extension_index
                      ].toUpperCase();
                      if (extension_arr.indexOf(file_extension) === -1) {
                        Ext.Msg.alert(
                          _text('MN00023'),
                          _text('MN00309') + ' : ' + extension_arr.join(', ')
                        ); //알림, 허용 확장자 :
                        return;
                      }

                      regist_form.submit({
                        url: '/custom/ktv-nps/download/upload_attach.php',
                        params: {
                          content_id: _this.content_id,
                          ud_content_id: _this._selected.ud_content_id
                        },
                        success: function (form, action) {
                          var r = Ext.decode(action.response.responseText);

                          if (r.result == 'false') {
                            Ext.Msg.alert(_text('MN00023'), r.msg);
                            return;
                          }
                          win.close();
                        },
                        failure: function (form, action) {
                          var r = Ext.decode(action.response.responseText);
                          Ext.Msg.alert(_text('MN00023'), r.msg);
                        }
                      });
                    }
                  },
                  {
                    text:
                      '<span style="position:relative;top:1px;"><i class="fa fa-close" style="font-size:13px;"></i></span>&nbsp;' +
                      _text('MN00031'), //'닫기'
                    scale: 'small',
                    handler: function (b, e) {
                      win.close();
                    }
                  }
                ]
              })
            }).show();
          }
        });

        var oldButton = tab.buttons.find(function (item) {
          return item.itemId == buttonItemId;
        });

        var oldButtonIndex = tab.buttons.indexOf(oldButton);

        if (oldButtonIndex > -1) {
          tab.buttons[oldButtonIndex].setText(text);
        } else {
          tab.addButton(button);
        }
        tab.doLayout();
      });
    }
  });
  Ext.reg('c-content-detail-window', Custom.ContentDetailWindow);
})();
