<?php
/**
 * Created by PhpStorm.
 * User: cerori
 * Date: 2015-04-08
 * Time: 오후 5:16
 */

class Content {
    private $content;

    function __construct($content) {
        $this->content = $content;
    }

    public static function putReview($content_id, $state, $comments, $user_id)
    {
        $requester = '';
        $accepter = '';

        if ($state == GRANT_REVIEW_REQUEST) {
            $requester = $user_id;
        } else {
            $requester = Content::getRequester($content_id);
            $accepter = $user_id;
        }

        $id = $GLOBALS['db']->exec("SELECT COALESCE(MAX(ID), 0) + 1 FROM REVIEW");
        $GLOBALS['db']->insert('REVIEW', array(
            'ID' => $id,
            'CONTENT_ID' => $content_id,
            'STATE' => $state,
            'COMMENTS' => $comments,
            'REQUESTER' => $requester,
            'ACCEPTER' => $accepter
        ));
    }

    function save() {
        $_content = $this->content;

        $id = $_content['content_id'];
        unset($_content['content_id']);

        $GLOBALS['db']->update('BC_CONTENT', $_content, 'content_id = $id');
    }

    /**
     * @param $content_id
     * @param null $and
     * @return int|string
     */
    static function getState($content_id, $and = null) {
        $state = $GLOBALS['db']->queryOne("SELECT COALESCE(STATE, 0) FROM BC_CONTENT WHERE CONTENT_ID = $content_id");
        if ($state != 0 && isset($and)) {
            $state = $state & $and;
        }

        return $state;
    }

    /**
     * @param $content_id
     * @param $state
     * @param $add_bit
     */
    static function setState($content_id, $state, $add_bit = null) {
        if (isset($add_bit)) {
            $state += $add_bit;
        }

        $GLOBALS['db']->update("BC_CONTENT", array(
            'STATE' => $state
        ), "CONTENT_ID = $content_id");
    }

    static function getRequester($content_id) {
        return $GLOBALS['db']->queryOne("SELECT REQUESTER
                                       FROM REVIEW
                                      WHERE STATE = '", GRANT_REVIEW_REQUEST, "'
                                        AND CONTENT_ID = $content_id");
    }
}
