<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
fn_checkAuthPermission($_SESSION);

$user_id = $_SESSION['user']['user_id'];
$user_lang = $_SESSION['user']['lang'];
$ud_content = $db->queryAll("select * from BC_UD_CONTENT where bs_content_id='506' order by SHOW_ORDER ");
$store = array();
foreach ($ud_content as $content ) {
	array_push($store , "[".$content['ud_content_id'].",'".$content['ud_content_title']."']");
}

$list = '['.join(',', $store).']';
$ingest_ip = $db->queryAll("
	select	c.code, c.name, c.ename
	from	BC_CODE c
			,BC_CODE_TYPE ct
	where	c.code_type_id=ct.id
	and		ct.code ='ingest_ip'
	order by c.name");
$ingest_ip_store = array();
foreach ($ingest_ip as $ip ) {
	if($user_lang == 'en'){
		array_push($ingest_ip_store , array($ip['code'], $ip['ename']));
	}else{
		array_push($ingest_ip_store , array($ip['code'], $ip['name']));	
	}
	
}
?>

(function(){
	Ext.ns('Ariel.config.Ingest');
	Ext.ns('regist_form.recordValues');
	var formWin;
	var myPageSize = 50;
	var now = new Date().format('Y-m-d');

	var _schedule_types = [
		[0, _text('MN02408')],	//repeat designated day
		[1, _text('MN02385')],	//repeat day
		[2, _text('MN02386')],		//repeat week
		[3, _text('MN02387')]	//set term
	];
	function scheduleMetaValues(tabPanel){
				
		var sm = Ext.getCmp('ingest_schedule_list').getSelectionModel();
        
			var record = sm.getSelected();
			var schedule_id = record.get('schedule_id');
			var ud_content_id = record.get('ud_content_id');
			Ext.Ajax.request({
				url: '/pages/menu/config/ingest_schedule/ingest_schedule_meta_value.php',
				params: {
					schedule_id: schedule_id,
					ud_content_id: ud_content_id
				},
				callback: function(options, success, response) {
					if (success) {
						var obj = Ext.decode(response.responseText);
						var form = tabPanel.getActiveTab().getForm();
                        form.setValues(obj);
					}
				}
			});


			
	};
	function edit() {
		var sm = Ext.getCmp('ingest_schedule_list').getSelectionModel();
		if (sm.hasSelection()) {
			var record = sm.getSelected();

			formWin = new Ariel.config.Ingest.detailWin();
			
			formWin.show(null, function(self) {
				var record = sm.getSelected();

				self.buttons[0].setText('<span style="position:relative;top:1px;"><i class="fa fa-edit" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00043'));	//edit

				self.get('insert_form').getForm().loadRecord(record);
                console.log(self.get('insert_form').getForm());
				var start_time = timecodeFormat(record.get('start_time'));
				var end_time = secondToTimecode(record.get('end_time'));
				var duration = secondToTimecode(record.get('duration'));

				self.get('insert_form').get(0).get('start_time').setValue(start_time);
				self.get('insert_form').get(0).get('end_time').setValue(end_time);
				self.get('insert_form').get(0).get('duration').setValue(duration);

				if (sm.getSelected().get('schedule_type') == 2) {
					var weeks = convertWeek(record.get('date_time'));
					self.get('insert_form').get(0).get('selectDayOfWeek').setValue(weeks);
					self.get('insert_form').get(0).get('selectDayOfWeek').show();
					self.get('insert_form').get(0).get('selectDay').hide();
				} else if (sm.getSelected().get('schedule_type') == 0) {
					var b_date = sm.getSelected().get('date_time');
					var year = b_date.substr(0,4);
					var mon = b_date.substr(4,2);
					var day = b_date.substr(6,2);
					var date_t = new Date(year, mon - 1, day);

					self.get('insert_form').get(0).get('selectDay').setValue( date_t );
					self.get('insert_form').get(0).get('selectDay').hide();
					self.get('insert_form').get(0).get('selectDayOfWeek').hide();
				} else {
					var b_date = sm.getSelected().get('date_time');
					var year = b_date.substr(0,4);
					var mon = b_date.substr(4,2);
					var day = b_date.substr(6,2);
					var date_t = new Date( year, mon - 1, day);

					self.get('insert_form').get(0).get('selectDay').setValue( date_t );
					self.get('insert_form').get(0).get('selectDay').show();
					self.get('insert_form').get(0).get('selectDayOfWeek').hide();
				}
				load_form('product', record.get('ud_content_id'), record.get('schedule_id'), 'edit');
			});
			formWin.setWidth(Ext.getBody().getViewSize().width*(80/100));
			formWin.setHeight(Ext.getBody().getViewSize().height*(80/100));
		}
	}

	function load_form(ud_content_tab, ud_content_id, schedule_id, formType) {
		schedule_id = schedule_id || null;

		Ext.Ajax.request({
			// url: '/store/get_metadata_form.php',
			url: '/interface/app/plugin/regist_form/get_metadata.php',
			params: {
				ud_content_tab: ud_content_tab,
				ud_content_id: ud_content_id,
				user_id: '<?=$user_id?>'
			},
			callback: function(opts, success, response) {
				if (success) {
					try {
						var container = Ext.getCmp('meta_field');
						var r = Ext.decode(response.responseText);

						container.schedule_id = schedule_id;

						container.removeAll();
						container.add(r);
						container.doLayout();
						container.activate(0);
						//Ext.getCmp('ud_content_tab').resumeEvents();    
						if(formType === 'edit'){
							//수정일 때만
						scheduleMetaValues(container);            
						}
						
					} catch(e) {
						Ext.Msg.alert(e['name'], e['message']);
					}
				} else {
					Ext.Msg.alert(_text('MN00022'), opts.url+'<br />'+response.statusText+'('+response.status+')');
				}
			}
		});
	}

	function convertWeek(weeks) {
		var result = [];
		weeks = weeks.split(',');

		for (var i = 0; i < 8; i++) {
			result[i] = false;
			for (var j = 0; j < 8; j++) {
				if (weeks[j] == (i+1)) {
					result[i] = true;
				}
			}
		}

		return result;
	}

	// 날짜 변환함수
	function date_format(v, meta, rec) {
		var result = v;
		switch (rec.get('schedule_type')) {
			case '1':
				result = _text('MN02388');	//everyday
				break;

			case '2':
				result = _.map(v.split(','), function(n) {
					//monday, tuesday...sunday
					var weeks = [_text('MN02389'), _text('MN02390'), _text('MN02391'), _text('MN02392'), _text('MN02393'), '<span style="color: blue">'+_text('MN02394')+'</span>', '<span style="color: red">'+_text('MN02395')+'</span>'];
					
					return weeks[n-1];
				});
				break;
		}

		return result;
	}

	function secondToTimeFormat(second) {
		return moment((second*1)-32400, 'x').format('HH:mm:ss');
	}

	function timecodeFormat(v) {
		var hour = v.substr(0, 2);
		var min = v.substr(2, 2);
		var sec = v.substr(4, 2);

		return hour + ':' + min + ':' + sec;;
	}

	function timecodeToSecond(sec) {
		sec = sec.replace(':', '');

		var hour = v.substr(0,2);
		var min = v.substr(2,2);
		var sec = v.substr(4,2);
	}

	function secondToTimecode(sec){
		var h = parseInt(sec / 3600);
		var i = parseInt((sec % 3600) / 60);
		var s = (sec % 3600) % 60;

		h = String.leftPad(h, 2, '0');
		i = String.leftPad(i, 2, '0');
		s = String.leftPad(s, 2, '0');

		return h + ':' + i + ':' + s;;
	}

	function timecodeToSecond(timecode) {
		timecode = timecode.replace(/:/g, '');

		var _hour = parseInt(timecode.substr(0,2)) * 60 * 60;
		var _min = parseInt(timecode.substr(2,2)) * 60;
		var _sec = parseInt(timecode.substr(4,2));

		return _hour + _min + _sec
	}

	function buildEndTimeToSecond(v, rec) {
		var start = timecodeToSecond(rec.start_time);
		var duration = parseInt(rec.duration);

		return start + duration;
	}

	function schedule_type_render(v) {
		return _schedule_types[v][1];
	}

	function channel_render(v) {
		if(v == '0') {
			return _text('MN02397');	//channel 1
		} else if(v == '1') {
			return _text('MN02398'); 	//channel 2
		} else if(v == '2') {
			return '3번 채널'; 	//channel 3
		} else if(v == '3') {
			return '4번 채널'; 	//channel 4
		} else {
			return v;
		}
	}

	function is_use_render(v) {
		if(v == 1) {
			return _text('MN02402');		//use
		}else if(v == 0){
			return _text('MN02403');	//not use
		}
	}

	Ariel.config.Ingest.detailWin = Ext.extend(Ext.Window, {

        initComponent: function(config){
            Ext.apply(this, config || {
				id: 'add_ingest',
				//width: 1000,
				//height: 500,
				width:Ext.getBody().getViewSize().width*(80/100),
				height:Ext.getBody().getViewSize().height*(80/100),
				layout: 'border',
				modal: true,
				resizable: false,
				title: _text('MN02404'),	//ingest schedule

				items: [{
					id: 'insert_form',
					cls: 'change_background_panel ingest_schedule_insert_form',
					region: 'west',
					width: 500,
					xtype: 'form',
					monitorValid : true,
					border: false,
					layout: {
						type: 'border',
						padding: 5
					},
					frame: true,
					padding: '5',

					items: [{
						xtype: 'fieldset',
						title: _text('MN02405'),	//schedule
						region: 'center',
						width: '40%',
						layout: 'form',
						labelWidth: 110,
						padding: '10',
						defaults: {
							anchor: '100%'
						},
						items: [{
							name: 'schedule_id',
							xtype: 'hidden'
						},{
							name: 'title',
							xtype: 'textfield',
							allowBlank : false,
							emptyText: _text('MSG02158'),	//please input title of schedule
							fieldLabel: _text('MN00249'),					//title
							listeners: {
								render: function(self) {
									self.focus(null, 500);
								}
							}
						},{
							name: 'ingest_system_ip',
							xtype: 'combo',
							allowBlank : false,
							fieldLabel: _text('MN02372'),		//ingest server
							emptyText: _text('MSG02162'),	//please select ingest server
							displayField: 'name',
							valueField: 'value',
							hiddenName: 'ingest_system_ip',
							triggerAction: 'all',
							typeAhead: true,
							editable: false,
							mode: 'local',
							value: '<?=$ingest_ip_store[0][0]?>',
							store: new Ext.data.ArrayStore({
								fields: [
									'value', 'name'
								],
								data: <?=json_encode($ingest_ip_store)?>
							})
						},{
							name: 'channel',
							xtype: 'combo',
							fieldLabel: _text('MN02299'),	//channel
							allowBlank : false,
							emptyText: _text('MSG02155'),	//please select ingest channel
							displayField: 'name',
							valueField: 'value',
							hiddenName: 'channel',
							triggerAction: 'all',
							typeAhead: true,
							editable: false,
							mode: 'local',
							value: '0',
							store: new Ext.data.ArrayStore({
								fields: [
									'value', 'name'
								],
								data: [
									['0', _text('MN02397')],	//channel 1
									['1', _text('MN02398')],	//channel 2
									['2', _text('MN06001')],	//channel 3
									['3', _text('MN06002')]	//channel 4
								]
							})
						},{
							name: 'schedule_type',
							xtype: 'combo',
							fieldLabel: _text('MN02399'),	//type of schedule
							allowBlank : false,
							emptyText: _text('MSG02156'),	//please select type of schedule
							displayField: 'name',
							valueField: 'value',
							hiddenName: 'schedule_type',
							triggerAction: 'all',
							typeAhead: true,
							editable: false,
							mode: 'local',
							value: '0',
							store: new Ext.data.ArrayStore({
								fields: [
									'value', 'name'
								],
								data: _schedule_types
							}),
							listeners: {
								select: function(self, r, idx){
									if (r.get('value') == 0) {
										formWin.get('insert_form').get(0).get('selectDay').show();
										formWin.get('insert_form').get(0).get('selectDay').setValue(now);
										formWin.get('insert_form').get(0).get('selectDayOfWeek').hide();
										formWin.get('insert_form').get(0).get('selectTerm').hide();
										formWin.get('insert_form').get(0).doLayout();
									} else if (r.get('value') == 1) {
										formWin.get('insert_form').get(0).get('selectDay').hide();
										formWin.get('insert_form').get(0).get('selectDayOfWeek').hide();
										formWin.get('insert_form').get(0).get('selectTerm').hide();
									} else if(r.get('value') == 2) {
										formWin.get('insert_form').get(0).get('selectDay').hide();
										formWin.get('insert_form').get(0).get('selectDayOfWeek').show();
										formWin.get('insert_form').get(0).get('selectTerm').hide();
										formWin.get('insert_form').get(0).doLayout();
									} else if(r.get('value') == 3) {
										formWin.get('insert_form').get(0).get('selectDay').hide();
										formWin.get('insert_form').get(0).get('selectDayOfWeek').hide();
										formWin.get('insert_form').get(0).get('selectTerm').show();
										formWin.get('insert_form').get(0).doLayout();
									} else {
										formWin.get('insert_form').get(0).get('selectDayOfWeek').hide();
										formWin.get('insert_form').get(0).get('selectDay').show();
										formWin.get('insert_form').get(0).doLayout();
									}
								}
							}
						}, {
							itemId: 'selectDayOfWeek',
							xtype: 'checkboxgroup',
							fieldLabel: _text('MN02396'),	//day of week
							hidden: true,
							items: [
								//monday, tuesday...sunday
								{boxLabel: _text('MN02389'), name: 'day_of_week', inputValue: 1},
								{boxLabel: _text('MN02390'), name: 'day_of_week', inputValue: 2},
								{boxLabel: _text('MN02391'), name: 'day_of_week', inputValue: 3},
								{boxLabel: _text('MN02392'), name: 'day_of_week', inputValue: 4},
								{boxLabel: _text('MN02393'), name: 'day_of_week', inputValue: 5},
								{boxLabel: '<span style="color: blue">'+_text('MN02394')+'</span>', name: 'day_of_week', inputValue: 6},
								{boxLabel: '<span style="color: red">'+_text('MN02395')+'</span>', name: 'day_of_week', inputValue: 7}
							]
						}, {
							itemId: 'selectDay',
							name: 'date_time2',
							xtype: 'datefield',
							format: 'Y-m-d',
							editable: false,
							value: now,
							fieldLabel: _text('MN02373')	//work date
						}, {
							itemId: 'selectTerm',
							xtype: 'compositefield',
							fieldLabel: _text('MN02409'),		//term, term of date
							hidden: true,
							items: [{
								xtype: 'datefield',
								name: 'start_date',
								format: 'Y-m-d',
								editable: false,
								flex: 1,
								value: now
							}, {
								xtype: 'datefield',
								name: 'end_date',
								format: 'Y-m-d',
								editable: false,
								flex: 1,
								value: now
							}]
						}, {
							itemId: 'start_time',
							name: 'start_time',
							xtype: 'textfield',
							enableKeyEvents: true,
							minLength: 8,
							maxLength: 8,
							twentyFour: true,
							allowBlank : false,
							invalidText : '00:00:00 ~ 23:59:59',
							emptyText: '00:00:00 ~ 23:59:59',
							regex: /(0[0-9]|1[0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9])/,
		                    plugins: [new Ext.ux.InputTextMask('99:99:99')],
							fieldLabel: _text('MN02374'),	//start time
						},{
							itemId: 'end_time',
							name: 'end_time',
							xtype: 'textfield',
							enableKeyEvents: true,
							minLength: 8,
							maxLength: 8,
							twentyFour: true,
							allowBlank : false,
							msgTarget: 'under',
							invalidText : '00:00:00 ~ 23:59:59',
							emptyText: '00:00:00 ~ 23:59:59',
							regex: /(0[0-9]|1[0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9])/,
		                    plugins: [new Ext.ux.InputTextMask('99:99:99')],
							fieldLabel: _text('MN02400')	//end time
						},{
							itemId: 'duration',
							name: 'duration',
							xtype: 'textfield',
							enableKeyEvents: true,
							minLength: 8,
							maxLength: 8,
							twentyFour: true,
							allowBlank : false,
							emptyText: '00:00:00 ~ 23:59:59',
							invalidText : '00:00:00 ~ 23:59:59',
							regex: /(0[0-9]|1[0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9])/,
		                    plugins: [new Ext.ux.InputTextMask('99:99:99')],
							fieldLabel: _text('MN02401'),	//duration
							listeners: {
								render: function(field) {
									var parent = field.ownerCt;
									var start = parent.get('start_time');
									var end = parent.get('end_time');
									var duration = parent.get('duration');

									function setDuration() {
										var _start = moment(start.getValue(), 'HH:mm:ss');
										var _end = moment(end.getValue(), 'HH:mm:ss');
										var _diff;

										if (_start.isValid() && _end.isValid()) {
											if (_start.isBefore(_end)) {
												_diff = _end.diff(_start);
												duration.setValue(moment(_diff-32400000, 'x').format('HH:mm:ss'));
											} else {
												duration.setValue('__:__:__');
											}
										}
									}

									start.on('keydown', setDuration, field, {buffer: 350});
									end.on('keydown', setDuration, field, {buffer: 350});
								},
								keydown: {
									fn: function(field) {
										var parent = field.ownerCt;
										var start = parent.get('start_time');
										var end = parent.get('end_time');
										var duration = parent.get('duration');

										if (start.isValid() && duration.isValid()) {
											_start = (moment(start.getValue(), 'HH:mm:ss').format('X')*1) + (moment(duration.getValue(), 'HH:mm:ss').format('X') * 1) + 32400;

											end.setValue(moment(_start, 'X').format('HH:mm:ss'));
										}
									},
									buffer: 350
								}
							}
						},{
							name: 'is_use',
							xtype: 'radiogroup',
							fieldLabel: _text('MN02334'),	//use yn
							columns: 2,
							items: [
								{ boxLabel: _text('MN02402'), name: 'is_use', inputValue:1 , checked: true },	//use
								{ boxLabel: _text('MN02403'), name: 'is_use', inputValue:0 }	//not use
							]
						},{
							id: 'meta',
							xtype: 'combo',
							name: 'ud_content_id',
							allowBlank : false,
							fieldLabel: _text('MN02406'),	//user defined content
							emptyText: _text('MSG02157'),	//when you select you can input metadata
							displayField: 'name',
							valueField: 'value',
							hiddenName: 'ud_content_id',
							hiddenValue: 'value',
							triggerAction: 'all',
							typeAhead: true,
							editable: false,
							mode: 'local',
							store: new Ext.data.ArrayStore({
								fields: [
									'value', 'name'
								],
								data: <?=$list?>
							}),
							listeners: {
								select: function(self, r, idx){
                                    var ud_content_tab_info = self.ownerCt.ownerCt.getForm().findField('ud_content_tab').getValue();
                                    if( !Ext.isEmpty(ud_content_tab_info) ){
                                        var ud_content_tab = ud_content_tab_info.inputValue;		
                                    }else{
                                        var ud_content_tab = 'product';
                                    }						
									var ud_content_id = Ext.getCmp('meta').getValue();

									load_form(ud_content_tab, ud_content_id);
								}
							}
						},{
							name: 'ud_content_tab',
							xtype: 'radiogroup',
							fieldLabel: '방송 구분',	//use yn
							columns: 3,
							items: [
								{ boxLabel: '뉴스', name: 'ud_content_tab', inputValue:'news' },	//not use
								{ boxLabel: '제작', name: 'ud_content_tab', inputValue:'product' , checked: true },	//use
								{ boxLabel: '디지털자료', name: 'ud_content_tab', inputValue:'telecine'}	//use
							],
							listeners: {
								change: function(self){
                                    if (!Ext.isEmpty(self.getValue())) {
                                        
                                        var ud_content_tab = self.getValue().inputValue;
                                    }else{
                                        
                                        var ud_content_tab = 'product';
                                    }
									var ud_content_id = Ext.getCmp('meta').getValue();

									load_form(ud_content_tab, ud_content_id);
								}
							}
						}]
					}],
				}, {
					id: 'meta_field',
					xtype: 'tabpanel',
					cls: 'proxima_media_tabpanel ingest_schedule_meta_field',
					region: 'center',
					activeTab: 0,
					autoScroll: true,
					title: _text('MN00164'),	//metadata
					items: [],
					listeners: {
						tabchange: function(self, tab) {
							if (self.schedule_id && tab) {
								var ud_content_id = Ext.getCmp('meta').getValue();

								Ext.Ajax.request({
									url: '/pages/menu/config/ingest_schedule/load_meta_value.php',
									params: {
										schedule_id: self.schedule_id,
										ud_content_id: ud_content_id
									},
									callback: function(options, success, response) {
										if (success) {
											var obj = Ext.decode(response.responseText);
											tab.getForm().setValues(obj);

										}
									}
								});
							}
						}
					}
				}]
            });

            Ariel.config.Ingest.detailWin.superclass.initComponent.call(this);
        },
        buttonAlign: 'center',
		buttons: [{
			itemId: 'button_ok',
			scale: 'medium',
			text : '<span style="position:relative;top:1px;"><i class="fa fa-plus" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00033'),
			handler: function() {
				var from = Ext.getCmp('insert_form').getForm();
				var val = from.getValues();


				val.values = Ext.getCmp('meta_field').getActiveTab().getForm().getValues();
				var materialData = null;

                Ext.getCmp('meta_field').getActiveTab().getForm().items.each(function(i){
                    if (i.xtype == 'checkbox' && !i.checked) {
                        i.el.dom.checked = true;
                        i.el.dom.value = '';
                    }
                    if(i.xtype == 'combo'){
                        var kval = i.id ;
                        val.values[i.name] = i.getValue();
                    }
                    if(i.xtype == 'c-tree-combo'){
                        var kval = i.id ;
                        val.values[i.name] = i.getValue();
                    }
					if(i.xtype == 'c-material-search'){
                    	materialData = i.getValues();
                	}
                });
                
				val.ud_content_id = Ext.getCmp('meta').getValue();

				if (Ext.isEmpty(Ext.getCmp('meta').getValue())) {
					//please select user defined content
					Ext.Msg.alert(_text('MN00024'), _text('MSG02157'));
					return;
				} else if (Ext.isEmpty(formWin.get('insert_form').get(0).get(1).getValue())) {
					//please input title of schedule
					Ext.Msg.alert(_text('MN00024'), _text('MSG02158'));
					return;
				} else if(  Ext.isEmpty(formWin.get('insert_form').get(0).get(2).getValue()) ) {
					//please select ingest server
					Ext.Msg.alert(_text('MN00024'), _text('MSG02162'));
					return;
				} else if(  Ext.isEmpty(formWin.get('insert_form').get(0).get(3).getValue()) ) {
					//please select ingest channel
					Ext.Msg.alert(_text('MN00024'), _text('MSG02155'));
					return;
				} else if(  Ext.isEmpty(formWin.get('insert_form').get(0).get(4).getValue()) ) {
					//please select type of schedule
					Ext.Msg.alert(_text('MN00024'), _text('MSG02159'));
					return;
				} else if(  Ext.isEmpty(formWin.get('insert_form').get(0).get('start_time').getValue()) ) {
					//please input start time
					Ext.Msg.alert(_text('MN00024'), _text('MSG02160'));
					return;
				} else if(  Ext.isEmpty(formWin.get('insert_form').get(0).get('end_time').getValue()) ) {
					//please input end time
					Ext.Msg.alert(_text('MN00024'), _text('MSG02161'));
					return;
				}

				if ( ! Ext.isEmpty(Ext.getCmp('category'))) {
					var tn = Ext.getCmp('category').treePanel.getSelectionModel().getSelectedNode();
					val.category_id =  tn.attributes.id;
				}

				var act = '';

				if( Ext.isEmpty(val.schedule_id) ) {
					act = 'add';
				} else {
					act = 'edit';
				}

				Ext.Ajax.request({
					url: '/pages/menu/config/ingest_schedule/schedule_action.php',
					params: {
						action: act,
						params:	Ext.encode(val)
					},
					callback: function(options, success, response){
						if (success) {
							try {
								var r = Ext.decode(response.responseText);
								if (r.success) {
									Ext.getCmp('ingest_schedule_list').getStore().reload();
									formWin.close();
								} else {
									Ext.Msg.alert( _text('MN00023'), r.msg);
								}
							} catch (e) {
								Ext.Msg.alert(e['name'], e['message']);
							}
						} else {
							Ext.Msg.alert( _text('MN01098'), response.statusText);//'서버 오류'
						}
					}
				});
			}
		}, {
			id:'cancel',
			scale: 'medium',
			text : '<span style="position:relative;top:1px;"><i class="fa fa-close" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00004'),
			handler: function(){
				formWin.close();
			}
		}]
	});

	Ariel.config.Ingest.Schedule = Ext.extend(Ext.Panel, {
		id: 'config_ingest_schedule',
		title: '<span class="user_span"><span class="icon_title"><i class=""></i></span><span class="main_title_header">'+_text('MN02370')+'</span></span>',
		cls: 'grid_title_customize',
		border: false,
		layout: 'fit',

		initComponent: function(config){
			Ext.apply(this, config || {});
			var _this = this;
			this.store = new Ext.data.JsonStore({
				url: '/pages/menu/config/ingest_schedule/schedule_store.php',
				remoteSort: true,
				sortInfo: {
					field: 'schedule_id',
					direction: 'DESC'
				},
				totalProperty: 'total',
				idProperty: 'schedule_id',
				root: 'data',
				fields: [
					'schedule_id',
					'ingest_system_ip',
					'ip_name',
					'ip_ename',
					'channel',
					'schedule_type',
					{name: 'date_time'},
					{name: 'date_time_week'},
					{name: 'start_time', type: 'string'},
					{name: 'duration', type: 'int'},
					{name: 'end_time', convert: buildEndTimeToSecond},
					{name: 'create_time', type: 'date', dateFormat: 'YmdHis'},
					'status',
					'category_id',
					'bs_content_id',
					'ud_content_id',
					'ud_content_tab',
					'title',
					'user_id',
					'is_use',
					'cron'
				],
				listeners: {
					exception: function(self, type, action, opts, response, args){
						try {
							var r = Ext.decode(response.responseText);
							if(!r.success) {
								Ext.Msg.alert(_text('MN00023'), r.msg);
							}
						}
						catch(e) {
							Ext.Msg.alert(_text('MN00022'), e);

						}
					}
				}
			});

			this.items = new Ext.grid.GridPanel({
				id: 'ingest_schedule_list',
				itemId: 'ingest_schedule_list',
				cls: 'proxima_customize',
				stripeRows: true,
				border: false,
				store: this.store,
				loadMask: true,
				viewConfig: {
					forceFit: true
				},
				listeners: {
					viewready: function(self){
						self.getStore().load({
							params: {
								start: 0,
								limit: myPageSize
							}
						});
					},
					rowdblclick: function() {
						edit();
					}
				},
				colModel: new Ext.grid.ColumnModel({
					defaults: {
						sortable: true
					},
					columns: [
						new Ext.grid.RowNumberer(),
						{header: _text('MN02218'), dataIndex: 'title'},
						{header: _text('MN02309'), dataIndex: 'user_id',sortable: false },
						{header: _text('MN02371'), dataIndex: 'schedule_id', hidden: true},
						<?php if($user_lang == 'en'){ ?>
							{header: _text('MN02372'), dataIndex: 'ip_ename' },
						<?php }else{?>
							{header: _text('MN02372'), dataIndex: 'ip_name' },
						<?php }?>
						{header: _text('MN02299'), dataIndex: 'channel' , renderer: channel_render,sortable: false },
						{header: _text('MN00222'), dataIndex: 'schedule_type', renderer: schedule_type_render,sortable: false },
						{header: _text('MN02373'), dataIndex: 'date_time', renderer: date_format,sortable: false},
						{header: _text('MN02374'), dataIndex: 'start_time', renderer: timecodeFormat},
						{header: _text('MN02120'), dataIndex: 'duration', renderer: secondToTimecode},
						{header: _text('MN02217'), dataIndex: 'create_time', renderer: Ext.util.Format.dateRenderer('Y-m-d')},
						{header: 'Status', dataIndex: 'status', hidden: true},
						{header: 'Category ID', dataIndex: 'category_id', hidden: true },
						{header: 'Content Type ID', dataIndex: 'bs_content_id', hidden: true },
						{header: 'User Defined Content ID', dataIndex: 'ud_content_id', hidden: true },
						{header: '사용여부', dataIndex: 'is_use' , renderer: is_use_render },
						{header: 'cron', dataIndex: 'cron', hidden: true, width: 200 }
					]
				}),
				tbar: [{
					cls: 'proxima_button_customize',
					width: 30,
					text: '<span style="position:relative;top:1px;" title="'+_text('MN00139')+'"><i class="fa fa-refresh" style="font-size:13px;color:white;"></i></span>',
					handler: function(btn, e){
						Ext.getCmp('ingest_schedule_list').getStore().load({
								params:{
									start:0,
									limit:myPageSize
								}
						});
					}
				},{
					cls: 'proxima_button_customize',
					width: 30,
					text: '<span style="position:relative;top:1px;" title="'+_text('MN00033')+'"><i class="fa fa-plus" style="font-size:13px;color:white;"></i></span>',
					handler: function(btn, e){

						formWin = new Ariel.config.Ingest.detailWin();

						formWin.show();
					},
					scope: this
				}, {
					cls: 'proxima_button_customize',
					width: 30,
					text: '<span style="position:relative;top:1px;" title="'+_text('MN00043')+'"><i class="fa fa-pencil-square-o" style="font-size:13px;color:white;"></i></span>',
					handler: function(btn, e){
     	               edit();			
					},
					scope: this
				}, {
					cls: 'proxima_button_customize',
					width: 30,
					text: '<span style="position:relative;top:1px;" title="'+_text('MN00034')+'"><i class="fa fa-ban" style="font-size:13px;color:white;"></i></span>',
					handler: function(btn, e){
						var sm = Ext.getCmp('ingest_schedule_list').getSelectionModel();
						if(sm.hasSelection()){
							var records = sm.getSelections();
							var schedules = [];

							Ext.each(records, function(record) {
								schedules.push(record.data);
							});

							Ext.Msg.show({
								icon: Ext.Msg.QUESTION,
								buttons: Ext.Msg.OKCANCEL,
								title: _text('MN00034'),
								msg: _text('MSG00172') ,
								fn: function(buttonId, text, opts){
									var p = {
										action: 'del',
										schedules: Ext.encode(schedules)
									};
									if (buttonId == 'ok') this.request(p);
								},
								scope: this
							});
						}
					},
					scope: this
				},
				'-',
				{
					xtype: 'combo',
					width:110,
					fieldLabel: '',
					itemId:'searchCombo',
					allowBlank : false,
					displayField: 'name',
					valueField: 'value',
					triggerAction: 'all',
					typeAhead: true,
					editable: false,
					mode: 'local',
					value: 'All',
					searchValue:null,
					insertItem:null,
					store: new Ext.data.ArrayStore({
						fields: [
							'value', 'name'
						],
						data: [
							['All','전체'],
							['title', '시스템 콘텐츠명'],
							['user_id', '작업자'],
							['ingest_system_ip', '인제스트 서버'],
							['channel', '채널'],
							['create_time', '등록일시']	
						]
					}),
					listeners:{
						afterrender: function(self){
							self._insertTbarSearchItem();
						},
						select: function(self, record, index){
							self._insertTbarSearchItem();
						}
					},
					_insertTbarSearchItem: function(){
						
						var grid = _this.getComponent('ingest_schedule_list');
						var gridTbar = grid.getTopToolbar();

						if(this.insertItem != null){
							gridTbar.remove(this.insertItem);
						}

						var value = this.getValue();
						var searchItems = null;

						switch(value){
							case 'title':
							case 'user_id':
								searchItems = this._makeSearchTextField();
								break;
							case 'channel':
							case 'ingest_system_ip':
								searchItems = this._makeSearchCombo();
								break;	
							case 'create_time':
								searchItems = this._makeSearchDateField();
								break;
							defalut:
								searchItems = null;
						}
						
						if(Ext.isEmpty(searchItems)){
							return false;
						}

						this.searchValue = searchItems.getValue();


						var nowIndex = gridTbar.items.indexOf(this);
						var insertIndex = nowIndex+1;

						this.insertItem = searchItems;

						gridTbar.insert(insertIndex,searchItems);
						gridTbar.doLayout();
					},
					_makeSearchTextField: function(){
						var searchCombo = this;
						var textField = new Ext.form.TextField({
							listeners:{
								change: function(self, oldValue, newValue){
									searchCombo.searchValue = self.getValue();
								},
								specialkey: function (f, e) {
									if (e.getKey() == e.ENTER) {
										var grid = _this.getComponent('ingest_schedule_list');
										var gridTbar = grid.getTopToolbar();
										var searchCombo = gridTbar.getComponent('searchCombo');
										var searchType = searchCombo.getValue();
										var searchValue = textField.getValue();

										grid.store.load({
											params: {
												start: 0,
												limit: myPageSize,
												search_type:searchType,
												search_value:searchValue
											}
										});
									}
								}
							}
						});
						return textField;
					},
					_makeSearchCombo: function(){
						var searchCombo = this;
						var value = this.getValue()
						var combo = new Ext.form.ComboBox({
							width:120,
							allowBlank : false,
							displayField: 'name',
							valueField: 'value',
							triggerAction: 'all',
							typeAhead: true,
							editable: false,
							mode: 'local',
							listeners:{
								beforerender: function(self){

									if(Ext.isEmpty(value)){
										return false;
									}
									var storeData = null;
									
									if(value == 'channel'){
										var channel = [
											['0', _text('MN02397')],	//channel 1
											['1', _text('MN02398')],	//channel 2
											['2', _text('MN06001')],	//channel 3
											['3', _text('MN06002')]	//channel 4
										];
										storeData = channel;    
									}
									if(value == 'ingest_system_ip'){
										var ingestIpStore = <?=json_encode($ingest_ip_store)?>;
										storeData = ingestIpStore;
									}
									
									var store = new Ext.data.ArrayStore({
										fields: [
											'value', 'name'
										],
										data:storeData
									});
									self.store = store;
									self.setValue(store.getAt(0).get('value'));
									searchCombo.searchValue = store.getAt(0).get('value');
								},
								change: function(self, oldValue, newValue){
									searchCombo.searchValue = self.getValue();
								}
							}
						});
						
						return combo;
					},
					_makeSearchDateField: function(){
						var dateFields = new Ext.form.CompositeField({
							width:210,
							style: {
								marginLeft: '5px'
							},
							items:[
                                {
                                    xtype:'datefield',
                                    editable: false,
                                    format: 'Y-m-d',
                                    listeners:{
                                        render: function (self) {
                                            var d = new Date();

                                            self.setMaxValue(d.format('Y-m-d'));
                                            // self.setValue(d.add(Date.DAY, -7).format('Y-m-d'));
                                            self.setValue(d.format('Y-m-d'));
                                            dateFields.value.startDate = self.getValue().format('Ymd')+'000000';
								
                                        },
                                        select: function(self,date){
                                            dateFields.value.startDate = date.format('Ymd')+'000000';
                                        }
                                    }
                                },
                                {
                                    xtype:'displayfield',
                                    value:'~'
                                },
                                {
                                    xtype:'datefield',
                                    editable: false,
                                    format: 'Y-m-d',
                                    listeners:{
                                        render: function (self) {
                                            var d = new Date();

                                            self.setMaxValue(d.format('Y-m-d'));
                                            self.setValue(d.format('Y-m-d'));
                                            dateFields.value.endDate = self.getValue().format('Ymd')+'235959';

                                        },
                                        select: function(self,date){
                                            dateFields.value.endDate = date.format('Ymd')+'235959';
                                        }
                                    }
                                }
                            ],
                            value:{
                                startDate:null,
                                endDate:null,
                            }
						});
						return dateFields;
					}
				},{
					cls: 'proxima_button_customize',
					width: 30,
					itemId:'searchButton',
					text: '<span style="position:relative;top:1px;" title="'+'검색'+'"><i class="fa fa-search" style="font-size:13px;color:white;"></i></span>',
					handler: function(btn){
						var grid = _this.getComponent('ingest_schedule_list');
						grid.search();
					}
				}],

				bbar: new Ext.PagingToolbar({
					store: this.store,
					pageSize: myPageSize
				}),
				search: function(){
					var grid = _this.getComponent('ingest_schedule_list');
					var gridTbar = grid.getTopToolbar();
					var searchCombo = gridTbar.getComponent('searchCombo');
				
					var searchType = searchCombo.getValue();
					var searchValue = searchCombo.searchValue;

					var startDate = null;
					var endDate = null;
					if(searchType == 'create_time'){
						startDate = searchCombo.searchValue.startDate;
						endDate = searchCombo.searchValue.endDate;
					}

					grid.store.load({
						params: {
							start: 0,
							limit: myPageSize,
							search_type:searchType,
							search_value:searchValue,
							start_date:startDate,
							end_date:endDate
						}
					});
				}
			});

			Ariel.config.Ingest.Schedule.superclass.initComponent.call(this);
		},
		request: function(p){
			Ext.Ajax.request({
				url: '/pages/menu/config/ingest_schedule/schedule_action.php',
				params: p,
				callback: function(self, success, response){
					try {
						var r = Ext.decode(response.responseText);

						if (r.success) {
							Ext.getCmp('ingest_schedule_list').getStore().reload();
						} else {
							Ext.Msg.alert(_text('MN00022'), r.msg);
						}
					} catch(e) {
						Ext.Msg.alert(_text('MN00022'), e);
					}
				}
			});
		}
	});

	return new Ariel.config.Ingest.Schedule();
})()