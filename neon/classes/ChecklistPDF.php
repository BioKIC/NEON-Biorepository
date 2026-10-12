<?php
require_once(__DIR__ . '/../vendor/tcpdf/tcpdf.php');

class ChecklistPDF extends TCPDF {

	public $checklistTitle = '';

	public function Header() {
		$this->Image(__DIR__ . '/../neon/images/NEON-NSF-2023.jpg', 15, 10, 40);

		// Box positions and sizes
		$startX     = 60;
		$startY     = 13.5;
		$titleWidth = 100;
		$dateWidth  = 27;
		$baseHeight = 8;

		$titleText = 'Title: ' . $this->checklistTitle . ' Dynamic Taxonomic Checklist';

		// Measure wrapped text height
		$this->SetFont('Calibri', '', 8);
		$textHeight = $this->getStringHeight($titleWidth - 4, $titleText);

		$height = max($baseHeight, $textHeight + 3); // +3 for padding

		$this->Rect($startX, $startY, $titleWidth, $height);
		$this->Rect($startX + $titleWidth, $startY, $dateWidth, $height);

		$this->SetXY($startX + 2, $startY + 1.5);
		$this->MultiCell(
			$titleWidth - 4,  // available width
			4,                // line height
			$titleText,       // text
			0,                // border
			'L',              // align left
			false             // no fill
		);

		$dateText = 'Date: ' . date('m/d/Y');
		$dateTextHeight = $this->getStringHeight($dateWidth - 4, $dateText);
		$dateY = $startY + ($height / 2) - ($dateTextHeight / 2);

		// Place date text
		$this->SetXY($startX + $titleWidth + 2, $dateY);
		$this->SetFont('calibrii', '', 8);
		$this->Write(0, 'Date: ');
		$this->SetFont('Calibri', '', 8);
		$this->Write(0, date('m/d/Y'));


		$this->Ln(18); // space after header
	}

	public function Footer() {
		$this->SetY(-15);
		$this->SetFont('Calibri', '', 10);
		$this->Cell(0, 10, 'Page ' . $this->getAliasNumPage() . ' of ' . $this->getAliasNbPages(), 0, 0, 'C');
	}

