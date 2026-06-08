/**
 * GLOBAL VARIABLES
 */
const criteriaPanel = document.getElementById('criteria-panel');
const allNeon = document.getElementById('all-neon-colls-quick');
const allSites = document.getElementById('all-sites');
const form = document.getElementById('params-form');
const formColls = document.getElementById('search-form-colls');
const formSites = document.getElementById('site-list');
const collsModal = document.getElementById('colls-modal');

let isInitializing = true;
let suppressChipRender = true;

document.addEventListener('DOMContentLoaded', () => {
  suppressChipRender = false;
  isInitializing = false;
  updateChip();
});


let criterionSelected = getCriterionSelected();
//////////////////////////////////////////////////////////////////////////

/**
 * METHODS
 */

/**
 * Toggles tab selection for collection picking options in modal
 * Uses jQuery
 */
$('input[type="radio"]').click(function () {
  var inputValue = $(this).attr('value');
  var targetBox = $('#' + inputValue);
  $('.box').not(targetBox).hide();
  $(targetBox).show();
  $(this).parent().addClass('tab-active');
  $(this).parent().siblings().removeClass('tab-active');
});

/**
 * Opens modal with id selector
 * @param {String} elementid Selector for modal to be opened
 */
function openModal(elementid) {
  $(elementid).css('display', 'block');
  $(document.body).css('overflow: hidden');
}

/**
 * Closes modal with id selector
 * @param {String} elementid Selector for modal to be opened
 */
function closeModal(elementid) {
  $(elementid).css('display', 'none');
}
/**
 * Chips
 */

/**
 * Adds default chips
 * @param {HTMLObjectElement} element Input for which chips are going to be created by default
 */
function addChip(element) {
  //console.log(element);

  let inputChip = document.createElement('span');
  inputChip.classList.add('chip');
  let chipBtn = document.createElement('button');
  chipBtn.setAttribute('type', 'button');
  chipBtn.classList.add('chip-remove-btn');
  chipBtn.classList.add('Mui');
  // if element is domain or site, pass other content
  if (element.name == 'some-datasetid') {
    if (element.text != '') {
      inputChip.id = 'chip-some-datasetids';
      inputChip.textContent = element.text;
      chipBtn.onclick = function () {
        uncheckAll(document.getElementById('all-sites'));
        removeChip(inputChip);
      };
    }
  } else if (
    (element.name == 'neonext-collections-items') ||
    (element.name == 'ext-collections-list') ||
    (element.name == 'taxonomic-cat') ||
    (element.name == 'neon-theme') ||
    (element.name == 'sample-type')
  ) {
    inputChip.id = `chip-some-${element.name}-collids`;
    inputChip.textContent = element.text;
    chipBtn.onclick = function () {
      uncheckAll(document.getElementById(element.name));
      removeChip(inputChip);
    };
  } 
    else if (element.name === 'status') {
    inputChip.id = 'chip-status';
    inputChip.textContent = element.text;
    chipBtn.onclick = function () {
      document.querySelectorAll('.status-checkbox').forEach(cb => cb.checked = false);
      removeChip(inputChip);
    };
  }
  else {
    inputChip.id = 'chip-' + element.id;
    let isTextOrNum = (element.type == 'text') | (element.type == 'number') | (element.type == 'textarea');
    isTextOrNum
      ? (inputChip.textContent = `${element.dataset.chip}: ${element.value}`)
      : (inputChip.textContent = element.dataset.chip);
    chipBtn.onclick = function () {
      element.type === 'checkbox'
        ? (element.checked = false)
        : (element.value = element.defaultValue);
      element.dataset.formId ? uncheckAll(element) : '';
      removeChip(inputChip);
    };
  }
  inputChip.appendChild(chipBtn);
  document.getElementById('chips').appendChild(inputChip);
}

/**
 * Removes chip
 * @param {HTMLObjectElement} chip Chip element
 */
function removeChip(chip) {
  chip != null ? chip.remove() : '';
}

/**
 * Updates chips based on selected options
 * @param {Event} e
 */
