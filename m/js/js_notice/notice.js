var ua = navigator.userAgent;
var checker = {
	iphone: ua.match(/(iPhone|iPod|iPad)/),
	blackberry: ua.match(/BlackBerry/),
	android: ua.match(/Android/)
};

function fn_reload_list_notice(av_obj){
	showModal();
	getJsonData({
		url : '/store/notice/get_list.php'
		,type : 'post'
		,dataType : 'json'
		,parameter : av_obj
		,complete : function(result)
		{
			$('#contentBody').dataToListNotice(result);
			var movCategory = $('#movCategory');
			var v_new_notice_cnt = result.new_total;
			var newTotalCntExists = $('#btn_notice_page').find('.new_total_cnt').length;
			if(newTotalCntExists == 0 && v_new_notice_cnt != 0){
				$('#btn_notice_page').append('<div class="new_total_cnt ui-li-count">' + v_new_notice_cnt + '</div>');
			} else if(newTotalCntExists == 1 && v_new_notice_cnt != 0) {
				$('#btn_notice_page').find('.new_total_cnt').html('' + v_new_notice_cnt + '');
			} else {
				$('#btn_notice_page').find('.new_total_cnt').remove();
			}
			movCategory.trigger("create");
			hideModal();
		}
	});
}

// 컨텐츠 리스트에 데이터 삽입하기
$.fn.dataToListNotice = function(json)
{
	var
		scr = ''
		,obj_basic = json.data
	;

	scr += '<table  data-mode="reflow" class="ui-responsive">';
	scr += '<thead><tr>';
	scr += '<th data-priority="1" class="display_none">Id</th>';
	scr += '<th data-priority="persist">Title</th>';
	scr += '<th data-priority="2">Type</th>';
	scr += '<th data-priority="3" style="width: 20%;"><abbr>Date</abbr></th>';
	scr += '<th data-priority="4">Writer</th></tr></thead><tbody>';

	for (var i in obj_basic)
	{
		var notice_type = '';
		var created_date = '';
		var from_user_id = '';
		var read_flag = '';
		if(obj_basic[i]['notice_type']) {
			notice_type = obj_basic[i]['notice_type'];
		}
		if(obj_basic[i]['created_date']) {
			created_date = stringToDate(obj_basic[i]['created_date']);
		}
		if(obj_basic[i]['from_user_id']) {
			from_user_id = obj_basic[i]['from_user_id'];
		}
		if(obj_basic[i]['read_flag'] == 0){
			//read_flag = '<img src="/led-icons/icon_new_v2.png">';
			read_flag = '<span class="fa-stack " style="position:relative;height: 17px;margin-top: -9px;"><i class="fa fa-certificate fa-stack-1x" style="font-size:17px;color:#ff6600;margin-left: 3px;"></i><strong class="fa fa-inverse fa-stack-1x fa-text" style="position:relative;font-size:6px;margin-left:3px;">N</strong></span>';
		}else{
			read_flag = '';
		}
		scr += '<tr class="notice_items">';
		scr += '<td class="notice_id display_none">'+obj_basic[i]['notice_id']+'</td>';
		scr += '<td style="text-align: left;"><span href="" data-rel="external">'+obj_basic[i]['notice_title']+'</span>'+read_flag+'</td>';
		scr += '<td>'+notice_type+'</td>';
		scr += '<td>'+created_date+'</td>';
		scr += '<td>'+from_user_id+'</td>';
		scr += '</tr>';

	}

	scr += '</tbody></table>';
	$(this).html(' ');
	$(this).html(scr);
	$(this).trigger("create");
}

function fn_reload_details_notice(av_obj){
	showModal();
	getJsonData({
		url : 'inc/get_list_notice.php'
		,type : 'post'
		,dataType : 'json'
		,parameter : av_obj
		,complete : function(result)
		{
			//$('.page_content').dataToDetailsNotice(result);
			$('#popup_edit_notice_content').viewAddNotice("edit", result);
			hideModal();
		}
	});
}

