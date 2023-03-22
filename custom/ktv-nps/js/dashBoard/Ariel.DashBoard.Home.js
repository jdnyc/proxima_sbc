
(function () {
    Ext.ns('Ariel.DashBoard');

    Ariel.DashBoard.Home = Ext.extend(Ext.Container, {
        initComponent: function (config) {
            var _this = this;

            var topLayout = new Ext.Container({
                autoScroll: true,
                border: false,
                frame: false,
                flex: 1,
                layout: {
                    type: 'hbox',
                    align: 'stretch'
                },
                defaults: {
                    border: false,
                    // margins: { top: 0, right: 0, bottom: 0, left: 0 },
                    flex: 1
                },
                items: [
                    new Ariel.DashBoard.Notice({
                        margins: '5 5 5 0'
                    }),
                    new Ariel.DashBoard.Monitor({
                        margins: '5 0 5 0',
                        viewHome: true
                    })
                ]
            });
            var bottomLayout = new Ext.Container({
                flex: 1,
                closable: true,
                autoScroll: true,
                border: false,
                frame: false,
                layout: {
                    type: 'hbox',
                    align: 'stretch'
                },
                defaults: {
                    border: false,
                    // margins: { top: 0, right: 0, bottom: 0, left: 0 },
                    flex: 1
                },
                items: [
                    new Ariel.DashBoard.Request({
                        statusButtonShow: true,
                        margins: '0 5 5 0',
                        height: 250
                    }),
                    new Ariel.DashBoard.Storage({
                        buttonShow: true,
                        _home: true,
                        margins: '0 5 5 0',
                        height: 250
                    }),
                    new Ariel.DashBoard.Review({
                        statusButtonShow: true,
                        margins: '0 0 5 0',
                        height: 250
                    })
                ]
            });

            Ext.apply(this, {
                layout: 'vbox',
                layoutConfig: {
                    align: 'stretch',
                    pack: 'start',
                },
                autoScroll: true,
                border: false,
                items: [
                    topLayout,
                    bottomLayout
                ]
            }, config || {});

            Ariel.DashBoard.Home.superclass.initComponent.call(this);
        }
    });

    // return new Ariel.DashBoard.Home();
})()
