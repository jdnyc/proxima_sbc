Ext.ns("Custom");

Custom.ToolBarDate = Ext.extend(Ext.form.RadioGroup, {
  startDateField: function () {
    var startDateField = new Ext.form.DateField({
      name: 'start_date',
      editable: false,
      width: 105,
      format: 'Y-m-d',
      altFormats: 'Ymd|ymd|Y-m-d|y-m-d|Y/m/d|Y/m/d',
      listeners: {
        render: function (self) {
          var d = new Date();

          self.setMaxValue(d.format('Y-m-d'));
          self.setValue(d.add(Date.DAY, -7).format('Y-m-d'));
        }
      }
    });
    return startDateField;
  },
  endDateField: function () {
    var endDateField = new Ext.form.DateField({
      name: 'end_date',
      editable: false,
      width: 105,
      format: 'Y-m-d',
      altFormats: 'Ymd|ymd|Y-m-d|y-m-d|Y/m/d|Y/m/d',
      listeners: {
        render: function (self) {
          var d = new Date();

          self.setMaxValue(d.format('Y-m-d'));
          self.setValue(d.format('Y-m-d'));
        }
      }
    });
  },
  groupField: function () {
    var startDateField = this.startDateField();
    var endDateField = this.endDateField();
    return startDateField, '~', endDateField, {
      xtype: 'radioday',
      dateFieldConfig: {
        startDateField: startDateField,
        endDateField: endDateField
      }
    };
  }
});
Custom.ToolBarDate = new Custom.ToolBarDate();