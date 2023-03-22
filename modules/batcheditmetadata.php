<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lang.php');

$jsonContent_ids = json_decode(str_replace('\\', '', $_GET['args']));

$action = $_GET['action'];

if( empty($jsonContent_ids) )
{
	die(json_encode(array(
		'success' => false,
		'msg' => _text('MN00274').' '._text('MSG00154')
	)));
}


?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>

	<!-- smh <title>EBS DAS::일괄 수정</title> -->
	<title><?=_text('MN00092')?>::<?=_text('MN00227')?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

	<link rel="stylesheet" type="text/css" href="/ext/resources/css/ext-all.css" />

	<script type="text/javascript" src="/ext/adapter/ext/ext-base.js"></script>
	<script type="text/javascript" src="/ext/ext-all.js"></script>
	<script type="text/javascript" src="/javascript/lang.php"></script>

	<script type="text/javascript" src="/flash/flowplayer/example/flowplayer-3.2.4.min.js"></script>
    <script type="text/javascript" src="/javascript/ext.ux/Ext.ux.TreeCombo.js"></script>

    <!-- <script type="text/javascript" src="/ext/src/locale/ext-lang-ko.js"></script> -->

	<script type="text/javascript">
	function checkChecked(flag)
	{
		var items = Ext.getCmp('batch_form').getForm().items,
			// 작성자: 박정근
			// 작성일: 2011-03-10
			// 내용: 선택된 콘텐츠만 일괄 작업 되도록 변경
			_ids = Ext.getCmp('batch_content_list').getSelectionModel().getSelections(),
			ids = [],
			tables = [],
			result = {
				k_contents: {
					contents_id: '',
					ud_content_id: ''
				},
				values: []
			};


		Ext.each(_ids, function(i){
			ids.push(i.get('content_id'));
			tables.push(i.get('ud_content_id'));
		});
		result.k_contents.contents_id = ids.join(',');
		result.k_contents.ud_content_id = tables.join(',');
		if(flag)//승인일때 체크 안되있어도 진행
		{
			return result;
		}

		items.each(function(i){
			if ( i.xtype == 'hidden' )
			{
				return;
			}
			// 체크되었는지 확인
			else if ( i.items.get(0).getValue() )
			{
				if (i.items.get(1).xtype == 'datefield')
				{
					result.values.push([
						i.name,
						i.items.get(1).getValue().format('YmdHis')
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
	function approval(params)
	{
		Ext.Ajax.request({
			url: '/modules/batch/store/approval.php',
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
								title: '<?=_text('MN00024')?>',
								msg: '일괄 승인이 완료되었습니다.<br />편집 중인 창을 닫으시겠습니까?',
								icon: Ext.Msg.QUESTION,
								buttons: Ext.Msg.YESNO,
								fn: function(btnID){
									//Ext.getCmp('workpanel').store.removeAll();
									if (btnID == 'yes')
									{
										//Ext.getCmp('batch-win').close();
										window.close();
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
	function refuse(params)
	{
		var refuse_win = new Ext.Window({
			layout:'fit'
			,title: '반려 사유'
			,width: 350
			,height: 230
			,modal: true
			//,closeAction:'hide'
			,resizable: false
			,plain: true
			,items: [{
				id: 'refuse_form',
				xtype: 'form',
				border: false,
				frame: true,
				padding: 10,
				labelWidth: 60,
				labelSeparator: '',
				defaults: {
					anchor: '100%'
				},
				items: [
				{
					xtype: 'combo',
					id: 'refuse_combo',
					//width: 80,
					mode: 'local',
					triggerAction: 'all',
					editable: false,
					displayField: 'd',
					valueField: 'v',
					fieldLabel:'반려목록',
					emptyText: '반려목록을 선택하세요',
					store: new Ext.data.ArrayStore({
						fields: [
							'd', 'v'
						],
						data: [
							['메타데이터 보완 요청','refuse_meta'],
							['인코딩 재작업 요청','refuse_encoding']
						]
					})
				},{
					id:'refuse',
					xtype: 'textarea',
					fieldLabel:'세부내용',
					height: 80,
					autoscroll: true
				}]
			}]
			,buttons: [{
				text: '전 송',
				handler: function(){

					var refuse_list = Ext.getCmp('refuse_combo').getValue();
					var refuse = Ext.getCmp('refuse').getValue();

					if (Ext.isEmpty(refuse_list))
					{
						Ext.Msg.alert('정보', '반려목록을 선택해주세요.');
						return;
					}

					Ext.Msg.show({
						title: '확인',
						msg: '일괄 반려 하시겠습니까?',
						minWidth: 100,
						modal: true,
						icon: Ext.MessageBox.QUESTION,
						buttons: Ext.Msg.OKCANCEL,
						fn: function(btnId){
							if(btnId=='cancel') return;

							Ext.Ajax.request({
								url: '/store/wait_regist_refuse.php',
								params:{
									id: params.k_contents.contents_id,
									refuse_list: refuse_list,
									description: refuse
								},
								callback: function(options,success,response){
									refuse_win.destroy();
									if(success)
									{
										Ext.Msg.show({
											title: '확인',
											msg: '일괄 반려되었습니다.<br />창을 닫으시겠습니까?',
											icon: Ext.Msg.QUESTION,
											buttons: Ext.Msg.OKCANCEL,
											fn: function(btnId){
												if (btnId == 'ok')
												{
													window.close();
												}
											}
										});
									}
									else
									{
										Ext.Msg.alert( _text('MN00023'),'반려 실패',response.statusText);
									}
								}
							});
						}
					});
				}
			},{
				text:'닫기',
				handler: function(){
					refuse_win.destroy();
				}
			}]
		});
		refuse_win.show();
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
								title: '<?=_text('MN00024')?>',
								msg: _text('MSG00034') + '<br />' + _text('MSG00190'),
								icon: Ext.Msg.QUESTION,
								buttons: Ext.Msg.YESNO,
								fn: function(btnID){
									//Ext.getCmp('workpanel').store.removeAll();
									if (btnID == 'yes')
									{
										//Ext.getCmp('batch-win').close();
										window.close();
									}
								}
							});
						}
						else
						{
							Ext.Msg.alert('<?=_text('MN00023')?>', r.msg);
						}
					}
					catch(e)
					{
						Ext.Msg.alert(e['name'], e['message']);
					}
				}
				else
				{
					Ext.Msg.alert('<?=_text('MN00022')?>', response.statusText);
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
					Ext.Msg.alert('<?=_text('MN00022')?>', response.statusText);
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
							//>>Ext.Msg.alert( _text('MN00023'), '스트리밍 정보가 없습니다.');MSG00070
							Ext.Msg.alert('<?=_text('MN00022')?>', '<?=_text('MSG00070')?>');
							return;
						}

						$f('batchplayer').play({
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
					Ext.Msg.alert('<?=_text('MN00022')?>', response.statusText);
				}
			}
		});
	}


	Ext.onReady(function(){
		var panel = new Ext.Panel({
			renderTo: Ext.getBody(),
			width: 1000,
			height: 600,
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
							$f(self.body.dom, {src: "/flash/flowplayer/flowplayer-3.2.4.swf", wmode: 'opaque'}, {
								clip: {
	//								autoPlay: false,
	//								autoBuffering: true,
	//								scaling: 'fit',
	//								provider: 'rtmp'
								},
								plugins: {
									rtmp: {
										url: '/flash/flowplayer/flowplayer.rtmp-3.2.3.swf',
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
					//>> title: '콘텐츠 목록',MN00274
					title: '<?=_text('MN00274')?>',
					loadMask: true,

					viewConfig: {
						emptyText: _text('MSG00066')
					},

					store: new Ext.data.JsonStore({
						url: '/modules/batch/store/content_list.php',
						root: 'data',
						fields: [
							{name: 'content_id', type: 'int'},
							{name: 'ud_content_id', type: 'int'},
							'title'
						],
						listeners: {
							load: function(){
								Ext.getCmp('batch_content_list').getSelectionModel().selectFirstRow();
								Ext.getCmp('batch_content_list').fireEvent('rowclick', Ext.getCmp('batch_content_list'));
								Ext.getCmp('batch_content_list').getSelectionModel().selectAll();
							},
							exception: function(self, type, action, options, response, arg){
								//>>Ext.Msg.alert('오류', response.responseText);
								Ext.Msg.alert('<?=_text('MN00022')?>', response.responseText);
							}
						}
					}),
					sm: new Ext.grid.CheckboxSelectionModel(),
					cm: new Ext.grid.ColumnModel({
						defaults: {
							sortable: false,
							menuDisabled: true
						},
						columns: [
							new Ext.grid.RowNumberer(),
							new Ext.grid.CheckboxSelectionModel(),
							//>>{header: '제목', dataIndex: 'title', width: 200}
							{header: '<?=_text('MN00249')?>', dataIndex: 'title', width: 200}
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
						rowdblclick: function(self, i, e){
							var sm = self.getSelectionModel();
							getStreamURL(sm.getSelected().get('content_id'));
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

								getFormItems(sm.getSelected().get('content_id'));
								$f().stop();
							}
						}
					}
				}]
			},{
				region: 'east',
				xtype: 'form',
				id: 'batch_form',
				width: 600,
				frame: true,
				padding: 5,
				autoScroll: true,
				labelWidth: 120,
				defaults: {
				 	labelSeparator: '',
					anchor: '95%'
				},

				buttonAlign: 'left',
				buttons: [
				{
					icon:'/led-icons/application_edit.png',
					scale: 'medium',
					//>>text: '일괄 수정',
					text: '<?=_text('MN00166')?>',
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
								//>>Ext.Msg.alert("정보", "일괄 수정할 필드를 체크하여주세요.");MSG00071
								Ext.Msg.alert("<?=_text('MN00023')?>", "<?=_text('MSG00071')?>");
								return;
							}

							//console.log(records);

							Ext.Msg.show({
								//>>title: '확인',
								title: '<?=_text('MN00024')?>',
								//>>msg: '메타데이터를 일괄 수정 하시겠습니까?',
								msg: '<?=_text('MSG00147')?>',
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
		});
	})



</script>
</head>

<body>

</body>

</html>