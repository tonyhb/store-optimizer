<?php

class THB_ABTest_Model_Overrides_Design_Package extends Mage_Core_Model_Design_Package
{

    /**
     * Gets the overall theme package name for the website. 
     *
     * This has been overridden to change the frontend package on 
     * a variation-by-variation basis.
     *
     * Also, this assigns the users to variations because it is the first method 
     * to be called (from V1.7 onwards) in each of the design overrides, and a 
     * user needs to be in a cohort before modifications can be made.
     *
     * For versions 1.4 - 1.6, getTheme also runs assignVariations() as this is 
     * the first method called in all of our overrides. We provide some form of 
     * optimization similar to memoization because the assignVariation method 
     * can only run once, so this doesn't impact speed.
     *
     * We CAN'T put assignVariations() in the __construct method because, if so, 
     * admin users trigger visits when browsing the admin side, and the getArea() 
     * method isn't accurate when called in __construct to prevent this.
     *
     */
/*
    public function getPackageName()
    {
        if (null === $this->_name) {
            # Are we testing or previewing a new theme?
            if ($this->getArea() != 'adminhtml') {
                # First, we need to assign variations. This is because if 
                # a visitor hasn't been to our website yet, they're NOT in 
                # a cohort - this gets called first. If the visitor gets 
                # put into a cohort with a different theme, the website's 
                # design is going to change after the first page view. This 
                # call ensures we don't get this...
                Mage::helper('abtest/visitor')->assignVariations();

                # Are we previewing a theme?
                if ($preview = Mage::helper('abtest/visitor')->getPreview()) {
                    if ($preview['theme']) {
                        $this->setPackageName($preview['theme']);
                    }
                } else {
                    # Do we have a variation with a theme?
                    if ($variations = Mage::helper('abtest/visitor')->getVariationsFromObserver('*'))
                    {
                        # Note that only one test can run themes at a time, so upon 
                        # the first theme test break the loop of tests on '*'
                        foreach ($variations as $variation)
                        {
                            if ($variation['theme']) {
                                $this->setPackageName($variation['theme']);
                                break;
                            }
                        }
                    }
                }
            }
        }

        # The variation or preview didn't have a theme override - we can go for 
        # the standard theme.
        if ($this->_name === null) {
            $this->setPackageName();
        }

        return $this->_name;
    }
*/

    /**
     * Overrides the parent method to provide correct filenames to all 
     * overrides.
     *
     * This is called from the getFilename() method as one of the fallback 
     * parameters
     *
     */
    public function getFallbackTheme()
    {
        if ( ! $variation = $this->_getVariation())
        {
            return parent::getFallbackTheme();
        }

        if (isset($variation["default"]))
        {
            # This matches "default" overrides to any $type (eg. skin, 
            # templates)
            $theme = $variation["default"];
            $customThemeType = $this->_checkUserAgentAgainstVariationRegexps($variation["default_exceptions"]);
            if ($customThemeType)
            {
                $theme = $customThemeType;
            }

            return $theme;
        }

        return parent::getFallbackTheme();
    }

