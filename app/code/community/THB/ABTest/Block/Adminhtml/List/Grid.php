<?php

class THB_ABTest_Block_Adminhtml_List_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    public function __construct()
    {
        parent::__construct();
        $this->setDefaultSortOrder('id');
    }

    protected function _prepareCollection()
    {
        $this->setCollection(Mage::getModel('abtest/test')->getResourceCollection());
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $helper = Mage::helper('core/data');

        $this->addColumn('is_active', array(
            'header' => $helper->__('Status'),
            'align'  => 'left',
            'width'  => '75px',
            'index'  => 'is_active',
            # Uses the getTestStatus method which allows us to use 
            # non-database data in our grid
            'getter' => 'getTestStatus',
        ));

        $this->addColumn('name', array(
            'header' => $helper->__('Test Name'),
            'align'  => 'left',
            'index'  => 'name'
        ));

        $this->addColumn('conversion_rate', array(
            'header' => $helper->__('Conversion Rate'),
            'align'  => 'left',
            'width'  => '125px',
            'index'  => 'conversion_rate',
            'getter' => 'getConversionRateAsString'
        ));

        $this->addColumn('conversions', array(
            'header' => $helper->__('Conversions'),
            'align'  => 'left',
            'width'  => '125px',
            'index'  => 'conversions'
        ));

        $this->addColumn('visitors', array(
            'header' => $helper->__('Visitors'),
            'align'  => 'left',
            'width'  => '125px',
            'index'  => 'visitors'
        ));

        $this->addColumn('views', array(
            'header' => $helper->__('Views'),
            'align'  => 'left',
            'width'  => '125px',
            'index'  => 'views'
        ));

        $this->addColumn('start_date', array(
            'header' => $helper->__('Start Date'),
            'align'  => 'left',
            'width'  => '125px',
            'index'  => 'start_date'
        ));

        $this->addColumn('end_date', array(
            'header' => $helper->__('End Date'),
            'align'  => 'left',
            'width'  => '125px',
            'index'  => 'end_date',
            'getter' => 'getEndDate',
        ));

    }

    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/view', array('id' => $row->getId()));
    }

}
