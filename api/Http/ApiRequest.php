<?php

namespace Api\Http;

use Api\Application;
use Slim\Http\Request;
use Illuminate\Support\Arr;

class ApiRequest extends Request
{
    /**
     * 기본 페이지 당 결과 수
     */
    const DEFAULT_SIZE = 30;

    /**
     * 예약된 파라메터
     */
    const KEYWORDS = [
        'start',
        'limit',
        'sort',
        'dir',
        '_dc',
        'includes'
    ];

    /**
     * 페이지네이션 시작 위치
     *
     * @var int
     */
    public $start;

    /**
     * 페이지네이션 조회 개수
     *
     * @var int
     */
    public $limit;

    /**
     * 정렬필드
     *
     * @var string
     */
    public $sort;

    /**
     * 정렬방향(asc, desc)
     *
     * @var string
     */
    public $dir;

    /**
     * 조회 시 포함할 관계
     *
     * @var array
     */
    public $includes = null;

    /**
     * 요청 입력
     *
     * @var array
     */
    protected $input;

    public static function createFromEnvironment(\Slim\Http\Environment $environment)
    {
        $request = parent::createFromEnvironment($environment);
        $request->parse();
        $request->input = $request->exceptKeywords($request->input);
        // is_deleted에 대한 공통 처리
        if (is_array($request->input) && array_key_exists('is_deleted', $request->input)) {
            $request->input['is_deleted'] = yn_to_bool($request->input['is_deleted']);
        }
        return $request;
    }

    protected function parse()
    {
        $this->start = (int) $this->input('start', 0);
        $this->limit = (int) $this->input('limit', self::DEFAULT_SIZE);
        $this->sort = $this->input('sort');
        $this->dir = $this->input('dir');
        $this->includes = $this->parseIncludes();
    }

    /**
     * 관계 포함 파라메터 유무
     *
     * @return boolean
     */
    public function hasIncludes()
    {
        return is_null($this->includes);
    }

    /**
     * 특정 입력 값 반환. key가 null 이면 모든 입력값 반환
     *
     * @param string|null $key 입력값 키
     * @param mixed|null $default 입력값이 null일때 기본값
     * @return mixed|array
     */
    public function input($key = null, $default = null)
    {
        if ($this->input === null) {
            $body = $this->getParsedBody();
            $params = $this->getQueryParams();
            $input = null;
            if (is_array($body)) {
                if (is_array($params)) {
                    $input = array_merge($body, $params);
                } else {
                    $input = $body;
                }
            } else {
                if (is_array($params)) {
                    $input = $params;
                }
            }

            $this->input = is_array($input) ? $input : [];
        }

        if ($key) {
            if (array_key_exists($key, $this->input)) {
                return $this->input[$key];
            } elseif ($default) {
                return $default;
            }
            return null;
        }
        return $this->input;
    }

    /**
     * 특정 입력값 배열 반환. keys에 입력된 key들에 대한 입력값 배열 반환. keys가 NULL이면 모든 입력값 반환
     *
     * @param array|null $keys
     * @return array
     */
    public function all(array $keys = null)
    {
        $input = $this->input();

        if (!$keys) {
            return $input;
        }

        $results = [];
        foreach (is_array($keys) ? $keys : func_get_args() as $key) {
            if (array_key_exists($key, $input)) {
                $results[$key] = $input[$key];
            }
        }

        return $results;
    }

    /**
     * 특정 입력값만 리턴
     *
     * @param array|mixed $keys
     * @return array
     */
    public function only(array $keys)
    {
        $keys = is_array($keys) ? $keys : func_get_args();
        return $this->all($keys);
    }

    /**
     * 특정 파라메터들을 제외한 결과 리턴
     *
     * @param mixed|array $keys
     * @return void
     */
    public function except($keys)
    {
        $input = $this->all();
        if (is_array($keys)) {
            foreach ($keys as $key) {
                if (array_key_exists($key, $input)) {
                    unset($input[$key]);
                }
            }
        } else {
            if (array_key_exists($key, $input)) {
                unset($input[$key]);
            }
        }
        return $input;
    }

    /**
     * 관계를 같이 조회 할 경우 조회 할 관계 리턴
     *
     * @return string[]|null
     */
    private function parseIncludes()
    {
        $includes = $this->input('includes');
        if (!is_array($includes)) {
            return null;
        }

        $includeArray = explode(',', str_replace(' ', '', $includes[0]));
        $result = [];
        foreach ($includeArray as $include) {
            $result[] = $include;
        }

        return $result;
    }

    /**
     * start, limit, sort, dir 같은 예약 파라메터 제거
     * 
     * @return string[]|null
     */
    private function exceptKeywords($params)
    {
        if (!is_array($params)) {
            return null;
        }

        foreach (self::KEYWORDS as $keyword) {
            if (array_key_exists($keyword, $params)) {
                unset($params[$keyword]);
            }
        }
        return $params;
    }
}
