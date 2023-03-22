/**
 * Created by cerori on 2015-04-01.
 */

(function() {
    Ext.ns('Ariel.Panel');

    Ariel.Panel.Main = Ext.extend(Ext.Panel, {
        layout: 'border',
        border: false,

        initComponent: function(config){
            var that = this;

            Ext.apply(this, {
                items: [{
                    //id: 'west-menu',
                    //region: 'north',
                    //layout: 'border',
                    //border: false,
                    //split: true,
                    //plain: true,
                    //cls: 'custom-nav-tab',
                    //width: 250,
                    //margins : '0 0 2 2',
                    //items: [
                        //new Ariel.nav.Main_WestPanel()
                    //]
                //}, {
                    xtype: 'maincenter',
                    region: 'center'
                }]
            }, config || {});

            Ariel.Panel.Main.superclass.initComponent.call(this);
        }
    });

    Ext.reg('mainpanel', Ariel.Panel.Main);
})();