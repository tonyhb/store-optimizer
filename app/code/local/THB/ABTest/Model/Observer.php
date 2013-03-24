<?php

/**
 *
 * @author Tony Holdstock-Brown
 */
class THB_ABTest_Model_Observer {

    /**
     * Stores the visitor's cohort data in an array (which variation a user has)
     *
     * @since 0.0.1
     *
     * @var array
     */
    protected $_visitor_cohort_data;

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
        $helper = Mage::helper('abtest');

        if ($helper->getActiveTests() == array())
            return;

        Mage::helper('abtest/visitor')->assignVariations();
    }

    /**
     * We register visits on a daily basis so we can show accurate graphs on the 
     * view test page.
     *
     * A new visit must be registered when either:
     *   - A new visitor arrives on the site and is separated into a cohort
     *   - Each day a visitor returns to the website, even if they are already 
     *     separated into a cohort.
     *
     * @since 0.0.1
     *
     * @return void
     */
    protected function _register_visitor_hit($test_id, $variation_id)
    {
        $optimizer = Mage::helper('abtest/optimizer');

        $variation_table = Mage::getSingleton('core/resource')->getTableName('abtest/variation');
        $test_table      = Mage::getSingleton('core/resource')->getTableName('abtest/test');
        $hit_table       = Mage::getSingleton('core/resource')->getTableName('abtest/hit');

        $optimizer->addQuery('INSERT INTO `'.$hit_table.'` (`test_id`, `variation_id`, `date`, `visitors`) VALUES ('.$test_id.', '.$variation_id.', "'.date('Y-m-d').'", 1) ON DUPLICATE KEY UPDATE `views` = `views` + 1');
        $optimizer->addQuery('UPDATE `'.$variation_table.'` SET views = views + 1 WHERE id = '.$variation_id);
        $optimizer->addQuery('UPDATE `'.$test_table.'` SET views = views + 1 WHERE id = '.$test_id);
    }

    /**
     * Runs any time a target event happens, ie. a user visits a page we are 
     * testing. This gets the cohort information, then calls a method to 
     * manipulate any event information for the specific test.
     *
     * @since 0.0.1
     * 
     * @param Varien_Event_Observer
     * @return void
     */
    public function run_target_event($observer)
    {
        if ( ! Mage::helper('abtest')->getIsRunning())
            return;

        # Get our event name for the method we're running, plus the variation data 
        # for the event
        $event_name = $observer->getEvent()->getName();
        $variation = Mage::helper('abtest/visitor')->getVariationFromObserverName($event_name);

        # Register a hit for this variation/test
        $this->_register_visitor_hit($variation['test_id'], $variation['id']);

        # Call our event method to manipulate any data for the test
        call_user_func_array(array($this, '_run_'.$event_name), array($observer, $variation));

        # Run our SQL queries
        Mage::helper('abtest/optimizer')->runQueries();
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
     * @param array  Cohort information
     * @return void
     */
    protected function _run_catalog_controller_product_view($observer, $cohort)
    {
        if ($cohort && $cohort['layout_update'])
        {
            $custom_updates = $observer->getProduct()->getCustomLayoutUpdate();
            $observer->getProduct()->setCustomLayoutUpdate($custom_updates.$cohort['layout_update']);
        }
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
        if ( ! Mage::helper('abtest')->getIsRunning())
            return;

        $cohort = $this->get_visitors_variation_from_conversion('checkout_onepage_controller_success_action');

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

            // Add our conversions
            $this->_register_conversion($cohort, $order['grand_total'], $order['entity_id']);
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
        if ( ! Mage::helper('abtest')->getIsRunning())
            return;

        $variation = $this->get_visitors_variation_from_conversion($observer->getEvent()->getName());

        # Get the price of our item. We don't use the product's price or final 
        # price because this may be a bundled or configurable product: these 
        # prices are stored in the custom option's buy request price.
        $buy_request = $observer->getEvent()->getProduct()->getCustomOptions();
        $price = $buy_request['info_buyRequest']->getItem()->getPrice() * $observer->getEvent()->getProduct()->getQty();

        # Get our table name for variations and update the row information
        $this->_register_conversion($variation, $price);
    }

    /**
     * Run from conversion obsers: this updates the test and variation 
     * conversion totals, plus adds a conversion entry to the database.
     *
     * @since 0.0.1
     *
     * @param array  Variation information
     * @param float  Value of conversion
     * @param int    The entity ID of the order, if this came from a sale
     * @return void
     */
    protected function _register_conversion($variation, $value = 0, $order_id = 'NULL')
    {
        $write = Mage::getSingleton('core/resource')->getConnection('core/write');

        # Get our table name for variations and update the row information
        $conversion_table = Mage::getSingleton('core/resource')->getTableName('abtest/conversion');
        $variation_table  = Mage::getSingleton('core/resource')->getTableName('abtest/variation');
        $test_table       = Mage::getSingleton('core/resource')->getTableName('abtest/test');

        # Update the variation and test row counters
        # In the variation table we need to update the conversion rate - if we 
        # don't include it we can't order by conversion rates in the "view test" 
        # table.
        $query  = 'UPDATE `'.$variation_table.'` SET conversions = conversions + 1, total_value = total_value + '.$value.', conversion_rate = ((conversions / visitors) * 100) WHERE id = '.$variation['id'].'; ';
        $query .= 'UPDATE `'.$test_table.'` SET conversions = conversions + 1 WHERE id = '.$variation['test_id'].'; ';
        $query .= 'INSERT INTO `'.$conversion_table.'` (test_id, variation_id, order_id, value, created_at) VALUES ('.$variation['test_id'].', '.$variation['id'].', '.$order_id.', '.$value.', "'.date('Y-m-d H:i:s').'"); ';

        $write->query($query);
    }

}
