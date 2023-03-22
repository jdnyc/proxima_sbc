<?php
use Monolog\Logger;
use Monolog\Handler\ChromePHPHandler;
use Monolog\Handler\FirePHPHandler;

$logger = new Logger('name');
// $logger->pushHandler(new FirePHPHandler(Logger::ERROR));
// $logger->pushHandler(new ChromePHPHandler(Logger::DEBUG));