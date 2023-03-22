Ext.ns('Ariel.category');

Ariel.category.Material = Ext.extend(Ext.tree.TreePanel, {
    dataUrl: '/store/get-nodes.php',

    root: {
        nodeType: 'async',
        text: '운행소재',
        draggable: false,
        id: '-2',
        expanded: true
    },

    listeners: {
    	click: function(node, e) {
            if ( Ext.isEmpty(node) || node.attributes.isNew ) return;

            Ext.getCmp('topic-tree').getLoader().baseParams.beforePath = node.getPath();

            var search_q = Ext.getCmp('search_input').getValue();
            var params = {
                filter_type: 'category',
                filter_value: node.getPath(),
                search_q: search_q
            };

            var at = Ext.getCmp('tab_warp').getActiveTab();
            at.reload(params);
    	}
    }
});

Ext.reg('materialcategory', Ariel.category.Material);