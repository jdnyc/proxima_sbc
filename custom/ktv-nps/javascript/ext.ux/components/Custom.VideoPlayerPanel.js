(function () {
  Ext.ns("Custom");

  Custom.VideoPlayerPanel = Ext.extend(Ext.BoxComponent, {
    // properties
    player: null,
    videojsReady: false,
    videoUrl: null,
    options: {
      controls: true,
      autoplay: true,
      preload: 'auto',
      playbackRates: [0.5, 1, 1.5, 2]
    },

    html: '<video id="proxy-player" class="video-js" />',

    listeners: {
      beforedestroy: function (self) {
        this.un('afterrender', this._onAfterRender);
        self.player.dispose();
      }
    },

    constructor: function (config) {
      this.addEvents('playerready');

      this.border = false;

      Ext.apply(this, {}, config || {});

      Custom.VideoPlayerPanel.superclass.constructor.call(this);
    },

    initComponent: function () {
      this.on('afterrender', this._onAfterRender);

      Custom.VideoPlayerPanel.superclass.initComponent.call(this);
    },

    setSrc: function (videoUrl) {
      if (Ext.isEmpty(videoUrl)) {
        return;
      }
      // console.log(this);
      this.player.src(videoUrl);
    },

    setFullScreen: function () {

    },

    _onAfterRender: function (self) {
      var _this = this,
        player, el;

      /* get the <video> ele_thisnt */
      el = this.getEl().dom.firstChild;

      player = videojs(el, _this.options);
      if (this.videoUrl) {
        player.src(this.videoUrl);
        _this._setPoster(this);
      }
      player.ready(function () {
        _this.player = this;
        /* set height and width */
        this.width(_this.getWidth());
        this.height(_this.getHeight() - 1);
        _this.videojsReady = true;
        _this.fireEvent('playerready');
      });
    },

    _setPoster: function (player) {
      //player.muted(true);
      player.play().then(function () {
        player.pause();
        player.hasStarted(false);
        //player.muted(false);
      });
    }
  });

  Ext.reg("c-video-player-panel", Custom.VideoPlayerPanel);
})();