<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lang.php');

$user_id = $_SESSION['user']['user_id'];

?>

Ariel.Nps.Audio = Ext.extend(Ext.Panel, {
            layout: 'border',
            initComponent: function(config) {
                Ext.apply(this, config || {});
                var that = this;

                this.items=[{
			id: 'west-menu-audio',
			region: 'west',
			layout: 'border',
			border: false,
			split: true,
			plain: true,
                        cls: 'custom-nav-tab',
                        width: 250,
                        items: [
                            new Ariel.nav.AudioMainPanel()
                        ]
                },{
                        region: 'center',
                        border: false,
                        layout: 'border',

                        simpleSearchValues: [],
                        _simpleSearch: function(){
                            var value = Ext.getCmp('simple_search_audio').getValue();
                            if(!value){
                                //>> Ext.Msg.alert('<?=_text('MN00023')?>', '검색어를 입력해주세요.');
                                Ext.Msg.alert(_text('MN00023'), _text('MSG00007'));
                                return;
                            }

                            var p = Ext.getCmp('tab_warp_audio').get(0);
                            i.store.load({
                                params: {
                                    task: 'simple_search',
                                    value: value
                                }
                            });
                        },

                        <?php
                            $active_tab = empty($_GET['active_tab']) ? 0 : $_GET['active_tab'];
                        ?>

                        items:[{
                            xtype: 'tabpanel',
                            id: 'tab_warp_audio',
                            activeTab: <?=$active_tab?>,
                            region: 'center',
                            doReload: function(){
                                Ext.getCmp('tab_warp_audio').getActiveTab().get(0).getStore().reload();
                            },
                            listeners: {
                                render: function(self){
                                    var init_path = Ext.getCmp('menu-tree-audio').getRootNode().getPath();
                                    Ext.getCmp('menu-tree-audio').selectPath( init_path );
                                },
                                beforetabchange: function(self, n, c){
                                    if( !Ext.isEmpty(Ext.getCmp('nav_tab_audio')) ){
                                        var tree, treeLoader, rootNode;
                                        tree = Ext.getCmp('menu-tree-audio');
                                        rootNode = tree.getRootNode();
                                        treeLoader = tree.getLoader();
                                        treeLoader.baseParams.ud_content_id = n.ud_content_id;
                                        rootNode.attributes.read = n.c_read;
                                        rootNode.attributes.add = n.c_add;
                                        rootNode.attributes.edit = n.c_edit;
                                        rootNode.attributes.del = n.c_del;
                                        rootNode.attributes.hidden =  n.c_hidden;

                                        var beforeSelNode = Ext.getCmp('menu-tree-audio').getSelectionModel().getSelectedNode();

                                        if( !Ext.isEmpty(beforeSelNode) ){
                                            rootNode.attributes.beforePath =   beforeSelNode.getPath();
                                        }

                                        if( c !== undefined  ){
                                            if(treeLoader.isLoading()){
                                                treeLoader.abort();
                                            }
                                        }
                                    }
                                },
                                tabchange: function(self, p) {
                                    var ud_content_id = p.ud_content_id;
                                    var mf_id = 'mf_' + ud_content_id;
                                    if(!self.radio_action) {

                                    }

                                    self.radio_action = false;

                                    if( Ext.isEmpty( p.get(0) ) ) {
                                        Ext.Ajax.request({
                                                url: '/pages/browse/content.php',
                                                params: {
                                                        ud_content_id: ud_content_id
                                                },
                                                callback: function(opt, success, response){

                                                        p.removeAll();

                                                        try{
                                                                var r = Ext.decode(response.responseText);
                                                                p.add(r);
                                                                p.doLayout();

                                                                var args = {};


                                                                var value = Ext.get('search_input_audio').dom.value;
                                                                if ( !Ext.isEmpty(value) ){
                                                                        r.reload({
                                                                                meta_table_id: ud_content_id,
                                                                                list_type: 'common_search',
                                                                                search_q: value
                                                                        });

                                                                        return;
                                                                }

                                                                var category = Ext.getCmp('menu-tree-audio').getSelectionModel().getSelectedNode();
                                                                if (!Ext.getCmp('menu-tree-audio').getRootNode().attributes.read)
                                                                {
                                                                        Ext.getCmp('menu-tree-audio').getRootNode().expand( false, true, function(n){

                                                                                var find = false;

                                                                                var find = n.findChild('read', 1);

                                                                                if(Ext.isEmpty(Ext.getCmp('menu-tree-audio').getLoader().baseParams.beforePath))
                                                                                {
                                                                                        if(!Ext.isEmpty(find))
                                                                                        {
                                                                                                find.select();
                                                                                                var args = {};
                                                                                                args.filter_type =	'category';
                                                                                                args.filter_value =	find.getPath();

                                                                                                Ext.getCmp('menu-tree-audio').getLoader().baseParams.beforePath = args.filter_value;
                                                                                                r.reload(args);
                                                                                        }
                                                                                }
                                                                                else
                                                                                {
                                                                                                var args = {};
                                                                                                args.filter_type =	'category';
                                                                                                args.filter_value =	Ext.getCmp('menu-tree-audio').getLoader().baseParams.beforePath;

                                                                                                Ext.getCmp('menu-tree-audio').selectPath(args.filter_value);
                                                                                                Ext.getCmp('menu-tree-audio').getLoader().baseParams.beforePath = args.filter_value;
                                                                                                r.reload(args);
                                                                                }

                                                                        }, this);
                                                                }
                                                                else
                                                                {
                                                                        if(!Ext.isEmpty(category)){
                                                                                args.filter_type =	'category';
                                                                                args.filter_value =	'/0'+category.getPath();
                                                                                r.reload(args);

                                                                        }
                                                                }

                                                                if(Ext.isAir)
                                                                {
                                                                        Ext.getCmp('tab_warp_audio').doReload.defer(2000);
                                                                }


                                                        }catch (e){

                                                        }
                                                }
                                        });

                                    } else {
                                        var args = {};

					var value = Ext.get('search_input_audio').dom.value;
					if ( !Ext.isEmpty(value) ){
                        			p.get(0).reload({
                                			meta_table_id: ud_content_id,
							list_type: 'common_search',
							search_q: value
						});
        					return;
					}

					var category = Ext.getCmp('menu-tree-audio').getSelectionModel().getSelectedNode();

					if (!Ext.getCmp('menu-tree-audio').getRootNode().attributes.read) {
        					Ext.getCmp('menu-tree-audio').getRootNode().expand( false, true, function(n){

                					var find = false;
							var find = n.findChild('read', 1);

							if(Ext.isEmpty(Ext.getCmp('menu-tree-audio').getLoader().baseParams.beforePath)) {
                                                            if(!Ext.isEmpty(find)) {
                                                                find.select();
                                                                var args = {};
                                                                args.filter_type =	'category';
                                                                args.filter_value =	find.getPath();

                                                                Ext.getCmp('menu-tree-audio').getLoader().baseParams.beforePath = args.filter_value;
                                                                p.get(0).reload(args);
                                                            }
                                                        } else {
                                                            var args = {};
                                                            args.filter_type =	'category';
                                                            args.filter_value =	Ext.getCmp('menu-tree-audio').getLoader().baseParams.beforePath;

                                                            Ext.getCmp('menu-tree-audio').selectPath(args.filter_value);

                                                            Ext.getCmp('menu-tree-audio').getLoader().baseParams.beforePath = args.filter_value;
                                                            p.get(0).reload(args);
                                                        }
                                                    }, this);
                                            } else {
                                                if(!Ext.isEmpty(category)){
                                                    args.filter_type =	'category';
                                                    args.filter_value =	category.getPath();
                                                    p.get(0).reload(args);
                                                }
                                            }
                                        }

                                        if( !Ext.isEmpty(Ext.getCmp('menu-tree-audio')) ){

                                        }
                                    }
                                },
                                items: [
                                    <?php
                                        $tabs = $db->queryAll("select * from bc_ud_content uc order by uc.show_order");
                                        foreach ($tabs as $tab){
                                            if( !in_array(  $tab['ud_content_id'], $AUDIO_LIST ) ){
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
                                            $category_grant = categoryGroupGrant($_SESSION['user']['groups']);
                                            $node_grant_array = set_category_grant($node_category_id, $category_grant, $category_grant_array, $tab['ud_content_id'] );
                                            $isTabView = false;

                                            if( is_array($category_grant[$tab['ud_content_id']] ) ) {
                                                foreach($category_grant[$tab['ud_content_id']] as $grant) {
                                                    if( ( 0 < $grant ) && ( $grant < 4 ) ) $isTabView = true;
                                                }
                                            }

                                            if( !checkAllowUdContentGrant($_SESSION['user']['user_id'] ,$tab['ud_content_id'],GRANT_READ ) ) {
								 $isTabView = false;
                                            }

                                            if( !$isTabView ) continue;

                                            $_tabs[] = "{
                                                title: '".$tab['ud_content_title']."',
                                                id: ".$tab['ud_content_id'].",
                                                ud_content_id: ".$tab['ud_content_id'].",
                                                c_read: ".$node_grant_array['read'].",
                                                c_add: ".$node_grant_array['add'].",
                                                c_edit: ".$node_grant_array['edit'].",
                                                c_del: ".$node_grant_array['del'].",
                                                c_hidden: ".$node_grant_array['hidden'].",
                                                layout: 'fit',

                                                reload: function( args ){

                                                    if (this.get(0)){
                                                        this.get(0).reload( args );
                                                    }
                                                }
                                            }";
                                        }

                                        if(empty($_tabs)){//권한이 전혀 없어 탭생성이 안될때 기본 탭 by 이성용
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
                                                        html: '<h2>권한이 필요합니다<br />관리자에게 문의하십시오</h2>'
                                                }";
                                        }


                                        echo join(", \n", $_tabs);
                                        ?>
                                    ]
                }],

                tbar: [{
                        xtype: 'box',
                        autoEl: {
                            tag: 'img',
                            src: '/ext/resources/images/default/etc/search_t_1.png',
                            width: '60px',
                            height: '20px'
                        },
                        style: {
                            marginRight: '5px'
                        }
                },{
                        xtype: 'textfield',
                        id: 'search_input_audio',
                        fieldLabel: '검색',
                        labelAlign: 'right',
                        labelWidth: 70,
			search_array : [],
                        width: 300,
                        enableKeyEvents: true,
                        listeners: {
                            keydown: function(self, e) {
                                if (e.getKey() == e.ENTER) {
                                    e.stopEvent();
                                    doSimpleSearch('audio');
                                }
                            }
                        }
                }, {
                        xtype: 'button',
                        icon: '/led-icons/find.png',
                        text: '검색',
                        style: {
                            marginLeft: '5px'
                        },
                        listeners: {
                            click: function(self, e) {
                                doSimpleSearch('audio');
                            }
                        }
                },{
                        xtype: 'button',
                        icon: '/led-icons/download_sicon.png',
                        text: '상세검색',
                        style: {
                            marginLeft: '5px'
                        },
                        listeners: {
                                click: function(self, e) {
                                        doAdvancedAudioSearch(self);
                                }
                        }
                },{
                        xtype: 'checkbox',
                        id: 'research_audio',
                        boxLabel: '결과내 재검색',
                        style: {
                            marginLeft: '5px',
                            marginTop: '0px'
                        },
                        listeners: {
                            check: function(self, checked) {
                                if(checked) {
                                    Ext.getCmp('search_input_audio').setValue('');
                                }
                            }
                        }
                }]
        },{
            id: 'audio_cuesheet',
            region: 'east',
            layout: {
                type: 'vbox',
                align: 'stretch'
            },
            border: false,
            split: true,
            plain: true,
            width: '30%',
            collapsed : true,
            collapsible: false,
            collapseMode: 'mini',
            hideCollapseTool: true,
            items: [
                 new Ariel.Nps.Cuesheet.AudioList({
                        flex: 1
                 }),
                 new Ariel.Nps.Cuesheet.AudioDetail({
                        flex: 1
                 })
            ]
        }];

        Ariel.Nps.Audio.superclass.initComponent.call(this);

        }
});