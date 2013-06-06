<?php

require 'Base.php';

# Attempt to load the test we just created
$test = Mage::getModel('abtest/test')
    ->getCollection()
    ->addFieldToFilter("name", "All Pages test")
    ->getFirstItem();

if ($test->getData() == array()) {
    header("HTTP/1.0 400 Bad Request");
    exit;
}

$variations= Mage::getModel("abtest/variation")
    ->getCollection()
    ->addFieldToFilter("test_id", $test->getId());

# Echo out the test data so CasperJS can confirm that the data is correct. This 
# is the ugly side of our tests using acceptance testing.

?>

<h1 id="test-name"><?php echo $test->getName(); ?></h1>
<p id="start-date"><?php echo $test->getStartDate(); ?></p>
<p id="end-date"><?php echo $test->getEndDate(); ?></p>
<p id="observer-target"><?php echo $test->getObserverTarget(); ?></p>
<p id="observer-conversion"><?php echo $test->getObserverConversion(); ?></p>
<p id="test-new-visitors"><?php echo $test->getOnlyTestNewVisitors(); ?></p>
<p id="visitors"><?php echo $test->getVisitors(); ?></p>
<p id="views"><?php echo $test->getViews(); ?></p>
<p id="conversions"><?php echo $test->getConversions(); ?></p>
<br />

<p id="total-variations"><?php echo $variations->count(); ?></p>
<br />

<?php foreach ($variations as $variation): ?>
    <p class="<?php echo strtolower(str_replace(' ', '-', $variation->getName())); ?> variation-<?php echo $variation->getId(); ?> name"><?php echo $variation->getName(); ?></p>
    <p class="<?php echo strtolower(str_replace(' ', '-', $variation->getName())); ?> variation-<?php echo $variation->getId(); ?> split_percentage"><?php echo $variation->getSplitPercentage(); ?></p>
    <p class="<?php echo strtolower(str_replace(' ', '-', $variation->getName())); ?> variation-<?php echo $variation->getId(); ?> visitors"><?php echo $variation->getvisitors(); ?></p>
    <p class="<?php echo strtolower(str_replace(' ', '-', $variation->getName())); ?> variation-<?php echo $variation->getId(); ?> vuews"><?php echo $variation->getViews(); ?></p>
    <p class="<?php echo strtolower(str_replace(' ', '-', $variation->getName())); ?> variation-<?php echo $variation->getId(); ?> conversions"><?php echo $variation->getConversions(); ?></p>
    <p class="<?php echo strtolower(str_replace(' ', '-', $variation->getName())); ?> variation-<?php echo $variation->getId(); ?> xml"><?php echo $variation->getLayoutUpdate(); ?></p>
    <br /> 
<?php endforeach; ?>
