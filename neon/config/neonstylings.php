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
<div class="MuiPaper-root MuiAccordion-root MuiAccordion-rounded MuiPaper-elevation1 MuiPaper-rounded">
	<div
		aria-disabled="false"
		aria-expanded="false"
		class="MuiButtonBase-root MuiAccordionSummary-root"
		role="button"
		tabindex="0"
		onclick="
			var accordion = this.closest('.MuiAccordion-root');
			var content = accordion.querySelector('.accordion-content');
			var icon = accordion.querySelector('.MuiAccordionSummary-expandIcon');
			var isExpanded = this.getAttribute('aria-expanded') === 'true';

			this.setAttribute('aria-expanded', isExpanded ? 'false' : 'true');

			if (isExpanded) {
				content.style.maxHeight = '0';
			} else {
				content.style.maxHeight = content.scrollHeight + 'px';
			}

			icon.style.transform = isExpanded ? '' : 'rotate(180deg)';

			this.classList.toggle('Mui-expanded', !isExpanded);
			accordion.classList.toggle('Mui-expanded', !isExpanded);
			icon.classList.toggle('Mui-expanded', !isExpanded);
		"
	>
		<div class="MuiAccordionSummary-content">
			Basic Accordion
		</div>

		<div
			aria-disabled="false"
			aria-hidden="true"
			class="MuiButtonBase-root MuiIconButton-root MuiAccordionSummary-expandIcon MuiIconButton-colorPrimary MuiIconButton-edgeEnd"
			style="transition: transform 0.25s ease;"
		>
			<span class="MuiIconButton-label">
				<svg
					aria-hidden="true"
					class="MuiSvgIcon-root"
					focusable="false"
					viewbox="0 0 24 24"
				>
					<path d="M16.59 8.59L12 13.17 7.41 8.59 6 10l6 6 6-6z">
					</path>
				</svg>
			</span>

			<span class="MuiTouchRipple-root">
			</span>
		</div>
	</div>

	<div
		class="accordion-content"
		style="
			max-height: 0;
			overflow: hidden;
			transition: max-height 0.25s ease;
		"
	>
		<div role="region">
			<div class="MuiAccordionDetails-root">
				<p class="MuiTypography-root MuiTypography-body1">
					Accordion Details
				</p>
			</div>

			<div class="MuiAccordionActions-root MuiAccordionActions-spacing">
				<button
					class="MuiButtonBase-root MuiButton-root MuiButton-text MuiButton-textPrimary"
					tabindex="0"
					type="button"
				>
					<span class="MuiButton-label">
						Cancel
					</span>

					<span class="MuiTouchRipple-root">
					</span>
				</button>

				<button
					class="MuiButtonBase-root MuiButton-root MuiButton-text MuiButton-textPrimary"
					tabindex="0"
					type="button"
				>
					<span class="MuiButton-label">
						Action
					</span>

					<span class="MuiTouchRipple-root">
					</span>
				</button>
			</div>
		</div>
	</div>
</div>
HTML;

