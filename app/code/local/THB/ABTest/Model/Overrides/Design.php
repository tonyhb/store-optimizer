<?php

class THB_ABTest_Model_Overrides_Design extends Mage_Core_Model_Design_Package
{

    public function getPackageName()
    {
        if (null === $this->_name) {
            # Are we testing or previewing a new theme?
            if ($this->getArea() != 'adminhtml') {

                # Are we previewing a theme?
                if ($preview = Mage::helper('abtest/visitor')->getPreview()) {
                    if ($preview['theme']) {
                        $this->setPackageName($preview['theme']);
                    }
                } else {
                    # First, we need to assign variations. This is because if 
                    # a visitor hasn't been to our website yet, they're NOT in 
                    # a cohort - this gets called first. If the visitor gets 
                    # put into a cohort with a different theme, the website's 
                    # design is going to change after the first page view. This 
                    # ensures we don't get this...
                    Mage::helper('abtest/visitor')->assignVariations();

                    # Do we have a variation with a theme?
                    $variation = Mage::helper('abtest/visitor')->getVariationFromObserverName('*');
                    if ($variation['theme']) {
                        $this->setPackageName($variation['theme']);
                    }
                }
            }
        }

        # The variation or preview didn't have a theme override - we can go for 
        # the standard theme.
        if ($this->_name === null) {
            $this->setPackageName();
        }

        return $this->_name;
    }
}
