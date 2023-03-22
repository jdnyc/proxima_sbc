<?
$jsonAddress = ($site_test) ? "mypage_stats1_1_json.php" : "/store/loan_request/get_list.php";
?>

<div class="statesList">
	<nav>
		<ul class="ui-toggleTab">
			<li><a href="mypage.php?act=state1&tab=0#con" class="active">대출신청현황</a></li>
			<li><a href="mypage.php?act=state1&tab=1#con">다운로드신청현황</a></li>
		</ul>
	</nav>

	<form class="search" id="mdetailSearch">
		<h1>
			<span>검색열기</span>
			<span>검색닫기</span>
		</h1>

		<fieldset>
			<legend class="blind">검색</legend>
			<dl>
				<dt>자료구분</dt>
				<dd><select name="rent_req_data_info" id="rent_req_data_info"></select></dd>
			</dl>

			<dl>
				<dt><label for="rent_req_user_name">성명</label></dt>
				<dd>
					<span class="ui-iptText ipt-dis">
						<input type="text" name="rent_req_user_name" id="rent_req_user_name" size="13" maxlength="20" value="<?=$_SESSION['user']['KOR_NM']?>" disabled="disabled"/>
					</span>
					<span class="ui-iptText ipt-dis">
						<input type="text" name="rent_req_id" id="rent_req_id" size="10" value="<?=$_SESSION['user']['user_id']?>" disabled="disabled"/>
					</span>
				</dd>
			</dl>

			<dl>
				<dt>정렬</dt>
				<dd>
					<p><select name="rent_req_sort1" id="rent_req_sort1"></select></p>
					<p><select name="rent_req_sort2" id="rent_req_sort2"></select></p>
					<p><select name="rent_req_sort3" id="rent_req_sort3"></select></p>
				</dd>
			</dl>
			<div class="btngroup">
				<button type="submit" class="ui-button btn-blue">조회</button>
				<button type="reset" class="ui-button">초기화</button>
			</div>
		</fieldset>
	</form>

	<section class="lst">
		<ul id="mlist"></ul>
	</section>
</div>

<div class="ui-more" id="loading">loading...</div>

<script type="text/javascript">
var
	loadingSw = loadingSw()
	,mlist = $('#mlist')
;

