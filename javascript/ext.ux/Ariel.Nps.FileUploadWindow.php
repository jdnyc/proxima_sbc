<?php

use ProximaCustom\core\MetadataManager;
require_once( dirname(dirname(__DIR__)).'/vendor/autoload.php');
require_once( dirname(dirname(__DIR__)).'/lib/config.php');
?>
Ariel.Nps.FileUploadWindow = Ext.extend(Ext.Window, {

    constructor: function(config) {

        var thisWindow = this;


        //this.cls = 'dark-window';

        Ext.apply(this, config || {});

        if(Ext.isEmpty(this.uploadUrl)) {
            //thisWindow.uploadUrl = 'http://localhost:3200/upload';
            this.uploadUrl = UPLOAD_URL;
        }

        if (this.uploadUrl === undefined || this.uploadUrl === null) {
            console.error('uploadUrl is empty.');
            this.uploadUrl = '';
        }

        this.fileList = [];

        var btnAddHidden = false;
        if(this.option.is_drag == 'Y') {
            btnAddHidden = true;
        }

        // 콘텐츠 유형 콤보박스
        this.contentTypeComboBox = new Ext.form.ComboBox({//MN00276 content type
            width: 100,
            editable: false,
            displayField:'ud_content_title',
            valueField: 'ud_content_id',
            typeAhead: true,
            beforeValue: '',
            triggerAction: 'all',
            lazyRender:true,
            store: new Ext.data.JsonStore({
                url: '/interface/mam_ingest/get_meta_json.php',
                root: 'data',
                baseParams: {
                    kind : 'ud_content',
                    flag: 'webupload'
                },
                fields: [
                    'ud_content_title',
                    'ud_content_id',
                    'bs_content_id',
                    'allowed_extension'
                ]
            }),
            listeners:{
                afterrender: function(self){
                    var ud_content_id = Ext.getCmp('tab_warp').getActiveTab().ud_content_id;
                    self.getStore().load({
                        callback:function(r,o,s){
                            if( !self.isDestroyed && s && r[0] ){                              
                                self.setValue(ud_content_id);
                                self.beforeValue = ud_content_id;
                                //원본 삭제 알림
                                if(ud_content_id == '1') {
                                    var originalDeleteText = self.ownerCt.get(7);
                                    originalDeleteText.setVisible(true);
                                }

                                var tab = self.ownerCt.ownerCt;
                                tab.get(0).loadFormMetaData(tab.get(0), 0);
                            }
                        }
                    });
                },
                select: function(self, record, index ){
                    var udContentId = record.get('ud_content_id');
                    //if(self.beforeValue == udContentId) return;
                    // originalDeleteText : 원본 콘텐츠는 2주 후 삭제됩니다.
                    var originalDeleteText = self.ownerCt.get(7);
                    if(udContentId == '1') {
                        originalDeleteText.setVisible(true);
                    } else {
                        originalDeleteText.setVisible(false);
                    }
                    var tab = self.ownerCt.ownerCt;
                    tab.get(0).setCustomAction();
                    self.beforeValue = udContentId;
                    tab.get(0).getCustomForm(0).setValues({
                        k_ud_content_id:udContentId,
                        cntnts_ty:udContentId
                    });
                }
            }
        });


        // 파일리스트 그리드
        this.fileListGrid = new Ext.grid.GridPanel({
            //cls: 'dark-panel',
            flex: 1,
            hideHeaders: true,
            store: new Ext.data.ArrayStore({
                id: 0,
                fields: [
                    'name',
                    'size',
                    'progress'
                ],
                data: []
            }),
            cm: new Ext.grid.ColumnModel({
                columns:[
                    {
                        dataIndex: 'name'
                    }, {
                        dataIndex: 'size', 
                        width: 20,
                        renderer: Ext.util.Format.fileSize
                    },
                    new Ext.ux.ProgressColumn({
                        width: 30,
                        dataIndex: 'progress',
                        align: 'center',
                        renderer: function(value, meta, record, rowIndex, colIndex, store, pct) {
                            return Ext.util.Format.number(pct, "0%");
                        }
                    })
                ]
            }),
            viewConfig: {
                forceFit: true
            }
        });

        // 파일업로드 필드
        this.fileUploadField = new Ext.form.FileUploadField({
            hidden: true,
            name: 'FileUpload',
            multiple: true,
            setFiles : function(files) {
                //files, uploaded by add button OR dragged files info.
                var names = [];
                var values = [];
                if (files) {
                    var grid_list_upload_files_store = thisWindow.fileListGrid.getStore();
                    for (var i = 0; i < files.length; i++){

                        if( Ext.isEmpty(files[i].name) ) continue;
                        names.push(files[i].name);
                        values = names.join(':');
                        var tt = grid_list_upload_files_store.recordType;
                        var data = new tt({
                            'name': files[i].name,
                            'size': files[i].size
                        });
                        grid_list_upload_files_store.data.add(data);
                    } 
                }
                var newFiles = Array.from(files);
                
                if(Ext.isEmpty(thisWindow.fileList)){
                    thisWindow.fileList = newFiles;
                }else{
                    thisWindow.fileList = thisWindow.fileList.concat(newFiles);
                }
                thisWindow.fileListGrid.getView().refresh();
           
                if (!Ext.isEmpty(values)) {
                    this.setValue(values);
                }else{
                    return false;
                }

                return true;
            },
            listeners: {
                fileselected: function(self, value,event){
                    if(thisWindow.option.is_drag == 'Y') return;//works only upload by button
                    var upload = this.fileInput.dom;
                    var files = upload.files;
                    self.setFiles(files);
                },
                afterrender: function(self){
                    if(thisWindow.option.is_drag != 'Y') return;//works only upload by Drag and Drop
                   
                    if( !Ext.isEmpty(thisWindow.option.files) && thisWindow.option.files.length > 0){
                        self.setFiles(thisWindow.option.files);
                    }else{
                        thisWindow.close();
                        return false;
                    }
                }
            }
        });

        // 파일 업로드 폼
        this.fileUploadForm = new Ext.form.FormPanel({
            split: true,
            hidden:true,
            fileUpload: true,
            //cls: 'dark-panel',
            border: false,
            frame: true,
            items:[
                this.fileUploadField
            ]
        });

        // Fake 파일 경로 필드
        this.fakeFilePathField = new Ext.form.TextField({
            allowBlank: false,
            hidden:true,
            readOnly: true,
            flex: 1
        });

        this.registerForm = new Ext.TabPanel({
            activeTab: 0,
            cls: "proxima_tabpanel_customize proxima_media_tabpanel",
            defaults:{autoScroll: true},
            frame:true,
            isFirst: true,
            items:[],
            getCustomForm: function(num){
                return this.get(num).getForm();
            },
            setTitleMeta: function(title){
                var titleField = this.get(0).getForm().findField('k_title');
                if(titleField){
                    titleField.setValue(title);
                }
                return;
            },
            loadFormMetaData: function(self, caseView ){
                var myMask = new Ext.LoadMask(Ext.getBody(), {msg:"로딩중입니다..."});
                myMask.show();
    
                var condition = self.getCondition();
                params = {};
                params.ud_content_tab = condition.ud_content_tab;
                params.ud_content_id = condition.ud_content_id;
                //params.user_id = thisWindow.option.user_id;
                //params.lang = thisWindow.option.user_lang;
                if(caseView == 0){
                    var current_category_id;
                    var selected_category = Ext.getCmp('menu-tree').getSelectionModel().getSelectedNode();
                    if(selected_category){
                        current_category_id = selected_category.id;
                    }else{
                        var node = Ext.getCmp('menu-tree').getNodeById(ud_content_id);
                        current_category_id = node.id;
                    }
                    params.current_category_id = current_category_id;
                }
                
                var registerForm = self;
                Ext.Ajax.request({
                    url: '/interface/app/plugin/regist_form/get_metadata.php',
                    params: params,
                    callback: function(opts, success, response){
                        myMask.hide();
                        if (success) {
                            try {
                                var r = Ext.decode(response.responseText);
                                self.removeAll();
                                self.add(r);
                                var customField = self.getCustomField(self);
    
                                if (!Ext.isEmpty(customField)) {
                                    self.get(0).insert(0, customField);
                                }
                                self.doLayout();
                                self.activate(0);
                                
                                self.setCustomAction();

                                if(thisWindow.option.is_drag == 'Y') {
                                    // set default value for user_meta form                                   
                                    var filename = thisWindow.option.files[0].name;
                                    if(filename){
                                        var filename_arr = filename.split('.');
                                        registerForm.setTitleMeta(filename_arr[0]);
                                    }
                                }
                            }
                            catch(e) {
                                Ext.Msg.alert(e['name'], e['message']);
                            }
                        }
                        else {
                            Ext.Msg.alert(_text('MN00022'), opts.url+'<br />'+response.statusText+'('+response.status+')');
                        }
                    }
                });
            },
            put_meta_afterLoadFormMetaData: function(self, params, input_meta_string){
                var tbar = self.ownerCt.getTopToolbar();
                var ud_content_tab = tbar.items.get(1).getValue().getRawValue();
                var ud_content_id = tbar.items.get(5).getValue();
                params = params || {};
                params.ud_content_tab = ud_content_tab;
                params.ud_content_id = ud_content_id;
                //params.user_id = thisWindow.option.user_id;
                //params.lang = thisWindow.option.user_lang;

                Ext.Ajax.request({
                    url: '/interface/app/plugin/regist_form/get_metadata.php',
                    params: params,
                    callback: function(opts, success, response){
                        if (success) {
                            try {
                                var r = Ext.decode(response.responseText);
                                self.removeAll();
                                self.add(r);
                                self.doLayout();
                                self.activate(0);
                                put_meta2(input_meta_string);
                            }
                            catch(e) {
                                Ext.Msg.alert(e['name'], e['message']);
                            }
                        }
                        else {
                            Ext.Msg.alert(_text('MN00022'), opts.url+'<br />'+response.statusText+'('+response.status+')');
                        }
                    }
                });
            },                    
            getCustomForm: function(num){
                return this.get(num).getForm();
            },
            setCustomAction: function(){
                //제작 구분 콘텐츠 유형에 따라 변경
                var self = this;
                var condition = self.getCondition();
                var customField =  self.get(0).getForm().findField('k_custom_field');

                if(condition.ud_content_id == '3'){
                    //마스터본
                    if( condition.ud_content_tab == 'product' ){
                        customField.setCondition('main_tm_on');
                        customField.setCondition('codec_on');
                        //주조전송
                        //코덱
                    }else if( condition.ud_content_tab == 'news' ){
                        //customField.setCondition('sub_tm_on');
                        customField.setCondition('main_tm_off');
                        customField.setCondition('codec_off');
                        //부조전송
                        //코덱 비활성화
                    }else{
                        if (condition.ud_content_tab == 'product') {
                            customField.setCondition('main_tm_off');
                        }else if( condition.ud_content_tab == 'news' ){
                            customField.setCondition('sub_tm_off');
                        }else{
                            customField.setCondition('main_tm_off');
                        }
                        //비활성화                           
                        customField.setCondition('codec_off');
                    }
                } else if(condition.ud_content_id == '9'){
                    //뉴스편집본
                    if( condition.ud_content_tab == 'product' ){
                        customField.setCondition('main_tm_off');
                        customField.setCondition('codec_on');
                        //주조전송
                        //코덱
                    }else if( condition.ud_content_tab == 'news' ){
                        customField.setCondition('sub_tm_on');
                        //customField.setCondition('main_tm_on');
                        customField.setCondition('codec_off');
                        //부조전송
                        //코덱 비활성화
                    }else{
                        if (condition.ud_content_tab == 'product') {
                            customField.setCondition('main_tm_off');
                        }else if( condition.ud_content_tab == 'news' ){
                            customField.setCondition('sub_tm_off');
                        }else{
                            customField.setCondition('main_tm_off');
                        }
                        //비활성화                           
                        customField.setCondition('codec_off');
                    }
                }else{
                    if(condition.ud_content_tab == 'product'){
                        customField.setCondition('main_tm_off');
                    }else if(condition.ud_content_tab == 'news'){
                        customField.setCondition('sub_tm_off');
                    }
                    customField.setCondition('codec_off');
                }

                
                if( condition.ud_content_id == '1'){
                    customField.setCondition('archive_off');
                }else{
                    customField.setCondition('archive_on');
                }
            },
            getCondition: function(){
                //메타데이터 뷰 조건정보
                var self = this;
                var tbar = self.ownerCt.getTopToolbar();
                var ud_content_tab = tbar.items.get(1).getValue().getRawValue();
                var ud_content_id = tbar.items.get(5).getValue();
                return {
                    'ud_content_tab' : ud_content_tab,
                    'ud_content_id' : ud_content_id,
                }
            },
            getCustomField : function(){
                var self = this;
                var condition = self.getCondition();
                //추가 입력폼 생성
                return {
                    hideLabel: true,
                    name:'k_custom_field',
                    xtype: 'compositefield',
                    //layout: 'anchor',
                    defaults: {
                        height: 80,
                        flex: 0.2
                    },
                    items: [{
							xtype: 'fieldset',
							title: '아카이브 여부',
							//height: 80,
							items: [{
                                hideLabel: true,
                                name: 'k_archive_select',
								xtype: 'radiogroup',
								width: 150,
								columns: 2,
								items: [{
										boxLabel: '보관',
										name: 'k_archv_trget_at',
										inputValue: 'Y',
										checked: true
									},
									{
										boxLabel: '보관안함',
										name: 'k_archv_trget_at',
										inputValue: 'N'
									},
								]
							},{
                                xtype: 'label',
                                text: '아카이브 원하면 보관 선택',
                                hidden: true,
                                width: 30,
                                style: {
                                    color: "red",
                                },
                            }]
						},{                               
                        xtype : 'fieldset',
                        hidden : true,
                        title: '사용금지여부',
                        items:[{
                            hideLabel: true,
                            xtype: 'radiogroup',
                            width: 200,
                            columns: 2,
                            items: [
                                {boxLabel: '사용', name: 'k_use_prhibt_at', inputValue:'N' , checked: true},
                                {boxLabel: '사용금지', name: 'k_use_prhibt_at', inputValue:'Y'}
                            ]
                        }]
                    }
                    ,{     
                        // disabled: true,                          
                        xtype : 'fieldset',
                        hidden:true,
                        title: '코덱',
                        items:[{
                            hideLabel: true,
                            name: 'k_codec_select',
                            xtype: 'radiogroup',
                            width: 200,
                            columns: 2,
                            items: [
                                {boxLabel: 'XDCAM HD', name: 'k_codec', inputValue:'xdcam' , checked: true},
                                {boxLabel: 'DVCPRO HD', name: 'k_codec', inputValue:'dvcpro' ,disabled: true}
                            ],
                            listeners:{
                                change: function(self, checked){
                                    var checkedVal = checked.getRawValue();
                                    if (self.beforeValue == checkedVal) return;
                                }
                            }
                        }]
                    },{                              
                        //disabled: true, 
                        xtype : 'fieldset',
                        title: '전송',
                        items: [{
                            name: 'k_send_select',
                            hideLabel: true,
                            xtype: 'checkboxgroup',
                            width: 300,
                            columns: 2,
                            defaults: {
                                width: 100
                            },
                            items: [
                                {
										boxLabel: '주조 전송',
										name: 'k_send_to_main',
										inputValue: 'k_send_to_main',
                                        listeners: {
                                            check: function(self, checked) {
                                                if(checked){
                                                    self.ownerCt.ownerCt.ownerCt.eachItem(function(r,idx){
                                                        if(self.name != r.name){
                                                            r.setValue(false);
                                                        }
                                                    });
                                                }
                                            }
                                        }
                                    },
                                    {
                                        boxLabel: 'A/B부조',
										name: 'k_send_to_sub',
										inputValue: 'k_send_to_sub',
										hidden: true,
									},
									{
                                        boxLabel: '뉴스부조',
										name: 'k_send_to_sub_news',
										inputValue: 'k_send_to_sub_news',
										hidden: true,
									},
									{
										boxLabel: 'QC 확인',
										name: 'k_qc_confirm',
										inputValue: 'k_qc_confirm',
										disabled: true,
                                        hidden: true
									},
                                    
                                    
                            ],
                            listeners:{
                                change: function(self, checked){
                                    var isValidQc = false;
                                    if(checked.length === 1 ) {
                                        if(checked[0].name == 'k_send_to_sub') {
                                            self.ownerCt.get(2).setVisible(true);
                                            self.ownerCt.get(3).setVisible(false);
                                        } else if(checked[0].name == 'k_send_to_sub_news') {
                                            self.ownerCt.get(1).setVisible(true);
                                            self.ownerCt.get(3).setVisible(false);
                                        } else {
                                            self.ownerCt.get(1).setVisible(false);
                                            self.ownerCt.get(2).setVisible(false);
                                            self.ownerCt.get(3).setVisible(false);
                                        }
                                    } else if (checked.length === 2) {
                                        self.ownerCt.get(1).setVisible(false);
                                        self.ownerCt.get(2).setVisible(false);
                                        self.ownerCt.get(3).setVisible(true);
                                    } else {
                                        self.ownerCt.get(1).setVisible(false);
                                        self.ownerCt.get(2).setVisible(false);
                                        self.ownerCt.get(3).setVisible(true);
                                    }
                                    Ext.each(checked, function(r){                                         
                                        if( r.name == 'k_send_to_main' || r.name == 'k_send_to_sub'|| r.name == 'k_send_to_sub_news' ){
                                            isValidQc = true;
                                        }
                                    });
                                }
                            }
                        },{
                            xtype: 'label',
                            text: '뉴스부조만 전송됩니다.',
                            hidden: true,
                            width: 30,
                            style: {
                                color: "red",
                            },
                            name: "sub_control_news",
                        },{
                            xtype: 'label',
                            text: 'A/B부조만 전송됩니다.',
                            hidden:true,
                            width: 30,
                            style: {
                                color: "red",
                            },
                            name: "sub_control_ab",
                        },{
                            xtype: 'label',
                            text: '중복 선택시 뉴스, A/B 부조 동시 전송',
                            hidden:true,
                            width: 30,
                            style: {
                                color: "red",
                            },
                        }]
                    }],
                    setCondition: function(type){
                        var self = this;                                  
                        self.items.each(function(item){
                            
                            if (type == 'main_tm_on' && item.name == 'k_send_select') {
                                item.setDisabled(false);
                                item.items.get(0).setVisible(true);
                                item.items.get(1).setVisible(false);
                                item.items.get(2).setVisible(false);
                                // label hidden
                                item.ownerCt.get(1).setVisible(false);
                                item.ownerCt.get(2).setVisible(false);
                                item.ownerCt.get(3).setVisible(false);
                            } else if (type == 'main_tm_off' && item.name == 'k_send_select') {
                                item.setValue('');
                                item.setDisabled(true);
                                item.eachItem(function(r,idx){                                        
                                    r.setValue(false);                                  
                                });
                                item.items.get(0).setVisible(true);
                                item.items.get(1).setVisible(false);
                                item.items.get(2).setVisible(false);

                                // label hidden
                                item.ownerCt.get(1).setVisible(false);
                                item.ownerCt.get(2).setVisible(false);
                                item.ownerCt.get(3).setVisible(false);
                            } else if (type == 'sub_tm_on' && item.name == 'k_send_select') {
                                
                                item.setDisabled(false);
                                item.items.get(0).setVisible(false);
                                item.items.get(1).setVisible(true);
                                item.items.get(2).setVisible(true);
                                
                                // label hidden
                                item.ownerCt.get(1).setVisible(false);
                                item.ownerCt.get(2).setVisible(false);
                                item.ownerCt.get(3).setVisible(true);
                            } else if (type == 'sub_tm_off' && item.name == 'k_send_select') {
                                item.setValue('');
                                item.setDisabled(true);
                                item.eachItem(function(r,idx){                                        
                                    r.setValue(false);                                  
                                });
                                item.items.get(0).setVisible(false);
                                item.items.get(1).setVisible(true);
                                item.items.get(2).setVisible(true);

                                // label hidden
                                item.ownerCt.get(1).setVisible(false);
                                item.ownerCt.get(2).setVisible(false);
                                item.ownerCt.get(3).setVisible(false);
                            }

                            if ( type == 'archive_on' && item.name == 'k_archive_select' ){
                                item.setValue('Y');
                                item.ownerCt.get(1).setVisible(false);
                                //item.setDisabled(false);
                            }
                            if ( type == 'archive_off' && item.name == 'k_archive_select' ){
                            
                                item.setValue('N');
                                item.ownerCt.get(1).setVisible(true);
                            }
                        } );
            
                    }
                };
            }
        });

        var windowWidth = Ext.getBody().getViewSize().width*(80/100);
        var windowHeight = Ext.getBody().getViewSize().height*(80/100);
        
        config = {
            title: _text('MN02530'),//File Upload
            //width: Ext.getBody().getViewSize().width*0.5,
            //height: Ext.getBody().getViewSize().height*0.5,
            //화면 비율 80% 
            width: windowWidth,
            height: windowHeight,
            modal: true,//modal해제 시, 상세보기 메타(기본메타 및 커스터마이즈드메타)와 ID겹쳐서 UI깨짐.
            closeAction:'close',
            layout: 'border',
            shadow: false,
            //style: 'position:fixed; right:0; bottom:0;',
            //style: 'position:fixed; right:'+Ext.getBody().getViewSize().width*0.25+'px; bottom:'+Ext.getBody().getViewSize().height*0.25+'px;',
            style: 'position:fixed; right:'+(Ext.getBody().getViewSize().width-windowWidth)/2+'px; bottom:'+(Ext.getBody().getViewSize().height-windowHeight)/2+'px;',
            collapsible: false,
            draggable : false,
            resizable : false,
            items: [{
                xtype: 'panel',
                region: 'west',
                split: true,
                //cls: 'dark-panel',
                width: Ext.getBody().getViewSize().width*0.125,
                border: false,
                frame: true,
                autoScroll: true,
                defaults: {
                    labelSeparator: '',
                    labelWidth: 30,
                    anchor: '95%',
                    style: {
                        //'padding-top': '5px'
                    }
                },
                layout:{
                    type: 'vbox',
                    align:'stretch'
                },
                items:[
                    thisWindow.fileUploadForm, 
                    thisWindow.fileListGrid
                ],
                buttonAlign: 'left',
                buttons: [{
                    xtype: 'button',
                    text:'<span style="position:relative;top:1px;"><i class="fa fa-plus" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00033'),//Add
                    hidden: btnAddHidden,
                    width: 50,
                    listeners: {
                        click: function(btn, e){
                            $('#'+thisWindow.fileUploadField.getFileInputId()).click();
                        }
                    }
                },{
                    xtype: 'button',
                    text:'<span style="position:relative;top:1px;"><i class="fa fa-close" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00054'),//Remove
                    width:50,
                    handler: function(b, e){
                        var hasSelection = thisWindow.fileListGrid.getSelectionModel().hasSelection();
                        if(hasSelection){
                            //MSG02522 'Are you sure to remove this file'
                            Ext.MessageBox.confirm(_text('MN00054'), _text('MSG02530'), function(btn){
                                if(btn === 'yes'){
                                    var grid_list_upload_files_store = thisWindow.fileListGrid.getStore();
                                    var sm = thisWindow.fileListGrid.getSelectionModel().getSelections();
                                    var newFileList = thisWindow.fileList;
                                    for(var i =0; i<sm.length;i++){ //>
                                        var current = sm[i];
                                        var current_file_name = current.get('name');
                                        grid_list_upload_files_store.remove(current);
                                        for(var j = 0; j<newFileList.length;j++){ //>
                                            if(current_file_name == newFileList[j].name){
                                                newFileList.splice(j,1);
                                            }
                                        }
                                        
                                        thisWindow.fileList = newFileList;
                                        
                                    }
                                }
                            });
                        }else{
                            Ext.Msg.alert(_text('MN00022'), _text('MSG00022'));//Select item(s) to delete.
                        }
                    }
                }]
            },{
                region: 'center',
                xtype: 'panel',
                layout: 'fit',
                frame:true,
                tbar:
                {
                    xtype: 'toolbar',
                    style: {
                        //backgroundColor: '#333333',
                        border: '0px'
                    },
                    items:[ {
					xtype: 'displayfield',
					width: 15
				},{
					xtype: 'radiogroup',
                    width: 360,
                    columns:  [100, 100,120,200,200,200],
                    name: 'ud_content_tab',
					items: [
                        {boxLabel: '뉴스', name: 'ud_content_tab', inputValue:'news' , checked: true},
                        {boxLabel: '제작', name: 'ud_content_tab', inputValue:'product' },
                        {boxLabel: '디지털자료', name: 'ud_content_tab', inputValue:'telecine' }
                        // ,{boxLabel: 'e영상역사관', name: 'ud_content_tab', inputValue:'ehistory' },
                        // {boxLabel: '부처영상', name: 'ud_content_tab', inputValue:'portal' },
                        // {boxLabel: '홈페이지', name: 'ud_content_tab', inputValue:'homepage' }
					],
					listeners:{
						change: function(self, checked){           
							var checkedVal = checked.getRawValue();
							if (self.beforeValue == checkedVal) return;
                
                            var tab = self.ownerCt.ownerCt;
                            tab.get(0).loadFormMetaData(tab.get(0),0);
                            self.beforeValue = checkedVal;
							//Ext.Msg.show({
							//	title: '알림',
							//	icon: Ext.Msg.INFO,
							//	msg: '입력하신 정보가 초기화 되며, 선택하신 유형으로 정보가 갱신됩니다.<br />진행하시겠습니까?',
							//	buttons: Ext.Msg.OKCANCEL,
							//	fn: function(btnID, text, opt) {
							//		if(btnID == 'ok') {                                       
							//			var tab = self.ownerCt.ownerCt;
							//			tab.get(0).loadFormMetaData(tab.get(0),0);
							//			self.beforeValue = checkedVal;
							//		} else {
							//			self.setValue(self.beforeValue);
							//		}
							//	}
							//});
						},
					}
				},'',{
					xtype: 'displayfield',
					width: 7
				},_text('MN00276'),
                thisWindow.contentTypeComboBox,
                {
					xtype: 'displayfield',
					width: 5
				},
                {
                    xtype: 'label',
                    text: '원본 콘텐츠는 2주 후 삭제됩니다.',
                    hidden: true,
                    width: 30,
                    style: {
                        color: "red",
                    },
                }
                ]
                },
                items: [thisWindow.registerForm]
            }],
            buttonAlign: 'center',
            buttons: [{
                text : '<span style="position:relative;top:1px;"><i class="fa fa-check" style="font-size:13px;"></i></span>&nbsp;'+_text('MN02197'),//Upload 등록
                scale: 'medium',
                handler: function (b, e) {
                    thisWindow.handleUpload(b, e);
                }
            },{
                text : '<span style="position:relative;top:1px;"><i class="fa fa-close" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00004'),//Cancel
                scale: 'medium',
                handler: function (b, e) {
                    thisWindow.close();
                }
            }],
            listeners:{
                beforeclose: function(self){
                    thisWindow.fileList = [];
                    return true;
                },
                show: function(self) {
                    thisWindow.el.setStyle('left', '');
                    thisWindow.el.setStyle('top', '');
                }
            }
        };

        Ariel.Nps.FileUploadWindow.superclass.constructor.call(this, config);

    },

    updateStatusText: function(statusText) {
        this.title = statusText;
    },

    // 등록 버튼 클릭 이벤트 핸들러
    handleUpload: function(b, e) {
        
        // GET ALLOW FILE FOR EACH UD_CONTENT_ID
        //SONCM make upload multiple files
        var content_type_value = this.contentTypeComboBox.getValue();
        var content_type_data = this.contentTypeComboBox.getStore().data.items;
        var content_type_allow_data;
        var bs_content_id = '';
        content_type_data.map(function(val){
            var data = val.data;
            if(data.ud_content_id == content_type_value){
                bs_content_id = data.bs_content_id;
                content_type_allow_data = data.allowed_extension.toUpperCase();
            }
        });
        var extension_allow = content_type_allow_data.split(',');
        // FILE INFORMATION
        var regist_form = this.fileUploadForm.getForm();
        
        var test_upload = this.fileListGrid.getStore().data.items;
        var upload_file =[];
        var user_id = null;
        for(var i=0; i<test_upload.length; i++){//>
            upload_file.push(test_upload[i].data.name);
        }

        if(!regist_form.isValid() || upload_file.length < 1 ) {//>
            Ext.Msg.alert( _text('MN00023'), _text('MSG02519'));//MSG02519 Please select upload file
            return;
        } 

        for(var i =0; i< upload_file.length;i++){
            upload_file[i];
            var filename_arr = upload_file[i].split('.');
            var extension_index = filename_arr.length-1;
            var file_extension = '.'+filename_arr[extension_index].toUpperCase();
            if(extension_allow.indexOf(file_extension) === -1) {
                //MN00309 Allowed File Extensions
                Ext.Msg.alert( _text('MN00023'), _text('MN00309') + ' : ' + extension_allow.join(', ') );
                return;
            }
        }
        var filename_parent = upload_file[0];
        // CHECK ALLOW FILE TYPE
        var filename = upload_file.join(':');
        var metaTab = this.registerForm;
        var length = metaTab.items.length;
        var arrMeta = [];

        <?php
        // 저장 전 작업에 대한 로직 문자열을 얻어온다.
        $beforeSaveJsLogic = '';
        if(defined('CUSTOM_ROOT') && class_exists('\ProximaCustom\core\MetadataManager')) {
            $beforeSaveJsLogic = \ProximaCustom\core\MetadataManager::getBeforeSaveJsLogic();
        }
        ?>


        for (var i = 0; i < length; ++i) {
            metaTab.setActiveTab(i);
            var form = metaTab.items.items[i].getForm();
            if(!form.isValid()) {
                Ext.Msg.alert( _text('MN00023'), _text('MSG02517'));//MSG02517 Please input mandatory filed(s)
                return;
            }
            
            // 저장전 커스텀 로직
            <?=$beforeSaveJsLogic?>

            var p = metaTab.items.items[i].getForm().getValues();
            
            if(p.k_user_id){
                user_id = p.k_user_id;
            }
            metaTab.items.items[i].getForm().items.each(function(i){
                if (i.xtype == 'checkbox' && !i.checked) {
                    i.el.dom.checked = true;
                    i.el.dom.value = '';
                }
                if(i.xtype == 'combo' || i.xtype == 'g-combo'){
                    var kval = i.id ;
                    p[i.name] = i.getValue();
                }
                if(i.xtype == 'c-tree-combo'){
                    var kval = i.id ;
                    p[i.name] = i.getValue();
                }
                if(i.xtype == 'c-tree-combo'){
                    var kval = i.id ;
                    p[i.name] = i.getValue();
                }

             
            });
            if (i == 0 && Ext.getCmp('category') != null) {
                var tn = Ext.getCmp('category').treePanel.getSelectionModel().getSelectedNode();
                p.c_category_id = tn.attributes.id;
            }
            arrMeta.push(p);
            metaTab.setActiveTab(0);
        }
        
        var newContent = new Object();
        newContent.user_id = user_id;
        newContent.flag = 'fileingest';
        newContent.metadata = arrMeta;
        newContent.regist_type = 'meta';
        newContent.original_filename = filename;
        //Return information for interface/plugin_register_web.php
 
        var msg_text = _text('MSG02532');//MSG02524 Do you want to register content?
        var msg_btn = Ext.Msg.YESNO;
        var msg_cancel_text = 'no';
        var is_group = 'N';
        //if(upload_file.length>1 && bs_content_id != '<?=MOVIE?>') {
        //    msg_text = _text('MSG02531');//MSG02523 Do you want to register by group content?
        //    msg_btn = Ext.Msg.YESNOCANCEL;
        //    msg_cancel_text = 'cancel';
        //    is_group = 'Y';
        //}

        var thisWindow = this;

        Ext.Msg.show({
            title:_text('MN00024'),
            msg: msg_text,
            buttons: msg_btn,
            animEl: 'elId',
            icon: Ext.MessageBox.QUESTION,
            fn: function(btn) {
                if(btn == msg_cancel_text){
                    return;
                }
                else if(btn == 'yes' && is_group == 'Y') {
                    newContent.is_group = 'Y';
                    newContent.filename_parent = filename_parent;
                }

                thisWindow.uploadAjax(newContent);
            }
        });
    },

    uploadAjax: function(newContent) {
        if(Ext.isEmpty(this.uploadUrl)) {
            alert('Upload url is empty!');
            return;
        }

        var thisWindow = this;
        Ext.Ajax.request({
            url: '/interface/mam_ingest/plugin_register_web.php',
            method: 'POST',
            params: {
                jsondata: Ext.encode(newContent)
            },
            success: function(response, opts) {
                var response = Ext.decode(response.responseText);
                
                var uploadItems = [];
                for (var i=0; i < response.data.length; i++) {
                    var data = response.data[i];
                    if (!data.success)
                        continue;
                    
                    uploadItems.push({
                        contentId: data.content_id,
                        channel: data.channel,
                        file: thisWindow.fileList[i]
                    });
                }

                var currentIndex = 0;
                thisWindow.uploadFile(thisWindow.uploadUrl, uploadItems, currentIndex, thisWindow,
                    // 진행중
                    thisWindow.onUploadProcess,
                    // 성공 
                    thisWindow.onUploadSuccess, 
                    // 실패
                    thisWindow.onUploadFail
                );
            },
            failure: function(response, opts){
                thisWindow.close();
            }
        });
    },

    // 업로드 실패 이벤트 핸들러
    onUploadFail: function(fileUploadWindow) {
        fileUploadWindow.close();
        var r = Ext.decode(action.response.responseText);
        Ext.Msg.alert( _text('MN00023'), r.msg);
    },

    // 업로드 성공 이벤트 핸들러
    onUploadSuccess: function(url, items, index, fileUploadWindow, newFileName) {
        // run_workflow.php 호출
        // items[index]
        var uploadItem = items[index];
        var payload = {
            content_id: uploadItem.contentId,
            channel: uploadItem.channel,
            filename: newFileName
        };

        ajax('/store/upload/run_workflow.php', 'POST', payload, 
            function(r) {
                ++index;
                if (index >= items.length) {
                    Ext.Msg.alert(_text('MN00023'), _text('MSG00104'));//등록 완료
                    fileUploadWindow.close();
                    if(!Ext.isEmpty(Ext.getCmp('tab_warp'))) {
                        Ext.getCmp('tab_warp').getActiveTab().get(0).reload();
                    }
                    return;
                }
                fileUploadWindow.uploadFile(url, items, index, fileUploadWindow,
                    // 진행중
                    fileUploadWindow.onUploadProcess,
                    // 성공 
                    fileUploadWindow.onUploadSuccess, 
                    // 실패
                    fileUploadWindow.onUploadFail
                );
            }
        );
    },

    // 업로드 진행상태 업데이트
    onUploadProcess: function(fileUploadWindow, startedAt, index, e) {
        if( e.lengthComputable ){
            var loaded = e.loaded;
            var total = e.total;

            var progress = loaded/total*100;
            var store = fileUploadWindow.fileListGrid.getStore();
            store.getAt(index).set('progress', progress);
            fileUploadWindow.fileListGrid.getView().refresh();
        }
    },

    // 파일 업로드
    uploadFile: function(uploadUrl, uploadItems, index, fileUploadWindow, cbProgress, cbComplete, cbFailed) {
        var uploadItem = uploadItems[index];
        var formData = new FormData();
        formData.append('file', uploadItem.file);
        formData.append('content_id', uploadItem.contentId);
        formData.append('channel', uploadItem.channel);
        $.ajax({
            url: uploadUrl,
            method: 'post',
            data: formData,
            processData: false,
            contentType: false,
            xhr: function(){
                var xhr = new window.XMLHttpRequest();
                var startedAt = new Date();
                xhr.upload.addEventListener( 'progress', function( e ){
                    
                    if(!Ext.isEmpty(cbProgress)) {
                        cbProgress(fileUploadWindow, startedAt, index, e);
                    }
        
                }, false );
                return xhr;
            },
            success: function(res, action) {
                var newFileName = res.data.new_filename;
                if(!Ext.isEmpty(cbComplete)) {
                    cbComplete(uploadUrl, uploadItems, index, fileUploadWindow, newFileName);
                }
            },
            failure: function(res, action) {
                if(!Ext.isEmpty(cbFailed)) {
                    cbFailed(action);
                }
             
            },
            error: function(error){
                fileUploadWindow.close();
                
                Ext.Msg.alert(error.status+' error', '콘텐츠 등록에 실패 했습니다.');
            }
        });
    }
});

