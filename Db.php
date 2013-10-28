<?php

/**
 * A Base Singleton Couchbase Class
 * Author: Cody Halovich
 */

class In_Couchbase_Db
{
    public static $instances = array(); //STATIC collection of already created instances (actual database selected instance
    private static $connections = array(); //STATIC collection of already created connections
    private static $connectionKeys = array(); // Static collection of connection strings

    public static function setKey($name, $data)
    {
    	self::$connectionKeys[$name] = serialize($data);
    }

    public static function getKey($name)
    {
    	if (!isset(self::$connectionKeys[$name]))
    	{
    		return false;
    	}
    	else
    	{
    		return self::$connectionKeys[$name];
    	}
    }

    public static function getConnectionKeys()
    {
    	return self::$connectionKeys;
    }

	public static function getInstance($keyName = 'DEFAULT')
	{
		$connectionInfoStr = '';

		if (self::getKey($keyName) != false) // Use the connection string for this key
		{
			$connectionInfoStr = self::getKey($keyName);
		}
		else // No connection string could be found
		{
			throw new In_Exception("No Couchbase connection string could be found for key '{$keyName}', please check the constants file");
        }

		//if the instance for this key does not exist yet make one
        if(!array_key_exists($connectionInfoStr,self::$instances) || !isset(self::$instances[$connectionInfoStr]))
        {
            $connectionInfoArray = unserialize($connectionInfoStr);
            self::$connections[$connectionInfoStr] = new Couchbase($connectionInfoArray['conn'], $connectionInfoArray['username'], $connectionInfoArray['password'], $connectionInfoArray['bucket'], true);
            self::$connections[$connectionInfoStr]->setTimeout(1000000);
            self::$instances[$connectionInfoStr] = self::$connections[$connectionInfoStr];
        }
            
        return self::$instances[$connectionInfoStr];
	}
	
	public static function killInstance($keyName = 'DEFAULT')
	{
		$connectionInfoStr = '';

		if (self::getKey($keyName) != false) // Use the connection string for this key
		{
			$connectionInfoStr = self::getKey($keyName);
		}
		else // No connection string could be found
		{
			throw new In_Exception("No Couchbase connection string could be found for key '{$keyName}', please check the constants file");
		}

		if (isset(self::$instances[$connectionInfoStr]))
		{
			unset(self::$connections[$connectionInfoStr]);
			unset(self::$instances[$connectionInfoStr]);
		}
	}
	
	/**
	 * function to kill all db connection instances
	 */
	public static function killAllInstances()
	{
	    self::$instances = array();
	    self::$connections= array();
	}
	
}
