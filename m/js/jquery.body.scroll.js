/*
작성자 : 서동희 (dawnseo@naver.com)
작성일 : 2011.01.04

사용방법
1) 파일연결
   <script type="text/javascript" src="/jscript/jquery.seo/jquery.cookie.js"></script>
   <script type="text/javascript" src="/jscript/jquery.seo/jquery.body.scroll.js"></script>
2) 끝
*/
$(function(){
	//get
	if($.cookie('scroll_loc')){
		$(window).scrollTop($.cookie('scroll_loc'));
	}
	//set
	$(window).scroll(function(){
		$.cookie('scroll_loc',$(this).scrollTop());
	});
});
