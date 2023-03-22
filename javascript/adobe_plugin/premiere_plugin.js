
/* 등록 시 데이터 저장할 변수 */
var premere_regist_dat = "";

/* 등록 시 팝업 창 클로벌 변수 */
var register_sequence_form_win_global = "";

/* 등록 팝업창에 사용되는 함수 */
function main_getRootPathArray() {
	Ext.Ajax.request({
		url: '/store/get_task_rootpath.php',
		callback: function (self, success, response) {
			if (success) {
				try {
					var r = Ext.decode(response.responseText);

					if (r.success) {
						root_path = r.data;
						// console.log(root_path);
					}
				} catch (e) {
				}
			} else {

			}
		}
	});
}

function main_getMPathArray() {
	Ext.Ajax.request({
		url: '/store/get_category_path.php',
		callback: function (self, success, response) {
			if (success) {
				try {
					var r = Ext.decode(response.responseText);

					if (r.success) {
						prog_path = r.data;
						// console.log(prog_path);
					}
				} catch (e) {

				}
			} else {

			}
		}
	});
}

function main_getFormData() {
	var metaTab = Ext.getCmp('regist_form_tabpanel_premiere_win');
	var length = metaTab.items.length;
	var arrMeta = [];
	var curTab = metaTab.activeTab;
	for (var i = 0; i < length; ++i) {
		metaTab.setActiveTab(i);
		var p = metaTab.items.items[i].getForm().getValues();

		if (i == 0 && Ext.getCmp('category') != null) {
			var tn = Ext.getCmp('category').treePanel.getSelectionModel().getSelectedNode();
			p.c_category_id = tn.attributes.id;
		}

		arrMeta.push(p);
	}

	//TC정보 그리드 스토어의 xml 등록
	var tc_grid = Ext.getCmp('list<?=$meta_field_id?>');

	if (!Ext.isEmpty(tc_grid)) {

		var tmp = new Array();

		tc_grid.getStore().each(function (i) {
			tmp.push(i.data);
		});
		arrMeta.push({ multi: tmp });
	}

	metaTab.setActiveTab(0);

	return arrMeta;
}

