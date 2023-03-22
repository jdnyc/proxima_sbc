/**
 * Created by cerori on 2015-04-08.
 */

// 익스플로러에서 console 에러 방지 및 운영어서 console 안나오도록 처리
// if (window['console'] === undefined || console.log === undefined ) {
//     console = {log: function() {}, info: function() {}, warn: function () {}, error: function() {}};
// } else if ( ! location.href.match('192.168') && ! location.href.match('127.0.0.1')) {
//     console.log = console.info = console.warn = console.error = function () {};
// }

function extractJsonFromModel(models) {
  var result = [];

  models = models || [];

  Ext.each(models, function (item) {
    result.push(item.json);
  });

  return result;
}

function grant_check(grant) {
  var result = false;

  for (var key in Ariel.my) {
    //console.log('(' + Ariel.my[key] + ' & ' + grant + ') == ' + grant);
    //console.log((Ariel.my[key] & grant));

    if ((Ariel.my[key] & grant) > 0) {
      result = true;
      break;
    }
  }

  return result;
}

/**
 * 파라미터 값을 추가하기 위해 한번더 래핑한 함수
 * @param {*} self
 * @param {*} params
 */
function openDetailWindowWithParams(self, params) {
  var that = self;
  self.load = new Ext.LoadMask(Ext.getBody(), {
    msg: '상세 정보를 불러오는 중입니다...'
  });
  self.load.show();
  if (!Ext.Ajax.isLoading(self.isOpen)) {
    that.isOpen = Ext.Ajax.request({
      url: '/javascript/ext.ux/Ariel.DetailWindow.php',
      params: params,
      callback: function (self, success, response) {
        if (success) {
          that.load.hide();
          try {
            var r = Ext.decode(response.responseText);
            if (r !== undefined && !r.success) {
              Ext.Msg.show({
                title: '경고',
                msg: r.msg,
                icon: Ext.Msg.WARNING,
                buttons: Ext.Msg.OK
              });
            }
          } catch (e) {
            // alert(response.responseText)
            // Ext.Msg.alert(e['name'], e['message']);
            //console.log('response.responseText', response.responseText);
            console.log('e', e);
          }
        } else {
          Ext.Msg.alert(
            '서버 오류',
            response.statusText + '(' + response.status + ')'
          );
        }
      }
    });
  } else {
    that.load.hide();
  }
}

function openDetailWindow(self, content_id, record) {
  self.load = new Ext.LoadMask(Ext.getBody(), {
    msg: '상세 정보를 불러오는 중입니다...'
  });
  self.load.show();
  var that = self;

  var params = {
    content_id: content_id,
    record: record
  };
  openDetailWindowWithParams(self, params);
}

/**
 * Ext.Ajax를 랩핑하여 간소하게 사용할 수 있는 함수
 *
 * @param {string} url ajax 호출 url
 * @param {string} method GET, POST, PUT, DELETE와 같은 HTTP Method
 * @param {object} payload POST, PUT, DELETE 시 message body로 넘길 객체
 * @param {function} success 메세지 디코드 후 r.success가 참일때 동작할 콜백 함수
 * @param {function} fail 메세지 디코드 후 r.success가 거짓일때 동작할 콜백 함수(에러 팝업창이 먼저 뜸)
 * @param {object} scope function scope
 */
function ajax(url, method, payload, success, fail, scope) {
  if (scope === undefined || scope === null) {
    scope = window;
  }
  Ext.Ajax.request({
    url: url,
    method: method,
    jsonData: payload,
    success: function (response, opts) {
      try {
        var r = Ext.decode(response.responseText);
        if (r.success) {
          if (success === undefined || success === null) return;
          success(scope, r);
        } else {
          console.error('error', e);
          Ext.Msg.alert('오류', r.msg);
          if (fail === undefined || fail === null) return;
          fail(scope, r);
        }
      } catch (e) {
        console.error('exception:', e);
        Ext.Msg.alert(
          e['name'],
          e['message'] + '<br/>' + response.responseText
        );
        if (fail === undefined || fail === null) return;
        fail(scope, e['message']);
      }
    },
    failure: function (response, opts) {
      Ext.Msg.alert(_text('MN01098'), response.responseText);
      if (fail === undefined || fail === null) return;
      fail(scope, response.responseText);
    },
    scope: scope
  });
}

