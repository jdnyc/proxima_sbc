<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'] . '/store/metadata/buildEditedListTab.php');

function getLogPanel($content_id)
{

	return "{
		title: '수정 로그',
		xtype: 'grid',
		store: new Ext.data.JsonStore({
			autoLoad: true,
			url: '/store/get_log.php',
			baseParams: {
				content_id: '$content_id'
			},
			root: 'data',
			fields: [
				{name: 'log_id'},
				{name: 'user_id'},
                {name :'created_date', type:'date', dateFormat:'YmdHis'}
            ]
		}),
		columns: [
			{header: '수정자', dataIndex: 'user_id'},
            {header: '수정일시', dataIndex: 'created_date', width: 100, renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s'), align: 'center'}
		],
		viewConfig: {
			emptyText: '수정 내역이 없습니다.',
            forceFit: true
		}
	}";
}

function getPreviewList($content_id)
{
	global $db;

	$query = "
	select
		count(*)
	from
		preview_main pm,
		preview_note pn ,
		nps_work_list n,
		( select  max(REGISTDATE) , content_id from preview_main group by  content_id ) maxc
	where
		pm.note_id=pn.note_id
	and
		pm.content_id=maxc.content_id
	and
		pm.content_id=n.content_id
	and
		n.work_type='preview'
	and
		pm.content_id='$content_id'
	";
	$check = $db->queryOne($query);
	if (empty($check)) return false;

	$listview_template = "{
		title: '프리뷰 노트',
		layout:'anchor',
		id: 'preview-{$content_id}',
		frame: true,
		items: [{

			xtype: 'form',
			anchor:'90% 40%',
			padding: 5,
			defaults: {
				labelSeparator: '',
				xtype: 'textfield',
				anchor: '98%',
				readOnly: true
			},
			items: [
					{ fieldLabel: '출연자', name:	'talent'},
					{ fieldLabel: '촬영구분', name:'shotgu'},
					{ fieldLabel: '촬영장소', name: 'shotlo'},
					{ xtype:'datefield', fieldLabel: '촬영일자', name: 'shotdate',format: 'Y-m-d',
					altFormats: 'Y-m-d|Y-m-d H:i:s|YmdHis|Ymd', format: 'Y-m-d'},
					{ fieldLabel: '계절', name: 'season'},
					{ fieldLabel: '촬영기법', name: 'shotmet'},
					{ fieldLabel: '촬영자', name: 'shotor'},
					{ xtype:'datefield', fieldLabel: '작성일자', name:'registdate',format: 'Y-m-d',
					altFormats: 'Y-m-d|Y-m-d H:i:s|YmdHis|Ymd', format: 'Y-m-d'}
			]
		}
		,{

			xtype: 'grid',
			anchor:'90% 55%',
			autoScroll: true,
			//tools: [{
			//	id: 'refresh',
			//	handler: function(e, toolEl, p, tc){
			//		p.store.reload();
			//	}
			//}],
			viewConfig: {
				emptyText: '등록된 데이터가 없습니다.',
				forceFit: true
			},
			sm: new Ext.grid.RowSelectionModel({
				singleSelect: true
			}),
			store: new Ext.data.JsonStore({
				autoLoad: true,
				url: '/store/getPreviewNote.php',
				baseParams: {
					content_id: '$content_id'
				},
				root: 'data',
				fields: [
					{ name:	'note_id' },
					{ name:	'content_id'},
					{ name:	'talent'},
					{ name:	'shotgu'},
					{ name:	'shotlo'},
					{ name:	'shotdate'},
					{ name:	'season'},
					{ name:	'shotmet'},
					{ name:	'shotor'},
					{ name:	'source'},
					{ name:	'registdate'},
					{ name:	'sort'},
					{ name:	'type'},
					{ name:	'start_tc'},
					{ name:	'content'},
					{ name:	'vigo'}
				],
				listeners: {
					load: function(self){
						//console.log(self);

						var record = self.getAt(0);

						//console.log(self);

						if(!Ext.isEmpty(record) )
						{
							var form = Ext.getCmp('preview-{$content_id}').get(0).getForm();
							//var form = self.ownerCt.ownerCt.get(0).getForm();
							form.loadRecord( record );
						}
					}
				}

			}),
			cm: new Ext.grid.ColumnModel({
				columns: [
					new Ext.grid.RowNumberer(),
					//{ header: '순번', dataIndex: 'sort'},
					{ header: '유형', dataIndex: 'type',width: 30, renderer: function(value){
						if(value == 'subtitle')
						{
							return '소제목';
						}
						else if(value == 'content')
						{
							return '내용';
						}
					}},
					{ header: '타임코드', dataIndex: 'start_tc', width: 50 },
					{ header: '내용', dataIndex: 'content'},
					{ header: '비고', dataIndex: 'vigo'}
				]
			})
			,listeners: {
				afterrender: function(self){
					//console.log(self);
					var record = self.getStore().getAt(0);

					if(!Ext.isEmpty(record) )
					{
						//var form = self.ownerCt.get(0).getForm();
						//form.loadRecord( record );
					}
				},
				rowclick : function(  self, rowIndex,  e ) {
					var form = Ext.getCmp('preview-{$content_id}').get(0).getForm();

					//timecodeToSec
					var tc = self.getSelectionModel().getSelected().get('start_tc');

					var record = self.getSelectionModel().getSelected();
					if(!Ext.isEmpty(record) )
					{
						form.loadRecord( record );
					}

					var sec = timecodeToSec(tc);

					Ext.getCmp('player_warp').seek(sec);
					//Ext.getCmp('player_warp').play();
				}
			}
		}]
	}";

	return $listview_template;
}


function getListViewDataFields($columns)
{
	$asciiA = 65;
	$columns = explode(';', $columns);
	foreach ($columns as $v) {
		$result[] = "{name: 'column" . chr($asciiA++) . "'}";
	}
	$result[] = "{name: 'meta_value_id'}";

	return join(",\n", $result);
}

function getListViewColumns($columns, $usr_meta_field_id) //소재영상쪽 컬럼 히든처리를 위해 필드아이디 포함
{
	$asciiA = 65;
	$columns = explode(';', $columns);
	foreach ($columns as $v) {
		if ($usr_meta_field_id == '11879136') {
			if ($v == '순번') {
				$result[] = "{width: .1, header: '$v', dataIndex: 'column" . chr($asciiA++) . "'}";
			} else if ($v == 'Start TC' || $v == 'End TC') {
				$result[] = "{width: .15, header: '$v', dataIndex: 'column" . chr($asciiA++) . "'}";
			} else if ($v == '내용') {
				$result[] = "{header: '$v', dataIndex: 'column" . chr($asciiA++) . "'}";
			} else {
				$asciiA++;
			}
		} else {
			$result[] = "{header: '$v', dataIndex: 'column" . chr($asciiA++) . "'}";
		}
	}

	//	$result[] = "{header: 'meta_value_id', dataIndex: 'meta_value_id', hidden: true}";

	return join(",\n", $result);
}

function getListViewForm($columns)
{
	$asciiA = 65;
	$columns = explode(';', $columns);
	$columnCount = count($columns);

	foreach ($columns as $v) {
		if ($v == '내용') {
			$result[] = "{xtype:'textarea', fieldLabel: '$v',width:400 , name: 'column" . chr($asciiA++) . "'}";
		} else {
			$result[] = "{fieldLabel: '$v',width:400 , name: 'column" . chr($asciiA++) . "'}";
		}
	}

	return array(
		'columnHeight' => ($columnCount * 45 + 20),
		'columns' => join(",\n", $result)
	);
}

function log_list($content_id, $action)
{
	$label = '메타데이터 작업 로그';
	$listview_template = ",{
		xtype: 'panel',
		fieldLabel: '$label',
		labelStyle: 'width: 120px;',
		layout: 'fit',
		height: 300,
		frame: true,
		items: [{
			xtype: 'listview',
			columnSort: false,
			emptyText: '등록된 데이터가 없습니다.',
			singleSelect: true,
			store: new Ext.data.JsonStore({
				autoLoad: true,
				url: '/store/getWorkLogList.php',
				baseParams: {
					content_id: '$content_id',
					action: '$action'
				},
				root: 'data',
				fields: [
					//{name: 'id'},
					{name: 'user_id'},
					//{name: 'link_table_id'},
					{name: 'description'},
					{name: 'created_time'}
				],
				listeners: {
					load: function(self){
						//console.log(self);
					}
				}

			}),
			columns: [
				//{header: 'id', dataIndex: 'id'},
				{header: '작업자', dataIndex: 'user_id'},
				//{header: 'link_table_id', dataIndex: 'link_table_id'},
				{header: '설명', dataIndex: 'description'},
				{header: '수정일자', dataIndex: 'created_time'}
			]
		}]

	}";

	return $listview_template;
}