function updateChip(e) {
  if (suppressChipRender) return;
  if (isInitializing) return;
  document.getElementById('chips').innerHTML = '';
  // first go through collections and sites
  // if any domains (except for "all") is selected, then add chip
  let dSList = document.querySelectorAll('#site-list input[type=checkbox]');
  let dSChecked = document.querySelectorAll(
    '#site-list input[type=checkbox]:checked'
  );
  if (dSChecked.length > 0 && dSChecked.length < dSList.length) {
    addChip(getDomainsSitesChips());
  }
  // if any biorepo colls are selected (except for "all"), then add chip
  let biorepoAllChecked = document.getElementById(
    'all-neon-colls-quick'
  ).checked;
  let biorepoChecked = Array.from(
    document.querySelectorAll(
      `#${getCriterionSelected()} input[name="collid[]"]:checked`
    )
  );
  if (!biorepoAllChecked && biorepoChecked.length > 0) {
    addChip(getCollsChips(getCriterionSelected(), 'Some Sample Types'));
  }
  // if any additional NEON colls are selected (except for "all"), then add chip
  let addCols = document.querySelectorAll(
    '#neonext-collections-items input[type=checkbox]'
  );
  let addColsChecked = document.querySelectorAll(
    '#neonext-collections-items input[type=checkbox]:checked'
  );
  if (addColsChecked.length > 0 && addColsChecked.length < addCols.length) {
    addChip(getCollsChips('neonext-collections-items', 'Some Sample Types at Other Repos'));
  }
  // if any external NEON colls are selected (expect for "all"), then add chip
  let extCols = document.querySelectorAll(
    '#ext-collections-list input[type=checkbox]'
  );
  let extColsChecked = document.querySelectorAll(
    '#ext-collections-list input[type=checkbox]:checked'
  );
  if (extColsChecked.length > 0 && extColsChecked.length < extCols.length) {
    addChip(getCollsChips('ext-collections-list', 'Some Ext NEON Colls'));
  }
  
  // then go through remaining inputs (exclude collid and datasetid)
  // go through entire form and find selected items
  formInputs.forEach((item) => {
        if (
          item.name !== 'collid[]' &&
          item.name !== 'datasetid' &&
          !item.name.startsWith('status')
        ) {      
        if (
        (item.type == 'checkbox' && item.checked) ||
        (item.type == 'text' && item.value != '') ||
        (item.type == 'textarea' && item.value != '') ||
        (item.type == 'number' && item.value != '') ||
        (item.type == 'select-one' && item.value != '') 
      ) {
        item.hasAttribute('data-chip') ? addChip(item) : '';
      }
    }
  });
  const statusChip = getStatusChips();
  if (statusChip) addChip(statusChip);

}

/**
 * Gets collections chips
 * @param {String} listId id of coll list element
 * @param {String} chipText explanatory text to be addded to chip
 * @returns {Object} chipEl chip element with text and name props
 */
function getCollsChips(listId, chipText) {
  // Goes through list of collection options
  let collOptions = document.querySelectorAll(
    `#${listId} input[type=checkbox]`
  );
  let collSelected = document.querySelectorAll(
    `#${listId} input[type=checkbox]:checked`
  );
  // If 'all' is not selected, picks which are selected
  collsArr = [];
  let chipEl = {};

  if (collOptions.length > collSelected.length) {
    // Generates chip element object
    collSelected.forEach((coll) => {
      // check if we're inside biorepo coll form
      let isColl = coll.dataset.cat != undefined;
      if (isColl) {
        let isCatSel = document.getElementById(coll.dataset.cat).checked;
        isCatSel ? '' : collsArr.push(coll.dataset.ccode);
      } else {
        collsArr.push(coll.dataset.ccode);
      }
    });
  }
  chipEl.text = `${chipText}: ${collsArr.join(', ')}`;
  chipEl.name = listId;
  return chipEl;
}

/**
 * Gets selected domains and sites to generate chips
 * @returns {Object} chipEl chip element with text and name props
 */
