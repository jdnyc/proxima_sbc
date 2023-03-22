<?php

namespace Api\Core\Session;

use Carbon\Carbon;
use Illuminate\Support\Arr;
use SessionHandlerInterface;
use Illuminate\Database\QueryException;
use Illuminate\Support\InteractsWithTime;
use Illuminate\Database\ConnectionInterface;

class DatabaseSessionHandler implements SessionHandlerInterface
{
    use InteractsWithTime;
    
    /**
     * 데이터베이스 매니저
     *
     * @var Illuminate\Database\ConnectionInterface
     */
    protected $connection;    

    /**
     * 세션 테이블명
     *
     * @var string
     */
    protected $table;

    /**
     * 세션 유효 시간(분)
     *
     * @var int
     */
    protected $minutes;

    /**
     * 세션 존재 유무
     *
     * @var bool
     */
    protected $exists;

    public function __construct($connection, $table, $minutes) 
    {
        $this->connection = $connection;
        $this->table = $table;
        $this->minutes = $minutes;
    }

    /**
     * {@inheritdoc}
     */
    public function open ( $savePath , $sessionName )
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function close ()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function read ( $sessionId )
    {
        $session = (object) $this->getQuery()->find($sessionId);

        if ($this->expired($session)) {
            $this->exists = true;
            return '';
        }
        if (isset($session->payload)) {
            $this->exists = true;
            return base64_decode($session->payload);
        }
        return '';
    }

    /**
     * 세션 만료 여부 결정
     *
     * @param  \stdClass  $session
     * @return bool
     */
    protected function expired($session)
    {
        return isset($session->last_activity) &&
            $session->last_activity < Carbon::now()->subMinutes($this->minutes)->getTimestamp();
    }

    /**
     * {@inheritdoc}
     */
    public function write ( $sessionId , $sessionData )
    {
        $payload = $this->getDefaultPayload($sessionData);
        if (! $this->exists) {
            $this->read($sessionId);
        }

        if ($this->exists) {
            $this->performUpdate($sessionId, $payload);
        } else {
            $this->performInsert($sessionId, $payload);
        }
        return $this->exists = true;
    }    

    /**
     * Perform an insert operation on the session ID.
     *
     * @param  string  $sessionId
     * @param  mixed  $payload
     * @return bool|null
     */
    protected function performInsert($sessionId, $payload)
    {
        try {
            return $this->getQuery()->insert(Arr::set($payload, 'id', $sessionId));
        } catch (QueryException $e) {
            $this->performUpdate($sessionId, $payload);
        }
    }

    /**
     * Perform an update operation on the session ID.
     *
     * @param  string  $sessionId
     * @param  mixed  $payload
     * @return int
     */
    protected function performUpdate($sessionId, $payload)
    {
        return $this->getQuery()->where('id', $sessionId)->update($payload);
    }

    /**
     * Get the default payload for the session.
     *
     * @param  string  $data
     * @return array
     */
    protected function getDefaultPayload($data)
    {
        $payload = [
            'payload' => base64_encode($data),
            'last_activity' => $this->currentTime(),
        ];

        return $payload;
    }

    /**
     * {@inheritdoc}
     */
    public function destroy ( $sessionId )
    {
        $this->getQuery()->where('id', $sessionId)->delete();
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function gc ( $lifetime )
    {
        $this->getQuery()->where('last_activity', '<=', $this->currentTime() - $lifetime)->delete();
    }

    /**
     * Get a fresh query builder instance for the table.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function getQuery()
    {
        return $this->connection->table($this->table);
    }
}