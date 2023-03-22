<?php

namespace Api\Services;

use Api\Models\User;
use Api\Models\FolderMng;
use Api\Models\FolderMngUser;
use Api\Models\FolderMngOwnerUser;
use Api\Services\BaseService;
use Api\Services\DTOs\FolderMngDto;
use Illuminate\Database\Capsule\Manager as DB;

class FolderMngService extends BaseService
{
    public function list($params, $user)
    {
        $userId = $user->user_id;
        $start = 1;
        if(isset($params['start'])) {
            $start = $params['start'];
        }
        $query = FolderMng::with('parent');
        $query->with('owners');
        $query->with('users');
        // 담당자 표시에 사용...
        $query->with('ownerInfo');

        if (!empty($params['folder_path'])) {
            $value = $params['folder_path'];
            $query->where('folder_path', 'like', "%{$value}%");
        } else if (!empty($params['folder_path_nm'])) {
            $value = $params['folder_path_nm'];
            $query->where('folder_path_nm', 'like', "%{$value}%");
        } else if (!empty($params['user_query'])) {
            $userIds = $params['userIds'];

            $query->whereHas('userInfos', function ($q) use ($userIds) {
                $q->whereIn('FOLDER_MNG_USER.user_id', $userIds);
            });
        }

        if (!empty($params['parent_id'])) {
            $query->where('parent_id', '=', $params['parent_id']);
        }

        if (!empty($params['using'])) {
            $query->where('using_yn', '=', $params['using']);
        }
        
        if(!empty($params['search_date_field'])){
            $startDate = dateToStr($params['start_date'], 'YmdHis');
            $endDate = dateToStr($params['end_date'], 'YmdHis');
            
            $query->whereBetween($params['search_date_field'], [$startDate,$endDate]);
        };
        
       

        $show = $params['show'] ?? 0;
        if($show !== 0) {
            return [];
        }             
        // 로그인한 사용자가 오너 유저 또는 맵핑 유저인 리스트만
        $isMyList = $params['my_list'] ?? 0;
        // 홈화면에선 isMyList 1 , 프로그램>스크래치 폴더 관리에선 0

        if ($isMyList) {
            $query->where(function($q) use ($userId) {
                $mngTable = FolderMngUser::where('user_id',$userId)->select('folder_id');
                $q->where("regist_user_id", $userId)
                ->orWhereIn("id", FolderMngOwnerUser::where('user_id',$userId)->select('folder_id')->union($mngTable));
         });
        }
        $query->orderBy('folder_mng.id','desc');
        
        $lists = paginate($query);

        return $lists;
    }


    /**
     * 생성
     *
     * @param \Api\Services\DTOs\FolderMngDto $data 생성 데이터
     * @param \Api\Models\User $user 사용자 객체
     * @return \Api\Models\FolderMng 생성된 테이블 객체
     */
    public function create(FolderMngDto $dto, User $user)
    {
        $folder = $this->createFromData($dto->toArray(), $user);
        return $folder;
    }

    /**
     * 배열로 생성
     *
     * @param array $data 생성 데이터
     * @param \Api\Models\User $user 사용자 객체
     * @return \Api\Models\FolderMng 생성된 테이블 객체
     */
    public function createFromData(array $data, User $user)
    {
        
        $folder = new FolderMng();
        foreach($data as $key => $val) {
            if (!($key == "root")) {
                $folder->$key = $val;
            };
        }
        $folder->regist_user_id = $user->user_id;
        $folder->updt_user_id = $user->user_id;
        $folder->save();
        return $folder;
    }

    /**
     * 수정
     * 
     * @param integer 수정할 코드아이템 아이디
     * @param \Api\Services\DTOs\DataDicCodeItemDto $dto 테이블 수정 데이터
     * @param \Api\Models\User $user 사용자 객체
     * @return \Api\Models\DataDicCodeItem 수정된 테이블 객체
     */
    public function update(int $id, FolderMngDto $dto, User $user)
    {
        $folder = $this->findOrFail($id);

        foreach ($dto->toArray() as $key => $val) {
            if (!($key == "root")) {
                $folder->$key = $val;
            };
        }
        $folder->updt_user_id = $user->user_id;
        $folder->save();

        return $folder;
    }

    /**
     * 삭제
     *
     * @param int $id ID
     * @return bool|null 삭제 성공여부
     */
    public function delete(int $id, User $user)
    {
        $collection = $this->findOrFail($id);
        $collection->updt_user_id = $user->user_id;
        $ret = $collection->delete();
        return $ret;
    }

    /**
     * 복원
     *
     * @param integer $id 아이디
     * @param User $user
     * @return bool|null 복원 성공여부
     */
    public function restore(int $id)
    {
        $folder = FolderMng::onlyTrashed()
            ->where('id', $id)
            ->first();
        if (!$folder) {
            api_abort_404('FolderMng');
        }
        $ret = $folder->restore();
        return $ret;
    }
    /**
     * 상세 조회 또는 $실패 처리
     *
     * @param integer $id
     * @return collection
     */
    public function findOrFail($id)
    {
        $folder = $this->find($id);
        if (!$folder) {
            api_abort_404('FolderMng');
        }
        return $folder;
    }

    public function find($id)
    {
        $query = FolderMng::query();
        return $query->find($id);
    }

