<?php

/**
 * 
 *
 * @since 0.0.1
 */
class THB_ABTest_Helper_Visitor extends Mage_Core_Helper_Data
{
    const COOKIE_KEY = '3uI.Gd$QvHG},}(l';

    /**
     * Holds the user's variation settings for each test, in the format:
     *
     *   array(
     *     $test_id => array(
     *        'variation' => $variation_id,
     *        'last_seen' => $date_last_seen,
     *     ),
     *     ...
     *   )
     *
     * @since 0.0.1
     * @var array
     */
    protected static $_variations = NULL;

    /**
     * Is this user a new or returning visitor?
     *
     * This is found out by looking for hte 'cohort_data' cookie. If it exists 
     * then the user is returning. We find this out in getAllVariations, which 
     * is called from the assignVariations method run each time the observer 
     * model is made.
     *
     * @since 0.0.1
     * @var bool
     */
    protected $_is_new = FALSE;

    /**
     * Depending on the version of Magento and how the design packages are 
     * handled, assignVariations can be run more than once on load. This is 
     * a static property to ensure the method only runs once.
     *
     * @var bool
     */
    protected static $_has_assigned = FALSE;

    /**
     * Is this visitor returning?
     *
     * @since 0.0.1
     * @return bool
     */
    public function getIsReturning()
    {
        return ! $this->_is_new;
    }

    /**
     * Is this visitor new?
     *
     * @since 0.0.1
     * @return bool
     */
    public function getIsNew()
    {
        return $this->_is_new;
    }

    /**
     * Returns whether we have assigned the variations yet.
     *
     * @since 0.0.1
     * @return bool
     */
    public function getHasAssigned()
    {
        return self::$_has_assigned;
    }

    /**
     * Assigns variations to the current visitor for all running tests.
     *
     * @since 0.0.1
     * @return bool
     */
    public function assignVariations()
    {
        if (self::$_has_assigned == TRUE || Mage::helper('abtest/bots')->isBot())
        {
            return;
        }

        # Used in optimizing SQL queries to update variation/test stats
        $optimizer = Mage::helper('abtest/optimizer');

        # We only want to write session data if the user has new cohort 
        # information - we don't want to write the same session data each time 
        # an event fires.
        $_session_data_has_changed = FALSE;

        # Ensure we've got all of the visitor's variations
        $this->getAllVariations();

        foreach (Mage::helper('abtest')->getActiveTests() as $test_id => $data)
        {
            if ($this->hasVariation($test_id))
            {
                # Check the current variation and see if it matches the one 
                # being forced. If not, we need to re-set the user's cohort.
                $variation = $this->getVariation($test_id);
                if ( ! isset($_GET["__t_".$test_id]))
                {
                    # If the user has a variation and we're not forcing a new one, 
                    # skip it. The observer model tracks all hits, so we've got 
                    # nothing to do. Note, we could do an OR above and remove this 
                    # entirely but it's here for documentation purposes.
                    if (strtotime(date('Y-m-d')) > strtotime($variation['last_seen']))
                    {
                        self::$_variations[$test_id]['last_seen'] = date('Y-m-d');
                        $_session_data_has_changed = TRUE;
                    }

                    continue;
                }
            }

            # Assign the visitor's variation - this will either randomise or 
            # force a variation depending on the get parameter. 
            $this->assignVariation($data, $data['variations'], FALSE);
            $_session_data_has_changed = TRUE;
        }

        self::$_has_assigned = TRUE;

        # If there's new session data or this is a new visitor, log them.
        if ($_session_data_has_changed OR Mage::getSingleton('core/cookie')->get('cohort_data') === FALSE)
        {
            try
            {
                # Write our cached DB queries
                $this->_writeVariationData();
                $optimizer->runQueries();
            }
            catch (Exception $e)
            {
                self::$_has_assigned = FALSE;
            }
        }
    }

