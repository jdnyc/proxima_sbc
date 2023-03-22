<?
include "_head.php";

$jsonAddress = ($site_test) ? "mov_view_rental_json.php" : "/store/loan_request_exec.php";

$a7date = substr(date('YmdHis',strtotime('+7 days',strtotime(date('YmdHis')))), 0, 8);
$todayDate = substr(date('YmdHis'), 0, 8);
?>

<hgroup class="subTitle">
	<h1>다운로드 신청</h1>
</hgroup>

<div class="mediaForm" id="mediaForm">
	<?if($_GET[copyright_yn]){?>
		<!-- 저작권 콘텐츠 -->
		<form action="#" method="post" class="box" name="copyright_info">
			<input type="hidden" name="etc_purpose" value=""/>
			<fieldset>
				<legend>저작권 콘텐츠</legend>
				<p>
					<strong class="co-red">본 콘텐츠는 저작권 자료로서 저작권자의 허가 또는 동의 없이 임의로 사용하거나 배포, 전송하는 경우 이에 대한 모든 책임은 사용자에게 있습니다.</strong>
				</p>
				<ul>
					<li>
						<label for="content_purpose">사용목적</label>
						<select name="content_purpose" id="content_purpose">
							<option value="재사용">재사용</option>
							<option value="단순참고">단순참고</option>
							<option value="기타">기타</option>
						</select>
					</li>
					<li>
						<label for="content_agreement">저작권 합의내용</label>
						<span class="ui-iptText ipt-block"><textarea name="content_agreement" id="content_agreement" rows="4" maxlength="100"></textarea></span>
						<p>
							<strong>저작권 경고문을 확인하였고 사용 목적을 저작권자의 허가를 받았으며 합의 내용을 입력하였습니다.</strong>
						</p>
					</li>
				</ul>
			</fieldset>
		</form>
		<!-- // 저작권 콘텐츠 -->
	<?}?>
	
	<form action="#" method="post" name="download_info">
		<fieldset>
			<legend class="blind">다운로드 신청폼</legend>
			<ul>
				<li>
					<label for="res_combo">타입</label>
					<select name="res_combo" id="res_combo"></select>
				</li>
				<li>
					<label>사용기간</label>
					<span class="ui-iptText ipt-dis"><input type="date" size="12" name="start_date" value="<?=$todayDate?>" readonly="readonly"/></span> ~ <span class="ui-iptText ipt-dis"><input type="date" size="12" value="<?=$a7date?>" name="end_date" readonly="readonly"/></span>
				</li>
				<li>
					<label for="download_reason">다운로드사유</label>
					<span class="ui-iptText ipt-block"><textarea name="download_reason" id="download_reason" rows="4" maxlength="100"></textarea></span>
					<p>
						<strong class="co-red">*알림 : 다운로드 사유는 100자 이내로 작성하세요.</strong>
					</p>
				</li>
			</ul>
		</fieldset>
	</form>

	<div class="btngroup">
		<button type="button" class="ui-button" onclick="history.back()">뒤로가기</button>
		<button type="button" class="ui-button btn-blue" id="onSubmit">신청</button>
	</div>
</div>

<script type="text/javascript">
jQuery(function($){
	var mediaForm = $('#mediaForm');

	$.fn.infOutput = function(type)
	{
		var
			$this = $(this)
			,out = new Object()
			,fail = false
		;

		if (type == 'dat1')
		{
			$this.children('input[type=hidden]').each(function(i){
				out[$(this).attr('name')] = $(this).val();
			});
		}

		$this.find('fieldset textarea').each(function(i){
			if (!$(this).val())
			{
				alert('항목을 입력해주세요.');
				$(this).focus();
				fail = true;
			}
			else
			{
				out[$(this).attr('name')] = $(this).val();
			}
		});

		if (!fail)
		{
			return out;
		}
	}

	$.fn.resCombo = function(obj)
	{
		var
			$this = $(this)
			,scr = ''
		;

		for (var i in obj)
		{
			scr += '<option value="' + obj[i][0] + '" n="' + obj[i][1] + '">' + obj[i][0] + '</option>';
		}
		
		$this.html(scr);
	}

	// 미디어 타입 옵션제작
	$('#res_combo').resCombo(res_combo_data);

	// submit 이벤트
	$('#onSubmit').on('click', function(){
		var
			result = new Object()
			,copyright_info = mediaForm.children('form[name=copyright_info]')
			,download_info = mediaForm.children('form[name=download_info]')
		;

		if (copyright_info.length)
		{
			var dat1 = copyright_info.infOutput('dat1');
			if (dat1)
			{
				result.copyright_info = JSON.stringify(dat1);
			}
			else
			{
				return false;
			}
		}
		else
		{
			result.copyright_info = 'null';
		}

		var dat2 = download_info.infOutput('dat2');
		if (dat2)
		{
			result.download_info = JSON.stringify(dat2);
		}
		else
		{
			return false;
		}

		result.action = 'download';
		result.loan_info = 'null';
		result.check = '';
		result.start = '';
		result.end = '';
		result.parent_check = '';
		result.records = '["<?=$_GET[content_id]?>"]';
		result.retscheymd = mediaForm.find('input[name=end_date]').val();
		result.res_combo = $('#res_combo>option:selected').attr('n');

		getJsonData({
			url : '<?=$jsonAddress?>'
			,type : 'post'
			,dataType : 'json'
			,parameter : objectToPost(result)
			,complete : function(json)
			{
				if (json.result)
				{
					alert(json.msg);
					history.back();
				}
			}
		});
		return false;
	});
});
</script>

<?
include "_foot.php";
// UTF-8 한글 체크
?>