function listview_template($label, $content_id, $ud_content_id, $usr_meta_field_id, $default_value)
{
	$listview_form			= getListViewForm($default_value);
	$listview_datafields	= getListViewDataFields($default_value);
	$listview_columns		= getListViewColumns($default_value, $usr_meta_field_id);

	$user_id = $_SESSION['user']['user_id'];
	if (checkAllowGrant($user_id, $content_id, GRANT_CONTENT_WRITE)) {
		$is_edit_hidden = 'false';
	} else {
		$is_edit_hidden = 'true';
	}

	$listview_template = "{
		xtype: 'panel',
		hideLabel: true,
		fieldLabel: '$label',
		labelStyle: 'width: 120px',
		layout: 'fit',
		height: 300,
		frame: true,
		submit: function(parent, from, action){
			var list = parent.get(0);
			var tmp = new Array();
			var del_value ='';
			if( action == '삭제' )
			{
				del_value = from[0].data.meta_value_id;
			}

			list.getStore().each(function(i){
				tmp.push(i.data);
			});

			Ext.Ajax.request({
				url: '/store/modifyListViewData.php',
				params: {
					content_id: '$content_id',
					ud_content_id: '$ud_content_id',
					usr_meta_field_id: '$usr_meta_field_id',
					action : action,
					del_value : del_value,
					json_value: Ext.encode(tmp)
				},
				callback: function(opts, success, response){
					if (success)
					{
						try
						{
							var r = Ext.decode(response.responseText);
							if (r.success)
							{
								Ext.getCmp('tc_panel').setVisible(false);
								Ext.getCmp('list{$usr_meta_field_id}').store.reload();

								//Ext.Msg.alert('확인', '완료');

							}
							else
							{
								Ext.Msg.alert('" . _text('MN00254') . "', r.msg);
							}

						}
						catch(e)
						{
							Ext.Msg.alert(e['title'], e['message']);
						}
					}
					else
					{
						Ext.Msg.alert('서버통신오류', response.statusText);
					}
				}
			});
		},
		form: function(outer){
			var w = new Ext.Window({
				width: 600,
				height: {$listview_form['columnHeight']},
				modal: true,
				layout: 'fit',

				items: {
					xtype: 'form',
					padding: 5,
					frame: true,
					autoScroll: true,
					labelSeparator: '',
					defaultType: 'textfield',

					items: [
						{$listview_form['columns']}
					],

					listeners: {
						afterrender: function(self){
							//self.get(0).focus(false, 250);
						}
					}
				},

				buttons: [{
					text: '전송',
					hidden: $is_edit_hidden,
					handler: function(b, e){

						var parent = b.ownerCt.ownerCt;

						Ext.Msg.show({
							title: '확인',
							msg: parent.title+' 하시겠습니까?',
							icon: Ext.Msg.QUESTION,
							buttons: Ext.Msg.OKCANCEL,
							fn: function(btnId){
								if ( btnId == 'ok')
								{
									var form = parent.get(0).getForm();
									var values = form.getFieldValues();
									var list = outer.get(0);
									var list_store = list.store;
									var new_record = new list_store.recordType( values );

									if ( parent.title == '추가' )
									{
										list_store.add( new_record );
									}
									else
									{
										var old_record = list.getSelectedRecords()[0];
										var idx = list_store.indexOf( old_record );

										list_store.remove( old_record );
										list_store.insert( idx, new_record );
									}

									outer.submit(outer, parent);
								}
							}
						});
					}
				},{
					text: '취소',
					handler: function(b, e){
						b.ownerCt.ownerCt.close();
					}
				}]
			});

			return w;
		},

		tbar: [{
			icon: '/led-icons/application_add.png',
			hidden: $is_edit_hidden,
			text: _text('MN00033'),
			handler: function(b, e){
				var parent = b.ownerCt.ownerCt;
				var form_store = Ext.getCmp('list{$usr_meta_field_id}').getStore();
				var numIndex = form_store.getCount()+1;

				Ext.getCmp('list{$usr_meta_field_id}').clearSelections();
				Ext.getCmp('tc_panel').setTitle(_text('MN00033'));
				Ext.getCmp('tc_panel').buttons[2].setIcon( '/led-icons/application_add.png' );
				Ext.getCmp('tc_panel').buttons[2].setText(_text('MN00033'));
				Ext.getCmp('tc_panel').setVisible(false);
				Ext.getCmp('tc_panel').setVisible(true);
				Ext.getCmp('tc_panel').get(0).getForm().items.get(0).setValue(numIndex);

				if( form_store.getCount() == 0 )
				{
					Ext.getCmp('player_warp').seek(0);
					Ext.getCmp('player_warp').play();

				}
				else if( form_store.getCount() > 0 )
				{
					var end_tc_time = form_store.getAt(form_store.getCount()-1).data.columnC;

					if( !Ext.isEmpty(end_tc_time) && end_tc_time.length == 8 )
					{
						var eh = end_tc_time.substring(0,2);
						var ei = end_tc_time.substring(3,5);
						var es = end_tc_time.substring(6,8);

						var endsec = Number(eh)*3600 + Number(ei)*60 + Number(es) + 1;

						eh = parseInt( endsec / 3600 );
						ei = parseInt( ( endsec % 3600) / 60 );
						es = parseInt( ( endsec % 3600) % 60 );

						eh = String.leftPad(eh, 2, '0');
						ei = String.leftPad(ei, 2, '0');
						es = String.leftPad(es, 2, '0');

						Ext.getCmp('secIn').setValue( eh+':'+ei+':'+es );

						Ext.getCmp('player_warp').play();
						Ext.getCmp('player_warp').seek(endsec);
					}
				}

			}
		},{
			xtype: 'tbseparator',
			width: 20
        },{
			icon: '/led-icons/application_edit.png',
			hidden: $is_edit_hidden,
			text: _text('MN00043'),//!!수정
			disableGroup: true,
			disabled: true,
			handler: function(b, e){
				var parent = b.ownerCt.ownerCt;
				if (parent.get(0).getSelectionCount() == 0)
				{
					Ext.Msg.alert(_text('MN00003'), _text('MSG00084'));
					return;
				}
				var list = parent.get(0);
				var records = list.getSelectedRecords()[0];

				Ext.getCmp('tc_panel').setTitle(_text('MN00043'));
				Ext.getCmp('tc_panel').buttons[2].setIcon( '/led-icons/application_edit.png' );
				Ext.getCmp('tc_panel').buttons[2].setText(_text('MN00043'));
				Ext.getCmp('tc_panel').setVisible(true);
				Ext.getCmp('tc_panel').get(0).getForm().loadRecord( records );
			}
		},{
			xtype: 'tbseparator',
			width: 20
        },{
			icon: '/led-icons/application_delete.png',
			hidden: $is_edit_hidden,
			text: _text('MN00034'),//!!삭제
			disableGroup: true,
			disabled: true,
			handler: function(b, e){
				Ext.Msg.show({
					title: _text('MN00003'),
					msg: _text('MSG00140'),
					icon: Ext.Msg.QUESTION,
					buttons: Ext.Msg.OKCANCEL,
					fn: function(btnId){
						if ( btnId == 'ok')
						{
							var action = _text('MN00034');
							var parent = b.ownerCt.ownerCt;
							var list = parent.get(0);

							var del_list= list.getSelectedRecords();

							list.getStore().remove( list.getSelectedRecords() );

							parent.submit(parent, del_list, action);
						}
					}
				});
			}
		}],

		items: [{
			xtype: 'listview',
			id: 'list{$usr_meta_field_id}',
			columnSort: false,
			emptyText: _text('MSG00148'),//!!emptyText: '등록된 데이터가 없습니다.',
			singleSelect: true,
			store: new Ext.data.JsonStore({
				autoLoad: true,
				url: '/store/getListView.php',
				baseParams: {
					content_id: '$content_id',
					usr_meta_field_id: '$usr_meta_field_id'
				},
				root: 'data',
				fields: [
					$listview_datafields
				],
				listeners: {
					load: function(self){
						//console.log(self);
					}
				}

			}),
			columns: [
				$listview_columns
			],
			listeners: {
				selectionchange: function(self, selections){
					var a = self.ownerCt.getTopToolbar().find('disableGroup', true);

					if( $is_edit_hidden ) return;

					if (self.getSelectionCount() == 0)
					{
						Ext.each(a, function(i){
							i.disable();
						});
					}
					else
					{
						Ext.each(a, function(i){
							i.enable();
						});
					}
				},
				click: function(self, selections){

					var time = self.getSelectedRecords()[0].data.columnB;
					if( !Ext.isEmpty(time) )
					{
						time = time.trim();
					}

					if(time.length =='8')
					{
						var h = time.substring(0,2);
						var i = time.substring(3,5);
						var s = time.substring(6,8);

						var sec = Number(h)*3600 + Number(i)*60 + Number(s);
						Ext.getCmp('player_warp').play();
						Ext.getCmp('player_warp').seek(sec);
						Ext.getCmp('secIn').setValue(h+':'+i+':'+s);
					}

					var outTime = self.getSelectedRecords()[0].data.columnC;

					if(outTime.length =='8')
					{
						var oh = outTime.substring(0,2);
						var oi = outTime.substring(3,5);
						var os = outTime.substring(6,8);
						Ext.getCmp('secOut').setValue(oh+':'+oi+':'+os);
					}

					var list = self;
					var records = list.getSelectedRecords()[0];

					Ext.getCmp('tc_panel').setTitle(_text('MN00043'));
					Ext.getCmp('tc_panel').buttons[2].setIcon( '/led-icons/application_edit.png' );
					Ext.getCmp('tc_panel').buttons[2].setText(_text('MN00043'));
					Ext.getCmp('tc_panel').setVisible(true);

					var tc_category = Ext.getCmp('tc_category');
					if ( tc_category )
					{
						tc_category.getTree().loader.baseParams.path = records.get('columnE');
						var node = tc_category.getTree().getRootNode();
						tc_category.getTree().loader.load(node);
					}


					Ext.getCmp('tc_panel').get(0).getForm().loadRecord( records );
				}
			}
		}]

	},";

	return $listview_template;
}

