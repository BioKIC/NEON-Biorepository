<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT . '/neon/classes/CollectionMetadata.php');
include_once($SERVER_ROOT . '/neon/classes/DatasetsMetadata.php');
include_once($SERVER_ROOT . '/classes/OccurrenceManager.php');
header("Content-Type: text/html; charset=" . $CHARSET);

$collData = new CollectionMetadata();
$siteData = new DatasetsMetadata();
$collManager = new OccurrenceManager();
?>
<html>

<head>
	<!-- neon edit -->
	<title>Search Samples &amp; Specimens</title>
	<!-- end neon edit -->
	<?php
	include_once($SERVER_ROOT . '/includes/head.php');
	?>
	<link href="<?= $CLIENT_ROOT; ?>/css/jquery-ui.min.css" type="text/css" rel="stylesheet">
	<script src="<?= $CLIENT_ROOT ?>/js/jquery-3.7.1.min.js" type="text/javascript"></script>
	<script src="<?= $CLIENT_ROOT ?>/js/jquery-ui.min.js" type="text/javascript"></script>
	<script src="<?= $CLIENT_ROOT ?>/js/symb/api.taxonomy.taxasuggest.js" type="text/javascript"></script>
	<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/css/select2.min.css" rel="stylesheet"/>
	<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/js/select2.min.js">
	</script>
	<script>
		const clientRoot = '<?php echo $CLIENT_ROOT; ?>';
	</script>
	<script>

	$(function () {
		$("#eventdate1, #eventdate2").datepicker({
			dateFormat: "yy-mm-dd",
			changeMonth: true,
			changeYear: true,
			yearRange: "2008:<?= date('Y') ?>",
	
			onSelect: function () {
				updateChip();
			}
		});
		$("#ui-datepicker-div").addClass("neon-search-datepicker");
		if ($("#taxa").autocomplete("instance")) {
			$("#taxa").autocomplete("option", "appendTo", "#params-form");
		}
	});
	</script>

	<!-- Search-specific styles -->
	<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
	<link rel="stylesheet" href="../css/app.css?v=<?php echo date('Ymd'); ?>">
	<link rel="stylesheet" href="css/searchform.css?v=<?= filemtime(__DIR__ . '/css/searchform.css') ?>">
</head>

