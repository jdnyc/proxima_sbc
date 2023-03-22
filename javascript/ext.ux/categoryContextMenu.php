<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lang.php');

use Proxima\core\Request;
use Proxima\core\Session;
use Proxima\models\content\UserContent;
use Proxima\models\system\CategoryGrant;
use Proxima\models\content\Category;
use Proxima\models\user\User;

Session::init();

$user = Session::get('user');

// use session
$groups = join(",", $user['groups']);

$permissions = ['read', 'add', 'edit', 'del', 'setting'];

$rootCategoryGrant = [];

$user_id = $user['user_id'];
$isAdminYn = $user['is_admin'];

if(!is_null($_POST['search_tbar'])){
    $searchTbar = json_decode($_POST['search_tbar']);
}

$userContents = UserContent::allWithRootCategory();

$mappedRootCategory = [];
foreach($userContents as $userContent) {
    $rootCategory = $userContent->rootCategory();
    $mappedRootCategory[] = [
        'ud_content_id' => $userContent->get('ud_content_id'),
        'category_id' => $rootCategory->get('category_id'),
        'category_title' => $rootCategory->get('category_title')
    ];
}

$viewValues = [
    'isAdmin' => false,
    'rootVisible' => false,
    'useWholeCategory' => false
];

// 전체 카테고리를 쓰던 안쓰던 루트는 0이여야 함
$rootCategory = Category::find(0);

// config.SYSTEM.xml에 정의된 옵션
if (strtolower(USE_WHOLE_CATEGORY) === 'true') {
    $viewValues['useWholeCategory'] = true;
    $viewValues['rootVisible'] = true;
}
$groups = User::find($user_id)->groups();
if ($isAdminYn == 'Y') {
    $viewValues['isAdmin'] = true;
    foreach($permissions as $permission) {	
        $rootCategoryGrant[$permission] = 1;
    }
} else {
    // 그룹에 해당하는 카테고리 권한 조회
    $categoryGrants = CategoryGrant::getCategoryGrantsByGroup($groups);
    //$rootCategoryGrant = CategoryGrant::getCategoryGrant($rootCategory['category_id'], $categoryGrants, []);
	$rootCategoryGrant = CategoryGrant::getCategoryGrant($rootCategory->get('category_id'), $categoryGrants, []);
}
foreach($permissions as $permission) {	
    $rootCategoryGrant[$permission] = 0;
}
$rootCategoryGrant['read']= 1;

$viewValues['rootCategory'] = [
    'id' => $rootCategory->get('category_id'),
    'text' => $rootCategory->get('category_title'),
    'read' => $rootCategoryGrant['read'],
    'add' => $rootCategoryGrant['add'],
    'edit' => $rootCategoryGrant['edit'],
    'del' => $rootCategoryGrant['del'],
    'setting' => $rootCategoryGrant['setting'],
    'expanded' => true // 요것도 옵션으로 빼야 할텐데
];

$customFirstNodeParams = [
    'path' => '/0/100',
    'text' => '',
    'isLoaded' => true
];
if( !empty($groups) ){
    foreach($groups as $group)
    {
        if( $group->get('member_group_id') == 29 ){
            $customFirstNodeParams = [
                'path' => '/0/100/200/3104',
                //'text' => '제20대 대통령직',
                'text' => '',
                'isLoaded' => false
            ];
        }
    }
}
?>

Ext.ns('Ariel.nav');
Ext.ns('Ariel.MainCategory');
Ext.ns('Ariel.AudioMainCategory');
Ext.ns('Ariel.CGMainCategory');


// 뷰 변수 렌더링

<?php

echo 'var categoryContextMenuValues = ' . json_encode($viewValues) . ';';
echo 'var customFirstNodeParams=' . json_encode($customFirstNodeParams) . ';';
?>


function add_favorite_category(category_full_path, category_name)
{
    Ext.Ajax.request({
        url: '/store/add_favorite_category.php',
        params: {
            category_full_path: category_full_path,
            category_name: category_name
        },
        callback: function(opts, success, response){
            if (success)
            {
                var ret = Ext.decode(response.responseText);
                if ( ret.success )
                {
                    Ext.getCmp('bookmark').getLoader().load(Ext.getCmp('bookmark').getRootNode());
                }
                else
                {
                    //>>Ext.Msg.alert('확인', ret.msg);
                    Ext.Msg.alert(_text('MN00024'), ret.msg);
                }
            }
        }
    })
}

