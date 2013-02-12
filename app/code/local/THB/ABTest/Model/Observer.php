<?php

/**
 *
 * @author Tony Holdstock-Brown
 */
class THB_ABTest_Model_Observer {

    /**
     * Stores whether an A/B test is running
     *
     * @since 0.0.1
     *
     * @var bool
     */
    protected static $_is_running = FALSE;

    /**
     * Stores an array of all active tests
     *
     * @since 0.0.1
     *
     * @var array
     */
    protected static $_active_tests = array();

    /**
     * Stores the core session model for the visitor
     *
     * @since 0.0.1
     *
     * @var Mage_Core_Model_Session
     */
    protected $_session;

    /**
     * This stores all of the queries made by the user's request and executes 
     * them at once after the observer has finished processing the event.
     *
     * This is for (minute) optimisation - it's quicker to make one request than 
     * two/three/four etc.
     *
     * @see THB_ABTest_Model_Observer::_run_queries() which runs these queries
     * @var string
     *
     * @since 0.0.1
     */
    protected $_sql_queries = '';

    /**
     * Initialises the model by loading all current A/B tests (and variants for 
     * the tests) and splitting visitors into cohorts.
     *
     * @uses THB_ABTest_Model_Observer::_split_user_into_cohorts()
     *
     * @since 0.0.1
     */
    public function __construct()
    {
        # Don't run any of this for bots
        if (Mage::helper('abtest/bots')->isBot())
            return;

        # Find out if we've got any tests running.
        $read = Mage::getSingleton('core/resource')->getConnection('core/read');
        $all_tests = $read->fetchAll('SELECT * FROM abtest WHERE (end_date >= '.date("Y-m-d").' OR end_date IS NULL) AND is_active = 1 ORDER BY id ASC');

        if (empty($all_tests))
            return;

        # There are active tests running
        self::$_is_running = TRUE;

        if ( ! $this->_session)
        {
            # Ensure we have the user's session data
            $this->_session = Mage::getSingleton('core/session', array('name' => 'frontend'));
        }

        # Find all test IDs and add the tests to the active tests property, 
        # using the test ID as the array key
        $test_ids = array();
        foreach ($all_tests as $test)
        {
            self::$_active_tests += array(
                $test['id'] => $test + array('variations' => array())
            );

            $test_ids[] = $test['id'];
        }

        # Find all variations
        $variations = $read->fetchAll('SELECT * FROM abtest_variation WHERE test_id IN ('.implode(',', $test_ids).') ORDER BY test_id ASC');

        # Add the variations to the tests in the active tests property
        foreach ($variations as $variant)
        {
            self::$_active_tests[$variant['test_id']]['variations'][] = $variant;
        }

        $this->_split_user_into_cohorts();
    }

    /**
     * Returns an array of all active tests and their variants.
     *
     * @since 0.0.1
     *
     * @api
     * @return array
     */
    public static function getActiveTests()
    {
        return self::$_active_tests;
    }

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
     * Splits a user into cohorts for currently active tests. This updates the 
     * session data used in Magento.
     *
     * @since 0.0.1
     *
     * @return void
     */
    protected function _split_user_into_cohorts()
    {
        # We only want to write session data if the user has new cohort 
        # information - we don't want to write the same session data each time 
        # an event fires.
        $_session_data_has_changed = FALSE;

        $cohort_data = $this->_session->getCohortData();

        if ( ! $cohort_data)
        {
            # They're not in any cohorts - create an empty array.
            $cohort_data = array();
        }

        foreach (self::$_active_tests as $test_id => $data)
        {
            # Force the user into a cohort, if possible
            if (isset($_GET['_abtest_'.$test_id]))
            {
                $_session_data_has_changed     = TRUE;
                $cohort_data[$test_id] = $_GET['_abtest_'.$test_id];
                continue;
            }

            # User has already been segmented for this test
            if (isset($cohort_data[$test_id]))
                continue;

            # We've udpated the session data with the below code and need 
            # to save it
            $_session_data_has_changed = TRUE;

            $seed = mt_rand(1,100);

            # We need to loop through each variation to get the cumulative 
            # percentage - when the seed is lower than the cumulative 
            # percentage we're in that cohort.
            # IE: $seed = 20 and Version A takes 50%: We're in A (20 <=50)
            #     $seed = 92, Version A: 50%, Version b: 50%:
            #       We're in B (92 <= (50 + 50))
            $current_percentage = 0;
            foreach ($data['variations'] as $variant)
            {
                $current_percentage += $variant['split_percentage'];

                if ($seed <= $current_percentage)
                {
                    $cohort_data[$test_id] = $variant['id'];
                    $this->_sql_queries .= ' UPDATE `abtest_variation` SET visitors = visitors + 1 WHERE id = '.$variant['id'].'; ';
                    break;
                }
            }
        }

        if ($_session_data_has_changed)
        {
            $this->_sql_queries = trim($this->_sql_queries);
            if ($this->_sql_queries && $this->_sql_queries != '')
            {
                $write = Mage::getSingleton('core/resource')->getConnection('core/write');
                $write->query($this->_sql_queries);
                $this->_sql_queries = '';
            }

            $this->_session->setCohortData($cohort_data);
        }
    }

