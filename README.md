Couchbase
=========

Couchbase Abstraction for HootSuite

- There is a config file with the following line to config a key for the singleton instance below:

        //init Couchbase
        In_Couchbase_Db::setKey('DEFAULT', array("conn"=>IN_COUCHBASE_HOSTS, "options"=>array(),  "username"=>"", "password"=>"", "bucket"=>"default"));


- Db.php is the base class.
This is a singleton that either starts a new connection, or grabs an existing connection if one has already been created. We currently only use one bucket 'default', so not specifying a key just defaults to our 'DEFAULT'. This seems to be working well, I don't believe the issue lies here.

- Bucket.php is an abstraction class.
This will grab a singleton connection through the In_Couchbase_Db class, it allows you to set a design document and view from there.

- Factory.php is a factory for pumping out pre-configured Couchbase buckets.
In our case, only the autocomplete ('default') bucket exists, so this is fairly straight forward.
