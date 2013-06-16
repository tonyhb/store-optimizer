# This test ensures that:
#  - The "Category Page" hooks only work on the category page
#  - The "Viewed Product" conversion target works correctly
#  - Two tests running concurrently log the correct statistics
#  - Logging only one conversion per visitor works correctly

version  = casper.cli.get("v") || "1_4_2_0"
url      = casper.cli.get("url") || "http://127.0.0.1:8888/"
magento  = url + version + "/"
username = casper.cli.get("u") || "admin"
password = casper.cli.get("p") || "password1"
db_test  = url + "Extension/tests/database/View.php?version=" + version + "&name=Category Page and Viewed Product Conversions test"
moment   = require('moment');

casper.echo "Testing: 2 - Category Page and Viewed Product Conversions - Test Creation", "GREEN_BAR"

# 1. Load the admin panel
casper.start magento + "admin", ->
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

# 4. Create a test
casper.then ->
    @test.assertTitle "New A/B Test / Magento Admin", "We're on the 'New A/B test' page"
    @fill("form#abtest_form", {
        "test[name]": "Category Page and Viewed Product Conversions test",
        "test[start_date]": moment().format("MMM D, YYYY")
        "target_observer_select": "catalog_category_view",
        "test[observer_target]": "catalog_category_view",
        "test[observer_conversion]": "catalog_controller_product_view",
        "cohorts": "2",
        "test[only_test_new_visitors]": "0",
        "cohort[Control][name]": "Control",
        "cohort[Control][layout_update]": ''
        "cohort[A][layout_update]": '<reference name="right"><action method="unsetChildren"></action></reference>'
    }, false);
    # Ensure traffic is split evenly.
    @clickLabel("(split evenly)", "a")
    # We can't preview because CasperJS doesn't support clicking links in popups
    # yet...

# 7. Save our test by clicking on the save button.
casper.then ->
    @test.assertEvalEquals( ->
        return abTestForm.validate()
    , true, "Validating a complete A/B test form returns true")
    @click ".content-header .save"

casper.wait 12000

# 8. We should have then saved our test and be on our view test page
casper.then ->
    @test.assertUrlMatch(/abtest\/view/, "We're on a view test page after hitting save")
    @test.assertSelectorHasText(".head-adminhtml", "Test: Category Page and Viewed Product Conversions test", "The visible test heading matches that of the saved test")

#
# Now that we've created our test, we need to ensure that the database holds the
# correct data. We can't do this from Casper or Phantom, though, so we're going
# to load a PHP script in the databases test directory which will load the data
# for us.
#
casper.thenOpen db_test, ->
    @test.assertSelectorHasText "#test-name", "Category Page and Viewed Product Conversions test", "The test name is saved correctly"
    @test.assertSelectorHasText "#start-date", moment().format("YYYY-MM-DD"), "The test start date is saved correctly"
    @test.assertSelectorHasText "#observer-target", "catalog_category_view", "The observer target is saved correctly"
    @test.assertSelectorHasText "#observer-conversion", "catalog_controller_product_view", "The observer conversion is saved correctly"
    @test.assertSelectorHasText "#test-new-visitors", "0", "The test includes everyone (not just new visitors)"
    @test.assertSelectorHasText "#visitors", "0", "There aren't any visitors (as the test has just been created)"
    @test.assertSelectorHasText "#views", "0", "There aren't any views (as the test has just been created)"
    @test.assertSelectorHasText "#conversions", "0", "There aren't any conversions (as the test has just been created)"
    @test.assertSelectorHasText "#total-variations", "2", "All variations were saved"
    @test.assertSelectorHasText ".control.name", "Control", "The control is named correctly"
    @test.assertSelectorHasText ".control.split_percentage", "50", "The control has the correct split percentage"
    @test.assertEvalEquals ->
        return document.querySelector(".control.xml").innerHTML
    , '', "The control has the correct layout update"
    @test.assertSelectorHasText ".variation-a.name", "Variation A", "The first variation is named correctly"
    @test.assertSelectorHasText ".variation-a.split_percentage", "50", "The first variation has the correct split percentage"
    @test.assertEvalEquals ->
        return document.querySelector(".variation-a.xml").innerHTML
    , '<reference name="right"><action method="unsetChildren"></action></reference>', "The first variation has the correct layout update"

    @echo "The first test has been created successfully", "GREEN_BAR"

casper.run ->
    @test.done()