/* 등록시 팝업창 */
function register_sequence_form(path, title) {

	// 이미 생성시 return
	if (register_sequence_form_win_global) {
		return;
	}

	var register_sequence_form_win = new Ext.Window({
		title: _text('MN02501'), //프리미어 플러그인 - 등록
		width: 500,
		modal: true,
		height: 350,
		layout: 'border',
		defaults: {
			split: true,
			autoScroll: true
		},
		listeners: {
			afterrender: function (self) {
			},
			render: function (self) {
				main_getRootPathArray();
				main_getMPathArray();
			},
			close: function (self) {
				register_sequence_form_win_global = "";
			}
		},
		buttons: [{
			text: '<span style="postion:relative;top:1px;font-family:\'나눔고딕\' !important;"><i class="fa fa-close">&nbsp;' + _text('MN00031') + '</i></span>',//닫기
			scale: 'medium',
			handler: function (b, e) {
				register_sequence_form_win.close();
				register_sequence_form_win_global = "";
			}
		}, {
			text: '<span style="postion:relative;top:1px;font-family:\'나눔고딕\' !important;"><i class="fa fa-sign-in" aria-hidden="true">&nbsp;' + _text('MN02502') + '</i></span>',//시퀀스로 등록
			scale: 'medium',
			handler: function (b, e) {
				if (register_sequence_form_win.get(0).get(0).get(0).getForm().isValid()) {
					register_sequence_form_win.el.mask();
					var metadata = [];
					var metadata = main_getFormData();

					var returnValue = {
						user_id: Ext.isPremiereUserid,
						//type: regist_type,
						//todas: todas,
						flag: '',
						metadata_type: 'id',
						metadata: metadata,
						path: path
					};

					// AJAX으로 content_id get 하기
					var ret = Ext.encode(returnValue);

					//CONTENT_ID 를 가져 써야함!!!
					premere_regist_dat = ret;
					// Export 함수 호출
					get_propj_info('pproj_save');
				}
			}
		}, {
			text: '<span style="postion:relative;top:1px;font-family:\'나눔고딕\' !important;"><i class="fa fa-sign-in" aria-hidden="true">&nbsp;' + _text('MN02503') + '</i></span>', //편집영상으로 등록
			scale: 'medium',
			handler: function (b, e) {
				if (register_sequence_form_win.get(0).get(0).get(0).getForm().isValid()) {
					register_sequence_form_win.el.mask();
					var metadata = [];
					var metadata = main_getFormData();

					var returnValue = {
						user_id: Ext.isPremiereUserid,
						//type: regist_type,
						//todas: todas,
						flag: '',
						metadata_type: 'id',
						metadata: metadata,
						path: path
					};

					// AJAX으로 content_id get 하기
					var ret = Ext.encode(returnValue);

					//CONTENT_ID 를 가져 써야함!!!
					premere_regist_dat = ret;
					// Export 함수 호출
					get_propj_info('render');
				}
			}
		}
		],
		items: [{
			region: 'center',
			xtype: 'panel',
			layout: 'fit',
			width: '100%',
			bodyStyle: { "background-color": "#fff" },
			border: false,
			id: 'regist_form_tab',
			defaults: { autoHeight: true },
			frame: false,
			tbar: [{
				xtype: 'displayfield',
				width: 15
			},
			{
				xtype: 'radiogroup',
				hideLabel: true,
				width: 180,
				columns: 3,
				hidden: true,
				beforeValue: 'program',
				items: [
					{ boxLabel: _text('MN00387'), name: 'is_use', inputValue: 'program', checked: true }
				],
				listeners: {
					change: function (self, checked) {
						var checkedVal = checked.getRawValue();
						if (self.beforeValue == checkedVal) return;
						Ext.Msg.show({
							title: _text('MN00023'),
							icon: Ext.Msg.INFO,
							msg: _text('MSG02502'), //입력하신 정보가 초기화 되며, 선택하신 유형으로 정보가 갱신됩니다.<br />진행하시겠습니까?
							buttons: Ext.Msg.OKCANCEL,
							fn: function (btnID, text, opt) {
								if (btnID == 'ok') {
									var tab = self.ownerCt.ownerCt;
									tab.get(0).loadFormMetaData(tab.get(0));
									self.beforeValue = checkedVal;
								} else {
									self.setValue(self.beforeValue);
								}
							}
						});
					},
				}
			}, '', {
				xtype: 'displayfield',
				width: 7
			},
			_text('MN00276'),
			{
				xtype: 'displayfield',
				width: 7
			},
			{//MN00276 content type
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
						flag: ''
					},
					fields: [
						'ud_content_title',
						'ud_content_id',
						'allowed_extension'
					]
				}),
				listeners: {
					afterrender: function (self) {
						self.getStore().load({
							callback: function (r, o, s) {
								if (s && r[0]) {
									//로드된 첫번째 항목 설정
									register_sequence_form_win.show();
									self.setValue(r[0].get('ud_content_id'));
									self.beforeValue = r[0].get('ud_content_id');

									var tab = self.ownerCt.ownerCt;
									tab.get(0).loadFormMetaData(tab.get(0));
								} else {
									Ext.Msg.alert(_text('MN00022'), _text('MSG01032'));
									register_sequence_form_win.close();
								}
							}
						});
					},
					select: function (self, record, index) {
						var selVal = record.get('ud_content_id');
						if (self.beforeValue == selVal) return;

						Ext.Msg.show({
							title: _text('MN00023'),//Information
							icon: Ext.Msg.INFO,
							//Current metadata will be cleared. And will be refresh by selected content type metadata.
							//Will you proceed?
							msg: _text('MSG02066') + '<br />' + _text('MSG02067'),
							buttons: Ext.Msg.OKCANCEL,
							fn: function (btnID, text, opt) {
								if (btnID == 'ok') {
									var tab = self.ownerCt.ownerCt;
									tab.get(0).loadFormMetaData(tab.get(0));
									self.beforeValue = selVal;
								} else {
									self.setValue(self.beforeValue);
								}
							}
						});
					}
				}
			}
			],
			items: [{
				xtype: 'tabpanel',
				id: 'regist_form_tabpanel_premiere_win',
				activeTab: 0,
				defaults: { autoHeight: true },
				frame: false,
				border: false,
				isFirst: true,
				listeners: {
					afterrender: function (self) {
						//self.loadFormMetaData(self);
					}
				},
				items: [],
				loadFormMetaData: function (self, params) {

					var tbar = self.ownerCt.getTopToolbar();
					var ud_content_tab = tbar.items.get(1).getValue().getRawValue();
					var ud_content_id = tbar.items.get(6).getValue();
					params = params || {};
					params.ud_content_tab = ud_content_tab;
					params.ud_content_id = ud_content_id;
					params.user_id = Ext.isPremiereUserid;
					params.lang = Ext.isPremierelang;
					params.title = title;

					if (Ext.isPremiereUdContent_id != '0') {
						params.ud_content_id = Ext.isPremiereUdContent_id;
						//params.ud_content_id = 2;
						tbar.hide();
					}
					//var tbar = self.ownerCt.getTopToolbar();
					//var ud_content_tab = tbar.items.get(1).getValue().getRawValue();
					//var ud_content_id = 2;
					//alert(ud_content_tab);

					var default_set_margin_height = 125;

					Ext.Ajax.request({
						url: '/plugin/regist_form/get_metadata.php',
						params: params,
						callback: function (opts, success, response) {
							if (success) {
								try {
									var r = Ext.decode(response.responseText);
									self.removeAll();
									self.add(r);
									self.doLayout();
									self.activate(0);

									if (self.isFirst) {
										self.isFirst = false;
										if (register_sequence_form_win.getHeight() < self.getHeight()) {
											var setHeight_v = self.getHeight() + default_set_margin_height;
											register_sequence_form_win.setHeight(setHeight_v);
										}
									}
									else {
										var setHeight_v = self.getHeight() + default_set_margin_height;
										register_sequence_form_win.setHeight(setHeight_v);
									}
								}
								catch (e) {
									Ext.Msg.alert(e['name'], e['message']);
								}
							}
							else {
								Ext.Msg.alert(_text('MN00022'), opts.url + '<br />' + response.statusText + '(' + response.status + ')');
							}
						}
					});
				}
			}]
		}]
	});

	register_sequence_form_win.show();
	register_sequence_form_win.hide();
	register_sequence_form_win_global = register_sequence_form_win;
}


