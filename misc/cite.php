<?php
include_once('../config/symbini.php');
header("Content-Type: text/html; charset=" . $CHARSET);
?>
<html>

<head>
	<title>Acknowledging and Citing the NEON Biorepository</title>
	<?php
	include_once($SERVER_ROOT . '/includes/head.php');
	?>
	<style>
		
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

		table.citation-table {
			width: 100%;
			border-collapse: collapse;
			margin: 1rem 0;
		}

		table.citation-table th,
		table.citation-table td {
			border: 1px solid #ccc;
			padding: 0.5rem;
			text-align: left;
			vertical-align: top;
		}

		table.citation-table th {
			background: #f5f5f5;
		}

		blockquote {
			font-size: 0.85rem;
			line-height: 1.5;
		}
	</style>
</head>

<body>

	<div id="innertext">
		<h1>Acknowledging and Citing the NEON Biorepository</h1>
		
		<article>
			<h3 class="anchor" id="h.1">Acknowledgement</h3>
			<p>Any publications involving use of NEON samples and specimens should include the following acknowledgment:</p>

			<blockquote>
				"The National Ecological Observatory Network is a program sponsored by the U.S. National Science Foundation and operated under cooperative agreement by Battelle. This material uses specimens and/or samples collected as part of the NEON Program and provided by the NEON Biorepository at Arizona State University."
			</blockquote>
		</article>

		<article>
			<h3 class="anchor" id="h.2">Citing the NEON Biorepository</h3>
			<p>If the sampling scheme, design, or operations of the NEON Biorepository is integral to facilitating a publication, we encourage citation of the following publication:</p>

			<blockquote>
				Thibault KM, Laney CM, Yule KM, Franz NM, Mabee PM. (2023). The US National Ecological Observatory Network and the Global Biodiversity Framework: National research infrastructure with a global reach. Journal of Ecology and Environment. 47:21.
				<a href="https://doi.org/10.5141/jee.23.076" target="_blank" rel="noopener noreferrer">https://doi.org/10.5141/jee.23.076</a>
			</blockquote>
		</article>

		<article>
			<h3 class="anchor" id="h.3">Citation Requirements for Data-Only Use</h3>

			<p>NEON sample data is offered under the Creative Commons Attribution (<a href="https://creativecommons.org/licenses/by/4.0/" target="_blank" rel="noopener noreferrer">CC BY 4.0</a>) license. Attribution is required when you use NEON data, including a link to the license and an indication of any changes.</p>
			
			<p>Records in the <a href="../neon/search/index.php" target="_blank" rel="noopener noreferrer">NEON Biorepository Sample Portal</a> are periodically updated based on ingestion of the most up-to-date NEON data and are, in many cases, supplemented with value-added data obtained from further analysis. Because these records are updated on a rolling basis and are not tied to a specific NEON data release, NEON Biorepository sample data should be considered subject to change and <strong>treated as provisional</strong>. Citations of these data should therefore follow <a href="https://www.neonscience.org/data-samples/guidelines-policies/citing#citing-data" target="_blank" rel="noopener noreferrer">NEON guidance for provisional data</a>, applied to the relevant Biorepository sample types.</p>

			<p>For each sample type used:</p>

			<p><b>For sample data which have been saved to a repository and assigned a DOI:</b></p>
			<blockquote>
				NEON (National Ecological Observatory Network) Biorepository. Mammal Collection (Ear Tissue). Data accessed from
				<a href="https://biorepo.neonscience.org/portal/collections/misc/neoncollprofiles.php?collid=25" target="_blank" rel="noopener noreferrer">https://biorepo.neonscience.org/portal/collections/misc/neoncollprofiles.php?collid=25</a>
				on [DATE ACCESSED]. Licensed under CC BY 4.0 (<a href="https://creativecommons.org/licenses/by/4.0/" target="_blank">https://creativecommons.org/licenses/by/4.0/</a>). Data were filtered and reformatted for analysis. Data archived at [your DOI].
			</blockquote>

			<p><b>For sample data which have not been saved to a repository (not recommended):</b></p>
			<blockquote>
				NEON (National Ecological Observatory Network) Biorepository. Mammal Collection (Ear Tissue). Data accessed from
				<a href="https://biorepo.neonscience.org/portal/collections/misc/neoncollprofiles.php?collid=25" target="_blank">https://biorepo.neonscience.org/portal/collections/misc/neoncollprofiles.php?collid=25</a>
				on [DATE ACCESSED]. Licensed under CC BY 4.0 (<a href="https://creativecommons.org/licenses/by/4.0/" target="_blank">https://creativecommons.org/licenses/by/4.0/</a>). No changes were made.
			</blockquote>

			<p>If additional data are obtained directly from the <a href="https://data.neonscience.org/data-products/explore" target="_blank" rel="noopener noreferrer">NEON Data Portal</a> (e.g., via download, API access, or programmatic tools), the relevant NEON Data Product(s) must also be cited in accordance with NEON data citation guidance, including the appropriate release DOI or provisional data citation.</p>
		</article>

		<article>
			<h3 class="anchor" id="h.4">Citation Requirements for Physical Sample Use</h3>
			<p>In addition to following the general <a href="https://www.neonscience.org/data-samples/guidelines-policies/publishing-research-outputs" target="_blank" rel="noopener noreferrer">NEON Guidelines for Publishing Research Outputs</a>, publications involving samples and specimens from the NEON Biorepository must include both physical sample citations <b>and</b> associated sample data citations (see sections below):</p>

			<h4>Physical Sample Citations</h4>
			<p>NEON samples and specimens involved in published research must be listed in either the main text or supplemental information of any relevant publication. A complete list of samples provided will be included in the Sample Use Agreement to support accurate physical sample citations (see example table below).</p>
			<p>The occurrenceID or full IGSN ID should be used to refer to samples (e.g., <a href="https://doi.org/10.58052/NEON0D0YI" target="_blank" rel="noopener noreferrer">https://doi.org/10.58052/NEON0D0YI</a>, <a href="https://doi.org/10.58052/NEON0D0YI" target="_blank" rel="noopener noreferrer">igsn:10.58052/NEON0D0YI</a> or <a href="https://doi.org/10.58052/NEON0D0YI" target="_blank" rel="noopener noreferrer">igsn:NEON0D0YI</a>) in manuscript text as well as in any data repositories, such as GenBank. In cases where IGSN ID use is already clearly denoted, the preceding igsn: tag may be excluded. Where possible, all representations of IGSN IDs should be hyperlinked with the IGSN ID’s complete DOI link. Authors are encouraged to follow established conventions for formatting and displaying IGSN IDs as described by <a href="https://ev.igsn.org/resources/using-igsn-in-publications" target="_blank" rel="noopener noreferrer">IGSN e.V.</a> and <a href="https://support.datacite.org/docs/displaying-igsn-ids" target="_blank" rel="noopener noreferrer">DataCite</a> guidelines. The NEON Biorepository can provide additional guidance on specimen citation conventions.</p>

			<table class="citation-table">
				<thead>
					<tr>
						<th>domain</th>
						<th>stateProvince</th>
						<th>siteID</th>
						<th>sampleID</th>
						<th>IGSN<sup>a</sup></th>
						<th>scientificName</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td rowspan="6">D02</td>
						<td rowspan="3">Maryland</td>
						<td rowspan="3">SERC</td>
						<td>SERC.2018.30.AEDALB.F.A.01</td>
						<td><a href="https://doi.org/10.58052/NEON03MVF" target="_blank" rel="noopener noreferrer">NEON03MVF</a></td>
						<td><i>Aedes albopictus</i></td>
					</tr>
					<tr>
						<td>SERC.2018.38.AEDALB.F.A.01</td>
						<td><a href="https://doi.org/10.58052/NEON03MVZ" target="_blank" rel="noopener noreferrer">NEON03MVZ</a></td>
						<td><i>Aedes albopictus</i></td>
					</tr>
					<tr>
						<td>SERC.2018.45.AEDVEX.F.A.01</td>
						<td><a href="https://doi.org/10.58052/NEON03BG5" target="_blank" rel="noopener noreferrer">NEON03BG5</a></td>
						<td><i>Aedes vexans</i></td>
					</tr>
					<tr>
						<td rowspan="3">Virginia</td>
						<td rowspan="3">SCBI</td>
						<td>SCBI.2018.29.AEDVEX.F.A.01</td>
						<td><a href="https://doi.org/10.58052/NEON03MTN" target="_blank" rel="noopener noreferrer">NEON03MTN</a></td>
						<td><i>Aedes vexans</i></td>
					</tr>
					<tr>
						<td>SCBI.2018.41.AEDVEX.F.A.01</td>
						<td><a href="https://doi.org/10.58052/NEON03MUN" target="_blank" rel="noopener noreferrer">NEON03MUN</a></td>
						<td><i>Aedes vexans</i></td>
					</tr>
					<tr>
						<td>SCBI.2018.44.AEDVEX.F.A.01</td>
						<td><a href="https://doi.org/10.58052/NEON03BIT" target="_blank" rel="noopener noreferrer">NEON03BIT</a></td>
						<td><i>Aedes vexans</i></td>
					</tr>
			
					<tr>
						<td rowspan="3">D07</td>
						<td rowspan="3">Tennessee</td>
						<td rowspan="3">ORNL</td>
						<td>ORNL.2019.20.AEDALB.F.A.01</td>
						<td><a href="https://doi.org/10.58052/NEON03HQN" target="_blank" rel="noopener noreferrer">NEON03HQN</a></td>
						<td><i>Aedes albopictus</i></td>
					</tr>
					<tr>
						<td>ORNL.2019.28.AEDALB.F.A.01</td>
						<td><a href="https://doi.org/10.58052/NEON03HXL" target="_blank" rel="noopener noreferrer">NEON03HXL</a></td>
						<td><i>Aedes albopictus</i></td>
					</tr>
					<tr>
						<td>ORNL.2019.24.AEDVEX.F.A.01</td>
						<td><a href="https://doi.org/10.58052/NEON03HV3" target="_blank" rel="noopener noreferrer">NEON03HV3</a></td>
						<td><i>Aedes vexans</i></td>
					</tr>
			
					<tr>
						<td rowspan="4">D08</td>
						<td rowspan="4">Alabama</td>
						<td rowspan="4">TALL</td>
						<td>TALL.2018.42.AEDALB.F.A.01</td>
						<td><a href="https://doi.org/10.58052/NEON03ALF" target="_blank" rel="noopener noreferrer">NEON03ALF</a></td>
						<td><i>Aedes albopictus</i></td>
					</tr>
					<tr>
						<td>TALL.2019.37.AEDVEX.F.A.01</td>
						<td><a href="https://doi.org/10.58052/NEON03I0Y" target="_blank" rel="noopener noreferrer">NEON03I0Y</a></td>
						<td><i>Aedes vexans</i></td>
					</tr>
					<tr>
						<td>TALL.2019.39.AEDVEX.F.A.01</td>
						<td><a href="https://doi.org/10.58052/NEON03I3K" target="_blank" rel="noopener noreferrer">NEON03I3K</a></td>
						<td><i>Aedes vexans</i></td>
					</tr>
					<tr>
						<td>TALL.2019.43.AEDVEX.F.A.01</td>
						<td><a href="https://doi.org/10.58052/NEON03I6O" target="_blank" rel="noopener noreferrer">NEON03I6O</a></td>
						<td><i>Aedes vexans</i></td>
					</tr>
			
					<tr>
						<td rowspan="3">D11</td>
						<td rowspan="3">Texas</td>
						<td rowspan="3">CLBJ</td>
						<td>CLBJ.2019.26.AEDALB.F.A.01</td>
						<td><a href="https://doi.org/10.58052/NEON03HF0" target="_blank" rel="noopener noreferrer">NEON03HF0</a></td>
						<td><i>Aedes albopictus</i></td>
					</tr>
					<tr>
						<td>CLBJ.2019.36.AEDALB.F.A.01</td>
						<td><a href="https://doi.org/10.58052/NEON03HME" target="_blank" rel="noopener noreferrer">NEON03HME</a></td>
						<td><i>Aedes albopictus</i></td>
					</tr>
					<tr>
						<td>CLBJ.2019.38.AEDALB.F.A.01</td>
						<td><a href="https://doi.org/10.58052/NEON03HOU" target="_blank" rel="noopener noreferrer">NEON03HOU</a></td>
						<td><i>Aedes albopictus</i></td>
					</tr>
				</tbody>
			</table>
			
						<p><sup>a.</sup> International Geo Sample Number, <a href="https://www.geosamples.org" target="_blank" rel="noopener noreferrer">www.geosamples.org</a></p>

			<h4>Sample Data Citations</h4>
			<p>Associated sample data must be cited following the <a href="#h.3">Citation Requirements for Data-Only Use</a> as described above.</p>
			<p>To support accurate sample data, the Sample Use Agreement will include a unique download link that will allow users to retrieve the most current associated data for their requested samples provided through the NEON Biorepository Sample Portal.</p>
			<p>If additional data are obtained directly from the <a href="https://data.neonscience.org/data-products/explore">NEON Data Portal</a> (e.g., via download, API access, or programmatic tools), the relevant NEON Data Product(s) must also be cited in accordance with <a href="https://www.neonscience.org/data-samples/guidelines-policies/citing#citing-data">NEON data citation guidance</a>.</p>
		</article>

		<article>
			<h3 class="anchor" id="h.5">Using and Citing Sample Images</h3>
			<p>Unless stated otherwise, images are made available under the Creative Commons Attribution-ShareAlike (<a href="https://creativecommons.org/licenses/by-sa/4.0/">CC BY-SA</a>) license. Users are allowed to copy, transmit, reuse, and/or adapt content, as long as attribution regarding the source of the content is made. If the content is altered, transformed, or enhanced, it may be re-distributed only under the same or similar license by which it was acquired.</p>

			<p>Use the following template to cite images accessed via the NEON Biorepository Data Portal:</p>

			<blockquote>
				<b>Template:</b> [Creator (if known)], NEON (National Ecological Observatory Network) Biorepository. Accessed via NEON (National Ecological Observatory Network) Biorepository Sample Portal [DATE ACCESSED]. [IMAGE TITLE or FILENAME]. [IMAGE TYPE (e.g., Photograph)]. [URL]
			</blockquote>

			<blockquote>
				<b>Example:</b> Steger, L., NEON (National Ecological Observatory Network) Biorepository. Accessed via NEON (National Ecological Observatory Network) Biorepository Sample Portal [2025-09-23]. B00000021553_1611185823_lg. Photograph.
				<a href="https://biorepo.neonscience.org/imglib/neon/NEON_MAMC-VSS/00000/B00000021553_1611185823_lg.jpg" target="_blank" rel="noopener noreferrer">https://biorepo.neonscience.org/imglib/neon/NEON_MAMC-VSS/00000/B00000021553_1611185823_lg.jpg</a>
			</blockquote>

			<p>A general condition of NEON specimen and sample use is that all resulting media files and associated metadata must be shared with the NEON Biorepository for archive and publication. These images will remain under the ownership of NEON and made available under the Creative Commons Attribution-ShareAlike (CC BY-SA) license, but creators of the image can be credited as requested by the researcher.</p>
		</article>
		
	<div style="margin-top: 70px;">
		<p><em>Last updated April 23, 2026</em></p>	
	</div>
	
	</div>

</body>

</html>