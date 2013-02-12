<?php

class THB_ABTest_Admin_ABTestController extends Mage_Adminhtml_Controller_Action
{

    protected function _init()
    {
        $this->loadLayout()->_setActiveMenu('catalog/abtest');
        return $this;
    }

    public function indexAction()
    {
        $this->_init()
            ->_addContent($this->getLayout()->createBlock('abtest/adminhtml_grid_container'))
            ->renderLayout();
    }

    public function newAction()
    {
        $this->_init()
            ->_addContent($this->getLayout()->createBlock('abtest/adminhtml_form_container'))
            ->renderLayout();
    }

    public function viewAction()
    {
        $this->_init()->renderLayout();
    }
}
