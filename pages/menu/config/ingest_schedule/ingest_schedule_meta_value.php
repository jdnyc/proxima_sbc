<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'].'/lib/config.php';
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');


try {
    $schedule_id = $_POST['schedule_id'];
    $ud_content_id = $_POST['ud_content_id'];

    if (empty($schedule_id)) {
        throw new Excpetion('schedule 이 존재하지 않습니다.');
    }

    $schedule = $db->queryRow("select * from ingestmanager_schedule where schedule_id=".$schedule_id);
    
    // $schedule_value = $db->queryAll("
	// 	select	v.schedule_id
	// 			, f.usr_metA_field_title
	// 			, f.usr_meta_field_code
	// 			, v.bc_usr_meta_field_id
	// 			, v.USR_META_VALUE
	// 	from	im_schedule_metadata v
	// 			left outer join bc_usr_meta_field f on (f.usr_meta_field_id = v.bc_usr_meta_Field_id)
	// 	where	v.schedule_id =".$schedule_id."
	// 	and		f.ud_content_id = ".$ud_content_id."
	// 	order by f.usr_meta_field_id
    // ");
    
    $schedule_value = $db->queryRow("
        select *
        from ingestmanager_schedule_meta
        where schedule_id =".$schedule_id."
    ");
    
    // if ($schedule['ud_content_tab'] == 'topic') {
    //     $root_category_text = '토픽';
    //     $root_category_id = '-1';
    //     $category_path = ltrim(substr(getCategoryPath($schedule['category_id']).'/'.$schedule['category_id'], 4), '/');
    // } else {
    //     $root_category_text = 'TBS NPS';
    //     $root_category_id = 0;
    //     $category_path = ltrim(substr(getCategoryPath($schedule['category_id']).'/'.$schedule['category_id'], 2), '/');
    // }

    // $logger->addInfo('schedule meta', $schedule);

    // $values = array(
    //     'k_title' => $schedule['title'],
	// 	'category_id' => $schedule['category_id'],
    //     'category_path' => ' > ' . $root_category_id . ' > ' . str_replace('/', ' > ', $category_path)
    // );

    // foreach ($schedule_value as $value) {
    //     $values[$value['bc_usr_meta_field_id']] = $value['usr_meta_value'];
	// 	if($value['usr_meta_field_code'] == 'EPSD_NO'){
	// 		$values['epsd_no'] = $value['usr_meta_value'];
	// 	}
    // }

   // echo json_encode($schedule_value['metadata']);
   echo $schedule_value['metadata'];

} catch (Exception $e) {
    $logger->addError($e->getMessage());
}
