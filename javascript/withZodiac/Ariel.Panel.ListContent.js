Ariel.Panel.ListContent = Ext.extend(Ext.Panel, {
  border: false,
  layout: 'fit',
  //region: 'center',
  defaults: {
    split: true
  },

  initComponent: function () {
    this.items = [
      {
        xtype: 'tabpanel',
        region: 'center',
        id: 'tab_content_list',
        initialized: false,
        border: false,
        // cls: 'proxima_media_tabpanel',
        /**
         * activeTab을 사용하면 레이아웃이 깨져서 안쓰는듯 함
         */
        //activeTab: 0,
        listeners: {
          render: function (self) {
          },
          afterrender: function (self) {
            // self.header = false;
            // self.hideTabStripItem(0);
            self.hideTabStripItem(1);

          },
          beforetabchange: function (self, n, c) { },
          tabchange: function (self, p) {
            Ext.Ajax.request({
              url: '/pages/request_zodiac/listContent.php',
              params: {
                tab_id: p.id
              },
              callback: function (opt, success, response) {
                p.removeAll();
                var r = Ext.decode(response.responseText);
                p.add(r);
                p.doLayout();
                var grid_matching, detail_id, action, request_id;
                var params = {};
                if (Ext.getCmp('tab_list').activeTab.id == 'tab_n') {
                  grid_matching = 'grid_article';
                  detail_id = 'grid_detail';
                  action = 'list_detail';
                  request_id = 'artcl_id';
                } else {
                  grid_matching = 'grid_article_q';
                  detail_id = 'grid_detail_q';
                  action = 'list_rundown_maching';
                  request_id = 'rd_id';
                }

                var grid_detail = Ext.getCmp(detail_id);
                if (grid_detail) {
                  if (p.id == 'tab_graphic') {
                    grid_detail.getColumnModel().setHidden(5, true);
                  } else {
                    grid_detail.getColumnModel().setHidden(5, false);
                  }
                }

                if (grid_detail) {
                  grid_detail.setTitle(p.title + _text('MN02107')); //매칭 항목
                  var grid_article = Ext.getCmp(grid_matching);
                  if (grid_article.getSelectionModel().getSelected()) {
                    params.action = action;
                    params.artcl_id = grid_article
                      .getSelectionModel()
                      .getSelected()
                      .get(request_id);
                    params.rd_seq = grid_article
                      .getSelectionModel()
                      .getSelected()
                      .get('rd_seq');
                    params.type_content = p.id;
                  } else {
                    params.action = action;
                    params.artcl_id = '';
                    params.rd_seq = '';
                    params.type_content = p.id;
                  }

                  grid_detail.getStore().load({
                    params: params
                  });
                }
                initialized = true;
              }
            });
          }
        },
        items: [
          {
            title: _text('MN02087'), //'비디오'
            id: 'tab_video',
            content_type: '001',
            layout: 'fit',
            border: false,
            reload: function (args) { },
          },
          {
            title: _text('MN02088'), //'그래픽'
            id: 'tab_graphic',
            content_type: '002',
            layout: 'fit',
            border: false,
            reload: function (args) { }
          }
        ]
      }
    ];

    this.listeners = {
      activate: function (p) {
        p.setActiveTab(0);
      },
      tabchange: function (self, tab) {
        tab
          .get(1)
          .getStore()
          .load();
      }
    };

    Ariel.Panel.ListContent.superclass.initComponent.call(this);
  }
});
