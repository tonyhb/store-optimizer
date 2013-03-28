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

        if ($helper->getActiveTests() == array())
            return;

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
        # Get our event name for the method we're running
        if ($event_name == NULL)
        {
            # Run from an observer - use the observer's name
            $event_name = $observer->getEvent()->getName();
        }

        # If we're previewing layout update XML we need to skip loading 
        # a variation. This will return FALSE and run the inner block if there's 
        # no preview.
        $layout_update = Mage::helper('abtest/visitor')->getPreviewXml($event_name);

        if ($layout_update === FALSE)
        {
            # Check if the test is running then, if so, load our variation and 
            # register a hit
            if ( ! Mage::helper('abtest')->getIsRunning())
                return;

            if ( ! $variation = Mage::helper('abtest/visitor')->getVariationFromObserverName($event_name))
            {

                # A test didn't exist for this event - probably because it was fired 
                # from the generate XML observer and we don't have a test running. 
                # We can quit here.
                return;
            }

            # Register a hit for this variation/test
            $this->_register_visitor_hit($variation['test_id'], $variation['id']);

            $layout_update = $variation['layout_update'];
        }

        # Call our event method to manipulate any data for the test
        call_user_func_array(array($this, '_run_'.$event_name), array($observer, $layout_update));

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
    protected function _run_catalog_controller_product_view($observer, $layout_update)
    {
        if ($layout_update)
        {
            $product = $observer->getProduct();

            # An admin can add custom layout updates to the product, so we need to 
            # be careful not to override them. They can also set dates that the 
            # custom layout update & custom theme work between - if the dates are in 
            # the past we need to remove the old settings, change the date and add 
            # only our custom layout update to get these to work.
            $custom_design_date = $product->getCustomDesignDate();
            if ($custom_design_date['to'] != NULL)
            {
                # Is the design date in the past? If so, remove all of the 
                # custom settings...
                if (strtotime($custom_design_date['to']) < time())
                {
                    $product->setCustomDesign('');
                    $product->setPageLayout(NULL);
                    $product->setCustomLayoutUpdate('');

                    # Set the date to tomorrow so our variation XML works
                    $product->setData('custom_design_to', date('Y-m-d H:i:s', strtotime('+1 day')));
                }
            }

            $custom_updates = $product->getCustomLayoutUpdate();
            $product->setCustomLayoutUpdate($custom_updates.$layout_update);
        }
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
    protected function _run_cms_page_render($observer, $layout_update)
    {
        if ($layout_update)
        {
            # Custom layout updates take precedence over standard layout 
            # updates. This means if we add to custom updates we'll stop any 
            # layout updates an admin may have added... not good.
            if ($custom_updates = $observer->getPage()->getCustomLayoutUpdateXml())
            {
                $observer->getPage()->setCustomLayoutUpdateXml($custom_updates.$layout_update);
            }
            else
            {
                $custom_updates = $observer->getPage()->getLayoutUpdateXml();
                $observer->getPage()->setLayoutUpdateXml($custom_updates.$layout_update);
            }
        }
    }

    protected function _run_catalog_controller_category_init_after($observer, $layout_update)
    {
        if ($layout_update)
        {
            $category = $observer->getCategory();
            # Categories use the parent (or root) category's settings by 
            # default. We've overriden the design model which configures layout 
            # updates for products and categories, so setting this property adds 
            # the variation XML to the category's design.
            $category->setData('_abtest_injected_xml', $layout_update);
        }
    }

    protected function _run_checkout_cart_index($observer, $layout_update)
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
        # Add the preview
        $observer->getEvent()->getLayout()->getUpdate()->addUpdate('<reference name="head"><action method="addJs"><script>abtest/abtest.core.js</script></action></reference>');
        if ($data = Mage::getSingleton('core/cookie')->get('test_preview'))
        {
            $observer->getEvent()->getLayout()->getUpdate()->addUpdate('<reference name="after_body_start"><block type="core/template" template="abtest/preview.phtml" /></reference>');
        }

        # Now we've added the preview we're moving on to adding XML overrides to 
        # other controllers without event hooks. We don't need to do this if 
        # there are no tests running.
        if ( ! Mage::helper('abtest')->getIsRunning())
            return;

        $request = $observer->getAction()->getRequest();

        if ($request->getModuleName() == 'checkout')
        {
            # Do we have a test for the cart page (ie. upsells?)
            $event_name = $request->getModuleName().'_'.$request->getControllerName().'_'.$request->getActionName();
            $this->run_target_event($observer, $event_name);
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

        $variation = Mage::helper('abtest/visitor')->getVariationFromObserverName('checkout_onepage_controller_success_action', 'observer_conversion');

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

        $variation = Mage::helper('abtest/visitor')->getVariationFromObserverName($observer->getEvent()->getName(), 'observer_conversion');

        # Get the price of our item. We don't use the product's price or final 
        # price because this may be a bundled or configurable product: these 
        # prices are stored in the custom option's buy request price.
        $buy_request = $observer->getEvent()->getProduct()->getCustomOptions();
        $price = $buy_request['info_buyRequest']->getItem()->getPrice() * $observer->getEvent()->getProduct()->getQty();

        # Get our table name for variations and update the row information
        $this->_register_conversion($variation, $price);
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
