<?php

namespace Tests\Groups;

class Base
{
    protected $_driver;
    protected $_session;

    public function setDriver($driver)
    {
        $this->_driver = $driver;
    }

    public function setSession($session)
    {
        $this->_session = $session;
    }
}
