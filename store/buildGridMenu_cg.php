<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lang.php');

$user_id		= $_SESSION['user']['user_id'];
$content_id		= $_POST['content_id'];
$content_info		= $db->queryRow("select * from view_content where content_id='$content_id'");
$ud_content_id		= $content_info['ud_content_id'];
$client_ip              = $_SERVER['REMOTE_ADDR'];
?>
[{
	icon:'/led-icons/control_wheel.png',
	hidden: 'true',
	text: '전송 의뢰',
	menu: [
        <?php
            if($ud_content_id == 4000365 ) {
                echo "{ icon:'/led-icons/drive_go.png',text: '주조 전송',handler: function(b, e){b.parentMenu.initialConfig.ownerCt.request_transfer($content_id);} },";
            }
        ?>
                { icon:'/led-icons/drive_go.png',text: '부조 전송',menu: [
                        // IP 대역대가 상암일 경우만
                        <?php
                            if(strpos($client_ip, '192.168.10.') !== false) {
                        ?>
                            { icon:'/led-icons/drive_go.png',text: '대형부조로 전송',handler: function(b, e){b.parentMenu.initialConfig.ownerCt.messageAlert();} },
                            { icon:'/led-icons/drive_go.png',text: '중형부조로 전송',handler: function(b, e){b.parentMenu.initialConfig.ownerCt.messageAlert();} },
                            { icon:'/led-icons/drive_go.png',text: '소형부조로 전송',handler: function(b, e){b.parentMenu.initialConfig.ownerCt.messageAlert();} }
                        <?php
                            } else {
                        ?>
                            // IP 대역대가 광화문일 경우만
                            { icon:'/led-icons/drive_go.png',text: '다목적부조로 전송',handler: function(b, e){b.parentMenu.initialConfig.ownerCt.messageAlert();} },
                            { icon:'/led-icons/drive_go.png',text: '뉴스부조로 전송',handler: function(b, e){b.parentMenu.initialConfig.ownerCt.messageAlert();} }
                        <?php
                            }
                        ?>
                ]},
		{ icon:'/led-icons/drive_go.png',text: '아카이브 의뢰',handler: function(b, e){b.parentMenu.initialConfig.ownerCt.request_archive();} },
                { icon:'/led-icons/drive_go.png',text: '광화문제작 등록의뢰',handler: function(b, e){b.parentMenu.initialConfig.ownerCt.request_cis_transfer();} }
	],
	changeType : function(type){
		var sm = Ext.getCmp('tab_warp').getActiveTab().get(0).getSelectionModel();
		var records = sm.getSelections();
		var rs=[];

		var is_working = false;
		var is_empty = false;

		Ext.each(records, function(r){

			if ( r.get('is_working') == 1 ) is_working = true;
			if ( r.get('ori_status') == 1 ) is_empty = true;

			rs.push(r.get('content_id'));
		});

		if(is_working)
		{
			Ext.Msg.alert( _text('MN00023'), '원본에 대한 작업중입니다.' );
			return;
		}


		Ext.Msg.show({
			icon: Ext.Msg.QUESTION,
			title: '확인',
			msg: '전송하시겠습니까?',
			buttons: Ext.Msg.OKCANCEL,
			fn: function(btnId, text, opts){
				if(btnId == 'cancel') return;
				Ext.Ajax.request({
					url: '/store/nps_work/to_tm.php',
					params: {
						target_ud_content_id : type,
						content_ids : Ext.encode(rs)
					},
					callback: function(self, success, response){
						if (success){
							try{
								var result = Ext.decode(response.responseText);
								Ext.Msg.alert( _text('MN00023'), result.msg );
								Ext.getCmp('tab_warp').getActiveTab().get(0).getStore().reload();
							}
							catch (e){
								Ext.Msg.alert(e['name'], e['message'] );
							}
						}else{
							Ext.Msg.alert('서버 오류', response.statusText+'('+response.status+')');
						}
					}
				});
			}
		});
	},
        request_transfer : function(content_id) {
			var sm = Ext.getCmp('tab_warp').getActiveTab().get(0).getSelectionModel();
			var record = sm.getSelected();
			if( Ext.isEmpty(record) || Ext.isEmpty(record.data) ) return;

           var req_win = new Ext.Window({
                    title: '주조 전송 의뢰',
                    width: 400,
                    height: 400,
                    layout: 'fit',

                    items: [{
                        xtype: 'form',
                        padding: 5,
                        labelWidth: 70,
                        labelAlign: 'right',
                        defaults: {
                            xtype:'textfield',
                            readOnly: true,
                            width:'90%'
                        },
                        items: [{
                            xtype:'hidden',
							name: 'content_id',
							value: content_id
                        },{
                            xtype:'hidden',
							name: 'type',
							value: 'master'
                        },{
                            fieldLabel : '프로그램',
							name: 'usr_progid',
							value: record.data.usr_progid
                        },{
                            fieldLabel : '소재ID',
							name: 'usr_materialid',
							value: record.data.usr_materialid
                        },{
                            fieldLabel : '회차',
							name: 'usr_turn',
							value: record.data.usr_turn
                        },{
                            fieldLabel : '의뢰시간',
							name: 'req_time',
							value: new Date().format('Y-m-d H:i:s')
                        },{
                            xtype: 'textarea',
							name: 'req_comment',
                            readOnly: false,
                            fieldLabel : '의뢰내용'
                        },{
                            fieldLabel : '담당 PD',
							name: 'usr_producer',
                            readOnly: false,
							value: record.data.usr_producer
                        },{
                            fieldLabel : '연락처',
							name: 'phone',
                            readOnly: false
                        }]
                    }],
                    buttonAlign: 'center',
                    buttons: [{
                        text: '의뢰',
						handler: function(b,e){
							var values = b.ownerCt.ownerCt.get(0).getForm().getValues();

							Ext.Ajax.request({
								url: '/store/nps_work/master_request.php',
								params: values,
								callback: function(self, success, response){
									if (success){
										try{
											var result = Ext.decode(response.responseText);
											Ext.Msg.alert( _text('MN00023'), result.msg );
											b.ownerCt.ownerCt.close();
										}
										catch (e){
											Ext.Msg.alert(e['name'], e['message'] );
										}
									}else{
										Ext.Msg.alert('서버 오류', response.statusText+'('+response.status+')');
									}
								}
							});
						}
                    },{
                        text: '닫기',
						handler: function(b,e){
							b.ownerCt.ownerCt.close();
						}
                    }]
            }).show();
        },

        request_archive: function() {
           var req_win = new Ext.Window({
                    title: '아카이브 의뢰',
                    width: 300,
                    height: 200,
                    layout: 'fit',

                    items: [{
                        xtype: 'form',
                        padding: 5,
                        labelWidth: 50,
                        labelAlign: 'right',
			defaults: {
			    anchor: '95%'
			},
                        items: [{
                            xtype: 'treecombo',
                            id: 'arcive_genre',
                            fieldLabel: '장르',
                            name: 'genre',
                            pathSeparator: ' > ',
                            rootVisible: false,
                            loader: new Ext.tree.TreeLoader({
                                    url: '/store/get_arc_genre.php'
                            }),
                            root: new Ext.tree.AsyncTreeNode({
                                    id: 0,
                                    expanded: true
                            })
                        },{
                            xtype: 'textarea',
			    id: 'archive_req_comment',
			    height: 80,
                            fieldLabel: '요청내용'
                        }]
                    }],
                    buttonAlign: 'center',
                    buttons: [{
                        text: '요청',
			handler: function(btn, e) {
			    var sm = Ext.getCmp('tab_warp').getActiveTab().get(0).getSelectionModel();
			    var records = sm.getSelections();
			    var rs=[];

			    Ext.each(records, function(r){
				rs.push(r.data);
			    });

			    if(sm.hasSelection()) {
				var archive_genre = Ext.getCmp('arcive_genre').treePanel.getSelectionModel().getSelectedNode().attributes.id;
				var archive_req_comment = Ext.getCmp('archive_req_comment').getValue();

				Ext.Ajax.request({
				    url: '/interface/archive_request.php',
				    params: {
					genre_tp: archive_genre,
					req_comment: archive_req_comment,
					user_id: '<?=$user_id?>',
					items: Ext.encode(rs)
				    },
				    callback: function (self, success, response) {
					if ( success ) {
					    try {
						req_win.close();
					    }
					    catch ( e ) {
						Ext.Msg.alert(e['name'], e['message']);
					    }
					} else {
					    Ext.Msg.alert('서버 오류', response.statusText + '(' + response.status + ')');
					}
				    }
				});
			    }
			}
                    },{
                        text: '취소',
			handler: function(btn, e) {
			    req_win.close();
			}
                    }]
            }).show();
        },
		request_cis_transfer: function() {
			var req_win = new Ext.Window({
				title: '광화문제작 등록의뢰',
				width: 300,
				height: 200,
				layout: 'fit',

				items: [{
					xtype: 'form',
					padding: 5,
					labelWidth: 50,
					labelAlign: 'right',
					defaults: {
						anchor: '95%'
					},
					items: [{
						xtype: 'treecombo',
						id: 'cis_transfer_genre',
						fieldLabel: '장르',
						name: 'genre',
						pathSeparator: ' > ',
						rootVisible: false,
						loader: new Ext.tree.TreeLoader({
								url: '/store/get_arc_genre.php'
						}),
						root: new Ext.tree.AsyncTreeNode({
								id: 0,
								expanded: true
						})
					},{
						xtype: 'textarea',
						id: 'cis_transfer_req_comment',
						height: 80,
						fieldLabel: '요청내용'
					}]
                }],
				buttonAlign: 'center',
				buttons: [{
					text: '요청',
					handler: function(btn, e) {
						var sm = Ext.getCmp('tab_warp').getActiveTab().get(0).getSelectionModel();
						var records = sm.getSelections();
						var rs=[];

						Ext.each(records, function(r){
						rs.push(r.data);
						});

						if(sm.hasSelection()) {
							var cis_transfer_genre = Ext.getCmp('cis_transfer_genre').treePanel.getSelectionModel().getSelectedNode().attributes.id;
							var cis_transfer_req_comment = Ext.getCmp('cis_transfer_req_comment').getValue();

							Ext.Ajax.request({
								url: '/interface/cis_transfer_request.php',
								params: {
									genre_tp: cis_transfer_genre,
									req_comment: cis_transfer_req_comment,
									user_id: '<?=$user_id?>',
									items: Ext.encode(rs)
								},
								callback: function (self, success, response) {
									if ( success ) {
										try {
										req_win.close();
										}
										catch ( e ) {
										Ext.Msg.alert(e['name'], e['message']);
										}
									} else {
										Ext.Msg.alert('서버 오류', response.statusText + '(' + response.status + ')');
									}
								}
							});
						}
					}
                },{
					text: '취소',
					handler: function(btn, e) {
						req_win.close();
					}
                }]
            }).show();
        },
        messageAlert: function() {
            Ext.Msg.alert( _text('MN00023'), '기능 구현중입니다');
        }
}

<?php
if( checkAllowGrant($user_id,$content_id,GRANT_DELETE ) ){
?>
,{
	icon: '/led-icons/delete.png',
	//>>text: '삭제',
	//text: _text('MN00034'),
	text:'삭제',
	handler: function(btn, e){
		var win = new Ext.Window({
				layout:'fit',
				//>>title:'삭제 사유',MN00128
				title:_text('MN00128'),
				modal: true,
				width:500,
				height:150,
				items:[{
					id:'cg_delete_inform',
					xtype:'form',
					border: false,
					frame: true,
					padding: 5,
					labelWidth: 70,
					defaults: {
						anchor: '95%'
					},
					items: [{
						id:'cg_delete_reason',
						xtype: 'textarea',
						height: 50,
						//>> fieldLabel: '내용'
						fieldLabel:_text('MN00128'),
						allowBlank: true,
						blankText: '삭제 사유를 적어주세요',
						msgTarget: 'under'
					}],
					buttons:[{
						//>>text:'삭제',
						text: _text('MN00034'),
						handler: function(btn,e){

							var isValid = Ext.getCmp('cg_delete_reason').isValid();
							if (!isValid)
							{
								Ext.Msg.show({
									icon: Ext.Msg.INFO,
									title: _text('MN00024'),
									msg: '삭제사유를 적어주세요.',
									buttons: Ext.Msg.OK
								});
								return;
							}


							var sm = Ext.getCmp('tab_warp_cg').getActiveTab().get(0).getSelectionModel();
							var tm = Ext.getCmp('cg_delete_reason').getValue();

							var rs = [];
							var _rs = sm.getSelections();
							Ext.each(_rs, function(r, i, a){
								rs.push({
									content_id: r.get('content_id'),
									delete_his: tm
								});
							});

							Ext.Msg.show({
								icon: Ext.Msg.QUESTION,
								//>> title: '확인',
								title: _text('MN00024'),

								msg: '원본 미디어만 삭제됩니다. \n삭제사유를 저장하고 콘텐츠를 삭제하시겠습니까?',
								//msg: _text('MSG00145'),

								buttons: Ext.Msg.OKCANCEL,
								fn: function(btnId, text, opts){
									if(btnId == 'cancel') return;

									var ownerCt = Ext.getCmp('tab_warp_cg').getActiveTab().get(0);
									ownerCt.sendAction('delete', rs, ownerCt);
									win.destroy();
								}
							});
						}
					},{
						//>>text:'닫기',
						text:_text('MN00031'),
						handler: function(btn,e){
							win.destroy();
						}
					}]

				}]
		});
		win.show();
	}
}
<?php
}?>

<?php
if( checkAllowGrant($user_id,$content_id,GRANT_DELETE ) ){
?>
,{
	icon: '/led-icons/delete.png',
	text:'프록시 삭제',
        hidden: true,
	handler: function(btn, e){

		var sm = Ext.getCmp('tab_warp_cg').getActiveTab().get(0).getSelectionModel();
		var tm = '프록시 삭제';

		var rs = [];
		var _rs = sm.getSelections();
		Ext.each(_rs, function(r, i, a){
			rs.push({
				content_id: r.get('content_id'),
				delete_his: tm
			});
		});

		Ext.Msg.show({
			icon: Ext.Msg.QUESTION,
			title: _text('MN00024'),
			msg: '원본 및 프록시 모두 삭제됩니다. \n 삭제하시겠습니까?',
			buttons: Ext.Msg.OKCANCEL,
			fn: function(btnId, text, opts){
				if(btnId == 'cancel') return;

				var ownerCt = Ext.getCmp('tab_warp_cg').getActiveTab().get(0);
				ownerCt.sendAction('delete_proxy', rs, ownerCt);
			}
		});

	}
}
<?php
}?>
,{
	text: '나의 전송 작업정보',
	icon: '/led-icons/television.png',
	hidden: true,
	handler: function(b, e){
		var sm = Ext.getCmp('tab_warp_cg').getActiveTab().get(0).getSelectionModel();
		if(!sm.hasSelection()){
			Ext.Msg.alert( _text('MN00023'),'콘텐츠를 선택 해 주세요');
			return;
		}
		var type = 'user_id';
		var records = sm.getSelected();
		var content_id= records.get('content_id');

		var interface_id = '';

		var user_id ='<?=$_SESSION['user']['user_id']?>';
		var user_type = 'USER';

		var args = {
			type: type,
			user_type : user_type,
			user_id : user_id
		};

		callModuleRen('/interface/info_view/viewInterface.php', {
			args: Ext.encode(args)
		});
	}
}
,{
	text: '콘텐츠 전송 작업정보',
	icon: '/led-icons/television.png',
	hidden: true,
	handler: function(b, e){
		var sm = Ext.getCmp('tab_warp_cg').getActiveTab().get(0).getSelectionModel();
		if(!sm.hasSelection()){
			Ext.Msg.alert( _text('MN00023'),'콘텐츠를 선택 해 주세요');
			return;
		}
		var type = 'content_id';
		var records = sm.getSelected();
		var content_id= records.get('content_id');

		var args = {
			type: type,
			content_id: content_id
			};

		callModuleRen('/interface/info_view/viewInterface.php', {
			args: Ext.encode(args)
		});
	}
},{
	text: '관심콘텐츠',
	icon: '/led-icons/star_2.png',
        menu: [{
            text: '추가',
            icon: '/led-icons/star_2.png',
            menu: [
                    <?php
                        $favorite_lists = $db->queryAll("select * from bc_favorite_category where user_id = '$user_id' and content_type ='M' order by favorite_category_id");

                        if(count($favorite_lists) > 0) {
                            for ( $i=0; $i<count($favorite_lists); $i++ ) {
                                $items[] = "{icon:'/led-icons/star_1.png',text: '".$favorite_lists[$i]['favorite_category_title']."',handler: function(b, e){b.parentMenu.parentMenu.initialConfig.ownerCt.addFavorite(".$favorite_lists[$i]['favorite_category_id'].");} }";
                            }
                        }

                        echo join(",\n", $items);
                    ?>
            ]
        },{
            text: '제외',
            icon: '/led-icons/delete.png',
            handler: function(b,e) {
                b.parentMenu.initialConfig.ownerCt.removeFavorite();
            }
        }],

        addFavorite: function(favorite_category_id) {
            var sm = Ext.getCmp('tab_warp_cg').getActiveTab().get(0).getSelectionModel();
            var records = sm.getSelections();
            var rs=[];

            Ext.each(records, function(r){
		rs.push(r.get('content_id'));
            });

            Ext.Ajax.request({
                url: '/store/add_favorite_list.php',
                params: {
                    favorite_category_id: favorite_category_id,
                    action: 'add',
                    content_type: 'C',
                    contents: Ext.encode(rs)
                },
                callback: function (self, success, response) {
                    if ( success ) {
                        try {
                            var result = Ext.decode(response.responseText);
                            Ext.getCmp('tab_warp_cg').getActiveTab().get(0).getStore().reload();
                        }
                        catch ( e ) {
                            Ext.Msg.alert(e['name'], e['message']);
                        }
                    } else {
                        Ext.Msg.alert('서버 오류', response.statusText + '(' + response.status + ')');
                    }
                }
            });
        },

        removeFavorite: function() {
            var sm = Ext.getCmp('tab_warp_cg').getActiveTab().get(0).getSelectionModel();
            var records = sm.getSelections();
            var rs=[];

            Ext.each(records, function(r){
		rs.push(r.get('content_id'));
            });

            Ext.Ajax.request({
                url: '/store/add_favorite_list.php',
                params: {
                    action: 'remove',
                    content_type: 'C',
                    contents: Ext.encode(rs)
                },
                callback: function (self, success, response) {
                    if ( success ) {
                        try {
                            var result = Ext.decode(response.responseText);
                            Ext.getCmp('tab_warp_cg').getActiveTab().get(0).getStore().reload();
                        }
                        catch ( e ) {
                            Ext.Msg.alert(e['name'], e['message']);
                        }
                    } else {
                        Ext.Msg.alert('서버 오류', response.statusText + '(' + response.status + ')');
                    }
                }
            });
        }
}]