// 메인카테고리 정의
Ariel.MainCategory = Ext.extend(Ext.tree.TreePanel, {
    region: 'center',
    //>>title: '카테고리 별',MN00271
    //title: _text('MN00271'),
	//title: _text('MN00271'),
    autoScroll: true,
    //enableDD: true,
    animate: true,
    enableDrag: true,
    enableDrop: true,
    rootVisible: true,
    containerScroll: true,
    autoLoad: false,
	enableKeyEvents:true,
    bodyStyle: {
        fontSize: '15px'
    },
    selectRootNode: function() {
        // 초기화 시 루트 노드를 선택한다.
        var rootNode = this.getRootNode();
        if(rootNode.attributes.read) {
            this.selectPath(this.getRootNode().getPath());
        }
    },
    initComponent: function(config){
        Ext.apply(this, {
			listeners: {
				afterrender: function( c ){
				},
				render:function(panel){
					panel.el.on('keypress',panel.myPanelKeyHandler);
					panel.el.on('keydown',panel.myPanelKeyHandler);
			   },
				beforeappend: function( tree, parent, node ){
					if( !node.attributes.read ) {
						node.disable(true);
					}
				},				
                movenode: function(tree, node, oldParent, newParent, index ) {
                    var order = [];
                    newParent.eachChild(function(i){
                        order.push(i.id);
                    });

                    Ext.Ajax.request({
                        url: '/store/add_category.php',
                        callback: actionCallback,
                        params: {
                            action: 'move-folder',
                            newParent_id: newParent.id,
                            oldParent_id: oldParent.id,
                            new_path: newParent.getPath(),
                            id: node.id,
                            order: Ext.encode(order)
                        }
                    });
                },
                beforenodedrop : function( e ) {

                    if( !Ext.isEmpty(e.source.tree) && ( e.source.tree.getXType() == 'navcategory' ) )
                    {//트리내에서 카테고리 이동과 정렬

                        var check = true;

                        if( !e.dropNode.attributes.edit )//우선 드롭노드의 권한여부
                        {
                            return false;
                        }

                        if( e.point == 'append' ) //타켓노드에 포함
                        {
                            if( !e.target.attributes.edit )//타겟노드의 권한여부
                            {
                                return false;
                            }
                        }
                        else if(e.point == 'below' || e.point == 'above' ) //노드의 위치이동
                        {
                            if( e.target.parentNode.id == 0 ) {
                            }
                            else if( !e.target.parentNode.attributes.edit )//타겟노드의 권한여부
                            {
                                return false;
                            }
                        }
                        else
                        {
                            return false;
                        }

                    }
                    else//콘텐츠 그리드에서 콘텐츠들의 카테고리 변경
                    {

                        var node_id= e.target.id;
                        var node_title = e.target.text;
                        var node_path = e.target.getPath();
                        var count = e.data.selections.length;

                        if(!e.target.attributes.read)
                        {
                            return false;
                        }
                        if( e.target.getDepth() < 1 )
                        {
                            Ext.Msg.alert( _text('MN00023'), '이동할 수 없는 카테고리입니다');
                            return false;
                        }

                        var parent_node_id = e.target.parentNode.id;

                        var selections = e.data.selections;

                        var is_same_parent = false;
                        var is_working = false;
                        var is_offline = false;

                        var contents = new Array();

                        var parent_node = e.target.parentNode;

                        Ext.each(selections , function(i){
                            contents.push(i.id);

                            if( parent_node_id == i.data.category_id )
                            {
                                is_same_parent = true;
                            }
                            else
                            {
                                parent_node.eachChild( function(r){

                                    if( r.id == i.data.category_id )
                                    {
                                        is_same_parent = true;
                                    }
                                });
                            }

                            if( i.json.ori_status == '1')
                            {
                                is_offline = true;
                            }

                            if( ( !Ext.isEmpty(i.json.ori_task_status) && ( i.json.ori_task_status != 'complete') ) || ( !Ext.isEmpty(i.json.proxy_task_status) && ( i.json.proxy_task_status != 'complete') ) )
                            {
                                is_working = true;
                            }


                        });

                        if(is_offline)
                        {
                            Ext.Msg.alert( _text('MN00023'), '원본이 존재 하지 않습니다.');
                            return false;
                        }

                        if( is_working )
                        {
                            Ext.Msg.alert( _text('MN00023'), '콘텐츠의 등록작업이 완료 되지 않았습니다.');
                            return false;
                        }


                        Ext.Msg.show({
                            title: _text('MN00024'),//확인
                            icon: Ext.Msg.INFO,
                            msg: count+'개 콘텐츠의 카테고리를'+'</br >'+node_title+'(으)로'+'</br >'+'변경하시겠습니까?</br >파일 이동에는 수초의 시간이 소요됩니다.',
                            buttons: Ext.Msg.OKCANCEL,
                            fn: function(btnID, text, opt) {
                                if(btnID == 'ok') {
                                    Ext.Ajax.request({
                                        url: '/store/add_category.php',
                                        callback: function(opts, success, response){
                                            var o = {}, store, record;

                                            if(true != success){
                                                Ext.Msg.show({
                                                    //>>title: '실패',
                                                    title: _text('MN00012'),
                                                    msg: '',
                                                    buttons: Ext.Msg.OK,
                                                    closable: false
                                                });
                                                return;
                                            }
                                            try {
                                                result = Ext.decode(response.responseText);
                                                if (result && true == result.success) {

                                                    Ext.getCmp('tab_warp').getActiveTab().get(0).getStore().reload();

                                                } else {
                                                    //>>Ext.Msg.alert('오류', result.msg);
                                                    Ext.Msg.alert(_text('MN00022'), result.msg);
                                                }
                                            } catch(e) {
                                                Ext.Msg.show({
                                                    //>>title: '오류',
                                                    title: _text('MN00022'),
                                                    msg: e,
                                                    buttons: Ext.Msg.OK,
                                                    closable: false
                                                })
                                            }
                                        },
                                        params: {
                                            action: 'change-category',
                                            new_path: node_path,
                                            id: node_id,
                                            contents: Ext.encode(contents)
                                        }
                                    });
                                }
                                else
                                {
                                    return false;
                                }
                            }
                        });
                    }

                },
                beforemovenode : function( tree, node, oldParent, newParent, index ) {
                }
            },
			myPanelKeyHandler:function(e){
				var key = e.getKey();
				if( key === 113 ){//F2
					var r = Ext.getCmp('menu-tree');
					r.editor.triggerEdit(r.selModel.selNode);
				}
			},
            root: new Ext.tree.AsyncTreeNode(categoryContextMenuValues.rootCategory)
        });


        this.on('collapse', function(p, a){

            if( p.nextSibling() )
            {
                p.nextSibling().expand();
            }
            else if( p.previousSibling() )
            {
                p.previousSibling().expand();
            }
        });

        Ariel.MainCategory.superclass.initComponent.call(this);

        Ext.override(Ext.tree.TreeEditor, {
            initEditor: function(tree){
                tree.on({
                    beforeclick: function(){

                    }
                })
            }
        });

        this.editor = new Ext.tree.TreeEditor(this, {
            allowBlank: false,
            grow: true,
            growMin: 90,
            growMax: 240
        },{
            completeOnEnter: true,
            cancelOnEsc: true,
            selectOnFocus: true,
            listeners: {
                cancelEdit: function(self, value, startValue){
                    var n = self.editNode;
                    if(n.attributes.isNew) n.remove();
                },
                beforecomplete: function(self, newValue, oldValue){
                    return false;
                    self.editNode.setText(newValue);
					//2015-12-22 카테고리명 15자 이내
					var new_name = self.editNode.attributes.text;
					if(new_name.length > 250){
						Ext.Msg.show({
							title : _text('MN00023'),//알림
							msg : _text('MSG02025'),//카테고리명은 15자 이내입니다.
							buttons: Ext.Msg.OK
						});
						return false;
					}else{
						if (self.editNode.attributes.isNew) {
							createFolder(self.editNode);
						} else {
							renameFolder(self.editNode, newValue, oldValue);
						}
						return true;
					}
                }
            }
        });

        this.on('contextmenu', this.onContextMenu, this);
    },

    contextMenu: new Ext.menu.Menu({
        defualts: {
            hidden: true
        },
        items: [{
            cmd: 'refresh-node',
            //>>text: '새로고침',
            text: _text('MN00139'),
            icon: 'led-icons/arrow_refresh.png'
        },{
            cmd: 'add-node',
            //>>text: '생성',
            text: _text('MN00042'),
            icon: 'led-icons/folder_add.png'
        },{
            cmd: 'edit-node',
            //>>text: '수정',
            text: _text('MN00043'),
            icon: 'led-icons/folder_edit.png'
        },{
            cmd: 'delete-node',
            //>>text: '삭제',
            text: _text('MN00034'),
            icon: 'led-icons/folder_delete.png'
        },
        /*
        {
            cmd: 'move-node',
            //>>text: '이동',
            text: _text('MN02036'),
            icon: 'led-icons/folders.png'
        },{
            cmd: 'bookmark',
            //>>text: '빠른검색 추가',
            text: '빠른검색 추가',
            icon: 'led-icons/star_2.png'
        },
        */
        {
            hidden: true,
            cmd: 'golast',
            text: '최하위로...',
            icon: 'led-icons/star_2.png'
        }
        
        ,{
            cmd: 'setting-node',
            //>>text: '설정',
            text: _text('MN01014'),
            icon: 'led-icons/cog.png'
        }
        
        ],
        listeners: {
            itemclick: {
                fn: function(item, e){
                    var r = item.parentMenu.contextNode.getOwnerTree();
                    switch (item.cmd) {
                        case 'refresh-node':
                            r.refreshFolder(item.parentMenu.contextNode);
                        break;
                        case 'add-node':
                            r.invokeCreateFolder(item.parentMenu.contextNode);
                        break;

                        case 'edit-node':
                            r.editor.triggerEdit(item.parentMenu.contextNode);
                        break;

                        case 'delete-node':
                            r.deleteFolder(item.parentMenu.contextNode);
                        break;

                        case 'move-node':
                            r.moveFolder(item.parentMenu.contextNode);
                        break;

                        case 'setting-node':
                            r.settingFolder(item.parentMenu.contextNode);
                        break;

                        case 'golast':
                            r.golast(item.parentMenu.contextNode);
                        break;

                        case 'bookmark':
                            r.addBookmarkFolder(item.parentMenu.contextNode);
                        break;
                    }
                },
                scope: this
            }
        }
    }),

	onContextMenu: function(node, e){
        // 읽기 권한이 있을 때만 선택
        if(node.attributes.read)
		//    node.select();
        var c = node.getOwnerTree().contextMenu;		
        //console.log(node);
		c.items.each( function(i) {
            if (i.cmd == 'refresh-node') {
                i.setVisible( true );
            } else if(i.cmd == 'bookmark') {
                i.setVisible(node.attributes.read);
            }
			else if (i.cmd == 'add-node' ) {
				i.setVisible(node.attributes.add);
			} else if (i.cmd == 'edit-node') {
				i.setVisible( node.attributes.edit );
			} else if (i.cmd == 'move-node') {
                if(node.getDepth() < 2) {
					i.setVisible( false );
				} else if(!node.attributes.leaf) {
                    i.setVisible( false );
                }
                else {
					i.setVisible( node.attributes.del );
				}				
			} else if (i.cmd == 'setting-node') {
				i.setVisible( node.attributes.setting );
			} else if (i.cmd == 'delete-node') {
				if(node.getDepth() < 2) {
					i.setVisible( false );
				}  else if(!node.attributes.leaf) {
                    i.setVisible( false );
                } else {
					i.setVisible( node.attributes.del );
				}
			} else {
				i.setVisible( false );
			}
		});
		c.contextNode = node;
		//c.showAt(e.getXY());
        //context menu 숨김처리
        c.showAt(false);
	},

    refreshFolder: function(node) {
        if(node != null && node.hasChildNodes())
        {
            node.reload();
        }
    },

    addBookmarkFolder: function(node) {
                
        var name = node.attributes.text;
        
        var requestData = {
            user_id: userId,
            name: name,
            filters: {
                category_id: node.id
            }
        }        

        Ext.Ajax.request({
            url: '/store/search/custom_search.php',
            method: 'PUT',
            jsonData: requestData,
            success: function(response, opts){
                try {
                    var r = Ext.decode(response.responseText);
                    if(r.success) {	
                        // 성공하면 빠른검색 리로드
                        Ext.getCmp('nps_media__custom_search_grid').store.reload();
                    } else {
                        Ext.Msg.alert('오류', r.msg);
                    }
                } catch(e) {
                    Ext.Msg.alert(e['name'], e['message']+'<br/>'+response.responseText);
                    return;
                }
            },
            failure: function(response, opts){
                Ext.Msg.alert('오류', '서버오류(' + response.status + ')');
            }
        });  
    },

    invokeCreateFolder: function(node){
        node.leaf = false;
        node.expand(false, true, function(node){
            var newNode = node.appendChild(new Ext.tree.TreeNode({
                // text: '새 이름',
                text: _text('MN00140'),
                cls: 'folder',
                //icon: '/lib/extjs/resources/images/default/tree/folder.gif',
                read: 1,
                add: node.attributes.add,
                edit: node.attributes.edit,
                del: node.attributes.del,
                setting: node.attributes.setting,
                leaf: false,
                isNew: true
            }));
            this.getSelectionModel().select(newNode);
            this.editor.triggerEdit(newNode);
        }, this);
    },

    deleteFolder: function(node){
        if(node.hasChildNodes()){
            Ext.Msg.show({
                title: _text('MN00023'),
                //>>msg: '하위 카테리고리 부터 삭제하여주세요.',
                msg: _text('MSG00141'),
                buttons: Ext.Msg.OK,
                closable: false
            })
            return;
        }

        Ext.Msg.show({
            //>>title: '삭제',
            title: _text('MN00034'),
            //>> msg: '\''+node.text+'\''+' 카테고리를 삭제 하시겠습니까?',
            msg: _text('MSG00140')+' '+node.text+' ?',
            buttons: Ext.Msg.YESNO,
            closable: false,
            icon: Ext.Msg.QUESTION,
            fn: function(btnID){
                if(btnID === 'yes'){
                    Ext.Ajax.request({
                        url: '/store/add_category.php',
                        callback: actionCallback,
                        params: {
                            action: 'delete-folder',
                            parent_id: node.parentNode.attributes.id,
                            id: node.attributes.id
                        },callback: function(opts, success, response){
                            if(success){
                                try{
                                    var r = Ext.decode(response.responseText);
                                    if(!r.success){
                                        Ext.Msg.alert(_text('MN00023'), r.msg);
                                        return;
                                    }
                                    else
                                    {
										//node.parentNode.leaf = true;
										node.remove();
									}
								}
								catch (e)
                                {
                                    Ext.Msg.alert(e['name'], e['message']);
                                }
							}else{
                                Ext.Msg.alert(_text('MN00022'), response.statusText);
                            }
						}
                    });
                }
            }
        })
    },

    // 폴더 이동(최하위 폴더만 이동 가능)
    moveFolder: function(node) {
        // js파일로 분리 필요
		if(node.hasChildNodes()) {
			Ext.Msg.alert('알림', '최하위 카테고리만 이동할 수 있습니다.');
			return;
		}

        var mappedRootCategory = <?=json_encode($mappedRootCategory)?>;

        var mappedUdContent = mappedRootCategory.find(function (userContent) {
            return userContent.ud_content_id == node.attributes.ud_contents[0];
        });
        var rootNodeId = mappedUdContent.category_id;
        var rootNodeText = mappedUdContent.category_title;

        var nodePath = node.getPath();
        var arrNodePath = nodePath.split('/');
        var rootNodeIdx = arrNodePath.indexOf(rootNodeId);

        for(var i=0; i < rootNodeIdx; i++) {
            arrNodePath.shift();
        }

        nodePath = arrNodePath.join('/');

		var categoryForm = new Ext.form.FormPanel({
			frame: true,
			width: 350,
			height: 80,
			items: [{
				xtype: 'toolbar',
				items:[{
					xtype: 'label',
					text: '이동할 대상 카테고리를 지정하십시오.'
				}]
			},{
				xtype: 'treecombo',
				flex: 1,
				id: 'move_category',
				name: 'category_id',
				value: nodePath,
				anchor: '98%',
				enableNodeSelect: true,
                pathSeparator: ' > ',
				fieldLabel: '대상 카테고리',
				listeners: {
					render: function(self){
						var path = nodePath;
						if(!Ext.isEmpty(path)){
							path = path.split('/');
							var catId = path[path.length-1];
							if(path.length <= 1) {
								self.setValue('');
								self.setRawValue('');
							} else {
								self.setValue(catId);
								self.setRawValue('');
							}
						}
					}
				},
				loader: new Ext.tree.TreeLoader({
					url: '/store/get_categories_media.php',
					baseParams: {
						path: nodePath
					},
					listeners: {
						load: function(self, node, response){
							
							var path = self.baseParams.path;
							if(!Ext.isEmpty(path) && path != '0'){
								path = path.split('/');

								var id = path.shift();
								if(id == node.id) {
									id = path.shift();
								}
								self.baseParams.path = path.join('/');

								var n = node.findChild('id', id);
								if(n && n.isExpandable()) {
									n.expand();
								} else {
									n.select();
									Ext.getCmp('move_category').setValue(n.id);
								}
							} else {
								node.select();
								Ext.getCmp('move_category').setValue(node.id);
							}							
						}
						
					}
				}),
				root: new Ext.tree.AsyncTreeNode({
					id: rootNodeId,
					text: rootNodeText,
					expanded: true
				}),
                listeners: {
                    select: function(self, node) {
                        Ext.getCmp('move_category').setValue(node.id);
                    }
                }
			}]
		});
		
		new Ext.Window({
			id: 'category-move-window',
			title: '카테고리 이동',
			width: 370,		
			autoHeight: true,
			modal: true,
			resizable: false,
			items: [categoryForm],
			buttons: [{
				text: '확인',
				handler: function(b, e){
					var oldNode = node.id;
					var newNode = Ext.getCmp('move_category').treePanel.getSelectionModel().getSelectedNode().attributes.id;

                    var loadMask = new Ext.LoadMask(Ext.getBody(), {msg:_text('MSG00143')});
                    loadMask.show();

					Ext.Ajax.request({
						url: '/store/category/move_category.php',
						params: {
							categoryId: oldNode,
							newParentId: newNode
						},
						success: function(response, opts){
							try {
								var r = Ext.decode(response.responseText);
                                loadMask.hide();
								if(r.success) {
                                    var rootNode = Ext.getCmp('move_category').root.id;
                                    var categoryNode = Ext.getCmp('menu-tree').getNodeById(rootNode);
									if(categoryNode != null && categoryNode.hasChildNodes()) {
										categoryNode.reload();
									}
                                    Ext.Msg.alert('알림', '카테고리 이동이 완료되었습니다');
									b.ownerCt.ownerCt.close();
								} else {
									if(r.msg == 'session over') {
										Ext.Msg.alert('알림', '사용자 세션이 종료되었습니다.');
									} else {
										Ext.Msg.alert('오류', r.msg+'(category-move-window)');
									}
								}
							} catch(e) {
                                loadMask.hide();
								Ext.Msg.alert(e['name'], e['message']+'(category-move-window)<br/>'+response.responseText);
								return;
							}
						},
						failure: function(response, opts) {
							Ext.Msg.alert('오류', '서버오류(' + response.status + ')');
						},
						scope: self
					});
				}
			},{
				text: '취소',
				handler: function(b, e){
					b.ownerCt.ownerCt.close();
				}
			}]			

		}).show();
	},

    // 폴더 관리
    settingFolder: function(node) {
        
        Ext.Ajax.request({
            url: '/javascript/ext.ux/category/categoryGrantWin.js',
            method: 'GET',
            callback: function(opts, success, response){
                if(success) {                            
                    var categoryGrantWinFunc = Ext.decode(response.responseText);                    
                    var categoryGrantWin = categoryGrantWinFunc(node.attributes.id);
                    categoryGrantWin.show();
                } else {
                    Ext.Msg.alert('서버 오류', response.statusText);
                }
            }
        });	
        
    },

    golast: function(node){
        Ext.Msg.show({
            title: '알림',
            msg: '['+node.text+']를 <br />폴더 삭제 하시겠습니까?<br />폴더 삭제시 모든 원본파일은 삭제됩니다.',
            buttons: Ext.Msg.YESNO,
            closable: false,
            icon: Ext.Msg.QUESTION,
            fn: function(btnID){
                if(btnID === 'yes'){
                    Ext.Ajax.request({
                        url: '/store/add_category.php',
                        params: {
                            action: 'golast',
                            parent_id: node.parentNode.attributes.id,
                            id: node.attributes.id
                        },
                        callback: function(opts, success, response){
                            if(success){
                                try{
                                    var r = Ext.decode(response.responseText);
                                    if(!r.success){
                                        Ext.Msg.alert(_text('MN00023'), r.msg);
                                        return;
                                    }
                                    else
                                    {
                                        Ext.Msg.alert(_text('MN00023'), r.msg);
                                        var tree = node.ownerTree.ownerCt.get(1);
                                        var root = tree.getRootNode();
                                        tree.getLoader().load(root);
                                        node.remove();
                                    }
                                }
                                catch (e)
                                {
                                    Ext.Msg.alert(e['name'], e['message']);
                                }
                            }else{
                                //>>Ext.Msg.alert('오류', response.statusText);
                                Ext.Msg.alert(_text('MN00022'), response.statusText);
                            }
                        }
                    });
                }
            }
        })
    },

    filterTree: function(t, e){
        var text = t.getValue();
        Ext.each(this.hiddenPkgs, function(n){
            n.ui.show();
        });
        if(!text){
            this.filter.clear();
            return;
        }
        this.expandAll();

        var re = new RegExp(Ext.escapeRe(text), 'i');
        this.filter.filterBy(function(n){

            return !n.attributes.isClass || re.test(n.text);
        });

        // hide empty packages that weren't filtered
        this.hiddenPkgs = [];
        var me = this;
        this.root.cascade(function(n){

            if(!n.attributes.isClass && n.ui.ctNode.offsetHeight < 3){
                n.ui.hide();
                me.hiddenPkgs.push(n);
            }
        });
    },

    isReadNode: function(nav, node){
        return;
    }
});
Ext.reg('navcategory', Ariel.MainCategory);

