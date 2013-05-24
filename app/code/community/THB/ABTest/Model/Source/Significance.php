<?php

class THB_ABTest_Model_Source_Significance
{

    public function toOptionArray()
    {
        return array(
            array(
                'value' => 80,
                'label' => '80% confidence'
            ),
            array(
                'value' => 95,
                'label' => '95% confidence',
            ),
            array(
                'value' => 99,
                'label' => '99% confidence',
            )
        );
    }

}
