<?php
function msg($msg) {
	$msg = str_replace('"','\"',$msg);

	echo("
			<script>			
			alert(\"$msg\");
			</script>
		");
}


//UTF-8 한글 체크
?>