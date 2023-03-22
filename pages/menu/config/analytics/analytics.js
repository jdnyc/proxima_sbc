Ext.ns('Ariel.menu', 'Ariel.menu.analytics');

Ariel.menu.analytics.TabPanel = Ext.extend(Ext.TabPanel, {
	activeTab: 0,

	initComponent: function(config){
		Ext.apply(this, config || {});

		this.items = [
			new Ariel.menu.analytics.Register(),
			new Ariel.menu.analytics.PageReadCount(),
			new Ariel.menu.analytics.Login()
		];

		Ariel.menu.analytics.TabPanel.superclass.initComponent.call(this);
	}
});

