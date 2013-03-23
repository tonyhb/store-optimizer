<?php

class THB_ABTest_Model_Variation extends THB_ABTest_Model_Abstract {

    protected $_control;

    protected function _construct()
    {
        $this->_init('abtest/variation');
        return $this;
    }

    public function getControl()
    {
        if ( ! $this->_control)
        {
            if ($this->getData('is_control') == 1)
            {
                $this->_control = $this;
            }
            else
            {
                $this->_control = Mage::getModel('abtest/variation')->getCollection()
                    ->addFieldToFilter('is_control', 1)
                    ->addFieldToFilter('test_id', $this->getData('test_id'))
                    ->setPageSize(1)
                    ->getFirstItem();
            }
        }

        return $this->_control;
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
        $standard_error = $standard_error * 1.96; # 1.96 = 95% confidence (bigger stanard error range with less visitors), or 1.28 for 80% confidence
        $standard_error = round($standard_error * 100, 2);

        return $this->getConversionRate().'% &nbsp;<small>&#177;'.$standard_error.'%</small>';
    }

    public function getStatisticalConfidence()
    {
        if ($this->getData('is_control') == 1)
        {
            return '<small>N/A</small>';
        }

        $Pc = $this->getControl()->getData('conversion_rate') / 100; # Divide by 100 because we store it as a % in the DB, not a float
        $Nc = $this->getControl()->getData('visitors');
        $P  = $this->getData('conversion_rate') / 100;
        $N  = $this->getdata('visitors');

        $probability = Mage::helper('abtest/statistics')->calculate_probability($P, $Pc, $N, $Nc);
        $confidence = (1 - $probability) * 100;
        $confidence = round($confidence, 0);

        if ($confidence < 80)
        {
            return '<span class="unlikely">&lt; 80%</span>';
        }

        if ($confidence > 95)
        {
            return '<span class="likely">'.$confidence.'%</span>';
        }

        return '<span class="average">'.$confidence.'%</small>';
    }

}
