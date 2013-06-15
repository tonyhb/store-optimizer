# The following just sets up some environment variables used in each test.
#
# It would be cool if we could move these to a casper pre-script, but it doesn't
# seem like we can create global variables or assign properties to the casper
# object in presecripts. 
version  = casper.cli.get("v") || "1_4_2_0"
url      = casper.cli.get("url") || "http://127.0.0.1:8888/"
magento  = url + version + "/"
username = casper.cli.get("u") || "admin"
password = casper.cli.get("p") || "password1"
moment   = require('moment');


casper.echo "Testing: 1 - All Pages - Test Creation", "GREEN_BAR"

# Pre. we have to truncate the test tables.
casper.start url + "Extension/tests/database/Truncate.php?version=" + version, ->
    @echo "The A/B test tables have been truncated.", "INFO"


# 1. Load the admin panel
casper.thenOpen magento + "admin", ->
    # We may already be logged in
    if @getTitle() == "Dashboard / Magento Admin"
        @test.comment "We're already logged in, skipping the login page."
        return
    @test.assertTitle "Log into Magento Admin Page", "We're at the login page"
    @fill("form#loginForm", { "login[username]": username, "login[password]": password }, true)


# 2. Navigate to the A/B test overview page
casper.then ->
    @test.assertTitle "Dashboard / Magento Admin", "We logged in to the dashboard successfully"
    # Find the A/B test URL
    link = @evaluate ->
        anchors = document.getElementById("nav").getElementsByTagName("A")
        for link in anchors
            return link if link.href.indexOf("abtest") > 0
    @open(link.href)


# 3. Navigate to the A/B test creation page
casper.then ->
    @test.assertTitle "Manage A/B Tests / Magento Admin", "We're on the 'Manage A/B tests' page"
    @clickLabel("New A/B Test")


# 
# Create a test
#
casper.then ->
    @test.assertTitle "New A/B Test / Magento Admin", "We're on the 'New A/B test' page"

    # Try validating an empty form
    @test.assertEvalEquals( ->
        return abTestForm.validate()
    , false, "Validating an incomplete A/B test form returns false")

    @fill("form#abtest_form", {
        "test[name]": "All Pages test",
        "test[start_date]": moment().format("MMM D, YYYY")
        "target_observer_select": "*",
        "test[observer_target]": "*",
        "test[observer_conversion]": "checkout_cart_add_product_complete",
        "cohorts": "3",
        "test[only_test_new_visitors]": "0",
        "cohort[Control][name]": "Control",
        "cohort[Control][layout_update]": '<reference name="after_body_start"><block name="ab.test.block" type="core/text"><action method="setText"><text> &lt;h1 id="all-pages-acceptance-test"&gt;All Pages test: Control&lt;/h1&gt;</text></action></block></reference>'
        "cohort[A][layout_update]": '<reference name="after_body_start"><block name="ab.test.block" type="core/text"><action method="setText"><text> &lt;h1 id="all-pages-acceptance-test"&gt;All Pages test: Variation A&lt;/h1&gt;</text></action></block></reference>'
        "cohort[B][layout_update]": '<reference name="after_body_start"><block name="ab.test.block" type="core/text"><action method="setText"><text> &lt;h1 id="all-pages-acceptance-test"&gt;All Pages test: Variation B&lt;/h1&gt;</text></action></block></reference>'
    }, false);

    # Ensure traffic is split evenly.
    @clickLabel("(split evenly)", "a")

    # Hit the preview for Variation Aand see if previewing works
    @click("#cohort_A_preview")


#
# Wait for the preview to pop up
#
casper.waitForPopup(magento, ->
    @test.assertEquals(@.popups.length, 1, "Previewing a variation opened the homepage in a popup")
, ->
    console.log @popups
    @fail "Couldn't load the preview on time"
, 30000)


