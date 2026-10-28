<?php
/*
 * Customize styling by adding or modifying CSS file links below
 * Default styling for individual page is defined within /css/symb/
 * Individual styling can be customized by:
 *     1) Uncommenting the $CUSTOM_CSS_PATH variable below
 *     2) Copying individual CCS file to the /css/symb/custom directory
 *     3) Modifying the sytle definiation within the file
 */

$CUSTOM_CSS_PATH = '/css/symb/custom';

$isNeonEditor = false;
if ($IS_ADMIN) $isNeonEditor = true;
elseif (array_key_exists('CollAdmin', $USER_RIGHTS) || array_key_exists('CollEditor', $USER_RIGHTS)) $isNeonEditor = true;

//react code
include($SERVER_ROOT . '/includes/react_head.php');

$CSS_VERSION = '20261028';
?>
<meta name="viewport" content="width=device-width, initial-scale=1">
<!-- UNIVERSAL CSS –––––––––––––––––––––––––––––––––––––––––––––––––– -->
<link href="<?= $CSS_BASE_PATH ?>/normalize.css?ver=<?= $CSS_VERSION ?>" type="text/css" rel="stylesheet">
<link href="<?= $CSS_BASE_PATH ?>/skeleton.css?ver=<?= $CSS_VERSION ?>" type="text/css" rel="stylesheet">
<link href="<?= $CSS_BASE_PATH ?>/neon.css?ver=<?= $CSS_VERSION ?>" type="text/css" rel="stylesheet">
<link href="<?= $CSS_BASE_PATH ?>/main-neon.css?ver=<?= $CSS_VERSION ?>" type="text/css" rel="stylesheet">

<script src="<?= $CLIENT_ROOT ?>/js/symb/lang.js" type="text/javascript"></script>
<script type="text/javascript">
	//Uncomment following line to support toggling of database content containing DIVs with lang classes in form of: <div class="lang en">Content in English</div><div class="lang es">Content in Spanish</div>
	//setLanguageDiv();
</script>
<?php
//if ($USERNAME != 0) {
//    echo "logged in as " . $USERNAME . ' <a href="http://localhost/neon/profile/index.php?submit=logout">Log out</a>';
//} else {
//    echo 'logged out <a href="http://localhost/neon/profile/index.php">Log in</a>';
//}
?>
