<?php

namespace Api\Controllers;

set_time_limit(180);

use Api\Http\ApiRequest;
use Api\Models\Category;
use Api\Http\ApiResponse;
use Api\Services\UserService;
use Api\Services\SysCodeService;
use Api\Services\CategoryService;
use Api\Services\DTOs\CategoryDto;

use Api\Services\FolderMngService;
use Api\Controllers\BaseController;
use Api\Services\DTOs\FolderMngDto;
use Psr\Container\ContainerInterface;
use ProximaCustom\core\FolderAuthManager;

class FolderMngController extends BaseController
{
    /**
     * 접근 권한 서비스
     *
     * @var \Api\Services\FolderMngService
     */
    private $folderMngService;
    private $userService;
    private $sysCodeService;
    private $categoryService;



    /**
     * 생성자는 필요할때만 정의하면 됨...
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->folderMngService = new FolderMngService($container);
        $this->userService = new UserService($container);
        $this->sysCodeService = new SysCodeService($container);
        $this->categoryService = new CategoryService($container);
    }

    /**
     * 홈화면 스토리지 쿼터 조회
     *
     * @param \Api\Http\ApiRequest $request
     * @param \Api\Http\ApiResponse $response
     * @param array $args
     * @return \Api\Http\ApiResponse
     */
    public function index(ApiRequest $request, ApiResponse $response, array $args)
    {
        $input = $request->all();
        $params = $input;
        $user = auth()->user();

        if (!empty($params['user_query'])) {
            $users = $this->userService->list(['user_query' => $params['user_query']]);
            $userIds = [];
            if (!empty($users)) {
                foreach ($users as $user) {
                    $userIds[] = $user->user_id;
                }
            }
            $params['userIds'] = $userIds;
        }
        $lists = $this->folderMngService->list($params, $user);
        return $response->ok($lists);
    }


    /**
     * 단건 조회
     *
     * @param \Api\Http\ApiRequest $request
     * @param \Api\Http\ApiResponse $response
     * @param array $args
     * @return \Api\Http\ApiResponse
     */
    public function show(ApiRequest $request, ApiResponse $response, array $args)
    {
        $id = $args['id'];
        $list = $this->folderMngService->find($id);
        return $response->ok($list);
    }

    /**
     * 검색 조회
     *
     * @param \Api\Http\ApiRequest $request
     * @param \Api\Http\ApiResponse $response
     * @param array $args
     * @return \Api\Http\ApiResponse
     */
    public function search(ApiRequest $request, ApiResponse $response, array $args)
    {
        $input = $request->all();
        $user = auth()->user();
        $params = $input;

        $lists = $this->folderMngService->list($params, $user);


        return $response->ok($lists);
    }


