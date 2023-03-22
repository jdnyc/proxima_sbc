<?php
function buildSystemMeta($content_id, $bs_content_id,$args)
{
	global $db;
	$content_fields = MetaDataClass::getFieldValueInfo('sys', $bs_content_id , $content_id);
//	$content_fields = $db->queryAll("select * from bc_sys_meta_field f, bc_sys_meta_value v where " .
//										"v.content_id=$content_id and v.sys_meta_field_id=f.sys_meta_field_id order by f.show_order, f.sys_meta_field_title");

    $loudnessInfo = $db->queryRow( "SELECT *
    FROM TB_LOUDNESS 
    WHERE content_id ='$content_id'");
	if ($content_fields)
	{
		$system_meta = "{
			xtype: 'form',
			cls: 'change_background_panel background_panel_detail_content',
			id: 'media_info_detail_content',
			name: 'media_info_tab',
			split: true,
			$args,
			title: '"._text('MN02542')."',
			autoScroll: true,
			defaultType: 'textfield',
			padding: 5,
			border: false,
			//frame: true,
			labelAlign: 'left',
			labelWidth: 110,
			defaults: {
				anchor: '95%',
				labelSeparator: ''
			},
			items: [";

		$json_array = array();
		foreach($content_fields as $v){
			if($v['is_visible'] != '1') continue;

			$json .= "{disabled: true, disabledClass: 'ariel-x-item-disabled', ";
			if ($v['field_input_type'] != 'textfield') $json .= "xtype: '{$v['field_input_type']}', ";
//			if (preg_match('/비트레이트/', $v['sys_meta_field_title'])) $v['sys_meta_value'] .= ' k';
//			if (strstr($v['sys_meta_field_title'], '프레임레이트') && !strstr($v['sys_meta_value'], 'Frame')) $v['sys_meta_value'] .= ' Frame/s';

			$json .= "fieldLabel: '".addslashes($v['sys_meta_field_title'])."', name: '".$v['sys_meta_field_id']."', value: '".addslashes(trim($v['value']))."'}";

			array_push($json_array, $json);
			unset($json);
		}
        $system_meta .= implode(', ', $json_array);
        $loudnessInfo = $db->queryRow( "SELECT *
        FROM TB_LOUDNESS 
        WHERE content_id ='$content_id'");
        if(!empty($loudnessInfo)){
            $integrate = $loudnessInfo['integrate'];
            $system_meta .= ",{xtype:'displayfield', value: '<font color=\"white\">*방송음향 기준 : LKFS -24 ( ±2)</font>'}";
            $system_meta .= ",{fieldLabel: 'LKFS 측정값', value: '".addslashes(trim($integrate))."'}";
        }
		$system_meta .= "]}";
	}
	else
	{
		$system_meta = "{title: '"._text('MN02542')."', $args, html: '"._text('MSG00148')."다'}";
	}

	return $system_meta;
}
?>