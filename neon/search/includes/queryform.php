<?php
//include_once('../../../config/symbini.php');
include_once($SERVER_ROOT.'/content/lang/collections/editor/includes/queryform.en.php');

$customFieldArr = array('associatedCollectors'=>$LANG['ASSOC_COLLECTORS'],
						//'associatedOccurrences'=>$LANG['ASSOC_OCCS'],
						'associatedTaxa'=>$LANG['ASSOC_TAXA'],
						//'attributes'=>$LANG['ATTRIBUTES'],
						'scientificNameAuthorship'=>$LANG['AUTHOR'],
						'basisOfRecord'=>$LANG['BASIS_OF_RECORD'],
						//'behavior'=>$LANG['BEHAVIOR'],
						'catalogNumber'=>$LANG['CAT_NUM'],
						'collectionCode'=>$LANG['COL_CODE'],
						'recordNumber'=>$LANG['COL_NUMBER'],
						'recordedBy'=>$LANG['COL_OBS'],
						'coordinateUncertaintyInMeters'=>$LANG['COORD_UNCERT_M'],
						//'country'=>$LANG['COUNTRY'],
						'county'=>$LANG['COUNTY'],
						//'cultivationStatus'=>$LANG['CULT_STATUS'],
						//'dataGeneralizations'=>$LANG['DATA_GEN'],
						'eventDate'=>$LANG['DATE'],
						//'dateEntered'=>$LANG['DATE_ENTERED'],
						//'dateLastModified'=>$LANG['DATE_LAST_MODIFIED'],
						//'dbpk'=>$LANG['DBPK'],
						'decimalLatitude'=>$LANG['DEC_LAT'],
						'decimalLongitude'=>$LANG['DEC_LONG'],
						//'maximumDepthInMeters'=>$LANG['DEPTH_MAX'],
						//'minimumDepthInMeters'=>$LANG['DEPTH_MIN'],
						'verbatimAttributes'=>$LANG['DESCRIPTION'],
						'disposition'=>$LANG['DISPOSITION'],
						'dynamicProperties'=>$LANG['DYNAMIC_PROPS'],
						'maximumElevationInMeters'=>$LANG['ELEV_MAX_M'],
						'minimumElevationInMeters'=>$LANG['ELEV_MIN_M'],
						//'establishmentMeans'=>$LANG['ESTAB_MEANS'],
						//'family'=>$LANG['FAMILY'],
						//'fieldNotes'=>$LANG['FIELD_NOTES'],
						//'fieldnumber'=>$LANG['FIELD_NUMBER'],
						//'geodeticDatum'=>$LANG['GEO_DATUM'],
						//'georeferenceProtocol'=>$LANG['GEO_PROTOCOL'],
						//'georeferenceRemarks'=>$LANG['GEO_REMARKS'],
						//'georeferenceSources'=>$LANG['GEO_SOURCES'],
						//'georeferenceVerificationStatus'=>$LANG['GEO_VERIF_STATUS'],
						//'georeferencedBy'=>$LANG['GEO_BY'],
						'eventID'=>'Event ID',
						'habitat'=>$LANG['HABITAT'],
						'identificationQualifier'=>$LANG['ID_QUALIFIER'],
						'identificationReferences'=>$LANG['ID_REFERENCES'],
						'identificationRemarks'=>$LANG['ID_REMARKS'],
						'identifiedBy'=>$LANG['IDED_BY'],
						'individualCount'=>$LANG['IND_COUNT'],
						//'informationWithheld'=>$LANG['INFO_WITHHELD'],
						'institutionCode'=>$LANG['INST_CODE'],
						//'labelProject'=>$LANG['LAB_PROJECT'],
						//'language'=>$LANG['LANGUAGE'],
						'lifeStage'=>$LANG['LIFE_STAGE'],
						'locationid'=>$LANG['LOCATION_ID'],
						'locality'=>$LANG['LOCALITY'],
						//'localitySecurity'=>$LANG['LOC_SEC'],
						//'localitySecurityReason'=>$LANG['LOC_SEC_REASON'],
						//'locationRemarks'=>$LANG['LOC_REMARKS'],
						//'username'=>$LANG['MODIFIED_BY'],
						//'municipality'=>$LANG['MUNICIPALITY'],
						'occurrenceRemarks'=>$LANG['NOTES_REMARKS'],
						//'ocrFragment'=>$LANG['OCR_FRAGMENT'],
						'otherCatalogNumbers'=>$LANG['OTHER_CAT_NUMS'],
						'ownerInstitutionCode'=>$LANG['OWNER_CODE'],
						'preparations'=>$LANG['PREPARATIONS'],
						'reproductiveCondition'=>$LANG['REP_COND'],
						//'samplingEffort'=>$LANG['SAMP_EFFORT'],
						'samplingProtocol'=>$LANG['SAMP_PROTOCOL'],
						//'sciname'=>$LANG['SCI_NAME'],
						'sex'=>$LANG['SEX'],
						'stateProvince'=>$LANG['STATE_PROVINCE'],
						'substrate'=>$LANG['SUBSTRATE'],
						'taxonRemarks'=>$LANG['TAXON_REMARKS'],
						//'typeStatus'=>$LANG['TYPE_STATUS'],
						'verbatimCoordinates'=>$LANG['VERBAT_COORDS'],
						'verbatimEventDate'=>$LANG['VERBATIM_DATE'],
						//'verbatimDepth'=>$LANG['VERBATIM_DEPTH'],
						'verbatimElevation'=>$LANG['VERBATIM_ELE']);

