<?php

/**
 *
 * @author Tony Holdstock-Brown
 */
class THB_ABTest_Model_Observer {

    /**
     * Initialises the model by loading all current A/B tests (and variations for 
     * the tests) and splitting visitors into cohorts.
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

        Mage::helper('abtest/visitor')->assignVariations();
    }

    /**
     * Runs any time a target event happens, ie. a user visits a page we are 
     * testing. This gets the cohort information, then calls a method to 
     * manipulate any event information for the specific test.
     *
     * @since 0.0.1
     * 
     * @param Varien_Event_Observer
     * @param string  Event to match in the test's observer_target field
     * @return void
     */
    public function run_target_event($observer, $event_name = NULL)
    {
        # If we're previewing layout update XML we need to skip loading 
        # a variation. This will return FALSE and run the inner block if there's 
        # no preview.
        $layout_update = Mage::helper('abtest/visitor')->getPreviewXml($event_name);

        if ($layout_update === FALSE)
        {
            # There's no preview, so if we're not running any tests just quit.
            if ( ! Mage::helper('abtest')->isRunning())
                return;

            # Load the variation for the current module/controller/action event 
            # combination, then log a hit.
            if ( ! $variation = Mage::helper('abtest/visitor')->getVariationFromObserverName($event_name))
                return;

            # Only register a hit if this isn't a 404...
            if ($event_name != 'cms_index_noRoute') {
                $this->_register_visitor_hit($variation['test_id'], $variation['id']);
            }

            $layout_update = $variation['layout_update'];
        }

        # Call our event method to manipulate any data for the test
        $this->_inject_xml($observer, $layout_update);

        # Run our SQL queries
        Mage::helper('abtest/optimizer')->runQueries();
    }

    protected function _inject_xml($observer, $layout_update)
    {
        if ($layout_update)
            $observer->getLayout()->getUpdate()->addUpdate($layout_update);
    }

    /**
     * This event is run before any XML is generated on the front end of the 
     * website, regardless of the controller or action. This allows us to hook 
     * into controllers such as the cart which don't have event hooks naturally.
     *
     * We also use this event to add the preview bar across all pages on the 
     * front of the website.
     *
     * @since 0.0.1
     * @return void
     */
    public function event_generate_xml($observer)
    {
        # Add Google Analytics integration, if need be. This is controlled by 
        # the settings in the configuration panel - the block will not output 
        # anything if the integration is disabled.
        $observer->getEvent()->getLayout()->getUpdate()->addUpdate('<reference name="before_body_end"><block name="abtest_ga" type="abtest/analytics" /></reference><reference name="head"><action method="addJs"><script>abtest/abtest.core.js</script></action></reference>');

        # Add the preview bar, if need be.
        if ($data = Mage::getSingleton('core/cookie')->get('test_preview'))
        {
            $observer->getEvent()->getLayout()->getUpdate()->addUpdate('<reference name="after_body_start"><block type="core/template" template="abtest/preview.phtml" /></reference>');
        }

        $request = $observer->getAction()->getRequest();

        # Do we have a test for the cart page (ie. upsells?)
        $event_name = $request->getModuleName().'_'.$request->getControllerName().'_'.$request->getActionName();
        $this->run_target_event($observer, $event_name);
    }

    /**
     * Registers a conversion when a product is viewed. 
     *
     */
    public function conversion_product_view($observer)
    {
        # This runs every time a product is added, regardless of whether or not 
        # an AB test is running.
        if ( ! Mage::helper('abtest')->isRunning())
            return;

        if ($variation = Mage::helper('abtest/visitor')->getVariationFromObserverName($observer->getEvent()->getName(), 'observer_conversion'))
        {
            if ($variation == false)
                return;

            # Get our table name for variations and update the row information
            $this->_register_conversion($variation, $observer->getProduct()->getPrice());
        }
    }

    /**
     * Registers a conversion when a product is added to the wishlist
     *
     */
    public function conversion_wishlist_add_product($observer)
    {
        # This runs every time a product is added, regardless of whether or not 
        # an AB test is running.
        if ( ! Mage::helper('abtest')->isRunning())
            return;

        if ($variation = Mage::helper('abtest/visitor')->getVariationFromObserverName($observer->getEvent()->getName(), 'observer_conversion'))
        {
            # Get our table name for variations and update the row information
            $this->_register_conversion($variation, $observer->getProduct()->getPrice());
        }
    }

    /**
     * Registers a conversion when a product is sent to a friend
     *
     */
    public function conversion_send_product_to_friend($observer)
    {
        # This runs every time a product is added, regardless of whether or not 
        # an AB test is running.
        if ( ! Mage::helper('abtest')->isRunning())
            return;

        if ($variation = Mage::helper('abtest/visitor')->getVariationFromObserverName($observer->getEvent()->getName(), 'observer_conversion'))
        {
            # Get our table name for variations and update the row information
            $this->_register_conversion($variation, $observer->getProduct()->getPrice());
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
    public function conversion_onepage_success()
    {
        if ( ! Mage::helper('abtest')->isRunning())
            return;

        if ( ! $variation = Mage::helper('abtest/visitor')->getVariationFromObserverName('checkout_onepage_controller_success_action', 'observer_conversion'))
            return;

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
            $this->_register_conversion($variation, $order['grand_total'], $order['entity_id']);
        }
        catch (Exception $e) {
            # This is observer is run multiple times depending on the checkout 
            # method used. We can skip this silently.
        }
    }

    /**
     * Registers a conversion when a user adds a product to their cart.
     *
     * @since 0.0.1
     *
     * @return void
     */
    public function conversion_add_product($observer)
    {
        # This runs every time a product is added, regardless of whether or not 
        # an AB test is running.
        if ( ! Mage::helper('abtest')->isRunning())
            return;

        if ($variation = Mage::helper('abtest/visitor')->getVariationFromObserverName($observer->getEvent()->getName(), 'observer_conversion'))
        {
            # Get the price of our item. We don't use the product's price or final 
            # price because this may be a bundled or configurable product: these 
            # prices are stored in the custom option's buy request price.
            $buy_request = $observer->getEvent()->getProduct()->getCustomOptions();
            $price = $buy_request['info_buyRequest']->getItem()->getPrice() * $observer->getEvent()->getProduct()->getQty();
            # Get our table name for variations and update the row information
            $this->_register_conversion($variation, $price);
        }
    }

    /**
     * We register visits on a daily basis so we can show accurate graphs on the 
     * view test page.
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

        $optimizer->addQuery('INSERT INTO `'.$hit_table.'` (`test_id`, `variation_id`, `date`, `visitors`, `views`) VALUES ('.$test_id.', '.$variation_id.', "'.date('Y-m-d').'", 1, 1) ON DUPLICATE KEY UPDATE `views` = `views` + 1');
        $optimizer->addQuery('UPDATE `'.$variation_table.'` SET views = views + 1 WHERE id = '.$variation_id);
        $optimizer->addQuery('UPDATE `'.$test_table.'` SET views = views + 1 WHERE id = '.$test_id);
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
        # This shouldn't happen because each conversion event shouldn't run this 
        # if there's no variation, but it's better to be safe anyway.
        if ( ! $variation) return;

        # Are we only registering one conversion per visitor? If so, stop this 
        # if the visitor has already converted.
        if (Mage::getStoreConfig('abtest/settings/single_conversion') == '1') {
            $variation_data = Mage::helper('abtest/visitor')->getVariation($variation['test_id']);
            if ($variation_data['converted'])
                return;
        }

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

        Mage::helper('abtest/visitor')->registerConversion($variation['test_id']);
    }

}
