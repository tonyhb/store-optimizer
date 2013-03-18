<?php

class THB_ABTest_Block_Adminhtml_View_PreviewColumn extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{

    /**
     * Method to create a 'Preview' button for the preview column when viewing 
     * a test
     *
     */
    public function _getValue(Varien_Object $model)
    {
        $button = $this->getLayout()->createBlock('Mage_Adminhtml_Block_Widget_Button');

        $button
            ->setOnClick("setLocation('".$this->getUrl('*/*/index')."')")
            ->setClass("preview")
            ->setLabel("Preview");

        return $button->_toHtml();
    }

}
