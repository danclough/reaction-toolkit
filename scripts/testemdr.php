<?php
require_once('include/functions.php');

$typeIDs = getAllTypeIDs();
$systemIDs = getAllSystemIDs();

$context = new ZMQContext();
$subscriber = $context->getSocket(ZMQ::SOCKET_SUB);

// Connect to the first publicly available relay.
$subscriber->connect("tcp://relay-us-central-1.eve-emdr.com:8050");
// Disable filtering.
$subscriber->setSockOpt(ZMQ::SOCKOPT_SUBSCRIBE, "");

while (true) {
	if (file_exists('breakmq')) {
		break;
	}
	// Receive raw market JSON strings.
	$market_json = gzuncompress($subscriber->recv());
	// Un-serialize the JSON data to a named array.
	$feed = json_decode($market_json,true);
	// Dump the market data to stdout. Or, you know, do more fun things here.
	$columnHeaders = $feed['columns'];
	$rowsets = $feed['rowsets'];
	foreach ($rowsets as $rowset) {
		$typeID = $rowset['typeID'];
		if (in_array($typeID,$typeIDs)) {
			$gendate = $rowset['generatedAt'];
			$regionID = $rowset['regionID'];
			$rows = $rowset['rows'];
			foreach ($rows as $row) {
				$data = array_combine($columnHeaders,$row);
				$systemID = $data['solarSystemID'];
				if (in_array($systemID,$systemIDs)) {
					$bid = (bool) $data['bid'];
					$price = $data['price'];
					$orderID = $data['orderID'];
					$issueDate = date("Y-m-d H:i:s",strtotime($data['issueDate']));
					if ($bid):
						$priceType = "buy";
					else:
						$priceType = "sell";
					endif;
					echo "{$orderID} - Item {$typeID} {$priceType} order at {$price}/unit, issued {$issueDate} in system {$systemID}.\n";
				}
			}
		}
	}
}
$subscriber->setSockOpt(ZMQ::SOCKOPT_UNSUBSCRIBE, "");
$subscriber->disconnect("tcp://relay-us-central-1.eve-emdr.com:8050");
