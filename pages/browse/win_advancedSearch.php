<?
	session_start();
	require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
	require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lib.php');
	require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

	$user_id = $_SESSION['user']['user_id'];
	$is_admin = $_SESSION['user']['is_admin'];
	$user_email = $_SESSION['user']['user_email'];
	$selected = $_POST['selected'];
	$ud_content_id = $_POST['ud_content_id'];

	$query = "
		SELECT	U.UD_CONTENT_ID, U.UD_CONTENT_TITLE
		FROM		BC_UD_CONTENT U
		WHERE	U.UD_CONTENT_ID IN(
				SELECT	G.UD_CONTENT_ID
				FROM		BC_GRANT G
				WHERE	G.UD_CONTENT_ID = U.UD_CONTENT_ID
				AND		MEMBER_GROUP_ID IN (".join(',', $_SESSION['user']['groups']).")
				AND		GRANT_TYPE = 'content_grant'
		)
		ORDER BY U.SHOW_ORDER ASC
	";

	$ud_content_list = $db->queryAll($query);

	$ud_list = array();
	foreach($ud_content_list as $key=>$ud_content){
		$checked =  $ud_content['ud_content_id'] == $ud_content_id ? ", checked : true " : "";
		@mb_internal_encoding("UTF-8");
		array_push($ud_list, "{boxLabel: '".$ud_content['ud_content_title']."',id: 'mf_".$ud_content['ud_content_id']."',	name: 'meta_table', inputValue: '".$ud_content['ud_content_id']."'".$checked."}");
	}
?>