$buttons = <<<'HTML'
			<button class="MuiButtonBase-root MuiButton-root MuiButton-text MuiButton-textPrimary MuiButton-textSizeSmall MuiButton-sizeSmall" tabindex="0" type="button">
				<span class="MuiButton-label">
					Text
				</span>
				<span class="MuiTouchRipple-root">
				</span>
			</button>
			<button class="MuiButtonBase-root MuiButton-root MuiButton-text MuiButton-textPrimary" tabindex="0" type="button">
				<span class="MuiButton-label">
					Text
				</span>
				<span class="MuiTouchRipple-root">
				</span>
			</button>
			<button class="MuiButtonBase-root MuiButton-root MuiButton-text MuiButton-textPrimary MuiButton-textSizeLarge MuiButton-sizeLarge" tabindex="0" type="button">
				<span class="MuiButton-label">
					Text
				</span>
				<span class="MuiTouchRipple-root">
				</span>
			</button>
			<button class="MuiButtonBase-root MuiButton-root MuiButton-text MuiButton-textPrimary MuiButton-textSizeSmall MuiButton-sizeSmall Mui-disabled Mui-disabled" disabled="" tabindex="-1" type="button">
				<span class="MuiButton-label">
					Text
				</span>
			</button>
			<button class="MuiButtonBase-root MuiButton-root MuiButton-text MuiButton-textPrimary Mui-disabled Mui-disabled" disabled="" tabindex="-1" type="button">
				<span class="MuiButton-label">
					Text
				</span>
			</button>
			<button class="MuiButtonBase-root MuiButton-root MuiButton-text MuiButton-textPrimary MuiButton-textSizeLarge MuiButton-sizeLarge Mui-disabled Mui-disabled" disabled="" tabindex="-1" type="button">
				<span class="MuiButton-label">
					Text
				</span>
			</button>
			<button class="MuiButtonBase-root MuiButton-root MuiButton-outlined MuiButton-outlinedPrimary MuiButton-outlinedSizeSmall MuiButton-sizeSmall" tabindex="0" type="button">
				<span class="MuiButton-label">
					<span class="MuiButton-startIcon MuiButton-iconSizeSmall">
						<svg aria-hidden="true" class="MuiSvgIcon-root" focusable="false" viewbox="0 0 24 24">
							<path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z">
							</path>
						</svg>
					</span>
					Outlined
				</span>
				<span class="MuiTouchRipple-root">
				</span>
			</button>
			<button class="MuiButtonBase-root MuiButton-root MuiButton-outlined MuiButton-outlinedPrimary" tabindex="0" type="button">
				<span class="MuiButton-label">
					<span class="MuiButton-startIcon MuiButton-iconSizeMedium">
						<svg aria-hidden="true" class="MuiSvgIcon-root" focusable="false" viewbox="0 0 24 24">
							<path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z">
							</path>
						</svg>
					</span>
					Outlined
				</span>
				<span class="MuiTouchRipple-root">
				</span>
			</button>
			<button class="MuiButtonBase-root MuiButton-root MuiButton-outlined MuiButton-outlinedPrimary MuiButton-outlinedSizeLarge MuiButton-sizeLarge" tabindex="0" type="button">
				<span class="MuiButton-label">
					<span class="MuiButton-startIcon MuiButton-iconSizeLarge">
						<svg aria-hidden="true" class="MuiSvgIcon-root" focusable="false" viewbox="0 0 24 24">
							<path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z">
							</path>
						</svg>
					</span>
					Outlined
				</span>
				<span class="MuiTouchRipple-root">
				</span>
			</button>
			<button class="MuiButtonBase-root MuiButton-root MuiButton-outlined MuiButton-outlinedPrimary MuiButton-outlinedSizeSmall MuiButton-sizeSmall Mui-disabled Mui-disabled" disabled="" tabindex="-1" type="button">
				<span class="MuiButton-label">
					Outlined
				</span>
			</button>
			<button class="MuiButtonBase-root MuiButton-root MuiButton-outlined MuiButton-outlinedPrimary Mui-disabled Mui-disabled" disabled="" tabindex="-1" type="button">
				<span class="MuiButton-label">
					Outlined
				</span>
			</button>
			<button class="MuiButtonBase-root MuiButton-root MuiButton-outlined MuiButton-outlinedPrimary MuiButton-outlinedSizeLarge MuiButton-sizeLarge Mui-disabled Mui-disabled" disabled="" tabindex="-1" type="button">
				<span class="MuiButton-label">
					Outlined
				</span>
			</button>
			<button class="MuiButtonBase-root MuiButton-root MuiButton-contained MuiButton-containedPrimary MuiButton-containedSizeSmall MuiButton-sizeSmall" tabindex="0" type="button">
				<span class="MuiButton-label">
					Contained
					<span class="MuiButton-endIcon MuiButton-iconSizeSmall">
						<svg aria-hidden="true" class="MuiSvgIcon-root" focusable="false" viewbox="0 0 24 24">
							<path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z">
							</path>
						</svg>
					</span>
				</span>
				<span class="MuiTouchRipple-root">
				</span>
			</button>
			<button class="MuiButtonBase-root MuiButton-root MuiButton-contained MuiButton-containedPrimary" tabindex="0" type="button">
				<span class="MuiButton-label">
					Contained
					<span class="MuiButton-endIcon MuiButton-iconSizeMedium">
						<svg aria-hidden="true" class="MuiSvgIcon-root" focusable="false" viewbox="0 0 24 24">
							<path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z">
							</path>
						</svg>
					</span>
				</span>
				<span class="MuiTouchRipple-root">
				</span>
			</button>
			<button class="MuiButtonBase-root MuiButton-root MuiButton-contained MuiButton-containedPrimary MuiButton-containedSizeLarge MuiButton-sizeLarge" tabindex="0" type="button">
				<span class="MuiButton-label">
					Contained
					<span class="MuiButton-endIcon MuiButton-iconSizeLarge">
						<svg aria-hidden="true" class="MuiSvgIcon-root" focusable="false" viewbox="0 0 24 24">
							<path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z">
							</path>
						</svg>
					</span>
				</span>
				<span class="MuiTouchRipple-root">
				</span>
			</button>
			<button class="MuiButtonBase-root MuiButton-root MuiButton-contained MuiButton-containedPrimary MuiButton-containedSizeSmall MuiButton-sizeSmall Mui-disabled Mui-disabled" disabled="" tabindex="-1" type="button">
				<span class="MuiButton-label">
					Contained
				</span>
			</button>
			<button class="MuiButtonBase-root MuiButton-root MuiButton-contained MuiButton-containedPrimary Mui-disabled Mui-disabled" disabled="" tabindex="-1" type="button">
				<span class="MuiButton-label">
					Contained
				</span>
			</button>
			<button class="MuiButtonBase-root MuiButton-root MuiButton-contained MuiButton-containedPrimary MuiButton-containedSizeLarge MuiButton-sizeLarge Mui-disabled Mui-disabled" disabled="" tabindex="-1" type="button">
				<span class="MuiButton-label">
					Contained
				</span>
			</button>
HTML;