$('body').off('click', '.tab_list_notice_view').on('click', '.tab_list_notice_view', fn_list_notice);			
function fn_list_notice(){
	param = new Object();
	search_text = $('#btn_search_notice').parentsUntil('.blind').find('input[name=search_q]').val();
	param = {
		start: 0,
		limit: 20,
		type: 'admin_list',
		search_text: search_text
	}
	fn_reload_list_notice(param);
}

$('body').off('click', '.tab_list_notice_management').on('click', '.tab_list_notice_management', fn_list_admin_notice);			
function fn_list_admin_notice(){
	param = new Object();
	param = {
		start: 0,
		limit: 20,
		type: 'admin_list'
	}
	//fn_reload_list_notice(param);
}

$('body').off('click', '.notice_items').on('click', '.notice_items', fn_view_details_notice);
function fn_view_details_notice(){
	var notice_id = $(this).children('.notice_id').text();

	param = new Object();
	param = {
		action:'user'
		,type:'edit'
		,notice_id:notice_id
	}
	fn_reload_details_notice(param);
}

function readURL(input) {
	if (input.files && input.files[0])
	{
		var reader = new FileReader;
		var image  = new Image();
		reader.onload = function (e) {
			image.src    = e.target.result;
			image.onload = function(e) {
				$('#blah').attr('src', e.target.src);
				$('#blah').css('display', 'block');
				//$('#blah').css('margin-top','10px');
			}
		}
		reader.onloadend = function(e){
			// get EXIF data
			var exif = EXIF.readFromBinaryFile(new BinaryFile(atob(e.target.result.split(',')[1])));
			var exif_date = '';
			
		};
		reader.readAsDataURL(input.files[0]);
	}
}

$('body').off('click', '#btn_add_notice').on('click', '#btn_add_notice', fn_add_notice);
function fn_add_notice(){
	$('#popup_edit_notice_content').viewAddNotice("add",null);
}

