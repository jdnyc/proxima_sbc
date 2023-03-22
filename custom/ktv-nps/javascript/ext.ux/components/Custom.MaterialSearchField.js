(function () {

  Ext.ns("Custom");
  Custom.MaterialSearchField = Ext.extend(Ext.form.CompositeField, {
    // properties
    _textField: null,//관리번호 필드
    _searchButton: null,//조회 버튼
    _grid: null,
    _win: null,
    items: [],
    value: null,
    values: null,
    constructor: function (config) {
      Ext.apply(this, {}, config || {});
      Custom.MaterialSearchField.superclass.constructor.call(this);
    },
    initComponent: function () {

      this._initItems();
      Custom.MaterialSearchField.superclass.initComponent.call(this);

    },
    listeners: {
      pgmselect: function (self, record) {
        var _this = self;
        var metaForm = null;
        var metaBaseForm = null;
        _this.values = record.data;

        // console.log('pgmselect', self);
        // console.log('pgmselect', record);

        if (!Ext.isEmpty(Ext.getCmp(_this.telecineDtaTyId))) {
          Ext.getCmp(_this.telecineDtaTyId).getValue();
          _this.values.telecine_type = Ext.getCmp(_this.telecineDtaTyId).getValue();
        };
        if (_this.ownerCt && _this.ownerCt.getXType() == "form") {
          metaForm = self.ownerCt.getForm();
          metaBaseForm = self.ownerCt;
        } else if (
          _this.ownerCt.ownerCt &&
          _this.ownerCt.ownerCt.getXType() == "form"
        ) {
          metaForm = _this.ownerCt.ownerCt.getForm();
          metaBaseForm = _this.ownerCt.ownerCt;
        } else {
          // _this._codeField.setValue(record.procode);
          // _this._textField.setValue(record.program_nm);
        }

        if (!Ext.isEmpty(metaForm)) {

          metaForm.setValues(record.data);
          var metaValues = metaForm.getValues();

          if (!Ext.isEmpty(record.get('c_category_id'))) {
            metaForm.findField('c_category_id').setValue(record.get('c_category_id'));
          }
          if (!Ext.isEmpty(record.get('program_nm'))) {
            if (!Ext.isEmpty(metaForm.findField('progrm_nm'))) {
              metaForm.findField('progrm_nm').setValue(record.get('program_nm'));
              metaForm.findField('progrm_nm')._codeField.setValue(record.get('procode'));
              metaForm.findField('progrm_nm')._textField.setValue(record.get('program_nm'));
            }
          }
          // if (!Ext.isEmpty(record.get('brdcst_de'))) {
          //   if (!Ext.isEmpty(metaForm.findField('brdcst_de'))) {
          //     metaForm.findField('brdcst_de').setValue(record.get('brdcst_de'));
          //   }
          // }
          var tapeNo = record.get('tape_no')
          var idNo = record.get('id_no');
          var canNo = record.get('can_no');
          var telecineCombo = Ext.getCmp(_this.telecineDtaTyId);
          if (!Ext.isEmpty(telecineCombo)) {
            if (!Ext.isEmpty(metaValues.k_content_id)) {
              _this._saveShotList({
                tape_no: tapeNo,
                content_id: metaValues.k_content_id,
                id_no: record.get('id_no'),
                telecine_type: _this.values.telecine_type
              });
            } else {
              if (Ext.isEmpty(metaForm.findField('telecine_type'))) {
                metaBaseForm.add(new Ext.form.TextField({
                  value: telecineCombo.getValue(),
                  name: 'telecine_type',
                  hidden: true
                }));
              } else {
                metaForm.findField('telecine_type').setValue(telecineCombo.getValue());
              }
              if (Ext.isEmpty(metaForm.findField('tape_no'))) {
                metaBaseForm.add(new Ext.form.TextField({
                  value: tapeNo,
                  name: 'tape_no',
                  hidden: true
                }));
              } else {
                metaForm.findField('tape_no').setValue(tapeNo);
              }
              if (Ext.isEmpty(metaForm.findField('id_no'))) {
                metaBaseForm.add(new Ext.form.TextField({
                  value: idNo,
                  name: 'id_no',
                  hidden: true
                }));
              } else {
                metaForm.findField('id_no').setValue(idNo);
              }
              metaBaseForm.doLayout();
            }
          }
          // if (metaValues.k_content_id) {
          //   var tapeNo = record.get('tape_no')
          //   var idNo = record.get('id_no');
          //   var canNo = record.get('can_no');
          //   var telecineCombo = Ext.getCmp(_this.telecineDtaTyId);

          //   if (!Ext.isEmpty(telecineCombo)) {
          //     if (telecineCombo.getValue() == 'BROADMAS') {

          //       if (!Ext.isEmpty(tapeNo) && !Ext.isEmpty(record.get('id_no'))) {
          //         _this._saveShotList({
          //           tape_no: tapeNo,
          //           content_id: metaValues.k_content_id,
          //           id_no: record.get('id_no')
          //         })

          //       }
          //     } else if ((telecineCombo.getValue() == 'STAPEMED') ||
          //       (telecineCombo.getValue() == 'SFILMMED') ||
          //       (telecineCombo.getValue() == 'NEWSCONMED')
          //     ) {

          //       if (!Ext.isEmpty(tapeNo)) {
          //         _this._saveShotList({
          //           tape_no: tapeNo,
          //           content_id: metaValues.k_content_id
          //         });
          //       }
          //     } else if ((telecineCombo.getValue() == 'MOVIEMAS') ||
          //       (telecineCombo.getValue() == 'NEWSMAS') ||
          //       (telecineCombo.getValue() == 'KTV_NEWSMAS')
          //     ) {

          //       if (!Ext.isEmpty(idNo)) {
          //         _this._saveShotList({
          //           id_no: idNo,
          //           content_id: metaValues.k_content_id
          //         });
          //       }
          //     }
          //   }

          // }

        }
        _this._win.close();
      }



      // Ext.Msg.show({
      //   title: '알림',
      //   msg: '해당 관리번호로 메타등록 하시겠습니까?',
      //   buttons: Ext.Msg.OKCANCEL,
      //   fn: function (btnId) {
      //     if (btnId == 'ok') {
      //       console.log(record, 'record');
      //       // _this._win.close();
      //     }
      //   }
      // })
    },
    _saveShotList: function (obj) {
      var _this = this;
      var mask = new Ext.LoadMask(Ext.getBody());

      var tapeNo = obj.tape_no;
      var idNo = obj.id_no;
      var contentId = obj.content_id;
      mask.show();
      Ext.Ajax.request({
        url: '/api/v1/materials-scenes',
        method: 'POST',
        params: obj,
        callback: function (opts, success, response) {
          if (success) {
            try {
              var r = Ext.decode(response.responseText);


              if (!Ext.isEmpty(Ext.getCmp('timeline-grid'))) {

                Ext.getCmp('timeline-grid').loadMask = true;
                Ext.getCmp('timeline-grid').initEvents();
                Ext.getCmp('timeline-grid').getStore().reload();
              }
              mask.hide();

            } catch (e) {
              Ext.Msg.alert(e['name'], e['message']);
            }
          } else {
            Ext.Msg.alert(_text('MN00022'), opts.url + '<br />' + response.statusText + '(' + response.status + ')');
          }
        }
      });
    },
    setValue: function (v) {
      // //재정의
      this.value = v;
      if (this.rendered) {
        this.el.dom.value = Ext.isEmpty(v) ? "" : v;
        this.validate();
      }

      this._textField.setValue(v);


      return this;
    },
    getValue: function () {

      return this._textField.getValue();
    },
    getValues: function () {



      return this.values;
    },
    _makeGrid: function () {
      var _this = this;
      _this.telecineDtaTyId = Ext.id();
      _this.manageNoId = Ext.id();
      _this.gridStore = new Ext.data.JsonStore({
        root: 'data',
        restful: true,
        proxy: new Ext.data.HttpProxy({
          method: 'GET',
          url: '/api/v1/materials',
        }),
        fields: [
          'tape_no',
          'procode',
          'subtitle',
          'broaddate',
          'program_nm',
          'progrm_code',
          'progrm_nm',
          'scenecontent',
          'vol_no',
          'manage_no',
          'hono',
          'subtl',
          'scenecontents',
          'id_no',
          'brdcst_de',
          'med',
          'title',
          'tape_hold_at',
          'size',
          'color',
          'clor',
          'prod_pd_nm',
          'shooting_de',
          'shootingdate',
          'hono',
          'tape_knd',
          'tape_hold_at',
          'tape_mg',
          'tape_hold_at',
          'vido_jrnlst',
          'telecine_ty_se'

        ],
        listeners: {
          beforeload: function (self, opts) {
            _this._grid.settingColumnsByTelecineType(Ext.getCmp(_this.telecineDtaTyId).getValue());
            self.baseParams = {
              limit: 50,
              start: 0,
              telecine_type: Ext.getCmp(_this.telecineDtaTyId).getValue(),
              tape_no: Ext.getCmp(_this.manageNoId).getValue()
            };
          }
        }
      });



      this._grid = new Ext.grid.GridPanel({
        width: _this.width,
        height: _this.height,
        stripeRows: true,
        loadMask: true,
        frame: false,
        layout: 'fit',
        viewConfig: {
          emptyText: '목록이 없습니다.',
          border: false
        },
        store: _this.gridStore,
        colModel: new Ext.grid.ColumnModel({
          defaults: {
            hidden: true,
            menuDisabled: true
          },
          columns: [
            { header: '', dataIndex: 'tape_no' },
            { header: '', dataIndex: 'manage_no' },
            { header: '', dataIndex: 'program_nm' },
            { header: '', dataIndex: 'procode' },
            { header: '', dataIndex: 'vol_no' },
            // { header: '회차', dataIndex: 'tme_no' },
            { header: '', dataIndex: 'title' },
            { header: '', dataIndex: 'subtitle' },
            { header: '', dataIndex: 'tape_hold_at' },
            { header: '', dataIndex: 'med' },
            { header: '', dataIndex: 'size' },
            { header: '', dataIndex: 'prod_pd_nm' },
            { header: '', dataIndex: 'shootingdate' },
            { header: '', dataIndex: 'color' },
            { header: '', dataIndex: 'id_no' },
            // { header: '', dataIndex: 'title' },

            // { header: '매체' },
            // { header: '구분' },
            // { header: '분류', dataIndex: 'thema_cl' },
            // { header: '규격', dataIndex: 'stndrd' },
            // { header: '제목', dataIndex: 'title' },
            // { header: '촬영일', dataIndex: 'shooting_de' },
            // { header: '인수일' },
            // { header: '촬영자', dataIndex: 'shooting_dirctr' },
            { header: '', dataIndex: 'broaddate' },
            // { header: '제작구분', dataIndex: 'prod_se' }
            { header: '', dataIndex: 'scenecontent' }
          ]
        }),
        bbar: new Ext.PagingToolbar({
          store: _this.gridStore,
          pageSize: 50
        }),
        tbar: [{
          xtype: 'combo',
          flex: 2,
          id: _this.telecineDtaTyId,
          // displayField: 'name',
          // valueField: 'value',
          typeAhead: true,
          triggerAction: 'all',
          lazyRender: true,
          mode: 'local',
          editable: false,
          displayField: "code_itm_nm",
          // valueField: "code_itm_code",
          valueField: "code_itm_code",
          hiddenValue: "code_itm_code",
          typeAhead: true,
          triggerAction: "all",
          value: '',
          width: 150,
          store: new Ext.data.JsonStore({
            restful: true,
            proxy: new Ext.data.HttpProxy({
              method: "GET",
              url: '/api/v1/open/data-dic-code-sets/' + 'TELECINE_DTA_TY' + '/code-items',
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
                // var comboRecord = Ext.data.Record.create([
                //   { name: "code_itm_code" },
                //   { name: "code_itm_nm" }
                // ]);
                // var allComboMenu = {
                //   code_itm_code: "All",
                //   code_itm_nm: "전체"
                // };
                // var addComboMenu = new comboRecord(allComboMenu);
                // store.insert(0, addComboMenu);
                // var firstValueCheck = self.valueField;

                // var firstValue;
                // if (Ext.isEmpty(firstValueCheck)) {
                //   firstValue = store.data.items[0].data[firstValueCheck];
                // } else {
                //   // firstValue = 'All';
                //   firstValue = '전체';
                // }
                _this._grid.settingColumnsByTelecineType(r[0].get('code_itm_code'));
                Ext.getCmp(_this.telecineDtaTyId).setValue(r[0].get('code_itm_code'));

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

          }),
          listeners: {
            beforerender: function (self) {
              // self.store = _this._jsonStoreByCode('TELECINE_DTA_TY', self);
            },
            change: function (slef, newValue, oldValue) {
              // var gridCm = _this._grid.getColumnModel();
              // _this._grid.settingColumnsByTelecineType(newValue);

            },
            afterrender: function (self) {
              // self.resizeEl.setWidth(125);

              self.getStore().load({
                params: {
                  is_code: 1
                }
              });
            }
          }
        }, ' ', '관리번호 :', {
          xtype: 'textfield',
          id: _this.manageNoId,
          enableKeyEvents: true,
          listeners: {
            keyup: function (self, e) {
              // console.log(e);
              // // ENTER key event
              if (e.getKey() == e.ENTER) {
                // console.log(_this.gridStore);
                _this.gridStore.load();

              }
            }
          }
        }, {
          text: '조회',
          width: "30",
          xtype: 'aw-button',
          scale: 'small',
          iCls: 'fa fa-search',
          handler: function (btn) {
            _this.gridStore.load();
          }
        }],
        buttons: [
          {
            text: '선택',
            handler: function (btn) {

              _this._findBisProgram();
              // _this._win.close();
            }
          }, {
            text: '닫기',
            handler: function (btn) {
              _this._win.close();
            }
          }
        ],
        customColumns: {
          BROADMAS: {
            tape_no: '관리번호',
            procode: '프로그램',
            vol_no: '수록횟수',
            program_nm: '프로그램명',
            subtitle: '부제',
            broaddate: '본방일'//,
            //scenecontent: '장면내용'
          },
          STAPEMED: {
            tape_no: '테잎번호',
            procode: '프로그램',
            vol_no: '수록횟수',
            program_nm: '프로그램명',
            subtitle: '부제',
            broaddate: '본방일'//,
            //scenecontent: '장면내용'
          },
          NEWSCONMED: {
            tape_no: '관리번호',
            subtitle: '부제',
            // subtitle: '제목',
            tape_hold_at: '테이프보유여부',
            med: '테이프종류',
            size: '테이프 크기'
          },
          NEWSMAS: {
            tape_no: '관리번호',
            tape_hold_at: '테이프보유여부',
            med: '테이프종류',
            size: '테이프크기',
            prod_pd_nm: '제작PD명',
            shootingdate: '촬영일자',
            id_no: '호수'
          },
          KTV_NEWSMAS: {
            tape_no: '관리번호',
            tape_hold_at: '테이프보유여부',
            med: '테이프종류',
            size: '테이프크기',
            prod_pd_nm: '제작PD명',
            shootingdate: '촬영일자',
            color: '색채',
            id_no: '호수'
          },
          MOVIEMAS: {
            tape_no: '관리번호',
            tape_hold_at: '테이프보유여부',
            med: '테이프종류',
            size: '테이프크기',
            prod_pd_nm: '제작PD명',
            shootingdate: '촬영일자',
            color: '색채',
            id_no: '호수',
            title: '제목'
          },
          SFILMMED: {
            manage_no: '관리번호',
            tape_hold_at: '테이프보유여부',
            med: '테이프종류',
            size: '테이프크기',
            prod_pd_nm: '제작PD명',
            shootingdate: '촬영일자',
            color: '색채',
            title: '제목'
          }
        },
        settingColumnsByTelecineType: function (telecineType) {
          var cm = _this._grid.getColumnModel();
          var columns = cm.columns;
          var customColumns = _this._grid.customColumns;
          for (i = 0; i < columns.length; i++) {
            var column = columns[i];
            if (column.dataIndex in customColumns[telecineType]) {
              var header = customColumns[telecineType][column.dataIndex];
              cm.setColumnHeader(i, header);
              cm.setHidden(i, false);
            } else {
              cm.setHidden(i, true);
            };
          }
          _this._grid.doLayout();
        }


      });
    },
    _makeSearchButton: function () {
      var _this = this;

      _this._searchButton = {
        text: '자료조회',
        width: "120",
        xtype: 'aw-button',
        scale: 'small',
        iCls: 'fa fa-search',
        handler: function () {
          _this._makeGrid();
          _this._win = new Ext.Window({
            width: 450,
            height: 450,
            title: '자료조회',
            layout: 'fit',
            items: _this._grid,
            modal: true,
            listeners: {
              close: function (self) {
              }
            }
          }).show();
        }
      };

      _this.items[1] = _this._searchButton;
    },
    _makeTextField: function () {
      var _this = this;
      this._textField = new Ext.form.TextField({
        name: 'manage_no',
        readOnly: true,
        enableKeyEvents: true,
        flex: 1,
        listeners: {
          keyup: function (self, e) {
            // console.log(e);
            // // ENTER key event
            if (e.getKey() == e.ENTER) {
              // console.log(_this.gridStore);
              // _this.gridStore.load();

            }
          }
        }
      });

      this.items[0] = this._textField;

    },
    _findBisProgram: function () {
      var _this = this;
      var sm = _this._grid.getSelectionModel();
      if (!sm.hasSelection()) {
        return Ext.Msg.alert('알림', '조회 목록을 선택해 주세요.');
      }
      var record = sm.getSelected();

      var pgmId = record.get('procode');
      if (!Ext.isEmpty(pgmId)) {
        Ext.Ajax.request({
          url: '/api/v1/open/bis-programs/' + pgmId,
          method: 'GET',
          callback: function (opts, success, response) {
            if (success) {
              try {
                var pr = Ext.decode(response.responseText);

                if (pr.success) {

                  if (!Ext.isEmpty(pr.data)) {
                    for (var pgmProperty in pr.data) {
                      if (Ext.isEmpty(record.get(pgmProperty))) {
                        record.set(pgmProperty, pr.data[pgmProperty]);
                        record.commit();
                      }
                    }

                  }

                  Ext.Ajax.request({
                    method: 'GET',
                    url: '/api/v1/open/folder-mngs/' + pgmId,
                    callback: function (opts, success, response) {
                      var r = Ext.decode(response.responseText);

                      if (r.success) {
                        var categoryId = null;
                        if (!Ext.isEmpty(r.data)) {
                          if (!Ext.isEmpty(r.data.category_id)) {
                            categoryId = r.data.category_id;
                          }
                        }

                        record.set('c_category_id', categoryId);
                        record.commit();

                      }
                      _this.fireEvent('pgmselect', _this, record);
                    }
                  });
                }

              } catch (e) {
                Ext.Msg.alert(e['name'], e['message']);
              }
            } else {
              Ext.Msg.alert(_text('MN00022'), opts.url + '<br />' + response.statusText + '(' + response.status + ')');
            }
          }
        })
      } else {
        _this.fireEvent('pgmselect', _this, record);
      }
    },
    _initItems: function () {

      this._makeTextField();
      this._makeSearchButton();
    }
  });

  Ext.reg("c-material-search", Custom.MaterialSearchField);
})();