$button_groups = <<<'HTML'
			<div class="MuiButtonGroup-root" role="group">
				<button class="MuiButtonBase-root MuiButton-root MuiButton-outlined MuiButtonGroup-grouped MuiButtonGroup-groupedHorizontal MuiButtonGroup-groupedOutlined MuiButtonGroup-groupedOutlinedHorizontal MuiButtonGroup-groupedOutlinedPrimary MuiButton-outlinedPrimary MuiButton-outlinedSizeSmall MuiButton-sizeSmall" tabindex="0" type="button">
					<span class="MuiButton-label">
						One
					</span>
					<span class="MuiTouchRipple-root">
					</span>
				</button>
				<button class="MuiButtonBase-root MuiButton-root MuiButton-outlined MuiButtonGroup-grouped MuiButtonGroup-groupedHorizontal MuiButtonGroup-groupedOutlined MuiButtonGroup-groupedOutlinedHorizontal MuiButtonGroup-groupedOutlinedPrimary MuiButton-outlinedPrimary MuiButton-outlinedSizeSmall MuiButton-sizeSmall" tabindex="0" type="button">
					<span class="MuiButton-label">
						Two
					</span>
					<span class="MuiTouchRipple-root">
					</span>
				</button>
			</div>
			<div class="MuiButtonGroup-root" role="group">
				<button class="MuiButtonBase-root MuiButton-root MuiButton-outlined MuiButtonGroup-grouped MuiButtonGroup-groupedHorizontal MuiButtonGroup-groupedOutlined MuiButtonGroup-groupedOutlinedHorizontal MuiButtonGroup-groupedOutlinedPrimary MuiButton-outlinedPrimary" tabindex="0" type="button">
					<span class="MuiButton-label">
						One
					</span>
					<span class="MuiTouchRipple-root">
					</span>
				</button>
				<button class="MuiButtonBase-root MuiButton-root MuiButton-outlined MuiButtonGroup-grouped MuiButtonGroup-groupedHorizontal MuiButtonGroup-groupedOutlined MuiButtonGroup-groupedOutlinedHorizontal MuiButtonGroup-groupedOutlinedPrimary MuiButton-outlinedPrimary" tabindex="0" type="button">
					<span class="MuiButton-label">
						Two
					</span>
					<span class="MuiTouchRipple-root">
					</span>
				</button>
			</div>
			<div class="MuiButtonGroup-root" role="group">
				<button class="MuiButtonBase-root MuiButton-root MuiButton-outlined MuiButtonGroup-grouped MuiButtonGroup-groupedHorizontal MuiButtonGroup-groupedOutlined MuiButtonGroup-groupedOutlinedHorizontal MuiButtonGroup-groupedOutlinedPrimary MuiButton-outlinedPrimary MuiButton-outlinedSizeLarge MuiButton-sizeLarge" tabindex="0" type="button">
					<span class="MuiButton-label">
						One
					</span>
					<span class="MuiTouchRipple-root">
					</span>
				</button>
				<button class="MuiButtonBase-root MuiButton-root MuiButton-outlined MuiButtonGroup-grouped MuiButtonGroup-groupedHorizontal MuiButtonGroup-groupedOutlined MuiButtonGroup-groupedOutlinedHorizontal MuiButtonGroup-groupedOutlinedPrimary MuiButton-outlinedPrimary MuiButton-outlinedSizeLarge MuiButton-sizeLarge" tabindex="0" type="button">
					<span class="MuiButton-label">
						Two
					</span>
					<span class="MuiTouchRipple-root">
					</span>
				</button>
			</div>
			<div class="MuiButtonGroup-root" role="group">
				<button class="MuiButtonBase-root MuiButton-root MuiButton-text MuiButtonGroup-grouped MuiButtonGroup-groupedHorizontal MuiButtonGroup-groupedText MuiButtonGroup-groupedTextHorizontal MuiButtonGroup-groupedTextPrimary MuiButton-textPrimary MuiButton-textSizeSmall MuiButton-sizeSmall" tabindex="0" type="button">
					<span class="MuiButton-label">
						One
					</span>
					<span class="MuiTouchRipple-root">
					</span>
				</button>
				<button class="MuiButtonBase-root MuiButton-root MuiButton-text MuiButtonGroup-grouped MuiButtonGroup-groupedHorizontal MuiButtonGroup-groupedText MuiButtonGroup-groupedTextHorizontal MuiButtonGroup-groupedTextPrimary MuiButton-textPrimary MuiButton-textSizeSmall MuiButton-sizeSmall" tabindex="0" type="button">
					<span class="MuiButton-label">
						Two
					</span>
					<span class="MuiTouchRipple-root">
					</span>
				</button>
			</div>
			<div class="MuiButtonGroup-root" role="group">
				<button class="MuiButtonBase-root MuiButton-root MuiButton-text MuiButtonGroup-grouped MuiButtonGroup-groupedHorizontal MuiButtonGroup-groupedText MuiButtonGroup-groupedTextHorizontal MuiButtonGroup-groupedTextPrimary MuiButton-textPrimary" tabindex="0" type="button">
					<span class="MuiButton-label">
						One
					</span>
					<span class="MuiTouchRipple-root">
					</span>
				</button>
				<button class="MuiButtonBase-root MuiButton-root MuiButton-text MuiButtonGroup-grouped MuiButtonGroup-groupedHorizontal MuiButtonGroup-groupedText MuiButtonGroup-groupedTextHorizontal MuiButtonGroup-groupedTextPrimary MuiButton-textPrimary" tabindex="0" type="button">
					<span class="MuiButton-label">
						Two
					</span>
					<span class="MuiTouchRipple-root">
					</span>
				</button>
			</div>
			<div class="MuiButtonGroup-root" role="group">
				<button class="MuiButtonBase-root MuiButton-root MuiButton-text MuiButtonGroup-grouped MuiButtonGroup-groupedHorizontal MuiButtonGroup-groupedText MuiButtonGroup-groupedTextHorizontal MuiButtonGroup-groupedTextPrimary MuiButton-textPrimary MuiButton-textSizeLarge MuiButton-sizeLarge" tabindex="0" type="button">
					<span class="MuiButton-label">
						One
					</span>
					<span class="MuiTouchRipple-root">
					</span>
				</button>
				<button class="MuiButtonBase-root MuiButton-root MuiButton-text MuiButtonGroup-grouped MuiButtonGroup-groupedHorizontal MuiButtonGroup-groupedText MuiButtonGroup-groupedTextHorizontal MuiButtonGroup-groupedTextPrimary MuiButton-textPrimary MuiButton-textSizeLarge MuiButton-sizeLarge" tabindex="0" type="button">
					<span class="MuiButton-label">
						Two
					</span>
					<span class="MuiTouchRipple-root">
					</span>
				</button>
			</div>
