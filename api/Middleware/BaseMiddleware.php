<?php
namespace Api\Middleware;

use Slim\App;
use Interop\Container\ContainerInterface;

class BaseMiddleware
{
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function auth()
    {
        return $this->container->get('auth');
    }

    public function user()
    {
        return $this->auth()->user();
    }

    public function config($key)
    {
        $settings = $this->container->get('settings');
        return $settings[$key];
    }

    public function notFound($request, $response)
    {
        throw new NotFoundException($request, $response);
    }

    protected function isSecure()
    {
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' || !empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
            return true;
        }
        return false;
    }
}