function buildButtons($container_id_tmp, $content_id, $ud_content_id, $bs_content_id, $status, $user_id, $content_user, $container_key = null, $is_watch_meta = null, $customWindowId = null)
{
	global $CG_LIST;
	global $AUDIO_LIST;
	global $db;

	$history_edited_yn = $db->queryOne("
		SELECT   COALESCE((
			SELECT   USE_YN
			FROM   BC_SYS_CODE A
			WHERE   A.TYPE_ID = 1
			AND      A.CODE='HISTORY_EDIT_METADATA'
		), 'N') AS USE_YN
		FROM   BC_MEMBER
		WHERE   USER_ID = '" . $_SESSION['user']['user_id'] . "'
    ");
    $buttons = [];
    
   // if(true){
       
        $buttons[] = "'->'";
    //}
//

	//등록대기 상태이고 관리자일 경우는 승인,반려가 다 보여짐
	if (($status == CONTENT_STATUS_REG_READY || $status == CONTENT_STATUS_REACCEPT)
		&& (in_array(ADMIN_GROUP, $_SESSION['user']['groups']) || $_SESSION['user']['is_admin'] == 'Y')
	) {
		//$buttons[] = buttonAccept($content_id);
		//$buttons[] = buttonRefuse($content_id);
	}

	//등록대기 상태이고 그룹 승인자일 경우에는 승인만 보여짐
	if ((($status == CONTENT_STATUS_REG_READY || $status == CONTENT_STATUS_REACCEPT)
		&& (checkAllowGrant($user_id, $content_id, 'approve')))) {
		//$buttons[] = buttonAccept($content_id);
	}

	//등록대기 상태이고 그룹 승인자일 경우에는 반려만 보여짐
	if (($status == CONTENT_STATUS_REG_READY || $status == CONTENT_STATUS_REACCEPT)
		&& checkAllowGrant($user_id, $content_id, 'restoration')
	) {
		//$buttons[] = buttonRefuse($content_id);
	}

	//반려중일때 버튼 재승인요청
	if ((($status == CONTENT_STATUS_REFUSE)
		&& checkAllowGrant($user_id, $content_id, 'restoration'))) {
		//$buttons[] = buttonAcceptRequest($content_id);
		//$buttons[] = "{xtype: 'tbfill'}";
	}

	//반려중일때 버튼 삭제
	if ((($status == CONTENT_STATUS_REFUSE)
		&& checkAllowGrant($user_id, $content_id, 'restoration'))) {
		//$buttons[] = buttonDelete($content_id);
		//$buttons[] = "{xtype: 'tbfill'}";
	}

	if ($_POST['mode'] == 'cg_delete') {
		$buttons[] = buttonNpsCGDelAccept($content_id);
		$buttons[] = buttonNpsCGDelRefuse($content_id);
	}

	// 수정 권한이 있으면 수정 버튼 추가
	if (($user_id
		&& (checkAllowUdContentGrant($user_id, $ud_content_id, GRANT_EDIT)))) {

		$buttons[] = buttonEdit($content_id, $container_id_tmp, $customWindowId);

		if ($history_edited_yn == 'Y') {
			$buttons[] = buttonContentHistory($content_id, $container_id_tmp);
		}

		if ($container_key != 0) {
			$buttons[] = buttonClose($content_id, $container_id_tmp);
		}
	}

	if ($is_watch_meta == 'Y') {
		$buttons[] = buttonWatchConfirm($content_id);
    }
    
    $buttons[] = buttonTapeLocation($content_id, $container_id_tmp);

	if (isset($buttons)) {
		$buttons = join(',', $buttons);
	}

	return $buttons;
}

function buttonNpsCGDelAccept($content_id)
{
	$result = "{
		text : '삭제',
		scale: 'medium',
		icon : '/led-icons/delete.png',
		handler : function(){

			Ext.Msg.show({
				title: '삭제',
				msg: '삭제하시겠습니까?',
				buttons: Ext.Msg.YESNO,
				fn: function(btn){
					if(btn == 'yes'){
						Ext.Ajax.request({
							url: '/store/delete_deleteList.php',
							params: {
								content_id : $content_id
							},
							callback: function(opts, success, response){
								if (success){
									try{
										var result = Ext.decode(response.responseText);
										if (!result.success){
											Ext.Msg.show({
												title: '오류',
												msg: result.msg,
												icon: Ext.Msg.ERROR,
												buttons: Ext.Msg.OK
											});
										}
										else
										{
											Ext.Msg.show({
												title: '확인',
												msg: '삭제되었습니다.<br />창을 닫으시겠습니까?',
												buttons: Ext.Msg.OK,
												fn: function(btn){
													if(btn == 'ok'){
														Ext.getCmp('winDetail').close();
													}
												}
											});
											Ext.getCmp('delete_list').getStore().reload();
										}
									}catch (e){
										Ext.Msg.show({
											title: '오류',
											msg: e['message'],
											icon: Ext.Msg.ERROR,
											buttons: Ext.Msg.OK
										});
									}
								}
							}
						});
					}
				}
			});

		}
	}";
	return $result;
}

function buttonNpsCGDelRefuse($content_id)
{
	$result = "{
		text : '복원',
		scale: 'medium',
		icon : '/led-icons/arrow_undo.png',
		handler : function(){

			Ext.Msg.show({
				title: '복원',
				msg: '복원하시겠습니까?',
				buttons: Ext.Msg.YESNO,
				fn: function(btn){
					if(btn == 'yes'){
						Ext.Ajax.request({
							url: '/store/return_deleteList.php',
							params: {
								content_id : $content_id
							},
							callback: function(opts, success, response){
								if (success){
									try{
										var result = Ext.decode(response.responseText);
										if (!result.success){
											Ext.Msg.show({
												title: '오류',
												msg: result.msg,
												icon: Ext.Msg.ERROR,
												buttons: Ext.Msg.OK
											});
										}else
										{
											Ext.Msg.show({
												title: '확인',
												msg: '복원되었습니다.<br />창을 닫으시겠습니까?',
												buttons: Ext.Msg.OK,
												fn: function(btn){
													if(btn == 'ok'){
														Ext.getCmp('winDetail').close();
													}
												}
											});
											Ext.getCmp('delete_list').getStore().reload();
										}
									}catch (e){
										Ext.Msg.show({
											title: '오류',
											msg: e['message'],
											icon: Ext.Msg.ERROR,
											buttons: Ext.Msg.OK
										});
									}
								}
							}
						});
					}
				}
			});
		}
	}";
	return $result;
}

function buttonRequestModify($content_id)
{
	$result = "{
		hidden: true,
		text: '수정 요청',
		scale: 'medium',
		handler: function(btn, e){
			new Ext.Window({
				title: '수정 요청 코멘트',
				width: 400,
				height: 209,
				resizable: false,
				modal: true,
				layout: 'fit',

				items: {
					xtype: 'form',
					baseCls: 'x-plain',
					border: false,
					labelSeparator: '',
					defaults: {
						anchor: '100%',
						border: false
					},

					items: [{
						xtype: 'textarea',
						height: 140,
						hideLabel: true,
						name: 'comment'
					}]
				},

				buttonAlign: 'center',
				buttons: [{
					text: '작성 완료',
					scale: 'medium',
					handler: function(btn, e){
						Ext.Msg.show({
							title: '정보',
							msg: '전송 하시겠습니까?',
							buttons: Ext.Msg.OKCANCEL,
							fn: function(btnId, text, opts){
								if(btnId == 'cancel') return;

								var w = Ext.Msg.wait('등록 요청 중입니다.');

								Ext.Ajax.request({
									url: '/store/add_comment.php',
									params: {
										content_id: $content_id,
										comment: btn.ownerCt.ownerCt.get(0).get(0).getValue()
									},
									callback: function(opts, success, response){

										w.hide();
										if(success)
										{
											try
											{
												var r  = Ext.decode(response.responseText);
												if(!r.success)
												{
													Ext.Msg.alert('오류', r.msg);
													return;
												}

												btn.ownerCt.ownerCt.close();
											}
											catch(e)
											{
												Ext.Msg.alert(e['name'], e['message']);
											}
										}
										else
										{
											Ext.Msg.alert('오류', response.statusText);
										}
									}
								})

							}
						})
					}
				},{
					text: '작성 취소',
					scale: 'medium',
					handler: function(btn, e){
						btn.ownerCt.ownerCt.close();
					}
				}]
			}).show();
		}";

	return $result;
}
function buttonAcceptRequest($content_id)
{
	$result = "{
		text: '재승인 요청',
		scale: 'medium',
	//	disabled: true,
		handler: function(b, e) {
			Ext.Msg.show({
				title: '확인',
				msg: '재승인 요청하시겠습니까?',
				minWidth: 100,
				modal: true,
				icon: Ext.MessageBox.QUESTION,
				buttons: Ext.Msg.OKCANCEL,
				fn: function(btnId){
					if(btnId=='cancel') return;

					Ext.Ajax.request({
						url: '/store/accept_request.php',
						params:{
							id: $content_id
						},

						callback: function(options,success,response){
							if(success){
								Ext.Msg.show({
									title: '확인',
									msg: '요청되었습니다.<br />창을 닫으시겠습니까?',
									icon: Ext.Msg.QUESTION,
									buttons: Ext.Msg.OKCANCEL,
									fn: function(btnId){
										if (btnId == 'ok')
										{
											Ext.getCmp('winDetail').close();
											Ext.getCmp('refuse_list').store.reload();
										}
									}
								});
							}
							else{
								Ext.Msg.alert('등록','요청 실패',response.statusText);
							}
						}
					});
				}
			});
		}
	}";

	return $result;
}
function buttonDelete($content_id)
{
	$result = "{
		text: _text('MN00034'),
		icon: '/led-icons/delete.png',
		scale: 'medium',
	//	disabled: true,
		handler: function(b, e) {
			Ext.Msg.show({
				title: _text('MN00003'),
				msg: _text('MSG00140'),
				minWidth: 100,
				modal: true,
				icon: Ext.MessageBox.QUESTION,
				buttons: Ext.Msg.OKCANCEL,
				fn: function(btnId){
					if(btnId=='cancel') return;

					var action = 'delete';
					var content_id =[{'content_id' : '$content_id' }];

					Ext.Ajax.request({
						url: '/store/delete_contents.php',
						params:{
							action: action,
							content_id: Ext.encode(content_id)
						},

						callback: function(options,success,response){
							if(success){
								Ext.Msg.show({
									title: _text('MN00003'),//!!확인
									msg: _text('MSG00040')+'<br />'+_text('MSG00190'),
									icon: Ext.Msg.QUESTION,
									buttons: Ext.Msg.OKCANCEL,
									fn: function(btnId){
										if (btnId == 'ok')
										{
											Ext.getCmp('winDetail').close();
										}
									}
								});
							}
							else{//!!삭제실패
								Ext.Msg.alert(_text('MN00022'),_text('MN00034') + _text('MN00012'),response.statusText);
							}
						}
					});
				}
			});
		}
	}";

	return $result;
}

