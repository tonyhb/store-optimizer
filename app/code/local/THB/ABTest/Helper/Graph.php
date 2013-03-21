<?php

class THB_ABTest_Helper_Graph extends Mage_Core_Helper_Data
{

    public function setTest(THB_ABTest_Model_Test $test)
    {
        $this->_test = $test;

        return $this;
    }

    /**
     * Runs through each day from the start of the test to the end of the test 
     * and returns an array of variant hit information for each day.
     *
     * @return array
     */
    public function getHitData()
    {
        $hits = array();
        foreach ($this->_test->getHitCollection() as $hit)
        {
            # Arrange our hits by day and variation ID for easy accessability
            $hits[$hit->getDate()][$hit->getData('variation_id')] = $hit->getData('visitors');
        }

        return $this->_parseDataForGraphs($hits);
    }

    public function getConversionData()
    {
        $conversions = array();
        foreach ($this->_test->getConversionCollection() as $conversion)
        {
            $date = date('Y-m-d', strtotime($conversion->getCreatedAt()));

            if (isset($conversions[$date][$conversion->getData('variation_id')]))
            {
                $conversions[$date][$conversion->getData('variation_id')]++;
            }
            else
            {
                $conversions[$date][$conversion->getData('variation_id')] = 1;
            }
        }

        return $this->_parseDataForGraphs($conversions);
    }

    public function getConversionRateData()
    {
        $hit_data = $this->getHitData();
        $conversion_data = $this->getConversionData();

        $conversion_rates = array();
        foreach ($hit_data as $day => $variations)
        {
            $conversion_rates[$day] = array();

            foreach ($variations as $id => $data)
            {
                if ($data == 0 OR ! isset($conversion_data[$day][$id]) OR $conversion_data[$day][$id] == 0)
                {
                    $conversion_rates[$day][$id] = 0;
                }
                else
                {
                    $conversion_rates[$day][$id] = round((($conversion_data[$day][$id] / $data) * 100), 2);
                }
            }
        }

        return $conversion_rates;
    }

    public function _parseDataForGraphs($data)
    {
        # Get the start date of the test and the current/end date so we can calculate 
        # statistics for each day in between the two
        $start_date = new DateTime($this->_test->getStartDate());

        if ($this->_test->getData('end_date'))
        {
            $end_date = new DateTime($this->_test->getData('end_date'));
        }
        else
        {
            $end_date = new DateTime(date('Y-m-d'));
        }

        # We need to add data points for each variation for each day - so if there are 
        # any blanks we need to fill with a 0,
        $variations = $this->_test->getVariationCollection()->getSize();

        $parsed_data = array();
        while ($start_date <= $end_date)
        {
            # If we have hits on this day use the hit information and fill any variant 
            # blanks with 0
            if (array_key_exists($start_date->format('Y-m-d'), $data))
            {
                $statistics = $data[$start_date->format('Y-m-d')];
                $statistics += array_fill(1, $variations, 0);
            }
            else
            {
                # There were no hits on this day, so fill each variation with a 0
                $statistics = array_fill(1, $variations, 0);
            }

            $parsed_data[$start_date->format('F d, Y 00:00:01')] = $statistics;

            $start_date->modify('+1 day');
        }

        return $parsed_data;
    }

    public function getVariationNames()
    {
        $names = array();
        foreach ($this->_test->getVariationCollection() as $variation)
        {
            $names[] = $variation->getName();
        }

        return $names;
    }
}
