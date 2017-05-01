# EVE Online Reaction Toolkit
A PHP-based web app for performing economic analysis on reaction scenarios based on live EVE Online market data.

See a live demo at https://rtk.bufferoverflow.xyz.

This app requires a minimum of PHP 5.4.  It has been tested up to PHP 7.0.

PHP Libraries used:
* pdo
* pdo_mysql
* XML
* memcached [2.2 through 3.0] (NOT memcache)

The testing and runtime environment is PHP 7.0 on Apache 2.4 with a MariaDB backend.  The optional cache engine is built on Memcached and requires libmemcached and php-pecl-memcached.  It offers around a 400% performance increase and can be disabled in config.php at your own peril.

The initial database creation SQL is contained in include/config/schema.sql.  This contains the schema and reference data such as reaction blueprints, item types, etc.

Automated tasks such as price updates and daily historical processing must be done via an OS scheduled task.  The scripts can also be called remotely using cURL.
* scripts/update_prices.php - Every 5 minutes
* scripts/process_historical_prices.php - Daily

PHP cron syntax:
```
*/5 * * * * php <path_to_project>/scripts/update_prices.php > /dev/null 2>&1
1 0 * * * php <path_to_project>/scripts/process_historical_prices.php > /dev/null 2>&1
```
Using cURL:
```
*/5 * * * * curl http://<project_url>/scripts/update_prices.php > /dev/null 2>&1
1 0 * * * curl http://<project_url>/scripts/process_historical_prices.php > /dev/null 2>&1
```
