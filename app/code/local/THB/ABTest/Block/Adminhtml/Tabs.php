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

        $this->addTab('cohort_1', array(
            'label'   => Mage::helper('core/data')->__('Control'),
            'title'   => Mage::helper('core/data')->__('Control'),
            'content' => $this->getLayout()->createBlock('abtest/adminhtml_form_cohort')->setCohort('Control')->toHtml(),
            'active'  => false
        ));

        $this->addTab('cohort_2', array(
            'label'   => Mage::helper('core/data')->__('Variant A'),
            'title'   => Mage::helper('core/data')->__('Variant A'),
            'content' => $this->getLayout()->createBlock('abtest/adminhtml_form_cohort')->setCohort('A')->toHtml(),
            'active'  => false
        ));

        $this->addTab('cohort_3', array(
            'label'   => Mage::helper('core/data')->__('Variant B'),
            'title'   => Mage::helper('core/data')->__('Variant B'),
            'content' => $this->getLayout()->createBlock('abtest/adminhtml_form_cohort')->setCohort('B')->toHtml(),
            'active'  => false,
            'style'   => 'display: none',
        ));

        $this->addTab('cohort_4', array(
            'label'   => Mage::helper('core/data')->__('Variant C'),
            'title'   => Mage::helper('core/data')->__('Variant C'),
            'content' => $this->getLayout()->createBlock('abtest/adminhtml_form_cohort')->setCohort('C')->toHtml(),
            'active'  => false,
            'style'   => 'display: none',
        ));

        $this->addTab('cohort_5', array(
            'label'   => Mage::helper('core/data')->__('Variant D'),
            'title'   => Mage::helper('core/data')->__('Variant D'),
            'content' => $this->getLayout()->createBlock('abtest/adminhtml_form_cohort')->setCohort('D')->toHtml(),
            'active'  => false,
            'style'   => 'display: none',
        ));

        return parent::_beforeToHtml();
    }

}
