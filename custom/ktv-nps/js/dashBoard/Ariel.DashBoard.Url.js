Ext.ns('Ariel.DashBoard');
Ariel.DashBoard.Url = new Ext.extend(Object, {
    passwordChange: '/api/v1/user/password-change',
    changePassword: '/api/v1/users/me/change-password',

    browserConfig: '/api/v1/user/browser-config',
    option: '/api/v1/users/me/option',

    userInfoChange: '/api/v1/user/user-info',

    request: '/api/v1/request',
    reviews: '/api/v1/dash-board-reviews',

    download: '/api/v1/downloads',

    authorityMandate: '/api/v1/authority-mandate',
    requestAttach: function () {
        return this.request + '-attach';
    },
    requestId: function (ordId) {
        return this.request + '/' + ordId;
    },
    requestStatusUpdate: function (ordId) {
        return this.request + '/' + ordId + '/update-status';
    },
    requestUpdateCharger: function (ordId) {
        return this.request + '/' + ordId + '/update-charger';
    },
    reviewsUpdateCharger: function (ordId) {
        return this.reviews + '/' + ordId + '/update-charger';
    },
    reviewsStatusUpdate: function (ordId) {
        return this.reviews + '/' + ordId + '/update-status';
    },
    rejectCnUpdate: function (ordId) {
        return this.reviews + '/' + ordId + '/update-rejectCn';
    },
    authorityMandateUpdate: function (authorityMandateId) {
        return this.authorityMandate + '/' + authorityMandateId;
    },
    downloadPath: function (path, name, type, id) {
        return this.download + '?path=' + path + '&name=' + name + '&type=' + type + '&ord_id = ' + id;
        // path="http://10.10.50.87/data/2015/02/03/253/Catalog/1.jpg"&name=1.jpg
    }
});
Ariel.DashBoard.Url = new Ariel.DashBoard.Url();
