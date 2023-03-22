<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lang.php');

$artcl_id = $_POST['artcl_id'];
$data = json_decode($_POST['data'], true);

$name_field = array(
	'artcl_id' => _text('MN02179'),//'기사아이디',
	'ch_div_nm' => '',
	'artcl_frm_nm' => _text('MN00310'),//'기사형식명',//형식
	'artcl_fld_nm' => _text('MN02113'),//'기사분야명',//대분류
	'artcl_titl' => _text('MN00249'),//'기사제목',//제목
	'artcl_ctt' => _text('MN02103'),//'기사내용',//내용
	'dept_nm' => _text('MN00181'),//'부서명',//부서
	'artcl_reqd_sec' => '',
	'artcl_div_nm' => '',
	'issu_seq' => '',
	'apprv_div_nm' => _text('MN00138'),//'승인구분명',//상태
	'artcl_ord' => '',
	'brdc_cnt' => '',
	'org_artcl_id' => '',
	'urg_yn' => _text('MN02180'),//'긴급여부',
	'internet_only_yn' => '',
	'frnoti_yn' => '',
	'embg_yn' => _text('MN02181'),//'엠바고여부',
	'sns_yn' => '',
	'top_yn' => '',
	'del_yn' => _text('MN00133'),//'삭제여부',
	'os_type' => '',
	'inputr_id' => _text('MN02149'),//'입력자아이디',//등록자 아이디
	'input_dtm' => _text('MN02001'),//'입력일시',//등록일시
	'inputr_nm' => _text('MN02150'),//'등록자 이름',
	'updtr_id' => _text('MN02182'),//'수정자 아이디',
	'updt_dtm' => _text('MN02184'),//'수정일시',
	'updtr_nm' => _text('MN02183'),//'수정자 이름',
	'grphc_count' => _text('MN02116'),//'그래픽매칭갯수',//그래픽수
	'video_count' => _text('MN02115'),//'영상매칭갯수',//영상수
	'cvcount' => '',
	'cgcount' => '',
	'attc_file_count' => '',
	'ord_gt_count' => '',
	'ord_gc_count' => '',
	'ord_vt_count' => '',
	'ord_vc_count' => '',
	//큐시트 관련
	'rd_id' => _text('MN02185'),//'큐시트Id',//런다운 ID
	'rd_seq' => _text('MN02186'),//'큐시트시퀀스',//런다운 시퀀스
	'ch_div_nm' => _text('MN01071'),//'채널명',//채널
	'artcl_id' => _text('MN02179'),//'기사 ID',
	'artcl_frm_nm' => _text('MN00310'),//'기사형식',//형식
	'artcl_fld_nm' => _text('MN02113'),//'기사분야',//대분류
	'artcl_titl' => _text('MN00249'),//'기사제목',//제목
	'artcl_ctt' => _text('MN02103'),//'기사내용',//내용
	'rptr_id' => '',
	'rptr_nm' => _text('MN02114'),//'기자명',//기자
	'dept_nm' => _text('MN00181'),//'부서명',//부서
	'apprv_div_nm' => _text('MN00138'),//'승인구분',//상태
	'apprv_dtm' => _text('MN02187'),//'승인일시',
	'apprvr_nm' => _text('MN02188'),//'승인자명',
	'inputr_nm' => _text('MN02150'),//'입력자',//'등록자 이름',
	'input_dtm' => _text('MN02001'),//'입력일시',//등록일시
	'updtr_nm' => _text('MN02183'),//'수정자',
	'updt_dtm' => _text('MN02184')//'수정일시'
);

$ex = array('artcl_id','issu_seq','artcl_ord','brdc_cnt','org_artcl_id','os_type');
$field_form = array();
foreach($data['data'] as $field=>$d){
	if( is_array($d) || strstr($field, '_cd') || in_array($field, $ex) || empty($name_field[$field]) )  {
		continue;
	}else if($field == 'artcl_ctt'){
		$value = addslashes($d);
		$value = str_replace("\r", '', str_replace("\n", '\\n', $value));
		array_push($field_form, "{xtype:'textarea', readOnly: true, fieldLabel:'".$name_field[$field]."', value:'".$value."', height : 400,listeners: {afterrender:function(self){self.getEl().setStyle({fontSize:'13px'})}} }");
	}else{
		$value = addslashes($d);
		$value = str_replace("\r", '', str_replace("\n", '\\n', $value));
		array_push($field_form, "{xtype:'textfield', readOnly: true, fieldLabel:'".$name_field[$field]."', value:'".$value."'}");
	}
}



$fields = join(',', $field_form);

?>

(function(records){
	var win = new Ext.Window({
		id: 'requestDetail',
		cls: 'change_background_panel',
		title: _text('MN02133'),//'기사 상세보기'
		modal: true,
		//width: '60%',
		autoScroll: true,
		height: 600,
		width: 600,
		layout:	'fit',
		border: false,
		closable  : false,
		items: [{
			xtype: 'form',
			frame: false,
			padding : 10,
			border: false,
			buttonAlign : 'center',
			labelWidth: 80,
			defaults: {
				labelStyle: 'text-align:left;',
				labelSeparator: '',
				anchor: '98%'
			},
			autoScroll: true,
			items:[<?=$fields?>],
			listeners : {
				afterrender : function(self){}
			}
		}],
		buttonAlign : 'center',
		buttons : [{
			text : '<span style="position:relative;top:1px;"><i class="fa fa-close" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00031'),//close
			scale: 'medium',
			handler : function(self, e){
				win.destroy();
				Ext.getBody().unmask();
			}
		}]
	});
	win.show();

})()