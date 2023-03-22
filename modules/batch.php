<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

$jsonContent_ids = json_decode(str_replace('\\', '', $_POST['args']));
if ( empty($jsonContent_ids) )
{
	die(json_encode(array(
		'success' => false,
		'msg' => '불러올 콘텐츠 목록이 존재하지 않습니다.'
	)));
}

$ud_content_id = $db->queryOne("
					SELECT	UD_CONTENT_ID
					FROM	BC_UD_CONTENT
					WHERE	CONTENT_ID = ".$jsonContent_ids[0]->content_id
				);

if ( ! checkAllowUdContentGrant($_SESSION['user']['user_id'], $ud_content_id, GRANT_EDIT)) {
	die(json_encode(array(
		'success' => false,
		'msg' => '메타데이터 일괄 수정 권한이 없습니다.'
	)));
}
?>
(function(){
	function checkChecked()
	{
		var items = Ext.getCmp('batch_form').getForm().items,
			_ids = Ext.getCmp('batch_content_list').store.getRange(),
			ids = [],
			tables = [],
			result = {
				k_contents: {
					contents_id: '',
					meta_tables_id: ''
				},
				values: []
			};

		Ext.each(_ids, function(i){
			ids.push(i.get('content_id'));
			tables.push(i.get('meta_table_id'));
		});
		result.k_contents.contents_id = ids.join(',');
		result.k_contents.meta_tables_id = tables.join(',');

		items.each(function(i){
			if ( i.xtype == 'hidden' )
			{
				return;
			}
			// 체크되었는지 확인
			else if ( i.items.get(0).getValue() )
			{
				if (i.fieldLabel == '상품목록')
				{
					var _items = [];
					Ext.getCmp('item_list').store.each(function(r){
						_items.push({
							item_cd: r.get('item_cd'),
							item_nm: r.get('item_nm')
						});
					});

					result.values.push([
						'x_items_list',
						Ext.encode(_items)
					]);

					delete _items;
				}
				else if (i.items.get(1).xtype == 'datefield' && i.fieldLabel == '방송일시')
				{
					result.values.push([
						i.name,
						i.items.get(1).getValue().format('Y-m-d')+' '+i.items.get(2).getValue()
					]);
				}
				else if (i.items.get(1).xtype == 'datefield')
				{
					result.values.push([
						i.name,
						i.items.get(1).getValue().format('Y-m-d H:i:s')
					]);
				}
				else
				{
					result.values.push([
						i.name,
						i.items.get(1).getValue()
					]);
				}
			}
		});

		if (result.values.length == 0)
		{
			return false;
		}

		return result;
	}

	function submit(params){
		Ext.Ajax.request({
			url: '/modules/batch/store/update.php',
			params: params,
			callback: function(opts, success, response){
				if ( success )
				{
					try
					{
						var r = Ext.decode(response.responseText);
						if (r.success)
						{
							Ext.Msg.show({
								title: '확인',
								msg: '메타데이터 일괄 수정이 완료되었습니다.<br />편집 중인 창을 닫으시겠습니까?',
								icon: Ext.Msg.QUESTION,
								buttons: Ext.Msg.YESNO,
								fn: function(btnID){
									//Ext.getCmp('workpanel').store.removeAll();
									if (btnID == 'yes')
									{
										Ext.getCmp('batch-win').close();
									}
								}
							});
						}
						else
						{
							Ext.Msg.alert('정보', r.msg);
						}
					}
					catch(e)
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

	function getFormItems(id){
		Ext.Ajax.request({
			url: '/modules/batch/store/form.php',
			params: {
				content_id: id
			},
			callback: function(opts, success, response){
				if ( success )
				{
					try
					{
						var r = Ext.decode(response.responseText);

						Ext.getCmp('batch_form').removeAll();
						Ext.getCmp('batch_form').add(r);
						Ext.getCmp('batch_form').doLayout();
					}
					catch(e)
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

	function getStreamURL(id){
		Ext.Ajax.request({
			url: '/store/getStreamURL.php',
			params: {
				content_id: id
			},
			callback: function(opts, success, response){
				if ( success )
				{
					try
					{
						var r = Ext.decode(response.responseText);

						if ( Ext.isEmpty(r.stream_url))
						{
							Ext.Msg.alert('확인', '스트리밍 정보가 없습니다.');
							return;
						}

						$f().play({
							url: 'mp4:'+r.stream_url,
							autoPlay: false,
							autoBuffering: true,
							scaling: 'fit',
							provider: 'rtmp'
						});
					}
					catch(e)
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
	var body = {
		layout: 'border',
		border: false,

		items: [{
			region: 'center',
			layout: 'border',

			items: [{
				id: 'batchplayer',
				region: 'north',
				height: 250,
				listeners: {
					render: function(self){
						$f(self.body.dom, {src: "/js/flowplayer/flowplayer-3.2.4.swf", wmode: 'opaque'}, {
							clip: {
//								autoPlay: false,
//								autoBuffering: true,
//								scaling: 'fit',
//								provider: 'rtmp'
							},
							plugins: {
								rtmp: {
									url: '/js/flowplayer/flowplayer.rtmp-3.2.3.swf',
									netConnectionUrl: '<?=STREAMER_ADDR?>'
								}
							}
						});
					}
				}
			},{
				region: 'center',
				xtype: 'grid',
				id: 'batch_content_list',
				title: '콘텐츠 목록',
				loadMask: true,

				viewConfig: {
					emptyText: '선택하신 콘텐츠가 존재 하지않습니다.'
				},

				store: new Ext.data.JsonStore({
					url: '/modules/batch/store/content_list.php',
					root: 'data',
					fields: [
						{name: 'content_id', type: 'int'},
						{name: 'meta_table_id', type: 'int'},
						'title'
					],
					listeners: {
						load: function(){
							Ext.getCmp('batch_content_list').getSelectionModel().selectFirstRow();
							Ext.getCmp('batch_content_list').fireEvent('rowclick', Ext.getCmp('batch_content_list'));
						},
						exception: function(self, type, action, options, response, arg){
							Ext.Msg.alert('오류', response.responseText);
						}
					}
				}),
				//sm: new Ext.grid.CheckboxSelectionModel(),
				cm: new Ext.grid.ColumnModel({
					defaults: {
						sortable: false,
						menuDisabled: true
					},
					columns: [
						//new Ext.grid.CheckboxSelectionModel(),
						new Ext.grid.RowNumberer(),
						{header: '제목', dataIndex: 'title', width: 200}
					]
				}),
				viewConfig: {
					forceFit: 'fit'
				},
				listeners: {
					viewready: function(self){
						self.store.load({
							params: {
								content_ids: '<?=json_encode($jsonContent_ids)?>'
							}
						});
					},
					rowclick: function( self, i, e ) {

						var f = Ext.getCmp('batch_form');
						var sm = self.getSelectionModel();

						if ( sm.getSelections().length > 1 )
						{
							/*
							App.setAlert(App.STATUS_NOTICE, '일괄 작업 모드');
							self.ownerCt.ownerCt.setTitle('[일괄 작업]');

							Ext.getCmp('batch_form').buttons[0].setText('일괄 수정');
							*/
						}
						else
						{
							/*
							App.setAlert(App.STATUS_NOTICE, '단일 작업 모드');
							self.ownerCt.ownerCt.setTitle( sm.getSelected().get('title') + '[단일 작업]');
							Ext.getCmp('batch_form').buttons[0].setText('단일 수정');
							*/

							getStreamURL(sm.getSelected().get('content_id'));
							getFormItems(sm.getSelected().get('content_id'));
						}
					}
				}
			}]
		},{
			region: 'east',
			xtype: 'form',
			id: 'batch_form',
			width: 400,
			frame: true,
			padding: 5,
			autoScroll: true,
			labelWidth: 80,
			defaults: {
				anchor: '95%'
			},

			buttons: [{
				scale: 'medium',
				text: '메타데이터 일괄 수정',
				handler: function(b, e){
					var form = Ext.getCmp('batch_form').getForm();

					if (b.text == '단일 수정')
					{
						submit(r);
					}
					else
					{
						if ( !(records = checkChecked()) )
						{
							Ext.Msg.alert("정보", "일괄 수정할 필드를 체크하여주세요.");
							return;
						}

						Ext.Msg.show({
							title: '확인',
							msg: '메타데이터를 일괄 수정 하시겠습니까?',
							icon: Ext.Msg.QUESTION,
							buttons: Ext.Msg.YESNO,
							fn: function(btnID){
								if (btnID == 'yes')
								{
									var params = Ext.getCmp('batch_form').getForm().getValues();
									var tn = Ext.getCmp('batch_category').treePanel.getSelectionModel().getSelectedNode();

									submit({'values': Ext.encode(records)});
								}

								return;
							}
						})
					}
				}
			}]
		}]
	};

	return 	new Ext.Window({
		id: 'batch-win',
		title: '일괄 메타데이터 수정',
		width: 800,
		height: 600,
		modal: true,
		layout: 'fit',
		border: false,

		items: body
	}).show();

})()