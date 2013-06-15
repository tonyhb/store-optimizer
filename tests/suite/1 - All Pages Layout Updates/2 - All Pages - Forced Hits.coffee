# Standard boilerplate...
version = casper.cli.get("v") || "1_4_2_0"
url     = casper.cli.get("url") || "http://127.0.0.1:8888/"
magento = url + version + "/"
db_test = url + "Extension/tests/database/View.php?version=" + version + "&name=All pages test"
cookies = url + "Extension/tests/database/Delete-cookies.php?version=" + version


casper.echo "Testing: 1 - All Pages - Forced Hits", "GREEN_BAR"

casper.start cookies

# First we're going to go to the homepage and set us up as a control.
# Note that the test ID and variations have been hard coded using forcing (which
# is coded into the assignVariations() method of the visitor helper). Forcing is
# not recommended for production use.
#
# Visitor 1 / Views 3
casper.thenOpen magento + "?__t_1=1", ->
    @test.assertSelectorHasText "#all-pages-acceptance-test", "All Pages test: Control", "The 'Control' cohort shows the correct layout update"
    @clickLabel "Laptops" # Navigate to a category
# Ensure that the category also has this layout update
casper.then ->
    @test.assertSelectorHasText "#all-pages-acceptance-test", "All Pages test: Control", "The 'Control' cohort shows the correct layout update"
    @clickLabel "Checkout"
# And ensure it's on the checkout
casper.then ->
    @page.deleteCookie("cohort_data")
    @page.deleteCookie("frontend")
    @test.assertSelectorHasText "#all-pages-acceptance-test", "All Pages test: Control", "The 'Control' cohort shows the correct layout update"

casper.thenOpen cookies

##
# Now we're going to test Variation A
#
# Visitor 2 / Views 6
casper.thenOpen magento + "?__t_1=2", ->
    @test.assertSelectorHasText "#all-pages-acceptance-test", "All Pages test: Variation A", "The 'Variation A' cohort shows the correct layout update"
    @clickLabel "Laptops" # Navigate to a category
# Ensure that the category also has this layout update
casper.then ->
    @test.assertSelectorHasText "#all-pages-acceptance-test", "All Pages test: Variation A", "The 'Variation A' cohort shows the correct layout update"
    @clickLabel "Checkout"
# And ensure it's on the checkout
casper.then ->
    @page.deleteCookie("cohort_data")
    @page.deleteCookie("frontend")
    @test.assertSelectorHasText "#all-pages-acceptance-test", "All Pages test: Variation A", "The 'Variation A' cohort shows the correct layout update"

casper.thenOpen cookies

##
# Now we're going to test Variation B
#
# Visitor 3 / Views 9
casper.thenOpen magento + "?__t_1=3", ->
    @test.assertSelectorHasText "#all-pages-acceptance-test", "All Pages test: Variation B", "The 'Variation B' cohort shows the correct layout update"
    @clickLabel "Laptops" # Navigate to a category
# Ensure that the category also has this layout update
casper.then ->
    @test.assertSelectorHasText "#all-pages-acceptance-test", "All Pages test: Variation B", "The 'Variation B' cohort shows the correct layout update"
    @clickLabel "Checkout"
# And ensure it's on the checkout
casper.then ->
    @page.deleteCookie("cohort_data")
    @page.deleteCookie("frontend")
    @test.assertSelectorHasText "#all-pages-acceptance-test", "All Pages test: Variation B", "The 'Variation B' cohort shows the correct layout update"


##
# Now we need to check that the database has been updated accurately
#
casper.thenOpen db_test, ->
    @test.assertSelectorHasText "#visitors", "3", "The correct number of visitors have been logged"
    @test.assertSelectorHasText "#views", "9", "The correct number of page views have been logged"
    @test.assertSelectorHasText ".control.visitors", "1", "Control has the correct number of visitors"
    @test.assertSelectorHasText ".control.views", "3", "Control has the correct number of views"
    @test.assertSelectorHasText ".variation-a.visitors", "1", "Variation A has the correct number of visitors"
    @test.assertSelectorHasText ".variation-a.views", "3", "Variation A has the correct number of views"
    @test.assertSelectorHasText ".variation-b.visitors", "1", "Variation B has the correct number of visitors"
    @test.assertSelectorHasText ".variation-b.views", "3", "Variation B has the correct number of views"

casper.run ->
    @test.done()