function buttonAccept($content_id)
{
	$result = "{
		icon:'/led-icons/accept.png',
		text: '승인',
		scale: 'medium',
	//	disabled: true,
		handler: function(b, e) {
			Ext.Msg.show({
				title: '확인',
				msg: '승인하시겠습니까?',
				minWidth: 100,
				modal: true,
				icon: Ext.MessageBox.QUESTION,
				buttons: Ext.Msg.OKCANCEL,
				fn: function(btnId){
					if(btnId=='cancel')	return;

					Ext.Ajax.request({
						url: '/store/wait_regist_update.php',
						params:{
							id: $content_id
						},

						callback: function(options,success,response){
							if(success){
								Ext.Msg.show({
									title: '확인',
									msg: '승인되었습니다.<br />창을 닫으시겠습니까?',
									icon: Ext.Msg.QUESTION,
									buttons: Ext.Msg.OKCANCEL,
									fn: function(btnId){
										if (btnId == 'ok')
										{
											Ext.getCmp('winDetail').close();
											Ext.getCmp('tab_warp').getActiveTab().store.reload();
										}
									}
								});

							}
							else{
								Ext.Msg.alert('등록','등록완료 실패',response.statusText);
							}
						}
					});
				}
			});
		}
	}";

	return $result;
}
function buttonRefuse($content_id)
{
	$result = "{
		icon: '/led-icons/cancel.png',
		text: '반려',
		scale: 'medium',
		//disabled: true,
		handler: function(b, e) {

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
							msg: '반려 하시겠습니까?',
							minWidth: 100,
							modal: true,
							icon: Ext.MessageBox.QUESTION,
							buttons: Ext.Msg.OKCANCEL,
							fn: function(btnId){
								if(btnId=='cancel') return;

								Ext.Ajax.request({
									url: '/store/wait_regist_refuse.php',
									params:{
										id: $content_id,
										refuse_list: refuse_list,
										description: refuse
									},
									callback: function(options,success,response){
										refuse_win.destroy();
										if(success)
										{
											Ext.Msg.show({
												title: '확인',
												msg: '반려되었습니다.<br />창을 닫으시겠습니까?',
												icon: Ext.Msg.QUESTION,
												buttons: Ext.Msg.OKCANCEL,
												fn: function(btnId){
													if (btnId == 'ok')
													{
														Ext.getCmp('winDetail').close();
														Ext.getCmp('tab_warp').getActiveTab().store.reload();
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
	}";

	return $result;
}

function buttonWatchConfirm($content_id)
{
	$result = "{
		text: '<span style=\"position:relative;top:1px;\"><i class=\"fa fa-check\" style=\"font-size:13px;\"></i></span>&nbsp;'+'등록',//'닫기'
		scale: 'medium',
		handler: function(b, e) {
			var w = Ext.Msg.wait(_text('MN00065'), _text('MN00023'));

			Ext.Ajax.request({
				url: '/store/watchfolder/watchfolder_mgmt_action.php',
				params: {
					action: 'accept',
					content_id: '" . $content_id . "'
				},
				callback: function(opts, success, response){
					w.hide();
					if (success)
					{
						try
						{
							var r = Ext.decode(response.responseText);
							if (!r.success)
							{
								Ext.Msg.alert(_text('MN00022'), r.msg);
							}
							else
							{
								Ext.getCmp('winDetail').close();
								Ext.getCmp('watchfolder_mgmt_grid').getStore().reload();
							}
						}
						catch (e)
						{
							Ext.Msg.alert(e['name'], e['message']);
						}
					}
					else
					{
						Ext.Msg.alert(_text('MN00022'), response.ststusText+'( '+response.status+' )');
					}
				}
			});
		}
	}";

	return $result;
}

function buttonClose($content_id, $container_id_tmp)
{
	$result = "{
		text: '<span style=\"position:relative;top:1px;\"><i class=\"fa fa-close\" style=\"font-size:13px;\"></i></span>&nbsp;'+_text('MN00031'),//'닫기'
		//icon: '/led-icons/cross.png',
		scale: 'medium',
		handler: function(b, e) {
			Ext.getCmp('winDetail').close();

			/*

			if ( ! Ext.isEmpty(Ext.getCmp('tab_warp'))) {
				Ext.getCmp('tab_warp').getActiveTab().get(0).getStore().reload();
			}

			// CG메인 화면
			if ( ! Ext.isEmpty(Ext.getCmp('tab_cg'))) {
				if (Ext.getCmp('tab_cg').getActiveTab() == Ext.getCmp('east-menu')) {
					Ext.getCmp('east-menu').get(0).getStore().reload();
				} else {
					Ext.getCmp('tab_cg').getActiveTab().getStore().reload();
				}
			}

			if ( ! Ext.isEmpty(Ext.getCmp('topic-tree'))) {
				var root = Ext.getCmp('topic-tree').getRootNode();
				Ext.getCmp('topic-tree').getLoader().load(root);
			}

			*/
		}
	}";

	return $result;
}

function buttonNewEdit($content_id, $container_id_tmp)
{
	$result = "{
		//icon:'/led-icons/application_edit.png',
		text: '<span style=\"position:relative;top:1px;\"><i class=\"fa fa-edit\" style=\"font-size:13px;\"></i></span>&nbsp;'+_text('MN00043'),
		scale: 'medium',
		submit: function(callback){
			var w = Ext.Msg.wait(_text('MN00065'), _text('MN00023'));

			Ext.getCmp('user_metadata_$container_id_tmp').getForm().items.each(function(i){

				if (i.xtype == 'checkbox' && !i.checked) {
					i.el.dom.checked = true;
					i.el.dom.value = '';
				}
			});

			var p = Ext.getCmp('user_metadata_$container_id_tmp').getForm().getValues();


			if( !Ext.getCmp('user_metadata_$container_id_tmp').getForm().isValid() )
			{
				Ext.Msg.alert(_text('MN00023'), '필수 입력 항목을 채워주세요');
				return;
			}

			//2010-11-11
			if (Ext.getCmp('category'))
			{
				var tn = Ext.getCmp('category').treePanel.getSelectionModel().getSelectedNode();
				p.c_category_id = tn.attributes.id;
			}



			// 메타데이터 업데이트
			Ext.Ajax.request({
				url: '/store/content_edit.php',
				params: p,
				callback: function(opts, success, response){
					w.hide();
					if (success)
					{
						try
						{
							var r = Ext.decode(response.responseText);
							if (!r.success)
							{
								Ext.Msg.alert(_text('MN00022'), r.msg);
							}
							else
							{
								Ext.getCmp('user_metadata_$container_id_tmp').getForm().items.each(function(i){
									if ( i.originalValue != undefined )
									{
										i.originalValue = i.getValue();
									}
								});

								if ( Ext.isFunction(callback) )
								{
									callback();
								}
							}
						}
						catch (e)
						{
							Ext.Msg.alert(e['name'], e['message']);
						}
					}
					else
					{
						Ext.Msg.alert(_text('MN00022'), response.ststusText+'( '+response.status+' )');
					}

				}
			});
		},
		listeners: {
			click: function(self, pass_conform){

				if ( pass_conform == true )
				{
					self.submit(function(){
						Ext.Msg.alert(_text('MN00003'), _text('MSG00087'));
					});
				}
				else
				{

					self.submit(function(){
					});

				}
			}
		},
		handler: function(b, e) {
			// 2011-01-20 박정근
			// listeners 에 click 으로 변경
		}
	}";

	return $result;
}

function buttonContentHistory($content_id, $container_id_tmp)
{

	$edited_list = buildEditedListTab($content_id, "height: 470");
	$result = "{
					text: '<span style=\"position:relative;top:1px;\"><i class=\"fa fa-history\" style=\"font-size:13px;\"></i></span>&nbsp;'+_text('MN02196'),
					scale: 'medium',
					handler: function(b, e){
						var win_ContentHistory = new Ext.Window({
							title : '" . _text('MN02196') . "',
							width: 650,
							modal: true,
							height: 540,
							miniwin: true,
							resizable: false,
							buttonAlign: 'center',
							items: [
								$edited_list
							],
							fbar:
							 [{
								xtype:'button',
								scale:'medium',
								text : '<span style=\"position:relative;top:1px;\"><i class=\"fa fa-close\" style=\"font-size:13px;\"></i></span>&nbsp;'+_text('MN00031'),
								//text: '" . _text('MN00031') . "',
								handler : function(e)
								{
									win_ContentHistory.close();
								}
							 }]
						}).show();
						//win_ContentHistory.show();
					}
				}";
	return $result;
}

function buttonTapeLocation($content_id, $container_id_tmp)
{

	//$edited_list = buildEditedListTab($content_id, "height: 470");
	$result = "{
					text: '<span style=\"position:relative;top:1px;\"><i class=\"fa fa-history\" style=\"font-size:13px;\"></i></span>&nbsp;LTO테이프정보',
					scale: 'medium',
					handler: function(b, e){
                        var win_tapeLoc = new Ext.Window({
							title : '보관위치',
							width: 650,
							modal: true,
							height: 500,
							miniwin: true,
							resizable: false,
							buttonAlign: 'center',
							items: [new Ariel.archiveManagement.offlineTapeLocationList({_contentId:'$content_id',height: 400}) ],
							fbar:
							 [{
								xtype:'button',
								scale:'medium',
								text : '<span style=\"position:relative;top:1px;\"><i class=\"fa fa-close\" style=\"font-size:13px;\"></i></span>&nbsp;'+_text('MN00031'),
								//text: '" . _text('MN00031') . "',
								handler : function(b,e)
								{
									win_tapeLoc.close();
								}
							 }]
						}).show();
					}
				}";
	return $result;
}

function buttonEdit($content_id, $container_id_tmp, $customWindowId)
{
	global $db;

	$query = "SELECT	coalesce((
				SELECT	use_yn
				FROM	bc_sys_code a
				WHERE	a.type_id = 1
				AND		a.code = 'hrdk_dtl_panel'), 'n') as use_yn
			FROM	(
					SELECT	user_id
					FROM	bc_member
					WHERE	user_id = '" . $_SESSION['user']['user_id'] . "') DUAL";
	$v_hrdk_panel_use_yn = $db->queryOne($query);

	// 저장 전 작업에 대한 로직 문자열을 얻어온다.
	$beforeSaveJsLogic = '';
	if (defined('CUSTOM_ROOT') && class_exists('\ProximaCustom\core\MetadataManager')) {
		$beforeSaveJsLogic = \ProximaCustom\core\MetadataManager::getBeforeSaveJsLogic();
	}

	$result = "{
		//icon:'/led-icons/application_edit.png',
		text: '<span style=\"position:relative;top:1px;\"><i class=\"fa fa-edit\" style=\"font-size:13px;\"></i></span>&nbsp;'+_text('MN00043'),//수정
		scale: 'medium',
		submit: function(callback){
			var w = Ext.Msg.wait(_text('MN00065'), _text('MN00023'));

			// 저장전 커스텀 로직
			{$beforeSaveJsLogic}

            var p = Ext.getCmp('user_metadata_$container_id_tmp').getForm().getValues();

			Ext.getCmp('user_metadata_$container_id_tmp').getForm().items.each(function(i){

				if (i.xtype == 'checkbox' && !i.checked) {
					i.el.dom.checked = true;
					i.el.dom.value = '';
				}

				if(i.xtype == 'combo' || i.xtype == 'g-combo'){
					var kval = i.id ;
                    if( !Ext.isEmpty(i.name) ){
                        p[i.name] = i.getValue();
                    }
                    p[i.id] = i.getValue();
                }
                if(i.xtype == 'c-tree-combo'){
					var kval = i.id ;
					p[i.name] = i.getValue();
				}

				if(i.xtype == 'c-tree-combo'){
					var kval = i.id ;
					p[i.name] = i.getValue();

				}
			});

			if( !Ext.getCmp('user_metadata_$container_id_tmp').getForm().isValid() )
			{
				//필수 항목에 값을 넣어주세요
				Ext.Msg.alert(_text('MN00023'), _text('MSG00125'));
				return;
			}

			//2010-11-11
			if (Ext.getCmp('category'))
			{
				var tn = Ext.getCmp('category').treePanel.getSelectionModel().getSelectedNode();
				p.c_category_id = tn.attributes.id;
			}
			if (Ext.getCmp('category_$content_id'))
			{
				var tn = Ext.getCmp('category_$content_id').treePanel.getSelectionModel().getSelectedNode();
				p.c_category_id = tn.attributes.id;
			}
			// 메타데이터 업데이트
			Ext.Ajax.request({
				url: '/store/content_edit.php',
				params: p,
				callback: function(opts, success, response){
					w.hide();
					if (success)
					{
						try
						{
							var r = Ext.decode(response.responseText);
							if (!r.success)
							{
								Ext.Msg.alert(_text('MN00022'), r.msg);
							}
							else
							{
								Ext.getCmp('user_metadata_$container_id_tmp').getForm().items.each(function(i){
									if ( i.originalValue != undefined )
									{
										i.originalValue = i.getValue();
									}
								});

								if ( Ext.isFunction(callback) )
								{
									callback();
								}
							}
						}
						catch (e)
						{
							Ext.Msg.alert(e['name'], e['message']);
						}
					}
					else
					{
						Ext.Msg.alert(_text('MN00022'), response.ststusText+'( '+response.status+' )');
					}

				}
			});
		},
		listeners: {
			click: function(self, pass_conform){

				if ( pass_conform == true )
				{
					self.submit(function(){
						Ext.Msg.alert(_text('MN00003'), _text('MSG00087'));
					});
				}
				else
				{
					Ext.Msg.show({
						title: _text('MN00003'),
						msg: _text('MSG00189'),
						icon: Ext.Msg.QUESTION,
						buttons: Ext.Msg.YESNO,
						fn: function(btnId){
							if (btnId == 'yes')
							{
								self.submit(function(){
									Ext.Msg.show({
										title: _text('MN00003'),
										msg: _text('MSG00087')+'<br />'+_text('MSG00190'),
										icon: Ext.Msg.QUESTION,
										buttons: Ext.Msg.OKCANCEL,
										fn: function(btnId){
											if (btnId == 'ok')
											{
            									var customWindow = Ext.getCmp('" . $customWindowId . "');
                                                if(!Ext.isEmpty(customWindow)){
                                                    customWindow.close();
												}else{
													Ext.getCmp('winDetail').close();
												};
												

												if(!Ext.isEmpty(Ext.getCmp('tab_warp')))
												{
													Ext.getCmp('tab_warp').getActiveTab().get(0).getStore().reload();
												}
												if(!Ext.isEmpty(Ext.getCmp('tab_cg')))//CG메인 화면
												{
													if(Ext.getCmp('tab_cg').getActiveTab() == Ext.getCmp('east-menu') )
													{
														Ext.getCmp('east-menu').get(0).getStore().reload();
													}
													else
													{
														Ext.getCmp('tab_cg').getActiveTab().getStore().reload();
													}
												}
											}
										}
									});
								});
							}
						}
					});
				}
			}
		},
		handler: function(b, e) {
			// 2011-01-20 박정근
			// listeners 에 click 으로 변경
		}
	}";

	return $result;
}

function buttonBIS($content_id, $container_id_tmp)
{
	$result = "{
		icon:'/led-icons/application_form.png',
		text: 'BIS 검색',
		scale: 'medium',
		listeners: {
			click: function(self){
                           var form = self.ownerCt.ownerCt;

                           var category_id = form.getForm().findField('k_category_id').getValue();

                           var bis_win = new Ext.Window({
                                title: 'BIS 소재검색',
                                width: 600,
                                height: 600,
                                modal: true,
                                layout: 'fit',

                                items: [
                                    new Ariel.Nps.BISEpisode({metaForm: form.id})
                                ],

                                buttons: [{
                                    text: '닫기',
                                    handler: function(btn, e) {
                                        bis_win.close();
                                    }
                                }],

                                listeners: {
                                    render: function(self) {
                                        Ext.getCmp('episode_list').getStore().load({
                                            params: {
                                                category_id: category_id
                                            }
                                        });

                                        Ext.getCmp('episode_list').events['rowdblclick'].clearListeners();

                                        var set_form = function(grid, rowIndex, e) {
                                                var metaForm = grid.ownerCt.metaForm;
                                                var p = Ext.getCmp(metaForm).getForm();

                                                var episode_list = grid.getSelectionModel();

                                                if(episode_list.hasSelection()) {
                                                    var rec = episode_list.getSelected();
                                                    p.loadRecord(rec);
                                                    bis_win.close();
                                                } else {
                                                    Ext.Msg.alert( _text('MN00023'), 'BIS항목을 선택해 주세요');
                                                }
                                        };

                                        Ext.getCmp('episode_list').addListener('rowdblclick', set_form);
                                    }
                                }
                           });

                           bis_win.show();
                        }
                }
	}";

	return $result;
}

