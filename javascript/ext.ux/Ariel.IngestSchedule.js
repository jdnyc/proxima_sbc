Ext.ns("Ariel");
Ariel.IngestSchedule = Ext.extend(Ext.Panel, {
  layout: "fit",
  autoScroll: true,
  isLoading: false,
  setLoading: function(isLoading) {
    this.isLoading = isLoading;
    return true;
  },
  getLoading: function() {
    return this.isLoading;
  },
  initComponent: function(config) {
    Ext.apply(this, config || {});
    var that = this;

    Ariel.IngestSchedule.superclass.initComponent.call(this);
  },
  //메인 메뉴에서 화면 활성화시 동작
  _onShow: function() {
    var that = this;
    //처음
    // if (this.activeTab == undefined) {
    // 	this.setActiveTab(0);
    // } else {
    // 	//활성탭 있는경우 리로드만
    // 	this._activeStoreReload();
    // }

    if (that.getLoading()) {
      return;
    }
    that.setLoading(true);

    if (that.items.length == 0) {
      var url = "/pages/menu/config/ingest_schedule/ingest_schedule.php";

      Ext.Ajax.request({
        url: url,
        timeout: 0,
        callback: function(opts, success, response) {
          try {
            var obj = Ext.decode(response.responseText);
            that.removeAll(true);
            that.add(obj);
            that.doLayout();
            that.setLoading(false);
          } catch (e) {
            Ext.Msg.alert(e["name"], opts.url + "<br />" + e["message"]);
          }
        }
      });
    } else {
      that.get(0).store.reload();
    }
  }
});