<body>
	<?php
	include($SERVER_ROOT . '/includes/header.php');

	//function to render sample type tree
	function renderTree($nodes, $parentId = '') {
		global $IS_ADMIN, $USER_RIGHTS;
		$html = '';
		foreach ($nodes as $node) {
			$cCodeId = 'cl-' . preg_replace('/[^a-z0-9\-]/', '-', strtolower($node['name']));
			$cCodeId = preg_replace('/-+/', '-', $cCodeId);
	
			$html .= "<li>";
	
			// leaf node (collection)
			if (isset($node['collid'])) {
				$collid = $node['collid'];
				$name = htmlspecialchars($node['name']);
	
				$html .= "<input type='checkbox' name='db' value='{$collid}' class='child search-checkbox-input' data-cat='{$parentId}' data-ccode='{$name}' "
						. (
							$IS_ADMIN ||
							!empty($USER_RIGHTS["CollAdmin"]) ||
							!empty($USER_RIGHTS["CollEditor"])
							? "checked"
							: ""
						)
						. ">";
				$html .= "<span class='leaf-label ml-1 child'>{$name}</span>";
				$html .= " <a href='../../collections/misc/neoncollprofiles.php?collid={$collid}' title='View Sample Type Profile' target='_blank'><span class='material-icons' style='color:#565a5c; vertical-align:middle;'>info</span></a>";

			} 
			// group node
			else {
				$name = htmlspecialchars($node['name']);
				$catAttr = $parentId !== '' ? " data-cat='{$parentId}'" : '';
				$html .= "<input type='checkbox' id='{$cCodeId}' class='all-selector child search-checkbox-input'{$catAttr} data-ccode='{$name}' "
					. (
						$IS_ADMIN ||
						!empty($USER_RIGHTS["CollAdmin"]) ||
						!empty($USER_RIGHTS["CollEditor"])
						? "checked"
						: ""
					)
					. ">";
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
	<div id="innertext" class="neon-search">
		<h1 style="
			margin-bottom: 30px;
			font-size: 2.3rem;
		">Search Samples &amp; Specimens</h1>
		<div id="error-msgs" class="errors" role="alert"></div>
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
						<div class="accordion-content-inner"><div class="accordion-content-body">
						<p class="Mui">
						  Looking for pinned specimens, blood vials, DNA extracts, frozen microbes or other NEON samples? Choose an available sample type here. 
						</p>
						<div id="search-form-colls">
							<section>
								<!-- Open NEON Collections modal -->
								<label class="accordion-subheader neon-modal-open" data-modal-id="biorepo-collections-list">
								<input
								  id="all-neon-colls-quick"
								  class="search-checkbox-input"
								  data-chip="All Sample Types at the Biorepository"
								  type="checkbox"
								  data-form-id="biorepo-collections-list"
								  <?php
									if (
										$IS_ADMIN ||
										!empty($USER_RIGHTS["CollAdmin"]) ||
										!empty($USER_RIGHTS["CollEditor"])
									) {
										echo 'checked';
									}
								  ?>
								>
									<span>All NEON Sample Types at the Biorepository</span>
								</label>
							</section>
							<section>
								<!-- External Collections -->
								<ul id="neonext-collections-list">
									<li class="Mui">
										<!-- Accordion selector -->
										<input type="checkbox" id="other-repos" class="accordion-selector" />
										<!-- Accordion header -->
										<label for="other-repos" class="accordion-subheader">
											<input
											  id="all-neon-ext"
											  data-chip="All Sample Types at Other Repositories"
											  type="checkbox"
											  class="all-selector search-checkbox-input"
											  data-form-id="neonext-collections-list"
											>
										  All NEON Sample Types at Other Repositories
										  <a href="https://www.neonscience.org/samples/sample-repositories"
											 target="_blank" rel="noopener noreferrer" title="View More Information">
											<span class="material-icons" style="color:#565a5c; vertical-align:middle;">info</span>
										  </a>
										</label>								
										<!-- Accordion content -->
										<div class="content">
											<div class="accordion-content-inner"><div class="accordion-content-body">
											<ul id="neonext-collections-items">
											  <?php if ($collsArr = $collData->getCollMetaByCat('Additional NEON Collections')) {
												echo '<ul>';
												foreach ($collsArr as $result) {
												  echo "<li class='Mui'>";
												  echo "<input type='checkbox' name='db' value='{$result["collid"]}' class='child search-checkbox-input' data-ccode='{$result["institutioncode"]} {$result["collectioncode"]}'>";
												  echo "<span class='leaf-label ml-1 child'>{$result["collectionname"]}</span>";
												  echo " <a href='../../collections/misc/neoncollprofiles.php?collid={$result["collid"]}' target='_blank' rel='noopener noreferrer' title='View Collection Profile'><span class='material-icons' style='color:#565a5c; vertical-align:middle;'>info</span></a>";
												  echo "</li>";
												}
												echo '</ul>';
											  } ?>
											</ul>
											</div></div>
										</div>
									</li>
								</ul>
							</section>
							<p class="Mui">
							  Unsure what sample type to choose? Browse our <a href="<?php echo $CLIENT_ROOT . '/collections/misc/browsecollprofiles.php'; ?>" target="_blank">sample type profiles</a> for descriptions, collection methods and associated data or <a href="https://www.neonscience.org/about/contact-neon-biorepository">contact us</a> for help!
							</p>	
						</div>
						</div></div>
					</div>
					<!-- NEON Biorepository Collections Modal -->
					<div class="modal" id="biorepo-collections-list">
						<div class="modal-content">
							<button type="button" id="neon-modal-close" class="modal-close btn">Accept and close</button>
							<div id="colls-modal">
								<div class="search-tabs">
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
										echo '    <input type="checkbox" class="all-selector all-neon-colls search-checkbox-input">';
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
										echo '    <input type="checkbox" class="all-selector all-neon-colls search-checkbox-input">';
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
										echo '    <input type="checkbox" class="all-selector all-neon-colls search-checkbox-input">';
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
					<button type="button" id="locality" class="accordion-header neon-modal-open" data-modal-id="domains-sites-modal">Domains &amp; Sites</button>
					<!-- Accordion content -->
					<div class="modal" id="domains-sites-modal">
						<div class="modal-content">
							<button type="button" id="domains-sites-modal-close" class="modal-close btn">Accept and close</button>
				
							<div id="collection-search-map"></div>
						</div>
					</div>
				</section>
				<!-- Collecting Event -->
				<section>
					<!-- Accordion selector -->
					<input type="checkbox" id="coll-event" class="accordion-selector" />
					<!-- Accordion header -->
					<label for="coll-event" class="accordion-header">Date</label>
					<!-- Accordion content -->
					<div class="content">
						<div class="accordion-content-inner"><div class="accordion-content-body">
						<div id="search-form-coll-event">
							<div class="input-text-container">
								<label for="eventdate1" class="input-text--outlined input-text--floating">
									<input type="text" placeholder=" " id="eventdate1" name="eventdate1" data-chip="Event Date Start">
									<span class="search-field-label" data-label="Start Date">Start Date</span>
								</label>
								<span class="assistive-text">
									Single date or start date of range.
								</span>
							</div>
					
							<div class="input-text-container">
								<label for="eventdate2" class="input-text--outlined input-text--floating">
									<input type="text" placeholder=" " id="eventdate2" name="eventdate2" data-chip="Event Date End">
									<span class="search-field-label" data-label="End Date">End Date</span>
								</label>
								<span class="assistive-text">
									End date of range.
								</span>
							</div>
						</div>
						</div></div>
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
						<div class="accordion-content-inner"><div class="accordion-content-body">
						<div id="search-form-sample">
							<div>
								<div class="text-area-container">
									<label for="catnum" class="text-area--outlined">
										<textarea id="catnum" name="catnum" data-chip="Identifier" rows="4"
												  placeholder="NEON007VA&#10;NEON.BET.D06.002579&#10;A00000020232&#10;MOS.D05*"
												  ></textarea>
										<span class="search-field-label" data-label="Identifiers">Identifiers</span></label>
									<span class="assistive-text">
										Separate multiple values with commas or new lines. 
									</span>
								</div>
								<div style="display:none">
									<input type="checkbox" name="includeothercatnum" id="includeothercatnum" value="1" checked>
									<label for="includeothercatnum">Search all identifiers</label>
								</div>
							</div>
							<div class="search-checks">
								<div>
									<input type="checkbox" id="hasimages" class="search-checkbox-input" name="hasimages" value=1 data-chip="Only with images">
									<label for="hasimages">Specimens with images</label>
								</div>
								<div>
									<input type="checkbox" id="hasgenetic" class="search-checkbox-input" name="hasgenetic" value=1 data-chip="Only with genetic">
									<label for="hasgenetic">Specimens with published genetic data</label>
								</div>
								<div>
									<input type="checkbox" id="availableforloan" class="search-checkbox-input" name="availableforloan" value=1 data-chip="Only available for loan">
									<label for="availableforloan">Specimens available for loan</label>
								</div>
							</div>
							<div class="input-text-container">
								<label for="collector" class="input-text--outlined input-text--floating">
									<input type="text" placeholder=" " id="collector" name="collector" data-chip="Collector/ORCID">
									<span class="search-field-label" data-label="Collector/ORCID">Collector/ORCID</span></label>
								<span class="assistive-text">Any part of a collector's name or ORCID iD (XXXX-XXXX-XXXX-XXXX).</span>
							</div>
						</div>
						</div></div>
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
						<div class="accordion-content-inner"><div class="accordion-content-body">
						<div id="taxa-text" class="input-text-container">
							<label for="taxa" class="input-text--outlined">
								<input type="text" name="taxa" id="taxa" data-chip="Taxa" data-occurrence-only="1" placeholder="Scientific names only (e.g., Carabidae)">
								<span class="search-field-label" data-label="Taxon">Taxon</span></label>
							<span class="assistive-text">Type at least 4 characters for quick suggestions. Separate multiple with commas. Includes non-organismal groups.</span>
						</div>
						<div class="search-checkbox">
						  <input type="checkbox" name="usethes" id="usethes" class="search-checkbox-input" data-chip="Exclude Synonyms">
						  <label for="usethes">Exclude Synonyms</label>
						</div>
						</div></div>
					</div>
				</section>
				<!-- Material Sample -->
				<?php
				if (
					$IS_ADMIN ||
					!empty($USER_RIGHTS["CollAdmin"]) ||
					!empty($USER_RIGHTS["CollEditor"])
				) {
				?>
				<script>
				function addMuiToMaterialSampleSelect2() {
					const $container = $('#materialsampletype').next('.select2-container');
				
					$container.addClass('Mui');
					$container.find('*:not(li)').addClass('Mui');
				
					$('#params-form .select2-dropdown').addClass('Mui');
					$('#params-form .select2-dropdown').find('*:not(li)').addClass('Mui');
				}
				
				$(document).ready(function () {
					$('#materialsampletype').select2({
						placeholder: 'Select material sample types',
						width: '100%',
						closeOnSelect: false,
						dropdownParent: $('#params-form')
					});
				
					addMuiToMaterialSampleSelect2();
				
					$('#materialsampletype').on(
						'select2:open select2:close select2:select select2:unselect select2:clear',
						addMuiToMaterialSampleSelect2
					);
				});
				</script>
				<section>
					<!-- Accordion selector -->
					<input type="checkbox" id="material-sample" class="accordion-selector" />
					
					<!-- Accordion header -->
					<label for="material-sample" class="accordion-header">Material Sample</label>
					
					<!-- Accordion content -->
					<div class="content">
						<div class="accordion-content-inner"><div class="accordion-content-body">
						<?php
						if ($matSampleTypeArr = $collManager->getMaterialSampleTypeArr()) {
						?>
							<div class="select-container">
								<label for="materialsampletype" class="search-control-label">Material sample types</label>
								<select name="materialsampletype[]" id="materialsampletype" data-chip= "Material Sample" multiple>
									<?php
									foreach ($matSampleTypeArr as $matSampeType) {
										echo '<option id="materialsampletype-' . $matSampeType . '" data-chip="' . $LANG['MATERIAL_SAMPLE'] . ': ' . $matSampeType . '" value="' . $matSampeType . '">' . $matSampeType . '</option>';
									}
									?>
								</select>
							</div>
						<?php
						}
						?>
						</div></div>
					</div>
				</section>
				
				<?php
				}
				?>
				<!-- Advanced Search -->
				<?php
				if (
					$IS_ADMIN ||
					!empty($USER_RIGHTS["CollAdmin"]) ||
					!empty($USER_RIGHTS["CollEditor"])
				) {
				?>
				
				<section>
					<!-- Accordion selector -->
					<input type="checkbox" id="advanced-search" class="accordion-selector" />
					
					<!-- Accordion header -->
					<label for="advanced-search" class="accordion-header">Advanced Search</label>
					
					<!-- Accordion content -->
					<div class="content">
						<div class="accordion-content-inner"><div class="accordion-content-body">
						<div id="search-form-advanced-search">
							<?php
							include($SERVER_ROOT . '/neon/search/includes/queryform.php');
							?>
						</div>
						</div></div>
					</div>
				</section>
				
				<?php
				}
				?>
			</div>
			<!-- Criteria panel -->
			<div id="criteria-panel">
				<div class="search-actions">
					<button type="submit" id="search-btn">Search</button>
					<button type="reset" id="reset-btn">Reset</button>
				</div>
				<h2>Criteria</h2>
				<div id="chips"></div>
				<p id="criteria-empty">Nothing selected</p>
			</div>
		</form>

	</div>
	<?php
	include($SERVER_ROOT . '/includes/footer.php');
	?>

</body>
<script src="js/searchform.js?ver=<?= date('Y-m-d H:i:s') ?>" type="text/javascript"></script>
</html>

