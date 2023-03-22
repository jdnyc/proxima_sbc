<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lib.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
$meta_table_id = $_POST['panel_id'];
$container_id = $db->queryOne("select container_id from meta_field where meta_table_id='$meta_table_id'and type='container' and name like '%기본정보%'");
$showColumnHeader = array(
	'바코드',
	'테이프형태',
	'콘텐츠ID',
	'저장물ID',
	'파일ID',
	'프로그램명',
	'부제',
	'방송일자',
	'방송시작시각',
	'방송종료시각'
);
	$body = "
	{
		xtype:'treegrid',
		id: 'ingest_list',
		//title: '인제스트 요청 리스트',
        width: 500,
		height: 500,
		//autoHeight: true,
		//columnResize : false,
		//enableSort : false,
       // containerScroll : true,
		//reserveScrollOffset : false,
		//enableHdMenu : true,
        enableDD: false,
		selModel: new Ext.tree.MultiSelectionModel({
		}),
        columns:[
			";

		$meta_fields = $db->queryAll("select name, meta_field_id from meta_field where type !='container' and container_id='$container_id' order by sort");
		$items = "{header: 'NO.',				dataIndex: 'no', width: 60, sortType: 'asInt'}";
		$items.=",{header: 'TC_IN',				dataIndex: 'tc_in', width: 80}";
		$items.=",{header: 'TC_OUT',			dataIndex: 'tc_out', width: 80}";
		$items.=",{header: '상태',			dataIndex: 'status', width: 80}";
		$items.=",{header: 'TITLE',			dataIndex: 'title', width: 80}";
		foreach($meta_fields as $meta_field)
		{
			$items .= ",{header:'".$meta_field['name']."', dataIndex: '".$meta_field['meta_field_id']."', width:80}";
		}
		$body.=$items;
		$body.="
		],
		loader: new Ext.tree.TreeLoader({
			baseParams: {
				start_date: Ext.getCmp('start_date').getValue().format('Ymd000000'),
				end_date: Ext.getCmp('end_date').getValue().format('Ymd240000'),
				meta_table_id: $meta_table_id,
				container_id: $container_id,
				start: 0,
				limit: Ariel.limit
			},
			dataUrl: '/pages/menu/config/ingest/ingest_list_data.php',
			listeners: {
				load: function(self){
					Ext.Ajax.request({
						url: '/pages/menu/config/ingest/getTotal.php',
						params: {
							start_date: self.baseParams.start_date,
							end_date: self.baseParams.end_date,
							meta_table_id: self.baseParams.meta_table_id,
							search: Ext.getCmp('search').getValue()
						},
						callback: function(opts, success, response){

							var r = Ext.decode(response.responseText);
							var cur = self.baseParams.start + self.baseParams.limit;
							Ariel.total_page = Math.floor((r.total/Ariel.limit));

							if(Ariel.total_page <1)
							{
								Ariel.total_page=1;
							}

							if ( self.baseParams.start == 0 )
							{
								ingest_panel.getBottomToolbar().get(0).disable();
								ingest_panel.getBottomToolbar().get(1).disable();
							}
							else
							{
								ingest_panel.getBottomToolbar().get(0).enable();
								ingest_panel.getBottomToolbar().get(1).enable();
							}

							if ( r.total < cur )
							{
								ingest_panel.getBottomToolbar().get(5).disable();
								ingest_panel.getBottomToolbar().get(6).disable();
							}
							else
							{
								ingest_panel.getBottomToolbar().get(5).enable();
								ingest_panel.getBottomToolbar().get(6).enable();
							}

							if(Ariel.cur_page>1)
							{
								ingest_panel.getBottomToolbar().get(0).enable();
								ingest_panel.getBottomToolbar().get(1).enable();
							}
							else
							{
								ingest_panel.getBottomToolbar().get(0).disable();
								ingest_panel.getBottomToolbar().get(1).disable();
							}

							if(Ariel.cur_page == Ariel.total_page)
							{
								ingest_panel.getBottomToolbar().get(5).disable();
								ingest_panel.getBottomToolbar().get(6).disable();
							}


							// 현재 페이지 표시
							Ext.getCmp('start_page').setValue(Ariel.cur_page);
							ingest_panel.getBottomToolbar().get(4).setText(Ariel.total_page);
						}
					});
				},
				loadException: function(self, node, response){
					Ext.Msg.alert('확인', response.responseText);
				}
			}
		}),
		contextMenu: new Ext.menu.Menu({
			items: [{
				cmd: 'add-node',
				text: '추가',
				icon: '/led-icons/folder_add.png'
			},{
				cmd: 'edit-node',
				text: '수정',
				icon: '/led-icons/folder_edit.png'
			}/*,{
				cmd: 'set-work-node',
				text: '작업 할당하기',
				icon: '/led-icons/folder_edit.png'
			},{
				cmd: 'get-mywork-node',
				text: '내 작업으로 가져오기',
				icon: '/led-icons/folder_edit.png'
			}*/,{
				cmd: 'delete-node',
				text: '삭제',
				icon: '/led-icons/folder_delete.png'
			}],
			listeners: {
				itemclick: {
					fn: function(item, e){
						var r = item.parentMenu.contextNode.getOwnerTree();
						switch (item.cmd) {
							case 'add-node':
								Ext.Ajax.request({
									url: '/javascript/ext.ux/ingest_type_panel.php',
									params: {
									},
									callback: function(options, success, response){
										if (success)
										{
											try
											{
												Ext.decode(response.responseText);
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
							break;

							case 'edit-node':
								var get_sel = Ext.getCmp('ingest_list').getSelectionModel().getSelectedNodes();
								var sel = get_sel[0];

								if(!sel)
								{
									msg('알림', '수정하실 목록을 선택해주세요');
								}
								else
								{
									var id = sel.attributes.id;
									Ext.Ajax.request({
										url: '/javascript/ext.ux/ingest_edit_panel.php',
										params: {
											id: id
										},
										callback: function(options, success, response){
											if (success)
											{
												try
												{
													Ext.decode(response.responseText);
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
							break;

							case 'set-work-node':
								set_work();
							break;

							case 'get-mywork-node':
								get_mywork();
							break;

							case 'delete-node':
								delete_list();
							break;
						}
					},
					scope: this
				}
			}
		}),
		listeners: {
			afterrender: function(self){
				var sort = new Ext.tree.TreeSorter(self, {
					//folderSort: true,
					dir: 'desc',
					sortType: function(node){
						return parseInt(node.id, 10);
					}
				});
			},

			contextmenu: function(node, e)
			{
				  node.select();
				var c = node.getOwnerTree().contextMenu;
				c.contextNode = node;
				c.showAt(e.getXY());
			}
		}

    }
	";
echo $body;
?>