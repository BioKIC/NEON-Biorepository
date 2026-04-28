<?php
include_once('../config/symbini.php');
header("Content-Type: text/html; charset=" . $CHARSET);
?>
<html>

<head>
	<title>Sample Use Procedures and Requirements</title>
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
			margin-bottom: .2rem;
		}
	</style>
</head>

<body>

<div id="innertext">
	<h1 style="text-align:center;">Sample Use Procedures and Requirements</h1>

	<article>
		<p>NEON Biorepository samples and specimens and their associated data are available for a wide variety of research and educational purposes. Anyone interested in requesting samples may do so. Most requests are fulfilled at no cost; however, unusually large requests or those requiring substantial additional processing may incur fees. You can browse NEON samples via the <a href="https://biorepo.neonscience.org/portal/collections/misc/browsecollprofiles.php" target="_blank"">Sample Type Browser</a> or <a href="https://biorepo.neonscience.org/portal/neon/search/index.php" target="_blank">NEON Sample Portal</a> and initiate inquiries about using samples in your work by <a href="https://www.neonscience.org/about/contact-neon-biorepository" target="_blank">contacting us</a> directly.</p>
	</article>
		
	<ol>
		<li><a href="#requesting-samples">Requesting Samples</a></li>
		<li><a href="#funding-proposals-and-letters-of-collaboration-or-support">Funding Proposals and Letters</a></li>
		<li><a href="#visiting-the-neon-biorepository">Visiting the Biorepository</a></li>
		<li><a href="#sample-use-agreement">Sample Use Agreement</a></li>
		<li><a href="#sample-use-policy">Sample Use Policy</a></li>
		<li><a href="#sample-use-approval-process">Sample Use Approval Process</a></li>
		<li><a href="#citations">Biorepository Citation Requirements</a></li>
	</ol>
	<hr>

		<article>
			<h2 class="anchor" id="requesting-samples">Requesting Samples</h2>
			
			<img src="images/loan_process.png" alt="Sample loan process" style="max-width: 100%; height: auto; margin: 1rem 0;">

			<p>To request samples or learn more about what samples are available for use, you may either browse the <a href="https://biorepo.neonscience.org/portal/collections/misc/browsecollprofiles.php" target="_blank"">Sample Type Browser</a> or <a href="https://biorepo.neonscience.org/portal/neon/search/index.php" target="_blank">NEON Biorepository Sample Portal</a> and initiate inquiries by <a href="https://www.neonscience.org/about/contact-neon-biorepository" target="_blank">contacting us</a> directly. We will follow up within five business days to assist you in identifying appropriate samples and submitting a <a href="https://asu.co1.qualtrics.com/jfe/form/SV_bfPgKtTfHTyzffg" target="_blank">Sample Loan Request</a>.</p>

			<p>All requests are evaluated via the <a href="#sample-use-approval-process">Sample Use Approval Process</a>, subject to the <a href="#sample-use-policy">Sample Use Policy</a>. <b><i>All requests require a mutually developed and signed Sample Use Agreement prior to sample processing and shipment or access.</i></b> Researchers should allow approximately two weeks for development and finalization of the Sample Use Agreement. Significantly more time may be required if the request may involve hiring additional personnel.</p>

			<p>The NEON Biorepository aims to fulfill requests of fewer than 100 samples that require no subsampling or additional processing within four weeks of signing a Sample Use Agreement and without any charge. Note that processing times can be much longer when hiring additional personnel is required.</p>

			<p>Funding is not required to request or access samples. However, requests requiring substantial effort to fulfill may require funding or service fees. These costs will be estimated by the NEON Biorepository based on the type of request, complexity, and amount of support required. Researchers may visit the NEON Biorepository to access samples, and we will provide working space, sample access, and our expertise free of charge.</p>
		</article>

		<article>
			<h2 class="anchor" id="funding-proposals-and-letters-of-collaboration-or-support">Funding Proposals and Letters of Collaboration or Support</h2>

			<p>Although funding is not required to use NEON samples, please <a href="https://www.neonscience.org/about/contact-neon-biorepository" target="_blank" rel="noopener noreferrer">contact us</a> at least two weeks in advance of internal deadlines for submitting any grant proposals involving NEON samples. Note that significantly more time may be required for proposed work that would involve hiring additional personnel. The NEON Biorepository requires this time in order to:</p>

			<ul>
				<li>Evaluate whether available samples are suitable for the proposed work</li>
				<li>Ensure that levels of destructive and consumptive sample use can be supported (See <a href="#sample-use-approval-process">Sample Use Approval Process</a>)</li>
				<li>Prevent scenarios in which multiple researchers are funded to use the same samples (Note: The NEON Biorepository can only guarantee the availability of samples for a six month period.)</li>
				<li>Provide quotes for service fees, if applicable</li>
			</ul>

			<p>For programs that allow the submission of letters of support or collaboration, researchers should obtain these from the NEON Biorepository directly if NEON samples will be used in the proposed research. Letters regarding samples are considered separate from those provided by <a href="https://www.neonscience.org/resources/research-support" target="_blank">NEON Research Support Services</a>.</p>
		</article>

		<article>
			<h2 class="anchor" id="visiting-the-neon-biorepository">Visiting the NEON Biorepository</h2>

			<p>The NEON Biorepository is located alongside the Arizona State University Biocollections in Tempe, AZ, USA. We welcome visitors interested in accessing, analyzing, borrowing, or learning more about samples on-site. We do not require bench fees. Please <a href="https://www.neonscience.org/about/contact-neon-biorepository" target="_blank" rel="noopener noreferrer">contact us</a> to arrange a visit.</p>
		</article>

		<article>
			<h2 class="anchor" id="sample-use-agreement">Sample Use Agreement</h2>

			<p><b><i>All sample requests require a mutually developed and signed Sample Use Agreement <a href="documents/sampleUseAgreement.pdf" target="_blank" rel="noopener noreferrer">(example template)</a> between the requesting user and the NEON Biorepository prior to sample processing and shipment or access.</i></b> The Sample Use Agreement documents the terms under which samples are provided and ensures that both parties have a shared understanding of the approved research and responsibilities associated with sample use. Each Sample Use Agreement is developed on a case-by-case basis.</p>

			<p>While the exact contents of each agreement vary, Sample Use Agreements generally include:</p>

			<ul>
				<li>the list of approved samples and the amount to be provided for the project</li>
				<li>the approved research scope and permitted sample uses (e.g., non-invasive, invasive, consumptive, or destructive)</li>
				<li>the loan period and return requirements for samples or derived materials</li>
				<li>requirements for receiving, handling, storing, and returning samples during the loan period</li>
				<li>requirements for submission of specimen-derived data (digital and physical) and sharing of publications</li>
				<li>instructions for citation of samples and associated data</li>
				<li>a download link for retrieving the most current sample-associated data for the approved sample list</li>
				<li>any special provisions regarding licensing, embargo periods, or data sharing</li>
			</ul>

			<p>Additional conditions or requirements may be included in the Sample Use Agreement depending on the nature of the requested samples or the proposed research.</p>
		</article>
		
		<article>
			<h2 class="anchor" id="sample-use-policy">Sample Use Policy</h2>
		
			<p>
			The NEON Biorepository Sample Use Policy outlines requirements for use, handling, return, and reporting of samples. 
			<a href="samplepolicy.php">Read the full Sample Use Policy</a>.
			</p>
		</article>

		<article>
			<h2 class="anchor" id="sample-use-approval-process">Sample Use Approval Process</h2>

			<p>After submitting an initial inquiry, we will reach out within 5 business days to begin co-developing a sample list and Sample Use Agreement. Please allow at least two weeks to complete the approval process. More time will be required if it is necessary to hire additional personnel.</p>

			<p>Sample uses can be non-invasive, invasive, consumptive, or destructive. Non-invasive use does not materially affect the condition or future availability of a sample; invasive alters a sample in a manner that may affect subsequent applications; consumptive use depletes a portion of a sample; and destructive use renders a sample unavailable for further use.</p>

			<p>While any type of use is permissible, requests involving substantial destructive or consumptive use require additional review as a part of the approval process. All requests are evaluated on a case-by-case basis; however, each sample type has guidelines regarding the amount of destructive and consumptive use that can be supported for a single project and the minimum number of samples that must be retained for long term archive. Requests that involve destructive or significant consumptive use exceeding those guidelines require strong justification and may be subject to external review.</p>

			<p>Other considerations relevant to sample use approval include: species rarity; physical condition of a requested specimen; suitability of requested samples to proposed application; significance relative to NEON’s <a href="https://www.neonscience.org/about/visionandmanagement" target="_blank" rel="noopener noreferrer">mission</a> to enable continental-scale ecology; prior adherence to the NEON Sample Use Policy; and U.S. National Park Service policies, where relevant.</p>

			<p>In cases where overlapping requests require destructive, consumptive, or invasive use of samples, priority will be research sponsored by the NSF Biological Sciences Directorate, followed by other NSF-sponsored research and finally non-NSF funded research, outreach, or education uses.</p>

			<p>Loans are typically approved for 6-12 months. Extensions requested before the current loan period expires will generally be approved except when the samples have been requested by other researchers. Failure to return samples within the approved loan period or to maintain communication regarding their status may affect eligibility for future sample requests.</p>
		</article>
		
		<article>
			<h2 class="anchor" id="citations">Biorepository Citation Requirements</h2>

			<p>
			Use of NEON Biorepository samples or data must follow established citation and acknowledgment requirements, including acknowledgment language and citation of data, physical samples, associated media, and derived datasets. See the <a href="cite.php">Acknowledging and Citing the NEON Biorepository</a> page for full details.
			</p>
		</article>
		
		<div style="margin-top: 70px;">
			<p><em>Last updated April 23, 2026</em></p>	
		</div>
	</div>

</body>

</html>