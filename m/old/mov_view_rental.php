<?
include "_head.php";

$jsonAddress = ($site_test) ? "mov_view_rental_json.php" : "/store/loan_request_exec.php";

$a7date = substr(date('YmdHis',strtotime('+7 days',strtotime(date('YmdHis')))), 0, 8);
$todayDate = substr(date('YmdHis'), 0, 8);
?>

<hgroup class="subTitle">
	<h1>대출신청</h1>
</hgroup>

<div class="mediaForm">
	<form action="#" method="post" id="sendForm">
		<fieldset>
			<legend class="blind">대출신청폼</legend>
			<ul>
				<li>
					<label for="loan_purpose">대출용도</label>
					<select name="loan_purpose" id="loan_purpose"></select>
				</li>
				<li>
					<label for="loan_reason">대출사유</label>
					<span class="ui-iptText ipt-block"><textarea name="loan_reason" id="loan_reason" rows="4" maxlength="100"></textarea></span>
					<p>
						<strong class="co-red">*알림 : 대출 사유는 100자 이내로 작성하세요.</strong>
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
	var frm = $('#sendForm');

	// 대출용도 옵션제작
	function resCombo(obj)
	{
		var scr = '';
		for (var i in obj)
		{
			scr += '<option value="' + obj[i][0] + '" n="' + obj[i][1] + '" f="' + obj[i][2] + '">' + obj[i][0] + '</option>';
		}
		return scr;
	}

	$('#loan_purpose')
		.html(resCombo(loan_combo_data))
		.change(function(){
			if (Number($(this).children(':selected').attr('f')) == 0)
			{
				$('#loan_reason')
					.val('')
					.attr('disabled','disabled')
					.parent().addClass('ipt-dis')
				;
			}
			else
			{
				$('#loan_reason')
					.removeAttr('disabled')
					.parent().removeClass('ipt-dis')
				;
			}
		})
	;

	// submit 이벤트
	$('#onSubmit').on('click', function(){
		var
			result = new Object()
			,error = null
		;

		function fromData()
		{
			var o = new Object();
			o.loan_purpose = frm.find('select[name=loan_purpose]').val();
			o.loan_reason = frm.find('textarea[name=loan_reason]').val();
			
			if (o.loan_purpose == '선택')
			{
				error = '대출용도를 선택해주세요.';
				frm.find('select[name=loan_purpose]').focus();
				return false;
			}

			if (!o.loan_reason && !$('#loan_reason').attr('disabled'))
			{
				error = '대출사유를 입력해주세요.';
				frm.find('textarea[name=loan_reason]').focus();
				return false;
			}

			if (o.loan_reason)
			{
				return JSON.stringify(o);
			}
			else
			{
				return false;
			}
		}

		result.records = '["<?=$_GET[content_id]?>"]';
		result.action = 'loan';
		result.download_info = 'null';
		result.loan_info = fromData();
		result.copyright_info = 'null';
		result.res_combo = $('#loan_purpose>option:selected').attr('n');
		result.check = '';
		result.start = '';
		result.end = '';
		result.retscheymd = '<?=$a7date?>';
		result.parent_check = '';

		if (error)
		{
			alert(error);
			return false;
		}

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
	});
});
</script>

<?
include "_foot.php";
// UTF-8 한글 체크
?>