<?php

include "./json/categoryOptions.php"; // list category menu
include "./json/list_bs_content_code.php"; // list bs content code

use \Proxima\models\content\UserContent;

$jsonAddress = ($site_test) ? "mov_list_json.php" : "/store/get_content.php";
$mov_view_json = ($site_test) ? "inc/mov_view_json_test.php" : "inc/mov_view_json.php";
$movCountAddress = "mov_cate_cnt_json.php";

$search_flag = '#loading';

reset($optValueArray);
$first_ud_content_id = key($optValueArray);

if(empty($first_ud_content_id)) {
	$firstUserContent = UserContent::first();
	$first_ud_content_id = $firstUserContent->get('ud_content_id');
}
?>

<!-- Search Box -->
<div data-role="content" data-theme="d" id="searchBox" class="searchBox">
	<legend class="blind">Search box form</legend>
	<dl>
		<dd class="keyword_1"><span><input type="search" id="search_text" name="search_q" value='<?=$sd[search_q]?>'/></span></dd>
		<dd class="btn_search"><button type="submit" id="btn_ge_search"><span><img src="img/searchBox_1.png" class="b1"/></span></button></dd>
		<!-- <dd class="btn_search"><button type="submit" id="btn_re_search"><span><img src="img/searchBox_2.png" class="b1"/></span></button></dd> -->
		<dd class="btn_search"><a id="btn_re_search" href="#popup_advanced_search" data-transition="flow" data-role="button" data-inline="true" data-rel="popup" style="display: block;margin: .5em 0;position: relative;"><span><img src="img/searchBox_2.png" class="b1"/></span></a></dd>
	</dl>
</div>

<!-- Media list -->
<div class="mediaList">
	<nav role="navigation" class="nav_menu ui-dropdown" id="mediaCategory">
		<ul id="movCategory" class="tab_category">
			<?php
			$i=0;
			foreach ($optValueArray as $key=>$value)
			{
				if($key == 4) continue;
				$active = (0 == $i) ? "class='active'" : "";
				echo "<li $active rel='$key'>$value<p class='li_index display_none'>$i</p></li>";
				$i++;
			}
			?>
		</ul>
	</nav>

	<ul data-role="listview" id="list"  class="type-list"></ul>

	<?php require "loadingTag.php"; ?>

</div>

<!-- views details -->
<div class="mediaDetail display_none">
	<div>
	<!-- <?=$player_tag?> -->
	</div>
	<!-- <a href="<?=$address?>" data-role="button" data-inline="true" class="test_play" >재생</a> -->
	<div class="mediaDetailTitle">
		
		<!-- <a href="" id="btn_back" data-role="button" data-inline="true" class="mov_view_button" data-icon="grid"><?=_text('MN00274')?></a> -->
		<a href="" id="btn_download" class="mov_view_button_download"><i class="fa fa-download fa-fw"></i></a>
		<h1 id="programTitle"></h1>
	</div>
	<div class="vid" id="mediaPlay">
		<!-- media player or image viewer -->
	</div>
	<section class="bd">
		<nav role="navigation" class="nav_menu ui-dropdown" id="bodyTab">
		<!--<div id="bodyTab" class="ui-dropdown"> -->
			<ul class="tab_category_view"></ul>
		</nav>
		<ul id="contentBody" class="con"></ul>
	</section>
</div>

<div data-role="popup" id="search_flag" data-shadow="false" data-corners="false" class="popup_search">Loading</div>

<div data-role="popup" id="popup_edit_metadata"  class="popup_alert_out" data-dismissible="false">
	<div class="popup_alert_in">
		<p><?=_text('MN01089')?></p>

		<div id="popup_edit_metadata_content">

		</div>
		<div style="text-align:center;">
			<span style="margin-left:auto; margin-right:auto;">
				<div data-role="button" id="btn_edit_metadata_save" data-inline="true" class="mov_view_button"><?=_text('MN00046')?></div>
				<div data-role="button" id="btn_edit_metadata_cancel" data-inline="true" ><?=_text('MN00004')?></div>
			</span>
		</div>

	</div>
</div>

<div data-role="popup" id="popup_history_edit_metadata"  class="popup_alert_out" data-dismissible="false">
	<div class="popup_alert_in">
		<p><?=_text('MN01089')?></p>

		<div id="popup_history_edit_metadata_content">

		</div>
		<div style="text-align:center;">
			<span style="margin-left:auto; margin-right:auto;">
				<div data-role="button" id="btn_history_edit_metadata_cancel" data-inline="true" ><?=_text('MN00031')?></div>
			</span>
		</div>

	</div>
</div>

<div data-role="popup" id="popup_menu_edit_last_comment" data-theme="d">
	<ul data-role="listview" data-inset="true" style="min-width:120px;" data-theme="d">
		<!--<li data-role="divider" data-theme="e">Action</li> -->
		<li><a id="delete_last_comment_btn"href="#"><?=_text('MN00034')?></a></li>
	</ul>
</div>

<div id="popup_advanced_search" data-role="popup" data-overlay-theme="a">
	<a href="#" data-rel="back" data-role="button" data-theme="a" data-icon="delete" data-iconpos="notext" class="ui-btn-right">Close</a>
	<p><?=_text('MN00059')?></p>
	<div>
		<!-- <label for="select_content_type" class="select"><?=_text('MN00279')?>:</label> -->
		<div data-role="fieldcontain">					
			<select name="select_content_type" id="select_content_type">
				<?php
				$i=0;
				foreach ($optValueArray as $key=>$value)
				{
					echo "<option value=".$key.">$value</option>";
					$i++;
				}
				?>
			</select>
		</div>
		<br>
		<!-- <legend class="select_metadata_title"><?=_text('MN00113')?></legend> -->
		<div class="select_metadata">
			<form>
				<select name="select_metadata_type" class="select_metadata_type">
				</select>
				<div class="search_metadata">
				</div>
			</form>
		</div>
		<div class="select_metadata">
			<form>
				<select name="select_metadata_type" class="select_metadata_type">
				</select>
				<div class="search_metadata">
				</div>
			</form>
		</div>
		<div class="select_metadata">
			<form>
				<select name="select_metadata_type" class="select_metadata_type">
				</select>
				<div class="search_metadata">
				</div>
			</form>
		</div>
	</div>
	<div style="text-align:center;">
		<span style="margin-left:auto; margin-right:auto;">
			<div data-role="button" id="advanced_search_submit" data-inline="true" ><?=_text('MN00059')?></div>
		</span>
		<span style="margin-left:auto; margin-right:auto;">
			<div data-role="button" id="advanced_search_clear" data-inline="true" ><?=_text('MN02096')?></div>
		</span>
	</div>
</div>

