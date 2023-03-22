(function () {
  Ext.ns("Custom");
  Custom.TagLabel = Ext.extend(Ext.Container, {
    // Properties
    tagName: 'Undefined',
    layout: 'toolbar',

    cls: 'tag-draggable',

    // private variables
    _tag: null,
    _closeBtn: null,

    style: {
      backgroundColor: '#00AEEF',
      padding: '3px 2px 3px 8px',
      borderRadius: '15px',
      margin: '0px 5px 5px 5px'
    },

    constructor: function (config) {

      this.addEvents('close');

      Ext.apply(this, {}, config || {});

      this._init();

      Custom.TagLabel.superclass.constructor.call(this);
    },

    initComponent: function () {
      Custom.TagLabel.superclass.initComponent.call(this);
      this._initComponent();
    },

    _init: function () {
      var _this = this;
      // event listener
      this.listeners = {
        render: _this._initDragZone
      }
    },

    _initComponent: function () {
      var _this = this;

      this._tag = new Ext.form.Label({
        text: this.tagName,
        style: {
          cursor: 'move',
          color: 'white',
          fontSize: '12px',
        }
      });

      this._closeBtn = new Ext.Button({
        text: '<i class="fa fa-times-circle" style="font-size: 15px; color: white;"/>',
        cls: 'icon-button',
        style: {
          marginLeft: '5px'
        },
        handler: function (btn, e) {
          _this.fireEvent('close', _this);
        }
      });

      this.add(this._tag);
      this.add(this._closeBtn);
      this.doLayout();
    },

    _initDragZone: function (self) {

      var dragZone = new Ext.dd.DragZone(Ext.getBody(), {
        getDragData: function (e) {

          // .button-draggable == class of the button you want to drag around
          var sourceEl = e.getTarget('.tag-draggable');
          if (sourceEl) {
            var d = sourceEl.cloneNode(true);
            d.id = Ext.id();
            return self.dragData = {
              sourceEl: sourceEl,
              repairXY: Ext.fly(sourceEl).getXY(),
              ddel: d
            }
          }
        },

        onDrag: function (e) {
          // !Important: manually fix the default position of Ext-generated proxy element
          // Uncomment these line to see the Ext issue
          var proxy = Ext.DomQuery.select('*', dragZone.getDragEl());
          proxy[2].style.position = 'relative';
          proxy[2].style.left = 0;
          proxy[2].style.top = 0;
        },

        getRepairXY: function () {
          return dragZone.dragData.repairXY;
        }
      });

      self.dragZone = dragZone;
    }
  });

  Ext.reg("c-tag-label", Custom.TagLabel);
})();