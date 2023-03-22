
Ext.ns('Custom');
Custom.ProximaPlayer = Ext.extend(Ext.Container, {
  
      // properties
      _config: null,
      updateFps : 30,//프레임레이트
      frameRate: 30,
      mediaSrc: null,//파일경로
      mode: 'inout',//작업 모드
      autoPlay: true,
      constructor: function (config) {  
        this.addEvents('setinout');
        Ext.apply(this, {}, config || {});  
        Custom.ProximaPlayer.superclass.constructor.call(this);
      },
  
      initComponent: function (config) {
        this._initItems();
  
        Ext.apply(this, {}, config || {});
        Custom.ProximaPlayer.superclass.initComponent.call(this);
      },
  
      setConfig: function (config) {
        this._config = config;
      },
  
      _initItems: function () {
        var _this = this;
        _this.playerId = Ext.id(); 
        if(_this.autoPlay && _this.mediaSrc ){
           var autoPlayVal = 'autoplay';
        }else{
            var autoPlayVal = '';
        }
        if( _this.mediaSrc ){
            var srcVal = '<source src="'+_this.mediaSrc+'" type="video/mp4"></source>';
        }else{
            var srcVal = '';
        }
        _this.html = '<video id="'+_this.playerId+'" class="vjs-skin-twitchy video-js vjs-big-play-centered vjs-preview-customize" preload="auto" '+autoPlayVal+' controls style="width:100%;height:100%;" data-setup=\'{ "inactivityTimeout": 0, "playbackRates": [0.5, 0.8, 1, 1.5, 2, 4] , "update_fps":'+_this.updateFps+'}\'>'+srcVal+'</video>';

        _this.listeners = {
            afterrender: function(self){
                _this._playerRender();
            }
        };
      },
      _getPlayerId : function(){
        return this.playerId;
      },
      //재생경로 입력
      _setSrc : function(source){

        var playerId = this._getPlayerId();
        videojs(playerId).src(source);
        videojs(playerId).play();
        return true;
      },
      _playerRender: function(){
          
          var _this = this;
            var frameRate = _this.frameRate;
            var playerId = _this._getPlayerId();

            videojs(playerId).ready(function(){
            
                //var playbackRates = JSON.parse(document.getElementById(playerId).getAttribute('data-setup')).playbackRates;
                var videojs_player = this;
                var controlBar;
                var controlArray = [];
                
                videojs_player.markers({
                    markerStyle: {
                        'width':'3px',
                        'background-color': 'red'
                    },
                    markers: [
                    ]
                });
                var playBtn = document.getElementsByClassName('vjs-play-control')[0];

                var frameBackBtn = _this._addKey('css', 'fa fa-lg fa-step-backward', '1초 뒤로' , function () {
                    var cur_time = videojs_player.currentTime();
                    videojs_player.currentTime(cur_time - 1);
                } );
                var frameThreeBackBtn = _this._addKey('css', 'fa fa-lg fa-backward', '3초 뒤로' , function () {
                    var cur_time = videojs_player.currentTime();
                    videojs_player.currentTime(cur_time - 3);
                } );
                
                var frameNextBtn = _this._addKey('css', 'fa fa-lg fa-step-forward', '1초 앞으로' , function () {
                    var cur_time = videojs_player.currentTime();
                    videojs_player.currentTime(cur_time + 1);
                } );
                var frameThreeNextBtn = _this._addKey('css', 'fa fa-lg fa-forward', '3초 앞으로' , function () {
                    var cur_time = videojs_player.currentTime();
                    videojs_player.currentTime(cur_time + 3);
                } );

                var setInBtn = _this._addKey('text', '{', 'Set In' , function () {
                    var markers = videojs_player.markers.getMarkers();
                    var current_time = videojs_player.currentTime();
                    for (var i = 0; i < markers.length; i++) {
                        if (markers[i].mark_type == 'MARK_IN'){
                            videojs_player.markers.remove([i]);
                        }
                    }
                    videojs_player.markers.add([{
                        time: videojs_player.currentTime(),
                        text: '{',
                        time_code: _this.secFrameToTimecode(videojs_player.currentTime(), _this.frameRate),
                        class: "mark-sec-in",
                        mark_type: 'MARK_IN'
                    }]);
                } );
                var setOutBtn = _this._addKey('text', '}', 'Set Out' , function () {
                    var markers = videojs_player.markers.getMarkers();
                    var current_time = videojs_player.currentTime();
                    for (var i = 0; i < markers.length; i++) {
                        if (markers[i].mark_type == 'MARK_OUT'){
                            videojs_player.markers.remove([i]);
                        }
                    }
                    videojs_player.markers.add([{
                        time: videojs_player.currentTime(),
                        text: '}',
                        time_code: secFrameToTimecode(videojs_player.currentTime(), _this.frameRate),
                        class: "mark-sec-out",
                        mark_type: 'MARK_OUT'
                    }]);
                } );

                var setValBtn = _this._addKey('css', 'fa fa-lg fa-angle-double-down', '입력' , function () {
                    var getValue = _this._getValue();
                    _this.fireEvent('setinout', this, getValue);
                } );
                var resetValBtn = _this._addKey('css', 'fa fa-lg fa-refresh', '초기화' , function () {
                     _this._resetMarkers();
                } );
                controlArray.push(frameThreeBackBtn);
                controlArray.push(frameBackBtn);
                controlArray.push(playBtn);
                controlArray.push(frameNextBtn);
                controlArray.push(frameThreeNextBtn);

                if( _this.mode == 'inout' ){
                    controlArray.push(setInBtn);
                    controlArray.push(setOutBtn);
                    controlArray.push(setValBtn);
                    
                    controlArray.push(resetValBtn);
                }

                var space_div = document.createElement('div');
                space_div.className = 'vjs-control-space-custom';

                // Get control bar and insert before elements
                controlBar = document.getElementsByClassName('vjs-custom-control-spacer')[0];
                var remainingTime = document.getElementsByClassName('vjs-remaining-time')[0];
                remainingTime.style.display = "none";

                //커스텀 버튼 추가
                // Insert the icon div in proper location
                for(var i=0; i < controlArray.length;i++){
                    controlBar.appendChild(controlArray[i]);
                }

                videojs_player.hotkeys({
                    volumeStep: 0.1,
                    seekStep: 1/_this.frameRate,
                });
                videojs_player.on('loadedmetadata', function(){
                    this.bigPlayButton.hide();
                });
                videojs_player.on('pause', function() {
                    this.bigPlayButton.show();
                    videojs_player.one('play', function() {
                        this.bigPlayButton.hide();
                    });
                });
            });
            var videoplayer = document.getElementById(playerId);
            if (videoplayer.addEventListener) {
                videoplayer.addEventListener('contextmenu', function(e) {
                    e.preventDefault();
                }, false);
            } else {
                videoplayer.attachEvent('oncontextmenu', function() {
                    window.event.returnValue = false;
                });
            }
      },
      //툴바 키 입력
    _addKey : function(type, code, name , hanlder ){
        var keyBtn = document.createElement('div');
        //keyBtn.id = code;
        keyBtn.className = ' vjs-control-custom';

        if(type == 'text'){
        var text = document.createElement('p');
        text.innerHTML = code ;
        keyBtn.appendChild(text);
        }else{
        var css = document.createElement('span');
        css.className = code;
        keyBtn.appendChild(css);
        }

        keyBtn.title = name;
        keyBtn.onclick = hanlder;
        return keyBtn;
    },
    //인아웃점
    _getMarkers: function(){
        var playerId = this._getPlayerId();
        var markers = videojs(playerId).markers.getMarkers();
        return markers;
    },
    _resetMarkers: function(){
        var playerId = this._getPlayerId();
        videojs(playerId).markers.removeAll();
        return true;
    },
    //인아웃값 조회
    _getValue: function(type){
        var markers =this._getMarkers();

        for (var i = 0; i < markers.length; i++) {
            if(type == 'code'){
                var sec = this.secFrameToTimecode(markers[i].time , this.frameRate);
            }else{                            
                var sec =  markers[i].time ;
            }
            if (markers[i].mark_type == 'MARK_IN'){
                var setInSec = sec;
            } else if (markers[i].mark_type == 'MARK_OUT'){
                var setOutSec = sec;
            }
        }
        var returnVal = {
            set_in : setInSec,
            set_out : setOutSec
        };
        return returnVal;
    },
    secFrameToTimecode: function(sec, frame_rate) {
        var h = parseInt(sec / 3600);
        var i = parseInt((sec % 3600) / 60);
        var s = parseInt((sec % 3600) % 60);
        var f = Math.floor((sec - parseInt(sec)) * frame_rate);
        //f= Math.round(f);
      
        h = String.leftPad(h, 2, "0");
        i = String.leftPad(i, 2, "0");
        s = String.leftPad(s, 2, "0");
        f = String.leftPad(f, 2, "0");
        var time = h + ":" + i + ":" + s + ":" + f;
        return time;
      },      
      frameToTimecode: function(frame, frame_rate) {
        var sec = parseInt(frame / frame_rate);
        var h = parseInt(sec / 3600);
        var i = parseInt((sec % 3600) / 60);
        var s = parseInt((sec % 3600) % 60);
        var f = Math.floor(frame - parseInt(sec) * frame_rate);
        //f= Math.round(f);
      
        h = String.leftPad(h, 2, "0");
        i = String.leftPad(i, 2, "0");
        s = String.leftPad(s, 2, "0");
        f = String.leftPad(f, 2, "0");
        var time = h + ":" + i + ":" + s + ":" + f;
        return time;
      }
  
});
Ext.reg('c-player', Custom.ProximaPlayer);