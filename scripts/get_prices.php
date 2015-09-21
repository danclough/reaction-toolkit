<html>
<body>
<table>
<?php
chdir(__DIR__);
while (! file_exists('include/config/config.php')) {
	chdir('..');
}
require('include/config/config.php');

if (isset($_GET['systemID']) || isset($argv[1])) {
	if (isset($_GET['systemID'])) {
		$systemID = $_GET['systemID'];
	}
	else {
		$systemID = $argv[1];
	}
}
else {
	$systemID = 30000142;
}

$db = new DatabaseManager(true);

$prices = $db->getSystemPrices($systemID);

foreach($prices as $row) {
	echo "<tr>";
	foreach($row as $cell) {
		echo "<td>{$cell}</td>";
	}
	echo "</tr>";
}	
?>
</table>
</body>
</html>
