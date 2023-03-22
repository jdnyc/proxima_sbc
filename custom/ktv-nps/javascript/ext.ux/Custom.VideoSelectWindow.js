(function () {
  Ext.ns("Custom");
  Custom.VideoSelectWindow = Ext.extend(Ext.Window, {
    // properties

    // private variables

    _selected: null,
    _keywordField: null,
    _searchTypeCombo: null,
    _searchResultTable: null,

    constructor: function (config) {
      this.addEvents("ok");

      this.title = "동영상 검색";
      this.width = 820;
      this.minWidth = 720;
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
          self._searchResultTable.un("select", self._onSelect, self);
        }
      };

      // 동영상 기준으로 컬럼 추가
      this._setVideoTableColumns();

      var _this = this;
      setTimeout(function () {
        _this._keywordField.focus();
      }, 200);

      Custom.VideoSelectWindow.superclass.constructor.call(this);
    },

    initComponent: function () {
      Custom.VideoSelectWindow.superclass.initComponent.call(this);
    },

    clear: function () {
      this._keywordField.setValue("");
      this._keywordField.focus();
    },

    _initItems: function () {
      var _this = this;

      this._keywordField = new Ext.form.TextField({
        name: "keyword",
        width: 200,
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

      this._searchTypeCombo = new Ext.form.ComboBox({
        mode: "local",
        name: "type",
        displayField: "display",
        valueField: "value",
        editable: false,
        height: 24,
        triggerAction: "all",
        value: "video_code",
        store: new Ext.data.ArrayStore({
          fields: ["value", "display"],
          data: [
            ["video_code", "동영상코드"],
            ["video_name", "동영상명"],
            ["item_code", "상품코드"]
          ]
        }),
        listeners: {
          select: function (self, record, idx) {
            if (
              record.get("value") === "video_code" ||
              record.get("value") === "video_name"
            ) {
              _this._setVideoTableColumns();
            } else {
              _this._setItemTableColumns();
            }
          }
        },
        width: 130
      });

      this._searchResultTable = new Custom.HtmlTable({
        style: {
          marginLeft: "10px"
        },
        valueFieldName: "video_code"
      });

      this._searchResultTable.on("select", this._onSelect, this);

      this.searchForm = new Ext.form.FormPanel({
        border: false,
        height: 25,
        items: [{
          layout: "hbox",
          border: false,
          defaults: {
            style: {
              paddingTop: "3px",
              height: "19px"
            }
          },
          items: [{
              xtype: "label",
              text: "구분:",
              style: {
                color: "white",
                marginTop: "7px"
              },
              width: 40
            },
            this._searchTypeCombo,
            {
              xtype: "spacer",
              width: 10
            },
            this._keywordField,
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
                _this._searchResultTable.renderTable('');
              }
            }
          ]
        }]
      });

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
          hidden: true,
          toolbarCls: "dark-toolbar",
          style: {
            paddingTop: '5px'
          },
          items: [
            "->",
            {
              xtype: "a-iconbutton",
              text: "취소",
              handler: function (btn, e) {
                _this.close();
              }
            },
            "->",
            {
              xtype: "a-iconbutton",
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
          this._searchResultTable
        ]
      }];
    },

    _setVideoTableColumns: function () {
      // 동영상 기준 컬럼 모델
      this._searchResultTable.setColumns([{
          header: "no",
          dataIndex: "no",
          align: "center"
        },
        {
          header: "동영상코드",
          dataIndex: "video_code",
          align: "center"
        },
        {
          header: "동영상명",
          dataIndex: "video_name"
        },
        {
          header: "동영상비율",
          dataIndex: "aspect_ratio",
          align: "center"
        },
        {
          header: "동영상사용여부",
          dataIndex: "use",
          align: "center"
        },
        {
          header: "상품코드",
          dataIndex: "item_code",
          align: "center"
        },
        {
          header: "상품명",
          dataIndex: "item_name"
        },
        {
          header: "상품상태",
          dataIndex: "item_status",
          align: "center"
        },
        {
          header: "전시순서",
          dataIndex: "display_order",
          align: "center"
        },
        {
          header: "전시여부",
          dataIndex: "display",
          align: "center"
        }
      ]);
    },

    _setItemTableColumns: function () {
      // 상품 기준 컬럼 모델
      this._searchResultTable.setColumns([{
          header: "no",
          dataIndex: "no",
          align: "center"
        },
        {
          header: "상품코드",
          dataIndex: "item_code",
          align: "center"
        },
        {
          header: "상품명",
          dataIndex: "item_name"
        },
        {
          header: "상품상태",
          dataIndex: "item_status",
          align: "center"
        },
        {
          header: "동영상코드",
          dataIndex: "video_code",
          align: "center"
        },
        {
          header: "동영상명",
          dataIndex: "video_name"
        },
        {
          header: "동영상비율",
          dataIndex: "aspect_ratio",
          align: "center"
        },
        {
          header: "동영상사용여부",
          dataIndex: "use",
          align: "center"
        }
      ]);
    },

    _search: function () {
      var values = this.searchForm.getForm().getValues();
      values.type = this._searchTypeCombo.getValue();
      if (Ext.isEmpty(values.keyword)) {
        var msg = '';
        if (values.type === 'video_code') {
          msg = '동영상 코드를 입력해 주세요.';
        } else if (values.type === 'video_name') {
          msg = '동영상 명을 입력해 주세요.';
        } else if (values.type === 'item_code') {
          msg = '상품 코드를 입력해 주세요.';
        }
        Ext.Msg.alert('알림', msg);
        this._keywordField.focus();
        return;
      }

      var res = Custom.Api.searchVideos(
        values,
        function (self, res) {
          self._searchResultTable.renderTable(res.data);
        },
        this
      );
    },

    _onSelect: function (table, r, idx) {
      this._selected = new Ext.data.Record({
        video_code: r.get("video_code"),
        video_name: r.get("video_name")
      });

      // console.log(this._selected);
      // console.log("r", r);
      // console.log("idx", idx);
      this._fireOkEvent();
    },

    _fireOkEvent: function () {
      this.fireEvent("ok", this, this._selected);
    }
  });
  Ext.reg("c-video-select-window", Custom.VideoSelectWindow);
})();