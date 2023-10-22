<?php
$scriptname = "blockdiagram.php";
$version = $scriptname." 091208";

class	systeminfo {
	var	$fontpath = "";
	var	$font;
	var	$hostencode = "EUC-JP";
	var	$fontencode = "UTF-8";
	var	$fontsize = 18;
	var	$space = 32;
	var	$margin = 8;
	var	$arrowsize = 10;
	var	$arrowmargin = 4;
	var	$linewidth = 2;
	var	$shadow = 2;
	function	systeminfo() {
		$this->font = array();
	}
	function	pos($mul = 1, $div = 1) {
		return $this->fontsize * $mul / $div;
	}
}
$systeminfo =& new systeminfo();

include("env.php");

function	puttesthtml($string) {
	global	$scriptname;
	
	$url = $scriptname."?".urlencode($string);
	$htmlstring = htmlspecialchars($string);
	return <<<EOO
<IMG alt="{$htmlstring}" src="{$url}">
EOO;
}

if (($command = urldecode($_SERVER["QUERY_STRING"])) == "") {
	$string1 = urlencode("");
	print <<<EOO
<HTML><HEAD><TITLE>{$version}</TITLE></HEAD><BODY>
<H1>{$version}</H1>

<UL>
EOO;
	print "<P>".puttesthtml("[first]S[second]S[third]")."</P>";
	print "<P>".puttesthtml("[technology mania]nn[visionary]SE(#f00 chasm )SE[early majority]nn[late majority]")."</P>";
	print "<P>".puttesthtml("[#f00Red]#f80SE[#ff0Yellow]#8f0SS[#0f0Green]#0f8SW[#0ffCyan]#08fNW[#00fBlue]#80fNN[#f0fMagenta]#f08NE")."</P>";
	print "<P>".puttesthtml("[#efccenter]{#cccN(#fcc N )#f00n(#f88NN)}{#cccE(#8ff E )}{#cccW(#8ff W )}{#cccS (#8ff S )}")."</P>";
	print "<P>".puttesthtml("[#00fproc1]{#fffw(input1)e}#8f8S[#05fproc2]{#fffw(input2)e}#8f8S[#0afproc3]{e(output1)}#8f8S[#0ffproc4]{e(output2)}")."</P>";
	print <<<EOO
</UL>

<HR>
</BODY></HTML>
EOO;
	return;
}
$command = mb_convert_encoding($command, $systeminfo->fontencode, $systeminfo->hostencode);


class	cell {
	var	$str;
	var	$font;
	var	$width, $height;
	var	$x, $y;
	var	$type = -1;
	var	$r = 255;
	var	$g = 255;
	var	$b = 255;
	function	cell($x = 0, $y = 0) {
		$this->str = array();
		$this->font = array();
		$this->x = $x;
		$this->y = $y;
	}
	function	setstr($str = "", $typechar = "[") {
		global	$systeminfo;
		
		if ($this->type < 0) {
			$this->type = ($typechar == "(")? 1 : 0;
			if (substr($str, 0, 1) == "#") {
				$this->r = (("0x".substr($str, 1, 1)) + 0) * 0x11;
				$this->g = (("0x".substr($str, 2, 1)) + 0) * 0x11;
				$this->b = (("0x".substr($str, 3, 1)) + 0) * 0x11;
				$str = substr($str, 4);
			}
		}
		if ($str != "") {
			$this->str[] = $str;
			$this->font[] = ($typechar == "(")? 0 : 1;
		}
		$len = 0;
		foreach ($this->str as $str)
			$len = max($len, mb_strwidth($str, $systeminfo->fontencode));
		$this->width = $len * $systeminfo->pos(1, 2);
		$this->height = $systeminfo->pos() * count($this->str);
	}
	function	draw($gid) {
		global	$systeminfo, $pos_x, $pos_y, $cell_w, $cell_h;
		
		$pixel_bg = imagecolorresolve($gid, $this->r, $this->g, $this->b);
		$pixel_line = imagecolorresolve($gid, 0, 0, 0);
		$pixel_str = $pixel_line;
		if ($this->r * 2 + $this->g * 4 + $this->b < 0x300)
			$pixel_str = imagecolorresolve($gid, 255, 255, 255);
		
		$x = $pos_x[$this->x];
		$y = $pos_y[$this->y];
		$w = $cell_w[$this->x];
		$h = $cell_h[$this->y];
		$m = $systeminfo->margin;
		
		$shadow = $systeminfo->shadow;
		if (($this->type)) {
#			imageellipse($gid, $x + $w / 2 + $m, $y + $h / 2 + $m, $w + $m * 2, $h + $m * 2, $pixel_line);
			imagefilledellipse($gid, $x + $w / 2 + $m, $y + $h / 2 + $m, $w + $m * 2, $h + $m * 2, $pixel_line);
			imagefilledellipse($gid, $x + $w / 2 + $m + $shadow, $y + $h / 2 + $m + $shadow, $w + $m * 2, $h + $m * 2, $pixel_line);
			imagefilledellipse($gid, $x + $w / 2 + $m, $y + $h / 2 + $m, $w + $m * 2 - $systeminfo->linewidth * 2, $h + $m * 2 - $systeminfo->linewidth * 2, $pixel_bg);
		} else {
			imagefilledrectangle($gid, $x + $shadow, $y + $shadow, $x + $w + $m * 2 + $shadow, $y + $h + $m * 2 + $shadow, $pixel_line);
			imagefilledrectangle($gid, $x, $y, $x + $w + $m * 2, $y + $h + $m * 2, $pixel_line);
			imagefilledrectangle($gid, $x + $systeminfo->linewidth, $y + $systeminfo->linewidth, $x + $w + $m * 2 - $systeminfo->linewidth, $y + $h + $m * 2 - $systeminfo->linewidth, $pixel_bg);
		}
#		$y += ;
		foreach ($this->str as $str) {
			$offset = ($w - mb_strwidth($str, $systeminfo->fontencode) * $systeminfo->pos(1, 2)) / 2;
			imagettftext($gid, $systeminfo->pos(72, 96), 0, $x + $offset + $m, $y + $m + $systeminfo->pos() - 1, $pixel_str, $systeminfo->font[0], $str);
			$y += $systeminfo->pos();
		}
	}
}

