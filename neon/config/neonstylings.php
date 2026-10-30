<?php
include_once('../../config/symbini.php');
header("Content-Type: text/html; charset=".$CHARSET);

function formatHtmlNode($node, $depth = 0) {
	$indent = str_repeat("\t", $depth);

	if ($node->nodeType === XML_TEXT_NODE) {
		$text = trim($node->nodeValue);
		return $text !== '' ? $indent . $text . "\n" : '';
	}

	if ($node->nodeType !== XML_ELEMENT_NODE) {
		return '';
	}

	$tag = $node->nodeName;

	$voidTags = array(
		'area', 'base', 'br', 'col', 'embed', 'hr', 'img',
		'input', 'link', 'meta', 'param', 'source', 'track', 'wbr'
	);

	$attributes = '';

	if ($node->hasAttributes()) {
		foreach ($node->attributes as $attribute) {
			$attributes .= ' ' . $attribute->name . '="' .
				htmlspecialchars($attribute->value, ENT_NOQUOTES | ENT_SUBSTITUTE, 'UTF-8') .
				'"';
		}
	}

	if (in_array(strtolower($tag), $voidTags)) {
		return $indent . '<' . $tag . $attributes . '>' . "\n";
	}

	$elementChildren = array();

	foreach ($node->childNodes as $child) {
		if (
			$child->nodeType === XML_ELEMENT_NODE ||
			($child->nodeType === XML_TEXT_NODE && trim($child->nodeValue) !== '')
		) {
			$elementChildren[] = $child;
		}
	}

	// Keep simple text-only elements on one line.
	if (
		count($elementChildren) === 1 &&
		$elementChildren[0]->nodeType === XML_TEXT_NODE
	) {
		return $indent .
			'<' . $tag . $attributes . '>' .
			trim($elementChildren[0]->nodeValue) .
			'</' . $tag . '>' . "\n";
	}

	$output = $indent . '<' . $tag . $attributes . '>' . "\n";

	foreach ($elementChildren as $child) {
		$output .= formatHtmlNode($child, $depth + 1);
	}

	$output .= $indent . '</' . $tag . '>' . "\n";

	return $output;
}

function formatComponentHtml($html) {
	$dom = new DOMDocument('1.0', 'UTF-8');

	libxml_use_internal_errors(true);

	$dom->loadHTML(
		'<div id="component-format-root">' . $html . '</div>',
		LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
	);

	libxml_clear_errors();

	$root = $dom->getElementById('component-format-root');

	$output = '';

	foreach ($root->childNodes as $child) {
		if ($child->nodeType === XML_ELEMENT_NODE) {
			$output .= formatHtmlNode($child);
		}
	}

	return trim($output);
}

