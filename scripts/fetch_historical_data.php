<?php
include_once "../include/functions.php";

$sql = "SELECT itemID from {$dbPrefix}_types";
$itemIDs = array();
$result = $reactDB->query($sql);
for($x = 1; $x <= $reactDB->affected_rows; $x++) {
	$row = $result->fetch_assoc();
	array_push($itemIDs,$row['itemID']);
}
$sql = "SELECT systemID, systemName from {$dbPrefix}_systems";
$systemIDs = array();
$result = $reactDB->query($sql);
for($x = 1; $x <= $reactDB->affected_rows; $x++) {
	$row = $result->fetch_assoc();
	$systemIDs[$row['systemID']]=$row['systemName'];
}
$priceTypes=array("maxBuy","minSell");
foreach($priceTypes as $priceType) {
	$priceData = array();
	if($priceType == "maxBuy") {
		$key = "max";
		$priceCode=1;
	}
	else {
		$key = "min";
		$priceCode=0;
	}
	foreach($itemIDs as $itemID) {
		foreach($systemIDs as $systemID => $systemName) {
			echo "Working on {$priceType} for ".getItemNameByItemID($itemID)." in {$systemName}...\n";
			$json_file = "../json/{$itemID}-{$systemID}-{$priceType}_history.json";
			if(!file_exists($json_file)) {
				$json_url = "http://api.eve-central.com/api/history/for/type/{$itemID}/system/{$systemName}/bid/{$priceCode}";
				shell_exec("wget --output-document='{$json_file}' {$json_url}");
				echo "Writing {$itemID}-{$systemID}-{$priceType}_history.json\n";
				$size = filesize($json_file);
				while($size == 0) {
					shell_exec("wget --output-document='{$json_file}' {$json_url}");				
					$size = filesize($json_file);
				}
			}
			$jsondata = file_get_contents($json_file);
			$obj = json_decode($jsondata,true);
			$records = $obj['values'];
			foreach($records as $record) {
				$at = str_replace('Z','GMT',$record['at']);
				$date = date("Y-m-d H:00:00",strtotime($at));
				array_push($priceData,"('{$systemID}','{$itemID}','{$date}','{$record[$key]}')");
			}
			echo "Finished working on {$priceType} for ".getItemNameByItemID($itemID)." in {$systemName}!\n";
		}
	}
	echo "before";
	$priceData = array_chunk($priceData,1000);
	echo "after";
	foreach($priceData as $dataSet) {
		$valueStr = implode(",",$dataSet);

		$sql = <<<EOT
INSERT INTO {$dbPrefix}_prices (systemID, itemID, datetime, {$priceType})
VALUES {$valueStr}
ON DUPLICATE KEY UPDATE {$priceType}=VALUES({$priceType});
EOT;

		if($reactDB->query($sql)) {
			echo "\n==========\nData for {$priceType} stored successfully in the database.\n==========\n";
		}
		else
		{
			echo "\n==========\nFailed to save data for {$priceType}.  Use this VALUE statement to update manually:\n{$valueStr}\n==========\n";
		}
	}
	echo "\n\n\nThere are ".count($priceData)." chunks of 1000 records to update for {$priceType}.\n\n\n";
}
