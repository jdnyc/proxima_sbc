<?php
include "_head.php";

?>

<script type="text/javascript">
	var loadingSw = loadingSw();
	$(document).ready(function(){
		$("#btn_notice_page").click(function(){
			showModal();
			$('.page_content').load('notice_list.php', function () {
				$('.page_content').trigger("create");
				fn_list_notice();
			}).trigger("create");
			$("#list_content_button_header").attr("href", "");
			$('#nav_header').find('li a').removeClass('ui-btn-active');
			$("#btn_notice_page").addClass('ui-btn-active');
			$('#nav_header').find('li a').removeClass('activeOnce');
			$("#btn_notice_page").addClass('activeOnce');

		});
		$("#video_nav").click(function(){
			showModal();
 			$('.page_content').empty();
			$('.page_content').load('content_list.php', function () {
				$('.page_content').trigger("create");
			}).trigger("create");
			$('#nav_header').find('li a').removeClass('ui-btn-active');
			$("#video_nav").addClass('ui-btn-active');
			$('#nav_header').find('li a').removeClass('activeOnce');
			$("#video_nav").addClass('activeOnce');
			$("#list_content_button_header").attr("href", "#left-panel");

		});
		$('.page_content').load('content_list.php', function () {
			$('.page_content').trigger("create");
			$('#nav_header').find('li a').removeClass('activeOnce');
			$("#video_nav").addClass('activeOnce');
			var param = {
				type: 'admin_list'
			}
			getJsonData({
				url : '/store/notice/get_list.php'
				,type : 'post'
				,dataType : 'json'
				,parameter : param
				,complete : function(result)
				{
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
				}
			});
		}).trigger("create");

		$('body').off('click', '#list_content_button_header').on('click', '#list_content_button_header', fn_back_list_content);
		function fn_back_list_content(){
			$('#nav_header').find('li a').removeClass('ui-focus');
			if ($("#video_nav").hasClass("activeOnce")){
				$('.mediaDetail').hide();
				//$("#nav_header li a").first().removeClass('ui-disabled');
				$('#list_content_button_header').removeClass('ui-btn-active');
				$("#list_content_button_header").attr("href", "#left-panel");
				var ud_content_id = $('#mediaCategory').find('.active').attr('rel');
				showModal();
				var v_obj = {
							start: START_INDEX
							,limit: gv_count_start
							,search_q: search_q
							,ud_content_id: ud_content_id
						}
				if (gv_category_full_path != '' && gv_category_full_path != null){
					v_obj.filter_type = 'category';
					v_obj.filter_value = gv_category_full_path;
				}
				fn_reload_media_list(v_obj);

				var id = window.location.hash.replace(/^#/, '');
				var n = id.indexOf("a");
				id = parseInt(id.substring(0, n));
				$('#searchBox').show();
				$('.mediaList').show();
				var vid = document.getElementById("play_video");
				if (typeof(vid) != 'undefined' && vid != null)
				{
					vid.pause();
				}
				var aud = document.getElementById("play_audio");
				if (typeof(aud) != 'undefined' && aud != null)
				{
					aud.pause();
				}
				$.mobile.silentScroll(id);
				$('#nav_header').find('li a').removeClass('ui-btn-active');
				$('#video_nav').addClass('ui-btn-active');
			} else if ($("#btn_notice_page").hasClass("activeOnce")){
				$('#nav_header').find('li a').removeClass('ui-btn-active');
				$('#btn_notice_page').addClass('activeOnce');
				showModal();
				$('.page_content').load('notice_list.php', function () {
					$('.page_content').trigger("create");
					fn_list_notice();
				}).trigger("create");
				var id = window.location.hash.replace(/^#/, '');
				var n = id.indexOf("a");
				id = parseInt(id.substring(0, n));
				$.mobile.silentScroll(id);
			}
		}
	});
</script>


<?php
include "_foot.php";
?>