<?php
include_once('../config/symbini.php');
header('Content-Type: text/html; charset=' . $CHARSET);
?>
<html>
	<head>
		<title>Sample Services</title>
		<?php
		include_once($SERVER_ROOT.'/includes/head.php');
		?>
	</head>
	<body>
		<!-- This is inner text! -->
		<div id="innertext">
			<h1>Sample Services</h1>
			<h2>Types of Requests</h2>
			<h3><strong>Loans</strong></h3>
			<ul>
				<li><p><strong>Loans for non-destructive use: </strong>These loans are typically granted for a 6-12 month period; a longer period of time may be accommodated if properly justified. Ordinarily, no more than one-half of a sample or series of samples from a NEON site may be borrowed at any one time for non-destructive use. All loan requests, no matter the quantity, are considered on a case-by-case basis. Loans of endangered taxa, fragile specimens, or samples which are deemed to be in limited supply may be subject to more stringent review. Because the NEON Biorepository is a community resource with finite materials, we ask that requestors only ask for what they need and do not request excess samples.</p></li>
				<li><p><strong>Loans for destructive or consumptive use: </strong>Decisions to grant or not grant permission for destructive/consumptive/invasive sampling will be based on: (1) rarity of the species or sample and its representation within the NEON Biorepository collections; (2) degree of destruction/consumption/invasiveness; (3) physical condition of the specimen(s); (4) significance of the proposed research relative to NEON’s mission to enable continental-scale ecology; and (5) qualifications of the investigators. Any physical material remaining after the analysis will be returned to the NEON Biorepository unless otherwise agreed upon in writing. Because the NEON Biorepository is a community resource with finite materials, we ask that requestors only ask for what they need and do not request excess samples.</p></li>
				<a class="link--button link--arrow" href="<?php echo $CLIENT_ROOT; ?>/misc/samplerequest.php">Sample Loan Request Form</a>
			</ul>
			<h3><strong>Storage</strong></h3>
			<ul>
				<li><p><strong>Archival Storage Request: </strong>NEON Biorepository at Arizona State University offers archival storage for samples collected through <a href="https://www.neonscience.org/resources/research-support" target="_blank">NEON Research Support Services</a> and other research at NEON sites. Archiving typically incurs a cost based on sample quantity and storage needs. Archived samples are published to the NEON Biorepository data portal and made available for future research.
				</p></li>
				<a class="link--button link--arrow" href="<?php echo $CLIENT_ROOT; ?>/misc/samplearchiverequest.php">Sample Archival Request Form</a>
			</ul>
			<h2>Considerations for Approval</h2>
			<p>The internal evaluation and approval of sample requests will focus on technical and logistical criteria as well as scientific justification, particularly for requests for destructive or consumptive use. The latter is not intended to subsume the scientific merit review that may have been conducted by the sponsoring agency; but rather is a means to ensure the highest and best use of this valuable but limited resource, as well as transparency and accountability to the greater research and collections communities.</p>
			<p><a class="link--button link--arrow" href="<?php echo $CLIENT_ROOT; ?>/misc/samplepolicy.php">Read our Sample Use Policy</a></p>
			<div class="align-right">
				<figure>
					<img loading="eager" width="100%" src="../images/home-card-images/2021_04_photo_Handling-Frozen-Samples-Biorepository_jpg.jpg" alt="Biorepository Samples" />
					<div class="field--name-field-caption">
						<p>A technician handles samples at the NEON Biorepository at Arizona State University.</p>
					</div>
				</figure>
			</div>

			<h2>Accessing Samples via the Biorepository</h2>
			<p>Cost of shipping will be handled on a case-by-case basis. At present, the NEON Biorepository foresees retaining the ability to cover shipping costs for small-scale or exploratory studies when no other funding is available. Larger-scale and/or long-term sample shipping needs will typically have to be financed by the corresponding external research team.</p>
			<p>For large or complicated requests of material, researchers will be encouraged to visit the NEON Biorepository, using their own funds, to select specimens for sampling or to study them directly on site when suitable. Removal of material from samples selected by the researcher must be approved by appropriate NEON Biorepository staff. Not only does a personal visit reduce the work required of staff, it allows the investigator to make more precise selections of material to be used, or even accomplish an entire research task on site.</p>
		</div>
	</body>
</html>