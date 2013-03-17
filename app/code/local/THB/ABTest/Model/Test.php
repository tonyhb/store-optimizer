<?php

class THB_ABTest_Model_Test extends Mage_Core_Model_Abstract {

    protected function _construct()
    {
        $this->_init('abtest/test');
    }

    /**
     * A getter for the test's end date. This is used because we don't want to 
     * show NULL for an end date in our admin grid.
     *
     * @return string
     */
    public function getEndDate()
    {
        if ( ! $this->getData('end_date'))
        {
            return "-";
        }

        return $this->getData('end_date');
    }

    /**
     * Gets a test's conversion rate from the conversions and visitors
     *
     * @return string
     */
    public function getConversionRateAsString()
    {
        return $this->getConversionRate().'%';
    }

    public function getConversionRate()
    {
        $percentage = ($this->getData('conversions') / $this->getData('visitors')) * 100;
        return round($percentage, 3);
    }

    /**
     * Gets the test status as a string
     *
     * @return string
     */
    public function getTestStatus()
    {
        if ($this->getData('is_active') == 1)
        {
            return 'Running';
        }
        else
        {
            return 'Stopped';
        }
    }

}
