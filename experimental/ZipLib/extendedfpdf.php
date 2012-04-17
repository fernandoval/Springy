<?php

define('FPDF_FONTPATH','../../lib/fpdf/font/');
require('fpdf.php');

class PDF extends FPDF
{
function Header()
{
        global $title;

        //Arial bold 15
        $this->SetFont('Arial','B',15);
        //Calculate width of title and position
        $w=$this->GetStringWidth($title)+6;
        $this->SetX((210-$w)/2);
        //Colors of frame, background and text
        $this->SetDrawColor(0,80,180);
        $this->SetFillColor(230,230,0);
        $this->SetTextColor(220,50,50);
        //Thickness of frame (1 mm)
        $this->SetLineWidth(1);
        //Title
        $this->Cell($w,9,$title,1,1,'C',1);
        //Line break
        $this->Ln(10);
}

function Footer()
{
        //Position at 1.5 cm from bottom
        $this->SetY(-15);
        //Arial italic 8
        $this->SetFont('Arial','I',8);
        //Text color in gray
        $this->SetTextColor(128);
        //Page number
        $this->Cell(0,10,'Page '.$this->PageNo(),0,0,'C');
}

function ChapterTitle($num,$label)
{
        //Arial 12
        $this->SetFont('Arial','',12);
        //Background color
        $this->SetFillColor(200,220,255);
        //Title
        $this->Cell(0,6,"by $label",0,1,'L',1);
        //Line break
        $this->Ln(4);
}

function ChapterBody($file)
{
        //Read text file
        $f=fopen($file,'r');
        $txt=fread($f,filesize($file));
        fclose($f);
        //Times 12
        $this->SetFont('Times','',12);
        //Output justified text
        $this->MultiCell(0,5,$txt);
        //Line break
        $this->Ln();
        //Mention in italics
        $this->SetFont('','I');
        $this->Cell(0,5,'(end of excerpt)');
}

function PrintChapter($num,$title,$file)
{
        $this->AddPage();
        $this->ChapterTitle($num,$title);
        $this->ChapterBody($file);
}



function getBuffer () {

  global $HTTP_ENV_VARS;

  if($this->state < 3) $this->Close();

  return $this->buffer;

}




}

?>