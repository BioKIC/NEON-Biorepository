<?php
include_once('../config/symbini.php');
header('Content-Type: text/html; charset=' . $CHARSET);
?>
<html>
	<head>
		<title>About Samples</title>
		<?php
		include_once($SERVER_ROOT.'/includes/head.php');
		?>
	</head>
	
	<script>
		window.onload = function() {
			function updateElementWidth() {	
				// hero image
				var neonPageContent = document.querySelector('div[data-selenium="neon-page.content"]');
				var neonPageContentWidth = neonPageContent.offsetWidth;
                var computedStyle = window.getComputedStyle(neonPageContent);
                var leftPadding = parseFloat(computedStyle.getPropertyValue('padding-left'));
				
				var muiContainer = document.querySelector('div.MuiContainer-root');
				var muiContainerStyle = window.getComputedStyle(muiContainer);
				var muiContainerRightMargin = parseFloat(muiContainerStyle.marginRight);
	
				var neonPageContentStyle = window.getComputedStyle(neonPageContent);
				var neonPageContentpaddingLeft = parseFloat(neonPageContentStyle.paddingLeft);
				
                var innerTextDiv = document.getElementById('innertext');
                var computedStyle = window.getComputedStyle(innerTextDiv);
                var leftMargin = parseFloat(computedStyle.getPropertyValue('margin-left'));
				
				document.getElementById('heroimage-div').style.width = (neonPageContentWidth + muiContainerRightMargin) + 'px';
				document.getElementById('heroimage-div').style.right = (leftPadding + leftMargin) + 'px';
			}
			
			var heroDiv = document.getElementById('heroimage-div');
			if (heroDiv) {
				// Update the width on initial load
				updateElementWidth();
			
				// Update the width on window resize
				let resizeTimeout;
				window.addEventListener('resize', function () {
				  clearTimeout(resizeTimeout);
				  resizeTimeout = setTimeout(updateElementWidth, 50);
				});
			}
		
			
		  document.getElementById("downloadButton").addEventListener("click", function() {
			fetch("https://data.neonscience.org/api/v0/samples/supportedClasses")
			  .then(response => response.json())
			  .then(data => {
				const entries = data.data.entries;
		
				// Convert the JSON entries to CSV format
				const csvData = [];
				const headers = ["key", "value"];
				csvData.push(headers.join(","));
		
				entries.forEach(entry => {
				  csvData.push([entry.key, entry.value].join(","));
				});
		
				// Create a CSV blob
				const blob = new Blob([csvData.join("\n")], { type: "text/csv" });
				const url = URL.createObjectURL(blob);
		
				// Create a link to download the CSV file
				const a = document.createElement("a");
				a.href = url;
				a.download = "sample_classes.csv";
				document.body.appendChild(a);
				a.click();
				document.body.removeChild(a);
			  })
			  .catch(error => console.error('Error fetching or processing the data:', error));
		  });
		}
	</script>

	<style>
	  #heroimage-div {
		position: relative;
		right: 74px;
	  }
	
	  #heroimage-div::after {
		content: '';
		position: absolute;
		background-image: url('<?php echo $CLIENT_ROOT . '/images/card-images/white-border.png'; ?>');
		background-position: 50% 0;
		background-repeat: repeat no-repeat;
		bottom: -30px;
		height: 60px;
		width: 100%;
		left: 0;
	  }
	</style>
	
	<body>
		<!-- This is inner text! -->
		<div id="innertext">
			<h1>About Samples</h1>
			</br>
			<div id = "heroimage-div">
				<img src="<?php echo $CLIENT_ROOT . '/images/card-images/20240820-by betancourt - sophia holds calosoma scrutator-29.jpg'; ?>" style="max-width:100%" alt="ASU biorepository" loading="lazy">
			</div>
			<p>NEON has been collecting specimens as part of its sampling program since 2012. Certain samples are earmarked for immediate curation at an archival institution, while others are sent to analytical facilities for chemical, taxonomic or genetic analysis. Where possible, the downstream byproducts of these analyses are archived in lieu of the original sample (e.g., when ear tissue from small mammal collections are sent for genetic analysis, any surplus genomic extracts are preserved at an archive facility). In 2018, NEON began construction of the NEON Biorepository – a facility intended to archive most specimens curated by the NEON program - at the Arizona State University Biocollections in Tempe, AZ. The NEON Biorepository publishes occurrence records for every archived sample and specimen, as well as additional value-added sample-associated data not present in NEON data products in the sample portal and makes these samples available for loan.</p>
			<h2>Sample Organization</h2>
			<div id="biorepo-aboutsamples-content"></div>
			<h2>Sample Processing</h2>
			<p>Providing high-quality, richly contextualized samples is a key component of NEON’s mission and allows for greater understanding of complex ecological processes at local, regional and continental scales. Each <a href="https://www.neonscience.org/data-collection/protocols-standardized-methods">protocol</a> describes field collection of samples. Some samples are immediately curated for archive at the NEON Biorepository and are not subject to direct analysis by NEON staff. Other samples are collected as a target for a particular analysis (e.g., chemical composition, taxonomic analysis, genetic analysis). Where possible, NEON strives to archive the downstream products of these analyses (e.g., the NEON Biorepository archives any remaining genomic DNA after small mammal ear tissue is processed for <a href="https://data.neonscience.org/data-products/DP1.10076.001">DNA sequence analysis</a>). Researchers interested in intermediate sample products or desiring additional sample collection can submit a NEON Research Support Services <a href="https://www.neonscience.org/resources/research-support">request</a>.</p>
			<h2>Sample Identification and Labeling</h2>
			<p>Every sample is identified in the NEON database with a unique primary identifier. All samples are designated a <b>Sample Tag (sampleID)</b> and/or <b>Barcode (sampleCode)</b> upon collection and a <b>Catalog Number (archiveGuid)</b> upon physical accession at the Biorepository. Each sample is also designated a <b>Sample Class (sampleClass)</b>, describing the sample and stored in a Collection within the Biorepository, which roughly equates to sample types. <a href="https://data.neonscience.org/data-products/explore">NEON data products</a> that include sampling will often record sampleIDs in their data tables. Within each data product download, an accompanying table that describes the products' variables will help you identify the sampleID variable names and the data table(s) in which they appear. All samples in the NEON Biorepository can be searched by sampleID, catalog number or barcode through the <a href="../neon/search/index.php">Sample Search</a> page.</p>
			<p>A <b>Sample Tag (sampleID)</b> is the identifier for the sample and contains as much information as is required to differentiate specimens within the same sample class; these identifiers may contain information about the sample encoded in the label, including the site or plot of collection, the date or time of collection, the taxon or sex (as applicable), or a unique number (e.g., tag number on a small mammal). As the observatory has moved into operations, written labels have been supplemented and/or replaced by barcodes.</p>
			<p>Upon arrival and check in at the NEON Biorepository, they are assigned a <b>Catalog Number (archiveGUID)</b>. The Catalog Number is shorter, globally unique across all samples, and easier to manage in large collections. It ensures consistency, as sampleIDs can vary in structure and length based on sample type or collection details. Using a uniform catalog number streamlines sample tracking and referencing across systems, simplifying specimen management and ensuring accurate records as samples are accessed or loaned for research.</p>
			<p><b>Sample Classes (sampleClass)</b> roughly equate to types of samples, such that carabid specimens mounted on pins (sample class: bet_IDandpinning_in.individualID) have a different class than mounted mosquito specimens (mos_identification_in.individualIDList). The first part of the sample class name identifies the data table that is input into NEON's database, e.g., bet = beetle, IDandpinning = identification and pinning activity, in = ingest. The second part of the sample class name identifies the specific field in the table that holds identifiers. New sample classes are often assigned as an originating field-collected sample goes through various processing steps, so not all sample classes correspond to samples archived at the Biorepository.</p>
			<!--<a class="link--button link--arrow" href="/neon/portal/misc/samplerequest.php">Download current list of supported sample classes-->
			<!--	<svg class="MuiSvgIcon-root MuiSvgIcon-fontSizeSmall" focusable="false" viewBox="0 0 24 24" aria-hidden="true" style="margin-left: 8px;">-->
			<!--		<path d="M19 12v7H5v-7H3v7c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2v-7h-2zm-6 .67l2.59-2.58L17 11.5l-5 5-5-5 1.41-1.41L11 12.67V3h2z"></path>-->
			<!--	</svg>-->
			<!--</a>-->
			<button class="MuiButtonBase-root MuiButton-root MuiButton-outlined MuiButton-outlinedPrimary" tabindex="0" type="button" data-selenium="download-sample-classes-button" id="downloadButton">
				<span class="MuiButton-label" style="font-weight:600">Download current list of supported sample classes
					<svg class="MuiSvgIcon-root MuiSvgIcon-fontSizeSmall" focusable="false" viewBox="0 0 24 24" aria-hidden="true" style="margin-left: 8px;">
						<path d="M19 12v7H5v-7H3v7c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2v-7h-2zm-6 .67l2.59-2.58L17 11.5l-5 5-5-5 1.41-1.41L11 12.67V3h2z"></path>
					</svg>
				</span>
				<span class="MuiTouchRipple-root"></span>
			</button>
			<p><b>Barcode (sampleCode)</b> labels were first used on NEON samples in 2017 on a select number of NEON specimens and expanded to most NEON sample types in subsequent years. NEON uses cryo-compatible barcode labels for specimens stored in -20 to -196 ˚C and non-cryo barcode labels for the remaining samples. The non-cryo labels are robust to exposure in solvents (particularly ethanol and water) and are optimized to hold up to field conditions. In the NEON Processed Data Repository (PDR), barcodes cannot be duplicated – as in, two separate/different samples cannot be affiliated with the same barcode. This enables NEON to leverage barcode tracking for duplicate checking in the database.</p>
			
		</div>
	</body>
</html>


