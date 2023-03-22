<?php

namespace Api\Controllers;

use Api\Http\ApiRequest;
use Api\Http\ApiResponse;
use Psr\Container\ContainerInterface;
use Api\Services\RequestService;
use Api\Services\DataDicCodeSetService;
use Api\Services\DataDicCodeItemService;
use Api\Services\UserService;
use Api\Models\TbOrdFile;

class RequestController extends BaseController
{
    /**
     * 의뢰 서비스
     *
     * @var \Api\Services\RequestService
     */
    private $requestService;

    /**
     * 생성자는 필요할때만 정의하면 됨...
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->requestService = new RequestService($container);
    }
    /**
     * 홈화면 의뢰 목록 리스트
     *
     * @param ApiRequest $request
     * @param ApiResponse $response
     * @param array $args
     * @return void
     */
    public function getRequestList(ApiRequest $request, ApiResponse $response, array $args)
    {
        $input = $request->all();
        $user = auth()->user();

        $requestes = $this->requestService->getRequestList($input, $user);

        /**
         * 심의 의뢰 상태 코드 값
         */
        $dataDicCodeSetService = new DataDicCodeSetService($this->container);

        $reviewStatusCodeItems = $dataDicCodeSetService->findByCodeOrFail('REQEST_REQUST_STTUS')
            ->codeItems()
            ->get(['id', 'code_itm_code', 'code_itm_nm']);

        $graphicReqestTyCodeItems = $dataDicCodeSetService->findByCodeOrFail('GRAPHIC_REQEST_TY')
            ->codeItems()
            ->get(['id', 'code_itm_code', 'code_itm_nm']);

        
        foreach ($requestes as $request) {
            $reviewStatusCode = $request->ord_status;
            $requestGraphicTyCode = $request->graphic_reqest_ty;
            
            $request->requeest_st_code = DataDicCodeItemService::getCodeItemByCode($reviewStatusCodeItems, $reviewStatusCode);
            $request->graphic_reqest_ty_ln = DataDicCodeItemService::getCodeItemByCode($graphicReqestTyCodeItems, $requestGraphicTyCode);
            
        };
        
        return $response->ok($requestes);
    }
    /**
     * 의뢰 단건 조회
     *
     * @param ApiRequest $request
     * @param ApiResponse $response
     * @param array $args
     * @return \Api\Http\ApiResponse
     */
    public function show(ApiRequest $request, ApiResponse $response, array $args)
    {
        $ordId = $args['ord_id'];
        // $request = $this->requestService->find($ordId);
        $request = $this->requestService->findWithUser($ordId);
        return $response->ok($request);
    }

    /**
     * 의뢰 진행상태 변경
     *
     * @param ApiRequest $request
     * @param ApiResponse $response
     * @param array $args
     * @return void
     */
    public function statusUpdate(ApiRequest $request, ApiResponse $response, array $args)
    {
        $input = $request->all();

        $ordId = $args['ord_id'];
        $changeStatus = $input['changeStatus'];

        $originStatus = $this->requestService->find($ordId)->ord_status;

        $status = $this->requestService->updateStatus($ordId, $changeStatus);
        return $response->ok($status);
    }

    public function updateStatusCancel(ApiRequest $request, ApiResponse $response, array $args)
    {
        $input = $request->all();

        $ordId = $args['ord_id'];
        $status = $this->requestService->updateStatusCancel($ordId);
        return $response->ok($status);
    }

    /**
     * 의뢰 담당자 변경
     *
     * @param ApiRequest $request
     * @param ApiResponse $response
     * @param array $args
     * @return void
     */
    public function updateCharger(ApiRequest $request, ApiResponse $response, array $args)
    {
        $input = $request->all();

        $ordId = $args['ord_id'];
        $updateCharger = $input['updateCharger'];

        $charger = $this->requestService->updateCharger($ordId, $updateCharger);
        return $response->ok($charger);
    }

    /**
     * 의뢰 수정 변경
     *
     * @param ApiRequest $request
     * @param ApiResponse $response
     * @param array $args
     * @return \Api\Http\ApiResponse
     */
    public function update(ApiRequest $request, ApiResponse $response, array $args)
    {
        $data = $request->all();
        $user = auth()->user();
        $requestData = json_decode($data['request_data']);
        $userNm = Json_decode($data['user_data'])->user_nm;
        $workUser = UserService::findByUserNm($userNm);
        $ordId = $args['ord_id'];


        $charger = $this->requestService->update($ordId, $data);
        return $response->ok($charger);
    }


    /**
     * 의뢰 요청 등록
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
        $requestData = json_decode($data['request_data']);
        $userNm = Json_decode($data['user_data'])->user_nm;
        $graphicReqestTy = Json_decode($data['user_data'])->graphic_reqest_ty;
        $workUser = UserService::findByUserNm($userNm);

        // 그래픽 or 영상편집 구분
        $typeSe = json_decode($data['typeSe']);

        $request = $this->requestService->create($requestData, $typeSe, $user, $workUser,$graphicReqestTy);
        // $dto = new DataDicTableDto($data);
        // $dto->createValidate();

        // $table = $this->dataDicTableService->create($dto, $user);
        return $response->ok($request, 201);
    }
    /**
     * 의뢰 첨부파일 추가
     *
     * @param ApiRequest $request
     * @param ApiResponse $response
     * @param array $args
     * @return \Api\Http\ApiResponse
     */
    public function attach(ApiRequest $request, ApiResponse $response, array $args)
    {
        $input = $request->all();
        $attach = $this->requestService->attach($input);
    }
    /**
     * 의뢰 첨부파일 단건 조회
     *
     * @param ApiRequest $request
     * @param ApiResponse $response
     * @param array $args
     * @return \Api\Http\ApiResponse
     */
    public function showAttach(ApiRequest $request, ApiResponse $response, array $args)
    {
        $ordId = $args['ord_id'];
        $query = TbOrdFile::query();
        $attach = $query->where('ord_id',$ordId)->get();
        
        return $response->ok($attach, 201);
    }

    public function delete(ApiRequest $request, ApiResponse $response, array $args)
    {
        $user = auth()->user();
        $userId = $user->user_id;
        $ordId = $args['ord_id'];
        $data = $request->all();
        $status = $data['status'];
        
        if(empty($ordId) || empty($status)){
            return $response->error('삭제할 수 없는 요청입니다.');
        }
        $request = $this->requestService->findOrFail($ordId);

        if($userId != $request->inputr_id){
            return $response->error('등록자만 삭제할 수 있습니다.');
        }
        
        if($status == 'complete'){
            return $response->error('완료 상태가 아닌 목록에서 요청해주세요.');
        }

        $request = $this->requestService->delete($ordId);
        // 첨부파일 삭제
        $fileQuery = TbOrdFile::query();
        $fileQuery->where('ord_id',$ordId)->delete();

        return $response->ok();
    }

    public function deleteAttach(ApiRequest $request, ApiResponse $response, array $args)
    {
        $id = $args['id'];
        
        $user = auth()->user();
        $userId = $user->user_id;

        $query = TbOrdFile::query();
        $attach = $query->find($id);
        
        $ordId = $attach->ord_id;

        $request = $this->requestService->findOrFail($ordId);
        if($userId === $request->inputr_id){
            $attach->delete();
            return $response->ok();
        }else{
            return $response->error('등록자만 삭제할 수 있습니다.');
        }
    }

}
