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
// list of parameters to be passed to url, modified by getSearchUrl method
let paramNames = [
  'db',
  'datasetid',
  'catnum',
  'collector',
  'includeothercatnum',
  'includematerialsample',
  'hasimages',
  'hasgenetic',
  'state',
  'county',
  'local',
  'elevlow',
  'elevhigh',
  'llbound',
  'footprintwkt',
  'llpoint',
  'eventdate1',
  'eventdate2',
  'taxa',
  'usethes',
  'taxontype',
  'availableforloan',
];

for (let i = 1; i <= 8; i++) {
  paramNames.push(
    `q_customopenparen${i}`,
    `q_customfield${i}`,
    `q_customtype${i}`,
    `q_customvalue${i}`,
    `q_customcloseparen${i}`
  );
  if (i < 8) {
    paramNames.push(`q_customandor${i + 1}`);
  }
}


const uLat = document.getElementById('upperlat');
const uLatNs = document.getElementById('upperlat_NS');
const bLat = document.getElementById('bottomlat');
const bLatNs = document.getElementById('bottomlat_NS');
const lLng = document.getElementById('leftlong');
const lLngEw = document.getElementById('leftlong_EW');
const rLng = document.getElementById('rightlong');
const rLngEw = document.getElementById('rightlong_EW');
const pLat = document.getElementById('pointlat');
const pLatNs = document.getElementById('pointlat_NS');
const pLng = document.getElementById('pointlong');
const pLngEw = document.getElementById('pointlong_EW');
const pRadius = document.getElementById('radius');
const pRadiusUn = document.getElementById('radiusunits');

let criterionSelected = getCriterionSelected();
let paramsArr = [];
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
 * Opens map helper
 * @param {String} mapMode Option from select in form
 * Function from `../../js/symb/collections.harvestparams.js`
 */