	public function downloadChecklistPdf($taxaArr, $showAuthors, $showSynonyms, $showCommon) {
		$this->SetCreator(PDF_CREATOR);
		$this->SetAuthor('NEON');
		$this->SetTitle($this->checklistTitle);
		$this->SetMargins(22, 32, 22);
		$this->SetAutoPageBreak(true, 25);
		$this->AddPage();
		$this->SetFont('Calibri', '', 11);
		//TCPDF_FONTS::addTTFfont(__DIR__ . '/../vendor/tcpdf/fonts/calibri-bold-italic.ttf', 'TrueTypeUnicode', '', 32);

		$downloadDate = date('F j, Y');
		$intro = <<<HTML
			<style>
				h1 {
					font-family: 'Calibri';
					font-size: 18.5;
					text-align: center;
					text-transform: uppercase;
				}
				h2 {
					font-family: 'Calibri';
					font-size: 14.5pt;
				}
				p, small {
					font-family: 'Calibri';
					font-size: 11pt;
				}
			</style>
			<br>
			<h1>{$this->checklistTitle} Dynamic Taxonomic Checklist</h1>
			</br>
			<h2>Introduction</h2>
			
			<p>This checklist presents taxonomic identification records derived from specimens collected and archived in the National Ecological Observatory Network (NEON) Biorepository located at Arizona State University. It was generated dynamically and reflects the most current identifications as of the date shown above.</p>
			
			<h2>Interpretation and Use</h2>
			
			<p>This checklist is intended to support research, education, and resource management by providing a structured view of the taxonomic content of NEON’s physical collections. It includes only those taxa for which specimens have been collected and cataloged in the Biorepository. Taxa observed but not collected are not represented and may instead appear in NEON’s observational data products, accessible through the NEON Data Portal. Users are encouraged to consult NEON's collection protocol documentation for details on sampling methods and identification, and are welcome to contact the NEON Biorepository with further questions or for additional clarification.</p>
			
			<h2>Scope and Limitations</h2>
			
			<p>Taxonomic identifications of specimens are fitted to NEON’s internal taxonomic backbone and may vary in rank depending on what could be reliably determined. Some specimens are identified to species, while others may be resolved only to higher levels such as genus or family due to available information or diagnostic limitations.</p>
			
			<p>Identifications reflect the best available information at the time the checklist is generated and may be informed by a combination of sources, including field determinations, post-collection review by domain or collection staff, consultation with taxonomic experts, and genetic data, depending on collection protocol. Each specimen is represented by its most current identification, standardized to NEON’s taxonomic backbone. Although all names included in the checklist are tied to physical specimens, none of these specimens are designated as formal vouchers, and the checklist as a whole has not been subjected to expert taxonomic review. As such, identification accuracy may vary, and updates or corrections should be expected as new information becomes available and records are revisited over time.</p>
			
			<p>This checklist is not intended to represent a complete or authoritative taxonomic list for any NEON location. Absence of a taxon from this document should not be interpreted as ecological absence, but rather reflects the scope and limitations of specimen-based sampling or sampling methods.</p>
			
			<h2>Record Structure</h2>
			
			<p>The checklist site allows users to customize outputs by choosing whether to include elements such as authorship, scientific synonyms, and common names. If filters are applied, some of the details described below may not be present in the output. For example, if synonyms are excluded, unaccepted names will not appear.</p>
			
			<p>This checklist is derived from taxonomic records in the NEON Biorepository. Each record can contain up to three components:</p>
			
			<ol>
			  <li><b>Scientific name</b>: the accepted taxon name associated with a specimen in the Biorepository.
				<ul>
				  <li>This may include the taxon name, scientific authority, and the taxonomic family.</li>
				</ul>
			  </li>
			  <li><b>Synonyms</b>: unaccepted or outdated names historically applied to the accepted taxon.
				<ul>
				  <li>Note that this may not be a comprehensive list of all synonyms for a given taxon.</li>
				</ul>
			  </li>
			  <li><b>Common names</b>: vernacular names, when available.</li>
			</ol>
			
			<p>Accepted scientific names are displayed in boldface and may include the author's name and publication year. If the name is not the original combination, the authority is enclosed in parentheses (e.g., <b><i>Aedes aegypti</i></b> (Linnaeus, 1762)). The associated family name follows in all caps. Synonyms, if included, appear in plain text within brackets below the accepted name (e.g., [<i>Culex aegypti</i> Linnaeus, 1762]). Common names in English, if shown, are listed beneath the scientific name in plain text.</p>
			
			
			<h3>Examples</h3>
			
			<p>A record containing an accepted name with authorship, family, a synonym, and a common name:</p>
			<p>
			  <b><i>Aedes aegypti</i></b> (Linnaeus, 1762) CULICIDAE<br>
			  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;[<i>Culex aegypti</i> Linnaeus, 1762]<br>
			  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Yellow fever mosquito
			</p>
			
			<p>A record identified only to family level:</p>
			<p>
			  <b>Carabidae sp.</b> Latreille, 1802<br>
			  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Ground Beetle
			</p>
			
			HTML;

		$this->writeHTML($intro, true, false, true, false, '');

		$this->SetY(-35);
		$this->SetFont('Calibri', '', 7);
		$this->MultiCell(0, 0,
				'The National Ecological Observatory Network is a project solely funded by the National Science Foundation and managed under cooperative agreement by Battelle. Any opinions, findings, and conclusions or recommendations expressed in this material are those of the author(s) and do not necessarily reflect the views of the National Science Foundation.',
				0, 'C', false, 1, '', '', true);

		$this->AddPage();
		$this->SetFont('Calibri', '', 14.5);
		$this->writeHTML('<br><h2 style="text-align: center;">Checklist</h2></br>', true, false, true, false, '');
		$this->SetFont('Calibri', '', 11);

		if ($taxaArr) {
			// sort by taxon name
			uasort($taxaArr, function ($a, $b) {
				$nameCompare = strcasecmp($a['taxongroup'], $b['taxongroup']);
				if ($nameCompare !== 0) {
					return $nameCompare;
				}
				return strcasecmp($a['sciname'], $b['sciname']);
			});
			$entries = array_values($taxaArr);

			$colWidth = ($this->getPageWidth() - 44) / 2; // respect left/right margins
			$xLeft = 22;
			$xRight = $xLeft + $colWidth + 2;
			$topY = 32;
			$firstPageRightStartY = $this->GetY();

			$bottomY = $this->getPageHeight() - 25;

			$currentX = $xLeft;
			$currentY = $this->GetY();
			$inLeftColumn = true;
			$previousGroup = null;

			foreach ($entries as $tArr) {
				$group = isset($tArr['taxongroup']) ? trim(str_replace(['<i>', '</i>'], '', $tArr['taxongroup'])) : null;

				if ($group && $group !== $previousGroup) {
					$groupHeader = '<br><div style="text-align:center; font-weight:bold; text-transform:uppercase; margin-bottom:6pt;">' . $group . '</div></br>';

					$groupHeight = $this->getStringHeight($colWidth, $groupHeader);

					if ($currentY + $groupHeight > $bottomY) {
						if ($inLeftColumn) {
							$currentX = $xRight;
							$currentY = ($this->getPage() === 2) ? $firstPageRightStartY : $topY;
							$inLeftColumn = false;
						} else {
							$this->AddPage();
							$currentX = $xLeft;
							$currentY = $topY;
							$inLeftColumn = true;
						}
					}

					$this->SetXY($currentX, $currentY);
					$this->writeHTMLCell($colWidth, 0, $currentX, $currentY, $groupHeader, 0, 1, false, true, 'C', true);
					$currentY = $this->GetY();
					$previousGroup = $group;
				}

				$nameLine = '';
				$name = $tArr['sciname'];

				if (strpos($name, ' ') !== false) {
					$parts = explode(' ', $name, 2);
					$genus = $parts[0];
					$rest = $parts[1];

					if (stripos($rest, 'sp.') === 0) {
						$nameLine = '<b><i>' . $genus . '</i> ' . $rest . '</b>';
					} else {
						$nameLine = '<i><b>' . $name . '</b></i>';
					}
				} else {
					$nameLine = '<b>' . $name . ' sp.</b>';
				}
				if (!empty($showAuthors) && !empty($tArr['author'])) {
					$nameLine .= ' ' . $tArr['author'];
				}
				if (!empty($tArr['family'])) {
					$nameLine .= ' ' . $tArr['family'];
				}

				$entry = '<div style="margin-left:15pt; text-indent:-15pt;">' . $nameLine . '</div>';

				if (!empty($showSynonyms) && !empty($tArr['syn'])) {
					preg_match_all('/<i>.*?<\/i>[^<]*/', $tArr['syn'], $matches);

					$first = true;
					foreach ($matches[0] as $syn) {
						$syn = trim($syn, " ,\t\n\r\0\x0B");
						if (!empty($syn)) {
							if (!$first) {
								$entry .= '<br>';
							}
							$entry .= '[' . $syn . ']';
							$first = false;
						}
					}
				}
				if (!empty($showSynonyms) && !empty($tArr['syn']) && $showCommon && !empty($tArr['vern'])) {
					$entry .= '<br>';
				}
				if ($showCommon && !empty($tArr['vern'])) {
					$entry .= $tArr['vern'];
				}

				$entryHeight = $this->getStringHeight($colWidth, $entry);

				if ($currentY + $entryHeight > $bottomY) {
					if ($inLeftColumn) {
						$currentX = $xRight;
						$currentY = ($this->getPage() === 3) ? $firstPageRightStartY : $topY;
						$inLeftColumn = false;
					} else {
						$this->AddPage();
						$currentX = $xLeft;
						$currentY = $topY;
						$inLeftColumn = true;
					}
				}

				$this->SetXY($currentX, $currentY);
				$this->writeHTMLCell($colWidth, 0, $currentX, $currentY, $entry, 0, 1, false, true, 'T', true);
				$currentY = $this->GetY();
			}
		} else {
			$this->writeHTML('<p>No taxa available.</p>', true, false, true, false, '');
		}

		$fileName = $this->checklistTitle . "_" . time() . ".pdf";
		$this->Output($fileName, 'D');
		exit();
	}

	//Setters and getters
	public function setChecklistTitle($title){
		$this->checklistTitle = $title;
	}

}
?>
