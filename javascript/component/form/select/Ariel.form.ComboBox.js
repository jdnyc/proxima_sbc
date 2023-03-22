(function(){
    Ext.ns('Ariel.form');
    Ariel.form.ComboBox = Ext.extend(Ext.form.ComboBox, { 
        constructor: function(config) {
            var combo = this;

            if (config.triggerAction === undefined) {
                config.triggerAction = 'all'
            }

            if (config.store === undefined) {
                config.store = new Ext.data.JsonStore({
                    proxy: new Ext.data.HttpProxy({
                        method: 'GET',            
                        prettyUrls: false,       
                        url: config.url
                    }),
                    autoLoad: true,
                    root: 'data',
                    fields: config.fields
                });
                config.url = undefined;
                config.fields = undefined;
            }

            //로드 후 첫번째 값 선택할 수 있도록
            if (config.setFirstValue === true) {
                config.store.on('load',function(store) {
                    var firstValue = store.getAt(0).get(config.valueField);
                    combo.setValue(firstValue);
                });
            }

            //로드 후 value 값 선택하도록. name으로 보여줘야 하기 때문에
            if (!Ext.isEmpty(config.value)) {
                config.store.on('load',function(store) {
                    combo.setValue(config.value);
                });
            }

            Ext.apply(this, config || {});
            Ariel.form.ComboBox.superclass.constructor.call(this);
        }
    });
    Ext.reg('a-combo', Ariel.form.ComboBox);
})()