class	arrow {
	var	$dx = 0;
	var	$dy = 0;
	var	$width = 1;
	var	$cell_x;
	var	$cell_y;
	var	$r = -1;
	var	$g = -1;
	var	$b = -1;
	function	arrow($cell_x, $cell_y) {
		$this->cell_x = $cell_x;
		$this->cell_y = $cell_y;
	}
	function	isvalid() {
		return ($this->dx || $this->dy);
	}
	function	setcolor($str) {
		$this->r = (("0x".substr($str, 0, 1)) + 0) * 0x11;
		$this->g = (("0x".substr($str, 1, 1)) + 0) * 0x11;
		$this->b = (("0x".substr($str, 2, 1)) + 0) * 0x11;
	}
	function	draw($gid) {
		global	$systeminfo, $pos_x, $pos_y, $cell_w, $cell_h;
		
		$pixel_line = $pixel_black = imagecolorresolve($gid, 0, 0, 0);
		if ($this->r < 0)
			$pixel_bg = imagecolorresolve($gid, 255, 255, 255);
		else
			$pixel_line = $pixel_bg = imagecolorresolve($gid, $this->r, $this->g, $this->b);
		
		$sx = $pos_x[$this->cell_x];
		$sy = $pos_y[$this->cell_y];
		$ex = $pos_x[$this->cell_x + $this->dx];
		$ey = $pos_y[$this->cell_y + $this->dy];
		
		if ($sx < $ex) {
			$sx += $cell_w[$this->cell_x] + $systeminfo->margin * 2 + $systeminfo->shadow + $systeminfo->arrowmargin;
			$ex -= $systeminfo->arrowmargin;
		} else if ($sx > $ex) {
			$ex += $cell_w[$this->cell_x + $this->dx] + $systeminfo->margin * 2 + $systeminfo->shadow + $systeminfo->arrowmargin;
			$sx -= $systeminfo->arrowmargin;
		} else {
			$sx += $cell_w[$this->cell_x] / 2 + $systeminfo->margin;
			$ex += $cell_w[$this->cell_x + $this->dx] / 2 + $systeminfo->margin;
		}
		if ($sy < $ey) {
			$sy += $cell_h[$this->cell_y] + $systeminfo->margin * 2 + $systeminfo->shadow + $systeminfo->arrowmargin;
			$ey -= $systeminfo->arrowmargin;
		} else if ($sy > $ey) {
			$ey += $cell_h[$this->cell_y + $this->dy] + $systeminfo->margin * 2 + $systeminfo->shadow + $systeminfo->arrowmargin;
			$sy -= $systeminfo->arrowmargin;
		} else {
			$sy += $cell_h[$this->cell_y] / 2 + $systeminfo->margin;
			$ey += $cell_h[$this->cell_y + $this->dy] / 2 + $systeminfo->margin;
		}
		
		$dx = $sx - $ex;
		$dy = $sy - $ey;
		$len = sqrt($dx * $dx + $dy * $dy);
		$ax = $dx * $systeminfo->arrowsize / $len;
		$ay = $dy * $systeminfo->arrowsize / $len;
		if ($this->width == 1) {
			if (($this->r == 255)&&($this->g == 255)&&($this->b == 255))
				return;
			
			imageline($gid, $sx, $sy, $ex, $ey, $pixel_line);
			
			imageline($gid, $ex, $ey, $ex + $ax + $ay / 2, $ey + $ay - $ax / 2, $pixel_line);
			imageline($gid, $ex, $ey, $ex + $ax - $ay / 2, $ey + $ay + $ax / 2, $pixel_line);
		} else {
			$points = array();
			$points[] = $ex;
			$points[] = $ey;
			$points[] = $ex + $dx / 2 + $ay;
			$points[] = $ey + $dy / 2 - $ax;
			$points[] = $ex + $dx / 2 + $ay / 2;
			$points[] = $ey + $dy / 2 - $ax / 2;
			$points[] = $sx + $ay / 2;
			$points[] = $sy - $ax / 2;
			$points[] = $sx - $ay / 2;
			$points[] = $sy + $ax / 2;
			$points[] = $ex + $dx / 2 - $ay / 2;
			$points[] = $ey + $dy / 2 + $ax / 2;
			$points[] = $ex + $dx / 2 - $ay;
			$points[] = $ey + $dy / 2 + $ax;
			imagefilledpolygon($gid, $points, count($points) / 2, $pixel_bg);
			imagepolygon($gid, $points, count($points) / 2, $pixel_black);
		}
	}
}


