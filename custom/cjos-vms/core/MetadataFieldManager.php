<?php

namespace ProximaCustom\core;

use Carbon\Carbon;
use ProximaCustom\core\Config;

Config::load();

/**
 * MetadataFieldManager 의 getFieldControl 호출 시 사용하는 모드 enum
 */
abstract class MetadataMode
{
    const Single = 1;
    const BatchPreview = 2;
    const BatchEdit = 3;
    const RegisterForm = 4;
}

abstract class MetaFieldControlOptions
{
    const IgnoreRender = 'ignore';
}

/**
 * 커스텀 css 로드를 위한 클래스
 */
class MetadataFieldManager
{
    const REQUIRED_MARK = "<span class=\'usr_meta_required_filed\'>&nbsp;*&nbsp;</span>";

    public static $metadataFields = [
        [
            'type' => 'date_period',
            'displayText' => 'CJENM VMS 사용기간'
        ],
        [
            'type' => 'channel_code',
            'displayText' => 'CJENM VMS 제작채널'
        ],
        [
            'type' => 'item_list',
            'displayText' => 'CJENM VMS 상품목록'
        ],
        [
            'type' => 'video_keyword',
            'displayText' => 'CJENM VMS 동영상키워드'
        ],
        [
            'type' => 'poster',
            'displayText' => 'CJENM VMS 기본이미지'
        ],
        [
            'type' => 'radio_group',
            'displayText' => 'CJENM VMS 라디오그룹'
        ],
        [
            'type' => 'checkbox_group',
            'displayText' => 'CJENM VMS 체크박스그룹'
        ],
        [
            'type' => 'image_upload',
            'displayText' => 'CJENM VMS 이미지업로드'
        ],
        [
            'type' => 'combo_triple',
            'displayText' => 'CJENM VMS 콘텐츠 유형'
        ]
    ];

    public static function getCustomMetadataFields()
    {
        $metaFields = [];
        foreach (self::$metadataFields as $metadataField) {
            // ['numberfield',	_text('MN00355')]
            $metaFields[] = "['" . $metadataField['type'] . "', '" . $metadataField['displayText'] . "']";
        }
        return ', ' . implode(', ', $metaFields);
    }

    public static function getCustomMetadataFieldNameByTypeCase()
    {
        /*
        case 'compositefield':
            return '합성필드';
        */
        $metaFieldsCase = [];
        foreach (self::$metadataFields as $metadataField) {
            $metaFieldsCase[] = "case '" . $metadataField['type'] . "': return '" . $metadataField['displayText'] . "';";
        }
        return implode("\n", $metaFieldsCase);
    }

    /**
     * 메타데이터 필드 유형으로 메타데이터 필드 찾기
     *
     * @param string $type 필드 타입(broad_datetime, item_list 등)
     * @return array
     */
    public static function findMetaField(string $type): array
    {
        foreach (self::$metadataFields as $metadataField) {
            if ($metadataField['type'] == $type) {
                return $metadataField;
            }
        }

        return [];
    }

