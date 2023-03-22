<?php
require_once($_SERVER['DOCUMENT_ROOT'] . "/lib/config.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/lib/functions.php");
session_start();
$flag = $_REQUEST['flag'];
$direct =  $_REQUEST['direct'];

$paramUserId = '';
//파일 매핑 목록과 계정 연계
if ($flag == 'fcp' || $flag == 'fcpx') {
	$direct = 'true';
	//이상하게 들어와서 파싱해야함
    $paramUserId = $_REQUEST['user_id'];

	$paramUserId = str_replace('?user_id=', '', $paramUserId);
	$remote_addr = $_SERVER['REMOTE_ADDR'];

	// if( $flag == 'fcp' && empty($paramUserId) ){
	// 	//하루만
	// 	$dt = \Carbon\Carbon::now();
	// 	$beforeDate = $dt->subDays(1);  
	// 	$file = \Api\Models\MapFile::where('remote_ip',  $remote_addr )
	// 	->whereDate('created_at', '>', $beforeDate )
	// 	->orderBy('id', 'desc')->first();
	// 	if( !empty($file) ){
	// 		$paramUserId =  $file->user_id;
	// 	}
	// }

	// if($paramUserId){        
	//     $container = app()->getContainer();
	//     $userService = new \Api\Services\UserService($container);
	//     $user = $userService->findByUserId(trim($paramUserId));
	// }

    $banUserIds = config('plugin')['ban_user'];

	if( !empty($paramUserId) && in_array($paramUserId, $banUserIds)){
	    echo "<script type=\"text/javascript\">window.location=\"/index.php?flag=" . $flag . "\"</script>";
	    exit;
    }

} else {
	$paramUserId = $_REQUEST['user_id'];
}

$sessionUserId = $_SESSION['user']['user_id'];

if ( !empty($flag) ||  $direct == 'true') {
    $container = app()->getContainer();
    $userService = new \Api\Services\UserService($container);
    if( empty($paramUserId) && !empty($sessionUserId) ){
        $paramUserId = $sessionUserId;
    }
    $user = $userService->findByUserId(trim($paramUserId));
	//$user = $db->queryRow("select * from bc_member where del_yn='N' and user_id='" . $db->escape($paramUserId) . "'");
	if (!empty($user)) {
		$userId = $user->user_id;
		$groups = getGroups($userId);
		$_REQUEST['lang'] = $user->lang;
		$_SESSION['user'] = array(
			'user_id' => trim($userId),
			'is_admin' => trim($user->is_admin),
			'groups' => $groups,
			'lang' => $user->lang
		);
	} else {
        echo "<script type=\"text/javascript\">window.location=\"/index.php?flag=" . $flag . "\"</script>";
	    exit;
	}
}else if (!empty($sessionUserId) && $sessionUserId != 'temp') {
	//세션사용
    $userId = $_SESSION['user']['user_id'];
}

//dd($userId);

if (empty($userId)) {
	echo "<script type=\"text/javascript\">window.location=\"/index.php?flag=" . $flag . "\"</script>";
	exit;
}

//For NLE Exporter, default REQUEST set
$lang = !empty($_REQUEST['lang']) ? $_REQUEST['lang'] : $_SESSION['user']['lang'];
if(empty($lang)){
    $lang = 'ko';
}
require_once($_SERVER['DOCUMENT_ROOT'] . '/store/metadata/buildMediaListTab.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/store/metadata/buildSystemMeta.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/lang.php');

// 저장 전 작업에 대한 로직 문자열을 얻어온다.
$beforeSaveJsLogic = '';
if (defined('CUSTOM_ROOT') && class_exists('\ProximaCustom\core\MetadataManager')) {
	$beforeSaveJsLogic = \ProximaCustom\core\MetadataManager::getBeforeSaveJsLogic();
}
?>
<html>

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=9" />
	<meta name="viewport" content="initial-scale=1, maximum-scale=1,user-scalable=no" />
	<title>등록 페이지</title>
	<link rel="SHORTCUT ICON" href="/Ariel.ico" />
	<link rel="stylesheet" type="text/css" href="/lib/extjs/resources/css/ext-all.css" />
	<link rel="stylesheet" type="text/css" href="/css/custom-xtheme-access.css" />
	<link rel="stylesheet" type="text/css" href="/lib/extjs/examples/ux/css/ProgressColumn.css" />
	<link rel="stylesheet" type="text/css" href="/javascript/timepicker/Ext.ux.Spinner/resources/css/Spinner.css" />
	<link rel="stylesheet" type="text/css" href="/javascript/timepicker/Ext.ux.TimePicker/resources/css/TimePicker.css" />

	<link rel="stylesheet" type="text/css" href="/css/font-awesome.min.css">

	<link rel="stylesheet" type="text/css" href="/css/style.css" />
	<link rel="stylesheet" type="text/css" href="/css/login.css" />

	<style type="text/css">
		.readonly-class {
			background-color: #DADADA;
			background-image: none;
			border-color: #B5B8C8;
		}

		/* 검은배경일때 그리드 마우스 오버시 잘 보이도록 */
		.x-grid3-row-over {
			background-color: #6081A1 !important;
			background-image: none !important;
		}
	</style>

