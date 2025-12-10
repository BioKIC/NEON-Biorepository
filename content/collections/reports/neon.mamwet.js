/***
 * NEON Mammals Wet Labels Custom Styles
 * Author: Laura Rocha Prado
 * Version: March 2023
 *
 * Features:
 * - Replaces standard Symbiota barcode with custom (using NEON barcode instead of catalogNumber)
 * - Barcodes courtesy of barcode.tec-it.com
 * - Removes orcid from recordedby
 * - Removes NEON UUID
 * - Removes NEON Hash
 * - Gets `preparedBy` and `preparedDate` field from `dynamicProperties` if available
 */

let labels = document.querySelectorAll('.label');
labels.forEach((label) => {
  let catNums = label.querySelector('.other-catalog-numbers');
  let cArr = catNums.innerText.split(';');
  let newCatNum = '';
  let hasBc = '';
  cArr.forEach((catNum) => {
    // skip if it's a NEON UUID
    if (catNum.includes('sampleUUID') || catNum.includes('Hash')) {
      return;
    } else {
      newCatNum += `<span class="block">${catNum.trim()}</span>`;
      let bcSrc = label.querySelector('.cn-barcode img');
      if (bcSrc) {
        if (catNum.includes('barcode')) {
          let barcode = catNum.match(/(?<=barcode\): ).*/)[0].trim();
          //bcSrc.src =
          //  'https://barcode.tec-it.com/barcode.ashx?data=' +
          //  barcode +
          //  '&code=Code128';
          bcSrc.src =
            'https://barcodeapi.org/api/128/' +
            barcode +
            '?&height=18&qz=0';
          hasBc = 'true';
          return hasBc;
        }

        if (hasBc != 'true') {
          // if there is no NEON barcode, uses the IGSN (catalogNumber instead)
          //bcSrc.src =
          //  'https://barcode.tec-it.com/barcode.ashx?data=' +
          //  label.querySelector('.catalognumber').innerText +
          //  '&code=Code128';
          bcSrc.src =
            'https://barcodeapi.org/api/128/' +
            label.querySelector('.catalognumber').innerText +
            '?&height=18&qz=0';
        }
      }
    }
  });
  catNums.innerHTML = newCatNum;
  catNums.classList.add('mt-2');
  // Removes ORCID from collector
  let recordedBy = label.querySelector('.collector');
  if (recordedBy) {
    let hasOrcid = recordedBy.innerText.toLowerCase().includes('orcid');
    if (hasOrcid) {
      // remove ORCID from recordedBy
      let orcidIdx = recordedBy.innerText;
      let orcid = '(' + orcidIdx.match(/(?<=\()[^)]*(?=\))/)[0] + ')';
      let newRecordedBy = recordedBy.innerText.replace(orcid, '');
      recordedBy.innerText = newRecordedBy;
    }
  }
  // Gets `preparedBy` field from `dynamicProperties` if available
  let dynProps = label.querySelector('.dynamicproperties');
  if (dynProps) {
    let prepBy = '';
    try {
      const obj = JSON.parse(dynProps.innerText.trim());
      if (obj.prepared_by) {
        prepBy = obj.prepared_by;
      }
    } catch (e) {
      // If invalid JSON, leave prepBy empty
    }

  dynProps.innerText = 'Prep. by: ' + (prepBy || '');
  } else {
    // Create 'Prep. by:' when no dynProps are found, after collector div (only if collector exists)
    if (recordedBy) {
      let prepBy = document.createElement('span');
      prepBy.className = 'dynamicproperties block';
      prepBy.innerText = 'Prep. by: ';
      recordedBy.parentNode.appendChild(prepBy);
    }
  }
});
