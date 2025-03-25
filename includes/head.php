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
?>

<!--neon react links-->
<!--React last updated: 3/20/2025, 12:54:50 PM-->
<meta name="viewport" content="width=device-width,initial-scale=1,shrink-to-fit=no"/><meta name="theme-color" content="#000000"/><link rel="manifest" href="<?php echo $CLIENT_ROOT; ?>/neon-react/manifest.json"/><link rel="shortcut icon" href="<?php echo $CLIENT_ROOT; ?>/neon-react/favicon.ico?v=201912"/><link rel="preconnect" href="https://www.neonscience.org" crossorigin="anonymous"/><link rel="stylesheet" data-meta="drupal-fonts" href="<?php echo $CLIENT_ROOT; ?>/neon-react/assets/css/drupal-fonts.css"/><link rel="stylesheet" data-meta="drupal-theme" href="<?php echo $CLIENT_ROOT; ?>/neon-react/assets/css/drupal-theme.e26dccb4b915a92adb9c77ae139e9824.min.css"/><link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons"/><script src="https://code.jquery.com/jquery-3.5.0.min.js" integrity="sha256-xNzN2a4ltkB44Mc/Jz3pT4iU1cmeR0FkXs4pru/JxaQ=" crossorigin="anonymous"></script><script>window.gtmDataLayer=[{page_category:"Core Components"}]</script><script>!function(e,t,a,n){e[n]=e[n]||[],e[n].push({"gtm.start":(new Date).getTime(),event:"gtm.js"});var g=t.getElementsByTagName(a)[0],m=t.createElement(a),r="&l="+n;m.async=!0,m.src="https://www.googletagmanager.com/gtm.js?id=GTM-K4S83R2"+r,g.parentNode.insertBefore(m,g)}(window,document,"script","gtmDataLayer")</script><script>window.NEON_SERVER_DATA="__NEON_SERVER_DATA__"</script><link href="<?php echo $CLIENT_ROOT; ?>/neon-react/static/css/main.24d10e69.css" rel="stylesheet">
<!--end of neon react links-->

