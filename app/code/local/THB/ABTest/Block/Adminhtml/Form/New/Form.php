<?php

class THB_ABTest_Block_Adminhtml_Form_New_Form extends Mage_Adminhtml_Block_Widget_Form {

    protected $_form;
    protected $_helper;

    public function _prepareForm()
    {
        $this->_helper = Mage::helper('core/data');
        $data = array();

        $this->_form = new Varien_Data_Form(array(
            'id' => 'edit_form',
            'action'  => $this->getUrl('*/*/create', array('id' => $this->getRequest()->getParam('id'))),
            'method'  => 'post',
            'enctype' => 'multipart/form-data'
        ));

        $this->_addDateFieldsetToForm();
        $this->_addObserverFieldsetToForm();

        $this->_form->setUseContainer(true);
        $this->setForm($this->_form);
    }

    /**
     * Adds the Date section of the form. Called from _prepareForm()
     * 
     * @return void
     */
    protected function _addDateFieldsetToForm()
    {
        $fieldset = $this->_form->addFieldset('date_fieldset', array(
            'legend' => $this->_helper->__('Date settings')
        ));

        $fieldset->addField('el_start_date', 'date', array(
            'label' => $this->_helper->__('Start date'),
            'name'  => 'start_date',
            'style' => 'width: 200px',
            'required' => TRUE,

            # Date format specific settings
            'format'   => 'd/M/yyyy',
            'time'     => TRUE,
            'image'    => $this->getSkinUrl('images/grid-cal.gif')
        ));

        $fieldset->addField('el_end_date', 'date', array(
            'label' => $this->_helper->__('End date'),
            'name'  => 'end_date',
            'note'  => 'Leave blank to leave running indefinitely',
            'style' => 'width: 200px',

            # Date format specific settings
            'format'   => 'd/M/yyyy',
            'time'     => TRUE,
            'image'    => $this->getSkinUrl('images/grid-cal.gif')
        ));
    }

    /**
     * Adds the objserver fieldset to the form
     *
     */
    protected function _addObserverFieldsetToForm()
    {
        $fieldset = $this->_form->addFieldset('observer_fieldset', array(
            'legend' => $this->_helper->__('A/B Test Settings')
        ));

        $fieldset->addField('el_target_observer', 'select', array(
            'label' => 'Test page',
            'name'  => 'test_observer',
            'style' => 'width: 300px',
            'required' => TRUE,

            # Select box specific settings
            'options'  => array(
                'catalog_controller_product_view' => 'Product page'
            ),
        ));

        $fieldset->addField('el_conversion_observer', 'select', array(
            'label' => 'Conversion action',
            'name'  => 'conversion_observer',
            'style' => 'width: 300px',
            'required' => TRUE,

            # Select box specific settings
            'options'  => array(
                'checkout_onepage_controller_success_action' => 'Completed checkout'
            ),
        ));
    }

}
