<?php
    require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
    session_start();
    require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lib.php');
    require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

    $user_id = $_SESSION['user']['user_id'];
    $user_nm = $_SESSION['user']['KOR_NM'];

    $action = $_POST['action'];
    $type = $_POST['type'];
    $notice_id = $_POST['notice_id'];

    $notice = array();
    $hidden_field_system = "hidden : true,";
    $min_height = 630;
    $auto_height = $_POST['screen_height']*0.7;
    $auto_width = $_POST['screen_width']*0.7;

    if( $min_height > $auto_height){
        $auto_height = $min_height;
    }
    $empty_height = 290;
    $btn_text = '';
    if( $type == 'insert' ){
        $notice['notice_type'] = 'all';
        $notice['from_user_id']= $user_id;
        $notice['from_user_nm']= $user_nm;
        $notice_start = date("Y-m-d");
        $notice_end = date("Y-m-d", strtotime(date("Y-m-d"). "+7day"));
        $notice_reg =  date("Y-m-d");
        $btn_text = '<span style="position:relative;top:1px;"><i class="fa fa-plus" style="font-size:13px;"></i></span>&nbsp;'._text('MN00033');
    }else{
        $btn_text = '<span style="position:relative;top:1px;"><i class="fa fa-edit" style="font-size:13px;"></i></span>&nbsp;'._text('MN00043');
        $description = '';

        if (!empty($notice_id)) {
            insertLogNotice('read_notice', $user_id, $notice_id, $description);

            $query = "
            SELECT	N.*, F.FILE_NAME, F.FILE_PATH, M.USER_NM AS FROM_USER_NM
            FROM		BC_NOTICE N
                            LEFT JOIN	BC_NOTICE_FILES F
                            ON				N.NOTICE_ID = F.NOTICE_ID
                            LEFT JOIN	BC_MEMBER M
                            ON				M.USER_ID = N.FROM_USER_ID
            WHERE	N.NOTICE_ID = ".$notice_id."
        ";
            $db->setLoadNEWCLOB(true);
            $notice = $db->queryRow($query);
            if ($type == 'edit') {
                if ($notice['notice_type'] == 'all') {
                } else {
                    $query_to = "
                    SELECT	M.MEMBER_ID as to_id, M.USER_NM as to_name, M.USER_ID AS USER_ID,
                                'u' AS TYPE_NOTICE
                    FROM		BC_NOTICE_RECIPIENTS N, BC_MEMBER M
                    WHERE	N.MEMBER_ID = M.MEMBER_ID
                    AND		N.NOTICE_ID = ".$notice_id."
                    UNION ALL
                    SELECT	N.MEMBER_GROUP_ID as to_id, M.MEMBER_GROUP_NAME as to_name, M.MEMBER_GROUP_NAME AS USER_ID,
                                'g' AS TYPE_NOTICE
                    FROM		BC_NOTICE_RECIPIENTS N, BC_MEMBER_GROUP M
                    WHERE	N.MEMBER_GROUP_ID = M.MEMBER_GROUP_ID
                    AND		N.NOTICE_ID = ".$notice_id."
                ";
                    $notice_to = $db->queryAll($query_to);
                    $to_ids = array();
                    $to_nm_ids = array();
                    $to_g_ids = array();
                    $to_g_nm_ids = array();
                    foreach ($notice_to as $to_id) {
                        if ($to_id['type_notice'] == 'u') {
                            array_push($to_ids, $to_id['to_id']);
                            array_push($to_nm_ids, $to_id['to_name']."[".$to_id['user_id']."]");
                        } elseif ($to_id['type_notice'] == 'g') {
                            array_push($to_g_ids, $to_id['to_id']);
                            array_push($to_g_nm_ids, $to_id['to_name']);
                        }
                    }

                    if (count($to_g_nm_ids) > 0) {
                        $groups = join(',', $to_g_nm_ids)."\\r";
                    }

                    $notice['to_user_ids'] = join(',', $to_ids);
                    ///$notice['to_user_names'] = join(',', array_merge($to_nm_ids,$to_g_nm_ids));
                    $notice['to_user_names'] = $groups.join(',', $to_nm_ids);
                    $notice['to_group_ids'] = join(',', $to_g_ids);
                }
            } else {
                $hidden_field = "hidden : true,";
                $hidden_field_system = "";
                if ($action == 'main_view') {
                    $hidden_field_system = "hidden: true,";
                }
                $html_readonly = "readOnly: true,";
                //$html_readonly = "disable: true,";
                $min_height = 500;
                $empty_height = 190;
            }

            $notice_start = empty($notice['notice_start']) ? '' : date("Y-m-d", strtotime($notice['notice_start']));
            $notice_end = empty($notice['notice_end']) ? '' : date("Y-m-d", strtotime($notice['notice_end']));
            $notice_reg = empty($notice['created_date']) ? '' : date("Y-m-d", strtotime($notice['created_date']));
            $contents =  addslashes($db->escape($notice['notice_content_c']));
            $contents = str_replace("\r", '', str_replace("\n", '\\n', $contents));
        }
    }


    $auto_height_htmleditor = $auto_height-$empty_height;

    /*
        Array
        (
            [notice_id] => 20
            [notice_title] => test
            [notice_content] => test
            [created_date] => 20160301221619
            [notice_type] => user
            [from_user_id] => tester
            [to_user_id] =>
            [member_group_id] =>
            [depcd] =>
            [fst_order] => 0
        )
    */