function _register_sequence_form(path, title) {
	if (register_sequence_form_win_global) {
		return;
	}
}


/* 실제 정보로 등록하는 Ajax */
function regist_premiere_(type, path, seq_id, content_id) {
	var render_exec_url = '/plugin/premiere/put_premiere.php';

	if (type == 'fail') {
		register_sequence_form_win_global.el.unmask();
		Ext.Msg.alert(_text('MN00022'), _text('MSG02503') + "<br>" + path); //장하려는 경로가 잘 못되었습니다.
		return;
	}
	else if (type == 'render') {
		Ext.Ajax.request({
			url: render_exec_url,
			params: {
				datas: premere_regist_dat,
				path: path,
				type: type,
				ismac: Ext.isMac
			},
			method: 'post',
			callback: function (opt, success, response) {
				register_sequence_form_win_global.el.unmask();
				var res = Ext.decode(response.responseText);
				if (res.success) {
					register_sequence_form_win_global.close();
					Ext.Msg.alert(_text('MN00023'), _text('MSG00094')); //작업이 등록되었습니다
					//alert('NewsNPS등록작업이 시작되었습니다.');
				} else {
					Ext.Msg.alert(_text('MN00022'), _text('MSG02503')); //작업이 실패 
				}
			}
		});
	}
	else if (type == 'fcp_xml') {
		Ext.Ajax.request({
			url: render_exec_url,
			params: {
				datas: premere_regist_dat,
				path: path,
				type: type,
				seq_id: seq_id,
				content_id: content_id,
				ismac: Ext.isMac
			},
			method: 'post',
			callback: function (opt, success, response) {
				register_sequence_form_win_global.el.unmask();
				var res = Ext.decode(response.responseText);
				if (res.success) {
					register_sequence_form_win_global.close();
					//alert('등록이 완료되었습니다.');
					Ext.Msg.alert(_text('MN00023'), _text('MSG00019')); //등록 요청이 완료되었습니다.
				} else {
					Ext.Msg.alert(_text('MN00022'), _text('MSG00127')); //등록에 실패하였습니다.
				}
			}
		});
	}
}