function buildFormDMC($content_id)
{
	global $db;
	$label = "방송이력 : ";

	$result = "{
				xtype:'panel',
				frame:true,
				title:'방송이력',
				layout:'form',
				width:150,
				padding:5,
				items:[
					{
					xtype:'form',
					autoScroll: true,
					frame:true,
					width:472,
					fieldLabel:'방송이력',
					padding: 10,
					labelSeparator: '',
					defaults: {
						xtype: 'textfield'
					},
					items:[{
						xtype: 'listview',
						height:200,
						emptyText: '등록된 데이터가 없습니다.',
						store: new Ext.data.JsonStore({
							autoLoad: true,
							url: '/store/dmc_meta_store.php',
							root: 'data',
							fields: [
								'brodKind',
								'disProd',
								'disBrod',
								'brodDate'
							],
							listeners: {
								beforeload: function(self, opts){
									self.baseParams = {
										content_id: $content_id
									}
								},
								load: function(self){
								}
							}
						}),
						columns: [
							{header: '매체', dataIndex: 'brodKind',align:'center'},
							{header: '제작구분', dataIndex: 'disProd',align:'center'},
							{header: '방송구분', dataIndex: 'disBrod',align:'center'},
							{header: '방송일자', dataIndex: 'brodDate',align:'center'}
						]
					}]
				}]
			},";

	return $result;
}

function buttonRestore($content_id)
{
	$result = "{
		text: _text('MN00051'),
		disabled: false,
		scale: 'medium',
		handler: function(b, e) {
			Ext.Msg.show({
				title: _text('MN00003'),
				msg: _text('MSG00188'),
				minWidth: 100,
				modal: true,
				icon: Ext.MessageBox.QUESTION,
				buttons: Ext.Msg.OKCANCEL,
				fn: function(btnId){
					if(btnId=='cancel')
					{
						return;
					}
					else
					{
						Ext.Ajax.request({
							url: '/store/restore_request.php',
							params:{
								content_id: $content_id
							},

							callback: function(options,success,response){
								if(success)
								{
									Ext.Msg.show({
										title: _text('MN00003'),
										msg: _text('MSG00083'),
										icon: Ext.Msg.QUESTION,
										buttons: Ext.Msg.OKCANCEL,
										fn: function(btnId){
											if (btnId == 'ok')
											{
												//Ext.getCmp('winDetail').close();
											}
										}
									});
								}
								else
								{
									Ext.Msg.alert(_text('MN00003'),_text('MSG00088'),response.statusText);
								}
							}
						});
					}
				}
			});
		}
	}";

	return $result;
}