//미디어 검색 카테고리(메인화면)
Ariel.nav.MainPanel = Ext.extend(Ext.Panel, {
	id: 'nav_tab',
	border: false,
	region: 'center',
    layout :'border',
    useWholeCategory: categoryContextMenuValues.useWholeCategory,
    //changeActiveTab:null,

	defaults : {
		split: true
	},

	initComponent: function(config){
        Ext.apply(this, config || {});
        Ext.override(Ext.tree.TreeEditor, {
            initEditor: function(tree){
                tree.on({
                    beforeclick: function(){

                    }
                })
            }
        });

        this.items = [{
            margins: '0 0 0 0',
            region: 'center',
	    	border:false,
            layout: 'border',
            //title: '<span class="user_span"><span class="icon_title"><i class="fa fa-sitemap"></i></span><span class="user_title">'+_text('MN00271')+'</span></span>',
            //title: '<span class="user_span"><span class="icon_title" style="color: #ffffff"><i class="fa fa-list"></i></span><span class="user_title" style="color: #ffffff">'+_text('MN00271')+'</span></span><span title="'+_text('MN02221')+'" class="icon_title3" onclick="fn_category_management_media()"><i class="fa fa-cog"></i></span>',
            items: [{
				xtype: 'tabpanel',
				border:false,
				region: 'north',
				hidden : true,
				height: 130,
				activeTab: 0,
				bbar: [{
					xtype: 'displayfield',
					value: ' ',
					height: 23
				}],
				items: [{
					xtype: 'form',
					title: _text('MN01108'),//아카이브 상태 검색 필터
					padding: 10,
					//frame: true,
					labelWidth: 60,
					items: [{
						xtype: 'combo',
						fieldLabel: _text('MN00138'),//상태
						width: 145,
						id: 'archive_search_combo',
						typeAhead: true,
						triggerAction: 'all',
						editable: false,
						mode: 'local',
						displayField: 'name',
						valueField: 'code',
						value: '1',
						store: new Ext.data.ArrayStore({
							fields: [
								'name', 'code'
							],
							data: [
								[_text('MN00008'),'1'],//전체
								[_text('MN01109'),'2'],//온라인
								[_text('MN01110'),'3']//아카이브
							]
						}),
						listeners: {
							select: function(self, record, index){
								var content_tab = Ext.getCmp('tab_warp');
								var active_tab = content_tab.getActiveTab();
								active_tab.get(0).reload();
							}
						}
					}]
				}]
			},{
				xtype: 'tabpanel',
				id: 'tree-tab',
				region: 'center',
				border:false,
                activeTab: 0,
				listeners: {
					tabchange: function(self, p) {

						var sm = p.getSelectionModel();
						var at = Ext.getCmp('tab_warp').getActiveTab();
						var node = '';
              
						if(p.id == 'menu-tree') {
							if( Ext.isEmpty(sm.getSelectedNode()) ) {
							} else {
								node = sm.getSelectedNode();
								var params = {
									filter_type: 'category',
									filter_value: node.getPath()
								};

								//at.reload( params );
							}
						} else if(p.id == 'topic-tree') {
							if( Ext.isEmpty(sm.getSelectedNode()) ) {
								var params = {
									filter_type: 'category',
									filter_value: '/-1'
								};
							} else {
                                node = sm.getSelectedNode();
                                var params = {
                                    filter_type: 'category',
                                    filter_value: node.getPath()
                                };
                            }
                            at.reload( params );
                        } else if (p.xtype == 'materialcategory') {
                            if (Ext.isEmpty(sm.getSelectedNode())) {
                                var params = {
                                    filter_type: 'category',
                                    filter_value: '/-2'
                                };
                            } else {
                                node = sm.getSelectedNode();
                                var params = {
                                    filter_type: 'category',
                                    filter_value: node.getPath()
                                };
                            }
                            at.reload(params);
                        }
                    },
                    afterrender: function(self){                
                    }
                },
                items: [
                // 카테고리 Tree Panel
                {
                    id: 'menu-tree',
                    layout: 'fit',
					border : false,
                    xtype: 'navcategory',
                    //ddGroup: 'ContentDD',
                    title: '프로그램',
					//title: _text('MN00387'),
                    rootVisible: categoryContextMenuValues.rootVisible,
                    customIsLoading:{},
                    depthIsLoadingArray:[

                    ],
                    depthInfo:{
                    },
                    nodeLoadInfo:{
                        prevDepth:0,
                        nowDepth:0,
                        index:null
                    },
                    customFirstNodeSetLoad: function(){
                        this.customFirstNode.isLoaded = true;
                        return true;
                    },
                    customFirstNode: customFirstNodeParams,
                    allLoaded:false,

                    mask:null,
                    customRender:function(self,nowNode){
                        function logic(){
                            if(self.allLoaded){
                                return;
                            }
                            self.nodeLoadInfo.nowDepth = nowNode.getDepth();
                            
                            if(self.nodeLoadInfo.nowDepth != self.nodeLoadInfo.prevDepth){
                                self.nodeLoadInfo.prevDepth = self.nodeLoadInfo.nowDepth;
                                self.nodeLoadInfo.index = 0;    
                            }else{
                                if(Ext.isEmpty(self.nodeLoadInfo.index)){
                                    self.nodeLoadInfo.index = 0;    
                                }else{
                                    self.nodeLoadInfo.index = self.nodeLoadInfo.index +1;
                                }
                            }    
                            
                            
                                
                            

                            
        
                            for(var i = 0, len = cs.length; i < len; i++){
                                

                                var customIsLoading = false;    
                                if(cs[i].hasChildNodes()){
                                    customIsLoading = true;
                                };
                    
                                self.depthIsLoadingArray[nowNode.getDepth()].push(customIsLoading);
                                
                                if(customIsLoading){
                                    self.depthInfo[nowNode.getDepth()]['hasChildCount']=self.depthInfo[nowNode.getDepth()]['hasChildCount']+1;
                                }
                                
                                if(cs[i].hasChildNodes()){
                                        self.customRenderStart(self,cs[i]);
                                }
                            }
                            
                            
                            var nodeEndIndex = self.nodeLoadInfo.index+1;
                            if(nowNode.getDepth() != 0){
                                if(nodeEndIndex == self.depthInfo[nowNode.getDepth()-1].hasChildCount){
                                    var loadNodes = self.depthIsLoadingArray[nowNode.getDepth()];
                                    var allLoaded = true;
                                    Ext.each(loadNodes,function(node){
                                        if(node){
                                            allLoaded = false;
                                        }
                                    });
                                }
                            }
                         
                            
                            if(allLoaded){
                                self.allLoaded = allLoaded;
                                self.el.unmask(self.removeMask);
                                self.getNodeById('100').expand();
                                nowNode.collapse();   
                                self._searchNode();

                                return;
                            }
                            nowNode.collapse();   
                        }
                        
                        if(self.allLoaded){
                            return;
                        };
                        
                        if(Ext.isEmpty(self.depthIsLoadingArray[nowNode.getDepth()])){
                            self.depthIsLoadingArray[nowNode.getDepth()] = new Array();

                            self.depthInfo[nowNode.getDepth()] = new Array();
                            self.depthInfo[nowNode.getDepth()]['hasChildCount'] = 0;
                        };
                        
                        if(nowNode.hasChildNodes()){
                            var cs = nowNode.childNodes;
                            var depthIndexObj = new Object();
                            var depthInfo = new Object();
                            var depthIndexArray = new Array();
                            var depthLodingArray = new Array();
                            var isExpanded = nowNode.isExpanded();

                            if(isExpanded){
                                logic();
                            }else{
                                nowNode.expand();
                                nowNode.on('expand',function(nowNode){
                                    logic();
                                });
                            }
             

                        }
                        
                    },
                    customRenderStart: function(self,nowNode){
                        self.customRender(self,nowNode);
                    },
                    searchReady: function(){
                        if(!this.allLoaded){
                            this.mask  = new Ext.LoadMask(this.el, {msg: '카테고리 정보를 불러오는 중입니다..'});
                            this.mask.show();
                            var rootNode = this.getRootNode();
                            this.customRenderStart(this,rootNode);
                        }
                    },
                    listeners:{
                        render: function(self){   
                            //this.mask  = new Ext.LoadMask(this.el, {msg: '카테고리 정보를 불러오는 중입니다..'});
                            //this.mask.show();
                            //var rootNode = this.getRootNode();
                            //self.customRenderStart(self,rootNode);
                            
                        },
                        beforerender: function(self){
                            var _this = this;
                            //매뉴 트리 검색 툴바
                            var tbar = self.getTopToolbar();

                            tbar.style = {
                                backgroundColor:'#2d2d33',
                                border : '1px solid #2d2d33',
                            };
                            self.searchField = new Ext.form.TextField({
                                style:{
                                    backgroundColor:'#1f1f1f',
                                    color:'#FFFFFF',
                                    border : '1px solid #000000',
                                },
                                emptyText:'검색어를 입력해주세요.',
                                listeners:{
                                    specialkey:function(f,e){
                                        var _this = Ext.getCmp('menu-tree');
                                        if (e.getKey() == e.ENTER) {
                                            //2뎁스 까지만 되던거
                                            //_this._filterNodeSearch();
                                            if(_this.allLoaded){
                                                _this._searchNode();
                                            }else{
                                                _this.searchReady();
                                            }
                                        }
                                    }
                                }
                            });
                            var searchButton = new Ext.Button({
                                cls : 'proxima_btn_customize proxima_btn_customize_new',
                                text: '<span style="position:relative;" title="'+_text('MN02262')+'"><i class="fa fa-search" style="font-size:13px;"></i></span>',//검색
                                handler:function(self){
                                    //트리노드 필터 검색
                                    //_this._filterNodeSearch();

                                    //뎁스만큼의 검색
                                    if(_this.allLoaded){
                                        _this._searchNode();
                                    }else{
                                        _this.searchReady();
                                    }
                                   
                                    
                                }
                            });
                            var refreshButton = new Ext.Button({
                                cls : 'proxima_btn_customize proxima_btn_customize_new',
                                text: '<span style="position:relative;" title="'+_text('MN02262')+'"><i class="fa fa-refresh" style="font-size:13px;"></i></span>',//초기화
                                handler:function(self){
                                    _this.getRootNode().select();
                                    _this.searchField.setValue(null);
                                    //2뎁스 까지만 되는거
                                    //_this._filterNodeSearch();
                                    //cms 노트 밑으로 새로고침시 다시 collapse
                                    //_this.getRootNode().childNodes[0].collapseChildNodes();

                                    //뎁스만큼의 검색
                                    if(_this.allLoaded){
                                        _this._searchNode();
                                    }else{
                                        _this.searchReady();
                                    }
                                }
                            });
                            tbar.add(_this.searchField);
                            tbar.add(searchButton);
                            tbar.add(refreshButton);
                        },
                        afterrender: function(self){
                            if(this.customFirstNode.isLoaded == false){
                                this.selectPath(this.customFirstNode.path);
                                if(this.customFirstNode.text != ''){
                                    this.getTopToolbar().get(0).setValue(this.customFirstNode.text);
                                    this.searchReady();
                                }
                            }
                            //doSimpleSearch('media');
                        }
                    },
                    loader: new Ext.tree.TreeLoader({
                        url: '/store/get_left_categories_media.php',
                        listeners: {
                            beforeload: function (treeLoader, node, callback){
                                if (!treeLoader.loaded && !treeLoader.baseParams.active_tab_ud_content_id) {
                                    treeLoader.baseParams.active_tab_ud_content_id = Ext.getCmp('tab_warp').get(0).ud_content_id;
                                }
                                
                                treeLoader.baseParams.action = "get-folders";
                                treeLoader.baseParams.read = node.attributes.read;
                                treeLoader.baseParams.add = node.attributes.add;
                                treeLoader.baseParams.edit = node.attributes.edit;
                                treeLoader.baseParams.del = node.attributes.del;
                                treeLoader.baseParams.setting = node.attributes.setting;
                                treeLoader.baseParams.hidden = node.attributes.hidden;
                                if(!Ext.isEmpty(node.attributes.ud_contents) && node.attributes.ud_contents.length > 0) {
                                    treeLoader.baseParams.ud_contents = node.attributes.ud_contents.join(',');    
                                } else {
                                    treeLoader.baseParams.ud_contents = null;
                                }      
                                treeLoader.baseParams.use_whole_category = categoryContextMenuValues.useWholeCategory;
							},
                            load: function (treeLoader, node, callback){                             
                                var loadNode = node;
                                if(!treeLoader.loaded){
                                    //첫 로드 후 
                                    //node select
                                   
                                    if( node.hasChildNodes() ){
                                        node.ownerTree.getSelectionModel().select(node.firstChild);
                                    }
                                }
                                if (treeLoader.baseParams.active_tab_ud_content_id) {
                                    treeLoader.loaded = true;
                                    delete treeLoader.baseParams.active_tab_ud_content_id;
                                }
								
								if (!Ext.getCmp('menu-tree').getRootNode().isExpanded()){
									Ext.getCmp('menu-tree').getRootNode().expand();
								}
								if( !node.attributes.read ){
									node.disabled = true;
								}
                           
                                Ext.getCmp('menu-tree').on('click',function(node,e){
                                                                   
                                    //if(node.id == '206'){
                                    //    return;
                                    //};
                                    if(node.id == '207'){
                                        return;
                                    };
                                            // 검색어가 입력 되어 있을 경우 카테고리가 바껴도 검색어를 계속 유지하도록 하기 위해 수정
                                    // 검색정책에 맞게 카테고리 클릭시 전체 초기화(2017.12.21 Alex)
                                    //var search_q = Ext.getCmp('search_input').getValue();

                                    Ext.getCmp('search_input').setValue();
                                    var customSearchGrid = Ext.getCmp('nps_media__custom_search_grid');
                                    customSearchGrid.getSelectionModel().clearSelections();
                                    customSearchGrid.rowSelectedIndex = -1;
                                    var tagSearchGrid = Ext.getCmp('tag_search');
                                    tagSearchGrid.getSelectionModel().clearSelections();
                                    tagSearchGrid.rowSelectedIndex = -1;
                                
                                    var contentTab = Ext.getCmp('tab_warp');
                                    var activeTab = contentTab.getActiveTab();
            
                                        // 바텀 툴바 검색창
                                        //var searchBottomToolbar = Ext.getCmp('nps_media_search_bottom_toolbar');
                                        //var dateCombo = searchBottomToolbar.find('name', 'dateCombo')[0].getValue();
                                        
                                        //var statusCombo = searchBottomToolbar.find('name', 'statusCombo')[0].getValue();
                                        //var reviewStatusCombo = searchBottomToolbar.find('name', 'reviewStatusCombo')[0].getValue();
                                        //var archiveStatusCombo = searchBottomToolbar.find('name', 'archiveStatusCombo')[0].getValue();

                                        //var contentTab = Ext.getCmp('tab_warp');
                                        //var activeTab = contentTab.getActiveTab();

                                        //var filters = {
                                          //  'content_status' : statusCombo,
                                            //'content_review_status': reviewStatusCombo,
                                            //'content_archive_status': archiveStatusCombo,
                                            //'created_date': dateCombo
                                        //};

                                        //바텀툴바 검색창 수정후 ~
                                        var filters = {

                                        };
                                        var searchToolbarBox = Ext.getCmp('tbarContainer');
                                        var searchTbar2 = searchToolbarBox.getComponent('toolbar2');
                                        var tbar2Fields = searchTbar2._getValueFields();
                                        Ext.each(tbar2Fields, function(field){
                                            filters[field.name || field.itemId] = field.getValue();
                                        });

                                        var searchTbar3 = searchToolbarBox.getComponent('toolbar3');
                                        var tbar3Fields = searchTbar3._getValueFields();
                                        Ext.each(tbar3Fields, function(field){
                                            filters[field.name || field.itemId] = field.getValue();
                                        });

                                        var searchTbar4 = searchToolbarBox.getComponent('toolbar4');
                                        var tbar4Fields = searchTbar4._getValueFields();
                                        Ext.each(tbar4Fields, function(field){
                                            filters[field.name || field.itemId] = field.getValue();
                                        });

                                    
                                    var newParams = {
                                        filter_type: 'category',
                                        filter_value: node.getPath(),
                                        search_q: '',
                                        filters: Ext.encode(filters),
                                        tag_category_id: '',
                                        start: 0
                                    }

                                    if (categoryContextMenuValues.useWholeCategory) {
                                        // 카테고리에 사용자 정의 콘텐츠가 맵핑되어 있으면 클릭할 때 해당 탭을 활성화 시켜준다.
                                        var nodeUdContents = node.attributes.ud_contents;   
                                                        
                                        if(nodeUdContents != undefined && nodeUdContents != null && nodeUdContents.length > 0) {
                                            var isChangedTab = true;  
                                            for(var i=0; i < nodeUdContents.length; i++) {
                                                // 현재 활성화 되어 있는 탭과 같으면 무시
                                                if(nodeUdContents[i] == activeTab.ud_content_id) {
                                                    isChangedTab = false;
                                                }
                                            }  

                                            if(isChangedTab) {
                                                // 첫번째 사용자 정의 콘텐츠 탭을 활성화 시킨다.
                                                var changeTabId = 'tab_warp_ud_content_'+ nodeUdContents[0];
                                                var changeTab = contentTab.getComponent(changeTabId);
                                                if(!Ext.isEmpty(changeTab)) {
                                                    contentTab.setActiveTab(changeTab);
                                                }
                                            }                             
                                        }
                                    }
                                    //activeTab.reload( newParams );

                                    doSimpleSearch();
                                    
                                    //Ext.each(loadNode.childNodes,function(r,i){
                                    //    console.log('r', r);
                                    //    console.log('node', node);
                                    //    if(r === node){
                                    //        doSimpleSearch();
                                   //     }
                                    //});

                                    e.preventDefault();
                                    return false;
                                
                                });
							}
                        }
                    }),
                    tbar:[],
                    _searchNode: function(){
                        var tree = this;
                        
                        var loader = this.getLoader();
                        var searchText = this.searchField.getValue();
                        var allNodes = this.nodeHash;
                        var filterNodesPath = [];


                        tree.collapseAll();
                        tree.getNodeById('100').expand();

                        //빈값 입력시 초기화
                        if(searchText == ""){
                            
                            for(var nodeId in allNodes){
                                var n = allNodes[nodeId];
                                n.getUI().show();    
                            }    
                            this.selectPath(this.getRootNode().getPath());
                            return;
                        }
                        
                        
                        //전체 노드를 반복문 돌려서 필터링
                        for(var nodeId in allNodes){
                            var n = allNodes[nodeId];
                            //검색된 노드들은 배열에 넣어두고 아니면 일단 숨김 처리해놓은 다음에
                            if (n.text.toLowerCase().indexOf(searchText.toLowerCase()) != -1) {
                                filterNodesPath.push(n.getPath());
                            }else{
                                
                                if(n.isLeaf()){
                                    n.getUI().hide(); 
                                }else{
                                    if(!n.hasChildNodes()){
                                        n.getUI().hide(); 
                                    }
                                }
                            }
                        }
                        
                        Ext.each(filterNodesPath, function(filterNodePath){
                            var nodeIds = filterNodePath.split('/');
                            Ext.each(nodeIds,function(nodeId){
                                if("" != nodeId.trim()){
                                    var node = tree.getNodeById(String(nodeId.trim()));
                                    node.expand();
                                    node.getUI().show();
                                }
                            });
                        });
                        
                            
                            
//                        loader.doPreload(node);
                        
                       
                    },
                    //아마 이 밑으로는 2뎁스 까지 검색되는.... tbar 밑에다 만들 함수는 있는 뎁스만큼 검색되게 할게요
                    //선뜻 지우기가 애메해서
                    _filterNode:function(node){
                        var _this = this;
                        var searchText = this.searchField.getValue();
                        var loader = _this.getLoader();
                        var nodes = node.childNodes;
                   
                        if(Ext.isEmpty(node.childNodes)){
                            if(!Ext.isEmpty(node.parentNode)){
                                if(!_this._isRootNode(node.parentNode)){
                                    nodes = node.parentNode.childNodes;
                                }
                            }
                        }
                        
                            Ext.each(nodes, function(child){
                                var name = child.text;
                                if(name.toLowerCase().indexOf(searchText.toLowerCase()) != -1){
                                    child.getUI().show();
                                }else{
                                    child.getUI().hide();
                                };
                            });
                        loader.doPreload(node);
                    },
                    _filterNodeSearch: function(){
                        var _this = this;
                        var searchText = this.searchField.getValue();
                        var loader = _this.getLoader();
                        var node = _this._selectedNode();
                      
                        if(Array.isArray(node)){
                            var nodes = node;
                            Ext.each(nodes,function(node){
                                node.expand(true);
                                if(!node.isExpanded()){
                                    node.on('expand',function(self){
                                        _this._filterNode(self);
                                    });
                                }else{
                                    _this._filterNode(node);
                                }
                            });
                        }else{
                            node.expand(true);
                            if(!node.isExpanded()){
                                node.on('expand',function(self){
                                    _this._filterNode(self);
                                });
                            }else{
                                _this._filterNode(node);
                            }
                        }
                        
                    },
                    _selectedNode: function(){
                        var node = this.getSelectionModel().getSelectedNode();
                        
                        if(this._isRootNode(node)){
                            if(node.disable == true){
                                return node.childNodes[0].childNodes;
                            }
                            return node.childNodes;
                        }else{
                            return node;
                        }
                    },
                    _isRootNode: function(node){
                        var rootCheck = ["0",0,"100",100];
                        var isRoot = rootCheck.indexOf(node.id);
                        if(isRoot == -1){
                            return false;
                        }else{
                            return true;
                        }
                    },
                    _clickNodeFilter:function(node){
                        if(Ext.isEmpty(node.parentNode)){
                                return;
                            }
                            if(Ext.isEmpty(node.childNodes)){
                                return;
                            }
                            var parentNode = node.parentNode;
                            var nodes = parentNode.childNodes;
                            Ext.each(nodes,function(r){
                                if(!(r == node)){
                                    Ext.each(r.childNodes,function(r){
                                        r.getUI().show();
                                    });
                                };
                            });
                    }
                },
                new Ext.tree.TreePanel({
                    id:'yearly-tree',
                    loader:new Ext.tree.TreeLoader({
                        dataUrl: '/store/yearly-node.php',
                        listeners: {
                            beforeload: function (treeLoader, node, callback){
                            },
                            load: function (treeLoader, node, callback){
                                Ext.getCmp('yearly-tree').on('click',function(node){
                                    if(node.attributes.leaf){
                                        
                                        Ext.getCmp('search_input').setValue();
                                        var customSearchGrid = Ext.getCmp('nps_media__custom_search_grid');
                                        customSearchGrid.getSelectionModel().clearSelections();
                                        customSearchGrid.rowSelectedIndex = -1;
                                        var tagSearchGrid = Ext.getCmp('tag_search');
                                        tagSearchGrid.getSelectionModel().clearSelections();
                                        tagSearchGrid.rowSelectedIndex = -1;


                                        // 바텀 툴바 검색창
                                        //var searchBottomToolbar = Ext.getCmp('nps_media_search_bottom_toolbar');
                                        //var dateCombo = searchBottomToolbar.find('name', 'dateCombo')[0].getValue();
                                        
                                        //var statusCombo = searchBottomToolbar.find('name', 'statusCombo')[0].getValue();
                                        //var reviewStatusCombo = searchBottomToolbar.find('name', 'reviewStatusCombo')[0].getValue();
                                        //var archiveStatusCombo = searchBottomToolbar.find('name', 'archiveStatusCombo')[0].getValue();

                                        var contentTab = Ext.getCmp('tab_warp');
                                        var activeTab = contentTab.getActiveTab();

                            
                                        //var filters = {
                                        //    'content_status' : statusCombo,
                                         //   'content_review_status': reviewStatusCombo,
                                         //   'content_archive_status': archiveStatusCombo,
                                         //   'category_start_date': node.attributes.startDate,
                                         //   'category_end_date': node.attributes.endDate,
                                         //   'created_date': dateCombo
                                        //};

                                        //바텀툴바 검색창 수정후
                                        var filters = {

                                        };
                                        var searchToolbarBox = Ext.getCmp('tbarContainer');
                                        var searchTbar2 = searchToolbarBox.getComponent('toolbar2');
                                        var tbar2Fields = searchTbar2._getValueFields();
                                        Ext.each(tbar2Fields, function(field){
                                            filters[field.name || field.itemId] = field.getValue();
                                        });

                                        var searchTbar3 = searchToolbarBox.getComponent('toolbar3');
                                        var tbar3Fields = searchTbar3._getValueFields();
                                        Ext.each(tbar3Fields, function(field){
                                            filters[field.name || field.itemId] = field.getValue();
                                        });

                                        var searchTbar4 = searchToolbarBox.getComponent('toolbar4');
                                        var tbar4Fields = searchTbar4._getValueFields();
                                        Ext.each(tbar4Fields, function(field){
                                            filters[field.name || field.itemId] = field.getValue();
                                        });
                                        var newParams = {
                                            list_type: 'common_search',
                                            filter_value: '/0',
                                            //search_tbar: Ext.encode(search_tbar),
                                            filters: Ext.encode(filters)
                                        }
                                
                                        //activeTab.reload( newParams );
                                        doSimpleSearch();
                                    }

                                    return false;
                                });
                            }   
                        }
                    }),
                    cls:'yearly-tree',
                    root: {
                        text: '연도 별',
                        nodeType: 'async',
                        expanded: true,
                    }
                })
                ]
            }]
        }];

        Ariel.nav.MainPanel.superclass.initComponent.call(this);
    }
});