    /**
     * 메타데이터 필드 유형에 대한 자바스크립트 컨트롤 문자열 반환
     *
     * @param array $fieldInfo 필드 정보 배열
     * @param string $value 메타데이터 값
     * @param $contentId 콘텐츠 아이디, null 가능하도록 변경. 신규등록 페이지에서 사용하기 때문
     * @param int $mode 필드 컨트롤 생성 모드(MetadataMode::Single : 상세보기, MetadataMode::BatchPreview : 일괄 메타 참조영역, MetadataMode::BatchEdit : 일괄메타 작성 영역)
     * @param array $fields 모든 필드 배열
     * @return string
     */
    public static function getFieldControl(array $fieldInfo, string $value, $contentId = '', int $mode, $fields): string
    {
        $control = '';

        $fieldType = $fieldInfo['usr_meta_field_type'];

        $enabledProperty = '';
        $hiddenProperty = '';
        $readOnlyProperty = '';
        $allowBlankProperty = '';
        $fieldLabelProperty = '';
        $fieldTitleProperty = '';
        $requiredProperty = '';
        $widthProperty = '';

        $extraPropsStr = $fieldInfo['extra_props'] ?? '';

        $extraProps = '';
        if (!empty($extraPropsStr)) {
            $extraProps = json_decode($extraPropsStr, true);
            if (isset($extraProps['width'])) {
                if (strpos($extraProps['width'], '%') !== false) {
                    $anchor = $extraProps['width'];
                    $widthProperty = "anchor: '$anchor',\n";
                } else {
                    $widthProperty = "width: {$extraProps['width']},\n\tanchor: '',\n";
                }
            }
        }

        $isRequired = $fieldInfo['is_required'];
        $requiredMark = "";
        if ($isRequired == 1) {
            $requiredMark = self::REQUIRED_MARK;
        }
        $fieldTitle = addslashes($fieldInfo['usr_meta_field_title']);
        $labelText    = $fieldTitle . $requiredMark;

        $metaFieldNameOrg = strtolower($fieldInfo['usr_meta_field_code']);
        $metaFieldName = $metaFieldNameOrg;

        if ($fieldInfo['is_editable'] == 0) {
            $readOnlyProperty = "readOnly: true,\n";
            $enabledProperty = "disabled: true,\n";
        }

        if ($fieldInfo['is_show'] == 0) {
            $hiddenProperty = "hidden: true,\n";
        }

        if ($isRequired == 1) {
            $allowBlankProperty = "allowBlank: false,\n";
            $requiredProperty = "required: true,\n";
        } else {
            $requiredProperty = "required: false,\n";
        }

        // 일괄 수정 오른쪽 작성 영역 체크박스 레이블
        $hiddenField = '';

        $fieldTitleProperty = "fieldTitle: '{$fieldTitle}',\n";

        $fieldLabelProperty = "fieldLabel: '{$labelText}',\n";
        if ($mode == MetadataMode::Single) {
            // 상세조회 일때
            $hiddenFieldName = 'usr_' . $metaFieldNameOrg;
            $hiddenField = "{
                            xtype: 'hidden', 
                            id: '{$hiddenFieldName}', 
                            name: '{$hiddenFieldName}', 
                            value: ''
                        },\n";
        } elseif ($mode == MetadataMode::RegisterForm) {
            // 등록모드일때 무시할 메타
            // 동영상 코드, 기본이미지, 동영상 비율
            $ignoreMetaFieldNames = [
                'video_code', 'poster', 'aspect_ratio', 'dynamic_thumb'
            ];
            if (in_array($metaFieldNameOrg, $ignoreMetaFieldNames)) {
                return MetaFieldControlOptions::IgnoreRender;
            }
        }

        // 커스텀 메타가 아니면 그냥 리턴하여 원래 동적메타데이터에 대한 로직을 수행하게 한다.
        if (empty(self::findMetaField($fieldType))) {
            return '';
        }

