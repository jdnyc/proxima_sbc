(function () {
  Ext.ns('Custom');
  Custom.UserListGrid = Ext.extend(Ext.grid.GridPanel, {
    constructor: function (config) {

      this.addEvents('userselect');
      this.addEvents('userdblclick');

      this._init(config);

      this.border = false;

      this.layout = {
        type: 'hbox'
      };

      Ext.apply(this, {}, config || {});

      Custom.UserListGrid.superclass.constructor.call(this);
    },

    listeners: {
      rowdblclick: function (self, idx, e) {
        // 멀티 셀렉트 모드 일 때는 이벤트 발생 시키지 않음
        if (this.singleSelect) {
          var record = self.getStore().getAt(idx);
          self.fireEvent('userdblclick', self, record, idx);
        }
      }
    },

    clear: function () {
      this.getStore().removeAll();
      this._updateEmptyText('');
    },

    _updateEmptyText: function (text) {
      this.view.mainBody.update('<div class="x-grid-empty">' + text + '</div>');
    },

    _init: function (config) {
      var singleSelect = (config && config.singleSelect);
      var userIdMasking = (config && config.userIdMasking);
      var _this = this;
      var userColumnModel = new Ext.grid.CheckboxSelectionModel({
        singleSelect: singleSelect,
        listeners: {
          rowselect: function (self, idx, record) {
            _this.fireEvent('userselect', _this, record, idx);
          }
        }
      });
      if (singleSelect) {
        userColumnModel.width = 0;
      }

      if(userIdMasking) {
        this.store = Custom.Store.getMaskingUserStore();
      } else {
        this.store = Custom.Store.getUserStore();
      }

      this.cls = 'header-center-grid';
      this.cm = new Ext.grid.ColumnModel({
        defaults: {
          width: 120,
          sortable: false,
          menuDisabled: true
        },
        columns: [
          new Ext.grid.RowNumberer(),
          userColumnModel,
          {
            header: 'MEMBER_ID',
            dataIndex: 'member_id',
            align: 'center',
            width: 100,
            hidden: true,
          },
          {
            header: '사용자아이디',
            dataIndex: 'user_id',
            align: 'center',
            width: 100
          },
          {
            header: '사용자명',
            dataIndex: 'user_nm',
            align: 'center',
            width: 100,
            hidden: userIdMasking
          },
          {
            header: '부서명',
            dataIndex: 'dept_nm',
            width: 280
          }
        ]
      });

      this.viewConfig = {
        emptyText: '검색된 결과가 없습니다.'
      };
      this.sm = userColumnModel;
      this.height = 150;
      this.frame = false;
    }
  });

  Ext.reg('c-user-list-grid', Custom.UserListGrid);
})();