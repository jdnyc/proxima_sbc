<?php
/**
 * Created by PhpStorm.
 * User: cerori
 * Date: 2015-04-08
 * Time: 오후 5:14
 */

class Review {
    private $content = null;

    function __construct($content) {
        $this->content = $content;
    }

    /**
     * 콘텐츠 상태 변경
     * @param $state
     */
    function setState($state) {

    }

    function getState() {
        return $this->content['state'];
    }
}