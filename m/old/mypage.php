<?
include "_head.php";
?>

<div class="mypageMain">
	<hgroup class="subTitle">
		<h1>Mypage</h1>
	</hgroup>

	<?
	// 사용자정보
	include "mypage_persnalinfo.php";
	?>

	<div class="btngroup logout">
		<a href="login_out.php" class="ui-button btn-block" onclick="return confirm('로그아웃하시겠습니까?')">로그아웃</a>
	</div>

	<section class="body">
		<a name="con"></a>
		<ul id="bodyTab" class="ui-tab">
			<li><a href='mypage.php?act=notice#con'>공지사항</a></li>
			<li><a href='mypage.php?act=state1&tab=0#con'>신청현황</a></li>
			<li><a href='mypage.php?act=state2&tab=0#con'>현황조회</a></li>
		</ul>

		<div class="con">
			<?
			switch($_GET[act])
			{
				case "state1":
					switch($_GET[tab])
					{
						case 0:
							include "mypage_stats1_1.php";
							break;
						case 1:
							include "mypage_stats1_2.php";
							break;
					}
					break;
				case "state2":
					switch($_GET[tab])
					{
						case 0:
							include "mypage_stats2_1.php";
							break;
						case 1:
							include "mypage_stats2_2.php";
							break;
						case 2:
							include "mypage_stats2_3.php";
							break;
					}
					break;
				default:
					include "mypage_notice.php";
					break;
			}
			?>
		</ul>
	</section>
</div>

<script type="text/javascript">
jQuery(function($){
	var
		act = '<?=$_GET[act]?>'
		,actNum = 0
	;
	switch(act)
	{
		case 'state1':
			actNum = 1;
			break;
		case 'state2':
			actNum = 2;
			break;
		default:
			actNum = 0;
			break;
	}
	$('#bodyTab>li').eq(actNum).addClass('active');
});
</script>

<?
include "_foot.php";
// UTF-8 한글 체크
?>