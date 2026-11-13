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
			showAnim: "fadeIn",
			duration: getSearchMotionDuration(),
	
			onSelect: function () {
				updateChip();
			}
		});
		$("#ui-datepicker-div").addClass("neon-search-datepicker");
		$("#params-form").tooltip({
			items: ".search-info[title]",
			classes: { "ui-tooltip": "neon-search-tooltip" },
			position: { my: "center bottom-8", at: "center top", collision: "flipfit" },
			show: { effect: "fadeIn", delay: 150, duration: getSearchMotionDuration(150) },
			hide: { effect: "fadeOut", duration: getSearchMotionDuration(100) }
		});
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

	function renderSearchInfoLink($url, $label) {
		$url = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
		$label = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
		return "<a class='search-info' href='{$url}' target='_blank' rel='noopener noreferrer' title='{$label}' aria-label='{$label}'>"
			. "<svg viewBox='0 0 24 24' focusable='false' aria-hidden='true'><path d='M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2m1 15h-2v-6h2zm0-8h-2V7h2z'></path></svg></a>";
	}

	function renderSearchProfileLink($collid, $name) {
		$url = htmlspecialchars('../../collections/misc/neoncollprofiles.php?collid=' . rawurlencode((string) $collid), ENT_QUOTES, 'UTF-8');
		$label = htmlspecialchars('View profile for ' . $name, ENT_QUOTES, 'UTF-8');
		return "<a class='search-profile-link' href='{$url}' target='_blank' rel='noopener noreferrer' aria-label='{$label}'>View profile</a>";
	}

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
				$html .= ' ' . renderSearchProfileLink($collid, $node['name']);

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
										  <?= renderSearchInfoLink('https://www.neonscience.org/samples/sample-repositories', 'View More Information') ?>
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
												  echo ' ' . renderSearchProfileLink($result['collid'], $result['collectionname']);
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
							<div id="colls-modal">
								<div class="search-tabs">
									<label class="tab tab-active"><input type="radio" name="collChoice" value="taxonomic-cat" checked="true"> Taxonomic Group</label>
									<label class="tab"><input type="radio" name="collChoice" value="neon-theme"> Protocol</label>
									<label class="tab"><input type="radio" name="collChoice" value="sample-type"> Preservation Method</label>
									<button type="button" id="neon-modal-close" class="modal-close btn">
										<span>Accept and close</span>
										<svg class="search-button-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M9 16.17 4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"></path></svg>
									</button>
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
					<div class="modal" id="domains-sites-modal" role="dialog" aria-modal="true" aria-labelledby="domains-sites-title">
						<div class="modal-content">
							<div class="search-map-header">
								<h2 id="domains-sites-title">Select sites</h2>
								<button type="button" id="domains-sites-modal-close" class="modal-close btn">
									<span>Accept and close</span>
									<svg class="search-button-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M9 16.17 4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"></path></svg>
								</button>
							</div>
				
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
									<input type="text" placeholder=" " id="eventdate1" name="eventdate1" data-chip="Event Date Start" autocomplete="off">
									<span class="search-field-label" data-label="Start Date">Start Date</span>
								</label>
								<span class="assistive-text">
									Single date or start date of range.
								</span>
							</div>
					
							<div class="input-text-container">
								<label for="eventdate2" class="input-text--outlined input-text--floating">
									<input type="text" placeholder=" " id="eventdate2" name="eventdate2" data-chip="Event Date End" autocomplete="off">
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
					<button type="submit" id="search-btn">
						<span>Search</span>
						<svg class="search-button-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19zM9.5 14C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14"></path></svg>
					</button>
					<button type="reset" id="reset-btn">
						<span>Reset</span>
						<svg class="search-button-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M17.65 6.35C16.2 4.9 14.21 4 12 4c-4.42 0-7.99 3.58-7.99 8s3.57 8 7.99 8c3.73 0 6.84-2.55 7.73-6h-2.08c-.82 2.33-3.04 4-5.65 4-3.31 0-6-2.69-6-6s2.69-6 6-6c1.66 0 3.14.69 4.22 1.78L13 11h7V4z"></path></svg>
					</button>
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

