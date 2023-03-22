/**
 *  jQuery Plugin for touch
 * Author : hwang jong yeol
 * 
 * id or class 값으로만 세팅 가능
 * ex1) $.h_touch('#touch-event', {movePage:false});
 * <div id="touch-event"><div>1</div><div>2</div><div>3</div></div>
 * ex2) $.h_touch('.touch-event');
 * <div class="touch-event"><div>1</div><div>2</div><div>3</div></div>
 * 
 * Date : 
 * var 0.0.1 2013-01-25
 * var 0.1.2 2013-03-06
 * 
 * options{
 * 	1. childrenTagName[var 0.1.1]
 *    - default : ""
 *    - description : 슬라이딩 될 자식 태그를 찾는다.
 *    - use : $.h_touch('#touch-event', {childrenTagName:'#children'});
 *    <div class="touch-event"><div>Hello~touch</div><span id="children">2</span><span>3</span></div>
 * 
 *  2. partition[var 0.1.1]
 *    - default : 1
 *    - description : 한 하면에 보여질 개수(페이지 수)
 *    - use : $.h_touch('#touch-event', {partition:2});
 *    
 *  3. movePartition(추후 개선 / partition 값하고 동일 하게 세팅 )
 *    - default : 1
 *    - description : 슬라이딩 시 한번에 이동 할 개수(페이지 수)
 *    - use : $.h_touch('#touch-event', {movePartition:2});
 *    - code : [_ERR_01]
 *    
 *  4. movePage[var 0.1.1]
 *    - default : false
 *    - description : true 일경우 페이지 순환
 *    - use : $.h_touch('#touch-event', {movePage{use:true, pageLink:true}});
 *    
 *    
 *  5. speed[var 0.1.1]
 *    - default : 100
 *    - description : 터치 반응 속도
 *    - use : $.h_touch('#touch-event', {speed:100});
 *    
 *  6. pageXY[var 0.1.1]
 *    - default : "X"
 *    - description : 좌우 or 상하  슬라이딩 반응 설정 "X" or "Y"
 *    - use : $.h_touch('#touch-event', {pageXY:"Y"});
 *    
 *  7. log[var 0.1.1]
 *  - default : true
 *  - description : log 보기
 *  - use : $.h_touch('#touch-event', {log:true});
 *  - log(el);
 *  
 *  8. pageButton[var 0.1.2]
 *  - default : pageButton{show: false}
 *  - description : 다음, 이전 버튼 이벤트 활성화
 *  - pageButton{
 *  	- show : 사용여부 true/false | default : false
 *  	- next : 다음 이벤트 버튼 여부 [id 값 or class값]
 *  	- prev : 이전 이벤트 버튼 여부 [id 값 or class값]
 *  	- maxPage : 최대페이지 표시 여부 [id 값 or class값]
 *  	- nowPage : 현제페이지 표시 여부 [id 값 or class값]
 *  - @ pageLink 옵션 사용시 maxPage,nowPage 옵션 사용 불가
 *  }
 *  - use : $.h_touch('#touch-event', {pageButton:{show:true,next:"#id",prev:"#id"}});
 *  <div>
 *      <input type="button" id="prev" value="이전" />
 *		<span id="nowpage"></span> / <span id="maxpage"></span>
 *		<input type="button" id="next" value="다음" />
 *  </div>
 * }
 * 
 *  9. pageLink[var 0.1.2]
 *  - default : false
 *  - description : true 경우 페이지 순환 단, 빈 칸이 없이 순차적으로 컨텐츠를 연결 후 순환 
 *  - use : $.h_touch('#touch-event', {pageLink:true});
 *  
 *  
 *  10. animate[var 0.1.2]
 *  - default : false
 *  - description : 자동적으로 순환
 *  - use : $.h_touch('#touch-event', {animate:{show:true, time:2000}});
 *  - time : 1000 > 1초
 *  
 *  
 * */

