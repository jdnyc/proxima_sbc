(function() {
  Ext.ns("Custom");
  Custom.ProgramCombo = Ext.extend(Ext.form.CompositeField, {
    // Properties
    fieldLabel: null,
    showType: "window",
    mapList: null, //조회 필드명 매핑 목록 객체
    _codeField: null, //코드 필드
    _textField: null, //프로그램명 필드
    _searchButton: null, //검색 버튼
    listeners: {
      afterrender: function(self) {},
      change: function(self, newValue, oldValue) {}
    },
    constructor: function(config) {
      this.addEvents("select");
      Ext.apply(this, {}, config || {});

      Custom.ProgramCombo.superclass.constructor.call(this);
    },

    initComponent: function() {
      this._initItems();
      Custom.ProgramCombo.superclass.initComponent.call(this);
    },
    setValue: function(v) {
      //재정의
      this.value = v;
      if (this.rendered) {
        this.el.dom.value = Ext.isEmpty(v) ? "" : v;
        this.validate();
      }
      this._textField.setValue(v);
      return this;
    },

    /**
     * 필드 생성
     */
    _initItems: function() {
      var _this = this;

      // if( Ext.isEmpty(_this.mapList) ){
      //     _this.mapList = {
      //         // 'pgm_id' : 'progrm_code',
      //         // 'pgm_nm' : 'progrm_nm',
      //         // 'epsd_no' : 'tme_no',
      //         // 'keyword' : 'kwrd',//키워드
      //         // 'epsd_nm' : 'subtl',//부제
      //         // 'main_role' : 'cast',//출연자
      //         // 'info2' : 'cn',//내용
      //         // 'makepd' : 'prod_pd_nm',//pd
      //         // 'prd_clf' : 'prod_se',
      //         // //'delib_grd' : 'watgrad',
      //         // 'rec_place' : 'shooting_place'//촬영장소
      //     };
      // }

      _this.items = [];

      _this._codeField = new Ext.form.TextField({
        submitValue: false,
        xtype: "textfield",
        name: "c-pgm-id",
        readOnly: true,
        width: 120
      });

      _this._textField = new Ext.form.TextField({
        //submitValue: false,
        xtype: "textfield",
        name: _this.name,
        value: _this.value,
        flex: 0.4
      });

      _this._searchButton = {
        width: "120",
        xtype: "aw-button",
        scale: "small",
        iCls: "fa fa-search",
        text: "프로그램 조회",
        handler: function(b, e) {
          var win = new Custom.BISProgramWindow({
            width: 620,
            height: 750,
            listeners: {
              ok: function(self, sel) {
                var newValues = _this._fieldMap(sel);

                _this.fireEvent("select", _this, newValues);

                if (_this.ownerCt && _this.ownerCt.getXType() == "form") {
                  _this.ownerCt.getForm().setValues(newValues);
                } else if (
                  _this.ownerCt.ownerCt &&
                  _this.ownerCt.ownerCt.getXType() == "form"
                ) {
                  _this.ownerCt.ownerCt.getForm().setValues(newValues);
                } else {
                  _this._codeField.setValue(newValues.pgm_id);
                  _this._textField.setValue(newValues.pgm_nm);
                }
              }
            }
          }).show();
        }
      };

      //this.items.push(this._codeField);
      this.items.push(this._textField);
      this.items.push(this._searchButton);
    },
    _fieldMap: function(values) {
      if (Ext.isEmpty(this.mapList)) {
        return values;
      }

      var returnValue = {};
      var valueKeys = this._objectKeys(values);
      for (var i = 0; i < valueKeys.length; i++) {
        var key = valueKeys[i];
        var newKey = this.mapList[key];
        if (!Ext.isEmpty(newKey)) {
          returnValue[newKey] = values[key];
        }
      }
      return returnValue;
    },
    _objectKeys: function(obj) {
      var hasOwnProperty = Object.prototype.hasOwnProperty,
        hasDontEnumBug = !{ toString: null }.propertyIsEnumerable("toString"),
        dontEnums = [
          "toString",
          "toLocaleString",
          "valueOf",
          "hasOwnProperty",
          "isPrototypeOf",
          "propertyIsEnumerable",
          "constructor"
        ],
        dontEnumsLength = dontEnums.length;

      if (
        typeof obj !== "function" &&
        (typeof obj !== "object" || obj === null)
      ) {
        throw new TypeError("Object.keys called on non-object");
      }

      var result = [],
        prop,
        i;

      for (prop in obj) {
        if (hasOwnProperty.call(obj, prop)) {
          result.push(prop);
        }
      }

      if (hasDontEnumBug) {
        for (i = 0; i < dontEnumsLength; i++) {
          if (hasOwnProperty.call(obj, dontEnums[i])) {
            result.push(dontEnums[i]);
          }
        }
      }
      return result;
    }
  });

  Ext.reg("c-program-search", Custom.ProgramCombo);
})();
