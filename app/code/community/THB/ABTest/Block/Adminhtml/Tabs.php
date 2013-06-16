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
        $cohort_block = $this->getLayout()->createBlock('abtest/adminhtml_form_cohort');

        $this->addTab('main', array(

            'label'   => Mage::helper('core/data')->__('Test information'),
            'title'   => Mage::helper('core/data')->__('Test information'),
            'content' => $this->getLayout()->createBlock('abtest/adminhtml_form_settings')->toHtml(),
            'active'  => true
        ));

        $this->addTab('cohort_1', array(
            'label'   => Mage::helper('core/data')->__('Control'),
            'title'   => Mage::helper('core/data')->__('Control'),
            'content' => $cohort_block->setCohort('Control')->initForm(),
            'active'  => false,
            'class'   => 'cohort-label cohort_Control_name',
        ));

        $this->addTab('cohort_2', array(
            'label'   => Mage::helper('core/data')->__('Variation A'),
            'title'   => Mage::helper('core/data')->__('Variation A'),
            'content' => $cohort_block->setCohort('A')->initForm(),
            'active'  => false,
            'class'   => 'cohort-label cohort_A_name',
        ));

        $this->addTab('cohort_3', array(
            'label'   => Mage::helper('core/data')->__('Variation B'),
            'title'   => Mage::helper('core/data')->__('Variation B'),
            'content' => $cohort_block->setCohort('B')->initForm(),
            'active'  => false,
            'style'   => 'display: none',
            'class'   => 'cohort-label cohort_B_name',
        ));

        $this->addTab('cohort_4', array(
            'label'   => Mage::helper('core/data')->__('Variation C'),
            'title'   => Mage::helper('core/data')->__('Variation C'),
            'content' => $cohort_block->setCohort('C')->initForm(),
            'active'  => false,
            'style'   => 'display: none',
            'class'   => 'cohort-label cohort_C_name',
        ));

        $this->addTab('cohort_5', array(
            'label'   => Mage::helper('core/data')->__('Variation D'),
            'title'   => Mage::helper('core/data')->__('Variation D'),
            'content' => $cohort_block->setCohort('D')->initForm(),
            'active'  => false,
            'style'   => 'display: none',
            'class'   => 'cohort-label cohort_D_name',
        ));

        return parent::_beforeToHtml();
    }

}
