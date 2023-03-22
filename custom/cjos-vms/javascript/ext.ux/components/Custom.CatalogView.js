(function () {
  Ext.ns("Custom");
  Custom.CatalogView = Ext.extend(Ext.DataView, {
    // Properties
    id: 'catalog-view',
    tpl: '',
    autoScroll: true,
    overClass: 'x-view-over',
    selectedClass: 'x-view-selected',
    itemSelector: 'div.thumb-wrap',
    padding: 5,
    emptyText: _text('MSG00207'),
    singleSelect: true,
    //contextmenu: true,
    state: {
      contentId: null,
      FPS: 29.97
    },
    // private variables

    constructor: function (config) {

      // console.log('config state in catalog view', config.state);

      Ext.apply(this, {}, config || {});

      // console.log('this state in catalog view', this.state);

      this._init();

      Custom.CatalogView.superclass.constructor.call(this);
    },

    initComponent: function () {
      Custom.CatalogView.superclass.initComponent.call(this);
    },

    listeners: {
      render: function (self) {
        self.getStore().load({
          params: {
            content_id: self.state.contentId
          }
        });
      },
      selectionchange: function (self, selections) {
        if (!selections.length) {
          return;
        }
        var frames = self.getRecord(selections[0]).get('start_frame');
        var sec = frames / self.state.FPS;

        var proxyPlayer = videojs(document.getElementById('proxy-player'), {}, function () {});
        proxyPlayer.currentTime(sec);
      },
      dblclick: function (self, index, node, e) {
        if (index < 0) {
          return;
        }

        var record = self.getStore().getAt(index);
        var url = record.get('url');
        if (url) {
          Ext.getCmp('usr_meta__poster').setValue(url);
        }
      }
    },

    setTemplate: function (template) {
      this.tpl = template;
      this.refresh();
    },

    _init: function () {
      this.store = Custom.Store.getCatalogStore();
      this.tpl = this._makeTemplate();
    },

    _makeTemplate: function () {
      var tpl = new Ext.XTemplate(
        '<tpl for=".">',
        '<div style="display: inline-block;">',
        '<div class="thumb-wrap" id="s-catalog-{sort}">',
        '<tpl if="(is_poster == 1)">',
        '<i class="fa fa-square fa-stack-1x icon_square_poster"></i>',
        '<i class="fa fa-picture-o fa-stack-1x fa-inverse icon_file_poster" title="Poster"></i>',
        '</tpl>',
        '<div class="thumb"><img class="thumb_img_storyboard_dragable" start_frame="{start_frame}" src="{url}"></div>',
        '<span class="x-editable">{start_tc}</span>',
        '</div>',
        '</div>',
        '</tpl>',
        '<div class="x-clear"></div>'
      );
      return tpl;
    }

  });

  Ext.reg("c-catalog-view", Custom.CatalogView);
})();