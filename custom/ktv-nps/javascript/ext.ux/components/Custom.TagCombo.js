(function () {
    Ext.ns("Custom");

    Custom.TagCombo = Ext.extend(Ext.Container, {
        // false : 테그 필드에 value field 값을 보여줌
        useDisplayField: true,
        // 선택후 값을 보여줄지 말지에 대한 설정
        displayValueField:false,
        // 검색콤보 사용 여부
        searchCombo:true,
        
        readOnly:false,
        name:'instt',
        value: null,
        tagValue:null,
        editable: false,
        triggerAction: 'all', 
        typeAhead: true, 
        mode: 'local', 
        valueField: 'key',
        displayField: 'val',
        store: null,
        // layout:'fit',
        listeners:{
            beforerender:function(self){},
            afterrender: function(self){
                var keywordsStr = self.value;
                if(Ext.isEmpty(keywordsStr)) {
                    return;
                }
                keywordsStr = keywordsStr.split(',');
                self.setValue(keywordsStr);
            }
        },
        initComponent: function(){
            this._initialize();
            Custom.TagCombo.superclass.initComponent.call(this);
        },
        _initialize: function(){
            var combo = this._makeCombo();
            var tagField = this._makeTagField();
            var valueField = this._makeValueField();
            this.items = [
                combo,
                valueField,
                new Ext.Spacer({
                    height: 5
                }),
                tagField
            ]
        },
        getValue: function(){
            if(this.readOnly){
                return this.value;    
            }
            var tagField = this.getTagField();
            var value = tagField.getValue();
            value = this._changeValueFieldsByDisplayFields(value);
            value = value.join(',');
            return value;
        },
        setValue: function(tags){
            tags = this._changeDisplayFieldsByValueFields(tags);
            this.getTagField().setValue(tags);
        },
        
        getCombo: function(){
            return this.items.get(0);
        },
        getValueField: function(){
            return this.items.get(1);
        },
        getTagField: function(){
            return this.items.get(3);
        },
        getStore: function(){
            return this.getCombo().getStore();
        },
        addTag: function(tag, index){
            var tagField = this.getTagField();
            tag = this._changeDisplayFieldByValueField(tag);
            tagField.addTag(tag);
            
            this.getValueField().setValue(this.getValue());
        },
        _makeCombo: function(){
            var _this = this;
            var valueField = this.valueField;
            
            var searchCombo = new Custom.SearchCombo({   
                // name: _this.name,
                // value:_this.value,
                readOnly:_this.readOnly,
                editable: _this.editable,
                triggerAction: _this.triggerAction, 
                typeAhead: _this.typeAhead, 
                mode: _this.mode, 
                valueField: _this.valueField,
                displayField: _this.displayField,
                store:_this.store,
                listeners:{
                    ok:function(self,searchCombo,record){
                        if(_this.displayValueField){
                            searchCombo.setValue(null);
                        }
                   
                        if(Ext.isEmpty(record)){
                            return;
                        }

                        tag = record.get(valueField);
                        
                        _this.addTag(tag);
                    }
                }
            });

            var combo = new Ext.form.ComboBox({     
                // name: _this.name,
                // value:_this.value,
                layout:'fit',
                readOnly:_this.readOnly,
                editable: _this.editable,
                triggerAction: _this.triggerAction, 
                typeAhead: _this.typeAhead, 
                mode: _this.mode, 
                valueField: _this.valueField,
                displayField: _this.displayField,
                store:_this.store,
                listeners:{
                    select:function(self, record, index){
                        if(_this.displayValueField){
                            self.setValue(null);
                        }

                        tag = record.get(valueField);
                        _this.addTag(tag);
                    }
                }
            });

            if(_this.searchCombo){
                return searchCombo;
            }
            return combo;
        },
        _makeTagField: function(){
            var _this = this;
            var tagField = new Custom.TagField({
                height: 70,
                // theme:_this.theme,
                autoScroll: true
            });
            tagField.on('close',function(self, tag){
                _this.getValueField().setValue(_this.getValue());
            });
            tagField.on('changeOrder',function(self, fromIndex, toIndex){
                _this.getValueField().setValue(_this.getValue());
            });
            return tagField;
        },
        _makeValueField: function(){
            var _this = this;

            var valueField = new Ext.form.TextField({
                name: _this.name,
                value:_this.value,
                hidden:true
            });
            return valueField;
        },
        _changeDisplayFieldsByValueFields: function(valueFields){
            this.getStore().clearFilter();

            var _this = this;
            var displayField = this.displayField;
            var displayTags = [];
            // displayField 값으로 저장하기 위함
            Ext.each(valueFields,function(r){
                var storeRecordIndex = _this.getStore().find(_this.valueField, r);
                /* 스토어 내에 값이 없을 경우 예외처리*/
                if(storeRecordIndex != -1){
                    var record = _this.getStore().getAt(storeRecordIndex);
                    var displayValue = record.get(displayField);
                    displayTags.push(displayValue);
                }else{
                    displayTags.push(r);
                }
            });
            return displayTags;
        },
        _changeValueFieldsByDisplayFields: function(displayFields){
            this.getStore().clearFilter();

            var _this = this;
            var valueField = this.valueField;
            var displayField = this.displayField;
            var valueTags = [];
            

            Ext.each(displayFields,function(r){
                var storeRecordIndex = _this.getStore().find(displayField, r);
                /* 스토어 내에 값이 없을 경우 예외처리*/
                if(storeRecordIndex != -1){
                    var record = _this.getStore().getAt(storeRecordIndex);
                    var value = record.get(valueField);
                }else{
                    var value = r;
                }
                valueTags.push(value);
            });
            
            return valueTags;
        },
        _changeDisplayFieldByValueField: function(valueField){
            this.getStore().clearFilter();

            var displayField = this.displayField;
            var storeRecordIndex = this.getStore().find(this.valueField, valueField);          
            /* 스토어 내에 값이 없을 경우 예외처리*/
            /* 리스트 목록 내에서 가져오는거라 없을 경우는 없지만 혹시 몰라서..*/
            if(storeRecordIndex != -1){
                var record = this.getStore().getAt(storeRecordIndex);
                var displayValue = record.get(displayField);
            }else{
                var displayValue = record.get(valueField);
            }
            
            return displayValue;
        }
    });

    Ext.reg("c-tag-combo", Custom.TagCombo);
})();