function getDomainsSitesChips() {
  let boxes = document.getElementsByName('datasetid');
  let dArr = [];
  let sArr = [];
  boxes.forEach((box) => {
    if (box.checked) {
      let isSite = box.dataset.domain != undefined;
      if (isSite) {
        let isDomainSel = document.getElementById(box.dataset.domain).checked;
        isDomainSel ? '' : sArr.push(box.id);
      } else {
        dArr.push(box.id);
      }
    }
  });
  let dStr = '';
  let sStr = '';
  dArr.length > 0 ? (dStr = `Domain(s): ${dArr.join(', ')} `) : '';
  sArr.length > 0 ? (sStr = `Sites: ${sArr.join(', ')}`) : '';
  let chipEl = {
    text: dStr + sStr,
    name: 'some-datasetid',
  };
  return chipEl;
}
/////////

//// status chips

function getStatusChips() {
  const all = document.querySelectorAll('.status-checkbox');
  const checked = document.querySelectorAll('.status-checkbox:checked');

  if (checked.length === 0) return null;

  if (checked.length === all.length) {
    return {
      text: 'Status: All',
      name: 'status'
    };
  }

  const values = Array.from(checked).map(cb => cb.value);

  return {
    text: `Status: ${values.join(', ')}`,
    name: 'status'
  };
}

/////////


/**
 * Toggles state of checkboxes in nested lists when clicking an "all-selector" element
 * Uses jQuery
 */
function toggleAllSelector() {
  // CASE 1: accordion-style (all-selector inside .accordion-subheader)
  const accordionHeader = this.closest('.accordion-subheader');
  if (accordionHeader) {
    const li = accordionHeader.closest('li');
    if (!li) return;

    li.querySelectorAll('.content input.child:enabled').forEach(cb => {
      cb.checked = this.checked;
      cb.indeterminate = false;
    });
    return;
  }

  // CASE 2: original behavior (unchanged)
  $(this)
    .siblings()
    .find('input[type=checkbox]:enabled')
    .prop('checked', this.checked)
    .prop('indeterminate', false)
    .attr('checked', this.checked);
}


/**
 * Triggers toggling of checked/unchecked boxes in nested lists
 * Default is all boxes are checked in HTML.
 * @param {String} e.data.element Selector for element containing
 * list, should be passed when binding function to element
 */

function updateGlobalMaster() {
  const $allKids = $('#collections-list1 input.child:enabled, #collections-list2 input.child:enabled, #collections-list3 input.child:enabled');
  const total = $allKids.length;
  const checked = $allKids.filter(':checked').length;
  const anyInd = $allKids.filter(function () { return this.indeterminate; }).length > 0;
  const $master = $('#all-neon-colls-quick');
  $master.prop('checked', total > 0 && checked === total && !anyInd);
  $master.prop('indeterminate', (checked > 0 && checked < total) || anyInd);
}

function updateAncestors(fromCheckbox) {
  // start at the LI that owns this checkbox
  let li = fromCheckbox.closest('li');

  while (li) {
    const parentLi = li.parentElement?.closest('li');
    if (!parentLi) break; // reached the root

    // --- CASE A: original structure ---
    // <li>
    //   <input class="all-selector">
    //   <ul> <li><input class="child"> ...
    // </li>
    let parentCb = parentLi.querySelector(':scope > input.all-selector');
    let kids = null;

    if (parentCb) {
      kids = parentLi.querySelectorAll(':scope > ul > li > input.child:enabled');
    } else {
      // --- CASE B: your accordion structure ---
      // <li>
      //   <label class="accordion-subheader">
      //     <input class="all-selector">
      //   </label>
      //   <div class="content">
      //     <ul> ... <input class="child"> ...
      // </li>
      parentCb = parentLi.querySelector(':scope > label.accordion-subheader input.all-selector');
      if (parentCb) {
        // only count real leaf checkboxes in the content area
        kids = parentLi.querySelectorAll(':scope > .content input.child:enabled');
      }
    }

    // If this parent LI doesn't have a group checkbox in either shape, just climb
    if (!parentCb || !kids) {
      li = parentLi;
      continue;
    }

    let total = 0, checkedCount = 0, anyIndeterminate = false;
    kids.forEach(cb => {
      total++;
      if (cb.indeterminate) anyIndeterminate = true;
      if (cb.checked) checkedCount++;
    });

    parentCb.checked = total > 0 && checkedCount === total && !anyIndeterminate;
    parentCb.indeterminate = anyIndeterminate || (checkedCount > 0 && checkedCount < total);

    // climb
    li = parentLi;
  }
}


