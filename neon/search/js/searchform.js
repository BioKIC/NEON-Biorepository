/**
 * Search criteria, collection trees, chips, and URL serialization.
 * Keep updateChip() global: the date picker and React site map call it.
 */
const form = document.getElementById('params-form');
const allNeon = document.getElementById('all-neon-colls-quick');
const formColls = document.getElementById('search-form-colls');
const collsModal = document.getElementById('colls-modal');
const chips = document.getElementById('chips');
const advancedForm = document.getElementById('search-form-advanced-search');
const advancedRowCount = 8;
const collectionViews = ['taxonomic-cat', 'neon-theme', 'sample-type'];
const renderedChips = new Map();
const modalCloseTimers = new WeakMap();
const modalReturnFocus = new WeakMap();
let modalBodyOverflow = null;

const paramNames = [
  'db', 'datasetid', 'catnum', 'collector', 'includeothercatnum',
  'hasimages', 'hasgenetic', 'state', 'county', 'local', 'elevlow',
  'elevhigh', 'llbound', 'footprintwkt', 'llpoint', 'eventdate1',
  'eventdate2', 'taxa', 'taxontype', 'availableforloan', 'materialsampletype[]',
];
for (let row = 1; row <= advancedRowCount; row++) {
  if (row > 1) paramNames.push(`q_customandor${row}`);
  ['openparen', 'field', 'type', 'value', 'closeparen'].forEach(part => {
    paramNames.push(`q_custom${part}${row}`);
  });
}

function getCriterionSelected() {
  return collsModal?.querySelector('input[name="collChoice"]:checked')?.value || collectionViews[0];
}

function getCollectionView() {
  return document.getElementById(getCriterionSelected());
}

function updateCollectionRowStripes(root) {
  if (!root) return;
  let index = 0;
  root.querySelectorAll('li').forEach(row => {
    let ancestor = row;
    let visible = true;
    while (ancestor && ancestor !== root) {
      if (ancestor.hidden || ancestor.classList.contains('collapsed') ||
          (ancestor.tagName === 'LI' && ancestor.style.display === 'none')) {
        visible = false;
        break;
      }
      ancestor = ancestor.parentElement;
    }
    row.classList.toggle('collection-row-alt', visible && index++ % 2 === 1);
  });
}

function refreshCollectionRowStripes() {
  collectionViews.forEach(id => updateCollectionRowStripes(document.getElementById(id)));
  updateCollectionRowStripes(document.getElementById('neonext-collections-items'));
}

function getCollectionInputs(root) {
  return Array.from(root?.querySelectorAll('input[name="db"]:enabled') || []);
}

function getCollsSelected() {
  return [...getCollectionInputs(getCollectionView()), ...getCollectionInputs(formColls)]
    .filter(input => input.checked);
}

/** Tree state is derived from enabled leaves, never from another tab. */
function getGroupRoot(checkbox) {
  return (checkbox.dataset.formId && document.getElementById(checkbox.dataset.formId)) ||
    checkbox.closest('li');
}

function setGroupState(checkbox) {
  const root = getGroupRoot(checkbox);
  const children = Array.from(root?.querySelectorAll('input.child:enabled:not(.all-selector)') || []);
  const checked = children.filter(child => child.checked).length;
  checkbox.checked = children.length > 0 && checked === children.length;
  checkbox.indeterminate = checked > 0 && checked < children.length;
}

function updateGlobalMaster() {
  const children = getCollectionInputs(getCollectionView());
  const checked = children.filter(child => child.checked).length;
  allNeon.checked = children.length > 0 && checked === children.length;
  allNeon.indeterminate = checked > 0 && checked < children.length;
}

function refreshCollectionSelectors() {
  form.querySelectorAll('.all-selector').forEach(setGroupState);
  updateGlobalMaster();
}

function updateAncestors(checkbox) {
  let li = checkbox.closest('li');
  while (li) {
    const parent = li.querySelector(':scope > input.all-selector, :scope > .accordion-subheader input.all-selector');
    if (parent) setGroupState(parent);
    li = li.parentElement?.closest('li');
  }
}

function setGroupChecked(root, checked) {
  root?.querySelectorAll('input[type="checkbox"]:enabled:not(.accordion-selector)').forEach(input => {
    input.checked = checked;
    input.indeterminate = false;
  });
}

