(function () {
  Ext.ns('Ariel.archiveManagement');
  Ariel.archiveManagement.offlineTapeLogList = Ext.extend(Ext.grid.GridPanel, {
    title: '<span class="user_span"><span class="icon_title"><i class=""></i></span><span class="main_title_header">' + '' + '</span></span>',
    loadMask: true,
    stripeRows: true,
    frame: false,
    viewConfig: {
      emptyText: '목록이 없습니다.'
    },
    cls: 'grid_title_customize proxima_customize',
    listeners: {
      afterrender: function (self) {
        self._search();
      }
    },
    initComponent: function () {

      this._initialize();
      Ariel.archiveManagement.offlineTapeLogList.superclass.initComponent.call(this);
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
        autoLoad: false,
        proxy: new Ext.data.HttpProxy({
          method: 'GET',
          url: '/api/v1/data-logs/search',
          type: 'rest'
        }),
        totalProperty: 'total',
        root: 'data',
        fields: [
          'id',
          'target_id',
          'before_value',
          'after_value',
          'regist_user_id',
          { name: 'regist_dt', type: 'date' }
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
          { header: 'id', dataIndex: 'id', hidden: true },
          { header: 'target_id', dataIndex: 'target_id', hidden: true },
          {
            header: '소산여부(변경전)', dataIndex: 'before_value', width: 100, renderer: function (value) {
              var row = Ext.decode(value);
              return row.disprs_at;
            }
          },
          {
            header: '소산여부(변경후)', dataIndex: 'after_value', width: 100, renderer: function (value) {

              var row = Ext.decode(value);//cstdy_lc":"bbbb","disprs_at
              return row.disprs_at;
            }
          },
          {
            header: '보관위치(변경전)', dataIndex: 'before_value', width: 130, renderer: function (value) {
              var row = Ext.decode(value);
              return row.cstdy_lc;
            }
          },
          {
            header: '보관위치(변경후)', dataIndex: 'after_value', width: 130, renderer: function (value) {

              var row = Ext.decode(value);//cstdy_lc":"bbbb","disprs_at
              return row.cstdy_lc;
            }
          },
          { header: '사용자', dataIndex: 'regist_user_id', width: 100 },
          { header: '수정일시', dataIndex: 'regist_dt', width: 130, renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s') }
        ]
      });

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
        var returnVal = {};
        returnVal['channel'] = 'DP_TAPES';
        returnVal['target_id'] = this._targetId;

        return returnVal;

      }

      this.listeners = {
        afterrender: function (self) {
          self._search();
        },
        rowclick: function (self, idx, n, e) {

        },
        rowdblclick: function (self, idx, e) {
        }
      };

    }
  });
  return new Ariel.archiveManagement.offlineTapeLogList();
})()