<?php
header('Content-Type: text/html; charset=UTF-8');
session_start();

$_SESSION['user'] = '';

// UTF-8 한글체크
?>

<script type="text/javascript">
top.location.href="../login_form.php";
</script>