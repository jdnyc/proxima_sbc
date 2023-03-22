Ariel.Nps.CheckRequest = Ext.extend(Ext.Panel, {
  layout: 'border',
  border: false,
  autoScroll: true,

  initComponent: function(config) {
    Ext.apply(this, config || {});
    var that = this;
    this.items = [
      {
        id: 'request_tp',
        xtype: 'treepanel',
        region: 'west',
        //title: '<center><span class="user_span"><span class="icon_title"><i class=""></i></span><span class="user_title">'+_text('MN02091')+'</span></span></center>',
        width: 178,
        //split: true,
        //collapsible: true,
        //autoScroll: true,
        rootVisible: false,
        border: false,
        bodyStyle: {
          'background-color': '#eaeaea',
          overflow: 'hidden',
          'border-right': '1px solid #d0d0d0'
        },
        boxMinWidth: 178,
        cls: 'tree_menu',
        lines: false,
        listeners: {
          afterrender: function(self) {
            // Ext.Ajax.request({
            //   url: "/store/request_zodiac/request_list.php",
            //   params: {
            //     action: "get_total_new_count"
            //   },
            //   callback: function (opts, success, response) {
            //     try {
            //       var r = Ext.decode(response.responseText, true);
            //       if (r.success) {
            //         var total_new_request_count = r.total_new_request_count;
            //         var total_new_video_count = r.total_new_video_count;
            //         var total_new_graphic_count = r.total_new_graphic_count;
            //         if (!Ext.isEmpty(Ext.getCmp("total_new_request"))) {
            //           if (total_new_request_count > 0) {
            //             Ext.fly("total_new_request").dom.innerHTML =
            //               '<font size=2px color="#FFFFFF" class="new_count_icon"><b>&nbsp' +
            //               total_new_request_count +
            //               "&nbsp</b></font>";
            //           } else {
            //             Ext.fly("total_new_request").dom.innerHTML = "";
            //           }
            //         }
            //         if (!Ext.isEmpty(Ext.getCmp("total_new_request_video"))) {
            //           if (total_new_video_count > 0) {
            //             Ext.fly("total_new_request_video").dom.innerHTML =
            //               '<font size=2px color="#FFFFFF" class="new_count_icon"><b>&nbsp' +
            //               total_new_video_count +
            //               "&nbsp</b></font>";
            //           } else {
            //             Ext.fly("total_new_request_video").dom.innerHTML = "";
            //           }
            //         }
            //         if (!Ext.isEmpty(Ext.getCmp("total_new_request_graphic"))) {
            //           if (total_new_graphic_count > 0) {
            //             Ext.fly("total_new_request_graphic").dom.innerHTML =
            //               '<font size=2px color="#FFFFFF" class="new_count_icon"><b>&nbsp' +
            //               total_new_graphic_count +
            //               "&nbsp</b></font>";
            //           } else {
            //             Ext.fly("total_new_request_graphic").dom.innerHTML = "";
            //           }
            //         }
            //       } else {
            //         Ext.Msg.alert(_text("MN00022"), r.msg);
            //       }
            //     } catch (e) {
            //       Ext.Msg.alert(_text("MN01098"), e.message);
            //     }
            //   }
            // });
          },
          click: function(node, e) {
            var url = node.attributes.url;
            if (!url) return;

            Ext.Ajax.request({
              url: url,
              timeout: 0,
              callback: function(opts, success, response) {
                try {
                  Ext.getCmp('admin_request_contain').removeAll(true);

                  Ext.getCmp('admin_request_contain').add(
                    Ext.decode(response.responseText)
                  );
                  Ext.getCmp('admin_request_contain').setTitle(
                    node.attributes.title
                  );
                  Ext.getCmp('admin_request_contain').doLayout();
                } catch (e) {
                  Ext.Msg.alert(e['name'], opts.url + '<br />' + e['message']);
                }
              }
            });
          }
        },
        root: {
          text: _text('MN02091'),
          expanded: true,
          children: [
            {
              text:
                '<span style="position:relative;top:3px;"><i class="fa fa-play" style="font-size:18px;"></i></span>&nbsp;' +
                _text('MN02087') +
                '&nbsp;&nbsp;<span id="total_new_request_video"></span>',
              title:
                '<span class="user_span"><span class="icon_title"><i class="fa fa-play"></i></span><span class="main_title_header">' +
                _text('MN02087') +
                '</span></span>',
              //title: _text('MN02087'),
              url: '/pages/request_zodiac/requestLlist.php?t=video',
              expend: true,
              leaf: true
            },
            {
              text:
                '<span style="position:relative;top:3px;"><i class="fa fa-image" style="font-size:18px;"></i></span>&nbsp;' +
                _text('MN02088') +
                '&nbsp;&nbsp;<span id="total_new_request_graphic"></span>',
              title:
                '<span class="user_span"><span class="icon_title"><i class="fa fa-image"></i></span><span class="main_title_header">' +
                _text('MN02088') +
                '</span></span>',
              //title: _text('MN02088'),
              url: '/pages/request_zodiac/requestLlist.php?t=graphic',
              expend: true,
              leaf: true
            }
          ]
        }
      },
      {
        region: 'center',
        id: 'admin_request_contain',
        //title: '&nbsp;',
        border: false,
        headerAsText: false,
        layout: 'fit'
      }
    ];

    Ariel.Nps.CheckRequest.superclass.initComponent.call(this);
  }
});
