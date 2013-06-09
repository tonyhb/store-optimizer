run = casper.cli.get("run")
version = casper.cli.get("v") || "1_4_2_0"

unless run == "ok"
    casper.echo ""
    casper.echo "Magento Optimizer test suite", "GREEN_BAR"
    casper.echo "============================", "GREEN_BAR"
    casper.echo ""
    casper.echo "Note: DO NOT RUN ON A LIVE SITE. Doing so will erase all tests!", "WARNING"
    casper.echo ""
    casper.echo "To run the test suite, please add -r -v to the shell command ", "TRACE"
    casper.echo "(ie. ./run.sh -r -v {version,version}).", "TRACE"
    casper.echo "The tests will take at least 10 minutes to run.", "TRACE"
    casper.echo ""
    casper.echo "Hit ctrl-c to quit and re-run."
    return

casper.echo ""
casper.echo "Testing version " + version, "GREEN_BAR"
casper.echo "=======================", "GREEN_BAR"
casper.echo ""

casper.test.done()
