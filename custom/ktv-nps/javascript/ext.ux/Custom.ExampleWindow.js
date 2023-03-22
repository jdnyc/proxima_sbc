(function () {
  Ext.ns("Custom");
  Custom.ExampleWindow = Ext.extend(Ext.Window, {
    // properties

    // private variables

    _selected: null,
    _userNameField: null,
    _userListGrid: null,
    constructor: function (config) {
      this.addEvents("ok");

      this.title = "Example";
      this.width = 600;
      this.minWidth = 510;
      this.modal = true;
      this.top = 100;
      this.height = getSafeHeight(510);
      this.layout = {
        type: "fit"
      };

      Ext.apply(this, {}, config || {});

      this._initItems(config);

      Ext.apply(this.listeners, {
        beforedestroy: function (self) {
        },
        show: function(self){

        }
      });


      Custom.ExampleWindow.superclass.constructor.call(this);
    },

    initComponent: function (config) {

      Custom.ExampleWindow.superclass.initComponent.call(this);
    },

    _initItems: function () {
    }
  });
  Ext.reg("c-example-window", Custom.ExampleWindow);
})();