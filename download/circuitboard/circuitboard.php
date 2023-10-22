<?php
$scriptname = "circuitboard.php";
$version = $scriptname." 100729";

class	systeminfo {
	var	$fontpath = "";
	var	$font;
	var	$hostencode = "EUC-JP";
	var	$fontencode = "UTF-8";
	var	$fontsize = 12;
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
	print "<P>".puttesthtml("......!.*..*.!.*..*.!.*..*.!.*..*.!......")."</P>";
	print "<P>".puttesthtml("......l.o..o.l.o..o.l.o..o.l.o..o.l......")."</P>";
	print "<P>".puttesthtml("......!.*(1)..*(8).!.*(2)..*(7).!.*(3)..*(6).!.*(4)..*(5).!......")."</P>";
	print "<P>".puttesthtml("........!.*6*..*3*.!.*6*..*(b99)*.!.*(a96666663)*(a36666669)..**.!.*6*..*6*.!........")."</P>";
	print "<P>".puttesthtml("..*!!*.*(r100)(u200)(l300)(d400).*!!..*")."</P>";
	print <<<EOO
</UL>

<HR>
</BODY></HTML>
EOO;
	return;
}
$command = mb_convert_encoding($command, $systeminfo->fontencode, $systeminfo->hostencode);

class	textcommand {
	var	$s, $x, $y ,$d;
	
	function	textcommand($s = "", $x = 0, $y = 0, $d = 0) {
		$this->s = $s;
		$this->x = $x;
		$this->y = $y;
		$this->d = $d;
	}
	function	draw($gid, $c, $s = 0) {
		global	$systeminfo;
		global	$left, $top;
		
		if ($s <= 0)
			$s = $systeminfo->fontsize;
		$x = ($this->x - $left) * $s;
		$y = ($this->y - $top) * $s;
		switch ($this->d) {
			case	0:
				$x += $s;
				$y += $s - 1;
				break;
			case	1:
				$x += $s - 1;
				break;
			case	3:
				$y += $s - 1;
				break;
		}
		imagettftext($gid, $systeminfo->pos(72, 96), 90 * $this->d, $x, $y, $c, $systeminfo->font[0], $this->s);
	}
}

function	linecommand(&$l, $s = "", $x = 0, $y = 0, $scale = 1)
{
	global	$left, $top, $right, $bottom;
	
	for ($i=0; $i<strlen($s); $i++) {
		$x0 = $x;
		$y0 = $y;
		switch (substr($s, $i, 1)) {
			case	"1":
			case	"4":
			case	"7":
				$x -= $scale;
				break;
			case	"3":
			case	"6":
			case	"9":
				$x += $scale;
				break;
		}
		switch (substr($s, $i, 1)) {
			case	"1":
			case	"2":
			case	"3":
				$y += $scale;
				break;
			case	"7":
			case	"8":
			case	"9":
				$y -= $scale;
				break;
		}
		$l[] = array($x0, $y0, $x, $y);
		if ($left > $x)
			$left = $x;
		if ($top > $y)
			$top = $y;
		if  ($right < $x + 1)
			$right = $x + 1;
		if  ($bottom < $y + 1)
			$bottom = $y + 1;
	}
}


$left = $top = 0;
$right = $bottom = 1;
$holelist = array(0 => array());
$textlist = array();
$linelist = array(array(), array());
$x = -1;
$y = 0;
while ($command != "") {
	$c = substr($command, 0, 1);
	$command = substr($command, 1);
# print htmlspecialchars($c)."<BR>\n";
	switch ($c) {
		case	" ":
			continue 2;
		case	".":
			$x++;
			if  ($right < $x + 1)
				$right = $x + 1;
			continue 2;
		case	"*":
		case	"o":
		case	"O":
			$x++;
			$holelist[$y][$x] = 1;
			if  ($right < $x + 1)
				$right = $x + 1;
			continue 2;
		case	"!":
		case	"l":
		case	"L":
			$x = -1;
			$y++;
			$holelist[$y] = array();
			if  ($bottom < $y + 1)
				$bottom = $y + 1;
			continue 2;
	}
	if ($x < 0)
		$x = 0;
	switch ($c) {
		case	"(":
			break;
		case	"1":
		case	"2":
		case	"3":
		case	"4":
		case	"6":
		case	"7":
		case	"8":
		case	"9":
			ereg('^([1-46-9]+)([^1-46-9].*)?$', $c.$command, $array);
			linecommand($linelist[0], $array[1], $x, $y);
			$command = $array[2];
			continue 2;
		default:
			die("unknown char:".htmlspecialchars($c));
	}
	if (($pos = strpos($command, ")")) === FALSE)
		die("not found: ')'");
	$s = substr($command, 1, $pos - 1);
	$c = substr($command, 0, 1);
	$command = substr($command, $pos + 1);
	switch ($c) {
		default:
			$textlist[] =& new textcommand($c.$s, $x, $y);
			break;
		case	"l":
			$textlist[] =& new textcommand($s, $x, $y, 2);
			break;
		case	"u":
			$textlist[] =& new textcommand($s, $x, $y, 1);
			break;
		case	"r":
			$textlist[] =& new textcommand($s, $x, $y, 0);
			break;
		case	"d":
			$textlist[] =& new textcommand($s, $x, $y, 3);
			break;
		case	"a":
			linecommand($linelist[0], $s, $x, $y, 0.5);
			break;
		case	"b":
			linecommand($linelist[1], $s, $x, $y, 0.5);
			break;
	}
}

$s = $systeminfo->fontsize;

$gid = imagecreate(($right - $left) * $s, ($bottom - $top) * $s) or die("imagecreate failed.");
imagesetthickness($gid, $s / 4);
$c0 = imagecolorresolve($gid, 255, 255, 255);
$c1 = imagecolorresolve($gid, 0, 0, 0);
$c2 = imagecolorresolve($gid, 255, 255, 128);
imagefilledrectangle($gid, 0, 0, ($right - $left) * $s, ($bottom - $top) * $s, $c2);

$c3 = imagecolorresolve($gid, 0, 128, 0);
foreach ($linelist[0] as $a) {
	imageline($gid, ($a[0] - $left) * $s + $s / 2, ($a[1] - $top) * $s + $s / 2, ($a[2] - $left) * $s + $s / 2, ($a[3] - $top) * $s + $s / 2, $c3);
}
$c4 = imagecolorresolve($gid, 255, 0, 255);
foreach ($linelist[1] as $a) {
	imageline($gid, ($a[0] - $left) * $s + $s / 2, ($a[1] - $top) * $s + $s / 2, ($a[2] - $left) * $s + $s / 2, ($a[3] - $top) * $s + $s / 2, $c4);
}

$c5 = imagecolorresolve($gid, 0, 192, 192);
foreach ($holelist as $y => $array)
	foreach ($holelist[$y] as $x => $dummy) {
		imagefilledellipse($gid, ($x - $left) * $s + $s / 2, ($y - $top) * $s + $s / 2, $s / 2, $s / 2, $c5);
		imagefilledellipse($gid, ($x - $left) * $s + $s / 2, ($y - $top) * $s + $s / 2, $s / 4, $s / 4, $c0);
	}

foreach ($textlist as $key => $dummy)
	$textlist[$key]->draw($gid, $c1, $s);

header("Content-Type: image/png");
imagepng($gid);
imagedestroy($gid);
die();

?>