<?php
// 사용자 정보에서 노출되면 안되는 항목 숨김   
$emailFieldHidden = 'false';
$phoneFieldHidden = 'false';
if(defined('CUSTOM_ROOT') && class_exists('\ProximaCustom\core\UserInfoCustom'))
{    
    $emailFieldHidden = \ProximaCustom\core\UserInfoCustom::EmailFieldVisible() ? 'false' : 'true';
    $phoneFieldHidden = \ProximaCustom\core\UserInfoCustom::PhoneFieldVisible() ? 'false' : 'true';    
}

echo "var emailFieldHidden = {$emailFieldHidden},\n
    phoneFieldHidden = {$phoneFieldHidden};";
?>

//Home 사용자 정보
Ariel.nav.Main_WestPanel = Ext.extend(Ext.Panel, {

    id: 'menu-change-info',
    region: 'west',
    //layout: {
        //type: 'vbox',
        //align: 'stretch'
    //},
	layout : 'fit',
	border:true,
    defaults : {
        //split: true
    },

    initComponent: function(){

        this.items = [{
                xtype : 'form',
                padding: '0px 10px 10px 10px',
                id: 'show_user_info',
                bodyStyle:{"background-color":"#eaeaea"},
                <!-- title : '<center><span class="user_span"><span class="icon_title"><i class="fa fa-user"></i></span><span class="user_title">'+_text('MN02125')+'</span></span><span title="'+_text('MN00043')+'" class="icon_title2" onclick="show_user_modifiy_information(\'<?=$user_id?>\');"><i class="fa fa-cog"></i></span></center>', -->
                title : '<center><span class="user_span"><span class="icon_title"><i class="fa fa-user"></i></span><span class="user_title">'+_text('MN02125')+'</span></span><span title="'+_text('MN00043')+'" class="icon_title2" onclick="showUserModifiyInformation()"><i class="fa fa-cog"></i></span></center>',
                autoScroll: true,
				border:false,
				frame : false,
				labelWidth: 25,
				//height:300,
                defaults: {
                    xtype: 'displayfield',
					labelSeparator: '',
					labelAlign:'top',
					labelStyle: 'text-align:center;display: inline-block;line-height: 1;'
                },
				cls: 'main_user',

                items : [{
                    fieldLabel :'',
		            hideLabel:true,
		            //html :'<center><div style="width:120px;height:100px;border:1px solid #aaa;margin-bottom:10px;">Img</div></center>',
					hidden:false
                },{
					hideLabel: true,
					//html: '<div style="text-align:center;"><img width=100 src="/css/h_img/Icon-user.png" style="  border-style: dotted; border-widh: 2px;  border-radius: 10px;  padding: 10px;  border-color: #e2e2e2;"></div>',
					//html : '<div style="display: table; width: 100px;"><div style="display: table-cell; text-align: center;"><img src="/css/h_img/Icon-user.png"  style="width:100px;border:2px dotted #e2e2e2;border-radius:10px;padding-top:8px;" /></div></div>',
					html : '<div style="display: table; width:100px;margin: 0 auto;margin-left: 60px;"><div style="display: table-cell; text-align: center;"><img src="/css/h_img/Icon-user.png"  style="width:100px;border:2px dotted #e2e2e2;border-radius:10px;padding-top:8px;" /></div></div>',
					height:125
				},{
                    fieldLabel : '<i class="fa fa-user"></i>',//이름
                    id : 'info_user_nm',
					hidden:false
                },{
                    fieldLabel : 'ID',
                    id : 'info_user_id',
                    cls: 'user_info_text_ellipsis',
					hidden:true
                },{
                    hidden: emailFieldHidden,
                    fieldLabel : '<i class="fa fa-phone"></i>',
                    cls: 'user_info_text_ellipsis',
                    id : 'info_user_phone'
                },{
                    hidden: emailFieldHidden,
                    fieldLabel : '<i class="fa fa-envelope"></i>',
                    cls: 'user_info_text_ellipsis',
                    id : 'info_user_email'
                },{
                    fieldLabel : '<i class="fa fa-building"></i>',
                    cls: 'user_info_text_ellipsis',
                    id : 'info_user_dept',
					height:35
                },{
                    hidden: true,
                    fieldLabel : '제작',
                    id : 'info_user_program'
                }]
            //}]
        }];

                this.on('render', this.init, this);
        Ariel.nav.Main_WestPanel.superclass.initComponent.call(this);
    },

        init: function() {
            var user_id = '<?=$user_id?>';

            Ext.Ajax.request({
                    url : '/store/get_myInfo.php',
                    params : {
                            user_id : user_id
                    },
                    callback : function(opts, success, response){

                        var r = Ext.decode(response.responseText);

                        if (success) {
                            Ext.getCmp('info_user_nm').setValue(r.data.user_nm);
                            Ext.getCmp('info_user_id').setValue(user_id);
                            Ext.getCmp('info_user_phone').setValue(r.data.phone);
                            Ext.getCmp('info_user_email').setValue(r.data.email);
                            Ext.getCmp('info_user_dept').setValue(r.data.dept_nm);
                            Ext.getCmp('info_user_program').setValue(r.data.programs);
                        }
                    }
            });
        }
});

