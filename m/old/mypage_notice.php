<?
$mypage_notice_json = ($site_test) ? "mypage_notice_json.php" : "/php/mypage/noticestore.php";
?>

<ul class="ui-docList" id="noticeList"></ul>

<div class="ui-more" id="loading">loading...</div>

<script type="text/javascript">
var loadingSw = loadingSw();

jQuery(function($){
	var postData = {
		start : 0
		,limit : 20
	};

	function prtMlist(opts)
	{
		getJsonData({
			url : '<?=$mypage_notice_json?>'
			,type : 'post'
			,dataType : 'json'
			,parameter : objectToPost(postData)
			,complete : function(json)
			{
				var
					scr = ''
					,list = $('#noticeList')
				;	
	
				if (Boolean(json.success) == true && json.data.length > 0)
				{
					json = json.data;
	
					for (var i in json)
					{
						scr += '<li>';
						scr += '<a href="#">' + json[i].title + '</a>';
						scr += '<pre class="bd">' + json[i].content + '</pre>';
						scr += '</li>';
					}

					list.children().children('a').unbind();

					if (opts.method)
					{
						list.append(scr);
					}
					else
					{
						list.html(scr);
					}

					list.children().children('a').on('click', function(){
						var li = $(this).parent()
						if (!li.hasClass('active'))
						{
							li.parent().children().removeClass('active')
							li.addClass('active');
						}
						else
						{
							li.removeClass('active');
						}
						return false;
					});
				}
				else
				{
					nomore = true;
				}

				if ((!opts.method && nomore == true))
				{
					list.html('<li class="empty">데이터가 없습니다.</li>');
				}

				if (postData.limit > json.length)
				{
					nomore = true;
				}

				if (opts.complete)
				{
					opts.complete();
				}
			}
		});
	}

	// 스크롤로 더 로드하기
	iscroll(function(){
		postData.start = parseInt(postData.start) + parseInt(postData.limit);

		prtMlist({
			method : true
			,complete : function()
			{
				loadingSw.hide();
			}
		});
	});

	/* Act */
	loadingSw.show();
	prtMlist({
		method : false
		,complete : function()
		{
			loadingSw.hide();
		}
	});
});
</script>

<?
// UTF-8 한글 체크
?>