// 컨텐츠 리스트에 데이터 삽입하기
$.fn.viewAddNotice = function(mode, json)
{
	var scr = '';
	var obj_basic;
	var readonly = '';
	var notice_title = '';
	var notice_type = ' ';
	var notice_type_all = ' ';
	var notice_type_group = ' ';
	var notice_type_user = ' ';
	var to_user_names = ' ';
	var to_user_ids = '';
	var to_group_ids = '';
	var start_date = '';
	var end_date = '';
	var notice_id = ' ';
	var content_text ='';
	var mode_type ='';
	var h_title ='';
	var disabled ='';

	if (mode == 'edit'){
		obj_basic = json.data;
		if (json.readonly == 1){
			readonly = 'readonly' ;
			disabled = 'disabled' ;
		} else {
			readonly = ' ';
			disabled = ' ' ;
		}
		var regex = /<br\s*[\/]?>/gi;
		if (obj_basic.notice_content_c){
			content_text = (obj_basic.notice_content_c.replace(regex, "\n"));
		}
		notice_id = obj_basic.notice_id;
		notice_title = obj_basic.notice_title;
		notice_type = obj_basic.notice_type;
		if (obj_basic.to_user_names){
			to_user_names = obj_basic.to_user_names;
		}
		if (obj_basic.to_user_ids){
			to_user_ids = obj_basic.to_user_ids;
		}
		if (obj_basic.to_group_ids){
			to_group_ids = obj_basic.to_group_ids;
		}
		start_date = stringToDate(obj_basic.notice_start);
		end_date = stringToDate(obj_basic.notice_end);
		mode_type = 'edit';
		if (obj_basic.notice_type == 'user'){
			notice_type_user = 'checked';
		} else if (obj_basic.notice_type == 'group'){
			notice_type_group = 'checked';
		} else {
			notice_type_all = 'checked';
		}
		h_title = 'Edit notice content';
	} else if (mode == 'add'){
		var currentdate = new Date();
		var res = currentdate.toISOString().slice(0,10).replace(/-/g,"");
		notice_type_all = 'checked';
		mode_type = 'insert';
		start_date = stringToDate(res);
		end_date = stringToDate(res);
		h_title = 'Add new notice content';
		notice_type = 'all';
	}

	scr += '<form method="post" enctype="multipart/form-data" data-ajax="false" class="form_notice" id="form_add_notice_content" name="form_add_notice_content">';
	scr += '	<div style="text-align:center;">'+h_title+'</div>';
	scr += '	<input name="fileup" type="file" onchange="readURL(this);" data-inline="true" id="fileup" />';
	//scr += '	<div data-role="button"  class="btn_select">back</div>';
	scr += '	<div  class="meta_line">';

	scr += '	<div class="form_line">';
	scr += '		<div class="left text" >Title:</div>';
	//scr += '		<div class="line_upload"></div>';
	scr += '		<div class="input_form">';
	scr += '			<input '+readonly+' type="text" name="title" caption="title" value="'+notice_title+'"/>';
	scr += '		</div>';
	scr += '	</div>';

	scr += '	<div class="form_line">';
	scr += '		<div class="left text" >Type:</div>';
	//scr += '		<div class="line_upload"></div>';	
	scr += '				<input type="radio" data-role="none" '+notice_type_all+' name="target_type_select" value="all" '+disabled+'>';
	scr += '				<label for="all">Alls</label>';
	scr += '				<input type="radio" data-role="none" '+notice_type_group+' name="target_type_select" value="group" '+disabled+'>';
	scr += '				<label for="group">Group</label>';
	scr += '				<input type="radio" data-role="none" '+notice_type_user+' name="target_type_select" value="user" '+disabled+'>';
	scr += '				<label for="user">User</label>';
	scr += '			<br><div class="input_form form_select_wrap">';
	scr += '			</div>';
	scr += '	</div>';

	scr += '	<div class="form_line edit_ui_field_contain">';
	scr += '		<div class="left text" >Target:</div>';
	//scr += '		<div class="line_upload"></div>';
	scr += '		<div class="input_form">';
	scr += '			<input readonly type="text" name="to_list" caption="to_list" value="'+to_user_names+'"/>';
	//scr += '			<i class="fa fa-fw fa-lg fa-times remove_target_icon" style="float: right; margin-top: -42px;"></i>';
	scr += '		</div>';
	scr += '	</div>';

	scr += '	<div class="form_line display_none">';
	scr += '		<div class="input_form">';
	scr += '			<input type="text" name="target_type" caption="target_type" value="'+notice_type+'"/>';
	scr += '		</div>';
	scr += '	</div>';

	scr += '	<div class="form_line display_none">';
	scr += '		<div class="input_form">';
	scr += '			<input type="text" name="to_user_ids" caption="to_user_ids" value="'+to_user_ids+'"/>';
	scr += '		</div>';
	scr += '	</div>';

	scr += '	<div class="form_line display_none">';
	scr += '		<div class="input_form">';
	scr += '			<input type="text" name="to_group_ids" caption="to_group_ids" value="'+to_group_ids+'"/>';
	scr += '		</div>';
	scr += '	</div>';

	scr += '	<div class="form_line">';
	scr += '		<div class="left text" >Notice Period:</div>';
	//scr += '		<div class="line_upload"></div>';
	scr += '		<div class="input_form select_create_date">';
	scr += '			<input '+readonly+' type="date" name="start_date" caption="start_date" value="'+start_date+'"/>';
	scr += '			<i class="fa fa-minus"></i>';
	scr += '			<input '+readonly+' type="date" name="end_date" caption="end_date" value="'+end_date+'"/>';
	//scr += '			<div class="select_create_date"><input class="select_create_date_from" type="date" required name="date" />';
	//scr += '			<i class="fa fa-minus fa-fw"></i><input class="select_create_date_to" type="date" required name="date"/> </div>';
	scr += '		</div>';
	scr += '	</div>';

	scr += '	<div class="form_line display_none">';
	scr += '		<div class="left text" >Attach</div>';
	//scr += '		<div class="line_upload"></div>';
	scr += '		<div class="input_form">';
	scr += '			<div data-role="button"  class="btn_select">Attach file</div>';
	scr += '		</div>';
	scr += '	</div>';

	scr += '	<div class="form_line display_none">';
	scr += '		<div class="left text" >Url</div>';
	//scr += '		<div class="line_upload"></div>';
	scr += '		<div class="input_form">';
	scr += '			<input type="text" name="url" caption="url" />';
	scr += '		</div>';
	scr += '	</div>';

	scr += '	<div class="form_line">';
	scr += '		<div class="left text" >Content:</div>';
	//scr += '		<div class="line_upload"></div>';
	scr += '		<div class="input_form">';
	scr += '			<textarea '+readonly+' rows="18" name="contents" caption="contents" >'+content_text+'</textarea>';
	scr += '		</div>';
	scr += '	</div>';

	scr += '	<textarea  name="contents11" class="blind" data-role="none" /></textarea>';

	scr += '	</div>';
	scr += '	<div style="text-align:center;">';
	scr += '		<span style="margin-left:auto; margin-right:auto;">';

	if (mode == 'edit'){
		if(json.readonly != 1){
			scr += '			<div data-role="button" id="btn_save_notice" class="mov_view_button" data-inline="true" onclick="fn_delete_notice(\''+notice_id+'\')">Delete</div>';
			scr += '			<div data-role="button" id="btn_save_notice" class="mov_view_button" data-inline="true" onclick="fn_save_notice(\''+mode_type+'\',\''+notice_id+'\')">Submit</div>';
		}
	}else {
		scr += '			<div data-role="button" id="btn_save_notice" class="mov_view_button" data-inline="true" onclick="fn_save_notice(\''+mode_type+'\',\''+notice_id+'\')">Submit</div>';
	}
	scr += '			<div data-role="button" id="btn_cancel_notice" data-inline="true" onclick="fn_cancel_notice();">Cancel</div>';
	scr += '		</span>';
	scr += '	</div>';

	scr += '</form>';

	$(this).html(' ');
	$(this).html(scr);
	$(this).trigger("create");
	$('#popup_edit_notice').popup("open");
	// handle event change radio button for type user
	var rad = document.form_add_notice_content.target_type_select;
	var prev = null;
	for(var i = 0; i < rad.length; i++) {
	    rad[i].onclick = function() {
	    	showModal();
	        if(this !== prev) {
	            prev = this;
	        }
	        if (this.value == 'group' ){
		        $.ajax({
					//url : '/store/get_categories.php'
					url : '/pages/menu/config/user/php/get_group.php'
					,type : 'POST'
					,data : {
							sort: 'member_group_id',
							dir: 'ASC',
							type: 'notice'
						}
					,dataType : 'json'
					,error : function()
					{
						hideModal();
						alert( 'Get Group Fail');
					}
					,success : function(result)
					{
						var res_group = $('input[name="to_group_ids"]').val().split(',');
						var v_result = result.data;
						$('div .form_select_wrap').find('select#type_group').remove().end();
						$('div .form_select_wrap').find('select#type_user').hide();
						$('<select>').attr({'id':'type_group', 'multiple':'multiple', 'data-native-menu':'false'}).appendTo('div .form_select_wrap');
						for (var i in v_result){
							if (res_group.indexOf(v_result[i].member_group_id) >= 0) {
								$('<option>').attr({'value':v_result[i].member_group_id, 'selected':'selected'}).html(v_result[i].member_group_name).appendTo('#type_group');
							} else {
								$('<option>').attr({'value':v_result[i].member_group_id}).html(v_result[i].member_group_name).appendTo('#type_group');
							}
						}
						//$('div .form_select_wrap').trigger("create");
						hideModal();
					}
				});
		    } else if (this.value == 'user'){
		    	$.ajax({
					//url : '/store/get_categories.php'
					url : '/pages/menu/config/user/php/get.php'
					,type : 'POST'
					,data : {
							start: 0,
							limit:9999999999,
							sort: 'user_id',
							dir: 'ASC'
						}
					,dataType : 'json'
					,error : function()
					{
						hideModal();
						alert( 'Get User Fail');
					}
					,success : function(result)
					{
						var res_user = $('input[name="to_user_ids"]').val().split(',');
						var v_result = result.data;
						$('div .form_select_wrap').find('select#type_user').remove().end();
						$('div .form_select_wrap').find('select#type_group').hide();
						$('<select>').attr({'id':'type_user', 'multiple':'multiple', 'data-native-menu':'false'}).appendTo('div .form_select_wrap');
						for (var i in v_result){
							if (res_user.indexOf(v_result[i].member_id) >= 0) {
								$('<option>').attr({'value':v_result[i].member_id, 'selected':'selected'}).html(v_result[i].user_id).appendTo('#type_user');
							} else {
								$('<option>').attr({'value':v_result[i].member_id}).html(v_result[i].user_id).appendTo('#type_user');
							}
						}
						//$('div .form_select_wrap').trigger("create");
						hideModal();
					}
				});
		    } else {
		    	$('div .form_select_wrap').find('select').remove().end();
				$('input[name="to_group_ids"]').val('');
				$('input[name="to_user_ids"]').val('');
				$('input[name="to_list"]').val('');
				$('input[name="target_type"]').val('all');
		    	//$('div .form_select_wrap').trigger("create");
		    	hideModal();
		    }
	    };
	}
}

