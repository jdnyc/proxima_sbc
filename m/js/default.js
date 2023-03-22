function log(o)
{
}

function $log(o)
{
}


var
	iOS = (navigator.userAgent.match(/(iPad|iPhone|iPod)/i) ? true : false)
	,datagu_mapping_num_array = [
		'참조영상',
		'TV방송',
		'소재영상',
		'Radio방송',
		'Radio소재',
		'음반',
		'정간물',
		'단행본',
		'공테이프'
	]
	,datagu_combo_data2 = [
		{name : '전체', label : ['자료명1','자료명2','자료명3'], formType : 0, n : '0'}
		,{name : 'TV 방송', label : ['프로그램명','부제','방송일자'], formType : 1, n : '81722'}
		,{name : '소재영상', label : ['자료형태','연출자','자료명'], formType : 0, n : '81767'}
		,{name : '참조영상', label : ['자료형태','원어표제명','한글표제명'], formType : 0, n : '81768'}
		,{name : 'Radio 방송', label : ['프로그램명','부제','방송일자'], formType : 1, n : '4023846'}
		,{name : '음반', label : ['분류번호','원어표제명','한글표제명'], formType : 0, n : '81771'}
		,{name : '단행본', label : ['청구기호','서명','저자'], formType : 0, n : '81773'}
		,{name : '정간물', label : ['원어정간물명','발행사','Vol/Iss.no'], formType : 0,n : '81774'}
		,{name : '공테이프', label : ['공테이프형태','테이프시간','제작회사'], formType : 0, n : '9'}
	]


	,sort_combo_data_loan_list = [
		['선택','0']
		,['부서','1']
		,['성명','7']
		,['자료구분','2']
		,['등록번호','3']
		,['자료명1','4']
		,['자료명2','5']
		,['자료명3','6']
		,['대출일자','8']
		,['반납예정일','9']
	]
	,download_combo_data = [
		{name : '전체', label : ['자료명1','자료명2','자료명3'], formType : 0, n : '0'}
		,{name : 'TV 방송', label : ['프로그램명','부제','방송일자'], formType : 1, n : '81722'}
		,{name : '소재영상', label : ['자료형태','연출자','자료명'], formType : 0, n : '81767'}
		,{name : '클립영상', label : ['프로그램명','부제','연출자'], formType : 0, n : '81770'}
		,{name : 'EDRB', label : ['원곡명','주요내용','EDRBID'], formType : 0, n : '4023848'}
		,{name : '참조영상', label : ['자료형태','원어표제명','한글표제명'], formType : 0, n : '81768'}
		,{name : 'Radio 방송', label : ['프로그램명','부제','방송일자'], formType : 1, n : '4023846'}
		,{name : '곡', label : ['원어곡명','한글곡명','가수'], formType : 0,n : '81772'}
		,{name : '이미지', label : ['제목','프로그램명','인물정보'], formType : 0, n : '4023847'}
	]
	,dept_combo_data = [['전체','0'],['EBS수능연계교재품질','3106'],['IT서비스운영부','3093'],['IT인프라관리부','3092'],['감사실','3103'],['고객서비스부','3091'],['광고문화사업부','3101'],['교양문화부','3060'],['교육뉴스특임부','3055'],['교육다큐부','3059'],['교육리소스부','3089'],['교육방송연구소','3052'],['국제협력실','3054'],['글로벌콘텐츠부','3087'],['기획예산부','3080'],['노동조합','3104'],['뉴미디어기획부','3081'],['대외협력실','3107'],['디지털영상부','3074'],['디지털인프라부','3071'],['디지털통합사옥건설단','3108'],['라디오부','3063'],['비서실','3051'],['수능교육부','3067'],['스마트서비스센터','3090'],['심의실','3053'],['영어교육부','3069'],['외국어사업부','3099'],['외주제작부','3086'],['운영지원부','3096'],['유아어린이특임부','3062'],['융합미디어본부','3070'],['이사회','1991'],['이사회사무국','3102'],['인적자원부','3094'],['임원실','2000'],['재무회계부','3095'],['정책기획센터','3079'],['제작기술1부','3072'],['제작기술2부','3073'],['제작아트1부','3075'],['제작아트2부','3076'],['제작아트3부','3077'],['조직법무부','3082'],['중계부','3078'],['진로직업청소년부','3061'],['창의인성부','3068'],['출판사업부','3100'],['콘텐츠기획센터','3084'],['콘텐츠사업단','3097'],['콘텐츠사업부','3098'],['편성기획부','3085'],['평생교육기획부','3058'],['평생교육본부','3056'],['플랫폼운영부','3088'],['학교교육기획부','3065'],['학교교육본부','3064'],['학교출판기획부','3066'],['홍보사회공헌부','3083']]
	,condi_combo_data_loan_list = [
		['전체','0']
		,['대기','1']
		,['승인','2']
		,['반려','3']
	]
	,loan_combo_data = [
		['선택','0', 1]
		,['제작','1', 0]
		,['사업','2', 1]
		,['자회사','3', 0]
		,['기타','4', 1]
	]
	,res_combo_data = [
		['(원본)','0','XDCAM 422 QuickTime Movie']
		,['홈초이스 HD','4','MPEG-PS 1920x1080 15M']
		,['홈초이스 SD','5','MPEG-PS 720x480 8M' ]
		,['POOQ','6','H264 1280x720 2M']
		,['WMV','7','WMV 720x400 1M']
		,['EBS미디어_DVD','8','MPG 720x480 5M']
		,['EBS미디어_교재','9','MPG 720x486 9M']
		,['유튜브_HD','10','MPEG4 720x400 1M']
		,['유튜브_SD','11','MPEG4 640x480 1M']
		,['시공미디어 HD','12','MPEG-PS 1920x1080 25M']
	]
	,history_combo_data = [
		{name : '전체', label : ['자료명1','자료명2','자료명3'], formType : 0, n : '0'}
		,{name : 'TV 방송', label : ['프로그램명','부제','방송일자'], formType : 1, n : '81722'}
		,{name : '소재영상', label : ['자료형태','연출자','자료명'], formType : 0, n : '81767'}
		,{name : '클립영상', label : ['프로그램명','부제','연출자'], formType : 0, n : '81770'}
		,{name : 'EDRB', label : ['원곡명','주요내용','EDRBID'], formType : 0, n : '4023848'}
		,{name : '참조영상', label : ['자료형태','원어표제명','한글표제명'], formType : 0, n : '81768'}
		,{name : 'Radio 방송', label : ['프로그램명','부제','방송일자'], formType : 1, n : '4023846'}
		,{name : '음반', label : ['분류번호','원어표제명','한글표제명'], formType : 0, n : '81771'}
		,{name : '곡', label : ['원어곡명','한글곡명','가수'], formType : 0, n : '81772'}
		,{name : '단행본', label : ['청구기호','서명','저자'], formType : 0, n : '81773'}
		,{name : '정간물', label : ['원어정간물명','발행사','Vol/Iss.no'], formType : 0, n : '81774'}
		,{name : '이미지', label : ['제목','프로그램명','인물정보'], formType : 0, n : '4023847'}
		,{name : '공테이프', label : ['공테이프형태','테이프시간','제작회사'], formType : 0, n : '9'}
	]
