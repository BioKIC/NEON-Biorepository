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
    let match = other.innerText.match(/NEON sampleID:[^;]*/);
    if (match) {
      other.innerText = match[0].trim();
    } else {
      other.innerText = "";
    }
  }
});