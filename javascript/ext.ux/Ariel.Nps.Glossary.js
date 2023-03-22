
Ariel.Nps.Glossary = Ext.extend(Ext.Container, {
    layout: 'border',
    autoScroll: false,
    border: false,
    isLoading: false,
    setLoading: function (isLoading) {
        this.isLoading = isLoading;
        return true;
    },
    getLoading: function () {
        return this.isLoading;
    },
    initComponent: function () {
        // Ext.apply(this, config || {});
        var _this = this;
        this.items = [{
            xtype: 'treepanel',
            region: 'west',
            width: 250,
            boxMinWidth: 250,
            split: true,
            collapsible: false,
            autoScroll: false,
            // rootVisible :false,
            lines: true,
            cls: 'tree_menu',
            bodyStyle: 'border-right: 1px solid #d0d0d0',
            isLoading: false,
            listeners: {
                click: function (node, e) {
                    /**
                     * false -> setLoading ->doLayout -> getLoading->if(true) ......-> setLoading(false)->false
                     */
                    if (_this.getLoading()) {

                        return;
                    }
                    _this.setLoading(true);

   
                    var url = node.attributes.url;

                    if (!url) {
                        _this.setLoading(false);
                        return;
                    }
                    var components = [
                        '/custom/ktv-nps/js/glossary/searchField.js',
                        '/custom/ktv-nps/js/glossary/inputFormWindow.js',
                        '/custom/ktv-nps/js/glossary/domainSelectWindow.js',
                        '/custom/ktv-nps/js/glossary/fieldSelectWindow.js',
                        '/custom/ktv-nps/js/glossary/codeSelectWindow.js',
                        '/custom/ktv-nps/js/glossary/ChangeStatus.js',
                        '/custom/ktv-nps/js/glossary/Ariel.glossary.UrlSet.js',
                        '/custom/ktv-nps/js/helper/functions.js'
                    ];
                    Ext.Loader.load(components, function (r) {
                        Ext.Ajax.request({
                            url: url,
                            timeout: 0,
                            callback: function (opts, success, response) {
                                try {
                                    _this.get(1).removeAll(true);
                                    _this.get(1).add(Ext.decode(response.responseText));
                                    _this.get(1).doLayout();
                                    _this.setLoading(false);

                                } catch (e) {
                                    Ext.Msg.alert(e['name'], opts.url + '<br />' + e['message']);
                                }
                            }
                        });
                    });


                }
            },
            root: {
                text: '데이타 사전',
                expanded: true,
                children: [{
                    text: '표준용어',
                    url: 'custom/ktv-nps/js/glossary/wordListGrid.js',
                    leaf: true
                }, {
                    text: '필드',
                    url: 'custom/ktv-nps/js/glossary/fieldListGrid.js',
                    leaf: true
                }, {
                    text: '테이블',
                    url: 'custom/ktv-nps/js/glossary/tableListGrid.js',
                    leaf: true
                }, {
                    text: '도메인',
                    url: 'custom/ktv-nps/js/glossary/domainListGrid.js',
                    leaf: true
                }, {
                    text: '코드',
                    url: 'custom/ktv-nps/js/glossary/codeListGrid.js',
                }]
            }
        }, {
            region: 'center',
            headerAsText: false,
            border: false,
            layout: 'fit'
        }]



        Ariel.Nps.Glossary.superclass.initComponent.call(this);
    }
});