function toggleAllSelector() {
  setGroupChecked(getGroupRoot(this), this.checked);
}

function uncheckAll(element) {
  if (!element) return;
  const root = element.matches('input') ? getGroupRoot(element) : element;
  setGroupChecked(root, false);
  if (element.matches('input[type="checkbox"]')) {
    element.checked = false;
    element.indeterminate = false;
  }
  refreshCollectionSelectors();
}

/** Modals */
function getSearchMotionDuration(duration = 200) {
  return window.matchMedia?.('(prefers-reduced-motion: reduce)').matches ? 0 : duration;
}

function openModal(selector) {
  const modal = document.querySelector(selector);
  if (!modal) return;
  clearTimeout(modalCloseTimers.get(modal));
  modalCloseTimers.delete(modal);
  if (modalBodyOverflow === null) modalBodyOverflow = document.body.style.overflow;
  document.body.style.overflow = 'hidden';
  const wasHidden = window.getComputedStyle(modal).display === 'none';
  if (wasHidden && modal.matches('[role="dialog"]')) modalReturnFocus.set(modal, document.activeElement);
  if (wasHidden) modal.style.opacity = '0';
  modal.style.display = 'block';
  if (modal.id === 'domains-sites-modal') document.body.classList.add('neon-search-map-open');
  if (wasHidden) void modal.offsetHeight;
  modal.style.opacity = '1';
  if (wasHidden && modal.matches('[role="dialog"]')) modal.querySelector('button')?.focus({ preventScroll: true });
}

function closeModal(selector) {
  const modal = document.querySelector(selector);
  if (!modal) return;
  clearTimeout(modalCloseTimers.get(modal));
  modal.style.opacity = '0';
  const finish = () => {
    modalCloseTimers.delete(modal);
    const restoreFocus = modal.contains(document.activeElement);
    modal.style.display = 'none';
    if (modal.id === 'domains-sites-modal') document.body.classList.remove('neon-search-map-open');
    const anyOpen = Array.from(form.querySelectorAll('.modal'))
      .some(item => item.style.display === 'block');
    if (!anyOpen && modalBodyOverflow !== null) {
      document.body.style.overflow = modalBodyOverflow;
      modalBodyOverflow = null;
    }
    const opener = modalReturnFocus.get(modal);
    if (!anyOpen && restoreFocus && opener?.isConnected) opener.focus({ preventScroll: true });
    modalReturnFocus.delete(modal);
  };
  const duration = getSearchMotionDuration();
  if (duration) modalCloseTimers.set(modal, setTimeout(finish, duration));
  else finish();
}

document.addEventListener('keydown', event => {
  if (event.defaultPrevented || !['Escape', 'Tab'].includes(event.key)) return;
  const modal = Array.from(form.querySelectorAll('.modal[role="dialog"]'))
    .find(item => item.style.display === 'block' && item.style.opacity === '1');
  if (!modal) return;
  if (event.target.closest('.MuiPopover-root, .MuiPopper-root')) return;
  if (event.key === 'Escape') {
    event.preventDefault();
    modal.querySelector('.modal-close')?.click();
    return;
  }
  const controls = Array.from(modal.querySelectorAll('a[href], button, input, select, textarea, [tabindex]'))
    .filter(element => !element.disabled && element.tabIndex >= 0 && element.getClientRects().length);
  const first = controls[0];
  const last = controls[controls.length - 1];
  if (!first) {
    event.preventDefault();
  } else if (!modal.contains(document.activeElement) || (event.shiftKey && document.activeElement === first)) {
    event.preventDefault();
    (event.shiftKey ? last : first).focus();
  } else if (!event.shiftKey && document.activeElement === last) {
    event.preventDefault();
    first.focus();
  }
});

function openCoordAid(mapMode) {
  const mapWindow = window.open(
    '../../collections/tools/mapcoordaid.php?mapmode=' + encodeURIComponent(mapMode),
    'polygon', 'resizable=0,width=900,height=630,left=20,top=20'
  );
  if (mapWindow) mapWindow.focus();
}

/** Advanced search */
function resetCustomElements(index) {
  const row = document.getElementById(`customdiv${index}`);
  row?.querySelectorAll('input, select').forEach(input => {
    if (input.tagName === 'SELECT') input.selectedIndex = 0;
    else input.value = '';
  });
}