HTML;

$card = <<<'HTML'
			<div class="MuiPaper-root MuiCard-root MuiPaper-outlined MuiPaper-rounded">
				<div class="MuiCardContent-root">
					<h2 class="MuiTypography-root MuiTypography-h5">
						Card
					</h2>
					<p class="MuiTypography-root MuiTypography-body2 MuiTypography-colorTextSecondary">
						Card content
					</p>
				</div>
				<div class="MuiCardActions-root MuiCardActions-spacing">
					<button class="MuiButtonBase-root MuiButton-root MuiButton-outlined MuiButton-outlinedPrimary" tabindex="0" type="button">
						<span class="MuiButton-label">
							Action
						</span>
						<span class="MuiTouchRipple-root">
						</span>
					</button>
				</div>
			</div>
HTML;

$checkboxes = <<<'HTML'
<label class="MuiFormControlLabel-root">
	<span aria-disabled="false" class="MuiButtonBase-root MuiIconButton-root MuiCheckbox-root MuiCheckbox-colorPrimary MuiIconButton-colorPrimary">
		<span class="MuiIconButton-label">
			<input
				data-indeterminate="false"
				type="checkbox"
				value=""
				style="position:absolute; opacity:0; width:100%; height:100%; top:0; left:0; margin:0; cursor:inherit;"
				onchange="
					var root = this.closest('.MuiCheckbox-root');
					var path = root.querySelector('path');

					if (this.checked) {
						root.classList.add('Mui-checked');
						path.setAttribute('d', 'M19 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.11 0 2-.9 2-2V5c0-1.1-.89-2-2-2zm-9 14l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z');
					} else {
						root.classList.remove('Mui-checked');
						path.setAttribute('d', 'M19 5v14H5V5h14m0-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z');
					}
				"
			/>
			<svg aria-hidden="true" class="MuiSvgIcon-root MuiSvgIcon-fontSizeSmall" focusable="false" viewbox="0 0 24 24">
				<path d="M19 5v14H5V5h14m0-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z">
				</path>
			</svg>
		</span>
		<span class="MuiTouchRipple-root">
		</span>
	</span>
	<span class="MuiTypography-root MuiFormControlLabel-label MuiTypography-body1">
		Checkbox
	</span>
</label>

<label class="MuiFormControlLabel-root Mui-disabled">
	<span aria-disabled="true" class="MuiButtonBase-root MuiIconButton-root MuiCheckbox-root MuiCheckbox-colorPrimary Mui-disabled MuiIconButton-colorPrimary Mui-disabled Mui-disabled" tabindex="-1">
		<span class="MuiIconButton-label">
			<input
				data-indeterminate="false"
				disabled=""
				type="checkbox"
				value=""
				style="position:absolute; opacity:0; width:100%; height:100%; top:0; left:0; margin:0;"
			/>
			<svg aria-hidden="true" class="MuiSvgIcon-root MuiSvgIcon-fontSizeSmall" focusable="false" viewbox="0 0 24 24">
				<path d="M19 5v14H5V5h14m0-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z">
				</path>
			</svg>
		</span>
	</span>
	<span class="MuiTypography-root MuiFormControlLabel-label Mui-disabled MuiTypography-body1">
		Checkbox
	</span>
</label>
HTML;

