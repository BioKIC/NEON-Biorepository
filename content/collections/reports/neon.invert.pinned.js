/***
 * NEON Pinned Invertebrate Custom Styles
 * Author: Chandra Earl
 * Version: Aug 2025
 *
 * Features:
 * - Replaces locality with NEON site name and code, removing "NEON" (e.g., "Lower Teakettle (TEAK),")
 * - Removes minus sign from longitude values
 * - Removes ORCID identifier from collector field
 * - Abbreviates collector names to first initial + last name (e.g., "Rebecca Valentine." → "R. Valentine.")
 * - Converts Battelle/Field Ops/Neon Inc. email addresses to abbreviated names (e.g., "ehenniger@field-ops.org" → "E. Henniger")
 * - Leaves other email addresses unchanged
 * - Removes "Co." from county names when state is Puerto Rico
 * - Removes spaces around dashes for compact formatting
 * - Extracts only the NEON sampleID from other catalog numbers; removes the element if not present
 * - Shortens date ranges:
 *    - Same month/year → "16-30 Sept. 2019"
 *    - Different months, same year → "31 Jul.-14 Aug. 2014"
 *    - Different years → "28 Dec. 2019-2 Jan. 2020"
 * - Preserves text prefixes before dates (e.g., "Pitfall Trap.") while shortening the date range
 */

function monthToRoman(monthIndex) {
  const romans = ["i","ii","iii","iv","v","vi","vii","viii","ix","x","xi","xii"];
  return romans[monthIndex];
}

let labels = document.querySelectorAll(".label");
labels.forEach((label) => {

  let locality = label.querySelector(".locality");
  if (locality) {
    let text = locality.innerText;
    let match = text.match(/([^,]+?\([A-Z]{4}\))(?=,\s*Plot)?/);
    if (match) {
      let cleaned = match[1]
        .replace(/\s*NEON\s*/i, " ")
        .replace(/\s*\([A-Z]{4}\)/, "")
        .trim();
      locality.innerText = cleaned + ".";
    }
  }

  let longitude = label.querySelector(".decimallongitude");
  if (longitude) {
    longitude.innerText = longitude.innerText.replace("-", "");
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
  
  let otherCat = label.querySelector(".othercatalognumbers");
  if (otherCat) {
    let text = otherCat.innerText;
    let match = text.match(/NEON sampleID:\s*([^;]+)/);
    if (match) {
      otherCat.innerText = match[1].trim();
    } else {
      otherCat.remove();
    }
  }

  let eventdate = label.querySelector(".eventdate");
  let eventdate2 = label.querySelector(".eventdate2");
  
  if (eventdate && eventdate2) {
    let prefixMatch = eventdate.innerText.match(/^(.*?\.\s*)(.*)$/);
    let prefix = "";
    let startText = eventdate.innerText;
    if (prefixMatch) {
      prefix = prefixMatch[1];
      startText = prefixMatch[2];
    }
  
    let endText = eventdate2.innerText.replace(/^\-/, "").trim();
  
    let start = new Date(startText);
    let end = new Date(endText);
  
    if (!isNaN(start) && !isNaN(end)) {
      let startDay = start.getDate();
      let endDay = end.getDate();
      let startMonth = monthToRoman(start.getMonth());
      let endMonth = monthToRoman(end.getMonth());
      let startYear = start.getFullYear();
      let endYear = end.getFullYear();
  
      let dateText = "";
  
      if (startYear === endYear) {
        if (start.getMonth() === end.getMonth()) {
          // Same month & year
          dateText = `${startDay}-${endDay}.${startMonth}.${startYear}`;
        } else {
          // Different month, same year
          dateText = `${startDay}.${startMonth}-${endDay}.${endMonth}.${startYear}`;
        }
      } else {
        // Different years
        dateText = `${startDay}.${startMonth}.${startYear}-${endDay}.${endMonth}.${endYear}`;
      }
  
      eventdate.innerText = prefix + dateText;
      eventdate2.remove();
    } else {
      eventdate.innerText = eventdate.innerText.trimEnd();
      eventdate2.innerText = eventdate2.innerText.replace(/^\s*-\s*/, "-");
    }
  }

  let collector = label.querySelector(".collector");
  if (collector) {
    //remove ORCID
    collector.innerText = collector.innerText.replace(/\s*\(ORCID.*\)/, "");
    
    //abbreviate first name
    let text = collector.innerText.trim();
    if (text.includes("@")) {
      let [local, domain] = text.split("@");
      if (domain === "battelleecology.org." || domain === "field-ops.org." || domain === "neoninc.org.") {
        let match = local.match(/^([a-z])([a-z]+)$/i);
        if (match) {
          let initial = match[1].toUpperCase();
          let last = match[2].charAt(0).toUpperCase() + match[2].slice(1);
          collector.innerText = `${initial}. ${last}`;
        } else {
          collector.innerText = local.charAt(0).toUpperCase() + local.slice(1);
        }
      } else {
        collector.innerText = text;
      }
    } else {
      let parts = text.split(/\s+/);
      if (parts.length > 1) {
        let first = parts[0];
        let last = parts.slice(1).join(" ");
        collector.innerText = `${first.charAt(0)}. ${last}`;
      }
    }
  }
  
});