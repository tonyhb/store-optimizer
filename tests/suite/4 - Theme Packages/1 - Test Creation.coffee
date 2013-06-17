# This test ensures that:
#  - The "Product Page" hooks only work on the product page
#  - The "One-page checkout" conversion target works correctly
#  - Testing only new visitors works correctly

version  = casper.cli.get("v") || "1_4_2_0"
url      = casper.cli.get("url") || "http://127.0.0.1:8888/"
magento  = url + version + "/"
username = casper.cli.get("u") || "admin"
password = casper.cli.get("p") || "password1"
db_test  = url + "Extension/tests/database/View.php?version=" + version + "&name=Theme Packages"
cookies  = url + "Extension/tests/database/Delete-cookies.php?version=" + version
moment   = require('moment');
casper.echo "Testing: 4 - Theme Packages - Test Creation", "GREEN_BAR"


# Pre. we have to truncate the test tables.
casper.start url + "Extension/tests/database/Truncate.php?version=" + version
casper.thenOpen cookies

# 1. Load the admin panel and navigate to the A/B test overview page
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

# 2. Create a test
casper.then ->
    @test.assertTitle "New A/B Test / Magento Admin", "We're on the 'New A/B test' page"
    @fill("form#abtest_form", {
        "test[name]"                   : "Theme Packages",
        "test[start_date]"             : moment().format("MMM D, YYYY")
        "target_observer_select"       : "*",
        "test[observer_target]"        : "*",
        "test[observer_conversion]"    : "checkout_onepage_controller_success_action",
        "cohorts"                      : "2",
        "test[only_test_new_visitors]" : "0",
        "cohort[A][package]"           : "other",
    }, false);
    @clickLabel("(split evenly)", "a")
    @click ".content-header .save"

casper.wait 12000

# 3. We should have then saved our test and be on our view test page
casper.then ->
    @test.assertUrlMatch(/abtest\/view/, "We're on a view test page after hitting save")
    @test.assertSelectorHasText(".head-adminhtml", "Test: Theme Packages", "The visible test heading matches that of the saved test")

casper.run ->
    @test.done()
