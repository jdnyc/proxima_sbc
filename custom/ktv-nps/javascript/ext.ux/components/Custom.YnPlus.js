(function () {
  Ext.ns("Custom");
  Custom.YnPlus = Ext.extend(Ext.form.CompositeField, {
    value: 'N',
    // 필드셋 여부
    fieldSet: null,
    initComponent: function () {
      this._initialize();

      Custom.YnPlus.superclass.initComponent.call(this);
    },
    _initialize: function () {
      var _this = this;
      /**
       * 엠바고 콤보박스
       */
      this.ynplusAtCombo = new Ext.form.ComboBox({
        editable: false,
        flex: 1,
        name: _this.name,
        triggerAction: 'all',
        editable: false,
        displayField: 'd',
        valueField: 'v',
        mode: 'local',
        value: _this.value,
        fields: [
          'd', 'v'
        ],
        store: [
          ['Y', 'Y'],
          ['N', 'N']
        ],
        listeners: {
          afterrender: function (self) {
            self.setValue(_this.value);

          },
          select: function (self, record, idx) {
            var _oriynplusReasonField = _this._oriynplusReasonField();
            switch (self.getValue()) {
              case 'Y':
                if (_oriynplusReasonField) {
                  _oriynplusReasonField.show();
                }
                break;
              case 'N':
                if (_oriynplusReasonField) {
                  _oriynplusReasonField.hide();
                  _oriynplusReasonField.setValue(null);
                }
                break;
            }

          }
        }
      });


      this.items = [this.ynplusAtCombo];

      this.listeners = {
        afterrender: function (self) {
          var _this = this;
          var ynplusAtComboValue = _this.ynplusAtCombo.getValue();
          var _oriynplusReasonField = _this._oriynplusReasonField();
          switch (ynplusAtComboValue) {
            case 'Y':
              if (_oriynplusReasonField) {
                _oriynplusReasonField.show();
              }
              break;
            case 'N':
              if (_oriynplusReasonField) {
                _oriynplusReasonField.hide();
              }
              break;
          }
        }
      };
    },
    setValue: function (v) {
      if ((v === null) || (v === ''))
        return this.ynplusAtCombo.setValue('N');

      this.ynplusAtCombo.setValue(v);
    },
    _isValue: function (v) {
      if ((v === null) || (v === '') || (typeof v === 'undefined')) {
        return false;
      } else {
        return true;
      }
    },
    /**
     * 숨겨진 원래 사유 필드
     */
    _oriynplusReasonField: function () {
      var _this = this;
      // var ownerComponent = this.ownerCt;
      var ownerComponent = this._ownerComponent();
      var fieldName = _this.name.slice(0, -3);

      var oriynplusReasonField = ownerComponent.getForm().findField(fieldName + '_cn');

      return oriynplusReasonField;
    },
    _ownerComponent: function () {
      var isForm = this._isValue(this.ownerCt.form);
      if (isForm) {
        return this.ownerCt;
      } else {
        var isForm = this._isValue(this.ownerCt.ownerCt.form);
        if (isForm)
          return this.ownerCt.ownerCt;
      }
    }

  });
  Ext.reg("ynplus", Custom.YnPlus);
})();