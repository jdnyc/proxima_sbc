<?
$jsonAddress = ($site_test) ? "mypage_stats1_2_json.php" : "/store/loan_request/get_list.php";
?>

<div class="statesList">
	<nav>
		<ul class="ui-toggleTab">
			<li><a href="mypage.php?act=state1&tab=0#con">대출신청현황</a></li>
			<li><a href="mypage.php?act=state1&tab=1#con" class="active">다운로드신청현황</a></li>
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
				<dt><label for="download_req_data_info">자료구분</label></dt>
				<dd><select name="download_req_data_info" id="download_req_data_info"></select></dd>
			</dl>

			<dl>
				<dt><label for="download_req_start_date">신청일자</label></dt>
				<dd>
					<div class="tb">
						<div class="tbc">
							<span class="ui-iptText ipt-block">
								<input type="date" name="download_req_start_date" id="download_req_start_date" placeholder="0000-00-00" maxlength="10"/>
							</span>
						</div>
						<div class="tbc em">~</div>
						<div class="tbc">
							<span class="ui-iptText ipt-block">
								<input type="date" name="download_req_end_date" placeholder="0000-00-00" maxlength="10"/>
							</span>
						</div>
					</div>
				</dd>
			</dl>

			<dl>
				<dt><label for="download_req_user_name">성명</label></dt>
				<dd>
					<span class="ui-iptText">
						<input type="text" name="download_req_user_name" id="download_req_user_name" value="<?=$_SESSION['user']['KOR_NM']?>" size="13" maxlength="20"/>
					</span>
					<span class="ui-iptText ipt-dis">
						<input type="text" name="download_req_id" size="10" value="<?=$_SESSION['user']['user_id']?>" readonly="readonly"/>
					</span>
				</dd>
			</dl>

			<dl>
				<dt><label for="download_req_create_no1">등록번호</label></dt>
				<dd>
					<div class="tb">
						<div class="tbc">
							<span class="ui-iptText ipt-block">
								<input type="text" name="download_req_create_no1" id="download_req_create_no1" maxlength="8" class="uppercase"/>
							</span>
						</div>
						<div class="tbc em">~</div>
						<div class="tbc">
							<span class="ui-iptText ipt-block">
								<input type="text" name="download_req_create_no2" id="download_req_create_no2" maxlength="8" class="uppercase"/>
							</span>
						</div>
					</div>
				</dd>
			</dl>

			<div id="cusForm">
				<dl>
					<dt><label for="download_req_datanm1">자료명1</label></dt>
					<dd>
						<span class="ui-iptText ipt-block">
							<input type="text" name="download_req_datanm1" id="download_req_datanm1" maxlength="8"/>
						</span>
					</dd>
				</dl>
				<dl>
					<dt><label for="download_req_datanm2">자료명2</label></dt>
					<dd>
						<span class="ui-iptText ipt-block">
							<input type="text" name="download_req_datanm2" id="download_req_datanm2" maxlength="8"/>
						</span>
					</dd>
				</dl>
				<dl>
					<dt><label>자료명3</label></dt>
					<dd>
						<!-- type1 -->
						<div>
							<span class="ui-iptText ipt-block">
								<input type="text" name="download_req_datanm3" maxlength="8"/>
							</span>
						</div>
						<!-- // type1 -->
						<!-- type2 -->
						<div class="tb">
							<div class="tbc">
								<span class="ui-iptText ipt-block">
									<input type="date" name="download_req_datanm3_1" maxlength="8"/>
								</span>
							</div>
							<div class="tbc em">~</div>
							<div class="tbc">
								<span class="ui-iptText ipt-block">
									<input type="date" name="download_req_datanm3_2" maxlength="8"/>
								</span>
							</div>
						</div>
						<!-- // type2 -->
					</dd>
				</dl>
			</div>

			<dl>
				<dt><label for="download_req_busu">부서</label></dt>
				<dd>
					<span class="ui-iptText ipt-block ipt-dis">
						<input type="text" name="download_req_busu" id="download_req_busu" maxlength="15" readonly="readonly" value="<?=$mem_info_arr[dept_nm]?>"/>
					</span>
				</dd>
			</dl>

			<dl>
				<dt><label for="download_req_sort1">정렬</label></dt>
				<dd>
					<div class="tb pad">
						<div class="tbc"><select name="download_req_sort1" id="download_req_sort1"></select></div>
						<div class="tbc"><select name="download_req_sort2" id="download_req_sort2"></select></div>
						<div class="tbc"><select name="download_req_sort3" id="download_req_sort3"></select></div>
					</div>
				</dd>
			</dl>

			<dl>
				<dt><label for="download_req_cond">상태조건</label></dt>
				<dd><select name="download_req_cond" id="download_req_cond"></select></dd>
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
			user_id : '<?=$_SESSION['user']['user_id']?>'
			,mode : 0
			,user_mode : 'user_mode'
			,sort : ''
			,dir : 'ASC'
			,start : 0
			,action : 'rent_download_req'
			,limit : 20
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
					//log(download_combo_data)
					for (var i in json.data)
					{
						var o = json.data[i];

						scr += '<li>';
						scr += '<strong>';
						scr += (download_combo_data[o.datagu-1]) ? '[' + download_combo_data[o.datagu-1].name + '] ' : '';
						scr += o.datanm1;
						scr += '</strong>';
						scr += '<div class="de">';
						//scr += '<dl>';
						//scr += '<dt>매체</dt>';
						//scr += '<dd>' + o.medcd + '</dd>';
						//scr += '</dl>';
						scr += '<dl>';
						scr += '<dt>아카이브 ID</dt>';
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
						scr += '<dt>다운로드 사유</dt>';
						scr += '<dd>' + o.reason + '</dd>';
						scr += '</dl>';
						scr += '<dl>';
						scr += '<dt>부서</dt>';
						scr += '<dd>' + o.dept + '</dd>';
						scr += '</dl>';
						scr += '<dl>';
						scr += '<dt>신청일자</dt>';
						scr += '<dd>' + stringToDate(o.reqymd) + '</dd>';
						scr += '</dl>';
						scr += '<dl>';
						scr += '<dt>승인일자</dt>';
						scr += '<dd>' + stringToDate(o.rtrnymd) + '</dd>';
						scr += '</dl>';
						scr += '<dl>';
						scr += '<dt>저작권</dt>';
						scr += (o.is_copyright == 1) ? '<dd>있음</dd>' : '<dd>없음</dd>';
						scr += '</dl>';
						scr += '<dl>';
						scr += '<dt>상태</dt>';
						scr += '<dd>';
						switch(o.reqstat)
						{
							case 2:
								scr += '승인';
								break;
							case 3:
								scr += '반려';
								break
							default:
								scr += '대기';
						}
						scr += '</dd>';
						scr += '</dl>';
						scr += '<dl>';
						scr += '<dt>다운로드</dt>';
						scr += '<dd>' + o.download_allow + '</dd>';
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
		var $this = $(this);

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
			var frm3Divs = $('#cusForm dl:nth-child(3)>dd>div');
			$('#cusForm label').each(function(i){
				$(this).text(download_combo_data[n].label[i]);
			});
			frm3Divs.hide().eq(download_combo_data[n].formType).show();
		}

		// generate selectbox
		$('#download_req_data_info').html(sb1(download_combo_data));
		$('#download_req_sort1').html(sb2(sort_combo_data_loan_list));
		$('#download_req_sort2').html(sb2(sort_combo_data_loan_list));
		$('#download_req_sort3').html(sb2(sort_combo_data_loan_list));
		$('#download_req_cond').html(sb2(condi_combo_data_loan_list));

		$('#download_req_cond').children().eq(2).attr('selected', 'selected');

		// init cusform
		changeCusForm(0);

		// change cusform
		$('#download_req_data_info').change(function(){
			changeCusForm($(this).children(':selected').attr('n'));
		});

		$this.submit(function(){
			var result = {
				download_req_data_info : $this.find('select[name=download_req_data_info]').val()
				,download_req_start_date : $this.find('input[name=download_req_start_date]').val()
				,download_req_end_date : $this.find('input[name=download_req_end_date]').val()
				,download_req_user_name : $this.find('input[name=download_req_user_name]').val()
				,download_req_id : $this.find('input[name=download_req_id]').val()
				,download_req_create_no1 : $this.find('input[name=download_req_create_no1]').val().toUpperCase()
				,download_req_create_no2 : $this.find('input[name=download_req_create_no2]').val().toUpperCase()
				,download_req_datanm1 : $this.find('input[name=download_req_datanm1]').val()
				,download_req_datanm1_1 : '선택'
				,download_req_datanm2 : $this.find('input[name=download_req_datanm2]').val()
				,download_req_datanm2_1 : '선택'
				,download_req_datanm3 : $this.find('input[name=download_req_datanm3]').val()
				,download_req_datanm3_1 : $this.find('input[name=download_req_datanm3_1]').val()
				,download_req_datanm3_2 : $this.find('input[name=download_req_datanm3_2]').val()
				,download_req_busu : $this.find('select[name=download_req_busu]').val()
				,download_req_sort1 : $this.find('select[name=download_req_sort1]').val()
				,download_req_sort2 : $this.find('select[name=download_req_sort2]').val()
				,download_req_sort3 : $this.find('select[name=download_req_sort3]').val()
				,download_req_cond : $this.find('select[name=download_req_cond]').val()
			};

			mlist.html('');
			loadingSw.show();
			nomore = false;

			delete postData.user_mode;
			delete postData.user_id;
			postData.mode = 'user_mode';
			postData.start = 0;
			postData.one_sort = 'false';
			postData.search = JSON.stringify(result);

			log(postData);

			prtMlist({
				method:false
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
	loadingSw.show();
	prtMlist({
		method : false
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

<?
// UTF-8 한글 체크
?>
