Ext.ns('Ariel.Nps');
Ariel.Nps.BISProgram = Ext.extend(Ext.Container, {
  layout: 'fit',
  border: false,

  initComponent: function(config) {
    Ext.apply(this, config || {});

    this.addEvents('selectProgram');

    var that = this;

    this.store = new Ext.data.JsonStore({
      restful: true,
      remoteSort: true,
      proxy: new Ext.data.HttpProxy({
        method: 'GET',
        url: '/api/v1/bis-programs',
        type: 'rest'
      }),
      root: 'data',
      totalProperty: 'total',
      idPropery: 'pgm_id',
      autoLoad: false,
      fields: [
        'pgm_id',
        'pgm_nm',
        'director',
        'main_role',
        'pgm_info',
        'status',
        'dvs_yn',
        { name: 'use_yn', mapping: 'channels.use_yn' },
        { name: 'category', mapping: 'pgm_nm' },
        { name: 'folder_name', mapping: 'pgm_id' }
      ],
      sortInfo: {
        field: 'pgm_nm',
        direction: 'ASC'
      },
      listeners: {
        exception: function(self, type, action, options, response, arg) {
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

    this.listGrid = {
      xtype: 'grid',
      //title: '프로그램 목록',
      region: 'center',
      loadMask: true,
      store: this.store,
      viewConfig: {
        emptyText: '조회된 프로그램 정보가 없습니다',
        forceFit: true
      },
      colModel: new Ext.grid.ColumnModel({
        columns: [
          new Ext.grid.RowNumberer(),

          {
            header: '프로그램명',
            dataIndex: 'pgm_nm',
            width: 200,
            sortable: false
          },
          {
            header: '내용',
            dataIndex: 'pgm_info',
            sortable: false
          },
          {
            header: '담당PD',
            dataIndex: 'director',
            sortable: false
          },
          {
            header: '사용여부',
            dataIndex: 'use_yn',
            sortable: false,
            renderer: function(value) {
              if (value == 'Y') return '사용';
              if (value == 'N') return '미사용';
            }
          },
          {
            header: '종방여부',
            dataIndex: 'dvs_yn',
            sortable: false,
            renderer: function(value) {
              if (value == 'Y') return '종방';
              if (value == 'N') return '방영';
            }
          },
          {
            header: '프로그램 ID',
            dataIndex: 'pgm_id',
            sortable: false
          }
          //{header: '등급분류' , dataIndex: 'info_grd', sortable: false, menuDisabled: true},
          //{header: '상태' , dataIndex: 'status', hidden: true, sortable: false, menuDisabled: true}
        ]
      }),
      tbar: [
        {
          xtype: 'aw-button',
          iCls: 'fa fa-refresh',
          text: '새로고침',
          handler: function(btn, e) {
            var grid = btn.ownerCt.ownerCt;

            grid.getStore().reload();
          }
        },
        '-',
        {
          submitValue: false,
          xtype: 'combo',
          fieldLabel: '사용유무',
          name: 'use_yn',
          typeAhead: true,
          editable: false,
          triggerAction: 'all',
          lazyRender: true,
          mode: 'local',
          value: 'Y',
          width: 80,
          store: new Ext.data.ArrayStore({
            fields: ['value', 'displayText'],
            data: [
              ['', '전체'],
              ['Y', '사용'],
              ['N', '미사용']
            ]
          }),
          valueField: 'value',
          displayField: 'displayText'
        },
        {
          submitValue: false,
          xtype: 'combo',
          fieldLabel: '종방여부',
          name: 'dvs_yn',
          typeAhead: true,
          editable: false,
          triggerAction: 'all',
          lazyRender: true,
          mode: 'local',
          width: 80,
          value: 'N',
          store: new Ext.data.ArrayStore({
            fields: ['value', 'displayText'],
            data: [
              ['', '전체'],
              ['N', '방영'],
              ['Y', '종방']
            ]
          }),
          valueField: 'value',
          displayField: 'displayText'
        },
        {
          xtype: 'displayfield',
          value: '프로그램명',
          style: {
            'text-align': 'center'
          },
          width: 70
        },
        {
          xtype: 'textfield',
          width: 120,
          submitValue: false,
          enableKeyEvents: true,
          listeners: {
            keydown: function(self, e) {
              if (e.getKey() == e.ENTER) {
                e.stopEvent();

                var search_pgm_nm = self.getValue();
                var useYn = self.ownerCt.items.items[2].getValue();
                var dvsYn = self.ownerCt.items.items[3].getValue();
                var grid = self.ownerCt.ownerCt;

                grid.getStore().load({
                  params: {
                    pgm_nm: search_pgm_nm,
                    dvs_yn: dvsYn,
                    use_yn: useYn
                  }
                });
              }
            }
          }
        },
        {
          xtype: 'aw-button',
          text: '검색',
          iCls: 'fa fa-search',
          listeners: {
            click: function(self, e) {
              var useYn = self.ownerCt.items.items[2].getValue();
              var dvsYn = self.ownerCt.items.items[3].getValue();
              var search_pgm_nm = self.ownerCt.items.items[5].getValue();
              var grid = self.ownerCt.ownerCt;

              grid.getStore().load({
                params: {
                  pgm_nm: search_pgm_nm,
                  dvs_yn: dvsYn,
                  use_yn: useYn
                }
              });
            }
          }
        }
      ],
      bbar: {
        xtype: 'paging',
        pageSize: 20,
        displayInfo: true,
        store: this.store
      },
      listeners: {
        viewready: function(self) {},
        rowclick: function(self, rowIndex, e) {
          var records = self.getStore().getAt(rowIndex);
          that._fireSelectEvent(records);
        }
      }
    };

    this.items = [this.listGrid];

    this._fireSelectEvent = function(records) {
      this.fireEvent('selectProgram', this, records);
    };

    Ariel.Nps.BISProgram.superclass.initComponent.call(this);
  }
});
