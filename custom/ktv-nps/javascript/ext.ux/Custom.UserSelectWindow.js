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

      this.toolbarCls = "dark-toolbar";
      this.fieldsetCls = "dark-fieldset";

      Ext.apply(this, {}, config || {});

      this._initItems(config);

      Ext.apply(this.listeners, {
        beforedestroy: function (self) {
            self._userListGrid.un("userselect", self._onUserSelect, self);
            self._userListGrid.un("userdblclick", self._onUserDblClick, self);
        },
        show: function(self){
            setTimeout(function () {
                self._userNameField.focus();
            }, 200);
        }
      });


      Custom.UserSelectWindow.superclass.constructor.call(this);
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
              text: this.userIdMasking ? "사용자명" : "사용자아이디/명:",
              style: {
                //color: "white",
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
              xtype: "aw-button",
              iCls:'fa fa-search',
              text: "조회",
              handler: function (btn) {
                _this._search();
              }
            },
            {
              xtype: "aw-button",
              iCls:'fa fa-refresh',
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
        singleSelect: this.singleSelect,
        userIdMasking : this.userIdMasking,
      });

      this._userListGrid.on("userselect", this._onUserSelect, this);
      this._userListGrid.on("userdblclick", this._onUserDblClick, this);

      this.items = [{
        xtype: "panel",
        layout: "form",
        //autoScroll: true,
        defaults: {
          anchor: "98%",
          align: "stretch"
        },
        border: false,
        buttons: [
             {
              xtype: "aw-button",
              style: {
                paddingTop: '5px'
              },
              text: "선택",
              iCls:'fa fa-check',
              handler: function (btn, e) {
                _this._fireOkEvent();
              }
            },{
              xtype: "aw-button",
              style: {
                paddingTop: '5px'
              },
              text: "닫기",
              iCls:'fa fa-window-close',
              handler: function (btn, e) {
                _this.close();
              }
            }
        ],
        items: [{
            xtype: "fieldset",
            cls: this.fieldsetCls,
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
            cls: this.fieldsetCls,
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
        if( !this.singleSelect ){            
            this._selected = this._userListGrid.getSelectionModel().getSelections();
        }
      this.fireEvent("ok", this, this._selected);
    }
  });
  Ext.reg("c-user-select-window", Custom.UserSelectWindow);
})();