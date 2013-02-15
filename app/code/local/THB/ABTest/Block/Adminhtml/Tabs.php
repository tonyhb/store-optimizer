<?php

/**
 * The base block class to add tabs for the new/edit form
 *
 */
class THB_ABTest_Block_Adminhtml_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{

    public function __construct()
    {
        parent::__construct();

        $this->setId('abtest_form_tabs');

        # Where is the tab content going to be inserted?
        $this->setDestElementId('abtest_form');

        $this->setTitle(Mage::helper('core/data')->__('A/B Test'));
    }

    protected function _beforeToHtml()
    {
        $this->addTab('main', array(

            'label'   => Mage::helper('core/data')->__('Test information'),
            'title'   => Mage::helper('core/data')->__('Test information'),
            'content' => $this->getLayout()->createBlock('abtest/adminhtml_form_settings')->toHtml(),
            'active'  => true
        ));

        $this->addTab('cohort', array(
            'label'   => Mage::helper('core/data')->__('Cohorts'),
            'title'   => Mage::helper('core/data')->__('Cohorts'),
            # 'content' => $this->getLayout()->createBlock('abtest/adminhtml_form_container')->toHtml(),
            'content' => $this->getLayout()->createBlock('abtest/adminhtml_form_settings')->toHtml(),
            'active'  => false
        ));

        return parent::_beforeToHtml();
    }

}
