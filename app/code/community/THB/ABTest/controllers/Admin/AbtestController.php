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
        $this->loadLayout()->_setActiveMenu('abtest');
        return $this;
    }

    /**
     * Loads a grid of A/B tests
     *
     * @since 0.0.1
     */
    public function indexAction()
    {
        $version = Mage::getConfig()->getModuleConfig("THB_ABTest")->version;
        if ($version != "0.0.2")
        {
            Mage::getSingleton('core/session')->addError('The Store Optimizer extension has been upgraded. Please clean your cache to keep your site working!');
        }

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
                $data["test"]["start_date"] = str_replace(",", "", $data["test"]["start_date"]);
                $data["test"]["end_date"] = str_replace(",", "", $data["test"]["end_date"]);

                if ($data["test"]["end_date"] == "-") {
                    unset($data["test"]["end_date"]);
                }

                $test = Mage::getModel('abtest/test')->addData($data['test']);

                # If the start date is in the future, this should be paused 
                # - allowing the user to start it early, if they want.
                $today = strtotime('today');
                $start_date = strtotime($data['test']['start_date']);
                if ($start_date > $today) {
                    $test->setStatus(0);
                } else {
                    $test->setStatus(1);
                }

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
            $data = array_merge(array(
                'init_at'        => date('Y-m-d H:i:s'),
                'observer'       => $post['test']['observer_target'],
                'is_control'     => (bool) $post['is_control'],
                'test_name'      => $post['test']['name'],
                'running'        => FALSE, # This is run from the create test page, so the test isn't running already
                'key'            => Mage::getSingleton('core/session')->getFormKey(),
            ), $data);
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
            # Set the status to '2' - Manually Stopped.
            $this->_changeStatus($test_id, 2);
            $this->_redirect('*/*/view', array('id' => $test_id, '_current' => true));
            return;
        }

        $this->_redirect('*/*/');
    }

    public function startAction()
    {
        if ($test_id = $this->getRequest()->getParam('id'))
        {
            # Set the status to '1' - Running.
            $this->_changeStatus($test_id, 1);
            $this->_redirect('*/*/view', array('id' => $test_id, '_current' => true));
            return;
        }
        $this->_redirect('*/*/');
    }


    protected function _changeStatus($test_id, $status)
    {
        $test = Mage::getModel('abtest/test')->load($test_id);
        $test->setStatus($status);
        $test->save();
    }

    public function settingsAction()
    {
        $test = Mage::getModel('abtest/test')->load($this->getRequest()->getParam('id'));
        $variations = $test->getVariationCollection();

        $this->_init()
            ->_addLeft($this->getLayout()->createBlock('abtest/adminhtml_tabs')->setTest($test)->setVariationCollection($variations))
            ->_addContent($this->getLayout()->createBlock('abtest/adminhtml_form')->setTest($test)->setVariationCollection($variations))
            ->_title($test->getName())
            ->renderLayout();
    }


}
