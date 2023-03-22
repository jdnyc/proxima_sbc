<?php
    header("Content-type: text/css; charset: UTF-8");

	$padding_left = '10px';
	$color_s = '#f25100';

	$window_width = 'window.screen.width';
    $window_height = 'window.screen.height';
?>


@media all and (min-width: <?=$window_width?>)
{
	.ui-page
	{
		width: <?=$window_width?> !important;
		margin: 0 auto !important;
		position: relative !important;
		padding:1em;
		/*border-right: 1px #666666 outset !important;
		border-left: 2px #e8e8e8 outset !important;*/
	}
	.ui-grid-c
	{
		width: <?=$window_width?> !important;
		margin: 0 auto !important;
		position: relative !important;
		padding:1em;
		bacgkround: #fff !important;
	}
	.ui-grid-b
	{
		width: <?=$window_width?> !important;
		margin: 0 auto !important;
		position: relative !important;
		padding:1em;
		bacgkround: #fff !important;
<!-- 		margin-right: -2px !important; -->
	}
	.ui-grid-a
	{
		width: <?=$window_width?> !important;
		margin: 0 auto !important;
		position: relative !important;
		padding:1em;
		bacgkround: #fff !important;
<!-- 		margin-right: -2px !important; -->
	}

	.ui-grid-b li.ui-block-c .ui-btn
	{
		margin-right: -2px !important;
	}
	.ui-header {border:none !important;}
	.ui-content {width: <?=$window_width?> !important;}
}

