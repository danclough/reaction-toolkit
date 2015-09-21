<?php
chdir(__DIR__);
while (! file_exists('include/config/config.php')) {
	chdir('..');
}
include_once('include/config/config.php');
$db = new DatabaseManager(false);
$twoDaysAgo = $db->getLastTimestamp(time()-172800,86400);
$yesterday = $db->getLastTimestamp(time()-86400,86400);
$db->processDailyHistoricalPrices($yesterday);
$db->deletePriceData($twoDaysAgo);
