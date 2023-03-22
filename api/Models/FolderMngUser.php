<?php
namespace Api\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Api\Models\BaseModel;

class FolderMngUser extends BaseModel
{
    protected $table = 'folder_mng_user';

    protected $primaryKey = ['folder_id', 'user_id'];
    public $incrementing = false;
    const CREATED_AT = 'created_at';//'regist_dt';
    const UPDATED_AT = 'updated_at';//'updt_dt';
    const DELETED_AT = null;//'delete_dt';';
    
    protected $guarded = [];

    protected $casts = [
    ];

     /**
     * 사용자
     *
     * @return \Api\Models\User
     */
    public function user()
    {
        return $this->belongsTo(\Api\Models\User::class, 'user_id', 'user_id');
    }
     /**
     * 폴더 관리
     *
     * @return \Api\Models\User
     */
    public function folders()
    {
        return $this->belongsTo(\Api\Models\FolderMng::class, 'folder_id', 'id');
    }

    /**
     * Set the keys for a save update query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function setKeysForSaveQuery(Builder $query)
    {
        $keys = $this->getKeyName();
        if(!is_array($keys)){
            return parent::setKeysForSaveQuery($query);
        }

        foreach($keys as $keyName){
            $query->where($keyName, '=', $this->getKeyForSaveQuery($keyName));
        }

        return $query;
    }
    /**
     * Get the primary key value for a save query.
     *
     * @param mixed $keyName
     * @return mixed
     */
    protected function getKeyForSaveQuery($keyName = null)
    {
        if(is_null($keyName)){
            $keyName = $this->getKeyName();
        }

        if (isset($this->original[$keyName])) {
            return $this->original[$keyName];
        }

        return $this->getAttribute($keyName);
    }
}
