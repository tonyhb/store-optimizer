<?php

class THB_ABTest_Model_Mysql4_Test_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract {

    protected function _construct() {
        $this->_init('abtest/test');
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
        $read = Mage::getSingleton('core/resource')->getConnection('core/read');
        $table = Mage::getSingleton('core/resource')->getTableName('abtest/test');
        $all_tests = $read->fetchAll('SELECT * FROM '.$table.' WHERE (end_date >= '.date("Y-m-d").' OR end_date IS NULL) AND is_active = 1 ORDER BY id ASC');

        if (empty($all_tests))
            return array();

        $active_tests = array();

        # Find all test IDs and add the tests to the active tests property, 
        # using the test ID as the array key
        $test_ids = array();
        foreach ($all_tests as $test)
        {
            $active_tests += array(
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
            $active_tests[$variation['test_id']]['variations'][$variation['id']] = $variation;
        }

        return $active_tests;
    }

}
