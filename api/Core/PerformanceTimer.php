<?php

namespace Api\core;

class PerformanceTimer
{
    private $timeList;

    public function __construct()
    {
        $this->timeList = [];
    }

    public function addLap($labName)
    {
        $this->timeList[] = $labName . ' => ' . date('H:i:s');
    }

    public function getLaps()
    {
        return $this->timeList;
    }

    public function dd()
    {
        dd($this->getLaps());
    }
}