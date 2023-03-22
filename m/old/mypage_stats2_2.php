<?
$jsonAddress = ($site_test) ? "mypage_stats2_2_json.php" : "/store/loan_request/get_list.php";
?>

<div class="statesList">
	<nav>
		<ul class="ui-toggleTab col3">
			<li><a href="mypage.php?act=state2&tab=0#con">대출</a></li>
			<li><a href="mypage.php?act=state2&tab=1#con" class="active">다운로드</a></li>
			<li><a href="mypage.php?act=state2&tab=2#con">이력</a></li>
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
				<dt><label for="download_list_data_info">자료구분</label></dt>
				<dd><select name="download_list_data_info" id="download_list_data_info"></select></dd>
			</dl>

			<dl>
				<dt><label for="download_list_user_name">성명</label></dt>
				<dd>
					<span class="ui-iptText ipt-dis">
						<input type="text" name="download_list_user_name" id="download_list_user_name" size="13" maxlength="20" value="<?=$_SESSION['user']['KOR_NM']?>" readonly="readonly"/>
					</span>
					<span class="ui-iptText ipt-dis">
						<input type="text" name="download_list_user_id" id="download_list_user_id" size="10" value="<?=$_SESSION['user']['user_id']?>" readonly="readonly"/>
					</span>
				</dd>
			</dl>

			<dl>
				<dt><label for="download_list_archive_id">아카이브ID</label></dt>
				<dd>
					<span class="ui-iptText ipt-block">
						<input type="text" name="download_list_archive_id" id="download_list_archive_id" maxlength="20"/>
					</span>
				</dd>
			</dl>

			<dl>
				<dt><label for="download_list_start_date">신청일자</label></dt>
				<dd>
					<div class="tb">
						<div class="tbc">
							<span class="ui-iptText ipt-block">
								<input type="date" name="download_list_start_date" id="download_list_start_date" placeholder="0000-00-00" maxlength="10"/>
							</span>
						</div>
						<div class="tbc em">~</div>
						<div class="tbc">
							<span class="ui-iptText ipt-block">
								<input type="date" name="download_list_end_date" id="download_list_end_date" placeholder="0000-00-00" maxlength="10"/>
							</span>
						</div>
					</div>
				</dd>
			</dl>

			<dl>
				<dt><label for="download_list_rtn_start_date">승인일자</label></dt>
				<dd>
					<div class="tb">
						<div class="tbc">
							<span class="ui-iptText ipt-block">
								<input type="date" name="download_list_rtn_start_date" id="download_list_rtn_start_date" placeholder="0000-00-00" maxlength="10"/>
							</span>
						</div>
						<div class="tbc em">~</div>
						<div class="tbc">
							<span class="ui-iptText ipt-block">
								<input type="date" name="download_list_rtn_end_date" id="download_list_rtn_end_date" placeholder="0000-00-00" maxlength="10"/>
							</span>
						</div>
					</div>
				</dd>
			</dl>

			<dl>
				<dt><label for="download_list_down_start_date">다운로드일자</label></dt>
				<dd>
					<div class="tb">
						<div class="tbc">
							<span class="ui-iptText ipt-block">
								<input type="date" name="download_list_down_start_date" id="download_list_down_start_date" placeholder="0000-00-00" maxlength="10"/>
							</span>
						</div>
						<div class="tbc em">~</div>
						<div class="tbc">
							<span class="ui-iptText ipt-block">
								<input type="date" name="download_list_down_end_date" id="download_list_down_end_date" placeholder="0000-00-00" maxlength="10"/>
							</span>
						</div>
					</div>
				</dd>
			</dl>

			<dl>
				<dt><label for="download_list_busu">부서</label></dt>
				<dd>
					<span class="ui-iptText ipt-block ipt-dis">
						<input type="text" name="download_list_busu" id="download_list_busu" maxlength="8" readonly="readonly" value="<?=$mem_info_arr[dept_nm]?>"/>
					</span>
				</dd>
			</dl>

			<dl>
				<dt><label for="download_list_sort1">정렬</label></dt>
				<dd>
					<div class="tb pad">
						<div class="tbc"><select name="download_list_sort1" id="download_list_sort1"></select></div>
						<div class="tbc"><select name="download_list_sort2" id="download_list_sort2"></select></div>
						<div class="tbc"><select name="download_list_sort3" id="download_list_sort3"></select></div>
					</div>
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
	var
		postData = {
			search : '{}'
			,sort : ''
			,dir : 'ASC'
			,start : '0'
			,action : 'get_download_list'
			,limit : '20'
			,start_date : ''
			,end_date : ''
			,user_mode : 'user_mode'
		}
	;

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
					scr += '<dl>';
					scr += '<dt>자료구분</dt>';
					scr += '<dd>' + datagu_combo_data2[o.datagu].name + '</dd>';
					scr += '</dl>';
					scr += '<dl>';
					scr += '<dt>아카이브ID</dt>';
					scr += '<dd>' + o.archive_id + '</dd>';
					scr += '</dl>';
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
					scr += '<dt>다운로드사유</dt>';
					scr += '<dd>' + o.loanrsn + '</dd>';
					scr += '</dl>';
					scr += '<dl>';
					scr += '<dt>부서</dt>';
					scr += '<dd>' + o.dept + '</dd>';
					scr += '</dl>';
					scr += '<dl>';
					scr += '<dt>성명</dt>';
					scr += '<dd>' + o.korname + '</dd>';
					scr += '</dl>';
					scr += '<dl>';
					scr += '<dt>신청일자</dt>';
					scr += '<dd>' + stringToDate(o.loanymd) + '</dd>';
					scr += '</dl>';
					scr += '<dl>';
					scr += '<dt>승인일자</dt>';
					scr += '<dd>' + stringToDate(o.retscheymd) + '</dd>';
					scr += '</dl>';
					scr += '<dl>';
					scr += '<dt>다운로드일자</dt>';
					scr += '<dd>' + stringToDate(o.dwnymd) + '</dd>';
					scr += '</dl>';
					scr += '<dl>';
					scr += '<dt>저작권</dt>';
					scr += (o.is_copyright == 1) ? '<dd>있음</dd>' : '<dd>없음</dd>';
					scr += '</dl>';
					scr += '</div>';
					scr += '</li>';
				}
	
					mlist.children('li').removeClass('active');
					mlist.find('li>strong').unbind();
	
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

	// 검색 폼
	$.fn.mdetailSearch = function()
	{
		var
			$this = $(this)
			,date = new Date()
			,y = date.getFullYear()
			,m = getFormattedPartTime(date.getMonth() + 1)
			,mm = getFormattedPartTime(date.getMonth())
			,d = getFormattedPartTime(date.getDate())
		;

		function sb1(obj)
		{
			var scr = '';
			for (var i in obj)
			{
				scr += '<option value="' + obj[i].name + '" n="' + i + '">' + obj[i].name + '</option>';
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

		$('#download_list_data_info').html(sb1(download_combo_data));
		$('#download_list_sort1').html(sb2(sort_combo_data_loan_list));
		$('#download_list_sort2').html(sb2(sort_combo_data_loan_list));
		$('#download_list_sort3').html(sb2(sort_combo_data_loan_list));

		$this.submit(function(){
			var
				error = false
				,result = {
					download_list_data_info : $this.find('select[name=download_list_data_info]').val()
					,download_list_user_name : $this.find('input[name=download_list_user_name]').val()
					,download_list_user_id : $this.find('input[name=download_list_user_id]').val()
					,download_list_archive_id : $this.find('input[name=download_list_archive_id]').val()
					,download_list_start_date : $this.find('input[name=download_list_start_date]').val()
					,download_list_end_date : $this.find('input[name=download_list_end_date]').val()
					,download_list_rtn_start_date : $this.find('input[name=download_list_rtn_start_date]').val()
					,download_list_rtn_end_date : $this.find('input[name=download_list_rtn_end_date]').val()
					,download_list_down_start_date : $this.find('input[name=download_list_down_start_date]').val()
					,download_list_down_end_date : $this.find('input[name=download_list_down_end_date]').val()
					,download_list_busu : $this.find('input[name=download_list_busu]').val()
					,download_list_sort1 : $this.find('select[name=download_list_sort1]').val()
					,download_list_sort2 : $this.find('select[name=download_list_sort2]').val()
					,download_list_sort3 : $this.find('select[name=download_list_sort3]').val()
				}
			;

			mlist.html('');
			loadingSw.show();
			nomore = false;

			postData.start = 0;
			postData.search = JSON.stringify(result);
			postData.start_date = stringToDate(y + mm + d);
			postData.end_date = stringToDate(y + m + d);

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

		$this.find('button[type=reset]').on('click', function(){
			changeCusForm(0);
		});
	}


	/* Act */
	// 검색폼
	$('#mdetailSearch').mdetailSearch();

	// 목록출력
	/*
	loadingSw.show();
	prtMlist({
		method : false
		,complete : function()
		{
			loadingSw.hide();
		}
	});
	*/
	$('#mdetailSearch').toggleClass('active');
	nomore = true;

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

<?
// UTF-8 한글 체크
?>
