Ext.ns('Script');
Script = Ext.extend(Object, {
  ver: '3.0.4',
  numberingScripts: function(scripts) {
    var newScripts = [];
    var v = this.ver;
    Ext.each(scripts, function(path) {
      var vPath = path + '?v=' + v;
      newScripts.push(vPath);
    });
    return newScripts;
  },
  numberingScript: function(script) {
    var v = this.ver;
    var newScript = script + '?v=' + v;
    return newScript;
  }
});

Script = new Script();
