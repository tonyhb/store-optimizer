<?php

namespace Tests;

use Behat\Mink\Mink,
    Behat\Mink\Session,
    Behat\Mink\Driver\ZombieDriver;

class Loader
{

    protected $_host = "127.0.0.1";
    protected static $_startUrl = '';
    protected static $_username = '';
    protected static $_password = '';

    protected $_testClasses = array(
        "Creation",
        // "Visitors",
        // "Conversions",
        // "Results",
    );

    protected $_driver  = NULL;
    protected $_session = NULL;

    /**
     * Calls _runTests on each of the classes in $_testClasses
     *
     * @return void
     */
    public function run($url, $user, $pass)
    {
        $this->setUrl($url)
            ->setUsername($user)
            ->setPassword($pass);

        # Initialize the Zombie driver and session once, before we create each class
        $driver  = $this->_initializeDriver();
        $session = $this->_initializeSession();

        foreach ($this->_testClasses as $testClass) {
            $className = "\Tests\\".$testClass;
            $class     = new $className;

            # Set the driver as Zombie.
            $class->setDriver($driver);
            $class->setSession($session);

            # Run our tests.
            $this->_runTests($class);
        }
    }

    /**
     * Initialize the Zombie driver
     *
     */
    protected function _initializeDriver()
    {
        if ($this->_driver !== NULL)
            return $this->_driver;

        $this->_driver = new \Behat\Mink\Driver\ZombieDriver($this->_host);

        return $this->_driver;
    }

    /**
     * Initializes the Mink session using the Zombie driver
     *
     */
    protected function _initializeSession()
    {
        if ($this->_session !== NULL)
            return $this->_session;

        $this->_session = new \Behat\Mink\Session($this->_initializeDriver());

        return $this->_session;
    }

    /**
     * Runs each test method inside the given class. Test methods must begin 
     * with "test".
     *
     * @return void
     */
    protected function _runTests($class)
    {
        $methods = get_class_methods($class);

        foreach ($methods as $method) {
            if (strpos($method, "test") === 0) {
                $class->{$method}();
            }
        }
    }

    public function setUrl($url)
    {
        self::$_startUrl = (string) $url;
        return $this;
    }

    public function setUsername($username)
    {
        self::$_username = (string) $username;
        return $this;
    }

    public function setPassword($password)
    {
        self::$_password = (string) $password;
        return $this;
    }

    public static function getUrl()
    {
        return self::$_startUrl;
    }

    public static function getUsername()
    {
        return self::$_username;
    }

    public static function getPassword()
    {
        return self::$_password;
    }

}
