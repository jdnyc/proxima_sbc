(function () {
  Ext.ns('Custom');
  Custom.RadioGroup = Ext.extend(Ext.form.RadioGroup, {
    // Properties
    labelStyle: 'color: white',
    style: {
      color: 'white',
    },
    invalidClass: '',

    itemList: '',
    defaultColumnWidth: null,
    columns: null,
    // private variables

    constructor: function (config) {
      Ext.apply(this, {}, config || {});

      if (this.defaultColumnWidth === null) {
        this.columns = 'auto';
      }
      this._initItems();

      Custom.RadioGroup.superclass.constructor.call(this);
    },

    initComponent: function () {
      Custom.RadioGroup.superclass.initComponent.call(this);
    },

    _initItems: function () {
      if (!Ext.isEmpty(this.itemList)) {
        var items = Ext.decode(this.itemList);
        if (this.columns === null && this.defaultColumnWidth) {
          var columns = [];
          var _this = this;
          Ext.each(items, function (item) {
            columns.push(_this.defaultColumnWidth);
          });
          this.columns = columns;
        }
        this.items = items;
      }
    }
  });

  Ext.reg('c-radiogroup', Custom.RadioGroup);
})();