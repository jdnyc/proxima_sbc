<?php

namespace Api\Services;

use Api\Models\User;
use Api\Models\FolderMng;
use Api\Models\FolderMngRequest;
use Api\Models\FolderMngOwnerUser;

use Api\Services\DTOs\FolderMngOwnerUserDto;

use Api\Services\BaseService;

class FolderMngOwnerUserService extends BaseService
{
    /**
     * 폴더 관리 오너 유저 테이블 추가
     *
     * @param \Api\Models\DTOs\FolderMngOwnerUserDto $data 데이터
     * @return \Api\Models\FolderMngOwnerUser 오너 유저 테이블 객체
     */
    public function create(FolderMngOwnerUserDto $data)
    {
        $folderMngOwnerUser = new FolderMngOwnerUser();
        foreach($data->toArray() as $key => $val){
            $folderMngOwnerUser->$key = $val;
        }
        $folderMngOwnerUser->save();
        return $folderMngOwnerUser;
    }


}