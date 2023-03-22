<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
session_start();
fn_checkAuthPermission($_SESSION);
//print_r($_SESSION);

$meta_table_id = $_POST['meta_table_id'];
$container_id = $_POST['container_id'];
$type = $_POST['type'];

if($type == 'container') {
        $result = array();

        $containers = $db->queryAll("
			SELECT	*
			FROM		BC_USR_META_FIELD
			WHERE	UD_CONTENT_ID = ".$meta_table_id."
			AND		USR_META_FIELD_TYPE = 'container'
			AND		IS_SHOW = '1'
			ORDER BY SHOW_ORDER");

        foreach($containers as $container) {
            $result[] = array(
                        'type'			=> $container['usr_meta_field_type'],
                        'name'			=> $container['usr_meta_field_title'],
                        'join'			=> array(
                                'table' => 'bc_usr_meta_value',
                                'field' => 'usr_meta_field_id'
                        ),
                        'container_id'	=> $container['usr_meta_field_id']
            );
        }

        echo json_encode(array(
                'success' => true,
                'total' => count($containers),
                'data' => $result
        ));
} else if($type == 'component') {
	$query = "
		SELECT	USR_META_FIELD_ID
		FROM		BC_USR_META_FIELD
		WHERE	USR_META_FIELD_TYPE = 'container'
		AND		UD_CONTENT_ID = ".$meta_table_id."
		AND		SHOW_ORDER = (
						SELECT		MIN(SHOW_ORDER)
						FROM			BC_USR_META_FIELD
						WHERE		USR_META_FIELD_TYPE = 'container'
						AND			UD_CONTENT_ID = ".$meta_table_id."
					)
	";
	$info_first = $db->queryOne($query);
    if( $container_id == $info_first  ){//($container_id == '4000284') {
        $result = array(
			array(
				'type'			=> 'textfield',
				'name'			=> _text('MN00249'),//'제목'
				'meta_field_id'	=> 'title',
				'table'			=> 'bc_content',
				'field'			=> 'title',
				'default_value'	=> ''
			),
			array(
				'type'			=> 'datefield',
				'name'			=> _text('MN01002'),//'등록일자',
				'meta_field_id'	=> 'created_date',
				'table'			=> 'bc_content',
				'field'			=> 'created_date',
				'default_value'	=> ''
			)
        );
    }else if( $meta_table_id == $container_id ){
		 $result = array(
			array(
				'type'			=> 'textfield',
				'name'			=> _text('MN00249'),//'제목'
				'meta_field_id'	=> 'title',
				'table'			=> 'bc_content',
				'field'			=> 'title',
				'default_value'	=> ''
			),
			array(
				'type'			=> 'datefield',
				'name'			=> _text('MN01002'),//'등록일자',
				'meta_field_id'	=> 'created_date',
				'table'			=> 'bc_content',
				'field'			=> 'created_date',
				'default_value'	=> ''
			)
        );
		$container_id = $info_first;
	}else {
        $result = array();
    }


	$fields = $db->queryAll("select * from bc_usr_meta_field where ud_content_id = $meta_table_id and container_id = $container_id and is_search_reg='1' and is_show ='1' and usr_meta_field_type!='container' order by show_order");
	foreach ($fields as $field)
	{

			$default_value_array = explode('(default)',$field['default_value']);
			$default_value = array_pop($default_value_array);
			$result[] = array(
					'type'			=> $field['usr_meta_field_type'],
					'name'			=> $field['usr_meta_field_title'],
					'join'			=> array(
							'table' => 'bc_usr_meta_value',
							'field' => 'usr_meta_field_id'
					),
					'meta_field_id'	=> $field['usr_meta_field_id'],
					'usr_meta_field_code'	=> $field['usr_meta_field_code'],
					'default_value'	=> $default_value
			);

	}
	echo json_encode(array(
			'success' => true,
			'data' => $result
	));
}

?>