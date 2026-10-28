<script>
  var TURBOPACK_CHUNK_BASE_PATH =
    <?php echo json_encode(rtrim($CLIENT_ROOT, '/') . '/neon-react/core-components/_next/'); ?>;
  window.NEON_CLIENT_ROOT = <?php echo json_encode($CLIENT_ROOT); ?>;
</script>
<!-- NEON REACT ASSETS START -->
<!-- React last updated: 10/27/2026, 2:20:33 PM -->
<meta charSet="utf-8"/><meta name="viewport" content="width=device-width, initial-scale=1"/><link rel="stylesheet" href="<?php echo $CLIENT_ROOT; ?>/neon-react/core-components/_next/static/chunks/3mqb6uwcgv5_z.css" data-precedence="next"/><link rel="preload" as="script" fetchPriority="low" href="<?php echo $CLIENT_ROOT; ?>/neon-react/core-components/_next/static/chunks/1ymln80vc0rl-.js"/><script src="<?php echo $CLIENT_ROOT; ?>/neon-react/core-components/_next/static/chunks/0twqply_6k3_c.js"></script><script src="<?php echo $CLIENT_ROOT; ?>/neon-react/core-components/_next/static/chunks/2muonxtzu2a3r.js"></script><script src="<?php echo $CLIENT_ROOT; ?>/neon-react/core-components/_next/static/chunks/turbopack-2sizzcrnxfbym.js"></script><script src="<?php echo $CLIENT_ROOT; ?>/neon-react/core-components/_next/static/chunks/3k_88bu8p7c4g.js"></script><script src="<?php echo $CLIENT_ROOT; ?>/neon-react/core-components/_next/static/chunks/1ki981-5p5oto.js"></script><script src="<?php echo $CLIENT_ROOT; ?>/neon-react/core-components/_next/static/chunks/40cx8dp4z2bqk.js"></script><script src="<?php echo $CLIENT_ROOT; ?>/neon-react/core-components/_next/static/chunks/0oxrt4rdhvasn.js"></script><script src="<?php echo $CLIENT_ROOT; ?>/neon-react/core-components/_next/static/chunks/17xh38m6on6pa.js"></script><script src="<?php echo $CLIENT_ROOT; ?>/neon-react/core-components/_next/static/chunks/2dq_z_y8msev9.js"></script><script src="<?php echo $CLIENT_ROOT; ?>/neon-react/core-components/_next/static/chunks/3vlaa871l5zra.js"></script><link rel="preload" href="<?php echo $CLIENT_ROOT; ?>/neon-react/core-components/assets/css/drupal-theme.0f92f17cfceb72295f216c13baa45da4.min.css" as="style"/><link rel="preload" href="https://fonts.googleapis.com/icon?family=Material+Icons" as="style"/><title>NEON | Portal Core Components</title><meta name="theme-color" content="#000000"/><link rel="manifest" href="<?php echo $CLIENT_ROOT; ?>/neon-react/core-components/manifest.json"/><link rel="shortcut icon" href="<?php echo $CLIENT_ROOT; ?>/neon-react/core-components/favicon.ico?v=201912"/><link rel="preconnect" href="https://www.neonscience.org" crossorigin="anonymous"/><link rel="stylesheet" data-meta="drupal-theme" href="<?php echo $CLIENT_ROOT; ?>/neon-react/core-components/assets/css/drupal-theme.0f92f17cfceb72295f216c13baa45da4.min.css"/><link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons"/><script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script><script>
              let gaInitialized = false;
              let interactionTracked = false;
              window.pendingGAEvents = [];
        
              function loadGAScript() {
                return new Promise(resolve => {
                  if (document.querySelector('script[data-ga-loader]')) {
                    resolve();
                    return;
                  }
        
                  const s = document.createElement('script');
                  s.async = true;
                  s.src = 'https://www.googletagmanager.com/gtag/js?id=G-6M1F2QCBWJ';
                  s.setAttribute('data-ga-loader', 'true');
                  s.onload = resolve;
        
                  document.head.appendChild(s);
                });
              }
        
              async function initGA() {
                if (gaInitialized) return;
                gaInitialized = true;
        
                await loadGAScript();
        
                window.dataLayer = window.dataLayer || [];
        
                window.gtag = function () {
                  window.dataLayer.push(arguments);
                };
        
                gtag('js', new Date());
        
                gtag('config', 'G-6M1F2QCBWJ', {
                  send_page_view: false
                });
        
                gtag('event', 'page_view', {
                  page_path: window.location.pathname,
                  page_location: window.location.href,
                  page_title: document.title
                });
        
                window.pendingGAEvents.forEach(args => {
                  gtag(...args);
                });
        
                window.pendingGAEvents = [];
        
                document.addEventListener('click', e => {
                  const el = e.target;
        
                  gtag('event', 'ui_click', {
                    tag: el.tagName || '',
                    text: (el.innerText || '').trim().substring(0, 100),
                    id: el.id || '',
                    page_path: window.location.pathname
                  });
                });
        
                document
                  .querySelectorAll('.search-api-form form')
                  .forEach(form => {
                    form.addEventListener('submit', e => {
                      const query = e.target.querySelector('[name="title"]')?.value?.trim();
        
                      if (!query) return;
        
                      gtag('event', 'neon-search', {
                        search_term: query
                      });
                    });
                  });
              }
        
              async function trackInteraction(type) {
                if (interactionTracked) return;
        
                interactionTracked = true;
        
                await initGA();
        
                gtag('event', 'interaction', {
                  interaction_type: type
                });
              }
        
              window.addEventListener('click', () => {
                trackInteraction('click');
              }, { once: true });
        
              window.addEventListener('keydown', () => {
                trackInteraction('keydown');
              }, { once: true });
        
              window.addEventListener('touchstart', () => {
                trackInteraction('touchstart');
              }, { once: true });
        
              window.addEventListener('mousemove', () => {
                trackInteraction('mousemove');
              }, { once: true });
            </script><script src="<?php echo $CLIENT_ROOT; ?>/neon-react/core-components/_next/static/chunks/0cz1d0mv5g_q7.js" noModule=""></script>
