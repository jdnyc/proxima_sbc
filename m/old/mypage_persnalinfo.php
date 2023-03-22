<?
if($site_test){

}else{
	$user_id = $_SESSION['user']['user_id'];
	$mem_info_arr = $mdb->queryRow("select * from member where user_id ='$user_id'");
	/*
	부서 : dept_nm
	직종 : occu_kind
	직급 : job_rank
	직위 : job_position
	직무 : job_duty
	부서번호 : dep_tel_num
	*/

}
?>

<section class="persnalInfo">
	<h1>사용자정보</h1>
	<ul>
		<li>
			<dl>
				<dt>사용지이름</dt>
				<dd><?=$_SESSION['user']['KOR_NM']?>(<?=$_SESSION['user']['user_id']?>)</dd>
			</dl>
		</li>
		<li>
			<dl>
				<dt>부서</dt>
				<dd><?=$mem_info_arr[dept_nm]?></dd>
			</dl>
		</li>
		<li>
			<dl>
				<dt>이메일주소</dt>
				<dd><?=$mem_info_arr[email]?></dd>
			</dl>
		</li>
	</ul>
</section>