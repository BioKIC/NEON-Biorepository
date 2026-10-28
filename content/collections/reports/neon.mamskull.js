/***
 * NEON Mammals Skull Vials Labels Custom Styles
 *
 * - Uses local Code128 generation for NEON barcodes
 * - Moves collection name into the label header
 * - Removes ORCID from recordedby
 * - Removes NEON UUID
 * - Displays prepared_by from dynamicProperties
 */

(() => {
  const labels = document.querySelectorAll('.label');

  labels.forEach((label) => {
    // Move collection name into the label header.
    const coll = label.querySelector('.col-title');
    const header = label.querySelector('.label-header');

    if (coll && header) {
      header.appendChild(coll);
    }

    const catNums = label.querySelector('.other-catalog-numbers');
    const barcodeImage = label.querySelector('.cn-barcode img');

    if (catNums) {
      const entries = catNums.innerText.split(';');
      const remainingEntries = document.createDocumentFragment();
      let neonBarcode = '';

      entries.forEach((entry) => {
        const catNum = entry.trim();

        if (!catNum || catNum.includes('sampleUUID')) {
          return;
        }

        const barcodeMatch = catNum.match(/barcode\):\s*(.+)$/i);

        if (barcodeMatch && barcodeImage) {
          neonBarcode = barcodeMatch[1].trim();
          return;
        }

        // Remove the sampleID prefix, retaining other entry text.
        const displayText = catNum.replace(/^NEON sampleID:\s*/i, '');

        const span = document.createElement('span');
        span.className = 'block';
        span.textContent = displayText;
        remainingEntries.appendChild(span);
      });

      // Replace the image only when a NEON barcode is available.
      // Otherwise, retain the original catalog-number barcode.
      if (barcodeImage && neonBarcode) {
        barcodeImage.src =
          'getBarcode128.php?bcheight=40&bctext=' +
          encodeURIComponent(neonBarcode);
      }

      catNums.replaceChildren(remainingEntries);
      catNums.classList.add('mt-2');
    }

    // Remove the parenthesized ORCID, preserving other collector text.
    const recordedBy = label.querySelector('.recordedby');

    if (recordedBy) {
      recordedBy.innerText = recordedBy.innerText
        .replace(/\s*\(ORCID[^)]*\)/gi, '')
        .trim();
    }

    // Display prepared_by from dynamicProperties.
    const dynProps = label.querySelector('.dynamicproperties');

    if (dynProps) {
      let prepBy = '';

      try {
        const obj = JSON.parse(dynProps.innerText.trim());

        if (obj && obj.prepared_by) {
          prepBy = obj.prepared_by;
        }
      } catch (error) {
        console.error('Invalid dynamicProperties JSON:', error);
      }

      dynProps.innerText = 'Prep. by: ' + prepBy;
    } else if (recordedBy) {
      const prepBy = document.createElement('span');
      prepBy.className = 'dynamicproperties block';
      prepBy.innerText = 'Prep. by: ';
      recordedBy.parentNode.appendChild(prepBy);
    }
  });
})();