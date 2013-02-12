<?php

class THB_ABTest_Block_Adminhtml_Form_Container extends Mage_Adminhtml_Block_Widget_Form_Container
{

    public function __construct()
    {
        # Ensure we don't completely override the parent's functionality
        parent::__construct();

        # This tells the form which class to load
        $this->_blockGroup = 'abtest';
        $this->_controller = 'adminhtml';
        $this->_mode = 'form_new'; # We want to keep everything tidy in the form folder

        $this->_objectId = 'id';

        $this->_headerText = 'New A/B Test';
    }

}
