Ext.ns('Ariel.Nps');
Ariel.Nps.OpenBISProgram = Ext.extend(Ext.grid.GridPanel, {
  layout: 'fit',
  border: false,
  xtype: 'grid',
  //title: '프로그램 목록',
  region: 'center',
  loadMask: true,
  store: this.store,
  viewConfig: {
    emptyText: '조회된 프로그램 정보가 없습니다',
    forceFit: true
  },
  initComponent: function (config) {
    Ext.apply(this, config || {});

    this.addEvents("selectProgram");
    this.addEvents("selectionsProgram");

    var that = this;

    this.store = new Ext.data.JsonStore({
      restful: true,
      remoteSort: true,
      proxy: new Ext.data.HttpProxy({
        method: 'GET',
        // url: '/api/v1/open/bis-programs',
        url: '/api/v1/open/folder-mngs',
        type: 'rest'
      }),
      baseParams: {
        parent_id: 2,
        paging: 1,
        limit: 20,
        show: 1
      },
      root: 'data',
      totalProperty: 'total',
      idPropery: 'pgm_id',
      autoLoad: true,
      fields: [
        { name: 'id', type: 'int' },
        { name: 'fsname' },
        { name: 'softlimit' },
        { name: 'hardlimit' },
        { name: 'cursize' },
        { name: 'status' },
        'folder_path',
        'folder_path_nm',
        'group_cd',
        'quota',
        'chmod',
        'owner_cd',
        'quota_unit',
        'grace_period',
        'grace_period_unit',
        'step',
        'parent_id',
        'parent_nm',
        'dc',
        'pgm_id',
        'using_yn',
        'parent',
        { name: 'created_at', type: 'date' },
        { name: 'updated_at', type: 'date' },
        { name: 'expired_date', type: 'date', dateFormat: 'YmdHis' }
      ],
      sortInfo: {
        // field: 'folder_path_nm',
        // direction: 'ASC'
        field: 'pgm_id',
        direction: 'ASC'
      },
      listeners: {
        exception: function (self, type, action, options, response, arg) {
          if (type == 'response') {
            if (response.status == '200') {
              Ext.Msg.alert(_text('MN00022'), response.responseText);
            } else {
              Ext.Msg.alert(_text('MN00022'), response.status);
            }
          } else {
            Ext.Msg.alert(_text('MN00022'), type);
          }
        }
      }
    });



    this.colModel = new Ext.grid.ColumnModel({
      columns: [
        {
          header: '순번',
          renderer: function (v, p, record, rowIndex) {
            return record.json.rn;
          },
          width: 70
        },
        { header: '프로그램명', dataIndex: 'folder_path_nm', sortable: true, width: 300 },
        { header: '폴더명', dataIndex: 'folder_path', sortable: true, width: 100 },
        { header: '프로그램ID', dataIndex: 'pgm_id', sortable: true, width: 150 },
      ]
    });
    this.tbar = [{
      xtype: 'aw-button',
      iCls: 'fa fa-refresh',
      text: '새로고침',
      handler: function (btn, e) {
        var grid = btn.ownerCt.ownerCt;

        grid.getStore().reload();
      }
    }, '-', {
      xtype: 'displayfield',
      value: '프로그램명',
      style: {
        'text-align': 'center'
      },
      width: 70
    }, {
      xtype: 'textfield',
      width: 120,
      submitValue: false,
      enableKeyEvents: true,
      listeners: {
        keydown: function (self, e) {
          if (e.getKey() == e.ENTER) {
            e.stopEvent();

            var search_folder_path_nm = self.getValue();
            var grid = self.ownerCt.ownerCt;

            grid.getStore().load({
              params: {
                parent_id: 2,
                folder_path_nm: search_folder_path_nm
              }
            });
          }
        }
      }
    }, {
      xtype: 'aw-button',
      text: '검색',
      iCls: 'fa fa-search',
      listeners: {
        click: function (self, e) {
          var search_folder_path_nm = self.ownerCt.items.items[3].getValue();
          var grid = self.ownerCt.ownerCt;

          grid.getStore().load({
            params: {
              parent_id: 2,
              folder_path_nm: search_folder_path_nm
            }
          });
        }
      }
    }, '->',
    {
      hidden: true,
      xtype: 'aw-button',
      iCls: 'fa fa-plus',
      text: '추가',
      handler: function (btn) {
        var grid = btn.ownerCt.ownerCt;
        var sm = grid.getSelectionModel();
        if (sm.hasSelection()) {
          var records = sm.getSelections();
          that._fireSelectionsEvent(records);
        } else {
          Ext.Msg.alert('알림', '추가하실 프로그램을 선택해주세요.');
        }
      }
    }];
    this.bbar = {
      xtype: 'paging',
      pageSize: 20,
      displayInfo: true,
      store: this.store
    };
    this.listeners = {
      viewready: function (self) {

      },
      rowclick: function (self, rowIndex, e) {
        var records = self.getStore().getAt(rowIndex);
        that._fireSelectEvent(records);
      }
    };


    // this.items = [this.listGrid];

    this._fireSelectEvent = function (records) {
      this.fireEvent("selectProgram", this, records);
    }

    this._fireSelectionsEvent = function (records) {
      this.fireEvent("selectionsProgram", this, records);
    }



    Ariel.Nps.OpenBISProgram.superclass.initComponent.call(this);
  }
});