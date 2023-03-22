(function () {
  Ext.ns('Custom');
  Custom.ItemMatchListGrid = Ext.extend(Ext.grid.EditorGridPanel, {

    // properties
    clicksToEdit: 1,
    toolbarTitle: '',
    initialData: '',
    onSetRepItem: null,

    constructor: function (config) {

      this.addEvents('itemselect');
      this.addEvents('itemdblclick');

      Ext.apply(this, {}, config || {});

      this._init(config);

      this.border = false;

      this.layout = {
        type: 'hbox'
      };

      Custom.ItemMatchListGrid.superclass.constructor.call(this);
    },

    listeners: {
      rowdblclick: function (self, idx, e) {
        // 멀티 셀렉트 모드 일 때는 이벤트 발생 시키지 않음
        if (this.singleSelect) {
          var record = self.getStore().getAt(idx);
          self.fireEvent('itemdblclick', self, record, idx);
        }
      },
      beforeedit: function (e) {
        // 상품코드가 없으면 편집 불가
        if (!e.record.get('itemCd')) {
          e.cancel = true;
          return;
        }
      },
      afterrender: function (self) {
        if (Ext.isEmpty(self.initialData)) {
          return;
        }
        var items = Ext.decode(self.initialData);
        self.setValue(items);
      }
    },

    getValue: function () {
      var items = [];

      this.getStore().each(function (record) {
        var item = {
          repItemYn: record.get('repItemYn'),
          itemCd: record.get('itemCd'),
          itemNm: record.get('itemNm'),
          chnCd: record.get('chnCd'),
          slCls: record.get('slCls'),
          slClsNm: record.get('slClsNm'),
          dispOrder: record.get('dispOrder'),
          dispYn: record.get('dispYn'),
          imageUrl: record.get('imageUrl'),
          modNm: record.get('modNm'),
          modDtm: record.get('modDtm'),
          dpCateId: record.get('dpCateId'),
          dpCateNm: record.get('dpCateNm')
        };
        if (record.dirty) {
          item.modNm = null;
          item.modDtm = null;
        }
        items.push(item);
      });

      return items;
    },

    setValue: function (items) {
      if (!Ext.isArray(items)) {
        return;
      }
      this.getStore().loadData({
        data: items
      });
    },

    addItems: function (itemRecords) {
      //itemRecords 는 MixedCollection
      //console.log('itemRecords', itemRecords);
      /*
      'itemCd', // 상품 코드
          'itemNm', // 상품명
          'chnCd', // 상품 채널
          'slCls', // 상품 상태
          'slClsNm', // 상품 상태
          'imageUrl' // 이미지 URL
           */
      var _this = this;
      itemRecords.each(function (itemRecord) {
        //console.log('itemRecord', itemRecord);
        _this._addRow({
          itemCd: itemRecord.get('itemCd'),
          itemNm: itemRecord.get('itemNm'),
          chnCd: itemRecord.get('chnCd'),
          slCls: itemRecord.get('slCls'),
          slClsNm: itemRecord.get('slClsNm'),
          imageUrl: itemRecord.get('imageUrl'),
          dpCateId: itemRecord.get('dpCateId'),
          dpCateNm: itemRecord.get('dpCateNm'),
          dispYn: 'Y'
        });
      });
    },

    validate: function () {
      var isValid = true;
      var _this = this;
      this.getStore().each(function (record) {
        if (Ext.isEmpty(record.get('itemCd'))) {
          Ext.Msg.alert('알림', '입력되지 않은 상품목록이 있습니다. 행을 삭제하거나 상품을 입력해 주세요.');
          isValid = false;
          return false;
        }

        // 전시순서 미입력 체크
        if (Ext.isEmpty(record.get('dispOrder'))) {
          Ext.Msg.alert('알림', '전시순서가 미 입력된 항목이 있습니다. 전시순서를 입력해 주세요.');
          isValid = false;
          return false;
        }

        var dispOrder = parseInt(record.get('dispOrder'));
        if (dispOrder > 999) {
          Ext.Msg.alert('알림', '상품의 전시 순서는 999까지 입력 가능합니다.');
          isValid = false;
          return false;
        }

        // 전시순서 중복 체크
        if (_this._displayOrderExists(record)) {
          Ext.Msg.alert('알림', '중복된 전시순서가 있습니다. 전시순서를 조정해 주세요.');
          isValid = false;
          return false;
        }
      });
      return isValid;
    },

    getError: function () {
      var isValid = true;
      var _this = this;
      var error = '';
      this.getStore().each(function (record) {
        if (Ext.isEmpty(record.get('itemCd'))) {
          error = '입력되지 않은 상품목록이 있습니다. 행을 삭제하거나 상품을 입력해주세요.';
          return false;
        }

        // 전시순서 중복 체크
        if (_this._displayOrderExists(record)) {
          error = '상품전시에 중복된 순서가 있습니다.';
          return false;
        }
      });
      return error;
    },

    _displayOrderExists: function (compareRecord) {
      var exists = false;
      this.getStore().each(function (record) {
        // 같은 상품은 비교 하면 안됨
        if (compareRecord.get('itemCd') === record.get('itemCd')) {
          return;
        }
        var compareDispOrder = compareRecord.get('dispOrder');
        var dispOrder = record.get('dispOrder');
        if (!Ext.isEmpty(compareDispOrder) && !Ext.isEmpty(dispOrder) &&
          compareDispOrder === dispOrder) {
          exists = true;
          return false;
        }
      });
      return exists;
    },

    _createItemSearchButton: function (value, id, record) {
      var _this = this;
      new Ext.Button({
        text: '<i class="fa fa-search"/>',
        cls: 'icon-button',
        handler: function () {
          var itemSelectWindow = new Custom.ItemSelectWindow({
            singleSelect: true
          });
          itemSelectWindow.on('ok', function (win, newRecord) {
            win.close();
            if (_this._itemExists(newRecord)) {
              Ext.Msg.alert('알림', '동일한 상품이 이미 매칭되어 있습니다.');
              return;
            }
            record.set('itemCd', newRecord.get('itemCd'));
            record.set('itemNm', newRecord.get('itemNm'));
            record.set('chnCd', newRecord.get('chnCd'));
            record.set('slCls', newRecord.get('slCls'));
            record.set('slClsNm', newRecord.get('slClsNm'));
            record.set('imageUrl', newRecord.get('imageUrl'));
            record.set('dpCateId', newRecord.get('dpCateId'));
            record.set('dpCateNm', newRecord.get('dpCateNm'));
            record.set('dispYn', 'Y');

            if (record.get('repItemYn') && _this.onSetRepItem) {
              _this.onSetRepItem(record);
            }
          });
          itemSelectWindow.show();
        }
      }).render(document.body, id);
    },

    _init: function (config) {

      var singleSelect = (config && config.singleSelect);
      var _this = this;
      var itemColumnModel = new Ext.grid.CheckboxSelectionModel({
        singleSelect: singleSelect,
        listeners: {
          rowselect: function (self, idx, record) {
            _this.fireEvent('itemselect', _this, record, idx);
          }
        }
      });
      if (singleSelect) {
        itemColumnModel.width = 0;
      }

      if (!this.store) {
        this.store = Custom.Store.getVideoItemsStore();
      }
      this.cls = 'header-center-grid';

      var dispYnCombo = new Ext.form.ComboBox({
        editable: false,
        triggerAction: 'all',
        lazyRender: true,
        mode: 'local',
        store: new Ext.data.ArrayStore({
          id: 0,
          fields: [
            'dispYn',
            'displayText'
          ],
          data: [
            ['Y', '전시'],
            ['N', '미전시']
          ]
        }),
        valueField: 'dispYn',
        displayField: 'displayText'
      });

      this.cm = new Ext.grid.ColumnModel({
        defaults: {
          width: 120,
          sortable: false,
          menuDisabled: true
        },
        columns: [
          new Ext.grid.RowNumberer(),
          itemColumnModel,
          {
            header: '대표상품',
            dataIndex: 'repItemYn',
            align: 'center',
            width: 50,
            renderer: function (value) {
              if (value && value.toUpperCase() === 'Y') {
                return '<i class="fa fa-circle" style="color: green;" />';
              } else {
                return '<i class="fa fa-circle-o" style="color: gray;" />';
              }
            },
            listeners: {
              click: function (col, grid, rowIndex, e) {
                var record = grid.getStore().getAt(rowIndex);
                if (!record.get('itemCd')) {
                  return;
                }
                var isRepItem = record.get('repItemYn') === 'Y';
                if (isRepItem) {
                  if (grid.onSetRepItem) {
                    grid.onSetRepItem(record);
                  }
                  return;
                } else {
                  record.set('repItemYn', 'Y');
                  record.set('dispOrder', 0);
                  grid.getStore().each(function (r) {
                    if (r.id === record.id) {
                      return;
                    }
                    if (r.get('repItemYn') === 'Y') {
                      r.set('repItemYn', 'N');
                    }
                  });
                  if (grid.onSetRepItem) {
                    grid.onSetRepItem(record);
                  }
                }
              }
            }
          },
          {
            header: '상품코드',
            dataIndex: 'itemCd',
            align: 'center',
            width: 70,
            renderer: function (value, metaData, record, rowIndex, colIndex, store) {
              if (value) {
                return value;
              }

              if (!record.dirty) {
                return;
              }
              var id = Ext.id();
              _this._createItemSearchButton.defer(1, _this, [null, id, record]);
              return '<center><div id="' + id + '" /></center>';
            }
          },
          {
            header: '상품명',
            dataIndex: 'itemNm',
            width: 250
            // renderer: function (value) {
            //   if (!value) {
            //     return null;
            //   }
            //   return '<div style="height:30px; line-height:1.3em; overflow: auto !important; white-space: normal !important; text-overflow: ellipsis; display: block;">' + value + '</div>';
            // }
          },
          {
            header: '채널',
            dataIndex: 'chnCd',
            align: 'center',
            width: 68
          },
          {
            header: '상품이미지',
            dataIndex: 'imageUrl',
            align: 'center',
            width: 70,
            renderer: function (value) {
              //console.log('imageUrl:', value);
              if (value === undefined) {
                return null;
              }
              return '<img width="20px" height="20px" src="' + value + '" onerror="fallbackImg(this)" />';
            }
          },
          {
            header: '상품상태',
            dataIndex: 'slClsNm',
            align: 'center',
            width: 50
          },
          {
            header: '전시순서',
            dataIndex: 'dispOrder',
            align: 'center',
            width: 50,
            editor: new Ext.form.NumberField({
              xtype: 'textfield',
              allowBlank: false,
              allowNegative: false
            }),
            getCellEditor: function (rowIndex) {
              var record = _this.getStore().getAt(rowIndex);
              if (!record.get('itemCd')) {
                return;
              }
              var isRepItem = record.get('repItemYn') === 'Y';
              if (isRepItem) {
                // 대표상품이면 수정불가능하게
                //col.editable = false;
                return null;
              } else {
                //col.editable = true;
                return new Ext.grid.GridEditor(new Ext.form.NumberField({
                  xtype: 'textfield',
                  allowBlank: false,
                  allowNegative: false
                }));
              }
            }
          },
          {
            header: '전시여부',
            dataIndex: 'dispYn',
            align: 'center',
            width: 70,
            editor: dispYnCombo,
            renderer: function (value) {
              var combo = dispYnCombo;
              var record = combo.findRecord(combo.valueField, value);
              return record ? record.get(combo.displayField) : combo.valueNotFoundText;
            }
          },
          {
            header: '수정자',
            dataIndex: 'modNm',
            align: 'center',
            width: 50
          },
          {
            header: '수정일',
            dataIndex: 'modDtm',
            align: 'center',
            width: 180,
            renderer: function (value) {
              var dt = Date.parseDate(value, 'YmdHis');
              if (!dt) {
                return null;
              }
              return dt.format('Y-m-d H:i:s');
            }
          }
        ],
      });

      this.viewConfig = {
        emptyText: '검색된 결과가 없습니다.'
      };
      this.sm = itemColumnModel;
      this.height = 200;
      this.frame = false;

      this.tbar = new Ext.Toolbar({
        toolbarCls: "dark-toolbar",
        style: {
          padding: '5px'
        },
        items: [{
            xtype: 'label',
            style: {
              marginLeft: '5px'
            },
            text: this.toolbarTitle
          },
          '->',
          {
            xtype: 'a-iconbutton',
            text: '행추가',
            handler: function (btn) {
              _this._addRow();
            }
          },
          {
            xtype: 'a-iconbutton',
            text: '행삭제',
            handler: function (btn) {
              _this._removeRow();
            }
          }
        ]
      });
    },
    // 0번째 인덱스에 빈행 추가
    _addRow: function (item) {
      var store = this.getStore();
      var count = store.getCount();
      if (count >= 5) {
        Ext.Msg.alert('알림', '동영상 코드별 상품코드는 최대 5개까지 매칭할 수 있습니다.');
        return;
      }
      var record = null;
      if (item) {
        //console.log('item', item);
        if (!item.dispOrder) {
          item.dispOrder = (count === 0) ? 0 : undefined;
        }
        //console.log('item2', item);
        record = new Ext.data.Record(item);

        if (this._itemExists(record)) {
          Ext.Msg.alert('알림', '동일한 상품이 이미 매칭되어 있습니다.');
          return;
        }
      } else {
        record = new Ext.data.Record({
          dispOrder: (count === 0) ? 0 : undefined
        });
      }

      if (count === 0) {
        record.set('repItemYn', 'Y');
        if (this.onSetRepItem) {
          this.onSetRepItem(record);
        }
      } else {
        record.set('repItemYn', 'N');
      }
      store.insert(0, record);
      this._orderNumber();
    },
    // 체크박스로 선택된 행 삭제
    _removeRow: function () {
      var _this = this;
      var selectedRecords = this.getSelectionModel().getSelections();
      Ext.each(selectedRecords, function (record) {
        _this.getStore().remove(record);
      });
      this._orderNumber();
    },

    _itemExists: function (newRecord) {
      var itemExists = false;
      this.getStore().each(function (record) {
        // console.log('record', record);
        // console.log('newRecord', newRecord);
        if (record.get('itemCd') === newRecord.get('itemCd')) {
          itemExists = true;
          return false;
        }
      });
      return itemExists;
    },

    // 그리드의 행번호 다시 부여
    _orderNumber: function () {
      this.getStore().each(function (record, idx) {
        record.set('no', idx + 1);
      });
    }

  });

  Ext.reg('c-item-match-list-grid', Custom.ItemMatchListGrid);
})();