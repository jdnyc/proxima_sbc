/**
 * Created by cerori on 2015-04-01.
 */
(function() {
    Ext.ns('Ariel.Panel.Main');

    Ariel.Panel.Main.Center = Ext.extend(Ext.Panel, {
	        defaults: {
	            border: false,
	            margins : '0 0 0 0'
	        },
		border: false,
         	layout: {
			 type: 'vbox',
			 align:'stretch'
		},

        	initComponent: function(config){
	            var that = this;
	            var _this = this;

            Ext.apply(this, {
                items: [{
					layout: {
						type: 'border',
						align: 'stretch'
					},
					id: 'home_panel',
					flex:1,
					border:false,
					frame:false,
                   	items: [{
						xtype: 'panel',
						border:false,
						frame:false,
						region:'west',
						width:240,
						boxMinWidth: 240,
						split: true,
						bodyStyle:{"background-color":"#eaeaea"},
						margins:'0 0 0 0',
						layout: {
							type: 'vbox',
							align: 'stretch'
						},
						items: [
							new Ariel.nav.Main_WestPanel({
								margins: '20 0 0 0',
								border:false,
								height:350//550
							}),
							new Ariel.nav.Main_SystemManager({
								margins: '10 0 0 0',
								border:false,
								height: 200
							}),
							new Ariel.nav.Main_Manual({
								//hidden: true,
								margins: '0 0 0 0',
								border:false
							})
						]
					 },{
						xtype: 'panel',
						border:false,
						frame:false,
						region:'center',
						flex:1,
						layout: {
							type: 'vbox',
							align: 'stretch'
						},							
						items:[
							new Ariel.Nps.Main.Notice({
								margins: '0 0 0 0',
								border:false,		  
								flex : 5
						}),{
								xtype: 'mainmonitor',
								margins: '0 0 0 0',
								flex : 5
						}]
					}]
				}]
            }, config || {});


            Ariel.Panel.Main.Center.superclass.initComponent.call(this);

            //_this.get(1).on('render', _this._onRender, _this.get(1));
        },

        _onRender: function() {
            var _this = this;
            _this.find('hidden', true).forEach(function(item) {
                //console.log(grant_check(item.grant))
                if ( ! grant_check(item.grant)) {
                    _this.hideTabStripItem(item);
                }
            });
        }
    });

    Ext.reg('maincenter', Ariel.Panel.Main.Center);
})();
