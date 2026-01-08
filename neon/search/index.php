<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT . '/neon/classes/CollectionMetadata.php');
include_once($SERVER_ROOT . '/neon/classes/DatasetsMetadata.php');
header("Content-Type: text/html; charset=" . $CHARSET);

$collData = new CollectionMetadata();
$siteData = new DatasetsMetadata();
?>
<html>

<head>
	<!-- neon edit -->
	<title><?php echo $DEFAULT_TITLE; ?></title>
	<!-- end neon edit -->
	<?php
	include_once($SERVER_ROOT . '/includes/head.php');
	include_once($SERVER_ROOT . '/includes/googleanalytics.php');
	?>
	<link href="<?= $CLIENT_ROOT; ?>/css/jquery-ui.min.css" type="text/css" rel="stylesheet">
	<script src="<?= $CLIENT_ROOT ?>/js/jquery-3.7.1.min.js" type="text/javascript"></script>
	<script src="<?= $CLIENT_ROOT ?>/js/jquery-ui.min.js" type="text/javascript"></script>
	<script src="<?= $CLIENT_ROOT ?>/js/symb/api.taxonomy.taxasuggest.js" type="text/javascript"></script>
	<script>
		const clientRoot = '<?php echo $CLIENT_ROOT; ?>';
	</script>
	<script>
	document.addEventListener('DOMContentLoaded', () => {
	  // expand/collapse groups
		document.querySelectorAll('[data-target], .group-label, .expansion-icon').forEach(el => {
		  el.addEventListener('click', e => {
			e.stopPropagation();
			const li = el.closest('li');
			const ul = li.querySelector('ul');
			const icon = li.querySelector('.expansion-icon');
			if (!ul) return;
		
			const isCollapsed = ul.classList.toggle('collapsed');
			if (icon) icon.textContent = isCollapsed ? 'add_box' : 'indeterminate_check_box';
		  });
		});
	
	  // toggle checkbox when clicking leaf text
	  document.querySelectorAll('.leaf-label').forEach(label => {
		label.addEventListener('click', e => {
		  const checkbox = label.previousElementSibling; // the <input type="checkbox">
		  if (checkbox && checkbox.type === 'checkbox') {
			checkbox.checked = !checkbox.checked;
		  }
		});
	  });
	});
	</script>

	<!-- Search-specific styles -->
	<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
	<link rel="stylesheet" href="../css/app.css?v=02">
	<style>
	.collapsed { 
	  display: none; 
	}
	.group-label { 
	  cursor: pointer; 
	  user-select: none; 
	}
	.group-label:hover { 
	  text-decoration: underline;
	  cursor: pointer; 
	}
	#neon-modal-open,
	.neon-modal-open { 
	  cursor: pointer; 
	  user-select: none; 
	}
	
	.neon-modal-open:hover { 
	  text-decoration: underline;
	  cursor: pointer; 
	}
	.leaf-label { cursor: pointer; user-select: none; }
	.leaf-label:hover { text-decoration: underline; cursor: pointer; }
	
	#neonext-collections-list li > span.group-label {
	  cursor: pointer;
	  text-decoration: none;
	}
	
	#neonext-collections-list li > span.group-label:hover {
	  text-decoration: underline;
	}
	</style>
</head>

