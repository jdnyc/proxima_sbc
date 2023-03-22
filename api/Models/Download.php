<?php

namespace Api\Models;

use Api\Models\LogModel;

/**
 * 다운로드
 * 
 * @property int $id 아이디
 * @property string $title 다운로드 항목 제목
 * @property string $icon 아이콘(Awesome font css)
 * @property string $path 파일경로(루트경로 제외)
 * @property string $description 설명
 * @property boolean $published 게시여부(게시된 항목만 화면에 표시)
 * @property int $show_order 정렬 순서
 * @property \Carbon\Carbon $created_at 생성일시
 * @property \Carbon\Carbon $updated_at 수정일시
 */
class Download extends LogModel
{
    protected $guarded = [];

    protected $casts = [
        'published' => 'boolean',
        'show_order' => 'int'
    ];

    public $logging = false;
       
}
