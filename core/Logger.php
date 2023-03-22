<?php
namespace Proxima\core;

use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;

class Logger
{
    protected $logger;
    public $levels = [
        'debug' => \Monolog\Logger::DEBUG,
        'info' => \Monolog\Logger::INFO,
        'warning' => \Monolog\Logger::WARNING,
        'error' => \Monolog\Logger::ERROR,
        'critical' => \Monolog\Logger::CRITICAL
    ];

    public function __construct($name, $level = 'debug')
    {
        if(empty($name)) {
            $name = 'nonamed';
        }

        if(empty($level) || empty($this->levels[$level])) {
            $level = 'debug';
        }

        $this->logger = new \Monolog\Logger($name);

        $name .= date('_Y-m-d');

        if(!defined('DS'))
            define('DS', DIRECTORY_SEPARATOR);

        $rootDir = dirname(__DIR__);
        $logFile = $rootDir . DS . 'log' . DS . $name . '.log';        

        $handler = new StreamHandler($logFile, $this->levels[$level]);
        $formatter = new LineFormatter(null, null, true, true);
        $handler->setFormatter($formatter);

        $this->logger->pushHandler($handler);        
    }

    public function info($message)
    {
        if(!$this->logger) {
            return;
        }
        $this->logger->addInfo($message);
    }

    public function error($message)
    {
        if(!$this->logger) {
            return;
        }
        $this->logger->addError($message);
    }

    public function warning($message)
    {
        if(!$this->logger) {
            return;
        }
        $this->logger->addWarning($message);
    }

    public function debug($message)
    {
        if(!$this->logger) {
            return;
        }
        $this->logger->addDebug($message);
    }

    public function critical($message)
    {
        if(!$this->logger) {
            return;
        }
        $this->logger->addCritical($message);
    }
}