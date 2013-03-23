<?php

class THB_ABTest_Block_Adminhtml_View_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    public function __construct()
    {
        parent::__construct();

        # Hide the pager and filter so we only show a basic grid
        # Also, we're not using a container - the header is covered in the base 
        # view.phtml template.
        $this->setPagerVisibility(false);
        $this->setFilterVisibility(false);
    }

    protected function _prepareCollection()
    {
        $this->setCollection(Mage::getModel('abtest/Variation')->getResourceCollection());
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $helper = Mage::helper('core/data');

        $this->addColumn('name', array(
            'header' => $helper->__('Variation Name'),
            'align'  => 'left',
            # 'width'  => '100px',
            'index'  => 'name',
        ));

        $this->addColumn('conversion_rate', array(
            'header' => $helper->__('Conversion Rate'),
            'align'  => 'left',
            'width'  => '100px',
            'index'  => 'conversion_rate',
            'getter' => 'getConversionRateAsString',
            'renderer' => 'THB_ABTest_Block_Adminhtml_View_RawColumn'
        ));

        $this->addColumn('statistical_confidence', array(
            'header' => $helper->__('Chance to beat control'),
            'align'  => 'left',
            'width'  => '100px',
            'index'  => 'statistical_confidence',
            'getter' => 'getStatisticalConfidence',
            'renderer' => 'THB_ABTest_Block_Adminhtml_View_RawColumn'
        ));

        $this->addColumn('conversions_visitors', array(
            'header' => $helper->__('Conversions / Visitors'),
            'align'  => 'left',
            'width'  => '100px',
            'index'  => 'conversions',
            'getter' => 'getConversionsOverVisitors'
        ));

        $this->addColumn('percentage', array(
            'header' => $helper->__('Visitor percentage'),
            'align'  => 'left',
            'width'  => '100px',
            'index'  => 'split_percentage',
            'getter' => 'getSplitPercentageAsString'
        ));

        $this->addColumn('value', array(
            'header' => $helper->__('Value'),
            'align'  => 'left',
            'width'  => '100px',
            'index'  => 'total_value',
            'renderer' => 'Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Currency',
            'currency_code' => Mage::app()->getStore()->getCurrentCurrencyCode()
        ));

        $this->addColumn('preview', array(
            'header' => $helper->__('Preview'),
            'align'  => 'center',
            'width'  => '75px',
            'renderer' => 'THB_ABTest_Block_Adminhtml_View_PreviewColumn',
        ));


    }
}
