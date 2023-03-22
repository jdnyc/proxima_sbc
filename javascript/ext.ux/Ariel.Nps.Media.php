<?php
use Proxima\core\Session;

require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lang.php');

Session::init();

$user_id = $_SESSION['user']['user_id'];
$user_lang = $_SESSION['user']['lang'];

$map_categories = getCategoryMapInfo();

$mapping_category = json_encode($map_categories);

$member_optioin = $db->queryRow("
    SELECT	*
    FROM		BC_MEMBER_OPTION
    WHERE	MEMBER_ID = (
        SELECT	MEMBER_ID
        FROM		BC_MEMBER
        WHERE	USER_ID =  '".$user_id."' and del_yn='N'
    )
");
$show_content_subcat_yn = trim($member_optioin['show_content_subcat_yn']);
$category_collapse = $member_optioin['category_visible'] == 'Y' ?  'collapsed : false, ' : 'collapsed : true,';

$top_menu_mode = $member_optioin['top_menu_mode'];

/**
    이미지 관련 TAB 만 보이던지 영상 관련 TAB만 보이던지.
*/
$show_only_bs_content_id = 0;

if ($_REQUEST['agent'] == PHOTOSHOP_AGENT_NM) {
    $photoshop_plugin_use_yn = $arr_sys_code['photoshop_plugin_use_yn']['use_yn'];
    if ($photoshop_plugin_use_yn == 'Y') {
        if ($arr_sys_code['photoshop_plugin_use_yn']['ref5'] != '0') {
            $show_only_bs_content_id = $arr_sys_code['photoshop_plugin_use_yn']['ref5'];
        }
    }
}

$searchengine_usable = ($arr_sys_code['interwork_gmsearch']['use_yn'] == 'Y')? 'true' : 'false';

?>

var autocomplete_flag = true;
var max_width = window.innerWidth*0.8;
var userId = '<?php echo $user_id; ?>';
var UPLOAD_URL = '<?php echo map_server_ip(UPLOAD_URL); ?>';

// custom functions for custom context menu
<?php

if (defined('CUSTOM_ROOT') && class_exists('\ProximaCustom\core\ContentAction')) {
    \ProximaCustom\core\ContentAction::renderCustomFunctions();
}
?>

// compositefield 생성 함수. 상세검색에서 사용
function buildCmp(meta_table_id, container_id, item_index, emptyText){
    var basic_width = 320;

    return {
        xtype: 'compositefield',
        hideLabel: true,
        width: 580,
        style: {
            padding: '3px'
        },
        items: [{
            xtype: 'combo',
            //>>emptyText: '검색 항목를 선택하세요.',
            emptyText: '<?=_text('MSG00135')?>',
            editable: false,
            typeAhead: true,
            //flex: 1.5,
            width: 200,
            triggerAction: 'all',
            displayField: 'name',
            valueField: 'meta_field_id',
            hiddenName: 'meta_field_id',
            hiddenValue: 'meta_field_id',
            item_index: item_index,
            store: new Ext.data.JsonStore({
                url: '/store/search/get_dynamic2.php',
                root: 'data',
                baseParams: {
                        meta_table_id: meta_table_id,
                        container_id: container_id,
                        type: 'component'
                },
                fields: [
                        'name', 'meta_field_id', 'type', 'default_value', 'table', 'field'
                ]
            }),
            listeners: {
                select: function(self, r, idx) {
                    var c;
                    var p = self.ownerCt;

                    for (; 1 != p.items.length; ) {
                        p.remove( p.get(1) );
                    }

                    var type = r.get('type');
                    var name = r.get('name');
                    if (type == 'datefield') {
                        p.add({
                            xtype: 'datefield',
                            name: 's_dt',
                            altFormats: 'Ymd|ymd|Y-m-d|y-m-d|Y/m/d|Y/m/d',
                            format: 'Y-m-d',
                            //flex: 0.9,
                            //width: 90,
                            width: 140,
                            listeners: {
                                select: function(self, date){
                                  self.ownerCt.get(5).setMinValue(self.value);
                                },
                                render: function(self){
                                    var d = new Date();
                                    self.setValue(d.add(Date.DAY, -7).format('Y-m-d'));
                                }
                            }
                        },{
                            xtype: 'displayfield',
                            width: 5
                        },{
                            xtype: 'displayfield',
                            value: '~',
                            //flex: 0.2,
                            width: 30,
                            style:{
                                "text-align": 'center',
                            }
                        },{
                            xtype: 'displayfield',
                            width: 5
                        },{
                            xtype: 'datefield',
                            name: 'e_dt',
                            altFormats: 'Ymd|ymd|Y-m-d|y-m-d|Y/m/d|Y/m/d',
                            format: 'Y-m-d',
                            //flex: 0.9,
                            //width: 90,
                            width: 140,
                            listeners: {
                                render: function(self){
                                    var d = new Date();
                                    self.setValue(d.format('Y-m-d'));
                                }
                            }
                        });
                    } else if ( name == '알파값' ) {
                        p.add({
                            xtype: 'combo',
                            flex:2,
                            displayField:'name',
                            valueField: 'value',
                            typeAhead: true,
                            triggerAction: 'all',
                            lazyRender:true,
                            mode: 'local',
                            store: new Ext.data.ArrayStore({
                                id: 0,
                                fields: [
                                    'name',
                                    'value'
                                ],
                                data: [['전체', 'all'], ['O', '1'], ['X', '2']]
                            })
                        });
                    } else if ( name == '해상도' ) {
                        p.add({
                            xtype:'combo',
                            flex:2,
                            displayField:'name',
                            valueField: 'value',
                            typeAhead: true,
                            triggerAction: 'all',
                            lazyRender:true,
                            mode: 'local',
                            value: 'all',
                            store: new Ext.data.ArrayStore({
                                id: 0,
                                fields: [
                                    'name',
                                    'value'
                                ],
                                data: [['전체', 'all'], ['720px 이하', 'low'], ['720 ~ 1920', 'center'], ['1920px 이상', 'high']]
                            })
                        });
                    } else if ( type == 'checkbox') {
                        /*
                        var default_value_array = r.get('default_value').split(';');												
                        for (i = 0; i < default_value_array.length; i++){
                            //p.add({
                                //xtype: 'checkbox',
                                //flex: 1,
                                //>>emptyText: '검색 값를 선택하세요.',
                                //emptyText: '<?=_text('MSG00041')?>',
                                //margins: '0 0 0 2',
                                //name: 'value',
                                //inputValue: default_value_array[i],
                                //boxLabel:default_value_array[i]
                            //});
                            
                            p.add({
                                xtype: 'radio',
                                flex: 1,
                                name: 'type_radio',
                                boxLabel: default_value_array[i],
                                inputValue:default_value_array[i]
                            });													
                        }
                        */
                        p.add({
                            xtype: 'checkbox',
                            width: basic_width,
                            //margins: '0 5 0 0',
                            name: 'type_checkbox'
                        });
                    } else if ( type == 'combo' ) {
                        p.add({
                            xtype: 'combo',
                            //flex: 2,
                            width: basic_width,
                            //>>emptyText: '검색 값를 선택하세요.',
                            emptyText: '<?=_text('MSG00041')?>',
                            editable: false,
                            typeAhead: true,
                            triggerAction: 'all',
                            store: r.get('default_value').split(';'),
                            name: 'value'
                        });
                    } else {
                        p.add({
                            xtype: 'textfield',
                            allowBlank: false,
                            //>> emptyText: '검색어를 입력하세요',
                            emptyText: '<?=_text('MSG00007')?>',
                            //margins: '0 2 0 0',
                            name:'value',
                            //flex: 2,
                            width: basic_width,
                            enableKeyEvents: true,
                            listeners: {
                                keydown: function(self, e) {
                                    if (e.getKey() == e.ENTER) {
                                        e.stopEvent();
                                        var search_button = null;
                                        if(meta_table_id == '4000406') {
                                            search_button = Ext.getCmp('a-search-audio-button');
                                        } else {
                                            search_button = Ext.getCmp('a-search-media-button');
                                        }

                                        search_button.handler(search_button);
                                    }
                                }
                            }
                        });
                    }

                    //p.add({xtype: 'displayfield', width: 5});

                    p.add({
                        xtype: 'combo',
                        hidden: true,
                        emptyText: '정렬방식',
                        editable: false,
                        typeAhead: true,
                        triggerAction: 'all',
                        mode: 'local',
                        displayField:'name',
                        valueField: 'value',
                        value: 'ASC',
                        lazyRender:true,
                        //margins: '0 0 0 2',
                        //flex: 0.7,
                        width: 100,
                        store: new Ext.data.ArrayStore({
                            fields: ['name','value'],
                            //data: [['오름차순','ASC'],['내림차순','DESC']]
                            data: [[ _text('MN02174'),'ASC'],[ _text('MN02175'),'DESC']]
                        })
                    });

                    p.add({xtype: 'displayfield', width: 5});

                    p.add({
                        xtype: 'button',
                        //>>text: '리셋', MN00055
                        //text: '<?=_text('MN00055')?>',
                        width: 25,
                        text: '<span style="position:relative;" title="'+_text('MN00055')+'"><i class="fa fa-refresh" style="font-size:13px;"></i></span>',
                        //margins: '0 0 0 2',
                        handler: function(self, e){
                            var c = self.ownerCt;
                            var cnt = c.items.length;

                            if (c.get(1).xtype == 'datefield') {
                                c.remove(1);
                                c.remove(1);
                                c.remove(1);
                                c.remove(1);
                                c.remove(1);

                                c.insert(1, {
                                    xtype: 'textfield',
                                    //>>emptyText: '검색어를 입력하세요',
                                    emptyText: '<?=_text('MSG00007')?>',
                                    name:'value',
                                    //flex: 2
                                    width: basic_width
                                });

                                c.doLayout();
                            }
                            else if (c.get(1).xtype == 'radio') {
                                for(i=0; i < c.items.length; i++){
                                    if(c.get(i).xtype == 'radio'){
                                        c.get(i).setVisible(false);
                                    }

                                }
                                c.insert(1, {
                                    xtype: 'textfield',
                                    //>>emptyText: '검색어를 입력하세요',
                                    emptyText: '<?=_text('MSG00007')?>',
                                    name:'value',
                                    //margins: ' 0 0 2',
                                    //flex:2
                                    width: basic_width
                                });

                            c.doLayout();
                            }
                            else {
                                c.remove(1);

                                c.insert(1, {
                                    xtype: 'textfield',
                                    //>>emptyText: '검색어를 입력하세요',
                                    emptyText: '<?=_text('MSG00007')?>',
                                    name:'value',
                                    //flex: 2
                                    width: basic_width
                                });

                                c.doLayout();
                            }

                            for (var i=0; i<cnt; i++) {
                                if (c.get(i) && typeof c.get(i).reset == 'function') {
                                    c.get(i).reset();
                                }
                            }
                        }
                    });

                    p.add({
                        xtype: 'hidden',
                        name: 'table',
                        value: r.get('table')
                    });
                    p.add({
                        xtype: 'hidden',
                        name: 'field',
                        value: r.get('field')
                    });

                    p.doLayout();
                }
            }
        },{
            xtype: 'textfield',
            //>>emptyText: '검색어를 입력하세요',
            emptyText: '<?=_text('MSG00007')?>',
            name: 'value',
            //flex: 2
            width: basic_width
        },{
            xtype: 'combo',
            hidden: true,
            emptyText: '정렬방식',
            editable: false,
            typeAhead: true,
            triggerAction: 'all',
            mode: 'local',
            displayField:'name',
            valueField: 'value',
            value: 'ASC',
            lazyRender:true,
            //flex: 0.7,
            width: 100,
            store: new Ext.data.ArrayStore({
                fields: ['name','value'],
                //data: [['오름차순','ASC'],['내림차순','DESC']]
                data: [[ _text('MN02174'),'ASC'],[ _text('MN02175'),'DESC']]
            })
        },{
            xtype: 'button',
            //>>text: '리셋',
            width: 25,
            text: '<span style="position:relative;" title="'+_text('MN00055')+'"><i class="fa fa-refresh" style="font-size:13px;"></i></span>',
            //margins: '0 0 0 2',
            handler: function(self, e){
                var c = self.ownerCt;
                var cnt = c.items.length;

                if (c.get(1).xtype == 'datefield') {
                    c.remove( c.get(1) );
                    c.remove( c.get(1) );
                    c.remove( c.get(1) );

                    c.insert(1, {
                        xtype: 'textfield',
                        //>>emptyText: '검색어를 입력하세요',
                        emptyText: '<?=_text('MSG00007')?>',
                        name:'value',
                        flex: 1
                    });

                    c.doLayout();
                } else {
                    for (var i=0; i < cnt; i++)
                    {
                        if ( c.get(i) && typeof c.get(i).reset == 'function' )
                        {
                                c.get(i).reset();
                        }
                    }
                }
            }
        }]
    };
}

function setCategory(action, value){
    return;
    Ext.Ajax.request({
        url : '/store/search/change_option.php',
        params : {
            action : action,
            value : value
        },
        callback: function(opt, success, response){
            try{
                var r = Ext.decode(response.responseText);
            }catch (e){
                console.log('Error setCategory : ', e);
                Ext.Msg.alert(_text('MN00024'), e['message']);
            }
        }
    });
}

// 웹업로드
function upload() {		
    var option = {
        user_id: '<?=$user_id?>',
        user_lang: '<?=$user_lang?>'
    };
    var uploadUrl = UPLOAD_URL;
    proximaWebUploader(option, uploadUrl);
}

// 파일인제스트 실행
function launchFileIngest() {
    var customUrl = 'gemiso.file-ingest://args?';
    var params = [];
    params.push({key: 'user_id', value: userId});	
    launchApp(customUrl, params);
}

Ariel.Nps.Media = Ext.extend(Ext.Panel, {
    layout: 'border',
    border:false,
    // 초기화 여부
    initialized: false,
    layoutConfig: {
        //align : 'stretch',
    },
    listeners: {
        show: function(self) {
            if(self.initialized) {
                return;
            }
            Ext.getCmp('tab_warp').setActiveTab(0);
            self.initialized = true;
        }
    },
    initComponent: function(config) {		
        Ext.apply(this, config || {});
        var that = this;

        this.items = [
            {
                xtype: 'panel',
                id: 'west-menu-media',
                cls: 'west-menu-media',
                region:'west',
                title: 'menu media',
                //<?=$category_collapse?>
                //width: <?=$member_optioin['category_width']?>,
                width: 225,
                //minWidth : 350,
                maxWidth: max_width,
                split: true,
                autoScroll: true,
                collapsible: true,
                header: false,
                is_show: true,
                footer: true,
                layout: 'anchor',
                //layout: 'fit',
                items: [
                    //{
                    //    /*Category*/
                    //    xtype: 'panel',
                    //    title: '<span class="user_span"><span class="icon_title"><i class="fa fa-list"></i></span><span class="user_title" style="color: #ffffff">'+_text('MN00271')+'</span></span>',
                    //    id: 'west_menu_item_media_category',
                    //    cls: 'west-menu-item-media',
                    //    anchor: '95% 75%',
                    //    layout: 'fit',
                    //    collapsible: true,
                    //    titleCollapse: true,
                    //    hideCollapseTool: true,
                    //    split: true,
                    //    items:[
                    //        new Ariel.nav.MainPanel({
                    //            useWholeCategory: false
                    //        })
                    //    ],
                    //    listeners: {					
                    //        collapse: function (p) {
                    //            p.ownerCt.doLayout();
                    //        },
                    //        expand: function (p) {
                    //            p.ownerCt.doLayout();
                    //    }
                    //    }
                    //},
                    {
                        /*Category*/
                        /* //! 좌측 프로그램별 | 연도별 트리구조 패널 */
                        xtype: 'panel',
                        title: '<span class="user_span", onclick="change_media_active_tab(0)", style="cursor:pointer"><span class="icon_title"><i class="fa fa-list"></i></span><span class="user_title", style="color: #ffffff">'+_text('MN00321')+'</span></span>'+
                        '<span style="color: #ffffff"> | </span> '+
                        '<span class="user_span", onclick="change_media_active_tab(1)", style="cursor:pointer"><span ><i class="fa fa-list"></i></span><span class="user_title", style="color: #ffffff">'+'연도 별'+'</span></span>',
                        id: 'west_menu_item_media_category',
                        cls: 'west-menu-item-media',
                        anchor: '100% 75%',
                        layout: 'fit',
                        //collapsible: true,
                        collapsible: false,
                        titleCollapse: true,
                        hideCollapseTool: true,
                        split: true,
                        items:[
                            new Ariel.nav.MainPanel({
                                useWholeCategory: false
                            })
                        ],
                        listeners: {					
                            collapse: function (p) {
                                p.ownerCt.doLayout();
                            },
                            expand: function (p) {
                                p.ownerCt.doLayout();
                            }
                        }
                    },
                    {
                        /*Favorites*/
                        xtype: 'panel',
                        title: '<span class="user_span"><span class="icon_title"><i class="fa fa-star"></i></span><span class="user_title" style="color:#ffffff">'+_text('MN02139')+'</span></span>',
                        cls: 'west-menu-item-media',
                        anchor: '100% 0%',
                        layout: 'fit',
                        hidden: true,
                        split: true,
                        collapsed: true,
                        collapsible: true,
                        titleCollapse: true,
                        hideCollapseTool: true,
                        listeners: {					
                            collapse: function (p) {
                                p.ownerCt.doLayout();
                            },
                            expand: function (p) {
                                p.ownerCt.doLayout();
                        }
                        }
                    },{
                        /*My Search*/
                        xtype: 'panel',
                        hidden: true,
                        //title: '<span class="user_span"><span class="icon_title"><i class="fa fa-search"></i></span><span class="user_title" style="color:#ffffff">'+_text('MN02536')+'</span></span>',
                        title: '<span class="user_span"><span class="icon_title"><i class="fa fa-search"></i></span><span class="user_title" style="color:#ffffff">빠른 검색</span></span>',
                        cls: 'west-menu-item-media',
                        anchor: '100% 25%',
                        split: true,
                        collapsible: true,
                        titleCollapse: true,
                        hideCollapseTool: true,
                        layout: 'fit',
                        items: [{
                            xtype: 'grid',
                            layout: 'fit',
                            id: 'nps_media__custom_search_grid',
                            cls: 'proxima_grid_tag',
                            stripeRows: false,
                            hideHeaders: true,
                            rowSelectedIndex: -1,
                            sm: new Ext.grid.RowSelectionModel({singleSelect:true}),
                            store: new Ext.data.JsonStore({
                                proxy: new Ext.data.HttpProxy({
                                    method: 'GET',       
                                    prettyUrls: false,       
                                    url: '/store/search/custom_search.php?user_id='+userId
                                }),							
                                root: 'data',
                                fields: ['id', 'name','filters', 'show_order', 'color', 'user_id']
                            }),
                            viewConfig: {
                                loadMask: true,
                                forceFit: true,
                                getRowClass: function (record, rowIndex, rp, store) {
                                    rp.tstyle += 'height: 23px;';
                                }
                            },
                            columns: [
                                {
                                    header: '',
                                    dataIndex: 'color',
                                    sortable:false,
                                    width: 40,
                                    renderer: function(value, metaData, record, rowIndex, colIndex, store) {
                                        return '<i class="fa fa-square" style=\"width: 10px;height: 10px;font-size: 18px;margin-left: 15px;color:'+value+';\"></i>';
                                    }
                                },
                                { header: '', dataIndex: 'filters', sortable:'false', hidden: true },							
                                { header: _text('MN02561'), dataIndex: 'name', sortable:false,
                                    renderer: function(value, metaData, record, rowIndex, colIndex, store) {
                                        //return '<span style=\"margin-left: 25px;\">'+value+'</span>';
                                        return value;
                                    }
                                }
                            ],
                            contextmenu: new Ext.menu.Menu({
                                items: [{
                                    text: _text('MN01106'),//'삭제',
                                    icon: 'led-icons/delete.png',
                                    handler: function(self) {

                                        Ext.Msg.show({
                                            title: _text('MN00024'),//'확인',
                                            //msg: '선택한 검색양식을 삭제 하시겠습니까?',
                                            msg: _text('MSG00172'),//삭제 하시겠습니까?
                                            icon: Ext.Msg.QUESTION,
                                            buttons: Ext.Msg.OKCANCEL,
                                            fn: function(btnid){
                                                if(btnid == 'ok') {									

                                                    ajax('/store/search/custom_search.php', 'DELETE', {id : self.selectedRecord.get('id')}, function(r) {
                                                        self.selectedRecord = null;
                                                        Ext.getCmp('nps_media__custom_search_grid').getStore().reload();
                                                    });			

                                                }
                                            }
                                        });

                                    }
                                }]
                            }),
                            listeners: {
                                afterrender: function(self){
                                    self.getStore().load();
                                },
                                rowclick: function(self, rowIndex, e ){
                                    var rowSelectedIndexBefore = self.rowSelectedIndex;

                                    if(rowSelectedIndexBefore != rowIndex){

                                        /*add selected and add short cut search filter*/
                                        var v_selection = self.getSelectionModel().getSelected();
                                        
                                        var content_tab = Ext.getCmp('tab_warp');
                                        var active_tab = content_tab.getActiveTab();
                                        var params = content_tab.mediaBeforeParam;
                                        var filters = v_selection.get("filters");
                                        params.filters = Ext.encode(filters);

                                        /*빠른검색 클릭시 Tag 검색은 초기화 2017.12.21 Alex*/
                                        var tag_search = Ext.getCmp('tag_search');
                                        tag_search.getSelectionModel().clearSelections();
                                        tag_search.rowSelectedIndex = -1;

                                        if (params && params.hasOwnProperty("tag_category_id")){
                                            delete params.tag_category_id;
                                        }
                                        /*빠른검색 클릭시 Tag 검색은 초기화 종료*/

                                        params.start = 0;//빠른검색시 페이징 초기화
                                        active_tab.get(0).reload(params);

                                        self.rowSelectedIndex = rowIndex;

                                        // 카테고리 선택
                                        var categoryTree = Ext.getCmp('menu-tree');	
                                        if(!Ext.isEmpty(filters.category_path)) {
                                            categoryTree.selectPath(filters.category_path);
                                        } else if(filters.category_path != content_tab.mediaBeforeParam.filter_value){
                                            categoryTree.selectPath(content_tab.mediaBeforeParam.filter_value);	
                                        }								
                                        

                                    } else {

                                        /*remove selected and remove tag filter*/
                                        self.getSelectionModel().clearSelections();
                                        var content_tab = Ext.getCmp('tab_warp');
                                        var active_tab = content_tab.getActiveTab();
                                        var params = content_tab.mediaBeforeParam;

                                        /*빠른검색 클릭시 Tag 검색은 초기화 2017.12.21 Alex*/
                                        var tag_search = Ext.getCmp('tag_search');
                                        tag_search.getSelectionModel().clearSelections();
                                        tag_search.rowSelectedIndex = -1;

                                        if (params && params.hasOwnProperty("tag_category_id")){
                                            delete params.tag_category_id;
                                        }
                                        /*빠른검색 클릭시 Tag 검색은 초기화 종료*/

                                        var filters = Ext.decode(params.filters);
                                        if (params && params.hasOwnProperty("filters")){
                                            delete params.filters;
                                            active_tab.get(0).reload(params);
                                        }
                                        self.rowSelectedIndex = -1;

                                        if(filters.category_path != content_tab.mediaBeforeParam.filter_value){
                                            var categoryTree = Ext.getCmp('menu-tree');
                                            categoryTree.selectPath(content_tab.mediaBeforeParam.filter_value);
                                        }									
                                    } 
                                },
                                rowcontextmenu: function(self, rowIndex, e) {
                                    
                                    e.stopEvent();
                                    
                                    if(rowIndex < 0)
                                        return;

                                    var record = self.store.getAt(rowIndex);	
                                    // 사용자 아이디가 ?common은 시스템 항목으로 삭제하면 안됨.
                                    if(record.get('user_id') == '?common')							
                                        return;

                                    self.contextmenu.get(0).selectedRecord = record;
                                    self.contextmenu.showAt(e.getXY());								
                                }
                            }
                        }]
                    },{
                        /*Tags*/
                        xtype: 'panel',
                        title: '<span class="user_span"><span class="icon_title"><i class="fa fa-tags"></i></span><span class="user_title" style="color: #ffffff">'+_text('MN02559')+'</span></span><span title="'+_text('MN02221')+'" class="icon_title3" onclick="tag_management_windown()"><i class="fa fa-cog"></i></span>',
                        id: 'west_menu_item_media_tags',
                        cls: 'west-menu-item-media',
                        anchor: '95% 25%',
                        split: true,
                        collapsible: true,
                        titleCollapse: true,
                        hideCollapseTool: true,
                        layout:'fit',
                        items: [{
                            xtype: 'grid',
                            id: 'tag_search',
                            cls: 'proxima_grid_tag',
                            stripeRows: false,
                            hideHeaders: true,
                            rowSelectedIndex: -1,
                            layout: 'fit',
                            sm: new Ext.grid.RowSelectionModel({singleSelect:true}),
                            store: new Ext.data.JsonStore({
                                url: '/store/tag/tag_action.php',
                                root: 'data',
                                baseParams: {
                                    action: 'listing'
                                },
                                fields: ['tag_category_id','tag_category_title', 'tag_category_color', 'show_order']
                            }),
                            viewConfig: {
                                loadMask: true,
                                forceFit: false
                            },
                            columns: [
                                { header: '', dataIndex: 'tag_category_id', sortable:'false', hidden: true },
                                {
                                    header: '',
                                    dataIndex: 'tag_category_color',
                                    sortable:false,
                                    width: 40,
                                    renderer: function(value, metaData, record, rowIndex, colIndex, store) {
                                        return '<i class="fa fa-circle" style=\"width: 10px;height: 10px;font-size: 18px;margin-left: 15px;color:'+value+';\"></i>';
                                    }
                                },
                                {
                                    header: _text('MN02561'),
                                    dataIndex: 'tag_category_title',
                                    width: 120,
                                    sortable:false,
                                    renderer: function(value, metaData, record, rowIndex, colIndex, store) {
                                        //return '<span style=\"font-weight:bold ;color:'+record.data.tag_category_color+';\">'+value+'</span>';
                                        return value;
                                    }
                                }
                            ],
                            listeners: {
                                afterrender: function(self){
                                    self.getStore().load();
                                },
                                rowclick: function(self, rowIndex, e ){
                                    var rowSelectedIndexBefore = self.rowSelectedIndex;
                                    if(rowSelectedIndexBefore != rowIndex){
                                        /*add selected and add tag filter*/
                                        var v_selection = self.getSelectionModel().getSelected();
                                        var tag_id = v_selection.get("tag_category_id");
                                        
                                        var content_tab = Ext.getCmp('tab_warp');
                                        var active_tab = content_tab.getActiveTab();
                                        var params = content_tab.mediaBeforeParam;

                                        /*Tag 검색 클릭시 빠른 검색은 초기화 2017.12.21 Alex*/
                                        var customSearchGrid = Ext.getCmp('nps_media__custom_search_grid');
                                        customSearchGrid.getSelectionModel().clearSelections();
                                        customSearchGrid.rowSelectedIndex = -1;

                                        if (params && params.hasOwnProperty("filters")){
                                            delete params.filters;
                                        }
                                        /*Tag 검색 클릭시 빠른 검색은 초기화 종료*/

                                        params.tag_category_id = tag_id;

                                        active_tab.get(0).reload(params);

                                        self.rowSelectedIndex = rowIndex;
                                    }else{
                                        /*remove selected and remove tag filter*/
                                        self.getSelectionModel().clearSelections();
                                        var content_tab = Ext.getCmp('tab_warp');
                                        var active_tab = content_tab.getActiveTab();
                                        var params = content_tab.mediaBeforeParam;

                                        /*Tag 검색 클릭시 빠른 검색은 초기화 2017.12.21 Alex*/
                                        var customSearchGrid = Ext.getCmp('nps_media__custom_search_grid');
                                        customSearchGrid.getSelectionModel().clearSelections();
                                        customSearchGrid.rowSelectedIndex = -1;

                                        if (params && params.hasOwnProperty("filters")){
                                            delete params.filters;
                                        }
                                        /*Tag 검색 클릭시 빠른 검색은 초기화 종료*/

                                        if (params && params.hasOwnProperty("tag_category_id")){
                                            delete params.tag_category_id;
                                            active_tab.get(0).reload(params);
                                        }
                                        self.rowSelectedIndex = -1;
                                    }
                                }
                            }
                        }]
                    },{
                        /*Filters*/
                        xtype: 'panel',
                        hidden: true,
                        title: '<span class="user_span"><span class="icon_title"><span class="mdi mdi-filter-outline"></span></span><span class="user_title" style="color:#ffffff">'+_text('MN02535')+'</span></span><span title="'+_text('MN02221')+'" class="icon_title3" onclick="fn_filter_management()"><i class="fa fa-cog"></i></span>',
                        id: 'west_menu_item_media_filters',
                        cls: 'west-menu-item-media',
                        collapsible: true,
                        titleCollapse: true,
                        hideCollapseTool: true,
                        items: [],
                        anchor: '95% 0%',
                        layout: 'fit',
                        listeners:{
                            afterrender: function(self){
                                self.fn_setFiltersUI(self);
                            }
                        },
                        fn_setFiltersUI: function(av_panel){

                            av_panel.removeAll();

                            Ext.Ajax.request({
                                url: '/store/user_filters/user_filter_action.php',
                                params: {
                                    action: "get_list_using"
                                },
                                success: function(conn, response, options, eOpts) {
                                    var responseText = Ext.decode(conn.responseText);
                                    var listFilters = responseText.data;
                                    
                                    Ext.each(listFilters, function(filter) {
                                        if(filter.use_yn == 'Y'){
                                            var filter_code = filter.code;
                                            var title = filter.title;

                                            var minus_button = {
                                                xtype: 'button',
                                                cls: 'proxima_btn_customize proxima_btn_customize_new filter_minus_btn_item',
                                                text: '-',
                                                handler: function(self){
                                                    var filter_id = self.filter_id;
                                                    var arr_filter_id = [];
                                                    arr_filter_id.push(filter_id);

                                                    Ext.Ajax.request({
                                                        url: '/store/user_filters/user_filter_action.php',
                                                        params: {
                                                            action: "remove_filter",
                                                            arr_filter_id: Ext.encode(arr_filter_id)
                                                        },
                                                        success: function(conn, response, options, eOpts) {
                                                            var filter_fieldset_item = Ext.getCmp(self.filter_fieldset_item);
                                                            self.hide();
                                                            filter_fieldset_item.hide();
                                                            av_panel.fn_removeFilterContentCondition(self.filter_type);
                                                        }
                                                    });
                                                }
                                            };
            
                                            if(filter_code == 'video_codec'){
                                                minus_button.filter_id = filter.id;
                                                minus_button.filter_fieldset_item = 'filter_fieldset_item_video_codec';
                                                minus_button.filter_type = 'video_codec_filter';

                                                var video_codec = {
                                                    xtype:'fieldset',
                                                    title: title,
                                                    id: 'filter_fieldset_item_video_codec',
                                                    cls: 'filter_fieldset_item',
                                                    width: 240,
                                                    //collapsible: true,
                                                    autoHeight:true,
                                                    items: [{
                                                                xtype: 'checkboxgroup',
                                                                id: 'video_codec_radio',
                                                                style: 'padding-left:5px !important;',
                                                                vertical: true,
                                                                columns: 1,
                                                                items: [
                                                                    {
                                                                        xtype: 'checkbox',
                                                                        cls: 'newCheckboxStyle',
                                                                        boxLabel: 'vc1',
                                                                        name: 'video_codec_value',
                                                                        inputValue: 'vc1',
                                                                    },
                                                                    {
                                                                        xtype: 'checkbox',
                                                                        cls: 'newCheckboxStyle',
                                                                        boxLabel: 'mpeg2video',
                                                                        name: 'video_codec_value',
                                                                        inputValue: 'mpeg2video'
                                                                    },
                                                                    {
                                                                        xtype: 'checkbox',
                                                                        cls: 'newCheckboxStyle',
                                                                        boxLabel: 'h264',
                                                                        name: 'video_codec_value',
                                                                        inputValue: 'h264'
                                                                    },
                                                                    {
                                                                        xtype: 'checkbox',
                                                                        cls: 'newCheckboxStyle',
                                                                        boxLabel: 'dvvideo',
                                                                        name: 'video_codec_value',
                                                                        inputValue: 'dvvideo'
                                                                    }
                                                                ],
                                                                listeners: {
                                                                    change: function( self, checked ){
                                                                        var v_value = self.getValue();
                                                                        var content_tab = Ext.getCmp('tab_warp');
                                                                        var active_tab = content_tab.getActiveTab();
                                                                        var params = content_tab.mediaBeforeParam;

                                                                        if(v_value.length>0){
                                                                            var arr_codec_value = [];
                                                                            v_value.forEach(function(val) {
                                                                                arr_codec_value.push(val.inputValue);
                                                                            });

                                                                            params.video_codec_filter = Ext.encode(arr_codec_value);
                                                                            active_tab.get(0).reload(params);
                                                                        }else{
                                                                            if (params && params.hasOwnProperty("video_codec_filter")){
                                                                                delete params.video_codec_filter;
                                                                                active_tab.get(0).reload(params);
                                                                            }
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                    ]
                                                };
                                                
                                                av_panel.add([minus_button, video_codec]);
                                                av_panel.doLayout();
                                            }else if(filter_code == 'audio_codec'){

                                                minus_button.filter_id = filter.id;
                                                minus_button.filter_fieldset_item = 'filter_fieldset_item_audio_codec';
                                                minus_button.filter_type = 'audio_codec_filter';

                                                var audio_codec = {
                                                    xtype:'fieldset',
                                                    title: title,
                                                    id: 'filter_fieldset_item_audio_codec',
                                                    cls: 'filter_fieldset_item',
                                                    width: 240,
                                                    //collapsible: true,
                                                    autoHeight:true,
                                                    items: [{
                                                                xtype: 'checkboxgroup',
                                                                id: 'audio_codec_radio',
                                                                style: 'padding-left:5px !important;',
                                                                vertical: true,
                                                                columns: 1,
                                                                items: [
                                                                    {
                                                                        xtype: 'checkbox',
                                                                        cls: 'newCheckboxStyle',
                                                                        boxLabel: 'pcm24le',
                                                                        name: 'audio_codec_value',
                                                                        inputValue: 'pcm_24le',
                                                                    },
                                                                    {
                                                                        xtype: 'checkbox',
                                                                        cls: 'newCheckboxStyle',
                                                                        boxLabel: 'pcm16le',
                                                                        name: 'audio_codec_value',
                                                                        inputValue: 'pcm_16le'
                                                                    },
                                                                    {
                                                                        xtype: 'checkbox',
                                                                        cls: 'newCheckboxStyle',
                                                                        boxLabel: 'wma',
                                                                        name: 'audio_codec_value',
                                                                        inputValue: 'wma'
                                                                    },
                                                                    {
                                                                        xtype: 'checkbox',
                                                                        cls: 'newCheckboxStyle',
                                                                        boxLabel: 'mp3',
                                                                        name: 'audio_codec_value',
                                                                        inputValue: 'mp3'
                                                                    },
                                                                    {
                                                                        xtype: 'checkbox',
                                                                        cls: 'newCheckboxStyle',
                                                                        boxLabel: 'aac',
                                                                        name: 'audio_codec_value',
                                                                        inputValue: 'aac'
                                                                    }
                                                                ],
                                                                listeners: {
                                                                    change: function( self, checked ){
                                                                        var v_value = self.getValue();
                                                                        var content_tab = Ext.getCmp('tab_warp');
                                                                        var active_tab = content_tab.getActiveTab();
                                                                        var params = content_tab.mediaBeforeParam;

                                                                        if(v_value.length>0){
                                                                            var arr_codec_value = [];
                                                                            v_value.forEach(function(val) {
                                                                                arr_codec_value.push(val.inputValue);
                                                                            });

                                                                            params.audio_codec_filter = Ext.encode(arr_codec_value);
                                                                            active_tab.get(0).reload(params);
                                                                        }else{
                                                                            if (params && params.hasOwnProperty("audio_codec_filter")){
                                                                                delete params.audio_codec_filter;
                                                                                active_tab.get(0).reload(params);
                                                                            }
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                    ]
                                                };
                                                
                                                av_panel.add([minus_button, audio_codec]);
                                                av_panel.doLayout();

                                            }else if(filter_code == 'resolution'){
                                                minus_button.filter_id = filter.id;
                                                minus_button.filter_fieldset_item = 'filter_fieldset_item_resolution';
                                                minus_button.filter_type = 'resolution_filter';

                                                var resolution = {
                                                    xtype:'fieldset',
                                                    title: title,
                                                    id: 'filter_fieldset_item_resolution',
                                                    cls: 'filter_fieldset_item',
                                                    width: 240,
                                                    //collapsible: true,
                                                    autoHeight:true,
                                                    items: [{
                                                            xtype: 'checkboxgroup',
                                                            id: 'resolution_radio',
                                                            style: 'padding-left:5px !important;',
                                                            vertical: true,
                                                            columns: 1,
                                                            items: [
                                                                {
                                                                    xtype: 'checkbox',
                                                                    cls: 'newCheckboxStyle',
                                                                    boxLabel: '352x242p',
                                                                    name: 'resolution_value',
                                                                    inputValue: '352x242',
                                                                },
                                                                {
                                                                    xtype: 'checkbox',
                                                                    cls: 'newCheckboxStyle',
                                                                    boxLabel: '480x272p',
                                                                    name: 'resolution_value',
                                                                    inputValue: '480x272',
                                                                },
                                                                {
                                                                    xtype: 'checkbox',
                                                                    cls: 'newCheckboxStyle',
                                                                    boxLabel: '640x360p',
                                                                    name: 'resolution_value',
                                                                    inputValue: '640x360',
                                                                },
                                                                {
                                                                    xtype: 'checkbox',
                                                                    cls: 'newCheckboxStyle',
                                                                    boxLabel: '720x486i tff',
                                                                    name: 'resolution_value',
                                                                    inputValue: '720x486',
                                                                },
                                                                {
                                                                    xtype: 'checkbox',
                                                                    cls: 'newCheckboxStyle',
                                                                    boxLabel: '768x512p',
                                                                    name: 'resolution_value',
                                                                    inputValue: '768x512',
                                                                },
                                                                {
                                                                    xtype: 'checkbox',
                                                                    cls: 'newCheckboxStyle',
                                                                    boxLabel: '720x488i tff',
                                                                    name: 'resolution_value',
                                                                    inputValue: '720x488',
                                                                },
                                                                {
                                                                    xtype: 'checkbox',
                                                                    cls: 'newCheckboxStyle',
                                                                    boxLabel: '1280x532p',
                                                                    name: 'resolution_value',
                                                                    inputValue: '1280x532',
                                                                },
                                                                {
                                                                    xtype: 'checkbox',
                                                                    cls: 'newCheckboxStyle',
                                                                    boxLabel: '1280x720p',
                                                                    name: 'resolution_value',
                                                                    inputValue: '1280x720',
                                                                },
                                                                {
                                                                    xtype: 'checkbox',
                                                                    cls: 'newCheckboxStyle',
                                                                    boxLabel: '7680x4320',
                                                                    name: 'resolution_value',
                                                                    inputValue: '7680x4320',
                                                                },
                                                                {
                                                                    xtype: 'checkbox',
                                                                    cls: 'newCheckboxStyle',
                                                                    boxLabel: '3840x2160p',
                                                                    name: 'resolution_value',
                                                                    inputValue: '3840x2160'
                                                                },
                                                                {
                                                                    xtype: 'checkbox',
                                                                    cls: 'newCheckboxStyle',
                                                                    boxLabel: '1920x1080i tff',
                                                                    name: 'resolution_value',
                                                                    inputValue: '1920x1080'
                                                                },
                                                                {
                                                                    xtype: 'checkbox',
                                                                    cls: 'newCheckboxStyle',
                                                                    boxLabel: '1920x816p',
                                                                    name: 'resolution_value',
                                                                    inputValue: '1920x816'
                                                                },
                                                                {
                                                                    xtype: 'checkbox',
                                                                    cls: 'newCheckboxStyle',
                                                                    boxLabel: '1134x994p',
                                                                    name: 'resolution_value',
                                                                    inputValue: '1134x994'
                                                                }
                                                            ],
                                                            listeners: {
                                                                change: function( self, checked ){
                                                                    var v_value = self.getValue();
                                                                    var content_tab = Ext.getCmp('tab_warp');
                                                                    var active_tab = content_tab.getActiveTab();
                                                                    var params = content_tab.mediaBeforeParam;

                                                                    if(v_value.length>0){
                                                                        var arr_codec_value = [];
                                                                        v_value.forEach(function(val) {
                                                                            arr_codec_value.push(val.inputValue);
                                                                        });

                                                                        params.resolution_filter = Ext.encode(arr_codec_value);
                                                                        active_tab.get(0).reload(params);
                                                                    }else{
                                                                        if (params && params.hasOwnProperty("resolution_filter")){
                                                                            delete params.resolution_filter;
                                                                            active_tab.get(0).reload(params);
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    ]
                                                };
                                                av_panel.add([minus_button, resolution]);
                                                av_panel.doLayout();
                
                                            }else if(filter_code == 'frame_rate'){
                                                minus_button.filter_id = filter.id;
                                                minus_button.filter_fieldset_item = 'filter_fieldset_item_frame_rate';
                                                minus_button.filter_type = 'frame_rate_filter';

                                                var frame_rate = {
                                                    xtype:'fieldset',
                                                    title: title,
                                                    id: 'filter_fieldset_item_frame_rate',
                                                    cls: 'filter_fieldset_item',
                                                    width: 240,
                                                    //collapsible: true,
                                                    autoHeight:true,
                                                    items: [{
                                                                xtype: 'checkboxgroup',
                                                                id: 'frame_rate_radio',
                                                                style: 'padding-left:5px !important;',
                                                                vertical: true,
                                                                columns: 1,
                                                                items: [
                                                                    {
                                                                        xtype: 'checkbox',
                                                                        cls: 'newCheckboxStyle',
                                                                        boxLabel: '24',
                                                                        name: 'frame_rate_value',
                                                                        inputValue: '24',
                                                                    },
                                                                    {
                                                                        xtype: 'checkbox',
                                                                        cls: 'newCheckboxStyle',
                                                                        boxLabel: '25',
                                                                        name: 'frame_rate_value',
                                                                        inputValue: '25'
                                                                    },
                                                                    {
                                                                        xtype: 'checkbox',
                                                                        cls: 'newCheckboxStyle',
                                                                        boxLabel: '29.97',
                                                                        name: 'frame_rate_value',
                                                                        inputValue: '29.97'
                                                                    },
                                                                    {
                                                                        xtype: 'checkbox',
                                                                        cls: 'newCheckboxStyle',
                                                                        boxLabel: '30',
                                                                        name: 'frame_rate_value',
                                                                        inputValue: '30'
                                                                    }
                                                                ],
                                                                listeners: {
                                                                    change: function( self, checked ){
                                                                        var v_value = self.getValue();
                                                                        var content_tab = Ext.getCmp('tab_warp');
                                                                        var active_tab = content_tab.getActiveTab();
                                                                        var params = content_tab.mediaBeforeParam;

                                                                        if(v_value.length>0){
                                                                            var arr_codec_value = [];
                                                                            v_value.forEach(function(val) {
                                                                                arr_codec_value.push(val.inputValue);
                                                                            });

                                                                            params.frame_rate_filter = Ext.encode(arr_codec_value);
                                                                            active_tab.get(0).reload(params);
                                                                        }else{
                                                                            if (params && params.hasOwnProperty("frame_rate_filter")){
                                                                                delete params.frame_rate_filter;
                                                                                active_tab.get(0).reload(params);
                                                                            }
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                    ]
                                                };
                                                av_panel.add([minus_button, frame_rate]);
                                                av_panel.doLayout();

                                            }else if(filter_code == 'archive'){
                                                minus_button.filter_id = filter.id;
                                                minus_button.filter_fieldset_item = 'filter_fieldset_item_archive';
                                                minus_button.filter_type = 'archive_combo';

                                                var archive = {
                                                    xtype:'fieldset',
                                                    title: title,
                                                    id: 'filter_fieldset_item_archive',
                                                    cls: 'filter_fieldset_item',
                                                    width: 240,
                                                    //collapsible: true,
                                                    autoHeight:true,
                                                    items: [{
                                                                xtype: 'checkboxgroup',
                                                                id: 'archive_radio',
                                                                style: 'padding-left:5px !important;',
                                                                vertical: true,
                                                                columns: 1,
                                                                items: [
                                                                    {
                                                                        xtype: 'checkbox',
                                                                        cls: 'newCheckboxStyle',
                                                                        boxLabel: 'Archived',
                                                                        name: 'archive_value',
                                                                        inputValue: '3'
                                                                    },
                                                                    {
                                                                        xtype: 'checkbox',
                                                                        cls: 'newCheckboxStyle',
                                                                        boxLabel: 'Not Archived',
                                                                        name: 'archive_value',
                                                                        inputValue: '2',
                                                                    }
                                                                    
                                                                ],
                                                                listeners: {
                                                                    change: function( self, checked ){
                                                                        var v_value = self.getValue();
                                                                        var content_tab = Ext.getCmp('tab_warp');
                                                                        var active_tab = content_tab.getActiveTab();
                                                                        var params = content_tab.mediaBeforeParam;

                                                                        if(v_value.length>0){
                                                                            var arr_codec_value = [];
                                                                            v_value.forEach(function(val) {
                                                                                arr_codec_value.push(val.inputValue);
                                                                            });

                                                                            if(arr_codec_value.length == 2){
                                                                                params.archive_combo = '1';
                                                                            }else{
                                                                                params.archive_combo = arr_codec_value[0];
                                                                            }
                                                                            active_tab.get(0).reload(params);
                                                                        }else{
                                                                            if (params && params.hasOwnProperty("archive_combo")){
                                                                                delete params.archive_combo;
                                                                                active_tab.get(0).reload(params);
                                                                            }
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                    ]
                                                };
                                                av_panel.add([minus_button, archive]);
                                                av_panel.doLayout();

                                            }else if(filter_code == 'group_content'){
                                                minus_button.filter_id = filter.id;
                                                minus_button.filter_fieldset_item = 'filter_fieldset_item_group_content';
                                                minus_button.filter_type = 'group_content_filter';

                                                var group_content = {
                                                    xtype:'fieldset',
                                                    title: title,
                                                    id: 'filter_fieldset_item_group_content',
                                                    cls: 'filter_fieldset_item',
                                                    width: 240,
                                                    //collapsible: true,
                                                    autoHeight:true,
                                                    items: [{
                                                                xtype: 'checkboxgroup',
                                                                id: 'group_content_radio',
                                                                style: 'padding-left:5px !important;',
                                                                vertical: true,
                                                                columns: 1,
                                                                items: [
                                                                    {
                                                                        xtype: 'checkbox',
                                                                        cls: 'newCheckboxStyle',
                                                                        boxLabel: 'Group',
                                                                        name: 'group_content_value',
                                                                        inputValue: 'G',
                                                                    },
                                                                    {
                                                                        xtype: 'checkbox',
                                                                        cls: 'newCheckboxStyle',
                                                                        boxLabel: 'Not Group',
                                                                        name: 'group_content_value',
                                                                        inputValue: 'I'
                                                                    }
                                                                ],
                                                                listeners: {
                                                                    change: function( self, checked ){
                                                                        var v_value = self.getValue();
                                                                        var content_tab = Ext.getCmp('tab_warp');
                                                                        var active_tab = content_tab.getActiveTab();
                                                                        var params = content_tab.mediaBeforeParam;

                                                                        if(v_value.length>0){
                                                                            var arr_codec_value = [];
                                                                            v_value.forEach(function(val) {
                                                                                arr_codec_value.push(val.inputValue);
                                                                            });

                                                                            params.group_content_filter = Ext.encode(arr_codec_value);
                                                                            active_tab.get(0).reload(params);
                                                                        }else{
                                                                            if (params && params.hasOwnProperty("group_content_filter")){
                                                                                delete params.group_content_filter;
                                                                                active_tab.get(0).reload(params);
                                                                            }
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                    ]
                                                };
                                                av_panel.add([minus_button, group_content]);
                                                av_panel.doLayout();

                                            }else if(filter_code == 'tags_content'){
                                                minus_button.filter_id = filter.id;
                                                minus_button.filter_fieldset_item = 'filter_fieldset_item_tags_content';
                                                minus_button.filter_type = 'tags_content_filter';

                                                var tags_content = {
                                                    xtype:'fieldset',
                                                    title: title,
                                                    id: 'filter_fieldset_item_tags_content',
                                                    cls: 'filter_fieldset_item',
                                                    width: 240,
                                                    //collapsible: true,
                                                    autoHeight:true,
                                                    items: [{
                                                                xtype: 'checkboxgroup',
                                                                id: 'tags_content_radio',
                                                                style: 'padding-left:5px !important;',
                                                                vertical: true,
                                                                columns: 1,
                                                                items: [
                                                                    {
                                                                        xtype: 'checkbox',
                                                                        cls: 'newCheckboxStyle',
                                                                        boxLabel: 'Tagged',
                                                                        name: 'tags_content_value',
                                                                        inputValue: '2',
                                                                    },
                                                                    {
                                                                        xtype: 'checkbox',
                                                                        cls: 'newCheckboxStyle',
                                                                        boxLabel: 'Not tagged',
                                                                        name: 'tags_content_value',
                                                                        inputValue: '3'
                                                                    }
                                                                ],
                                                                listeners: {
                                                                    change: function( self, checked ){
                                                                        var v_value = self.getValue();
                                                                        var content_tab = Ext.getCmp('tab_warp');
                                                                        var active_tab = content_tab.getActiveTab();
                                                                        var params = content_tab.mediaBeforeParam;

                                                                        if(v_value.length>0){
                                                                            var arr_codec_value = [];
                                                                            v_value.forEach(function(val) {
                                                                                arr_codec_value.push(val.inputValue);
                                                                            });

                                                                            if(arr_codec_value.length == 2){
                                                                                params.tags_content_filter = '1';
                                                                            }else{
                                                                                params.tags_content_filter = arr_codec_value[0];
                                                                            }
                                                                            
                                                                            active_tab.get(0).reload(params);
                                                                        }else{
                                                                            if (params && params.hasOwnProperty("tags_content_filter")){
                                                                                delete params.tags_content_filter;
                                                                                active_tab.get(0).reload(params);
                                                                            }
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                    ]
                                                };
                                                av_panel.add([minus_button, tags_content]);
                                                av_panel.doLayout();

                                            }else if(filter_code == 'sns_share'){
                                                minus_button.filter_id = filter.id;
                                                minus_button.filter_fieldset_item = 'filter_fieldset_item_sns_share';
                                                minus_button.filter_type = 'sns_share_filter';

                                                var sns_share = {
                                                    xtype:'fieldset',
                                                    title: title,
                                                    id: 'filter_fieldset_item_sns_share',
                                                    cls: 'filter_fieldset_item',
                                                    width: 240,
                                                    //collapsible: true,
                                                    autoHeight:true,
                                                    items: [{
                                                                xtype: 'checkboxgroup',
                                                                id: 'sns_share_radio',
                                                                style: 'padding-left:5px !important;',
                                                                vertical: true,
                                                                columns: 1,
                                                                items: [
                                                                    {
                                                                        xtype: 'checkbox',
                                                                        cls: 'newCheckboxStyle',
                                                                        boxLabel: 'Youtube',
                                                                        name: 'sns_share_value',
                                                                        inputValue: "YOUTUBE",
                                                                    },
                                                                    {
                                                                        xtype: 'checkbox',
                                                                        cls: 'newCheckboxStyle',
                                                                        boxLabel: 'Twitter',
                                                                        name: 'sns_share_value',
                                                                        inputValue: "TWITTER"
                                                                    },
                                                                    {
                                                                        xtype: 'checkbox',
                                                                        cls: 'newCheckboxStyle',
                                                                        boxLabel: 'Facebook',
                                                                        name: 'sns_share_value',
                                                                        inputValue: "FACEBOOK"
                                                                    }
                                                                ],
                                                                listeners: {
                                                                    change: function( self, checked ){
                                                                        var v_value = self.getValue();
                                                                        var content_tab = Ext.getCmp('tab_warp');
                                                                        var active_tab = content_tab.getActiveTab();
                                                                        var params = content_tab.mediaBeforeParam;

                                                                        if(v_value.length>0){
                                                                            var arr_codec_value = [];
                                                                            v_value.forEach(function(val) {
                                                                                arr_codec_value.push(val.inputValue);
                                                                            });

                                                                            params.sns_share_filter = Ext.encode(arr_codec_value);
                                                                            
                                                                            active_tab.get(0).reload(params);
                                                                        }else{
                                                                            if (params && params.hasOwnProperty("sns_share_filter")){
                                                                                delete params.sns_share_filter;
                                                                                active_tab.get(0).reload(params);
                                                                            }
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                    ]
                                                };
                                                av_panel.add([minus_button, sns_share]);
                                                av_panel.doLayout();

                                            }else if(filter_code == 'duration'){
                                                minus_button.filter_id = filter.id;
                                                minus_button.filter_fieldset_item = 'filter_fieldset_item_duration';
                                                minus_button.filter_type = 'duration_filter';

                                                var duration = {
                                                    xtype:'fieldset',
                                                    title: title,
                                                    id: 'filter_fieldset_item_duration',
                                                    cls: 'filter_fieldset_item',
                                                    width: 240,
                                                    //collapsible: true,
                                                    autoHeight:true,
                                                    items: [{
                                                                xtype: 'checkboxgroup',
                                                                id: 'duration_radio',
                                                                style: 'padding-left:5px !important;',
                                                                vertical: true,
                                                                columns: 1,
                                                                items: [
                                                                    {
                                                                        xtype: 'checkbox',
                                                                        cls: 'newCheckboxStyle',
                                                                        boxLabel: '< 1min',
                                                                        name: 'duration_value',
                                                                        inputValue: '60',
                                                                    },
                                                                    {
                                                                        xtype: 'checkbox',
                                                                        cls: 'newCheckboxStyle',
                                                                        boxLabel: '< 10min',
                                                                        name: 'duration_value',
                                                                        inputValue: '600'
                                                                    },
                                                                    {
                                                                        xtype: 'checkbox',
                                                                        cls: 'newCheckboxStyle',
                                                                        boxLabel: '< 30min',
                                                                        name: 'duration_value',
                                                                        inputValue: '1800'
                                                                    },
                                                                    {
                                                                        xtype: 'checkbox',
                                                                        cls: 'newCheckboxStyle',
                                                                        boxLabel: '< 1hour',
                                                                        name: 'duration_value',
                                                                        inputValue: '3600'
                                                                    },
                                                                    {
                                                                        xtype: 'checkbox',
                                                                        cls: 'newCheckboxStyle',
                                                                        boxLabel: '> 1hour',
                                                                        name: 'duration_value',
                                                                        inputValue: '3601'
                                                                    }
                                                                ],
                                                                listeners: {
                                                                    change: function( self, checked ){
                                                                        var v_value = self.getValue();
                                                                        var content_tab = Ext.getCmp('tab_warp');
                                                                        var active_tab = content_tab.getActiveTab();
                                                                        var params = content_tab.mediaBeforeParam;

                                                                        if(v_value.length>0){
                                                                            var arr_codec_value = [];
                                                                            v_value.forEach(function(val) {
                                                                                arr_codec_value.push(val.inputValue);
                                                                            });

                                                                            params.duration_filter = Ext.encode(arr_codec_value);
                                                                            active_tab.get(0).reload(params);
                                                                        }else{
                                                                            if (params && params.hasOwnProperty("duration_filter")){
                                                                                delete params.duration_filter;
                                                                                active_tab.get(0).reload(params);
                                                                            }
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                    ]
                                                };

                                                
                                                av_panel.add([minus_button, duration]);
                                                av_panel.doLayout();
                                            }
                                        }
                                    });
                                }
                            });
                        },

                        fn_removeFilterContentCondition: function(av_type){

                            var content_tab = Ext.getCmp('tab_warp');
                            var active_tab = content_tab.getActiveTab();
                            var params = content_tab.mediaBeforeParam;
                            if (params && params.hasOwnProperty(av_type)){
                                delete params[av_type];
                                active_tab.get(0).reload(params);
                            }
                        }
                    }
                ],
                bbar: [
                    '->',
                    {
                        xtype: 'button',
                        id: 'west-menu-media-visible',
                        cls: 'proxima_btn_customize proxima_btn_customize_new proxima_btn_control_tool',
                        width: 30,
                        height: 33,
                        text: '<i class="fa fa-backward" aria-hidden="true" title="'+_text('MN02541')+'"></i>',
                        handler: function(self){
                            var cmpWestMenuMedia = Ext.getCmp('west-menu-media');
                            var isShow = cmpWestMenuMedia.is_show;
                            if(isShow){
                                self.setText('<i class="fa fa-forward" aria-hidden="true" title="'+_text('MN02540')+'"></i>');
                                cmpWestMenuMedia.collapse();
                            }else{
                                self.setText('<i class="fa fa-backward" aria-hidden="true" title="'+_text('MN02541')+'"></i>');
                                cmpWestMenuMedia.expand();
                            }
                        }

                    }
                ],
                listeners : {
                    afterrender : function(self){
                    },
                    collapse : function(self){
                        self.is_show = false;
                        setCategory('collapse');
                        self.setTitle(_text('MN00271')+" | "+_text('MN02284'));
                        Ext.getCmp('west-menu-media-visible').setText('<i class="fa fa-forward" aria-hidden="true" title="'+_text('MN02540')+'"></i>');
                    },
                    beforeexpand : function(self){
                        self.setTitle("");
                    },
                    expand : function(self){
                        self.is_show = true;
                        setCategory('expand');
                        Ext.getCmp('west-menu-media-visible').setText('<i class="fa fa-backward" aria-hidden="true" title="'+_text('MN02541')+'"></i>');
                    },
                    bodyresize: function( p, width, height ){
                        setCategory('category_width', width);
                    },
                }
            },{
                /* //! 센터부분 */
                region: 'center',
                border: false,
                layout: 'border',
                id: 'main-media-content',

                simpleSearchValues: [],
                _simpleSearch: function(){
                    var value = Ext.getCmp('simple_search').getValue();
                    if(!value){
                        //>> Ext.Msg.alert('<?=_text('MN00023')?>', '검색어를 입력해주세요.');
                        Ext.Msg.alert(_text('MN00023'), _text('MSG00007'));
                        return;
                    }

                    var p = Ext.getCmp('tab_warp').get(0);
                    i.store.load({
                        params: {
                            task: 'simple_search',
                            value: value
                        }
                    });
                },
                items:[
                    {
                        id: 'a-search-win',
                        region: 'north',
                        layout: 'fit',
                        frame: true,
                        height: 255,
                        headerAsText : false,
                        hideCollapseTool : true,
                        collapsible: true,
                        collapseMode: 'mini',
                        collapsed: true,
                        forceLayout: false,
                        is_new : 'test',
                        items: [{
                            id: 'form_value',
                            cls: 'cls_form_advanced_search',
                            xtype: 'form',
                            layout: 'form',
                            //bodyStyle:{"background-color":"#eaeaea"},
                            buttonAlign: 'center',
                            padding: 10,
                            itemNumber: 6,
                            border : false,
                            items:[{
                            xtype: 'fieldset',
                            //>>title: '콘텐츠 유형',MN00276
                            //title: _text('MN00276'),
                            border: false,
                            cls: 'fieldset-content-types',
                            items:[{
                                    width: '100%',
                                    id: 'a-search-meta-table',
                                    xtype: 'radiogroup',
                                    hideLabel: true,
                                    test : false,
                                    columns: 12,
                                    columns: [120, 120, 120, 120, 120, 120, 120, 120, 120, 120, 120, 120],
                                    vertical: true,
                                    items: [
                                        <?php
                                        /**
                                            $틀정한 BS_content_id 를 보려고 할 경우
                                        */

                                        $userGroups = $_SESSION['user']['groups'];

                                        if ($show_only_bs_content_id  != 0) {
                                            $query = "
                                                SELECT  U.UD_CONTENT_ID, U.UD_CONTENT_TITLE
                                                FROM		BC_UD_CONTENT U
                                                WHERE   U.UD_CONTENT_ID IN(
                                                                SELECT  G.UD_CONTENT_ID
                                                                FROM		BC_GRANT G
                                                                WHERE   G.UD_CONTENT_ID = U.UD_CONTENT_ID
                                                                AND	 MEMBER_GROUP_ID IN (".join(',', $userGroups).")
                                                                AND	 GRANT_TYPE = 'content_grant'
                                                )
                                                                AND U.BS_CONTENT_ID ={$show_only_bs_content_id}
                                                ORDER BY U.SHOW_ORDER ASC
                                                ";
                                        } else {
                                            $query = "
                                                SELECT  U.UD_CONTENT_ID, U.UD_CONTENT_TITLE
                                                FROM		BC_UD_CONTENT U
                                                WHERE   U.UD_CONTENT_ID IN(
                                                                SELECT  G.UD_CONTENT_ID
                                                                FROM		BC_GRANT G
                                                                WHERE   G.UD_CONTENT_ID = U.UD_CONTENT_ID
                                                                AND	 MEMBER_GROUP_ID IN (".join(',', $userGroups).")
                                                                AND	 GRANT_TYPE = 'content_grant'
                                                )
                                                ORDER BY U.SHOW_ORDER ASC
                                                ";
                                        }
                                                                            
                                        if(empty($userGroups)){
                                            $ud_content_list= [];
                                        }else{
                                            $ud_content_list= $db->queryAll($query);

                                            $ud_list = array();
                                            $i = 0;
                                            foreach ($ud_content_list as $key=>$ud_content) {
                                                //$checked =  $ud_content['ud_content_id'] == $ud_content_id ? ", checked : true " : "";
                                                $checked =  $i == 0 ? ", checked : true " : "";
                                                $i ++;
                                                //@mb_internal_encoding("UTF-8");
                                                array_push($ud_list, "{boxLabel: '".$ud_content['ud_content_title']."', targetTab:'".($i-1)."', id: 'mf_".$ud_content['ud_content_id']."',   name: 'meta_table', inputValue: '".$ud_content['ud_content_id']."'".$checked."}");
                                            }
                                            //$fields .= "{boxLabel: '뉴스', id: 'mf_news', name: 'ud_content_id', inputValue: ".$news_tb_id.", checked: false},";
                                            if ($ud_list > 0) {
                                                echo join(',', $ud_list);
                                            }
                                        }
                                        ?>
                                    ],
                                    listeners: {
                                        render: function (self) {
                                                Ext.getCmp('a-search-field').removeAll();
                                        },
                                        afterrender : function(self){
                                        },
                                        change: function (self, checked) {
                                            var search_grid_tab = Ext.getCmp('tab_warp');
                                            //search_grid_tab.setActiveTab(Ext.getCmp('a-search-meta-table').getValue().getRawValue());

                                            Ext.getCmp('a-search-field').removeAll();
                                            var d=Ext.getCmp('a-search-meta-table').getValue();
                                            var meta_table = Ext.getCmp('form_value').getForm().getValues().meta_table;

                                            Ext.Ajax.request({
                                                url: '/store/search/get_dynamic2.php',
                                                params: {
                                                        meta_table_id: Ext.getCmp('a-search-meta-table').getValue().getRawValue(),
                                                        type: 'container'
                                                },
                                                callback: function(opt, success, response){
                                                    if(success) {
                                                        var result = Ext.decode(response.responseText);
                                                        if(result.success) {
                                                            Ext.getCmp('a-search-field').add({
                                                                xtype: 'tabpanel',
                                                                id: 'a-search-field-tab',
                                                                activeTab: 0,
                                                                border:false
                                                            });
                                                            for(var i=0; i < result.total; i++) {
                                                                var item_tap_id = 'a-search-field-tab-' + result.data[i].container_id;
                                                                var ud_content_id = result.data[i].container_id;

                                                                Ext.getCmp('a-search-field-tab').add({
                                                                        title: result.data[i].name,
                                                                        id: item_tap_id,
                                                                        cls: 'a-search-field-tab-form',
                                                                        ud_content_id: ud_content_id,
                                                                        //bodyStyle:{"background-color":"#eaeaea"},
                                                                        border:false,
                                                                        items: []
                                                                });

                                                                //bbbb
                                                                for (var j=0; j < Ext.getCmp('form_value').itemNumber; j++) {
                                                                        if(j<3){
                                                                            if(j == 0){
                                                                                var make_field =   buildCmp(ud_content_id, result.data[i].container_id, j);
                                                                                make_field.style = 'margin-top:-2px;padding: 3px;';
                                                                                Ext.getCmp(item_tap_id).add(make_field);
                                                                            }else{
                                                                                var make_field =   buildCmp(ud_content_id, result.data[i].container_id, j);
                                                                                Ext.getCmp(item_tap_id).add(make_field);
                                                                            }

                                                                        }
                                                                        if(j == 3){
                                                                            var make_field =   buildCmp(ud_content_id, result.data[i].container_id, j);
                                                                            make_field.style = 'margin-top:-93px; margin-left:580px;padding: 3px;';
                                                                            Ext.getCmp(item_tap_id).add(make_field);
                                                                        }
                                                                        if(j > 3){
                                                                            var make_field =   buildCmp(ud_content_id, result.data[i].container_id, j);
                                                                            make_field.style = 'margin-left:580px;padding: 3px;';
                                                                            Ext.getCmp(item_tap_id).add(make_field);
                                                                        }
                                                                }
                                                            }

                                                            Ext.getCmp('a-search-field').doLayout();

                                                        }
                                                    }
                                                }
                                            });

                                            Ext.getCmp('a-search-field').doLayout();
                                        }
                                    }
                                }]
                            },{
                                xtype: 'fieldset',
                                //title: _text('MN00113'),
                                cls: 'fieldset-items',
                                border: false,
                                //height: 220,
                                items: [{
                                        id: 'a-search-field',
                                        items: [],
                                        listeners: {
                                                beforeremove: function(self, cmp){
                                                },
                                                add: function (self, cmp, idx) {
                                                        if (idx != 0) {

                                                        }
                                                }
                                        }
                                }]
                            }],
                            buttons: [{
                                text : '<span style="position:relative;top:1px;"><i class="fa fa-search" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00037'),//검색
                                id: 'a-search-media-button',
                                scale: 'medium',
                                handler: function(b, e){
                                    //상세검색일 시 통합검색 초기화
                                    Ext.getCmp('search_input').setValue('');
                                    var f = Ext.getCmp('a-search-meta-table');

                                    var rs = [];
                                    for (var i=0; i < Ext.getCmp('a-search-field-tab').getActiveTab().items.length; i++) {

                                        var c = Ext.getCmp('a-search-field-tab').getActiveTab().items.items[i].innerCt;
                                        if (Ext.isEmpty(c) || Ext.isEmpty(c.get(0).getValue())) {
                                            continue;
                                        }

                                        var table = '';
                                        var field = '';
                                        var s = c.get(0).getStore();
                                        var order_type = '', search_type = '';
                                        var field_type = s.getAt(s.find('meta_field_id', c.get(0).getValue())).get('type');

                                        if (field_type == 'datefield')  {
                                            var s_dt='', e_dt='';

                                            table = c.get(10) ? c.get(10).getValue() : '';
                                            field = c.get(11) ? c.get(11).getValue() : '';

                                            // search_type = c.get(4) ? c.get(4).getValue() : '';
                                            //order_type = c.get(7) ? c.get(7).getValue() : '';



                                            if (Ext.isEmpty(c.get(1).getValue()))   {
                                                e_dt = c.get(5).getValue().format('Ymd999999');
                                            }
                                            else {
                                                s_dt = c.get(1).getValue().format('Ymd000000');
                                            }

                                            if (Ext.isEmpty(c.get(5).getValue()))   {
                                                s_dt = c.get(1).getValue().format('Ymd000000');
                                            }
                                            else {
                                                e_dt = c.get(5).getValue().format('Ymd999999');
                                            }

                                            if( Ext.isEmpty(s_dt) && Ext.isEmpty(e_dt) ) {
                                                Ext.Msg.alert(_text('MN00023'), '검색기간을 설정해주세요');
                                                return;
                                            }


                                            rs.push({
                                                type: field_type,
                                                meta_field_id: c.get(0).getValue(),
                                                s_dt: s_dt,
                                                e_dt: e_dt,
                                                table: table,
                                                field: field
                                                                            //  search_type: search_type,
                                                                            //    order_type: order_type
                                            });
                                        }else if(field_type == 'checkbox'){
                                            table = c.get(6) ? c.get(6).getValue() : '';
                                            field = c.get(7) ? c.get(7).getValue() : '';

                                            // search_type = c.get(2) ? c.get(2).getValue() : '';
                                            // order_type = c.get(3) ? c.get(3).getValue() : '';

                                            if( !c.get(1).getValue() && !c.get(2).getValue()) {
                                                    Ext.Msg.alert(_text('MN00023'), '검색어를 입력해주세요');
                                                    return;
                                            }

                                            var value_of_checkbox;

                                            if(c.get(1).getValue()){
                                                value_of_checkbox = c.get(1).inputValue;
                                            }
                                            if(c.get(2).getValue()){
                                                value_of_checkbox = c.get(2).inputValue;
                                            }

                                            rs.push({
                                                type: field_type,
                                                meta_field_id: c.get(0).getValue(),
                                                value: value_of_checkbox,
                                                table: table,
                                                field: field
                                                //order_type: order_type
                                            });

                                        }else{
                                            table = c.get(6) ? c.get(6).getValue() : '';
                                            field = c.get(7) ? c.get(7).getValue() : '';

                                            // search_type = c.get(2) ? c.get(2).getValue() : '';
                                            //order_type = c.get(3) ? c.get(3).getValue() : '';
                                            if( Ext.isEmpty(c.get(1).getValue()) ) {
                                                    Ext.Msg.alert(_text('MN00023'), '검색어를 입력해주세요');
                                                    return;
                                            }
                                            rs.push({
                                                type: field_type,
                                                meta_field_id: c.get(0).getValue(),
                                                value: c.get(1).getValue(),
                                                table: table,
                                                field: field
                                                                            // search_type: search_type,
                                                                            //    order_type: order_type
                                            });
                                        }
                                    }

                                    var result = {
                                        meta_table_id: f.getValue().getRawValue(),
                                        fields: rs
                                    };

                                    var tab;

                                    if( Ext.isEmpty(rs) ) {
                                        //Ext.Msg.alert(_text('MN00023'), '검색 항목과 검색어를 입력해주세요');
                                        //MSG00007 검색어를 입력해주세요
                                        Ext.Msg.alert(_text('MN00023'), _text('MSG00007'));
                                        return;
                                    }

                                    var args = {
                                        action: 'a_search',
                                        params: Ext.encode(result),
                                        start: 0
                                    };

                                    var category = Ext.getCmp('menu-tree').getSelectionModel().getSelectedNode();

                                    if(!Ext.isEmpty(category)) {
                                        args.category_full_path =   category.getPath();
                                    }

                                    var target_tab = f.getValue().targetTab;
                                    target_tab = parseInt(target_tab);

                                    //탭이 활성화 되기 전에는 ud_content_id로 탭을 active 할 수 없으므로 탭 순번으로 active 시킴
                                    Ext.getCmp('tab_warp').setActiveTab(target_tab);
                                    //Ext.getCmp('a-search-win').collapse();
                                    (function(){
                                        Ext.getCmp('tab_warp').getActiveTab().reload(args);
                                    }).defer(700);

                                    if(!Ext.isEmpty( Ext.getCmp('advSearchBtn') )){
                                        //Ext.getCmp('advSearchBtn').toggle(true);
                                    }
                                }
                            },{
                                text : '<span style="position:relative;top:1px;"><i class="fa fa-close" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00031'),//'닫기'
                                scale: 'medium',
                                handler: function(b, e){
                                    Ext.getCmp('a-search-win').collapse();
                                    Ext.getCmp('main-media-content').getTopToolbar().setVisible(true);
                                    Ext.getCmp('main-media-content').doLayout();
                                }
                            },{
                                text : '<span style="position:relative;top:1px;"><i class="fa fa-refresh" style="font-size:13px;"></i></span>&nbsp;'+_text('MN02096'),//'닫기'
                                scale: 'medium',

                                id: 'clearFieldConditions',
                                handler: function(b, e){
                                    Ext.getCmp('a-search-win').searchWinReset();
                                    var params = Ext.getCmp('tab_warp').mediaBeforeParam;
                                    if (params && params.hasOwnProperty("params") && params.hasOwnProperty("action")){
                                        delete params.params;
                                        delete params.action;
                                        Ext.getCmp('tab_warp').getActiveTab().reload(
                                            {start: 0}
                                        );
                                    }
                                }
                            }],
                        }],
                        listeners: {
                            beforecollapse: function(self){
                                <?php if ($top_menu_mode == 'S') {?>
                                //document.getElementById('thumb_slider').style.top = '115px';
                                //document.getElementById('thumb_slider').style.top = '100px';
                                //document.getElementById('summary_slider').style.top = '100px';
                                <?php } else {?>
                                    //document.getElementById('thumb_slider').style.top = '141px';
                                    //document.getElementById('thumb_slider').style.top = '125px';
                                    //document.getElementById('summary_slider').style.top = '125px';
                                <?php }?>

                                                        if(Ext.isHideMenu){
                                                            //document.getElementById('thumb_slider').style.top = '92px';
                                                            //document.getElementById('summary_slider').style.top = '92px';
                                                        }
                            },
                            collapse: function(self){

                            },
                            expand: function(self){
                                Ext.getCmp('main-media-content').getTopToolbar().setVisible(false);
                                Ext.getCmp('a-search-field').doLayout();
                                Ext.getCmp('main-media-content').doLayout();

                                <?php if ($top_menu_mode == 'S') {?>
                                //document.getElementById('thumb_slider').style.top = '317px';
                                //document.getElementById('summary_slider').style.top = '317px';
                                <?php } else {?>
                                    //document.getElementById('thumb_slider').style.top = '344px';
                                //document.getElementById('summary_slider').style.top = '344px';
                                <?php }?>

                            },
                            afterrender: function(self){
                                Ext.getCmp('a-search-field').removeAll();
                                var content_tab = Ext.getCmp('tab_warp');
                                var active_tab = content_tab.getActiveTab();
                                var detailSearchRadioGroup = Ext.getCmp('a-search-meta-table');
                                if (detailSearchRadioGroup.items.length == 0)
                                    return;
                                var ud_content_id = Ext.getCmp('a-search-meta-table').items[0].inputValue;

                                Ext.Ajax.request({
                                    url: '/store/search/get_dynamic2.php',
                                    params: {
                                        meta_table_id: ud_content_id,
                                        type: 'container'
                                    },
                                    callback: function(opt, success, response){
                                    if(success) {
                                        var result = Ext.decode(response.responseText);
                                        if(result.success) {
                                        Ext.getCmp('a-search-field').add({
                                            xtype: 'tabpanel',
                                            id: 'a-search-field-tab',
                                            activeTab: 0,
                                            border:false
                                        });
                                        for(var i=0; i < result.total; i++) {
                                            var item_tap_id = 'a-search-field-tab-' + result.data[i].container_id;
                                            Ext.getCmp('a-search-field-tab').add({
                                                title: result.data[i].name,
                                                id: item_tap_id,
                                                cls: 'a-search-field-tab-form',
                                                ud_content_id: result.data[i].container_id,
                                                //bodyStyle:{"background-color":"#eaeaea"},
                                                border:false,
                                                items: []
                                            });
                                            
                                            for (var j=0; j < Ext.getCmp('form_value').itemNumber; j++)
                                            {
                                                if(j<3){
                                                    if(j == 0){
                                                        var make_field =   buildCmp(ud_content_id, result.data[i].container_id, j);
                                                        make_field.style = 'margin-top:-2px;padding: 3px;';
                                                        Ext.getCmp(item_tap_id).add(make_field);
                                                    }else{
                                                        var make_field =   buildCmp(ud_content_id, result.data[i].container_id, j);
                                                        Ext.getCmp(item_tap_id).add(make_field);
                                                    }

                                                }
                                                if(j == 3){
                                                    var make_field =   buildCmp(ud_content_id, result.data[i].container_id, j);
                                                    make_field.style = 'margin-top:-93px; margin-left:580px;padding: 3px;';
                                                    Ext.getCmp(item_tap_id).add(make_field);
                                                }
                                                if(j > 3){
                                                    var make_field =   buildCmp(ud_content_id, result.data[i].container_id, j);
                                                    make_field.style = 'margin-left:580px;padding: 3px;';
                                                    Ext.getCmp(item_tap_id).add(make_field);
                                                }
                                            }
                                        }
                                        Ext.getCmp('a-search-field-tab').setHeight(110);
                                        Ext.getCmp('a-search-field-tab').doLayout();
                                        Ext.getCmp('a-search-field').doLayout();

                                        }
                                    }
                                    }
                                });

                                Ext.getCmp('a-search-field').doLayout();
                            },
                            beforeexpand: function(self){
                            }
                        },
                        searchWinReset: function(){
                            //Ext.getCmp('advance_search_form').searchWinReset();
                            Ext.getCmp('a-search-field').removeAll();
                            var clearFieldConditions_btn = Ext.getCmp('clearFieldConditions');
                            clearFieldConditions_btn.disable();
                            Ext.Ajax.request({
                            url: '/store/search/get_dynamic2.php',
                            params: {
                                meta_table_id: Ext.getCmp('a-search-meta-table').getValue().getRawValue(),
                                type: 'container'
                            },
                            callback: function(opt, success, response){
                                if(success) {
                                var result = Ext.decode(response.responseText);
                                if(result.success) {
                                    var clearFieldConditions_btn = Ext.getCmp('clearFieldConditions');
                                    clearFieldConditions_btn.enable();
                                    Ext.getCmp('a-search-field').add({
                                        xtype: 'tabpanel',
                                        id: 'a-search-field-tab',
                                        activeTab: 0,
                                        border:false
                                    });
                                    for(var i=0; i<result.total; i++) {

                                        var item_tap_id = 'a-search-field-tab-' + result.data[i].container_id;
                                        var ud_content_id = result.data[i].container_id;

                                        Ext.getCmp('a-search-field-tab').add({
                                            title: result.data[i].name,
                                            id: item_tap_id,
                                            cls: 'a-search-field-tab-form',
                                            ud_content_id: ud_content_id,
                                            //bodyStyle:{"background-color":"#eaeaea"},
                                            border:false,
                                            items: []
                                        });

                                        for (var j=0; j < Ext.getCmp('form_value').itemNumber; j++) {
                                            if(j<3){
                                                    if(j == 0){
                                                        var make_field =   buildCmp(ud_content_id, result.data[i].container_id, j);
                                                        make_field.style = 'margin-top:-2px;padding: 3px;';
                                                        Ext.getCmp(item_tap_id).add(make_field);
                                                    }else{
                                                        var make_field =   buildCmp(ud_content_id, result.data[i].container_id, j);
                                                        Ext.getCmp(item_tap_id).add(make_field);
                                                    }

                                                }
                                                if(j == 3){
                                                    var make_field =   buildCmp(ud_content_id, result.data[i].container_id, j);
                                                    make_field.style = 'margin-top:-93px; margin-left:580px;padding: 3px;';
                                                    Ext.getCmp(item_tap_id).add(make_field);
                                                }
                                                if(j > 3){
                                                    var make_field =   buildCmp(ud_content_id, result.data[i].container_id, j);
                                                    make_field.style = 'margin-left:580px;padding: 3px;';
                                                    Ext.getCmp(item_tap_id).add(make_field);
                                                }
                                        }
                                    }

                                    Ext.getCmp('a-search-field').doLayout();
                                }
                                }
                            }
                            });

                            Ext.getCmp('a-search-field').doLayout();
                        }
                    },
                    {
                        xtype: 'tabpanel',
                        id: 'tab_warp',                    
                        cls: 'proxima_media_tabpanel search_media_tabpanel',
                        border:false,
                        bodyStyle: 'border-left: 1px solid #000000',
                        region: 'center',
                        enableTabScroll:true,                    
                        mediaBeforeParam: {},
                        doReload: function(){                        
                            Ext.getCmp('tab_warp').getActiveTab().get(0).getStore().reload();
                        },
                        listeners: {
                            render: function(self){
                                Ext.getCmp('menu-tree').selectRootNode();
                            },
                            afterrender: function(self){
                                // 20170606 canpn add droppable_area for web upload function
                                var element = document.getElementById('tab_warp');
                                fn_make_droppable_area(element, fn_web_upload_callback);
                            },
                            beforetabchange: function(self, n, c){
                                if( !Ext.isEmpty( n ) && !Ext.isEmpty( n.items ) ) {
                                    //새 탭에 대한건 일단 할게 없네
                                }
                                if( !Ext.isEmpty( c ) && !Ext.isEmpty( c.items ) && n.id != c.id ) {
                                    //이전탭의 파라미터 정보 저장. 같은탭 새로고침 일시 제외
                                    self.mediaBeforeParam = c.items.get(0).getStore().lastOptions.params;
                                    self.mediaBeforeRootCategory =  c.root_id;

                                    //탭전환시 상세검색 초기화
                                    if(!Ext.isEmpty(Ext.getCmp('a-search-win'))) {
                                        //Ext.getCmp('a-search-win').searchWinReset();
                                    }
                                }
                                if( !Ext.isEmpty(Ext.getCmp('nav_tab')) ){

                                    var tree, treeLoader, rootNode;
                                    tree = Ext.getCmp('menu-tree');
                                    rootNode = tree.getRootNode();
                                    treeLoader = tree.getLoader();
                                    treeLoader.baseParams.ud_content_id = n.ud_content_id;
                                    rootNode.attributes.read = n.c_read;
                                    rootNode.attributes.add = n.c_add;
                                    rootNode.attributes.edit = n.c_edit;
                                    rootNode.attributes.del = n.c_del;
                                    rootNode.attributes.hidden =  n.c_hidden;

                                    if(rootNode.attributes.read) {
                                        rootNode.disabled = false;
                                    } else {
                                        rootNode.disable = true;
                                    }

                                    if(!Ext.getCmp('nav_tab').useWholeCategory) {
                                        /*기존탭과 뉴탭의 루트 카테고리ID가 같을 경우에는 카테고리 로드 안함 - 2017.12.7 Alex Lim*/
                                        if( c !== undefined && c.root_id != n.root_id ){
                                            if(treeLoader.isLoading()){
                                                treeLoader.abort();
                                            }
                                            treeLoader.load(rootNode);
                                        }
                                    } else {
                                        if(!Ext.isEmpty(n) && !Ext.isEmpty(c) && n.root_id == c.root_id) {
                                        } else {
                                            var category_mapping = <?=$mapping_category?>;
                                            var tree_category_id = tree.getNodeById(category_mapping[n.ud_content_id]['category_id']);
                                            if(tree_category_id) {
                                                tree_category_id.select();
                                            }
                                        }
                                    }
                                }
                            },
                            tabchange: function(self, p) {
                                p.removeAll();
                                if( !Ext.isEmpty(Ariel.menu_context) ) {
                                Ext.destroy(Ariel.menu_context);
                                }
                                if( !Ext.isEmpty(Ariel.tag_menu_list) ) {
                                Ext.destroy(Ariel.tag_menu_list);
                                }
                                var cuesheet_view = Ext.getCmp('cuesheet_view_btn');
                                if (!Ext.isEmpty(cuesheet_view)){
                                    if(p.cuesheet_view == 1) {
                                        cuesheet_view.show();
                                    } else {
                                        cuesheet_view.hide();
                                        var media_cuesheet = Ext.getCmp('media_cuesheet');
                                        var isExpand = media_cuesheet.isExpand;
                                        media_cuesheet.collapse();
                                        media_cuesheet.isExpand = false;
                                    }
                                }
                                var transfer_cuesheet_btn = Ext.getCmp('transfer_cuesheet_btn');
                                if (!Ext.isEmpty(transfer_cuesheet_btn)){
                                    if(p.transfer_cuesheet == 1) {
                                        transfer_cuesheet_btn.show();
                                    } else {
                                        transfer_cuesheet_btn.hide();
                                    }
                                }
                                var ud_content_id = p.ud_content_id;
                                var meta_table_id = p.ud_content_id;
                                var mf_id = 'mf_' + ud_content_id;
                                var bs_content_id = p.bs_content_id;
                                var value = Ext.get('search_input').dom.value;

                                if(Ariel.menu_context) {
                                    Ariel.menu_context.destroy();
                                }
                                Ariel.menu_context = new Ext.menu.Menu({
                                    items: [],
                                    separatorHandler: function(menu){
                                        
                                        
                                        
                                        
                                        var separatorItemId = menu.itemMenuId+'-s';
                                        var separatorHidden = menu.hidden;
                                        var separator = new Ext.menu.Separator({
                                            itemId : separatorItemId,
                                            hidden: separatorHidden
                                        })
                                        this.addItem(separator);
                                        
                                    },
                                    listeners: {
                                        render: function(self){
                                            Ext.Ajax.request({
                                                url:'/store/buildGridMenu.php',
                                                params:{
                                                    ud_content_id: ud_content_id,
                                                    bs_content_id : bs_content_id
                                                },
                                                callback: function(options,success,response){
                                                    if(success){
                                                        Ext.Ajax.request({
                                                            method: 'POST',
                                                            url: '/api/v1/permission/search-by-path',
                                                            callback: function(opts,suc,res){
                                                                var menuItem = Ext.decode(response.responseText);
                                                                var permissions = Ext.decode(res.responseText).data;
                                                                Ext.each(menuItem, function(menu){
                                                                    menu.permissions = permissions;
                                                                    menu.itemMenuId = Ext.id();
                                                                    self.addItem(menu);

                                                                    self.separatorHandler(menu);
                                                                });
                                                                //self.addItem(menuItem);
                                                            }       
                                                        })

                                                    }else{
                                                        Ext.Msg.alert(_text('MN00022'),_text('MSG00024'));
                                                    }
                                                }
                                            });
                                        },
                                        showContextMenuLists: function(self){
                                            var items = self.items.items;
                                            var lastSeparator;
                                            Ext.each(items,function(item){
                                                var xtype = item.getXType();

                                                if(xtype != 'menuseparator'){
                                                    var separator = self.getComponent(item.itemMenuId+'-s');
                                                    if(separator){
                                                        if(item.hidden){
                                                            separator.setVisible(false);
                                                        }else{
                                                            separator.setVisible(true);
                                                            lastSeparator = separator;
                                                        };
                                                    }
                                                }
                                            });
                                            if(!Ext.isEmpty(lastSeparator)){
                                                lastSeparator.setVisible(false);
                                            }
                                        }
                                    }
                                });
                                Ariel.menu_context.showAt('0,0');
                                Ariel.menu_context.setVisible(false);

                                if(Ariel.tag_menu_list) {
                                    Ariel.tag_menu_list.destroy();
                                }
                                Ariel.tag_menu_list = new Ext.menu.Menu({
                                    width: 150,
                                    id: 'tag_menu_list_data',
                                    cls: 'hideMenuIconSpace',
                                    store: new Ext.data.JsonStore({
                                        url: '/store/tag/tag_action.php',
                                        root: 'data',
                                        baseParams: {
                                            action: 'listing',
                                        },

                                        fields: ['tag_category_title','tag_category_id', 'is_checked', 'tag_category_color'],
                                        autoLoad: true,
                                        listeners: {
                                            load: function(store,records,success,operation,opts) {

                                                Ariel.tag_menu_list.add({
                                                    text:'<i class=\"fa fa-eraser\" style=\"font-weight:bold ;color: black;\"></i>'+_text('MN02240'),
                                                            handler: function (btn, e) {
                                                                var selection2 = Ext.getCmp('tab_warp').getActiveTab().get(0).getSelectionModel().getSelections();
                                                                var content_id_array_2 = [];

                                                                Ext.each(selection2, function(r, i, a){
                                                                    content_id_array_2.push({
                                                                        content_id: r.get('content_id')
                                                                    });
                                                                });

                                                                Ext.Ajax.request({
                                                                    url: '/store/tag/tag_action.php',
                                                                    params: {
                                                                        content_id: Ext.encode(content_id_array_2),
                                                                        action: "clear_tag_for_content"
                                                                    },
                                                                    callback: function(opts, success, response) {
                                                                        Ext.getCmp('tab_warp').getActiveTab().get(0).getStore().reload();
                                                                    }
                                                                });
                                                            }
                                                        });

                                                        store.each(function(record) {
                                                            Ariel.tag_menu_list.add({
                                                                text: '<i class=\"fa fa-circle\" style=\"font-weight:bold ;color:'+record.get('tag_category_color')+'\"></i>'+record.get('tag_category_title'),
                                                                handler: function (btn, e) {
                                                                    var selection2 = Ext.getCmp('tab_warp').getActiveTab().get(0).getSelectionModel().getSelections();
                                                                    var content_id_array_2 = [];

                                                                    Ext.each(selection2, function(r, i, a){
                                                                        content_id_array_2.push({
                                                                            content_id: r.get('content_id')
                                                                        });
                                                                    });
                                                                    change_tag_content('change_tag_content', content_id_array_2, record.get('tag_category_id'));
                                                                    Ariel.tag_menu_list.hide();
                                                                }
                                                            })
                                                        });
                                                        Ariel.tag_menu_list.add({
                                                            text:'<i class=\"fa fa-cog\" style=\"font-weight:bold ;color: black;\"></i>'+_text('MN02239'),
                                                            handler: function (btn, e) {
                                                                tag_management_windown();
                                                            }
                                                        });
                                                    }
                                                }
                                    }),
                                    listeners: {

                                    },
                                    menuReset: function(){
                                        Ext.getCmp('tag_menu_list_data').removeAll();
                                        Ext.getCmp('tag_menu_list_data').store.load();
                                    }
                                });
                                Ariel.tag_menu_list.showAt('0,0');
                                Ariel.tag_menu_list.setVisible(false);

                                //기존 tabchange시엔 여러 값을 가져와서 로드하게 됨.
                                //변경된점은 이전탭의 파라미터값을 가지고 로드하도록
                                var args = self.mediaBeforeParam;

                                if( Ext.isEmpty(args) ) {
                                    args = {};
                                }

                                //tabchange 시에는 빠른검색과 태그검색을 초기화 2017.12.21 Alex
                                var customSearchGrid = Ext.getCmp('nps_media__custom_search_grid');
                                customSearchGrid.getSelectionModel().clearSelections();
                                customSearchGrid.rowSelectedIndex = -1;
                                var tagSearchGrid = Ext.getCmp('tag_search');
                                tagSearchGrid.getSelectionModel().clearSelections();
                                tagSearchGrid.rowSelectedIndex = -1;

                                if (args && args.hasOwnProperty("tag_category_id")){
                                    delete args.tag_category_id;
                                }

                                if (args && args.hasOwnProperty("filters")){
                                    delete args.filters;
                                }

                                args.search_q = value;
                                args.ud_content_id = ud_content_id;
                                args.meta_table_id = meta_table_id;
                                //2016-01-11 이전 탭 페이지 정보로 로드 하는 것 방지
                                args.start = 0;
                                if ( ! Ext.isEmpty(value)) {
                                    self.list_type = 'common_search';
                                }
                                self.radio_action = false;

                                if( !Ext.isEmpty(<?=$mapping_category?>)){
                                    var category_mapping = <?=$mapping_category?>;

                                    if(self.mediaBeforeRootCategory != p.root_id) {
                                        args.filter_value = category_mapping[ud_content_id]['category_full_path'];
                                    }
                                }

                                if (Ext.isEmpty(p.get(0)) ) {
                                    if( ud_content_id ){
                                        Ext.Ajax.request({
                                            url: '/pages/browse/content.php',
                                            params: {
                                                ud_content_id: ud_content_id,
                                                bs_content_id: bs_content_id
                                            },
                                            callback: function(opt, success, response){
                                                p.removeAll();
                                                try{
                                                    var r = Ext.decode(response.responseText);
                                                    p.add(r);
                                                    p.doLayout();
                                                    var active_tab_id = Ext.getCmp('tab_warp').getActiveTab().id;

                                                    var last_child = $("div#"+active_tab_id).find("div.cls_slider").find("tr.x-toolbar-right-row").last();						                
                                                    last_child.find("td.x-toolbar-cell").first().addClass("x-hide-display");
                                                    last_child.find("td.x-toolbar-cell:eq(1)").addClass("x-hide-display");

                                                    //연도별 탭 일때 검색 조건 추가
                                                    var treeTab = Ext.getCmp('tree-tab').getActiveTab();
                                                    if(treeTab.getRootNode().text == '연도 별'){
                                                        var smNode = treeTab.getSelectionModel();
                                                        if(smNode.getSelectedNode() != null){
                                                            var selectedNode = smNode.getSelectedNode();
                                                            var nodeStartDate = selectedNode.attributes.startDate;
                                                            var nodeEndDate = selectedNode.attributes.endDate;
                                                            
                                                            var filters = {
                                                                category_start_date : nodeStartDate,
                                                                category_end_date : nodeEndDate
                                                            };
                                                            args.filters = Ext.encode(filters);
                                                        }
                                                    }
                                                    
                                                    //r.reload(args);
                                                    
                                                    doSimpleSearch();
                                                
                                                    
                                                    if(Ext.isAir)
                                                    {
                                                        Ext.getCmp('tab_warp').doReload.defer(2000);
                                                    }
                                                }catch (e){
                                                    console.log('Error get content : ', e);
                                                }
                                            }
                                        });
                                    }

                                } else {
                                    Ext.Ajax.request({
                                        url: '/pages/browse/content.php',
                                        params: {
                                            ud_content_id: ud_content_id,
                                            bs_content_id: bs_content_id
                                        },
                                        callback: function(opt, success, response){
                                            var r = Ext.decode(response.responseText);
                                            p.add(r);
                                            p.doLayout();
                                            var active_tab_id = Ext.getCmp('tab_warp').getActiveTab().id;
                                            var last_child = $("div#"+active_tab_id).find("div.cls_slider").find("tr.x-toolbar-right-row").last();						                
                                            last_child.find("td.x-toolbar-cell").first().addClass("x-hide-display");
                                            last_child.find("td.x-toolbar-cell:eq(1)").addClass("x-hide-display");
                                            r.reload(args);
                                        }
                                    });
                                    // var active_tab_id = Ext.getCmp('tab_warp').getActiveTab().id;
                                    // var last_child = $("div#"+active_tab_id).find("div.cls_slider").find("tr.x-toolbar-right-row").last();						                
                                    // last_child.find("td.x-toolbar-cell").first().addClass("x-hide-display");
                                    // last_child.find("td.x-toolbar-cell:eq(1)").addClass("x-hide-display");
                                    // p.get(0).reload(args);
                                    // p.doLayout();//2017-12-28 탭 전환시 페이징툴바가 사라지는 증상이 있어서 추가함
                                }
                                //Ext.getCmp('menu-tree').collapseAll();
                                //Ext.getCmp('menu-tree').getRootNode().expand();
                            }
                        },
                        items: [
                            <?php
                            /**
                                *	특정한 BS_content_id 를 보려고 할 경우
                                */
                                if ($show_only_bs_content_id  != 0) {
                                    $tabs_q = "
                                            SELECT  A.*
                                            FROM	(
                                                        SELECT 	UC.*
                                                        FROM 		BC_UD_CONTENT UC
                                                                        INNER JOIN BC_BS_CONTENT BS ON UC.BS_CONTENT_ID = BS.BS_CONTENT_ID AND BS.BS_CONTENT_ID = {$show_only_bs_content_id}
                                                    ) A
                                            ORDER BY A.SHOW_ORDER
                                        ";
                                    $tabs = $db->queryAll($tabs_q);
                                } else {
                                    $tabs = $db->queryAll("
                                                    SELECT  UC.*
                                                    FROM	BC_UD_CONTENT UC
                                                    ORDER BY UC.SHOW_ORDER
                                                ");
                                }

                                foreach ($tabs as $k => $tab) {

                                    // 권한이 없으면 건너 뛰기
                                    if (!checkAllowUdContentGrant($_SESSION['user']['user_id'], $tab['ud_content_id'], GRANT_READ)) {
                                        continue;
                                    }

                                    $category_grant_array = array(
                                        'read' => 0,
                                        'add' => 0,
                                        'edit' => 0,
                                        'del' => 0,
                                        'hidden' => 0
                                    );

                                    $node_category_id = '0';
                                    $category_grant = accessGroupGrant($_SESSION['user']['groups']);
                                    $node_grant_array = set_category_access_grant($node_category_id, $category_grant, $category_grant_array, $tab['ud_content_id']);

                                    $isTabView = false;

                                    if (is_array($category_grant[$tab['ud_content_id']])) {
                                        foreach ($category_grant[$tab['ud_content_id']] as $grant) {
                                            if (checkAllowUdContentGrant($_SESSION['user']['user_id'], $tab['ud_content_id'], GRANT_READ)) {
                                                $isTabView = true;
                                            }
                                            //if ((0 < $grant) && ($grant < 32)) $isTabView = true;
                                        }
                                    }

                                    if (!$isTabView) {
                                        continue;
                                    }
                                    $cuesheet_view = 0;

                                    if ($arr_sys_code['cuesheet_use_yn']['use_yn'] == 'Y') {
                                        if (checkAllowUdContentGrant($user_id, $tab['ud_content_id'], GRANT_VIEW_CUESHEET)) {
                                            $cuesheet_view = 1;
                                        }
                                    }

                                    $transfer_cuesheet = 0;
                                    if ($arr_sys_code['cuesheet_use_yn']['use_yn'] == 'Y') {
                                        if (checkAllowUdContentGrant($user_id, $tab['ud_content_id'], GRANT_CUESHEET)) {
                                            $transfer_cuesheet = 1;
                                        }
                                    }
                                    
                                    $_tabs[] = "{
                                        title: '".$tab['ud_content_title']."',
                                        id: ".$tab['ud_content_id'].",
                                        itemId: 'tab_warp_ud_content_".$tab['ud_content_id']."',
                                        ud_content_id: ".$tab['ud_content_id'].",
                                        bs_content_id: ".$tab['bs_content_id'].",
                                        root_id: ".$map_categories[$tab['ud_content_id']]['category_id'].",
                                        c_read: ".$node_grant_array['read'].",
                                        c_add: ".$node_grant_array['add'].",
                                        c_edit: ".$node_grant_array['edit'].",
                                        c_del: ".$node_grant_array['del'].",
                                        c_hidden: ".$node_grant_array['hidden'].",
                                        cuesheet_view: ".$cuesheet_view.",
                                        transfer_cuesheet: ".$transfer_cuesheet.",
                                        layout: 'fit',

                                        reload: function( args ){

                                            if (this.get(0)){
                                                this.get(0).reload( args );
                                            }
                                        }
                                    }";
                                }

                                //권한이 전혀 없어 탭생성이 안될때 기본 탭 by 이성용
                                if (empty($_tabs)) {
                                    $_tabs[] = "{
                                        title: 'DEFAULT',
                                        id: 0,
                                        ud_content_id: 0,
                                        c_read: 0,
                                        c_add: 0,
                                        c_edit: 0,
                                        c_del: 0,
                                        c_hidden: 0,
                                        layout: 'fit',
                                        cuesheet_view: 0,
                                        transfer_cuesheet: 0,
                                        html: '<h2>권한이 필요합니다<br />관리자에게 문의하십시오</h2>'
                                    }";
                                }

                                echo join(", \n", $_tabs);
                                ?>
                        ]
                    }
                ],
                tbar: new Ext.Container({
                    id:'tbarContainer',
                    layout:'anchor',
                    xtype: 'container',
                    defaults: {
                        anchor: '100%',
                        autoHeight:true
                    },
                    items:[
                        new Ext.Toolbar({
                            items:[
                                {
                                    xtype: 'textfield',
                                    id: 'search_input',
                                    fieldLabel: '검색',
                                    labelAlign: 'right',
                                    labelWidth: 70,
                                    width: 400,
                                    search_array : [],
                                    enableKeyEvents: true,
                                    listeners: {
                                        render: function(self){
                                            var search_input_dom = document.getElementById("search_input");
                                            search_input_dom.placeholder = _text('MSG02121');
                                        },
                                        afterrender: function(self){
                                            if('<?=$searchengine_usable?>' == 'true'){
                                                $( "#search_input" ).autocomplete({
                                                    source : function( request, response ) {
                                                        if(!autocomplete_flag) 
                                                            return;
                                                        
                                                        $.ajax({
                                                            url: '/store/searchengine/getAutoComplete.php',
                                                            data: {
                                                                search_key: $('#search_input').val()
                                                            },
                                                            success: function( data ) {												
                                                                if(Ext.isEmpty(data)) 
                                                                    return;
                                                                var data = Ext.decode(data);
                                                                var jsonData = data.response.docs;
                                                                var rtnArr = [];
                                                                for(var i=0; i < jsonData.length; i++){
                                                                    rtnArr.push(jsonData[i].title);
                                                                }

                                                                response( rtnArr );
                                                            }
                                                        });
                                                    },
                                                    minLength: 1,
                                                    select: function( event, ui ) {	
                                                        self.setValue(ui.item.value);
                                                        doSimpleSearch('media');
                                                        $(".ui-autocomplete").hide();
                                                    },
                                                    focus: function(event) {										
                                                        autocomplete_flag = false;
                                                    }
                                                });
                                            }
                                        },
                                        keydown: function(self, e) {
                                            if (e.getKey() == e.ENTER) {
                                                e.stopEvent();
                                                autocomplete_flag = false;
                                                $(".ui-autocomplete").hide();
                                                doSimpleSearch('media');
                                                // hideArk();
                                            }
                                            else{
                                                autocomplete_flag = true;
                                            }
                                        }
                                    }
                                }, {
                                        xtype: 'button',
                                        id: 'search_btn_media',
                                        cls : 'proxima_btn_customize proxima_btn_customize_new',
                                        text: '<span style="position:relative;" title="'+_text('MN00037')+'"><i class="fa fa-search" style="font-size:13px;"></i></span>',//search
                                        style: {
                                            marginLeft: '5px'
                                        },
                                        listeners: {
                                            click: function(self, e) {
                                                autocomplete_flag = false;
                                                $(".ui-autocomplete").hide();
                                                doSimpleSearch('media');
                                            }
                                        }
                                },{
                                        xtype: 'button',
                                        hidden: true,
                                        cls : 'proxima_btn_customize proxima_btn_customize_new',
                                        text: '<span style="position:relative;" title="'+_text('MN00136')+'"><i class="fa fa-search-plus" style="font-size:13px;"></i></span>',//advance search
                                        id: 'advSearchBtn',
                                        style: {},
                                        listeners: {
                                            click: function(self, e) {
                                                openAdvancePanel();
                                            }
                                        }
                                },{
                                    /*Save custom search*/
                                    hidden: true,
                                    xtype: 'button',
                                    cls : 'proxima_btn_customize proxima_btn_customize_new',
                                    text: '<span style="position:relative;" title="'+'검색 양식 저장'+'"><i class="fa fa-save" style="font-size:13px;"></i></span>',					
                                    style: {},
                                    listeners: {
                                        click: function(self, e) {

                                            Ext.Ajax.request({
                                                url : '/javascript/ext.ux/search/custom-search-window.js',
                                                method: 'GET',
                                                callback: function(opt, success, response){
                                                    if(!success) {
                                                        Ext.Msg.alert(_text('MN00024'), '서버에서 정보를 획득하지 못했습니다.');
                                                        return;
                                                    }
                                                    try{
                                                        Ext.decode(response.responseText);
                                                    }catch (e){
                                                        console.log('Error setCategory : ', e);
                                                        Ext.Msg.alert(_text('MN00024'), e['message']);
                                                    }
                                                }
                                            });

                                            // Ext.Loader.load(['/javascript/ext.ux/search/custom-search-window.js']);
                                        }
                                    }
                                },{
                                    /*UPLOAD MEDIA*/
                                    xtype: 'button',
                                    cls : 'proxima_btn_customize proxima_btn_customize_new',
                                    text: '<span style="position:relative;" title="'+_text('MN02530')+'"><i class="fa fa-upload" style="font-size:13px;"></i></span>',
                                    id: 'uploadMediaBtn',
                                    clicked: false,
                                    style: {},
                                    listeners: {
                                        click: function(self, e) {
                                            if(self.clicked) return;
                                            self.clicked = true;
                                            upload();
                                            self.clicked = false;
                                        }
                                    }					
                                },{
                                    xtype: 'button',
                                    hidden: true,
                                    text: '<span style="position:relative;top:1px;"><i class="fa fa-refresh" style="font-size:13px;"></i></span>&nbsp;'+_text('MN02096'),//'초기화'
                                    style: {
                                        marginLeft: '5px'
                                    },
                                    listeners: {
                                        click: function(self, e) {
                                            Ext.getCmp('search_input').setValue('');
                                            if ( ! Ext.isEmpty(Ext.getCmp('a-search-win'))) {
                                                //Ext.getCmp('a-search-win').searchWinReset();
                                            }
                                            doSimpleSearch('media');
                                        }
                                    }
                                },{
                                    xtype: 'tbspacer',
                                    width: 20
                                },{
                                    xtype: 'button',  
                                    cls : 'proxima_btn_customize proxima_btn_customize_new',
                                    text: '<span style="position:relative;" title="도움말"><i class="fa fa-question-circle" style="font-size:13px;"></i></span>', 
                                    style: {
                                        marginLeft: '5px'
                                    },
                                    handler: function(self, e){
                                        // cms 매뉴얼은 1
                                        var downloadId = 1;
                                        ajax(requestApiPath('/downloads/' + downloadId), 'GET', null, 
                                            function(scope, res) {
                                            var download = res.data;
                                            if (download.url) {
                                                window.open(download.url);
                                            } else {
                                                Ext.Msg.alert(
                                                    _text('MN00022'),
                                                    '파일을 찾을 수 없습니다.'
                                                );
                                            }
                                        });
                                    }
                                },{
                                    xtype: 'checkbox',
                                    id: 'research_media',
                                    hidden: true,
                                    boxLabel: _text('MN00084'),//'결과내 재검색'
                                    style: {
                                        marginLeft: '5px',
                                        marginTop: '0px'
                                    },
                                    listeners: {
                                        check: function(self, checked) {
                                            if(checked) {
                                                Ext.getCmp('search_input').setValue('');
                                            }
                                        }
                                    }
                                }
                                <?php
                                if ($arr_sys_code['cuesheet_use_yn']['use_yn'] == 'Y') {
                                    echo ",'->',{
                                            xtype: 'button',
                                            icon: '/led-icons/doc_film.png',
                                            text: _text('MN02449'),
                                            id: 'cuesheet_view_btn',
                                            listeners: {
                                                click: function(self, e) {
                                                    Ext.getCmp('tab_warp').getActiveTab().get(0).enableDragDrop = true;
                                                    var media_cuesheet = Ext.getCmp('media_cuesheet');
                                                    var isExpand = media_cuesheet.isExpand;
                                                    if(isExpand) {
                                                        media_cuesheet.collapse();
                                                        media_cuesheet.isExpand = false;
                                                    } else {
                                                        media_cuesheet.expand();
                                                        media_cuesheet.isExpand = true;
                                                    }
                                                }
                                            }
                                        }";
                                }
                                ?>
                            ]
                        }),
                        new Ext.Toolbar({
                            //밑에 new Custom.MediaToolbar 로 toolbar2 로 대체
                            hidden:true,
                            //id:'nps_media_search_bottom_toolbar',
                            dock: 'top',
                            items:[{
                                xtype: 'tbtext', 
                                text: '방송형태',
                                style:{
                                    color:'white',
                                    marginLeft: '5px'
                                }
                            },{
                                xtype:'combo',
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
                                name:'broadcastForm',
                                hiddenName:'broadcastForm',
                                editable: false,
                                mode: "local",
                                displayField: "code_itm_nm",
                                //valueField: "code_itm_code",
                                valueField: "code_itm_nm",
                                hiddenValue: "code_itm_nm",
                                typeAhead: true,
                                triggerAction: "all",
                                listeners:{
                                    beforerender:function(self){
                                        self.store=jsonStoreByCode('BRDCST_STLE_SE',self);
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

                            },{
                                xtype: 'tbtext', 
                                text: '소재종류',
                                style:{
                                    color:'white',
                                    marginLeft: '10px'
                                }
                            },{
                                xtype:'combo',
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
                                name:'materialKind',
                                hiddenName:'materialKind',
                                editable: false,
                                mode: "local",
                                displayField: "code_itm_nm",
                                //valueField: "code_itm_code",
                                valueField: "code_itm_nm",
                                hiddenValue: "code_itm_nm",
                                typeAhead: true,
                                triggerAction: "all",
                                listeners:{
                                    beforerender:function(self){
                                        self.store=jsonStoreByCode('MATR_KND',self);
                                    },
                                    afterrender:function(self){
                                        self.resizeEl.setWidth(125);
                                        self.getStore().load({
                                            params:{
                                                is_code:1
                                            }
                                        })
                                    }
                                }

                            },{
                                xtype: 'tbtext', 
                                text: '날짜',
                                style:{
                                    color:'white',
                                    marginLeft: '10px'
                                }
                            },{
                                xtype:'combo',
                                name:'dateCombo',
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
                                width:80,
                                store: new Ext.data.ArrayStore({
                                    fields: ['value','name'],
                                    data: [
                                        ['All','전체'],
                                        [new Date().add(Date.DAY,-1).format('Ymd'+'000000'),'지난 1일'],
                                        [new Date().add(Date.DAY, -7).format('Ymd'+'000000'),'지난 1주'],
                                        [new Date().add(Date.MONTH, -1).format('Ymd'+'000000'),'지난 1개월'],
                                        [new Date().add(Date.YEAR, -1).format('Ymd'+'000000'),'지난 1년']
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
                            },{
                                xtype: 'tbtext', 
                                text: '콘텐츠 상태',
                                style:{
                                    color:'white',
                                    marginLeft: '5px'
                                }
                            },{
                                xtype:'combo',
                                width:80,
                                name:'statusCombo',
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
                                        ['0','대기'],
                                        ['-3','등록중'],
                                        ['2','승인'],
                                        ['-5','반려']
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
                            },{
                                hidden:true,
                                xtype: 'tbtext', 
                                text: '심의 상태',
                                style:{
                                    color:'white',
                                    marginLeft: '5px'
                                }
                            },{
                                hidden:true,
                                xtype:'combo',
                                width:80,
                                name:'reviewStatusCombo',
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
                                        ['3','요청'],
                                        ['4','승인'],
                                        ['5','반려']
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
                            },{
                                xtype: 'tbtext', 
                                text: '아카이브 여부',
                                style:{
                                    color:'white',
                                    marginLeft: '5px'
                                }
                            },{
                                xtype:'combo',
                                width:80,
                                cls: 'black_combobox_trigger',
                                name:'archiveStatusCombo',
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
                            },{
                                xtype: 'button',
                                cls : 'proxima_btn_customize proxima_btn_customize_new',
                                text: '<span style="position:relative;" title="'+_text('MN02262')+'"><i class="fa fa-refresh" style="font-size:13px;"></i></span>',//초기화
                                style: {
                                    marginLeft: '5px'
                                },
                                listeners: {
                                    click: function(self, e) {
                                        var searchBottomToolbar = Ext.getCmp('nps_media_search_bottom_toolbar');
                                        var dateCombo = searchBottomToolbar.find('name', 'dateCombo')[0];
                                        var statusCombo = searchBottomToolbar.find('name', 'statusCombo')[0];
                                        var reviewStatusCombo = searchBottomToolbar.find('name', 'reviewStatusCombo')[0];
                                        var archiveStatusCombo = searchBottomToolbar.find('name', 'archiveStatusCombo')[0];
                                        //방송형태
                                        var broadcastFormCombo = searchBottomToolbar.find('name', 'broadcastForm')[0];
                                        //소재종류
                                        var materialKindCombo = searchBottomToolbar.find('name','materialKind')[0];
                                        materialKindCombo;
                                        
                                        
                                        //broadcastFormCombo.setValue('All');
                                        //materialKindCombo.setValue('All');
                                        //dateCombo.setValue('All');
                                        //statusCombo.setValue('All');
                                        //reviewStatusCombo.setValue('All');
                                        //archiveStatusCombo.setValue('All');
                                        //doSimpleSearch('media');

                                        //코드값네임으로 변경된 동안 All을 '전체 로 바꾸어 놓음'
                                        //수정한 부분 여기,custom.media.Toolbar 의 _visibleControl 메소드,콤보박스 displayField
                                        broadcastFormCombo.setValue('전체');
                                        materialKindCombo.setValue('전체');
                                        dateCombo.setValue('전체');
                                        statusCombo.setValue('전체');
                                        reviewStatusCombo.setValue('전체');
                                        archiveStatusCombo.setValue('전체');
                                    }
                                }
                            },{
                                xtype: 'tbtext', 
                                text: '상세검색',
                                style:{
                                    color:'white',
                                    marginLeft: '5px'
                                }
                            },{
                                xtype:'button',
                                cls : 'proxima_btn_customize proxima_btn_customize_new',
                                text: '<span style="position:relative;" title="'+_text('MN02262')+'"><i class="fa fa-plus" style="font-size:13px;"></i></span>',//초기화
                                style: {
                                    marginLeft: '5px'
                                },
                                handler: function(btn){
                                    var tbarBox = Ext.getCmp('tbarContainer');
                                    var toolbar3 = tbarBox.getComponent('toolbar3');
                                    var toolbar4 = tbarBox.getComponent('toolbar4');

                                    if(toolbar3.isVisible() && toolbar4.isVisible()){
                                        btn.setText('<span style="position:relative;" title="'+_text('MN02262')+'"><i class="fa fa-plus" style="font-size:13px;"></i></span>');
                                        toolbar3.setVisible(false);
                                        toolbar4.setVisible(false);
                                    }else{
                                        btn.setText('<span style="position:relative;" title="'+_text('MN02262')+'"><i class="fa fa-minus" style="font-size:13px;"></i></span>');
                                        toolbar3.setVisible(true);
                                        toolbar4.setVisible(true);
                                    }
                                }
                            }]
                        })
                    ],
                    listeners:{
                        afterrender: function(self){

                            //커스텀 툴바 콤포넌트 추가
                            var components = [
                                '/custom/ktv-nps/javascript/ext.ux/Custom.MediaToolbar.js'
                            ];
                            Ext.Loader.load(components, function(r) {
                                Ext.Ajax.request({
                                method: 'POST',
                                url: '/api/v1/permission/search-by-path',   
                                    callback: function(opts, success, response) {                              
                                        var res = Ext.decode(response.responseText);
                                        if(res.success){

                                            var permissions = res.data;
                                            var toolbar2 = new Custom.MediaToolbar({
                                                //아이디 쓰는건 다 지웠는데 혹시몰라서
                                                id:'nps_media_search_bottom_toolbar',
                                                itemId:'toolbar2',
                                                permissions:permissions,
                                                listeners:{
                                                    beforerender: function(self){
                                                        var toolbarItems = [
                                                            self._makeTbText('방송형태'),
                                                            self._makeCodeCombo('brdcst_stle_se','BRDCST_STLE_SE'),

                                                            self._makeTbText('소재종류'),
                                                            self._makeCodeCombo('matr_knd','MATR_KND'),

                                                            self._makeTbText('날짜'),
                                                            self._dateCombo('created_date'),

                                                            self._makeTbText('콘텐츠 상태'),
                                                            self._makeCustomCombo({
                                                                name:'content_status',
                                                                itemId:'content_status',
                                                                store:new Ext.data.ArrayStore({
                                                                    fields: ['value','name'],
                                                                    data: [
                                                                        ['All','전체'],
                                                                        ['0','대기'],
                                                                        ['-3','등록중'],
                                                                        ['2','승인'],
                                                                        ['-5','반려']
                                                                    ]
                                                                })
                                                            }),

                                                            //심의상태 숨김
                                                            //self._makeTbText('심의 상태'),
                                                            //self._makeYnCombo('content_review_status'),

                                                            self._makeTbText('아카이브 여부'),
                                                            self._makeYnCombo('content_archive_status'),
                                                            
                                                            self._makeButton('초기화','fa fa-refresh',function(){
                                                                var tbarBox = Ext.getCmp('tbarContainer');
                                                                tbarBox.getComponent('toolbar2')._visibleControl();
                                                                tbarBox.getComponent('toolbar3')._visibleControl();
                                                                tbarBox.getComponent('toolbar4')._visibleControl();

                                                                //날짜 검색 데이터 필드가 표출되어있으면 지워주기
                                                                self._removeItem('user_set_start_date');
                                                                self._removeItem('dateBetweenText');
                                                                self._removeItem('user_set_end_date');
                                                            }),
                                                            self._makeButton('상세검색','fa fa-plus',function(btn){
                                                                var tbarBox = Ext.getCmp('tbarContainer');
                                                                var toolbar3 = tbarBox.getComponent('toolbar3');
                                                                var toolbar4 = tbarBox.getComponent('toolbar4');

                                                                var mainMediaContent = Ext.getCmp('main-media-content');
                                                                if(toolbar3.isVisible() && toolbar4.isVisible()){
                                                                    btn.setText('<span style="position:relative;" title="'+'상세검색'+'"><i class="fa fa-plus" style="font-size:13px;"></i></span>');
                                                                    toolbar3.setVisible(false);
                                                                    toolbar4.setVisible(false);
                                                                    mainMediaContent.getEl().setHeight(mainMediaContent.lastSize.height);
                                                                    mainMediaContent.syncSize();
                                                                }else{
                                                                    btn.setText('<span style="position:relative;" title="'+'상세검색'+'"><i class="fa fa-minus" style="font-size:13px;"></i></span>');
                                                                    toolbar3.setVisible(true);
                                                                    toolbar4.setVisible(true);
                                                                    mainMediaContent.getEl().setHeight(mainMediaContent.lastSize.height);
                                                                    mainMediaContent.syncSize();
                                                                }
                                                            })
                                                        ];
                                                        self._addItems(toolbarItems);
                                                    }
                                                },
                                                _dateCombo: function(){
                                                    var _this = this;
                                                    var dateCombostore =  new Ext.data.ArrayStore({
                                                        fields: ['value','name'],
                                                        data: [
                                                            ['All','전체'],
                                                            [new Date().add(Date.DAY,-1).format('Ymd'+'000000'),'지난 1일'],
                                                            [new Date().add(Date.DAY, -7).format('Ymd'+'000000'),'지난 1주'],
                                                            [new Date().add(Date.MONTH, -1).format('Ymd'+'000000'),'지난 1개월'],
                                                            [new Date().add(Date.YEAR, -1).format('Ymd'+'000000'),'지난 1년'],
                                                            ['user_set_created_date','사용자 지정']
                                                        ]
                                                    });
                                                    var dateCombo = this._makeYnCombo('created_date');
                                                    dateCombo.on('select',function(self,record,index){
                                                        var startDateField = _this._makeDateField('user_set_start_date');

                                                        var dateBetweenText = _this._makeTbText('~');
                                                        dateBetweenText.itemId = 'dateBetweenText';

                                                        var endDateField = _this._makeDateField('user_set_end_date');
                                                        endDateField.on('select',function(endDateFieldThis,endDate){
                                                            var startDateFieldValue = _this.getComponent('user_set_start_date').getValue();
                                                            if (startDateFieldValue > endDate) {
                                                                /**
                                                                * 이전 날짜보다 작은 값을 선택 했을 시
                                                                * // 이전날짜 선택시 null 값 입력
                                                                */
                                                                endDateFieldThis.setValue(new Date());
                                                                return Ext.Msg.alert('알림', '시작날짜보다 이전날짜를 선택할 수 없습니다.');
                                                            };
                                                        });
                                                        
                                                        if(record.get('value') == "user_set_created_date"){
                                                            if(Ext.isEmpty(_this._getField('user_set_start_date'))){
                                                                var insertIndex = _this.items.indexOf(self)+1;    
                                                                _this.insert(insertIndex,startDateField);
                                                            }

                                                            if(Ext.isEmpty(_this._getField('dateBetweenText'))){
                                                                insertIndex = insertIndex+1;
                                                                _this.insert(insertIndex,dateBetweenText);
                                                            }

                                                            if(Ext.isEmpty(_this._getField('user_set_end_date'))){
                                                                insertIndex = insertIndex+1;
                                                                _this.insert(insertIndex,endDateField);
                                                            }


                                                            _this.doLayout();
                                                        }else{
                                                            
                                                            //if(!Ext.isEmpty(_this.getComponent('user_set_start_date'))){
                                                            //    _this.remove(_this.getComponent('user_set_start_date'));
                                                            //}
                                                            //if(!Ext.isEmpty(_this.getComponent('dateBetweenText'))){
                                                            //    _this.remove(_this.getComponent('dateBetweenText'));
                                                            //}
                                                            //if(!Ext.isEmpty(_this.getComponent('user_set_end_date'))){
                                                            //    _this.remove(_this.getComponent('user_set_end_date'));
                                                            //}

                                                            _this._removeItem('user_set_start_date');
                                                            _this._removeItem('dateBetweenText');
                                                            _this._removeItem('user_set_end_date');
                                                            _this.doLayout();
                                                        };
                                                    });

                                                    dateCombo.on('change',function(self,newValue, oldValue){

                                                    });
                                                    dateCombo.store = dateCombostore;
                                                    return dateCombo;
                                                }
                                            });

                                            var toolbar3 = new Custom.MediaToolbar({
                                                itemId:'toolbar3',
                                                visible:true,
                                                permissions:permissions,
                                                listeners:{
                                                    beforerender: function(self){
                                                        var toolbarItems = [
                                                            self._makeTbText('프로그램 유형'),
                                                            self._makeCodeCombo('vido_ty_se','VIDO_TY_SE'),

                                                            self._makeTbText('포털 다운로드 가능'),
                                                            self._makeYnCombo('dwld_posbl_at'),

                                                            self._makeTbText('리스토어 여부'),
                                                            self._makeYnCombo('restore_at'),

                                                            self._makeTbText('사용금지 여부'),
                                                            self._makeYnCombo('use_prhibt_at'),

                                                            self._makeCustomTbText({
                                                                text:'콘텐츠 숨김 여부',
                                                                permission_code:['archive','admin']
                                                            }),

                                                            self._makeCustomCombo({
                                                                name:'is_hidden',
                                                                itemId:'is_hidden',
                                                                defaultValue:'N',
                                                                permission_code:['archive','admin']
                                                            }),

                                                            self._makeTbText('해상도'),
                                                            self._makeCustomCombo({
                                                                name:'resolution',
                                                                itemId:'resolution',
                                                                store:new Ext.data.ArrayStore({
                                                                    fields: ['value','name'],
                                                                    data: [
                                                                        ['All','전체'],
                                                                        ['UHD','UHD'],
                                                                        ['HD','HD'],
                                                                        ['SD','SD'],
                                                                        ['MP4','MP4'],
                                                                        ['ETC','ETC']
                                                                    ]
                                                                })
                                                            })
                                                        ];
                                                        self._addItems(toolbarItems);
                                                    }
                                                }
                                            });

                                            var toolbar4 = new Custom.MediaToolbar({
                                                itemId:'toolbar4',
                                                visible:true,
                                                permissions:permissions,
                                                listeners:{
                                                    beforerender: function(self){
                                                        var toolbarItems = [
                                                            self._makeTbText('키워드'),
                                                            self._makeTextField('kwrd'),

                                                            self._makeTbText('호수'),
                                                            self._makeTextField('hono'),

                                                            self._makeTbText('관리번호'),
                                                            self._makeTextField('manage_no'),

                                                            self._makeTbText('회차'),
                                                            self._makeTextField('tme_no'),

                                                            self._makeTbText('제작PD'),
                                                            self._makeTextField('prod_pd_nm')
                                                        ];
                                                        self._addItems(toolbarItems);
                                                    }
                                                }
                                            });

                                            self.add(toolbar2);
                                            self.add(toolbar3);
                                            self.add(toolbar4);
                                            self.doLayout();
                                        }
                                    }
                                });
                            });
                        }
                    }
                })
            }
            <?php
                if (defined('CUSTOM_ROOT')) {
            ?>
            
            <?php
                } elseif ($arr_sys_code['cuesheet_use_yn']['use_yn'] == 'Y') {
            ?>
            ,{
                id: 'media_cuesheet',
                region: 'east',
                layout: {
                    type: 'vbox',
                    align: 'stretch'
                },
                border: false,
                split: true,
                plain: true,
                width: '30%',
                minWidth : 270,
                maxWidth: max_width,
                collapsed : true,
                collapsible: false,
                collapseMode: 'mini',
                hideCollapseTool: true,
                items: [
                    new Ariel.Nps.Cuesheet.List({
                        flex: 1
                    }),
                    new Ariel.Nps.Cuesheet.Detail({
                        flex: 1
                    })
                ],
                listeners: {
                    beforecollapse: function(self){
                        //document.getElementById('thumb_slider').style.right = '20px';
                        //document.getElementById('summary_slider').style.right = '20px';
                    },
                    resize: {
                        fn: function(el) {
                            //var cuesheet_width = Ext.getCmp('media_cuesheet').getWidth() + 20;
                            //document.getElementById('thumb_slider').style.right = cuesheet_width+'px';
                            //document.getElementById('summary_slider').style.right = cuesheet_width+'px';
                        }
                    },
                    expand: function(p){
                        //확장할때 로드되도록 수정
                        //var cuesheet_width = Ext.getCmp('media_cuesheet').getWidth() + 20;
                        //document.getElementById('thumb_slider').style.right = cuesheet_width+'px';
                        //document.getElementById('summary_slider').style.right = cuesheet_width+'px';
                        p.get(0).get(0).getStore().load({
                            params: {
                                broad_sdate: new Date().add(Date.DAY, -7).format('Ymd'),
                                broad_edate: new Date().format('Ymd'),
                                cuesheet_type: 'M',
                                prog_id: 'all',
                                subcontrol_room : 'all'
                            },
                            callback: function(opt, success, response){
                                if(success) {

                                }
                            }
                        });
                    }
                }
            }
            <?php
                }
            ?>
        ];
        Ariel.Nps.Media.superclass.initComponent.call(this);
    }
});
