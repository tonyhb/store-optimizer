<?php

class THB_ABTest_Helper_Bots extends Mage_Core_Helper_Data {

    /**
     * Stores whether the current request originated from one of the major bots. 
     * This is a protected variable because it should only be modified from 
     * within this class.
     *
     * @since 0.0.1
     *
     * @var bool|null Initially NULL, but a boolean once the bot test has ran
     */
    protected static $_is_bot = NULL;

    /**
     * Runs the detect bots method the first time the class is instantiated
     *
     * @since 0.0.1
     */
    public function __construct()
    {
        # Only run this once; micro-optimisation
        if (self::$_is_bot === NULL)
            $this->_detect_bots();
    }

    /**
     * Detects whether the request was from a common bot and sets the $_is_bot 
     * static property. Current bots checked for are:
     *
     * (Google, Yahoo, Slurp, MSN, Lycos, Internet Archive, and SEOMoz aka 
     * "rogerbot", "SEO")
     *
     * @since 0.0.1
     *
     * @return void
     */
    protected function _detect_bots()
    {
        # False by default, and only overwritten below.
        self::$_is_bot = FALSE;

        $bots = array('googlebot', 'msnbot', 'slurp', 'ask jeeves', 'crawl', 'ia_archiver', 'lycos', 'rogerbot', 'SEO', 'MJ12bot');
        foreach($bots as $botname)
        {
            if(stripos($_SERVER['HTTP_USER_AGENT'], $botname) !== FALSE)
            {
                self::$_is_bot = TRUE;
                break;
            }
        }
    }

    /**
     * Returns whether this request was from a bot or not
     *
     * @since 0.0.1
     *
     * @api
     * @return bool
     */
    public function isBot()
    {
        return self::$_is_bot;
    }

}

