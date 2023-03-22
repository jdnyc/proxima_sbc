<?php

namespace ProximaCustom\core;

require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

use Proxima\core\Request;
use Proxima\core\Session;
use Proxima\core\WebPath;
use Proxima\models\user\User;
use ProximaCustom\core\ViewCustom;
use \Proxima\models\content\Content;
use Proxima\models\content\Thumbnail;
use ProximaCustom\services\CasService;

/**
 * 메타데이터 저장 시 커스터마이징을 위한 클래스
 */
class MetadataManager
{
    /**
     * 저장 버튼 클릭 후 getForm()이 호출 되기 전 수행해야 하는 javascript 로직을 문자열로 반환한다.
     *
     * @return string
     */
    public static function getBeforeSaveJsLogic()
    {
        $scriptPath = '/javascript/beforeMetadataSaveLogic.js';
        $logic = ViewCustom::getScriptData($scriptPath);

        return $logic;
    }

    public static function modifyMetadataJsLogic()
    {
        $scriptPath = '/javascript/modifyMetadataJsLogic.js';
        $logic = ViewCustom::getScriptData($scriptPath);

        return $logic;
    }

    public static function beforeOpenWebUploaderJsLogic()
    {
        $scriptPath = '/javascript/beforeOpenWebUploaderJsLogic.js';
        $logic = ViewCustom::getScriptData($scriptPath);

        return $logic;
    }

    /**
     * 메타데이터 저장 전 값 조작
     *
     * @param string $fieldName
     * @param string $value
     * @return string
     */
    public static function modifyMetaBeforeSave($fieldName, $value)
    {
        $newValue = $value;
        if ($fieldName === 'usr_item_list' && !empty($value)) {
            // 상품 목록에 수정자/수정일시 추가
            $itemList = json_decode($value, true);
            $userSession = Session::get('user');
            $user = User::find($userSession['user_id']);
            $newItemList = [];
            foreach ($itemList as $item) {
                if (empty($item['modNm'])) {
                    $item['modNm'] = $user->get('user_nm');
                }
                if (empty($item['modDtm'])) {
                    $item['modDtm'] = date('YmdHis');
                }
                $newItemList[] = $item;
            }
            $newValue = json_encode($newItemList);
            Request::setPost('usr_item_list', $newValue);
        }
        if ($fieldName === 'usr_channel_code') {
            $service = new CasService();
            $channel = $service->getChannel($value);
            Request::setPost('usr_channel_name', $channel['name']);
        }
    }
    
