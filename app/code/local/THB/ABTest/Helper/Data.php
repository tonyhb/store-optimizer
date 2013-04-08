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

        return self::$_active_tests;
    }

    protected $_test;

    /**
     * Helper method to set the current test. Used for setting variation content 
     * from within templates:
     *
     *   if (Mage::helper('abtest')->test("Test Name")->is_variation("a"))
     *      // ... DO stuff for variation A here.
     *
     * @since 0.0.1
     * @return $this
     */
    public function test($test_name)
    {
        $this->_test = $test_name;
        return $this;
    }

    /**
     * Helper method to add variation content from within templates:
     *
     *   if (Mage::helper('abtest')->test("Test Name")->is_variation("a"))
     *      // ... DO stuff for variation A here.
     *
     * @since 0.0.1
     * @return bool
     */
    public function is_variation($variation_name)
    {
        if ( ! $this->_test)
            return FALSE;

        $test_id = 0;
        foreach (self::$_active_tests as $test) {
            if ($test['name'] == $this->_test) {
                $test_id = $test['id'];
                break;
            }
        }

        if ($test_id == 0)
            return FALSE;

        # Get the user's variation for the test ID
        if ( ! $variation = Mage::helper('abtest/visitor')->getVariation($test_id))
            return FALSE;

        if ($variation['variation']['name'] == $variation_name)
            return TRUE;

        return FALSE;
    }

}
