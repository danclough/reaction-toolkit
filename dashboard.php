<?php
require_once('include/session_setup.php');
$time = microtime();
$time = explode(' ', $time);
$time = $time[1] + $time[0];
$start = $time;
$scriptName = basename($_SERVER['PHP_SELF']);
$options = array(
    "r" => $_SESSION['params']['r'],
    "s" => $_SESSION['params']['s'],
    "g" => $_SESSION['params']['g'],
    "sy" => $_SESSION['params']['sy'],
    "i" => $_SESSION['params']['i'],
    "f" => $_SESSION['params']['f'],
    "o" => $_SESSION['params']['o'],
    "t" => $_SESSION['params']['t']
);
$fmt = new Formatter();
$form = new Form();
$db = new Database();
$datetime = $db->getLastTimestamp(time(),300);
?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<?php if (isset($argArray['configure'])) {
			echo "<title>Reaction Toolkit Configuration</title>";
		} else {
			echo "<title>Reaction Toolkit Dashboard</title>";
		} ?>
		<link rel="stylesheet" type="text/css" href="include/css/bootstrap.css">
		<link rel="stylesheet" type="text/css" href="include/css/toolkit.css">
		<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery/2.1.3/jquery.js"></script>
		<script type="text/javascript">
			$(function () {
				$('[data-toggle="popover"]').popover({ html : true })
			})
            $(function () {
                $('[data-toggle="tooltip"]').tooltip()
            })
		</script>
	</head>
	<body role="document">
		<?php include('include/pages/navbar.php'); ?>
		<div class="container-fluid">
			<div class="page-header">
