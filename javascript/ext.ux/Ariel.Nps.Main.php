<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lang.php');
session_start();

$user_id = $_SESSION['user']['user_id'];

$array_manual = array(
    //'manual'	=>	 'MAM QuickManual_20180511.pptx',
	'manual'	=>	 'Proxima Manual.pdf',
    'cjo_app'=>  'ContentHubApp(1.1.5).exe'
);
$array_manual_json = json_encode($array_manual);
$server_filename = LOCAL_LOWRES_ROOT.'/Install Files/';

/**
 * 사용자별로 패스워드 만료기간일 체크하여 패스워드 변경토록 하는 구문
 * 사용자 등록일시가 없을 경우 에러 발생함
 * 주석만 추가 2017.12.27 Alex
 */
$v_pwd_change_chk = $db->queryOne("
	SELECT	COALESCE((
		SELECT	CASE
					WHEN COALESCE(B.USE_YN, 'N') = 'N' THEN
						'N'
					WHEN A.C_DATE IS NULL THEN
						'N'
					WHEN TO_DATE(TO_CHAR(CAST(A.C_DATE  AS DOUBLE PRECISION) + 300000000, '99999999999999'), 'YYYYMMDDHH24MISS'  ) < CURRENT_DATE THEN
						'Y'
					ELSE
						'N'
				END AS CHK_YN
		FROM	(
				SELECT	COALESCE(PASSWORD_CHANGE_DATE, CREATED_DATE) AS C_DATE
				FROM	BC_MEMBER
				WHERE	USER_ID		= '".$user_id."' and del_yn='N'
				) A
				LEFT OUTER JOIN BC_SYS_CODE B ON(B.TYPE_ID = 1 AND B.CODE = 'QUARTER_PASS_CHANGE_YN')
		), 'N') AS CHK
	FROM		(
					SELECT	USER_ID
					FROM		BC_MEMBER
					WHERE	USER_ID = '".$user_id."' and del_yn='N'
				) DUAL
");

?>
Ext.ns('Ariel.Nps');

function showTaskDetail(workflow_id, content_id) {

	Ext.Ajax.request({
		url: '/javascript/ext.ux/viewInterfaceWorkFlow.php',
		params: {
			workflow_id: workflow_id,
			content_id: content_id
		},
		callback: function(options, success, response) {
			if (success) {
				try {
					Ext.decode(response.responseText);
				} catch (e) {
					Ext.Msg.alert(e.name, e.message);
				}
			} else {
				//>>Ext.Msg.alert('서버 오류', response.statusText);
				Ext.Msg.alert(_text('MN00022'), response.statusText);
			}
		}
	});
}

function renderTaskMonitorStatus(v) {
		switch(v){
			case 'complete':
				//>>v = '성 공';
				v = _text('MN00011');
			break;

			case 'down_queue':
			case 'watchFolder':
			case 'queue':
				//>>v = '대 기';
				v = _text('MN00039');
			break;

			case 'error':
				//>>v = '실 패';
				v = _text('MN00012');
			break;

			case 'processing':
			case 'progressing':
				//>>v = '처리중';
				v = _text('MN00262');
			break;

			case 'cancel':
				//>>v = '취소 대기중';
				v = _text('MN00004');
			break;

			case 'canceling':
				//>>v = '취소 중';
				v = _text('MN00004');
			break;

			case 'canceled':
				//>>v = '취소됨';
				v = _text('MN00004');
			break;

			case 'retry':
				//>>v = '재시작';
				v = _text('MN00006');
			break;

			case 'delete':
				//>>v = '삭제';
				v = _text('MN01106');
			break;
		}

		return v;
	}

Ariel.Nps.Main = Ext.extend(Ext.Panel, {

	initComponent: function(config){
		Ext.apply(this, config || {});
		var that = this;

		this.workStatusMapping = function(value){
			switch(value)
			{
				case 'queue':
					return '대기';
					return '<img src="/javascript/awesomeuploader/hourglass.png" width=16 height=16>';
				break;
				case 'progressing':
					return '진행중';
					return '<img src="/javascript/awesomeuploader/loading.gif" width=16 height=16>';
				break;
				case 'error':
					return '실패';
					return '<img src="/javascript/awesomeuploader/cross.png" width=16 height=16>';
				break;
				case 'cancel':
				case 'aborted':
					return '취소';
					return '<img src="/javascript/awesomeuploader/cancel.png" width=16 height=16>';
				break;
				case 'complete':
					return '완료';
					return '<img src="/javascript/awesomeuploader/tick.png" width=16 height=16>';
				break;
				case 'accept':
					return '승인';
				break;
				case 'refuse':
					return '반려';
				break;
			}
		};

		this.view_north = {
			region: 'north',
			height: 101,
			baseCls: 'bg_main_top_gif',
			html: 'main_top'
		}

		/*
			로그인시 기본 WEST 부분
		*/

		this.view_west_menu = {
			id: 'west-menu',
			region: 'west',
			layout: 'border',
			border: false,
			split: true,
			plain: true,
			cls: 'custom-nav-tab',
			width: 180,
			items: [
				 new Ariel.nav.Main_WestPanel()
			]
		};


		Ariel.Nps.Main.superclass.initComponent.call(this);
	}
});

//홈 - 공지사항
Ariel.Nps.Main.Notice = Ext.extend(Ext.Panel, {

    layout: 'hbox',
	layoutConfig: {
        align:'stretch'
    },
	frame:false,
	initComponent: function(config){
		Ext.apply(this, config || {});
		var that = this;

		function format_date(str_date){
			return str_date.substr(0, 4)+'-'+str_date.substr( 4, 2)+'-'+str_date.substr( 6, 2);
		}

		function showDetail(n_id){
			Ext.Ajax.request({
				//url: '/store/notice/main_notice_form.php',
				url: '/pages/menu/config/notice/win_notice.php',
				params: {
					action: 'view',
					type : 'view',
					notice_id : n_id,
					screen_width : window.innerWidth,
					screen_height : window.innerHeight
				},
				callback: function(self, success, response){
					try {
						var r = Ext.decode(response.responseText);
						r.show();
					}
					catch(e){
						//>>Ext.Msg.alert('오류', e);
						Ext.Msg.alert(_text('MN00022'), e);
					}
				}
			});
		}

		var store = new Ext.data.JsonStore({
			url:'/store/notice/get_list.php',
			root: 'data',
			totalProperty: 'total',
			baseParams : {
				type: 'list'
			},
			fields: [
				{name: 'notice_id'},
				{name : 'notice_title'},
				{name : 'notice_content'},
				{name : 'from_user_id'},
				{name : 'from_user_nm'},
				{name : 'notice_type'},
				{name : 'member_group_id'},
				{name: 'notice_type_text'},
				{name : 'depnm'},
				{name : 'mname'},
				{name : 'depcd'},
				{name:'created_date',type:'date',dateFormat:'YmdHis'},
				{name : 'is_new'},
				{name : 'total_notice_new'},
				{name : 'file_flag'},
				{name : 'read_flag'},
				{name : 'notice_start', type: 'date', dateFormat: 'YmdHis'},
				{name : 'notice_end', type: 'date', dateFormat: 'YmdHis'},
				{name : 'notice_date', convert: function(v, record){
						if(Ext.isEmpty(record.notice_start) && Ext.isEmpty(record.notice_end)){
							return;
						}else{
							return format_date(record.notice_start)+' ~ '+format_date(record.notice_end);
						}
					}
				},
				{name: 'title_flag_read', convert: function(v, record) {
					if(record.read_flag == 0){
						record.read_flag = '<span class="fa-stack " style="position:relative;height: 17px;margin-top: -3px;"><i class="fa fa-certificate fa-stack-1x" style="font-size:17px;color:#ff6600;"></i><strong class="fa fa-inverse fa-stack-1x fa-text" style="position:relative;font-size:10px;font-weight:bold;">N</strong></span>';
					}else{
						record.read_flag = '';
					}
					return record.read_flag;
				}},
				{name: 'flag_file', convert: function(v, record) {
					if(record.file_flag > 0){
						record.read_flag = '<span style="position:relative;top:1px;"><i class="fa fa-paperclip" style="font-size:13px;"></i></span>';
					}else{
						record.read_flag = '';
					}
					return record.read_flag;
				}}
			],
			listeners : {
				load : function(self){
					if(self.reader.jsonData.total > 0){
						var total_notice_count = self.reader.jsonData.new_total;
						if(total_notice_count >0){
							Ext.fly('total_new_notice').dom.innerHTML = '<font size=2px color="#FFFFFF" class="new_count_icon"><b>&nbsp'+total_notice_count+'&nbsp</b></font>';
						}else{
							Ext.fly('total_new_notice').dom.innerHTML = '';
						}
					}
				}
			}
		});

        var my_media_store = new Ext.data.JsonStore({
			url:'/store/get_my_media.php',
			root: 'data',
			totalProperty: 'total',
			baseParams : {
				type: 'list'
			},
			fields: [
				'task_id','media_id','content_id','path','complete_datetime','creation_datetime','status','title','filename','in_out','task_file_type'
			],
			listeners : {
				beforeload: function(self, opts){
                    opts.params = opts.params || {};
        
                    Ext.apply(opts.params, {
                        start_date: Ext.getCmp('my_media_start_date').getValue().format('Ymd000000'),
                        end_date: Ext.getCmp('my_media_end_date').getValue().format('Ymd240000')
        
                    });
                }
			}
		});

		this.items = [{
			xtype: 'grid',
            flex: 1,
			cls: 'proxima_customize',
			stripeRows: true,
			title : '<span class="user_span"><span class="icon_title"><i class="fa fa-file-text-o"></i></span><span class="main_title_header">'+_text('MN00144')+'</span></span>',
			id: 'main_notice_grid',
			loadMask: true,
			store: store,
			border: false,
			bodyStyle: 'border-left: 1px solid #d0d0d0',
			viewConfig:{
				emptyText : _text('MSG00148')
				//forceFit: true
			},
			listeners: {
				render: function(self){
					self.store.load();
				},
				rowclick: function(self, index, e){
					//var sel = self.getSelectionModel().getSelected();
				},
				rowdblclick: function(self, index, e){
					var sel = self.getSelectionModel().getSelected();
					var n_id = sel.get('notice_id');
					var n_type = sel.get('notice_type');
					showDetail(n_id);

				},
				rowcontextmenu: function(self, rowIdx, e){
					e.stopEvent();
					var sm = self.getSelectionModel();
					sm.selectRow(rowIdx);
					var sel = sm.getSelected();
					var n_id = sel.get('notice_id');

					var menu_f = new Ext.menu.Menu({
						items: [{
							text: _text('MN02198'),//상세보기 Detail
							icon: '/led-icons/application_view_detail.png',
							handler: function(btn, e){
								showDetail(n_id);
							}
						}],
						listeners: {
							render: function(self){
							}
						}
					});

					menu_f.showAt(e.getXY());
				},
				afterrender : function(self){

				}
            },
            tbar:[{
                //>>text: '새로고침',
                cls: 'proxima_button_customize',
                width: 30,
                text: '<span style="position:relative;" title="'+_text('MN00139')+'"><i class="fa fa-refresh" style="font-size:13px;color:white;"></i></span>',
                handler: function(){
                    store.reload();
                },
                scope: this
            }],
			colModel: new Ext.grid.ColumnModel({
				defaultSortable: false,
				columns: [
					new Ext.grid.RowNumberer(),
					//{header : '<span style="position:relative;top:1px;"><i class="fa fa-paperclip" style="font-size:13px;"></i></span>', dataIndex : 'flag_file', width : 15, align:'center'},//첨부파일 attached file
					//{header : '<span style="position:relative;top:1px;"><img src="/led-icons/new_1.png"/></span>', dataIndex : 'title_flag_read',width: 20, align:'center'},
					{header : '<span class="fa-stack " style="position:relative;height: 17px;margin-top: -9px;"><i class="fa fa-certificate fa-stack-1x" style="font-size:17px;color:#ff6600;"></i><strong class="fa fa-inverse fa-stack-1x fa-text" style="position:relative;font-size:10px;font-weight:bold;">N</strong></span>', dataIndex : 'title_flag_read',width: 30, align:'center', hidden: true},
					{header : _text('MN00249'), dataIndex : 'notice_title', width : 250},//title
					//{header: _text('MN00249'), dataIndex: 'notice_title',renderer: function(value, metaData, record, rowIndex, colIndex, store){
							//if(Ext.getCmp('main_notice_grid').getStore().data.length > 0){
								//var total_notice_count = Ext.getCmp('main_notice_grid').getStore().data.items[0].data.total_notice_new;
								//if(total_notice_count >0){
									//Ext.fly('total_new_notice').dom.innerHTML = '<font size=2px color="#FFFFFF" class="new_count_icon"><b>&nbsp'+total_notice_count+'&nbsp</b></font>';
								//}
							//}

							//if(record.get('is_new') == 1){
								//return record.get('notice_title') + '   <span><img src=\"/led-icons/new.png\"/></span>';
							//}else{
								//return record.get('notice_title') ;
							//}
						//}
					//},//제목
					//{header: _text('MN00222'), dataIndex: 'notice_type_text',width: 60, align:'center'},//유형
					{header: _text('MN02207'), dataIndex: 'notice_date',width: 160, align:'center'},//공지기간
					{header: _text('MN00102'), width : 130, dataIndex: 'created_date',  renderer: Ext.util.Format.dateRenderer('Y-m-d'), align:'center'},//등록일자
					{header: _text('MN02206'), dataIndex: 'from_user_nm',width : 60}//등록자
				]
			}),
			sm: new Ext.grid.RowSelectionModel({
				singleSelect:true
			}),
			bbar: {
				xtype: 'paging',
				pageSize: 20,
				displayInfo: true,
				store: store
			}
		},{
            xtype: 'grid',
            flex: 1,
            cls: 'proxima_customize',
            stripeRows: true,
            //내가 만든 미디어
            title : '<span class="user_span"><span class="icon_title"><i class="fa fa-download"></i></span><span class="main_title_header">'+_text('MN01997')+'</span></span>',
            id: 'main_my_media_grid',
            loadMask: true,
            store: my_media_store,
            border: false,
            bodyStyle: 'border-left: 1px solid #d0d0d0',
            viewConfig:{
				emptyText : _text('MSG00148')
                //forceFit: true
            },
            listeners: {
                render: function(self){
                    self.store.load();
                }
            },
            tbar: [{
                id: 'my_media_start_date',
                xtype: 'datefield',
                format: 'Y-m-d',
                altFormats: 'Y-m-d|Y-m-d H:i:s|YmdHis|Ymd', format: 'Y-m-d',
                width : 90,
                value: new Date().add(Date.DAY, -7).format('Y-m-d')
            },' ~ ', {
                id: 'my_media_end_date',
                xtype: 'datefield',
                format: 'Y-m-d',
                altFormats: 'Y-m-d|Y-m-d H:i:s|YmdHis|Ymd', format: 'Y-m-d',
                width : 90,
                value: new Date()
            },{
                //>>text: '새로고침',
                cls: 'proxima_button_customize',
                width: 30,
                text: '<span style="position:relative;" title="'+_text('MN00139')+'"><i class="fa fa-refresh" style="font-size:13px;color:white;"></i></span>',
                handler: function(){
                    my_media_store.reload();
                },
                scope: this
            },{
                cls: 'proxima_button_customize',
                width: 30,
                text: '<span style="position:relative;" title="'+_text('MN02209')+'"><i class="fa fa-download" style="font-size:13px;color:white;"></i></span>',
                handler: function(){
                    var records = Ext.getCmp('main_my_media_grid').getSelectionModel().getSelections();
                    if ( !checkSelected( records ) ) return;

                    var mediaIds = [];
                    for(var i=0; i<records.length; i++){
                        var mediaId = records[i].get('media_id');
                        mediaIds.push(mediaId);
                    }

                    downloadPFRMedia( mediaIds );
                },
                scope: this
            }],
            colModel: new Ext.grid.ColumnModel({
                defaultSortable: false,
                columns: [
                    {header: _text('MN02438'), dataIndex: 'title',width: 150, align:'center'},//'제목'
                    {header: _text('MN00237'), dataIndex: 'status',width: 70, align:'center', renderer: renderTaskMonitorStatus},//'작업상태'
                    {header: _text('MN01025'), dataIndex: 'complete_datetime',width: 130, align:'center'},//'작업종료일'
                    {header: _text('MN01023'), dataIndex: 'creation_datetime',width: 130, align:'center'},//'작업생성일'
                    {header: 'content_id', dataIndex: 'content_id', hidden: true},
                    {header: 'media_id', dataIndex: 'media_id', hidden: true}
                ]
            }),
            sm: new Ext.grid.RowSelectionModel({
                singleSelect:true
            }),
            bbar: {
                xtype: 'paging',
                pageSize: 20,
                displayInfo: true,
                store: my_media_store
            }
        }];

		Ariel.Nps.Main.Notice.superclass.initComponent.call(this);

		var v_pwd_change_chk = '<?=$v_pwd_change_chk?>';

		if(v_pwd_change_chk == 'Y'){
			var change_password = new Ext.Window({
				id: 'popup_pwd_change_win',
				title: '비밀번호 변경',
				height: 230,
				width: 280,
				modal: true,
				layout: 'fit',
				resizable: false,
				closable: false,
				buttonAlign: 'center',
				items: [{
					xtype: 'form',
					frame: true,
					labelAlign: 'left',
					padding: 5,
					items: [
					{
						xtype: 'textfield',
						inputType: 'password',
						id: 'user_password',
						name: 'user_password',
						allowBlank: false,
						msgTarget: 'under',
						fieldLabel: '비밀번호'
					},{
						xtype: 'textfield',
						inputType: 'password',
						id: 'user_password_valid',
						name: 'user_password_valid',
						allowBlank: false,
						msgTarget: 'under',
						fieldLabel: '비밀번호확인'
					},{
						xtype: 'displayfield',
						heigth : 200,
						width : 240,
						hideLabel: true,
						readOnly: true,
						html: '<br><b>비밀번호 변경이 필요합니다.</b><br><br>* 비밀번호 변경은 분기 1회.<br>* 비밀번호는 영문, 숫자, 특수 문자를 조합.<br>* 비밀번호는 8글자 이상으로 생성.'
					}
					]
				}],
				buttons: [{
					'text': '변경',
					scale: 'medium',
					handler: function(btnId, text, opts){
						var value1 = Ext.getCmp('user_password').getValue();
						var value2 = Ext.getCmp('user_password_valid').getValue();

						function fn_pwCheck(p) {
							chk1 = /^[a-z\d\{\}\[\]\/?.,;:|\)*~`!^\-_+&lt;&gt;@\#$%&amp;\\\=\(\'\"]{8,12}$/i;  //영문자 숫자 특문자 이외의 문자가 있는지 확인
							chk2 = /[a-z]/i;  //적어도 한개의 영문자 확인
							chk3 = /\d/;  //적어도 한개의 숫자 확인
							chk4 = /[\{\}\[\]\/?.,;:|\)*~`!^\-_+&lt;&gt;@\#$%&amp;\\\=\(\'\"]/i; //적어도 한개의 특문자 확인

							return chk1.test(p) && chk2.test(p) && chk3.test(p) && chk4.test(p);
						}

						if(Ext.isEmpty(value1)){
							Ext.Msg.alert('확인','비밀번호를 입력해 주세요.');
						}else if(Ext.isEmpty(value2)){
							Ext.Msg.alert('확인','비밀번호 확인을 입력해 주세요.');
						}else if(value1 != value2){
							Ext.Msg.alert('확인','비밀번호 확인을 다시 입력해 주세요.');
						}else if(value1.length < 9){
							Ext.Msg.alert('확인','비밀번호를 9자리 이상으로 입력해 주세요.');
						}else if(!fn_pwCheck(value1)){
							Ext.Msg.alert('확인','비밀번호는 영문, 숫자, 특수문자를 각각 1개 이상 포함하여야 합니다.');
						}else{
							Ext.Ajax.request({
								url: '/store/change_password.php',
								params: {
									user_id: '<?=$user_id?>',
									user_password: SHA512(value1)
								},
								callback: function(options, success, response){
									if (success)
									{
										try
										{
											var r = Ext.decode(response.responseText);
											if (r.success)
											{
												Ext.Msg.show({
													title: '확인',
													msg: r.msg,
													buttons: Ext.Msg.OK
												});
												change_password.destroy();
											}
											else
											{
											}
										}
										catch (e)
										{
											Ext.Msg.alert(e['name'], e['message']);
										}
									}
									else
									{
										Ext.Msg.alert('확인', response.statusText);
									}
								}
							});
						}
					}
				}]
			}).show();
		}
	}
});


//Home manual 매뉴얼
Ariel.nav.Main_Manual = Ext.extend(Ext.Panel, {
    id: 'menu-manual',
	layout : 'fit',
	border:false,
    defaults : {
        //split: true
    },

    initComponent: function(config) {
		Ext.apply(this, config || {});
		var that = this;
		this.items= [{
			xtype: 'treepanel',
            //>>title: 'Manual', 매뉴얼
			//title: '<?=_text('MN02209')?>',
			title : '<span class="user_span2"><span class="icon_title"><i class="fa fa-download"></i></span><span class="user_title">'+_text('MN02209')+'</span></span>',
			width: 178,
			id : 'manuals',
			border:false,
			bodyStyle:{"background-color":"#eaeaea","overflow":"hidden"},
			boxMinWidth: 178,
			split: true,
			//autoScroll: true,
			rootVisible :false,
			cls:'tree_menu',
			lines:false,
			listeners: {
				afterrender: function(self) {
					var node = self.getRootNode().findChild('id', '<?=$_GET['select']?>');
					if (node) {
						node.fireEvent('click', node);
					}
				},
				click: function(node, e){
					//var url = "/files/manual/manual_cms.ppt";
					Ext.Msg.show({
						title : _text('MN00024'),
						msg : _text('MSG02030'),
						buttons : Ext.Msg.OKCANCEL,
						fn : function(btn){
							if( btn == 'ok' ){
                                //2017-12-28 이승수
                                //CJO에서 운영인 45번 서버가 아닌 구서버 42번을 바라봐서 다운로드 하는 방식이라 get_list.php의 send_attachment함수가 동작안함
                                //window.open으로 바로 링크를 열도록 변경
								//var url = "/store/notice/get_list.php?type=manual&manual_type="+node.attributes.title;
								//var w = window.open(url);

                                var manual_json = <?=$array_manual_json?>;
                                var manual_type = node.attributes.title;
                                window.open('<?=$server_filename?>'+manual_json[manual_type]);

							}
						}
					});
				}
			},
			root: {
				//>> text: 'manual',
				text: '<?=_text('MN02209')?>',
				expanded: false,
				children: [
				/*
				{
					text: '<span class="fa-stack " style="position:relative;height: 17px;margin-top: -9px;"><i class="fa fa-certificate fa-stack-1x" style="font-size:17px;color:#ff6600;"></i><strong class="fa fa-inverse fa-stack-1x fa-text" style="position:relative;font-size:10px;font-weight:bold;">N</strong></span>&nbsp;방송콘텐츠 허브 앱 설치 - 18.04.23',
                    title: 'cjo_app',
					leaf: true
				},
				*/
				{
					text: '<span style="position:relative;top:3px;"><i class="fa fa-file-powerpoint-o" style="font-size:15px;"></i></span>&nbsp;&nbsp;'+ 'Proxima 매뉴얼',					
					title: 'manual',
					leaf: true
				}]
			},
		}]

		Ariel.nav.Main_Manual.superclass.initComponent.call(this);
	}
});


//Home system manager
Ariel.nav.Main_SystemManager = Ext.extend(Ext.Panel, {
    id: 'menu-sysmanager',
	layout : 'fit',
	border:false,
    defaults : {
        //split: true
    },

    initComponent: function(config) {
		Ext.apply(this, config || {});
		var that = this;
		this.items= [{
			xtype : 'form',
			padding: '0px 10px 10px 10px',
			id: 'sysmanagers',
			bodyStyle:{"background-color":"#eaeaea"},
			title : '<span class="user_span2"><span class="icon_title"><i class="fa fa-phone"></i></span><span class="user_title">'+_text('MN02556')+'</span></span>',
			autoScroll: true,
			border:false,
			frame : false,
			labelWidth: 15,
			defaults: {
				xtype: 'displayfield',
				labelSeparator: '',
				labelAlign:'top',
				labelStyle: 'text-align:center;display: inline-block;line-height: 1;'
			},
			cls: 'main_user',

			items : [{
				fieldLabel : '<i class="fa fa-chevron-right"></i>',
				value: '제머나이소프트 09:00 ~ 18:00</br> 02-857-1101'
			}]
		}]

		Ariel.nav.Main_SystemManager.superclass.initComponent.call(this);
	}
});