function actionCallback(opts, success, response){
    var o = {}, store, record;

    if(true != success){
        Ext.Msg.show({
            //>>title: '실패',
            title: _text('MN00012'),
            msg: '',
            buttons: Ext.Msg.OK,
            closable: false
        });
        return;
    }
    try {
        result = Ext.decode(response.responseText);
        if (result && true == result.success) {


            switch (opts.params.action) {

                case 'create-folder':
                    var n = opts.node;
                    n.setId(result.id);

                    delete n.attributes.isNew;
                break;

                case 'golast':
                    var n = opts.node;
                break;

                case 'change-category':

                    Ext.getCmp('tab_warp').getActiveTab().reload();
                break;
            }
        } else {
            //>>Ext.Msg.alert('오류', result.msg);

            switch (opts.params.action) {
                case 'create-folder':
                    var n = opts.node;

                     n.remove(true);
                break;

                case 'rename-folder':
                    var n = opts.node;
                break;

                case 'change-category':
                    Ext.getCmp('tab_warp').getActiveTab().reload();
                break;
            }
            Ext.Msg.alert(_text('MN00022'), result.msg);
        }

        if(Ext.getCmp('topic-tree')) {
            var root = Ext.getCmp('topic-tree').getRootNode();
            Ext.getCmp('topic-tree').getLoader().load(root);
        }
    } catch(e) {
        Ext.Msg.show({
            //>>title: '오류',
            title: _text('MN00022'),
            msg: e,
            buttons: Ext.Msg.OK,
            closable: false
        })
    }
}

