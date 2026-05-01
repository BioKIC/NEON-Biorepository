<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT . '/neon/classes/CollectionMetadata.php');
include_once($SERVER_ROOT . '/neon/classes/DatasetsMetadata.php');
include_once($SERVER_ROOT . '/neon/classes/RequestReport.php');

header("Content-Type: text/html; charset=" . $CHARSET);

$collData = new CollectionMetadata();
$siteData = new DatasetsMetadata();
$reportManager = new RequestReportManager();
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

  // Expand / collapse groups
	const toggleElements = document.querySelectorAll('[data-target], .group-label, .expansion-icon');

  toggleElements.forEach(element => {
    element.addEventListener('click', (event) => {
      event.stopPropagation();

      const parentItem = element.closest('li');
      const childList = parentItem?.querySelector('ul');
      const icon = parentItem?.querySelector('.expansion-icon');

      if (!childList) return;

      const collapsed = childList.classList.toggle('collapsed');

      if (icon) {
        icon.textContent = collapsed ? 'add_box' : 'indeterminate_check_box';
      }
    });
  });


  // Toggle checkbox when clicking leaf label
  const leafLabels = document.querySelectorAll('.leaf-label');

  leafLabels.forEach(label => {
    label.addEventListener('click', () => {
      const checkbox = label.previousElementSibling;

      if (checkbox?.type === 'checkbox') {
        checkbox.checked = !checkbox.checked;
      }
    });
  });

});

	</script>

	<!-- Search-specific styles -->
	<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
	<link rel="stylesheet" href="../css/app.css?v=<?php echo date('Ymd'); ?>">
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

	#status-list,
	#status-list ul {
		list-style: none;
		padding-left: 20;
		margin-left: 0;
	}

	#status-list li {
		margin-left: 0;
	}

	#status-list ul {
    padding-left:40px; 
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
	
				$html .= "<input type='checkbox' name='db' value='{$collid}' class='child' data-cat='{$parentId}' data-ccode='{$name}'>";
				$html .= "<span class='leaf-label ml-1 child'>{$name}</span>";
				$html .= " <a href='../../collections/misc/neoncollprofiles.php?collid={$collid}' title='View Sample Type Profile' target='_blank'><span class='material-icons' style='color:#565a5c; vertical-align:middle;'>info</span></a>";

			} 
			// group node
			else {
				$name = htmlspecialchars($node['name']);
				$catAttr = $parentId !== '' ? " data-cat='{$parentId}'" : '';
				$html .= "<input type='checkbox' id='{$cCodeId}' class='all-selector child'{$catAttr} data-ccode='{$name}'>";
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
		<h1 style="
			margin-bottom: 30px;
			font-size: 2.3rem;
		">Search Sample Use Inquiries</h1>
		<div id="error-msgs" class="errors"></div>
		<form id="params-form">
			<!-- Criteria forms -->
			<div class="accordions">
				<!-- Status -->
				<section>
					<!-- Accordion selector -->
					<input type="checkbox" id="status" class="accordion-selector" />
					<!-- Accordion header -->
					<label for="status" class="accordion-header">Status</label>
					<!-- Accordion content -->
					<div class="content">
						<div id="search-form-status">
							<ul id="status-list">
							<?php 
							if ($statuses = $reportManager->getStatuses()) {
								foreach ($statuses as $status) {
								$val = htmlspecialchars($status["status"]);
								echo "
								<li class='Mui'>
									<label style='display:flex; align-items:center; gap:5px;'>
									<input type='checkbox'
											name='status[]'
											value='{$val}'
											class='child status-checkbox'
											data-chip='Status: {$val}'
											checked>
									<span>{$val}</span>
									</label>
								</li>";
								}
							}
							?>
							</ul>
						</div>
					</div>
				</section>
				<!-- Collections -->
				<section>
					<!-- Accordion selector -->
					<input type="checkbox" id="collections" class="accordion-selector"/>
					<!-- Accordion header -->
					<label for="collections" class="accordion-header">Sample Types</label>
					<!-- Accordion content -->
					<div class="content">
						<div id="search-form-colls">
							<section>
								<!-- Open NEON Collections modal -->
								<label class="accordion-subheader neon-modal-open">
									<input id="all-neon-colls-quick" data-chip="All Sample Types at the Biorepository" type="checkbox" data-form-id="biorepo-collections-list">
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
											  class="all-selector"
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
											<ul id="neonext-collections-items">
											  <?php if ($collsArr = $collData->getCollMetaByCat('Additional NEON Collections')) {
												echo '<ul>';
												foreach ($collsArr as $result) {
												  echo "<li class='Mui'>";
												  echo "<input type='checkbox' name='db' value='{$result["collid"]}' class='child' data-ccode='{$result["institutioncode"]} {$result["collectioncode"]}'>";
												  echo "<span class='leaf-label ml-1 child'>{$result["collectionname"]}</span>";
												  echo " <a href='../../collections/misc/neoncollprofiles.php?collid={$result["collid"]}' target='_blank' rel='noopener noreferrer' title='View Collection Profile'><span class='material-icons' style='color:#565a5c; vertical-align:middle;'>info</span></a>";
												  echo "</li>";
												}
												echo '</ul>';
											  } ?>
											</ul>
										</div>
									</li>
								</ul>
							</section>	
						</div>
					</div>
					<!-- NEON Biorepository Collections Modal -->
					<div class="modal" id="biorepo-collections-list">
						<div class="modal-content">
							<button id="neon-modal-close" class="btn" style="width:auto !important">Accept and close</button>
							<div id="colls-modal">
								<div>
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
										echo '    <input type="checkbox" class="all-selector all-neon-colls">';
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
										echo '    <input type="checkbox" class="all-selector all-neon-colls">';
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
										echo '    <input type="checkbox" class="all-selector all-neon-colls">';
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
					<input type="checkbox" id="locality" class="accordion-selector"/>
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
							</div>
						</div>
					</div>
				</section>
				<!-- Collecting Event -->
				<section>
					<!-- Accordion selector -->
					<input type="checkbox" id="coll-event" class="accordion-selector"/>
					<!-- Accordion header -->
					<label for="coll-event" class="accordion-header">Status Date</label>
					<!-- Accordion content -->
					<div class="content">
						<div id="search-form-coll-event">
							<div class="input-text-container">
								<label for="inquiry-eventdate1" class="input-text--outlined">
									<input type="text" name="inquiry-eventdate1" data-chip="Initial Inquiry Date Start">
									<span data-label="Inquiry Date"></span></label>
								<span class="assistive-text">Single date or start date of range (e.g. YYYY, YYYY-MM-DD, or similar).</span>
							</div>
							<div class="input-text-container">
								<label for="inquiry-eventdate2" class="input-text--outlined">
									<input type="text" name="inquiry-eventdate2" data-chip="Initial Inquiry Date End">
									<span data-label="Inquiry End Date"></span></label>
								<span class="assistive-text">End date of range (e.g. YYYY, YYYY-MM-DD, or similar).</span>
							</div>
							<div class="input-text-container">
								<label for="active-eventdate1" class="input-text--outlined">
									<input type="text" name="active-eventdate1" data-chip="Active Date Start">
									<span data-label="Active Date"></span></label>
								<span class="assistive-text">Single date or start date of range (e.g. YYYY, YYYY-MM-DD, or similar).</span>
							</div>
							<div class="input-text-container">
								<label for="active-eventdate2" class="input-text--outlined">
									<input type="text" name="active-eventdate2" data-chip="Active Date End">
									<span data-label="Active End Date"></span></label>
								<span class="assistive-text">End date of range (e.g. YYYY, YYYY-MM-DD, or similar).</span>
							</div>
							<div class="input-text-container">
								<label for="status-eventdate1" class="input-text--outlined">
									<input type="text" name="status-eventdate1" data-chip="Latest Status Date Start">
									<span data-label="Latest Status Date"></span></label>
								<span class="assistive-text">Single date or start date of range (e.g. YYYY, YYYY-MM-DD, or similar).</span>
							</div>
							<div class="input-text-container">
								<label for="status-eventdate2" class="input-text--outlined">
									<input type="text" name="status-eventdate2" data-chip="Latest Status Date End">
									<span data-label="Latest Status End Date"></span></label>
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
								<input type="text" name="taxa" id="taxa" data-chip="Taxa" placeholder="Scientific names only (e.g., Carabidae)">
								<span data-label="Taxon"></span></label>
							<span class="assistive-text">Type at least 4 characters for quick suggestions. Separate multiple with commas. Includes non-organismal groups.</span>
						</div>
						<div>
						  <input type="checkbox" name="usethes" id="usethes" data-chip="Exclude Synonyms">
						  <span class="ml-1">Exclude Synonyms</span>
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

<script src="<?= $CLIENT_ROOT ?>/neon/requests/js/requestsearchform.js"></script>
</html>