/**
 * Ext.Ajax를 랩핑하여 간소하게 사용할 수 있는 함수(폼 데이터 전송용)
 *
 * @param {string} url ajax 호출 url
 * @param {string} method GET, POST, PUT, DELETE와 같은 HTTP Method
 * @param {object} params POST, PUT, DELETE 시 message body로 넘길 객체
 * @param {function} success 메세지 디코드 후 r.success가 참일때 동작할 콜백 함수
 * @param {function} fail 메세지 디코드 후 r.success가 거짓일때 동작할 콜백 함수(에러 팝업창이 먼저 뜸)
 */
function ajaxForm(url, method, params, success, fail) {
  Ext.Ajax.request({
    url: url,
    method: method,
    params: params,
    success: function (response, opts) {
      try {
        var r = Ext.decode(response.responseText);
        if (r.success) {
          if (success === undefined) return;
          success(r);
        } else {
          Ext.Msg.alert('오류', r.msg);
          if (fail === undefined) return;
          fail(r);
        }
      } catch (e) {
        Ext.Msg.alert(
          e['name'],
          e['message'] + '<br/>' + response.responseText
        );
        return;
      }
    },
    failure: function (response, opts) {
      Ext.Msg.alert(_text('MN01098'), response.responseText);
    }
  });
}

/**
 * 요청 path을 생성해줌
 *
 * @param string path
 * @param object params key를 속성으로 가지는 객체 {key1: value, key2: value}
 * @param string basePath 기본 경로가 있으면 path앞에 기본 경로를 붙여줌
 * @returns
 */
function requestPath(path, params, basePath) {
  if (params) {
    var keys = Object.keys(params);
    var queryStringArray = [];
    for (var i = 0; i < keys.length; i++) {
      var key = keys[i];
      queryStringArray.push(encodeURI(key + '=' + params[key]));
    }

    if (queryStringArray.length) {
      path += '?' + queryStringArray.join('&');
    }
  }

  if (basePath !== undefined) {
    path = basePath + path;
  }
  return path;
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
    // ,
    // headers: {
    //   'Content-Type': 'application/json',
    //   'X-API-KEY': 'B+Hqhy*3GEuJJmk%',
    //   'X-API-USER': 'admin'
    // }
  });
}

/**
 * API에 대한 요청 path을 생성해줌
 *
 * @param string path
 * @param object params key를 속성으로 가지는 객체 {key1: value, key2: value}
 * @param string basePath 기본 경로가 있으면 path앞에 기본 경로를 붙여줌
 * @returns
 */
function requestApiPath(path, params, basePath) {
  var path = requestPath('/api/v1' + path, params, basePath);

  return path;
}

/**
 * 안전한 Height 조회
 *
 * @param integer minHeight
 * @returns integer
 */
function getSafeHeight(minHeight) {
  var screenHeight = Ext.getBody().getViewSize().height * 0.9;
  if (minHeight > screenHeight) {
    return screenHeight;
  }
  return minHeight;
}

function getByteLength(s, b, i, c) {
  // 한글을 3바이트로 하고 싶으면
  //for(b=i=0;c=s.charCodeAt(i++);b+=c>>11?3:c>>7?2:1);
  // 한글을 2바이트로 하고 싶으면
  for (b = i = 0; (c = s.charCodeAt(i++)); b += c >> 11 ? 2 : c >> 7 ? 2 : 1);
  return b;
}

Ext.ns('Ariel');

Ariel.grant = {
  REVIEW_REQUEST: 1 << 11,
  REVIEW_ACCEPT: 1 << 12,
  REVIEW_REJECT: 1 << 13
};

Ariel.versioning = {
  ver: '3.0.0',
  numberingScripts: function (scripts) {
    var newScripts = [];
    var v = this.ver;
    Ext.each(scripts, function (path) {
      var vPath = path + '?v=' + v;
      newScripts.push(vPath);
    });
    return newScripts;
  },
  numberingScript: function (script) {
    var v = this.ver;
    var newScript = script + '?v=' + v;
    return newScript;
  },
  setVer: function (ver) {
    this.ver = ver;
  }
};

//크로미움여부 글로벌 변수
Ariel.chromiumCheck = function () {
  var isChromium = false;
  for (var i = 0; i < navigator.plugins.length; i++) {
    if (navigator.plugins[i].name == 'Chromium PDF Viewer') {
      isChromium = true;
    }
  }
  return isChromium;
};

Ariel.isChromium = Ariel.chromiumCheck();