var premiere_evt_pop = "";

function processing_popup(type, msg) {
	if (type == 'hide') {
		if (premiere_evt_pop) {
			premiere_evt_pop.hide();
		}
	}
	else {
		msg = _text('MSG02504');
		premiere_evt_pop = Ext.MessageBox.show({
			title: 'Please wait',
			msg: msg,
			progressText: _text('MSG01018'),
			width: 300,
			progress: true,
			closable: false
		});
	}
}

//premiere CMS CONTENT DRAG Event flag
var content_drag_flag = false;

function premiere_evt_end(evt) {
	content_drag_flag = false;
}

function get_drag_flag() {
	return content_drag_flag;
}

/**
	Context 메뉴- 로 해당 시퀀스만 로드할 수 있도록 함.
*/

function _premiere_open_project_sequece() {
	var content_tab = Ext.getCmp('tab_warp');
	var active_tab = content_tab.getActiveTab();
	var content_grid = active_tab.get(0);
	var sel = content_grid.getSelectionModel().getSelected();

	if (sel) {
		var ori_path = sel.get('premiere_media_path');
		var lowres_root = sel.get('lowres_root');

		ori_path = ori_path.replace(/\\/gi, "/");

		if (Ext.isMac) {
			ori_path = 'Volumes/Storage/lowres' + ori_path;
		}
		else if (ori_path.indexOf(lowres_root) < 0) {
			ori_path = lowres_root + "/" + ori_path;
		}

		//alert(ori_path);
		//return;
		var seq_id = sel.get('seq_id');
		top.premiere_open_project_sequece(ori_path, seq_id);
	}
	else {
		Ext.Msg.alert(_text('MN01039'), _text('MSG00066'));
	}
}

function _premiere_open_create_a_sequece() {
	var content_tab = Ext.getCmp('tab_warp');
	var active_tab = content_tab.getActiveTab();
	var content_grid = active_tab.get(0);
	var sel = content_grid.getSelectionModel().getSelected();

	var deferreds = [];

	if (sel) {
		var ori_path = sel.get('ori_path');
		var seq_id = sel.get('seq_id');
		var content_id = sel.get('content_id');

		var children = sel.get('children');
		var ori_path = sel.get('ori_path');
		var highres_path = sel.get('highres_path');
		var title = sel.get('title');

		/* TEST
		deferreds.push(highres_path+'/'+'2016/08/24/101169/101169.MXF');
	  deferreds.push(highres_path+'/'+'2016/08/24/101170/101170.MXF');

		window.top.premiere_open_create_a_sequece('','',title,deferreds);
		return ;
		*/

		if (ori_path) {
			//만약 그룹인경우
			if (children) {

				Ext.each(children, function (item) {
					var child_ori_path = item['original_file'];
					deferreds.push(highres_path + '/' + child_ori_path);
					i++;
				});

				window.top.premiere_open_create_a_sequece('', '', title, deferreds);
				return;

			} else {

				if (!Ext.isMac) {
					deferreds.push(highres_path + '/' + ori_path);
					window.top.premiere_open_create_a_sequece('', '', title, deferreds);

				} else {
					deferreds.push(highres_path + '/' + ori_path);
					window.top.premiere_open_create_a_sequece('', '', title, deferreds);
				}

				return;
			}

		} else {
			Ext.Msg.alert(_text('MN01039'), _text('MSG00031'));		 //원본 파일이 없습니다.
		}
	}
	else {
		Ext.Msg.alert(_text('MN01039'), _text('MSG00066'));
	}
}

