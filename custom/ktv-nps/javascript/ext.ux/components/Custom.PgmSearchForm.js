(function () {
  Ext.ns('Custom');
  Custom.PgmSearchForm = Ext.extend(Ext.form.FormPanel, {

    // const
    SHOCK_LIVE_CODE: 'CJSL',

    // properties

    // private variables
    _channelComboBox: null,

    constructor: function (config) {

      this.addEvents('search');
      this.addEvents('clear');
      this.addEvents('channelselect');

      this._initItems();

      this.border = false;

      this.layout = {
        type: 'hbox'
      };

      Ext.apply(this, {}, config || {});

      Custom.PgmSearchForm.superclass.constructor.call(this);
    },

    /**
     * 채널콤보박스 데이터를 로드한 후 넘겨준 채널로 선택한다.
     * 
     * @param string channelCode 
     */
    setChannel: function (channelCode) {
      var _this = this;
      this._channelComboBox.getStore().load({
        params: null,
        callback: function () {
          setTimeout(function () {
            _this._channelComboBox.setValue(channelCode);
            var visible = (channelCode === _this.SHOCK_LIVE_CODE);
            _this._displayPgmGroupCombo(visible);
          }, 0);
          //_this._setChannelValue.defer(_this, channelCode);
        }
      });
    },

    getChannelCode: function () {
      return this._channelComboBox.getValue();
    },

    // _setChannelValue: function (form, channelCode) {
    //   console.log(form, form._channelComboBox);
    //   form._channelComboBox.setValue(channelCode);
    //   var visible = (channelCode === form.SHOCK_LIVE_CODE);
    //   form._displayPgmGroupCombo(visible);
    // },

    _displayPgmGroupCombo: function (visible) {
      //console.log('visible', visible);
      this._pgmGroupCombo.setVisible(visible);
      this._pgmGroupLabel.setVisible(visible);
      this._pgmGroupSpacer.setVisible(visible);
      this.doLayout();
      this.fireEvent('channelselect', this, this.getChannelCode());
    },

    _initItems: function () {
      var _this = this;

      this._pgmGroupLabel = new Ext.form.Label({
        text: 'PGM 그룹명:',
        style: {
          color: 'white',
          marginTop: '6px'
        },
        width: 70,
        hidden: true
      });

      this._pgmGroupCombo = new Ext.form.ComboBox({
        name: 'pgm_group',
        triggerAction: 'all',
        editable: false,
        width: 120,
        store: Custom.Store.getPgmGroupStore(),
        displayField: 'pgmGroupNm',
        valueField: 'pgmGroupCd',
        hidden: true,
        emptyText: '선택'
      });

      this._pgmGroupSpacer = new Ext.Spacer({
        width: 10,
        hidden: true
      });

      this._channelComboBox = new Ext.form.ComboBox({
        name: 'channel_code',
        triggerAction: 'all',
        editable: false,
        width: 120,
        store: Custom.Store.getChannelStore({
          broadcast: true,
          channel_only: true
        }),
        displayField: 'name',
        valueField: 'code',
        emptyText: '선택',
        listeners: {
          select: function (self, r) {
            // 쇼크라이브를 선택했으면 PGM그룹명을 보여주고
            // 아니면 숨긴다
            var visible = (self.getValue() === _this.SHOCK_LIVE_CODE);
            _this._displayPgmGroupCombo(visible);
          }
        }
      });

      this.items = [{
          xtype: 'label',
          text: '방송채널:',
          style: {
            color: 'white',
            marginTop: '6px'
          },
          width: 50
        },
        this._channelComboBox,
        {
          xtype: 'spacer',
          width: 10
        }, {
          xtype: 'label',
          text: '방송일:',
          style: {
            color: 'white',
            marginTop: '6px'
          },
          width: 40
        }, {
          xtype: 'datefield',
          width: 120,
          format: 'Y-m-d',
          altFormats: 'Y-m-d|Ymd|YmdHis',
          name: 'broad_date',
          value: new Date().format('Y-m-d')
        }, {
          xtype: 'spacer',
          width: 10
        },
        this._pgmGroupLabel,
        this._pgmGroupCombo,
        this._pgmGroupSpacer,
        {
          xtype: 'a-iconbutton',
          //icon: 'fa fa-search',
          text: '조회',
          handler: function (btn, e) {
            var form = _this.getForm();
            var values = form.getValues();
            values.channel_code = _this._channelComboBox.getValue();
            if (values.channel_code === '') {
              Ext.Msg.alert('확인', '방송채널을 선택해 주세요.');
              return;
            }
            if (values.channel_code === _this.SHOCK_LIVE_CODE) {
              values.pgm_group = _this._pgmGroupCombo.getValue();
              if (values.pgm_group === '') {
                Ext.Msg.alert('확인', 'PGM그룹을 선택해 주세요.');
                return;
              }
            }
            _this.fireEvent('search', _this, values);
          }
        }, {
          xtype: 'spacer',
          width: 5
        }, {
          xtype: 'a-iconbutton',
          //icon: 'fa fa-search',
          text: '초기화',
          handler: function (btn, e) {
            var form = _this.getForm();
            form.setValues({
              channel_code: '',
              broad_date: new Date().format('Y-m-d'),
              pgm_group: ''
            });

            _this._displayPgmGroupCombo(false);
            _this.doLayout();
            _this.fireEvent('clear', _this);
          }
        }
      ];
    }
  });

  Ext.reg('c-pgm-search-form', Custom.PgmSearchForm);
})();