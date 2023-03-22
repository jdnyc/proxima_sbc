Ext.ns('Ariel.archiveManagement');
Ariel.archiveManagement.UrlSet = new Ext.extend(Object, {
    order: '/api/v1/content-orders',
    content: '/api/v1/contents',
    price: '/api/v1/content-order-price',

    orderOrderNumParam: function (orderNum) {
        return this.order + '/' + orderNum;
    },
    getOrderItemsByOderNum: function (orderNum) {
        return this.order + '/' + orderNum + '/items';
    },
    orderUpdateStatus: function (orderNum) {
        return this.order + '/' + orderNum + '/update-status';
    },
    getOrderSalesPriceByIdx: function (idx) {
        return this.price + '/' + idx;
    },
    listParentContent: function (idx) {
        return this.content + '/' + idx + '/parent'
    }

});
Ariel.archiveManagement.UrlSet = new Ariel.archiveManagement.UrlSet();