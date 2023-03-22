<?php

namespace Api\Controllers;

use Api\Http\ApiRequest;
use Api\Http\ApiResponse;
use Api\Services\DTOs\ApiLogDto;
use Psr\Container\ContainerInterface;
use Api\Services\MediaService;

class MediaController extends BaseController
{
    /**
     * 미디어 서비스
     *
     * @var \Api\Services\MediaService
     */
    private $mediaService;

    /**
     * 생성자는 필요할때만 정의하면 됨...
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);

        $this->userService = new MediaService($container);
    }
}
