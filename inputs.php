<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<?php if (isset($argArray['configure'])) {
			echo "<title>Reaction Toolkit Configuration</title>";
		} else {
			echo "<title>Reaction Toolkit Dashboard</title>";
		} ?>
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<link rel="stylesheet" type="text/css" href="include/css/bootstrap.css">
		<link rel="stylesheet" type="text/css" href="include/css/toolkit.css">
		<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery/2.1.3/jquery.js"></script>
	</head>
	<body role="document">
		<?php include('include/pages/navbar.php'); ?>
		<div class="container-fluid">
			<div class="page-header">
				<h1 class="center">Reaction Input Lookup</h1>
			</div>
<?php
require_once('include/session_setup.php');
$form = new FormBuilder();
$scriptName = basename($_SERVER['PHP_SELF']);
if (isset($_POST['re'])) {
	$reactionID = $_POST['re'];
        $of = new ObjectFactory();
        $reaction = $of->create(ObjectFactory::REACTION, $reactionID);
	$reactionType = $reaction->getReactionType();
	if ($reactionType == 3) {
		$reactionName = $reaction->getOutput()->getName()." Alchemy";
	} else {
			$reactionName = $reaction->getOutput()->getName();
	}
	$inputs = $reaction->getInputs();
?>			<div class="center">
				<form class="form-inline" style="display:inline" method="post" action="<?php echo $scriptName; ?>">
					<input type='hidden' name='reset' value='1'>
					<button type='submit' class='btn btn-xs btn-danger'>Reset</button>
				</form>
			</div>
			<div class="center">
				<table class="table table-striped table-condensed">
					<thead>
						<tr>
							<th colspan='2'>
								<?php echo $reactionName; ?>
							</th>
						</tr>
						<tr>
							<th>Input</th>
							<th>Quantity</th>
						</tr>
					</thead>
					<tbody>
<?php	foreach($inputs as $input) {
?>
						<tr>
							<td><?php
                                $thisInput = $of->create(ObjectFactory::TYPE, $input['typeID']);
                                echo $thisInput->getName();
                                ?></td>
							<td align=right><?php echo $input['inputQty']; ?></td>
						</tr>
<?php	}
?>					</tbody>
				</table>
			</div>
<?php
} else {
?>			<div class="center">
				<?php $form->generateReactionSelectForm($scriptName,"Lookup"); ?>
			</div>
<?php }
?>		</div>
		<script src="include/javascript/bootstrap.min.js"></script>
	</body>
</html>