$.h_touch = function(id, options) {
	//webkit 
	vendor = (/webkit/i).test(navigator.appVersion) ? 'webkit' :
		(/firefox/i).test(navigator.userAgent) ? 'Moz' :
		(/trident/i).test(navigator.userAgent) ? 'ms' :
		'opera' in window ? 'O' : '';
				
	// Browser capabilities
    isAndroid = (/android/gi).test(navigator.appVersion);
    isIDevice = (/iphone|ipad/gi).test(navigator.appVersion);
    isPlaybook = (/playbook/gi).test(navigator.appVersion);
    isTouchPad = (/hp-tablet/gi).test(navigator.appVersion);

    has3d = 'WebKitCSSMatrix' in window && 'm11' in new WebKitCSSMatrix();
    hasTouch = 'ontouchstart' in window && !isTouchPad;
    hasTransform = vendor + 'Transform' in document.documentElement.style;
    hasTransitionEnd = isIDevice || isPlaybook;
    
    // Helpers
	trnOpen = 'translate' + (has3d ? '3d(' : '('),
	trnClose = has3d ? ',0)' : ')',
			
	// Events
	RESIZE_EV = 'onorientationchange' in window ? 'orientationchange' : 'resize';
	START_EV = hasTouch ? 'touchstart' : 'mousedown';
	MOVE_EV = hasTouch ? 'touchmove' : 'mousemove';
	END_EV = hasTouch ? 'touchend' : 'mouseup';
	CANCEL_EV = hasTouch ? 'touchcancel' : 'mouseup';				//미구현
	WHEEL_EV = vendor == 'Moz' ? 'DOMMouseScroll' : 'mousewheel';	//미구현
	DRAGSTART_EV = "dragstart";										//미구현
	DRAG_EV = "drag";												//미구현
	DRAGEND_EV = "dragend";											//미구현
	
    // Default options
	var t = this;
    t.op = {
    		childrenTagName : "",
    		partition : 1,
    		movePartition : 1,
    		movePage : {
    			use:false,
    			pageLink:false
    		},
    		speed : 100,
    		pageXY : "X",
    		log : false,
    		pageButton : {
    			show:false,
				next:"", 
		   		prev:"",
		   		maxPage:"",
		   		nowPage:""
    		},
    		animate:{
    			show: false,
    			time: 5000
    		}
    };

    // User defined options
    for (i in options) t.op[i] = options[i];

    /* code : [_ERR_01]
     * movePartition 버그로 인한 추후 개선
     * partition 값하고 동일하게 해준다.
     * */
    t.op.movePartition = t.op.partition;
    /*[_ERR_01] END*/
	
	base = new Base(id, t.op);/*기본 변수*/
	
	//모든 노드의 값을 display none 시킨다.
    $(base.pId).css("display","none");
    
    //2. 한 하면에 보여질 개수
	for (var i = 0; i<t.op.partition; i++) $(base.pId).eq(i).css("display","block");
	
	//총개수 보다 보여줄 페이지가 많을 경우 변경
	if(base.maxPage < t.op.partition) t.op.partition = base.maxPage;
    
	//이벤트 시작점 위치
    $(base.mId).bind(START_EV, function(e){
    	base.move_ev = base.stop_m_ev;
		var orig = e.originalEvent;

		base.s_pos = t.op.pageXY == "X" ? e.pageX  || orig.changedTouches[0].pageX : e.pageY  || orig.changedTouches[0].pageY;
	});
    
    //이벤트 이동 위치
    $(base.mId).bind(MOVE_EV, function(e){
		e.preventDefault();
		var orig = e.originalEvent;
		base.e_pos = t.op.pageXY == "X" ? e.pageX || orig.changedTouches[0].pageX : e.pageY || orig.changedTouches[0].pageY;
		var pos_v = parseInt(base.s_pos) - parseInt(base.e_pos);

		if(pos_v  < 0){
			pos_v = pos_v * -1;
		}
		if(pos_v > t.op.speed){
			if(base.s_pos < base.e_pos){
				base.move_ev = base.right_m_ev;
			}else {
				base.move_ev = base.left_m_ev;
			}  
		}else{
			base.move_ev = base.stop_m_ev;
		}
    });
    
    //이벤트 이동 후 처리
    $(base.mId).bind(END_EV, function(e){
		e.preventDefault();
		if(base.move_ev == base.right_m_ev){
			prev_page(e);
		}else if(base.move_ev == base.left_m_ev){
			next_page(e);
		}else{
			/*log(base.move_ev);*/
		}  
	});
    
    //8. pageButton 이벤트
    if(t.op.pageButton.show){
    	if(t.op.pageButton.next != null){
    		$(t.op.pageButton.next).bind("click", function(e){
    			next_page(e);
    		});
    	}
    	if(t.op.pageButton.prev != null){
    		$(t.op.pageButton.prev).bind("click", function(e){
    			prev_page(e);
    		});
    	}
    	if(t.op.movePage.use == true && t.op.movePage.pageLink == true){}
    	else{
    		if(t.op.pageButton.maxPage != null) $(t.op.pageButton.maxPage).text(Math.ceil(base.maxPage / t.op.partition));
	    	if(t.op.pageButton.nowPage != null) $(t.op.pageButton.nowPage).text(base.pagenum);
    	}
    }
    
    //10.animateMove
    if(t.op.animate.show == false){
    	animateMove(base, t.op)
    }	

    //이전 페이지 이동
    function prev_page(e){
    	if(t.op.movePage.use == true && t.op.movePage.pageLink == true) pagePrevLink(e);
    	else pagePrevNoLink(e);
	}
	
    //다음 페이지 이동
	function next_page(e){
		if(t.op.movePage.use == true && t.op.movePage.pageLink == true) pageNextLink(e);
		else pageNextNoLink(e);
	}
	
	function pageNextLink(e){
		
		
		var m = base.pagecount + (t.op.movePartition - 1);
		if(m >= base.maxPage){ 
			base.pagecount = (base.pagecount + t.op.partition) - base.maxPage;
		}else {
			//3. 슬라이딩 시 한번에 이동 할 개수
			base.pagecount  = base.pagecount + t.op.partition;
		}
		//2. 한 하면에 보여질 개수
		$(base.pId).css("display","none");
		$(".tm").remove();	//임시로 만든  태그를 삭제 한다.
		for (var i = 0; i<t.op.partition; i++) $(base.pId).eq((base.pagecount - 1) + i).css("display","block");

		if(base.maxPage < base.pagecount + t.op.partition){
			var cnt = (base.pagecount + t.op.partition - 1) - base.maxPage;
			
			for (var i = 0; i<cnt ; i++) $(base.mId).append("<"+base.cTag+" class='tm'>" + $(base.pId).eq(i).text() + "</"+$(base.cTag).get(0).tagName+">");
		}
	}
	
	function pageNextNoLink(e){
		var m = base.pagecount + (t.op.movePartition - 1);
		if(m >= base.maxPage){ 
			//4. 반복 여부 설정
			if(t.op.movePage.use == true) base.pagecount = 1;
		}else {
			//3. 슬라이딩 시 한번에 이동 할 개수
			base.pagecount  = base.pagecount + t.op.movePartition; 
		} 
		
		//2. 한 하면에 보여질 개수
		$(base.pId).css("display","none");
		for (var i = 0; i<t.op.partition; i++) $(base.pId).eq((base.pagecount - 1) + i).css("display","block");
		
		//8. pageButton 이벤트
		if(t.op.pageButton.show){
	    	if(t.op.pageButton.nowPage != null){
	    		if(base.pagenum == Math.ceil(base.maxPage / t.op.partition)){
	    			if(t.op.movePage.use == true) base.pagenum = 1;
	    		} 
		    	else base.pagenum = base.pagenum + 1;
	    		$(t.op.pageButton.nowPage).text(base.pagenum);
	    	} 
	    }
	}
	
	function pagePrevLink(e){
		base.pagecount = base.maxPage + (base.pagecount - t.op.partition);

		if(base.pagecount > base.maxPage) base.pagecount = base.pagecount - base.maxPage;

		//2. 한 하면에 보여질 개수
		$(base.pId).css("display","none");
		$(".tm").remove();	//임시로 만든  태그를 삭제 한다.
		for (var i = 0; i<t.op.partition; i++) $(base.pId).eq((base.pagecount - 1) + i).css("display","block");
		
		if((t.op.partition + base.pagecount) - base.maxPage >  1){
			var cnt = (base.pagecount + t.op.partition) - base.maxPage - 1;

			for (var i = 0; i<cnt ; i++) $(base.mId).append("<"+base.cTag+" class='tm'>" + $(base.pId).eq(i).text() + "</"+$(base.cTag).get(0).tagName+">");
		}
	}
	
	function pagePrevNoLink(e){
		if(base.pagecount == 1){
			var mathInt = Math.ceil(base.maxPage % t.op.movePartition);
	    	var tPagecount = 1;
	    	if(mathInt == 0){
	    		if(base.partition == 1)tPagecount = base.maxPage;
	    		else tPagecount = base.maxPage - (t.op.partition - 1);
	    	}else{
	    		tPagecount = (base.maxPage + 1) - mathInt;
	    	}
			if(t.op.movePage.use == true) base.pagecount = tPagecount; //4. 반복 여부 설정
		}else {
			base.pagecount  = base.pagecount - t.op.movePartition;//3. 슬라이딩 시 한번에 이동 할 개수
		} 

		//2. 한 하면에 보여질 개수
		$(base.pId).css("display","none");
		for (var i = 0; i<t.op.partition; i++) $(base.pId).eq((base.pagecount - 1) + i).css("display","block");
		
		//8. pageButton 이벤트
		if(t.op.pageButton.show){
	    	if(t.op.pageButton.nowPage != null) {
	    		if(base.pagenum <= 1) {
	    			if(t.op.movePage.use == true) base.pagenum = Math.ceil(base.maxPage / t.op.partition);
	    		}
		    	else base.pagenum = base.pagenum - 1;
	    		
	    		$(t.op.pageButton.nowPage).text(base.pagenum);
	    	}
	    }
	}
	
	function animateMove(e, t){
		$(base.mId).animate({
			opacity: 1
		}, t.animate.time, function(e) {
			next_page(e);
			animateMove(e, t);
		});
	}
	
	function log(l){
		if(t.op.log){
			var d = new Date()
			var date = d.getFullYear() +"."+d.getMonth() +"."+ d.getDate() +" "+ d.getHours() +":"+ d.getMinutes() +":"+ d.getSeconds();
			if(!$("#logMain").attr("id")){
				$("body").append("<div id='logMain' style='font-size:25px; height:100%'>[==LOG==]</div>");
				$("#logMain").append("<div>Debug ["+date+"] : "+l+"</div>")
			}else{
				$("#logMain").append("<div>Debug ["+date+"] : "+l+"</div>");
			}
		}
	}
	
	function Base(id, op){
		/*마스터 id 및 class 세팅*/
		this.mId = id;
		
		/*1. 슬라이딩 될 자식 태그를 찾고 자식 id + tag를 세팅 한다.*/
	    if(op.childrenTagName != "") this.pId = this.mId + ' ' + $(op.childrenTagName).get(0).tagName;
	    else this.pId = this.mId + ' ' +$(this.mId).children().get(0).tagName;	
	    
	    /*자식 태그만 세팅 한다.*/
	    if(op.childrenTagName != "") this.cTag = $(op.childrenTagName).get(0).tagName;
	    else this.cTag = $(this.mId).children().get(0).tagName;	

	    /*슬라이더 될 총 개수*/
	    this.maxPage = $(this.pId).size();
	    
	    /*터치 시작 포지션*/
	    this.s_pos = 0;
	    
	    /*터치 끝 포지션*/
	    this.e_pos = 0;
	    
	    /*event 호출 설정 값 RIGHT, LEFT, STOP 값이 저장 된다.*/
	    this.move_ev = "";
	    
	    /*move_ev비교 대상 될 값*/
	    this.right_m_ev = "RIGHT";
	    this.left_m_ev = "LEFT";
	    this.stop_m_ev = "STOP";
	    
	    /*페이징 카운트*/
	    this.pagecount = 1;
	    
	    /*현재 페이지*/
	    this.pagenum = 1;
	}
};