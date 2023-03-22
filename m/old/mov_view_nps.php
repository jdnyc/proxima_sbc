<?
include "_head.php";

$jsonAddress = ($site_test) ? "mov_view_rental_json.php" : "/store/transmit_request.php";
?>

<hgroup class="subTitle">
	<h1>NPS전송</h1>
</hgroup>

<div class="mediaForm">
	<form action="#" name="sendForm" method="post" id="sendForm">
		<fieldset>
			<legend class="blind">NPS전송</legend>
			<ul>
				<li>
					<label for="transmit_category">카테고리</label>
					<select name="transmit_category" id="transmit_category">
						<option value="샘플1">샘플1</option>
						<option value="샘플2">샘플2</option>
						<option value="샘플3">샘플3</option>
					</select>
				</li>
				<li>
					<label for="transmit_reason">전송사유</label>
					<span class="ui-iptText ipt-block"><textarea name="transmit_reason" id="transmit_reason" rows="4" maxlength="100"></textarea></span>
					<p>
						<strong class="co-red">*알림 : 전송사유는 100자 이내로 작성하세요.</strong>
					</p>
				</li>
			</ul>
		</fieldset>
		<div class="btngroup">
			<button type="button" class="ui-button" onclick="history.back()">뒤로가기</button>
			<button type="submit" class="ui-button btn-blue">전송</button>
		</div>
	</form>
</div>
<script type="text/javascript">
jQuery(function($){
	var postData = {
		action : 'transmit'
		,content_id : '<?=$_GET[content_id]?>'
		,copyright_info : 'null'
		,transmit_info : ''
	};

	$('#sendForm').submit(function(){
		var
			$this = $(this)
			,o = new Object()
			,error = null
		;

		o.transmit_category = $this.find('select[name=transmit_category]').val();
		o.transmit_reason = $this.find('textarea[name=transmit_reason]').val();

		if (!o.transmit_category)
		{
			error = '카테고리를 선택해주세요.';
			$this.find('select[name=transmit_category]').focus();
		}

		if (!o.transmit_reason)
		{
			error = '전송사유를 입력해주세요.';
			$this.find('textarea[name=transmit_reason]').focus();
		}

		if (error)
		{
			alert(error);
			return false;
		}

		postData.transmit_info = JSON.stringify(o);

		getJsonData({
			url : '<?=$jsonAddress?>'
			,type : 'post'
			,dataType : 'json'
			,parameter : objectToPost(postData)
			,complete : function(json)
			{
				if (json.result)
				{
					alert('전송완료');
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