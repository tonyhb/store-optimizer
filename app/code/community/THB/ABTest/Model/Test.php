<?php

class THB_ABTest_Model_Test extends THB_ABTest_Model_Abstract {

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

    /**
     * Gets a test's conversion rate from the conversions and visitors
     *
     * @return string
     */
    public function getConversionRateAsString()
    {
        return $this->getConversionRate().'%';
    }

    public function getConversionCollection()
    {
        return Mage::getModel('abtest/conversion')
            ->getcollection()
            ->addFieldToFilter('test_id', $this->getId())
            ->setOrder('created_at', 'asc')
            ->addFieldToSelect('*');
    }

    public function getHitCollection()
    {
        return Mage::getModel('abtest/hit')
            ->getCollection()
            ->addFieldToFilter('test_id', $this->getId())
            ->setOrder('date', 'asc')
            ->addFieldToSelect('*');
    }

    public function getVariationCollection()
    {
        return Mage::getModel('abtest/variation')
            ->getCollection()
            ->addFieldToFilter('test_id', $this->getId())
            ->addFieldToSelect('*');
    }

    /**
     * Ensures data from `getData()` is valid. Used before a new model is saved.
     *
     * @since 0.0.1
     */
    public function validate($throw_exception_with_errors = FALSE)
    {
        $errors = array();
        $valid  = TRUE;

        # Let's do a sanity check for the date. The date must either be 
        # today or in the future - we don't want the start date to be Jan 1, 
        # 1979, or we'll have 40 years of data to show in the graph LOL.
        $today = new DateTime(Date('Y-m-d'));
        $start_date = new DateTime($this->getData('start_date'));
        if ($start_date < $today)
        {
            $valid = FALSE;
            $errors[] = Mage::helper('core')->__('The start date can\'t be in the past.');
        }

        # Is there a test name? There bloody better be.
        if ( ! $this->getData('name'))
        {
            $valid = FALSE;
            $errors[] = Mage::helper('core')->__('The test needs to have a name.');
        }

        # Is there a test with the action event running already?
        $other_tests = Mage::getModel('abtest/test')
            ->getCollection()
            ->addFieldToFilter('is_active', 1)
            ->addFieldToFilter('observer_target', $this->getData('observer_target'))
            ->getSize();
        if ($other_tests > 0)
        {
            $valid = FALSE;
            $errors[] = Mage::helper('core')->__('An A/B test is already running with the chosen test page.');
        }

        # This is invalid - return an array of errors or throw an exception
        if ( ! $valid)
        {
            if ($throw_exception_with_errors) throw new Exception("Validation failed");

            return $errors;
        }

        return TRUE;
    }

    /**
     * Overrides the default save command to ensure that the start date is in 
     * the correct format
     *
     */
    public function save()
    {
        if ( ! $start_date = $this->getData("start_date"))
        {
            $start_date = time();
        }

        $start_date = strtotime($start_date);
        $this->setData("start_date", date("Y-m-d", $start_date));

        // We only need to do end dates if they are provided.
        if ($start_date = $this->getData("end_date"))
        {
            $end_date = strtotime($end_date);
            $this->setData("end_date", date("Y-m-d", $end_date));
        }

        parent::save();
    }

}