function toggleCustomDiv(index) {
  const row = document.getElementById(`customdiv${index}`);
  if (!row) return;
  const opening = row.style.display === 'none';
  // Clear every following row, so hidden conditions cannot remain in the URL.
  for (let current = index; current <= advancedRowCount; current++) {
    resetCustomElements(current);
    const next = document.getElementById(`customdiv${current}`);
    if (next) next.style.display = 'none';
  }
  row.style.display = opening ? 'block' : 'none';
  updateChip();
}

function getAdvancedRows() {
  const rows = [];
  if (!advancedForm) return rows;
  for (let index = 1; index <= advancedRowCount; index++) {
    const row = { index };
    ['andor', 'openparen', 'field', 'type', 'value', 'closeparen'].forEach(part => {
      const input = form.elements.namedItem(`q_custom${part}${index}`);
      row[part] = input?.value.trim() || '';
      if (part === 'field' || part === 'type') {
        row[`${part}Label`] = input?.selectedOptions[0]?.textContent || '';
      }
    });
    if (['andor', 'openparen', 'field', 'type', 'value', 'closeparen'].some(part => row[part])) {
      rows.push(row);
    }
  }
  return rows;
}

function syncAdvancedState(rows = getAdvancedRows()) {
  const flag = document.getElementById('AdvancedHasBeenChanged');
  if (flag) flag.checked = rows.length > 0;
}

function resetAdvancedSearch() {
  advancedForm?.querySelectorAll('input, select').forEach(input => {
    if (input.type === 'checkbox') input.checked = false;
    else if (input.tagName === 'SELECT') input.selectedIndex = 0;
    else input.value = '';
  });
  for (let index = 2; index <= advancedRowCount; index++) {
    const row = document.getElementById(`customdiv${index}`);
    if (row) row.style.display = 'none';
  }
}

function getAdvancedSearchChip(rows = getAdvancedRows()) {
  const text = rows.map(row => {
    const parts = [];
    if (row.andor) parts.push(row.andor);
    if (row.openparen || row.field) parts.push(row.openparen + (row.field ? row.fieldLabel : ''));
    if (row.type) parts.push(row.typeLabel);
    if (row.value && row.type !== 'IS_NULL' && row.type !== 'NOT_NULL') parts.push(`'${row.value}'`);
    return parts.join(' ') + row.closeparen;
  }).join(' ');
  return { id: 'chip-advancedsearchstr', text: `Advanced Search: ${text}`, anchor: advancedForm, clear: resetAdvancedSearch };
}

/** Chips */
function clearSites() {
  // The map owns its hidden checkboxes. Notify React as well as clearing legacy inputs.
  form.querySelectorAll('input[name="datasetid"]').forEach(input => {
    input.checked = false;
    input.indeterminate = false;
  });
  window.dispatchEvent(new Event('neon:clear-sites'));
}

function getCollsChips(listId, label) {
  const root = document.getElementById(listId);
  const labels = [];
  root?.querySelectorAll('input[type="checkbox"]:checked:enabled[data-ccode]').forEach(input => {
    // Use the local tree rather than IDs, which may repeat across collection tabs.
    let parent = input.closest('li')?.parentElement?.closest('li');
    while (parent && root.contains(parent)) {
      const group = parent.querySelector(':scope > input.all-selector');
      if (group?.checked && !group.indeterminate && group.dataset.ccode) return;
      parent = parent.parentElement?.closest('li');
    }
    if (input.dataset.ccode) labels.push(input.dataset.ccode);
  });
  return {
    id: `chip-some-${listId}-collids`,
    text: `${label}: ${[...new Set(labels)].join(', ')}`,
    anchor: collsModal.contains(root) ? allNeon : root,
    clear: () => uncheckAll(root),
  };
}

function getInputChip(input) {
  let text = input.dataset.chip;
  if (input.tagName === 'SELECT') {
    const selected = Array.from(input.selectedOptions).map(option => `'${option.value}'`);
    text += `: ${selected.join(', ')}`;
  } else if (input.type !== 'checkbox') {
    text += `: ${input.value}`;
  }
  return {
    id: `chip-${input.id || input.name}`,
    text,
    anchor: input,
    clear: () => {
      if (input.type === 'checkbox') {
        input.checked = false;
        input.indeterminate = false;
        if (input.dataset.formId) uncheckAll(input);
      } else if (input.tagName === 'SELECT') {
        Array.from(input.options).forEach(option => { option.selected = false; });
        // Update Select2 without invoking our form change handler a second time.
        $(input).trigger('change.select2');
      } else {
        input.value = '';
      }
    },
  };
}

