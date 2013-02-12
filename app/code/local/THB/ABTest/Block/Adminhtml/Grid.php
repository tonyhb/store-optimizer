<?php

class THB_ABTest_Block_Adminhtml_Grid extends Mage_Adminhtml_Block_Widget_Grid
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
            'index'  => 'is_active'
        ));

        $this->addColumn('description', array(
            'header' => $helper->__('Description'),
            'align'  => 'left',
            'index'  => 'description'
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
            'index'  => 'end_date'
        ));

    }

    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/view', array('id' => $row->getId()));
    }

}
