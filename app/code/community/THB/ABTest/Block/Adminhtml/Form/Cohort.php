<?php

class THB_ABTest_Block_Adminhtml_Form_Cohort extends Mage_Adminhtml_Block_Widget_Form
{

    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('abtest/form/cohort.phtml');

        $this->_data['config_data'] = Mage::getModel("adminhtml/config_data")
            ->setSection("design")
            ->setWebsite("")
            ->setStore("")
            ->load();

        $this->_fieldsetRenderer = Mage::getBlockSingleton("adminhtml/widget_form_renderer_fieldset");
    }

    public function initForm()
    {
        $this->_form = new Varien_Data_Form();

        $this->_addNameFieldset()
            ->_addXmlFieldset()
            ->_addThemeFieldset();

        # We need to add a div around our form. The useContainer form method 
        # adds an actual <form> tag which we already have.
        $cohort = $this->getCohort();
        $html = $this->_form->toHtml();
        return "<div id='cohort-{$cohort}' class='cohort-form'>{$html}</div>";
    }

    protected function _addNameFieldset()
    {
        $baseName = "cohort_".$this->getCohort()."_name";

        # This works exactly the same as having no renderer...
        $fieldset = $this->_form
            ->addFieldset( "$baseName-fieldset", array(
                "legend"   => $this->__("Name"),
            ))
            ->setRenderer($this->_fieldsetRenderer);

        // Set the name and split percentage values according to the cohort
        $percentage = "0";

        if ( ! $this->getVariation()->isObjectNew()) {
            // Use the saved values
            $value = $this->getVariation()->getName();
            $percentage = $this->getVariation()->getSplitPercentage();
        } else if ($this->getCohort() == "Control") {
            $value = "Control";
            $percentage = "70";
        } else {
            $value = "Variation ".$this->getCohort();
            if ($this->getCohort() == "A") {
                $percentage = "30";
            }
        }

        $fieldset->addField("$baseName-field", "text", array(
            "label"    => $this->__("Variation name"),
            "required" => true,
            "value"    => $value,
            "name"     => "cohort[".$this->getCohort()."][name]",
        ));

        $fieldset->addfield("cohort_".$this->getCohort()."_preview", "button", array(
            "value" => "Preview this variation",
            "class" => "form-button scalable preview-variation",
        ));

        $fieldset->addField("cohort_".$this->getCohort()."_percentage", "hidden", array(
            "name"  => "cohort[".$this->getCohort()."][split_percentage]",
            "value" => $percentage
        ));

        return $this;
    }

    protected function _addXmlFieldset()
    {
        $fieldset = $this->_form->addFieldset("cohort_".$this->getCohort()."_xml-fieldset", array(
            "legend"      => $this->__("XML Layout Updates"),
            "table_class" => "form-edit"
        ));

        $fieldset->addField("cohort_".$this->getCohort()."_xml-field", "textarea", array(
            "name"     => "cohort[".$this->getCohort()."][layout_update]",
            "style"    => "width: 97.5%; height: 25em; margin: 5px; font-family: monospace",
            "value"    => $this->getVariation()->getLayoutUpdate(),
        ));

        return $this;
    }

    protected function _addThemeFieldset()
    {
        $regex_renderer = Mage::getBlockSingleton("adminhtml/system_config_form_field_regexceptions");
        $regex_renderer->setForm($this->_form);
        $fieldset = $this->_form->addFieldset("cohort_".$this->getCohort()."_theme", array(
            "legend"      => $this->__("Theme Updates"),
            "table_class" => "form-list theme-table"
        ));

        # Add a note which says that themes can only be tested on all pages. 
        # Note that the $fieldset->addElement can only accept types inheriting 
        # the varien form abstract class. The only usable class for HTML is the 
        # note, which (at least on 1.4) doesn't allow classes, so we need to use 
        # setAfterElementHtml to add a new span with our class - this will be on 
        # all 5 variation forms and saves the ID being duplicated 5 times.
        $all_pages_note = new Varien_Data_Form_Element_Note();
        $all_pages_note->setHtmlId("cohort_".$this->getCohort()."_note")->setAfterElementHtml("<span class='all_pages_note'>Themes can only be tested on all pages</span>");
        $fieldset->addElement($all_pages_note);

        # Uset when getting the data on a view form
        # $model = Mage::getModel("adminhtml/system_config_backend_serialized_array");

        $fieldset->addField("cohort_".$this->getCohort()."_package-field", "text", array(
            "label"    => $this->__("Package name"),
            "name"     => "cohort[".$this->getCohort()."][package]",
            "value"    => $this->getVariation()->getPackage(),
        ));
        $field = $fieldset->addField("cohort_".$this->getCohort()."_package_exceptions-field", "text", array(
            "name"     => "cohort[".$this->getCohort()."][package_exceptions]",
            "comment"  => $this->__("Match expressions in the same order as displayed in the configuration."),
            "value"    => array_filter(unserialize($this->getVariation()->getPackageExceptions())),
        ));
        $field->setRenderer($regex_renderer);

        $fieldset->addField("cohort_".$this->getCohort()."_templates-field", "text", array(
            "label"    => $this->__("Templates"),
            "name"     => "cohort[".$this->getCohort()."][templates]",
            "value"    => $this->getVariation()->getTemplates(),
        ));
        $field = $fieldset->addField("cohort_".$this->getCohort()."_templates_exceptions-field", "text", array(
            "name"     => "cohort[".$this->getCohort()."][templates_exceptions]",
            "value"    => array_filter(unserialize($this->getVariation()->getTemplatesExceptions())),
        ));
        $field->setRenderer($regex_renderer);

        $fieldset->addField("cohort_".$this->getCohort()."_skin-field", "text", array(
            "label"    => $this->__("Skin (Images / CSS)"),
            "name"     => "cohort[".$this->getCohort()."][skin]",
            "value"    => $this->getVariation()->getSkin(),
        ));
        $field = $fieldset->addField("cohort_".$this->getCohort()."_skin_exceptions-field", "text", array(
            "name"     => "cohort[".$this->getCohort()."][skin_exceptions]",
            "value"    => array_filter(unserialize($this->getVariation()->getSkinExceptions())),
        ));
        $field->setRenderer($regex_renderer);

        $fieldset->addField("cohort_".$this->getCohort()."_layout-field", "text", array(
            "label"    => $this->__("Layout"),
            "name"     => "cohort[".$this->getCohort()."][layout]",
            "value"    => $this->getVariation()->getLayout(),
        ));
        $field = $fieldset->addField("cohort_".$this->getCohort()."_layout_exceptions-field", "text", array(
            "name"     => "cohort[".$this->getCohort()."][layout_exceptions]",
            "value"    => array_filter(unserialize($this->getVariation()->getLayoutExceptions())),
        ));
        $field->setRenderer($regex_renderer);

        $fieldset->addField("cohort_".$this->getCohort()."_default-field", "text", array(
            "label"    => $this->__("Default"),
            "name"     => "cohort[".$this->getCohort()."][default]",
            "value"    => $this->getVariation()->getDefault(),
        ));
        $field = $fieldset->addField("cohort_".$this->getCohort()."_default_exceptions-field", "text", array(
            "name"     => "cohort[".$this->getCohort()."][default_exceptions]",
            "value"    => array_filter(unserialize($this->getVariation()->getDefaultExceptions())),
        ));
        $field->setRenderer($regex_renderer);

        return $this;
    }

}