<?php if (isset($argArray['configure'])) {
?>				<h1 class="center">Dashboard Options</h1>
			</div>
			<div class="center">
				<?php $form->generateOptionsForm($scriptName,"Save",$options); ?>
			</div>
<?php } else {
	switch ($_SESSION['params']['t']) {
		case "m":
			$timeframeStr = "Monthly";
			break;
		case "w":
			$timeframeStr = "Weekly";
			break;
		case "d": 
			$timeframeStr = "Daily";
			break;
	}
?>				<h1 class="center">Reaction Toolkit Dashboard</h1>
				<h2 class="center"><small><?php echo $timeframeStr; ?> Net Income</small></h2>
			</div>	
			<div class="center">
                <form id="configure" class="form-inline" style="display:inline" action="<?php echo $scriptName; ?>" method="post">
                    <input type="hidden" name="configure" value="1">
                </form>
                <form id="reset" class="form-inline" style="display:inline" method="post" action="<?php echo $scriptName; ?>">
                    <input type="hidden" name="reset" value="1">
                </form>
                <div class="btn-group btn-group-xs">
                    <button class="btn btn-success" type="submit" form="configure">Configure</button>
                    <button class="btn btn-danger" type="submit" form="reset">Reset</button>
                    <a class="btn btn-primary" href="
                    <?php $form->generatePermalink($options); ?>">Permalink</a>
                </div>
			</div>
<?php
	$systemName = $db->getSystemName($_SESSION['params']['sy']);
	$numCycles = $db->getNumCycles($_SESSION['params']['t']);
	$simpleReactions = $db->getAllReactionIDs(1);
	$complexReactions = $db->getAllReactionIDs(2);
	$alchemyReactions = $db->getAllReactionIDs(3);
	$polymerReactions = $db->getAllReactionIDs(4);
?>			<div class="center">
				<img src="include/images/moonminingbee.png" alt="Moon mining bee wants your precious goo." style="width:200px;height:225px;float:right;" alt="">
				<p>This dashboard shows real-time net income calculations for all simple and complex reactions. Assumptions are:</p>
				<ul>
					<li>Simple, alchemy, and polymer reactions are calculated as a single reaction on a medium tower.</li>
<?php	if ($_SESSION['params']['r'] == 3) { ?>
					<li>All reactions done on Gallente towers for minimal effort.</li>
<?php	} else { ?>
					<li>All reactions done on Caldari towers for maximum profit.</li>
<?php	}
	if ($_SESSION['params']['s'] && !$_SESSION['params']['g']) { ?>
					<li>All towers anchored in systems with sovereignty for 25% fuel reduction.</li>
<?php	}
	if ($_SESSION['params']['s'] && $_SESSION['params']['g']) { ?>
					<li>All towers anchored in systems with sovereignty for 25% fuel reduction and are subject to the Goonswarm Federation moon tax.</li>
<?php	}
	if (!$_SESSION['params']['s'] && $_SESSION['params']['g']) { ?>
					<li>All towers are subject to the Goonswarm Federation moon tax.</li>
<?php	}
	if ($_SESSION['params']['i'] == "b" && $_SESSION['params']['f'] == "b") { ?>
					<li>Inputs and fuel blocks are purchased at highest <?php echo $systemName; ?> buy price.</li>
<?php	} elseif ($_SESSION['params']['i'] == "s" && $_SESSION['params']['f'] == "b") { ?>
					<li>Inputs are purchased from lowest <?php echo $systemName; ?> sell order.</li>
					<li>Fuel blocks are purchased at highest <?php echo $systemName; ?> buy price.</li>
<?php	} elseif ($_SESSION['params']['i'] == "b" && $_SESSION['params']['f'] == "s") { ?>
					<li>Inputs are purchased at highest <?php echo $systemName; ?> buy price.</li>
					<li>Fuel blocks are purchased from lowest <?php echo $systemName; ?> sell order.</li>
<?php	} else { ?>
					<li>Inputs and fuel blocks are purchased from lowest <?php echo $systemName; ?> sell order.</li>
<?php	}
	if ($_SESSION['params']['o'] == "s") { ?>
					<li>Output sold at lowest <?php echo $systemName; ?> sell price.</li>
<?php	} else { ?>
					<li>Output sold to highest <?php echo $systemName; ?> buy order.</li>
<?php } ?>
				</ul>
				<p>Click on a reaction for an in-depth analysis including shipping costs, volume estimates, taxes, and fees.</p>
			</div>	
			<div class="row">
				<div class="col-sm-4">
                    <div class="panel panel-primary">
                        <div class="panel-heading">
                            <h3 class="panel-title">Simple Reactions</h3>
                        </div>
                        <table class="table table-condensed">
                            <thead>
                                <tr>
                                    <th>Reaction</th>
                                    <th style="text-align:right">Net Income</th>
                                </tr>
                            </thead>
                            <tbody>
<?php	foreach ($simpleReactions as $reactionID) {
        $reaction = new Reaction($reactionID);
		$typeID = $reaction->getOutput()->getTypeID();
		$itemName = $reaction->getOutput()->getName();
		$itemDesc = htmlspecialchars($reaction->getOutput()->getDescription());
		$imageURL = "https://image.eveonline.com/type/{$typeID}_64.png";
		$mouseoverText = "<img src=\"{$imageURL}\" style=\"float:left;\">{$itemDesc}";
        $calc = new Calculator($reactionID,0,$datetime);
		$netIncome = $calc->getHourlyNetIncome()*$numCycles;
		if ($netIncome > 0) {
			$class = "success positive";
		}
		else {
			$class = "danger negative";
		}
    ?>							<tr>
                                    <td>
                                        <a data-toggle="popover" data-trigger="hover" data-placement="bottom" title="<?php echo $itemName; ?>" data-content="<?php echo htmlspecialchars($mouseoverText); ?>"><?php echo $itemName; ?></a>
                                    </td>
                                    <td class="<?php echo $class; ?>" align="right">
                                        <?php echo $fmt->formatAsISK($netIncome); ?>
                                        <form class="form-inline" style="display:inline" name="submitForm" method="POST" action="scenario.php">
                                            <input type="hidden" name="re" value="<?php echo $reactionID; ?>">
                                            <input type="image" src="include/images/report.svg" width="16" height="16">
                                        </form>
                                        <form class="form-inline" style="display:inline" name="submitForm" method="POST" action="history.php">
                                            <input type="hidden" name="re" value="<?php echo $reactionID; ?>">
                                            <input type="image" src="include/images/graph.svg" width="16" height="16">
                                        </form>
                                    </td>
                                </tr>
    <?php	}
    ?>						</tbody>
                        </table>
                    </div>
				</div>
				<div class="col-sm-4">
                    <div class="panel panel-primary">
                        <div class="panel-heading">
                            <h3 class="panel-title">Complex Reactions</h3>
                        </div>
                        <table class="table table-condensed">
                            <thead>
                                <tr>
                                    <th>Reaction</th>
                                    <th style="text-align:right">Net Income</th>
                                </tr>
                            </thead>
                            <tbody>
<?php	foreach ($complexReactions as $reactionID) {
        $reaction = new Reaction($reactionID);
        $typeID = $reaction->getOutput()->getTypeID();
        $itemName = $reaction->getOutput()->getName();
        $itemDesc = htmlspecialchars($reaction->getOutput()->getDescription());
        $imageURL = "https://image.eveonline.com/type/{$typeID}_64.png";
        $mouseoverText = "<img src=\"{$imageURL}\" style=\"float:left;\">{$itemDesc}";
        $calc = new Calculator($reactionID,0,$datetime);
        $netIncome = $calc->getHourlyNetIncome()*$numCycles;
		if ($netIncome > 0) {
			$class = "success positive";
		}
		else {
			$class = "danger negative";
		}
    ?>							<tr>
                                    <td>
                                        <a data-toggle="popover" data-trigger="hover" data-placement="bottom" title="<?php echo $itemName; ?>" data-content="<?php echo htmlspecialchars($mouseoverText); ?>"><?php echo $itemName; ?></a>
                                    </td>
                                    <td class="<?php echo $class; ?>" align="right">
                                        <?php echo $fmt->formatAsISK($netIncome); ?>
                                        <form class="form-inline" style="display:inline" name="submitForm" method="POST" action="scenario.php">
                                            <input type="hidden" name="re" value="<?php echo $reactionID; ?>">
                                            <input type="image" src="include/images/report.svg" width="16" height="16">
                                        </form>
                                        <form class="form-inline" style="display:inline" name="submitForm" method="POST" action="history.php">
                                            <input type="hidden" name="re" value="<?php echo $reactionID; ?>">
                                            <input type="image" src="include/images/graph.svg" width="16" height="16">
                                        </form>
                                    </td>
                                </tr>
<?php	}
    ?>						</tbody>
                        </table>
                    </div>
				</div>
				<div class="col-sm-4">
                    <div class="panel panel-primary">
                        <div class="panel-heading">
                            <h3 class="panel-title">Complex Chains</h3>
                        </div>
                        <table class="table table-condensed">
                            <thead>
                                <tr>
                                    <th>Reaction</th>
                                    <th style="text-align:right">Net Income</th>
                                </tr>
                            </thead>
                            <tbody>
<?php	foreach ($complexReactions as $reactionID) {
        $reaction = new Reaction($reactionID);
        $typeID = $reaction->getOutput()->getTypeID();
        $itemName = $reaction->getOutput()->getName();
        $itemDesc = htmlspecialchars($reaction->getOutput()->getDescription());
        $imageURL = "https://image.eveonline.com/type/{$typeID}_64.png";
        $mouseoverText = "<img src=\"{$imageURL}\" style=\"float:left;\">{$itemDesc}";
        $calc = new Calculator($reactionID,1,$datetime);
        $netIncome = $calc->getHourlyNetIncome()*$numCycles;
		if ($netIncome > 0) {
			$class = "success positive";
		}
		else {
			$class = "danger negative";
		}
    ?>							<tr>
                                    <td>
                                        <a data-toggle="popover" data-trigger="hover" data-placement="bottom" title="<?php echo $itemName; ?>" data-content="<?php echo htmlspecialchars($mouseoverText); ?>"><?php echo $itemName; ?></a>
                                    </td>
                                    <td class="<?php echo $class; ?>" align="right">
                                        <?php echo $fmt->formatAsISK($netIncome); ?>
                                        <form class="form-inline" style="display:inline" name="submitForm" method="POST" action="scenario.php">
                                            <input type="hidden" name="re" value="<?php echo $reactionID; ?>">
                                            <input type="hidden" name="c" value="1">
                                            <input type="image" src="include/images/report.svg" width="16" height="16">
                                        </form>
                                        <form class="form-inline" style="display:inline" name="submitForm" method="POST" action="history.php">
                                            <input type="hidden" name="re" value="<?php echo $reactionID; ?>">
                                            <input type="hidden" name="c" value="1">
                                            <input type="image" src="include/images/graph.svg" width="16" height="16">
                                        </form>
                                    </td>
                                </tr>
<?php	}
    ?>						</tbody>
                        </table>
                    </div>
				</div>
				<div class="col-sm-4">
                    <div class="panel panel-primary">
                        <div class="panel-heading">
                            <h3 class="panel-title">Alchemy Reactions</h3>
                        </div>
                        <table class="table table-condensed">
                            <thead>
                                <tr>
                                    <th>Reaction</th>
                                    <th style="text-align:right">Net Income</th>
                                </tr>
                            </thead>
                            <tbody>
<?php	foreach ($alchemyReactions as $reactionID) {
        $reaction = new Reaction($reactionID);
        $typeID = $reaction->getOutput()->getTypeID();
        $itemName = $reaction->getOutput()->getName();
        $itemDesc = htmlspecialchars($reaction->getOutput()->getDescription());
        $imageURL = "https://image.eveonline.com/type/{$typeID}_64.png";
        $mouseoverText = "<img src=\"{$imageURL}\" style=\"float:left;\">{$itemDesc}";
        $calc = new Calculator($reactionID,0,$datetime);
        $netIncome = $calc->getHourlyNetIncome()*$numCycles;
		if ($netIncome > 0) {
			$class = "success positive";
		}
		else {
			$class = "danger negative";
		}
    ?>							<tr>
                                    <td>
                                        <a data-toggle="popover" data-trigger="hover" data-placement="bottom" title="<?php echo $itemName; ?>" data-content="<?php echo htmlspecialchars($mouseoverText); ?>"><?php echo $itemName; ?></a>
                                    </td>
                                    <td class="<?php echo $class; ?>" align="right">
                                        <?php echo $fmt->formatAsISK($netIncome); ?>
                                        <form class="form-inline" style="display:inline" name="submitForm" method="POST" action="scenario.php">
                                            <input type="hidden" name="re" value="<?php echo $reactionID; ?>">
                                            <input type="image" src="include/images/report.svg" width="16" height="16">
                                        </form>
                                        <form class="form-inline" style="display:inline" name="submitForm" method="POST" action="history.php">
                                            <input type="hidden" name="re" value="<?php echo $reactionID; ?>">
                                            <input type="image" src="include/images/graph.svg" width="16" height="16">
                                        </form>
                                    </td>
                                </tr>
<?php	}
    ?>						</tbody>
                        </table>
                    </div>
				</div>
				<div class="col-sm-4">
                    <div class="panel panel-primary">
                        <div class="panel-heading">
                            <h3 class="panel-title">Polymer Reactions</h3>
                        </div>
                        <table class="table table-condensed">
                            <thead>
                                <tr>
                                    <th>Reaction</th>
                                    <th style="text-align:right">Net Income</th>
                                </tr>
                            </thead>
                            <tbody>
<?php	foreach ($polymerReactions as $reactionID) {
        $reaction = new Reaction($reactionID);
        $typeID = $reaction->getOutput()->getTypeID();
        $itemName = $reaction->getOutput()->getName();
        $itemDesc = htmlspecialchars($reaction->getOutput()->getDescription());
        $imageURL = "https://image.eveonline.com/type/{$typeID}_64.png";
        $mouseoverText = "<img src=\"{$imageURL}\" style=\"float:left;\">{$itemDesc}";
        $calc = new Calculator($reactionID,0,$datetime);
        $netIncome = $calc->getHourlyNetIncome()*$numCycles;
		if ($netIncome > 0) {
			$class = "success positive";
		}
		else {
			$class = "danger negative";
		}
    ?>							<tr>
                                    <td>
                                        <a data-toggle="popover" data-trigger="hover" data-placement="bottom" title="<?php echo $itemName; ?>" data-content="<?php echo htmlspecialchars($mouseoverText); ?>"><?php echo $itemName; ?></a>
                                    </td>
                                    <td class="<?php echo $class; ?>" align="right">
                                        <?php echo $fmt->formatAsISK($netIncome); ?>
                                        <form class="form-inline" style="display:inline" name="submitForm" method="POST" action="scenario.php">
                                            <input type="hidden" name="re" value="<?php echo $reactionID; ?>">
                                            <input type="image" src="include/images/report.svg" width="16" height="16">
                                        </form>
                                        <form class="form-inline" style="display:inline" name="submitForm" method="POST" action="history.php">
                                            <input type="hidden" name="re" value="<?php echo $reactionID; ?>">
                                            <input type="image" src="include/images/graph.svg" width="16" height="16">
                                        </form>
                                    </td>
                                </tr>
<?php	}
?>						    </tbody>
                        </table>
                    </div>
				</div>
			</div>
<?php
	$time = microtime();
	$time = explode(' ', $time);
	$time = $time[1] + $time[0];
	$finish = $time;
	$total_time = round(($finish - $start), 4);
?>			<div class="center">
				<p class="small">
					<?php include('include/pages/price_update.txt'); ?>
				</p>
			</div>
			<div class="center">
				<p class="small">
					Page generated in <?php echo $total_time; ?> seconds.
				</p>
			</div>
<?php }
?>
		<script src="include/javascript/bootstrap.min.js"></script>
	</body>
</html>
