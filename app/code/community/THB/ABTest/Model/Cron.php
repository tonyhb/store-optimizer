<?php

class THB_ABTest_Model_Cron extends Mage_Core_Model_Abstract {

    /**
     * Run nightly at 12:01 PM, stopping any tests that have an end date in the 
     * past.
     *
     * @since 0.0.1
     */
    public static function run()
    {
        # We're just going to use raw SQL to do this - if we've got to query the 
        # database to load tests then run a query each time to update the tests 
        # we may as well do one query to update all of them at the start.
        $write = Mage::getSingleton('core/resource')->getConnection('core/write');
        $write->query("
            UPDATE abtest_test SET is_active = 1 WHERE start_date <= CURDATE() AND (end_date IS NULL OR end_date > CURDATE());
            UPDATE abtest_test SET is_active = 0 WHERE end_date < CURDATE();
        ");
    }

}
