<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>Reaction Toolkit Help</title>
		<link rel="stylesheet" type="text/css" href="include/css/bootstrap.css">
		<link rel="stylesheet" type="text/css" href="include/css/toolkit.css">
		<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery/2.1.3/jquery.js"></script>
	</head>
	<body role="document">
		<?php include('include/pages/navbar.php'); ?>
		<div class="container">
			<div class="page-header">
				<h1 class="center">Reaction Toolkit Help</h1>
			</div>
			<div class="center">
				<h4>wat</h4>
				<p>Welcome to this jumbled monstrosity of PHP, horribly written style sheets, and questionable use of accounting terms.&nbsp;&nbsp;If you're here you're probably wondering what the fuck, so I'll keep things brief.</p>
				<h3>The Reaction Toolkit</h3>
				<p>The Reaction Toolkit is a web-based utility I wrote in a fit of rage when Google Sheets stopped updating my XML price feeds for the 80th time.&nbsp;&nbsp;The goal of the Reaction Toolkit is to automate the tedious math involved in computing profits for reactions in EVE Online.&nbsp;&nbsp;The Reaction Toolkit does this by analyzing market data and crunching numbers to determine the profitability of a reaction or reaction chain.</p>
				<p>There are several components that make up the toolkit.&nbsp;&nbsp;Please see below for detailed explanations.</p>
				<h4>The Dashboard</h4>
				<p>The Reaction Analysis Dashboard is the core of the toolkit.&nbsp;&nbsp;The dashboard displays current net income statistics for every possible reaction in EVE, including Alchemy and Polymers.&nbsp;&nbsp;Drugs<p>
				<p>You'll notice that next to each Net Income figure on the dashboard is a set of clickable icons. Here's what they do:</p>
				<dl class="dl-horizontal">
					<dt><img src='include/images/report.svg' width='16' height='16'> Scenario Report</dt>
						<dd>The Scenario Report is an in-depth breakdown and profitability analysis of a particular reaction or reaction chain.&nbsp;&nbsp;It takes into account even small costs like shipping, sales tax, and broker fees.</dd>
					<dt><img src='include/images/graph.svg' width='16' height='16'> Historical Trend</dt>
						<dd>The Historical Trend report provides a graph and table of net income data based on historical prices within a given timeframe.</dd>
				</dl>
				<p>The Reaction Analysis Dashboard defaults to the following options:</p>
				<dl class="dl-horizontal">
					<dt>Tower Race</dt><dd>Gallente</dd>
					<dt>Sovereignty</dt><dd>Enabled</dd>
					<dt>Market System</dt><dd>Jita</dd>
					<dt>Input Price</dt><dd>Max Buy</dd>
					<dt>Fuel Block Price</dt><dd>Max Buy</dd>
					<dt>Output Price</dt><dd>Min Sell</dd>
					<dt>Timeframe</dt><dd>Monthly</dd>
				</dl>
				<p>These settings can be modified by clicking the Change Options button at the top of the page.</p>
				<h4>Reaction Scenario</h4>
				<p>The Reaction Scenario calculator provides a detailed breakdown and profitability analysis of a particular reaction or reaction chain.&nbsp;&nbsp;Every cost is taken into account to determine the profitability of a particular reaction.&nbsp;&nbsp;The Reaction Scenario calculator can be accessed from the menu bar, and will allow you to create a new scenario from scratch.&nbsp;&nbsp;Alternatively, clicking the Scenario Report icon on the Dashboard will take you straight to the report for a given reaction.</p>
				<h4>Historical Trends</h4>
				<p>The Historical Trend report provides daily, weekly, or monthly net income data for a given reaction over a specified period of time.&nbsp;&nbsp;The Historical Trend report can provide data for the following intervals:</p>
				<ul>
					<li>Hourly over a period of 24 hours</li>
					<li>Daily over a period of 30 days</li>
					<li>Monthly over a period of one year</li>
				</ul>
				<p>The Historical Trend report provides both a line graph and a table of the requested data.&nbsp;&nbsp;Clicking on the Scenario Report icon next to each date will generate a Reaction Scenario report using price data for the respective date and time.</p>
				<h4>Input Lookup</h4>
				<p>The Input Lookup tool does one thing - takes input in the form of a reaction, and lists the inputs required to react it.&nbsp;&nbsp;Not necessarily groundbreaking or innovative, but it could still prove useful from time to time.</p>	
			</div>
		</div>
		<script src="include/javascript/bootstrap.min.js"></script>
	</body>
</html>

