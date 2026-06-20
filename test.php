<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/BWRMS/libs/fpdf.php';

$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial','B',16);
$pdf->Cell(0,10,'FPDF Loaded Successfully!',0,1,'C');
$pdf->Output();
?>
