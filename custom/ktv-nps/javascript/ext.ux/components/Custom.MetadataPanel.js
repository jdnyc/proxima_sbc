(function () {
  Ext.ns('Custom');

  Custom.MetadataPanel = Ext.extend(Ext.TabPanel, {
    // properties
    id: 'detail_panel',
    cls: 'proxima_tabpanel_customize proxima_media_tabpanel',
    title: _text('MN00164'), //!!title: '메타데이터',
    enableTabScroll: true,
    border: false,

    listeners: {
      beforedestroy: function (self) {
        self.un('render', self._onRender);
      }
    },

    //state
    state: {
      contentId: null
    },

    constructor: function (config) {

      Ext.apply(this, {}, config || {});

      //console.log('state', this.state);

      Custom.MetadataPanel.superclass.constructor.call(this);
    },

    initComponent: function () {
      this.on('render', this._onRender);
      Custom.MetadataPanel.superclass.initComponent.call(this);
    },

    loadMetadata: function () {
      var _this = this;
      var loadMask = new Ext.LoadMask(Ext.getBody(), {
        msg: _text('MSG00143')
      });
      loadMask.show();
      Ext.Ajax.request({
        url: '/store/get_detail_metadata.php',
        params: {
          content_id: this.state.contentId
        },
        callback: function (opts, success, response) {
          loadMask.hide();
          if (success) {
            try {
              var r = Ext.decode(response.responseText);

              _this.add(r);
              _this.doLayout();
              _this.activate(0);
            } catch (e) {
              Ext.Msg.alert(e['name'], e['message']);
            }
          } else {
            Ext.Msg.alert(_text('MN00022'), opts.url + '<br />' + response.statusText + '(' + response.status + ')');
          }
        }
      });
    },

    _onRender: function (self) {
      self.loadMetadata();
    }

  });

  Ext.reg('c-metadata-panel', Custom.MetadataPanel);
})();