function autoToggleSelector(e) {
  if (e.type !== 'click' && e.type !== 'change') return;

  const t = e.target;
  if (!t.classList.contains('child')) return;

  // If a group/all-selector changed, propagate to all descendants first
  if (t.classList.contains('all-selector')) {
    const li = t.closest('li');
    if (li) {
      li.querySelectorAll(':scope ul input.child:enabled').forEach(cb => {
        cb.checked = t.checked;
        cb.indeterminate = false;
      });
    }
  }

  // Then recompute all ancestors up to the root
  updateAncestors(t);
  updateGlobalMaster();
  updateChip();
}

/**
 * Unchecks children of 'all-selector' checkboxes or all checkboxes in list
 * when criterion chip is removed
 * Uses 'data-form-id' property in .php
 * @param {Object} element HTML Node Object
 */
function uncheckAll(element) {
  let isAllSel = element.classList.contains('all-selector');
  if (isAllSel) {
    let selChildren = document.querySelectorAll(
      `#${element.dataset.formId} input[type=checkbox]:checked`
    );
    selChildren.forEach((item) => {
      item.checked = false;
    });
  } else {
    let items = document.querySelectorAll(
      `#${element.id} input[type=checkbox]:checked`
    );
    items.forEach((item) => {
      item.checked = false;
    });
  }
}
/////////
function getCriterionSelected() {
  return collsModal.querySelector('.tab.tab-active input[type=radio]:checked')
    .value;
}

/**
 * Finds all collections selected
 * Uses active tab in modal
 */
function getCollsSelected() {
  let query = '#' + getCriterionSelected() + ' input[name="collid[]"]:checked';
  let selectedInModal = Array.from(document.querySelectorAll(query));
  let selectedInForm = Array.from(
    document.querySelectorAll('#search-form-colls input[name="collid[]"]:checked')
  );
  let collsArr = selectedInForm.concat(selectedInModal);
  return collsArr;
}

/**
 * Form validation functions
 * @returns {Array} errors Array of errors objects with form element it refers to (elId), for highlighting, and errorMsg
 */

form.addEventListener('submit', function (e) {
  const errors = validateForm?.() || [];

  if (errors.length) {
    e.preventDefault();
    handleValErrors(errors);
    return;
  }
});

/**
 * Gets validation errors, outputs alerts with error messages and highlights form element with error
 * @param {Array} errors Array with error objects with form element it refers to (elId), for highlighting, and errorMsg
 */
function handleValErrors(errors) {
  const errorDiv = document.getElementById('error-msgs');
  errorDiv.innerHTML = '';
  errors.map((err) => {
    let element = document.getElementById(err.elId);
    element.classList.add('invalid');
    errorDiv.classList.remove('visually-hidden');
    let errorP = document.createElement('p');
    errorP.classList.add('error');
    errorP.innerText = err.errorMsg + ' Click to dismiss.';
    errorP.onclick = function () {
      errorP.remove();
      element.classList.remove('invalid');
    };
    errorDiv.appendChild(errorP);
  });
}

function validateDateRange(startName, endName, label) {
  const errors = [];

  const start = document.querySelector(`[name="${startName}"]`)?.value;
  const end = document.querySelector(`[name="${endName}"]`)?.value;

  const startDate = new Date(start);
  const endDate = new Date(end);

  if ((start && !end) || (!start && end)) {
    errors.push({
      elId: startName,
      errorMsg: `Please provide BOTH a start and end date for ${label}.`
    });
    errors.push({
      elId: endName,
      errorMsg: `Please provide BOTH a start and end date for ${label}.`
    });
  }

  if (
    start &&
    end &&
    !isNaN(startDate) &&
    !isNaN(endDate) &&
    startDate > endDate
  ) {
    errors.push({
      elId: startName,
      errorMsg: `${label}: Start date must be before end date.`
    });
  }

  return errors;
}

