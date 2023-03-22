Ext.ns("Custom");

Custom.YearlyCategory = Ext.extend(Ext.tree.TreePanel, {
  dataUrl: '/test/testp.php',
  root: {
    text: '연도 별',
    nodeType: 'async',
    expanded: true,
  },
  style: {
    color: 'red'
  },
  listeners: {

    click: function (node, e) {
      console.log(node.attributes);
    }

  }
});
