(function () {
  Ext.ns("Custom");
  Custom.DetailSearchPanel = Ext.extend(Ext.form.FormPanel, {
    // properties
    region: "north",
    layout: "fit",
    height: 150,
    autoScroll: false,
    border: false,
    bodyStyle: "padding:5px",
    cls: "dark-panel",

    // callback function
    onSearch: null,

    constructor: function (config) {
      Ext.apply(this, {}, config || {});

      this._initItems();

      Custom.DetailSearchPanel.superclass.constructor.call(this);
    },

    getFilteredConditions: function () {
      var conditions = this.getForm().getValues();
      conditions.use = this._useCombo.getValue();
      conditions.aspect_ratio = this._aspectRatioCombo.getValue();

      conditions.channel_code = this._channelField.getChannelCombo().getValue();


      var keys = Object.keys(conditions);
      var filteredConditions = {};
      Ext.each(keys, function (key) {
        if (Ext.isEmpty(conditions[key])) {
          return;
        }
        filteredConditions[key] = conditions[key];
      });

      return filteredConditions;
    },

    _initItems: function () {
      var _this = this;

      var videoField = this._makeVideoField();

      var itemField = this._makeItemField();

      this._useCombo = this._makeUseCombo();
      this._aspectRatioCombo = this._makeAspectRatioCombo();

      this._updaterField = this._makeUpdaterCombo();

      this._channelField = new Custom.ChannelField({
        fieldLabel: '동영상 제작채널'
      });

      this.items = [{
        layout: "column",
        border: false,
        items: [{
            width: 500,
            layout: "form",
            border: false,
            defaults: {
              anchor: "100%"
            },
            items: [{
                xtype: "spacer",
                height: 5
              },
              // 등록일
              {
                xtype: "compositefield",
                fieldLabel: "등록일",
                items: [{
                    xtype: "datefield",
                    width: 120,
                    format: "Y-m-d",
                    altFormats: "Y-m-d|Ymd|YmdHis",
                    name: "from_date",
                    value: new Date().add(Date.DAY, -30)
                  },
                  {
                    xtype: "label",
                    style: {
                      color: "white",
                      marginTop: "5px"
                    },
                    text: " ~"
                  },
                  {
                    xtype: "datefield",
                    width: 120,
                    altFormats: "Y-m-d|Ymd|YmdHis",
                    format: "Y-m-d",
                    name: "to_date",
                    value: new Date()
                  }
                ]
              },
              {
                xtype: "spacer",
                height: 5
              },
              // 동영상 코드/명
              videoField,
              {
                xtype: "spacer",
                height: 5
              },
              // 동영상 제작 채널
              this._channelField,
              {
                xtype: "spacer",
                height: 5
              },
              // 상품코드/명
              itemField
            ]
          },
          {
            width: 350,
            layout: "form",
            border: false,
            items: [{
                xtype: "spacer",
                height: 5
              },
              // 동영상명
              {
                xtype: "textfield",
                fieldLabel: "동영상명",
                name: "title",
                width: 250
              },
              {
                xtype: "spacer",
                height: 5
              },
              // 동영상 비율
              this._aspectRatioCombo,
              {
                xtype: "spacer",
                height: 5
              },
              // 사용여부
              this._useCombo,
              {
                xtype: "spacer",
                height: 5
              },
              // 수정자
              this._updaterField
            ]
          },
          {
            columnWidth: 1,
            layout: "anchor",
            border: false,
            items: [{
              xtype: "compositefield",
              items: [{
                  xtype: "spacer",
                  width: 10
                },
                {
                  xtype: "a-iconbutton",
                  text: "조회",
                  handler: function (btn, e) {

                    if (_this.onSearch) {
                      var filteredConditions = _this.getFilteredConditions();
                      _this.onSearch(_this, filteredConditions);
                    }
                  }
                },
                {
                  xtype: "a-iconbutton",
                  text: "초기화",
                  handler: function (btn, e) {
                    _this.getForm().setValues({
                      aspect_ratio: '',
                      channel_code: '',
                      item_code: '',
                      item_name: '',
                      updater_id: '',
                      updater_name: '',
                      pgm_code: '',
                      pgm_name: '',
                      from_date: new Date().add(Date.DAY, -30),
                      to_date: new Date(),
                      title: '',
                      use: '',
                      video_code: '',
                      video_name: ''
                    });
                    _this.doLayout();
                    if (_this.onSearch) {
                      var filteredConditions = _this.getFilteredConditions();
                      _this.onSearch(_this, filteredConditions);
                    }
                  }
                }
              ]
            }]
          }
        ]
      }];
    },

    _makeUseCombo: function () {
      var useCombo = new Ext.form.ComboBox({
        fieldLabel: "사용여부",
        name: "use",
        mode: "local",
        displayField: "display",
        valueField: "value",
        editable: false,
        triggerAction: "all",
        value: "",
        store: new Ext.data.ArrayStore({
          fields: ["value", "display"],
          data: [
            ["", "선택"],
            ["사용", "사용"],
            ["미사용", "미사용"]
          ]
        }),
        width: 130
      });

      return useCombo;
    },

    _makeAspectRatioCombo: function () {
      var aspectRatioCombo = new Ext.form.ComboBox({
        fieldLabel: "동영상 비율",
        name: "aspect_ratio",
        mode: "local",
        displayField: "display",
        valueField: "value",
        editable: false,
        triggerAction: "all",
        value: "",
        store: new Ext.data.ArrayStore({
          fields: ["value", "display"],
          data: [
            ["", "선택"],
            ["16:9", "16:9(가로형)"],
            ["1:1", "1:1(정방형)"],
            ["9:16", "9:16(세로형)"]
          ]
        }),
        width: 130
      });

      return aspectRatioCombo;
    },

    _makeVideoField: function () {
      var videoField = new Custom.PopupSearchField({
        fieldLabel: "동영상코드/명",
        codeFieldName: "video_code",
        nameFieldName: "video_name",
        width: 300,
        codeKey: "video_code",
        nameKey: "video_name",
        createSearchWindow: function () {
          return new Custom.VideoSelectWindow();
        }
      });
      return videoField;
    },

    _makeItemField: function () {
      var itemField = new Custom.PopupSearchField({
        codeFieldName: "item_code",
        nameFieldName: "item_name",
        fieldLabel: "상품코드",
        width: 300,
        codeFieldWidth: 70,
        nameFieldWidth: 250,
        codeKey: "itemCd",
        nameKey: "itemNm",
        createSearchWindow: function () {
          return new Custom.ItemSelectWindow({
            singleSelect: true
          });
        }
      });
      return itemField;
    },

    _makeUpdaterCombo: function () {
      var updaterField = new Custom.PopupSearchField({
        fieldLabel: "수정자",
        codeFieldName: "updater_id",
        nameFieldName: "updater_name",
        codeFieldWidth: 130,
        nameFieldWidth: 80,
        codeKey: "user_id",
        nameKey: "user_nm",
        createSearchWindow: function () {
          return new Custom.UserSelectWindow({
            singleSelect: true
          });
        }
      });
      return updaterField;
    }
  });

  Ext.reg("c-detail-search-panel", Custom.DetailSearchPanel);
})()