# EVE Online Reaction Toolkit
A PHP-based web app for performing economic analysis on reaction scenarios based on live EVE Online market data.

See a live demo at https://rtk.bufferoverflow.xyz.

This app requires a minimum of PHP 5.4.  The development environment is NetBeans 8.0.2.

PHP Libraries used:
* PDO
* MySQL
* XML
* Memcached (NOT memcache)

The testing and runtime environment is PHP 5.4.16 on nginx 1.9.9 with a MariaDB 5.5 backend.  The optional cache engine is built around Memcached and requires libmemcached and php-pecl-memcached.

Those wishing to set up a new instance of RTK can find the database schema in include/config/schema.sql.  This contains the schema and certain static content such as reaction data, item data, etc.  Price data is dynamic and voluminous, and as such can't reasonably be committed to Github, so instead I've scripted an automated dump of the price tables to https://rtk.bufferoverflow.xyz/pricedata.sql.bz2 that runs nightly.  Simply create a new database using the included schema file and then download, extract, and import the pricedata.sql file for a price data jumpstart.

Automated tasks such as price updates and daily historical processing must be done via an OS scheduled task.
* scripts/update_prices.php - Every 5 minutes
* scripts/process_historical_prices.php - Daily

UNIX/Linux cron syntax:
```
*/5 * * * * php <path_to_project>/scripts/update_prices.php > /dev/null 2>&1
1 0 * * * php <path_to_project>/scripts/process_historical_prices.php > /dev/null 2>&1
```