$('body').on('change', '#type_group', function(){
	var value = $(this).val();
	$('input[name="to_group_ids"]').val(value);
	if ($('input[name="to_user_ids"]').val() == '' && $('input[name="to_group_ids"]').val() != ''){
		$('input[name="target_type"]').val('group');
	}
	var text = '';
	var res_group = $('input[name="to_group_ids"]').val().split(',');
	$("#type_group option").each(function(){
		if (res_group.indexOf($(this).val()) >= 0) {
			text += $(this).text()+',';
		}	
	});
	var res_user = $('input[name="to_user_ids"]').val().split(',');
	$("#type_user option").each(function(){
		if (res_user.indexOf($(this).val()) >= 0) {
			text += $(this).text()+',';
		}	
	});
	$('input[name="to_list"]').val(text);
});
$('body').on('change', '#type_user', function(){
	var value = $(this).val();
	$('input[name="to_user_ids"]').val(value);
	if ($('input[name="to_user_ids"]').val() != ''){
		$('input[name="target_type"]').val('user');
	}
	var text = '';
	var res_group = $('input[name="to_group_ids"]').val().split(',');
	$("#type_group option").each(function(){
		if (res_group.indexOf($(this).val()) >= 0) {
			text += $(this).text()+',';
		}	
	});
	var res_user = $('input[name="to_user_ids"]').val().split(',');
	$("#type_user option").each(function(){
		if (res_user.indexOf($(this).val()) >= 0) {
			text += $(this).text()+',';
		}	
	});
	$('input[name="to_list"]').val(text);
});

