/**
 * 2015-10-21 proxima_zodiac
 */
(function () {
  Ext.ns("Ariel.Panel");

  // 영상 매핑
  Ariel.Panel.InfoReport = Ext.extend(Ext.Panel, {
    layout: "border",
    border: false,

    initComponent: function (config) {
      var that = this;
      var width_combo = 95;
      var width_label = 60;

      Ext.apply(
        this,
        {
          items: [
            {
              split: true,
              cls: "custom-nav-tab",
              margins: "0 0 0 0",
              region: "west",
              layout: {
                type: "fit",
                align: "stretch",
                pack: "start"
              },
              width: 550,
              plain: true,
              border: false,
              //frame : false,
              items: [new Ariel.Panel.ListContent()]
            },
            {
              region: "center",
              border: false,
              bodyStyle: "border-left: 1px solid #d0d0d0",
              //frame : false,
              layout: {
                type: "fit",
                align: "stretch",
                pack: "start"
              },
              items: [
                {
                  xtype: "tabpanel",
                  id: "tab_list",
                  region: "center",
                  initialized: false,
                  // cls: "proxima_tabpanel_customize",
                  border: false,
                  /**
                   * activeTab을 사용하니 레이아웃이 틀어져서 사용하지 않은것으로 보임
                   */
                  //activeTab: 0,
                  doReload: function () {
                    //Ext.getCmp('tab_list').getActiveTab().get(0).getStore().reload();
                  },
                  listeners: {
                    render: function (self) { },
                    beforetabchange: function (self, n, c) { },
                    tabchange: function (self, p) {
                      if (typeof p.get(0) !== 'undefined') {
                        return;
                      };

                      if (p.id == "tab_n") {
                        var url = "/pages/request_zodiac/listArticle.php";
                      } else {
                        var url = "/pages/request_zodiac/listQlist.php";
                      }
                      Ext.Ajax.request({
                        url: url,
                        params: {
                          tab_id: p.id
                        },
                        callback: function (opt, success, response) {
                          p.removeAll();
                          try {
                            var r = Ext.decode(response.responseText);
                            p.add(r);
                            p.doLayout();

                            self.initialized = true;
                            //r.reload(args);
                          } catch (e) {
                            //console.log(e);
                          }
                        }
                      });
                    }
                  },
                  items: [
                    {
                      title: "기사", //'일반기사',
                      id: "tab_n",
                      tab_type: "001",
                      layout: "fit",
                      border: false,
                      reload: function (args) { }
                    },
                    {
                      title: "큐시트", //'큐시트기사',
                      hidden: true,
                      id: "tab_q",
                      tab_type: "002",
                      layout: "fit",
                      border: false,
                      reload: function (args) { }
                    }
                  ]
                }
              ]
            }
          ]
        },
        config || {}
      );

      Ariel.Panel.InfoReport.superclass.initComponent.call(this);
    }
  });

  Ext.reg("infoReport", Ariel.Panel.InfoReport);
})();