;



//html 특수문자 처리
function checkText(text){
	return String(text).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/”/g, '&quot;');
}
// 20131215 -> 2013-12-15로 변환
function stringToDate(o)
{
	return o.substr(0, 4) + '-' + o.substr(4, 2) + '-' + o.substr(6, 2);
}


// 051223 -> 05:12:23로 변환
function stringToTime(o)
{
	return o.substr(0, 2) + ':' + o.substr(2, 2) + ':' + o.substr(4, 2);
}


// 20131215051223 -> 2013-12-15 05:12:23
function stringToFullDate(o)
{
	var
		date = o.substr(0, 8)
		,time = o.substr(8, 6)
	;
	time = (parseInt(time) > 0) ? ' ' + stringToTime(time) : '';
	//return stringToDate(date) + time;
	return stringToDate(date);
}

// 2016-02-29 -> 20160229000000
function startDateToString(o){
	var s_dt = '';
	var dateAr = o.split('-');
	s_dt = dateAr[0] + dateAr[1] + dateAr[2] + '000000';
	return s_dt;
}

// 2016-02-29 -> 20160229999999
function endDateToString(o){
	var e_dt = '';
	var dateAr = o.split('-');
	e_dt = dateAr[0] + dateAr[1] + dateAr[2] + '999999';
	return e_dt;
}