$cell_x = 0;
$cell_y = 0;
$cell_w = array(0 => 0);
$cell_h = array(0 => 0);
$celllist = array();
$arrowlist = array();
$arrow =& new arrow($cell_x, $cell_y);
$stack = array();
while ($command != "") {
	$c = substr($command, 0, 1);
	$command = substr($command, 1);
	switch ($c) {
		case	"#":
			$arrow->setcolor(substr($command, 0, 3));
			$command = substr($command, 3);
			continue;
		case	"(":
		case	"[":
			if ($arrow->isvalid())
				$arrowlist[] =& $arrow;
			$arrow =& new arrow($cell_x, $cell_y);
			
			$pos = strpos($command, (($c == "(")? ")" : "]"));
			if ($pos === FALSE)
				break;
			if (@$celllist[$cell_y][$cell_x] === null) {
				$cell =& new cell($cell_x, $cell_y);
				@$celllist[$cell_y][$cell_x] =& $cell;
			} else
				$cell =& $celllist[$cell_y][$cell_x];
			
			$cell->setstr(substr($command, 0, $pos), $c);
			$command = substr($command, $pos + 1);
			
			if (@$cell_w[$cell_x] + 0 < $cell->width)
				$cell_w[$cell_x] = $cell->width;
			if (@$cell_h[$cell_y] + 0 < $cell->height)
				$cell_h[$cell_y] = $cell->height;
			
			continue;
		case	"{":
			if ($arrow->isvalid())
				$arrowlist[] =& $arrow;
			$arrow =& new arrow($cell_x, $cell_y);
			
			$stack[] = $cell_x;
			$stack[] = $cell_y;
			continue;
		case	"}":
			$cell_y = array_pop($stack);
			$cell_x = array_pop($stack);
			
			if ($arrow->isvalid())
				$arrowlist[] =& $arrow;
			$arrow =& new arrow($cell_x, $cell_y);
			continue;
		case	"N":
			$arrow->width = 2;
		case	"n":
			$arrow->dy--;
			$cell_y--;
			continue;
		case	"E":
			$arrow->width = 2;
		case	"e":
			$arrow->dx++;
			$cell_x++;
			continue;
		case	"W":
			$arrow->width = 2;
		case	"w":
			$arrow->dx--;
			$cell_x--;
			continue;
		case	"S":
			$arrow->width = 2;
		case	"s":
			$arrow->dy++;
			$cell_y++;
			continue;
		case	" ":
			continue;
		default:
			die("Unknown command: ".htmlspecialchars($c));
	}
}

if ($arrow->isvalid())
	$arrowlist[] =& $arrow;

ksort($cell_w);
ksort($cell_h);
$size_x = $size_y = 0;
$pos_x = array();
$pos_y = array();
foreach ($cell_w as $x => $w) {
	$pos_x[$x] = $size_x + $systeminfo->space / 2;
	$size_x += $w + $systeminfo->margin * 2 + $systeminfo->space;
}
foreach ($cell_h as $y => $h) {
	$pos_y[$y] = $size_y + $systeminfo->space / 2;
	$size_y += $h + $systeminfo->margin * 2 + $systeminfo->space;
}

$gid = imagecreate($size_x, $size_y) or die("imagecreate failed.");
imagesetthickness($gid, $systeminfo->linewidth);
$pixel0 = imagecolorresolve($gid, 255, 255, 255);
imagefilledrectangle($gid, 0, 0, $size_x - 1, $size_y - 1, $pixel0);

foreach ($cell_h as $y => $dummy) {
	foreach ($cell_w as $x => $dummy2) {
		if ((@$celllist[$y][$x]))
			$celllist[$y][$x]->draw($gid);
	}
}

foreach ($arrowlist as $key => $dummy)
	$arrowlist[$key]->draw($gid);

header("Content-Type: image/png");
imagepng($gid);
imagedestroy($gid);
die();

?>