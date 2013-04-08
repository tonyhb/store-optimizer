<?php

/**
 * Helps optimize A/B tests by taking a series of SQL statements and runs them 
 * as one query at the end of processing.
 *
 */
class THB_ABTest_Helper_Optimizer extends Mage_Core_Helper_Data
{

    /**
     * Stores an array of queries to run when the runQueries method is called.
     *
     * @var array
     */
    protected static $_queries = array();

    /**
     * Adds a query to be run with the runQueries method call
     *
     * @param string  SQL query
     * @return $this;
     */
    public function addQuery($query)
    {
        $query = (string) $query;
        self::$_queries[] = $query;

        return $this;
    }

    /**
     * Takes the array of queries ($_queries), combines them into one statement 
     * separated via semi-colons and executes it with one request.
     *
     * @return void
     */
    public function runQueries()
    {
        self::$_queries = array_filter(self::$_queries);

        # Don't need to run any queries
        if (empty(self::$_queries))
            return;

        $statement = '';
        foreach (self::$_queries as $query)
        {
            $statement .= trim($query) . '; ';
        }

        $db = Mage::getSingleton('core/resource')->getConnection('core/write');
        $db->query($statement);

        self::$_queries = array();
    }


}
