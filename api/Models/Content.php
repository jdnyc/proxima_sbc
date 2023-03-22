<?php
namespace Api\Models;

use Api\Models\BaseModel;
use Api\Types\MediaType;
use Illuminate\Database\Eloquent\Model;

class Content extends BaseModel
{
    protected $table = 'bc_content';

    protected $primaryKey = 'content_id';
    
    protected $guarded = [];

    const CREATED_AT = 'created_date';
    const UPDATED_AT = 'updated_at';

    public $sort = 'content_id';
    public $dir = 'desc';

    public $sortable = ['content_id'];

    protected $casts = [
        'content_id' => 'integer'
    ];

    
    protected $fillable = [
        'reg_user_id',
        'category_id',
        'content_id',
        'category_full_path',
        'bs_content_id',
        'ud_content_id',
        'title',
        'parent_content_id',
        'expired_date',
        'last_modified_date',
        'updated_at',
        'created_date',
        'status'
    ];

    public $logging = false;

    /**
     * 삭제
     * 실제 삭제가 아닌 업데이트로 재정의
     *
     * @param integer $id
     * @return true/false
     */
    public function delete()
    {
        if (is_null($this->getKeyName())) {
            throw new \Exception('No primary key defined on model.');
        }

        if (!$this->exists) {
            return;
        }

        if ($this->fireModelEvent('deleting') === false) {
            return false;
        }

        //$this->touchOwners(); and $this->performDeleteOnModel();
        $this->is_deleted = 'Y';
        $this->last_modified_date = date('YmdHis');
        $this->updated_at = date('YmdHis');
        $this->save();

        $this->exists = false;

        //이벤트 처리
        $this->fireModelEvent('deleted', false);
        return true;
    }

    public function sysMeta()
    {
        return $this->hasOne(\Api\Models\ContentSysMeta::class ,'sys_content_id' ,'content_id' );
    }
    public function statusMeta()
    {
        return $this->hasOne(\Api\Models\ContentStatus::class ,'content_id' ,'content_id' );
    }
    public function usrMeta()
    {
        return $this->hasOne(\Api\Models\ContentUsrMeta::class ,'usr_content_id' ,'content_id' );
    }

    /**
     * 사용자 정의 콘텐츠
     *
     * @return \Api\Models\UserContent
     */
    public function userContent()
    {
        return $this->belongsTo(\Api\Models\UserContent::class, 'ud_content_id', 'ud_content_id');
    }

    /**
     * 미디어
     *
     * @return \Api\Models\Media
     */
    public function medias()
    {
        return $this->hasMany(\Api\Models\Media::class, 'content_id', 'content_id');
    }

    /**
     * 프록시 미디어
     *
     * @return \Api\Models\Media
     */
    public function getProxyMedia()
    {
        return $this->medias()
            ->where('media_type', MediaType::PROXY)
            ->first();
    }

    /**
     * SNS 게시물
     *
     * @return \Api\Models\Social\SnsPost
     */
    public function snsPosts()
    {
        return $this->hasMany(\Api\Models\Social\SnsPost::class, 'content_id', 'content_id');
    }
}
