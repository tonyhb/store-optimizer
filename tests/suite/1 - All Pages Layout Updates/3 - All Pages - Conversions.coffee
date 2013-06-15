# Standard boilerplate...
version = casper.cli.get("v") || "1_4_2_0"
url     = casper.cli.get("url") || "http://127.0.0.1:8888/"
magento = url + version + "/"
db_test = url + "Extension/tests/database/View.php?version=" + version + "&name=All pages test"
cookies = url + "Extension/tests/database/Delete-cookies.php?version=" + version

# Pre.

# 1.
casper.start cookies, ->
    @echo "Testing: 1 - All Pages - Conversions", "GREEN_BAR"

# 2. Now, we're going to add a conversion on the Contorl cohort.
# Visitor 4 / Views 11
casper.thenOpen magento + "acer-ferrari-3200-notebook-computer-pc.html?__t_1=1", ->
    @click "#options_2_4" # Add 2 year parts and labour guarantee
    @clickLabel "Add to Cart"

# 3. Ensure the item was added
casper.then ->
    @page.deleteCookie("cohort_data")
    @page.deleteCookie("frontend")
    @test.assertTextExists "Acer Ferrari 3200 Notebook Computer PC was added to your shopping cart.", "The Acer laptop was added to the cart"
    @test.assertTextExists "2 Years - Parts and Labor", "Extended warranty was added to the cart"

# 4. Ensure the DB was updated correctly
casper.thenOpen db_test, ->
    @test.assertSelectorHasText ".control.value", "2049.99", "Control has the correct conversion value"

casper.run ->
    @test.done()
