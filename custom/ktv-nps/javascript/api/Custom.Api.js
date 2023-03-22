(function () {

    Ext.ns('Custom');
    Api = Ext.extend(Object, {
        searchContent: function (params, callback, scope) {
            var url = requestCustomPath('/store/contents.php', params);
            ajax(url, 'GET', null, function (scope, res) {
                callback(scope, res);
            }, null, scope);
        },
        searchVideos: function (params, callback, scope) {
            var url = requestCustomPath('/store/videos.php', params);
            ajax(url, 'GET', null, function (scope, res) {
                callback(scope, res);
            }, null, scope);
        },
        createVideoKeyword: function (params, callback, scope) {
            var url = requestCustomPath('/store/video-keywords.php');
            ajax(url, 'POST', params, function (scope, res) {
                callback(scope, res);
            }, null, scope);
        },
        updateUse: function (params, callback, scope) {
            var url = requestCustomPath('/store/use.php');
            ajax(url, 'POST', params, function (scope, res) {
                callback(scope, res);
            }, null, scope);
        }
    });

    Custom.Api = new Api();
})();