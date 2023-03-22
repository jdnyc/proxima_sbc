(function () {
  Ext.ns("Custom");
  Custom.Embargo = Ext.extend(Ext.Container, {
    // 엠바고 여부
    fieldLabel: null,
    labelSeparator: '>>>',
    layoutConfig: {
      labelSeparator: '~'   // layout config has lowest priority (defaults to ':')
    },
    initComponent: function () {
      this._initialize();
      Custom.Embargo.superclass.initComponent.call(this);
    },
    _initialize: function () {
      var _this = this;
      this.embargoReasonField = new Ext.form.TextArea({
        name: 'embg_resn',
        anchor: '95%',
        height: 200,
        labelSeparator: '>',
        fieldLabel: '엠바고사유',
        maxLength: 4000,
        regexText: '최대 길이는 4000자 입니다.',
      });
      this.embargoAtCombo = new Ext.form.ComboBox({
        editable: false,
        flex: 1,
        name: 'embg_at',
        triggerAction: 'all',
        editable: false,
        displayField: 'd',
        valueField: 'v',
        mode: 'local',
        value: 'N',
        fields: [
          'd', 'v'
        ],
        store: [
          ['Y', 'Y'],
          ['N', 'N']
        ]
      });
      this.embargoDateField = new Ext.form.DateField({
        name: 'embg_relis_dt',
        flex: 1,
        format: 'Y-m-d',
        altFormats: 'Ymd|ymd|Y-m-d|y-m-d|Y/m/d|Y/m/d',
      });
      this.compositeField = new Ext.form.CompositeField({
        items: [
          this.embargoAtCombo,
          this.embargoDateField
        ]
      });
      // this.items = [this.compositeField];
      // this.formPanel = new Ext.form.FormPanel({
      //   items: [
      //     this.embargoAtCombo
      //   ]
      // });
      this.items = [this.compositeField, this.embargoReasonField];
      // this.items = [this.embargoAtCombo];
      // this.items = [this.formPanel];
      // this.items = [
      //   this.compositeField,
      //   this.embargoReasonField
      // ]
    },
  });
  Ext.reg("embargo", Custom.Embargo);
})();