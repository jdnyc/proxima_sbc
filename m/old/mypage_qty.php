<?
include "_head.php";
?>
<br>
대출수량
<br>
<br>
<br>

<div id='json_output'> 데이타 출력 부분</div>

<script>
function mov_view_ajax(){
	/*
	반드시 post 전송 할것.
	type=1       : 1이면 온라인, 2이면 오프라인
	member_id=1  : 무조건 1
	*/
	<?if($_SERVER[HTTP_HOST] == 'www.local-das.co.kr'){?>
		$('#json_output').load('mypage_qty_json.php');
	<?}else{?>
		$('#json_output').load('/menu/config/user/php/get_qty.php');
	<?}?>

}

// 도큐먼트가 로드되면 실행
jQuery(document).ready(function(){
	mov_view_ajax();
});


</script>
<?
include "_foot.php";
// UTF-8 한글 체크
?>