<body>
	<?php
	include($SERVER_ROOT . '/includes/header.php');

	//function to render sample type tree
	function renderTree($nodes, $parentId = '') {
		$html = '';
		foreach ($nodes as $node) {
			$cCodeId = 'cl-' . preg_replace('/[^a-z0-9\-]/', '-', strtolower($node['name']));
			$cCodeId = preg_replace('/-+/', '-', $cCodeId);
	
			$html .= "<li>";
	
			// leaf node (collection)
			if (isset($node['collid'])) {
				$collid = $node['collid'];
				$name = htmlspecialchars($node['name']);
	
				$html .= "<input type='checkbox' name='db' value='{$collid}' class='child' data-cat='{$parentId}' data-ccode='{$name}' checked>";
				$html .= "<span class='leaf-label ml-1 child'>{$name}</span>";
				$html .= " <a href='../../collections/misc/neoncollprofiles.php?collid={$collid}' title='View Sample Type Profile'><span class='material-icons' style='color:#565a5c; vertical-align:middle;'>info</span></a>";

			} 
			// group node
			else {
				$name = htmlspecialchars($node['name']);
				$html .= "<input type='checkbox' id='{$cCodeId}' class='all-selector child' data-ccode='{$name}' checked>";
				// wrap label + icon so whole thing is clickable
				$html .= "<span data-target='{$cCodeId}'>
							<span class='material-icons expansion-icon'>add_box</span>
							<span class='group-label'>{$name}</span>
						  </span>";
	
				if (!empty($node['children'])) {
					$html .= "<ul class='collapsed'>";
					$html .= renderTree($node['children'], $cCodeId);
					$html .= "</ul>";
				}
			}
	
			$html .= "</li>";
		}
		return $html;
	}
	?>
	<!-- This is inner text! -->
	<div id="innertext">
		<!-- neon edit -->
		<h1>Biorepository Sample Portal</h1>
		<!-- end neon edit -->
		<div id="error-msgs" class="errors"></div>
		<form id="params-form">
			<!-- Criteria forms -->
			<div class="accordions">
				<!-- Collections -->
				<section>
					<!-- Accordion selector -->
					<input type="checkbox" id="collections" class="accordion-selector" checked=true />
					<!-- Accordion header -->
					<label for="collections" class="accordion-header">Sample Types</label>
					<!-- Accordion content -->
					<div class="content">
						<div id="search-form-colls">
							<!-- Open NEON Collections modal -->
							<div><input id="all-neon-colls-quick" data-chip="All Sample Types" class="all-selector" type="checkbox" checked="true" data-form-id="biorepo-collections-list"><span id="neon-modal-open" class="material-icons expansion-icon">add_box</span><span class="neon-modal-open">NEON Samples at the NEON Biorepository</span></div>
							<!-- External Collections -->
							<div>
								<ul id="neonext-collections-list">
									<li class="Mui"><input id="all-neon-ext" data-chip="Sample Types at Other Repositories" type="checkbox" class="all-selector" data-form-id='neonext-collections-list'><span class="material-icons expansion-icon">add_box</span><span class="group-label">NEON Samples at Other Repositories</span>
									<a href='https://www.neonscience.org/samples/sample-repositories' target='_blank' rel='noopener noreferrer' title='View More Information'><span class='material-icons' style='color:#565a5c; vertical-align:middle;'>info</span></a>
										<?php if ($collsArr = $collData->getCollMetaByCat('Additional NEON Collections')) {
											echo '<ul class="collapsed">';
											foreach ($collsArr as $result) {
												echo "<li class='Mui'>";
												echo "<input type='checkbox' name='db' value='{$result["collid"]}' class='child' data-ccode='{$result["institutioncode"]} {$result["collectioncode"]}'>";
												echo "<span class='leaf-label ml-1 child'>{$result["collectionname"]} </span>";
												echo " <a href='../../collections/misc/neoncollprofiles.php?collid={$result["collid"]}' target='_blank' rel='noopener noreferrer' title='View Collection Profile'><span class='material-icons' style='color:#565a5c; vertical-align:middle;'>info</span></a>";
												echo "</li>";
											}
											echo '</ul>';
										}; ?>
									</li>
								</ul>
							</div>
						</div>
						<p class="Mui">
						  Looking for more about NEON's sample types? Browse the <a href="<?php echo $CLIENT_ROOT . '/collections/misc/browsecollprofiles.php'; ?>" target="_blank" rel="noopener noreferrer">sample type profiles</a> for descriptions, associated data, and contact information.
						</p>
					</div>
					<!-- NEON Biorepository Collections Modal -->
					<div class="modal" id="biorepo-collections-list">
						<div class="modal-content">
							<button id="neon-modal-close" class="btn" style="width:auto !important">Accept and close</button>
							<div id="colls-modal">
								<div>
									<h3>Use the tabs, dropdowns and checkboxes to find and choose a sample type you're interested in.</h3>
									<label class="tab tab-active"><input type="radio" name="collChoice" value="taxonomic-cat" checked="true"> Taxonomic Group</label>
									<label class="tab"><input type="radio" name="collChoice" value="neon-theme"> Protocol</label>
									<label class="tab"><input type="radio" name="collChoice" value="sample-type"> Preservation Method</label>
								</div>
								<!-- By Taxonomic Group -->
								<div id="taxonomic-cat" class="box" style="display: block;">
									<h2>Organized by Taxonomic Group</h2>
									<?php
									// load the JSON
									$jsonFile = '../../neon-react/biorepo_lib/collections-taxonomic.json';
									$jsonData = file_get_contents($jsonFile);
									$dataArr = json_decode($jsonData, true);
									
									// render the full tree
									if ($dataArr) {
										echo '<ul id="collections-list1">';
										echo '  <li>';
										echo '    <input type="checkbox" class="all-selector all-neon-colls" checked>';
										echo '    <span class="material-icons expansion-icon">indeterminate_check_box</span>';
										echo '    <span class="group-label">All Sample Types</span>';
										
										echo '    <ul>';
										echo          renderTree($dataArr);
										echo '    </ul>';
										
										echo '  </li>';
										echo '</ul>';
									}
									?>
								</div>
								<div id="neon-theme" class="box">
									<h2>Organized by Protocol</h2>
									<?php
									// load the JSON
									$jsonFile = '../../neon-react/biorepo_lib/collections-protocol.json';
									$jsonData = file_get_contents($jsonFile);
									$dataArr = json_decode($jsonData, true);
									
									// render the full tree
									if ($dataArr) {
										echo '<ul id="collections-list2">';
										echo '  <li>';
										echo '    <input type="checkbox" class="all-selector all-neon-colls" checked>';
										echo '    <span class="material-icons expansion-icon">indeterminate_check_box</span>';
										echo '    <span class="group-label">All Sample Types</span>';
										
										echo '    <ul>';
										echo          renderTree($dataArr);
										echo '    </ul>';
										
										echo '  </li>';
										echo '</ul>';
									}
									?>
								</div>
								<div id="sample-type" class="box">
									<h2>Organized by Preservation Method</h2>
									<?php
									// load the JSON
									$jsonFile = '../../neon-react/biorepo_lib/collections-sampletype.json';
									$jsonData = file_get_contents($jsonFile);
									$dataArr = json_decode($jsonData, true);
									
									// render the full tree
									if ($dataArr) {
										echo '<ul id="collections-list3">';
										echo '  <li>';
										echo '    <input type="checkbox" class="all-selector all-neon-colls" checked>';
										echo '    <span class="material-icons expansion-icon">indeterminate_check_box</span>';
										echo '    <span class="group-label">All Sample Types</span>';
										
										echo '    <ul>';
										echo          renderTree($dataArr);
										echo '    </ul>';
										
										echo '  </li>';
										echo '</ul>';
									}
									?>
								</div>
							</div>
						</div>
					</div>
				</section>
				<!-- Locality -->
				<section>
					<!-- Accordion selector -->
					<input type="checkbox" id="locality" class="accordion-selector" checked=true />
					<!-- Accordion header -->
					<label for="locality" class="accordion-header">Domains & Sites</label>
					<!-- Accordion content -->
					<div class="content">
						<div id="search-form-locality">
							<ul id="site-list"><li class='Mui'><input id="all-sites" data-chip="All Domains & Sites" type="checkbox" class="all-selector" checked="" data-form-id='search-form-locality'><span class="material-icons expansion-icon">indeterminate_check_box</span><span>All NEON Domains and Sites</span>
								<?php if ($domainsArr = $siteData->getNeonDomains()) {
									echo '<ul>';
									foreach ($domainsArr as $domain) {
										echo "<li class='Mui'><input type='checkbox' id='{$domain["domainnumber"]}' class='all-selector child' name='datasetid' value='{$domain["datasetid"]}' checked=''><span class='material-icons expansion-icon'>add_box</span><span class='group-label'>{$domain["domainnumber"]} - {$domain["domainname"]}</span>";
										echo "<ul class='collapsed'>";
										// ECHO SITES PER DOMAINS
										$sitesArr = $siteData->getNeonSitesByDom($domain["domainnumber"]);
										if ($sitesArr) {
											foreach ($sitesArr as $site) {
												echo "<li class='Mui'>";
												echo "<input type='checkbox' id='{$site["siteid"]}' name='datasetid' value='{$site["datasetid"]}' class='child' data-domain='{$domain["domainnumber"]}' checked>";
												echo "<span class='leaf-label ml-1 child'>({$site["siteid"]}) {$site["sitename"]}</span>";
												echo " <a href='https://www.neonscience.org/field-sites/{$site["siteid"]}' target='_blank' rel='noopener noreferrer' title='View Site Profile'><span class='material-icons' style='color:#565a5c; vertical-align:middle;'>info</span></a>";
												echo "</li>";

											}
										};
										echo "</ul>";
										echo "</li>";
									}
									echo '</ul>';
								}; ?>
								</li>
							</ul>
							<div>
								<div>
									<div class="input-text-container">
										<label for="state" class="input-text--outlined">
											<input type="text" name="state" id="state" data-chip="State">
											<span data-label="State"></span></label>
										<span class="assistive-text">Separate multiple with commas.</span>
									</div>
									<div class="input-text-container">
										<label for="county" class="input-text--outlined">
											<input type="text" name="county" id="county" data-chip="County">
											<span data-label="County"></span></label>
										<span class="assistive-text">Separate multiple with commas.</span>
									</div>
									<div class="input-text-container">
										<label for="local" class="input-text--outlined">
											<input type="text" name="local" id="local" data-chip="Locality">
											<span data-label="Locality"></span></label>
										<span class="assistive-text" style="line-height:1.7em">Separate multiple with commas. Accepts NEON Domain and/or Site names and codes.</span>
									</div>
								</div>
								<div class="grid grid--half">
									<div class="input-text-container">
										<label for="elevlow" class="input-text--outlined">
											<input type="number" step="any" name="elevlow" id="elevlow" data-chip="Min Elevation">
											<span data-label="Minimum Elevation"></span></label>
										<span class="assistive-text">Meters</span>
									</div>
									<div class="input-text-container">
										<label for="elevhigh" class="input-text--outlined">
											<input type="number" step="any" name="elevhigh" id="elevhigh" data-chip="Max Elevation">
											<span data-label="Maximum Elevation"></span></label>
										<span class="assistive-text">Meters</span>
									</div>
								</div>
							</div>
						</div>
					</div>
				</section>
				<!-- Collecting Event -->
				<section>
					<!-- Accordion selector -->
					<input type="checkbox" id="coll-event" class="accordion-selector" checked=true />
					<!-- Accordion header -->
					<label for="coll-event" class="accordion-header">Date</label>
					<!-- Accordion content -->
					<div class="content">
						<div id="search-form-coll-event">
							<div class="input-text-container">
								<label for="eventdate1" class="input-text--outlined">
									<input type="text" name="eventdate1" data-chip="Event Date Start">
									<span data-label="Start Date"></span></label>
								<span class="assistive-text">Single date or start date of range (e.g. YYYY, YYYY-MM-DD, or similar).</span>
							</div>
							<div class="input-text-container">
								<label for="eventdate2" class="input-text--outlined">
									<input type="text" name="eventdate2" data-chip="Event Date End">
									<span data-label="End Date"></span></label>
								<span class="assistive-text">End date of range (e.g. YYYY, YYYY-MM-DD, or similar).</span>
							</div>
						</div>
					</div>
				</section>
				<!-- Sample Properties -->
				<section>
					<!-- Accordion selector -->
					<input type="checkbox" id="sample" class="accordion-selector"/>
					<!-- Accordion header -->
					<label for="sample" class="accordion-header">Sample Properties</label>
					<!-- Accordion content -->
					<div class="content">
						<div id="search-form-sample">
							<div>
								<div class="text-area-container">
									<label for="" class="text-area--outlined">
										<textarea name="catnum" data-chip="Identifier" style="width: 100%"></textarea>
										<span data-label="Identifiers"></span></label>
									<span class="assistive-text">Separate multiple with commas or new lines.</span>
								</div>
								<div style="display:none">
									<input type="checkbox" name="includeothercatnum" id="includeothercatnum" value="1" checked>
									<label for="includeothercatnum">Search all identifiers</label>
								</div>
								<div>
									<input type="checkbox" name="includematerialsample" id="includematerialsample" value=1 data-chip="Include material samples" >
									<label for="includematerialsample">Include material samples</label>
								</div>
							</div>
							<div>
								<div>
									<input type="checkbox" name="hasimages" value=1 data-chip="Only with images">
									<label for="hasimages">Specimens with images</label>
								</div>
								<div>
									<input type="checkbox" name="hasgenetic" value=1 data-chip="Only with genetic">
									<label for="hasgenetic">Specimens with genetic data</label>
								</div>
								<div>
									<input type="checkbox" name="availableforloan" value=1 data-chip="Only available for loan">
									<label for="availableforloan">Specimens available for loan</label>
								</div>
							</div>
							<div class="input-text-container">
								<label for="collector" class="input-text--outlined">
									<input type="text" name="collector" data-chip="Collector/ORCID">
									<span data-label="Collector/ORCID"></span></label>
								<span class="assistive-text">Any part of a collector's name or ORCID iD (XXXX-XXXX-XXXX-XXXX).</span>
							</div>
						</div>
					</div>
				</section>
				<!-- Taxonomy -->
				<section>
					<!-- Accordion selector -->
					<input type="checkbox" id="taxonomy" class="accordion-selector"/>

					<!-- Accordion header -->
					<label for="taxonomy" class="accordion-header">Taxonomy</label>

					<!-- Taxonomy -->
					<div id="search-form-taxonomy" class="content">
						<div id="taxa-text" class="input-text-container">
							<label for="taxa" class="input-text--outlined">
								<input type="text" name="taxa" id="taxa" data-chip="Taxa">
								<span data-label="Taxon"></span></label>
							<span class="assistive-text">Type at least 4 characters for quick suggestions. Separate multiple with commas. Includes non-organismal groups.</span>
						</div>
						<div>
						  <input type="checkbox" name="usethes" id="usethes" data-chip="Exclude Synonyms">
						  <span class="ml-1">Exclude Synonyms</span>
						</div>
					</div>
				</section>
				<!-- Latitude & Longitude -->
				<section>
					<!-- Accordion selector -->
					<input type="checkbox" id="lat-long" class="accordion-selector" />
					<!-- Accordion header -->
					<label for="lat-long" class="accordion-header">Latitude & Longitude</label>
					<!-- Accordion content -->
					<div class="content">
						<div id="search-form-latlong">
							<div id="bounding-box-form">
								<h3>Bounding Box</h3>
								<button onclick="openCoordAid('rectangle');return false;">Select in map</button>
								<div class="input-text-container">
									<label for="upperlat" class="input-text--outlined">
										<input type="number" step="any" min="-90" max="90" id="upperlat" name="upperlat" data-chip="Upper Lat">
										<select class="mt-1" id="upperlat_NS" name="upperlat_NS">
											<option value="">Select N/S</option>
											<option id="ulN" value="N">N</option>
											<option id="ulS" value="S">S</option>
										</select>
										<span data-label="Northern Latitude"></span></label>
									<span class="assistive-text">Values between -90 and 90.</span>
								</div>
								<div class="input-text-container">
									<label for="bottomlat" class="input-text--outlined">
										<input type="number" step="any" min="-90" max="90" id="bottomlat" name="bottomlat" data-chip="Bottom Lat">
										<select class="mt-1" id="bottomlat_NS" name="bottomlat_NS">
											<option value="">Select N/S</option>
											<option id="blN" value="N">N</option>
											<option id="blS" value="S">S</option>
										</select>
										<span data-label="Southern Latitude"></span></label>
									<span class="assistive-text">Values between -90 and 90.</span>
								</div>
								<div class="input-text-container">
									<label for="leftlong" class="input-text--outlined">
										<input type="number" step="any" min="-180" max="180" id="leftlong" name="leftlong" data-chip="Left Long">
										<select class="mt-1" id="leftlong_EW" name="leftlong_EW">
											<option value="">Select W/E</option>
											<option id="llW" value="W">W</option>
											<option id="llE" value="E">E</option>
										</select>
										<span data-label="Western Longitude"></span></label>
									<span class="assistive-text">Values between -180 and 180.</span>
								</div>
								<div class="input-text-container">
									<label for="rightlong" class="input-text--outlined">
										<input type="number" step="any" min="-180" max="180" id="rightlong" name="rightlong" data-chip="Right Long">
										<select class="mt-1" id="rightlong_EW" name="rightlong_EW">
											<option value="">Select W/E</option>
											<option id="rlW" value="W">W</option>
											<option id="rlE" value="E">E</option>
										</select>
										<span data-label="Eastern Longitude"></span></label>
									<span class="assistive-text">Values between -180 and 180.</span>
								</div>
							</div>
							<div id="polygon-form">
								<h3>Polygon (WKT footprint)</h3>
								<button onclick="openCoordAid('polygon');return false;">Select in map</button>
								<div class="text-area-container">
									<label for="footprintwkt" class="text-area--outlined">
										<textarea id="footprintwkt" name="footprintwkt" wrap="off" cols="30%" rows="5"></textarea>
										<span data-label="Polygon"></span></label>
									<span class="assistive-text">Select in map with button or paste values.</span>
								</div>
							</div>
							<div id="point-radius-form">
								<h3>Point-Radius</h3>
								<button onclick="openCoordAid('circle');return false;">Select in map</button>
								<div class="input-text-container">
									<label for="pointlat" class="input-text--outlined">
										<input type="number" step="any" min="-90" max="90" id="pointlat" name="pointlat" data-chip="Point Lat">
										<select class="mt-1" id="pointlat_NS" name="pointlat_NS">
											<option value="">Select N/S</option>
											<option id="N" value="N">N</option>
											<option id="S" value="S">S</option>
										</select>
										<span data-label="Latitude"></span></label>
									<span class="assistive-text">Values between -90 and 90.</span>
								</div>
								<div class="input-text-container">
									<label for="pointlong" class="input-text--outlined">
										<input type="number" step="any" min="-180" max="180" id="pointlong" name="pointlong" data-chip="Point Long">
										<select class="mt-1" id="pointlong_EW" name="pointlong_EW">
											<option value="">Select W/E</option>
											<option id="W" value="W">W</option>
											<option id="E" value="E">E</option>
										</select>
										<span data-label="Longitude"></span></label>
									<span class="assistive-text">Values between -180 and 180.</span>
								</div>
								<div class="input-text-container">
									<label for="radius" class="input-text--outlined">
										<input type="number" min="0" step="any" id="radius" name="radius" data-chip="Radius">
										<select class="mt-1" id="radiusunits" name="radiusunits">
											<option value="">Select Unit</option>
											<option value="km">Kilometers</option>
											<option value="mi">Miles</option>
										</select>
										<span data-label="Radius"></span></label>
									<span class="assistive-text">Any positive values.</span>
								</div>
							</div>
						</div>
					</div>
				</section>
				<!-- Advanced Search -->
				<section>
					<!-- Accordion selector -->
					<input type="checkbox" id="advanced-search" class="accordion-selector" />
					<!-- Accordion header -->
					<label for="advanced-search" class="accordion-header">Advanced Search</label>
					<!-- Accordion content -->
					<div class="content">
						<div id="search-form-advanced-search">

						<?php
						include($SERVER_ROOT . '/neon/search/includes/queryform.php');
						?>



						</div>
					</div>
				</section>
			</div>
			<!-- Criteria panel -->
			<div id="criteria-panel" style="position: sticky; top: 130; height: 50vh">
				<button id="search-btn">Search</button>
				<button id="reset-btn">Reset</button>
				<h2>Criteria</h2>
				<div id="chips"></div>
			</div>
		</form>

	</div>
	<?php
	include($SERVER_ROOT . '/includes/footer.php');
	?>
</body>
<script src="js/searchform.js?ver=14" type="text/javascript"></script>
<script> window.addEventListener('load', updateChip);</script>
</html>