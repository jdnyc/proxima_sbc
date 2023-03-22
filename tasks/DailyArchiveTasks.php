<?php
use Crunz\Schedule;

$scheduler = new Schedule();
$task = $scheduler->run(PHP_BINARY . ' artisan schedule:archive');
// $task->daily()
// ->hour(1,2,3,4,5,6,7,12,22,23)
//     ->description('Daily Archive');

$task->everyHour()
    ->skip(function() {
        $flag = true;
        switch (date("w")) {
            case '0':
                # 일요일
            case '6':
                # 토요일
                $flag = false;
                break;
            case '1':
            case '2':
            case '3':
            case '4':
            case '5':
            default:
                # 평일
                switch (date("G")) {
                    case '0': case '8': case '9': case '10': case '11':
                    case '13': case '14': case '15': case '16':
                    case '17': case '18': case '19': case '20': case '21':
                        $flag = true;
                        break;
                    default:
                        $flag = false;
                        break;
                }
                break;
        }
        return (bool) $flag;
    })
    ->description('Daily Archive');

$task = $scheduler->run(PHP_BINARY . ' artisan schedule:archive_mig');
$task->everyHour()
    ->description('Archive Migration');

return $scheduler;