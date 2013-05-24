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
    public static function isRunning()
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

    /**
     * Stores the test name for cohort checks
     *
     * @param string
     */
    protected $_test;

    /**
     * Helper method to set the current test. Used for setting variation content 
     * from within templates:
     *
     *   if (Mage::helper('abtest')->test("Test Name")->isVariation("a"))
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
     *   if (Mage::helper('abtest')->test("Test Name")->isVariation("a"))
     *      // ... DO stuff for variation A here.
     *
     * @since 0.0.1
     * @return bool
     */
    public function isVariation($variation_name)
    {
        # If we're previewing a variation we don't need to load active tests 
        # - we can use the preview information saved in a cookie. 
        if ($preview_data = Mage::helper('abtest/visitor')->getPreview() AND $preview_data['test_name'] == $this->_test)
        {
            if ($preview_data['variation_name'] == $variation_name)
                return TRUE;
            else
                return FALSE;
        }

        if ( ! $variation = $this->_getTestVariation())
            return FALSE;

        if ($variation['variation']['name'] == $variation_name)
            return TRUE;

        return FALSE;
    }

    /**
     * Helper method to determine whether a user is in the control cohort.
     *
     * Note that it is **far better** to use `isVariation()` to detect each 
     * variation's name than use isControl, because isControl only returns TRUE 
     * when the test is active, meaning code wrapped in an isControl block may 
     * not be executed by default.
     *
     * @since 0.0.1
     * @return bool
     **/
    public function isControl()
    {
        # If we're previewing a variation we don't need to load active tests 
        # - we can use the preview information saved in a cookie. 
        if ($preview_data = Mage::helper('abtest/visitor')->getPreview() AND $preview_data['test_name'] == $this->_test)
        {
            if ($preview_data['is_control'])
                return TRUE;
            else
                return FALSE;
        }

        if ( ! $variation = $this->_getTestVariation())
            return FALSE;

        if ($variation['variation']['is_control'])
            return TRUE;

        return FALSE;
    }

    /**
     * Returns the current variation's name as a string. This is used to make 
     * a switch containing different functionality per variation.
     *
     * @since 0.0.1
     * @return string|boolean  String of the variation name or FALSE if the test 
     *                         isn't running
     */
    public function getVariationName()
    {
        if ($preview_data = Mage::helper('abtest/visitor')->getPreview() AND $preview_data['test_name'] == $this->_test)
            return $preview_data['variation_name'];

        if ( ! $variation = $this->_getTestVariation())
            return FALSE;

        return $variation['variation']['name'];
    }

    /**
     * Returns the variation for the specified test.
     *
     * Note that the test must be set by calling the `test()` method beforehand.
     *
     * @return THB_ABTest_Model_Test|boolean
     */
    protected function _getTestVariation()
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

        # We can't load the variation; this should never happen.
        if ( ! $variation = Mage::helper("abtest/visitor")->getVariation($test_id))
            return FALSE;

        return $variation;
    }

}