#
# Ensure the preview looks as expected
#
casper.withPopup magento, ->
    @test.assertExists("#thb_abtest_preview", "The preview bar exists when previewing a page")
    @test.assertTextExists("All Pages test (Unsaved)", "The test name is correct in the preview bar")
    @test.assertExists("#all-pages-acceptance-test", "The page preview has the layout update XML applied from the variation")
    @test.assertSelectorHasText("#all-pages-acceptance-test", "All Pages test: Variation A", "The layout update has the correct content from the variation")
    # Exit the preview and destroy the preview cookie[
    @clickLabel("Exit preview")

#
# Save our test by clicking on the save button.
#
casper.then ->
    @test.assertEvalEquals( ->
        return abTestForm.validate()
    , true, "Validating a complete A/B test form returns true")
    @click ".content-header .save"


casper.wait 12000


#
# We should have then saved our test and be on our view test page
#
casper.then ->
    @test.assertUrlMatch(/abtest\/view/, "We're on a view test page after hitting save")
    @test.assertSelectorHasText(".head-adminhtml", "Test: All Pages test", "The visible test heading matches that of the saved test")


#
# Now that we've created our test, we need to ensure that the database holds the
# correct data. We can't do this from Casper or Phantom, though, so we're going
# to load a PHP script in the databases test directory which will load the data
# for us.
#
casper.thenOpen url + "Extension/tests/database/All-pages-test.php?version=" + version, ->
    @test.assertSelectorHasText "#test-name", "All Pages test", "The test name is saved correctly"
    @test.assertSelectorHasText "#start-date", moment().format("YYYY-MM-DD"), "The test start date is saved correctly"
    @test.assertSelectorHasText "#observer-target", "*", "The observer target is saved correctly"
    @test.assertSelectorHasText "#observer-conversion", "checkout_cart_add_product_complete", "The observer conversion is saved correctly"
    @test.assertSelectorHasText "#test-new-visitors", "0", "The test includes everyone (not just new visitors)"
    @test.assertSelectorHasText "#visitors", "0", "There aren't any visitors (as the test has just been created)"
    @test.assertSelectorHasText "#views", "0", "There aren't any views (as the test has just been created)"
    @test.assertSelectorHasText "#conversions", "0", "There aren't any conversions (as the test has just been created)"
    @test.assertSelectorHasText "#total-variations", "3", "All three variations were saved"
    @test.assertSelectorHasText ".control.name", "Control", "The control is named correctly"
    @test.assertSelectorHasText ".control.split_percentage", "33", "The control has the correct split percentage"
    @test.assertEvalEquals ->
        return document.querySelector(".control.xml").innerHTML
    , '<reference name="after_body_start"><block name="ab.test.block" type="core/text"><action method="setText"><text> &lt;h1 id="all-pages-acceptance-test"&gt;All Pages test: Control&lt;/h1&gt;</text></action></block></reference>', "The control has the correct layout update"
    @test.assertSelectorHasText ".variation-a.name", "Variation A", "The first variation is named correctly"
    @test.assertSelectorHasText ".variation-a.split_percentage", "33", "The first variation has the correct split percentage"
    @test.assertEvalEquals ->
        return document.querySelector(".variation-a.xml").innerHTML
    , '<reference name="after_body_start"><block name="ab.test.block" type="core/text"><action method="setText"><text> &lt;h1 id="all-pages-acceptance-test"&gt;All Pages test: Variation A&lt;/h1&gt;</text></action></block></reference>', "The first variation has the correct layout update"
    @test.assertSelectorHasText ".variation-b.name", "Variation B", "The second variation is named correctly"
    @test.assertSelectorHasText ".variation-b.split_percentage", "33", "The second variation has the correct split percentage"
    @test.assertEvalEquals ->
        return document.querySelector(".variation-b.xml").innerHTML
    , '<reference name="after_body_start"><block name="ab.test.block" type="core/text"><action method="setText"><text> &lt;h1 id="all-pages-acceptance-test"&gt;All Pages test: Variation B&lt;/h1&gt;</text></action></block></reference>', "The second variation has the correct layout update"

    @echo "The first test has been created successfully", "GREEN_BAR"

casper.run ->
    @test.done()
