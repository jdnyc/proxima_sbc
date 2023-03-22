/*! videojs-markers !*/
'use strict'; 

(function($, videojs, undefined) {
	//default setting
	var defaultSetting = {
		markerStyle: {
		'width':'2px',
		'border-radius': '30%',
		'background-color': 'red'
		},
		markerTip: {
		display: true,
		text: function(marker) {
			return marker.text;
		},
		time: function(marker) {
			if (marker === undefined || marker === null) {
				return ;
			} else {
				return marker.time;
			}
		}
		},
		breakOverlay:{
		display: false,
		displayTime: 3,
		text: function(marker) {
			return "Break overlay: " + marker.overlayText;
		},
		style: {
			'width':'100%',
			'height': '20%',
			'background-color': 'rgba(0,0,0,0.7)',
			'color': 'white',
			'font-size': '17px'
		}
		},
		onMarkerClick: function(marker) {},
		onMarkerReached: function(marker) {},
		markers: []
	};

	function getScrollOffset() {
		if (window.pageXOffset) {
		return {
			x: window.pageXOffset,
			y: window.pageYOffset
		};
		}
		return {
		x: document.documentElement.scrollLeft,
		y: document.documentElement.scrollTop
		};
	};

	function getComputedStyle(el, pseudo) {
		return function(prop) {
		if (window.getComputedStyle) {
			return window.getComputedStyle(el, pseudo)[prop];
		} else {
			return el.currentStyle[prop];
		}
		};
	}

	function offsetParent(el) {
		if (el.nodeName !== 'HTML' && getComputedStyle(el)('position') === 'static') {
		return offsetParent(el.offsetParent);
		}
		return el;
	}
	
	// create a non-colliding random number
	function generateUUID() {
		var d = new Date().getTime();
		var uuid = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
		var r = (d + Math.random()*16)%16 | 0;
		d = Math.floor(d/16);
		return (c=='x' ? r : (r&0x3|0x8)).toString(16);
		});
		return uuid;
	};
	
	function registerVideoJsMarkersPlugin(options) {
		/**
		* register the markers plugin (dependent on jquery)
		*/
	
		var setting		= $.extend(true, {}, defaultSetting, options),
			markersMap	= {},
			markersList	= [], // list of markers sorted by time
			videoWrapper = $(this.el()),
			currentMarkerIndex	= -1, 
			player		= this,
			markerTip	= null,
			breakOverlay = null,
			overlayIndex = -1;
			
		function sortMarkersList() {
		// sort the list by time in asc order
		markersList.sort(function(a, b){
			return setting.markerTip.time(a) - setting.markerTip.time(b);
		});
		}
		
		function addMarkers(newMarkers) {
		// create the markers
		$.each(newMarkers, function(index, marker) {
			marker.key = generateUUID();
			var markerWrapIcon = $("<div class='vjs-wrap-icon' style='width:100%;height:2px;outline: 0;position: relative;cursor: pointer;padding: 0;'></div>");
			
			videoWrapper.find('.vjs-progress-control').append(
				createMarkerDiv(marker));
			//videoWrapper.find('.vjs-progress-control').prepend(markerWrapIcon);
			// store marker in an internal hash map
			markersMap[marker.key] = marker;
			markersList.push(marker); 
		});
		
		sortMarkersList();
		}
		
		function getPosition(marker){
		return (setting.markerTip.time(marker) / player.duration()) * 100
		}
		
		function createMarkerDiv(marker, duration) {
		if (marker.mark_type == 'MARK'){
			if (marker.color == null) {
				marker.color = '#FF0000';
			}
			var string = "<div class='vjs-marker markers_"+ marker.mark_id+"' ><span class= 'icon-rotate-225 fa fa-tag' style ='color: "+marker.color+"; margin-top: -11px;margin-left: -5px;position: absolute;'></span></div>";
			var markerDiv = $(string);
		} else if (marker.mark_type == 'MARK_IN'){
			var markerDiv = $("<div class='vjs-marker mark-sec-in markers_"+ marker.mark_id+"'><p style ='color: #0446ff;margin-top: -11px;margin-left: -1px;position: absolute;'>{</p></div>")
		} else if (marker.mark_type == 'MARK_OUT'){
			var markerDiv = $("<div class='vjs-marker mark-sec-out markers_"+ marker.mark_id+"'><p style ='color: #0446ff;margin-top: -11px;position: absolute;'>}</p></div>")
		}	else if (marker.mark_type == 'MARK_SHOT_LIST_IN'){
			var string = "<div class='vjs-marker markers_"+ marker.mark_id+"'><p style ='color: "+marker.color+";margin-top: -11px;margin-left: -1px;position: absolute;'>{</p></div>";
			var markerDiv = $(string);
		} else if (marker.mark_type == 'MARK_SHOT_LIST_OUT'){
			var string = "<div class='vjs-marker markers_"+ marker.mark_id+"'><p style ='color: "+marker.color+";margin-top: -11px;margin-left: 0px;position: absolute;'>}</p></div>";
			var markerDiv = $(string);
		} else{
			if (marker.color == null) {
				marker.color = '#FF0000';
			}
			var markerDiv = $("<div class='vjs-marker markers_"+ marker.mark_id+"'><span class= 'icon-rotate-225 fa fa-tag' style ='color: "+marker.color+"; margin-top: -11px;margin-left: -5px;position: absolute;'></span></div>")
		}

		markerDiv.css(setting.markerStyle)
			.css({"margin-left" : -parseFloat(markerDiv.css("width"))/2 + 'px', 
				"left" : getPosition(marker) + '%',
				"background-color" : marker.color})
			.attr("data-marker-key", marker.key)
			.attr("data-marker-id", marker.mark_id)
			.attr("data-marker-time", setting.markerTip.time(marker));
			
		// add user-defined class to marker
		if (marker.class) {
			markerDiv.addClass(marker.class);
		}
		
		// bind click event to seek to marker time
		markerDiv.on('click', function(e) {
			
			var preventDefault = false;
			if (typeof setting.onMarkerClick === "function") {
				// if return false, prevent default behavior
				preventDefault = setting.onMarkerClick(marker) == false;
			}
			
			if (!preventDefault) {
				var key = $(this).data('marker-key');
				player.currentTime(setting.markerTip.time(markersMap[key]));
			}
		});
		
		if (setting.markerTip.display) {
			registerMarkerTipHandler(markerDiv);
		}
		
		return markerDiv;
		}		
		function updateMarkers() {
		// update UI for markers whose time changed

		for (var i = 0; i< markersList.length; i++) {
			var marker = markersList[i];
			var markerDiv = videoWrapper.find(".vjs-marker[data-marker-key='" + marker.key +"']"); 
			var markerTime = setting.markerTip.time(marker);
			
			if (markerDiv.data('marker-time') != markerTime) {
				markerDiv.css({"left": getPosition(marker) + '%'})
					.attr("data-marker-time", markerTime);
			}
		}
		sortMarkersList();
		}

		function removeMarkers(indexArray) {
		// reset overlay
		if (breakOverlay){
			overlayIndex = -1;
			breakOverlay.css("visibility", "hidden");
		}
		currentMarkerIndex = -1;

		for (var i = 0; i < indexArray.length; i++) {
			var index = indexArray[i];
			var marker = markersList[index];
			if (marker) {
				// delete from memory
				delete markersMap[marker.key];
				markersList[index] = null;
				
				// delete from dom
				videoWrapper.find(".vjs-marker[data-marker-key='" + marker.key +"']").remove();
			}
		}
		
		// clean up array
		for (var i = markersList.length - 1; i >=0; i--) {
			if (markersList[i] === null) {
				markersList.splice(i, 1);
			}
		}
		
		// sort again
		sortMarkersList();
		}
		
		
		// attach hover event handler
		function registerMarkerTipHandler(markerDiv) {
		
		markerDiv.on('mouseover', function(){
			var marker = markersMap[$(this).data('marker-key')];
			
			//markerTip.find('.vjs-tip-inner').text(setting.markerTip.text(marker));
			//markerTip.find('.vjs-tip-inner').innerHTML = '<p>'+marker.text+'</p><br/><p>'+marker.comments+'</p>';
			if (marker.text != null){
				markerTip.find('.vjs-tip-inner-title').text(marker.text);
			} else {
				markerTip.find('.vjs-tip-inner-title').text('');
			}
			if (marker.comments != null){
				markerTip.find('.vjs-tip-inner-comments').text(marker.comments);
			} else {
				markerTip.find('.vjs-tip-inner-comments').text('');
			}
			markerTip.find('.vjs-tip-inner-time').text(marker.time_code);

			// margin-left needs to minus the padding length to align correctly with the marker
			markerTip.css({"left" : getPosition(marker) + '%',
							"margin-left" : -parseFloat(markerTip.css("width"))/2 - 5 + 'px',
							"visibility"	: "visible"});
			var progressControl = player.controlBar.progressControl;		
			var pageXOffset = getScrollOffset().x;
			var clientRect = offsetParent(progressControl.el()).getBoundingClientRect();
			var width = parseFloat( markerTip.css("width"));
			var halfWidth = width / 2;
			var left = parseFloat( markerTip.css("left") );
			var right = parseFloat( (clientRect.width || clientRect.right) + pageXOffset );

			// img.style.display = 'none';
			// make sure that the thumbnail doesn't fall off the right side of the left side of the player
			var left_halfwidth = (left + halfWidth);
			var left_arrow = 0;

			if ( (left + halfWidth) > right ) {
				left_arrow = (left + halfWidth) - right;
				left -= (left + halfWidth) - right;
			} else if (left < halfWidth) {
				left_arrow = left - parseFloat(halfWidth);
				left = halfWidth;
			} else {
				left_arrow = 0;
			}

			if (left_arrow > ( parseInt(halfWidth) - 7 )) {
				left_arrow = halfWidth - 15;
			} else if (Math.abs( left_arrow ) > Math.abs( parseInt(halfWidth) - 5 )){
				left_arrow = 7 - halfWidth;
			}
			var img_style_left =	left - width/2;
			markerTip.css({"left" : left});
			markerTip.children('.vjs-tip-arrow').css({"margin-left" : left_arrow+ 'px'});
			$('.vjs-mouse-display').addClass('vjs-mouse-display-none');
		}).on('mouseout',function(){
			markerTip.css("visibility", "hidden");
			$('.vjs-mouse-display').removeClass('vjs-mouse-display-none');
		});
		}
		
		function initializeMarkerTip() {
		markerTip = $("<div class='vjs-tip proxima_customize_tip'><div class='vjs-tip-arrow'></div><div class='vjs-tip-inner'><p class='vjs-tip-inner-time'></p><br/><p class='vjs-tip-inner-title'></p><br/><p class='vjs-tip-inner-comments multiline_row_line'></p></div></div>");
		videoWrapper.find('.vjs-progress-control').append(markerTip);
		}
		
		// show or hide break overlays
		function updateBreakOverlay(currentTime) {
		if(currentMarkerIndex < 0){
			return;
		}
		
		var marker = markersList[currentMarkerIndex];
		var markerTime = setting.markerTip.time(marker);
		
		if (currentTime >= markerTime && 
			currentTime <= (markerTime + setting.breakOverlay.displayTime)) {

			if (overlayIndex != currentMarkerIndex){
				overlayIndex = currentMarkerIndex;
				breakOverlay.find('.vjs-break-overlay-text').text(setting.breakOverlay.text(marker));
			}
			
			breakOverlay.css('visibility', "visible");
			
		} else {
			overlayIndex = -1;
			breakOverlay.css("visibility", "hidden");
		}
		}
		
		// problem when the next marker is within the overlay display time from the previous marker
		function initializeOverlay() {
		breakOverlay = $("<div class='vjs-break-overlay'><div class='vjs-break-overlay-text'></div></div>")
			.css(setting.breakOverlay.style);
		videoWrapper.append(breakOverlay);
		overlayIndex = -1;
		}
		
		function onTimeUpdate() {
		/*
			check marker reached in between markers
			the logic here is that it triggers a new marker reached event only if the player 
			enters a new marker range (e.g. from marker 1 to marker 2). Thus, if player is on marker 1 and user clicked on marker 1 again, no new reached event is triggered)
		*/
		
		var getNextMarkerTime = function(index) {
			if (index < markersList.length - 1) {
				return setting.markerTip.time(markersList[index + 1]);
			} 
			// next marker time of last marker would be end of video time
			return player.duration();
		}
		var currentTime = player.currentTime();
		var newMarkerIndex;
		
		if (currentMarkerIndex != -1) {
			// check if staying at same marker
			var nextMarkerTime = getNextMarkerTime(currentMarkerIndex);
			if(currentTime >= setting.markerTip.time(markersList[currentMarkerIndex]) &&
				currentTime < nextMarkerTime) {
				return;
			}
		}
		
		// check first marker, no marker is selected
		if (markersList.length > 0 &&
			currentTime < setting.markerTip.time(markersList[0])) {
			newMarkerIndex = -1;
		} else {
			// look for new index
			for (var i = 0; i < markersList.length; i++) {
				nextMarkerTime = getNextMarkerTime(i);
				
				if(currentTime >= setting.markerTip.time(markersList[i]) &&
					currentTime < nextMarkerTime) {
					
					newMarkerIndex = i;
					break;
				}
			}
		}
		
		// set new marker index
		if (newMarkerIndex != currentMarkerIndex) {
			// trigger event
			if (newMarkerIndex != -1 && options.onMarkerReached) {
				options.onMarkerReached(markersList[newMarkerIndex]);
			}
			currentMarkerIndex = newMarkerIndex;
		}
		
		// update overlay
		if(setting.breakOverlay.display) {
			updateBreakOverlay(currentTime);
		}
		}
		
		// setup the whole thing
		function initialize() {
		if (setting.markerTip.display) {
			initializeMarkerTip();
		}
		
		// remove existing markers if already initialized
		player.markers.removeAll();
		addMarkers(options.markers);
					
		if (setting.breakOverlay.display) {
			initializeOverlay();
		}
		onTimeUpdate();
		player.on("timeupdate", onTimeUpdate);
		}
		
		// setup the plugin after we loaded video's meta data
		player.on("loadedmetadata", function() {
		initialize();
		});
		
		// exposed plugin API
		player.markers = {
		getMarkers: function() {
			return markersList;
		},
		next : function() {
			// go to the next marker from current timestamp
			var currentTime = player.currentTime();
			for (var i = 0; i < markersList.length; i++) {
				var markerTime = setting.markerTip.time(markersList[i]);
				if (markerTime > currentTime) {
					player.currentTime(markerTime);
					break;
				}
			}
		},
		prev : function() {
			// go to previous marker
			var currentTime = player.currentTime();
			for (var i = markersList.length - 1; i >=0 ; i--) {
				var markerTime = setting.markerTip.time(markersList[i]);
				// add a threshold
				if (markerTime + 0.5 < currentTime) {
					player.currentTime(markerTime);
					break;
				}
			}
		},
		add : function(newMarkers) {
			// add new markers given an array of index
			addMarkers(newMarkers);
		},
		remove: function(indexArray) {
			// remove markers given an array of index
			removeMarkers(indexArray);
		},
		removeAll: function(){
			var indexArray = [];
			for (var i = 0; i < markersList.length; i++) {
				indexArray.push(i);
			}
			removeMarkers(indexArray);
		},
		updateTime: function(){
			// notify the plugin to update the UI for changes in marker times 
			updateMarkers();
		},
		reset: function(newMarkers){
			// remove all the existing markers and add new ones
			player.markers.removeAll();
			addMarkers(newMarkers);
		},
		destroy: function(){
			// unregister the plugins and clean up even handlers
			player.markers.removeAll();
			breakOverlay.remove();
			markerTip.remove();
			player.off("timeupdate", updateBreakOverlay);
			delete player.markers;
		},
		};
	}

	videojs.plugin('markers', registerVideoJsMarkersPlugin);

})(jQuery, window.videojs);
