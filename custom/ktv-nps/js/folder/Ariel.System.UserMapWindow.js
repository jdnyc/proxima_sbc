(function () {
    Ext.ns("Ariel.System");
    Ariel.System.UserMapWindow = Ext.extend(Ext.Window, {
      // properties
  
      // private variables

      //대상 사용자
      initUserList: [],
      curUserList: [],
      //제외한 사용자
      delUserList: [],
      userListGrid: null,
      folderPath: null,
      folderPathNM: null,
      folderId: null,
      saveUrl:  null,
      storeUrl:  null,
      constructor: function (config) {
                  
        Ext.apply(this, {}, config || {});

        this.addEvents("userSave");
  
        this.title = "사용자 매핑";
        this.width = 500;
        this.minWidth = 500;
        this.modal = true;
        this.height = getSafeHeight(480);
        this.layout = "fit";

        //this.saveUrl =  '/api/v1/folder-mngs/{id}/users';
        this.storeUrl = '/api/v1/folder-mngs/{id}/users';

        //this.cls = "dark-window";  
        //this.toolbarCls = "dark-toolbar";
        //this.fieldsetCls = "dark-fieldset";

        this._initItems(config);
  
        Ext.apply(this.listeners, {
          show: function(self){
          }
        });
  
  
        Ariel.System.UserMapWindow.superclass.constructor.call(this);
      },
      _initItems: function (config) {
        var _this = this;

        _this.storeUrl = _this.storeUrl.replace( '{id}', _this.folderId );

        _this.userListStore =  new Ext.data.JsonStore({
            url: _this.storeUrl,
            restful: true,
            remoteSort: true,
            sortInfo: {
                field: 'user_nm',
                direction: 'ASC'
            },
            idProperty: 'user_id',
            root: 'data',
            fields: [
                'member_id',
                'user_id',
                'user_nm',
                'dept_nm'
            ],
            listeners: {
                beforeload: function(self, node, callback) {
                    //self.baseParams.action = 'grid_category_users';
                    //self.baseParams.category_id = category_id
                },
                load: function(self, records){
                    _this.curUserList = [];
                    _this.initUserList = [];
                    if(!Ext.isEmpty(records)){
                        for(var i=0; i < records.length; i++){
                            _this.curUserList.push(records[i].get('user_id'));
                            _this.initUserList.push(records[i].get('user_id'));
                        }
                    }
                }
            }
        });

        _this.onUserSelect = function(self, sels){
            Ext.each(sels, function(r){
                var idx = _this.curUserList.indexOf(r.get('user_id')) ;             
                if( idx < 0 ){
                    _this.userListStore.add(r);
                    _this.curUserList.push( r.get('user_id') );
                }
                var idx = _this.delUserList.indexOf(r.get('user_id')) ;             
                if( idx > -1 ){
                    _this.delUserList.splice(idx, 1);
                }
            });
        }
        _this.onUserDelete = function(){
            var sels = _this.userListGrid.getSelectionModel().getSelections();
            Ext.each(sels, function(r){
                var idx = _this.curUserList.indexOf(r.get('user_id')) ;
                if( idx > -1 ){
                    _this.userListGrid.getStore().remove(r);
                    _this.curUserList.splice(idx, 1);
                }
                var idx = _this.delUserList.indexOf(r.get('user_id')) ;             
                if( idx < 0 ){
                    //초기값에 있으때만 제외                    
                    var InitIdx =_this.initUserList.indexOf(r.get('user_id')) ;
                    if( InitIdx > -1 ){                        
                        _this.delUserList.push( r.get('user_id') );
                    }
                }
            });
        }

        _this.userListGrid = new Ext.grid.GridPanel({
            flex : 1,
            xtype: 'grid',
            fieldLabel:'사용자 목록',
            columnSort: false,
            reserveScrollOffset: true,
            autoScroll: true,
            emptyText: '검색된 결과가 없습니다.',
            multiSelect: true,
            loadMask: true,
            tbar: [{
                xtype:'aw-button',
                iCls: 'fa fa-plus',
                scale: 'small',
                text: '추가',
                handler: function(){
                    new Custom.UserSelectWindow({
                        width: 450,
                        height: 400,
                        cls: null,
                        listeners: {
                            'ok': _this.onUserSelect
                        }
                    }).show();

                }
            },{										
                xtype:'aw-button',
                iCls: 'fa fa-ban',
                scale: 'small',
                text: '삭제',
                handler: function(){
                  _this.onUserDelete();
                }
            }],
            store: _this.userListStore,
            columns: [
                { header : '사용자아이디', dataIndex: 'user_id' },
                { header : '사용자명', dataIndex: 'user_nm' },
                { header : '부서', dataIndex: 'dept_nm' }
            ],
            listeners: {
                viewready: function(self){
                    self.getStore().load();
                }
            }
        });

        _this.items = [{
            layout: {
                type: 'vbox',
                align: 'stretch'
            },
            items: [{
                xtype:'form',
                border:false,
                frame:true,
                labelWidth : 100,
                defaults:{
                    anchor:'95%'
                },
                items: [{
                    xtype:'hidden',
                    value: _this.folderId
                },{
                    xtype:'textfield',
                    readOnly : true,
                    value: _this.folderPath,
                    fieldLabel:'폴더명'
                },{
                    xtype:'textfield',
                    readOnly : true,
                    fieldLabel:'폴더영문명',
                    value: _this.folderPathNM
                }]
            },
            this.userListGrid
            ]
        }],
        _this.buttons = [{
            xtype:'aw-button',
            iCls: 'fa fa-check',
            scale: 'small',
            text:'저장',
            handler:function(){
                var params = {
                    curUserList: Ext.encode(_this.curUserList),
                    delUserList:  Ext.encode(_this.delUserList)
                };
                _this.onUserSave(params);
            }
        },{
            xtype:'aw-button',
            iCls: 'fa fa-ban',
            scale: 'small',
            text:'닫기',
            handler:function(){
                _this.onUserClose();
            }
        }];
      },

      fireUserSave: function (response) {
          //저장 이벤트

            this.fireEvent("userSave", this, response);
      },
      onUserSave: function( params ){
        var _this = this;

        //배열비교
        var isEquals = _this.curUserList.equals(_this.initUserList ,false);

        if( !isEquals )
        {
           
            var waitMsg = Ext.Msg.wait('처리 중입니다.', '처리중...');

            Ext.Ajax.request({
                method : 'PUT',
                url: _this.storeUrl,
                params : params,
                callback:function(option,success,response)
                {
                    waitMsg.hide();
                    var r = Ext.decode(response.responseText);
                    if(r.success){

                        _this.destroy();
                        _this.fireUserSave(r);
                        Ext.Msg.alert('알림','저장되었습니다.');
                    }
                    else{
                        Ext.Msg.alert('오류', r.msg);
                    }
                }
            });
        }
        else{
            Ext.Msg.alert('알림','변경사항이 없습니다');
        }
      },
      onUserClose: function(){
        this.close();     
      }
    });
  })();