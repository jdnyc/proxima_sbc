/**
 * Ext.ux.grid.PageSizer
 * Copyright (c) 2009-2010, José Alfonso Dacosta Dominguez (galdaka@hotmail.com)
 *
 * Ext.ux.grid.PageSizer is licensed http://creativecommons.org/licenses/by-nc/3.0/ license.
 *
 * Commercial use is prohibited. contact with galdaka@hotmail.com
 * if you need to obtain a commercial license.
 *
 *  Site: www.jadacosta.es
 */

Ext.namespace('Ext.ux.grid');
Ext.ux.grid.PageSizer = Ext.extend(Ext.CycleButton, {
  initialSize: this.initialSize || 35,
  pageSizes: this.pageSizes || [10, 15, 20, 35, 50, 100, 200],
  addText: '&nbsp;' + _text('MN00124'),

  initComponent: function() {
    var ir = [];
    var at = this.addText;
    var is = this.initialSize;
    Ext.each(this.pageSizes, function(ps) {
      ir.push({
        text: '&nbsp;' + ps + at,
        value: ps,
        checked: ps == is ? true : false
      });
    });
    Ext.apply(this, {
      showText: true,
      prependText: '&nbsp;',
      forceIcon: 'myImg01',
      items: ir
    });

    Ext.ux.grid.PageSizer.superclass.initComponent.apply(this, arguments);
  },
  init: function(pagingToolbar) {
    pagingToolbar.on('render', this.onInitView, this);
  },
  onInitView: function(pagingToolbar) {
    pagingToolbar.insert(12, this);
    //pagingToolbar.insert(12, '-');
    this.on('change', this.onPageSizeChanged, pagingToolbar);
  },
  onPageSizeChanged: function(cycleButton) {
    this.pageSize = parseInt(cycleButton.getActiveItem().value);
    this.doLoad(0);
  }
});