const firstCollectionDate = '2008-10-02';
const firstCollectionMonth = 2008 * 12 + 9;
const dateFields = ['eventdate1', 'eventdate2'].map(id => document.getElementById(id));
const dateHandles = ['date-range-start', 'date-range-end'].map(id => document.getElementById(id));
const dateMonthFormat = new Intl.DateTimeFormat('en', { month: 'short', year: 'numeric' });

function dateString(date) {
  return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
}

function dateMonthIndex(value) {
  return Number(value.slice(0, 4)) * 12 + Number(value.slice(5, 7)) - 1;
}

function readCollectionDate(value, end = false, today = dateString(new Date())) {
  const match = /^(\d{4})(?:-(\d{2})(?:-(\d{2}))?)?$/.exec(value.trim());
  if (!match) return null;
  const year = Number(match[1]);
  const month = Number(match[2] || 1);
  const day = Number(match[3] || 1);
  const first = new Date(year, month - 1, day);
  if (first.getFullYear() !== year || first.getMonth() !== month - 1 || first.getDate() !== day) return null;
  const low = dateString(first);
  const high = match[3] ? low : dateString(new Date(year, match[2] ? month : 12, 0));
  if (low > today || high < firstCollectionDate) return null;
  return end ? (high > today ? today : high) : (low < firstCollectionDate ? firstCollectionDate : low);
}

function syncDateRange() {
  const slider = document.getElementById('date-range-slider');
  if (!slider) return;
  const today = dateString(new Date());
  const maximum = dateMonthIndex(today) - firstCollectionMonth;
  const dates = dateFields.map((input, index) => {
    const date = readCollectionDate(input.value, index === 1, today);
    input.setCustomValidity(input.value.trim() && !date
      ? `Enter a valid date from ${firstCollectionDate} through ${today} (YYYY-MM-DD, YYYY-MM, or YYYY).` : '');
    return date;
  });
  if (dates[0] && dates[1] && dates[0] > dates[1]) {
    dateFields[1].setCustomValidity('End Date must be on or after Start Date.');
  }
  const values = dates.map((date, index) => date ? dateMonthIndex(date) - firstCollectionMonth : index ? maximum : 0);
  if (values[0] <= values[1]) {
    dateHandles.forEach((handle, index) => {
      handle.max = maximum;
      handle.value = values[index];
      const month = firstCollectionMonth + values[index];
      const label = dateMonthFormat.format(new Date(Math.floor(month / 12), month % 12, 1));
      handle.setAttribute('aria-valuetext', label);
      handle.setAttribute('aria-valuemin', index ? values[0] : 0);
      handle.setAttribute('aria-valuemax', index ? maximum : values[1]);
      document.getElementById(`${handle.id}-label`).textContent = label;
    });
    slider.style.setProperty('--date-start', `${maximum ? values[0] / maximum * 100 : 0}%`);
    slider.style.setProperty('--date-end', `${maximum ? values[1] / maximum * 100 : 100}%`);
  }
  document.getElementById('date-range-reset').disabled = !dateFields.some(input => input.value.trim());
  if (slider.dataset.maximum !== String(maximum)) {
    slider.dataset.maximum = maximum;
    const marks = document.getElementById('date-range-marks');
    marks.replaceChildren();
    const lastYear = Number(today.slice(0, 4));
    const labelStep = Math.max(1, Math.ceil((lastYear - 2008) / 3));
    for (let year = 2008; year <= lastYear; year++) {
      const value = year === 2008 ? 0 : year === lastYear ? maximum : year * 12 - firstCollectionMonth;
      const mark = document.createElement('span');
      mark.className = 'search-date-mark';
      mark.style.left = `${maximum ? value / maximum * 100 : 0}%`;
      if (year === 2008 || year === lastYear || ((year - 2008) % labelStep === 0 && lastYear - year >= labelStep / 2)) {
        const label = document.createElement('span');
        label.textContent = year;
        mark.append(label);
      }
      marks.append(mark);
    }
  }
}

