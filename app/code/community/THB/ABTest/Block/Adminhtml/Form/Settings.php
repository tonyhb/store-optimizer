<?php

class THB_ABTest_Block_Adminhtml_Form_Settings extends Mage_Adminhtml_Block_Template
{

    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('abtest/form/settings.phtml');
    }

    /**
     * Either get the current saved test or an empty test object. This ensures
     * setting values from the test's properties works for new tests and when 
     * viewing a test. A tad bit hackish, but it saves us recreating the form 
     * for both cases. 
     *
     * @return THB_ABTest_Model_Test
     */
    public function getTest()
    {
        $test = parent::getTest();
        if ($test == NULL) {
            return Mage::getModel("abtest/test");
        }

        return $test;
    }

    public function getVariationCollection()
    {
        $coll = parent::getVariationCollection();
        if ($coll == NULL) {
            return Mage::getModel("abtest/test")->getVariationCollection();
        }

        return $coll;
    }


    /**
     * Either returns the saved test's start date or today's date in Magento's 
     * calendar date format.
     *
     * @return string  String in Magento's calendar date format "j M, Y"
     */
    public function getStartDate()
    {
        if ($this->getTest()->getStartDate() == NULL) {
            return Date("j M, Y", time());
        }

        return Date("j M, Y", strtotime($this->getTest()->getStartDate()));
    }

    public function isTestObserverCustom()
    {
        $predefined = array(
            "*",
            "catalog_category_view",
            "catalog_product_view",
            "checkout_cart_index",
            "checkout_onepage_index",
            "cms_page_view",
            "cms_index_index",
            "wishlist_index_index",
        );

        if ($this->getTest()->getObserverTarget() != NULL && ! in_array($this->getTest()->getObserverTarget(), $predefined)) {
            return TRUE;
        }

        return FALSE;
    }

}