$customTermArr = array('EQUALS', 'NOT_EQUALS', 'STARTS', 'LIKE', 'NOT_LIKE', 'GREATER', 'LESS', 'NULL', 'NOTNULL');
$customArr = array();

// sort($customFieldArr);

?>

<input type="checkbox" id="AdvancedHasBeenChanged" style=display:none>

<?php 
for($x=1; $x<9; $x++){
	$cAndOr = ''; $cOpenParen = ''; $cCloseParen = ''; $cField = ''; $cTerm = ''; $cValue = '';
	if(isset($customArr[$x]['andor'])) $cAndOr = $customArr[$x]['andor'];
	if(isset($customArr[$x]['openparen'])) $cOpenParen = $customArr[$x]['openparen'];
	if(isset($customArr[$x]['closeparen'])) $cCloseParen = $customArr[$x]['closeparen'];
	if(isset($customArr[$x]['field'])) $cField = $customArr[$x]['field'];
	if(isset($customArr[$x]['term'])) $cTerm = $customArr[$x]['term'];
	if(isset($customArr[$x]['value'])) $cValue = $customArr[$x]['value'];

	$divDisplay = 'none';
	if($x == 1 || $cValue != '' || $cTerm == 'NULL' || $cTerm == 'NOTNULL') $divDisplay = 'block';
	?>
	
	<div id="customdiv<?php echo $x; ?>" class="fieldGroupDiv" style="display:<?php echo $divDisplay; ?>;">
		<?php
		if($x > 1){
			?>
			<div class="select-container" style="width: 7%; display: inline-block;">
				<select name="q_customandor<?php echo $x; ?>" style="height:30px;">
					<option value="">---</option>
					<option value="AND"><?php echo $LANG['AND']; ?></option>
					<option <?php echo ($cAndOr == 'OR' ? 'SELECTED' : ''); ?> value="OR"><?php echo $LANG['OR']; ?></option>
				</select>
			</div>
			<?php
		}
		?>
		<div class="select-container" style="width: 5%; display: inline-block;">
			<select name="q_customopenparen<?php echo $x; ?>" style="height:30px;">
				<option value="">---</option>
				<?php
				echo '<option '.($cOpenParen == '(' ? 'SELECTED' : '').' value="(">(</option>';
				if($x < 7) echo '<option '.($cOpenParen == '((' ? 'SELECTED' : '').' value="((">((</option>';
				if($x < 8) echo '<option '.($cOpenParen == '(((' ? 'SELECTED' : '').' value="(((">(((</option>';
				?>
			</select>
			<!--<span class="assistive-text">Parentheses for advanced searches</span>-->
		</div>
		
		<div class="select-container" style="width: 25%; display: inline-block;">
			<select name="q_customfield<?php echo $x; ?>" style="height:30px;">
				<option value=""><?php echo $LANG['SELECT_FIELD_NAME']; ?></option>
				<option value="">---------------------------------</option>
				<?php
				foreach($customFieldArr as $k => $v){
					echo '<option value="'.$k.'" '.($k == $cField ? 'SELECTED' : '').'>'.$v.'</option>';
				}
				?>
			</select>
		</div>
		<div class="select-container" style="width: 21%; display: inline-block;">
			<select name="q_customtype<?php echo $x; ?>" style="height:30px;">
				<option value="">Select Statement</option>
				<option value="">---------------------------------</option>
				<?php
				foreach($customTermArr as $term){
					echo '<option '.($cTerm == $term ? 'SELECTED' : '').' value="'.$term.'">'.$LANG[$term].'</option>';
				}
				?>
			</select>
		</div>
		<div class="input-text-container" style="width: 30%; display: inline-block;">
			<label for="customvalue" class="input-text--outlined">
				<input name="q_customvalue<?php echo $x; ?>" type="text" value="<?php echo $cValue; ?>" style="height:30px" />
				<!--<span data-label="State"></span></label>-->
		</div>
		<div class="select-container" style="width: 5%; display: inline-block;">
			<select name="q_customcloseparen<?php echo $x; ?>" style="height:30px;">
				<option value="">---</option>
				<?php
				echo '<option '.($cCloseParen == ')' ? 'SELECTED' : '').' value=")">)</option>';
				if($x > 1) echo '<option '.($cCloseParen == '))' ? 'SELECTED' : '').' value="))">))</option>';
				if($x > 2) echo '<option '.($cCloseParen == ')))' ? 'SELECTED' : '').' value=")))">)))</option>';
				?>
			</select>
		</div>
		<div style="display: inline-block;">
			<a href="#" onclick="toggleCustomDiv(<?php echo ($x+1); ?>);return false;">
				<img class="editimg" src="../../images/editplus.png" />
			</a>
		</div>
	<div>
		<span class="assistive-text"><?php echo $LANG['CUSTOM_FIELD'].' '.$x; ?></span>
	</div>
	
	</div>
	<?php
}
?>

