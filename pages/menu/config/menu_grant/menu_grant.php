<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
session_start();
fn_checkAuthPermission($_SESSION);
?>

(function(){
    Ext.ns('Ariel.config');

    function mappingGrant(value) {
        if (value == 0) {
            return '권한 없음';
        } else if( value == 1 ) {
            return '읽기';
        } else if( value == 2 ) {
            return '읽기 / 생성 / 수정 / 이동';
        } else if( value == 3 ) {
            return '읽기 / 생성 / 수정 / 이동 / 삭제';
        } else if( value == 4 ) {
            return '숨김';
        }

        return value;
    }

    Ariel.config.Top_Menu_Grant = Ext.extend(Ext.Panel, {
		//title: '메인 메뉴 권한 관리',
		layout: 'fit',
		frame: false,
		border:false,
		initComponent: function(config){
			Ext.apply(this, config || {});

			var that = this;

			this.request = function(title, params, grid){
				Ext.Msg.show({
					icon: Ext.Msg.QUESTION,
					title: _text('MN00024'),
					msg: title+ '. ' + _text('MSG01007'),
					buttons: Ext.Msg.OKCANCEL,
					fn: function(btnId, text, opts){
						if(btnId == 'cancel') return;

						var w = Ext.Msg.wait(_text('MN00262'));
						Ext.Ajax.request({
							url: '/store/menu_grant_set.php',
							params: params,
							callback: function(opts, success, response){
								w.hide();
								if(success){
									try{
										var r = Ext.decode(response.responseText);
										if(!r.success){
											Ext.Msg.alert(_text('MN00024'), r.msg);
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
									 Ext.Msg.alert(_text('MN01039'), response.statusText);
								}
							}
						});
					}
				});
			};

			this.formCheck = function(){
		
				var ud_content_form = that.get(0).get(1).get(0).get(0);
				var member_group_form = that.get(0).get(1).get(1).get(0);			

				if( Ext.isEmpty(ud_content_form.getValue()) )
				{
					//Ext.Msg.alert('알림','사용자 그룹을 선택해주세요');
					Ext.Msg.alert(_text('MN00024'),_text('MSG00098'));
					return false;
				}
				else if( Ext.isEmpty(member_group_form.getValue()) )
				{
					//Ext.Msg.alert('알림','메뉴를 선택해주세요');
					 Ext.Msg.alert(_text('MN00024'),_text('MSG01058'));
					return false;
				}			
			

				return true;
			};
				

			this.items = [{
				xtype: 'form',
				autoScroll: true,
				layout: 'border',
				bodyPadding: 10,
				frame: false,
				border:false,
				defaults: {
					split: true
				},
				url: '/store/menu_grant_set.php',
				buttonAlign: 'center',
				buttons: [{
					text : '<span style="position:relative;top:1px;"><i class="fa fa-check" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00046'),
					scale: 'medium',
					handler: function() {
						var formvalues = that.get(0).getForm().getValues();
						
						var ud_content_form = that.get(0).get(1).get(0).get(0);
						var member_group_form = that.get(0).get(1).get(1).get(0);					
						var grid = that.get(0).get(0).get(0);

						if( !that.formCheck() ) return;
						
						formvalues.action = 'add';
						formvalues.grant_type = 'top_menu_grant';
						that.request( 'SAVE', formvalues, grid );
					}
				},{
					text : '<span style="position:relative;top:1px;"><i class="fa fa-trash" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00034'),
					scale: 'medium',
					handler: function() {

						var grid = that.get(0).get(0).get(0);

						if( Ext.isEmpty(grid.getSelectionModel().getSelected()) )
						{
						   //MSG00022
							 Ext.Msg.alert(_text('MN00024'),_text('MSG00022'));
							return;
						}

						var selections = grid.getSelectionModel().getSelections();
						var list = new Array();
						var params = {};
						Ext.each(selections, function(i){
							list.push(i.data);
						});

						params.action = 'delete';
						params.grant_type = 'top_menu_grant';
						params.list =  Ext.encode(list);
						that.request( 'DELETE', params, grid );
					}
				}],
				items: [{
					xtype: 'panel',
					region: 'north',
					height: 300,
					layout: 'fit',
					border:false,
					items:[{
						xtype: 'grid',
						cls: 'proxima_customize',
						stripeRows: true,
						loadMask: true,
						store: new Ext.data.GroupingStore({
							reader: new Ext.data.ArrayReader({}, [							
								{name: 'member_group_name'},							
								{name: 'group_grant'},							
								{name: 'member_group_id'},
								{name: 'grant_text'}
							]),
							autoLoad: true,
							url: '/store/menu_grant_store.php',
							baseParams: {
								grant_type: 'top_menu_grant'
							},
							sortInfo: { field: 'member_group_name', direction: "ASC" }
						}),
						border: false,
						autoShow: true,
						columns: [
						{ header: "Group",width:150,  sortable: true, dataIndex: 'member_group_name' },
							{ header: "Menu list", sortable: true, dataIndex: 'grant_text',width:500}
							
						
						],
						sm: new Ext.grid.RowSelectionModel({							
						}),

						view: new Ext.grid.GroupingView({
							forceFit: true,
							groupTextTpl: '{text} ({[values.rs.length]} {[values.rs.length > 1 ? "Items" : "Item"]})'
						}),
						listeners: {
							rowclick: function(self, idx, e)
							{
								var select = self.getSelectionModel().getSelected();
								var form = that.get(0).getForm();

								if( Ext.isEmpty(select) ) return;								
							
								var member_group_form = that.get(0).get(1).get(0).get(0);
								var group_grant_form = that.get(0).get(1).get(1).get(0);											
								member_group_form.reset();
								group_grant_form.reset();											

								member_group_form.setValue(  'member_group_id'+'-'+select.get('member_group_id') , true );								
								var grant = select.get('group_grant').split(",");			

						
								group_grant_form.items.each( function(i){									
									var nameArray = i.getName().split("-");//name에서 권한 코드 뽑기	
									
									for(var z=0; z<grant.length;z++)
									{									
										if(parseInt(grant[z]) == parseInt(nameArray[1]))
										{
											group_grant_form.setValue( i.getName(), true);
										}
									}

								});	
							}
						}

					}]
				},{
					xtype: 'panel',					
					region: 'center',
					border:false,
					cls: 'change_background_panel',
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
					items:[{
						xtype: 'fieldset',
						minWidth: 150,
						flex: 1,
						 title: 'Group',					
						layout: 'fit',
						items :	[{
							xtype: 'checkboxgroup',
							autoScroll: true,
							cls: 'x-check-group-alt',
							columns: 1,
							items: [

							<?php
							$member_group_info = $db->queryAll("select * from BC_MEMBER_GROUP order by member_group_id");
							//$member_group_info = "";
							while ( $member_group_info && $member_group = current($member_group_info) )
							{
								if(key($member_group_info) == 0)
								{
									echo "{boxLabel: 'SELECT ALL', name: 'member_group_id-0' 
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
						xtype: 'fieldset',
						minWidth: 150,
						flex:1,
						title: _text('MN01120'),//메뉴리스트
						layout: 'fit',
						
						items : [{
							xtype: 'checkboxgroup',
							autoScroll: true,
							cls: 'x-check-group-alt',
							columns: 1,
							frame: true,
							items: [
								<?php
							//$metas = $db->queryAll("select * from bc_ud_content order by sort");
							/*각 사이트별 필요 권한만 보여주기 위해서 is_show 항목 추가*/
							$metas = $db->queryAll("select * from bc_top_menu where is_show = 'Y' order by sort");
							//$metas ="";
							while ($metas && $meta = current($metas))
							{
								if(key($metas) == 0)
								{
									echo "{boxLabel: 'SELECT ALL', name: 'menu_id-0' 
										,listeners: {
											check: function( self, checked  ){											
												self.ownerCt.items.each( function(k){													
													k.setValue( checked  );													
												});																			
											}
										}
									},\n";								
								}								

								echo "{boxLabel: '{$meta['menu_name']}', name: 'menu_id-{$meta['id']}' }\n";
								if (next($metas))
								{
									echo ',';
								}
							}
							?>
							]
						}]
					}]
				}]
			}];


			Ariel.config.Top_Menu_Grant.superclass.initComponent.call(this);
		}
	});

    return new Ariel.config.Top_Menu_Grant();

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
