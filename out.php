<?php

require __DIR__ . '/vendor/autoload.php';

use Proxima\core\Session;

Session::init();

//extract($_REQUEST);

//ini_set('display_errors', 0);
header('Content-Type: text/html; charset=utf-8');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lib.php');

//로그아웃시 창이 나오지 않고 바로 홈으로 갈수 있도록 수정
//2010 12 20 조훈휘
if (!$_SESSION['user']) {
    echo "<script>alert(_text('MSG01001'))</script>";
    echo "<script>location.href='/index.php'</script>";
    exit;
}
