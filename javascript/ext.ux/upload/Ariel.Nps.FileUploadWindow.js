
Ariel.Nps.FileUploadWindow = Ext.extend(Ext.Window, {

    constructor: function(config) {

        var thisWindow = this;

        Ext.apply(this, config || {});

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
                    flag: 'fileingest'
                },
                fields: [
                    'ud_content_title',
                    'ud_content_id',
                    'ud_content_code',
                    'bs_content_code',
                    'allowed_extension'
                ]
            }),
            listeners:{
                afterrender: function(self){
                    var ud_content_id = Ext.getCmp('tab_warp').getActiveTab().ud_content_id;
                    self.getStore().load({
                        callback:function(r,o,s){
                            if( s && r[0] ){
                                self.setValue(ud_content_id);
                                self.beforeValue = ud_content_id;
                                var tab = self.ownerCt.ownerCt;
                                tab.get(0).loadFormMetaData(tab.get(0), 0);
                            }
                        }
                    });
                },
                select: function(self, record, index ){
                    var selVal = record.get('ud_content_id');
                    if(self.beforeValue == selVal) return;
                    Ext.Msg.show({
                        title: _text('MN00023'),//Information
                        icon: Ext.Msg.INFO,
                        //Current metadata will be cleared. And will be refresh by selected content type metadata.
                        //Will you proceed?
                        msg: _text('MSG02066')+'<br />'+_text('MSG02067'),
                        buttons: Ext.Msg.OKCANCEL,
                        fn: function(btnID, text, opt) {
                            if(btnID == 'ok') {
                                var tab = self.ownerCt.ownerCt;
                                tab.get(0).loadFormMetaData(tab.get(0));
                                self.beforeValue = selVal;
                            } else {
                                self.setValue(self.beforeValue);
                            }
                        }
                    });
                }
            }
        });

        // 파일리스트 그리드
        this.fileListGrid = new Ext.grid.GridPanel({
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
                if (files) {
                    var grid_list_uploadFiless_store = thisWindow.fileListGrid.getStore();
                    for (var i = 0; i < files.length; i++){
                        names.push(files[i].name);
                        values = names.join(':');
                        var tt = grid_list_uploadFiless_store.recordType;
                        var data = new tt({
                            'name': files[i].name,
                            'size': files[i].size
                        });
                        grid_list_uploadFiless_store.data.add(data);
                    } 
                }
                var newFiles = [];
                for (var i = 0; i < files.length; i++) {
                    newFiles.push(files[i]);
                }
                
                if(Ext.isEmpty(thisWindow.fileList)){
                    thisWindow.fileList = newFiles;
                }else{
                    thisWindow.fileList = thisWindow.fileList.concat(newFiles);
                }
                thisWindow.fileListGrid.getView().refresh();
                this.setValue(values);
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
                    self.setFiles(thisWindow.option.files);
                }
            }
        });

        // 파일 업로드 폼
        this.fileUploadForm = new Ext.form.FormPanel({
            split: true,
            hidden:true,
            fileUpload: true,
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
            defaults:{autoScroll: true},
            frame:true,
            isFirst: true,
            items:[],
            loadFormMetaData: function(self, caseView ){
                var tbar = self.ownerCt.getTopToolbar();
                var ud_content_tab = 'program';
                var ud_content_id = tbar.items.get(2).getValue();
                params = {};
                params.ud_content_tab = ud_content_tab;
                params.ud_content_id = ud_content_id;
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
                        if (success) {
                            try {
                                var r = Ext.decode(response.responseText);
                                self.removeAll();
                                self.add(r);
                                self.doLayout();
                                self.activate(0);

                                if(thisWindow.option.is_drag == 'Y') {
                                    // set default value for user_meta form
                                    var v_title = registerForm.items.items[0].getForm().findField('k_title');
                                    var filename = thisWindow.option.files[0].name;
                                    var filename_arr = filename.split('.');
                                    v_title.setValue(filename_arr[0]);
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
                // params.user_id = thisWindow.option.user_id;
                // params.lang = thisWindow.option.user_lang;

                Ext.Ajax.request({
                    url: '/interface/app/plugin/registForm/get_metadata.php',
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
            }
        });

        config = {
            title: _text('MN02530'),//File Upload
            width: Ext.getBody().getViewSize().width*0.7,
            height: Ext.getBody().getViewSize().height*0.5,
            modal: true,//modal해제 시, 상세보기 메타(기본메타 및 커스터마이즈드메타)와 ID겹쳐서 UI깨짐.
            closeAction:'close',
            layout: 'border',
            shadow: false,
            style: 'position:fixed; right:0; bottom:0;',
            collapsible: true,
            items: [            
            {
                xtype: 'panel',
                region: 'west',
                split: true,
                width: Ext.getBody().getViewSize().width*0.3,
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
                                    var grid_list_uploadFiless_store = thisWindow.fileListGrid.getStore();
                                    var sm = thisWindow.fileListGrid.getSelectionModel().getSelections();
                                    var newFileList = thisWindow.fileList;
                                    for(var i =0; i<sm.length;i++){
                                        var current = sm[i];
                                        var current_file_name = current.get('name');
                                        grid_list_uploadFiless_store.remove(current);
                                        for(var j = 0; j<newFileList.length;j++){
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
                tbar:[{
                    xtype: 'displayfield',
                    width: 7
                },_text('MN00276'),
                thisWindow.contentTypeComboBox
                ],
                items: [thisWindow.registerForm]
            }],
            buttonAlign: 'center',
            buttons: [{
                text : '<span style="position:relative;top:1px;"><i class="fa fa-check" style="font-size:13px;"></i></span>&nbsp;'+_text('MN02532'),//Upload
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
        var udContentId = this.contentTypeComboBox.getValue();
        var udContentRecord = this.contentTypeComboBox.findRecord('ud_content_id', udContentId);
        
        var allowedExtensions = udContentRecord.get('allowed_extension').toUpperCase().split(',');

        var uploadFiles = [];
        var fileListStore = this.fileListGrid.getStore();
        this.fileListGrid.getStore().each(function(record, idx) {
            uploadFiles.push(fileListStore.getAt(idx).get('name'));
        });

        var registForm = this.fileUploadForm.getForm();
        if(!registForm.isValid() || uploadFiles.length < 1 ) {
            Ext.Msg.alert( _text('MN00023'), _text('MSG02519'));//MSG02519 Please select upload file
            return;
        } 

        for(var i =0; i< uploadFiles.length;i++){
            uploadFiles[i];
            var filename_arr = uploadFiles[i].split('.');
            var extension_index = filename_arr.length-1;
            var file_extension = '.'+filename_arr[extension_index].toUpperCase();
            if(allowedExtensions.indexOf(file_extension) === -1) {
                //MN00309 Allowed File Extensions
                Ext.Msg.alert( _text('MN00023'), _text('MN00309') + ' : ' + allowedExtensions.join(', ') );
                return;
            }
        }
        var filename_parent = uploadFiles[0];
        // CHECK ALLOW FILE TYPE
        var filename = uploadFiles.join(':');
        var metaTab = this.registerForm;
        var length = metaTab.items.length;
        var arrMeta = [];
        var user_id = null;
        for (var i = 0; i < length; ++i) {
            metaTab.setActiveTab(i);
            var form = metaTab.items.items[i].getForm();
            if(!form.isValid()) {
                Ext.Msg.alert( _text('MN00023'), _text('MSG02517'));//MSG02517 Please input mandatory filed(s)
                return;
            }
            var p = metaTab.items.items[i].getForm().getValues();
            if( p.k_user_id ){
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
        if(uploadFiles.length>1 && udContentRecord.get('bs_content_code') == 'IMAGE') {
            msg_text = _text('MSG02531');//MSG02523 Do you want to register by group content?
            msg_btn = Ext.Msg.YESNOCANCEL;
            msg_cancel_text = 'cancel';
            is_group = 'Y';
        }

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
                    Ext.Msg.alert('알림', '업로드 완료');
                    fileUploadWindow.close();
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
        
            }
        });
    }
});