    /**
     * 등록
     *
     * @param \Api\Http\ApiRequest $request
     * @param \Api\Http\ApiResponse $response
     * @param array $args
     * @return \Api\Http\ApiResponse
     */
    public function create(ApiRequest $request, ApiResponse $response, array $args)
    {
        $data = $request->all();
        $user = auth()->user();

        $dto = new FolderMngDto($data);
        $keys = array_keys($data);
        $dto = $dto->only(...$keys);
        // $dto = $dto->except(['pgm_id']);
        // dd($dto);

        //$authFolderCode = $this->sysCodeService->codeMapByCodeType('AUTH_FOLDER');

        $storageFsname = config('auth_config')['fsname'];
        $linkage = config('auth_config')['linkage']; 
        $authOwner = config('auth_config')['auth_owner'];

        //스크래치
        if ($dto->parent_id == 4) {
            $vol1_mid_path = config('auth_config')['mid_path_scratch'];
            $vol1_prefix_path = '/Volumes/' . $storageFsname . '/' . $vol1_mid_path;
            $pathAuth = config('auth_config')['path_auth_scratch'];

            if ($linkage) {
                $authMng = new FolderAuthManager([
                    'storageFsname' =>  $storageFsname,
                    'vol1_prefix_path' => $vol1_prefix_path,
                    'vol1_mid_path' => $vol1_mid_path,
                    'pathAuth' => $pathAuth
                ]);

                //그룹생성
                $return = $authMng->createGroupFromOD($dto->group_cd, $dto->folder_path_nm);

                //폴더생성 권한 부여
                $return = $authMng->makeFolderSetAuthor($dto->folder_path, $dto->group_cd, $authOwner);

                //쿼터 부여
                $return = $authMng->createQuota([
                    'fsname'             => $authMng::$storageFsname,
                    'type'               => 'dir',
                    'directory'          => $authMng::$vol1_mid_path . '/' . $dto->folder_path
                ]);
                $return = $authMng->updateQuota([
                    'gracePeriod_unit'   => $dto->grace_period_unit,
                    'softLimit_unit'     => $dto->quota_unit,
                    'hardLimit_unit'     => $dto->quota_unit,
                    'fsname'             => $authMng::$storageFsname,
                    'softLimit'          => $dto->quota,
                    'hardLimit'          => $dto->quota,
                    'gracePeriod'        => $dto->grace_period,
                    'type'               => 'dir',
                    'directory'          => $authMng::$vol1_mid_path . '/' . $dto->folder_path
                ]);
            }

            $collection = $this->folderMngService->create($dto, $user);
        } else {
            if ($dto->parent_id == 3) {
                $categoryInfo = Category::where("dep", '=', 3)->where("code", '=', 'news')->get()->first();
                $categoryParentId = $categoryInfo->category_id;
                $categoryDep = $categoryInfo->dep + 1;

                $vol1_mid_path = config('auth_config')['mid_path_news'];
                $dto->group_cd  = config('auth_config')['group_news'];
            } else if ($dto->parent_id == 2) {
                $categoryInfo = Category::where("dep", '=', 3)->where("code", '=', 'product')->get()->first();
                $categoryParentId = $categoryInfo->category_id;
                $categoryDep = $categoryInfo->dep + 1;

                $vol1_mid_path = config('auth_config')['mid_path_product'];
                $dto->group_cd  = config('auth_config')['group_product'];
            }
            $vol1_prefix_path = '/Volumes/' . $storageFsname . '/' . $vol1_mid_path;

            $dto->chmod = config('auth_config')['path_auth_product'];

            if ($linkage ) {
                $authMng = new FolderAuthManager([
                    'storageFsname' => $storageFsname,
                    'vol1_prefix_path' => $vol1_prefix_path,
                    'vol1_mid_path' => $vol1_mid_path,
                    'pathAuth' => $dto->chmod
                ]);

                //그룹생성
                $return = $authMng->createGroupFromOD($dto->group_cd, $dto->folder_path_nm);

                //폴더생성 권한 부여
                $return = $authMng->makeFolderSetAuthor($dto->folder_path, $dto->group_cd, $authOwner);

                //쿼터 부여
                $return = $authMng->createQuota([
                    'fsname'             => $authMng::$storageFsname,
                    'type'               => 'dir',
                    'directory'          => $authMng::$vol1_mid_path . '/' . $dto->folder_path
                ]);
            }


            $categoryDto = new CategoryDto([
                'category_title' => $dto->folder_path_nm,
                'parent_id' => $categoryParentId,
                'no_children' => 1,
                'dep' => $categoryDep
            ]);
            // dd($categoryDto);

            $categoryCol = $this->categoryService->create($categoryDto, $user);

            $dto->category_id = $categoryCol->category_id;
            $keys[] = 'category_id';
            $dto = $dto->only(...$keys);
            $collection = $this->folderMngService->create($dto, $user);
        }

        return $response->ok($collection, 201);
    }

    /**
     * 수정
     *
     * @param \Api\Http\ApiRequest $request
     * @param \Api\Http\ApiResponse $response
     * @param array $args
     * @return \Api\Http\ApiResponse
     */
    public function update(ApiRequest $request, ApiResponse $response, array $args)
    {
        $id = $args['id'];
        $data = $request->all();
        $user = auth()->user();

        $dto = new FolderMngDto($data);
        $keys = array_keys($data);
        $dto = $dto->only(...$keys);


        // dd($this->isTest);
        $storageFsname = config('auth_config')['fsname'];

        $linkage = config('auth_config')['linkage']; 

        $authOwner = config('auth_config')['auth_owner'];


        if ($linkage) {

            if ($dto->parent_id == 4) {
                $vol1_mid_path = config('auth_config')['mid_path_scratch'];
                $vol1_prefix_path = '/Volumes/' . $storageFsname . '/' . $vol1_mid_path;
                $pathAuth = config('auth_config')['path_auth_scratch'];


                $authMng = new FolderAuthManager([
                    'storageFsname' => $storageFsname,
                    'vol1_mid_path' => $vol1_mid_path
                ]);

                //처리
                $return = $authMng->updateQuota([
                    'gracePeriod_unit'   => $dto->grace_period_unit,
                    'softLimit_unit'     => $dto->quota_unit,
                    'hardLimit_unit'     => $dto->quota_unit,
                    'fsname'             => $authMng::$storageFsname,
                    'softLimit'          => $dto->quota,
                    'hardLimit'          => $dto->quota,
                    'gracePeriod'        => $dto->grace_period,
                    'type'               => 'dir',
                    'directory'          => $authMng::$vol1_mid_path . '/' . $dto->folder_path
                ]);
            }
        }

        $collection = $this->folderMngService->update($id, $dto, $user);
    
        
        $category = $this->categoryService->findOrFail($collection['category_id']);
        $category->category_title = $collection['folder_path_nm'];
        $category->save();

        return $response->ok($collection);
    }

