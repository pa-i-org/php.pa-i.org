<?php
$scriptname = "wavediagram.php";
$version = $scriptname." 100128";

class	systeminfo {
	var	$fontpath = "";
	var	$font;
	var	$hostencode = "EUC-JP";
	var	$fontencode = "UTF-8";
	var	$fontsize = 14;
	var	$timepitch = 16;
	var	$waveheight = 20;
	var	$wavemargin = 12;
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
	print "<P>".puttesthtml("TX+|||    |TX-start|TX+1|TX-0|0|TX+1|1|1|TX-0|0|TX+stop||||")."</P>";
	print "<P>".puttesthtml("DATA?~STRB+BUSY-~ACK+|DATA(data)1us>||~STRB-1us>|BUSY+|~STRB+1us>||DATA?>||||~ACK-1us>|BUSY-|~ACK+>|")."</P>";
	print "<P>".puttesthtml("RS?RW?E-D?|RS()RW+D!|E+|D(read)|||E-|D!|RS?RW?D?||||RS()RW-|E+||D(write)||E-|RS?RW?D?")."</P>";
	print <<<EOO
</UL>

<HR>
</BODY></HTML>
EOO;
	return;
}


function	stringwidth($s) {
	global	$systeminfo;
	
	$s = mb_convert_encoding($s, $systeminfo->fontencode, $systeminfo->hostencode);
	return mb_strwidth($s, $systeminfo->fontencode) * $systeminfo->pos(1, 2);
}


class	signal {
	var	$list;
	function	signal() {
		$this->list = array();
		$this->set(0, "?");
	}
	function	set($time, $value) {
		$this->list[$time] = $value;
	}
	function	get($time) {
		if ($time < 0)
			$time = 0;
		while (($val = @$this->list[$time]) === null)
			$time--;
		return $val;
	}
	function	draw($gid, $left, $top, $right, $bottom, $pixel_line, $pixel1, $pixel2) {
		global	$systeminfo;
		
		$list = $this->list;
		$list[$right] = ">";
		ksort($list);
		$x = $left;
		$t = $top;
		$b = $bottom;
		$p = null;
		$s = "";
		$first = 1;
		foreach ($list as $key => $val) {
			$old_x = $x;
			$x = $left + $systeminfo->timepitch * $key;
			$old_t = $t;
			$old_b = $b;
			$old_p = $p;
			$old_s = $s;
			$p = null;
			$s = "";
			switch ($val) {
				default:
					$t = $top;
					$b = $bottom;
					$s = $val;
					break;
				case	'>';
					break;
				case	'+':
					$t = $b = $top;
					break;
				case	'-':
					$t = $b = $bottom;
					break;
				case	'!':
					$t = $b = ($top + $bottom) / 2;
					break;
				case	'*':
					$t = $top;
					$b = $bottom;
					$p = $pixel2;
					break;
				case	'?':
					$t = $top;
					$b = $bottom;
					$p = $pixel1;
					break;
			}
			if (($first)) {
				$first = 0;
				continue;
			}
			if ($old_p !== null)
				imagefilledrectangle($gid, $old_x + 4, $old_t, $x - 4, $old_b, $old_p);
			if ($old_s != "") {
				$old_s = mb_convert_encoding($old_s, $systeminfo->fontencode, $systeminfo->hostencode);
				imagettftext($gid, $systeminfo->pos(72, 96), 0, ($old_x + $x - stringwidth($old_s)) / 2, ($old_t + $old_b + $systeminfo->fontsize) / 2, $pixel_line, $systeminfo->font[0], $old_s);
			}
			imageline($gid, $old_x + 4, $old_t, $x - 4, $old_t, $pixel_line);
			imageline($gid, $old_x + 4, $old_b, $x - 4, $old_b, $pixel_line);
			if (($old_t < $old_b)&&($t < $b)) {
				$i = $old_t;
				$old_t = $old_b;
				$old_b = $i;
				if ($old_p !== null)
					imagefilledpolygon($gid, array($x - 4, $old_t, $x, ($old_t + $old_b) / 2, $x - 4, $old_b), 3, $old_p);
				if ($p !== null)
					imagefilledpolygon($gid, array($x + 4, $t, $x, ($t + $b) / 2, $x + 4, $b), 3, $p);
			} else {
				if ($old_p !== null)
					imagefilledpolygon($gid, array($x - 4, $old_t, $x + 4, $t, $x + 4, $b, $x - 4, $old_b), 4, $old_p);
				if ($p !== null)
					imagefilledpolygon($gid, array($x - 4, $old_t, $x + 4, $t, $x + 4, $b, $x - 4, $old_b), 4, $p);
			}
			imageline($gid, $x - 4, $old_t, $x + 4, $t, $pixel_line);
			imageline($gid, $x - 4, $old_b, $x + 4, $b, $pixel_line);
		}
	}
}

