<?php
include_once('../config/symbini.php');
header("Content-Type: text/html; charset=" . $CHARSET);
?>
<html>

<head>
	<title>Sample Use Policy</title>
	<?php include_once($SERVER_ROOT . '/includes/head.php'); ?>
	<style>
		article { margin: 2rem 0; }

		h1 {
			font-size: 2.5rem !important;
		}
		
		h4 {
			font-size: 1.1rem !important;
		}
		
		.anchor {
			font-size: 1.675rem !important;
			scroll-margin-top: 150px;
		}
		
		li {
			margin-bottom: 1rem;
		}
	</style>
</head>

<body>

<div id="innertext">
	<h1 style="text-align:center;">Sample Use Policy</h1>

		<article>

			<p>The NEON Biorepository reserves the right to deny future sample requests from researchers who do not adhere to this Sample Use Policy or to the terms outlined in a signed Sample Use Agreement. <b><i>All requests require a mutually developed and signed <a href="sampleguidelines.php#sample-use-agreement">Sample Use Agreement</a> prior to sample processing and shipment.</i></b></p>

			<ul>
				<li><b>Samples may only be used for the research purposes described in the approved Sample Use Agreement.</b> Significant changes to the scope, methods, or objectives of the approved research must be communicated to and approved by the NEON Biorepository prior to implementation.</li>

				<li><b>Destructive, consumptive, and invasive uses of samples are permitted only when previously agreed upon</b> in writing as part of the Sample Use Agreement (see also <a href="sampleguidelines.php#sample-use-approval-process">Sample Use Approval Process</a>).</li>

				<li><b>Researchers must maintain timely communication with the NEON Biorepository regarding the status, condition, and use of loaned samples, as well as the progress of the approved research.</b> Failure to maintain communication may affect eligibility for future sample requests.</li>

				<li><b>All loaned materials must be returned to the NEON Biorepository within the approved loan period (typically 6–12 months)</b>, except in cases where no viable sample remains following approved use. Extensions may be granted in writing, if no other requests for the samples exist. In some cases, material remaining after consumptive or destructive use (e.g., DNA extracts or individuals removed from bulk samples) must also be returned for archive. The Sample Use Agreement will specify labeling and archival requirements for these materials. Unless otherwise arranged in writing, users will be required to cover sample return costs. Failure to return loans may affect eligibility for future sample requests.</li>

				<li><b>Researchers using NEON Biorepository samples must provide all resulting sample-associated data to NEON for publication via the <a href="https://biorepo.neonscience.org/portal/neon/search/index.php" target="_blank" rel="noopener noreferrer">NEON Sample Portal</a> for public use within 2 years of sample receipt or upon publication, whichever comes first.</b> Special exceptions regarding data embargos, sensitive data, or extensions may be arranged in writing. This data includes, but is not limited to, species determinations, images and other media files, and links to externally-hosted genetic and genomic sequence data. Instructions for providing these data to the NEON Biorepository will be outlined in the Sample Use Agreement. Guidelines for attribution and licensing of researcher-contributed data default to those listed in the Citation Requirements for <a href="https://biorepo.neonscience.org/portal/misc/cite.php#h.3" target="_blank" rel="noopener noreferrer">Data-Only Use</a> and <a href= "biorepo.org/portal/misc.cite.php#h.5" target="_blank" rel="noopener noreferrer" >Using and Citing Sample Images</a> policies. Future users of requestor-derived data will be encouraged to cite the original publications, which will be linked to associated sample records within the NEON Biorepository Sample Portal.</li>

				<li><b>Researchers must agree to share all publications arising from sample use</b> with the NEON Biorepository and ensure proper citation in accordance with NEON Biorepository citation guidelines. Citations and links to these publications will be added to all associated sample records in the NEON Sample Portal.</li>

				<li><b>Upon receipt of samples, the researcher must review the shipment and sign and return a <a href="documents/sampleReceiptConfirmation.pdf" target="_blank" rel="noopener noreferrer">Sample Receipt Confirmation</a></b> acknowledging receipt of the materials and their condition. The researcher’s institution is responsible for proper handling, storage, and use of all samples while in their possession and for ensuring compliance with the Sample Use Agreement, including maintaining appropriate storage conditions and preventing loss or contamination. The NEON Biorepository must be notified promptly if samples are lost, damaged, or compromised while in the researcher’s possession.</li>
			</ul>
		</article>
		
		<div style="margin-top: 70px;">
			<p><em>Last updated April 23, 2026</em></p>	
		</div>
	</div>

</body>

</html>