<?php

namespace Api\Controllers;

use Api\Services\ApiLogService;
use Api\Services\DTOs\ApiLogDto;
use Interop\Container\ContainerInterface;

abstract class BaseController
{
    /**
     * @var \Interop\Container\ContainerInterface
     */
    protected $container;    

    /**
     * BaseController constructor.
     *
     * @param \Interop\Container\ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }   
}
