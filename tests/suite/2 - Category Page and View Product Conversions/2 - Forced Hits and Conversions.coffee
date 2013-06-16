version = casper.cli.get("v") || "1_4_2_0"
url     = casper.cli.get("url") || "http://127.0.0.1:8888/"
magento = url + version + "/"
db_test = url + "Extension/tests/database/View.php?version=" + version + "&name=Category Page and Viewed Product Conversions test"
cookies = url + "Extension/tests/database/Delete-cookies.php?version=" + version

casper.echo "Testing: 2 - Category Page and Viewed Product Conversions - Forced Hits and Conversions", "GREEN_BAR"

# 1. View the homepage as control
#    Visitors: 1 / Views: 1 (We should only register a view on categories)
casper.start cookies
casper.thenOpen magento + "?__t_2=4", ->
    @test.assertTitle "Home page", "We're on the homepage with Control"
    @clickLabel "Furniture"
casper.then ->
    @test.assertExists ".block-compare", "The comparison block exists for control"
    @page.deleteCookie("cohort_data")
    @page.deleteCookie("frontend")

# 2. Visit the site as variation A and convert (viewing a product is
#    a conversion)
#    Visitors: 2 / Views: 2 / Total Views: 3
casper.thenOpen cookies
casper.thenOpen magento + "?__t_2=5", ->
    @test.assertTitle "Home page", "We're on the homepage with Variation A"
    @clickLabel "Furniture"
casper.then ->
    @test.assertDoesntExist ".block-compare", "The comparison block doesn't exist for Variation A"
    @clickLabel "Chair"
casper.then ->
    # This should have logged a conversion
    @clickLabel "Apparel"
casper.then ->
    @clickLabel "The Only Children: Paisley T-Shirt"
casper.then ->
    # This product page shouldn't log a conversion - we're only tracking one
    # conversion per visitor
    @page.deleteCookie("cohort_data")
    @page.deleteCookie("frontend")

# 3. Ensure this test has the correct stats logged
casper.thenOpen cookies
casper.thenOpen db_test, ->
    @test.assertSelectorHasText "#visitors",                "2", "The right number of visitors has been logged"
    @test.assertSelectorHasText "#views",                   "3", "The right number of views has been logged"
    @test.assertSelectorHasText "#conversions",             "1", "The right number of conversions has been logged"
    @test.assertSelectorHasText ".control.visitors",        "1", "'Control' has the right number of visitors"
    @test.assertSelectorHasText ".control.views",           "1", "'Control' has the right number of views"
    @test.assertSelectorHasText ".variation-a.visitors",    "1", "'Variation A' has the right number of views"
    @test.assertSelectorHasText ".variation-a.views",       "2", "'Variation A' has the right number of visitors"
    @test.assertSelectorHasText ".variation-a.conversions", "1", "'Variation A' has the right number of conversions"

# 4. We were running the first test at the same time, so 
casper.thenOpen url + "Extension/tests/database/View.php?version=" + version + "&name=All pages test", ->
    @test.assertSelectorHasText "#visitors", "8",  "The correct number of visitors have been logged"
    @test.assertSelectorHasText "#views",    "24", "The correct number of page views have been logged"

casper.run ->
    @test.done()
