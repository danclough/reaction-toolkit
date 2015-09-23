# EVE Online Reaction Toolkit
A PHP-based web app for performing economic analysis on reaction scenarios based on live EVE Online market data.

See a live demo at https://rtk.bufferoverflow.xyz.

This app requires a minimum of PHP 5.4.  The development environment is NetBeans 8.0.2.

The testing and runtime environment is PHP 5.4.16 on Apache 2.4.6 with a MariaDB 5.5 backend.  The optional cache engine is built around Memcached and requires libmemcached and php-pecl-memcached.

Those wishing to set up a new instance of RTK can find the database schema in include/config/schema.sql.  This contains the schema and certain static content such as reaction data, item data, etc.  Price data is dynamic and voluminous, and as such can't reasonably be committed to Github, so instead I've scripted an automated dump of the price tables to https://rtk.bufferoverflow.xyz/pricedata.sql.bz2 that runs nightly.  Simply create a new database using the included schema file and then download, extract, and import the pricedata.sql file for a price data jumpstart.
