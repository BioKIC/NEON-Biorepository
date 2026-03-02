/***
 * NEON Mammals Skull Vials Labels Custom Styles
 * Author: Laura Rocha Prado
 * Version: April 2023
 *
 * Features:
 * - Replaces standard Symbiota barcode with custom (using NEON barcode instead of catalogNumber)
 * - Barcodes courtesy of barcode.tec-it.com
 * - Removes orcid from recordedby
 * - Removes NEON UUID
 * - Gets `preparedBy` and `preparedDate` field from `dynamicProperties` if available
 */

let labels = document.querySelectorAll('.label');
labels.forEach((label) => {
  // Moves collection name to label top div
  let coll = label.querySelector('.col-title');
  let header = label.querySelector('.label-header');
  header.appendChild(coll);
  let catNums = label.querySelector('.other-catalog-numbers');
  let cArr = catNums.innerText.split(';');
  let newCatNum = '';
  let hasBc = '';
  cArr.forEach((catNum) => {
    // Skip if it's a NEON UUID
    if (catNum.includes('sampleUUID')) {
      return;
    } else {
      // Skips adding barcode to 'other-catalog-numbers' because it's
      // displayed in actual barcode image
      let bcSrc = label.querySelector('.cn-barcode img');
      if (bcSrc) {
        if (catNum.includes('barcode')) {
          let barcode = catNum.match(/(?<=barcode\): ).*/)[0].trim();
          // bcSrc.src = 'getBarcode.php?bcheight=60&bctext=' + barcode;
          //bcSrc.src =
          //  'https://barcode.tec-it.com/barcode.ashx?data=' +
          //  barcode +
          //  '&code=Code128&hidehrt=False';
          bcSrc.src =
            'https://barcodeapi.org/api/128/' +
            barcode +
            '?&height=18&qz=0';
          hasBc = 'true';
          return hasBc;
        } else {
          let sIdPrefix = 'NEON sampleID: ';
          let sId = catNum.slice(sIdPrefix.length);
          newCatNum += `<span class="block">${sId.trim()}</span>`;
        }

        if (hasBc != 'true') {
          // if there is no NEON barcode, uses the IGSN (catalogNumber instead)
          //bcSrc.src =
          //  'https://barcode.tec-it.com/barcode.ashx?data=' +
          //  label.querySelector('.catalognumber').innerText +
          //  '&code=Code128&hidehrt=False';
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
  // Removes ORCID from recordedby
  let recordedBy = label.querySelector('.collector');

  if (recordedBy) {
    let orcid = recordedBy.innerText.indexOf(' (ORCID');
    if (orcid != -1) {
      let newRecordedBy = recordedBy.innerText.slice(0, orcid);
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
