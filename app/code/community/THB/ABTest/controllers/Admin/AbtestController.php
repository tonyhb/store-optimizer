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
            ->_title("Manage A/B Tests")
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
     * Note that we've made forms using HTML instead of using varien objects for 
     * speed. There's no difference in usability or the results.
     *
     * @since 0.0.1
     */
    public function newAction()
    {
        $this->_init()
            ->_addLeft($this->getLayout()->createBlock('abtest/adminhtml_tabs'))
            ->_addContent($this->getLayout()->createBlock('abtest/adminhtml_form'))
            ->_title("New A/B Test")
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
            ->_title("View A/B Test")
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
                    if ($variations == 1) {
                        $model->setIsControl(true);
                    }
                    $model->save();
                }

            } catch (Exception $e) {
                $this->_redirect('*/*/new', array('_current' => true));
            }
        }

        $this->_redirect('*/*/view', array('id' => $test->getId(), '_current' => true));
    }

    /**
     * Used when creating a new A/B test. Ensures data is valid.
     *
     * @since 0.0.1
     */
    public function validateAction()
    {
        $errors = array();
        $valid  = TRUE;
        $data   = $this->getRequest()->getPost();

        $test       = Mage::getModel('abtest/test')->addData($data['test']);
        $validation = $test->validate();
        if ($validation !== TRUE) {
            $valid  = FALSE;
            $errors = $validation;
        }

        $variations = 0;
        foreach ($data['cohort'] as $variation)
        {
            if ($variations >= $data['cohorts']) break;

            $variations++;
            $model = Mage::getModel('abtest/variation')->setData($variation);
            $model->setTestId(0);
            $validation = $model->validate();
            if ($validation !== TRUE) {
                $valid  = FALSE;
                $errors = array_merge($errors, $validation);
            }
        }

        if ( ! $valid)
        {
            $string = "<ul class='messages'><li class='error-msg'><ul>";
            foreach ($errors as $error) {
                $string .= '<li><span>'.$error.'</span></li>';
            }
            $string .= '</ul></li></ul>';

            echo Mage::helper('core')->jsonEncode(array("error" => true, "message" => $string));
            return;
        }

        echo Mage::helper('core')->jsonEncode(array("error" => false));
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

            $data = array_merge($variation->getData(), array(
                'init_at'        => date('Y-m-d H:i:s'),
                'observer'       => $test->getData('observer_target'),
                'is_control'     => (bool) $variation->getData('is_control'),
                'test_name'      => $test->getData('name'),
                'running'        => TRUE, # This is run from the test overview page, which means the test is running
                'key'            => Mage::getSingleton('core/session')->getFormKey(),
            ));
        }
        else if ($post = $this->getRequest()->getPost())
        {
            $data = $post['cohort'][$post['used_cohort']]; # Get the used cohort's data
            $data += array(
                'init_at'        => date('Y-m-d H:i:s'),
                'observer'       => $post['test']['observer_target'],
                'is_control'     => (bool) $post['is_control'],
                'test_name'      => $post['test']['name'],
                'running'        => FALSE, # This is run from the create test page, so the test isn't running already
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

    public function stopAction()
    {
        if ($test_id = $this->getRequest()->getParam('id'))
        {
            $test = Mage::getModel('abtest/test')->load($test_id);
            $test->setIsActive('0');
            $test->save();
            $this->_redirect('*/*/view', array('id' => $test->getId(), '_current' => true));
            return;
        }

        $this->_redirect('*/*/');
    }

}
