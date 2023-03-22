<?php

namespace Api\Exceptions;

use Slim\Handlers\PhpError;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class PhpErrorHandler extends PhpError
{
    /** @inheritdoc */
    public function __construct(bool $displayErrorDetails)
    {
        parent::__construct($displayErrorDetails);
    }

    /**
     * Invoke error handler
     *
     * @param ServerRequestInterface $request   The most recent Request object
     * @param ResponseInterface      $response  The most recent Response object
     * @param \Throwable             $error     The caught Throwable object
     *
     * @return ResponseInterface
     * @throws UnexpectedValueException
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, \Throwable $error)
    {   
        $response->apiLogFail($error);

        if ($error instanceof \Spatie\DataTransferObject\DataTransferObjectError) {
            return response()->error($error->getMessage(), 'dto_error', 400);
        }
        
        $response = response()->debugError($error, 'server_error', 500);
        return $response;
        // return parent::__invoke($request, $response, $error);
    }
}
