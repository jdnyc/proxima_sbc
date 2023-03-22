(function () {
  Ext.ns('Custom');
  Custom.ItemSelectWindow = Ext.extend(Ext.Window, {
    // properties

    // private variables

    _selected: null,
    _searchTypeCombo: null,
    _itemCodeField: null,
    _itemListGrid: null,

    constructor: function (config) {
      this.addEvents('ok');

      this.title = '상품 검색';
      this.width = 750;
      this.minWidth = 750;
      this.modal = true;
      this.height = getSafeHeight(510);
      this.layout = {
        type: 'fit',
      };

      this.cls = 'dark-window';

      Ext.apply(this, {}, config || {});

      this._initItems(config);

      this.listeners = {
        beforedestroy: function (self) {
          self._itemListGrid.un('itemselect', self._onItemSelect, self);
          self._itemListGrid.un('itemdblclick', self._onItemDblClick, self);
        }
      };

      var _this = this;
      setTimeout(function () {
        _this._itemCodeField.focus();
      }, 200);

      Custom.ItemSelectWindow.superclass.constructor.call(this);
    },

    initComponent: function () {
      Custom.ItemSelectWindow.superclass.initComponent.call(this);
    },

    clear: function () {
      this._itemChannelCombo.setValue('선택');
      this._itemCodeField.setValue('');
      this._itemCodeField.focus();
      this._itemListGrid.clear();
    },

    _initItems: function () {
      var _this = this;

      this._searchTypeCombo = new Ext.form.ComboBox({
        mode: "local",
        name: "type",
        hidden: true,
        displayField: "display",
        valueField: "value",
        editable: false,
        height: 24,
        triggerAction: "all",
        value: "item_code",
        store: new Ext.data.ArrayStore({
          fields: ["value", "display"],
          data: [
            ["item_code", "상품코드"],
            ["item_name", "상품명"]
          ]
        }),
        width: 130
      });

      this._itemChannelCombo = new Ext.form.ComboBox({
        name: 'item_channel_code',
        triggerAction: 'all',
        editable: false,
        width: 160,
        store: Custom.Store.getItemChannelStore(),
        displayField: 'chnNm',
        valueField: 'chnCd',
        emptyText: '선택'
      });

      this._itemCodeField = new Ext.form.TextField({
        name: 'keyword',
        width: 120,
        listeners: {
          specialkey: function (f, e) {
            if (e.getKey() == e.ENTER) {
              e.stopEvent();
              _this._search();
            }
          },
        },
      });

      this.searchForm = new Ext.form.FormPanel({
        border: false,
        items: [{
          layout: 'hbox',
          border: false,
          items: [{
              xtype: "label",
              text: "구분:",
              hidden: true,
              style: {
                color: "white",
                marginTop: "6px"
              },
              width: 40
            },
            this._searchTypeCombo,
            {
              xtype: 'spacer',
              width: 10,
            }, {
              xtype: 'label',
              text: '채널:',
              style: {
                color: 'white',
                marginTop: '6px',
              },
              width: 30,
            },
            this._itemChannelCombo,
            {
              xtype: 'spacer',
              width: 10,
            }, {
              xtype: 'label',
              text: '상품코드:',
              style: {
                color: 'white',
                marginTop: '6px',
              },
              width: 70,
            },
            this._itemCodeField,
            {
              xtype: 'spacer',
              width: 10,
            },
            {
              xtype: 'a-iconbutton',
              text: '조회',
              handler: function (btn) {
                _this._search();
              },
            },
            {
              xtype: 'a-iconbutton',
              text: '초기화',
              handler: function (btn) {
                console.log('초기화!!');
                _this.clear();
              },
            },
          ],
        }, ],
      });
      this._itemListGrid = new Custom.ItemListGrid({
        height: 300,
        singleSelect: this.singleSelect,
        pagination: true,
        pageSize: 10
      });

      this._itemListGrid.on('itemselect', this._onItemSelect, this);
      this._itemListGrid.on('itemdblclick', this._onItemDblClick, this);

      this.items = [{
        xtype: 'panel',
        layout: 'form',
        autoScroll: true,
        defaults: {
          anchor: '98%',
          align: 'stretch',
        },
        border: false,
        bbar: {
          toolbarCls: 'dark-toolbar',
          style: {
            paddingTop: '5px'
          },
          items: [
            '->',
            {
              xtype: 'a-iconbutton',
              text: '취소',
              handler: function (btn, e) {
                _this.close();
              },
            },
            '->',
            {
              xtype: 'a-iconbutton',
              text: '확인',
              handler: function (btn, e) {
                _this._fireOkEvent();
              },
            },
          ],
        },
        items: [{
            xtype: 'fieldset',
            cls: 'dark-fieldset',
            style: {
              marginTop: '10px',
              marginLeft: '10px',
            },
            items: [this.searchForm],
          },
          {
            xtype: 'spacer',
            height: 10,
          },
          {
            xtype: 'fieldset',
            cls: 'dark-fieldset',
            style: {
              marginTop: '10px',
              marginLeft: '10px',
            },
            title: '상품목록',
            items: [this._itemListGrid],
          },
        ],
      }, ];
    },

    _search: function () {
      var values = this.searchForm.getForm().getValues();
      values.type = this._searchTypeCombo.getValue();

      values.item_channel_code = this._itemChannelCombo.getValue();
      if (Ext.isEmpty(values.item_channel_code)) {
        Ext.Msg.alert('알림', '상품채널을 입력해 주세요.');
        return;
      }

      if (Ext.isEmpty(values.keyword)) {
        var msg = '';
        if (values.type === 'item_code') {
          msg = '상품코드를 입력해 주세요.';
        } else if (values.type === 'item_name') {
          msg = '상품명을 입력해 주세요.';
        }
        Ext.Msg.alert('알림', msg);
        this._keywordField.focus();
        return;
      }

      this._itemListGrid.getStore().load({
        params: values,
      });
    },

    _onItemSelect: function (grid, r, idx) {
      this._selected = r;
    },

    _onItemDblClick: function (grid, r, idx) {
      this._selected = r;
      this._fireOkEvent();
    },

    _fireOkEvent: function () {
      this.fireEvent('ok', this, this._selected);
    },
  });
  Ext.reg('c-item-select-window', Custom.ItemSelectWindow);
})();