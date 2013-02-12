<?php

class THB_ABTest_Block_Adminhtml_Grid_Container extends Mage_Adminhtml_Block_Widget_Grid_Container
{

    protected $_addButtonLabel = "New A/B Test";

    public function __construct()
    {
        parent::__construct();

        $this->_blockGroup = 'abtest';
        $this->_controller = 'adminhtml';

        $this->_headerText = 'A/B Tests';
        $this->_updateButton('add', 'onclick', "setLocation('".$this->getUrl('*/*/new')."')");
    }

}
