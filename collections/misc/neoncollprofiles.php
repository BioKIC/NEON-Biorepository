<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT . '/content/lang/collections/misc/collprofiles.' . $LANG_TAG . '.php');
include_once($SERVER_ROOT . '/classes/OccurrenceCollectionProfile.php');
header('Content-Type: text/html; charset=' . $CHARSET);
unset($_SESSION['editorquery']);

$collManager = new OccurrenceCollectionProfile();

$collid = isset($_REQUEST['collid']) ? $collManager->sanitizeInt($_REQUEST['collid']) : 0;
$action = array_key_exists('action', $_REQUEST) ? $_REQUEST['action'] : '';
$eMode = array_key_exists('emode', $_REQUEST) ? $collManager->sanitizeInt($_REQUEST['emode']) : 0;

if ($eMode && !$SYMB_UID) {
    header('Location: ../../profile/index.php?refurl=' . rawurlencode('../collections/misc/neoncollprofiles.php?' . ($_SERVER['QUERY_STRING'] ?? '')));
    exit;
}

$collManager->setCollid($collid);

$collData = $collManager->getCollectionMetadata();
$datasetKey = $collManager->getDatasetKey();
$linkedCollections = $collManager->getLinkedCollections();

$editCode = 0;		//0 = no permissions; 1 = CollEditor; 2 = CollAdmin; 3 = SuperAdmin
if ($SYMB_UID) {
	if ($IS_ADMIN) {
		$editCode = 3;
	} else if ($collid) {
		if (array_key_exists('CollAdmin', $USER_RIGHTS) && in_array($collid, $USER_RIGHTS['CollAdmin'])) $editCode = 2;
		elseif (array_key_exists('CollEditor', $USER_RIGHTS) && in_array($collid, $USER_RIGHTS['CollEditor'])) $editCode = 1;
	}
}
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($LANG_TAG, ENT_QUOTES); ?>">
<head>
    <title><?php echo htmlspecialchars('NEON Biorepository | ' . (($collData[$collid]['publicname'] ?? '') ?: ($collData[$collid]['collectionname'] ?? 'Sample Types')), ENT_QUOTES); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php include_once($SERVER_ROOT . '/includes/head.php'); ?>
    <script>
        function toggleById(target) {
            const panel = document.getElementById(target);
            if (panel) panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
            return false;
        }
    </script>
