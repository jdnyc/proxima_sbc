<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lang.php');

$user_id		= $_SESSION['user']['user_id'];
$content_id		= $_POST['content_id'];
$isCuesheet		= $_POST['isCuesheet'];

$check = $db->queryOne("select reg_user_id from bc_content where content_id='$content_id'");

if( ($_SESSION['user']['is_admin'] != 'Y') && !in_array(CG_ADMIN_GROUP, $_SESSION['user']['groups']) &&  ( $check != $user_id ) )
{
	echo '[{ hidden:true }]';
	exit;
}
?>

[{ hidden:true }

, {
	icon: '/led-icons/delete.png',
	text:'삭제 요청',
	handler: function(btn, e){
		var win = new Ext.Window({
				layout:'fit',
				//>>title:'삭제 사유',MN00128
				title:_text('MN00128'),
				modal: true,
				width:500,
				height: 180,
				items:[{
					id:'delete_inform',
					xtype:'form',
					border: false,
					frame: true,
					padding: 5,
					defaults: {
						anchor: '100%'
					},
					items: [{
						id:'delete_reason',
						xtype: 'textarea',
						height: 80,
						hideLabel : true,
						allowBlank: true,
						emptyText: '삭제 사유를 적어주세요',
						blankText: '삭제 사유를 적어주세요',
						msgTarget: 'under'
					}],
					buttons:[{
						//>>text:'삭제',
						text: '삭제 요청',
						handler: function(btn,e){
							var isValid = Ext.getCmp('delete_reason').isValid();
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

							if( !Ext.isEmpty(Ext.getCmp('tab_warp_audio')) )
							{
								var sm = Ext.getCmp('tab_warp_audio').getActiveTab().get(0).getSelectionModel();
								var ownerCt = Ext.getCmp('tab_warp_audio').getActiveTab().get(0);
							}
							else if(  !Ext.isEmpty(Ext.getCmp('tab_cg')) )
							{
								Ext.getCmp('tab_cg').getActiveTab();
								var sm = Ext.getCmp('tab_cg').getActiveTab().getSelectionModel();
								var ownerCt = Ext.getCmp('tab_cg').getActiveTab();
							}
							else
							{
								return;
							}
							var tm = Ext.getCmp('delete_reason').getValue();

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
								msg: '삭제 요청하시겠습니까?',

								buttons: Ext.Msg.OKCANCEL,
								fn: function(btnId, text, opts){
									if(btnId == 'cancel') return;

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
},{
	text: '관심콘텐츠',
	icon: '/led-icons/star_2.png',
        menu: [{
            text: '추가',
            icon: '/led-icons/star_2.png',
            menu: [
                    <?php
                        $favorite_lists = $db->queryAll("select * from bc_favorite_category where user_id = '$user_id' and content_type ='A' order by favorite_category_id");

                        if(count($favorite_lists) > 0) {
                            for ( $i=0; $i<count($favorite_lists); $i++ ) {
                                $items[] = "{icon:'/led-icons/star_2.png',text: '".$favorite_lists[$i]['favorite_category_title']."',handler: function(b, e){b.parentMenu.parentMenu.initialConfig.ownerCt.addFavorite(".$favorite_lists[$i]['favorite_category_id'].");} }";
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
            var sm = Ext.getCmp('tab_warp_audio').getActiveTab().get(0).getSelectionModel();
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
                    content_type: 'A',
                    contents: Ext.encode(rs)
                },
                callback: function (self, success, response) {
                    if ( success ) {
                        try {
                            var result = Ext.decode(response.responseText);
                            Ext.getCmp('tab_warp_audio').getActiveTab().get(0).getStore().reload();
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
            var sm = Ext.getCmp('tab_warp_audio').getActiveTab().get(0).getSelectionModel();
            var records = sm.getSelections();
            var rs=[];

            Ext.each(records, function(r){
		rs.push(r.get('content_id'));
            });

            Ext.Ajax.request({
                url: '/store/add_favorite_list.php',
                params: {
                    action: 'remove',
                    content_type: 'A',
                    contents: Ext.encode(rs)
                },
                callback: function (self, success, response) {
                    if ( success ) {
                        try {
                            var result = Ext.decode(response.responseText);
                            Ext.getCmp('tab_warp_audio').getActiveTab().get(0).getStore().reload();
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
<?php
if( $isCuesheet == 'true' ){
?>
,{
	text: '큐시트추가',
	icon: '/led-icons/doc_music_playlist.png',
        handler: function(b,e) {
            var sm = Ext.getCmp('tab_warp_audio').getActiveTab().get(0).getSelectionModel();
            var records = sm.getSelections();
            var rs=[];

            Ext.each(records, function(r){
                var tmp = [];
                tmp.push(r.get('content_id'));
                tmp.push(r.get('title'));
		rs.push(tmp);
            });

            var cuesheet_lists = Ext.getCmp('audio_cuesheet_list').getSelectionModel();

            if(cuesheet_lists.hasSelection()) {
                var cuesheet_id = cuesheet_lists.getSelected().get('cuesheet_id');
                Ext.Ajax.request({
                    url: '/store/cuesheet/cuesheet_action.php',
                    params: {
                        action: 'add-items',
                        cuesheet_id: cuesheet_id,
                        contents: Ext.encode(rs)
                    },
                    callback: function (self, success, response) {
                        if ( success ) {
                            try {
                                var result = Ext.decode(response.responseText);
                                Ext.getCmp('tab_warp_audio').getActiveTab().get(0).getStore().reload();
                                Ext.getCmp('audio_cuesheet_items').getStore().reload();
                            }
                            catch ( e ) {
                                Ext.Msg.alert(e['name'], e['message']);
                            }
                        } else {
                            Ext.Msg.alert('서버 오류', response.statusText + '(' + response.status + ')');
                        }
                    }
                });
            } else {
                Ext.Msg.alert( _text('MN00023'), '큐시트를 선택해 주세요');
            }
        }
}
<?php
}
?>
]