</head>

<body>
	<script type="text/javascript" src="/lib/extjs/adapter/ext/ext-base.js"></script>
	<script type="text/javascript" src="/lib/extjs/ext-all.js"></script>
	<script type="text/javascript" src="/javascript/lang.php"></script>
	<script type="text/javascript" src="/javascript/functions.js"></script>
	<script type="text/javascript" src="/javascript/ext.ux/dd.js"></script>
	<script type="text/javascript" src="/javascript/ext.ux/Ext.ux.grid.PageSizer.js"></script>
	<script type="text/javascript" src="/lib/extjs/src/locale/ext-lang-<?= $lang ?>.js"></script>

	<!--Custom Menu Pages -->
	<?php

	if (defined('CUSTOM_ROOT') && class_exists('\ProximaCustom\core\ScriptManager')) {
		$scripts = \ProximaCustom\core\ScriptManager::getCustomScripts(false, ['Ariel.Nps.DashBoard', 'Ariel.Das.ArcManage', 'Ariel.task.Monitor']);
		foreach ($scripts as $script) {
			echo $script;
		}
	}

	?>
	<script type="text/javascript">
		dt = new Date();
		var current_focus = null;
		var advanceSearchWin = null;
		// RootPath 변수
		var root_path = null;
		// 	Program별 Path 담는 변수
		var prog_path = null;

		Ext.chart.Chart.CHART_URL = '/lib/extjs/resources/charts.swf';
		Ext.BLANK_IMAGE_URL = '/lib/extjs/resources/images/default/s.gif';

		Ext.onReady(function() {

			Ext.QuickTips.init();

			var view = new Ext.Viewport({
				layout: 'border',
				items: [{
					region: 'center',
					xtype: 'panel',
					layout: 'border',
					style: {},
					id: 'regist_form_tab',
					//autoScroll: true,
					//defaults:{autoHeight: true},
					frame: true,
					tbar: [{
						xtype: 'displayfield',
						width: 15
					}, {
						xtype: 'radiogroup',
						width: 380,
						columns: [100, 100, 100, 200, 200, 200],
						name: 'ud_content_tab',
						items: [{
								boxLabel: '뉴스',
								name: 'ud_content_tab',
								inputValue: 'news',
								checked: true
							},
							{
								boxLabel: '제작',
								name: 'ud_content_tab',
								inputValue: 'product'
							},
							{
								boxLabel: '디지털자료',
								name: 'ud_content_tab',
								inputValue: 'telecine'
							}
							// ,
							// {boxLabel: '텔레시네', name: 'ud_content_tab', inputValue:'telecine' },
							// {boxLabel: 'e영상역사관', name: 'ud_content_tab', inputValue:'ehistory' },
							// {boxLabel: '부처영상', name: 'ud_content_tab', inputValue:'portal' },
							// {boxLabel: '홈페이지', name: 'ud_content_tab', inputValue:'homepage' }
						],
						listeners: {
							change: function(self, checked) {
								var checkedVal = checked.getRawValue();
								if (self.beforeValue == checkedVal) return;

								var tab = self.ownerCt.ownerCt;
								tab.get(0).loadFormMetaData(tab.get(0));
								self.beforeValue = checkedVal;

								// Ext.Msg.show({
								// 	title: '알림',
								// 	icon: Ext.Msg.INFO,
								// 	msg: '입력하신 정보가 초기화 되며, 선택하신 유형으로 정보가 갱신됩니다.<br />진행하시겠습니까?',
								// 	buttons: Ext.Msg.OKCANCEL,
								// 	fn: function(btnID, text, opt) {
								// 		if(btnID == 'ok') {
								// 			var tab = self.ownerCt.ownerCt;
								// 			tab.get(0).loadFormMetaData(tab.get(0));
								//             self.beforeValue = checkedVal;
								// 		} else {
								// 			self.setValue(self.beforeValue);
								// 		}
								// 	}
								// });
							}
						}
					}, '', {
						xtype: 'displayfield',
						width: 7
					}, _text('MN00276'), {
						xtype: 'combo',
						width: 100,
						id: 'content_type',
						editable: false,
						displayField: 'ud_content_title',
						valueField: 'ud_content_id',
						typeAhead: true,
						beforeValue: '',
						triggerAction: 'all',
						lazyRender: true,
						store: new Ext.data.JsonStore({
							url: '/interface/mam_ingest/get_meta_json.php',
							root: 'data',
							baseParams: {
								kind: 'ud_content',
								//bs_content_id: 506,
								flag: '<?= $flag ?>'
							},
							fields: [
								'ud_content_title',
								'ud_content_id',
								'allowed_extension'
							]
						}),
						listeners: {
							afterrender: function(self) {
								self.getStore().load({
									callback: function(r, o, s) {
										if (s && r[0]) {
											//로드된 첫번째 항목 설정
											self.setValue(r[0].get('ud_content_id'));
											self.beforeValue = r[0].get('ud_content_id');
											//원본 삭제 알림
											if(r[0].get('ud_content_id') == '1') {
												var originalDeleteText = self.ownerCt.get(7);
												originalDeleteText.setVisible(true);
											}

											var tab = self.ownerCt.ownerCt;
											tab.get(0).loadFormMetaData(tab.get(0));
										}
									}
								});
							},
							select: function(self, record, index) {
								var selVal = record.get('ud_content_id');
								//if(self.beforeValue == selVal) return;

								// originalDeleteText : 원본 콘텐츠는 2주 후 삭제됩니다.
								var originalDeleteText = self.ownerCt.get(7);
								if(selVal == '1') {
									originalDeleteText.setVisible(true);
								} else {
									originalDeleteText.setVisible(false);
								}
								
								var tab = self.ownerCt.ownerCt;

								//tab.get(0).setCustomAction();
								tab.get('bbar_form').setCustomAction(tab.get(0).getCondition());

								tab.get(0).getCustomForm(0).setValues({
									k_ud_content_id: selVal,
									cntnts_ty: selVal
								});
							}
						}
					}, {
						xtype: 'displayfield',
						width: 7
					},{
						xtype: 'label',
						text: '원본 콘텐츠는 2주 후 삭제됩니다.',
						hidden: true,
						width: 30,
						style: {
							color: "red",
						},
					}, {
						xtype: 'displayfield',
						width: 7
					}, {
						text: 'submit_meta()',
						hidden: true,
						handler: function(b, e) {
							var sm = submit_meta();
							console.log(sm);
						}
					}, {
						text: 'isValid()',
						hidden: true,
						handler: function(b, e) {
							var sm = isValid();
							console.log(sm);
						}
					}, '->', {
						xtype: 'displayfield',
						width: 100,
						value: '[<?= $userId ?>]'
					}],
					items: [{
                        xtype: 'tabpanel',
                        region: 'center',
						id: 'regist_form_tabpanel',
						activeTab: 0,
						frame: true,
						isFirst: true,
						defaults: {
							labelSeparator: '',
							anchor: '95%'
						},
						listeners: {
							afterrender: function(self) {

							}
						},
						items: [],
						loadFormMetaData: function(self, params) {
							var myMask = new Ext.LoadMask(Ext.getBody(), {
								msg: "로딩중입니다..."
							});
							myMask.show();

							var condition = self.getCondition();

							params = params || {};
							params.ud_content_tab = condition.ud_content_tab;
							params.ud_content_id = condition.ud_content_id;
							params.user_id = '<?= $userId ?>';
                            params.lang = '<?= $lang ?>';
                            params.flag = '<?= $flag ?>';

							Ext.Ajax.request({
								url: 'get_metadata.php',
								params: params,
								callback: function(opts, success, response) {
									myMask.hide();
									if (success) {
										try {
											var r = Ext.decode(response.responseText);
											self.removeAll();
											self.add(r);

											//콘텐츠 유형 제작 유형
                                            var condition = self.getCondition();
                                       
											self.ownerCt.get('bbar_form').setCustomAction(condition);
											self.doLayout();
											self.activate(0);
										} catch (e) {
											Ext.Msg.alert(e['name'], e['message']);
										}
									} else {
										Ext.Msg.alert(_text('MN00022'), opts.url + '<br />' + response.statusText + '(' + response.status + ')');
									}
								}
							});
						},
						put_meta_afterLoadFormMetaData: function(self, params, input_meta_string) {
							var tbar = self.ownerCt.getTopToolbar();
							var ud_content_tab = tbar.items.get(1).getValue().getRawValue();
							var ud_content_id = tbar.items.get(5).getValue();
							params = params || {};
							params.ud_content_tab = ud_content_tab;
							params.ud_content_id = ud_content_id;
							params.user_id = '<?= $userId ?>';
							params.lang = '<?= $lang ?>';

							Ext.Ajax.request({
								url: 'get_metadata.php',
								params: params,
								callback: function(opts, success, response) {
									if (success) {
										try {
											var r = Ext.decode(response.responseText);
											self.removeAll();
											self.add(r);
											self.doLayout();
											self.activate(0);

											put_meta2(input_meta_string);
										} catch (e) {
											Ext.Msg.alert(e['name'], e['message']);
										}
									} else {
										Ext.Msg.alert(_text('MN00022'), opts.url + '<br />' + response.statusText + '(' + response.status + ')');
									}
								}
							});
						},
						getCustomForm: function(num) {
							return this.get(num).getForm();
						},
						getCondition: function() {
							//메타데이터 뷰 조건정보
							var self = this;
							var tbar = self.ownerCt.getTopToolbar();
							var ud_content_tab = tbar.items.get(1).getValue().getRawValue();
							var ud_content_id = tbar.items.get(5).getValue();
							return {
								'ud_content_tab': ud_content_tab,
								'ud_content_id': ud_content_id,
							}
						}
					},{
					region: 'north',
					height: 80,
					xtype: 'form',
                    itemId: 'bbar_form',
                    padding: '5 5 5 5',
                    border: true,
					items: [{
						hideLabel: true,
						//height: 130,
						name: 'k_custom_field',
						xtype: 'compositefield',
						//layout: 'anchor',
						defaults: {
							height: 80,
							//flex: 0.2
						},
						items: [{
							xtype: 'fieldset',
							title: '아카이브 여부',
							//height: 80,
							items: [{
                                hideLabel: true,
                                name: 'k_archive_select',
								xtype: 'radiogroup',
								width: 150,
								columns: 2,
								items: [{
										boxLabel: '보관',
										name: 'k_archv_trget_at',
										inputValue: 'Y',
										checked: true
									},
									{
										boxLabel: '보관안함',
										name: 'k_archv_trget_at',
										inputValue: 'N'
									}
								]
							},{
                                xtype: 'label',
                                text: '아카이브 원하면 보관 선택',
                                hidden: true,
                                width: 30,
                                style: {
                                    color: "red",
                                },
                            }]
						},{
							xtype: 'fieldset',
							title: '사용금지여부',
							//height: 80,
							hidden: true,
							items: [{
								hideLabel: true,
								xtype: 'radiogroup',
								width: 150,
								columns: 2,
								items: [{
										boxLabel: '사용',
										name: 'k_use_prhibt_at',
										inputValue: 'N',
										checked: true
									},
									{
										boxLabel: '사용금지',
										name: 'k_use_prhibt_at',
										inputValue: 'Y'
									}
								]
							}]
						}, {
							height: 80,
							hidden: true,
							// disabled: true,                          
							xtype: 'fieldset',
							title: '코덱',
							items: [{
								hideLabel: true,
								name: 'k_codec_select',
								xtype: 'radiogroup',
								width: 150,
								columns: 1,
								items: [{
										boxLabel: 'XDCAM HD',
										name: 'k_codec',
										inputValue: 'xdcam',
										checked: true
									},
									{
										boxLabel: 'DVCPRO HD',
										name: 'k_codec',
										inputValue: 'dvcpro',
										disabled: true
									}
								],
								listeners: {
									change: function(self, checked) {
										var checkedVal = checked.getRawValue();
										if (self.beforeValue == checkedVal) return;
									}
								}
							}]
						}, {
							//height: 80,                  
							//disabled: true, 
							xtype: 'fieldset',
							width: 250,
							title: '전송',
							items: [{
								name: 'k_send_select',
								hideLabel: true,
								xtype: 'checkboxgroup',
								//width: 180,
								columns: 2,
								defaults: {
									width: 100
								},
								items: [{
										boxLabel: '주조 전송',
										name: 'k_send_to_main',
										inputValue: 'k_send_to_main',
                                        listeners: {
                                            check: function(self, checked) {
                                                if(checked){
                                                    self.ownerCt.ownerCt.ownerCt.eachItem(function(r,idx){
                                                        if(self.name != r.name){
                                                            r.setValue(false);
                                                        }
                                                    });
                                                }
                                            }
                                        }
                                    },
                                    {
                                        boxLabel: 'A/B부조',
										name: 'k_send_to_sub',
										inputValue: 'k_send_to_sub',
										hidden: true,
									},
									{
                                        boxLabel: '뉴스부조',
										name: 'k_send_to_sub_news',
										inputValue: 'k_send_to_sub_news',
										hidden: true,
									},
									{
										boxLabel: 'QC 확인',
										name: 'k_qc_confirm',
										inputValue: 'k_qc_confirm',
										disabled: true,
                                        hidden: true
									}
								],
								listeners: {
									change: function(self, checked) {
										if(checked.length === 1 ) {
											if(checked[0].name == 'k_send_to_sub') {
												self.ownerCt.get(2).setVisible(true);
												self.ownerCt.get(3).setVisible(false);
											} else if(checked[0].name == 'k_send_to_sub_news') {
												self.ownerCt.get(1).setVisible(true);
												self.ownerCt.get(3).setVisible(false);
											} else {
												self.ownerCt.get(1).setVisible(false);
												self.ownerCt.get(2).setVisible(false);
												self.ownerCt.get(3).setVisible(false);
											}
										} else if(checked.length === 2) {
											self.ownerCt.get(1).setVisible(false);
											self.ownerCt.get(2).setVisible(false);
											self.ownerCt.get(3).setVisible(true);
										} else {
											self.ownerCt.get(1).setVisible(false);
											self.ownerCt.get(2).setVisible(false);
											self.ownerCt.get(3).setVisible(true);
										}
										var isValidQc = false;
										Ext.each(checked, function(r) {
											if (r.name == 'k_send_to_main' || r.name == 'k_send_to_sub' || r.name == 'k_send_to_sub_news') {
												isValidQc = true;
											}
                                        });
										
									}
								}
							},{
								xtype: 'label',
								text: '뉴스부조만 전송됩니다.',
								hidden: true,
								width: 30,
								style: {
									color: "red",
								},
								name: "sub_control_news",
							},{
								xtype: 'label',
								text: 'A/B부조만 전송됩니다.',
								hidden:true,
								width: 30,
								style: {
									color: "red",
								},
								name: "sub_control_ab",
							},{
								xtype: 'label',
								text: '중복 선택시 뉴스, A/B 부조 동시 전송',
								hidden:true,
								width: 30,
								style: {
									color: "red",
								},
							}]
						}],
						setCondition: function(type) {
							var self = this;
							self.items.each(function(item) {
								if (type == 'main_tm_on' && item.name == 'k_send_select') {
									item.setDisabled(false);
									item.items.get(0).setVisible(true);
                                    item.items.get(1).setVisible(false);
                                    item.items.get(2).setVisible(false);

									// label hidden
									item.ownerCt.get(1).setVisible(false);
                                	item.ownerCt.get(2).setVisible(false);
									item.ownerCt.get(3).setVisible(false);
								} else if (type == 'main_tm_off' && item.name == 'k_send_select') {
									item.setValue('');
                                    item.setDisabled(true);
                                    item.eachItem(function(r,idx){                                        
                                        r.setValue(false);                                  
                                    });
									item.items.get(0).setVisible(true);
                                    item.items.get(1).setVisible(false);
                                    item.items.get(2).setVisible(false);

									// label hidden
									item.ownerCt.get(1).setVisible(false);
                                	item.ownerCt.get(2).setVisible(false);
									item.ownerCt.get(3).setVisible(false);
								} else if (type == 'sub_tm_on' && item.name == 'k_send_select') {
									item.setDisabled(false);
									item.items.get(0).setVisible(false);
                                    item.items.get(1).setVisible(true);
                                    item.items.get(2).setVisible(true);

									// label hidden
									item.ownerCt.get(1).setVisible(false);
                                	item.ownerCt.get(2).setVisible(false);
									item.ownerCt.get(3).setVisible(true);
								} else if (type == 'sub_tm_off' && item.name == 'k_send_select') {
									item.setValue('');
                                    item.setDisabled(true);
                                    item.eachItem(function(r,idx){                                        
                                        r.setValue(false);                                  
                                    });
									item.items.get(0).setVisible(false);
                                    item.items.get(1).setVisible(true);
                                    item.items.get(2).setVisible(true);

									// label hidden
									item.ownerCt.get(1).setVisible(false);
                                	item.ownerCt.get(2).setVisible(false);
									item.ownerCt.get(3).setVisible(false);
                                }
                                
                                if ( type == 'archive_on' && item.name == 'k_archive_select' ){
                                    item.setValue('Y');
									item.ownerCt.get(1).setVisible(false);
                                    //item.setDisabled(false);
                                }
                                if ( type == 'archive_off' && item.name == 'k_archive_select' ){
                              
									item.setValue('N');
									item.ownerCt.get(1).setVisible(true);
                                }

								// else if( type == 'codec_on'  && item.name == 'k_codec_select' ){                                       
								//     item.items.get(1).setDisabled(false);
								// }else if( type == 'codec_off'  && item.name == 'k_codec_select' ){                                      
								//     item.items.get(1).setDisabled(true);
								// }
							});

						}
					}],
					setCustomAction: function(condition) {
						//제작 구분 콘텐츠 유형에 따라 변경
						var self = this;
						var customField = self.getForm().findField('k_custom_field');
						if (condition.ud_content_id == '3' || condition.ud_content_id == '9') {
							//마스터본
							if (condition.ud_content_id == '3' && condition.ud_content_tab == 'product') {
								customField.setCondition('main_tm_on');
								//주조전송
								//코덱
							} else if (condition.ud_content_id == '9' && condition.ud_content_tab == 'news') {
								customField.setCondition('sub_tm_on');
								//부조전송
								//코덱 비활성화
							} else {
								if (condition.ud_content_tab == 'product') {
									customField.setCondition('main_tm_off');
								} else if (condition.ud_content_tab == 'news') {
									customField.setCondition('sub_tm_off');
								} else {
									customField.setCondition('main_tm_off');
								}
							}
						} else {
							if (condition.ud_content_tab == 'product') {
								customField.setCondition('main_tm_off');
							} else {
								customField.setCondition('sub_tm_off');
                            }
                        }

                        if( condition.ud_content_id == '1'){
                            customField.setCondition('archive_off');
                        }else{
                            customField.setCondition('archive_on');
                        }
					}
				}]
				}],
				listeners: {
					afterrender: function(self) {},
					render: function(self) {
						//getRootPathArray();
						//getMPathArray();
					}
				}
			});
		});

		function checkExt() {
			var ud_content = Ext.getCmp('content_type');
			var ud_content_id = ud_content.getValue();
			var record = ud_content.findRecord(ud_content.valueField || ud_content.displayField, ud_content_id);

			return record.get('allowed_extension');
		}

		function isValid() {
			var metaTab = Ext.getCmp('regist_form_tabpanel');
			var length = metaTab.items.length;
			var curTab = metaTab.activeTab;

			//TC정보 그리드 스토어에 valid체크
			var tc_grid = Ext.getCmp('list<?= $meta_field_id ?>');

			if (!Ext.isEmpty(tc_grid)) {
				if (Ext.isEmpty(tc_grid.getStore().data.items)) {
					return 'false';
				}
			}

			for (var i = 0; i < length; ++i) {
				metaTab.setActiveTab(i);
				if (!metaTab.items.items[i].getForm().isValid()) {
					return 'false';
				}
			}

			metaTab.setActiveTab(curTab);

			return 'true';
		}

		function clearForm() {
			var metaTab = Ext.getCmp('regist_form_tabpanel');
			var length = metaTab.items.length;
			var curTab = metaTab.activeTab;

			for (var i = 0; i < length; ++i) {
				metaTab.setActiveTab(i);
				metaTab.items.items[i].getForm().reset();
			}
			var tc_grid = Ext.getCmp('list<?= $meta_field_id ?>');
			if (!Ext.isEmpty(tc_grid)) {
				tc_grid.getStore().removeAll();
			}
			metaTab.setActiveTab(curTab);
		}

		function getFormData() {
			var metaTab = Ext.getCmp('regist_form_tabpanel');
			var length = metaTab.items.length;
			var arrMeta = [];
			var curTab = metaTab.activeTab;

			//CJO, 저장전 커스텀 로직
			<?= $beforeSaveJsLogic ?>

			for (var i = 0; i < length; ++i) {
				metaTab.setActiveTab(i);
				var p = metaTab.items.items[i].getForm().getValues();
				metaTab.items.items[i].getForm().items.each(function(i) {
					if (i.xtype == 'checkbox' && !i.checked) {
						i.el.dom.checked = true;
						i.el.dom.value = '';
					}
					if (i.xtype == 'combo') {
						var kval = i.id;
						p[i.name] = i.getValue();
					}
					if (i.xtype == 'c-tree-combo') {
						var kval = i.id;
						p[i.name] = i.getValue();
					}
				});

				if (i == 0 && Ext.getCmp('category') != null) {
					var tn = Ext.getCmp('category').treePanel.getSelectionModel().getSelectedNode();
					p.c_category_id = tn.attributes.id;
				}

				if (i == 0 && Ext.getCmp('regist_form_tab').get('bbar_form')) {
                    var addValues = Ext.getCmp('regist_form_tab').get('bbar_form').getForm().getValues();
                    //console.log(addValues);
					//p.use_prhibt_at = addValues.use_prhibt_at;
					p.k_codec = addValues.k_codec;
					p.k_send_to_main = addValues.k_send_to_main;
                    p.k_send_to_sub = addValues.k_send_to_sub;
                    p.k_send_to_sub_news = addValues.k_send_to_sub_news;
                    p.k_qc_confirm = addValues.k_qc_confirm;
                    p.k_archv_stre_at = addValues.k_archv_stre_at;
                    p.k_archv_trget_at = addValues.k_archv_trget_at;
				}
				arrMeta.push(p);
			}

			//TC정보 그리드 스토어의 xml 등록
			var tc_grid = Ext.getCmp('list<?= $meta_field_id ?>');

			if (!Ext.isEmpty(tc_grid)) {

				var tmp = new Array();

				tc_grid.getStore().each(function(i) {
					tmp.push(i.data);
				});
				arrMeta.push({
					multi: tmp
				});
			}

			metaTab.setActiveTab(0);

			return arrMeta;
		}

		function loadFormData(data) {
			var metaTab = Ext.getCmp('regist_form_tabpanel');
			var retData = {
				success: true,
				msg: ''
			};

			if (data == null) {
				retData.success = false;
				retData.msg = 'data is null';
				return retData;
			}

			var rec = Ext.decode(data);

			try {
				var curTab = metaTab.activeTab;

				for (var i = 0; i < rec.length; ++i) {
					var target_tab = metaTab.get('user_metadata_' + rec[i].k_meta_field_id);

					if (!Ext.isEmpty(target_tab)) {

						if (Ext.isObject(rec[i]) && !Ext.isEmpty(rec[i]) && Ext.isEmpty(rec[i].multi)) {
							var record = new Ext.data.Record(rec[i]);
							target_tab.getForm().loadRecord(record);
						}

						if (i == 0 && Ext.getCmp('category')) {
							//카테고리 처리
							var categoryId = rec[i].c_category_id;
							if (categoryId != '0') {
								Ext.getCmp('category').setPath(rec[i].c_fullPath);
							}
						}

						if (rec[i].k_meta_field_id == '4002615') {

							var tc_grid = Ext.getCmp('list<?= $meta_field_id ?>');
							tc_grid.getStore().load({
								params: {
									meta_field_id: '<?= $meta_field_id ?>',
									content_id: rec[i].c_content_id
								}
							});
						}
					}
				}
				metaTab.setActiveTab(curTab);
			} catch (err) {
				retData.success = false;
				retData.msg = 'Fail to load form data\n' + err;
			}

			return retData;
		}

		function get_meta() {
			var metadata = [];

			var metadata = getFormData();

			var returnValue = {

				user_id: '<?= $userId ?>',
				flag: '<?= $flag ?>',
				metadata_type: 'id',
				metadata: metadata
			};
			var ret = Ext.encode(returnValue);

			return ret;
		}

		function put_meta(data) {
			var decodeData = Ext.decode(data);
			var metadata = decodeData.metadata;

			Ext.getCmp('content_type').setValue(metadata[0].k_ud_content_id);

			Ext.getCmp("regist_form_tabpanel").put_meta_afterLoadFormMetaData(Ext.getCmp("regist_form_tabpanel"), '', data);
			Ext.getCmp("regist_form_tabpanel").beforeValue = metadata[0].k_ud_content_id;
		}

		function put_meta2(data) {
			data = Ext.decode(data);
			var metadata = data.metadata;

			var metaTab = Ext.getCmp('regist_form_tabpanel');
			var length = metaTab.items.length;
			var arrMeta = [];
			var curTab = metaTab.activeTab;

			var i = 0;
			Ext.each(metadata, function(meta, index) {
				metaTab.setActiveTab('user_metadata_' + meta.k_ud_content_id);

				var p = metaTab.items.items[i].getForm().setValues(meta);
				i++;
			});

			metaTab.setActiveTab(curTab.id);
		}

		function getManageNoValues(){
			var manage = new Object();
			manage['tape_no'] = null;
			manage['id_no'] = null;
			manage['telecine_type'] = null;
			var metaTab = Ext.getCmp('regist_form_tabpanel');
			var manageNoField = metaTab.get(0).getForm().findField('manage_no');
		
			if(!Ext.isEmpty(manageNoField)){
				var manageNoValues = manageNoField.getValues();
				manage['tape_no'] = manageNoValues.tape_no;
            	manage['id_no'] = manageNoValues.id_no;
            	manage['telecine_type'] = manageNoValues.telecine_type;
			}	
			
            return [manage];
		}

		function submit_meta() {
			var metadata = [];

			var metadata = getFormData();

			var returnValue = {

				user_id: '<?= $userId ?>',
				flag: '<?= $flag ?>',
				metadata_type: 'id',
				metadata: metadata
			};
			var ret = Ext.encode(returnValue);

			return ret;
		}

		function submit_meta2() {
			var metadata = [];

			var metadata = getFormData();

			var returnValue = {

				result: 'false',
				msg: '필수 입력 데이터가 없습니다.',
				user_id: '<?= $userId ?>',
				flag: '<?= $flag ?>',
				metadata: metadata
			};
			var ret = Ext.encode(returnValue);

			var return_sub = ret.substr(0, ret.length - 1);

			return ret;
		}

		function submit_meta_soap() {
			var metadata = [];
			var metadata = getFormData();
            
			var returnValue = {
				user_id: '<?= $userId ?>',
				flag: '<?= $flag ?>',
				metadata_type: 'id',
				metadata: metadata
			};
			var ret = Ext.encode(returnValue);


			return ret;
		}

		function getRootPath() {
			var category_id = null;
			var channel = 'edius';
			var mid_path = '';

			if (Ext.getCmp('category') != null) {
				var tn = Ext.getCmp('category').treePanel.getSelectionModel().getSelectedNode();
				category_id = tn.attributes.id;
			}

			if (Ext.isEmpty(category_id) || Ext.isEmpty(prog_path) || Ext.isEmpty(root_path)) {
				return 'false';
			}

			// Edius로 넘길때는 역슬러쉬 두번(\\)으로 변경해줘야 됨
			//return root_path[channel] + "\\" + prog_path[category_id] + mid_path ;
			return "Z:\\Export";
		}

		function getRootPathArray() {
			Ext.Ajax.request({
				url: '/store/get_task_rootpath.php',
				callback: function(self, success, response) {
					if (success) {
						try {
							var r = Ext.decode(response.responseText);

							if (r.success) {
								root_path = r.data;
							}
						} catch (e) {}
					} else {

					}
				}
			});
		}

		function getMPathArray() {
			Ext.Ajax.request({
				url: '/store/get_category_path.php',
				callback: function(self, success, response) {
					if (success) {
						try {
							var r = Ext.decode(response.responseText);

							if (r.success) {
								prog_path = r.data;
							}
						} catch (e) {

						}
					} else {

					}
				}
			});
		}

		function submit(url, file) {
			var metadata = [];

			var metadata = Ext.encode(getFormData());
			var url = '/interface/plugin_register.php';

			Ext.Ajax.request({
				url: url,
				params: {
					filepath: file,
					user_id: '<?= $userId ?>',
					metadata: metadata,
					flag: '<?= $flag ?>'
				},
				callback: function(self, success, response) {

					if (success) {
						try {
							var r = Ext.decode(response.responseText);

							if (r.success) {
								window.location = 'success.php';
							} else {
								window.location = 'fail.php';
							}
						} catch (e) {
							window.location = 'fail.php';
						}
					} else {
						window.location = 'fail.php';
					}
				}
			});

			return true;
		}

		function fn_filter_type3_1() {
			var v_filter_value = Ext.getCmp('k_type3_1').getValue();

			Ext.getCmp('k_type3_2').setValue();
			Ext.getCmp('k_type3_3').setValue();

			Ext.getCmp('k_type3_2').store.clearFilter();
			Ext.getCmp('k_type3_3').store.clearFilter();

			Ext.getCmp('k_type3_2').store.filterBy(function(record) {
				if (!Ext.isEmpty(v_filter_value)) {
					if (record.get('c_pid') == v_filter_value || Ext.isEmpty(record.get('c_pid'))) return true;
				} else {
					return false;
				}
			});
		}

		function fn_filter_type3_2() {
			var v_filter_value = Ext.getCmp('k_type3_2').getValue();

			Ext.getCmp('k_type3_3').setValue();

			Ext.getCmp('k_type3_3').store.clearFilter();

			Ext.getCmp('k_type3_3').store.filterBy(function(record) {
				if (!Ext.isEmpty(v_filter_value)) {
					if (record.get('c_pid') == v_filter_value || Ext.isEmpty(record.get('c_pid'))) return true;
				} else {
					return false;
				}
			});
		}
	</script>



</body>

</html>