    /**
     * Returns variant information for a test. The test is found by passing the 
     * test's observer name. The data returned is in the format:
     *
     * array(
     *   'id'                  => $variant_id,
     *   'test_id'             => $test_id,
     *   'is_default_template' => bool,  // Whether this is the standard template
     *   'layout_update'       => '...', // XML updates if not default template
     *   'split_percentage'    => int,   // Percentage of people in this cohort
     *   'visitors'            => int,   // Number of visitors in $variant_id
     *   'views'               => int,   // Number of views in $variant_id
     *   'conversions'         => int,   // Number of conversions from $variant_id
     *   'total_value'         => float, // Total sales value from $variant_id,
     *   'is_winner'           => bool,
     * );
     *
     * @since 0.0.1
     *
     * @param  string   current test's observer name
     * @return array    array of variant information
     */
    public function get_variant($observer_target)
    {
        $session = Mage::getSingleton('core/session', array('name' => 'frontend'));

        foreach (self::$_active_tests as $test)
        {
            if ($test['observer_target'] == $observer_target)
            {
                $cohort_id = $session['cohort_data'][$test['id']];

                foreach ($test['variations'] as $variant)
                {
                    if ($variant['id'] == $cohort_id)
                    {
                        # This has a view: add an SQL query to the query log 
                        # to run
                        $this->_sql_queries .= ' UPDATE abtest_variation SET views = views + 1 WHERE id = '.$variant['id'].'; ';
                        return $variant;
                    }
                }
            }
        }

        return FALSE;
    }

    /**
     * Updates the product page's layout using Custom Layout Updates 
     * based upon the visitor's cohort.
     *
     * This is used for A/B testing product page designs.
     *
     * @since 0.0.1
     *
     * @param Varien_Event_Observer
     * @return void
     */
    public function product_view($observer)
    {
        if ( ! self::$_is_running)
            return;

        $cohort = $this->get_variant('catalog_controller_product_view');

        if ($cohort && ! $cohort['is_default_template'])
        {
            $custom_updates = $observer->getProduct()->getCustomLayoutUpdate();
            $observer->getProduct()->setCustomLayoutUpdate($custom_updates.$cohort['layout_update']);
        }

        $this->_run_queries();
    }

    /**
     * Registers a conversion when a user successfully purchases via the onepage 
     * checkout
     *
     * @since 0.0.1
     *
     * @return void
     */
    public function onepage_success()
    {
        if ( ! self::$_is_running)
            return;

        $cohort = $this->get_variant('catalog_controller_product_view');

        if (Mage::getConfig() !== NULL)
        {
            # Get the last order ID. Sometimes getSingleton doesn't work because Mage isn't configured.
            # Strange: this only happens from a callback from SagePay and may be something to do with
            # that module.
            $incrementId = Mage::getSingleton('checkout/session')->getLastRealOrderId();
        }
        else
        {
            $session = new Mage_Checkout_Model_Session;
            $incrementId = $session->getLastRealOrderId();
        }

        if ($incrementId == NULL)
            return;

        try
        {
            $read  = Mage::getSingleton('core/resource')->getConnection('core/write');
            $order = $read->fetchRow('SELECT entity_id, grand_total FROM sales_flat_order WHERE increment_id = '.$incrementId);

            $write = Mage::getSingleton('core/resource')->getConnection('core/write');
            $write->insert('abtest_variation_order', array(
                    'order_id'   => $order['entity_id'],
                    'variant_id' => $cohort['id'],
                ));

            $write->query('UPDATE `abtest_variation` SET conversions = conversions + 1, total_value = '.
                ($cohort['total_value'] + $order['grand_total']).
                ' WHERE id = '.$cohort['id']);
        }
        catch (Exception $e) {
            # This is observer is run up to 6 times, and this is an error thrown by a unique constraint in the DB.
            # @TODO find out why it's run 6 times.
        }
    }

    /**
     * Write all of the logged queries to the database at the end of the 
     * observer's lifespan.
     *
     * @since 0.0.1
     *
     * @return void
     */
    public function _run_queries()
    {
        $this->_sql_queries = trim($this->_sql_queries);
        if ($this->_sql_queries && $this->_sql_queries != '')
        {
            $write = Mage::getSingleton('core/resource')->getConnection('core/write');
            $write->query($this->_sql_queries);
        }
    }

}
