Ext.CUSTOM_ROOT = '/custom/cjos-vms';

/**
 * 커스텀 url을 생성해줌
 *
 * @param string path
 * @param object params key를 속성으로 가지는 객체 {key1: value, key2: value}
 * @returns string url path
 */
function requestCustomPath(path, params) {
  return requestPath(path, params, Ext.CUSTOM_ROOT);
}

/**
 * 반복 사용되는 HttpProxy 객체 생성
 *
 * @param string method
 * @param string url
 * @param Object defaultHeaders
 * @returns Ext.data.HttpProxy
 */
function makeHttpProxy(method, url, defaultHeaders) {
  return new Ext.data.HttpProxy({
    method: method,
    prettyUrls: false,
    url: url,
    defaultHeaders: defaultHeaders
  });
}

function getComponentUrls(components) {
  var urls = [];
  Ext.each(components, function (component) {
    var componentPath = '';
    if (typeof component === 'object' || component.isControl) {
      componentPath = '/javascript/ext.ux/' + component.name + '.js';
    } else {
      componentPath = '/javascript/ext.ux/components/' + component + '.js';
    }
    var url = requestCustomPath(componentPath);
    urls.push(url);
  });
  return urls;
}


// Ext.Loader.load([requestCustomPath('/javascript/ext.ux/cas/search-pgm.js')], function () {
//     var win = new Custom.PgmSearchWindow();
//     win.show();
// });