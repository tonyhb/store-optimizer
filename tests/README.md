Magento Optimizer Test Suite
============================

Because the extension focuses on changing the layout of web pages and
recording user interactions, simple unit testing doesn't cover the core
functionality of the extension. Magento Optimizer uses acceptance 
testing to ensure that the extension works properly. 

The tests are built using CasperJS and PhantomJS, imitating a real browser that
creates tests, views each variation and runs through conversions to ensure the
extension works properly across different Magento installations.


## Assumptions

The unit tests assume that you have CasperJS and PhantomJS installed. You also
need a web server (by deault hosted on 127.0.0.1:8888) that has the following
folder structure:

+ Extension/ - The Magento Optimizer extension
+ 1_4_2_0/   - A Magento 1.4.2.0 installation
+ 1_5_1_0/   - A Magento 1.5.1.0 installation
+ ...etc     - Other magento installations you need to test against

For Test Suite 4 (Theme Packages) you _must_ have a theme package called "other"
installed.


#k# Running the tests

In order to run the tests, navigate to the tests folder and type:
    run.sh -r
* -v: You may additionally provide versions to run against:
        run.sh -r -v 1_4_2_0_1_5_1_0
* -d: You may also enable debugging by using the -d flag.
* -s: You may provide numbers for the suites to run:
        run.sh -r -s 1,2
      This will run test suites 1 and 2