$radio_buttons = <<<'HTML'
<label class="MuiFormControlLabel-root">
	<span aria-disabled="false" class="MuiButtonBase-root MuiIconButton-root MuiRadio-root MuiRadio-colorPrimary MuiIconButton-colorPrimary">
		<span class="MuiIconButton-label">
			<input
				type="radio"
				value=""
				style="position:absolute; opacity:0; width:100%; height:100%; top:0; left:0; margin:0; cursor:inherit;"
				onclick="
					var root = this.closest('.MuiRadio-root');
					var dot = root.querySelector('.radio-dot');

					if (this.checked && !root.classList.contains('Mui-checked')) {
						root.classList.add('Mui-checked');
						dot.style.transform = 'scale(1)';
					} else {
						this.checked = false;
						root.classList.remove('Mui-checked');
						dot.style.transform = 'scale(0)';
					}
				"
			/>
			<div style="position:relative; display:flex;">
				<svg aria-hidden="true" class="MuiSvgIcon-root MuiSvgIcon-fontSizeSmall" focusable="false" viewbox="0 0 24 24">
					<path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8z">
					</path>
				</svg>
				<svg
					aria-hidden="true"
					class="MuiSvgIcon-root MuiSvgIcon-fontSizeSmall radio-dot"
					focusable="false"
					viewbox="0 0 24 24"
					style="position:absolute; left:0; transform:scale(0); transition:transform 150ms cubic-bezier(0.4, 0, 0.2, 1);"
				>
					<path d="M8.465 8.465C9.37 7.56 10.62 7 12 7C14.76 7 17 9.24 17 12C17 13.38 16.44 14.63 15.535 15.535C14.63 16.44 13.38 17 12 17C9.24 17 7 14.76 7 12C7 10.62 7.56 9.37 8.465 8.465Z">
					</path>
				</svg>
			</div>
		</span>
		<span class="MuiTouchRipple-root">
		</span>
	</span>
	<span class="MuiTypography-root MuiFormControlLabel-label MuiTypography-body1">
		Radio
	</span>
</label>

<label class="MuiFormControlLabel-root Mui-disabled">
	<span aria-disabled="true" class="MuiButtonBase-root MuiIconButton-root MuiRadio-root MuiRadio-colorPrimary Mui-disabled MuiIconButton-colorPrimary Mui-disabled Mui-disabled" tabindex="-1">
		<span class="MuiIconButton-label">
			<input
				disabled=""
				type="radio"
				value=""
				style="position:absolute; opacity:0; width:100%; height:100%; top:0; left:0; margin:0;"
			/>
			<div style="position:relative; display:flex;">
				<svg aria-hidden="true" class="MuiSvgIcon-root MuiSvgIcon-fontSizeSmall" focusable="false" viewbox="0 0 24 24">
					<path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8z">
					</path>
				</svg>
				<svg
					aria-hidden="true"
					class="MuiSvgIcon-root MuiSvgIcon-fontSizeSmall"
					focusable="false"
					viewbox="0 0 24 24"
					style="position:absolute; left:0; transform:scale(0);"
				>
					<path d="M8.465 8.465C9.37 7.56 10.62 7 12 7C14.76 7 17 9.24 17 12C17 13.38 16.44 14.63 15.535 15.535C14.63 16.44 13.38 17 12 17C9.24 17 7 14.76 7 12C7 10.62 7.56 9.37 8.465 8.465Z">
					</path>
				</svg>
			</div>
		</span>
	</span>
	<span class="MuiTypography-root MuiFormControlLabel-label Mui-disabled MuiTypography-body1">
		Radio
	</span>
</label>
HTML;

$switches = <<<'HTML'
<label class="MuiFormControlLabel-root">
	<span class="MuiSwitch-root MuiSwitch-sizeSmall">
		<span aria-disabled="false" class="MuiButtonBase-root MuiIconButton-root MuiSwitch-switchBase MuiSwitch-colorPrimary MuiIconButton-colorPrimary">
			<span class="MuiIconButton-label">
				<input
					class="MuiSwitch-input"
					type="checkbox"
					value=""
					style="position:absolute; opacity:0; width:100%; height:100%; top:0; left:0; margin:0; cursor:inherit;"
					onchange="
						var switchBase = this.closest('.MuiSwitch-switchBase');

						if (this.checked) {
							switchBase.classList.add('Mui-checked');
						} else {
							switchBase.classList.remove('Mui-checked');
						}
					"
				/>
				<span class="MuiSwitch-thumb">
				</span>
			</span>
			<span class="MuiTouchRipple-root">
			</span>
		</span>
		<span class="MuiSwitch-track">
		</span>
	</span>
	<span class="MuiTypography-root MuiFormControlLabel-label MuiTypography-body1">
		Switch
	</span>
</label>

<label class="MuiFormControlLabel-root Mui-disabled">
	<span class="MuiSwitch-root MuiSwitch-sizeSmall">
		<span aria-disabled="true" class="MuiButtonBase-root MuiIconButton-root MuiSwitch-switchBase MuiSwitch-colorPrimary Mui-disabled MuiIconButton-colorPrimary Mui-disabled Mui-disabled" tabindex="-1">
			<span class="MuiIconButton-label">
				<input
					class="MuiSwitch-input"
					disabled=""
					type="checkbox"
					value=""
					style="position:absolute; opacity:0; width:100%; height:100%; top:0; left:0; margin:0;"
				/>
				<span class="MuiSwitch-thumb">
				</span>
			</span>
		</span>
		<span class="MuiSwitch-track">
		</span>
	</span>
	<span class="MuiTypography-root MuiFormControlLabel-label Mui-disabled MuiTypography-body1">
		Switch
	</span>
</label>
HTML;

