<?php

namespace Api\Middleware;

use Slim\App;
use Api\Middleware\BaseMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class AuthMiddleware extends BaseMiddleware
{
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        $apiKey = $request->getHeaderLine('X-API-KEY');
        $auth = $this->auth();
        if (!empty($apiKey) && $apiKey === config('api_key')) {
            $apiUser = $request->getHeaderLine('X-API-USER');
            $auth->setUser($apiUser);
        } else {
            if (!$auth->check()) {
                return response()->error('forbidden', 'forbidden', 403);
            }
        }

        return $next($request, $response);
    }
}
