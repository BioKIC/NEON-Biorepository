/***
 * NEON Barcode-only Custom Styles
 * Author: Chandra Earl
 * Version: Aug 2025
 *
 * Features:
 * - Replaces locality with NEON site code (e.g., GUAN, )
 * - Removes minus sign from longitude values
 * - Removes ORCID identifier from collector field
 * - Removes "Co." from county names when state is Puerto Rico
 * - Removes spaces from around dash to save space
 */

let labels = document.querySelectorAll(".label");
labels.forEach((label) => {

  let locality = label.querySelector(".locality");
  if (locality) {
    let text = locality.innerText;
    let match = text.match(/[A-Z]{4}(?=_\d+)/);
    if (match) {
      locality.innerText = match[0] + ", ";
    }
  }


  let longitude = label.querySelector(".decimallongitude");
  if (longitude) {
    longitude.innerText = longitude.innerText.replace("-", "");
  }
  
  let collector = label.querySelector(".collector");
  if (collector) {
    collector.innerText = collector.innerText.replace(/\s*\(ORCID.*\)/, "");
  }
  
  let state = label.querySelector(".stateprovince");
  let county = label.querySelector(".county");

  if (state && county) {
    if (state.innerText.toLowerCase().includes("puerto rico")) {
      county.innerText = county.innerText.replace(/\s*Co\.\s*$/, "");
    }
  }
  
  label.querySelectorAll(".field-block span").forEach((span) => {
    span.innerText = span.innerText.replace(/\s*-\s*/g, "-");
  });  
});