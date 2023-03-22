<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
session_start();
fn_checkAuthPermission($_SESSION);

$user_id = $_SESSION['user']['user_id'];
$category = $db->queryRow("select * from bc_category where category_id =0 order by show_order");
$category_grant_array = array(
    'read' => 0,
    'add' => 0,
    'edit' => 0,
    'del' => 0
);

$root_category = "id : '".$category['category_id']."',text : ".'"'.$category['category_title'].'"'.", singleClickExpand : true, read : ".$category_grant_array['read'].", add : ".$category_grant_array['add'].", edit : ".$category_grant_array['edit'].", del : ".$category_grant_array['del']."";
?>

(function(){
    Ext.ns('Ariel.config');

    function mappingGrant(value)
    {
        if(value == 0)
        {
            return _text('MN02035');//'권한 없음'
        }
        else if( value == 1 )
        {
            return _text('MN00035');//'읽기';
        }
        else if( value == 2 )
        {
            return _text('MN00035')+' / '+_text('MN00041')+' / '+' / '+_text('MN00043')+' / '+_text('MN02036');//'읽기 / 생성 / 수정 / 이동';
        }
        else if( value == 3 )
        {
            return _text('MN00035')+' / '+_text('MN00041')+' / '+' / '+_text('MN00043')+' / '+_text('MN02036')+' / '+_text('MN00034');//'읽기 / 생성 / 수정 / 이동 / 삭제';
        }
        else if( value == 4 )
        {
            return _text('MN02037');//'숨김';
        }

        return value;
    }

    Ariel.config.Category_Grant = Ext.extend(Ext.Panel, {
        //title: '카테고리 권한 관리',
        title: _text('MN02039'),
        layout: 'fit',
        frame: true,
        hidden: true,
        initComponent: function(config){
            Ext.apply(this, config || {});

            var that = this;

            this.request = function(title, params, grid){
                Ext.Msg.show({
                    icon: Ext.Msg.QUESTION,
                    //title: '확인',
                    title: _text('MN00024'),
                    msg: title+' : '+_text('MSG02039'),//title+' : '+이 작업을 진행하시겠습니까?
                    buttons: Ext.Msg.OKCANCEL,
                    fn: function(btnId, text, opts){
                        if(btnId == 'cancel') return;

                        var w = Ext.Msg.wait(_text('MN02045')+'...');//'요청중...'
                        Ext.Ajax.request({
                            url: '/store/category_grant_set.php',
                            params: params,
                            callback: function(opts, success, response){
                                w.hide();
                                if(success){
                                    try{
                                        var r = Ext.decode(response.responseText);
                                        if(!r.success){
                                            Ext.Msg.alert( _text('MN00023'), r.msg);//알림
                                            return;
                                        }

                                        that.get(0).getForm().reset();
                                        grid.getSelectionModel().clearSelections();
                                        grid.getStore().reload();
                                    }
                                    catch (e)
                                    {
                                        Ext.Msg.alert(e['name'], e['message']);
                                    }
                                }
                                else
                                {
                                    Ext.Msg.alert( _text('MN01098'), response.statusText);//'서버 오류'
                                }
                            }
                        });
                    }
                });
            };

            this.formCheck = function(){
                var category = that.get(0).get(1).get(2).get(0);
                var ud_content_form = that.get(0).get(1).get(0).get(0);
                var member_group_form = that.get(0).get(1).get(1).get(0);
                var group_grant_form = that.get(0).get(1).get(3).get(0);

                if( Ext.isEmpty(ud_content_form.getValue()) )
                {
                    //Ext.Msg.alert('알림','사용자정의콘텐츠를 선택해주세요');
                    Ext.Msg.alert( _text('MN00023'), _text('MSG02005'));
                    return false;
                }
                else if( Ext.isEmpty(member_group_form.getValue()) )
                {
                    //Ext.Msg.alert('알림','사용자 그룹을 선택해주세요');
                    Ext.Msg.alert( _text('MN00023'), _text('MSG02006'));
                    return false;
                }
                else if( category.getSelectionModel().isSelected() )
                {
                    //Ext.Msg.alert('알림','카테고리를 선택해주세요');
                    Ext.Msg.alert( _text('MN00023'), _text('MSG00122'));
                    return false;
                }
                else if( Ext.isEmpty(group_grant_form.getValue()) )
                {
                    //Ext.Msg.alert('알림','권한을 선택해주세요');
                    Ext.Msg.alert( _text('MN00023'), _text('MSG02007'));
                    return false;
                }

                return true;
            }

            this.items = [{
                xtype: 'form',
                autoScroll: true,
                layout: 'border',
                bodyPadding: 10,
                frame: false,
                defaults: {
                    split: true
                },
                url: '/store/category_grant_set.php',
                buttonAlign: 'center',
                buttons: [{
                    //icon: '/led-icons/accept.png',
                    //text: '저장',
                    text: _text('MN00046'),
                    scale: 'medium',
                    handler: function() {
                        var formvalues = that.get(0).getForm().getValues();
                        var category = that.get(0).get(1).get(2).get(0);
                        var ud_content_form = that.get(0).get(1).get(0).get(0);
                        var member_group_form = that.get(0).get(1).get(1).get(0);
                        var group_grant_form = that.get(0).get(1).get(3).get(0);
                        var grid = that.get(0).get(0).get(0);

                        if( !that.formCheck() ) return;

                        var category_id = category.getSelectionModel().getSelectedNode().id;
                        var category_full_path = category.getSelectionModel().getSelectedNode().getPath();

                        formvalues.category_id = category_id;
                        formvalues.category_full_path = category_full_path;

                        formvalues.action = 'add';
                        formvalues.grant_type = 'category_grant';

                        that.request( _text('MN00046'), formvalues, grid );//'저장'

                    }
                },{
                    //icon: '/led-icons/delete.png',
                    //text: '삭제',
                    text: _text('MN00034'),
                    scale: 'medium',
                    handler: function() {

                        var grid = that.get(0).get(0).get(0);

                        if( Ext.isEmpty(grid.getSelectionModel().getSelected()) )
                        {
                            //Ext.Msg.alert('알림','먼저 대상을 선택 해 주시기 바랍니다.');
                            Ext.Msg.alert( _text('MN00023'), _text('MSG01005'));
                            return;
                        }

                        var selections = grid.getSelectionModel().getSelections();
                        var list = new Array();
                        var params = {};
                        Ext.each(selections, function(i){
                            list.push(i.data);
                        });

                        params.action = 'delete';
                        params.grant_type = 'category_grant';
                        params.list =  Ext.encode(list);
                        that.request( _text('MN00023'), params, grid );//'삭제'
                    }
                }],
                items: [{
                    xtype: 'panel',
                    region: 'north',
                    height: 300,
                    layout: 'fit',
                    items: [{
                        xtype: 'grid',
                        loadMask: true,
                        store: new Ext.data.GroupingStore({
                            reader: new Ext.data.ArrayReader({}, [
                                {name: 'ud_content_title'},
                                {name: 'member_group_name'},
                                {name: 'category_title'},
                                {name: 'group_grant'},
                                {name: 'ud_content_id' },
                                {name: 'member_group_id'},
                                {name: 'category_id' },
                                {name: 'category_full_path' }
                            ]),
                            autoLoad: true,
                            url: '/store/category_grant_store.php',
                            baseParams: {
                                grant_type: 'category_grant'
                            },
                            sortInfo: { field: 'member_group_name', direction: "ASC" },
                            groupField:'ud_content_title'
                        }),
                        border: false,
                        autoShow: true,
                        columns: [
                            //{ header: "사용자정의콘텐츠", sortable: true, dataIndex: 'ud_content_title' },
                            //{ header: "사용자 그룹",  sortable: true, dataIndex: 'member_group_name' },
                            //{ header: "카테고리", sortable: true, dataIndex: 'category_title' },
                            //{ header: "카테고리 권한", sortable: true, dataIndex: 'group_grant' , renderer: mappingGrant }
                            { header: _text('MN02046'), sortable: true, dataIndex: 'ud_content_title' },
                            { header: _text('MN02047'),  sortable: true, dataIndex: 'member_group_name' },
                            { header: _text('MN00387'), sortable: true, dataIndex: 'category_title' },
                            { header: _text('MN02048'), sortable: true, dataIndex: 'group_grant' , renderer: mappingGrant }
                        ],
                        sm: new Ext.grid.RowSelectionModel({
                            //singleSelect: true
                        }),

                        view: new Ext.grid.GroupingView({
                            forceFit: true,
                            groupTextTpl: '{text} ({[values.rs.length]} {[values.rs.length > 1 ? "Items" : "Item"]})'
                        }),
                        listeners: {
                            rowclick: function(self, idx, e){
                                var select = self.getSelectionModel().getSelected();
                                var form = that.get(0).getForm();

                                if( Ext.isEmpty(select) ) return;

                                var category = that.get(0).get(1).get(2).get(0);
                                var ud_content_form = that.get(0).get(1).get(0).get(0);
                                var member_group_form = that.get(0).get(1).get(1).get(0);
                                var group_grant_form = that.get(0).get(1).get(3).get(0);

                                ud_content_form.reset();
                                member_group_form.reset();
                                group_grant_form.reset();

                                ud_content_form.setValue( 'ud_content_id'+'-'+select.get('ud_content_id'), true );
                                member_group_form.setValue(  'member_group_id'+'-'+select.get('member_group_id') , true );
                                group_grant_form.setValue(select.data.group_grant);

                                category.collapseAll();
                                category.getRootNode().expand();

                                category.selectPath(select.data.category_full_path);
                                category.expand(true);
                            }
                        }

                    }]
                },{
                    xtype: 'panel',
                    region: 'center',
                    layout: {
                        type: 'hbox',
                        autoScroll: true,
                        padding: 10,
                        align:'stretch'
                    },
                    defaults:{
                        margins:'0 5 0 0',
                        padding: 5

                    },
                    items: [{
                        flex: 1,
                        xtype: 'fieldset',
                        minWidth: 150,
                        //title: '사용자정의콘텐츠',
                        title: _text('MN02046'),
                        height: 200,
                        layout: 'fit',
                        items: [{
                            xtype: 'checkboxgroup',
                            autoScroll: true,
                            cls: 'x-check-group-alt',
                            columns: 1,
                            frame: true,
                            items: [
                            <?php
                            $metas = $db->queryAll("select * from bc_ud_content order by show_order");

                            while ($meta = current($metas))
                            {
                                if(key($metas) == 0)
                                {
                                    //'전체 선택'
                                    echo "{boxLabel: _text('MN02023'), name: 'ud_content_id-0'
                                        ,listeners: {
                                            check: function( self, checked  ){
                                                self.ownerCt.items.each( function(k){
                                                    k.setValue( checked  );
                                                });
                                            }
                                        }
                                    },\n";
                                }

                                echo "{boxLabel: '{$meta['ud_content_title']}', name: 'ud_content_id-{$meta['ud_content_id']}' }\n";
                                if (next($metas))
                                {
                                    echo ',';
                                }
                            }
                            ?>
                            ],
                            listeners: {
                            }
                        }]
                    },{
                        xtype: 'fieldset',
                        minWidth: 150,
                        flex: 1,
                        //title: '사용자 그룹',
                        title: _text('MN02047'),
                        layout: 'fit',
                        items :	[{
                            xtype: 'checkboxgroup',
                            autoScroll: true,
                            cls: 'x-check-group-alt',
                            columns: 1,
                            items: [

                            <?php
                            $member_group_info = $db->queryAll("select * from BC_MEMBER_GROUP order by member_group_id");
                            while ( $member_group = current($member_group_info) )
                            {
                                if(key($member_group_info) == 0)
                                {
                                    //'전체 선택'
                                    echo "{boxLabel: _text('MN02023'), name: 'member_group_id-0'
                                        ,listeners: {
                                            check: function( self, checked  ){
                                                self.ownerCt.items.each( function(k){
                                                    k.setValue( checked  );
                                                });
                                            }
                                        }
                                    },\n";
                                }
                                echo "{boxLabel: '{$member_group['member_group_name']}', name: 'member_group_id-{$member_group['member_group_id']}' }\n";
                                if (next($member_group_info))
                                {
                                    echo ',';
                                }
                            }
                            ?>
                            ]
                        }]
                    },{
                        flex: 1,
                        xtype: 'fieldset',
                        //title: '카테고리',
                        title: _text('MN00387'),
                        minWidth: 200,
                        layout: 'fit',
                        items: [{
                            xtype: 'treepanel',
                            autoScroll: true,
                            enableDD: false,
                            animate: true,
                            rootVisible: true,
                            loader: new Ext.tree.TreeLoader({
                                url: '/store/get_categories.php',
                                listeners: {
                                    beforeload: {
                                        fn: function (treeLoader, node, callback){
                                            treeLoader.baseParams.action = "get-folders";
                                            treeLoader.baseParams.mode = "all";
                                            treeLoader.baseParams.check = "true";
                                        },
                                        scope: this
                                    }
                                }
                            }),
                            root: new Ext.tree.AsyncTreeNode({
                                id : '0',
                                text : '<?=$category['category_title']?>',
                                singleClickExpand : true
                            })
                        }]
                    },{
                        xtype: 'fieldset',
                        flex: 1,
                        //title: '권한',
                        title: _text('MN00110'),
                        minWidth: 150,
                        layout: 'fit',
                        items : [{
                            xtype: 'radiogroup',
                            autoScroll: true,
                            cls: 'x-check-group-alt',
                            columns: 1,
                            frame: true,
                            items: [
                                {boxLabel: _text('MN02037'), name: 'group_grant' ,inputValue: 4 },//'숨김'
                                {boxLabel: _text('MN02035'), name: 'group_grant' ,inputValue: 0 },//'권한 없음'
                                {boxLabel: _text('MN00035'), name: 'group_grant' ,inputValue: 1 },//'읽기'
                                {boxLabel: _text('MN00035')+' / '+_text('MN00041')+' / '+' / '+_text('MN00043')+' / '+_text('MN02036'), name: 'group_grant' ,inputValue: 2 },//'읽기 / 생성 / 수정 / 이동'
                                {boxLabel: _text('MN00035')+' / '+_text('MN00041')+' / '+' / '+_text('MN00043')+' / '+_text('MN02036')+' / '+_text('MN00034'), name: 'group_grant' ,inputValue: 3 }//'읽기 / 생성 / 수정 / 이동 / 삭제'
                            ]
                        }]
                    }]
                }]
            }];

            Ariel.config.Category_Grant.superclass.initComponent.call(this);
        }
    });

    Ariel.config.Content_Grant = Ext.extend(Ext.Panel, {
        //title: '콘텐츠 권한 관리',
        title: _text('MN02049'),
        header: false,
        layout: 'fit',
        border: false,
        // frame: true,
        initComponent: function(config){
            Ext.apply(this, config || {});

            var that = this;

            this.request = function(title, params, grid){
                Ext.Msg.show({
                    icon: Ext.Msg.QUESTION,
                    //title: '확인',
                    title: _text('MN00024'),
                    msg: title+' : '+_text('MSG02039'),//title+' : '+이 작업을 진행하시겠습니까?
                    buttons: Ext.Msg.OKCANCEL,
                    fn: function(btnId, text, opts){
                        if(btnId == 'cancel') return;

                        var w = Ext.Msg.wait(_text('MN02045')+'...');//'요청중...'
                        Ext.Ajax.request({
                            url: '/store/grant_set.php',
                            params: params,
                            callback: function(opts, success, response){
                                w.hide();
                                if(success){
                                    try{
                                        var r = Ext.decode(response.responseText);
                                        if(!r.success){
                                            //Ext.Msg.alert('알림', r.msg);
                                            Ext.Msg.alert( _text('MN00023'), r.msg);
                                            return;
                                        }

                                        that.get(0).getForm().reset();
                                        grid.getSelectionModel().clearSelections();
                                        grid.getStore().reload();
                                    }
                                    catch (e)
                                    {
                                        Ext.Msg.alert(e['name'], e['message']);
                                    }
                                }
                                else
                                {
                                    Ext.Msg.alert( _text('MN01098'), response.statusText);//'서버 오류'
                                }
                            }
                        });
                    }
                });
            };

            this.formCheck = function(){

                var ud_content_form = that.get(0).get(1).get(0).get(0);
                var member_group_form = that.get(0).get(1).get(1).get(0);
                var group_grant_form = that.get(0).get(1).get(2).get(0);

                if( Ext.isEmpty(ud_content_form.getValue()) )
                {
                    //Ext.Msg.alert('알림','사용자정의콘텐츠를 선택해주세요');
                    Ext.Msg.alert( _text('MN00023'), _text('MSG02005'));
                    return false;
                }
                else if( Ext.isEmpty(member_group_form.getValue()) )
                {
                    //Ext.Msg.alert('알림','사용자 그룹을 선택해주세요');
                    Ext.Msg.alert( _text('MN00023'), _text('MSG02006'));
                    return false;
                }
                else if( Ext.isEmpty(group_grant_form.getValue()) )
                {
                    //Ext.Msg.alert('알림','권한을 선택해주세요');
                    Ext.Msg.alert( _text('MN00023'), _text('MSG02007'));
                    return false;
                }

                return true;
            }

            this.items = [{
                xtype: 'form',
                id: 'right_management_form',
                bodyStyle: 'border:none;',
                autoScroll: true,
                layout: 'border',
                bodyPadding: 10,
                frame: false,
                defaults: {
                    split: true
                },
                url: '/store/grant_set.php',
                buttonAlign: 'center',
                buttons: [{
                    text : '<span style="position:relative;top:1px;"><i class="fa fa-check" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00046'),
                    scale: 'medium',
                    handler: function() {
                        var formvalues = that.get(0).getForm().getValues();

                        var ud_content_form		= that.get(0).get(1).get(0).get(0);
                        var member_group_form	= that.get(0).get(1).get(1).get(0);
                        var content_grant		= that.get(0).get(1).get(2).get(0);
                        var grid = that.get(0).get(0).get(0);

                        if( !that.formCheck() ) return;

                        formvalues.action = 'add';
                        formvalues.grant_type = 'content_grant';
                        that.request( _text('MN00046'), formvalues, grid );//'저장'
                    }
                },{
                    text : '<span style="position:relative;top:1px;"><i class="fa fa-trash" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00034'),
                    scale: 'medium',
                    handler: function() {

                        var grid = that.get(0).get(0).get(0);

                        if( Ext.isEmpty(grid.getSelectionModel().getSelected()) )
                        {
                            //Ext.Msg.alert('알림','먼저 대상을 선택 해 주시기 바랍니다.');
                            Ext.Msg.alert( _text('MN00023'), _text('MSG02001'));
                            return;
                        }

                        var selections = grid.getSelectionModel().getSelections();
                        var list = new Array();
                        var params = {};
                        Ext.each(selections, function(i){
                            list.push(i.data);
                        });

                        params.action = 'delete';
                        params.grant_type = 'content_grant';
                        params.list =  Ext.encode(list);
                        that.request( _text('MN01106'), params, grid );//'삭제'
                    }
                }],
                items: [{
                    xtype: 'panel',
                    region: 'north',
                    border: false,
                    height: 300,
                    layout: 'fit',
                    split: true,
                    items: [{
                        xtype: 'grid',
                        stripeRows: true,
                        title: '<span class="user_span"><span class="icon_title"><i class=""></i></span><span class="main_title_header">'+_text('MN02038')+'</span></span>',
                        cls: 'grid_title_customize proxima_customize',
                        border: false,
                        loadMask: true,
                        autoShow: true,
                        store: new Ext.data.GroupingStore({
                            reader: new Ext.data.ArrayReader({}, [
                                {name: 'ud_content_title'},
                                {name: 'member_group_name'},
                                {name: 'category_title'},
                                {name: 'group_grant'},
                                {name: 'ud_content_id' },
                                {name: 'member_group_id'},
                                {name: 'grant_text'}
                            ]),
                            autoLoad: true,
                            url: '/store/grant_store.php',
                            baseParams: {
                                grant_type: 'content_grant'
                            },
                            root: 'data',
                            sortInfo: { field: 'member_group_name', direction: "ASC" },
                            groupField:'ud_content_title'
                        }),
                        border: false,
                        autoShow: true,
                        columns: [
                            { header: _text('MN02046'), sortable: true, dataIndex: 'ud_content_title' },//사용자정의콘텐츠
                            { header: _text('MN02047'),  sortable: true, dataIndex: 'member_group_name' },//사용자 그룹
                            { header: _text('MN01003'), sortable: true, dataIndex: 'grant_text' }//권한관리
                        ],
                        sm: new Ext.grid.RowSelectionModel({
                        }),

                        view: new Ext.grid.GroupingView({
                            forceFit: true,
                            groupTextTpl: '{group}'
                            //groupTextTpl: '{text} ({[values.rs.length]} {[values.rs.length > 1 ? "Items" : "Item"]})'
                        }),
                        listeners: {
                            rowclick: function(self, idx, e){
                                var select = self.getSelectionModel().getSelected();
                                var form = that.get(0).getForm();

                                if( Ext.isEmpty(select) ) return;

                                var ud_content_form = that.get(0).get(1).get(0).get(0);
                                var member_group_form = that.get(0).get(1).get(1).get(0);
                                var group_grant_form  = that.get(0).get(1).get(2).get(0);

                                ud_content_form.reset();
                                member_group_form.reset();
                                group_grant_form.reset();

                                ud_content_form.setValue( 'ud_content_id'+'-'+select.get('ud_content_id'), true );
                                member_group_form.setValue(  'member_group_id'+'-'+select.get('member_group_id') , true );

                                group_grant_form.items.each(function(item) {

                                    //name에서 권한 코드 뽑기
                                    var nameArray = item.getName().split("-");

                                    // 권한 체크
                                    if (parseInt(nameArray[1]) & parseInt( select.get('group_grant'))) {
                                        group_grant_form.setValue(item.getName(), true);
                                    }
                                });
                            }
                        }

                    }]
                },{
                    region: 'center',
                    id: 'right_management_rights',
                    xtype: 'panel',
                    //title: _text('MN02192'),
                    title: '<span class="user_span"><span class="icon_title"><i class=""></i></span><span class="main_title_header">'+_text('MN02192')+'</span></span>',
                    cls: 'grid_title_customize change_background_panel',
                    padding: 10,
                    frame: true,
                    layout: {
                        type: 'hbox',
                        autoScroll: true,
                        padding: 10,
                        align:'stretch'
                    },
                    defaults:{
                        margins:'0 5 0 0',
                        padding: 5
                    },
                    items: [{
                        flex: 1,
                        xtype: 'fieldset',
                        minWidth: 150,
                        //title: '사용자정의콘텐츠',
                        title: _text('MN02046'),
                        height: 200,
                        layout: 'fit',
                        items: [{
                            xtype: 'checkboxgroup',
                            autoScroll: true,
                            cls: 'x-check-group-alt',
                            columns: 1,
                            frame: true,
                            items: [
                            <?php
                                $metas = $db->queryAll("select * from bc_ud_content order by show_order");
                                $items_ud_content = array();
                                foreach ($metas as $meta) {
                                    array_push($items_ud_content, "{boxLabel: '{$meta['ud_content_title']}', name: 'ud_content_id-{$meta['ud_content_id']}' }");
                                }
                                
                                echo join($items_ud_content, ',');
                            ?>
                            ]
                        }]
                    }, {
                        flex: 1,
                        xtype: 'fieldset',
                        title: _text('MN02047'),
                        layout: 'fit',
                        items : [{
                            xtype: 'checkboxgroup',
                            columns: 1,
                            autoScroll: true,
                            cls: 'x-check-group-alt',
                            frame: true,
                            items: [
                            <?php
                                $member_group_info = $db->queryAll("select * from BC_MEMBER_GROUP order by member_group_id");
                                $items_member_group_info = array();
                                foreach ($member_group_info as $member_group) {
                                    array_push($items_member_group_info, "{boxLabel: '{$member_group['member_group_name']}', name: 'member_group_id-{$member_group['member_group_id']}'}");
                                }
                                
                                echo join($items_member_group_info, ',');
                            ?>
                            ]
                        }]
                    },{
                        flex: 1,
                        xtype: 'fieldset',
                        title: _text('MN01003'),//권한관리
                        layout: 'fit',
                        items: [{
                            xtype: 'checkboxgroup',
                            columns: 1,
                            autoScroll: true,
                            cls: 'x-check-group-alt',
                            frame: true,
                            items: [
                            <?php
                                $grant_query = "
                                        SELECT	A.ID, A.CODE, 
                                                (CASE WHEN C.LANG = 'ko' THEN COALESCE(A.NAME, A.ENAME) ELSE COALESCE(A.ENAME, A.NAME) END) AS NAME,
                                                A.CODE_TYPE_ID, A.SORT, A.HIDDEN, A.ENAME, A.REF1, A.OTHER, 
                                                B.NAME AS TYPE_NAME
                                        FROM	BC_CODE A
                                                LEFT OUTER JOIN BC_CODE_TYPE B ON (B.ID = A.CODE_TYPE_ID)
                                                LEFT OUTER JOIN BC_MEMBER C ON (C.USER_ID = 'admin')
                                        WHERE	B.CODE = 'content_grant' AND A.USE_YN='Y'
                                        ORDER BY A.ID
                                ";
                                $grant_list = $db->queryAll($grant_query);
                                
                                $items_content_grant = array();
                                foreach ($grant_list as $grant) {
                                    array_push($items_content_grant, "{boxLabel: '{$grant['name']}', name: 'grant-{$grant['code']}'}");
                                }
                                
                                
                                $grant_query = "
                                    SELECT	*
                                    FROM
                                        (
                                            SELECT	A.ID, A.CODE, 
                                                        (CASE WHEN C.LANG = 'ko' THEN COALESCE(A.NAME, A.ENAME) ELSE COALESCE(A.ENAME, A.NAME) END) AS NAME,
                                                        A.CODE_TYPE_ID, A.SORT, A.HIDDEN, A.ENAME, A.REF1, A.OTHER, 
                                                        B.NAME AS TYPE_NAME
                                                FROM	BC_CODE A
                                                        LEFT OUTER JOIN BC_CODE_TYPE B ON (B.ID = A.CODE_TYPE_ID)
                                                        LEFT OUTER JOIN BC_MEMBER C ON (C.USER_ID = '$user_id')
                                                WHERE	A.REF1 = 'CONTEXT_GRANT' AND A.USE_YN='Y'
                                                AND		B.CODE = 'content_grant'
                                            UNION ALL
                                                SELECT	A.ID, A.CODE, 
                                                        (CASE WHEN C.LANG = 'ko' THEN COALESCE(A.NAME, A.ENAME) ELSE COALESCE(A.ENAME, A.NAME) END) AS NAME,
                                                        A.CODE_TYPE_ID, A.SORT, A.HIDDEN, A.ENAME, A.REF1, A.OTHER, 
                                                        B.NAME AS TYPE_NAME
                                                FROM	BC_CODE A
                                                        LEFT OUTER JOIN BC_CODE_TYPE B ON (B.ID = A.CODE_TYPE_ID)
                                                        LEFT OUTER JOIN BC_MEMBER C ON (C.USER_ID = '$user_id')
                                                WHERE	A.REF1 = 'ARCHIVE_GRANT' AND A.USE_YN='Y'
                                                AND		'".ARCHIVE_USE_YN."' = 'Y'
                                                AND		B.CODE = 'content_grant'
                                            UNION ALL
                                                SELECT	A.ID, A.CODE, 
                                                        (CASE WHEN C.LANG = 'ko' THEN COALESCE(A.NAME, A.ENAME) ELSE COALESCE(A.ENAME, A.NAME) END) AS NAME,
                                                        A.CODE_TYPE_ID, A.SORT, A.HIDDEN, A.ENAME, A.REF1, A.OTHER, 
                                                        B.NAME AS TYPE_NAME
                                                FROM	BC_CODE A
                                                        LEFT OUTER JOIN BC_CODE_TYPE B ON (B.ID = A.CODE_TYPE_ID)
                                                        LEFT OUTER JOIN BC_MEMBER C ON (C.USER_ID = '$user_id')
                                                        LEFT OUTER JOIN BC_SYS_CODE D ON (D.CODE = A.REF1)
                                                WHERE	D.CODE = 'INTERWORK_LOUDNESS' AND A.USE_YN='Y'
                                                AND		D.USE_YN = 'Y'
                                                AND		B.CODE = 'content_grant'
                                        ) vv
                                    ORDER BY CAST(CODE AS DOUBLE PRECISION)
                                ";
                                $grant_list = $db->queryAll($grant_query);
                                foreach ($grant_list as $grant) {
                                    array_push($items_content_grant, "{boxLabel: '{$grant['name']}', name: 'grant-{$grant['code']}'}");
                                }

                                echo join($items_content_grant, ',');
                            ?>
                            ]
                        }]
                    }]
                }]
            }];

            Ariel.config.Content_Grant.superclass.initComponent.call(this);
        }
    });

    return new Ariel.config.Content_Grant();

    // return {
    //     xtype: 'tabpanel',
    //     activeTab: 0,
    //     border: false,

    //     items: [
    //         new Ariel.config.Category_Grant(),
    //         new Ariel.config.Content_Grant()
    //     ],

    //     listeners: {
    //         tabchange: function(self, p){
    //         }
    //     }
    // }
})()