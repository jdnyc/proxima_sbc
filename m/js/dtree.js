/*!
 * jquery tree menu
 * dTree.js v1.0.0 
 * mefe@mefe.net
 * 2014-09-03
 * 
 */
(function ( $ ) {

	$.fn.dTree = function(options) {
		var defaults = {
			closeSameLevel: true,
			useCookie: true,
		};
		
		var settings = $.extend( {}, defaults, options );
		 //.css({"color":"red","border":"2px solid red"});
		
		return this.each(function() {
			var $div = $(this);
			var $ul = $(this).find('ul');
			var $li = $(this).find('li');
			var $folder = $li.has("ul");
			
			$folder.prepend("<span class=\"plus\"></span><span class=\"folder\"></span>");
			$li.not($folder).prepend("<span class=\"join\"></span><span class=\"folder\"></span>");
//		  $ul.parent("li").addClass("folder-group");
			$li.addClass("folder-group");
			
			$ul.children('li:last-child').not($folder).addClass("join-last");
			$.fn.dTree.tree_first_element($li.first());
		
			$ul.children('li.folder-group:last-child').addClass("last");
			$(this).children('ul:first-child').children('li:first-child').addClass("first_li");
			$(this).children('ul:first-child').children('li:first-child').removeClass("last");
			var obect_index = [];
			if(settings.useCookie && $.fn.dTree.check_cookie("com.gemiso."+$div.attr('id')))
			{
				var object_index = JSON.parse($.fn.dTree.get_cookie("com.gemiso."+$div.attr('id')));
				$.each( object_index, function( key, value ) {
					obect_index.push(value);
					$this = $ul.find("li.folder-group").eq(value);
					$.fn.dTree.set_icons($this.children('span:first')); 
					$this.children('ul:first').toggle();
				});
			} else if($li.hasClass("active"))
			{
				$active = $ul.find("li.folder-group.active");
				$active.each(function(){
					$.fn.dTree.set_icons($(this).children('span:first')); 
					$(this).children('ul:first').toggle();
				});
				$active.parentsUntil("div", ".folder-group").each(function(){
					$.fn.dTree.set_icons($(this).children('span:first')); 
					$(this).children('ul:first').toggle();
				});	
			}   
			$(this).unbind("click");
			$(this).on('click', '.plus, .minus', function(){
				if(settings.useCookie)
				{
					if($(this).attr('class')=='plus'){
						$(this).parentsUntil("div", ".folder-group").each(function(){
							var j = obect_index.indexOf($(this).index(".folder-group"));
							if (j == -1) {
								obect_index.push($(this).index(".folder-group"));
							}
						});
					} else if ($(this).attr('class')=='minus'){
						var i = obect_index.indexOf($(this).parent().index(".folder-group"));
						if (i != -1) {
							obect_index.splice(i,1);
						}
					}
					$.fn.dTree.set_cookie("com.gemiso."+$div.attr('id'), JSON.stringify(obect_index));
				}
				if(settings.closeSameLevel){
					$.fn.dTree.close_same_level($(this));
				}
				$.fn.dTree.set_icons($(this));
				$(this).parent().children('ul:first').toggle(250);
			});
			$ul.children('li:last-child').not($folder).children().removeClass("minus");
			$ul.children('li:last-child').not($folder).children(".folder-open").addClass("folder");
			$ul.children('li:last-child').not($folder).children().removeClass("folder-open");
		});
	};
	
	
	$.fn.dTree.set_cookie = function(name, value)
	{
		 document.cookie = name + "=" + value;
	};
	
	$.fn.dTree.get_cookie = function(name)
	{
		var value = "; " + document.cookie;
		var parts = value.split("; " + name + "=");
		if (parts.length === 2) return parts.pop().split(";").shift();
	};
	
	$.fn.dTree.check_cookie = function(name)
	{
		var _cookie = document.cookie;
		var pattern = new RegExp(""+name+"=([^;=]+)[;\\b]?");
		if(pattern.test(_cookie))
		{
			return true;
		}
	}
	
	$.fn.dTree.set_icons = function($selected) 
	{
		if(!$selected.parent().children('ul:first').is(':visible'))
		{
			$selected.removeClass("plus").addClass("minus");
			$selected.siblings("span").removeClass("folder").addClass("folder-open");
		}
		else
		{
			$selected.removeClass("minus").addClass("plus");
			$selected.siblings("span").removeClass("folder-open").addClass("folder");
		}
	};
	
	$.fn.dTree.close_same_level = function($selected)
	{
		var $same_level = $selected.parent().siblings(".folder-group").children('ul:first'); 

		if($same_level.is(':visible')) 
		{
			$same_level.toggle(250);
			$.fn.dTree.set_icons($selected.parent().siblings(".folder-group").children('span:first'));	
		}
	};
	
	$.fn.dTree.tree_first_element = function($selected)
	{
		 $selected.children("span.join").remove();
		 $selected.children("span").removeClass("page");
	};
		  
}( jQuery ));