function updateDatesFromSlider(handle) {
  const index = dateHandles.indexOf(handle);
  if (index < 0) return;
  const other = 1 - index;
  const value = index ? Math.max(Number(handle.value), Number(dateHandles[other].value))
    : Math.min(Number(handle.value), Number(dateHandles[other].value));
  const month = firstCollectionMonth + value;
  const year = Math.floor(month / 12);
  const selectedDate = dateString(index ? new Date(year, month % 12 + 1, 0) : new Date(year, month % 12, 1));
  const today = dateString(new Date());
  dateFields[index].value = selectedDate < firstCollectionDate ? firstCollectionDate : selectedDate > today ? today : selectedDate;
  if (!readCollectionDate(dateFields[other].value, other === 1, today)) {
    dateFields[other].value = other ? today : firstCollectionDate;
  }
}

function updateChip() {
  if (!chips) return;
  syncDateRange();
  form.querySelectorAll('.MuiSwitch-input').forEach(input => {
    input.closest('.MuiSwitch-switchBase')?.classList.toggle('Mui-checked', input.checked);
  });
  const next = [];
  const selectedSites = Array.from(form.querySelectorAll('input[name="datasetid"]:checked'));
  if (selectedSites.length) {
    const labels = [...new Set(selectedSites.map(input => input.id || input.value))];
    next.push({ id: 'chip-some-datasetids', text: `Sites: ${labels.join(', ')}`, anchor: document.getElementById('locality'), clear: clearSites });
  }

  const selectedCollections = getCollectionInputs(getCollectionView()).filter(input => input.checked);
  if (!allNeon.checked && selectedCollections.length) {
    next.push(getCollsChips(getCriterionSelected(), 'Some Sample Types'));
  }
  [
    ['neonext-collections-items', 'Some Sample Types at Other Repos'],
    ['ext-collections-list', 'Some Ext NEON Colls'],
  ].forEach(([id, label]) => {
    const inputs = getCollectionInputs(document.getElementById(id));
    const checked = inputs.filter(input => input.checked).length;
    if (checked > 0 && checked < inputs.length) next.push(getCollsChips(id, label));
  });

  const rows = getAdvancedRows();
  syncAdvancedState(rows);
  if (rows.length) next.push(getAdvancedSearchChip(rows));

  form.querySelectorAll('input[data-chip], textarea[data-chip], select[data-chip]').forEach(input => {
    if (input.disabled || input.name === 'db' || input.name === 'datasetid') return;
    const active = input.type === 'checkbox' ? input.checked : input.value.trim() !== '';
    if (active) next.push(getInputChip(input));
  });

  next.sort((a, b) => {
    const position = a.anchor.compareDocumentPosition(b.anchor);
    if (position & Node.DOCUMENT_POSITION_FOLLOWING) return -1;
    if (position & Node.DOCUMENT_POSITION_PRECEDING) return 1;
    return 0;
  });

  // Reuse unchanged chips, preserving keyboard focus and avoiding repeated DOM rebuilds.
  const nextIds = new Set(next.map(chip => chip.id));
  renderedChips.forEach((chip, id) => {
    if (!nextIds.has(id)) {
      chip.node.remove();
      renderedChips.delete(id);
    }
  });
  document.getElementById('criteria-empty').hidden = next.length > 0;
  document.getElementById('reset-btn').disabled = next.length === 0;
  document.getElementById('search-btn').disabled = next.length === 0;
  let position = chips.firstChild;
  next.forEach(descriptor => {
    let chip = renderedChips.get(descriptor.id);
    if (!chip) {
      const node = document.createElement('span');
      node.id = descriptor.id;
      node.className = 'chip';
      const label = document.createElement('span');
      label.className = 'chip-label';
      const button = document.createElement('button');
      button.type = 'button';
      button.className = 'chip-remove-btn';
      button.innerHTML = '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Zm5 13.6L15.6 17 12 13.4 8.4 17 7 15.6l3.6-3.6L7 8.4 8.4 7l3.6 3.6L15.6 7 17 8.4 13.4 12Z"/></svg>';
      node.append(label, button);
      chip = { node, label, button };
      renderedChips.set(descriptor.id, chip);
    }
    chip.clear = descriptor.clear;
    if (chip.label.textContent !== descriptor.text) {
      chip.label.textContent = descriptor.text;
      chip.button.setAttribute('aria-label', `Remove ${descriptor.text}`);
      chip.button.title = `Remove ${descriptor.text}`;
    }
    if (chip.node !== position) chips.insertBefore(chip.node, position);
    position = chip.node.nextSibling;
  });
}

