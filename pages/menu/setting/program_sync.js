(function() {

    var tree = new Ext.tree.TreePanel({
        xtype: 'treepanel',
        autoScroll: true,
        root: {
            text: '프로그램',
            nodeType: 'async'
        },
        dataUrl: 'pages/menu/setting/get_bis_program.php',
        listeners: {
            resize: function(self) {
                self.mask  = new Ext.LoadMask(self.el, {msg: 'BIS으로부터 프로그램 목록을 불러오는 중입니다... <br />시간이 오래 소요될 수 있습니다.'});
                self.mask.show();
            },
            load: function(node) {
                tree.mask.hide();
            },
            checkchange: function(node, checked) {
                checked = checked ? 1 : 0;

                this.mask.show();
                Ext.Ajax.request({
                    url: 'pages/menu/setting/set_sync.php',
                    params: {
                        pgm_id: node.id,
                        checked: checked
                    },
                    callback: function(self, success, response) {
                        tree.mask.hide();
                    }
                });
            }
        }
    });

    tree.getRootNode().expand();

    return tree;
})()