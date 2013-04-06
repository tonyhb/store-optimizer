<?php

/**
 * The controller which handles incoming requests and performs the necessary 
 * actions for managing A/B tests.
 *
 * @author Tony Holdstock-Brown
 * @since  0.0.1
 */
class THB_ABTest_Admin_ABTestController extends Mage_Adminhtml_Controller_Action
{

    /**
     * Run from all actions which render a view. This loads our layout for 
     * rendering and sets the A/B test menu item as selected.
     *
     * @since 0.0.1
     */
    protected function _init()
    {
        $this->loadLayout()->_setActiveMenu('catalog/abtest');
        return $this;
    }

    /**
     * Loads a grid of A/B tests
     *
     * @since 0.0.1
     */
    public function indexAction()
    {
        $this->_init()
            ->_addContent($this->getLayout()->createBlock('abtest/adminhtml_list_grid_container'))
            ->renderLayout();
    }

    /**
     * Shows the new A/B test form.
     *
     * This loads our tab block on the left and the empty form container as 
     * content.
     *
     * The tab block adds each tab and adds content to our form container.
     *
     * @since 0.0.1
     */
    public function newAction()
    {
        $this->_init()
            ->_addLeft($this->getLayout()->createBlock('abtest/adminhtml_tabs'))
            ->_addContent($this->getLayout()->createBlock('abtest/adminhtml_form'))
            ->renderLayout();
    }

    public function viewAction()
    {
        # Create a new view block and add the children
        $view = $this->getLayout()->createBlock('abtest/adminhtml_view');

        $view->setChild('grid', $this->getLayout()->createBlock('abtest/adminhtml_view_grid'));
        $view->setChild('graph', $this->getLayout()->createBlock('abtest/adminhtml_view_graph'));

        $this->_init()
            ->_addContent($view)
            ->renderLayout();
    }

    /**
     * The form action for creating a new A/B test and its cohorts.
     *
     * @since 0.0.1
     */
    public function saveAction()
    {
        if ($data = $this->getRequest()->getPost())
        {
            # Let's do a sanity check for the date. The date must either be 
            # today or in the future - we don't want the start date to be Jan 1, 
            # 1979, or we'll have 40 years of data to show in the graph LOL.
            $today = new DateTime(Date('Y-m-d'));
            $start_date = new DateTime($data['test']['start_date']);
            if ($start_date < $today)
            {
                // Throw an error, baby
                die("Oh no you di-uhnt! This test can't start in the past!");
            }

            # @TODO: Check for running tests with the same observers.
            # @TODO: We should allow tests to have the same conversion observer.

            try
            {
                $test = Mage::getModel('abtest/test')->addData($data['test']);
                $test->save();

                $variations = 0;
                foreach ($data['cohort'] as $variation)
                {
                    if ($variations >= $data['cohorts']) break;

                    $variations++;
                    $model = Mage::getModel('abtest/variation')->setData($variation);
                    $model->setTestId((int) $test->getId());
                    $model->save();
                }

            } catch (Exception $e) {
                # @TODO: Add exception handling
            }
        }

        $this->_redirect('*/*/view', array('id' => $test->getId(), '_current' => true));
    }

    /**
     * Called when an admin wants to preview a variation's XML.
     *
     */
    public function previewAction()
    {
        if ($variation_id = $this->getRequest()->getParam('id'))
        {
            $variation = Mage::getModel('abtest/variation')->load($variation_id);
            $test      = Mage::getModel('abtest/test')->load($variation['test_id']);

            $data = array(
                'init_at'        => date('Y-m-d H:i:s'),
                'observer'       => $test->getData('observer_target'),
                'xml'            => $variation->getData('layout_update'),
                'variation_name' => $variation->getData('name'),
                'test_name'      => $test->getData('name'),
                'running'        => TRUE, # Is this test running already?
                'key'            => Mage::getSingleton('core/session')->getFormKey(),
            );
        }
        else if ($data = $this->getRequest()->getPost())
        {
            $data = array(
                'init_at'        => date('Y-m-d H:i:s'),
                'observer'       => $data['observer'],
                'xml'            => $data['xml'],
                'variation_name' => $data['variation_name'],
                'test_name'      => 'Unsaved test',
                'running'        => FALSE, # Is this test running already?
                'key'            => Mage::getSingleton('core/session')->getFormKey(),
            );
        }

        $data = Mage::helper('core')->jsonEncode($data);

        Mage::getSingleton('core/cookie')->set('test_preview', $data, (Mage::getStoreConfig('abtest/settings/preview_length') * 60));

        $this->_redirectUrl(Mage::getStoreConfig('web/unsecure/base_url'));
    }

    public function exitPreviewAction()
    {
        $referrer = $_SERVER['HTTP_REFERER'];
        if ( ! $referrer OR strpos($referrer, 'exitPreview') != -1)
        {
            $referrer = '/';
        }

        Mage::getSingleton('core/cookie')->delete('test_preview');
        echo "<html><head><meta http-equiv='refresh' content='1;URL=\"$referrer\"'></head><body><script>window.close();</script><p style='font: 16px/1.5 Helvetica, Arial, sans-serif; text-align: center; margin-top: 100px'>Redirecting...</p></body>";
    }

}
