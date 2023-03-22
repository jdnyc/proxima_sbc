<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

try
{
	$artcl_id = $_POST['artcl_id'];
	$data = json_decode($_POST['data'], true);

	$name_field = array(
		'artcl_id' => '기사아이디',
		'ch_div_nm' => '',
		'artcl_frm_nm' => '기사형식명',
		'artcl_fld_nm' => '기사분야명',
		'artcl_titl' => '기사제목',
		'artcl_ctt' => '기사내용',
		'dept_nm' => '부서명',
		'artcl_reqd_sec' => '',
		'artcl_div_nm' => '',
		'issu_seq' => '',
		'apprv_div_nm' => '승인구분명',
		'artcl_ord' => '',
		'brdc_cnt' => '',
		'org_artcl_id' => '',
		'urg_yn' => '긴급여부',
		'internet_only_yn' => '',
		'frnoti_yn' => '',
		'embg_yn' => '엠바고여부',
		'sns_yn' => '',
		'top_yn' => '',
		'del_yn' => '삭제여부',
		'os_type' => '',
		'inputr_id' => '입력자아이디',
		'input_dtm' => '입력일시',
		'inputr_nm' => '입력자명',
		'updtr_id' => '수정자아이디',
		'updt_dtm' => '수정일시',
		'updtr_nm' => '수정자명',
		'grphc_count' => '그래픽매칭갯수',
		'video_count' => '영상매칭갯수',
		'cvcount' => '',
		'cgcount' => '',
		'attc_file_count' => '',
		'ord_gt_count' => '',
		'ord_gc_count' => '',
		'ord_vt_count' => '',
		'ord_vc_count' => '',
		//큐시트 관련
		'rd_id' => '큐시트Id',
		'rd_seq' => '큐시트시퀀스',
		'ch_div_nm' => '채널명',
		'artcl_id' => '기사ID',
		'artcl_frm_nm' => '기사형식',
		'artcl_fld_nm' => '기사분야',
		'artcl_titl' => '기사제목',
		'artcl_ctt' => '기사내용',
		'rptr_id' => '',
		'rptr_nm' => '기자명',
		'dept_nm' => '부서명',
		'apprv_div_nm' => '승인구분',
		'apprv_dtm' => '승인일시',
		'apprvr_nm' => '승인자명',
		'inputr_nm' => '입력자',
		'input_dtm' => '입력일시',
		'updtr_nm' => '수정자',
		'updt_dtm' => '수정일시'
	);

	$ex = array('artcl_id','issu_seq','artcl_ord','brdc_cnt','org_artcl_id','os_type');
	$field_form = array();
	foreach($data['data'] as $field=>$d){
		if( is_array($d) || strstr($field, '_cd') || in_array($field, $ex) || empty($name_field[$field]) )  {
			continue;
		}else if($field == 'artcl_ctt'){
			//$value = addslashes($d);
			//$value = str_replace("\r", '', str_replace("\n", '\\n', $value));
			//array_push($field_form, "{xtype:'textarea', readOnly: true, fieldLabel:'".$name_field[$field]."', value:'".$value."', height : 400,listeners: {afterrender:function(self){self.getEl().setStyle({fontSize:'13px'})}} }");
		}else{
			$value = addslashes($d);
			$value = str_replace("\r", '', str_replace("\n", '\\n', $value));
			array_push($field_form, "{xtype:'textfield', readOnly: true, fieldLabel:'".$name_field[$field]."', value:'".$value."'}");
		}
	}



	$fields = join(',', $field_form);

	echo json_encode(array(
		'success' => $r_data['result']['success'],
		'total' => $r_data['data']['totalcount'],
		'data' => $r_data['data']['record']
	));
}
catch(Exception $e)
{
echo 'err:'.$e->getMessage();

}
?>