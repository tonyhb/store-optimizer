<?php

class THB_ABTest_Block_Adminhtml_Form extends Mage_Adminhtml_Block_Template
{

    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('abtest/form.phtml');
        $this->setId('abtest_edit');
    }

}
