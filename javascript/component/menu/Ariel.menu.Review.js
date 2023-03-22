/**
 * Created by cerori on 2015-04-08.
 */
Ext.ns('Ariel.menu');
(function() {

    Ariel.menu.Review = Ext.extend(Ext.menu.Item, {

        initComponent: function(config) {
            var _this = this;

            Ext.apply(this, {
                text: '심의',
                hideOnClick: false,
                icon: '/led-icons/review_sicon1.jpg',
                menu: [{
                    hidden: true,
                    grant: Ariel.grant.REVIEW_ACCEPT,
                    icon: '/led-icons/review_sicon4.jpg',
                    text: '승인',
                    handler: _this.onAccept,
                    scope: _this
                }, {
                    hidden: true,
                    grant: Ariel.grant.REVIEW_REJECT,
                    icon: '/led-icons/review_sicon2.jpg',
                    text: '반려',
                    handler: _this.onReject,
                    scope: _this
                }, {
                    hidden: true,
                    grant: Ariel.grant.REVIEW_REQUEST,
                    icon: '/led-icons/review_sicon1.jpg',
                    text: '의뢰',
                    handler: _this.onRequest,
                    scope: _this
                }]
            }, config || {});

            Ariel.menu.Review.superclass.initComponent.call(this);

            this.menu.items.each(function(item) {
                for (var key in Ariel.grant) {
                    if ((item.grant & Ariel.grant[key]) == _this.grant) {
                        item.hidden = false;
                    }
                }
            });
        },

        onAccept: function() {
            new Ariel.window.review.Accept({contents: this.getSelections()}).show();
        },

        onReject: function() {
            new Ariel.window.review.Reject({contents: this.getSelections()}).show();
        },

        onRequest: function() {
            new Ariel.window.review.Request({contents: this.getSelections()}).show();
        },

        getSelections: function () {
            return Ext.getCmp('tab_warp').getActiveTab().get(0).getSelectionModel().getSelections();
        }
    });

    Ext.reg('menureview', Ariel.menu.Review);
})();
