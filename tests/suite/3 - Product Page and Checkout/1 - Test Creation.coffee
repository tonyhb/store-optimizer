# This test ensures that:
#  - The "Product Page" hooks only work on the product page
#  - The "One-page checkout" conversion target works correctly
#  - Testing only new visitors works correctly

version  = casper.cli.get("v") || "1_4_2_0"
url      = casper.cli.get("url") || "http://127.0.0.1:8888/"
magento  = url + version + "/"
username = casper.cli.get("u") || "admin"
password = casper.cli.get("p") || "password1"
db_test  = url + "Extension/tests/database/View.php?version=" + version + "&name=Product Page and Checkout"
moment   = require('moment');
casper.echo "Testing: 3 - Product Page and Checkout - Test Creation", "GREEN_BAR"


# Pre. we have to truncate the test tables.
casper.start url + "Extension/tests/database/Truncate.php?version=" + version

# 1. View the website so we're a returning visitor. This enables us to test the 
# "only test new visitors" setting
casper.thenOpen magento

# 2. Load the admin panel and navigate to the A/B test overview page
casper.thenOpen magento + "admin", ->
    return @test.comment "We're already logged in, skipping the login page." if @getTitle() == "Dashboard / Magento Admin"
    @test.assertTitle "Log into Magento Admin Page", "We're at the login page"
    @fill "form#loginForm", { "login[username]": username, "login[password]": password }, true
casper.then ->
    @test.assertTitle "Dashboard / Magento Admin", "We logged in to the dashboard successfully"
    @clickLabel("Manage A/B Tests")
casper.then ->
    @test.assertTitle "Manage A/B Tests / Magento Admin", "We're on the 'Manage A/B tests' page"
    @clickLabel("New A/B Test")

# 3. Create a test
casper.then ->
    @test.assertTitle "New A/B Test / Magento Admin", "We're on the 'New A/B test' page"
    @fill("form#abtest_form", {
        "test[name]": "Product Page and Checkout",
        "test[start_date]": moment().format("MMM D, YYYY")
        "target_observer_select": "catalog_product_view",
        "test[observer_target]": "catalog_product_view",
        "test[observer_conversion]": "checkout_onepage_controller_success_action",
        "cohorts": "2",
        "test[only_test_new_visitors]": "1",
        "cohort[Control][name]": "Control",
        "cohort[Control][layout_update]": ''
        "cohort[A][layout_update]": '<reference name="after_body_start"><block name="ab.test.block" type="core/text"><action method="setText"><text> &lt;h1 id="all-pages-acceptance-test"&gt;Variation A&lt;/h1&gt;</text></action></block></reference>'
    }, false);
    @clickLabel("(split evenly)", "a")
    @click ".content-header .save"

casper.wait 12000

# 4. We should have then saved our test and be on our view test page
casper.then ->
    @test.assertUrlMatch(/abtest\/view/, "We're on a view test page after hitting save")
    @test.assertSelectorHasText(".head-adminhtml", "Test: Product Page and Checkout", "The visible test heading matches that of the saved test")

# 5. Ensure the test is saved correctly
casper.thenOpen db_test, ->
    @test.assertSelectorHasText "#test-name", "Product Page and Checkout", "The test name is saved correctly"
    @test.assertSelectorHasText "#start-date", moment().format("YYYY-MM-DD"), "The test start date is saved correctly"
    @test.assertSelectorHasText "#observer-target", "catalog_product_view", "The observer target is saved correctly"
    @test.assertSelectorHasText "#observer-conversion", "checkout_onepage_controller_success_action", "The observer conversion is saved correctly"
    @test.assertSelectorHasText "#test-new-visitors", "1", "The test includes only new visitors"
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
    , '<reference name="after_body_start"><block name="ab.test.block" type="core/text"><action method="setText"><text> &lt;h1 id="all-pages-acceptance-test"&gt;Variation A&lt;/h1&gt;</text></action></block></reference>', "The first variation has the correct layout update"
    @echo "The test has been created successfully", "INFO"

# 6. Test a returning visitor - this shouldn't track any statistics 
casper.thenOpen magento, ->
    @clickLabel "Nine West Women's Lucero Pump"
casper.then ->
    @click ".products-grid a"
casper.thenOpen db_test, ->
    @test.assertSelectorHasText "#visitors", "0",        "Returning visitors aren't tracked when the test includes only new visitors"
    @test.assertSelectorHasText "#views", "0",           "Returning views aren't tracked when the test includes only new visitors"
    @test.assertSelectorHasText ".control.visitors",     "0", "'Control' has the right number of visitors"
    @test.assertSelectorHasText ".control.views",        "0", "'Control' has the right number of views"
    @test.assertSelectorHasText ".variation-a.visitors", "0", "'Variation A' has the right number of views"
    @test.assertSelectorHasText ".variation-a.views",    "0", "'Variation A' has the right number of visitors"

casper.run ->
    @test.done()
