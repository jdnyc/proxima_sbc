(function (){
    Ext.ns("Custom");
    Custom.CodeCombo = Ext.extend(Ext.form.ComboBox, {
        notUseField: 'use_yn',
        notUseValue: 'N',
        listeners: {
            beforequery: function(query){
                var combo = this;
                var isNotUseField = combo.isField(combo.notUseField);
                var view = query.combo.view;
                var notUse = new Ext.XTemplate(
                    '<tpl for=".">',
                        '<tpl if="this.useCheck('+combo.notUseField+')">',
                            '<div class="x-combo-list-item">',
                                '{'+combo.displayField+'}',
                            '</div>',
                        '</tpl>',  
                        '<tpl if="!this.useCheck('+combo.notUseField+')">',
                            '<div class="x-combo-list-item" style="display: none;">',
                            '</div>',
                        '</tpl>',                           
                    '</tpl>',
                    {
                        useCheck: function(useYn){
                            if(useYn == combo.notUseValue){
                                return false;
                            }
                            return true;
                        }
                    }
                );
                if(isNotUseField){
                    view.tpl = notUse;
                    view.refresh();
                }
                
            }
        },
        isField: function(name){
            if(Ext.isEmpty(this.getStore())){
                return false;
            };
            
            var fields = this.getStore().fields.items;
            var isField = false;
 
            Ext.each(fields,function(field){
                if(field.name == name){
                    isField = true;
                }
            });
            return isField;
        },
        initComponent: function(){            
            Custom.CodeCombo.superclass.initComponent.call(this);
        }
    });
    Ext.reg("c-code-combo", Custom.CodeCombo);
})();