(function () {
  Ext.ns('Custom');
  Custom.PgmListGrid = Ext.extend(Ext.grid.GridPanel, {

    constructor: function (config) {

      this.addEvents('pgmselect');
      this.addEvents('pgmdblclick');

      this._init();

      this.border = false;

      this.layout = {
        type: 'hbox'
      };

      Ext.apply(this, {}, config || {});

      Custom.PgmListGrid.superclass.constructor.call(this);
    },

    listeners: {
      rowdblclick: function (self, idx, e) {
        var record = self.getStore().getAt(idx);
        self.fireEvent('pgmdblclick', self, record, idx);
      }
    },

    resetColumnWidth: function (channelCode) {
      console.log('pgmListGrid(this)', this);
      var cm = this.getColumnModel();
      if (channelCode === 'CJSL') {
        cm.setHidden(4, true);
        cm.setHidden(5, true);
        cm.setHidden(6, false);
      } else {
        cm.setHidden(4, false);
        cm.setHidden(5, false);
        cm.setHidden(6, true);
      }
    },

    clear: function () {
      this.getStore().removeAll();
      this._updateEmptyText('');
    },

    _updateEmptyText: function (text) {
      this.view.mainBody.update('<div class="x-grid-empty">' + text + '</div>');
    },

    _init: function () {
      var _this = this;

      this.store = Custom.Store.getPgmStore();
      this.cls = 'header-center-grid';
      this.cm = new Ext.grid.ColumnModel({
        defaults: {
          width: 120,
          sortable: false,
          menuDisabled: true
        },
        columns: [
          new Ext.grid.RowNumberer(),
          {
            header: 'PGM코드',
            dataIndex: 'pgmCd',
            width: 60,
            align: 'center'
          },
          {
            header: 'PGM명',
            dataIndex: 'pgmNm',
            width: 150
          },
          {
            header: '방송시간',
            dataIndex: 'bdStDtm',
            width: 130,
            align: 'center'
          },
          {
            header: '쇼호스트',
            dataIndex: 'showHostInfo',
            width: 150
          },
          {
            header: '출연자',
            dataIndex: '??',
            width: 150
          },
          {
            header: 'PD',
            dataIndex: 'pdInfo',
            width: 150
          }
        ]
      });

      this.viewConfig = {
        emptyText: '검색된 결과가 없습니다.'
      };

      this.sm = new Ext.grid.RowSelectionModel({
        singleSelect: true,
        listeners: {
          rowselect: function (self, idx, record) {
            _this.fireEvent('pgmselect', _this, record, idx);
          }
        }
      });

      this.frame = false;
      this.height = 120;
    }
  });

  Ext.reg('c-pgm-list-grid', Custom.PgmListGrid);
})();