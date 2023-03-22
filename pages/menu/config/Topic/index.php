<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

$mode = $_REQUEST['mode'];

if($mode == 'nle') {
	$nle_hidden = " hidden: true, ";
	$nle_option = "true";
	$page_mode = 'nle';
} else {
	$nle_option = "false";
	$page_mode = 'manage';
}

?>

(function(){
	var topicSearchWin = new Ext.Panel({
		xtype: 'panel',
		width: 850,
		height: 550,
		modal: true,
		layout: 'fit',
		items: [{
			id: 'topic-tree-system',
			xtype: 'treegrid',
			layout : 'fit',
			loader: new Ext.ux.tree.TreeGridLoader({
				dataUrl: '/store/get_tree_grid_data.php',
				baseParams: {
					page_mode: '<?=$page_mode?>'
				},
            }),
			isLoading: false,
			columns : [
				{header : '토픽', dataIndex : 'title', width:140},
				{header : '남은 만료일', dataIndex : 'remain_expired_date', width: 90, align: 'center'},
				{header : '사용 만료일자', dataIndex : 'expired_date', width: 90, align: 'center'},
				{header : '승인여부', dataIndex : 'status', width: 90, align: 'center'},
				{header : '방송 예정일', dataIndex : 'broad_date', width: 90, align: 'center'},
				{header : '내용', dataIndex : 'contents', width: 250, align: 'center'}
			],
			selModel: new Ext.tree.MultiSelectionModel({
			}),
			reloadTree: function(){
				var root = this.getRootNode();
				var that = this;
				if(!that.isLoading) {
					that.isLoading = true;
					that.getLoader().load(root, function(){
						that.isLoading = false;
					});
				}
			},
			tbar: [
			{
				xtype: 'button',
				icon: '/led-icons/arrow_refresh.png',
				//MN00059 조회
				text: '새로고침',
				handler: function(b, e){
					Ext.getCmp('topic-tree-system').reloadTree();
				}
			},{
				xtype: 'button',
				<?=$nle_hidden?>
				icon: '/led-icons/accept.png',
				text: '승인',
				handler: function(b, e) {
					var sm = Ext.getCmp('topic-tree-system').getSelectionModel();
					if( sm.selNodes.length < 1 ) {
						Ext.Msg.alert( _text('MN00023'), '항목을 선택 해 주시기 바랍니다.');
						return;
					}

					var category_id = [];
					Ext.each(sm.selNodes, function(i){
						category_id.push(i.id);
					});

					Ext.Ajax.request({
						url: '/pages/menu/config/Topic/action.php',
						params: {
							'category_ids[]': category_id,
							action: 'accept'
						},
						callback: function(opt, success, response){
							Ext.getCmp('topic-tree-system').reloadTree();
						}
					});
				}
			},{
				xtype: 'button',
				<?=$nle_hidden?>
				icon: '/led-icons/cross.png',
				text: '반려',
				handler: function(b, e){
					var sm = Ext.getCmp('topic-tree-system').getSelectionModel();
					if( sm.selNodes.length < 1 ) {
						Ext.Msg.alert( _text('MN00023'), '항목을 선택 해 주시기 바랍니다.');
						return;
					}

					var category_id = [];
					Ext.each(sm.selNodes, function(i){
						category_id.push(i.id);
					});

					Ext.Ajax.request({
						url: '/pages/menu/config/Topic/action.php',
						params: {
							'category_ids[]': category_id,
							action: 'decline'
						},
						callback: function(opt, success, response){
							Ext.getCmp('topic-tree-system').reloadTree();
						}
					});
				}
			}],
			contextMenu: new Ext.menu.Menu({
				items: [{
					cmd: 'topic-refresh',
					text: '새로고침',
					icon: '/led-icons/arrow_refresh.png'
				},'-',{
					cmd: 'topic-add',
					hidden: true,
					text: '토픽 추가',
					hidden: true,
					icon: '/led-icons/application_add.png'
				},{
					cmd: 'topic-edit',
					text: '토픽 수정',
					hidden: true,
					icon: '/led-icons/application_edit.png'
				},{
					cmd: 'topic-del',
					text: '토픽 삭제',
					hidden: true,
					icon: '/led-icons/application_delete.png'
				}],
				listeners: {
					itemclick: {
						fn: function(item, e){
							var self = Ext.getCmp('topic-tree-system');
							var node = item.parentMenu.contextNode;
							switch (item.cmd) {
								case 'topic-refresh':
									var root = self.getRootNode();
									self.getLoader().load(root);
								break;
								case 'topic-add':
									self.topicDetail(self, 'add');
								break;
								case 'topic-edit':
									self.topicDetail(self, 'edit');
								break;
								case 'topic-del':
									self.topicDelete(node);
								break;
							}
						},
						scope: this
					}
				}
			}),
			listeners: {
				contextmenu: function(node, e) {
					node.select();
					var c = node.getOwnerTree().contextMenu;

					c.items.each( function(i){
						if( i.cmd == 'topic-add' )
						{
							i.setVisible( node.attributes.topic_add );
						}
						else if( i.cmd == 'topic-edit' )
						{
							i.setVisible( node.attributes.topic_edit);
						}
						else if( i.cmd == 'topic-del' )
						{
							i.setVisible( node.attributes.topic_del );
						}
					});

					c.contextNode = node;
					c.showAt(e.getXY());
//					node.select();
//
//					var c = node.getOwnerTree().contextMenu;
//					c.items.each(function(i){
//						i.show();
//					});
//
//					c.contextNode = node;
//					c.showAt(e.getXY());
				},
				click: function(node, e){
					if ( Ext.isEmpty(node) || node.attributes.isNew ) return;

					Ext.getCmp('topic-tree-system').getLoader().baseParams.beforePath = node.getPath();
				}
			},
			makeParam: function(node){
				// 검색어가 입력 되어 있을 경우 카테고리가 바껴도 검색어를 계속 유지하도록 하기 위해 수정
				var search_q = Ext.getCmp('search_input').getValue();
				var params = {};

				if( node.id == '-1' ) {
					params = {
						filter_type: 'topic_root',
						search_q: search_q
					};
				} else if( node.id.search('-') > 0 ) {
					params = {
						filter_type: 'topic_content_id',
						topic_content_id: node.attributes.content_id,
						search_q: search_q
					};
				} else {
					params = {
						filter_type: 'topic_category',
						topic_category: node.id,
						search_q: search_q
					};
				}

				return params;
			},
			topicDetail: function(self, mode){
//				var sm = self.getSelectionModel();
//				if( Ext.isEmpty(sm.getSelectedNode()) ) {
//					Ext.Msg.alert( _text('MN00023'), '항목을 선택 해 주시기 바랍니다.');
//					return;
//				}
				var sm = Ext.getCmp('topic-tree-system').getSelectionModel();
				if( sm.selNodes.length != 1 ) {
					Ext.Msg.alert( _text('MN00023'), '항목 하나를 선택 해 주시기 바랍니다.');
					return;
				}

				var node = sm.selNodes[0];
				var category_id = node.id;
				if( Ext.isEmpty(category_id) ) {
					return;
				}
				var params = {};

				if(mode == 'add') {
					params = {parent_category_id: category_id};
				} else {
					params = {category_id: category_id};
				}

				//>>self.load = new Ext.LoadMask(Ext.getBody(), {msg: '상세 정보를 불러오는 중입니다...'});
				self.load = new Ext.LoadMask(Ext.getBody(), {msg: _text('MSG00143')});
				self.load.show();
				var that = self;

				if ( !Ext.Ajax.isLoading(self.isOpen) )
				{
					self.isOpen = Ext.Ajax.request({
						url: '/javascript/ext.ux/Detailpanel/topic.php',
						params: params,
						callback: function(self, success, response){
							if (success)
							{
								that.load.hide();
								try
								{

									var r = Ext.decode(response.responseText);

									if ( r !== undefined && !r.success)
									{
										Ext.Msg.show({
											title: '경고'
											,msg: r.msg
											,icon: Ext.Msg.WARNING
											,buttons: Ext.Msg.OK
										});
									}
								}
								catch (e)
								{
									//alert(response.responseText)
									//Ext.Msg.alert(e['name'], e['message'] );
								}
							}
							else
							{
								//>>Ext.Msg.alert('서버 오류', response.statusText+'('+response.status+')');
								Ext.Msg.alert(_text('MN00022'), response.statusText+'('+response.status+')');
							}
						}
					});
				} else {
                    that.load.hide();
                }
			},
			topicDelete: function(node){
				if(node.hasChildNodes()){
					Ext.Msg.show({
						title: _text('MN00023'),
						msg: '하위 토픽부터 삭제해주세요.',
						buttons: Ext.Msg.OK,
						closable: false
					});
					return;
				}

				Ext.Msg.show({
					//>>title: '삭제',
					title: _text('MN00034'),
					//>> msg: '\''+node.text+'\''+' 카테고리를 삭제 하시겠습니까?',
					msg: '토픽을 삭제 하시겠습니까( '+node.text+' )?',
					buttons: Ext.Msg.YESNO,
					closable: false,
					icon: Ext.Msg.QUESTION,
					fn: function(btnID){
						if(btnID === 'yes'){
							Ext.Ajax.request({
								url: '/store/delete_topic.php',
								params: {
									parent_id: node.parentNode.attributes.id,
									id: node.attributes.id
								},
								callback: function(opt, success, response) {
									var r = Ext.decode(response.responseText);

									if ( r !== undefined && !r.success)
									{
										Ext.Msg.show({
											title: '오류'
											,msg: r.msg
											,icon: Ext.Msg.WARNING
											,buttons: Ext.Msg.OK
										});
									}

									if( !Ext.isEmpty(Ext.getCmp('topicSearchGrid')) ){
										Ext.getCmp('topicSearchGrid').getStore().reload();
									}
									Ext.getCmp('topic-tree-system').reloadTree();
								}
							});
						}
					}
				})
			}
		}]
	});

	return topicSearchWin;
})()