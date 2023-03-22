<?php

namespace ProximaCustom\models;

use Proxima\core\ModelBase;

/**
 * 동영상 코드
 */
class VideoCode extends ModelBase
{
    public static $table = 'video_codes';

    /**
     * 동영상 코드생성
     * 동영상 코드 생성 규칙 (알파벳 1글자 + 숫자 6)
     *
     * @return \ProximaCustom\models\VideoCode
     */
    public static function create()
    {
        $latest = self::latest();
        
        $code = '';
        if (is_null($latest)) {
            $code = 'A000001';
        } else {
            $code = self::getNextCode($latest->get('code'));
        }

        $videoCode = new VideoCode(array('code' => $code));
        self::insert(self::$table, $videoCode->getAll());
        return $videoCode;
    }

    /**
     * 다음 코드 구하기
     *
     * @param string $currentCode
     * @return string
     */
    private static function getNextCode($currentCode)
    {
        if (strlen($currentCode) != 7) {
            throw new \Exception('invalid_video_code(length)');
        }

        $prefix = $currentCode[0];
        $number = (int)substr($currentCode, 1);

        if ($number === 999999) {
            $prefix++;
            $number = 0;
        } else {
            $number++;
        }

        $code = sprintf('%s%06d', $prefix, $number);
        return $code;
    }

    public static function latest()
    {
        $query = "SELECT code FROM " . self::$table . " order by id desc";
        $videoCode = ModelBase::queryObject($query, VideoCode::class);
        return $videoCode;
    }
}
