<?php

class THB_ABTest_Model_Mysql4_Hit extends Mage_Core_Model_Mysql4_Abstract {

    protected function _construct() {
        $this->_init('abtest/hit', 'id');
    }

}
