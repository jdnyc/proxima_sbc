(function (){
    Ext.ns("Custom");
    Custom.NullableCombo = Ext.extend(Ext.form.ComboBox, {
        listeners:{
            beforeselect: function(self) {
                self.beforeValue = self.getValue();
            },
            select: function(self,records) { 
                var oldValue = self.beforeValue;
                if(oldValue === self.value) {
                    self.setValue(self.tmpValue);
                }
            } 
        },
        initComponent: function(){
            Custom.NullableCombo.superclass.initComponent.call(this);
        },
    });
    Ext.reg("g-combo", Custom.NullableCombo);
})();