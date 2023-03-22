<?php

///* Specify the server and connection string attributes. */
//$serverName = $arr_sys_code['interwork_flashnet']['ref5'];
//
///* Get UID and PWD from application-specific files.  */
//$uid = "flashnet";
//$pwd = "flashnet";
//$connectionInfo = array( "UID"=>$uid,
//    "PWD"=>$pwd,
//    "Database"=>"flashnet");
//echo "$serverName, ";
//print_r($connectionInfo);
//echo '<br />';
///* Connect using SQL Server Authentication. */
//$conn = sqlsrv_connect( $serverName, $connectionInfo);
//
//if( $conn === false )
//{
//    echo "Unable to connect.</br>";
//    die( print_r( sqlsrv_errors(), true));
//}
//else
//{
//    echo "connect!!!!!!!!.</br>";
//}



////header('Content-Type: text/html; charset=euc-kr');
//$user = base64_decode('Zmxhc2huZXQ=');//flashnet
//$password = base64_decode('Zmxhc2huZXQ=');//flashnet
////base64_decode('c2dsYWRtaW4=');//sgladmin
////base64_decode('MQ==');//1
//$server = $arr_sys_code['interwork_flashnet']['ref5'];
//$database = base64_decode('Zmxhc2huZXQ=');//flashnet
//
////echo $database;
////print_r("Driver={SQL Server Native Client 10.0};Server=$server;Database=$database;, $user, $password");
////$dbh_ms = odbc_connect("Driver={SQL Server Native Client 10.0};Server=$server;Database=$database;", $user, $password);
//$dbh_ms = odbc_connect('Flashnet', $username, $password);

$username = "flashnet"; 
$password = "flashnet"; 
$odbc_name = "Flashnet"; 

$dbh_ms = odbc_connect($odbc_name, $username, $password);

function db_ms_fetchAll($query) {
	global $dbh_ms;

	$stmt = odbc_exec($dbh_ms, $query);

	$result = array();
	while ($row = odbc_fetch_array($stmt)) {
		$result[] = $row;
	}
	
	return convert_key_lower($result);
}

function db_ms_exec($query) {
	global $dbh_ms;

	odbc_exec($dbh_ms, $query);
}

function db_ms_fetchOne($query) {
	global $dbh_ms;

	$stmt = odbc_exec($dbh_ms, $query);

	return odbc_result($stmt, 1);
}

function convert_key_lower($data) {
	$result = array();
	foreach ($data as $idx=>$row) {
		foreach ($row as $k=>$v) {
			$result[$idx][strtolower($k)] = $v;
		}
	}

	return $result;
}

?>