function listview_container($label, $content_id, $ud_content_id, $usr_meta_field_id, $default_value)
{
	$listview_datafields = getListViewDataFields($default_value);
	$listview_columns = getListViewColumns($default_value);
	$item = "{
			xtype: 'panel',
			layout: 'fit',
			frame: true,
			border: true,
			items: [{
				xtype: '$xtype',
				emptyText: '등록된 데이터가 없습니다.',
				height: 250,
				listeners: {
					render: function(self){
						self.store.load({
							params: {
								content_id: '" . $content_id . "'
							}
						});
					}
				},
				store: new Ext.data.JsonStore({
					url: '/store/getListView.php',
					root: 'data',
					fields: [
						$listview_datafields
					]
				}),
				columns: [
					$listview_columns
				]
			}]
		}";
	return $item;
}




function buildSubProgField($content_id, $label, $name, $value, $meta_field_id, $category_id)
{
	global $db;
	$value = esc2($value);

	$res = getTotalInfo($category_id, $content_id);

	$start_brodymd = empty($res['start_brodymd']) ? '' : $res['start_brodymd'];
	$end_brodymd =  empty($res['end_brodymd']) ? '' : $res['end_brodymd'];

	$end_ymd = '';

	$codes = $db->queryRow("select * from content_code_info where content_id='$content_id'");

	$progcd = $codes['progcd'];
	$subprogcd = $codes['subprogcd'];

	if (empty($progcd) ||  empty($subprogcd)) {
		//	$value = '';
	}

	return	"{
			fieldLabel: '$label',
			xtype: 'fieldset',
			title: '방송일자 검색',
			defaults: {
				hideLabel: true,
				anchor : '-20',
				bodyStyle: 'margin-bottom: 10px;'
			},
			items: [{
				xtype: 'combo',
				id: '$meta_field_id',
				name: '$meta_field_id',
				typeAhead: true,
				editable: false,
				triggerAction: 'all',
				lazyRender: true,
				//emptyText: '부제를 선택해주세요.',
				value: '$value',
				store: new Ext.data.JsonStore({
					url: '/store/searchmeta_store.php',
					baseParams: {
						category_id : '$category_id'
					},
					root: 'data',
					fields: [
						'comb_cd','subprogcd', 'prognm', 'subprognm', 'progcd', 'brodymd', 'formbaseymd' ,'korname', 'medcd'
					]
				}),
				valueField: 'comb_cd',
				displayField: 'subprognm',
				listeners: {
					select: function(self, record){

						var form = self.ownerCt.ownerCt;
						form.get('4000292').setValue(record.get('prognm'));//프로그램
						form.get('4000289').setValue( self.stringToDate(record.get('brodymd') ).format('Y-m-d'));//방송예정일

						form.get('4000288').setValue( record.get('korname') );//담당PD
						form.get('k_subprogcd').setValue( record.get('subprogcd') );
						form.get('k_formbaseymd').setValue( record.get('formbaseymd') );
						form.get('k_progcd').setValue( record.get('progcd') );
						form.get('k_brodymd').setValue( record.get('brodymd') );
						form.get('k_medcd').setValue( record.get('medcd') );

					},
					render: function(self){
					}
				},
				stringToDate: function(sDate){
					var date,nYear,nMonth, nDay;

					sDate = sDate.trim();

					if( sDate.length == 8 )
					{
						nYear = parseInt(sDate.substr(0,4) , 10);
						nMonth = parseInt(sDate.substr(4,2), 10);
						nDay = parseInt(sDate.substr(6,2), 10);
					}

					date = new Date(nYear, nMonth -1, nDay);

					return date;
				}
			},{
				xype: 'compositefield',
				layout: 'hbox',
				defaults: {
				},
				items: [{
					flex: 1.3,
					xtype: 'datefield',
					format: 'Y-m-d',
					altFormats: 'Y-m-d|Y-m-d H:i:s|YmdHis|Ymd', format: 'Y-m-d',
					value: '$start_brodymd'
				},{
					width: 10,
					xtype:'displayfield',
					value: '~'
				},{
					flex: 1.3,
					xtype: 'datefield',
					format: 'Y-m-d',
					altFormats: 'Y-m-d|Y-m-d H:i:s|YmdHis|Ymd', format: 'Y-m-d',
					value: '$end_brodymd'
				},{
					width: 35,
					xtype: 'button',
					text: '검색',
					handler: function(e){

						var	combo = this.ownerCt.ownerCt.get(0);

						var sdate = this.ownerCt.get(0).getValue().format('Ymd');
						var edate = this.ownerCt.get(2).getValue().format('Ymd');
 						combo.getStore().reload({
							params: {
								brodstymd: sdate,
								brodendymd: edate
							}
						});
					}
				}]
			}]
		}";
}

