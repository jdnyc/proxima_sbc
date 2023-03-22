function ContentStatus(code, name, color, icon) {
    this.code = code;
    this.name = name;
    this.color = color;
    this.icon = icon;
}

function ContentStatusManager() {
    this.contentStatuses = [];
}

ContentStatusManager.prototype = {
    /**
     * 콘텐츠 상태코드 조회 및 CSS 생성
     */
    load: function() {
        var manager = this;
        Ext.Ajax.request({
            method: 'GET',
            url: '/store/content/get_content_status.php',
            callback: function(opts, success, response) {                
                if(success) {
                    try {
                        var r = Ext.decode(response.responseText);
                        if (r.success) {                            
                            contentStatuses = [];
                            var data = r.data;
                            for(var i = 0; i < data.length; i++) {
                                var contentStatus = new ContentStatus(data[i].code, data[i].name, data[i].color, data[i].icon);                                
                                manager.contentStatuses.push(contentStatus);                                
                            }                            
                            manager.createContentListBgColorStyleSheet(manager.contentStatuses);
                        } else {
                            Ext.Msg.alert('오류', r.msg);
                        }
                    }
                    catch(e) {
                        Ext.Msg.alert(e['name'], e['message']);
                    }
                } else {
                    Ext.Msg.alert('서버 오류', response.statusText);
                }
            }
        });
    },
    /**
     * 상태코드로 상태 객체 조회
     */
    getStatus: function (statusCode) {
        if(this.contentStatuses == null || this.contentStatuses.length == 0) {
            return null;
        }
        for(var i=0; i < this.contentStatuses.length; i++) {
            if( this.contentStatuses[i].code == statusCode ) {
                return this.contentStatuses[i];
            }
        }
        return null;
    },
    /**
     * 콘텐츠 목록에서 보여질 배경색 CSS 생성
     */
    createContentListBgColorStyleSheet: function(contentStatuses) {
        Ext.util.CSS.refreshCache();
        Ext.each(contentStatuses, function(status) {  
            if(!Ext.isEmpty(status.code)) {
                var css = '.content-status-' + status.code + ' { background-color:' + status.color + ' !important; }';
                // console.log(css);
                Ext.util.CSS.createStyleSheet(css);
            }                      
        });
    }
}
