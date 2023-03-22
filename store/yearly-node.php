<?php

use Proxima\core\Path;
use Proxima\core\Request;

session_start();
require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/functions.php');
// require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/lang.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/FcpXML.class.php');


if(Request::POST('start_year') != null){
    $startYear = Request::POST('startYear');
}else{
    if(Request::GET('startYear') != null){
        $startYear = Request::GET('start_year');
    }else{
        $startYear = 1940;
    }
};

/**
 * bool true or false
 */
if(Request::POST('reverse') != null){
    $reverse = Request::POST('reverse');
}else{
    if(Request::GET('reverse') != null){
        $reverse = Request::GET('reverse');
    }else{
        $reverse = true;
    }
};

if(Request::POST('start_year') != null){
    $startYear = Request::POST('startYear');
}else{
    if(Request::GET('startYear') != null){
        $startYear = Request::GET('start_year');
    }else{
        $startYear = 1940;
    }
};

$endYear = (int)date("Y");
$yearlyArr = [];
$childYearlyArr = [];


// 연도별  1940
while($startYear <= $endYear){
    $y = 0;
    $childYearlyArry = [];
    array_push($yearlyArr, $startYear);
    $leaf = true;
    // 세부 연도별 1940-01-01 ~ 1940-12-31
    while($y < 10){
        $childYear = $startYear+$y;
            $childYearlyArry[$y]['text'] = "{$childYear}년도";
            $childYearlyArry[$y]['leaf'] = true;    
            // $childYearlyArry[$y]['startDate'] = "{$childYear}0101000000";
            // $childYearlyArry[$y]['endDate'] = "{$childYear}1231115959";
            $childYearlyArry[$y]['startDate'] = "{$childYear}0101";
            $childYearlyArry[$y]['endDate'] = "{$childYear}1231";
        $y = $y +1;
    }
    $childYearlyArr[$startYear] =  $childYearlyArry;
    if($startYear >= 2010){
        $startYear = $startYear + 1;
        $leaf = false;
    }else{
        $startYear = $startYear + 10;
        $leaf = true;
    }
    
};

// 연도별
$yearlyTree = array();
$i = 0;
foreach($childYearlyArr as $year => $child1){
 
    $childrenTree = null;
    $childYear = array();
    foreach($child1 as $child2){
    array_push($childYear,((object)$child2));  
            if($year >= 2010){
                $endYear = $year;
            }else{
                $endYear = $year +9;
            } 
            // $startDate = "{$year}0101000000";
            // $endDate = "{$endYear}1231115959";   
            $startDate = "{$year}0101";
            $endDate = "{$endYear}1231";   
    }

    array_push($yearlyTree, array(
        'text' => "{$year}년",
        'leaf' => true,
        'children' => $childYear,
        'startDate' => $startDate,
        'endDate' => $endDate,
        'type' => 'yearly'
    ));
}

if($reverse){
    $yearlyTree = array_reverse($yearlyTree);
};

echo json_encode($yearlyTree);
