<?php

namespace ProximaCustom\models;

use Proxima\core\WebPath;
use Proxima\core\ModelBase;

/**
 * 상품
 */
class Item extends ModelBase
{
    public static $table = 'items';

    /**
     * 아이디로 조회
     *
     * @param mixed $id
     * @return \ProximaCustom\models\Item
     */
    public static function find($id)
    {
        $query = "SELECT * FROM " . self::$table . " WHERE id = {$id}";
        
        $item = ModelBase::queryObject($query, Item::class);

        return $item;
    }

    /**
     * 코드로 조회
     *
     * @param mixed $code
     * @return \ProximaCustom\models\Item
     */
    public static function findByCode($code)
    {
        $query = "SELECT * FROM " . self::$table . " WHERE code = '{$code}'";
        
        $items = ModelBase::queryCollection($query, Item::class);

        return $items;
    }

    /**
     * 콘텐츠 아이디로 조회
     *
     * @param mixed $contentId
     * @return array \ProximaCustom\models\Item 객체 배열
     */
    public static function findByContentId($contentId)
    {
        $query = "SELECT * FROM " . self::$table . " WHERE content_id = {$contentId}";
        
        $items = ModelBase::queryCollection($query, Item::class);

        return $items;
    }

    /**
     * 아이템 삽입
     *
     * @param string $contentId
     * @param array $items
     * @return void
     */
    public static function saveItems($contentId, $items)
    {
        $where = "content_id={$contentId}";
        self::delete(self::$table, $where);

        foreach ($items as $item) {
            // http: 제거하고 저장
            $imageUrl = str_replace(WebPath::getDefaultProtocol(), '', $item['imageUrl']);
            $data = [
                'code' => $item['itemCd'],
                'name' => $item['itemNm'],
                'rep_item_yn' => $item['repItemYn'],
                'chn_cd' => $item['chnCd'],
                'sl_cls' => $item['slCls'],
                'disp_order' => $item['dispOrder'],
                'disp_yn' => $item['dispYn'],
                'mod_nm' => $item['modNm'],
                'mod_dtm' => $item['modDtm'],
                'image_url' => $imageUrl,
                'dp_cate_id' => $item['dpCateId'],
                'dp_cate_nm' => $item['dpCateNm'],
                'content_id' => $contentId
            ];
            self::insert('items', $data);
        }
    }
}