// ------------------------------------------------------------------------------------------------------------------


function createFolder(node){
    Ext.Ajax.request({
        url: '/store/add_category.php',
        node: node,
        params: {
            action: 'create-folder',
            parent_id: node.parentNode.attributes.id,
            title: node.attributes.text
        },
        callback: function(opts, success, response){
            var o = {}, store, record;
            if(true != success){
                Ext.Msg.show({
                    //>>title: '실패',
                    title: _text('MN00012'),
                    msg: '',
                    buttons: Ext.Msg.OK,
                    closable: false
                });
                return;
            }
            try {
                result = Ext.decode(response.responseText);
                if (result && true == result.success) {
                    switch (opts.params.action) {

                        case 'create-folder':
                            var n = opts.node;
                            n.setId(result.id);
                            n.setText(result.title);


                            delete n.attributes.isNew;
                        break;
                    }

                    if(Ext.getCmp('topic-tree')) {
                        var root = Ext.getCmp('topic-tree').getRootNode();
                        Ext.getCmp('topic-tree').getLoader().load(root);
                    }
                } else {
                    //>>Ext.Msg.alert('오류', result.msg);

                    switch (opts.params.action) {
                        case 'create-folder':
                            var n = opts.node;
                             n.remove(true);
                        break;
                    }
                    Ext.Msg.alert(_text('MN00022'), result.msg);
                }
            } catch(e) {
                Ext.Msg.show({
                    //>>title: '오류',
                    title: _text('MN00022'),
                    msg: e,
                    buttons: Ext.Msg.OK,
                    closable: false
                })
            }
        }
    })
}