/** URL parameters are read from the form on demand, with no mutable parameter cache. */
function getParam(name) {
  if (name === 'db') {
    const values = allNeon.checked
      ? ['all', ...getCollectionInputs(formColls).filter(input => input.checked).map(input => input.value)]
      : getCollsSelected().map(input => input.value);
    return [...new Set(values)];
  }
  if (name === 'datasetid') {
    return [...new Set(Array.from(form.querySelectorAll('input[name="datasetid"]:checked'))
      .map(input => input.value))];
  }
  const input = form.elements.namedItem(name);
  if (!input || input.disabled) return '';
  if (input.type === 'checkbox') return input.checked ? input.value : '';
  if (input.tagName === 'SELECT') return Array.from(input.selectedOptions).map(option => option.value).filter(Boolean);
  return input.value || '';
}

function getSearchUrl() {
  const url = new URL('../../collections/list.php', window.location.href);
  paramNames.forEach(name => {
    const value = getParam(name);
    if (Array.isArray(value)) {
      if (name === 'materialsampletype[]') value.forEach(item => url.searchParams.append(name, item));
      else if (value.length) url.searchParams.set(name, value.join(','));
    } else if (value !== '') {
      url.searchParams.set(name, value);
    }
  });
  const excludeSynonyms = document.getElementById('usethes');
  if (excludeSynonyms) url.searchParams.set('usethes', excludeSynonyms.checked ? '0' : '1');
  return url.href;
}

function expandCriterionAccordions(input) {
  let content = input?.closest('.content');
  while (content && form.contains(content)) {
    const selector = content.previousElementSibling?.previousElementSibling;
    if (selector?.matches('input.accordion-selector')) selector.checked = true;
    content = content.parentElement.closest('.content');
  }
}

function expandCollectionPath(input, root) {
  let list = input.closest('ul');
  while (list && root.contains(list)) {
    list.classList.remove('collapsed');
    list.style.removeProperty('display');
    const parent = list.parentElement;
    if (parent?.tagName === 'LI') {
      const icon = parent.querySelector(':scope > .expansion-icon, :scope > [data-target] .expansion-icon');
      if (icon) icon.textContent = 'indeterminate_check_box';
    }
    list = parent?.closest('ul');
  }
}

function prefillFromUrl() {
  const params = new URLSearchParams(window.location.search);
  if (params.has('db')) {
    const selected = new Set(params.getAll('db').flatMap(value => value.split(',')));
    collectionViews.forEach(id => {
      const root = document.getElementById(id);
      getCollectionInputs(root).forEach(input => {
        input.checked = selected.has('all') || selected.has(input.value);
        if (selected.has(input.value)) expandCollectionPath(input, root);
      });
    });
    getCollectionInputs(formColls).forEach(input => {
      input.checked = selected.has(input.value);
      if (input.checked) expandCriterionAccordions(input);
    });
    if (getCollsSelected().length) expandCriterionAccordions(formColls);
    refreshCollectionRowStripes();
  }

  // The React map initializes its own site selections from datasetid.
  // Only known search controls may be populated; never interpolate URL keys into selectors.
  paramNames.filter(name => name !== 'db' && name !== 'datasetid').forEach(name => {
    if (!params.has(name)) return;
    const input = form.elements.namedItem(name);
    if (!input) return;
    const values = params.getAll(name);
    if (input.type === 'checkbox') {
      input.checked = values.some(value => value === '1' || value === 'true' || value === input.value);
    } else if (input.tagName === 'SELECT' && input.multiple) {
      Array.from(input.options).forEach(option => { option.selected = values.includes(option.value); });
      $(input).trigger('change.select2');
    } else {
      input.value = values[values.length - 1];
    }
    const hasValue = input.type === 'checkbox' ? input.checked : input.value.trim() !== '';
    if (hasValue) expandCriterionAccordions(input);
  });
  const excludeSynonyms = document.getElementById('usethes');
  if (excludeSynonyms && params.has('usethes')) {
    excludeSynonyms.checked = params.get('usethes') !== '1';
    if (excludeSynonyms.checked) expandCriterionAccordions(excludeSynonyms);
  }

  const rows = getAdvancedRows();
  const lastRow = rows.length ? rows[rows.length - 1].index : 1;
  for (let index = 1; index <= lastRow; index++) {
    const row = document.getElementById(`customdiv${index}`);
    if (row) row.style.display = 'block';
  }
  refreshCollectionSelectors();
  updateChip();
}

