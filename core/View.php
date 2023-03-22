<?php

namespace Proxima\core;

class View
{
	/**
	 * Location of expected template
	 *
	 * @var string
	 */
	private $viewsDir;

	/**
	 * constructor
	 *
	 * @param [type] $viewsDir
	 */
	public function __construct($viewsDir = null)
	{
		if ($viewsDir) {
			$this->viewsDir = $viewsDir;
		} else {
			$ds = DIRECTORY_SEPARATOR;
			$viewsDir = dirname(__DIR__) . $ds . 'views';
			$this->viewsDir = $viewsDir;
		}
	}

	/**
	 * render view
	 *
	 * @param string|array $viewName
	 * @param array $variables
	 * @return void
	 */
	public function render($viewName, $variables = array())
	{
		$renderData = $this->getRenderData($viewName, $variables);
		if ($renderData !== false) {
			echo $renderData;
		}
	}

	/**
	 * render view
	 *
	 * @param string|array $viewName
	 * @param array $variables
	 * @return mixed
	 */
	public function getRenderData($viewName, $variables = array())
	{
		$template = $this->findTemplate($viewName);
		if ($template) {
			$output = $this->renderTemplate($template, $variables);
			return $output;
		}
		return false;
	}

	/**
	 * Look for the first template suggestion
	 *
	 * @param $viewName
	 *
	 * @return bool|string
	 */
	private function findTemplate($viewName)
	{
		if (!is_array($viewName)) {
			$viewName = array($viewName);
		}
		$viewName = array_reverse($viewName);
		$found = false;
		foreach ($viewName as $viewFileName) {
			$ds = DIRECTORY_SEPARATOR;
			$file = $this->viewsDir . $ds . $viewFileName . '.php';
			if (file_exists($file)) {
				$found = $file;
				break;
			}
		}
		return $found;
	}
	/**
	 * Execute the template by extracting the variables into scope, and including
	 * the template file.
	 *
	 * @internal param $template
	 * @internal param $variables
	 *
	 * @return string
	 */
	private function renderTemplate( /*$template, $variables*/)
	{
		$args = func_get_args();
		ob_start();
		foreach ($args[1] as $key => $value) {
			${$key} = $value;
		}
		include $args[0];
		return ob_get_clean();
	}

	/**
	 * 스크립트 파일을 읽어서 문자열로 리턴함
	 *
	 * @param string $scriptPath 스크립트 경로(프로젝트 루트 기준 절대경로)
	 * @param array $variables 변수
	 * @return string
	 */
	public static function getScriptData($scriptPath, $variables = [])
	{
		$path = dirname(__DIR__) . '/' . $scriptPath;
		if (!file_exists($path)) {
			return false;
		}
		ob_start();
		include $path;
		$ret = ob_get_clean();

		if ($variables && is_array($variables)) {
			foreach ($variables as $k => $v) {
                if(is_bool($v)) {
                    $v = $v ? 'true' : 'false';
				} else if(is_string($v)) {
                    $v = "'{$v}'";
				}
				$ret = str_replace("{{{$k}}}", "{$v}", $ret);
			}
		}
		return $ret;
	}
}
