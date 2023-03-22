<?php

namespace Api\Models;

use Api\Types\DefinedGroups;

// use DefinedGroups;

class User extends BaseModel
{
    protected $table = 'bc_member';

    protected $primaryKey = 'member_id';

    const CREATED_AT = 'created_date';
    const UPDATED_AT = 'updated_date';

    //date 포맷
    protected $dateFormat = 'YmdHis';

    // 숨김필드
    protected $hidden = [
        'password',
        'ori_password',
        'session_id',
        'check_session',
        'salt_key',
        'crtfc_number_dt'
    ];

    protected $guarded = [];

    // base

    // customized
    /**
     * 영상편집 그룹을 가지고 있는지 여부
     * 0보다 크면 참을 그게 아니면 거짓을 리턴
     *
     * @return boolean
     */
    public function hasEditGroup()
    {
        $count = 0;
        $groups = $this->groups;
        foreach ($groups as $group) {
            if ($group->member_group_id == DefinedGroups::EDIT_GROUP) {
                $count = $count + 1;
            };
        }
        return $count > 0;
    }

    /**
     * 영상편집 그룹을 가지고 있는지 여부
     *
     * @return boolean
     */
    public function hasCgGroup()
    {
        $count = 0;
        $groups = $this->groups;
        foreach ($groups as $group) {
            if ($group->member_group_id == DefinedGroups::CG_GROUP) {
                $count = $count + 1;
            };
        }
        return $count > 0;
    }
    /**
     * 인제스트 그룹을 가지고 있는지 여부
     *
     * @return boolean
     */
    public function hasIngestGroup()
    {
        $count = 0;
        $groups = $this->groups;
        foreach ($groups as $group) {
            if ($group->member_group_id == DefinedGroups::INGEST_GROUP) {
                $count = $count + 1;
            };
        }
        return $count > 0;
    }
    /**
     * 콘텐츠 그룹을 가지고 있는지 여부
     *
     * @return boolean
     */
    public function hasContentGroup()
    {
        $count = 0;
        $groups = $this->groups;
        foreach ($groups as $group) {
            if ($group->member_group_id == DefinedGroups::CONTENT_GROUP) {
                $count = $count + 1;
            };
        }
        return $count > 0;
    }

    /**
     * 아카이브 그룹을 가지고 있는지 여부
     *
     * @return boolean
     */
    public function hasArchiveGroup()
    {
        $count = 0;
        $groups = $this->groups;
        foreach ($groups as $group) {
            if ($group->member_group_id == DefinedGroups::ARCHIVE_GROUP) {
                $count = $count + 1;
            };
        }
        return $count > 0;
    }

    public function delete()
    {
        if ($this->del_yn != bool_to_yn(true)) {
            $this->del_yn = bool_to_yn(true);
            $this->save();
        }
    }

    /**
     * 외부 사용자 여부(external_yn이 Y면 외부, NULL이나 N이면 내부))
     *
     * @return boolean
     */
    public function isExternalUser()
    {
        return ($this->external_yn === bool_to_yn(true));
    }

    public function dataDicTables()
    {
        return $this->hasMany(\Api\Models\DataDicTable::class, 'regist_user_id', 'user_id');
    }

    public function dataDicWords()
    {
        return $this->hasMany(\Api\Models\DataDicWord::class, 'regist_user_id', 'user_id');
    }

    public function dataDicCodeItems()
    {
        return $this->hasMany(\Api\Models\DataDicCodeItem::class, 'regist_user_id', 'user_id');
    }
    public function dataDicCodeSets()
    {
        return $this->hasMany(\Api\Models\DataDicCodeSet::class, 'regist_user_id', 'user_id');
    }
    public function dataDicColumns()
    {
        return $this->hasMany(\Api\Models\DataDicCodeSet::class, 'regist_user_id', 'user_id');
    }
    public function dataDicFields()
    {
        return $this->hasMany(\Api\Models\DataDicField::class, 'regist_user_id', 'user_id');
    }
    public function groups()
    {
        return $this->belongsToMany(\Api\Models\Group::class, 'bc_member_group_member', 'member_id', 'member_group_id', 'member_id', 'member_group_id');
    }

    public function folderMngs()
    {
        return $this->belongsToMany(\Api\Models\FolderMng::class, 'folder_mng_user', 'user_id', 'folder_id', 'user_id', 'id');
    }

    public function option()
    {
        return $this->hasOne(\Api\Models\UserOption::class, 'member_id', 'member_id');
    }

    public function hasAdminGroup()
    {
        return $this->groups()
            ->where('is_admin', \bool_to_yn('Y'))
            ->exists();
    }
}