jQuery(function($){
	var postData = {
		sort : 'datagu'
		,dir : 'ASC'
		,start : '0'
		,action : 'rent_req'
		,limit : '20'
		,mode : '0'
		,user_mode : 'user_mode'
		,user_id : '<?=$_SESSION['user']['user_id']?>'
	};

	function prtMlist(opts)
	{
		getJsonData({
			url : '<?=$jsonAddress?>'
			,type : 'post'
			,dataType : 'json'
			,parameter : objectToPost(postData)
			,complete : function(json)
			{
				if (Boolean(json.result) == true && json.data.length > 0)
				{
					var scr = '';
					for (var i in json.data)
					{
						var o = json.data[i];
						scr += '<li>';
						scr += '<strong>[' + datagu_mapping_num_array[o.datagu-1] + '] ' + o.datanm1 + '</strong>';
						scr += '<div class="de">';
						//scr += '<dl>';
						//scr += '<dt>매체</dt>';
						//scr += '<dd>' + o.medcd + '</dd>';
						//scr += '</dl>';
						scr += '<dl>';
						scr += '<dt>등록번호</dt>';
						scr += '<dd>' + o.creatno + '</dd>';
						scr += '</dl>';
						scr += '<dl>';
						scr += '<dt>자료명2</dt>';
						scr += '<dd>' + o.datanm2 + '</dd>';
						scr += '</dl>';
						scr += '<dl>';
						scr += '<dt>자료명3</dt>';
						scr += '<dd>' + o.datanm3 + '</dd>';
						scr += '</dl>';
						scr += '<dl>';
						scr += '<dt>대출사유</dt>';
						scr += '<dd>' + o.loanrsn + '</dd>';
						scr += '</dl>';
						scr += '<dl>';
						scr += '<dt>부서</dt>';
						scr += '<dd>' + o.dept + '</dd>';
						scr += '</dl>';
						scr += '<dl>';
						scr += '<dt>대출일자</dt>';
						scr += '<dd>' + stringToDate(o.reqymd) + '</dd>';
						scr += '</dl>';
						scr += '<dl>';
						scr += '<dt>반납예정일</dt>';
						scr += '<dd>' + stringToDate(o.retscheymd) + '</dd>';
						scr += '</dl>';
						scr += '<dl>';
						scr += '<dt>저작권</dt>';
						scr += (o.is_copyright == 1) ? '<dd>있음</dd>' : '<dd>없음</dd>';
						scr += '</dl>';
						scr += '</div>';
						scr += '</li>';
					}

					$('#mlist>li').removeClass('active');
					$('#mlist>li>strong').unbind();
	
					if (opts.method)
					{
						mlist.append(scr);
					}
					else
					{
						mlist.html(scr);
					}
	
					mlist.find('li>strong').on('click', function(){
						var li = $(this).parent();
						if (!li.hasClass('active'))
						{
							li.parent().children().removeClass('active')
							li.addClass('active');
						}
						else
						{
							li.removeClass('active');
						}
					});
				}
				else
				{
					nomore = true;
				}

				if ((!opts.method && nomore == true))
				{
					mlist.html('<li class="empty">데이터가 없습니다.</li>');
				}

				if (postData.limit > json.data.length)
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

	// 셀렉트박스 제작
	$.fn.generatoeSelectbox = function(opts)
	{
		var scr = '';

		for (var i in opts.data)
		{
			scr += '<option value="' + opts.data[i][0] + '" n="' + opts.data[i][1] + '">' + opts.data[i][0] + '</option>';
		}
		$(this).html(scr);
	}

	$.fn.mdetailSearch = function()
	{
		var $this = $(this);

		function sb1(obj)
		{
			var scr = '';
			for (var i in obj)
			{
				scr += '<option value="' + obj[i].name + '" n="' + obj[i].n + '">' + obj[i].name + '</option>';
			}
			return scr;
		}

		function sb2(obj)
		{
			var scr = '';
			for (var i in obj)
			{
				scr += '<option value="' + obj[i][0] + '">' + obj[i][0] + '</option>';
			}
			return scr;
		}

		$('#rent_req_data_info').html(sb1(datagu_combo_data2));
		$('#rent_req_sort1').html(sb2(sort_combo_data_loan_list));
		$('#rent_req_sort2').html(sb2(sort_combo_data_loan_list));
		$('#rent_req_sort3').html(sb2(sort_combo_data_loan_list));

		$this.submit(function(){
			var
				result = {
					rent_req_data_info : $('#rent_req_data_info').val()
					,rent_req_user_name : $this.find('input[name=rent_req_user_name]').val()
					,rent_req_id : $this.find('input[name=rent_req_id]').val()
					,rent_req_sort1 : $('#rent_req_sort1').val()
					,rent_req_sort2 : $('#rent_req_sort2').val()
					,rent_req_sort3 : $('#rent_req_sort3').val()
				}
			;

			mlist.html('');
			loadingSw.show();
			nomore = false;

			delete postData.user_mode;
			delete postData.user_id;
			postData.start = 0;
			postData.one_sort = 'false';
			postData.sort = '';
			postData.mode = 'user_mode';
			postData.search = JSON.stringify(result);

			prtMlist({
				method : false
				,complete : function()
				{
					loadingSw.hide();
				}
			});
			return false;
		});

		$this.children('h1').on('click', function(){
			$this.toggleClass('active');
		});
	}


	/* Act */
	// 검색폼
	$('#mdetailSearch').mdetailSearch();

	// 목록출력
	loadingSw.show();
	prtMlist({
		method:false
		,complete : function()
		{
			loadingSw.hide();
		}
	});

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
});
</script>
