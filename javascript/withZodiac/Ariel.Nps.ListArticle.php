Ariel.Nps.ListArticle = Ext.extend(Ext.Panel, {
    layout: 'border',

    initComponent: function(config) {
        Ext.apply(this, config || {});
        var that = this;

        this.items=[{
			id: 'west-content-list',
			region: 'west',
			layout: 'border',
			border: false,
			split: true,
			plain: true,
            cls: 'custom-nav-tab',
            width: 350,
            items: [
                new Ariel.nav.MainPanel()
            ]
        },{
            region: 'center',
            border: false,
            layout: 'border',

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
            items:[{
                xtype: 'tabpanel',
                id: 'tab_warp',
                region: 'center',

    			mediaBeforeParam: {},
    			doReload: function(){
                    Ext.getCmp('tab_warp').getActiveTab().get(0).getStore().reload();
                },
                listeners: {
                    render: function(self){
                        var init_path = Ext.getCmp('menu-tree').getRootNode().getPath();
                        Ext.getCmp('menu-tree').selectPath( init_path );
                    },
                    beforetabchange: function(self, n, c){
    					if( !Ext.isEmpty( n ) && !Ext.isEmpty( n.items ) ) {
    						//새 탭에 대한건 일단 할게 없네
    					}
    					if( !Ext.isEmpty( c ) && !Ext.isEmpty( c.items ) && n.id != c.id ) {
    						//console.log('beforetabchange');
    						//이전탭의 파라미터 정보 저장. 같은탭 새로고침일신 제외
    						self.mediaBeforeParam = c.items.get(0).getStore().lastOptions.params;

                            //탭전환시 상세검색 초기화
                            if(!Ext.isEmpty(Ext.getCmp('a-search-win'))) {
                                Ext.getCmp('a-search-win').searchWinReset();
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

                            var beforeSelNode = Ext.getCmp('menu-tree').getSelectionModel().getSelectedNode();

                            if( !Ext.isEmpty(beforeSelNode) ){
                                rootNode.attributes.beforePath =   beforeSelNode.getPath();
                            }

                            if( c !== undefined  ){
                                if(treeLoader.isLoading()){
                                    treeLoader.abort();
                                }
								//treeLoader.load(rootNode);
                            }
                        }
                    },
                    tabchange: function(self, p) {
                        var ud_content_id = p.ud_content_id;
    					var meta_table_id = p.ud_content_id;
                        var mf_id = 'mf_' + ud_content_id;
    					var value = Ext.get('search_input').dom.value;
                        if(!self.radio_action) {
                        }

    					//기존 tabchange시엔 여러 값을 가져와서 로드하게 됨.
    					//변경된점은 이전탭의 파라미터값을 가지고 로드하도록
    					var args = self.mediaBeforeParam;
    					if( Ext.isEmpty(args) ) {
    						args = {};
    					}

						args.search_q = value;
						args.ud_content_id = ud_content_id;
						args.meta_table_id = meta_table_id;
						if ( ! Ext.isEmpty(value)) {
							self.list_type = 'common_search';
						}
						self.radio_action = false;

						if (Ext.isEmpty(p.get(0)) ) {
							if( ud_content_id ){
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
											r.reload(args);

											if(Ext.isAir) {
												Ext.getCmp('tab_warp').doReload.defer(2000);
											}
										}catch (e){
										}
									}
								});
							}

						} else {
							p.get(0).reload(args);
						}

						//Ext.getCmp('menu-tree').collapseAll();
						//Ext.getCmp('menu-tree').getRootNode().expand();
					}
				},
				items: [
					<?php
						$tabs = $db->queryAll("select * from bc_ud_content uc order by uc.show_order");
						foreach ($tabs as $tab) {
							if ( ! in_array($tab['ud_content_id'], $MEDIA_LIST)) {
								continue;
							}

							// 권한이 없으면 건너 뛰기
							if ( ! checkAllowUdContentGrant($_SESSION['user']['user_id'], $tab['ud_content_id'], GRANT_READ)) {
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

							if (is_array($category_grant[$tab['ud_content_id']])) {
                                foreach ($category_grant[$tab['ud_content_id']] as $grant) {
                                    if ((0 < $grant) && ($grant < 4)) $isTabView = true;
                                }
                            }

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
                                html: '<h2>권한이 필요합니다<br />관리자에게 문의하십시오</h2>'
                            }";
                        }

                        echo join(", \n", $_tabs);
                        ?>
                    ]
                }]
        }];

        Ariel.Nps.ListArticle.superclass.initComponent.call(this);

        }
		,listeners: {
		}
});