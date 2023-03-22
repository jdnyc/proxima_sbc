Ext.CUSTOM_ROOT = '/custom/ktv-nps';

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

Array.prototype.equals = function (array, strict) {
  if (!array)
    return false;

  if (arguments.length == 1)
    strict = true;

  if (this.length != array.length)
    return false;

  for (var i = 0; i < this.length; i++) {
    if (this[i] instanceof Array && array[i] instanceof Array) {
      if (!this[i].equals(array[i], strict))
        return false;
    }
    else if (strict && this[i] != array[i]) {
      return false;
    }
    else if (!strict) {
      return this.sort().equals(array.sort(), true);
    }
  }
  return true;
}


// Ext.Loader.load([requestCustomPath('/javascript/ext.ux/cas/search-pgm.js')], function () {
//     var win = new Custom.PgmSearchWindow();
//     win.show();
// });