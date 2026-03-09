<?php
//NEON specific class
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
}
?>
