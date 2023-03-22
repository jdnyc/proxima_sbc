(function () {
  Ext.ns('Custom');
  Custom.ItemListGrid = Ext.extend(Ext.grid.GridPanel, {

    // property
    pagination: false,
    pageSize: 10,

    constructor: function (config) {

      this.addEvents('itemselect');
      this.addEvents('itemdblclick');

      this._init(config);

      this.border = false;

      this.layout = {
        type: 'hbox'
      };

      Ext.apply(this, {}, config || {});

      Custom.ItemListGrid.superclass.constructor.call(this);
    },

    listeners: {
      rowdblclick: function (self, idx, e) {
        // 멀티 셀렉트 모드 일 때는 이벤트 발생 시키지 않음
        if (this.singleSelect) {
          var record = self.getStore().getAt(idx);
          self.fireEvent('itemdblclick', self, record, idx);
        }
      },
      beforedestroy: function (self) {
        this.getStore().un('exception', this._onStoreException, this);
      }
    },

    viewConfig: {
      emptyText: '검색된 결과가 없습니다.'
    },

    setAutoCheckItem: function () {
      this.getStore().on('load', this._onStoreLoad, this);
    },

    clear: function () {
      this.getStore().removeAll();
      this.getBottomToolbar().updateInfo();
      this._updateEmptyText('');
    },

    _init: function (config) {

      var singleSelect = (config && config.singleSelect);
      var _this = this;
      this._itemColumnModel = new Ext.grid.CheckboxSelectionModel({
        singleSelect: singleSelect,
        listeners: {
          rowselect: function (self, idx, record) {
            _this.fireEvent('itemselect', _this, record, idx);
          }
        }
      });
      if (singleSelect) {
        this._itemColumnModel.width = 0;
      }

      if (!this.store) {
        this.store = Custom.Store.getItemStore();
      }

      this.store.on('exception', this._onStoreException, this);

      this.cls = 'header-center-grid';
      this.cm = new Ext.grid.ColumnModel({
        defaults: {
          width: 120,
          sortable: false,
          menuDisabled: true
        },
        columns: [
          new Ext.grid.RowNumberer(),
          this._itemColumnModel,
          {
            header: '상품코드',
            dataIndex: 'itemCd',
            align: 'center',
            width: 70
          },
          {
            header: '상품명',
            dataIndex: 'itemNm',
            width: 300
          },
          {
            header: '채널',
            dataIndex: 'chnCd',
            align: 'center',
            width: 100
          },
          {
            header: '상품상태',
            dataIndex: 'slClsNm',
            align: 'center',
            width: 100
          }
        ]
      });

      this.sm = this._itemColumnModel;
      this.height = 100;
      this.frame = false;

      if (config.pagination) {
        this.bbar = new Ext.PagingToolbar({
          store: this.getStore(),
          pageSize: this.pageSize,
          displayInfo: true,
          buttonAlign: 'center'
        });
      }
    },

    _updateEmptyText: function (text) {
      this.view.mainBody.update('<div class="x-grid-empty">' + text + '</div>');
    },

    _onStoreException: function (dataProxy, type, action, options, response, arg) {
      var msg = '검색된 결과가 없습니다.';
      if (response.isTimeout) {
        msg += '(조회시간(' + this.getStore().timeoutSec + '초) 초과)';
      } else {
        msg += '(' + response.statusText + ')';
      }
      this._updateEmptyText(msg);
    },

    _onStoreLoad: function (store, records, options) {
      //console.log('item grid loaded');
      var autoSelectRecords = [];
      Ext.each(records, function (record) {
        //console.log('item grid check items : ', record);
        // 상품 상태가 정상이면 자동으로 체크를 해준다.
        if (record.get('slCls') === 'A') {
          autoSelectRecords.push(record);
        }
      });
      // console.log(this);
      this._itemColumnModel.selectRecords(autoSelectRecords);
    }
  });

  Ext.reg('c-item-list-grid', Custom.ItemListGrid);
})();