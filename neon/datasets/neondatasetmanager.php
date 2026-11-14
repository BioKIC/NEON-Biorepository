<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT . '/classes/OccurrenceDataset.php');
if ($LANG_TAG != 'en' && file_exists($SERVER_ROOT . '/content/lang/collections/datasets/datasetmanager.' . $LANG_TAG . '.php')) include_once($SERVER_ROOT . '/content/lang/collections/datasets/datasetmanager.' . $LANG_TAG . '.php');
else include_once($SERVER_ROOT . '/content/lang/collections/datasets/datasetmanager.en.php');
include_once($SERVER_ROOT.'/neon/classes/Utilities.php');
include_once($SERVER_ROOT.'/neon/classes/DatasetCitations.php');

header("Content-Type: text/html; charset=" . $CHARSET);

if (!$SYMB_UID) header('Location: ' . $CLIENT_ROOT . '/profile/index.php?refurl=' . $CLIENT_ROOT . '/neon/datasets/neondatasetmanager.php?' . htmlspecialchars($_SERVER['QUERY_STRING'], ENT_QUOTES));

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
$datasetArr = $datasetManager->getPublicDatasets();

// Dataset access levels:
// 1 = Full Access: NEON Biorepository staff/editors who can manage all project information,
//     samples, and user access.
// 2 = Read/Write: Project PIs who can edit project information and samples and view user access,
//     but cannot manage users.
// 3 = Read Only: Users who need access to view project information, citations, and sample data,
//     but cannot make changes or view user access.

$isEditor = 0;
if ($SYMB_UID == $mdArr['uid']) {
	$isEditor = 1;
} elseif (isset($mdArr['roles'])) {
	if (in_array('DatasetAdmin', $mdArr['roles'])) {
		$isEditor = 1;
	} elseif (in_array('DatasetEditor', $mdArr['roles'])) {
		$isEditor = 2;
	} elseif (in_array('DatasetReader', $mdArr['roles'])) {
		$isEditor = 3;
	}
} elseif ($IS_ADMIN) {
	$isEditor = 1;
}

$statusStr = '';
if ($isEditor) {
	if ($isEditor < 3) {
		if ($action == 'Remove Selected Samples') {
			if ($datasetManager->removeSelectedOccurrences($datasetId, $_POST['occid'])) {
				$statusStr = 'Samples removed successfully.';
			} else {
				$statusStr = implode(',', $datasetManager->getErrorArr());
			}
		} elseif ($action == 'Save Edits') {
			$isPublic = isset($_POST['ispublic']) ? (int)$_POST['ispublic'] : 0;

			if ($datasetManager->editDataset(
				$_POST['datasetid'],
				$_POST['name'],
				$_POST['notes'],
				$_POST['description'],
				$isPublic
			)) {
				$mdArr = $datasetManager->getDatasetMetadata($datasetId);
				$statusStr = $LANG['DS_EDITS_SAVED'];
			} else {
				$statusStr = implode(',', $datasetManager->getErrorArr());
			}
		}
	}

	if ($isEditor == 1) {
		if ($action == 'Delete Dataset') {
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
		} elseif ($action == 'DelUser') {
			if ($datasetManager->deleteUser($datasetId, $_POST['uid'], $_POST['role'])) {
				$statusStr = 'User access removed successfully.';
			} else {
				$statusStr = implode(',', $datasetManager->getErrorArr());
			}
		} elseif ($action == 'Link Dataset') {
			if ($datasetManager->addDatasetAssociation($datasetId, $_POST['associatedDatasetID'])) {
				$statusStr = 'Dataset associated successfully.';
			} else {
				$statusStr = implode(',', $datasetManager->getErrorArr());
			}
		}
	}
}