/** Validation */
function validateForm() {
  const errors = [];
  if (!getCollsSelected().length) {
    errors.push({ element: formColls, errorMsg: 'Please select at least one sample type.' });
  }
  form.querySelectorAll('input:invalid, textarea:invalid, select:invalid').forEach(input => {
    errors.push({ element: input, errorMsg: input.validity.customError ? input.validationMessage : `Please check values in field ${input.dataset.chip || input.name}.` });
  });

  const messages = new Set();
  let balance = 0;
  getAdvancedRows().forEach((row, index) => {
    if (row.index !== index + 1) messages.add('Complete advanced search rows in order, without empty rows between conditions.');
    if (!row.field) messages.add('Missing field name.');
    if (!row.type) messages.add('Missing condition statement.');
    if (row.type && row.type !== 'IS_NULL' && row.type !== 'NOT_NULL' && !row.value) {
      messages.add('Missing input value.');
    }
    if (row.index > 1 && row.andor !== 'AND' && row.andor !== 'OR') {
      messages.add('Choose AND/OR between advanced search conditions.');
    }
    balance += row.openparen.length - row.closeparen.length;
    if (balance < 0) messages.add('Mismatched parentheses.');
  });
  if (balance !== 0) messages.add('Mismatched parentheses.');
  messages.forEach(errorMsg => errors.push({ element: advancedForm, errorMsg }));
  return errors;
}

function clearValidationErrors(animate = false) {
  const messages = $('#error-msgs').children().stop(true);
  if (animate) {
    messages.animate({ height: 'hide', opacity: 'hide', marginBottom: 'hide', paddingTop: 'hide', paddingBottom: 'hide' },
      getSearchMotionDuration(), function () { $(this).remove(); });
  } else messages.remove();
  form.querySelectorAll('.invalid').forEach(element => element.classList.remove('invalid'));
}

function handleValErrors(errors) {
  clearValidationErrors();
  const errorDiv = document.getElementById('error-msgs');
  if (!errorDiv || !errors.length) return;
  errorDiv.classList.remove('visually-hidden');
  const message = document.createElement('div');
  message.className = 'error';
  message.innerHTML = '<div class="search-alert-heading">' +
    '<svg viewBox="0 0 24 24" focusable="false" aria-hidden="true"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2zm0-4h-2V7h2z"></path></svg>' +
    '<strong>Please check your search</strong>' +
    '<button type="button" class="search-alert-close" aria-label="Dismiss search errors">' +
    '<svg viewBox="0 0 24 24" focusable="false" aria-hidden="true"><path d="M19 6.41 17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"></path></svg>' +
    '</button></div><hr><ul class="search-alert-messages"></ul>';
  const list = message.querySelector('.search-alert-messages');
  errors.forEach(({ element, errorMsg }) => {
    element?.classList.add('invalid');
    const item = document.createElement('li');
    item.textContent = errorMsg;
    list.appendChild(item);
  });
  message.querySelector('.search-alert-close').addEventListener('click', () => clearValidationErrors(true));
  message.style.display = 'none';
  errorDiv.appendChild(message);
  $(message).animate({ height: 'show', opacity: 'show', marginBottom: 'show', paddingTop: 'show', paddingBottom: 'show' },
    getSearchMotionDuration());
}

function simpleSearch() {
  document.getElementById('alert-msgs')?.replaceChildren();
  const errors = validateForm();
  if (errors.length) handleValErrors(errors);
  else window.location.assign(getSearchUrl());
}

function hideColCheckbox(collid) {
  getCollectionInputs(collsModal).forEach(input => {
    if (input.value === String(collid)) input.closest('li').style.display = 'none';
  });
  refreshCollectionRowStripes();
}

