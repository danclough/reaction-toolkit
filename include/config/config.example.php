<?php

/*
 * Database configuration
 * Currently the only supported database engine is MySQL/MariaDB.
 */
define("DATABASE_HOST", "localhost"); //The hostname or IP address of the MySQL database server.
define("DATABASE_NAME", "<changeme>"); //The database to use for RTK.
define("DATABASE_USERNAME", "<changeme>"); //A username with all privileges on the specified database.
define("DATABASE_PASSWORD", "<changeme>"); //The password for the specified username.
/*
 * If using RTK in a shared hosting environment or anywhere else that limits database creation, you can configure a
 * prefix to be prepended to all table names.
 */
define("DATABASE_PREFIX", "rtk");

/*
 * Memcached configuration
 * Set USE_MEMCACHED to false to disable caching functionality.
 * Hint: Don't do this.
 */
define("USE_MEMCACHED", true);
define("MEMCACHED_HOST", "localhost"); //The hostname or IP address of the Memcached server.
define("MEMCACHED_PORT", 11211); //The port on which Memcached is listening.
/*
 * If your Memcached instance is shared among multiple applications, you can configure a unique ID that RTK will prepend
 * to all keys to avoid key collisions.
 *
 * Keys are also hashed with sha1, but this is just another level of safety in case you're running multiple copies of
 * RTK.
 */
define("MEMCACHED_PREFIX", "RTK");

/*
 * General toolkit configuration
 */
define("DEFAULT_SYSTEMID", 30000142); // The default systemID to use for new sessions or where systemID is unspecified.

/*
 * ===========================================================================
 * DON'T CHANGE ANYTHING BELOW THIS LINE!
 * ===========================================================================
 */

/*
 * PSR-0 Autoloader function
 */

function __autoload($class_name) {
    require_once('include/class/' . $class_name . '.php');
}

require_once('include/functions.php');
ini_set('display_errors', 'on'); //On = Debugging output in the browser.  Off = Nothing to see here, move along.
date_default_timezone_set('UTC'); //All EVE Online systems are in UTC.  You really don't want to change this.
