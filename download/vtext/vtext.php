<?php
$scriptname = "vtext.php";
$version = $scriptname." 061125";

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

<TABLE border>
EOO;
	print "<TR><TH>";
	print "<TH>".puttesthtml("column 1");
	print "<TH>".puttesthtml("column 2");
	print "<TH>".puttesthtml("column 3");
	print <<<EOO
<TR><TH>row 1
	<TD>1
		<TD>2
			<TD>3
<TR><TH>row 2
	<TD>4
		<TD>5
			<TD>6
<TR><TH>row 3
	<TD>7
		<TD>8
			<TD>9
</TABLE>

<HR>
</BODY></HTML>
EOO;
	return;
}
$command = mb_convert_encoding($command, $systeminfo->fontencode, $systeminfo->hostencode);

$size_y = $systeminfo->pos(mb_strwidth($command, $systeminfo->fontencode), 2) + 4;
$size_x = $systeminfo->pos() + 4;
$gid = imagecreate($size_x, $size_y) or die("imagecreate failed.");
$pixel0 = imagecolorresolve($gid, 255, 255, 255);
imagefilledrectangle($gid, 0, 0, $size_x - 1, $size_y - 1, $pixel0);

$pixel1 = imagecolorresolve($gid, 0, 0, 0);
imagettftext($gid, $systeminfo->pos(72, 96), 90, $size_x - 3, $size_y - 3, $pixel1, $systeminfo->font[0], $command);
header("Content-Type: image/png");
imagepng($gid);
imagedestroy($gid);
die();

?>
