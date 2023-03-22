(function() {
  Ext.ns("Custom");
  Custom.BISProgramWindow = Ext.extend(Ext.Window, {
    // properties

    // private variables
    _selected: null,
    _itemCodeField: null,
    _searchForm: null,
    _pgmListGrid: null,

    _config: null,

    constructor: function(config) {
      this.addEvents("ok");

      this.title = "프로그램 조회";
      this.width = 700;
      this.minWidth = 700;
      this.modal = true;
      this.layout = {
        type: "fit"
      };

      this._selected = null;

      this.cls = "dark-window";

      Ext.apply(this, {}, config || {});

      this._initItems();

      Custom.BISProgramWindow.superclass.constructor.call(this);
    },

    initComponent: function(config) {
      this._initItems();

      Ext.apply(this, {}, config || {});
      Custom.BISProgramWindow.superclass.initComponent.call(this);
    },

    setConfig: function(config) {
      this._config = config;
    },

    _onClearSearchForm: function() {
      this._pgmListGrid.clear();
    },

    _initItems: function() {
      var _this = this;

      _this._bisProgram = new Custom.ProgramListGrid({
        height: 220,
        listeners: {
          pgmdblclick: function(self, sel) {},
          pgmselect: function(self, sel) {
            //결과 입력
            var form = _this.getComponent("pgmSearchForm").getForm();
            form.setValues({
              pgm_id: sel.get("pgm_id"),
              pgm_nm: sel.get("pgm_nm")
            });
            //회차 조회
            _this._bisEpisode._setUrlProgram(sel.get("pgm_id"));
            _this._bisEpisode.getStore().load();
          }
        }
      });

      _this._bisEpisode = new Custom.EpisodeListGrid({
        height: 220,
        listeners: {
          epsddblclick: function(self, sel) {
            if (_this._fireOkEvent()) {
              _this.close();
            }
          },
          epsdselect: function(self, sel) {
            //결과 입력
            var form = _this.getComponent("pgmSearchForm").getForm();
            form.setValues({
              epsd_no: sel.get("epsd_no"),
              epsd_nm: sel.get("epsd_nm")
            });
          }
        }
      });

      _this._searchSet = new Ext.form.FieldSet({
        title: "조회결과",
        xtype: "fieldset",
        style: {
          marginTop: "10px",
          marginLeft: "10px"
        },
        items: [
          {
            itemId: "pgm",
            xtype: "compositefield",
            fieldLabel: "프로그램",
            items: [
              {
                xtype: "textfield",
                name: "pgm_id",
                itemId: "pgm_id",
                width: 100,
                readOnly: true
              },
              {
                xtype: "textfield",
                name: "pgm_nm",
                flex: 1,
                readOnly: true
              }
            ]
          },
          {
            xtype: "compositefield",
            fieldLabel: "회차",
            items: [
              {
                xtype: "textfield",
                fieldLabel: "회차",
                width: 100,
                name: "epsd_no",
                readOnly: true
              },
              {
                xtype: "textfield",
                fieldLabel: "부제목",
                name: "epsd_nm",
                flex: 1,
                readOnly: true
              }
            ]
          }
        ]
      });

      this.items = [
        {
          xtype: "form",
          itemId: "pgmSearchForm",
          //layout: 'form',
          autoScroll: true,
          defaults: {
            anchor: "98%",
            align: "stretch"
          },
          border: false,
          busttonAlign: "center",
          buttons: [
            {
              xtype: "aw-button",
              iCls: "fa fa-times",
              text: "닫기",
              handler: function(btn, e) {
                _this.close();
              }
            },
            "->",
            {
              xtype: "aw-button",
              iCls: "fa fa-check-circle",
              text: "선택",
              handler: function(btn, e) {
                if (_this._fireOkEvent()) {
                  _this.close();
                } else {
                  Ext.Msg.alert(
                    "알림",
                    "프로그램과 회차를" + "<br />" + " 선택해주세요."
                  );
                }
              }
            }
          ],
          items: [
            _this._searchSet,
            {
              title: "프로그램",
              xtype: "fieldset",
              cls: "dark-fieldset",
              style: {
                marginTop: "10px",
                marginLeft: "10px"
              },
              items: [_this._bisProgram]
            },
            // {
            //     xtype: 'spacer',
            //     height: 10
            // },
            {
              title: "회차",
              xtype: "fieldset",
              cls: "dark-fieldset",
              style: {
                marginTop: "10px",
                marginLeft: "10px"
              },
              items: [_this._bisEpisode]
            }
          ]
        }
      ];
    },
    _objectAssign: function(target, varArgs) {
      // .length of function is 2
      "use strict";
      if (target == null) {
        // TypeError if undefined or null
        throw new TypeError("Cannot convert undefined or null to object");
      }

      var to = Object(target);

      for (var index = 1; index < arguments.length; index++) {
        var nextSource = arguments[index];

        if (nextSource != null) {
          // Skip over if undefined or null
          for (var nextKey in nextSource) {
            // Avoid bugs when hasOwnProperty is shadowed
            if (Object.prototype.hasOwnProperty.call(nextSource, nextKey)) {
              to[nextKey] = nextSource[nextKey];
            }
          }
        }
      }
      return to;
    },

    _getSelectedRecord: function() {
      var returnSelected = {};
      var pgmSel = this._bisProgram.getSelectionModel().getSelected();
      var epsdSel = this._bisEpisode.getSelectionModel().getSelected();
      if (pgmSel || epsdSel) {
        if (pgmSel && pgmSel.json) {
          var pgmSelData = JSON.parse(JSON.stringify(pgmSel.json));
          returnSelected = this._objectAssign(returnSelected, pgmSelData);
        }
        if (epsdSel && epsdSel.json) {
          var epsdSelData = JSON.parse(JSON.stringify(epsdSel.json));
          returnSelected = this._objectAssign(returnSelected, epsdSelData);
        }
      } else {
        return false;
      }
      // 물리폴더 카테고리 아이디 추가
      var categoryId = pgmSel.data.c_category_id;
      returnSelected.c_category_id = categoryId;

      return returnSelected;
    },

    _fireOkEvent: function() {
      var selected = this._getSelectedRecord();
      if (!selected) {
        return false;
      }
      this.fireEvent("ok", this, selected);
      return true;
    }
  });
  Ext.reg("c-bis-program-window", Custom.BISProgramWindow);
})();
