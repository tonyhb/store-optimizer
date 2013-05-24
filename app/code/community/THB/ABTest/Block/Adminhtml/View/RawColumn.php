<?php

class THB_ABTest_Block_Adminhtml_View_RawColumn extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{

    public function _getValue(Varien_Object $model)
    {
        $defaultValue = $this->getColumn()->getDefault();
        $data = parent::_getValue($model);

        $string = is_null($data) ? $defaultValue : $data;

        return $string;
    }

}
