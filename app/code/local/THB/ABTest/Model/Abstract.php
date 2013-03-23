<?php

abstract class THB_ABTest_Model_Abstract extends Mage_Core_Model_Abstract {

    /**
     * Gets the test or variation's conversion rate as a float
     *
     * @return float
     */
    public function getConversionRate()
    {
        $percentage = ($this->getData('conversions') / $this->getData('visitors')) * 100;
        return round($percentage, 3);
    }

}