#nav_header>li{
	width: 28%;
}
#nav_header>li:first-child{
	width: 16%;
}
.ui-navbar {background:#fff !important;border:none !important;}
/* init */
.intro,
.intro>body {width:100%; height:100%;}
body {margin:0; padding:0; font-size:100%; font-family:sans-serif; -webkit-text-size-adjust:none;}
button {cursor:pointer;}
select {margin:0;}

.ui-body-d{padding-left:2px;}

.footer {background:#fff;border-top:.1em solid #d7d7d7;color:#707070;padding:.5em;text-align:right;}
.blind {overflow:hidden; width:0; height:0; font-size:0; line-height:0; visibility:hidden;}
.uppercase {text-transform:uppercase;}

.viewport {overflow:hidden;}
.img_header img {width:100%;}

.ui-mobile {
  -webkit-touch-callout: none;
  -webkit-user-select: none;
  -khtml-user-select: none;
  -ms-user-select: none;
  user-select: none;
  -webkit-tap-highlight-color: rgba(0,0,0,0);
  }


/* sprite icon */
.ico-basic {
	background-image:url('../img/sp_basic@1x.png');
	background-repeat:no-repeat;
	text-indent:-9999px;
}
@media (-webkit-min-device-pixel-ratio: 2), (min-resolution: 192dpi) {
	.ico-basic {
		background-image:url('../img/sp_basic@2x.png');
		background-size:230px 300px;
	}
}

.cnt {color:#707070;}
body {
    -webkit-touch-callout: none !important;
}
a { -webkit-user-select: none !important; }

/* sub title */
.subTitle {background:#022d5c;}
.subTitle>h1 {margin:0; color:#5990b8; font-size:.813em; padding:.7em 10px;}


/* color */
.co-red {color:#ff0000 !important;}

/* btngroup */
.btngroup {margin:.5em 0; text-align:center;}

/* font-size*/
.font-title{
	font-size:.813em;
}

/* common css*/
.display_none{
	display: none;
}

.modalWindow{
	width:200%;
    height:200%;
    top:0;
    margin: 0;
	vertical-align:middle;
	margin-left:auto; margin-right:auto;
	/*background : red; opacity : .7;*/
    background: rgba(0, 0, 0, 0.3);
    position: fixed;
    z-index: 1500;
}

.modalWindow_s{
	width:100%;
    height:100%;
    top:0;
    margin: 0;
	vertical-align:middle;
	margin-left:auto; margin-right:auto;
	/*background : red; opacity : .7;*/
    background: rgba(0, 0, 0, 0.3);
    position: fixed;
    z-index: 1500;
}

.ui-loader{
    z-index: 1501;
}

/* top button*/
#toTop {
	/*background : <?=$color_s?>; opacity : .7;*/
	background : <?=$color_s?>;
	color:#fff;
	text-shadow:0 1px 1px rgba(255,255,255,.0);
	width:8em;

	padding:0;
	margin:0;
	position:fixed!important;
	bottom:87px;
	right:2em;
	font-size:.625em;
	border:none !important;
	z-index:10;
}



#lo_s {
	/*background : <?=$color_s?>; opacity : .7;*/
	background : <?=$color_s?>;
	color:#fff;
	text-shadow:0 1px 1px rgba(255,255,255,.0);
	width:8em;

	padding:0;
	margin:0;
	position:fixed!important;
	bottom:150px;
	right:2em;
	font-size:.625em;
	border:none !important;
	z-index:10;
}

#toTop .ui-btn-inner{border:none !important;margin:0;padding:0;top:.5em;}

#top_image{
	position:relative;
	vertical-align:middle;
	width:100%;
	margin:0;
	padding:0;
	top:0;
	left:0;
}
.arrow_top {margin:0;padding:0;height:1em;}




/* page_login */

.content_login {padding:.5em;width:300px !important;margin-left:auto; margin-right:auto;}
	.contents_login {width:90%;padding-top:0;}
	.ebs_ci_l {width:90%;display:table-cell;}
	.image_ci_l {width:90%;}
	.input_login {width:80%;margin-left:auto; margin-right:auto;}
	.img_login {width:100px;}
.pageLogin form  label {
	color:#fff; 
	font-family:'Helvetica', Arial; 
	font-size:.875em; 
	font-weight:normal;
	margin-top:1em;
}/**/
.pageLogin form div input {
	color:#fff;
	margin:0;
	padding:0;
	border:none;
	height:24px;
	line-height:24px;
	font-size:.875em;
} /**/
.text_login .ui-input-text {padding-left:<?=$padding_left?>}/*width:240px;*/
.ui-checkbox-off .ui-icon, .ui-radio-off .ui-icon{background-color: #a0a0a0;}/*체크박스 컬러 checkbox color*/
#checkbox_login {width:10em;}




/*page_intro*/
.userInfo {background:#fff !important;text-align:left !important;margin:0;padding:0;width:100%;margin:0;padding:0;}
.menu_intro {}
.menu_td {text-align:center;}
.menu_intro>.menu_td{display:table-cell;}
.menu_intro>.menu_td:first-child {text-align:left;margin:0;padding:0;left:0;}
.menu_intro>.menu_td:last-child {text-align:right;margin:0;padding:0;right:0;}
.menu_td_ {}
.img_menu {width:100%;}
.menu_td{width:33.3%;margin:0;padding:0;}
.menu_a {width:100%;vertical-align:middle;margin:0;padding:0;text-align:center;display:table-cell;}


.menu_intro td a img{position:relative;margin:0;padding:0;width:99%;margin-right:1em;}/* clip: rect(0 0px 0px 0px);*/
.menu_text {width:100%;position:relative;text-align:center;color:#001d5f;font-family:'Helvetica';}/*font-size:.95em;sans-serif*/

/* header navbar */

	.header_nav .ui-btn .ui-btn-inner {
		/*background:#3c3c3c;*/
		padding-top: 0 !important;
		padding-right: 0 !important;
		padding-left: 0 !important;
		vertical-align:center;
		psition:absolute !important;

	}

	.header_nav .ui-btn .ui-icon {
		width: 10px!important;
		height: 10px!important;
		margin-top:-10px !important;
		margin-left: -24px !important;
		box-shadow: none!important;
		-moz-box-shadow: none!important;
		-webkit-box-shadow: none!important;
		-webkit-border-radius: none !important;
		border-radius: none !important;

	}
	.header_nav div ul li a img{height:20px;margin-top:.5em;margin-bottom:-.5em;z-index:1590;}
	.header_nav {psition:absolute;z-index:1491;}

.custom-navbar ul li a {
	/*background: #646464;*/
	background: #e2e2e2;
	color:rgba(0, 0, 0, 0.0);
	text-decoration:none;
	psition:absolute;
	z-index:10;
}

.custom-navbar ul li a.activeOnce {
text-decoration:none;
	z-index:20;
	psition:absolute;
	color:rgba(0, 0, 0, 0.0);
    /*background: linear-gradient(#5393C5, #6FACD5) repeat scroll 0 0 #5393C5 !important;*/
	background: #5393C5;
		background: -moz-linear-gradient(top,  #5393C5 0%, #6FACD5 100%);
		background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,#5393C5), color-stop(100%,#6FACD5));
		background: -webkit-linear-gradient(top,  #5393C5 0%,#6FACD5 100%);
		background: -o-linear-gradient(top,  #5393C5 0%,#6FACD5 100%);
		background: -ms-linear-gradient(top,  #5393C5 0%,#6FACD5 100%);
		background: linear-gradient(to bottom,  #5393C5 0%,#6FACD5 100%);

}

/* comment*/
.edit_last_comment{
	vertical-align: top;
	float: right;
	width: 12px;
	color: black;
}
.input_comment_text{
	width: 100% !important;
}

#popup_menu_edit_last_comment{
	padding-left: 0px !important;
}

/* notice*/
.form_line {
	float: left;
	width: 100%;
}
.input_form {
    width: 100%;
	float: left;
}
.left {
    float: left;
    width: 100%;
	font-weight: normal;
}
.text {
    padding-top: 0.4em;
    padding-bottom: 0.4em;
    display: block;
    font-size: 16px;
}

.line_upload {float: left; width:4%;height:2.2em;margin-top:.5em;border-left:1px solid #bbbbbb;font-size: 16px;}
.line_upload_1 {float: left; width:4%;height:2.5em;margin-top:.6em;border-left:1px solid #bbbbbb;font-size: 16px;}
.line_upload_2 {float: left; width:4%;height:3em;margin-top:.6em;border-left:1px solid #bbbbbb;font-size: 16px;}
.form_upload {width:90%;margin-left:auto; margin-right:auto;}
.form_upload label {width:15%;display:inline-block;}

.form_upload .ui-input-text{padding-left:<?=$padding_left?>;}

.form_select {width:70%;}


#fileup {visibility:hidden;position:absolute;top:1px;left:0;}
.btn_select{margin:0;padding:0;}
#blah {
	width:100%;/**/
	/*height:10em;
	margin:0;
	padding:2px;*/
}

.line_blank {height:1em;}
.time_store {width:10em}
#selected_time {
	text-align:center;
	vertical-align:middle;
	display:table;
}

.btn_upload .ui-btn-inner {background:#065b89;color:#fff;font-weight:bold;text-shadow:0 1px 1px rgba(255,255,255,.0);}

.form_upload dd {margin:0;padding:0;display:table-cell; vertical-align:middle;}
.form_upload dl {margin:0; display:table; width:100%;}
.popup_alert_out {width:100%;height:100%;z-index:1497;padding:0;margin-left:auto; margin-right:auto;vertical-algin:middle;background:white !important;display:table-cell;position:absolute;top:0;}
.popup_alert_in {padding:1em;position:relative;}

#popup_edit_notice_content .ui-btn-inner {
	padding-right: 6px !important;
	padding-left: 6px !important;
}
.form_select_wrap select#type_group,  .form_select_wrap select#type_user{
	width: 100%;
}

/*upload_list*/
.accept_color {width:.5em;background:red;}
.mediaList .type-list .accept_img{width:.5em;height:100%;background:red;margin:0;padding:0;bottom:0;position:absolute;}

/* table */
.tb {display:table; width:100%; margin:0; padding:0;}
.tb>.tbc {display:table-cell; vertical-align:middle;overflow:hidden;text-overflow:ellipsis;}
.tb>.tbc:first-child {width:8.7em !important;}
.tb.pad>.tbc {padding:0 .3em;}
.tb.pad>.tbc:first-child {padding-left:0;}
.tb.pad>.tbc:last-child {padding-right:0;}

.popup_tc {width:100%;height:100%;z-index:1497;padding:0;margin-left:auto; margin-right:auto;vertical-algin:middle;display:table-cell;position:absolute;top:0;}
#content_tc {padding:1em;position:relative;}



/* more load */
	.ui-more {
		display:none;
		margin:1em .5em; padding:.6em 0; text-align:center; border:1px solid #ccc;
		text-shadow:0 1px 0 rgba(255,255,255,1);
		font-size:.875em; color:#555; text-align:center; vertical-align:middle; cursor:pointer; font-weight:bold;
		background: #edf3f7;
		background: -moz-linear-gradient(top,  #edf3f7 0%, #9bb3c9 100%);
		background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,#edf3f7), color-stop(100%,#9bb3c9));
		background: -webkit-linear-gradient(top,  #edf3f7 0%,#9bb3c9 100%);
		background: -o-linear-gradient(top,  #edf3f7 0%,#9bb3c9 100%);
		background: -ms-linear-gradient(top,  #edf3f7 0%,#9bb3c9 100%);
		background: linear-gradient(to bottom,  #edf3f7 0%,#9bb3c9 100%);
	}
	#loading.ui-more {
		background: #eeeeee;
		background: -moz-linear-gradient(top,  #eeeeee 0%, #cccccc 100%);
		background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,#eeeeee), color-stop(100%,#cccccc));
		background: -webkit-linear-gradient(top,  #eeeeee 0%,#cccccc 100%);
		background: -o-linear-gradient(top,  #eeeeee 0%,#cccccc 100%);
		background: -ms-linear-gradient(top,  #eeeeee 0%,#cccccc 100%);
		background: linear-gradient(to bottom,  #eeeeee 0%,#cccccc 100%);
	}
	#loading_s.ui-more {
		background: #eeeeee;
		background: -moz-linear-gradient(top,  #eeeeee 0%, #cccccc 100%);
		background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,#eeeeee), color-stop(100%,#cccccc));
		background: -webkit-linear-gradient(top,  #eeeeee 0%,#cccccc 100%);
		background: -o-linear-gradient(top,  #eeeeee 0%,#cccccc 100%);
		background: -ms-linear-gradient(top,  #eeeeee 0%,#cccccc 100%);
		background: linear-gradient(to bottom,  #eeeeee 0%,#cccccc 100%);
	}
	#loading_r.ui-more {
		background: #eeeeee;
		background: -moz-linear-gradient(top,  #eeeeee 0%, #cccccc 100%);
		background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,#eeeeee), color-stop(100%,#cccccc));
		background: -webkit-linear-gradient(top,  #eeeeee 0%,#cccccc 100%);
		background: -o-linear-gradient(top,  #eeeeee 0%,#cccccc 100%);
		background: -ms-linear-gradient(top,  #eeeeee 0%,#cccccc 100%);
		background: linear-gradient(to bottom,  #eeeeee 0%,#cccccc 100%);
	}
	.ui-more.show {display:block;}

/* tab
	.ui-tab {margin:0; padding:0; list-style:none; border-bottom:1px solid #d7d7d7;}
	.ui-tab:after {content:''; display:block; clear:both;}
	.ui-tab li {
		float:left; display:block; padding:.4em .4em .4em; background:#f6f6f6; margin-bottom:-1px;
		font-size:.875em; cursor:pointer;
		border-color:#d7d7d7; border-style:solid; border-width:1px 0 1px 1px;
	}
	.ui-tab li,
	.ui-tab li a {color:#666;}
	.ui-tab li a {text-decoration:none;}

	.ui-tab li:last-child {border-right-width:1px;}
	.ui-tab li.active {background:white; border-bottom:1px solid #fff; cursor:default; color:#333;}*/

/* drop down menu */
	.ui-dropdown {margin-left:auto; margin-right:auto;padding:.1em;width:100%;}

	.ui-dropdown ul {
		margin:0; padding:0; list-style:none; background:#fff; /*display:none; border-bottom-left-radius:.3em; border-bottom-right-radius:.3em;*/
	}
	.ui-dropdown ul.active {display:block;}
	.ui-dropdown ul li {
		padding:.4em 1.1em; 
		font-size:.813em; 
		color:#555; 
		cursor:pointer;
		background-color: #9C9C9C;
		margin-right: 0.2em;
		color: white !important;
		text-shadow: none;
	}/*2013-12-13 탭 padding 수정*/
	.ui-dropdown ul li:last-child {border-bottom:none;}
	.ui-dropdown ul li.active {
		font-weight:bold; 
		/*color:<?=$color_s?> !important;*/
		color: white;    
		background-color: #0d9ac8;
	}

/* tab_category */
	.tab_category {
		white-space: nowrap;
		overflow:auto;
		margin:0; padding:0; list-style:none; display:table; width:940px; /*border-radius:.3em;*/
		box-shadow:0 1px 4px rgba(0,0,0,.2);
	}
	.tab_category>li {
		display:table-cell;
		/* width:16.66%; */
		text-align:center;
		height:1.5em;
		vertical-align:middle;
		background-color: #9C9C9C;
    	margin-right: 0.2em;
    	color: white !important;
    	text-shadow: none;
	}
	/*.tab_category>li:first-child a {border-left:none; border-top-left-radius:.2em; border-bottom-left-radius:.2em;}
	.tab_category>li:last-child a {border-right:none; border-top-right-radius:.2em; border-bottom-right-radius:.2em;}
	.tab_category.col3>li {width:33.33%;}
	.tab_category.col3>li:nth-child(2) {width:33.34%;}*/

/* tab_category_view mov_view*/
	.tab_category_view
	{
		margin:0;
		padding:0;
		list-style:none;
		display:table;
		width:100%;
		/*width: 32em;*/
		white-space: nowrap;
		overflow:auto;
		box-shadow:0 1px 4px rgba(0,0,0,.2);/*border-radius:.3em;*/
	}
	.tab_category_view>li {display:table-cell; text-align:center;}
/*	.tab_category_view>li {display:table-cell; width:32%; text-align:center;}*/
/*	.tab_category_view>li:last-child {display:table-cell; width:36%; text-align:center;}*/

/* newstest document list */
	.ui-docList {margin:0; padding:0; list-style:none;}
	.ui-docList>li {border-bottom:1px solid #ccc;}
	.ui-docList>li>a {display:block; padding:.7em 0 .7em .2em; text-decoration:none; font-size:.813em; color:#555;}
	.ui-docList>li .bd {border-top:1px dashed #ccc; font-size:13px; display:none; padding:1em .5em; color:#666; margin:0;}
	.ui-docList>li.active a {background:#ddd; font-weight:bold; padding-left:.5em;}
	.ui-docList>li.active .bd {display:block;}

.ui-input-corner-all {
  -moz-border-radius: 0px;
  -webkit-border-radius: 0px;
  border-radius: 0px;
}





.whole_page {background:#fff !important;}

/*search popup
#searchText {
	width:100%;

}*/

.viewport .page_content{margin:0;padding:0;}
.page_content .searchBox{margin:0;padding:0;}

/* .searchbox */
.searchBox form {width:100%;margin:0;padding:0;background:#d8d8d8;/*display:none;background:#cc00cc; #d8d8d8*/}
.searchBox.active {display:block;}
.searchBox fieldset {margin:0; padding:0; border:none;}
.searchBox dl {margin:0; display:table; width:100%;}
.searchBox dd {display:table-cell; vertical-align:middle; }
.searchBox .keyword span {width:97%;display:block; border:0px solid #bbb; padding:0 .2em 0 .2em;margin:0;}
.searchBox .keyword span input {border:0; margin:0; padding:0; height:35px;  font-size:.813em;}
.btn_search {display:table-cell;vertical-align:middle;padding:0 .2em 0 0;}

.keyword_1 {padding:0 .2em 0 .2em;}

.searchBox .btn_search .ui-btn-inner {
	width:2em;
	height:35px;
	border:none;
	padding:0;
	margin:0 auto;
	vertical-align:middle;

	display:block;
	text-align:center;
	position:relative;
	top:.3em;
}
.btn_search img{margin-left:auto; margin-right:auto;position:relative;vertical-align:middle;padding:.1em;}


.b1 {height:1.3em;padding:0;margin:0;}
.searchBox dd button {
	 border:0px solid #bbb; font-size:.75em;
	/*background: #ffffff;
	background: -webkit-linear-gradient(top, #ffffff 20%,#dddddd 100%);
	background: -moz-linear-gradient(top, #ffffff 20%,#dddddd 100%);
	background: -o-linear-gradient(top, #ffffff 20%,#dddddd 100%);
	background: -ms-linear-gradient(top, #ffffff 20%,#dddddd 100%);
	background: linear-gradient(to bottom, #ffffff 20%,#dddddd 100%);*/
}
.searchBox dd span {/*display:inline-block; vertical-align:middle;*/}




/* media list */
.mediaList {padding:0;width:100%;margin:0;}/*border:1px solid #000;*/
.text_icon {
	padding:3px;
	height:.3em;
	color:#fff;
	font-weight:bold;
	text-shadow:0 1px 1px rgba(255,255,255,.0);
	margin:0;
	line-height:.05em;
	}
.icons_img img{
	padding:3px;
	height:14px;
	z-index:4;
}
.mediaList .type-list .bookmark_star{
	width:14px;
	margin:0;
	padding:0;
	top:0;
	z-index:3;
}
.table_star {
	text-align:right;
	right:0;
	z-index:2;
	position:absolute;
}
.table_icon {
	width:150px;
	height:20px;
	position:absolute;
	vertical-align:middle;
	top:0;
	background: rgba(0, 0, 0, 0.3);
	z-index:1;
}

.nobr_list {overflow:hidden;text-overflow:ellipsis;}

.mediaList .listType {margin:1em .5em .5em;}


.mediaList .type-list .tumbnail_img{display:block; position:relative; border:0px solid #bbb; width:150px;height:85px;}

.mediaList #list {margin:0; padding:0; list-style:none;}
.mediaList #list dl {margin:0;padding:0;}
.mediaList #list a {padding:.2em; display:block; text-decoration:none;position:relative;}
.mediaList #list dd strong {color:#044a82; font-size:.875em;overflow:hidden;}/*044a82*/
.mediaList #list dd strong video {display:none;}
.mediaList #list dd .st {font-size:.875em; color:#707070; font-weight:bold;}/*707070*/
.mediaList #list dd p {margin:0;}
.mediaList #list dt {position:relative;}
/*.mediaList #list dt img {display:block; position:relative; border:1px solid #bbb; width:100%;}*/
/*.mediaList #list dt i {display:block; width:30px; height:20px; position:absolute; left:0; top:0; background-position:0 -111px;}*/
.mediaList #list dd {margin:0;}
.mediaList #list .noimg {border:0px solid #bbb; font-size:.813em; text-align:center; padding:1em 0; color:#888;}

.mediaList .type-list li {border-bottom:1px solid #ececec;}
.mediaList .type-list dl {}
.mediaList .type-list dl:after {content:''; display:block; clear:both;}
.mediaList .type-list dt,
.mediaList .type-list dd {vertical-align:top;}
.mediaList .type-list dt {width:150px; float:left; margin-right:.5em;}
.mediaList .type-list dd {display:block;padding-left:160px;padding-right:5px;}
.mediaList .type-list dd .inf {margin-top:.3em;}
.mediaList .type-list dd .inf span {display:block; color:#777; font-size:.813em;}

.mediaList .type-thumnail:after {content:''; display:block; clear:both;}
.mediaList .type-thumnail li {float:left; width:50%;}
.mediaList .type-thumnail li:nth-child(2n+1) {clear:both;}
.mediaList .type-thumnail a {padding:.5em .5em 1em;}
.mediaList .type-thumnail dt {}
.mediaList .type-thumnail dd strong,
.mediaList .type-thumnail dd .st {display:block; width:100%; white-space:nowrap; overflow:hidden;}
.mediaList .type-thumnail dd .inf {display:none;}
.mediaList .type-thumnail .noimg,
.mediaList .type-thumnail dt img {margin-bottom:.2em; width:auto !important; max-width:100%;}
.mediaList #list .empty {float:none; width:auto; text-align:center; font-size:.875em; color:#555; font-weight:bold; padding:2em 0;}


/* media detail */
.mediaDetail {padding-bottom:1em;}
.mediaDetail .vid {text-align:center;width:100%;margin-bottom:-10px;padding:0;}/*height:100px;*/
.modal_img {
	background: rgba(0, 0, 0, 0.0) ;/**/
	z-index:10;
	width:100%;
	height:100%;
	position:absolute;
}
.btngroup_view {margin:0 0 0 5px;width:95%;padding:0;}

.play_thumb {height:1px;width:100%;-webkit-touch-callout:none;}
.img_thumb {padding:0;margin:10px;-webkit-touch-callout:none;}
/*.img_thumb {margin:0 auto;position:relative;top:8px;width:100%;}height:84px;*/

.table_play{display:block;width:100%;position:absolute;}

.btn_play {height:40px;width:40px;}

.mediaDetail .view {display:block; background:black; min-height:100px; position:relative; cursor:pointer; text-indent:-9999px;}
.mediaDetail .view i {
	position:absolute; display:block; left:50%; top:50%;
	width:38px; height:59px;
	margin-left:-16px; margin-top:-28px;
	background-position:-61px -111px;
}
.mediaDetail .ui-dropdown {margin:0;width:100% !important;}
.mediaDetail .bd {padding:0 .5em 0;}
.mediaDetail .bd h1, .mediaDetail h1{font-size:1.25em; color:#044a82;}
.mediaDetailTitle h1{font-size:1.25em; color:#044a82;display: block;white-space: nowrap;text-overflow: ellipsis;overflow: hidden;margin-bottom: 0;}
.mediaDetailTitle a{padding: 0.5em 0.3em 0em 0.5em;}
.mediaDetail .bd .con {margin:.2em 0 0 0; padding:0; list-style:none;}
.mediaDetail .bd .con>li {
	display:none; padding:0 .2em;
	border-top:1px solid #aaa; border-bottom:1px solid #aaa;
}
.mediaDetail .bd .con>li.active {display:block;}
.mediaDetail .bd .con>li dl {border-bottom:1px dashed #ccc; padding:.5em 0;}
.mediaDetail .bd .con>li dl:last-child {border-bottom:none;}
.mediaDetail .bd .con>li dt,
.mediaDetail .bd .con>li dd {font-size:.75em;}
.mediaDetail .bd .con>li dt {width:90px; color:#333; border-right:1px solid #ccc; font-weight:bold;}
.mediaDetail .bd .con>li dd {color:#666;padding-left:.5em;}
.mediaDetail .btngroup {text-align:center;}

#contentBody > table >tbody> tr> td, #contentBody > table >thead > tr> th {
	border-bottom:1px dashed #ccc; padding:.5em 0;
}

/*#mediaPlay {background:red;}*/
#mediaPlay video {width:100%; height:240px; margin:0 auto;}/*240px*/


/* media form */
.mediaForm {}
.mediaForm form {margin:0; padding:.5em;}
.mediaForm fieldset {margin:0; padding:0; border:none;}
.mediaForm ul {margin:0; padding:0; list-style:none;}
.mediaForm ul li {margin:0; padding:.5em .2em; border-bottom:1px solid #ccc;}
.mediaForm label {display:block; font-weight:bold; font-size:.875em; margin-bottom:.3em; color:#444;}
.mediaForm p {margin:.4em 0; font-size:.813em;}
.mediaForm strong {color:#888;}

.mediaForm .box {margin-top:.5em;}
.mediaForm .box fieldset {border:1px solid #ccc; padding:.5em;}
.mediaForm .box legend {font-size:.875em; font-weight:bold; color:#444;}
.mediaForm .box li:last-child {border:none; padding-bottom:0;}

.mediaForm .btngroup {margin:.5em 0 0;}


/* mypage */
.mypageMain .persnalInfo {margin:1em 0;}
.mypageMain .persnalInfo h1 {margin:0 0 .3em; padding:0 10px; font-size:1em;}
.mypageMain .persnalInfo ul {margin:0; padding:0; list-style:none; border-top:1px solid #d7d7d7;}
.mypageMain .persnalInfo li {border-bottom:1px solid #d7d7d7;}
.mypageMain .persnalInfo dl {margin:0; display:table; width:100%;}
.mypageMain .persnalInfo dt,
.mypageMain .persnalInfo dd {display:table-cell; vertical-align:middle; font-size:.813em; padding:.8em 0;}
.mypageMain .persnalInfo dt {width:70px; padding-left:10px; color:#333; border-right:1px solid #ddd; background:#f6f6f6;}
.mypageMain .persnalInfo dd {margin:0; color:#666; padding-left:10px;}

.mypageMain .body {padding:0 .5em;}
.mypageMain .body .con {margin:0; padding:0; list-style:none;}
.mypageMain .body .con>li {display:none;}
.mypageMain .body .con>.active {display:block;}

.mypageMain .btngroup {margin:1em 0 1.5em;}
.mypageMain .btngroup ul {margin:0; padding:0; list-style:none; border-top:1px solid #ccc;}
.mypageMain .btngroup li {border-bottom:1px solid #ccc;}
.mypageMain .btngroup li a {
	display:block; padding:.7em .5em; background:#ececec; position:relative;
	font-size:.875em; color:#383838; text-decoration:none;
}
.mypageMain .btngroup li a i {
	position:absolute; right:.5em; top:50%; margin-top:-5px;
	display:block; width:6px; height:12px; background-position:0 -180px;
}

.mypageMain .ui-more {margin-left:0; margin-right:0;}

.mypageMain .logout {margin-left:1em; margin-right:1em;}

.scrollable_x {overflow-x: scroll; -webkit-overflow-scrolling: touch;}

/* listComment */
.listComment>dl:first-child {font-weight:bold;}
.listComment>dl dd:first-child {width:30% !important;}
.listComment>dl dd {margin: 0 auto !important; padding-top: 6px;}
.listComment>dl dd.listCommentVer {width: 4em;}
.listComment>dl dt {	
	all: none;
	padding-left: 21px;
	background: url(../../css/images/reply.png) no-repeat 0 2px;
	border-right: 0 !important;
	font-weight : normal !important;
	width : auto !important;
}
.listComment>dl {
	display : block !important;
}

/* edit metadata */
.edit_ui_field_contain>label {
	//float: left;
	font-weight: bold;
	width: 50%;
	font-size: .75em;
}
.edit_ui_field_contain>input {
	float: right;
	width: 100%;
	font-size: .75em;
}
.edit_ui_field_contain>textarea {
	float: right;
	width: 100%;
	font-size: .75em;
}
.edit_ui_field_contain{
	/*margin: 0.2em 0;*/
}

/* history edit metadata */
.history_edit_ui_field_contain>label {
	//float: left;
	font-weight: bold;
	width: 50%;
	font-size: .75em;
}
.history_edit_ui_field_contain>input {
	float: right;
	width: 100%;
	font-size: .75em;
}
.history_edit_ui_field_contain>textarea {
	float: right;
	width: 100%;
	font-size: .75em;
}
.history_edit_ui_field_contain{
	/*margin: 0.2em 0;*/
}

.edit_ui_field_contain #tree_menu_category{
	border: 1px solid #bbb;
	border-radius: .6em;
	margin-top: 1em;
	padding-left: 1em;
}

/* nav menu */
.nav_menu {
	overflow-x: scroll; /* 1 */
	-webkit-overflow-scrolling: touch; /* 2 */
}
.nav_menu>ul {
	margin: 0;
	text-align: justify; /* 3 */
	/*width: 32.5em;  4 */
	width: 100%;
}
.nav_menu>ul:after { /* 5 */
	content: '';
	display: inline-block;
	/*width: 100%;*/
}
.nav_menu> ul li {
	display: inline-block; /* 6 */
}

@media (min-width: 31.25em) {
	.nav_menu>ul {
		max-height: none; /* reset the max-height */
		overflow: hidden; /* this prevents the scroll bar showing on large devices */
	}
	.nav_menu>ul {
		width: 100%;
	}
}

/* dtree for category tree */
#categories> li.current_category > a{
	color: red;
}
#categories> li{
	cursor: pointer;
}
#tree_menu_category{
	//border:solid;
	//position: absolute;
	background-color: white;
}

.tab_comment_time{
	width: 20% !important;
}
.tab_comment_user{
	width: 20% !important;
}
.tab_comment_value{
	/*word-wrap: break-word !important;*/
	word-break:break-all;
	white-space: pre-wrap;
}

.tab_history_log_field_id{
	width: 20% !important;
	word-break:break-all;
	white-space: pre-wrap;
	font-size: 0.75em;
}
.tab_history_log_old_value{
	width: 40% !important;
	word-break:break-all;
	white-space: pre-wrap;
	border-right: 1px solid #ccc;
	border-left: 1px solid #ccc;
	font-size: 0.75em;
	padding-left: .5em;
}
.tab_history_log_new_value{
	width: 40% !important;
	word-break:break-all;
	white-space: pre-wrap;
	font-size: 0.75em;
	padding-left: .5em;
}
.tab_basic_information_value{
	word-break:break-all;
	white-space: pre-wrap;
}
.word_break_white_space{
	word-break:break-all;
	white-space: pre-wrap;
}

.tb>.tab_comment {display:table-cell; vertical-align:top;overflow:hidden;text-overflow:ellipsis;}

.tb>.tab_history_log {
	display:table-cell; 
	vertical-align:middle;
	overflow:hidden;
	text-overflow:ellipsis;
	font-weight: normal;
}

#popup_history_edit_metadata_content>dl {border-bottom:1px dashed #ccc; padding:.5em 0;}

#left-panel{
	width: 17em !important;
}

.tab_list_history_log{
	text-align: center;
}

#video_nav{
	//border: 2px solid #9C9C9C;
}
.header_nav{
	//border: 1px solid white;
}

.new_cnt {
	background:#FF0000;
	/* position: absolute; */ 
	min-width:11px;
	height: 11px;
	color:#ffffff;
	/* border-radius: 34%; */
	padding: 0px 2px 2.5px 2px;
	text-align: center;
	font-size: 12px;
	float: right;
	margin-top: -4px;
	margin-right: -13.5px;
	text-align: center;
	/* border: 2px solid white; */
}
.new_total_cnt{
	background:#FF0000;
	/* position: absolute; */ 
	min-width:11px;
	height: 11px;
	color:#ffffff;
	/* border-radius: 34%; */
	padding: 0px 2px 3px 2px;
	text-align: center;
	font-size: 12px;
	float: right;
	margin-top: -31px;
	margin-right: 4px;
	text-align: center;
	/* border: 2px solid white; */
}

.totop .ui-btn-inner{
	height:2.4em;
	vertical-align:top;
	line-height:1.25em;
}
.ui-popup{
	/* border:none !important; */
	/* background:rgba(255,255,255,.0); */
	/* color:#646464; */
	font-weight:bold;
}

.mov_view_button, .tbc, .select_metadata_type, .popup_advanced_search, .select_metadata_items{
	font-size: .813em !important;	
}

.select_metadata>form>div.search_metadata>p{
	display:inline-block !important;
}
.select_metadata>form>div.search_metadata>div{
	/*display:inline-block !important; */
}
.select_create_date{
	text-align: center;
}
.select_create_date>p{
	display:inline;
	text-align: center!important;
}
.select_create_date>div{
	display:inline-block;
}
.select_metadata_title{
	font-weight: normal;
}
.select_metadata{
	width: 96%;
	margin: 0 auto;
}
.search_metadata{
	font-weight: normal;
	font-size: .813em !important;
	width: 95%;
    margin: 0 auto;
}

.ui-popup-container, .ui-popup {
    width: 98%;
    position: absolute;
    top: 10;
    left:0;
}

.text_tree{
	font-size: 1.3em !important;
	/*display: block;
	white-space: nowrap;
	text-overflow: ellipsis;
	overflow: hidden; */
}

/* font awesome */
.fa-stack{
	width: 1.2em !important;
	text-align: center;
	line-height: 1.5em !important;
}
span.fa-stack > strong {
	top: -2px !important;
}
span.fa-stack > i:last-child {
	top: -2px !important;
}
.mov_view_button_download{
	float:right;
}
div.icons_img .fa{
	text-shadow: none !important;
}
.border_field_metadata{
	border: 1px solid #bbb;
	border-radius: .6em;
	margin-top: 1em;
}
.border_field_metadata > form{
	margin-top: -1em;
}