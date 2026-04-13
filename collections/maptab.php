<?php
//Custom NEON page
include_once('../config/symbini.php');
include_once($SERVER_ROOT . '/classes/OccurrenceListManager.php');

$collManager = new OccurrenceListManager();
$searchVar = $collManager->getQueryTermStr();
?>
<div id="maps" style="min-height:400px;margin-bottom:10px;">
	<div style='margin-top:10px;'>
		<h2>Map</h2>
	</div>
	<div>
		The maps feature provides users an interactive map that can pan (by dragging the mouse) and zoom (by using the mouse wheel).
		Collection points are displayed as colored markers that when clicked on, displays the full information for that collection. When multiple species are queried
		(separated by semi-colons), different colored markers denote each individual species.
	</div>
	<?php
	$mapParams = 'tabindex=3&gridSizeSetting=60&minClusterSetting=10&clusterSwitch=y&menuClosed&embedded=1';
	if (empty($searchVar) && !empty($_SERVER['QUERY_STRING'])) {
		$searchParams = '?' . $_SERVER['QUERY_STRING'] . '&' . $mapParams;
	} else {
		$searchParams = '?' . $searchVar . '&' . $mapParams;
	}
	$mapUrl = $CLIENT_ROOT . '/collections/map/index.php' . $searchParams;
	?>
	<div style="margin-top:10px;">
		<iframe
			src="<?= htmlspecialchars($mapUrl) ?>"
			width="100%"
			height="500"
			style="border:0;"
			scrolling="no">
		</iframe>
	</div>
	<form name="kmlform" action="map/kmlhandler.php" method="post">
		<div style="margin:10px 0;">
			<input name="searchvar" type="hidden" value="<?= $searchVar; ?>" />
			<button name="formsubmit" type="submit" value="createKML">Create KML</button>
		</div>
		<div>
			<a href="#" onclick="toggleFieldBox('fieldBox');">
				Add Extra Fields
			</a>
		</div>
		<div id="fieldBox" style="display:none;">
			<fieldset>
				<?php
				$occFieldArr = array(
					'occurrenceid',
					'identifiedby',
					'dateidentified',
					'identificationreferences',
					'identificationremarks',
					'taxonremarks',
					'recordedby',
					'recordnumber',
					'associatedcollectors',
					'eventdate',
					'year',
					'month',
					'day',
					'verbatimeventdate',
					'habitat',
					'substrate',
					'occurrenceremarks',
					'associatedtaxa',
					'verbatimattributes',
					'reproductivecondition',
					'cultivationstatus',
					'establishmentmeans',
					'lifestage',
					'sex',
					'individualcount',
					'samplingprotocol',
					'preparations',
					'country',
					'stateprovince',
					'county',
					'municipality',
					'locality',
					'locationremarks',
					'coordinateuncertaintyinmeters',
					'verbatimcoordinates',
					'georeferencedby',
					'georeferenceprotocol',
					'georeferencesources',
					'georeferenceverificationstatus',
					'georeferenceremarks',
					'minimumelevationinmeters',
					'maximumelevationinmeters',
					'verbatimelevation'
				);
				foreach ($occFieldArr as $v) {
					?>
					<div style="float:left;margin-right:5px;">
						<input type="checkbox" name="kmlFields[]" value="<?= $v ?>" /> <?= $v ?>
					</div>
					<?php
				}
				?>
			</fieldset>
		</div>
	</form>
</div>