function validateForm() {
  let errors = [];

  errors = errors.concat(
    validateDateRange('inquiry-eventdate1', 'inquiry-eventdate2', 'Inquiry Date')
  );

    errors = errors.concat(
    validateDateRange('status-eventdate1', 'status-eventdate2', 'Latest Status Date')
  );

  errors = errors.concat(
    validateDateRange('active-eventdate1', 'active-eventdate2', 'Active Date')
  );

  return errors;
}


/**
 * Hides selected collections checkboxes (for whatever reason)
 * @param {integer} collid
 */
function hideColCheckbox(collid) {
  let colsToHide = document.querySelectorAll(
    `input[type='checkbox'][value='${collid}']`
  );
  colsToHide.forEach((col) => {
    let li = col.closest('li');
    let isInsideSearchNEONColls = li.closest('#biorepo-collections-list.modal');
    if (isInsideSearchNEONColls) {
      li.style.display = 'none';
    }
  });
}

//////////////////////////////////////////////////////////////////////////

/**
 * EVENT LISTENERS/INITIALIZERS
 */
// Reset button
document
  .getElementById('reset-btn')
  .addEventListener('click', function (event) {
    document.getElementById('params-form').reset();
    for(let x = 1; x < 9; x++){
			if(x > 1) document.getElementById("customdiv"+x).style.display = "none";
		}
    updateChip();
  });
// Listen for open modal click but not when checkbox is clicked
document
  .querySelectorAll('#neon-modal-open, .neon-modal-open')
  .forEach(el => {
    el.addEventListener('click', function (event) {
      if (event.target.matches('input[type="checkbox"]')) {
        return; // let the checkbox do its thing
      }

      event.preventDefault();
      openModal('#biorepo-collections-list');
    });
  });
// When checking "all neon collections" box, toggle checkboxes in modal
$('#all-neon-colls-quick').click(function () {
  let isChecked = $(this).prop('checked');
  $('.all-neon-colls').prop('checked', isChecked);
  $('.all-neon-colls').siblings().find('.child').prop('checked', isChecked);
});
// When checking any 'all-selector', toggle children checkboxes
$('.all-selector').click(toggleAllSelector);
formColls.addEventListener('click', autoToggleSelector, false);
formColls.addEventListener('change', autoToggleSelector, false);
formSites.addEventListener('click', autoToggleSelector, false);
collsModal.addEventListener('click', autoToggleSelector, false);
collsModal.addEventListener('change', autoToggleSelector, false);
// Listen for close modal click and passes value of selected colls to main form
document
  .getElementById('neon-modal-close')
  .addEventListener('click', function (event) {
    removeChip(document.getElementById('chip-' + allNeon.id));
    event.preventDefault();
    closeModal('#biorepo-collections-list');
    let tabSelected = document.getElementById(getCriterionSelected());
    let isAllSelected =
      tabSelected.getElementsByClassName('all-neon-colls')[0].checked;
    allNeon.checked = isAllSelected;
    updateChip();
  });
//////// Binds Update chip on event change
const formInputs = document.querySelectorAll('#params-form input, #params-form textarea, #params-form select');
formInputs.forEach((formInput) => {
  formInput.addEventListener('change', updateChip);
});
// Binds expansion function to plus and minus icons in selectors, uses jQuery
$('#collections-list1, #collections-list2, #collections-list3').on('click', '.expansion-icon', function () {
  const $li = $(this).closest('li');
  const $childUl = $li.children('ul').first();

  if (!$childUl.length) return; // no children here

  const isCollapsed = $childUl.toggleClass('collapsed').hasClass('collapsed');
  $(this).text(isCollapsed ? 'add_box' : 'indeterminate_check_box');
});

// Hides MOSC-BU checkboxes
hideColCheckbox(58);
// Hides identified zoops for now
hideColCheckbox(55);
