Ext.ns('Ariel.nav');

Ariel.nav.Favorite = Ext.extend(Ext.tree.TreePanel, {
	id: 'bookmark',
	iconCls: 'subnav-favorite',    
	title: '즐겨찾기',
	rootVisible: false,
	enableDrop: true,
	autoScroll: true,
	containerScroll: true,
	ddAppendOnly: true,
	root: {
		id: '0',
		text: '즐겨찾기'
	},
	loader: new Ext.tree.TreeLoader({
		dataUrl:'/store/get_favorite_category.php',
		baseParams: {
			type: 'favorite'
		}
	}),

	initComponent: function(){

		Ariel.nav.Favorite.superclass.initComponent.call(this);

		this.on('click', function(node, e){
			if ( Ext.isEmpty(node) || node.attributes.isNew ) return;

			Ext.getCmp('tab_warp').setActiveTab(0);

			var params = {
				filter_type: 'category',
				filter_value: node.attributes.full_path
			}

			var at = Ext.getCmp('tab_warp').getActiveTab();
			if (at.title == 'TV방송프로그램')
			{
				if (at.layout.activeItem.store)
				{
					at.layout.activeItem.store.reload({
						params: {
							content_type: Ext.getCmp('content_list_type').getValue(),
							filter_type: 'category',
							filter_value: node.attributes.full_path
						}
					});
				}
				else
				{
					var i = at.layout.activeItem.get(0).items;
					i.each(function(i, idx, a){
						i.store.reload({
							params: params
						})
					});
				}
			}
			else
			{
				at.store.reload({
					params: params
				});
			}
		});
	}
});
Ext.reg('navfavorite', Ariel.nav.Favorite);

Ariel.nav.WorkFlow = Ext.extend(Ext.tree.TreePanel, {
	iconCls: 'subnav-workflow',
	title: 'My Page',
	rootVisible: false,
	listeners: {
		click: function(n, e){
			var n = n.attributes;
			
			var win = window.open('/my_page.php?task_type='+n.task_type, n.eng_name);
			win.focus();
		}
	},
	root: {
		text: 'WorkFlow',
		expanded: true,
		children: [{
			hidden: true,
			icon: 'img/Cataloging.png',
			text: 'Cataloging',
			leaf: true,
			task_type: 10
		},{
			hidden: true,
			icon: 'img/Transcoding.png',
			text: 'Transcoding',
			leaf: true,
			task_type: 20
		},{
			hidden: true,
			icon: 'img/Rewrapping.png',
			text: 'Rewrapping',
			leaf: true,
			task_type: 30
		},{
			icon: 'img/PartialFileRestore.png',
			text: '구간 추출',
			leaf: true,
			eng_name: 'pfr',
			task_type: 30
		},{
			hidden: true,
			icon: 'img/Transfer.png',
			text: '전송',
			leaf: true,
			task_type: 80
		}]
	},
	
	initComponent: function(){

		Ariel.nav.WorkFlow.superclass.initComponent.call(this);

		this.getSelectionModel().on('selectionchange', function(sm, node){
			//console.log(node);
		});
	}		
});
Ext.reg('navworkflow', Ariel.nav.WorkFlow);

Ariel.nav.SubPanel = Ext.extend(Ext.Panel, {
	region: 'south',
	layout: 'accordion',
	split: true,
	defaults: {
		border: false
	},

	initComponent: function(config){
		Ext.apply(this, config || {});
		this.items = [{
			xtype: 'navfavorite'
		},{
			xtype: 'navworkflow'
		}]

		Ariel.nav.SubPanel.superclass.initComponent.call(this);
	}
});
