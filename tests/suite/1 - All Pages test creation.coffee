# The following just sets up some environment variables used in each test.
#
# It would be cool if we could move these to a casper pre-script, but it doesn't
# seem like we can create global variables or assign properties to the casper
# object in presecripts. 
version = casper.cli.get("v") || "1_4_2_0"
url = casper.cli.get("url") || "http://127.0.0.1:8888/"
url = url + version + "/"

# Get the usernames and passwords
username = casper.cli.get("u") || "admin"
password = casper.cli.get("p") || "password1"

##
# Stage 1: Load the admin panel
#
casper.start url + "admin", ->
    # We may already be logged in
    if @getTitle() == "Dashboard / Magento Admin"
        @test.comment "We're already logged in, skipping the login page."
        return
    @test.assertTitle "Log into Magento Admin Page", "We're at the login page"
    @fill("form#loginForm", { "login[username]": username, "login[password]": password }, true)


#
# Navigate to the A/B test overview page
#
casper.then ->
    @test.assertTitle "Dashboard / Magento Admin", "We logged in to the dashboard successfully"
    # Find the A/B test URL
    link = @evaluate ->
        anchors = document.getElementById("nav").getElementsByTagName("A")
        for link in anchors
            return link if link.href.indexOf("abtest") > 0
    @open(link.href)


#
# Navigate to the A/B test creation page
#
casper.then ->
    @test.assertTitle "Manage A/B Tests / Magento Admin", "We're on the 'Manage A/B tests' page"
    @clickLabel("New A/B Test")


# 
# Create a test
#
casper.then ->
    @test.assertTitle "New A/B Test / Magento Admin", "We're on the 'New A/B test' page"

    # Ensure traffic is split evenly.
    @clickLabel("(split evenly)", "a")
    @fill("form#abtest_form", {
        "test[name]": "All Pages test",
        "target_observer_select": "*",
        "test[observer_target]": "*",
        "test[observer_conversion]": "checkout_cart_add_product_complete",
        "cohorts": "j",
        "test[only_test_new_visitors]": "0",
        "cohort[Control][name]": "Control",
        "cohort[Control][layout_update]": '<reference name="after_body_start"><block name="ab.test.block" type="core/text"><action method="setText"><text> &lt;h1 id="all-pages-acceptance-test"&gt;All Pages test: Control&lt;&#47;h1&gt;</text></action></block></reference>'
        "cohort[A][layout_update]": '<reference name="after_body_start"><block name="ab.test.block" type="core/text"><action method="setText"><text> &lt;h1 id="all-pages-acceptance-test"&gt;All Pages test: Variation A&lt;&#47;h1&gt;</text></action></block></reference>'
        "cohort[B][layout_update]": '<reference name="after_body_start"><block name="ab.test.block" type="core/text"><action method="setText"><text> &lt;h1 id="all-pages-acceptance-test"&gt;All Pages test: Variation B&lt;&#47;h1&gt;</text></action></block></reference>'
    }, false);

    # Hit the preview for Variation Aand see if previewing works
    @click("#abtest_form_tabs_cohort_2_content button")


#
# Wait for the preview to pop up
#
casper.waitForPopup url, ->
    @test.assertEquals(@.popups.length, 1, "Previewing a variation opened the homepage in a popup")


#
# Ensure the preview looks as expected
#
casper.withPopup url, ->
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
    @capture("FORM.png")
    @test.assertEvalEquals( ->
        return abTestForm.validate()
    , true)
    @click ".content-header .save"

casper.wait 10000

#
# We should have then saved our test and be on our view test page
#
casper.then ->
    @test.assertUrlMatch(/abtest\/view/, "We're on a view test page after hitting save")
    @test.assertSelectorHasText(".head-adminhtml", "Test: All Pages test", "The visible test heading matches that of the saved test")
    @test.done()

casper.run ->
    @test.done()