(function(){
	function buildCmp(meta_table_id, container_id, item_index, emptyText){
		return {
				xtype: 'compositefield',
				hideLabel: true,
				width: 530,
				style: {
					padding: '3px'
				},
				items: [{
						xtype: 'combo',
						//>>emptyText: '검색 항목를 선택하세요.',
						emptyText: '<?=_text('MSG00135')?>',
						editable: false,
						typeAhead: true,
						flex: 1.5,
						triggerAction: 'all',
						displayField: 'name',
						valueField: 'meta_field_id',
						hiddenName: 'meta_field_id',
						hiddenValue: 'meta_field_id',
						item_index: item_index,
						store: new Ext.data.JsonStore({
								url: '/store/search/get_dynamic2.php',
								root: 'data',
								baseParams: {
										meta_table_id: meta_table_id,
										container_id: container_id,
										type: 'component'
								},
								fields: [
										'name', 'meta_field_id', 'type', 'default_value', 'table', 'field'
								]
						}),
						listeners: {
								select: function(self, r, idx) {
										var c;
										var p = self.ownerCt;

										for (; 1 != p.items.length; ) {
												p.remove( p.get(1) );
										}

										var type = r.get('type');
										var name = r.get('name');
										if (type == 'datefield') {
												p.add({
														xtype: 'datefield',
														name: 's_dt',
														altFormats: 'Ymd|ymd|Y-m-d|y-m-d|Y/m/d|Y/m/d',
														format: 'Y-m-d',
														flex: 0.9,
														listeners: {
																select: function(self, date){
																		self.ownerCt.get(3).setMinValue(self.value);
																},
																render: function(self){
																	var d = new Date();
																	self.setValue(d.add(Date.DAY, -7).format('Y-m-d'));
																}
														}
												},{
														xtype: 'displayfield',
														value: '~',
														flex: 0.2,
														style:{
															"text-align": 'center',
														}
												},{
														xtype: 'datefield',
														name: 'e_dt',
														altFormats: 'Ymd|ymd|Y-m-d|y-m-d|Y/m/d|Y/m/d',
														format: 'Y-m-d',
														flex: 0.9,
														listeners: {
															render: function(self){
																var d = new Date();
																self.setValue(d.format('Y-m-d'));
															}
														}
												});
										} else if ( name == '알파값' ) {
												p.add({
														xtype: 'combo',
														flex:2,
														displayField:'name',
														valueField: 'value',
														typeAhead: true,
														triggerAction: 'all',
														lazyRender:true,
														mode: 'local',
														store: new Ext.data.ArrayStore({
																id: 0,
																fields: [
																		'name',
																		'value'
																],
																data: [['전체', 'all'], ['O', '1'], ['X', '2']]
														})
												});
										} else if ( name == '해상도' ) {
												p.add({
														xtype:'combo',
														flex:2,
														displayField:'name',
														valueField: 'value',
														typeAhead: true,
														triggerAction: 'all',
														lazyRender:true,
														mode: 'local',
														value: 'all',
														store: new Ext.data.ArrayStore({
																id: 0,
																fields: [
																		'name',
																		'value'
																],
																data: [['전체', 'all'], ['720px 이하', 'low'], ['720 ~ 1920', 'center'], ['1920px 이상', 'high']]
														})
												});
										} else if ( type == 'checkbox') {
												var default_value_array = r.get('default_value').split(';');
												for (i = 0; i < default_value_array.length; i++){
													//p.add({
														//xtype: 'checkbox',
														//flex: 1,
														//>>emptyText: '검색 값를 선택하세요.',
														//emptyText: '<?=_text('MSG00041')?>',
														//margins: '0 0 0 2',
														//name: 'value',
														//inputValue: default_value_array[i],
														//boxLabel:default_value_array[i]
													//});
													p.add({
														xtype: 'radio',
														flex: 1,
														name: 'type_radio',
														boxLabel: default_value_array[i],
														inputValue:default_value_array[i]
													});
												}

										} else if ( type == 'combo' ) {
												p.add({
														xtype: 'combo',
														flex: 2,
														//>>emptyText: '검색 값를 선택하세요.',
														emptyText: '<?=_text('MSG00041')?>',
														editable: false,
														typeAhead: true,
														triggerAction: 'all',
														store: r.get('default_value').split(';'),
														name: 'value'
												});
										} else {
												p.add({
														xtype: 'textfield',
														allowBlank: false,
														//>> emptyText: '검색어를 입력하세요',
														emptyText: '<?=_text('MSG00007')?>',
														margins: '0 2 0 0',
														name:'value',
														flex: 2,
														enableKeyEvents: true,
														listeners: {
															keydown: function(self, e) {
																if (e.getKey() == e.ENTER) {
																	e.stopEvent();
																	var search_button = null;
																	if(meta_table_id == '4000406') {
																		search_button = Ext.getCmp('a-search-audio-button');
																	} else {
																		search_button = Ext.getCmp('a-search-media-button');
																	}

																	search_button.handler(search_button);
																}
															}
														}
												});
										}

										p.add({
												xtype: 'combo',
												emptyText: '정렬방식',
												editable: false,
												typeAhead: true,
												triggerAction: 'all',
												mode: 'local',
												displayField:'name',
												valueField: 'value',
												value: 'ASC',
												lazyRender:true,
												margins: '0 0 0 2',
												flex: 0.7,
												store: new Ext.data.ArrayStore({
													fields: ['name','value'],
													//data: [['오름차순','ASC'],['내림차순','DESC']]
													data: [[ _text('MN02174'),'ASC'],[ _text('MN02175'),'DESC']]
												})
										});

										p.add({
												xtype: 'button',
												//>>text: '리셋', MN00055
												text: '<?=_text('MN00055')?>',
												margins: '0 0 0 2',
												handler: function(self, e){
														var c = self.ownerCt;
														var cnt = c.items.length;

														if (c.get(1).xtype == 'datefield') {
																c.remove(1);
																c.remove(1);
																c.remove(1);

																c.insert(1, {
																		xtype: 'textfield',
																		//>>emptyText: '검색어를 입력하세요',
																		emptyText: '<?=_text('MSG00007')?>',
																		name:'value',
																		flex: 2
																});

																c.doLayout();
														}
														else if (c.get(1).xtype == 'radio') {
															for(i=0; i < c.items.length; i++){
																if(c.get(i).xtype == 'radio'){
																	c.get(i).setVisible(false);
																}

															}
															c.insert(1, {
																xtype: 'textfield',
																//>>emptyText: '검색어를 입력하세요',
																emptyText: '<?=_text('MSG00007')?>',
																name:'value',
																//margins: ' 0 0 2',
																flex:2
															});

														c.doLayout();
														}
														else {
																c.remove(1);

																c.insert(1, {
																		xtype: 'textfield',
																		//>>emptyText: '검색어를 입력하세요',
																		emptyText: '<?=_text('MSG00007')?>',
																		name:'value',
																		flex: 2
																});

																c.doLayout();
														}

														for (var i=0; i<cnt; i++) {
																if (c.get(i) && typeof c.get(i).reset == 'function') {
																		c.get(i).reset();
																}
														}
												}
										});

										p.add({
												xtype: 'hidden',
												name: 'table',
												value: r.get('table')
										});
										p.add({
												xtype: 'hidden',
												name: 'field',
												value: r.get('field')
										});

										p.doLayout();
								}
						}
				},{
						xtype: 'textfield',
						//>>emptyText: '검색어를 입력하세요',
						emptyText: '<?=_text('MSG00007')?>',
						name: 'value',
						flex: 2
				},{
						xtype: 'combo',
						emptyText: '정렬방식',
						editable: false,
						typeAhead: true,
						triggerAction: 'all',
						mode: 'local',
						displayField:'name',
						valueField: 'value',
						value: 'ASC',
						lazyRender:true,
						flex: 0.7,
						store: new Ext.data.ArrayStore({
							fields: ['name','value'],
							 //data: [['오름차순','ASC'],['내림차순','DESC']]
							data: [[ _text('MN02174'),'ASC'],[ _text('MN02175'),'DESC']]
						})
				},{
						xtype: 'button',
						//>>text: '리셋',
						text: '<?=_text('MN00055')?>',
						handler: function(self, e){
							var c = self.ownerCt;
							var cnt = c.items.length;

							if (c.get(1).xtype == 'datefield') {
									c.remove( c.get(1) );
									c.remove( c.get(1) );
									c.remove( c.get(1) );

									c.insert(1, {
											xtype: 'textfield',
											//>>emptyText: '검색어를 입력하세요',
											emptyText: '<?=_text('MSG00007')?>',
											name:'value',
											flex: 1
									});

									c.doLayout();
							} else {
									for (var i=0; i < cnt; i++)
									{
											if ( c.get(i) && typeof c.get(i).reset == 'function' )
											{
													c.get(i).reset();
											}
									}
							}
						}
				}]
		};
	}

	Ariel.advancedSearch = new Ext.Window({
			//11-11-11, 승수. w: 500, h: 395 가 원래값
			width: 600,
			height: 430,
			//>>title: '검색',
			title: '<?=_text('MN00037')?>',
			id: 'a-search-win',
			closeAction : 'hide',
			layout: 'fit',
			//baseCls:'proxima25-window',
			x: <?=$_POST['x_pos']?>,
			y: <?=$_POST['y_pos']?>,
			border : false,
			items: [{
				id: 'form_value',
				xtype: 'form',
				layout: 'form',
				bodyStyle:{"background-color":"white"},
				buttonAlign: 'center',
				padding: 10,
				itemNumber: 5,
				border : false,
				items:[{
					xtype: 'fieldset',
					//>>title: '콘텐츠 유형',MN00276
					title: _text('MN00276'),
					items: [{
						id: 'a-search-meta-table',
						xtype: 'radiogroup',
						hideLabel: true,
						columns: [180, 180, 180],
						vertical: true,
						items: [
						<?php
							if( $ud_list > 0 ){
								echo join(',', $ud_list);
							}
						?>
						],
						listeners: {
							render: function (self) {
									Ext.getCmp('a-search-field').removeAll();
							},
							afterrender : function(self){
							},
							change: function (self, checked) {
								var search_grid_tab = Ext.getCmp('tab_warp');
								//search_grid_tab.setActiveTab(Ext.getCmp('a-search-meta-table').getValue().getRawValue());

								Ext.getCmp('a-search-field').removeAll();
								var d=Ext.getCmp('a-search-meta-table').getValue();
								var meta_table = Ext.getCmp('form_value').getForm().getValues().meta_table;

								Ext.Ajax.request({
									url: '/store/search/get_dynamic2.php',
									params: {
											meta_table_id: Ext.getCmp('a-search-meta-table').getValue().getRawValue(),
											type: 'container'
									},
									callback: function(opt, success, response){
										if(success) {
											var result = Ext.decode(response.responseText);
											if(result.success) {
												Ext.getCmp('a-search-field').add({
													xtype: 'tabpanel',
													id: 'a-search-field-tab',
													activeTab: 0,
													border:false
												});
												for(var i=0; i < result.total; i++) {

													Ext.getCmp('a-search-field-tab').add({
															title: result.data[i].name,
															id: 'a-search-field-tab-' + result.data[i].container_id,
															ud_content_id: result.data[i].container_id,
															border:false,
															items: []
													});
													for (var j=0; j < Ext.getCmp('form_value').itemNumber; j++) {
															Ext.getCmp('a-search-field-tab-' + result.data[i].container_id).add(buildCmp(Ext.getCmp('a-search-meta-table').getValue().getRawValue(), result.data[i].container_id), i );
													}
												}

												Ext.getCmp('a-search-field').doLayout();

											}
										}
									}
								});

								Ext.getCmp('a-search-field').doLayout();
							}
						}
					}]
				},{
						xtype: 'fieldset',
						title: _text('MN00113'),
						height: 220,
						items: [{
								id: 'a-search-field',
								items: [],
								listeners: {
										beforeremove: function(self, cmp){
										},
										add: function (self, cmp, idx) {
												if (idx != 0) {

												}
										}
								}
						}]
				}],
				buttons: [{
					text : '<span style="position:relative;top:1px;"><i class="fa fa-search" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00037'),//검색
					id: 'a-search-media-button',
					scale: 'medium',
					handler: function(b, e){
						//상세검색일 시 통합검색 초기화
						Ext.getCmp('search_input').setValue('');
						var f = Ext.getCmp('a-search-meta-table');

						var rs = [];
						for (var i=0; i < Ext.getCmp('a-search-field-tab').getActiveTab().items.length; i++) {

							var c = Ext.getCmp('a-search-field-tab').getActiveTab().items.items[i].innerCt;
							if (Ext.isEmpty(c) || Ext.isEmpty(c.get(0).getValue()))	{
								continue;
							}

							var table = '';
							var field = '';
							var s = c.get(0).getStore();
							var order_type = '', search_type = '';

							var field_type = s.getAt(s.find('meta_field_id', c.get(0).getValue())).get('type');

							if (field_type == 'datefield')	{
								var s_dt='', e_dt='';

								table = c.get(7) ? c.get(7).getValue() : '';
								field = c.get(8) ? c.get(8).getValue() : '';

							   // search_type = c.get(4) ? c.get(4).getValue() : '';
								order_type = c.get(4) ? c.get(4).getValue() : '';



								if (Ext.isEmpty(c.get(1).getValue()))	{
									e_dt = c.get(3).getValue().format('Ymd999999');
								}
								else {
									s_dt = c.get(1).getValue().format('Ymd000000');
								}

								if (Ext.isEmpty(c.get(3).getValue()))	{
									s_dt = c.get(1).getValue().format('Ymd000000');
								}
								else {
									e_dt = c.get(3).getValue().format('Ymd999999');
								}

								if( Ext.isEmpty(s_dt) && Ext.isEmpty(e_dt) ) {
									Ext.Msg.alert(_text('MN00023'), '검색기간을 설정해주세요');
									return;
								}


								rs.push({
									type: field_type,
									meta_field_id: c.get(0).getValue(),
									s_dt: s_dt,
									e_dt: e_dt,
									table: table,
									field: field,
																  //  search_type: search_type,
																	order_type: order_type
								});
							}else if(field_type == 'checkbox'){
								table = c.get(5) ? c.get(5).getValue() : '';
								field = c.get(6) ? c.get(6).getValue() : '';

							   // search_type = c.get(2) ? c.get(2).getValue() : '';
								order_type = c.get(3) ? c.get(3).getValue() : '';

								if( !c.get(1).getValue() && !c.get(2).getValue()) {
										Ext.Msg.alert(_text('MN00023'), '검색어를 입력해주세요');
										return;
								}

								var value_of_checkbox;

								if(c.get(1).getValue()){
									value_of_checkbox = c.get(1).inputValue;
								}
								if(c.get(2).getValue()){
									value_of_checkbox = c.get(2).inputValue;
								}

								rs.push({
									type: field_type,
									meta_field_id: c.get(0).getValue(),
									value: value_of_checkbox,
									table: table,
									field: field,
									order_type: order_type
								});

							}else{
								table = c.get(5) ? c.get(5).getValue() : '';
								field = c.get(6) ? c.get(6).getValue() : '';

							   // search_type = c.get(2) ? c.get(2).getValue() : '';
								order_type = c.get(2) ? c.get(2).getValue() : '';
								if( Ext.isEmpty(c.get(1).getValue()) ) {
										Ext.Msg.alert(_text('MN00023'), '검색어를 입력해주세요');
										return;
								}
								rs.push({
									type: field_type,
									meta_field_id: c.get(0).getValue(),
									value: c.get(1).getValue(),
									table: table,
									field: field,
																   // search_type: search_type,
																	order_type: order_type
								});
							}
						}

						var result = {
							meta_table_id: f.getValue().getRawValue(),
							fields: rs
						};

						var tab;

						if( Ext.isEmpty(rs) ) {
							//Ext.Msg.alert(_text('MN00023'), '검색 항목과 검색어를 입력해주세요');
                            //MSG00007 검색어를 입력해주세요
                            Ext.Msg.alert(_text('MN00023'), _text('MSG00007'));
							return;
						}

						var args = {
							action: 'a_search',
							params: Ext.encode(result)
						};

						var category = Ext.getCmp('menu-tree').getSelectionModel().getSelectedNode();

						if(!Ext.isEmpty(category)) {
							args.category_full_path =	category.getPath();
						}

						Ext.getCmp('tab_warp').setActiveTab(f.getValue().getRawValue());
											Ariel.advancedSearch.hide();
						(function(){
							Ext.getCmp('tab_warp').getActiveTab().reload(args);
						}).defer(700);

						if(!Ext.isEmpty( Ext.getCmp('advSearchBtn') )){
							Ext.getCmp('advSearchBtn').toggle(true);
						}
					}
				},{
					text : '<span style="position:relative;top:1px;"><i class="fa fa-close" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00031'),//'닫기'
					scale: 'medium',
					handler: function(b, e){
										Ariel.advancedSearch.hide();
					}
				},{
					text : '<span style="position:relative;top:1px;"><i class="fa fa-refresh" style="font-size:13px;"></i></span>&nbsp;'+_text('MN02096'),//'닫기'
					scale: 'medium',
					id: 'clearFieldConditions',
					handler: function(b, e){
						Ext.getCmp('a-search-win').searchWinReset();
						//Ext.getCmp('tab_warp').getActiveTab().reload();
					}
				}],
			}],
			listeners: {
				afterrender: function(self){
					Ext.getCmp('a-search-field').removeAll();
					var content_tab = Ext.getCmp('tab_warp');
                    var active_tab = content_tab.getActiveTab();
                    var ud_content_id = active_tab.ud_content_id;
					Ext.Ajax.request({
						url: '/store/search/get_dynamic2.php',
						params: {
							meta_table_id: ud_content_id,
							type: 'container'
						},
						callback: function(opt, success, response){
						if(success) {
							var result = Ext.decode(response.responseText);
							if(result.success) {
							Ext.getCmp('a-search-field').add({
								xtype: 'tabpanel',
								id: 'a-search-field-tab',
								activeTab: 0,
								border:false
							});
							for(var i=0; i < result.total; i++) {

								Ext.getCmp('a-search-field-tab').add({
									title: result.data[i].name,
									id: 'a-search-field-tab-' + result.data[i].container_id,
									ud_content_id: result.data[i].container_id,
									bodyStyle:{"background-color":"#f0f0f0"},
									border:false,
									items: []
								});
								for (var j=0; j < Ext.getCmp('form_value').itemNumber; j++)
								{
									Ext.getCmp('a-search-field-tab-' + result.data[i].container_id).add( buildCmp(ud_content_id, result.data[i].container_id, j) );
								}
							}

							Ext.getCmp('a-search-field').doLayout();

							}
						}
						}
					});

					Ext.getCmp('a-search-field').doLayout();
				}
			},
			searchWinReset: function(){
				//Ext.getCmp('a-search-win').searchWinReset();
				Ext.getCmp('a-search-field').removeAll();
				var clearFieldConditions_btn = Ext.getCmp('clearFieldConditions');
				clearFieldConditions_btn.disable();
				Ext.Ajax.request({
				url: '/store/search/get_dynamic2.php',
				params: {
					meta_table_id: Ext.getCmp('a-search-meta-table').getValue().getRawValue(),
					type: 'container'
				},
				callback: function(opt, success, response){
					if(success) {
					var result = Ext.decode(response.responseText);
					if(result.success) {
						var clearFieldConditions_btn = Ext.getCmp('clearFieldConditions');
						clearFieldConditions_btn.enable();
						Ext.getCmp('a-search-field').add({
						xtype: 'tabpanel',
						id: 'a-search-field-tab',
						activeTab: 0
						});
						for(var i=0; i<result.total; i++) {
						Ext.getCmp('a-search-field-tab').add({
							title: result.data[i].name,
							id: 'a-search-field-tab-' + result.data[i].container_id,
							ud_content_id: result.data[i].container_id,
							items: []
						});
						for (var j=0; j<Ext.getCmp('form_value').itemNumber; j++)
						{
							Ext.getCmp('a-search-field-tab-' + result.data[i].container_id).add( buildCmp(Ext.getCmp('a-search-meta-table').getValue().getRawValue(), result.data[i].container_id, j) );
						}
						}

						Ext.getCmp('a-search-field').doLayout();
					}
					}
				}
				});

				Ext.getCmp('a-search-field').doLayout();

				//초기화시 기본검색으로 새로고침 해준다.
				Ext.getCmp('tab_warp').mediaBeforeParam.action = '';
				if(!Ext.isEmpty( Ext.getCmp('advSearchBtn') )){
					Ext.getCmp('advSearchBtn').toggle(false);
				}
			}
	}).show();

	return Ariel.advancedSearch;
})()