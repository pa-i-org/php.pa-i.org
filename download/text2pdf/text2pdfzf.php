<?php
$version = "text2pdf 230822";

class	systeminfo {
	var	$pdffontname = "KozMinPro-Regular-Acro";
	var	$pdflicense = "";
	var	$nonkf = 0;
	var	$op_body;
	var	$cl_body = "<HR></BODY></HTML>";
	function	systeminfo() {
		global	$version;
		$this->op_body = "<HTML><HEAD><TITLE>$version</TITLE></HEAD><BODY>";
	}
}
$systeminfo = new systeminfo();
include("env.php");

$filelist = array();
$inputlist = "";
for ($i=0; $i<10; $i++) {
	$name = ($i == 0)? "fl" : "file{$i}";
	$inputlist .= "<LI><INPUT type=file name={$name}>\n";
	$file = @$_FILES[$name];
	if (is_uploaded_file($file["tmp_name"]) === TRUE)
		$filelist[] = $file;
}
$color = 0;
if (preg_match('/^([0-6])/', @$_REQUEST["color"], $array))
	$color = $array[1] + 0;
$mode = @$_REQUEST["mode"] + 0;


$doublespace = 0;
if (@$argc > 0) {
	for ($i=1; $i<$argc; $i++) {
		$s = $argv[$i];
		if (substr($s, 0, 1) != "-")
			break;
		if (preg_match('/^-b$/', $s)) {
			$mode = 1;
			continue;
		}
		if (preg_match('/^-d$/', $s)) {
			$doublespace = 1;
			continue;
		}
		if (preg_match('/^-c([0-6])$', $s, $a)) {
			$color = $a[1] + 0;
			continue;
		}
		break;
	}
	for (; $i<$argc; $i++)
		$filelist[] = array("name" => $argv[$i], "tmp_name" => $argv[$i]);
	if (count($filelist) <= 0) {
		$s = $argv[0];
		print <<<EOO
usage: {$s} [-b] [-c1] <file>...

EOO;
		die();
	}
} else if (count($filelist) <= 0) {
	print <<<eoo
<HTML><HEAD><TITLE>$version</TITLE></HEAD><BODY>

<H1>$version</H1>

<FORM enctype="multipart/form-data" method=POST>
<OL>
{$inputlist}
</OL>
<P>
	<SELECT name=color size=0>
		<OPTION value=0 selected>K</OPTION>
		<OPTION value=1>B</OPTION>
		<OPTION value=2>R</OPTION>
		<OPTION value=3>M</OPTION>
		<OPTION value=4>G</OPTION>
		<OPTION value=5>C</OPTION>
		<OPTION value=6>Y</OPTION>
	</SELECT>
	<SELECT name=mode size=0>
		<OPTION value=0>single</OPTION>
		<OPTION value=1>booklet</OPTION>
		<OPTION value=2>B5</OPTION>
	</SELECT>
	<INPUT type=submit>
</P>
</FORM>

<HR>
</BODY></HTML>
eoo;
	return;
}


set_include_path(get_include_path().PATH_SEPARATOR."/project/cdrom/ZendFramework-1.11.6/library/");
require_once('Zend/Loader/Autoloader.php');
Zend_Loader_Autoloader::getInstance();

ini_set("max_execution_time", "300");
ini_set("memory_limit", "1024M");


class	nullbook {
	var	$x = 0;
	var	$y = 0;
	var	$max_x = 120;
#	var	$max_y = 85;
	var	$max_y = 70;
	var	$pagecount = 0;
	var	$linecount = 1;
	var	$lastlinecount = 0;
	var	$in_page = 0;
	var	$currenttitle = "";
	function	__construct() {
	}
	function	openpage() {
		if (($this->in_page))
			return;
		$this->in_page = 1;
		$this->pagecount++;
		$this->x = 0;
		$this->y = 0;
	}
	function	closepage() {
		if ($this->in_page == 0)
			return;
		$this->in_page = 0;
	}
	function	newline() {
		global	$doublespace;
		
		$this->openpage();
		$this->x = 0;
		$this->y++;
		if (($doublespace))
			$this->y++;
		if ($this->y >= $this->max_y)
			$this->closepage();
	}
	function	checkremain($w = 1) {
		$this->openpage();
		if ($this->x + $w >= $this->max_x)
			$this->newline();
	}
	function	drawc($c) {
		$this->openpage();
	}
	function	draww($c) {
		$this->openpage();
	}
	function	drawcontent($content) {
		$crlf = 0;
		$pos = 0;
		$istitle = 0;
		while ($pos < strlen($content)) {
			switch ($c = ord(substr($content, $pos++, 1))) {
				case	1:
					if (($istitle ^= 1))
						$this->currenttitle = "";
					$this->lastlinecount = 0;
					break;
				case	9:
					$crlf = 0;
					$this->checkremain(1);
					$this->x += 8 - ($this->x % 8);
					break;
				case	0xa:
				case	0xd:
					if (($crlf)&&($crlf != $c)) {
						$crlf = 0;
						continue 2;
					}
					$crlf = $c;
					$this->newline();
					$this->linecount++;
					break;
				case	0xc:
					$crlf = 0;
					$this->closepage();
					$this->linecount++;
					break;
				default:
					$crlf = 0;
					if (($istitle))
						$this->currenttitle .= chr($c);
					else if ($c < 0x80) {
						$this->checkremain();
						$this->drawc(chr($c));
						$this->x++;
					} else {
						$this->checkremain(2);
						$this->draww(chr($c).substr($content, $pos++, 1));
						$this->x += 2;
					}
					break;
			}
		}
	}
	function	getoutput($header = 0) {
		return $this->pagecount;
	}
}


