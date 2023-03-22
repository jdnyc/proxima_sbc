<?php
    $user = 'ktvcms';
    $password = 'ktvcms';
    $connection = '10.93.251.139:8521/PROXIMA';
    $charset = 'AL32UTF8';

    $conn = @oci_pconnect($user, $password, $connection, $charset);

    if(!$conn) {
        echo "No Connection ".oci_error();
        exit;
    } else {
       echo "Connect Success!<br>";
    }

    $query = "SELECT * FROM BC_MEMBER WHERE USER_ID LIKE '%ad%'";

    $stmt = oci_parse($conn,$query);
    $result = oci_execute($stmt);

    echo '$result ::: '. $result.'<br>';


    while($row = oci_fetch_assoc($stmt))
    {
        print_r($row);
    }


    // 오라클 접속 닫기 
    oci_free_statement($stmt);
    // 오라클에서 로그아웃 
    oci_close($conn); 
?>