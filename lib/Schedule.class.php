<?php
/**
 * Created by PhpStorm.
 * User: cerori
 * Date: 2015-02-26
 * Time: 오전 11:13
 */

require dirname(__FILE__) . '/../vendor/autoload.php';

class Schedule {

    private $cron = null;

    public function __construct() {

    }

    /**
     * @param $date
     * @param $time
     * @throws Exception
     */
    public function specifyDay($date, $time) {
        $dateParts = $this->explodeDate($date);
        $timeParts = $this->explodeTime($time);

        $expression = (int)$timeParts[1] . ' ' . (int)$timeParts[0] . ' ' . (int)$dateParts[2] . ' ' . (int)$dateParts[1] . ' * ' . (int)$dateParts[0];

        $this->cron = Cron\CronExpression::factory($expression);
    }

    /**
     * @param $time
     * @throws Exception
     */
    public function daily($time) {
        $timeParts = $this->explodeTime($time);

        $expression = (int)$timeParts[1] . ' ' . (int)$timeParts[0] . ' * * *';

        $this->cron = Cron\CronExpression::factory($expression);
    }

    /**
     * @param $week
     * @param $time
     * @throws Exception
     */
    public function weekly($week, $time) {
        $timeParts = $this->explodeTime($time);

        $expression = $timeParts[1] . ' ' . $timeParts[0] . ' * * ';

        /*
         * 0~6: 일 ~ 토         
         * 7: 일요일
         */
        $expression .= ' ' . $week;

        $this->cron = Cron\CronExpression::factory($expression);
    }

    /**
     * @param $start_date
     * @param $end_date
     * @param $time
     * @throws Exception
     */
    public function term($start_date, $end_date, $time) {

        $startDateParts = $this->explodeDate($start_date);
        $endDateParts = $this->explodeDate($end_date);
        $timeParts = $this->explodeTime($time);

        $expression = (int)$timeParts[1] . ' ' . $timeParts[0] . ' ' . $startDateParts[2] . '-' . $endDateParts[2] . ' ' . $startDateParts[1] . '-' . $endDateParts[1] .' *';

        $this->cron = Cron\CronExpression::factory($expression);
    }

    /**
     * @param $date
     * @return array
     * @throws Exception
     */
    public function explodeDate($date) {
        if ( ! strstr($date, '-')) {
            $date = substr($date , 0, 4) . '-' .  substr($date , 4, 2) . '-' . substr($date , 6, 2);
        }
        $dateParts = explode('-', $date);

        if (count($dateParts) != 3) {
            throw new Exception('날짜형식이 맞지 않습니다.(' . $date . ')');
        }

        return array_map(function($item) {
            return (int)$item;
        }, $dateParts);
    }

    /**
     * @param $time
     * @return array
     * @throws Exception
     */
    public function explodeTime($time) {
        if ( ! strstr($time, ':')) {
            $time = substr($time , 0, 2) . ':' .  substr($time , 2, 2) . ':' . substr($time , 4, 2);
        }
        $timeParts = explode(':', $time);

        if (count($timeParts) != 3) {
            throw new Exception('시간형식이 맞지 않습니다.(' . $time . ')');
        }

        return array_map(function($item) {
            return (int)$item;
        }, $timeParts);
    }

    public function getCronExpression() {
        if ( ! $this->cron) {
            throw new Exception('cron이 설정되지 않았습니다.');
        }

        return $this->cron->getExpression();
    }
}