</head>
<body>
    <?php
    $displayLeftMenu = false;
    include($SERVER_ROOT . '/includes/header.php');
    ?>
    <div class="navpath">
        <a href="../../index.php">Home</a> &gt;
        <a href="../index.php">Sample types</a> &gt; Sample type profile
    </div>
    <div id="innertext">
        <?php
		if ($editCode > 1) {
			if ($action == 'UpdateStatistics') {
				echo '<h2> ' . (isset($LANG['UPDATE_STATISTICS']) ? $LANG['UPDATE_STATISTICS'] : 'Updating statistics related to this collection...') . '</h2>';
				$collManager->updateStatistics(true);
				echo '<hr/>';
			}
		}
		if ($editCode && $collid) {
		?>
			<div style="float:right;margin:3px;cursor:pointer;" onclick="toggleById('controlpanel');" title="<?php echo (isset($LANG['TOGGLE_MAN']) ? $LANG['TOGGLE_MAN'] : 'Toggle Manager\'s Control Panel'); ?>">
				<img style='border:0px;' src='../../images/edit.png' alt='Open editing controls' />
			</div>
			<?php
		}
		if ($collid && isset($collData[$collid])) {
			$collData = $collData[$collid];
			$_SESSION['colldata'] = $collData;
			if ($editCode) {
			?>
				<div id="controlpanel" style="clear:both;display:<?php echo ($eMode ? 'block' : 'none'); ?>;">
					<fieldset style="padding:10px;padding-left:25px;">
						<legend><b><?php echo (isset($LANG['DAT_EDIT']) ? $LANG['DAT_EDIT'] : 'Data Editor Control Panel'); ?></b></legend>
						<fieldset style="float:right;margin:5px;min-inline-size:0;padding:0.35em 0.625em 0.75em;" title="Quick Search">
							<legend><b><?php echo (isset($LANG['QUICK_SEARCH']) ? $LANG['QUICK_SEARCH'] : 'Quick Search'); ?></b></legend>
							<b><?php echo (isset($LANG['CAT_NUM']) ? $LANG['CAT_NUM'] : 'Catalog Number'); ?></b><br />
							<form name="quicksearch" action="../editor/occurrenceeditor.php" method="post">
								<input name="q_catalognumber" type="text" style="border:1px solid #d0d0d0 !important;"/>
								<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
								<input name="occindex" type="hidden" value="0" />
							</form>
						</fieldset>
						<ul>
							<?php
							if (stripos($collData['colltype'], 'observation') !== false) {
							?>
								<li>
									<a href="../editor/observationsubmit.php?collid=<?php echo $collid; ?>">
										<?php echo (isset($LANG['SUBMIT_IMAGE_V']) ? $LANG['SUBMIT_IMAGE_V'] : 'Submit an Image Voucher (observation supported by a photo)'); ?>
									</a>
								</li>
							<?php
							}
							?>
							<li>
								<a href="../editor/occurrenceeditor.php?gotomode=1&collid=<?php echo $collid; ?>">
									<?php echo (isset($LANG['ADD_NEW_OCCUR']) ? $LANG['ADD_NEW_OCCUR'] : 'Add New Occurrence Record'); ?>
								</a>
							</li>

							<li>
								<a href="../editor/occurrencetabledisplay.php?displayquery=1&collid=<?php echo $collid; ?>">
									<?php echo (isset($LANG['EDIT_EXISTING']) ? $LANG['EDIT_EXISTING'] : 'Edit Existing Occurrence Records'); ?>
								</a>
							</li>

							<li>
								<a href="../reports/labelmanager.php?collid=<?php echo $collid; ?>">
									<?php echo (isset($LANG['PRINT_LABELS']) ? $LANG['PRINT_LABELS'] : 'Print Specimen Labels'); ?>
								</a>
							</li>

							<?php
							if ($collData['colltype'] == 'Preserved Specimens') {
							?>
								<li>
									<a href="../loans/index.php?collid=<?php echo $collid; ?>">
										<?php echo (isset($LANG['LOAN_MANAGEMENT']) ? $LANG['LOAN_MANAGEMENT'] : 'Loan Management'); ?>
									</a>
								</li>
							<?php
							}
							?>
						</ul>
					</fieldset>
					<?php
					if ($editCode > 1) {
					?>
						<fieldset style="padding:10px;padding-left:25px;">
							<legend><b><?php echo (isset($LANG['ADMIN_CONTROL']) ? $LANG['ADMIN_CONTROL'] : 'Administration Control Panel'); ?></b></legend>
							<ul>

								<li>
									<a href="collmetadata.php?collid=<?php echo $collid; ?>">
										<?php echo (isset($LANG['EDIT_META']) ? $LANG['EDIT_META'] : 'Edit Metadata'); ?>
									</a>
								</li>

								<li>
									<a href="collpermissions.php?collid=<?php echo $collid; ?>">
										<?php echo (isset($LANG['MANAGE_PERMISSIONS']) ? $LANG['MANAGE_PERMISSIONS'] : 'Manage Permissions'); ?>
									</a>
								</li>
								<li>
									<a href='../../neon/editor/neoneditor.php'>NEON Occurrence (Skeletal) and Extended Data Editor</a>
								</li>

								<?php
								if ($collData['colltype'] != 'General Observations') {
									if ($collData['managementtype'] != 'Aggregate') {
								?>
										<li>
											<a href="../specprocessor/index.php?collid=<?php echo $collid; ?>">
												<?php echo (isset($LANG['PROCESSING_TOOLBOX']) ? $LANG['PROCESSING_TOOLBOX'] : 'Processing Toolbox'); ?>
											</a>
										</li>
										<li>
											<a href="../datasets/datapublisher.php?collid=<?php echo $collid; ?>">
												<?php echo (isset($LANG['DARWIN_CORE_PUB']) ? $LANG['DARWIN_CORE_PUB'] : 'Darwin Core Archive Publishing'); ?>
											</a>
										</li>
									<?php
									}
									?>
									<li>
										<a href="../editor/editreviewer.php?collid=<?php echo $collid; ?>">
											<?php echo (isset($LANG['REVIEW_SPEC_EDITS']) ? $LANG['REVIEW_SPEC_EDITS'] : 'Review/Verify Occurrence Edits'); ?>
										</a>
									</li>

								<?php
								}
								if (!empty($ACTIVATE_DUPLICATES)) {
								?>
									<li>
										<a href="../datasets/duplicatemanager.php?collid=<?php echo $collid; ?>">
											<?php echo (isset($LANG['DUP_CLUSTER']) ? $LANG['DUP_CLUSTER'] : 'Duplicate Clustering'); ?>
										</a>
									</li>
								<?php
								}
								?>
								<li>
									<?php echo (isset($LANG['MAINTENANCE_TASKS']) ? $LANG['MAINTENANCE_TASKS'] : 'General Maintenance Tasks'); ?>
								</li>
								<?php
								if ($collData['colltype'] != 'General Observations') {
								?>
									<li style="margin-left:10px;">
									<a href="../cleaning/index.php?obsuid=0&collid=<?php echo $collid; ?>">
									<?php echo (isset($LANG['DATA_CLEANING']) ? $LANG['DATA_CLEANING'] : 'Data Cleaning Tools'); ?>
                                    </a></li>
								<?php
								}
								?>
								<li style="margin-left:10px;">
									<a href="#" onclick="newWindow = window.open('collbackup.php?collid=<?php echo $collid; ?>','bucollid','scrollbars=1,toolbar=0,resizable=1,width=600,height=250,left=20,top=20');">
										<?php echo (isset($LANG['BACKUP_DATA_FILE']) ? $LANG['BACKUP_DATA_FILE'] : 'Download Backup Data File'); ?>
									</a>
								</li>
								<?php
								if ($collData['managementtype'] == 'Live Data') {
								?>
									<li style="margin-left:10px;">
										<a href="../admin/restorebackup.php?collid=<?php echo $collid; ?>">
											<?php echo (isset($LANG['RESTORE_BACKUP']) ? $LANG['RESTORE_BACKUP'] : 'Restore Backup File'); ?>
										</a>
									</li>
								<?php
								}
								?>

								<li style="margin-left:10px;">
									<a href="neoncollprofiles.php?collid=<?php echo $collid; ?>&action=UpdateStatistics">
										<?php echo (isset($LANG['UPDATE_STATS']) ? $LANG['UPDATE_STATS'] : 'Update Statistics'); ?>
									</a>
								</li>
							</ul>
						</fieldset>
					<?php
					}
					?>
				</div>
			<?php
			}
			?>

            <?php
            // Adapt existing backend output for React; no class changes are needed.
            $decodeArray = static function ($json) {
                $value = json_decode($json ?? '', true);
                return is_array($value) ? $value : [];
            };
            $linkedCollectionCodes = [];
            $sampleCatalogPath = $SERVER_ROOT . '/neon/cache/sample-catalog.json';
            if ($linkedCollections && is_file($sampleCatalogPath) && is_readable($sampleCatalogPath)) {
                $sampleCatalog = $decodeArray(file_get_contents($sampleCatalogPath));
                $catalogItems = is_array($sampleCatalog['items'] ?? null) ? $sampleCatalog['items'] : [];
                foreach ($catalogItems as $item) {
                    if (!is_array($item)) continue;
                    $linkedId = (int) ($item['id'] ?? 0);
                    if (array_key_exists($linkedId, $linkedCollections) && is_string($item['collectionCode'] ?? null)) {
                        $linkedCollectionCodes[$linkedId] = trim($item['collectionCode']);
                    }
                }
            }
            ob_start();
            include($SERVER_ROOT . '/includes/citationneoncollection.php');
            ob_end_clean();
            $stats = $collManager->getBasicStats();
            $archiveUrl = $collData['dwcaurl'] ?? '';
            $archiveFilename = $archiveUrl ? basename(rawurldecode((string) parse_url($archiveUrl, PHP_URL_PATH))) : '';
            $resources = array_values(array_filter($decodeArray($collData['resourcejson'] ?? ''), static function ($resource) use ($archiveUrl, $archiveFilename) {
                if (!is_array($resource) || empty($resource['url']) || !is_string($resource['url'])) return false;
                // The archive has one PHP-selected action; never expose a second raw link in JSON.
                if (($resource['kind'] ?? '') === 'dwca' || $resource['url'] === $archiveUrl
                    || ($resource['title'] ?? '') === 'Darwin Core Archive') return false;
                $resourcePath = rawurldecode((string) parse_url($resource['url'], PHP_URL_PATH));
                return !$archiveFilename || basename($resourcePath) !== $archiveFilename
                    || strpos($resourcePath, '/content/dwca/') === false;
            }));
            $additionalResources = [];
            if ($archiveUrl) {
                $downloadUrl = rtrim($CLIENT_ROOT, '/') . '/content/dwca/' . rawurlencode($archiveFilename);
                $archiveSize = null;
                $archiveDirectory = realpath($SERVER_ROOT . '/content/dwca');
                $archiveFile = $archiveDirectory ? realpath($archiveDirectory . '/' . $archiveFilename) : false;
                // Read the size of the same local archive used by the root-relative download link.
                if ($archiveFile && dirname($archiveFile) === $archiveDirectory
                    && is_file($archiveFile) && is_readable($archiveFile)) {
                    $bytes = filesize($archiveFile);
                    if ($bytes !== false) $archiveSize = $bytes;
                }
                $returnUrl = $CLIENT_ROOT . '/collections/misc/neoncollprofiles.php?collid=' . $collid . '#data-downloads';
                // Use the Biorepository session, not a client-side login flag.
                $additionalResources[] = [
                    'kind' => 'dwca',
                    'title' => 'Full Data Download',
                    'description' => 'Download specimen records (DwC-A)',
                    'filename' => $SYMB_UID ? $archiveFilename : '',
                    'sizeBytes' => $archiveSize,
                    'url' => $SYMB_UID ? $downloadUrl : $CLIENT_ROOT . '/profile/index.php?refurl=' . rawurlencode($returnUrl),
                    'action' => $SYMB_UID ? 'Download' : 'Log in to download',
                    'actionIcon' => $SYMB_UID ? 'download' : 'lock',
                    'openInNewTab' => !empty($SYMB_UID),
                ];
            }
            $additionalResources[] = ['kind' => 'eml', 'title' => 'Collection metadata', 'description' => 'Ecological Metadata Language (EML)', 'url' => '../datasets/emlhandler.php?collid=' . $collid, 'action' => 'View'];
            if (!empty($collData['publishtogbif']) && $datasetKey) $additionalResources[] = ['title' => 'GBIF dataset', 'url' => 'https://www.gbif.org/dataset/' . $datasetKey];
            $dynamicProps = $decodeArray($collData['dynamicproperties'] ?? '');
            if (!empty($dynamicProps['edi']) && is_string($dynamicProps['edi'])) $additionalResources[] = ['title' => 'EDI dataset', 'url' => 'https://doi.org/' . preg_replace('/^doi:/i', '', $dynamicProps['edi'])];
            if (!empty($collData['publishtoidigbio'])) {
                $idigbioKey = $collManager->getIdigbioKey() ?: $collManager->findIdigbioKey($collData['recordid']);
                if ($idigbioKey) $additionalResources[] = ['title' => 'iDigBio dataset', 'url' => 'https://www.idigbio.org/portal/recordsets/' . $idigbioKey];
            }
            $distribution = null;
            if (($_REQUEST['stat'] ?? '') === 'geography') {
                $country = is_string($_REQUEST['country'] ?? null) ? $_REQUEST['country'] : '';
                $state = is_string($_REQUEST['state'] ?? null) ? $_REQUEST['state'] : '';
                $distribution = ['type' => 'geography', 'country' => $country, 'state' => $state, 'rows' => $collManager->getGeographyStats($country, $state)];
            } elseif (($_REQUEST['stat'] ?? '') === 'taxonomy') {
                $family = is_string($_REQUEST['family'] ?? null) ? $collManager->cleanOutStr($_REQUEST['family']) : '';
                $distribution = ['type' => 'taxonomy', 'family' => $family, 'rows' => $collManager->getTaxonomyStats($family)];
            }
            $profile = [
                'id' => (int) $collid,
                'title' => $collData['publicname'] ?: $collData['collectionname'],
                'code' => $collData['collectioncode'] ?? '',
                'description' => $collData['fulldescription'] ?? '',
                'identificationCollectionId' => (int) ($collData['identificationcollids'] ?? 0),
                'stats' => $stats,
                'extraStats' => $decodeArray($stats['dynamicProperties'] ?? ''),
                'citation' => $neonCitation,
                'citationText' => $neonCitationText,
                'related' => (object) $linkedCollections,
                'relatedCodes' => (object) $linkedCollectionCodes,
                'resources' => $resources,
                'additionalResources' => $additionalResources,
                'contacts' => array_values($decodeArray($collData['contactjson'] ?? '')),
                'datasetKey' => $datasetKey,
                'siteCodes' => $collManager->getNeonAvailabilitySiteCodes(),
                'distribution' => $distribution
            ];
            ?>
        <?php } else {
            $profile = ['collections' => []];
            foreach ($collData ?: [] as $cid => $collection) {
                $profile['collections'][] = ['id' => (int) $cid, 'title' => $collection['publicname'] ?: $collection['collectionname'], 'code' => $collection['collectioncode'] ?? ''];
            }
        } ?>
        <script id="neon-collection-profile-data" type="application/json"><?php
            echo json_encode(['profile' => $profile, 'clientRoot' => $CLIENT_ROOT, 'language' => $LANG_TAG], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE);
        ?></script>
        <div id="biorepo-collection-page-content"><p role="status">Loading sample type profile…</p></div>
        <noscript>Please enable JavaScript to view this sample type profile.</noscript>

    </div>
    <?php include($SERVER_ROOT . '/includes/footer.php'); ?>
</body>
</html>