<!-- NEON REACT ASSETS END -->

<script>
  (globalThis.TURBOPACK ||= []).push([
    {
      getAttribute(name) {
        return name === 'src'
          ? '/neon/neon-react/core-components/_next/static/chunks/php-bootstrap.js'
          : null;
      }
    },
    {
    otherChunks: [
      'static/chunks/1ymln80vc0rl-.js', // script in body
      'static/chunks/3vlaa871l5zra.js' // last script before Drupal CSS
    ],
      runtimeModuleIds: [94469] //number in script above by BIOREPO
    }
  ]);
</script>


<?php
$isLoggedIn = !empty($SYMB_UID);
$isNeonMode = empty($NEON_DEV_MODE) || $NEON_DEV_MODE == 1;
$headerButtons = [];
if ($isLoggedIn) {
    $headerButtons[] = ['My Account', $isNeonMode ? 'https://data.neonscience.org/myaccount' : $CLIENT_ROOT . '/profile/viewprofile.php'];
    $headerButtons[] = ['Sign Out', $CLIENT_ROOT . '/profile/index.php?submit=logout'];
} else {
    $headerButtons[] = ['Sign In', $CLIENT_ROOT . ($isNeonMode ? '/profile/openIdAuth.php' : '/profile/index.php')];
}
if ($isNeonEditor) {
    $headerButtons[] = ['Management Tools', $CLIENT_ROOT . '/neon/index.php'];
    $headerButtons[] = ['Sitemap', $CLIENT_ROOT . '/sitemap.php'];
}
?>
<script>
(() => {
    const $ = selector => document.querySelector(selector);
    const byId = id => document.getElementById(id);
    const styles = (element, values) => Object.assign(element.style, values);

    // Create DOM elements from attributes and children.
    const element = (tag, attributes = {}, ...children) => {
        const node = document.createElement(tag);
        Object.entries(attributes).forEach(([key, value]) => {
            if (key === 'style') styles(node, value);
            else if (key === 'className') node.className = value;
            else if (key === 'textContent') node.textContent = value;
            else node.setAttribute(key, value);
        });
        node.append(...children);
        return node;
    };

    const link = (href, children, attributes = {}) =>
        element('a', { href, ...attributes }, ...children);

    // Preserve the original asynchronous wait behavior.
    const waitForElement = (selector, callback) => {
        const observer = new MutationObserver(() => {
            if (!$(selector)) return;
            observer.disconnect();
            callback();
        });
        observer.observe(document.body, { childList: true, subtree: true });
    };

    const createBiorepoPage = () => {
        const innerText = byId('innertext');
        if (innerText) innerText.before(element('div', { id: 'biorepo-page' }));
    };

    const updateElementWidth = () => {
        const blue = byId('blue-div');
        if (!blue) return;

        const content = $('div[data-selenium="neon-page.content"]');
        const container = $('div.MuiContainer-root');
        const innerText = byId('innertext');
        const stats = byId('statistics-container');
        const padding = parseFloat(getComputedStyle(content).paddingLeft);
        const margin = parseFloat(getComputedStyle(innerText).marginLeft);
        const rightMargin = parseFloat(getComputedStyle(container).marginRight);
        const right = `${padding + margin}px`;

        styles(blue, { width: `${content.offsetWidth + rightMargin}px`, right });
        styles(stats, { width: `${content.offsetWidth - 2 * padding}px`, right });
    };

    const updateBreadcrumbHash = () => {
        const breadcrumb = $('nav a[href="../misc/neoncollprofiles.php?collid=#"]');
        if (breadcrumb) {
            breadcrumb.href = breadcrumb.href.replace(
                '#',
                <?= json_encode(isset($collid) ? $collid : '#', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>
            );
        }
    };

    const replaceLegacyLayout = () => {
        const logo = byId('block-neon-site-branding');
        if (logo) logo.href = 'https://www.neonscience.org/';

        const innerText = byId('innertext');
        const content = $('div[data-selenium="neon-page.content"]');
        if (innerText && content) content.append(innerText);

        ['top_navbar', 'main-header', 'main-footer'].forEach(id => byId(id)?.remove());
        $('.navpath')?.remove();
    };

    const addFooterMessage = () => {
        const footer = $('.footer-bottom__message');
        const symbiota = link('https://symbiota.org/', ['Symbiota']);
        const credit = element('p', {},
            'Site powered by ', symbiota, ' | ',
            element('i', { textContent: 'This site uses Google Analytics' })
        );

        const githubIcon = element('img', {
            src: <?= json_encode($CLIENT_ROOT . '/images/icons/github-mark-white.svg') ?>,
            alt: 'GitHub Logo',
            style: { width: '16px', height: '16px', marginRight: '5px' }
        });
        const reportLink = link(
            'https://github.com/BioKIC/NEON-Biorepository/issues',
            [githubIcon, 'Report a problem'],
            { style: { textDecoration: 'none', color: 'inherit', display: 'flex', alignItems: 'center' } }
        );
        const report = element('div', {
            style: { display: 'flex', justifyContent: 'flex-end', alignItems: 'center', flex: '1' }
        }, reportLink);
        const wrapper = element('div', {
            style: { display: 'flex', justifyContent: 'space-between', width: '100%' }
        }, credit, report);

        if (footer) footer.append(wrapper);
    };

    const addFooterLogos = () => {
        const footer = $('.footer-top__logo');
        const logos = [
            [<?= json_encode($CLIENT_ROOT . '/images/layout/logo_symbiota_white_text.png') ?>, 'https://symbiota.org/'],
            [<?= json_encode($CLIENT_ROOT . '/images/layout/logo-asu-biokic.jpg') ?>, 'https://biokic.asu.edu/collections']
        ];
        const clickableImage = ([src, href]) =>
            link(href, [element('img', { src, height: 60, width: 167 })], { target: '_blank' });

        if (footer) footer.append(...logos.map(clickableImage));
    };

    const addNotificationBanner = () => {
        const now = new Date();
        if (now < new Date('2026-03-30T00:00:00-04:00') ||
            now > new Date('2026-04-02T02:00:00-04:00')) return;

        const header = $('header');
        if (!header || byId('neon-dev-banner')) return;

        const message = element('p', {
            textContent: 'The primary NEON website, www.neonscience.org, is currently down. We are working on a solution and hope to have this resolved shortly. Thank you for your patience.',
            style: {
                margin: '0 auto', maxWidth: '1652px', boxSizing: 'border-box',
                color: '#000', lineHeight: '1.5', fontSize: '.95rem'
            }
        });
        const banner = element('div', {
            id: 'neon-dev-banner', role: 'status', 'aria-live': 'polite',
            style: { background: '#FFCB4F', padding: '12px 16px' }
        }, message);
        header.insertAdjacentElement('afterend', banner);
    };

    const BUTTON_CLASSES = 'MuiButtonBase-root MuiButton-root MuiButton-outlined MuiButton-outlinedPrimary MuiButton-outlinedSizeSmall MuiButton-sizeSmall';
    const BUTTON_STYLES = {
        color: '#0073cf', fontSize: '0.55rem', fontWeight: '600',
        lineHeight: '1.75', whiteSpace: 'nowrap', textTransform: 'uppercase',
        borderWidth: '1px', borderStyle: 'solid', borderRadius: '0',
        borderColor: '#0073cf', padding: '5px 10px', display: 'inline'
    };

    const createHeaderButton = ([label, url]) => {
        const button = element('button', {
            className: BUTTON_CLASSES,
            type: 'button',
            tabindex: '0',
            'data-selenium': 'neon-menu.data-management-button',
            style: BUTTON_STYLES
        }, element('span', { className: 'MuiButton-label', textContent: label }));

        button.style.setProperty('font-family', '"Inter", Helvetica, Arial, sans-serif', 'important');
        button.style.setProperty('background-color', 'white', 'important');
        button.addEventListener('click', () => { window.location.href = url; });
        return button;
    };

    const addHeaderButtons = () => {
        const container = byId('header__authentication-ui');
        const headerRight = $('.header__right');
        if (!container || !headerRight) return;
    
        headerRight.append(container);
    
        Object.assign(container.style, {
            position: 'static',
            margin: '0',
            padding: '0',
            display: 'flex',
            alignItems: 'center',
            gap: '0'
        });
    
        const buttons = <?= json_encode($headerButtons, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    
        buttons.map(createHeaderButton).forEach(button => container.prepend(button));
    };

    document.addEventListener('DOMContentLoaded', createBiorepoPage);

    window.addEventListener('biorepo:neon-page-ready', () => {
        waitForElement('.neon__sidebar-sticky', updateElementWidth);
        waitForElement('.neon__sidebar-sticky', updateBreadcrumbHash);
        window.addEventListener('resize', updateElementWidth);

        <?php if (empty($DEACTIVATE_REACT)): ?>
        [
            replaceLegacyLayout,
            addFooterMessage,
            addFooterLogos,
            addNotificationBanner,
            addHeaderButtons
        ].forEach(action => action());
        <?php endif; ?>
    });
})();
</script>


<script>
(function () {
    const muiStyles = new Map();
    const muiSelector = '[class*="Mui"]';

    function isReactElement(el) {
        return Object.keys(el).some(key =>
            key.startsWith('__reactFiber$') || key.startsWith('__reactInternalInstance$')
        );
    }

    function getKey(el) {
        const classes = [...el.classList].filter(c => c.startsWith('Mui')).sort();
        return classes.length ? `${el.tagName}|${classes.join('|')}` : null;
    }

    function collectStyles() {
        document.querySelectorAll(muiSelector).forEach(el => {
            if (!isReactElement(el)) return;

            const key = getKey(el);
            const cssClasses = [...el.classList].filter(c => c.startsWith('css-'));
            if (!key || !cssClasses.length) return;

            const variables = {};
            for (const prop of el.style) {
                if (prop.startsWith('--')) variables[prop] = el.style.getPropertyValue(prop);
            }
            muiStyles.set(key, { cssClasses, variables });
        });
    }

    function applyStyles() {
        document.querySelectorAll(muiSelector).forEach(el => {
            if (isReactElement(el)) return;

            const styles = muiStyles.get(getKey(el));
            if (!styles) return;

            [...el.classList].filter(c => c.startsWith('css-')).forEach(c => el.classList.remove(c));
            el.classList.add(...styles.cssClasses);

            for (const [prop, value] of Object.entries(styles.variables)) {
                el.style.setProperty(prop, value);
            }
        });
    }

    function init() {
        let scheduled = false;
        const options = { childList: true, subtree: true };

        const update = () => {
            collectStyles();
            applyStyles();
        };

        const observer = new MutationObserver(() => {
            if (scheduled) return;
            scheduled = true;

            requestAnimationFrame(() => {
                scheduled = false;
                observer.disconnect();
                update();
                observer.observe(document.documentElement, options);
            });
        });

        update();
        observer.observe(document.documentElement, options);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>