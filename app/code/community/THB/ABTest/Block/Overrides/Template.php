<?php

class THB_ABTest_Block_Overrides_Template extends Mage_Core_Block_Template
{

    public function getCacheKeyInfo()
    {
        if ( ! Mage::helper("abtest")->isRunning())
        {
            return parent::getCacheKeyInfo();
        }

        // Add running A/B tests and user variations to the cache key, ensuring 
        // test/variation combos are cached.

        $ab_test_key = "";
        foreach (Mage::helper("abtest/variations")->getAllVariations() as $item) {
            $ab_test_key .= "test:".$item["test"]["id"];
            $ab_test_key .= "variation:".$item["variation"]["id"];
        }

        return array_merge(array(
            'ab_test' => $ab_test_key,
        ), $info);
    }
}