$icon_buttons = <<<'HTML'
			<button class="MuiButtonBase-root MuiIconButton-root MuiIconButton-colorPrimary MuiIconButton-sizeSmall" tabindex="0" type="button">
				<span class="MuiIconButton-label">
					<svg aria-hidden="true" class="MuiSvgIcon-root" focusable="false" viewbox="0 0 24 24">
						<path d="M19 12v7H5v-7H3v7c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2v-7h-2zm-6 .67l2.59-2.58L17 11.5l-5 5-5-5 1.41-1.41L11 12.67V3h2z">
						</path>
					</svg>
				</span>
				<span class="MuiTouchRipple-root">
				</span>
			</button>
			<button class="MuiButtonBase-root MuiIconButton-root MuiIconButton-colorPrimary MuiIconButton-sizeMedium"" tabindex="0" type="button">
				<span class="MuiIconButton-label">
					<svg aria-hidden="true" class="MuiSvgIcon-root MuiSvgIcon-fontSizeMedium" focusable="false" viewbox="0 0 24 24">
						<path d="M19 12v7H5v-7H3v7c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2v-7h-2zm-6 .67l2.59-2.58L17 11.5l-5 5-5-5 1.41-1.41L11 12.67V3h2z">
						</path>
					</svg>
				</span>
				<span class="MuiTouchRipple-root">
				</span>
			</button>
			<button class="MuiButtonBase-root MuiIconButton-root MuiIconButton-colorPrimary MuiIconButton-sizeLarge" tabindex="0" type="button">
				<span class="MuiIconButton-label">
					<svg aria-hidden="true" class="MuiSvgIcon-root MuiSvgIcon-fontSizeLarge" focusable="false" viewbox="0 0 24 24">
						<path d="M19 12v7H5v-7H3v7c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2v-7h-2zm-6 .67l2.59-2.58L17 11.5l-5 5-5-5 1.41-1.41L11 12.67V3h2z">
						</path>
					</svg>
				</span>
				<span class="MuiTouchRipple-root">
				</span>
			</button>
			<button class="MuiButtonBase-root MuiIconButton-root MuiIconButton-colorPrimary Mui-disabled Mui-disabled" disabled="" tabindex="-1" type="button">
				<span class="MuiIconButton-label">
					<svg aria-hidden="true" class="MuiSvgIcon-root" focusable="false" viewbox="0 0 24 24">
						<path d="M19 12v7H5v-7H3v7c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2v-7h-2zm-6 .67l2.59-2.58L17 11.5l-5 5-5-5 1.41-1.41L11 12.67V3h2z">
						</path>
					</svg>
				</span>
			</button>
HTML;

$toggle_buttons = <<<'HTML'
<div class="MuiToggleButtonGroup-root" role="group">
	<button
		aria-pressed="true"
		class="MuiButtonBase-root MuiToggleButton-root MuiToggleButtonGroup-grouped MuiToggleButtonGroup-groupedHorizontal Mui-selected MuiToggleButton-sizeSmall"
		tabindex="0"
		type="button"
		value="one"
		onclick="
			var group = this.closest('.MuiToggleButtonGroup-root');
			group.querySelectorAll('.MuiToggleButton-root').forEach(function(button) {
				button.classList.remove('Mui-selected');
				button.setAttribute('aria-pressed', 'false');
			});
			this.classList.add('Mui-selected');
			this.setAttribute('aria-pressed', 'true');
		"
	>
		<span class="MuiToggleButton-label">
			One
		</span>
		<span class="MuiTouchRipple-root">
		</span>
	</button>

	<button
		aria-pressed="false"
		class="MuiButtonBase-root MuiToggleButton-root MuiToggleButtonGroup-grouped MuiToggleButtonGroup-groupedHorizontal MuiToggleButton-sizeSmall"
		tabindex="0"
		type="button"
		value="two"
		onclick="
			var group = this.closest('.MuiToggleButtonGroup-root');
			group.querySelectorAll('.MuiToggleButton-root').forEach(function(button) {
				button.classList.remove('Mui-selected');
				button.setAttribute('aria-pressed', 'false');
			});
			this.classList.add('Mui-selected');
			this.setAttribute('aria-pressed', 'true');
		"
	>
		<span class="MuiToggleButton-label">
			Two
		</span>
		<span class="MuiTouchRipple-root">
		</span>
	</button>
</div>

<div class="MuiToggleButtonGroup-root" role="group">
	<button
		aria-pressed="true"
		class="MuiButtonBase-root MuiToggleButton-root MuiToggleButtonGroup-grouped MuiToggleButtonGroup-groupedHorizontal Mui-selected"
		tabindex="0"
		type="button"
		value="one"
		onclick="
			var group = this.closest('.MuiToggleButtonGroup-root');
			group.querySelectorAll('.MuiToggleButton-root').forEach(function(button) {
				button.classList.remove('Mui-selected');
				button.setAttribute('aria-pressed', 'false');
			});
			this.classList.add('Mui-selected');
			this.setAttribute('aria-pressed', 'true');
		"
	>
		<span class="MuiToggleButton-label">
			One
		</span>
		<span class="MuiTouchRipple-root">
		</span>
	</button>

	<button
		aria-pressed="false"
		class="MuiButtonBase-root MuiToggleButton-root MuiToggleButtonGroup-grouped MuiToggleButtonGroup-groupedHorizontal"
		tabindex="0"
		type="button"
		value="two"
		onclick="
			var group = this.closest('.MuiToggleButtonGroup-root');
			group.querySelectorAll('.MuiToggleButton-root').forEach(function(button) {
				button.classList.remove('Mui-selected');
				button.setAttribute('aria-pressed', 'false');
			});
			this.classList.add('Mui-selected');
			this.setAttribute('aria-pressed', 'true');
		"
	>
		<span class="MuiToggleButton-label">
			Two
		</span>
		<span class="MuiTouchRipple-root">
		</span>
	</button>
