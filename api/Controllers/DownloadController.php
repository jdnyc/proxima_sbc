<?php

namespace Api\Controllers;

use Api\Http\ApiRequest;
use Api\Http\ApiResponse;
use Api\Models\Download;
use Psr\Container\ContainerInterface;

class DownloadController extends BaseController
{
    /**
     * 생성자는 필요할때만 정의하면 됨...
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);

        // db 커넥션 연결
        $container->get('db');
    }

    /**
     * 다운로드 목록 조회
     *
     * @param \Api\Http\ApiRequest $request
     * @param Api\Http\ApiResponse $response
     * @param array $args
     * @return Api\Http\ApiResponse
     */
    public function index(ApiRequest $request, ApiResponse $response, array $args)
    {   
        $downloads = Download::where('published', true)
                    ->orderBy('show_order')
                    ->get();

        /** @var \Api\Models\Download $download */
        foreach($downloads as $download) {
            $download->setAttribute('url', $download->path);
        }                    

        return response()->ok($downloads);
    }

    /**
     * 다운로드 목록 생성
     *
     * @param \Api\Http\ApiRequest $request
     * @param Api\Http\ApiResponse $response
     * @param array $args
     * @return Api\Http\ApiResponse
     */
    public function store(ApiRequest $request, ApiResponse $response, array $args)
    {   
        $data = $request->all();
        $lastDownloadItem = Download::orderByDesc('show_order')
                            ->first();

        $download = new Download();
        $download->title = $data['title'];
        $download->icon = $data['icon'] ?? 'fa-circle';
        $download->path = $data['path'];
        $download->description = $data['description'];
        if(empty($download->show_order)) {
            $download->show_order = ($lastDownloadItem->show_order + 1);
        }
        $download->save();

        return response()->ok($download);
    }

    /**
     * 다운로드 상세조회
     *
     * @param \Api\Http\ApiRequest $request
     * @param Api\Http\ApiResponse $response
     * @param array $args
     * @return Api\Http\ApiResponse
     */
    public function show(ApiRequest $request, ApiResponse $response, array $args)
    {   
        $downloadId = $args['download_id'];
        if(empty($downloadId)) {
            api_abort('download_id should not empty.', null, 400);
        }

        $download = Download::find($downloadId);
        if($download === null) {
            api_abort_404(Download::class);
        }

        $download->setAttribute('url', $download->path);

        return response()->ok($download);
    }

    /**
     * 다운로드 단일 항목 업데이트
     *
     * @param \Api\Http\ApiRequest $request
     * @param Api\Http\ApiResponse $response
     * @param array $args
     * @return Api\Http\ApiResponse
     */
    public function update(ApiRequest $request, ApiResponse $response, array $args)
    {   
        $data = $request->all();
        $downloadId = $args['download_id'];
        if(empty($downloadId)) {
            api_abort('download_id should not empty.', null, 400);
        }

        $download = Download::find($downloadId);
        if($download === null) {
            api_abort_404(Download::class);
        }
        $download->title = $data['title'];
        $download->icon = $data['icon'] ?? 'fa-circle';
        $download->path = $data['path'];
        $download->description = $data['description'];
        if(!empty($data['show_order'])) {
            $download->show_order = $data['show_order'];
        }
        $download->save();

        return response()->ok($download);
    }

    /**
     * 다운로드 단일 항목 삭제
     *
     * @param \Api\Http\ApiRequest $request
     * @param Api\Http\ApiResponse $response
     * @param array $args
     * @return Api\Http\ApiResponse
     */
    public function destroy(ApiRequest $request, ApiResponse $response, array $args)
    {   
        $downloadId = $args['download_id'];
        if(empty($downloadId)) {
            api_abort('download_id should not empty.', null, 400);
        }

        $download = Download::find($downloadId);
        if($download === null) {
            api_abort_404(Download::class);
        }

        $download->delete();

        return response()->ok(null, 204);
    }
}
