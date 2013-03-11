<?php

class THB_ABTest_Model_Variation extends Mage_Core_Model_Abstract {

    protected function _construct()
    {
        $this->_init('abtest/variation');

        return $this;
    }

}
