<?php

/*
 * Class for dealing with Couchbase Buckets
 * You should use the factory class
 * @codyhalovich
 */

use Hs\Common\Logger;
use Hs\Common\Statsd;

class In_Couchbase_Bucket extends In_Couchbase_Db {

    protected $bucket; // Bucket selected
    protected $designDoc; // Design doc to read
    protected $dataView; // View to query
    protected $instanceKey; // Current cluster / instance

    // Map of buckets to clusters/instances 
    protected $bucketKeys   = array('default' => 'DEFAULT');

    public function __construct($bucket = 'default') {
        $this->bucket       = $bucket;
        $this->instanceKey  = $this->bucketKeys[$bucket];
        $this->cb           = parent::getInstance($this->instanceKey); 
    }

    /* Setters */
    public function setDataView($view)  { $this->dataView = $view; }
    public function setDesignDoc($doc)  { $this->designDoc = $doc; }
    
    /* Getters */
    public function getDataView()       { return $this->dataView; }
    public function getDesignDoc()      { return $this->designDoc; }
    public function getBucket()         { return $this->bucket; }
    public function getInstanceKey()    { return $this->instanceKey; }

    /*
     * @param $options Array
     * @param $try Integer
     * returns $results Array
     *
     * This method will query a view. If there is no connection to Couchbase
     * it will attempt to kill, and rebuild the connection up to two times
     * as needed and retry the query each time.
     */
    public function find(Array $options, $try = 0) {

        // Make sure designDoc and dataView are set
        if(empty($this->designDoc) && empty($this->dataView)) {
            throw new Exception('Missing Design Doc and/or View name for Couchbase', 400);
        }

        try {
            $results = $this->cb->view($this->designDoc, $this->dataView, $options);
        } catch(CouchbaseLibcouchbaseException $e) {
            // Connection not active, try to rebuild connection and query again

            // Log stats on exception
            Statsd::increment("web.Couchbase.Exception.LibCouchbaseException.{$this->instanceKey}.{$try}");
            Logger::error("LibCouchbaseException on {$this->instanceKey}: {$e->getMessage()}");

            // Try to reconnect and query up to twice
            $try++;
            if($try <= 2) {
                $this->rebuildConnection();
                return $this->find($options, $try);
            } else {
                // Fail if we've already tried twice
                throw new Exception('Could not connect to Couchbase', 500);
            }

        } catch(CouchbaseException $e) { 
            // Catch general exception, try to determine cause

            // Log stats on exception
            Statsd::increment("web.Couchbase.Exception.CouchbaseException.{$this->instanceKey}.{$try}");
            Logger::error("CouchbaseException on {$this->instanceKey}: {$e->getMessage()}");

            // Throw exception if design document or view not found
            if($this->getExceptionCode($e->getMessage()) == 404) {
                throw new Exception('Design Document, or View not found in Couchbase', 404);
            } else {
                throw new Exception($e->getMessage(), 500);
            }

        }

        // Success, return results
        Statsd::increment("web.Couchbase.{$this->instanceKey}.success.{$try}");
        return $results;
    }

    /*
     * @param $key String
     * @param $doc Array
     * @param $try Integer
     * returns $results Array
     *
     * This method will add/update a document. If there is no connection to Couchbase
     * it will attempt to kill, and rebuild the connection up to two times
     * as needed and retry the query each time.
     */
    public function set($key, $doc, $try = 0) {

        // Make sure designDoc and dataView are set
        if(empty($this->designDoc) && empty($this->dataView)) {
            throw new Exception('Missing Design Doc and/or View name for Couchbase', 400);
        }

        try {
            $results = $this->cb->set($key, $doc);
        } catch(CouchbaseLibcouchbaseException $e) {
            // Connection not active, try to rebuild connection and query again

            // Log stats on exception
            Statsd::increment("web.Couchbase.Exception.LibCouchbaseException.{$this->instanceKey}");
            Logger::error("LibCouchbaseException on {$this->instanceKey}: {$e->getMessage()}");

            // Try to reconnect and query up to twice
            $try++;
            if($try <= 2) {
                $this->rebuildConnection();
                return $this->set($key, $doc, $try);
            } else {
                // Fail if we've already tried twice
                throw new Exception('Could not connect to Couchbase', 500);
            }

        } catch(CouchbaseException $e) { 
            // Catch general exception, try to determine cause

            // Log stats on exception
            Statsd::increment("web.Couchbase.Exception.CouchbaseException.{$this->instanceKey}");
            Logger::error("CouchbaseException on {$this->instanceKey}: {$e->getMessage()}");

            // Throw exception if design document or view not found
            if($this->getExceptionCode($e->getMessage()) == 404) {
                throw new Exception('Design Document, or View not found in Couchbase', 404);
            } else {
                throw new Exception('Error with Couchbase, try again.', 500);
            }

        } catch(Exception $e) {
            // Catch non-couchbase related exception

            Statsd::increment("web.Couchbase.Exception.Exception.{$this->instanceKey}");
            Logger:error("Exception from Couchbase: {$e->getMessage()}");
         
        }

        // Success, return results
        Statsd::increment("web.Couchbase.{$this->instanceKey}.success");
        return $results;
    }

    public function delete($key, $try = 0) {

        // Make sure designDoc and dataView are set
        if(empty($this->designDoc) && empty($this->dataView)) {
            throw new Exception('Missing Design Doc and/or View name for Couchbase', 400);
        }

        try {
            $results = $this->cb->delete($key);
        } catch(CouchbaseLibcouchbaseException $e) {
            // Connection not active, try to rebuild connection and query again

            // Log stats on exception
            Statsd::increment("web.Couchbase.Exception.LibCouchbaseException.{$this->instanceKey}");
            Logger::error("LibCouchbaseException on {$this->instanceKey}: {$e->getMessage()}");

            // Try to reconnect and query up to twice
            $try++;
            if($try <= 2) {
                $this->rebuildConnection();
                return $this->delete($key, $try);
            } else {
                // Fail if we've already tried twice
                throw new Exception('Could not connect to Couchbase', 500);
            }

        } catch(CouchbaseException $e) { 
            // Catch general exception, try to determine cause

            // Log stats on exception
            Statsd::increment("web.Couchbase.Exception.CouchbaseException.{$this->instanceKey}");
            Logger::error("CouchbaseException on {$this->instanceKey}: {$e->getMessage()}");

            // Throw exception if design document or view not found
            if($this->getExceptionCode($e->getMessage()) == 404) {
                throw new Exception('Design Document, or View not found in Couchbase', 404);
            } else {
                throw new Exception('Error with Couchbase, try again.', 500);
            }

        }

        // Success, return results
        Statsd::increment("web.Couchbase.{$this->instanceKey}.success");
        return $results;
    }

    /*
     * Rebuild Connection
     */
    public function rebuildConnection() {

        // Statsd the reconnect
        Statsd::increment("web.Couchbase.{$this->instanceKey}.Reconnect");

        // Kill stored connection 
        parent::killInstance($this->instanceKey);

        // Re-connect
        $this->cb = parent::getInstance($this->instanceKey);
    }

    /*
     * param $exception String
     * returns Int
     */
    public function getExceptionCode($exception) {
        $patterns = array('/\[/', '/\]/');
        foreach($patterns as $pattern) {
            $exception = preg_replace($pattern, '', $exception);
        }

        $pieces = explode(',', $exception);
        return $pieces[0];
    }

    /*
     * param $results Array
     * returns $data Array
     */
    public function handleResponse($results) {
        $data = array();

        foreach($results['rows'] as $row) {
            $data[] = $row['value'];
        }

        return $data;
    }
}

