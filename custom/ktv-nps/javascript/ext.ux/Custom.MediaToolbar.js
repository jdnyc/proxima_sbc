(function (){
    Ext.ns("Custom");
    Custom.MediaToolbar = Ext.extend(Ext.Toolbar, {
        // 랜더링 전에 가려줄지 말지 true,false
        visible:false,
        initComponent: function(){
            this._initialize();
            Custom.MediaToolbar.superclass.initComponent.call(this);
        },
        _initialize: function(){
            this.listeners = {
                beforerender: function(self){
                    self._visible();
                },
                afterrender: function(self){
                },
                beforeshow: function(self){
                },
                beforehide: function(self){
                    // tbar를 쓰는 콤포넌트에 syncSize() 를 사용해서 구성요소의 크기를 다시 계산 해줘야 한다.
                    self._visibleControl();
                }
            }
        },
        //*
        // make method
        //*
        _makeTbText: function(text){
            var tbText = new Ext.Toolbar.TextItem({
                xtype: 'tbtext', 
                text: text,
                style:{
                    color:'white',
                    marginLeft: '10px'
                }
            });
            return tbText;
        },
        _makeCustomTbText: function(config){
            var isPermission = this._permissionCheck(config.permission_code);
            if(!isPermission){
                return;
            };
            var tbText = new Ext.Toolbar.TextItem({
                xtype: 'tbtext', 
                text: config.text,
                
                style:{
                    color:'white',
                    marginLeft: '10px'
                }
            });
            return tbText;
        },
        _makeTextField: function(name){
            var textField = new Ext.form.TextField({
                name:name,
                itemId:name,
                width:150,
                style: {
                    marginLeft: '5px',
                    backgroundColor: '#1f1f1f ',
                    color:'#ffffff',
                    border : '1px solid #000000'
                },
            });
            return textField;    
        },
        _makeNumberField: function(name){
            var textField = new Ext.form.NumberField({
                name:name,
                itemId:name,
                width:70,
                style: {
                    marginLeft: '5px',
                    backgroundColor: '#1f1f1f ',
                    color:'#ffffff',
                    border : '1px solid #000000'
                },
            });
            return textField;    
        },
        _makeCodeCombo: function(name,code){
            var _this = this;
            var codeCombo = new Ext.form.ComboBox({
                name:name,
                hiddenName:name,
                itemId:name,
                width:125,
                cls: 'black_combobox_trigger',
                style: {
                    marginLeft: '5px',
                    backgroundColor: '#1f1f1f ',
                    color:'white',
                    border : '1px solid #000000'
                },
                triggerConfig: {
                    src:Ext.BLANK_IMAGE_URL,
                    tag: "img",
                    cls:'x-form-trigger x-form-arrow-trigger x-form-trigger-over x-form-trigger-black'
                },
                allowBlank: false,
                editable: false,
                mode: "local",
                displayField: "code_itm_nm",
                // valueField: "code_itm_code",
                valueField: "code_itm_nm",
                hiddenValue: "code_itm_nm",
                typeAhead: true,
                triggerAction: "all",
                listeners:{
                    beforerender:function(self){
                        self.store = _this._jsonStoreByCode(code,self);
                    },
                    afterrender:function(self){
                        self.resizeEl.setWidth(125);
                        self.getStore().load({
                            params:{
                                is_code:1
                            }
                        });
                    }
                }
            }); 
            return codeCombo;
        },
        _jsonStoreByCode: function(code,self){
            var jsonStore = new Ext.data.JsonStore({
                restful: true,
                proxy: new Ext.data.HttpProxy({
                    method: "GET",
                    url: '/api/v1/open/data-dic-code-sets/' + code + '/code-items',
                    type: "rest"
                }),
                root: "data",
                fields: [
                    { name: "code_itm_code", mapping: "code_itm_code" },
                    { name: "code_itm_nm", mapping: "code_itm_nm" },
                    { name: "id", mapping: "id" }
                ],
                listeners: {
                    load: function (store, r) {
                        var comboRecord = Ext.data.Record.create([
                            { name: "code_itm_code" },
                            { name: "code_itm_nm" }
                        ]);
                        var allComboMenu = {
                            code_itm_code: "All",
                            code_itm_nm: "전체"
                        };
                        var addComboMenu = new comboRecord(allComboMenu);
                        store.insert(0, addComboMenu);
                        var firstValueCheck = self.valueField;
        
                        var firstValue;
                        if (Ext.isEmpty(firstValueCheck)) {
                            firstValue = store.data.items[0].data[firstValueCheck];
                        } else {
                            // firstValue = 'All';
                            firstValue = '전체';
                        }
        
                        self.setValue(firstValue);
        
                    },
                    exception: function (self, type, action, opts, response, args) {
                        try {
                            var r = Ext.decode(response.responseText, true);
        
                            if (!r.success) {
                                Ext.Msg.alert(_text("MN00023"), r.msg);
                            }
                        } catch (e) {
                            Ext.Msg.alert(_text("MN00023"), r.msg);
                        }
                    }
                }
        
            });
            return jsonStore;
        },
        _makeYnCombo: function(name){
            var ynCombo = new Ext.form.ComboBox({
                width:90,
                cls: 'black_combobox_trigger',
                name:name,
                itemId:name,
                style: {
                    marginLeft: '5px',
                    backgroundColor: '#1f1f1f ',
                    color:'white',
                    border : '1px solid #000000'
                },
                triggerConfig: {
                    src:Ext.BLANK_IMAGE_URL,
                    tag: "img",
                    cls:'x-form-trigger x-form-arrow-trigger x-form-trigger-over x-form-trigger-black'
                },
                store: new Ext.data.ArrayStore({
                    fields: ['value','name'],
                    data: [
                        ['All','전체'],
                        ['Y','Y'],
                        ['N','N']
                    ]
                }),
                valueField: 'value',
                displayField: 'name',
                value: 'All',
                mode: 'local',
                typeAhead: true,
                triggerAction: 'all',
                forceSelection: true,
                editable: false,
                listeners:{
                    afterrender: function(self){
                        self.resizeEl.setWidth(90);
                    }
                } 
            });
            return ynCombo;
        },
        _makeCustomCombo: function(config){
            var isPermission = this._permissionCheck(config.permission_code);
            if(!isPermission){
                return;
            };

            if(!Ext.isEmpty(config.defaultValue)){
                config.value = config.defaultValue;
            };
            var customCombo = new Ext.form.ComboBox({
                width:90,
                cls: 'black_combobox_trigger',
                style: {
                    marginLeft: '5px',
                    backgroundColor: '#1f1f1f ',
                    color:'white',
                    border : '1px solid #000000'
                },
                triggerConfig: {
                    src:Ext.BLANK_IMAGE_URL,
                    tag: "img",
                    cls:'x-form-trigger x-form-arrow-trigger x-form-trigger-over x-form-trigger-black'
                },
                store: new Ext.data.ArrayStore({
                    fields: ['value','name'],
                    data: [
                        ['All','전체'],
                        ['Y','Y'],
                        ['N','N']
                    ]
                }),
                valueField: 'value',
                displayField: 'name',
                value: 'All',
                mode: 'local',
                typeAhead: true,
                triggerAction: 'all',
                forceSelection: true,
                editable: false,
                listeners:{
                    afterrender: function(self){
                        self.resizeEl.setWidth(90);
                    }
                } 
            });
           
            return Ext.apply(customCombo,config);
        },
        _makeButton: function(text,iconClass,handler){
            var button = new Ext.Button({
                xtype: 'button',
                cls : 'proxima_btn_customize proxima_btn_customize_new',
                text: '<span style="position:relative;" title="'+text+'"><i class="'+iconClass+'" style="font-size:13px;"></i></span>',//초기화
                style: {
                    marginLeft: '5px'
                },
                handler:handler
            });
            return button;
        },
        _makeDateField: function(name,mode){
            var dateField = new Ext.form.DateField({
                name:name,
                itemId:name,
                dateMode:mode,
                editable:false,
                width:105,
                style: {
                    marginLeft: '10px',
                    backgroundColor: '#1f1f1f ',
                    color:'white',
                    border : '1px solid #000000',
                    // backgroundImage:'url(/lib/extjs/resources/images/gray/form/date-trigger-black.gif)'
                },
                // triggerConfig: {
                    // src:Ext.BLANK_IMAGE_URL,
                    // tag: "img",
                    // backgroundImage: 'url(/lib/extjs/resources/images/gray/form/trigger-black.png)',
                    // cls:'x-form-trigger x-form-date-trigger x-form-trigger-over'
                // },
                format:'Y-m-d',
                altFormats: 'Ymd|ymd|Y-m-d|y-m-d|Y/m/d|Y/m/d',
                listeners:{
                    render: function(self){
                        self.trigger.setStyle('backgroundImage','url(/lib/extjs/resources/images/gray/form/date-trigger-black.gif)');

                        var d = new Date();
                        self.setMaxValue(d);
                        self.setValue(d);
                    }
                }
            });
            return dateField;
        },
        //*
        //config control method
        //*
        _visible: function(){
            var visibleCheck = this.visible;
            if(visibleCheck){
                // this.setVisible(false);
                this.setVisible(false);
            }
        },
        //*
        // custom method
        //*
        _setVisible: function(visible){
            return this.rendered && this.getVisibilityEl().isVisible();
        },
        _addItems: function(items){
            var _this = this;
            Ext.each(items, function(item,index){
                if(Ext.isEmpty(item)){
                    return true;
                }
                _this.add(item);
            });
            _this.doLayout();
        },
        _getField: function(itemId){
            return this.getComponent(itemId);
        },
        _getValueFields: function(){
            var _this = this;
            var valueFields = [];
            this.items.each(function(item){
                var itemId = item.itemId;
                if(!Ext.isEmpty(itemId)){
                    var findField = _this._getField(itemId);
                    if(typeof findField.getValue == 'function'){
                        valueFields.push(findField);
                    };
                };
            });
            return valueFields;
        },
        _visibleControl: function(){
            var fields = this._getValueFields();
            Ext.each(fields, function(field){
                if(!Ext.isEmpty(field.getValue())){

                    if(field.getXType() == 'combo'){
                        if(Ext.isEmpty(field.defaultValue)){
                            // 일단 코드값 대신 한글로 바꿔놓음
                            // field.setValue('All');
                            field.setValue('전체');
                        }else{
                            field.setValue(field.defaultValue);
                        }
                    };

                    if(field.getXType() == 'textfield' || field.getXType() == 'numberfield'){
                        field.setValue(null);
                    }
                };
            });
        },
        _removeItem: function(itemId){
            if(!Ext.isEmpty(this._getField(itemId))){
                this.remove(this._getField(itemId));
            }
        },
        _permissionCheck: function(permission_code){
            if(Ext.isEmpty(permission_code)){
                return true;
            }
            var isPermission = false;
            if(!Ext.isEmpty(permission_code)){
                var permissions = this.permissions;
                Ext.each(permission_code, function(code){
                    if(permissions.indexOf(code) != -1){
                        isPermission = true;
                        return isPermission;
                    };
                });
                
                return isPermission;
            };
            return isPermission;
        }

    });
    Ext.reg('c-media-toolbar', Custom.MediaToolbar);
})();