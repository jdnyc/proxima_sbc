<?php

namespace Api\Middleware;

use Slim\App;
use Api\Middleware\BaseMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * php 설정용 
 */
class ConfigMiddleware extends BaseMiddleware
{
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        set_time_limit(300);
        return $next($request, $response);
    }
}
