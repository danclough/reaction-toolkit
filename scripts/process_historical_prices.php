<?php
chdir(__DIR__);
while (! file_exists('include/config/config.php')) {
	chdir('..');
}
include_once('include/config/config.php');
$dbMgr = new DatabaseManager(false);
$twoDaysAgo = $dbMgr->getLastTimestamp(time()-172800,86400);
$yesterday = $dbMgr->getLastTimestamp(time()-86400,86400);
$dbMgr->processDailyHistoricalPrices($yesterday);
$dbMgr->deletePriceData($twoDaysAgo);
