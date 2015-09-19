<?php
require_once('include/session_setup.php');
$time = microtime();
$time = explode(' ', $time);
$time = $time[1] + $time[0];
$start = $time;
$scriptName = basename($_SERVER['PHP_SELF']);
$form = new Form();
$db = new Database();
if (isset($argArray['re'])) {
    $reactionID = $argArray['re'];
    $reaction = new Reaction($reactionID);
}
if (isset($argArray['c']) && ($reaction->getReactionType() == 2)) {
    $chain = 1;
    $chainStr = "Reaction Chain";
} else {
    $chain = 0;
    $chainStr = "Reaction";
}
if (isset($argArray['d'])) {
    $datetime = $db->getLastTimestamp($argArray['d'],300);
    $dateChanged = 1;
} else {
    $datetime = $db->getLastTimestamp(time(),300);
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
    "st" => $_SESSION['params']['st'],
    "b" => $_SESSION['params']['b'],
    "t" => $_SESSION['params']['t']
);
?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>Reaction Scenario Drill-down</title>
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
		<div class="container">
<?php if (isset($argArray['re'])) {
    $reactionID = $argArray['re'];
	if (isset($argArray['configure'])) { ?>
            <div class="page-header">
				<h1 class="center">Reaction Scenario Options</h1>
				<h2 class="center"><small><?php echo $reaction->getOutput()->getName(); ?> Reaction</small></h2>
			</div>
			<div class="center">
				<?php $form->generateResetButton($scriptName); ?>
			</div>
			<div class="center">
                <?php $form->generateOptionsForm($scriptName,"Calculate",$options,$reactionID);?>
			</div>
<?php	} else {
        $scenario = new Scenario($reactionID,$chain,$datetime);
        switch($_SESSION['params']['t']) {
			case "d":
				$timeframeStr = "Daily";
				break;
			case "w": 
				$timeframeStr = "Weekly";
				break;
			case "m":
				$timeframeStr = "Monthly";
				break; 
		} ?>
            <div class="page-header">
				<h1 class="center"><?php echo $scenario->getReactionName(); echo " {$chainStr}"; ?></h1>
				<h2 class="center"><small><?php if (isset($dateChanged)) { echo "Historical "; }?><?php echo $timeframeStr; ?> Report</small></h2>
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
			<br>
<?php		if (isset($dateChanged)) { ?>
			<div class="alert alert-danger alert-dismissible fade in">
				<button type="button" class="close" data-dismiss="alert">&times;</button>
				<p class="center"><strong>Hey!</strong>&nbsp;&nbsp;This scenario is based on historical price data from <?php echo date("F j, Y \\a\\t H:i:s e",strtotime($datetime)); ?>.  I trust that you know what you're doing!</p>
			</div>
<?php		}
?>
			<div class="row">
                <div class="col-sm-6">
                    <div class="panel panel-primary">
                        <div class="panel-heading">
                            <h3 class="panel-title">Inputs</h3>
                        </div>
                        <?php $scenario->generateInputTable(); ?>
                    </div>
<?php if ($chain) { ?>
                    <div class="panel panel-default">
                        <div class="panel-heading">
                            <h3 class="panel-title">Intermediates</h3>
                        </div>
                        <?php $scenario->generateIntermediateTable(); ?>
                    </div>
<?php } ?>
                    <div class="panel panel-primary">
                        <div class="panel-heading">
                            <h3 class="panel-title">Output</h3>
                        </div>
                        <?php $scenario->generateOutputTable(); ?>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="panel panel-info">
                        <div class="panel-heading">
                            <h3 class="panel-title">Towers and Equipment</h3>
                        </div>
                        <?php $scenario->generateTowerList(); ?>
                    </div>
                    <div class="panel panel-info">
                        <div class="panel-heading">
                            <h3 class="panel-title">Fuel</h3>
                        </div>
                        <?php $scenario->generateFuelTable(); ?>
                    </div>
                    <div class="panel panel-info">
                        <div class="panel-heading">
                            <h3 class="panel-title">Shipping</h3>
                        </div>
                        <?php $scenario->generateShippingReport(); ?>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-4 col-sm-offset-4">
                    <div class="panel panel-success">
                        <div class="panel-heading">
                            <h3 class="panel-title"><?php echo $timeframeStr;?> Income Statement</h3>
                        </div>
                        <?php $scenario->generateIncomeStatement(); ?>
                    </div>
                </div>
            </div>
			<div class="center">
				<p class="small"><?php include('include/pages/price_update.txt'); ?></p>
			</div>
<?php
		$time = microtime();
		$time = explode(' ', $time);
		$time = $time[1] + $time[0];
		$finish = $time;
		$total_time = round(($finish - $start), 4);
?>			<div class="center">
				<p class="small">Page generated in <?php echo $total_time; ?> seconds.</p>
			</div>
<?php	}
} else {
?>
            <div class="page-header">
				<h1 class="center">Reaction Scenario Calculator</h1>
			</div>
			<div class="center">
				<?php $form->generateReactionSelectForm($scriptName,"Configure Options"); ?>
			</div>
<?php
	}
?>
		<script src="include/javascript/bootstrap.min.js"></script>
	</body>
</html>
