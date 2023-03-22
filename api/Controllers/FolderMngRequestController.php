<?php

namespace Api\Controllers;

use Api\Http\ApiRequest;
use Api\Models\Category;

use Api\Http\ApiResponse;

// 서비스

use Api\Services\UserService;
use Api\Services\ContentService;
use Api\Services\SysCodeService;
use Api\Services\CategoryService;

use Api\Models\FolderMngOwnerUser;
use Api\Services\DTOs\CategoryDto;
use Api\Services\FolderMngService;
use Api\Services\DTOs\FolderMngDto;
use Api\Types\FolderMngRequestStatus;
use Psr\Container\ContainerInterface;
use Api\Services\FolderMngRequestService;

use Api\Support\Helpers\SMSMessageHelper;
use ProximaCustom\core\FolderAuthManager;
use Api\Services\DTOs\FolderMngRequestDto;

use Api\Services\FolderMngOwnerUserService;
use Api\Services\DTOs\FolderMngOwnerUserDto;
use Api\Services\ZodiacService;

class FolderMngRequestController extends BaseController
{
    private $folderMngRequestService;
    private $folderMngService;
    private $userService;
    private $sysCodeService;
    private $categoryService;
    private $contentService;
    private $zodiacService;
    private $folderMngOwnerUserService;

