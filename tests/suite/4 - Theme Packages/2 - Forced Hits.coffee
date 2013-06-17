version = casper.cli.get("v") || "1_4_2_0"
url     = casper.cli.get("url") || "http://127.0.0.1:8888/"
magento = url + version + "/"
db_test = url + "Extension/tests/database/View.php?version=" + version + "&name=Category Page and Viewed Product Conversions test"
cookies = url + "Extension/tests/database/Delete-cookies.php?version=" + version

casper.echo "Testing: 4 - Theme Packages - Forced Hits", "GREEN_BAR"

# 1. View the homepage as control
#    Visitors: 1 / Views: 1 (We should only register a view on categories)
casper.start cookies
casper.thenOpen magento + "?__t_1=1", ->
    @test.assertTitle "Home page", "We're on the homepage with Control"
    @test.assertEvalEquals( ->
        return document.querySelector("link[rel='stylesheet']").getAttribute('href').indexOf("frontend/default/default") > 0
    , true, "The control uses the 'Default' package")
    @page.deleteCookie("cohort_data")
    @page.deleteCookie("frontend")
casper.thenOpen magento + "?__t_1=2", ->
    @test.assertTitle "Home page", "We're on the homepage with Variation A"
    @test.assertEvalEquals( ->
        return document.querySelector("link[rel='stylesheet']").getAttribute('href').indexOf("frontend/other/default") > 0
    , true, "Variation A uses the 'Other' package")
    @page.deleteCookie("cohort_data")
    @page.deleteCookie("frontend")

casper.run ->
    @test.done()
