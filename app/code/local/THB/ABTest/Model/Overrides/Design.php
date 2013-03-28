<?php

class THB_ABTest_Model_Overrides_Design extends Mage_Catalog_Model_Design
{

    /**
     * So when loading a category's design settings we use a method in the 
     * category's Resource model called `getParentDesignCategory`. This loads 
     * a separate category which doesn't have our injected variation XML in.
     *
     * This method overrides the original getDesignSettings code to ensure our 
     * injected XML is always added to the returned layout updates array key.
     *
     * @since 0.0.1
     * @param Mage_Catalog_Model_Category|Mage_Catalog_Model_Product $object
     * @retur array
     */
    public function getDesignSettings($object)
    {
        $settings = parent::getDesignSettings($object);

        # Have we got injected data?
        if ($object->getData('_abtest_injected_xml'))
        {
            # Get the current layout updates and add our stuff
            $layout_updates   = $settings->getData('layout_updates');
            if ($layout_updates == NULL)
            {
                $layout_updates = array($object->getData('_abtest_injected_xml'));
            }
            else
            {
                $layout_updates[] = $object->getData('_abtest_injected_xml');
            }

            $settings->setData('layout_updates', $layout_updates);
        }

        return $settings;
    }
}