class	pdfbook	extends	nullbook {
	var	$pdf = null;
	var	$page = null;
	var	$fontc = null;
	var	$fontw = null;
#	var	$fontsize = 2.5;
	var	$fontsize = 3;
	var	$charpitch = 1.5;
	var	$linepitch = 3.75;
#	var	$leftmargin = 15;
	var	$leftmargin = 18;
	var	$topmargin = 15;
	var	$lastfont = -1;
	function	__construct() {
		global	$systeminfo;
		
		parent::__construct();
		$this->pdf = new Zend_Pdf();
		
#		$fontname = "{$systeminfo->zffontpath}HGRHR4.TTC";
#		$fontname = "{$systeminfo->zffontpath}ipam.ttf";
		$fontname = "{$systeminfo->zffontpath}ipag.ttf";
#		$font = Zend_Pdf_Font::fontWithPath("{$fontname}:1");
		$this->fontc = Zend_Pdf_Font::fontWithPath("{$fontname}");
		$fontname = "{$systeminfo->zffontpath}HGRHR8.TTC";
		$this->fontw = Zend_Pdf_Font::fontWithPath("{$fontname}");
	}
	function        mm2pnt($val) {
	        return  $val / 25.4 * 72;
	}
	function	newpage() {
#		$this->page = $this->pdf->newPage(Zend_Pdf_Page::SIZE_A4_LANDSCAPE);
		$this->page = $this->pdf->newPage(Zend_Pdf_Page::SIZE_A4);
		$this->pdf->pages[] = $this->page;
	}
	function	openpage() {
		if (($this->in_page))
			return;
		parent::openpage();
		$this->newpage();
		
		$this->page->setFont($this->fontc, $this->mm2pnt($this->fontsize));
		$this->lastfont = 0;
		
		$a = explode(" - ", $this->currenttitle, 2);
		
		$i = $this->page->getTextWidth($a[1], "UTF-8");
		$this->page->drawText($a[1], $this->mm2pnt(200) - $i, $this->mm2pnt(287), "UTF-8");
		
		$this->page->drawText("p.".$this->pagecount, $this->mm2pnt(100), $this->mm2pnt(10), "UTF-8");
		$i = $this->page->getTextWidth($a[0], "UTF-8");
		$this->page->drawText($a[0], $this->mm2pnt(200) - $i, $this->mm2pnt(10), "UTF-8");
		for ($i=4; $i<$this->max_y; $i+=5) {
			if (($i % 10) != 9)
				$c = "-";
			else
				$c = round($i / 10);
			$y = $this->mm2pnt(297 - $this->topmargin - $this->fontsize - $this->linepitch * $i);
			$this->page->drawText($c, $this->mm2pnt($this->leftmargin - 10), $y, "UTF-8");
		}
		
		$this->page->setLineWidth($this->mm2pnt(0.1));
#		$y = $this->mm2pnt(297 - $this->topmargin + $this->linepitch);
#		$y = $this->mm2pnt(297 - $this->topmargin + $this->fontsize);
		$y = $this->mm2pnt(297 - $this->topmargin + 1);
		for ($i=0; $i<=$this->max_x; $i++) {
			$x = $this->mm2pnt($this->leftmargin + $this->charpitch * $i);
			switch ($i % 10) {
				default:
					$y0 = $y + $this->mm2pnt(0.5);
					break;
				case	0:
					$y0 = $y + $this->mm2pnt(2);
					break;
				case	5:
					$y0 = $y + $this->mm2pnt(1);
					break;
			}
			$this->page->drawLine($x, $y, $x, $y0);
		}
	}
	function	checkremain($w = 1) {
		parent::checkremain($w);
		if ($this->lastlinecount != $this->linecount) {
			$this->lastlinecount = $this->linecount;
			
			$x = $this->mm2pnt($this->leftmargin - 5);
			$y = $this->mm2pnt(297 - $this->topmargin - $this->fontsize - $this->linepitch * $this->y);
			$this->page->setFont($this->fontc, $this->mm2pnt($this->fontsize / 2));
			$this->lastfont = -1;
			$this->page->drawText($this->linecount."", $x, $y, "UTF-8");
		}
	}
	function	drawc($c) {
		parent::drawc($c);
		
		$x = $this->mm2pnt($this->leftmargin + $this->charpitch * $this->x);
		$y = $this->mm2pnt(297 - $this->topmargin - $this->fontsize - $this->linepitch * $this->y);
		if ($this->lastfont != 0) {
			$this->page->setFont($this->fontc, $this->mm2pnt($this->fontsize));
			$this->lastfont = 0;
		}
		$this->page->drawText($c, $x, $y, "UTF-8");
	}
	function	draww($c) {
		parent::draww($c);
		
		$x = $this->mm2pnt($this->leftmargin + $this->charpitch * $this->x);
		$y = $this->mm2pnt(297 - $this->topmargin - $this->fontsize - $this->linepitch * $this->y);
		if ($this->lastfont != 1) {
			$this->page->setFont($this->fontw, $this->mm2pnt($this->fontsize));
			$this->lastfont = 1;
		}
		$this->page->drawText(mb_convert_encoding($c, "UTF-8", "EUC-JP"), $x, $y, "UTF-8");
	}
	function	getoutput($header = 0) {
		$content = $this->pdf->render();
		if (($header)) {
			header("Content-Type: application/pdf");
			header("Content-Length: ".strlen($content));
			header("Content-Disposition: inline; filename=test.pdf");
		}
		return $content;
	}
}