    /**
     * 삭제
     *
     * @param \Api\Http\ApiRequest $request
     * @param \Api\Http\ApiResponse $response
     * @param array $args
     * @return \Api\Http\ApiResponse
     */
    public function delete(ApiRequest $request, ApiResponse $response, array $args)
    {
        $id = $args['id'];
        $user = auth()->user();
        $collection = $this->folderMngService->findOrFail($id);

        $storageFsname = config('auth_config')['fsname'];
        $linkage = config('auth_config')['linkage'] ; 
        $authOwner = config('auth_config')['auth_owner'];

        if ($linkage) {
            if ($collection->parent_id == 4) {
                $vol1_mid_path = config('auth_config')['mid_path_scratch'];

                $authMng = new FolderAuthManager([
                    'storageFsname' => $storageFsname,
                    'vol1_mid_path' => $vol1_mid_path
                ]);

                //처리
                $return = $authMng->deleteQuota([
                    'fsname'             => $authMng::$storageFsname,
                    'type'               => 'dir',
                    'directory'          => $authMng::$vol1_mid_path . '/' . $collection->folder_path
                ]);

                $return = $authMng->deleteGroupFromOD($collection->group_cd);
            }
        }


        $collection = $this->folderMngService->delete($id, $user);

        return $response->ok();
    }

    /**
     * 복원
     *
     * @param \Api\Http\ApiRequest $request
     * @param \Api\Http\ApiResponse $response
     * @param array $args
     * @return \Api\Http\ApiResponse
     */
    public function restore(ApiRequest $request, ApiResponse $response, array $args)
    {
        $id = $args['id'];
        $user = auth()->user();

        $this->folderMngService->restore($id, $user);
        return $response->ok();
    }


    public function findByWithUser(ApiRequest $request, ApiResponse $response, array $args)
    {
        $id = $args['id'];
        $user = auth()->user();
        $lists = $this->folderMngService->findByWithUser($id);

        return $response->ok($lists);
    }


    /**
     * 유저 매핑
     *
     * @param \Api\Http\ApiRequest $request
     * @param \Api\Http\ApiResponse $response
     * @param array $args
     * @return \Api\Http\ApiResponse
     */
    public function saveUser(ApiRequest $request, ApiResponse $response, array $args)
    {
        $id = $args['id'];
        $data = $request->all();
        $user = auth()->user();
        $curUserList = $data['curUserList'];
        $mapUsers = json_decode($curUserList, true);

        $delUserList = $data['delUserList'];
        $mapDelUsers = json_decode($delUserList, true);

        $storageFsname = config('auth_config')['fsname'];
        $linkage = config('auth_config')['linkage'] ; 
        $authOwner = config('auth_config')['auth_owner'];

        // $dto = new FolderMngDto($data);
        // $keys = array_keys($data);
        // $dto = $dto->only(...$keys);

        $collection = $this->folderMngService->findOrFail($id);

        $authMng = new FolderAuthManager();
        $addList = [];
        $delList = [];
        if (!empty($mapUsers)) {
            foreach ($mapUsers as $userId) {
                $userInfo = $authMng::findUserFromOD($userId);
                if ($userInfo) {
                    $addList[] = $userInfo;
                }
            }
        }
        if (!empty($mapDelUsers)) {
            //없는경우 
            foreach ($mapDelUsers as $userId) {
                $userInfo = $authMng::findUserFromOD($userId);
                if ($userInfo) {
                    $delList[] = $userInfo;
                }
            }
        }

        if ($linkage ) {
            if ($collection->parent_id == 4) {
                if (!empty($addList) || !empty($delList)) {
                    //그룹에 계정 추가
                    $return = $authMng::groupMapUserFromOD($collection->group_cd, $addList, $delList);
                }
            }
        }

        $collection = $this->folderMngService->saveUser($id,  $mapUsers, $user);
        return $response->ok($collection);
    }

    public function sync(ApiRequest $request, ApiResponse $response, array $args)
    {
        //$id = $args['id'];
        //$data = $request->all();
        $user = auth()->user();

        $storageFsname = config('auth_config')['fsname'];
        $linkage = config('auth_config')['linkage'] ; 
        $authOwner = config('auth_config')['auth_owner'];
        //$storageFsname = 'MAIN';
        $authMng = new FolderAuthManager([
            'storageFsname' => $storageFsname
        ]);

        //처리
        $quotaLists = $authMng->getQuotas();

        $lists = $this->folderMngService->list([], $user);

        foreach ($lists as $list) {

            $fullPath = $this->folderMngService->getFullPath($list->id);

            foreach ($quotaLists as $quotaList) {

                if ($quotaList['type'] == 'dir' && strstr($fullPath, $quotaList['name'] ) ) {

                    $folder = $this->folderMngService->findOrFail($list->id);

                    $folder->cursize          = $quotaList['curSize'];
                    $folder->status           = $quotaList['status'];
                    $folder->fs_type          = $quotaList['type'];
                    $folder->hardlimit_num    = $authMng::convNumber($quotaList['hardLimit']);
                    $folder->softlimit_num    = $authMng::convNumber($quotaList['softLimit']);
                    $folder->cursize_num      = $authMng::convNumber($quotaList['curSize']);

                    // if('/gemiso/Scratch/gemiso24' == $quotaList['name']){
                    //     dump( $list);
                    //     dd($quotaList);
                    // }

                    $folder->save();
                }
            }
        }

        return $response->ok($lists);
    }
    
    public function showByPgmId(ApiRequest $request, ApiResponse $response, array $args)
    {
        $pgmId = $args['pgm_id'];
        $folder = $this->folderMngService->getFolderByPgmId($pgmId);
        return $response->ok($folder);
    }

}
