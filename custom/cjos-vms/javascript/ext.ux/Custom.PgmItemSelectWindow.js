(function () {
  Ext.ns('Custom');
  Custom.PgmItemSelectWindow = Ext.extend(Ext.Window, {

    // private variables
    _searchForm: null,
    _pgmListGrid: null,
    _itemListGrid: null,

    _searchParams: null, // 검색 파라메터

    _config: null, // 커스텀 설정

    constructor: function (config) {

      this.title = 'PGM 정보 조회';
      this.width = 750;
      this.minWidth = 750;
      this.modal = true;
      this.height = getSafeHeight(600);
      this.layout = {
        type: 'fit'
      };
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
          self._itemListGrid.un('itemselect', self._onItemSelect, self);
        }
      };

      this._initItems();

      Custom.PgmItemSelectWindow.superclass.constructor.call(this);
    },

    setConfig: function (config) {
      this._config = config;
    },

    _onClearSearchForm: function () {
      this._pgmListGrid.clear();
      this._itemListGrid.clear();
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
        height: 200
      });
      this._pgmListGrid.on('pgmselect', this._onPgmSelect, this);
      this._pgmListGrid.on('pgmdblclick', this._onPgmDblClick, this);

      // item grid
      this._itemListGrid = new Custom.ItemListGrid({
        store: Custom.Store.getPgmItemsStore(),
        height: 200,
        autoCheckItem: true,
        viewConfig: {
          emptyText: '선택하신 PGM에 등록된 상품이 없습니다.'
        }
      });
      this._itemListGrid.setAutoCheckItem();
      this._itemListGrid.on('itemselect', this._onItemSelect, this);

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
          title: '상품목록',
          items: [
            this._itemListGrid
          ]
        }]
      }];
    },

    _onSearch: function (frm, params) {
      //console.log('onSearch', params);
      this._selected = null;

      this._searchParams = {
        channel_code: params.channel_code,
        broad_date: params.broad_date
      };

      this._pgmListGrid.getStore().load({
        params: params
      });
    },

    _onChannelSelect: function (frm, channelCode) {
      this._pgmListGrid.resetColumnWidth(channelCode);
    },

    _onPgmSelect: function (grid, r, idx) {
      r.set('channel_code', this._searchForm.getChannelCode());

      this._selected = r.copy();

      //console.log('this._selected', this._selected);
      var params = {
        broad_date: this._searchParams.broad_date,
        channel_code: this._searchParams.channel_code,
        pgm_code: r.get('pgmCd')
      };

      this._itemListGrid.getStore().load({
        params: params
      });
    },

    _onItemSelect: function (grid, r, idx) {

    },

    _onPgmDblClick: function (grid, r, idx) {

    },

    _fireOkEvent: function () {
      var selectedItems = this._itemListGrid.getSelectionModel().selections;
      this._selected.selectedItems = selectedItems.clone();

      this.fireEvent('ok', this, this._selected);
    }
  });
  Ext.reg('c-pgmitem-select-window', Custom.PgmItemSelectWindow);
})();