    /**
     * Assigns a variation to a visitor for a particular test.
     *
     * @since  0.0.1
     * @param  int    Test ID to assign variation for
     * @param  array  Array of variations as returned by the Data helper's
     *                `getActiveTests` method
     * @param  bool   Whether to write session data immediately. Defaults to 
     *                TRUE; used to optimise A/B testing for a new visitor so 
     *                we can write data once.
     * @return bool
     */
    public function assignVariation($test_data, $variations, $write_session_data = TRUE)
    {
        if (isset($_GET['__t_'.$test_data['id']]))
        {
            # The forced variation doesn't exist...
            if ( ! array_key_exists($_GET['__t_'.$test_data['id']], $variations))
                return FALSE;

            # We have got a GET parameter which ensures a visitor gets a particular 
            # version (useful for client links).
            $this->_assignVariation($test_data['id'], $_GET['__t_'.$test_data['id']]);
        }
        else
        {
            # Are we only testing new visitors?
            if ( ! $test_data['only_test_new_visitors'] OR ($test_data['only_test_new_visitors'] && $this->getIsNew())) {
                # Randomly assign the visitor to a variation depending on the split 
                # percentage settings.
                # We need to loop through each variation to get the cumulative 
                # percentage - when the seed is lower than the cumulative 
                # percentage we're in that cohort.
                # IE: $seed = 20 and Version A takes 50%: We're in A (20 <=50)
                #     $seed = 92, Version A: 50%, Version b: 50%:
                #       We're in B (92 <= (50 + 50))
                $seed = mt_rand(1,100);
                $current_percentage = 0;
                foreach ($variations as $variation)
                {
                    $current_percentage += $variation['split_percentage'];

                    if ($seed <= $current_percentage)
                    {
                        $this->_assignVariation($test_data['id'], $variation['id'], $test_data['name'], $variation['name'], (bool) $variation['is_control']);
                        break;
                    }
                }

                # Sanity check: have we actually got a variation, or has someone 
                # messed around with the split percentages and the user is left 
                # designless? Or, this may be because we've got a returning visitor.
                if ( ! isset(self::$_variations[$test_data['id']]))
                {
                    # Assign them variation #1, which is the control.
                    $control = array_shift($variations);
                    $this->_assignVariation($test_data['id'], $control['id'], $test_data['name'], $control['name'], TRUE);
                }
            }
        }

        if ($write_session_data)
        {
            $this->_writeVariationData();
        }
    }

    /**
     * Adds the variation information to the $_variations property, and adds the 
     * necessary statistics to the database.
     *
     * This is a helper method to keep things DRY.
     *
     * @since 0.0.1
     * @param int   Test ID to assign
     * @param int   Variation ID to assign
     * @return void
     */
    private function _assignVariation($test_id, $variation_id, $test_name = NULL, $variation_name = NULL, $is_control = FALSE)
    {
        self::$_variations[$test_id] = array(
            'test'      => array(
                'name'  => $test_name,
                'id'    => $test_id
            ),
            'variation' => array(
                'name'       => $variation_name,
                'id'         => $variation_id,
                'is_control' => $is_control,
            ),
            'converted' => FALSE,
            'last_seen' => date('Y-m-d'),
        );

        # @TODO: Add IP Ignore checks to this DB query statement...
        # Add a new visitor to our variation and update the conversion rate 
        # accordingly.
        $optimizer       = Mage::helper('abtest/optimizer');
        $variation_table = Mage::getSingleton('core/resource')->getTableName('abtest/variation');
        $test_table      = Mage::getSingleton('core/resource')->getTableName('abtest/test');
        $hit_table       = Mage::getSingleton('core/resource')->getTableName('abtest/hit');

        $optimizer->addQuery('INSERT INTO `'.$hit_table.'` (`test_id`, `variation_id`, `date`, `visitors`) VALUES ('.$test_id.', '.$variation_id.', "'.date('Y-m-d').'", 1) ON DUPLICATE KEY UPDATE `visitors` = `visitors` + 1');
        $optimizer->addQuery('UPDATE `'.$variation_table.'` SET visitors = visitors + 1, conversion_rate = ((conversions / visitors) * 100) WHERE id = '.$variation_id);
        $optimizer->addQuery('UPDATE `'.$test_table.'` SET visitors = visitors + 1 WHERE id = '.$test_id);
    }

    /**
     * Adds the contents of our $_variations property to the user's session
     *
     * @since 0.0.1
     */
    private function _writeVariationData()
    {
        # Don't use Magento's jsonEncode because it pointlessly loads some 
        # extra models (including Design_Package, which causes a recursive 
        # loop). This will almost certainly fall back to the native json_encode()
        $data = Zend_Json::encode(self::$_variations);
        $data = base64_encode($data);
        $data = mcrypt_encrypt(MCRYPT_CAST_128, self::COOKIE_KEY, $data, MCRYPT_MODE_ECB);
        $data = base64_encode($data);

        Mage::getSingleton('core/cookie')->set('cohort_data', $data, (86400 * 365));
    }

    /**
     * Sets the 'converted' flag for the user's test/variation information to 
     * TRUE, so that we can only register one variation per visitor if need be.
     *
     * @since 0.0.1
     * @return void
     */
    public function registerConversion($test_id)
    {
        $this->getAllVariations();
        self::$_variations[$test_id]['converted'] = true;
        $this->_writeVariationData();
    }