    public function getByPgmId($pgmId)
    {
        $query = FolderMng::query();
        $query->where('pgm_id', '=', $pgmId);
        $folderMng = $query->first();
        return  $folderMng;
    }

    /**
     * 폴더목록에 매핑된 사용자 조회
     *
     * @param [type] $folderId
     * @return void
     */
    public function findByWithUser($folderId)
    {

        $query = User::query();
        $query->where('DEL_YN', '=', "N");
        $query->join('FOLDER_MNG_USER', 'BC_MEMBER.USER_ID', '=', 'FOLDER_MNG_USER.USER_ID');
        $query->whereRaw('FOLDER_MNG_USER.FOLDER_ID=?', $folderId);
        $query->select('BC_MEMBER.USER_ID', 'MEMBER_ID', 'USER_NM', 'DEPT_NM');

        $list = $query->get();
        return $list;
    }

    /**
     * 폴더목록에 사용자 매핑 function
     *
     * @param [type] $folder_id
     * @param [type] $mapUsers
     * @param [type] $user
     * @return void
     */
    public function saveUser($folder_id, $mapUsers, $user)
    {

        $bfUsers = FolderMngUser::where("folder_id", '=', $folder_id)->get()->all();
        //  dump($bfUsers->toArray());
        //dump($bfUsers->count());

        if (count($mapUsers) == 0) {
            //모두 삭제

            if (count($bfUsers) > 0) {
                foreach ($bfUsers as $bfUser) {
                    $folder = FolderMngUser::where('folder_id', '=', $folder_id)
                        ->where('user_id', '=', $bfUser->user_id)
                        ->first();
                    $folder->delete();
                }
            }
        } else {
            if (count($mapUsers) == 0) {
                //모두 추가
                foreach ($mapUsers as $newUserId) {
                    $folder = FolderMngUser::where('folder_id', '=', $folder_id)
                        ->where('user_id', '=', $newUserId)
                        ->first();
                        if (empty($folder)) {
                            $folder = new FolderMngUser();
                            $folder->folder_id = $folder_id;
                            $folder->user_id = $newUserId;
                            $folder->save();
                        }
                }
            } else {
                //비교
                //추가
                foreach ($mapUsers as $newUserId) {
                    $isExist = false;
                    foreach ($bfUsers as $bfUser) {
                        if ($newUserId == $bfUser->user_id) {
                            $isExist = true;
                        }
                    }
                    if (!$isExist) {
                        //추가
                        //$newUserId
                        $folder = FolderMngUser::where('folder_id', '=', $folder_id)
                        ->where('user_id', '=', $newUserId)
                        ->first();
                        if (empty($folder)) {
                            $folder = new FolderMngUser();
                            $folder->folder_id = $folder_id;
                            $folder->user_id = $newUserId;
                            $folder->save();
                        }
                    }
                }

                //삭제
                foreach ($bfUsers as $bfUser) {
                    $isExist = false;
                    foreach ($mapUsers as $newUserId) {
                        if ($newUserId == $bfUser->user_id) {
                            $isExist = true;
                        }
                    }
                    if (!$isExist) {
                        //삭제
                        $folder = FolderMngUser::where('folder_id', '=', $folder_id)
                            ->where('user_id', '=', $bfUser->user_id)
                            ->first();
                        $folder->delete();
                    }
                }
            }
        }
        return true;
    }

    /**
     * SNFS 쿼터와 동기화 테이블 function
     *
     * @return void
     */
    public function sync()
    {
        $query = FolderMng::query();
        $list = $query->get();
        return $list;
    }

    public function getFullPath($id)
    {
        $curId = $id;
        $curPath = [];
        while ($curId > 0) {
            $col = $this->find($curId);
            if (!empty($col->folder_path)) {
                array_unshift($curPath, $col->folder_path);
            }
            $curId = $col->parent_id;
        }
        return join('/', $curPath);
    }

    public function folderIndex()
    {
        $count = $this->getSequence('FOLDER_PROGRAM_SEQ');
        // $query = FolderMng::query();
        // $query->where('folder_path', 'like', 'program_%');
        // $count = $query->count();
        return $count;
    }

    public function getMyFolder(){
        $query = FolderMng::query();
        $query->where('using_yn', 'Y');
//         SELECT 
// c.*,m.PGM_ID,m.DVS_YN,m.USE_YN,m.QUOTA,m.CURSIZE
// FROM bc_category c 
// JOIN FOLDER_MNG m ON (c.category_id=m.CATEGORY_ID)
// LEFT OUTER JOIN FOLDER_MNG_USER u ON (m.id=u.FOLDER_ID)
// LEFT OUTER JOIN FOLDER_MNG_OWNER_USER ou ON (m.id=ou.FOLDER_ID)
// WHERE c.PARENT_ID=200 
// AND m.using_yn='Y' 
// AND (u.USER_ID='ohh' OR ou.USER_ID ='ohh')
// ORDER BY SHOW_ORDER;
    }
    public function getFolderByPgmId($pgmId){
        $query = FolderMng::query();
        $query->where('using_yn', 'Y');
        $query->where('PARENT_ID' ,'2');
        $query->where('pgm_id', $pgmId);
        $folder = $query->first();
        return $folder;
    }
}
