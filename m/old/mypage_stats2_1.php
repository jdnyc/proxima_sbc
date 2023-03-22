<?
$jsonAddress = ($site_test) ? "mypage_stats2_1_json.php" : "/store/loan_request/get_list.php";
?>

<div class="statesList">
	<nav>
		<ul class="ui-toggleTab col3">
			<li><a href="mypage.php?act=state2&tab=0#con" class="active">대출</a></li>
			<li><a href="mypage.php?act=state2&tab=1#con">다운로드</a></li>
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
				<dt><label for="loan_list_data_info">자료구분</label></dt>
				<dd><select name="loan_list_data_info" id="loan_list_data_info"></select></dd>
			</dl>

			<dl>
				<dt><label for="loan_list_start_date">대출일자</label></dt>
				<dd>
					<div class="tb">
						<div class="tbc">
							<span class="ui-iptText ipt-block">
								<input type="date" name="loan_list_start_date" id="loan_list_start_date" placeholder="0000-00-00" maxlength="10"/>
							</span>
						</div>
						<div class="tbc em">~</div>
						<div class="tbc">
							<span class="ui-iptText ipt-block">
								<input type="date" name="loan_list_end_date" id="loan_list_end_date" placeholder="0000-00-00" maxlength="10"/>
							</span>
						</div>
					</div>
				</dd>
			</dl>

			<dl>
				<dt><label for="loan_list_user_name">성명</label></dt>
				<dd>
					<span class="ui-iptText ipt-dis">
						<input type="text" name="loan_list_user_name" id="loan_list_user_name" size="13" maxlength="20" value="<?=$_SESSION['user']['KOR_NM']?>" readonly="readonly"/>
					</span>
					<span class="ui-iptText ipt-dis">
						<input type="text" name="loan_list_user_id" id="loan_list_user_id" size="10" value="<?=$_SESSION['user']['user_id']?>" readonly="readonly"/>
					</span>
				</dd>
			</dl>

			<dl>
				<dt><label for="loan_list_create_no1">등록번호</label></dt>
				<dd>
					<div class="tb">
						<div class="tbc">
							<span class="ui-iptText ipt-block">
								<input type="text" name="loan_list_create_no1" id="loan_list_create_no1" maxlength="8" class="uppercase"/>
							</span>
						</div>
						<div class="tbc em">~</div>
						<div class="tbc">
							<span class="ui-iptText ipt-block">
								<input type="text" name="loan_list_create_no2" maxlength="8" class="uppercase"/>
							</span>
						</div>
					</div>
				</dd>
			</dl>

			<div id="cusForm">
				<dl>
					<dt><label for="loan_list_datanm1">자료명1</label></dt>
					<dd>
						<span class="ui-iptText ipt-block">
							<input type="text" name="loan_list_datanm1" id="loan_list_datanm1"/>
						</span>
					</dd>
				</dl>
				<dl>
					<dt><label for="download_req_datanm2">자료명2</label></dt>
					<dd>
						<span class="ui-iptText ipt-block">
							<input type="text" name="loan_list_datanm2" id="loan_list_datanm2"/>
						</span>
					</dd>
				</dl>
				<dl>
					<dt><label>자료명3</label></dt>
					<dd>
						<!-- type1 -->
						<div>
							<span class="ui-iptText ipt-block">
								<input type="text" name="loan_list_datanm3"/>
							</span>
						</div>
						<!-- // type1 -->
						<!-- type2 -->
						<div class="tb">
							<div class="tbc">
								<span class="ui-iptText ipt-block">
									<input type="date" name="loan_list_datanm3_1" maxlength="8"/>
								</span>
							</div>
							<div class="tbc em">~</div>
							<div class="tbc">
								<span class="ui-iptText ipt-block">
									<input type="date" name="loan_list_datanm3_2" maxlength="8"/>
								</span>
							</div>
						</div>
						<!-- // type2 -->
					</dd>
				</dl>
			</div>

			<dl>
				<dt><label for="loan_list_busu">부서</label></dt>
				<dd>
					<span class="ui-iptText ipt-block ipt-dis">
						<input type="text" name="loan_list_busu" id="loan_list_busu" maxlength="8" readonly="readonly" value="<?=$mem_info_arr[dept_nm]?>"/>
					</span>
				</dd>
			</dl>

			<dl>
				<dt><label for="download_req_sort1">정렬</label></dt>
				<dd>
					<div class="tb pad">
						<div class="tbc"><select name="loan_list_sort1" id="loan_list_sort1"></select></div>
						<div class="tbc"><select name="loan_list_sort2" id="loan_list_sort2"></select></div>
						<div class="tbc"><select name="loan_list_sort3" id="loan_list_sort3"></select></div>
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
			,action : 'loan_list'
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
					scr += '<dt>성명</dt>';
					scr += '<dd>' + o.korname + '</dd>';
					scr += '</dl>';
					scr += '<dl>';
					scr += '<dt>대출일자</dt>';
					scr += '<dd>' + stringToDate(o.loanymd) + '</dd>';
					scr += '</dl>';
					scr += '<dl>';
					scr += '<dt>반납예정일</dt>';
					scr += '<dd>' + stringToDate(o.retscheymd) + '</dd>';
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

		function changeCusForm(n)
		{
			var
				frm3Divs = $('#cusForm dl:nth-child(3)>dd>div')
				,data = datagu_combo_data2[n]
			;
			$('#cusForm label').each(function(i){
				$(this).text(data.label[i]);
			});
			frm3Divs.hide().eq(data.formType).show();
		}

		// generate selectbox
		$('#loan_list_data_info').html(sb1(datagu_combo_data2));
		$('#loan_list_sort1').html(sb2(sort_combo_data_loan_list));
		$('#loan_list_sort2').html(sb2(sort_combo_data_loan_list));
		$('#loan_list_sort3').html(sb2(sort_combo_data_loan_list));

		$('#loan_list_start_date').attr('value', stringToDate(y + mm + d));
		$('#loan_list_end_date').attr('value', stringToDate(y + m + d));

		// init cusform
		changeCusForm(0);

		// change cusform
		$('#loan_list_data_info').change(function(){
			changeCusForm($(this).children(':selected').attr('n'));
		});

		$this.submit(function(){
			var
				error = false
				,result = {
					loan_list_data_info : $this.find('select[name=loan_list_data_info]').val()
					,loan_list_start_date : $this.find('input[name=loan_list_start_date]').val()
					,loan_list_end_date : $this.find('input[name=loan_list_end_date]').val()
					,loan_list_user_name : $this.find('input[name=loan_list_user_name]').val()
					,loan_list_user_id : $this.find('input[name=loan_list_user_id]').val()
					,loan_list_create_no1 : $this.find('input[name=loan_list_create_no1]').val().toUpperCase()
					,loan_list_create_no2 : $this.find('input[name=loan_list_create_no2]').val().toUpperCase()
					,loan_list_datanm1 : $this.find('input[name=loan_list_datanm1]').val()
					,loan_list_datanm1_1 : '선택'
					,loan_list_datanm2 : $this.find('input[name=loan_list_datanm2]').val()
					,loan_list_datanm2_1 : '선택'
					,loan_list_datanm3 : $this.find('input[name=loan_list_datanm3]').val()
					,loan_list_datanm3_1 : $this.find('input[name=loan_list_datanm3_1]').val()
					,loan_list_datanm3_2 : $this.find('input[name=loan_list_datanm3_2]').val()
					,loan_list_datanm3_4 : '선택'
					,loan_list_busu : $this.find('input[name=loan_list_busu]').val()
					,loan_list_sort1 : $this.find('select[name=loan_list_sort1]').val()
					,loan_list_sort2 : $this.find('select[name=loan_list_sort2]').val()
					,loan_list_sort3 : $this.find('select[name=loan_list_sort3]').val()
				}
			;

			mlist.html('');
			loadingSw.show();
			nomore = false;

			postData.start = 0;
			postData.search = JSON.stringify(result);
			postData.start_date = result.loan_list_start_date;
			postData.end_date = result.loan_list_end_date;

			log(postData);

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
	};

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