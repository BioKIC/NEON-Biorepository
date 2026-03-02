<?php
include_once('../config/symbini.php');
header("Content-Type: text/html; charset=".$CHARSET);
?>
<html>
	<head>
		<title>Sample Archival Request Form</title>
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
			<b>Sample Archival Request Form</b>
		</div>
		<!-- This is inner text! -->
		<div id="innertext" style="text-align: center;">
			<h1>Sample Archival Request Form</h1>
			<iframe src="https://asu.co1.qualtrics.com/jfe/form/SV_6eWQllcWCUGtvro" width="790" height="1000px" frameborder="0" marginheight="0" marginwidth="0" style="margin-top: 2rem">Loading…</iframe></iframe>
		</div>
		<?php
			include($SERVER_ROOT.'/includes/footer.php');
		?>
	</body>
</html>
