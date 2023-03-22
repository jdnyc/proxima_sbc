(function () {
  Ext.ns('Custom');
  Custom.ChannelField = Ext.extend(Ext.form.CompositeField, {
    // Properties
    fieldLabel: null,
    onPgmItemSearched: null,
    createSearchWindow: null,
    onSelect: null,
    displayPgmGroupInfo: false,

    channelCodeName: 'channel_code',
    pgmCodeName: 'pgm_code',

    listeners: {
      beforedestroy: function (self) {
        if (self.onPgmItemSearched) {
          self._pgmField.un('searched', self.onPgmItemSearched, self);
        }
        self._pgmField.un('aftersearch', self._onAfterPgmSearch, self);
      }
    },

    // private variables
    _pgmField: null,
    _channelCombo: null,

    constructor: function (config) {
      this.addEvents('afterpgmsearch');
      Ext.apply(this, {}, config || {});
      Custom.ChannelField.superclass.constructor.call(this);
    },

    initComponent: function () {
      this._initItems();
      Custom.ChannelField.superclass.initComponent.call(this);
    },

    getChannelCombo: function () {
      return this._channelCombo;
    },

    setPgmValue: function (pgmCode, pgmName) {
      this._pgmField.setValue(pgmCode, pgmName);
    },

    setPgmGroupValue: function (pgmGroupCode, pgmGroupName) {
      if (Ext.isEmpty(pgmGroupCode) && Ext.isEmpty(pgmGroupName)) {
        return;
      }
      var pgmRecord = new Ext.data.Record();
      pgmRecord.set('pgmGrpCd', pgmGroupCode);
      pgmRecord.set('pgmGrpNm', pgmGroupName);
      this._displayPgmGroupInfo(pgmRecord);
    },

    setChannel: function (channelCode) {
      var _this = this;
      if (!Ext.isEmpty(this._pgmGroupCodeField.getValue())) {
        var pgmGroupCode = this._pgmGroupCodeField.getValue();
        var pgmGroupName = this._pgmGroupNameField.getValue();
        this.setPgmGroupValue(pgmGroupCode, pgmGroupName);
      } else {
        this._displayPgmGroupInfo(null);
      }
      this._channelCombo.getStore().load({
        params: null,
        callback: function () {
          setTimeout(function () {
            var combo = _this._channelCombo;
            combo.setValue(channelCode);
            combo.getStore().each(function (record) {
              if (record.get('code') == channelCode) {
                var isBroadcastChannel = record.get('broadcast');
                _this._pgmField.setVisible(isBroadcastChannel);
                _this.doLayout();

                if (_this.onSelect) {
                  _this.onSelect(record);
                }
              }
            });
          }, 0);
          //_this._setChannelValue.defer(_this, channelCode);
        }
      });
    },

    /**
     * 필드 생성
     */
    _initItems: function () {
      var _this = this;

      this._pgmField = this._makePgmField();
      if (this.onPgmItemSearched) {
        this._pgmField.on('searched', this.onPgmItemSearched, this);
      }

      this._channelCombo = this._makeChannelCombo(this._pgmField);

      this._pgmGroupCodeField = new Ext.form.Hidden({
        xtype: 'hidden',
        name: 'usr_pgm_group_code'
      });

      this._pgmGroupNameField = new Ext.form.Hidden({
        xtype: 'hidden',
        name: 'usr_pgm_group_name'
      });
      this.items = [
        this._pgmGroupCodeField,
        this._pgmGroupNameField
      ];
      this.items.push(this._channelCombo);

      if (this.displayPgmGroupInfo) {
        this._pgmGroupInfo = new Ext.form.Label({
          style: {
            marginTop: '6px',
          },
          hidden: true,
        });
        this.items.push(this._pgmGroupInfo);
      }

      this.items.push(this._pgmField);
    },

    _displayPgmGroupInfo: function (pgmRecord) {
      if (
        !this.displayPgmGroupInfo ||
        !pgmRecord ||
        !pgmRecord.get('pgmGrpCd') ||
        !pgmRecord.get('pgmGrpNm')
      ) {
        if (!this._pgmGroupInfo) {
          return;
        }
        this._pgmGroupInfo.setText('');
        this._pgmGroupCodeField.setValue('');
        this._pgmGroupNameField.setValue('');
        if (this._pgmGroupInfo.isVisible()) {
          this._pgmGroupInfo.hide();
          this.doLayout();
        }
        return;
      }

      this._setPgmGroupInfo(
        pgmRecord.get('pgmGrpCd'),
        pgmRecord.get('pgmGrpNm')
      );
      if (!this._pgmGroupInfo.isVisible()) {
        this._pgmGroupInfo.show();
        this.doLayout();
      }
    },

    _setPgmGroupInfo: function (pgmGroupCode, pgmGroupName) {
      var pgmGroupInfo = pgmGroupCode + ' / ' + pgmGroupName;
      this._pgmGroupInfo.setText(pgmGroupInfo);

      this._pgmGroupCodeField.setValue(pgmGroupCode);
      this._pgmGroupNameField.setValue(pgmGroupName);
    },

    _makeChannelCombo: function (pgmField) {
      var _this = this;

      var channelCombo = new Ext.form.ComboBox({
        name: this.channelCodeName || 'channel_code',
        triggerAction: 'all',
        editable: false,
        width: 120,
        store: Custom.Store.getChannelStore(),
        displayField: 'name',
        valueField: 'code',
        emptyText: '선택',
        listeners: {
          select: function (self, r) {
            pgmField.clear();
            var isBroadcastChannel = r.get('broadcast');

            pgmField.setVisible(isBroadcastChannel);
            _this.doLayout();

            if (_this.onSelect) {
              _this.onSelect(r);
            }
          },
        },
      });

      return channelCombo;
    },

    _makePgmField: function () {
      var _this = this;
      var createSearchWindow = null;
      if (this.createSearchWindow) {
        createSearchWindow = this.createSearchWindow;
      } else {
        createSearchWindow = function () {
          return new Custom.PgmSelectWindow();
        };
      }
      var pgmField = new Custom.PopupSearchField({
        codeFieldName: this.pgmCodeName || 'pgm_code',
        nameFieldName: this.pgmNameName || 'pgm_name',
        channelFieldComponent: this,
        width: 300,
        codeKey: 'pgmCd',
        nameKey: 'pgmNm',
        createSearchWindow: createSearchWindow,
        setSearchWindowConfig: this._setSearchWindowConfig,
        hidden: true,
      });
      pgmField.on('aftersearch', this._onAfterPgmSearch, this);

      return pgmField;
    },
    _setSearchWindowConfig: function (channelFieldComponent) {
      if (!channelFieldComponent) {
        return null;
      }
      return {
        channelCode: channelFieldComponent.getChannelCombo().getValue(),
      };
    },
    _onAfterPgmSearch: function (pgmField, pgmRecord) {
      // console.log('_onAfterPgmSearch', pgmRecord);
      //pgmRecord.set('pgmGrpCd', '000004');
      //pgmRecord.set('pgmGrpNm', '겟꿀쇼');
      this._displayPgmGroupInfo(pgmRecord);
      this.setChannel(pgmRecord.get('channel_code'));
      this.fireEvent('afterpgmsearch', this, pgmRecord);
    },
  });

  Ext.reg('c-channel-field', Custom.ChannelField);
})();