function getCategoryTree($category_path, $root_category_id, $root_category_text, $ud_content_tab = 'program')
{

	if ($ud_content_tab == 'topic') {
		$fieldLabel = '토픽';
	} else {
		$fieldLabel = _text('MN00387');
	}

	$is_site_hrdk = is_site_hrdk();

	$result = "{
		xtype: 'treecombo',
		flex: 1,
		id: 'category',
		fieldLabel: '" . $fieldLabel . "',
		name: 'c_category_id',
		value: '" . $category_path . "',
		pathSeparator: ' > ',
        rootVisible: true,
        readOnly: true,
		loader: new Ext.tree.TreeLoader({
			url: '/store/get_categories_media.php',
			baseParams: {
				action: 'get-folders',
				path: '" . $category_path . "',
				ud_content_tab: '" . $ud_content_tab . "'
			},
			listeners: {
				load: function(self, node, response){
					var path = self.baseParams.path;
					
					if(!Ext.isEmpty(path) && path != '0'){
						path = path.split('/');
						self.baseParams.path = path.join('/');

						var caregory_id, id, n, i;
						caregory_id = path[path.length-1];
						
						//Find id to select. If path is long, many time run this part.
						for(i=1; i<path.length; i++) {
							id = path[path.length -i];
							n = node.findChild('id', id);
							if(!Ext.isEmpty(n)) {
								break;
							}
						}

						if(Ext.isEmpty(n) || node.id === caregory_id) {
							//For root category or find id
							node.select();
							Ext.getCmp('category').setValue(caregory_id);
						} else {
							//Expand and search again or select
							if(n && n.isExpandable()){
								n.expand(); //if not find id in this load, then expand(reload)
							}else{
								n.select();
								Ext.getCmp('category').setValue(n.id);
							}
						}
					}else{
						node.select();
						Ext.getCmp('category').setValue(node.id);
					}
				}
			}
		}),

		root: new Ext.tree.AsyncTreeNode({
			id: '" . $root_category_id . "',
			text: '$root_category_text',
			expanded: true
		}),
		listeners: {
			select: function(self, node) {
				Ext.getCmp('category').setValue(node.id);
			}
		}
	";

	if ($is_site_hrdk == 'Y') {
		$result .= "
			,listeners: {
				select: function(self, record) {
					var form = self.ownerCt;

					try {
						// 방영여부
						if (record.getPath('text').match('방영')) {
							if (form.find('id', '4778513')[0]) {
								form.find('id', '4778513')[0].setValue(1);
							}
							if (form.find('id', '4778464')[0]) {
								form.find('id', '4778464')[0].setValue(1);
							}
						} else {
							if (form.find('id', '4778513')[0]) {
								form.find('id', '4778513')[0].setValue(0);
							}
							if (form.find('id', '4778464')[0]) {
								form.find('id', '4778464')[0].setValue(0);
							}
						}

						// 묶음번호
						if (form.find('id', '4778500')[0]) {
							form.find('id', '4778500')[0].setValue(record.attributes.id);
						}
						if (form.find('id', '4778508')[0]) {
							form.find('id', '4778508')[0].setValue(record.attributes.id);
						}
					} catch (e) {
					}
				}
			}
		";
	}

	$result .= "
		}
	";

	return $result;
}
function getCustomCategoryTree($category_path, $root_category_id, $root_category_text, $ud_content_tab = 'program'){
	if ($ud_content_tab == 'topic') {
		$fieldLabel = '토픽';
	} else {
		$fieldLabel = _text('MN00387');
	}
	$is_site_hrdk = is_site_hrdk();
	$result = "{
		xtype:'c-tree-combo',
		fieldLabel: '" . $fieldLabel . "',
		readOnly:true,
		url: '/store/get_categories_media.php',
		flex: 1,
		params:{
			action: 'get-folders',
			path: '".$category_path."',
			ud_content_tab: '".$ud_content_tab."'
		},
		name: 'c_category_id',
		value: '".$category_path."',
		pathSeparator: ' > ',
		rootId:'".$root_category_id."',
		rootText:'".$root_category_text."',
		rootVisible: false,
		newLoaderEvent:function(treeCombo,loader,node,response){
			var path = loader.baseParams.path;
                
			if(!Ext.isEmpty(path) && path != '0'){
				path = path.split('/');
				loader.baseParams.path = path.join('/');

				var category_id, id, n, i;
				category_id = path[path.length-1];
				
				//Find id to select. If path is long, many time run this part.
				for(i=1; i<path.length; i++) {
					id = path[path.length -i];
					n = node.findChild('id', id);
					if(!Ext.isEmpty(n)) {
						break;
					}
				}

				if(Ext.isEmpty(n) || node.id === category_id) {
					//For root category or find id
					node.select();
					treeCombo.setValue(category_id);
				} else {
					//Expand and search again or select
					if(n && n.isExpandable()){
						n.expand(); //if not find id in this load, then expand(reload)
					}else{
						n.select();
						treeCombo.setValue(n.id);
					}
				}
			}else{
				node.select();
				treeCombo.setValue(node.id);
			}
		}
	";

	if ($is_site_hrdk == 'Y') {
		$result .= "
			,listeners: {
				select: function(self, record) {
					var form = self.ownerCt;

					try {
						// 방영여부
						if (record.getPath('text').match('방영')) {
							if (form.find('id', '4778513')[0]) {
								form.find('id', '4778513')[0].setValue(1);
							}
							if (form.find('id', '4778464')[0]) {
								form.find('id', '4778464')[0].setValue(1);
							}
						} else {
							if (form.find('id', '4778513')[0]) {
								form.find('id', '4778513')[0].setValue(0);
							}
							if (form.find('id', '4778464')[0]) {
								form.find('id', '4778464')[0].setValue(0);
							}
						}

						// 묶음번호
						if (form.find('id', '4778500')[0]) {
							form.find('id', '4778500')[0].setValue(record.attributes.id);
						}
						if (form.find('id', '4778508')[0]) {
							form.find('id', '4778508')[0].setValue(record.attributes.id);
						}
					} catch (e) {
					}
				}
			}
		";
	}

	$result .= "
		}
	";

	return $result;
}
function getCategoryTreeCustom($category_path, $root_category_id, $root_category_text, $ud_content_tab = 'program', $content_id)
{

	if ($ud_content_tab == 'topic') {
		$fieldLabel = '토픽';
	} else {
		$fieldLabel = _text('MN00387');
	}

	$is_site_hrdk = is_site_hrdk();

	$result = "{
		xtype: 'treecombo',
		flex: 1,
		id: 'category_" . $content_id . "',
		fieldLabel: '" . $fieldLabel . "',
		name: 'c_category_id',
		value: '" . $category_path . "',
		pathSeparator: ' > ',
        rootVisible: true,
        readOnly: true,
		loader: new Ext.tree.TreeLoader({
			url: '/store/get_categories_media.php',
			baseParams: {
				action: 'get-folders',
				path: '" . $category_path . "',
				ud_content_tab: '" . $ud_content_tab . "'
			},
			listeners: {
				load: function(self, node, response){
					var path = self.baseParams.path;
					
					if(!Ext.isEmpty(path) && path != '0'){
						path = path.split('/');
						self.baseParams.path = path.join('/');

						var caregory_id, id, n, i;
						caregory_id = path[path.length-1];
						
						//Find id to select. If path is long, many time run this part.
						for(i=1; i<path.length; i++) {
							id = path[path.length -i];
							n = node.findChild('id', id);
							if(!Ext.isEmpty(n)) {
								break;
							}
						}

						if(Ext.isEmpty(n) || node.id === caregory_id) {
							//For root category or find id
							node.select();
							Ext.getCmp('category_" . $content_id . "').setValue(caregory_id);
						} else {
							//Expand and search again or select
							if(n && n.isExpandable()){
								n.expand(); //if not find id in this load, then expand(reload)
							}else{
								n.select();
								Ext.getCmp('category_" . $content_id . "').setValue(n.id);
							}
						}
					}else{
						node.select();
						Ext.getCmp('category_" . $content_id . "').setValue(node.id);
					}
				}
			}
		}),

		root: new Ext.tree.AsyncTreeNode({
			id: '" . $root_category_id . "',
			text: '$root_category_text',
			expanded: true
		}),
		listeners: {
			select: function(self, node) {
				Ext.getCmp('category_" . $content_id . "').setValue(node.id);
			}
		}
	";

	if ($is_site_hrdk == 'Y') {
		$result .= "
			,listeners: {
				select: function(self, record) {
					var form = self.ownerCt;

					try {
						// 방영여부
						if (record.getPath('text').match('방영')) {
							if (form.find('id', '4778513')[0]) {
								form.find('id', '4778513')[0].setValue(1);
							}
							if (form.find('id', '4778464')[0]) {
								form.find('id', '4778464')[0].setValue(1);
							}
						} else {
							if (form.find('id', '4778513')[0]) {
								form.find('id', '4778513')[0].setValue(0);
							}
							if (form.find('id', '4778464')[0]) {
								form.find('id', '4778464')[0].setValue(0);
							}
						}

						// 묶음번호
						if (form.find('id', '4778500')[0]) {
							form.find('id', '4778500')[0].setValue(record.attributes.id);
						}
						if (form.find('id', '4778508')[0]) {
							form.find('id', '4778508')[0].setValue(record.attributes.id);
						}
					} catch (e) {
					}
				}
			}
		";
	}

	$result .= "
		}
	";

	return $result;
}

//사용안함. 아래 getCategorySearch 로 대체되었음. 2015.07.20
function getCategorySearchTree($content_id, $category_path, $root_category_id, $root_category_text, $ud_content_tab = 'program')
{

	$fieldLabel = '<font style=color:blue;font-size:14px;><b>프로그램 검색</b></font>';

	return "{
		xtype: 'treecombo',
		flex: 1,
		id: 'k_search_category',
		fieldLabel: '" . $fieldLabel . "',
		name: 'k_search_category',
		value: '" . $category_path . "',
		pathSeparator: ' > ',
		rootVisible: false,
		loader: new Ext.tree.TreeLoader({
			url: '/store/get_categories.php',
			baseParams: {
				action: 'get-folders',
				path: '" . $category_path . "',
				ud_content_tab: '" . $ud_content_tab . "'
			},
			listeners: {
				load: function(self, node, response){

					var path = self.baseParams.path;
					if(!Ext.isEmpty(path) && path != '0'){
						path = path.split('/');

						var id = path.shift();
						self.baseParams.path = path.join('/');

						var n = node.findChild('id', id);
						if(n && n.isExpandable()){
							n.expand();
						}else{
							n.select();
							Ext.getCmp('category').setValue(n.id);
						}
					}else{
						node.select();
						Ext.getCmp('category').setValue(node.id);
					}
				}
			}
		}),
		root: new Ext.tree.AsyncTreeNode({
			id: " . $root_category_id . ",
			text: '$root_category_text',
			expanded: true
		}),
		listeners: {
			select: function (self, record, index) {

				Ext.Ajax.request({
					url: '/store/last_content_info.php',
					params:{
						id: record.id
					},
					callback: function(options,success,response){
						if(success){
							var r = Ext.decode(response.responseText);

							alert('program id:' + record.id  + ':::' + 'new content_id:' + r.content_id + ':::' + 'old_content_id:' + " . $content_id . ");

							//프로그램의 마지막 콘텐츠 아이디가 있는경우 메타 복사
							if(" . $content_id . " != r.content_id && !Ext.isEmpty(r.content_id)){
								Ext.Msg.confirm('확인', '선택된 프로그램의 메타를 복사하시겠습니까?',
									function(btn){
										if(btn == 'yes'){

											Ext.Ajax.request({
												url: '/store/metadata/getMetadataByProgram.php',
												params: {
													content_id: " . $content_id . "
												},
												callback: function(opts, success, response){
													if (success)
													{
														try
														{
															var r = Ext.decode(response.responseText);

															if (r.success)
															{
																var data = new Ext.data.Record(r.data);
																var hrdk_form = Ext.getCmp('hrdk_form').getForm().loadRecord(data);
																var meta = new Ext.data.Record(r.meta_data);
																var detail_panel = Ext.getCmp('detail_panel');
																var count = detail_panel.items.length;

																for(var i=0; i < (count-2); i++) {
																	detail_panel.activate(i);
																	detail_panel.items.items[i].getForm().loadRecord(meta);
																}
																detail_panel.activate(0);
															}
														} catch (e) {
															//nothing
														}
													}
												}
											});
										}
									}
								);
							}
						}
						else{
							Ext.Msg.alert('등록','요청 실패',response.statusText);
						}
					}
				});
			}
		}
	}";
}

