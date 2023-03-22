(function (args) {

  args = Ext.decode(args);

  var componentString = atob(args.mediaDetailWindowData);
  eval(componentString);

  new Custom.MediaDetailWindow({
    state: {
      contentId: args.contentId,
      previewPath: args.previewPath,
      FPS: args.FPS
    },
    listeners: {
      ready: function (self) {
        self.show();
      }
    }
  });

})('{args}')