<script type="text/javascript">

	var formData = new Object();
	var
		mediaCategory = $('#mediaCategory')
		,listTypes = $('#listType a')
		,list = $('#list')
		,totalCount = $('#more')
		,moreCount = 20
		,search_flip = $('#search_flip')
		,test_list = $('#test_list')
	;
	var nomore = false;
	var search_text = $('#search_text');
	var search_q = '';
	var gv_count_start = 0;
	var START_INDEX= 0;
	var LIMIT_INDEX= 20;
	var gv_category_full_path = '';
	var gv_option_attr_value = [];
	var bs_content_info = {};
	<?php foreach ($bs_content_info as $key=>$value){?>
		bs_content_info[<?php echo $key ?>] = '<?php echo $value ?>';
	<?php }?>

	function fn_reload_media_list(av_obj){
		var v_obj = new Object();
		v_obj.search_q = av_obj.search_q;
		if (av_obj.start){
			v_obj.start = av_obj.start;
		} else {
			v_obj.start = START_INDEX;
		}
		if (av_obj.ud_content_id){
			v_obj.ud_content_id = av_obj.ud_content_id;
		} else {
			v_obj.ud_content_id = <?=$first_ud_content_id?>;
		}
		if (av_obj.limit){
			v_obj.limit = av_obj.limit;
		} else {
			v_obj.limit = LIMIT_INDEX;
		}
		if (av_obj.action){
			v_obj.action = av_obj.action;
		} else {
			v_obj.action = '';
		}
		if (av_obj.params){
			v_obj.params = JSON.stringify(av_obj.params);
		} else {
			v_obj.params = '';
		}
		if (v_obj.search_q)
		{
			formData = {
				action : v_obj.action
				,params : v_obj.params
				,status_filter : '1'
				,archive_filter : '1'
				,sort : 'content_id'
				,dir : 'DESC'
				,limit : v_obj.limit
				,start : v_obj.start
				,task : 'listing'
				,ud_content_id : v_obj.ud_content_id
				,filter_type: av_obj.filter_type
				,filter_value: av_obj.filter_value
			}
			formData.search_q = v_obj.search_q;
		}
		else
		{
			formData = {
				action : v_obj.action
				,params : v_obj.params
				,status_filter : '1'
				,archive_filter : '1'
				,sort : 'content_id'
				,dir : 'DESC'
					
				,limit : v_obj.limit
				,start : v_obj.start
				,task : 'listing'
				,ud_content_id : v_obj.ud_content_id
				,filter_type: av_obj.filter_type
				,filter_value: av_obj.filter_value
			}
		}
		moreCount = parseInt(v_obj.limit) + parseInt(v_obj.start);
		getMediaList({
			url : '<?=$jsonAddress?>'
			,target : list
			,data : formData
			,method : 'html'
		});

		var v_category_id = 0;
		if (av_obj.filter_type == 'category'){
			var str_arr = av_obj.filter_value.split("/");
			v_category_id = str_arr[str_arr.length - 1];
		} else {
			v_category_id = 0;
		}

		var param = {
			ud_content_id: v_obj.ud_content_id, // hardcoding 4000295: need check more
			action: 'get-folders',
			category_id: v_category_id
		};
		fn_get_categories(param);
	}

	function getMediaList(opts, complete){
		var target = $(opts.target);
		showModal();
		loadingSw.hide($('#more'));
		loadingSw.show($('<?=$search_flag?>'));

		function initAjax(){
			$.ajax({
				url : 		opts.url,
				type : 		'POST',
				data : 		opts.data,
				dataType : 	'json',
				timeout : 	200000,
				error : 	function(){
					alert('Error loading data');
				},
				success : 	function(av_result){
					//$.mobile.loading('hide');
					generateElement(av_result);
					fn_get_count(av_result);
					fn_view_notice_count(av_result);
					hideModal();
				}
			});
		}

		function generateElement(obj){
			var scr = '';
			var v_length = 0;
			if (obj.results != null){
				v_length = obj.results.length
			}
			if (Boolean(obj.success) == true && v_length > 0){
				if (v_length < 4){
					$('#toTop').css('visibility', 'hidden');
				}
				for (var i in obj.results) {
					scr += '<li class="tap_list" id="'+obj.results[i].content_id+'"  content_id="'+obj.results[i].content_id+'"  >';
					scr += '<a href="" class="test_list">';
					scr += '<p class="display_none text_content_id">'+obj.results[i].content_id+'</p>';
					scr += '<p class="display_none text_meta_table_id">'+obj.results[i].ud_content_id+'</p>';
					scr += '<p class="display_none text_bs_content_id">'+obj.results[i].bs_content_id+'</p>';
					scr += '<p class="display_none text_is_group">'+obj.results[i].is_group+'</p>';
					scr += '<dl class="list_line">';
					scr += '<dt data-native-menu="false">';

					scr += '<div class="table_icon" ><div class="icons_img">'+obj.results[i]['icons']+'</div></div>';

					switch(bs_content_info[obj.results[i].bs_content_id])
					{
						// video: source
						case 'MOVIE':
						case 'SEQUENCE':
							scr += '<img src="'+obj.results[i]['lowres_web_root']+'/'+obj.results[i]['thumb']+'" alt="Video" class="tumbnail_img" />';
							break;
						// infographic
						case 'IMAGE':
							scr += '<img src="'+obj.results[i]['lowres_web_root']+'/'+obj.results[i]['thumb']+'" alt="Video" class="tumbnail_img" />';
							break;
						// sound
						case 'SOUND':
							scr += '<img src="'+obj.results[i]['lowres_web_root']+'/'+obj.results[i]['thumb']+'" alt="Video" class="tumbnail_img" />';
							break;
					}
					scr += '</dt>';
					scr += '<dd style="table-layout:fixed">';

					switch(bs_content_info[obj.results[i].bs_content_id])
					{
						case 'MOVIE':
						case 'SEQUENCE': //video
							scr += '<strong ><div class="nobr_list"><nobr>' + checkText(obj.results[i]['title']) + '</nobr></div></strong>';
							scr += '<div class="inf">';
							scr += (obj.results[i]['user_nm']) ? ' <span><?=_text('MN00189')?> : ' + obj.results[i]['user_nm'] + '</span>' : '';
							scr += (obj.results[i]['created_date']) ? '<span><?=_text('MN00354')?> : ' + stringToDate(obj.results[i]['created_date'].substr(0,8)) + '</span>' : '';
						break;
						case 'IMAGE': //image
							scr += '<strong ><div class="nobr_list"><nobr>' + checkText(obj.results[i]['title']) + '</nobr></div></strong>';
							scr += '<div class="inf">';
							scr += (obj.results[i]['user_nm']) ? ' <span><?=_text('MN00189')?> : ' + obj.results[i]['user_nm'] + '</span>' : '';
							scr += (obj.results[i]['created_date']) ? '<span><?=_text('MN00354')?> : ' + stringToDate(obj.results[i]['created_date'].substr(0,8)) + '</span>' : '';
						break;
						case 'SOUND': //audio
							scr += '<strong ><div class="nobr_list"><nobr>' + checkText(obj.results[i]['title']) + '</nobr></div></strong>';
							scr += '<div class="inf">';
							scr += (obj.results[i]['user_nm']) ? ' <span><?=_text('MN00189')?> : ' + obj.results[i]['user_nm'] + '</span>' : '';
							scr += (obj.results[i]['created_date']) ? '<span><?=_text('MN00354')?> : ' + stringToDate(obj.results[i]['created_date'].substr(0,8)) + '</span>' : '';
						break;
					}
					scr += '</div>';
					scr += '</dd>';
					scr += '</dl>';
					scr += '</a>';
					scr += '</li>';
				}
				eval('target.' + opts.method + '(scr)');
				if( window.location.hash == '' )
				{
					scrollWin();
				}
				else
				{
					window.scrollTo(0, window.location.hash.replace('#', '').split('a')[0]);
				}
				nomore = false;
			} else {
				nomore = true;
			}

			if ((opts.method !== 'append' && nomore == true)){
				target.html('<li class="empty"><?=_text('MSG00142')?></li>');
				target.trigger("create");
			}

			if (formData.limit > v_length || formData.limit < LIMIT_INDEX){
				nomore = true;
			}else{
				loadingSw.show($('#more'));
			}

			loadingSw.hide($('<?=$search_flag?>'));

			if (opts.complete){
				opts.complete();
			}

			if (moreCount >= obj.total && obj.total>=LIMIT_INDEX){
				gv_count_start = obj.total;
			} else {
				gv_count_start = moreCount;
			}

			totalCount.text('View More [ ' +moreCount + ' / ' + obj.total + ' ]' );
			setTimeout('hideModal()', 1500);
		}

		initAjax();

		function fn_get_count(av_result){
			var movCategory = $('#movCategory');
			movCategory.children().each(function(){
				var elementExists = $(this).find('.cnt').length;
				var temp_ud_content_id = $(this).attr('rel');
				var cnt = '0';
				if (av_result['results'][temp_ud_content_id] != null){
					cnt = av_result['results'][temp_ud_content_id]['new_cnt'];
				}
				if (elementExists == 0){
					//$(this).append(' <br><span class="cnt">(' + cnt + ')</span>');
				} else {
					//$(this).find('.cnt').html('(' + cnt + ')');
				}
			});
		}

		function fn_view_notice_count(av_result){
			var movCategory = $('#movCategory');
			var new_total_cnt = 0;
			movCategory.children().each(function(){
				var elementExists = $(this).find('.new_cnt').length;
				var temp_ud_content_id = $(this).attr('rel');
				var cnt = '0';

				if (av_result['results'][temp_ud_content_id] != null && av_result['results'][temp_ud_content_id]['new_cnt'] != null){
					cnt = av_result['results'][temp_ud_content_id]['new_cnt'];
				} else {
					cnt = 0;
				}
				if (cnt != 0){
					if (elementExists == 0){
						$(this).append('<div class="new_cnt ui-li-count">' + cnt + '</div>');
						//$(this).append('<span class="new_cnt ui-li-count ui-btn-up-b ui-btn-corner-all">' + cnt + '</span>');
					} else {
						$(this).find('.new_cnt').html('' + cnt + '');
					}
				} else {
					if (elementExists == 1){
						$(this).find('.new_cnt').remove();
					}
				}
				new_total_cnt += cnt;
			});

			var newTotalCntExists = $('#video_nav').find('.new_total_cnt').length;
			if(newTotalCntExists == 0 && new_total_cnt != 0){
				$('#video_nav').append('<div class="new_total_cnt ui-li-count">' + new_total_cnt + '</div>');
			} else if(newTotalCntExists == 1 && new_total_cnt != 0) {
				$('#video_nav').find('.new_total_cnt').html('' + new_total_cnt + '');
			} else {
				$('#video_nav').find('.new_total_cnt').remove();
			}
			movCategory.trigger("create");
		}
	}

	function fn_reload_view_details(av_obj){
		showModal();
		getJsonData({
			url : '<?=$mov_view_json?>'
			,type : 'post'
			,dataType : 'json'
			,parameter : av_obj
			,complete : function(result)
			{
				var file_type = '' ;
				if (result.stream_file){
					var n = result.stream_file.indexOf(".");
					file_type = result.stream_file.substring(n, result.stream_file.length);
				}
				$('#btn_download').attr("href", '/data/'+ result.stream_file);
				$('#btn_download').attr("download", result.title+file_type);
				$('#bodyTab').dataToCategory(result);
				$('#contentBody').dataToList(result);
				$('#programTitle').text(result.title);

				if (result.is_group == 'G' || result.is_group == 'C'){
					param = new Object();
					param = {
						content_id: result.content_id,
						is_group: result.is_group,
						parent_content_id : result.parent_content_id,
						bs_content_id : result.bs_content_id
					}
					$('#group_list_content').fn_group_list_content(param);
				} else {
					$(document).off("swipeleft swiperight", "#mediaPlay");
				}

				$('#mediaPlay').generateMediaElement(result);
				switch(bs_content_info[result.bs_content_id])
					{
						case 'MOVIE':
						case 'SEQUENCE':
							$('#play_video source').attr("src", '/data/'+ result.stream_file);
							break;
						case 'SOUND':
							$('#play_audio source').attr("src", '/data/'+ result.stream_file);
							break;
						case 'IMAGE':
							$('#proxy_image').attr("src", '/data/'+ result.stream_file);
							break;
					}
				//$('#play_video').attr("src", '/data/'+ result.stream_file);
				$('#popup_edit_metadata_content').dataToListEdit(av_obj, result);
				$('#searchBox').hide();
				$('.mediaList').hide();
				$('.mediaDetail').show();
				hideModal();
				$.mobile.silentScroll(0);
				$("#list_content_button_header").attr("href", "");
				//$('#toTop').css('visibility','hidden');
			}
		});
	}

	function fn_group_list_select(content_id, bs_content_id){
		param = new Object();
		param = {
			content_id : content_id,
			bs_content_id : bs_content_id
		}
		fn_reload_view_details(param);
		$('#toTop').css('visibility','hidden');
	}

	$.fn.fn_group_list_content = function(av_obj){
		var element = $(this);
		param = new Object();
		var v_content_id = av_obj.content_id;
		if (av_obj.is_group == 'C'){
			v_content_id = av_obj.parent_content_id;
		}
		param = {
			content_id: v_content_id,
			bs_content_id : av_obj.bs_content_id
		}
		getJsonData({
			url: '/store/group/get_child_list.php'
			,type : 'post'
			,dataType : 'json'
			,parameter : param
			,cache: true
			,complete : function(result)
			{
				var scr = '';
				var next_content_id = 0;
				var back_content_id = 0;
				for (var j in result.data){
					//scr += '<dl>';
					if (result.data[j].content_id == av_obj.content_id){
						next_index = parseInt(j) + 1;
						back_index = parseInt(j) - 1;
						if (j == result.data.length - 1) {
							next_index = 0;
						} else if (j == 0){
							back_index = result.data.length - 1;
						}
						next_content_id = result.data[next_index].content_id;
						back_content_id = result.data[back_index].content_id;
					}
					scr += '<div class="thumb" style = "width: 120px;padding: .3em .3em .3em .3em;float: left;" onClick="fn_group_list_select('+result.data[j].content_id+', '+result.data[j].bs_content_id+')"><img class="thumb_img_storyboard_dragable" src="/data/'+result.data[j].thumb+'" width="100"></div>';
					//scr += '</dl>';
				}
				element.html(scr);
				element.trigger("create");
				$(document).off("swipeleft swiperight", "#mediaPlay").on( "swipeleft swiperight", "#mediaPlay", function( e ) {
					if ( $.mobile.activePage.jqmData( "panel" ) !== "open" ) {
						if ( e.type === "swipeleft"  ) {
							param = {
								content_id : next_content_id,
								bs_content_id : av_obj.bs_content_id
							}
							fn_reload_view_details(param);
							$('#toTop').css('visibility','hidden');
						} else if ( e.type === "swiperight" ) {
							param = {
								content_id : back_content_id,
								bs_content_id : av_obj.bs_content_id
							}
							fn_reload_view_details(param);
							$('#toTop').css('visibility','hidden');
						}
						else {
						}
					}
				});
			}
		});
	}

	$.fn.dataToCategory = function(json){
		var
			scr = ''
			,n = 0
		;

		if (json.is_group == 'G' || json.is_group == 'C'){
			var active = (n == 0) ? 'class="active"' : '';
			scr += '<li rel="' + n + '" ' + active + '><?=_text('MN00111')?>';
			scr += '<p style="display:none;"><?=_text('MN00111')?></p>';
			scr += '</li>';
			n++;
		}
		if (json.data['basic'] != ''){
			var active = (n == 0) ? 'class="active"' : '';
			scr += '<li rel="' + n + '" ' + active + '><?=_text('MN01089')?>';
			scr += '<p style="display:none;"><?=_text('MN01089')?></p>';
			scr += '</li>';
			n++;
		}
		if (json.data['comment'] != 'N'){
			var active = (n == 0) ? 'class="active"' : '';
			scr += '<li rel="' + n + '" ' + active + '><?=_text('MN01036')?>';
			scr += '<p style="display:none;"><?=_text('MN01036')?></p>';
			scr += '</li>';
			n++;
		}

		if (json.data['mediaInfo'] != ''){
			var active = (n == 0) ? 'class="active"' : '';
			scr += '<li rel="' + n + '" ' + active + '><?=_text('MN00170')?>';
			scr += '<p style="display:none;"><?=_text('MN00170')?></p>';
			scr += '</li>';
			n++;
		}

		if (json.data['history_edit_metadata'] != 'N' && json.data['history_edit_metadata'] != ''){
			var active = (n == 0) ? 'class="active"' : '';
			scr += '<li rel="' + n + '" ' + active + '><?=_text('MN02196')?>';
			scr += '<p style="display:none;"><?=_text('MN02196')?></p>';
			scr += '</li>';
			n++;
		}
		
		$(this).children('ul')
			.html(scr)
			.children().off('click').on('click', function(){
				if (!$(this).hasClass('active'))
				{
					var bd = $('#contentBody');
					$(this).parent().children().removeClass('active');
					$(this).addClass('active');
					bd.children().removeClass('active');
					bd.children().eq($(this).attr('rel')).addClass('active');
				}
			})
		;
		$(this).children('ul').trigger("create");
	}

	// 컨텐츠 리스트에 데이터 삽입하기
	$.fn.dataToList = function(json)
	{
		var
			scr = ''
			,obj_basic = json.data['basic']
			,obj_comment = json.data['comment']
			,obj_mediaInfo = json.data['mediaInfo']
			,obj_history_edit_metadata = json.data['history_edit_metadata']
			,scr_tc = ''
		;
		if (json.is_group == 'G' || json.is_group == 'C'){
			scr += '<li id="group_list_content">';
			scr += '</li><li>';
		} else {
			scr += '<li>';
		}
		scr += '<dl class="tb">';
		scr += '<dt class="tbc "><?=_text('MN00249')?></dt>';
		scr += '<dd class="tbc">' + json.title + '</dd>';
		scr += '</dl>';
		scr += '<dl class="tb">';
		scr += '<dt class="tbc "><?=_text('MN00387')?></dt>';
		scr += '<dd class="tbc">' + json.category_title + '</dd>';
		scr += '</dl>';
		for (var i in obj_basic)
		{
			if (obj_basic[i].value)
			{
				switch(obj_basic[i].usr_meta_field_type)
				{
					case 'datefield':
						value = stringToFullDate(obj_basic[i].value);
						break;
					default:
						value = checkText(obj_basic[i].value);
						break;
				}
			}
			else
			{
				value='';
			}

			if( obj_basic[i].usr_meta_field_id == '175' )
			{
				scr += '<dl class="tb" id="btn_tc">';
				scr += '<dt class="tbc">' + obj_basic[i].usr_meta_field_title + '<img src="./img/tc_arrow.png" style="width:1.5em;top:.4em;left:.4em;position:relative;"/></dt>';
				scr += '<dd class="tbc"></dd>';
				scr += '</dl><dl class="tb" id="append_tc"></dl>';
			}
			else
			{
				scr += '<dl class="tb">';
				scr += '<dt class="tbc ">' + obj_basic[i].usr_meta_field_title + '</dt>';
				scr += '<dd class="tbc word_break_white_space">' + value + '</dd>';
				scr += '</dl>';
			}

		}
		if (json.buttonEdit == 1){
			scr += '<input class="mov_view_button" data-role="button" type="button" id="btn_edit_metadata" value="<?=_text('MN00166')?>">';
		}		
		scr += '</li>';

		if(json.data['comment'] != 'N'){
			scr += '<li class="listComment">';
			scr += '<dl class="tb">';
			scr += '<dd class="tab_comment">'+json.data['comment'].length+' <?=_text('MN01036')?></dd>';
			//scr += '<dd class="tab_comment tab_comment_user"><?=_text('MN00189')?></dd>';
			//scr += '<dd class="tab_comment tab_comment_value"><?=_text('MN01036')?></dd>';
			scr += '</dl>';

			if (obj_comment != '' && obj_comment[0].user_id != null){
				for (var j in obj_comment){
					if (obj_comment[j].delete_yn == '0'){
						scr += '<dl class="tb">';

						scr += '<dt><span style="padding-right: 5px;">'+obj_comment[j].user_nm+'</span>'+obj_comment[j].datetime_format;


						//scr += '<dd class="tab_comment tab_comment_time">'+obj_comment[j].datetime_format+'</dd>';
						//scr += '<dd class="tab_comment tab_comment_user">'+obj_comment[j].user_nm+'</dd>';
						
						if (obj_comment[j].is_lasted == 1){
							//scr += '<a href="#popup_menu_edit_last_comment" class="menu_edit_last_comment" data-rel="popup" data-transition="slideup"><img class="edit_last_comment" src="img/pencil_pen_edit.png"/>';
							scr += '<a href="#popup_menu_edit_last_comment" class="menu_edit_last_comment" data-rel="popup" data-transition="slideup"><i class="fa fa-fw fa-lg fa-times edit_last_comment"></i>';
							scr += '<p class="display_none comment_content_id">'+obj_comment[j].content_id+'</p>';
							scr += '<p class="display_none comment_user_id">'+obj_comment[j].user_id+'</p>';
							scr += '<p class="display_none comment_seq">'+obj_comment[j].seq+'</p>';
							scr += '</a></dt>';
						}
						scr += '<dd class="tab_comment tab_comment_value">'+obj_comment[j].comments+'</dd>';

						scr += '</dl>';
					}
				}
			}

			scr += '<div>';
			scr += '<div class="input_comment_text"><textarea name="comment_text"  placeholder="<?=_text('MN01036')?>" value="" maxlength="4000"></textarea></div>';
			scr += '<div class=""><input data-role="button" type="button" id="btn_edit_comment" value="<?=_text('MN02197')?>"></div>';
			scr += '<div class="display_none"><input type="text" name="content_id" value="'+ json.content_id+'"></div>';
			scr += '<div class="display_none"><input type="text" name="bs_content_id" value="'+ obj_mediaInfo[0].bs_content_id+'"></div>';
			scr += '</div>';
		}

		if (obj_mediaInfo != ''){
			scr += '</li><li class="mediaInfo">';
			for (var j in obj_mediaInfo){
				if (obj_mediaInfo[j].value == ''){
					continue;
				}
				scr += '<dl class="tb">';
				scr += '<dt class="tbc">'+obj_mediaInfo[j].sys_meta_field_title+'</dt>';
				scr += '<dd class="tbc">'+obj_mediaInfo[j].value+'</dd>';
				scr += '</dl>';
			}
		}

		if(json.data['history_edit_metadata'] != 'N'){
			scr += '</li><li class="list_history_edit_metadata">';
			scr += '<dl class="tb">';
			scr += '<dd class="tbc"><?=_text('MN00354')?></dd>';
			scr += '<dd class="tbc"><?=_text('MN00189')?></dd>';
			scr += '<dd class="tbc tab_list_history_log"><?=_text('MN00067')?></dd>';
			scr += '</dl>';

			if (obj_history_edit_metadata != '' && obj_history_edit_metadata[0].user_nm != null){
				for (var j in obj_history_edit_metadata){
					scr += '<dl class="tb">';
					
					var time = obj_history_edit_metadata[j].created_date.substr(8, 6);
					time = (parseInt(time) > 0) ? ' ' + stringToTime(time) : '';
					scr += '<dd class="tbc">'+stringToFullDate(obj_history_edit_metadata[j].created_date)+' '+time+'</dd>';
					//scr += '<dd class="tbc"><p style="word-wrap: break-word;">'+obj_history_edit_metadata[j].user_nm+'</p></dd>';
					scr += '<dd class="tbc">'+obj_history_edit_metadata[j].user_nm+'</dd>';
					scr += '<dd class="tbc"><a class="view_details_history_edit_metadata"><?=_text('MN02198')?></a>';
					/*
					for (var k in obj[2]) {
						scr += '<div class="history_edit_metadata_content display_none">';
						scr += '<p class="history_metadata_field">'+obj[2][k]+'<p>';
						scr += '<p class="history_metadata_value">'+obj[3][k]+'<p>';
						scr += '</div><br>';
					}
					*/
					scr += '<div class="history_edit_metadata_content display_none">';
					scr += '<p class="history_metadata_log_id">'+obj_history_edit_metadata[j].log_id+'<p>';
					scr += '<p class="history_metadata_ud_content_id">'+json.meta_table_id+'<p>';
					scr += '</div>';

					scr += '</dd>';
					scr += '</dl>';
				}
			}
		}

		scr += '</li>';

		$(this).html(scr);
		$(this).trigger("create");
		$('#btn_tc').off('click').on('click', function(){
			$('#append_tc').html('<?=$scr_tc?>');
		})
		$('.tb').off('click').on('click',function(e){
			$('#append_tc').children().off('click').on('click',function(){
				$('#content_td').css('width', $(window).width());
				$('#content_td').popup("open");

				//$('#content_tc').css('width', '90%');
				$('#content_tc').html($(this).find('span').text());
			})
		})

		var detail_tab = $(this);
		$(".tab_category_view").children().each(function( index ) {
				if ($(this).hasClass('active')){
					detail_tab.children().eq(index).addClass('active');
				}
		});
	}

	// 미디어 내용 만들기
	$.fn.generateMediaElement = function(data)
	{
		var src = '';
		if (data.bs_content_id == '<?=MOVIE?>'){
			src += '<video id="play_video" style="width: 100%;" controls autoplay>';
			src	+= '<source src="" type="video/mp4">';
			src += '</video>'
		} else if (data.bs_content_id == '<?=IMAGE?>'){
			src += '<img id="proxy_image" style="width: 100%;">';
		} else if (data.bs_content_id == '<?=SOUND?>'){
			src += '<audio id="play_audio" controls autoplay>';
			src	+= '<source src="" type="audio/mpeg">';
			src += '</audio>'
		}
		$(this).html(src);
		$(this).trigger("create");
	}

	// 미디어 내용 만들기
	$.fn.dataToListEdit = function(param, json)
	{
		var obj = json.data['basic'];
		var require = 'required: false';
		var scr = '';
		scr += '<form method="post" action="" id="form_edit_meta">';
		scr += '<div class="edit_ui_field_contain">';
		scr += '<label for=""><?=_text('MN00249')?>: </label>';
		scr += '<textarea type="text" required name="k_title" id="">'+json.title+'</textarea>';	
		scr += '</div><br>';
		scr += '<div class="edit_ui_field_contain">';
		scr += '<label for=""><?=_text('MN00387')?>: </label>';
		scr += '<textarea type="text" readonly id="category_name" name="c_category_id">'+json.category_title+'</textarea>';
		scr += '<p class="display_none" id="text_category_id">'+json.category_id+'</p>'
		scr += '<div id="tree_menu_category" class="display_none" ></div>';
		scr += '</div><br>';
		for (var i in obj){
			var value='';
			if (obj[i].value)
			{
				switch(obj[i].usr_meta_field_type)
				{
					case 'datefield':
						value = stringToFullDate(obj[i].value);
						break;
					default:
						value = checkText(obj[i].value);
						break;
				}
			}
			else
			{
				value='';
			}
			if (obj[i].is_required == '1'){
				required = 'required';
			} else {
				required = ' ';
			}
			if (obj[i].depth != 0){
				scr += '<div class="edit_ui_field_contain">';
				scr += '<label for="">' + obj[i].usr_meta_field_title + ': </label>';
				if (obj[i].usr_meta_field_type == 'datefield'){
					scr += '<input type="date" '+required+' name="'+obj[i].usr_meta_field_id+'" value="'+value+'">';
				} else if (obj[i].usr_meta_field_type == 'combo'){
					var value_arr;
					if (obj[i].default_value){
						value_arr = obj[i].default_value.split(";");
					}
					var v_default_value = '';
					scr += '<select class="select_metadata_items" name="' + obj[i].usr_meta_field_id + '">';
					for (var j in value_arr){
						if (value_arr[j] == obj[i].value){
							v_default_value = 'selected';
						} else {
							v_default_value = '';
						}
						scr += '<option value="'+value_arr[j]+'" '+v_default_value+'>'+value_arr[j]+'</option>';
					}
					scr += '</select>';
				} else if (obj[i].usr_meta_field_type == 'checkbox'){
					scr += '</form><form>';
					var value_arr;
					if (obj[i].default_value){
						value_arr = obj[i].default_value.split(";");
					}
					var checked_attr = '';
					for (var j in value_arr){
						if (value_arr[j] == obj[i].value){
							checked_attr = 'checked';
						} else {
							checked_attr = '';
						}
						scr += '		<input type="radio" data-role="none" name="'+obj[i].usr_meta_field_id+'" '+checked_attr+' value="'+value_arr[j]+'">';
						scr += '		<label for="checkbox_'+j+'">'+value_arr[j]+'</label>';
					}
					scr += '</form>';
				}
				else {
					scr += '<textarea type="text" '+required+' name="' + obj[i].usr_meta_field_id + '" id="">'+value+'</textarea>';	
				}
				
				scr += '</div><br>';
			} else {
				scr += '<div class="edit_ui_field_contain" style="display: none;">';
				//scr += '<label for="">' + obj[i].usr_meta_field_title + ': </label>';
				scr += '<textarea type="text" name="k_meta_field_id" id="">'+obj[i].usr_meta_field_id+'</textarea>';
				scr += '</div>';
			}
		}
		scr += '<div class="edit_ui_field_contain" style="display: none;">';
		scr += '<textarea type="text" name="k_content_id" id="">'+param.content_id+'</textarea>';
		scr += '</div>';
		scr += '<div class="edit_ui_field_contain" style="display: none;">';
		scr += '<textarea type="text" name="k_ud_content_id" id="">'+param.meta_table_id+'</textarea>';
		scr += '</div>';
		scr += '<div class="edit_ui_field_contain" style="display: none;">';
		scr += '<textarea type="text" name="k_bs_content_id" id="">'+param.bs_content_id+'</textarea>';
		scr += '</div>';
		//scr += '</form>';
		$(this).html(scr);
		$(this).children("input[type='checkbox']").checkboxradio("refresh");
		$(this).trigger("create");
		var v_obj = {
					category_id : json.category_id
					,ud_content_id: json.meta_table_id
				}
		fn_create_category_tree_menu(v_obj);
		$('#form_edit_meta').validate({
			rules:{
				22:{required:true}
			},
			messages:{
				22:{required:"Content is required"}
			},
			submitHandler: function(form) {
				var v_metadata_param = new Object();
				$(".edit_ui_field_contain").each(function( index ) {
					var v_temp = '';
					if ($(this).find('select.select_metadata_items').attr("name") != undefined){
						v_temp = $(this).find('select.select_metadata_items').attr("name");
						v_metadata_param[v_temp] = $(this).find('select.select_metadata_items').val();
					} else if ($(this).find('input[type="radio"]').attr("name") != undefined){
						v_temp = $(this).find('input[type="radio"]').attr("name");
						v_metadata_param[v_temp] = $(this).find('input:checked').val();
					} else if ($(this).find('input[type="date"]').attr("name") != undefined){
						v_temp = $(this).find('input[type="date"]').attr("name");
						v_metadata_param[v_temp] = $(this).find('input[type="date"]').val();
					} else if ($(this).children("textarea").attr("name") != undefined){
						v_temp = $(this).children("textarea").attr("name");
						if (v_temp == 'c_category_id'){
							v_metadata_param[v_temp] = $(this).children('#text_category_id').text();
						} else {
							v_metadata_param[v_temp] = $(this).children("textarea").val();
						}
					} else{
					}	
				});
				
				param = new Object();
				param = {
					content_id : v_metadata_param.k_content_id,
					meta_table_id : v_metadata_param.k_meta_table_id,
					bs_content_id : v_metadata_param.k_bs_content_id,
					edit_mode_yn : 1
				}
				showModal();
				$.ajax({
					url : '/store/content_edit.php'
					,type : 'POST'
					,data : v_metadata_param
					,dataType : 'json'
					//,timeout : 200000
					,error : function()
					{
						alert( '작업이 실패했습니다.');
					}
					,success : function(json)
					{
						fn_edit_metadata_callback(param);
					}
				}); 

				$('#popup_edit_metadata').popup('close');	
			}
		});
	}

	// 분류토글
	$('#bodyTab').children('a').on('click', function(){
		$(this).toggleClass('active').parent().children('ul').toggleClass('active');
		return false;
	});

	//즐겨찾기 추가 삭제
	$('body').off('click', '#btn_edit_metadata').on('click', '#btn_edit_metadata', fn_edit_metadata);
	function fn_edit_metadata(){
		$("#tree_menu_category").hide();
		var v_tab_name = $('.tab_category_view').children(".active").children("p").text();
		if (v_tab_name == '<?=_text('MN01089')?>'){
			$('.popup_alert_out').css('width', $(window).width());
			$('#popup_edit_metadata').popup("open");
			//$('.popup_alert_in').css('width', '90%');
		} else if (v_tab_name == '<?=_text('MN00170')?>'){
			$('.popup_alert_out').css('width', $(window).width());
			$('#popup_edit_mediainfo').popup("open");
			//$('.popup_alert_in').css('width', '90%');
		}
	}

	$('#btn_edit_metadata_cancel').on('click', function(){
		$('#popup_edit_metadata').popup('close');
	});

	$('#btn_history_edit_metadata_cancel').on('click', function(){
		$('#popup_history_edit_metadata').popup('close');
	});

	$('#btn_edit_mediainfo_cancel').on('click', function(){
		$('#popup_edit_mediainfo').popup('close');
	});

	$('body').off('click', '.menu_edit_last_comment').on('click', '.menu_edit_last_comment',function(){
		$('.menu_edit_last_comment').removeClass('comment_target');
		$(this).addClass('comment_target');
	});

	$('#delete_last_comment_btn').off('click').on('click', function(){
		var v_comment_content_id = $('.comment_target').find('.comment_content_id').text();
		var v_comment_seq =  $('.comment_target').find('.comment_seq').text();
		var v_comment_user_id =  $('.comment_target').find('.comment_user_id').text();
		var ud_content_id = $('#mediaCategory').find('.active').attr('rel');
		var bs_content_id = $('.listComment').find('input[name=bs_content_id]').val();
		var content_id = $('.listComment').find('input[name=content_id]').val();
		var param = {
						bs_content_id: 		bs_content_id,
						meta_table_id: 		ud_content_id,
						content_id: 		content_id
					};
		showModal();
		$.ajax({
			url : '/store/edit_comments.php'
			,type : 'POST'
			,data : {
				content_id: v_comment_content_id,
				seq: v_comment_seq,
				comment_user_id: v_comment_user_id,
				mode: 'delete'
			}
			,dataType : 'json'
			,error : function()
			{
				alert( 'Delete comment error');
			}
			,success : function(json)
			{
				fn_edit_comments_callback(param);
			}
		});
		$('.menu_edit_last_comment').removeClass('comment_target');
		$('#popup_menu_edit_last_comment').popup('close');
	});

	$('#btn_edit_metadata_save').on('click', function(){
		var v_metadata_param = new Object();
		$(".edit_ui_field_contain").each(function( index ) {
			var v_temp = '';
			if ($(this).find('select.select_metadata_items').attr("name") != undefined){
				v_temp = $(this).find('select.select_metadata_items').attr("name");
				v_metadata_param[v_temp] = $(this).find('select.select_metadata_items').val();
			} else if ($(this).find('input[type="radio"]').attr("name") != undefined){
				v_temp = $(this).find('input[type="radio"]').attr("name");
				v_metadata_param[v_temp] = $(this).find('input:checked').val();
			} else if ($(this).children("textarea").attr("name") != undefined){
				v_temp = $(this).children("textarea").attr("name");
				if (v_temp == 'c_category_id'){
					v_metadata_param[v_temp] = $(this).children('#text_category_id').text();
				} else {
					v_metadata_param[v_temp] = $(this).children("textarea").val();
				}
			} else{
			}	
		});

		param = new Object();
		param = {
			content_id : v_metadata_param.k_content_id,
			meta_table_id : v_metadata_param.k_ud_content_id,
			bs_content_id : v_metadata_param.k_bs_content_id
		}
		showModal();
		$.ajax({
			url : '/store/content_edit.php'
			,type : 'POST'
			,data : v_metadata_param
			,dataType : 'json'
			//,timeout : 200000
			,error : function()
			{
				alert( '작업이 실패했습니다.');
			}
			,success : function(json)
			{
				fn_edit_metadata_callback(param);
			}
		}); 

		$('#popup_edit_metadata').popup('close');
	});

	function fn_edit_metadata_callback(param){
		$.ajax({
			url : '<?=$mov_view_json?>'
			,type : 'POST'
			,dataType : 'json'
			,data : param
			,error : function()
			{
				alert( 'error load data.');
			}
			,success : function(result)
			{
				$('#contentBody').dataToList(result);
				$('#programTitle').text(result.title);
				if (result.is_group == 'G' || result.is_group == 'C'){
					param = new Object();
					param = {
						content_id: result.content_id,
						is_group: result.is_group,
						parent_content_id : result.parent_content_id,
						bs_content_id : result.bs_content_id
					}
					$('#group_list_content').fn_group_list_content(param);
				}
				hideModal();
			}
		});
	}

	$('body').off('click', '#btn_edit_comment').on('click', '#btn_edit_comment', fn_edit_comments);
	function fn_edit_comments(){
		var text = $('textarea[name=comment_text]').val();
		var mode = 'insert';
		var parent_content_id = $('#mediaCategory').find('.active').attr('rel');
		var ud_content_id = $('#mediaCategory').find('.active').attr('rel');
		var bs_content_id = $('.listComment').find('input[name=bs_content_id]').val();
		var content_id = $('.listComment').find('input[name=content_id]').val();
		var param = {
						bs_content_id: 		bs_content_id,
						meta_table_id: 		ud_content_id,
						content_id: 		content_id
					};
		if(text != '' && text != 'undefined'){
			showModal();
			$.ajax({
				url : '/store/edit_comments.php'
				,type : 'POST'
				,data : {
						mode: 				mode,
						text: 				text,
						parent_content_id: 	parent_content_id,
						content_id: 		content_id
					}
				,dataType : 'json'
				//,timeout : 200000
				,error : function()
				{
					alert( 'Insert comment error');
				}
				,success : function(json)
				{
					fn_edit_comments_callback(param);
				}
			});
		}
	}

	function fn_edit_comments_callback(param){
		$.ajax({
			url : '<?=$mov_view_json?>'
			,type : 'POST'
			,dataType : 'json'
			,data : param
			,error : function()
			{
				alert( 'Edit comment error.');
			}
			,success : function(result)
			{
				$('#contentBody').dataToList(result);
				$('#programTitle').text(result.title);
				if (result.is_group == 'G' || result.is_group == 'C'){
					param = new Object();
					param = {
						content_id: result.content_id,
						is_group: result.is_group,
						parent_content_id : result.parent_content_id,
						bs_content_id : result.bs_content_id
					}
					$('#group_list_content').fn_group_list_content(param);
				}
				$('#contentBody').children().removeClass('active');
				$('#contentBody').find('.listComment').addClass('active');
				hideModal();
			}
		});
	}

	$('body').off('click', '.view_details_history_edit_metadata').on('click', '.view_details_history_edit_metadata', function(){
		var popup_content = '';
		/* $(this).parent().find('.history_edit_metadata_content').each(function( index ) {
			popup_content += '<div class="history_edit_ui_field_contain">';
			popup_content += '<label for="">'+$(this).children('.history_metadata_field').text()+': </label>';
			popup_content += '<textarea readonly type="text">'+$(this).children('.history_metadata_value').text()+'</textarea>';	
			popup_content += '</div><br>';
		}); */
		var log_id = $(this).parent().find('.history_metadata_log_id').text();
		var ud_content_id = $(this).parent().find('.history_metadata_ud_content_id').text();
		showModal();
		$.ajax({
			url : '/store/get_log_info.php'
			,type : 'POST'
			,dataType : 'json'
			,data : {
					log_id: log_id,
					ud_content_id: ud_content_id
				}
			,error : function()
			{
				alert( 'Get details history log error.');
			}
			,success : function(result)
			{
				popup_content += '<dl class="tb">';
				popup_content += '<dd class="tab_history_log tab_history_log_field_id"><?=_text('MN02200')?></dd>';
				popup_content += '<dd class="tab_history_log tab_history_log_old_value"><?=_text('MN02201')?></dd>';
				popup_content += '<dd class="tab_history_log tab_history_log_new_value"><?=_text('MN02202')?></dd>';
				popup_content += '</dl>';
				var v_content_log = result.details;
				for (var i in v_content_log){
					popup_content += '<dl class="tb">';
					popup_content += '<dd class="tab_history_log tab_history_log_field_id">'+v_content_log[i].field_id+'</dd>';
					popup_content += '<dd class="tab_history_log tab_history_log_old_value">'+v_content_log[i].old_contents+'</dd>';
					popup_content += '<dd class="tab_history_log tab_history_log_new_value">'+v_content_log[i].new_contents+'</dd>';
					popup_content += '</dl>';
				}
				hideModal();
				$('#popup_history_edit_metadata_content').html(popup_content);
				$('#popup_history_edit_metadata_content').trigger("create");
				$('.popup_alert_out').css('width', $(window).width());
				$('#popup_history_edit_metadata').popup("open");
				//$('.popup_alert_in').css('width', '90%');
			}
		});
	});

	function fn_create_category_tree_menu(av_obj){
		var v_result_create_tree = '';
		$.ajax({
			url : 'inc/get_category_tree.php'
			,type : 'POST'
			,data : {
				category_id: av_obj.category_id
				,ud_content_id: av_obj.ud_content_id
			}
			,dataType : 'json'
			,error : function()
			{
				alert( '작업이 실패했습니다.');
			}
			,success : function(result)
			{
				v_result_create_tree = result.data;
				$("#tree_menu_category").html(v_result_create_tree);
				$("#tree_menu_category").trigger("create");
				$("#tree_menu_category").dTree({
//	 				useCookie: false, // enable cookie support
					closeSameLevel: false // close same level nodes
		        });
		        $("#tree_menu_category").fn_handler_category_tree();
			}
		});
	}

	function fn_get_categories(param){
		var v_result_create_tree = '';
		$.ajax({
			//url : '/store/get_categories.php'
			url : 'inc/get_category_tree.php'
			,type : 'POST'
			,data : param
			,dataType : 'json'
			,error : function()
			{
				alert( 'get category fail');
			}
			,success : function(result)
			{
				v_result_create_tree = result.data;
				$("#list_categories").html(v_result_create_tree);
				$("#list_categories").dTree({
					//useCookie: false,
					closeSameLevel: false // close same level nodes
				});
			}
		});
	}

	// tree category handler event
	$.fn.fn_handler_category_tree = function()
	{	
		// jquery for category tree menu
		$(this).find('li a').off('click').on('click', function(){
			var v_category_name ='';
			var v_category_title = $(this).children('.text_category_title').text();
			var v_category_id = $(this).children('.text_category_id').text();
			v_category_name = v_category_title;
			$("#popup_edit_metadata_content").find('textarea[name=c_category_id]').val(v_category_name);
			$("#popup_edit_metadata_content").find('#text_category_id').html(v_category_id);
			$("#tree_menu_category").find("li").removeClass("current_category");
			$(this).parent().addClass("current_category");
			$("#tree_menu_category").hide();
		});
	}

	$(document).on( "swipeleft swiperight", "#list", function( e ) {
		// We check if there is no open panel on the page because otherwise
		// a swipe to close the left panel would also open the right panel (and v.v.).
		// We do this by checking the data that the framework stores on the page element (panel: open).
		if ( $.mobile.activePage.jqmData( "panel" ) !== "open" ) {
			if ( e.type === "swipeleft"  ) {
				//$("#list_categories").hide();
				//$( "#right-panel" ).panel( "open" );
			} else if ( e.type === "swiperight" ) {
				//$("#list_categories").show();
				//$( "#left-panel" ).panel( "open" );
			}
			else {
				//$("#list_categories").hide();
			}
		}
	});

