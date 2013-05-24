<?php

class THB_ABTest_Block_Adminhtml_Form_Settings extends Mage_Adminhtml_Block_Template
{

    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('abtest/form/settings.phtml');
    }

}
