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
