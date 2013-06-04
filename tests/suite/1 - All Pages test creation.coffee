# The following just sets up some environment variables used in each test.
#
# It would be cool if we could move these to a casper pre-script, but it doesn't
# seem like we can create global variables or assign properties to the casper
# object in presecripts. 
version = casper.cli.get("v") || "1_4_2_0"
url = "http://127.0.0.1/" + version + "/"

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

casper.run ->
    @test.renderResults true, 0, this.cli.get('save') || false
    @test.done()

