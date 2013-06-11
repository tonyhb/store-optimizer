<?php

class THB_ABTest_Model_Overrides_Design extends Mage_Core_Model_Design_Package
{

    public function __construct()
    {
        # First, we need to assign variations. This is because if 
        # a visitor hasn't been to our website yet, they're NOT in 
        # a cohort - this gets called first. If the visitor gets 
        # put into a cohort with a different theme, the website's 
        # design is going to change after the first page view. This 
        # call ensures we don't get this...

        // echo "<h1 style='font-size: 100px'>Constructing le design</h1>";

        // @TODO: Line 250 of the visitor helper calls 
        // `Mage_Core_Model_Translate_Inline->isAllowed( )`, which loads a new 
        // design instance. We need to add a global $hasAssigned static property 
        // so this is only called once
        // Mage::helper('abtest/visitor')->assignVariations();
    }

    public function getPackageName()
    {
        if (null === $this->_name) {
            # Are we testing or previewing a new theme?
            if ($this->getArea() != 'adminhtml') {

                # Are we previewing a theme?
                if ($preview = Mage::helper('abtest/visitor')->getPreview()) {
                    if ($preview['theme']) {
                        $this->setPackageName($preview['theme']);
                    }
                } else {
                    # Do we have a variation with a theme?
                    $variations = Mage::helper('abtest/visitor')->getVariationsFromObserver('*');

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

        # The variation or preview didn't have a theme override - we can go for 
        # the standard theme.
        if ($this->_name === null) {
            $this->setPackageName();
        }

        return $this->_name;
    }

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
        $variation = Mage::helper('abtest/visitor')->getVariationsFromObserver('*');
        $variation = array_shift($variation);

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
     * Applies theme overrides for each variation
     *
     */
    public function getTheme($type)
    {
        #return parent::getTheme($type);
        $variation = Mage::helper('abtest/visitor')->getVariationsFromObserver('*');
        $variation = array_shift($variation);

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

        return parent::getTheme($type);

        return $return;
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
            if ( ! empty(self::$_customThemeTypeCache[$serialized_regexes]))
            {
                return self::$_customThemeTypeCache[$serialized_regexes];
            }

            $regexps = @unserialize($serialized_regexes);

            if (!empty($regexps))
            {
                foreach ($regexps as $rule) {
                    if ( ! is_array($rule)) continue;

                    # @TODO: More caching
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
        # echo "<div style='text-align:left;padding:10px'>";
        # var_dump("Fallback for $file", $fallbackScheme);
        # echo "</div>";
        return parent::_fallback($file, $params, $fallbackScheme);
    }

}