    /**
     * Returns TRUE or FALSE depending on whether the user has a variation set 
     * for the current test.
     *
     * @since 0.0.1
     * @param  int   Test ID
     * @return bool  True or false depending on whether a variation has 
     *               previously been set.
     */
    public function hasVariation($test_id)
    {
        return array_key_exists($test_id, $this->getAllVariations());
    }

    /**
     * Gets the user's entire A/B testing variation data
     *
     * @since 0.0.1
     *
     * @return array
     */
    public function getAllVariations()
    {
        # We've already queried the DB for our session data; don't do it again.
        if (self::$_variations !== NULL)
            return self::$_variations;

        $data = Mage::getSingleton('core/cookie')->get('cohort_data');

        if ($data == FALSE)
        {
            self::$_variations = array();
            $this->_is_new = TRUE;
        }
        else
        {
            $data = base64_decode($data);
            $data = mcrypt_decrypt(MCRYPT_CAST_128, self::COOKIE_KEY, $data, MCRYPT_MODE_ECB);
            $data = base64_decode($data);
            self::$_variations = Mage::helper('core')->jsonDecode($data);
        }

        return self::$_variations;
    }

    /**
     * Gets the visitor's variation for a given test. 
     *
     * @since 0.0.1
     * @param  int    ID of the test 
     * @return array  Array of basic test and variation information
     */
    public function getVariation($test_id)
    {
        $this->getAllVariations();

        if ( ! is_int($test_id) && (int) $test_id == 0)
        {
            throw new Exception("The test ID must be given as an integer");
        }

        # Typecast, just in case we had a string like "2"
        $test_id = (int) $test_id;

        return self::$_variations[$test_id];
    }

    /**
     * Gets the visitor's assigned variation by looking through all running 
     * tests for a matching observer.
     *
     * The default observer to look for is the target - the actual 
     * observer/event we're A/B testing. This can be changed to 
     * 'observer_conversion' to find the variation from the conversion event.
     *
     * The variation data returned is in the following format:
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
     * @param  string  Name of the observer to get XML for
     * @param  string  The observer type: "observer_target" for a test event and 
     *                 "observer_conversion" for a conversion event
     * @return string  String of XML (empty string "" for no XML updates)
     */
    public function getVariationsFromObserver($observer_name, $source = 'observer_target')
    {
        # Load our session data first
        $this->getAllVariations();

        $variations = array();
        foreach (Mage::helper('abtest')->getActiveTests() as $test)
        {
            if ($this->_matchEvent($test[$source], $observer_name))
            {
                # The user doesn't have a variation for this test
                if ( ! isset(self::$_variations[$test['id']]))
                    continue;

                # This is the test we're looking for, so we're going to get the 
                # ID of the variation for this user
                $variation_id = self::$_variations[$test['id']]['variation']['id'];

                # Loop through all of the test's variations to find the matching 
                # variation information, then return the complete variation 
                # array of information
                foreach ($test['variations'] as $variation)
                {
                    if ($variation['id'] == $variation_id)
                    {
                        $variations[] = $variation;
                        break;
                    }
                }
            }
        }

        if (empty($variations))
            return FALSE;

        return $variations;
    }

    /**
     * Returns the user's preivew data inside the user's cookie, if possible.
     * If the variation doesn't exist this returns FALSE
     *
     * @return array|boolean
     */
    public function getPreview()
    {
        if ($cookie = Mage::getSingleton('core/cookie')->get('test_preview'))
        {
            return Mage::helper('core')->jsonDecode($cookie);
        }

        return FALSE;
    }

    /**
     * Loads layout updates when previewing a variation. The layout information 
     * is extracted from the 'test_preview' cookie. If this cookie doesn't exist 
     * we return FALSE.
     *
     * @since 0.0.1
     *
     * @param  string  Observer name to load preview for
     * @return mixed
     */
    public function getPreviewXml($observer_event_name)
    {
        if ($cookie = $this->getPreview())
        {
            if ($this->_matchEvent($cookie['observer'], $observer_event_name))
                return $cookie['layout_update'];
        }

        return FALSE;
    }

    /**
     * Matches an event, turning asterisks (*) into wildcards.
     *
     * @since 0.0.1
     * @param string  Event name (from the test, including wildcards) to match
     * @param string  Controller/module/action pair to match
     * @return bool
     */
    protected function _matchEvent($event, $subject)
    {
        $event = str_replace('*', '[a-zA-Z_]*', $event);
        $event = '/'.$event.'/i';

        return (bool) preg_match($event, $subject);
    }

}
