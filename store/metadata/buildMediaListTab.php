<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
$user_id = $_SESSION['user']['user_id'];//2015-11-09 upload_other

function buildMediaListTab($content_id, $args) {

	global $db, $logger, $user_id;

	$down_ip = $_SERVER['SERVER_ADDR'];

    $deny_download_proxy = 'true';

	$ud_content_id = $db->queryOne("
						SELECT	UD_CONTENT_ID
						FROM	BC_CONTENT
						WHERE	CONTENT_ID = ".$content_id."
					");

	 if (checkAllowUdContentGrant($_SESSION['user']['user_id'], $ud_content_id, GRANT_EDIT)) {
		$lv_check_hidden_upload = 'false';
	} else {
		$lv_check_hidden_upload = 'true';
	}

	if (checkAllowUdContentGrant($_SESSION['user']['user_id'], $ud_content_id, GRANT_DOWNLOAD)) {
		$deny_download_proxy = 'false';
    }

	// if (checkAllowGrant($_SESSION['user']['user_id'], $content_id, GRANT_REWRAPPER)) {
	// 	$REWRAPPERhidden = 'false';
	// } else {
	// 	$REWRAPPERhidden="true";
	// }

	// 전체 주석
	$REWRAPPERhidden = "true";

	$query = "select distinct t1.media_id from
	  (select * from bc_media bm where bm.content_id = {$content_id} and bm.media_type ='original' and bm.flag is null) t1,bc_task bt
	   where t1.media_id = bt.media_id and bt.status = 'complete'";
	 $media_id = $db->queryOne($query);

	if ( ! empty($media_id)) {
		 $orginal_status = true;
	} else {
		$orginal_status = false;
	}

	$usrMetaContent = $db->queryRow("
		SELECT USE_PRHIBT_AT, EMBG_RELIS_DT 
		FROM BC_USRMETA_CONTENT 
		WHERE USR_CONTENT_ID =".$content_id
	);
	
/*	$query = "select count(*) from alto_archive aa where aa.media_id in ( select media_id from bc_media bm where bm.content_id ={$content_id} and bm.media_type ='original')";

	if($db->queryOne($query))
	{
		$archive_status = true;
	}
	else
	{
		$archive_status = false;
	}
*/
	if ( ! $orginal_status
			&&  $archive_status
			&& checkAllowGrant($_SESSION['user']['user_id'], $content_id, GRANT_RESTORE)) {

		$restore_hidden = 'false';
	} else {
		$restore_hidden = "true";
	}

	$restore_hidden = "true";//전체주석

	return <<<EOD
	{
		xtype: 'grid',
		border: false,
		$args,
		id: 'media_list',
		cls: 'proxima_customize',
		stripeRows: true,
		//region: 'north',
		layout: 'fit',
		// tools: [{
		// 	id: 'refresh',
		// 	handler: function(e, toolEl, p, tc){
		// 		p.store.reload();
		// 	}
		// }],
		loadMask: true,
		title: _text('MN00173'),
		split: true,

		tbar:[{
			text: '<span style="position:relative;top:1px;" title="'+_text('MN00390')+'"><i class="fa fa-refresh" style="font-size:13px;"></i></span>',
			cls: 'proxima_btn_customize proxima_btn_customize_new',
			width: 30,
			handler: function(btn, e){
				Ext.getCmp('media_list').getStore().reload();
			}
		},{
			hidden : $lv_check_hidden_upload,
			cls: 'proxima_btn_customize proxima_btn_customize_new',
			width: 30,
			text: '<span style="position:relative;top:1px;" title="'+_text('MN00399')+'"><i class="fa fa-upload" style="font-size:13px;"></i></span>',
			handler: function(btn, e){
				var extension_arr = ['ZIP', 'HWP', 'DOC', 'DOCX','XML', 'PPTX', 'PPT', 'XLS', 'XLSX', 'PDF', 'JPG', 'JPEG', 'PNG', 'MP3','WAV','TXT'];
				var extensionStr = extension_arr.join(',');
				var win = new Ext.Window({
					title: _text('MN00399'),//'업로드',
					//width : 450,
					width : 545,
					top: 50,
					//height: 110,
					height: 200,
					modal: true,
					layout: 'fit',
					items: [{
						xtype: 'form',
						fileUpload: true,
						border: false,
						frame: true,
						id: 'fileAttachuploadForm',
						defaults: {
							labelSeparator: '',
							labelWidth: 30,
							anchor: '95%',
							style: {
								'padding-top': '5px'
							}
						},
						items: [{
							xtype: 'fileuploadfield',
							hidden: true,
							id: 'fileAttachUpload',
							name: 'FileAttach',
							listeners: {
								fileselected: function(self, value){
									Ext.getCmp('fileAttachFakePath').setValue(value);
								}
							}
						},{
							xtype: 'compositefield',
							fieldLabel: _text('MN01045'),//'첨부 파일',
							items: [{
								xtype: 'textfield',
								id: 'fileAttachFakePath',
								allowBlank: false,
								readOnly: true,
								flex: 1
							},{
								xtype: 'button',
								text: _text('MN02176'),//'파일선택',
								listeners: {
									click: function(btn, e){
										$('#'+Ext.getCmp('fileAttachUpload').getFileInputId()).click();
									}
								}
							}]
						},{
							xtype: 'combo',
							id: 'attach_file_type',
							editable: false,
							mode: "local",
							fieldLabel: '첨부파일 유형',
							displayField: 'code_itm_nm',
							valueField: 'code_itm_code',
							hiddenValue: 'code_itm_code',
							typeAhead: true,
							triggerAction: 'all',
							lazyRender: true,
							allowBlank: false,
							store: new Ext.data.JsonStore({
								restful: true,
								proxy: new Ext.data.HttpProxy({
									method: 'GET',
									url: '/api/v1/open/data-dic-code-sets/' + 'ATCHMNFL_TY' + '/code-items',
									type: 'rest'
								}),
								baseParams: {
									is_code: '1',
								},
								autoLoad:true,
								root: 'data',
								fields: [
									{ name: 'code_itm_code', mapping: 'code_itm_code' },
									{ name: 'code_itm_nm', mapping: 'code_itm_nm' }
								]
							}),
							listeners: {
								select: function (self, record, idx) {
									self.setValue(record.get('code_itm_code'));
								}
							}
						},{
							xtype:'fieldset',
                            title:'허용확장자',
                            collapsible:false,
                            border:true,
                            items:[{
                                xtype:'displayfield',
                                hideLabel:true,
                                value:extensionStr
                            }]
						}],
						buttonsAlign: 'left',
						buttons: [{
							text : '<span style="position:relative;top:1px;"><i class="fa fa-check" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00046'),//'저장'
							scale: 'small',
							handler: function (b, e) {
								var regist_form = Ext.getCmp('fileAttachuploadForm').getForm();
								if(!regist_form.isValid()) {
									Ext.Msg.alert( _text('MN00023'), _text('MSG01006'));//알림, 첨부파일을 선택 해 주시기 바랍니다.
									return;
								}
								//확장자 체크
                                //var extension_arr = ['ZIP', 'HWP', 'DOC', 'DOCX','XML', 'PPTX', 'PPT', 'XLS', 'XLSX', 'PDF', 'JPG', 'JPEG', 'PNG', 'MP3','WAV','TXT'];
								var upload_file = Ext.getCmp('fileAttachUpload').getValue();
                                var filename_arr = upload_file.split('.');
                                var extension_index = filename_arr.length-1;
                                var file_extension = filename_arr[extension_index].toUpperCase();
								var attach_file_type = Ext.getCmp('attach_file_type').getValue();
                                if(extension_arr.indexOf(file_extension) === -1) {
									Ext.Msg.alert( _text('MN00023'), _text('MN00309') + ' : ' + extension_arr.join(', ') );//알림, 허용 확장자 :
									return;
								}
								regist_form.submit({
									url: '/custom/ktv-nps/download/upload_attach.php',
									params: {
										content_id : '{$content_id}',
										ud_content_id : '{$ud_content_id}',
										attach_file_type : attach_file_type,
									},
									success: function(form, action) {
										var r = Ext.decode(action.response.responseText);

										if(r.result == 'false') {
											Ext.Msg.alert( _text('MN00023'), r.msg);
											return;
										}
										//Ext.Msg.alert( _text('MN00023'), '등록에 성공하였습니다.');
										Ext.getCmp('media_list').getStore().reload();
										win.close();
									},
									failure: function(form, action) {
										var r = Ext.decode(action.response.responseText);
										Ext.Msg.alert( _text('MN00023'), r.msg);
									}
								});
							}
						},{
							text : '<span style="position:relative;top:1px;"><i class="fa fa-close" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00031'),//'닫기'
							scale: 'small',
							handler: function (b, e) {
								win.close();
							}
						}]
					}]
				}).show();
			}
		},{
			//hidden: $deny_download_proxy,
			cls: 'proxima_btn_customize proxima_btn_customize_new',
			width: 30,
			text: '<span style="position:relative;top:1px;" title="'+_text('MN00142')+'"><i class="fa fa-download" style="font-size:13px;"></i></span>',
			handler: function(btn, e){
				var mediaList = Ext.getCmp('media_list');
				var files = Ext.getCmp('media_list').getSelectionModel().getSelected();
				if (!files) {
					Ext.Msg.alert(_text('MN00024'), _text('MSG00055'));

					return;
				}

				var media_type = files.get('media_type');
                var ud_content_id = files.get('ud_content_id');
                var bs_content_id = files.get('bs_content_id');
				var user_id = '{$user_id}';

                var media_id = files.get('media_id');
                				
				// var usrMetaRecord = Ext.getCmp('ud_content_id'+ud_content_id).getSelectionModel().getSelected();
				// var usePrhibtAt = usrMetaRecord.get('usr_meta').use_prhibt_at;
    
    
				// if(usePrhibtAt == 'Y'){
				//   return Ext.Msg.alert('알림','사용금지 된 콘텐츠 입니다.');
				// }
			
				// 엠바고 해제 일시
				// var embgRelisDt = usrMetaRecord.get('usr_meta').embg_relis_dt;
				// if(!Ext.isEmpty(embgRelisDt)){
				//   var embargoTimeStamp = YmdHisToDate(embgRelisDt).getTime();
				//   var nowTimeStamp = new Date().getTime();
				//   if(embargoTimeStamp > nowTimeStamp){
				// 	return Ext.Msg.alert('알림','엠바고 해제일시가 지난 후 다운로드 해주세요.');
				//   }
				// }
			
                // if(( media_type == 'original' || media_type == 'archive' ) && bs_content_id == MOVIE ) {
				// 	Ext.Msg.alert( _text('MN00023'), _text('MSG01046'));//다운로드 할 수 없는 유형입니다
				// 	return;
				// }
           
                var url = '/store/download.php?media_id='+ media_id + '&media_type=' + media_type + '&ud_content_id=' + ud_content_id + '&user_id='+user_id;
                //window.open(url);
                var checkList = ['use','embargo'];
                var waitMsg = Ext.Msg.wait('처리 중입니다.', '처리중...');
                Ext.Ajax.request({
                    url: '/api/v1/contents/$content_id/check',
                    params: {
                        check_list:Ext.encode(checkList),
                        media_id: media_id,
                        media_type: media_type
                    },
                    callback:function(opts, success, response){
                        var r = Ext.decode(response.responseText);
                        waitMsg.hide();
                        if(success){
                            Ext.getBody().createChild({
                                tag: 'iframe',
                                cls: 'x-hidden',
                                onload: 'var t = Ext.get(this); t.remove.defer(1000, t);',
                                src: url
                            });
                        }else{
                            Ext.Msg.alert('알림', r.msg);
                        };
                    }
                });

			}
		},{
		    //hidden: $deny_download_proxy,
			hidden : true,
			//icon: '/led-icons/disk.png',
			//style: 'border-style:outset;',
			text: '프록시 다운로드',
			handler: function(btn, e){
				/*
				var files = Ext.getCmp('media_list').getSelectionModel().getSelections(),
					media_id_list=[];

				if(Ext.getCmp('media_list').getSelectionModel().getCount() < 1) {
					Ext.Msg.alert( _text('MN00023'), '항목을 선택 해 주시기 바랍니다.');
					return;
				}

				if ( ! files) {
					Ext.Msg.alert(_text('MN00024'), _text('MSG00055'));

					return;
				}

				var is_allow = true;
				Ext.each(files, function(r){
					media_id_list.push(r.get('media_id'));
				});

				window.open('/store/download_use_link.php?media_id='+media_id_list.join(','));
				*/

				window.open('/store/download.php?content_id=$content_id');
			}
		},{
			//style: 'border-style:outset;',
			hidden: $REWRAPPERhidden,
			width : 70,
			text: '<span style="position:relative;top:1px;"><i class="fa fa-pencil-square-o" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00396'),
			handler: function(btn, e){
				Ext.Msg.show({
					title: _text('MN00024'),
					msg: _text('MSG00214'),
					buttons: Ext.Msg.YESNO,
					fn: function(btnID, text, opt){
						if (btnID == 'yes')
						{
							Ext.Ajax.request({
								url: '/store/rewrapping.php',
								params: {
									content_id: $content_id,
									type: 'XDCAM'
								},
								callback: function(opts, success, response)
								{
									if (success)
									{
										try
										{
											var r = Ext.decode(response.responseText);
											if (r.success)
											{
												Ext.Msg.alert(_text('MN00024'), _text('MSG00019'));
											}
											else
											{
												Ext.Msg.alert(_text('MN00024'), response.msg);
											}
										}
										catch (e)
										{
											Ext.Msg.alert(e['title'], e['message']);
										}
									}
									else
									{
										Ext.Msg.alert(_text('MN00024'), response.statusText+'[error code: '+response.status+']');
									}
								}
							});
						}
					}
				});
			}
		},
		{
				hidden : $restore_hidden,
				//style: 'border-style:outset;',
				//$$ 다운로드, MN00142
				text: '<span style="position:relative;top:1px;"><i class="fa fa-hdd-o" style="font-size:13px;"></i></span>&nbsp;'+'리스토어 요청',

				handler: function(){
						Ext.Msg.show({
						title: '확인',
						msg: '리스토어 요청을 하시겠습니까?',
						buttons: Ext.Msg.YESNO,
						fn: function(btnID, text, opt){
							if (btnID == 'yes')
							{
								Ext.Ajax.request({
									url: '/connector/alto/restore.php',
									params: {
										content_id: $content_id
									},
									callback: function(opts, success, response)
									{
										if (success)
										{
											try
											{
												var r = Ext.decode(response.responseText);
												if (r.success)
												{
													Ext.Msg.alert('확인', '성공적으로 리스토어 요청을 하였습니다.');
												}
												else
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
												Ext.Msg.alert(e['title'], e['message']);
											}
										}
										else
										{
											Ext.Msg.alert('확인', response.statusText+'[error code: '+response.status+']');
										}
									}
								});
							}
						}
					})
				}

        },{
            //HUIMAI, 첨부파일 삭제기능
            hidden : $lv_check_hidden_upload,
            //MN01034 첨부파일 삭제
            text: '<span style="position:relative;top:1px;" title="'+_text('MN01034')+'"><i class="fa fa-ban" style="font-size:13px;"></i></span>',
            cls: 'proxima_btn_customize proxima_btn_customize_new',
            width: 30,
            handler: function(btn, e){
                var files = Ext.getCmp('media_list').getSelectionModel().getSelected();
                if (!files) {
                    //MSG00082 삭제하실 항목을 선택해주세요
                    Ext.Msg.alert(_text('MN00024'), _text('MSG00082'));

                    return;
                }

                var media_type = files.get('media_type');
                var ud_content_id = files.get('ud_content_id');
				var media_id = files.get('media_id');
                
                if(media_type != 'Attach') {
					//Ext.Msg.alert( _text('MN00023'), _text('MSG01006'));//첨부파일을 선택 해 주시기 바랍니다.
					Ext.Msg.alert( _text('MN00023'), '첨부파일만 삭제해 주세요.');//첨부파일을 선택 해 주시기 바랍니다.
                    return;
                }

                //2015-11-19 다운로드 버튼 클릭시 파일사이즈 체크
                Ext.Msg.show({
                    title: _text('MN00024'),
                    msg: _text('MSG00172'),//삭제 하시겠습니까?
                    modal: true,
                    icon: Ext.MessageBox.QUESTION,
                    buttons: Ext.Msg.YESNO,
                    fn: function(btnId) {
                        if(btnId=='no') return;
                        Ext.Ajax.request({
                            url: '/store/attach_delete.php',
                            params: {
                                media_id: media_id,
                                ud_content_id: ud_content_id
                            },
                            callback: function(opts, success, response){
                                var r = Ext.decode(response.responseText);
                                if (!r.success) {
                                    Ext.Msg.alert( _text('MN00023'), r.msg);
                                    return;
                                }
                                Ext.getCmp('media_list').getStore().reload();
                            }
                        });
                    }
                });
            }
        }],
		isContentDownloadGrant: function (udContentId, userId) {
			var _this = this;
			var downLoadGrant = 16;
			var ajax = Ext.Ajax.request({
			  url: '/api/v1/permission/content-grant',
			  params: {
				user_id: userId,
				ud_content_id: udContentId,
				grant: downLoadGrant
			  },
			  callback: function (opts, success, response) {
				var r = Ext.decode(response.responseText);
				if (r.success) {
				  _this.downloadHandler();
				} else {
				  Ext.Msg.alert('알림', r.msg);
				}
			  }
			});
			return ajax;
		  },
		store: new Ext.data.JsonStore({
			id: 'detail_media_grid',
			url: '/store/get_media.php',
			root: 'data',
			fields: [
				'task_status',
				'task_progress',
				'bs_content_id',
				'ud_content_id',
				'content_id',
				'media_id',
				'storage_id',
				'media_type',
				'attach_type',
				'path',
                'filesize',
                'memo',
				{name: 'created_date', type: 'date', dateFormat: 'YmdHis'},
				{name: 'del'},
				'extension',
				'media_type_name'
			],
			listeners: {
				exception: function(self, type, action, opts, response, args){
					Ext.Msg.alert(_text('MN00022'), response.responseText);
				}
			}

		}),
		viewConfig: {
			//!!emptyText: '등록된 미디어파일이 없습니다.'
			emptyText: _text('MSG00142'),
			//forceFit : true
		},
		sm: new Ext.grid.CheckboxSelectionModel({
			singleSelect: true
		}),
		cm: new Ext.grid.ColumnModel({
			defaults: {
				sortable: false,
				menuDisabled: true
			},
			columns: [
				
				{header: _text('MN00300'), hidden:true,dataIndex: 'media_type', width: 65, renderer: function(value, metaData, record, rowIndex, colIndex, store){
					var tip;
					switch(value){
						case 'original':
							//>>tip = '원본 자료입니다';
							tip = _text('MSG00177');
							//>>value = '원본';
							value = _text('MN00370');
						break;

						case 'thumb':
							//>>tip = '콘텐츠를 대표하는 이미지입니다.';
							tip = _text('MSG00178');
							//>>value = '대표이미지';
							value = _text('MN00371');
						break;

						case 'proxy':
							//>>tip = '스트리밍 서비스를 위한 H264형식의 저해상도 프록시 파일입니다.';
							tip = _text('MSG00179');
							//>>value = '프록시 파일';
							value = _text('MN00372');
						break;

						case 'download':
							//>>tip = '다운로드 서비스를 위한 WMV형식의 자료입니다..';
							tip = _text('MSG00180');
							//>>value = '다운로드';
							value = _text('MN00050');
						break;

						case 'userproxy':
							//>>tip = '사용자가 추가한 프록시 파일입니다.';
							tip = _text('MSG00181');
							//>>value = '사용자추가 프록시 파일';
							value = _text('MN00373');
						break;

						case 'nearline':
							//>>tip = '니어라인 자료입니다';
							tip = _text('MSG00182');
							//>>value ='니어라인';
							value = _text('MN00057');
						break;

						case 'archive':
							//>>tip = '아카이브 자료입니다';
							tip = _text('MSG00183');
							//>>value = '아카이브';
							value = _text('MN00056');
						break;

						case 'restore':
							//>>tip = '리스토어 자료입니다';
							tip = _text('MSG00184');
							//>>value = '리스토어';
							value = _text('MN00051');
						break;

						case 'pfr_restore':
							//>>tip = '리스토어 자료입니다';
							tip = _text('MSG00184');
							//>>value = '구간추출 리스토어';
							value = _text('MN00374');
						break;

						case 'proxy_hi':
							//>>tip = '고해상도 프록시파일입니다.';
							tip = _text('MSG00185');
							//>>value = '고해상도 프록시파일';
							value = _text('MN00375');
						break;

						case 'attach':
							//>>tip = '고해상도 프록시파일입니다.';
							tip = '첨부파일';
							//>>value = '고해상도 프록시파일';
							value = '첨부파일';
						break;
						case 'pfr':
							//>>tip = '고해상도 프록시파일입니다.';
							tip = '원본구간추출';
							//>>value = '고해상도 프록시파일';
							value = '원본구간추출';
						break;
						case 'sproxy':
							//>>tip = '보급본';
							tip = '보급본';
							//>>value = '보급본';
							value = '보급본';
						break;
						case 'hproxy':
							//>>tip = '고해상도';
							tip = '고해상도';
							//>>value = '고해상도';
							value = '고해상도';
						break;
			case 'Attach':
							//>>tip = '첨부파일';
							tip = '첨부파일';
							//>>value = '첨부파일';
							value = '첨부파일';
						break;
			case 'shot_list':
							//>>tip = '샷리스트';
							tip = '샷리스트';
							//>>value = '샷리스트';
							value = '샷리스트';
						break;
					}

//					if (v.search(/^pfr [0-9]/) > -1)
//					{
//						tip =  '';
//						//>>value = '고해상도 프록시파일';
//						value = _text('MN00375');
//					}

					metaData.attr = 'ext:qtip="'+tip+'"';
					return value;
				}},
				
				{header: _text('MN00300'), dataIndex: 'media_type_name', width: 100,},
				{header:'코덱', dataIndex: 'extension', width: 65, align:'center'},
					/*
				{header: '저장경로', dataIndex: 'path', width: 270},
				{header: _text('MN00301'), dataIndex: 'filesize', width: 70, align: 'center'},
				{header: _text('MN00107'), dataIndex: 'created_date', renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s'), width: 120, align: 'center'},
				{header: '삭제정보', dataIndex: 'del', width: 100, align: 'center'}
				*/
				{header: _text('MN00301'), dataIndex: 'filesize', width: 70, align: 'center'},
				{header: '해상도',dataIndex:'media_type', width: 70, align: 'center',
					renderer: function(value,metaData,record){
						switch(value){
							// 중해상도
							case "proxy":
								return '1280x720';
							break;
							// 저해상도
							case "proxy360":
								return '640x360';
							break;
							// 고해상도
                            case "proxy2m1080":
                                case "proxy2m1080logo":
								return '1920x1080';
							break;
							// 전송용
							case "proxy15m1080":
								return '1920x1080';
							break;
							// 대표이미지
							case "thumb":
								return '640x360';
							break;
							default:
                                return;
							break;
						};
					} 
				},
				{header: '첨부파일 유형',dataIndex: 'attach_type', width: 90, align: 'center'},
				// {header: '로고',dataIndex:'media_type', align: 'center',
				// 	renderer: function(value,metaData,record){
				// 		switch(value){
				// 			// 중해상도
				// 			case "proxy":
				// 				return '무';
				// 			break;
				// 			// 저해상도
				// 			case "proxy360":
				// 				return '유';
				// 			break;
				// 			// 고해상도
				// 			case "proxy2m1080":
				// 				return '유';
				// 			break;
				// 			// 전송용
				// 			case "proxy15m1080":
				// 				return '무';
				// 			break;
				// 			// 대표이미지
				// 			case "thumb":
				// 				return;
				// 			break;
				// 			default:
                //                 return;
				// 			break;
				// 		};
				// 	},
				// 	width:35
				// },
				{header: _text('MN00107'), dataIndex: 'created_date', renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s'), width: 130, align: 'center'},
				/* 미디어 목록에서 작어상태는 보여줄 필요없음. 2016.01.21 g.c.Shin
				{header: _text('MN00138'), dataIndex: 'task_status', width: 100, align: 'center', renderer: this.taskStatusRender, scope: this},
				*/
                {header: _text('MN00502'), dataIndex: 'del', width: 130, align: 'center'},
				{header: _text('MN02241'), dataIndex: 'memo', width: 200},//MN02241 정보
				{header: _text('MN00242'), dataIndex: 'path', width: 300}
			]
		}),
		taskStatusRender: function(v){
			//console.log('ㅁㅁ'+v);
			if (v == 'complete') return '정상';
			if (v == 'queue') return '대기중';
			if (v == 'processing') return '진행중';
			if (v == 'error') return '오류';
		},
		listeners: {
			rowcontextmenu: function(self, idx, e){
				if (!self.getSelectionModel().hasSelection())
				{
					var r = self.getSelectionModel().selectRow(idx);
				}
				e.stopEvent();

				var menu = new Ext.menu.Menu({
					items: [{
						//!!text: '리스토어',
						hidden: true,
						text: _text('MN00051'),
						icon: '/led-icons/drive_go.png',
						handler: function(b, e) {
							var records = Ext.getCmp('media_list').getSelectionModel().getSelections();
							if ( !checkSelected( records ) ) return;

							var content_id=records[0].get('content_id');

							Ext.Ajax.request({
								url: '/store/restore.php',
								params: {
									content_id: content_id
								},
								callback: function(opts, success, response){
									if(success)
									{
										try
										{
											var r = Ext.decode(response.responseText);
											if (!r.success)
											{
												Ext.Msg.alert( _text('MN00022'), r.msg);
											}
											else
											{
												Ext.Msg.alert( _text('MN00003'), _text('MSG00094'));
											}
										}
										catch(e)
										{
											Ext.Msg.alert(e['name'], e['message']);
										}
									}
									else
									{
										Ext.Msg.alert(_text('MN00022'), response.statusText);
									}
								}
							});

						}
					},{
						//>> text: '고해상도 트랜스코딩',
						text: _text('MN00143'),
						hidden: true,
						icon: '/led-icons/doc_convert.png',
						handler: function(b, e){
							var records = Ext.getCmp('media_list').getSelectionModel().getSelections();
							if ( !checkSelected( records ) ) return;

							var content_id=records[0].get('content_id');

							Ext.Ajax.request({
								url: '/store/transcoding_hi_regist.php',
								params: {
									content_id: content_id
								},
								callback: function(opts, success, response){
									if(success)
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
												//>.Ext.Msg.alert('확인', '작업이 등록되었습니다.');
												Ext.Msg.alert(_text('MN00024'), _text('MSG00037'));
											}
										}
										catch(e)
										{
											Ext.Msg.alert(e['name'], e['message']);
										}
									}
									else
									{
										Ext.Msg.alert(_text('MN00022'), response.statusText);
									}
								}
							});
						}

					}

						/*{
						icon: '/led-icons/disk.png',
						//>>text: '다운로드',
						text: _text('MN00050'),

						handler: function(b, e) {
							var records = Ext.getCmp('media_list').getSelectionModel().getSelections();
							if ( !checkSelected( records ) ) return;

							doDownloadMediaSelectedItems( $content_id, records );
						}
					}*/]
				});
				menu.showAt(e.getXY());
			},
			viewready: function(self){
				self.getStore().load({
					params: {
						content_id: $content_id
					}
				});
			}
		}
	}
EOD;
}

?>
