// 대출기간을 자동 일주일로 설정
(function (){
//var mugu = "<p style='margin-bottom:10px;'><font style='font:10pt 굴림;color:red;line-height:15px;'><b>본 콘텐츠는 저작권법 및 기타 관련 법률에 의해 보호되며 저작권자의 허가 또는 동의 없이 임의 사용, 배포, 전송을 할 경우 사용자가 법적 책임을 질 수 있습니다.</b></font></p>";

var mugu = "<p style='margin-bottom:10px;'><font style='font:10pt 굴림;color:red;line-height:15px;'><b>본 콘텐츠는 저작권 자료로서 저작권자의 허가 또는 동의 없이 임의로 사용하거나 배포, 전송하는 경우 이에 대한 모든 책임은 사용자에게 있습니다.</b></font></p>";


    var window_width = 600;
    var window_height = 240;
    var mode = 'loan';
	var download_btn_flag = true;

    var default_loan_day = 7;


    
        var copyright_flag = false;
    

    var res_combo_data = [
	  ['(원본)','0','XDCAM 422 QuickTime Movie']
	  ,['홈초이스 HD','4', 'MPEG-PS 1920x1080 15M' ]
	  ,['홈초이스 SD','5', 'MPEG-PS 720x480 8M' ]
	  ,['POOQ','6', 'H264 1280x720 2M']
	  ,['WMV','7', 'WMV 720x400 1M']
	  ,['EBS미디어_DVD','8', 'MPG 720x480 5M']
	  ,['EBS미디어_교재','9', 'MPG 720x486 9M']
	  ,['유튜브_HD','10', 'MPEG4 720x400 1M']
	  ,['유튜브_SD','11', 'MPEG4 640x480 1M']
	  ,['시공미디어 HD','12', 'MPEG-PS 1920x1080 25M']
];



    var copyright_combo_data = [
                                ['재사용','0'],
                                ['단순참고','1'],
                                ['기타','2']
                            ];

	var loan_combo_data = [
							['선택','0'],
							['제작','1'],
							['사업','2'],
							['자회사','3'],
							['기타','4']
						  ];

    //var width = 640;



    function copyright_loan_form_panel()
    {
        if(copyright_flag)
        return {
                  xtype:'fieldset',
                  columnWidth: 0.5,
                  title: "<font style='11pt 굴림'><b>저작권 콘텐츠</b></font>",
                  layout:'column',
                  collapsible: false,
                  width: 574,
                  autoHeight: true,
                  labelWidth: 150,
                  items: [

                             {
                                xtype:'panel',
                                anchor:'95%',
                                //padding : '10px',
                                margins :{
                                     bottom: '10px'
                                },
                                html : mugu
                             }
                        ]
                }

        else
            return {};
    }

    function copyright_download_form_panel()
    {
        if(copyright_flag)
        return {
          xtype:'fieldset',
          columnWidth: 0.5,
          title: "<font style='11pt 굴림'><b>저작권 콘텐츠</b></font>",
          layout:'column',
          collapsible: false,
          width: 574,
          height: 210,
          labelWidth: 150,
          items: [
                     {
                        xtype:'panel',
                        anchor:'95%',
                        //padding : '10px',
                        margins :{
                             bottom: '10px'
                        },
                        html : mugu
                     },
                     {
                        xtype:'form',
                        id : 'copyright_info',
                        width: '95%',
                        labelWidth: 70,
                        fieldLabel: '사용목적',
                        defaults: {
                                labelStyle: 'text-align:center;'
                             },
                        items :[
                                 {
                                    xtype: 'compositefield',
                                    fieldLabel: '사용목적',
                                    id : 'content_copyright',
                                    items :
                                    [
                                        {
                                            id: 'content_purpose',
                                            width : 100,
                                            xtype: 'combo',
                                            triggerAction: 'all',
                                            typeAhead: true,
                                            editable: false,
                                            mode: 'local',
                                            disabled : false,
                                            store: new Ext.data.SimpleStore({
                                                  fields : ['c_name', 'c_value'],
                                                  data : copyright_combo_data
                                            }),
                                            displayField:'c_name',
                                            valueField:'c_value',
                                            value: 0,
                                            listeners: {
                                                select: function(self,e)
                                                {
                                                    if(self.value == 2)
                                                    {
                                                        Ext.getCmp('etc_purpose').show(true);
                                                    }
                                                    else
                                                    {
                                                        Ext.getCmp('etc_purpose').hide(true);
                                                    }

                                                    Ext.getCmp('content_copyright').innerCt.doLayout();
                                                }
                                            }
                                        }
                                        ,
                                        {
                                            width : 10
                                        },
                                        {
                                            id : 'etc_purpose',
                                            xtype:'textfield',
                                            width: 310,
                                            readOnly : false,
                                            hidden :true,
                                            fieldLabel: '목적',
                                            value : ''
                                        }
                                    ]
                                 }
                                 ,
                                 {
                                            id: 'content_agreement',
                                            width : 430,
                                            height : 70,
                                            xtype: 'textarea',
                                            fieldLabel: '저작권<br>합의내용',
                                            readOnly : false,
                                            value : ''
                                }
                        ]

                     }

                     , {
                        xtype:'panel',
                        anchor:'95%',
                        height: 70,
                        padding : '10px',
                        html :'<div align=center>&nbsp; &nbsp; &nbsp; &nbsp; &nbsp;저작권 경고문을 확인하였고 사용 목적을 저작권자의 허가를 받았으며 합의 내용을 입력하였습니다.'
                     }

               ]
            }
        else
        return {};
    }

//	function down_load_win_dddddd(content_id)
//	{
//	//	alert(content_id);
//
//			Ext.getCmp('loan_request').close();
//			Ext.Ajax.request({
//				url: '/store/loan_request_exec.php',
//				params: {
//					action : 'download',
//					records : content_id
//				},
//				callback: function(opt, success, response)
//				{
//					var r = Ext.decode(response.responseText);
//					var msg = r.msg;
//
//					if(r.result === 'true')
//					{
//						Ext.Msg.alert(' 알림 ' , '신청 완료 되었습니다.');
//						btn.ownerCt.ownerCt.destroy();
//					}
//					else
//					{
//						Ext.Msg.alert(' 오류 ' ,msg);
//					}
//
//				}
//			});
//
//		//console.log(Ext.getCmp('loan_request'));
//	}

    var download_reqeust_form_panel = new Ext.Panel({

        border: false,
        autoScroll: true,
        id: 'download_reqeust_form_panel',
        height: 500,
        width: 600,
        frame: true,

        autoScroll: true,
        items: [
            copyright_download_form_panel(),
            {
                xtype:'form',
                id: 'download_info',
                labelWidth: 100,
                height: 180,
                defaults: {
                    labelStyle: 'text-align:center;'
                },
                items:
                [
					//2012-06-18 라디오버튼 제거 -- 주석처리
					//                    {
					//                         xtype :'radiogroup',
					//                         id : 'test',
					//                         width  : 275,
					//                         disabled : false,
					//                         items :[
					//                            {
					//                                boxLabel: '고해상도',
					//                                name : 'res',
					//                                value : 'hi_res',
					//                                disabled : false,
					//                                checked : true,
					//                                inputValue : 'hi_res',
					//                                listeners :
					//                                {
					//                                    check : function (self)
					//                                    {
					//                                        if(self.checked)
					//                                        {
					//                                            Ext.getCmp('res_combo').setDisabled(false);
					//                                        }
					//                                    }
					//                                }
					//                            },
					//                            {
					//                                boxLabel: '저해상도',
					//                                name : 'res',
					//                                inputValue : 'low_res',
					//                                checked : false,
					//                                listeners :
					//                                {
					//                                    check : function (self)
					//                                    {
					//                                        self.setValue('s2df');
					//                                        if(self.checked)
					//                                        {
					//                                            Ext.getCmp('res_combo').setDisabled(true);
					//                                        }
					//                                        //console.log(Ext.getCmp('res_combo'));
					//                                    }
					//                                }
					//                            }
					//                         ]
					//                    },
                    {
                        id: 'res_combo',
                        width : 180,
                        xtype: 'combo',
                        triggerAction: 'all',
                        typeAhead: true,
                        disabled : false,
                        mode: 'local',
                        hidden: false,
						tpl: '<tpl for="."><div ext:qtip="{cc_qtip}" class="x-combo-list-item">{cc_name}</div></tpl>',
                        store: new Ext.data.SimpleStore({
                              fields : ['cc_name', 'cc_value','cc_qtip'],
                              data : res_combo_data
                        }),
                        displayField:'cc_name',
                        valueField:'cc_value',
                        value: 0,
						lastvalue : 0,
						listeners : {
							beforeselect : function ( self,  record,index )
							{
								//console.log(self.getValue());
								if(record.data.cc_value)
								{
									this.lastvalue = self.getValue();
								}
								else this.lastvalue = 0;
								//console.log(this.lastvalue);
							},
							select : function(self,record,index)
							{
								//console.log(self.lastSelectionText);
								var v = record.data.cc_value;

								if( v>1 && 4>v)   //dohoon 수정 20120725
								{
									Ext.Msg.alert('알림','서비스 준비중에 있습니다');
									self.setValue(this.lastvalue);
									return;
								}

							}
						}
                    },
                    {
                        xtype: 'compositefield',
                        fieldLabel: '사용기간',
                        items:
                        [
                            {
                                xtype: 'datefield',
                                id : 'loan_start_date',
                                width : 120,
                                format: 'Y-m-d',
                               // altFormats: 'Y-m-d H:i:s|Y-m-d|Ymd|YmdHis',
                                editable : false,
                                disabled : true,
                                //minValue :  new Date().format('Y-m-d'),
                                value :  ''
                            }
                            ,
                            {
                                xtype: 'displayfield',
                                width: 10,
                                value: '<div align=center style="padding:2px">~</div>'
                            },
                            {
                                xtype: 'datefield',
                                id : 'loan_end_date',
                                width : 120,
                                format: 'Y-m-d',
                                //altFormats: 'Y-m-d H:i:s|Y-m-d|Ymd|YmdHis',
                                editable : false,
                                disabled : true,
                                //minValue :  new Date().format('Y-m-d'),
                                value :  ''
                            }
                        ]
                    },
                    {
                        xtype:'textarea',
                        fieldLabel: '다운로드사유',
                        id : 'download_reason',
                        width : 430,
                        height: 80,
                        readOnly : false,
                        value : ''
                    },
					{
                        xtype:'displayfield',
                        fieldLabel: '<font color=red><b></b></font>',
                        labelSeparator : '',
						hidden:false,
                        width : 430,
						value : '<font color=red><b>*&nbsp;알&nbsp;&nbsp;&nbsp;림&nbsp;:&nbsp;다운로드 사유는 100자 이내로 작성하세요.</b></font>'


                    }
                ]
            }
          ]
        });

    var loan_request_form_panel = new Ext.form.FormPanel({

        border: false,
        autoScroll: true,
        id: 'loan_form_panel',
        height: 150,
        width: 300,
        frame: true,
        labelWidth: 80,
        defaults: {
            labelStyle: 'text-align:center;'
        },
        autoScroll: true,
        loadMask: true,
        items: [
            copyright_loan_form_panel(),

					{
						id: 'loan_purpose',
						width : 100,
						xtype: 'combo',
						triggerAction: 'all',
						fieldLabel : '대출용도',
						typeAhead: true,
						editable: false,
						mode: 'local',
						disabled : false,
						store: new Ext.data.SimpleStore({
							  fields : ['c_name','c_value'],
							  data : loan_combo_data
						}),
						displayField:'c_name',
						valueField:'c_value',
						value: '0',
						listeners: {
							select: function(self,e)
							{
								//console.log(self.value);
								if(self.value == 1 || self.value == 3)
								{
									Ext.getCmp('loan_reason').setDisabled(true);
								}
								else
								{
									Ext.getCmp('loan_reason').setDisabled(false);
								}

								//Ext.getCmp('loan_reason').innerCt.doLayout();
							}
						}
					},
                   /*{
                        xtype: 'compositefield',
                        fieldLabel: '대출기간',
                        items:
                        [
                            {
                                xtype: 'datefield',
                                id : 'loan_start_date',
                                width : 120,
                                format: 'Y-m-d',
                                altFormats: 'Y-m-d H:i:s|Y-m-d|Ymd|YmdHis',
                                editable : true,
                                disabled : true,
                                minValue :  new Date().format('Y-m-d'),
                                value :  new Date().format('Y-m-d')
                            }
                            ,
                            {
                                xtype: 'displayfield',
                                width: 10,
                                value: '<div align=center style="padding:2px">~</div>'
                            },
                            {
                                xtype: 'datefield',
                                id : 'loan_end_date',
                                width : 120,
                                format: 'Y-m-d',
                                altFormats: 'Y-m-d H:i:s|Y-m-d|Ymd|YmdHis',
                                editable : true,
                                disabled : true,
                                minValue :  new Date().format('Y-m-d'),
                                value :  new Date().add(Date.DAY, default_loan_day).format('Y-m-d')
                            }
                        ]
                   },*/


                    {
                        xtype:'textarea',
                        fieldLabel: '대출사유',
                        id : 'loan_reason',
                        width : 430,
                        readOnly : false,
						value : '',
                        height: 80
                    },
					{
                        xtype:'displayfield',
                        fieldLabel: '<font color=red><b></b></font>',
                        labelSeparator : '',
						hidden:false,
                        width : 430,
						value : '<font color=red><b>*&nbsp;알&nbsp;&nbsp;&nbsp;림&nbsp;:&nbsp;대출 사유는 100자 이내로 작성하세요.</b></font>'
                    }
               ]
        });


    return new Ext.Window({

            title: '대출신청',
			id:'loan_request',
			content_id : '23473238',
			req_no : '',
            layout: 'fit',
            height: window_height,
            width: window_width,
            modal: true,
            items : [
                loan_request_form_panel
            ],
			listeners :
			{
				afterrender : function(self)
				{
					self.setHeight(window_height);
				}
			},

            buttons:[
                        {
                            text: ' 신 청 ',
                            hidden : false,
                            handler: function(btn,e)
                            {

                                var reason_val  = Ext.getCmp('download_reason').getValue();
                                var reason_val2  = Ext.getCmp('loan_reason').getValue();

								var loan_purpose = Ext.getCmp('loan_purpose').getValue();

                                var purpose_sel = 0;
                                var content_val = 1;

								var reason_len;
								if(mode == 'download')
								{
									reason_len = Ext.getCmp('download_reason').getValue().length;
								}
								else if(mode == 'loan')
								{
									reason_len = Ext.getCmp('loan_reason').getValue().length;
								}


                                if(copyright_flag && mode =='download')
                                {
                                    if(Ext.getCmp('content_purpose'))
                                    {
                                        var purpose_sel = Ext.getCmp('content_purpose').getValue();
                                    }

                                    if(Ext.getCmp('content_agreement'))
                                    {

                                        var content_val = Ext.getCmp('content_agreement').getValue();
                                    }

                                    if(purpose_sel == 2)
                                    {
                                        if(!Ext.getCmp('etc_purpose').getValue())
                                        {
                                                Ext.Msg.alert('오류','저작권 기타 내용을 입력하세요.',function(){
                                            Ext.getCmp('etc_purpose').focus();});

                                            return;
                                        }
                                    }

                                    if(!content_val)
                                    {

                                        //console.log( Ext.getCmp('content_agreement'));

                                        Ext.Msg.alert('오류','저작권 합의내용을 입력하세요.',function(){
                                            Ext.getCmp('content_agreement').focus();});

                                            return;

                                    }

                                }
								//console.log(mode);
								//console.log(loan_purpose);
								//console.log(reason_val2);

								if(mode =='loan' && loan_purpose =='0')
								{
									var msg = '대출용도를 선택하셔야 합니다.';

                                        Ext.Msg.alert('오류',msg,function(){
                                                Ext.getCmp('loan_purpose').focus();});

									return;

								}
								else if(mode =='loan' && (  loan_purpose =='2' || loan_purpose =='4') && !reason_val2)
								{

									var msg = '대출신청 사유를 입력하세요';

                                        Ext.Msg.alert('오류',msg,function(){
                                                Ext.getCmp('loan_reason').focus();});
									return;

								}
								else if(!reason_val && mode  == 'download')
								{
									 var msg = '다운로드 사유를 입력하세요';
									 Ext.Msg.alert('오류',msg,function(){
											Ext.getCmp('download_reason').focus();});

									return ;

								}
								else if(reason_len >= 100)
								{
									var msg = '사유를 100자 이내로 작성하세요.';
									 Ext.Msg.alert('오류',msg,function(){
											Ext.getCmp('download_reason').focus();});

									return ;
								}
                                else
                                {
                                    var copyright_info = null;
                                    var download_info = null;
                                    var loan_info = null;

                                    if(mode == 'download' && copyright_flag)
                                    {
                                        var copyright_info = Ext.getCmp('copyright_info').getForm().getValues();
                                    }

                                    if(mode == 'loan')
                                    {
                                        var loan_info = Ext.getCmp('loan_form_panel').getForm().getValues();


                                        if(copyright_flag)
                                        {
                                            var copyright_info = 'ok';
                                        }
                                    }

                                    if(mode == 'download')
                                    {
                                        var download_info = Ext.getCmp('download_info').getForm().getValues();
                                    }


                                    Ext.Ajax.request({
                                                url: '/store/loan_request_exec.php',
                                                params: {
                                                    records : Ext.encode(["23473238"]),
                                                    //start_date : Ext.getCmp('loan_start_date').getValue().format('Ymd'),
                                                    //end_date : Ext.getCmp('loan_end_date').getValue().format('Ymd'),
                                                    action : 'loan',
                                                    download_info : Ext.encode(download_info),
                                                    loan_info: Ext.encode(loan_info),
                                                    copyright_info : Ext.encode(copyright_info),
                                                    res_combo : Ext.getCmp('res_combo').getValue(),
													check : '',
													start : '',
													end : '',
													retscheymd: '20130225',
													parent_check : ''
                                                },
                                                callback: function(opt, success, response)
                                                {
                                                    var r = Ext.decode(response.responseText);
                                                    var msg = r.msg;

                                                    if(r.result === 'true')
                                                    {
                                                        Ext.Msg.alert(' 알림 ' , '신청 완료 되었습니다.');
                                                        btn.ownerCt.ownerCt.destroy();
                                                    }
                                                    else
                                                    {
                                                        Ext.Msg.alert(' 오류 ' ,msg);
                                                    }

                                                }
                                            });
                                }

                            }
                        },
						{
							xtype:'component',
							hidden : download_btn_flag,
							html : '<div id="flashContent"><object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" width="75" height="22" id="GeminiBadge_Downloader" align="middle"><param name="movie" value="/air_app/GeminiBadge_Downloader.swf" /><param name="quality" value="high" /><param name="bgcolor" value="#ffffff" /><param name="play" value="true" /><param name="loop" value="true" /><param name="wmode" value="Opaque" /><param name="scale" value="showall" /><param name="menu" value="true" /><param name="devicefont" value="false" /><param name="salign" value="" /><param name="allowScriptAccess" value="always" /><param name="FlashVars" value="appurl=http:///air_app/ArielDownloader.air&server_url=&media_ids=&app_ver=1.2.4" /><!--[if !IE]>--><object type="application/x-shockwave-flash" data="/air_app/GeminiBadge_Downloader.swf" width="75" height="22" ><param name="movie" value="/air_app/GeminiBadge_Downloader.swf" /><param name="quality" value="high" /><param name="bgcolor" value="#ffffff" /><param name="play" value="true" /><param name="loop" value="true" /><param name="wmode" value="window" /><param name="scale" value="showall" /><param name="menu" value="true" /><param name="devicefont" value="false" /><param name="salign" value="" /><param name="allowScriptAccess" value="always" /><param name="FlashVars" value="appurl=http:///air_app/ArielDownloader.air&server_url=&media_ids=&app_ver=1.2.4" /><!--<![endif]--><a href="http://www.adobe.com/go/getflash"><img src="http://www.adobe.com/images/shared/download_buttons/get_flash_player.gif" alt="Get Adobe Flash player" /></a><!--[if !IE]>--></object><!--<![endif]--></object></div>',
							listeners : {
								render : function(self){
									//down_load_win_dddddd(23473238);
								}
							}
						},
						/* 2012-07-07 다운로드 위 코드 추가 후 주석처리*/
						/*
						{
						 text: '다운로드',
						// scale: 'medium',
						 hidden : download_btn_flag,
						 listeners :
						 {

						 },

						 handler : function(btn,e)
						 {
							var media_id = '';
							if(!media_id)
							{
								Ext.Msg.alert('알림','해당 파일이 존재하지 않습니다');
								return;
							}

							//console.log();
							//alert('Air 다운로드 버튼이 와야한다');
							Ext.Ajax.request({
								url: '/store/GeminiBadge_Downloader.php',
								params: {
									media_id : media_id
								},
								callback : function(opt , success, response){
									downloader_html = response.responseText;

									//console.log(downloader_html);

									//return ;
									new Ext.Window({
										title : '다운로더',
										width: 180,
										padding: 10,
										frame: true,
										html :'<div align=center><h3> 다운로더를 실행합니다.<br>'+downloader_html+'</div>'
									}).show();

									btn.ownerCt.ownerCt.close();
								}
							});
						 }
						},*/

                        {
                            text: ' 닫 기 ',
                            //scale : 'medium',
                            handler: function(btn,e)
                            {
                                btn.ownerCt.ownerCt.destroy();
                            }
                        }
            ]

    }).show();

})()<?
// UTF-8 한글 체크
?>