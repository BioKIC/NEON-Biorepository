<?php
include_once('../config/symbini.php');
header('Content-Type: text/html; charset=' . $CHARSET);
?>
<html>
	<head>
	<title>Requesting Samples</title>
	<?php include_once($SERVER_ROOT . '/includes/head.php'); ?>
	<style>
		article { margin: 2rem 0; }

		h1 {
			font-size: 2.5rem !important;
		}
		
	</style>
	</head>
	<body>
		<!-- This is inner text! -->
		<div id="innertext">
			<article>
				<h1 style="text-align:center;">Requesting Samples</h1>
	
				<p>To request samples or learn more about what samples are available for use, you may either browse the <a href="https://biorepo.neonscience.org/portal/collections/misc/browsecollprofiles.php" target="_blank"">Sample Type Browser</a> or <a href="https://biorepo.neonscience.org/portal/neon/search/index.php" target="_blank">NEON Biorepository Sample Portal</a> and initiate inquiries by <a href="https://www.neonscience.org/about/contact-neon-biorepository" target="_blank">contacting us</a> directly. We will follow up within five business days to assist you in identifying appropriate samples and submitting a formal Sample Use Request.</p>
				
				<a class="link--button link--arrow" href="https://www.neonscience.org/about/contact-neon-biorepository" style="margin-top: 25px;margin-bottom: 25px;">Contact Us to Request Samples <svg width="13px" height="10px" viewBox="0 0 13 10" xmlns="http://www.w3.org/2000/svg"><g class="chevronGroup" fill-rule="evenodd" stroke-linecap="round" stroke-linejoin="round" stroke="#0073CF" stroke-width="2" transform="translate(1.000000, 1.000000)"><path d="M11,4 L7,8" class="bottom"></path><path d="M11,4 L7,0" class="top"></path><path d="M11,4 L0,4" class="line"></path></g></svg></a>
	
				<p>All requests are evaluated via the <a href="sampleguidelines.php#sample-use-approval-process">Sample Use Approval Process</a>, subject to the <a href="sampleguidelines.php#sample-use-policy">Sample Use Policy</a>. <b><i>All requests require a mutually developed and signed Sample Use Agreement prior to sample processing and shipment or access.</i></b> Researchers should allow approximately two weeks for development and finalization of the Sample Use Agreement.</p>
	
				<p>The NEON Biorepository aims to fulfill requests of fewer than 100 samples that require no subsampling or additional processing within four weeks of signing a Sample Use Agreement and without any charge. Note that processing times can be much longer when hiring additional NEON Biorepository personnel is required.</p>
	
				<p>Funding is not required to request or access samples. However, requests requiring substantial effort to fulfill may require funding or service fees. These costs will be estimated by the NEON Biorepository based on the type of request, complexity, and amount of support required. Researchers are encouraged to visit the NEON Biorepository to access samples, and we will provide working space, sample access, and our expertise free of charge.</p>
				
				<img src="images/loan_process.png" alt="Sample loan process" style="max-width: 100%; height: auto; margin: 1rem 0;">
			</article>
		</div>
	</body>
</html>