<script>
    document.addEventListener('DOMContentLoaded', function() {
        
        // image resizings for homepage
        function updateElementWidth() {
            const blueDiv = document.getElementById('blue-div');
            
            if (blueDiv) {
                // blue div
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
                
                document.getElementById('blue-div').style.width = (neonPageContentWidth + muiContainerRightMargin) + 'px';
                document.getElementById('blue-div').style.right = (leftPadding + leftMargin) + 'px';
                document.getElementById('statistics-container').style.width = (neonPageContentWidth - (2* neonPageContentpaddingLeft)) + 'px';
                document.getElementById('statistics-container').style.right = (leftPadding + leftMargin) + 'px';
            }
        }
        
        function updateBreadcrumbHash() {
            const breadcrumbLink = document.querySelector('nav a[href="../misc/neoncollprofiles.php?collid=#"]');
            if (breadcrumbLink) {
                breadcrumbLink.href = breadcrumbLink.href.replace('#', '<?php echo isset($collid) ? $collid : '#'; ?>');
            }
        }
        
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
    
        waitForElement('.neon__sidebar-sticky', updateElementWidth);
        waitForElement('.neon__sidebar-sticky', updateBreadcrumbHash);
        window.addEventListener('resize', updateElementWidth);
        
        // Create biorepo-page div
        // A page must have the innertext div
        var biorepoPage = document.createElement("div");
        biorepoPage.id = "biorepo-page";

        var innerText = document.getElementById("innertext");
        if (innerText) {
            innerText.parentNode.insertBefore(biorepoPage, innerText);
            //old page link
            var oldPageUrl = window.location.href.replace("prodreview", "portal");
            var returnLink = document.createElement("a");
            returnLink.href = oldPageUrl;
            returnLink.textContent = "View the original page version";
            returnLink.style.display = "block"; // Makes it a full-width block element
            returnLink.style.marginBottom = "10px"; // Adds spacing below
            innerText.insertBefore(returnLink, innerText.firstChild);
        }

        //javascript code created by React
        var reactScript = document.createElement('script');
        reactScript.src = '<?php echo $CLIENT_ROOT; ?>/neon-react/static/js/main.0095559d.js';
        reactScript.defer = true;

        reactScript.onload = function() {
            // To move innertext into neon-page.content
            var innerTextDiv = document.getElementById('innertext');
            var targetDiv = document.querySelector('div[data-selenium="neon-page.content"]');
            if(innerTextDiv && targetDiv) targetDiv.appendChild(innerTextDiv);

            //remove old header, footer and breadcrumbs
            var navBar = document.getElementById("top_navbar");
            if (navBar) {
                navBar.parentNode.removeChild(navBar);
            }
            var mainHeader = document.getElementById("main-header");
            if (mainHeader) {
                mainHeader.parentNode.removeChild(mainHeader);
            }

            var mainFooter = document.getElementById("main-footer");
            if (mainFooter) {
                mainFooter.parentNode.removeChild(mainFooter);
            }

            var mainFooter = document.getElementById("main-footer");
            if (mainFooter) {
                mainFooter.parentNode.removeChild(mainFooter);
            }

            // Breadcrumbs
            const navpath = document.querySelector('.navpath');
            if (navpath) {
                navpath.remove();
            }

            // Edit footer
            const footerMessageDiv = document.querySelector('.footer-bottom__message');

            const wrapperDiv = document.createElement('div');
            wrapperDiv.style.display = 'flex';
            wrapperDiv.style.justifyContent = 'space-between';
            wrapperDiv.style.width = '100%';

            const newParagraph1 = document.createElement('p');

            const textBeforeLink = document.createTextNode('Site powered by ');
            const symbiotaLink = document.createElement('a');
            symbiotaLink.href = 'https://symbiota.org/';
            symbiotaLink.textContent = 'Symbiota';
            const textAfterLink = document.createTextNode(' | ');

            const googleAnalytics = document.createElement('i');
            googleAnalytics.textContent = 'This site uses Google Analytics';

            newParagraph1.appendChild(textBeforeLink);
            newParagraph1.appendChild(symbiotaLink);
            newParagraph1.appendChild(textAfterLink);
            newParagraph1.appendChild(googleAnalytics);

            const reportDiv = document.createElement('div');
            reportDiv.style.display = 'flex';
            reportDiv.style.justifyContent = 'flex-end'; // Align to the right
            reportDiv.style.alignItems = 'center';
            reportDiv.style.flex = '1';

            const githubLink = document.createElement('a');
            githubLink.href = 'https://github.com/BioKIC/NEON-Biorepository/issues';
            githubLink.style.textDecoration = 'none';
            githubLink.style.color = 'inherit';
            githubLink.style.display = 'flex';
            githubLink.style.alignItems = 'center';

            const githubLogo = document.createElement('img');
            githubLogo.src = '<?php echo $CLIENT_ROOT; ?>/images/icons/github-mark-white.svg';
            githubLogo.alt = 'GitHub Logo';
            githubLogo.style.width = '16px';
            githubLogo.style.height = '16px';
            githubLogo.style.marginRight = '5px';

            const reportText = document.createTextNode('Report a problem');

            githubLink.appendChild(githubLogo);
            githubLink.appendChild(reportText);

            reportDiv.appendChild(githubLink);

            wrapperDiv.appendChild(newParagraph1);
            wrapperDiv.appendChild(reportDiv);

            if(footerMessageDiv) footerMessageDiv.appendChild(wrapperDiv);

            //footer logos
            const footerLogoDiv = document.querySelector('.footer-top__logo');

            function createImage(src, height, width) {
                const img = document.createElement('img');
                img.src = src;
                img.height = height;
                img.width = width;
                return img;
            }

            function createClickableImage(src, height, width, href) {
                const link = document.createElement('a');
                link.href = href;
                link.target = '_blank';
                const img = createImage(src, height, width);
                link.appendChild(img);
                return link;
            }

            const newImage1 = createClickableImage(
                '<?php echo $CLIENT_ROOT; ?>/images/layout/logo_symbiota_white_text.png',
                60,
                167,
                'https://symbiota.org/'
            );
            const newImage2 = createClickableImage(
                '<?php echo $CLIENT_ROOT; ?>/images/layout/logo-asu-biokic.jpg',
                60,
                167,
                'https://biokic.asu.edu/collections'
            );

            if(footerLogoDiv){
                footerLogoDiv.appendChild(newImage1);
                footerLogoDiv.appendChild(newImage2);
            }

            //sign in and sign out
            <?php
            if ($SYMB_UID) {
                //add my account
                echo <<<EOL
                    const myAccountButton = document.createElement('button');
                    myAccountButton.className = "MuiButtonBase-root MuiButton-root MuiButton-outlined MuiButton-outlinedPrimary MuiButton-outlinedSizeSmall MuiButton-sizeSmall";
                    myAccountButton.setAttribute('type', 'button');
                    myAccountButton.setAttribute('tabindex', '0');
                    myAccountButton.setAttribute('data-selenium', 'neon-menu.data-management-button');
                    myAccountButton.style.color = '#0073cf';
                    myAccountButton.style.fontSize = '0.55rem';
                    myAccountButton.style.setProperty('font-family', '"Inter", Helvetica, Arial, sans-serif', 'important');
                    myAccountButton.style.fontWeight = '600';
                    myAccountButton.style.lineHeight = '1.75';
                    myAccountButton.style.whiteSpace = 'nowrap';
                    myAccountButton.style.textTransform = 'uppercase';
                    myAccountButton.style.setProperty('background-color', 'white', 'important');
                    myAccountButton.style.borderWidth = '1px';
                    myAccountButton.style.borderStyle = 'solid';
                    myAccountButton.style.borderRadius = '0';
                    myAccountButton.style.borderColor = '#0073cf';
                    myAccountButton.style.padding = '5px 10px';
                    myAccountButton.style.display = 'inline';
                    myAccountButton.innerHTML = '<span class="MuiButton-label">My Account</span>';
                    myAccountButton.addEventListener('click', () => {
                EOL;

                echo "window.location.href = 'https://data.neonscience.org/myaccount';";
                echo <<<EOL
                    });
                    const signInDiv = document.getElementById("header__authentication-ui");
                    if (signInDiv) {
                        signInDiv.insertBefore(myAccountButton, signInDiv.firstChild);
                    }
                EOL;

                //add sign out
                echo <<<EOL
                    const signoutButton = document.createElement('button');
                    signoutButton.className = "MuiButtonBase-root MuiButton-root MuiButton-outlined MuiButton-outlinedPrimary MuiButton-outlinedSizeSmall MuiButton-sizeSmall";
                    signoutButton.setAttribute('type', 'button');
                    signoutButton.setAttribute('tabindex', '0');
                    signoutButton.setAttribute('data-selenium', 'neon-menu.data-management-button');
                    signoutButton.style.color = '#0073cf';
                    signoutButton.style.fontSize = '0.55rem';
                    signoutButton.style.setProperty('font-family', '"Inter", Helvetica, Arial, sans-serif', 'important');
                    signoutButton.style.fontWeight = '600';
                    signoutButton.style.lineHeight = '1.75';
                    signoutButton.style.whiteSpace = 'nowrap';
                    signoutButton.style.textTransform = 'uppercase';
                    signoutButton.style.setProperty('background-color', 'white', 'important');
                    signoutButton.style.borderWidth = '1px';
                    signoutButton.style.borderStyle = 'solid';
                    signoutButton.style.borderRadius = '0';
                    signoutButton.style.borderColor = '#0073cf';
                    signoutButton.style.padding = '5px 10px';
                    signoutButton.style.display = 'inline';
                    signoutButton.innerHTML = '<span class="MuiButton-label">Sign Out</span>';
                    signoutButton.addEventListener('click', () => {
                EOL;

                echo "window.location.href = '".$CLIENT_ROOT."/profile/index.php?submit=logout';";
                echo <<<EOL
                    });
                    if (signInDiv) {
                        signInDiv.insertBefore(signoutButton, signInDiv.firstChild);
                    }
                EOL;

            } else {
                //add sign in
                echo <<<EOL
                    const signinButton = document.createElement('button');
                    signinButton.className = "MuiButtonBase-root MuiButton-root MuiButton-outlined MuiButton-outlinedPrimary MuiButton-outlinedSizeSmall MuiButton-sizeSmall";
                    signinButton.setAttribute('type', 'button');
                    signinButton.setAttribute('tabindex', '0');
                    signinButton.setAttribute('data-selenium', 'neon-menu.data-management-button');
                    signinButton.style.color = '#0073cf';
                    signinButton.style.fontSize = '0.55rem';
                    signinButton.style.setProperty('font-family', '"Inter", Helvetica, Arial, sans-serif', 'important');
                    signinButton.style.fontWeight = '600';
                    signinButton.style.lineHeight = '1.75';
                    signinButton.style.whiteSpace = 'nowrap';
                    signinButton.style.textTransform = 'uppercase';
                    signinButton.style.setProperty('background-color', 'white', 'important');
                    signinButton.style.borderWidth = '1px';
                    signinButton.style.borderStyle = 'solid';
                    signinButton.style.borderRadius = '0';
                    signinButton.style.borderColor = '#0073cf';
                    signinButton.style.padding = '5px 10px';
                    signinButton.style.display = 'inline';
                    signinButton.innerHTML = '<span class="MuiButton-label">Sign In</span>';
                    signinButton.addEventListener('click', () => {
                EOL;

                echo "window.location.href = '".$CLIENT_ROOT."/profile/openIdAuth.php';";
                echo <<<EOL
                    });
                    const signInDiv = document.getElementById("header__authentication-ui");
                    if (signInDiv) {
                        signInDiv.insertBefore(signinButton, signInDiv.firstChild);
                    }
                EOL;
            }
            ?>

            //utilities and management menus
            <?php
            if ($isNeonEditor) {
                //management tools button
                echo <<<EOL
                    const dataManagementButton = document.createElement('button');
                    dataManagementButton.className = "MuiButtonBase-root MuiButton-root MuiButton-outlined MuiButton-outlinedPrimary MuiButton-outlinedSizeSmall MuiButton-sizeSmall";
                    dataManagementButton.setAttribute('type', 'button');
                    dataManagementButton.setAttribute('tabindex', '0');
                    dataManagementButton.setAttribute('data-selenium', 'neon-menu.data-management-button');
                    dataManagementButton.style.color = '#0073cf';
                    dataManagementButton.style.fontSize = '0.55rem';
                    dataManagementButton.style.setProperty('font-family', '"Inter", Helvetica, Arial, sans-serif', 'important');
                    dataManagementButton.style.fontWeight = '600';
                    dataManagementButton.style.lineHeight = '1.75';
                    dataManagementButton.style.whiteSpace = 'nowrap';
                    dataManagementButton.style.textTransform = 'uppercase';
                    dataManagementButton.style.setProperty('background-color', 'white', 'important');
                    dataManagementButton.style.borderWidth = '1px';
                    dataManagementButton.style.borderStyle = 'solid';
                    dataManagementButton.style.borderRadius = '0';
                    dataManagementButton.style.borderColor = '#0073cf';
                    dataManagementButton.style.padding = '5px 10px';
                    dataManagementButton.style.display = 'inline';
                    dataManagementButton.innerHTML = '<span class="MuiButton-label">Management Tools</span>';
                    dataManagementButton.addEventListener('click', () => {
                EOL;

                echo "window.location.href = '".$CLIENT_ROOT."/neon/index.php';";
                echo <<<EOL
                    });
                    if (signInDiv) {
                        signInDiv.insertBefore(dataManagementButton, signInDiv.firstChild);
                    }
                EOL;
                //utilities button
                echo <<<EOL
                    const utilitiesButton = document.createElement('button');
                    utilitiesButton.className = "MuiButtonBase-root MuiButton-root MuiButton-outlined MuiButton-outlinedPrimary MuiButton-outlinedSizeSmall MuiButton-sizeSmall";
                    utilitiesButton.setAttribute('type', 'button');
                    utilitiesButton.setAttribute('tabindex', '0');
                    utilitiesButton.setAttribute('data-selenium', 'neon-menu.data-management-button');
                    utilitiesButton.style.color = '#0073cf';
                    utilitiesButton.style.fontSize = '0.55rem';
                    utilitiesButton.style.setProperty('font-family', '"Inter", Helvetica, Arial, sans-serif', 'important');
                    utilitiesButton.style.fontWeight = '600';
                    utilitiesButton.style.lineHeight = '1.75';
                    utilitiesButton.style.whiteSpace = 'nowrap';
                    utilitiesButton.style.textTransform = 'uppercase';
                    utilitiesButton.style.setProperty('background-color', 'white', 'important');
                    utilitiesButton.style.borderWidth = '1px';
                    utilitiesButton.style.borderStyle = 'solid';
                    utilitiesButton.style.borderRadius = '0';
                    utilitiesButton.style.borderColor = '#0073cf';
                    utilitiesButton.style.padding = '5px 10px';
                    utilitiesButton.style.display = 'inline';
                    utilitiesButton.innerHTML = '<span class="MuiButton-label">Sitemap</span>';
                    utilitiesButton.addEventListener('click', () => {
                EOL;

                echo "window.location.href = '".$CLIENT_ROOT."/sitemap.php';";
                echo <<<EOL
                    });
                    if (signInDiv) {
                        signInDiv.insertBefore(utilitiesButton, signInDiv.firstChild);
                    };
                EOL;
            }
            ?>
        };

        document.body.appendChild(reactScript);

    });

</script>
<!--end-->


<!-- UNIVERSAL CSS –––––––––––––––––––––––––––––––––––––––––––––––––– -->
<link rel="stylesheet" href="<?php echo $CLIENT_ROOT; ?>/css/normalize.css">
<!--<link rel="stylesheet" href="<?php echo $CLIENT_ROOT; ?>/css/skeleton.css">-->
<link rel="stylesheet" href="<?php echo $CLIENT_ROOT; ?>/css/neon.css?ver=4">
<link href="<?= $CSS_BASE_PATH ?>/main-neon.css?ver=<?= $CSS_VERSION ?>" type="text/css" rel="stylesheet">
<link href="<?= $CSS_BASE_PATH ?>/symbiota/customizations.css?ver=<?= $CSS_VERSION ?>" type="text/css" rel="stylesheet">

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
