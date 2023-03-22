(function () {
  Ext.ns('Custom.Archive');
  Custom.Archive.EventWindow = Ext.extend(Ext.Window, {
    // properties

    // private variables

    _itemGrid: null,
    _taskId: null,

    constructor: function (config) {
      this.addEvents('ok');

      this.title = '이벤트 조회';
      this.width = 750;
      this.minWidth = 750;
      this.modal = true;
      this.height = getSafeHeight(510);
      this.layout = {
        type: 'fit',
      };

      this.closeAction = 'hide';

      //this.cls = 'dark-window';

      Ext.apply(this, {}, config || {});

      this._initItems(config);

      this.listeners = {
        beforedestroy: function (self) {
        }
      };

      Custom.Archive.EventWindow.superclass.constructor.call(this);
    },

    initComponent: function () {
      Custom.Archive.EventWindow.superclass.initComponent.call(this);
    },

    clear: function () {
    },

    _load: function (taskId) {
      this._taskId = taskId;
      this.store.removeAll();
      this.store.reload();
      return taskId;
    },

    _initItems: function () {
      var _this = this;

      this.store = new Ext.data.JsonStore({
        remoteSort: true,
        restful: true,
        idProperty: 'id',
        proxy: new Ext.data.HttpProxy({
          method: "GET",
          url: Custom.Archive.UrlSet.getEvents(_this._taskId),
          type: "rest"
        }),
        remoteSort: true,
        totalProperty: "total",
        root: "data",
        fields: [
          "id",
          "eventDescription",
          "eventSeverity",
          // "eventDate",
          { name: 'eventDateRen', type: 'date', dateFormat: 'YmdHis' },
          "requestId"
        ],
        listeners: {
          beforeload: {
            fn: function (store, options) {
              store.proxy.setUrl(Custom.Archive.UrlSet.getEvents(_this._taskId));
            }
          }
        }
      });
      this.cm = new Ext.grid.ColumnModel({
        defaults: {
          align: "center",
          menuDisabled: true,
          sortable: false
        },
        columns: [
          {
            header: "ID",
            dataIndex: "id"
          },
          {
            header: "심각도",
            dataIndex: "eventSeverity",
            width: 130
          },
          {
            header: "이벤트일시",
            dataIndex: "eventDateRen",
            width: 150,
            //sortable: true,
            renderer: Ext.util.Format.dateRenderer("Y-m-d H:i:s")
          },
          {
            header: "설명",
            dataIndex: "eventDescription",
            width: 250,
            align: "left"
          }]
      });
      this._itemGrid = new Ext.grid.GridPanel({
        height: 300,
        singleSelect: this.singleSelect,
        cm: this.cm,
        store: this.store
      });
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
          //toolbarCls: 'dark-toolbar',
          style: {
            paddingTop: '5px'
          },
          items: [
            '->',
            {
              xtype: 'a-iconbutton',
              text: '닫기',
              handler: function (btn, e) {
                _this.hide();
              },
            }
          ],
        },
        items: [
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
            //title: '이벤트 조회',
            items: [this._itemGrid],
          },
        ],
      },];
    },
    _fireOkEvent: function () {
      this.fireEvent('ok', this, this._selected);
    },
  });
  Ext.reg('c-archive-event-window', Custom.Archive.EventWindow);
})();