
Ext.ns('Custom');
Custom.Category = Ext.extend(Ext.Panel, {
  title: '<span class="user_span"><span class="icon_title"><i class="fa fa-list"></i></span><span class="user_title" style="color: #ffffff">' + _text('MN00321') + '</span></span>',
  id: 'west_menu_item_media_category',
  cls: 'west-menu-item-media',
  anchor: '95% 75%',
  layout: 'fit',
  collapsible: true,
  titleCollapse: true,
  hideCollapseTool: true,
  split: true,
  items: [
    new Ariel.nav.MainPanel({
      useWholeCategory: false
    })
  ],
  listeners: {
    collapse: function (p) {
      p.ownerCt.doLayout();
    },
    expand: function (p) {
      p.ownerCt.doLayout();
    }
  }
});

