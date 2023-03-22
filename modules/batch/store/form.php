<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

//error_reporting(E_ALL ^ E_WARNING ^ E_NOTICE);

try
{
	$content_id = $_POST['content_id'];
	if ( empty($content_id) ) throw new Exception('No content_ids');

	$rsFields = $db->queryAll("select c.content_id, c.title, c.category_id, c.ud_content_id, f.usr_meta_field_id, f.usr_meta_field_type, f.usr_meta_field_title, f.is_editable, v.usr_meta_value, v.usr_meta_value_id ".
								"from bc_content c, bc_usr_meta_field f, bc_usr_meta_value v ".
								"where c.content_id=".$content_id." ".
								"and c.ud_content_id=f.ud_content_id ".
								"and f.usr_meta_field_id=v.usr_meta_field_id ".
								"and c.content_id=v.content_id ".
								"and f.usr_meta_field_type != 'container' ".
								"order by f.show_order");
/*
	$root_category = findCategoryRoot($rsFields[0]['ud_content_id']); //메타테이블아이디를 루트로 설정 by 이성용

	if( $root_category )
	{
		$root_category_id = $root_category['category_id'];
		$root_category_text = $root_category['category_title'];
		$category_path = substr(getCategoryPath($rsFields[0]['category_id']).'/'.$rsFields[0]['category_id'], 11);
	}
	else
	{*/
		$root_category_id = 0;
		$root_category_text = 'EBS DAS';
		$category_path = substr(getCategoryPath($rsFields[0]['category_id']).'/'.$rsFields[0]['category_id'], 2);
//	}



	if(empty($category_path)) $category_path = '0';

	$items = array();

	array_push($items, "{xtype: 'hidden', name: 'k_content_id', value: '".$content_id."'}\n");
	array_push($items, "{xtype: 'hidden', name: 'k_ud_content_id', value: '".$rsFields[0]['ud_content_id']."'}\n");
//	array_push( $items, buildCompositeFieldWithCheckbox( '제목', 'c_title', buildTextField('c_title', $rsFields[0]['title']) ) );
	array_push($items, buildCompositeFieldWithCheckbox( _text('MN00387'), 'c_category_id', '1' , getCategoryTree($category_path, $root_category_id, $root_category_text ) ));

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
		array_push($item, "value:			'".esc2($value)."'");
		array_push($item, "flex: 1");

		if ($xtype == 'datefield')
		{
			array_push($item, "altFormats: 'Y-m-d|Y-m-d H:i:s|YmdHis', format: 'Y-m-d'");
		}
		else if ($xtype == 'combo')
		{
			$store = "[".getFieldDefaultValue($meta_field_id)."]";
			array_push($item, "editable: true, triggerAction: 'all', typeAhead: true, mode: 'local', store: $store");
		}


		array_push( $items, buildCompositeFieldWithCheckbox( $label, $name, $f['is_editable'], "{".join(', ', $item)."}\n" ) );
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
		$result = "{".
		"xtype: 'compositefield',".
		"fieldLabel: '$label',".
		"name: '$name',".

		"items: [{".
			"xtype: 'checkbox'".
		"}, ".
			$item.
		"]}";
	}
	else
	{
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
			$timestamp = date('Y-m-d H:i:s', $timestamp);
		}

		return $timestamp;
	}
	else
	{
		return addslashes($value);
	}
}
?>
