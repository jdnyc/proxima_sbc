<?php
/**
 * Created by PhpStorm.
 * User: cerori
 * Date: 2015-01-16
 * Time: 오전 12:57
 */

use Carbon\Carbon;
use Seld\JsonLint\JsonParser;

/**
 * 파라메터 검증
 * @param $definition
 * @param $json_str
 * @throws Exception
 */
function validator($definition, $json_str) {

	$result = jsonValidate($json_str);
	if ($result != null) throw new Exception($result->getMessage());

    $data = json_decode($json_str, true);
	if ( ! $data)  throw new Exception('파라메터 오류 - '.$json_str);
//    if ( ! is_array($data)) throw new Exception('파라메터 오류 - '.$data);

    foreach ($definition as $k => $condition) {
        if ($condition['required'] && ! array_key_exists($k, $data)) throw new Exception($k. ' 필수 값입니다.');
        if ($condition['type'] == 'date' && $condition['format']) {
            try {
                Carbon::createFromFormat($condition['format'], $data[$k]);
            } catch (Exception $e) {
                throw new Exception($k . '('.$data[$k].') 날짜형식이 맞지 않습는다.('.$condition['format']);
            }
        }
    }

	return $data;
}

function jsonValidate($json) {
	$parser = new JsonParser();

	return $parser->lint($json);
}
