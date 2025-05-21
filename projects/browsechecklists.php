<?php
include_once('../config/symbini.php');
header('Content-Type: text/html; charset=' . $CHARSET);
?>
<html>
	<head>
		<title>Browse Species Checklists</title>
		<?php
		include_once($SERVER_ROOT.'/includes/head.php');
		?>
	</head>
	
	<script>
		function waitForElement(selector, callback) {
			const observer = new MutationObserver(() => {
				const element = document.querySelector(selector);
				if (element) {
					observer.disconnect();
					callback();
				}
			});
	
			observer.observe(document.body, { childList: true, subtree: true });
		}
		window.onload = function() {
			function updateElementWidth() {	
				// hero image
				var neonPageContent = document.querySelector('div[data-selenium="neon-page.content"]');
				var neonPageContentWidth = neonPageContent.offsetWidth;
                var computedStyle = window.getComputedStyle(neonPageContent);
                var leftPadding = parseFloat(computedStyle.getPropertyValue('padding-left'));
				
				var muiContainer = document.querySelector('div.MuiContainer-root');
				var muiContainerStyle = window.getComputedStyle(muiContainer);
				var muiContainerRightMargin = parseFloat(muiContainerStyle.marginRight);
	
				var neonPageContentStyle = window.getComputedStyle(neonPageContent);
				var neonPageContentpaddingLeft = parseFloat(neonPageContentStyle.paddingLeft);
				
                var innerTextDiv = document.getElementById('innertext');
                var computedStyle = window.getComputedStyle(innerTextDiv);
                var leftMargin = parseFloat(computedStyle.getPropertyValue('margin-left'));
				
				document.getElementById('heroimage-div').style.width = (neonPageContentWidth + muiContainerRightMargin) + 'px';
				document.getElementById('heroimage-div').style.right = (leftPadding + leftMargin) + 'px';
			}
			
			var heroDiv = document.getElementById('heroimage-div');
			if (heroDiv) {			
				// Update the width on initial load
				waitForElement('.neon__sidebar-sticky', updateElementWidth);
				// wait a little after snapping
				let resizeTimeout;
				window.addEventListener('resize', function () {
				  clearTimeout(resizeTimeout);
				  resizeTimeout = setTimeout(updateElementWidth, 50);
				});
			}
		}
	</script>

	<style>
	  #heroimage-div {
		position: relative;
		right: 74px;
	  }
	
	  #heroimage-div::after {
		content: '';
		position: absolute;
		background-image: url('<?php echo $CLIENT_ROOT . '/images/card-images/white-border.png'; ?>');
		background-position: 50% 0;
		background-repeat: repeat no-repeat;
		bottom: -30px;
		height: 60px;
		width: 100%;
		left: 0;
	  }
	</style>
	
	<body>
		<!-- This is inner text! -->
		<div id="innertext">
			<h1>Browse Species Checklists</h1>
			</br>
			<div id = "heroimage-div">
				<img src="<?php echo $CLIENT_ROOT . '/images/card-images/Beetles_pinned.jpg'; ?>" style="max-width:100%" alt="Pinned Beetles" loading="lazy">
			</div>
			<p>Species checklists are intended to help users identify taxa at and around NEON field sites. Each checklist provides a comprehensive species list, visual resources, and direct links to voucher specimens collected as a part of the NEON project. Some checklists contain identification keys, which can be used to help a user identify a taxon within the checklist that possesses specific traits.</p>
			<div id="biorepo-checklists-content"></div>
		</div>
	</body>
</html>