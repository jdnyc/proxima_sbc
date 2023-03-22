Ext.ns('Custom.Archive');
Custom.Archive.UrlSet = new Ext.extend(Object, {
  version: '/api/v1/',
  dtlArchive: 'dtl-archive',


  getEvents: function (taskId) {
    return this.version + this.dtlArchive + '/' + taskId + '/events';
  },
});
Custom.Archive.UrlSet = new Custom.Archive.UrlSet();