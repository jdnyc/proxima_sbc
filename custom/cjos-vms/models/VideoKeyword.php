<?php

namespace ProximaCustom\models;

use Proxima\core\ModelBase;

/**
 * 동영상 키워드
 */
class VideoKeyword extends ModelBase
{
    public static $table = 'video_keywords';

    /**
     * 아이디로 조회
     *
     * @param mixed $id
     * @return \ProximaCustom\models\VideoKeyword
     */
    public static function find($id)
    {
        $query = "SELECT * FROM " . self::$table . " WHERE id = {$id}";
        
        $videoKeyword = ModelBase::queryObject($query, VideoKeyword::class);

        return $videoKeyword;
    }

    /**
     * 키워드로 조회
     *
     * @param string $keyword
     * @return \ProximaCustom\models\VideoKeyword
     */
    public static function findByKeyword($keyword)
    {
        $query = "SELECT * FROM " . self::$table . " WHERE keyword = '{$keyword}'";
        
        $videoKeyword = ModelBase::queryObject($query, VideoKeyword::class);

        return $videoKeyword;
    }

    /**
     * 키워드로 검색
     *
     * @param string $keyword
     * @return array array of \ProximaCustom\models\VideoKeyword
     */
    public static function searchKeyword($keyword)
    {
        $query = "SELECT * FROM " . self::$table . " WHERE keyword like '{$keyword}%' ORDER BY keyword";
        
        $videoKeywords = ModelBase::queryCollection($query, VideoKeyword::class);

        return $videoKeywords;
    }

    /**
     * 동영상 키워드 생성
     *
     * @param string $keyword
     * @return void
     */
    public static function create($keyword)
    {
        if (empty($keyword)) {
            return;
        }
        self::insert(self::$table, ['keyword' => $keyword]);
    }
}