        /**
     * 생성자는 필요할때만 정의하면 됨...
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->folderMngRequestService = new FolderMngRequestService($container);
        $this->folderMngService = new FolderMngService($container);
        $this->userService = new UserService($container);
        $this->sysCodeService = new SysCodeService($container);
        $this->categoryService = new CategoryService($container);
        $this->contentService = new ContentService($container);
        $this->zodiacService = new ZodiacService($container);
        $this->folderMngOwnerUserService = new FolderMngOwnerUserService($container);
    }

    public function index(ApiRequest $request, ApiResponse $response, array $args)
    {
        $user = auth()->user();
        $data = $request->all();
        $requests = $this->folderMngRequestService->list($data,$user);
        
        return $response->ok($requests);
    }
    /**
     * 신청
     *
     * @param ApiRequest $request
     * @param ApiResponse $response
     * @param array $args
     * @return \Api\Http\ApiResponse
     */
    public function create(ApiRequest $request, ApiResponse $response, array $args)
    {
        $data = $request->all();
        $dto = new FolderMngRequestDto($data); 

        $pgmId = $dto->pgm_id;
        $dc = trim($dto->dc);

        if($pgmId === "") return $response->okMsg(null,'프로그램이 정상적으로 선택되지 않았습니다.');
        if($dc === "") return $response->okMsg(null,'신청 사유가 입력되지 않았습니다.');

        $folderMngRequest = $this->folderMngRequestService->getByPgmId($pgmId);
        if(!is_null($folderMngRequest)) return $response->okMsg(null,'이미 신청관리에 추가되어있는 프로그램입니다.');

        $folderMng = $this->folderMngService->getByPgmId($pgmId);
    
        if(!is_null($folderMng)) return $response->okMsg(null,'이미 폴더관리에 추가되어있는 프로그램입니다.');

        $keys = array_keys($data);
        $dto = $dto->only(...$keys);
   
        $user = auth()->user();
        $request = $this->folderMngRequestService->create($dto,$user);
        
        $smsMsg = SMSMessageHelper::makeMsgCreateFolderRequest($request,$user);
        $adminUsers = $this->userService->getAdminUsers();
        foreach ($adminUsers as $adminUser) {
            $this->zodiacService->sendSMS($adminUser->phone, $smsMsg);
        }        
        return $response->ok($request);
    }
    /**
     * 수정
     *
     * @param ApiRequest $request
     * @param ApiResponse $response
     * @param array $args
     * @return \Api\Http\ApiResponse
     */
    public function update(ApiRequest $request, ApiResponse $response, array $args)
    {
        $id = $args['id'];
        $data = $request->all();
        $user = auth()->user();

        $dto = new FolderMngRequestDto($data);
        $keys = array_keys($data);
        $dto = $dto->only(...$keys);

        $collection = $this->folderMngRequestService->update($id, $dto, $user);
        return $response->ok($collection);
    }
    /**
     * 상태 변경
     *
     * @param ApiRequest $request
     * @param ApiResponse $response
     * @param array $args
     * @return \Api\Http\ApiResponse 
     */
    public function updateStatus(ApiRequest $request, ApiResponse $response, array $args)
    {
        
        $id = $args['id'];
        $data = $request->all();
        $user = auth()->user();
        // 관리자가 아닐때 메세지 리턴
        $hasAdmin = $user->hasAdminGroup();
        if(!$hasAdmin) return $response->okMsg(null,'관리자 권한이 있어야 합니다.');

        // 요청상태가 아닐때 거절 메세지 리턴\
        $folderMngRequest = $this->folderMngRequestService->findOrFail($id);
        $folderMngRegUserId = $folderMngRequest->regist_user_id;
    
        $oriStatus = $folderMngRequest->status;
        if($oriStatus != FolderMngRequestStatus::REQUEST) return $response->okMsg(null,'요청상태가 아닙니다.');

        $collection = $this->folderMngRequestService->updateStatus($id, $data, $user);
        

                 // 상태변경이 승인 일때
                 if($data['status'] == FolderMngRequestStatus::APPROVAL){
                    //  시퀸스 아이디
                
                    // 제작 폴더 카운터
                    $folderCount = $this->folderMngService->folderIndex();


                    // 스크래치 폴더 생성 시작
                    $folderPathNm = $folderMngRequest->folder_path_nm.'-'.$folderCount;
                    $folderPath = 'program_'.$folderCount;

                    $authFolderId = $this->contentService->getSequence('SEQ_FOLDER_MNG');
                    
                    $authFolderMngRequestArray = $folderMngRequest->toArray();
                    $authFolderDto = new FolderMngDto([]);
                    foreach($authFolderDto as $key => $val){
                        switch($key){
                            case 'id':
                                $authFolderDto->$key = $authFolderId;
                            break;
                            case 'folder_path_nm':
                                $authFolderDto->$key = $folderPathNm;
                            break;
                            case 'folder_path':
                                $authFolderDto->$key = $folderPath;
                            break;
                            case 'group_cd':
                                $authFolderDto->$key = 'group_'.$folderPath;
                            break;
                            case 'grace_period':
                                $authFolderDto->$key = 1;
                            break;
                            case 'quota_unit':
                                $authFolderDto->$key = 'TB';
                            break;
                            case 'grace_period_unit':
                                $authFolderDto->$key = 'Minutes';
                            break;
                            case 'parent_id':
                                $authFolderDto->$key = 4;
                            break;
                            case 'step':
                                $authFolderDto->$key = 2;
                            break;
                            case 'quota':
                                $authFolderDto->$key = 3;
                            break;
                            default:
                                $authFolderDto->$key = $authFolderMngRequestArray[$key];
                            break;
                        }
                    }
                    
                    $storageFsname = config('auth_config')['fsname'];
                    $linkage = config('auth_config')['linkage']; 
                    $authOwner = config('auth_config')['auth_owner'];

                    $vol1_mid_path = config('auth_config')['mid_path_scratch'];
                    $vol1_prefix_path = '/Volumes/' . $storageFsname . '/' . $vol1_mid_path;
                    $pathAuth = config('auth_config')['path_auth_scratch'];
        
                    //물리 폴더 및 권한 부여
                    if ($linkage) {
                        $authMng = new FolderAuthManager([
                            'storageFsname' =>  $storageFsname,
                            'vol1_prefix_path' => $vol1_prefix_path,
                            'vol1_mid_path' => $vol1_mid_path,
                            'pathAuth' => $pathAuth
                        ]);
        
                        //그룹생성
                        $return = $authMng->createGroupFromOD($authFolderDto->group_cd, $authFolderDto->folder_path_nm);
        
                        //폴더생성 권한 부여
                        $return = $authMng->makeFolderSetAuthor($authFolderDto->folder_path, $authFolderDto->group_cd, $authOwner);
        
                        //쿼터 부여
                        $return = $authMng->createQuota([
                            'fsname'             => $authMng::$storageFsname,
                            'type'               => 'dir',
                            'directory'          => $authMng::$vol1_mid_path . '/' . $authFolderDto->folder_path
                        ]);
                        $return = $authMng->updateQuota([
                            'gracePeriod_unit'   => $authFolderDto->grace_period_unit,
                            'softLimit_unit'     => $authFolderDto->quota_unit,
                            'hardLimit_unit'     => $authFolderDto->quota_unit,
                            'fsname'             => $authMng::$storageFsname,
                            'softLimit'          => $authFolderDto->quota,
                            'hardLimit'          => $authFolderDto->quota,
                            'gracePeriod'        => $authFolderDto->grace_period,
                            'type'               => 'dir',
                            'directory'          => $authMng::$vol1_mid_path . '/' . $authFolderDto->folder_path
                        ]);
                    }
                
                    $authFolderMng = $this->folderMngService->create($authFolderDto, $user);
                    // 스크래치 폴더 생성 끝

                    //물리 폴더 및 권한 부여
                    if ($linkage) {
                        
                        $addList = [];
                        $mapUsers = [
                            $folderMngRegUserId
                        ];
                        if (!empty($mapUsers)) {
                            foreach ($mapUsers as $userId) {
                                $userInfo = $authMng::findUserFromOD($userId);
                                if ($userInfo) {
                                    $addList[] = $userInfo;
                                }
                            }
                        }
            
                        if (!empty($addList) || !empty($delList)) {
                            //그룹에 계정 추가
                            $return = $authMng::groupMapUserFromOD($authFolderMng->group_cd, $addList, []);
                        }                
                
                        $this->folderMngService->saveUser($authFolderMng->id, $mapUsers, $user);
                    }

                    // $authFolderOwnerDto = new FolderMngOwnerUserDto([
                    //     'folder_id' => $authFolderId,
                    //     'user_id' =>     $folderMngRegUserId
                    // ]);
                    
                    // $this->folderMngOwnerUserService->create($authFolderOwnerDto);
           
                       
                    // 제작 폴더 관련 
                    $folderMngId = $this->contentService->getSequence('SEQ_FOLDER_MNG');

                
                    $folderMngRequest->folder_path_nm = $folderPathNm;
                    $folderMngRequest->folder_path = $folderPath;

                    // 카테고리 정보 생성
                    $categoryInfo = Category::where("dep", '=', 3)->where("code", '=', 'product')->get()->first();
                    $categoryParentId = $categoryInfo->category_id;
                    $categoryDep = $categoryInfo->dep + 1;
                    
                    $categoryDto = new CategoryDto([
                        'category_title' => $folderPathNm,
                        'parent_id' => $categoryParentId,
                        'no_children' => 1,
                        'dep' => $categoryDep
                    ]);
                    
                    // 폴더 제작 관리 테이블 추가
                    $categoryCol = $this->categoryService->create($categoryDto, $user);
                    
                    $folderMngRequest->category_id = $categoryCol->category_id;
                    $keys[] = 'category_id';
                    // $folderMngRequest = $folderMngRequest->only(...$keys);
                    $folderDto = new FolderMngDto([
                    ]);
                    $folderMngRequestArray = $folderMngRequest;


                    //프로그램 폴더 생성 끝
                    foreach($folderDto as $key => $val){
                        if($key === 'id'){
                            $folderDto->id = $folderMngId;
                        }else if($key === 'owner_cd'){
                            $folderDto->owner_cd = $folderMngRequestArray['folder_path'];
                        }else{
                            $folderDto->$key = $folderMngRequestArray[$key];
                        };
                    }

                    $vol1_mid_path = config('auth_config')['mid_path_product'];
                    $folderDto->group_cd  = config('auth_config')['group_product'];            
                    $vol1_prefix_path = '/Volumes/' . $storageFsname . '/' . $vol1_mid_path;        
                    $folderDto->chmod = config('auth_config')['path_auth_product'];

                    //물리 폴더 및 권한 부여
                    if ($linkage ) {
                        $authMng = new FolderAuthManager([
                            'storageFsname' => $storageFsname,
                            'vol1_prefix_path' => $vol1_prefix_path,
                            'vol1_mid_path' => $vol1_mid_path,
                            'pathAuth' => $folderDto->chmod
                        ]);
        
                        //그룹생성
                        $return = $authMng->createGroupFromOD($folderDto->group_cd, $folderDto->folder_path_nm);
        
                        //폴더생성 권한 부여
                        $return = $authMng->makeFolderSetAuthor($folderDto->folder_path, $folderDto->group_cd, $authOwner);
        
                        //쿼터 부여
                        $return = $authMng->createQuota([
                            'fsname'             => $authMng::$storageFsname,
                            'type'               => 'dir',
                            'directory'          => $authMng::$vol1_mid_path . '/' . $folderDto->folder_path
                        ]);
                    }
        
                    $folderMng = $this->folderMngService->create($folderDto, $user);
                    //프로그램 폴더 생성 끝

                    
                    // 폴더 관리 오너 유저 테이블 추가
                    $folderOwnerDto = new FolderMngOwnerUserDto([
                        'folder_id' => $folderMngId,
                        'user_id' => $folderMngRegUserId
                    ]);
                    $this->folderMngOwnerUserService->create($folderOwnerDto);

                    $collection->folder_mng = $folderMng;
                    $collection->auth_folder_mng = $authFolderMng;
                };

        $regUser = $this->userService->findByUserId($folderMngRegUserId);
        if( $regUser->phone ){
            $smsMsg = SMSMessageHelper::makeMsgCreateFolderRequest($folderMngRequest,$regUser);
            $this->zodiacService->sendSMS($regUser->phone, $smsMsg);    
        }

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
        
        
        $collection = $this->folderMngRequestService->delete($id, $user);

        return $response->ok();
    }
}