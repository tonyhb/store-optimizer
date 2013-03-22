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

    public function getSplitPercentageAsString()
    {
        return $this->getData('split_percentage') . '%';
    }

    /**
     * Gets a test's conversion rate from the conversions and visitors
     *
     * @return string
     */
    public function getConversionRateAsString()
    {
        # Calculate the standard error
        #
        # Find out the standard error
        $conversion_rate = $this->getConversions() / $this->getVisitors();
        $standard_error = sqrt($conversion_rate * (1 - $conversion_rate) / (int) $this->getVisitors());
        $standard_error = $standard_error * 1.96; # or 1.28 for 80% confidence
        $standard_error = round($standard_error * 100, 2);

        return $this->getConversionRate().'% &nbsp;<small>&#177;'.$standard_error.'%</small>';
    }


}