function createFavoriteFolder(node){
        Ext.Ajax.request({
        url: '/store/add_favorite_category.php',
        node: node,
        params: {
            action: 'create-folder',
            title: node.attributes.text,
                        content_type: node.attributes.favorite
        },
        callback: function(opts, success, response){
            var o = {}, store, record;
            if(true != success){
                Ext.Msg.show({
                    //>>title: '실패',
                    title: _text('MN00012'),
                    msg: '',
                    buttons: Ext.Msg.OK,
                    closable: false
                });
                return;
            }
            try {
                result = Ext.decode(response.responseText);
                if (result && true == result.success) {
                    switch (opts.params.action) {

                        case 'create-folder':
                            var n = opts.node;
                            n.setId(result.id);
                            n.setText(result.title);


                            delete n.attributes.isNew;
                        break;
                    }
                } else {
                    //>>Ext.Msg.alert('오류', result.msg);

                    switch (opts.params.action) {
                        case 'create-folder':
                            var n = opts.node;
                             n.remove(true);
                        break;
                    }
                    Ext.Msg.alert(_text('MN00022'), result.msg);
                }
            } catch(e) {
                Ext.Msg.show({
                    //>>title: '오류',
                    title: _text('MN00022'),
                    msg: e,
                    buttons: Ext.Msg.OK,
                    closable: false
                })
            }
        }
    })
}

function renameFavoriteFolder(node, newName, oldName){
    Ext.Ajax.request({
        url: '/store/add_favorite_category.php',
        node: node,
        newName: newName,
        oldName: oldName,
        callback: actionCallback,
        params: {
            action: 'rename-folder',
            id: node.attributes.id,
                        content_type: node.attributes.favorite,
            newName: newName,
            oldName: oldName
        }
    })
}


function renameFolder(node, newName, oldName){
    Ext.Ajax.request({
        url: '/store/add_category.php',
        node: node,
        newName: newName,
        oldName: oldName,
        callback: actionCallback,
        params: {
            action: 'rename-folder',
            id: node.attributes.id,
            parent_id: node.parentNode.attributes.id,
            newName: newName,
            oldName: oldName
        }
    })
}

function selectCategory(){
}
