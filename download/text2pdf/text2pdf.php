<?php
$version = "text2pdf 140216";

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
$systeminfo =& new systeminfo();
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
if (ereg('^([0-6])', @$_REQUEST["color"], $array))
	$color = $array[1] + 0;
$mode = @$_REQUEST["mode"] + 0;


if (@$argc > 0) {
	for ($i=1; $i<$argc; $i++) {
		$s = $argv[$i];
		if (substr($s, 0, 1) != "-")
			break;
		if (preg_match('/^-b$/', $s)) {
			$mode = 1;
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


class	page {
	var	$pid;
	var	$width = 210;
	var	$height = 297;
	var	$x = 0;
	var	$y = 0;
	var	$max_x = 120;
	var	$max_y = 85;
	var	$margin_left;
	var	$margin_top;	// from bottom
	var	$fontsize = 8;
	var	$hfontsize = 7.25;
	var	$charpitch = 4;
	var	$linepitch = 9.25;
	var	$title;
	var	$page = 1;
	var	$in_page = 0;
	function	page($title = "", $width = 210, $height = 297) {
		global	$systeminfo;
		
		$this->width = $width;
		$this->height = $height;
		
		$this->pid = pdf_new() or die("pdf_new failed.");
		if (strlen($systeminfo->pdflicense) > 0)
			pdf_set_parameter($this->pid, "license", $systeminfo->pdflicense);
		pdf_open_file($this->pid, "");
		$this->margin_left = $this->mm2pnt(20);
		$this->margin_top = $this->mm2pnt($this->height - 13) + $this->linepitch;
		$this->max_x = floor(($this->mm2pnt($this->width) - $this->margin_left * 2) / $this->charpitch);
		$this->max_y = floor($this->mm2pnt($this->height - 13 - 9) / $this->linepitch) + 1;
		$this->settitle($title);
	}
	function	mm2pnt($val) {
		return $val / 25.4 * 72;
	}
	function	putstringh($string) {
		$font = pdf_findfont($this->pid, "Courier", "winansi", 0) or die("pdf_findfont failed.");
		pdf_setfont($this->pid, $font, $this->hfontsize);
		pdf_show($this->pid, $string);
	}
	function	putstring($string) {
		global	$systeminfo;
#		$font = pdf_findfont($this->pid, $systeminfo->pdffontname, "UniJIS-UCS2-HW-H", 0) or die("pdf_findfont failed.");
		$font = pdf_findfont($this->pid, $systeminfo->pdffontname, "EUC-H", 0) or die("pdf_findfont failed.");
		pdf_setfont($this->pid, $font, $this->fontsize);
#		pdf_show($this->pid, mb_convert_encoding($string, "UCS-2", "EUC-JP"));
		pdf_show($this->pid, $string);
	}
	function	settitle($title = "") {
		$this->title = $title;
	}
	function	pdf_begin_page() {
		pdf_begin_page($this->pid, $this->mm2pnt($this->width), $this->mm2pnt($this->height));
	}
	function	pdf_end_page() {
		pdf_end_page($this->pid);
	}
	function	flushpage() {
		if ($this->in_page == 0)
			return;
		pdf_set_text_pos($this->pid, $this->mm2pnt($this->width - 30) - strlen($this->title) * $this->fontsize / 2, $this->mm2pnt($this->height - 8));
		$this->putstringh($this->title);
		pdf_set_text_pos($this->pid, ($this->mm2pnt($this->width) - $this->fontsize) / 2, $this->mm2pnt(6));
		$this->putstringh("p.".($this->page++));
		$this->pdf_end_page();
		$this->x = 0;
		$this->y = 0;
		$this->in_page = 0;
	}
	function	newpage() {
		global	$color;
		
		$this->flushpage();
		$this->pdf_begin_page();
		$this->in_page = 1;
		
		pdf_setlinewidth($this->pid, 0.5);
		pdf_setcolor($this->pid, "fillstroke", "rgb", ($color >> 1) & 1, ($color >> 2) & 1, $color & 1, 0);
		for ($x=0; $x<=$this->max_x; $x++) {
			$pnt_x = $this->margin_left + $this->charpitch * $x;
			switch ($x % 10) {
				case	0:
					pdf_moveto($this->pid, $pnt_x, $this->margin_top + $this->fontsize * 0.6);
					pdf_lineto($this->pid, $pnt_x, $this->margin_top);
					pdf_stroke($this->pid);
					continue;
				case	5:
					pdf_moveto($this->pid, $pnt_x, $this->margin_top + $this->fontsize * 0.3);
					pdf_lineto($this->pid, $pnt_x, $this->margin_top);
					pdf_stroke($this->pid);
					continue;
				default:
					pdf_circle($this->pid, $pnt_x, $this->margin_top + $this->fontsize * 0.1, 0.25);
					pdf_stroke($this->pid);
					continue;
			}
		}
		for ($y=0; $y<=$this->max_y; $y+=5) {
			pdf_set_text_pos($this->pid, $this->margin_left - $this->charpitch * 3, $this->margin_top - $this->linepitch * $y);
			$this->putstringh(($y % 10)? "-" : (($y / 10).""));
		}
	}
	function	close() {
		$this->flushpage();
		pdf_close($this->pid);
		$content = pdf_get_buffer($this->pid);
		pdf_delete($this->pid);
		$this->pid = null;
		header("Content-Type: application/pdf");
		header('Content-Disposition: inline; filename="text2pdf.pdf"');
		header("Content-Length: ".strlen($content));
		print $content;
	}
	function	checkpos($length = 0) {
		if ($this->x + $length > $this->max_x) {
			$this->x = 0;
			$this->y++;
		}
		if ($this->y >= $this->max_y)
			$this->flushpage();
	}
	function	putchar($code) {
		switch ($code) {
			case	9:
				$this->checkpos(1);
				$this->x = floor(($this->x + 8) / 8) * 8;
				return;
			case	0xa:
				$this->x = 0;
				$this->y++;
				return;
			case	0xc:
				$this->flushpage();
				return;
		}
		if ($code < 0x20)
			return;
		$length = ($code < 0x100)? 1 : 2;
		$this->checkpos($length);
		if ($this->in_page == 0)
			$this->newpage();
		pdf_set_text_pos($this->pid, $this->margin_left + $this->charpitch * $this->x, $this->margin_top - $this->linepitch * ($this->y + 1));
		if ($length == 1)
			$this->putstringh(chr($code));
		else
			$this->putstring(chr($code >> 8).chr($code & 0xff));
		$this->x += $length;
	}
}


class	page_template	extends	page {
	var	$templatelist;
	function	page_template($title = "") {
		parent::page($title);
		$this->templatelist = array();
	}
	function	pdf_begin_page() {
		$this->templatelist[] = pdf_begin_template($this->pid, $this->mm2pnt(210), $this->mm2pnt(297));
	}
	function	pdf_end_page() {
		pdf_end_template($this->pid);
	}
	function	close() {
		$this->flushpage();
		$pages = $columns = count($this->templatelist);
		while (($columns % 4))
			$columns++;
		$current = 0;
		while ($current < $columns / 2) {
			pdf_begin_page($this->pid, $this->mm2pnt(210), $this->mm2pnt(297));
			if ($current < $pages) {
				pdf_save($this->pid);
				pdf_setmatrix($this->pid, 0, 0.7, -0.7, 0, $this->mm2pnt(210), $this->mm2pnt(297 / 2));
				pdf_place_image($this->pid, $this->templatelist[$current], 0, 0, 1);
				pdf_restore($this->pid);
			}
			if ($columns - $current - 1 < $pages) {
				pdf_save($this->pid);
				pdf_setmatrix($this->pid, 0, 0.7, -0.7, 0, $this->mm2pnt(210), 0);
				pdf_place_image($this->pid, $this->templatelist[$columns - $current - 1], 0, 0, 1);
				pdf_restore($this->pid);
			}
			$current++;
			pdf_end_page($this->pid);
			pdf_begin_page($this->pid, $this->mm2pnt(210), $this->mm2pnt(297));
			if ($current < $pages) {
				pdf_save($this->pid);
				pdf_setmatrix($this->pid, 0, -0.7, 0.7, 0, 0, $this->mm2pnt(297));
				pdf_place_image($this->pid, $this->templatelist[$current], 0, 0, 1);
				pdf_restore($this->pid);
			}
			if ($columns - $current - 1 < $pages) {
				pdf_save($this->pid);
				pdf_setmatrix($this->pid, 0, -0.7, 0.7, 0, 0, $this->mm2pnt(297 / 2));
				pdf_place_image($this->pid, $this->templatelist[$columns - $current - 1], 0, 0, 1);
				pdf_restore($this->pid);
			}
			$current++;
			pdf_end_page($this->pid);
		}
		parent::close();
	}
}


#
# main
#

switch ($mode) {
	default:
		$genv =& new page();
		break;
	case	1:
		$genv =& new page_template();
		break;
	case	2:
		$genv =& new page("", 182, 257);
		break;
}
foreach ($filelist as $file) {
	$genv->settitle(@mb_convert_encoding($file["name"], "EUC-JP")."");
	if ($systeminfo->nonkf == 0)
		$fp = popen("nkf -e ".escapeshellarg($file["tmp_name"]), "r") or die("popen failed.");
	else
		$fp = fopen($file["tmp_name"], "r") or die("fopen failed.");
	
	$crlf = 0;
	while (!feof($fp)) {
		switch ($c = ord(fgetc($fp))) {
			case	0xa:
			case	0xd:
				if (($crlf)&&($crlf != $c)) {
					$crlf = 0;
					continue 2;
				}
				$crlf = $c;
				$genv->putchar(0xa);
				continue 2;
			case	0xc:
				$genv->flushpage();
				continue 2;
		}
		$crlf = 0;
		if ($c >= 0x80)
			$c = ($c << 8) | ord(fgetc($fp));
		$genv->putchar($c);
	}
	$genv->flushpage();
}
$genv->close();
?>
