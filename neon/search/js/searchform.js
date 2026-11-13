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
function openModal(selector) {
  const modal = document.querySelector(selector);
  if (!modal) return;
  if (modalBodyOverflow === null) modalBodyOverflow = document.body.style.overflow;
  modal.style.display = 'block';
  document.body.style.overflow = 'hidden';
}

function closeModal(selector) {
  const modal = document.querySelector(selector);
  if (modal) modal.style.display = 'none';
  const anyOpen = Array.from(form.querySelectorAll('.modal'))
    .some(item => item.style.display === 'block');
  if (!anyOpen && modalBodyOverflow !== null) {
    document.body.style.overflow = modalBodyOverflow;
    modalBodyOverflow = null;
  }
}

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
  return { id: 'chip-advancedsearchstr', text: `Advanced Search: ${text}`, clear: resetAdvancedSearch };
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

function updateChip() {
  if (!chips) return;
  const next = [];
  const selectedSites = Array.from(form.querySelectorAll('input[name="datasetid"]:checked'));
  if (selectedSites.length) {
    const labels = [...new Set(selectedSites.map(input => input.id || input.value))];
    next.push({ id: 'chip-some-datasetids', text: `Sites: ${labels.join(', ')}`, clear: clearSites });
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
    const active = input.type === 'checkbox' ? input.checked : input.value !== '';
    if (active) next.push(getInputChip(input));
  });

  // Reuse unchanged chips, preserving keyboard focus and avoiding repeated DOM rebuilds.
  const nextIds = new Set(next.map(chip => chip.id));
  renderedChips.forEach((chip, id) => {
    if (!nextIds.has(id)) {
      chip.node.remove();
      renderedChips.delete(id);
    }
  });
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

function prefillFromUrl() {
  const params = new URLSearchParams(window.location.search);
  if (params.has('db')) {
    const selected = new Set(params.getAll('db').flatMap(value => value.split(',')));
    collectionViews.forEach(id => {
      getCollectionInputs(document.getElementById(id)).forEach(input => {
        input.checked = selected.has('all') || selected.has(input.value);
      });
    });
    getCollectionInputs(formColls).forEach(input => { input.checked = selected.has(input.value); });
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
  });
  const excludeSynonyms = document.getElementById('usethes');
  if (excludeSynonyms && params.has('usethes')) excludeSynonyms.checked = params.get('usethes') !== '1';

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
    errors.push({ element: input, errorMsg: `Please check values in field ${input.dataset.chip || input.name}.` });
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

function clearValidationErrors() {
  document.getElementById('error-msgs')?.replaceChildren();
  form.querySelectorAll('.invalid').forEach(element => element.classList.remove('invalid'));
}

function handleValErrors(errors) {
  clearValidationErrors();
  const errorDiv = document.getElementById('error-msgs');
  if (!errorDiv) return;
  errorDiv.classList.remove('visually-hidden');
  errors.forEach(({ element, errorMsg }) => {
    element?.classList.add('invalid');
    const message = document.createElement('p');
    message.className = 'error';
    message.textContent = `${errorMsg} Click to dismiss.`;
    message.onclick = () => {
      message.remove();
      element?.classList.remove('invalid');
    };
    errorDiv.appendChild(message);
  });
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
}

/** Events: one delegated change handler also handles Select2 and dynamic inputs. */
$(form).on('change.searchForm', 'input, textarea, select', function () {
  if (this.name === 'collChoice' && collsModal.contains(this)) {
    collsModal.querySelectorAll('.box').forEach(box => { box.style.display = box.id === this.value ? 'block' : 'none'; });
    collsModal.querySelectorAll('.tab').forEach(tab => tab.classList.toggle('tab-active', tab.contains(this)));
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
    const collapsed = list.classList.toggle('collapsed');
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
  simpleSearch();
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

if (document.readyState === 'complete') prefillFromUrl();
else window.addEventListener('load', prefillFromUrl, { once: true });
