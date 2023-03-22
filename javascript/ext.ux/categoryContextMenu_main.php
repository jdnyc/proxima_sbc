<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lang.php');

$category = $db->queryRow("select * from bc_category where category_id=0 order by show_order");
$ud_content_id = $_POST['ud_content_id'];

$category_grant_array = array(
	'read' => 0,
	'add' => 0,
	'edit' => 0,
	'del' => 0,
	'hidden' => 0
);

if( !empty($_SESSION['user']['groups']) ) {
	$category_grant = categoryGroupGrant($_SESSION['user']['groups']);
	$category_grant_array = set_category_grant($category['category_id'], $category_grant, $category_grant_array, $ud_content_id);
}
$root_category = "
	id : '".$category['category_id']."',

	text : '".$category['category_title']."',
	read : ".$category_grant_array['read'].",
	add : ".$category_grant_array['add'].",
	edit : ".$category_grant_array['edit'].",
	del : ".$category_grant_array['del'].",
	hidden : ".$category_grant_array['hidden']."
";
?>

Ext.ns('Ariel.nav');
Ext.ns('Ariel.MainCategory');

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
					Ext.Msg.alert('<?=_text('MN00024')?>', ret.msg);
				}
			}
		}
	})
}

Ariel.MainCategory = Ext.extend(Ext.tree.TreePanel, {
	region: 'center',
	id: 'menu-tree',
	tabCls: 'x-panel_nav_week',
	iconCls: 'mainnav-category',
	//>>title: '카테고리 별',MN00271
//	title: _text('MN00271'),
	autoScroll: true,
	enableDD: true,
	animate: true,
	//enableDrag: true,
	//enableDrop: false,
	rootVisible: true,
	containerScroll: true,
	autoLoad: false,
	bodyStyle: {
		fontSize: '15px'
	},
	



	initComponent: function(config){
		Ext.apply(this, {

			listeners: {
				afterrender: function( c ){

				},
				beforeappend: function( tree, parent, node ){

					if( !node.attributes.read )
					{
						node.disable(true);
					}

					if( node.attributes.hidden )
					{
						node.hidden = true;
					}
				},
				click: function(node, e){
					//console.log('tree click');
					if ( Ext.isEmpty(node) || node.attributes.isNew ) return;

					if( !node.attributes.read ) return;

					var params = {
						filter_type: 'category',
						filter_value: node.getPath()
					}

					Ext.get('search_input').dom.value = '';
/*
					Ext.getCmp('tab_warp').items.each(function(item){
						item.setTitle(item.initialConfig.title);
					});

					var at = Ext.getCmp('tab_warp').getActiveTab();
					at.reload( params );
*/
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
						//console.log('트리 카테고리 이동 , 정렬');

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
							
							if( e.target.id == e.dropNode.parentNode.id )
							{
								//console.log('정렬');
							}
							else
							{
								//console.log('이동');

								if(!e.dropNode.attributes.leaf)
								{
									return false;
								}
							}							
							
						}
						else if(e.point == 'below' || e.point == 'above' ) //노드의 위치이동
						{
							if( e.target.parentNode.id == 0 )
							{

							}
							else if( !e.target.parentNode.attributes.edit )//타겟노드의 권한여부
							{
								return false;
							}

							if( e.target.parentNode.id == e.dropNode.parentNode.id )
							{
								//console.log('정렬');
							}
							else
							{
								//console.log('이동');

								if(!e.dropNode.attributes.leaf)
								{
									return false;
								}
							}

						}
						else
						{
							return false;
						}						

					}
					else//콘텐츠 그리드에서 콘텐츠들의 카테고리 변경
					{					
						//console.log('콘텐츠 카테고리 변경');

						var node_id= e.target.id;
						var node_title = e.target.text;
						var node_path = e.target.getPath();
						var count = e.data.selections.length;

						var selections = e.data.selections;

						if(!e.target.attributes.read)
						{
							return false;
						}						
						
						var contents = new Array();

						Ext.each(selections , function(i){
							contents.push(i.id);
						});

						Ext.Msg.show({														
							title: _text('MN00024'),//확인
							icon: Ext.Msg.INFO,								
							msg: count+'개 콘텐츠의 카테고리를'+'</br >'+node_title+'(으)로'+'</br >'+'변경하시겠습니까?',
							buttons: Ext.Msg.OKCANCEL,
							fn: function(btnID, text, opt) {
								if(btnID == 'ok') {
									Ext.Ajax.request({
										url: '/store/add_category.php',
										callback: actionCallback,
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
			loader: new Ext.tree.TreeLoader({			
				url: '/store/get_categories.php',
				listeners: {
					beforeload: function (treeLoader, node, callback){
					
						if (!treeLoader.loaded && !treeLoader.baseParams.ud_content_id)
						{
						//	treeLoader.baseParams.ud_content_id = Ext.getCmp('tab_warp').get(0).ud_content_id;
						}
						
						treeLoader.baseParams.action = "get-folders";
						treeLoader.baseParams.read = node.attributes.read;
						treeLoader.baseParams.add = node.attributes.add;
						treeLoader.baseParams.edit = node.attributes.edit;
						treeLoader.baseParams.del = node.attributes.del;
						treeLoader.baseParams.hidden = node.attributes.hidden;						

						treeLoader.baseParams.beforePath = node.attributes.beforePath;
					},
					load: function (treeLoader, node, callback){
					
						if (treeLoader.baseParams.ud_content_id)
						{
							treeLoader.loaded = true;
							delete treeLoader.baseParams.ud_content_id;
						}						

						var beforePath =  node.attributes.beforePath;

						if( Ext.isEmpty(beforePath) )
						{
							if( !Ext.getCmp('menu-tree').getRootNode().attributes.read)
							{
								beforePath = Ext.getCmp('menu-tree').getRootNode().findChild('read', 1).getPath();
							}
							else
							{
								beforePath = Ext.getCmp('menu-tree').getRootNode().getPath();
							}
						}					

						Ext.getCmp('menu-tree').selectPath( beforePath );						
					}
				}
			}),
			root: new Ext.tree.AsyncTreeNode({
				<?=$root_category?>
			})
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
					self.editNode.setText(newValue);
					if (self.editNode.attributes.isNew) {
						createFolder(self.editNode);
					} else {
						renameFolder(self.editNode, newValue, oldValue);
					}

					return true;
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
		},{
			cmd: 'bookmark',
			hidden: true,
			//>>text: '즐겨찾기 추가',
			text: _text('MN00256'),
			icon: 'led-icons/star_2.png'
		}],
		listeners: {
			itemclick: {
				fn: function(item, e){
					var r = item.parentMenu.contextNode.getOwnerTree();
					switch (item.cmd) {
						case 'add-node':
							r.invokeCreateFolder(item.parentMenu.contextNode);
						break;

						case 'edit-node':
							r.editor.triggerEdit(item.parentMenu.contextNode);
						break;

						case 'delete-node':
							r.deleteFolder(item.parentMenu.contextNode);
						break;

						case 'bookmark':
							var c = Ext.getCmp('menu-tree').getSelectionModel().getSelectedNode().attributes;
							add_favorite_category(Ext.getCmp('menu-tree').getSelectionModel().getSelectedNode().getPath(), c.text);
						break;
					}
				},
				scope: this
			}
		}
	}),

	contextMenuAddFavorite: new Ext.menu.Menu({
		items: [{
			cmd: 'bookmark',
			// text: '즐겨찾기 추가',
			text: _text('MN00256'),
			icon: 'led-icons/star_2.png'
		}],
		listeners: {
			itemclick: {
				fn: function(item, e){
					var r = item.parentMenu.contextNode.getOwnerTree();
					switch (item.cmd) {
						case 'bookmark':
							var c = Ext.getCmp('menu-tree').getSelectionModel().getSelectedNode().attributes;
							add_favorite_category(Ext.getCmp('menu-tree').getSelectionModel().getSelectedNode().getPath(), c.text);
						break;
					}
				},
				scope: this
			}
		}
	}),

	onContextMenu: function(node, e){
		node.select();
		var c = node.getOwnerTree().contextMenu;

		c.items.each( function(i){
			if( i.cmd == 'add-node' )
			{
				i.setVisible( node.attributes.add );
			}
			else if( i.cmd == 'edit-node' )
			{
				i.setVisible( node.attributes.edit );
			}
			else if( i.cmd == 'delete-node' )
			{
				i.setVisible( node.attributes.del );
			}
		});



		if( node.attributes.add || node.attributes.edit || node.attributes.del )
		{
			c.contextNode = node;
			c.showAt(e.getXY());
		}

	},

	invokeCreateFolder: function(node){
		node.leaf = false;
		node.expand(false, true, function(node){
			var newNode = node.appendChild(new Ext.tree.TreeNode({
				// text: '새 이름',
				text: _text('MN00140'),
				cls: 'folder',
				read: 1,
				add: 1,
				edit: 1,
				del: 1,
				leaf: true,
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
						}
					});
					node.parentNode.leaf = true;
					node.remove();
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

		var returnVal;

//		node.eachChild(function(i){
//
//			if( i.attributes.read == 1 )
//			{
//				console.log(i.attributes.read);
//				nav.fireEvent('click', i, {});
//				exit;
//			}
//		});

		return;
	}
});
Ext.reg('navcategory', Ariel.MainCategory);
/*
Ariel.nav.DateCategory = Ext.extend(Ext.tree.TreePanel, {
	id: 'nav_program',
    tabCls: 'x-panel_nav_week',
	//>>title: '프로그램 별',
	title: _text('MN00321'),
	autoScroll: true,
	rootVisible: false,
	loader: new Ext.tree.TreeLoader({
		dataUrl: '/store/get_category_program.php'
	}),
	root: {
		//>>text: '프로그램',
		text: _text('MN00322'),
		expanded: true
	},

	initComponent: function(){

		Ariel.nav.DateCategory.superclass.initComponent.call(this);

		new Ext.tree.TreeSorter(this, {
			folderSort: true
//			,dir: "desc"
//			,sortType: function(node) {
//				// sort by a custom, typed attribute:
//				return parseInt(node.id, 10);
//			}
		});

		this.on('click', function(n, e){

			var p = Ext.getCmp('tab_warp').getActiveTab();
			var _v = n.getPath('text').substr(6);
			var params = {
				sort: 'created_time',
				dir: 'desc',
				action: 'program',
				value: _v
			};

			p.reload(params);
		});
	}
})
Ext.reg('navdate', Ariel.nav.DateCategory);
*/
Ariel.nav.MainPanel1 = Ext.extend(Ext.Panel, {
	title : '제작 프로그램',
	id: 'nav_tab',
	region: 'center',
//	activeTab: 0,
	defaults: {
		border: false
	},
	border:false,
	initComponent: function(){
		this.items = [{
			xtype: 'navcategory'
		}]

		Ariel.nav.MainPanel1.superclass.initComponent.call(this);

		this.on('tabchange', function(self, p){

			Ext.get('search_input').dom.value = '';

//			if (!p.getSelectionModel().getSelectedNode()) {
//				p.getSelectionModel().select(p.getRootNode());
//			}
		})
	}
})


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
		callback: actionCallback
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
			newName: newName,
			oldName: oldName
		}
	})
}

function selectCategory(){
}