<?php
require_once('ftp.class.php');

class ExFTP extends FTP {
	public $path;
	public $storage_id;
	public $prefix_queue = '\_QUEUE_';
	
	private $ftp;
	private $dirs = array();
	private $files = array();

	function __construct($host, $port, $id, $pw, $path = '.', $storage_id = null) {
		$this->ftp = new FTP();
		$this->ftp->debug = true;

		$connected = $this->ftp->ftp_connect($host, $port);
		if (!$connected) {
			throw new Exception($this->ftp->last_message);
		}

		$logined = $this->ftp->ftp_login($id, $pw);
		if (!$logined) {
			throw new Exception($this->ftp->last_message);
		}

		$this->path = rtrim($path, '/');
		$this->storage_id = $storage_id;
	}

//	function getAllList($path) {
//		$path = trim($this->path.'/'.$path, '/');
//
//		//echo $path;
//
//		$files = $this->ftp->ftp_list($path);
//		foreach ($files as $file) {
//			unset($dir);
//			$file_info = explode(';', $file);
//			$dir['path'] = $path;
//			$dir['name'] = trim(array_pop($file_info));
//			foreach ($file_info as $info) {
//				list($k, $v) = explode('=', $info);
//				$dir[$k] = trim($v);
//			}
//
//			if ($dir['type'] == 'file') {
//				$this->dirs[] = $dir;
//			}
//		}
//
//		return $this->dirs;
//	}

	function getAllList($path, $filter = null) {
		$path = trim($this->path.'/'.$path, '/');

		//echo $path;

		$files = $this->ftp->ftp_list($path); // print_r($files);
		foreach ($files as $file) {
			if ($filter && preg_match($filter, $file)) {
				continue;
			}

			unset($dir);
			$file = preg_split('/[\s]+/', $file);
			$dir['path'] = $path;
			$dir['name'] = iconv('euc-kr', 'utf-8', trim(implode(' ', array_slice($file, 8))));
			$dir['storage_id'] = $this->storage_id;
			$dir['size'] = $file[4];
			$dir['modify'] = $file[5].' '.$file[6].' '.$file[7];
			if (preg_match('/^d/', $file[0])) {
				$dir['type'] = 'dir';
			}
			else {
				$dir['type'] = 'file';
			}

			$this->dirs[] = $dir;
		}

		return $this->dirs;
	}

	function getDirList($path) {
		$path = trim($this->path.'/'.$path, '/');

		//echo $path;

		$files = $this->ftp->ftp_list($path); //print_r($files);
		foreach ($files as $file) {
			unset($dir);
			$file = preg_split('/[\s]+/', $file);
			if (preg_match('/^d/', $file[0])) {
				$dir['path'] = $path;
				$dir['name'] = trim(implode(' ', array_slice($file, 8)));
				$dir['type'] = 'dir';
				$dir['size'] = $file[4];
				$dir['modify'] = $file[5].' '.$file[6].' '.$file[7];

				$this->dirs[] = $dir;
			}
		}

		return $this->dirs;
	}

	function getFileList($path) {
		$path = trim($this->path.'/'.$path, '/');

		//echo $path;

		$files = $this->ftp->ftp_list($path);
		foreach ($files as $file) {
			unset($dir);
			$file = preg_split('/[\s]+/', $file);
			if (!preg_match('/^d/', $file[0])) {
				$dir['path'] = $path;
				$dir['name'] = trim(implode(' ', array_slice($file, 8)));
				$dir['type'] = 'file';
				$dir['size'] = $file[4];
				$dir['modify'] = $file[5].' '.$file[6].' '.$file[7];

				$this->dirs[] = $dir;
			}
		}

		return $this->dirs;
	}
}