/***
 * NEON Herptiles Vial Labels Custom Styles
 * Author: Laura Rocha Prado
 * Version: September 2022
 *
 * Features:
 * - Replaces standard Symbiota barcode with custom (using NEON barcode instead of catalogNumber)
 * - Barcodes courtesy of barcode.tec-it.com
 * - Removes orcid from recordedby
 * - Removes NEON UUID
 * - Deactivates this to remove prepBy line: "Gets `preparedBy` and `preparedDate` field from `dynamicProperties` if available"
 * - Removes newly added "NEON sampleID Hash" and barcode from last line (leave only sampleID)
 */

let labels = document.querySelectorAll('.label');
labels.forEach((label) => {
  let catNums = label.querySelector('.other-catalog-numbers');
  let cArr = catNums.innerText.split(';');
  let newCatNum = '';
  let hasBc = '';
  let bcSrc = label.querySelector('.cn-barcode img');
  cArr.forEach((catNum) => {
    // add barcode image
    if (catNum.includes('barcode')) {
      if (bcSrc) {
        let barcode = catNum.match(/(?<=barcode\): ).*/)[0].trim();
          bcSrc.src =
            'getBarcode128.php?bcheight=40&bctext=' +
            encodeURIComponent(barcode);
        return hasBc;
      }
    } else if (catNum.includes('sampleID:')) {
      // add sampleID to bottom
      catNum = catNum.substr(
        catNum.indexOf('sampleID:') + 'sampleID:'.length,
        catNum.length
      );
      newCatNum += `<span class="block">${catNum.trim()}</span>`;
    }
    catNums.innerHTML = newCatNum;
    catNums.classList.add('mt-2');
  });
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
});
