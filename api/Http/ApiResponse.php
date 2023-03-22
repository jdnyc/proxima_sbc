<?php

namespace Api\Http;

use Slim\Http\Response;
use Api\Models\BaseModel;
use Illuminate\Pagination\LengthAwarePaginator;

class ApiResponse extends Response
{
    /**
     * ApiLog 객체
     *
     * @var \Api\Models\ApiLog
     */
    public $apiLog;

    /**
     * ApiLog 서비스
     *
     * @var \Api\Services\ApiLogService
     */
    public $apiLogService;

    public function ok($data = null, $status = 200)
    {
        $res = [
            'success' => true
        ];

        // 관계 조회 파라메터가 있을때 관계 속성을 추가해줌
        if (
            $data && $data instanceof BaseModel &&
            request()->hasIncludes()
        ) {
            $data->addRelationships();
        }

        if ($data && $data instanceof LengthAwarePaginator) {
            $res['total'] = $data->total();
            $res['current_page'] = $data->currentPage();
            $res['per_page'] = $data->perPage();
            $res['data'] = $data->items();
        } else {
            $res['data'] = $data;
        }

        $res['data'] = \Api\Support\Helpers\FormatHelper::fixDateTimeFormat($res['data']);

        $this->apiLogSucceed();

        return response()->withJson($res)
            ->withStatus($status);
    }

    public function okArray($data = null, $status = 200)
    {
        // 관계 조회 파라메터가 있을때 관계 속성을 추가해줌
        if (
            $data && $data instanceof BaseModel &&
            request()->hasIncludes()
        ) {
            $data->addRelationships();
        }

        $this->apiLogSucceed();

        return response()->withJson($data)
            ->withStatus($status);
    }


    public function error($message, $code = '', $status = 500)
    {
        $res = [
            'success' => false,
            'msg' => $message
        ];

        if (!empty($code)) {
            $res['code'] = $code;
        }

        return response()->withJson($res)
            ->withStatus($status);
    }

    public function debugError($exception, $code = '', $status = 500)
    {
        $res = [
            'success' => false,
            'msg' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ];

        if (!empty($code)) {
            $res['code'] = $code;
        }

        return response()->withJson($res)
            ->withStatus($status);
    }


    public function okMsg($data = null, $message = null, $status = 200)
    {
        $res = [
            'success' => true,
            'msg' => $message
        ];

        // 관계 조회 파라메터가 있을때 관계 속성을 추가해줌
        if (
            $data && $data instanceof BaseModel &&
            request()->hasIncludes()
        ) {
            $data->addRelationships();
        }

        if ($data && $data instanceof LengthAwarePaginator) {
            $res['total'] = $data->total();
            $res['current_page'] = $data->currentPage();
            $res['per_page'] = $data->perPage();
            $res['data'] = $data->items();
        } else {
            $res['data'] = $data;
        }

        $this->apiLogSucceed();

        return response()->withJson($res)
            ->withStatus($status);
    }

    public function apiLogFail($exception)
    {
        if (!$this->apiLogService || !$this->apiLog) {
            return;
        }

        $this->apiLogService->fail($this->apiLog, $exception->getMessage() . "\n" . $exception->getTraceAsString());
    }

    protected function apiLogSucceed()
    {
        if (!$this->apiLogService || !$this->apiLog) {
            return;
        }

        $this->apiLogService->succeed($this->apiLog);
    }
}
