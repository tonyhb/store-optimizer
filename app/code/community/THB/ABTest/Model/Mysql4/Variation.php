<?php

class THB_ABTest_Model_Mysql4_Variation extends Mage_Core_Model_Mysql4_Abstract {

    protected $_serializableFields = array(
        "package_exceptions"   => array(NULL, NULL),
        "templates_exceptions" => array(NULL, NULL),
        "skin_exceptions"      => array(NULL, NULL),
        "layout_exceptions"    => array(NULL, NULL),
        "default_exceptions"   => array(NULL, NULL),
    );

    protected function _construct() {
        $this->_init('abtest/variation', 'id');
    }

}