$('body').off('click', '.remove_target_icon').on('click', '.remove_target_icon', function(){
	$('input[name="to_group_ids"]').val('');
	$('input[name="to_user_ids"]').val('');
	$('input[name="to_list"]').val('');
});

$('#btn_search_notice').off('click').on('click', function(){
	fn_list_notice();
});

function fn_delete_notice(notice_id){
	$.ajax({
		type: "POST",
		url: "/pages/menu/config/notice/notice_del.php",
		cache: false,
		data: {
			id: notice_id
		},
		success: function(result){
			fn_list_notice();
			$('#popup_edit_notice').popup('close');
		},
		error: function(result){
		}
	});
}

//$('body').off('click', '#btn_upload').on('click', '#btn_upload', function(){
function fn_save_notice(type, notice_id){
	var formData = $("#form_add_notice_content").serializeArray();
    var indexed_array = {};

    $.map(formData, function(n, i){
        indexed_array[n['name']] = n['value'];
    });
	$.ajax({
		type: "POST",
		url: "/store/notice/get_list.php",
		cache: false,
		data: {
			type : type,
			meta : JSON.stringify(indexed_array),
			notice_id: notice_id
		},
		success: function(result){
			fn_list_notice();
			$('#popup_edit_notice').popup('close');
		},
		error: function(result){
		}
	});
}

function fn_cancel_notice(){
	fn_list_notice();
	$('#popup_edit_notice').popup('close');
}

$('body').off('click', '.btn_select').on('click', '.btn_select', function(){
	$("#fileup").click();
});

$(document).ready(function(){

			//필수값 체크
	$('#form_add_notice_content').validate({
		//debug: true,
		rules:{
			fileup:{required:true},
			title:{required:true},
		},
		messages:{
			fileup:{required:"chose file"},//파일
			title:{required:"plz type title"},//제목
		},
		submitHandler: function(form) {
		}
	})
})