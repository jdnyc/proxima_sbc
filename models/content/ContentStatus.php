<?php

namespace Proxima\models\content;

use \Proxima\core\ModelBase;

/**
 * ContentStatus model class
 */
class ContentStatus extends ModelBase
{
    /**
     * 콘텐츠 상태코드로 콘텐츠 상태 객체 조회
     *
     * @param mixed $status status code (ex. 0, 1, 2, 3 ...)
     * @return ContentStatus object of ContentStatus
     */
    public static function findByStatus($status)
    {        
        $code = \Proxima\models\system\Code::getCode('CONTENT_STATUS', $status);

        return self::createContentStatusFromCode($code);
    }

    /**
     * 모든 콘텐츠 상태코드 조회
     *     
     * @param bool $onlyCanUseStatus 사용할 수 있는 상태코드만 조회할지 여부
     * @return array array of ContentStatus object 
     */
    public static function all($onlyCanUseStatus = true)
    {
        $codes = \Proxima\models\system\Code::getCodeList('CONTENT_STATUS', ['code' => 'ASC']);
        $result = [];
        foreach($codes as $code) {
            if($code->get('use_yn') != 'Y') {
                continue;
            }
            $result[(string)$code->get('code')] = self::createContentStatusFromCode($code);
        }
        return $result;
    }

    /**
     * Code데이터로 ContentStatus객체 생성
     *
     * @param Code $code Code object
     * @return ContentStatus ContentStatus object
     */
    public static function createContentStatusFromCode($code)
    {
        $data = [
            'code' => $code->get('code'),
            'name' => $code->get('name'),
            'color' => $code->get('ref2'),
            'icon' => $code->get('ref3')
        ];
        return new ContentStatus($data);
    }

}