    /**
     * Applies theme overrides for each variation.
     *
     * For versions 1.4 - 1.6, getTheme also runs assignVariations() as this is 
     * the first method called in all of our overrides. We provide some form of 
     * optimization similar to memoization because the assignVariation method 
     * can only run once, so this doesn't impact speed.
     *
     * We CAN'T put assignVariations() in the __construct method because, if so, 
     * admin users trigger visits when browsing the admin side, and the getArea() 
     * method isn't accurate when called in __construct to prevent this.
     *
     */
    public function getTheme($type)
    {
        if ($this->getArea() != 'adminhtml')
        {
            # Assign the users to variations if they're on the main website. 
            # Avoid adminhtml because that logs incorrect stats when a user has 
            # just made a test.
            Mage::helper('abtest/visitor')->assignVariations();
        }

        if ( ! $variation = $this->_getVariation())
        {
            # There's no variation altering themes
            return parent::getTheme($type);
        }

        # The particular $type has overrides - for example, templates.
        # This will not match "default" overrides, which are catch-all
        if (isset($variation[$type]) && $variation[$type])
        {
            # The A/B test has overrides for this particular theme element 
            # (skin, templates, default etc.)
            $this->_theme[$type] = $variation[$type];

            # Let's just check regex overwrites from our variation
            $customThemeType = $this->_checkUserAgentAgainstVariationRegexps($variation["{$type}_exceptions"]);
            if ($customThemeType)
            {
                $this->_theme[$type] = $customThemeType;
            }

            return $this->_theme[$type];
        }
        elseif (isset($variation["default"]))
        {
            # This matches "default" overrides to any $type (eg. skin, 
            # templates)
            $this->_theme[$type] = $variation["default"];

            $customThemeType = $this->_checkUserAgentAgainstVariationRegexps($variation["default_exceptions"]);
            if ($customThemeType)
            {
                $this->_theme[$type] = $customThemeType;
            }

            return $this->_theme[$type];
        }

        # We had a variation but it turns out it didn't alter any themes. Run 
        # the parent method.
        return parent::getTheme($type);
    }

    /**
     * These were originally declared private which means we're not accessing 
     * them. Guess we've got to duplicate...
     *
     */
    private static $_regexMatchCache      = array();

    private static $_customThemeTypeCache = array();

    /**
     * A duplicate of the `_checkUserAgentAgainstRegexps` method made to work 
     * with serialized arrays as an argument
     *
     * @var string  Serialized array of regexes
     */
    protected function _checkUserAgentAgainstVariationRegexps($serialized_regexes)
    {
        if ( ! empty($_SERVER['HTTP_USER_AGENT']))
        {
            # Quick sanity check: the argument passed by previews is an array, not 
            # a serialized string. I know this is a particularly horrible hack but 
            # we're going to do it anyways. This has no speed impact because 
            # visitors aren't previewing :)
            if (is_array($serialized_regexes)) {
                $serialized_regexes = serialize($serialized_regexes);
            }

            if ( ! empty(self::$_customThemeTypeCache[$serialized_regexes]))
            {
                return self::$_customThemeTypeCache[$serialized_regexes];
            }

            $regexps = @unserialize($serialized_regexes);

            if (!empty($regexps))
            {
                foreach ($regexps as $rule) {
                    if ( ! is_array($rule)) continue;

                    if ( ! empty(self::$_regexMatchCache[$rule['regexp']][$_SERVER['HTTP_USER_AGENT']]))
                    {
                        self::$_customThemeTypeCache[$serialized_regexes] = $rule['value'];
                        return $rule['value'];
                    }

                    $regexp = $rule['regexp'];

                    if (false === strpos($regexp, '/', 0))
                    {
                        $regexp = '/' . $regexp . '/';
                    }

                    if (@preg_match($regexp, $_SERVER['HTTP_USER_AGENT']))
                    {
                        self::$_regexMatchCache[$rule['regexp']][$_SERVER['HTTP_USER_AGENT']] = true;
                        self::$_customThemeTypeCache[$serialized_regexes] = $rule['value'];
                        return $rule['value'];
                    }
                }
            }
        }

        return false;
    }


    protected function _fallback($file, array &$params, array $fallbackScheme = array(array()))
    {
        return parent::_fallback($file, $params, $fallbackScheme);
    }

    /**
     * Protected helper method to remove some code duplication. This just loads 
     * either the preview, the all pages variation, or FALSE for methods which 
     * alter themes
     *
     * @return array|bool
     */
    protected function _getVariation()
    {
        if ($preview = Mage::helper('abtest/visitor')->getPreview())
        {
            return $preview;
        }
        else
        {
            $variation = Mage::helper('abtest/visitor')->getVariationsFromObserver('*');
            if ( ! $variation) {
                return FALSE;
            }
            return array_shift($variation);
        }

        return FALSE;
    }

}
