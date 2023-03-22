(function () {
  Ext.ns('Custom');
  Custom.PopupSearchField = Ext.extend(Ext.form.CompositeField, {
    // Properties

    /**
     * 코드 필드명
     */
    codeFieldName: '',

    /**
     * 이름 필드명
     */
    nameFieldName: '',

    codeKey: '',

    nameKey: '',

    createSearchWindow: null,

    setSearchWindowConfig: null,

    channelFieldComponent: null,

    codeFieldWidth: 0,

    nameFieldWidth: 0,

    onEnterPressed: null,

    // private variables

    _codeField: null,
    _nameField: null,

    constructor: function (config) {
      this.addEvents('aftersearch');

      if (!config || !config.codeFieldName || !config.nameFieldName) {
        throw new Ext.Error(
          'codeFieldName and nameFieldName property required.'
        );
      }

      Ext.apply(this, {}, config || {});
      Custom.PopupSearchField.superclass.constructor.call(this);
    },

    initComponent: function () {
      this._initItems();
      Custom.PopupSearchField.superclass.initComponent.call(this);
    },

    setValue: function (codeValue, nameValue) {
      this._codeField.setValue(codeValue);
      this._nameField.setValue(nameValue);
    },

    onSearchWindowOk: function (win, record) {
      if (record) {
        if (this.codeKey) {
          this._codeField.setValue(record.get(this.codeKey));
        }
        if (this.nameKey) {
          this._nameField.setValue(record.get(this.nameKey));
        }
      }
      win.un('ok', this.onSearchWindowOk, this);
      win.close();
      this.fireEvent('aftersearch', this, record);
    },

    clear: function () {
      this._codeField.setValue('');
      this._nameField.setValue('');
    },

    /**
     * 필드 생성
     */
    _initItems: function () {
      var _this = this;

      this._codeField = new Ext.form.TextField({
        width: this.codeFieldWidth || 60,
        readOnly: true,
        name: this.codeFieldName,
        listeners: {
          specialkey: function (f, e) {
            if (!_this.onEnterPressed) return;

            if (e.getKey() == e.ENTER) {
              e.stopEvent();
              //console.log(f.getValue());
              _this.onEnterPressed(f.getValue());
            }
          },
        },
      });

      this._nameField = new Ext.form.TextField({
        width: this.nameFieldWidth || 155,
        readOnly: true,
        cls: 'readonly-gray-textfield',
        name: this.nameFieldName,
      });

      var searchButton = new Ariel.IconButton({
        icon: 'fa fa-search',
        text: '검색',
        handler: function (btn, e) {
          if (!_this.createSearchWindow) {
            return;
          }
          var searchWindow = _this.createSearchWindow();
          searchWindow.on('ok', _this.onSearchWindowOk, _this);
          if (
            _this.setSearchWindowConfig &&
            searchWindow.setConfig &&
            _this.channelFieldComponent
          ) {
            var config = _this.setSearchWindowConfig(
              _this.channelFieldComponent
            );
            if (config) {
              searchWindow.setConfig(config);
            }
          }
          searchWindow.show();
        },
      });

      this.items = [this._codeField, this._nameField, searchButton];
    },
  });

  Ext.reg('c-popup-searchfield', Custom.PopupSearchField);
})();