$adArr = $datasetManager->getAssociatedDatasets($datasetId);
?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET; ?>">
	<title>NEON Biorepository | Manage Project</title>

	<?php
	include_once($SERVER_ROOT . '/includes/head.php');
	?>
	<link rel="stylesheet" href="<?php echo htmlspecialchars($CLIENT_ROOT . '/neon/css/dataset-editor.css', ENT_QUOTES, $CHARSET); ?>" />
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-3.7.1.min.js" type="text/javascript"></script>
	<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-ui.min.js" type="text/javascript"></script>
	<script type="text/javascript" src="../../js/symb/shared.js"></script>
	<script type="text/javascript" src="../../js/tinymce/tinymce.min.js"></script>
	<link rel="stylesheet" href="../../js/datatables/datatables.css" />
    <script src="../../js/datatables/datatables.js"></script>
	<script type="text/javascript">

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

			$("#userinput").autocomplete({
				source: "../../collections/rpc/getuserlist.php",
				minLength: 3,
				autoFocus: true,
				appendTo: "body",
				position: { my: "left top+4", at: "left bottom", collision: "flipfit" },
				create: function() {
					$(this).autocomplete("widget").addClass("dataset-user-menu");
				},
				select: function(event, ui) {
					$('#uid-add').val(ui.item.id);
				},
				change: function(event, ui) {
					if (!ui.item) $('#uid-add').val('');
				}
			}).on('input', function() {
				$('#uid-add').val('');
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
			if (window.tinymce) tinymce.triggerSave();
			if (f.elements.namedItem('name').value.trim() == '') {
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

	function switchDatasetTab(button) {
		var tabs = button.closest('.MuiTabs-root');
		var buttons = tabs.querySelectorAll('[role="tab"]');
	
		buttons.forEach(function(tab) {
			var selected = tab === button;
			tab.classList.toggle('Mui-selected', selected);
			tab.setAttribute('aria-selected', String(selected));
			tab.tabIndex = selected ? 0 : -1;
		});
	
		document.querySelectorAll('.dataset-tab-content').forEach(function(panel) {
			panel.style.display = panel.id === button.dataset.tab ? 'block' : 'none';
		});
	
		var indicator = tabs.querySelector('.MuiTabs-indicator');
		var scroller = tabs.querySelector('.MuiTabs-scroller');
		var tabRect = button.getBoundingClientRect();
		var scrollerRect = scroller.getBoundingClientRect();
	
		indicator.style.left = (tabRect.left - scrollerRect.left + scroller.scrollLeft) + 'px';
		indicator.style.width = tabRect.width + 'px';
		if (button.dataset.tab === 'admintab') initializeDatasetEditors();
	}

    document.addEventListener('DOMContentLoaded', function() {
        var active = document.querySelector('.MuiTabs-root [role="tab"].Mui-selected')
            || document.querySelector('.MuiTabs-root [role="tab"]');

        if (active) switchDatasetTab(active);
    });

		async function copyDatasetCitation(button) {
			var card = button.closest('.dataset-citation-card');
			var status = card.querySelector('.dataset-citation-status');
			try {
				await navigator.clipboard.writeText(card.querySelector('.dataset-citation-text').textContent);
				status.textContent = 'Citation copied.';
			} catch (error) {
				status.textContent = 'Unable to copy automatically. Select and copy the citation above.';
			}
		}

		var datasetEditorsStarted = false;
		function initializeDatasetEditors() {
			var panel = document.getElementById('admintab');
			if (datasetEditorsStarted || !window.tinymce || !panel || panel.style.display !== 'block') return;

			// Moving an initialized iframe into the React layout clears its visible document.
			// Keep the textareas available until the page is in its final location.
			var usesReactLayout = <?php echo empty($DEACTIVATE_REACT) ? 'true' : 'false'; ?>;
			var content = document.querySelector('div[data-selenium="neon-page.content"]');
			if (usesReactLayout && (!content || !content.contains(document.getElementById('innertext')))) return;
			datasetEditorsStarted = true;

			['name', 'description'].forEach(function(id) {
				tinymce.init({
					selector: '#' + id,
					height: id === 'name' ? 150 : 300,
					plugins: 'link lists image code',
					menubar: false,
					toolbar: id === 'name'
						? 'undo redo | bold italic underline | code'
						: 'undo redo | bold italic underline | link | alignleft aligncenter alignright | bullist numlist | indent outdent | blockquote | code',
					branding: false,
					default_link_target: '_blank',
					forced_root_block: 'div',
					paste_as_text: true,
					invalid_styles: { '*': 'font-family' },
					content_css: [
						<?php echo json_encode($CLIENT_ROOT . '/js/tinymce/skins/content/default/content.min.css'); ?>,
						<?php echo json_encode($CLIENT_ROOT . '/neon/css/dataset-editor.css'); ?>
					],
					content_style: 'body { color: #333; background: #fff; margin: 16px; }',
					setup: function(editor) {
						editor.on('init', function() {
							// Exempt TinyMCE controls from the legacy page button rules.
							editor.getContainer().querySelectorAll('button').forEach(function(button) {
								button.classList.add('Mui');
							});
						});
					}
				});
			});
		}
		window.addEventListener('load', initializeDatasetEditors);
		window.addEventListener('biorepo:neon-page-ready', function() {
			// Run after all layout-ready listeners, regardless of their registration order.
			requestAnimationFrame(initializeDatasetEditors);
		});

	</script>
	<style>

		#innertext .dataset-manager-title {
			margin: 0 0 16px;
			font: 400 2.25rem/1.3 Inter, Helvetica, Arial, sans-serif;
			color: #000;
			text-transform: none;
		}
		#innertext .dataset-project-name {
			margin: 0 0 24px;
			font: 400 1.5rem/1.4 Inter, Helvetica, Arial, sans-serif;
			color: #333;
			overflow-wrap: anywhere;
		}
		#innertext .dataset-project-name > :first-child { margin-top: 0; }
		#innertext .dataset-project-name > :last-child { margin-bottom: 0; }
		#innertext .dataset-heading-divider {
			border: 0;
			border-top: 1px solid #d7d9d9;
			margin: 0 0 24px;
		}
		#innertext .dataset-info {
			border: 1px solid #b8d8f4;
			border-radius: 3px;
			background: #f4f8fd;
			color: #1a1a1a;
			padding: 24px;
			margin: 24px 0;
			font: 400 14px/1.6 Inter, Helvetica, Arial, sans-serif;
		}
		#innertext .dataset-info-inset { margin: 15px; }
		#innertext .dataset-info-header {
			display: flex;
			align-items: center;
			gap: 12px;
			margin-bottom: 16px;
		}
		#innertext .dataset-info-header h4 {
			margin: 0;
			font: 600 13px/1.5 Inter, Helvetica, Arial, sans-serif;
			color: inherit;
			text-transform: uppercase;
		}
		#innertext .dataset-info-icon {
			flex: 0 0 18px;
			width: 18px;
			height: 18px;
			color: #1a1a1a;
			fill: currentColor;
		}
		#innertext .dataset-info-badge {
			width: 32px;
			height: 32px;
			flex-shrink: 0;
			margin-left: auto;
			color: #8abfec;
			fill: currentColor;
		}
		#innertext .dataset-info p { margin: 0 0 12px; font: inherit; }
		#innertext .dataset-info p:last-child { margin-bottom: 0; }
		#innertext .dataset-info a { color: #0073cf; text-decoration: underline; }
		#innertext .dataset-info-with-actions .dataset-info-body {
			display: flex;
			align-items: center;
			flex-wrap: wrap;
			gap: 24px;
		}
		#innertext .dataset-info-message { flex: 1 1 400px; min-width: 0; }
		#innertext .dataset-info-actions { margin-left: auto; }
		#innertext .dataset-citation-guidance { background: #f7f3ef; border-color: #b18c70; }
		#innertext .dataset-citation-guidance .dataset-info-header { padding-bottom: 24px; margin-bottom: 24px; border-bottom: 1px solid #d7d9d9; }
		#innertext .dataset-citation-guidance .dataset-info-header h4 { font-size: 14px; text-transform: none; }
		#innertext .dataset-citation-guidance .dataset-info-icon { width: 32px; height: 32px; flex-basis: 32px; color: #b18c70; }
		#innertext .dataset-citation-steps { margin: 24px 15px; padding: 0; list-style: none; counter-reset: citation-step; }
		#innertext .dataset-citation-step { display: grid; grid-template-columns: 32px minmax(0, 1fr); gap: 16px; padding: 24px 0; border-bottom: 1px solid #d7d9d9; counter-increment: citation-step; }
		#innertext .dataset-citation-step:first-child { padding-top: 0; }
		#innertext .dataset-citation-step:last-child { border-bottom: 0; }
		#innertext .dataset-citation-step::before { content: counter(citation-step); display: flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 50%; color: #0073cf; background: #e6f3fc; font: 600 16px/1 Inter, Helvetica, Arial, sans-serif; }
		#innertext .dataset-citation-step h3 { margin: 2px 0 12px; color: #1a1a1a; font: 400 20px/1.4 Inter, Helvetica, Arial, sans-serif; }
		#innertext .dataset-citation-step p,
		#innertext .dataset-citation-step li { font: 400 14px/1.65 Inter, Helvetica, Arial, sans-serif; }
		#innertext .dataset-citation-step p { margin: 0 0 16px; }
		#innertext .dataset-citation-step form { margin: 20px 0 0; }
		#innertext .dataset-citation-checklist { margin: 0 0 20px; padding-left: 20px; }
		#innertext .dataset-citation-checklist li + li { margin-top: 12px; }
		#innertext .dataset-citation-card-header { display: flex; align-items: baseline; justify-content: space-between; flex-wrap: wrap; gap: 8px 24px; margin-bottom: 16px; }
		#innertext .dataset-citation-card-header h4 { margin: 0; font: 600 15px/1.5 Inter, Helvetica, Arial, sans-serif; color: #1a1a1a; }
		#innertext .dataset-citation-card-header a { font-size: 14px; white-space: nowrap; }
		#innertext .dataset-citation-card {
			background: #fff;
			padding: 24px;
			margin-bottom: 16px;
			border: 1px solid #d7d9d9;
			border-radius: 4px;
			overflow-wrap: anywhere;
		}
		#innertext .dataset-citation-step .dataset-citation-text {
			margin: 0;
			color: #1a1a1a;
			font: 400 .75rem/1.6 monospace;
		}
		#innertext .dataset-citation-actions {
			display: flex;
			flex-wrap: wrap;
			gap: 8px;
			margin-top: 24px;
		}
		#innertext .dataset-citation-actions .dataset-citation-button.MuiButton-outlined {
			padding: 5px 10px;
			font-size: .75rem;
			white-space: nowrap;
		}
		#innertext .dataset-citation-actions .dataset-citation-button svg { width: 16px; height: 16px; margin-right: 8px; }
		#innertext .dataset-citation-status { margin: 12px 0 0; font-size: 14px; }
		#innertext .dataset-citation-status:empty { margin: 0; }
		#innertext .dataset-project-nav { margin: 0 10px 8px; }
		#innertext .dataset-project-actions { border: 1px solid #d7d9d9; border-radius: 3px; margin: 0 10px 24px; }
		#innertext .dataset-project-actions > h2 {
			margin: 0;
			padding: 12px 20px;
			background: #2f78cf;
			border-bottom: 1px solid #2464ad;
			color: #fff;
			font: 600 14px/1.5 Inter, Helvetica, Arial, sans-serif;
			text-transform: uppercase;
			letter-spacing: .06em;
		}
		#innertext .dataset-project-action-row {
			display: flex;
			background: #fff;
			align-items: center;
			justify-content: space-between;
			flex-wrap: wrap;
			gap: 16px;
			padding: 16px 20px;
		}
		#innertext .dataset-project-action-row + .dataset-project-action-row { border-top: 1px solid #e4e6e7; }
		#innertext .dataset-project-action-row:nth-child(odd) { background: #f5f6f7; }
		#innertext .dataset-project-action-row h3 { margin: 0 0 4px; font: 600 14px/1.5 Inter, Helvetica, Arial, sans-serif; }
		#innertext .dataset-project-action-row p { margin: 0; color: #565a5c; font: 400 13px/1.5 Inter, Helvetica, Arial, sans-serif; }
		#innertext .dataset-manager-button {
			display: inline-flex;
			align-items: center;
			justify-content: center;
			box-sizing: border-box;
			min-width: 64px;
			padding: 8px 16px;
			border: 0;
			border-radius: 2px;
			font: 600 .7rem/1.75 Inter, Helvetica, Arial, sans-serif;
			letter-spacing: .06em;
			text-transform: uppercase;
			text-decoration: none;
			color: #fff;
			background: #0073cf;
			box-shadow: 0 3px 1px -2px rgb(0 0 0 / 20%), 0 2px 2px rgb(0 0 0 / 14%), 0 1px 5px rgb(0 0 0 / 12%);
			cursor: pointer;
			transition: background-color 150ms ease, box-shadow 150ms ease;
		}
		#innertext .dataset-manager-button:hover { background: #0092e2; }
		#innertext .dataset-project-action-row .dataset-manager-button,
		#innertext .dataset-project-nav .dataset-manager-button {
			width: auto;
			min-height: 36px;
			margin: 0;
			flex-shrink: 0;
			font-size: 11.2px;
			white-space: nowrap;
		}
		#innertext .dataset-project-nav .dataset-manager-return { padding: 8px 0; color: #0073cf; background: transparent; box-shadow: none; }
		#innertext .dataset-project-nav .dataset-manager-return:hover { color: #0092e2; text-decoration: underline; }
		#innertext .dataset-project-nav .MuiButton-startIcon { margin-left: 0; }
		#innertext .dataset-manager-button:focus-visible {
			outline: 2px solid #005dab;
			outline-offset: 3px;
		}
		#innertext .dataset-manager-button.MuiButton-outlined {
			padding: 7px 15px;
			border: 1px solid #0073cf;
			color: #0073cf;
			background: transparent;
			box-shadow: none;
		}
		#innertext .dataset-manager-button.MuiButton-outlined:hover { background: #e6f3fc; }
		#innertext .dataset-project-actions .MuiButton-outlined:hover {
			color: #0092e2;
			border-color: #0092e2;
			background: rgba(0, 115, 207, .04);
			text-decoration: underline;
		}
		#innertext .dataset-manager-button .MuiButton-icon { display: inline-flex; align-items: center; flex-shrink: 0; }
		#innertext .dataset-manager-button .MuiButton-startIcon { margin: 0 8px 0 -4px; }
		#innertext .dataset-manager-button .MuiButton-endIcon { margin: 0 -4px 0 8px; }
		#innertext .dataset-manager-button svg { width: 20px; height: 20px; fill: currentColor; }
		#innertext .dataset-manager-icon-button {
			color: #0073cf;
			background: transparent;
			border: 0;
			cursor: pointer;
		}
		#innertext .dataset-manager-icon-button:hover { background: #e6f3fc; }
		#innertext .dataset-manager-icon-button svg { width: 20px; height: 20px; fill: currentColor; }
		#innertext .dataset-visibility .MuiToggleButton-root {
			padding: 8px 16px;
			border: 1px solid #bbb;
			font: 600 .7rem/1.75 Inter, Helvetica, Arial, sans-serif;
			color: #565a5c;
			background: #fff;
			cursor: pointer;
		}
		#innertext .dataset-visibility .MuiToggleButton-root.Mui-selected {
			color: #0073cf;
			background: #e6f3fc;
		}
		#innertext .dataset-visibility .MuiToggleButton-root:hover { background: #e6f3fc; }
		#innertext .tinymce-wrapper { margin: 24px 15px; }
		#innertext .tinymce-wrapper > label { display: block; margin-bottom: 8px; color: #000; }
		#innertext .tinymce-wrapper .tox-tinymce { border-radius: 3px; }
		.dataset-manager-page .tox svg,
		.dataset-manager-page .tox .tox-tbtn__select-label { filter: none; }
		@media (prefers-reduced-motion: reduce) {
			#innertext .dataset-manager-button { transition: none; }
		}

		.dataset-manager-page .dataset-user-menu {
			position: absolute;
			z-index: 1500;
			box-sizing: border-box;
			max-height: 280px;
			overflow-y: auto;
			overflow-x: hidden;
			margin: 0;
			padding: 4px 0;
			list-style: none;
			border: 1px solid #b8c8d8;
			border-radius: 3px;
			background: #fff;
			box-shadow: 0 4px 12px rgb(0 0 0 / 16%);
			font: 400 14px/1.5 Inter, Helvetica, Arial, sans-serif;
		}
		.dataset-manager-page .dataset-user-menu .ui-menu-item { margin: 0; padding: 0; list-style: none; cursor: pointer; }
		.dataset-manager-page .dataset-user-menu .ui-menu-item-wrapper {
			display: block;
			box-sizing: border-box;
			min-height: 44px;
			margin: 0;
			padding: 12px 16px;
			border: 0;
			color: #1a1a1a;
			font: inherit;
			text-decoration: none;
			white-space: normal;
		}
		.dataset-manager-page .dataset-user-menu .ui-menu-item + .ui-menu-item { border-top: 1px solid #edf0f2; }
		.dataset-manager-page .dataset-user-menu .ui-menu-item-wrapper.ui-state-active,
		.dataset-manager-page .dataset-user-menu .ui-menu-item-wrapper:hover { color: #005dab; background: #e6f3fc; }
		.dataset-manager-page .ui-helper-hidden-accessible { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0; }

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

		.MuiTabs-root {
			box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
		}

		.MuiTabs-root .MuiTab-root {
			font-size: 0.7rem;
			flex: 1;
			max-width: none;
		}

		.MuiTabs-root .MuiTabs-flexContainer {
			width: 100%;
		}

		.sample-table {
			width: 100%;
			max-width: 100%;
			border-collapse: collapse;
			table-layout: auto;
			font-size: clamp(7px, 0.7vw, 12px);
			background: #fff;
		}

		.sample-table th {
			background: #2f78cf;
			color: #fff;
			font-weight: 700;
			text-transform: uppercase;
			letter-spacing: 0.03em;
			text-align: left;
			padding: clamp(4px, 0.6vw, 10px);
			border-right: 1px solid rgba(255, 255, 255, 0.35);
			vertical-align: middle;
			white-space: nowrap;
		}

		.sample-table th:last-child {
			border-right: none;
		}

		.sample-table td {
			padding: clamp(4px, 0.6vw, 10px);
			border-right: 1px solid #ddd;
			vertical-align: middle;
			white-space: nowrap;
		}

		.sample-table td:last-child {
			border-right: none;
		}

		.sample-table tbody tr:nth-child(even) {
			background-color: #f5f5f5;
		}

		.sample-table tbody tr:nth-child(odd) {
			background-color: #fff;
		}

		.sample-table tbody tr:hover {
			background-color: #eeeeee;
		}

		.sample-table a {
			color: #0073CF;
			text-decoration: underline;
			white-space: nowrap;
		}

		.sample-table a:hover {
			color: #0095D9;
		}

		.sample-table input[type="checkbox"] {
			cursor: pointer;
		}

		.sample-table th:first-child,
		.sample-table td:first-child {
			width: 30px;
			text-align: center;
			padding-left: 4px;
			padding-right: 4px;
		}
		.view-samples-container {
			padding-top: 0;
			padding-bottom: 20px;
			display: flex;
			align-items: center;
			justify-content: space-between;
			flex-wrap: wrap;
			gap: 16px;
		}
		#innertext .dataset-sample-count { margin: 0; font: 400 14px/1.6 Inter, Helvetica, Arial, sans-serif; }
	</style>
</head>

<body class="dataset-manager-page">
	<!-- This is inner text! -->
	<div role="main" id="innertext">
		<h1 class="dataset-manager-title">Manage Project</h1>
		<hr class="dataset-heading-divider" />
		<?php
		echo '<div class="dataset-project-name">' . $mdArr['name'] . '</div>';
		?>

		<?php if ($datasetId && $isEditor && $mdArr['ispublic'] == 1) { ?>
			<nav class="dataset-project-nav" aria-label="Project navigation">
				<a class="MuiButtonBase-root MuiButton-root MuiButton-text MuiButton-textPrimary MuiButton-sizeMedium MuiButton-textSizeMedium MuiButton-colorPrimary dataset-manager-button dataset-manager-return"
					href="<?php echo htmlspecialchars($CLIENT_ROOT . '/neon/datasets/neonpublic.php?datasetid=' . (int)$datasetId, ENT_QUOTES, $CHARSET); ?>">
					<span class="MuiButton-startIcon MuiButton-icon MuiButton-iconSizeMedium"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"></path></svg></span>
					View Public Page
				</a>
			</nav>
		<?php } ?>

		<?php if ($mdArr['category'] == "Request") {
			$rArr = $datasetManager->getRequestInquiryMetadata($datasetId);
			$agreementUrl = trim($rArr['sampleUseAgreementLink'] ?? '');
			$hasSampleUseAgreement = in_array(strtolower(parse_url($agreementUrl, PHP_URL_SCHEME) ?? ''), array('http', 'https'), true)
				&& strtolower(parse_url($agreementUrl, PHP_URL_HOST) ?? '') === 'drive.google.com';
		?>
			<section class="dataset-project-actions" aria-labelledby="dataset-project-actions-heading">
				<h2 id="dataset-project-actions-heading">Project Actions</h2>
				<div class="dataset-project-action-row">
					<div>
						<h3>Sample Use Agreement</h3>
						<p><?php echo $hasSampleUseAgreement ? 'Terms for using the samples in this project.' : 'No Sample Use Agreement is available for this project.'; ?></p>
					</div>
					<?php if ($hasSampleUseAgreement) { ?>
						<a class="MuiButtonBase-root MuiButton-root MuiButton-outlined MuiButton-outlinedPrimary MuiButton-sizeMedium MuiButton-outlinedSizeMedium MuiButton-colorPrimary dataset-manager-button"
							href="<?php echo htmlspecialchars($agreementUrl, ENT_QUOTES, $CHARSET); ?>" target="_blank" rel="noopener noreferrer">
							View Agreement
							<span class="MuiButton-endIcon MuiButton-icon MuiButton-iconSizeMedium"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 19H5V5h7V3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7h-2v7zM14 3v2h3.59l-9.83 9.83 1.41 1.41L19 6.41V10h2V3h-7z"></path></svg></span>
						</a>
					<?php } ?>
				</div>
				<?php if (!empty($rArr['id']) && $isEditor == 1) { ?>
					<div class="dataset-project-action-row">
						<div>
							<h3>Sample Request</h3>
							<p>Review and update the request associated with this project.</p>
						</div>
						<a class="MuiButtonBase-root MuiButton-root MuiButton-contained MuiButton-containedPrimary MuiButton-sizeMedium MuiButton-containedSizeMedium MuiButton-colorPrimary dataset-manager-button"
							href="../requests/inquiryform.php?id=<?php echo urlencode($rArr['id']); ?>" target="_blank" rel="noopener noreferrer">
							Manage Request
							<span class="MuiButton-endIcon MuiButton-icon MuiButton-iconSizeMedium"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 19H5V5h7V3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7h-2v7zM14 3v2h3.59l-9.83 9.83 1.41 1.41L19 6.41V10h2V3h-7z"></path></svg></span>
						</a>
					</div>
				<?php } ?>
			</section>
		<?php } ?>
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
			if ($isEditor) {
				?>
				<div id="tabs" style="margin:10px;padding:0;border:1px solid #e6e6e6;">

					<!-- DATASET TABS -->
					<div class="MuiTabs-root" style="position:relative;">
						<div class="MuiTabs-scroller MuiTabs-fixed" style="overflow:hidden;margin-bottom:0;">
							<div class="MuiTabs-list" role="tablist" aria-label="Dataset sections" style="display:flex;position:relative;">
					
								<button class="MuiButtonBase-root MuiTab-root MuiTab-textColorPrimary Mui-selected"
									type="button" role="tab" aria-selected="true" tabindex="0" data-tab="admintab"
									onclick="switchDatasetTab(this)">
									Description
								</button>
					
								<button class="MuiButtonBase-root MuiTab-root MuiTab-textColorPrimary"
									type="button" role="tab" aria-selected="false" tabindex="-1" data-tab="citationtab"
									onclick="switchDatasetTab(this)">
									Citation
								</button>
					
								<button class="MuiButtonBase-root MuiTab-root MuiTab-textColorPrimary"
									type="button" role="tab" aria-selected="false" tabindex="-1" data-tab="occurtab"
									onclick="switchDatasetTab(this)">
									Samples
								</button>
					
								<button class="MuiButtonBase-root MuiTab-root MuiTab-textColorPrimary"
									type="button" role="tab" aria-selected="false" tabindex="-1" data-tab="linkedtab"
									onclick="switchDatasetTab(this)">
									Related Datasets
								</button>
					
								<?php if ($isEditor < 3) { ?>
									<button class="MuiButtonBase-root MuiTab-root MuiTab-textColorPrimary"
										type="button" role="tab" aria-selected="false" tabindex="-1" data-tab="accesstab"
										onclick="switchDatasetTab(this)">
										<?php echo htmlspecialchars($LANG['USER_ACCESS'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?>
									</button>
								<?php } ?>
					
							</div>
					
							<span class="MuiTabs-indicator" style="position:absolute;bottom:0;left:0;width:0;transition:left 300ms ease,width 300ms ease;"></span>
						</div>
					</div>
					<div id="occurtab" class="dataset-tab-content">
						<?php
						if ($occArr = $datasetManager->neonGetOccurrences($datasetId)) {
							$headerArr = ['occid','Domain', 'State','Site','Sample ID','Sample Code','IGSN ID','Scientific Name'];
							?>
							<form name="occurform"
								action="neondatasetmanager.php?datasetid=<?php echo (int)$datasetId; ?>"
								method="post"
								onsubmit="return validateOccurForm(this)">

								<div class="section" style="margin:15px;">
									<section class="dataset-info dataset-info-with-actions" aria-labelledby="dataset-sample-guidance">
										<div class="dataset-info-header">
											<svg class="dataset-info-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M11 7h2v2h-2zm0 4h2v6h-2zm1-9C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"></path></svg>
											<h4 id="dataset-sample-guidance">Managing Project Samples</h4>
											<svg class="dataset-info-badge" viewBox="0 0 24 24" aria-hidden="true"><path d="M11 7h2v2h-2zm0 4h2v6h-2zm1-9C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"></path></svg>
										</div>
										<div class="dataset-info-body">
											<div class="dataset-info-message">
												<p>Samples that will not be used for this project may be removed from the list.</p>
												<p>If additional samples need to be added, please contact the NEON Biorepository. The request will need to be reviewed and the Sample Use Agreement updated before additional samples can be added.</p>
											</div>
											<div class="dataset-info-actions">
												<input name="datasetid" type="hidden" value="<?php echo (int)$datasetId; ?>" />
												<?php if ($occArr && $isEditor < 3) { ?>
													<button class="MuiButtonBase-root MuiButton-root MuiButton-contained MuiButton-containedPrimary MuiButton-sizeMedium MuiButton-containedSizeMedium MuiButton-colorPrimary dataset-manager-button"
														type="submit" name="submitaction" value="Remove Selected Samples"><?php echo $LANG['REM_SEL_OCCS']; ?></button>
												<?php } ?>
											</div>
										</div>
									</section>
									<div class="view-samples-container">
										<p class="dataset-sample-count">
											<?php echo $LANG['COUNT'] . ': ' . count($occArr) . ' ' . $LANG['RECORDS']; ?>
										</p>
										<a
											class="MuiButtonBase-root MuiButton-root MuiButton-outlined MuiButton-outlinedPrimary MuiButton-sizeMedium MuiButton-outlinedSizeMedium MuiButton-colorPrimary dataset-manager-button"
											href="../../collections/list.php?datasetid=<?php echo $datasetId; ?>"
											target="_blank"
										>
											<span class="button-text">Explore Samples</span>
											<span class="MuiButton-endIcon MuiButton-icon MuiButton-iconSizeMedium" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"></path></svg></span>
										</a>
									</div>
									<table id="sampleTable" class="Mui sample-table">
										<thead>
											<tr>
												<th>
													<input
														type="checkbox"
														onclick="selectAll(this);"
														title="<?php echo $LANG['SEL_DESEL_SPCS']; ?>"
													>
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
											<tr>
												<td>
													<input
														type="checkbox"
														name="occid[]"
														value="<?php echo $row['occid']; ?>"
													>
												</td>

												<td>
													<a href="#" onclick="openIndPopup(<?php echo $row['occid']; ?>); return false;">
														<?php echo $row['occid']; ?>
													</a>
												</td>

												<td><?php echo htmlspecialchars($row['domain'] ?? ''); ?></td>
												<td><?php echo htmlspecialchars($row['stateProvince'] ?? ''); ?></td>
												<td><?php echo htmlspecialchars($row['siteID'] ?? ''); ?></td>
												<td><?php echo htmlspecialchars($row['sampleID'] ?? ''); ?></td>
												<td><?php echo htmlspecialchars($row['barcode'] ?? ''); ?></td>

												<td>
													<a href="<?php echo htmlspecialchars($row['IGSN_ID']); ?>" target="_blank">
														<?php echo htmlspecialchars($row['IGSN']); ?>
													</a>
												</td>

												<td><?php echo htmlspecialchars($row['scientificName']); ?></td>
											</tr>
										<?php } ?>
										</tbody>
									</table>
								</div>
							</form>
							<?php
						} else {
							?>
							<div style="font-weight:bold; margin:15px"><?php echo $LANG['NO_OCCS_DS']; ?></div>
							<div style="margin:15px"><?php echo $LANG['LINK_OCCS_VIA'] . ' <a href="../index.php">' . htmlspecialchars($LANG['OCC_SEARCH'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE) . '</a> ' . htmlspecialchars($LANG['OR_VIA_OCC_PROF'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></div>
							<?php
						}
						?>
					</div>
					<div id="citationtab" class="dataset-tab-content">
						<section class="dataset-info dataset-info-inset dataset-citation-guidance" aria-labelledby="dataset-citation-guidance">
							<div class="dataset-info-header">
								<svg class="dataset-info-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"></path></svg>
								<h4 id="dataset-citation-guidance">Citing This Project</h4>
							</div>
							<div class="dataset-info-body">
								<p>Your publication needs a citation for <strong>each sample type you used</strong>. Follow the steps below to prepare them.</p>
								<?php if ($mdArr['category'] == 'Request') { ?>
									<p><strong>For physical sample use,</strong> include both the sample table and the data citations. <strong>For data-only use,</strong> skip the physical sample table and start with <a href="#dataset-citations-heading">sample data citations</a>.</p>
								<?php } ?>
								<p>See <a href="../../misc/cite.php" target="_blank" rel="noopener noreferrer">Acknowledging and Citing the NEON Biorepository</a> for complete requirements, including citations for additional NEON data products and sample images.</p>
							</div>
						</section>
						<ol class="dataset-citation-steps" role="list">
							<?php if ($mdArr['category'] == 'Request') { ?>
								<li class="dataset-citation-step">
									<div>
										<h3>List the Physical Samples You Used (if any)</h3>
										<p>Download the table, check that it lists the samples used in your research, and include it in your publication's <strong>main text [preferred] or supplemental information</strong>.</p>
										<p>Use the occurrenceID (e.g., <a href="https://doi.org/10.58052/NEON03MVF" target="_blank" rel="noopener noreferrer">https://doi.org/10.58052/NEON03MVF</a>) or full IGSN ID (e.g., <a href="https://doi.org/10.58052/NEON0D0YI" target="_blank" rel="noopener noreferrer">igsn:10.58052/NEON0D0YI</a> or <a href="https://doi.org/10.58052/NEON0D0YI" target="_blank" rel="noopener noreferrer">igsn:NEON0D0YI</a>) when referring to individual samples in the text.</p>
										<form action="<?php echo $CLIENT_ROOT; ?>/neon/requests/exporthandler.php" method="post">
											<input type="hidden" name="pubID" value="<?php echo (int)$datasetId; ?>" />
											<input type="hidden" name="type" value="dataset" />
											<input type="hidden" name="exportTask" value="pubtable" />
											<button class="MuiButtonBase-root MuiButton-root MuiButton-contained MuiButton-containedPrimary MuiButton-sizeMedium MuiButton-containedSizeMedium MuiButton-colorPrimary dataset-manager-button" type="submit">
												<span>Download Physical Sample Citations Table (CSV)</span>
												<span class="MuiButton-endIcon MuiButton-icon MuiButton-iconSizeMedium" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M19 12v7H5v-7H3v7c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2v-7zm-6 .67 2.59-2.58L17 11.5l-5 5-5-5 1.41-1.41L11 12.67V3h2z"></path></svg></span>
											</button>
										</form>
									</div>
								</li>
							<?php } ?>
							<li class="dataset-citation-step">
								<div class="dataset-citations">
									<h3 id="dataset-citations-heading">Add the Sample Data Citations to Your References</h3>
									<p>For each sample type used in your research, <strong>include it in your references</strong>.</p>
									<?php
									$citations = array();
									$citationLoadFailed = false;
									try {
										$citations = DatasetCitations::fromCollections($datasetManager->getDatasetCitationCollections($datasetId));
									} catch (RuntimeException $error) {
										$citationLoadFailed = true;
										error_log($error->getMessage());
									}
									?>
									<?php if ($citationLoadFailed) { ?>
										<p role="alert">Sample data citations could not be loaded. Please reload this page or <a href="https://www.neonscience.org/about/contact-neon-biorepository" target="_blank" rel="noopener noreferrer">contact the Biorepository</a>.</p>
									<?php } elseif (!$citations) { ?>
										<p>No sample types are linked to this project yet. Add records in the Samples tab to generate their citations.</p>
									<?php } ?>
									<?php foreach ($citations as $citation) { ?>
										<article class="dataset-citation-card">
											<div class="dataset-citation-card-header">
												<h4><?php echo htmlspecialchars($citation['title'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></h4>
												<a href="<?php echo $CLIENT_ROOT; ?>/collections/misc/neoncollprofiles.php?collid=<?php echo (int)$citation['collectionId']; ?>" target="_blank" rel="noopener noreferrer">View Sample Type Profile</a>
											</div>
											<p class="dataset-citation-text"><?php echo htmlspecialchars($citation['text'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></p>
											<div class="dataset-citation-actions">
												<button type="button" class="MuiButtonBase-root MuiButton-root MuiButton-outlined MuiButton-outlinedPrimary MuiButton-sizeSmall MuiButton-outlinedSizeSmall MuiButton-colorPrimary dataset-manager-button dataset-citation-button" onclick="copyDatasetCitation(this)"
													title="Copy this citation to the clipboard">
													<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z"></path></svg>
													Copy
												</button>
												<?php foreach (array('bib' => 'BibTeX', 'ris' => 'RIS') as $format => $label) {
													$download = DatasetCitations::download($citation, $format);
												?>
													<a class="MuiButtonBase-root MuiButton-root MuiButton-outlined MuiButton-outlinedPrimary MuiButton-sizeSmall MuiButton-outlinedSizeSmall MuiButton-colorPrimary dataset-manager-button dataset-citation-button"
														href="<?php echo htmlspecialchars($download['href'], ENT_QUOTES, 'UTF-8'); ?>"
														download="<?php echo htmlspecialchars($download['filename'], ENT_QUOTES, 'UTF-8'); ?>"
														title="Download this citation as <?php echo $label; ?>">
														<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 12v7H5v-7H3v7c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2v-7zm-6 .67 2.59-2.58L17 11.5l-5 5-5-5 1.41-1.41L11 12.67V3h2z"></path></svg>
														Download (<?php echo $label; ?>)
													</a>
												<?php } ?>
											</div>
											<p class="dataset-citation-status" role="status" aria-live="polite"></p>
										</article>
									<?php } ?>
								</div>
							</li>
							<li class="dataset-citation-step">
								<div>
									<h3>Check Your Citations Before Submitting</h3>
									<ul class="dataset-citation-checklist">
										<li><strong>Replace [date downloaded]</strong> in each citation with the date you downloaded the data used in your research.</li>
										<li><strong>Describe any changes to the data.</strong> Edit or omit the filtering and reformatting sentence as appropriate, and remove its bracketed instruction.</li>
										<li><strong>Replace [your DOI]</strong> with the DOI for your archived data. Archiving the data used in your analysis is recommended; if you have not archived them, follow the <a href="../../misc/cite.php#h.3" target="_blank" rel="noopener noreferrer">citation example for data without an archive</a>.</li>
										<li><strong>If you used physical samples, add the required acknowledgment</strong> to your publication. <a href="../../misc/cite.php#h.1" target="_blank" rel="noopener noreferrer">Get the acknowledgment text</a>.</li>
									</ul>
								</div>
							</li>
						</ol>
					</div>
					<div id="admintab" class="dataset-tab-content">
						<form name="editform" action="neondatasetmanager.php?datasetid=<?php echo (int)$datasetId; ?>" method="post" onsubmit="return validateEditForm(this)">

							<!--Visibility section-->
							<div style="display:flex; align-items:center; gap:80px; margin:20px 0; padding-left:15px;">
								<div style="font-weight:bold;">
									Visibility
								</div>

								<div class="MuiToggleButtonGroup-root MuiToggleButtonGroup-horizontal dataset-visibility"
									 role="group" aria-label="Dataset visibility">
								
									<input type="hidden" name="ispublic" id="ispublic"
										   value="<?php echo ($mdArr['ispublic'] ? '1' : '0'); ?>" />
								
									<!-- PUBLIC -->
									<button
										class="MuiButtonBase-root MuiToggleButtonGroup-grouped MuiToggleButton-root MuiToggleButton-sizeMedium MuiToggleButton-standard MuiToggleButtonGroup-grouped MuiToggleButtonGroup-firstButton<?php echo ($mdArr['ispublic'] ? ' Mui-selected' : ''); ?>"
										type="button" value="1"
										tabindex="<?php echo ($mdArr['ispublic'] ? '0' : '-1'); ?>"
										aria-pressed="<?php echo ($mdArr['ispublic'] ? 'true' : 'false'); ?>"
										onclick="
											var group=this.closest('.MuiToggleButtonGroup-root');
											group.querySelectorAll('.MuiToggleButton-root').forEach(function(b){
												b.classList.remove('Mui-selected');
												b.setAttribute('aria-pressed','false');
												b.setAttribute('tabindex','-1');
											});
											this.classList.add('Mui-selected');
											this.setAttribute('aria-pressed','true');
											this.setAttribute('tabindex','0');
											group.querySelector('input[name=ispublic]').value=this.value;
										">
										Public
									</button>
								
									<!-- PRIVATE -->
									<button
										class="MuiButtonBase-root MuiToggleButtonGroup-grouped MuiToggleButton-root MuiToggleButton-sizeMedium MuiToggleButton-standard MuiToggleButtonGroup-grouped MuiToggleButtonGroup-lastButton<?php echo (!$mdArr['ispublic'] ? ' Mui-selected' : ''); ?>"
										type="button" value="0"
										tabindex="<?php echo (!$mdArr['ispublic'] ? '0' : '-1'); ?>"
										aria-pressed="<?php echo (!$mdArr['ispublic'] ? 'true' : 'false'); ?>"
										onclick="
											var group=this.closest('.MuiToggleButtonGroup-root');
											group.querySelectorAll('.MuiToggleButton-root').forEach(function(b){
												b.classList.remove('Mui-selected');
												b.setAttribute('aria-pressed','false');
												b.setAttribute('tabindex','-1');
											});
											this.classList.add('Mui-selected');
											this.setAttribute('aria-pressed','true');
											this.setAttribute('tabindex','0');
											group.querySelector('input[name=ispublic]').value=this.value;
										">
										Private
									</button>
								</div>
								<?php
								if ($mdArr['ispublic'] == 0) {
								?>
									<div style="color:#666; font-size:0.9em;">
										Make this project visible to the public
									</div>
								<?php
								} 
								?>

							</div>
							<div style="padding:0 15px;">
								<hr class="MuiDivider-root MuiDivider-fullWidth" />
							</div>
							<!--Title Section-->
							<div class="tinymce-wrapper">
								<label for="name">Title</label>
								<textarea name="name" id="name" rows="3" style="width:100%; box-sizing:border-box;" aria-label="<?php echo $LANG['NAME']; ?>"><?php echo htmlspecialchars($mdArr['name'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></textarea>
							</div>
							<div style="padding:0 15px;">
								<hr class="MuiDivider-root MuiDivider-fullWidth" />
							</div>
							<!--Notes Section-->
							<div class="MuiFormControl-root MuiTextField-root" style="width:98%; margin:25px 10px;">
								<label
									class="MuiFormLabel-root MuiInputLabel-root MuiInputLabel-formControl MuiInputLabel-animated MuiInputLabel-outlined<?php echo !empty($mdArr['notes']) ? ' MuiInputLabel-shrink MuiFormLabel-filled' : ''; ?>"
									data-shrink="<?php echo !empty($mdArr['notes']) ? 'true' : 'false'; ?>"
									style="<?php echo !empty($mdArr['notes']) ? 'background-color:#fff;' : ''; ?>"
								>
									<?php echo $LANG['NOTES_INTERNAL']; ?>
								</label>

								<textarea
									name="notes"
									id="notes"
									aria-label="<?php echo $LANG['NOTES_INTERNAL']; ?>"
									class="MuiInputBase-input MuiOutlinedInput-input MuiInputBase-inputMultiline MuiOutlinedInput-inputMultiline"
									rows="3"
									style="border:1px solid rgba(0, 0, 0, 0.23); outline:none; width:100%; box-sizing:border-box; padding:18.5px 14px;"
									onfocus="
										var label = this.previousElementSibling;

										label.classList.add('MuiInputLabel-shrink', 'Mui-focused');
										label.setAttribute('data-shrink', 'true');
										label.style.backgroundColor = '#fff';

										this.style.borderColor = '#0073CF';
										this.style.borderWidth = '2px';
									"
									onblur="
										var label = this.previousElementSibling;

										label.classList.remove('Mui-focused');

										this.style.borderColor = 'rgba(0, 0, 0, 0.23)';
										this.style.borderWidth = '1px';

										if (this.value !== '') {
											label.classList.add('MuiInputLabel-shrink', 'MuiFormLabel-filled');
											label.setAttribute('data-shrink', 'true');
										} else {
											label.classList.remove('MuiInputLabel-shrink', 'MuiFormLabel-filled');
											label.setAttribute('data-shrink', 'false');
											label.style.backgroundColor = '';
										}
									"
								><?php echo htmlspecialchars($mdArr['notes'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></textarea>
							</div>
							<div style="padding:0 15px;">
								<hr class="MuiDivider-root MuiDivider-fullWidth" />
							</div>
							<!--Description Section-->
							<div class="tinymce-wrapper">
								<label for="description">Description</label>
								<textarea name="description" id="description" cols="100" rows="10" style="width: 100%;" aria-label="<?php echo $LANG['DESCRIPTION']; ?>"><?php echo htmlspecialchars($mdArr['description'], ENT_COMPAT | ENT_HTML401 | ENT_SUBSTITUTE); ?></textarea>
							</div>
							<div style="margin:15px; text-align:right;">
								<input name="tabindex" type="hidden" value="0" />
								<input name="datasetid" type="hidden" value="<?php echo $datasetId; ?>" />

								<?php
								if ($isEditor < 3) {
									?>

									<button
										class="MuiButtonBase-root MuiButton-root MuiButton-contained MuiButton-containedPrimary MuiButton-sizeMedium MuiButton-containedSizeMedium MuiButton-colorPrimary dataset-manager-button"
										tabindex="0"
										name="submitaction"
										type="submit"
										value="Save Edits"
									>
										<span><?php echo $LANG['SAVE_EDITS']; ?></span>
									
										<span
											class="MuiButton-endIcon MuiButton-icon MuiButton-iconSizeMedium"
											style="display: inline-flex; align-items: center; justify-content: center; margin-top: 0; margin-bottom: 0;"
										>
											<svg
												class="MuiSvgIcon-root MuiSvgIcon-fontSizeMedium"
												focusable="false"
												aria-hidden="true"
												viewBox="0 0 24 24"
												style="display: block;"
											>
												<path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"></path>
											</svg>
										</span>
									</button>

									<?php
								}
								?>
							</div>
						</form>
						<!--<form name="editform" action="neondatasetmanager.php?datasetid=<?php echo (int)$datasetId; ?>" method="post" onsubmit="return confirm('<?php echo $LANG['SURE_DEL_DS_PERM']; ?>')">-->
						<!--	<div style="margin:15px;">-->
						<!--		<input name="datasetid" type="hidden" value="<?php echo $datasetId; ?>" />-->
						<!--		<input name="tabindex" type="hidden" value="0" />-->
						<!--		<button class="button-danger" name="submitaction" type="submit" value="Delete Dataset"><?php echo $LANG['DEL_DS']; ?></button>-->
						<!--	</div>-->
						<!--</form>-->
					</div>
					<?php
					if ($isEditor < 3) {
						?>
						<div id="accesstab" class="dataset-tab-content">
							<div style="padding:15px;">
								<?php
								$userArr = $datasetManager->getUsers($datasetId);

								$roleArr = array(
									'DatasetAdmin' => 'Full Access Users',
									'DatasetEditor' => 'Read/Write Users',
									'DatasetReader' => 'Read Only Users'
								);
								?>

								<section class="dataset-info" aria-labelledby="dataset-access-guidance">
									<div class="dataset-info-header">
										<svg class="dataset-info-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M11 7h2v2h-2zm0 4h2v6h-2zm1-9C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"></path></svg>
										<h4 id="dataset-access-guidance">Authorizing User Access</h4>
										<svg class="dataset-info-badge" viewBox="0 0 24 24" aria-hidden="true"><path d="M11 7h2v2h-2zm0 4h2v6h-2zm1-9C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"></path></svg>
									</div>
									<div class="dataset-info-body">
										<p>Contact the <a href="https://www.neonscience.org/about/contact-neon-biorepository" target="_blank" rel="noopener noreferrer">NEON Biorepository</a> to authorize additional users.</p>
									</div>
								</section>

								<div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:20px; margin-bottom:25px;">
									<?php
									foreach ($roleArr as $roleStr => $labelStr) {
										?>
										<div
											class="MuiPaper-root MuiCard-root MuiPaper-outlined MuiPaper-rounded"
											style="padding:18px;"
										>
											<h5
												class="MuiTypography-root MuiTypography-h5"
												style="margin:0 0 12px 0;"
											>
												<?php echo $labelStr; ?>
											</h5>

											<hr class="MuiDivider-root uiDivider-fullWidth" style="margin-bottom:12px;">

											<?php
											if (array_key_exists($roleStr, $userArr)) {
												?>

												<ul
													class="MuiList-root"
													style="margin:0; padding:0; list-style:none;"
												>
													<?php
													foreach ($userArr[$roleStr] as $uid => $name) {
														?>

														<li
															class="MuiListItem-root"
															style="
																display:flex;
																align-items:center;
																justify-content:space-between;
																padding:8px;
															"
														>
															<span class="MuiTypography-root MuiTypography-body1">
																<?php echo htmlspecialchars($name); ?>
															</span>
															<?php
															if ($isEditor == 1) {
																?>
																<form
																	name="deluserform"
																	method="post"
																	action="neondatasetmanager.php?datasetid=<?php echo (int)$datasetId; ?>"
																	style="display:inline;"
																	onsubmit="return confirm('<?php echo $LANG['SURE_REM_USER'] . ' ' . $name . '?'; ?>')"
																>
																	<input type="hidden" name="submitaction" value="DelUser" />
																	<input type="hidden" name="role" value="<?php echo $roleStr; ?>" />
																	<input type="hidden" name="uid" value="<?php echo $uid; ?>" />
																	<input type="hidden" name="datasetid" value="<?php echo $datasetId; ?>" />
																	<input type="hidden" name="tabindex" value="2" />

<button
    type="submit"
    class="MuiButtonBase-root MuiIconButton-root MuiIconButton-colorPrimary MuiIconButton-sizeSmall dataset-manager-icon-button"
    title="Remove Access"
    aria-label="Remove Access"
    style="padding: 8px; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%;"
>
    <svg
        aria-hidden="true"
        class="MuiSvgIcon-root MuiSvgIcon-fontSizeMedium"
        focusable="false"
        viewBox="0 0 24 24"
        style="display: block;"
    >
        <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM8 9h8v10H8V9zm7.5-5-1-1h-5l-1 1H5v2h14V4z"></path>
    </svg>
</button>
																</form>
																<?php
															}
															?>
														</li>
														<?php
													}
													?>
												</ul>
												<?php
											} else {
												?>
												<p
													class="MuiTypography-root MuiTypography-body1"
													style="margin:0; color:#666;"
												>
													<?php echo $LANG['NONE_ASSIGNED']; ?>
												</p>
												<?php
											}
											?>
										</div>
										<?php
									}
									?>
								</div>

								<?php
								if ($isEditor == 1) {
									?>
									<div>
										<div
											class="MuiTypography-root MuiTypography-body1"
											style="line-height:1.6; color:#555;"
										>
											<div style="font-weight:bold; margin-bottom:6px;">
												Dataset access levels:
											</div>

											<div style="margin-left:20px; margin-bottom:4px;">
												<strong>Full Access:</strong>
												NEON Biorepository staff/editors who can manage all project information,
												samples, and user access.
											</div>

											<div style="margin-left:20px; margin-bottom:4px;">
												<strong>Read/Write:</strong>
												Project PIs who can edit project information and samples and view user access,
												but cannot manage users.
											</div>

											<div style="margin-left:20px; margin-bottom:10px;">
												<strong>Read Only:</strong>
												Users who can view project information, citations, and sample data,
												but cannot make changes or view user access.
											</div>
										</div>
									</div>

									<div
										class="MuiPaper-root MuiCard-root MuiPaper-outlined MuiPaper-rounded"
										style="padding:20px;"
									>
										<h5
											class="MuiTypography-root MuiTypography-h5"
											style="margin:0 0 20px 0;"
										>
											Add User Access
										</h5>

										<form
											name="addform"
											action="neondatasetmanager.php?datasetid=<?php echo (int)$datasetId; ?>"
											method="post"
											onsubmit="return validateUserAddForm(this)"
										>
											<div style="display:flex; justify-content:space-between; align-items:flex-start; width:100%;">

												<div style="width:60%;">
													<div class="MuiFormControl-root MuiTextField-root" style="width:100%; position:relative;">
														<label for="userinput" id="userinput-label"
															class="MuiFormLabel-root MuiInputLabel-root MuiInputLabel-formControl MuiInputLabel-animated MuiInputLabel-outlined MuiFormLabel-colorPrimary"
															data-shrink="false"
															style="position:absolute; top:0; left:0; z-index:1; pointer-events:none; transform:translate(14px,16px) scale(1); transform-origin:top left; transition:transform 200ms ease;">
															User Name
														</label>
												
														<div class="MuiInputBase-root MuiOutlinedInput-root MuiInputBase-colorPrimary MuiInputBase-formControl" style="position:relative;">
															<input id="userinput" type="text" aria-labelledby="userinput-label"
																class="MuiInputBase-input MuiOutlinedInput-input"
																onfocus="
																	var root=this.parentElement, label=root.previousElementSibling, legend=root.querySelector('legend');
																	root.classList.add('Mui-focused');
																	label.classList.add('Mui-focused','MuiInputLabel-shrink');
																	label.setAttribute('data-shrink','true');
																	label.style.transform='translate(14px,-9px) scale(0.75)';
																	legend.style.maxWidth='100%';
																"
																onblur="
																	var root=this.parentElement, label=root.previousElementSibling, legend=root.querySelector('legend');
																	root.classList.remove('Mui-focused');
																	label.classList.remove('Mui-focused');
																	if (!this.value.trim()) {
																		label.classList.remove('MuiInputLabel-shrink');
																		label.setAttribute('data-shrink','false');
																		label.style.transform='translate(14px,16px) scale(1)';
																		legend.style.maxWidth='0.01px';
																	}
																"
															/>
															<fieldset aria-hidden="true" class="MuiOutlinedInput-notchedOutline">
																<legend style="float:unset; width:auto; overflow:hidden; display:block; height:11px; padding:0; font-size:0.75em; visibility:hidden; white-space:nowrap; max-width:0.01px; transition:max-width 200ms ease;">
																	<span style="padding:0 5px; display:inline-block; opacity:0;">User Name</span>
																</legend>
															</fieldset>
														</div>
												
														<input id="uid-add" name="uid" type="hidden" value="" />
													</div>
												</div>

												<div style="width:35%; min-width:220px;">
<!-- ROLE DROPDOWN -->
<div class="MuiFormControl-root" style="width:100%; min-width:220px; position:relative;">

    <label id="role-label"
        class="MuiFormLabel-root MuiInputLabel-root MuiInputLabel-formControl MuiInputLabel-animated MuiInputLabel-outlined MuiFormLabel-colorPrimary"
        data-shrink="false"
        style="position:absolute; top:0; left:0; z-index:1; pointer-events:none; transform:translate(14px,16px) scale(1); transform-origin:top left; transition:transform 200ms ease;">
        <?php echo $LANG['ROLE']; ?>
    </label>

    <div class="MuiInputBase-root MuiOutlinedInput-root MuiInputBase-colorPrimary MuiInputBase-formControl MuiSelect-root"
        style="position:relative; width:100%; min-width:220px; height:56px; box-sizing:border-box;">

        <div class="MuiSelect-select MuiSelect-outlined MuiInputBase-input MuiOutlinedInput-input"
            tabindex="0"
            role="combobox"
            aria-labelledby="role-label"
            aria-expanded="false"
            aria-haspopup="listbox"
            style="display:flex; align-items:center; width:100%; min-height:56px; box-sizing:border-box; padding:0 32px 0 14px; cursor:pointer;"

            onclick="
                var root=this.parentElement,
                    group=root.parentElement,
                    label=group.querySelector('label'),
                    menu=group.querySelector('[role=listbox]'),
                    legend=root.querySelector('legend'),
                    input=group.querySelector('input[name=role]');

                var open=this.getAttribute('aria-expanded')!=='true';
                var shrink=open || !!input.value;

                this.setAttribute('aria-expanded',String(open));

                menu.style.opacity=open?'1':'0';
                menu.style.visibility=open?'visible':'hidden';
                menu.style.transform=open?'scaleY(1)':'scaleY(0.92)';
                menu.style.pointerEvents=open?'auto':'none';

                root.classList.toggle('Mui-focused',open);
                label.classList.toggle('Mui-focused',open);
                label.classList.toggle('MuiInputLabel-shrink',shrink);
                label.setAttribute('data-shrink',String(shrink));
                label.style.transform=shrink
                    ?'translate(14px,-9px) scale(0.75)'
                    :'translate(14px,16px) scale(1)';
                legend.style.maxWidth=shrink?'100%':'0.01px';
            "

            onkeydown="
                if(event.key==='Enter' || event.key===' '){
                    event.preventDefault();
                    this.click();
                }
                if(event.key==='Escape' && this.getAttribute('aria-expanded')==='true'){
                    event.preventDefault();
                    this.click();
                }
            "
        ><span class="notranslate" aria-hidden="true">&#8203;</span></div>

        <input type="hidden" name="role" id="role" class="MuiSelect-nativeInput" value="" />

        <svg class="MuiSvgIcon-root MuiSvgIcon-fontSizeMedium MuiSelect-icon"
            focusable="false"
            aria-hidden="true"
            viewBox="0 0 24 24"
            style="pointer-events:none; transition:transform 180ms ease;">
            <path d="M7 10l5 5 5-5z"></path>
        </svg>

        <fieldset aria-hidden="true" class="MuiOutlinedInput-notchedOutline">
            <legend style="float:unset; width:auto; overflow:hidden; display:block; height:11px; padding:0; font-size:0.75em; visibility:hidden; white-space:nowrap; max-width:0.01px; transition:max-width 200ms ease;">
                <span style="padding:0 5px; display:inline-block; opacity:0;">
                    <?php echo $LANG['ROLE']; ?>
                </span>
            </legend>
        </fieldset>
    </div>

    <!-- DROPDOWN MENU -->
    <div role="listbox"
        aria-labelledby="role-label"
        class="MuiPaper-root MuiPaper-elevation MuiPaper-rounded MuiMenu-paper MuiPopover-paper"
        style="
            display:block;
            position:absolute;
            top:100%;
            left:0;
            width:100%;
            z-index:1300;
            background:#fff;
            box-shadow:0 5px 15px rgba(0,0,0,0.2);
            border-radius:4px;
            max-height:300px;
            overflow-y:auto;
            opacity:0;
            visibility:hidden;
            transform:scaleY(0.92);
            transform-origin:top center;
            transition:opacity 180ms ease, transform 180ms ease, visibility 180ms;
            pointer-events:none;
        ">

        <ul class="MuiList-root MuiList-padding MuiMenu-list"
            style="list-style:none; margin:0; padding:8px 0;">

            <?php
            $roles = [
                'DatasetAdmin'  => $LANG['FULL_ACCESS'],
                'DatasetEditor' => $LANG['READ_WRITE_ACCESS'],
                'DatasetReader' => $LANG['READ_ACCESS']
            ];

            foreach ($roles as $value => $text):
            ?>
                <li class="MuiButtonBase-root MuiMenuItem-root MuiMenuItem-gutters"
                    role="option"
                    aria-selected="false"
                    tabindex="-1"
                    data-value="<?php echo htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); ?>"
                    style="cursor:pointer; padding:6px 16px; min-height:36px;"

                    onmouseenter="this.style.background='rgba(0,0,0,0.04)'"
                    onmouseleave="this.style.background=''"

                    onclick="
                        var group=this.closest('.MuiFormControl-root'),
                            root=group.querySelector('.MuiSelect-root'),
                            select=root.querySelector('[role=combobox]'),
                            input=group.querySelector('input[name=role]'),
                            label=group.querySelector('label'),
                            menu=group.querySelector('[role=listbox]');

                        input.value=this.dataset.value;
                        select.textContent=this.textContent.trim();
                        select.setAttribute('aria-expanded','false');

                        menu.style.opacity='0';
                        menu.style.visibility='hidden';
                        menu.style.transform='scaleY(0.92)';
                        menu.style.pointerEvents='none';

                        root.classList.remove('Mui-focused');
                        label.classList.remove('Mui-focused');
                        label.classList.add('MuiInputLabel-shrink');
                        label.setAttribute('data-shrink','true');
                        label.style.transform='translate(14px,-9px) scale(0.75)';
                        root.querySelector('legend').style.maxWidth='100%';

                        menu.querySelectorAll('[role=option]').forEach(function(option){
                            option.classList.remove('Mui-selected');
                            option.setAttribute('aria-selected','false');
                        });

                        this.classList.add('Mui-selected');
                        this.setAttribute('aria-selected','true');

                        input.dispatchEvent(new Event('change',{bubbles:true}));
                        select.focus();
                    "
                ><?php echo htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); ?></li>
            <?php endforeach; ?>

        </ul>
    </div>
</div>
												</div>

											</div>


											<div style="display:flex; justify-content:flex-end; margin-top:20px;">
												<input type="hidden" name="tabindex" value="2" />
												<input type="hidden" name="datasetid" value="<?php echo $datasetId; ?>" />

												<button
													class="MuiButtonBase-root MuiButton-root MuiButton-contained MuiButton-containedPrimary MuiButton-sizeMedium MuiButton-containedSizeMedium MuiButton-colorPrimary dataset-manager-button"
													type="submit"
													name="submitaction"
													value="addUser"
												>
													<span><?php echo $LANG['ADD_USER']; ?></span>
												
													<span
														class="MuiButton-endIcon MuiButton-icon MuiButton-iconSizeMedium"
														style="display: inline-flex; align-items: center; justify-content: center; margin-top: 0; margin-bottom: 0;"
													>
														<svg
															class="MuiSvgIcon-root MuiSvgIcon-fontSizeMedium"
															focusable="false"
															aria-hidden="true"
															viewBox="0 0 24 24"
															style="display: block;"
														>
															<path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"></path>
														</svg>
													</span>
												</button>
											</div>

										</form>
									</div>
									<?php
								}
								?>
							</div>
						</div>
						<?php
					}
					?>
					<div id="linkedtab" class="dataset-tab-content">
						<!--Linked Datasets section-->
						<div style="margin:15px; text-align:left;">
							<input name="tabindex" type="hidden" value="0" />
							<input name="datasetid" type="hidden" value="<?php echo $datasetId; ?>" />

							<?php
							if (!empty($adArr)) {
								?>
								<div style="padding:0 15px;">
									<hr class="MuiDivider-root MuiDivider-fullWidth" />
								</div>
								<div style="font-weight:bold;margin-top: 20px;">
									Linked Datasets
								</div>

								<div>
									<ul class="MuiList-root MuiList-padding">
										<?php foreach ($adArr as $ad) { ?>
											<a href="../datasets/neonpublic.php?datasetid=<?php echo $ad['datasetID']; ?>"
												class="MuiButtonBase-root MuiListItem-root MuiListItem-gutters MuiListItem-button"
												style="text-decoration:none; color:inherit;">

											<div class="MuiListItemIcon-root">
												<svg aria-hidden="true" class="MuiSvgIcon-root" focusable="false" viewBox="0 0 24 24">
													<path d="M3.9 12c0-1.71 1.39-3.1 3.1-3.1h4V7H7a5 5 0 0 0 0 10h4v-1.9H7A3.1 3.1 0 0 1 3.9 12zM8 13h8v-2H8v2zm9-6h-4v1.9h4a3.1 3.1 0 1 1 0 6.2h-4V17h4a5 5 0 0 0 0-10z"></path>
													</svg>
											</div>

												<div class="MuiListItemText-root">
													<span class="MuiTypography-root MuiListItemText-primary MuiTypography-body1 MuiTypography-displayBlock">
														<?php echo htmlspecialchars(strip_tags($ad['name'])); ?>
													</span>
												</div>

												<span class="MuiTouchRipple-root"></span>
											</a>
										<?php } ?>
									</ul>
								</div>
								<?php
							}
							if ($isEditor == 1) {
								?>
								<div style="padding:0 15px;">
									<form name="linkdatasetform" action="neondatasetmanager.php?datasetid=<?php echo (int)$datasetId; ?>" method="post">
										<input name="datasetid" type="hidden" value="<?php echo $datasetId; ?>"/>
										<div style="margin-top:15px;">
											<div style="display:flex; align-items:center; gap:10px; width:100%;">

												<!-- SELECT DATASET -->
												<div class="MuiFormControl-root" style="width:80%;max-width:700px;min-width:220px;position:relative;">
													<label id="associated-dataset-label" class="MuiFormLabel-root MuiInputLabel-root MuiInputLabel-formControl MuiInputLabel-animated MuiInputLabel-outlined MuiFormLabel-colorPrimary" data-shrink="false"
														style="position:absolute;top:0;left:0;z-index:1;pointer-events:none;transform:translate(14px,16px) scale(1);transform-origin:top left;transition:transform 200ms ease;">Select Dataset</label>
												
													<div class="MuiInputBase-root MuiOutlinedInput-root MuiInputBase-colorPrimary MuiInputBase-formControl MuiSelect-root" style="position:relative;width:100%;height:56px;box-sizing:border-box;">
														<div class="MuiSelect-select MuiSelect-outlined MuiInputBase-input MuiOutlinedInput-input" tabindex="0" role="combobox" aria-labelledby="associated-dataset-label" aria-expanded="false" aria-haspopup="listbox" aria-controls="associated-dataset-menu"
															style="display:flex;align-items:center;width:100%;min-height:56px;box-sizing:border-box;padding:0 32px 0 14px;cursor:pointer;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;"
															onclick="
																var root=this.parentElement,group=root.parentElement,label=group.querySelector('label'),menu=group.querySelector('[role=listbox]'),legend=root.querySelector('legend'),input=group.querySelector('input[name=associatedDatasetID]');
																var open=this.getAttribute('aria-expanded')!=='true',shrink=open||!!input.value;
																this.setAttribute('aria-expanded',String(open));
																menu.style.opacity=open?'1':'0';menu.style.visibility=open?'visible':'hidden';menu.style.transform=open?'scaleY(1)':'scaleY(0.92)';menu.style.pointerEvents=open?'auto':'none';
																root.classList.toggle('Mui-focused',open);label.classList.toggle('Mui-focused',open);label.classList.toggle('MuiInputLabel-shrink',shrink);
																label.setAttribute('data-shrink',String(shrink));label.style.transform=shrink?'translate(14px,-9px) scale(0.75)':'translate(14px,16px) scale(1)';legend.style.maxWidth=shrink?'100%':'0.01px';
															"
															onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();this.click();}if(event.key==='Escape'&&this.getAttribute('aria-expanded')==='true'){event.preventDefault();this.click();}"
														><span class="notranslate" aria-hidden="true">&#8203;</span></div>
												
														<input type="hidden" name="associatedDatasetID" id="associatedDatasetID" class="MuiSelect-nativeInput" value="">
														<svg class="MuiSvgIcon-root MuiSvgIcon-fontSizeMedium MuiSelect-icon" focusable="false" aria-hidden="true" viewBox="0 0 24 24" style="pointer-events:none;"><path d="M7 10l5 5 5-5z"></path></svg>
														<fieldset aria-hidden="true" class="MuiOutlinedInput-notchedOutline">
															<legend style="float:unset;width:auto;overflow:hidden;display:block;height:11px;padding:0;font-size:0.75em;visibility:hidden;white-space:nowrap;max-width:0.01px;transition:max-width 200ms ease;">
																<span style="padding:0 5px;display:inline-block;opacity:0;">Select Dataset</span>
															</legend>
														</fieldset>
													</div>
												
													<div id="associated-dataset-menu" role="listbox" aria-labelledby="associated-dataset-label" class="MuiPaper-root MuiPaper-elevation MuiPaper-rounded MuiMenu-paper MuiPopover-paper"
														style="display:block;position:absolute;top:56px;left:0;width:100%;z-index:1300;background:#fff;box-shadow:0 5px 15px rgba(0,0,0,.2);border-radius:4px;max-height:300px;overflow-y:auto;opacity:0;visibility:hidden;transform:scaleY(.92);transform-origin:top center;transition:opacity 180ms ease,transform 180ms ease,visibility 180ms;pointer-events:none;">
												
														<ul class="MuiList-root MuiList-padding MuiMenu-list" style="list-style:none;margin:0;padding:8px 0;"
															onclick="
																var item=event.target.closest('[role=option]');if(!item)return;
																var group=this.closest('.MuiFormControl-root'),root=group.querySelector('.MuiSelect-root'),select=root.querySelector('[role=combobox]'),input=group.querySelector('input[name=associatedDatasetID]'),label=group.querySelector('label'),menu=group.querySelector('[role=listbox]');
																var selected=!!item.dataset.value;
																input.value=item.dataset.value;
																select.textContent=selected?item.textContent.trim():'';
																if(!selected)select.innerHTML='<span class=&quot;notranslate&quot; aria-hidden=&quot;true&quot;>&#8203;</span>';
																select.setAttribute('aria-expanded','false');
																menu.style.opacity='0';menu.style.visibility='hidden';menu.style.transform='scaleY(0.92)';menu.style.pointerEvents='none';
																root.classList.remove('Mui-focused');label.classList.remove('Mui-focused');label.classList.toggle('MuiInputLabel-shrink',selected);
																label.setAttribute('data-shrink',String(selected));label.style.transform=selected?'translate(14px,-9px) scale(0.75)':'translate(14px,16px) scale(1)';
																root.querySelector('legend').style.maxWidth=selected?'100%':'0.01px';
																this.querySelectorAll('[role=option]').forEach(function(o){o.classList.toggle('Mui-selected',o===item);o.setAttribute('aria-selected',String(o===item));});
																input.dispatchEvent(new Event('change',{bubbles:true}));select.focus();
															">
															<li class="MuiButtonBase-root MuiMenuItem-root MuiMenuItem-gutters" role="option" aria-selected="true" data-value=""
																style="cursor:pointer;padding:6px 16px;min-height:36px;" onmouseenter="this.style.background='rgba(0,0,0,.04)'" onmouseleave="this.style.background=''">-----</li>
												
															<?php
															if (!empty($datasetArr)) {
																usort($datasetArr, function ($a, $b) {
																	return strcasecmp(strip_tags($a['name']), strip_tags($b['name']));
																});
												
																foreach ($datasetArr as $dataset) {
																	$assocDatasetID = (int)$dataset['datasetid'];
																	$assocDatasetName = strip_tags($dataset['name']);
																	if ($assocDatasetID == $datasetId) continue;
												
																	$alreadyAssociated = false;
																	if (!empty($adArr)) {
																		foreach ($adArr as $ad) {
																			if ((int)$ad['datasetID'] == $assocDatasetID) {
																				$alreadyAssociated = true;
																				break;
																			}
																		}
																	}
																	if ($alreadyAssociated) continue;
																	?>
																	<li class="MuiButtonBase-root MuiMenuItem-root MuiMenuItem-gutters" role="option" aria-selected="false"
																		data-value="<?php echo $assocDatasetID; ?>"
																		style="cursor:pointer;padding:6px 16px;min-height:36px;"
																		onmouseenter="this.style.background='rgba(0,0,0,.04)'" onmouseleave="this.style.background=''"><?php echo htmlspecialchars($assocDatasetName, ENT_QUOTES, 'UTF-8'); ?></li>
																	<?php
																}
															}
															?>
														</ul>
													</div>
												</div>

												<button
													class="MuiButtonBase-root MuiButton-root MuiButton-outlined MuiButton-outlinedPrimary MuiButton-sizeMedium MuiButton-outlinedSizeMedium MuiButton-colorPrimary dataset-manager-button"
													tabindex="0"
													name="submitaction"
													type="submit"
													value="Link Dataset"
													style="margin-left:auto; flex-shrink:0;"
												>
													Link
												</button>

											</div>

											<p class="MuiFormHelperText-root MuiFormHelperText-contained">
												Link associated datasets together
											</p>
										</div>
									</form>
								</div>
								<?php
							}
							?>
						</div>
					</div>
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
