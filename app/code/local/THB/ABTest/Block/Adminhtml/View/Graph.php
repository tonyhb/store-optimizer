<?php

class THB_ABTest_Block_Adminhtml_View_Graph extends Mage_Adminhtml_Block_Template
{
    public function __construct()
    {
        parent::__construct();

        $this->setTemplate('abtest/graph.phtml');
        $this->_test = Mage::getModel('abtest/test')->load($this->getRequest()->getParam('id'));
    }

    public function getTest()
    {
        return $this->_test;
    }

}
