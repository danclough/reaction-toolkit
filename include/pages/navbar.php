<nav class="navbar navbar-red navbar-fixed-top">
	<div class="container-fluid">
		<div class="navbar-header">
			<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
				<span class="sr-only">Toggle navigation</span>
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
			</button>
			<a class="navbar-brand" href="#"><img width=44 height=20 src="include/images/rtk-white.png" alt="RTk"></a>
		</div>
		<center>
			<div id="navbar" class="collapse navbar-collapse">
				<ul class="nav navbar-nav">
					<li<?php if (basename($_SERVER['PHP_SELF']) == "dashboard.php") { echo " class=\"active\""; } ?>><a href="dashboard.php">Dashboard</a></li>
					<li<?php if (basename($_SERVER['PHP_SELF']) == "scenario.php") { echo " class=\"active\""; } ?>><a href="scenario.php">Reaction Scenario</a></li>
					<li<?php if (basename($_SERVER['PHP_SELF']) == "history.php") { echo " class=\"active\""; } ?>><a href="history.php">Historical Trends</a></li>
					<li<?php if (basename($_SERVER['PHP_SELF']) == "inputs.php") { echo " class=\"active\""; } ?>><a href="inputs.php">Input Lookup</a></li>
				</ul>
				<ul class="nav navbar-nav navbar-right">
					<li<?php if (basename($_SERVER['PHP_SELF']) == "help.php") { echo " class=\"active\""; } ?>><a href="help.php">Help</a><li>
				</ul>
			</div>
		</center>
	</div>
</nav>
