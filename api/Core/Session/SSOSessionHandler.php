<?php

namespace Api\Core\Session;

use Api\Core\Session\DatabaseSessionHandler;

class SSOSessionHandler extends DatabaseSessionHandler
{   
    public function __construct($connection, $table = 'sessions') 
    {        
        parent::__construct($connection, $table, 0);        
    }    

    /**
     * {@inheritdoc}
     */
    public function destroy ( $sessionId )
    {        
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function gc ( $lifetime )
    {
        return true;
    }

    /**
     * 세션 만료 여부 결정
     *
     * @param  \stdClass  $session
     * @return bool
     */
    protected function expired($session)
    {
        return false;
    }

}