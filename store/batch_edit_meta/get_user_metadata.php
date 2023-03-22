<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

	$bs_content_id = $_POST['bs_content_id'];
	$ud_content_id = $_POST['ud_content_id'];
	$job = $_POST['job'];

	switch ($job) {
		case 'get_list_image_data':
			$content_ids = json_decode($_POST['content_ids'], true);
			get_list_image_data($content_ids);
			break;
		case 'get_list_movie_data':
			$content_ids = json_decode($_POST['content_ids'], true);
			get_list_movie_data($content_ids);
			break;
		case 'get_list_document_data':
			$content_ids = json_decode($_POST['content_ids'], true);
			get_list_document_data($content_ids);
			break;
		case 'get_user_meta_data_preview':
			$content_id = $_POST['content_id'];
			get_user_meta_data_preview($content_id, $ud_content_id, $bs_content_id);
			break;
		case 'get_user_meta_data_layout':
			$content_id = $_POST['content_id'];
			get_user_meta_data_layout($ud_content_id, $bs_content_id, $content_id);
			break;
		default:
			# code...
			break;
	}

	function get_list_image_data($content_ids){
		global $db;
		$query = "
			SELECT 	BCM.content_id,
					BCM.path, 
					BCC.title, 
					--S.virtual_path
					'".LOCAL_LOWRES_ROOT."' AS VIRTUAL_PATH
			FROM BC_MEDIA BCM
			  LEFT JOIN BC_CONTENT BCC
			  ON BCM.CONTENT_ID = BCC.CONTENT_ID
			  --LEFT OUTER JOIN BC_STORAGE S ON (BCM.STORAGE_ID = S.STORAGE_ID)
			WHERE BCM.CONTENT_ID IN (".join(',',$content_ids).")
			AND BCM.MEDIA_TYPE = 'proxy'
		";
		$data = $db->queryAll($query);
		echo json_encode(array(
			'success' => true,
			'data' => $data
		));
	}

	function get_list_movie_data($content_ids){
		global $db;
		$query = "
			SELECT 	BCM.content_id,
					BCM.path, 
					BCC.title, 
					--S.VIRTUAL_PATH
					'".LOCAL_LOWRES_ROOT."' AS VIRTUAL_PATH
			FROM BC_MEDIA BCM
			  LEFT JOIN BC_CONTENT BCC
			  ON BCM.CONTENT_ID = BCC.CONTENT_ID
			  --LEFT OUTER JOIN BC_STORAGE S ON (BCM.STORAGE_ID = S.STORAGE_ID)
			WHERE BCM.CONTENT_ID IN (".join(',',$content_ids).")
			AND BCM.MEDIA_TYPE = 'proxy'
		";

		$data = $db->queryAll($query);
		echo json_encode(array(
			'success' => true,
			'data' => $data
		));
	}

	function get_list_document_data($content_ids){
		global $db;
		$query = "
			SELECT 	BCM.content_id,
					BCM.path, 
					BCC.title, 
					S.virtual_path
			FROM BC_MEDIA BCM
			  LEFT JOIN BC_CONTENT BCC
			  ON BCM.CONTENT_ID = BCC.CONTENT_ID
			  LEFT OUTER JOIN BC_STORAGE S ON (BCM.STORAGE_ID = S.STORAGE_ID)
			WHERE BCM.CONTENT_ID IN (".join(',',$content_ids).")
		";

		$data = $db->queryAll($query);
		echo json_encode(array(
			'success' => true,
			'data' => $data
		));
	}

	function get_user_meta_data_preview($content_id, $ud_content_id, $bs_content_id){
		require_once($_SERVER['DOCUMENT_ROOT'].'/store/metadata/function.php');
		require_once($_SERVER['DOCUMENT_ROOT'].'/store/metadata/buildEditedListTab.php');
		require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lang.php');
		global $db;

		$content = $db->queryRow("select c.ud_content_id, c.title, c.bs_content_id, c.is_group, m.ud_content_title as meta_type_name, c.reg_user_id, c.category_full_path from bc_content c, bc_ud_content m where c.content_id={$content_id} and c.ud_content_id=m.ud_content_id");
		$category_full_path = $content['category_full_path'];
		$category_path = ltrim ( $category_full_path );

		$root_category_info = $db->queryRow("
			SELECT	C.*
			FROM	BC_CATEGORY C,
					BC_CATEGORY_MAPPING CM
			WHERE	C.CATEGORY_ID = CM.CATEGORY_ID
			AND		CM.UD_CONTENT_ID = ".$content ['ud_content_id']."
		");
		$root_category_id = $root_category_info['category_id'];
		$root_category_text = $root_category_info['category_title'];
		if (empty($category_path)) $category_path = '0';

		$containerList = $db->queryAll("
			SELECT	*
			FROM		BC_USR_META_FIELD
			WHERE	UD_CONTENT_ID = ".$content['ud_content_id']."
			AND		CONTAINER_ID IS NOT NULL
			AND		DEPTH = 0
			AND		IS_SHOW = '1'
			ORDER BY SHOW_ORDER
		");

		$container_array = array();

		foreach ($containerList as $container_key => $container) {

			$container_id_tmp = $container['container_id'];
			$container_title = addslashes($container['usr_meta_field_title']);
			$rsFields =  MetaDataClass::getFieldValueforContaierInfo('usr' , $content['ud_content_id'], $container_id_tmp, $content_id);

			$items = array();
			
			if(empty($old_content_id)){
				$old_content_id = $content_id;
			}

			array_push($items, "{xtype: 'hidden', name: 'k_content_id', value: '".$old_content_id."'}\n");
			array_push($items, "{xtype: 'hidden', name: 'k_ud_content_id', value: '".$content['ud_content_id']."'}\n");
	        array_push($items, "{xtype: 'hidden', name: 'k_category_id', value: '".$content['category_id']."'}\n");
			if ($container_key == 0) {
					array_push($items, "{xtype: 'textfield', readOnly:true, fieldLabel: '&nbsp;&nbsp;&nbsp;"._text('MN00249')."', name: 'k_title', value: '".addslashes($content['title'])."'}\n");
			}

			if ($container_key == 0 ) {
				array_push($items, "{
										xtype: 'treecombo',
										readOnly : true,
										flex: 1,
										id: 'category_content',
										fieldLabel: _text('MN00387'),
										name: 'c_category_id_content',
										value: '".$category_path."',
										pathSeparator: ' > ',
										rootVisible: true,
										loader: new Ext.tree.TreeLoader({
											url: '/store/get_categories.php',
											baseParams: {
												action: 'get-folders',
												path: '".$category_path."',
												ud_content_tab: ".$ud_content_id."
											},
											listeners: {
												load: function(self, node, response){
													var path = self.baseParams.path;
													
													if(!Ext.isEmpty(path) && path != '0'){
														path = path.split('/');
														self.baseParams.path = path.join('/');

														var caregory_id, id, n, i;
														caregory_id = path[path.length-1];
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
															Ext.getCmp('category_content').setValue(caregory_id);
														} else {
															//Expand and search again or select
															if(n && n.isExpandable()){
																n.expand(); //if not find id in this load, then expand(reload)
															}else{
																n.select();
																Ext.getCmp('category_content').setValue(n.id);
															}
														}
													}else{
														node.select();
														Ext.getCmp('category_content').setValue(node.id);
													}
												}
											}
										}),

										root: new Ext.tree.AsyncTreeNode({
											id: '".$root_category_id."',
											text: '".$root_category_text."',
											expanded: true
										}),
										listeners: {
											select: function(self, node) {
												Ext.getCmp('category_content').setValue(node.id);
											}
										}
									}");
			}

			foreach ($rsFields as $f) {
				if ($f['is_show'] != 0){					
                    
					$xtype	= $f['usr_meta_field_type'];
					$is_required = $f['is_required'];
					if ($is_required == 1) {
						$check_star = "<font color=red>*&nbsp;</font>";
					} else {
						$check_star = "&nbsp;&nbsp;&nbsp;";
					}
					$label	= $check_star.addslashes($f['usr_meta_field_title']);
					$name	= strtolower($f['usr_meta_field_code']);
					$value	= autoConvertByType($xtype, $f['value']);
					$meta_field_id = $f['usr_meta_field_id'];

					// 커스터마이징 된 메타데이터에 대한 처리
					// The logic for customized metadata
					if(defined('CUSTOM_ROOT') && class_exists('\ProximaCustom\core\MetadataFieldManager')) {
						$control = \ProximaCustom\core\MetadataFieldManager::getFieldControl($f, 
													$value, $content_id, 
													\ProximaCustom\core\MetadataMode::BatchPreview,$rsFields);
						if(!empty($control)) {
							$items[] = $control;	
							continue;			
						}					
					}
		
					$item = array();

					array_push($item, "xtype:			'".$xtype."'");
					array_push($item, "name:			'".$name."'");
					array_push($item, "id:			'".$name."'");
					
					array_push($item, "readOnly: true");
					
		            if( $f['is_editable'] == 0 ) {
		                array_push($item, "readOnly: true");
		            }
		
		            if( $f['is_show'] == 0 ) {
		                array_push($item, "hidden: true");
		            }
		
					if ($xtype == 'checkbox' ) {
						if(!empty($value) && ( $value == 'on' || $value == '1' ) ){
							array_push($item, "checked:			'".'true'."'");
						}
						array_push($item, "value: '".esc2($value)."'");
					} else if ($xtype == 'textarea') {
						array_push($item, "value: '" . esc3($value) . "'");
					} else {
						array_push($item, "value: '".esc2($value)."'");
					}
		
					//array_push($item, "flex: 1");
					if ( $is_required == '1' ) {
						array_push($item, "allowBlank: false");
					}
                    
					if ($xtype == 'textarea') {
						array_push($item, "height: 200");
						//2015-12-16 textarea 4000자 이내로 수정
						array_push($item, "maxLength: 4000");
						//2015-12-29 textareat regexText 추가
						array_push($item, "regexText: '최대 길이는 4000자 입니다.'");
					}
		
					//2015-12-16 textfield 200자 이내로 수정
					if ($xtype == 'textfield') {
						array_push($item, "autoCreate: {tag: 'input', type: 'text', autocomplete: 'off', maxlength: '200'}");
					}
		
					if ($xtype == 'datefield') {
						array_push($item, "altFormats: 'Y-m-d|Y-m-d H:i:s|YmdHis', format: 'Y-m-d'");
						//2015-12-29 datefield regexText 추가
						array_push($item, "regexText: '올바른 날짜 형식이 아닙니다.'");
					} else if ($xtype == 'combo') {
						$store = getFieldCodeValue($meta_field_id, $f['usr_meta_field_code']);
						if( empty($store) || $store =='[]' ){
							$store = "[".getFieldDefaultValue($meta_field_id)."]";
							//2015-12-29 combo editable true->false 수정
							array_push($item, "editable: false, triggerAction: 'all', typeAhead: true, mode: 'local', store: $store");
						}else{
							array_push($item, "editable: false, triggerAction: 'all', typeAhead: true, mode: 'local', valueField: 'key',displayField: 'val',store: new Ext.data.JsonStore({ fields: [{name:'key'},{name:'val'}],data: $store }) ");
						}
					}

					array_push($item, "fieldLabel:	'".$label."'");
					array_push($items, "{".join(', ', $item)."}\n" );
				}
			}

			
			$title_tab = "title: '{$container_title}',";
			

			$items_text = '['.join(', ', $items)."]\n";

			$container_text = "	
				{
					id: 'preview_metadata_{$container_id_tmp}',
					xtype: 'form',
					cls: 'change_background_panel_detail_content_light',
					flex : 1.5,
					autoScroll: true,
					$title_tab
					padding: 5,
					border: false,
					defaultType: 'textfield',
					defaults: {
						labelSeparator: '',
						anchor: '95%'
					}
					,buttonAlign: 'center'
					,listeners: {
						render: function (self) {
							self.getForm().on('beforeaction', function (form) {
								form.items.each(function (item)	{
									if (item.xtype == 'checkbox')
									{
										if (!item.checked) {
											item.el.dom.checked = true;
											item.el.dom.value = 'off';
										}
									}
								});
							});
						}
					},
					items: [ $items_text ]
				}";

			$container_array [] = $container_text;
		}

		$containerBody = '['.join(',', $container_array).']';

		echo $containerBody;
	}

	function get_user_meta_data_layout($ud_content_id, $bs_content_id, $content_id){
		
		require_once($_SERVER['DOCUMENT_ROOT'].'/store/metadata/function.php');
		require_once($_SERVER['DOCUMENT_ROOT'].'/store/metadata/buildEditedListTab.php');
		require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lang.php');
		global $db;

		$root_category_info = $db->queryRow("
			SELECT	C.*
			FROM	BC_CATEGORY C,
					BC_CATEGORY_MAPPING CM
			WHERE	C.CATEGORY_ID = CM.CATEGORY_ID
			AND		CM.UD_CONTENT_ID = ".$ud_content_id."
		");
		$root_category_id = $root_category_info['category_id'];
		$root_category_text = $root_category_info['category_title'];
		
		if (empty($category_path)) $category_path = '0';

		$containerList = $db->queryAll("
			SELECT	*
			FROM		BC_USR_META_FIELD
			WHERE	UD_CONTENT_ID = ".$ud_content_id."
			AND		CONTAINER_ID IS NOT NULL
			AND		DEPTH = 0
			AND		IS_SHOW = '1'
			ORDER BY SHOW_ORDER
		");

		$container_array = array();

		foreach ($containerList as $container_key => $container) {

			$container_id_tmp = $container['container_id'];
			$container_title = addslashes($container['usr_meta_field_title']);
			$rsFields =  MetaDataClass::getFieldValueforContaierInfo('usr' , $ud_content_id, $container_id_tmp, $content_id);

			$items = array();

			if ($container_key == 0) {
				array_push($items, "{
										fieldLabel: _text('MN00249'),
										xtype: 'compositefield',
										items:[{
											name: 'content_title_edit_checkbox',
											xtype: 'checkbox',
											disabled: true,
											checked: false,
											listeners: {
												change: function(cb, checked) {
													Ext.getCmp('content_title_edit').setDisabled(!checked);
												}
										   }
										},{
											id:'content_title_edit',
											flex: 1,
											name: 'k_title',
											xtype: 'textfield',
											disabled: true,
										}]
									}
							");
				array_push($items, "{
										fieldLabel: _text('MN00387'),
										xtype: 'compositefield',
										items:[{
											name: 'content_category_edit_checkbox',
											xtype: 'checkbox',
											checked: false,
											listeners: {
												check: function(cb, checked) {
													Ext.getCmp('category_content_edit').setDisabled(!checked);
													Ext.getCmp('category_content_edit').setReadOnly(!checked);
													if(checked){
														Ext.getCmp('category_content_edit').focus();
													}
												}

										   }
										},{
											xtype: 'treecombo',
											flex: 1,
											disabled: true,
											readOnly: true,
											id: 'category_content_edit',
											fieldLabel: _text('MN00387'),
											name: 'c_category_id',
											pathSeparator: ' > ',
											rootVisible: true,
											loader: new Ext.tree.TreeLoader({
												url: '/store/get_categories.php',
												baseParams: {
													action: 'get-folders',
													path: '".$category_path."',
													ud_content_tab: ".$ud_content_id."
												},
												listeners: {
													load: function(self, node, response){
														var path = self.baseParams.path;
														
														if(!Ext.isEmpty(path) && path != '0'){
															path = path.split('/');
															self.baseParams.path = path.join('/');

															var caregory_id, id, n, i;
															caregory_id = path[path.length-1];
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
																Ext.getCmp('category_content_edit').setValue(caregory_id);
															} else {
																//Expand and search again or select
																if(n && n.isExpandable()){
																	n.expand(); //if not find id in this load, then expand(reload)
																}else{
																	n.select();
																	Ext.getCmp('category_content_edit').setValue(n.id);
																}
															}
														}else{
															node.select();
															Ext.getCmp('category_content_edit').setValue(node.id);
														}
													}
												}
											}),

											root: new Ext.tree.AsyncTreeNode({
												id: '".$root_category_id."',
												text: '".$root_category_text."',
												expanded: true
											}),
											listeners: {
												select: function(self, node) {
													Ext.getCmp('category_content_edit').setValue(node.id);
												}
											}
										}]
									}");
			}

			foreach ($rsFields as $f) {
				if ($f['is_show'] != 0){
					
					$xtype	= $f['usr_meta_field_type'];
					$is_required = $f['is_required'];
					if ($is_required == 1) {
						$check_star = "<font color=red>*&nbsp;</font>";
					} else {
						$check_star = "";
					}
					$label	= $check_star.addslashes($f['usr_meta_field_title']);
					$name	= strtolower($f['usr_meta_field_code']);
					$value	= autoConvertByType($xtype, $f['value']);
					$meta_field_id = $f['usr_meta_field_id'];

					// 커스터마이징 된 메타데이터에 대한 처리
					// The logic for customized metadata
					if(defined('CUSTOM_ROOT') && class_exists('\ProximaCustom\core\MetadataFieldManager')) {
						$control = \ProximaCustom\core\MetadataFieldManager::getFieldControl($f, 
									$value, $content_id, \ProximaCustom\core\MetadataMode::BatchEdit,$rsFields);
						if(!empty($control)) {
							$items[] = $control;	
							continue;			
						}					
					}
		
					$item = array();

					array_push($item, "xtype:			'".$xtype."'");
					array_push($item, "name:			'usr_".$name."'");
					array_push($item, "id:			'user_metadata_".$name."'");

		            if( $f['is_editable'] == 0 ) {
		                array_push($item, "readOnly: true");
		            }
		
		            if( $f['is_show'] == 0 ) {
		                array_push($item, "hidden: true");
		            }
                    
					if ( $is_required == '1' ) {
						array_push($item, "allowBlank: false");
					}
		
					if ($xtype == 'textarea') {
						array_push($item, "height: 200");
						//2015-12-16 textarea 4000자 이내로 수정
						array_push($item, "maxLength: 4000");
						//2015-12-29 textareat regexText 추가
						array_push($item, "regexText: '최대 길이는 4000자 입니다.'");
					}
		
					//2015-12-16 textfield 200자 이내로 수정
					if ($xtype == 'textfield') {
						array_push($item, "autoCreate: {tag: 'input', type: 'text', autocomplete: 'off', maxlength: '200'}");
					}
		
					if ($xtype == 'datefield') {
						array_push($item, "altFormats: 'Y-m-d|Y-m-d H:i:s|YmdHis', format: 'Y-m-d'");
						//2015-12-29 datefield regexText 추가
						array_push($item, "regexText: '올바른 날짜 형식이 아닙니다.'");
					} else if ($xtype == 'combo') {
						$store = getFieldCodeValue($meta_field_id, $f['usr_meta_field_code']);
						if( empty($store) || $store =='[]' ){
							$store = "[".getFieldDefaultValue($meta_field_id)."]";
							//2015-12-29 combo editable true->false 수정
							array_push($item, "editable: false, triggerAction: 'all', typeAhead: true, mode: 'local', store: $store");
						}else{
							array_push($item, "editable: false, triggerAction: 'all', typeAhead: true, mode: 'local', valueField: 'key',displayField: 'val',store: new Ext.data.JsonStore({ fields: [{name:'key'},{name:'val'}],data: $store }) ");
						}
					}
		
					array_push($item, "flex: 1");
					array_push($item, "disabled: true");
					//array_push($items, "{".join(', ', $item)."}\n" );
					array_push($items, "
										{
											fieldLabel: '{$label}',
											xtype: 'compositefield',
											items:[
												{
													name: 'user_metadata_checkbox_{$name}',
													xtype: 'checkbox',
													checked: false,
													listeners: {
														check: function(cb, checked){
															Ext.getCmp('user_metadata_{$name}').setDisabled(!checked);
															if(checked){
																Ext.getCmp('user_metadata_{$name}').focus();
															}
														}
												   }
												},
												{".join(', ', $item)."}
											]
										}\n" );
				}
			}

			
			$title_tab = "title: '{$container_title}',";
			

			$items_text = '['.join(', ', $items)."]\n";

			// 저장 전 작업에 대한 로직 문자열을 얻어온다.
			$beforeSaveJsLogic = '';
			if(defined('CUSTOM_ROOT') && class_exists('\ProximaCustom\core\MetadataManager')) {
				$beforeSaveJsLogic = \ProximaCustom\core\MetadataManager::getBeforeSaveJsLogic();				
			}

			$container_text = "	
				{
					id: 'edit_metadata_{$container_id_tmp}',
					xtype: 'form',
					cls: 'change_background_panel_detail_content_light',
					flex : 1,
					autoScroll: true,
					$title_tab
					padding: 5,
					border: false,
					defaultType: 'textfield',
					defaults: {
						labelSeparator: '',
						anchor: '95%'
					}
					,buttonAlign: 'center'
					,listeners: {
						render: function (self) {
							self.getForm().on('beforeaction', function (form) {
								form.items.each(function (item)	{
									if (item.xtype == 'checkbox')
									{
										if (!item.checked) {
											item.el.dom.checked = true;
											item.el.dom.value = 'off';
										}
									}
								});
							});
						}
					},
					items: [ $items_text ],
					buttons:[{
						text: '<span style=\"position:relative;top:1px;\"><i class=\"fa fa-edit\" style=\"font-size:13px;\"></i></span>&nbsp;'+_text('MN00043'),
						scale: 'medium',
						submit: function(callback){

						},
						listeners: {
							click: function(self, pass_conform){

								// 저장전 커스텀 로직
								{$beforeSaveJsLogic}

								var p = Ext.getCmp('edit_metadata_{$container_id_tmp}').getForm().getValues();
								var is_empty = Object.keys(p).length;
								if(is_empty > 0){
									var isValid = Ext.getCmp('edit_metadata_{$container_id_tmp}').getForm().isValid();
									
									if(isValid){
										if(p.hasOwnProperty('k_title')){
											var title = p.k_title;
											if(title.length == 0){
												Ext.Msg.alert(_text('MN00022'), _text('MSG00090'));
												return;	
											}
										}

										if (p.hasOwnProperty('c_category_id')) {
											var tn = Ext.getCmp('category_content_edit').treePanel.getSelectionModel().getSelectedNode();
											p.c_category_id = tn.attributes.id;
										}
										for(var propertyName in p) {
										   if(p[propertyName] == 'on'){
												delete p[propertyName];
											}
										}

										var list_content_grid_items = Ext.getCmp('list_content_grid').getStore().data.items;
										
										var list_content = [];
		                                Ext.each(list_content_grid_items, function(r, i, a){
		                        			list_content.push(r.get('content_id'));
		                        		});
									
										p.content_ids = Ext.encode(list_content);
										p.ud_content_id = ".$ud_content_id.";

										Ext.Msg.show({
											icon: Ext.Msg.QUESTION,
											title: _text('MN00003'),
											msg: _text('MSG00189'),
											buttons: Ext.Msg.YESNO,
											fn: function(btnId, text, opts){
												if(btnId == 'no') return;

												Ext.Ajax.request({
													url: '/store/batch_edit_meta/batch_edit_meta.php',
													params: p,
													callback: function(opts, success, response){
														if(success){
															Ext.getCmp('batchEditMetaWin').close();
														}else{

														}
													}
												});
											}
										});
									}else{
										Ext.Msg.alert(_text('MN00022'), _text('MSG02517'));
									}

								}else{
									Ext.Msg.alert(_text('MN00022'), _text('MSG02516'));
									return;
								}
							}
						}
					}]
				}";

			$container_array [] = $container_text;
		}

		$containerBody = '['.join(',', $container_array).']';

		echo $containerBody;
	}

?>