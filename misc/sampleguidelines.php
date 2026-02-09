<?php
include_once('../config/symbini.php');
header('Content-Type: text/html; charset=' . $CHARSET);
?>
<html>
	<head>
		<title>Sample Guidelines and Policies</title>
		<?php
		include_once($SERVER_ROOT.'/includes/head.php');
		?>
	</head>
	<body>
		<!-- This is inner text! -->
		<div id="innertext">
			<h1>Sample Guidelines and Policies</h1>
				<a href="https://www.neonscience.org/data-samples/guidelines-policies">From Data Guidelines and Policies</a>
				<p>NEON is committed to providing data of high value to the ecological research community by meeting the <a href="https://www.nature.com/articles/sdata201618">FAIR data principles</a>. These principles recommend that data be:</p>
				<ul>
					<li><strong>Findable</strong>, through globally unique persistent identifiers and rich metadata which is indexed in a searchable resource.</li>
					<li><strong>Accessible</strong>, through standardized communication protocols. Metadata should be preserved even when data are no longer available.</li>
					<li><strong>Interoperable</strong>, through the use of broadly accessible language with shared vocabularies and qualified references to other metadata.</li>
					<li><strong>Reusable</strong>, through prescribed data usage criteria, documentation of provenance, and defined domain-relevant community standards.</li>
				</ul>
				<p>To show our commitment to FAIR, we are a <a href="https://copdess.org/enabling-fair-data-project/commitment-statement-in-the-earth-space-and-environmental-sciences/signatories/">COPDESS signatory for the Enabling FAIR Data project,</a> as a data repository and research infrastructure.</p>
				<p>We highly encourage practices that continue the enablement of FAIR, including acknowledging and citing sources of data, samples, and documentation as well as preserving and openly publishing research inputs, workflows, and outputs so that they may be discovered and used by others.</p>
			<div id="biorepo-guidelines-content"></div>
		</div>
	</body>
</html>