<?php
class FilterFiles {
	private $files;
	private $result = array();

	function __construct($files) {
		$this->files = $files;
	}

	function getDirList() {
		foreach ($this->$files as $file) {
			$file = preg_split('/[\s]+/', $file);
			if ($file[0]{0} == 'd') {
				$this->result[] = $this->export($file);
			}
		}

		return $this->result;
	}

	function getFileList() {
		foreach ($this->$files as $file) {
			$file = preg_split('/[\s]+/', $file);
			if ($file[0]{0} == '-') {
				$this->result[] = $this->export($file);
			}
		}
	}

	function getAllList() {
		foreach ($this->$files as $file) {
			$file = preg_split('/[\s]+/', $file);
			$this->result[] = $this->export($file);
		}

		return $this->result;
	}

	function export($file_info) {
		return array(
			'name' => implode(' ', array_slice($file_info, 8)),
			'size' => $file_info[3],
			'mtime' => $file_info[4].' '.$file_info[5].' '.$file_info[6]
		);
	}
}
?>