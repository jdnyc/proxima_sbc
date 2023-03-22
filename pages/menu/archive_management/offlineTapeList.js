(function () {
  Ext.ns('Ariel.archiveManagement');
  Ariel.archiveManagement.offlineTapeList = Ext.extend(Ext.grid.GridPanel, {
    title: '<span class="user_span"><span class="icon_title"><i class=""></i></span><span class="main_title_header">' + '아카이브 테이프 관리' + '</span></span>',
    loadMask: true,
    stripeRows: true,
    frame: false,
    viewConfig: {
      emptyText: '목록이 없습니다.'
    },
    cls: 'grid_title_customize proxima_customize',
    listeners: {
      afterrender: function (self) {
        self.getStore().load({
        });
      }
    },
    initComponent: function () {

      this._initialize();
      Ariel.archiveManagement.offlineTapeList.superclass.initComponent.call(this);
    },
    _initialize: function () {
      var _this = this;
      var sm = new Ext.grid.RowSelectionModel({
        singleSelect: false,
        listeners: {
        }
      });
      this.store = new Ext.data.JsonStore({
        remoteSort: true,
        restful: true,
        proxy: new Ext.data.HttpProxy({
          method: 'GET',
          url: '/api/v1/tape',
          type: 'rest'
        }),
        remoteSort: true,
        totalProperty: 'total',
        root: 'data',
        fields: [
          'ta_id',
          'ta_barcode',
          'ta_acs',
          'ta_lsm',
          'ta_media_type_tp_id',
          'ta_set_id',
          'ta_is_online',
          'ta_protected',
          'ta_enable_for_writing',
          'ta_to_be_cleared',
          'ta_enable_for_repack',
          'ta_group_tg_id',
          'ta_remaining_size',
          'ta_filling_ratio',
          'ta_fragmentation_ratio',
          'ta_block_size',
          'ta_last_written_block',
          'ta_format',
          'ta_eject_comment',
          'ta_last_archive_date',
          'ta_first_mount_date',
          'ta_last_retention_date',
          'ta_first_insertion_date',
          'ta_export_tape',
          'created_at',
          'updated_at',
          'deleted_at',
          'id',
          'tape_se',
          'cstdy_lc',
          'disprs_at',
          'ta_total_size',
          'ta_version',
        ]
      });
      this.sm = sm;

      this.cm = new Ext.grid.ColumnModel({
        defaults: {
          align: 'center',
          menuDisabled: true,
          sortable: false
        },
        columns: [
          new Ext.grid.RowNumberer({ width: 30 }),
          //sm,
          {
            header: '구분', dataIndex: 'tape_se', renderer: function (value) {
              if (Ext.isEmpty(value)) {
                return value;
              }
              return value.toUpperCase();
            }
          },
          { header: '바코드', dataIndex: 'ta_barcode', sortable: true },
          { header: '소산여부', dataIndex: 'disprs_at', sortable: true },
          { header: '보관위치', dataIndex: 'cstdy_lc', width: 400 },

          { header: '전체용량', dataIndex: 'ta_total_size' },
          //{ header: '가용량', dataIndex: 'cstdy_lc' },
          //{ header: 'Tape ID', dataIndex: 'ta_id' },
          {
            header: '가용량', dataIndex: 'ta_remaining_size', sortable: true, width: 150, renderer: function (value) {

              // if (!Ext.isEmpty(value)) {
              //   var MBValue = value / 1024;
              //   return MBValue.toFixed(2) + ' MB';
              // }
              return value;
            }
          },
          { header: '사용률', dataIndex: 'ta_filling_ratio' },
          {
            header: 'LTO버전', dataIndex: 'ta_version', renderer: function (value) {
              if (value == 'LTO-2.5T') {
                return 'LTO6(2.5T)';
              } if (value == 'LTO-12.8T') {
                return 'LTO8(12.8T)';
              }
              return value;
            }
          },

          //{ header: 'is_online', dataIndex: 'ta_is_online' },
          {
            header: '그룹', dataIndex: 'ta_set_id', sortable: true, renderer: function (value) {
              if (value == '2') {
                return 'MAIN';
              } if (value == '3') {
                return 'BACKUP_DR';
              } if (value == '4') {
                return 'BACKUP';
              }
              return value;
            }
          },
          //{ header: 'eject_comment', dataIndex: 'ta_eject_comment' },
          //{ header: 'export_tape', dataIndex: 'ta_export_tape' },
          { header: '업데이트일시', dataIndex: 'updated_at', width: 200 }
        ]
      });

      this.searchCombo1 = new Ext.form.TextField({
        width: 70,
        name: 'tape_se',
        value: 'DIVA'
      });
      this.searchCombo2 = new Ext.form.ComboBox({
        width: 70,
        triggerAction: 'all',
        editable: false,
        name: 'disprs_at',
        mode: 'local',
        store: [
          ['', '전체'],
          ['Y', 'Y'],
          ['N', 'N']
        ],
        value: '',
        listeners: {
          afterrender: function (self) {
          },
          select: function (self, record, index) {
          }
        }
      });

      this.searchCombo3 = new Ext.form.ComboBox({
        width: 90,
        triggerAction: 'all',
        editable: false,
        name: 'ta_set_id',
        mode: 'local',
        store: [
          ['', '전체'],
          ['2', 'MAIN'],
          ['3', 'BACKUP_DR'],
          ['4', 'BACKUP']
        ],
        value: '',
        listeners: {
          afterrender: function (self) {
          },
          select: function (self, record, index) {
          }
        }
      });

      this.searchText = new Ext.form.TextField({
        width: 130,
        name: 'search',
        listeners: {
          specialkey: function (f, e) {
            if (e.getKey() === e.ENTER) {
              _this._search();
            };
          },
          afterrender: function (self) {
          },
          show: function (self) {
          }
        }
      });
      this.searchBtn = {
        xtype: 'aw-button',
        iCls: 'fa fa-search',
        text: '조회',
        handler: function (self) {
          _this._search();
        }
      };

      this.tbar = [
        {

          text: '새로고침',
          xtype: 'aw-button',
          iCls: 'fa fa-refresh',
          handler: function (self) {
            _this.getStore().reload();
          }
        }, {
          xtype: 'aw-button',
          iCls: 'fa fa-plus',
          text: '추가',
          handler: function (self) {

            _this._getAddWin();

          }
        }, {
          xtype: 'aw-button',
          iCls: 'fa fa-edit',
          text: '수정',
          handler: function (self) {
            var sels = _this.getSelectionModel().getSelections();
            if (Ext.isEmpty(sels)) {
              Ext.Msg.alert('알림', '목록을 선택해주세요.');
              return;
            }
            _this._getEditWin(sels[0].data);
          }
        }, {
          xtype: 'aw-button',
          iCls: 'fa fa-times-circle',
          text: '삭제',
          handler: function (self) {

            var sels = _this.getSelectionModel().getSelections();
            if (Ext.isEmpty(sels)) {
              Ext.Msg.alert('알림', '목록을 선택해주세요.');
              return;
            }

            var params = sels[0].data;

            var url = '/api/v1/tape/delete';
            if (!Ext.isEmpty(params.ta_id)) {
              Ext.Msg.alert('알림', 'DIVA DTL은 삭제 할 수 없습니다');
              return;
            }

            Ext.Msg.show({
              title: '알림',
              msg: '삭제하시겠습니까?',
              buttons: Ext.Msg.OKCANCEL,
              fn: function (btnId, text, opts) {
                if (btnId == 'ok') {
                  Ext.Ajax.request({
                    url: url,
                    params: params,
                    callback: function (options, success, response) {
                      if (success) {
                        try {
                          Ext.Msg.alert('알림', '삭제되었습니다.');
                          _this.getStore().reload();
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
                }
              }
            });
          }
        }, { xtype: 'tbspacer', width: 20 },
        {
          xtype: 'aw-button',
          iCls: 'fa fa-search',
          text: '미디어목록 조회',
          handler: function (self) {
            var sel = _this.getSelectionModel().getSelected();
            if (Ext.isEmpty(sel)) {
              Ext.Msg.alert('알림', '목록을 선택해주세요.');
              return;
            }
            _this._getMedias(sel.data);
          }
        }, { xtype: 'tbspacer', width: 20 },
        {
          xtype: 'aw-button',
          iCls: 'fa fa-check',
          text: 'DIVA DB 즉시 동기화',
          handler: function (self) {
            Ext.Msg.show({
              title: '알림',
              msg: '동기화 하시겠습니까?',
              buttons: Ext.Msg.OKCANCEL,
              fn: function (btnId, text, opts) {
                if (btnId == 'ok') {
                  var waitMsg = Ext.Msg.wait('처리 중입니다.', '처리중...');
                  var url = '/api/v1/tape/sync';
                  Ext.Ajax.request({
                    url: url,
                    method: 'GET',
                    timeout: 1200000,
                    callback: function (options, success, response) {
                      waitMsg.hide();
                      if (success) {
                        try {
                          Ext.Msg.alert('알림', '동기화 완료');
                          _this.getStore().reload();
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
                }
              }
            });
          }
        }
        , '->',
        '구분:',
        this.searchCombo1,
        ' ',
        '그룹:',
        this.searchCombo3,
        ' ',
        '소산여부:',
        this.searchCombo2,
        ' ',
        '바코드/미디어ID:',
        this.searchText,
        this.searchBtn
      ]

      this.bbar = {
        xtype: 'paging',
        pageSize: 30,
        displayInfo: true,
        store: this.store
      };

      this._search = function () {
        var searchParams = this._getParams();
        //console.log(searchParams);
        this.getStore().load({
          params: searchParams
        })
      };

      this._getParams = function () {
        var toolbar = this.getTopToolbar();
        var returnVal = {};
        toolbar.items.each(function (item) {
          if (item.name == 'tape_se' || item.name == 'disprs_at' || item.name == 'search' || item.name == 'ta_set_id') {
            returnVal[item.name] = item.getValue();
          }
        });

        return returnVal;

      }

      this._addWin = new Ext.Window({
        title: '추가',
        closeAction: 'hide',
        width: Ext.getBody().getViewSize().width * 0.4,
        height: Ext.getBody().getViewSize().height * 0.4,
        layout: 'fit',
        modal: true,
        border: false,
        items: [{
          xtype: 'form',
          padding: 5,
          defaults: {
            anchor: '95%'
          },
          items: [{
            xtype: 'hidden',
            name: 'id'
          }, {
            xtype: 'hidden',
            name: 'ta_id'
          }, {
            fieldLabel: '구분',
            xtype: 'textfield',
            name: 'tape_se'
          },
          {
            fieldLabel: '바코드',
            xtype: 'textfield',
            name: 'ta_barcode'
          }, {
            fieldLabel: '소산여부',
            xtype: 'combo',
            triggerAction: 'all',
            editable: false,
            name: 'disprs_at',
            mode: 'local',
            store: [
              ['Y', 'Y'],
              ['N', 'N']
            ],
            value: 'N'
          },
          {
            fieldLabel: '보관위치',
            xtype: 'textarea',
            name: 'cstdy_lc'
          }
          ]
        }],
        buttonAlign: 'left',
        buttons: [{
          xtype: 'aw-button',
          iCls: 'fa fa-check',
          text: '수정이력',
          handler: function (self, e) {
            var params = _this._addWin.get(0).getForm().getValues();

            var win_tapeLoc = new Ext.Window({
              title: '수정이력',
              width: 650,
              modal: true,
              height: 500,
              miniwin: true,
              resizable: false,
              buttonAlign: 'center',
              items: [new Ariel.archiveManagement.offlineTapeLogList({ _targetId: params.id, height: 400 })],
              fbar:
                [{
                  xtype: 'button',
                  scale: 'medium',
                  text: '<span style=\"position:relative;top:1px;\"><i class=\"fa fa-close\" style=\"font-size:13px;\"></i></span>&nbsp;' + _text('MN00031'),
                  //text: '" . _text('MN00031') . "',
                  handler: function (b, e) {
                    win_tapeLoc.close();
                  }
                }]
            }).show();
          }
        }, '->', {
          xtype: 'aw-button',
          iCls: 'fa fa-edit',
          text: '저장',
          handler: function (self, e) {
            var params = _this._addWin.get(0).getForm().getValues();

            if (Ext.isEmpty(params.id)) {
              var url = '/api/v1/tape/create';
            } else {
              var url = '/api/v1/tape/update';
            }
            Ext.Ajax.request({
              url: url,
              params: params,
              callback: function (options, success, response) {
                if (success) {
                  try {

                    Ext.Msg.alert('알림', '저장 되었습니다.');
                    _this.getStore().reload();
                    _this._addWin.hide();
                  } catch (e) {
                    Ext.Msg.alert(e.name, e.message);
                  }
                } else {
                  var res = Ext.decode(response.responseText);
                  var errorMsg = res.msg;
                  Ext.Msg.alert('알림', errorMsg);
                  _this._addWin.hide();
                }
              }
            });
          }
        }, {
          xtype: 'aw-button',
          iCls: 'fa fa-close',
          text: '닫기',
          handler: function (btn, e) {
            btn.ownerCt.ownerCt.hide();
            //btn.ownerCt._addWin.close();
          }
        }]
      });

      this._getMedias = function (selectRow) {
        //console.log(selectRow);
        var barcode = selectRow.ta_barcode;
        var getUrl = '/api/v1/tape/' + barcode + '/medias';
        new Ext.Window({
          title: '미디어 조회',
          //closeAction: 'hide',
          width: Ext.getBody().getViewSize().width * 0.4,
          height: Ext.getBody().getViewSize().height * 0.4,
          layout: 'fit',
          modal: true,
          border: false,
          items: [{
            xtype: 'grid',
            title: barcode,
            loadMask: true,
            store: new Ext.data.JsonStore({
              proxy: new Ext.data.HttpProxy({
                method: 'GET',
                url: getUrl,
                type: 'rest'
              }),
              restful: true,
              autoLoad: true,
              remoteSort: true,
              totalProperty: 'total',
              root: 'data',
              fields: [
                'of_id',
                'of_object_name',
                'of_category',
                'of_instance_order_number',
                'of_request_type',
                'of_request_date',
                'of_barcode',
                'of_group_name',
                'title',
                'filesize',
                'status',
              ]
            }),
            viewConfig: {
              emptyText: '조회된 정보가 없습니다',
              forceFit: true
            },
            colModel: new Ext.grid.ColumnModel({
              columns: [
                new Ext.grid.RowNumberer({ width: 35 }),
                { header: '미디어ID', dataIndex: 'of_object_name' },
                { header: '제목', dataIndex: 'title' },
                { header: '파일사이즈', dataIndex: 'filesize' },
                { header: '상태', dataIndex: 'status' }
              ]
            }),
          }],
          buttons: [{
            xtype: 'aw-button',
            iCls: 'fa fa-close',
            text: '닫기',
            handler: function (btn, e) {
              //btn.ownerCt.ownerCt.hide();
              btn.ownerCt.ownerCt.close();
            }
          }]
        }).show();
      };

      this._getAddWin = function () {
        var form = this._addWin.get(0).getForm();
        this._addWin.get(0).getForm().reset();
        this._addWin.setTitle('추가');
        form.reset();
        if (form.findField('ta_barcode')) {
          form.findField('ta_barcode').setReadOnly(false);
          form.findField('ta_barcode').reset();
        }
        if (form.findField('tape_se')) {
          form.findField('tape_se').setReadOnly(false);
          form.findField('tape_se').reset();
        }
        if (form.findField('disprs_at')) {
          form.findField('disprs_at').setReadOnly(false);
          form.findField('disprs_at').reset();
        }
        this._addWin.show();
      }


      this._getEditWin = function (values) {
        var form = this._addWin.get(0).getForm();
        this._addWin.get(0).getForm().reset();
        this._addWin.setTitle('수정');
        this._addWin.show();
        //form.reset();
        form.setValues(values);
        if (!Ext.isEmpty(values.ta_id)) {
          if (form.findField('ta_barcode')) {
            form.findField('ta_barcode').setReadOnly(true);
          }
          if (form.findField('tape_se')) {
            form.findField('tape_se').setReadOnly(true);
          }
          if (form.findField('disprs_at')) {
            form.findField('disprs_at').setReadOnly(true);
          }
        } else {
          if (form.findField('ta_barcode')) {
            form.findField('ta_barcode').setReadOnly(false);
          }
          if (form.findField('tape_se')) {
            form.findField('tape_se').setReadOnly(false);
          }
          if (form.findField('disprs_at')) {
            form.findField('disprs_at').setReadOnly(false);
          }
        }
        this._addWin.show();
      }

      this.listeners = {
        afterrender: function (self) {
          self.getStore().load({
          });
        },
        rowclick: function (self, idx, n, e) {

        },
        rowdblclick: function (self, idx, e) {

          var sels = _this.getSelectionModel().getSelections();
          if (!Ext.isEmpty(sels)) {
            _this._getEditWin(sels[0].data);
          }
        }
      };
    }
  });
  return new Ariel.archiveManagement.offlineTapeList();
})()