//크로미움 드래그앤드랍 처리
//이미지 경로 변경
//백그라운드 처리
Ariel.ddEvent = function (evt, record) {
  //크로미움 여부
  if (Ext.isChrome) {
    var contentId = record.get('content_id');

    //원본 확장자
    var oriExt = record.json.ori_ext;

    //원본 온라인여부
    var oriStatus = record.json.ori_status;

    //원본 유형
    var bsContentId = record.json.bs_content_id;

    //리스토어 여부
    var restoreAt = record.json.restore_at;
    var ori_path = record.get('ori_path');
    var title = record.get('title');

    if (
      !Ext.isEmpty(oriExt) &&
      bsContentId == MOVIE &&
      //oriStatus == 0 &&
      oriExt.toLowerCase() != 'mxf' &&
      oriExt.toLowerCase() != 'mov'
    ) {
      Ext.example.msg(
        '드래그&드롭',
        '허용된 확장자(MXF,MOV)만 드래그&드롭이 가능합니다.'
      );
      console.log('Ariel.ddEvent not allowed ext', oriExt);
      console.log('Ariel.ddEvent not allowed bsContentId', bsContentId);
      evt.preventDefault();
      return false;
    }

    if (oriStatus != 0) {
      Ext.example.msg(
        '드래그&드롭',
        '원본 영상이 메인에 없습니다.<br />리스토어 해주세요.'
      );
    }

    if (!Ariel.isChromium) {
      Ext.example.msg('드래그&드롭', '전용 브라우저가 아닙니다.');
      evt.preventDefault();
      return false;
    } else {
      // 사용금지 여부
      var usePrhibtAt = record.get('usr_meta').use_prhibt_at;
      if (usePrhibtAt == 'Y') {
        Ext.example.msg('드래그&드롭', '사용금지 된 콘텐츠 입니다. 아카이브팀에게 문의 바랍니다');
        evt.preventDefault();
        return false;
      }
      // // 엠바고 해제 일시
      // var embgRelisDt = record.get('usr_meta').embg_relis_dt;
      // if(!Ext.isEmpty(embgRelisDt)){
      //   var embargoTimeStamp = YmdHisToDate(embgRelisDt).getTime();
      //   var nowTimeStamp = new Date().getTime();
      //   if(embargoTimeStamp > nowTimeStamp){
      //     Ext.example.msg('드래그&드롭', '엠바고 해제일시가 지난 후 사용해주세요.');
      //     evt.preventDefault();
      //     return false;
      //   }
      // }
    }

    var highres_web_root = '/rootdata';
    if (record.get('highres_web_root')) {
      highres_web_root = record.get('highres_web_root');
    }
    var highres_path = record.json.highres_path;
    var host = window.location.host;
    var mediaInfo = new Object();
    mediaInfo['thumb'] =
      record.get('thumb_web_root') + '/' + record.get('thumb');
    mediaInfo['originalUrl'] =
      'http://' + host + highres_web_root + '/' + ori_path;
    mediaInfo['proxyUrl'] =
      'http://' +
      host +
      record.get('thumb_web_root') +
      '/' +
      record.get('proxy_path');
    mediaInfo['mediaInfo'] = new Object();
    mediaInfo['mediaInfo']['title'] = title;
    mediaInfo['mediaInfo']['duration'] = record.get('sys_video_rt');
    mediaInfo['mediaInfo']['frame_rate'] = record.get('sys_frame_rate');
    mediaInfo['mediaInfo']['display_size'] = record.get('sys_display_size');

    var mediaInfo_str = JSON.stringify(mediaInfo);

    if (Ext.isMac) {
      var re_dd_path = record.json.highres_mac_path + '/' + ori_path;
    } else if (Ext.isLinux) {
      var re_dd_path = record.json.highres_unix_path + '/' + ori_path;
    } else {
      var re_dd_path = highres_path + '/' + ori_path;
      re_dd_path = re_dd_path.replace(/\//gi, '\\\\');
    }

    evt.dataTransfer.setData('text/mediainfo', mediaInfo_str);
    var video_path = 'application/gmsdd:{"medias":["' + re_dd_path + '"]}';

    evt.dataTransfer.setData('DownloadURL', video_path);

    //리스토어된 영상만 만료연장

    var url = requestApiPath(
      '/contents/' + contentId + '/dd-event',
      undefined,
      undefined
    );
    var method = 'POST';
    var payload = {};
    Ext.Ajax.request({
      url: url,
      method: method,
      jsonData: payload,
      success: function (response, opts) {
        if (Ext.isChrome) {
          console.log('success', response);
        }
      },
      failure: function (response, opts) {
        if (Ext.isChrome) {
          console.log('failure', response);
        }
      }
    });
  } else {
    evt.preventDefault();
    return false;
  }
};
