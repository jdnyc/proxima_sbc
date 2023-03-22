(function (){
    Ext.ns("Custom");
    Custom.SearchCombo = Ext.extend(Ext.form.CompositeField, {
        initComponent: function(){
            this._initialize();
            Custom.SearchCombo.superclass.initComponent.call(this);
        },
        _initialize: function(){
            var _this = this;

            this.items = [
                _this.makeSearchField(),
                _this.makeCombo()
            ];

            this.listeners={
                afterrender:function(self){
      
                }
            }

        },
        makeSearchField: function(){
            var _this = this;
            var searchField = new Ext.form.TextField({
                flex:1,
                enableKeyEvents: true,
                emptyText: '검색어를 입력해주세요',
                listeners:{
                    keyup: function(searchField,e){
                        // // 일반 키들이 입력되면 검색을 한다.
                        // if (!e.isSpecialKey()) {
                        //     var searchValue = searchField.getValue();
                        //     var searchValueLength = searchValue.toString().length;          
                        //     _this.filterComboList();
                        // }
                        // // 글자가 지워지면서 다시 검색
                        if (e.getKey() == e.BACKSPACE) {
                            _this.filterComboList();
                        }

                        // // 데이터가 있고 리스트가 보일때 방향키를 누를때 선택해준다.
                        if (e.getKey() == e.UP) {
                            _this.comboListArrowSelect('up');
                        }
                        if (e.getKey() == e.DOWN) {
                            _this.comboListArrowSelect('down');
                        }

                        // // ENTER key event
                        if(e.getKey() == e.ENTER){
                            _this.filterComboList();
                            _this.EnterKeyLogic();
                            _this.fireEvent('ok',_this,_this.getCombo(),_this.getSelecteRecord());
                        }
                        
                    },
                    keydown: function(searchField,e){
                        var searchValue = searchField.getValue();
                        var searchValueLength = searchValue.toString().length;
      
            
                        searchField.keydownLength = searchValueLength;
                        var searchValue = searchField.getValue();

                        
                    },
                    afterrender: function(searchField){
                        searchField.getEl().on('click', function(event, el) {
                            _this.filterComboList();
                        });
                    }
                }
            });
            return searchField;
        },
        makeCombo: function(name){
            var _this = this;

            var combo = new Ext.form.ComboBox({
                flex:(!Ext.isEmpty(_this.flex)) ? _this.flex : 2,
                store: _this.store,
                hiddenName: _this.hiddenName,
                valueField: _this.valueField,
                displayField: _this.displayField,
                mode: (!Ext.isEmpty(_this.mode)) ? _this.mode : 'local',
                triggerAction: (!Ext.isEmpty(_this.triggerAction)) ? _this.triggerAction : 'all',
                editable: (!Ext.isEmpty(_this.editable)) ? _this.editable : false,
                autoSelect:false,
                notUseField: (!Ext.isEmpty(_this.notUseField)) ? _this.notUseField : 'use_yn',
                notUseValue: (!Ext.isEmpty(_this.notUseValue)) ? _this.notUseValue : 'N',
                listeners:{
                    beforerender: function(self){
                        
                    },
                    beforequery: function(query){
                        
                        var combo = this;

                        var isNotUseField = _this.isStoreField(combo.notUseField);
                        
                        var view = query.combo.view;
                        var customView = new Ext.XTemplate(
                            '<tpl for=".">',
                                '<tpl if="this.useCheck('+combo.notUseField+')">',
                                    '<div class="x-combo-list-item">',
                                        '{'+combo.displayField+'}',
                                    '</div>',
                                '</tpl>',  
                                '<tpl if="!this.useCheck('+combo.notUseField+')">',
                                    '<div class="x-combo-list-item" style="display: none;">',
                                    '</div>',
                                '</tpl>',                           
                            '</tpl>',
                            {
                                useCheck: function(useYn){
                                    if(useYn == combo.notUseValue){
                                        return false;
                                    }
                                    return true;
                                }
                            }
                        );
                        if(isNotUseField){
                            view.tpl = customView;
                            view.refresh();
                        }
                    },
                    select: function(self, record, index){
                        _this.fireEvent('ok',_this,self,record);
                    }
                }
            });
            return combo;
        },
        filterComboList: function(){
            var store = this.getStore();
            var combo = this.getCombo();
            var comboValueField = combo.displayField;
            var keyword = this.getSearchField().getValue();
            var _this = this;
            var isNotUseField = _this.isStoreField(combo.notUseField);
            store.filterBy(function(record, id){
                if(record.data[comboValueField].toLowerCase().indexOf(keyword.toLowerCase()) != -1){
                    if(isNotUseField){
                        if(record.get(combo.notUseField) == combo.notUseValue){
                            return false;
                        };
                    }
                    return true;
                };
                return false;
            });

            // Ext.defer(function(){
                _this.comboExpand();
                _this.getSearchField().focus();
                // this.getSearchField().getEl().dom.focus();
            // },20000);
            
  
        },
        comboListArrowSelect: function(arrowDirection){
            var selecteRecord = this.getSelecteRecord();
            
            // filterRecordList 가 없으면 실행 안함
            var filterRecordListCount = this.getStore().getCount()
            if(filterRecordListCount == 0){
                return false;
            };

            if(Ext.isEmpty(selecteRecord)){
                this.getCombo().select(0);
            }else{
                var nowSelecteIndex = this.getStore().indexOf(selecteRecord);

                var nextSelecteIndex;
                switch(arrowDirection){
                    case 'up':
                        nextSelecteIndex = nowSelecteIndex - 1;
                        // 맨 위로 갔을때 마지막으로 이동
                        if(nextSelecteIndex == -1){
                            var lastRecordIndex = filterRecordListCount - 1;
                            nextSelecteIndex = lastRecordIndex;
                        }
                    break;
                    case 'down':
                        nextSelecteIndex = nowSelecteIndex + 1;
                        // 마지막 까지 갔을떄 0번째로 이동
                        if(nextSelecteIndex == filterRecordListCount){
                            nextSelecteIndex = 0;
                        }
                    break;
                }

                this.getCombo().select(nextSelecteIndex)
            }
        },

        getCombo: function(){
            return this.items.get(1);
        },
        getSearchField: function(){
            return this.items.get(0);
        },
        getStore: function(){
            return this.getCombo().getStore();
        },
        getView: function(){
            return this.getCombo().view;
        },
        getSelecteRecord: function(){
            var view = this.getView();
            return view.getSelectedRecords()[0];
        },

        comboExpand: function(){
            var combo = this.getCombo();
            if(!combo.isExpanded()){
                combo.onTriggerClick();
            }else{
                combo.onTriggerClick();
                combo.onTriggerClick();
            };
        },
        EnterKeyLogic: function(){
            var filterRecordListCount = this.getStore().getCount();

            // 0개 조회에선 처리할게 없다.
            if(filterRecordListCount == 0){
                return false;
            };

            // 1개 일때는 바로 index->0 set
            if(filterRecordListCount == 1){
                this.getCombo().select(0);
                this.getCombo().onViewClick();
            }else{
                // 한개 이상일때
                if(Ext.isEmpty(this.getSelecteRecord())){
                    // 선택된것이 없으면 할게 없다.
                    return false;
                }
                
                this.getCombo().onViewClick();
            }
        },
        isStoreField: function(name){
            var fields = this.getStore().fields.items;
            var isCheck = false;
 
            Ext.each(fields,function(field){
                if(field.name == name){
                    isCheck = true;
                }
            });
            return isCheck;
        }
    });
    Ext.reg("c-search-combo", Custom.SearchCombo);
})();