

<!-- Search Box -->
<div data-role="content" data-theme="d" id="searchBox" class="searchBox">
	<legend class="blind">Search box form</legend>
	<dl>
		<dd class="keyword_1"><span><input type="search" id="search_text" name="search_q" value='<?=$sd[search_q]?>'/></span></dd>
		<dd class="btn_search"><button type="submit" id="btn_search_notice"><span class="fa fa-search fa-lg"></span></button></dd>
		<dd class="btn_search"><button type="submit" id="btn_add_notice"><span class="fa fa-plus fa-lg"></span></button></dd>
		<!-- <dd class="btn_search"><button type="submit" id="btn_re_search"><span><img src="img/searchBox_2.png" class="b1"/></span></button></dd> -->
		<!-- <dd class="btn_search"><a id="btn_re_search" href="#popup_advanced_search" data-transition="flow" data-role="button" data-inline="true" data-rel="popup"><span><img src="img/searchBox_2.png" class="b1"/></span></a></dd> -->
	</dl>
</div>

<!-- Media list -->

<!-- <div data-role="navbar">
	<ul class="nav_upload">
		<li><a href="" class="ui-btn-active tab_list_notice_view">View notice</a></li>
		<li><a href="" class="tab_list_notice_management">management notice</a></li>
	</ul>

</div> -->

<div class="mediaDetails">
	<section class="bd">
		<div id="contentBody" class="con"></div>
		
	</section>
	
</div>


<div data-role="popup" id="message_upload" data-shadow="false" data-corners="false" class="popup_search">Loading...</div>

<div data-role="popup" id="popup_edit_notice"  class="popup_alert_out" data-dismissible="false">
	<div class="popup_alert_in">

		<div id="popup_edit_notice_content">

		</div>
	</div>
</div>

<!-- notice page-->
<script type="text/javascript" src="./js/js_notice/notice.js" ></script>