</div>

<div class="MuiToggleButtonGroup-root" role="group">
	<button
		aria-pressed="true"
		class="MuiButtonBase-root MuiToggleButton-root MuiToggleButtonGroup-grouped MuiToggleButtonGroup-groupedHorizontal Mui-selected MuiToggleButton-sizeLarge"
		tabindex="0"
		type="button"
		value="one"
		onclick="
			var group = this.closest('.MuiToggleButtonGroup-root');
			group.querySelectorAll('.MuiToggleButton-root').forEach(function(button) {
				button.classList.remove('Mui-selected');
				button.setAttribute('aria-pressed', 'false');
			});
			this.classList.add('Mui-selected');
			this.setAttribute('aria-pressed', 'true');
		"
	>
		<span class="MuiToggleButton-label">
			One
		</span>
		<span class="MuiTouchRipple-root">
		</span>
	</button>

	<button
		aria-pressed="false"
		class="MuiButtonBase-root MuiToggleButton-root MuiToggleButtonGroup-grouped MuiToggleButtonGroup-groupedHorizontal MuiToggleButton-sizeLarge"
		tabindex="0"
		type="button"
		value="two"
		onclick="
			var group = this.closest('.MuiToggleButtonGroup-root');
			group.querySelectorAll('.MuiToggleButton-root').forEach(function(button) {
				button.classList.remove('Mui-selected');
				button.setAttribute('aria-pressed', 'false');
			});
			this.classList.add('Mui-selected');
			this.setAttribute('aria-pressed', 'true');
		"
	>
		<span class="MuiToggleButton-label">
			Two
		</span>
		<span class="MuiTouchRipple-root">
		</span>
	</button>
</div>
HTML;

$tooltip = <<<'HTML'
			<button aria-label="tooltip" class="MuiButtonBase-root MuiIconButton-root MuiIconButton-colorPrimary" tabindex="0" title="A basic tooltip" type="button">
				<span class="MuiIconButton-label">
					<svg aria-hidden="true" class="MuiSvgIcon-root" focusable="false" viewbox="0 0 24 24">
						<path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z">
						</path>
					</svg>
				</span>
				<span class="MuiTouchRipple-root">
				</span>
			</button>
HTML;

$link = <<<'HTML'
			<a class="MuiTypography-root MuiLink-root MuiLink-underlineAlways MuiTypography-colorPrimary" href="#">
				Link
			</a>
HTML;

$divider = <<<'HTML'
			<hr class="MuiDivider-root"/>
HTML;

$typography = <<<'HTML'
			<h1 class="MuiTypography-root MuiTypography-h1">
				h1 Heading
			</h1>
			<h2 class="MuiTypography-root MuiTypography-h2">
				h2 Heading
			</h2>
			<h3 class="MuiTypography-root MuiTypography-h3">
				h3 Heading
			</h3>
			<h4 class="MuiTypography-root MuiTypography-h4">
				h4 Heading
			</h4>
			<h5 class="MuiTypography-root MuiTypography-h5">
				h5 Heading
			</h5>
			<h6 class="MuiTypography-root MuiTypography-h6">
				h6 Heading
			</h6>
			<h6 class="MuiTypography-root MuiTypography-subtitle1">
				Subtitle1
			</h6>
			<h6 class="MuiTypography-root MuiTypography-subtitle2">
				Subtitle2
			</h6>
			<p class="MuiTypography-root MuiTypography-body1">
				Body1
			</p>
			<p class="MuiTypography-root MuiTypography-body2">
				Body2
			</p>
			<span class="MuiTypography-root MuiTypography-button">
				Button Text
			</span>
			<span class="MuiTypography-root MuiTypography-caption">
				Caption
			</span>
			<span class="MuiTypography-root MuiTypography-overline">
				Overline
			</span>
HTML;