function premiere_evt(evt, content_id, type, d_prefix) {
	content_drag_flag = true;

	var deferreds = [];
	var i = 0;

	if (type != 1) {
		var content_tab = Ext.getCmp('tab_warp');
		var active_tab = content_tab.getActiveTab();
		var content_grid = active_tab.get(0);
		var sel = content_grid.getSelectionModel().getSelected();

		var children = sel.get('children');
		var ori_path = sel.get('ori_path');
		var highres_path = sel.get('highres_path');
		var lowres_root = sel.get('lowres_root');

		var mac_highres_path = sel.get('highres_mac_path');
		//alert(mac_highres_path);

		var ud_content_id = sel.get('ud_content_id');

		var premiere_media_path = sel.get('premiere_media_path');

		if (ori_path) {
			//만약 그룹인경우
			if (children) {

				Ext.each(children, function (item) {

					var child_ori_path = item['original_file'];
					if (Ext.isMac) {
						deferreds.push(mac_highres_path + '/' + child_ori_path);
					} else {
						deferreds.push(highres_path + '/' + child_ori_path);
					}

					//alert(i+":"+deferreds[i]);
					i++;
				});

				window.top.dragHandler(evt, deferreds);
				return;

			} else {

				if (Ext.isMac) {
					deferreds.push(mac_highres_path + '/' + ori_path);
					window.top.dragHandler(evt, deferreds);
					return;
				} else {
					deferreds.push(highres_path + '/' + ori_path);
					window.top.dragHandler(evt, deferreds);
					return;
				}


			}

		} else if (premiere_media_path) {
			//alert(premiere_media_path);
			premiere_media_path = premiere_media_path.replace(/\\/gi, "/");

			if (Ext.isMac) {
				premiere_media_path = 'Volumes/Storage/lowres' + premiere_media_path;
			}
			else if (premiere_media_path.indexOf(lowres_root) < 0) {
				premiere_media_path = lowres_root + "/" + premiere_media_path;
			}

			deferreds.push(premiere_media_path);
			window.top.dragHandler(evt, deferreds);
			return;
		}

	} else {

		deferreds.push(content_id);
		window.top.dragHandler(evt, deferreds);
		return;
	}
}



function get_propj_info(type) {

	var params = {};
	params.type = type;

	if (Ext.isMac) {
		params.ismac = 1;
	}

	if (!type) {
		Ext.Msg.alert(_text('MN00022'), _text('MSG00127')); //등록에 실패하였습니다.
		return;
	}

	Ext.Ajax.request({
		url: '/plugin/premiere/gets.php',
		params: params,
		callback: function (self, success, response) {
			if (success) {
				try {
					var r = Ext.decode(response.responseText);

					if (r.success) {
						var save_path = r.save_path;
						var save_content_id = r.content_id;
						if (type == 'render') {
							top.export_render_before(save_path, save_content_id);
						} else if (type == 'pproj_save') {
							top.export_ftp_xml_before(save_path, save_content_id);
						}
					}
				} catch (e) {

				}
			} else {

			}
		}
	});
}





/* Body Event Add */
/* CEP script 호출 하도록 되어있음  없을시 Drag n Drop 이 되지 않음*/
document.body.addEventListener('dragover', function (event) { top.allowDrop(event); }, false);
document.body.addEventListener('drop', function (event) { top.drop(event); }, false);
