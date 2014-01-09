<?php

/**
 *
 *
 */
class THB_ABTest_Block_Adminhtml_View extends Mage_Adminhtml_Block_Template
{

    public function __construct()
    {
        parent::__construct();

        $this->setTemplate('abtest/view.phtml');
        $this->_test = Mage::getModel('abtest/test')->load($this->getRequest()->getParam('id'));
    }

    public function getTest()
    {
        return $this->_test;
    }

    public function getButtonHtml()
    {
        $children = array(
            "back"   => $this->getLayout()->createBlock("Mage_Adminhtml_Block_Widget_Button"),
            "view"   => $this->getLayout()->createBlock("Mage_Adminhtml_Block_Widget_Button"),
        );

        $children['back']
            ->setOnClick("setLocation('".$this->getUrl('*/*/index')."')")
            ->setClass("back")
            ->setLabel("Back");

        $children['view']
            ->setOnClick("setLocation('".$this->getUrl('*/*/settings/id/'.$this->_test->getId())."')")
            ->setClass("view")
            ->setLabel("View");

        $status = $this->getTest()->getStatus();
        # When the test is running or paused (manually), we create a button to either 
        # stop or start the test. This leaves the status of '0' without 
        # a button.
        if ($status != 0) {
            $children["action"] = $this->getLayout()->createBlock('Mage_Adminhtml_Block_Widget_Button');
        }

        # If the test is running, show a "Stop" button.
        if ($status == 1) { 
            $children['action']
                ->setOnClick("confirmSetLocation('Are you sure you want to stop this test?', '".$this->getUrl('*/*/stop/id/'.$this->_test->getId())."')")
                ->setClass("delete")
                ->setLabel("Stop test");
        } else if ($status == 2) { 
            $children['action']
                ->setOnClick("confirmSetLocation('Are you sure you want to unpause this test?', '".$this->getUrl('*/*/start/id/'.$this->_test->getId())."')")
                ->setLabel("Resume test");
        }

        $html = '';
        foreach($children as $button)
        {
            $html .= $button->_toHtml();
        }

        return $html;
    }

}