function getCategorySearch($content_id)
{

	$fieldLabel = '<font style=color:blue;font-size:14px;><b>프로그램 검색</b></font>';

	return "{
		xtype: 'combo',
		id: 'k_search_category',
		name: 'k_search_category',
		fieldLabel: '" . $fieldLabel . "',
		typeAhead:true,
        triggerAction: 'all',
		mode: 'local',
        selectOnFocus:true,
		store: new Ext.data.JsonStore({
			url: '/store/get_categories_program.php',
			autoLoad : true,
			baseParams: {
				path: '" . $category_path . "',
				mode: 'category_search'
			},
			//root: 'data',
			fields: [
				'id','text', 'prognm', 'subprognm', 'progcd', 'brodymd', 'formbaseymd' ,'korname', 'medcd'
			]
		}),

		valueField: 'id',
		displayField: 'text',
		listeners: {
			select: function (self, record, index) {
				Ext.Ajax.request({
					url: '/store/last_content_info.php',
					params:{
						id: record.id
					},
					callback: function(options,success,response){
						if(success){
							var _r = Ext.decode(response.responseText);

							//alert('program id:' + record.id  + ':::' + 'new content_id:' + _r.content_id + ':::' + 'old_content_id:' + " . $content_id . ");

							//프로그램의 마지막 콘텐츠 아이디가 있는경우 메타 복사
							if(" . $content_id . " != _r.content_id && !Ext.isEmpty(_r.content_id)){
								Ext.Msg.confirm('확인', '선택된 프로그램의 메타를 복사하시겠습니까?',
									function(btn){
										if(btn == 'yes'){
											Ext.Ajax.request({
												url: '/store/metadata/getMetadataByProgram.php',
												params: {
													content_id: _r.content_id
												},
												callback: function(opts, success, response){

													if (success) {
														try {
															var r = Ext.decode(response.responseText);

															if (r.success) {
																var form = Ext.getCmp('detail_panel').items.get(0);
																var data = new Ext.data.Record(r.data);
																var hrdk_form = Ext.getCmp('hrdk_panel').getForm().loadRecord(data);
																var meta = new Ext.data.Record(r.meta_data);
																var detail_panel = Ext.getCmp('detail_panel');

																var tree = Ext.getCmp('category').getTree();
																tree.expandAll();

																// 프로그램 검색 선택하면 제작프로그램 자동 선택
																(function() {
																	var category_path = '/' + tree.root.id + data.get('category_path');
																	category_path = category_path.split('/').join(tree.pathSeparator);
																	tree.expandPath(category_path, 'id', function(success, node) {
																		Ext.getCmp('category').setValue(node.id);
																	});
																}).defer(1000);


																// 4778482 편
																meta.set('4778482', (meta.get('4778482') * 1 + 1));
																meta.set('4778529', (meta.get('4778529') * 1 + 1));
																// 4778483 총편
																meta.set('4778483', (meta.get('4778483') * 1 + 1));
																meta.set('4778530', (meta.get('4778530') * 1 + 1));

																var count = detail_panel.items.length;
																for(var i=0; i < (count-2); i++) {
																	detail_panel.activate(i);

																	detail_panel.items.items[i].getForm().loadRecord(meta);
																}
																detail_panel.activate(0);
															}
														} catch (e) {
															//nothing

														}
													}
												}
											});
										}
									}
								);
							}
						} else {
							Ext.Msg.alert('등록','요청 실패',response.statusText);
						}
					}
				});
			}
		}
	}";
}

function buildCompositeFieldWithCheckbox($label, $name, $editable, $item)
{
	if ($editable == '1') {
		return $item;

		//		$result = "{".
		//		"xtype: 'compositefield',".
		//		"fieldLabel: '$label',".
		//		"name: '$name',".
		//
		//		"items: [{".
		//			"xtype: 'checkbox'".
		//		"}, ".
		//			$item.
		//		"]}";
	} else {
		return $item;
		$result = "{" .
			"xtype: 'compositefield'," .
			"disabled: true," .
			"fieldLabel: '$label'," .
			"name: '$name'," .

			"items: [{" .
			"xtype: 'checkbox'" .
			"}, " .
			$item .
			"]}";
	}

	return $result;
}

function buildTextField($name, $value)
{
	$name = addslashes($name);
	$value = addslashes($value);

	return "{" .
		"xtype: 'textfield'," .
		"name: '" . $name . "'," .
		"value: '" . $value . "'," .
		"flex: 1" .
		"}";
}

/**
 * 일반 콤보박스 목록을 만들기 위한 정보 조회
 *
 * @param [type] $field_id
 * @return void
 */
function getFieldDefaultValue($field_id)
{
	global $db;

	$data = $db->queryOne("select default_value from bc_usr_meta_field where usr_meta_field_id=" . $field_id);

	list($default, $value_list) = explode('(default)', $data);
	$value_list = explode(';', $value_list);

	foreach ($value_list as $value) {
		$result[] = "'$value'";
	}

	return join(',', $result);
}

/**
 * 일반 콤보박스 목록을 만들기 위한 정보 조회
 *
 * @param [type] $field_id
 * @return void
 */
function getFieldDefaultValueArray($field_id)
{
	global $db;

	$store = [];
	$data = $db->queryOne("select default_value from bc_usr_meta_field where usr_meta_field_id=" . $field_id);

	list($default, $value_list) = explode('(default)', $data);
	$value_list = explode(';', $value_list);

	foreach ($value_list as $value) {
		$store[] = [
			'key' => $value,
			'val' => $value,
		];
	}
	return [
		'store' => $store,
		'default' => $default
	];
}

/**
 * 코드 콤보박스 목록을 만들기 위한 정보 조회
 *
 * @param [type] $field_id
 * @param [type] $user_meta_field_code
 * @return void
 */
function getFieldCodeValue($field_id, $user_meta_field_code)
{
	global $db;
	$code = strtoupper($user_meta_field_code);
	//$datas = $db->queryAll("SELECT usr_code_key as key,usr_code_value as val FROM bc_usr_meta_code WHERE usr_meta_field_code='$code' ORDER BY show_order");
	if ($user_meta_field_code == 'REGIST_INSTT') {
		$user_meta_field_code = 'INSTT';
	}
	$datas = $db->queryAll("SELECT * FROM (
	SELECT ci.CODE_ITM_CODE AS key,
			ci.CODE_ITM_NM AS val,
			ci.USE_YN,
			ci.SORT_ORDR
			FROM DD_CODE_SET CS 
			JOIN DD_CODE_ITEM CI 
			ON (cs.ID=ci.CODE_SET_ID) 
			WHERE cs.DELETE_DT IS NULL 
			AND ci.DELETE_DT IS NULL 
			AND cs.CODE_SET_CODE='$user_meta_field_code') 
			ORDER BY SORT_ORDR");
	if (empty($datas)) {
		$datas = array();
	}
	return $datas;
	$result = array();
	foreach ($datas as $data) {
		$result[] = "{key:'" . $data['key'] . "',val:'" . $data['value'] . "'}";
	}

	return join(',', $result);
}

/**
 * 이름 중복 확인
 * 코드 콤보박스 목록을 만들기 위한 정보 조회 
 *
 * @param [type] $field_id
 * @param [type] $user_meta_field_code
 * @return void
 */
function getFieldCodeValue2($field_id, $user_meta_field_code)
{
	global $db;
	$code = strtoupper($user_meta_field_code);
	//$datas = $db->queryAll("SELECT usr_code_key as key,usr_code_value as val FROM bc_usr_meta_code WHERE usr_meta_field_code='$code' ORDER BY show_order");
	if ($user_meta_field_code == 'REGIST_INSTT') {
		$user_meta_field_code = 'INSTT';
	}
	$datas = $db->queryAll("SELECT * FROM (
	SELECT ci.CODE_ITM_CODE AS key,
			ci.CODE_ITM_NM AS val,
			ci.SORT_ORDR
			FROM DD_CODE_SET CS 
			JOIN DD_CODE_ITEM CI 
			ON (cs.ID=ci.CODE_SET_ID) 
			WHERE cs.DELETE_DT IS NULL 
			AND ci.DELETE_DT IS NULL 
			AND cs.CODE_SET_CODE='$code') 
			ORDER BY SORT_ORDR");
	if (empty($datas)) {
		$datas = array();
	}
	return $datas;
	$result = array();
	foreach ($datas as $data) {
		$result[] = "{key:'" . $data['key'] . "',val:'" . $data['value'] . "'}";
	}

	return join(',', $result);
}

function autoConvertByType($xtype, $value)
{
	if ($xtype == 'datefield') {
		$timestamp = strtotime($value);
		if (!$timestamp) {
			$timestamp = '';
		} else {
			$timestamp = date('YmdHis', $timestamp);
		}

		return $timestamp;
	} else {
		return addslashes($value);
	}
}


function getTotalInfo($category_id, $content_id)
{
	global $db;
	global $db_ms;

	if (is_null($category_id)) {
		$category_info = $db->queryRow("select ct.* from bc_content c, bc_category ct where ct.category_id=c.category_id and c.content_id='$content_id'");

		if ($category_info['parent_id'] == '0') {
			$category_id = $category_info['category_id'];
		} else {
			$category_id = $category_info['parent_id'];
		}
	}

	$whereinfo = $db->queryAll("select * from CATEGORY_PROGCD_MAPPING where category_id='$category_id'");
	$medcd = $whereinfo['medcd'];
	$formbaseymd = $whereinfo['formbaseymd'];
	$progcd = $whereinfo['progcd'];
	$prognm = $whereinfo['prognm'];

	$query_array = array();

	if (!empty($whereinfo)) {
		foreach ($whereinfo as $info) {
			$category_id = $info['category_id'];
			$medcd = $info['medcd'];
			$progparntcd = $info['progparntcd'];
			$progcd = $info['progcd'];
			$prognm = $info['prognm'];
			$formbaseymd = $info['formbaseymd'];
			$brodstymd = $info['brodstymd'];
			$brodendymd = $info['brodendymd'];

			$forquery = " (
			select
				tm2.*,
				tb1.korname
			from
				tbbf002 tf2,
				tbbma02 tm2,
				tbpae01 tb1
			where
				tm2.pdempno=tb1.empno
			and tm2.medcd=tf2.medcd
			and tf2.progcd=tm2.progcd
			and tf2.formbaseymd=tm2.formbaseymd
			and tf2.brodgu='001'
			and tf2.medcd='$medcd'
			and tf2.formbaseymd='$formbaseymd'
			and tf2.progcd='$progcd') ";


			array_push($query_array, $forquery);
		}

		$query = join(' union all ', $query_array);
		$query = "select * from ( $query  ) t";
		$query .= $where;
		$order = " order by t.brodymd desc";

		if (MDB2::isError($db_ms)) {
			return array(
				'data' => array(),
				'start_brodymd' => $start_brodymd,
				'end_brodymd' => $end_brodymd
			);
		}

		$res = $db_ms->queryAll($query . $order);

		if (!empty($res)) {
			$start_brodymd = $res[count($res) - 1]['brodymd'];
			$end_brodymd = $res[0]['brodymd'];
		} else {
			$start_brodymd = '';
			$end_brodymd = '';
		}
	} else {
		$res = array();
		$start_brodymd = '';
		$end_brodymd = '';
	}

	return array(
		'data' => $res,
		'start_brodymd' => $start_brodymd,
		'end_brodymd' => $end_brodymd
	);
}
