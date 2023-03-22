<?php
namespace Proxima\core;
use Proxima\core\Logger;
/**
 * php ssh2 모듈 필요함
 */

class SSH2 {
	private $host = 'host';
	private $user = 'user';
	private $port = '22';
	private $password = 'password';
	private $con = null;
	private $shell_type = 'xterm';
	private $shell = null;
	private $log = '';
	private $exception_mode = true;
    private $log_mode = false;
    
    public static $logger = null;

	function __construct($host='', $port=''  ) {

		if( $host!='' ) $this->host  = $host;
		if( $port!='' ) $this->port  = $port;
		
		$this->con  = @ssh2_connect($this->host, $this->port);


		if( !$this->con ) {
			$return_text = "Connection failed !".$this->host;

            //$this->_log($return_text);
            self::_log()->debug(print_r($return_text,true));
			if($this->exception_mode){
				throw new \Exception($return_text);
			}
		}
	}

	function authPassword( $user = '', $password = '' ) {

		if( $user!='' ) $this->user  = $user;
		if( $password!='' ) $this->password  = $password;

		if( !@ssh2_auth_password( $this->con, $this->user, $this->password ) ) {
			$return_text = "auth_password failed !".$this->host;
            //$this->_log($return_text);
            self::_log()->debug(print_r($return_text,true));
			if($this->exception_mode){
				throw new \Exception($return_text);
			}
		}

	}

	function openShell( $shell_type = '', $time = null  ) {

		if ( $shell_type != '' ) $this->shell_type = $shell_type;
		$this->shell = @ssh2_shell( $this->con,  $this->shell_type );
		if($time){
			sleep($time);
		}        

		@stream_set_blocking( $this->shell, true );
		$return = @fread( $this->shell, 4096 );
        //$this->_log('return:'.print_r($return,true));
        self::_log()->debug('return:'.print_r($return,true));

		if( !$this->shell ){
			$return_text = " Shell connection failed !";			
			$this->_log($return_text);
			if($this->exception_mode){
				throw new \Exception("error: ".$return_text);
			}
		}

	}

	function writeShell( $command = '' , $time = null ) {
        //$this->_log('cmd:'.$command);
        self::_log()->debug('cmd:'.print_r($command,true));
		@fwrite($this->shell, $command."\n");
		if($time){
			sleep($time);
		}        

		@stream_set_blocking( $this->shell, true );
		$return = @fread( $this->shell, 4096 );
        //$this->_log('return:'.print_r($return,true));
        self::_log()->debug('return:'.print_r($return,true));

		return $return;
	}

	function cmdExec() {

		$argc = func_num_args();
		$argv = func_get_args();

		$cmd = '';
		for( $i=0; $i<$argc ; $i++) {
		if( $i != ($argc-1) ) {
			$cmd .= $argv[$i]." && ";
		}else{
			$cmd .= $argv[$i];
		}
		}
		
        //$this->_log('cmd:'.$cmd);
        self::_log()->debug('cmd:'.print_r($cmd,true));
        $stream = @ssh2_exec( $this->con, $cmd ); 
		sleep(1);
        @stream_set_blocking( $stream, true );
        $return = @fread( $stream, 4096 );    
		return $return;
	}

	function getLog() {
		return $this->log;
	}

	function getshell(){
		return $this->shell;
	}

	function isError($return , $cmd = null ){
        self::_log()->debug('isError cmd:'.print_r($cmd,true));
        self::_log()->debug('isError return:'.print_r($return,true));
		$return_array = explode("\n", $return);
		if(count($return_array) > 1){
            $check = join('',$return_array);
			//$check = trim($return_array[0]);
			if( !empty($check) ){
				if( !empty($cmd) && trim($cmd) == $check ){
				}else{
                    //throw new \Exception("error:".$return);
				}
			}
		}else{
			if( !strstr($return, '#') ){
				 throw new \Exception("error:".$return);
			}
		}
    }
    
    public static function _log(){
        if( self::$logger == null ){
            self::$logger = new Logger(basename(__FILE__,'.php'));
        }
        return self::$logger;
    }

	// function _log($text){	
	// 	if($this->log_mode){
	// 		@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/'.basename(__FILE__).date('Ymd').'.log', date('H:i:s').','.microtime(true)."\n", FILE_APPEND);
	// 		@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/'.basename(__FILE__).date('Ymd').'.log', print_r($text, true)."\n", FILE_APPEND);
	// 	}else{
	// 		print_r($text, true);
	// 	}
	// }

	function disconnect() {
		unset($this->shell);
		unset($this->con);
		$this->_log("Server disconnected"."\n");
    } 

	public function __destruct() { 
        $this->disconnect(); 
    } 

}
?>