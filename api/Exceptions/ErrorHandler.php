<?php

namespace Api\Exceptions;

use Slim\Handlers\Error;
use Slim\Handlers\NotFound;
use Api\Exceptions\ApiException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ErrorHandler extends Error
{
    /** @inheritdoc */
    public function __construct(bool $displayErrorDetails)
    {
        parent::__construct($displayErrorDetails);
    }
    /** @inheritdoc */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, \Exception $exception)
    {   
        $response->apiLogFail($exception);

        if ($exception instanceof ModelNotFoundException) {
            return (new NotFound())($request, $response);
        } else if ($exception instanceof \Respect\Validation\Exceptions\ExceptionInterface) {
            // validation 체크 에러를 개행문자로 묶어서 에러처리 한다.
            $messages = $exception->getMessages();
            $message = implode(PHP_EOL, $messages);
            return response()->error($message, 'invalid_input', 400);
        } else if ($exception instanceof ApiException) {
            return response()->error($exception->message, $exception->code, $exception->status);
        } else if ($exception instanceof \Illuminate\Database\QueryException) {                             
            return response()->error($exception->getMessage(), 'query_exception', 400);
        }

        return response()->error($exception->getMessage(), 'server_exception', 500);
        // return parent::__invoke($request, $response, $exception);
    }
}
