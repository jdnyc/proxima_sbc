(function () {
  Ext.ns('Custom');

  Custom.MediaDetailWindow = Ext.extend(Ext.Window, {
    // properties
    id: 'winDetail',
    modal: true,
    layout: 'fit',
    width: Ext.getBody().getViewSize().width * 0.99,
    height: Ext.getBody().getViewSize().height * 0.99,
    draggable: false, //prevent move

    //
    state: {
      contentId: null,
      FPS: 29.97
    },

    // private variables
    _components: [
      'Custom.VideoPlayerPanel',
      'Custom.MetadataPanel',
      'Custom.CatalogView',
      'Custom.PosterPanel',
      'Custom.ItemMatchListGrid',
      'Custom.TagLabel',
      'Custom.TagField',
      'Custom.TagPanel',
      'Custom.RadioGroup',
      'Custom.CheckboxGroup',
      'Custom.ImageUploadField',
    ],

    listeners: {
      render: function (self) {
        self.mask.applyStyles({
          opacity: '0.5',
          'background-color': '#000000',
        });
      },

      show: function (win) {
        // document.onkeyup = function (evt) {
        //   evt = evt || window.event;
        //   if (evt.keyCode == 27) {
        //     if (!Ext.isEmpty(player3)) {
        //       if (!player3.isFullscreen()) {
        //         win.close();
        //       }
        //     } else {
        //       win.close();
        //     }
        //   }
        // };
      },
    },

    constructor: function (config) {
      this.addEvents('ready');

      // console.log('config state', config.state);

      Ext.apply(this, {}, config || {});
      // 1366x768 기준 너비가 최소 1350이라고 생각하자

      // console.log('this.state', this.state)

      Custom.MediaDetailWindow.superclass.constructor.call(this);
    },

    initComponent: function (config) {
      Ext.apply(this, config || {});

      Ext.QuickTips.init();

      var _this = this;
      Ext.Loader.load(getComponentUrls(_this._components), function () {
        _this._initItems();

        Custom.MediaDetailWindow.superclass.initComponent.call(_this);

        _this.fireEvent('ready', _this);
      });
    },

    _initItems: function () {
      var _this = this;

      this._player = this._makeVideoPlayerPanel();

      this._metaPanel = this._makeMetadataPanel({
        contentId: this.state.contentId,
      });

      this._catalogView = this._makeCatalogView({
        contentId: this.state.contentId,
        FPS: this.state.FPS
      });

      this.items = {
        border: false,
        layout: 'border',
        items: [{
            region: 'center',
            bodyStyle: 'border:0px;background-color:#1e1e1e;',
            layout: 'border',
            items: [this._player, this._catalogView],
          },
          this._metaPanel,
        ],
      };
    },

    _makeVideoPlayerPanel: function (options) {
      var _this = this;
      var videoPlayerPanel = new Custom.VideoPlayerPanel({
        region: 'north',
        listeners: {
          render: function () {
            var panelWidth = videoPlayerPanel.getEl().dom.clientWidth;
            videoPlayerPanel.height = _this._calcVideoPanelHeight(panelWidth);
          },
        },
      });
      videoPlayerPanel.on('playerready', function () {
        //_this._player.setSrc('https://sample-videos.com/video123/mp4/720/big_buck_bunny_720p_1mb.mp4');
        _this._player.setSrc(_this.state.previewPath);
      });
      return videoPlayerPanel;
    },

    _makeMetadataPanel: function (state) {
      var _this = this;
      var metadataPanel = new Custom.MetadataPanel({
        region: 'east',
        width: 900,
        state: state,
      });
      return metadataPanel;
    },

    _makeCatalogView: function (state) {
      var _this = this;
      var catalogView = new Custom.CatalogView({
        region: 'center',
        state: state,
      });
      return catalogView;
    },

    _calcVideoPanelHeight: function (panelWidth, videoWidth, videoHeight) {
      if (!videoWidth) {
        videoWidth = 16;
      }

      if (!videoHeight) {
        videoHeight = 9;
      }

      var ratio = videoHeight / videoWidth;
      return panelWidth * ratio;
    },
  });

  Ext.reg('c-media-detail-window', Custom.MediaDetailWindow);
})();