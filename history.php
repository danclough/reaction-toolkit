<?php
require_once('include/session_setup.php');
require_once 'include/chart.php';
$time = microtime();
$time = explode(' ', $time);
$time = $time[1] + $time[0];
$start = $time;
$scriptName = basename($_SERVER['PHP_SELF']);
$db = new DatabaseManager(true);
$of = new ObjectFactory();
$form = new FormBuilder();
$options = array(
    "r" => $_SESSION['params']['r'],
    "s" => $_SESSION['params']['s'],
    "g" => $_SESSION['params']['g'],
    "sy" => $_SESSION['params']['sy'],
    "i" => $_SESSION['params']['i'],
    "f" => $_SESSION['params']['f'],
    "o" => $_SESSION['params']['o'],
    "t" => $_SESSION['params']['t'],
    "rn" => $_SESSION['params']['rn']
);
?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<link rel="stylesheet" type="text/css" href="include/css/bootstrap.css">
		<link rel="stylesheet" type="text/css" href="include/css/toolkit.css">
		<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery/2.1.3/jquery.js"></script>
<?php if (isset($argArray['re'])) {
    $reactionID = $argArray['re'];
    $reaction = $of->create(ObjectFactory::REACTION, $reactionID);
    $typeID = $reaction->getOutput()->getID();
    $itemName = $reaction->getOutput()->getName();
    $reactionType = $reaction->getReactionType();
    switch ($reactionType) {
        case 1:
            $chain = 0;
            $chainStr = "Reaction";
            break;
        case 2:
            if (isset($argArray['c'])) {
                $chain = 1;
                $chainStr = "Reaction Chain";
            } else {
                $chain = 0;
                $chainStr = "Reaction";
            }
            break;
        case 3:
            $chain = 0;
            $chainStr = "Alchemy Reaction";
            break;
        case 4:
            $chain = 0;
            $chainStr = "Polymer Reaction";
            break;
    }
    $options = array(
        "r" => $_SESSION['params']['r'],
        "c" => $chain,
        "s" => $_SESSION['params']['s'],
        "g" => $_SESSION['params']['g'],
        "sy" => $_SESSION['params']['sy'],
        "i" => $_SESSION['params']['i'],
        "f" => $_SESSION['params']['f'],
        "o" => $_SESSION['params']['o'],
        "t" => $_SESSION['params']['t'],
        "rn" => $_SESSION['params']['rn']
    );
	if (isset($argArray['configure'])) {
?>	</head>
	<body role="document">
		<?php include('include/pages/navbar.php'); ?>
		<div class="container-fluid">
			<div class="page-header">
				<h1 class="center">Historical Trend Report Options</h1>
				<h2 class="center"><small><?php echo $itemName; ?> Reaction</small></h2>
			</div>
			<div class="center">	
				<?php $form->generateResetButton($scriptName); ?>
			</div>
			<div class="center">
			    <?php $form->generateOptionsForm($scriptName,"Calculate",$options,$reactionID); ?>
            </div>
        </div>
        <script src="include/javascript/bootstrap.min.js"></script>
    </body>
<?php	} else {
		switch ($_SESSION['params']['rn']) {
			case "d":
				$rangeStr = "Last 24 hours";
				$numResults = 24;
				$modulo = 3600;
				$headerStr = "Time";
				break;
			case "m":
				$rangeStr = "Last 30 days";
				$numResults = 30;
				$modulo = 86400;
				$headerStr = "Date";
				break;
			case "y":
				$rangeStr = "Last 12 months";
				$numResults = 12;
				$modulo = 2592000;
				$headerStr = "Month";
				break;
		}
		switch ($_SESSION['params']['t']) {
			case "d":
				$timeframeStr = "Daily";
				$numCycles = $db->getNumCycles($_SESSION['params']['t']);
				break;
			case "w":
				$timeframeStr = "Weekly";
				$numCycles = $db->getNumCycles($_SESSION['params']['t']);
				break;
			case "m":
				$timeframeStr = "Monthly";
				$numCycles = $db->getNumCycles($_SESSION['params']['t']);
				break;
		}
        $numCycles = $db->getNumCycles($_SESSION['params']['t']);
		$graphTitle = "{$itemName} {$chainStr} Historical {$timeframeStr} Net Income";
		
		$historicalData = array();
		for ($x = 0; $x < $numResults; $x++) {
            $datetime = $db->getLastTimestamp(time()-($modulo*$x),$modulo);
            switch ($_SESSION['params']['rn']) {
                case "d":
                    $datetime = $db->getLastTimestamp(time()-($modulo*$x),$modulo);
                    break;
                case "m":
                    $datetime = date("Y-m-d",strtotime($datetime));
                    break;
                case "y":
                    $datetime = date("F Y",strtotime($datetime));
                    break;
            }
            $calc = new Calculator($reactionID,$chain,$datetime);
			$netIncome = $calc->getHourlyNetIncome()*$numCycles;
			$historicalData[$datetime] = $netIncome;
		}
	
		$data = array();
		foreach ($historicalData as $dateStamp => $netIncome) {
			array_push($data,"['{$dateStamp}',{$netIncome}]");
		}
		$numPoints = count($historicalData) - 1;
		$hAxisOpts = "{gridlines:{color:'red'},textStyle:{color:'black'},showTextEvery:{$numPoints}}";
		$vAxisOpts = "{textStyle:{color:'black'}}";
		$legendOpts = "{position:'none',alignment:'center',textStyle:{color:'white',fontSize: 12}}";
		$chartOpts = "title:'{$graphTitle}',chartArea: {width: '75%', height: '75%'},colors:['blue'],legend:{$legendOpts},hAxis:{$hAxisOpts},vAxis:{$vAxisOpts},backgroundColor:'white',width:800,height:400,curveType:'function',pointSize:10,reverseCategories:true";
?>		<script type="text/javascript" src="https://www.google.com/jsapi"></script>
		<script type="text/javascript">
			google.load('visualization', '1.0', {'packages':['corechart']});
			google.setOnLoadCallback(drawChart);
			function drawChart() {
				var data = new google.visualization.DataTable();
				data.addColumn('string', 'Date');
				data.addColumn('number', 'Monthly Net Income');
				data.addRows([<?php echo implode(",",$data); ?>]);
				var formatter = new google.visualization.NumberFormat({suffix: ' ISK', negativeColor: 'red', negativeParens: true});
				formatter.format(data, 1);
				var chartOptions = {<?php echo $chartOpts; ?>};
				var chart = new google.visualization.LineChart(document.getElementById('chart_div'));
				chart.draw(data, chartOptions);
			}
		</script>
        <script type="text/javascript">
            $(function () {
                $('[data-toggle="popover"]').popover({ html : true })
            })
            $(function () {
                $('[data-toggle="tooltip"]').tooltip()
            })
        </script>
		<title><?php echo $graphTitle; ?></title>
	</head>
	<body role="document">
		<?php include('include/pages/navbar.php'); ?>
		<div class="container-fluid">
			<div class="page-header">
				<h1 class="center"><?php echo $itemName; echo " ".$chainStr; ?></h1>
				<h2 class="center"><small>Historical Trend over <?php echo $rangeStr; ?></small></h2>
			</div>
            <div class="center">
                <form id="configure" class="form-inline" style="display:inline" action="<?php echo $scriptName; ?>" method="post">
                    <input type="hidden" name="re" value="<?php echo $reactionID; ?>">
                    <input type="hidden" name="configure" value="1">
                    <?php		if ($chain) { ?>
                        <input type="hidden" name="c" value="1">
                    <?php		} ?>
                </form>
                <form id="reset" class="form-inline" style="display:inline" method="post" action="<?php echo $scriptName; ?>">
                    <input type="hidden" name="reset" value="1">
                </form>
                <div class="btn-group btn-group-xs">
                    <button class="btn btn-success" type="submit" form="configure">Configure</button>
                    <button class="btn btn-danger" type="submit" form="reset">Reset</button>
                    <a class="btn btn-primary" href="<?php $form->generatePermalink($options,$reactionID,$chain);?>">Permalink</a>
                </div>
            </div>
			<div class="center">
				<div class="center" id="chart_div" style="width:800; height:400"></div>
				<div class="center">
					<table class="table table-condensed">
						<thead>
							<tr>
								<th>
									<?php echo $headerStr; ?>
								</th>
								<th>
									<?php echo $timeframeStr; ?> Net Income
								</th>
							</tr>
						</thead>
						<tbody>
<?php		foreach ($historicalData as $time => $netIncome) {
?>
							<tr>
								<td>
									<?php echo date("j F Y, G:i e",strtotime($time)); ?>
								</td>
<?php			if ((float) $netIncome >= 0) {
?>								<td class="success">
<?php			} else {
?>								<td class="danger">
<?php			}
?>									<?php echo formatAsISK($netIncome); ?>
									<form class="form-inline" style="display:inline" name="submitForm" method="POST" action="scenario.php">
										<input type="hidden" name="re" value="<?php echo $reactionID; ?>">
										<input type="hidden" name="c" value="<?php echo $chain; ?>">
										<input type="hidden" name="d" value="<?php echo strtotime($time); ?>">
										<input type="image" src="include/images/report.svg" width="16" height="16">
									</form>
								</td>
							</tr>
<?php		}
?>						</tbody>
					</table>
				</div>
			</div>
<?php	$time = microtime();
		$time = explode(' ', $time);
		$time = $time[1] + $time[0];
		$finish = $time;
		$total_time = round(($finish - $start), 4);
?>			<div class="center">
				<p class="small">Page generated in <?php echo $total_time; ?> seconds.</p>
			</div>
        </div>
        <script src="include/javascript/bootstrap.min.js"></script>
    </body>
<?php	}
} else { ?>
    <title>Historical Trend Report</title>
    </head>
    <body role="document">
        <?php include('include/pages/navbar.php'); ?>
        <div class="container">
            <div class="page-header">
                <h1 class="center">Historical Trend Report</h1>
            </div>
            <div class="center">
			    <?php $form->generateReactionSelectForm($scriptName,"Configure Options"); ?>
		    </div>
        </div>
        <script src="include/javascript/bootstrap.min.js"></script>
    </body>
<?php } ?>
</html>
