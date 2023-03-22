<?php

namespace Api\Middleware;

use Slim\App;
use Api\Services\ApiLogService;
use Api\Services\DTOs\ApiLogDto;
use Api\Middleware\BaseMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ApiLogMiddleware extends BaseMiddleware
{
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next)
    {
        $uri = $request->getUri();
        $apiPath = $request->getMethod() . ' ' . $uri->getBasePath() . '/' . $uri->getPath();              
        
        $body = (string)$request->getBody();
        if(empty($body)) {
            $body = json_encode($request->getParsedBody());
        }

        $apiUserId = $request->getHeaderLine('X-API-USER');
        if(empty($apiUserId)) {
            $apiUserId = 'unknown';
        }
        
        $dto = new ApiLogDto([
            'path' => $apiPath,
            'query' => $uri->getQuery(),
            'payload' => $body,
            'user_id' => $apiUserId,
            'status' => 'A'
            ]);        
            
        $apiLogService = new ApiLogService($this->container);
        $apiLog = $apiLogService->create($dto);
            
        $response = response();
        $response->apiLogService = $apiLogService;
        $response->apiLog = $apiLog;        

        return $next($request, $response);
    }
}