function renderComponentExample($title, $description, $html) {
	$html = formatComponentHtml($html);

	echo '<section class="component-section">';
	echo '<h2>' . htmlspecialchars($title, ENT_NOQUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</h2>';

	if ($description) {
		echo '<p class="component-section__description">' .
			htmlspecialchars($description, ENT_NOQUOTES | ENT_SUBSTITUTE, 'UTF-8') .
			'</p>';
	}

	echo '<div class="component-demo">';
	echo $html;
	echo '</div>';

	echo '<pre class="component-code"><code>' .
		htmlspecialchars($html, ENT_NOQUOTES | ENT_SUBSTITUTE, 'UTF-8') .
		'</code></pre>';

	echo '</section>';
}

$accordion = <<<'HTML'
<div class="MuiPaper-root MuiPaper-elevation MuiPaper-rounded MuiPaper-elevation1 MuiAccordion-root MuiAccordion-rounded MuiAccordion-gutters">

  <h3 class="MuiAccordion-heading">
    <button
      class="MuiButtonBase-root MuiAccordionSummary-root MuiAccordionSummary-gutters"
      tabindex="0"
      type="button"
      aria-expanded="false"
      onclick="
        var accordion = this.closest('.MuiAccordion-root');
        var content = accordion.querySelector('.accordion-content');
        var icon = this.querySelector('.MuiAccordionSummary-expandIconWrapper');
        var summaryContent = this.querySelector('.MuiAccordionSummary-content');
        var isExpanded = this.getAttribute('aria-expanded') === 'true';

        this.setAttribute('aria-expanded', String(!isExpanded));

        accordion.classList.toggle('Mui-expanded', !isExpanded);
        this.classList.toggle('Mui-expanded', !isExpanded);
        summaryContent.classList.toggle('Mui-expanded', !isExpanded);
        icon.classList.toggle('Mui-expanded', !isExpanded);

        content.style.maxHeight = isExpanded ? '0px' : content.scrollHeight + 'px';
        icon.style.transform = isExpanded ? 'rotate(0deg)' : 'rotate(180deg)';
      ">

      <span class="MuiAccordionSummary-content">
        Basic Accordion
      </span>

      <span
        class="MuiAccordionSummary-expandIconWrapper"
        style="transition: transform 0.25s ease;">
        <svg
          class="MuiSvgIcon-root MuiSvgIcon-fontSizeMedium"
          focusable="false"
          aria-hidden="true"
          viewBox="0 0 24 24">
          <path d="M16.59 8.59 12 13.17 7.41 8.59 6 10l6 6 6-6z"></path>
        </svg>
      </span>

    </button>
  </h3>

  <div
    class="accordion-content"
    style="max-height: 0px; overflow: hidden; transition: max-height 0.25s ease;">

    <div role="region" class="MuiAccordion-region">

      <div class="MuiAccordionDetails-root">
        <p class="MuiTypography-root MuiTypography-body1">
          Accordion Details
        </p>
      </div>

      <div class="MuiAccordionActions-root MuiAccordionActions-spacing">

        <button
          class="MuiButtonBase-root MuiButton-root MuiButton-text MuiButton-sizeMedium MuiButton-colorPrimary"
          tabindex="0"
          type="button">
          Cancel
        </button>

        <button
          class="MuiButtonBase-root MuiButton-root MuiButton-text MuiButton-sizeMedium MuiButton-colorPrimary"
          tabindex="0"
          type="button">
          Action
        </button>

      </div>
    </div>
  </div>
</div>
HTML;

$buttons = <<<'HTML'
<!-- TEXT BUTTONS -->

<button class="MuiButtonBase-root MuiButton-root MuiButton-text MuiButton-sizeSmall MuiButton-colorPrimary" tabindex="0" type="button">Text</button>

<button class="MuiButtonBase-root MuiButton-root MuiButton-text MuiButton-sizeMedium MuiButton-colorPrimary" tabindex="0" type="button">Text</button>

<button class="MuiButtonBase-root MuiButton-root MuiButton-text MuiButton-sizeLarge MuiButton-colorPrimary" tabindex="0" type="button">Text</button>

<button class="MuiButtonBase-root Mui-disabled MuiButton-root MuiButton-text MuiButton-sizeSmall MuiButton-colorPrimary" tabindex="-1" type="button" disabled>Text</button>

<button class="MuiButtonBase-root Mui-disabled MuiButton-root MuiButton-text MuiButton-sizeMedium MuiButton-colorPrimary" tabindex="-1" type="button" disabled>Text</button>

<button class="MuiButtonBase-root Mui-disabled MuiButton-root MuiButton-text MuiButton-sizeLarge MuiButton-colorPrimary" tabindex="-1" type="button" disabled>Text</button>


<!-- OUTLINED BUTTONS -->

<button class="MuiButtonBase-root MuiButton-root MuiButton-outlined MuiButton-sizeSmall MuiButton-colorPrimary" tabindex="0" type="button">
  <span class="MuiButton-icon MuiButton-startIcon">
    <svg class="MuiSvgIcon-root MuiSvgIcon-fontSizeMedium" focusable="false" aria-hidden="true" viewBox="0 0 24 24">
      <path d="M15.41 7.41 14 6l-6 6 6 6 1.41-1.41L10.83 12z"></path>
    </svg>
  </span>
  Outlined
</button>

<button class="MuiButtonBase-root MuiButton-root MuiButton-outlined MuiButton-sizeMedium MuiButton-colorPrimary" tabindex="0" type="button">
  <span class="MuiButton-icon MuiButton-startIcon">
    <svg class="MuiSvgIcon-root MuiSvgIcon-fontSizeMedium" focusable="false" aria-hidden="true" viewBox="0 0 24 24">
      <path d="M15.41 7.41 14 6l-6 6 6 6 1.41-1.41L10.83 12z"></path>
    </svg>
  </span>
  Outlined
</button>

<button class="MuiButtonBase-root MuiButton-root MuiButton-outlined MuiButton-sizeLarge MuiButton-colorPrimary" tabindex="0" type="button">
  <span class="MuiButton-icon MuiButton-startIcon">
    <svg class="MuiSvgIcon-root MuiSvgIcon-fontSizeMedium" focusable="false" aria-hidden="true" viewBox="0 0 24 24">
      <path d="M15.41 7.41 14 6l-6 6 6 6 1.41-1.41L10.83 12z"></path>
    </svg>
  </span>
  Outlined
</button>

<button class="MuiButtonBase-root Mui-disabled MuiButton-root MuiButton-outlined MuiButton-sizeSmall MuiButton-colorPrimary" tabindex="-1" type="button" disabled>Outlined</button>

<button class="MuiButtonBase-root Mui-disabled MuiButton-root MuiButton-outlined MuiButton-sizeMedium MuiButton-colorPrimary" tabindex="-1" type="button" disabled>Outlined</button>

<button class="MuiButtonBase-root Mui-disabled MuiButton-root MuiButton-outlined MuiButton-sizeLarge MuiButton-colorPrimary" tabindex="-1" type="button" disabled>Outlined</button>


<!-- CONTAINED BUTTONS -->

<button class="MuiButtonBase-root MuiButton-root MuiButton-contained MuiButton-sizeSmall MuiButton-colorPrimary" tabindex="0" type="button">
  Contained
  <span class="MuiButton-icon MuiButton-endIcon">
    <svg class="MuiSvgIcon-root MuiSvgIcon-fontSizeMedium" focusable="false" aria-hidden="true" viewBox="0 0 24 24">
      <path d="M10 6 8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"></path>
    </svg>
  </span>
</button>

<button class="MuiButtonBase-root MuiButton-root MuiButton-contained MuiButton-sizeMedium MuiButton-colorPrimary" tabindex="0" type="button">
  Contained
  <span class="MuiButton-icon MuiButton-endIcon">
    <svg class="MuiSvgIcon-root MuiSvgIcon-fontSizeMedium" focusable="false" aria-hidden="true" viewBox="0 0 24 24">
      <path d="M10 6 8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"></path>
    </svg>
  </span>
</button>

<button class="MuiButtonBase-root MuiButton-root MuiButton-contained MuiButton-sizeLarge MuiButton-colorPrimary" tabindex="0" type="button">
  Contained
  <span class="MuiButton-icon MuiButton-endIcon">
    <svg class="MuiSvgIcon-root MuiSvgIcon-fontSizeMedium" focusable="false" aria-hidden="true" viewBox="0 0 24 24">
      <path d="M10 6 8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"></path>
    </svg>
  </span>
</button>

<button class="MuiButtonBase-root Mui-disabled MuiButton-root MuiButton-contained MuiButton-sizeSmall MuiButton-colorPrimary" tabindex="-1" type="button" disabled>Contained</button>

<button class="MuiButtonBase-root Mui-disabled MuiButton-root MuiButton-contained MuiButton-sizeMedium MuiButton-colorPrimary" tabindex="-1" type="button" disabled>Contained</button>

<button class="MuiButtonBase-root Mui-disabled MuiButton-root MuiButton-contained MuiButton-sizeLarge MuiButton-colorPrimary" tabindex="-1" type="button" disabled>Contained</button>
HTML;

$button_groups = <<<'HTML'
<!-- OUTLINED BUTTON GROUPS -->

<!-- Small -->
<div role="group" class="MuiButtonGroup-root MuiButtonGroup-outlined MuiButtonGroup-horizontal MuiButtonGroup-colorPrimary">
  <button class="MuiButtonBase-root MuiButtonGroup-grouped MuiButton-root MuiButton-outlined MuiButton-sizeSmall MuiButton-colorPrimary MuiButtonGroup-firstButton" tabindex="0" type="button">One</button>
  <button class="MuiButtonBase-root MuiButtonGroup-grouped MuiButton-root MuiButton-outlined MuiButton-sizeSmall MuiButton-colorPrimary MuiButtonGroup-lastButton" tabindex="0" type="button">Two</button>
</div>

<!-- Medium -->
<div role="group" class="MuiButtonGroup-root MuiButtonGroup-outlined MuiButtonGroup-horizontal MuiButtonGroup-colorPrimary">
  <button class="MuiButtonBase-root MuiButtonGroup-grouped MuiButton-root MuiButton-outlined MuiButton-sizeMedium MuiButton-colorPrimary MuiButtonGroup-firstButton" tabindex="0" type="button">One</button>
  <button class="MuiButtonBase-root MuiButtonGroup-grouped MuiButton-root MuiButton-outlined MuiButton-sizeMedium MuiButton-colorPrimary MuiButtonGroup-lastButton" tabindex="0" type="button">Two</button>
</div>

<!-- Large -->
<div role="group" class="MuiButtonGroup-root MuiButtonGroup-outlined MuiButtonGroup-horizontal MuiButtonGroup-colorPrimary">
  <button class="MuiButtonBase-root MuiButtonGroup-grouped MuiButton-root MuiButton-outlined MuiButton-sizeLarge MuiButton-colorPrimary MuiButtonGroup-firstButton" tabindex="0" type="button">One</button>
  <button class="MuiButtonBase-root MuiButtonGroup-grouped MuiButton-root MuiButton-outlined MuiButton-sizeLarge MuiButton-colorPrimary MuiButtonGroup-lastButton" tabindex="0" type="button">Two</button>
</div>


<!-- TEXT BUTTON GROUPS -->

<!-- Small -->
<div role="group" class="MuiButtonGroup-root MuiButtonGroup-text MuiButtonGroup-horizontal MuiButtonGroup-colorPrimary">
  <button class="MuiButtonBase-root MuiButtonGroup-grouped MuiButton-root MuiButton-text MuiButton-sizeSmall MuiButton-colorPrimary MuiButtonGroup-firstButton" tabindex="0" type="button">One</button>
  <button class="MuiButtonBase-root MuiButtonGroup-grouped MuiButton-root MuiButton-text MuiButton-sizeSmall MuiButton-colorPrimary MuiButtonGroup-lastButton" tabindex="0" type="button">Two</button>
</div>

<!-- Medium -->
<div role="group" class="MuiButtonGroup-root MuiButtonGroup-text MuiButtonGroup-horizontal MuiButtonGroup-colorPrimary">
  <button class="MuiButtonBase-root MuiButtonGroup-grouped MuiButton-root MuiButton-text MuiButton-sizeMedium MuiButton-colorPrimary MuiButtonGroup-firstButton" tabindex="0" type="button">One</button>
  <button class="MuiButtonBase-root MuiButtonGroup-grouped MuiButton-root MuiButton-text MuiButton-sizeMedium MuiButton-colorPrimary MuiButtonGroup-lastButton" tabindex="0" type="button">Two</button>
</div>

<!-- Large -->
<div role="group" class="MuiButtonGroup-root MuiButtonGroup-text MuiButtonGroup-horizontal MuiButtonGroup-colorPrimary">
  <button class="MuiButtonBase-root MuiButtonGroup-grouped MuiButton-root MuiButton-text MuiButton-sizeLarge MuiButton-colorPrimary MuiButtonGroup-firstButton" tabindex="0" type="button">One</button>
  <button class="MuiButtonBase-root MuiButtonGroup-grouped MuiButton-root MuiButton-text MuiButton-sizeLarge MuiButton-colorPrimary MuiButtonGroup-lastButton" tabindex="0" type="button">Two</button>
</div>
HTML;

$card = <<<'HTML'
<div class="MuiPaper-root MuiPaper-elevation MuiPaper-rounded MuiPaper-elevation1 MuiCard-root">
  <div class="MuiCardContent-root">
    <h2 class="MuiTypography-root MuiTypography-h5">Card</h2>
    <p class="MuiTypography-root MuiTypography-body2">Card content</p>
  </div>
  <div class="MuiCardActions-root MuiCardActions-spacing">
    <button class="MuiButtonBase-root MuiButton-root MuiButton-outlined MuiButton-sizeMedium MuiButton-colorPrimary" tabindex="0" type="button">
      Action
    </button>
  </div>
</div>
HTML;

$checkboxes = <<<'HTML'
<!-- UNCHECKED CHECKBOX -->
<label class="MuiFormControlLabel-root MuiFormControlLabel-labelPlacementEnd">
  <span class="MuiButtonBase-root MuiCheckbox-root MuiCheckbox-colorPrimary MuiCheckbox-sizeMedium PrivateSwitchBase-root">
    <input data-indeterminate="false" class="PrivateSwitchBase-input" type="checkbox">
    <svg class="MuiSvgIcon-root MuiSvgIcon-fontSizeMedium" focusable="false" aria-hidden="true" viewBox="0 0 24 24">
      <path d="M19 5v14H5V5h14m0-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z"></path>
    </svg>
  </span>
  <span class="MuiTypography-root MuiTypography-body1 MuiFormControlLabel-label">Checkbox</span>
</label>


<!-- CHECKED CHECKBOX -->
<label class="MuiFormControlLabel-root MuiFormControlLabel-labelPlacementEnd">
  <span class="MuiButtonBase-root MuiCheckbox-root MuiCheckbox-colorPrimary MuiCheckbox-sizeMedium PrivateSwitchBase-root Mui-checked">
    <input data-indeterminate="false" class="PrivateSwitchBase-input" type="checkbox" checked>
    <svg class="MuiSvgIcon-root MuiSvgIcon-fontSizeMedium" focusable="false" aria-hidden="true" viewBox="0 0 24 24">
      <path d="M19 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.11 0 2-.9 2-2V5c0-1.11-.89-2-2-2zm-9 14l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"></path>
    </svg>
  </span>
  <span class="MuiTypography-root MuiTypography-body1 MuiFormControlLabel-label">Checkbox</span>
</label>


<!-- DISABLED CHECKBOX -->
<label class="MuiFormControlLabel-root Mui-disabled MuiFormControlLabel-labelPlacementEnd">
  <span class="MuiButtonBase-root MuiCheckbox-root MuiCheckbox-colorPrimary MuiCheckbox-sizeMedium PrivateSwitchBase-root Mui-disabled">
    <input disabled data-indeterminate="false" class="PrivateSwitchBase-input" type="checkbox">
    <svg class="MuiSvgIcon-root MuiSvgIcon-fontSizeMedium" focusable="false" aria-hidden="true" viewBox="0 0 24 24">
      <path d="M19 5v14H5V5h14m0-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z"></path>
    </svg>
  </span>
  <span class="MuiTypography-root MuiTypography-body1 MuiFormControlLabel-label Mui-disabled">Checkbox</span>
</label>
HTML;

$radio_buttons = <<<'HTML'
<!-- UNCHECKED RADIO -->
<label class="MuiFormControlLabel-root MuiFormControlLabel-labelPlacementEnd">
  <span class="MuiButtonBase-root MuiRadio-root MuiRadio-colorPrimary PrivateSwitchBase-root">
    <input class="PrivateSwitchBase-input" type="radio" name="radio-example">
    <span>
      <svg class="MuiSvgIcon-root MuiSvgIcon-fontSizeMedium" focusable="false" aria-hidden="true" viewBox="0 0 24 24">
        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8z"></path>
      </svg>
      <svg class="MuiSvgIcon-root MuiSvgIcon-fontSizeMedium" focusable="false" aria-hidden="true" viewBox="0 0 24 24">
        <path d="M8.465 8.465C9.37 7.56 10.62 7 12 7C14.76 7 17 9.24 17 12C17 13.38 16.44 14.63 15.535 15.535C14.63 16.44 13.38 17 12 17C9.24 17 7 14.76 7 12C7 10.62 7.56 9.37 8.465 8.465Z"></path>
      </svg>
    </span>
  </span>
  <span class="MuiTypography-root MuiTypography-body1 MuiFormControlLabel-label">Radio</span>
</label>


<!-- CHECKED RADIO -->
<label class="MuiFormControlLabel-root MuiFormControlLabel-labelPlacementEnd">
  <span class="MuiButtonBase-root MuiRadio-root MuiRadio-colorPrimary PrivateSwitchBase-root Mui-checked">
    <input class="PrivateSwitchBase-input" type="radio" name="radio-example" checked>
    <span>
      <svg class="MuiSvgIcon-root MuiSvgIcon-fontSizeMedium" focusable="false" aria-hidden="true" viewBox="0 0 24 24">
        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8z"></path>
      </svg>
      <svg class="MuiSvgIcon-root MuiSvgIcon-fontSizeMedium" focusable="false" aria-hidden="true" viewBox="0 0 24 24">
        <path d="M8.465 8.465C9.37 7.56 10.62 7 12 7C14.76 7 17 9.24 17 12C17 13.38 16.44 14.63 15.535 15.535C14.63 16.44 13.38 17 12 17C9.24 17 7 14.76 7 12C7 10.62 7.56 9.37 8.465 8.465Z"></path>
      </svg>
    </span>
  </span>
  <span class="MuiTypography-root MuiTypography-body1 MuiFormControlLabel-label">Radio</span>
</label>


<!-- DISABLED RADIO -->
<label class="MuiFormControlLabel-root Mui-disabled MuiFormControlLabel-labelPlacementEnd">
  <span class="MuiButtonBase-root MuiRadio-root MuiRadio-colorPrimary PrivateSwitchBase-root Mui-disabled">
    <input disabled class="PrivateSwitchBase-input" type="radio" name="radio-example">
    <span>
      <svg class="MuiSvgIcon-root MuiSvgIcon-fontSizeMedium" focusable="false" aria-hidden="true" viewBox="0 0 24 24">
        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8z"></path>
      </svg>
      <svg class="MuiSvgIcon-root MuiSvgIcon-fontSizeMedium" focusable="false" aria-hidden="true" viewBox="0 0 24 24">
        <path d="M8.465 8.465C9.37 7.56 10.62 7 12 7C14.76 7 17 9.24 17 12C17 13.38 16.44 14.63 15.535 15.535C14.63 16.44 13.38 17 12 17C9.24 17 7 14.76 7 12C7 10.62 7.56 9.37 8.465 8.465Z"></path>
      </svg>
    </span>
  </span>
  <span class="MuiTypography-root MuiTypography-body1 MuiFormControlLabel-label Mui-disabled">Radio</span>
</label>
HTML;

$switches = <<<'HTML'
<!-- UNCHECKED SWITCH -->
<label class="MuiFormControlLabel-root MuiFormControlLabel-labelPlacementEnd">
  <span class="MuiSwitch-root MuiSwitch-sizeMedium">
    <span class="MuiButtonBase-root MuiSwitch-switchBase MuiSwitch-colorPrimary PrivateSwitchBase-root">
      <input role="switch" class="PrivateSwitchBase-input MuiSwitch-input" type="checkbox">
      <span class="MuiSwitch-thumb"></span>
    </span>
    <span class="MuiSwitch-track"></span>
  </span>
  <span class="MuiTypography-root MuiTypography-body1 MuiFormControlLabel-label">Switch</span>
</label>


<!-- CHECKED SWITCH -->
<label class="MuiFormControlLabel-root MuiFormControlLabel-labelPlacementEnd">
  <span class="MuiSwitch-root MuiSwitch-sizeMedium">
    <span class="MuiButtonBase-root MuiSwitch-switchBase MuiSwitch-colorPrimary Mui-checked PrivateSwitchBase-root">
      <input role="switch" class="PrivateSwitchBase-input MuiSwitch-input" type="checkbox" checked>
      <span class="MuiSwitch-thumb"></span>
    </span>
    <span class="MuiSwitch-track"></span>
  </span>
  <span class="MuiTypography-root MuiTypography-body1 MuiFormControlLabel-label">Switch</span>
</label>


<!-- DISABLED SWITCH -->
<label class="MuiFormControlLabel-root Mui-disabled MuiFormControlLabel-labelPlacementEnd">
  <span class="MuiSwitch-root MuiSwitch-sizeMedium">
    <span class="MuiButtonBase-root MuiSwitch-switchBase MuiSwitch-colorPrimary Mui-disabled PrivateSwitchBase-root">
      <input disabled role="switch" class="PrivateSwitchBase-input MuiSwitch-input" type="checkbox">
      <span class="MuiSwitch-thumb"></span>
    </span>
    <span class="MuiSwitch-track"></span>
  </span>
  <span class="MuiTypography-root MuiTypography-body1 MuiFormControlLabel-label Mui-disabled">Switch</span>
</label>
HTML;

$icon_buttons = <<<'HTML'
<!-- SMALL ICON BUTTON -->
<button class="MuiButtonBase-root MuiIconButton-root MuiIconButton-colorPrimary MuiIconButton-sizeSmall" tabindex="0" type="button">
    <svg class="MuiSvgIcon-root MuiSvgIcon-fontSizeMedium" focusable="false" aria-hidden="true" viewBox="0 0 24 24">
        <path d="M19 12v7H5v-7H3v7c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2v-7zm-6 .67 2.59-2.58L17 11.5l-5 5-5-5 1.41-1.41L11 12.67V3h2z"></path>
    </svg>
</button>

<!-- MEDIUM ICON BUTTON WITH SMALL ICON -->
<button class="MuiButtonBase-root MuiIconButton-root MuiIconButton-colorPrimary MuiIconButton-sizeMedium" tabindex="0" type="button">
    <svg class="MuiSvgIcon-root MuiSvgIcon-fontSizeSmall" focusable="false" aria-hidden="true" viewBox="0 0 24 24">
        <path d="M19 12v7H5v-7H3v7c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2v-7zm-6 .67 2.59-2.58L17 11.5l-5 5-5-5 1.41-1.41L11 12.67V3h2z"></path>
    </svg>
</button>

<!-- MEDIUM ICON BUTTON WITH MEDIUM ICON -->
<button class="MuiButtonBase-root MuiIconButton-root MuiIconButton-colorPrimary MuiIconButton-sizeMedium" tabindex="0" type="button">
    <svg class="MuiSvgIcon-root MuiSvgIcon-fontSizeMedium" focusable="false" aria-hidden="true" viewBox="0 0 24 24">
        <path d="M19 12v7H5v-7H3v7c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2v-7zm-6 .67 2.59-2.58L17 11.5l-5 5-5-5 1.41-1.41L11 12.67V3h2z"></path>
    </svg>
</button>

<!-- MEDIUM ICON BUTTON WITH LARGE ICON -->
<button class="MuiButtonBase-root MuiIconButton-root MuiIconButton-colorPrimary MuiIconButton-sizeMedium" tabindex="0" type="button">
    <svg class="MuiSvgIcon-root MuiSvgIcon-fontSizeLarge" focusable="false" aria-hidden="true" viewBox="0 0 24 24">
        <path d="M19 12v7H5v-7H3v7c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2v-7zm-6 .67 2.59-2.58L17 11.5l-5 5-5-5 1.41-1.41L11 12.67V3h2z"></path>
    </svg>
</button>

<!-- DISABLED ICON BUTTON -->
<button class="MuiButtonBase-root Mui-disabled MuiIconButton-root MuiIconButton-sizeMedium" tabindex="-1" type="button" disabled>
  <svg class="MuiSvgIcon-root MuiSvgIcon-fontSizeMedium" focusable="false" aria-hidden="true" viewBox="0 0 24 24">
    <path d="M19 12v7H5v-7H3v7c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2v-7zm-6 .67 2.59-2.58L17 11.5l-5 5-5-5 1.41-1.41L11 12.67V3h2z"></path>
  </svg>
</button>
HTML;

$toggle_buttons = <<<'HTML'
<!-- SMALL -->
<div class="MuiToggleButtonGroup-root MuiToggleButtonGroup-horizontal" role="group">
    <button type="button" value="one" tabindex="0" aria-pressed="true"
        class="MuiButtonBase-root MuiToggleButtonGroup-grouped MuiToggleButton-root Mui-selected MuiToggleButton-sizeSmall MuiToggleButton-standard MuiToggleButtonGroup-firstButton"
        onclick="var g=this.closest('.MuiToggleButtonGroup-root');g.querySelectorAll('.MuiToggleButton-root').forEach(function(b){b.classList.remove('Mui-selected');b.setAttribute('aria-pressed','false');b.tabIndex=-1;});this.classList.add('Mui-selected');this.setAttribute('aria-pressed','true');this.tabIndex=0;">
        One
    </button>
    <button type="button" value="two" tabindex="-1" aria-pressed="false"
        class="MuiButtonBase-root MuiToggleButtonGroup-grouped MuiToggleButton-root MuiToggleButton-sizeSmall MuiToggleButton-standard MuiToggleButtonGroup-lastButton"
        onclick="var g=this.closest('.MuiToggleButtonGroup-root');g.querySelectorAll('.MuiToggleButton-root').forEach(function(b){b.classList.remove('Mui-selected');b.setAttribute('aria-pressed','false');b.tabIndex=-1;});this.classList.add('Mui-selected');this.setAttribute('aria-pressed','true');this.tabIndex=0;">
        Two
    </button>
</div>

<!-- MEDIUM -->
<div class="MuiToggleButtonGroup-root MuiToggleButtonGroup-horizontal" role="group">
    <button type="button" value="one" tabindex="0" aria-pressed="true"
        class="MuiButtonBase-root MuiToggleButtonGroup-grouped MuiToggleButton-root Mui-selected MuiToggleButton-sizeMedium MuiToggleButton-standard MuiToggleButtonGroup-firstButton"
        onclick="var g=this.closest('.MuiToggleButtonGroup-root');g.querySelectorAll('.MuiToggleButton-root').forEach(function(b){b.classList.remove('Mui-selected');b.setAttribute('aria-pressed','false');b.tabIndex=-1;});this.classList.add('Mui-selected');this.setAttribute('aria-pressed','true');this.tabIndex=0;">
        One
    </button>
    <button type="button" value="two" tabindex="-1" aria-pressed="false"
        class="MuiButtonBase-root MuiToggleButtonGroup-grouped MuiToggleButton-root MuiToggleButton-sizeMedium MuiToggleButton-standard MuiToggleButtonGroup-lastButton"
        onclick="var g=this.closest('.MuiToggleButtonGroup-root');g.querySelectorAll('.MuiToggleButton-root').forEach(function(b){b.classList.remove('Mui-selected');b.setAttribute('aria-pressed','false');b.tabIndex=-1;});this.classList.add('Mui-selected');this.setAttribute('aria-pressed','true');this.tabIndex=0;">
        Two
    </button>
</div>

<!-- LARGE -->
<div class="MuiToggleButtonGroup-root MuiToggleButtonGroup-horizontal" role="group">
    <button type="button" value="one" tabindex="0" aria-pressed="true"
        class="MuiButtonBase-root MuiToggleButtonGroup-grouped MuiToggleButton-root Mui-selected MuiToggleButton-sizeLarge MuiToggleButton-standard MuiToggleButtonGroup-firstButton"
        onclick="var g=this.closest('.MuiToggleButtonGroup-root');g.querySelectorAll('.MuiToggleButton-root').forEach(function(b){b.classList.remove('Mui-selected');b.setAttribute('aria-pressed','false');b.tabIndex=-1;});this.classList.add('Mui-selected');this.setAttribute('aria-pressed','true');this.tabIndex=0;">
        One
    </button>
    <button type="button" value="two" tabindex="-1" aria-pressed="false"
        class="MuiButtonBase-root MuiToggleButtonGroup-grouped MuiToggleButton-root MuiToggleButton-sizeLarge MuiToggleButton-standard MuiToggleButtonGroup-lastButton"
        onclick="var g=this.closest('.MuiToggleButtonGroup-root');g.querySelectorAll('.MuiToggleButton-root').forEach(function(b){b.classList.remove('Mui-selected');b.setAttribute('aria-pressed','false');b.tabIndex=-1;});this.classList.add('Mui-selected');this.setAttribute('aria-pressed','true');this.tabIndex=0;">
        Two
    </button>
</div>
HTML;

$tooltip = <<<'HTML'
<button class="MuiButtonBase-root MuiIconButton-root MuiIconButton-sizeMedium"
        tabindex="0"
        type="button"
        aria-label="tooltip">
  <svg class="MuiSvgIcon-root MuiSvgIcon-fontSizeMedium"
       focusable="false"
       aria-hidden="true"
       viewBox="0 0 24 24">
    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2m1 15h-2v-6h2zm0-8h-2V7h2z"></path>
  </svg>
</button>
HTML;

$link = <<<'HTML'
<a class="MuiTypography-root MuiTypography-inherit MuiLink-root MuiLink-underlineAlways" href="#">Link</a>
HTML;

$divider = <<<'HTML'
<hr class="MuiDivider-root MuiDivider-fullWidth">
HTML;

$typography = <<<'HTML'
<!-- HEADINGS -->
<h1 class="MuiTypography-root MuiTypography-h1">Heading</h1>
<h2 class="MuiTypography-root MuiTypography-h2">Heading</h2>
<h3 class="MuiTypography-root MuiTypography-h3">Heading</h3>
<h4 class="MuiTypography-root MuiTypography-h4">Heading</h4>
<h5 class="MuiTypography-root MuiTypography-h5">Heading</h5>
<h6 class="MuiTypography-root MuiTypography-h6">Heading</h6>

<!-- SUBTITLES -->
<h6 class="MuiTypography-root MuiTypography-subtitle1">Subtitle</h6>
<h6 class="MuiTypography-root MuiTypography-subtitle2">Subtitle</h6>

<!-- BODY TEXT -->
<p class="MuiTypography-root MuiTypography-body1">Body</p>
<p class="MuiTypography-root MuiTypography-body2">Body</p>

<!-- BUTTON TEXT -->
<span class="MuiTypography-root MuiTypography-button">Button Text</span>

<!-- CAPTION -->
<span class="MuiTypography-root MuiTypography-caption">Caption</span>

<!-- OVERLINE -->
<span class="MuiTypography-root MuiTypography-overline">Overline</span>
HTML;

$tabs = <<<'HTML'
<div class="MuiTabs-root" style="position:relative;">
    <div class="MuiTabs-scroller MuiTabs-fixed" style="overflow:hidden;margin-bottom:0;position:relative;">
        <div role="tablist" class="MuiTabs-list" style="display:flex;position:relative;">

            <button class="MuiButtonBase-root MuiTab-root MuiTab-textColorPrimary Mui-selected"
                tabindex="0" type="button" role="tab" aria-selected="true"
                onclick="
                    var root=this.closest('.MuiTabs-root'),tabs=root.querySelectorAll('[role=tab]'),indicator=root.querySelector('.MuiTabs-indicator'),scroller=root.querySelector('.MuiTabs-scroller');
                    tabs.forEach(function(tab){tab.classList.toggle('Mui-selected',tab===event.currentTarget);tab.setAttribute('aria-selected',String(tab===event.currentTarget));tab.tabIndex=tab===event.currentTarget?0:-1;},this);
                    indicator.style.left=(this.getBoundingClientRect().left-scroller.getBoundingClientRect().left+scroller.scrollLeft)+'px';
                    indicator.style.width=this.getBoundingClientRect().width+'px';
                ">
                Tab One
            </button>

            <button class="MuiButtonBase-root MuiTab-root MuiTab-textColorPrimary"
                tabindex="-1" type="button" role="tab" aria-selected="false"
                onclick="
                    var root=this.closest('.MuiTabs-root'),tabs=root.querySelectorAll('[role=tab]'),indicator=root.querySelector('.MuiTabs-indicator'),scroller=root.querySelector('.MuiTabs-scroller'),selected=this;
                    tabs.forEach(function(tab){tab.classList.toggle('Mui-selected',tab===selected);tab.setAttribute('aria-selected',String(tab===selected));tab.tabIndex=tab===selected?0:-1;});
                    indicator.style.left=(this.getBoundingClientRect().left-scroller.getBoundingClientRect().left+scroller.scrollLeft)+'px';
                    indicator.style.width=this.getBoundingClientRect().width+'px';
                ">
                Tab Two
            </button>

            <button class="MuiButtonBase-root Mui-disabled MuiTab-root MuiTab-textColorPrimary"
                tabindex="-1" type="button" disabled role="tab" aria-selected="false">
                Disabled
            </button>
        </div>

        <span class="MuiTabs-indicator" style="position:absolute;bottom:0;left:0;width:0;transition:left 300ms ease,width 300ms ease;"></span>
    </div>
</div>

<script>
(function(){
    var root=document.currentScript.previousElementSibling;
    var active=root.querySelector('[role=tab].Mui-selected');
    var indicator=root.querySelector('.MuiTabs-indicator');
    var scroller=root.querySelector('.MuiTabs-scroller');
    if(active){
        indicator.style.transition='none';
        indicator.style.left=(active.getBoundingClientRect().left-scroller.getBoundingClientRect().left+scroller.scrollLeft)+'px';
        indicator.style.width=active.getBoundingClientRect().width+'px';
        requestAnimationFrame(function(){indicator.style.transition='left 300ms ease,width 300ms ease';});
    }
})();
</script>
HTML;

$lists = <<<'HTML'
<ul class="MuiList-root MuiList-padding">

  <!-- LIST ITEM WITH SECONDARY TEXT -->
  <li class="MuiListItem-root MuiListItem-gutters MuiListItem-padding">
    <div class="MuiListItemText-root MuiListItemText-multiline">
      <span class="MuiTypography-root MuiTypography-body1 MuiListItemText-primary">
        List Item
      </span>
      <p class="MuiTypography-root MuiTypography-body2 MuiListItemText-secondary">
        Secondary text
      </p>
    </div>
  </li>

  <!-- LIST ITEM WITH ICON -->
  <li class="MuiListItem-root MuiListItem-gutters MuiListItem-padding">
    <div class="MuiListItemIcon-root">
      <svg class="MuiSvgIcon-root MuiSvgIcon-fontSizeMedium"
           focusable="false"
           aria-hidden="true"
           viewBox="0 0 24 24">
        <path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"></path>
      </svg>
    </div>
    <div class="MuiListItemText-root">
      <span class="MuiTypography-root MuiTypography-body1 MuiListItemText-primary">
        Clickable List Item
      </span>
    </div>
  </li>

  <!-- DISABLED LIST ITEM -->
  <li disabled class="MuiListItem-root MuiListItem-gutters MuiListItem-padding">
    <div class="MuiListItemText-root">
      <span class="MuiTypography-root MuiTypography-body1 MuiListItemText-primary">
        Disabled List Item
      </span>
    </div>
  </li>

</ul>
HTML;

$text_fields = <<<'HTML'
<!-- STANDARD TEXT FIELD -->
<!-- STANDARD TEXT FIELD -->
<div class="MuiFormControl-root MuiTextField-root" style="position:relative;">
    <label
        class="MuiFormLabel-root MuiInputLabel-root MuiInputLabel-formControl MuiInputLabel-animated MuiInputLabel-outlined MuiFormLabel-colorPrimary"
        data-shrink="false"
        for="static-text-field"
        id="static-text-field-label"
        style="position:absolute; top:0; left:0; z-index:1; pointer-events:none; transform:translate(14px,16px) scale(1); transform-origin:top left; transition:transform 200ms ease;">
        Text Field
    </label>

    <div class="MuiInputBase-root MuiOutlinedInput-root MuiInputBase-colorPrimary MuiInputBase-formControl" style="position:relative;">
        <input
            aria-invalid="false"
            id="static-text-field"
            class="MuiInputBase-input MuiOutlinedInput-input"
            type="text"
            aria-labelledby="static-text-field-label"
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
                <span style="padding:0 5px; display:inline-block; opacity:0;">Text Field</span>
            </legend>
        </fieldset>
    </div>
</div>


<!-- DISABLED TEXT FIELD -->
<div class="MuiFormControl-root MuiTextField-root">
  <label
    class="MuiFormLabel-root MuiInputLabel-root MuiInputLabel-formControl MuiInputLabel-animated MuiInputLabel-outlined MuiFormLabel-colorPrimary Mui-disabled"
    data-shrink="false"
    for="static-disabled-field"
    id="static-disabled-field-label">
    Disabled Field
  </label>

  <div class="MuiInputBase-root MuiOutlinedInput-root MuiInputBase-colorPrimary Mui-disabled MuiInputBase-formControl">
    <input
      aria-invalid="false"
      disabled
      id="static-disabled-field"
      class="MuiInputBase-input MuiOutlinedInput-input Mui-disabled"
      type="text">

    <fieldset aria-hidden="true" class="MuiOutlinedInput-notchedOutline">
      <legend>
        <span>Disabled Field</span>
      </legend>
    </fieldset>
  </div>
</div>

<!-- ERROR TEXT FIELD -->
<div class="MuiFormControl-root MuiTextField-root" style="position:relative;">
    <label
        class="MuiFormLabel-root MuiInputLabel-root MuiInputLabel-formControl MuiInputLabel-animated MuiInputLabel-outlined MuiFormLabel-colorPrimary Mui-error"
        data-shrink="false"
        for="static-error-field"
        id="static-error-field-label"
        style="position:absolute; top:0; left:0; z-index:1; pointer-events:none; transform:translate(14px,16px) scale(1); transform-origin:top left; transition:transform 200ms ease;">
        Error Field
    </label>

    <div class="MuiInputBase-root MuiOutlinedInput-root MuiInputBase-colorPrimary Mui-error MuiInputBase-formControl" style="position:relative;">
        <input
            aria-invalid="true"
            aria-describedby="static-error-field-helper-text"
            aria-labelledby="static-error-field-label"
            id="static-error-field"
            class="MuiInputBase-input MuiOutlinedInput-input"
            type="text"
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
                <span style="padding:0 5px; display:inline-block; opacity:0;">Error Field</span>
            </legend>
        </fieldset>
    </div>

    <p class="MuiFormHelperText-root Mui-error MuiFormHelperText-sizeMedium MuiFormHelperText-contained" id="static-error-field-helper-text">
        There is an error.
    </p>
</div>

<!-- MULTILINE TEXT FIELD -->
<div class="MuiFormControl-root MuiTextField-root" style="position:relative;">
    <label
        class="MuiFormLabel-root MuiInputLabel-root MuiInputLabel-formControl MuiInputLabel-animated MuiInputLabel-outlined MuiFormLabel-colorPrimary"
        data-shrink="false"
        for="static-multiline-field"
        id="static-multiline-field-label"
        style="position:absolute; top:0; left:0; z-index:1; pointer-events:none; transform:translate(14px,16px) scale(1); transform-origin:top left; transition:transform 200ms ease;">
        Multiline Field
    </label>

    <div class="MuiInputBase-root MuiOutlinedInput-root MuiInputBase-colorPrimary MuiInputBase-formControl MuiInputBase-multiline" style="position:relative;">
        <textarea
            rows="3"
            aria-invalid="false"
            aria-labelledby="static-multiline-field-label"
            id="static-multiline-field"
            class="MuiInputBase-input MuiOutlinedInput-input MuiInputBase-inputMultiline"
            style="height:84px; resize:vertical;"
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
        ></textarea>

        <fieldset aria-hidden="true" class="MuiOutlinedInput-notchedOutline">
            <legend style="float:unset; width:auto; overflow:hidden; display:block; height:11px; padding:0; font-size:0.75em; visibility:hidden; white-space:nowrap; max-width:0.01px; transition:max-width 200ms ease;">
                <span style="padding:0 5px; display:inline-block; opacity:0;">Multiline Field</span>
            </legend>
        </fieldset>
    </div>
</div>
HTML;

$select = <<<'HTML'
<!-- SELECT FIELD -->
<div class="MuiFormControl-root" style="width:100%; min-width:220px; position:relative;">

    <label id="static-sample-type-label"
        class="MuiFormLabel-root MuiInputLabel-root MuiInputLabel-formControl MuiInputLabel-animated MuiInputLabel-outlined MuiFormLabel-colorPrimary"
        data-shrink="false"
        style="position:absolute; top:0; left:0; z-index:1; pointer-events:none; transform:translate(14px,16px) scale(1); transform-origin:top left; transition:transform 200ms ease;">
        Sample Type
    </label>

    <div class="MuiInputBase-root MuiOutlinedInput-root MuiInputBase-colorPrimary MuiInputBase-formControl MuiSelect-root"
        style="position:relative; width:100%; min-width:220px; height:56px; box-sizing:border-box;">

        <div class="MuiSelect-select MuiSelect-outlined MuiInputBase-input MuiOutlinedInput-input"
            tabindex="0"
            role="combobox"
            aria-labelledby="static-sample-type-label"
            aria-expanded="false"
            aria-haspopup="listbox"
            aria-controls="static-sample-type-menu"
            style="display:flex; align-items:center; width:100%; min-height:56px; box-sizing:border-box; padding:0 32px 0 14px; cursor:pointer;"

            onclick="
                var root=this.parentElement,
                    group=root.parentElement,
                    label=group.querySelector('label'),
                    menu=group.querySelector('[role=listbox]'),
                    legend=root.querySelector('legend'),
                    input=group.querySelector('input[id=static-sample-type]');

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

        <input
            type="hidden"
            name="sampleType"
            id="static-sample-type"
            class="MuiSelect-nativeInput"
            value=""
        />

        <svg class="MuiSvgIcon-root MuiSvgIcon-fontSizeMedium MuiSelect-icon"
            focusable="false"
            aria-hidden="true"
            viewBox="0 0 24 24"
            style="pointer-events:none; transition:transform 180ms ease;">
            <path d="M7 10l5 5 5-5z"></path>
        </svg>

        <fieldset aria-hidden="true" class="MuiOutlinedInput-notchedOutline">
            <legend style="float:unset; width:auto; overflow:hidden; display:block; height:11px; padding:0; font-size:0.75em; visibility:hidden; white-space:nowrap; max-width:0.01px; transition:max-width 200ms ease;">
                <span style="padding:0 5px; display:inline-block; opacity:0;">Sample Type</span>
            </legend>
        </fieldset>
    </div>

    <!-- DROPDOWN MENU -->
    <div id="static-sample-type-menu"
        role="listbox"
        aria-labelledby="static-sample-type-label"
        class="MuiPaper-root MuiPaper-elevation MuiPaper-rounded MuiMenu-paper MuiPopover-paper"
        style="
            display:block;
            position:absolute;
            top:56px;
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

            <li class="MuiButtonBase-root MuiMenuItem-root MuiMenuItem-gutters"
                role="option" aria-selected="false" tabindex="-1" data-value="TypeA"
                style="cursor:pointer; padding:6px 16px; min-height:36px;"
                onmouseenter="this.style.background='rgba(0,0,0,0.04)'"
                onmouseleave="this.style.background=''"
                onclick="
                    var group=this.closest('.MuiFormControl-root'),
                        root=group.querySelector('.MuiSelect-root'),
                        select=root.querySelector('[role=combobox]'),
                        input=group.querySelector('#static-sample-type'),
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
            >Type A</li>

            <li class="MuiButtonBase-root MuiMenuItem-root MuiMenuItem-gutters"
                role="option" aria-selected="false" tabindex="-1" data-value="TypeB"
                style="cursor:pointer; padding:6px 16px; min-height:36px;"
                onmouseenter="this.style.background='rgba(0,0,0,0.04)'"
                onmouseleave="this.style.background=''"
                onclick="
                    var group=this.closest('.MuiFormControl-root'),
                        root=group.querySelector('.MuiSelect-root'),
                        select=root.querySelector('[role=combobox]'),
                        input=group.querySelector('#static-sample-type'),
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
            >Type B</li>

            <li class="MuiButtonBase-root MuiMenuItem-root MuiMenuItem-gutters"
                role="option" aria-selected="false" tabindex="-1" data-value="TypeC"
                style="cursor:pointer; padding:6px 16px; min-height:36px;"
                onmouseenter="this.style.background='rgba(0,0,0,0.04)'"
                onmouseleave="this.style.background=''"
                onclick="
                    var group=this.closest('.MuiFormControl-root'),
                        root=group.querySelector('.MuiSelect-root'),
                        select=root.querySelector('[role=combobox]'),
                        input=group.querySelector('#static-sample-type'),
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
            >Type C</li>
        </ul>
    </div>

    <p class="MuiFormHelperText-root MuiFormHelperText-sizeMedium MuiFormHelperText-contained">
        Select a sample type
    </p>
</div>
HTML;

$chips = <<<'HTML'
<!-- DEFAULT CHIP -->
<div class="MuiChip-root MuiChip-filled MuiChip-sizeMedium MuiChip-colorDefault">
  <span class="MuiChip-label">Default Chip</span>
</div>


<!-- PRIMARY CHIP -->
<div class="MuiChip-root MuiChip-filled MuiChip-sizeMedium MuiChip-colorPrimary">
  <span class="MuiChip-label">Primary Chip</span>
</div>


<!-- OUTLINED CHIP -->
<div class="MuiChip-root MuiChip-outlined MuiChip-sizeMedium MuiChip-colorPrimary">
  <span class="MuiChip-label">Outlined Chip</span>
</div>
HTML;

?>
<!DOCTYPE html>
<html lang="<?php echo $LANG_TAG ?>">
	<head>
		<title>NEON Interface Component Reference</title>
		<?php include_once($SERVER_ROOT.'/includes/head.php'); ?>
		<style>
			.component-reference {
				max-width: 1200px;
				margin: 0 auto;
				padding: 24px;
			}

			.component-reference__intro {
				margin-bottom: 36px;
			}

			.component-section {
				margin-bottom: 48px;
				padding-bottom: 40px;
				border-bottom: 1px solid #ddd;
			}

			.component-section:last-child {
				border-bottom: 0;
			}

			.component-section h2 {
				margin: 0 0 8px;
			}

			.component-section__description {
				margin: 0 0 20px;
				color: #555;
			}

			.component-demo {
				margin-bottom: 20px;
				padding: 24px;
				background: #fff;
				border: 1px solid #ddd;
				border-radius: 4px;
				overflow-x: auto;
			}

			.component-demo > * {
				margin-right: 12px;
				margin-bottom: 12px;
			}

			.component-code {
				margin: 0;
				padding: 18px;
				overflow-x: auto;
				background: #f5f6f7;
				border: 1px solid #d9dcdf;
				border-radius: 4px;
				font-family: monospace;
				font-size: 13px;
				line-height: 1.5;
				white-space: pre;
			}

			.component-code code {
				font-family: inherit;
				background: #f5f6f7;
			}
		</style>
	</head>
	<body>
		<div role="main" id="innertext" class="component-reference">
			<div class="component-reference__intro">
				<h1>NEON Interface Component Reference</h1>
			</div>

			<?php
			renderComponentExample('Accordion', 'Expandable content with actions.', $accordion);
			renderComponentExample('Buttons', 'Text, outlined, contained, size, icon, and disabled button states.', $buttons);
			renderComponentExample('Button Groups', 'Outlined and text button groups in multiple sizes.', $button_groups);
			renderComponentExample('Card', 'Card/callout content with actions.', $card);
			renderComponentExample('Checkboxes', 'Unchecked, checked, and disabled checkbox states.', $checkboxes);
			renderComponentExample('Radio Buttons', 'Unchecked, checked, and disabled radio states.', $radio_buttons);
			renderComponentExample('Switches', 'Off, on, and disabled switch states.', $switches);
			renderComponentExample('Icon Buttons', 'Small, medium, large, and disabled icon buttons.', $icon_buttons);
			renderComponentExample('Toggle Buttons', 'Small, medium, and large toggle button groups.', $toggle_buttons);
			renderComponentExample('Tooltip', 'Basic tooltip trigger.', $tooltip);
			renderComponentExample('Link', 'Standard themed link.', $link);
			renderComponentExample('Divider', 'Horizontal divider.', $divider);
			renderComponentExample('Typography', 'Heading, subtitle, body, button, caption, and overline variants.', $typography);
			renderComponentExample('Tabs', 'Selected, unselected, and disabled tab states.', $tabs);
			renderComponentExample('Lists', 'Standard, clickable, secondary-text, and disabled list items.', $lists);
			renderComponentExample('Text Fields', 'Standard text field.', $text_fields);
			renderComponentExample('Select', 'Outlined select with helper text.', $select);
			renderComponentExample('Chips', 'Default, primary, and outlined chips.', $chips);
			?>
		</div>
		<?php
		include($SERVER_ROOT . '/includes/footer.php');
		?>
	</body>
</html>