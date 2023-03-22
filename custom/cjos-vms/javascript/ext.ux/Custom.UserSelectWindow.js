(function () {
  Ext.ns("Custom");
  Custom.UserSelectWindow = Ext.extend(Ext.Window, {
    // properties

    // private variables

    _selected: null,
    _userNameField: null,
    _userListGrid: null,

    constructor: function (config) {
      this.addEvents("ok");

      this.title = "사용자 검색";
      this.width = 600;
      this.minWidth = 600;
      this.modal = true;
      this.height = getSafeHeight(510);
      this.layout = {
        type: "fit"
      };

      this.cls = "dark-window";

      Ext.apply(this, {}, config || {});

      this._initItems(config);

      this.listeners = {
        beforedestroy: function (self) {
          self._userListGrid.un("userselect", self._onUserSelect, self);
          self._userListGrid.un("userdblclick", self._onUserDblClick, self);
        }
      };

      var _this = this;
      setTimeout(function () {
        _this._userNameField.focus();
      }, 200);

      Custom.UserSelectWindow.superclass.constructor.call(this);
    },

    initComponent: function () {
      Custom.UserSelectWindow.superclass.initComponent.call(this);
    },

    clear: function () {
      this._userNameField.setValue("");
      this._userNameField.focus();
      this._userListGrid.clear();
    },

    _initItems: function () {
      var _this = this;

      this._userNameField = new Ext.form.TextField({
        name: "user_query",
        width: 120,
        style: {
          marginTop: "2px"
        },
        listeners: {
          specialkey: function (f, e) {
            if (e.getKey() == e.ENTER) {
              e.stopEvent();
              _this._search();
            }
          }
        }
      });

      this.searchForm = new Ext.form.FormPanel({
        border: false,
        items: [{
          layout: "hbox",
          border: false,
          items: [{
            xtype: "label",
            text: "사용자아이디/명:",
            style: {
              color: "white",
              marginTop: "7px"
            },
            width: 100
          },
          this._userNameField,
          {
            xtype: "spacer",
            width: 10
          },
          {
            xtype: "a-iconbutton",
            text: "조회",
            handler: function (btn) {
              _this._search();
            }
          },
          {
            xtype: "a-iconbutton",
            text: "초기화",
            handler: function (btn) {
              _this.clear();
            }
          }
          ]
        }]
      });
      this._userListGrid = new Custom.UserListGrid({
        height: 300,
        singleSelect: this.singleSelect
      });

      this._userListGrid.on("userselect", this._onUserSelect, this);
      this._userListGrid.on("userdblclick", this._onUserDblClick, this);

      this.items = [{
        xtype: "panel",
        layout: "form",
        autoScroll: true,
        defaults: {
          anchor: "98%",
          align: "stretch"
        },
        border: false,
        bbar: {
          toolbarCls: "dark-toolbar",
          items: [
            "->",
            {
              xtype: "a-iconbutton",
              style: {
                paddingTop: '5px'
              },
              text: "취소",
              handler: function (btn, e) {
                _this.close();
              }
            },
            "->",
            {
              xtype: "a-iconbutton",
              style: {
                paddingTop: '5px'
              },
              text: "확인",
              handler: function (btn, e) {
                _this._fireOkEvent();
              }
            }
          ]
        },
        items: [{
          xtype: "fieldset",
          cls: "dark-fieldset",
          style: {
            marginTop: "10px",
            marginLeft: "10px"
          },
          items: [this.searchForm]
        },
        {
          xtype: "spacer",
          height: 10
        },
        {
          xtype: "fieldset",
          cls: "dark-fieldset",
          style: {
            marginTop: "10px",
            marginLeft: "10px"
          },
          title: "사용자목록",
          items: [this._userListGrid]
        }
        ]
      }];
    },

    _search: function () {
      var values = this.searchForm.getForm().getValues();
      if (Ext.isEmpty(values.user_query)) {
        Ext.Msg.alert('알림', '사용자 아이디 또는 이름을 입력해 주세요.')
        return;
      }
      this._userListGrid.getStore().load({
        params: values
      });
    },

    _onUserSelect: function (grid, r, idx) {
      this._selected = r;
    },

    _onUserDblClick: function (grid, r, idx) {
      this._selected = r;
      this._fireOkEvent();
    },

    _fireOkEvent: function () {
      this.fireEvent("ok", this, this._selected);
    }
  });
  Ext.reg("c-user-select-window", Custom.UserSelectWindow);
})();