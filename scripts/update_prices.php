<?php
chdir(__DIR__);
while (! file_exists('include/config/config.php')) {
	chdir('..');
}
require_once('include/config/config.php');
$filePath = __DIR__;
$statusFile = "{$filePath}/../include/pages/price_update.txt";
$status = "";
$db = new Database();
$api = new MarketAPI();
$source = $api->updatePrices();
if ($source !== false):
	$status = "Prices updated successfully from {$source} as of ".$db->getLastTimestamp(time())." EVE time (UTC).";
else:
	$status = "Last price update failed.  Oops.";
endif;
file_put_contents($statusFile,$status);