/** Events: one delegated change handler also handles Select2 and dynamic inputs. */
$(form).on('input.searchForm', 'input:not([type="checkbox"]):not([type="radio"]), textarea', function () {
  if (this.classList.contains('search-date-range-input')) updateDatesFromSlider(this);
  updateChip();
});
$(form).on('change.searchForm', 'input, textarea, select', function () {
  if (this.name === 'collChoice' && collsModal.contains(this)) {
    collsModal.querySelectorAll('.box').forEach(box => { box.style.display = box.id === this.value ? 'block' : 'none'; });
    collsModal.querySelectorAll('.tab').forEach(tab => tab.classList.toggle('tab-active', tab.contains(this)));
    updateCollectionRowStripes(getCollectionView());
    updateGlobalMaster();
  } else if (this === allNeon) {
    setGroupChecked(collsModal, this.checked);
    refreshCollectionSelectors();
  } else if (this.type === 'checkbox' && !this.closest('#collection-search-map')) {
    if (this.classList.contains('all-selector')) toggleAllSelector.call(this);
    updateAncestors(this);
    updateGlobalMaster();
  }
  updateChip();
});

chips.addEventListener('click', event => {
  const button = event.target.closest('.chip-remove-btn');
  if (!button) return;
  const chip = renderedChips.get(button.parentElement.id);
  if (!chip) return;
  chip.clear();
  refreshCollectionSelectors();
  updateChip();
});

form.addEventListener('click', event => {
  const leafLabel = event.target.closest('.leaf-label');
  if (leafLabel) {
    const checkbox = leafLabel.previousElementSibling;
    if (checkbox?.matches('input[type="checkbox"]:enabled')) checkbox.click();
    return;
  }
  const expansion = event.target.closest('[data-target], .group-label, .expansion-icon');
  if (expansion && (collsModal.contains(expansion) || formColls.contains(expansion))) {
    const li = expansion.closest('li');
    const list = li?.querySelector(':scope > ul');
    if (!list) return;
    const branch = $(list).stop(true);
    branch.css('display', branch.css('display'));
    const collapsed = list.classList.toggle('collapsed');
    updateCollectionRowStripes(li.closest('.box, #neonext-collections-items'));
    branch[collapsed ? 'slideUp' : 'slideDown'](getSearchMotionDuration(), () => {
      list.style.removeProperty('display');
    });
    const icon = li.querySelector(':scope > .expansion-icon, :scope > [data-target] .expansion-icon');
    if (icon) icon.textContent = collapsed ? 'add_box' : 'indeterminate_check_box';
  }
});

form.querySelectorAll('#neon-modal-open, .neon-modal-open').forEach(opener => {
  opener.addEventListener('click', event => {
    if (event.target.closest('input, a')) return;
    event.preventDefault();
    if (opener.dataset.modalId) openModal(`#${opener.dataset.modalId}`);
  });
});
[
  ['neon-modal-close', '#biorepo-collections-list'],
  ['domains-sites-modal-close', '#domains-sites-modal'],
].forEach(([id, selector]) => {
  document.getElementById(id)?.addEventListener('click', event => {
    event.preventDefault();
    closeModal(selector);
    updateGlobalMaster();
    updateChip();
  });
});

form.addEventListener('submit', event => {
  event.preventDefault();
  updateChip();
  if (!document.getElementById('search-btn').disabled) simpleSearch();
});
document.getElementById('date-range-reset')?.addEventListener('click', () => {
  dateFields.forEach(input => { input.value = ''; });
  clearValidationErrors(true);
  updateChip();
});
form.addEventListener('reset', () => {
  // Native controls reset after this event; then reconcile widgets and derived state.
  setTimeout(() => {
    clearSites();
    resetAdvancedSearch();
    form.querySelectorAll('input[type="checkbox"]').forEach(input => { input.indeterminate = false; });
    $('#materialsampletype').trigger('change.select2');
    const selected = collsModal.querySelector('input[name="collChoice"]:checked');
    if (selected) $(selected).trigger('change');
    refreshCollectionSelectors();
    clearValidationErrors();
    updateChip();
  }, 0);
});

refreshCollectionRowStripes();
if (document.readyState === 'complete') prefillFromUrl();
else window.addEventListener('load', prefillFromUrl, { once: true });