class	comment {
	var	$start = 0;
	var	$end = -1;
	var	$string = "";
	var	$slot = 0;
	function	setstart($start = 0) {
		$this->start = $start;
	}
	function	setend($end = -1) {
		$this->end = $end;
	}
	function	setstring($string = "") {
		$this->string = $string;
	}
	function	setslot($slot = 0) {
		$this->slot = $slot;
	}
	function	stringwidth() {
		return stringwidth($this->string);
	}
	function	draw($gid, $left, $top, $pixel) {
		global	$systeminfo;
		
		$x = $l = $left + $systeminfo->timepitch * $this->start;
		$y = $top + ($systeminfo->fontsize + $systeminfo->wavemargin) * ($this->slot + 1);
		if ($this->end >= 0) {
			$r = $left + $systeminfo->timepitch * $this->end;
			$y1 = $y - $systeminfo->fontsize - 3;
			imageline($gid, $l, $y1, $r, $y1, $pixel);
			imageline($gid, $l, $y1, $l + 6, $y1 - 3, $pixel);
			imageline($gid, $l, $y1, $l + 6, $y1 + 3, $pixel);
			imageline($gid, $r - 6, $y1 - 3, $r, $y1, $pixel);
			imageline($gid, $r - 6, $y1 + 3, $r, $y1, $pixel);
			$x += ($r - $l - stringwidth($this->string)) / 2;
		}
		$s = mb_convert_encoding($this->string, $systeminfo->fontencode, $systeminfo->hostencode);
		imagettftext($gid, $systeminfo->pos(72, 96), 0, $x, $y, $pixel, $systeminfo->font[0], $s);
	}
}


$signallist = array();
$currenttime = 0;
$commentlist = array();
$openedcomment = -1;
$commentslot = array();
$labelwidth = 0;

foreach (explode("|", $command) as $chunk) {
	while (ereg('^([^-+?!(*]*)([-+?!(*])(.*)$', $chunk, $array)) {
		if (@$signallist[$array[1]] === null) {
			$signallist[$array[1]] =& new signal();
			$labelwidth = max($labelwidth, stringwidth($array[1]));
		}
		if (($array[2] == "(")&&(($pos = strpos($array[3], ")")) !== FALSE)) {
			$signallist[$array[1]]->set($currenttime, substr($array[3], 0, $pos));
			$chunk = substr($array[3], $pos + 1);
		} else {
			$signallist[$array[1]]->set($currenttime, $array[2]);
			$chunk = $array[3];
		}
	}
	do {
		if ($chunk == "")
			break;
		if ($openedcomment >= 0) {
			$commentlist[$openedcomment]->setend($currenttime);
			$openedcomment = -1;
		}
		if (($chunk == ">")||($chunk == "#"))
			break;
		$comment =& new comment();
		$comment->setstart($currenttime);
		$commentlist[] =& $comment;
		switch (substr($chunk, -1)) {
			case	">":
			case	"#":
				$openedcomment = count($commentlist) - 1;
				$chunk = substr($chunk, 0, strlen($chunk) - 1);
				break;
		}
		$comment->setstring($chunk);
		$slot = 0;
		while (@$commentslot[$slot] + 0 > $currenttime)
			$slot++;
		$comment->setslot($slot);
		$commentslot[$slot] = $currenttime + $comment->stringwidth() / $systeminfo->timepitch;
	} while (0);
	$currenttime++;
}


$size_x = $labelwidth + $systeminfo->timepitch * $currenttime - $systeminfo->timepitch / 2;
$size_y = count($signallist) * ($systeminfo->waveheight + $systeminfo->wavemargin);
$size_y += count($commentslot) * ($systeminfo->fontsize + $systeminfo->wavemargin);
$size_y += $systeminfo->wavemargin;

$gid = imagecreate($size_x, $size_y) or die("imagecreate failed.");
imagesetthickness($gid, 1);
$pixel0 = imagecolorresolve($gid, 255, 255, 255);
imagefilledrectangle($gid, 0, 0, $size_x - 1, $size_y - 1, $pixel0);
$pixel1 = imagecolorresolve($gid, 0, 0, 0);
$pixel2 = imagecolorresolve($gid, 128, 128, 128);
for ($i=1; $i<$currenttime; $i++) {
	$x = $labelwidth + $systeminfo->timepitch * $i;
	imageline($gid, $x, 0, $x, $size_y - 1, $pixel2);
}

$y = count($signallist) * ($systeminfo->waveheight + $systeminfo->wavemargin);
foreach ($commentlist as $key => $val)
	$commentlist[$key]->draw($gid, $labelwidth, $y, $pixel1);

imagesetthickness($gid, 2);
$count = 0;
foreach ($signallist as $key => $val) {
	$x = $labelwidth;
	$y = $systeminfo->wavemargin + ($systeminfo->waveheight + $systeminfo->wavemargin) * $count;
	$signallist[$key]->draw($gid, $x, $y, $currenttime, $y + $systeminfo->waveheight, $pixel1, $pixel2, $pixel2);
	
	$x -= stringwidth($key);
	$y += ($systeminfo->waveheight + $systeminfo->fontsize) / 2;
	$s = mb_convert_encoding($key, $systeminfo->fontencode, $systeminfo->hostencode);
	imagettftext($gid, $systeminfo->pos(72, 96), 0, $x, $y, $pixel1, $systeminfo->font[0], $s);
	$count++;
}

header("Content-Type: image/png");
imagepng($gid);
imagedestroy($gid);
die();

?>