        // 사용기간
        if ($fieldType == 'date_period') {
            //초기값은 오늘로
            $fromDate = '';
            $toDate = '';
            if (empty($value)) {
                $fromDate = Carbon::now()->format('Y-m-d');
                $toDate = Carbon::now()->addYears(1)->format('Y-m-d');
            } else {
                $dates = explode('~', $value);
                if (count($dates) === 2) {
                    $fromDate = (new Carbon($dates[0]))->format('Y-m-d');
                    $toDate = (new Carbon($dates[1]))->format('Y-m-d');
                } else {
                    $fromDate = $value;
                    $toDate = $value;
                }
            }

            $control = "{
                xtype: 'compositefield',
                id: 'usr_meta__expire_period',
                name: 'expire_period',
                {$fieldTitleProperty}
                {$enabledProperty}
                {$fieldLabelProperty}
                items: [{
                    xtype: 'datefield',
                    altFormats: 'Ymd',
                    format: 'Y-m-d',
                    name: 'usr_{$metaFieldName}_from',
                    value: '{$fromDate}',
                    {$readOnlyProperty}
                    {$allowBlankProperty}
                    {$hiddenProperty}
                    {$requiredProperty}
                    width:100,
                    validator: function(value) {
                        if(Ext.isEmpty(value)) {
                            return '사용기간 시작일을 설정후 저장해주세요.'
                        }
                        return true;
                    }
                }, {
                    xtype: 'label',
                    style: {
                      marginTop: '5px'
                    },
                    text: '~'
                }, {
                    xtype: 'datefield',
                    altFormats: 'Ymd',
                    format: 'Y-m-d',
                    name: 'usr_{$metaFieldName}_to',
                    value: '{$toDate}',
                    {$readOnlyProperty}
                    {$allowBlankProperty}
                    {$hiddenProperty}
                    {$requiredProperty}
                    width:100
                }]
            }";
        }

        // 채널코드
        if ($fieldType == 'channel_code') {
            $pgmCodeField = self::findMetaFieldInfo($fields, 'pgm_code');
            $pgmCode = $pgmCodeField['value'] ?? '';
            $pgmNameField = self::findMetaFieldInfo($fields, 'pgm_name');
            $pgmName = $pgmNameField['value'] ?? '';

            $pgmGroupCodeField = self::findMetaFieldInfo($fields, 'pgm_group_code');
            $pgmGroupCode = $pgmGroupCodeField['value'] ?? '';
            $pgmGroupNameField = self::findMetaFieldInfo($fields, 'pgm_group_name');
            $pgmGroupName = $pgmGroupNameField['value'] ?? '';
            $control = "{
                id: 'usr_meta__channel_code',
                xtype: 'c-channel-field',
                relatedControlIds: [
                    'usr_meta__broad_dtm',
                    'usr_meta__showhost',
                    'usr_meta__guest',
                    'usr_meta__pd'
                ],
                channelCodeName: 'usr_channel_code',
                pgmCodeName: 'usr_pgm_code',
                pgmNameName: 'usr_pgm_name',
                {$enabledProperty}
                {$fieldLabelProperty}
                {$allowBlankProperty}
                {$requiredProperty}
                {$fieldTitleProperty}
                displayPgmGroupInfo: true,
                listeners: {
                    afterrender: function(self) {
                        var channelCode = '{$value}';
                        var pgmCode = '{$pgmCode}';
                        var pgmName = '{$pgmName}';

                        var pgmGroupCode = '{$pgmGroupCode}';
                        var pgmGroupName = '{$pgmGroupName}';
                        if(Ext.isEmpty(channelCode)) {
                            return;
                        }
                        self.setChannel(channelCode);
                        self.setPgmValue(pgmCode, pgmName);
                        self.setPgmGroupValue(pgmGroupCode, pgmGroupName);
                    },
                    afterpgmsearch: function(self, pgmRecord) {
                        //console.log('afterpgmsearch', pgmRecord);
                        var channelCode = pgmRecord.get('channel_code');
                        self.setChannel(channelCode);
                        Ext.getCmp('usr_meta__broad_dtm').setValue(pgmRecord.get('bdStDtm'));
                        Ext.getCmp('usr_meta__showhost').setValue(pgmRecord.get('showHostInfo'));
                        //Ext.getCmp('usr_meta__guest').setValue(pgmRecord.get('guestInfo'));
                        Ext.getCmp('usr_meta__pd').setValue(pgmRecord.get('pdInfo'));

                        Ext.getCmp('usr_meta__item_list').addItems(pgmRecord.selectedItems);

                        // 첫번째 키워드로 추가
                        var keywordControl = Ext.getCmp('usr_meta__keyword');
                        var keywords = keywordControl.getValue();
                        // 쇼크라이브면 프로그램 그룹명을 넣어야 한다.
                        var tag = channelCode === 'CJSL' ? pgmRecord.get('pgmGrpNm') : pgmRecord.get('pgmNm');                        
                        keywordControl.insertTag(0, tag);

                    }
                },
                createSearchWindow: function() {
                    return new Custom.PgmItemSelectWindow();
                },
                // 방송채널이 아니면 방송일시, 쇼호스트, 출연자 항목 숨김
                onSelect: function(record) {
                    // record : id, code, name, broadcast
                    var isBroadcastChannel = record.get('broadcast');
                    var channelCode = record.get('code');
                    // console.log(isBroadcastChannel);
                    Ext.each(this.relatedControlIds, function(id) {
                        var control = Ext.getCmp(id);
                        
                        //console.log(control);
                        //console.log(isBroadcastChannel);
                        if(!control) {
                            return;
                        }
                        if(isBroadcastChannel) {
                            // CJOL/CJOP 면 쇼호스트, 출연자, 방송일시만 보이고
                            // CJSL면 PD만 보여야 함
                            /*
                            'usr_meta__broad_dtm',
                            'usr_meta__showhost',
                            'usr_meta__guest',
                            'usr_meta__pd'
                            */
                            if(channelCode === 'CJOL' || channelCode === 'CJOP') {
                                if(id === 'usr_meta__broad_dtm' ||
                                    id === 'usr_meta__showhost' ||
                                    id === 'usr_meta__guest') {
                                    control.show();
                                    return;
                                }
                            } else if(channelCode === 'CJSL') {
                                if(id === 'usr_meta__broad_dtm' ||
                                    id === 'usr_meta__pd') {
                                    control.show();
                                    return;
                                }
                            }

                            control.hide();
                        } else {
                            control.hide();
                        }
                    });
                    
                }
            }";
        }

        // 상품목록
        if ($fieldType == 'item_list') {
            $control = "{
                id: 'usr_meta__item_list',
                xtype: 'c-item-match-list-grid',
                {$requiredProperty}
                {$fieldTitleProperty}
                toolbarTitle: '상품매칭 및 동영상 카테고리 설정{$requiredMark}',
                style: {
                    marginBottom: '5px'
                },
                // 대표 상품 지정 시 카테고리 자동입력
                onSetRepItem: function(record) {
                    console.log('onSetRepItem record : ', record);
                    if(Ext.isEmpty(record)) {
                        return;
                    }
                    var videoCategoryField = Ext.getCmp('usr_meta__video_category');
                    if(videoCategoryField) {
                        videoCategoryField.setValue(record.get('dpCateNm'));
                    }
                    var videoCategoryIdField = Ext.getCmp('usr_meta__video_category_id');
                    if(videoCategoryIdField) {
                        videoCategoryIdField.setValue(record.get('dpCateId'));
                    }
                },
                initialData: '{$value}'
            }";
        }

        // 동영상 키워드
        if ($fieldType == 'video_keyword') {
            $control = "{
                id: 'usr_meta__keyword',
                xtype: 'c-tag-panel',
                fieldLabel: '동영상 키워드',
                {$requiredProperty}
                {$fieldTitleProperty}
                toolbarTitle: '동영상 키워드는 최대 10개까지 설정 가능합니다. 키워드는 띄어쓰기 없이 작성해주세요.',
                maxTagLenght: 12,
                maxTagCount: 10,
                style: {
                    marginBottom: '5px'
                },
                listeners: {
                    afterrender: function(self) {
                        var keywordsStr = '{$value}';
                        if(Ext.isEmpty(keywordsStr)) {
                            return;
                        }
                        var keywords = Ext.decode(keywordsStr);
                        self.setValue(keywords);
                    }
                }
            }";
        }

        // 기본 이미지
        if ($fieldType == 'poster') {
            $conentIdValue = empty($contentId) ? 'null' : $contentId;
            $control = "{
                id: 'usr_meta__poster',
                xtype: 'c-poster-panel',
                name: 'usr_{$metaFieldName}',
                {$requiredProperty}
                {$fieldTitleProperty}
                fieldLabel: '기본 이미지',
                contentId: {$conentIdValue}
            }";
        }

        // 라디오 그룹
        if ($fieldType == 'radio_group') {
            $radioGroupItems = self::getRadioGroupItems($fieldInfo, $value);
            $itemListJson = json_encode($radioGroupItems);
            $itemListProperty = "itemList: '{$itemListJson}'";

            $defaultColumnWidthProperty = '';
            if (!empty($extraProps)) {
                $defaultColumnWidth = $extraProps['column_width'] ?? null;
                if ($defaultColumnWidth) {
                    $defaultColumnWidthProperty = "defaultColumnWidth: {$defaultColumnWidth},\n";
                }
            }
            $control = "{
                xtype: 'c-radiogroup',
                cls: 'dark-checkbox-group',
                {$widthProperty}
                {$fieldLabelProperty}
                {$allowBlankProperty}
                {$defaultColumnWidthProperty}
                {$fieldTitleProperty}
                {$itemListProperty}
            }";
        }

        // 체크박스 그룹
        if ($fieldType == 'checkbox_group') {
            $radioGroupItems = self::getCheckboxGroupItems($fieldInfo, $value);
            $itemListJson = json_encode($radioGroupItems);
            $itemListProperty = "itemList: '{$itemListJson}'";

            $defaultColumnWidthProperty = '';
            $totalItemNameProperty = '';
            $totalItemNameProperty = '';
            if (!empty($extraProps)) {
                $maxCheckCount = $extraProps['max_check_count'] ?? 0;
                // 최대 체크 수
                $maxCheckCountProperty = "maxCheckCount: {$maxCheckCount},\n";
                // 전체 체크 아이템 명(CJOS-VMS 커스터마이징)
                $totalItemName = $extraProps['total_item_name'] ?? '';
                $totalItemNameProperty = "totalItemName: '{$totalItemName}',\n";
                $defaultColumnWidth = $extraProps['column_width'] ?? null;
                if ($defaultColumnWidth) {
                    $defaultColumnWidthProperty = "defaultColumnWidth: {$defaultColumnWidth},\n";
                }
            } else {
                $maxCheckCountProperty = "maxCheckCount: 0,\n";
                $totalItemNameProperty = "totalItemName: '',\n";
            }

            $control = "{
                xtype: 'c-checkboxgroup', 
                cls: 'dark-checkbox-group',
                fieldCode: 'usr_{$metaFieldName}',
                {$widthProperty}
                {$maxCheckCountProperty}
                {$allowBlankProperty}
                {$defaultColumnWidthProperty}
                {$totalItemNameProperty}
                {$fieldLabelProperty}
                {$fieldTitleProperty}
                {$itemListProperty}
            }";
        }

        // 동적 이미지
        if ($fieldType == 'image_upload') {
            $conentIdValue = empty($contentId) ? 'null' : $contentId;
            $control = "{
                xtype: 'c-imgupload-field',
                {$fieldLabelProperty}
                value: '{$value}',
                valueFieldName: 'usr_{$metaFieldName}',
                contentId: {$conentIdValue}
            }";
        }

        // CJENM VMS 콘텐츠 유형
        if ($fieldType == 'combo_triple') {
            $storeProperty = "comboStore: [" . self::getFieldDefaultValue($fieldInfo['default_value']) . "],\n";
            $control = "{
                xtype: 'c-combo-triple',
                fieldCode: 'k_{$metaFieldName}',
                {$storeProperty}
                {$fieldLabelProperty}
                {$fieldTitleProperty}
                value: '{$value}'
            }";
        }

        return $control;
    }

    public static function decorateMetadata($items)
    {
        $decoreatedItems = [];
        foreach ($items as $item) {
            // 제목을 동영상명으로 변경
            if (self::metadataExists($item, 'k_title')) {
                $item = str_replace("fieldTitle: '제목'", "fieldTitle: '동영상명'", $item);
                $item = str_replace("fieldLabel: '제목", "fieldLabel: '동영상명", $item);
                $decoreatedItems[] = $item;
                $item = "{xtype:'label', style: {marginLeft: '105px', color: '#28aeff'}, text: '동영상명은 CJmall 프론트에 노출됩니다. 고객친화적으로 작성해주세요.'}\n";
                $decoreatedItems[] = $item;
                $item = "{xtype: 'spacer', height: 10}";
                $decoreatedItems[] = $item;
                continue;
            }

            // 방송일시
            $item = self::addId($item, 'broad_dtm', true);
            // 쇼호스트
            $item = self::addId($item, 'showhost', true);
            // 출연자
            $item = self::addId($item, 'guest', true);
            // PD
            $item = self::addId($item, 'pd', true);

            // 동영상 카테고리
            $item = self::addId($item, 'video_category', false);
            // 동영상 카테고리 아이디
            $item = self::addId($item, 'video_category_id', true);


            $decoreatedItems[] = $item;
        }
        return $decoreatedItems;
    }

    private static function metadataExists($item, $name)
    {
        return (strpos($item, $name) !== false);
    }

    private static function addId($item, $name, $hidden = false)
    {
        if (self::metadataExists($item, $name)) {
            $hiddenStr = $hidden ? ', hidden: true' : '';
            $item = str_replace("{$name}', ", "{$name}', id: 'usr_meta__{$name}'{$hiddenStr}, ", $item);
        }
        return $item;
    }

    private static function findMetaFieldInfo($fields, $fieldCode)
    {
        if (is_null($fields)) {
            return null;
        }
        foreach ($fields as $f) {
            $fieldCodeTmp = strtolower($f['usr_meta_field_code']);
            if ($fieldCodeTmp === $fieldCode) {
                return $f;
            }
        }
        return null;
    }

    private static function getRadioGroupItems($fieldInfo, $value)
    {
        $defaultValue = $fieldInfo['default_value'];
        $defaultValueArr = explode('(default)', $defaultValue);
        $realDefaultValue = $defaultValueArr[0] ?? '';
        $itemList = explode(';', $defaultValueArr[1] ?? '');

        $metaFieldCode = strtolower($fieldInfo['usr_meta_field_code']);
        $radioGroup = [];
        foreach ($itemList as $item) {
            $radio = [
                'boxLabel' => $item,
                'name' => 'usr_' . $metaFieldCode,
                'inputValue' => $item,
                'style' => 'vertical-align: middle;'
            ];
            if (!empty($value)) {
                if (!empty($value) && $item == $value) {
                    $radio['checked'] = true;
                }
            } else {
                if (!empty($realDefaultValue) && $item == $realDefaultValue) {
                    $radio['checked'] = true;
                }
            }
            $radioGroup[] = $radio;
        }
        return $radioGroup;
    }

    private static function getCheckboxGroupItems($fieldInfo, $value)
    {
        $defaultValue = $fieldInfo['default_value'];
        $defaultValueArr = explode('(default)', $defaultValue);
        $realDefaultValue = $defaultValueArr[0] ?? '';
        $itemList = explode(';', $defaultValueArr[1] ?? '');

        $metaFieldCode = strtolower($fieldInfo['usr_meta_field_code']);
        $checkboxGroup = [];

        $values = explode(',', $value);
        foreach ($itemList as $item) {
            $checkbox = [
                'boxLabel' => $item,
                'name' => 'usr_' . $metaFieldCode,
                'inputValue' => $item,
                'style' => 'vertical-align: middle;'
            ];
            if (!empty($value) && !empty($values)) {
                foreach ($values as $v) {
                    if (!empty($v) && $item == $v) {
                        $checkbox['checked'] = true;
                    }
                }
            } else {
                if (!empty($realDefaultValue) && $item == $realDefaultValue) {
                    $checkbox['checked'] = true;
                }
            }
            $checkboxGroup[] = $checkbox;
        }
        return $checkboxGroup;
    }

    private static function getFieldDefaultValue($default_value)
    {
        list($default, $value_list) = explode('(default)', $default_value);
        $value_list = explode(';', $value_list);

        foreach ($value_list as $value) {
            $result[] = "'$value'";
        }

        return join(',', $result);
    }
}
