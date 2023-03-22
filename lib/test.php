<?php
define(ROOT, dirname(__FILE__));
define(BASEDIR, __DIR__.'/..');

require BASEDIR.'/vendor/autoload.php';

require_once("MDB2.php");
require_once(ROOT."/../lib/error_handler.php");
require_once(ROOT."/../lib/DBOracle.class.php");
require_once(ROOT."/../lib/lang.php");

require_once(ROOT."/../lib/MetaData.class.php");
require_once(ROOT."/../lib/Logger.php");

$GLOBAL['_config'] = require_once('_config.php');

$mdb = new Database('proxima', 'proxima', '192.168.1.80:1521/proxima');
//$mdb = new Database('proxima', 'proxima', '1.215.23.20:8016/proxima');
//$mdb = new Database('tbs', 'tbs', '192.168.100.6/das');







$GLOBALS['db'] = & $mdb;

?>
