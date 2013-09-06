<?php

/**
 * The base block class to add tabs for the new/edit form
 *
 */
class THB_ABTest_Block_Adminhtml_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{

    protected $_variations = array();

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
        // Used when viewing a test's settings. Has no effect when creating 
        // a form, but sets a test model when viewing a test's settings
        $test = $this->getTest();
        $coll = $this->getVariationCollection();

        // We have a loaded test: get all elements in a simple one-dimensional 
        // array. We can't use getItems() because the return is ordered by the 
        // primary key, not from offset 0.
        if ($coll) {
            $iterator = $coll->getIterator();
            foreach ($iterator as $item) {
                $this->_variations[] = $item;
            }
        }

        // Ensure all 5 variations have a model.
        while (count($this->_variations) < 5) {
            $this->_variations[] = Mage::getModel("abtest/variation");
        }

        $this->addTab('main', array(
            'label'   => Mage::helper('core/data')->__('Test information'),
            'title'   => Mage::helper('core/data')->__('Test information'),
            'content' => $this->getLayout()->createBlock('abtest/adminhtml_form_settings')->setTest($test)->setVariationCollection($coll)->toHtml(),
            'active'  => true
        ));

        $cohort_block = $this->getLayout()->createBlock('abtest/adminhtml_form_cohort')->setTest($test)->setVariationCollection($coll);
        $this->addTab('cohort_1', array(
            'label'   => $this->_getTabTitle(1),
            'title'   => $this->_getTabTitle(1),
            'content' => $cohort_block->setCohort('Control')->setVariation($this->_variations[0])->initForm(),
            'active'  => false,
            'class'   => 'cohort-label cohort_Control_name',
        ));

        $this->addTab('cohort_2', array(
            'label'   => $this->_getTabTitle(2),
            'title'   => $this->_getTabTitle(2),
            'content' => $cohort_block->setCohort('A')->setVariation($this->_variations[1])->initForm(),
            'active'  => false,
            'class'   => 'cohort-label cohort_A_name',
        ));

        $this->addTab('cohort_3', array(
            'label'   => $this->_getTabTitle(3),
            'title'   => $this->_getTabTitle(3),
            'content' => $cohort_block->setCohort('B')->setVariation($this->_variations[2])->initForm(),
            'active'  => false,
            'style'   => 'display: none',
            'class'   => 'cohort-label cohort_B_name',
        ));

        $this->addTab('cohort_4', array(
            'label'   => $this->_getTabTitle(4),
            'title'   => $this->_getTabTitle(4),
            'content' => $cohort_block->setCohort('C')->setVariation($this->_variations[3])->initForm(),
            'active'  => false,
            'style'   => 'display: none',
            'class'   => 'cohort-label cohort_C_name',
        ));

        $this->addTab('cohort_5', array(
            'label'   => $this->_getTabTitle(5),
            'title'   => $this->_getTabTitle(5),
            'content' => $cohort_block->setCohort('D')->setVariation($this->_variations[4])->initForm(),
            'active'  => false,
            'style'   => 'display: none',
            'class'   => 'cohort-label cohort_D_name',
        ));

        return parent::_beforeToHtml();
    }

    /**
     * Returns either the saved variations name or a new label "Control" or 
     * "Variation N".
     *
     * The argument should be the same iteration number as used in the tab ID 
     * (starting from 1, not 0)
     */
    private function _getTabTitle($cohort) {
        $cohort--;

        if ( ! $name = $this->_variations[$cohort]->getName()) {
            $names = array("Control", "Variation A", "Variation B", "Variation C", "Variation D");
            return $names[$cohort];
        }

        return $name;
    }
}