?>

(function(){
    var d = new Date();
    var save_win = new Ext.Window({//공지사항 새로작성하기 창
        //layout:'fit',
        //>>,title: '공지사항'
        title: _text('MN00144'),
        height: <?=$auto_height?>,
        minHeight : <?=$min_height?>,
        width: <?=$auto_width?>,
        minWidth : 800,
        draggable : false,//prevent move
        maximizable : true,
        modal: true,
        layout: 'fit',
        autoScroll : true,
        items: [{
            xtype: 'form',
            fileUpload: true,
            //padding: '10px 0px 0px 0px',
            padding: 10,
            id : 'form_notice',
            cls: 'change_background_panel',
            frame : false,
            border : false,
            width : '100%',
            defaults: {
                anchor: '100%',
                labelPad : 10,
                labelAlign: 'top',
                padding: 5,
                labelSeparator: ''
            },
            items: [{
                xtype: 'textfield',
                //fieldLabel: '제목',
                <?=$html_readonly?>
                fieldLabel: _text('MN00249'),
                allowBlank: false,
                autoCreate: {tag: 'input', type: 'text',maxLength: 4000},
                name: 'title',
                value : '<?=$notice['notice_title']?>'
            },{
                xtype : 'compositefield',
                <?=$hidden_field?>
                fieldLabel : _text('MN00222'),
                style: {
                        //background : '#F1F1F1'
                },
                items : [{
                    xtype: 'combo',
                    //fieldLabel: '유형',
                    fieldLabel: _text('MN00222'),
                    name: 'target_type',
                    width : 300,
                    //emptyText: '목록을 선택해주세요',
                    emptyText: _text('MSG01033'),
                    typeAhead: true,
                    triggerAction: 'all',
                    lazyRender: true,
                    mode: 'local',
                    editable: false,
                    //value : 'all',
                    value : '<?=$notice['notice_type']?>',
                    hiddenName: 'target_type',
                    store: new Ext.data.ArrayStore({
                        fields: [
                            'value',
                            'name'
                        ],
                        data: [
                            ['all', _text('MN00008')],//전체
                            ['group', _text('MN00111')],//그룹
                            ['user', _text('MN00189')]//사용자
                        ]
                    }),
                    valueField: 'value',
                    displayField: 'name',
                    listeners: {
                        select: function(self, record, idx){
                            var target = record.get('value');
                            var name = record.get('name');

                            if( target == 'all' )
                            {
                                self.ownerCt.get(1).disable(true);
                                Ext.getCmp('to_list').setValue('');
                                Ext.getCmp('to_user_ids').setValue('');
                                Ext.getCmp('to_group_ids').setValue('');
                            }
                            else
                            {
                                self.ownerCt.get(1).enable(true);
                            }
                        },
                        afterrender : function(self, record, idx){
                            if( self.getValue() == 'all' ){
                                self.ownerCt.get(1).disable(true);
                            }else{
                                self.ownerCt.get(1).enable(true);
                            }
                        }
                    }
                },{
                    xtype: 'button',
                    id: 'notice_detail_type_search_btn',
                    //text : '<span style="position:relative;top:1px;"><i class="fa fa-search" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00037'),//'검색'
                    //width : 60,
                    cls: 'proxima_button_customize',
                    width: 30,
                    text: '<span style="position:relative;top:1px;" title="'+_text('MN00037')+'"><i class="fa fa-search" style="font-size:13px;color:white;"></i></span>',
                    handler: function(btn, e){
                        var url, ids;
                        if( btn.ownerCt.get(0).value == 'group' ){
                            url = '/pages/menu/config/notice/win_group.php';
                            ids = 'to_group_ids';
                        }else{
                            url = '/pages/menu/config/notice/win_user.php';
                            ids = 'to_user_ids';
                        }
                        Ext.Ajax.request({
                            url: url,
                            params: {
                                user_ids : Ext.getCmp('to_user_ids').getValue(),
                                group_ids : Ext.getCmp('to_group_ids').getValue()
                            },
                            callback: function(self, success, response){
                                try {
                                    var r = Ext.decode(response.responseText);
                                    r.show();
                                }
                                catch(e){
                                    //>>Ext.Msg.alert('오류', e);
                                    Ext.Msg.alert(_text('MN00022'), e);
                                }
                            }
                        });
                    }
                },{
                    xtype: 'button',
                    id: 'notice_detail_type_delete_btn',
                    //text : '<span style="position:relative;top:1px;"><i class="fa fa-close" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00034'),//'삭제' Delete
                    //width : 60,
                    cls: 'proxima_button_customize',
                    width: 30,
                    text: '<span style="position:relative;top:1px;" title="'+_text('MN00034')+'"><i class="fa fa-close" style="font-size:13px;color:white;"></i></span>',
                    handler: function(btn, e){
                        Ext.getCmp('to_list').setValue('');
                        Ext.getCmp('to_user_ids').setValue('');
                        Ext.getCmp('to_group_ids').setValue('');
                    }
                } ,
                // 팝업여부
                {
                    xtype : 'displayfield',
                    value : '<div align="right" style="line-height:2;">'+'팝업여부'+'&nbsp;</div>'
                },
                {
                    xtype: 'checkbox',
                    name: 'notice_popup_at',
                    id: 'notice_popup_at',
                    inputValue: 'Y'
                    <?php
                        if($notice['notice_popup_at'] == 'Y') {
                            echo ", checked: true";
                        }
                    ?>
                }]
            },{
                xtype: 'textarea',
                <?=$hidden_field?>
                id: 'to_list',
                name: 'to_list',
                readOnly : true,
                value : '<?=$notice['to_user_names']?>',
                fieldLabel : _text('MN02136')//수신
            },{
                xtype: 'textarea',
                id: 'to_user_ids',
                name: 'to_user_ids',
                value : '<?=$notice['to_user_ids']?>',
                hidden : true,
                fieldLabel : _text('MN02136')//수신
            },{
                xtype: 'textarea',
                id: 'to_group_ids',
                name: 'to_group_ids',
                value : '<?=$notice['to_group_ids']?>',
                hidden : true,
                fieldLabel : _text('MN02136')//수신
            },{
                xtype: 'combo',
                hidden : true,
                fieldLabel: '대상 목록',
                name: 'target_list',
                emptyText: '목록을 선택해주세요',
                editable: false,
                typeAhead: true,
                triggerAction: 'all',
                displayField: 'name',
                valueField: 'value',
                hiddenName: 'target_list',
                hiddenValue: 'value',
                store: new Ext.data.JsonStore({
                    url: '/store/notice/get_list.php',
                    root: 'data',
                    autoLoad: true,
                    baseParams: {
                        type: 'all'
                    },
                    fields: [
                        'name',
                        'value'
                    ]
                })
            },{
                xtype : 'compositefield',
                fieldLabel :_text('MN02207'),//공지기간 Notice Period
                style: {
                        //background : '#F1F1F1'
                },
                items : [{
                    xtype : 'datefield',
                    name : 'start_date',
                    width : 100,
                    format : 'Y-m-d',
                    allformats: 'Ymd|ymd|Y-m-d|y-m-d|Y/m/d|Y/m/d',
                    value : '<?=$notice_start?>',
                    listeners : {
                        afterrender : function(self){
                            if( '<?=$type?>' == 'view' ){
                                self.setDisabled(true);
                            }
                            self.setMaxValue('<?=$notice_end?>');
                        },
                        select: function(self, date){
                            var edate = self.ownerCt.items.items[2];
                            edate.setMinValue(date);
                        }
                    }
                },{
                    xtype : 'displayfield',
                    width : 10,
                    value : '~'
                },{
                    xtype : 'datefield',
                    name : 'end_date',
                    width : 100,
                    format : 'Y-m-d',
                    allformats: 'Ymd|ymd|Y-m-d|y-m-d|Y/m/d|Y/m/d',
                    value : '<?=$notice_end?>',
                    listeners : {
                        afterrender : function(self){
                            if( '<?=$type?>' == 'view' ){
                                self.setDisabled(true);
                            }
                            self.setMinValue('<?=$notice_start?>');
                        },
                        select: function(self, date){
                            var sdate = self.ownerCt.items.items[0];
                            sdate.setMaxValue(date);
                        }
                    }
                },{
                    xtype : 'displayfield',
                    width : 112,
                    height : 20,
                    value : '<div align="right" style="line-height:2;">'+_text('MN00109')+'&nbsp;</div>'//'등록일'
                },{
                    xtype : 'datefield',
                    name : 'created_date',
                    //readOnly : true,
                    width : 100,
                    format : 'Y-m-d',
                    value : '<?=$notice_reg?>',
                    listeners : {
                        afterrender : function(self){
                            self.setDisabled(true);
                        }
                    }
                },{
                    xtype : 'displayfield',
                    width : 70,
                    height : 20,
                    value : '<div align="right" style="line-height:2;">'+_text('MN02206')+'&nbsp;</div>'//'작성자'
                },{
                    xtype : 'textfield',
                    //ieldLabel : _text('MN02206'),//작성자
                    width : 120,
                    name : 'from_user',
                    readOnly : true,
                    hidden: true,
                    value : '<?=$notice['from_user_id']?>'
                },{
                    xtype : 'textfield',
                    //ieldLabel : _text('MN02206'),//작성자
                    width : 120,
                    name : 'from_user_nm',
                    readOnly : true,
                    value : '<?=$notice['from_user_nm']?>'
                }]
            },{
                xtype: 'htmleditor',
                <?=$html_readonly?>
                //fieldLabel: '내용',
                fieldLabel: _text('MN00067'),
                autoScroll : true,
                boxMinHeight : 330,
                height : <?=$auto_height_htmleditor?>,
                name: 'contents',
                contentsLenghth : 4000,

                listeners: {
                    beforesync : function( editor, htmlvalue ) {
                    },
                    afterrender : function(self){
                        self.setValue('<?=$contents?>');
                    },
                    render: function(self){
                        if( '<?=$type?>' == 'view' ){
                            self.getToolbar().hide();
                        }
                    },
                    resize : function(self, width, height){
                        //self.setHeight(<?=$_POST['screen_height']?>);

                    }
                }
            },{
                xtype: 'fileuploadfield',
                hidden: true,
                id: 'fileAttachUpload',
                name: 'FileAttach',
                listeners: {
                    fileselected: function(self, value){
                        Ext.getCmp('fileAttachFakePath').setValue(value);
                    }
                }
            },{
                xtype: 'compositefield',
                hidden: true,
                fieldLabel: _text('MN01045'),//'첨부 파일',
                style: {
                        //background : '#F1F1F1'
                },
                items: [{
                    xtype: 'textfield',
                    id: 'fileAttachFakePath',
                    //allowBlank: false,
                    readOnly: true,
                    flex: 1,
                    value : '<?=$notice['file_path']?>'
                },{
                    xtype: 'button',
                    <?=$hidden_field?>
                    //text: _text('MN02176'),//'파일선택',
                    //width : 30,
                    id: 'notice_detail_upload_btn',
                    cls: 'proxima_button_customize',
                    width: 30,
                    text: '<span align="center" style="position:relative;top:1px;" title="'+_text('MN01033')+'"><i class="fa fa-ellipsis-h" style="font-size:13px;color:white;"></i></span>',
                    listeners: {
                        click: function(btn, e){
                            $('#'+Ext.getCmp('fileAttachUpload').getFileInputId()).click();
                        }
                    }
                },{
                    xtype: 'button',
                    <?=$hidden_field?>
                    //text: '<span align="center" style="position:relative;top:1px;left:2px;"><i class="fa fa-close" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00034'),//'삭제', Delete
                    //width : 60,
                    id: 'notice_detail_close_btn',
                    cls: 'proxima_button_customize',
                    width: 30,
                    text: '<span align="center" style="position:relative;top:1px;" title="'+_text('MN00034')+'"><i class="fa fa-close" style="font-size:13px;color:white;"></i></span>',
                    listeners: {
                        click: function(btn, e){
                            Ext.getCmp('fileAttachUpload').reset();
                            Ext.getCmp('fileAttachFakePath').setValue('');
                        }
                    }
                },{
                    xtype: 'button',
                    id: 'notice_detail_download_btn',
                    //width : 80,
                    //text: '<span align="center" style="position:relative;top:1px;left:2px;"><i class="fa fa-download" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00050'),//다운로드 Download
                    cls: 'proxima_button_customize',
                    width: 30,
                    text: '<span align="center" style="position:relative;top:1px;" title="'+_text('MN00050')+'"><i class="fa fa-download" style="font-size:13px;color:white;"></i></span>',
                    listeners: {
                        click: function(btn, e){
                            if( Ext.isEmpty(Ext.getCmp('fileAttachFakePath').getValue()) ){
                                Ext.Msg.alert(_text('MN00023'), _text('MSG02040'));
                            }else{
                                var url = "/store/notice/get_list.php?type=download&notice_id=<?=$notice_id?>";
                                var w = window.open(url);
                            }
                        }
                    }
                }]
            }],
            buttonAlign: 'center',
            buttons: [{
                text : '<?=$btn_text?>',
                scale: 'medium',
                <?=$hidden_field?>
                handler: function(b, e){
                    var s = b.ownerCt.ownerCt.get(0);
                    var form_notice = Ext.getCmp('form_notice').getForm();
                    var meta = form_notice.getValues();
                    meta.contents = Ext.getCmp('form_notice').getForm().getFieldValues().contents;
                    //확장자 체크
                    var extension_arr = ['ZIP', 'HWP', 'DOC', 'DOCX','XML', 'PPTX', 'PPT', 'XLS', 'XLSX', 'PDF', 'JPG', 'JPEG', 'PNG', 'MP3','WAV','TXT'];
                    var upload_file = Ext.getCmp('fileAttachUpload').getValue();
                    var filename_arr = upload_file.split('.');

                    if (!s.isValid()) {
                        Ext.Msg.alert(_text('MN00023'), _text('MSG00090'));//제목을 입력해주세요
                        return;
                    }else if( meta.target_type != 'all' && ( Ext.isEmpty(meta.to_user_ids) && Ext.isEmpty(meta.to_group_ids) ) ){
                        Ext.Msg.alert(_text('MN00023'), _text('MSG00073'));//수신자를 선택해주세요
                        return;
                    //}else if( meta.contents.length > 4000 ){
                        ////Ext.Msg.alert(_text('MN00023'), _text('MSG02065'));//내용은 4000자 이내입니다.
                        ////return;
                    }else if( meta.title.length > 4000 ){
                        Ext.Msg.alert(_text('MN00023'), _text('MSG02065'));//내용은 4000자 이내입니다.
                        return;
                    }else if( filename_arr.length > 1 && extension_arr.indexOf(filename_arr[1].toUpperCase()) === -1 ){
                        Ext.Msg.show({
                            title : _text('MN00023'),
                            //width : 200,
                            msg : _text('MN00309') + ' :</br>' +'&nbsp;&nbsp;'+ extension_arr.join(','),
                            buttons : Ext.Msg.OK
                        });//알림, 허용 확장자 :
                        return;
                    }else{
                        Ext.Msg.show({
                            title : _text('MN00023'),
                            msg : _text('MN00046')+' : '+_text('MSG02039'),
                            buttons: Ext.Msg.OKCANCEL,
                            fn: function(btnId, text, opts){
                                if( btnId == 'ok' ){
                                    form_notice.submit({
                                        url: '/store/notice/get_list.php',
                                        params: {
                                            type : '<?=$type?>',
                                            meta : Ext.encode(meta),
                                            notice_id : '<?=$notice_id?>'
                                        },
                                        success: function(form, action) {
                                            save_win.destroy();
                                            Ext.getCmp('notice_grid').getStore().load({params:{start:0, limit:25}});
                                            var r = Ext.decode(action.response.responseText);
                                            save_win.destroy();
                                            if(r.result == 'false') {
                                                Ext.Msg.alert( _text('MN00023'), r.msg);
                                                return;
                                            }
                                        },
                                        failure: function(form, action) {
                                            var r = Ext.decode(action.response.responseText);
                                            Ext.Msg.alert( _text('MN00023'), r.msg);
                                        }
                                    });
                                }
                            }
                        });
                    }
                }
            },{
                text : '<span style="position:relative;top:1px;"><i class="fa fa-close" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00004'),
                scale: 'medium',
                <?=$hidden_field?>
                handler: function(e){
                    save_win.close();
                }
            },{
                text : '<span style="position:relative;top:1px;"><i class="fa fa-close" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00031'),
                scale: 'medium',
                <?=$hidden_field_system?>
                handler: function(e){
                    Ext.getCmp('main_notice_grid').getStore().load();
                    save_win.close();
                }
            },{
                text : '<span style="position:relative;top:1px;"><i class="fa fa-close" style="font-size:13px;"></i></span>&nbsp;'+_text('MSG02527'),
                scale: 'medium',
                <?php
                    if($action != 'main_view') {
                        echo 'hidden: true,';
                    }
                ?>
                handler: function(b, e){
                    var today = new Date();
                    var expiry = new Date(today.add(Date.DAY, 1).format('Y-m-d')+ ' 00:00:00');
                    Ext.util.Cookies.set('notice_popup_cookie_<?=$notice_id?>', 'N', expiry);								
                    save_win.close();
                }
            }]
        }],
        listeners: {
            close : function(self){
                if( Ext.getCmp('main_notice_grid') ){
                    Ext.getCmp('main_notice_grid').getStore().load();
                }
            },
            resize : function( self, width, height ){
                self.get(0).setWidth(width-15);
                Ext.getCmp('form_notice').getForm().findField('contents').setHeight(height-<?=$empty_height?>);
                Ext.getCmp('form_notice').doLayout();
            },
            maximize : function(self){
                //console.log(self.get(0));
                //alert('max');
            }
        }
    });

    return save_win;
})()