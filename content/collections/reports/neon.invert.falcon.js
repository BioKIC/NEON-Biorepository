/***
 * NEON Barcode-only Custom Styles
 * Author: Chandra Earl
 * Version: Aug 2025
 *
 * Features:
 * - Removes all othercatalognumbers except NEON SampleID
 */

let labels = document.querySelectorAll(".label");
labels.forEach((label) => {

  let otherCatNums = label.querySelector(".other-catalog-numbers");
  if (otherCatNums) {
    otherCatNums.remove();
  }

  let other = label.querySelector(".othercatalognumbers");
  if (other) {
    let codeMatch = other.innerText.match(/NEON sampleCode \(barcode\):\s*([^;]+)/);
    let bcImg = label.querySelector(".cn-barcode img");

    if (codeMatch && bcImg) {
      let sampleCode = codeMatch[1].trim();
      bcImg.src = bcImg.src.replace(/(bctext=)[^&]+/, "$1" + encodeURIComponent(sampleCode));
    } else if (bcImg) {
      bcImg.remove();
    }

    let match = other.innerText.match(/NEON sampleID:\s*([^;]+)/);
    if (match) {
      other.innerText = match[1].trim();
    } else {
      other.remove();
    }
  }
});