    /**
     * 메타데이터 저장 후 액션...
     *
     * @param mixed $contentId
     * @return void
     */
    public static function actionAfterSaveMetadata($contentId)
    {
        $postItem = Request::post('usr_item_list');
        if ($postItem != null) {
            $items = json_decode($postItem, true);
            \ProximaCustom\models\Item::saveItems($contentId, $items);
        }

        $content = Content::find($contentId);

        // 기본이미지 변경
        $thumb = Thumbnail::findByContentId($contentId);
        $userMeta = $content->userMetadata();
        $posterUrl = $userMeta->get('usr_poster');
        if (!empty($posterUrl)) {
            $posterSubUrl = WebPath::removeCdnRootPath($posterUrl);
            if ($posterSubUrl !== $thumb->get('url')) {
                $thumb->set('url', $posterSubUrl);
                $thumb->save();
            }
        }
        
        // CAS 동기화
        $casService = new CasService();
        $result = $casService->syncContent($content);
        return $result;
    }
    /**
     * 기본 코딩되어있던 컬럼들 
     *
     * @return void
     */
    public static function metadataColumns(){
        $columns=[];
        // /*new Ext.grid.CheckboxSelectionModel()*/
        // $columns[]="new Ext.grid.RowNumberer({
        //     width:50,
        //     id:'numberer'
        // })\n";
        $columns['numberer'] = "{header:'', width:50, id:'numberer', constructor : function(config){Ext.apply(this, config);if(this.rowspan){this.renderer = this.renderer.createDelegate(this);}},fixed:true,hideable: false,menuDisabled:true,dataIndex: '',id: 'numberer',rowspan: undefined,renderer : function(v, p, record, rowIndex){if(this.rowspan){p.cellAttr = 'rowspan=\"'+this.rowspan+'\"';}return rowIndex+1;}}\n";
        // /*thumb*/
        $columns['thumb']="{header:'썸네일', id:'thumb', sortable:false, width:80, menuDisabled:true, renderer: function(value,meta,record){
                var thumb = record.get('thumb');
                var thumbWebRoot = record.get('thumb_web_root');
                var contentId = record.get('content_id');
                return '<img id=\"list-'+ contentId + '\" src=\"' + thumbWebRoot+'/'+thumb + '\" onerror=\"fallbackImg(this)\" onload=\"resizeImg(this, {w:80, h:60})\" align=\"center\" style=\" vertical-align:middle\"/>';
            }
        }\n";
        // /*icons*/
        $columns['icons_grid'] = "{header: _text('MN02325'), id:'icons_grid', dataIndex: 'icons_grid', width: 90, sortable: false,menuDisabled: true}\n";
        // /*title*/
        $columns['title'] = "{header: _text('MN00249'), id: 'title', dataIndex: 'title', width: 400,menuDisabled: true,
            renderer: function(value, metaData, record, rowIndex, colIndex, store) {
                 metaData.css += 'column_content_data';
                 return value;
            }
        }\n";
        // /*category*/
        $columns['category_title'] = "{header: _text('MN00387'), id: 'category_title', dataIndex: 'category_title', width:200, menuDisabled: true,
            renderer: function(value, metaData, record, rowIndex, colIndex, store) {
                 metaData.css += 'column_content_data';
                 return value;
            }
        }\n";
        // /*콘텐츠 상태*/
        $columns['content_status_nm'] = "{header: _text('MN02053'), id: 'content_status_nm', dataIndex: 'content_status_nm', width:90,hidden: true, menuDisabled: true,align: 'center',
            renderer: function(value, metaData, record, rowIndex, colIndex, store) {
                 metaData.css += 'column_content_data';
                 return value;
            }
        }\n";
        return $columns;
    }
    public static function customMetadataColumns(){
        $columns=[];
        // 사용자 요구에 따라 필드목록과 순서 고정 2011-02-25 by 이성용
        $columns['created_date'] = "{header: '"._text('MN00102')."', id: 'created_date',dataIndex: 'created_date', width:120 , menuDisabled: true, renderer: Ext.util.Format.dateRenderer('Y-m-d'),renderer: function(value, metaData, record, rowIndex, colIndex, store) {metaData.css += 'column_content_data';return Ext.util.Format.date(value, 'Y-m-d');} }\n";
        $columns['reg_user_nm'] = "{header: '"._text('MN00120')."', id: 'reg_user_nm', dataIndex: 'reg_user_nm', width:70, menuDisabled: true, renderer: function(value, metaData, record, rowIndex, colIndex, store) {metaData.css += 'column_content_data';return value;}}\n";
        return $columns;
    }
    /**
    * 메타데이터 순서 고정
    * 2019.04.22 Alex
    * @param mixed $usrMetadatas, $sysMetadatas
    * @return array
    */
    public static function makeMetadataShowOrder($contentDatas, $usrMetadatas, $sysMetadatas)
    {   
        /* 순번 정보 배열 추후에는 사용자별로 처리 될듯 */
        $sort_fields = array(
            'media_id',
            'title',
            'progrm_nm',
            'cn',
            'category_title',
            'watgrad',
            'cast',
            'dirctr',
            'tme_no',
            'subtl',
            'title',
            'created_date',
            'updater_nm',
            'last_modified_date',
            'sys_frame_rate',
            'sys_video_rt',
            'sys_display_size',
            'sys_video_codec',
            'sys_video_bitrate',
            'sys_audio_bitrate',
            'sys_audio_channel'
        );

        $metaFields = array();
        
        if (!empty($contentDatas)) {
            foreach ($contentDatas as $data) {
                $metaFields[$data['header']] = array(
                    'header' => $data['header'],
                    'dataIndex' => strstr($data['dataIndex'], 'usr_item_list_') ? 'usr_item_list' : $data['dataIndex'],
                    'dataIndexSub' => $data['dataIndex'],
                    'field_type' => $data['filed_type']
                );
            }
        }
        
        if (!empty($usrMetadatas)) {
            foreach ($usrMetadatas as $data) {
                // $usr_field_code = strtolower('USR_'.$data['usr_meta_field_code']);
                $usr_field_code = strtolower($data['usr_meta_field_code']);
                $metaFields[$usr_field_code] = array(
                    'header' => $data['usr_meta_field_title'],
                    'dataIndex' => $usr_field_code,
                    'field_type' => $data['usr_meta_field_type'],
                    'metadata_type' => 'usrMetadata'
                );
            }
        }

        if (!empty($sysMetadatas)) {
            foreach ($sysMetadatas as $data) {
                $sys_field_code = strtolower('SYS_'.$data['sys_meta_field_code']);
                $metaFields[$sys_field_code] = array(
                    'header' => $data['sys_meta_field_title'],
                    'dataIndex' => $sys_field_code,
                    'field_type' => $data['sys_meta_field_type']
                );
            }
        }
        
        $returnFields = array();
        $idx = 0;
        
        foreach ($metaFields as $meta) {
            $searchIndex = ($meta['dataIndex'] == 'usr_item_list') ? $meta['dataIndexSub'] : $meta['dataIndex'];
            if (in_array($searchIndex, $sort_fields)) {
                // $idx = array_search($searchIndex, $sort_fields);
                if ($meta['field_type'] == 'datefield') {
                    $returnFields[$meta['dataIndex']] = "{header: '" . $meta['header'] . "', dataIndex: '". str_replace(' ', '_', $meta['dataIndex']) ."', renderer: Ext.util.Format.dateRenderer('Y-m-d'), id: '".str_replace(' ', '_', $meta['dataIndex'])."', menuDisabled: true,renderer: function(value, metaData, record, rowIndex, colIndex, store) {metaData.css += 'column_content_data';return Ext.util.Format.date(value, 'Y-m-d');}}\n";
                }else {
                    $returnFields[$meta['dataIndex']] = "{header: '" . $meta['header'] . "', dataIndex: '" . str_replace(' ', '_', $meta['dataIndex']) . "', id: '".str_replace(' ', '_', $meta['dataIndex'])."',menuDisabled: true,renderer: function(value, metaData, record, rowIndex, colIndex, store) {metaData.css += 'column_content_data';return value;}}\n";
                }
            }else{
                if($meta['metadata_type'] === 'usrMetadata'){
                    switch($meta['field_type']){
                        case 'textfield':
                            $width = 130;
                        break;
                        case 'textarea':
                            $width = 230;
                        break;
                        default:
                            $width = 100;
                    };
                    if ($meta['field_type'] == 'datefield') {
                        $returnFields[$meta['dataIndex']] = "{header: '" . $meta['header'] . "', dataIndex: '". str_replace(' ', '_', $meta['dataIndex']) ."', renderer: Ext.util.Format.dateRenderer('Y-m-d'), id: '".str_replace(' ', '_', $meta['dataIndex'])."', menuDisabled: true,renderer: function(value, metaData, record, rowIndex, colIndex, store) {metaData.css += 'column_content_data';return Ext.util.Format.date(value, 'Y-m-d');}}\n";
                    }else{
                        $returnFields[$meta['dataIndex']] = "{header: '" . $meta['header'] . "', dataIndex: '" . str_replace(' ', '_', $meta['dataIndex']) . "',width:".$width.", id: '".str_replace(' ', '_', $meta['dataIndex'])."',menuDisabled: true,renderer: function(value, metaData, record, rowIndex, colIndex, store) {metaData.css += 'column_content_data';return value;}}\n";
                    }
             
                };
            }
            $idx++;
        }
        // ksort($returnFields);
        return $returnFields;
    }
}