$tabs = <<<'HTML'
<div class="MuiTabs-root">
	<div class="MuiTabs-scroller MuiTabs-fixed" style="overflow: hidden;">
		<div class="MuiTabs-flexContainer" role="tablist">
			<button
				aria-selected="true"
				class="MuiButtonBase-root MuiTab-root MuiTab-textColorPrimary Mui-selected"
				role="tab"
				tabindex="0"
				type="button"
				onclick="
					var tabs = this.closest('.MuiTabs-root');
					var buttons = tabs.querySelectorAll('.MuiTab-root:not(.Mui-disabled)');
					var indicator = tabs.querySelector('.MuiTabs-indicator');

					buttons.forEach(function(button) {
						button.classList.remove('Mui-selected');
						button.setAttribute('aria-selected', 'false');
						button.setAttribute('tabindex', '-1');
					});

					this.classList.add('Mui-selected');
					this.setAttribute('aria-selected', 'true');
					this.setAttribute('tabindex', '0');

					indicator.style.left = this.offsetLeft + 'px';
					indicator.style.width = this.offsetWidth + 'px';
				"
			>
				<span class="MuiTab-wrapper">
					Tab One
				</span>
				<span class="MuiTouchRipple-root">
				</span>
			</button>

			<button
				aria-selected="false"
				class="MuiButtonBase-root MuiTab-root MuiTab-textColorPrimary"
				role="tab"
				tabindex="-1"
				type="button"
				onclick="
					var tabs = this.closest('.MuiTabs-root');
					var buttons = tabs.querySelectorAll('.MuiTab-root:not(.Mui-disabled)');
					var indicator = tabs.querySelector('.MuiTabs-indicator');

					buttons.forEach(function(button) {
						button.classList.remove('Mui-selected');
						button.setAttribute('aria-selected', 'false');
						button.setAttribute('tabindex', '-1');
					});

					this.classList.add('Mui-selected');
					this.setAttribute('aria-selected', 'true');
					this.setAttribute('tabindex', '0');

					indicator.style.left = this.offsetLeft + 'px';
					indicator.style.width = this.offsetWidth + 'px';
				"
			>
				<span class="MuiTab-wrapper">
					Tab Two
				</span>
				<span class="MuiTouchRipple-root">
				</span>
			</button>

			<button
				aria-selected="false"
				class="MuiButtonBase-root MuiTab-root MuiTab-textColorPrimary Mui-disabled Mui-disabled"
				disabled=""
				role="tab"
				tabindex="-1"
				type="button"
			>
				<span class="MuiTab-wrapper">
					Disabled
				</span>
			</button>
		</div>

		<span class="MuiTabs-indicator" style="left: 0px; width: 160px;">
		</span>
	</div>
</div>
HTML;

$lists = <<<'HTML'
			<ul class="MuiList-root MuiList-padding">
				<li class="MuiListItem-root MuiListItem-gutters">
					<div class="MuiListItemText-root MuiListItemText-multiline">
						<span class="MuiTypography-root MuiListItemText-primary MuiTypography-body1 MuiTypography-displayBlock">
							List Item
						</span>
						<p class="MuiTypography-root MuiListItemText-secondary MuiTypography-body2 MuiTypography-colorTextSecondary MuiTypography-displayBlock">
							Secondary text
						</p>
					</div>
				</li>
				<div aria-disabled="false" class="MuiButtonBase-root MuiListItem-root MuiListItem-gutters MuiListItem-button" role="button" tabindex="0">
					<div class="MuiListItemIcon-root">
						<svg aria-hidden="true" class="MuiSvgIcon-root" focusable="false" viewbox="0 0 24 24">
							<path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z">
							</path>
						</svg>
					</div>
					<div class="MuiListItemText-root">
						<span class="MuiTypography-root MuiListItemText-primary MuiTypography-body1 MuiTypography-displayBlock">
							Clickable List Item
						</span>
					</div>
					<span class="MuiTouchRipple-root">
					</span>
				</div>
				<li class="MuiListItem-root MuiListItem-gutters Mui-disabled" disabled="">
					<div class="MuiListItemText-root">
						<span class="MuiTypography-root MuiListItemText-primary MuiTypography-body1 MuiTypography-displayBlock">
							Disabled List Item
						</span>
					</div>
				</li>
			</ul>
HTML;

$text_fields = <<<'HTML'
<div class="MuiFormControl-root MuiTextField-root">
	<label class="MuiFormLabel-root MuiInputLabel-root MuiInputLabel-formControl MuiInputLabel-animated MuiInputLabel-outlined" data-shrink="false">
		Text Field
	</label>

	<input
		aria-invalid="false"
		class="MuiInputBase-input MuiOutlinedInput-input"
		type="text"
		value=""
		style="border: 1px solid rgba(0, 0, 0, 0.23); outline: none;"
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
	/>
</div>
HTML;

$select = <<<'HTML'
<div class="MuiFormControl-root">
	<label class="MuiFormLabel-root MuiInputLabel-root MuiInputLabel-formControl MuiInputLabel-animated MuiInputLabel-shrink MuiInputLabel-outlined MuiFormLabel-filled" data-shrink="true" style="background:#fff;">
		Sample Type
	</label>

	<select
		class="MuiInputBase-input MuiOutlinedInput-input"
		style="border:1px solid rgba(0, 0, 0, 0.23); outline:none; background:#fff;"
		onfocus="this.style.borderColor='#0073CF'; this.style.borderWidth='2px';"
		onblur="this.style.borderColor='rgba(0, 0, 0, 0.23)'; this.style.borderWidth='1px';"
	>
		<option value="">Select a sample type</option>
		<option value="soil">Soil</option>
		<option value="plant">Plant Tissue</option>
		<option value="aquatic">Aquatic</option>
	</select>

	<p class="MuiFormHelperText-root MuiFormHelperText-contained">
		Select a sample type
	</p>
</div>
HTML;

$chips = <<<'HTML'
			<div class="MuiChip-root">
				<span class="MuiChip-label">
					Default Chip
				</span>
			</div>
			<div class="MuiChip-root MuiChip-colorPrimary">
				<span class="MuiChip-label">
					Primary Chip
				</span>
			</div>
			<div class="MuiChip-root MuiChip-colorPrimary MuiChip-outlined MuiChip-outlinedPrimary">
				<span class="MuiChip-label">
					Outlined Chip
				</span>
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
	</body>
</html>