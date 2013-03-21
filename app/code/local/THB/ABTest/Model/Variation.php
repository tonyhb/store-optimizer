<?php

class THB_ABTest_Model_Variation extends THB_ABTest_Model_Abstract {

    protected function _construct()
    {
        $this->_init('abtest/variation');

        return $this;
    }

    public function getconversionsOverVisitors()
    {
        return $this->getConversions() . " / " . $this->getVisitors();
    }

}