class	pdffoldbook	extends	pdfbook {
	var	$totalpages;
	function	__construct($pages = 1) {
		parent::__construct();
		
		$this->totalpages = $pages + ((4 - ($pages % 4)) % 4);
		for ($i=0; $i<$this->totalpages; $i+=4) {
#		for ($i=0; $i<$this->totalpages; $i+=2) {
			$page = $this->pdf->newPage(Zend_Pdf_Page::SIZE_A4);
			$this->pdf->pages[] = $page;
			$page->translate($this->mm2pnt(210), $this->mm2pnt(148));
			$page->scale(0.7, 0.7);
			$page->rotate(0, 0, 3.14159 / 2);
			
			$page = $this->pdf->newPage(Zend_Pdf_Page::SIZE_A4);
			$this->pdf->pages[] = $page;
			$page->translate(0, $this->mm2pnt(297));
			$page->scale(0.7, 0.7);
			$page->rotate(0, 0, -3.14159 / 2);
		}
	}
	function	newpage() {
		$i = $this->pagecount - 1;
		if ($i < $this->totalpages / 2) {
			$this->page = $this->pdf->pages[$i];
			return;
		}
		$this->page = $this->pdf->pages[$this->totalpages - 1 - $i];
		if (($i % 2))
			$this->page->translate(-$this->mm2pnt(210), 0);
		else
			$this->page->translate($this->mm2pnt(210), 0);
	}
#	function	closepage() {
#		if ($this->in_page == 0)
#			return;
#		$this->page->restoreGS();
#		parent::closepage();
#	}
}


$content = "";
$date = date("ymd-His");
foreach ($filelist as $file) {
	$sha1 = sha1_file($fn = $file["tmp_name"]);
	if ($systeminfo->nonkf == 0)
		$fp = popen("nkf -e ".escapeshellarg($fn), "r") or die("popen failed.");
	else
		$fp = fopen($fn, "r") or die("fopen failed.");
	$s = stream_get_contents($fp);
	pclose($fp);
	
#	$content .= chr(1).str_replace(chr(1), "", @mb_convert_encoding($file["name"], "EUC-JP")."").chr(1);
	$content .= chr(1).str_replace(chr(1), "", "{$date}-{$sha1} - ".@$file["name"]).chr(1);
	$content .= str_replace(chr(1), "", $s).chr(0xc);
}


$book = new nullbook();
$book->drawcontent($content);
if (($pagecount = $book->pagecount) <= 0)
	die("no page.");
#print $book->getoutput(1);
#$book = new pdfbook();
$book = new pdffoldbook($pagecount);
$book->drawcontent($content);
print $book->getoutput(1);

?>