function openCoordAid(mapMode) {
  mapWindow = open(
    '../../collections/tools/mapcoordaid.php?mapmode=' + mapMode,
    'polygon',
    'resizable=0,width=900,height=630,left=20,top=20'
  );
  if (mapWindow.opener == null) mapWindow.opener = self;
  mapWindow.focus();
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
    (element.name == 'neonext-collections-list') |
    (element.name == 'ext-collections-list') |
    (element.name == 'taxonomic-cat') |
    (element.name == 'neon-theme') |
    (element.name == 'sample-type')
  ) {
    inputChip.id = `chip-some-${element.name}-collids`;
    inputChip.textContent = element.text;
    chipBtn.onclick = function () {
      uncheckAll(document.getElementById(element.name));
      removeChip(inputChip);
    };
  } else if (element.name == 'advancedsearchstr') {
    if (element.text != '') {
      inputChip.id = 'chip-advancedsearchstr';
      inputChip.textContent = element.text;
      chipBtn.onclick = function () {
        const formElements = document.querySelectorAll('#search-form-advanced-search input, #search-form-advanced-search select');
        formElements.forEach(element => {
            if (element.type === 'checkbox') {
                element.checked = false;
            } else if (element.type === 'select'){
                element.selectedIndex = 0;
            } else {
                element.value = '';
            }
        for(let x = 1; x < 9; x++){
          if(x > 1) document.getElementById("customdiv"+x).style.display = "none";
        }
        removeChip(inputChip);
        });
      }
    }
  } else {
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
      `#${getCriterionSelected()} input[name="db"]:checked`
    )
  );
  if (!biorepoAllChecked && biorepoChecked.length > 0) {
    addChip(getCollsChips(getCriterionSelected(), 'Some Biorepo Colls'));
  }
  // if any additional NEON colls are selected (except for "all"), then add chip
  let addCols = document.querySelectorAll(
    '#neonext-collections-list input[type=checkbox]'
  );
  let addColsChecked = document.querySelectorAll(
    '#neonext-collections-list input[type=checkbox]:checked'
  );
  if (addColsChecked.length > 0 && addColsChecked.length < addCols.length) {
    addChip(getCollsChips('neonext-collections-list', 'Some Add NEON Colls'));
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
  // if there are advanced query changes
  let advCheckbox = document.getElementById('AdvancedHasBeenChanged');
  if (advCheckbox.checked == true) {
    addChip(getAdvancedSearchChip());
    //getAdvancedSearchChip();
  }
  
  // then go through remaining inputs (exclude db and datasetid)
  // go through entire form and find selected items
  formInputs.forEach((item) => {
    if ((item.name != 'db') | (item.name != 'datasetid')) {
      if (
        (item.type == 'checkbox' && item.checked) |
        (item.type == 'text' && item.value != '') |
        (item.type == 'textarea' && item.value != '') |
        (item.type == 'number' && item.value != '')
      ) {
        // now add chips depending on type of item
        item.hasAttribute('data-chip') ? addChip(item) : '';
      }
    }
    // print inputs checked or filled in
  });
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

/**
 * Creates advanced search string to generate chip
 * @returns {Object} chipEl chip element with text and name props
 */
function getAdvancedSearchChip() {
  const advancedInputs = document.querySelectorAll('#search-form-advanced-search select, #search-form-advanced-search input[type=text]');
  let sqlString = "";
  let openParen = "";
  let closeParen = "";
	advancedInputs.forEach((advancedInput) => {
    const name = advancedInput.name;
    const value = advancedInput.value;
    
    if (name.startsWith("q_customopenparen")) {
      openParen = value;
    } else if (name.startsWith("q_customfield")) {
      if (value) {
        sqlString += `${openParen}${advancedInput.options[advancedInput.selectedIndex].text}`;
      }
    } else if (name.startsWith("q_customtype")) {
      if (value) {
        sqlString += ` ${advancedInput.options[advancedInput.selectedIndex].text}`;
      }
    } else if (name.startsWith("q_customvalue")) {
      if (value) {
        sqlString += ` '${value}'`;
      }
    } else if (name.startsWith("q_customcloseparen")) {
      closeParen = value;
    } else if (name.startsWith("q_customandor")) {
      sqlString += ` ${value} `;
    }
    
    if (closeParen) {
      sqlString += `${closeParen}`;
      openParen = "";
      closeParen = "";
    }
	});
  
  let chipEl = {
    text: "Advanced Search: " + sqlString.trim(),
    name: 'advancedsearchstr',
  };
  return chipEl;
}
/////////


/**
 * Toggles state of checkboxes in nested lists when clicking an "all-selector" element
 * Uses jQuery
 */
function toggleAllSelector() {
  $(this)
    .siblings()
    .find('input[type=checkbox]:enabled')
    .prop('checked', this.checked)
    .attr('checked', this.checked);
}

/**
 * Triggers toggling of checked/unchecked boxes in nested lists
 * Default is all boxes are checked in HTML.
 * @param {String} e.data.element Selector for element containing
 * list, should be passed when binding function to element
 */
function autoToggleSelector(e) {
  if (e.type == 'click' || e.type == 'change') {
    let isChild = e.target.classList.contains('child');
    if (isChild) {
      let nearParentNode = e.target.closest('ul').parentNode;
      let nearParentOpt = e.target
        .closest('ul')
        .parentNode.querySelector('.all-selector');
      let numOptions = nearParentNode.querySelectorAll(
        'ul > li input.child:not(.all-selector):enabled'
      ).length;
      let numOpChecked = nearParentNode.querySelectorAll(
        'ul > li input.child:not(.all-selector):checked'
      ).length;
      numOptions == numOpChecked
        ? (nearParentOpt.checked = true)
        : (nearParentOpt.checked = false);

      if (nearParentOpt.classList.contains('child')) {
        let parentAllNode = nearParentNode.closest('ul').parentNode;
        let parentAllOpt = parentAllNode.querySelector('.all-selector');
        let numOptionsAll = parentAllNode.querySelectorAll(
          'input.child:enabled'
        ).length;
        let numOpCheckedAll = parentAllNode.querySelectorAll(
          'input.child:checked'
        ).length;
        numOptionsAll == numOpCheckedAll
          ? (parentAllOpt.checked = true)
          : (parentAllOpt.checked = false);
      }
    }
  }
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
  let query = '#' + getCriterionSelected() + ' input[name="db"]:checked';
  let selectedInModal = Array.from(document.querySelectorAll(query));
  let selectedInForm = Array.from(
    document.querySelectorAll('#search-form-colls input[name="db"]:checked')
  );
  let collsArr = selectedInForm.concat(selectedInModal);
  return collsArr;
}

/**
 * Searches specified fields and capture values
 * @param {String} paramName Name of parameter to be looked for in form
 * Passes objects to `paramsArr`
 * Passes default objects
 */
function getParam(paramName) {
  //Default country
  // paramsArr['country'] = 'USA';
  const elements = document.getElementsByName(paramName);
  const firstEl = elements[0];

  let elementValues = '';

  // for db and datasetid
  if (paramName === 'db') {
    let dbArr = [];
    let tempArr = getCollsSelected();
    tempArr.forEach((item) => {
      dbArr.push(item.value);
    });
    elementValues = dbArr;
  } else if (paramName === 'datasetid') {
    let datasetArr = [];
    elements.forEach((el) => {
      if (el.checked) {
        let isSite = el.dataset.domain != undefined;
        if (isSite) {
          let isDomainSel = document.getElementById(el.dataset.domain).checked;
          isDomainSel ? '' : datasetArr.push(el.value);
        } else {
          datasetArr.push(el.value);
        }
      }
    });
    elementValues = datasetArr;
  } else if (paramName === 'llbound') {
    // Only if inputs aren't empty
    if (
      uLat.value != '' &&
      bLat.value != '' &&
      lLng.value != '' &&
      rLng.value != ''
    ) {
      let uLatVal = uLatNs.value == 'S' ? uLat.value * -1 : uLat.value * 1;
      let bLatVal = bLatNs.value == 'S' ? bLat.value * -1 : bLat.value * 1;
      let lLngVal = lLngEw.value == 'W' ? lLng.value * -1 : lLng.value * 1;
      let rLngVal = rLngEw.value == 'W' ? rLng.value * -1 : rLng.value * 1;
      elementValues = `${uLatVal};${bLatVal};${lLngVal};${rLngVal}`;
    }
  } else if (paramName === 'llpoint') {
    if (
      pLat.value != '' &&
      pLng.value != '' &&
      pRadius.value != '' &&
      pRadiusUn.value != ''
    ) {
      let pLatVal =
        pLatNs.value == 'S'
          ? Math.round(pLat.value * -1 * 100000) / 100000
          : Math.round(pLat.value * 100000) / 100000;
      let pLngVal =
        pLngEw.value == 'W'
          ? Math.round(pLng.value * -1 * 100000) / 100000
          : Math.round(pLng.value * 100000) / 100000;
      let pRadiusVal = pRadius.value + ';' + pRadiusUn.value;
      elementValues = `${pLatVal};${pLngVal};${pRadiusVal}`;
    }
  } else if (elements[0] != undefined) {
    switch (firstEl.tagName) {
      case 'INPUT':
        (firstEl.type === 'checkbox' && firstEl.checked) ||
        ((firstEl.type === 'text' || firstEl.type === 'number' || firstEl.type === 'textarea') && firstEl != '')
          ? (elementValues = firstEl.value)
          : '';
        break;
      case 'SELECT':
        elementValues = firstEl.options[firstEl.selectedIndex].value;
        break;
      case 'TEXTAREA':
        elementValues = firstEl.value;
        break;
    }
  }
  elementValues != '' ? (paramsArr[paramName] = elementValues) : '';
  return paramsArr;
}

/**
 * Creates search URL with parameters
 * Define parameters to be looked for in `paramNames` array
 */
function getSearchUrl() {
  const harvestUrl = location.href.slice(0, location.href.indexOf('/neon/search'));
  const baseUrl = new URL(harvestUrl + '/collections/list.php');

  // Clears array temporarily to avoid redundancy
  paramsArr = [];

  // Only adds 'datasetid' to list of params if there is at least one selected
  // and if 'all' is not checked
  if (allSites.checked) {
    paramNames = paramNames.filter((value, index, arr) => {
      return value != 'datasetid';
    });
  } else {
    document.querySelectorAll('#site-list input[type=checkbox]:checked')
      .length > 1
      ? paramNames.push('datasetid')
      : false;
  }

  // Grabs params from form for each param name
  paramNames.forEach((param, i) => {
    return getParam(paramNames[i]);
  });

  // Appends each key value for each param in search url
  let queryString = Object.keys(paramsArr).map((key) => {
    //   return encodeURIComponent(key) + '=' + encodeURIComponent(paramsArr[key])
    // }).join('&');
    // console.log(baseURL + queryString);
    baseUrl.searchParams.append(key, paramsArr[key]);
  });
  return baseUrl.href;
}

/**
 * Form validation functions
 * @returns {Array} errors Array of errors objects with form element it refers to (elId), for highlighting, and errorMsg
 */
function validateForm() {
  errors = [];
  // DB
  let anyCollsSelected = getCollsSelected();
  if (anyCollsSelected.length === 0) {
    errors.push({
      elId: 'search-form-colls',
      errorMsg: 'Please select at least one collection.',
    });
  }
  // HTML5 built-in validation
  let invalidInputs = document.querySelectorAll('input:invalid');
  if (invalidInputs.length > 0) {
    invalidInputs.forEach((inp) => {
      errors.push({
        elId: inp.id,
        errorMsg: `Please check values in field ${inp.dataset.chip}.`,
      });
    });
  }
  // Bounding Box
  let bBoxNums = document.querySelectorAll(
    '#bounding-box-form input[type=number]'
  );
  let bBoxNumArr = [];
  bBoxNums.forEach((el) => {
    el.value != '' ? bBoxNumArr.push(el.value) : false;
  });
  let bBoxCardinals = document.querySelectorAll('#bounding-box-form select');
  selectedCardinals = [];
  bBoxCardinals.forEach((hItem) => {
    hItem.value != '' ? selectedCardinals.push(hItem.id) : false;
  });
  if (bBoxNumArr.length > 0 && bBoxNumArr.length < bBoxNums.length) {
    errors.push({
      elId: 'bounding-box-form',
      errorMsg:
        'Please make sure either all Lat/Long bounding box values contain a value, or all are empty.',
    });
  } else if (bBoxNumArr.length > 0 && selectedCardinals.length == 0) {
    errors.push({
      elId: 'bounding-box-form',
      errorMsg: 'Please select hemisphere values.',
    });
  } else if (bBoxNumArr.length > 0 && selectedCardinals.length > 0) {
    let uLatVal = uLat.value;
    let uLatNsVal = uLatNs.value;
    let bLatVal = bLat.value;
    let bLatNsVal = bLatNs.value;

    if (uLatNsVal == 'S' && bLatNsVal == 'S') {
      uLatVal = uLatVal * -1;
      bLatVal = bLatVal * -1;
      if (uLatVal < bLatVal) {
        errors.push({
          elId: 'bounding-box-form',
          errorMsg:
            'Your northern latitude value is less than your southern latitude value.',
        });
      }
    }

    let lLngVal = lLng.value;
    let lLngEwVal = lLngEw.value;
    let rLngVal = rLng.value;
    let rLngEwVal = rLngEw.value;

    if (lLngEwVal == 'W' && rLngEwVal == 'W') {
      lLngVal = lLngVal * -1;
      rLngVal = rLngVal * -1;
      if (lLngVal > rLngVal) {
        errors.push({
          elId: 'bounding-box-form',
          errorMsg:
            'Your western longitude value is greater than your eastern longitude value. Note that western hemisphere longitudes in the decimal format are negative.',
        });
      }
    }
  }

  
  const advancedHasBeenChangedCheckbox = document.getElementById('AdvancedHasBeenChanged');

  if (advancedHasBeenChangedCheckbox.checked == true) {
    const advancedInputs = document.querySelectorAll('#search-form-advanced-search select, #search-form-advanced-search input[type=text]');
    let openParensCount = 0;
    let closeParensCount = 0;
    
    const nonDefaultInputs = Array.from(advancedInputs).filter(input => {
      // For select elements, check if the value is not the first option (default)
      if (input.tagName === 'SELECT') {
        return input.selectedIndex !== 0;
      }
      // For text inputs, check if the value is not empty
      if (input.type === 'text') {
        return input.value.trim() !== '';
      }
      return false;
    });
    
    // To keep track of whether we've seen a field without a complete statement
    let awaitingCondition = false;
    let awaitingValue = false;
    let lastInputType = "";
  
    nonDefaultInputs.forEach((advancedInput) => {
      const name = advancedInput.name;
      const value = advancedInput.value.trim();
  
      if (name.startsWith("q_customopenparen")) {
        openParensCount += value.length;
        lastInputType = "openparen";
      } else if (name.startsWith("q_customfield")) {
        if (awaitingCondition || awaitingValue) {
          errors.push({
            elId: 'search-form-advanced-search',
            errorMsg:
              'Each field must have a corresponding condition and value if required.',
          });
        }
        awaitingCondition = true;
        lastInputType = "field";
      } else if (name.startsWith("q_customtype")) {
        if (lastInputType !== "field") {
          errors.push({
            elId: 'search-form-advanced-search',
            errorMsg:
              'Missing field name.',
          });
        }
        awaitingCondition = false;
        if (value !== "NULL" && value !== "NOTNULL") {
          awaitingValue = true;
        }
        lastInputType = "type";
      } else if (name.startsWith("q_customvalue")) {
        if (lastInputType !== "type") {
          errors.push({
             elId: 'search-form-advanced-search',
             errorMsg:
               'Missing condition statement.',
           });
        }
        awaitingValue = false;
        lastInputType = "value";
      } else if (name.startsWith("q_customcloseparen")) {
          closeParensCount += value.length;
          lastInputType = "closeparen";
      } else if (name.startsWith("q_customandor")) {
        if (lastInputType !== "value" && lastInputType !== "closeparen" && lastInputType !== "type") {
          errors.push({
             elId: 'search-form-advanced-search',
             errorMsg:
               'AND/OR must follow a value, condition type, or closing parenthesis.',
           });
        }
        lastInputType = "andor";
      }
    });
  
    if (openParensCount !== closeParensCount) {
      errors.push({
        elId: 'search-form-advanced-search',
        errorMsg:
          'Mismatched parentheses.',
      });
    }
    
    if (awaitingCondition) {
      errors.push({
         elId: 'search-form-advanced-search',
         errorMsg:
           'Missing condition statement.',
       });
    }
    if (awaitingValue) {
      errors.push({
         elId: 'search-form-advanced-search',
         errorMsg:
           'Missing input value.',
       });
    }
  }
  return errors;
}

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

/**
 * Calls methods to validate form and build URL that will redirect search
 */
function simpleSearch() {
  let alerts = document.getElementById('alert-msgs');
  alerts != null ? (alerts.innerHTML = '') : '';
  let errors = [];
  errors = validateForm();
  let isValid = errors.length == 0;
  if (isValid) {
    let searchUrl = getSearchUrl();
    window.location = searchUrl;
  } else {
    handleValErrors(errors);
  }
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

// Search button
document
  .getElementById('search-btn')
  .addEventListener('click', function (event) {
    event.preventDefault();
    simpleSearch();
  });
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
// Listen for open modal click
document
  .getElementById('neon-modal-open')
  .addEventListener('click', function (event) {
    event.preventDefault();
    openModal('#biorepo-collections-list');
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
const formInputs = document.querySelectorAll('.content input, .content textarea, #search-form-advanced-search select');
formInputs.forEach((formInput) => {
  formInput.addEventListener('change', updateChip);
});
// on default (on document load): All Neon Collections, All Domains & Sites, Include other IDs, All Domains & Sites
window.addEventListener('load', updateChip);
// Binds expansion function to plus and minus icons in selectors, uses jQuery
$('.expansion-icon').click(function () {
  if ($(this).siblings('ul').hasClass('collapsed')) {
    $(this)
      .html('indeterminate_check_box')
      .siblings('ul')
      .removeClass('collapsed');
  } else {
    $(this).html('add_box').siblings('ul').addClass('collapsed');
  }
});
// Hides MOSC-BU checkboxes
hideColCheckbox(58);
// Hides identified zoops for now
hideColCheckbox(55);
//Hides opal soils
hideColCheckbox(96); 
// Hides subsample collections for the moment
hideColCheckbox(110);
hideColCheckbox(111);
// Hides large invert collection for now
hideColCheckbox(114);
