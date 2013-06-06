# Standard boilerplate...
version = casper.cli.get("v") || "1_4_2_0"
url = casper.cli.get("url") || "http://127.0.0.1:8888/"

# Helper variables
magento = url + version + "/"
db_test = url + "Extension/tests/database/All-pages-test.php?version=" + version


casper.echo "Testing first test (all pages) layout updates", "GREEN_BAR"


# First we're going to go to the homepage and set us up as a control.
# Note that the test ID and variations have been hard coded using forcing (which
# is coded into the assignVariations() method of the visitor helper). Forcing is
# not recommended for production use.
casper.start magento + "?__t_1=1", ->
    @test.assertSelectorHasText "#all-pages-acceptance-test", "All Pages test: Control", "The 'Control' cohort shows the correct layout update"
    @clickLabel "Laptops" # Navigate to a category
# Ensure that the category also has this layout update
casper.then ->
    @test.assertSelectorHasText "#all-pages-acceptance-test", "All Pages test: Control", "The 'Control' cohort shows the correct layout update"
    @clickLabel "Checkout"
# And ensure it's on the checkout
casper.then ->
    @test.assertSelectorHasText "#all-pages-acceptance-test", "All Pages test: Control", "The 'Control' cohort shows the correct layout update"

casper.thenOpen url + "Extension/tests/delete-cookies.php"

##
# Now we're going to test Variation A
casper.thenOpen magento + "?__t_1=2", ->
    @test.assertSelectorHasText "#all-pages-acceptance-test", "All Pages test: Variation A", "The 'Variation A' cohort shows the correct layout update"
    @clickLabel "Laptops" # Navigate to a category
# Ensure that the category also has this layout update
casper.then ->
    @test.assertSelectorHasText "#all-pages-acceptance-test", "All Pages test: Variation A", "The 'Variation A' cohort shows the correct layout update"
    @clickLabel "Checkout"
# And ensure it's on the checkout
casper.then ->
    @test.assertSelectorHasText "#all-pages-acceptance-test", "All Pages test: Variation A", "The 'Variation A' cohort shows the correct layout update"

casper.thenOpen url + "Extension/tests/delete-cookies.php"

##
# Now we're going to test Variation B
casper.thenOpen magento + "?__t_1=3", ->
    @test.assertSelectorHasText "#all-pages-acceptance-test", "All Pages test: Variation B", "The 'Variation B' cohort shows the correct layout update"
    @clickLabel "Laptops" # Navigate to a category
# Ensure that the category also has this layout update
casper.then ->
    @test.assertSelectorHasText "#all-pages-acceptance-test", "All Pages test: Variation B", "The 'Variation B' cohort shows the correct layout update"
    @clickLabel "Checkout"
# And ensure it's on the checkout
casper.then ->
    @test.assertSelectorHasText "#all-pages-acceptance-test", "All Pages test: Variation B", "The 'Variation B' cohort shows the correct layout update"

casper.thenOpen url + "Extension/tests/delete-cookies.php"

#
# Now, we're going to add a conversion on the Contorl cohort.
#
casper.thenOpen magento + "acer-ferrari-3200-notebook-computer-pc.html?__t_1=1", ->
    @click "#options_2_4" # Add 2 year parts and labour guarantee
    @clickLabel "Add to Cart"

casper.then ->
    @test.assertTextExists "Acer Ferrari 3200 Notebook Computer PC was added to your shopping cart.", "The Acer laptop was added to the cart"
    @test.assertTextExists "2 Years - Parts and Labor", "Extended warranty was added to the cart"


##
# Now we need to check that the database has been updated accurately
#
casper.thenOpen db_test, ->
    @test.assertSelectorHasText "#visitors", "4", "The correct number of visitors have been logged"
    @test.assertSelectorHasText "#views", "11", "The correct number of page views have been logged"
    @test.assertSelectorHasText ".control.visitors", "2", "Control has the correct number of visitors"
    @test.assertSelectorHasText ".control.views", "5", "Control has the correct number of views"
    @test.assertSelectorHasText ".control.value", "2049.99", "Control has the correct conversion value"
    @test.assertSelectorHasText ".variation-a.visitors", "1", "Variation A has the correct number of visitors"
    @test.assertSelectorHasText ".variation-a.views", "3", "Variation A has the correct number of views"
    @test.assertSelectorHasText ".variation-b.visitors", "1", "Variation B has the correct number of visitors"
    @test.assertSelectorHasText ".variation-b.views", "3", "Variation B has the correct number of views"


casper.run ->
    @test.done()
