// (function () {
Ext.ns('Ariel.glossary');
Ariel.glossary.ChangeStatus = new Ext.extend(Object, {
    /**
     * 
     * @param string statusCode 상태코드 ACCEPT or REFUSE 
     * @param string apiUrl request 요청 url
     * @param string clickRowId 클릭한 레코드의 id
     * @param Object store 리로드 하기위한 스토어
     */
    changeStatus: function (statusCode, apiUrl, store) {

        if (statusCode == 'ACCEPT') {
            var statusStr = '승인';
        } else if (statusCode == 'REFUSE') {
            var statusStr = '반려';
        }

        return Ext.Ajax.request({
            method: 'GET',
            url: apiUrl,
            callback: function (opts, success, resp) {

                if (success) {
                    try {
                        /**
                         * DTO에 등록이 안된 자동으로 들어가는 컬럼들
                         */
                        var notDtoArr = [
                            `id`, `regist_dt`, `updt_dt`, `regist_user_id`, `updt_user_id`, `registerer`, `updater`, 'delete_dt',
                            'domain', 'field'
                        ];
                        var r = Ext.decode(resp.responseText).data;

                        /**
                         * 요청상태에서만 승인 과 반려가 가능하다??
                         */
                        // sttus_code 값이 이상함
                        // console.log(r);
                        // console.log(statusCode);
                        // console.log(statusCode);

                        if (!(statusCode == r.sttus_code)) {
                            /**
                             * 승인과 요청 값이 다르면 바꿔주기 
                             */
                            r.sttus_code = statusCode;

                            for (var key in r) {
                                if (r[key] == null) {
                                    delete r[key];
                                };
                            }

                            Ext.each(Object.keys(r), function (key, idx1, e) {
                                Ext.each(notDtoArr, function (notKey, idx2, e) {
                                    if (key == notKey) {
                                        delete r[key];
                                    }
                                })
                            });

                            Ext.Ajax.request({
                                method: 'PUT',
                                url: apiUrl,
                                params: r,
                                callback: function (opts, success, resp) {
                                    if (success) {
                                        try {
                                            store.reload();
                                            Ext.Msg.alert('알림', statusStr + '되었습니다.');
                                        } catch (e) {
                                            Ext.Msg.alert(e['name'], e['message']);
                                        }
                                    } else {
                                        Ext.Msg.alert('status: ' + resp.status, resp.statusText);
                                    }
                                }
                            })
                        } else {
                            Ext.Msg.alert('알림', '이미 ' + statusStr + ' 상태입니다.');
                        }
                    } catch (e) {
                        Ext.Msg.alert(e['name'], e['message']);
                    }
                } else {
                    Ext.Msg.alert('status: ' + resp.status, resp.statusText);
                }
            }
        });
    }

});
Ariel.glossary.ChangeStatus = new Ariel.glossary.ChangeStatus();
// })();