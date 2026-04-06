<?php
include_once('../config/symbini.php');
header("Content-Type: text/html; charset=".$CHARSET);
?>
<html>
	<head>
		<title>Tutorials and Help</title>
		<?php
		include_once($SERVER_ROOT.'/includes/head.php');
		?>
	</head>
	<body>
		<?php
		$displayLeftMenu = true;
		include($SERVER_ROOT.'/includes/header.php');
		?>
		<div class="navpath">
			<a href="<?php echo $CLIENT_ROOT; ?>/index.php">Home</a> &gt;&gt;
			<b>Tutorials and Help</b>
		</div>
		<!-- This is inner text! -->
		<div id="innertext">
	  <h1 style="text-align: center;">Tutorials and Help</h1>
	  <p>Find more information on how to use the NEON Biorepository Data Portal by clicking on these links:</p>
	  <ul>
		<li><a href="<?php echo $CLIENT_ROOT; ?>/misc/searchtutorial.php">Conduct a Sample Search</a></li>
		<li><a href="<?php echo $CLIENT_ROOT; ?>/misc/mapsearchtutorial.php">Conduct a Map Search</a></li>
		<li><a href="<?php echo $CLIENT_ROOT; ?>/misc/gettingstarted.php">Getting Started and Frequently Asked Questions</a></li>
	  </ul>



		</div>
		<?php
			include($SERVER_ROOT.'/includes/footer.php');
		?>
	</body>
</html>
