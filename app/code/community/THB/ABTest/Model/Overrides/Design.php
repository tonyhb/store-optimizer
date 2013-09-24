<?php

class THB_ABTest_Model_Overrides_Design extends Mage_Core_Model_Design
{

    public function loadChange($storeId, $date = null)
    {
        if (Mage::helper('abtest/bots')->isBot()) {
            return parent::loadChange($storeId, $date);
        }

        # First, we need to assign variations. This is because if 
        # a visitor hasn't been to our website yet, they're NOT in 
        # a cohort - this gets called first. If the visitor gets 
        # put into a cohort with a different theme, the website's 
        # design is going to change after the first page view. This 
        # call ensures we don't get this...
        Mage::helper('abtest/visitor')->assignVariations();

        # Are we previewing a theme?
        if ($preview = Mage::helper('abtest/visitor')->getPreview()) {
            if ($preview['package']) {
                $this->setPackage($preview['package']);

                # This is called "them" in this model, but refers to the 
                # 'Default' field in the system config & A/B test form
                if ($preview['default']) {
                    $this->setTheme($preview['default']);
                } else {
                    $this->setTheme('default');
                }

                return $this;
            }
        } else {
            # Do we have a variation with a theme?
            if ($variations = Mage::helper('abtest/visitor')->getVariationsFromObserver('*'))
            {
                # Note that only one test can run themes at a time, so upon 
                # the first theme test break the loop of tests on '*'
                foreach ($variations as $variation)
                {
                    if ($variation['package']) {
                        $this->setPackage($variation['package']);

                        if ($variation['default']) {
                            $this->setTheme($variation['default']);
                        } else {
                            $this->setTheme('default');
                        }

                        return $this;
                    }
                }
            }
        }

        return parent::loadChange($storeId, $date);
    }

}
