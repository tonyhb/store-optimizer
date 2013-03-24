<?php

class THB_ABTest_Helper_Data extends Mage_Core_Helper_Data {

    /**
     * Stores whether an A/B test is running
     *
     * @since 0.0.1
     *
     * @var bool
     */
    protected static $_is_running = FALSE;

    /**
     * Returns whether any A/B tests are running or not.
     *
     * @since 0.0.1
     *
     * @api
     * @return array
     */
    public static function getIsRunning()
    {
        return self::$_is_running;
    }

    /**
     * Stores an array of all active tests
     *
     * @since 0.0.1
     *
     * @var array
     */
    protected static $_active_tests = NULL;

    /**
     * Returns an array of all active tests and their variations. We use this 
     * method instead of the test collection's method because this cache's the 
     * query result, optimising performance of the A/B test by decreasing 
     * queries.
     *
     * @since 0.0.1
     *
     * @api
     * @return array
     */
    public static function getActiveTests()
    {
        # We must already have initialised active tests, because it wouldn't be 
        # NULL otherwise. We can safely return our property
        if (self::$_active_tests !== NULL)
            return self::$_active_tests;

        self::$_active_tests = Mage::getModel('abtest/test')->getCollection()->getActiveTests();

        if (self::$_active_tests != array())
        {
            self::$_is_running = TRUE;
        }

        return self::$_is_running;
    }

}
