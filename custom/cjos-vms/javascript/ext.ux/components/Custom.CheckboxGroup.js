(function () {
  Ext.ns('Custom');
  Custom.CheckboxGroup = Ext.extend(Ext.form.CheckboxGroup, {
    // Properties
    labelStyle: 'color: white',
    style: {
      color: 'white',
    },
    invalidClass: '',

    itemList: '',
    columns: null,
    defaultColumnWidth: null,
    totalItemName: '', // 전체 기능 체크박스 아이템명(체크 시 다른 체크 해제). 전체 선택 아님
    maxCheckCount: 0,
    // private variables
    _beforeCheckedItems: [],
    _lastCheckedItems: [],

    constructor: function (config) {
      Ext.apply(this, {}, config || {});

      if (this.defaultColumnWidth === null) {
        this.columns = 'auto';
      }

      this._initItems();

      Custom.CheckboxGroup.superclass.constructor.call(this);
    },

    initComponent: function () {
      Custom.CheckboxGroup.superclass.initComponent.call(this);
    },

    setRawValue: function (rawValue) {
      if (!rawValue) {
        return;
      }
      var rawValueList = rawValue.split(',');
      var valueList = [];
      this.items.each(function (checkbox) {
        var checked = false;
        Ext.each(rawValueList, function (val) {
          if (checkbox.inputValue === val) {
            checked = true;
            return false;
          }
        });
        valueList.push(checked);
      });
      this.clear();
      //console.log('valueList', valueList);
      this.setValue(valueList);
    },

    clear: function () {
      var valueList = [];
      this.items.each(function (checkbox) {
        valueList.push(false);
      });
      //console.log('clear valueList', valueList);
      this.setValue(valueList);
    },

    listeners: {
      change: function (self, checked) {
        //console.log('checked', checked);

        // 전체 체크박스 처리
        // 체크 값이 2개 이상이면
        if (checked.length > 1 && this._totalItemChecked(checked)) {
          // console.log('_getTotalItemValue', this._getTotalItemValue());
          // 이전에 체크되어 있는 값이 전체이면, 전체 해제
          // 현재 체크한 값이 전체 이면, 다른 값들 해제
          var value = null;
          if (this._totalItemExists()) {
            this.setValue(this._getExtraItemValue());
          } else {
            this.setValue(this._getTotalItemValue());
            this._setLastCheckedItems();
            return;
          }
        }

        // 최대 선택 체크 수 처리
        if (this.maxCheckCount > 0) {
          if (checked.length > this.maxCheckCount) {
            Ext.Msg.alert(
              '확인',
              '최대 ' + this.maxCheckCount + '개 까지만 선택 가능합니다.'
            );
            this.setValue(this._beforeCheckedItems);
          } else {
            this._setBeforeCheckedItems();
          }
        }

        // 기본적으로 이전 체크값을 가지고 있음.
        this._setLastCheckedItems();
      },
    },

    // _getChecked: function () {
    //   var checked = [];
    //   this.items.each(function (checkbox) {
    //     if (checkbox.checked) {
    //       checked.push(checkbox);
    //     }
    //   });
    //   return checked;
    // },

    _setBeforeCheckedItems: function () {
      var beforeCheckedItems = [];
      this.items.each(function (checkbox) {
        beforeCheckedItems.push(checkbox.checked);
      });
      this._beforeCheckedItems = beforeCheckedItems;
    },

    _setLastCheckedItems: function () {
      var lastCheckedItems = [];
      this.items.each(function (checkbox) {
        lastCheckedItems.push({
          name: checkbox.inputValue,
          checked: checkbox.checked
        });
      });
      this._lastCheckedItems = lastCheckedItems;
    },

    _totalItemExists: function () {
      var exists = false;
      var totalItemName = this.totalItemName;
      Ext.each(this._lastCheckedItems, function (checkbox) {
        if (checkbox.name === totalItemName && checkbox.checked) {
          exists = true;
          return false;
        }
      });
      return exists;
    },

    _getTotalItemValue: function () {
      var totalItemName = this.totalItemName;
      var totalItemValues = [];
      var checked = false;
      this.items.each(function (checkbox) {
        if (checkbox.inputValue === totalItemName) {
          checked = true;
        } else {
          checked = false;
        }
        totalItemValues.push(checked);
      });
      return totalItemValues;
    },

    _getExtraItemValue: function () {
      var totalItemName = this.totalItemName;
      var extraItemValues = [];
      var checked = false;
      this.items.each(function (checkbox) {
        if (checkbox.inputValue === totalItemName) {
          checked = false;
        } else {
          checked = checkbox.checked;
        }
        extraItemValues.push(checked);
      });
      return extraItemValues;
    },

    _totalItemChecked: function (checked) {
      var totalItemName = this.totalItemName;
      // console.log('totalItemName', totalItemName);
      if (Ext.isEmpty(totalItemName)) {
        return false;
      }

      var result = false;
      Ext.each(checked, function (checkbox) {
        // console.log('checkbox.inputValue', checkbox.inputValue);
        if (checkbox.inputValue === totalItemName) {
          result = true;
          return false;
        }
      });
      return result;
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
    },
  });

  Ext.reg('c-checkboxgroup', Custom.CheckboxGroup);
})();