# This test ensures that:
#  - The "Product Page" hooks only work on the product page
#  - The "One-page checkout" conversion target works correctly
#  - Testing only new visitors works correctly

version  = casper.cli.get("v") || "1_4_2_0"
url      = casper.cli.get("url") || "http://127.0.0.1:8888/"
magento  = url + version + "/"
db_test  = url + "Extension/tests/database/View.php?version=" + version + "&name=Product Page and Checkout"
cookies  = url + "Extension/tests/database/Delete-cookies.php?version=" + version

casper.echo "Testing: 3 - Product Page and Checkout - Conversion Checkout", "GREEN_BAR"
casper.start cookies

casper.thenOpen magento, ->
    @test.assertTitle "Home page", "We're on the home page"
    @clickLabel "Nine West Women's Lucero Pump"
casper.then ->
    @test.assertTitle "Nine West Women's Lucero Pump", "We're on the first product page"
    @clickLabel "Cell Phones"
casper.then ->
    @test.assertTitle "Cell Phones - Electronics", "We're on the Cell Phones page"
    @clickLabel "Nokia 2610 Phone"
casper.then ->
    @test.assertTitle "Nokia 2610", "We're on the Nokia product page"
    @clickLabel "Add to Cart"
casper.then ->
    @test.assertTitle "Shopping Cart", "We're on the shopping cart page"
    @clickLabel "Proceed to Checkout"

# 2. Fill out the one page checkout
casper.then ->
    @test.assertTitle "Checkout", "We're on the checkout page"
    @clickLabel "Checkout as Guest"
    @click "#checkout-step-login .col-1 button"
casper.then ->
    @fill("form#co-billing-form", {
        "billing[firstname]" : "Test",
        "billing[lastname]"  : "User",
        "billing[email]"     : "test@example.com",
        "billing[street][]"  : "Apt 325, 1 First Ave, Manhattan",
        "billing[city]"      : "New York City",
        "billing[region_id]" : "43",
        "billing[postcode]"  : "11211",
        "billing[telephone]" : "5551234567",
    }, false);
    @clickLabel "Ship to this address"
    @click "#billing-buttons-container button"
    @test.comment "Billing and shipping addresses filled"
# Wait for the billing address to be saved - now on the shipping method page
casper.waitFor(
      ->
        @evaluate( -> return document.querySelector("#checkout-progress-wrapper a[href='#payment']") != null)
    , ->
        @test.comment "Shipping method selected"
        @click "#shipping-method-buttons-container button"
    , ->
        @echo(@evaluate ->
          return document.querySelector("#checkout-progress-wrapper a[href='#payment']") != null
        )
        @test.fail "Couldn't save the billing address"
    , 15000)
# We're now on the payment page
casper.waitFor(
      ->
        @evaluate( -> return document.querySelector("#checkout-progress-wrapper a[href='#shipping_method']") != null)
    , ->
        @test.comment "Payment method selected"
        @clickLabel "Check / Money order "
        @click "#payment-buttons-container button"
    , ->
        @test.fail "Couldn't save the shipping method"
    , 15000)
casper.wait 15000
casper.then ->
    @clickLabel "Place Order"
casper.waitForSelector(".checkout-onepage-success", ->
    # @TODO: Add assertion here to show a successful purchase
    @page.deleteCookie("cohort_data")
    @page.deleteCookie("frontend")
, ->
    casper.fail "Couldn't load the success page on time"
, 30000)

# 3. Ensure the conversion was logged correctly
casper.thenOpen db_test, ->
    # Find which of the variations had the hit
    variation = @evaluate ->
        if document.querySelector(".control.visitors").innerHTML != "0"
            return ".control"
        else
            return ".variation-a"

    @test.assertSelectorHasText "#visitors",                "1", "A new visitor was tracked"
    @test.assertSelectorHasText "#views",                   "2", "The new visitor was tracked only on the specified pages"
    @test.assertSelectorHasText variation + ".visitors",    "1", "The right number of visitors were logged"
    @test.assertSelectorHasText variation + ".views",       "2", "The right number of views were logged"
    @test.assertSelectorHasText variation + ".conversions", "1", "The right number of conversions were logged"
    @test.assertSelectorHasText variation + ".value", "167.5500", "The right conversion value was logged"

casper.run ->
    @test.done()
