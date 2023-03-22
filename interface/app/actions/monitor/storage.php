<?php
$server->register('UpdateMonitorStorage',
    array(
        'server_name' => 'xsd:string',
        'server_ip' => 'xsd:string',
        'drive' => 'xsd:string',
        'used' => 'xsd:string',
        'available' => 'xsd:string',
        'total' => 'xsd:string'
    ),
    array(
		'response'	=> 'xsd:string'
	),
    $namespace,
    $namespace.'#UpdateMonitorStorage',
    'rpc',
    'encoded',
    'UpdateMonitorStorage'
);

/**
 * 에이전트 입력 문자 파서
 *
 * @param String $param
 * @return void
 */
function UpdateMonitorStorageParser($param)
{
    $lists =  explode(' ', $param);
          
    $firstVal = trim(array_shift($lists) );
    $secondsVal =  trim(array_pop($lists) );
    $firstVal = str_replace(',', '', $firstVal);
    $secondsVal = str_replace(',', '', $secondsVal);
    //숫자만 추출
    $firstValNum = preg_replace("/[^0-9.]*/s", "", $firstVal); 
    
    //문자만 추출 
    $firstValLetter = preg_replace("/[0-9.]*/s", "", $firstVal); 

    if($firstValLetter == "KB"){
        $firstValNum = $firstValNum * 1024;
    }else if( $firstValLetter == "MB" ){
        $firstValNum = $firstValNum * 1024* 1024;
    }else if( $firstValLetter == "GB" ){
        $firstValNum = $firstValNum * 1024* 1024* 1024;
    }else if( $firstValLetter == "TB" ){
        $firstValNum = $firstValNum * 1024* 1024* 1024* 1024;
    }else if( $firstValLetter == "PB" ){
        $firstValNum = $firstValNum * 1024* 1024* 1024* 1024* 1024;
    }

    //괄호제거
    $secondsVal = preg_replace("/[\(\\%)]*/s", "", $secondsVal); 
    return [
        'param' => $param,
        'firstVal' => $firstVal,
        'seconds' => $secondsVal,
        'firstValNum' => $firstValNum,
        'firstValLetter' => $firstValLetter
    ];
}

function UpdateMonitorStorage($server_name, $server_ip, $drive, $used, $available, $total) {
    global $db;

    // `name` varchar(1000) ,
	// `ip` varchar(1000) ,
	// `drive` varchar(1000) ,
	// `used` varchar(1000) ,
	// `available` varchar(1000) ,
	// `total` varchar(1000) ,
	// `used_num` bigint,
	// `available_num` bigint,
	// `total_num` bigint,
	// `created_at` varchar(14) ,
	// `updated_at` varchar(14) ,
	// `deleted_at` varchar(14) ,

    try {

        //레터명
        $driveParser = UpdateMonitorStorageParser($drive); 
        $driveLetter = $driveParser['seconds'];

        $nowDt = date("YmdHis");

        if( !empty($driveLetter) ) {

            $usedParser = UpdateMonitorStorageParser($used);
            $usedNum = $usedParser['firstValNum'];

            $availableParser = UpdateMonitorStorageParser($available);
            $availableNum = $availableParser['firstValNum'];

            $totalParser = UpdateMonitorStorageParser($total);
            $totalNum = $totalParser['firstValNum'];

            $storageInfo = $db->queryRow("select * from MONITORING_STORAGE where drive='$driveLetter'");
            if( empty($storageInfo) ){
                //등록
                 
                $db->insert("MONITORING_STORAGE", [
                    'NAME' => $server_name,
                    'IP' => $server_ip,
                    'DRIVE' => $driveLetter,
                    'USED' => $used,
                    'AVAILABLE' => $available,
                    'TOTAL' => $total,
                    'USED_NUM' => $usedNum,
                    'AVAILABLE_NUM' => $availableNum,
                    'TOTAL_NUM' => $totalNum,
                    'CREATED_AT' => $nowDt,
                    'UPDATED_AT' => $nowDt
                ]);
            }else{
                //업데이트
                $id = $storageInfo['id'];

                $db->update("MONITORING_STORAGE", [
                    'NAME' => $server_name,
                    'IP' => $server_ip,
                    'DRIVE' => $driveLetter,
                    'USED' => $used,
                    'AVAILABLE' => $available,
                    'TOTAL' => $total,
                    'USED_NUM' => $usedNum,
                    'AVAILABLE_NUM' => $availableNum,
                    'TOTAL_NUM' => $totalNum,
                    'UPDATED_AT' => $nowDt
                ],"id='$id'");
            }
        }

		return 'true';
    } catch (Exception $e) {
		return 'false';
    }
}
