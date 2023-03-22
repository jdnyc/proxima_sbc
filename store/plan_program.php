<?php
require_once '../lib/config.php';
require_once '../pages/bis/bis.class.php';

$chan_cd = $_GET['chan_cd'];
$trff_ymd = $_GET['trff_ymd'];
$trff_clf = $_GET['trff_clf'];
$trff_no = $_GET['trff_no'];

$bis = new BIS();

$data = $bis->GetPlanProgramList(array(
            'chan_cd' => $chan_cd,
            'trff_ymd' => $trff_ymd,
            'trff_clf' => $trff_clf,
            'trff_no' => $trff_no
		));

$data = json_decode($data);

echo json_encode(array(
	'success' => true,
	'data' => $data
));