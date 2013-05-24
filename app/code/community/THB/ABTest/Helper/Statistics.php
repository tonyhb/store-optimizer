<?php

class THB_ABTest_Helper_Statistics extends Mage_Core_Helper_Data
{

    public function calculate_probability($P, $Pc, $N, $Nc)
    {
        $X  = $P - $Pc;

        if ($X < 0)
        {
            # Our null hypothesis is that X > 0, so we're not even going to do 
            # any maths if this doesn't work out
            return '-';
        }

        $denominator = ($P * (1 - $P) / $N) + ($Pc * (1 - $Pc) / $Nc);
        $denominator = sqrt($denominator);

        if ($denominator == 0 OR $X == 0)
        {
            return "<small>N/A</small>";
        }

        $Z = $X / $denominator;

        # For single-tailed Z scores a level of 1.65 represents greater than 
        # a 95% confidence, but we're not going to use this - we're going to use 
        # a normal distribution calculation to get our actual confidence level
        #
        # if ($Z > 1.65) {
        #     return '> 95%';
        # }

        # We've now got a one-tailed Z score from the standard deviation of our 
        # populations and our means. We can use this to calculate statistical 
        # confidence.
        return $this->_normal_distribution($Z);
    }

    protected function _normal_distribution($z_score)
    {
        return 0.5 * (1 - $this->_erf(abs($z_score)/sqrt(2)));
    }

    protected function _erf($x)
    {
        //A&S formula 7.1.26
        $a1 = 0.254829592;
        $a2 = -0.284496736;
        $a3 = 1.421413741;
        $a4 = -1.453152027;
        $a5 = 1.061405429;
        $p = 0.3275911;
        $x = abs($x);
        $t = 1 / (1 + $p * $x);

        # Horner's method takes O(n) operations for nth order polynomial
        return 1 - (((((($a5 * $t + $a4) * $t) + $a3) * $t + $a2) * $t) + $a1) * $t * exp(-1 * $x * $x);
    }
}