<script>
	
	function toggleCustomDiv(x){
		resetCustomElements(x);
		$('#customdiv'+x).toggle();
		if(x < 8){
			y = x + 1;
			resetCustomElements(y);
			document.getElementById('customdiv'+y).style.display = "none";
		}
	}

	function resetCustomElements(x){
		var f = document.getElementById("search-form-advanced-search");
		if(x < 9 && f.querySelector["q_customvalue" + x] && f.querySelector["q_customvalue" + x] != undefined){
			if(x > 1) f.querySelector["q_customandor" + x].options[0].selected = true;
			f.querySelector["q_customopenparen" + x].options[0].selected = true;
			f.querySelector["q_customfield" + x].options[0].selected = true;
			f.querySelector["q_customtype" + x].options[0].selected = true;
			f.querySelector["q_customvalue" + x].value = "";
			f.querySelector["q_customcloseparen" + x].options[0].selected = true;	
		}
	}
	
	
	const advancedInputs = document.querySelectorAll('#search-form-advanced-search select, #search-form-advanced-search input[type=text]');
	const advancedHasBeenChangedCheckbox = document.getElementById('AdvancedHasBeenChanged');
	
	advancedInputs.forEach((advancedInput) => {
		advancedInput.addEventListener('change', function(){
			let allDefault = true;
			
			advancedInputs.forEach((input) => {
				if (input.tagName === 'SELECT') {
					if (input.selectedIndex !== 0) {
						allDefault = false;
					}
				} else if (input.type === 'text') {
					if (input.value.trim() !== "") {
						allDefault = false;
					}
				}
			});
	
			advancedHasBeenChangedCheckbox.checked = !allDefault;
		});
	});

	
	
</script>