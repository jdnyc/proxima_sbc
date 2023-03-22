<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/mssql_connection.php');

//error_reporting(E_ALL ^ E_WARNING ^ E_NOTICE);

try
{
	$content_id = $_REQUEST['content_id'];
	$type = $_REQUEST['type'];
	if ( empty($content_id) ) throw new Exception('No content_ids');

	$container_info = $db->queryRow("select * from bc_usr_meta_field where usr_meta_field_type='container' and usr_meta_field_title='기본정보' and ud_content_id=( select ud_content_id from bc_content where content_id='$content_id' )");

	$rsFields = $db->queryAll("select c.content_id, c.title, c.category_id, c.ud_content_id, f.usr_meta_field_id, f.usr_meta_field_type, f.usr_meta_field_title, f.is_editable, v.usr_meta_value, v.usr_meta_value_id ".
	"from bc_content c, bc_usr_meta_field f, bc_usr_meta_value v ".
	"where c.content_id=".$content_id." ".
	"and c.ud_content_id=f.ud_content_id ".
	"and f.usr_meta_field_id=v.usr_meta_field_id ".
	"and c.content_id=v.content_id ".
	"and f.usr_meta_field_type != 'container' ".
	"and f.container_id = '{$container_info[usr_meta_field_id]}' ".
	"order by f.show_order");

	if(empty($category_path)) $category_path = '0';

	$items = array();

	array_push($items, "{xtype: 'hidden', name: 'k_content_id', value: '".$content_id."'}\n");
	array_push($items, "{xtype: 'hidden', name: 'k_progcd',id: 'k_progcd'}\n");
	array_push($items, "{xtype: 'hidden', name: 'k_subprogcd',id: 'k_subprogcd'}\n");
	array_push($items, "{xtype: 'hidden', name: 'k_brodymd',id: 'k_brodymd'}\n");
	array_push($items, "{xtype: 'hidden', name: 'k_formbaseymd',id: 'k_formbaseymd'}\n");
	array_push($items, "{xtype: 'hidden', name: 'k_medcd',id: 'k_medcd'}\n");
	array_push($items, "{xtype: 'hidden', name: 'k_ud_content_id', value: '".$rsFields[0]['ud_content_id']."'}\n");
	array_push($items, "{xtype: 'textfield',fieldLabel: '제목', name: 'k_title', value: '".addslashes($rsFields[0]['title'])."'}\n");

	foreach ( $rsFields as $f )
	{
		$item = array();
		$xtype	= $f['usr_meta_field_type'];
		$label	= addslashes($f['usr_meta_field_title']);
		$name	= $f['usr_meta_field_id'];
		$value	= autoConvertByType($xtype, $f['usr_meta_value']);
		$meta_field_id = $f['usr_meta_field_id'];

		if ( $xtype == 'listview') continue;



		$item = array();
		array_push($item, "xtype:			'".$xtype."'");
		array_push($item, "name:			'".$name."'");
		array_push($item, "id:			'".$name."'");
		array_push($item, "value:			'".esc2($value)."'");
		//array_push($item, "flex: 1");

		if ($xtype == 'datefield')
		{
			array_push($item, "altFormats: 'Y-m-d|Y-m-d H:i:s|YmdHis', format: 'Y-m-d'");
		}
		else if ($xtype == 'combo')
		{
			$store = "[".getFieldDefaultValue($meta_field_id)."]";
			array_push($item, "editable: true, triggerAction: 'all', typeAhead: true, mode: 'local', store: $store");
		}

		if($label == '부제')
		{
			array_push($item, "flex: 1");

			$category_info = $db->queryRow("select ct.* from bc_content c, bc_category ct where ct.category_id=c.category_id and c.content_id='$content_id'");

			if($category_info['parent_id'] == '0')
			{
				$category_mapping = $category_info['category_id'];
			}
			else
			{
				$category_mapping = $category_info['parent_id'];
			}

			$item = buildSubProgField($content_id , $label, $name , $value , $meta_field_id, $category_mapping );
			array_push( $items,  $item );
		}
		else
		{
			if( ($type =='review') && ( $label == '등급분류') ){

					array_push($item, " allowBlank : false ");
			}
			array_push($item, "fieldLabel:	'".$label."'");
			array_push( $items, "{".join(', ', $item)."}\n" );
		}
	}


	if($type =='review') {
		//심의 의뢰 일경우
		//필드 추가


		$content_info = $db->queryRow("select * from view_content where content_id='$content_id'");

		$medcd = $content_info['medcd'];
		$progcd = $content_info['progcd'];
		$formbaseymd = $content_info['formbaseymd'];
		$subprogcd = $content_info['subprogcd'];
		if( !MDB2::isError($db_ms) ){
			$pdempno = $db_ms->queryOne("select pdempno from tbbma02 where medcd='$medcd' and  progcd='$progcd' and formbaseymd='$formbaseymd' and  subprogcd='$subprogcd' ");
		}
		$pdempno = trim($pdempno);

		if( !empty($pdempno) ){
			$dept_nm = $db->queryOne("select dept_nm from bc_member where user_id='$pdempno' ");
		}

		array_push($items, "{xtype: 'textfield',fieldLabel: 'Tape No', name: 'k_tapeno'}\n");
		array_push($items, "{xtype: 'textfield',fieldLabel: '자체/외주', name: 'k_producer' ,value:'자체' }\n");
		array_push($items, "{xtype: 'textfield',fieldLabel: '담당부서',id: 'k_dept_nm', name: 'k_dept_nm',value:'$dept_nm' }\n");
		array_push($items, "{xtype: 'combo',fieldLabel: '심의형식', name: 'k_review_type',value:'FILE',editable: true, triggerAction: 'all', typeAhead: true, mode: 'local', store: ['FILE','DVD'] }\n");
	}


	echo '['.join(', ', $items)."]\n";
}
catch (Exception $e)
{
	die(json_encode(array(
		'success' => false,
		'msg' => $e->getMessage().$db->last_query
	)));
}

