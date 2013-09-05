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
            'back'   => $this->getLayout()->createBlock('Mage_Adminhtml_Block_Widget_Button'),
        );

        $children['back']
            ->setOnClick("setLocation('".$this->getUrl('*/*/index')."')")
            ->setClass("back")
            ->setLabel("Back");

        if ($this->getTest()->getIsActive() != "0") {
            $children["stop"] = $this->getLayout()->createBlock('Mage_Adminhtml_Block_Widget_Button');
            $children['stop']
                ->setOnClick("confirmSetLocation('Are you sure you want to stop this test?', '".$this->getUrl('*/*/stop/id/'.$this->_test->getId())."')")
                ->setClass("delete")
                ->setLabel("Stop test");
        }

        $html = '';
        foreach($children as $button)
        {
            $html .= $button->_toHtml();
        }

        return $html;
    }

}
