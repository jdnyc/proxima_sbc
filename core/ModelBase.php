<?php
namespace Proxima\core;

use Proxima\core\Session;

if(!defined('DS'))
define('DS', DIRECTORY_SEPARATOR);
require_once(dirname(__DIR__) . DS . 'lib'. DS .'config.php');

Session::init();

require_once(dirname(__DIR__) . DS . 'lib'. DS .'functions.php');

/**
 * Proxima Model base class 
 */
class ModelBase
{   
    private static $lastQuery;

    protected $data;   
    protected $dataChanged = false;

    protected static $db;
        
    /**
     * constructor
     *
     * @param array $data db field/value array
     */
    public function __construct($data)
    {
        if(is_array($data)) {
            $this->data = $data; 
        }     
    }

    /**
     * 데이터베이스 클래스 setter
     *
     * @param Object $db database 클래스 인스턴스
     * @return void
     */
    protected static function setDatabase($db)
    {
        self::$db = $db;
    }

    /**
     * 데이터베이스 객체 획득
     *
     * @return Object database 클래스 인스턴스
     */
    protected static function getDatabase()
    {
        global $db;
        // 과거 코드 호환성
        if(empty(self::$db)) {
            self::$db = $db;
        }
        return self::$db;
    }

    /**
     * 목록 형태를 반환해야 할 경우 자주 중복되는 패턴을 매서드로 만든것으로
     * 쿼리와 클래스명을 주면 클래스 인스턴스의 배열로 반환한다.
     *
     * @param string $query 실행할 쿼리
     * @param string $className 인스턴스를 생성할 클래스
     * @param object $db 데이터베이스 인스턴스
     * @return array array of Object
     */
    protected static function queryCollection($query, $className, $db = null)
    {
        if ($db === null) {
            $db = self::getDatabase();
        }

        if (empty($query)) {
            throw new \Exception('$query is empty.');
        }
        if (empty($className)) {
            throw new \Exception('$className is empty.');
        }
        $rows = $db->queryAll($query);
        if(empty($rows))
            return [];

        $collection = [];
        foreach($rows as $row) {
            $collection[] = new $className($row);
        }

        return $collection;
    }

    /**
     * 오브젝트 형태를 반환해야 할 경우 자주 중복되는 패턴을 매서드로 만든것으로
     * 쿼리와 클래스명을 주면 클래스 인스턴스로 반환한다.
     *
     * @param string $query 실행할 쿼리
     * @param string $className 인스턴스를 생성할 클래스
     * @param object $db 데이터베이스 인스턴스
     * @return object Object
     */
    protected static function queryObject($query, $className, $db = null)
    {
        if ($db === null) {
            $db = self::getDatabase();
        }
        $row = $db->queryRow($query);
        if(empty($row))
            return null;

        return new $className($row);
    }

    /**
     * check model data empty
     *
     * @return boolean
     */
    public function isEmpty()
    {
        return empty($this->data);
    }

    /**
     * gets model data
     *
     * @param string $field
     * @return mixed value
     */
    public function get($field)
    {
        // var_dump($this->data);
        return $this->data[$field];
    }

    /**
     * check field exists
     *
     * @param string $field
     * @return boolean
     */
    public function fieldExists($field)
    {
        return array_key_exists($field, $this->data);
    }

    /**
     * sets model data
     *
     * @param string $field
     * @param mixed $value
     * @return void
     */
    public function set($field, $value)
    {
        $this->data[$field] = $value;
        $this->dataChanged = true;
    }

    /**
     * get all data
     *
     * @return array db field/value array
     */
    public function getAll()
    {
        return $this->data;
    }

    /**
     * insert data to db
     *
     * @param string $table table name
     * @param array $data the data that insert to db
     * @param object $db 데이터베이스 인스턴스
     * @return mixed
     */
    public static function insert($table, $data, $sequeneName = null, $db = null)
    {
        if ($db === null) {
            $db = self::getDatabase();
        }
        
        self::$lastQuery = $db->insert($table, $data);
        if(!empty($sequeneName)) {
            $id = $db->queryOne("SELECT currval('{$sequeneName}')");
            return $id;
        }
    }

    /**
     * update data to db
     *
     * @param string $table table name
     * @param array $data the data that insert to db
     * @param string $where the data that insert to db
     * @param object $db 데이터베이스 인스턴스
     * @return void
     */
    public static function update($table, $data, $where, $db = null)
    {
        if ($db === null) {
            $db = self::getDatabase();
        }

        if (empty($where)) {
            throw \Exception('where syntax is empty.');
        }
        self::$lastQuery = $db->update($table, $data, $where);        
    }

    /**
     * delete data to db
     * @param string $table table name     
     * @param string $where the data that insert to db
     */
    public static function delete($table, $where)
    {        
        if (empty($where)) {
            throw \Exception('where syntax is empty.');
        }
        $query = "DELETE FROM {$table} WHERE {$where}";
        self::$lastQuery = $query;
        $result = self::exec($query);    
    }

    public static function queryOne($query, $db = null)
    {
        if ($db === null) {
            $db = self::getDatabase();
        }

        self::$lastQuery = $query;
        $value = $db->queryOne($query);
        return $value;
    }    

    public static function queryRow($query, $db = null)
    {
        if ($db === null) {
            $db = self::getDatabase();
        }

        self::$lastQuery = $query;
        $row = $db->queryRow($query);        
        return $row;
    }

    public static function queryAll($query, $db = null)
    {
        if ($db === null) {
            $db = self::getDatabase();
        }

        self::$lastQuery = $query;
        $rows = $db->queryAll($query);        
        return $rows;
    }

    public static function exec($query, $db = null)
    {
        if ($db === null) {
            $db = self::getDatabase();
        }

        self::$lastQuery = $query;
        $result = $db->exec($query);        
        return $result;
    }

    public static function getLastQuery()
    {
        return self::$lastQuery;
    }
}