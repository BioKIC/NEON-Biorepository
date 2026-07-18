<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT . '/classes/OccurrenceDataset.php');
if ($LANG_TAG != 'en' && file_exists($SERVER_ROOT . '/content/lang/collections/datasets/datasetmanager.' . $LANG_TAG . '.php')) include_once($SERVER_ROOT . '/content/lang/collections/datasets/datasetmanager.' . $LANG_TAG . '.php');
else include_once($SERVER_ROOT . '/content/lang/collections/datasets/datasetmanager.en.php');
include_once($SERVER_ROOT.'/neon/classes/Utilities.php');

header("Content-Type: text/html; charset=" . $CHARSET);

if (!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../../neon/datasets/neondatasetmanager.php?' . htmlspecialchars($_SERVER['QUERY_STRING'], ENT_QUOTES));

$datasetId = $_REQUEST['datasetid'];
$tabIndex = array_key_exists('tabindex', $_REQUEST) ? $_REQUEST['tabindex'] : 0;
$action = array_key_exists('submitaction', $_REQUEST) ? $_REQUEST['submitaction'] : '';
$utilities = new Utilities();

//Sanitation
if (!is_numeric($datasetId)) $datasetId = 0;
if (!is_numeric($tabIndex)) $tabIndex = 0;
if ($action && !preg_match('/^[a-zA-Z0-9\s_]+$/', $action)) $action = '';

$datasetManager = new OccurrenceDataset();

$mdArr = $datasetManager->getDatasetMetadata($datasetId);
$role = '';
$roleLabel = '';
$isEditor = 0;
if ($SYMB_UID == $mdArr['uid']) {
	$isEditor = 1;
	$role = 'owner';
} elseif (isset($mdArr['roles'])) {
	if (in_array('DatasetAdmin', $mdArr['roles'])) {
		$isEditor = 1;
		$role = $LANG['ADMINISTRATOR'];
	} elseif (in_array('DatasetEditor', $mdArr['roles'])) {
		$isEditor = 2;
		$role = $LANG['EDITOR'];
		$roleLabel = $LANG['ROLE_LABEL_EDITOR'];
	} elseif (in_array('DatasetReader', $mdArr['roles'])) {
		$isEditor = 3;
		$role = $LANG['READ_ACCESS'];
	}
} elseif ($IS_ADMIN) {
	$isEditor = 1;
	$role = $LANG['SUPERADMIN'];
}

$statusStr = '';
if ($isEditor) {
	if ($isEditor < 3) {
		if ($action == 'Remove Selected Occurrences') {
			if ($datasetManager->removeSelectedOccurrences($datasetId, $_POST['occid'])) {
				//$statusStr = 'Selected occurrences removed successfully';
			} else {
				$statusStr = implode(',', $datasetManager->getErrorArr());
			}
		}
	}
	if ($isEditor == 1) {
		if ($action == 'Save Edits') {
			$isPublic = (isset($_POST['ispublic']) && is_numeric($_POST['ispublic']) ? 1 : 0);
			if ($datasetManager->editDataset($_POST['datasetid'], $_POST['name'], $_POST['notes'], $_POST['description'], $isPublic)) {
				$mdArr = $datasetManager->getDatasetMetadata($datasetId);
				$statusStr = $LANG['DS_EDITS_SAVED'];
			} else {
				$statusStr = implode(',', $datasetManager->getErrorArr());
			}
		} elseif ($action == 'Delete Dataset') {
			if ($datasetManager->deleteDataset($_POST['datasetid'])) {
				header('Location: index.php');
			} else {
				$statusStr = implode(',', $datasetManager->getErrorArr());
			}
		} elseif ($action == 'addUser') {
			if ($datasetManager->addUser($datasetId, $_POST['uid'], $_POST['role'])) {
				$statusStr = $LANG['USER_ADDED'];
			} else {
				$statusStr = implode(',', $datasetManager->getErrorArr());
			}
		} 
	}
}

?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET; ?>">
	<title><?php echo $DEFAULT_TITLE . ' ' . $LANG['DS_OCC_MANAGER']; ?></title>
	<link href="<?php echo $CSS_BASE_PATH; ?>/jquery-ui.css" type="text/css" rel="stylesheet">
	<?php
	include_once($SERVER_ROOT . '/includes/head.php');
	?>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-3.7.1.min.js" type="text/javascript"></script>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-ui.min.js" type="text/javascript"></script>
	<script type="text/javascript" src="../../js/symb/shared.js"></script>
	<script type="text/javascript" src="../../js/tinymce/tinymce.min.js"></script>
	<link rel="stylesheet" href="../../js/datatables/datatables.css" />
    <script src="../../js/datatables/datatables.js"></script>
	<script type="text/javascript">
		// Adds WYSIWYG editor to description field
		tinymce.init({
			selector: '#description',
			plugins: 'link lists image code',
			menubar: '',
			toolbar: ['undo redo | bold italic underline | link | alignleft aligncenter alignright | formatselect | bullist numlist | indent outdent | blockquote | image | code'],
			branding: false,
			default_link_target: "_blank",
			paste_as_text: true,
			invalid_styles: {
				'*': 'font-family'
			}
		});
		tinymce.init({
			selector: '#citation',
			plugins: 'link lists image',
			menubar: '',
			toolbar: ['undo redo | bold italic underline | link '],
			branding: false,
			default_link_target: "_blank",
			paste_as_text: true
		});
		$(document).ready(function () {
			$('#sampleTable').DataTable({
				pageLength: 25,
				order: [[1, 'asc']], // sort by Occurrence ID
				responsive: true,
				stateSave: true,
				columnDefs: [
					{
						orderable: false,
						searchable: false,
						targets: 0 // checkbox column
					}
				],
				layout: {
					topStart: {
						pageLength: {
							menu: [10,25,50,100,300,500,{label:'All',value:-1}]
						}
					},
					topEnd: 'search',
					bottomStart: 'info',
					bottomEnd: 'paging'
				}
			});
		});
	</script>
	<script type="text/javascript">
		var isDownloadAction = false;
		$(document).ready(function() {
			var dialogArr = new Array("schemanative", "schemadwc");
			var dialogStr = "";
			for (i = 0; i < dialogArr.length; i++) {
				dialogStr = dialogArr[i] + "info";
				$("#" + dialogStr + "dialog").dialog({
					autoOpen: false,
					modal: true,
					position: {
						my: "left top",
						at: "center",
						of: "#" + dialogStr
					}
				});

				$("#" + dialogStr).click(function() {
					$("#" + this.id + "dialog").dialog("open");
				});
			}

			$('#tabs').tabs({
				active: <?php echo $tabIndex; ?>,
				beforeLoad: function(event, ui) {
					$(ui.panel).html("<p><?php echo $LANG['LOADING']; ?>...</p>");
				}
			});

			$("#userinput").autocomplete({
				source: "../../collections/rpc/getuserlist.php",
				minLength: 3,
				autoFocus: true,
				select: function(event, ui) {
					$('#uid-add').val(ui.item.id);
				}
			});

		});

		function selectAll(cb) {
			boxesChecked = true;
			if (!cb.checked) {
				boxesChecked = false;
			}
			var dbElements = document.getElementsByName("occid[]");
			for (i = 0; i < dbElements.length; i++) {
				var dbElement = dbElements[i];
				dbElement.checked = boxesChecked;
			}
		}

		function validateDataSetForm(f) {
			var dbElements = document.getElementsByName("dsids[]");
			for (i = 0; i < dbElements.length; i++) {
				var dbElement = dbElements[i];
				if (dbElement.checked) return true;
			}
			alert("<?php echo $LANG['PLS_SELECT_DS']; ?>");

			var confirmStr = '';
			if (f.submitaction.value == "Merge") {
				confirmStr = '<?php echo $LANG['SURE_MERGE_DS']; ?>';
			} else if (f.submitaction.value == "Clone (make copy)") {
				confirmStr = '<?php echo $LANG['SURE_CLONE_DS']; ?>';
			} else if (f.submitaction.value == "Delete") {
				confirmStr = '<?php echo $LANG['SURE_DEL_DS']; ?>';
			}
			if (confirmStr == '') return true;
			return confirm(confirmStr);
		}

		function validateEditForm(f) {
			if (f.name.value == '') {
				alert("<?php echo $LANG['DS_NOT_NULL']; ?>");
				return false;
			}
			return true;
		}

		function validateOccurForm(f) {
			var occidChecked = false;
			var dbElements = document.getElementsByName("occid[]");
			for (i = 0; i < dbElements.length; i++) {
				var dbElement = dbElements[i];
				if (dbElement.checked) {
					occidChecked = true;
					break;
				}
			}
			if (!occidChecked) {
				alert("<?php echo $LANG['PLS_SEL_SPC']; ?>");
				return false;
			}
			if (isDownloadAction) {
				f.action = "../download/index.php";
				targetDownloadPopup(f);
			}
			return true;
		}

		function validateUserAddForm(f) {
			if (f.uid.value == "") {
				alert("<?php echo $LANG['SEL_USER_LIST']; ?>");
				return false;
			}
			return true;
		}

		function openIndPopup(occid) {
			openPopup("../individual/index.php?occid=" + occid);
		}

		function openPopup(urlStr) {
			var wWidth = 900;
			if (document.body.offsetWidth) wWidth = document.body.offsetWidth * 0.9;
			if (wWidth > 1200) wWidth = 1200;
			newWindow = window.open(urlStr, 'popup', 'scrollbars=1,toolbar=0,resizable=1,width=' + (wWidth) + ',height=600,left=20,top=20');
			if (newWindow.opener == null) newWindow.opener = self;
			newWindow.focus();
			return false;
		}

		function targetDownloadPopup(f) {
			window.open('', 'downloadpopup', 'left=100,top=50,width=900,height=700');
			f.target = 'downloadpopup';
		}

		document.addEventListener("DOMContentLoaded", function() {
			const adjustPagination = () => {
				const paginationLinks = document.querySelectorAll(".pagination-link");
				const screenWidth = window.innerWidth;
				let shouldReduceLinks = false;
				let shouldReduceByHalf = false;

				if (screenWidth < 770) {
					shouldReduceLinks = true;
				}
				if (screenWidth < 1200) {
					shouldReduceByHalf = true;
				}

				paginationLinks.forEach(link => {
					const shouldKeepLink = parseInt(link.getAttribute("data-keep-link"));
					const isEven = (parseInt(link.getAttribute("data-even-odd")) || 1) % 2;
					if (shouldReduceByHalf) {
						link.style.display = (isEven) ? "inline-block" : "none";
					}
					if (shouldReduceLinks) {
						link.style.display = (shouldKeepLink) ? "inline-block" : "none";
					}
					if (!shouldReduceByHalf && !shouldReduceLinks) {
						link.style.display = "inline-block";
					}
				});
			}

			window.addEventListener("resize", adjustPagination);
			adjustPagination();
		});
	</script>
	<style>
		.section-title {
			margin: 0px 15px;
			font-weight: bold;
			text-decoration: underline;
		}

		.contact-box {
			float: right;
			width: 300px;
			margin: 10px 20px 20px 20px;
			padding: 15px;
			border: 1px solid #ccc;
			border-radius: 8px;
			background-color: #f5f8fa;
			box-shadow: 0 2px 4px rgba(0,0,0,0.1);
		}

		.contact-box h2 {
			margin: 0;
			font-size: 1.1em;
			line-height: 1.4;
		}

		.contact-box a {
			color: #006699;
			text-decoration: none;
		}

		.contact-box a:hover {
			text-decoration: underline;
		}
	</style>
</head>

<body>
	<?php
	$displayLeftMenu = (isset($collections_datasets_indexMenu) ? $collections_datasets_indexMenu : false);
	include($SERVER_ROOT . '/includes/header.php');
	?>
	<div class='navpath'>
		<a href='../../index.php'><?php echo htmlspecialchars($LANG['HOME'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a> &gt;&gt;
		<a href="../../profile/viewprofile.php?tabindex=1"><?php echo htmlspecialchars($LANG['MY_PROF'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></a> &gt;&gt;
		<a href="index.php">
			<?php echo $LANG['RETURN_DS_LISTING']; ?>
		</a> &gt;&gt;
		<b><?php echo $LANG['DS_MANAGER']; ?></b>
	</div>
	<!-- This is inner text! -->
	<div role="main" id="innertext">
		<h1 class="page-heading"><?= $LANG['DS_OCC_MANAGER']; ?></h1>
		<?php
		if ($statusStr) {
			$color = 'green';
			if (strpos($statusStr, $LANG['ERROR']) !== false) $color = 'red';
			elseif (strpos($statusStr, $LANG['WARNING']) !== false) $color = 'orange';
			elseif (strpos($statusStr, $LANG['NOTICE']) !== false) $color = 'yellow';
			echo '<div style="margin:15px;color:' . $color . ';">';
			echo $statusStr;
			echo '</div>';
		}
		if ($datasetId) {
			echo "<a href='../datasets/public.php?datasetid=" . $datasetId . "'>View Dataset</a>";
			echo '<div style="margin:10px 0px 5px 20px;font-weight:bold;font-size:130%;">' . $mdArr['name'] . '</div>';
			if ($role) echo '<div style="margin-left:20px" title="' . $LANG['ROLE'] . '"' . $roleLabel . '>' . $LANG['ROLE'] . ': ' . $role . '</div>';
			if ($isEditor) {
		?>
				<div id="tabs" style="margin:10px;">
					<ul>
						<?php if ($isEditor == 1) { ?>
							<li><a href="#admintab"><span>Dataset Description</span></a></li>
						<?php } ?>

						<li><a href="#occurtab"><span>Samples</span></a></li>

						<?php if ($isEditor == 1) { ?>
							<li><a href="#accesstab"><span><?php echo htmlspecialchars($LANG['USER_ACCESS'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></span></a></li>
						<?php } ?>
					</ul>
					<div id="occurtab">
						<?php
						if ($occArr = $datasetManager->neonGetOccurrences($datasetId)) {
							$headerArr = ['occid','Domain', 'State','Site','Sample ID','Sample Code','IGSN ID','Scientific Name'];
						?>
							<div style="float:right;margin-right:10px">
								<?php echo '<b>' . $LANG['COUNT'] . ': ' . count($occArr) . ' ' . $LANG['RECORDS'] . '</b>'; ?>
							</div>
								<form name="occurform"
									action="neondatasetmanager.php"
									method="post"
									onsubmit="return validateOccurForm(this)">

									<div class="section">
										<h2>Submitted Samples</h2>

										<table id="sampleTable" class="styledtable display" style="width:100%;font-size:12px;">
											<thead>
												<tr>
													<th>
														<input type="checkbox"
															onclick="selectAll(this);"
															title="<?php echo $LANG['SEL_DESEL_SPCS']; ?>">
													</th>
													<th>occid</th>
													<th>Domain</th>
													<th>State</th>
													<th>Site</th>
													<th>Sample ID</th>
													<th>Sample Code</th>
													<th>IGSN ID</th>
													<th>Scientific Name</th>
												</tr>
											</thead>

											<tbody>
											<?php
											$i = 0;
											foreach ($occArr as $row) {
												$i++;
											?>
												<tr class="<?php echo ($i % 2 ? 'alt' : ''); ?>">
													<td>
														<input type="checkbox"
															name="occid[]"
															value="<?php echo $row['occid']; ?>">
													</td>

													<td>
														<a href="#"
														onclick="openIndPopup(<?php echo $row['occid']; ?>); return false;">
															<?php echo $row['occid']; ?>
														</a>
													</td>

													<td><?php echo htmlspecialchars($row['domain'] ?? ''); ?></td>
													<td><?php echo htmlspecialchars($row['stateProvince'] ?? ''); ?></td>
													<td><?php echo htmlspecialchars($row['siteID'] ?? ''); ?></td>
													<td><?php echo htmlspecialchars($row['sampleID'] ?? ''); ?></td>
													<td><?php echo htmlspecialchars($row['barcode'] ?? ''); ?></td>
													<td>
														<a href="<?php echo htmlspecialchars($row['IGSN_ID']); ?>"
														target="_blank">
															<?php echo htmlspecialchars($row['IGSN']); ?>
														</a>
													</td>
													<td><?php echo htmlspecialchars($row['scientificName']); ?></td>
												</tr>
											<?php } ?>
											</tbody>
										</table>
									</div>
									<div style="margin:15px;">
										<input name="datasetid" type="hidden" value="<?php echo $datasetId; ?>" />

										<?php if ($occArr && $isEditor < 3) { ?>
											<button type="submit"
													name="submitaction"
													value="Remove Selected Occurrences">
												<?php echo $LANG['REM_SEL_OCCS']; ?>
											</button>
										<?php } ?>
									</div>

								</form>
								<div style="margin: 15px;">
									<form name="exportAllForm" action="../collections/download/index.php" method="post" onsubmit="targetDownloadPopup(this)">
										<input name="searchvar" type="hidden" value="datasetid=<?php echo $datasetId; ?>" />
										<input name="dltype" type="hidden" value="specimen" />
										<button type="submit" name="submitaction" value="exportAll"><?php echo $LANG['EXPORT_DS']; ?></button>
									</form>
								</div>
							<?php
						} else {
						?>
							<div style="font-weight:bold; margin:15px"><?php echo $LANG['NO_OCCS_DS']; ?></div>
							<div style="margin:15px"><?php echo $LANG['LINK_OCCS_VIA'] . ' <a href="../index.php">' . htmlspecialchars($LANG['OCC_SEARCH'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a> ' . htmlspecialchars($LANG['OR_VIA_OCC_PROF'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></div>
						<?php
						}
						?>
					</div>
					<?php
					if ($isEditor == 1) {
					?>
						<div id="admintab">
							<section class="fieldset-like">
								<h2><span><b><?php echo $LANG['EDITOR']; ?></b></span></h2>
								<form name="editform" action="neondatasetmanager.php" method="post" onsubmit="return validateEditForm(this)">
									<div style="margin:25px 10px;">
										<label for="name">Title</label>
										<input name="name" id="name" type="text" value="<?php echo $mdArr['name']; ?>" aria-label="<?php echo $LANG['NAME']; ?>" style="width:70%" />
									</div>
									<div>
										<p>
											<input type="checkbox" name="ispublic" id="ispublic" value="1" aria-label="<?php echo $LANG['PUB_VISIBLE']; ?>" <?php echo ($mdArr['ispublic'] ? 'CHECKED' : ''); ?> />
											<!-- <b><?php echo $LANG['PUB_VISIBLE']; ?></b> -->
											<label for="ispublic"><?php echo $LANG['PUB_VISIBLE']; ?></label>
										</p>
									</div>
									<div style="margin:25px 10px;">
										<label for="notes"><?php echo $LANG['NOTES_INTERNAL']; ?></label>
										<input name="notes" id="notes" type="text" value="<?php echo $mdArr['notes']; ?>" style="width:70%" aria-label="<?php echo $LANG['NOTES_INTERNAL']; ?>" />
									</div>
									<div style="margin:15px;">
										<label for="description"><?php echo $LANG['DESCRIPTION'] . '</br>'; ?></label>
										<textarea name="description" id="description" cols="100" rows="10" style="width: 70%;" aria-label="<?php echo $LANG['DESCRIPTION']; ?>"><?php echo $mdArr['description']; ?></textarea>
									</div>
									<div style="margin:15px;">
										<label for="citation"><?php echo 'Citation<br>'; ?></label>
										<textarea name="citation" id="citation" cols="100" rows="10" style="width: 70%;" aria-label="<?php echo 'Citation'; ?>"><?php echo $mdArr['bibliographicCitation']; ?></textarea>
									</div>
									<div style="margin:15px;">
										<input name="tabindex" type="hidden" value="0" />
										<input name="datasetid" type="hidden" value="<?php echo $datasetId; ?>" />
										<button name="submitaction" type="submit" value="Save Edits"><?php echo $LANG['SAVE_EDITS']; ?></button>
									</div>
								</form>
									<?php if ($mdArr['category'] == "Request") { 
										?>
										<div style="margin:15px;">
										<?php $type = 'dataset'; ?>
										<?php $pubID = $datasetId; ?>
										<form action="<?php echo $CLIENT_ROOT; ?>/neon/requests/exporthandler.php" method="post">
											<input type="hidden" name="pubID" value="<?php echo $pubID; ?>" />
											<input type="hidden" name="type" value="<?php echo $type; ?>" />
											<input type="hidden" name="exportTask" value="pubtable" />
											<button type="submit">
												Export Publication-Ready Table
											</button>
										</form>
										</div>
									<?php
									};
									?>
							</section>
							<section class="fieldset-like">
								<h2><span><b><?php echo $LANG['DEL_DS']; ?></b></span></h2>
								<form name="editform" action="neondatasetmanager.php" method="post" onsubmit="return confirm('<?php echo $LANG['SURE_DEL_DS_PERM']; ?>')">
									<div style="margin:15px;">
										<input name="datasetid" type="hidden" value="<?php echo $datasetId; ?>" />
										<input name="tabindex" type="hidden" value="0" />
										<button class="button-danger" name="submitaction" type="submit" value="Delete Dataset"><?php echo $LANG['DEL_DS']; ?></button>
									</div>
								</form>
							</section>
						</div>
					<div id="accesstab">

						<div style="display:flex; gap:30px; align-items:flex-start; margin:25px 10px;">

							<div style="flex:1;">

								<?php
								$userArr = $datasetManager->getUsers($datasetId);

								$roleArr = array(
									'DatasetAdmin' => 'Full Access Users',
									'DatasetEditor' => 'Read/Write Users',
									'DatasetReader' => 'Read Only Users'
								);

								foreach ($roleArr as $roleStr => $labelStr) {
								?>

									<div class="section-title">
										<?php echo $labelStr; ?>
									</div>

									<div style="margin:15px;">

										<?php if (array_key_exists($roleStr, $userArr)) { ?>

											<ul>
												<?php
												foreach ($userArr[$roleStr] as $uid => $name) {
												?>

													<li>
														<?php echo htmlspecialchars($name); ?>

														<form 
															name="deluserform" 
															method="post" 
															action="neondatasetmanager.php" 
															style="display:inline;" 
															onsubmit="return confirm('<?php echo $LANG['SURE_REM_USER'] . ' ' . $name . '?'; ?>')">

															<input type="hidden" name="submitaction" value="DelUser" />
															<input type="hidden" name="role" value="<?php echo $roleStr; ?>" />
															<input type="hidden" name="uid" value="<?php echo $uid; ?>" />
															<input type="hidden" name="datasetid" value="<?php echo $datasetId; ?>" />
															<input type="hidden" name="tabindex" value="2" />

															<input 
																name="submitimage" 
																type="image" 
																src="../../images/drop.png" 
																style="width:1.2em" 
																alt="<?php echo $LANG['DROP_ICON']; ?>" />

														</form>

													</li>

												<?php
												}
												?>
											</ul>

										<?php } else { ?>

											<div style="margin:15px;">
												<?php echo $LANG['NONE_ASSIGNED']; ?>
											</div>

										<?php } ?>

									</div>

								<?php
								}
								?>

							</div>


							<div class="contact-box">

								<h2>
									<a href="https://www.neonscience.org/about/contact-neon-biorepository">
										Contact the NEON Biorepository to authorize additional users.
									</a>
								</h2>

							</div>

						</div>


						<?php if ($isEditor == 1) { ?>

							<div style="margin:15px;">

								<section class="fieldset-like">

									<h2>
										<span><b><?php echo $LANG['ADD_USER']; ?></b></span>
									</h2>

									<form 
										name="addform" 
										action="neondatasetmanager.php" 
										method="post" 
										onsubmit="return validateUserAddForm(this)">

										<div title="User">

											User 

											<input 
												id="userinput" 
												type="text" 
												style="width:400px;" 
												aria-label="User" />

											<input 
												id="uid-add" 
												name="uid" 
												type="hidden" 
												value="" />

										</div>


										<label for="role">
											<?php echo $LANG['ROLE']; ?>:
										</label>


										<select name="role" id="role">

											<option value="DatasetAdmin">
												<?php echo $LANG['FULL_ACCESS']; ?>
											</option>

											<option value="DatasetEditor">
												<?php echo $LANG['READ_WRITE_ACCESS']; ?>
											</option>

											<option value="DatasetReader">
												<?php echo $LANG['READ_ACCESS']; ?>
											</option>

										</select>


										<div style="margin:10px;">

											<input type="hidden" name="tabindex" value="2" />
											<input type="hidden" name="datasetid" value="<?php echo $datasetId; ?>" />

											<button 
												type="submit" 
												name="submitaction" 
												value="addUser">

												<?php echo $LANG['ADD_USER']; ?>

											</button>

										</div>

									</form>

								</section>

							</div>

						<?php } ?>

					</div>
					<?php
					}
					?>
				</div>
		<?php
			} else echo '<div style="margin:30px">' . $LANG['NOT_AUTH'] . '</div>';
		} else echo '<div><b>' . $LANG['DS_NOT_IDENTIFIED'] . '</b></div>';
		?>
	</div>
	<?php
	include($SERVER_ROOT . '/includes/footer.php');
	?>
</body>

</html>