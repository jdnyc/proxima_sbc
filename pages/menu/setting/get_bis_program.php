<?php
session_start();

require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require __DIR__ . '/program.manager.class.php';
require __DIR__ . '/../../bis/bis.class.php';

$params = array(
    'chan_cd' => 'CH_B',
    'pgm_nm'=> '',
    'use_yn'=> 'Y',
    'page'=> 0,
    'row_per_page'=> 10000,
    'sort_field'=> 'pgm_nm',
    'sort_dir'=> 'asc'
);

$bis = new BIS();

$data = $bis->ProgramList($params);
$data = json_decode($data, true);

$program = $db->queryAll('SELECT * FROM SYNC_PROGRAM');

$nodes = array();
foreach ($data as $node) {

    $is_check = false;
    foreach ($program as $p) {
        if ($p['PGM_ID'] == $node['pgm_id']) {
            $is_check = (bool)$p['IS_CHECK'];
            break;
        }
    }

    $_node = array(
        'id' => $node['pgm_id'],
        'text' => $node['pgm_nm'],
        'checked' => $is_check,
        'leaf' => true
    );

    array_push($nodes, $_node);
}

echo json_encode($nodes);