function buildSubProgField($content_id , $label, $name , $value , $meta_field_id, $category_id )
{
	global $db;
	$value = esc2($value);

	$res = getTotalInfo($category_id, $content_id);

	$start_brodymd = empty($res['start_brodymd']) ? '': $res['start_brodymd'];
	$end_brodymd =  empty($res['end_brodymd']) ? '': $res['end_brodymd'];

	$codes = $db->queryRow("select * from content_code_info where content_id='$content_id'");

	$progcd = $codes['progcd'];
	$subprogcd = $codes['subprogcd'];

	if( empty($progcd) ||  empty($subprogcd) ){
		$value = '';
	}

	$end_ymd ='';
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
			 	allowBlank : false,
				emptyText: '',
				value: '$value',
				store: new Ext.data.JsonStore({
					url: '/store/searchmeta_store.php',
					baseParams: {
						category_id : '$category_id'
					},
					root: 'data',
					fields: [
						'dept_nm', 'comb_cd', 'subprogcd', 'prognm', 'subprognm', 'progcd', 'brodymd', 'formbaseymd' ,'korname', 'medcd'
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

						if( !Ext.isEmpty(form.get('k_dept_nm')) ){
							form.get('k_dept_nm').setValue( record.get('dept_nm') );
						}

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
					width: 30,
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

function getCategoryTree($category_path, $root_category_id, $root_category_text){

	return "{".
		"xtype: 'treecombo',".
		"flex: 1,".
		"id: 'batch_category',".
		"name: 'c_category_id',".
		"value: '".$category_path."',".
		"pathSeparator: ' > ',".
		"rootVisible: false, ".
		"loader: new Ext.tree.TreeLoader({".
			"url: '/store/get_categories.php',".
			"baseParams: {".
				"action: 'get-folders',".
				"path: '".$category_path."'".
			"},".
			"listeners: {".
				"load: function(self, node, response){

					var path = self.baseParams.path;".
					"if(!Ext.isEmpty(path) && path != '0'){".
						"path = path.split('/');".

						"var id = path.shift();".
						"self.baseParams.path = path.join('/');".

						"var n = node.findChild('id', id);".
						"if(n && n.isExpandable()){".
							"n.expand();".
						"}else{".
							"n.select();".
							"Ext.getCmp('batch_category').setValue(n.id);".
						"}".
					"}else{".
						"node.select();".
						"Ext.getCmp('batch_category').setValue(node.id);".
					"}".
				"}".
			"}".
		"}),".
		"root: new Ext.tree.AsyncTreeNode({".
			"id: ".$root_category_id.",".
			//"text: '$root_category_text',".
			"expanded: true".
		"})".
	"}";
}

function buildCompositeFieldWithCheckbox($label, $name, $editable, $item)
{
	if( $editable == '1')
	{
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
	}
	else
	{
		return $item;
		$result = "{".
			"xtype: 'compositefield',".
			"disabled: true,".
			"fieldLabel: '$label',".
			"name: '$name',".

			"items: [{".
				"xtype: 'checkbox'".
			"}, ".
				$item.
			"]}";
	}

	return $result;
}

function buildTextField($name, $value)
{
	$name = addslashes($name);
	$value = addslashes($value);

	return "{".
		"xtype: 'textfield',".
		"name: '".$name."',".
		"value: '".$value."',".
		"flex: 1".
	"}";
}

function getFieldDefaultValue($field_id)
{
	global $db;

	$data = $db->queryOne("select default_value from bc_usr_meta_field where usr_meta_field_id=".$field_id);

	list($default, $value_list) = explode('(default)', $data);
	$value_list = explode(';', $value_list);

	foreach ($value_list as $value)
	{
		$result[] = "'$value'";
	}

	return join(',', $result);
}

function autoConvertByType($xtype, $value)
{
	if ($xtype == 'datefield')
	{
		$timestamp = strtotime($value);
		if (!$timestamp)
		{
			$timestamp = '';
		}
		else
		{
			$timestamp = date('YmdHis', $timestamp);
		}

		return $timestamp;
	}
	else
	{
		return addslashes($value);
	}
}


function getTotalInfo($category_id, $content_id ){
	global $db;
	global $db_ms;



	if( is_null($category_id) )
	{
		$category_info = $db->queryRow("select ct.* from bc_content c, bc_category ct where ct.category_id=c.category_id and c.content_id='$content_id'");

		if($category_info['parent_id'] == '0')
		{
			$category_id = $category_info['category_id'];
		}
		else
		{
			$category_id = $category_info['parent_id'];
		}
	}

	$whereinfo = $db->queryAll("select * from CATEGORY_PROGCD_MAPPING where category_id='$category_id'");
	$medcd = $whereinfo['medcd'];
	$formbaseymd = $whereinfo['formbaseymd'];
	$progcd = $whereinfo['progcd'];
	$prognm = $whereinfo['prognm'];

	$query_array = array();

	if( !empty($whereinfo) )
	{
		foreach($whereinfo as $info)
		{
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


			array_push($query_array , $forquery );
		}

		$query = join(' union all ', $query_array);
		$query = "select * from ( $query  ) t";
		$query .= $where;
		$order = " order by t.brodymd desc";

		if( MDB2::isError($db_ms) ){
			return array(
				'data' => array(),
				'start_brodymd' => $start_brodymd,
				'end_brodymd' => $end_brodymd
			);
		}

		$res = $db_ms->queryAll($query.$order);



		if( !empty($res) )
		{
			$start_brodymd = $res[count($res)-1]['brodymd'];
			$end_brodymd = $res[0]['brodymd'];
		}
		else
		{
			$start_brodymd ='';
			$end_brodymd ='';
		}
	}
	else
	{
		$res = array();
		$start_brodymd ='';
		$end_brodymd ='';
	}

	return array(
		'data' => $res,
		'start_brodymd' => $start_brodymd,
		'end_brodymd' => $end_brodymd
	);

}
?>