//yyyy-MM-dd
function dateFormat(o){
	var yyyy = o.getFullYear().toString();
	var mm = (o.getMonth()+1).toString(); // getMonth() is zero-based
	var dd  = o.getDate().toString();
	return yyyy + '-'+(mm[1]?mm:"0"+mm[0]) + '-'+(dd[1]?dd:"0"+dd[0]); // padding
}

// 10 이하의 숫자는 0x로 변환
function getFormattedPartTime(partTime)
{
	if (partTime < 10)
	{
		return "0"+partTime;
	}
	return partTime;
}


// object -> post data
function objectToPost(obj)
{
	var result = '';
	for (var i in obj)
	{
		result += i + '=' + obj[i] + '&';
	}
	return result;
}


// json Event
function getJsonData(opt)
{
	$.ajax({
		url : opt.url
		,type : opt.type
		,data : opt.parameter
		,dataType : opt.dataType
		,cache: opt.cache
		//,timeout : 3000
		,error : function()
		{
			//alert('Error ajax');
		}
		,success : function(json)
		{
			if (json.success || json.result)
			{
				opt.complete(json);
			}
		}
	});
}


/* loading show/hide */
function loadingSw()
{
	return {
		show : function(o)
		{
			this.element = o;
			this.element.addClass('show');
			
			
		}
		,hide : function(o)
		{
			this.element = o;
			this.element.removeClass('show');
		}
	};
}

function showModal(){
	//$(".whole_page").on('touchmove', function(e) {
		//e.preventDefault();
		//e.stopPropagation();
	//});
	$(".whole_page").append('<div class="modalWindow"/>');
	$.mobile.showPageLoadingMsg();
	
	//setTimeout('hideModal()', 4000);
}

function hideModal(){
	if($('#search_flag'))
	{
		$('#search_flag').popup('close');
	}
	if($('#message_upload'))
	{
		$('#message_upload').popup('close');
	}
	$(".modalWindow").remove();
	$.mobile.hidePageLoadingMsg();
	//$(".whole_page").on('touchmove', function(e) {
		//$.mobile.defaultPageTransition  = 'slide';
	//});
}



function showModal_s(){
	$(".whole_page").append('<div class="modalWindow"/>');
	$.mobile.showPageLoadingMsg();
	setTimeout('hideModal()', 2000);
}

function alert_s(text1) {
  $("#sure .alert_content").text(text1);
  $('#sure').popup("open");
  //$.mobile.changePage("#sure");
}

function saveHash(hash, scroll, limit){
	var array_hash = new Array();
	array_hash.push(scroll);
	array_hash.push(limit);
	window.location.hash= array_hash.join('a');
}


/* infinite scroll
function iscroll(func)
{
	var
		loading = false
		,h = (iOS) ? 60 : 0
	;

	$(window).scroll(function(){
		if (($(window).scrollTop() >= $(document).height() - $(window).height() - h) && (nomore == false))
		{
			if (loading == false)
			{
				loadingSw.show();
				loading = true;
				var timer = setInterval(function(){
					clearInterval(timer);
					var result = func();
					loading = false;
				},300);
			}
		}
	});
}
 */

 
 function iscroll(func)
{
	$('#more').on('click', function(){
		showModal_s();
		func();
	});
}