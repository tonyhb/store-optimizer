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
     * Initialises the model by loading all current A/B tests (and variations for 
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
        $table = Mage::getSingleton('core/resource')->getTableName('abtest/test');
        $all_tests = $read->fetchAll('SELECT * FROM '.$table.' WHERE (end_date >= '.date("Y-m-d").' OR end_date IS NULL) AND is_active = 1 ORDER BY id ASC');


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
        $table = Mage::getSingleton('core/resource')->getTableName('abtest/variation');
        $variations = $read->fetchAll('SELECT * FROM '.$table.' WHERE test_id IN ('.implode(',', $test_ids).') ORDER BY test_id ASC');

        # Add the variations to the tests in the active tests property
        foreach ($variations as $variation)
        {
            self::$_active_tests[$variation['test_id']]['variations'][] = $variation;
        }

        $this->_split_user_into_cohorts();
    }

    /**
     * Returns an array of all active tests and their variation.
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

            # We need to ge tthe table name that we're updating
            $table = Mage::getSingleton('core/resource')->getTableName('abtest/variation');

            $seed = mt_rand(1,100);

            # We need to loop through each variation to get the cumulative 
            # percentage - when the seed is lower than the cumulative 
            # percentage we're in that cohort.
            # IE: $seed = 20 and Version A takes 50%: We're in A (20 <=50)
            #     $seed = 92, Version A: 50%, Version b: 50%:
            #       We're in B (92 <= (50 + 50))
            $current_percentage = 0;
            foreach ($data['variations'] as $variation)
            {
                $current_percentage += $variation['split_percentage'];

                if ($seed <= $current_percentage)
                {
                    $cohort_data[$test_id] = $variation['id'];
                    $this->_sql_queries .= ' UPDATE `'.$table.'` SET visitors = visitors + 1 WHERE id = '.$variation['id'].'; ';
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
     * Returns variation information for a test. The test is found by passing the 
     * test's observer name. The data returned is in the format:
     *
     * @since 0.0.1
     *
     * @param  string   current test's observer name
     * @return array    array of variation information
     */
    public function get_variation_from_target($observer_name)
    {
        if ($variation = $this->_get_variation($observer_name, 'observer_target'))
        {
            # Get the table name for variation before we loop
            $table = Mage::getSingleton('core/resource')->getTableName('abtest/variation');

            $this->_sql_queries .= ' UPDATE `'.$table.'` SET views = views + 1 WHERE id = '.$variation['id'].'; ';

            return $variation;
        }

        return FALSE;
    }

    /**
     * Gets a variation from a conversion observer.
     *
     * @since 0.0.1
     *
     * @param  string   current test's observer name
     * @return array    array of variation information
     */
    public function get_variation_from_conversion($observer_name)
    {
        return $this->_get_variation($observer_name, 'observer_conversion');
    }

    /**
     * Returns variation information for a test. The test is found by passing the 
     * test's observer name and the type of observer you're looking for (either
     * a test page or a conversion event). The data returned is in the format:
     *
     * array(
     *   'id'               => $variation_id,
     *   'test_id'          => $test_id,
     *   'is_control'       => bool,  // Whether this is the standard template
     *   'layout_update'    => '...', // XML updates if not default template
     *   'split_percentage' => int,   // Percentage of people in this cohort
     *   'visitors'         => int,   // Number of visitors in $variation
     *   'views'            => int,   // Number of views in $variation_id
     *   'conversions'      => int,   // Number of conversions from $variation_id
     *   'total_value'      => float, // Total sales value from $variation_id,
     *   'is_winner'        => bool,
     * );
     *
     * @since 0.0.1
     *
     * @param string  The event name to find a variation for
     * @param enum    Either observer_target or observer_coversion, depending on 
     *                whether you're looking for a variation from a conversion 
     *                or a test page
     * @return array
     */
    protected function _get_variation($observer_name, $source = 'observer_target')
    {
        $session = Mage::getSingleton('core/session', array('name' => 'frontend'));

        foreach (self::$_active_tests as $test)
        {
            if ($test[$source] == $observer_name)
            {
                $cohort_id = $session['cohort_data'][$test['id']];

                foreach ($test['variations'] as $variation)
                {
                    if ($variation['id'] == $cohort_id)
                    {
                        return $variation;
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

        $cohort = $this->get_variation_from_target('catalog_controller_product_view');

        if ($cohort && $cohort['layout_update'])
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

        $cohort = $this->get_variation_from_conversion('checkout_onepage_controller_success_action');

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

            # Add a conversion row in our conversion table
            $table = Mage::getSingleton('core/resource')->getTableName('abtest/conversion');
            $write->insert($table, array(
                    'test_id'    => $cohort['test_id'],
                    'order_id'   => $order['entity_id'],
                    'variation_id' => $cohort['id'],
                    'value'      => $order['grand_total'],
                ));

            # Update the variation information
            $table = Mage::getSingleton('core/resource')->getTableName('abtest/variation');
            $write->query('UPDATE `'.$table.'` SET conversions = conversions + 1, total_value = '.
                ($cohort['total_value'] + $order['grand_total']).
                ' WHERE id = '.$cohort['id']);
        }
        catch (Exception $e) {
            # This is observer is run up to 6 times, and this is an error thrown by a unique constraint in the DB.
            # @TODO find out why it's run 6 times.
        }
    }

    /**
     * Registers a conversion when a user adds a product to their cart.
     *
     * @since 0.0.1
     *
     * @return void
     */
    public function add_product($observer)
    {
        # This runs every time a product is added, regardless of whether or not 
        # an AB test is running.
        if ( ! self::$_is_running)
            return;

        $cohort = $this->get_variation_from_conversion($observer->getEvent()->getName());

        # Get the price of our item. We don't use the product's price or final 
        # price because this may be a bundled or configurable product: these 
        # prices are stored in the custom option's buy request price.
        $buy_request = $observer->getEvent()->getProduct()->getCustomOptions();
        $price = $buy_request['info_buyRequest']->getItem()->getPrice() * $observer->getEvent()->getProduct()->getQty();

        # Get our table name for variations and update the row information
        $table = Mage::getSingleton('core/resource')->getTableName('abtest/variation');
        $write = Mage::getSingleton('core/resource')->getConnection('core/write');
        $write->query('UPDATE `'.$table.'` SET conversions = conversions + 1, total_value = '.
            ($cohort['total_value'] + $price).
            ' WHERE id = '.$cohort['id']);

        # Add a conversion row
        $table = Mage::getSingleton('core/resource')->getTableName('abtest/conversion');
        $write->insert($table, array(
                'test_id'      => $cohort['test_id'],
                'variation_id' => $cohort['id'],
                'value'        => $price,
                'created_at'   => date('Y-m-d H:i:s'),
            ));
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