//	hide new count if count value is 0
	$.each($('.ui-li-count'), function (i, v) {
        if($(this).html() == 0) {
            $(this).hide();
        }
    });

	$(document).off( "click", "#list_categories li a").on( "click", "#list_categories li a", function( e ) {
		var v_category_full_path = $(this).children('.text_category_full_path').text();
		gv_category_full_path = v_category_full_path;
		var ud_content_id = $('#mediaCategory').find('.active').attr('rel');
		$( "#left-panel" ).panel( "close" );
		var v_obj = {
					start : START_INDEX
					,search_q: search_q
					,ud_content_id: ud_content_id
					,filter_type: 'category'
					, filter_value: v_category_full_path
				}
		fn_reload_media_list(v_obj);
	});

	$(document).off( "click", "#btn_re_search").on( "click", "#btn_re_search", function( e ) {
		var ud_content_id = $('#mediaCategory').find('.active').attr('rel');
		var select_content_type = $("select#select_content_type");
		$('input.search_metadata_text').val('');
		$('.search_metadata').html('');
		$('.search_metadata').trigger("create");
		select_content_type.val(ud_content_id).attr('selected', true).siblings('option').removeAttr('selected');
		select_content_type.selectmenu("refresh");
		fn_get_metadata_list_select(ud_content_id);
		$('.select_metadata').removeClass("border_field_metadata");
	});

	$("#select_content_type").change(function () {
		var select_content_type = $("select#select_content_type");
		var selected_ud_content_id = select_content_type.val();
//		fn_get_metadata_list_select(selected_ud_content_id);

		$('input.search_metadata_text').val('');
		$('.search_metadata').html('');
		$('.search_metadata').trigger("create");
		$('.select_metadata').removeClass("border_field_metadata");
		select_content_type.val(selected_ud_content_id).attr('selected', true).siblings('option').removeAttr('selected');
		select_content_type.selectmenu("refresh");
		fn_get_metadata_list_select(selected_ud_content_id);

	});
	$(".select_metadata_type").change(function () {
		$(this).parents('.select_metadata').addClass("border_field_metadata");
		var search_metadata = $(this).parentsUntil('.select_metadata').find('.search_metadata');
		search_metadata.html('');
		var v_meta_field_id = $(this).val();
		var v_input_element = '';
		var v_type = '';
		for (var i in gv_option_attr_value){
			if (gv_option_attr_value[i]['meta_field_id'] == v_meta_field_id){
				if (gv_option_attr_value[i]['type'] == 'datefield'){
					var today = new Date();
					var fromday = new Date();
					fromday.setDate(fromday.getDate()-7);
					v_input_element = '<div class="select_create_date"><input class="select_create_date_from" type="date" required name="date" value="'+dateFormat(fromday)+'">';
					v_input_element += '<i class="fa fa-minus" style="padding: 0px 3px;"></i><input class="select_create_date_to" type="date" required name="date" value="'+dateFormat(today)+'"> </div>';
				} else if (gv_option_attr_value[i]['type'] == 'combo'){
					var value_arr = gv_option_attr_value[i]['default_value'].split(";");
					v_input_element += '<select class="select_metadata_items" name="select_metadata_items">';
					for (var j in value_arr){
						v_input_element += '<option value="'+value_arr[j]+'">'+value_arr[j]+'</option>';
					}
					v_input_element += '</select>';
				} else if(gv_option_attr_value[i]['type'] == 'checkbox'){
					v_type = gv_option_attr_value[i]['type'];
					v_input_element += '<form style="padding-bottom: .8em;">';
					//v_input_element += '	<fieldset data-role="controlgroup" data-iconpos="right">';
					var value_arr = gv_option_attr_value[i]['default_value'].split(";");
					for (var j in value_arr){
						v_input_element += '		<input type="radio" name="'+gv_option_attr_value[i]['meta_field_id']+'" value="'+value_arr[j]+'">';
						v_input_element += '		<label for="checkbox_'+j+'">'+value_arr[j]+'</label>';
					}
					//v_input_element += '	</fieldset>';
					v_input_element += '</form>';
				}
				else {
					v_input_element = '<input class="search_metadata_text" type="text" name="name" value="" placeholder="<?=_text('MSG00015')?>" />';	
				}
			}
		}
		if (v_type == 'checkbox'){
			$(this).parentsUntil('.select_metadata').find('.search_metadata').empty().append(v_input_element);
		} else {
			$(this).parentsUntil('.select_metadata').find('.search_metadata').html(v_input_element);
			$(this).parentsUntil('.select_metadata').trigger("create");
		}
	});

	function fn_get_metadata_list_select(selected_ud_content_id){
		$.ajax({
			url : '/store/search/get_dynamic2.php'
			,type : 'POST'
			,dataType : 'json'
			,data : {
					meta_table_id:selected_ud_content_id,
					container_id:selected_ud_content_id,
					type: 'component'
			}
			,error : function()
			{
				alert( 'get dynamic content error');
			}
			,success : function(results)
			{
				var v_list_metadata_type = '';
				v_list_metadata_type +='<option value="0" disabled="disabled" selected="selected"><?=_text('MSG00135')?></option>';
				var result = results.data;
				gv_option_attr_value = result;
				for (var i in result){
					v_list_metadata_type += '<option value="'+result[i]['meta_field_id']+'">'+result[i]['name']+'</option>';
				}
				$('.select_metadata_type').html(v_list_metadata_type);
				$('.select_metadata_type').trigger("create");
				$('select.select_metadata_type').selectmenu("refresh");
			}
		});
	}

	$(document).off( "click", "#advanced_search_submit").on( "click", "#advanced_search_submit", function( e ) {
		var select_content_type = $("select#select_content_type");
		var selected_ud_content_id = select_content_type.val();
		var fields_arr = []
		var fields_arr_length = 0;
		$('.select_metadata').each(function(index) {
			var fields = {};
			for (var i in gv_option_attr_value){
				var select_metadata_type = $(this).find('select.select_metadata_type').val();
				if (select_metadata_type == gv_option_attr_value[i]['meta_field_id'] && select_metadata_type != 0 && select_metadata_type != null){
					if(gv_option_attr_value[i]['type'] == 'datefield'){
						fields.s_dt = startDateToString($(this).find('.select_create_date_from').val());
						fields.e_dt = endDateToString($(this).find('.select_create_date_to').val());
					} else if (gv_option_attr_value[i]['type'] == 'combo'){
						fields.value = $(this).find('select.select_metadata_items').val();
					} else if (gv_option_attr_value[i]['type'] == 'checkbox'){
						fields.value = $(this).find('input:checked').val();
					}
					else {
						fields.value = $(this).find('.search_metadata_text').val();
					}
					fields.type = gv_option_attr_value[i]['type'];
					fields.meta_field_id = gv_option_attr_value[i]['meta_field_id'];
					fields.table = gv_option_attr_value[i]['table'];
					fields.field = gv_option_attr_value[i]['field'];
					fields.order_type = 'ASC';
					if (fields.value != ""){
						fields_arr.push(fields);
					}
					fields_arr_length++;
				}
			}
		});
		if (fields_arr_length == 0){
			alert("You need to set metadata list");
			return false;
		}
		if (fields_arr.length == 0){
			alert("Please fill the search form");
			return false;
		}
		var params = {
			meta_table_id: selected_ud_content_id
			,fields: fields_arr
		}
		var v_obj = {
					ud_content_id: selected_ud_content_id
					,action: 'a_search'
					,params: params
				}
		fn_reload_media_list(v_obj);
		mediaCategory.children('#movCategory').children().removeClass('active');
		mediaCategory.children('#movCategory').children().each(function(){
			if ($(this).attr('rel') == selected_ud_content_id){
				$(this).addClass('active');
				return false;
			}
		});
		$('#popup_advanced_search').popup("close");
	});

	$(document).off( "click", "#advanced_search_clear").on( "click", "#advanced_search_clear", function( e ) {
		var ud_content_id = $('#mediaCategory').find('.active').attr('rel');
		var select_content_type = $("select#select_content_type");
		$('input.search_metadata_text').val('');
		$('.search_metadata').html('');
		$('.search_metadata').trigger("create");
		$('.select_metadata').removeClass("border_field_metadata");
		select_content_type.val(ud_content_id).attr('selected', true).siblings('option').removeAttr('selected');
		select_content_type.selectmenu("refresh");
		fn_get_metadata_list_select(ud_content_id);
	});

	$(document).on("vmouseover", "#list" ,function(){
		$('#toTop').css('visibility','visible');
	});

	//페이지 맨 위 도착시 top button hidden
	$(document).on("scrollstop",function(){
		if( $(window).scrollTop() == 0 )
		{
			$('#toTop').css('visibility','hidden');
		}
	});
	mediaCategory.children('li').off('click').on('click', function(){
		$(this)
			.toggleClass('active')
			.parent().children('ul').toggleClass('active')
		;
		return false;
	});
	// 분류선택
	mediaCategory.find('li').off('click').on('click', function(){
		showModal();
		saveHash(window.location.hash, $(document).scrollTop(), parseInt(formData.start)+LIMIT_INDEX);
		nomore = false;
		list.html('');
		v_ud_content_id = $(this).attr('rel');
		var v_obj = {
			start : START_INDEX
			,search_q: search_q
			,ud_content_id: v_ud_content_id
		}
		fn_reload_media_list(v_obj);
		gv_category_full_path = '';

		$(this).parent().children().removeClass('active');
		$(this).addClass('active');
	});


	// 목록 출력방식 선택
	listTypes.off('click').on('click', function(){
		if (!$(this).hasClass('active'))
		{
			$(this).parent().parent().find('a').removeClass('active')
			$(this).toggleClass('active')
			list.removeClass().addClass('type-' + $(this).attr('rel'));
		}
		return false;
	});

	//top으로 버튼
	$('#toTop').click(function()
	{
		$.mobile.silentScroll(0);
		$('#toTop').css('visibility','hidden');
		return false;
	});

	// 스크롤로 더 로드하기
	iscroll(function(){
		showModal();
		formData.start = parseInt(formData.start) + parseInt(formData.limit);
		formData.limit = LIMIT_INDEX;
		saveHash(window.location.hash, $(document).scrollTop(), parseInt(formData.start)+LIMIT_INDEX);
		getMediaList({
			url : '<?=$jsonAddress?>'
			,target : list
			,data : formData
			,method : 'append'
		});
		moreCount = parseInt(formData.start)+LIMIT_INDEX;
	});
	

	$('#btn_ge_search').off('click').on('click', function(){
		search_q = $(this).parentsUntil('.blind').find('input[name=search_q]').val();
		saveHash(window.location.hash, $(document).scrollTop(), LIMIT_INDEX);
		var v_ud_content_id = $('#mediaCategory').find('.active').attr('rel');
		var v_obj = {
			search_q: 	search_q
			,start: 	START_INDEX
			,ud_content_id: v_ud_content_id
		}
		fn_reload_media_list(v_obj);
	});

	// click for view detail metadata
	$('body').off('click', '.test_list').on('click', '.test_list', fn_view_details);
	
	function fn_view_details(){
		//$.mobile.silentScroll(0);
		//$("#nav_header li a").first().addClass('ui-disabled');
		var content_id = $(this).children('p.text_content_id').text();
		var meta_table_id = $(this).children('p.text_meta_table_id').text();
		var bs_content_id = $(this).children('p.text_bs_content_id').text();
		var is_group = $(this).children('p.text_is_group').text();
		param = new Object();
		param = {
			content_id : content_id,
			meta_table_id : meta_table_id,
			bs_content_id : bs_content_id,
			is_group: is_group
		}
		fn_reload_view_details(param);
		$('#toTop').css('visibility','hidden');
	}

	$('body').off('click', '#category_name').on('click', '#category_name', function(){
		$("#tree_menu_category").show();
	});

	jQuery(function($){
		$(".type-list").off("click").on("click",function(){
			saveHash(window.location.hash, $(document).scrollTop(), parseInt(formData.start)+LIMIT_INDEX);
		});

		//page refresh
		$(window).bind("pageshow", function(event) {
			if (event.originalEvent.persisted) {
				document.location.reload();
				//list.listview("refresh");
			}
		});

		var v_obj = {
				start: 	0
				,search_q: ''
				,ud_content_id: <?=$first_ud_content_id?>
			}
		fn_reload_media_list(v_obj);
	});
</script>
