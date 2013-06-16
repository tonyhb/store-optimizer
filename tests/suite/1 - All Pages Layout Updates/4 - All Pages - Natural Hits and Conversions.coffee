# This test file ensures that visitors and views are logged correctly. We're not
# testing layout updates or any modification of the pages here.

version = casper.cli.get("v") || "1_4_2_0"
url     = casper.cli.get("url") || "http://127.0.0.1:8888/"
magento = url + version + "/"
db_test = url + "Extension/tests/database/View.php?version=" + version + "&name=All pages test"
cookies = url + "Extension/tests/database/Delete-cookies.php?version=" + version

# Pre.
casper.echo "Testing: 1 - All Pages - Natural Hits", "GREEN_BAR"

# 1.
casper.start cookies

# 2. Visit the website and browse some pages. These will be put into random cohorts.
# Visitor 5 / Views 15
casper.thenOpen magento, ->
    @clickLabel "Furniture"
casper.then ->
    @clickLabel "Ottoman"
casper.then ->
    @clickLabel "Add to Cart"
casper.then ->
    @page.deleteCookie("cohort_data")
    @page.deleteCookie("frontend")
    @test.assertTextExists "Ottoman was added to your shopping cart.", "The 'Ottoman' was added to the cart with a natural conversion"
    @test.assertEvalEquals( ->
        return $("shopping-cart-totals-table").down(".price").innerHTML
    , "£299.99", "The price for the ottoman is correct")

# 3. Delete the cookies and browse the site again.
# Visitor 6 / Views 17
casper.thenOpen cookies
casper.thenOpen magento, ->
    @clickLabel "About Us"
casper.then ->
    @page.deleteCookie("cohort_data")
    @page.deleteCookie("frontend")

# 4. Delete cookies and check the database
casper.thenOpen cookies
casper.thenOpen db_test, ->
    @test.assertSelectorHasText "#visitors", "6", "The correct number of visitors have been logged"
    @test.assertSelectorHasText "#views", "17", "The correct number of page views have been logged"

casper.run ->
    @test.done()
