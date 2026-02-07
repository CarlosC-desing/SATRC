<?php
// HTML2PDF by Clément Lavoillotte - Optimizado para FPDF 1.86
require('../../../assets/lib/fpdf186/fpdf.php');

function hex2dec($couleur = "#000000")
{
    $R = substr($couleur, 1, 2);
    $rouge = hexdec($R);
    $V = substr($couleur, 3, 2);
    $vert = hexdec($V);
    $B = substr($couleur, 5, 2);
    $bleu = hexdec($B);
    return array('R' => $rouge, 'V' => $vert, 'B' => $bleu);
}

function px2mm($px)
{
    return $px * 25.4 / 72;
}

function txtentities($html)
{
    $trans = get_html_translation_table(HTML_ENTITIES);
    $trans = array_flip($trans);
    return strtr($html, $trans);
}

class PDF_HTML extends FPDF
{
    protected $B = 0;
    protected $I = 0;
    protected $U = 0;
    protected $HREF = '';
    protected $fontlist = array('arial', 'times', 'courier', 'helvetica', 'symbol');
    protected $issetfont = false;
    protected $issetcolor = false;

    function WriteHTML($html)
    {
        // Limpieza de etiquetas y preparación
        $html = strip_tags($html, "<b><u><i><a><img><p><br><strong><em><font><tr><blockquote>");
        $html = str_replace("\n", ' ', $html);
        $a = preg_split('/<(.*)>/U', $html, -1, PREG_SPLIT_DELIM_CAPTURE);

        foreach ($a as $i => $e) {
            if ($i % 2 == 0) {
                if ($this->HREF)
                    $this->PutLink($this->HREF, $e);
                else
                    $this->Write(5, txtentities($e));
            } else {
                if ($e[0] == '/')
                    $this->CloseTag(strtoupper(substr($e, 1)));
                else {
                    $a2 = explode(' ', $e);
                    $tag = strtoupper(array_shift($a2));
                    $attr = array();
                    foreach ($a2 as $v) {
                        if (preg_match('/([^=]*)=["\']?([^"\']*)/', $v, $a3))
                            $attr[strtoupper($a3[1])] = $a3[2];
                    }
                    $this->OpenTag($tag, $attr);
                }
            }
        }
    }

    function OpenTag($tag, $attr)
    {
        switch ($tag) {
            case 'STRONG':
                $this->SetStyle('B', true);
                break;
            case 'EM':
                $this->SetStyle('I', true);
                break;
            case 'B':
            case 'I':
            case 'U':
                $this->SetStyle($tag, true);
                break;

            case 'A':
                $this->HREF = $attr['HREF'];
                break;

            case 'IMG':
                if (isset($attr['SRC']) && (isset($attr['WIDTH']) || isset($attr['HEIGHT']))) {
                    // SEGURIDAD: Evitar Path Traversal en imágenes dinámicas
                    $src = str_replace(['../', '..\\'], '', $attr['SRC']);
                    $w = isset($attr['WIDTH']) ? px2mm($attr['WIDTH']) : 0;
                    $h = isset($attr['HEIGHT']) ? px2mm($attr['HEIGHT']) : 0;
                    if (file_exists($src)) {
                        $this->Image($src, $this->GetX(), $this->GetY(), $w, $h);
                    }
                }
                break;

            case 'TR':
            case 'BLOCKQUOTE':
            case 'BR':
                $this->Ln(5);
                break;
            case 'P':
                $this->Ln(10);
                break;

            case 'FONT':
                if (isset($attr['COLOR']) && $attr['COLOR'] != '') {
                    $coul = hex2dec($attr['COLOR']);
                    $this->SetTextColor($coul['R'], $coul['V'], $coul['B']);
                    $this->issetcolor = true;
                }
                if (isset($attr['FACE']) && in_array(strtolower($attr['FACE']), $this->fontlist)) {
                    $this->SetFont(strtolower($attr['FACE']));
                    $this->issetfont = true;
                }
                break;
        }
    }

    function CloseTag($tag)
    {
        if ($tag == 'STRONG') $tag = 'B';
        if ($tag == 'EM') $tag = 'I';
        if ($tag == 'B' || $tag == 'I' || $tag == 'U') $this->SetStyle($tag, false);
        if ($tag == 'A') $this->HREF = '';
        if ($tag == 'FONT') {
            if ($this->issetcolor) $this->SetTextColor(0);
            if ($this->issetfont) {
                $this->SetFont('Arial');
                $this->issetfont = false;
            }
        }
    }

    function SetStyle($tag, $enable)
    {
        $this->$tag += ($enable ? 1 : -1);
        $style = '';
        foreach (array('B', 'I', 'U') as $s) {
            if ($this->$s > 0) $style .= $s;
        }
        $this->SetFont('', $style);
    }

    function PutLink($URL, $txt)
    {
        $this->SetTextColor(0, 0, 255);
        $this->SetStyle('U', true);
        $this->Write(5, $txt, $URL);
        $this->SetStyle('U', false);
        $this->SetTextColor(0);
    }
}
