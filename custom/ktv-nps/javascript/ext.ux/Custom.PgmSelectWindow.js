(function () {

  Ext.ns('Custom');
  Custom.PgmSelectWindow = Ext.extend(Ext.Window, {

    // properties

    // private variables
    _selected: null,
    _itemCodeField: null,
    _searchForm: null,
    _pgmListGrid: null,

    _config: null,

    constructor: function (config) {

      this.addEvents('ok');

      this.title = 'PGM 정보 조회';
      this.width = 720;
      this.minWidth = 720;
      this.modal = true;
      this.height = getSafeHeight(510);
      this.layout = {
        type: 'fit'
      };

      this._selected = null;

      this.cls = 'dark-window';

      Ext.apply(this, {}, config || {});

      this.listeners = {
        afterrender: function (self) {
          if (self._config && self._config.channelCode) {
            self._searchForm.setChannel(self._config.channelCode);
          }
        },
        beforedestroy: function (self) {
          self._searchForm.un('search', self._onSearch, self);
          self._searchForm.un('clear', self._onClearSearchForm, self);
          self._searchForm.un('channelselect', self._onChannelSelect, self);
          self._pgmListGrid.un('pgmselect', self._onPgmSelect, self);
          self._pgmListGrid.un('pgmdblclick', self._onPgmDblClick, self);
        }
      };

      this._initItems();

      Custom.PgmSelectWindow.superclass.constructor.call(this);
    },

    initComponent: function (config) {

      Custom.PgmSelectWindow.superclass.initComponent.call(this);
    },

    setConfig: function (config) {
      this._config = config;
    },

    _onClearSearchForm: function () {
      this._pgmListGrid.clear();
    },

    _initItems: function () {
      var _this = this;

      // search form
      this._searchForm = new Custom.PgmSearchForm();
      this._searchForm.on('search', this._onSearch, this);
      this._searchForm.on('clear', this._onClearSearchForm, this);
      this._searchForm.on('channelselect', this._onChannelSelect, this);

      // pgm grid
      this._pgmListGrid = new Custom.PgmListGrid({
        height: 300
      });
      this._pgmListGrid.on('pgmselect', this._onPgmSelect, this);
      this._pgmListGrid.on('pgmdblclick', this._onPgmDblClick, this);

      this.items = [{
        xtype: 'panel',
        layout: 'form',
        autoScroll: true,
        defaults: {
          anchor: '98%',
          align: 'stretch'
        },
        border: false,
        bbar: {
          toolbarCls: "dark-toolbar",
          style: {
            paddingTop: '5px'
          },
          items: ['->', {
            xtype: 'a-iconbutton',
            text: '취소',
            handler: function (btn, e) {
              _this.close();
            }
          }, '->', {
            xtype: 'a-iconbutton',
            text: '확인',
            handler: function (btn, e) {
              _this._fireOkEvent();
            }
          }]
        },
        items: [{
          xtype: 'fieldset',
          cls: 'dark-fieldset',
          style: {
            marginTop: '10px',
            marginLeft: '10px'
          },
          items: [
            this._searchForm
          ]
        }, {
          xtype: 'spacer',
          height: 10
        }, {
          xtype: 'fieldset',
          cls: 'dark-fieldset',
          style: {
            marginTop: '10px',
            marginLeft: '10px'
          },
          title: '편성목록',
          items: [
            this._pgmListGrid
          ]
        }]
      }];
    },

    _onSearch: function (frm, params) {
      this._selected = null;
      this._pgmListGrid.getStore().load({
        params: params
      });
    },

    _onChannelSelect: function (frm, channelCode) {
      this._pgmListGrid.resetColumnWidth(channelCode);
    },

    _onPgmSelect: function (grid, r, idx) {
      this._setSelectedRecord(r);
    },

    _onPgmDblClick: function (grid, r, idx) {
      this._setSelectedRecord(r);
      this._fireOkEvent();
    },

    _setSelectedRecord: function (record) {
      record.set('channel_code', this._searchForm.getChannelCode());
      this._selected = record;
    },

    _fireOkEvent: function () {
      this.fireEvent('ok', this, this._selected);
    }

  });
  Ext.reg('c-pgm-select-window', Custom.PgmSelectWindow);
})();